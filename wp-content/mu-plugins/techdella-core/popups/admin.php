<?php
/**
 * Growth → Popups (§13.1, §14.2): list, add, edit, delete popups without code.
 * Capability td_manage_popups (admins + editors).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Popups\Targeting;

/**
 * Parent menu: Growth when that group exists on this site, else Content, else Tools.
 */
function td_popups_admin_parent(): string {
	$groups = function_exists( 'td_admin_groups_for_site' ) ? td_admin_groups_for_site() : [];
	foreach ( [ 'td-growth', 'td-content' ] as $slug ) {
		if ( in_array( $slug, $groups, true ) ) {
			return $slug;
		}
	}
	return 'tools.php';
}

add_action(
	'admin_menu',
	static function () {
		add_submenu_page( td_popups_admin_parent(), __( 'Popups', 'techdella' ), __( 'Popups', 'techdella' ), 'td_manage_popups', 'td-popups', 'td_popups_admin_render' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- granted on td_setup.
	},
	10
);

/**
 * Save / delete handler.
 */
function td_popups_admin_handle(): void {
	if ( ! current_user_can( 'td_manage_popups' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
		wp_die( esc_html__( 'You cannot manage popups.', 'techdella' ), 403 );
	}
	check_admin_referer( 'td_popups_save' );
	$popups = td_popups();
	$action = isset( $_POST['td_action'] ) ? sanitize_key( wp_unslash( $_POST['td_action'] ) ) : '';
	$id     = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
	if ( 'delete' === $action ) {
		$popups = array_values( array_filter( $popups, static fn( $p ) => $p['id'] !== $id ) );
		td_popups_save( $popups );
		wp_safe_redirect( add_query_arg( 'td_msg', 'deleted', td_popups_admin_page_url() ) );
		exit;
	}
	$text = static fn( string $k ): string => isset( $_POST[ $k ] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST[ $k ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
	$variants = [];
	foreach ( [ 'a', 'b' ] as $v ) {
		$variants[] = [
			'title'  => $text( 'title_' . $v ),
			'text'   => $text( 'text_' . $v ),
			'button' => $text( 'button_' . $v ),
		];
	}
	$post_types = isset( $_POST['post_types'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['post_types'] ) ) : [];
	$raw        = [
		'id'                 => '' !== $id ? $id : 'popup-' . strtolower( wp_generate_password( 6, false ) ),
		'name'               => $text( 'name' ),
		'enabled'            => ! empty( $_POST['enabled'] ),
		'type'               => $text( 'type' ),
		'trigger'            => $text( 'trigger' ),
		'trigger_value'      => (int) $text( 'trigger_value' ),
		'site'               => td_site(),
		'post_types'         => $post_types,
		'categories'         => $text( 'categories' ),
		'exclude_categories' => $text( 'exclude_categories' ),
		'include'            => $text( 'include' ),
		'exclude'            => $text( 'exclude' ),
		'offer_type'         => $text( 'offer_type' ),
		'resource'           => $text( 'resource' ),
		'form_key'           => $text( 'form_key' ),
		'frequency_days'     => (int) $text( 'frequency_days' ),
		'variants'           => $variants,
	];
	$popup    = Targeting::normalize( $raw );
	$replaced = false;
	foreach ( $popups as $i => $existing ) {
		if ( $existing['id'] === $popup['id'] ) {
			$popups[ $i ] = $popup;
			$replaced     = true;
		}
	}
	if ( ! $replaced ) {
		$popups[] = $popup;
	}
	td_popups_save( $popups );
	wp_safe_redirect( add_query_arg( 'td_msg', 'saved', td_popups_admin_page_url() ) );
	exit;
}
add_action( 'admin_post_td_popups_save', 'td_popups_admin_handle' );

/**
 * Render the screen (list or edit form).
 */
function td_popups_admin_render(): void {
	if ( ! current_user_can( 'td_manage_popups' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only view switches.
	$edit = isset( $_GET['edit'] ) ? sanitize_key( wp_unslash( $_GET['edit'] ) ) : null;
	$msg  = isset( $_GET['td_msg'] ) ? sanitize_key( wp_unslash( $_GET['td_msg'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	echo '<div class="wrap td-popups-admin">';
	if ( 'saved' === $msg ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Popup saved.', 'techdella' ) . '</p></div>';
	} elseif ( 'deleted' === $msg ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Popup deleted.', 'techdella' ) . '</p></div>';
	}
	if ( null !== $edit ) {
		td_popups_admin_form( $edit );
	} else {
		td_popups_admin_list();
	}
	echo '</div>';
}

/**
 * Human labels.
 *
 * @return array<string,array<string,string>>
 */
function td_popups_admin_labels(): array {
	return [
		'type'    => [
			'exit'    => __( 'Exit-intent modal (desktop only)', 'techdella' ),
			'slidein' => __( 'Scroll slide-in (corner card)', 'techdella' ),
			'topbar'  => __( 'Top bar', 'techdella' ),
			'inline'  => __( 'Inline end-of-post card', 'techdella' ),
		],
		'trigger' => [
			'exit'   => __( 'Exit intent', 'techdella' ),
			'scroll' => __( 'Scroll %', 'techdella' ),
			'time'   => __( 'Time on page (seconds)', 'techdella' ),
			'pages'  => __( 'Pages viewed', 'techdella' ),
		],
		'form'    => [
			'popup_exit'   => __( 'Exit-intent popup (POPUP_EXIT)', 'techdella' ),
			'popup_scroll' => __( 'Post slide-in (POPUP_SCROLL)', 'techdella' ),
			'newsletter'   => __( 'Newsletter (NEWSLETTER)', 'techdella' ),
			'lead_magnet'  => __( 'Resource download (LEAD_MAGNET)', 'techdella' ),
		],
	];
}

/**
 * The list.
 */
function td_popups_admin_list(): void {
	$labels = td_popups_admin_labels();
	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Popups', 'techdella' ) . '</h1> ';
	echo '<a class="page-title-action" href="' . esc_url( add_query_arg( 'edit', '', td_popups_admin_page_url() ) ) . '">' . esc_html__( 'Add popup', 'techdella' ) . '</a>';
	echo '<p>' . esc_html__( 'Popups never show on the booking, audit and Start pages, thank-you pages, tool pages before the tool is used, or in a visitor\'s first 10 seconds. On phones only the slide-in and top bar are used, after 50% scroll or 30 seconds. Impressions and conversions go to GA4 (popup_impression, popup_conversion).', 'techdella' ) . '</p>';
	echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Popup', 'techdella' ) . '</th><th>' . esc_html__( 'Type', 'techdella' ) . '</th><th>' . esc_html__( 'Trigger', 'techdella' ) . '</th><th>' . esc_html__( 'Where', 'techdella' ) . '</th><th>' . esc_html__( 'Offer', 'techdella' ) . '</th><th>' . esc_html__( 'Status', 'techdella' ) . '</th><th><span class="screen-reader-text">' . esc_html__( 'Actions', 'techdella' ) . '</span></th></tr></thead><tbody>';
	$popups = td_popups();
	if ( ! $popups ) {
		echo '<tr><td colspan="7">' . esc_html__( 'No popups yet.', 'techdella' ) . '</td></tr>';
	}
	foreach ( $popups as $p ) {
		$where = array_filter(
			[
				$p['post_types'] ? implode( ', ', $p['post_types'] ) : '',
				$p['categories'] ? 'cat: ' . implode( ', ', $p['categories'] ) : '',
				$p['exclude_categories'] ? 'not cat: ' . implode( ', ', $p['exclude_categories'] ) : '',
				$p['include'] ? implode( ' ', $p['include'] ) : '',
				$p['exclude'] ? 'not ' . implode( ' ', $p['exclude'] ) : '',
			]
		);
		$edit  = add_query_arg( 'edit', $p['id'], td_popups_admin_page_url() );
		echo '<tr><td><a href="' . esc_url( $edit ) . '"><strong>' . esc_html( '' !== $p['name'] ? $p['name'] : $p['id'] ) . '</strong></a>' . ( count( $p['variants'] ) > 1 ? ' <span class="description">A/B</span>' : '' ) . '</td>';
		echo '<td>' . esc_html( $labels['type'][ $p['type'] ] ?? $p['type'] ) . '</td>';
		echo '<td>' . esc_html( ( $labels['trigger'][ $p['trigger'] ] ?? $p['trigger'] ) . ( $p['trigger_value'] ? ' ' . $p['trigger_value'] : '' ) ) . '</td>';
		echo '<td>' . esc_html( $where ? implode( ' · ', $where ) : __( 'Whole site', 'techdella' ) ) . '</td>';
		echo '<td>' . esc_html( 'resource' === $p['offer_type'] ? $p['resource'] : __( 'Newsletter', 'techdella' ) ) . '</td>';
		echo '<td>' . ( $p['enabled'] ? esc_html__( 'On', 'techdella' ) : esc_html__( 'Off', 'techdella' ) ) . '</td>';
		echo '<td><a href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit', 'techdella' ) . '</a> · <form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline" onsubmit="return confirm(\'' . esc_js( __( 'Delete this popup?', 'techdella' ) ) . '\')">';
		wp_nonce_field( 'td_popups_save' );
		echo '<input type="hidden" name="action" value="td_popups_save"><input type="hidden" name="td_action" value="delete"><input type="hidden" name="id" value="' . esc_attr( $p['id'] ) . '"><button type="submit" class="button-link button-link-delete">' . esc_html__( 'Delete', 'techdella' ) . '</button></form></td></tr>';
	}
	echo '</tbody></table>';
}

/**
 * The add/edit form.
 *
 * @param string $id Popup ID ('' = new).
 */
function td_popups_admin_form( string $id ): void {
	$popup = null;
	foreach ( td_popups() as $p ) {
		if ( $p['id'] === $id ) {
			$popup = $p;
		}
	}
	$p      = $popup ?? Targeting::normalize(
		[
			'site'           => td_site(),
			'frequency_days' => 7,
		]
	);
	$labels = td_popups_admin_labels();
	$v      = static fn( int $i, string $k ): string => (string) ( $p['variants'][ $i ][ $k ] ?? '' );
	$field  = static function ( string $name, string $label, string $control, string $help = '' ): void {
		echo '<tr><th scope="row"><label for="td-pop-' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>' . $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- controls are built escaped below.
		if ( '' !== $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	};
	$select = static function ( string $name, array $options, string $current ): string {
		$out = '<select id="td-pop-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';
		foreach ( $options as $value => $label ) {
			$out .= '<option value="' . esc_attr( (string) $value ) . '"' . selected( $current, (string) $value, false ) . '>' . esc_html( (string) $label ) . '</option>';
		}
		return $out . '</select>';
	};
	$input = static fn( string $name, string $value, string $type = 'text', string $extra = '' ): string => '<input class="regular-text" type="' . esc_attr( $type ) . '" id="td-pop-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . $extra . '>';
	$area  = static fn( string $name, string $value ): string => '<textarea class="large-text" rows="3" id="td-pop-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';

	echo '<h1>' . esc_html( $popup ? __( 'Edit popup', 'techdella' ) : __( 'Add popup', 'techdella' ) ) . '</h1>';
	echo '<p><a href="' . esc_url( td_popups_admin_page_url() ) . '">&larr; ' . esc_html__( 'All popups', 'techdella' ) . '</a></p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'td_popups_save' );
	echo '<input type="hidden" name="action" value="td_popups_save"><input type="hidden" name="id" value="' . esc_attr( $popup ? $p['id'] : '' ) . '">';
	echo '<table class="form-table" role="presentation"><tbody>';
	$field( 'name', __( 'Name (admin only)', 'techdella' ), $input( 'name', (string) $p['name'] ) );
	$field( 'enabled', __( 'Status', 'techdella' ), '<label><input type="checkbox" id="td-pop-enabled" name="enabled" value="1"' . checked( (bool) $p['enabled'], true, false ) . '> ' . esc_html__( 'Show this popup', 'techdella' ) . '</label>' );
	$field( 'type', __( 'Type', 'techdella' ), $select( 'type', $labels['type'], (string) $p['type'] ), __( 'Exit-intent modals show on desktop only. Top bars link to the offer page; the other types carry a short form.', 'techdella' ) );
	$field( 'trigger', __( 'Trigger', 'techdella' ), $select( 'trigger', $labels['trigger'], (string) $p['trigger'] ) . ' ' . $input( 'trigger_value', (string) $p['trigger_value'], 'number', ' min="0" max="600" style="width:6em" aria-label="' . esc_attr__( 'Trigger value', 'techdella' ) . '"' ), __( 'Scroll: percent of the page (e.g. 60). Time: seconds (at least 10). Pages: pages viewed in total.', 'techdella' ) );

	$types = '';
	foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $pt ) {
		if ( in_array( $pt->name, [ 'attachment', 'thank-you', 'document' ], true ) ) {
			continue;
		}
		$types .= '<label style="margin-right:1em"><input type="checkbox" name="post_types[]" value="' . esc_attr( $pt->name ) . '"' . checked( in_array( $pt->name, (array) $p['post_types'], true ), true, false ) . '> ' . esc_html( $pt->labels->singular_name ) . '</label> ';
	}
	$field( 'post_types', __( 'Post types', 'techdella' ), '<fieldset id="td-pop-post_types">' . $types . '</fieldset>', __( 'None ticked = every page type.', 'techdella' ) );
	$field( 'categories', __( 'Only in categories', 'techdella' ), $input( 'categories', implode( ', ', (array) $p['categories'] ) ), __( 'Category slugs, comma-separated (posts only). Empty = any.', 'techdella' ) );
	$field( 'exclude_categories', __( 'Not in categories', 'techdella' ), $input( 'exclude_categories', implode( ', ', (array) $p['exclude_categories'] ) ) );
	$field( 'include', __( 'Only on URLs', 'techdella' ), $area( 'include', implode( "\n", (array) $p['include'] ) ), __( 'One path per line; * matches anything, e.g. /services/*. Empty = any.', 'techdella' ) );
	$field( 'exclude', __( 'Never on URLs', 'techdella' ), $area( 'exclude', implode( "\n", (array) $p['exclude'] ) ) );
	$field( 'offer_type', __( 'Offer', 'techdella' ), $select( 'offer_type', [ 'resource' => __( 'Resource (PDF)', 'techdella' ), 'newsletter' => __( 'Newsletter', 'techdella' ) ], (string) $p['offer_type'] ) );

	$resources = [ '' => __( '— choose —', 'techdella' ) ];
	if ( post_type_exists( 'td_resource' ) ) {
		foreach ( get_posts( [ 'post_type' => 'td_resource', 'post_status' => [ 'publish', 'draft', 'pending' ], 'posts_per_page' => 100 ] ) as $r ) {
			$resources[ $r->post_name ] = $r->post_title;
		}
	}
	if ( '' !== $p['resource'] && ! isset( $resources[ $p['resource'] ] ) ) {
		$resources[ $p['resource'] ] = $p['resource'] . ' ' . __( '(resource page not created yet)', 'techdella' );
	}
	$field( 'resource', __( 'Resource', 'techdella' ), $select( 'resource', $resources, (string) $p['resource'] ), __( 'The PDF offered (a Resources page on the Marketing site). Sent to Startbuddi as the offer.', 'techdella' ) );
	$field( 'form_key', __( 'Startbuddi form', 'techdella' ), $select( 'form_key', $labels['form'], (string) $p['form_key'] ) );
	$field( 'frequency_days', __( 'Show again after (days)', 'techdella' ), $input( 'frequency_days', (string) $p['frequency_days'], 'number', ' min="1" max="365" style="width:6em"' ), __( 'Default 7. Never shown again to someone who converted.', 'techdella' ) );
	echo '</tbody></table>';

	foreach ( [ 0 => 'a', 1 => 'b' ] as $i => $letter ) {
		echo '<h2>' . esc_html( 0 === $i ? __( 'Copy (variant A)', 'techdella' ) : __( 'Variant B (optional A/B test, split 50/50)', 'techdella' ) ) . '</h2><table class="form-table" role="presentation"><tbody>';
		$field( 'title_' . $letter, __( 'Headline', 'techdella' ), $input( 'title_' . $letter, $v( $i, 'title' ) ), __( 'Mark the one accent word with *asterisks*, e.g. "Audit your *growth*". Leave variant B empty for no test.', 'techdella' ) );
		$field( 'text_' . $letter, __( 'Text', 'techdella' ), $area( 'text_' . $letter, $v( $i, 'text' ) ) );
		$field( 'button_' . $letter, __( 'Button label', 'techdella' ), $input( 'button_' . $letter, $v( $i, 'button' ) ) );
		echo '</tbody></table>';
	}
	submit_button( __( 'Save popup', 'techdella' ) );
	echo '</form>';
}
