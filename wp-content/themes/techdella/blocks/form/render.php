<?php
/**
 * Form block: wraps td_form_render() from the core forms module (§11).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'td_form_render' ) ) {
	return;
}

$td_key = (string) ( $attributes['formKey'] ?? 'contact' );
if ( ! in_array( $td_key, td_forms_keys(), true ) ) {
	return;
}

$td_args = [
	'variant' => in_array( $attributes['variant'] ?? '', [ 'light', 'dark', 'inline', 'compact' ], true ) ? (string) $attributes['variant'] : 'light',
	'hidden'  => is_array( $attributes['hidden'] ?? null ) ? array_map( 'strval', array_filter( $attributes['hidden'], 'is_scalar' ) ) : [],
];
foreach ( [
	'title'          => 'title',
	'intro'          => 'intro',
	'submitLabel'    => 'submit_label',
	'successMessage' => 'success_message',
] as $td_attr => $td_arg ) {
	$td_value = trim( (string) ( $attributes[ $td_attr ] ?? '' ) );
	if ( '' !== $td_value ) {
		$td_args[ $td_arg ] = $td_value;
	}
}
if ( ! empty( $attributes['anchor'] ) ) {
	// The wrapper carries the anchor id; the form root gets a derived one.
	$td_args['id_prefix'] = (string) $attributes['anchor'] . '-form';
}
?>
<div <?php echo td_block_wrapper( 'td-form-block td-form-block--' . $td_args['variant'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo td_form_render( $td_key, $td_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by td_form_render(). ?>
</div>
