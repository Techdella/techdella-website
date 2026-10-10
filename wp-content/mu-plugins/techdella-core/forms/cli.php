<?php
/**
 * WP-CLI: wp td forms:queue | forms:retry | forms:test | forms:export-legacy | forms:reset-limits.
 * Nothing here prints payload contents (no PII).
 */

defined( 'ABSPATH' ) || exit;

/**
 * List queued deliveries (no payloads).
 *
 * ## OPTIONS
 *
 * [--status=<status>]
 * : pending|failed (default: all).
 *
 * [--format=<format>]
 * : table|json|csv|count. Default table.
 *
 * @param string[]             $args  Positional.
 * @param array<string,string> $assoc Options.
 */
function td_forms_cli_queue( array $args, array $assoc ): void {
	$rows = td_forms_queue_rows( (string) ( $assoc['status'] ?? '' ) );
	\WP_CLI\Utils\format_items( (string) ( $assoc['format'] ?? 'table' ), array_map( static fn( $r ) => (array) $r, $rows ), [ 'id', 'form_key', 'attempts', 'status', 'next_attempt_at', 'last_error', 'notified', 'created_at' ] );
}

/**
 * Retry queued deliveries now.
 *
 * ## OPTIONS
 *
 * [--all]
 * : Retry every row (pending and failed), ignoring the schedule. Without it, only rows that are due.
 *
 * [--id=<id>]
 * : Retry one row.
 *
 * @param string[]             $args  Positional.
 * @param array<string,string> $assoc Options.
 */
function td_forms_cli_retry( array $args, array $assoc ): void {
	$stats = td_forms_process_queue( ! empty( $assoc['all'] ), isset( $assoc['id'] ) ? (int) $assoc['id'] : null );
	WP_CLI::success( sprintf( 'Delivered %d, still failing %d.', $stats['delivered'], $stats['failed'] ) );
}

/**
 * Send a TEST payload ("test": true) to a form's configured endpoint.
 *
 * ## OPTIONS
 *
 * <key>
 * : Form key, e.g. audit.
 *
 * @param string[]             $args  Positional.
 * @param array<string,string> $assoc Options.
 */
function td_forms_cli_test( array $args, array $assoc ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- WP-CLI signature.
	$key    = (string) ( $args[0] ?? '' );
	$schema = td_form_schema( $key );
	if ( ! $schema ) {
		WP_CLI::error( 'Unknown form key. Keys: ' . implode( ', ', td_forms_keys() ) );
	}
	if ( ! td_form_endpoint_configured( $key ) ) {
		WP_CLI::error( 'SB_ENDPOINT_' . strtoupper( $key ) . ' is not set.' );
	}
	$clean = [];
	foreach ( (array) $schema['fields'] as $name => $f ) {
		$clean[ $name ] = td_forms_sample_value( (string) $name, (array) $f );
	}
	$payload = \TechDella\Core\Forms\Payload::build(
		$key,
		(array) $schema['fields'],
		$clean,
		[
			'site'         => td_forms_site_domain(),
			'site_key'     => td_site(),
			'environment'  => td_environment(),
			'page_url'     => home_url( '/' ),
			'referrer'     => '',
			'utm_first'    => (object) [],
			'utm_last'     => (object) [],
			'submitted_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		],
		true
	);
	$result  = td_forms_forward( $key, $payload, wp_generate_uuid4() );
	if ( ! $result['ok'] ) {
		WP_CLI::error( 'Delivery failed: ' . $result['error'] );
	}
	WP_CLI::success( "TEST payload delivered to the {$key} endpoint (HTTP {$result['status']})." );
}

/**
 * Sample value for a test payload.
 *
 * @param string              $name Field name.
 * @param array<string,mixed> $f    Definition.
 * @return mixed
 */
function td_forms_sample_value( string $name, array $f ) {
	$keys = array_map( 'strval', array_keys( (array) ( $f['options'] ?? [] ) ) );
	switch ( $f['type'] ?? 'text' ) {
		case 'email':
			return 'test@example.com';
		case 'url':
			return 'https://example.com';
		case 'checkbox':
			return false;
		case 'pills':
			return ! empty( $f['multiple'] ) ? array_slice( $keys, 0, 1 ) : ( $keys[0] ?? '' );
		case 'select':
			return $keys[0] ?? '';
		case 'hidden':
			return 'json' === ( $f['format'] ?? '' ) ? [ 'test' => true ] : 'test-' . str_replace( '_', '-', $name );
	}
	return 'TEST ' . $name;
}

/**
 * Export a legacy lead table (td_form_submissions, founders_hub…) to CSV, optionally drop it.
 * §11.2: export for the owner, then drop [CONFIRM — after export].
 *
 * ## OPTIONS
 *
 * --table=<name>
 * : Table name, with or without the $wpdb prefix.
 *
 * --file=<csv>
 * : Output CSV path (must not exist).
 *
 * [--drop]
 * : Drop the table after a successful export. Requires --yes.
 *
 * [--yes]
 * : Confirm the drop.
 *
 * @param string[]             $args  Positional.
 * @param array<string,string> $assoc Options.
 */
function td_forms_cli_export_legacy( array $args, array $assoc ): void {
	global $wpdb;
	$name = (string) ( $assoc['table'] ?? '' );
	$file = (string) ( $assoc['file'] ?? '' );
	if ( ! preg_match( '/^[A-Za-z0-9_]{1,64}$/', $name ) || '' === $file ) {
		WP_CLI::error( 'Usage: wp td forms:export-legacy --table=<name> --file=<csv> [--drop --yes]' );
	}
	$table = null;
	foreach ( [ $name, $wpdb->prefix . $name ] as $candidate ) {
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $candidate ) ) ) === $candidate ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table = $candidate;
			break;
		}
	}
	if ( null === $table ) {
		WP_CLI::error( "Table {$name} not found." );
	}
	if ( in_array( $table, [ $wpdb->posts, $wpdb->postmeta, $wpdb->users, $wpdb->usermeta, $wpdb->options, td_forms_queue_table() ], true ) ) {
		WP_CLI::error( 'Refusing to export/drop a core table.' );
	}
	if ( file_exists( $file ) ) {
		WP_CLI::error( "{$file} already exists." );
	}
	$fh = fopen( $file, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	if ( ! $fh ) {
		WP_CLI::error( "Cannot write {$file}." );
	}
	$rows   = 0;
	$offset = 0;
	$header = false;
	do {
		$chunk = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` LIMIT %d OFFSET %d", 500, $offset ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( $chunk as $row ) {
			if ( ! $header ) {
				fputcsv( $fh, array_keys( $row ) );
				$header = true;
			}
			fputcsv( $fh, array_values( $row ) );
			++$rows;
		}
		$offset += 500;
		$more    = 500 === count( $chunk );
	} while ( $more );
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	WP_CLI::success( "Exported {$rows} rows from {$table} to {$file}. This file contains personal data: hand it to the owner and delete it." );

	if ( ! empty( $assoc['drop'] ) ) {
		if ( empty( $assoc['yes'] ) ) {
			WP_CLI::warning( 'Not dropping: pass --drop --yes to drop the table after export.' );
			return;
		}
		$wpdb->query( "DROP TABLE `{$table}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		WP_CLI::success( "Dropped {$table}." );
	}
}

/**
 * Clear the forms rate-limit counters (local/staging only; used by e2e tests).
 *
 * @param string[]             $args  Positional.
 * @param array<string,string> $assoc Options.
 */
function td_forms_cli_reset_limits( array $args, array $assoc ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- WP-CLI signature.
	global $wpdb;
	if ( td_is_production() ) {
		WP_CLI::error( 'Not available in production.' );
	}
	$n = (int) $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_td\\_frl\\_%' OR option_name LIKE '\\_transient\\_timeout\\_td\\_frl\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	wp_cache_flush();
	WP_CLI::success( "Cleared {$n} rate-limit rows." );
}

WP_CLI::add_command( 'td forms:queue', 'td_forms_cli_queue' );
WP_CLI::add_command( 'td forms:retry', 'td_forms_cli_retry' );
WP_CLI::add_command( 'td forms:test', 'td_forms_cli_test' );
WP_CLI::add_command( 'td forms:export-legacy', 'td_forms_cli_export_legacy' );
WP_CLI::add_command( 'td forms:reset-limits', 'td_forms_cli_reset_limits' );
