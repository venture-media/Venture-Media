<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Only define once
if ( ! function_exists( 'venture_admin_sort_posts_by_tags' ) ) :

function venture_admin_sort_posts_by_tags() {

    // Make Tags column sortable
    add_filter( 'manage_edit-post_sortable_columns', function( $columns ) {
        if ( isset( $columns['tags'] ) ) return $columns; // avoid duplicate
        $columns['tags'] = 'tags';
        return $columns;
    } );

    // Default admin posts list to sort by tags
    add_action( 'pre_get_posts', function( $query ) {
        try {
            if ( ! is_admin() || ! $query->is_main_query() ) return;

            $screen = function_exists('get_current_screen') ? get_current_screen() : false;
            if ( $screen && $screen->id === 'edit-post' && ! isset( $_GET['orderby'] ) ) {
                $query->set( 'orderby', 'tags' );
                $query->set( 'order', 'ASC' );
            }
        } catch ( Exception $e ) {
            // Silently fail to avoid breaking admin
        }
    } );

    // Adjust the query when sorting by tags
    add_filter( 'posts_clauses', function( $clauses, $query ) {
        global $wpdb;
        try {
            if ( is_admin() && $query->get('orderby') === 'tags' ) {
                $clauses['join'] .= " LEFT JOIN {$wpdb->term_relationships} AS tr ON {$wpdb->posts}.ID = tr.object_id
                                      LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                                      LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id ";
                $clauses['where'] .= " AND tt.taxonomy = 'post_tag' ";
                $clauses['groupby'] = "{$wpdb->posts}.ID";
                // Sort posts alphabetically by tags (posts without tags will appear first)
                $clauses['orderby'] = "GROUP_CONCAT(t.name ORDER BY t.name ASC)";
            }
        } catch ( Exception $e ) {
            // Silently fail
        }
        return $clauses;
    }, 10, 2 );

}

add_action( 'plugins_loaded', 'venture_admin_sort_posts_by_tags' );

endif;
