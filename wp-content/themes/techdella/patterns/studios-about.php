<?php
/**
 * Title: Studios about
 * Slug: techdella/studios-about
 * Description: /about/ on Studios: who we are, how we work, the team, the founder note (needs sign-off), CTA.
 * Inserter: no
 * TD Site: studios
 * TD Post Type: page
 * TD Slug: about
 * TD Title: About TechDella Studios
 * TD Description: TechDella Studios is the build side of TechDella. We design, build and automate digital products for founders, growing businesses and agencies.
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
			'eyebrow'        => 'About',
			'title'          => 'A small studio that *ships*',
			'lead'           => 'TechDella Studios is the build side of TechDella. We design, build and automate digital products for founders, growing businesses and agencies, with the same team that runs search and growth for our marketing clients.',
			'primaryLabel'   => 'Start a project',
			'primaryUrl'     => '/start/',
			'secondaryLabel' => 'See our work',
			'secondaryUrl'   => '/work/',
			'imageSet'       => [
				[
					'image' => 'team/joanna-okedara-kalu',
					'name'  => 'Joanna Okedara-Kalu',
					'role'  => 'Founder & CEO',
				],
				[
					'image' => 'team/chinedu-emmanuel-kalu',
					'name'  => 'Chinedu Emmanuel Kalu',
					'role'  => 'COO & Co-founder',
				],
			],
			'layout'         => 'split',
			'background'     => 'grid',
		],
	],
	[
		'feature-grid',
		[
			'label' => 'How we work',
			'title' => 'Clear scope, honest *timelines*',
			'style' => 'rule',
			'align' => 'left',
			'items' => [
				[
					'title' => 'Fixed scope before we start',
					'text'  => 'Deliverables, duration, revisions and exclusions are agreed in writing.',
				],
				[
					'title' => 'Milestone-based payments',
					'text'  => 'You pay per milestone, after you have reviewed it.',
				],
				[
					'title' => 'Human-reviewed AI builds',
					'text'  => 'We use AI tools to move faster. A person reviews everything before it ships.',
				],
				[
					'title' => 'You own everything',
					'text'  => 'Code, files and accounts are yours, handed over with documentation.',
				],
			],
		],
	],
	[
		'arch-people',
		[
			'title'          => 'The people you’ll *work* with',
			'intro'          => 'A small, senior team of strategists, designers and builders who work directly with you.',
			'primaryLabel'   => 'Start a project',
			'primaryUrl'     => 'studios:/start/',
			'secondaryLabel' => 'Contact us',
			'secondaryUrl'   => 'marketing:/book-a-discovery-call/',
			'source'         => 'auto',
		],
	],
	[
		'founder',
		[
			'label'      => 'From the founder',
			'title'      => 'Hi, I’m *Joanna*.',
			'body'       => "I started TechDella to help companies become easier to understand, easier to trust and easier to choose. Studios is where we build the products, sites and automations that make that possible.\n\nWe only promise what we can control: clear scope, quality work and honest timelines.",
			'stats'      => [
				[
					'value' => '11',
					'label' => 'Published case studies',
				],
				[
					'value' => '5',
					'label' => 'Client countries',
				],
			],
			'image'      => 'team/joanna-okedara-kalu',
			'imageAlt'   => 'Joanna Okedara-Kalu, Founder and CEO',
			'quote'      => 'Techdella reframed how we were thinking about our market entirely. We went from scattered tactics to a positioning and GTM system that actually made sense for where we were heading. The difference was immediate.',
			'quoteName'  => 'Levi Barbosa',
			'quoteRole'  => 'CEO, WezOps',
			'quoteImage' => 'people/levi-barbosa-wezops',
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
