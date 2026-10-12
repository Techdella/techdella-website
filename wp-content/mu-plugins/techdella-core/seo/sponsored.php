<?php
/**
 * Paid placements (owner, 12 Oct 2026: sponsored articles, paid guest posts, link insertions).
 * Google's link spam policy requires paid links to carry rel="sponsored", and readers must be told
 * (§8.1 site reputation abuse). A post with `sponsored` ticked gets rel="sponsored" on every outside
 * link and a disclosure; URLs in `sponsored_links` (link insertions) get rel="sponsored" wherever
 * they appear, plus a short note at the end.
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a post is a paid placement.
 *
 * @param int $post_id Post ID.
 */
function td_is_sponsored( int $post_id ): bool {
	return (bool) td_field( 'sponsored', $post_id );
}

/**
 * Paid link URLs on a post (link insertions), normalised without trailing slashes.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function td_sponsored_links( int $post_id ): array {
	$out = [];
	foreach ( (array) preg_split( '/\R/', (string) td_field( 'sponsored_links', $post_id ) ) as $line ) {
		$url = esc_url_raw( trim( (string) $line ) );
		if ( '' !== $url ) {
			$out[] = untrailingslashit( strtolower( $url ) );
		}
	}
	return array_values( array_unique( $out ) );
}

/**
 * Add rel values to the matching links in HTML.
 *
 * @param string   $html     HTML.
 * @param bool     $all_out  Mark every link that leaves our three sites.
 * @param string[] $paid     Specific paid URLs (normalised).
 */
function td_sponsored_rel( string $html, bool $all_out, array $paid ): string {
	if ( ( ! $all_out && ! $paid ) || false === stripos( $html, '<a ' ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $html;
	}
	$ours = array_filter(
		array_map(
			static fn( $u ) => (string) wp_parse_url( $u, PHP_URL_HOST ),
			array_merge( array_values( td_site_domains() ), [ home_url( '/' ), td_url( 'marketing', '/' ), td_url( 'journal', '/' ), td_url( 'studios', '/' ) ] )
		)
	);
	$tags = new WP_HTML_Tag_Processor( $html );
	while ( $tags->next_tag( [ 'tag_name' => 'a' ] ) ) {
		$href = (string) $tags->get_attribute( 'href' );
		$host = (string) wp_parse_url( $href, PHP_URL_HOST );
		if ( '' === $host ) {
			continue;
		}
		$is_paid = in_array( untrailingslashit( strtolower( $href ) ), $paid, true );
		if ( ! $is_paid && ! ( $all_out && ! in_array( $host, $ours, true ) ) ) {
			continue;
		}
		$rel = array_filter( preg_split( '/\s+/', (string) $tags->get_attribute( 'rel' ) ) );
		$rel = array_values( array_unique( array_merge( array_diff( $rel, [ 'follow', 'dofollow' ] ), [ 'sponsored', 'noopener' ] ) ) );
		$tags->set_attribute( 'rel', implode( ' ', $rel ) );
	}
	return $tags->get_updated_html();
}

add_filter(
	'the_content',
	static function ( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$id        = (int) get_the_ID();
		$sponsored = td_is_sponsored( $id );
		$paid      = td_sponsored_links( $id );
		if ( ! $sponsored && ! $paid ) {
			return $content;
		}
		$content = td_sponsored_rel( (string) $content, $sponsored, $paid );
		if ( ! $sponsored && $paid ) {
			$content .= '<p class="td-sponsored-note">' . esc_html__( 'This article contains a sponsored link, marked as such for search engines. Our editors chose where it appears.', 'techdella' ) . '</p>';
		}
		return $content;
	},
	30
);
