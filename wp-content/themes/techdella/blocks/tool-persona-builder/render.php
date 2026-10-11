<?php
/**
 * Persona Builder (§12.1): AI-generated B2B buyer persona via POST /techdella/v1/tools/ai.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/tool-kit/kit.php';

$td_t      = 'persona-builder';
$td_fields = td_tool_field(
	$td_t,
	'product',
	[
		'type'        => 'textarea',
		'label'       => __( 'What you sell', 'techdella' ),
		'required'    => true,
		'max'         => 400,
		'rows'        => 3,
		'placeholder' => __( 'e.g. Invoicing software for logistics companies', 'techdella' ),
	]
)
	. td_tool_field(
		$td_t,
		'audience',
		[
			'label'       => __( 'Who buys it (role)', 'techdella' ),
			'required'    => true,
			'max'         => 160,
			'half'        => true,
			'placeholder' => __( 'e.g. Finance manager', 'techdella' ),
		]
	)
	. td_tool_field(
		$td_t,
		'industry',
		[
			'label'       => __( 'Their industry', 'techdella' ),
			'max'         => 120,
			'half'        => true,
			'placeholder' => __( 'e.g. Freight and logistics', 'techdella' ),
		]
	)
	. td_tool_field(
		$td_t,
		'size',
		[
			'type'    => 'select',
			'label'   => __( 'Company size', 'techdella' ),
			'half'    => true,
			'options' => [
				''         => __( 'Any size', 'techdella' ),
				'1-10'     => __( '1–10 people', 'techdella' ),
				'11-50'    => __( '11–50 people', 'techdella' ),
				'51-200'   => __( '51–200 people', 'techdella' ),
				'201-1000' => __( '201–1,000 people', 'techdella' ),
				'1000+'    => __( '1,000+ people', 'techdella' ),
			],
		]
	)
	. td_tool_field(
		$td_t,
		'region',
		[
			'label'       => __( 'Market', 'techdella' ),
			'max'         => 120,
			'half'        => true,
			'placeholder' => __( 'e.g. US, Nigeria, UK', 'techdella' ),
		]
	)
	. td_tool_field(
		$td_t,
		'notes',
		[
			'type'        => 'textarea',
			'label'       => __( 'Anything else we should know', 'techdella' ),
			'max'         => 400,
			'rows'        => 3,
			'placeholder' => __( 'e.g. They switch from spreadsheets; the CFO signs off.', 'techdella' ),
		]
	);

echo td_tool_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_tool_field()/td_tool_render().
	$td_t,
	$attributes,
	$td_fields,
	[
		'submit' => __( 'Build my persona', 'techdella' ),
		'note'   => __( 'AI-generated. Takes up to 30 seconds.', 'techdella' ),
	]
);
