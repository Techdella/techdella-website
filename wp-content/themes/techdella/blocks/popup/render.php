<?php
/**
 * Offer card (inline popup) block (§13.1 "inline end-of-post card").
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'td_popup_markup' ) || ! class_exists( '\TechDella\Core\Popups\Targeting' ) ) {
	return;
}

$td_popup = null;
$td_id    = sanitize_key( (string) ( $attributes['popupId'] ?? '' ) );
if ( '' !== $td_id && function_exists( 'td_popups' ) ) {
	foreach ( td_popups() as $td_candidate ) {
		if ( $td_candidate['id'] === $td_id && $td_candidate['enabled'] ) {
			$td_popup = $td_candidate;
		}
	}
}
if ( null === $td_popup ) {
	$td_resource = sanitize_title( (string) ( $attributes['resource'] ?? '' ) );
	$td_popup    = \TechDella\Core\Popups\Targeting::normalize(
		[
			'id'         => 'inline-' . ( '' !== $td_resource ? $td_resource : (string) ( $attributes['formKey'] ?? 'newsletter' ) ),
			'enabled'    => true,
			'type'       => 'inline',
			'site'       => td_site(),
			'offer_type' => '' !== $td_resource ? 'resource' : 'newsletter',
			'resource'   => $td_resource,
			'form_key'   => (string) ( $attributes['formKey'] ?? 'newsletter' ),
			'variants'   => [
				[
					'title'  => (string) ( $attributes['title'] ?? '' ),
					'text'   => (string) ( $attributes['text'] ?? '' ),
					'button' => (string) ( $attributes['buttonLabel'] ?? '' ),
				],
			],
		]
	);
}
$td_html = td_popup_markup( $td_popup, 'inline' );
if ( '' === $td_html ) {
	return;
}
?>
<div <?php echo td_block_wrapper( 'td-popup-block' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo $td_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_popup_markup(). ?></div>
