<?php
/**
 * Roles (§14.1). Every CPT has its own capability type (cpt/Registry.php,
 * map_meta_cap on), so roles get exactly the caps listed here:
 *
 *  Admin     everything (all custom caps).
 *  Editor    core editor caps + every CPT cap (publish everything),
 *            td_edit_menus (menus only — see below), td_manage_popups,
 *            td_manage_media. No plugins/themes/settings (core editor has none).
 *  Writer    td_writer: own drafts of guides (post), glossary terms and case
 *            studies; upload media; "Submit for review". No publish caps.
 *  Designer  td_designer: media (incl. others' alt text), studio_work,
 *            resources and case studies (galleries) incl. others' and
 *            published ones; no publish caps at all (§10.1: only Editors publish).
 *
 * Menus: WordPress gates nav-menus.php behind edit_theme_options, which would
 * also open the Customizer, widgets and theme switching. Editors are NOT given
 * that cap. Instead td_edit_menus grants edit_theme_options dynamically only
 * while the request is nav-menus.php or one of its AJAX actions.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Cpt\Registry;

const TD_ROLES_VERSION = '1';

/**
 * Every primitive cap for every TechDella capability type.
 *
 * @return string[]
 */
function td_all_cpt_caps(): array {
	$caps = [];
	foreach ( Registry::capability_types() as [ , $plural ] ) {
		$caps = array_merge( $caps, array_values( Registry::primitive_caps( $plural ) ) );
	}
	return $caps;
}

/**
 * Caps for a capability type, filtered to the given role-level keys.
 *
 * @param string   $plural Plural cap base, e.g. td_case_studies.
 * @param string[] $keys   Keys from Registry::primitive_caps().
 * @return string[]
 */
function td_caps_for( string $plural, array $keys ): array {
	return array_values( array_intersect_key( Registry::primitive_caps( $plural ), array_flip( $keys ) ) );
}

/**
 * Role → caps that this module manages.
 *
 * @return array<string,array{name:string,caps:string[]}>
 */
function td_role_definitions(): array {
	$own_drafts = [ 'edit_posts', 'delete_posts' ];
	$others     = [ 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'delete_posts' ];
	$extra      = [ 'td_edit_menus', 'td_manage_popups', 'td_manage_media' ];

	$defs = [
		'administrator' => [
			'name' => 'Administrator',
			'caps' => array_merge( td_all_cpt_caps(), $extra ),
		],
		'editor'        => [
			'name' => 'Editor',
			'caps' => array_merge( td_all_cpt_caps(), $extra ),
		],
		'td_writer'     => [
			'name' => 'Writer',
			'caps' => array_merge(
				[ 'read', 'upload_files', 'edit_posts', 'delete_posts' ],
				td_caps_for( 'td_glossary_terms', $own_drafts ),
				td_caps_for( 'td_case_studies', $own_drafts )
			),
		],
		'td_designer'   => [
			'name' => 'Designer',
			'caps' => array_merge(
				[ 'read', 'upload_files', 'td_manage_media' ],
				td_caps_for( 'td_works', $others ),
				td_caps_for( 'td_resources', $others ),
				td_caps_for( 'td_case_studies', [ 'edit_posts', 'edit_others_posts', 'edit_published_posts' ] )
			),
		],
	];
	return (array) apply_filters( 'td_role_definitions', $defs );
}

/**
 * Create/update roles. Idempotent; never removes caps it did not add.
 */
function td_sync_roles(): void {
	foreach ( td_role_definitions() as $slug => $def ) {
		$role = get_role( $slug );
		if ( ! $role ) {
			$role = add_role( $slug, $def['name'], array_fill_keys( $def['caps'], true ) );
		}
		if ( ! $role ) {
			continue;
		}
		foreach ( $def['caps'] as $cap ) {
			if ( empty( $role->capabilities[ $cap ] ) ) {
				$role->add_cap( $cap );
			}
		}
	}
	// Writers/Designers must never publish, even if someone added it by hand.
	foreach ( [ 'td_writer', 'td_designer' ] as $slug ) {
		$role = get_role( $slug );
		if ( ! $role ) {
			continue;
		}
		foreach ( array_keys( $role->capabilities ) as $cap ) {
			if ( str_starts_with( (string) $cap, 'publish_' ) ) {
				$role->remove_cap( (string) $cap );
			}
		}
	}
	update_option( 'td_roles_version', TD_ROLES_VERSION . ':' . md5( (string) wp_json_encode( td_role_definitions() ) ), true );
}

/**
 * Remove the Subscriber/Contributor/Author roles, but only when nobody has
 * them (never delete a role with users).
 *
 * @return string[] Removed roles.
 */
function td_remove_unused_roles(): array {
	$counts  = count_users();
	$removed = [];
	foreach ( [ 'subscriber', 'contributor', 'author' ] as $slug ) {
		if ( get_role( $slug ) && empty( $counts['avail_roles'][ $slug ] ) ) {
			if ( get_option( 'default_role' ) === $slug ) {
				update_option( 'default_role', 'td_writer' );
			}
			remove_role( $slug );
			$removed[] = $slug;
		}
	}
	return $removed;
}

// Keep caps in sync after a deploy that changes the model, without waiting for `wp td setup`.
add_action(
	'init',
	static function () {
		if ( get_option( 'td_roles_version' ) !== TD_ROLES_VERSION . ':' . md5( (string) wp_json_encode( td_role_definitions() ) ) ) {
			td_sync_roles();
		}
	},
	50
);

add_action(
	'td_setup',
	static function () {
		td_sync_roles();
		$removed = td_remove_unused_roles();
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( 'admin: roles synced (Editor, Writer, Designer)' . ( $removed ? '; removed unused: ' . implode( ', ', $removed ) : '' ) );
			WP_CLI::log( 'admin: [CONFIRM] turn on 2FA for every admin and editor (WP 2FA plugin or Hostinger).' );
		}
	},
	20
);

/**
 * Whether the current request is the menu editor (or its AJAX calls).
 */
function td_is_menu_request(): bool {
	global $pagenow;
	if ( 'nav-menus.php' === $pagenow ) {
		return true;
	}
	if ( wp_doing_ajax() ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only reads the action name; core verifies nonces.
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		return in_array( $action, [ 'add-menu-item', 'menu-get-metabox', 'menu-quick-search', 'menu-locations-save', 'menu-settings-column-save', 'closed-postboxes', 'meta-box-order' ], true );
	}
	return false;
}

/*
 * Scoped menu editing: edit_theme_options only on the menu screen for td_edit_menus.
 */
add_filter(
	'user_has_cap',
	static function ( array $allcaps, array $caps ) {
		if ( in_array( 'edit_theme_options', $caps, true ) && empty( $allcaps['edit_theme_options'] ) && ! empty( $allcaps['td_edit_menus'] ) && td_is_menu_request() ) {
			$allcaps['edit_theme_options'] = true;
		}
		return $allcaps;
	},
	10,
	2
);

/*
 * Designers fix alt text/captions on anyone's media without edit_others_posts.
 */
add_filter(
	'map_meta_cap',
	static function ( array $caps, string $cap, int $user_id, array $args ) {
		if ( ! in_array( $cap, [ 'edit_post', 'delete_post' ], true ) || empty( $args[0] ) ) {
			return $caps;
		}
		$post = get_post( (int) $args[0] );
		if ( $post && 'attachment' === $post->post_type && user_can( $user_id, 'td_manage_media' ) ) {
			return [ 'edit_post' === $cap ? 'upload_files' : 'td_manage_media' ];
		}
		return $caps;
	},
	10,
	4
);
