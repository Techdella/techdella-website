<?php
/**
 * WP-CLI command registration: wp td setup | import-content | redirects | audit | og:generate | permissions:grant.
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
 * [--update-published]
 * : Local and staging only: also update posts that are already published (status unchanged;
 * WordPress keeps the old content as a revision). Production never does this.
 *
 * [--publish]
 * : Local and staging only: publish what is imported, so the private staging site can be
 * reviewed as a whole. Production never does this (§0.4).
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
	$dry = (bool) Utils\get_flag_value( $assoc, 'dry-run', false );
	if ( Utils\get_flag_value( $assoc, 'update-published', false ) ) {
		if ( ! in_array( td_environment(), [ 'local', 'staging' ], true ) ) {
			WP_CLI::error( '--update-published only works on local and staging sites.' );
		}
		add_filter( 'td_import_update_published', '__return_true' );
	}
	if ( Utils\get_flag_value( $assoc, 'publish', false ) ) {
		if ( ! in_array( td_environment(), [ 'local', 'staging' ], true ) ) {
			WP_CLI::error( '--publish only works on local and staging sites.' );
		}
		add_filter( 'td_import_publish', '__return_true' );
		add_filter( 'td_checklist_bypass', '__return_true' );
	}
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

/**
 * Record the owner's written confirmation that clients approved their names, numbers, quotes and
 * logos, by setting permission_confirmed on every case study and testimonial (or the given IDs).
 * Logs who confirmed it and when, on each post (_td_permission_log). Publishing stays manual.
 *
 * ## OPTIONS
 *
 * [<id>...]
 * : Post IDs. Default: every case_study and td_testimonial.
 *
 * --note=<note>
 * : Who confirmed and how, e.g. "Joanna Okedara-Kalu confirmed all client permissions, 11 Oct 2026".
 *
 * [--dry-run]
 * : List what would change.
 *
 * ## EXAMPLES
 *
 *     wp td permissions:grant --note="Owner confirmed all client permissions, 11 Oct 2026"
 *
 * @param string[]             $args  Post IDs.
 * @param array<string,string> $assoc Options.
 */
function td_cli_permissions_grant( array $args, array $assoc ): void {
	$note = trim( (string) ( $assoc['note'] ?? '' ) );
	if ( '' === $note ) {
		WP_CLI::error( 'Say who confirmed the permissions: --note="…".' );
	}
	$ids  = $args ? array_map( 'intval', $args ) : get_posts(
		[
			'post_type'      => array_filter( [ 'case_study', 'td_testimonial' ], 'post_type_exists' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		]
	);
	$dry  = (bool) Utils\get_flag_value( $assoc, 'dry-run', false );
	$done = 0;
	foreach ( $ids as $id ) {
		$type = (string) get_post_type( $id );
		if ( ! in_array( $type, [ 'case_study', 'td_testimonial' ], true ) ) {
			WP_CLI::warning( sprintf( '#%d is a %s, skipped.', $id, $type ) );
			continue;
		}
		WP_CLI::log( sprintf( '%s #%d %s', $dry ? 'would grant' : 'granted', $id, get_the_title( $id ) ) );
		if ( ! $dry ) {
			if ( function_exists( 'update_field' ) ) {
				update_field( 'permission_confirmed', 1, $id );
			}
			update_post_meta( $id, 'permission_confirmed', 1 );
			add_post_meta(
				$id,
				'_td_permission_log',
				[
					'note' => $note,
					'by'   => get_current_user_id(),
					'time' => gmdate( 'c' ),
				]
			);
			++$done;
		}
	}
	WP_CLI::success( sprintf( '%d post(s) %s.', $dry ? count( $ids ) : $done, $dry ? 'would change' : 'updated' ) );
}

WP_CLI::add_command( 'td setup', 'td_cli_setup' );
WP_CLI::add_command( 'td import-content', 'td_cli_import_content' );
WP_CLI::add_command( 'td redirects', 'TD_CLI_Redirects' );
WP_CLI::add_command( 'td redirects:import', [ new TD_CLI_Redirects(), 'import' ] );
WP_CLI::add_command( 'td redirects:verify', [ new TD_CLI_Redirects(), 'verify' ] );
WP_CLI::add_command( 'td audit', 'TD_CLI_Audit' );
WP_CLI::add_command( 'td og:generate', 'td_cli_og_generate' );
WP_CLI::add_command( 'td permissions:grant', 'td_cli_permissions_grant' );

/**
 * Unpublish old pages that the redirects already send elsewhere (status → draft, never deleted),
 * so a copy of the old site doesn't keep them in sitemaps, menus and search.
 *
 * ## OPTIONS
 *
 * [--dry-run]
 * : List what would change.
 *
 * [--production]
 * : Required on production, as a deliberate step at launch.
 *
 * ## EXAMPLES
 *
 *     wp td legacy:retire --dry-run
 *
 * @param string[]             $args  Unused.
 * @param array<string,string> $assoc Options.
 */
function td_cli_legacy_retire( array $args, array $assoc ): void {
	$dry = (bool) Utils\get_flag_value( $assoc, 'dry-run', false );
	if ( td_is_production() && ! $dry && ! Utils\get_flag_value( $assoc, 'production', false ) ) {
		WP_CLI::error( 'On production, add --production (after a --dry-run).' );
	}
	$rules = td_redirect_rules();
	if ( ! $rules ) {
		WP_CLI::error( 'No redirect rules loaded: run wp td redirects import first.' );
	}
	$types = array_values( array_filter( [ 'page', 'service', 'solution', 'location', 'case_study', 'td_tool' ], 'post_type_exists' ) );
	$ids   = get_posts(
		[
			'post_type'      => $types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		]
	);
	$rows  = [];
	foreach ( $ids as $id ) {
		$path = (string) wp_parse_url( (string) get_permalink( $id ), PHP_URL_PATH );
		$hit  = \TechDella\Core\Cli\RedirectRules::match( $rules, $path );
		if ( ! $hit || trim( (string) wp_parse_url( $hit['target'], PHP_URL_PATH ), '/' ) === trim( $path, '/' ) ) {
			continue;
		}
		if ( ! $dry ) {
			wp_update_post(
				[
					'ID'          => (int) $id,
					'post_status' => 'draft',
				]
			);
		}
		$rows[] = [
			'id'     => $id,
			'type'   => get_post_type( $id ),
			'path'   => $path,
			'target' => $hit['target'],
			'result' => $dry ? 'would unpublish' : 'unpublished (draft)',
		];
	}
	// Old pages on a hub address (/case-studies/, /glossary/, /business/ …) hide the new hub
	// template, which serves the same URL: unpublish the page so the hub shows.
	foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $pto ) {
		if ( ! $pto->has_archive ) {
			continue;
		}
		$base = is_string( $pto->has_archive ) ? $pto->has_archive : (string) ( $pto->rewrite['slug'] ?? $pto->name );
		$page = get_page_by_path( $base, OBJECT, 'page' );
		if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
			continue;
		}
		if ( ! $dry ) {
			$tpl  = (string) get_post_meta( $page->ID, '_wp_page_template', true );
			$data = [
				'ID'          => (int) $page->ID,
				'post_status' => 'draft',
			];
			if ( '' !== $tpl && 'default' !== $tpl && ! isset( wp_get_theme()->get_page_templates( $page, 'page' )[ $tpl ] ) ) {
				$data['page_template'] = 'default';
			}
			wp_update_post( $data );
		}
		$rows[] = [
			'id'     => $page->ID,
			'type'   => 'page',
			'path'   => '/' . $base . '/',
			'target' => '(new ' . $pto->labels->name . ' hub)',
			'result' => $dry ? 'would unpublish' : 'unpublished (draft)',
		];
	}
	Utils\format_items( 'table', $rows, [ 'id', 'type', 'path', 'target', 'result' ] );
	WP_CLI::success( sprintf( '%d old page(s) %s.', count( $rows ), $dry ? 'would be unpublished' : 'unpublished' ) );
}

WP_CLI::add_command( 'td legacy:retire', 'td_cli_legacy_retire' );
