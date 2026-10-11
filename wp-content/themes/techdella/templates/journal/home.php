<?php
/**
 * Journal home (prototype/Blog.dc.html): featured guide, latest posts with
 * category filters, newsletter, then the archive list. Page 2+ is a plain grid.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

get_header();

$td_paged = max( 1, (int) get_query_var( 'paged' ) );

if ( $td_paged > 1 ) :
	?>
	<header class="td-container td-section" style="padding-bottom:40px">
		<?php
		echo td_section_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			[
				'label' => __( 'Journal', 'techdella' ),
				/* translators: %d: page number */
				'title' => sprintf( __( 'All posts, *page %d*', 'techdella' ), $td_paged ),
				'level' => 1,
			]
		);
		?>
	</header>
	<?php
	$td_grid_label = __( 'Posts', 'techdella' );
	require __DIR__ . '/loop-cards.php';
	get_footer();
	return;
endif;

// Featured = newest sticky post, else the newest post (same rule as the post-featured block).
$td_sticky   = array_map( 'intval', (array) get_option( 'sticky_posts', [] ) );
$td_newest   = get_posts(
	[
		'post_type'           => 'post',
		'posts_per_page'      => 1,
		'fields'              => 'ids',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	]
);
$td_featured = $td_sticky ? get_posts(
	[
		'post_type'      => 'post',
		'post__in'       => $td_sticky,
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	]
) : $td_newest;
// Skip the featured post in the grid only when it is also the newest post.
$td_offset  = $td_featured && $td_newest && (int) $td_featured[0] === (int) $td_newest[0] ? 1 : 0;
$td_ppp     = max( 10, (int) get_option( 'posts_per_page' ) );
$td_grid_n  = 6;
$td_archive = $td_ppp - $td_offset - $td_grid_n;
?>
<h1 class="td-sr"><?php esc_html_e( 'TechDella Journal: search, AI and building better products', 'techdella' ); ?></h1>
<?php
echo td_block( 'post-featured', [ 'label' => __( 'Featured guide', 'techdella' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'post-grid',
	[
		'title'   => __( 'Notes on getting *found*', 'techdella' ),
		'filters' => true,
		'count'   => $td_grid_n,
		'offset'  => $td_offset,
	]
);
echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'newsletter',
	[
		'title' => __( 'One useful search insight, *every two weeks*', 'techdella' ),
		'intro' => __( 'No fluff. Unsubscribe anytime.', 'techdella' ),
	]
);
if ( $td_archive > 0 ) {
	global $wp_query;
	echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'post-grid',
		[
			'layout'    => 'list',
			'title'     => __( 'From the *archive*', 'techdella' ),
			'count'     => $td_archive,
			'offset'    => $td_offset + $td_grid_n,
			'moreLabel' => $wp_query->max_num_pages > 1 ? __( 'Older posts', 'techdella' ) : '',
			'moreUrl'   => $wp_query->max_num_pages > 1 ? (string) get_pagenum_link( 2 ) : '',
		]
	);
}
get_footer();
