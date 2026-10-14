<?php
/**
 * On-Page SEO Checker (§12.1): the server fetches the URL (POST /techdella/v1/tools/fetch,
 * SSRF-guarded) and returns facts; the browser turns them into checks.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/tool-kit/kit.php';

$td_t      = 'on-page-seo-checker';
$td_fields = td_tool_field(
	$td_t,
	'url',
	[
		'type'        => 'url',
		'label'       => __( 'Page URL', 'techdella' ),
		'required'    => true,
		'max'         => 2000,
		'placeholder' => 'https://example.com/services/',
		'help'        => __( 'A public page. We fetch it once from our server and don’t store it.', 'techdella' ),
	]
)
	. td_tool_field(
		$td_t,
		'keyword',
		[
			'label'       => __( 'Target keyword', 'techdella' ),
			'max'         => 80,
			'placeholder' => __( 'e.g. B2B SEO services', 'techdella' ),
		]
	);

echo td_tool_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_tool_field()/td_tool_render().
	$td_t,
	$attributes,
	$td_fields,
	[
		'submit' => __( 'Check this page', 'techdella' ),
		'note'   => __( 'Takes up to 10 seconds.', 'techdella' ),
	]
);
