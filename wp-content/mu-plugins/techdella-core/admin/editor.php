<?php
/**
 * Editor UX (§14.2, §10.1):
 *  - pre-publish checklist panel in the block editor (assets/prepublish.js)
 *    that locks the Publish button until the rules pass;
 *  - the same rules enforced on the server when an item goes live, for every
 *    path (REST/block editor, classic, WP-CLI). Admins can override with
 *    _td_checklist_override; each override is logged in _td_checklist_override_log;
 *  - SEO title/description counters only when Rank Math is NOT active
 *    (Rank Math's own metabox already shows counters and a preview; a second
 *    set of fields would compete with it and race its separate save request).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Admin\Checklist;

/**
 * Whether Rank Math is active.
 */
function td_rank_math_active(): bool {
	return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
}

/**
 * Hosts whose links count as internal: this site and its sister sites.
 *
 * @return string[]
 */
function td_internal_hosts(): array {
	$hosts = [ Checklist::normalise_host( home_url( '/' ) ) ];
	foreach ( [ 'marketing', 'journal', 'studios' ] as $site ) {
		$hosts[] = Checklist::normalise_host( td_url( $site, '/' ) );
	}
	foreach ( td_site_domains() as $url ) {
		$hosts[] = Checklist::normalise_host( $url );
	}
	return array_values( array_unique( array_filter( $hosts ) ) );
}

/**
 * Checklist input for a post, with incoming (unsaved) values taking priority.
 *
 * @param int                $post_id  Post ID (0 for new).
 * @param string             $title    Title.
 * @param string             $content  Content.
 * @param array<string,mixed> $incoming Incoming meta.
 * @return array<string,mixed>
 */
function td_checklist_input( int $post_id, string $title, string $content, array $incoming ): array {
	$get = static function ( string $key ) use ( $post_id, $incoming ): string {
		if ( array_key_exists( $key, $incoming ) && is_scalar( $incoming[ $key ] ) ) {
			return (string) $incoming[ $key ];
		}
		$value = $post_id ? get_post_meta( $post_id, $key, true ) : '';
		return is_scalar( $value ) ? (string) $value : '';
	};
	return [
		'title'            => $title,
		'h1'               => [ $get( 'hero_title' ) ],
		'content'          => $content,
		'primary_kw'       => $get( 'primary_kw' ),
		'meta_description' => $get( 'rank_math_description' ),
		'hosts'            => td_internal_hosts(),
	];
}

/**
 * Whether an admin override applies (meta set AND user may override).
 *
 * @param int                $post_id  Post ID.
 * @param array<string,mixed> $incoming Incoming meta.
 */
function td_checklist_overridden( int $post_id, array $incoming ): bool {
	return current_user_can( 'manage_options' ) && td_incoming_bool( $post_id, $incoming, '_td_checklist_override' );
}

/**
 * Override log entries waiting for a post ID.
 *
 * @param array<string,mixed>|null $entry Entry to queue (null = take all).
 * @return array<int,array<string,mixed>>
 */
function td_checklist_pending_log( ?array $entry = null ): array {
	static $pending = [];
	if ( null !== $entry ) {
		$pending[] = $entry;
		return [];
	}
	$out     = $pending;
	$pending = [];
	return $out;
}

/**
 * Queue an override log entry (written on wp_insert_post).
 *
 * @param array<string,string> $failures Failed rules.
 */
function td_checklist_log_override( array $failures ): void {
	$user = wp_get_current_user();
	td_checklist_pending_log(
		[
			'user_id'  => (int) $user->ID,
			'user'     => (string) $user->user_login,
			'time'     => gmdate( 'c' ),
			'failures' => array_keys( $failures ),
		]
	);
}

add_action(
	'wp_insert_post',
	static function ( int $post_id ) {
		foreach ( td_checklist_pending_log() as $entry ) {
			add_post_meta( $post_id, '_td_checklist_override_log', $entry );
		}
	}
);

/**
 * Message listing failed rules.
 *
 * @param array<string,string> $failures Failed rules.
 */
function td_checklist_message( array $failures ): string {
	return __( 'Not published — the pre-publish checklist is not done:', 'techdella' ) . ' ' . implode( ' ', $failures );
}

/*
 * Register the override flag (admins only) for the block editor.
 */
add_action(
	'init',
	static function () {
		foreach ( td_checklist_post_types() as $type ) {
			if ( ! post_type_exists( $type ) ) {
				continue;
			}
			register_post_meta(
				$type,
				'_td_checklist_override',
				[
					'type'          => 'boolean',
					'single'        => true,
					'default'       => false,
					'auth_callback' => static fn() => current_user_can( 'manage_options' ),
					'show_in_rest'  => true,
				]
			);
		}
	},
	20
);

/**
 * Posts that passed the REST checklist in this request.
 *
 * @param int|null $post_id Mark (null = read).
 * @return array<int,bool>
 */
function td_checklist_passed( ?int $post_id = null ): array {
	static $passed = [];
	if ( null !== $post_id ) {
		$passed[ $post_id ] = true;
	}
	return $passed;
}

/*
 * REST / block editor: return an error the editor shows as a notice.
 */
add_action(
	'init',
	static function () {
		foreach ( td_checklist_post_types() as $type ) {
			add_filter(
				"rest_pre_insert_{$type}",
				static function ( $prepared, WP_REST_Request $request ) {
					if ( is_wp_error( $prepared ) ) {
						return $prepared;
					}
					$post_id = (int) ( $prepared->ID ?? 0 );
					$old     = $post_id ? (string) get_post_status( $post_id ) : 'new';
					$new     = (string) ( $prepared->post_status ?? $old );
					if ( ! td_is_going_live( $old, $new ) ) {
						return $prepared;
					}
					$meta     = (array) ( $request['meta'] ?? [] );
					$title    = isset( $prepared->post_title ) ? (string) $prepared->post_title : (string) get_post_field( 'post_title', $post_id );
					$content  = isset( $prepared->post_content ) ? (string) $prepared->post_content : (string) get_post_field( 'post_content', $post_id );
					$failures = Checklist::failures( Checklist::run( td_checklist_input( $post_id, $title, $content, $meta ) ) );
					if ( $failures && ! td_checklist_overridden( $post_id, $meta ) ) {
						return new WP_Error( 'td_checklist_failed', td_checklist_message( $failures ), [ 'status' => 403, 'failures' => $failures ] );
					}
					if ( $failures ) {
						td_checklist_log_override( $failures );
					}
					if ( $post_id ) {
						td_checklist_passed( $post_id );
					}
					return $prepared;
				},
				20,
				2
			);
		}
	},
	40
);

/*
 * Every other path: keep the previous status and explain.
 */
add_filter(
	'wp_insert_post_data',
	static function ( array $data, array $postarr ) {
		$type    = (string) $data['post_type'];
		$post_id = (int) ( $postarr['ID'] ?? 0 );
		$old     = $post_id ? (string) get_post_status( $post_id ) : 'new';
		if ( ! in_array( $type, td_checklist_post_types(), true ) || ! td_is_going_live( $old, (string) $data['post_status'] ) || isset( td_checklist_passed()[ $post_id ] ) ) {
			return $data;
		}
		$meta     = (array) ( $postarr['meta_input'] ?? [] );
		$failures = Checklist::failures( Checklist::run( td_checklist_input( $post_id, wp_unslash( (string) $data['post_title'] ), wp_unslash( (string) $data['post_content'] ), $meta ) ) );
		if ( ! $failures ) {
			return $data;
		}
		if ( td_checklist_overridden( $post_id, $meta ) ) {
			td_checklist_log_override( $failures );
			return $data;
		}
		$data['post_status'] = in_array( $old, [ 'new', 'auto-draft' ], true ) ? 'draft' : $old;
		td_queue_admin_notice( td_checklist_message( $failures ) );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::warning( td_checklist_message( $failures ) . ' (admins: set _td_checklist_override=1 with --user=<admin> to override; it is logged)' );
		}
		return $data;
	},
	25,
	2
);

/*
 * Block editor script.
 */
add_action(
	'enqueue_block_editor_assets',
	static function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$type   = $screen ? (string) $screen->post_type : '';
		$gated  = in_array( $type, td_checklist_post_types(), true );
		if ( ! $gated && ! in_array( $type, td_permission_post_types(), true ) ) {
			return;
		}
		$file = TD_CORE_DIR . '/admin/assets/prepublish.js';
		wp_enqueue_script(
			'td-prepublish',
			TD_CORE_URL . '/admin/assets/prepublish.js',
			[ 'wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-data', 'wp-element', 'wp-components', 'wp-i18n' ],
			(string) ( file_exists( $file ) ? filemtime( $file ) : TD_CORE_VERSION ),
			true
		);
		wp_localize_script(
			'td-prepublish',
			'TD_PREPUBLISH',
			[
				'checklist'      => $gated,
				'hosts'          => td_internal_hosts(),
				'rankMath'       => td_rank_math_active(),
				'canOverride'    => current_user_can( 'manage_options' ),
				'placeholderRe'  => trim( Checklist::PLACEHOLDER_RE, '/' ),
				'minLinks'       => Checklist::MIN_INTERNAL_LINKS,
				'metaMin'        => Checklist::META_MIN,
				'metaMax'        => Checklist::META_MAX,
				'titleMax'       => Checklist::TITLE_MAX,
				'mirror'         => [ 'primary_kw', 'hero_title', 'permission_confirmed' ],
				'permissionType' => in_array( $type, td_permission_post_types(), true ),
			]
		);
	}
);
