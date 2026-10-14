<?php
/**
 * Favicons and the web app manifest from the theme's brand mark (owner, 12 Oct 2026), the same on
 * all three sites. Replaces WordPress's Site Icon output (an old logo on the existing installs),
 * serves /favicon.ico and /site.webmanifest without rewrite rules.
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL of an icon file in the theme.
 *
 * @param string $file File name in assets/img/icons/.
 */
function td_icon_url( string $file ): string {
	return get_theme_file_uri( 'assets/img/icons/' . $file );
}

// No WordPress Site Icon tags: ours below are the only ones.
add_action(
	'init',
	static function () {
		remove_action( 'wp_head', 'wp_site_icon', 99 );
		remove_action( 'login_head', 'wp_site_icon', 99 );
	}
);
add_filter( 'site_icon_meta_tags', '__return_empty_array', 99 );

/**
 * The icon <link> tags.
 */
function td_icon_tags(): string {
	return '<link rel="icon" href="' . esc_url( home_url( '/favicon.ico' ) ) . '" sizes="48x48">' . "\n"
		. '<link rel="icon" href="' . esc_url( td_icon_url( 'icon-32.png' ) ) . '" type="image/png" sizes="32x32">' . "\n"
		. '<link rel="icon" href="' . esc_url( td_icon_url( 'icon-192.png' ) ) . '" type="image/png" sizes="192x192">' . "\n"
		. '<link rel="apple-touch-icon" href="' . esc_url( td_icon_url( 'apple-touch-icon.png' ) ) . '">' . "\n"
		. '<link rel="manifest" href="' . esc_url( home_url( '/site.webmanifest' ) ) . '">' . "\n"
		. '<meta name="theme-color" content="#FFFFFF">' . "\n";
}
/**
 * Print the icon tags.
 */
function td_icon_print(): void {
	echo td_icon_tags(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_icon_tags().
}
add_action( 'wp_head', 'td_icon_print', 2 );
add_action( 'login_head', 'td_icon_print' );
add_action( 'admin_head', 'td_icon_print' );

/**
 * The site's name for the manifest.
 */
function td_icon_app_name(): string {
	return [
		'journal' => 'TechDella Journal',
		'studios' => 'TechDella Studios',
	][ td_site() ] ?? 'TechDella';
}

// /favicon.ico (browsers ask for it at the root) and /site.webmanifest.
add_action(
	'parse_request',
	static function ( WP $wp ) {
		$req = trim( (string) $wp->request, '/' );
		if ( 'favicon.ico' === $req ) {
			$file = get_theme_file_path( 'assets/img/icons/favicon.ico' );
			if ( is_readable( $file ) ) {
				status_header( 200 );
				header( 'Content-Type: image/x-icon' );
				header( 'Cache-Control: public, max-age=604800' );
				readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
				exit;
			}
		}
		if ( 'site.webmanifest' === $req ) {
			status_header( 200 );
			header( 'Content-Type: application/manifest+json; charset=utf-8' );
			header( 'Cache-Control: public, max-age=86400' );
			header( 'X-Robots-Tag: noindex' );
			echo wp_json_encode(
				[
					'name'             => td_icon_app_name(),
					'short_name'       => td_icon_app_name(),
					'start_url'        => '/',
					'display'          => 'browser',
					'background_color' => '#FFFFFF',
					'theme_color'      => '#FFFFFF',
					'icons'            => [
						[
							'src'   => td_icon_url( 'icon-192.png' ),
							'sizes' => '192x192',
							'type'  => 'image/png',
						],
						[
							'src'   => td_icon_url( 'icon-512.png' ),
							'sizes' => '512x512',
							'type'  => 'image/png',
						],
						[
							'src'     => td_icon_url( 'icon-maskable-512.png' ),
							'sizes'   => '512x512',
							'type'    => 'image/png',
							'purpose' => 'maskable',
						],
					],
				],
				JSON_UNESCAPED_SLASHES
			);
			exit;
		}
	},
	0
);
