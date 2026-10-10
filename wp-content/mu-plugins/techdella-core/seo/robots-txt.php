<?php
/**
 * The robots.txt file (§8.3): exactly the listed rules, the sitemap index, and the
 * AI-crawler policy (option td_ai_crawler_policy = allow-all | block-training,
 * [CONFIRM owner]). Staging: Disallow everything (§5.3).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Seo\RobotsTxt;

/**
 * The sitemap index URL for this site (Rank Math's, or core's when Rank Math
 * sitemaps are off).
 */
function td_seo_sitemap_url(): string {
	$use_core = ! td_seo_rank_math_module( 'sitemap' ) && function_exists( 'wp_sitemaps_get_server' ) && (bool) apply_filters( 'wp_sitemaps_enabled', (bool) get_option( 'blog_public' ) );
	return home_url( $use_core ? '/wp-sitemap.xml' : '/sitemap_index.xml' );
}

/**
 * The AI-crawler policy (allow-all by default).
 */
function td_seo_ai_policy(): string {
	$policy = (string) get_option( 'td_ai_crawler_policy', 'allow-all' );
	return in_array( $policy, RobotsTxt::POLICIES, true ) ? $policy : 'allow-all';
}

/**
 * The robots.txt body for this site.
 */
function td_robots_txt(): string {
	$url = td_seo_sitemap_url();
	if ( td_is_production() ) {
		$url = set_url_scheme( $url, 'https' );
	}
	return RobotsTxt::build( $url, td_seo_ai_policy(), 'staging' === td_environment() );
}

add_filter( 'robots_txt', static fn() => td_robots_txt(), PHP_INT_MAX );
