<?php
/**
 * Shared SEO helpers: Rank Math detection, post type lists, team lookups.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether Rank Math's front-end SEO (head tags, sitemaps) is running: the
 * plugin is loaded and its registration step is done or skipped.
 */
function td_seo_rank_math(): bool {
	static $active = null;
	if ( null !== $active ) {
		return $active;
	}
	$is_active = class_exists( 'RankMath\Helper' ) && is_callable( [ 'RankMath\Helper', 'is_invalid_registration' ] )
		&& ! call_user_func( [ 'RankMath\Helper', 'is_invalid_registration' ] );
	if ( did_action( 'plugins_loaded' ) ) {
		$active = $is_active;
	}
	return $is_active;
}

/**
 * Whether a Rank Math module is active (and Rank Math is running).
 *
 * @param string $module Module id, e.g. "sitemap".
 */
function td_seo_rank_math_module( string $module ): bool {
	return td_seo_rank_math() && is_callable( [ 'RankMath\Helper', 'is_module_active' ] )
		&& (bool) call_user_func( [ 'RankMath\Helper', 'is_module_active' ], $module );
}

/**
 * Post types that are always noindex (§8.3). Reads the content model when it is loaded.
 *
 * @return string[]
 */
function td_seo_noindex_types(): array {
	$types = function_exists( 'td_post_types_noindex' ) ? td_post_types_noindex() : [ 'thank-you', 'document' ];
	return array_values( array_unique( array_map( 'strval', $types ) ) );
}

/**
 * Post types excluded from sitemaps (§8.3), always including the noindex ones.
 *
 * @return string[]
 */
function td_seo_sitemap_excluded_types(): array {
	$types = function_exists( 'td_post_types_excluded_from_sitemap' )
		? td_post_types_excluded_from_sitemap()
		: [ 'document', 'thank-you', 'td_testimonial', 'td_team', 'platform' ];
	$types = array_merge( $types, td_seo_noindex_types(), [ 'attachment' ] );
	return array_values( array_unique( array_map( 'strval', $types ) ) );
}

/**
 * Whether a post is marked noindex (post meta _td_noindex, or a noindex type).
 *
 * @param WP_Post|int $post Post.
 */
function td_seo_post_is_noindex( $post ): bool {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	if ( in_array( $post->post_type, td_seo_noindex_types(), true ) ) {
		return true;
	}
	return '1' === (string) get_post_meta( $post->ID, '_td_noindex', true );
}

/**
 * Published td_team profiles (cached per request).
 *
 * @return WP_Post[]
 */
function td_seo_team_posts(): array {
	static $posts = null;
	if ( null === $posts ) {
		$posts = post_type_exists( 'td_team' ) ? get_posts(
			[
				'post_type'        => 'td_team',
				'post_status'      => 'publish',
				'numberposts'      => 50,
				'orderby'          => [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				],
				'suppress_filters' => false,
			]
		) : [];
	}
	return $posts;
}

/**
 * The td_team profile linked to a WP user (field wp_user), or null.
 *
 * @param int $user_id User ID.
 */
function td_seo_team_post_for_user( int $user_id ): ?WP_Post {
	if ( $user_id <= 0 ) {
		return null;
	}
	foreach ( td_seo_team_posts() as $post ) {
		if ( td_seo_team_user_id( $post ) === $user_id ) {
			return $post;
		}
	}
	return null;
}

/**
 * The WP user ID linked to a td_team profile (field wp_user), or 0.
 *
 * @param WP_Post $post td_team post.
 */
function td_seo_team_user_id( WP_Post $post ): int {
	$linked = td_field( 'wp_user', $post->ID );
	if ( $linked instanceof WP_User ) {
		return (int) $linked->ID;
	}
	if ( is_array( $linked ) ) {
		$linked = $linked['ID'] ?? ( $linked[0] ?? 0 );
		$linked = $linked instanceof WP_User ? $linked->ID : $linked;
	}
	return is_numeric( $linked ) ? (int) $linked : 0;
}

/**
 * The td_team profile with this name, or null.
 *
 * @param string $name Person name.
 */
function td_seo_team_post_by_name( string $name ): ?WP_Post {
	foreach ( td_seo_team_posts() as $post ) {
		if ( 0 === strcasecmp( trim( $post->post_title ), trim( $name ) ) ) {
			return $post;
		}
	}
	return null;
}

/**
 * Plain text from a WordPress string (titles are texturized/encoded).
 *
 * @param string $text Text or HTML.
 */
function td_seo_text( string $text ): string {
	$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * The meta description an editor wrote for a post (Rank Math field), or ''.
 * Never derived from the content (§8.5).
 *
 * @param int $post_id Post ID.
 */
function td_seo_meta_description( int $post_id ): string {
	$desc = (string) get_post_meta( $post_id, 'rank_math_description', true );
	if ( '' === $desc || false !== strpos( $desc, '%' ) ) {
		return '' === $desc ? '' : td_seo_text( (string) preg_replace( '/%[a-z_()0-9]+%/i', '', $desc ) );
	}
	return td_seo_text( $desc );
}

/**
 * Markets served (§10.4), used for Service areaServed and llms.txt.
 * Option td_markets (array of names) overrides.
 *
 * @return string[]
 */
function td_seo_markets(): array {
	$markets = get_option( 'td_markets' );
	if ( ! is_array( $markets ) || ! $markets ) {
		$markets = [ 'Nigeria', 'United States', 'United Kingdom', 'Canada', 'United Arab Emirates', 'Europe' ];
	}
	return array_values( array_filter( array_map( 'strval', (array) apply_filters( 'td_seo_markets', $markets ) ) ) );
}
