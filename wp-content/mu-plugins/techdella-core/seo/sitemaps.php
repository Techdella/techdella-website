<?php
/**
 * Sitemaps (§8.3): Rank Math sitemaps, one index per site, images/videos
 * included. Excluded: document, thank-you, td_testimonial, td_team,
 * platform, and anything noindex. Core sitemaps get the same exclusions
 * when Rank Math sitemaps are not running (and are off when they are).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Seo\Noindex;

/**
 * Whether a user's author archive is indexable (team member with ≥ 3 posts).
 *
 * @param int $user_id User ID.
 */
function td_seo_author_indexable( int $user_id ): bool {
	return null !== td_seo_team_post_for_user( $user_id ) && (int) count_user_posts( $user_id, 'post', true ) >= Noindex::MIN_AUTHOR_POSTS;
}

// --- Rank Math --------------------------------------------------------------

add_filter(
	'rank_math/sitemap/exclude_post_type',
	static function ( $exclude, $type ) {
		return in_array( (string) $type, td_seo_sitemap_excluded_types(), true ) ? true : $exclude;
	},
	99,
	2
);

add_filter(
	'rank_math/sitemap/exclude_taxonomy',
	static function ( $exclude, $taxonomy ) {
		return in_array( (string) $taxonomy, [ 'division', 'post_format' ], true ) ? true : $exclude;
	},
	99,
	2
);

add_filter(
	'rank_math/sitemap/entry',
	static function ( $url, $type, $item ) {
		if ( 'post' === $type && $item instanceof WP_Post && td_seo_post_is_noindex( $item ) ) {
			return false;
		}
		if ( 'term' === $type && $item instanceof WP_Term && 'post_tag' === $item->taxonomy && (int) $item->count < Noindex::MIN_TAG_POSTS ) {
			return false;
		}
		if ( 'user' === $type && $item instanceof WP_User && ! td_seo_author_indexable( (int) $item->ID ) ) {
			return false;
		}
		return $url;
	},
	99,
	3
);

// Rank Math replaces core sitemaps; keep core off while its sitemap module runs.
add_filter(
	'wp_sitemaps_enabled',
	static function ( $enabled ) {
		return td_seo_rank_math_module( 'sitemap' ) ? false : $enabled;
	},
	99
);

// --- Core sitemaps (fallback when Rank Math sitemaps are off) ----------------

add_filter(
	'wp_sitemaps_post_types',
	static function ( array $types ): array {
		return array_diff_key( $types, array_flip( td_seo_sitemap_excluded_types() ) );
	}
);

add_filter(
	'wp_sitemaps_posts_query_args',
	static function ( array $args ): array {
		$args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- sitemap generation only.
			'relation' => 'OR',
			[
				'key'     => '_td_noindex',
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => '_td_noindex',
				'value'   => '1',
				'compare' => '!=',
			],
		];
		return $args;
	}
);

add_filter(
	'wp_sitemaps_taxonomies',
	static function ( array $taxonomies ): array {
		unset( $taxonomies['division'], $taxonomies['post_format'] );
		return $taxonomies;
	}
);

add_filter(
	'wp_sitemaps_taxonomies_query_args',
	static function ( array $args, string $taxonomy ): array {
		if ( 'post_tag' === $taxonomy ) {
			$args['hide_empty'] = true;
			$args['meta_query'] = []; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			add_filter( 'get_terms', 'td_seo_drop_thin_tags', 10, 1 );
		}
		return $args;
	},
	10,
	2
);

/**
 * Drop tags with fewer than 5 posts from a core sitemap term list.
 *
 * @param array<int,mixed> $terms Terms.
 * @return array<int,mixed>
 */
function td_seo_drop_thin_tags( $terms ) {
	remove_filter( 'get_terms', 'td_seo_drop_thin_tags', 10 );
	if ( ! is_array( $terms ) ) {
		return $terms;
	}
	return array_values(
		array_filter(
			$terms,
			static fn( $term ) => ! ( $term instanceof WP_Term ) || (int) $term->count >= Noindex::MIN_TAG_POSTS
		)
	);
}

add_filter(
	'wp_sitemaps_users_query_args',
	static function ( array $args ): array {
		$ids             = array_values( array_filter( array_map( 'td_seo_team_user_id', td_seo_team_posts() ), 'td_seo_author_indexable' ) );
		$args['include'] = $ids ? $ids : [ 0 ];
		return $args;
	}
);
