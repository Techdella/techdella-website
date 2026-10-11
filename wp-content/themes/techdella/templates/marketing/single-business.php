<?php
/**
 * Business library guide ("How to start a … business", /business/<slug>/): the Journal post layout —
 * post header, featured image, sticky table of contents, TL;DR, article blocks, a Studios/audit CTA,
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
					echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'inline-cta',
						[
							'title'       => __( 'Ready to *grow* it?', 'techdella' ),
							'text'        => __( 'TechDella Studios builds your site or MVP, and our marketing team gets you found and chosen.', 'techdella' ),
							'buttonLabel' => __( 'Start a project', 'techdella' ),
							'buttonUrl'   => 'studios:/start/',
						]
					);
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
			'title'    => __( 'More business *guides*', 'techdella' ),
			'postType' => 'business',
			'related'  => true,
			'count'    => 3,
			'surface'  => 'canvas',
		]
	);
endwhile;
get_footer();
