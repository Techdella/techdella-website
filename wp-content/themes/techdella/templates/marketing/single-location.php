<?php
/**
 * Location (market) page (§10.4): market facts (currency, working hours),
 * local proof only from that market, local channels, address only when a real
 * one is set (nap), market FAQ, CTA. Service schema with areaServed comes from
 * the SEO module; no LocalBusiness without a real address.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';
td_m2_styles();

get_header();
while ( have_posts() ) :
	the_post();
	$td_id     = (int) get_the_ID();
	$td_market = trim( (string) td_field( 'market', $td_id ) );
	$td_market = '' !== $td_market ? $td_market : get_the_title();

	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'hero',
		[
			'parentLabel'    => __( 'Locations', 'techdella' ),
			'parentUrl'      => '/locations/',
			'eyebrow'        => $td_market,
			'title'          => td_m2_accent( get_the_title(), $td_market ),
			'lead'           => td_m2_lead( $td_id, 40 ),
			'primaryLabel'   => __( 'Get a growth audit', 'techdella' ),
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => __( 'Book a discovery call', 'techdella' ),
			'secondaryUrl'   => '/book-a-discovery-call/',
			'image'          => (int) get_post_thumbnail_id( $td_id ) ? (int) get_post_thumbnail_id( $td_id ) : '',
			'background'     => 'grid',
		]
	);
	td_m2_breadcrumbs();

	$td_facts = array_filter(
		[
			__( 'Market', 'techdella' )        => $td_market,
			__( 'Currency', 'techdella' )      => (string) td_field( 'currency', $td_id ),
			__( 'Working hours', 'techdella' ) => (string) td_field( 'timezone', $td_id ),
		],
		static fn( $v ) => '' !== trim( (string) $v )
	);
	?>
	<section class="td-section" aria-label="<?php esc_attr_e( 'Market facts', 'techdella' ); ?>" style="padding-bottom:0">
		<div class="td-container">
			<dl class="td-m2-facts">
				<?php foreach ( $td_facts as $td_k => $td_v ) : ?>
					<div style="padding:20px 20px 20px 0;border-bottom:1px solid var(--td-line)">
						<dt><?php echo esc_html( (string) $td_k ); ?></dt>
						<dd class="td-m2-facts__v" style="margin-left:0"><?php echo td_mark_placeholders( esc_html( (string) $td_v ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		</div>
	</section>
	<?php

	// Local proof: case studies (permissioned, from this market) and testimonials (permissioned).
	$td_cases = [];
	$td_quote = [];
	foreach ( td_m2_ids( td_field( 'local_proof', $td_id ) ) as $td_pid ) {
		if ( 'publish' !== get_post_status( $td_pid ) || ! td_field( 'permission_confirmed', $td_pid ) ) {
			continue;
		}
		if ( 'case_study' === get_post_type( $td_pid ) ) {
			$td_country = (string) td_field( 'country', $td_pid );
			// Only proof from this market (§10.4): the case study's country must match.
			if ( '' === $td_country || false !== stripos( $td_market, $td_country ) || false !== stripos( $td_country, $td_market ) || false !== stripos( get_the_title(), $td_country ) ) {
				$td_cases[] = $td_pid;
			}
		} elseif ( 'td_testimonial' === get_post_type( $td_pid ) && '' !== (string) td_field( 'quote', $td_pid ) ) {
			$td_quote[] = $td_pid;
		}
	}
	?>
	<section class="td-section" aria-labelledby="td-m2-proof-h">
		<div class="td-container">
			<div class="td-m2-section-head">
				<?php echo td_chip( __( 'Local proof', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<h2 class="td-m2-h2" id="td-m2-proof-h"><?php echo td_accent( sprintf( /* translators: %s: market */ __( 'Our work in *%s*', 'techdella' ), $td_market ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
			</div>
			<?php if ( $td_cases ) : ?>
				<ul class="td-m2-grid" role="list">
					<?php foreach ( $td_cases as $td_pid ) : ?>
						<li><?php echo td_block( 'case-card', [ 'postId' => (int) $td_pid ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="td-m2-note">
					<?php
					/* translators: %s: market */
					echo esc_html( sprintf( __( 'We’re adding %s case studies as clients approve them for publication.', 'techdella' ), $td_market ) );
					?>
					<a class="td-link" href="<?php echo esc_url( (string) get_post_type_archive_link( 'case_study' ) ); ?>"><?php esc_html_e( 'See all case studies', 'techdella' ); ?></a>
				</p>
			<?php endif; ?>
			<?php
			foreach ( $td_quote as $td_tid ) {
				echo '<div style="margin-top:32px">';
				echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'testimonial',
					[
						'quote'   => (string) td_field( 'quote', $td_tid ),
						'name'    => (string) td_field( 'name', $td_tid ),
						'role'    => (string) td_field( 'role', $td_tid ),
						'company' => (string) td_field( 'company', $td_tid ),
						'variant' => 'arch',
					]
				);
				echo '</div>';
			}
			?>
		</div>
	</section>
	<?php

	$td_channels = [];
	foreach ( td_m2_rows( td_field( 'local_channels', $td_id ) ) as $td_row ) {
		$td_channels[] = [
			'title' => (string) ( $td_row['channel'] ?? '' ),
			'text'  => (string) ( $td_row['note'] ?? '' ),
		];
	}
	if ( $td_channels ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'feature-grid',
			[
				'label'   => __( 'Channels', 'techdella' ),
				/* translators: %s: market */
				'title'   => sprintf( __( 'Where buyers in %s *look*', 'techdella' ), $td_market ),
				'style'   => 'rule',
				'surface' => 'canvas',
				'columns' => min( 3, count( $td_channels ) ),
				'items'   => $td_channels,
			]
		);
	}

	if ( '' !== trim( (string) get_post_field( 'post_content', $td_id ) ) ) :
		?>
		<section class="td-section">
			<div class="td-container td-container--narrow td-prose"><?php the_content(); ?></div>
		</section>
		<?php
	endif;

	// Address only when a real, staffed address exists (§8.2, §10.4).
	$td_nap = trim( (string) td_field( 'nap', $td_id ) );
	if ( '' !== $td_nap ) :
		?>
		<section class="td-section td-section--sand" aria-labelledby="td-m2-nap-h">
			<div class="td-container">
				<h2 class="td-m2-h2" id="td-m2-nap-h"><?php echo td_accent( sprintf( /* translators: %s: market */ __( 'Find us in *%s*', 'techdella' ), $td_market ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<address class="td-m2-address" style="margin-top:16px"><?php echo nl2br( td_mark_placeholders( esc_html( $td_nap ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></address>
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
				'title' => sprintf( __( 'Working with us from *%s*', 'techdella' ), $td_market ),
				'items' => $td_faq,
			]
		);
	}

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
endwhile;
get_footer();
