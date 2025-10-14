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
