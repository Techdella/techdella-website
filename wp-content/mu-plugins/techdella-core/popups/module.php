<?php
/**
 * Popups (§13.1): an engine with no plugin. Popups live in the td_popups
 * option and are edited under Growth → Popups (cap td_manage_popups).
 *
 * Public API:
 *   td_popups(): array                  all popups (normalised)
 *   td_popups_for_request(): array      popups that target the current page
 *   td_popup_markup( $popup, $context ) markup for one popup (dialog or inline card)
 *
 * Files:
 *   src/Targeting.php  data model, targeting rules, defaults P1–P4 (pure)
 *   admin.php          admin screen (list / add / edit / delete)
 *   render.php         front end: footer markup + JSON config, inline cards
 * Behaviour (triggers, frequency, mobile/search rules, focus) lives in the
 * theme's blocks/popup/view.js.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Popups\Targeting;

require_once __DIR__ . '/src/Targeting.php';
require_once __DIR__ . '/render.php';
if ( is_admin() ) {
	require_once __DIR__ . '/admin.php';
}

/**
 * All popups on this site, normalised.
 *
 * @return array<int,array<string,mixed>>
 */
function td_popups(): array {
	$raw = get_option( 'td_popups', [] );
	$out = [];
	foreach ( is_array( $raw ) ? $raw : [] as $popup ) {
		if ( is_array( $popup ) ) {
			$out[] = Targeting::normalize( $popup );
		}
	}
	return $out;
}

/**
 * Save popups (normalised).
 *
 * @param array<int,array<string,mixed>> $popups Popups.
 */
function td_popups_save( array $popups ): void {
	update_option( 'td_popups', array_values( array_map( [ Targeting::class, 'normalize' ], $popups ) ), true );
}

/**
 * Admin URL of the Popups screen.
 */
function td_popups_admin_page_url(): string {
	return admin_url( 'admin.php?page=td-popups' );
}

// Dashboard "Edit popups" quick action (admin/dashboard.php).
add_filter( 'td_popups_admin_url', 'td_popups_admin_page_url' );

// `wp td setup`: capability for admins + editors, and P1–P4 for this site (once).
add_action(
	'td_setup',
	static function ( $site ) {
		foreach ( [ 'administrator', 'editor' ] as $role_name ) {
			$role = get_role( $role_name );
			if ( $role && ! $role->has_cap( 'td_manage_popups' ) ) {
				$role->add_cap( 'td_manage_popups' );
			}
		}
		if ( false === get_option( 'td_popups', false ) ) {
			td_popups_save( Targeting::defaults( (string) $site ) );
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::log( '  popups: default popups created for ' . $site );
			}
		}
	},
	25
);
