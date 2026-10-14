<?php
/**
 * Business library hub (/business/): every "How to start a … business" guide as a card grid.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/m2.php';

if ( is_paged() ) {
	wp_safe_redirect( (string) get_post_type_archive_link( 'business' ), 301 );
	exit;
}
td_m2_styles();

get_header();
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'hero',
	[
		'eyebrow'    => __( 'Business guides', 'techdella' ),
		'title'      => __( 'How to start a business that *lasts*', 'techdella' ),
		'lead'       => __( 'Step-by-step guides for founders in the US, UK and Nigeria: approvals, startup costs, pricing and how to win your first customers.', 'techdella' ),
		'layout'     => 'center',
		'background' => 'plain',
	]
);
td_m2_breadcrumbs();
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'post-grid',
	[
		'postType' => 'business',
		'title'    => __( 'All business *guides*', 'techdella' ),
		'count'    => 100,
		'surface'  => 'paper',
	]
);
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'cta-dark',
	[
		'title'       => __( 'Ready to build the *brand*?', 'techdella' ),
		'lead'        => __( 'TechDella Studios designs the brand, website and product for new businesses. Tell us what you are starting.', 'techdella' ),
		'buttonLabel' => __( 'Start a project brief', 'techdella' ),
		'buttonUrl'   => td_url( 'studios', '/start/' ),
		'arch'        => true,
	]
);
get_footer();
