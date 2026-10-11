<?php
/**
 * Case study (§10.5): hero with metric pills, challenge → approach → results,
 * services used (linked), the verbatim quote (only with client permission),
 * gallery, video, next case study, CTA. Article (+ Review) schema comes from
 * the SEO module; the testimonial block adds Review only when shown here.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';
td_m2_styles();

get_header();
while ( have_posts() ) :
	the_post();
	$td_id         = (int) get_the_ID();
	$td_permission = (bool) td_field( 'permission_confirmed', $td_id );
	$td_client     = trim( (string) td_field( 'client', $td_id ) );
	$td_client     = '' !== $td_client ? $td_client : get_the_title();
	$td_timeframe  = trim( (string) td_field( 'timeframe', $td_id ) );
	$td_country    = trim( (string) td_field( 'country', $td_id ) );
	$td_terms      = taxonomy_exists( 'td_industry' ) ? get_the_terms( $td_id, 'td_industry' ) : false;
	$td_industry   = is_array( $td_terms ) && $td_terms ? $td_terms[0]->name : '';

	// Metrics: only shown once the client has confirmed them (permission_confirmed, §10.5).
	$td_stats = [];
	if ( $td_permission ) {
		for ( $td_i = 1; $td_i <= 3; $td_i++ ) {
			$td_num = trim( (string) td_field( "metric_{$td_i}_num", $td_id ) );
			if ( '' !== $td_num ) {
				$td_stats[] = [
					'value' => $td_num,
					'label' => (string) td_field( "metric_{$td_i}_label", $td_id ),
				];
			}
		}
	}

	$td_thumb = (int) get_post_thumbnail_id( $td_id );
	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'hero',
		[
			'parentLabel'    => __( 'Case studies', 'techdella' ),
			'parentUrl'      => '/case-studies/',
			'eyebrow'        => implode( ' · ', array_filter( [ $td_client, $td_industry ] ) ),
			'title'          => td_m2_accent( get_the_title(), $td_client ),
			'lead'           => td_m2_lead( $td_id ),
			'primaryLabel'   => __( 'Get a growth audit', 'techdella' ),
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => __( 'Read the story', 'techdella' ),
			'secondaryUrl'   => '#story',
			'image'          => $td_thumb ? $td_thumb : '',
			'imageAlt'       => $td_thumb ? (string) get_post_meta( $td_thumb, '_wp_attachment_image_alt', true ) : '',
			'background'     => 'plain',
		]
	);
	td_m2_breadcrumbs();

	if ( ! $td_permission ) :
		?>
		<div class="td-container">
			<p class="td-m2-note td-m2-note--banner" role="note">
				<strong><?php esc_html_e( 'Preview only.', 'techdella' ); ?></strong>
				<?php echo td_mark_placeholders( esc_html__( 'This case study cannot be published until the client approves the names, numbers and quote in writing (permission_confirmed). Metrics and the quote are hidden. [CONFIRM client permission]', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</p>
		</div>
		<?php
	endif;

	if ( $td_stats ) {
		echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'stat-pills',
			[
				'label' => __( 'Results', 'techdella' ),
				/* translators: %s: client name */
				'title' => sprintf( __( 'What changed for *%s*', 'techdella' ), $td_client ),
				'meta'  => implode( ' · ', array_filter( [ $td_timeframe, $td_country ] ) ),
				'stats' => $td_stats,
			]
		);
	}

	// Services used, linked.
	$td_services = array_filter( array_map( 'td_m2_service_link', td_m2_ids( td_field( 'services', $td_id ) ) ) );

	$td_story = array_filter(
		[
			[ __( 'The challenge', 'techdella' ), __( 'Where *%s* started', 'techdella' ), td_field( 'challenge', $td_id ) ],
			[ __( 'Our approach', 'techdella' ), __( 'What we *did*', 'techdella' ), td_field( 'approach', $td_id ) ],
			[ __( 'The results', 'techdella' ), __( 'What *changed*', 'techdella' ), td_field( 'results', $td_id ) ],
		],
		static fn( $row ) => '' !== trim( (string) $row[2] )
	);
	?>
	<section class="td-section" id="story" aria-label="<?php esc_attr_e( 'The story', 'techdella' ); ?>">
		<div class="td-container">
			<?php if ( $td_services ) : ?>
				<ul class="td-m2-services" role="list" style="margin-bottom:40px">
					<li class="td-m2-services__label"><?php esc_html_e( 'Services used', 'techdella' ); ?></li>
					<?php foreach ( $td_services as $td_s ) : ?>
						<li><a href="<?php echo esc_url( $td_s['url'] ); ?>"><?php echo esc_html( $td_s['title'] ); ?><?php echo td_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $td_story ) : ?>
				<ol class="td-m2-story" role="list">
					<?php foreach ( $td_story as $td_step ) : ?>
						<li class="td-m2-story__step">
							<div>
								<?php echo td_chip( $td_step[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<h2><?php echo td_accent( sprintf( $td_step[1], $td_client ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
							</div>
							<div class="td-m2-story__body"><?php echo td_m2_paras( $td_step[2] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<?php if ( '' !== trim( (string) get_post_field( 'post_content', $td_id ) ) ) : ?>
				<div class="td-prose td-container--narrow" style="margin:48px 0 0;padding:0">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php

	// Verbatim quote: only with permission (§2.4, §7). Review schema only because it's about this page.
	$td_quote = trim( (string) td_field( 'quote', $td_id ) );
	$td_by    = trim( (string) td_field( 'quote_author', $td_id ) );
	if ( $td_permission && '' !== $td_quote && '' !== $td_by ) :
		?>
		<section class="td-section td-section--canvas" aria-label="<?php esc_attr_e( 'Client quote', 'techdella' ); ?>">
			<div class="td-container">
				<?php
				echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'testimonial',
					[
						'quote'         => $td_quote,
						'name'          => $td_by,
						'role'          => (string) td_field( 'quote_role', $td_id ),
						'company'       => $td_client,
						'variant'       => 'arch',
						'aboutThisPage' => true,
					]
				);
				?>
			</div>
		</section>
		<?php
	endif;

	$td_gallery = td_m2_ids( td_field( 'gallery', $td_id ) );
	$td_video   = (int) td_field( 'video', $td_id );
	$td_video   = $td_video ? (string) wp_get_attachment_url( $td_video ) : '';
	if ( $td_gallery || '' !== $td_video ) :
		?>
		<section class="td-section" aria-labelledby="td-m2-gallery-h">
			<div class="td-container">
				<div class="td-m2-section-head">
					<?php echo td_chip( __( 'Gallery', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="td-m2-h2" id="td-m2-gallery-h"><?php echo td_accent( __( 'The *work*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				</div>
				<?php if ( $td_gallery ) : ?>
					<ul class="td-m2-gallery" role="list">
						<?php foreach ( $td_gallery as $td_img ) : ?>
							<li>
								<figure>
									<?php
									echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										$td_img,
										[
											'alt'   => (string) get_post_meta( $td_img, '_wp_attachment_image_alt', true ),
											'sizes' => '(max-width: 700px) 100vw, 400px',
										]
									);
									$td_cap = wp_get_attachment_caption( $td_img );
									if ( $td_cap ) {
										echo '<figcaption class="td-muted" style="padding:10px 14px;font-size:14px">' . esc_html( $td_cap ) . '</figcaption>';
									}
									?>
								</figure>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( '' !== $td_video ) : ?>
					<video class="td-m2-video" controls preload="none" playsinline src="<?php echo esc_url( $td_video ); ?>"></video>
				<?php endif; ?>
			</div>
		</section>
		<?php
	endif;

	// Next case study: the next permissioned one (menu order, then date), wrapping around.
	$td_all  = get_posts(
		[
			'post_type'      => 'case_study',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => [
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			],
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'   => 'permission_confirmed',
					'value' => '1',
				],
			],
		]
	);
	$td_pos  = array_search( $td_id, $td_all, true );
	$td_next = 0;
	if ( count( $td_all ) > 1 ) {
		$td_next = false === $td_pos ? (int) $td_all[0] : (int) $td_all[ ( $td_pos + 1 ) % count( $td_all ) ];
	}
	if ( $td_next && $td_next !== $td_id ) :
		?>
		<section class="td-section td-section--canvas" aria-labelledby="td-m2-next-h">
			<div class="td-container td-m2-next">
				<div>
					<?php echo td_chip( __( 'Next case study', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="td-m2-h2" id="td-m2-next-h"><?php echo td_accent( __( 'Keep *reading*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					<p style="margin-top:16px"><a class="td-link" href="<?php echo esc_url( (string) get_post_type_archive_link( 'case_study' ) ); ?>"><?php esc_html_e( 'All case studies', 'techdella' ); ?></a></p>
				</div>
				<?php echo td_block( 'case-card', [ 'postId' => $td_next ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>
		<?php
	endif;

	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'cta-dark',
		[
			'title'       => __( 'Want results like *these*?', 'techdella' ),
			'lead'        => __( 'Get an audit of your search, ads and funnel, and a clear plan for what to fix first.', 'techdella' ),
			'buttonLabel' => __( 'Get a growth audit', 'techdella' ),
			'buttonUrl'   => '/growth-audit/',
			'arch'        => true,
		]
	);
endwhile;
get_footer();
