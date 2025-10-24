<?php
/**
 * -----------------------------
 * 08 Expire pages/posts
 * -----------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'template_redirect', 'venture_handle_expired_posts' );

function venture_handle_expired_posts() {
	if ( is_admin() ) {
		return;
	}

	global $post;

	// Ensure we have a post and it’s singular
	if ( empty( $post ) || ! is_singular() ) {
		return;
	}

	$expire_date  = get_post_meta( $post->ID, '_expire_date', true );
	$redirect_url = get_post_meta( $post->ID, '_expire_redirect', true );

	// Only proceed if both exist
	if ( empty( $expire_date ) || empty( $redirect_url ) ) {
		return;
	}

	$expire_timestamp = strtotime( str_replace( 'T', ' ', $expire_date ) );
	$now = current_time( 'timestamp' );

	// If expired
	if ( $expire_timestamp && $expire_timestamp <= $now ) {

		// 1. Move to trash
		if ( 'trash' !== get_post_status( $post->ID ) ) {
			wp_trash_post( $post->ID );
		}

		// 2. Add redirect to Venture Redirects list
		$redirects = get_option( 'venture_redirects_list', [] );
		if ( ! is_array( $redirects ) ) {
			$redirects = [];
		}

		// Ensure both paths are clean and have trailing slashes
		$from_path = trailingslashit( parse_url( get_permalink( $post->ID ), PHP_URL_PATH ) );
		$to_path   = trailingslashit( parse_url( $redirect_url, PHP_URL_PATH ) );

		if ( ! empty( $from_path ) && ! empty( $to_path ) ) {
			$redirects[ $from_path ] = $to_path;
			update_option( 'venture_redirects_list', $redirects );
		}
	}
}
