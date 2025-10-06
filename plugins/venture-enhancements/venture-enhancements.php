<?php
/**
 * Plugin Name: Venture Enhancements v0.9.11-beta
 * Plugin URI:  https://github.com/venture-media/Venture-Media
 * Description: Site-specific enhancements, non-essential features for Venture Media website.
 * Version:     0.9.11-beta
 * Author:      Leon de Klerk
 * Author URI:  https://github.com/Leon2332
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * -----------------------------
 * 1. Staff Dashboard Shortcodes
 * -----------------------------
 */
require_once plugin_dir_path(__FILE__) . 'includes/staff-shortcodes.php';

/**
 * -----------------------------
 * 2. WooCommerce Tweaks
 * -----------------------------
 */
require_once plugin_dir_path(__FILE__) . 'includes/woocommerce-tweaks.php';

/**
 * -----------------------------
 * 3. Front-end Enhancements
 * -----------------------------
 */
require_once plugin_dir_path(__FILE__) . 'includes/frontend-enhancements.php';

/**
 * -----------------------------
 * 4. Admin Enhancements
 * -----------------------------
 */
require_once plugin_dir_path(__FILE__) . 'includes/admin-sort-tags.php';

/**
 * -----------------------------
 * 5. Redirect Manager
 * -----------------------------
 */
require_once plugin_dir_path(__FILE__) . 'includes/redirect-manager.php';
