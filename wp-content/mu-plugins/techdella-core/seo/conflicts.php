<?php
/**
 * Plugins left over from the old sites that fight our SEO, analytics and ads rules (§5.2, §8.2,
 * §12). Until a person removes them, they are silenced here:
 *
 *  - Yoast SEO next to Rank Math (D5): a second <title>, meta set and JSON-LD graph. Yoast prints
 *    nothing on the front end.
 *  - Site Kit: its GA4 / Tag Manager tags load before consent and duplicate our consent-aware
 *    loader (§12.3), so they are blocked when we load that ID ourselves; its AdSense tag (Auto ads
 *    on every page) is always blocked (D8: manual units on tool pages only).
 *  - Hostinger Tools: its llms.txt file is written to the web root and shadows ours (llms.php);
 *    `wp td setup` turns that feature off and removes its file.
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether Rank Math is active (our chosen SEO plugin).
 */
function td_conflicts_rank_math(): bool {
	return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
}

// Yoast: no front-end output when Rank Math is the SEO plugin.
add_filter( 'wpseo_frontend_presenters', static fn( $p ) => td_conflicts_rank_math() ? [] : $p, PHP_INT_MAX );
add_filter( 'wpseo_json_ld_output', static fn( $out ) => td_conflicts_rank_math() ? false : $out, PHP_INT_MAX );
add_filter( 'wpseo_debug_markers', '__return_false' );

// Site Kit tags.
add_filter( 'googlesitekit_analytics-4_tag_blocked', static fn( $b ) => '' !== (string) GA4_ID ? true : $b );
add_filter( 'googlesitekit_analytics-4_tag_amp_blocked', static fn( $b ) => '' !== (string) GA4_ID ? true : $b );
add_filter( 'googlesitekit_tagmanager_tag_blocked', static fn( $b ) => '' !== (string) GTM_ID ? true : $b );
add_filter( 'googlesitekit_adsense_tag_blocked', '__return_true' );
add_filter( 'googlesitekit_adsense_tag_amp_blocked', '__return_true' );

/**
 * Turn off Hostinger Tools' llms.txt and remove the file it wrote, so /llms.txt is ours.
 *
 * @return string[] Log lines.
 */
function td_conflicts_hostinger_llms(): array {
	$log      = [];
	$settings = get_option( 'hostinger_tools' );
	if ( is_array( $settings ) && ! empty( $settings['enable_llms_txt'] ) ) {
		$settings['enable_llms_txt'] = false;
		update_option( 'hostinger_tools', $settings );
		$log[] = 'Turned off Hostinger Tools llms.txt';
	}
	$file = ABSPATH . 'llms.txt';
	if ( is_readable( $file ) && str_contains( (string) file_get_contents( $file ), 'Hostinger Tools' ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		wp_delete_file( $file );
		$log[] = 'Removed the Hostinger llms.txt file (ours serves /llms.txt)';
	}
	return $log;
}
add_action(
	'td_setup',
	static function () {
		foreach ( td_conflicts_hostinger_llms() as $line ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::log( $line );
			}
		}
	}
);
