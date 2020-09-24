<?php

class Zoop extends Curl {
    private $idMarketplace;
    private $keyZpk;
    private $api;

    function __construct() {
        $this->idMarketplace = WC_DC_FIG::ID_MKT_PLACE;
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
        $info['logo']         = "https://i.imgur.com/lzVI0zH.png";
        return $this->transactions( $info, 'transactions' );
    }
}