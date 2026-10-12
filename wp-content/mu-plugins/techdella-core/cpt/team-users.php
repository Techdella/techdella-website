<?php
/**
 * Team profiles ↔ WordPress users (§8.1: every article has a named author and a reviewer, with an
 * author page). Each td_team profile is linked to a user through its `wp_user` field: an existing
 * user with the same slug or display name, else a new Writer account with a random password and no
 * email (nobody is notified; an admin sets an email and password when the person needs to log in).
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The WordPress user linked to a team profile, linking (and creating) one when needed.
 *
 * @param WP_Post $team   td_team post.
 * @param bool    $create Create a user when none matches.
 */
function td_team_user_id( WP_Post $team, bool $create = true ): int {
	$linked = td_field( 'wp_user', $team->ID );
	$linked = is_array( $linked ) ? (int) ( $linked['ID'] ?? 0 ) : ( $linked instanceof WP_User ? $linked->ID : (int) $linked );
	if ( $linked && get_userdata( $linked ) ) {
		return $linked;
	}
	$user = get_user_by( 'slug', $team->post_name );
	if ( ! $user ) {
		foreach ( get_users(
			[
				'search'         => $team->post_title,
				'search_columns' => [ 'display_name' ],
				'number'         => 2,
			]
		) as $u ) {
			if ( 0 === strcasecmp( $u->display_name, $team->post_title ) ) {
				$user = $u;
				break;
			}
		}
	}
	if ( ! $user && $create ) {
		$id   = wp_insert_user(
			[
				'user_login'    => $team->post_name,
				'user_nicename' => $team->post_name,
				'display_name'  => $team->post_title,
				'first_name'    => strtok( $team->post_title, ' ' ),
				'user_pass'     => wp_generate_password( 32, true, true ),
				'role'          => get_role( 'td_writer' ) ? 'td_writer' : 'author',
			]
		);
		$user = is_wp_error( $id ) ? null : get_userdata( $id );
	}
	if ( ! $user ) {
		return 0;
	}
	update_post_meta( $team->ID, 'wp_user', (int) $user->ID );
	return (int) $user->ID;
}

/**
 * Link every team profile on this site (wp td setup).
 */
function td_team_sync_users(): void {
	if ( ! post_type_exists( 'td_team' ) ) {
		return;
	}
	foreach ( get_posts(
		[
			'post_type'      => 'td_team',
			'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
			'posts_per_page' => -1,
		]
	) as $team ) {
		td_team_user_id( $team );
	}
}
add_action( 'td_setup', 'td_team_sync_users', 30 );
