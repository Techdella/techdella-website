<?php
/**
 * Keyword Density Checker (§12.1): keyword counts and top words/phrases, in the browser.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/tool-kit/kit.php';

$td_t      = 'keyword-density-checker';
$td_fields = td_tool_field(
	$td_t,
	'keyword',
	[
		'label'       => __( 'Target keyword', 'techdella' ),
		'required'    => true,
		'max'         => 80,
		'half'        => true,
		'placeholder' => __( 'e.g. B2B SEO', 'techdella' ),
	]
)
	. td_tool_field(
		$td_t,
		'keyword2',
		[
			'label'       => __( 'Second keyword', 'techdella' ),
			'max'         => 80,
			'half'        => true,
			'placeholder' => __( 'e.g. technical SEO', 'techdella' ),
		]
	)
	. td_tool_field(
		$td_t,
		'text',
		[
			'type'        => 'textarea',
			'label'       => __( 'Your content', 'techdella' ),
			'required'    => true,
			'max'         => 60000,
			'rows'        => 10,
			'placeholder' => __( 'Paste the article or page copy.', 'techdella' ),
			'help'        => __( 'Your text stays in your browser. Nothing is uploaded.', 'techdella' ),
		]
	);

echo td_tool_render( $td_t, $attributes, $td_fields, [ 'submit' => __( 'Check density', 'techdella' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_tool_field()/td_tool_render().
