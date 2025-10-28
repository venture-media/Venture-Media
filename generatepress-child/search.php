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

<main id="primary" class="venture-search-page" <?php generate_do_element_classes( 'main' ); ?>">
	<div class="inside-article">
		<header class="page-header">
			<h1 class="page-title">
				<?php printf( esc_html__( 'Search Results for: %s', 'generatepress' ), '<span>' . get_search_query() . '</span>' ); ?>
			</h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="search-results">
				<?php while ( have_posts() ) : the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<div class="entry-summary">
							<?php the_excerpt(); ?>
						</div>
					</article>
				<?php endwhile; ?>

				<?php generate_do_pagination(); ?>
			</div>
		<?php else : ?>
			<div class="no-results not-found">
				<h2><?php esc_html_e( 'Nothing Found', 'generatepress' ); ?></h2>
				<p><?php esc_html_e( 'Sorry, but nothing matched your search terms. Please try again.', 'generatepress' ); ?></p>
				<?php get_search_form(); ?>
			</div>
		<?php endif; ?>
	</div>
</main>

<?php get_footer(); ?>
