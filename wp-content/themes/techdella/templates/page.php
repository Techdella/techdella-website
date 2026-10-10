<?php
/**
 * Default page: title + block content.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	// Pages built from TechDella blocks/patterns are full-bleed: each block brings its own container.
	if ( str_contains( (string) get_post_field( 'post_content' ), '<!-- wp:techdella/' ) ) :
		?>
		<article <?php post_class( 'td-page--blocks' ); ?>>
			<h1 class="td-sr"><?php the_title(); ?></h1>
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
