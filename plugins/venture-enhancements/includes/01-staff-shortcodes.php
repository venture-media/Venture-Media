<?php
/**
 * -----------------------------
 * 01 Staff Dashboard Shortcodes
 * -----------------------------
 */




// Shortcode: [staff_dashboard]
function staff_dashboard_shortcode() {
    $user_id = get_current_user_id();

    // Only staff OR admins can see this page
    if ( ! ( current_user_can('staff') || current_user_can('administrator') ) ) {
        return '<p>You do not have permission to access this page.</p>';
    }

    // Handle form submission
    if ( isset($_POST['staff_nonce']) && wp_verify_nonce($_POST['staff_nonce'], 'staff_update') ) {
        if ( ! function_exists( 'media_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        // Save job title
        update_user_meta($user_id, 'staff_title', sanitize_text_field($_POST['staff_title']));

        // Save bio
        update_user_meta($user_id, 'staff_bio', sanitize_textarea_field($_POST['staff_bio']));

        // Save mobile and email
        update_user_meta($user_id, 'staff_mobile', sanitize_text_field($_POST['staff_mobile']));
        update_user_meta($user_id, 'staff_email', sanitize_email($_POST['staff_email']));

        // Handle image uploads
        $image_fields = [
            'staff_img1' => 'Profile picture',
            'staff_img2' => 'Feature image (About us)',
            'staff_img3' => 'Your handwriting (About us)',
        ];

        foreach ($image_fields as $field => $label) {
            // Delete if requested
            if (isset($_POST['delete_'.$field]) && $_POST['delete_'.$field] == '1') {
                delete_user_meta($user_id, $field);
            }

               // Upload new
            if (!empty($_FILES[$field]['name'])) {
                $img_id = media_handle_upload($field, 0);
                if (is_wp_error($img_id)) {
                    echo '<p class="staff-error">❌ Error uploading "' . esc_html($label) . '": ' . esc_html($img_id->get_error_message()) . '</p>';
                } else {
                    update_user_meta($user_id, $field, $img_id);
                }
            }
        }

        echo '<p class="staff-success">✅ Your details have been updated.</p>';
    }

    // Get existing values
    $title = get_user_meta($user_id, 'staff_title', true);
    $bio   = get_user_meta($user_id, 'staff_bio', true);
    $mobile = get_user_meta($user_id, 'staff_mobile', true);
    $email  = get_user_meta($user_id, 'staff_email', true);
    $img1  = get_user_meta($user_id, 'staff_img1', true);
    $img2  = get_user_meta($user_id, 'staff_img2', true);
    $img3  = get_user_meta($user_id, 'staff_img3', true);

    ob_start(); ?>
    <form method="post" enctype="multipart/form-data" class="staff-profile-form">
        <label for="staff_title">Job Title</label>
        <input type="text" id="staff_title" name="staff_title" value="<?php echo esc_attr($title); ?>">

        <label for="staff_bio">Biography</label>
        <textarea id="staff_bio" name="staff_bio" rows="6"><?php echo esc_textarea($bio); ?></textarea>
        
        <label for="staff_mobile">Mobile number</label>
        <input type="text" id="staff_mobile" name="staff_mobile" value="<?php echo esc_attr($mobile); ?>">
        
        <label for="staff_email">Email address</label>
        <input type="email" id="staff_email" name="staff_email" value="<?php echo esc_attr($email); ?>">

        <?php
        $image_fields = [
            'staff_img1' => 'Profile picture',
            'staff_img2' => 'About us (desktop 16:9)',
            'staff_img3' => 'About us (mobile 1:1)',
        ];
        foreach ($image_fields as $field => $label):
            $img_id = get_user_meta($user_id, $field, true);
            $img_url = $img_id ? wp_get_attachment_url($img_id) : false;
        ?>
            <label for="<?php echo $field; ?>"><?php echo $label; ?></label>
            <?php if ($img_url): ?>
                <div class="staff-image-preview">
                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($label); ?>" style="max-width:150px;">
                    <button type="submit" name="delete_<?php echo $field; ?>" value="1" class="staff-delete-btn">🗑</button>
                </div>
                <input type="file" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="staff-file-input">
                <small>Change image</small>
            <?php else: ?>
                <input type="file" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="staff-file-input">
                <small>Upload image</small>
            <?php endif; ?>
        <?php endforeach; ?>

        <input type="hidden" name="staff_nonce" value="<?php echo wp_create_nonce('staff_update'); ?>">
        <button type="submit">Save Changes</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('staff_dashboard', 'staff_dashboard_shortcode');


// Shortcode: [staff_logout]
function staff_logout_shortcode() {
    if ( ! is_user_logged_in() ) {
        return ''; // hide for guests
    }

    $logout_url = wp_logout_url( home_url() ); // redirect to homepage after logout
    return '<a class="staff-profile-form-logout" href="' . esc_url( $logout_url ) . '">Log out</a>';
}
add_shortcode( 'staff_logout', 'staff_logout_shortcode' );


// Shortcode: [staff_name]
function staff_name_shortcode() {
    if ( ! is_user_logged_in() ) {
        return ''; // hide for guests
    }

    $user = wp_get_current_user();

    // Use display_name (can also use first_name or user_login if you prefer)
    return '<h2 class="staff-name">' . esc_html( $user->display_name ) . '</h2>';
}
add_shortcode('staff_name', 'staff_name_shortcode');


// Shortcode: [staff_image1]
function staff_image1_shortcode() {
    if ( ! is_user_logged_in() ) {
        return ''; // hide for guests
    }

    $user_id = get_current_user_id();
    $img1_id = get_user_meta( $user_id, 'staff_img1', true );
    $img1    = wp_get_attachment_url( $img1_id );

    if ( ! $img1 ) {
        return ''; // no image set
    }

    // Output container with image
    ob_start(); ?>
    <div class="staff-image1-container">
        <img src="<?php echo esc_url( $img1 ); ?>" alt="Staff Image 1">
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'staff_image1', 'staff_image1_shortcode' );


// Shortcode: [staff_image2]
function staff_image2_shortcode() {
    if ( ! is_user_logged_in() ) {
        return ''; // hide for guests
    }

    $user_id = get_current_user_id();
    $img2_id = get_user_meta( $user_id, 'staff_img2', true );
    $img2    = wp_get_attachment_url( $img2_id );

    if ( ! $img2 ) {
        return ''; // no image set
    }

    // Output container with image
    ob_start(); ?>
    <div class="staff-image2-container">
        <img src="<?php echo esc_url( $img2 ); ?>" alt="Staff Image 2">
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'staff_image2', 'staff_image2_shortcode' );


// Shortcode: [staff_image3]
function staff_image3_shortcode() {
    if ( ! is_user_logged_in() ) {
        return ''; // hide for guests
    }

    $user_id = get_current_user_id();
    $img3_id = get_user_meta( $user_id, 'staff_img3', true );
    $img3    = wp_get_attachment_url( $img3_id );

    if ( ! $img3 ) {
        return ''; // no image set
    }

    // Output container with image
    ob_start(); ?>
    <div class="staff-image3-container">
        <img src="<?php echo esc_url( $img3 ); ?>" alt="Staff Image 3">
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'staff_image3', 'staff_image3_shortcode' );



// Shortcode: [staff_images user="123"]
function staff_images_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'user' => 0, // default: none
    ), $atts, 'staff_images' );

    $user_id = intval( $atts['user'] );
    if ( ! $user_id ) {
        return ''; // no user specified
    }

    // Get img2 and img3
    $img2_id = get_user_meta( $user_id, 'staff_img2', true );
    $img3_id = get_user_meta( $user_id, 'staff_img3', true );

    $img2 = $img2_id ? wp_get_attachment_url( $img2_id ) : '';
    $img3 = $img3_id ? wp_get_attachment_url( $img3_id ) : '';

    if ( ! $img2 && ! $img3 ) {
        return ''; // no images set
    }

    ob_start(); ?>
    <div class="staff-images-container">
        <?php if ( $img2 ): ?>
            <div class="staff-image2-container">
                <img src="<?php echo esc_url( $img2 ); ?>" alt="Staff Image 2">
            </div>
        <?php endif; ?>

        <?php if ( $img3 ): ?>
            <div class="staff-image3-container">
                <img src="<?php echo esc_url( $img3 ); ?>" alt="Staff Image 3">
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'staff_images', 'staff_images_shortcode' );


// Shortcode: [staff_directory]
function staff_directory_shortcode() {
    // Only show for logged-in staff or admins if desired
    // remove this block if you want it public
    /*
    if ( ! ( current_user_can('staff') || current_user_can('administrator') ) ) {
        return '<p>You do not have permission to view this list.</p>';
    }
    */

    $args = array(
        'role__in' => array('staff', 'administrator'),
        'number'   => 9999, // adjust as needed
    );

    $users = get_users($args);

    if (empty($users)) {
        return '<p>No staff members found.</p>';
    }

    ob_start(); ?>

    <table class="staff-directory">
        <tbody>
            <?php foreach ($users as $user):
                $user_id = $user->ID;
                $title   = get_user_meta($user_id, 'staff_title', true);
                $mobile  = get_user_meta($user_id, 'staff_mobile', true);
                $email   = get_user_meta($user_id, 'staff_email', true);
                $img1_id = get_user_meta($user_id, 'staff_img1', true);
                $img1    = $img1_id ? wp_get_attachment_url($img1_id) : '';
                
                // skip empty users (no profile data)
                if (!$title && !$mobile && !$email && !$img1) continue;
            ?>
                <tr>
                    <td class="staff-directory-photo">
                        <?php if ($img1): ?>
                            <img src="<?php echo esc_url($img1); ?>" alt="<?php echo esc_attr($user->display_name); ?>" style="width:75px; height:75px; object-fit:cover;">
                        <?php endif; ?>
                    </td>
                    <td class="staff-directory-name"><?php echo esc_html($user->display_name); ?></td>
                    <td class="staff-directory-title"><?php echo esc_html($title); ?></td>
                    <td class="staff-directory-mobile"><?php echo esc_html($mobile); ?></td>
                    <td class="staff-directory-email"><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <style>
        .staff-directory {
            border-collapse: collapse;
            width: 100%;
            border: none;
        }
        .staff-directory tr {
            border: solid 1px var(--e-global-color-accent);
        }
        .staff-directory td {
            padding: 10px;
            border: none;
            vertical-align: middle;
        }
        .staff-directory-photo {
            width: 90px;
        }
    </style>

    <?php
    return ob_get_clean();
}
add_shortcode('staff_directory', 'staff_directory_shortcode');
