<?php
/**
 * Content model module (§7): post types, taxonomies, fields (ACF JSON +
 * post meta), locked block templates and publishing gates.
 *
 * Public helpers for other modules:
 *  - td_post_types_noindex()                 thank-you, document (§8.3)
 *  - td_post_types_excluded_from_sitemap()   document, thank-you, td_testimonial, td_team, platform
 *  - td_cpt_definition( $type )              the Registry entry for a type
 *  - td_cpt_fields()                         TechDella\Core\Cpt\Fields instance
 *  - td_acf_pro_active()                     whether ACF Pro features are available (D4)
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/Registry.php';
require_once __DIR__ . '/Fields.php';
require_once __DIR__ . '/register.php';
require_once __DIR__ . '/meta.php';
require_once __DIR__ . '/templates.php';
require_once __DIR__ . '/gates.php';
require_once __DIR__ . '/team-users.php';

/**
 * Post types that are always noindex (meta robots + X-Robots-Tag), §8.3.
 * The SEO module reads this.
 *
 * @return string[]
 */
function td_post_types_noindex(): array {
	return (array) apply_filters( 'td_post_types_noindex', \TechDella\Core\Cpt\Registry::noindex() );
}

/**
 * Post types excluded from sitemaps (§8.3). The SEO module reads this.
 *
 * @return string[]
 */
function td_post_types_excluded_from_sitemap(): array {
	return (array) apply_filters( 'td_post_types_excluded_from_sitemap', \TechDella\Core\Cpt\Registry::excluded_from_sitemap() );
}

/**
 * The Registry definition of a post type on this site, or null.
 *
 * @param string $post_type Post type key.
 * @return array<string,mixed>|null
 */
function td_cpt_definition( string $post_type ): ?array {
	$defs = \TechDella\Core\Cpt\Registry::for_site( td_site() );
	return $defs[ $post_type ] ?? null;
}

/**
 * Field definitions parsed from acf-json/ (cached per request).
 */
function td_cpt_fields(): \TechDella\Core\Cpt\Fields {
	static $fields = null;
	if ( null === $fields ) {
		$fields = \TechDella\Core\Cpt\Fields::from_dir( TD_CORE_DIR . '/acf-json' );
	}
	return $fields;
}

/**
 * Whether ACF is active at all.
 */
function td_acf_active(): bool {
	return function_exists( 'acf_get_setting' );
}

/**
 * Whether ACF Pro (repeater, flexible content, gallery) is active (D4 [CONFIRM licence]).
 */
function td_acf_pro_active(): bool {
	return td_acf_active() && defined( 'ACF_PRO' ) && ACF_PRO;
}
