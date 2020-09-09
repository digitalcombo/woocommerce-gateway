<?php

final class WC_DC_FIG
{
    const VERSION      = "0.1";
    const BASE         = __DIR__ . "/..";
    const NAME         = "WooDigintalCombo";
    const ID           = "woo_digital_combo";
    const ID_MKT_PLACE = "83824523b30a4f44a6231c46319c8c12";
    const ICO          = BASE_DCP . "/static/images/logo/logo.svg";
    const METHOD_TITLE = "Woo Digital Combo";
    const HAS_FIELDS   = true;
    const HAS_DESCRIPT = "A forma mais fácil de vender através de boleto, cartão de crédito e débito recorrente via Woocommerce.";
    const TEXT_BUTTON  = "Pagar com Digital Combo";

    static function init()
    {
        if( self::verifica_woocomerce_esta_ativo() ) :
            self::auto_load();
            self::campos_adicionais();
            self::mostrar_link_boleto_apos_finalizar();
            add_action( 'woocommerce_api_digitalcombo', [ __CLASS__, 'callback_handler' ] );
            add_filter( 'woocommerce_payment_gateways', [ __CLASS__ , "adicionar_como_meio_de_pagamento" ] );
            // add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ __CLASS__, "adiciona_link_de_configuracao" ] );
        endif;
    }

    static function adicionar_como_meio_de_pagamento( $gateways )
    {
        $gateways[] = self::NAME;
        return $gateways;
    }   

    static function verifica_woocomerce_esta_ativo()
    {
        return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

    static function adiciona_link_de_configuracao( $links )
    {
        $url     = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . self::ID );
        $texto   = self::METHOD_TITLE;
        $links[] = "<a href=\"$url\">$texto</a>";
        return $links;
    }

    static function auto_load()
    {
        $includes = [
            "DigitalFig",
            "WooDigintalCombo",
            "WDC_Validacao",
            "Zoppintegracao",
            "Curl",
            "Zoop",
            "Gateway",
        ];
        foreach( $includes as $nomeClass ):
            if( ! class_exists( $nomeClass ) ) :
                include_once __DIR__ . "/$nomeClass.php";
            endif;
        endforeach;
    }
    static function callback_handler()
	{
        header( 'HTTP/1.1 200 OK' );
        global $wpdb;
        $table_perfixed = $wpdb->prefix . 'comments';
        file_put_contents( __DIR__ . "/../log/webhook-" . uniqid() . ".json", json_encode( $_REQUEST ) );
        if( isset( $_REQUEST['id'] ) && isset( $_REQUEST['type'] ) )
        {
            $token   = $_REQUEST['id'];
            $results = $wpdb->get_results("
                SELECT *
                FROM $table_perfixed
                WHERE  comment_content = 'TOKEN PEDIDO: $token'
            ");
            $pedido_id = $results[0]->comment_post_ID ?? 0;
            if( $pedido_id  )
            {
                $order = new WC_Order( $pedido_id );
            }
            switch ( $_REQUEST['type'] ) 
            {
                case 'subscription.active':
                case 'transaction.succeeded':
                    $order->update_status('completed', "Pagamento confirmado");
                    break;
                case 'subscription.deleted':
                case 'subscription.expired':
                case 'subscription.suspended':
                case 'transaction.canceled':
                case 'transaction.failed':
                case 'transaction.reversed':
                    $order->update_status('failed', "Pedido cancelado ou sumpenso");
                    break;
            }
        }
        die;
    }
    
    static function campos_adicionais()
    {
        add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

        function custom_override_checkout_fields( $fields ) {
            $fields['billing']['billing_bairro'] = [
                'type'        => 'text',
                'label'       => "bairro",
                'placeholder' => "Digite aqui seu bairro",
                'required'    => true,
                'priority'    => 61
            ];
            $fields['billing']['billing_cpf'] = [
                'type' => 'text',
                'label' => "CPF",
                'placeholder' => "000.000.000-00",
                'required' => true,
                'priority'    => 30
            ];
            return $fields;
        }
    }

    static function mostrar_link_boleto_apos_finalizar()
    {
        add_filter('woocommerce_thankyou_order_received_text', 'woo_change_order_received_text', 10, 2 );
        function woo_change_order_received_text( $str, $order ) {
            global $wpdb;
            $table_perfixed = $wpdb->prefix . 'comments';
            $id             = $order->get_id();
            $results = $wpdb->get_results("
                SELECT *
                FROM $table_perfixed
                WHERE comment_post_ID = '$id'
            
            ");
            $codigo_de_barra = array_filter( $results, function( $comment ) {		 
                return stripos( $comment->comment_content, "CODIGO DE BARRAS:" ) !== false;
            } );
            $codigo_de_barra = array_values(  $codigo_de_barra );
            if( count($codigo_de_barra) > 0 ) {
                $codigo_de_barra = $codigo_de_barra[0]->comment_content;
                $codigo_de_barra = str_replace( "CODIGO DE BARRAS:", '', $codigo_de_barra );
                $str .= "
                    <li>Seu código de barras é:  <b>$codigo_de_barra</b></li>
                ";		
            }
            $link_boleto = array_filter( $results, function( $comment ) {		 
                return stripos( $comment->comment_content, "URL BOLETO:" ) !== false;		
            } );
            $link_boleto = array_values(  $link_boleto );
            if( count($link_boleto) > 0 ) {
                $link_boleto = $link_boleto[0]->comment_content;
                $link_boleto = str_replace( "URL BOLETO:", '', $link_boleto );
                $str .= "
                    <li> 
                        Para imprimir seu boleto
                        <a href=\"$link_boleto\" target=\"_blank\">
                            Clique aqui
                        </a>
                    </li>
                ";		
            }
            return $str;
        }
    }

}
