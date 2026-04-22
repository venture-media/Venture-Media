<?php
/**
 * -----------------------------
 * 09 WooCommerce shipping price by weight
 * -----------------------------
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'venture_register_weight_shortcode' );
function venture_register_weight_shortcode() {
    if ( ! shortcode_exists( 'weight' ) ) {
        add_shortcode( 'weight', 'venture_shipping_weight_shortcode' );
    }
}

/**
 * Shortcode [weight] for use in shipping method cost fields
 * (e.g. "10 * [weight]" in Flat Rate / Table Rate shipping)
 */
function venture_shipping_weight_shortcode( $atts = array(), $content = null ) {
    $weight = 0.0;

    // Priority 1: Package context (multi-package shipping)
    if ( ! empty( $GLOBALS['wc_shipping_package'] ) && is_array( $GLOBALS['wc_shipping_package'] ) ) {
        $package = $GLOBALS['wc_shipping_package'];
        if ( isset( $package['contents'] ) && is_array( $package['contents'] ) ) {
            foreach ( $package['contents'] as $item ) {
                if ( isset( $item['data'] ) && is_object( $item['data'] ) && method_exists( $item['data'], 'get_weight' ) ) {
                    $qty = isset( $item['quantity'] ) ? max( 1, (int) $item['quantity'] ) : 1;
                    $item_weight = (float) $item['data']->get_weight();
                    $weight += $item_weight * $qty;
                }
            }
        }
    } 
    // Priority 2: Cart context (normal checkout)
    elseif ( function_exists( 'WC' ) && isset( WC()->cart ) && is_object( WC()->cart ) && method_exists( WC()->cart, 'get_cart_contents_weight' ) ) {
        $cart_weight = WC()->cart->get_cart_contents_weight();
        if ( is_numeric( $cart_weight ) ) {
            $weight = (float) $cart_weight;
        }
    }

    // Always return a string – required for WC_Eval_Math
    return (string) $weight;
}

/**
 * Pass 'weight' variable to WC_Eval_Math when evaluating shipping costs
 * Extra safety guards prevent null values that trigger the preg_match deprecation.
 */
add_filter( 'woocommerce_evaluate_shipping_cost_args', 'venture_add_weight_to_eval_args', 10, 3 );
function venture_add_weight_to_eval_args( $args, $sum, $instance ) {
    if ( ! is_array( $args ) ) {
        $args = [];
    }

    // Prevent null/undefined values reaching WC_Eval_Math
    if ( ! isset( $args['weight'] ) || ! is_numeric( $args['weight'] ) ) {
        $args['weight'] = 0.0;

        if ( function_exists( 'WC' ) && isset( WC()->cart ) && is_object( WC()->cart ) && method_exists( WC()->cart, 'get_cart_contents_weight' ) ) {
            $cart_weight = WC()->cart->get_cart_contents_weight();
            if ( is_numeric( $cart_weight ) ) {
                $args['weight'] = (float) $cart_weight;
            }
        }
    }

    return $args;
}
