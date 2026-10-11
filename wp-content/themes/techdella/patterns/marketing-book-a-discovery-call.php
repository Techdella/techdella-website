<?php
/**
 * Title: Marketing · Book a discovery call
 * Slug: techdella/marketing-book-a-discovery-call
 * Categories: techdella
 * Description: Contact page: hero with the contact form (Startbuddi key "contact"), what happens next, a client quote. No popups on this URL (popups module).
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: page
 * TD Slug: book-a-discovery-call
 * TD Title: Book a discovery call
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

$td_b = static fn( string $name, array $attrs = [], array $inner = [] ): array => [
	'blockName'    => 'techdella/' . $name,
	'attrs'        => $attrs,
	'innerBlocks'  => $inner,
	'innerHTML'    => '',
	'innerContent' => array_fill( 0, count( $inner ), null ),
];

$td_blocks = [
	$td_b(
		'hero',
		[
			'eyebrow'    => 'Book a discovery call',
			'title'      => 'Tell us where you want to *grow*',
			'lead'       => 'Share a little about your company and goals. We’ll come to the call with questions about your market, your current marketing and what you’ve already tried.',
			'background' => 'plain',
		],
		[
			$td_b(
				'form',
				[
					'formKey' => 'contact',
					'variant' => 'light',
				]
			),
		]
	),
	$td_b(
		'feature-grid',
		[
			'label'   => 'What happens next',
			'title'   => 'From form to *plan*',
			'style'   => 'numbered',
			'surface' => 'canvas',
			'columns' => 3,
			'items'   => [
				[
					'title' => 'We reply',
					'text'  => 'We read your answers and reply by email to set a time. [CONFIRM reply time]',
				],
				[
					'title' => 'Discovery call',
					'text'  => 'A call to understand your market, your buyers and your current marketing. [CONFIRM call length]',
				],
				[
					'title' => 'Clear next step',
					'text'  => 'We recommend where to start, usually a Growth Audit, with defined deliverables and price. [CONFIRM]',
				],
			],
		]
	),
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
