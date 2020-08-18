<?php
/*
Plugin Name: Shipping Coordinadora Woocommerce Rules
Description: Reglas para Shipping Coordinadora Woocommerce
Version: 1.0.0
Author: Saul Morales Pacheco
Author URI: https://saulmoralespa.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; //Exit if accessed directly
}

if(!defined('SHIPPING_COORDINADORA_RULES_VERSION')){
    define('SHIPPING_COORDINADORA_RULES_VERSION', '1.0.0');
}

add_action( 'plugins_loaded', 'shipping_coordinadora_rules_init');

function shipping_coordinadora_rules_init(){
    if(!shipping_coordinadora_rules_requirements()) return;

    add_filter( 'coordinadora_shipping_calculate_cost', 'coordinadora_shipping_calculate_cost_filter', 10, 2 );

    function coordinadora_shipping_calculate_cost_filter($data, $package){
        global $woocommerce;
        $coordinadora = new WC_Shipping_Method_Shipping_Coordinadora_WC();

        $state_destination = $package['destination']['state'];
        $city_destination  = $package['destination']['city'];
        $items = $woocommerce->cart->get_cart();
        $count = 0;
        $total_valorization = 0;
        $height = 0;
        $length = 0;
        $weight = 0;
        $width = 0;
        $quantityItems = count($items);
        $cart_prods = [];

        foreach ( $items as $item => $values ) {
            $_product_id = $values['data']->get_id();
            $_product = wc_get_product( $_product_id );

            if ( $_product->is_type( 'variable' ) )
                $_product = wc_get_product($_product->get_parent_id());

            if ( !$_product->get_weight() || !$_product->get_length()
                || !$_product->get_width() || !$_product->get_height() )
                break;

            $custom_price_product = get_post_meta($_product->get_parent_id(), '_shipping_custom_price_product_smp', true);
            $total_valorization += $custom_price_product ? wc_format_decimal($custom_price_product, 0) : wc_format_decimal($_product->get_price(), 0);

            $quantity = $values['quantity'];

            $total_valorization = $total_valorization * $quantity;

            $height += $_product->get_height() * $quantity;
            $length = $_product->get_length() > $length ? $_product->get_length() : $length;
            $weight =+ $weight + ($_product->get_weight() * $quantity);
            $width =  $_product->get_width() > $width ? $_product->get_width() : $width;

            $count++;

            if ($count === $quantityItems || ceil($weight) === $coordinadora->weight_max){

                $cart_prods[] = [
                    'ubl'      => '0',
                    'alto'     => $height,
                    'ancho'    => $width,
                    'largo'    => $length,
                    'peso'     => ceil($weight),
                    'unidades' => 1
                ];

                $height = 0;
                $length = 0;
                $weight = 0;
                $width = 0;
            }
        }

        $result_destination = Shipping_Coordinadora_WC::code_city($state_destination, $city_destination);

        if ( empty( $result_destination ) ){
            $city_destination = Shipping_Coordinadora_WC::clean_string($city_destination);
            $city_destination = Shipping_Coordinadora_WC::clean_city($city_destination);
            $result_destination = Shipping_Coordinadora_WC::code_city($state_destination, $city_destination);
        }

        $params = array(
            'div'            => $coordinadora->div,
            'cuenta'         => $coordinadora->code_account,
            'producto'       => '0',
            'origen'         => $coordinadora->city_sender,
            'destino'        => $result_destination->codigo,
            'valoracion'     => $total_valorization,
            'nivel_servicio' => array( 0 ),
            'detalle'        => array(
                'item' => $cart_prods
            )
        );

        if ($coordinadora->debug === 'yes')
            shipping_coordinadora_wc_cswc()->log($params);

        $data = Shipping_Coordinadora_WC::cotizar($params);

        if ($coordinadora->debug === 'yes')
            shipping_coordinadora_wc_cswc()->log($data);

        $data->flete_total = 0;

        return $data;
    }

}

function shipping_coordinadora_rules_notices( $notice ) {
    ?>
    <div class="error notice">
        <p><?php echo esc_html( $notice ); ?></p>
    </div>
    <?php
}

function shipping_coordinadora_rules_requirements(){

    if (!class_exists('Shipping_Coordinadora_WC_Plugin')) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_coordinadora_rules_notices( 'Shipping Coordinadora Woocommerce Rules: Requiere que se encuentre instalado y activo: Plugin WooCommerce de Wompi' );
                }
            );
        }
        return false;
    }

    return true;
}