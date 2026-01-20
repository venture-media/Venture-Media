<?php
/**
 * -----------------------------
 * 04 Admin Enhancements
 * -----------------------------
 */




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

add_action( 'after_setup_theme', 'venture_admin_sort_posts_by_tags' );

endif;


function cpt_client_reports() {
    register_post_type( 'client_report', [
        'labels' => [
            'name' => 'Client Reports',
            'singular_name' => 'Client Report',
            'add_new' => 'Add Report',
            'add_new_item' => 'Add New Report',
            'edit_item' => 'Edit Report',
            'new_item' => 'New Report',
            'view_item' => 'View Report',
            'search_items' => 'Search Reports',
            'not_found' => 'No reports found',
            'not_found_in_trash' => 'No reports found in Trash',
            'all_items' => 'All Reports',
            'menu_name' => 'Client Reports',
            'name_admin_bar' => 'Report'
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-portfolio',
        'supports' => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
        'rewrite' => [
            'slug' => 'reports/%report_category%',
            'with_front' => false
]
    ]);
}
add_action( 'init', 'cpt_client_reports' );



function cpt_client_reports() {
    register_post_type( 'client_report', [
        'labels' => [
            'name' => 'Client Reports',
            'singular_name' => 'Client Report',
            'add_new' => 'Add Report',
            'add_new_item' => 'Add New Report',
            'edit_item' => 'Edit Report',
            'new_item' => 'New Report',
            'view_item' => 'View Report',
            'search_items' => 'Search Reports',
            'not_found' => 'No reports found',
            'not_found_in_trash' => 'No reports found in Trash',
            'all_items' => 'All Reports',
            'menu_name' => 'Client Reports',
            'name_admin_bar' => 'Report'
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-portfolio',
        'supports' => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
        'rewrite' => [
            'slug' => 'reports/%report_category%',
            'with_front' => false
        ]
    ]);
}
add_action( 'init', 'cpt_client_reports' );


function cpt_client_reports_taxonomy() {
    $labels = [
        'name' => 'Report Categories',
        'singular_name' => 'Report Category',
        'search_items' => 'Search Categories',
        'all_items' => 'All Categories',
        'parent_item' => 'Parent Category',
        'parent_item_colon' => 'Parent Category:',
        'edit_item' => 'Edit Category',
        'update_item' => 'Update Category',
        'add_new_item' => 'Add New Category',
        'new_item_name' => 'New Category Name',
        'menu_name' => 'Report Categories',
    ];

    register_taxonomy( 'report_category', [ 'client_report' ], [
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_in_rest' => true,
        'rewrite' => [
            'slug' => 'reports',
            'with_front' => false,
            'hierarchical' => true
        ],
    ]);
}
add_action( 'init', 'cpt_client_reports_taxonomy', 0 );


function client_reports_permalink( $post_link, $post ) {
    if ( $post->post_type === 'client_report' ) {
        if ( $terms = get_the_terms( $post->ID, 'report_category' ) ) {
            $post_link = str_replace( '%report_category%', array_pop($terms)->slug, $post_link );
        } else {
            $post_link = str_replace( '%report_category%', 'uncategorized', $post_link );
        }
    }
    return $post_link;
}
add_filter( 'post_type_link', 'client_reports_permalink', 10, 2 );

