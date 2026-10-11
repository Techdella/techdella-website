<?php
/**
 * Loader for the cli module's pure classes (unit tests).
 */

if ( ! function_exists( 'td_cli_unit_load' ) ) {
	/**
	 * Load cli/src/*.php.
	 */
	function td_cli_unit_load(): void {
		$dir = dirname( __DIR__, 3 ) . '/wp-content/mu-plugins/techdella-core/cli/src';
		foreach ( [ 'FrontMatter', 'RedirectRules', 'Decay', 'Directives' ] as $class ) {
			require_once $dir . '/' . $class . '.php';
		}
	}
}
