<?php
/**
 * Title: Studios service: UI/UX design
 * Slug: techdella/studios-service-ui-ux-design
 * Description: studio_service page (Studios artboard pattern): hero, deliverables, process, packages, work, FAQ, CTA. Primary keyword to validate: UI/UX design agency.
 * Inserter: no
 * TD Site: studios
 * TD Post Type: studio_service
 * TD Slug: ui-ux-design
 * TD Title: UI/UX design
 * TD Description: UI/UX design agency for web apps, dashboards and SaaS: user flows, wireframes and polished screens from TechDella Studios, tested with users before build.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

$td_blocks = [
	[
		'hero',
		[
			'parentLabel'    => 'Studios',
			'parentUrl'      => '/',
			'eyebrow'        => 'UI/UX design',
			'title'          => 'UI/UX design that makes your product *obvious*',
			'lead'           => 'Flows, wireframes and polished screens for web apps, dashboards and SaaS products, tested with real users before they are built.',
			'primaryLabel'   => 'Start a project',
			'primaryUrl'     => '/start/',
			'secondaryLabel' => 'See packages',
			'secondaryUrl'   => '#packages',
			'image'          => 'stock/designer-drawing-tablet-studio',
			'imageAlt'       => 'A designer sketching an interface on a drawing tablet',
			'layout'         => 'split',
			'background'     => 'grid',
		],
	],
	[
		'service-switcher',
		[
			'label'   => 'Studio services',
			'items'   => [
				[
					'label' => 'MVP development',
					'url'   => '/services/mvp-development/',
				],
				[
					'label' => 'AI automation',
					'url'   => '/services/ai-automation/',
				],
				[
					'label' => 'UI/UX design',
					'url'   => '/services/ui-ux-design/',
				],
				[
					'label' => 'Website design',
					'url'   => '/services/website-design/',
				],
				[
					'label' => 'Branding',
					'url'   => '/services/branding/',
				],
				[
					'label' => 'Graphic design',
					'url'   => '/services/graphic-design/',
				],
				[
					'label' => 'Motion design',
					'url'   => '/services/motion-design/',
				],
			],
			'current' => '/services/ui-ux-design/',
		],
	],
	[
		'feature-grid',
		[
			'anchor'    => 'deliverables',
			'label'     => 'Deliverables',
			'title'     => 'What you *get*',
			'style'     => 'icon',
			'columns'   => 3,
			'highlight' => -2,
			'items'     => [
				[
					'title' => 'User flows',
					'text'  => 'How each type of user gets from first screen to done.',
					'icon'  => 'lines',
				],
				[
					'title' => 'Wireframes',
					'text'  => 'Low-fidelity layouts to agree structure before detail.',
					'icon'  => 'list',
				],
				[
					'title' => 'Polished screens',
					'text'  => 'Final UI for desktop and mobile, ready for developers.',
					'icon'  => 'target',
				],
				[
					'title' => 'Clickable prototype',
					'text'  => 'A prototype you can put in front of users and investors.',
					'icon'  => 'chat',
				],
				[
					'title' => 'Design system',
					'text'  => 'Reusable components and tokens, so new screens stay consistent.',
					'icon'  => 'nodes',
				],
			],
		],
	],
	[
		'feature-grid',
		[
			'anchor'  => 'process',
			'label'   => 'Process',
			'title'   => 'How it *runs*',
			'style'   => 'numbered',
			'surface' => 'canvas',
			'items'   => [
				[
					'title' => 'Brief',
					'text'  => 'Who uses the product, and what they need to get done.',
				],
				[
					'title' => 'Flows',
					'text'  => 'We map the journeys and agree priorities.',
				],
				[
					'title' => 'Wireframes',
					'text'  => 'Structure first, reviewed with you.',
				],
				[
					'title' => 'UI',
					'text'  => 'Polished screens and a clickable prototype.',
				],
				[
					'title' => 'Handover',
					'text'  => 'Files, components and notes for your developers.',
				],
			],
		],
	],
	[
		'pricing-cards',
		[
			'anchor'     => 'packages',
			'label'      => 'Packages',
			'title'      => 'Clear scope, clear *price*',
			'background' => 'paper',
			'intro'      => 'Every package lists deliverables, duration, revisions and exclusions. Prices are confirmed in your scope.',
			'cards'      => [
				[
					'featured'    => true,
					'badge'       => 'Start here',
					'kicker'      => 'Duration [CONFIRM] weeks · [CONFIRM] revision rounds',
					'title'       => 'UI/UX Design Sprint',
					'text'        => 'Flows, wireframes and polished screens.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Get a scope',
					'buttonUrl'   => '/start/',
				],
				[
					'kicker'      => 'Duration [CONFIRM] weeks · [CONFIRM] revision rounds',
					'title'       => 'Design system',
					'text'        => 'Components and tokens for a product that keeps growing.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Get a scope',
					'buttonUrl'   => '/start/',
				],
			],
		],
	],
	[
		'case-grid',
		[
			'source'  => 'studio_work',
			'variant' => 'cover',
			'count'   => 3,
			'title'   => 'Recent *work*',
			'intro'   => 'Concept projects are labelled Concept.',
			'surface' => 'canvas',
		],
	],
	[
		'faq',
		[
			'title'      => 'UI/UX design *questions*',
			'items'      => [
				[
					'question' => 'Which design tools do you use?',
					'answer'   => 'We design in Figma and hand over organized files your developers can work from. [CONFIRM]',
				],
				[
					'question' => 'Can you redesign an existing product?',
					'answer'   => 'Yes. We start by reviewing what users struggle with today, then redesign those flows first.',
				],
				[
					'question' => 'Do you build what you design?',
					'answer'   => 'We can. Our MVP development team builds from the same files, so nothing gets lost in handover.',
				],
				[
					'question' => 'How many revisions are included?',
					'answer'   => 'Each package lists its revision rounds before we start. [CONFIRM rounds]',
				],
			],
			'background' => 'paper',
		],
	],
	[
		'cta-dark',
		[
			'title'       => 'Have an idea? Let’s make it *real*',
			'lead'        => 'Send a short brief. We’ll reply with a scope and an estimate.',
			'buttonLabel' => 'Start a project',
			'buttonUrl'   => '/start/',
			'variant'     => 'sand',
		],
	],
];

foreach ( $td_blocks as $td_block ) {
	echo get_comment_delimited_block_content( 'techdella/' . $td_block[0], $td_block[1], '' ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- serialized block markup; attributes JSON-encoded by core.
}
