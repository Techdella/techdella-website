<?php
/**
 * Post card: one published post (or a labelled example item).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/lib.php';

$td_id   = (int) ( $attributes['postId'] ?? 0 );
$td_post = $td_id ? td_a3_post_data( $td_id ) : null;
if ( ! $td_post && ! $td_id && ! empty( $attributes['item']['title'] ) ) {
	$td_post = td_a3_post_inline( (array) $attributes['item'] );
}
if ( ! $td_post ) {
	echo td_a3_notice( __( 'Post card: choose a published post.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div <?php echo td_block_wrapper( 'td-post-card-block' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php
	echo td_a3_post_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$td_post,
		[
			'tone' => (int) ( $attributes['tone'] ?? 0 ),
			'meta' => (string) ( $attributes['meta'] ?? 'read' ),
		]
	);
	?>
</div>
