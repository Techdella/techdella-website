<?php
/**
 * Button block: wraps td_button().
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_label = trim( (string) ( $attributes['label'] ?? '' ) );
if ( '' === $td_label ) {
	return;
}
$td_url     = (string) ( $attributes['url'] ?? '/' );
$td_url     = str_starts_with( $td_url, '/' ) ? home_url( $td_url ) : $td_url;
$td_variant = in_array( $attributes['variant'] ?? '', [ 'primary', 'secondary', 'light' ], true ) ? $attributes['variant'] : 'primary';
$td_align   = 'center' === ( $attributes['align'] ?? '' ) ? ' td-button-block--center' : '';
?>
<div <?php echo td_block_wrapper( 'td-button-block' . $td_align ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo td_button( $td_label, $td_url, $td_variant, [ 'size' => ( $attributes['size'] ?? 'md' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
