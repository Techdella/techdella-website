<?php
/**
 * Loader for the tools module's pure classes (no WordPress needed).
 */

if ( ! function_exists( 'td_tools_unit_load' ) ) {
	/**
	 * Load tools/src/*.php.
	 */
	function td_tools_unit_load(): void {
		$dir = dirname( __DIR__, 3 ) . '/wp-content/mu-plugins/techdella-core/tools/src';
		foreach ( [ 'Ssrf', 'PageFacts', 'AiTools' ] as $class ) {
			require_once $dir . '/' . $class . '.php';
		}
	}
}
