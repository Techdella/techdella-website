<?php
/**
 * Journal category archive: the category as H1 with filter chips, its posts
 * (post-grid block, offset by page), the newsletter, pagination.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

get_header();

$td_term  = get_queried_object();
$td_paged = max( 1, (int) get_query_var( 'paged' ) );
$td_ppp   = max( 1, (int) get_option( 'posts_per_page' ) );
$td_name  = $td_term instanceof WP_Term ? $td_term->name : '';

echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'post-grid',
	[
		'label'      => td_posts_hub_label(),
		'title'      => $td_paged > 1
			/* translators: 1: category name, 2: page number */
			? sprintf( __( '%1$s, page %2$d', 'techdella' ), $td_name, $td_paged )
			: $td_name,
		'titleLevel' => 1,
		'filters'    => true,
		'count'      => $td_ppp,
		'offset'     => ( $td_paged - 1 ) * $td_ppp,
	]
);

$td_desc = $td_term instanceof WP_Term ? trim( (string) $td_term->description ) : '';
if ( '' !== $td_desc || $GLOBALS['wp_query']->max_num_pages > 1 ) :
	?>
	<div class="td-container" style="padding-bottom:48px">
		<?php if ( '' !== $td_desc ) : ?>
			<p class="td-lead"><?php echo esc_html( $td_desc ); ?></p>
		<?php endif; ?>
		<?php
		the_posts_pagination(
			[
				'mid_size'  => 1,
				'prev_text' => __( 'Newer posts', 'techdella' ),
				'next_text' => __( 'Older posts', 'techdella' ),
			]
		);
		?>
	</div>
	<?php
endif;

echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'newsletter',
	[
		'title' => __( 'One useful search insight, *every two weeks*', 'techdella' ),
		'intro' => __( 'No fluff. Unsubscribe anytime.', 'techdella' ),
	]
);
get_footer();
