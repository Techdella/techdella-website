<?php
/**
 * Inline CTA block (article component).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_title = trim( (string) ( $attributes['title'] ?? '' ) );
$td_url   = trim( (string) ( $attributes['buttonUrl'] ?? '' ) );
if ( '' === $td_title || '' === $td_url ) {
	return;
}
if ( preg_match( '/^(marketing|journal|studios):(\/.*)$/', $td_url, $td_m ) ) {
	$td_url = td_url( $td_m[1], $td_m[2] );
} elseif ( str_starts_with( $td_url, '/' ) ) {
	$td_url = home_url( $td_url );
}
?>
<aside <?php echo td_block_wrapper( 'td-inlinecta' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $td_title ); ?>">
	<div class="td-inlinecta__copy">
		<p class="td-inlinecta__title"><?php echo td_accent( $td_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php if ( ! empty( $attributes['text'] ) ) : ?>
			<p class="td-inlinecta__text"><?php echo td_lite_inline( (string) $attributes['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
	</div>
	<?php echo td_button( (string) ( $attributes['buttonLabel'] ?? __( 'Talk to us', 'techdella' ) ), $td_url, 'light', [ 'size' => 'sm' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</aside>
