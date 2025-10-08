<?php
/**
 * -----------------------------
 * 04 Redirect Manager
 * -----------------------------
 */




// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// -------------------------------
// 05.1 Handle redirects on the front-end
// -------------------------------
add_action('template_redirect', function() {
    $redirects = get_option('venture_redirects_list', []);

    if (!is_array($redirects)) return;

    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = trailingslashit($path);

    if (isset($redirects[$path])) {
        wp_redirect(home_url($redirects[$path]), 301);
        exit;
    }
});


// -------------------------------
// 05.2 Add dashboard widget
// -------------------------------
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'venture_redirects_widget',
        'Venture Redirects',
        'venture_redirects_widget_display'
    );
});

function venture_redirects_widget_display() {
    $redirects = get_option('venture_redirects_list', []);
    if (!is_array($redirects)) $redirects = [];

    // Handle form submission
    if (!empty($_POST['venture_redirects_nonce']) &&
        wp_verify_nonce($_POST['venture_redirects_nonce'], 'venture_redirects_save')) {

        $source = trailingslashit(sanitize_text_field($_POST['redirect_from']));
        $target = trailingslashit(sanitize_text_field($_POST['redirect_to']));

        if (!empty($source) && !empty($target)) {
            $redirects[$source] = $target;
            update_option('venture_redirects_list', $redirects);
            echo '<div class="updated"><p>Redirect added successfully!</p></div>';
        }
    }

    // Handle deletion
    if (isset($_GET['delete_redirect'])) {
        $delete = sanitize_text_field($_GET['delete_redirect']);
        unset($redirects[$delete]);
        update_option('venture_redirects_list', $redirects);
        echo '<div class="updated"><p>Redirect deleted.</p></div>';
    }

    // Display form
    ?>
    <form method="post">
        <?php wp_nonce_field('venture_redirects_save', 'venture_redirects_nonce'); ?>
        <p>
            <label><strong>From:</strong> (e.g. /old-page/)</label><br>
            <input type="text" name="redirect_from" style="width:100%;" required>
        </p>
        <p>
            <label><strong>To:</strong> (e.g. /2025/old-page/)</label><br>
            <input type="text" name="redirect_to" style="width:100%;" required>
        </p>
        <p><button type="submit" class="button button-primary">Add Redirect</button></p>
    </form>

    <hr>
    <h3>Existing Redirects</h3>
    <table class="widefat striped">
        <thead><tr><th>From</th><th>To</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($redirects as $from => $to): ?>
            <tr>
                <td><?php echo esc_html($from); ?></td>
                <td><?php echo esc_html($to); ?></td>
                <td><a href="?delete_redirect=<?php echo urlencode($from); ?>" class="button">Delete</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
