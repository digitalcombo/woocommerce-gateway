<?php

class WooDigintalCombo  extends WC_Payment_Gateway 
{
 
	function __construct() {	 
		
		$this->id                 = WC_DC_FIG::ID;
		$this->icon               = WC_DC_FIG::ICO;
		$this->has_fields         = WC_DC_FIG::HAS_FIELDS;
		$this->method_title       = WC_DC_FIG::METHOD_TITLE;
		$this->method_description = WC_DC_FIG::HAS_DESCRIPT;
		
		$this->init_form_fields();
		$this->init_settings();

		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );
	

		add_action( 'woocommerce_update_options_payment_gateways_'. $this->id, [ $this, 'process_admin_options'] );
		
		// add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		// add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		
		// add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}
	
	

		public function init_form_fields() {	  
			$this->form_fields = apply_filters( 'wc_offline_form_fields', array(
		  
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'wc-gateway-offline' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Offline Payment', 'wc-gateway-offline' ),
					'default' => 'yes'
				),
				
				'title' => array(
					'title'       => __( 'Title', 'wc-gateway-offline' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-offline' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				
				'description' => array(
					'title'       => __( 'Description', 'wc-gateway-offline' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-offline' ),
					'default'     => __( 'Please remit payment to Store Name upon pickup or delivery.', 'wc-gateway-offline' ),
					'desc_tip'    => true,
				),
				
				'instructions' => array(
					'title'       => __( 'Instructions', 'wc-gateway-offline' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-offline' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'CHAVE_ZPK' => array(
					'title'       => "Chave ZPK",
					'type'        => 'text',
					'description' => "insira sua chave",
					'default'     => '',
					'desc_tip'    => true,
				),
				'SELLER_ID' => array(
					'title'       => "Seller ID",
					'type'        => 'text',
					'description' => "insira sua chave",
					'default'     => '',
					'desc_tip'    => true,
				),
			) );
		}
	

		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}
	

		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {		
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
	

		public function process_payment( $order_id ) {
	
			$order = wc_get_order( $order_id );
			
			$order->update_status( 'on-hold', __( 'Awaiting offline payment', 'wc-gateway-offline' ) );
			
			$order->reduce_order_stock();
			
			WC()->cart->empty_cart();
			
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
			);
		}
}