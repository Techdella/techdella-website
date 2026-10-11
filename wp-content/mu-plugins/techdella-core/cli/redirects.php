<?php
/**
 * Redirects (§9, D6): data/redirects.csv → Rank Math Redirections when that
 * module is active, else our fallback store (option td_redirects) served by
 * a small runtime redirector. One hop, 301, exact/prefix/regex — never a
 * substring match (the §9.2 bug in the old td_redirects()).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Cli\RedirectRules;

/**
 * Path of data/redirects.csv (repo checkout, or the read-only mount in Docker).
 */
function td_redirects_csv_path(): string {
	$candidates = [
		'/var/www/repo/data/redirects.csv',
		dirname( TD_CORE_DIR, 3 ) . '/data/redirects.csv',
	];
	foreach ( $candidates as $file ) {
		if ( is_readable( $file ) ) {
			return (string) apply_filters( 'td_redirects_csv', $file );
		}
	}
	return (string) apply_filters( 'td_redirects_csv', $candidates[0] );
}

/**
 * Whether Rank Math's Redirections module is running (then it owns redirects).
 */
function td_redirects_rank_math_active(): bool {
	return class_exists( '\RankMath\Helper' ) && class_exists( '\RankMath\Redirections\Redirection' )
		&& \RankMath\Helper::is_module_active( 'redirections' );
}

/**
 * Production base → this environment's base for each site, where they differ
 * (used to rewrite cross-site targets on local/staging).
 *
 * @return array<string,string>
 */
function td_redirects_domain_map(): array {
	$map = [];
	foreach ( td_site_domains() as $site => $prod ) {
		$here = untrailingslashit( td_url( $site, '/' ) );
		if ( untrailingslashit( $prod ) !== $here ) {
			$map[ $prod ] = $here;
		}
	}
	return $map;
}

/**
 * Rules in the fallback store.
 *
 * @return array<int,array<string,mixed>>
 */
function td_redirect_rules(): array {
	$store = get_option( 'td_redirects', [] );
	return is_array( $store ) && isset( $store['rules'] ) && is_array( $store['rules'] ) ? $store['rules'] : [];
}

/**
 * Resolve a rule target to an absolute URL.
 *
 * @param string $target Relative path or absolute URL.
 */
function td_redirects_absolute( string $target ): string {
	return preg_match( '#^https?://#i', $target ) ? $target : home_url( '/' . ltrim( $target, '/' ) );
}

/**
 * Send the redirect for a match (or a 410) and stop.
 *
 * @param array{rule: array<string,mixed>, target: string} $hit Match.
 */
function td_redirects_send( array $hit ): void {
	$code = (int) $hit['rule']['code'];
	if ( 410 === $code ) {
		global $wp_query;
		if ( $wp_query instanceof WP_Query ) {
			$wp_query->set_404();
		}
		status_header( 410 );
		return;
	}
	header( 'X-Redirect-By: TechDella' );
	wp_redirect( td_redirects_absolute( $hit['target'] ), $code, 'TechDella' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- targets come from the reviewed redirects.csv, incl. our other sites.
	exit;
}

/**
 * Current request path.
 */
function td_redirects_request_path(): string {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '/';
	return RedirectRules::normalize_path( $uri );
}

// Fallback redirector, only when Rank Math Redirections is not active.
// Exact rules early (parse_request) — they apply even when a page exists.
add_action(
	'parse_request',
	static function () {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || td_redirects_rank_math_active() ) {
			return;
		}
		$rules = array_values( array_filter( td_redirect_rules(), static fn( $r ) => RedirectRules::EXACT === ( $r['kind'] ?? '' ) && 410 !== (int) $r['code'] ) );
		if ( ! $rules ) {
			return;
		}
		$hit = RedirectRules::match( $rules, td_redirects_request_path() );
		if ( $hit ) {
			td_redirects_send( $hit );
		}
	},
	1
);

// Prefix/regex rules (and 410s) only for URLs that would otherwise 404.
add_action(
	'template_redirect',
	static function () {
		if ( ! is_404() || td_redirects_rank_math_active() ) {
			return;
		}
		$rules = td_redirect_rules();
		if ( ! $rules ) {
			return;
		}
		$hit = RedirectRules::match( $rules, td_redirects_request_path() );
		if ( $hit ) {
			td_redirects_send( $hit );
		}
	},
	0
);

/**
 * Load and parse the CSV.
 *
 * @param string $file CSV path.
 * @return array{rules: array<int,array<string,mixed>>, errors: string[]}
 */
function td_redirects_load_csv( string $file ): array {
	if ( ! is_readable( $file ) ) {
		return [
			'rules'  => [],
			/* translators: %s: file path. */
			'errors' => [ sprintf( __( 'Cannot read %s', 'techdella' ), $file ) ],
		];
	}
	return RedirectRules::parse_csv( (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

/**
 * Make sure Rank Math's redirection tables exist (they are created when the
 * module is switched on in its UI; `wp td setup` switches it on via options).
 */
function td_redirects_rank_math_tables(): void {
	global $wpdb;
	$table = $wpdb->prefix . 'rank_math_redirections';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table && class_exists( '\RankMath\Installer' ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		\RankMath\Installer::create_tables( [ 'redirections', '404-monitor' ] );
	}
}

/**
 * Import rules into Rank Math Redirections. Idempotent: rows we imported
 * before that are no longer in the CSV are deleted; existing rows are updated.
 *
 * Exact rows are stamped newest so Rank Math (which tries candidates newest
 * first) prefers exact over prefix over regex.
 *
 * @param array<int,array<string,mixed>> $rules Rules (targets already rewritten).
 * @param bool                           $dry   Dry run.
 * @return array<int,array<string,string>> Report rows.
 */
function td_redirects_import_rank_math( array $rules, bool $dry = false ): array {
	$report = [];
	$seen   = [];
	$ids    = [];
	if ( ! $dry ) {
		td_redirects_rank_math_tables();
	}
	if ( ! class_exists( '\RankMath\Redirections\Redirection' ) || ! class_exists( '\RankMath\Redirections\DB' ) ) {
		return [];
	}
	foreach ( $rules as $rule ) {
		$rows = RedirectRules::to_rank_math( $rule, (string) $rule['target'] );
		if ( is_string( $rows ) ) {
			$report[] = [
				'source' => (string) $rule['source'],
				'target' => (string) $rule['target'],
				'result' => 'skipped: ' . $rows,
			];
			continue;
		}
		foreach ( $rows as $row ) {
			$src  = $row['sources'][0];
			$dupe = $src['comparison'] . '|' . strtolower( $src['pattern'] );
			if ( isset( $seen[ $dupe ] ) ) {
				$report[] = [
					'source' => (string) $rule['source'],
					'target' => $row['url_to'],
					'result' => 'duplicate of line ' . $seen[ $dupe ],
				];
				continue;
			}
			$seen[ $dupe ] = (string) $rule['line'];
			$result        = $dry ? 'would import' : 'imported';
			if ( ! $dry ) {
				$redirection = \RankMath\Redirections\Redirection::from(
					[
						'sources'     => $row['sources'],
						'url_to'      => $row['url_to'],
						'header_code' => (string) $row['header_code'],
						'status'      => 'active',
						'updated'     => wp_date( 'Y-m-d H:i:s', time() - 60 * (int) $row['rank'] ),
					]
				);
				$id          = $redirection->save();
				if ( $id ) {
					$ids[] = (int) $id;
				} else {
					$result = 'failed';
				}
			}
			$report[] = [
				'source' => $src['comparison'] . ' ' . $src['pattern'],
				'target' => $row['url_to'],
				'result' => $result,
			];
		}
	}
	if ( ! $dry ) {
		$old   = array_map( 'intval', (array) get_option( 'td_redirects_rank_math_ids', [] ) );
		$stale = array_values( array_diff( $old, $ids ) );
		if ( $stale ) {
			\RankMath\Redirections\DB::delete( $stale );
		}
		update_option( 'td_redirects_rank_math_ids', array_values( array_unique( $ids ) ), false );
	}
	return $report;
}

/**
 * Number of active redirects in effect.
 */
function td_redirects_count(): int {
	if ( td_redirects_rank_math_active() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rank_math_redirections';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return 0;
		}
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'active'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
	return count( td_redirect_rules() );
}

/**
 * Redirect health for the admin dashboard (§14.2 site-health strip).
 *
 * @return array<int,array{ok:bool|null,label:string}>
 */
function td_redirects_health(): array {
	$count  = td_redirects_count();
	$engine = td_redirects_rank_math_active() ? 'Rank Math' : __( 'fallback store', 'techdella' );
	$rows   = [
		[
			'ok'    => $count > 0,
			/* translators: 1: number of redirects, 2: engine. */
			'label' => sprintf( __( 'Redirects: %1$d active (%2$s)', 'techdella' ), $count, $engine ),
		],
	];
	$verify = get_option( 'td_redirects_verify' );
	if ( is_array( $verify ) && isset( $verify['time'] ) ) {
		$rows[] = [
			'ok'    => ! empty( $verify['ok'] ),
			'label' => sprintf(
				/* translators: 1: date, 2: checked count, 3: failed count. */
				__( 'Redirect check %1$s: %2$d checked, %3$d failed', 'techdella' ),
				wp_date( get_option( 'date_format', 'Y-m-d' ), (int) $verify['time'] ),
				(int) ( $verify['total'] ?? 0 ),
				(int) ( $verify['failed'] ?? 0 )
			),
		];
	} else {
		$rows[] = [
			'ok'    => null,
			'label' => __( 'Redirect check: not run yet (wp td redirects verify)', 'techdella' ),
		];
	}
	return $rows;
}
