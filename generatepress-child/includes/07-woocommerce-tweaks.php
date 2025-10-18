<?php
/**
 * -----------------------------
 * 07 WooCommerce Tweaks
 * -----------------------------
 */




add_action( 'woocommerce_before_calculate_totals', 'apply_bulk_discount_price_attribute', 20, 1 );

/**
 * Apply per-product bulk price (from attribute) when the cart quantity
 * meets the product's minimum-bulk-discount-qty. Otherwise use sale price
 * if present, falling back to regular price.
 */
function apply_bulk_discount_price_attribute( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    // Helper: read an attribute value (works for global 'pa_' attrs and custom attrs)
    $get_attr_value = function( $product, $attr_slug ) {
        $attributes = $product->get_attributes();
        if ( ! isset( $attributes[ $attr_slug ] ) ) {
            return null;
        }

        $attr = $attributes[ $attr_slug ];

        // WC_Product_Attribute object (global attribute)
        if ( is_object( $attr ) && method_exists( $attr, 'get_options' ) ) {
            $options = $attr->get_options();
            if ( ! empty( $options ) ) {
                $first = reset( $options );
                if ( is_numeric( $first ) ) {
                    // store as term ID – fetch term name
                    $term = get_term( intval( $first ), $attr_slug );
                    if ( $term && ! is_wp_error( $term ) ) {
                        return $term->name;
                    }
                } else {
                    // non-global attribute – option may be slug or text
                    return $first;
                }
            }
        }

        // Fallback for arrays/strings
        if ( is_array( $attr ) ) {
            return reset( $attr );
        }
        return $attr;
    };

    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        $product = $cart_item['data'];
        $qty     = isset( $cart_item['quantity'] ) ? intval( $cart_item['quantity'] ) : 0;

        // --- Determine default price: sale price if present, otherwise regular ---
        $regular = floatval( $product->get_regular_price() );
        $sale_raw = $product->get_sale_price();
        $has_sale = ( $sale_raw !== '' && $sale_raw !== false && $sale_raw !== null );
        $sale     = $has_sale ? floatval( $sale_raw ) : null;
        $default_price = ( $sale !== null ) ? $sale : $regular;

        // --- Read Bulk Price attribute (try both pa_ and non-pa_ slugs) ---
        $bulk_price_raw = $get_attr_value( $product, 'pa_bulk-discount-price' );
        if ( $bulk_price_raw === null ) {
            $bulk_price_raw = $get_attr_value( $product, 'bulk-discount-price' );
        }

        $bulk_price = null;
        if ( $bulk_price_raw !== null && $bulk_price_raw !== '' ) {
            // strip anything non-numeric except dot and minus, then cast
            $bulk_price = floatval( preg_replace( '/[^\d\.\-]/', '', (string) $bulk_price_raw ) );
            if ( $bulk_price <= 0 ) {
                $bulk_price = null;
            }
        }

        // --- Read Minimum Qty attribute (try both pa_ and non-pa_ slugs) ---
        $min_qty_raw = $get_attr_value( $product, 'pa_minimum-bulk-discount-qty' );
        if ( $min_qty_raw === null ) {
            $min_qty_raw = $get_attr_value( $product, 'minimum-bulk-discount-qty' );
        }

        $min_qty = 20; // fallback default
        if ( $min_qty_raw !== null && $min_qty_raw !== '' ) {
            $min_val = intval( preg_replace( '/[^\d]/', '', (string) $min_qty_raw ) );
            if ( $min_val > 0 ) {
                $min_qty = $min_val;
            }
        }

        // --- Decide final price ---
        // If bulk price exists and qty meets/exceeds min – use bulk price.
        // Otherwise use the sale price (if set) or regular price.
        if ( $bulk_price !== null && $qty >= $min_qty ) {
            $final_price = $bulk_price;
        } else {
            $final_price = $default_price;
        }

        // Set price on the cart item product object (prevents shared-product caching issues)
        $cart_item['data']->set_price( $final_price );
    }
}

add_action( 'woocommerce_single_product_summary', 'show_bulk_discount_info', 11 );
function show_bulk_discount_info() {
    global $product;

    // Get product attributes
    $attributes = $product->get_attributes();

    // Get Bulk Price
    $bulk_price = null;
    if ( isset( $attributes['pa_bulk-discount-price'] ) ) {
        $attr_obj = $attributes['pa_bulk-discount-price'];
        if ( is_object( $attr_obj ) && method_exists( $attr_obj, 'get_options' ) ) {
            $options = $attr_obj->get_options();
            if ( ! empty( $options ) ) {
                $term_id = reset( $options );
                $term    = get_term( $term_id, 'pa_bulk-discount-price' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $bulk_price = floatval( $term->name );
                }
            }
        }
    }

    // Get Minimum Bulk Discount Qty
    $min_qty = null;
    if ( isset( $attributes['pa_minimum-bulk-discount-qty'] ) ) {
        $attr_obj = $attributes['pa_minimum-bulk-discount-qty'];
        if ( is_object( $attr_obj ) && method_exists( $attr_obj, 'get_options' ) ) {
            $options = $attr_obj->get_options();
            if ( ! empty( $options ) ) {
                $term_id = reset( $options );
                $term    = get_term( $term_id, 'pa_minimum-bulk-discount-qty' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $min_qty = intval( $term->name );
                }
            }
        }
    }

    // Display Bulk Price Info
    if ( $bulk_price !== null && $min_qty !== null ) {
        echo '<p class="bulk-discount-info" style="margin-top:0px; font-weight:400;">';
        echo "Bulk Price: " . wc_price($bulk_price) . " for " . $min_qty . "+ units";
        echo '</p>';
    }
}

add_action( 'woocommerce_single_product_summary', 'show_book_author_attribute', 6 );
function show_book_author_attribute() {
    global $product;

    // Get product attributes
    $attributes = $product->get_attributes();

    $authors = [];

    if ( isset( $attributes['pa_book-author'] ) ) {
        $attr_obj = $attributes['pa_book-author'];

        if ( is_object( $attr_obj ) && method_exists( $attr_obj, 'get_options' ) ) {
            $options = $attr_obj->get_options();
            if ( ! empty( $options ) ) {
                foreach ( $options as $term_id ) {
                    $term = get_term( $term_id, 'pa_book-author' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $authors[] = $term->name;
                    }
                }
            }
        }
    }

    if ( ! empty( $authors ) ) {
        // Format authors: "A", "A & B", "A, B & C"
        if ( count( $authors ) === 1 ) {
            $formatted = $authors[0];
        } else {
            $last = array_pop( $authors );
            $formatted = implode( ', ', $authors ) . ' & ' . $last;
        }

        echo '<p class="product-authors" style="font-style:italic; margin:5px 0 10px;">by ' . esc_html( $formatted ) . '</p>';
    }
}

add_filter( 'woocommerce_sale_flash', 'custom_woocommerce_sale_flash', 10, 3 );
function custom_woocommerce_sale_flash( $html, $post, $product ) {
    return '<span class="onsale">Pre-order</span>';
}


add_action( 'wp', 'venture_remove_woo_upsells_related', 20 );
function venture_remove_woo_upsells_related() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
}


// Add the admin field
add_filter( 'woocommerce_get_sections_shipping', 'venture_add_handling_fee_section' );
function venture_add_handling_fee_section( $sections ) {
    $sections['venture_handling_fee'] = __( 'Handling Fee', 'woocommerce' );
    return $sections;
}

add_filter( 'woocommerce_get_settings_shipping', 'venture_add_handling_fee_setting', 10, 2 );
function venture_add_handling_fee_setting( $settings, $current_section ) {
    if ( 'venture_handling_fee' === $current_section ) {
        $settings = [
            [
                'title' => __( 'Handling Fee Settings', 'woocommerce' ),
                'type'  => 'title',
                'desc'  => __( 'Set a flat handling fee per quantity for all shipped items. Local Pickup is excluded.', 'woocommerce' ),
                'id'    => 'venture_handling_fee_options',
            ],
            [
                'title'    => __( 'Handling Fee per Item', 'woocommerce' ),
                'desc'     => __( 'Amount (per quantity of shipped items)', 'woocommerce' ),
                'id'       => 'venture_handling_fee_per_item',
                'type'     => 'number',
                'css'      => 'width:100px;',
                'default'  => '10',
                'desc_tip' => true,
            ],
            [
                'type' => 'sectionend',
                'id'   => 'venture_handling_fee_options',
            ],
        ];
    }
    return $settings;
}


add_action( 'woocommerce_cart_calculate_fees', 'venture_add_handling_fee_per_item', 20, 1 );
function venture_add_handling_fee_per_item( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    // Only apply if shipping is needed
    if ( ! $cart->needs_shipping() ) {
        return;
    }

    // Get chosen shipping methods
    $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
    if ( empty( $chosen_methods ) ) {
        return;
    }

    // Skip fee for Local Pickup
    foreach ( $chosen_methods as $method_id ) {
        if ( strpos( $method_id, 'local_pickup' ) !== false ) {
            return;
        }
    }

    // Get fee from settings (default 10 if empty)
    $fee_per_item = floatval( get_option( 'venture_handling_fee_per_item', 10 ) );

    if ( $fee_per_item <= 0 ) {
        return;
    }

    $qty_total = $cart->get_cart_contents_count();
    $handling_total = $fee_per_item * $qty_total;

    $cart->add_fee( __( 'Handling Fee', 'woocommerce' ), $handling_total, true );
}


add_action( 'wp_footer', 'venture_refresh_fees_on_shipping_change' );
function venture_refresh_fees_on_shipping_change() {
    if ( is_cart() || is_checkout() ) : ?>
        <script type="text/javascript">
            jQuery(function($){
                $('form.woocommerce-cart-form, form.checkout').on('change', 'input[name^="shipping_method"]', function(){
                    $(document.body).trigger('update_checkout');
                });
            });
        </script>
    <?php endif;
}
