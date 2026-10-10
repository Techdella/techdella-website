<?php
/**
 * Loader for the popups module's pure classes (unit tests).
 */

if ( ! function_exists( 'td_popups_unit_load' ) ) {
	/**
	 * Load popups/src/Targeting.php.
	 */
	function td_popups_unit_load(): void {
		require_once dirname( __DIR__, 3 ) . '/wp-content/mu-plugins/techdella-core/popups/src/Targeting.php';
	}
}
