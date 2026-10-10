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
}
