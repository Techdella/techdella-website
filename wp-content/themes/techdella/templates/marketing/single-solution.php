<?php
/**
 * Industry page (§10.4), compact and visual (owner, 11 Oct 2026: "fewer words, more design
 * blocks, bento"): hero; an at-a-glance bento (challenges, outcomes, a verbatim quote and real
 * metrics where this industry has a case study); the services as a hub around the industry;
 * the keyword sections as tabs (full text stays in the page for search); the process; client
 * quotes and case studies in a scroller; the industry's rules as an accordion; the full overview
 * folded away; FAQ; CTA. Nothing is invented: metrics and quotes come from permissioned case
 * studies or CLAUDE.md §2.4.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';
td_m2_styles();

get_header();
while ( have_posts() ) :
	the_post();
	$td_id       = (int) get_the_ID();
	$td_terms    = taxonomy_exists( 'td_industry' ) ? get_the_terms( $td_id, 'td_industry' ) : false;
	$td_industry = is_array( $td_terms ) && $td_terms ? $td_terms[0]->name : trim( (string) preg_replace( '/\s*marketing agency$/i', '', get_the_title() ) );
	$td_first    = strtok( get_the_title(), ' ' );
	// First sentence only (fewer words on the page); trimmed only if one sentence runs long.
	$td_short = static function ( string $text, int $words ): string {
		$text  = trim( wp_strip_all_tags( $text ) );
		$first = preg_match( '/^(.+?[.!?])(\s|$)/u', $text, $m ) ? $m[1] : $text;
		return wp_trim_words( $first, $words, '…' );
	};

	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'hero',
		[
			'eyebrow'        => $td_industry,
			'title'          => td_m2_accent( get_the_title(), false !== $td_first ? $td_first : '' ),
			'lead'           => td_m2_lead( $td_id, 28 ),
			'primaryLabel'   => __( 'Get a growth audit', 'techdella' ),
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => __( 'See how we help', 'techdella' ),
			'secondaryUrl'   => '#glance',
			'layout'         => 'center',
			'background'     => 'grid',
		]
	);
	td_m2_breadcrumbs();

	// Case studies for this industry (permission-confirmed): metrics, quote and the scroller.
	$td_related = array_values(
		array_filter(
			td_m2_ids( td_field( 'proof', $td_id ) ),
			static fn( $pid ) => 'publish' === get_post_status( $pid ) && td_field( 'permission_confirmed', $pid )
		)
	);

	// 1. At a glance: challenges, outcomes, a quote and real numbers.
	$td_tiles = [];
	$td_tones = [ 'canvas', 'sand', 'canvas' ];
	foreach ( array_slice( td_m2_rows( td_field( 'pains', $td_id ) ), 0, 3 ) as $td_i => $td_row ) {
		$td_tiles[] = [
			'type'    => 'text',
			'span'    => 4,
			'variant' => $td_tones[ $td_i ],
			'value'   => sprintf( '%02d', $td_i + 1 ),
			'eyebrow' => __( 'Challenge', 'techdella' ),
			'title'   => (string) ( $td_row['title'] ?? '' ),
			'text'    => $td_short( (string) ( $td_row['text'] ?? '' ), 22 ),
		];
	}
	foreach ( array_slice( td_m2_rows( td_field( 'outcomes', $td_id ) ), 0, 4 ) as $td_row ) {
		$td_tiles[] = [
			'type'    => 'text',
			'span'    => 3,
			'variant' => 'paper',
			'eyebrow' => __( 'What changes', 'techdella' ),
			'title'   => (string) ( $td_row['title'] ?? '' ),
			'text'    => $td_short( (string) ( $td_row['text'] ?? '' ), 24 ),
		];
	}
	// A verbatim quote: this industry's case study first, else a §2.4 testimonial.
	$td_quote = null;
	foreach ( $td_related as $td_pid ) {
		$td_q = trim( (string) td_field( 'quote', $td_pid ) );
		if ( '' !== $td_q ) {
			$td_quote = [
				'quote' => $td_q,
				'name'  => (string) td_field( 'quote_author', $td_pid ),
				'role'  => (string) td_field( 'quote_role', $td_pid ),
			];
			break;
		}
	}
	if ( ! $td_quote ) {
		$td_any   = td_m2_quotes()[0] ?? null;
		$td_quote = $td_any ? [
			'quote' => $td_any['quote'],
			'name'  => $td_any['name'],
			'role'  => trim( $td_any['role'] . ', ' . $td_any['company'], ', ' ),
		] : null;
	}
	// Real metrics from this industry's first case study that has them.
	$td_stats = [];
	foreach ( $td_related as $td_pid ) {
		foreach ( [ 1, 2 ] as $td_n ) {
			$td_num = trim( (string) td_field( "metric_{$td_n}_num", $td_pid ) );
			if ( '' !== $td_num && ! str_contains( $td_num, '[' ) ) {
				$td_client  = trim( (string) td_field( 'client', $td_pid ) );
				$td_stats[] = [
					'type'    => 'stat',
					'span'    => 3,
					'variant' => 1 === $td_n ? 'sand' : 'canvas',
					'value'   => $td_num,
					'label'   => (string) td_field( "metric_{$td_n}_label", $td_pid ),
					'note'    => '' !== $td_client ? $td_client : get_the_title( $td_pid ),
					'url'     => (string) get_permalink( $td_pid ),
				];
			}
		}
		if ( $td_stats ) {
			break;
		}
	}
	if ( $td_quote ) {
		$td_tiles[] = array_merge(
			[
				'type'    => 'quote',
				'span'    => $td_stats ? 6 : 8,
				'variant' => 'dark',
			],
			$td_quote
		);
	}
	if ( $td_stats ) {
		$td_tiles = array_merge( $td_tiles, $td_stats );
	} else {
		$td_tiles[] = [
			'type'      => 'link',
			'span'      => $td_quote ? 4 : 12,
			'variant'   => 'sand',
			'chip'      => __( 'Start here', 'techdella' ),
			'title'     => __( 'A growth audit shows what to fix first', 'techdella' ),
			'linkLabel' => __( 'Get a growth audit', 'techdella' ),
			'url'       => '/growth-audit/',
		];
	}
	echo '<div id="glance">';
	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'bento-grid',
		[
			'label' => __( 'At a glance', 'techdella' ),
			/* translators: %s: industry */
			'title' => sprintf( __( '%s marketing at a *glance*', 'techdella' ), $td_industry ),
			'tiles' => $td_tiles,
		]
	);
	echo '</div>';

	// 2. Services as a hub around the industry, in the editor's order.
	$td_services = [];
	$td_spoke    = [ 'paper', 'sand', 'paper', 'dark', 'paper', 'sand' ];
	foreach ( td_m2_rows( td_field( 'services_used', $td_id ) ) as $td_i => $td_row ) {
		$td_link = td_m2_service_link( (int) ( td_m2_ids( $td_row['service'] ?? 0 )[0] ?? 0 ) );
		if ( $td_link ) {
			$td_services[] = [
				'title' => $td_link['title'],
				'text'  => '',
				'url'   => $td_link['url'],
				'tone'  => $td_spoke[ $td_i % 6 ],
			];
		}
	}
	if ( count( $td_services ) >= 3 ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'hub-spoke',
			[
				'label'       => __( 'Services', 'techdella' ),
				/* translators: %s: industry */
				'title'       => sprintf( __( 'One system for %s *growth*', 'techdella' ), $td_industry ),
				'intro'       => __( 'The services we combine for teams like yours, in the order we usually start them.', 'techdella' ),
				'centerLabel' => $td_industry,
				'items'       => $td_services,
			]
		);
	}

	// 3. Keyword sections as tabs: one tab per channel; the full answer stays in the page.
	$td_tabs = [];
	foreach ( td_m2_rows( td_field( 'keyword_sections', $td_id ) ) as $td_row ) {
		$td_head = trim( (string) ( $td_row['heading'] ?? '' ) );
		$td_text = trim( (string) ( $td_row['text'] ?? '' ) );
		if ( '' === $td_head || '' === $td_text ) {
			continue;
		}
		// "Fintech PPC and paid social" → tab "PPC and paid social".
		$td_tab    = trim( (string) preg_replace( '/^' . preg_quote( $td_industry, '/' ) . '\s+/i', '', $td_head ) );
		$td_tabs[] = [
			'label' => ucfirst( '' !== $td_tab ? $td_tab : $td_head ),
			'title' => $td_head,
			'text'  => $td_text,
		];
	}
	if ( $td_tabs ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'tabs',
			[
				'label'     => __( 'Channels', 'techdella' ),
				/* translators: %s: industry */
				'title'     => sprintf( __( '%s marketing, channel by *channel*', 'techdella' ), $td_industry ),
				/* translators: %s: industry */
				'ariaLabel' => sprintf( __( '%s marketing channels', 'techdella' ), $td_industry ),
				'items'     => $td_tabs,
			]
		);
	}

	// 4. Process.
	$td_steps = [];
	foreach ( td_m2_rows( td_field( 'process', $td_id ) ) as $td_row ) {
		$td_steps[] = [
			'title' => (string) ( $td_row['title'] ?? '' ),
			'text'  => $td_short( (string) ( $td_row['text'] ?? '' ), 18 ),
		];
	}
	if ( $td_steps ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'feature-grid',
			[
				'label'   => __( 'How we work', 'techdella' ),
				/* translators: %s: industry */
				'title'   => sprintf( __( 'Four steps, built for *%s*', 'techdella' ), $td_industry ),
				'style'   => 'numbered',
				'surface' => 'canvas',
				'columns' => min( 4, count( $td_steps ) ),
				'items'   => $td_steps,
			]
		);
	}

	// 5. Client voices: verbatim quotes and case studies (this industry's first), in a scroller.
	$td_others = get_posts(
		[
			'post_type'      => 'case_study',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'post__not_in'   => $td_related ? $td_related : [ 0 ],
		]
	);
	$td_cases  = array_slice(
		array_merge( $td_related, array_values( array_filter( $td_others, static fn( $pid ) => (bool) td_field( 'permission_confirmed', (int) $pid ) ) ) ),
		0,
		6
	);
	$td_quotes = td_m2_quotes();
	if ( $td_cases || $td_quotes ) :
		?>
		<section class="td-section" aria-labelledby="td-m2-voices-h">
			<div class="td-container">
				<div class="td-m2-section-head td-m2-section-head--row">
					<div>
						<?php echo td_chip( __( 'Clients', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<h2 class="td-m2-h2" id="td-m2-voices-h"><?php echo td_accent( __( 'What our clients *say*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					</div>
					<a class="td-link" href="<?php echo esc_url( home_url( '/case-studies/' ) ); ?>"><?php esc_html_e( 'All case studies', 'techdella' ); ?></a>
				</div>
				<ul class="td-m2-scroller" role="list" tabindex="0" aria-label="<?php esc_attr_e( 'Client quotes and case studies (scroll sideways)', 'techdella' ); ?>">
					<?php foreach ( $td_quotes as $td_q ) : ?>
						<li class="td-m2-scroller__item">
							<figure class="td-m2-quote">
								<blockquote><p><?php echo esc_html( $td_q['quote'] ); ?></p></blockquote>
								<figcaption><strong><?php echo esc_html( $td_q['name'] ); ?></strong><span><?php echo esc_html( trim( $td_q['role'] . ', ' . $td_q['company'], ', ' ) ); ?></span></figcaption>
							</figure>
						</li>
					<?php endforeach; ?>
					<?php foreach ( $td_cases as $td_pid ) : ?>
						<li class="td-m2-scroller__item"><?php echo td_block( 'case-card', [ 'postId' => (int) $td_pid ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	endif;

	// 6. The industry's rules and channel quirks as an accordion ("Google Ads: …" → question).
	$td_rules = [];
	foreach ( (array) preg_split( '/\R/', (string) td_field( 'compliance_notes', $td_id ) ) as $td_line ) {
		$td_line = trim( (string) preg_replace( '/^[-*]\s+/', '', trim( (string) $td_line ) ) );
		if ( '' === $td_line ) {
			continue;
		}
		$td_parts   = preg_match( '/^([^:]{2,48}):\s+(.+)$/u', $td_line, $td_m ) ? [ $td_m[1], $td_m[2] ] : [ __( 'Also good to know', 'techdella' ), $td_line ];
		$td_rules[] = [
			'question' => $td_parts[0],
			'answer'   => ucfirst( $td_parts[1] ),
		];
	}
	if ( $td_rules ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'faq',
			[
				/* translators: %s: industry */
				'title'      => sprintf( __( 'The rules %s marketing *works within*', 'techdella' ), $td_industry ),
				'items'      => $td_rules,
				'background' => 'canvas',
			]
		);
	}

	// 7. The full overview (answer-first, with the case-study facts and links), folded away.
	if ( '' !== trim( (string) get_post_field( 'post_content', $td_id ) ) ) :
		?>
		<section class="td-section td-section--tight">
			<div class="td-container td-container--narrow">
				<details class="td-m2-more">
					<summary><?php echo esc_html( sprintf( /* translators: %s: industry */ __( 'More about %s marketing with TechDella', 'techdella' ), $td_industry ) ); ?></summary>
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
				/* translators: %s: industry */
				'title'      => sprintf( __( '%s marketing *questions*', 'techdella' ), $td_industry ),
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
