<?php
/**
 * WP-CLI command registration: wp td setup | import-content | redirects | audit | og:generate.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Cli\Decay;
use TechDella\Core\Cli\RedirectRules;
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
 * Redirects from data/redirects.csv (§9): import, verify, export.
 */
final class TD_CLI_Redirects {

	/**
	 * Read + parse the CSV, applying the local-domain rewrite.
	 *
	 * @param array<string,string> $assoc Options.
	 * @return array<int,array<string,mixed>> Active rules.
	 */
	private function rules( array $assoc ): array {
		$file   = (string) ( $assoc['file'] ?? td_redirects_csv_path() );
		$parsed = td_redirects_load_csv( $file );
		foreach ( $parsed['errors'] as $error ) {
			WP_CLI::warning( $error );
		}
		if ( ! $parsed['rules'] ) {
			WP_CLI::error( 'No rules read from ' . $file );
		}
		$rules = RedirectRules::active( $parsed['rules'], (bool) Utils\get_flag_value( $assoc, 'include-conditional', false ) );
		$local = (bool) Utils\get_flag_value( $assoc, 'local-domains', 'local' === td_environment() );
		if ( $local ) {
			$map = td_redirects_domain_map();
			foreach ( $rules as &$rule ) {
				$rule['target'] = RedirectRules::rewrite_domain( (string) $rule['target'], $map );
			}
			unset( $rule );
		}
		$skipped = count( $parsed['rules'] ) - count( $rules );
		if ( $skipped ) {
			WP_CLI::log( sprintf( '%d conditional rule(s) skipped (GSC check first). Use --include-conditional after the check.', $skipped ) );
		}
		return $rules;
	}

	/**
	 * Import rules into Rank Math Redirections (when active) and the fallback store.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<file>]
	 * : CSV (default data/redirects.csv).
	 *
	 * [--include-conditional]
	 * : Also import rows with a condition such as "GSC check first".
	 *
	 * [--[no-]local-domains]
	 * : Rewrite production domains to this environment's site URLs (default on locally).
	 *
	 * [--dry-run]
	 * : Report only.
	 *
	 * @param string[]             $args  Positional.
	 * @param array<string,string> $assoc Options.
	 */
	public function import( array $args, array $assoc ): void {
		$rules = $this->rules( $assoc );
		$dry   = (bool) Utils\get_flag_value( $assoc, 'dry-run', false );
		foreach ( RedirectRules::chains( $rules, [ untrailingslashit( home_url() ) ] ) as $chain ) {
			WP_CLI::warning( sprintf( 'Chain: %s → %s → %s. Point the first rule at the final URL.', $chain['source'], $chain['target'], $chain['next'] ) );
		}
		if ( ! $dry ) {
			update_option(
				'td_redirects',
				[
					'rules'       => $rules,
					'imported_at' => time(),
					'file'        => (string) ( $assoc['file'] ?? td_redirects_csv_path() ),
				],
				true
			);
		}
		if ( td_redirects_rank_math_active() ) {
			$report = td_redirects_import_rank_math( $rules, $dry );
			Utils\format_items( 'table', $report, [ 'source', 'target', 'result' ] );
			WP_CLI::success( sprintf( '%d rule(s) %s into Rank Math Redirections.', count( $rules ), $dry ? 'would be imported' : 'imported' ) );
			return;
		}
		Utils\format_items(
			'table',
			array_map(
				static fn( $r ) => [
					'source' => $r['source'],
					'kind'   => $r['kind'],
					'target' => $r['target'],
				],
				$rules
			),
			[ 'source', 'kind', 'target' ]
		);
		WP_CLI::success( sprintf( '%d rule(s) %s the fallback redirector (Rank Math Redirections is off).', count( $rules ), $dry ? 'would be stored for' : 'stored for' ) );
	}

	/**
	 * Request every source and assert ONE hop, a 301 to the expected target,
	 * and a 200 target. Exit code 1 on chains, loops, non-301s or non-200 targets.
	 *
	 * ## OPTIONS
	 *
	 * [--base=<url>]
	 * : Site to test (default: this site's home URL).
	 *
	 * [--file=<file>]
	 * : CSV (default data/redirects.csv).
	 *
	 * [--include-conditional]
	 * : Also check conditional rows.
	 *
	 * [--[no-]local-domains]
	 * : Expect production domains rewritten to this environment's URLs (default on locally).
	 *
	 * [--format=<format>]
	 * : table|csv|json.
	 * ---
	 * default: table
	 * ---
	 *
	 * @param string[]             $args  Positional.
	 * @param array<string,string> $assoc Options.
	 */
	public function verify( array $args, array $assoc ): void {
		$rules = $this->rules( $assoc );
		$base  = untrailingslashit( (string) ( $assoc['base'] ?? home_url() ) );
		$own   = array_map( static fn( $s ) => strtolower( (string) wp_parse_url( td_url( $s, '/' ), PHP_URL_HOST ) . ':' . (int) wp_parse_url( td_url( $s, '/' ), PHP_URL_PORT ) ), [ 'marketing', 'journal', 'studios' ] );
		$own   = array_merge( $own, array_map( static fn( $u ) => strtolower( (string) wp_parse_url( $u, PHP_URL_HOST ) ) . ':0', td_site_domains() ) );
		$rows  = [];
		$fail  = 0;
		$norm  = static fn( string $u ): string => strtolower( rtrim( (string) preg_replace( '/[?#].*$/', '', $u ), '/' ) );
		foreach ( $rules as $rule ) {
			$path = RedirectRules::REGEX === $rule['kind'] ? str_replace( '*', 'td-verify-sample', (string) $rule['source'] ) : (string) $rule['source'];
			if ( RedirectRules::REGEX === $rule['kind'] && ! str_ends_with( $path, '/' ) ) {
				$path .= '/';
			}
			$hit      = RedirectRules::match( [ $rule ], $path );
			$expected = $hit ? $hit['target'] : '';
			$expected = preg_match( '#^https?://#i', $expected ) ? $expected : $base . '/' . ltrim( $expected, '/' );
			$res      = td_cli_http_get( $base . $path );
			$row      = [
				'source' => $path,
				'status' => (string) $res['status'],
				'target' => $res['location'],
				'target_status' => '',
				'result' => 'ok',
			];
			$problems = [];
			if ( (int) $rule['code'] !== $res['status'] ) {
				$problems[] = 0 === $res['status'] ? 'request failed: ' . $res['error'] : 'expected ' . $rule['code'] . ', got ' . $res['status'];
			}
			if ( 410 !== (int) $rule['code'] && '' !== $res['location'] && $norm( $res['location'] ) !== $norm( $expected ) ) {
				$problems[] = 'wrong target (expected ' . $expected . ')';
			}
			if ( '' !== $res['location'] && ! $problems ) {
				$host = strtolower( (string) wp_parse_url( $res['location'], PHP_URL_HOST ) ) . ':' . (int) wp_parse_url( $res['location'], PHP_URL_PORT );
				if ( ! in_array( $host, $own, true ) && ! td_is_production() ) {
					$row['target_status'] = 'external: not checked';
				} elseif ( $norm( $res['location'] ) === $norm( $base . $path ) ) {
					$problems[] = 'loop';
				} else {
					$next                 = td_cli_http_get( $res['location'] );
					$row['target_status'] = (string) $next['status'];
					if ( $next['status'] >= 300 && $next['status'] < 400 ) {
						$problems[] = $norm( $next['location'] ) === $norm( $base . $path ) ? 'loop' : 'chain → ' . $next['location'];
					} elseif ( 200 !== $next['status'] && RedirectRules::REGEX !== $rule['kind'] ) {
						$problems[] = 'target returns ' . ( $next['status'] ? $next['status'] : $next['error'] );
					} elseif ( RedirectRules::REGEX === $rule['kind'] ) {
						$row['target_status'] .= ' (catch-all sample)';
					}
				}
			}
			if ( $problems ) {
				++$fail;
				$row['result'] = 'FAIL: ' . implode( '; ', $problems );
			}
			$rows[] = $row;
		}
		Utils\format_items( (string) ( $assoc['format'] ?? 'table' ), $rows, [ 'source', 'status', 'target', 'target_status', 'result' ] );
		update_option(
			'td_redirects_verify',
			[
				'time'   => time(),
				'ok'     => 0 === $fail,
				'total'  => count( $rows ),
				'failed' => $fail,
				'base'   => $base,
			],
			false
		);
		if ( $fail ) {
			WP_CLI::error( sprintf( '%d of %d redirect(s) failed (§9.2: one hop, 301, target 200).', $fail, count( $rows ) ) );
		}
		WP_CLI::success( sprintf( 'All %d redirect(s) pass: one hop, 301, target 200.', count( $rows ) ) );
	}

	/**
	 * Print the stored rules as CSV (the redirects.csv format).
	 *
	 * @param string[]             $args  Positional.
	 * @param array<string,string> $assoc Options.
	 */
	public function export( array $args, array $assoc ): void {
		$rules = td_redirect_rules();
		if ( ! $rules ) {
			WP_CLI::error( 'Nothing imported yet: run wp td redirects import.' );
		}
		WP_CLI::line( rtrim( RedirectRules::to_csv( $rules ) ) );
	}
}

/**
 * Site audits (§8.3, §9.4, §10.1, §16).
 */
final class TD_CLI_Audit {

	/**
	 * Published, indexable posts with no inbound internal link from other
	 * published content or menus (template-generated links are not counted).
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table|csv|json|count.
	 * ---
	 * default: table
	 * ---
	 *
	 * @param string[]             $args  Positional.
	 * @param array<string,string> $assoc Options.
	 */
	public function orphans( array $args, array $assoc ): void {
		$rows = td_audit_orphans();
		if ( ! $rows ) {
			WP_CLI::success( 'No orphan pages.' );
			return;
		}
		Utils\format_items( (string) ( $assoc['format'] ?? 'table' ), $rows, [ 'id', 'type', 'url', 'title' ] );
		WP_CLI::error( sprintf( '%d orphan page(s): link to each from at least one related page.', count( $rows ) ) );
	}

	/**
	 * Find [CONFIRM], [PRICE], [YOUR PRICE], [MINIMUM SPEND] and lorem ipsum in
	 * published content + meta, and optionally in rendered pages.
	 *
	 * ## OPTIONS
	 *
	 * [--url=<url>]
	 * : Also fetch these rendered URLs (comma-separated), or "all" for every published permalink.
	 *
	 * [--format=<format>]
	 * : table|csv|json|count.
	 * ---
	 * default: table
	 * ---
	 *
	 * @param string[]             $args  Positional.
	 * @param array<string,string> $assoc Options.
	 */
	public function placeholders( array $args, array $assoc ): void {
		$rows = td_audit_placeholders_db();
		if ( ! empty( $assoc['url'] ) ) {
			$urls = 'all' === $assoc['url'] ? array_map( 'get_permalink', td_audit_indexable_posts() ) : array_map( 'trim', explode( ',', (string) $assoc['url'] ) );
			$rows = array_merge( $rows, td_audit_placeholders_rendered( array_filter( array_map( 'strval', $urls ) ) ) );
		}
		if ( ! $rows ) {
			WP_CLI::success( 'No placeholders on published content.' );
			return;
		}
		Utils\format_items( (string) ( $assoc['format'] ?? 'table' ), $rows, [ 'where', 'url', 'match', 'count' ] );
		WP_CLI::error( sprintf( '%d place(s) with placeholders (§16): resolve before launch.', count( $rows ) ) );
	}

	/**
	 * Pages to refresh: GSC clicks down ≥ 30% (comparison export) and pages past
	 * their review cadence (money pages 3 months, guides 6).
	 *
	 * ## OPTIONS
	 *
	 * [--gsc-csv=<file>]
	 * : GSC Performance → Pages export with Date → Compare (two periods, e.g. last 3 months vs previous).
	 *
	 * [--threshold=<pct>]
	 * : Minimum click drop in percent.
	 * ---
	 * default: 30
	 * ---
	 *
	 * [--min-clicks=<n>]
	 * : Ignore pages with fewer clicks in the earlier period.
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--format=<format>]
	 * : table|csv|json.
	 * ---
	 * default: table
	 * ---
	 *
	 * @param string[]             $args  Positional.
	 * @param array<string,string> $assoc Options.
	 */
	public function decay( array $args, array $assoc ): void {
		$format  = (string) ( $assoc['format'] ?? 'table' );
		$decayed = [];
		if ( ! empty( $assoc['gsc-csv'] ) ) {
			$file = (string) $assoc['gsc-csv'];
			if ( ! is_readable( $file ) ) {
				WP_CLI::error( 'Cannot read ' . $file );
			}
			try {
				$decayed = Decay::decayed( Decay::parse_gsc( (string) file_get_contents( $file ) ), ( (float) ( $assoc['threshold'] ?? 30 ) ) / 100, (int) ( $assoc['min-clicks'] ?? 10 ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			} catch ( InvalidArgumentException $e ) {
				WP_CLI::error( $e->getMessage() );
			}
			WP_CLI::log( sprintf( 'GSC: %d page(s) lost ≥ %s%% of clicks.', count( $decayed ), $assoc['threshold'] ?? 30 ) );
			if ( $decayed ) {
				Utils\format_items(
					$format,
					array_map(
						static fn( $r ) => [
							'url'      => $r['url'],
							'previous' => $r['previous'],
							'current'  => $r['current'],
							'drop'     => round( $r['drop'] * 100 ) . '%',
						],
						$decayed
					),
					[ 'url', 'previous', 'current', 'drop' ]
				);
			}
		}
		$due = td_audit_review_due( time() );
		WP_CLI::log( sprintf( 'Review cadence: %d page(s) overdue.', count( $due ) ) );
		if ( $due ) {
			Utils\format_items( $format, $due, [ 'url', 'type', 'last_reviewed', 'cadence' ] );
		}
		update_option(
			'td_audit_decay',
			[
				'time'    => time(),
				'decayed' => $decayed,
				'due'     => $due,
			],
			false
		);
		WP_CLI::success( 'Saved to option td_audit_decay for the dashboard.' );
	}

	/**
	 * Crawl internal links from the home page and the sitemap; report non-200s
	 * and redirect chains; save a CSV in uploads/td-audit/.
	 *
	 * ## OPTIONS
	 *
	 * [--base=<url>]
	 * : Site to crawl (default: home URL).
	 *
	 * [--max=<n>]
	 * : Max pages.
	 * ---
	 * default: 500
	 * ---
	 *
	 * @param string[]             $args  Positional.
	 * @param array<string,string> $assoc Options.
	 */
	public function crawl( array $args, array $assoc ): void {
		$rows = td_audit_crawl( (string) ( $assoc['base'] ?? home_url() ), max( 1, (int) ( $assoc['max'] ?? 500 ) ) );
		$file = td_audit_save_csv( 'crawl', $rows );
		$bad  = array_values( array_filter( $rows, static fn( $r ) => 200 !== $r['status'] || $r['hops'] > 1 || '' !== $r['note'] ) );
		WP_CLI::log( sprintf( 'Crawled %d URL(s). CSV: %s', count( $rows ), $file ) );
		if ( $bad ) {
			Utils\format_items( 'table', $bad, [ 'url', 'status', 'hops', 'final', 'from', 'note' ] );
			WP_CLI::error( sprintf( '%d problem URL(s).', count( $bad ) ) );
		}
		WP_CLI::success( 'No non-200s or redirect chains.' );
	}
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
		$query['post__in']  = array_map( 'intval', explode( ',', (string) $assoc['id'] ) );
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
