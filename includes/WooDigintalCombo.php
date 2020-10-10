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

		
		$this->plan_id             = false;
		$this->plan_grace          = $this->get_option( 'dias_carencia' ) ? $this->get_option( 'dias_carencia' ) : 3;
		$this->plan_tolerance      = $this->get_option( 'periodo_tolerancia' ) ? $this->get_option( 'periodo_tolerancia' ) : 3;
		$this->plan_frequency      = "monthly";
		$this->plan_amount         = 0;


		$this->split           = $this->get_option( 'split' );
		$this->split_prezuiso  = $this->get_option( 'prezuiso_split' );
		$this->split_liquido   = $this->get_option( 'liquido_split' );
		$this->split_percent   = $this->get_option( 'percentual_split' );
		$this->split_valor     = $this->get_option( 'valor_split' );
		$this->split_seller    = $this->get_option( 'id_split' );



		add_action( 'woocommerce_update_options_payment_gateways_'. $this->id, [ $this, 'process_admin_options'] );		
	}
	
	public function init_form_fields() 
	{	  
		$this->form_fields = apply_filters( 'wc_offline_form_fields', DigitalFig::fields() );
	}

	public function products_recorrente( $pedido_id, $pedido_type = 'credit' )
	{
		$pedido = new WC_Order( $pedido_id );
		$resposta = null;
		foreach( $pedido->get_items() as $product ) {
			$recorrente = get_post_meta( $product["product_id"] , '_recorrente', true );
			if( !empty( $recorrente ) ) {
				$this->plan_frequency = $recorrente;
				$this->plan_amount    = $product['subtotal'];
				$this->new_plan();
				$resposta = $this->new_sub( $pedido_id, $pedido_type );
			} 
             
		}
		file_put_contents( 
			__DIR__ . "/../log/_card-recorrente-" . Date( 'Y-m-d-H-i' ) . '-'. uniqid() . ".json", 
			json_encode( $resposta ) 
		);

		return $resposta;

	}

	public function has_products_recorrente( $pedido_id )
	{
		$pedido = new WC_Order( $pedido_id );
		foreach( $pedido->get_items() as $product ) {
			$recorrente = get_post_meta( $product["product_id"] , '_recorrente', true );
			if( !empty( $recorrente ) ) {
				return true;
			}
		}
		return false;

	}

	public function process_payment( $pedido_id ) 
	{	
		global $woocommerce;
		$pedido           = new WC_Order( $pedido_id );		
		$tipo_transacao   = isset( $_POST["type_pagamento"] ) ? $_POST["type_pagamento"]: "cartao_credito" ;
		$validar_trasacao = false;
		$has_recorrente   = $this->has_products_recorrente( $pedido_id );

		switch ( $tipo_transacao ) 
		{
			case 'cartao_debito':
				$validar_trasacao = $this->cartao_debito( $pedido );
				$this->products_recorrente( $pedido_id, 'debit' );
				break;
				
			case 'cartao_credito':
				if( $has_recorrente ) {
					$validar_trasacao = $this->products_recorrente( $pedido_id, 'credit' );
					$validar_trasacao = $this->cartao_credito( $pedido, 'credit' );
				} else {
					$validar_trasacao = $this->cartao_credito( $pedido );
				}
				break;
				
			case 'boleto':
			default:
				$validar_trasacao = $this->boleto( $pedido );
				// $this->products_recorrente( $pedido_id, 'boleto' );
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

	public function debug( $teste, $isJson = false, $prefix = 'trasasion' )
	{
		if( $isJson ) {
			file_put_contents( __DIR__ . "/../log/$prefix-" . Date( 'Y-m-d-H-i' ) . uniqid() . ".json", json_encode( $teste ) );
		} else {
			file_put_contents( __DIR__ . "/../log/$prefix-" . Date( 'Y-m-d-H-i' ) . uniqid() . ".json", $teste );
		}
	}

	public function boleto( $pedido )
	{

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
			'on_behalf_of'	 => $this->id_vendedor,
			"customerID"     => $this->getCustomerID( 'boleto' ),
			"amount"         => str_replace( '.', '', $pedido->total ),
			"currency"       => "BRL",
			"description"    => "venda",
			"logo"           => "https://i.imgur.com/YrjT5ye.png",
			"payment_method" => [
				"expiration_date" => $this->additionalDays( $this->vencimento_boleto )
			]
		];
		$splitRules = $this->getSplitRules();
		$boleto     = $gateway->boleto( $usuario, $compra, $splitRules );
		$validacao  = isset( $boleto->error ) ? false : true;
		if ( $validacao )
		{
			$ID     = $boleto->payment_method->id;
			$CODE   = $boleto->payment_method->barcode;
			$BOLETO = $boleto->payment_method->url;
			$pedido->add_order_note(  "CODIGO DE BARRAS: $CODE", 'woothemes'  );
			$pedido->add_order_note(  "TOKEN PEDIDO: $ID", 'woothemes'  );
			$pedido->add_order_note(  "URL BOLETO: $BOLETO", 'woothemes'  );
		}
		$this->debug( $boleto , true );	
		return $validacao;
	}

	public function getCustomerID( $venda_type = "credit")
	{
		$idUser =  get_current_user_id();
		return get_post_meta( $idUser, "customerID_$venda_type", true );
	}

	public function getSplitRules() 
	{
		// if( !empty( $this->split ) ) :
			$splits = [];
			if( !empty( $this->get_option( 'id_split' ) ) ) :
				$splits[] = [
					"recipient"             => $this->get_option( 'id_split' ),
					"percentage"            => (int) $this->get_option( 'percentual_split' ),
					"amount"                => (int) $this->get_option( 'valor_split' ),
					"charge_processing_fee" => (int) $this->split_liquido,
					"liable"                => (int) $this->split_prezuiso,
				];
			endif; 
			if( !empty( $this->get_option( 'id_split_2' ) ) ) :
				$splits[] = [
					"recipient"             => $this->get_option( 'id_split_2' ),
					"percentage"            => (int) $this->get_option( 'percentual_split_2' ),
					"amount"                => (int) $this->get_option( 'valor_split_2' ),
					"charge_processing_fee" => (int) $this->split_liquido,
					"liable"                => (int) $this->split_prezuiso,
				];
			endif; 
			if( !empty( $this->get_option( 'id_split_3' ) ) ) :
				$splits[] = [
					"recipient"             => $this->get_option( 'id_split_3' ),
					"percentage"            => (int) $this->get_option( 'percentual_split_3' ),
					"amount"                => (int) $this->get_option( 'valor_split_3' ),
					"charge_processing_fee" => (int) $this->split_liquido,
					"liable"                => (int) $this->split_prezuiso,
				];
			endif; 
			return [
				'split_rules' => $splits
			];
		// endif;
		// return [];
	}

	public function cartao_credito( $pedido, $venda_type = "credit" )
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
		
		$splitRules = $this->getSplitRules();
    
		$pagar_com_cartao = $gateway->transCard(
			[
				"amount"       => str_replace( '.', '', $pedido->total ),
				"payment_type" => $venda_type,
				'customerID'   => $this->getCustomerID( $venda_type ),
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
			], $splitRules
		);
		if( empty( $this->getCustomerID() ) )
		{
			$idUser =  get_current_user_id();
			update_post_meta( $idUser, "customerID_$venda_type", $pagar_com_cartao->customer );
		}
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
		$this->cartao_credito( $pedido, 'debit' );
	}
	public function additionalDays( string $day ) 
	{
		$date = date_create( Date( 'Y-m-d' ) );
		date_add( $date, date_interval_create_from_date_string( "$day days" ) );
		return date_format( $date, 'Y-m-d' );
	}
	public function new_plan()
	{
		$gateway = new Gateway;
		$plan    = [
			"frequency" 	   => $this->plan_frequency,
			"interval"         => 1,
			"payment_methods"  => ["credit"],
			"amount"		   => $this->plan_amount,
			"description" 	   => "Plano Mensal Especial Silver",
			"name"             => "Plano Silver",
			"grace_period"     => $this->plan_grace ,
			"tolerance_period" => $this->plan_tolerance ,
			"currency"         => "BRL",
			"duration"         => 12
		];
		$response      = $gateway->createPlan($plan);
		$this->debug( $response, true, 'new-plan--'  );
		$this->plan_id = $response->id;
		return $response->id;
	}

	public function new_sub( $pedido_id, $pedido_type = 'credit' ) {
		$gateway = new Gateway;
		$pedido  = new WC_Order( $pedido_id );	
		$custome = [
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
		
		
		$mes_ano    = explode( '/', $_POST["card_valid"] );
		$card = [
			"expiration_month" => $mes_ano[0] ?? "",
			"holder_name"      => $_POST["card_name"] ?? "",
			"expiration_year"  => $mes_ano[1] ?? "",
			"security_code"    => $_POST["card_cvv"] ?? "",
			"card_number"      => $_POST["card_number"] ?? ""
		];
		$resposta = $gateway->subscriptions( [ 
			'customerID'  => $this->getCustomerID( $pedido_type ),
			'paymentType' => $pedido_type,
			'idPlan' 	  => $this->plan_id,
			'idVendedor'  => $this->id_vendedor,
			'card'        => $card,
			'customer'    => $custome, 
			'dueDate'     => '2021-01-26'
		]); 
		return $resposta;
	}

}