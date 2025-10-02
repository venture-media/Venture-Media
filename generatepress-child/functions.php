<?php
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'generatepress-parent-style', get_template_directory_uri() . '/style.css' );

    // Enqueue custom JS
    wp_enqueue_script(
        'child-header-scroll',
        get_stylesheet_directory_uri() . '/js/header-scroll.js',
        array(),
        null,
        true 
    );
});


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


function my_enqueue_chartjs() {
    // Load only on single posts
    if ( is_single() && get_post_type() === 'post' ) {
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            null,
            true
        );
    }
}
add_action( 'elementor/frontend/after_enqueue_scripts', 'my_enqueue_chartjs' );


function my_register_report_chart_widget( $widgets_manager ) {
    require_once get_stylesheet_directory() . '/elementor-widgets/class-report-chart-widget.php';
    $widgets_manager->register( new \Report_Chart_Widget() );
}
add_action( 'elementor/widgets/register', 'my_register_report_chart_widget' );



// Remove upsells and related products
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );


// Shortcode to display an Elementor template
function my_elementor_template_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'id' => '', // Elementor template ID
    ], $atts, 'elementor_template' );

    if ( empty( $atts['id'] ) ) {
        return ''; // no ID provided
    }

    return \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $atts['id'] );
}
add_shortcode( 'elementor_template', 'my_elementor_template_shortcode' );


function my_custom_image_above_password_form( $content ) {
    if ( post_password_required() ) {
        $image_url = 'https://www.venture.com.na/wp-content/uploads/2025/09/Advertising.jpg';

        $img_html = '<div class="protected-decorative-image"><img src="' . esc_url( $image_url ) . '" alt="" /></div>';

        $form = get_the_password_form();
        return $img_html . $form;
    }

    return $content;
}
add_filter( 'the_content', 'my_custom_image_above_password_form', 0 );



// Allow shortcodes in menu item titles
add_filter( 'wp_nav_menu_items', function( $items, $args ) {
    return do_shortcode( $items );
}, 10, 2 );


function rename_post_labels() {
    global $wp_post_types;

    // Target the default "post" post type
    $labels = &$wp_post_types['post']->labels;

    $labels->name = 'Reports';
    $labels->singular_name = 'Report';
    $labels->add_new = 'Add Report';
    $labels->add_new_item = 'Add New Report';
    $labels->edit_item = 'Edit Report';
    $labels->new_item = 'Report';
    $labels->view_item = 'View Report';
    $labels->search_items = 'Search Reports';
    $labels->not_found = 'No reports found';
    $labels->not_found_in_trash = 'No reports found in Trash';
    $labels->all_items = 'All Reports';
    $labels->menu_name = 'Client reports'; // matches your menu rename
    $labels->name_admin_bar = 'Report';
}
add_action( 'init', 'rename_post_labels' );


// Shortcode: [staff_dashboard]
function staff_dashboard_shortcode() {
    $user_id = get_current_user_id();

    // Only staff OR admins can see this page
    if ( ! ( current_user_can('staff') || current_user_can('administrator') ) ) {
        return '<p>You do not have permission to access this page.</p>';
    }

    // Handle form submission
    if ( isset($_POST['staff_nonce']) && wp_verify_nonce($_POST['staff_nonce'], 'staff_update') ) {
        if ( ! function_exists( 'media_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        // Save job title
        update_user_meta($user_id, 'staff_title', sanitize_text_field($_POST['staff_title']));

        // Save bio
        update_user_meta($user_id, 'staff_bio', sanitize_textarea_field($_POST['staff_bio']));

        // Handle image uploads
        $image_fields = [
            'staff_img1' => 'Profile picture',
            'staff_img2' => 'Slider image (About us)',
            'staff_img3' => 'Your handwriting (About us)',
        ];

        foreach ($image_fields as $field => $label) {
            // Delete if requested
            if (isset($_POST['delete_'.$field]) && $_POST['delete_'.$field] == '1') {
                delete_user_meta($user_id, $field);
            }

            // Upload new
            if (!empty($_FILES[$field]['name'])) {
                // Restrict staff_img3 to PNG only
                if ($field === 'staff_img3' && strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION)) !== 'png') {
                    echo '<p class="staff-error">❌ "'.$label.'" must be a PNG file.</p>';
                } else {
                    $img_id = media_handle_upload($field, 0);
                    if (!is_wp_error($img_id)) {
                        update_user_meta($user_id, $field, $img_id);
                    }
                }
            }
        }

        echo '<p class="staff-success">✅ Your details have been updated.</p>';
    }

    // Get existing values
    $title = get_user_meta($user_id, 'staff_title', true);
    $bio   = get_user_meta($user_id, 'staff_bio', true);
    $img1  = get_user_meta($user_id, 'staff_img1', true);
    $img2  = get_user_meta($user_id, 'staff_img2', true);
    $img3  = get_user_meta($user_id, 'staff_img3', true);

    ob_start(); ?>
    <form method="post" enctype="multipart/form-data" class="staff-profile-form">
        <label for="staff_title">Job Title</label>
        <input type="text" id="staff_title" name="staff_title" value="<?php echo esc_attr($title); ?>">

        <label for="staff_bio">Biography</label>
        <textarea id="staff_bio" name="staff_bio" rows="6"><?php echo esc_textarea($bio); ?></textarea>

        <?php
        $image_fields = [
            'staff_img1' => 'Profile picture',
            'staff_img2' => 'Slider image (About us)',
            'staff_img3' => 'Your handwriting (About us)',
        ];
        foreach ($image_fields as $field => $label):
            $img_id = get_user_meta($user_id, $field, true);
            $img_url = $img_id ? wp_get_attachment_url($img_id) : false;
        ?>
            <label for="<?php echo $field; ?>"><?php echo $label; ?></label>
            <?php if ($img_url): ?>
                <div class="staff-image-preview">
                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($label); ?>" style="max-width:150px;">
                    <button type="submit" name="delete_<?php echo $field; ?>" value="1" class="staff-delete-btn">🗑</button>
                </div>
                <input type="file" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="staff-file-input">
                <small>Change image</small>
            <?php else: ?>
                <input type="file" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="staff-file-input">
                <small>Upload image</small>
            <?php endif; ?>
        <?php endforeach; ?>

        <input type="hidden" name="staff_nonce" value="<?php echo wp_create_nonce('staff_update'); ?>">
        <button type="submit">Save Changes</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('staff_dashboard', 'staff_dashboard_shortcode');


// Shortcode: [staff_logout]
function staff_logout_shortcode() {
    if ( ! is_user_logged_in() ) {
        return ''; // hide for guests
    }

    $logout_url = wp_logout_url( home_url() ); // redirect to homepage after logout
    return '<a class="staff-profile-form-logout" href="' . esc_url( $logout_url ) . '">Log out</a>';
}
add_shortcode( 'staff_logout', 'staff_logout_shortcode' );


// Shortcode: [staff_name]
function staff_name_shortcode() {
    if ( ! is_user_logged_in() ) {
        return ''; // hide for guests
    }

    $user = wp_get_current_user();

    // Use display_name (can also use first_name or user_login if you prefer)
    return '<h2 class="staff-name">' . esc_html( $user->display_name ) . '</h2>';
}
add_shortcode('staff_name', 'staff_name_shortcode');


// Shortcode: [staff_image1]
function staff_image1_shortcode() {
    if ( ! is_user_logged_in() ) {
        return ''; // hide for guests
    }

    $user_id = get_current_user_id();
    $img1_id = get_user_meta( $user_id, 'staff_img1', true );
    $img1    = wp_get_attachment_url( $img1_id );

    if ( ! $img1 ) {
        return ''; // no image set
    }

    // Output container with image
    ob_start(); ?>
    <div class="staff-image1-container">
        <img src="<?php echo esc_url( $img1 ); ?>" alt="Staff Image 1">
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'staff_image1', 'staff_image1_shortcode' );


// Add custom social icons inside navigation
add_action( 'generate_inside_navigation', function() {
    ?>
    <div class="custom-social-icons" id="masthead">
        <a href="https://www.instagram.com/venture_namibia/" target="_blank" aria-label="Instagram">
            <svg xmlns="http://www.w3.org/2000/svg" class="custom_icon_instagram" width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12,4.622c2.403,0,2.688,0.009,3.637,0.052c0.877,0.04,1.354,0.187,1.671,0.31c0.42,0.163,0.72,0.358,1.035,0.673 c0.315,0.315,0.51,0.615,0.673,1.035c0.123,0.317,0.27,0.794,0.31,1.671c0.043,0.949,0.052,1.234,0.052,3.637 s-0.009,2.688-0.052,3.637c-0.04,0.877-0.187,1.354-0.31,1.671c-0.163,0.42-0.358,0.72-0.673,1.035 c-0.315,0.315-0.615,0.51-1.035,0.673c-0.317,0.123-0.794,0.27-1.671,0.31c-0.949,0.043-1.233,0.052-3.637,0.052 s-2.688-0.009-3.637-0.052c-0.877-0.04-1.354-0.187-1.671-0.31c-0.42-0.163-0.72-0.358-1.035-0.673 c-0.315-0.315-0.51-0.615-0.673-1.035c-0.123-0.317-0.27-0.794-0.31-1.671C4.631,14.688,4.622,14.403,4.622,12 s0.009-2.688,0.052-3.637c0.04-0.877,0.187-1.354,0.31-1.671c0.163-0.42,0.358-0.72,0.673-1.035 c0.315-0.315,0.615-0.51,1.035-0.673c0.317-0.123,0.794-0.27,1.671-0.31C9.312,4.631,9.597,4.622,12,4.622 M12,3 C9.556,3,9.249,3.01,8.289,3.054C7.331,3.098,6.677,3.25,6.105,3.472C5.513,3.702,5.011,4.01,4.511,4.511 c-0.5,0.5-0.808,1.002-1.038,1.594C3.25,6.677,3.098,7.331,3.054,8.289C3.01,9.249,3,9.556,3,12c0,2.444,0.01,2.751,0.054,3.711 c0.044,0.958,0.196,1.612,0.418,2.185c0.23,0.592,0.538,1.094,1.038,1.594c0.5,0.5,1.002,0.808,1.594,1.038 c0.572,0.222,1.227,0.375,2.185,0.418C9.249,20.99,9.556,21,12,21s2.751-0.01,3.711-0.054c0.958-0.044,1.612-0.196,2.185-0.418 c0.592-0.23,1.094-0.538,1.594-1.038c0.5-0.5,0.808-1.002,1.038-1.594c0.222-0.572,0.375-1.227,0.418-2.185 C20.99,14.751,21,14.444,21,12s-0.01-2.751-0.054-3.711c-0.044-0.958-0.196-1.612-0.418-2.185c-0.23-0.592-0.538-1.094-1.038-1.594 c-0.5-0.5-1.002-0.808-1.594-1.038c-0.572-0.222-1.227-0.375-2.185-0.418C14.751,3.01,14.444,3,12,3L12,3z M12,7.378 c-2.552,0-4.622,2.069-4.622,4.622S9.448,16.622,12,16.622s4.622-2.069,4.622-4.622S14.552,7.378,12,7.378z M12,15 c-1.657,0-3-1.343-3-3s1.343-3,3-3s3,1.343,3,3S13.657,15,12,15z M16.804,6.116c-0.596,0-1.08,0.484-1.08,1.08 s0.484,1.08,1.08,1.08c0.596,0,1.08-0.484,1.08-1.08S17.401,6.116,16.804,6.116z"/>
            </svg>
        </a>
        <a href="https://www.linkedin.com/company/18213666/" target="_blank" aria-label="LinkedIn">
            <svg xmlns="http://www.w3.org/2000/svg" class="custom_icon_linkedin" width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.7,3H4.3C3.582,3,3,3.582,3,4.3v15.4C3,20.418,3.582,21,4.3,21h15.4c0.718,0,1.3-0.582,1.3-1.3V4.3 C21,3.582,20.418,3,19.7,3z M8.339,18.338H5.667v-8.59h2.672V18.338z M7.004,8.574c-0.857,0-1.549-0.694-1.549-1.548 c0-0.855,0.691-1.548,1.549-1.548c0.854,0,1.547,0.694,1.547,1.548C8.551,7.881,7.858,8.574,7.004,8.574z M18.339,18.338h-2.669 v-4.177c0-0.996-0.017-2.278-1.387-2.278c-1.389,0-1.601,1.086-1.601,2.206v4.249h-2.667v-8.59h2.559v1.174h0.037 c0.356-0.675,1.227-1.387,2.526-1.387c2.703,0,3.203,1.779,3.203,4.092V18.338z"/>
            </svg>
        </a>
    </div>
    <?php
}, 20);


// Redirect non-logged-in users away from Staff Dashboard page
function protect_staff_dashboard_page() {
    if ( is_page(1134) && ! is_user_logged_in() ) {
        wp_safe_redirect( wp_login_url( get_permalink(1134) ) );
        exit;
    }
}
add_action( 'template_redirect', 'protect_staff_dashboard_page' );


// Register "Staff" role with basic permissions
function register_staff_role() {
    add_role(
        'staff',
        'Staff',
        array(
            'read'         => true,
            'upload_files' => true, // allow image uploads
            'edit_posts'   => false // NOT allowed to publish/edit posts
        )
    );
}
add_action('init', 'register_staff_role');


// Redirect Staff users away from wp-admin to the staff page
function redirect_staff_from_admin() {
    if ( is_admin() && ! defined('DOING_AJAX') && current_user_can('staff') ) {
        wp_redirect( get_permalink(1134) ); // staff page ID
        exit;
    }
}
add_action( 'admin_init', 'redirect_staff_from_admin' );

// On login, redirect Staff to staff page
function staff_login_redirect( $redirect_to, $request, $user ) {
    if ( isset($user->roles) && is_array($user->roles) && in_array( 'staff', $user->roles ) ) {
        return get_permalink(1134); // staff page ID
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'staff_login_redirect', 10, 3 );


function vv_last_updated_shortcode( $atts ) {
    global $post;

    $atts = shortcode_atts( array(
        'format'       => get_option( 'date_format' ),
        'label'        => '',
        'show_time'    => 'false',
        'hide_if_same' => 'false',
        'class'        => 'last-updated-date', // default CSS class
    ), $atts, 'last_updated' );

    $post_id = ( is_object( $post ) && isset( $post->ID ) ) ? $post->ID : get_the_ID();
    if ( ! $post_id ) {
        return '';
    }

    $format = $atts['format'];
    if ( strtolower( $atts['show_time'] ) === 'true' ) {
        $format = $format . ' ' . get_option( 'time_format' );
    }

    $modified  = get_the_modified_date( $format, $post_id );
    $published = get_the_date( $format, $post_id );

    if ( strtolower( $atts['hide_if_same'] ) === 'true' && $modified === $published ) {
        return '';
    }

    $output = esc_html( $atts['label'] . $modified );

    return '<span class="' . esc_attr( $atts['class'] ) . '">' . $output . '</span>';
}
add_shortcode( 'last_updated', 'vv_last_updated_shortcode' );

/*
// Disable plugin installation UI
function restrict_plugin_installation() {
    // Remove "Add New" submenu
    remove_submenu_page('plugins.php', 'plugin-install.php');

    // Hide the "Add New" button with CSS
    echo '<style>
        .wrap .page-title-action,
        .plugin-install-php .page-title-action {
            display: none !important;
        }
    </style>';
}
add_action('admin_menu', 'restrict_plugin_installation', 999);
add_action('admin_head', 'restrict_plugin_installation');
*/
