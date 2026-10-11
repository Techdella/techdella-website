<?php
/**
 * Article covers (IMG-12, "template-driven, title + one image"): 1600×900 JPEG per article with
 * the title and category on canvas, beside one of our photos masked into the TechDella arch.
 * Set as the featured image only where an article has none (or has an older generated cover), so
 * an editor's own image always wins. `wp td covers:generate`.
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

const TD_COVER_VERSION = 1;

/**
 * Photos available for covers (theme image derivatives), in a fixed order.
 *
 * @return string[] Absolute file paths.
 */
function td_cover_photos(): array {
	$out = [];
	foreach ( [ 'woman-working-laptop-desk', 'website-wireframe-sketches', 'designer-desk-swatches-wide', 'shop-owner-checking-phone', 'designer-drawing-tablet-studio', 'product-flatlay-notebook-candle-square' ] as $name ) {
		$file = get_theme_file_path( 'assets/img/stock/' . $name . '-1200.jpg' );
		if ( is_readable( $file ) ) {
			$out[] = $file;
		}
	}
	return $out;
}

/**
 * Draw a cover.
 *
 * @param string $title Title.
 * @param string $label Category label.
 * @param string $photo Photo path ('' for none).
 * @param string $file  Output JPEG path.
 */
function td_cover_render( string $title, string $label, string $photo, string $file ): bool {
	$w     = 1600;
	$h     = 900;
	$sans  = __DIR__ . '/fonts/Geist-SemiBold.ttf';
	$serif = __DIR__ . '/fonts/InstrumentSerif-Italic.ttf';
	$img   = imagecreatetruecolor( $w, $h );
	if ( ! $img ) {
		return false;
	}
	$canvas = (int) imagecolorallocate( $img, 0xF7, 0xF5, 0xF2 );
	$sand   = (int) imagecolorallocate( $img, 0xEF, 0xE7, 0xDD );
	$ink    = (int) imagecolorallocate( $img, 0x0D, 0x0D, 0x0D );
	$muted  = (int) imagecolorallocate( $img, 0x6B, 0x65, 0x60 );
	$orange = (int) imagecolorallocate( $img, 0xE8, 0x47, 0x0A );
	$paper  = (int) imagecolorallocate( $img, 0xFF, 0xFF, 0xFF );
	imagefilledrectangle( $img, 0, 0, $w, $h, $canvas );

	// The arch (right): photo masked to a round top and 32px bottom corners, on a sand plinth.
	$ax = 1010;
	$ay = 110;
	$aw = 500;
	$ah = 790;
	imagefilledrectangle( $img, $ax - 30, $h - 60, $w, $h, $sand );
	$src = '' !== $photo ? @imagecreatefromjpeg( $photo ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( $src ) {
		$sw    = imagesx( $src );
		$sh    = imagesy( $src );
		$scale = max( $aw / $sw, $ah / $sh );
		$cw    = (int) ceil( $aw / $scale );
		$ch    = (int) ceil( $ah / $scale );
		$tile  = imagecreatetruecolor( $aw, $ah );
		imagecopyresampled( $tile, $src, 0, 0, (int) ( ( $sw - $cw ) / 2 ), (int) ( ( $sh - $ch ) / 2 ), $aw, $ah, $cw, $ch );
		imagedestroy( $src );
		$r = $aw / 2;
		for ( $y = 0; $y < $ah; $y++ ) {
			for ( $x = 0; $x < $aw; $x++ ) {
				$dx = $x - $r;
				$dy = $y - $r;
				$in = $y >= $r || ( $dx * $dx + $dy * $dy ) <= $r * $r;
				if ( $in ) {
					imagesetpixel( $img, $ax + $x, $ay + $y, imagecolorat( $tile, $x, $y ) );
				}
			}
		}
		imagedestroy( $tile );
	} else {
		imagefilledellipse( $img, $ax + $aw / 2, $ay + $aw / 2, $aw, $aw, $sand );
		imagefilledrectangle( $img, $ax, $ay + (int) ( $aw / 2 ), $ax + $aw, $ay + $ah, $sand );
	}

	// Logo mark + site name.
	$mark = get_theme_file_path( 'assets/img/brand/techdella-mark-360.png' );
	if ( is_readable( $mark ) ) {
		$m = imagecreatefrompng( $mark );
		if ( $m ) {
			imagecopyresampled( $img, $m, 96, 88, 0, 0, 64, 64, imagesx( $m ), imagesy( $m ) );
			imagedestroy( $m );
		}
	}
	$site = [
		'marketing' => 'TechDella',
		'journal'   => 'TechDella Journal',
		'studios'   => 'TechDella Studios',
	][ td_site() ];
	imagettftext( $img, 26, 0, 178, 132, $ink, $sans, $site );

	// Label chip.
	$label = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $label ) : strtoupper( $label );
	$box   = imagettfbbox( 17, 0, $sans, $label );
	$lw    = false === $box ? 140 : $box[2] - $box[0];
	imagefilledrectangle( $img, 96 + 24, 228, 96 + 24 + $lw + 44, 276, $paper );
	imagefilledellipse( $img, 120, 252, 48, 48, $paper );
	imagefilledellipse( $img, 96 + 24 + $lw + 44, 252, 48, 48, $paper );
	imagefilledellipse( $img, 126, 252, 12, 12, $orange );
	imagettftext( $img, 17, 0, 146, 259, $ink, $sans, $label );

	// Title, up to 4 lines in the left 820px.
	$size  = 64.0;
	$lines = td_og_wrap( $title, $sans, $size, 820 );
	$count = count( $lines );
	while ( $count > 4 && $size > 44 ) {
		$size -= 4;
		$lines = td_og_wrap( $title, $sans, $size, 820 );
		$count = count( $lines );
	}
	if ( count( $lines ) > 5 ) {
		$lines    = array_slice( $lines, 0, 5 );
		$lines[4] = rtrim( $lines[4], ' .,;:' ) . '…';
	}
	$y = 360 + (int) $size;
	foreach ( $lines as $l ) {
		imagettftext( $img, $size, 0, 96, $y, $ink, $sans, $l );
		$y += (int) round( $size * 1.28 );
	}

	$domain = (string) wp_parse_url( td_site_domains()[ td_site() ], PHP_URL_HOST );
	imagettftext( $img, 22, 0, 96, 820, $muted, $sans, $domain );
	imagettftext( $img, 34, 0, 820, 822, $ink, $serif, 'Grow.' );

	$ok = imagejpeg( $img, $file, 84 );
	imagedestroy( $img );
	return (bool) $ok;
}

/**
 * Generate covers and set them as featured images where an article has none of its own.
 *
 * ## OPTIONS
 *
 * [--post_type=<types>]
 * : Comma-separated (default: post on the Journal; business and em-glossary on Marketing).
 *
 * [--force]
 * : Redraw existing generated covers.
 *
 * @param string[]             $args  Unused.
 * @param array<string,string> $assoc Options.
 */
function td_cli_covers_generate( array $args, array $assoc ): void {
	if ( ! function_exists( 'imagettftext' ) ) {
		WP_CLI::error( 'PHP GD with FreeType is required.' );
	}
	$types  = ! empty( $assoc['post_type'] ) ? array_map( 'trim', explode( ',', (string) $assoc['post_type'] ) ) : ( 'journal' === td_site() ? [ 'post' ] : [ 'business', 'em-glossary' ] );
	$types  = array_values( array_filter( $types, 'post_type_exists' ) );
	$force  = (bool) WP_CLI\Utils\get_flag_value( $assoc, 'force', false );
	$photos = td_cover_photos();
	$ids    = $types ? get_posts(
		[
			'post_type'      => $types,
			'post_status'    => [ 'publish', 'draft', 'pending' ],
			'posts_per_page' => -1,
			'fields'         => 'ids',
		]
	) : [];
	$up     = wp_upload_dir();
	$dir    = trailingslashit( $up['basedir'] ) . 'td-covers';
	wp_mkdir_p( $dir );
	$done = 0;
	foreach ( $ids as $id ) {
		$id    = (int) $id;
		$thumb = (int) get_post_thumbnail_id( $id );
		$ours  = (int) get_post_meta( $id, '_td_cover', true );
		if ( $thumb && $thumb !== $ours ) {
			continue; // An editor's own image wins.
		}
		$post  = get_post( $id );
		$label = td_og_label( $post );
		$hash  = md5( TD_COVER_VERSION . '|' . $post->post_title . '|' . $label );
		if ( $ours && ! $force && get_post_meta( $ours, '_td_cover_hash', true ) === $hash ) {
			continue;
		}
		$photo = $photos ? $photos[ abs( crc32( (string) $post->post_name ) ) % count( $photos ) ] : '';
		$file  = $dir . '/' . $post->post_name . '-' . substr( $hash, 0, 8 ) . '.jpg';
		if ( ! td_cover_render( wp_strip_all_tags( $post->post_title ), $label, $photo, $file ) ) {
			WP_CLI::warning( '#' . $id . ': could not draw the cover.' );
			continue;
		}
		$att = wp_insert_attachment(
			[
				'post_mime_type' => 'image/jpeg',
				'post_title'     => $post->post_title . ' cover',
				'post_status'    => 'inherit',
			],
			$file,
			$id
		);
		if ( is_wp_error( $att ) || ! $att ) {
			continue;
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $file ) );
		update_post_meta( $att, '_wp_attachment_image_alt', '' );
		update_post_meta( $att, '_td_cover_hash', $hash );
		set_post_thumbnail( $id, $att );
		update_post_meta( $id, '_td_cover', $att );
		if ( $ours ) {
			wp_delete_attachment( $ours, true );
		}
		++$done;
	}
	WP_CLI::success( sprintf( '%d cover(s) drawn.', $done ) );
}

WP_CLI::add_command( 'td covers:generate', 'td_cli_covers_generate' );
