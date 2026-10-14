<?php
/**
 * Images (§6.1): <picture> with AVIF/WebP/fallback from the build pipeline
 * (scripts/images.mjs → assets/img/manifest.json), and uploads via core srcset.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manifest of theme images built from assets/images/.
 *
 * @return array<string,array{width:int,height:int,alpha:bool,fallback:string,widths:int[]}>
 */
function td_image_manifest(): array {
	static $manifest = null;
	if ( null === $manifest ) {
		$path     = get_theme_file_path( 'assets/img/manifest.json' );
		$manifest = is_readable( $path ) ? (array) json_decode( (string) file_get_contents( $path ), true ) : []; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
	return $manifest;
}

/**
 * Responsive <picture> for a theme image.
 *
 * @param string              $key  Manifest key, e.g. "team/joanna-okedara-kalu".
 * @param array<string,mixed> $args alt (required for meaningful images; "" = decorative),
 *                                  sizes, class, img_class, priority (hero: fetchpriority=high, no lazy),
 *                                  style (inline style on <img>, e.g. object-position).
 */
function td_picture( string $key, array $args = [] ): string {
	$manifest = td_image_manifest();
	if ( ! isset( $manifest[ $key ] ) ) {
		return '<!-- td_picture: missing ' . esc_html( $key ) . ' -->';
	}
	$img      = $manifest[ $key ];
	$args     = wp_parse_args(
		$args,
		[
			'alt'       => '',
			'sizes'     => '100vw',
			'class'     => '',
			'img_class' => '',
			'priority'  => false,
			'style'     => '',
			'width'     => 0,
		]
	);
	$base     = get_theme_file_uri( 'assets/img/' . $key );
	$widths   = $img['widths'];
	$largest  = (int) end( $widths );
	$srcset   = static function ( string $ext ) use ( $base, $widths ): string {
		return implode( ', ', array_map( static fn( $w ) => esc_url( "{$base}-{$w}.{$ext}" ) . " {$w}w", $widths ) );
	};
	$fallback = $img['fallback'];
	$default  = $widths[0];
	foreach ( $widths as $w ) {
		if ( $w >= 1200 ) {
			$default = $w;
			break;
		}
		$default = $w;
	}
	$width  = $args['width'] ? (int) $args['width'] : $largest;
	$height = (int) round( $img['height'] * ( $width / max( 1, $img['width'] ) ) );

	$loading = $args['priority'] ? ' fetchpriority="high" decoding="async"' : ' loading="lazy" decoding="async"';
	$html    = '<picture' . ( $args['class'] ? ' class="' . esc_attr( $args['class'] ) . '"' : '' ) . '>';
	$html   .= '<source type="image/avif" srcset="' . $srcset( 'avif' ) . '" sizes="' . esc_attr( $args['sizes'] ) . '">';
	$html   .= '<source type="image/webp" srcset="' . $srcset( 'webp' ) . '" sizes="' . esc_attr( $args['sizes'] ) . '">';
	$html   .= sprintf(
		'<img src="%s" srcset="%s" sizes="%s" width="%d" height="%d" alt="%s"%s%s%s>',
		esc_url( "{$base}-{$default}.{$fallback}" ),
		$srcset( $fallback ),
		esc_attr( $args['sizes'] ),
		$width,
		$height,
		esc_attr( (string) $args['alt'] ),
		$args['img_class'] ? ' class="' . esc_attr( $args['img_class'] ) . '"' : '',
		$args['style'] ? ' style="' . esc_attr( $args['style'] ) . '"' : '',
		$loading
	);
	return $html . '</picture>';
}

/**
 * Image from either an attachment ID (uploads) or a theme manifest key.
 *
 * @param int|string          $image Attachment ID or manifest key.
 * @param array<string,mixed> $args  See td_picture().
 */
function td_image( $image, array $args = [] ): string {
	if ( is_numeric( $image ) && (int) $image > 0 ) {
		$attrs = [
			'alt'      => $args['alt'] ?? (string) get_post_meta( (int) $image, '_wp_attachment_image_alt', true ),
			'sizes'    => $args['sizes'] ?? '100vw',
			'class'    => $args['img_class'] ?? '',
			'decoding' => 'async',
		];
		if ( ! empty( $args['priority'] ) ) {
			$attrs['fetchpriority'] = 'high';
			$attrs['loading']       = false;
		} else {
			$attrs['loading'] = 'lazy';
		}
		if ( ! empty( $args['style'] ) ) {
			$attrs['style'] = $args['style'];
		}
		return wp_get_attachment_image( (int) $image, 'full', false, $attrs );
	}
	return is_string( $image ) && '' !== $image ? td_picture( $image, $args ) : '';
}

/**
 * The TechDella logo (official file, never redrawn).
 *
 * @param string $variant "default" (gradient mark + ink wordmark) | "white" | "mark".
 * @param int    $height   Rendered height in px.
 * @param bool   $priority Above the fold (header): fetchpriority high, no lazy-load. Footers pass false.
 */
function td_logo( string $variant = 'default', int $height = 28, bool $priority = true ): string {
	$key = [
		'default' => 'brand/techdella-logo',
		'white'   => 'brand/techdella-logo-white',
		'mark'    => 'brand/techdella-mark',
	][ $variant ] ?? 'brand/techdella-logo';
	$m   = td_image_manifest()[ $key ] ?? null;
	if ( ! $m ) {
		return '<span class="td-logo-text">TechDella</span>';
	}
	$width = (int) round( $m['width'] * ( $height / max( 1, $m['height'] ) ) );
	return td_picture(
		$key,
		[
			'alt'       => 'mark' === $variant ? '' : 'TechDella',
			'sizes'     => $width . 'px',
			'width'     => $width,
			'img_class' => 'td-logo__img',
			'style'     => "width:{$width}px;height:{$height}px",
			'priority'  => $priority,
		]
	);
}
