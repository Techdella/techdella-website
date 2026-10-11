<?php
/**
 * Default single view for pages and custom post types: full-bleed when the content is built
 * from TechDella blocks (each block brings its own container), otherwise a titled prose column.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	// Pages built from TechDella blocks/patterns are full-bleed: each block brings its own container.
	$td_raw = (string) get_post_field( 'post_content' );
	if ( str_contains( $td_raw, '<!-- wp:techdella/' ) ) :
		// Hero-type blocks render the page H1 themselves; keep exactly one H1 (§8.5).
		$td_has_h1 = (bool) preg_match( '#<!-- wp:techdella/(?:hero|hero-orbit|studio-hero|post-header)[\s/]#', $td_raw );
		?>
		<article <?php post_class( 'td-page--blocks' ); ?>>
			<?php if ( ! $td_has_h1 ) : ?>
				<h1 class="td-sr"><?php the_title(); ?></h1>
			<?php endif; ?>
			<?php the_content(); ?>
		</article>
		<?php
		continue;
	endif;
	?>
	<article <?php post_class(); ?>>
		<header class="td-container td-container--narrow td-section" style="padding-bottom:32px">
			<?php
			if ( function_exists( 'td_breadcrumbs' ) ) {
				td_breadcrumbs(); }
			?>
			<h1><?php the_title(); ?></h1>
		</header>
		<div class="td-container td-container--narrow td-prose" style="padding-bottom:var(--td-space)">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
