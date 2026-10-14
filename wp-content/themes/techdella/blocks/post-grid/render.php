<?php
/**
 * Post grid / archive list with JS-free category filter chips.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/post-card/lib.php';

$td_layout  = 'list' === ( $attributes['layout'] ?? '' ) ? 'list' : 'grid';
$td_count   = max( 1, min( 24, (int) ( $attributes['count'] ?? 6 ) ) );
$td_current = is_singular() ? (int) get_queried_object_id() : 0;
// Articles of the same kind: posts on the Journal, the business library on Marketing.
$td_type     = sanitize_key( (string) ( $attributes['postType'] ?? '' ) );
$td_type     = '' !== $td_type ? $td_type : ( is_singular( 'business' ) ? 'business' : 'post' );
$td_args     = [
	'post_type'           => $td_type,
	'post_status'         => 'publish',
	'posts_per_page'      => $td_count,
	'offset'              => max( 0, (int) ( $attributes['offset'] ?? 0 ) ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'fields'              => 'ids',
];
$td_cat_slug = sanitize_title( (string) ( $attributes['category'] ?? '' ) );
if ( ! empty( $attributes['related'] ) && $td_current ) {
	$td_primary = td_a3_primary_category( $td_current );
	if ( $td_primary ) {
		$td_args['cat'] = $td_primary->term_id;
	}
} elseif ( '' !== $td_cat_slug ) {
	$td_args['category_name'] = $td_cat_slug;
} elseif ( is_category() ) {
	$td_args['cat'] = (int) get_queried_object_id();
}
if ( $td_current ) {
	$td_args['post__not_in'] = [ $td_current ]; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
}

$td_posts = [];
foreach ( ( new WP_Query( $td_args ) )->posts as $td_pid ) {
	$td_p = td_a3_post_data( (int) $td_pid );
	if ( $td_p ) {
		$td_posts[] = $td_p;
	}
}
if ( ! $td_posts && td_a3_is_preview() ) {
	foreach ( array_slice( (array) ( $attributes['items'] ?? [] ), 0, $td_count ) as $td_item ) {
		if ( is_array( $td_item ) && ! empty( $td_item['title'] ) ) {
			$td_posts[] = td_a3_post_inline( $td_item );
		}
	}
}

// Filter chips: plain links to category archives.
$td_chips = [];
if ( ! empty( $attributes['filters'] ) ) {
	$td_slugs  = array_filter( array_map( 'sanitize_title', array_map( 'strval', (array) ( $attributes['filterCategories'] ?? [] ) ) ) );
	$td_terms  = $td_slugs
		? array_filter( array_map( static fn( $s ) => get_category_by_slug( $s ), $td_slugs ) )
		: get_categories(
			[
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 6,
				'hide_empty' => true,
				'exclude'    => [ (int) get_option( 'default_category' ) ],
			]
		);
	$td_active = is_category() ? (int) get_queried_object_id() : 0;
	if ( '' !== $td_cat_slug ) {
		$td_t      = get_category_by_slug( $td_cat_slug );
		$td_active = $td_t ? (int) $td_t->term_id : $td_active;
	}
	$td_posts_page = (int) get_option( 'page_for_posts' );
	$td_chips[]    = [
		'label'  => (string) ( $attributes['allLabel'] ?? __( 'All', 'techdella' ) ),
		'url'    => $td_posts_page ? (string) get_permalink( $td_posts_page ) : home_url( '/' ),
		'active' => 0 === $td_active,
	];
	foreach ( $td_terms as $td_term ) {
		if ( $td_term instanceof WP_Term ) {
			$td_chips[] = [
				'label'  => $td_term->name,
				'url'    => (string) get_category_link( $td_term ),
				'active' => $td_active === (int) $td_term->term_id,
			];
		}
	}
	if ( count( $td_chips ) < 2 ) {
		$td_chips = [];
	}
}

if ( ! $td_posts && ! $td_chips ) {
	echo td_a3_notice( __( 'Post grid: no published posts match yet.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_title  = (string) ( $attributes['title'] ?? '' );
$td_hlevel = 1 === (int) ( $attributes['titleLevel'] ?? 2 ) ? 1 : 2;
$td_clevel = '' !== $td_title ? $td_hlevel + 1 : 2;
$td_cls    = 'td-post-grid td-post-grid--' . $td_layout . ( 'canvas' === ( $attributes['surface'] ?? '' ) ? ' td-section--canvas td-post-grid--canvas' : '' );
?>
<section <?php echo td_block_wrapper( $td_cls ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-post-grid__inner">
		<?php if ( '' !== $td_title || $td_chips ) : ?>
			<div class="td-post-grid__head">
				<?php if ( '' !== $td_title ) : ?>
					<div class="td-post-grid__heading">
						<?php echo td_chip( (string) ( $attributes['label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php printf( '<h%1$d class="td-post-grid__title">%2$s</h%1$d>', (int) $td_hlevel, td_accent( $td_title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( '' !== trim( (string) ( $attributes['intro'] ?? '' ) ) ) : ?>
							<p class="td-post-grid__intro"><?php echo esc_html( (string) $attributes['intro'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( $td_chips ) : ?>
					<nav class="td-post-grid__filters" aria-label="<?php esc_attr_e( 'Categories', 'techdella' ); ?>">
						<ul role="list">
							<?php foreach ( $td_chips as $td_c ) : ?>
								<li><a href="<?php echo esc_url( $td_c['url'] ); ?>"<?php echo $td_c['active'] ? ' aria-current="page" class="is-active"' : ''; ?>><?php echo esc_html( $td_c['label'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( $td_posts ) : ?>
			<?php if ( 'list' === $td_layout ) : ?>
				<ul class="td-post-grid__rows" role="list">
					<?php foreach ( $td_posts as $td_p ) : ?>
						<li class="td-post-grid__row">
							<?php printf( '<h%1$d class="td-post-grid__row-title"><a href="%2$s">%3$s</a></h%1$d>', (int) $td_clevel, esc_url( (string) $td_p['url'] ), esc_html( (string) $td_p['title'] ) ); ?>
							<span class="td-post-grid__row-meta">
								<?php echo esc_html( implode( ' · ', array_filter( [ (string) $td_p['author'], (string) $td_p['date'] ] ) ) ); ?>
								<?php if ( ! empty( $td_p['example'] ) ) : ?>
									<span class="td-post-grid__example"><?php esc_html_e( 'Example', 'techdella' ); ?></span>
								<?php endif; ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<ul class="td-post-grid__cards" role="list">
					<?php foreach ( $td_posts as $td_i => $td_p ) : ?>
						<li>
							<?php
							echo td_a3_post_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$td_p,
								[
									'level' => $td_clevel,
									'tone'  => $td_i,
									'size'  => ! empty( $attributes['related'] ) ? 'sm' : 'md',
								]
							);
							?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( ! empty( $attributes['moreLabel'] ) && ! empty( $attributes['moreUrl'] ) ) : ?>
			<a class="td-btn td-btn--secondary td-btn--sm td-post-grid__more" href="<?php echo esc_url( td_a3_href( (string) $attributes['moreUrl'] ) ); ?>"><?php echo esc_html( (string) $attributes['moreLabel'] ); ?></a>
		<?php endif; ?>
	</div>
</section>
