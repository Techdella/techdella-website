<?php
/**
 * UTM Parameter Generator (§12.1): builds a tagged campaign URL in the browser.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/tool-kit/kit.php';

$td_t      = 'utm-parameter-generator';
$td_fields = td_tool_field(
	$td_t,
	'url',
	[
		'type'        => 'url',
		'label'       => __( 'Page URL', 'techdella' ),
		'required'    => true,
		'max'         => 2000,
		'placeholder' => 'https://example.com/pricing/',
		'help'        => __( 'The page people land on. Existing query parameters are kept.', 'techdella' ),
	]
)
	. td_tool_field(
		$td_t,
		'utm_source',
		[
			'label'       => __( 'Source (utm_source)', 'techdella' ),
			'required'    => true,
			'max'         => 100,
			'half'        => true,
			'placeholder' => 'linkedin',
			'help'        => __( 'Where the click comes from: google, linkedin, newsletter.', 'techdella' ),
		]
	)
	. td_tool_field(
		$td_t,
		'utm_medium',
		[
			'label'       => __( 'Medium (utm_medium)', 'techdella' ),
			'required'    => true,
			'max'         => 100,
			'half'        => true,
			'placeholder' => 'paid-social',
			'help'        => __( 'The channel type: cpc, email, paid-social, organic-social.', 'techdella' ),
		]
	)
	. td_tool_field(
		$td_t,
		'utm_campaign',
		[
			'label'       => __( 'Campaign (utm_campaign)', 'techdella' ),
			'required'    => true,
			'max'         => 100,
			'placeholder' => 'q4-ai-search-audit',
		]
	)
	. td_tool_field(
		$td_t,
		'utm_term',
		[
			'label'       => __( 'Term (utm_term)', 'techdella' ),
			'max'         => 100,
			'half'        => true,
			'placeholder' => 'b2b-seo-agency',
		]
	)
	. td_tool_field(
		$td_t,
		'utm_content',
		[
			'label'       => __( 'Content (utm_content)', 'techdella' ),
			'max'         => 100,
			'half'        => true,
			'placeholder' => 'video-ad-a',
		]
	)
	. td_tool_field(
		$td_t,
		'clean',
		[
			'type'    => 'checkbox',
			'label'   => __( 'Tidy values: lowercase, spaces become hyphens', 'techdella' ),
			'checked' => true,
		]
	);

echo td_tool_render( $td_t, $attributes, $td_fields, [ 'submit' => __( 'Build my URL', 'techdella' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_tool_field()/td_tool_render().
