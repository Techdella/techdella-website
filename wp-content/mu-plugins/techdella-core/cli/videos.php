<?php
/**
 * WP-CLI: wp td media:videos <dir> — import the motion renders (§6.3) into this site's Media Library.
 *
 * Reads <scene>-<format>.mp4 / .webm / .jpg (format: tall 9:16, square, wide 16:9, portrait 4:5) and <scene>.vtt from <dir> (normally assets/video after
 * `npm run motion:render`), uploads each through WordPress (never by copying into Uploads), and
 * records the attachment IDs in the `td_videos` option:
 *   [ '<scene>' => [ 'vtt' => id, '<format>' => [ 'mp4' => id, 'webm' => id, 'poster' => id ] ] ]
 * The video block resolves its `scene` attribute through that option, so page patterns can name a
 * video without knowing attachment IDs. Unchanged files are skipped; changed files replace the old
 * attachment (td_import_featured() compares hashes).
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Import motion renders into the Media Library.
 *
 * ## OPTIONS
 *
 * <dir>
 * : Directory with the renders, e.g. /var/www/repo/assets/video.
 *
 * [--only=<scenes>]
 * : Comma-separated scene slugs (prefix match), e.g. mo-03,mo-05.
 *
 * ## EXAMPLES
 *
 *     wp td media:videos /var/www/repo/assets/video
 *     wp td media:videos ~/td-video --only=mo-07
 *
 * @param string[]             $args  Directory.
 * @param array<string,string> $assoc Options.
 */
function td_cli_media_videos( array $args, array $assoc ): void {
	$dir = rtrim( (string) ( $args[0] ?? '' ), '/' );
	if ( '' === $dir || ! is_dir( $dir ) ) {
		WP_CLI::error( 'Pass the directory with the renders, e.g. /var/www/repo/assets/video.' );
	}
	$only  = array_filter( array_map( 'trim', explode( ',', (string) ( $assoc['only'] ?? '' ) ) ) );
	$files = glob( $dir . '/*-{tall,square,wide,portrait}.mp4', GLOB_BRACE );
	$files = is_array( $files ) ? $files : [];
	if ( ! $files ) {
		WP_CLI::error( 'No <scene>-<format>.mp4 files in ' . $dir . '. Run `npm run motion:render` first.' );
	}
	$base   = dirname( $dir );
	$folder = basename( $dir );
	$map    = (array) get_option( 'td_videos', [] );
	$count  = 0;
	foreach ( $files as $mp4 ) {
		if ( ! preg_match( '/^(.+)-(tall|square|wide|portrait)\.mp4$/', basename( $mp4 ), $m ) ) {
			continue;
		}
		[ , $scene, $format ] = $m;
		if ( $only && ! array_filter( $only, static fn( $o ) => str_starts_with( $scene, $o ) ) ) {
			continue;
		}
		$ids  = [];
		$exts = [
			'mp4'    => '.mp4',
			'webm'   => '.webm',
			'poster' => '.jpg',
		];
		foreach ( $exts as $key => $ext ) {
			$att = td_import_featured( $folder . '/' . $scene . '-' . $format . $ext, $base, 0 );
			if ( is_int( $att ) ) {
				$ids[ $key ] = $att;
			} elseif ( 'webm' !== $key ) { // WebM is optional for client creatives.
				WP_CLI::warning( $scene . '-' . $format . $ext . ': ' . $att );
			}
		}
		if ( empty( $ids['mp4'] ) ) {
			continue;
		}
		$map[ $scene ]            = (array) ( $map[ $scene ] ?? [] );
		$map[ $scene ][ $format ] = $ids;
		if ( is_file( $dir . '/' . $scene . '.vtt' ) ) {
			$vtt = td_import_featured( $folder . '/' . $scene . '.vtt', $base, 0 );
			if ( is_int( $vtt ) ) {
				$map[ $scene ]['vtt'] = $vtt;
			}
		}
		++$count;
		WP_CLI::log( sprintf( '%-34s %-6s mp4 #%d webm #%d poster #%d', $scene, $format, $ids['mp4'], $ids['webm'] ?? 0, $ids['poster'] ?? 0 ) );
	}
	update_option( 'td_videos', $map, false );
	WP_CLI::success( sprintf( '%d video(s) in the Media Library; td_videos lists %d scene(s).', $count, count( $map ) ) );
}

WP_CLI::add_command( 'td media:videos', 'td_cli_media_videos' );
