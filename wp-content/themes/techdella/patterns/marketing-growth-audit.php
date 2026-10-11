<?php
/**
 * Title: Marketing · Growth audit
 * Slug: techdella/marketing-growth-audit
 * Categories: techdella
 * Description: Growth audit page (prototype Audit): hero with the audit request form, what's included + fee + quote, FAQ.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: page
 * TD Slug: growth-audit
 * TD Title: Growth audit
 * TD Description: Request a growth audit: a one-time marketing audit of your search and AI visibility, paid ads, funnel and follow-up, with a prioritized plan to act on.
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
	$td_b(
		'hero',
		[
			'eyebrow'        => 'Growth Audit',
			'title'          => 'See where your growth is *leaking*',
			'lead'           => 'A growth audit is a one-time marketing audit of your search visibility, AI visibility, paid ads, funnel and follow-up. You get a prioritized plan your team can act on immediately, and a walkthrough call to go through it.',
			'primaryLabel'   => '',
			'secondaryLabel' => 'Prefer to talk first? Book a call',
			'secondaryUrl'   => '/book-a-discovery-call/',
		],
		[
			$td_b(
				'form',
				[
					'anchor'  => 'request',
					'formKey' => 'audit',
					'title'   => 'Request your audit',
					'intro'   => 'Takes two minutes. Nothing is billed until we confirm fit.',
					'variant' => 'light',
				]
			),
		]
	),
	$td_section(
		[
			$td_cols(
				[
					$td_b(
						'feature-grid',
						[
							'title' => 'What’s *included*',
							'lead'  => 'Fixed fee: {{price:growth_audit}} · delivered in 10 business days.',
							'style' => 'checks',
							'bare'  => true,
							'items' => [
								[
									'title' => 'Technical SEO review:',
									'text'  => 'indexing, structure, speed, on-page.',
								],
								[
									'title' => 'AI search check:',
									'text'  => 'how often you’re mentioned for buyer prompts.',
								],
								[
									'title' => 'Paid ads & funnel review:',
									'text'  => 'Google, Meta, landing pages, email follow-up.',
								],
								[
									'title' => 'Tracking check:',
									'text'  => 'analytics, conversions and CRM source data.',
								],
								[
									'title' => 'Prioritized roadmap:',
									'text'  => 'every fix ranked by impact and effort, plus a walkthrough call with your team.',
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
				],
				55
			),
		],
		'canvas'
	),
	$td_b(
		'feature-grid',
		[
			'title' => 'What happens *next*',
			'intro' => 'After you send the form, three things happen.',
			'style' => 'numbered',
			'items' => [
				[
					'title' => 'We confirm fit',
					'text'  => 'We review your site and confirm the audit is a good fit.',
				],
				[
					'title' => 'You get a scope',
					'text'  => 'A short scoping email with the fee and timeline.',
				],
				[
					'title' => 'We deliver & walk through',
					'text'  => 'We deliver the audit and walk your team through the roadmap.',
				],
			],
		]
	),
	$td_b(
		'faq',
		[
			'title' => 'Common *questions*',
			'items' => [
				[
					'question' => 'Who is this for?',
					'answer'   => 'Established B2B SaaS and professional service firms with an existing website, content and marketing budget.',
				],
				[
					'question' => 'Do you guarantee rankings or AI citations?',
					'answer'   => 'No. Nobody honestly can. We guarantee a clear diagnosis and a roadmap built on evidence.',
				],
				[
					'question' => 'What happens after the audit?',
					'answer'   => 'Implement it yourselves, or work with us in a fixed-scope Growth Sprint.',
				],
				[
					'question' => 'What access do you need?',
					'answer'   => 'Read access to Google Search Console and GA4, and to your ad accounts if you run ads. We confirm the list in the scoping email and never need passwords to your website.',
				],
			],
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
