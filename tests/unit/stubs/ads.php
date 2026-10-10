<?php
/**
 * Loader for the ads module's pure classes (unit tests).
 */

if ( ! function_exists( 'td_ads_unit_load' ) ) {
	/**
	 * Load ads/src/Policy.php.
	 */
	function td_ads_unit_load(): void {
		require_once dirname( __DIR__, 3 ) . '/wp-content/mu-plugins/techdella-core/ads/src/Policy.php';
	}
}
