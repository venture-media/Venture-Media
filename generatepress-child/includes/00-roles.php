<?php
/**
 * -----------------------------
 * 00 Roles
 * -----------------------------
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register custom roles
 */
function venture_register_staff_role() {

    // Only add role if it doesn't already exist
    if ( ! get_role( 'staff' ) ) {

        add_role(
            'staff',
            __( 'Staff', 'venture' ),
            array(
                'read'                     => true,
                'edit_posts'               => false,
                'delete_posts'             => false,
                'edit_pages'               => false,
                'edit_others_posts'        => false,
                'publish_posts'            => false,
                'upload_files'             => true,  // can upload files (for profile images)
                'edit_user_meta'           => true,  // custom capability for staff meta
            )
        );
    }

}
add_action( 'init', 'venture_register_staff_role', 5 ); // early init, before shortcodes load


// Redirect non-logged-in users away from Staff Dashboard page
function protect_staff_dashboard_page() {
    if ( is_page(1134) && ! is_user_logged_in() ) {
        wp_safe_redirect( wp_login_url( get_permalink(1134) ) );
        exit;
    }
}
add_action( 'template_redirect', 'protect_staff_dashboard_page' );


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
