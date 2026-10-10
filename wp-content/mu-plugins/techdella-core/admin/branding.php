<?php
/**
 * Branding (§14.2): TechDella login screen and the "TechDella" admin colour
 * scheme (ink/canvas, orange only for focus), default for new users.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'login_enqueue_scripts',
	static function () {
		$css = TD_CORE_DIR . '/admin/assets/login.css';
		wp_enqueue_style( 'td-login', TD_CORE_URL . '/admin/assets/login.css', [ 'login' ], (string) ( file_exists( $css ) ? filemtime( $css ) : TD_CORE_VERSION ) );
	}
);

add_filter( 'login_headerurl', static fn() => home_url( '/' ) );
add_filter( 'login_headertext', static fn() => 'TechDella' );

// The theme's official logo file (td_logo) above the form; text fallback otherwise.
add_action(
	'login_header',
	static function () {
		$logo = function_exists( 'td_logo' ) ? td_logo( 'default', 32 ) : '<span class="td-logo-text">TechDella</span>';
		echo '<div class="td-login-brand"><a href="' . esc_url( home_url( '/' ) ) . '">' . $logo . '</a></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- td_logo() escapes its markup.
	}
);

add_action(
	'admin_init',
	static function () {
		$css = TD_CORE_DIR . '/admin/assets/colors-techdella.css';
		wp_admin_css_color(
			'techdella',
			__( 'TechDella', 'techdella' ),
			add_query_arg( 'ver', (string) ( file_exists( $css ) ? filemtime( $css ) : TD_CORE_VERSION ), TD_CORE_URL . '/admin/assets/colors-techdella.css' ),
			[ '#0D0D0D', '#1A1715', '#F7F5F2', '#E8470A' ],
			[
				'base'    => '#C9C4BE',
				'focus'   => '#FFFFFF',
				'current' => '#FFFFFF',
			]
		);
	},
	1
);

// New users get the TechDella scheme; anyone who never picked one sees it too.
add_action( 'user_register', static fn( $user_id ) => update_user_meta( (int) $user_id, 'admin_color', 'techdella' ) );
add_filter( 'get_user_option_admin_color', static fn( $color ) => ( false === $color || '' === $color ) ? 'techdella' : $color );

// Admin styles for the dashboard, help page and the fallback field box.
add_action(
	'admin_enqueue_scripts',
	static function () {
		$css = TD_CORE_DIR . '/admin/assets/admin.css';
		wp_enqueue_style( 'td-admin', TD_CORE_URL . '/admin/assets/admin.css', [], (string) ( file_exists( $css ) ? filemtime( $css ) : TD_CORE_VERSION ) );
	}
);

add_filter( 'admin_footer_text', static fn() => esc_html__( 'TechDella HQ · Questions? See How to in the Dashboard menu.', 'techdella' ) );
