<?php
/**
 * /llms.txt (§8.1, §8.3): plain Markdown generated from the CMS per site —
 * who we are (entity facts), services with URLs, key guides, contact.
 * Cached in a transient, cleared on save_post.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Seo\LlmsTxt;

/**
 * Published posts of a type (empty when the type is not registered).
 *
 * @param string              $type Post type.
 * @param array<string,mixed> $args Extra get_posts() args.
 * @return WP_Post[]
 */
function td_seo_llms_posts( string $type, array $args = [] ): array {
	if ( ! post_type_exists( $type ) ) {
		return [];
	}
	return get_posts(
		array_merge(
			[
				'post_type'        => $type,
				'post_status'      => 'publish',
				'numberposts'      => 50,
				'orderby'          => [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				],
				'suppress_filters' => false,
				'meta_query'       => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- cached in a transient.
					'relation' => 'OR',
					[
						'key'     => '_td_noindex',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => '_td_noindex',
						'value'   => '1',
						'compare' => '!=',
					],
				],
			],
			$args
		)
	);
}

/**
 * A llms.txt item for a post.
 *
 * @param WP_Post $post Post.
 * @return array{title:string,url:string,description:string}
 */
function td_seo_llms_item( WP_Post $post ): array {
	$desc = td_seo_meta_description( $post->ID );
	if ( '' === $desc ) {
		$short = td_field( 'short_definition', $post->ID );
		$desc  = is_string( $short ) ? $short : ( '' !== $post->post_excerpt ? $post->post_excerpt : '' );
	}
	return [
		'title'       => td_seo_text( get_the_title( $post ) ),
		'url'         => (string) get_permalink( $post ),
		'description' => td_seo_text( $desc ),
	];
}

/**
 * A page's item by path, or null when no such published page exists.
 *
 * @param string $path  Page path.
 * @param string $title Title fallback.
 * @return array{title:string,url:string,description:string}|null
 */
function td_seo_llms_page( string $path, string $title = '' ): ?array {
	$page = get_page_by_path( trim( $path, '/' ) );
	if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
		return null;
	}
	$item = td_seo_llms_item( $page );
	if ( '' !== $title ) {
		$item['title'] = $title;
	}
	return $item;
}

/**
 * Data for the llms.txt builder for this site.
 *
 * @return array<string,mixed>
 */
function td_seo_llms_data(): array {
	$site      = td_site();
	$marketing = td_url( 'marketing', '/' );
	$journal   = td_url( 'journal', '/' );
	$studios   = td_url( 'studios', '/' );
	$contact   = td_url( 'marketing', '/book-a-discovery-call/' );

	$facts    = [
		'Name'                   => 'TechDella',
		'What we do'             => 'B2B growth marketing: search (SEO and AI search optimization), paid ads, lead generation, email marketing and AI marketing automation. TechDella Studios builds products: MVPs, AI automation, UI/UX, websites, branding and motion design.',
		'Founder'                => 'Joanna Okedara-Kalu (Founder & CEO)',
		'Co-founder'             => 'Chinedu Emmanuel Kalu (COO & Co-founder)',
		'Client countries'       => 'Nigeria, United States, Mexico, South Africa, Lebanon',
		'Markets served'         => implode( ', ', td_seo_markets() ),
		'Published case studies' => '11 (' . td_url( 'marketing', '/case-studies/' ) . ')',
		'Websites'               => 'Marketing ' . $marketing . ' · Journal ' . $journal . ' · Studios ' . $studios,
	];
	$sections = [];

	if ( 'marketing' === $site ) {
		$title   = 'TechDella';
		$summary = 'TechDella is a B2B growth marketing agency. Search is the lead offer (SEO and AI search optimization), connected to paid ads, lead generation, email marketing and AI marketing automation.';
		// The six services (§9.3); titles/descriptions come from the CMS when the post exists.
		$core     = [
			'seo'                    => 'SEO',
			'ai-search-optimization' => 'AI search optimization',
			'paid-ads'               => 'Paid ads',
			'lead-generation'        => 'Lead generation',
			'email-marketing'        => 'Email marketing',
			'marketing-automation'   => 'AI marketing automation',
		];
		$services = [];
		$posts    = td_seo_llms_posts( 'service' );
		$by_slug  = [];
		foreach ( $posts as $post ) {
			$by_slug[ $post->post_name ] = $post;
		}
		foreach ( $core as $slug => $label ) {
			$services[] = isset( $by_slug[ $slug ] ) ? td_seo_llms_item( $by_slug[ $slug ] ) : [
				'title'       => $label,
				'url'         => td_url( 'marketing', '/services/' . $slug . '/' ),
				'description' => '',
			];
		}
		foreach ( $posts as $post ) {
			if ( ! isset( $core[ $post->post_name ] ) ) {
				$services[] = td_seo_llms_item( $post );
			}
		}
		$sections['Services'] = $services;

		$start = array_filter(
			[
				td_seo_llms_page( 'growth-audit', 'Growth audit' ),
				td_seo_llms_page( 'pricing', 'Pricing: ways to start' ),
				td_seo_llms_page( 'services', 'All services' ),
			]
		);
		if ( $start ) {
			$sections['Start here'] = array_values( $start );
		}
		$sections['Case studies'] = array_map( 'td_seo_llms_item', td_seo_llms_posts( 'case_study', [ 'numberposts' => 20 ] ) );
		$sections['Industries']   = array_map( 'td_seo_llms_item', td_seo_llms_posts( 'solution' ) );
		$sections['Markets']      = array_map( 'td_seo_llms_item', td_seo_llms_posts( 'location' ) );

		$guides = array_map( 'td_seo_llms_item', td_seo_llms_posts( 'td_resource', [ 'numberposts' => 20 ] ) );
		$hubs   = [
			'glossary'  => [ 'em-glossary', 'Marketing glossary' ],
			'tools'     => [ 'td_tool', 'Free marketing tools' ],
			'resources' => [ 'td_resource', 'Guides and checklists (PDF)' ],
		];
		foreach ( $hubs as $hub => [ $type, $label ] ) {
			if ( post_type_exists( $type ) ) {
				$guides[] = [
					'title'       => $label,
					'url'         => td_url( 'marketing', '/' . $hub . '/' ),
					'description' => '',
				];
			}
		}
		$guides[]               = [
			'title'       => 'TechDella Journal',
			'url'         => $journal,
			'description' => 'Guides on SEO, AI search, paid ads, lead generation, email and automation.',
		];
		$sections['Key guides'] = $guides;
		$sections['Tools']      = array_map( 'td_seo_llms_item', td_seo_llms_posts( 'td_tool' ) );
	} elseif ( 'studios' === $site ) {
		$title   = 'TechDella Studios';
		$summary = 'TechDella Studios is the build arm of TechDella: MVP development, AI automation, UI/UX design, website design, branding, graphic design and motion design.';
		$core    = [
			'mvp-development' => 'MVP development',
			'ai-automation'   => 'AI automation',
			'ui-ux-design'    => 'UI/UX design',
			'website-design'  => 'Website design',
			'branding'        => 'Branding',
			'graphic-design'  => 'Graphic design',
			'motion-design'   => 'Motion design',
		];
		$posts   = td_seo_llms_posts( 'studio_service' );
		$by_slug = [];
		foreach ( $posts as $post ) {
			$by_slug[ $post->post_name ] = $post;
		}
		$services = [];
		foreach ( $core as $slug => $label ) {
			$services[] = isset( $by_slug[ $slug ] ) ? td_seo_llms_item( $by_slug[ $slug ] ) : [
				'title'       => $label,
				'url'         => td_url( 'studios', '/services/' . $slug . '/' ),
				'description' => '',
			];
		}
		$sections['Services']           = $services;
		$work                           = array_map( 'td_seo_llms_item', td_seo_llms_posts( 'studio_work' ) );
		$work[]                         = [
			'title'       => 'All work',
			'url'         => td_url( 'studios', '/work/' ),
			'description' => 'Projects labelled "Concept" are concept work, not client projects.',
		];
		$sections['Work']               = $work;
		$sections['Start a project']    = array_values(
			array_filter(
				[
					td_seo_llms_page( 'start', 'Project brief builder' ),
					td_seo_llms_page( 'mvp-calculator', 'MVP cost calculator' ),
				]
			)
		);
		$sections['Marketing services'] = [
			[
				'title'       => 'TechDella (marketing)',
				'url'         => $marketing,
				'description' => 'Search, paid ads, lead generation, email and AI marketing automation.',
			],
		];
	} else {
		$title      = 'TechDella Journal';
		$summary    = 'The TechDella Journal publishes practical guides on B2B growth: SEO, AI search, paid ads, lead generation, email marketing and marketing automation. Every guide has a named author and reviewer.';
		$categories = [];
		foreach ( get_categories( [ 'hide_empty' => true ] ) as $cat ) {
			if ( $cat instanceof WP_Term && 'uncategorized' !== $cat->slug ) {
				$link         = get_term_link( $cat );
				$categories[] = [
					'title'       => td_seo_text( $cat->name ),
					'url'         => is_string( $link ) ? $link : '',
					'description' => td_seo_text( $cat->description ),
				];
			}
		}
		$sections['Topics'] = $categories;
		$sticky             = array_map( 'intval', (array) get_option( 'sticky_posts', [] ) );
		if ( $sticky ) {
			$sections['Key guides'] = array_map( 'td_seo_llms_item', td_seo_llms_posts( 'post', [ 'post__in' => $sticky ] ) );
		}
		$sections['Latest guides'] = array_map(
			'td_seo_llms_item',
			td_seo_llms_posts(
				'post',
				[
					'numberposts' => 15,
					'orderby'     => 'date',
					'order'       => 'DESC',
				]
			)
		);
		$sections['Services']      = [
			[
				'title'       => 'TechDella services',
				'url'         => td_url( 'marketing', '/services/' ),
				'description' => 'SEO, AI search optimization, paid ads, lead generation, email marketing, AI marketing automation.',
			],
		];
	}

	$sections['Contact'] = [
		[
			'title'       => 'Book a discovery call',
			'url'         => $contact,
			'description' => 'Talk to the team about your growth plan.',
		],
	];

	return (array) apply_filters(
		'td_llms_txt_data',
		[
			'title'    => $title,
			'summary'  => $summary,
			'facts'    => $facts,
			'sections' => $sections,
		]
	);
}

/**
 * The /llms.txt body for this site (cached).
 */
function td_llms_txt(): string {
	$key    = 'td_llms_txt_' . td_site();
	$cached = get_transient( $key );
	if ( is_string( $cached ) && '' !== $cached ) {
		return $cached;
	}
	$body = td_year_tokens( LlmsTxt::build( td_seo_llms_data() ) );
	set_transient( $key, $body, DAY_IN_SECONDS );
	return $body;
}

/**
 * Clear the cached llms.txt.
 */
function td_seo_llms_flush(): void {
	delete_transient( 'td_llms_txt_' . td_site() );
}

add_action( 'save_post', static fn() => td_seo_llms_flush() );
add_action( 'deleted_post', static fn() => td_seo_llms_flush() );
add_action( 'edited_term', static fn() => td_seo_llms_flush() );
add_action( 'td_setup', static fn() => td_seo_llms_flush() );

// Serve /llms.txt before WordPress resolves a query (no rewrite rule to flush).
add_action(
	'parse_request',
	static function ( WP $wp ) {
		if ( 'llms.txt' !== trim( (string) $wp->request, '/' ) ) {
			return;
		}
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' );
		echo td_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text/plain, built from sanitized text.
		exit;
	},
	0
);
