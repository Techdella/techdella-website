<?php
/**
 * Title: Studios MVP calculator
 * Slug: techdella/studios-mvp-calculator
 * Description: /mvp-calculator/: a live MVP cost range from the Studios prices (Growth → Prices), emailed through the MVP_CALC form (owner, 12 Oct 2026).
 * Inserter: no
 * TD Site: studios
 * TD Post Type: page
 * TD Slug: mvp-calculator
 * TD Title: MVP cost calculator
 * TD Description: Free MVP cost calculator: pick your platform, features and timeline to see a price range and weeks to launch, based on TechDella Studios' published prices.
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
			'eyebrow'        => 'MVP calculator',
			'title'          => 'What will your MVP *cost*?',
			'lead'           => 'Pick what your first version needs and see a price range and timeline, based on our published Studios prices. Then get the breakdown by email.',
			'primaryLabel'   => 'Start the calculator',
			'primaryUrl'     => '#calculator',
			'secondaryLabel' => 'See MVP packages',
			'secondaryUrl'   => '/services/mvp-development/#packages',
			'layout'         => 'center',
			'background'     => 'grid',
		],
	],
	[
		'tool-mvp-calculator',
		[],
	],
	[
		'feature-grid',
		[
			'label' => 'What drives the cost',
			'title' => 'Five things that shape an *estimate*',
			'intro' => 'The calculator starts from our MVP Discovery & Prototype and MVP Build prices, then adjusts for these.',
			'style' => 'numbered',
			'items' => [
				[
					'title' => 'Prototype or MVP',
					'text'  => 'A clickable prototype to test the idea, or a working first version built in milestones.',
				],
				[
					'title' => 'Platforms',
					'text'  => 'A web app, iOS and Android apps, or both. Native mobile apps add build time.',
				],
				[
					'title' => 'Core features',
					'text'  => 'Payments, roles, dashboards, messaging, bookings, integrations and AI each add to the build.',
				],
				[
					'title' => 'Design',
					'text'  => 'End-to-end UI/UX from us, or ready-to-build screens from your team.',
				],
				[
					'title' => 'Timeline',
					'text'  => 'A standard pace, or a larger team to get a first version out sooner.',
				],
			],
		],
	],
	[
		'form',
		[
			'formKey' => 'mvp_calc',
			'anchor'  => 'estimate',
		],
	],
	[
		'core/spacer',
		[ 'height' => '96px' ],
		'<div style="height:96px" aria-hidden="true" class="wp-block-spacer"></div>',
	],
];

foreach ( $td_blocks as $td_block ) {
	echo get_comment_delimited_block_content( str_contains( $td_block[0], '/' ) ? $td_block[0] : 'techdella/' . $td_block[0], $td_block[1], $td_block[2] ?? '' ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- serialized block markup; attributes JSON-encoded by core.
}
