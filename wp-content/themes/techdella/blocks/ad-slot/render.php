<?php
/**
 * Ad slot block (§12.2). All rules live in td_ad_slot(): nothing renders off
 * tool pages, without a publisher ID, or past 3 units.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'td_ad_slot' ) ) {
	return;
}
echo td_ad_slot( (string) ( $attributes['position'] ?? 'content' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_ad_slot().
