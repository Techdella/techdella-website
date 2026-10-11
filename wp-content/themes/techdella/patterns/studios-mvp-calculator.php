<?php
/**
 * Title: Studios MVP calculator (shell)
 * Slug: techdella/studios-mvp-calculator
 * Description: /mvp-calculator/ page shell. The calculator itself is Phase 7 and needs a spec ([CONFIRM calculator spec]); until then the page offers the contact form, not a fake estimate.
 * Inserter: no
 * TD Site: studios
 * TD Post Type: page
 * TD Slug: mvp-calculator
 * TD Title: MVP cost calculator
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
			'lead'           => 'Our MVP cost calculator is being rebuilt. Until it is back, tell us about your idea and the Studios team will reply with a scoped estimate.',
			'primaryLabel'   => 'Ask for an estimate',
			'primaryUrl'     => '#estimate',
			'secondaryLabel' => 'See MVP packages',
			'secondaryUrl'   => '/services/mvp-development/#packages',
			'layout'         => 'center',
			'background'     => 'grid',
		],
	],
	[
		'feature-grid',
		[
			'label' => 'What drives the cost',
			'title' => 'Five things that shape an *estimate*',
			'intro' => '[CONFIRM calculator spec: inputs, pricing rules, and whether the estimate is emailed via the MVP_CALC endpoint.]',
			'style' => 'numbered',
			'items' => [
				[
					'title' => 'Project type',
					'text'  => 'Web app, SaaS product, internal tool or prototype.',
				],
				[
					'title' => 'Core features',
					'text'  => 'Accounts, payments, dashboards, integrations and admin tools.',
				],
				[
					'title' => 'Platforms',
					'text'  => 'Web only, or web plus mobile.',
				],
				[
					'title' => 'Design',
					'text'  => 'Whether you already have designs, or need UI/UX first.',
				],
				[
					'title' => 'Timeline',
					'text'  => 'How soon you need a first version in users’ hands.',
				],
			],
		],
	],
	[
		'form',
		[
			'formKey' => 'contact',
			'anchor'  => 'estimate',
			'title'   => 'Get an estimate from the team',
			'intro'   => 'Describe the product in a few sentences. We reply with a scope and an estimate.',
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
