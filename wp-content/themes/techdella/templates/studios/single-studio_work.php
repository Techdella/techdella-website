<?php
/**
 * Studios work item: Live/Concept label, client, disciplines, gallery, write-up,
 * more work, CTA. Items whose content is built from a hero block render
 * full-bleed through this site's singular template instead.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

if ( preg_match( '#<!-- wp:techdella/(hero|studio-hero)[ /]#', (string) get_post_field( 'post_content', get_queried_object_id() ) ) ) {
	require __DIR__ . '/singular.php';
	return;
}

require_once get_theme_file_path( 'blocks/case-card/lib.php' );

get_header();
while ( have_posts() ) :
	the_post();
	$td_id      = (int) get_the_ID();
	$td_concept = 'Live' !== td_field( 'status', $td_id );
	$td_client  = (string) td_field( 'client', $td_id );
	$td_disc    = [];
	foreach ( (array) td_field( 'disciplines', $td_id ) as $td_row ) {
		$td_d = is_array( $td_row ) ? (string) ( $td_row['discipline'] ?? '' ) : (string) $td_row;
		if ( '' !== trim( $td_d ) ) {
			$td_disc[] = $td_d;
		}
	}
	$td_gallery = [];
	foreach ( (array) td_field( 'gallery', $td_id ) as $td_g ) {
		$td_gid = is_array( $td_g ) ? (int) ( $td_g['ID'] ?? $td_g['id'] ?? 0 ) : (int) $td_g;
		if ( $td_gid ) {
			$td_gallery[] = $td_gid;
		}
	}
	if ( ! $td_gallery && has_post_thumbnail() ) {
		$td_gallery[] = (int) get_post_thumbnail_id();
	}
	?>
	<style>
		.td-work-single__head{padding-bottom:40px}
		.td-work-single__status{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin:0 0 18px}
		.td-work-single__title{margin:0 0 16px}
		.td-work-single__meta{display:flex;flex-wrap:wrap;gap:12px 40px;margin:28px 0 0;padding:20px 0 0;border-top:1px solid var(--td-line)}
		.td-work-single__meta dt{font-size:12px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--td-muted)}
		.td-work-single__meta dd{margin:4px 0 0;color:var(--td-ink);font-weight:500}
		.td-work-single__gallery{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));margin:0 0 56px;padding:0;list-style:none}
		.td-work-single__gallery li:first-child{grid-column:1/-1}
		.td-work-single__gallery figure{margin:0;border-radius:var(--td-r-md);overflow:hidden;background:var(--td-canvas)}
		.td-work-single__gallery img{display:block;width:100%;height:auto}
		.td-work-single__gallery li:not(:first-child) img{max-height:640px;object-fit:contain}
		.td-work-single__body{padding-bottom:var(--td-space)}
	</style>
	<article <?php post_class( 'td-work-single' ); ?>>
		<header class="td-container td-section td-work-single__head">
			<?php
			if ( function_exists( 'td_breadcrumbs' ) ) {
				td_breadcrumbs();
			}
			?>
			<p class="td-work-single__status">
				<?php if ( $td_concept ) : ?>
					<span class="td-label-example"><?php esc_html_e( 'Concept', 'techdella' ); ?></span>
					<span class="td-muted"><?php esc_html_e( 'A concept project, not client work.', 'techdella' ); ?></span>
				<?php else : ?>
					<span class="td-chip"><?php esc_html_e( 'Live project', 'techdella' ); ?></span>
				<?php endif; ?>
			</p>
			<h1 class="td-work-single__title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="td-lead"><?php echo td_mark_placeholders( esc_html( get_the_excerpt() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
			<dl class="td-work-single__meta">
				<?php if ( '' !== $td_client ) : ?>
					<div><dt><?php esc_html_e( 'Client', 'techdella' ); ?></dt><dd><?php echo esc_html( $td_client ); ?></dd></div>
				<?php endif; ?>
				<?php if ( $td_disc ) : ?>
					<div><dt><?php esc_html_e( 'Disciplines', 'techdella' ); ?></dt><dd><?php echo esc_html( implode( ' · ', $td_disc ) ); ?></dd></div>
				<?php endif; ?>
				<div><dt><?php esc_html_e( 'Status', 'techdella' ); ?></dt><dd><?php echo esc_html( $td_concept ? __( 'Concept', 'techdella' ) : __( 'Live', 'techdella' ) ); ?></dd></div>
			</dl>
		</header>
		<div class="td-container">
			<?php if ( $td_gallery ) : ?>
				<ul class="td-work-single__gallery" role="list" aria-label="<?php esc_attr_e( 'Gallery', 'techdella' ); ?>">
					<?php foreach ( $td_gallery as $td_i => $td_gid ) : ?>
						<li>
							<figure>
								<?php
								echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									$td_gid,
									[
										'alt'      => (string) get_post_meta( $td_gid, '_wp_attachment_image_alt', true ),
										'sizes'    => 0 === $td_i ? '(max-width: 1240px) 100vw, 1240px' : '(max-width: 760px) 100vw, 600px',
										'priority' => 0 === $td_i,
									]
								);
								?>
							</figure>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="td-container td-container--narrow td-prose td-work-single__body">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
	$td_more  = get_posts(
		[
			'post_type'      => 'studio_work',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'post__not_in'   => [ $td_id ], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			'fields'         => 'ids',
			'no_found_rows'  => true,
		]
	);
	$td_cards = array_filter( array_map( static fn( $p ) => td_a3_case_from_post( (int) $p ), $td_more ) );
	if ( $td_cards ) :
		?>
		<section class="td-case-grid td-section td-section--canvas td-case-grid--cover" aria-labelledby="td-more-work">
			<div class="td-container">
				<div class="td-section-head"><h2 class="td-section-head__title" id="td-more-work"><?php echo td_accent( __( 'More *work*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2></div>
				<ul class="td-case-grid__list" role="list">
					<?php foreach ( $td_cards as $td_case ) : ?>
						<li><?php echo td_a3_case_card( $td_case, 'cover', 3 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	endif;
endwhile;

echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'cta-dark',
	[
		'title'       => __( 'Have an idea? Let’s make it *real*', 'techdella' ),
		'lead'        => __( 'Send a short brief. We’ll reply with a scope and an estimate.', 'techdella' ),
		'buttonLabel' => __( 'Start a project', 'techdella' ),
		'buttonUrl'   => '/start/',
		'variant'     => 'sand',
	]
);
get_footer();
