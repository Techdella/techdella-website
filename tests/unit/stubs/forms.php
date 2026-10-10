<?php
/**
 * WordPress stubs for the forms module's pure classes (each guarded).
 */

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { // phpcs:ignore
		return $text;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) { // phpcs:ignore
		return $value;
	}
}

if ( ! function_exists( 'td_forms_unit_load' ) ) {
	/**
	 * Load the forms module's pure files.
	 */
	function td_forms_unit_load(): void {
		$dir = dirname( __DIR__, 3 ) . '/wp-content/mu-plugins/techdella-core/forms';
		require_once $dir . '/schema.php';
		foreach ( [ 'Validator', 'Payload', 'Token', 'Crypto', 'Retry' ] as $class ) {
			require_once $dir . '/src/' . $class . '.php';
		}
	}
}
