<?php
/**
 * Title: Journal write for us
 * Slug: techdella/journal-write-for-us
 * Description: /write-for-us/: guest posts, sponsored articles and link insertions (paid, owner 12 Oct 2026), editorial rules (paid links are rel="sponsored" and labelled, §8.1), the request form (GUEST_POST endpoint), FAQ.
 * Inserter: no
 * TD Site: journal
 * TD Post Type: page
 * TD Slug: write-for-us
 * TD Title: Write for us and sponsored posts
 * TD Description: Guest posts, sponsored articles and link insertions on the TechDella Journal: what we accept, how we label paid content and how to request rates.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

$td_blocks = [
	[
		'hero',
		[
			'eyebrow'        => 'Write for us',
			'title'          => 'Guest posts and *sponsored* content',
			'lead'           => 'Reach founders and B2B marketing teams who read the Journal for practical search, ads, email and automation advice. We accept guest posts, sponsored articles and link insertions that are useful to our readers, and we label every paid placement.',
			'primaryLabel'   => 'Request rates',
			'primaryUrl'     => '#pitch',
			'secondaryLabel' => 'Editorial rules',
			'secondaryUrl'   => '#rules',
			'layout'         => 'center',
			'background'     => 'plain',
		],
	],
	[
		'feature-grid',
		[
			'label'   => 'Options',
			'title'   => 'Three ways to *publish* with us',
			'intro'   => 'Rates depend on the format and the article. Send a request and we reply within two business days.',
			'style'   => 'tiles',
			'columns' => 3,
			'items'   => [
				[
					'title' => 'Guest post',
					'text'  => 'You write an original article from your own experience; our editors shape it to the Journal’s standard. One link to your site in the article or your author bio.',
					'tone'  => 'canvas',
				],
				[
					'title' => 'Sponsored article',
					'text'  => 'We write the article with you, from a brief and an interview, so it reads like the rest of the Journal. Clearly labelled as sponsored.',
					'tone'  => 'dark',
				],
				[
					'title' => 'Link insertion',
					'text'  => 'One relevant link added to an existing, published Journal article, where it genuinely helps the reader. Labelled as sponsored.',
					'tone'  => 'sand',
				],
			],
		],
	],
	[
		'feature-grid',
		[
			'anchor'  => 'rules',
			'label'   => 'Editorial rules',
			'title'   => 'Paid, but *labelled*',
			'style'   => 'checks',
			'lead'    => 'We keep editorial control of everything on the Journal and follow Google’s rules for paid links, so placements stay useful to readers and safe for your site.',
			'surface' => 'canvas',
			'items'   => [
				[
					'title' => 'Every paid placement is labelled.',
					'text'  => 'Sponsored articles, paid guest posts and link insertions show a “Sponsored” label and a short disclosure.',
				],
				[
					'title' => 'Paid links use rel="sponsored".',
					'text'  => 'Google asks sites to mark paid links this way. We do it on every paid link, so they don’t pass ranking credit.',
				],
				[
					'title' => 'Relevant topics only.',
					'text'  => 'Marketing, search, ads, email, automation, startups, product and software. We decline gambling, adult, CBD, payday lending and get-rich-quick offers.',
				],
				[
					'title' => 'We edit everything.',
					'text'  => 'Expect questions, cuts and a fact check. We don’t publish previously published posts or unreviewed AI drafts.',
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
			'title' => 'Placement *questions*',
			'items' => [
				[
					'question' => 'How much does a placement cost?',
					'answer'   => 'It depends on the format and, for link insertions, the article. Send a request and we reply with rates within two business days.',
				],
				[
					'question' => 'Will the link be dofollow?',
					'answer'   => 'No. Paid links carry rel="sponsored", as Google requires. You get readers who are interested in the topic, not ranking credit.',
				],
				[
					'question' => 'Will the post be marked as sponsored?',
					'answer'   => 'Yes. Every paid placement shows a “Sponsored” label and a disclosure line, so readers know.',
				],
				[
					'question' => 'Can I write an unpaid guest post?',
					'answer'   => 'Sometimes, when you bring first-hand experience our readers can’t get elsewhere. Choose “Guest post” in the form and tell us what you would share.',
				],
				[
					'question' => 'How long until it goes live?',
					'answer'   => 'We agree the publish date when we confirm the placement, after edits and payment.',
				],
			],
		],
	],
];

foreach ( $td_blocks as $td_block ) {
	echo get_comment_delimited_block_content( 'techdella/' . $td_block[0], $td_block[1], '' ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- serialized block markup; attributes JSON-encoded by core.
}
