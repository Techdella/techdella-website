<?php
/**
 * Location (market) page (§10.4), rebuilt as a genuinely local page (owner, 11 Oct 2026: "finer,
 * fuller, more relevant"). Modelled on what strong agency location pages carry: a real office
 * (NAP + directions), sourced market numbers, services described for this market, local prices,
 * local channels, local proof only from this market, the team, areas served, a way to book, and a
 * market FAQ. Every section hides itself when its fields are empty, so nothing is padded. Only
 * markets with a real presence get a page; the rest redirect (data/redirects.csv).
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
	$td_ngn    = false !== stripos( (string) td_field( 'currency', $td_id ), 'NGN' );
	$td_nap    = trim( (string) td_field( 'nap', $td_id ) );
	$td_tz     = trim( (string) td_field( 'timezone', $td_id ) );
	$td_hours  = preg_match( '/working hours:\s*([^,.]+?WAT)/i', $td_tz, $td_m ) ? $td_m[1] : '';

	// Office card in the hero: only when a real address is set.
	$td_office = '';
	if ( '' !== $td_nap ) {
		$td_map    = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( (string) preg_replace( '/\s+/', ' ', $td_nap ) );
		$td_office = '<div class="td-loc-office">'
			. '<p class="td-loc-office__label">' . esc_html__( 'Our office', 'techdella' ) . '</p>'
			. '<address class="td-m2-address">' . nl2br( esc_html( $td_nap ) ) . '</address>'
			. ( '' !== $td_hours ? '<p class="td-loc-office__meta"><strong>' . esc_html__( 'Hours', 'techdella' ) . '</strong> ' . esc_html( $td_hours ) . ', ' . esc_html__( 'Monday to Friday', 'techdella' ) . '</p>' : '' )
			. '<p class="td-loc-office__meta"><strong>' . esc_html__( 'Prices in', 'techdella' ) . '</strong> ' . esc_html( (string) td_field( 'currency', $td_id ) ) . '</p>'
			. '<a class="td-link" href="' . esc_url( $td_map ) . '" rel="noopener" target="_blank">' . esc_html__( 'Get directions', 'techdella' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens Google Maps in a new tab)', 'techdella' ) . '</span></a>'
			. '</div>';
	}

	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'hero',
		[
			'eyebrow'        => '' !== $td_nap ? sprintf( /* translators: %s: market */ __( 'Based in %s', 'techdella' ), $td_market ) : $td_market,
			'title'          => td_m2_accent( get_the_title(), $td_market ),
			'lead'           => td_m2_lead( $td_id, 34 ),
			'primaryLabel'   => __( 'Get a growth audit', 'techdella' ),
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => __( 'Book a discovery call', 'techdella' ),
			'secondaryUrl'   => '#td-loc-book',
			'layout'         => '' !== $td_office ? 'split' : 'center',
			'background'     => 'grid',
		],
		$td_office
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

	// 1. The market in numbers: each one sourced and linked.
	$td_stats = [];
	foreach ( td_m2_rows( td_field( 'market_stats', $td_id ) ) as $td_row ) {
		if ( '' !== trim( (string) ( $td_row['value'] ?? '' ) ) && '' !== trim( (string) ( $td_row['source'] ?? '' ) ) ) {
			$td_stats[] = [
				'value'  => (string) $td_row['value'],
				'label'  => (string) ( $td_row['label'] ?? '' ),
				'source' => (string) $td_row['source'],
				'url'    => (string) ( $td_row['url'] ?? '' ),
			];
		}
	}
	if ( $td_stats ) :
		?>
		<section class="td-section td-section--tight" aria-labelledby="td-loc-stats-h">
			<div class="td-container">
				<div class="td-m2-section-head">
					<?php echo td_chip( __( 'The market', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="td-m2-h2" id="td-loc-stats-h"><?php echo td_accent( sprintf( /* translators: %s: market */ __( 'How buyers in %s *search*', 'techdella' ), $td_market ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				</div>
				<?php echo td_block( 'stats', [ 'items' => array_slice( $td_stats, 0, 4 ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>
		<?php
	endif;

	// 2. Services, described for this market.
	$td_services = [];
	$td_nav      = [];
	foreach ( td_nav_services() as $td_s ) {
		$td_nav[ basename( untrailingslashit( (string) wp_parse_url( $td_s['url'], PHP_URL_PATH ) ) ) ] = $td_s['url'];
	}
	foreach ( td_m2_rows( td_field( 'local_services', $td_id ) ) as $td_row ) {
		$td_title = trim( (string) ( $td_row['title'] ?? '' ) );
		if ( '' === $td_title ) {
			continue;
		}
		$td_slug       = trim( (string) ( $td_row['service'] ?? '' ) );
		$td_services[] = [
			'title' => $td_title,
			'text'  => (string) ( $td_row['text'] ?? '' ),
			'url'   => $td_nav[ $td_slug ] ?? ( '' !== $td_slug ? '/services/' . $td_slug . '/' : '' ),
		];
	}
	if ( $td_services ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'feature-grid',
			[
				'label'   => __( 'Services', 'techdella' ),
				/* translators: %s: market */
				'title'   => sprintf( __( 'What we run for companies in *%s*', 'techdella' ), $td_market ),
				'intro'   => __( 'Six services, one connected system. Search leads, the rest follows.', 'techdella' ),
				'style'   => 'numbered',
				'columns' => 3,
				'surface' => 'canvas',
				'items'   => $td_services,
			]
		);
	}

	// 3. Local prices (naira for Nigeria), straight from Growth → Prices.
	$td_prices = td_prices();
	$td_col    = $td_ngn ? 1 : 0;
	$td_cards  = [];
	foreach (
		[
			[ 'growth_audit', __( 'Start here', 'techdella' ), __( 'Growth Audit', 'techdella' ), __( 'Search, ads, website and follow-up reviewed, with a ranked plan in 10 business days.', 'techdella' ), '', '/growth-audit/', true ],
			[ 'growth_sprint', __( 'Fix the basics', 'techdella' ), __( 'Growth Sprint', 'techdella' ), __( 'The audit’s top fixes built and shipped: pages, tracking, forms and follow-up.', 'techdella' ), __( '/ month', 'techdella' ), '/pricing/', false ],
			[ 'growth_mgmt', __( 'Keep it growing', 'techdella' ), __( 'Ongoing growth', 'techdella' ), __( 'Search, ads, email and automation run each month, with a report you can act on.', 'techdella' ), __( '/ month', 'techdella' ), '/pricing/', false ],
		] as $td_c
	) {
		if ( isset( $td_prices[ $td_c[0] ] ) ) {
			$td_cards[] = [
				'kicker'      => $td_c[1],
				'title'       => $td_c[2],
				'text'        => $td_c[3],
				'price'       => 'growth_audit' === $td_c[0] ? $td_prices[ $td_c[0] ][ $td_col ] : sprintf( /* translators: 1: price, 2: period */ __( 'From %1$s %2$s', 'techdella' ), $td_prices[ $td_c[0] ][ $td_col ], $td_c[4] ),
				'buttonLabel' => 'growth_audit' === $td_c[0] ? __( 'Get the audit', 'techdella' ) : __( 'See what’s included', 'techdella' ),
				'buttonUrl'   => $td_c[5],
				'featured'    => $td_c[6],
			];
		}
	}
	if ( $td_cards ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'pricing-cards',
			[
				'label'      => __( 'Prices', 'techdella' ),
				'title'      => $td_ngn ? __( 'Priced in *naira*', 'techdella' ) : __( 'Ways to *start*', 'techdella' ),
				'intro'      => __( 'Agreed in writing before any work starts. Ad spend is paid to the platforms, not to us.', 'techdella' ),
				'cards'      => $td_cards,
				'background' => 'paper',
			]
		);
	}

	// 4. Local channels as tabs.
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

	// 5. Local work: case studies from this market, with their verbatim quotes.
	if ( $td_cases ) :
		$td_quotes = [];
		foreach ( $td_cases as $td_pid ) {
			$td_q = trim( (string) td_field( 'quote', $td_pid ) );
			if ( '' !== $td_q ) {
				$td_quotes[] = [
					'quote' => $td_q,
					'name'  => (string) td_field( 'quote_author', $td_pid ),
					'role'  => (string) td_field( 'quote_role', $td_pid ),
				];
				continue;
			}
			// Else this client's verbatim testimonial (§2.4), matched by company.
			$td_client = trim( (string) td_field( 'client', $td_pid ) );
			$td_client = '' !== $td_client ? $td_client : get_the_title( $td_pid );
			foreach ( td_m2_quotes() as $td_t ) {
				if ( '' !== $td_t['company'] && false !== stripos( $td_client, $td_t['company'] ) ) {
					$td_quotes[] = [
						'quote' => $td_t['quote'],
						'name'  => $td_t['name'],
						'role'  => trim( $td_t['role'] . ', ' . $td_t['company'], ', ' ),
					];
					break;
				}
			}
		}
		?>
		<section class="td-section td-section--sand" aria-labelledby="td-m2-proof-h">
			<div class="td-container">
				<div class="td-m2-section-head td-m2-section-head--row">
					<div>
						<?php echo td_chip( __( 'Local work', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<h2 class="td-m2-h2" id="td-m2-proof-h"><?php echo td_accent( sprintf( /* translators: %s: market */ __( 'Our work in *%s*', 'techdella' ), $td_market ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					</div>
					<a class="td-link" href="<?php echo esc_url( home_url( '/case-studies/' ) ); ?>"><?php esc_html_e( 'All case studies', 'techdella' ); ?></a>
				</div>
				<div class="td-loc-work">
					<ul class="td-loc-work__cards" role="list">
						<?php foreach ( $td_cases as $td_pid ) : ?>
							<li><?php echo td_block( 'case-card', [ 'postId' => (int) $td_pid ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
						<?php endforeach; ?>
					</ul>
					<?php foreach ( array_slice( $td_quotes, 0, 1 ) as $td_q ) : ?>
						<figure class="td-m2-quote td-loc-work__quote">
							<blockquote><p><?php echo esc_html( $td_q['quote'] ); ?></p></blockquote>
							<figcaption><strong><?php echo esc_html( $td_q['name'] ); ?></strong><span><?php echo esc_html( $td_q['role'] ); ?></span></figcaption>
						</figure>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	endif;

	// 6. The team (only for a market where we are actually based).
	if ( '' !== $td_nap ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'arch-people',
			[
				'label'   => __( 'The team', 'techdella' ),
				/* translators: %s: market */
				'title'   => sprintf( __( 'The people you’ll work with in *%s*', 'techdella' ), $td_market ),
				'intro'   => __( 'Founded by Joanna Okedara-Kalu. Strategy, design and delivery in one team, on your clock.', 'techdella' ),
				'source'  => 'inline',
				'surface' => 'canvas',
				'people'  => [
					[
						'name'  => 'Joanna Okedara-Kalu',
						'role'  => __( 'Founder & CEO', 'techdella' ),
						'image' => 'team/joanna-okedara-kalu',
					],
					[
						'name'  => 'Chinedu Emmanuel Kalu',
						'role'  => __( 'COO & Co-founder', 'techdella' ),
						'image' => 'team/chinedu-emmanuel-kalu',
					],
					[
						'name'  => 'Abel Saka',
						'role'  => __( 'Brand Designer & UI/UX', 'techdella' ),
						'image' => 'team/abel-saka',
					],
					[
						'name'  => 'Omolola Akiyode',
						'role'  => __( 'TechDella Team', 'techdella' ),
						'image' => 'team/omolola-akiyode',
					],
					[
						'name'  => 'Mercy Olumakinwa',
						'role'  => __( 'TechDella Team', 'techdella' ),
						'image' => 'team/mercy-olumakinwa',
					],
					[
						'name' => 'Olabimpe Salamin',
						'role' => __( 'TechDella Team', 'techdella' ),
					],
					[
						'name' => 'Oyinkansola Opatola',
						'role' => __( 'TechDella Team', 'techdella' ),
					],
				],
			]
		);
	}

	// 7. Areas served + where to find us.
	$td_areas = array_values( array_filter( array_map( static fn( $r ) => trim( (string) ( $r['area'] ?? '' ) ), td_m2_rows( td_field( 'areas', $td_id ) ) ) ) );
	if ( $td_areas || '' !== $td_nap ) :
		?>
		<section class="td-section" aria-labelledby="td-loc-areas-h">
			<div class="td-container td-loc-areas">
				<div>
					<?php echo td_chip( __( 'Areas we serve', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="td-m2-h2" id="td-loc-areas-h"><?php echo td_accent( sprintf( /* translators: %s: market */ __( 'Across *%s*, remotely', 'techdella' ), $td_market ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					<p class="td-m2-intro"><?php esc_html_e( 'Workshops, reviews and reporting happen on video calls in your working day, wherever your team sits.', 'techdella' ); ?></p>
					<?php if ( $td_areas ) : ?>
						<ul class="td-loc-areas__list" role="list">
							<?php foreach ( $td_areas as $td_area ) : ?>
								<li><?php echo esc_html( $td_area ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<?php if ( '' !== $td_office ) : ?>
					<div class="td-loc-areas__office"><?php echo $td_office; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	endif;

	// 8. Book a call, in place.
	?>
	<div id="td-loc-book">
		<?php
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'booking-embed',
			[
				'label'      => __( 'Talk to us', 'techdella' ),
				'title'      => __( 'Book a *discovery call*', 'techdella' ),
				'lead'       => '' !== $td_hours ? sprintf( /* translators: %s: hours */ __( '45 minutes on video, %s. Pick a time that suits you.', 'techdella' ), $td_hours ) : __( '45 minutes on video. Pick a time that suits you.', 'techdella' ),
				'src'        => 'https://app.startbuddi.com/b/startbuddi-team/discovery-call-with-techdella',
				'frameTitle' => __( 'Discovery Call with TechDella', 'techdella' ),
			]
		);
		?>
	</div>
	<?php

	// 9. The full overview, folded away.
	if ( '' !== trim( (string) get_post_field( 'post_content', $td_id ) ) ) :
		?>
		<section class="td-section td-section--tight">
			<div class="td-container td-container--narrow">
				<details class="td-m2-more" open>
					<summary><?php echo esc_html( sprintf( /* translators: %s: market */ __( 'Marketing in %s: the full guide', 'techdella' ), $td_market ) ); ?></summary>
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
