<?php
/**
 * Admin module (§14): roles, grouped menu, "TechDella HQ" dashboard,
 * pre-publish checklist (editor + server), branding and the how-to page.
 *
 * Depends on the cpt module (Registry, td_incoming_bool, td_queue_admin_notice,
 * td_checklist_post_types), which always loads first.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'td_cpt_definition' ) ) {
	return; // The cpt module is required.
}

require_once __DIR__ . '/Checklist.php';
require_once __DIR__ . '/Refresh.php';
require_once __DIR__ . '/Prices.php';
require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/menu.php';
require_once __DIR__ . '/dashboard.php';
require_once __DIR__ . '/editor.php';
require_once __DIR__ . '/branding.php';
require_once __DIR__ . '/help.php';
require_once __DIR__ . '/prices-admin.php';
