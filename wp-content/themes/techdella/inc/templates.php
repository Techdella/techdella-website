<?php
/**
 * Template loading: PHP templates live in templates/ (§3). WordPress's normal
 * hierarchy is kept; each candidate is looked up in templates/ first.
 * Site-specific variants win: templates/<site>/front-page.php beats
 * templates/front-page.php, so Journal and Studios can each have their own home.
 */

defined( 'ABSPATH' ) || exit;

$td_template_types = [ 'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'embed', 'home', 'frontpage', 'privacypolicy', 'page', 'paged', 'search', 'single', 'singular', 'attachment' ];
foreach ( $td_template_types as $td_type ) {
	add_filter(
		"{$td_type}_template_hierarchy",
		static function ( array $templates ): array {
			$out = [];
			foreach ( $templates as $template ) {
				$out[] = 'templates/' . td_site() . '/' . $template;
				$out[] = 'templates/' . $template;
			}
			return array_merge( $out, $templates );
		}
	);
}
unset( $td_template_types, $td_type );

/**
 * Load a part for the current site: parts/<slug>-<site>.php, falling back to
 * parts/<slug>.php.
 *
 * @param string              $slug Part name, e.g. "nav" or "footer".
 * @param array<string,mixed> $args Passed to the part as $args.
 */
function td_part( string $slug, array $args = [] ): void {
	get_template_part( 'parts/' . $slug, td_site(), $args );
}
