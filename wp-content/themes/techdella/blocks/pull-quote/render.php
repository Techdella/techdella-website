<?php
/**
 * Pull quote block (article component). Verbatim quotes only (§2.4).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_q = trim( (string) ( $attributes['quote'] ?? '' ) );
if ( '' === $td_q ) {
	return;
}
$td_name = trim( (string) ( $attributes['name'] ?? '' ) );
$td_url  = trim( (string) ( $attributes['url'] ?? '' ) );
?>
<figure <?php echo td_block_wrapper( 'td-pullquote' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<blockquote class="td-pullquote__q"<?php echo '' !== $td_url && preg_match( '#^https?://#', $td_url ) ? ' cite="' . esc_url( $td_url ) . '"' : ''; ?>><p><?php echo esc_html( $td_q ); ?></p></blockquote>
	<?php if ( '' !== $td_name ) : ?>
		<figcaption class="td-pullquote__by">
			<?php echo '' !== $td_url ? td_lite_inline( '[' . $td_name . '](' . $td_url . ')' ) : esc_html( $td_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( ! empty( $attributes['role'] ) ) : ?>
				<span><?php echo esc_html( (string) $attributes['role'] ); ?></span>
			<?php endif; ?>
		</figcaption>
	<?php endif; ?>
</figure>
