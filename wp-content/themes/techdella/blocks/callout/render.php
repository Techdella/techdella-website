<?php
/**
 * Callout block (article component).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_v     = in_array( $attributes['variant'] ?? '', [ 'note', 'tip', 'warning', 'key' ], true ) ? (string) $attributes['variant'] : 'note';
$td_text  = trim( (string) ( $attributes['text'] ?? '' ) );
$td_title = trim( (string) ( $attributes['title'] ?? '' ) );
if ( '' === $td_text && '' === $td_title ) {
	return;
}
$td_names = [
	'note'    => __( 'Note', 'techdella' ),
	'tip'     => __( 'Tip', 'techdella' ),
	'warning' => __( 'Watch out', 'techdella' ),
	'key'     => __( 'Key point', 'techdella' ),
];
?>
<aside <?php echo td_block_wrapper( 'td-callout td-callout--' . $td_v ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $td_names[ $td_v ] ); ?>">
	<p class="td-callout__kicker"><span class="td-callout__mark" aria-hidden="true"></span><?php echo esc_html( $td_names[ $td_v ] ); ?></p>
	<?php if ( '' !== $td_title ) : ?>
		<p class="td-callout__title"><?php echo td_lite_inline( $td_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<?php endif; ?>
	<div class="td-callout__body"><?php echo td_lite( $td_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
</aside>
