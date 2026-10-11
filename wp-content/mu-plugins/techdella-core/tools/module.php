<?php
/**
 * Free tools: server routes (§12.1). Tools run in the browser wherever
 * possible (theme blocks/tool-<name>/); only two things need the server:
 *
 *   POST /wp-json/techdella/v1/tools/fetch   On-Page SEO Checker: fetch a URL behind
 *                                            an SSRF guard, return parsed SEO facts.
 *   POST /wp-json/techdella/v1/tools/ai      Persona Builder + AI Business Name Generator:
 *                                            Anthropic Messages API, key in AI_API_KEY.
 *
 * Files:
 *   src/Ssrf.php       URL + IP checks (pure)
 *   src/PageFacts.php  HTML → SEO facts, robots.txt → AI-crawler access (pure)
 *   src/AiTools.php    AI tool input/output contracts + request body (pure)
 *   rest.php           routes, pinned fetcher, rate limits, AI call
 *
 * Filters: td_tools_fetch_ports, td_tools_fetch_limits, td_tools_ai_daily_limit,
 * td_tools_ai_global_daily_limit, td_tools_ai_max_tokens, td_tools_resolver.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/src/Ssrf.php';
require_once __DIR__ . '/src/PageFacts.php';
require_once __DIR__ . '/src/AiTools.php';
require_once __DIR__ . '/rest.php';
