<?php
/**
 * Canonical URL cleaner (§8.3): absolute, https in production, trailing
 * slash, no query string (UTM, ref, gclid, fbclid, ?q= on tools and any
 * unknown query var all canonicalise to the clean URL), no fragment.
 *
 * Pure PHP (no WordPress) so it can be unit-tested.
 */

namespace TechDella\Core\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Canonical URL helpers.
 */
final class Canonical {

	/**
	 * Clean a URL for use as a canonical.
	 *
	 * @param string   $url   Absolute URL (relative URLs are returned as '').
	 * @param bool     $https Force the https scheme (production).
	 * @param string[] $keep  Query vars to keep (only for plain permalinks, e.g. "p", "page_id").
	 */
	public static function clean( string $url, bool $https = false, array $keep = [] ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}
		$parts = parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure class, no WordPress.
		if ( false === $parts || empty( $parts['host'] ) ) {
			return '';
		}
		$scheme = strtolower( $parts['scheme'] ?? 'https' );
		if ( $https || ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			$scheme = 'https';
		}
		$host = strtolower( $parts['host'] );
		$port = isset( $parts['port'] ) ? (int) $parts['port'] : 0;
		if ( ( 'https' === $scheme && 443 === $port ) || ( 'http' === $scheme && 80 === $port ) || ( $https && 80 === $port ) ) {
			$port = 0;
		}
		$path = self::path( (string) ( $parts['path'] ?? '/' ) );

		$query = '';
		if ( $keep && ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $vars );
			$vars = array_intersect_key( $vars, array_flip( $keep ) );
			ksort( $vars );
			$query = $vars ? '?' . http_build_query( $vars ) : '';
		}

		return $scheme . '://' . $host . ( $port ? ':' . $port : '' ) . $path . $query;
	}

	/**
	 * Normalise a path: collapse duplicate slashes and add the trailing
	 * slash, except on file-like paths (/sitemap_index.xml, /llms.txt).
	 *
	 * @param string $path URL path.
	 */
	public static function path( string $path ): string {
		$path = '/' . ltrim( (string) preg_replace( '#/{2,}#', '/', $path ), '/' );
		$last = (string) substr( $path, (int) strrpos( $path, '/' ) + 1 );
		if ( '' !== $last && preg_match( '/\.[a-z0-9]{2,5}$/i', $last ) ) {
			return $path;
		}
		return rtrim( $path, '/' ) . '/';
	}

	/**
	 * Append a pagination segment (/page/N/) to an archive URL. Paginated
	 * archives are self-canonical (§8.3), never pointing at page 1.
	 *
	 * @param string $url  Archive URL.
	 * @param int    $page Page number.
	 * @param string $base Pagination base ("page").
	 */
	public static function paged( string $url, int $page, string $base = 'page' ): string {
		if ( $page < 2 || '' === $url ) {
			return $url;
		}
		$query = '';
		$pos   = strpos( $url, '?' );
		if ( false !== $pos ) {
			$query = substr( $url, $pos );
			$url   = substr( $url, 0, $pos );
		}
		return rtrim( $url, '/' ) . '/' . trim( $base, '/' ) . '/' . $page . '/' . $query;
	}
}
