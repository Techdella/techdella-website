<?php
/**
 * AdSense policy (§12.2, D8): where ads may load, the ads.txt line, slot
 * limits. Pure PHP, unit-tested.
 */

namespace TechDella\Core\Ads;

defined( 'ABSPATH' ) || exit;

/**
 * Ads rules.
 */
final class Policy {

	public const POSITIONS = [ 'result', 'content', 'sidebar' ];
	public const MAX_SLOTS = 3;
	// Google's TAG-ID certification authority ID for AdSense.
	public const GOOGLE_CERT_ID = 'f08c47fec0942fa0';

	/**
	 * The numeric part of a publisher ID ("ca-pub-123…", "pub-123…"), or ''.
	 *
	 * @param string $publisher_id ADSENSE_PUBLISHER_ID.
	 */
	public static function publisher_digits( string $publisher_id ): string {
		return preg_match( '/^(?:ca-)?pub-(\d{10,20})$/', trim( $publisher_id ), $m ) ? $m[1] : '';
	}

	/**
	 * Client ID for data-ad-client ("ca-pub-…"), or ''.
	 *
	 * @param string $publisher_id ADSENSE_PUBLISHER_ID.
	 */
	public static function client( string $publisher_id ): string {
		$digits = self::publisher_digits( $publisher_id );
		return '' === $digits ? '' : 'ca-pub-' . $digits;
	}

	/**
	 * The ads.txt line, or '' when the ID is missing/invalid.
	 *
	 * @param string $publisher_id ADSENSE_PUBLISHER_ID.
	 */
	public static function ads_txt_line( string $publisher_id ): string {
		$digits = self::publisher_digits( $publisher_id );
		return '' === $digits ? '' : sprintf( 'google.com, pub-%s, DIRECT, %s', $digits, self::GOOGLE_CERT_ID );
	}

	/**
	 * Whether a page may carry ads.
	 *
	 * @param string $site    marketing|journal|studios.
	 * @param string $context tool (td_tool single) | tools_hub | journal_post | other.
	 * @param bool   $journal Journal ads switched on (td_ads_journal, off at launch).
	 */
	public static function enabled( string $site, string $context, bool $journal = false ): bool {
		if ( 'marketing' === $site ) {
			return in_array( $context, [ 'tool', 'tools_hub' ], true );
		}
		if ( 'journal' === $site ) {
			return $journal && 'journal_post' === $context;
		}
		return false;
	}

	/**
	 * Whether another slot may be printed.
	 *
	 * @param string $position Slot position.
	 * @param int    $printed  Slots already printed on this page.
	 */
	public static function slot_allowed( string $position, int $printed ): bool {
		return in_array( $position, self::POSITIONS, true ) && $printed < self::MAX_SLOTS;
	}
}
