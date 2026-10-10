<?php
/**
 * Stubs for the cpt pure classes (Registry, Fields): no WordPress needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'TD_TEST_CORE_DIR' ) ) {
	define( 'TD_TEST_CORE_DIR', dirname( __DIR__, 3 ) . '/wp-content/mu-plugins/techdella-core' );
}

require_once TD_TEST_CORE_DIR . '/cpt/Registry.php';
require_once TD_TEST_CORE_DIR . '/cpt/Fields.php';
