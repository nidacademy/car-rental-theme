<?php
/**
 * Fallback template: posts, search results, archives and the "page not found" screen.
 * Pages built with Elementor use Elementor's own template.
 */
get_header();
?>
<section class="page-head">
	<div class="container">
		<h1>
			<?php
			if ( is_404() ) {
				echo 'Page not found';
			} elseif ( is_singular() ) {
				single_post_title();
			} elseif ( is_search() ) {
				echo esc_html( 'Search results: ' . get_search_query() );
			} else {
				the_archive_title();
			}
			?>
		</h1>
		<?php if ( is_404() ) : ?>
			<p>This page may have moved. Browse our cars or go back to the home page.</p>
		<?php endif; ?>
	</div>
</section>
<div class="entry">
	<div class="container">
		<?php if ( is_404() ) : ?>
			<a class="btn btn--primary" href="<?php echo esc_url( function_exists( 'orego_purl' ) ? orego_purl( 'cars' ) : home_url( '/' ) ); ?>">See our cars</a>
		<?php elseif ( is_singular() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<div class="entry-content"><?php the_content(); ?></div>
			<?php endwhile; ?>
		<?php elseif ( have_posts() ) : ?>
			<div class="entry-content">
				<?php while ( have_posts() ) : the_post(); ?>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p>Nothing found.</p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
