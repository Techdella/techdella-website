<?php
/**
 * LiteSpeed Cache keeps its page cache, but must never rewrite our CSS/JS/HTML (§5.1, §8.4):
 * the build already minifies, inlines critical CSS per block and defers scripts. Its "unused
 * CSS", critical CSS, combine and minify options strip the theme's styles. A site copied from
 * the old live site carries those options, so they are forced off on every request
 * (LiteSpeed's own `litespeed_conf_force` action; nothing is saved to its settings).
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function (): void {
		if ( ! has_action( 'litespeed_conf_force' ) ) {
			return;
		}
		$off = [
			'optm-css_min',
			'optm-css_comb',
			'optm-css_comb_ext_inl',
			'optm-ucss',
			'optm-ucss_inline',
			'optm-css_async',
			'optm-ccss_per_url',
			'optm-css_async_inline',
			'optm-js_min',
			'optm-js_comb',
			'optm-js_comb_ext_inl',
			'optm-js_defer',
			'optm-html_min',
			'guest',
			'guest_optm',
		];
		foreach ( $off as $key ) {
			do_action( 'litespeed_conf_force', $key, false );
		}
	},
	1
);
