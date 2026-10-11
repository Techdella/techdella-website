<?php
/**
 * Title: Marketing · Book a discovery call
 * Slug: techdella/marketing-book-a-discovery-call
 * Categories: techdella
 * Description: Contact page: hero, the Startbuddi booking calendar (booking-embed), the contact form for people who prefer to write (Startbuddi key "contact"), what happens next, a client quote. No popups on this URL (popups module).
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: page
 * TD Slug: book-a-discovery-call
 * TD Title: Book a discovery call
 * TD Description: Book a discovery call with TechDella. Tell us about your company and growth goals, and we'll come prepared with questions about your market and marketing.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

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

$td_blocks = [
	$td_b(
		'hero',
		[
			'eyebrow'    => 'Book a discovery call',
			'title'      => 'Tell us where you want to *grow*',
			'lead'       => 'Pick a time that suits you. We’ll come to the call with questions about your market, your current marketing and what you’ve already tried.',
			'layout'     => 'center',
			'background' => 'plain',
		]
	),
	$td_b(
		'booking-embed',
		[
			'src'        => 'https://app.startbuddi.com/b/startbuddi-team/discovery-call-with-techdella',
			'frameTitle' => 'Discovery Call with Techdella',
			'height'     => 760,
		]
	),
	$td_section(
		[
			$td_b(
				'form',
				[
					'anchor'  => 'message',
					'formKey' => 'contact',
					'title'   => 'Prefer to write?',
					'intro'   => 'Send us a message instead and we’ll reply by email.',
					'variant' => 'light',
				]
			),
		],
		'canvas'
	),
	$td_b(
		'feature-grid',
		[
			'label'   => 'What happens next',
			'title'   => 'From booking to *plan*',
			'style'   => 'numbered',
			'surface' => 'canvas',
			'columns' => 3,
			'items'   => [
				[
					'title' => 'Pick a time',
					'text'  => 'Choose a slot that suits you in the calendar above. Your booking is confirmed on the spot.',
				],
				[
					'title' => 'Discovery call',
					'text'  => 'A 45-minute call to understand your market, your buyers and your current marketing.',
				],
				[
					'title' => 'Clear next step',
					'text'  => 'We recommend where to start, usually a Growth Audit: {{price:growth_audit}}, delivered in 10 business days.',
				],
			],
		]
	),
	// The quote card sits in a narrow, centred container (the block is a card, not a full-bleed band).
	$td_div(
		'core/group',
		'td-section',
		[
			$td_div(
				'core/group',
				'td-container td-container--narrow',
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
			'title' => 'Before you *book*',
			'items' => [
				[
					'question' => 'Who is the call for?',
					'answer'   => 'B2B companies that want more qualified leads from search, paid, email or automation, and teams that need a product or site built by TechDella Studios.',
				],
				[
					'question' => 'Do I need to prepare anything?',
					'answer'   => 'No. If you have them, your website, the channels you use today and a rough monthly budget help us make the call useful.',
				],
				[
					'question' => 'Where do my details go?',
					'answer'   => 'Your answers go to our CRM so we can reply. We don’t add you to marketing emails unless you tick the consent box. See our privacy policy.',
				],
			],
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
