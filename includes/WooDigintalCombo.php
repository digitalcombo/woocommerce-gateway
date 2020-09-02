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
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );
		$this->id_vendedor  = $this->get_option( 'SELLER_ID' );
		add_action( 'woocommerce_update_options_payment_gateways_'. $this->id, [ $this, 'process_admin_options'] );		
	}
	
	public function init_form_fields() 
	{	  
		$this->form_fields = apply_filters( 'wc_offline_form_fields', DigitalFig::fields() );
	}

	public function process_payment( $pedido_id ) 
	{
		global $woocommerce;
		$pedido = new WC_Order( $pedido_id );		
		$tipo_transacao   = "cartao_credito";
		$validar_trasacao = false;
		if( $tipo_transacao ==  "cartao_credito" ) {
			$validar_trasacao = $this->cartao_credito( $pedido );
		} else {
			$validar_trasacao = $this->boleto( $pedido );
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
		include_once __DIR__ . "/../public/formulario-tramparent-dc.php";
	}

	public function debug( $teste )
	{
		file_put_contents( __DIR__ . "/../debug.json", $teste );
	}

	public function boleto( $pedido )
	{
		$gateway    = new Gateway;
		$boleto     = $gateway->boleto(
			[
				"first_name"  => $pedido->billing->first_name, 
				"last_name"   => $pedido->billing->last_name,
				"taxpayer_id" => "571.615.310-04",
				"email"       => $pedido->billing->email, 
				"address"     => [
					"line1"        => $pedido->billing->address_1, 
					"line2"        => $pedido->billing->address_2, 
					"neighborhood" => "A completar Bairro", 
					"city"         => $pedido->billing->city, 
					"state"        => $pedido->billing->state, 
					"postal_code"  => $pedido->billing->postcode, 
					"country_code" => "BR" 
				]
			],
			[
				"amount"       => $pedido->total,
				"currency"     => "BRL",
				"description"  => "venda",
				"payment_type" => "boleto"
			]
		);
		return true;
	}	
	public function cartao_credito( $pedido )
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
				"source" => [
					"usage" => "single_use",
					"card"  => [
						"holder_name"      => $cartao["nome"],
						"expiration_month" => $cartao["mes"],
						"expiration_year"  => $cartao["ano"],
						"card_number"      => $cartao["numero"],
						"security_code"    => $cartao["cvv"]
					],
					"currency" => "BRL",
					"type"     => "card",
					"amount"   => "str_replace( '.', '', $pedido->total )"
				],
				"amount"       => "str_replace( '.', '', $pedido->total )",
				"currency"     => "BRL",
				"description"  => "Venda",
				"on_behalf_of" => $this->id_vendedor,
				"payment_type" => "credit"
			]	
		);
		$reposta = json_decode( $pagar_com_cartao );
		$this->debug( $reposta );
		return isset( $reposta->error ) ? false : true;
	}
}