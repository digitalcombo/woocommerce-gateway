<?php

class Gateway extends Zoop {
    public function createPlan( $plan ) { return json_decode($this->transactions( $plan, 'plans', true )); }

    public function customer( $buyer ) { return json_decode($this->transactions( $buyer, 'buyers', false, true )); }

    public function tokenCard( $card ) { return json_decode($this->transactions( $card, 'cards/tokens', false, true )); }

    private function createUserToken( $card, $customer ) {
        $customer  = $this->customer($customer);
        $card      = $this->tokenCard($card);    
        $result    = $this->card($card->id, $customer->id);

        return $result->customer;
    }


    public function transCard( $infoBuyer, $splitRules = [] ) {
        $transf = [
            "amount"       => $infoBuyer['amount'],
            "currency"     => "BRL",
            "on_behalf_of" => $infoBuyer['on_behalf_of'],
            "customer"     => empty($infoBuyer['customerID']) ? $this->createUserToken($infoBuyer['card'], $infoBuyer['customer']) : $infoBuyer['customerID'],
            "payment_type" => $infoBuyer['payment_type'],
        ];
        $transf = !empty($splitRules) ? array_merge($transf, $splitRules) : $transf;

        return json_decode($this->transactions( $transf, 'transactions', false, true ));
    }

    public function boleto( $buyer, $info ) {
        if( empty($info['customerID']) ) {
            $customer = $this->customer($buyer);
        }
        return json_decode( $this->boletoOrder( $info, empty( $info['customerID']) ? $customer->id : $info['customerID'] ) );
    }

    public function card( $cardID, $customerID ) {
        $card = [
            "token"    => $cardID,
            "customer" => $customerID
        ];

        return json_decode($this->transactions( $card, 'cards',false , true ));
    }

    public function subscriptions( $infoPlan ) {
        $userToken   = empty( $infoPlan['customerID'] ) ? $this->createUserToken( $infoPlan['card'], $infoPlan['customer'] ) : $infoPlan['customerID'];
        $createBuyer = $this->customer($infoPlan['customer']);

        $subs = [
            "plan"         => $infoPlan['idPlan'],
            "on_behalf_of" => $infoPlan['idVendedor'],
            "customer"     => $userToken,
            "currency"     => "BRL",
            "due_date"     => $infoPlan['dueDate']
        ];
        file_put_contents( __DIR__ . "/../log/sub-" . Date( 'Y-m-d-H-i-' ) . uniqid() . ".json", json_encode($subs) );

        return json_decode($this->transactions( $subs, 'subscriptions', true, true ));
    }

    static function webHook() {
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