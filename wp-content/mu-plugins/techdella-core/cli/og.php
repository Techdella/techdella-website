<?php
/**
 * OG/Twitter images (§8.5, IMG-13): 1200×630 PNG per post from its title and
 * category on the brand template, drawn with GD. Saved to uploads/td-og/,
 * attached to the post, ID in post meta _td_og_image (read by seo/social.php).
 *
 * Fonts: TTF copies of the theme's Geist SemiBold and Instrument Serif Italic
 * (cli/fonts/, SIL OFL — licences alongside).
 */

defined( 'ABSPATH' ) || exit;

const TD_OG_TEMPLATE_VERSION = 1;

/**
 * Post types that get generated OG images.
 *
 * @return string[]
 */
function td_og_post_types(): array {
	$types = array_diff( get_post_types( [ 'public' => true ] ), [ 'attachment', 'thank-you', 'document' ] );
	return array_values( (array) apply_filters( 'td_og_post_types', $types ) );
}

/**
 * The label shown above the title: primary category/term, else the type label.
 *
 * @param WP_Post $post Post.
 */
function td_og_label( WP_Post $post ): string {
	foreach ( [ 'category', 'td_industry', 'glossary_category', 'td_service_tag' ] as $taxonomy ) {
		if ( taxonomy_exists( $taxonomy ) && is_object_in_taxonomy( $post->post_type, $taxonomy ) ) {
			$terms = get_the_terms( $post, $taxonomy );
			if ( is_array( $terms ) && $terms && 'uncategorized' !== $terms[0]->slug ) {
				return $terms[0]->name;
			}
		}
	}
	$obj = get_post_type_object( $post->post_type );
	if ( 'page' === $post->post_type || ! $obj ) {
		return [
			'marketing' => 'TechDella',
			'journal'   => 'Journal',
			'studios'   => 'Studios',
		][ td_site() ];
	}
	return (string) $obj->labels->singular_name;
}

/**
 * Hash of what the image depends on.
 *
 * @param WP_Post $post Post.
 */
function td_og_hash( WP_Post $post ): string {
	return md5( TD_OG_TEMPLATE_VERSION . '|' . td_site() . '|' . $post->ID . '|' . $post->post_title . '|' . td_og_label( $post ) );
}

/**
 * Wrap text to a width with a TTF font.
 *
 * @param string $text  Text.
 * @param string $font  Font file.
 * @param float  $size  Size (pt).
 * @param int    $width Max width (px).
 * @return string[]
 */
function td_og_wrap( string $text, string $font, float $size, int $width ): array {
	$words = preg_split( '/\s+/u', trim( $text ) );
	$lines = [];
	$line  = '';
	foreach ( (array) $words as $word ) {
		$try = '' === $line ? (string) $word : $line . ' ' . $word;
		$box = imagettfbbox( $size, 0, $font, $try );
		if ( false !== $box && ( $box[2] - $box[0] ) > $width && '' !== $line ) {
			$lines[] = $line;
			$line    = (string) $word;
		} else {
			$line = $try;
		}
	}
	if ( '' !== $line ) {
		$lines[] = $line;
	}
	return $lines;
}

/**
 * Render the PNG to a file.
 *
 * @param string $title Title.
 * @param string $label Category label.
 * @param string $file  Output path.
 */
function td_og_render( string $title, string $label, string $file ): bool {
	if ( ! function_exists( 'imagettftext' ) ) {
		return false;
	}
	$w     = 1200;
	$h     = 630;
	$sans  = __DIR__ . '/fonts/Geist-SemiBold.ttf';
	$serif = __DIR__ . '/fonts/InstrumentSerif-Italic.ttf';
	$img   = imagecreatetruecolor( $w, $h );
	if ( ! $img ) {
		return false;
	}
	imagealphablending( $img, true );
	imagesavealpha( $img, true );
	$canvas = (int) imagecolorallocate( $img, 0xF7, 0xF5, 0xF2 );
	$sand   = (int) imagecolorallocate( $img, 0xEF, 0xE7, 0xDD );
	$ink    = (int) imagecolorallocate( $img, 0x0D, 0x0D, 0x0D );
	$muted  = (int) imagecolorallocate( $img, 0x6B, 0x65, 0x60 );
	$line   = (int) imagecolorallocate( $img, 0xE7, 0xE4, 0xDF );
	$orange = (int) imagecolorallocate( $img, 0xE8, 0x47, 0x0A );
	$paper  = (int) imagecolorallocate( $img, 0xFF, 0xFF, 0xFF );
	imagefilledrectangle( $img, 0, 0, $w, $h, $canvas );

	// Arch motif, bottom right (sand), the brand's one decorative shape.
	imagefilledellipse( $img, 1040, 470, 340, 340, $sand );
	imagefilledrectangle( $img, 870, 470, 1210, 640, $sand );

	// Logo mark + site name.
	$mark = get_theme_file_path( 'assets/img/brand/techdella-mark-360.png' );
	if ( is_readable( $mark ) ) {
		$src = imagecreatefrompng( $mark );
		if ( $src ) {
			imagecopyresampled( $img, $src, 72, 64, 0, 0, 56, 56, imagesx( $src ), imagesy( $src ) );
			imagedestroy( $src );
		}
	}
	$site = [
		'marketing' => 'TechDella',
		'journal'   => 'TechDella Journal',
		'studios'   => 'TechDella Studios',
	][ td_site() ];
	imagettftext( $img, 22, 0, 144, 102, $ink, $sans, $site );

	// Label chip: paper pill with an orange dot.
	$label = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $label ) : strtoupper( $label );
	$box   = imagettfbbox( 14, 0, $sans, $label );
	$lw    = false === $box ? 120 : $box[2] - $box[0];
	imagefilledrectangle( $img, 72 + 20, 176, 72 + 20 + $lw + 36, 216, $paper );
	imagefilledellipse( $img, 92, 196, 40, 40, $paper );
	imagefilledellipse( $img, 72 + 20 + $lw + 36, 196, 40, 40, $paper );
	imagefilledellipse( $img, 98, 196, 10, 10, $orange );
	imagettftext( $img, 14, 0, 116, 202, $ink, $sans, $label );

	// Title: Geist SemiBold, up to 4 lines, shrinking for long titles.
	$size  = 54.0;
	$lines = td_og_wrap( $title, $sans, $size, 960 );
	while ( count( $lines ) > 3 && $size > 38 ) {
		$size -= 4;
		$lines = td_og_wrap( $title, $sans, $size, 960 );
	}
	if ( count( $lines ) > 4 ) {
		$lines    = array_slice( $lines, 0, 4 );
		$lines[3] = rtrim( $lines[3], ' .,;:' ) . '…';
	}
	$y = 290 + (int) $size;
	foreach ( $lines as $l ) {
		imagettftext( $img, $size, 0, 72, $y, $ink, $sans, $l );
		$y += (int) round( $size * 1.32 );
	}

	// Footer: domain + serif accent.
	imagefilledrectangle( $img, 72, 548, 760, 549, $line );
	$domain = (string) wp_parse_url( td_site_domains()[ td_site() ], PHP_URL_HOST );
	imagettftext( $img, 18, 0, 72, 590, $muted, $sans, $domain );
	imagettftext( $img, 26, 0, 960, 590, $ink, $serif, 'Grow.' );

	$ok = imagepng( $img, $file, 6 );
	imagedestroy( $img );
	return (bool) $ok;
}

/**
 * Generate (or reuse) the OG image for a post.
 *
 * @param int  $post_id Post ID.
 * @param bool $force   Regenerate even when unchanged.
 * @return int|WP_Error Attachment ID.
 */
function td_og_generate( int $post_id, bool $force = false ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || '' === trim( $post->post_title ) ) {
		return new WP_Error( 'td_og', 'No post or no title.' );
	}
	$hash    = td_og_hash( $post );
	$current = (int) get_post_meta( $post_id, '_td_og_image', true );
	if ( ! $force && $current && get_post_meta( $current, '_td_og_hash', true ) === $hash && is_readable( (string) get_attached_file( $current ) ) ) {
		return $current;
	}
	$uploads = wp_upload_dir();
	$dir     = trailingslashit( $uploads['basedir'] ) . 'td-og';
	wp_mkdir_p( $dir );
	$file = $dir . '/' . $hash . '.png';
	if ( ! td_og_render( $post->post_title, td_og_label( $post ), $file ) ) {
		return new WP_Error( 'td_og', 'GD with FreeType is required.' );
	}
	$att = wp_insert_attachment(
		[
			'post_mime_type' => 'image/png',
			'post_title'     => 'OG: ' . $post->post_title,
			'post_status'    => 'inherit',
			'post_parent'    => $post_id,
		],
		$file,
		$post_id,
		true
	);
	if ( is_wp_error( $att ) ) {
		return $att;
	}
	update_post_meta( (int) $att, '_wp_attachment_metadata', [
		'width'  => 1200,
		'height' => 630,
		'file'   => _wp_relative_upload_path( $file ),
		'sizes'  => [],
	] );
	update_post_meta( (int) $att, '_wp_attachment_image_alt', $post->post_title );
	update_post_meta( (int) $att, '_td_og_hash', $hash );
	update_post_meta( (int) $att, '_td_noindex', 1 );
	update_post_meta( $post_id, '_td_og_image', (int) $att );
	if ( $current && $current !== (int) $att && get_post_meta( $current, '_td_og_hash', true ) ) {
		wp_delete_attachment( $current, true );
	}
	return (int) $att;
}

// Regenerate after publish / title change, out of the request (single cron event).
add_action(
	'save_post',
	static function ( int $post_id, WP_Post $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'publish' !== $post->post_status || ! in_array( $post->post_type, td_og_post_types(), true ) ) {
			return;
		}
		$current = (int) get_post_meta( $post_id, '_td_og_image', true );
		if ( $current && get_post_meta( $current, '_td_og_hash', true ) === td_og_hash( $post ) ) {
			return;
		}
		if ( ! wp_next_scheduled( 'td_og_generate_event', [ $post_id ] ) ) {
			wp_schedule_single_event( time() + 10, 'td_og_generate_event', [ $post_id ] );
		}
	},
	20,
	2
);

add_action(
	'td_og_generate_event',
	static function ( $post_id ) {
		td_og_generate( (int) $post_id );
	}
);

// Remove a post's generated OG image with the post.
add_action(
	'before_delete_post',
	static function ( $post_id ) {
		$att = (int) get_post_meta( (int) $post_id, '_td_og_image', true );
		if ( $att && get_post_meta( $att, '_td_og_hash', true ) ) {
			wp_delete_attachment( $att, true );
		}
	}
);
