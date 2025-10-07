<?php
/**
 * Plugin Name: Venture Enhancements v1.0.0-rc
 * Plugin URI:  https://github.com/venture-media/Venture-Media
 * Description: Site-specific enhancements and non-essential features for Venture Media.
 * Version:     1.0.0-rc
 * Author:      Leon de Klerk
 * Author URI:  https://github.com/Leon2332
 *
 * ------------------------------------------------------------------
 *  PLUGIN STRUCTURE OVERVIEW
 * ------------------------------------------------------------------
 *
 * venture-enhancements/
 * │
 * ├─ venture-enhancements.php
 * │   → Main plugin loader file
 * │
 * ├─ includes/
 * │   ├─ staff-shortcodes.php          → Staff Dashboard shortcodes
 * │   ├─ woocommerce-tweaks.php        → Minor WooCommerce customizations
 * │   ├─ frontend-enhancements.php     → Custom social icons + asset enqueues
 * │   ├─ admin-sort-tags.php           → Sort posts by tags alphabetically in admin
 * │   └─ redirect-manager.php          → Programmatic redirects management (wp-admin dashboard widget)
 * │
 * └─ assets/
 *     ├─ css/
 *     │   └─ export-report.css         → Styling for report exports (Client reports)
 *     │
 *     └─ js/
 *         └─ export-report.js          → Handles dynamic report export logic
 *
 * ------------------------------------------------------------------
 *  NOTES
 * ------------------------------------------------------------------
 *  • The /includes/ folder contains modular PHP logic.
 *  • The /assets/ folder holds enqueueable CSS and JS files.
 *  • Only whitelisted JS is enqueued for security.
 *  • CSS is auto-enqueued for convenience.
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
