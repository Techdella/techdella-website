<?php
/**
 * Resources hub (/resources/): free PDF guides (§13.2), one card each.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';

if ( is_paged() ) {
	wp_safe_redirect( (string) get_post_type_archive_link( 'td_resource' ), 301 );
	exit;
}
td_m2_styles();

$td_resources = get_posts(
	[
		'post_type'      => 'td_resource',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'no_found_rows'  => true,
		'orderby'        => [
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		],
	]
);

get_header();
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'hero',
	[
		'eyebrow'    => __( 'Resources', 'techdella' ),
		'title'      => __( 'Free guides you can *use*', 'techdella' ),
		'lead'       => __( 'Checklists, templates and playbooks from our work on search, paid, email and automation. Free PDFs, no fluff.', 'techdella' ),
		'layout'     => 'center',
		'background' => 'plain',
	]
);
td_m2_breadcrumbs();
?>
<section class="td-section" aria-labelledby="td-m2-res-h">
	<div class="td-container">
		<h2 class="td-sr" id="td-m2-res-h"><?php esc_html_e( 'All resources', 'techdella' ); ?></h2>
		<?php if ( $td_resources ) : ?>
			<ul class="td-m2-grid" role="list">
				<?php foreach ( $td_resources as $td_i => $td_res ) : ?>
					<?php $td_pages = (int) td_field( 'pages', $td_res->ID ); ?>
					<li>
						<?php
						echo td_m2_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							[
								'kicker' => __( 'Free PDF', 'techdella' ),
								'title'  => get_the_title( $td_res ),
								'text'   => td_m2_lead( $td_res, 24 ),
								/* translators: %d: page count */
								'meta'   => $td_pages ? sprintf( _n( '%d page', '%d pages', $td_pages, 'techdella' ), $td_pages ) : '',
								'url'    => (string) get_permalink( $td_res ),
								'tone'   => 0 === $td_i % 2 ? 'sand' : 'canvas',
							]
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="td-m2-note"><?php esc_html_e( 'Our first guides are on the way.', 'techdella' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<section class="td-section td-section--canvas" aria-labelledby="td-m2-more-h">
	<div class="td-container">
		<div class="td-m2-section-head">
			<?php echo td_chip( __( 'More free resources', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<h2 class="td-m2-h2" id="td-m2-more-h"><?php echo td_accent( __( 'Keep *learning*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
		</div>
		<ul class="td-m2-grid" role="list">
			<?php
			$td_more = [
				[ __( 'Journal', 'techdella' ), __( 'B2B marketing guides', 'techdella' ), __( 'In-depth guides on SEO, AI search, paid ads, lead generation, email and automation.', 'techdella' ), td_url( 'journal', '/' ) ],
				[ __( 'Glossary', 'techdella' ), __( 'Marketing glossary', 'techdella' ), __( 'Plain-English definitions of the terms you meet in search, ads, email and growth.', 'techdella' ), '/glossary/' ],
				[ __( 'Free tools', 'techdella' ), __( 'Free marketing tools', 'techdella' ), __( 'UTM builder, readability and keyword checks, subject line analyzer and more.', 'techdella' ), '/tools/' ],
				[ __( 'Business guides', 'techdella' ), __( 'How to start a business', 'techdella' ), __( 'Approvals, costs, pricing and first customers for founders in the US, UK and Nigeria.', 'techdella' ), '/business/' ],
			];
			foreach ( $td_more as $td_i => $td_m ) :
				?>
				<li>
					<?php
					echo td_m2_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						[
							'kicker' => $td_m[0],
							'title'  => $td_m[1],
							'text'   => $td_m[2],
							'url'    => $td_m[3],
							'tone'   => 'paper',
						]
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'cta-dark',
	[
		'title'       => __( 'Prefer a plan made for *you*?', 'techdella' ),
		'lead'        => __( 'Get an audit of your search, ads and funnel, and a clear plan for what to fix first.', 'techdella' ),
		'buttonLabel' => __( 'Get a growth audit', 'techdella' ),
		'buttonUrl'   => '/growth-audit/',
		'arch'        => true,
	]
);
get_footer();
