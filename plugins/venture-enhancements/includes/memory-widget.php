<?php

// -----------------------------
// 1. Create a custom table for memory logs & schedule cleanup
// -----------------------------
add_action('after_switch_theme', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'memory_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT(20) DEFAULT NULL,
        url TEXT NOT NULL,
        context VARCHAR(20) NOT NULL,
        peak_memory FLOAT NOT NULL,
        logged_at DATETIME NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Schedule daily cleanup if not already scheduled
    if (!wp_next_scheduled('memory_log_cleanup_daily')) {
        wp_schedule_event(time(), 'daily', 'memory_log_cleanup_daily');
    }
});

// -----------------------------
// 2. Log peak memory usage on every request (admins only to reduce database writes)
// -----------------------------
add_action('shutdown', function() {
    if (!current_user_can('manage_options')) return; // Only log for admins

    global $wpdb;
    $table_name = $wpdb->prefix . 'memory_log';

    $post_id = isset($GLOBALS['post']->ID) ? $GLOBALS['post']->ID : null;
    $url = is_admin() ? admin_url($_SERVER['REQUEST_URI']) : get_permalink($post_id) ?: $_SERVER['REQUEST_URI'];
    $context = is_admin() ? 'admin' : 'frontend';
    $peak_memory = memory_get_peak_usage(true) / 1024 / 1024; // MB
    $logged_at = current_time('mysql');

    $wpdb->insert($table_name, [
        'post_id' => $post_id,
        'url' => $url,
        'context' => $context,
        'peak_memory' => $peak_memory,
        'logged_at' => $logged_at,
    ]);
});

// -----------------------------
// 3. Dashboard widget to display metrics
// -----------------------------
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget('memory_limit_widget', 'PHP Memory & Usage', function() {
        if (!current_user_can('manage_options')) return;

        global $wpdb;
        $table_name = $wpdb->prefix . 'memory_log';
        $config_path = ABSPATH . 'wp-config.php';
        $memory_limit = ini_get('memory_limit');

        // Top page by memory usage in last 30 days
        $top_admin = $wpdb->get_row($wpdb->prepare(
            "SELECT url, peak_memory FROM $table_name WHERE context='admin' AND logged_at >= %s ORDER BY peak_memory DESC LIMIT 1",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        $top_frontend = $wpdb->get_row($wpdb->prepare(
            "SELECT url, peak_memory FROM $table_name WHERE context='frontend' AND logged_at >= %s ORDER BY peak_memory DESC LIMIT 1",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));

        // Oldest record age in days
        $oldest_record = $wpdb->get_var("SELECT MIN(logged_at) FROM $table_name");
        $days_old = $oldest_record ? round((time() - strtotime($oldest_record)) / DAY_IN_SECONDS) : 0;

        echo '<p><strong>wp-config.php path:</strong><br>' . esc_html($config_path) . '</p>';
        echo '<p><strong>Current PHP memory limit:</strong> ' . esc_html($memory_limit) . '</p>';

        echo '<h4>Page with highest memory usage (last 30 days)</h4>';
        echo '<p><strong>Admin:</strong> ' . esc_html($top_admin->url ?? 'No data') . 
             ' (' . number_format($top_admin->peak_memory ?? 0, 2) . ' MB)</p>';
        echo '<p><strong>Frontend:</strong> ' . esc_html($top_frontend->url ?? 'No data') . 
             ' (' . number_format($top_frontend->peak_memory ?? 0, 2) . ' MB)</p>';

        echo '<p><strong>Oldest record:</strong> ' . esc_html($days_old) . ' day(s)</p>';
    });
});

// -----------------------------
// 4. Daily cleanup: remove records older than 30 days
// -----------------------------
add_action('memory_log_cleanup_daily', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'memory_log';
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE logged_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        )
    );
});

// -----------------------------
// 5. Unschedule cleanup on plugin deactivation
// -----------------------------
register_deactivation_hook( __FILE__, function() {
    $timestamp = wp_next_scheduled('memory_log_cleanup_daily');
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'memory_log_cleanup_daily' );
    }
});
