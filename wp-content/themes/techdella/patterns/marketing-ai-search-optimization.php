<?php
/**
 * Title: Marketing · AI search optimization service
 * Slug: techdella/marketing-ai-search-optimization
 * Categories: techdella
 * Description: AI Search Optimization page (prototype AISearch): hero with AI answer mock, stats strip, how AI picks brands, what's included, connected services, process + testimonial, FAQ, CTA.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: service
 * TD Slug: ai-search-optimization
 * TD Title: AI Search Optimization
 * TD Description: AI search optimization for B2B brands. We make your company easy for ChatGPT, Perplexity, Gemini and Google AI Overviews to understand and recommend.
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
	$td_b( 'service-switcher', [ 'current' => '/services/ai-search-optimization/' ] ),
	$td_b(
		'hero',
		[
			'parentLabel'    => 'Services',
			'parentUrl'      => '/services/',
			'eyebrow'        => 'AI Search Optimization',
			'title'          => 'AI search optimization that gets you *recommended*',
			'lead'           => 'AI search optimization makes your brand easy for ChatGPT, Perplexity, Gemini and Google’s AI Overviews to understand, trust and mention. We do AEO and GEO for B2B companies: clear entity facts, answer-first pages and credible mentions, tracked across the prompts your buyers use.',
			'primaryLabel'   => 'Check my AI visibility',
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => 'What’s included',
			'secondaryUrl'   => '#included',
			'platformsLabel' => 'AI platforms we optimize for',
			'platforms'      => [
				[
					'name' => 'ChatGPT',
					'mark' => 'C',
					'slug' => 'chatgpt',
					'ink'  => true,
				],
				[
					'name' => 'Perplexity',
					'mark' => 'P',
					'slug' => 'perplexity',
				],
				[
					'name' => 'AI Overviews',
					'mark' => 'G',
					'slug' => 'google-ai-overviews',
				],
				[
					'name' => 'Gemini',
					'mark' => 'Ge',
					'slug' => 'gemini',
				],
				[
					'name' => 'Copilot',
					'mark' => 'Co',
					'slug' => 'copilot',
				],
			],
		],
		[
			$td_b(
				'ai-answer-mock',
				[
					'question' => 'Which firms help fintechs with data protection compliance?',
					'answer'   => 'Firms frequently recommended for fintech compliance include *YourCo*[1], known for NDPA and GDPR programs, and two others…',
					'sources'  => [
						[
							'label' => 'yourcompany.com',
							'own'   => true,
						],
						[ 'label' => 'industry directory' ],
						[ 'label' => 'news article' ],
					],
					'caption'  => 'Example AI answer',
					'variant'  => 'sand',
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
						[
							'title' => '20 [CONFIRM]',
							'text'  => 'buyer prompts tracked each month',
						],
						[
							'title' => '5',
							'text'  => 'AI platforms monitored',
						],
						[
							'title' => '1',
							'text'  => 'clear monthly visibility report',
						],
						[
							'title' => '0',
							'text'  => 'fake guarantees',
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
			'title' => 'How AI answers decide which brands to *mention*',
			'intro' => 'AI tools recommend brands they can describe with confidence. Four signals do most of the work.',
			'style' => 'rule',
			'items' => [
				[
					'title' => 'Clearly described',
					'text'  => 'Your site says plainly what you do, for whom, and where.',
				],
				[
					'title' => 'Mentioned elsewhere',
					'text'  => 'Credible directories, reviews and articles reference you.',
				],
				[
					'title' => 'Directly useful',
					'text'  => 'Your pages answer real buyer questions, answer-first.',
				],
				[
					'title' => 'Consistent',
					'text'  => 'Your name, services and details match everywhere.',
				],
			],
		]
	),
	$td_b(
		'feature-grid',
		[
			'anchor'  => 'included',
			'title'   => 'What’s included in *AEO & GEO*',
			'intro'   => 'Generative engine optimization built on solid SEO foundations.',
			'style'   => 'icon',
			'surface' => 'canvas',
			'columns' => 3,
			'items'   => [
				[
					'icon'  => 'search',
					'title' => 'AI visibility audit',
					'text'  => 'We test your buyers’ questions across AI platforms and map who gets cited, and why.',
				],
				[
					'icon'  => 'list',
					'title' => 'Prompt research & tracking',
					'text'  => 'We build your list of buyer prompts and track your share of mentions every month.',
				],
				[
					'icon'  => 'person',
					'tone'  => 'sand',
					'title' => 'Entity & brand clarity',
					'text'  => 'Schema, About pages and consistent profiles so AI knows exactly who you are.',
				],
				[
					'icon'  => 'lines',
					'title' => 'Answer-first content',
					'text'  => 'We restructure key pages so AI tools can quote them clearly.',
				],
				[
					'icon'  => 'chat',
					'title' => 'Digital PR & credible mentions',
					'text'  => 'Getting you listed and quoted in the sources AI tools trust.',
				],
				[
					'icon'  => 'chart',
					'tone'  => 'dark',
					'title' => 'AI visibility reporting',
					'text'  => 'Share of mentions, competitor comparison and next actions, monthly.',
				],
			],
		]
	),
	$td_b(
		'connected-services',
		[
			'title'   => 'AI search works better *connected*',
			'intro'   => 'AI tools lean on ranked pages and credible mentions. When buyers find you there, the rest of your system takes over.',
			'current' => '/services/ai-search-optimization/',
			'items'   => [
				[
					'relation' => 'Foundation',
					'title'    => 'SEO',
					'text'     => 'Ranked pages are what AI cites.',
					'url'      => '/services/seo/',
				],
				[
					'relation' => 'You are here',
					'title'    => 'AI Search',
					'text'     => 'Gets your brand recommended.',
					'url'      => '/services/ai-search-optimization/',
				],
				[
					'relation' => 'Reinforces',
					'title'    => 'Paid ads',
					'text'     => 'Retarget people who discovered you.',
					'url'      => '/services/paid-ads/',
				],
				[
					'relation' => 'Converts',
					'title'    => 'Lead generation',
					'text'     => 'Turn AI referrals into leads.',
					'url'      => '/services/lead-generation/',
				],
				[
					'relation' => 'Nurtures',
					'title'    => 'Email',
					'text'     => 'Keep researchers warm.',
					'url'      => '/services/email-marketing/',
				],
				[
					'relation' => 'Follows up',
					'title'    => 'AI automation',
					'text'     => 'Instant reply to every inquiry.',
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
							'title'   => 'How we get you *cited*',
							'style'   => 'numbered',
							'bare'    => true,
							'columns' => 2,
							'items'   => [
								[
									'title' => 'Benchmark',
									'text'  => 'Your share of mentions today.',
								],
								[
									'title' => 'Fix foundations',
									'text'  => 'Clarity, schema and answer-first pages.',
								],
								[
									'title' => 'Earn mentions',
									'text'  => 'Directories, PR and partner content.',
								],
								[
									'title' => 'Track & report',
									'text'  => 'Monthly visibility trend and next steps.',
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
			'title' => 'AI search *questions*',
			'items' => [
				[
					'question' => 'What are AEO and GEO?',
					'answer'   => 'Answer engine optimization and generative engine optimization. Both describe making your brand easy for AI tools to understand, trust and recommend.',
				],
				[
					'question' => 'Can you guarantee ChatGPT will mention us?',
					'answer'   => 'No. AI answers change from query to query. We improve the signals AI relies on and report your share of mentions honestly over time.',
				],
				[
					'question' => 'How do you measure AI visibility?',
					'answer'   => 'We track a fixed set of buyer prompts across AI platforms every month and report the share of answers that mention or cite you, alongside traffic and leads.',
				],
				[
					'question' => 'Do we still need SEO?',
					'answer'   => 'Yes. Many AI answers draw on ranked web pages, so strong SEO makes AI optimization more effective.',
				],
				[
					'question' => 'What is llms.txt, and do we need one?',
					'answer'   => 'llms.txt is a short plain-text summary of your site for AI tools: who you are, your services and key pages. It’s quick to add, and we include it in the foundations work.',
				],
			],
		]
	),
	$td_b(
		'cta-dark',
		[
			'title'       => 'Be the *answer*',
			'lead'        => 'Find out how often AI recommends you today, and what to fix first.',
			'buttonLabel' => 'Check my AI visibility',
			'buttonUrl'   => '/growth-audit/',
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
