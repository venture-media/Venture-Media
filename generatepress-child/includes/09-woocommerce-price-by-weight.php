<?php
/**
 * WooCommerce Shipping by Weight - Robust fix for preg_match null deprecation
 * Works with recent WooCommerce versions + PHP 8.3+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'venture_register_weight_shortcode', 5 );
function venture_register_weight_shortcode() {
    if ( ! shortcode_exists( 'weight' ) ) {
        add_shortcode( 'weight', 'venture_shipping_weight_shortcode' );
    }
}

/**
 * [weight] shortcode – always returns a clean numeric string
 */
function venture_shipping_weight_shortcode() {
    $weight = 0.0;

    // 1. Package context (advanced shipping / multiple packages)
    if ( ! empty( $GLOBALS['wc_shipping_package'] ) && is_array( $GLOBALS['wc_shipping_package'] ) ) {
        $package = $GLOBALS['wc_shipping_package'];
        if ( ! empty( $package['contents'] ) && is_array( $package['contents'] ) ) {
            foreach ( $package['contents'] as $item ) {
                if ( ! empty( $item['data'] ) && is_object( $item['data'] ) && method_exists( $item['data'], 'get_weight' ) ) {
                    $qty         = ! empty( $item['quantity'] ) ? max( 1, (int) $item['quantity'] ) : 1;
                    $item_weight = $item['data']->get_weight();
                    if ( is_numeric( $item_weight ) ) {
                        $weight += (float) $item_weight * $qty;
                    }
                }
            }
        }
    }

    // 2. Cart context (normal checkout / single package)
    if ( $weight <= 0 && function_exists( 'WC' ) && WC()->cart && is_object( WC()->cart ) ) {
        $cart_weight = WC()->cart->get_cart_contents_weight();
        if ( is_numeric( $cart_weight ) ) {
            $weight = (float) $cart_weight;
        }
    }

    return (string) max( 0, $weight );
}

/**
 * Guarantee 'weight' is always a valid float for WC_Eval_Math
 */
add_filter( 'woocommerce_evaluate_shipping_cost_args', 'venture_add_weight_to_eval_args', 5, 3 );
function venture_add_weight_to_eval_args( $args, $sum, $instance ) {
    if ( ! is_array( $args ) ) {
        $args = [];
    }
    $args['weight'] = isset( $args['weight'] ) && is_numeric( $args['weight'] )
        ? (float) $args['weight']
        : 0.0;

    return $args;
}

/**
 * Extra safety net – prevent any null/empty cost from reaching the parser
 */
add_filter( 'woocommerce_shipping_method_add_rate', 'venture_sanitize_rate_cost', 999, 2 );
function venture_sanitize_rate_cost( $rate, $package ) {
    if ( isset( $rate['cost'] ) && ( $rate['cost'] === null || $rate['cost'] === '' ) ) {
        $rate['cost'] = 0;
    }
    return $rate;
}
