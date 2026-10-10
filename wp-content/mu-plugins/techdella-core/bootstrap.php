<?php
/**
 * TechDella Core bootstrap: config, helpers, then each module.
 *
 * Each module directory has a module.php entry file. Modules must not depend on
 * load order beyond inc/ (config + helpers), and register their hooks only.
 */

defined( 'ABSPATH' ) || exit;

define( 'TD_CORE_VERSION', '0.1.0' );
define( 'TD_CORE_DIR', __DIR__ );
define( 'TD_CORE_URL', content_url( 'mu-plugins/techdella-core' ) );

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/dev.php';

/**
 * Modules, in load order. Filterable for tests.
 *
 * @var string[] $td_modules
 */
$td_modules = apply_filters( 'td_core_modules', [ 'cpt', 'seo', 'forms', 'popups', 'ads', 'admin', 'cli', 'preview' ] );

foreach ( $td_modules as $td_module ) {
	$td_module_file = __DIR__ . '/' . $td_module . '/module.php';
	if ( is_readable( $td_module_file ) ) {
		require_once $td_module_file;
	}
}
unset( $td_modules, $td_module, $td_module_file );
