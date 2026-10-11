<?php
/**
 * Solution (industry) page (§10.4): industry pains, which services apply and
 * why (in the editor's order), proof from a linked case study in that
 * industry — or an honest "what we'd do first" when there is none (never an
 * invented one) — compliance and channel notes, industry FAQ, CTA.
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
			'parentLabel'    => __( 'Services', 'techdella' ),
			'parentUrl'      => '/services/',
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

	// 2. Services that apply, and why — in the industry-specific order the editor set.
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
				'label'   => __( 'How we help', 'techdella' ),
				/* translators: %s: industry */
				'title'   => sprintf( __( 'The services that move %s *forward*', 'techdella' ), $td_industry ),
				'intro'   => __( 'In the order we usually run them for teams like yours.', 'techdella' ),
				'style'   => 'numbered',
				'surface' => 'canvas',
				'columns' => min( 3, count( $td_services ) ),
				'items'   => $td_services,
			]
		);
		echo '</div>';
	}

	// 3. Proof: linked case studies (permission-confirmed only), else the honest first steps.
	$td_proof = array_values(
		array_filter(
			td_m2_ids( td_field( 'proof', $td_id ) ),
			static fn( $pid ) => 'publish' === get_post_status( $pid ) && td_field( 'permission_confirmed', $pid )
		)
	);
	?>
	<section class="td-section" aria-labelledby="td-m2-proof-h">
		<div class="td-container">
			<div class="td-m2-section-head">
				<?php echo td_chip( __( 'Proof', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $td_proof ) : ?>
					<h2 class="td-m2-h2" id="td-m2-proof-h"><?php echo td_accent( sprintf( /* translators: %s: industry */ __( 'Work with %s *teams*', 'techdella' ), $td_industry ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<?php else : ?>
					<h2 class="td-m2-h2" id="td-m2-proof-h"><?php echo td_accent( __( 'What we’d do *first*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					<p><?php echo esc_html( sprintf( /* translators: %s: industry */ __( 'We don’t have a published %s case study yet, so here is the honest version: the first steps we’d take with you.', 'techdella' ), $td_industry ) ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $td_proof ) : ?>
				<ul class="td-m2-grid" role="list">
					<?php foreach ( $td_proof as $td_pid ) : ?>
						<li><?php echo td_block( 'case-card', [ 'postId' => (int) $td_pid ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<ol class="td-m2-checks">
					<li><?php esc_html_e( 'A growth audit: search, AI visibility, ads, funnel and tracking, with a prioritized plan.', 'techdella' ); ?></li>
					<?php foreach ( array_slice( $td_services, 0, 2 ) as $td_s ) : ?>
						<li><a class="td-link" href="<?php echo esc_url( $td_s['url'] ); ?>"><?php echo esc_html( $td_s['title'] ); ?></a><?php echo '' !== $td_s['text'] ? ': ' . td_mark_placeholders( esc_html( $td_s['text'] ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
					<?php endforeach; ?>
					<li><?php esc_html_e( 'Monthly reporting on rankings, traffic and the leads it brings.', 'techdella' ); ?></li>
				</ol>
			<?php endif; ?>
		</div>
	</section>
	<?php

	// 4. Compliance and channel quirks.
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

	if ( '' !== trim( (string) get_post_field( 'post_content', $td_id ) ) ) :
		?>
		<section class="td-section">
			<div class="td-container td-container--narrow td-prose"><?php the_content(); ?></div>
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
