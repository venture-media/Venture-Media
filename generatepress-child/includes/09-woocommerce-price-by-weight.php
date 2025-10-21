<?php
/**
 * -----------------------------
 * 09 WooCommerce price by weight
 * -----------------------------
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Add [weight] support to dynamic pricing formulas
add_filter('woocommerce_dynamic_pricing_product_price', function($price, $product, $qty) {
    
    // Get product weight
    $weight = $product->get_weight();
    if ( ! $weight ) $weight = 0;

    // Replace [weight] in the formula if present
    if ( isset($_POST['pricing_equation']) ) {
        $equation = $_POST['pricing_equation'];
        $equation = str_replace('[weight]', $weight, $equation);
        
        // Replace [qty] as well.
        $equation = str_replace('[qty]', $qty, $equation);

        // Evaluate the formula safely
        try {
            // Only allow numbers and operators
            if (preg_match('/^[0-9+\-.*\/ ()]+$/', $equation)) {
                $calculated_price = eval('return ' . $equation . ';');
                if ( is_numeric($calculated_price) ) {
                    $price = $calculated_price;
                }
            }
        } catch (Exception $e) {
            // fallback to normal price
        }
    }

    return $price;

}, 10, 3);
