<?php
/**
 * The Template for displaying all single products.
 *
 * Override this template by copying it to yourtheme/woocommerce/single-product.php
 *
 * @author: Leon de Klerk    
 */


get_header('shop'); ?>

<div class="custom-top-banner">
    <img src="https://www.venture.com.na/wp-content/uploads/2025/09/Sand-dunes-Sahara-desert.jpg.webp" alt="Decorative banner">
</div>

	<div class="row">

	<?php
		/**
		 * woocommerce_before_main_content hook
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20
		 */
		do_action('woocommerce_before_main_content');
	?>

		<?php while ( have_posts() ) : the_post(); ?>

			<?php wc_get_template_part( 'content', 'single-product' ); ?>

		<?php endwhile; // end of the loop. ?>

	<?php
		/**
		 * woocommerce_after_main_content hook
		 *
		 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
		 */
		do_action('woocommerce_after_main_content');
	?>

	<?php
		/**
		 * woocommerce_sidebar hook
		 *
		 * @hooked woocommerce_get_sidebar - 10
		 */
		do_action('woocommerce_sidebar');
	?>

</div>

<?php echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( 529 ); ?>

<div class="custom_product-page-spacer"></div>

<?php get_footer('shop'); ?>
