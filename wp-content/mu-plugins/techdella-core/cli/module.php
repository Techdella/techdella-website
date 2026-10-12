<?php
/**
 * WP-CLI tooling, redirects and OG images (§8.3, §8.5, §9, §10.1).
 *
 * Public API:
 *   td_redirects_health(): array            rows {ok,label} for the admin dashboard
 *   td_redirect_rules(): array              rules in effect (fallback store)
 *   td_og_generate( $post_id, $force ): int attachment ID of the generated OG image
 *
 * Files:
 *   src/FrontMatter.php    YAML front-matter parser (pure)
 *   src/Directives.php     :::article blocks in Markdown (pure)
 *   src/RedirectRules.php  CSV parsing, matcher, Rank Math mapping (pure)
 *   src/Decay.php          GSC decay + review cadence (pure)
 *   redirects.php          fallback store + runtime redirector + Rank Math import + health
 *   og.php                 GD OG image generator + save_post/cron regeneration
 *   setup.php              `wp td setup` steps (do_action td_setup)
 *   importer.php           `wp td import-content` (Markdown → drafts)
 *   commands.php           WP-CLI command registration (only under WP-CLI)
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/src/FrontMatter.php';
require_once __DIR__ . '/src/Directives.php';
require_once __DIR__ . '/src/RedirectRules.php';
require_once __DIR__ . '/src/Decay.php';
require_once __DIR__ . '/redirects.php';
require_once __DIR__ . '/og.php';
require_once __DIR__ . '/covers.php'; // td_cover_generate() for the yearly title roll-over; CLI command inside.
require_once __DIR__ . '/setup.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/importer.php';
	require_once __DIR__ . '/audit.php';
	require_once __DIR__ . '/class-redirects-command.php';
	require_once __DIR__ . '/class-audit-command.php';
	require_once __DIR__ . '/commands.php';
	require_once __DIR__ . '/videos.php';
	require_once __DIR__ . '/legacy-upgrade.php';
	WP_CLI::add_command( 'td legacy:upgrade', 'td_cli_legacy_upgrade' );
}
