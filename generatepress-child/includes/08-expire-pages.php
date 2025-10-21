<?php
/**
 * -----------------------------
 * 08 Expire pages/posts
 * -----------------------------
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Redirect and trash expired posts/pages.
function gp_expire_and_redirect() {
    if ( is_admin() ) return; // Skip admin area

    global $post;
    if ( empty( $post ) ) return;

    $expire_date  = get_post_meta( $post->ID, '_expire_date', true );
    $redirect_url = get_post_meta( $post->ID, '_expire_redirect', true );

    // Require both fields
    if ( empty( $expire_date ) || empty( $redirect_url ) ) return;

    $now              = current_time( 'timestamp' );
    $expire_timestamp = strtotime( $expire_date );

    // If expired, move to trash and redirect
    if ( $expire_timestamp && $expire_timestamp <= $now ) {
        // Move the post/page to trash
        wp_update_post( array(
            'ID'          => $post->ID,
            'post_status' => 'trash',
        ) );

        // Redirect permanently (301)
        wp_redirect( esc_url_raw( $redirect_url ), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'gp_expire_and_redirect' );

// Add meta box for expiry fields.
function gp_add_expiry_meta_box() {
    add_meta_box(
        'gp_expiry_meta_box',
        'Page Expiration',
        'gp_render_expiry_meta_box',
        array( 'post', 'page' ),
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'gp_add_expiry_meta_box' );

// Render the expiry meta box.
function gp_render_expiry_meta_box( $post ) {
    $expire_date  = get_post_meta( $post->ID, '_expire_date', true );
    $redirect_url = get_post_meta( $post->ID, '_expire_redirect', true );

    wp_nonce_field( 'gp_save_expiry_meta_box', 'gp_expiry_meta_box_nonce' );
    ?>
    <p>
        <label for="gp_expire_date"><strong>Expiry Date/Time</strong></label><br>
        <input
            type="datetime-local"
            id="gp_expire_date"
            name="gp_expire_date"
            value="<?php echo esc_attr( $expire_date ? date( 'Y-m-d\TH:i', strtotime( $expire_date ) ) : '' ); ?>"
            style="width:100%;"
        />
    </p>
    <p>
        <label for="gp_expire_redirect"><strong>Redirect URL</strong></label><br>
        <input
            type="text"
            id="gp_expire_redirect"
            name="gp_expire_redirect"
            value="<?php echo esc_attr( $redirect_url ); ?>"
            placeholder="/shop/the-book or https://example.com/shop/the-book"
            style="width:100%;"
        />
    </p>
    <?php
}


// Save the expiry meta box fields.
function gp_save_expiry_meta_box( $post_id ) {
    if (
        ! isset( $_POST['gp_expiry_meta_box_nonce'] ) ||
        ! wp_verify_nonce( $_POST['gp_expiry_meta_box_nonce'], 'gp_save_expiry_meta_box' )
    ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['gp_expire_date'] ) ) {
        $expire_date = sanitize_text_field( $_POST['gp_expire_date'] );
        update_post_meta( $post_id, '_expire_date', $expire_date );
    }

    if ( isset( $_POST['gp_expire_redirect'] ) ) {
        $redirect_url = esc_url_raw( trim( $_POST['gp_expire_redirect'] ) );
        update_post_meta( $post_id, '_expire_redirect', $redirect_url );
    }
}
add_action( 'save_post', 'gp_save_expiry_meta_box' );


// Add "Expires On" column to post/page lists.
function gp_add_expiry_column( $columns ) {
    $columns['gp_expire_date'] = 'Expires On';
    return $columns;
}
add_filter( 'manage_posts_columns', 'gp_add_expiry_column' );
add_filter( 'manage_pages_columns', 'gp_add_expiry_column' );


// Display expiry date in the admin list column.
function gp_show_expiry_column( $column, $post_id ) {
    if ( $column === 'gp_expire_date' ) {
        $expire_date = get_post_meta( $post_id, '_expire_date', true );
        echo $expire_date
            ? esc_html( date( 'Y-m-d H:i', strtotime( $expire_date ) ) )
            : '<span style="color:#aaa;">—</span>';
    }
}
add_action( 'manage_posts_custom_column', 'gp_show_expiry_column', 10, 2 );
add_action( 'manage_pages_custom_column', 'gp_show_expiry_column', 10, 2 );
