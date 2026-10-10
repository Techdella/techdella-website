<?php
/**
 * Stubs for the SEO module's pure classes (Canonical, Noindex, RobotsTxt,
 * LlmsTxt, Graph). They use no WordPress functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( 'td_seo_unit_load' ) ) {
	/**
	 * Load the SEO module's pure classes.
	 */
	function td_seo_unit_load(): void {
		$dir = dirname( __DIR__, 3 ) . '/wp-content/mu-plugins/techdella-core/seo/src';
		foreach ( [ 'Canonical', 'Noindex', 'RobotsTxt', 'LlmsTxt', 'Graph' ] as $class ) {
			require_once $dir . '/' . $class . '.php';
		}
	}
}
td_seo_unit_load();
