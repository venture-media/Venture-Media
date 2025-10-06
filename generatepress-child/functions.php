<?php
// =======================
// Front-end assets (CSS + JS)
// =======================
function gp_child_enqueue_assets() {
    // Load parent and base child styles
    wp_enqueue_style('gp-parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('gp-child-style', get_stylesheet_uri());

    // Auto-enqueue all CSS files from /css
    $css_path = get_stylesheet_directory() . '/css/';
    $css_files = glob($css_path . '*.css');

    // Sort naturally (ensures 0, 1, 2... order)
    natsort($css_files);

    foreach ($css_files as $file) {
        $handle = 'gp-' . basename($file, '.css');
        wp_enqueue_style(
            $handle,
            get_stylesheet_directory_uri() . '/css/' . basename($file),
            array('gp-child-style'),
            filemtime($file) // cache-busting by modification time
        );
    }

    // Custom JS: header scroll
    wp_enqueue_script(
        'child-header-scroll',
        get_stylesheet_directory_uri() . '/js/header-scroll.js',
        array(),
        filemtime(get_stylesheet_directory() . '/js/header-scroll.js'),
        true
    );

    // Chart.js (load only on single post pages)
    if ( is_single() && get_post_type() === 'post' ) {
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            null,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'gp_child_enqueue_assets');



function my_register_report_chart_widget( $widgets_manager ) {
    require_once get_stylesheet_directory() . '/elementor-widgets/class-report-chart-widget.php';
    $widgets_manager->register( new \Report_Chart_Widget() );
}
add_action( 'elementor/widgets/register', 'my_register_report_chart_widget' );



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


// Hide admin bar for staff users
add_action('after_setup_theme', function() {
    if (current_user_can('staff') && !current_user_can('administrator')) {
        show_admin_bar(false);
    }
});


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
