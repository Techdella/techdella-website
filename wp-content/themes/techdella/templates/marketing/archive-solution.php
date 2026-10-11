<?php
/**
 * Industries hub (/solution/): every industry page (§10.4), one card each, with the case
 * studies that prove it where we have them (never invented).
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';

if ( is_paged() ) {
	wp_safe_redirect( (string) get_post_type_archive_link( 'solution' ), 301 );
	exit;
}
td_m2_styles();

$td_solutions = get_posts(
	[
		'post_type'      => 'solution',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'no_found_rows'  => true,
		'orderby'        => [
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		],
	]
);

get_header();
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'hero',
	[
		'eyebrow'        => __( 'Industries', 'techdella' ),
		'title'          => __( 'Growth marketing built for your *industry*', 'techdella' ),
		'lead'           => __( 'Each industry has its own buyers, rules and channels. These pages cover what we see in yours, which services we start with, and the work that proves it.', 'techdella' ),
		'primaryLabel'   => __( 'Get a growth audit', 'techdella' ),
		'primaryUrl'     => '/growth-audit/',
		'secondaryLabel' => __( 'See case studies', 'techdella' ),
		'secondaryUrl'   => '/case-studies/',
		'layout'         => 'center',
	]
);
td_m2_breadcrumbs();
?>
<section class="td-section" aria-labelledby="td-m2-industries-h">
	<div class="td-container">
		<div class="td-m2-section-head">
			<?php echo td_chip( __( 'Industries', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<h2 class="td-m2-h2" id="td-m2-industries-h"><?php echo td_accent( __( 'Find your *industry*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
		</div>
		<?php if ( $td_solutions ) : ?>
			<ul class="td-m2-grid" role="list">
				<?php foreach ( $td_solutions as $td_i => $td_sol ) : ?>
					<li>
						<?php
						$td_proof = [];
						foreach ( (array) ( td_field( 'proof', $td_sol->ID ) ?? [] ) as $td_rel ) {
							$td_case = get_post( $td_rel instanceof WP_Post ? $td_rel->ID : (int) $td_rel );
							if ( $td_case instanceof WP_Post && 'publish' === $td_case->post_status ) {
								$td_client  = trim( (string) td_field( 'client', $td_case->ID ) );
								$td_proof[] = '' !== $td_client ? $td_client : get_the_title( $td_case );
							}
						}
						echo td_m2_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							[
								'kicker' => __( 'Industry', 'techdella' ),
								'title'  => get_the_title( $td_sol ),
								'text'   => td_m2_lead( $td_sol, 22 ),
								/* translators: %s: case study names */
								'meta'   => $td_proof ? sprintf( __( 'Proof: %s', 'techdella' ), implode( ', ', $td_proof ) ) : '',
								'url'    => (string) get_permalink( $td_sol ),
								'tone'   => 0 === $td_i % 3 ? 'canvas' : 'paper',
							]
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="td-m2-note"><?php esc_html_e( 'Industry pages are being rebuilt. Book a call to talk about your market.', 'techdella' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'cta-dark',
	[
		'title'       => __( 'Don’t see your *industry?*', 'techdella' ),
		'lead'        => __( 'We work with B2B companies across sectors. Get an audit and a clear plan for what to fix first.', 'techdella' ),
		'buttonLabel' => __( 'Get a growth audit', 'techdella' ),
		'buttonUrl'   => '/growth-audit/',
		'arch'        => true,
	]
);
get_footer();
