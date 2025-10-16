<?php
/**
 * -----------------------------
 * 05 Noindex tags
 * -----------------------------
 */


// Add "No index" checkbox to Quick Edit
add_action('quick_edit_custom_box', function($column_name, $post_type) {
    if ($column_name !== 'title') return; // attach to Title column for all post types
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label>
                <input type="checkbox" name="noindex" value="1">
                <?php _e('No index', 'your-textdomain'); ?>
            </label>
        </div>
    </fieldset>
    <?php
}, 10, 2);

// Save the checkbox value
add_action('save_post', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    update_post_meta($post_id, '_noindex', isset($_POST['noindex']) ? 1 : 0);
});

// Add a data attribute to each post row for JS prefill
add_filter('post_class', function($classes, $class, $post_id) {
    $noindex = get_post_meta($post_id, '_noindex', true) ? '1' : '0';
    echo ' data-noindex="' . esc_attr($noindex) . '"';
    return $classes;
}, 10, 3);

// Output <meta name="robots" content="noindex"> on the front end
add_action('wp_head', function() {
    if (is_singular() && get_post_meta(get_the_ID(), '_noindex', true)) {
        echo '<meta name="robots" content="noindex">' . "\n";
    }
});
