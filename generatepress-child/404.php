<?php
/**
 * The template for displaying 404 pages (not found)
 */

get_header(); ?>

<?php $uploads = wp_get_upload_dir(); ?>
<div class="custom-top-banner">
    <img src="<?php echo $uploads['baseurl'] . '/2025/09/Advertising-scaled.jpg'; ?>" alt="Decorative banner">
</div>

<main id="main" class="site-main">
    <section class="error-404 not-found">
        <h1>Well, that didn't work...</h1>
        <p>It looks like nothing was found here. Try one of these products or return to the <a href="<?php echo esc_url(home_url('/')); ?>">homepage</a>.</p>
		<div>
    		<?php echo do_shortcode('[elementor-template id="529"]'); ?>
		</div>
    </section>
</main>

<?php get_footer();
