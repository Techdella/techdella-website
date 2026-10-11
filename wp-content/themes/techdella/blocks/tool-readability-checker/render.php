<?php
/**
 * Readability Checker (§12.1): Flesch scores, sentence length and passive-voice hints, in the browser.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/tool-kit/kit.php';

$td_t      = 'readability-checker';
$td_fields = td_tool_field(
	$td_t,
	'text',
	[
		'type'        => 'textarea',
		'label'       => __( 'Your text', 'techdella' ),
		'required'    => true,
		'max'         => 60000,
		'rows'        => 10,
		'placeholder' => __( 'Paste a page, email or post (at least a few sentences).', 'techdella' ),
		'help'        => __( 'Your text stays in your browser. Nothing is uploaded.', 'techdella' ),
	]
);

echo td_tool_render( $td_t, $attributes, $td_fields, [ 'submit' => __( 'Check readability', 'techdella' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_tool_field()/td_tool_render().
