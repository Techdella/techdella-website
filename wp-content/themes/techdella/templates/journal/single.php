<?php
/**
 * Journal post (prototype/Post.dc.html): post header (H1, byline), featured image,
 * sticky table of contents beside the article, TL;DR, content, service CTA,
 * author box (author, reviewer, last reviewed — §8.1), related posts.
 * Works with ordinary post content: blocks the post already contains
 * (tldr, author-box, post-cta) are not repeated.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<style>
		.td-article__figure{margin:0 auto 48px}
		.td-article__figure img{display:block;width:100%;height:auto;max-height:560px;object-fit:cover;border-radius:var(--td-r-lg)}
		.td-article__layout{display:grid;grid-template-columns:minmax(0,1fr);gap:32px;padding-bottom:var(--td-space)}
		.td-article__main{min-width:0;max-width:720px;display:flex;flex-direction:column;gap:32px}
		.td-article__main>*{margin-block:0}
		@media (min-width:1024px){.td-article__layout{grid-template-columns:230px minmax(0,1fr);gap:64px}}
	</style>
	<article <?php post_class( 'td-article' ); ?>>
		<?php echo td_block( 'post-header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="td-container td-article__figure">
				<?php
				echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					(int) get_post_thumbnail_id(),
					[
						'alt'      => (string) get_post_meta( (int) get_post_thumbnail_id(), '_wp_attachment_image_alt', true ),
						'sizes'    => '(max-width: 1240px) 100vw, 1240px',
						'priority' => true,
					]
				);
				?>
			</figure>
		<?php endif; ?>
		<div class="td-container td-article__layout">
			<aside class="td-article__aside" aria-label="<?php esc_attr_e( 'On this page', 'techdella' ); ?>">
				<?php echo td_block( 'toc', [ 'title' => __( 'On this page', 'techdella' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</aside>
			<div class="td-article__main">
				<?php
				if ( ! has_block( 'techdella/tldr' ) ) {
					echo td_block( 'tldr', [ 'label' => __( 'The short answer', 'techdella' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
				<div class="td-prose td-article__content">
					<?php the_content(); ?>
				</div>
				<?php
				if ( ! has_block( 'techdella/post-cta' ) ) {
					echo td_block( 'post-cta' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				if ( ! has_block( 'techdella/author-box' ) ) {
					echo td_block( 'author-box', [ 'showReviewer' => true ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div>
		</div>
	</article>
	<?php
	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'post-grid',
		[
			'title'   => __( 'Keep *reading*', 'techdella' ),
			'related' => true,
			'count'   => 3,
			'surface' => 'canvas',
		]
	);
endwhile;
get_footer();
