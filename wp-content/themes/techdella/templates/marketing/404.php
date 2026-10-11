<?php
/**
 * Marketing 404: helpful, not a dead end — search, the most useful links,
 * and the Growth Audit CTA. (404s are noindex by status code.)
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';
td_m2_styles();

$td_links = [
	[ __( 'Services', 'techdella' ), __( 'Search, paid, lead generation, email and AI automation.', 'techdella' ), '/services/' ],
	[ __( 'Case studies', 'techdella' ), __( 'What we did for clients, and what changed.', 'techdella' ), '/case-studies/' ],
	[ __( 'Free tools', 'techdella' ), __( 'UTM builder, readability checker and more.', 'techdella' ), '/tools/' ],
	[ __( 'Glossary', 'techdella' ), __( 'Marketing terms in plain English.', 'techdella' ), '/glossary/' ],
];

get_header();
?>
<section class="td-section td-m2-404" aria-labelledby="td-m2-404-h">
	<div class="td-container">
		<p class="td-m2-404__code" aria-hidden="true">404</p>
		<h1 id="td-m2-404-h" class="td-m2-h2" style="font-size:var(--td-h2);margin-top:12px"><?php echo td_accent( __( 'This page has *moved* or no longer exists', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
		<p class="td-lead" style="margin-top:16px;max-width:60ch;color:var(--td-body)"><?php esc_html_e( 'Try a search, or pick up from one of the pages below. Guides now live on the TechDella Journal.', 'techdella' ); ?></p>

		<form class="td-m2-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label for="td-m2-404-s"><?php esc_html_e( 'Search techdella.com', 'techdella' ); ?></label>
			<input type="search" id="td-m2-404-s" name="s" autocomplete="off" placeholder="<?php esc_attr_e( 'e.g. technical SEO', 'techdella' ); ?>">
			<button type="submit"><?php esc_html_e( 'Search', 'techdella' ); ?></button>
		</form>

		<h2 class="td-sr"><?php esc_html_e( 'Popular pages', 'techdella' ); ?></h2>
		<ul class="td-m2-grid" role="list" style="margin-top:48px">
			<?php foreach ( $td_links as $td_i => $td_l ) : ?>
				<li>
					<?php
					echo td_m2_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						[
							'title' => $td_l[0],
							'text'  => $td_l[1],
							'url'   => home_url( $td_l[2] ),
							'tone'  => 0 === $td_i ? 'canvas' : 'paper',
						],
						3
					);
					?>
				</li>
			<?php endforeach; ?>
			<li>
				<?php
				echo td_m2_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					[
						'title' => __( 'The Journal', 'techdella' ),
						'text'  => __( 'Guides on search, paid, email and automation.', 'techdella' ),
						'url'   => td_url( 'journal', '/' ),
						'tone'  => 'sand',
					],
					3
				);
				?>
			</li>
		</ul>
	</div>
</section>
<?php
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'cta-dark',
	[
		'title'       => __( 'Not sure where to *start?*', 'techdella' ),
		'lead'        => __( 'Get an audit of your search, ads and funnel, and a clear plan for what to fix first.', 'techdella' ),
		'buttonLabel' => __( 'Get an audit', 'techdella' ),
		'buttonUrl'   => '/growth-audit/',
		'arch'        => true,
	]
);
get_footer();
