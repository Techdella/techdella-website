<?php
/**
 * Glossary hub (/glossary/): A–Z index, category filter chips (links,
 * ?category=<slug>), each term with its short definition. DefinedTermSet
 * schema comes from the SEO module.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';

// The hub lists every term on one page; /glossary/page/N/ goes back to it.
if ( is_paged() ) {
	wp_safe_redirect( (string) get_post_type_archive_link( 'em-glossary' ), 301 );
	exit;
}
td_m2_styles();

$td_hub      = (string) get_post_type_archive_link( 'em-glossary' );
$td_category = td_m2_query_arg( 'category' );
$td_cats     = taxonomy_exists( 'glossary_category' ) ? get_terms(
	[
		'taxonomy'   => 'glossary_category',
		'hide_empty' => true,
	]
) : [];
$td_cats     = is_array( $td_cats ) ? $td_cats : [];
$td_valid    = wp_list_pluck( $td_cats, 'slug' );
if ( '' !== $td_category && ! in_array( $td_category, $td_valid, true ) ) {
	$td_category = '';
}

$td_args = [
	'post_type'      => 'em-glossary',
	'post_status'    => 'publish',
	'posts_per_page' => 500,
	'no_found_rows'  => true,
	'orderby'        => 'title',
	'order'          => 'ASC',
];
if ( '' !== $td_category ) {
	$td_args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		[
			'taxonomy' => 'glossary_category',
			'field'    => 'slug',
			'terms'    => $td_category,
		],
	];
}
$td_terms = get_posts( $td_args );

// Group by first letter (digits and symbols under #).
$td_groups = [];
foreach ( $td_terms as $td_term ) {
	$td_letter                 = strtoupper( substr( remove_accents( ltrim( get_the_title( $td_term ) ) ), 0, 1 ) );
	$td_letter                 = preg_match( '/[A-Z]/', $td_letter ) ? $td_letter : '#';
	$td_groups[ $td_letter ][] = $td_term;
}
ksort( $td_groups );

$td_chips = [
	[
		'label'   => __( 'All terms', 'techdella' ),
		'url'     => $td_hub,
		'current' => '' === $td_category,
	],
];
foreach ( $td_cats as $td_cat ) {
	$td_chips[] = [
		'label'   => $td_cat->name,
		'url'     => add_query_arg( 'category', $td_cat->slug, $td_hub ),
		'current' => $td_cat->slug === $td_category,
	];
}

get_header();
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'hero',
	[
		'eyebrow'      => __( 'Glossary', 'techdella' ),
		'title'        => __( 'The growth marketing *glossary*', 'techdella' ),
		'lead'         => __( 'Plain-English definitions of the search, paid, email and automation terms you’ll hear from us, each with an example and what to do about it.', 'techdella' ),
		'primaryLabel' => __( 'Get a growth audit', 'techdella' ),
		'primaryUrl'   => '/growth-audit/',
		'layout'       => 'center',
		'background'   => 'plain',
	]
);
td_m2_breadcrumbs();
?>
<section class="td-section" aria-label="<?php esc_attr_e( 'Glossary terms', 'techdella' ); ?>" style="padding-top:40px">
	<div class="td-container">
		<?php echo td_m2_chips( __( 'Filter by category', 'techdella' ), $td_chips ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php if ( $td_groups ) : ?>
			<nav class="td-m2-az" aria-label="<?php esc_attr_e( 'Jump to letter', 'techdella' ); ?>">
				<ul role="list">
					<?php foreach ( array_merge( range( 'A', 'Z' ), [ '#' ] ) as $td_l ) : ?>
						<li>
							<?php if ( isset( $td_groups[ $td_l ] ) ) : ?>
								<a href="#letter-<?php echo esc_attr( '#' === $td_l ? 'num' : strtolower( $td_l ) ); ?>"><?php echo esc_html( $td_l ); ?></a>
							<?php elseif ( '#' !== $td_l ) : ?>
								<span aria-hidden="true"><?php echo esc_html( $td_l ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<?php foreach ( $td_groups as $td_l => $td_list ) : ?>
				<section class="td-m2-letter" id="letter-<?php echo esc_attr( '#' === $td_l ? 'num' : strtolower( (string) $td_l ) ); ?>" aria-labelledby="letter-<?php echo esc_attr( '#' === $td_l ? 'num' : strtolower( (string) $td_l ) ); ?>-h">
					<h2 class="td-m2-letter__h" id="letter-<?php echo esc_attr( '#' === $td_l ? 'num' : strtolower( (string) $td_l ) ); ?>-h"><?php echo esc_html( (string) $td_l ); ?></h2>
					<ul class="td-m2-terms" role="list">
						<?php foreach ( $td_list as $td_term ) : ?>
							<li>
								<a href="<?php echo esc_url( (string) get_permalink( $td_term ) ); ?>"><?php echo esc_html( get_the_title( $td_term ) ); ?></a>
								<?php
								$td_def = trim( (string) td_field( 'short_definition', $td_term->ID ) );
								if ( '' !== $td_def ) {
									echo '<p>' . td_mark_placeholders( esc_html( wp_trim_words( $td_def, 18 ) ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="td-m2-note"><?php esc_html_e( 'The glossary is being refreshed. Check back soon.', 'techdella' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'cta-dark',
	[
		'title'       => __( 'Turn the jargon into a *plan*', 'techdella' ),
		'lead'        => __( 'Get an audit of your search, ads and funnel, and a clear plan for what to fix first.', 'techdella' ),
		'buttonLabel' => __( 'Get a growth audit', 'techdella' ),
		'buttonUrl'   => '/growth-audit/',
		'arch'        => true,
	]
);
get_footer();
