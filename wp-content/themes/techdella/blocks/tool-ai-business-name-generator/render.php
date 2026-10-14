<?php
/**
 * AI Business Name Generator (§12.1): 10 AI-generated name ideas via POST /techdella/v1/tools/ai.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/tool-kit/kit.php';

$td_t      = 'ai-business-name-generator';
$td_fields = td_tool_field(
	$td_t,
	'industry',
	[
		'label'       => __( 'Industry', 'techdella' ),
		'required'    => true,
		'max'         => 120,
		'half'        => true,
		'placeholder' => __( 'e.g. Fintech', 'techdella' ),
	]
)
	. td_tool_field(
		$td_t,
		'tone',
		[
			'type'    => 'select',
			'label'   => __( 'Tone', 'techdella' ),
			'half'    => true,
			'options' => [
				''          => __( 'Any', 'techdella' ),
				'modern'    => __( 'Modern', 'techdella' ),
				'playful'   => __( 'Playful', 'techdella' ),
				'serious'   => __( 'Serious', 'techdella' ),
				'technical' => __( 'Technical', 'techdella' ),
				'premium'   => __( 'Premium', 'techdella' ),
			],
		]
	)
	. td_tool_field(
		$td_t,
		'keywords',
		[
			'type'        => 'textarea',
			'label'       => __( 'What the business does, plus words you like', 'techdella' ),
			'required'    => true,
			'max'         => 300,
			'rows'        => 3,
			'placeholder' => __( 'e.g. Cross-border payments for freelancers; words: flow, bridge, pay', 'techdella' ),
		]
	)
	. td_tool_field(
		$td_t,
		'avoid',
		[
			'label'       => __( 'Words to avoid', 'techdella' ),
			'max'         => 160,
			'placeholder' => __( 'e.g. pay, cash', 'techdella' ),
		]
	);

echo td_tool_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_tool_field()/td_tool_render().
	$td_t,
	$attributes,
	$td_fields,
	[
		'submit' => __( 'Suggest names', 'techdella' ),
		'note'   => __( 'AI-generated. Takes up to 30 seconds.', 'techdella' ),
	]
);
