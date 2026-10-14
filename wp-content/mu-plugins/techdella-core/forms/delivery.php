<?php
/**
 * Delivery to Startbuddi (§11.2 step 4): forwarder, encrypted retry queue,
 * WP-cron retries (1m/5m/30m/2h/12h), one admin email after 5 failures,
 * purge after 14 days. Never logs or emails payload contents (no PII).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Forms\Retry;

const TD_FORMS_DB_VERSION = '1';
const TD_FORMS_CRON_HOOK  = 'td_forms_process_queue';
const TD_FORMS_PURGE_HOOK = 'td_forms_purge_queue';

/**
 * Queue table name.
 */
function td_forms_queue_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'td_form_queue';
}

/**
 * Create/upgrade the queue table (dbDelta). Runs on td_setup and lazily on first need.
 *
 * @param bool $force Run dbDelta even if the stored version matches.
 */
function td_forms_install_table( bool $force = false ): void {
	if ( ! $force && get_option( 'td_forms_db_version' ) === TD_FORMS_DB_VERSION ) {
		return;
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table   = td_forms_queue_table();
	$charset = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_key varchar(40) NOT NULL,
			idem_key varchar(64) NOT NULL,
			payload longtext NOT NULL,
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'pending',
			next_attempt_at datetime NOT NULL,
			last_error varchar(190) NOT NULL DEFAULT '',
			notified tinyint(1) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY status_next (status,next_attempt_at),
			KEY created_at (created_at)
		) {$charset};"
	);
	update_option( 'td_forms_db_version', TD_FORMS_DB_VERSION, false );
}

add_action(
	'td_setup',
	static function () {
		td_forms_install_table( true );
	}
);

/**
 * POST a payload to the form's endpoint.
 *
 * @param string              $key     Form key.
 * @param array<string,mixed> $payload Payload.
 * @param string              $idem    Idempotency key (same across retries).
 * @return array{ok: bool, status: int, error: string}
 */
function td_forms_forward( string $key, array $payload, string $idem ): array {
	if ( ! td_form_endpoint_configured( $key ) ) {
		return [
			'ok'     => false,
			'status' => 0,
			'error'  => 'not_configured',
		];
	}
	// Startbuddi endpoint forms take the answers under "data" ({"data": {question_id|key: answer}});
	// keys that match no question are kept with the response, so our field names and `meta` arrive as is.
	$body    = (string) wp_json_encode( [ 'data' => $payload ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$headers = [
		'Content-Type'    => 'application/json; charset=utf-8',
		'Accept'          => 'application/json',
		'Idempotency-Key' => $idem,
	];
	if ( '' !== (string) SB_SHARED_SECRET ) {
		// [CONFIRM Startbuddi signed requests] header name + format.
		$headers['X-TechDella-Signature'] = \TechDella\Core\Forms\Payload::signature( $body, (string) SB_SHARED_SECRET, time() );
	}
	// The endpoint is a trusted constant (not user input), so wp_remote_post() rather than wp_safe_remote_post():
	// locally it is http://sb-mock:8090 (non-standard port, private network).
	$response = wp_remote_post(
		td_form_endpoint( $key ),
		[
			'timeout'     => 8,
			'redirection' => 0,
			'headers'     => $headers,
			'body'        => $body,
			'user-agent'  => 'TechDella-Forms/' . TD_CORE_VERSION . ' (' . td_forms_site_domain() . ')',
		]
	);
	if ( is_wp_error( $response ) ) {
		return [
			'ok'     => false,
			'status' => 0,
			'error'  => Retry::error_class( 0, (string) $response->get_error_code() ),
		];
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	return [
		'ok'     => $status >= 200 && $status < 300,
		'status' => $status,
		'error'  => ( $status >= 200 && $status < 300 ) ? '' : Retry::error_class( $status ),
	];
}

/**
 * Store a failed delivery in the queue (payload encrypted).
 *
 * @param string              $key     Form key.
 * @param array<string,mixed> $payload Payload.
 * @param string              $idem    Idempotency key.
 * @param string              $error   Error class (no PII).
 * @return int Row id (0 on failure).
 */
function td_forms_enqueue( string $key, array $payload, string $idem, string $error ): int {
	global $wpdb;
	td_forms_install_table();
	$now = time();
	$ok  = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		td_forms_queue_table(),
		[
			'form_key'        => $key,
			'idem_key'        => $idem,
			'payload'         => td_forms_crypto()->encrypt( (string) wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ),
			'attempts'        => 1,
			'status'          => 'pending',
			'next_attempt_at' => gmdate( 'Y-m-d H:i:s', $now + (int) Retry::next_delay( 1 ) ),
			'last_error'      => substr( $error, 0, 190 ),
			'notified'        => 0,
			'created_at'      => gmdate( 'Y-m-d H:i:s', $now ),
			'updated_at'      => gmdate( 'Y-m-d H:i:s', $now ),
		],
		[ '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s' ]
	);
	td_forms_schedule_cron( $now + (int) Retry::next_delay( 1 ) );
	return $ok ? (int) $wpdb->insert_id : 0;
}

/**
 * Schedule a single queue run at $when (WP-cron), unless one is already due earlier.
 *
 * @param int $when Unix time.
 */
function td_forms_schedule_cron( int $when ): void {
	$next = wp_next_scheduled( TD_FORMS_CRON_HOOK );
	if ( false === $next || $next > $when ) {
		if ( false !== $next ) {
			wp_unschedule_event( $next, TD_FORMS_CRON_HOOK );
		}
		wp_schedule_single_event( $when, TD_FORMS_CRON_HOOK );
	}
}

/**
 * Queue rows (no payloads decrypted).
 *
 * @param string $status '' for all, or pending|failed.
 * @return array<int,object>
 */
function td_forms_queue_rows( string $status = '' ): array {
	global $wpdb;
	td_forms_install_table();
	$table = td_forms_queue_table();
	if ( '' !== $status ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT id, form_key, attempts, status, next_attempt_at, last_error, notified, created_at FROM {$table} WHERE status = %s ORDER BY id", $status ) );
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return (array) $wpdb->get_results( "SELECT id, form_key, attempts, status, next_attempt_at, last_error, notified, created_at FROM {$table} ORDER BY id" );
}

/**
 * Try delivering queued rows.
 *
 * @param bool     $all Retry every row now (pending and failed), ignoring next_attempt_at.
 * @param int|null $id  Only this row.
 * @return array{delivered: int, failed: int}
 */
function td_forms_process_queue( bool $all = false, ?int $id = null ): array {
	global $wpdb;
	td_forms_install_table();
	$table = td_forms_queue_table();
	$stats = [
		'delivered' => 0,
		'failed'    => 0,
	];
	if ( null !== $id ) {
		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	} elseif ( $all ) {
		$rows = (array) $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id LIMIT 200" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	} else {
		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'pending' AND next_attempt_at <= %s ORDER BY id LIMIT 50", gmdate( 'Y-m-d H:i:s' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	foreach ( $rows as $row ) {
		$json    = td_forms_crypto()->decrypt( (string) $row->payload );
		$payload = null === $json ? null : json_decode( $json, true );
		if ( ! is_array( $payload ) ) {
			// Undecryptable (salts rotated): nothing we can deliver. Mark failed; purge removes it.
			$wpdb->update(
				$table,
				[
					'status'     => 'failed',
					'last_error' => 'decrypt_failed',
					'updated_at' => gmdate( 'Y-m-d H:i:s' ),
				],
				[ 'id' => (int) $row->id ]
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			++$stats['failed'];
			continue;
		}
		$result = td_forms_forward( (string) $row->form_key, $payload, (string) $row->idem_key );
		if ( $result['ok'] ) {
			$wpdb->delete( $table, [ 'id' => (int) $row->id ], [ '%d' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			++$stats['delivered'];
			continue;
		}
		++$stats['failed'];
		$failures = (int) $row->attempts + 1;
		$delay    = Retry::next_delay( $failures );
		$update   = [
			'attempts'   => $failures,
			'last_error' => substr( $result['error'], 0, 190 ),
			'status'     => null === $delay ? 'failed' : 'pending',
			'updated_at' => gmdate( 'Y-m-d H:i:s' ),
		];
		if ( null !== $delay ) {
			$update['next_attempt_at'] = gmdate( 'Y-m-d H:i:s', time() + $delay );
			td_forms_schedule_cron( time() + $delay );
		}
		if ( ! (int) $row->notified && $failures >= Retry::NOTIFY_AFTER ) {
			td_forms_notify_admin( (string) $row->form_key, $failures, $result['error'] );
			$update['notified'] = 1;
		}
		$wpdb->update( $table, $update, [ 'id' => (int) $row->id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
	return $stats;
}

/**
 * The one "Startbuddi delivery failed" email per row. No PII: key, attempts, error class only.
 *
 * @param string $key      Form key.
 * @param int    $attempts Failed attempts.
 * @param string $error    Error class.
 */
function td_forms_notify_admin( string $key, int $attempts, string $error ): void {
	$to   = (string) apply_filters( 'td_forms_alert_email', (string) get_option( 'admin_email' ) );
	$body = implode(
		"\n",
		[
			'A form submission could not be delivered to Startbuddi.',
			'',
			'Site: ' . td_forms_site_domain() . ' (' . td_environment() . ')',
			'Form key: ' . $key,
			'Attempts: ' . $attempts,
			'Last error: ' . $error,
			'',
			'The submission is stored encrypted and is retried once more automatically.',
			'It is deleted 14 days after it was received.',
			'Check SB_ENDPOINT_' . strtoupper( $key ) . ' and Startbuddi, then run: wp td forms:retry --all',
		]
	);
	// Explicit From: WordPress's default (wordpress@<host>) is invalid on localhost hosts.
	$from = (string) apply_filters( 'td_forms_alert_from', 'TechDella Website <noreply@' . td_forms_site_domain() . '>' );
	wp_mail( $to, 'Startbuddi delivery failed', $body, [ 'From: ' . $from ] );
}

/**
 * Delete rows older than 14 days (delivered rows are deleted immediately).
 *
 * @return int Rows deleted.
 */
function td_forms_purge_queue(): int {
	global $wpdb;
	td_forms_install_table();
	$table = td_forms_queue_table();
	return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", gmdate( 'Y-m-d H:i:s', time() - Retry::PURGE_AFTER ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

add_action(
	TD_FORMS_CRON_HOOK,
	static function () {
		td_forms_process_queue();
	}
);
add_action(
	TD_FORMS_PURGE_HOOK,
	static function () {
		td_forms_purge_queue();
	}
);

// Daily purge + safety sweep (picks up rows whose single event was lost).
add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( TD_FORMS_PURGE_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', TD_FORMS_PURGE_HOOK );
		}
	}
);
add_action(
	TD_FORMS_PURGE_HOOK,
	static function () {
		td_forms_process_queue();
	},
	20
);
