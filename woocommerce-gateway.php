<?php

/**
 * Plugin Name: Woo Digital Combo
 * Plugin URI: https://digitalcombo.com.br/solucoes-de-pagamento
 * Description: A forma mais fácil de vender através de boleto, cartão de crédito e débito recorrente via Woocommerce.
 * Version: 0.1
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Digital Combo
 * Author URI: https://digitalcombo.com.br
 * Text Domain: woocommerce-gateway
 * Domain Path: /languages
 * License:
 * License URI
 * 
 * {Plugin Name} is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *  
 * {Plugin Name} is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *  
 * You should have received a copy of the GNU General Public License
 * along with {Plugin Name}. If not, see {URI to Plugin License}.
 * 
 */
 
defined( 'ABSPATH' ) || exit;

if( ! defined( 'BASE_DCP' ) ) :
	define( 'BASE_DCP', trailingslashit( WP_PLUGIN_URL ) . plugin_basename( dirname( __FILE__ ) ) );
endif;


if( ! class_exists( 'WC_DC_FIG' ) ) :
	include_once __DIR__ . "/includes/WC_DC_FIG.php";
	add_action( 'plugins_loaded', [ "WC_DC_FIG", "init" ] );
endif;

// comprador_id_zoop
// comprador_id_card_zoop


// add_action( 'plugins_loaded', function() {
add_action( 'woocommerce_after_cart_contents', function() {
	// update_post_meta( 301, 'comprador_id_zoop', '302' );
	// $id = get_post_meta( 302, 'comprador_id_zoop', true );
	// var_dump( $id );
	// $app = new WooDigintalCombo;
	// $app->products_recorrente( 83 );

} );

function add_list_order_btn_duplicate( $columns ) 
{
    $columns['wc_order_duplicate'] = 'Duplicar';
	return $columns;
}
add_filter( 'manage_edit-shop_order_columns', 'add_list_order_btn_duplicate' );


function wc_btn_order_duplicate( $column ) 
{
    global $post;
    if ( 'wc_order_duplicate' === $column ) :
		echo "
			<a href=\"javascript:void(0)\" onclick=\"globalThis.duplicar('$post->ID', this)\" class=\"button wc-action-button wc-action-button-processing processing\"> 
				Duplicar 
				#<b>$post->ID</b>
			</a>
		";
	endif;
}
add_action( 'manage_shop_order_posts_custom_column', 'wc_btn_order_duplicate' );


add_action( 'rest_api_init', function () {
	register_rest_route( 'dc-api/v1', '/order/(?P<id>\d+)', array(
	  'methods' => 'GET',
	  'callback' => 'duplicar_order',
	) );
} );

function duplicar_order( $param )
{
	$original_order = new WC_Order( $param->get_param('id') );
    $user_id        = $original_order->get_user_id();
	$order = wc_create_order( [
		'status'        => 'on-hold',
		'customer_id'   => $user_id,
		'customer_note' => '',
		'total'         => $original_order->get_total(),
	] );
	

	$address_1 = get_user_meta( $user_id, 'billing_address_1', true );
	$address_2 = get_user_meta( $user_id, 'billing_address_2', true );
	$city      = get_user_meta( $user_id, 'billing_city', true );
	$postcode  = get_user_meta( $user_id, 'billing_postcode', true );
	$country   = get_user_meta( $user_id, 'billing_country', true );
	$state     = get_user_meta( $user_id, 'billing_state', true );
	$address         = array(
		'first_name' => $original_order->get_billing_first_name(),
		'last_name'  => $original_order->get_billing_last_name(),
		'email'      => $original_order->get_billing_email(),
		'address_1'  => $address_1,
		'address_2'  => $address_2,
		'city'       => $city,
		'state'      => $state,
		'postcode'   => $postcode,
		'country'    => $country,
	);

	$order->set_address( $address, 'billing' );
	$order->set_address( $address, 'shipping' );

	foreach( $original_order->get_items() as $product ) :
		$id_prod = $product['product_id'];
		$is_prod = wc_get_product( $id_prod );
		$order->add_product( $is_prod, 1);
	endforeach;
	header('Content-Type: text/html; charset=utf-8');
	$order->calculate_totals();
	return [
		"status" => $param->get_param('id'),
		"user"   => $address
	];
}

function wc_script_order_duplique_js() {
    wp_enqueue_script( 'my_custom_script', plugin_dir_url( __FILE__ ) . 'static/js/wc-order-duplicate.js', [], '1.0' );
}
add_action( 'admin_enqueue_scripts', 'wc_script_order_duplique_js' );