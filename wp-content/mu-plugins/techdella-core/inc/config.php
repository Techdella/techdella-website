<?php
/**
 * Config: turn environment variables into PHP constants (§4.3).
 *
 * Production sets these as constants in wp-config.php (or as env vars in
 * hPanel); a constant that is already defined always wins over the env var.
 * Secrets never live in Git.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read an environment variable, with a default for missing/empty values.
 *
 * @param string $key     Variable name.
 * @param string $fallback Fallback.
 */
function td_env( string $key, string $fallback = '' ): string {
	if ( defined( $key ) ) {
		return (string) constant( $key );
	}
	$value = getenv( $key );
	return ( false === $value || '' === $value ) ? $fallback : (string) $value;
}

( static function () {
	$keys = [
		'TD_SITE',
		'TD_ENV',
		'TD_URL_MARKETING',
		'TD_URL_JOURNAL',
		'TD_URL_STUDIOS',
		'SB_ENDPOINT_CONTACT',
		'SB_ENDPOINT_AUDIT',
		'SB_ENDPOINT_STUDIOS_BRIEF',
		'SB_ENDPOINT_NEWSLETTER',
		'SB_ENDPOINT_LEAD_MAGNET',
		'SB_ENDPOINT_TOOL_GATE',
		'SB_ENDPOINT_POPUP_EXIT',
		'SB_ENDPOINT_POPUP_SCROLL',
		'SB_ENDPOINT_MVP_CALC',
		'SB_ENDPOINT_GUEST_POST',
		'SB_SHARED_SECRET',
		'TURNSTILE_SITE_KEY',
		'TURNSTILE_SECRET_KEY',
		'ADSENSE_PUBLISHER_ID',
		'GA4_ID',
		'GTM_ID',
		'CLARITY_ID',
		'AI_API_KEY',
		'AI_MODEL',
		'STAGING_BASIC_AUTH_USER',
		'STAGING_BASIC_AUTH_PASS',
		'TD_SMTP_HOST',
		'TD_SMTP_PORT',
	];
	foreach ( $keys as $key ) {
		if ( ! defined( $key ) ) {
			$value = getenv( $key );
			define( $key, false === $value ? '' : (string) $value );
		}
	}
} )();

if ( ! in_array( TD_SITE, [ 'marketing', 'journal', 'studios' ], true ) ) {
	// Unknown/missing site: behave as the marketing site, but say so in the log.
	error_log( 'TechDella: TD_SITE is not set to marketing|journal|studios; defaulting to marketing.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

// Theme/plugin file editing is off everywhere (§14.2).
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}
