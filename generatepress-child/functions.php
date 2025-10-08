<?php
// =====================
// PHP includes
// =====================
$includes_dir = get_stylesheet_directory() . '/includes/';

if (is_dir($includes_dir)) {
    foreach (scandir($includes_dir) as $file) {
        $file_path = $includes_dir . $file;
        if (is_file($file_path) && strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'php') {
            require_once $file_path;
        }
    }
} else {
    error_log('Includes folder does not exist: ' . $includes_dir);
}

// =====================
// Enqueue CSS/JS
// =====================
function gp_child_enqueue_assets() {
    // 1. Parent theme style
    wp_enqueue_style(
        'gp-parent-style',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme('generatepress')->get('Version')
    );

    // 2. Child theme style.css (loaded after parent)
    wp_enqueue_style(
        'gp-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('gp-parent-style'),
        wp_get_theme()->get('Version')
    );

    // 3. Auto-enqueue additional CSS from /css/
    $css_dir_path = get_stylesheet_directory() . '/css/';
    $css_dir_uri  = get_stylesheet_directory_uri() . '/css/';

    if (is_dir($css_dir_path)) {
        $css_files = array_filter(scandir($css_dir_path), function($file) use ($css_dir_path) {
            return is_file($css_dir_path . $file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'css';
        });

        if (!empty($css_files)) {
            natsort($css_files);
            foreach ($css_files as $file_name) {
                $file_path = $css_dir_path . $file_name;
                wp_enqueue_style(
                    'gp-' . pathinfo($file_name, PATHINFO_FILENAME),
                    $css_dir_uri . $file_name,
                    array('gp-child-style'), // ensures it loads after child style.css
                    filemtime($file_path)
                );
            }
        } else {
            error_log('Auto-enqueue CSS: No CSS files found in ' . $css_dir_path);
        }
    }

    // 4. JS (whitelisted)
    $approved_js = [
        '00-header-scroll.js',
        '01-menu-site-overlay.js',
        '02-export-report.js',
    ];

    $js_path = get_stylesheet_directory() . '/js/';
    $js_url  = get_stylesheet_directory_uri() . '/js/';

    foreach ($approved_js as $file) {
        $file_path = $js_path . $file;
        $file_url  = $js_url . $file;

        if (file_exists($file_path)) {
            wp_enqueue_script(
                'gp-' . basename($file, '.js'),
                $file_url,
                array('jquery'),
                filemtime($file_path),
                true
            );
        }
    }

    // 5. External scripts
    if (is_singular('post')) {
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
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
