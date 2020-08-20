<?php

final class WC_DC_FIG
{
    const VERSION      = "0.1";
    const BASE         = __DIR__ . "/..";
    const NAME         = "WooDigintalCombo";
    const ID           = "woo_digital_combo";
    const ID_MKT_PLACE = "83824523b30a4f44a6231c46319c8c12";
    const ICO          = self::BASE . "/static/images/icone-white.svg";
    const METHOD_TITLE = "Woo Digital Combo";
    const HAS_FIELDS   = true;
    const HAS_DESCRIPT = "A forma mais fácil de vender através de boleto, cartão de crédito e débito recorrente via Woocommerce.";

    static function init()
    {
        if( self::verifica_woocomerce_esta_ativo() ) :
            self::auto_load();
            
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
            "WooDigintalCombo",
            "WDC_Validacao",
            "Zoppintegracao"
        ];
        foreach( $includes as $nomeClass ):
            if( ! class_exists( $nomeClass ) ) :
                include_once __DIR__ . "/$nomeClass.php";
            endif;
        endforeach;
    }

}
