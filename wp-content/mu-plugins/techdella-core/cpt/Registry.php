<?php
/**
 * The content model (§7) as data: post types, the sites they live on (D3),
 * their live URL bases (§9.1–9.3), capability types (§14.1), admin menu
 * group (§14.2) and the noindex/sitemap flags the SEO module reads (§8.3).
 *
 * Pure PHP so it can be unit-tested; cpt/register.php turns it into
 * register_post_type()/register_taxonomy() calls.
 */

namespace TechDella\Core\Cpt;

defined( 'ABSPATH' ) || exit;

/**
 * Post type and taxonomy definitions.
 */
final class Registry {

	public const ALL_SITES = [ 'marketing', 'journal', 'studios' ];

	/**
	 * Post types in the content model. `post` and `page` are core and only
	 * listed so menus, checklists and caps can treat them uniformly
	 * (`core` => true: never registered by us).
	 *
	 * Keys per type:
	 *  sites      – sites the type is registered on.
	 *  singular / plural – labels.
	 *  slug       – rewrite base (false = no front end URL).
	 *  archive    – has_archive value (false | slug).
	 *  public     – publicly queryable front end.
	 *  cap        – [ singular, plural ] capability type.
	 *  menu       – admin group (td-content|td-growth|td-studios|td-brand).
	 *  supports   – post type supports.
	 *  noindex / sitemap – SEO flags (§8.3).
	 *  checklist  – pre-publish checklist applies (§10.1).
	 *  permission – needs permission_confirmed before publishing (§7, §10.5).
	 *  cadence    – refresh cadence in months (§10.1), 0 = none.
	 *  icon       – dashicon.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function post_types(): array {
		$editor = [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'author' ];
		$plain  = [ 'title', 'revisions', 'custom-fields' ];
		$money  = [ 'td_site_page', 'td_site_pages' ];

		return [
			'post'           => [
				'core'      => true,
				'sites'     => [ 'journal', 'marketing' ],
				'singular'  => 'Guide',
				'plural'    => 'Guides',
				'menu'      => 'td-content',
				'checklist' => true,
				'cadence'   => 6,
			],
			'page'           => [
				'core'     => true,
				'sites'    => self::ALL_SITES,
				'singular' => 'Page',
				'plural'   => 'Pages',
				'menu'     => 'td-content',
				'cadence'  => 0,
			],
			'service'        => [
				'sites'     => [ 'marketing' ],
				'singular'  => 'Service',
				'plural'    => 'Services',
				'slug'      => 'services',
				'archive'   => false, // /services/ is the hub page (connected-system block, §9.3).
				'cap'       => $money,
				'menu'      => 'td-growth',
				'supports'  => array_merge( $editor, [ 'page-attributes' ] ), // Flat URLs: /services/technical-seo/ (url-map).
				'checklist' => true,
				'cadence'   => 3,
				'icon'      => 'dashicons-chart-line',
			],
			'solution'       => [
				'sites'     => [ 'marketing' ],
				'singular'  => 'Solution',
				'plural'    => 'Solutions',
				'slug'      => 'solution', // Live base (not td_solution / solutions).
				'archive'   => false,
				'cap'       => $money,
				'menu'      => 'td-growth',
				'supports'  => $editor,
				'checklist' => true,
				'cadence'   => 3,
				'icon'      => 'dashicons-lightbulb',
			],
			'location'       => [
				'sites'     => [ 'marketing' ],
				'singular'  => 'Location',
				'plural'    => 'Locations',
				'slug'      => 'locations',
				'archive'   => 'locations',
				'cap'       => $money,
				'menu'      => 'td-growth',
				'supports'  => $editor,
				'checklist' => true,
				'cadence'   => 3,
				'icon'      => 'dashicons-location',
			],
			'td_tool'        => [
				'sites'    => [ 'marketing' ],
				'singular' => 'Tool',
				'plural'   => 'Tools',
				'slug'     => 'tools',
				'archive'  => 'tools',
				'cap'      => $money,
				'menu'     => 'td-growth',
				'supports' => $editor,
				'cadence'  => 3,
				'icon'     => 'dashicons-admin-tools',
			],
			'case_study'     => [
				'sites'      => [ 'marketing', 'studios' ],
				'singular'   => 'Case study',
				'plural'     => 'Case studies',
				'slug'       => 'case-studies',
				'archive'    => 'case-studies',
				'cap'        => [ 'td_case_study', 'td_case_studies' ],
				'menu'       => 'td-content',
				'supports'   => $editor,
				'checklist'  => true,
				'permission' => true,
				'cadence'    => 0,
				'icon'       => 'dashicons-awards',
			],
			'em-glossary'    => [
				'sites'     => [ 'marketing' ],
				'singular'  => 'Glossary term',
				'plural'    => 'Glossary',
				'slug'      => 'glossary',
				'archive'   => 'glossary',
				'cap'       => [ 'td_glossary_term', 'td_glossary_terms' ],
				'menu'      => 'td-content',
				'supports'  => $editor,
				'checklist' => true,
				'cadence'   => 6,
				'icon'      => 'dashicons-book-alt',
			],
			'td_resource'    => [
				'sites'     => [ 'marketing' ],
				'singular'  => 'Resource',
				'plural'    => 'Resources',
				'slug'      => 'resources',
				'archive'   => 'resources',
				'cap'       => [ 'td_resource', 'td_resources' ],
				'menu'      => 'td-content',
				'supports'  => $editor,
				'checklist' => true,
				'cadence'   => 6,
				'icon'      => 'dashicons-media-document',
			],
			'business'       => [
				'sites'    => [ 'marketing' ],
				'singular' => 'Business guide',
				'plural'   => 'Business library',
				'slug'     => 'business',
				'archive'  => 'business', // /business/, /business/page/N/, /business/<slug>/ (old custom rules).
				'cap'      => $money,
				'menu'     => 'td-content',
				'supports' => $editor,
				'cadence'  => 0,
				'icon'     => 'dashicons-store',
			],
			'thank-you'      => [
				'sites'    => self::ALL_SITES,
				'singular' => 'Thank-you page',
				'plural'   => 'Thank-you pages',
				'slug'     => 'thank-you',
				'archive'  => false,
				'search'   => false,
				'cap'      => $money,
				'menu'     => 'td-growth',
				'supports' => $editor,
				'noindex'  => true,
				'sitemap'  => false,
				'cadence'  => 0,
				'icon'     => 'dashicons-yes-alt',
			],
			'document'       => [
				'sites'    => [ 'marketing' ], // Never on Journal/Studios (§7).
				'singular' => 'Document',
				'plural'   => 'Documents',
				'slug'     => 'document',
				'archive'  => false,
				'search'   => false,
				'cap'      => [ 'td_document', 'td_documents' ],
				'menu'     => 'td-content',
				'supports' => [ 'title', 'editor', 'revisions', 'author' ],
				'noindex'  => true,
				'sitemap'  => false,
				'private'  => true,
				'cadence'  => 0,
				'icon'     => 'dashicons-lock',
			],
			'studio_service' => [
				'sites'     => [ 'studios' ],
				'singular'  => 'Studio service',
				'plural'    => 'Studio services',
				'slug'      => 'services',
				'archive'   => false,
				'cap'       => $money,
				'menu'      => 'td-studios',
				'supports'  => $editor,
				'checklist' => true,
				'cadence'   => 3,
				'icon'      => 'dashicons-art',
			],
			'studio_work'    => [
				'sites'     => [ 'studios' ],
				'singular'  => 'Work',
				'plural'    => 'Work',
				'slug'      => 'work',
				'archive'   => 'work',
				'cap'       => [ 'td_work', 'td_works' ],
				'menu'      => 'td-studios',
				'supports'  => $editor,
				'checklist' => true,
				'cadence'   => 0,
				'icon'      => 'dashicons-portfolio',
			],
			'td_team'        => [
				'sites'    => self::ALL_SITES,
				'singular' => 'Team member',
				'plural'   => 'Team',
				'slug'     => false,
				'public'   => false,
				'cap'      => [ 'td_brand_item', 'td_brand_items' ],
				'menu'     => 'td-brand',
				'supports' => array_merge( $plain, [ 'thumbnail' ] ),
				'sitemap'  => false,
				'icon'     => 'dashicons-groups',
			],
			'td_testimonial' => [
				'sites'      => self::ALL_SITES,
				'singular'   => 'Testimonial',
				'plural'     => 'Testimonials',
				'slug'       => false,
				'public'     => false,
				'cap'        => [ 'td_brand_item', 'td_brand_items' ],
				'menu'       => 'td-brand',
				'supports'   => $plain,
				'permission' => true,
				'sitemap'    => false,
				'icon'       => 'dashicons-format-quote',
			],
			'platform'       => [
				'sites'    => self::ALL_SITES,
				'singular' => 'Platform',
				'plural'   => 'Platforms',
				'slug'     => false,
				'public'   => false,
				'cap'      => [ 'td_brand_item', 'td_brand_items' ],
				'menu'     => 'td-brand',
				'supports' => $plain,
				'sitemap'  => false,
				'icon'     => 'dashicons-share',
			],
		];
	}

	/**
	 * Taxonomies (§7). `types` are filtered to the types that exist on the
	 * current site at registration time.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function taxonomies(): array {
		return [
			'td_industry'       => [
				'types'    => [ 'solution', 'case_study', 'post' ],
				'singular' => 'Industry',
				'plural'   => 'Industries',
			],
			'td_service_tag'    => [
				'types'    => [ 'post', 'em-glossary' ],
				'singular' => 'Service tag',
				'plural'   => 'Service tags',
			],
			'division'          => [
				'types'    => [ 'case_study' ],
				'singular' => 'Division',
				'plural'   => 'Divisions',
				'terms'    => [
					'marketing' => 'Marketing',
					'studios'   => 'Studios',
				],
			],
			'glossary_category' => [
				'types'    => [ 'em-glossary' ],
				'singular' => 'Glossary category',
				'plural'   => 'Glossary categories',
			],
		];
	}

	/**
	 * Post types (including core) that exist on a site.
	 *
	 * @param string $site Site key.
	 * @return array<string,array<string,mixed>>
	 */
	public static function for_site( string $site ): array {
		return array_filter(
			self::post_types(),
			static fn( array $def ): bool => in_array( $site, (array) $def['sites'], true )
		);
	}

	/**
	 * Post type keys carrying a flag (e.g. 'checklist', 'permission').
	 *
	 * @param string $flag Flag name.
	 * @return string[]
	 */
	public static function with_flag( string $flag ): array {
		return array_keys( array_filter( self::post_types(), static fn( array $def ): bool => ! empty( $def[ $flag ] ) ) );
	}

	/**
	 * Post types forced noindex (§8.3).
	 *
	 * @return string[]
	 */
	public static function noindex(): array {
		return self::with_flag( 'noindex' );
	}

	/**
	 * Post types excluded from sitemaps (§8.3).
	 *
	 * @return string[]
	 */
	public static function excluded_from_sitemap(): array {
		return array_keys( array_filter( self::post_types(), static fn( array $def ): bool => array_key_exists( 'sitemap', $def ) && false === $def['sitemap'] ) );
	}

	/**
	 * Every [ singular, plural ] capability type we define.
	 *
	 * @return array<int,array{0:string,1:string}>
	 */
	public static function capability_types(): array {
		$out = [];
		foreach ( self::post_types() as $def ) {
			if ( ! empty( $def['cap'] ) ) {
				$out[ $def['cap'][0] ] = [ $def['cap'][0], $def['cap'][1] ];
			}
		}
		// The popups module's CPT (§13.1) uses this capability type.
		$out['td_popup'] = [ 'td_popup', 'td_popups' ];
		return array_values( $out );
	}

	/**
	 * Primitive capabilities WordPress maps for a capability type.
	 *
	 * @param string $plural Plural capability base.
	 * @return array<string,string> role-level key (edit_posts, …) => real cap.
	 */
	public static function primitive_caps( string $plural ): array {
		return [
			'edit_posts'             => "edit_{$plural}",
			'edit_others_posts'      => "edit_others_{$plural}",
			'edit_private_posts'     => "edit_private_{$plural}",
			'edit_published_posts'   => "edit_published_{$plural}",
			'publish_posts'          => "publish_{$plural}",
			'read_private_posts'     => "read_private_{$plural}",
			'delete_posts'           => "delete_{$plural}",
			'delete_others_posts'    => "delete_others_{$plural}",
			'delete_private_posts'   => "delete_private_{$plural}",
			'delete_published_posts' => "delete_published_{$plural}",
		];
	}
}
