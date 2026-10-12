<?php
/**
 * Studios guides (/guides/, the posts page): plain-English guides on building, design and AI for
 * business owners. Featured guide, guides by topic, the archive, then a brief CTA. Page 2+ is a
 * plain grid (the Journal loop).
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

if ( max( 1, (int) get_query_var( 'paged' ) ) > 1 ) {
	require get_theme_file_path( 'templates/journal/home.php' );
	return;
}

get_header();
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'hero',
	[
		'eyebrow'        => __( 'Guides', 'techdella' ),
		'title'          => __( 'Build better products, in *plain English*', 'techdella' ),
		'lead'           => __( 'Straight answers on vibe coding, development, design and AI automation for business owners: what things cost, how long they take and what to ask before you hire.', 'techdella' ),
		'primaryLabel'   => __( 'Start a project', 'techdella' ),
		'primaryUrl'     => '/start/',
		'secondaryLabel' => __( 'MVP cost calculator', 'techdella' ),
		'secondaryUrl'   => '/mvp-calculator/',
		'layout'         => 'center',
		'background'     => 'grid',
	]
);
echo td_block( 'post-featured', [ 'label' => __( 'Start here', 'techdella' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'post-grid',
	[
		'title'   => __( 'Guides by *topic*', 'techdella' ),
		'filters' => true,
		'count'   => 9,
		'offset'  => 1,
	]
);
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'cta-dark',
	[
		'title'       => __( 'Ready to *build*?', 'techdella' ),
		'lead'        => __( 'Send a short brief. We reply with a scope, a timeline and a fixed price.', 'techdella' ),
		'buttonLabel' => __( 'Start a project', 'techdella' ),
		'buttonUrl'   => '/start/',
		'arch'        => true,
	]
);
get_footer();
