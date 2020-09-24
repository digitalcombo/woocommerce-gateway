<?php

class WC_Product_recorrente extends WC_Product_Simple 
{
    public function get_type()
    {
        return 'recorrente';
    }


    public function get_price_suffix( $price = '', $quant = 1 ) {
        $periodo_recorrencia = $this->get_meta( '_recorrente', true );
        return " / " . $this->tipos_recorrencia( $periodo_recorrencia );
    }

    public function tipos_recorrencia( $quant_mes )
    {
        $tipos = [
            "1"  => "Mensal",
            "3"  => "Trimestral",
            "6"  => "Semestral",
            "12" => "Anual",
        ];
        return $tipos[ $quant_mes ];
    }

    public function add_to_cart_text()
    {
        return 'Quero Ajudar';
    }

    
}