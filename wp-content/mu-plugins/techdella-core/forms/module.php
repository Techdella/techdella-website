<?php
/**
 * Forms → Startbuddi (§11). WordPress only collects, validates and forwards;
 * nothing is stored permanently (D7).
 *
 * Public API (other modules and blocks use only these):
 *   td_form_render( string $key, array $args = [] ): string
 *   td_form_schema( ?string $key = null ): array
 *   td_form_endpoint_configured( string $key ): bool
 *
 * Files:
 *   schema.php     field schema + Startbuddi field mapping (the one place)
 *   src/*.php      pure classes: Validator, Payload, Token, Crypto, Retry
 *   delivery.php   forwarder, encrypted retry queue, WP-cron, admin email
 *   rest.php       POST /techdella/v1/submit/{key}, GET /techdella/v1/form-token
 *   render.php     td_form_render() markup
 *   thank-you.php  thank-you pages (td_setup), noindex, generate_lead event, lead-magnet download
 *   admin.php      admin notices (Turnstile / endpoints / failed deliveries)
 *   cli.php        wp td forms:* commands
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/src/Validator.php';
require_once __DIR__ . '/src/Payload.php';
require_once __DIR__ . '/src/Token.php';
require_once __DIR__ . '/src/Crypto.php';
require_once __DIR__ . '/src/Retry.php';
require_once __DIR__ . '/delivery.php';
require_once __DIR__ . '/rest.php';
require_once __DIR__ . '/render.php';
require_once __DIR__ . '/thank-you.php';
require_once __DIR__ . '/admin.php';
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/cli.php';
}

/**
 * Field schema for one form (with its keys) or all forms.
 *
 * Each form: name (Startbuddi form name), sites[], nurture, title, intro, submit,
 * success_title, success, steps?, fields{ name => definition }.
 * Filter: td_form_schema ( $schema, $key ).
 *
 * @param string|null $key Form key, or null for all.
 * @return array<string,mixed>
 */
function td_form_schema( ?string $key = null ): array {
	static $all = null;
	if ( null === $all ) {
		$all = [];
		foreach ( td_forms_definitions() as $k => $def ) {
			$all[ $k ] = (array) apply_filters( 'td_form_schema', $def, $k );
		}
	}
	if ( null === $key ) {
		return $all;
	}
	return $all[ $key ] ?? [];
}

/**
 * The SB_ENDPOINT_<KEY> URL for a form ('' when not set).
 *
 * @param string $key Form key.
 */
function td_form_endpoint( string $key ): string {
	$const = 'SB_ENDPOINT_' . strtoupper( $key );
	$url   = defined( $const ) ? (string) constant( $const ) : '';
	return (string) apply_filters( 'td_form_endpoint', $url, $key );
}

/**
 * Whether the form's Startbuddi endpoint is configured (a valid http(s) URL).
 *
 * @param string $key Form key.
 */
function td_form_endpoint_configured( string $key ): bool {
	$url = td_form_endpoint( $key );
	return '' !== $url && (bool) preg_match( '#^https?://#i', $url ) && false !== filter_var( $url, FILTER_VALIDATE_URL );
}

/**
 * Keys whose thank-you pages exist on this site (§11.2: keep only slugs live forms use).
 *
 * @param string|null $site Site key (default: this site).
 * @return string[]
 */
function td_forms_keys_for_site( ?string $site = null ): array {
	$site = $site ?? td_site();
	$keys = [];
	foreach ( td_form_schema() as $key => $def ) {
		if ( in_array( $site, (array) ( $def['sites'] ?? [] ), true ) ) {
			$keys[] = $key;
		}
	}
	return (array) apply_filters( 'td_forms_site_keys', $keys, $site );
}

/**
 * Token signer (key derived from wp_salt('nonce'); see src/Token.php for the design).
 */
function td_forms_token(): \TechDella\Core\Forms\Token {
	static $token = null;
	if ( null === $token ) {
		$token = new \TechDella\Core\Forms\Token( hash_hmac( 'sha256', 'td-forms-token|v1', wp_salt( 'nonce' ) ) );
	}
	return $token;
}

/**
 * Queue encryption (key derived from wp_salt('secure_auth')).
 */
function td_forms_crypto(): \TechDella\Core\Forms\Crypto {
	static $crypto = null;
	if ( null === $crypto ) {
		$crypto = new \TechDella\Core\Forms\Crypto( \TechDella\Core\Forms\Crypto::derive_key( wp_salt( 'secure_auth' ) ) );
	}
	return $crypto;
}

/**
 * Slug used in thank-you URLs: studios_brief → studios-brief.
 *
 * @param string $key Form key.
 */
function td_forms_slug( string $key ): string {
	return str_replace( '_', '-', $key );
}

/**
 * Thank-you page path for a key, e.g. /thank-you/studios-brief/ (relative).
 *
 * @param string $key Form key.
 */
function td_forms_thankyou_path( string $key ): string {
	$path = '/thank-you/' . td_forms_slug( $key ) . '/';
	if ( post_type_exists( 'thank-you' ) ) {
		$post = get_page_by_path( td_forms_slug( $key ), OBJECT, 'thank-you' );
		if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
			$link = (string) get_permalink( $post );
			$rel  = (string) wp_make_link_relative( $link );
			$path = '' !== $rel ? $rel : $path;
		}
	}
	return (string) apply_filters( 'td_forms_thankyou_path', $path, $key );
}

/**
 * Production domain of this site (meta.site), e.g. techdella.com.
 */
function td_forms_site_domain(): string {
	$domains = td_site_domains();
	return (string) wp_parse_url( $domains[ td_site() ] ?? $domains['marketing'], PHP_URL_HOST );
}

/**
 * Hosts allowed as Origin / page_url / redirect targets: this site, the other
 * two sites (local + production).
 *
 * @return string[]
 */
function td_forms_allowed_hosts(): array {
	$hosts = [ (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ];
	foreach ( [ 'marketing', 'journal', 'studios' ] as $site ) {
		$hosts[] = (string) wp_parse_url( td_url( $site, '/' ), PHP_URL_HOST );
	}
	foreach ( td_site_domains() as $url ) {
		$host    = (string) wp_parse_url( $url, PHP_URL_HOST );
		$hosts[] = $host;
		$hosts[] = 'www.' . $host;
	}
	return array_values( array_unique( array_filter( (array) apply_filters( 'td_forms_allowed_hosts', $hosts ) ) ) );
}

/**
 * Whether a URL points at one of our hosts (port ignored: local stack uses ports).
 *
 * @param string $url URL.
 */
function td_forms_is_own_url( string $url ): bool {
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	return '' !== $host && in_array( strtolower( $host ), array_map( 'strtolower', td_forms_allowed_hosts() ), true );
}

/**
 * Tell page caches (LiteSpeed) and browsers not to cache this response —
 * used where a response carries per-visitor data (no-JS error flash, download grant).
 */
function td_forms_nocache(): void {
	if ( ! headers_sent() ) {
		nocache_headers();
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
	}
	do_action( 'litespeed_control_set_nocache', 'techdella forms: per-visitor response' );
}
