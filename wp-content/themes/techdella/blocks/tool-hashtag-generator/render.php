<?php
/**
 * Hashtag Generator (§12.1): suggestions from the topic + a curated list, in the browser.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/tool-kit/kit.php';

$td_t      = 'hashtag-generator';
$td_fields = td_tool_field(
	$td_t,
	'topic',
	[
		'label'       => __( 'Topic or niche', 'techdella' ),
		'required'    => true,
		'max'         => 120,
		'placeholder' => __( 'e.g. B2B SaaS marketing', 'techdella' ),
		'help'        => __( 'A few words about the post. More specific topics give better tags.', 'techdella' ),
	]
)
	. td_tool_field(
		$td_t,
		'platform',
		[
			'type'    => 'select',
			'label'   => __( 'Platform', 'techdella' ),
			'value'   => 'linkedin',
			'options' => [
				'linkedin'  => 'LinkedIn',
				'instagram' => 'Instagram',
				'tiktok'    => 'TikTok',
				'x'         => 'X',
			],
		]
	);

echo td_tool_render( $td_t, $attributes, $td_fields, [ 'submit' => __( 'Suggest hashtags', 'techdella' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_tool_field()/td_tool_render().
