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


// Reports
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
        'menu_icon' => 'dashicons-chart-bar',
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


// Add Report Categories column to Client Reports list table
add_filter( 'manage_client_report_posts_columns', function( $columns ) {
    $columns['report_category'] = __( 'Report Categories', 'venture-media' );
    return $columns;
});

// Render Report Categories column content
add_action( 'manage_posts_custom_column', function( $column, $post_id ) {

    if ( get_post_type( $post_id ) !== 'client_report' ) {
        return;
    }

    if ( $column === 'report_category' ) {

        $terms = get_the_terms( $post_id, 'report_category' );

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

            $links = [];

            foreach ( $terms as $term ) {
                $links[] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( add_query_arg(
                        [ 'report_category' => $term->slug ],
                        admin_url( 'edit.php?post_type=client_report' )
                    ) ),
                    esc_html( $term->name )
                );
            }

            echo implode( ', ', $links );

        } else {
            echo '—';
        }
    }

}, 10, 2 );

// Make Report Categories column sortable
add_filter( 'manage_edit-client_report_sortable_columns', function( $columns ) {
    $columns['report_category'] = 'report_category';
    return $columns;
});


// Sorting logic for Report Categories
add_action( 'pre_get_posts', function( $query ) {

    if ( ! is_admin() || ! $query->is_main_query() || $query->get('post_type') !== 'client_report' ) return;

    if ( $query->get('orderby') === 'report_category' ) {

        add_filter( 'posts_join', function( $join, $q ) {
            global $wpdb;
            if ( $q->get('post_type') === 'client_report' ) {
                $join .= " LEFT JOIN {$wpdb->term_relationships} AS tr ON {$wpdb->posts}.ID = tr.object_id
                           LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'report_category'
                           LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id ";
            }
            return $join;
        }, 10, 2 );

        add_filter( 'posts_groupby', function( $groupby, $q ) {
            global $wpdb;
            if ( $q->get('post_type') === 'client_report' ) {
                return "{$wpdb->posts}.ID";
            }
            return $groupby;
        }, 10, 2 );

        add_filter( 'posts_orderby', function( $orderby_sql, $q ) use ( $wpdb ) {
            if ( $q->get('post_type') === 'client_report' ) {
                return "GROUP_CONCAT(t.name ORDER BY t.name ASC)";
            }
            return $orderby_sql;
        }, 10, 2 );
    }

});


// Magazines
function cpt_magazines() {
    register_post_type( 'magazines', [
        'labels' => [
            'name' => 'Magazines',
            'singular_name' => 'Magazine',
            'add_new' => 'Add Magazine',
            'add_new_item' => 'Add New Magazine',
            'edit_item' => 'Edit Magazine',
            'new_item' => 'New Magazine',
            'view_item' => 'View Magazine',
            'search_items' => 'Search Magazines',
            'not_found' => 'No magazines found',
            'not_found_in_trash' => 'No magazines found in Trash',
            'all_items' => 'All Magazines',
            'menu_name' => 'Magazines',
            'name_admin_bar' => 'Magazines'
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-tablet',
        'supports' => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
        'rewrite' => [
            'slug' => 'magazines/%magazine_category%',
            'with_front' => false
        ]
    ]);
}
add_action( 'init', 'cpt_magazines', 0 );


function cpt_magazines_taxonomy() {
    $labels = [
        'name' => 'Magazine Categories',
        'singular_name' => 'Magazine Category',
        'search_items' => 'Search Categories',
        'all_items' => 'All Categories',
        'parent_item' => 'Parent Category',
        'parent_item_colon' => 'Parent Category:',
        'edit_item' => 'Edit Category',
        'update_item' => 'Update Category',
        'add_new_item' => 'Add New Category',
        'new_item_name' => 'New Category Name',
        'menu_name' => 'Magazine Categories',
    ];

    register_taxonomy( 'magazine_category', [ 'magazines' ], [
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_in_rest' => true,
        'rewrite' => [
            'slug' => 'magazines',
            'with_front' => false,
            'hierarchical' => true
        ],
    ]);
}
add_action( 'init', 'cpt_magazines_taxonomy', 10 );


function magazines_permalink( $post_link, $post ) {
    if ( $post->post_type === 'magazines' ) {
        if ( $terms = get_the_terms( $post->ID, 'magazine_category' ) ) {
            $post_link = str_replace( '%magazine_category%', array_pop($terms)->slug, $post_link );
        } else {
            $post_link = str_replace( '%magazine_category%', 'uncategorized', $post_link );
        }
    }
    return $post_link;
}
add_filter( 'post_type_link', 'magazines_permalink', 10, 2 );



