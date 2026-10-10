<?php
/**
 * Theme supports, menus and front-end clean-up (§8.4: no jQuery, no emoji, no embeds).
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		load_theme_textdomain( 'techdella', get_template_directory() . '/languages' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
		remove_theme_support( 'core-block-patterns' );
		add_editor_style( 'assets/build/editor.css' );

		register_nav_menus(
			[
				'primary'       => __( 'Main navigation', 'techdella' ),
				'services'      => __( 'Services mega-menu', 'techdella' ),
				'footer-1'      => __( 'Footer column 1', 'techdella' ),
				'footer-2'      => __( 'Footer column 2', 'techdella' ),
				'footer-social' => __( 'Footer social links', 'techdella' ),
				'footer-legal'  => __( 'Footer legal links', 'techdella' ),
			]
		);

		// Card + hero crops. Front-end markup uses td_picture()/srcset either way.
		add_image_size( 'td-card', 768, 432, true );
		add_image_size( 'td-hero', 2400, 1400, true );
		add_image_size( 'td-portrait', 768, 960, true );
	}
);

// Front-end diet (§8.4).
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( is_admin() ) {
			return;
		}
		wp_deregister_script( 'jquery' );
		wp_deregister_script( 'wp-embed' );
		wp_dequeue_style( 'classic-theme-styles' );
		wp_dequeue_style( 'wp-block-library-theme' );
	},
	100
);
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
add_filter( 'emoji_svg_url', '__return_false' );

// Comments are off sitewide (§14.2).
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'comments_array', '__return_empty_array', 10 );

/**
 * Body classes: site + environment, so CSS/tests can tell the sites apart.
 */
add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'td-site-' . td_site();
		$classes[] = 'td-env-' . td_environment();
		return $classes;
	}
);
