<?php

class Zoop extends Curl {
    private $idMarketplace;
    private $idSeller;
    private $keyZpk;
    private $Api;

    function __construct() {
        $this->idMarketplace = '83824523b30a4f44a6231c46319c8c12';
        $this->idSeller      = '6cf4bb1e78c6428786fc8fe6ddada3a6';
        $this->keyZpk        = 'zpk_test_lcyUVmcv7ISdesnZe4m3w5eN';
        $this->api           = 'https://api.zoop.ws/';
    }

    public function transactions( $arr, $url, $version = false ) {
        $version = $version  ?? '' ? 'v2' : 'v1';
        $fullUrl = "{$this->api}{$version}/marketplaces/{$this->idMarketplace}/{$url}";
        if( $url == 'subscriptions' ) { $this->post( $fullUrl, $arr, [], $this->keyZpk, true ); }

        return $this->post( $fullUrl, $arr, [], $this->keyZpk );
    }

    public function boletoOrder( $info, $customer ) {
        $info['on_behalf_of'] = $this->idSeller;
        $info['customer']     = $customer;
        $info['payment_type'] = "boleto";
        return $this->transactions( $info, 'transactions' );
    }
}