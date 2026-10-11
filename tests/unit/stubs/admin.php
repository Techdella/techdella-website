<?php
/**
 * Stubs for the admin/cpt pure classes. They use no WordPress functions;
 * this file loads them (and guards ABSPATH) for the Admin and Cpt tests.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$td_core = dirname( __DIR__, 3 ) . '/wp-content/mu-plugins/techdella-core';
require_once $td_core . '/admin/Checklist.php';
require_once $td_core . '/admin/Refresh.php';
require_once $td_core . '/admin/Prices.php';
unset( $td_core );
