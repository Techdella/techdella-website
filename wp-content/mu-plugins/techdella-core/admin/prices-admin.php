<?php
/**
 * Growth → Prices (§14.2): edit every published price (USD and naira) without code.
 * Capability td_manage_prices (admins + editors). Stored per site in the td_prices option as
 * [ key => [ 'usd' => int, 'ngn' => int|null ] ]; td_prices() in the theme merges it over the
 * defaults, and every {{price:key}} token on the site updates. Empty dollars = the default price;
 * empty naira = dollars × 200, never below ₦300,000.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Admin\Prices;

/**
 * Parent menu: Growth when that group exists on this site, else Content, else Tools.
 */
function td_prices_admin_parent(): string {
	$groups = function_exists( 'td_admin_groups_for_site' ) ? td_admin_groups_for_site() : [];
	foreach ( [ 'td-growth', 'td-content' ] as $slug ) {
		if ( in_array( $slug, $groups, true ) ) {
			return $slug;
		}
	}
	return 'tools.php';
}

/**
 * Admin page URL.
 */
function td_prices_admin_url(): string {
	$parent = td_prices_admin_parent();
	return admin_url( ( str_ends_with( $parent, '.php' ) ? $parent : 'admin.php' ) . '?page=td-prices' );
}

add_action(
	'admin_menu',
	static function () {
		if ( ! function_exists( 'td_price_defaults' ) ) {
			return; // The theme defines the price list.
		}
		add_submenu_page( td_prices_admin_parent(), __( 'Prices', 'techdella' ), __( 'Prices', 'techdella' ), 'td_manage_prices', 'td-prices', 'td_prices_admin_render' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- granted on td_setup.
	},
	11
);

/**
 * Save handler.
 */
function td_prices_admin_handle(): void {
	if ( ! current_user_can( 'td_manage_prices' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
		wp_die( esc_html__( 'You cannot edit prices.', 'techdella' ), 403 );
	}
	check_admin_referer( 'td_prices_save' );
	$usd   = isset( $_POST['usd'] ) ? (array) wp_unslash( $_POST['usd'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- parsed to integers below.
	$ngn   = isset( $_POST['ngn'] ) ? (array) wp_unslash( $_POST['ngn'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- parsed to integers below.
	$saved = [];
	$bad   = 0;
	foreach ( array_keys( td_price_defaults() ) as $key ) {
		$typed_usd = sanitize_text_field( (string) ( $usd[ $key ] ?? '' ) );
		$typed_ngn = sanitize_text_field( (string) ( $ngn[ $key ] ?? '' ) );
		$d         = Prices::parse( $typed_usd );
		$n         = Prices::parse( $typed_ngn );
		$bad      += ( '' !== $typed_usd && null === $d ) || ( '' !== $typed_ngn && null === $n ) ? 1 : 0;
		if ( null !== $d ) {
			$saved[ $key ] = [
				'usd' => $d,
				'ngn' => $n,
			];
		}
	}
	update_option( 'td_prices', $saved, true );
	do_action( 'litespeed_purge_all' ); // Cached pages show the new prices straight away.
	wp_safe_redirect( add_query_arg( 'td_msg', $bad ? 'partial' : 'saved', td_prices_admin_url() ) );
	exit;
}
add_action( 'admin_post_td_prices_save', 'td_prices_admin_handle' );

/**
 * The screen.
 */
function td_prices_admin_render(): void {
	if ( ! current_user_can( 'td_manage_prices' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
		return;
	}
	$msg      = isset( $_GET['td_msg'] ) ? sanitize_key( wp_unslash( $_GET['td_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice.
	$defaults = td_price_defaults();
	$labels   = td_price_labels();
	$saved    = (array) get_option( 'td_prices', [] );
	$group    = '';
	echo '<div class="wrap td-prices-admin"><h1>' . esc_html__( 'Prices', 'techdella' ) . '</h1>';
	if ( 'saved' === $msg ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Prices saved. Every page that shows them is updated.', 'techdella' ) . '</p></div>';
	} elseif ( 'partial' === $msg ) {
		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Saved, but some boxes were not a number and were ignored. Type amounts like 1500 or 1,500.', 'techdella' ) . '</p></div>';
	}
	echo '<p>' . esc_html__( 'Type whole amounts (1500 or 1,500). Leave dollars empty to use the default. Leave naira empty to use dollars × 200, never below ₦300,000. Visitors in Nigeria see naira; everyone else sees dollars.', 'techdella' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'td_prices_save' );
	echo '<input type="hidden" name="action" value="td_prices_save">';
	echo '<table class="widefat striped td-prices-table" style="max-width:960px"><thead><tr><th scope="col">' . esc_html__( 'Price', 'techdella' ) . '</th><th scope="col">' . esc_html__( 'US dollars', 'techdella' ) . '</th><th scope="col">' . esc_html__( 'Naira', 'techdella' ) . '</th><th scope="col">' . esc_html__( 'Default', 'techdella' ) . '</th></tr></thead><tbody>';
	foreach ( $defaults as $key => $pair ) {
		[ $g, $name ] = $labels[ $key ] ?? [ '', $key ];
		if ( $g !== $group ) {
			$group = $g;
			echo '<tr><th colspan="4" scope="colgroup" style="background:#f6f7f7"><strong>' . esc_html( $g ) . '</strong></th></tr>';
		}
		$row  = is_array( $saved[ $key ] ?? null ) ? $saved[ $key ] : [];
		$id   = 'td-price-' . $key;
		$usd  = ! empty( $row['usd'] ) ? number_format( (int) $row['usd'] ) : '';
		$ngn  = ! empty( $row['ngn'] ) ? number_format( (int) $row['ngn'] ) : '';
		$auto = ! empty( $row['usd'] ) ? Prices::ngn( Prices::ngn_from_usd( (int) $row['usd'] ) ) : $pair[1];
		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s</label><br><code style="font-size:11px">{{price:%3$s}}</code></th><td>$ <input type="text" inputmode="numeric" id="%1$s" name="usd[%3$s]" value="%4$s" placeholder="%5$s" size="10"></td><td>₦ <input type="text" inputmode="numeric" name="ngn[%3$s]" value="%6$s" placeholder="%7$s" size="12" aria-label="%8$s"></td><td>%9$s</td></tr>',
			esc_attr( $id ),
			esc_html( $name ),
			esc_attr( $key ),
			esc_attr( $usd ),
			esc_attr( ltrim( $pair[0], '$' ) ),
			esc_attr( $ngn ),
			esc_attr( ltrim( $auto, '₦' ) ),
			/* translators: %s: price name */
			esc_attr( sprintf( __( '%s in naira', 'techdella' ), $name ) ),
			esc_html( $pair[0] . ' · ' . $pair[1] )
		);
	}
	echo '</tbody></table>';
	submit_button( __( 'Save prices', 'techdella' ) );
	echo '</form></div>';
}
