<?php
/**
 * Title: Marketing · Technical SEO service
 * Slug: techdella/marketing-technical-seo
 * Categories: techdella
 * Description: Technical SEO service page (SEO pattern structure): hero with an example site-health report, strip, what's included, before/after, connected services, process + testimonial, FAQ, CTA.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: service
 * TD Slug: technical-seo
 * TD Title: Technical SEO
 * TD Description: Technical SEO services that fix what stops Google crawling and ranking your pages: slow templates, broken links, duplicate URLs and missing schema.
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
			'current' => '/services/technical-seo/',
		]
	),
	$td_b(
		'hero',
		[
			'parentLabel'    => 'SEO',
			'parentUrl'      => '/services/seo/',
			'eyebrow'        => 'Technical SEO',
			'title'          => 'Technical SEO services that make your site easy to *crawl*',
			'lead'           => 'Our technical SEO services fix what stops Google from crawling, indexing and ranking your pages: slow templates, broken internal links, duplicate URLs and missing schema. We audit the site, rank every issue by impact and ship the fixes in your CMS.',
			'primaryLabel'   => 'Get a technical SEO audit',
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => 'What’s included',
			'secondaryUrl'   => '#included',
			'platformsLabel' => 'Tools we work in',
			'platforms'      => [
				[
					'name' => 'Google Search Console',
					'mark' => 'G',
					'slug' => 'google-search-console',
					'ink'  => true,
				],
				[
					'name' => 'GA4',
					'mark' => 'GA',
					'slug' => 'ga4',
				],
				[
					'name' => 'Semrush',
					'mark' => 'S',
					'slug' => 'semrush',
				],
				[
					'name' => 'WordPress & Webflow',
					'mark' => 'W',
					'slug' => 'wordpress',
				],
			],
		],
		[
			$td_b(
				'report-mock',
				[
					'cardTitle' => 'Site health',
					'badge'     => 'Example report',
					'kpis'      => [
						[
							'label' => 'Pages indexed',
							'value' => '420',
						],
						[
							'label' => 'Crawl errors',
							'value' => '12',
						],
						[
							'label'     => 'Fixes shipped',
							'value'     => '30',
							'highlight' => true,
						],
					],
					'barsLabel' => 'Open issues by type',
					'bars'      => [
						[
							'label' => 'Speed',
							'value' => '8',
						],
						[
							'label' => 'Indexing',
							'value' => '6',
						],
						[
							'label' => 'Links',
							'value' => '5',
						],
						[
							'label'     => 'Schema',
							'value'     => '3',
							'highlight' => true,
						],
					],
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
							'title' => 'Fixes ranked by impact',
							'text'  => 'You know what to do first, and why.',
						],
						[
							'title' => 'Shipped, not just reported',
							'text'  => 'We make the changes in your CMS or with your developers.',
						],
						[
							'title' => 'Verified after release',
							'text'  => 'Every fix is checked in Search Console once it’s live.',
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
			'title'   => 'What’s included in *technical SEO*',
			'intro'   => 'The foundations every other SEO effort depends on, checked page by page and template by template.',
			'style'   => 'icon',
			'columns' => 3,
			'items'   => [
				[
					'icon'  => 'search',
					'tone'  => 'canvas',
					'title' => 'Crawling & indexing',
					'text'  => 'Robots rules, sitemaps, canonicals and noindex tags set so Google indexes the pages that matter, and skips the ones that don’t.',
				],
				[
					'icon'  => 'chart',
					'title' => 'Core Web Vitals',
					'text'  => 'Loading speed, responsiveness and layout stability measured on mobile, then fixed in the templates that cause them.',
				],
				[
					'icon'  => 'list',
					'title' => 'Schema markup',
					'text'  => 'Structured data for your organization, services, articles and people, validated and free of duplicates.',
				],
				[
					'icon'  => 'nodes',
					'tone'  => 'sand',
					'title' => 'Site architecture',
					'text'  => 'Clear URL structure, breadcrumbs and internal links, so your most important pages are never more than a few clicks deep.',
				],
				[
					'icon'  => 'lines',
					'title' => 'Redirects & migrations',
					'text'  => 'One-hop 301s, no chains or loops, and a URL map before any redesign or domain move.',
				],
				[
					'icon'  => 'target',
					'tone'  => 'dark',
					'title' => 'Monitoring',
					'text'  => 'Monthly checks for new errors, broken links and pages that drop out of the index.',
				],
			],
		]
	),
	$td_b(
		'before-after',
		[
			'title'      => 'One page, *one* URL',
			'intro'      => 'Duplicate and parameter URLs split your ranking signals. A clean setup tells Google exactly which page to rank.',
			'layout'     => 'split',
			'surface'    => 'canvas',
			'afterStyle' => 'paper',
			'strike'     => true,
			'before'     => [
				'label' => 'Before',
				'title' => '4 URLs for the same page',
				'text'  => '/services/?ref=nav, /Services, /services/index.php and http://yourcompany.com/services all indexed separately.',
			],
			'after'      => [
				'label' => 'After',
				'title' => 'https://yourcompany.com/services/',
				'text'  => 'One canonical URL, the others redirected in a single hop, and internal links updated to match.',
			],
		]
	),
	$td_b(
		'connected-services',
		[
			'title'   => 'Technical SEO works better *connected*',
			'intro'   => 'A fast, crawlable site makes every other channel cheaper: content ranks sooner, AI tools can read it and ad clicks land on pages that load.',
			'current' => '/services/technical-seo/',
			'items'   => [
				[
					'relation' => 'Part of',
					'title'    => 'SEO',
					'text'     => 'Content and keywords built on solid foundations.',
					'url'      => '/services/seo/',
				],
				[
					'relation' => 'You are here',
					'title'    => 'Technical SEO',
					'text'     => 'Makes every page crawlable and fast.',
					'url'      => '/services/technical-seo/',
				],
				[
					'relation' => 'Feeds',
					'title'    => 'AI Search',
					'text'     => 'Schema and clean HTML that AI tools can read.',
					'url'      => '/services/ai-search-optimization/',
				],
				[
					'relation' => 'Supports',
					'title'    => 'Paid ads',
					'text'     => 'Landing pages that load quickly on mobile.',
					'url'      => '/services/paid-ads/',
				],
				[
					'relation' => 'Converts',
					'title'    => 'Lead generation',
					'text'     => 'Forms and pages that work on every device.',
					'url'      => '/services/lead-generation/',
				],
				[
					'relation' => 'Tracks',
					'title'    => 'AI automation',
					'text'     => 'Clean data from site to CRM.',
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
							'title'   => 'How a technical SEO project *runs*',
							'style'   => 'numbered',
							'bare'    => true,
							'columns' => 2,
							'items'   => [
								[
									'title' => 'Crawl',
									'text'  => 'A full crawl plus Search Console and speed data.',
								],
								[
									'title' => 'Prioritize',
									'text'  => 'Every issue ranked by impact and effort.',
								],
								[
									'title' => 'Fix',
									'text'  => 'Changes shipped in your CMS or with your developers.',
								],
								[
									'title' => 'Monitor',
									'text'  => 'Monthly checks so new issues are caught early.',
								],
							],
						]
					),
				],
				[
					$td_b(
						'testimonial',
						[
							'quote'   => 'The clarity they brought to our strategy saved us months of trial and error — and probably a lot of money too…',
							'name'    => 'Akin Oyegoke',
							'role'    => 'CEO',
							'company' => 'Johan Consults',
							'image'   => 'people/akin-oyegoke-johan-consults',
							'logo'    => 'clients/johan-consults',
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
			'title' => 'Technical SEO *questions*',
			'items' => [
				[
					'question' => 'What is technical SEO?',
					'answer'   => 'Technical SEO is the work that lets search engines crawl, index and understand your site: speed, URL structure, internal links, redirects and structured data. It doesn’t replace content, but content can’t rank well without it.',
				],
				[
					'question' => 'Do you make the fixes, or just report them?',
					'answer'   => 'We make them. In WordPress and Webflow we usually ship fixes ourselves; on custom builds we write tickets your developers can act on and check each one after release.',
				],
				[
					'question' => 'Will technical fixes improve rankings on their own?',
					'answer'   => 'Sometimes, when an issue was blocking pages from being indexed. More often they remove a ceiling, so your content and links can do their job. We tell you which kind of fix each one is.',
				],
				[
					'question' => 'Can you help with a site migration?',
					'answer'   => 'Yes. We map every old URL to its new home, set one-hop redirects, and monitor Search Console daily after launch.',
				],
				[
					'question' => 'How long does a technical SEO audit take?',
					'answer'   => 'It depends on the size of the site. We confirm the timeline in your scoping email. [CONFIRM typical turnaround]',
				],
			],
		]
	),
	$td_b(
		'cta-dark',
		[
			'title'       => 'Fix what *blocks* ranking',
			'lead'        => 'Get a technical SEO audit with every issue ranked by impact.',
			'buttonLabel' => 'Get a technical SEO audit',
			'buttonUrl'   => '/growth-audit/',
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
