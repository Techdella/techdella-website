<?php
/**
 * Retry schedule and rate limiting (§11.2). Pure PHP, unit-tested.
 *
 * Delivery attempts: the first try happens during the request. After failure
 * n (n = 1…5) the next try is scheduled DELAYS[n-1] later: 1m, 5m, 30m, 2h, 12h.
 * After the 5th failure the one admin email goes out; the 12 h retry is the last
 * automatic one. A row that still fails is kept (encrypted) for `wp td forms:retry`
 * until it is purged 14 days after it was created.
 */

namespace TechDella\Core\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Retry + rate-limit maths.
 */
final class Retry {

	public const DELAYS       = [ 60, 300, 1800, 7200, 43200 ];
	public const NOTIFY_AFTER = 5;
	public const PURGE_AFTER  = 14 * 86400;

	/**
	 * Seconds until the next attempt after $failures failed attempts, or null to stop.
	 *
	 * @param int $failures Failed attempts so far (≥ 1).
	 */
	public static function next_delay( int $failures ): ?int {
		return self::DELAYS[ $failures - 1 ] ?? null;
	}

	/**
	 * Whether this failure count triggers the (single) admin email.
	 *
	 * @param int $failures Failed attempts so far.
	 */
	public static function should_notify( int $failures ): bool {
		return self::NOTIFY_AFTER === $failures;
	}

	/**
	 * Whether a row created at $created is due for purging.
	 *
	 * @param int $created Unix created time.
	 * @param int $now     Unix now.
	 */
	public static function should_purge( int $created, int $now ): bool {
		return $now - $created >= self::PURGE_AFTER;
	}

	/**
	 * Fixed-window rate limit step.
	 *
	 * @param array{0:int,1:int}|null $state  [ window start, count ] or null.
	 * @param int                     $now    Unix now.
	 * @param int                     $limit  Max hits per window.
	 * @param int                     $window Window seconds.
	 * @return array{0: bool, 1: array{0:int,1:int}} [ allowed, new state ].
	 */
	public static function rate_limit( ?array $state, int $now, int $limit = 5, int $window = 60 ): array {
		if ( ! $state || $now - (int) $state[0] >= $window ) {
			$state = [ $now, 0 ];
		}
		if ( (int) $state[1] >= $limit ) {
			return [ false, [ (int) $state[0], (int) $state[1] ] ];
		}
		return [ true, [ (int) $state[0], (int) $state[1] + 1 ] ];
	}

	/**
	 * Classify a delivery failure without leaking response bodies (no PII).
	 *
	 * @param int    $status   HTTP status (0 when no response).
	 * @param string $wp_error WP_Error code, if any.
	 */
	public static function error_class( int $status, string $wp_error = '' ): string {
		if ( '' !== $wp_error ) {
			return 'wp_error:' . substr( (string) preg_replace( '/[^a-z0-9_]/i', '', $wp_error ), 0, 60 );
		}
		return 'http_' . $status;
	}
}
