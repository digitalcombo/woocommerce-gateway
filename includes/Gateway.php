<?php

class Gateway extends Zoop {
    public function createPlan( $plan ) { return json_decode($this->transactions( $plan, 'plans', true )); }

    public function customer( $buyer ) { return json_decode($this->transactions( $buyer, 'buyers' )); }

    public function tokenCard( $card ) { return json_decode($this->transactions( $card, 'tokens' )); }

    public function transfCard( $card ) { return json_decode($this->transactions( $card, 'transactions' )); }

    public function boleto( $buyer, $info, $idSeller ) {
        $customer = $this->customer($buyer);
        return json_decode($this->boletoOrder( $info, $customer->id, $idSeller ));
    }

    public function card( $card, $customer ) {
        $card     = $this->tokenCard($card);
        $customer = $this->customer($customer);
        $card = [
            "token"    => $card->id,
            "customer" => $customer->id
        ];
        $token = $this->transactions( $card, 'cards' );

        return $token;
    }

    public function subscriptions( $idPlan, $customer, $dueDate ) {
        $customer = $this->customer($customer);
        if(!empty($customer->error)) { return $customer; }
        $subs = [
            "plan"         => $idPlan,
            "on_behalf_of" => '6cf4bb1e78c6428786fc8fe6ddada3a6',
            "customer"     => $customer->id,
            "currency"     => "BRL",
            "due_date"     => $dueDate
        ];

        return json_decode($this->transactions( $subs, 'subscriptions', true ));
    }

    static function webHook()
    {
        $request = file_get_contents('php://input');
        file_put_contents( __DIR__ . "/../log/webhook-" . Date( 'Y-m-d-H-i-' ) . uniqid() . ".json", $request );
        $request = json_decode( $request );
        $response = [
            "type" => $request->type,
            "id"   => $request->payload->object->payment_method->id
        ];
        return $response;        
    }

}