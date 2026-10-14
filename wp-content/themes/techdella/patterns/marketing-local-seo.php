<?php
/**
 * Title: Marketing · Local SEO service
 * Slug: techdella/marketing-local-seo
 * Categories: techdella
 * Description: Local SEO service page (SEO pattern structure): hero with an example local result, strip, what's included, before/after, connected services, process + testimonial, FAQ, CTA.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: service
 * TD Slug: local-seo
 * TD Title: Local SEO
 * TD Description: Local SEO services that help nearby buyers find you in Google Maps, with Google Business Profile optimization, consistent listings and real location pages.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

// Block array builders (serialized with serialize_blocks() at the end; core JSON-encodes the attributes).
$td_b   = static fn( string $name, array $attrs = [], array $inner = [] ): array => [
	'blockName'    => str_contains( $name, '/' ) ? $name : 'techdella/' . $name,
	'attrs'        => $attrs,
	'innerBlocks'  => $inner,
	'innerHTML'    => '',
	'innerContent' => array_fill( 0, count( $inner ), null ),
];
$td_div = static function ( string $block, string $css_class, array $inner, array $extra = [] ): array {
	$base  = [
		'core/group'   => 'wp-block-group',
		'core/columns' => 'wp-block-columns',
		'core/column'  => 'wp-block-column',
	][ $block ];
	$attrs = array_merge( $css_class ? [ 'className' => $css_class ] : [], $extra );
	// Inline style exactly as core's save() writes it for these attributes (column width, group padding).
	$style = '';
	if ( isset( $attrs['width'] ) ) {
		$style = 'flex-basis:' . $attrs['width'];
	}
	foreach ( $attrs['style']['spacing']['padding'] ?? [] as $side => $value ) {
		$style .= ( $style ? ';' : '' ) . 'padding-' . $side . ':' . $value;
	}
	$open = '<div class="' . esc_attr( trim( $base . ' ' . $css_class ) ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
	return [
		'blockName'    => $block,
		'attrs'        => $attrs,
		'innerBlocks'  => $inner,
		'innerHTML'    => $open . '</div>',
		'innerContent' => array_merge( [ $open ], array_fill( 0, count( $inner ), null ), [ '</div>' ] ),
	];
};
// A full-width section with a container: optional surface (canvas|sand|dark) and extra group attributes.
$td_section = static fn( array $inner, string $surface = '', array $extra = [] ): array => $td_div(
	'core/group',
	trim( 'td-section ' . ( $surface ? 'td-section--' . $surface : '' ) ),
	[ $td_div( 'core/group', 'td-container', $inner ) ],
	$extra
);
// Two columns (left, right); widths as column `width` attributes (core saves them as flex-basis).
$td_cols = static fn( array $left, array $right, int $left_pct = 58 ): array => $td_div(
	'core/columns',
	'td-cols',
	[
		$td_div( 'core/column', '', $left, [ 'width' => $left_pct . '%' ] ),
		$td_div( 'core/column', '', $right, [ 'width' => ( 100 - $left_pct ) . '%' ] ),
	]
);

$td_seo_family = [
	[
		'label' => 'SEO',
		'url'   => '/services/seo/',
	],
	[
		'label' => 'Technical SEO',
		'url'   => '/services/technical-seo/',
	],
	[
		'label' => 'Local SEO',
		'url'   => '/services/local-seo/',
	],
	[
		'label' => 'AI Search',
		'url'   => '/services/ai-search-optimization/',
	],
];

$td_blocks = [
	$td_b(
		'service-switcher',
		[
			'label'   => 'SEO services',
			'items'   => $td_seo_family,
			'current' => '/services/local-seo/',
		]
	),
	$td_b(
		'hero',
		[
			'parentLabel'    => 'SEO',
			'parentUrl'      => '/services/seo/',
			'eyebrow'        => 'Local SEO',
			'title'          => 'Local SEO services that put you on the *map*',
			'lead'           => 'Our local SEO services help nearby buyers find you in Google Maps and local results. We optimize your Google Business Profile, keep your business details consistent across the web, and build location pages with real local substance.',
			'primaryLabel'   => 'Get a local SEO audit',
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => 'What’s included',
			'secondaryUrl'   => '#included',
			'platformsLabel' => 'Platforms we work in',
			'platforms'      => [
				[
					'name' => 'Google Search',
					'mark' => 'G',
					'slug' => 'google-search',
					'ink'  => true,
				],
				[
					'name' => 'Google Search Console',
					'mark' => 'G',
					'slug' => 'google-search-console',
				],
				[
					'name' => 'GA4',
					'mark' => 'GA',
					'slug' => 'ga4',
				],
				[
					'name' => 'Bing',
					'mark' => 'B',
					'slug' => 'bing',
				],
			],
		],
		[
			$td_b(
				'serp-mock',
				[
					'query'   => 'it support company near me',
					'results' => [
						[
							'rank'      => '#1',
							'url'       => 'yourcompany.com › lagos',
							'title'     => 'IT Support for Businesses in Lagos | YourCo',
							'text'      => 'Managed IT and helpdesk for offices on the Island and Mainland. Book an on-site visit…',
							'highlight' => true,
						],
						[
							'url'   => 'directory.example',
							'title' => 'IT companies in Lagos | Listings',
						],
					],
					'caption' => 'Example result',
					'variant' => 'sand',
				]
			),
		]
	),
	$td_div(
		'core/group',
		'td-container',
		[
			$td_b(
				'feature-grid',
				[
					'style'   => 'rule',
					'bare'    => true,
					'columns' => 3,
					'items'   => [
						[
							'title' => 'Google Business Profile',
							'text'  => 'Set up, verified and kept current.',
						],
						[
							'title' => 'Consistent details',
							'text'  => 'The same name, address and phone everywhere.',
						],
						[
							'title' => 'Real local pages',
							'text'  => 'Local proof and detail, not a swapped city name.',
						],
					],
				]
			),
		],
		[
			'style' => [
				'spacing' => [
					'padding' => [
						'top'    => '8px',
						'bottom' => '24px',
					],
				],
			],
		]
	),
	$td_b(
		'feature-grid',
		[
			'anchor'  => 'included',
			'title'   => 'What’s included in *local SEO*',
			'intro'   => 'Everything that decides whether you appear when someone nearby searches for what you do.',
			'style'   => 'icon',
			'columns' => 3,
			'items'   => [
				[
					'icon'  => 'target',
					'tone'  => 'canvas',
					'title' => 'Google Business Profile',
					'text'  => 'Categories, services, photos, hours and posts set up so your profile answers the questions buyers ask before they call.',
				],
				[
					'icon'  => 'list',
					'title' => 'Citations & listings',
					'text'  => 'Your business details made consistent across directories, maps and industry listings.',
				],
				[
					'icon'  => 'chat',
					'title' => 'Reviews process',
					'text'  => 'A simple way to ask happy clients for reviews, and a plan for replying to every review, good or bad.',
				],
				[
					'icon'  => 'lines',
					'tone'  => 'sand',
					'title' => 'Location pages',
					'text'  => 'One page per real market, with local clients, working hours, channels and FAQs. No city-swap templates.',
				],
				[
					'icon'  => 'search',
					'title' => 'Local keyword research',
					'text'  => 'The “near me” and city searches that bring calls and visits, mapped to the right pages.',
				],
				[
					'icon'  => 'chart',
					'tone'  => 'dark',
					'title' => 'Local rank & call tracking',
					'text'  => 'Map and local rankings, profile views, calls and direction requests, reported monthly.',
				],
			],
		]
	),
	$td_b(
		'before-after',
		[
			'title'      => 'Local pages with real *substance*',
			'intro'      => 'Google ignores pages that only swap the city name. Pages that rank show real local proof.',
			'layout'     => 'split',
			'surface'    => 'canvas',
			'afterStyle' => 'paper',
			'strike'     => true,
			'before'     => [
				'label' => 'Before',
				'title' => 'IT Support in {city}',
				'text'  => 'We are the leading IT support company in {city}. Contact us today.',
			],
			'after'      => [
				'label' => 'After',
				'title' => 'IT Support for Businesses in Lagos | YourCo',
				'text'  => 'On-site visits on the Island and Mainland, a local helpdesk from 8am to 6pm WAT, and three Lagos client stories.',
			],
		]
	),
	$td_b(
		'connected-services',
		[
			'title'   => 'Local SEO works better *connected*',
			'intro'   => 'Local rankings bring calls and visits. The rest of your system makes sure each one is followed up.',
			'current' => '/services/local-seo/',
			'items'   => [
				[
					'relation' => 'Part of',
					'title'    => 'SEO',
					'text'     => 'Content and keywords for every market.',
					'url'      => '/services/seo/',
				],
				[
					'relation' => 'You are here',
					'title'    => 'Local SEO',
					'text'     => 'Puts you on the map where buyers search.',
					'url'      => '/services/local-seo/',
				],
				[
					'relation' => 'Feeds',
					'title'    => 'AI Search',
					'text'     => 'Consistent details AI tools can trust.',
					'url'      => '/services/ai-search-optimization/',
				],
				[
					'relation' => 'Reinforces',
					'title'    => 'Paid ads',
					'text'     => 'Location targeting on Google and Meta.',
					'url'      => '/services/paid-ads/',
				],
				[
					'relation' => 'Converts',
					'title'    => 'Lead generation',
					'text'     => 'Local offers and booking forms.',
					'url'      => '/services/lead-generation/',
				],
				[
					'relation' => 'Follows up',
					'title'    => 'AI automation',
					'text'     => 'Missed calls and forms answered fast.',
					'url'      => '/services/marketing-automation/',
				],
			],
		]
	),
	$td_section(
		[
			$td_cols(
				[
					$td_b(
						'feature-grid',
						[
							'title'   => 'How a local SEO engagement *runs*',
							'style'   => 'numbered',
							'bare'    => true,
							'columns' => 2,
							'items'   => [
								[
									'title' => 'Audit',
									'text'  => 'Profile, listings, local pages and competitors.',
								],
								[
									'title' => 'Fix the basics',
									'text'  => 'Profile, categories and consistent details.',
								],
								[
									'title' => 'Build local pages',
									'text'  => 'One real page per market you serve.',
								],
								[
									'title' => 'Grow & report',
									'text'  => 'Reviews, posts and monthly local reporting.',
								],
							],
						]
					),
				],
				[
					$td_b(
						'testimonial',
						[
							'quote'   => 'Techdella reframed how we were thinking about our market entirely. We went from scattered tactics to a positioning and GTM system that actually made sense for where we were heading. The difference was immediate.',
							'name'    => 'Levi Barbosa',
							'role'    => 'CEO',
							'company' => 'WezOps',
							'image'   => 'people/levi-barbosa-wezops',
							'logo'    => 'clients/wezops',
							'variant' => 'arch',
						]
					),
				]
			),
		]
	),
	$td_b(
		'faq',
		[
			'title' => 'Local SEO *questions*',
			'items' => [
				[
					'question' => 'What is local SEO?',
					'answer'   => 'Local SEO helps your business appear when people nearby search for what you offer, in Google Maps, the local pack and city searches. It covers your Google Business Profile, listings, reviews and location pages.',
				],
				[
					'question' => 'Do we need a physical address?',
					'answer'   => 'For a Google Business Profile, you need a real address or a defined service area. We never create addresses or offices that don’t exist.',
				],
				[
					'question' => 'Can you create pages for every city we want to reach?',
					'answer'   => 'Only for markets where you have something real to say: clients, staff, working hours or local channels. Thin city-swap pages can hurt the whole site.',
				],
				[
					'question' => 'Do you write fake reviews?',
					'answer'   => 'No. We help you ask real clients for reviews and reply to them. Fake reviews break Google’s rules and put your profile at risk.',
				],
				[
					'question' => 'How long does local SEO take?',
					'answer'   => 'Profile and listing fixes can show up within weeks; location pages and reviews build over months. We set expectations in your plan.',
				],
			],
		]
	),
	$td_b(
		'cta-dark',
		[
			'title'       => 'Be the *local* answer',
			'lead'        => 'Get a local SEO audit of your profile, listings and location pages.',
			'buttonLabel' => 'Get a local SEO audit',
			'buttonUrl'   => '/growth-audit/',
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
