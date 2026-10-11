<?php
/**
 * SEO health checks for the TechDella HQ dashboard (§14.2): blog_public,
 * robots.txt, sitemap index, llms.txt, one JSON-LD graph with a single
 * Organization. HTTP checks are cached for an hour.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fetch a URL on this site (loopback) for a health check.
 *
 * @param string $path Path.
 * @return array{code:int,body:string,type:string}
 */
function td_seo_health_fetch( string $path ): array {
	$response = wp_remote_get(
		home_url( $path ),
		[
			'timeout'     => 8,
			'redirection' => 2,
			'sslverify'   => td_is_production(),
			'headers'     => [ 'Cache-Control' => 'no-cache' ],
		]
	);
	if ( is_wp_error( $response ) ) {
		return [
			'code' => 0,
			'body' => '',
			'type' => '',
		];
	}
	return [
		'code' => (int) wp_remote_retrieve_response_code( $response ),
		'body' => (string) wp_remote_retrieve_body( $response ),
		'type' => (string) wp_remote_retrieve_header( $response, 'content-type' ),
	];
}

/**
 * Run the SEO checks.
 *
 * @param bool $fresh Skip the hourly cache.
 * @return array<int,array{ok:bool|null,label:string}>
 */
function td_seo_health( bool $fresh = false ): array {
	$key = 'td_seo_health_' . td_site();
	if ( ! $fresh ) {
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}
	$rows   = [];
	$public = '1' === (string) get_option( 'blog_public' );
	$env    = td_environment();

	if ( 'production' === $env ) {
		$rows[] = [
			'ok'    => $public,
			'label' => $public ? __( 'Visible to search engines', 'techdella' ) : __( 'Production is hidden from search engines (blog_public = 0)', 'techdella' ),
		];
	} else {
		$rows[] = [
			'ok'    => null,
			/* translators: %s: environment name. */
			'label' => sprintf( __( 'Environment: %s (noindex rules for production not checked)', 'techdella' ), $env ),
		];
	}

	$robots      = td_seo_health_fetch( '/robots.txt' );
	$has_sitemap = false !== stripos( $robots['body'], 'Sitemap:' );
	$rows[]      = [
		'ok'    => 200 === $robots['code'] && ( 'staging' === $env || $has_sitemap ),
		'label' => 200 === $robots['code'] ? __( 'robots.txt reachable', 'techdella' ) : __( 'robots.txt not reachable', 'techdella' ),
	];

	$sitemap_path = (string) wp_parse_url( td_seo_sitemap_url(), PHP_URL_PATH );
	$sitemap      = td_seo_health_fetch( $sitemap_path );
	$sitemap_ok   = 200 === $sitemap['code'] && false !== strpos( $sitemap['body'], '<sitemapindex' );
	$rows[]       = [
		'ok'    => $sitemap_ok,
		/* translators: %s: sitemap path. */
		'label' => sprintf( $sitemap_ok ? __( 'Sitemap index OK (%s)', 'techdella' ) : __( 'Sitemap index not reachable (%s)', 'techdella' ), $sitemap_path ),
	];

	$llms   = td_seo_health_fetch( '/llms.txt' );
	$rows[] = [
		'ok'    => 200 === $llms['code'] && 0 === stripos( $llms['type'], 'text/plain' ),
		'label' => 200 === $llms['code'] ? __( 'llms.txt reachable', 'techdella' ) : __( 'llms.txt not reachable', 'techdella' ),
	];

	$home    = td_seo_health_fetch( '/' );
	$scripts = preg_match_all( '#<script[^>]+application/ld\+json[^>]*>(.*?)</script>#s', $home['body'], $m );
	$orgs    = 0;
	foreach ( $m[1] as $json ) {
		$data = json_decode( $json, true );
		foreach ( (array) ( $data['@graph'] ?? [ $data ] ) as $node ) {
			if ( is_array( $node ) && in_array( 'Organization', (array) ( $node['@type'] ?? [] ), true ) ) {
				++$orgs;
			}
		}
	}
	$rows[] = [
		'ok'    => 1 === $scripts && 1 === $orgs,
		/* translators: 1: JSON-LD script count, 2: Organization node count. */
		'label' => sprintf( __( 'Structured data on home: %1$d JSON-LD graph(s), %2$d Organization node(s)', 'techdella' ), $scripts, $orgs ),
	];

	if ( 'staging' === $env ) {
		$locked = '' !== (string) STAGING_BASIC_AUTH_USER && '' !== (string) STAGING_BASIC_AUTH_PASS;
		$rows[] = [
			'ok'    => $locked,
			'label' => $locked ? __( 'Staging password protection on', 'techdella' ) : __( 'Staging basic auth not configured (STAGING_BASIC_AUTH_USER/PASS)', 'techdella' ),
		];
	}

	set_transient( $key, $rows, HOUR_IN_SECONDS );
	return $rows;
}
