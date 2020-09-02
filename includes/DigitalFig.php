<?php

final class DigitalFig
{
    static function fields()
    {
        return [
            'enabled' => [
                'title'   => 'Ativar/Desativar',
                'type'    => 'checkbox',
                'label'   => "Pagar com " . WC_DC_FIG::METHOD_TITLE,
                'default' => 'yes'
            ],
            'title' => [
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'Este é que aparece na hora de efetuar um pagamento',
                'default'     => '',
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => 'Descrição',
                'type'        => 'textarea',
                'description' => 'Esta é a descrição que aparece na hora de efetuar um pagamento',
                'default'     => '',
                'desc_tip'    => true,
            ],
            'SELLER_ID' => [
                'title'       => "ID de Vendedor",
                'type'        => 'text',
                'description' => "insira aqui sei ID de vendedor",
                'default'     => '',
                'desc_tip'    => true,
            ],
            'boleto' => [
                'title'   => 'Ativar/Desativar',
                'type'    => 'checkbox',
                'label'   => "Pagar via Boleto",
                'default' => 'yes'
            ],
            'card' => [
                'title'   => 'Ativar/Desativar',
                'type'    => 'checkbox',
                'label'   => "Pagar via Cartão de Credito",
                'default' => 'yes'
            ],
           
        ];
    }
}