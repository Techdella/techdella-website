<?php
/**
 * Label chip block.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_text = trim( (string) ( $attributes['text'] ?? '' ) );
if ( '' === $td_text ) {
	return;
}
$td_variant = (string) ( $attributes['variant'] ?? 'label' );
$td_map     = [
	'label'     => 'td-chip',
	'upper'     => 'td-chip td-chip--upper',
	'dark'      => 'td-chip td-chip--dark',
	'neutral'   => 'td-tag',
	'highlight' => 'td-tag td-tag--highlight',
	'selected'  => 'td-tag td-tag--selected',
];
$td_class   = $td_map[ $td_variant ] ?? 'td-chip';
?>
<div <?php echo td_block_wrapper( 'td-label-chip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><span class="<?php echo esc_attr( $td_class ); ?>"><?php echo esc_html( $td_text ); ?></span></div>
