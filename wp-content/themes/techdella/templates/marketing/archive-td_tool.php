<?php
/**
 * Free tools hub (/tools/) (§12.1). One of the two places AdSense may load
 * (§12.2): a single in-content unit after the grid.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';

if ( is_paged() ) {
	wp_safe_redirect( (string) get_post_type_archive_link( 'td_tool' ), 301 );
	exit;
}
td_m2_styles();

$td_tools = get_posts(
	[
		'post_type'      => 'td_tool',
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
		'eyebrow'    => __( 'Free tools', 'techdella' ),
		'title'      => __( 'Free marketing tools that do one job *well*', 'techdella' ),
		'lead'       => __( 'Build UTM links, check readability, test subject lines and more. Free, no sign-up for most tools, and each one comes with a short guide on using the result.', 'techdella' ),
		'layout'     => 'center',
		'background' => 'plain',
	]
);
td_m2_breadcrumbs();
?>
<section class="td-section" aria-labelledby="td-m2-tools-h">
	<div class="td-container">
		<h2 class="td-sr" id="td-m2-tools-h"><?php esc_html_e( 'All tools', 'techdella' ); ?></h2>
		<?php if ( $td_tools ) : ?>
			<ul class="td-m2-grid" role="list">
				<?php foreach ( $td_tools as $td_i => $td_tool ) : ?>
					<li>
						<?php
						echo td_m2_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							[
								'kicker' => 'email' === td_field( 'gate', $td_tool->ID ) ? __( 'Free · results by email', 'techdella' ) : __( 'Free tool', 'techdella' ),
								'title'  => get_the_title( $td_tool ),
								'text'   => td_m2_lead( $td_tool, 22 ),
								'url'    => (string) get_permalink( $td_tool ),
								'tone'   => 0 === $td_i % 3 ? 'canvas' : 'paper',
							]
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="td-m2-note"><?php esc_html_e( 'Our tools are being rebuilt. Check back soon.', 'techdella' ); ?></p>
		<?php endif; ?>
		<div style="margin-top:48px"><?php echo td_block( 'ad-slot', [ 'position' => 'content' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	</div>
</section>
<?php
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'cta-dark',
	[
		'title'       => __( 'Want the full *picture*?', 'techdella' ),
		'lead'        => __( 'Tools answer one question. The Growth Audit reviews search, AI visibility, ads, funnel and tracking, with a prioritized plan.', 'techdella' ),
		'buttonLabel' => __( 'Get a growth audit', 'techdella' ),
		'buttonUrl'   => '/growth-audit/',
		'arch'        => true,
	]
);
get_footer();
