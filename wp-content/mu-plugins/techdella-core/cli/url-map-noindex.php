<?php
/**
 * `wp td url-map:noindex <url-map.csv>`: marks every live item that data/url-map.csv lists as
 * `noindex` (e.g. thin business guides with no clicks, §10.9) with _td_noindex, so it gets meta
 * robots + X-Robots-Tag noindex and leaves the sitemap. Idempotent; never unpublishes or deletes.
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Apply the url-map noindex rows on this site.
 *
 * ## OPTIONS
 *
 * <file>
 * : Path to url-map.csv.
 *
 * [--dry-run]
 * : Report only.
 *
 * ## EXAMPLES
 *
 *     wp td url-map:noindex data/url-map.csv --dry-run
 *
 * @param string[]             $args  File path.
 * @param array<string,string> $assoc Options.
 */
function td_cli_url_map_noindex( array $args, array $assoc ): void {
	$file = (string) ( $args[0] ?? '' );
	if ( ! is_readable( $file ) ) {
		WP_CLI::error( 'Cannot read ' . $file );
	}
	$dry  = (bool) WP_CLI\Utils\get_flag_value( $assoc, 'dry-run', false );
	$fh   = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	$hdr  = fgetcsv( $fh, 0, ',', '"', '' );
	$rows = [];
	while ( false !== ( $line = fgetcsv( $fh, 0, ',', '"', '' ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		if ( ! is_array( $hdr ) || count( $line ) !== count( $hdr ) ) {
			continue;
		}
		$row = array_combine( $hdr, $line );
		if ( 'noindex' !== ( $row['action'] ?? '' ) || ! post_type_exists( (string) $row['type'] ) ) {
			continue;
		}
		$path = trim( (string) wp_parse_url( (string) $row['old_url'], PHP_URL_PATH ), '/' );
		$base = (string) ( get_post_type_object( (string) $row['type'] )->rewrite['slug'] ?? '' );
		$slug = '' !== $base && str_starts_with( $path, $base . '/' ) ? substr( $path, strlen( $base ) + 1 ) : $path;
		$post = '' !== $slug ? get_page_by_path( $slug, OBJECT, (string) $row['type'] ) : null;
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$was = '1' === (string) get_post_meta( $post->ID, '_td_noindex', true );
		if ( ! $dry && ! $was ) {
			update_post_meta( $post->ID, '_td_noindex', 1 );
		}
		$rows[] = [
			'id'     => $post->ID,
			'path'   => '/' . $path . '/',
			'result' => $was ? 'already noindex' : ( $dry ? 'would noindex' : 'noindex' ),
		];
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	WP_CLI\Utils\format_items( 'table', $rows, [ 'id', 'path', 'result' ] );
	WP_CLI::success( sprintf( '%d item(s) checked.', count( $rows ) ) );
}
