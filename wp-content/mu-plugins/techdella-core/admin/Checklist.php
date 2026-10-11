<?php
/**
 * Pre-publish checklist rules (§10.1). Pure PHP, unit-tested; the same
 * rules run in the block editor (admin/assets/prepublish.js) and on the
 * server when a post goes live (admin/editor.php). Keep both in sync.
 */

namespace TechDella\Core\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Checklist rules.
 */
final class Checklist {

	/**
	 * Placeholder tokens that must not reach a public page (§0.5, §16). Same
	 * set as td_mark_placeholders().
	 */
	public const PLACEHOLDER_RE = '/\[(CONFIRM|PRICE|YOUR PRICE|MINIMUM SPEND|URLS|PHOTO)([^\]]*)\]/';

	public const MIN_INTERNAL_LINKS = 3;
	public const META_MIN           = 140;
	public const META_MAX           = 155;
	public const TITLE_MAX          = 60;

	/**
	 * Run every rule.
	 *
	 * @param array{title?:string,h1?:string[],content?:string,primary_kw?:string,meta_description?:string,hosts?:string[]} $in Input.
	 * @return array<string,array{ok:bool,blocking:bool,label:string,message:string}>
	 */
	public static function run( array $in ): array {
		$title    = (string) ( $in['title'] ?? '' );
		$content  = (string) ( $in['content'] ?? '' );
		$kw       = trim( (string) ( $in['primary_kw'] ?? '' ) );
		$meta     = trim( (string) ( $in['meta_description'] ?? '' ) );
		$h1s      = array_values( array_filter( array_merge( [ $title ], (array) ( $in['h1'] ?? [] ) ), static fn( $h ): bool => '' !== (string) $h ) );
		$links    = self::internal_links( $content, (array) ( $in['hosts'] ?? [] ) );
		$no_alt   = self::images_missing_alt( $content );
		$tokens   = array_values( array_unique( array_merge( self::placeholders( $title ), self::placeholders( $content ), self::placeholders( $meta ) ) ) );
		$meta_len = self::length( $meta );

		$kw_in_h1 = '' !== $kw && (bool) array_filter( $h1s, static fn( $h ) => self::contains_keyword( (string) $h, $kw ) );

		return [
			'meta_description' => [
				'ok'       => '' !== $meta,
				'blocking' => true,
				'label'    => 'Meta description filled',
				'message'  => '' !== $meta ? 'Meta description is filled.' : 'Write a meta description (140–155 characters) in the Rank Math box.',
			],
			'meta_length'      => [
				'ok'       => $meta_len >= self::META_MIN && $meta_len <= self::META_MAX,
				'blocking' => false,
				'label'    => 'Meta description 140–155 characters',
				'message'  => sprintf( 'Meta description is %d characters (aim for 140–155).', $meta_len ),
			],
			'keyword_title'    => [
				'ok'       => $kw_in_h1,
				'blocking' => true,
				'label'    => 'Primary keyword in the H1/title',
				'message'  => '' === $kw ? 'Set the primary keyword (primary_kw) first.' : ( $kw_in_h1 ? 'The H1/title uses the primary keyword.' : sprintf( 'Use "%s" in the title or H1.', $kw ) ),
			],
			'keyword_intro'    => [
				'ok'       => '' !== $kw && self::keyword_in_intro( $content, $kw ),
				'blocking' => true,
				'label'    => 'Primary keyword in the first paragraph',
				'message'  => '' === $kw ? 'Set the primary keyword (primary_kw) first.' : sprintf( 'The first paragraph should use "%s" naturally.', $kw ),
			],
			'internal_links'   => [
				'ok'       => $links >= self::MIN_INTERNAL_LINKS,
				'blocking' => true,
				'label'    => 'At least 3 internal links',
				'message'  => sprintf( '%d internal link(s) found; add at least %d to services, guides or glossary terms.', $links, self::MIN_INTERNAL_LINKS ),
			],
			'image_alt'        => [
				'ok'       => 0 === $no_alt,
				'blocking' => true,
				'label'    => 'Every image has alt text',
				'message'  => 0 === $no_alt ? 'Every image has alt text.' : sprintf( '%d image(s) have no alt text. Describe what is shown.', $no_alt ),
			],
			'placeholders'     => [
				'ok'       => ! $tokens,
				'blocking' => true,
				'label'    => 'No [CONFIRM]/[PRICE] tokens left',
				'message'  => $tokens ? 'Resolve: ' . implode( ', ', array_slice( $tokens, 0, 5 ) ) : 'No placeholder tokens left.',
			],
		];
	}

	/**
	 * Blocking rules that failed.
	 *
	 * @param array<string,array{ok:bool,blocking:bool,label:string,message:string}> $results From run().
	 * @return array<string,string> rule id => message.
	 */
	public static function failures( array $results ): array {
		$out = [];
		foreach ( $results as $id => $rule ) {
			if ( $rule['blocking'] && ! $rule['ok'] ) {
				$out[ $id ] = $rule['message'];
			}
		}
		return $out;
	}

	/**
	 * Count distinct internal link targets: relative URLs, or absolute URLs on
	 * one of the given hosts (this site and its sister sites). Anchors,
	 * mailto:, tel: and javascript: never count.
	 *
	 * @param string   $html  HTML.
	 * @param string[] $hosts Hosts treated as internal (www. ignored).
	 */
	public static function internal_links( string $html, array $hosts ): int {
		$hosts = array_map( [ self::class, 'normalise_host' ], $hosts );
		if ( ! preg_match_all( '/<a\b[^>]*?\bhref\s*=\s*(["\'])(.*?)\1/is', $html, $m ) ) {
			return 0;
		}
		$targets = [];
		foreach ( $m[2] as $href ) {
			$href = trim( html_entity_decode( (string) $href, ENT_QUOTES ) );
			if ( '' === $href || '#' === $href[0] || preg_match( '/^(mailto|tel|javascript|data|sms):/i', $href ) ) {
				continue;
			}
			if ( preg_match( '#^(?:[a-z][a-z0-9+.-]*:)?//([^/?\#:]+)#i', $href, $hm ) ) {
				if ( ! in_array( self::normalise_host( $hm[1] ), $hosts, true ) ) {
					continue;
				}
			} elseif ( preg_match( '/^[a-z][a-z0-9+.-]*:/i', $href ) ) {
				continue; // Some other scheme.
			}
			$targets[ strtolower( (string) preg_replace( '/#.*$/', '', $href ) ) ] = true;
		}
		return count( $targets );
	}

	/**
	 * Host without www. and port, lowercased.
	 *
	 * @param string $host Host or URL.
	 */
	public static function normalise_host( string $host ): string {
		$host = strtolower( trim( $host ) );
		if ( str_contains( $host, '//' ) ) {
			$host = (string) parse_url( $host, PHP_URL_HOST ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		}
		$host = (string) preg_replace( '/:\d+$/', '', $host );
		return (string) preg_replace( '/^www\./', '', $host );
	}

	/**
	 * Number of <img> tags with a missing or empty alt attribute.
	 *
	 * @param string $html HTML.
	 */
	public static function images_missing_alt( string $html ): int {
		if ( ! preg_match_all( '/<img\b[^>]*>/i', $html, $m ) ) {
			return 0;
		}
		$missing = 0;
		foreach ( $m[0] as $tag ) {
			if ( ! preg_match( '/\balt\s*=\s*(["\'])(.*?)\1/is', $tag, $alt ) || '' === trim( html_entity_decode( $alt[2], ENT_QUOTES ) ) ) {
				++$missing;
			}
		}
		return $missing;
	}

	/**
	 * Placeholder tokens found in a text.
	 *
	 * @param string $text Text or HTML.
	 * @return string[]
	 */
	public static function placeholders( string $text ): array {
		return preg_match_all( self::PLACEHOLDER_RE, $text, $m ) ? array_values( array_unique( $m[0] ) ) : [];
	}

	/**
	 * Text of the first non-empty paragraph.
	 *
	 * @param string $html HTML (block markup is fine).
	 */
	public static function first_paragraph( string $html ): string {
		if ( preg_match_all( '/<p\b[^>]*>(.*?)<\/p>/is', $html, $m ) ) {
			foreach ( $m[1] as $inner ) {
				$text = trim( html_entity_decode( strip_tags( (string) $inner ), ENT_QUOTES ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
				if ( '' !== $text ) {
					return $text;
				}
			}
		}
		return '';
	}

	/**
	 * Whether the primary keyword appears in the first paragraph.
	 *
	 * @param string $html HTML.
	 * @param string $kw   Keyword.
	 */
	public static function keyword_in_intro( string $html, string $kw ): bool {
		return self::contains_keyword( self::first_paragraph( $html ), $kw );
	}

	/**
	 * Case-, punctuation- and whitespace-insensitive phrase match on word
	 * boundaries ("B2B SEO" matches "b2b-seo" and "B2B  SEO," but not "b2bseo").
	 *
	 * @param string $haystack Text.
	 * @param string $kw       Keyword phrase.
	 */
	public static function contains_keyword( string $haystack, string $kw ): bool {
		$needle = self::normalise_text( $kw );
		if ( '' === $needle ) {
			return false;
		}
		return str_contains( ' ' . self::normalise_text( $haystack ) . ' ', ' ' . $needle . ' ' );
	}

	/**
	 * Lowercase, entities decoded, tags stripped, non-letters to single spaces.
	 *
	 * @param string $text Text.
	 */
	public static function normalise_text( string $text ): string {
		$text = html_entity_decode( strip_tags( $text ), ENT_QUOTES ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
		$text = mb_strtolower( $text );
		$text = (string) preg_replace( "/['’]/u", '', $text );
		$text = (string) preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $text );
		return trim( $text );
	}

	/**
	 * Character length as editors count it (multibyte-safe).
	 *
	 * @param string $text Text.
	 */
	public static function length( string $text ): int {
		return mb_strlen( trim( $text ) );
	}
}
