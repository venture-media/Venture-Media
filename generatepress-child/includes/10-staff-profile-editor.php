<?php
/**
 * ====================
 * ADMIN STAFF PROFILE EDITOR - Admins ONLY
 * ====================
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Add submenu page under Users (Admins only)
add_action('admin_menu', 'staff_admin_menu_page');
function staff_admin_menu_page() {
    // Only show the menu to Administrators
    if (current_user_can('administrator')) {
        add_submenu_page(
            'users.php',                    // parent slug
            'Staff Profiles',               // page title
            'Staff Profiles',               // menu title
            'administrator',                // capability → only default Admin role
            'staff-profiles',               // menu slug
            'staff_profiles_admin_page'     // callback
        );
    }
}

// 2. The admin page
function staff_profiles_admin_page() {
    // Extra security check - only Administrators allowed
    if (!current_user_can('administrator')) {
        wp_die('You do not have sufficient permissions to access this page. Only Administrators can edit staff profiles.');
    }

    $message = '';
    $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;

    // Handle form submission
    if (isset($_POST['staff_admin_nonce']) && wp_verify_nonce($_POST['staff_admin_nonce'], 'staff_admin_update')) {
        $user_id = absint($_POST['staff_user_id']);

        if ($user_id && get_userdata($user_id)) {
            // Save text fields
            update_user_meta($user_id, 'staff_title', sanitize_text_field($_POST['staff_title']));
            update_user_meta($user_id, 'staff_bio', sanitize_textarea_field($_POST['staff_bio']));
            update_user_meta($user_id, 'staff_mobile', sanitize_text_field($_POST['staff_mobile']));
            update_user_meta($user_id, 'staff_email', sanitize_email($_POST['staff_email']));

            // Image handling
            if (!function_exists('media_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }

            $image_fields = [
                'staff_img1' => 'Profile picture',
                'staff_img2' => 'Feature image (About us)',
                'staff_img3' => 'Feature image (About us)',
            ];

            foreach ($image_fields as $field => $label) {
                // Delete if requested
                if (isset($_POST['delete_' . $field]) && $_POST['delete_' . $field] == '1') {
                    delete_user_meta($user_id, $field);
                }

                // Upload new image
                if (!empty($_FILES[$field]['name'])) {
                    $img_id = media_handle_upload($field, 0);
                    if (is_wp_error($img_id)) {
                        $message .= '<p class="error">❌ Error uploading "' . esc_html($label) . '": ' . esc_html($img_id->get_error_message()) . '</p>';
                    } else {
                        update_user_meta($user_id, $field, $img_id);
                        $message .= '<p class="success">✅ "' . esc_html($label) . '" uploaded successfully.</p>';
                    }
                }
            }

            $message .= '<p class="success">✅ Staff profile for <strong>' . esc_html(get_userdata($user_id)->display_name) . '</strong> has been updated.</p>';
        } else {
            $message = '<p class="error">Invalid user selected.</p>';
        }
    }

    // Get list of staff users
    $staff_users = get_users([
        'role__in' => ['staff', 'shop_manager', 'administrator'],
        'orderby'  => 'display_name',
    ]);

    // Load data if a user is selected
    if ($user_id) {
        $current_user = get_userdata($user_id);
        $title   = get_user_meta($user_id, 'staff_title', true);
        $bio     = get_user_meta($user_id, 'staff_bio', true);
        $mobile  = get_user_meta($user_id, 'staff_mobile', true);
        $email   = get_user_meta($user_id, 'staff_email', true);
        $img1_id = get_user_meta($user_id, 'staff_img1', true);
        $img2_id = get_user_meta($user_id, 'staff_img2', true);
        $img3_id = get_user_meta($user_id, 'staff_img3', true);
    }
    ?>

    <div class="wrap">
        <h1>Staff Profiles <span style="font-size:0.8em; color:#666;">(Administrators only)</span></h1>

        <?php if ($message) echo $message; ?>

        <!-- User selector -->
        <form method="get" action="">
            <input type="hidden" name="page" value="staff-profiles">
            <label for="user_id"><strong>Select Staff Member to Edit:</strong></label><br><br>
            <select name="user_id" id="user_id" style="width:350px;" onchange="this.form.submit()">
                <option value="">— Choose a staff member —</option>
                <?php foreach ($staff_users as $u) : ?>
                    <option value="<?php echo esc_attr($u->ID); ?>" <?php selected($user_id, $u->ID); ?>>
                        <?php echo esc_html($u->display_name); ?> 
                        (<?php echo esc_html($u->user_email); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($user_id && isset($current_user)) : ?>
            <hr>
            <h2>Editing Profile: <?php echo esc_html($current_user->display_name); ?></h2>

            <form method="post" enctype="multipart/form-data" style="max-width:900px;">
                <?php wp_nonce_field('staff_admin_update', 'staff_admin_nonce'); ?>
                <input type="hidden" name="staff_user_id" value="<?php echo esc_attr($user_id); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="staff_title">Job Title</label></th>
                        <td><input type="text" name="staff_title" id="staff_title" value="<?php echo esc_attr($title); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="staff_bio">Biography</label></th>
                        <td><textarea name="staff_bio" id="staff_bio" rows="8" class="large-text"><?php echo esc_textarea($bio); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="staff_mobile">Mobile number</label></th>
                        <td><input type="text" name="staff_mobile" id="staff_mobile" value="<?php echo esc_attr($mobile); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="staff_email">Email address</label></th>
                        <td><input type="email" name="staff_email" id="staff_email" value="<?php echo esc_attr($email); ?>" class="regular-text"></td>
                    </tr>

                    <?php
                    $image_fields = [
                        'staff_img1' => 'Profile picture',
                        'staff_img2' => 'Feature image (About us) – desktop 16:9',
                        'staff_img3' => 'Feature image (About us) – mobile 1:1',
                    ];

                    foreach ($image_fields as $field => $label) :
                        $img_id  = get_user_meta($user_id, $field, true);
                        $img_url = $img_id ? wp_get_attachment_url($img_id) : '';
                    ?>
                        <tr>
                            <th><label><?php echo esc_html($label); ?></label></th>
                            <td>
                                <?php if ($img_url) : ?>
                                    <div style="margin-bottom: 15px;">
                                        <img src="<?php echo esc_url($img_url); ?>" 
                                             style="max-width:280px; border:1px solid #ddd; border-radius:4px;" 
                                             alt="<?php echo esc_attr($label); ?>">
                                        <br><br>
                                        <button type="submit" 
                                                name="delete_<?php echo esc_attr($field); ?>" 
                                                value="1"
                                                class="button button-small button-link-delete"
                                                onclick="return confirm('Are you sure you want to delete this image?');">
                                            🗑 Delete Image
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <input type="file" name="<?php echo esc_attr($field); ?>" accept="image/*">
                                <p class="description">Upload a new image (or leave empty to keep current)</p>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large">Save Changes</button>
                </p>
            </form>
        <?php endif; ?>
    </div>

    <style>
        .error { color: #d63638; background: #fce2e4; padding: 12px; border-left: 4px solid #d63638; }
        .success { color: #006400; background: #dff0d8; padding: 12px; border-left: 4px solid #006400; }
    </style>
    <?php
}
