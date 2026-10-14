<?php
/**
 * Social images (§8.5): og:image/twitter:image come from post meta
 * _td_og_image (attachment ID or URL, made by `wp td og:generate`), else the
 * featured image, else the brand fallback. Rank Math prints the tags; we feed
 * it the image.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The brand fallback image (1200px logo, from the theme image manifest).
 */
function td_og_fallback_image(): string {
	$file = 'assets/img/brand/techdella-logo-1200.png';
	if ( function_exists( 'td_image_manifest' ) ) {
		$entry = td_image_manifest()['brand/techdella-logo'] ?? null;
		if ( is_array( $entry ) && ! empty( $entry['widths'] ) ) {
			$widths = array_map( 'intval', (array) $entry['widths'] );
			$width  = in_array( 1200, $widths, true ) ? 1200 : max( $widths );
			$file   = 'assets/img/brand/techdella-logo-' . $width . '.' . ( $entry['fallback'] ?? 'png' );
		}
	}
	return (string) apply_filters( 'td_og_fallback_image', get_theme_file_uri( $file ) );
}

/**
 * The generated OG image of a post (_td_og_image), as a URL, or ''.
 *
 * @param int $post_id Post ID.
 */
function td_og_generated_image( int $post_id ): string {
	$value = get_post_meta( $post_id, '_td_og_image', true );
	if ( is_numeric( $value ) && (int) $value > 0 ) {
		$url = wp_get_attachment_image_url( (int) $value, 'full' );
		return $url ? $url : '';
	}
	$value = is_string( $value ) ? trim( $value ) : '';
	return '' !== $value && wp_http_validate_url( $value ) ? $value : '';
}

/**
 * The social image for a post (or the current page): _td_og_image →
 * featured image → brand fallback.
 *
 * @param int|null $post_id Post ID (default: queried singular post).
 */
function td_og_image_url( ?int $post_id = null ): string {
	if ( null === $post_id ) {
		$post_id = is_singular() ? (int) get_queried_object_id() : 0;
	}
	if ( $post_id ) {
		$generated = td_og_generated_image( $post_id );
		if ( '' !== $generated ) {
			return $generated;
		}
		$thumb = get_post_thumbnail_id( $post_id );
		if ( $thumb ) {
			$url = wp_get_attachment_image_url( (int) $thumb, 'full' );
			if ( $url ) {
				return $url;
			}
		}
	}
	return td_og_fallback_image();
}

foreach ( [ 'facebook', 'twitter' ] as $td_seo_network ) {
	// Generated image first (unless an editor set a Rank Math social image).
	add_action(
		"rank_math/opengraph/{$td_seo_network}/add_images",
		static function ( $image ) use ( $td_seo_network ) {
			if ( ! is_object( $image ) || ! method_exists( $image, 'add_image' ) || ! is_singular() ) {
				return;
			}
			$post_id = (int) get_queried_object_id();
			if ( get_post_meta( $post_id, "rank_math_{$td_seo_network}_image_id", true ) || get_post_meta( $post_id, 'rank_math_facebook_image_id', true ) ) {
				return;
			}
			$url = td_og_generated_image( $post_id );
			if ( '' !== $url ) {
				$image->add_image( $url );
			}
		}
	);
	// No image at all → brand fallback (Rank Math calls add_image() with an empty URL).
	add_filter(
		"rank_math/opengraph/{$td_seo_network}/image",
		static function ( $url ) {
			return ( is_string( $url ) && '' !== trim( $url ) ) ? $url : td_og_fallback_image();
		}
	);
}
unset( $td_seo_network );

// With a generated image, skip the featured/content images.
add_filter(
	'rank_math/opengraph/pre_set_featured_image',
	static function ( $short, $post_id = 0 ) {
		return ( $post_id && '' !== td_og_generated_image( (int) $post_id ) ) ? true : $short;
	},
	10,
	2
);
// Never pick a random image out of the content: fall back to the brand image.
add_filter( 'rank_math/opengraph/pre_set_content_image', '__return_true' );
