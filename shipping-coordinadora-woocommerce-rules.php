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

    add_filter( 'coordinadora_shipping_settings', 'coordinadora_shipping_settings_filter', 10, 1 );
    add_filter( 'coordinadora_shipping_calculate_cost', 'coordinadora_shipping_calculate_cost_filter', 10, 2 );

    function coordinadora_shipping_settings_filter(array $settings){

        $settings['percentage_increase'] = [
            'title'       => __( 'Porcentaje de incremento en el envío'),
            'type'        => 'number',
            'description' => __( 'El porcentaje de incremento para el costo del envío' ),
            'default'     => '0',
            'desc_tip'    => true
        ];
        return $settings;
    }

    function coordinadora_shipping_calculate_cost_filter($data, $package){
        $settings = get_option('woocommerce_shipping_coordinadora_wc_settings');
        $percentage = $settings['percentage_increase'];
        $data->flete_total += $data->flete_total * ($percentage/100);

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

    if ( !in_array(
        'woocommerce/woocommerce.php',
        apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
        true
    ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_coordinadora_rules_notices( 'Shipping Coordinadora Woocommerce Rules: Requiere que se encuentre instalado y activo el plugin: Woocommerce' );
                }
            );
        }
        return false;
    }

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
