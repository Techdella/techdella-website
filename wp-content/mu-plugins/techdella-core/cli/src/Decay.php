<?php
/**
 * Content decay (§10.1 step 6): pages whose Google Search Console clicks
 * dropped ≥ 30% between two periods, and pages past their review cadence.
 * Pure PHP, unit-tested.
 *
 * Input: a GSC "Pages" export made with Date → Compare (e.g. last 3 months vs
 * previous 3 months). The CSV has a page column ("Top pages", "Page" or "URL")
 * and two clicks columns, e.g. "Last 3 months Clicks" and "Previous 3 months Clicks".
 */

namespace TechDella\Core\Cli;

defined( 'ABSPATH' ) || exit;

/**
 * Decay maths.
 */
final class Decay {

	/**
	 * Parse a GSC comparison export.
	 *
	 * @param string $csv CSV text.
	 * @return array<int,array{url:string,current:int,previous:int}>
	 * @throws \InvalidArgumentException When the columns cannot be found.
	 */
	public static function parse_gsc( string $csv ): array {
		$csv   = (string) preg_replace( '/^\xEF\xBB\xBF/', '', $csv );
		$lines = preg_split( '/\r\n|\n|\r/', trim( $csv ) );
		$lines = false === $lines ? [] : $lines;
		$head  = array_map( static fn( $h ) => strtolower( trim( (string) $h ) ), str_getcsv( (string) array_shift( $lines ), ',', '"', '' ) );

		$url_col  = null;
		$cur_col  = null;
		$prev_col = null;
		foreach ( $head as $i => $h ) {
			if ( null === $url_col && preg_match( '/\b(page|pages|url|address)\b/', $h ) && ! str_contains( $h, 'click' ) ) {
				$url_col = $i;
			} elseif ( str_contains( $h, 'click' ) ) {
				if ( preg_match( '/previous|prior|before/', $h ) ) {
					$prev_col = $prev_col ?? $i;
				} elseif ( null === $cur_col ) {
					$cur_col = $i;
				} elseif ( null === $prev_col ) {
					$prev_col = $i;
				}
			}
		}
		if ( null === $url_col || null === $cur_col || null === $prev_col ) {
			throw new \InvalidArgumentException( 'Expected a page column and two Clicks columns (export Pages with Date → Compare).' );
		}
		$rows = [];
		foreach ( $lines as $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}
			$r      = str_getcsv( $line, ',', '"', '' );
			$rows[] = [
				'url'      => trim( (string) ( $r[ $url_col ] ?? '' ) ),
				'current'  => self::num( (string) ( $r[ $cur_col ] ?? '0' ) ),
				'previous' => self::num( (string) ( $r[ $prev_col ] ?? '0' ) ),
			];
		}
		return $rows;
	}

	/**
	 * "1,234" → 1234.
	 *
	 * @param string $value Cell.
	 */
	private static function num( string $value ): int {
		return (int) preg_replace( '/[^\d]/', '', $value );
	}

	/**
	 * Relative drop, 0…1 (0 when there was no traffic before or it grew).
	 *
	 * @param int $current  Clicks now.
	 * @param int $previous Clicks before.
	 */
	public static function drop( int $current, int $previous ): float {
		if ( $previous <= 0 || $current >= $previous ) {
			return 0.0;
		}
		return ( $previous - $current ) / $previous;
	}

	/**
	 * Rows whose clicks dropped by at least $threshold, worst first.
	 *
	 * @param array<int,array{url:string,current:int,previous:int}> $rows Parsed rows.
	 * @param float                                                 $threshold Minimum drop (0.30 = 30%).
	 * @param int                                                   $min_previous Ignore pages with fewer clicks before (noise).
	 * @return array<int,array{url:string,current:int,previous:int,drop:float}>
	 */
	public static function decayed( array $rows, float $threshold = 0.30, int $min_previous = 10 ): array {
		$out = [];
		foreach ( $rows as $row ) {
			$drop = self::drop( $row['current'], $row['previous'] );
			if ( $row['previous'] >= $min_previous && $drop >= $threshold - 1e-9 ) {
				$out[] = $row + [ 'drop' => round( $drop, 4 ) ];
			}
		}
		usort( $out, static fn( $a, $b ) => 0 !== ( $b['drop'] <=> $a['drop'] ) ? $b['drop'] <=> $a['drop'] : $b['previous'] <=> $a['previous'] );
		return $out;
	}

	/**
	 * Whether a review is overdue: reviewed + N calendar months ≤ now.
	 *
	 * @param int $reviewed Unix time of the last review.
	 * @param int $months   Cadence (3 money pages, 6 guides).
	 * @param int $now      Unix now.
	 */
	public static function review_overdue( int $reviewed, int $months, int $now ): bool {
		if ( $months <= 0 ) {
			return false;
		}
		$index = (int) gmdate( 'n', $reviewed ) - 1 + $months; // Months since January of the review year.
		$y     = (int) gmdate( 'Y', $reviewed ) + intdiv( $index, 12 );
		$m     = ( $index % 12 ) + 1;
		$d     = min( (int) gmdate( 'j', $reviewed ), (int) gmdate( 't', gmmktime( 0, 0, 0, $m, 1, $y ) ) );
		$due   = gmmktime( 0, 0, 0, $m, $d, $y );
		return $now >= $due;
	}
}
