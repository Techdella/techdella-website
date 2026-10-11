<?php
/**
 * Free-tool REST routes (§12.1).
 *
 * POST /wp-json/techdella/v1/tools/fetch  { url }
 *   200 { ok, facts{…}, robots{found, bots{name: allowed|blocked|partial}}, llms_txt }
 *   400 { ok:false, code, message }   bad / blocked URL (private, loopback, scheme…)
 *   422 { ok:false, code, message }   fetched, but not an HTML page / HTTP error
 *   429 { ok:false, code, message }   rate limit
 *   502 { ok:false, code, message }   could not reach the site
 *
 * POST /wp-json/techdella/v1/tools/ai     { tool, input{…} }
 *   200 { ok, data{…}, ai_generated:true }
 *   400 invalid input · 429 limit · 503 not configured / busy · 422 refused · 502 upstream
 *
 * Abuse controls: JSON only (no simple-form CSRF), same-site Origin when the
 * browser sends one, per-IP limits in transients, and for the fetcher an SSRF
 * guard on every hop with the connection pinned to the vetted IP (no DNS rebinding).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Tools\AiTools;
use TechDella\Core\Tools\PageFacts;
use TechDella\Core\Tools\Ssrf;

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'techdella/v1',
			'/tools/fetch',
			[
				'methods'             => 'POST',
				'callback'            => 'td_tools_rest_fetch',
				'permission_callback' => '__return_true',
				'args'                => [
					'url'     => [
						'type'      => 'string',
						'required'  => true,
						'maxLength' => 2048,
					],
					'keyword' => [
						'type'      => 'string',
						'maxLength' => 120,
					],
				],
			]
		);
		register_rest_route(
			'techdella/v1',
			'/tools/ai',
			[
				'methods'             => 'POST',
				'callback'            => 'td_tools_rest_ai',
				'permission_callback' => '__return_true',
				'args'                => [
					'tool'  => [
						'type'     => 'string',
						'required' => true,
						'enum'     => array_keys( AiTools::definitions() ),
					],
					'input' => [
						'type'     => 'object',
						'required' => true,
					],
				],
			]
		);
	}
);

/**
 * JSON error reply.
 *
 * @param int    $status  HTTP status.
 * @param string $code    Machine code.
 * @param string $message Visitor-facing message.
 * @param array  $extra   Extra fields.
 */
function td_tools_error( int $status, string $code, string $message, array $extra = [] ): WP_REST_Response {
	$res = new WP_REST_Response(
		array_merge(
			[
				'ok'      => false,
				'code'    => $code,
				'message' => $message,
			],
			$extra
		),
		$status
	);
	$res->header( 'Cache-Control', 'no-store' );
	return $res;
}

/**
 * Client IP (shares the forms module's trusted-proxy filter when present).
 */
function td_tools_client_ip(): string {
	if ( function_exists( 'td_forms_client_ip' ) ) {
		return td_forms_client_ip();
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Fixed-window counter in a transient. Returns false once $limit is reached.
 *
 * @param string $bucket Bucket name (already includes the IP when per-IP).
 * @param int    $limit  Max hits per window.
 * @param int    $window Window in seconds.
 */
function td_tools_rate_hit( string $bucket, int $limit, int $window ): bool {
	$key   = 'td_tl_' . md5( $bucket );
	$state = get_transient( $key );
	$now   = time();
	if ( ! is_array( $state ) || (int) ( $state['reset'] ?? 0 ) <= $now ) {
		$state = [
			'n'     => 0,
			'reset' => $now + $window,
		];
	}
	if ( (int) $state['n'] >= $limit ) {
		return false;
	}
	++$state['n'];
	set_transient( $key, $state, max( 1, (int) $state['reset'] - $now ) );
	return true;
}

/**
 * Same-site request: JSON body, and an Origin (when sent) on one of our hosts.
 *
 * @param WP_REST_Request $request Request.
 */
function td_tools_request_ok( WP_REST_Request $request ): bool {
	if ( ! str_contains( strtolower( (string) $request->get_header( 'content_type' ) ), 'application/json' ) ) {
		return false;
	}
	$origin = (string) $request->get_header( 'origin' );
	if ( '' === $origin ) {
		return true;
	}
	$host  = strtolower( (string) wp_parse_url( $origin, PHP_URL_HOST ) );
	$hosts = [ (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ];
	foreach ( [ 'marketing', 'journal', 'studios' ] as $site ) {
		$hosts[] = (string) wp_parse_url( td_url( $site, '/' ), PHP_URL_HOST );
	}
	foreach ( td_site_domains() as $url ) {
		$h       = (string) wp_parse_url( $url, PHP_URL_HOST );
		$hosts[] = $h;
		$hosts[] = 'www.' . $h;
	}
	return in_array( $host, array_map( 'strtolower', $hosts ), true );
}

/**
 * DNS resolver for the SSRF guard (A + AAAA). Filterable for tests.
 *
 * @param string $host Host name.
 * @return string[]
 */
function td_tools_resolve( string $host ): array {
	$filtered = apply_filters( 'td_tools_resolver', null, $host );
	if ( is_array( $filtered ) ) {
		return array_map( 'strval', $filtered );
	}
	$ips     = [];
	$records = @dns_get_record( $host, DNS_A | DNS_AAAA ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- DNS failure is an expected outcome.
	foreach ( is_array( $records ) ? $records : [] as $r ) {
		if ( ! empty( $r['ip'] ) ) {
			$ips[] = (string) $r['ip'];
		} elseif ( ! empty( $r['ipv6'] ) ) {
			$ips[] = (string) $r['ipv6'];
		}
	}
	if ( ! $ips ) {
		$v4  = gethostbynamel( $host );
		$ips = is_array( $v4 ) ? $v4 : [];
	}
	return $ips;
}

/**
 * GET a public URL safely: Ssrf::fetch() vets and pins every hop (≤ 3 redirects,
 * hard time budget); the transport below is WordPress HTTP forced onto curl with
 * CURLOPT_RESOLVE, so the connection goes only to the vetted address. Never throws.
 *
 * @param string $url       URL.
 * @param int    $max_bytes Body cap.
 * @param float  $timeout   Total seconds for all hops.
 * @return array{ok:bool,error:string,status:int,headers:array<string,string>,body:string,url:string,redirects:int}
 */
function td_tools_safe_get( string $url, int $max_bytes, float $timeout ): array {
	$ports = array_map( 'intval', (array) apply_filters( 'td_tools_fetch_ports', [ 80, 443 ] ) );
	if ( ! function_exists( 'curl_init' ) ) {
		return [
			'ok'        => false,
			'error'     => 'transport',
			'status'    => 0,
			'headers'   => [],
			'body'      => '',
			'url'       => $url,
			'redirects' => 0,
		];
	}
	$http = static function ( string $hop_url, string $host, int $port, string $ip, float $left ) use ( $max_bytes ): array {
		$pin        = $host . ':' . $port . ':' . ( str_contains( $ip, ':' ) ? '[' . $ip . ']' : $ip );
		$pin_hook   = static function ( $handle ) use ( $pin ): void {
			// phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_setopt
			curl_setopt( $handle, CURLOPT_RESOLVE, [ $pin ] );
			curl_setopt( $handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS );
			curl_setopt( $handle, CURLOPT_FOLLOWLOCATION, false );
			curl_setopt( $handle, CURLOPT_PROXY, '' );
			// phpcs:enable
		};
		$transports = static fn(): array => [ 'curl' ];
		add_action( 'http_api_curl', $pin_hook, PHP_INT_MAX );
		add_filter( 'http_api_transports', $transports, PHP_INT_MAX );
		$response = wp_remote_get( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
			$hop_url,
			[
				'timeout'             => max( 1, $left ),
				'redirection'         => 0,
				'limit_response_size' => $max_bytes,
				'reject_unsafe_urls'  => false, // Ssrf is stricter, and pinned.
				'user-agent'          => 'TechDellaSEOChecker/1.0 (+' . td_url( 'marketing', '/tools/on-page-seo-checker/' ) . ')',
				'headers'             => [ 'Accept' => 'text/html,application/xhtml+xml,text/plain;q=0.9,*/*;q=0.5' ],
				'sslverify'           => true,
			]
		);
		remove_action( 'http_api_curl', $pin_hook, PHP_INT_MAX );
		remove_filter( 'http_api_transports', $transports, PHP_INT_MAX );
		if ( is_wp_error( $response ) ) {
			return [ 'error' => str_contains( strtolower( $response->get_error_message() ), 'timed out' ) ? 'timeout' : 'unreachable' ];
		}
		$headers = [];
		$raw     = wp_remote_retrieve_headers( $response );
		$raw     = is_object( $raw ) && method_exists( $raw, 'getAll' ) ? $raw->getAll() : (array) $raw;
		foreach ( $raw as $name => $value ) {
			$headers[ strtolower( (string) $name ) ] = is_array( $value ) ? (string) reset( $value ) : (string) $value;
		}
		return [
			'status'  => (int) wp_remote_retrieve_response_code( $response ),
			'headers' => $headers,
			'body'    => substr( (string) wp_remote_retrieve_body( $response ), 0, $max_bytes ),
		];
	};
	return Ssrf::fetch( $url, 'td_tools_resolve', $http, $timeout, $ports, 3 );
}

/**
 * Visitor-facing message for a fetch error code.
 *
 * @param string $code Error code.
 */
function td_tools_fetch_message( string $code ): string {
	$messages = [
		'invalid_url' => __( 'Enter a full web address, like https://example.com/page/.', 'techdella' ),
		'scheme'      => __( 'Only http:// and https:// addresses can be checked.', 'techdella' ),
		'userinfo'    => __( 'Remove the username and password from the address.', 'techdella' ),
		'port'        => __( 'Only standard web ports (80 and 443) can be checked.', 'techdella' ),
		'host'        => __( 'That doesn’t look like a public website address.', 'techdella' ),
		'private'     => __( 'That address points to a private or local network, so we can’t check it.', 'techdella' ),
		'dns'         => __( 'We couldn’t find that domain. Check the spelling.', 'techdella' ),
		'timeout'     => __( 'The site took too long to answer (over 5 seconds). Try again in a moment.', 'techdella' ),
		'redirects'   => __( 'The page redirects more than 3 times. Check the final URL instead.', 'techdella' ),
		'unreachable' => __( 'We couldn’t connect to that site.', 'techdella' ),
		'transport'   => __( 'The checker is unavailable on this server right now.', 'techdella' ),
	];
	return $messages[ $code ] ?? $messages['unreachable'];
}

/**
 * POST /tools/fetch.
 *
 * @param WP_REST_Request $request Request.
 */
function td_tools_rest_fetch( WP_REST_Request $request ): WP_REST_Response {
	if ( ! td_tools_request_ok( $request ) ) {
		return td_tools_error( 403, 'forbidden', __( 'This request isn’t allowed.', 'techdella' ) );
	}
	$url   = trim( (string) $request->get_param( 'url' ) );
	$check = Ssrf::check_url( $url, array_map( 'intval', (array) apply_filters( 'td_tools_fetch_ports', [ 80, 443 ] ) ) );
	if ( ! $check['ok'] ) {
		return td_tools_error( 400, $check['error'], td_tools_fetch_message( $check['error'] ) );
	}
	// Count only well-formed public-looking URLs: malformed/blocked input never leaves the server.
	$ip     = td_tools_client_ip();
	$limits = (array) apply_filters(
		'td_tools_fetch_limits',
		[
			'minute' => 'local' === td_environment() ? 60 : 10,
			'day'    => 'local' === td_environment() ? 1000 : 100,
		]
	);
	if ( ! td_tools_rate_hit( 'fetch-m|' . $ip, (int) $limits['minute'], MINUTE_IN_SECONDS ) || ! td_tools_rate_hit( 'fetch-d|' . $ip, (int) $limits['day'], DAY_IN_SECONDS ) ) {
		return td_tools_error( 429, 'rate_limit', __( 'You’ve run a lot of checks. Please wait a minute and try again.', 'techdella' ) );
	}

	$cache_key = 'td_tf_' . md5( $check['url'] );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return new WP_REST_Response( $cached, 200 );
	}

	$start = microtime( true );
	$page  = td_tools_safe_get( $check['url'], 2 * MB_IN_BYTES, 5.0 );
	if ( ! $page['ok'] ) {
		$status = in_array( $page['error'], [ 'timeout', 'unreachable', 'transport' ], true ) ? 502 : 400;
		return td_tools_error( $status, $page['error'], td_tools_fetch_message( $page['error'] ) );
	}
	$ms   = (int) round( ( microtime( true ) - $start ) * 1000 );
	$type = strtolower( (string) ( $page['headers']['content-type'] ?? '' ) );
	if ( $page['status'] >= 400 ) {
		/* translators: %d: HTTP status code. */
		return td_tools_error( 422, 'http_status', sprintf( __( 'The page answered with HTTP %d, so there is nothing to check.', 'techdella' ), $page['status'] ), [ 'status' => $page['status'] ] );
	}
	if ( '' !== $type && ! str_contains( $type, 'html' ) ) {
		return td_tools_error( 422, 'not_html', __( 'That URL isn’t an HTML page (it may be a PDF, image or file).', 'techdella' ) );
	}

	$facts                 = PageFacts::from_html( $page['body'], $page['url'] );
	$facts['url']          = $page['url'];
	$facts['status']       = $page['status'];
	$facts['https']        = str_starts_with( $page['url'], 'https://' );
	$facts['redirects']    = $page['redirects'];
	$facts['response_ms']  = $ms;
	$facts['bytes']        = strlen( $page['body'] );
	$facts['x_robots_tag'] = substr( (string) ( $page['headers']['x-robots-tag'] ?? '' ), 0, 200 );

	// robots.txt + llms.txt on the final origin (each capped and time-boxed).
	$origin = (string) preg_replace( '#^(https?://[^/]+).*$#', '$1', $page['url'] );
	$robots = td_tools_safe_get( $origin . '/robots.txt', 512 * KB_IN_BYTES, 3.0 );
	$r_ok   = $robots['ok'] && 200 === $robots['status'] && ! str_contains( strtolower( (string) ( $robots['headers']['content-type'] ?? 'text/plain' ) ), 'html' );
	$llms   = td_tools_safe_get( $origin . '/llms.txt', 64 * KB_IN_BYTES, 3.0 );
	$l_ok   = $llms['ok'] && 200 === $llms['status'] && '' !== trim( $llms['body'] ) && ! str_contains( strtolower( (string) ( $llms['headers']['content-type'] ?? '' ) ), 'html' );

	$body = [
		'ok'       => true,
		'facts'    => $facts,
		'robots'   => [
			'found' => $r_ok,
			'bots'  => PageFacts::ai_rules( $r_ok ? $robots['body'] : '' ),
		],
		'llms_txt' => $l_ok,
	];
	set_transient( $cache_key, $body, 10 * MINUTE_IN_SECONDS );
	$res = new WP_REST_Response( $body, 200 );
	$res->header( 'Cache-Control', 'no-store' );
	return $res;
}

/**
 * POST /tools/ai.
 *
 * @param WP_REST_Request $request Request.
 */
function td_tools_rest_ai( WP_REST_Request $request ): WP_REST_Response {
	if ( ! td_tools_request_ok( $request ) ) {
		return td_tools_error( 403, 'forbidden', __( 'This request isn’t allowed.', 'techdella' ) );
	}
	$key = defined( 'AI_API_KEY' ) ? (string) AI_API_KEY : '';
	if ( '' === $key ) {
		return td_tools_error( 503, 'not_configured', __( 'AI tools are not configured on this site yet, so we can’t generate results right now. Please try again later.', 'techdella' ) );
	}
	$tool  = (string) $request->get_param( 'tool' );
	$input = $request->get_param( 'input' );
	$clean = AiTools::sanitize_input( $tool, is_array( $input ) ? $input : [] );
	if ( ! $clean['ok'] ) {
		return td_tools_error( 400, 'invalid', __( 'Check the highlighted fields and try again.', 'techdella' ), [ 'errors' => $clean['errors'] ] );
	}

	$ip = td_tools_client_ip();
	if ( ! td_tools_rate_hit( 'ai-d|' . $ip, (int) apply_filters( 'td_tools_ai_daily_limit', 10 ), DAY_IN_SECONDS ) ) {
		return td_tools_error( 429, 'rate_limit', __( 'You’ve reached today’s limit for the AI tools. Please come back tomorrow.', 'techdella' ) );
	}
	if ( ! td_tools_rate_hit( 'ai-global', (int) apply_filters( 'td_tools_ai_global_daily_limit', 500 ), DAY_IN_SECONDS ) ) {
		return td_tools_error( 503, 'busy', __( 'The AI tools are very busy today. Please try again tomorrow.', 'techdella' ) );
	}

	$model    = defined( 'AI_MODEL' ) && '' !== (string) AI_MODEL ? (string) AI_MODEL : AiTools::DEFAULT_MODEL;
	$max      = min( 8000, (int) apply_filters( 'td_tools_ai_max_tokens', 4000, $tool ) );
	$response = wp_remote_post(
		'https://api.anthropic.com/v1/messages',
		[
			'timeout' => 45,
			'headers' => [
				'x-api-key'         => $key,
				'anthropic-version' => '2023-06-01',
				'anthropic-beta'    => 'server-side-fallback-2026-07-01',
				'content-type'      => 'application/json',
			],
			'body'    => (string) wp_json_encode( AiTools::request_body( $tool, $clean['input'], $model, $max ) ),
		]
	);
	if ( is_wp_error( $response ) ) {
		error_log( 'techdella tools/ai: transport error ' . $response->get_error_code() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- no visitor input logged.
		return td_tools_error( 502, 'upstream', __( 'We couldn’t reach the AI service. Please try again in a minute.', 'techdella' ) );
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( 200 !== $status ) {
		error_log( 'techdella tools/ai: HTTP ' . $status ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		if ( 429 === $status || 529 === $status ) {
			return td_tools_error( 503, 'busy', __( 'The AI service is busy. Please try again in a minute.', 'techdella' ) );
		}
		return td_tools_error( 502, 'upstream', __( 'The AI service returned an error. Please try again in a minute.', 'techdella' ) );
	}
	$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	$parsed  = AiTools::parse_response( $tool, is_array( $decoded ) ? $decoded : [] );
	if ( ! $parsed['ok'] ) {
		if ( 'refusal' === $parsed['error'] ) {
			return td_tools_error( 422, 'refusal', __( 'We couldn’t generate results for that brief. Try describing your business differently.', 'techdella' ) );
		}
		return td_tools_error( 502, $parsed['error'], __( 'The AI answer wasn’t usable. Please try again.', 'techdella' ) );
	}
	$res = new WP_REST_Response(
		[
			'ok'           => true,
			'ai_generated' => true,
			'data'         => $parsed['data'],
		],
		200
	);
	$res->header( 'Cache-Control', 'no-store' );
	return $res;
}
