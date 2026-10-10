<?php
/**
 * A small, safe YAML front-matter parser for content/ Markdown files (§10.1).
 *
 * Supports exactly what the briefs need, and nothing that executes or
 * references: scalars (string, int, float, true/false, null), single- and
 * double-quoted strings, inline lists [a, "b, c"], block lists ("- item"),
 * nested maps one level deep (key:\n  child: value) including block lists
 * inside them, "#" comments and blank lines. Anchors, tags, multi-document
 * streams and block scalars (| >) are not supported: | and > are read as the
 * folded text of the indented lines that follow.
 *
 * Pure PHP, unit-tested (tests/unit/CliFrontMatterTest.php).
 */

namespace TechDella\Core\Cli;

defined( 'ABSPATH' ) || exit;

/**
 * Front-matter + body splitter and YAML-subset parser.
 */
final class FrontMatter {

	/**
	 * Split a document into front matter (parsed) and body.
	 *
	 * @param string $doc Whole file contents.
	 * @return array{meta: array<string,mixed>, body: string}
	 */
	public static function parse( string $doc ): array {
		$doc = preg_replace( '/^\xEF\xBB\xBF/', '', $doc ) ?? $doc; // BOM.
		$doc = str_replace( [ "\r\n", "\r" ], "\n", $doc );
		if ( ! preg_match( '/^---[ \t]*\n(.*?)\n(?:---|\.\.\.)[ \t]*(?:\n|$)(.*)$/s', $doc, $m ) ) {
			if ( preg_match( '/^---[ \t]*\n(?:---|\.\.\.)[ \t]*(?:\n|$)(.*)$/s', $doc, $e ) ) {
				return [
					'meta' => [],
					'body' => ltrim( $e[1], "\n" ),
				];
			}
			return [
				'meta' => [],
				'body' => $doc,
			];
		}
		return [
			'meta' => self::parse_yaml( $m[1] ),
			'body' => ltrim( $m[2], "\n" ),
		];
	}

	/**
	 * Parse the YAML subset.
	 *
	 * @param string $yaml YAML text.
	 * @return array<string,mixed>
	 * @throws \InvalidArgumentException On a line it cannot read.
	 */
	public static function parse_yaml( string $yaml ): array {
		$lines = explode( "\n", str_replace( [ "\r\n", "\r", "\t" ], [ "\n", "\n", '  ' ], $yaml ) );
		$out   = [];
		$count = count( $lines );
		$i     = 0;
		while ( $i < $count ) {
			$line = self::strip_comment( $lines[ $i ] );
			if ( '' === trim( $line ) ) {
				++$i;
				continue;
			}
			if ( self::indent( $line ) > 0 ) {
				throw new \InvalidArgumentException( sprintf( 'Unexpected indentation on line %d.', $i + 1 ) );
			}
			if ( ! preg_match( '/^([A-Za-z0-9_][A-Za-z0-9_\-\.]*)\s*:(?:\s+(.*))?$/', rtrim( $line ), $m ) ) {
				throw new \InvalidArgumentException( sprintf( 'Cannot read line %d: expected "key: value".', $i + 1 ) );
			}
			$key   = $m[1];
			$value = isset( $m[2] ) ? trim( $m[2] ) : '';
			++$i;
			if ( '' !== $value && '|' !== $value[0] && '>' !== $value[0] ) {
				$out[ $key ] = self::scalar_or_inline( $value );
				continue;
			}
			// Collect the indented child block.
			$block = [];
			while ( $i < $count ) {
				$raw = self::strip_comment( $lines[ $i ] );
				if ( '' !== trim( $raw ) && 0 === self::indent( $raw ) && ! preg_match( '/^-(\s|$)/', $raw ) ) {
					break;
				}
				$block[] = $raw;
				++$i;
			}
			if ( '' !== $value ) { // | or > block scalar.
				$text        = array_map( 'trim', $block );
				$out[ $key ] = '|' === $value[0] ? rtrim( implode( "\n", $text ) ) : trim( (string) preg_replace( '/\s+/', ' ', implode( ' ', $text ) ) );
				continue;
			}
			$out[ $key ] = self::parse_block( $block, 1 );
		}
		return $out;
	}

	/**
	 * Parse an indented block: a list or a one-level map.
	 *
	 * @param string[] $block Lines.
	 * @param int      $depth Nesting depth (maps nest one level only).
	 * @return mixed
	 * @throws \InvalidArgumentException On unreadable lines.
	 */
	private static function parse_block( array $block, int $depth ) {
		$block = array_values( array_filter( $block, static fn( $l ) => '' !== trim( $l ) ) );
		if ( ! $block ) {
			return null;
		}
		$base = self::indent( $block[0] );
		if ( preg_match( '/^\s*-(\s|$)/', $block[0] ) ) {
			$list = [];
			foreach ( $block as $l ) {
				if ( self::indent( $l ) !== $base || ! preg_match( '/^\s*-\s*(.*)$/', $l, $m ) ) {
					throw new \InvalidArgumentException( sprintf( 'Cannot read list item "%s" (nested lists are not supported).', trim( $l ) ) );
				}
				$list[] = self::scalar_or_inline( trim( $m[1] ) );
			}
			return $list;
		}
		if ( $depth > 1 ) {
			throw new \InvalidArgumentException( 'Maps nest one level only.' );
		}
		$map   = [];
		$count = count( $block );
		$i     = 0;
		while ( $i < $count ) {
			$l = $block[ $i ];
			if ( self::indent( $l ) !== $base || ! preg_match( '/^\s*([A-Za-z0-9_][A-Za-z0-9_\-\.]*)\s*:(?:\s+(.*))?$/', rtrim( $l ), $m ) ) {
				throw new \InvalidArgumentException( sprintf( 'Cannot read "%s".', trim( $l ) ) );
			}
			++$i;
			$value = isset( $m[2] ) ? trim( $m[2] ) : '';
			if ( '' !== $value ) {
				$map[ $m[1] ] = self::scalar_or_inline( $value );
				continue;
			}
			$child = [];
			while ( $i < $count && self::indent( $block[ $i ] ) > $base ) {
				$child[] = $block[ $i ];
				++$i;
			}
			$map[ $m[1] ] = $child ? self::parse_list_only( $child ) : null;
		}
		return $map;
	}

	/**
	 * A list nested under a map key.
	 *
	 * @param string[] $block Lines.
	 * @return array<int,mixed>
	 * @throws \InvalidArgumentException When the block is not a flat list.
	 */
	private static function parse_list_only( array $block ): array {
		$value = self::parse_block( $block, 2 );
		if ( ! is_array( $value ) || ! array_is_list( $value ) ) {
			throw new \InvalidArgumentException( 'Maps nest one level only.' );
		}
		return $value;
	}

	/**
	 * A scalar or an inline [list].
	 *
	 * @param string $value Raw value.
	 * @return mixed
	 */
	private static function scalar_or_inline( string $value ) {
		if ( '' !== $value && '[' === $value[0] && ']' === substr( $value, -1 ) ) {
			$inner = trim( substr( $value, 1, -1 ) );
			if ( '' === $inner ) {
				return [];
			}
			return array_map( [ self::class, 'scalar' ], self::split_inline( $inner ) );
		}
		if ( '{' === ( $value[0] ?? '' ) ) {
			throw new \InvalidArgumentException( 'Inline maps {…} are not supported; use an indented map.' );
		}
		return self::scalar( $value );
	}

	/**
	 * Split "a, 'b, c', d" on commas outside quotes.
	 *
	 * @param string $inner List contents.
	 * @return string[]
	 */
	private static function split_inline( string $inner ): array {
		$items = [];
		$buf   = '';
		$quote = '';
		$len   = strlen( $inner );
		for ( $i = 0; $i < $len; $i++ ) {
			$c = $inner[ $i ];
			if ( '' !== $quote ) {
				if ( '\\' === $c && '"' === $quote && $i + 1 < $len ) {
					$buf .= $c . $inner[ ++$i ];
					continue;
				}
				if ( $c === $quote ) {
					$quote = '';
				}
				$buf .= $c;
				continue;
			}
			if ( '"' === $c || "'" === $c ) {
				$quote = $c;
				$buf  .= $c;
			} elseif ( ',' === $c ) {
				$items[] = trim( $buf );
				$buf     = '';
			} else {
				$buf .= $c;
			}
		}
		$items[] = trim( $buf );
		return $items;
	}

	/**
	 * Typed scalar.
	 *
	 * @param string $value Raw value.
	 * @return string|int|float|bool|null
	 */
	public static function scalar( string $value ) {
		$value = trim( $value );
		$len   = strlen( $value );
		if ( $len >= 2 && '"' === $value[0] && '"' === $value[ $len - 1 ] ) {
			return stripcslashes( substr( $value, 1, -1 ) );
		}
		if ( $len >= 2 && "'" === $value[0] && "'" === $value[ $len - 1 ] ) {
			return str_replace( "''", "'", substr( $value, 1, -1 ) );
		}
		$lower = strtolower( $value );
		if ( in_array( $lower, [ 'true', 'yes' ], true ) ) {
			return true;
		}
		if ( in_array( $lower, [ 'false', 'no' ], true ) ) {
			return false;
		}
		if ( in_array( $lower, [ '', 'null', '~' ], true ) ) {
			return null;
		}
		if ( preg_match( '/^-?(0|[1-9]\d*)$/', $value ) && strlen( $value ) < 16 ) {
			return (int) $value;
		}
		if ( preg_match( '/^-?(0|[1-9]\d*)\.\d+$/', $value ) ) {
			return (float) $value;
		}
		return $value;
	}

	/**
	 * Remove a trailing " # comment" outside quotes.
	 *
	 * @param string $line Line.
	 */
	private static function strip_comment( string $line ): string {
		if ( preg_match( '/^\s*#/', $line ) ) {
			return '';
		}
		if ( ! str_contains( $line, '#' ) ) {
			return rtrim( $line );
		}
		$quote = '';
		$len   = strlen( $line );
		for ( $i = 0; $i < $len; $i++ ) {
			$c = $line[ $i ];
			if ( '' !== $quote ) {
				if ( $c === $quote ) {
					$quote = '';
				}
				continue;
			}
			if ( '"' === $c || "'" === $c ) {
				$quote = $c;
			} elseif ( '#' === $c && $i > 0 && ( ' ' === $line[ $i - 1 ] ) ) {
				return rtrim( substr( $line, 0, $i ) );
			}
		}
		return rtrim( $line );
	}

	/**
	 * Leading spaces.
	 *
	 * @param string $line Line.
	 */
	private static function indent( string $line ): int {
		return strlen( $line ) - strlen( ltrim( $line, ' ' ) );
	}

	/**
	 * Count [CONFIRM…] tokens in a text.
	 *
	 * @param string $text Text.
	 */
	public static function count_confirm( string $text ): int {
		return (int) preg_match_all( '/\[CONFIRM\b[^\]]*\]/', $text );
	}
}
