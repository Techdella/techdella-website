<?php
/**
 * Admin notices for the forms module (admins only): Turnstile not configured,
 * Startbuddi endpoints missing, deliveries waiting in the queue.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_notices',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$notes = [];
		if ( '' === (string) TURNSTILE_SECRET_KEY || '' === (string) TURNSTILE_SITE_KEY ) {
			$notes[] = td_is_production()
				? __( 'Cloudflare Turnstile is not configured (TURNSTILE_SITE_KEY / TURNSTILE_SECRET_KEY). Forms still check the token, honeypot, timing and rate limit, but bots are not challenged. Add the keys in wp-config.php.', 'techdella' )
				: __( 'Cloudflare Turnstile is not configured, so the forms skip the Turnstile check on this environment.', 'techdella' );
		}
		$missing = array_values( array_filter( td_forms_keys_for_site(), static fn( $k ) => ! td_form_endpoint_configured( $k ) ) );
		if ( $missing ) {
			/* translators: %s: list of env constants. */
			$notes[] = sprintf( __( 'Startbuddi endpoints are missing for: %s. Submissions to these forms are kept in the encrypted retry queue until an endpoint is set.', 'techdella' ), implode( ', ', array_map( static fn( $k ) => 'SB_ENDPOINT_' . strtoupper( $k ), $missing ) ) );
		}
		if ( get_option( 'td_forms_db_version' ) ) {
			$failed = count( td_forms_queue_rows( 'failed' ) );
			if ( $failed ) {
				/* translators: %d: number of submissions. */
				$notes[] = sprintf( _n( '%d form submission could not be delivered to Startbuddi. Run "wp td forms:retry --all" once the endpoint works.', '%d form submissions could not be delivered to Startbuddi. Run "wp td forms:retry --all" once the endpoints work.', $failed, 'techdella' ), $failed );
			}
		}
		foreach ( $notes as $note ) {
			echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Forms', 'techdella' ) . ':</strong> ' . esc_html( $note ) . '</p></div>';
		}
	}
);
