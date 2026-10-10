<?php
/**
 * Ads, consent and analytics (§12.2, §12.3, §8.4, D8).
 *
 * Public API:
 *   td_ads_enabled(): bool               whether this page may load AdSense
 *   td_ad_slot( $position ): string      manual unit (result|content|sidebar), reserved height
 *
 * Loading sequence (every page):
 *   1. <head>, before any Google tag: inline loader (consent.min.js, < 2 KB) sets Consent
 *      Mode v2 defaults: denied in the EEA/UK/CH (explicit region list), granted elsewhere,
 *      wait_for_update 500 ms.
 *   2. Google's certified CMP (Privacy & messaging, fundingchoicesmessages.google.com) loads
 *      async when ADSENSE_PUBLISHER_ID is set: at once on ad pages; on other pages only for
 *      visitors whose time zone is in Europe (where consent is needed for analytics), on idle.
 *   3. GA4 (gtag.js), GTM, Clarity: injected only after analytics consent (TCF purpose 1, or
 *      outside the consent region), on idle. Nothing is render-blocking.
 *   4. AdSense (ad pages only): blocks/ad-slot/view.js injects adsbygoogle.js after the CMP
 *      allows ads AND a slot is within ~600px of the viewport. No Auto ads.
 *
 * Files: src/Policy.php (pure), consent.js (source), consent.min.js (printed).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Ads\Policy;

require_once __DIR__ . '/src/Policy.php';

/**
 * EEA + UK + Switzerland (ISO 3166-1 alpha-2), where consent defaults to denied.
 *
 * @return string[]
 */
function td_consent_regions(): array {
	return (array) apply_filters(
		'td_consent_regions',
		[ 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'IS', 'LI', 'NO', 'GB', 'CH' ]
	);
}

/**
 * Whether this page may carry ads: Marketing td_tool singles and the /tools/ hub only.
 * Journal posts are off at launch (filter td_ads_journal, [CONFIRM later]).
 */
function td_ads_enabled(): bool {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$context = 'other';
	if ( is_singular( 'td_tool' ) ) {
		$context = 'tool';
		$tool_id = (int) get_queried_object_id();
		// td_tool.ad_slots defaults to on (ACF default 1); only an explicitly saved "off" disables ads.
		if ( metadata_exists( 'post', $tool_id, 'ad_slots' ) && ! td_field( 'ad_slots', $tool_id ) ) {
			$context = 'other'; // Per-tool switch (td_tool.ad_slots) turned off.
		}
	} elseif ( ( post_type_exists( 'td_tool' ) && is_post_type_archive( 'td_tool' ) ) || is_page( 'tools' ) ) {
		$context = 'tools_hub';
	} elseif ( is_singular( 'post' ) ) {
		$context = 'journal_post';
	}
	$enabled = Policy::enabled( td_site(), $context, (bool) apply_filters( 'td_ads_journal', false ) )
		&& '' !== Policy::client( (string) ADSENSE_PUBLISHER_ID );
	$cache   = (bool) apply_filters( 'td_ads_enabled', $enabled, $context );
	return $cache;
}

/**
 * Manual AdSense unit with reserved height and an "Advertisement" label.
 * Outputs nothing when ads are off for the page, the publisher ID is unset,
 * or 3 units were already printed.
 *
 * @param string $position result|content|sidebar.
 */
function td_ad_slot( string $position ): string {
	static $printed = 0;
	if ( ! td_ads_enabled() || ! Policy::slot_allowed( $position, $printed ) ) {
		return '';
	}
	$client = Policy::client( (string) ADSENSE_PUBLISHER_ID );
	$slots  = (array) get_option( 'td_adsense_slots', [] );
	$slot   = preg_replace( '/\D/', '', (string) ( $slots[ $position ] ?? '' ) );
	if ( '' === $slot && td_is_production() ) {
		return ''; // No unit ID yet: nothing in production.
	}
	++$printed;
	$out  = '<aside class="td-ad-slot td-ad-slot--' . esc_attr( $position ) . '" data-td-ad="' . esc_attr( $position ) . '" aria-label="' . esc_attr__( 'Advertisement', 'techdella' ) . '">';
	$out .= '<p class="td-ad-slot__label">' . esc_html__( 'Advertisement', 'techdella' ) . '</p>';
	if ( '' === $slot ) {
		// Non-production preview: the reserved box without a unit (set option td_adsense_slots[<position>]).
		$out .= '<div class="td-ad-slot__unit td-ad-slot__unit--pending" data-ad-pending="1"></div>';
	} else {
		$out .= '<ins class="adsbygoogle td-ad-slot__unit" style="display:block" data-ad-client="' . esc_attr( $client ) . '" data-ad-slot="' . esc_attr( (string) $slot ) . '"';
		$out .= 'sidebar' === $position ? ' data-ad-format="vertical"' : ' data-ad-format="auto" data-full-width-responsive="true"';
		$out .= '></ins>';
	}
	return $out . '</aside>';
}

// 1. Consent defaults + loader, first thing in <head>.
add_action(
	'wp_head',
	static function () {
		$publisher = Policy::publisher_digits( (string) ADSENSE_PUBLISHER_ID );
		$ga        = preg_match( '/^G-[A-Z0-9]+$/', (string) GA4_ID ) ? (string) GA4_ID : '';
		$gtm       = preg_match( '/^GTM-[A-Z0-9]+$/', (string) GTM_ID ) ? (string) GTM_ID : '';
		$clarity   = preg_match( '/^[a-z0-9]{6,16}$/', (string) CLARITY_ID ) ? (string) CLARITY_ID : '';
		if ( '' === $publisher && '' === $ga && '' === $gtm && '' === $clarity ) {
			return;
		}
		$file = __DIR__ . '/consent.min.js';
		$js   = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$js   = trim( (string) preg_replace( '#^/\*.*?\*/\s*#s', '', $js ) );
		if ( '' === $js ) {
			return;
		}
		$config = [
			'r'   => td_consent_regions(),
			'p'   => '' !== $publisher ? 'pub-' . $publisher : '',
			'ad'  => td_ads_enabled(),
			'all' => (bool) apply_filters( 'td_cmp_everywhere', false ),
			'ga'  => $ga,
			'gtm' => $gtm,
			'cl'  => $clarity,
		];
		$js     = str_replace( 'window.__tdc', (string) wp_json_encode( $config ), $js );
		echo '<script id="td-consent">' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- our static script + JSON of validated IDs.
	},
	0
);

// No AdSense Auto ads, ever: we never print the page-level tag; this filter lets
// other code check the rule.
add_filter( 'td_adsense_auto_ads', '__return_false' );

// ads.txt at the Marketing root (§12.2); 404 elsewhere or when the ID is unset.
add_action(
	'parse_request',
	static function () {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		if ( '/ads.txt' !== $uri ) {
			return;
		}
		$line = td_is_site( 'marketing' ) ? Policy::ads_txt_line( (string) ADSENSE_PUBLISHER_ID ) : '';
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		if ( '' === $line ) {
			status_header( 404 );
			nocache_headers();
			echo "Not found\n";
			exit;
		}
		status_header( 200 );
		header( 'Cache-Control: public, max-age=3600' );
		echo esc_html( $line ) . "\n";
		exit;
	},
	0
);

// Keep WordPress from adding a trailing slash redirect to /ads.txt/.
add_filter(
	'redirect_canonical',
	static function ( $redirect, $requested ) {
		return str_ends_with( (string) wp_parse_url( (string) $requested, PHP_URL_PATH ), '/ads.txt' ) ? false : $redirect;
	},
	10,
	2
);
