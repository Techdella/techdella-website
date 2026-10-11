<?php
/**
 * Location (market) page (§10.4), compact and visual (owner, 11 Oct 2026): hero; an
 * at-a-glance bento (currency, working hours on your clock, how to start, local proof only from
 * this market); local channels as tabs; the services as a hub around the market; local case
 * studies when they exist; the address only when a real one is set (nap); the full overview
 * folded away; market FAQ; CTA. Service schema with areaServed comes from the SEO module; no
 * LocalBusiness without a real address. No hub page: the nav lists the markets.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';
require_once get_theme_file_path( 'parts/nav-common.php' );
td_m2_styles();

get_header();
while ( have_posts() ) :
	the_post();
	$td_id     = (int) get_the_ID();
	$td_market = trim( (string) td_field( 'market', $td_id ) );
	$td_market = '' !== $td_market ? $td_market : get_the_title();
	$td_first  = static function ( string $text, int $words = 24 ): string {
		$text  = trim( wp_strip_all_tags( $text ) );
		$first = preg_match( '/^(.+?[.!?])(\s|$)/u', $text, $m ) ? $m[1] : $text;
		return wp_trim_words( $first, $words, '…' );
	};

	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'hero',
		[
			'eyebrow'        => $td_market,
			'title'          => td_m2_accent( get_the_title(), $td_market ),
			'lead'           => td_m2_lead( $td_id, 30 ),
			'primaryLabel'   => __( 'Get a growth audit', 'techdella' ),
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => __( 'Book a discovery call', 'techdella' ),
			'secondaryUrl'   => '/book-a-discovery-call/',
			'layout'         => 'center',
			'background'     => 'grid',
		]
	);
	td_m2_breadcrumbs();

	// Local proof: permissioned case studies from this market only (§10.4).
	$td_cases = [];
	foreach ( td_m2_ids( td_field( 'local_proof', $td_id ) ) as $td_pid ) {
		if ( 'publish' !== get_post_status( $td_pid ) || ! td_field( 'permission_confirmed', $td_pid ) || 'case_study' !== get_post_type( $td_pid ) ) {
			continue;
		}
		$td_country = (string) td_field( 'country', $td_pid );
		if ( '' === $td_country || false !== stripos( $td_market, $td_country ) || false !== stripos( $td_country, $td_market ) || false !== stripos( get_the_title(), $td_country ) ) {
			$td_cases[] = (int) $td_pid;
		}
	}

	// 1. At a glance.
	$td_tz       = trim( (string) td_field( 'timezone', $td_id ) );
	$td_hours    = preg_match( '/working hours:\s*([^,.]+?WAT)/i', $td_tz, $td_m ) ? $td_m[1] : __( '9am–6pm WAT', 'techdella' );
	$td_hours_tx = preg_match( '/WAT,\s*(which .+?[.])(\s|$)/u', $td_tz, $td_m ) ? ucfirst( $td_m[1] ) : $td_first( $td_tz, 20 );
	$td_currency = trim( (string) td_field( 'currency', $td_id ) );
	$td_tiles    = [
		[
			'type'    => 'text',
			'span'    => 4,
			'variant' => 'canvas',
			'eyebrow' => __( 'Working hours', 'techdella' ),
			'title'   => $td_hours,
			'text'    => $td_hours_tx,
		],
		[
			'type'    => 'text',
			'span'    => 4,
			'variant' => 'sand',
			'eyebrow' => __( 'Quoted and invoiced in', 'techdella' ),
			'title'   => '' !== $td_currency ? $td_currency : __( 'US dollars', 'techdella' ),
			'text'    => __( 'Prices are agreed in writing before any work starts.', 'techdella' ),
		],
		[
			'type'    => 'text',
			'span'    => 4,
			'variant' => 'dark',
			'eyebrow' => __( 'Start with', 'techdella' ),
			'title'   => __( 'A growth audit', 'techdella' ),
			'text'    => __( 'From {{price:growth_audit}}: search, ads, funnel and tracking reviewed, with a ranked plan.', 'techdella' ),
		],
	];
	foreach ( array_slice( $td_cases, 0, 1 ) as $td_pid ) {
		$td_client = trim( (string) td_field( 'client', $td_pid ) );
		foreach ( [ 1, 2, 3 ] as $td_n ) {
			$td_num = trim( (string) td_field( "metric_{$td_n}_num", $td_pid ) );
			if ( '' !== $td_num && ! str_contains( $td_num, '[' ) ) {
				$td_tiles[] = [
					'type'    => 'stat',
					'span'    => 4,
					'variant' => 'paper',
					'value'   => $td_num,
					'label'   => (string) td_field( "metric_{$td_n}_label", $td_pid ),
					'note'    => '' !== $td_client ? $td_client : get_the_title( $td_pid ),
					'url'     => (string) get_permalink( $td_pid ),
				];
			}
		}
	}
	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'bento-grid',
		[
			'label' => __( 'At a glance', 'techdella' ),
			/* translators: %s: market */
			'title' => sprintf( __( 'Working with us from *%s*', 'techdella' ), $td_market ),
			'tiles' => $td_tiles,
		]
	);

	// 2. Local channels as tabs.
	$td_tabs = [];
	foreach ( td_m2_rows( td_field( 'local_channels', $td_id ) ) as $td_row ) {
		$td_ch = trim( (string) ( $td_row['channel'] ?? '' ) );
		$td_nt = trim( (string) ( $td_row['note'] ?? '' ) );
		if ( '' !== $td_ch && '' !== $td_nt ) {
			$td_tabs[] = [
				'label' => $td_ch,
				/* translators: 1: channel, 2: market */
				'title' => sprintf( __( '%1$s in %2$s', 'techdella' ), $td_ch, $td_market ),
				'text'  => $td_nt,
			];
		}
	}
	if ( $td_tabs ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'tabs',
			[
				'label'     => __( 'Local channels', 'techdella' ),
				/* translators: %s: market */
				'title'     => sprintf( __( 'Where buyers in %s *decide*', 'techdella' ), $td_market ),
				/* translators: %s: market */
				'ariaLabel' => sprintf( __( 'Marketing channels in %s', 'techdella' ), $td_market ),
				'items'     => $td_tabs,
			]
		);
	}

	// 3. Services as a hub around the market.
	$td_spoke = [];
	foreach ( td_nav_services() as $td_i => $td_s ) {
		$td_spoke[] = [
			'title' => $td_s['label'],
			'url'   => $td_s['url'],
			'tone'  => 3 === $td_i ? 'dark' : ( 1 === $td_i % 2 ? 'sand' : 'paper' ),
		];
	}
	if ( count( $td_spoke ) >= 3 ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'hub-spoke',
			[
				'label'       => __( 'Services', 'techdella' ),
				/* translators: %s: market */
				'title'       => sprintf( __( 'One growth system for *%s*', 'techdella' ), $td_market ),
				'intro'       => __( 'Search first, then paid, lead capture, email and automation, connected.', 'techdella' ),
				'centerLabel' => $td_market,
				'items'       => $td_spoke,
			]
		);
	}

	// 4. Local case studies (only from this market).
	if ( $td_cases ) :
		?>
		<section class="td-section" aria-labelledby="td-m2-proof-h">
			<div class="td-container">
				<div class="td-m2-section-head td-m2-section-head--row">
					<div>
						<?php echo td_chip( __( 'Local work', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<h2 class="td-m2-h2" id="td-m2-proof-h"><?php echo td_accent( sprintf( /* translators: %s: market */ __( 'Our work in *%s*', 'techdella' ), $td_market ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					</div>
					<a class="td-link" href="<?php echo esc_url( home_url( '/case-studies/' ) ); ?>"><?php esc_html_e( 'All case studies', 'techdella' ); ?></a>
				</div>
				<ul class="td-m2-grid" role="list">
					<?php foreach ( $td_cases as $td_pid ) : ?>
						<li><?php echo td_block( 'case-card', [ 'postId' => (int) $td_pid ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	endif;

	// 5. Address only when a real, staffed one is set (no LocalBusiness otherwise).
	$td_nap = trim( (string) td_field( 'nap', $td_id ) );
	if ( '' !== $td_nap ) :
		?>
		<section class="td-section td-section--sand" aria-labelledby="td-m2-nap-h">
			<div class="td-container td-container--narrow">
				<h2 class="td-m2-h2" id="td-m2-nap-h"><?php echo td_accent( sprintf( /* translators: %s: market */ __( 'Find us in *%s*', 'techdella' ), $td_market ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<address class="td-m2-address" style="margin-top:16px"><?php echo nl2br( td_mark_placeholders( esc_html( $td_nap ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></address>
			</div>
		</section>
		<?php
	endif;

	// 6. The full overview, folded away.
	if ( '' !== trim( (string) get_post_field( 'post_content', $td_id ) ) ) :
		?>
		<section class="td-section td-section--tight">
			<div class="td-container td-container--narrow">
				<details class="td-m2-more">
					<summary><?php echo esc_html( sprintf( /* translators: %s: market */ __( 'More about marketing in %s with TechDella', 'techdella' ), $td_market ) ); ?></summary>
					<div class="td-prose"><?php the_content(); ?></div>
				</details>
			</div>
		</section>
		<?php
	endif;

	$td_faq = td_m2_faq( td_field( 'faq', $td_id ) );
	if ( $td_faq ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'faq',
			[
				/* translators: %s: market */
				'title'      => sprintf( __( 'Marketing in %s: *questions*', 'techdella' ), $td_market ),
				'items'      => $td_faq,
				'background' => 'paper',
			]
		);
	}

	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'cta-dark',
		[
			'title'       => __( 'Find out what to fix *first*', 'techdella' ),
			'lead'        => __( 'Get an audit of your search, ads and funnel, and a clear plan your team can act on.', 'techdella' ),
			'buttonLabel' => __( 'Get a growth audit', 'techdella' ),
			'buttonUrl'   => '/growth-audit/',
			'arch'        => true,
		]
	);
endwhile;
get_footer();
