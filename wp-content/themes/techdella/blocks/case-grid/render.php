<?php
/**
 * Case grid: case_study (permission-confirmed) or studio_work posts; inline items as a fallback.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';

$td_source  = 'studio_work' === ( $attributes['source'] ?? '' ) ? 'studio_work' : 'case_study';
$td_variant = 'cover' === ( $attributes['variant'] ?? '' ) ? 'cover' : 'card';
$td_count   = max( 1, min( 12, (int) ( $attributes['count'] ?? 3 ) ) );
$td_cases   = [];

if ( post_type_exists( $td_source ) ) {
	$td_args = [
		'post_type'           => $td_source,
		'post_status'         => 'publish',
		'posts_per_page'      => $td_count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'fields'              => 'ids',
		'orderby'             => [
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		],
	];
	if ( 'case_study' === $td_source ) {
		// Only permissioned case studies (§7). ACF stores true_false as "1".
		$td_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			[
				'key'   => 'permission_confirmed',
				'value' => '1',
			],
		];
	}
	$td_tax = [];
	if ( ! empty( $attributes['division'] ) && taxonomy_exists( 'division' ) ) {
		$td_tax[] = [
			'taxonomy' => 'division',
			'field'    => 'slug',
			'terms'    => sanitize_title( (string) $attributes['division'] ),
		];
	}
	if ( ! empty( $attributes['industry'] ) && taxonomy_exists( 'td_industry' ) ) {
		$td_tax[] = [
			'taxonomy' => 'td_industry',
			'field'    => 'slug',
			'terms'    => sanitize_title( (string) $attributes['industry'] ),
		];
	}
	if ( $td_tax ) {
		$td_args['tax_query'] = $td_tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}
	foreach ( ( new WP_Query( $td_args ) )->posts as $td_id ) {
		$td_case = td_a3_case_from_post( (int) $td_id );
		if ( $td_case ) {
			$td_cases[] = $td_case;
		}
	}
}
if ( ! $td_cases ) {
	foreach ( array_slice( (array) ( $attributes['items'] ?? [] ), 0, $td_count ) as $td_item ) {
		if ( is_array( $td_item ) && ! empty( $td_item['title'] ) ) {
			$td_cases[] = td_a3_case_normalize( $td_item, $td_source );
		}
	}
}
if ( ! $td_cases ) {
	echo td_a3_notice( __( 'Case grid: no published items yet (case studies need permission_confirmed). Nothing renders on the live site.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_surface = 'canvas' === ( $attributes['surface'] ?? '' ) ? ' td-section--canvas' : '';
$td_has_h   = ! empty( $attributes['title'] );
?>
<section <?php echo td_block_wrapper( 'td-case-grid td-section' . $td_surface . ' td-case-grid--' . $td_variant ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container">
		<?php
		if ( $td_has_h || ! empty( $attributes['label'] ) ) {
			echo td_section_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				[
					'label' => (string) ( $attributes['label'] ?? '' ),
					'title' => (string) ( $attributes['title'] ?? '' ),
					'intro' => (string) ( $attributes['intro'] ?? '' ),
				]
			);
		}
		?>
		<ul class="td-case-grid__list" role="list">
			<?php foreach ( $td_cases as $td_case ) : ?>
				<li><?php echo td_a3_case_card( $td_case, $td_variant, $td_has_h ? 3 : 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
