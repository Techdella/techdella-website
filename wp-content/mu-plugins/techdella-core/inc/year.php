<?php
/**
 * Year in titles and meta descriptions rolls over by itself (owner, 11 Oct 2026).
 *
 * Editors write {{year}} (or Rank Math's %currentyear%) in a title, SEO title, meta description
 * or excerpt, e.g. "How to Start a Catering Business ({{year}} Guide)". Every place that prints
 * it shows the current year: the H1 and cards (the_title), the document title, meta description,
 * Open Graph and Twitter tags, schema and llms.txt. On the first request of a new year the
 * page cache is purged and social images are regenerated, so nothing keeps last year's number.
 * Dated facts ("2026 listening data") are written as numbers and never change.
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Replace year tokens with the current year (site time zone).
 *
 * @param string $text Text.
 */
function td_year_tokens( string $text ): string {
	if ( ! str_contains( $text, '{{year}}' ) && ! str_contains( $text, '%currentyear%' ) ) {
		return $text;
	}
	return str_replace( [ '{{year}}', '%currentyear%' ], wp_date( 'Y' ), $text );
}

// Titles, excerpts and descriptions on the front end and in feeds/REST (the editor keeps the token).
$td_year_filter = static fn( $text ) => is_string( $text ) && ! ( is_admin() && ! wp_doing_ajax() ) ? td_year_tokens( $text ) : $text;
foreach ( [ 'the_title', 'single_post_title', 'get_the_excerpt', 'the_excerpt', 'pre_get_document_title', 'rank_math/frontend/title', 'rank_math/frontend/description' ] as $td_hook ) {
	add_filter( $td_hook, $td_year_filter, 999 );
}
foreach ( [ 'facebook', 'twitter' ] as $td_net ) {
	foreach ( [ 'title', 'description', 'og_title', 'og_description', 'twitter_title', 'twitter_description' ] as $td_tag ) {
		add_filter( "rank_math/opengraph/{$td_net}/{$td_tag}", $td_year_filter, 999 );
	}
}
add_filter(
	'document_title_parts',
	static function ( $parts ) {
		return is_array( $parts ) ? array_map( static fn( $p ) => is_string( $p ) ? td_year_tokens( $p ) : $p, $parts ) : $parts;
	},
	999
);
unset( $td_year_filter, $td_hook, $td_net, $td_tag );

// New year: purge cached pages and queue new social images for posts whose title has the token.
add_action(
	'init',
	static function () {
		$year = wp_date( 'Y' );
		if ( get_option( 'td_year_seen' ) === $year ) {
			return;
		}
		update_option( 'td_year_seen', $year, true );
		do_action( 'litespeed_purge_all' );
		if ( ! wp_next_scheduled( 'td_year_rollover' ) ) {
			wp_schedule_single_event( time() + 60, 'td_year_rollover' );
		}
	},
	20
);
add_action(
	'td_year_rollover',
	static function () {
		global $wpdb;
		$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND ( post_title LIKE '%{{year}}%' OR post_title LIKE '%\\%currentyear\\%%' )" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( array_map( 'intval', (array) $ids ) as $id ) {
			if ( function_exists( 'td_og_generate' ) ) {
				td_og_generate( $id, true );
			}
			if ( function_exists( 'td_cover_generate' ) ) {
				td_cover_generate( $id, true );
			}
		}
	}
);
