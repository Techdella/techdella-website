<?php
/**
 * Case studies hub (/case-studies/): permission-confirmed case studies only
 * (§7, §10.5), with industry filter chips as plain links (?industry=<slug>).
 * Filtered views are canonical to /case-studies/ (§8.3, SEO module).
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';

// One page: the hub lists every case study (≤ 12), so /page/N/ goes back to the hub.
if ( is_paged() ) {
	wp_safe_redirect( (string) get_post_type_archive_link( 'case_study' ), 301 );
	exit;
}
td_m2_styles();

$td_hub      = (string) get_post_type_archive_link( 'case_study' );
$td_industry = td_m2_query_arg( 'industry' );

// Industries that have at least one published, permissioned case study.
$td_ids  = get_posts(
	[
		'post_type'      => 'case_study',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			[
				'key'   => 'permission_confirmed',
				'value' => '1',
			],
		],
	]
);
$td_inds = [];
if ( $td_ids && taxonomy_exists( 'td_industry' ) ) {
	foreach ( wp_get_object_terms( $td_ids, 'td_industry' ) as $td_term ) {
		$td_inds[ $td_term->slug ] = $td_term->name;
	}
	asort( $td_inds );
}
if ( '' !== $td_industry && ! isset( $td_inds[ $td_industry ] ) ) {
	$td_industry = '';
}
$td_chips = [
	[
		'label'   => __( 'All industries', 'techdella' ),
		'url'     => $td_hub,
		'current' => '' === $td_industry,
	],
];
foreach ( $td_inds as $td_slug => $td_name ) {
	$td_chips[] = [
		'label'   => $td_name,
		'url'     => add_query_arg( 'industry', $td_slug, $td_hub ),
		'current' => $td_slug === $td_industry,
	];
}

get_header();
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'hero',
	[
		'eyebrow'        => __( 'Case studies', 'techdella' ),
		'title'          => __( 'Work that shows up in the *numbers*', 'techdella' ),
		'lead'           => __( 'Eleven published case studies from clients in Nigeria, the US, Mexico, South Africa and Lebanon. Each one shows what we found, what we did and what changed.', 'techdella' ),
		'primaryLabel'   => __( 'Get a growth audit', 'techdella' ),
		'primaryUrl'     => '/growth-audit/',
		'secondaryLabel' => __( 'Book a discovery call', 'techdella' ),
		'secondaryUrl'   => '/book-a-discovery-call/',
		'layout'         => 'center',
	]
);
td_m2_breadcrumbs();
?>
<section class="td-section" aria-label="<?php esc_attr_e( 'Case studies', 'techdella' ); ?>" style="padding-bottom:0">
	<div class="td-container">
		<?php echo td_m2_chips( __( 'Filter by industry', 'techdella' ), $td_chips ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( ! $td_ids ) : ?>
			<p class="td-m2-note"><?php esc_html_e( 'Case studies are being updated with our clients. Check back soon, or book a call and we’ll walk you through relevant work.', 'techdella' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
if ( $td_ids ) {
	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'case-grid',
		[
			'source'   => 'case_study',
			'count'    => 12,
			'industry' => $td_industry,
			'variant'  => 'card',
		]
	);
}
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'cta-dark',
	[
		'title'       => __( 'Your growth story *next*', 'techdella' ),
		'lead'        => __( 'Get an audit of your search, ads and funnel, and a clear plan for what to fix first.', 'techdella' ),
		'buttonLabel' => __( 'Get a growth audit', 'techdella' ),
		'buttonUrl'   => '/growth-audit/',
		'arch'        => true,
	]
);
get_footer();
