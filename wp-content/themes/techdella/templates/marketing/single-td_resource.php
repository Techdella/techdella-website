<?php
/**
 * Resource page (§13.2): cover, what's inside (bullets), who it's for (the
 * page content), page count, the inline lead_magnet form (hidden
 * resource_slug) and a sample-page preview. The page is indexable; the PDF
 * itself is delivered after submit (thank-you page + Startbuddi email).
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
	$td_slug       = (string) get_post_field( 'post_name', $td_id );
	$td_pages      = (int) td_field( 'pages', $td_id );
	$td_cover      = (int) ( td_m2_ids( td_field( 'cover', $td_id ) )[0] ?? 0 );
	$td_cover      = $td_cover ? $td_cover : (int) get_post_thumbnail_id( $td_id );
	$td_pdf        = (int) ( td_m2_ids( td_field( 'pdf', $td_id ) )[0] ?? 0 );
	$td_formkey    = 'newsletter' === td_field( 'form', $td_id ) ? 'newsletter' : 'lead_magnet';
	$td_bullets    = array_values( array_filter( array_map( static fn( $r ) => trim( (string) ( $r['text'] ?? '' ) ), td_m2_rows( td_field( 'bullets', $td_id ) ) ) ) );
	$td_cover_html = static function ( string $size = '' ) use ( $td_cover, $td_pages ): string {
		$out = '<div class="td-m2-cover' . ( $size ? ' td-m2-cover--' . esc_attr( $size ) : '' ) . '">';
		if ( $td_cover ) {
			$out .= td_image(
				$td_cover,
				[
					'alt'   => '',
					'sizes' => '(max-width: 900px) 80vw, 420px',
				]
			);
		} else {
			/* translators: %d: page count */
			$out .= '<span class="td-m2-cover__kicker">' . esc_html( $td_pages ? sprintf( _n( 'Free PDF · %d page', 'Free PDF · %d pages', $td_pages, 'techdella' ), $td_pages ) : __( 'Free PDF', 'techdella' ) ) . '</span>';
			$out .= '<span class="td-m2-cover__title">' . esc_html( get_the_title() ) . '</span>';
		}
		return $out . '</div>';
	};
	?>
	<article <?php post_class(); ?>>
		<div class="td-container td-m2-head td-m2-head--wide">
			<?php
			if ( function_exists( 'td_breadcrumbs' ) ) {
				td_breadcrumbs();
			}
			?>
			<div class="td-m2-split">
				<div>
					<?php echo td_chip( __( 'Free guide', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h1><?php echo td_accent( td_m2_accent( get_the_title() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
					<?php $td_lead = td_m2_lead( $td_id, 40 ); ?>
					<?php if ( has_excerpt() ) : ?>
						<p class="td-m2-head__lead"><?php echo td_mark_placeholders( esc_html( $td_lead ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<?php endif; ?>

					<?php if ( $td_bullets ) : ?>
						<h2 style="font-size:22px;margin:36px 0 16px"><?php esc_html_e( 'What’s inside', 'techdella' ); ?></h2>
						<ul class="td-m2-checks">
							<?php foreach ( $td_bullets as $td_b ) : ?>
								<li><?php echo td_mark_placeholders( esc_html( $td_b ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<dl class="td-m2-facts" style="margin-top:36px">
						<?php if ( $td_pages ) : ?>
							<div style="padding:16px 16px 16px 0;border-bottom:1px solid var(--td-line)"><dt><?php esc_html_e( 'Length', 'techdella' ); ?></dt><dd class="td-m2-facts__v" style="margin-left:0"><?php echo esc_html( sprintf( /* translators: %d: page count */ _n( '%d page', '%d pages', $td_pages, 'techdella' ), $td_pages ) ); ?></dd></div>
						<?php endif; ?>
						<div style="padding:16px 16px 16px 0;border-bottom:1px solid var(--td-line)"><dt><?php esc_html_e( 'Format', 'techdella' ); ?></dt><dd class="td-m2-facts__v" style="margin-left:0"><?php esc_html_e( 'PDF', 'techdella' ); ?></dd></div>
						<div style="padding:16px 16px 16px 0;border-bottom:1px solid var(--td-line)"><dt><?php esc_html_e( 'Price', 'techdella' ); ?></dt><dd class="td-m2-facts__v" style="margin-left:0"><?php esc_html_e( 'Free', 'techdella' ); ?></dd></div>
					</dl>
					<?php if ( ! $td_pdf ) : ?>
						<p class="td-m2-note td-m2-note--banner"><?php echo td_mark_placeholders( esc_html__( 'The PDF file is not attached yet. [CONFIRM file]', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<?php endif; ?>
				</div>
				<div id="get">
					<?php echo $td_cover_html( 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div style="margin-top:24px">
						<?php
						echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'form',
							[
								'formKey' => $td_formkey,
								'variant' => 'light',
								'hidden'  => 'lead_magnet' === $td_formkey ? [ 'resource_slug' => $td_slug ] : [],
							]
						);
						?>
					</div>
				</div>
			</div>
		</div>

		<?php if ( '' !== trim( (string) get_post_field( 'post_content', $td_id ) ) ) : ?>
			<section class="td-section td-section--canvas" aria-labelledby="td-m2-who-h">
				<div class="td-container td-container--narrow">
					<h2 class="td-m2-h2" id="td-m2-who-h"><?php echo td_accent( __( 'Who it’s *for*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					<div class="td-prose" style="margin-top:20px"><?php the_content(); ?></div>
				</div>
			</section>
		<?php endif; ?>

		<section class="td-section" aria-labelledby="td-m2-sample-h">
			<div class="td-container td-m2-sample">
				<div>
					<?php echo td_chip( __( 'Sample page', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="td-m2-h2" id="td-m2-sample-h"><?php echo td_accent( __( 'A look *inside*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					<p style="margin-top:14px;color:var(--td-body)"><?php esc_html_e( 'Each guide uses the same clear layout: short sections, checklists you can tick off, and a next step at the end.', 'techdella' ); ?></p>
					<p style="margin-top:24px"><?php echo td_button( __( 'Get the PDF', 'techdella' ), '#get', 'primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				</div>
				<figure style="margin:0">
					<div class="td-m2-page" role="img" aria-label="<?php esc_attr_e( 'Illustration of a guide page layout', 'techdella' ); ?>">
						<span class="td-m2-page__bar td-m2-page__bar--h"></span>
						<span class="td-m2-page__bar"></span>
						<span class="td-m2-page__bar" style="width:85%"></span>
						<span class="td-m2-page__box"></span>
						<span class="td-m2-page__bar"></span>
						<span class="td-m2-page__bar" style="width:60%"></span>
						<span class="td-m2-page__box"></span>
					</div>
					<figcaption class="td-muted" style="font-size:14px;margin-top:10px"><?php esc_html_e( 'Layout preview, not the actual page', 'techdella' ); ?></figcaption>
				</figure>
			</div>
		</section>
	</article>
	<?php
endwhile;
get_footer();
