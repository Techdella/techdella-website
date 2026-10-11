<?php
/**
 * Title: Studios service: Graphic design
 * Slug: techdella/studios-service-graphic-design
 * Description: studio_service page (Studios artboard pattern): hero, deliverables, process, packages, work, FAQ, CTA. Primary keyword to validate: graphic design services.
 * Inserter: no
 * TD Site: studios
 * TD Post Type: studio_service
 * TD Slug: graphic-design
 * TD Title: Graphic design
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
			'eyebrow'        => 'Graphic design',
			'title'          => 'Graphic design for campaigns, social and *sales*',
			'lead'           => 'Campaign graphics, social assets, presentations and documents, made on your brand and sized for every channel.',
			'primaryLabel'   => 'Start a project',
			'primaryUrl'     => '/start/',
			'secondaryLabel' => 'See packages',
			'secondaryUrl'   => '#packages',
			'image'          => 'stock/designer-desk-swatches-wide',
			'imageAlt'       => 'A designer working on a laptop and tablet with color swatches',
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
			'current' => '/services/graphic-design/',
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
					'title' => 'Campaign graphics',
					'text'  => 'Static ads and banners in every size the platforms need.',
					'icon'  => 'target',
				],
				[
					'title' => 'Social assets',
					'text'  => 'Posts, carousels and story templates your team can reuse.',
					'icon'  => 'list',
				],
				[
					'title' => 'Presentations',
					'text'  => 'Sales decks and pitch decks that are easy to read.',
					'icon'  => 'lines',
				],
				[
					'title' => 'Documents',
					'text'  => 'One-pagers, case study layouts and lead magnets.',
					'icon'  => 'check',
				],
				[
					'title' => 'Templates',
					'text'  => 'Editable files so your team can make the next ones.',
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
					'text'  => 'What it is for, where it runs and the deadline.',
				],
				[
					'title' => 'Concepts',
					'text'  => 'First designs for the key format.',
				],
				[
					'title' => 'Adapt',
					'text'  => 'We adapt the approved design to every size.',
				],
				[
					'title' => 'Revise',
					'text'  => 'Revisions within the agreed rounds.',
				],
				[
					'title' => 'Deliver',
					'text'  => 'Final files and editable templates.',
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
					'kicker'      => '[CONFIRM] assets · [CONFIRM] revision rounds',
					'title'       => 'Creative Asset Package',
					'text'        => 'Campaign graphics, social assets and presentations.',
					'price'       => 'From [PRICE]',
					'buttonLabel' => 'Start',
					'buttonUrl'   => '/start/',
				],
				[
					'kicker'      => '[CONFIRM] slides · [CONFIRM] revision rounds',
					'title'       => 'Pitch or sales deck',
					'text'        => 'A presentation designed around your story.',
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
			'title'      => 'Graphic design *questions*',
			'items'      => [
				[
					'question' => 'Do you write the copy for ads?',
					'answer'   => 'Our paid ads team writes ad copy and tests it. For design-only work, send us the copy in the brief.',
				],
				[
					'question' => 'Which sizes do you deliver?',
					'answer'   => 'Every size the brief lists, for Meta, LinkedIn, TikTok, Google and print.',
				],
				[
					'question' => 'Can my team edit the files?',
					'answer'   => 'Yes. We deliver editable templates along with the final files.',
				],
				[
					'question' => 'How quickly can you turn work around?',
					'answer'   => 'It depends on the package and queue. [CONFIRM turnaround]',
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
