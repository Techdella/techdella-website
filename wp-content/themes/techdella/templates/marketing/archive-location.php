<?php
/**
 * Locations hub (/locations/): the markets we serve (§10.4), one card each.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';

if ( is_paged() ) {
	wp_safe_redirect( (string) get_post_type_archive_link( 'location' ), 301 );
	exit;
}
td_m2_styles();

$td_locations = get_posts(
	[
		'post_type'      => 'location',
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
		'eyebrow'        => __( 'Locations', 'techdella' ),
		'title'          => __( 'Growth marketing for the markets our clients *serve*', 'techdella' ),
		'lead'           => __( 'Our clients operate in Nigeria, the US, Mexico, South Africa and Lebanon. Each market page covers local channels, costs, working hours and the work we’ve done there.', 'techdella' ),
		'primaryLabel'   => __( 'Get a growth audit', 'techdella' ),
		'primaryUrl'     => '/growth-audit/',
		'secondaryLabel' => __( 'Book a discovery call', 'techdella' ),
		'secondaryUrl'   => '/book-a-discovery-call/',
		'layout'         => 'center',
	]
);
td_m2_breadcrumbs();
?>
<section class="td-section" aria-labelledby="td-m2-markets-h">
	<div class="td-container">
		<div class="td-m2-section-head">
			<?php echo td_chip( __( 'Markets', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<h2 class="td-m2-h2" id="td-m2-markets-h"><?php echo td_accent( __( 'Pick your *market*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
		</div>
		<?php if ( $td_locations ) : ?>
			<ul class="td-m2-grid" role="list">
				<?php foreach ( $td_locations as $td_i => $td_loc ) : ?>
					<li>
						<?php
						$td_meta = implode( ' · ', array_filter( [ (string) td_field( 'currency', $td_loc->ID ), (string) td_field( 'timezone', $td_loc->ID ) ] ) );
						echo td_m2_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							[
								'kicker' => (string) ( td_field( 'market', $td_loc->ID ) ?? '' ),
								'title'  => get_the_title( $td_loc ),
								'text'   => td_m2_lead( $td_loc, 22 ),
								'meta'   => $td_meta,
								'url'    => (string) get_permalink( $td_loc ),
								'tone'   => 0 === $td_i % 3 ? 'canvas' : 'paper',
							]
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="td-m2-note"><?php esc_html_e( 'Market pages are being rebuilt. Book a call to talk about your market.', 'techdella' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'cta-dark',
	[
		'title'       => __( 'Ready to *grow?*', 'techdella' ),
		'lead'        => __( 'Get an audit of your search, ads and funnel, and a clear plan for what to fix first.', 'techdella' ),
		'buttonLabel' => __( 'Get a growth audit', 'techdella' ),
		'buttonUrl'   => '/growth-audit/',
		'arch'        => true,
	]
);
get_footer();
