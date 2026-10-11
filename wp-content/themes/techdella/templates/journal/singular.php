<?php
/**
 * Journal pages (newsletter, write for us…): block-built content renders full-bleed
 * (each block brings its own container). The visible H1 comes from the hero block;
 * a visually hidden H1 is added only when the content has no H1 of its own.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$td_content = (string) get_post_field( 'post_content' );
	if ( str_contains( $td_content, '<!-- wp:techdella/' ) ) :
		$td_has_h1 = (bool) preg_match( '#<!-- wp:(techdella/(hero|hero-orbit|studio-hero|post-header)[ /]|heading \{[^}]*"level":1)#', $td_content );
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
				td_breadcrumbs();
			}
			?>
			<h1 class="td-h1"><?php the_title(); ?></h1>
		</header>
		<div class="td-container td-container--narrow td-prose" style="padding-bottom:var(--td-space)">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
