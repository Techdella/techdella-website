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
		'intro'      => trim(
			( $td_term instanceof WP_Term ? trim( (string) $td_term->description ) . ' ' : '' )
			/* translators: %s: number of articles */
			. ( $td_term instanceof WP_Term ? sprintf( _n( '%s article.', '%s articles.', (int) $td_term->count, 'techdella' ), number_format_i18n( (int) $td_term->count ) ) : '' )
		),
		'filters'    => true,
		'count'      => $td_ppp,
		'offset'     => ( $td_paged - 1 ) * $td_ppp,
	]
);

if ( $GLOBALS['wp_query']->max_num_pages > 1 ) :
	?>
	<div class="td-container td-post-grid" style="padding-block:0 48px">
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
