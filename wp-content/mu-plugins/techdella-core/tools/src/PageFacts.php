<?php
/**
 * On-Page SEO Checker facts (§12.1): parse fetched HTML into plain SEO facts,
 * and read AI-crawler rules from robots.txt. Pure PHP (DOMDocument), unit-tested.
 * The browser turns these facts into checks; nothing here scores a page.
 */

namespace TechDella\Core\Tools;

defined( 'ABSPATH' ) || exit;

/**
 * HTML + robots.txt parsing.
 */
final class PageFacts {

	/** Crawlers reported in the AI-access table (search + training bots). */
	public const AI_BOTS = [ 'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'PerplexityBot', 'Google-Extended', 'Applebot-Extended', 'CCBot' ];

	/**
	 * Facts from an HTML document.
	 *
	 * @param string $html     HTML (already capped at 2 MB by the fetcher).
	 * @param string $page_url Final URL (to tell internal from external links).
	 * @return array<string,mixed>
	 */
	public static function from_html( string $html, string $page_url ): array {
		$doc  = new \DOMDocument();
		$prev = libxml_use_internal_errors( true );
		// Force UTF-8 unless the document says otherwise.
		$html = preg_match( '/<meta[^>]+charset/i', substr( $html, 0, 4096 ) ) ? $html : '<?xml encoding="UTF-8">' . $html;
		$doc->loadHTML( $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		$xp = new \DOMXPath( $doc );

		$text = static fn( ?\DOMNode $n ): string => null === $n ? '' : trim( (string) preg_replace( '/\s+/u', ' ', (string) $n->textContent ) );
		$attr = static function ( string $query, string $name ) use ( $xp ): string {
			$n = $xp->query( $query );
			if ( false === $n || 0 === $n->length ) {
				return '';
			}
			$el = $n->item( 0 );
			return $el instanceof \DOMElement ? trim( $el->getAttribute( $name ) ) : '';
		};
		$lower = 'translate(%s,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")';

		$title_node = $xp->query( '//head/title|//title' );
		$title      = false !== $title_node && $title_node->length ? $text( $title_node->item( 0 ) ) : '';
		$desc       = $attr( '//meta[' . sprintf( $lower, '@name' ) . '="description"]', 'content' );
		$robots     = $attr( '//meta[' . sprintf( $lower, '@name' ) . '="robots"]', 'content' );
		$canonical  = $attr( '//link[' . sprintf( $lower, '@rel' ) . '="canonical"]', 'href' );
		$viewport   = $attr( '//meta[' . sprintf( $lower, '@name' ) . '="viewport"]', 'content' );
		$og_title   = $attr( '//meta[@property="og:title"]', 'content' );
		$og_image   = $attr( '//meta[@property="og:image"]', 'content' );
		$html_el    = $doc->documentElement;
		$lang       = $html_el instanceof \DOMElement ? $html_el->getAttribute( 'lang' ) : '';

		$headings = [];
		$h1s      = [];
		$hq       = $xp->query( '//h1|//h2|//h3|//h4|//h5|//h6' );
		if ( false !== $hq ) {
			foreach ( $hq as $h ) {
				$level = (int) substr( $h->nodeName, 1 );
				$t     = self::cut( $text( $h ), 160 );
				if ( 1 === $level ) {
					$h1s[] = $t;
				}
				if ( count( $headings ) < 60 ) {
					$headings[] = [
						'level' => $level,
						'text'  => $t,
					];
				}
			}
		}

		// Schema types: JSON-LD (incl. @graph, nested) + microdata.
		$types = [];
		$ld    = $xp->query( '//script[' . sprintf( $lower, '@type' ) . '="application/ld+json"]' );
		if ( false !== $ld ) {
			foreach ( $ld as $s ) {
				$data = json_decode( (string) $s->textContent, true );
				if ( is_array( $data ) ) {
					self::collect_types( $data, $types );
				}
			}
		}
		$md = $xp->query( '//*[@itemtype]' );
		if ( false !== $md ) {
			foreach ( $md as $el ) {
				if ( $el instanceof \DOMElement ) {
					foreach ( preg_split( '/\s+/', trim( $el->getAttribute( 'itemtype' ) ) ) ?: [] as $t ) { // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
						$types[] = (string) preg_replace( '#^https?://schema\.org/#i', '', $t );
					}
				}
			}
		}
		$types = array_values( array_unique( array_filter( $types ) ) );

		// Images.
		$images      = 0;
		$missing_alt = [];
		$empty_alt   = 0;
		$iq          = $xp->query( '//img' );
		if ( false !== $iq ) {
			foreach ( $iq as $img ) {
				if ( ! $img instanceof \DOMElement ) {
					continue;
				}
				++$images;
				if ( ! $img->hasAttribute( 'alt' ) ) {
					if ( count( $missing_alt ) < 20 ) {
						$src           = $img->getAttribute( 'src' ) ? $img->getAttribute( 'src' ) : $img->getAttribute( 'data-src' );
						$missing_alt[] = self::cut( $src, 200 );
					} else {
						$missing_alt[] = '';
					}
				} elseif ( '' === trim( $img->getAttribute( 'alt' ) ) ) {
					++$empty_alt;
				}
			}
		}

		// Links.
		$page_host = strtolower( (string) parse_url( $page_url, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		$page_host = (string) preg_replace( '/^www\./', '', $page_host );
		$internal  = 0;
		$external  = 0;
		$nofollow  = 0;
		$lq        = $xp->query( '//a[@href]' );
		if ( false !== $lq ) {
			foreach ( $lq as $a ) {
				if ( ! $a instanceof \DOMElement ) {
					continue;
				}
				$href = trim( $a->getAttribute( 'href' ) );
				if ( '' === $href || str_starts_with( $href, '#' ) || preg_match( '#^(mailto|tel|javascript|data):#i', $href ) ) {
					continue;
				}
				$host = strtolower( (string) parse_url( $href, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
				$host = (string) preg_replace( '/^www\./', '', $host );
				if ( '' === $host || $host === $page_host ) {
					++$internal;
				} else {
					++$external;
				}
				if ( preg_match( '/\b(nofollow|sponsored|ugc)\b/i', $a->getAttribute( 'rel' ) ) ) {
					++$nofollow;
				}
			}
		}

		// Visible text: body minus script/style/noscript/template/svg.
		$body_text = '';
		$first_p   = '';
		$bodies    = $doc->getElementsByTagName( 'body' );
		$body      = $bodies->length ? $bodies->item( 0 ) : null;
		if ( $body instanceof \DOMElement ) {
			$clone = $body->cloneNode( true );
			if ( $clone instanceof \DOMElement ) {
				foreach ( [ 'script', 'style', 'noscript', 'template', 'svg' ] as $tag ) {
					$nodes = iterator_to_array( $clone->getElementsByTagName( $tag ) );
					foreach ( $nodes as $n ) {
						if ( $n->parentNode ) {
							$n->parentNode->removeChild( $n );
						}
					}
				}
				$body_text = $text( $clone );
			}
			$pq = $xp->query( '//main//p|//article//p' );
			if ( false === $pq || 0 === $pq->length ) {
				$pq = $xp->query( '//body//p' );
			}
			if ( false !== $pq ) {
				foreach ( $pq as $p ) {
					$t = $text( $p );
					if ( str_word_count( $t ) >= 8 ) {
						$first_p = self::cut( $t, 600 );
						break;
					}
				}
			}
		}
		$words = self::word_count( $body_text );

		return [
			'title'              => self::cut( $title, 300 ),
			'meta_description'   => self::cut( $desc, 500 ),
			'meta_robots'        => self::cut( $robots, 200 ),
			'canonical'          => self::cut( $canonical, 500 ),
			'viewport'           => '' !== $viewport,
			'lang'               => self::cut( $lang, 20 ),
			'og_title'           => '' !== $og_title,
			'og_image'           => '' !== $og_image,
			'h1'                 => array_slice( $h1s, 0, 10 ),
			'headings'           => $headings,
			'word_count'         => $words,
			'first_paragraph'    => $first_p,
			'first_words'        => self::cut( implode( ' ', array_slice( preg_split( '/\s+/u', $body_text ) ?: [], 0, 100 ) ), 1000 ), // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
			'images'             => $images,
			'images_missing_alt' => count( $missing_alt ),
			'missing_alt_srcs'   => array_values( array_filter( $missing_alt ) ),
			'images_empty_alt'   => $empty_alt,
			'links_internal'     => $internal,
			'links_external'     => $external,
			'links_nofollow'     => $nofollow,
			'schema_types'       => array_slice( $types, 0, 30 ),
		];
	}

	/**
	 * Walk JSON-LD and collect every @type.
	 *
	 * @param array<mixed> $data  Decoded JSON-LD.
	 * @param string[]     $types Collected (by reference).
	 * @param int          $depth Recursion guard.
	 */
	private static function collect_types( array $data, array &$types, int $depth = 0 ): void {
		if ( $depth > 8 || count( $types ) > 60 ) {
			return;
		}
		foreach ( $data as $key => $value ) {
			if ( '@type' === $key ) {
				foreach ( (array) $value as $t ) {
					if ( is_string( $t ) ) {
						$types[] = (string) preg_replace( '#^https?://schema\.org/#i', '', $t );
					}
				}
			} elseif ( is_array( $value ) ) {
				self::collect_types( $value, $types, $depth + 1 );
			}
		}
	}

	/**
	 * Word count that works for non-Latin scripts too.
	 *
	 * @param string $text Text.
	 */
	public static function word_count( string $text ): int {
		return (int) preg_match_all( '/[\p{L}\p{N}][\p{L}\p{N}\'’-]*/u', $text );
	}

	/**
	 * Truncate to $max characters (multibyte-safe).
	 *
	 * @param string $s   String.
	 * @param int    $max Max characters.
	 */
	private static function cut( string $s, int $max ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $s, 0, $max ) : substr( $s, 0, $max );
	}

	/**
	 * AI-crawler access from robots.txt (RFC 9309 groups; the most specific
	 * matching group wins, else "*"). Per bot: allowed | blocked (Disallow: /) |
	 * partial (Disallow: / plus some Allow paths).
	 *
	 * @param string   $robots robots.txt body ('' = no file: everything allowed).
	 * @param string[] $bots   User-agent tokens.
	 * @return array<string,string>
	 */
	public static function ai_rules( string $robots, array $bots = self::AI_BOTS ): array {
		$groups  = [];
		$current = null;
		$last_ua = false;
		foreach ( preg_split( '/\r\n|\r|\n/', $robots ) ?: [] as $line ) { // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
			$line = trim( (string) preg_replace( '/#.*$/', '', $line ) );
			if ( ! preg_match( '/^([A-Za-z-]+)\s*:\s*(.*)$/', $line, $m ) ) {
				continue;
			}
			$field = strtolower( $m[1] );
			$value = trim( $m[2] );
			if ( 'user-agent' === $field ) {
				if ( ! $last_ua || null === $current ) {
					$groups[] = [
						'agents' => [],
						'rules'  => [],
					];
					$current  = count( $groups ) - 1;
				}
				$groups[ $current ]['agents'][] = strtolower( $value );
				$last_ua                        = true;
			} elseif ( in_array( $field, [ 'allow', 'disallow' ], true ) ) {
				$last_ua = false;
				if ( null !== $current ) {
					$groups[ $current ]['rules'][] = [ $field, $value ];
				}
			} else {
				$last_ua = false;
			}
		}

		$out = [];
		foreach ( $bots as $bot ) {
			$rules = null;
			$star  = null;
			foreach ( $groups as $g ) {
				if ( in_array( strtolower( $bot ), $g['agents'], true ) ) {
					$rules = array_merge( $rules ?? [], $g['rules'] );
				} elseif ( in_array( '*', $g['agents'], true ) ) {
					$star = array_merge( $star ?? [], $g['rules'] );
				}
			}
			$out[ $bot ] = self::verdict( $rules ?? $star ?? [] );
		}
		return $out;
	}

	/**
	 * Verdict for one group's rules.
	 *
	 * @param array<int,array{0:string,1:string}> $rules Rules.
	 */
	private static function verdict( array $rules ): string {
		$root_blocked = false;
		$allows       = false;
		foreach ( $rules as [ $type, $path ] ) {
			if ( '' === $path ) {
				continue; // "Disallow:" (empty) allows everything.
			}
			if ( 'disallow' === $type && in_array( $path, [ '/', '/*' ], true ) ) {
				$root_blocked = true;
			} elseif ( 'allow' === $type ) {
				$allows = true;
			}
		}
		if ( ! $root_blocked ) {
			return 'allowed'; // Path-level rules (/wp-admin/, /search/) don't stop a crawler reading pages.
		}
		return $allows ? 'partial' : 'blocked';
	}
}
