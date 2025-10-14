<?php
  /**
 * -----------------------------
 * 06 Admin
 * -----------------------------
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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
    $labels->menu_name = 'Client reports';
    $labels->name_admin_bar = 'Report';
}
add_action( 'init', 'rename_post_labels' );
