<?php
/**
 * Case card: one case_study / studio_work post, or the inline fallback item.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/lib.php';

$td_variant = 'cover' === ( $attributes['variant'] ?? '' ) ? 'cover' : 'card';
$td_post_id = (int) ( $attributes['postId'] ?? 0 );
$td_case    = $td_post_id ? td_a3_case_from_post( $td_post_id ) : null;
if ( ! $td_case && ! $td_post_id && ! empty( $attributes['item'] ) && is_array( $attributes['item'] ) ) {
	$td_case = td_a3_case_normalize( $attributes['item'], ! empty( $attributes['item']['status'] ) ? 'studio_work' : 'case_study' );
}
if ( ! $td_case ) {
	echo td_a3_notice( __( 'Case card: no published case study with confirmed permission (or work item) found for this ID.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div <?php echo td_block_wrapper( 'td-case-card-block' . ( ! empty( $attributes['contained'] ) ? ' td-container' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo td_a3_case_card( $td_case, $td_variant ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts. ?>
</div>
