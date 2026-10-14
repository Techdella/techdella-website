<?php
/**
 * Cache-safe form tokens (§11.2 step 1).
 *
 * LiteSpeed caches pages for logged-out visitors, so a WP nonce in the HTML
 * would go stale. Instead each form carries an HMAC-signed timestamp token:
 *
 *   v1.<form key>.<issued unix>.<random>.<hmac-sha256>
 *
 * - The HTML renders one (valid for MAX_AGE_RENDERED, longer than any page-cache TTL),
 *   so the no-JS POST works from a cached page.
 * - With JS, view.js fetches a fresh one from GET /techdella/v1/form-token on the
 *   first interaction; its issue time is also the min-time (≥ 3 s) clock.
 * - The token is bound to the form key and signed with a key derived from
 *   wp_salt('nonce'), so it cannot be forged or reused for another form.
 *
 * The class also signs short-lived values (no-JS error flash ids, lead-magnet
 * download grants): "<base64url data>.<expires>.<hmac>".
 *
 * Pure PHP, unit-tested.
 */

namespace TechDella\Core\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Token signer/verifier.
 */
final class Token {

	public const MIN_AGE          = 3;
	public const MAX_AGE_FETCHED  = 6 * 3600;
	public const MAX_AGE_RENDERED = 10 * 86400;

	/**
	 * Signing secret.
	 *
	 * @var string
	 */
	private string $secret;

	/**
	 * Constructor.
	 *
	 * @param string $secret Secret (≥ 16 bytes).
	 */
	public function __construct( string $secret ) {
		$this->secret = $secret;
	}

	/**
	 * Issue a token for a form.
	 *
	 * @param string $form Form key.
	 * @param int    $now  Unix time.
	 */
	public function issue( string $form, int $now ): string {
		$rand = bin2hex( random_bytes( 8 ) );
		$body = 'v1.' . $form . '.' . $now . '.' . $rand;
		return $body . '.' . $this->mac( $body );
	}

	/**
	 * Verify a token. Returns '' when valid, else an error code:
	 * malformed|signature|form|future|expired|too_fast.
	 *
	 * @param string $token   Token.
	 * @param string $form    Expected form key.
	 * @param int    $now     Unix time.
	 * @param int    $max_age Max age in seconds.
	 * @param int    $min_age Min age in seconds (min-time check).
	 */
	public function verify( string $token, string $form, int $now, int $max_age = self::MAX_AGE_RENDERED, int $min_age = self::MIN_AGE ): string {
		$parts = explode( '.', $token );
		if ( 5 !== count( $parts ) || 'v1' !== $parts[0] || ! ctype_digit( $parts[2] ) ) {
			return 'malformed';
		}
		$body = implode( '.', array_slice( $parts, 0, 4 ) );
		if ( ! hash_equals( $this->mac( $body ), $parts[4] ) ) {
			return 'signature';
		}
		if ( $parts[1] !== $form ) {
			return 'form';
		}
		$age = $now - (int) $parts[2];
		if ( $age < -60 ) {
			return 'future';
		}
		if ( $age > $max_age ) {
			return 'expired';
		}
		if ( $age < $min_age ) {
			return 'too_fast';
		}
		return '';
	}

	/**
	 * Issue time of a token (0 when malformed). Does not verify.
	 *
	 * @param string $token Token.
	 */
	public static function issued_at( string $token ): int {
		$parts = explode( '.', $token );
		return ( 5 === count( $parts ) && ctype_digit( $parts[2] ) ) ? (int) $parts[2] : 0;
	}

	/**
	 * Sign a short value with an expiry.
	 *
	 * @param string $data    Value.
	 * @param int    $expires Unix expiry.
	 */
	public function sign( string $data, int $expires ): string {
		$body = rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' ) . '.' . $expires; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- URL-safe token encoding.
		return $body . '.' . substr( $this->mac( 's|' . $body ), 0, 32 );
	}

	/**
	 * Open a signed value; null when tampered or expired.
	 *
	 * @param string $signed Signed value.
	 * @param int    $now    Unix time.
	 */
	public function open( string $signed, int $now ): ?string {
		$parts = explode( '.', $signed );
		if ( 3 !== count( $parts ) || ! ctype_digit( $parts[1] ) ) {
			return null;
		}
		$body = $parts[0] . '.' . $parts[1];
		if ( ! hash_equals( substr( $this->mac( 's|' . $body ), 0, 32 ), $parts[2] ) || (int) $parts[1] < $now ) {
			return null;
		}
		$data = base64_decode( strtr( $parts[0], '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return false === $data ? null : $data;
	}

	/**
	 * HMAC.
	 *
	 * @param string $body Data.
	 */
	private function mac( string $body ): string {
		return hash_hmac( 'sha256', $body, $this->secret );
	}
}
