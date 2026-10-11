<?php
/**
 * Required fallback. Real templates are in templates/ (see inc/templates.php).
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="td-container td-section">
	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'td-stack' ); ?>>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<div class="td-prose"><?php the_excerpt(); ?></div>
			</article>
		<?php endwhile; ?>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing here yet.', 'techdella' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
