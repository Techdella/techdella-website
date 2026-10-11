<?php
/**
 * Title: Journal write for us
 * Slug: techdella/journal-write-for-us
 * Description: /write-for-us/: what we publish, editorial rules (guest links are nofollow or sponsored, §8.1), the pitch form (GUEST_POST endpoint), FAQ.
 * Inserter: no
 * TD Site: journal
 * TD Post Type: page
 * TD Slug: write-for-us
 * TD Title: Write for us
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

$td_blocks = [
	[
		'hero',
		[
			'eyebrow'      => 'Write for us',
			'title'        => 'Write for the *Journal*',
			'lead'         => 'We publish a small number of guest posts from practitioners with first-hand experience in search, paid, lead generation, email and automation. Every post is edited by our team.',
			'primaryLabel' => 'Pitch a post',
			'primaryUrl'   => '#pitch',
			'layout'       => 'center',
			'background'   => 'plain',
		],
	],
	[
		'feature-grid',
		[
			'label' => 'What we publish',
			'title' => 'Experience over *opinions*',
			'style' => 'icon',
			'items' => [
				[
					'title' => 'First-hand work',
					'text'  => 'What you did, what happened and what you would change. Screenshots and real numbers, shared with permission.',
					'icon'  => 'check',
				],
				[
					'title' => 'Our topics',
					'text'  => 'SEO, AI search, paid ads, lead generation, email and marketing automation for B2B teams.',
					'icon'  => 'list',
				],
				[
					'title' => 'Useful the same week',
					'text'  => 'Readers should be able to act on the post without buying anything.',
					'icon'  => 'target',
				],
			],
		],
	],
	[
		'feature-grid',
		[
			'label'   => 'Editorial rules',
			'title'   => 'How we *edit*',
			'style'   => 'checks',
			'lead'    => 'We keep editorial control of everything on the Journal.',
			'surface' => 'canvas',
			'items'   => [
				[
					'title' => 'We edit every post.',
					'text'  => 'Expect questions, cuts and a fact check before anything goes live.',
				],
				[
					'title' => 'Links are nofollow or sponsored.',
					'text'  => 'Links in guest posts carry rel="nofollow", or rel="sponsored" where there is any commercial relationship.',
				],
				[
					'title' => 'Original work only.',
					'text'  => 'No previously published posts, and no AI drafts without your own experience and review.',
				],
				[
					'title' => 'No paid placements.',
					'text'  => 'We do not sell guest posts or links. [CONFIRM]',
				],
			],
		],
	],
	[
		'form',
		[
			'formKey' => 'guest_post',
			'anchor'  => 'pitch',
		],
	],
	[
		'faq',
		[
			'title' => 'Guest post *questions*',
			'items' => [
				[
					'question' => 'How long until I hear back?',
					'answer'   => 'An editor replies to every pitch within two weeks.',
				],
				[
					'question' => 'Can I include a link to my company?',
					'answer'   => 'Yes, one link in your author bio. Like every link in a guest post, it is nofollow or sponsored.',
				],
				[
					'question' => 'Do you pay for guest posts?',
					'answer'   => 'No. [CONFIRM]',
				],
				[
					'question' => 'How long should a post be?',
					'answer'   => 'As long as it needs to be to answer the question well. Most run 1,200 to 2,000 words.',
				],
			],
		],
	],
];

foreach ( $td_blocks as $td_block ) {
	echo get_comment_delimited_block_content( 'techdella/' . $td_block[0], $td_block[1], '' ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- serialized block markup; attributes JSON-encoded by core.
}
