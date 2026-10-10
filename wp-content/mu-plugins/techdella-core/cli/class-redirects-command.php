<?php
/**
 * `wp td redirects import|verify|export` (§9).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Cli\RedirectRules;
use WP_CLI\Utils;

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
		$local = (bool) Utils\get_flag_value( $assoc, 'local-domains', td_environment() === 'local' ); // phpcs:ignore WordPress.PHP.YodaConditions.NotYoda -- default value expression.

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
				'source'        => $path,
				'status'        => (string) $res['status'],
				'target'        => $res['location'],
				'target_status' => '',
				'result'        => 'ok',
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
	public function export( array $args, array $assoc ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WP-CLI signature.
		$rules = td_redirect_rules();
		if ( ! $rules ) {
			WP_CLI::error( 'Nothing imported yet: run wp td redirects import.' );
		}
		WP_CLI::line( rtrim( RedirectRules::to_csv( $rules ) ) );
	}
}
