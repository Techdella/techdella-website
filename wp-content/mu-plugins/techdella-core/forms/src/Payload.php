<?php
/**
 * Build the JSON payload forwarded to Startbuddi (§11.3) and sign it.
 *
 * Shape: { form, ...fields (Startbuddi names from schema.php), meta: {…}, test? }.
 * Pure PHP, unit-tested.
 */

namespace TechDella\Core\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Payload builder.
 */
final class Payload {

	/**
	 * UTM keys kept from the td_utm_first / td_utm_last cookies (set by main.js).
	 */
	public const UTM_KEYS = [ 'source', 'medium', 'campaign', 'term', 'content' ];

	/**
	 * Build the payload.
	 *
	 * @param string                            $key    Form key.
	 * @param array<string,array<string,mixed>> $fields Field schema.
	 * @param array<string,mixed>               $clean  Validated values.
	 * @param array<string,mixed>               $meta   Enrichment (site, page_url, referrer, utm_*, submitted_at…).
	 * @param bool                              $test   Mark as a test submission.
	 * @return array<string,mixed>
	 */
	public static function build( string $key, array $fields, array $clean, array $meta, bool $test = false ): array {
		$out = [ 'form' => $key ];
		foreach ( $fields as $name => $field ) {
			$target = (string) ( $field['sb'] ?? $name );
			$value  = $clean[ $name ] ?? null;
			if ( ! empty( $field['meta'] ) ) {
				$meta[ $target ] = 'checkbox' === ( $field['type'] ?? '' ) ? (bool) $value : $value;
				continue;
			}
			$out[ $target ] = $value;
		}
		// Consent flags are always present (false when the form has no checkbox).
		$meta        += [ 'consent_marketing' => false ];
		$out['meta'] = $meta;
		if ( $test ) {
			$out['test'] = true;
		}
		return $out;
	}

	/**
	 * Parse a UTM cookie (JSON object) into the allowed keys only.
	 *
	 * @param string|null $raw Cookie value (already URL-decoded by PHP).
	 * @return array<string,string>
	 */
	public static function utm( ?string $raw ): array {
		if ( null === $raw || '' === $raw || strlen( $raw ) > 2000 ) {
			return [];
		}
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			$data = json_decode( rawurldecode( $raw ), true );
		}
		if ( ! is_array( $data ) ) {
			return [];
		}
		$out = [];
		foreach ( self::UTM_KEYS as $k ) {
			$v = $data[ $k ] ?? ( $data[ 'utm_' . $k ] ?? null );
			if ( is_scalar( $v ) && '' !== trim( (string) $v ) ) {
				$out[ $k ] = mb_substr( Validator::text( $v, false ), 0, 120 );
			}
		}
		return $out;
	}

	/**
	 * Keep a URL only when it is http(s); drop the fragment and our own
	 * internal query args (td_fe, dl). Max 500 chars.
	 *
	 * @param string $url Raw URL.
	 */
	public static function clean_url( string $url ): string {
		$url = trim( $url );
		if ( '' === $url || strlen( $url ) > 2000 || ! preg_match( '#^https?://#i', $url ) || false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}
		$url = (string) preg_replace( '/#.*$/', '', $url );
		$url = (string) preg_replace( '/([?&])(td_fe|dl)=[^&]*(&|$)/', '$1', $url );
		$url = rtrim( $url, '?&' );
		return substr( $url, 0, 500 );
	}

	/**
	 * HMAC-SHA256 signature header value for a body:
	 * "t=<unix>,v1=<hex hmac of "<t>.<body>">" [CONFIRM Startbuddi signed requests].
	 *
	 * @param string $body   Exact request body.
	 * @param string $secret Shared secret.
	 * @param int    $time   Unix time.
	 */
	public static function signature( string $body, string $secret, int $time ): string {
		return 't=' . $time . ',v1=' . hash_hmac( 'sha256', $time . '.' . $body, $secret );
	}
}
