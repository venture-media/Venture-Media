<?php
/**
 * Plugin Name: Venture Enhancements v1.0.0-rc2
 * Plugin URI:  https://github.com/venture-media/Venture-Media
 * Description: Site-specific enhancements and non-essential features for Venture Media.
 * Version:     1.0.0-rc2
 * Author:      Leon de Klerk
 * Author URI:  https://github.com/Leon2332
 *
 * ------------------------------------------------------------------
 *  PLUGIN STRUCTURE OVERVIEW
 * ------------------------------------------------------------------
 *
 *   venture-enhancements/
 *     │
 *     ├─ venture-enhancements.php              → Main plugin loader file
 *     │
 *     │
 *     ├─ includes/
 *     │     ├─ staff-shortcodes.php            → Staff Dashboard shortcodes
 *     │     ├─ woocommerce-tweaks.php          → Minor WooCommerce customizations
 *     │     ├─ frontend-enhancements.php       → Custom social icons + asset enqueues
 *     │     ├─ admin-sort-tags.php             → Sort posts by tags alphabetically in admin
 *     │     └─ redirect-manager.php            → Programmatic redirects management (wp-admin dashboard widget)
 *     │
 *     └─ assets/
 *           ├─ css/
 *           │   └─ export-report.css           → Styling for report exports (Client reports)
 *           │
 *           └─ js/
 *               └─ export-report.js            → Handles dynamic report export logic
 *
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
 * 01 Staff Dashboard Shortcodes
 * -----------------------------
 */
require_once plugin_dir_path(__FILE__) . 'includes/01-staff-shortcodes.php';


/**
 * -----------------------------
 * 02 WooCommerce Tweaks
 * -----------------------------
 */
require_once plugin_dir_path(__FILE__) . 'includes/02-woocommerce-tweaks.php';


/**
 * -----------------------------
 * 03 Front-end Enhancements
 * -----------------------------
 */
require_once plugin_dir_path(__FILE__) . 'includes/03-frontend-enhancements.php';


/**
 * -----------------------------
 * 04 Admin Enhancements
 * -----------------------------
 */
require_once plugin_dir_path(__FILE__) . 'includes/04-admin-sort-tags.php';


/**
 * -----------------------------
 * 05 Redirect Manager
 * -----------------------------
 */
require_once plugin_dir_path(__FILE__) . 'includes/05-redirect-manager.php';
