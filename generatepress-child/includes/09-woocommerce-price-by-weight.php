<?php
/**
 * -----------------------------
 * 09 WooCommerce price by weight
 * -----------------------------
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


add_filter('woocommerce_dynamic_pricing_process_product_discounts', function($discount, $product, $cart_item, $processing_rules) {
    if ( ! is_array($discount) || empty($discount['adjustment']) ) {
        return $discount;
    }

    // Get weight
    $weight = (float) $product->get_weight();
    $qty = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;

    // Replace placeholders in adjustment string (if applicable)
    $equation = $discount['adjustment'];

    // Only act if [weight] is found
    if (strpos($equation, '[weight]') !== false) {
        $equation = str_replace('[weight]', $weight, $equation);
        $equation = str_replace('[qty]', $qty, $equation);

        // Allow only safe math
        if (preg_match('/^[0-9+\-.*\/ ()]+$/', $equation)) {
            $discount['adjustment'] = eval('return ' . $equation . ';');
        }
    }

    return $discount;

}, 10, 4);
