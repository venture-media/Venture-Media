<?php
// =====================
// PHP includes
// =====================
$includes_dir = get_stylesheet_directory() . '/includes/';

if (is_dir($includes_dir)) {
    foreach (scandir($includes_dir) as $file) {
        $file_path = $includes_dir . $file;
        if (is_file($file_path) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
            require_once $file_path;
        }
    }
} else {
    error_log('Includes folder does not exist: ' . $includes_dir);
}

// =====================
// Enqueue CSS/JS
// =====================
function gp_child_enqueue_assets() {
    // 1️⃣ Parent theme style
    wp_enqueue_style(
        'gp-parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme('generatepress')->get('Version')
    );

    // 2️⃣ Child theme style (this will load after parent)
    wp_enqueue_style(
        'gp-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['gp-parent-style'], // make child depend on parent
        filemtime(get_stylesheet_directory() . '/style.css')
    );

    // Auto-enqueue CSS from /css/ folder
    $css_dir_path = get_stylesheet_directory() . '/css/';
    $css_dir_uri  = get_stylesheet_directory_uri() . '/css/';

    if (is_dir($css_dir_path)) {
        $css_files = array_filter(scandir($css_dir_path), function($file) use ($css_dir_path) {
            return is_file($css_dir_path . $file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'css';
        });

        if (!empty($css_files)) {
            natsort($css_files);
            foreach ($css_files as $file_name) {
                $file_path = $css_dir_path . $file_name;
                wp_enqueue_style(
                    'gp-' . pathinfo($file_name, PATHINFO_FILENAME),
                    $css_dir_uri . $file_name,
                    ['gp-child-style'], // make all additional CSS depend on child
                    filemtime($file_path)
                );
            }
        }
    }

    // -----------------------------
    // Frontend JS (whitelisted)
    // -----------------------------
    $approved_js = [
        '00-header-scroll.js',
        '01-menu-site-overlay.js',
        '02-export-report.js',
        '03-noindex.js',
    ];

    $js_path = get_stylesheet_directory() . '/js/';
    $js_url  = get_stylesheet_directory_uri() . '/js/';

    foreach ($approved_js as $file) {
        $file_path = $js_path . $file;
        $file_url  = $js_url . $file;

        if (file_exists($file_path)) {
            wp_enqueue_script(
                'gp-' . basename($file, '.js'),
                $file_url,
                array('jquery'),
                filemtime($file_path),
                true
            );
        }
    }

    // External scripts
    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js',
        [],
        null,
        true
    );
}
// Higher priority so this runs after parent
add_action('wp_enqueue_scripts', 'gp_child_enqueue_assets', 20);


// ==============================
// Admin JS whitelist
// ==============================
function gp_child_admin_js() {
    $approved_admin_js = [
        '03-noindex.js',
    ];

    $js_path = get_stylesheet_directory() . '/js/';
    $js_url  = get_stylesheet_directory_uri() . '/js/';

    foreach ($approved_admin_js as $file) {
        $file_path = $js_path . $file;
        if (file_exists($file_path)) {
            $handle = 'gp-admin-' . basename($file, '.js');
            wp_enqueue_script(
                $handle,
                $js_url . $file,
                ['jquery'],
                filemtime($file_path),
                true
            );

            // Pass ajaxurl to JS only once per file
            wp_localize_script($handle, 'gpNoindex', [
                'ajaxurl' => admin_url('admin-ajax.php')
            ]);
        }
    }
}
add_action('admin_enqueue_scripts', 'gp_child_admin_js');
