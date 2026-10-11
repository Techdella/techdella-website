<?php
/**
 * Free tool page (§12.1): the tool app mount, then real content (how to use,
 * why it matters + worked example from the page content, FAQ, related guide
 * and service). AdSense units in the three §12.2 positions: below the result,
 * in the content after "How to use", and a desktop-only sidebar unit. The
 * ads module decides whether they print (tool pages only, consent, ≤ 3).
 * SoftwareApplication schema comes from the SEO module.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';
td_m2_styles();

if ( ! function_exists( 'td_m2_tool_related' ) ) :
	/**
	 * Related service + Journal guide per tool app (§12.1 "related guide and service").
	 * Filter `td_tool_related` to change a mapping without editing the template.
	 *
	 * @param string $component tool_component value.
	 * @return array{service:array{0:string,1:string},guide:array{0:string,1:string}}
	 */
	function td_m2_tool_related( string $component ): array {
		$map = [
			'utm-parameter-generator'     => [ [ 'Paid ads', '/services/paid-ads/' ], [ 'Startup marketing playbook: 30 days to your first 100 customers', '/how-to-get-your-first-100-customers/' ] ],
			'hashtag-generator'           => [ [ 'Paid ads', '/services/paid-ads/' ], [ 'How to launch a new product', '/how-to-launch-a-new-product/' ] ],
			'readability-checker'         => [ [ 'SEO', '/services/seo/' ], [ 'Why most content marketing agencies fail startups', '/content-marketing-agency-for-startups/' ] ],
			'keyword-density-checker'     => [ [ 'SEO', '/services/seo/' ], [ 'Why most content marketing agencies fail startups', '/content-marketing-agency-for-startups/' ] ],
			'email-subject-line-analyzer' => [ [ 'Email marketing', '/services/email-marketing/' ], [ 'What no one tells you about email marketing tools', '/email-marketing-setup-problems/' ] ],
			'on-page-seo-checker'         => [ [ 'SEO', '/services/seo/' ], [ 'Why most content marketing agencies fail startups', '/content-marketing-agency-for-startups/' ] ],
			'persona-builder'             => [ [ 'Lead generation', '/services/lead-generation/' ], [ 'Startup marketing playbook: 30 days to your first 100 customers', '/how-to-get-your-first-100-customers/' ] ],
			'ai-business-name-generator'  => [ [ 'Branding (TechDella Studios)', 'studios:/services/branding/' ], [ 'How to brainstorm a startup name that converts', '/how-to-brainstorm-a-startup-name/' ] ],
		];
		$row = $map[ $component ] ?? [ [ 'SEO', '/services/seo/' ], [ 'The Journal', '/' ] ];
		return (array) apply_filters(
			'td_tool_related',
			[
				'service' => $row[0],
				'guide'   => $row[1],
			],
			$component
		);
	}
endif;

get_header();
while ( have_posts() ) :
	the_post();
	$td_id        = (int) get_the_ID();
	$td_component = sanitize_title( (string) td_field( 'tool_component', $td_id ) );
	$td_component = '' !== $td_component ? $td_component : preg_replace( '/^free-/', '', (string) get_post_field( 'post_name', $td_id ) );
	$td_gate      = 'email' === td_field( 'gate', $td_id );
	$td_related   = td_m2_tool_related( (string) $td_component );
	$td_svc_path  = (string) $td_related['service'][1];
	$td_svc_url   = str_starts_with( $td_svc_path, 'studios:' ) ? td_url( 'studios', substr( $td_svc_path, 8 ) ) : home_url( $td_svc_path );
	$td_guide_url = td_url( 'journal', (string) $td_related['guide'][1] );
	?>
	<article <?php post_class(); ?>>
		<header class="td-container td-m2-head td-m2-head--wide" style="padding-bottom:28px">
			<?php
			if ( function_exists( 'td_breadcrumbs' ) ) {
				td_breadcrumbs();
			}
			echo td_chip( __( 'Free tool', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<h1><?php echo td_accent( td_m2_accent( get_the_title() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
			<?php $td_lead = td_m2_lead( $td_id, 40 ); ?>
			<?php if ( has_excerpt() ) : ?>
				<p class="td-m2-head__lead"><?php echo td_mark_placeholders( esc_html( $td_lead ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
		</header>

		<div class="td-container td-m2-tool-layout">
			<div>
				<section class="td-m2-tool" data-td-tool="<?php echo esc_attr( (string) $td_component ); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
					<?php
					// The tool app block renders the form, the email gate (gate=email) and the results.
					if ( WP_Block_Type_Registry::get_instance()->is_registered( 'techdella/tool-' . $td_component ) ) {
						echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'tool-' . $td_component,
							[
								'gate' => $td_gate,
								'slug' => (string) get_post_field( 'post_name', $td_id ),
							]
						);
					} else {
						echo '<p class="td-m2-tool__placeholder">' . esc_html__( 'This tool is not available yet.', 'techdella' ) . '</p>';
					}
					?>
				</section>

				<?php // Result unit: ≥ 150px below the tool's buttons and inputs (§12.2). ?>
				<div class="td-m2-tool-result"><?php echo td_block( 'ad-slot', [ 'position' => 'result' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

				<div class="td-m2-tool-body">
					<?php
					$td_how = [];
					foreach ( td_m2_rows( td_field( 'how_to', $td_id ) ) as $td_row ) {
						$td_how[] = [
							'title' => (string) ( $td_row['title'] ?? '' ),
							'text'  => (string) ( $td_row['text'] ?? '' ),
						];
					}
					if ( $td_how ) {
						echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'feature-grid',
							[
								'label'   => __( 'How to use it', 'techdella' ),
								'title'   => __( 'How to use this *tool*', 'techdella' ),
								'style'   => 'numbered',
								'bare'    => true,
								'columns' => min( 3, count( $td_how ) ),
								'items'   => $td_how,
							]
						);
					}

					// In-content unit, after "How to use" (§12.2).
					echo td_block( 'ad-slot', [ 'position' => 'content' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>

					<?php if ( '' !== trim( (string) get_post_field( 'post_content', $td_id ) ) ) : ?>
						<div class="td-prose" style="max-width:760px"><?php the_content(); ?></div>
					<?php endif; ?>

					<?php
					$td_faq = td_m2_faq( td_field( 'faq', $td_id ) );
					if ( $td_faq ) {
						echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'faq',
							[
								'title' => __( 'Common *questions*', 'techdella' ),
								'items' => $td_faq,
							]
						);
					}
					?>

					<section aria-labelledby="td-m2-next-h">
						<div class="td-m2-section-head">
							<?php echo td_chip( __( 'Go further', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<h2 class="td-m2-h2" id="td-m2-next-h"><?php echo td_accent( __( 'Related guide and *service*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
						</div>
						<ul class="td-m2-related-pair" role="list">
							<li>
								<?php
								echo td_m2_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									[
										'kicker' => __( 'Guide · Journal', 'techdella' ),
										'title'  => (string) $td_related['guide'][0],
										'url'    => $td_guide_url,
										'tone'   => 'canvas',
									],
									3
								);
								?>
							</li>
							<li>
								<?php
								echo td_m2_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									[
										'kicker' => __( 'Service', 'techdella' ),
										'title'  => (string) $td_related['service'][0],
										'text'   => __( 'When you want a team to do this for you, end to end.', 'techdella' ),
										'url'    => $td_svc_url,
										'tone'   => 'sand',
									],
									3
								);
								?>
							</li>
						</ul>
					</section>
				</div>
			</div>
			<?php $td_side = td_block( 'ad-slot', [ 'position' => 'sidebar' ] ); ?>
			<?php if ( '' !== trim( $td_side ) ) : ?>
				<div class="td-m2-tool-side"><div><?php echo $td_side; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div>
			<?php endif; ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
