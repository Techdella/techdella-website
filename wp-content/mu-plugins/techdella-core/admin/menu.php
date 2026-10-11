<?php
/**
 * Grouped admin menu (§14.2): Content · Growth · Studios · Brand, only the
 * groups that have something on this site. Comments are off for everyone;
 * Tools and plugin screens are hidden for non-admins.
 *
 * CPTs join their group through show_in_menu (cpt/register.php). Other
 * modules add items with add_submenu_page( 'td-growth', … ) — e.g. Popups.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Cpt\Registry;

/**
 * Menu groups.
 *
 * @return array<string,array{title:string,icon:string,position:float}>
 */
function td_admin_groups(): array {
	return [
		'td-content' => [
			'title'    => __( 'Content', 'techdella' ),
			'icon'     => 'dashicons-edit-page',
			'position' => 3.1,
		],
		'td-growth'  => [
			'title'    => __( 'Growth', 'techdella' ),
			'icon'     => 'dashicons-chart-area',
			'position' => 3.2,
		],
		'td-studios' => [
			'title'    => __( 'Studios', 'techdella' ),
			'icon'     => 'dashicons-art',
			'position' => 3.3,
		],
		'td-brand'   => [
			'title'    => __( 'Brand', 'techdella' ),
			'icon'     => 'dashicons-star-filled',
			'position' => 3.4,
		],
	];
}

/**
 * Groups that have at least one item on this site.
 *
 * @return string[]
 */
function td_admin_groups_for_site(): array {
	$groups = [];
	foreach ( Registry::for_site( td_site() ) as $def ) {
		if ( ! empty( $def['menu'] ) ) {
			$groups[ (string) $def['menu'] ] = true;
		}
	}
	return array_values( array_intersect( array_keys( td_admin_groups() ), array_keys( $groups ) ) );
}

/**
 * Admin menu group of a post type, or ''.
 *
 * @param string $post_type Post type.
 */
function td_admin_group_of( string $post_type ): string {
	$def = td_cpt_definition( $post_type );
	return $def ? (string) ( $def['menu'] ?? '' ) : '';
}

/**
 * The slug a group's top-level item really has after WordPress re-points it
 * to its first submenu.
 *
 * @param string $group Group slug.
 */
function td_admin_real_parent( string $group ): string {
	global $_wp_real_parent_file;
	return is_array( $_wp_real_parent_file ) && isset( $_wp_real_parent_file[ $group ] ) ? (string) $_wp_real_parent_file[ $group ] : $group;
}

add_action(
	'admin_menu',
	static function () {
		$groups = td_admin_groups();
		foreach ( td_admin_groups_for_site() as $slug ) {
			add_menu_page( $groups[ $slug ]['title'], $groups[ $slug ]['title'], 'read', $slug, 'td_render_group_page', $groups[ $slug ]['icon'], $groups[ $slug ]['position'] );
		}
		// Core types inside Content.
		$types = Registry::for_site( td_site() );
		add_submenu_page( 'td-content', __( 'Pages', 'techdella' ), __( 'Pages', 'techdella' ), 'edit_pages', 'edit.php?post_type=page' );
		if ( isset( $types['post'] ) ) {
			add_submenu_page( 'td-content', __( 'Guides', 'techdella' ), __( 'Guides', 'techdella' ), 'edit_posts', 'edit.php' );
		}
		remove_menu_page( 'edit.php' );
		remove_menu_page( 'edit.php?post_type=page' );
	},
	9
);

add_action(
	'admin_menu',
	static function () {
		// Taxonomy screens (CPTs nested under a custom parent don't get them automatically).
		foreach ( Registry::taxonomies() as $tax => $def ) {
			$object = get_taxonomy( $tax );
			if ( ! $object ) {
				continue;
			}
			$type = (string) ( $object->object_type[0] ?? 'post' );
			add_submenu_page( 'td-content', (string) $def['plural'], (string) $def['plural'], 'manage_categories', 'edit-tags.php?taxonomy=' . $tax . '&post_type=' . $type );
		}
		// Menus for Editors (scoped edit_theme_options, admin/roles.php). Admins keep Appearance → Menus.
		// phpcs:disable WordPress.WP.Capabilities.Unknown -- td_edit_menus is added by admin/roles.php.
		if ( ! current_user_can( 'manage_options' ) && current_user_can( 'td_edit_menus' ) ) {
			add_submenu_page( 'td-content', __( 'Menus', 'techdella' ), __( 'Menus', 'techdella' ), 'td_edit_menus', 'nav-menus.php' );
			// phpcs:enable WordPress.WP.Capabilities.Unknown
		}
	},
	11
);

add_action(
	'admin_menu',
	static function () {
		global $submenu, $menu;

		// Drop each group's self-link so the top item opens its first real screen.
		foreach ( array_keys( td_admin_groups() ) as $slug ) {
			if ( empty( $submenu[ $slug ] ) ) {
				continue;
			}
			foreach ( $submenu[ $slug ] as $i => $item ) {
				if ( $slug === $item[2] && count( $submenu[ $slug ] ) > 1 ) {
					unset( $submenu[ $slug ][ $i ] );
				}
			}
		}

		// Comments are off sitewide (§14.2).
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );

		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		// Non-admins: only the team's screens. Plugin screens, Tools, Appearance go.
		$allowed = (array) apply_filters( 'td_admin_menu_allowlist', array_merge( [ 'index.php', 'upload.php', 'profile.php', 'users.php', 'td-help' ], array_keys( td_admin_groups() ) ) );
		foreach ( (array) $menu as $item ) {
			$slug = (string) ( $item[2] ?? '' );
			if ( '' === $slug || str_starts_with( $slug, 'separator' ) || in_array( $slug, $allowed, true ) ) {
				continue;
			}
			remove_menu_page( $slug );
		}
	},
	9999
);

/**
 * Fallback screen for a group's top-level slug (normally never shown).
 */
function td_render_group_page(): void {
	global $submenu;
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<div class="wrap"><h1>' . esc_html( td_admin_groups()[ $page ]['title'] ?? '' ) . '</h1><ul>';
	foreach ( (array) ( $submenu[ $page ] ?? [] ) as $item ) {
		if ( current_user_can( (string) $item[1] ) ) {
			$url = str_contains( (string) $item[2], '.php' ) ? admin_url( (string) $item[2] ) : menu_page_url( (string) $item[2], false );
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( wp_strip_all_tags( (string) $item[0] ) ) . '</a></li>';
		}
	}
	echo '</ul></div>';
}

/*
 * Highlight the right group for list/edit/new screens of grouped types.
 */
add_filter(
	'parent_file',
	static function ( $parent_file ) {
		global $pagenow, $typenow, $taxnow;
		$group = '';
		if ( in_array( $pagenow, [ 'edit.php', 'post.php', 'post-new.php' ], true ) ) {
			$group = td_admin_group_of( $typenow ? $typenow : 'post' );
		} elseif ( in_array( $pagenow, [ 'edit-tags.php', 'term.php' ], true ) && $taxnow && isset( Registry::taxonomies()[ $taxnow ] ) ) {
			$group = 'td-content';
		} elseif ( 'nav-menus.php' === $pagenow && ! current_user_can( 'manage_options' ) ) {
			$group = 'td-content';
		}
		return '' !== $group ? td_admin_real_parent( $group ) : $parent_file;
	},
	20
);
add_filter(
	'submenu_file',
	static function ( $submenu_file ) {
		global $pagenow, $typenow, $taxnow;
		if ( in_array( $pagenow, [ 'post.php', 'post-new.php', 'edit.php' ], true ) ) {
			$type = $typenow ? $typenow : 'post';
			if ( td_admin_group_of( $type ) ) {
				return 'post' === $type ? 'edit.php' : 'edit.php?post_type=' . $type;
			}
		}
		if ( in_array( $pagenow, [ 'edit-tags.php', 'term.php' ], true ) && $taxnow && isset( Registry::taxonomies()[ $taxnow ] ) ) {
			$object = get_taxonomy( $taxnow );
			return 'edit-tags.php?taxonomy=' . $taxnow . '&post_type=' . ( $object ? (string) ( $object->object_type[0] ?? 'post' ) : 'post' );
		}
		return $submenu_file;
	},
	20
);

/*
 * Comments off for everyone: no support, no screens, no admin-bar item.
 */
add_action(
	'init',
	static function () {
		foreach ( get_post_types() as $type ) {
			remove_post_type_support( $type, 'comments' );
			remove_post_type_support( $type, 'trackbacks' );
		}
	},
	100
);
add_action(
	'admin_init',
	static function () {
		global $pagenow;
		if ( 'edit-comments.php' === $pagenow || 'comment.php' === $pagenow || ( 'options-discussion.php' === $pagenow && ! current_user_can( 'manage_options' ) ) ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
		if ( ! current_user_can( 'manage_options' ) && in_array( $pagenow, [ 'tools.php', 'import.php', 'export.php', 'site-health.php', 'export-personal-data.php', 'erase-personal-data.php' ], true ) ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}
);
add_action(
	'admin_bar_menu',
	static function ( WP_Admin_Bar $bar ) {
		$bar->remove_node( 'comments' );
		if ( ! current_user_can( 'manage_options' ) ) {
			foreach ( [ 'rank-math', 'customize', 'themes', 'widgets', 'updates' ] as $node ) {
				$bar->remove_node( $node );
			}
		}
	},
	999
);
