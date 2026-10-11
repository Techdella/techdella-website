<?php
/**
 * Title: Marketing · Pricing
 * Slug: techdella/marketing-pricing
 * Categories: techdella
 * Description: Pricing / ways to start (Main "Ways to start" + Paid FIT + PRICING): hero, offers, paid ads fit and pricing, what every engagement includes, FAQ, CTA. All prices are [PRICE] until the owner supplies them.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: page
 * TD Slug: pricing
 * TD Title: Pricing
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
	$td_b(
		'hero',
		[
			'eyebrow'        => 'Pricing',
			'title'          => 'Clear ways to *start*',
			'lead'           => 'Most clients start with a one-time Growth Audit, then move to a fixed-scope Growth Sprint or monthly Growth Management. Every offer has defined deliverables, and there are no open-ended retainers.',
			'primaryLabel'   => 'Get a growth audit',
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => 'Book a call',
			'secondaryUrl'   => '/book-a-discovery-call/',
			'layout'         => 'center',
		]
	),
	$td_b(
		'pricing-cards',
		[
			'label' => 'Ways to start',
			'title' => "Start with an audit.\nGrow from *there*.",
			'intro' => 'Clear offers with defined deliverables. No open-ended retainers.',
			'cards' => [
				[
					'featured'    => true,
					'kicker'      => 'One-time',
					'badge'       => 'Start here',
					'title'       => 'Growth Audit',
					'text'        => 'Search, AI visibility, ads, funnel and tracking reviewed, with a prioritized plan.',
					'price'       => '[PRICE]',
					'buttonLabel' => 'Get started',
					'buttonUrl'   => '/growth-audit/',
				],
				[
					'kicker'      => 'Fixed scope',
					'title'       => 'Growth Sprint',
					'text'        => 'We implement the top priorities: SEO fixes, a campaign launch, an email sequence or an automation.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Talk to us',
					'buttonUrl'   => '/book-a-discovery-call/',
				],
				[
					'kicker'      => 'Monthly',
					'title'       => 'Growth Management',
					'text'        => 'Ongoing search, paid, email and automation work with contracted monthly deliverables.',
					'price'       => '[PRICE] / month',
					'buttonLabel' => 'Talk to us',
					'buttonUrl'   => '/book-a-discovery-call/',
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
							'title' => 'Is paid right for you *yet?*',
							'lead'  => 'We take on paid ads when we’re confident they’ll pay back. You’re a good fit if you have:',
							'style' => 'checks',
							'bare'  => true,
							'items' => [
								[
									'title' => 'A monthly ad budget',
									'text'  => 'of at least [MINIMUM SPEND]',
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
									'text'  => '[PRICE] one-time',
								],
								[
									'title' => 'One platform:',
									'text'  => '[PRICE] / month',
								],
								[
									'title' => 'Two or more platforms:',
									'text'  => '[PRICE] / month',
								],
								[
									'title' => 'Creative package:',
									'text'  => 'from [PRICE]',
								],
							],
						]
					),
					$td_b(
						'button',
						[
							'label'   => 'See paid ads',
							'url'     => '/services/paid-ads/',
							'variant' => 'secondary',
						]
					),
				],
				50
			),
		]
	),
	$td_b(
		'feature-grid',
		[
			'title'   => 'In every *engagement*',
			'intro'   => 'Whichever way you start, these come as standard.',
			'style'   => 'icon',
			'surface' => 'canvas',
			'columns' => 3,
			'items'   => [
				[
					'icon'  => 'chart',
					'title' => 'Shared dashboard',
					'text'  => 'Leads, cost per lead and revenue by channel, updated automatically.',
				],
				[
					'icon'  => 'chat',
					'title' => 'Monthly review call',
					'text'  => 'What we did, what it produced and what comes next.',
				],
				[
					'icon'  => 'check',
					'tone'  => 'dark',
					'title' => 'You own every account',
					'text'  => 'Ads, analytics, CRM and content stay yours if we ever part ways.',
				],
			],
		]
	),
	$td_b(
		'faq',
		[
			'title'      => 'Pricing *questions*',
			'background' => 'paper',
			'items'      => [
				[
					'question' => 'Why start with an audit?',
					'answer'   => 'Because it tells us both where the money should go. The audit ranks every opportunity by impact and effort, so a Sprint or monthly plan starts with the work most likely to pay back.',
				],
				[
					'question' => 'Is ad spend included in your fees?',
					'answer'   => 'No. Ad spend is paid directly to Google, Meta, TikTok or LinkedIn from your own accounts. Our fees cover strategy, creatives and management.',
				],
				[
					'question' => 'Is there a minimum commitment?',
					'answer'   => 'The audit and Sprints are one-off. Monthly management has a minimum term of [CONFIRM: minimum term] months, so campaigns and content have time to work.',
				],
				[
					'question' => 'Can we change scope later?',
					'answer'   => 'Yes. We review scope with you every month and agree any change in writing before it starts.',
				],
			],
		]
	),
	$td_b(
		'cta-dark',
		[
			'title'       => 'Start with *clarity*',
			'lead'        => 'Get a growth audit and a prioritized plan for what to fix first.',
			'buttonLabel' => 'Get a growth audit',
			'buttonUrl'   => '/growth-audit/',
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
