<?php
/**
 * `wp td audit orphans|placeholders|decay|crawl` (§8.3, §9.4, §10.1, §16).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Cli\Decay;
use WP_CLI\Utils;

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
