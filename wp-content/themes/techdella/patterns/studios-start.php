<?php
/**
 * Title: Studios start (project brief)
 * Slug: techdella/studios-start
 * Description: /start/ (prototype/Start.dc.html): the four-step project brief, posting to the Startbuddi STUDIOS_BRIEF endpoint.
 * Inserter: no
 * TD Site: studios
 * TD Post Type: page
 * TD Slug: start
 * TD Title: Start a project
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

$td_blocks = [
	[
		'hero',
		[
			'parentLabel' => 'Studios',
			'parentUrl'   => '/',
			'eyebrow'     => 'Project brief',
			'title'       => 'Start a *project*',
			'lead'        => 'Four quick questions. We’ll reply with a scope and an estimate.',
			'layout'      => 'center',
			'background'  => 'plain',
		],
	],
	[
		'form',
		[
			'formKey' => 'studios_brief',
			'anchor'  => 'brief',
			'title'   => 'Your brief',
			'intro'   => 'Takes about two minutes.',
		],
	],
	[
		'built-for',
		[
			'label' => 'Every project',
			'items' => [ 'Fixed scope before work starts', 'Milestone payments', 'You own the code and files' ],
		],
	],
];

foreach ( $td_blocks as $td_block ) {
	echo get_comment_delimited_block_content( 'techdella/' . $td_block[0], $td_block[1], '' ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- serialized block markup; attributes JSON-encoded by core.
}
