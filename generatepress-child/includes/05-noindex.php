<?php
/**
 * -----------------------------
 * 05 Noindex tags (Page & Client Report)
 * -----------------------------
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// --- 1. Add "No index" column ---
function venture_noindex_add_column( $columns ) {
    $columns['noindex'] = __('No index', 'venture-media');
    return $columns;
}
add_filter('manage_page_posts_columns', 'venture_noindex_add_column');
add_filter('manage_client_report_posts_columns', 'venture_noindex_add_column');

// --- 2. Render checkbox ---
function venture_noindex_render_column( $column, $post_id ) {
    if ( $column === 'noindex' ) {
        $checked = get_post_meta( $post_id, '_noindex', true ) ? 'checked' : '';
        $nonce   = wp_create_nonce( 'toggle_noindex_' . $post_id );
        echo '<input type="checkbox" class="noindex-toggle" data-post-id="' . $post_id . '" data-nonce="' . $nonce . '" ' . $checked . '>';
    }
}
add_action('manage_page_posts_custom_column', 'venture_noindex_render_column', 10, 2);
add_action('manage_client_report_posts_custom_column', 'venture_noindex_render_column', 10, 2); // <-- added CPT

// --- 3. Save checkbox via AJAX ---
function venture_noindex_ajax_save() {
    $post_id = intval($_POST['post_id'] ?? 0);
    $value   = !empty($_POST['value']) ? 1 : 0;
    $nonce   = $_POST['nonce'] ?? '';

    if (!$post_id || ! wp_verify_nonce($nonce, 'toggle_noindex_' . $post_id)) {
        wp_send_json_error('Invalid nonce');
    }

    if (! current_user_can('edit_post', $post_id)) {
        wp_send_json_error('Insufficient permissions');
    }

    update_post_meta($post_id, '_noindex', $value);
    wp_send_json_success();
}
add_action('wp_ajax_toggle_noindex', 'venture_noindex_ajax_save');

// --- 4. Output <meta name="robots" content="noindex"> on front-end ---
function venture_noindex_meta() {
    if ( is_singular( ['page','client_report'] ) && get_post_meta( get_the_ID(), '_noindex', true ) ) {
        echo '<meta name="robots" content="noindex">' . "\n";
    }
}
add_action('wp_head', 'venture_noindex_meta');
