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
		$pedido->update_status( 'on-hold', 'Aguardando Confirmação de pagamentp' );		
		$woocommerce->cart->empty_cart();		
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $pedido )
		);
	}
	
	public function payment_fields()
	{
		include_once __DIR__ . "/../public/formulario-tramparent-dc.php";
	}


}