<?php
/**
 * Title: Marketing · Paid ads service
 * Slug: techdella/marketing-paid-ads
 * Categories: techdella
 * Description: Paid ads page (prototype Paid): hero with creative collage, strip, platform tabs, creative gallery, funnel, report, fit + pricing, FAQ, CTA.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: service
 * TD Slug: paid-ads
 * TD Title: Paid ads
 * TD Description: TechDella is a paid ads agency for Google, Meta, TikTok and LinkedIn. We plan strategy, make creatives in-house and manage campaigns for leads and revenue.
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

$td_blocks = [
	$td_b( 'service-switcher', [ 'current' => '/services/paid-ads/' ] ),
	$td_b(
		'hero',
		[
			'parentLabel'    => 'Services',
			'parentUrl'      => '/services/',
			'eyebrow'        => 'Paid ads',
			'title'          => 'Paid ads that *pay back*, on every platform',
			'lead'           => 'TechDella is a paid ads agency for Google Ads, Meta, TikTok and LinkedIn. We plan the strategy, make the creatives in-house, and manage every campaign against leads and revenue, not clicks.',
			'primaryLabel'   => 'Get a paid ads audit',
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => 'Compare platforms',
			'secondaryUrl'   => '#platforms',
			'platforms'      => [
				[
					'name' => 'Google Ads',
					'mark' => 'G',
					'slug' => 'google-ads',
					'ink'  => true,
				],
				[
					'name' => 'Meta Ads',
					'mark' => 'M',
					'slug' => 'meta',
				],
				[
					'name' => 'TikTok Ads',
					'mark' => 'T',
					'slug' => 'tiktok',
				],
				[
					'name' => 'LinkedIn Ads',
					'mark' => 'in',
					'slug' => 'linkedin',
				],
			],
		],
		[
			$td_b(
				'creative-gallery',
				[
					'layout' => 'collage',
					'note'   => 'Example creatives for illustration.',
					'items'  => [
						[
							'format'   => 'tiktok',
							'platform' => 'TikTok',
							'overlay'  => '3 signs your website is invisible to buyers',
							'headline' => '@yourco · Sponsored',
							'cta'      => 'Learn more',
							'image'    => 'stock/creator-recording-video-vertical',
						],
						[
							'format'   => 'linkedin',
							'platform' => 'LinkedIn',
							'brand'    => 'YourCo',
							'text'     => 'Is your brand showing up when buyers ask AI for recommendations? Get the free checklist.',
							'headline' => 'The AI Search Checklist',
							'footer'   => 'Free guide for B2B teams',
							'cta'      => 'Download',
							'image'    => 'stock/designer-desk-swatches-wide',
						],
						[
							'format'   => 'google-search',
							'platform' => 'Google',
							'domain'   => 'yourcompany.com',
							'headline' => 'SOC 2 Readiness in Weeks | Fixed Fee',
							'text'     => 'Gap assessment and audit support for growing SaaS teams.',
						],
						[
							'format'   => 'instagram',
							'platform' => 'Instagram',
							'brand'    => 'YourCo',
							'cta'      => 'Book a call',
							'image'    => 'stock/product-flatlay-notebook-candle-square',
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
					'columns' => 4,
					'items'   => [
						[ 'title' => '4 ad platforms, one plan' ],
						[ 'title' => 'Creatives made in-house with Studios' ],
						[ 'title' => 'Ad spend paid directly to platforms' ],
						[ 'title' => 'Weekly optimization and testing' ],
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
		'tabs',
		[
			'anchor'    => 'platforms',
			'title'     => 'The right platform for *each job*',
			'intro'     => 'Every platform does something different. We match each one to a clear job in your funnel.',
			'ariaLabel' => 'Ad platforms',
			'items'     => [
				[
					'label' => 'Google Ads',
					'job'   => 'Job: capture demand',
					'title' => 'Google Ads management',
					'text'  => 'Appear the moment someone searches for what you sell. The highest-intent traffic you can buy.',
					'facts' => [
						[
							'label' => 'Formats',
							'text'  => 'Search, Performance Max, YouTube, remarketing',
						],
						[
							'label' => 'Targeting',
							'text'  => 'Keywords, negatives, locations, audiences',
						],
						[
							'label' => 'Best for',
							'text'  => 'Services people already search for',
						],
						[
							'label' => 'Tracking',
							'text'  => 'Google Ads conversions + GA4',
						],
					],
					'mock'  => [
						'block'   => 'serp-mock',
						'query'   => 'soc 2 compliance consultant',
						'caption' => 'Example search ad',
						'results' => [
							[
								'sponsored' => true,
								'highlight' => true,
								'url'       => 'yourcompany.com',
								'title'     => 'SOC 2 Readiness in Weeks | Fixed Fee',
								'text'      => 'Gap assessment, policies and audit support. Book a free scoping call.',
								'sitelinks' => [ 'Pricing', 'Case studies', 'Book a call' ],
							],
						],
					],
				],
				[
					'label'    => 'Meta · Facebook & Instagram',
					'job'      => 'Job: create demand',
					'title'    => 'Meta ads agency',
					'subtitle' => 'Facebook & Instagram',
					'dark'     => true,
					'text'     => 'Reach the right people before they search, then bring back everyone who showed interest.',
					'facts'    => [
						[
							'label' => 'Formats',
							'text'  => 'Reels, Stories, feed, carousel, lead forms',
						],
						[
							'label' => 'Targeting',
							'text'  => 'Interests, lookalikes, retargeting',
						],
						[
							'label' => 'Best for',
							'text'  => 'Visual offers, lead magnets, retargeting',
						],
						[
							'label' => 'Tracking',
							'text'  => 'Meta Pixel + Conversions API',
						],
					],
					'mock'     => [
						'block'    => 'ad-mock',
						'format'   => 'meta',
						'brand'    => 'YourCo',
						'overlay'  => 'Audit-ready in weeks.',
						'headline' => 'SOC 2 checklist',
						'cta'      => 'Learn more',
						'image'    => 'stock/product-flatlay-notebook-candle-square',
					],
				],
				[
					'label' => 'TikTok Ads',
					'job'   => 'Job: reach new audiences with video',
					'title' => 'TikTok ads agency',
					'text'  => 'Native, sound-on short videos that stop the scroll. We script, film or animate, and test hooks every week.',
					'facts' => [
						[
							'label' => 'Formats',
							'text'  => 'In-feed video, Spark Ads, lead generation',
						],
						[
							'label' => 'Creative',
							'text'  => 'Hooks, UGC-style, motion graphics',
						],
						[
							'label' => 'Best for',
							'text'  => 'Consumer brands, apps, younger audiences',
						],
						[
							'label' => 'Tracking',
							'text'  => 'TikTok Pixel + Events API',
						],
					],
					'mock'  => [
						'block'    => 'ad-mock',
						'format'   => 'tiktok',
						'overlay'  => 'POV: your ads finally pay back',
						'headline' => '@yourco · Sponsored',
						'text'     => 'We tested 12 hooks. Here’s what worked.',
						'cta'      => 'Learn more',
						'image'    => 'stock/creator-recording-video-vertical',
					],
				],
				[
					'label' => 'LinkedIn Ads',
					'job'   => 'Job: reach decision-makers',
					'title' => 'LinkedIn ads agency',
					'dark'  => true,
					'text'  => 'Target by job title, company size and industry. The most precise B2B targeting available.',
					'facts' => [
						[
							'label' => 'Formats',
							'text'  => 'Sponsored content, document ads, lead gen forms',
						],
						[
							'label' => 'Targeting',
							'text'  => 'Job titles, seniority, company lists (ABM)',
						],
						[
							'label' => 'Best for',
							'text'  => 'High-value B2B services and SaaS',
						],
						[
							'label' => 'Tracking',
							'text'  => 'Insight Tag + CRM sync',
						],
					],
					'mock'  => [
						'block'    => 'ad-mock',
						'format'   => 'linkedin-document',
						'brand'    => 'YourCo',
						'text'     => 'Heads of Marketing: how many of your top 20 buyer prompts mention your brand? Download the AI search benchmark.',
						'headline' => 'AI Search Benchmark',
						'footer'   => 'Document ad',
						'cta'      => 'Download',
					],
				],
			],
		]
	),
	$td_b(
		'creative-gallery',
		[
			'label'   => 'Creative',
			'title'   => 'We make the *creatives* too',
			'intro'   => 'Scripts, video, motion, static and carousels, made by our Studios team and tested inside your campaigns.',
			'layout'  => 'gallery',
			'surface' => 'canvas',
			'filters' => true,
			'note'    => 'Example creatives for illustration.',
			'items'   => [
				[
					'size'     => 'tall',
					'platform' => 'Meta',
					'format'   => 'reel',
					'tag'      => 'Reel · 0:15',
					'headline' => '“We stopped guessing which leads to call first.”',
					'image'    => 'stock/creator-recording-video-vertical',
				],
				[
					'size'     => 'wide',
					'platform' => 'LinkedIn',
					'format'   => 'carousel',
					'tag'      => 'Carousel · 5 slides',
					'slides'   => [
						[
							'label' => '1/5',
							'title' => '5 SEO mistakes costing you leads',
						],
						[
							'label' => '2/5',
							'title' => 'Pages written for you, not buyers',
						],
						[
							'label' => '3/5',
							'title' => 'No answer to “how much?”',
						],
					],
				],
				[
					'platform' => 'TikTok',
					'format'   => 'motion',
					'tag'      => 'Motion · 0:06',
				],
				[
					'platform' => 'Google',
					'format'   => 'google-display',
					'headline' => 'Be the answer.',
					'cta'      => 'Audit',
				],
				[
					'platform' => 'Meta',
					'format'   => 'video',
					'tag'      => 'Product video',
					'image'    => 'stock/designer-drawing-tablet-studio',
				],
				[
					'type'    => 'link',
					'eyebrow' => 'Made with',
					'title'   => 'TechDella *Studios*',
					'cta'     => 'See creative services',
					'url'     => 'studios:/',
				],
			],
		]
	),
	$td_b(
		'video',
		[
			'heading' => 'Creative in *motion*',
			'intro'   => 'Three short ad samples, one per platform. Each is an example we made for a fictional brand, so you can see our approach without a client’s data.',
			'chip'    => 'Examples',
			'ratio'   => '9:16',
			'items'   => [
				[
					'scene'       => 'mo-04a-tiktok-hook',
					'title'       => 'TikTok hook ad (example for a fictional brand)',
					'description' => 'A 10-second TikTok ad sample: a one-second hook, the product in three steps, then the call to action. Made for the fictional brand Northwind.',
					'duration'    => 10,
					'caption'     => 'Example · TikTok: hook in the first second.',
				],
				[
					'scene'       => 'mo-04b-meta-carousel',
					'title'       => 'Meta carousel ad (example for a fictional brand)',
					'description' => 'A 10-second Meta carousel ad sample, one benefit per card. Made for the fictional brand Northwind.',
					'duration'    => 10,
					'caption'     => 'Example · Meta: one benefit per card.',
				],
				[
					'scene'       => 'mo-04c-linkedin-document',
					'title'       => 'LinkedIn document ad (example for a fictional brand)',
					'description' => 'A 10-second LinkedIn document ad sample that teaches before it sells. Made for the fictional brand Northwind.',
					'duration'    => 10,
					'caption'     => 'Example · LinkedIn: teach first, then ask.',
				],
			],
		]
	),
	$td_b(
		'funnel',
		[
			'title' => 'One funnel, every platform *in its place*',
			'intro' => 'Paid ads work best when they’re connected to search, landing pages, email and automation.',
			'items' => [
				[
					'stage' => 'Reach',
					'title' => 'Get seen',
					'text'  => 'Short video and social ads to new audiences.',
					'chips' => [ [ 'label' => 'TikTok' ], [ 'label' => 'Meta' ] ],
				],
				[
					'stage' => 'Target',
					'title' => 'Reach decision-makers',
					'text'  => 'Job-title and account targeting.',
					'chips' => [ [ 'label' => 'LinkedIn' ] ],
				],
				[
					'stage' => 'Capture',
					'title' => 'Win the search',
					'text'  => 'Be there when they’re ready to compare.',
					'chips' => [
						[ 'label' => 'Google Ads' ],
						[
							'label' => 'SEO',
							'url'   => '/services/seo/',
						],
					],
				],
				[
					'stage' => 'Convert',
					'title' => 'Turn clicks into leads',
					'text'  => 'Landing pages and in-platform lead forms.',
					'tone'  => 'sand',
					'chips' => [
						[
							'label' => 'Lead generation',
							'url'   => '/services/lead-generation/',
						],
					],
				],
				[
					'stage' => 'Nurture',
					'title' => 'Follow up automatically',
					'text'  => 'Email and AI automation hand warm leads to sales.',
					'tone'  => 'dark',
					'chips' => [
						[
							'label' => 'Email',
							'url'   => '/services/email-marketing/',
						],
						[
							'label' => 'AI automation',
							'url'   => '/services/marketing-automation/',
						],
					],
				],
			],
		]
	),
	$td_b(
		'report-mock',
		[
			'title'     => 'Everything managed, *everything* visible',
			'items'     => [
				[
					'title' => 'Setup & tracking',
					'text'  => 'Pixels, APIs, GA4, CRM',
				],
				[
					'title' => 'Strategy',
					'text'  => 'Budget split by platform',
				],
				[
					'title' => 'Creatives',
					'text'  => 'Video, static, copy',
				],
				[
					'title' => 'Weekly testing',
					'text'  => 'Hooks, audiences, bids',
				],
			],
			'cardTitle' => 'Paid ads report',
			'badge'     => 'Example report',
			'kpis'      => [
				[
					'label' => 'Spend',
					'value' => '$6,000',
				],
				[
					'label' => 'Leads',
					'value' => '120',
				],
				[
					'label'     => 'Cost per lead',
					'value'     => '$50',
					'highlight' => true,
				],
			],
			'barsLabel' => 'Leads by platform',
			'bars'      => [
				[
					'label' => 'Google',
					'value' => '54',
				],
				[
					'label' => 'LinkedIn',
					'value' => '30',
				],
				[
					'label' => 'Meta',
					'value' => '24',
				],
				[
					'label'     => 'TikTok',
					'value'     => '12',
					'highlight' => true,
				],
			],
			'note'      => 'Illustrative numbers only. Your report shows your live data.',
		]
	),
	$td_section(
		[
			$td_cols(
				[
					$td_b(
						'feature-grid',
						[
							'title' => 'Is paid right for you *yet?*',
							'lead'  => 'We take on paid ads when we’re confident they’ll pay back. You’re a good fit if you have:',
							'style' => 'checks',
							'bare'  => true,
							'items' => [
								[
									'title' => 'A monthly ad budget',
									'text'  => 'of at least $2,000',
								],
								[
									'title' => 'A clear offer',
									'text'  => 'people already want',
								],
								[
									'title' => 'Conversion tracking,',
									'text'  => 'or you’re happy for us to set it up',
								],
								[
									'title' => 'Someone to follow up',
									'text'  => 'leads quickly',
								],
							],
						]
					),
				],
				[
					$td_b(
						'feature-grid',
						[
							'title' => 'Paid ads *management*',
							'lead'  => 'Transparent pricing. Ad spend is paid directly to the platforms.',
							'style' => 'checks',
							'bare'  => true,
							'items' => [
								[
									'title' => 'Setup & tracking:',
									'text'  => '$1,000 one-time',
								],
								[
									'title' => 'One platform:',
									'text'  => '$1,000 / month',
								],
								[
									'title' => 'Two or more platforms:',
									'text'  => '$1,800 / month',
								],
								[
									'title' => 'Creative package:',
									'text'  => 'from $750',
								],
							],
						]
					),
					$td_b(
						'button',
						[
							'label'   => 'Check if you qualify',
							'url'     => '/growth-audit/',
							'variant' => 'primary',
						]
					),
				],
				50
			),
		],
		'canvas'
	),
	$td_b(
		'faq',
		[
			'title'      => 'Paid ads *questions*',
			'background' => 'paper',
			'items'      => [
				[
					'question' => 'Which platform should we start with?',
					'answer'   => 'If people already search for what you sell, start with Google Ads. For B2B decision-makers, LinkedIn. To build demand with visuals or video, Meta or TikTok. We recommend a mix in your audit.',
				],
				[
					'question' => 'Do you create the ads?',
					'answer'   => 'Yes. Copy, static design, video and motion are produced in-house with TechDella Studios, then tested inside your campaigns.',
				],
				[
					'question' => 'Do you guarantee results?',
					'answer'   => 'No honest agency can. We commit to clear tracking, steady testing and transparent reporting on cost per lead.',
				],
				[
					'question' => 'Who owns the ad accounts?',
					'answer'   => 'You do. We work inside your accounts, and your data stays yours if we ever part ways.',
				],
				[
					'question' => 'Is there a minimum commitment?',
					'answer'   => 'Three months, so campaigns have time to learn and improve.',
				],
			],
		]
	),
	$td_b(
		'cta-dark',
		[
			'title'       => 'Make every ad *count*',
			'lead'        => 'Tell us your offer and budget. We’ll tell you honestly which platforms will pay back.',
			'buttonLabel' => 'Get a paid ads audit',
			'buttonUrl'   => '/growth-audit/',
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
