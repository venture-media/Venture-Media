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

function venture_shipping_weight_shortcode( $atts = array(), $content = null ) {
	$weight = 0;

	// Try package data if a global package is set (rare), then fall back to cart weight.
	if ( ! empty( $GLOBALS['wc_shipping_package'] ) && is_array( $GLOBALS['wc_shipping_package'] ) ) {
		$package = $GLOBALS['wc_shipping_package'];
		if ( isset( $package['contents'] ) ) {
			$w = 0.0;
			foreach ( $package['contents'] as $item ) {
				if ( isset( $item['data'] ) && is_object( $item['data'] ) ) {
					$qty = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
					$w  += (float) $item['data']->get_weight() * $qty;
				}
			}
			$weight = $w;
		}
	} elseif ( function_exists( 'WC' ) && isset( WC()->cart ) && is_object( WC()->cart ) ) {
		$weight = (float) WC()->cart->get_cart_contents_weight();
	}

	// Return numeric string (WC_Eval_Math expects numbers).
	return (string) floatval( $weight );
}

// Add weight to evaluate args so 3rd parties can use it if needed.
add_filter( 'woocommerce_evaluate_shipping_cost_args', 'venture_add_weight_to_eval_args', 10, 3 );
function venture_add_weight_to_eval_args( $args, $sum, $instance ) {
	if ( isset( $args['weight'] ) ) {
		return $args;
	}
	$args['weight'] = 0;
	if ( function_exists( 'WC' ) && isset( WC()->cart ) && is_object( WC()->cart ) ) {
		$args['weight'] = (float) WC()->cart->get_cart_contents_weight();
	}
	return $args;
}
