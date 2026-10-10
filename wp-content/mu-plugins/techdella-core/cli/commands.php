<?php
/**
 * WP-CLI command registration: wp td setup | import-content | redirects | audit | og:generate.
 */

defined( 'ABSPATH' ) || exit;

use WP_CLI\Utils;

/**
 * Idempotent site setup: permalinks, comments, timezone, styleguide + legal
 * stubs, Rank Math modules, then every module's td_setup step.
 *
 * ## OPTIONS
 *
 * [--quiet]
 * : Only print errors.
 *
 * ## EXAMPLES
 *
 *     wp td setup
 *
 * @param string[]             $args  Positional.
 * @param array<string,string> $assoc Options.
 */
function td_cli_setup( array $args, array $assoc ): void {
	$log = td_setup_run();
	if ( ! Utils\get_flag_value( $assoc, 'quiet', false ) ) {
		foreach ( $log as $line ) {
			WP_CLI::log( '  ' . $line );
		}
		WP_CLI::success( sprintf( 'Setup done for %s.', td_site() ) );
	}
}

/**
 * Import Markdown + front matter from content/ as drafts (or pending), matched
 * by slug + post type. Never publishes; published posts are skipped.
 *
 * Note: `--path` is a WP-CLI global option (the WordPress install), so the
 * content location is passed as a positional argument or --dir.
 *
 * ## OPTIONS
 *
 * [<dir>]
 * : File or directory (recursive), e.g. /var/www/repo/content/journal.
 *
 * [--dir=<dir>]
 * : Same as <dir>.
 *
 * [--status=<status>]
 * : draft or pending.
 * ---
 * default: draft
 * ---
 *
 * [--dry-run]
 * : Report only.
 *
 * ## EXAMPLES
 *
 *     wp td import-content /var/www/repo/content/journal --status=draft
 *
 * @param string[]             $args  Positional.
 * @param array<string,string> $assoc Options.
 */
function td_cli_import_content( array $args, array $assoc ): void {
	$status = (string) ( $assoc['status'] ?? 'draft' );
	if ( ! in_array( $status, [ 'draft', 'pending' ], true ) ) {
		WP_CLI::error( 'Status must be draft or pending. Content is never auto-published (§0.4): a named person reviews and publishes.' );
	}
	$path = (string) ( $args[0] ?? ( $assoc['dir'] ?? '' ) );
	if ( '' === $path || ! file_exists( $path ) ) {
		WP_CLI::error( 'Pass the content directory or file: wp td import-content /var/www/repo/content/journal (in Docker). --path is WP-CLI\'s own option.' );
	}
	$dry   = (bool) Utils\get_flag_value( $assoc, 'dry-run', false );
	$files = td_import_files( $path );
	$base  = dirname( is_dir( $path ) ? rtrim( $path, '/' ) : dirname( $path ) );
	// Repo root: the folder that holds content/.
	$real = (string) realpath( $path );
	if ( preg_match( '#^(.*)/content(/|$)#', $real, $m ) ) {
		$base = $m[1];
	}
	$rows   = [];
	$counts = [
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'error'   => 0,
	];
	foreach ( $files as $file ) {
		$row    = td_import_file( $file, $status, $dry, $base );
		$rows[] = $row;
		$kind   = (string) strtok( $row['result'], ' :' );
		if ( isset( $counts[ $kind ] ) ) {
			++$counts[ $kind ];
		}
	}
	if ( $rows ) {
		Utils\format_items( 'table', $rows, [ 'file', 'type', 'slug', 'result', 'confirm' ] );
	}
	$msg = sprintf( '%d created, %d updated, %d skipped, %d errors; %d [CONFIRM] tokens to resolve.%s', $counts['created'], $counts['updated'], $counts['skipped'], $counts['error'], array_sum( array_column( $rows, 'confirm' ) ), $dry ? ' (dry run)' : '' );
	if ( $counts['error'] ) {
		WP_CLI::error( $msg );
	}
	WP_CLI::success( $msg );
}

/**
 * Generate 1200×630 OG images (§8.5) for published posts.
 *
 * ## OPTIONS
 *
 * [--post_type=<types>]
 * : Comma-separated post types (default: all public types).
 *
 * [--id=<ids>]
 * : Comma-separated post IDs.
 *
 * [--force]
 * : Regenerate even when title/category are unchanged.
 *
 * @param string[]             $args  Positional.
 * @param array<string,string> $assoc Options.
 */
function td_cli_og_generate( array $args, array $assoc ): void {
	if ( ! function_exists( 'imagettftext' ) ) {
		WP_CLI::error( 'PHP GD with FreeType is required.' );
	}
	$query = [
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'post_type'      => ! empty( $assoc['post_type'] ) ? array_map( 'trim', explode( ',', (string) $assoc['post_type'] ) ) : td_og_post_types(),
	];
	if ( ! empty( $assoc['id'] ) ) {
		$query['post__in']    = array_map( 'intval', explode( ',', (string) $assoc['id'] ) );
		$query['post_type']   = 'any';
		$query['post_status'] = 'any';
	}
	$ids   = get_posts( $query );
	$force = (bool) Utils\get_flag_value( $assoc, 'force', false );
	$done  = 0;
	foreach ( $ids as $id ) {
		$att = td_og_generate( (int) $id, $force );
		if ( is_wp_error( $att ) ) {
			WP_CLI::warning( sprintf( '#%d: %s', $id, $att->get_error_message() ) );
			continue;
		}
		++$done;
		WP_CLI::log( sprintf( '#%d → %s', $id, wp_get_attachment_url( (int) $att ) ) );
	}
	WP_CLI::success( sprintf( '%d OG image(s) ready.', $done ) );
}

WP_CLI::add_command( 'td setup', 'td_cli_setup' );
WP_CLI::add_command( 'td import-content', 'td_cli_import_content' );
WP_CLI::add_command( 'td redirects', 'TD_CLI_Redirects' );
WP_CLI::add_command( 'td redirects:import', [ new TD_CLI_Redirects(), 'import' ] );
WP_CLI::add_command( 'td redirects:verify', [ new TD_CLI_Redirects(), 'verify' ] );
WP_CLI::add_command( 'td audit', 'TD_CLI_Audit' );
WP_CLI::add_command( 'td og:generate', 'td_cli_og_generate' );
