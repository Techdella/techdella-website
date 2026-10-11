<?php
/**
 * TechDella theme bootstrap.
 *
 * Requires the techdella-core must-use plugin (td_site(), td_url(), …).
 */

defined( 'ABSPATH' ) || exit;

define( 'TD_THEME_VERSION', '0.1.0' );

if ( ! function_exists( 'td_site' ) ) {
	// Core plugin missing: fail loudly in admin, render a plain notice on the front end.
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The TechDella theme needs the techdella-core must-use plugin (wp-content/mu-plugins/techdella-core.php).', 'techdella' ) . '</p></div>';
		}
	);
	return;
}

require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/assets.php';
require_once __DIR__ . '/inc/media.php';
require_once __DIR__ . '/inc/components.php';
require_once __DIR__ . '/inc/prices.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/templates.php';
