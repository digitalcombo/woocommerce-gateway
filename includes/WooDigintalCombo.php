<?php

class WooDigintalCombo  extends WC_Payment_Gateway 
{
	function __construct() 
	{
		$this->id                 = WC_DC_FIG::ID;
		$this->icon               = WC_DC_FIG::ICO;
		$this->has_fields         = WC_DC_FIG::HAS_FIELDS;
		$this->method_title       = WC_DC_FIG::METHOD_TITLE;
		$this->method_description = WC_DC_FIG::HAS_DESCRIPT;
		$this->order_button_text  = WC_DC_FIG::TEXT_BUTTON;		
		$this->init_form_fields();
		$this->init_settings();
		$this->title               = $this->get_option( 'title' );
		$this->description         = $this->get_option( 'description' );
		$this->instructions        = $this->get_option( 'instructions', $this->description );
		$this->id_vendedor         = $this->get_option( 'SELLER_ID' );
		$this->pagar_como          = $this->get_option( 'pagar_como' );
		$this->vencimento_boleto   = $this->get_option( 'vencimento_boleto' );
		$this->mode_dev            = $this->get_option( 'mode_dev' );

		add_action( 'woocommerce_update_options_payment_gateways_'. $this->id, [ $this, 'process_admin_options'] );		
	}
	
	public function init_form_fields() 
	{	  
		$this->form_fields = apply_filters( 'wc_offline_form_fields', DigitalFig::fields() );
	}	

	public function process_payment( $pedido_id ) 
	{	
		global $woocommerce;
		$pedido           = new WC_Order( $pedido_id );		
		$tipo_transacao   = isset( $_POST["type_pagamento"] ) ? $_POST["type_pagamento"]: "cartao_credito" ;
		$validar_trasacao = false;

		switch ( $tipo_transacao ) 
		{
			case 'cartao_debito':
				$validar_trasacao = $this->cartao_debito( $pedido );
				break;
				
			case 'cartao_credito':
				$validar_trasacao = $this->cartao_credito( $pedido );
				break;
				
			case 'boleto':
			default:
				$validar_trasacao = $this->boleto( $pedido );
				break;
		}

		if( $validar_trasacao ) 
		{
			$pedido->update_status( 'on-hold', 'Aguardando Confirmação de pagamentp' );		
			$woocommerce->cart->empty_cart();
		}


		return array(
			'result' 	=> $validar_trasacao ? 'success' : 'error',
			'redirect'	=> $this->get_return_url( $pedido )
		);
	}
	
	public function payment_fields()
	{
		$modo_de_pagamento = $this->pagar_como;
		include_once __DIR__ . "/../public/formulario-tramparent-dc.php";
	}

	public function debug( $teste, $isJson = false )
	{
		if( $isJson ) {
			file_put_contents( __DIR__ . "/../log/trasasion-" . Date( 'Y-m-d-H-i' ) . ".json", json_encode( $teste ) );
		} else {
			file_put_contents( __DIR__ . "/../log/trasasion-" . Date( 'Y-m-d-H-i' ) . ".json", $teste );
		}
	}

	public function boleto( $pedido )
	{
		// billing
		// $pedido = gettype( $pedido );

		$gateway    = new Gateway;
		$usuario    = [
			"first_name"  => $pedido->get_billing_first_name(), 
			"last_name"   => $pedido->get_billing_last_name(),
			"taxpayer_id" => $pedido->get_meta('_billing_cpf'),
			"email"       => $pedido->get_billing_email(), 
			"address"     => [
				"line1"        => $pedido->get_billing_address_1(), 
				"line2"        => $pedido->get_billing_address_2(), 
				"neighborhood" => $pedido->get_meta('_billing_bairro'), 
				"city"         => $pedido->get_billing_city(), 
				"state"        => $pedido->get_billing_state(), 
				"postal_code"  => $pedido->get_billing_postcode(), 
				"country_code" => "BR" 
			]
		];
		$compra = [
			"amount"         => str_replace( '.', '', $pedido->total ),
			"currency"       => "BRL",
			"description"    => "venda",
			"logo"           => "https://i.imgur.com/YrjT5ye.png",
			"payment_method" => [
				"expiration_date" => $this->additionalDays( $this->vencimento_boleto )
			]
		];
		$boleto     = $gateway->boleto( $usuario, $compra, $this->id_vendedor );
		$validacao  = isset( $boleto->error ) ? false : true;
		if ( $validacao )
		{
			$ID     = $boleto->payment_method->id;
			$CODE   = $boleto->payment_method->barcode;
			$BOLETO = $boleto->payment_method->url;
			$pedido->add_order_note(  "CODIGO DE BARRAS: $CODE", 'woothemes'  );
			$pedido->add_order_note(  "TOKEN PEDIDO: $ID", 'woothemes'  );
			$pedido->add_order_note(  "URL BOLETO: $BOLETO", 'woothemes'  );
			$this->debug( $boleto, true );
		}
		$this->debug( $boleto , true );	
		return $validacao;
	}
	public function cartao_credito( $pedido, $venda_debito = false )
	{
		$gateway    = new Gateway;
		$mes_ano    = explode( '/', $_POST["card_valid"] );
		$cartao     = [
			"nome"   => $_POST["card_name"] ?? "",
			"numero" => $_POST["card_number"] ?? "",
			"cvv"    => $_POST["card_cvv"] ?? "",
			"mes"    => $mes_ano[0] ?? "",
			"ano"    => $mes_ano[1] ?? "",
		];
		$pagar_com_cartao = $gateway->transCard(
			[
				"amount"       => str_replace( '.', '', $pedido->total ),
				"payment_type" => $venda_debito ? "debit" :"credit",
				'customerID'   => '',
				"on_behalf_of" => $this->id_vendedor,
				"card"  => [
					"holder_name"      => $cartao["nome"],
					"expiration_month" => $cartao["mes"],
					"expiration_year"  => $cartao["ano"],
					"card_number"      => $cartao["numero"],
					"security_code"    => $cartao["cvv"]
				],
				"customer" => [
					"first_name"  => $pedido->get_billing_first_name(), 
					"last_name"   => $pedido->get_billing_last_name(),
					"taxpayer_id" => $pedido->get_meta('_billing_cpf'),
					"email"       => $pedido->get_billing_email(), 
					"address" => [
						"line1"        => $pedido->get_billing_address_1(), 
						"line2"        => $pedido->get_billing_address_2(), 
						"neighborhood" => $pedido->get_meta('_billing_bairro'), 
						"city"         => $pedido->get_billing_city(), 
						"state"        => $pedido->get_billing_state(), 
						"postal_code"  => $pedido->get_billing_postcode(), 
						"country_code" => "BR" 
					]
				]
			]
		);
		file_put_contents( __DIR__ . "/../log/_card-" . Date( 'Y-m-d-H-i' ) . ".json", json_encode( $pagar_com_cartao ) );

		$validacao = isset( $pagar_com_cartao->error ) ? false : true;
		if ( $validacao )
		{
			$ID     = $pagar_com_cartao->payment_method->id;
			$pedido->add_order_note(  "TOKEN PEDIDO: $ID", 'woothemes' );
		}
		return $validacao;
	}
	public function cartao_debito( $pedido ) 
	{
		$this->cartao_credito( $pedido, true );
	}
	public function additionalDays( string $day ) 
	{
		$date = date_create( Date( 'Y-m-d' ) );
		date_add( $date, date_interval_create_from_date_string( "$day days" ) );
		return date_format( $date, 'Y-m-d' );
	}

}