<?php
/**
 * Title: Marketing · SEO service
 * Slug: techdella/marketing-seo
 * Categories: techdella
 * Description: SEO service page (prototype SEO): hero with SERP mock, proof strip, what's included, before/after, connected services, process + testimonial, FAQ, CTA.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: service
 * TD Slug: seo
 * TD Title: SEO
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
$td_div = static function ( string $block, string $css_class, array $inner, string $style = '' ): array {
	$base  = [
		'core/group'   => 'wp-block-group',
		'core/columns' => 'wp-block-columns',
		'core/column'  => 'wp-block-column',
	][ $block ];
	$open  = '<div class="' . esc_attr( trim( $base . ' ' . $css_class ) ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
	$attrs = $css_class ? [ 'className' => $css_class ] : [];
	return [
		'blockName'    => $block,
		'attrs'        => $attrs,
		'innerBlocks'  => $inner,
		'innerHTML'    => $open . '</div>',
		'innerContent' => array_merge( [ $open ], array_fill( 0, count( $inner ), null ), [ '</div>' ] ),
	];
};
// A full-width section with a container: optional surface (canvas|sand|dark) and inline style.
$td_section = static fn( array $inner, string $surface = '', string $style = '' ): array => $td_div(
	'core/group',
	trim( 'td-section ' . ( $surface ? 'td-section--' . $surface : '' ) ),
	[ $td_div( 'core/group', 'td-container', $inner ) ],
	$style
);
// Two columns (left, right); widths as flex-basis percentages.
$td_cols = static fn( array $left, array $right, int $left_pct = 58 ): array => $td_div(
	'core/columns',
	'td-cols',
	[
		$td_div( 'core/column', '', $left, 'flex-basis:' . $left_pct . '%' ),
		$td_div( 'core/column', '', $right, 'flex-basis:' . ( 100 - $left_pct ) . '%' ),
	]
);

$td_blocks = [
	$td_b( 'service-switcher', [ 'current' => '/services/seo/' ] ),
	$td_b(
		'hero',
		[
			'parentLabel'    => 'Services',
			'parentUrl'      => '/services/',
			'eyebrow'        => 'SEO',
			'title'          => 'B2B SEO services that turn *searches* into pipeline',
			'lead'           => 'Our B2B SEO services get your site ranked for the searches buyers make before they choose a provider. We fix the technical foundations, research the keywords that lead to sales, and write the pages and content that rank for them.',
			'primaryLabel'   => 'Get an SEO audit',
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => 'What’s included',
			'secondaryUrl'   => '#included',
			'platformsLabel' => 'Platforms & tools we work in',
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
				'serp-mock',
				[
					'query'   => 'data protection consultant for fintech',
					'results' => [
						[
							'rank'      => '#1',
							'url'       => 'yourcompany.com › services',
							'title'     => 'Data Protection Consulting for Fintech Companies',
							'text'      => 'NDPA, GDPR and PCI DSS compliance programs for growing…',
							'highlight' => true,
						],
						[
							'url'   => 'competitor.com',
							'title' => 'Compliance services | Home',
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
							'title' => '15,000',
							'text'  => 'monthly organic visitors for Paycape',
						],
						[
							'title' => '50 days',
							'text'  => 'to Paycape’s first result',
						],
						[
							'title' => '50%',
							'text'  => 'of Paycape’s conversions came from inbound',
						],
					],
				]
			),
		],
		'padding-block:8px 24px'
	),
	$td_b(
		'feature-grid',
		[
			'anchor'  => 'included',
			'title'   => 'What’s included in our *SEO services*',
			'intro'   => 'Everything a B2B site needs to rank for commercial searches, prioritized by impact.',
			'style'   => 'icon',
			'columns' => 3,
			'items'   => [
				[
					'icon'  => 'chart',
					'tone'  => 'canvas',
					'title' => 'Technical SEO',
					'text'  => 'Crawlability, indexing, Core Web Vitals, schema markup and site architecture, fixed in order of impact.',
					'url'   => '/services/technical-seo/',
				],
				[
					'icon'  => 'search',
					'title' => 'Keyword & intent research',
					'text'  => 'We find the searches that lead to sales, not just traffic, and map each one to the page that should rank for it.',
				],
				[
					'icon'  => 'lines',
					'title' => 'On-page optimization',
					'text'  => 'Titles, headings, copy and internal links written for search intent.',
				],
				[
					'icon'  => 'list',
					'tone'  => 'sand',
					'title' => 'Content strategy & writing',
					'text'  => 'Service pages, guides and comparison content that answer real buyer questions.',
				],
				[
					'icon'  => 'nodes',
					'title' => 'Site structure & internal links',
					'text'  => 'Topic clusters that help Google, and buyers, find your best pages.',
				],
				[
					'icon'  => 'target',
					'tone'  => 'dark',
					'title' => 'Rank & lead tracking',
					'text'  => 'Monthly reporting on rankings, organic traffic and the leads it brings.',
				],
			],
		]
	),
	$td_b(
		'before-after',
		[
			'title'      => 'Written for the *search*, not the org chart',
			'intro'      => 'Most B2B pages describe the company. Pages that rank describe the buyer’s problem in the words they search with.',
			'layout'     => 'split',
			'surface'    => 'canvas',
			'afterStyle' => 'paper',
			'strike'     => true,
			'before'     => [
				'label' => 'Before',
				'title' => 'Services | Home',
				'text'  => 'We offer a range of solutions for businesses of all sizes.',
			],
			'after'      => [
				'label' => 'After',
				'title' => 'Data Protection Consulting for Fintech | YourCo',
				'text'  => 'NDPA and GDPR compliance programs for growing fintech teams. Book a scoping call.',
			],
		]
	),
	$td_b(
		'connected-services',
		[
			'title'   => 'SEO works better *connected*',
			'intro'   => 'Your rankings feed AI answers, your keyword data sharpens Google Ads, and every organic visitor can become a lead.',
			'current' => '/services/seo/',
			'items'   => [
				[
					'relation' => 'You are here',
					'title'    => 'SEO',
					'text'     => 'Ranks your pages for buying searches.',
					'url'      => '/services/seo/',
				],
				[
					'relation' => 'Feeds',
					'title'    => 'AI Search',
					'text'     => 'Ranked pages become AI citations.',
					'url'      => '/services/ai-search-optimization/',
				],
				[
					'relation' => 'Shares data',
					'title'    => 'Paid ads',
					'text'     => 'Keyword insights guide Google Ads.',
					'url'      => '/services/paid-ads/',
				],
				[
					'relation' => 'Converts',
					'title'    => 'Lead generation',
					'text'     => 'Organic visitors get a reason to act.',
					'url'      => '/services/lead-generation/',
				],
				[
					'relation' => 'Reuses',
					'title'    => 'Email',
					'text'     => 'Articles become newsletter content.',
					'url'      => '/services/email-marketing/',
				],
				[
					'relation' => 'Follows up',
					'title'    => 'AI automation',
					'text'     => 'New leads routed in minutes.',
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
							'title'   => 'How an SEO engagement *runs*',
							'style'   => 'numbered',
							'bare'    => true,
							'columns' => 2,
							'items'   => [
								[
									'title' => 'Audit',
									'text'  => 'Technical, content and competitor review.',
								],
								[
									'title' => 'Roadmap',
									'text'  => 'Every fix and page ranked by impact.',
								],
								[
									'title' => 'Implement',
									'text'  => 'Fixes shipped, pages rewritten, content published.',
								],
								[
									'title' => 'Grow & report',
									'text'  => 'Monthly content, monitoring and reporting.',
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
			'title' => 'SEO *questions*',
			'items' => [
				[
					'question' => 'How long does SEO take to work?',
					'answer'   => 'Technical fixes can show impact within weeks; content usually takes a few months to compound. Paycape saw its first results in 50 days.',
				],
				[
					'question' => 'Do you guarantee first-page rankings?',
					'answer'   => 'No. Nobody controls Google’s rankings. We commit to a clear roadmap, quality work and transparent reporting.',
				],
				[
					'question' => 'What’s the difference between SEO and AI search optimization?',
					'answer'   => 'SEO gets your pages ranked on Google. AI search optimization gets your brand mentioned in AI answers. They share foundations, and we usually run them together.',
				],
				[
					'question' => 'Do you write the content?',
					'answer'   => 'Yes. Our team researches and writes service pages, guides and articles, and works with your experts for accuracy.',
				],
				[
					'question' => 'Can you work with our existing website?',
					'answer'   => 'Yes. We work in WordPress, Webflow and most modern CMSs. If you need a rebuild, TechDella Studios can help.',
				],
				[
					'question' => 'How do you report on SEO?',
					'answer'   => 'Every month you get rankings for your target keywords, organic traffic, and the leads organic visitors produced, with what we did and what comes next.',
				],
			],
		]
	),
	$td_b(
		'cta-dark',
		[
			'title'       => 'Rank for what *sells*',
			'lead'        => 'Get an SEO audit with a prioritized roadmap your team can act on.',
			'buttonLabel' => 'Get an SEO audit',
			'buttonUrl'   => '/growth-audit/',
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
