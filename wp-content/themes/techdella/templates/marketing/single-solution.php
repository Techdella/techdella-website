<?php
/**
 * Industry page (§10.4): answer-first intro, industry pains, how we help (outcomes), the
 * services in the editor's order, keyword sections (one H2 per search phrase), the process,
 * a scroller of verbatim client quotes and case studies (this industry's first; never invented),
 * compliance and channel notes, industry FAQ, CTA.
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

	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'hero',
		[
			'eyebrow'        => $td_industry,
			'title'          => td_m2_accent( get_the_title(), false !== $td_first ? $td_first : '' ),
			'lead'           => td_m2_lead( $td_id, 40 ),
			'primaryLabel'   => __( 'Get a growth audit', 'techdella' ),
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => __( 'How we help', 'techdella' ),
			'secondaryUrl'   => '#services',
			'image'          => (int) get_post_thumbnail_id( $td_id ) ? (int) get_post_thumbnail_id( $td_id ) : '',
			'background'     => 'grid',
		]
	);
	td_m2_breadcrumbs();

	// Answer-first intro (the body).
	if ( '' !== trim( (string) get_post_field( 'post_content', $td_id ) ) ) :
		?>
		<section class="td-section td-section--tight">
			<div class="td-container td-container--narrow td-prose td-m2-intro"><?php the_content(); ?></div>
		</section>
		<?php
	endif;

	// 1. Industry pains.
	$td_pains = [];
	foreach ( td_m2_rows( td_field( 'pains', $td_id ) ) as $td_row ) {
		$td_pains[] = [
			'title' => (string) ( $td_row['title'] ?? '' ),
			'text'  => (string) ( $td_row['text'] ?? '' ),
		];
	}
	if ( $td_pains ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'feature-grid',
			[
				'label'   => __( 'What we hear', 'techdella' ),
				/* translators: %s: industry */
				'title'   => sprintf( __( 'What slows %s teams *down*', 'techdella' ), $td_industry ),
				'style'   => 'rule',
				'columns' => min( 3, count( $td_pains ) ),
				'items'   => $td_pains,
			]
		);
	}

	// 2. How we help: what changes for the business.
	$td_outcomes = [];
	foreach ( td_m2_rows( td_field( 'outcomes', $td_id ) ) as $td_row ) {
		$td_outcomes[] = [
			'title' => (string) ( $td_row['title'] ?? '' ),
			'text'  => (string) ( $td_row['text'] ?? '' ),
		];
	}
	if ( $td_outcomes ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'feature-grid',
			[
				'label'   => __( 'How we help', 'techdella' ),
				/* translators: %s: industry */
				'title'   => sprintf( __( 'How we help %s businesses *grow*', 'techdella' ), $td_industry ),
				'style'   => 'checks',
				'surface' => 'canvas',
				'columns' => 2,
				'items'   => $td_outcomes,
			]
		);
	}

	// 3. Services that apply, and why — in the industry-specific order the editor set.
	$td_services = [];
	foreach ( td_m2_rows( td_field( 'services_used', $td_id ) ) as $td_row ) {
		$td_link = td_m2_service_link( (int) ( td_m2_ids( $td_row['service'] ?? 0 )[0] ?? 0 ) );
		if ( $td_link ) {
			$td_services[] = [
				'title' => $td_link['title'],
				'text'  => (string) ( $td_row['why'] ?? '' ),
				'url'   => $td_link['url'],
			];
		}
	}
	if ( $td_services ) {
		echo '<div id="services">';
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'feature-grid',
			[
				'label'   => __( 'Services', 'techdella' ),
				/* translators: %s: industry */
				'title'   => sprintf( __( 'The services that move %s *forward*', 'techdella' ), $td_industry ),
				'intro'   => __( 'In the order we usually run them for teams like yours.', 'techdella' ),
				'style'   => 'numbered',
				'columns' => min( 3, count( $td_services ) ),
				'items'   => $td_services,
			]
		);
		echo '</div>';
	}

	// 4. Keyword sections: one H2 per phrase buyers search, answered first.
	$td_kw = array_values(
		array_filter(
			td_m2_rows( td_field( 'keyword_sections', $td_id ) ),
			static fn( $r ) => '' !== trim( (string) ( $r['heading'] ?? '' ) ) && '' !== trim( (string) ( $r['text'] ?? '' ) )
		)
	);
	if ( $td_kw ) :
		?>
		<section class="td-section" aria-labelledby="td-m2-kw-h">
			<div class="td-container">
				<div class="td-m2-section-head">
					<?php echo td_chip( sprintf( /* translators: %s: industry */ __( '%s marketing', 'techdella' ), $td_industry ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<p class="td-m2-h2" id="td-m2-kw-h"><?php echo td_accent( sprintf( /* translators: %s: industry */ __( 'Every channel %s buyers *use*', 'techdella' ), $td_industry ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				</div>
				<div class="td-m2-kw">
					<?php foreach ( $td_kw as $td_row ) : ?>
						<article class="td-m2-kw__row">
							<h2 class="td-m2-kw__h"><?php echo esc_html( (string) $td_row['heading'] ); ?></h2>
							<p class="td-m2-kw__text"><?php echo td_lite_inline( (string) $td_row['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	endif;

	// 5. Process.
	$td_steps = [];
	foreach ( td_m2_rows( td_field( 'process', $td_id ) ) as $td_row ) {
		$td_steps[] = [
			'title' => (string) ( $td_row['title'] ?? '' ),
			'text'  => (string) ( $td_row['text'] ?? '' ),
		];
	}
	if ( $td_steps ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'feature-grid',
			[
				'label'   => __( 'How we work', 'techdella' ),
				/* translators: %s: industry */
				'title'   => sprintf( __( 'Our %s marketing *process*', 'techdella' ), $td_industry ),
				'style'   => 'numbered',
				'surface' => 'canvas',
				'columns' => min( 4, count( $td_steps ) ),
				'items'   => $td_steps,
			]
		);
	}

	// 6. Client voices: verbatim quotes and case studies (this industry's first), in a scroller.
	$td_related = array_values(
		array_filter(
			td_m2_ids( td_field( 'proof', $td_id ) ),
			static fn( $pid ) => 'publish' === get_post_status( $pid ) && td_field( 'permission_confirmed', $pid )
		)
	);
	$td_others  = get_posts(
		[
			'post_type'      => 'case_study',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'post__not_in'   => $td_related ? $td_related : [ 0 ],
		]
	);
	$td_cases   = array_slice(
		array_merge( $td_related, array_values( array_filter( $td_others, static fn( $pid ) => (bool) td_field( 'permission_confirmed', (int) $pid ) ) ) ),
		0,
		6
	);
	$td_quotes  = td_m2_quotes();
	if ( $td_cases || $td_quotes ) :
		?>
		<section class="td-section" aria-labelledby="td-m2-voices-h">
			<div class="td-container">
				<div class="td-m2-section-head">
					<?php echo td_chip( __( 'Clients', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="td-m2-h2" id="td-m2-voices-h"><?php echo td_accent( __( 'What our clients *say*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
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
				<p class="td-m2-scroller__more"><a class="td-link" href="<?php echo esc_url( home_url( '/case-studies/' ) ); ?>"><?php esc_html_e( 'All case studies', 'techdella' ); ?></a></p>
			</div>
		</section>
		<?php
	endif;

	// 7. Compliance and channel quirks.
	$td_notes = td_m2_paras( td_field( 'compliance_notes', $td_id ) );
	if ( '' !== $td_notes ) :
		?>
		<section class="td-section td-section--sand" aria-labelledby="td-m2-compliance-h">
			<div class="td-container td-m2-split">
				<div>
					<?php echo td_chip( __( 'Compliance & channels', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="td-m2-h2" id="td-m2-compliance-h"><?php echo td_accent( sprintf( /* translators: %s: industry */ __( 'Marketing %s within the *rules*', 'techdella' ), $td_industry ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				</div>
				<div class="td-m2-story__body"><?php echo $td_notes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
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
				'title' => sprintf( __( '%s marketing *questions*', 'techdella' ), $td_industry ),
				'items' => $td_faq,
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
