<?php
/**
 * Title: Journal newsletter
 * Slug: techdella/journal-newsletter
 * Description: /newsletter/ on the Journal: what subscribers get, the signup (NEWSLETTER endpoint), latest posts.
 * Inserter: no
 * TD Site: journal
 * TD Post Type: page
 * TD Slug: newsletter
 * TD Title: Newsletter
 * TD Description: Get the TechDella newsletter: one practical idea on SEO, AI search and B2B growth every two weeks, written by the team that does the work. Free to join.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

$td_blocks = [
	[
		'hero',
		[
			'eyebrow'    => 'Newsletter',
			'title'      => 'The TechDella *newsletter*',
			'lead'       => 'One useful insight on search, AI search and B2B growth, every two weeks. Written by the people who do the work.',
			'layout'     => 'center',
			'background' => 'plain',
		],
	],
	[
		'feature-grid',
		[
			'label' => 'What you get',
			'title' => 'Short, specific, *useful*',
			'style' => 'rule',
			'align' => 'left',
			'items' => [
				[
					'title' => 'One insight per issue',
					'text'  => 'What changed in Google or AI search, and what it means for a B2B team.',
				],
				[
					'title' => 'Something to use',
					'text'  => 'A checklist, template or teardown you can apply the same week.',
				],
				[
					'title' => 'New guides first',
					'text'  => 'Our latest Journal posts, before they are shared anywhere else.',
				],
			],
		],
	],
	[
		'newsletter',
		[
			'anchor' => 'subscribe',
			'title'  => 'One useful search insight, *every two weeks*',
			'intro'  => 'No fluff. Unsubscribe anytime.',
			'fields' => [ 'email', 'first_name' ],
		],
	],
	[
		'post-grid',
		[
			'title' => 'Latest from the *Journal*',
			'count' => 3,
		],
	],
];

foreach ( $td_blocks as $td_block ) {
	echo get_comment_delimited_block_content( 'techdella/' . $td_block[0], $td_block[1], '' ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- serialized block markup; attributes JSON-encoded by core.
}
