<?php
/**
 * Title: Studios service: Website design
 * Slug: techdella/studios-service-website-design
 * Description: studio_service page (Studios artboard pattern): hero, deliverables, process, packages, work, FAQ, CTA. Primary keyword to validate: website design agency.
 * Inserter: no
 * TD Site: studios
 * TD Post Type: studio_service
 * TD Slug: website-design
 * TD Title: Website design
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
			'eyebrow'        => 'Website design',
			'title'          => 'Website design that loads fast and *converts*',
			'lead'           => 'Marketing websites and landing pages, designed and built to explain what you do, load quickly and turn visitors into leads.',
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
			'current' => '/services/website-design/',
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
					'title' => 'Site structure',
					'text'  => 'Pages and navigation planned around what your buyers search for.',
					'icon'  => 'list',
				],
				[
					'title' => 'Design',
					'text'  => 'Desktop and mobile layouts on your brand.',
					'icon'  => 'target',
				],
				[
					'title' => 'Build',
					'text'  => 'A fast, accessible site on WordPress or another CMS you can edit. [CONFIRM platforms]',
					'icon'  => 'nodes',
				],
				[
					'title' => 'SEO foundations',
					'text'  => 'Clean URLs, metadata, schema and redirects from your old site.',
					'icon'  => 'search',
				],
				[
					'title' => 'Forms and tracking',
					'text'  => 'Lead forms connected to your CRM, and analytics that respect consent.',
					'icon'  => 'chart',
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
					'text'  => 'Goals, audience and the pages you need.',
				],
				[
					'title' => 'Structure',
					'text'  => 'Sitemap and wireframes, agreed before design.',
				],
				[
					'title' => 'Design',
					'text'  => 'Key page designs, reviewed with you.',
				],
				[
					'title' => 'Build & test',
					'text'  => 'Build, speed checks, accessibility checks and QA.',
				],
				[
					'title' => 'Launch',
					'text'  => 'Redirects, launch and handover.',
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
					'title'       => 'Landing page',
					'text'        => 'One focused page for a campaign or offer.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Start',
					'buttonUrl'   => '/start/',
				],
				[
					'kicker'      => 'Duration [CONFIRM] weeks · [CONFIRM] revision rounds',
					'title'       => 'Marketing website',
					'text'        => 'A multi-page site with CMS and SEO foundations.',
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
			'title'      => 'Website design *questions*',
			'items'      => [
				[
					'question' => 'Will I be able to edit the site?',
					'answer'   => 'Yes. We build on a CMS and show your team how to update text, images and pages.',
				],
				[
					'question' => 'Will I lose my Google rankings?',
					'answer'   => 'We map every old URL to its new home with a 301 redirect before launch, so search engines follow the move.',
				],
				[
					'question' => 'Do you write the copy?',
					'answer'   => 'We can. Our marketing team writes conversion and SEO copy. [CONFIRM: included or extra]',
				],
				[
					'question' => 'How fast will the site be?',
					'answer'   => 'We test every template against Core Web Vitals on mobile before launch.',
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
