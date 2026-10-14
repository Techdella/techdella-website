<?php
/**
 * CSS/JS loading (§8.4): one inlined CSS bundle (critical CSS = the whole
 * bundle while it stays small), one deferred vanilla JS bundle, two font
 * preloads, nothing render-blocking.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme asset URL with a cache-busting version.
 *
 * @param string $rel Path relative to the theme root.
 */
function td_asset_url( string $rel ): string {
	return get_theme_file_uri( $rel );
}

/**
 * Version string for a built file (from assets/build/manifest.json).
 *
 * @param string $file Built file name, e.g. main.js.
 */
function td_build_version( string $file ): string {
	static $manifest = null;
	if ( null === $manifest ) {
		$path     = get_theme_file_path( 'assets/build/manifest.json' );
		$manifest = is_readable( $path ) ? (array) json_decode( (string) file_get_contents( $path ), true ) : []; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
	return (string) ( $manifest[ $file ]['version'] ?? TD_THEME_VERSION );
}

/**
 * Point relative font/image URLs in built CSS at the theme (needed when CSS is inlined).
 *
 * @param string $css Built CSS.
 */
function td_css_fix_urls( string $css ): string {
	return str_replace( [ '../fonts/', '../img/' ], [ get_theme_file_uri( 'assets/fonts/' ), get_theme_file_uri( 'assets/img/' ) ], $css );
}

/**
 * Inline CSS at or under this size (bytes, minified). Above it, link the file.
 */
function td_inline_css_limit(): int {
	return (int) apply_filters( 'td_inline_css_limit', 120 * 1024 );
}

add_action(
	'wp_head',
	static function () {
		$fonts = get_theme_file_uri( 'assets/fonts/' );
		// Only the two fonts above the fold are preloaded (§2.2).
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $fonts . 'geist-500.woff2' ) );
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $fonts . 'instrument-serif-italic.woff2' ) );

		// Critical CSS = core.css (tokens, base, components, nav, footer), inlined. Block CSS is
		// inlined just before each block's first render (td_block_inline_css()), so a page only
		// carries what it uses and nothing render-blocks (§8.4). main.css is the fallback.
		$core     = get_theme_file_path( 'assets/build/core.css' );
		$css_path = is_readable( $core ) ? $core : get_theme_file_path( 'assets/build/main.css' );
		if ( ! is_readable( $css_path ) ) {
			echo "<!-- techdella: run `npm run build` -->\n";
			return;
		}
		if ( filesize( $css_path ) <= td_inline_css_limit() ) {
			echo '<style id="td-css">' . td_css_fix_urls( (string) file_get_contents( $css_path ) ) . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- built, trusted CSS.
		} else {
			printf(
				'<link rel="stylesheet" id="td-css" href="%s">' . "\n", // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- printed at wp_head priority 2, before any enqueued style.
				esc_url( get_theme_file_uri( 'assets/build/main.css' ) . '?v=' . td_build_version( 'main.css' ) )
			);
		}
	},
	2
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( is_readable( get_theme_file_path( 'assets/build/main.js' ) ) ) {
			wp_enqueue_script(
				'techdella',
				get_theme_file_uri( 'assets/build/main.js' ),
				[],
				td_build_version( 'main.js' ),
				[
					'strategy'  => 'defer',
					'in_footer' => false,
				]
			);
			wp_localize_script(
				'techdella',
				'TD',
				[
					'site' => td_site(),
					'env'  => td_environment(),
					'rest' => esc_url_raw( rest_url( 'techdella/v1/' ) ),
					'urls' => [
						'marketing' => td_url( 'marketing', '/' ),
						'journal'   => td_url( 'journal', '/' ),
						'studios'   => td_url( 'studios', '/' ),
					],
				]
			);
		}
	}
);

// Block editor canvas (iframed): the same CSS bundle, so blocks preview on-brand.
add_action(
	'enqueue_block_assets',
	static function () {
		if ( ! is_admin() ) {
			return;
		}
		$css = get_theme_file_path( 'assets/build/main.css' );
		if ( is_readable( $css ) ) {
			wp_enqueue_style( 'techdella-editor-blocks', get_theme_file_uri( 'assets/build/main.css' ), [], td_build_version( 'main.css' ) );
		}
	}
);
