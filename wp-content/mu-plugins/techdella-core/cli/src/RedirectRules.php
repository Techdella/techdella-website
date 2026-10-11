<?php
/**
 * Redirect rules from data/redirects.csv (§9, D6). Pure PHP, unit-tested.
 *
 * CSV columns: source,target,code,condition,notes. The condition column
 * decides how a row matches:
 *   ''                 exact path (trailing slash and case ignored)
 *   'prefix'           the source and everything under it; when the target is a
 *                      directory root (ends in "/") the remainder is kept:
 *                      /solutions/x/ → /solution/x/
 *   'regex <pattern>'  PCRE against the path, $1…$9 substituted into the target
 *   anything else      (e.g. "GSC check first") an exact rule that is
 *                      CONDITIONAL: skipped unless explicitly included.
 *
 * Matching is never substring-based (the §9.2 strpos bug): exact beats
 * prefix (longest source first) beats regex (file order).
 */

namespace TechDella\Core\Cli;

defined( 'ABSPATH' ) || exit;

/**
 * Parse, match and convert redirect rules.
 */
final class RedirectRules {

	public const EXACT  = 'exact';
	public const PREFIX = 'prefix';
	public const REGEX  = 'regex';

	/**
	 * Parse the CSV.
	 *
	 * @param string $csv CSV text with a header row.
	 * @return array{rules: array<int,array<string,mixed>>, errors: string[]}
	 */
	public static function parse_csv( string $csv ): array {
		$rules  = [];
		$errors = [];
		$lines  = preg_split( '/\r\n|\n|\r/', trim( $csv ) );
		$lines  = false === $lines ? [] : $lines;
		$header = array_map( static fn( $h ) => strtolower( trim( (string) $h ) ), str_getcsv( (string) array_shift( $lines ), ',', '"', '' ) );
		$col    = array_flip( $header );
		foreach ( [ 'source', 'target' ] as $required ) {
			if ( ! isset( $col[ $required ] ) ) {
				return [
					'rules'  => [],
					'errors' => [ sprintf( 'Missing "%s" column.', $required ) ],
				];
			}
		}
		foreach ( $lines as $n => $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}
			$row  = str_getcsv( $line, ',', '"', '' );
			$get  = static fn( string $k ): string => isset( $col[ $k ] ) ? trim( (string) ( $row[ $col[ $k ] ] ?? '' ) ) : '';
			$rule = self::make_rule( $get( 'source' ), $get( 'target' ), $get( 'code' ), $get( 'condition' ), $get( 'notes' ), $n + 2 );
			if ( is_string( $rule ) ) {
				$errors[] = sprintf( 'Line %d: %s', $n + 2, $rule );
				continue;
			}
			$rules[] = $rule;
		}
		return [
			'rules'  => $rules,
			'errors' => $errors,
		];
	}

	/**
	 * Build one rule, or an error string.
	 *
	 * @param string $source    Source path.
	 * @param string $target    Target path or absolute URL.
	 * @param string $code      HTTP code.
	 * @param string $condition Condition column.
	 * @param string $notes     Notes.
	 * @param int    $line      CSV line.
	 * @return array<string,mixed>|string
	 */
	public static function make_rule( string $source, string $target, string $code = '301', string $condition = '', string $notes = '', int $line = 0 ) {
		$code = '' === $code ? 301 : (int) $code;
		if ( ! in_array( $code, [ 301, 302, 307, 308, 410 ], true ) ) {
			return sprintf( 'unsupported code %s', $code );
		}
		if ( '' === $source || '/' !== $source[0] ) {
			return 'source must start with "/"';
		}
		if ( '' === $target && 410 !== $code ) {
			return 'empty target';
		}
		$kind        = self::EXACT;
		$pattern     = '';
		$conditional = false;
		$cond        = trim( $condition );
		if ( 'prefix' === strtolower( $cond ) ) {
			$kind = self::PREFIX;
		} elseif ( preg_match( '/^regex\s+(.+)$/i', $cond, $m ) ) {
			$kind    = self::REGEX;
			$pattern = trim( $m[1] );
			if ( false === @preg_match( self::delimit( $pattern ), '' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- validating user regex.
				return sprintf( 'invalid regex %s', $pattern );
			}
		} elseif ( '' !== $cond ) {
			$conditional = true;
		}
		if ( str_contains( $source, '*' ) && self::REGEX !== $kind ) {
			return 'wildcards need a "regex …" condition';
		}
		return [
			'source'      => $source,
			'target'      => $target,
			'code'        => $code,
			'kind'        => $kind,
			'pattern'     => $pattern,
			'conditional' => $conditional,
			'condition'   => $cond,
			'notes'       => $notes,
			'line'        => $line,
		];
	}

	/**
	 * Rules that apply (conditional ones only when included).
	 *
	 * @param array<int,array<string,mixed>> $rules Rules.
	 * @param bool                           $include_conditional Include conditional rows.
	 * @return array<int,array<string,mixed>>
	 */
	public static function active( array $rules, bool $include_conditional = false ): array {
		return array_values( array_filter( $rules, static fn( $r ) => $include_conditional || empty( $r['conditional'] ) ) );
	}

	/**
	 * Normalise a request path: no query/fragment, leading slash, decoded.
	 *
	 * @param string $path Path or URL.
	 */
	public static function normalize_path( string $path ): string {
		$path = (string) preg_replace( '/[?#].*$/s', '', $path );
		if ( preg_match( '#^https?://[^/]+(/.*)?$#i', $path, $m ) ) {
			$path = $m[1] ?? '/';
		}
		$path = rawurldecode( $path );
		$path = '/' . ltrim( $path, '/' );
		return (string) preg_replace( '#/{2,}#', '/', $path );
	}

	/**
	 * Comparable form: lowercase, no trailing slash ("/" stays "/").
	 *
	 * @param string $path Path.
	 */
	private static function key( string $path ): string {
		$path = strtolower( self::normalize_path( $path ) );
		return '/' === $path ? '/' : rtrim( $path, '/' );
	}

	/**
	 * Find the rule for a path and the resolved target (single hop).
	 *
	 * @param array<int,array<string,mixed>> $rules Active rules.
	 * @param string                         $path  Request path.
	 * @return array{rule: array<string,mixed>, target: string}|null
	 */
	public static function match( array $rules, string $path ): ?array {
		$path = self::normalize_path( $path );
		$key  = self::key( $path );
		foreach ( $rules as $rule ) {
			if ( self::EXACT === $rule['kind'] && self::key( (string) $rule['source'] ) === $key ) {
				return [
					'rule'   => $rule,
					'target' => (string) $rule['target'],
				];
			}
		}
		$prefixes = array_values( array_filter( $rules, static fn( $r ) => self::PREFIX === $r['kind'] ) );
		usort( $prefixes, static fn( $a, $b ) => strlen( (string) $b['source'] ) <=> strlen( (string) $a['source'] ) );
		foreach ( $prefixes as $rule ) {
			$base = self::key( (string) $rule['source'] );
			$base = '/' === $base ? '/' : $base . '/';
			if ( rtrim( $base, '/' ) === $key || ( '/' !== $base && str_starts_with( strtolower( $path ), $base ) ) ) {
				$remainder = rtrim( $base, '/' ) === $key ? '' : (string) substr( $path, strlen( $base ) );
				$target    = (string) $rule['target'];
				if ( str_ends_with( $target, '/' ) ) {
					$target .= ltrim( $remainder, '/' );
				}
				return [
					'rule'   => $rule,
					'target' => $target,
				];
			}
		}
		foreach ( $rules as $rule ) {
			if ( self::REGEX !== $rule['kind'] ) {
				continue;
			}
			if ( preg_match( self::delimit( (string) $rule['pattern'] ), $path, $m ) ) {
				$target = (string) preg_replace_callback(
					'/\$(\d)/',
					static fn( $g ) => (string) ( $m[ (int) $g[1] ] ?? '' ),
					(string) $rule['target']
				);
				return [
					'rule'   => $rule,
					'target' => $target,
				];
			}
		}
		return null;
	}

	/**
	 * Wrap a user pattern in delimiters (case-insensitive).
	 *
	 * @param string $pattern Pattern without delimiters.
	 */
	public static function delimit( string $pattern ): string {
		return '#' . str_replace( '#', '\#', $pattern ) . '#i';
	}

	/**
	 * Rewrite production domains in a target to other bases (local ports).
	 *
	 * @param string               $target Target.
	 * @param array<string,string> $map    Production base => replacement base.
	 */
	public static function rewrite_domain( string $target, array $map ): string {
		foreach ( $map as $from => $to ) {
			$from = rtrim( $from, '/' );
			if ( 0 === strcasecmp( $target, $from ) || 0 === stripos( $target, $from . '/' ) ) {
				return rtrim( $to, '/' ) . substr( $target, strlen( $from ) );
			}
		}
		return $target;
	}

	/**
	 * Static chain check: a same-site target that is itself redirected.
	 *
	 * @param array<int,array<string,mixed>> $rules Active rules.
	 * @param string[]                       $own_bases Absolute bases that count as this site.
	 * @return array<int,array{source:string,target:string,next:string}>
	 */
	public static function chains( array $rules, array $own_bases = [] ): array {
		$issues = [];
		foreach ( $rules as $rule ) {
			if ( 410 === $rule['code'] || self::REGEX === $rule['kind'] ) {
				continue;
			}
			$probe = (string) $rule['source'];
			$hit   = self::match( $rules, $probe );
			if ( ! $hit ) {
				continue;
			}
			$target = $hit['target'];
			foreach ( $own_bases as $base ) {
				if ( 0 === stripos( $target, rtrim( $base, '/' ) . '/' ) ) {
					$target = substr( $target, strlen( rtrim( $base, '/' ) ) );
				}
			}
			if ( '/' !== ( $target[0] ?? '' ) ) {
				continue; // Another host: checked by verify.
			}
			$next = self::match( $rules, $target );
			if ( $next ) {
				$issues[] = [
					'source' => (string) $rule['source'],
					'target' => $target,
					'next'   => $next['target'],
				];
			}
		}
		return $issues;
	}

	/**
	 * Escape a literal path for Rank Math regex sources. Rank Math wraps the
	 * pattern in "@…@" after stripslashes(), so backslashes cannot be used:
	 * special characters go in character classes instead.
	 *
	 * @param string $literal Literal text.
	 */
	public static function rm_quote( string $literal ): string {
		return (string) preg_replace_callback( '/[.+*?()\[\]{}|^$@]/', static fn( $m ) => '[' . ( '^' === $m[0] ? '\\^' : $m[0] ) . ']', $literal );
	}

	/**
	 * Convert one rule to Rank Math redirection rows (sources are matched by
	 * Rank Math against the path WITHOUT leading/trailing slashes).
	 *
	 * @param array<string,mixed> $rule   Rule.
	 * @param string              $target Target (after domain rewrite; relative or absolute).
	 * @return array<int,array{sources: array<int,array{pattern:string,comparison:string,ignore:string}>, url_to: string, header_code: int, rank: int}>|string
	 *         Rows (rank: 0 = exact, 1 = prefix, 2 = regex — lower ranks must win), or an error.
	 */
	public static function to_rank_math( array $rule, string $target ) {
		$source = trim( (string) $rule['source'], '/' );
		$row    = static fn( string $pattern, string $comparison, string $to, int $rank ) => [
			'sources'     => [
				[
					'pattern'    => $pattern,
					'comparison' => $comparison,
					'ignore'     => 'case',
				],
			],
			'url_to'      => $to,
			'header_code' => (int) $rule['code'],
			'rank'        => $rank,
		];
		if ( self::EXACT === $rule['kind'] ) {
			if ( '' === $source ) {
				return 'cannot redirect the home page';
			}
			return [ $row( $source, 'exact', $target, 0 ) ];
		}
		if ( self::PREFIX === $rule['kind'] ) {
			if ( ! str_ends_with( $target, '/' ) ) {
				return [ $row( $source, 'start', $target, 1 ) ];
			}
			return [
				$row( $source, 'exact', $target, 0 ),
				$row( '^' . self::rm_quote( $source ) . '/(.+)$', 'regex', $target . '$1/', 1 ),
			];
		}
		$pattern = (string) $rule['pattern'];
		if ( str_contains( $pattern, '\\' ) || str_contains( $pattern, '@' ) ) {
			return 'regex with backslashes or "@" cannot be stored in Rank Math';
		}
		$pattern = (string) preg_replace( '#^\^/#', '^', $pattern );
		$pattern = (string) preg_replace( '#/\$$#', '$', $pattern ); // Rank Math strips the trailing slash.
		$to      = $target;
		if ( preg_match( '/\$\d$/', $to ) ) {
			$to .= '/';
		}
		return [ $row( $pattern, 'regex', $to, 2 ) ];
	}

	/**
	 * Export rules in the redirects.csv format.
	 *
	 * @param array<int,array<string,mixed>> $rules Rules.
	 */
	public static function to_csv( array $rules ): string {
		$fh = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $fh ) {
			return '';
		}
		fputcsv( $fh, [ 'source', 'target', 'code', 'condition', 'notes' ], ',', '"', '' );
		foreach ( $rules as $r ) {
			$cond = self::PREFIX === $r['kind'] ? 'prefix' : ( self::REGEX === $r['kind'] ? 'regex ' . $r['pattern'] : (string) $r['condition'] );
			fputcsv( $fh, [ $r['source'], $r['target'], $r['code'], $cond, $r['notes'] ], ',', '"', '' );
		}
		rewind( $fh );
		$out = (string) stream_get_contents( $fh );
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return $out;
	}
}
