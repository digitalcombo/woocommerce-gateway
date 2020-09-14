<?php

class Zoop extends Curl {
    private $idMarketplace;
    private $idSeller;
    private $keyZpk;
    private $Api;

    function __construct() {
        $this->idMarketplace = WC_DC_FIG::ID_MKT_PLACE;
        $this->idSeller      = '6cf4bb1e78c6428786fc8fe6ddada3a6';
        $this->keyZpk        = WC_DC_FIG::ZPK;
        $this->api           = 'https://api.zoop.ws/';
    }

    public function transactions( $arr, $url, $version = false ) {
        $version = $version  ?? '' ? 'v2' : 'v1';
        $fullUrl = "{$this->api}{$version}/marketplaces/{$this->idMarketplace}/{$url}";
        if( $url == 'subscriptions' ) { $this->post( $fullUrl, $arr, [], $this->keyZpk, true ); }

        return $this->post( $fullUrl, $arr, [], $this->keyZpk );
    }

    public function boletoOrder( $info, $customer, $idSeller ) {
        $info['on_behalf_of'] = $idSeller;
        $info['customer']     = $customer;
        $info['payment_type'] = "boleto";
        $info['logo']         = "https://homologacao.digitalcombo.com.br/wp-content/plugins/woocommerce-gateway/static/images/logo/logo.png";
        return $this->transactions( $info, 'transactions' );
    }
}