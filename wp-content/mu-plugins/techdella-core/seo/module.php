<?php
/**
 * SEO module (§8): structured data, canonicals, noindex, staging lock,
 * robots.txt, llms.txt, sitemaps, breadcrumbs, social images, health.
 *
 * Rank Math (free, D5) keeps the titles/meta UI, sitemaps, redirections and
 * the 404 monitor. This module owns the JSON-LD and the indexing rules and
 * makes Rank Math's output agree with them.
 *
 * Public API:
 *  - td_schema_add_node( array $node )      add a node to the page's single @graph
 *  - td_schema_add_video( array $video )     VideoObject for a self-hosted video
 *  - apply_filters( 'td_schema_graph', $nodes ) final list of nodes
 *  - apply_filters( 'td_noindex', bool )     force noindex (meta + X-Robots-Tag); post meta _td_noindex = 1 per post
 *  - td_is_noindex()                         the decision for the current request
 *  - td_canonical_url()                      the canonical URL for the current request
 *  - td_breadcrumbs( $args ) / td_breadcrumb_items()
 *  - td_og_image_url( $post_id )             social image (_td_og_image → featured → brand fallback)
 *  - td_llms_txt()                           /llms.txt body
 *  - td_seo_health()                         list of {ok,label} checks for the dashboard
 */

defined( 'ABSPATH' ) || exit;

foreach ( [ 'Canonical', 'Noindex', 'RobotsTxt', 'LlmsTxt', 'Graph' ] as $td_seo_class ) {
	require_once __DIR__ . '/src/' . $td_seo_class . '.php';
}
unset( $td_seo_class );

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/staging.php';
require_once __DIR__ . '/indexing.php';
require_once __DIR__ . '/rank-math.php';
require_once __DIR__ . '/robots-txt.php';
require_once __DIR__ . '/llms.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/conflicts.php';
require_once __DIR__ . '/sitemaps.php';
require_once __DIR__ . '/breadcrumbs.php';
require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/sponsored.php';
require_once __DIR__ . '/social.php';
require_once __DIR__ . '/health.php';
require_once __DIR__ . '/setup.php';
