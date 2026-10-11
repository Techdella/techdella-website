<?php
/**
 * Prices edited in Growth → Prices: parse what an editor typed, format it, and fill naira
 * from dollars (NGN = USD × 200, never below ₦300,000 — docs/PRICING.md). Pure PHP, unit-tested.
 */

namespace TechDella\Core\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Price parsing and merging.
 */
final class Prices {

	public const NGN_RATE  = 200;
	public const NGN_FLOOR = 300000;

	/**
	 * A whole amount from what was typed: "1500", "$1,500", "₦300,000", "1500.00". Null if none.
	 *
	 * @param string $typed Input.
	 */
	public static function parse( string $typed ): ?int {
		$typed = trim( $typed );
		if ( '' === $typed ) {
			return null;
		}
		$typed = (string) preg_replace( '/\.\d{1,2}$/', '', str_replace( [ ',', ' ' ], '', $typed ) );
		if ( ! preg_match( '/^(?:\$|₦|USD|NGN|N)?(\d{1,9})$/iu', $typed, $m ) ) {
			return null;
		}
		$amount = (int) $m[1];
		return $amount > 0 ? $amount : null;
	}

	/**
	 * "$1,500".
	 *
	 * @param int $amount Dollars.
	 */
	public static function usd( int $amount ): string {
		return '$' . number_format( $amount );
	}

	/**
	 * "₦300,000".
	 *
	 * @param int $amount Naira.
	 */
	public static function ngn( int $amount ): string {
		return '₦' . number_format( $amount );
	}

	/**
	 * The naira price for a dollar price when none is set.
	 *
	 * @param int $usd Dollars.
	 */
	public static function ngn_from_usd( int $usd ): int {
		return max( self::NGN_FLOOR, $usd * self::NGN_RATE );
	}

	/**
	 * Saved prices over the defaults. Saved rows are [ 'usd' => int, 'ngn' => int|null ]; a row
	 * without dollars is ignored, a row without naira gets the calculated naira.
	 *
	 * @param array<string,array{0:string,1:string}> $defaults Key => [ USD, NGN ] strings.
	 * @param mixed                                  $saved    The stored option.
	 * @return array<string,array{0:string,1:string}>
	 */
	public static function merge( array $defaults, $saved ): array {
		if ( ! is_array( $saved ) ) {
			return $defaults;
		}
		foreach ( $defaults as $key => $pair ) {
			$row = $saved[ $key ] ?? null;
			if ( ! is_array( $row ) || empty( $row['usd'] ) ) {
				continue;
			}
			$usd              = (int) $row['usd'];
			$ngn              = ! empty( $row['ngn'] ) ? (int) $row['ngn'] : self::ngn_from_usd( $usd );
			$defaults[ $key ] = [ self::usd( $usd ), self::ngn( $ngn ) ];
		}
		return $defaults;
	}
}
