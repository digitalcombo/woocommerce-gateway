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
            'vencimento_boleto' => [
                'title'       => 'Vencimento Boleto',
                'type'        => 'number',
                'description' => 'Coloque Aqui a quantidade de de dias adicionar a vencimento do boleto',
                'default'     => 0,
                'desc_tip'    => true,
            ],
            'SELLER_ID' => [
                'title'       => "ID de Vendedor",
                'type'        => 'text',
                'description' => "insira aqui sei ID de vendedor",
                'default'     => '',
                'desc_tip'    => true,
            ],
            'pagar_como' => [
                'title'   => 'Pagar via',
                'type'    => 'select',
                'label'   => "Escolha seu meio de pagamento",
                'default' => 'cartao_credito_e_boleto',
                'options' => [
                    "cartao_credito_e_boleto" => "Cartão Credito e Boleto",
                    "cartao_de_credito" => "Somente via Cartão Credito",
                    "boleto" => "Somente via Boleto",
                ]
            ],
            'mode_dev' => [
                'title'   => 'Ativar/Desativar',
                'label'   => 'Modo de Teste',
                'type'    => 'checkbox',
                'description'  => "Ao marcar a opção o modo de teste sera ativado",
                'default' => 'yes',
                'desc_tip' => true
            ],
           
        ];
    }
}