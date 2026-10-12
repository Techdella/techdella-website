<?php
/**
 * Glossary term (§10.6): answer-first — the ≤40-word short definition comes
 * first (tldr block), then the article, related terms, the related service
 * CTA and the byline (author, reviewer, last reviewed; §8.1 E-E-A-T).
 * DefinedTerm schema comes from the SEO module.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';
require_once get_theme_file_path( 'blocks/post-card/lib.php' );
td_m2_styles();

get_header();
while ( have_posts() ) :
	the_post();
	$td_id   = (int) get_the_ID();
	$td_cats = taxonomy_exists( 'glossary_category' ) ? get_the_terms( $td_id, 'glossary_category' ) : false;
	$td_cat  = is_array( $td_cats ) && $td_cats ? $td_cats[0] : null;
	$td_def  = trim( (string) td_field( 'short_definition', $td_id ) );
	?>
	<article <?php post_class( 'td-m2-glossary' ); ?>>
		<header class="td-container td-container--narrow td-m2-head">
			<?php
			if ( function_exists( 'td_breadcrumbs' ) ) {
				td_breadcrumbs();
			}
			echo td_chip( $td_cat ? sprintf( /* translators: %s: category */ __( 'Glossary · %s', 'techdella' ), $td_cat->name ) : __( 'Glossary', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<h1><?php echo td_accent( td_m2_accent( get_the_title() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
		</header>

		<div class="td-container td-container--narrow td-m2-term" style="padding-bottom:var(--td-space)">
			<?php
			if ( '' !== $td_def ) {
				echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'tldr',
					[
						'label' => __( 'Definition', 'techdella' ),
						'text'  => $td_def,
					]
				);
			}
			?>
			<div class="td-prose"><?php the_content(); ?></div>

			<?php
			$td_related = array_filter(
				td_m2_ids( td_field( 'related_terms', $td_id ) ),
				static fn( $rid ) => 'publish' === get_post_status( $rid ) && $rid !== $td_id
			);
			if ( $td_related ) :
				?>
				<section aria-labelledby="td-m2-related-h">
					<h2 class="td-m2-h2" id="td-m2-related-h" style="font-size:28px"><?php echo td_accent( __( 'Related *terms*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					<ul class="td-m2-related" role="list">
						<?php foreach ( $td_related as $td_rid ) : ?>
							<li><a href="<?php echo esc_url( (string) get_permalink( $td_rid ) ); ?>"><?php echo esc_html( get_the_title( $td_rid ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php
			$td_service = td_m2_service_link( (int) ( td_m2_ids( td_field( 'related_service', $td_id ) )[0] ?? 0 ) );
			if ( $td_service ) {
				echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'post-cta',
					[
						/* translators: %s: service name */
						'title'       => sprintf( __( 'Want help with %s?', 'techdella' ), $td_service['title'] ),
						'text'        => __( 'Tell us where you are today. We’ll show you what to fix first.', 'techdella' ),
						/* translators: %s: service name */
						'buttonLabel' => sprintf( __( 'See %s', 'techdella' ), $td_service['title'] ),
						'buttonUrl'   => $td_service['url'],
					]
				);
			} else {
				echo td_block( 'post-cta', [ 'service' => 'seo' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			// Byline (§8.1): named author + reviewer + last reviewed. Never a generic team byline.
			$td_author   = td_a3_person( (int) get_post_field( 'post_author', $td_id ) );
			$td_reviewer = td_byline_person( td_field( 'reviewer', $td_id ) );
			$td_reviewed = trim( (string) td_field( 'last_reviewed', $td_id ) );
			$td_ts       = '' !== $td_reviewed ? strtotime( $td_reviewed ) : false;
			?>
			<dl class="td-m2-byline">
				<div>
					<dt><?php esc_html_e( 'Written by', 'techdella' ); ?></dt>
					<dd>
						<?php
						if ( $td_author ) {
							echo $td_author['url'] ? '<a href="' . esc_url( $td_author['url'] ) . '" rel="author">' . esc_html( $td_author['name'] ) . '</a>' : esc_html( $td_author['name'] );
							echo $td_author['role'] ? ', ' . esc_html( $td_author['role'] ) : '';
						} else {
							echo td_mark_placeholders( '[CONFIRM author]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</dd>
				</div>
				<div>
					<dt><?php esc_html_e( 'Reviewed by', 'techdella' ); ?></dt>
					<dd><?php echo $td_reviewer ? esc_html( $td_reviewer['name'] . ( $td_reviewer['role'] ? ', ' . $td_reviewer['role'] : '' ) ) : td_mark_placeholders( '[CONFIRM reviewer]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd>
				</div>
				<div>
					<dt><?php esc_html_e( 'Last reviewed', 'techdella' ); ?></dt>
					<dd><?php echo $td_ts ? '<time datetime="' . esc_attr( gmdate( 'Y-m-d', $td_ts ) ) . '">' . esc_html( date_i18n( get_option( 'date_format' ), $td_ts ) ) . '</time>' : td_mark_placeholders( '[CONFIRM review date]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd>
				</div>
			</dl>
			<p><a class="td-link" href="<?php echo esc_url( (string) get_post_type_archive_link( 'em-glossary' ) ); ?>"><?php esc_html_e( 'Back to the glossary', 'techdella' ); ?></a></p>
		</div>
	</article>
	<?php
endwhile;
get_footer();
