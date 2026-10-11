<?php
/**
 * Journal partial: the main query as post cards (paged home, author, search),
 * using the post-grid block's markup and card helper, then pagination.
 * Expects $td_grid_label (aria-label) in scope; optional $td_card_level (2|3).
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'blocks/post-card/lib.php' );

$td_cards = [];
while ( have_posts() ) {
	the_post();
	$td_card = td_a3_post_data( (int) get_the_ID() );
	if ( $td_card ) {
		$td_cards[] = $td_card;
	}
}
?>
<?php echo td_block_css( 'post-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<section class="td-post-grid td-post-grid--grid td-section" style="padding-top:0" aria-label="<?php echo esc_attr( (string) ( $td_grid_label ?? __( 'Posts', 'techdella' ) ) ); ?>">
	<div class="td-container td-post-grid__inner">
		<?php if ( $td_cards ) : ?>
			<ul class="td-post-grid__cards" role="list">
				<?php foreach ( $td_cards as $td_i => $td_card ) : ?>
					<li>
						<?php
						echo td_a3_post_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							$td_card,
							[
								'level' => (int) ( $td_card_level ?? 2 ),
								'tone'  => $td_i,
							]
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			the_posts_pagination(
				[
					'mid_size'  => 1,
					'prev_text' => __( 'Newer posts', 'techdella' ),
					'next_text' => __( 'Older posts', 'techdella' ),
				]
			);
			?>
		<?php else : ?>
			<p class="td-lead"><?php esc_html_e( 'Nothing here yet.', 'techdella' ); ?></p>
		<?php endif; ?>
	</div>
</section>
