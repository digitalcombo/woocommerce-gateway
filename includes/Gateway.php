<?php

class Gateway extends Zoop {
    public function createPlan( $plan ) { return json_decode($this->transactions( $plan, 'plans', true )); }

    public function customer( $buyer ) { return json_decode($this->transactions( $buyer, 'buyers' )); }

    public function tokenCard( $card ) { return json_decode($this->transactions( $card, 'tokens' )); }

    public function transCard( $card ) { return json_decode($this->transactions( $card, 'transactions' )); }

    public function boleto( $buyer, $info ) {
        $customer = $this->customer($buyer);
        return json_decode($this->boletoOrder( $info, $customer->id ));
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

}