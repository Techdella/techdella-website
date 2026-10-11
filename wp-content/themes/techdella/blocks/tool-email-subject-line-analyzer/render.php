<?php
/**
 * Email Subject Line Analyzer (§12.1): scores a subject line with a transparent rubric, in the browser.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/tool-kit/kit.php';

$td_t      = 'email-subject-line-analyzer';
$td_fields = td_tool_field(
	$td_t,
	'subject',
	[
		'label'       => __( 'Subject line', 'techdella' ),
		'required'    => true,
		'max'         => 200,
		'placeholder' => __( 'e.g. Your Q4 pipeline review, in 5 minutes', 'techdella' ),
	]
)
	. td_tool_field(
		$td_t,
		'preheader',
		[
			'label'       => __( 'Preview text (preheader)', 'techdella' ),
			'max'         => 200,
			'placeholder' => __( 'e.g. The three numbers we check first', 'techdella' ),
			'help'        => __( 'The grey line shown after the subject in most inboxes.', 'techdella' ),
		]
	);

echo td_tool_render( $td_t, $attributes, $td_fields, [ 'submit' => __( 'Analyze subject line', 'techdella' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_tool_field()/td_tool_render().
