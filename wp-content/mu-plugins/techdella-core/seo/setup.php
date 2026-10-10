<?php
/**
 * `wp td setup` hook (td_setup): Rank Math options this module relies on.
 * Idempotent. Also callable directly: td_seo_setup().
 */

defined( 'ABSPATH' ) || exit;

/**
 * Configure Rank Math for this site (D5, §8.2, §8.3, §8.5).
 *
 * @return array<string,mixed> What was set (for CLI output).
 */
function td_seo_setup(): array {
	// Use the free plugin without connecting an account; front-end SEO only runs after this.
	update_option( 'rank_math_registration_skip', '1' );
	update_option( 'rank_math_wizard_completed', '1' );

	// Modules: sitemap, redirections (D6), 404 monitor on; schema and llms.txt off (ours).
	$modules = get_option( 'rank_math_modules', [] );
	$modules = is_array( $modules ) ? $modules : [];
	$modules = array_values( array_unique( array_diff( array_merge( $modules, [ 'sitemap', 'redirections', '404-monitor' ] ), [ 'rich-snippet', 'llms' ] ) ) );
	update_option( 'rank_math_modules', $modules );

	$public_types = array_values( get_post_types( [ 'public' => true ] ) );
	$excluded     = td_seo_sitemap_excluded_types();

	// General: our breadcrumbs and attachment redirects.
	$general                             = (array) get_option( 'rank-math-options-general', [] );
	$general['breadcrumbs']              = 'off';
	$general['attachment_redirect_urls'] = 'off';
	$general['strip_category_base']      = 'off';
	update_option( 'rank-math-options-general', $general );

	// Titles: never auto-fill descriptions from the excerpt (§8.5); search noindex.
	$titles                          = (array) get_option( 'rank-math-options-titles', [] );
	$titles['noindex_search']        = 'on';
	$titles['author_custom_robots']  = 'off';
	$titles['disable_date_archives'] = 'on';
	$titles['website_name']          = [
		'marketing' => 'TechDella',
		'journal'   => 'TechDella Journal',
		'studios'   => 'TechDella Studios',
	][ td_site() ];
	$titles['title_separator']       = '|';
	foreach ( $public_types as $type ) {
		$titles[ "pt_{$type}_description" ] = '';
		if ( ! isset( $titles[ "pt_{$type}_title" ] ) ) {
			$titles[ "pt_{$type}_title" ] = '%title% %sep% %sitename%';
		}
	}
	update_option( 'rank-math-options-titles', $titles );

	// Sitemaps: one index; images on; excluded types off; authors only on Journal.
	$sitemap                         = (array) get_option( 'rank-math-options-sitemap', [] );
	$sitemap['include_images']       = 'on';
	$sitemap['authors_sitemap']      = td_is_site( 'journal' ) ? 'on' : 'off';
	$sitemap['tax_post_tag_sitemap'] = 'off';
	foreach ( $public_types as $type ) {
		$sitemap[ "pt_{$type}_sitemap" ] = in_array( $type, $excluded, true ) ? 'off' : 'on';
	}
	foreach ( [ 'division', 'post_format' ] as $tax ) {
		$sitemap[ "tax_{$tax}_sitemap" ] = 'off';
	}
	update_option( 'rank-math-options-sitemap', $sitemap );

	if ( false === get_option( 'td_ai_crawler_policy' ) ) {
		add_option( 'td_ai_crawler_policy', 'allow-all' ); // [CONFIRM owner].
	}
	if ( false === get_option( 'td_social_profiles' ) ) {
		add_option( 'td_social_profiles', [] ); // [URLS] from the owner (§17).
	}

	// Rank Math's sitemap routes only register on a request where the module
	// is already on: rebuild rewrite rules on the next request (after any flush
	// later in this one).
	delete_option( 'rewrite_rules' );
	add_action(
		'shutdown',
		static function () {
			delete_option( 'rewrite_rules' );
		}
	);
	if ( function_exists( 'td_seo_llms_flush' ) ) {
		td_seo_llms_flush();
	}
	delete_transient( 'td_seo_health_' . td_site() );

	return [
		'modules'      => $modules,
		'sitemap'      => $sitemap,
		'public_types' => $public_types,
	];
}

add_action(
	'td_setup',
	static function () {
		td_seo_setup();
	},
	20
);
