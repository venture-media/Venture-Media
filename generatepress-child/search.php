<?php
/**
 * Search Results Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>

<?php $uploads = wp_get_upload_dir(); ?>
<div class="custom-top-banner">
    <img src="<?php echo $uploads['baseurl'] . '/2025/09/Advertising-scaled.jpg'; ?>" alt="Decorative banner">
</div>

<div>
    <?php echo do_shortcode('[elementor-template id="529"]'); ?>
</div>

<?php get_footer(); ?>
