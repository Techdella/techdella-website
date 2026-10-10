<?php
/**
 * Register post types and taxonomies from the Registry, per site (D3), on
 * their live URL bases (§9). Flushes rewrites when the model changes.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Cpt\Registry;

/**
 * Build register_post_type() args from a Registry definition.
 *
 * @param string              $type Post type.
 * @param array<string,mixed> $def  Definition.
 * @return array<string,mixed>
 */
function td_cpt_args( string $type, array $def ): array {
	$public   = $def['public'] ?? true;
	$singular = (string) $def['singular'];
	$plural   = (string) $def['plural'];
	$lower    = strtolower( $singular );
	$args     = [
		'labels'              => [
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			'all_items'          => $plural,
			'add_new'            => 'Add ' . $lower,
			'add_new_item'       => 'Add ' . $lower,
			'edit_item'          => 'Edit ' . $lower,
			'new_item'           => 'New ' . $lower,
			'view_item'          => 'View ' . $lower,
			'search_items'       => 'Search ' . strtolower( $plural ),
			'not_found'          => 'No ' . strtolower( $plural ) . ' yet',
			'not_found_in_trash' => 'No ' . strtolower( $plural ) . ' in the bin',
		],
		'public'              => $public,
		'publicly_queryable'  => $public,
		'exclude_from_search' => ! $public || false === ( $def['search'] ?? true ),
		'show_ui'             => true,
		'show_in_menu'        => $def['menu'] ?? true,
		'show_in_nav_menus'   => $public && empty( $def['noindex'] ),
		'show_in_admin_bar'   => true,
		'show_in_rest'        => true,
		'has_archive'         => $def['archive'] ?? false,
		'hierarchical'        => ! empty( $def['hier'] ),
		'supports'            => (array) ( $def['supports'] ?? [ 'title', 'editor' ] ),
		'menu_icon'           => (string) ( $def['icon'] ?? 'dashicons-admin-post' ),
		'capability_type'     => $def['cap'] ?? 'post',
		'map_meta_cap'        => true,
		'rewrite'             => false,
		'query_var'           => $public ? $type : false,
		'delete_with_user'    => false,
	];
	if ( $public && ! empty( $def['slug'] ) ) {
		$args['rewrite'] = [
			'slug'       => (string) $def['slug'],
			'with_front' => false,
			'feeds'      => false,
			'pages'      => true,
		];
	}
	/**
	 * Filter post type args before registration.
	 *
	 * @param array  $args Args.
	 * @param string $type Post type.
	 */
	return (array) apply_filters( 'td_cpt_args', $args, $type );
}

add_action(
	'init',
	static function () {
		$site  = td_site();
		$types = Registry::for_site( $site );

		foreach ( $types as $type => $def ) {
			if ( ! empty( $def['core'] ) ) {
				continue;
			}
			register_post_type( $type, td_cpt_args( $type, $def ) );
		}

		foreach ( Registry::taxonomies() as $tax => $def ) {
			$objects = array_values( array_filter( (array) $def['types'], static fn( $t ) => isset( $types[ $t ] ) ) );
			if ( ! $objects ) {
				continue;
			}
			$assign = 'division' === $tax ? 'edit_td_case_studies' : 'edit_posts';
			register_taxonomy(
				$tax,
				$objects,
				[
					'labels'             => [
						'name'          => (string) $def['plural'],
						'singular_name' => (string) $def['singular'],
						'menu_name'     => (string) $def['plural'],
						'all_items'     => 'All ' . strtolower( (string) $def['plural'] ),
						'add_new_item'  => 'Add ' . strtolower( (string) $def['singular'] ),
						'edit_item'     => 'Edit ' . strtolower( (string) $def['singular'] ),
					],
					// Internal-linking and filtering only: no thin term archives (§8.1 doorway/thin content).
					'public'             => false,
					'publicly_queryable' => false,
					'show_ui'            => true,
					'show_in_rest'       => true,
					'show_admin_column'  => true,
					'show_in_nav_menus'  => false,
					'hierarchical'       => 'td_service_tag' !== $tax,
					'rewrite'            => false,
					'query_var'          => false,
					'capabilities'       => [
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'manage_categories',
						'delete_terms' => 'manage_categories',
						'assign_terms' => $assign,
					],
				]
			);
		}

		// Core posts are the team's "Guides" (§14.2).
		$post_labels = get_post_type_object( 'post' );
		if ( $post_labels ) {
			$post_labels->labels->name          = 'Guides';
			$post_labels->labels->menu_name     = 'Guides';
			$post_labels->labels->all_items     = 'Guides';
			$post_labels->labels->singular_name = 'Guide';
			$post_labels->labels->add_new_item  = 'Write a guide';
			$post_labels->labels->edit_item     = 'Edit guide';
		}
	},
	5
);

/**
 * Hash of everything that shapes rewrite rules, so a deploy that changes the
 * model flushes rules once (soft flush, .htaccess untouched).
 */
function td_cpt_rewrite_signature(): string {
	$parts = [ td_site(), (string) get_option( 'permalink_structure' ) ];
	foreach ( Registry::for_site( td_site() ) as $type => $def ) {
		$parts[] = $type . ':' . wp_json_encode( [ $def['slug'] ?? null, $def['archive'] ?? null, $def['public'] ?? true ] );
	}
	return md5( implode( '|', $parts ) . '|v1' );
}

add_action(
	'init',
	static function () {
		$sig = td_cpt_rewrite_signature();
		if ( get_option( 'td_cpt_rewrite_signature' ) !== $sig ) {
			flush_rewrite_rules( false );
			update_option( 'td_cpt_rewrite_signature', $sig, true );
		}
	},
	99
);

/**
 * Studios shows only case studies in the `studios` division (§7).
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! td_is_site( 'studios' ) || ! $query->is_post_type_archive( 'case_study' ) ) {
			return;
		}
		$query->set(
			'tax_query',
			[
				[
					'taxonomy' => 'division',
					'field'    => 'slug',
					'terms'    => [ 'studios' ],
				],
			]
		);
	}
);

/**
 * A single case study on Studios must be in the studios division, otherwise 404.
 */
add_action(
	'template_redirect',
	static function () {
		if ( td_is_site( 'studios' ) && is_singular( 'case_study' ) && ! has_term( 'studios', 'division' ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}
);

/**
 * Document REST endpoints are for logged-in staff only (§7: never public).
 */
add_filter(
	'rest_pre_dispatch',
	static function ( $result, $server, WP_REST_Request $request ) {
		$route = $request->get_route();
		if ( preg_match( '#^/wp/v2/(document|types/document)(/|$)#', $route ) && ! current_user_can( 'edit_td_documents' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to do that.', 'techdella' ), [ 'status' => rest_authorization_required_code() ] );
		}
		return $result;
	},
	10,
	3
);

/**
 * Site setup (wp td setup): permalinks, division terms, rewrite flush.
 */
add_action(
	'td_setup',
	static function () {
		// Journal guides live at /<slug>/ (§9.3); the other sites use the same structure for pages.
		if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
		}
		foreach ( Registry::taxonomies() as $tax => $def ) {
			if ( empty( $def['terms'] ) || ! taxonomy_exists( $tax ) ) {
				continue;
			}
			foreach ( (array) $def['terms'] as $slug => $name ) {
				if ( ! term_exists( (string) $slug, $tax ) ) {
					wp_insert_term( (string) $name, $tax, [ 'slug' => (string) $slug ] );
				}
			}
		}
		flush_rewrite_rules( false );
		update_option( 'td_cpt_rewrite_signature', td_cpt_rewrite_signature(), true );
	},
	5
);
