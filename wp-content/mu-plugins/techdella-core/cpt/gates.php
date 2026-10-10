<?php
/**
 * Publishing gates (§7, §10.5):
 *  - case_study and td_testimonial cannot go live until permission_confirmed
 *    is true (server-side for every path: block editor/REST, classic, WP-CLI);
 *  - document is always private.
 *
 * Only transitions INTO publish/future are gated, so already-published items
 * (e.g. imported live case studies) are never silently unpublished on edit.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Cpt\Registry;

/**
 * Post types that need permission_confirmed before publishing.
 *
 * @return string[]
 */
function td_permission_post_types(): array {
	return Registry::with_flag( 'permission' );
}

/**
 * Whether a status change goes live.
 *
 * @param string $old Old status ('new' for new posts).
 * @param string $new New status.
 */
function td_is_going_live( string $old, string $new ): bool {
	return in_array( $new, [ 'publish', 'future' ], true ) && ! in_array( $old, [ 'publish', 'future' ], true );
}

/**
 * Read a boolean from incoming meta, else from the stored meta.
 *
 * @param int                 $post_id  Post ID (0 for new).
 * @param array<string,mixed> $incoming Incoming meta (meta_input / REST meta).
 * @param string              $key      Meta key.
 */
function td_incoming_bool( int $post_id, array $incoming, string $key ): bool {
	$value = array_key_exists( $key, $incoming ) ? $incoming[ $key ] : ( $post_id ? get_post_meta( $post_id, $key, true ) : '' );
	return in_array( $value, [ true, 1, '1', 'true', 'on', 'yes' ], true );
}

/**
 * Message shown when the permission gate refuses to publish.
 *
 * @param string $post_type Post type.
 */
function td_permission_message( string $post_type ): string {
	return 'td_testimonial' === $post_type
		? __( 'Not published: tick "Permission confirmed" once the person has approved this exact wording in writing. Saved as a draft.', 'techdella' )
		: __( 'Not published: tick "Client permission confirmed" once the client has approved the names, numbers and quote in writing. Saved as a draft.', 'techdella' );
}

/**
 * Queue an admin notice for the current user (shown on the next admin page).
 *
 * @param string $message Message.
 * @param string $type    error|warning|success.
 */
function td_queue_admin_notice( string $message, string $type = 'error' ): void {
	$key                        = 'td_notices_' . get_current_user_id();
	$notices                    = (array) get_transient( $key );
	$notices[ md5( $message ) ] = [
		'message' => $message,
		'type'    => $type,
	];
	set_transient( $key, $notices, 5 * MINUTE_IN_SECONDS );
}

add_action(
	'admin_notices',
	static function () {
		$key     = 'td_notices_' . get_current_user_id();
		$notices = (array) get_transient( $key );
		if ( ! $notices ) {
			return;
		}
		delete_transient( $key );
		foreach ( $notices as $notice ) {
			if ( is_array( $notice ) && ! empty( $notice['message'] ) ) {
				printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( (string) $notice['type'] ), esc_html( (string) $notice['message'] ) );
			}
		}
	}
);

/**
 * Posts whose REST request already passed the gate (so the generic filter
 * below does not re-check against not-yet-saved meta).
 *
 * @param int|null $post_id Mark this ID (null = read).
 * @return array<int,bool>
 */
function td_gate_passed( ?int $post_id = null ): array {
	static $passed = [];
	if ( null !== $post_id ) {
		$passed[ $post_id ] = true;
	}
	return $passed;
}

/*
 * REST (block editor): refuse with a clear error the editor shows inline.
 */
add_action(
	'init',
	static function () {
		foreach ( td_permission_post_types() as $type ) {
			add_filter(
				"rest_pre_insert_{$type}",
				static function ( $prepared, WP_REST_Request $request ) use ( $type ) {
					if ( is_wp_error( $prepared ) ) {
						return $prepared;
					}
					$post_id = (int) ( $prepared->ID ?? 0 );
					$old     = $post_id ? (string) get_post_status( $post_id ) : 'new';
					$new     = (string) ( $prepared->post_status ?? $old );
					if ( ! td_is_going_live( $old, $new ) ) {
						return $prepared;
					}
					$meta = (array) ( $request['meta'] ?? [] );
					if ( ! td_incoming_bool( $post_id, $meta, 'permission_confirmed' ) ) {
						return new WP_Error( 'td_permission_required', td_permission_message( $type ), [ 'status' => 403 ] );
					}
					if ( $post_id ) {
						td_gate_passed( $post_id );
					}
					return $prepared;
				},
				10,
				2
			);
		}
	},
	40
);

/*
 * Every other path (classic editor, quick edit, WP-CLI, wp_insert_post):
 * keep the item as a draft and say why.
 */
add_filter(
	'wp_insert_post_data',
	static function ( array $data, array $postarr ) {
		$type    = (string) $data['post_type'];
		$post_id = (int) ( $postarr['ID'] ?? 0 );
		$old     = $post_id ? (string) get_post_status( $post_id ) : 'new';
		$new     = (string) $data['post_status'];

		// Documents are private client material: never public (§7).
		if ( 'document' === $type && in_array( $new, [ 'publish', 'future' ], true ) ) {
			$data['post_status'] = 'private';
			return $data;
		}

		if ( ! in_array( $type, td_permission_post_types(), true ) || ! td_is_going_live( $old, $new ) || isset( td_gate_passed()[ $post_id ] ) ) {
			return $data;
		}
		if ( td_incoming_bool( $post_id, (array) ( $postarr['meta_input'] ?? [] ), 'permission_confirmed' ) ) {
			return $data;
		}
		$data['post_status'] = in_array( $old, [ 'new', 'auto-draft' ], true ) ? 'draft' : $old;
		td_queue_admin_notice( td_permission_message( $type ) );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::warning( td_permission_message( $type ) );
		}
		return $data;
	},
	20,
	2
);
