<?php
/**
 * Title: Studios service: Branding
 * Slug: techdella/studios-service-branding
 * Description: studio_service page (Studios artboard pattern): hero, deliverables, process, packages, work, FAQ, CTA. Primary keyword to validate: branding agency.
 * Inserter: no
 * TD Site: studios
 * TD Post Type: studio_service
 * TD Slug: branding
 * TD Title: Branding
 * TD Description: Branding for startups and growing businesses: logo, visual identity and brand guidelines from TechDella Studios, made to work on your site and in ads.
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
			'eyebrow'        => 'Branding',
			'title'          => 'Branding that makes you easy to *recognize*',
			'lead'           => 'Logo, identity and brand guidelines for startups and growing businesses, built to work on a website, in ads and on a slide.',
			'primaryLabel'   => 'Start a project',
			'primaryUrl'     => '/start/',
			'secondaryLabel' => 'See packages',
			'secondaryUrl'   => '#packages',
			'image'          => 'stock/product-flatlay-notebook-candle-square',
			'imageAlt'       => 'A notebook and products laid out on a table',
			'imageShape'     => 'arch',
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
			'current' => '/services/branding/',
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
					'title' => 'Brand direction',
					'text'  => 'Positioning, personality and the look you are aiming for, agreed first.',
					'icon'  => 'target',
				],
				[
					'title' => 'Logo',
					'text'  => 'A primary logo, a mark and the variations you need.',
					'icon'  => 'person',
				],
				[
					'title' => 'Color and type',
					'text'  => 'A palette and type system that work on screen and in print.',
					'icon'  => 'lines',
				],
				[
					'title' => 'Brand guidelines',
					'text'  => 'A short guide your team and partners can follow.',
					'icon'  => 'list',
				],
				[
					'title' => 'Starter assets',
					'text'  => 'Social templates, a slide template and business essentials.',
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
					'text'  => 'Who you are for and how you want to be seen.',
				],
				[
					'title' => 'Direction',
					'text'  => 'Two or three directions to choose from.',
				],
				[
					'title' => 'Design',
					'text'  => 'We develop the chosen direction into a full identity.',
				],
				[
					'title' => 'Refine',
					'text'  => 'Revisions until it works everywhere it needs to.',
				],
				[
					'title' => 'Handover',
					'text'  => 'Files in every format, plus guidelines.',
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
					'kicker'      => '3–4 weeks · 2 revision rounds',
					'title'       => 'Brand identity',
					'text'        => 'Logo, color, type and guidelines.',
					'price'       => 'From {{price:brand_identity}}',
					'buttonLabel' => 'Get a scope',
					'buttonUrl'   => '/start/',
				],
				[
					'kicker'      => '2–3 weeks · 2 revision rounds',
					'title'       => 'Brand refresh',
					'text'        => 'An update to an existing identity, keeping what works.',
					'price'       => 'From {{price:brand_refresh}}',
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
			'title'      => 'Branding *questions*',
			'items'      => [
				[
					'question' => 'What files do I get?',
					'answer'   => 'Vector and web formats for every logo version, plus the guidelines as a PDF.',
				],
				[
					'question' => 'Can you work with our existing logo?',
					'answer'   => 'Yes. A brand refresh keeps what people already recognize and fixes what does not work.',
				],
				[
					'question' => 'Do you name companies?',
					'answer'   => 'Not as a standalone service. [CONFIRM]',
				],
				[
					'question' => 'Who owns the brand?',
					'answer'   => 'You do. Full rights transfer on final payment. [CONFIRM contract terms]',
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
