<?php
/**
 * Studios /work/ archive: every published studio_work item as a cover card
 * (Live items carry a Live dot, Concept items a visible Concept label).
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'blocks/case-card/lib.php' );

get_header();

echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'hero',
	[
		'parentLabel'  => __( 'Studios', 'techdella' ),
		'parentUrl'    => '/',
		'eyebrow'      => __( 'Work', 'techdella' ),
		'title'        => __( 'Selected *work*', 'techdella' ),
		'lead'         => __( 'Apps, automation, design and motion. Live projects are client work; projects labelled Concept show how we would approach a brief.', 'techdella' ),
		'primaryLabel' => __( 'Start a project', 'techdella' ),
		'primaryUrl'   => '/start/',
		'layout'       => 'center',
		'background'   => 'plain',
	]
);

$td_cards = [];
while ( have_posts() ) {
	the_post();
	$td_case = td_a3_case_from_post( (int) get_the_ID() );
	if ( $td_case ) {
		$td_cards[] = $td_case;
	}
}
?>
<section class="td-case-grid td-section td-case-grid--cover" style="padding-top:0" aria-label="<?php esc_attr_e( 'Projects', 'techdella' ); ?>">
	<div class="td-container">
		<?php if ( $td_cards ) : ?>
			<ul class="td-case-grid__list" role="list">
				<?php foreach ( $td_cards as $td_case ) : ?>
					<li><?php echo td_a3_case_card( $td_case, 'cover', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
				<?php endforeach; ?>
			</ul>
			<?php
			the_posts_pagination(
				[
					'mid_size'  => 1,
					'prev_text' => __( 'Newer', 'techdella' ),
					'next_text' => __( 'Older', 'techdella' ),
				]
			);
			?>
		<?php else : ?>
			<p class="td-lead"><?php esc_html_e( 'New projects are on their way.', 'techdella' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'cta-dark',
	[
		'title'       => __( 'Have an idea? Let’s make it *real*', 'techdella' ),
		'lead'        => __( 'Send a short brief. We’ll reply with a scope and an estimate.', 'techdella' ),
		'buttonLabel' => __( 'Start a project', 'techdella' ),
		'buttonUrl'   => '/start/',
		'variant'     => 'sand',
	]
);
get_footer();
