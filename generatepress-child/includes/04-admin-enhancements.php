<?php
/**
 * -----------------------------
 * 04 Admin Enhancements
 * -----------------------------
 */



// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Only define once
if ( ! function_exists( 'venture_admin_sort_client_reports_by_tags' ) ) :

function venture_admin_sort_client_reports_by_tags() {

    // 1. Make Tags column sortable for Client Reports
    add_filter( 'manage_edit-client_report_sortable_columns', function( $columns ) {
        $columns['tags'] = 'tags';
        return $columns;
    } );

    // 2. Adjust the query when sorting by tags
    add_filter( 'posts_clauses', function( $clauses, $query ) {
        global $wpdb;

        // Only in admin, main query, for client_report, and when ordering by tags
        if ( is_admin() 
            && $query->is_main_query() 
            && $query->get('post_type') === 'client_report'
            && $query->get('orderby') === 'tags' ) {

            // Join terms with LEFT JOIN to include reports without tags
            $clauses['join'] .= "
                LEFT JOIN {$wpdb->term_relationships} AS tr ON {$wpdb->posts}.ID = tr.object_id
                LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'post_tag'
                LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
            ";

            // Group by post ID so LEFT JOIN works
            $clauses['groupby'] = "{$wpdb->posts}.ID";

            // Order by tag names alphabetically; posts without tags will appear first
            $clauses['orderby'] = "GROUP_CONCAT(t.name ORDER BY t.name ASC)";
        }

        return $clauses;
    }, 10, 2 );
}

add_action( 'after_setup_theme', 'venture_admin_sort_client_reports_by_tags' );

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
add_action( 'init', 'cpt_client_reports', 0 );


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
add_action( 'init', 'cpt_client_reports_taxonomy', 10 );


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


