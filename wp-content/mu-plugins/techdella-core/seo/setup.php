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
	// Default titles/descriptions for the Journal home and the post type archives (§8.5).
	// Only filled when empty or still Rank Math's generic default: an editor's value always wins.
	$generic = [ '', '%title% %page% %sep% %sitename%', '%sitename% %page% %sep% %sitedesc%', '%pt_plural% Archive %page% %sep% %sitename%' ];
	foreach ( td_seo_default_option_titles() as $key => $value ) {
		if ( in_array( (string) ( $titles[ $key ] ?? '' ), $generic, true ) ) {
			$titles[ $key ] = $value;
		}
	}
	update_option( 'rank-math-options-titles', $titles );
	td_seo_apply_page_titles();

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
	// Official profiles (owner, 12 Oct 2026): footer links and Organization sameAs. Filled when the
	// option is missing or empty, so a later edit in WordPress is never overwritten.
	$profiles = get_option( 'td_social_profiles' );
	if ( ! is_array( $profiles ) || ! array_filter( $profiles ) ) {
		update_option( 'td_social_profiles', td_seo_default_social_profiles() );
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

/**
 * Rank Math title options for the Journal home and post type archives (§8.5):
 * titles ≤ 60 characters with the brand at the end, descriptions 140–155.
 *
 * @return array<string,string> Option key => value, for the current site.
 */
function td_seo_default_option_titles(): array {
	$sep = ' %sep% %sitename%';
	$all = [
		'marketing' => [
			'pt_case_study_archive_title'        => 'B2B Marketing Case Studies' . $sep,
			'pt_case_study_archive_description'  => 'Read TechDella case studies from 11 clients, including Paycape, WezOps and Shopinbos: the challenge, what we did across search, ads and email, and results.',
			'pt_em-glossary_archive_title'       => 'Marketing Glossary: SEO, AI Search & Growth' . $sep,
			'pt_em-glossary_archive_description' => 'Plain-English definitions of SEO, AI search, paid ads, email and growth marketing terms, with examples from our work and links to related guides.',
			'pt_td_resource_archive_title'       => 'Free B2B Marketing Guides & Checklists' . $sep,
			'pt_td_resource_archive_description' => 'Free B2B marketing guides, checklists and templates from TechDella on SEO, AI search, paid ads, email and automation. Download the one you need.',
			'pt_td_tool_archive_title'           => 'Free Marketing Tools' . $sep,
			'pt_td_tool_archive_description'     => 'Free marketing tools from TechDella: a UTM generator, readability and keyword density checkers, an on-page SEO checker, a subject line analyzer and more.',
			'pt_location_archive_title'          => 'B2B Growth Marketing by Market' . $sep,
			'pt_location_archive_description'    => 'How TechDella runs B2B search, paid ads, lead generation and email for companies in Nigeria, the US, the UK, Canada, the UAE and Europe. Find your market.',
			'pt_business_archive_title'          => 'How to Start a Business: Guides' . $sep,
			'pt_business_archive_description'    => 'Step-by-step guides on how to start a business, from planning and registration to winning your first customers. Browse the TechDella business library.',
		],
		'journal'   => [
			'homepage_title'       => 'B2B Marketing & AI Search Guides' . $sep,
			'homepage_description' => 'Practical guides on SEO, AI search, paid ads, lead generation, email and automation for B2B teams, written by the TechDella team that does the work.',
		],
		'studios'   => [
			'pt_studio_work_archive_title'       => 'Work: Products, Sites & Brands' . $sep,
			'pt_studio_work_archive_description' => 'See work from TechDella Studios: web apps, MVPs, websites, branding and motion design for founders and growing businesses, from first scope to launch.',
			'pt_case_study_archive_title'        => 'Case Studies' . $sep,
			'pt_case_study_archive_description'  => 'Case studies from TechDella Studios: how we scoped, designed and built products, websites and brands for founders and growing businesses, and what shipped.',
		],
	];
	return $all[ td_site() ] ?? [];
}

/**
 * Keyword-led SEO titles for the seeded pages (§8.5, §10.3), keyed by
 * "<post type>:<path>". The H1/page title stays as it is; this only fills
 * Rank Math's per-post title field.
 *
 * @return array<string,string>
 */
function td_seo_default_page_titles(): array {
	$sep = ' %sep% %sitename%';
	$all = [
		'marketing' => [
			'page:home'                      => 'B2B Growth Marketing Agency' . $sep,
			'page:services'                  => 'B2B Marketing Services: SEO, Ads, Email & AI' . $sep,
			'service:seo'                    => 'B2B SEO Services' . $sep,
			'service:technical-seo'          => 'Technical SEO Services' . $sep,
			'service:local-seo'              => 'Local SEO Services' . $sep,
			'service:ai-search-optimization' => 'AI Search Optimization (AEO & GEO)' . $sep,
			'service:paid-ads'               => 'Paid Ads Agency: Google, Meta, TikTok & LinkedIn' . $sep,
			'service:lead-generation'        => 'B2B Lead Generation Services' . $sep,
			'service:email-marketing'        => 'B2B Email Marketing Services' . $sep,
			'service:marketing-automation'   => 'AI Marketing Automation Services' . $sep,
			'page:growth-audit'              => 'Growth Audit: A Marketing Audit for B2B Teams' . $sep,
			'page:pricing'                   => 'Pricing & Ways to Start' . $sep,
			'page:about-us'                  => 'About Us: B2B Growth Marketing Team' . $sep,
		],
		'journal'   => [
			'page:newsletter'   => 'B2B Growth Newsletter' . $sep,
			'page:write-for-us' => 'Write for Us: Guest Posts' . $sep,
		],
		'studios'   => [
			'page:home'                      => 'Product Design & Development Studio' . $sep,
			'page:about'                     => 'About the Studio' . $sep,
			'page:mvp-calculator'            => 'MVP Cost Calculator' . $sep,
			'studio_service:mvp-development' => 'MVP Development Agency' . $sep,
			'studio_service:ai-automation'   => 'AI Automation Agency' . $sep,
			'studio_service:ui-ux-design'    => 'UI/UX Design Agency' . $sep,
			'studio_service:website-design'  => 'Website Design Agency' . $sep,
			'studio_service:branding'        => 'Branding Agency' . $sep,
			'studio_service:graphic-design'  => 'Graphic Design Services' . $sep,
			'studio_service:motion-design'   => 'Motion Design Studio' . $sep,
		],
	];
	return $all[ td_site() ] ?? [];
}

/**
 * Fill Rank Math's per-post title for the pages in td_seo_default_page_titles()
 * when it is empty (never overwrites an editor's title). Pages that don't exist
 * yet are skipped; `wp td setup` after seeding fills them.
 */
function td_seo_apply_page_titles(): void {
	foreach ( td_seo_default_page_titles() as $key => $title ) {
		[ $type, $path ] = explode( ':', $key, 2 );
		if ( ! post_type_exists( $type ) ) {
			continue;
		}
		$post = 'page' === $type && 'home' === $path && get_option( 'page_on_front' )
			? get_post( (int) get_option( 'page_on_front' ) )
			: get_page_by_path( $path, OBJECT, $type );
		if ( $post instanceof WP_Post && '' === (string) get_post_meta( $post->ID, 'rank_math_title', true ) ) {
			update_post_meta( $post->ID, 'rank_math_title', $title );
		}
	}
}

add_action(
	'td_setup',
	static function () {
		td_seo_setup();
	},
	20
);

/**
 * TechDella's official social profiles (owner, 12 Oct 2026), label => URL.
 *
 * @return array<string,string>
 */
function td_seo_default_social_profiles(): array {
	return [
		'LinkedIn'  => 'https://www.linkedin.com/company/techdella-inc/',
		'Instagram' => 'https://www.instagram.com/techdella.agency/',
		'X'         => 'https://x.com/TechDella',
		'TikTok'    => 'https://www.tiktok.com/@techdella',
		'YouTube'   => 'https://www.youtube.com/@techdella',
		'Facebook'  => 'https://www.facebook.com/techdella/',
	];
}
