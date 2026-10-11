<?php
/**
 * Staging lock (§5.3, §16): X-Robots-Tag noindex, nofollow on every response
 * (pages, REST, feeds, sitemaps, robots.txt) and HTTP basic auth when
 * STAGING_BASIC_AUTH_USER/PASS are set. Staging must never be indexed.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether this request may skip basic auth: WP-CLI, cron, or localhost
 * (deploy smoke tests run over SSH on the box).
 */
function td_seo_staging_auth_exempt(): bool {
	if ( ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() ) {
		return true;
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return in_array( $ip, [ '127.0.0.1', '::1' ], true );
}

/**
 * Check HTTP basic auth credentials against the staging constants.
 */
function td_seo_staging_auth_ok(): bool {
	$user = (string) STAGING_BASIC_AUTH_USER;
	$pass = (string) STAGING_BASIC_AUTH_PASS;
	// phpcs:disable WordPress.Security.ValidatedSanitizedInput -- compared with hash_equals only, never output or stored.
	$given_user = isset( $_SERVER['PHP_AUTH_USER'] ) ? (string) wp_unslash( $_SERVER['PHP_AUTH_USER'] ) : '';
	$given_pass = isset( $_SERVER['PHP_AUTH_PW'] ) ? (string) wp_unslash( $_SERVER['PHP_AUTH_PW'] ) : '';
	if ( '' === $given_user ) {
		$header = (string) ( $_SERVER['HTTP_AUTHORIZATION'] ?? ( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '' ) );
		// phpcs:enable
		if ( 0 === stripos( $header, 'basic ' ) ) {
			$decoded = (string) base64_decode( substr( $header, 6 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- HTTP basic auth.
			if ( false !== strpos( $decoded, ':' ) ) {
				[ $given_user, $given_pass ] = explode( ':', $decoded, 2 );
			}
		}
	}
	return hash_equals( $user, $given_user ) && hash_equals( $pass, $given_pass );
}

if ( 'staging' === td_environment() ) {
	add_action(
		'init',
		static function () {
			if ( ! headers_sent() ) {
				header( 'X-Robots-Tag: noindex, nofollow', true );
			}
			if ( '' === (string) STAGING_BASIC_AUTH_USER || '' === (string) STAGING_BASIC_AUTH_PASS || td_seo_staging_auth_exempt() ) {
				return;
			}
			if ( td_seo_staging_auth_ok() ) {
				return;
			}
			nocache_headers();
			header( 'WWW-Authenticate: Basic realm="TechDella staging", charset="UTF-8"' );
			status_header( 401 );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'Authentication required.';
			exit;
		},
		0
	);

	// REST responses are built later; make sure the header is on them too.
	add_filter(
		'rest_post_dispatch',
		static function ( $response ) {
			if ( $response instanceof WP_HTTP_Response ) {
				$response->header( 'X-Robots-Tag', 'noindex, nofollow' );
			}
			return $response;
		}
	);

	// Static files (uploads, PDFs) are served by the web server: add the header there too.
	add_filter(
		'mod_rewrite_rules',
		static function ( $rules ) {
			return $rules . "\n<IfModule mod_headers.c>\nHeader always set X-Robots-Tag \"noindex, nofollow\"\n</IfModule>\n";
		}
	);
}
