<?php
/**
 * Refresh cadence (§10.1): money pages quarterly, guides every 6 months,
 * counted from last_reviewed (or the last update when nothing was reviewed).
 * Pure PHP, unit-tested.
 */

namespace TechDella\Core\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Refresh-due calculation.
 */
final class Refresh {

	/**
	 * Parse a review date as ACF stores it (Ymd) or as returned (Y-m-d).
	 *
	 * @param mixed $value Date value.
	 * @return int|null Unix timestamp (UTC midnight) or null.
	 */
	public static function parse_date( $value ): ?int {
		$value = trim( (string) $value );
		if ( preg_match( '/^(\d{4})-?(\d{2})-?(\d{2})/', $value, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
			return gmmktime( 0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1] );
		}
		return null;
	}

	/**
	 * The date a review falls due: reviewed date + N calendar months
	 * (clamped to the month's last day, so 31 Aug + 6 months = 28/29 Feb).
	 *
	 * @param int $reviewed Timestamp.
	 * @param int $months   Cadence in months.
	 */
	public static function due_at( int $reviewed, int $months ): int {
		$index = (int) gmdate( 'n', $reviewed ) - 1 + $months; // Months since January of the review year.
		$y     = (int) gmdate( 'Y', $reviewed ) + intdiv( $index, 12 );
		$m     = ( $index % 12 ) + 1;
		$d     = min( (int) gmdate( 'j', $reviewed ), (int) gmdate( 't', gmmktime( 0, 0, 0, $m, 1, $y ) ) );
		return gmmktime( 0, 0, 0, $m, $d, $y );
	}

	/**
	 * Refresh status of one item.
	 *
	 * @param mixed  $last_reviewed last_reviewed field (Ymd / Y-m-d / empty).
	 * @param string $modified      post_modified_gmt (Y-m-d H:i:s), the fallback.
	 * @param int    $months        Cadence (0 = never due).
	 * @param int    $now           Current timestamp.
	 * @return array{due:bool,due_at:int,days_overdue:int,reviewed:bool}
	 */
	public static function status( $last_reviewed, string $modified, int $months, int $now ): array {
		$reviewed = self::parse_date( $last_reviewed );
		$base     = $reviewed ?? self::parse_date( $modified ) ?? $now;
		if ( $months <= 0 ) {
			return [
				'due'          => false,
				'due_at'       => 0,
				'days_overdue' => 0,
				'reviewed'     => null !== $reviewed,
			];
		}
		$due_at = self::due_at( $base, $months );
		return [
			'due'          => $now >= $due_at,
			'due_at'       => $due_at,
			'days_overdue' => $now >= $due_at ? intdiv( $now - $due_at, 86400 ) : 0,
			'reviewed'     => null !== $reviewed,
		];
	}

	/**
	 * Sort due items, most overdue first, and keep the top N.
	 *
	 * @template T of array{days_overdue:int}
	 * @param array<int,T> $items Items with status merged in.
	 * @param int          $limit Max items.
	 * @return array<int,T>
	 */
	public static function top( array $items, int $limit = 10 ): array {
		usort( $items, static fn( $a, $b ) => $b['days_overdue'] <=> $a['days_overdue'] );
		return array_slice( $items, 0, $limit );
	}
}
