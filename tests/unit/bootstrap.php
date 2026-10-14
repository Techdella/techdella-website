<?php
/**
 * Unit test bootstrap: pure classes only, with minimal WordPress stubs.
 */

define( 'ABSPATH', __DIR__ . '/' );
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

foreach ( glob( __DIR__ . '/stubs/*.php' ) as $stub ) {
	require_once $stub;
}
