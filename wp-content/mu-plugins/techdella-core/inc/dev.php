<?php
/**
 * Local/staging conveniences. Nothing here runs in production.
 */

defined( 'ABSPATH' ) || exit;

// Route every wp_mail() to Mailpit locally (§4.1).
if ( 'local' === td_environment() && '' !== TD_SMTP_HOST ) {
	add_action(
		'phpmailer_init',
		static function ( $mailer ) {
			$mailer->isSMTP();
			$mailer->Host        = TD_SMTP_HOST; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$mailer->Port        = (int) ( TD_SMTP_PORT ? TD_SMTP_PORT : 1025 ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$mailer->SMTPAuth    = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$mailer->SMTPAutoTLS = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}
	);
	// WordPress defaults to wordpress@localhost, which PHPMailer rejects.
	add_filter( 'wp_mail_from', static fn() => 'noreply@techdella.com' );
}

/*
 * `make share` preview tunnels (§4.2): when a local site is reached through a
 * Cloudflare quick tunnel, serve it under the tunnel's host so links and
 * assets work, and keep wp-admin/login off the public tunnel (local admin
 * credentials are not secret).
 */
if ( 'local' === td_environment() ) {
	$td_share_host = isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) : ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' );
	if ( preg_match( '/^[a-z0-9-]+\.trycloudflare\.com$/', $td_share_host ) ) {
		$_SERVER['HTTPS'] = 'on';
		$td_share_base    = 'https://' . $td_share_host;
		foreach ( [ 'option_home', 'option_siteurl' ] as $td_share_filter ) {
			add_filter( $td_share_filter, static fn() => $td_share_base );
		}
		$td_share_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		if ( preg_match( '#^/(wp-admin|wp-login\.php|xmlrpc\.php)#', $td_share_path ) && ! str_starts_with( $td_share_path, '/wp-admin/admin-ajax.php' ) ) {
			status_header( 403 );
			exit( 'Admin is not available on the preview tunnel.' );
		}
		unset( $td_share_base, $td_share_filter, $td_share_path );
	}
	unset( $td_share_host );
}
