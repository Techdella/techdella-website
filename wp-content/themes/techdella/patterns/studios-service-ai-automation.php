<?php
/**
 * Title: Studios service: AI automation
 * Slug: techdella/studios-service-ai-automation
 * Description: studio_service page (Studios artboard pattern): hero, deliverables, process, packages, work, FAQ, CTA. Primary keyword to validate: AI automation agency.
 * Inserter: no
 * TD Site: studios
 * TD Post Type: studio_service
 * TD Slug: ai-automation
 * TD Title: AI automation
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
			'eyebrow'        => 'AI automation',
			'title'          => 'AI automation for the work your team *repeats*',
			'lead'           => 'We design and build AI assistants and workflows that connect your forms, CRM, inbox and support tools, with a person in the loop where it matters.',
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
			'current' => '/services/ai-automation/',
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
					'title' => 'Workflow map',
					'text'  => 'The process as it runs today, and where automation saves real time.',
					'icon'  => 'lines',
				],
				[
					'title' => 'Assistants and agents',
					'text'  => 'AI assistants for support, sales or operations, connected to your data.',
					'icon'  => 'chat',
				],
				[
					'title' => 'CRM and tool integrations',
					'text'  => 'Forms, CRM, email, Slack and the tools you already use, connected.',
					'icon'  => 'nodes',
				],
				[
					'title' => 'Human handoff',
					'text'  => 'Clear rules for when the assistant stops and a person takes over.',
					'icon'  => 'person',
				],
				[
					'title' => 'Testing and monitoring',
					'text'  => 'Test runs before launch, and logs your team can review after.',
					'icon'  => 'check',
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
					'text'  => 'Tell us which workflow costs you the most time.',
				],
				[
					'title' => 'Map',
					'text'  => 'We map the workflow and agree what to automate first.',
				],
				[
					'title' => 'Build',
					'text'  => 'We build the automation and connect your tools.',
				],
				[
					'title' => 'Test',
					'text'  => 'We test with real cases and tune the handoff rules.',
				],
				[
					'title' => 'Handover',
					'text'  => 'Training, documentation and access for your team.',
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
					'kicker'      => 'Duration [CONFIRM] weeks · Testing + handover',
					'title'       => 'AI Automation Setup',
					'text'        => 'Automate a defined workflow end to end.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Start',
					'buttonUrl'   => '/start/',
				],
				[
					'kicker'      => 'Duration [CONFIRM] weeks · [CONFIRM] revision rounds',
					'title'       => 'Support assistant',
					'text'        => 'An AI assistant for customer questions, with human handoff.',
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
			'title'      => 'AI automation *questions*',
			'items'      => [
				[
					'question' => 'Which tools do you work with?',
					'answer'   => 'Most CRMs, help desks, email platforms and Slack. Tell us your stack in the brief. [CONFIRM supported tools list]',
				],
				[
					'question' => 'Will the AI make mistakes?',
					'answer'   => 'It can. That is why every build includes test runs, handoff rules and logs, and a person reviews anything sensitive.',
				],
				[
					'question' => 'Is my data safe?',
					'answer'   => 'We only connect the data the workflow needs, and you keep ownership of every account. [CONFIRM data-processing terms]',
				],
				[
					'question' => 'Can you automate marketing too?',
					'answer'   => 'Yes. For lead routing and nurture, our marketing team runs AI marketing automation on techdella.com.',
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
