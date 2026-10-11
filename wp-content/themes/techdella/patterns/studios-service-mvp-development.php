<?php
/**
 * Title: Studios service: MVP development
 * Slug: techdella/studios-service-mvp-development
 * Description: studio_service page (Studios artboard pattern): hero, deliverables, process, packages, work, FAQ, CTA. Primary keyword to validate: MVP development agency.
 * Inserter: no
 * TD Site: studios
 * TD Post Type: studio_service
 * TD Slug: mvp-development
 * TD Title: MVP development
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
			'eyebrow'        => 'MVP development',
			'title'          => 'MVP development that gets you to a *working* product',
			'lead'           => 'We scope, design and build the first version of your web app or SaaS product in milestones, so you can put it in front of users and learn what to build next.',
			'primaryLabel'   => 'Start a project',
			'primaryUrl'     => '/start/',
			'secondaryLabel' => 'See packages',
			'secondaryUrl'   => '#packages',
			'layout'         => 'center',
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
			'current' => '/services/mvp-development/',
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
					'title' => 'Scoped plan',
					'text'  => 'User flows, feature list and what stays out of version one, agreed in writing.',
					'icon'  => 'list',
				],
				[
					'title' => 'Clickable prototype',
					'text'  => 'Key screens you can test with users before any code is written.',
					'icon'  => 'target',
				],
				[
					'title' => 'Working build',
					'text'  => 'A web app or SaaS product built in milestones, with engineering review at each one.',
					'icon'  => 'nodes',
				],
				[
					'title' => 'Testing and QA',
					'text'  => 'Every milestone is tested before you sign it off.',
					'icon'  => 'check',
				],
				[
					'title' => 'Handover',
					'text'  => 'Your code, accounts and documentation, so you own everything we build.',
					'icon'  => 'person',
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
					'text'  => 'Tell us the idea, the users and the deadline.',
				],
				[
					'title' => 'Discovery',
					'text'  => 'Paid discovery turns the idea into a scoped plan and prototype.',
				],
				[
					'title' => 'Milestones',
					'text'  => 'We agree what ships in each milestone and what it costs.',
				],
				[
					'title' => 'Build & review',
					'text'  => 'We build, test and review every milestone with you.',
				],
				[
					'title' => 'Launch & handover',
					'text'  => 'We launch, then hand over code, files and access.',
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
					'title'       => 'MVP Discovery & Prototype',
					'text'        => 'Validate the idea with a scoped plan and a clickable prototype.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Start',
					'buttonUrl'   => '/start/',
				],
				[
					'kicker'      => 'Duration [CONFIRM] weeks · Discovery first',
					'title'       => 'MVP Build',
					'text'        => 'A working first version, built in milestones.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Start',
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
			'title'      => 'MVP development *questions*',
			'items'      => [
				[
					'question' => 'How long does an MVP take?',
					'answer'   => 'It depends on scope. Discovery fixes the scope first, then we give you a timeline per milestone. [CONFIRM typical range]',
				],
				[
					'question' => 'Do I own the code?',
					'answer'   => 'Yes. You own the code, designs and accounts. We hand everything over at launch, documented.',
				],
				[
					'question' => 'Do you use AI to build?',
					'answer'   => 'We use AI tools to move faster, and an engineer reviews everything before it ships. No unreviewed AI code goes to production.',
				],
				[
					'question' => 'What if I already have designs?',
					'answer'   => 'Tell us in the brief. We review them in discovery and build from them where they work.',
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
