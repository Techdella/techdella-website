<?php
/**
 * Title: Studios home
 * Slug: techdella/studios-home
 * Description: Studios front page (prototype/Studios.dc.html): hero, built for, services, photo cards, work, team, process, packages, CTA.
 * Inserter: no
 * TD Site: studios
 * TD Post Type: page
 * TD Slug: home
 * TD Title: TechDella Studios
 * TD Front Page: yes
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

$td_blocks = [
	[
		'studio-hero',
		[
			'chip'           => 'Apps · AI automation · Design · Motion',
			'title'          => 'From idea to something *real*',
			'lead'           => 'We design, build and automate digital products. Clearly scoped, milestone-based, and made with care.',
			'primaryLabel'   => 'Start a project',
			'primaryUrl'     => '/start/',
			'secondaryLabel' => 'Explore our work',
			'secondaryUrl'   => '#work',
			'screens'        => [
				[
					'title' => 'startbuddi',
					'image' => 'work/startbuddi-website-phone',
					'alt'   => 'The startbuddi.com website on a phone',
					'note'  => '[CONFIRM Studios build]',
				],
				[
					'title'   => 'Bookings',
					'kind'    => 'dashboard',
					'concept' => true,
				],
				[
					'title'   => 'Automations',
					'kind'    => 'toggle',
					'concept' => true,
				],
			],
		],
	],
	[
		'built-for',
		[
			'label' => 'Built for',
			'items' => [ 'Bootstrapped founders', 'Small & medium businesses', 'Agencies & marketing teams' ],
		],
	],
	[
		'feature-grid',
		[
			'anchor'    => 'services',
			'label'     => 'Services',
			'title'     => 'One studio, *six* crafts',
			'intro'     => 'Design, engineering, automation and motion under one roof, so nothing gets lost between teams.',
			'style'     => 'icon',
			'columns'   => 3,
			'highlight' => 0,
			'items'     => [
				[
					'title' => 'Apps & MVPs',
					'text'  => 'Web apps, SaaS products, internal tools and prototypes.',
					'icon'  => 'list',
					'url'   => '/services/mvp-development/',
				],
				[
					'title' => 'AI agents & automation',
					'text'  => 'Assistants, CRM workflows, support automation.',
					'icon'  => 'nodes',
					'url'   => '/services/ai-automation/',
				],
				[
					'title' => 'UI/UX & product',
					'text'  => 'Interfaces, prototypes, dashboards, design systems.',
					'icon'  => 'target',
					'url'   => '/services/ui-ux-design/',
				],
				[
					'title' => 'Websites',
					'text'  => 'Marketing sites and landing pages, built to load fast and rank.',
					'icon'  => 'search',
					'url'   => '/services/website-design/',
				],
				[
					'title' => 'Graphics & brand',
					'text'  => 'Identity, campaign creative, presentations.',
					'icon'  => 'person',
					'url'   => '/services/branding/',
				],
				[
					'title' => 'Motion & video',
					'text'  => 'Explainers, demos, launch films, animated ads.',
					'icon'  => 'chart',
					'url'   => '/services/motion-design/',
				],
			],
		],
	],
	[
		'photo-cards',
		[
			'image' => 'stock/designer-drawing-tablet-studio',
			'title' => 'Build faster, and *finally* ship it properly',
			'intro' => 'What makes working with us different:',
			'cards' => [
				[
					'title'     => 'Fixed scope before we start',
					'icon'      => 'scope',
					'linkLabel' => 'See packages',
					'url'       => '#packages',
				],
				[
					'title' => 'Milestone-based payments',
					'icon'  => 'milestone',
				],
				[
					'title' => 'Human-reviewed AI builds',
					'icon'  => 'review',
				],
			],
		],
	],
	[
		'case-grid',
		[
			'anchor'  => 'work',
			'source'  => 'studio_work',
			'variant' => 'cover',
			'count'   => 6,
			'title'   => 'Selected *work*',
			'intro'   => 'Apps · Automation · Design · Motion. Concept projects are labelled Concept.',
		],
	],
	[
		'arch-people',
		[
			'anchor'         => 'team',
			'title'          => 'Where ideas get *built*',
			'intro'          => 'A small, senior team of strategists, designers and builders who work directly with you.',
			'primaryLabel'   => 'Start a project',
			'primaryUrl'     => 'studios:/start/',
			'secondaryLabel' => 'Contact us',
			'secondaryUrl'   => 'marketing:/book-a-discovery-call/',
			'source'         => 'auto',
			'people'         => [
				[
					'name'  => 'Joanna Okedara-Kalu',
					'role'  => 'Founder & CEO',
					'image' => 'team/joanna-okedara-kalu',
				],
				[
					'name'  => 'Chinedu Emmanuel Kalu',
					'role'  => 'COO & Co-founder',
					'image' => 'team/chinedu-emmanuel-kalu',
				],
				[
					'name'  => 'Abel Saka',
					'role'  => 'Brand Designer & UI/UX',
					'image' => 'team/abel-saka',
				],
				[
					'name'  => 'Akeem Feysupo',
					'role'  => 'Growth Strategist',
					'image' => 'team/akeem-feysupo',
				],
				[
					'name'  => 'Omolola Akiyode',
					'role'  => 'TechDella Team',
					'image' => 'team/omolola-akiyode',
				],
				[
					'name'  => 'Mercy Olumakinwa',
					'role'  => 'TechDella Team',
					'image' => 'team/mercy-olumakinwa',
				],
			],
			'asideTitle'     => 'Quickly expand your team.',
			'asideText'      => 'Bring us in for a single project, or as an ongoing creative and technical partner.',
			'asideCards'     => [
				[
					'tag'   => 'Founders',
					'title' => 'Build an MVP',
					'url'   => 'studios:/services/mvp-development/',
				],
				[
					'tag'   => 'Agencies',
					'title' => 'White-label delivery',
					'url'   => 'studios:/start/',
					'dark'  => true,
				],
			],
		],
	],
	[
		'feature-grid',
		[
			'anchor' => 'process',
			'label'  => 'Process',
			'title'  => 'How a project runs. *No surprises.*',
			'style'  => 'numbered',
			'items'  => [
				[
					'title' => 'Brief',
					'text'  => 'A short structured brief.',
				],
				[
					'title' => 'Scope & estimate',
					'text'  => 'A package, or paid discovery for larger builds.',
				],
				[
					'title' => 'Deposit & kickoff',
					'text'  => 'Milestones and revisions agreed upfront.',
				],
				[
					'title' => 'Build & review',
					'text'  => 'Engineering review, testing and QA.',
				],
				[
					'title' => 'Launch & handover',
					'text'  => 'Your code, files and access, documented.',
				],
			],
		],
	],
	[
		'pricing-cards',
		[
			'anchor' => 'packages',
			'label'  => 'Packages',
			'title'  => "Clear packages,\n*transparent* pricing",
			'intro'  => 'Every package lists deliverables, duration, revisions and exclusions. Larger builds start with paid discovery.',
			'cards'  => [
				[
					'featured'    => true,
					'kicker'      => 'Duration [CONFIRM] weeks · [CONFIRM] revision rounds',
					'badge'       => 'Start here',
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
				[
					'kicker'      => 'Duration [CONFIRM] weeks · Testing + handover',
					'title'       => 'AI Automation Setup',
					'text'        => 'Automate a defined workflow end to end.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Start',
					'buttonUrl'   => '/start/',
				],
				[
					'kicker'      => 'Duration [CONFIRM] weeks · [CONFIRM] revision rounds',
					'title'       => 'UI/UX Design Sprint',
					'text'        => 'Flows, wireframes and polished screens.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Start',
					'buttonUrl'   => '/start/',
				],
				[
					'kicker'      => 'Length [CONFIRM] seconds · [CONFIRM] revision rounds',
					'title'       => 'Motion Graphics Package',
					'text'        => 'Explainers, product demos and animated ads.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Start',
					'buttonUrl'   => '/start/',
				],
				[
					'kicker'      => '[CONFIRM] assets · [CONFIRM] revision rounds',
					'title'       => 'Creative Asset Package',
					'text'        => 'Campaign graphics, social assets and presentations.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Start',
					'buttonUrl'   => '/start/',
				],
			],
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
