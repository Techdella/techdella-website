<?php
/**
 * REST routes (§11.2).
 *
 *   GET  /wp-json/techdella/v1/form-token?form=<key>   fresh token (view.js, first interaction)
 *   POST /wp-json/techdella/v1/submit/<key>            JSON (fetch) or form-encoded (no-JS POST)
 *
 * Checks, in order: origin + token (CSRF that survives full-page caching),
 * Turnstile, honeypot, min-time ≥ 3 s, rate limit 5/min/IP, schema validation.
 *
 * Responses (JSON):  200 { ok: true, redirect }            delivered
 *                    202 { ok: true, redirect, queued }    Startbuddi down → queued
 *                    400 { ok: false, errors: { field: message } }
 *                    403 { ok: false, errors: { _form }, code }   bot / token
 *                    429 { ok: false, errors: { _form } }          rate limit
 * No-JS (form-encoded): 303 to the thank-you page, or 303 back to the form
 * with ?td_fe=<signed id> (errors + old input in a 10-minute transient; no PII in the URL).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Forms\Payload;
use TechDella\Core\Forms\Retry;
use TechDella\Core\Forms\Token;
use TechDella\Core\Forms\Validator;

/** Field names the form adds besides the schema fields. */
const TD_FORMS_META_FIELDS = [ 'td_token', 'td_page', 'td_ref', 'td_form_id', 'td_hp', 'cf-turnstile-response' ];

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'techdella/v1',
			'/form-token',
			[
				'methods'             => 'GET',
				'callback'            => 'td_forms_rest_token',
				'permission_callback' => '__return_true',
				'args'                => [
					'form' => [
						'type'     => 'string',
						'required' => true,
						'enum'     => td_forms_keys(),
					],
				],
			]
		);
		register_rest_route(
			'techdella/v1',
			'/submit/(?P<key>[a-z_]+)',
			[
				'methods'             => 'POST',
				'callback'            => 'td_forms_rest_submit',
				'permission_callback' => '__return_true',
			]
		);
	}
);

/**
 * GET /form-token.
 *
 * @param WP_REST_Request $request Request.
 */
function td_forms_rest_token( WP_REST_Request $request ): WP_REST_Response {
	$key      = (string) $request->get_param( 'form' );
	$response = new WP_REST_Response(
		[
			'token' => td_forms_token()->issue( $key, time() ),
			'min'   => Token::MIN_AGE,
		]
	);
	$response->header( 'Cache-Control', 'no-store, max-age=0' );
	$response->header( 'X-LiteSpeed-Cache-Control', 'no-cache' );
	return $response;
}

/**
 * Client IP for rate limiting. Uses REMOTE_ADDR only; behind a trusted proxy
 * (e.g. Cloudflare) return the forwarded header from the td_forms_client_ip filter.
 */
function td_forms_client_ip(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$ip = (string) apply_filters( 'td_forms_client_ip', $ip );
	return false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Rate limit: 5 accepted-for-validation submissions per minute per IP (transients).
 *
 * @param string $ip IP.
 */
function td_forms_rate_limit_hit( string $ip ): bool {
	$limit               = (int) apply_filters( 'td_forms_rate_limit', 5 );
	$tkey                = 'td_frl_' . md5( $ip . '|' . wp_salt( 'nonce' ) );
	$state               = get_transient( $tkey );
	[ $allowed, $state ] = Retry::rate_limit( is_array( $state ) ? $state : null, time(), $limit, MINUTE_IN_SECONDS );
	set_transient( $tkey, $state, MINUTE_IN_SECONDS + 5 );
	return $allowed;
}

/**
 * Verify Cloudflare Turnstile. Returns true when not configured (an admin notice says so).
 *
 * @param string $response cf-turnstile-response.
 * @param string $ip       Client IP.
 */
function td_forms_turnstile_ok( string $response, string $ip ): bool {
	if ( '' === (string) TURNSTILE_SECRET_KEY ) {
		return true;
	}
	if ( '' === $response ) {
		return false;
	}
	$result = wp_safe_remote_post(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		[
			'timeout' => 5,
			'body'    => [
				'secret'   => (string) TURNSTILE_SECRET_KEY,
				'response' => $response,
				'remoteip' => $ip,
			],
		]
	);
	if ( is_wp_error( $result ) ) {
		// Fail closed: a bot check that cannot run is not a pass.
		return false;
	}
	$data = json_decode( (string) wp_remote_retrieve_body( $result ), true );
	return is_array( $data ) && ! empty( $data['success'] );
}

/**
 * POST /submit/<key>.
 *
 * @param WP_REST_Request $request Request.
 */
function td_forms_rest_submit( WP_REST_Request $request ): WP_REST_Response {
	$key    = (string) $request['key'];
	$schema = td_form_schema( $key );
	$type   = strtolower( (string) $request->get_header( 'content_type' ) );
	$is_js  = str_contains( $type, 'json' );
	$input  = $is_js ? (array) $request->get_json_params() : (array) $request->get_body_params();

	if ( ! $schema ) {
		return td_forms_reply( $request, $is_js, $key, $input, 404, [ '_form' => __( 'Unknown form.', 'techdella' ) ], 'unknown_form' );
	}
	$generic = __( 'We couldn’t send this form. Reload the page and try again.', 'techdella' );

	// 1a. Origin: a browser POST from another site is refused (CSRF). Requests without Origin pass to the token check.
	$origin = (string) $request->get_header( 'origin' );
	if ( '' !== $origin && 'null' !== $origin && ! td_forms_is_own_url( $origin ) ) {
		return td_forms_reply( $request, $is_js, $key, $input, 403, [ '_form' => $generic ], 'origin' );
	}

	// 1b. Token (HMAC-signed timestamp bound to the form key). Rendered tokens live longer than the page cache.
	$token  = is_string( $input['td_token'] ?? null ) ? (string) $input['td_token'] : '';
	$tcheck = td_forms_token()->verify( $token, $key, time(), Token::MAX_AGE_RENDERED, 0 );
	if ( '' !== $tcheck ) {
		return td_forms_reply( $request, $is_js, $key, $input, 403, [ '_form' => $generic ], 'token' );
	}

	// 2. Turnstile.
	$ip = td_forms_client_ip();
	if ( ! td_forms_turnstile_ok( is_string( $input['cf-turnstile-response'] ?? null ) ? (string) $input['cf-turnstile-response'] : '', $ip ) ) {
		return td_forms_reply( $request, $is_js, $key, $input, 403, [ '_form' => __( 'Please complete the spam check and try again.', 'techdella' ) ], 'turnstile' );
	}

	// 3. Honeypot.
	if ( '' !== trim( is_scalar( $input['td_hp'] ?? '' ) ? (string) ( $input['td_hp'] ?? '' ) : 'x' ) ) {
		return td_forms_reply( $request, $is_js, $key, $input, 403, [ '_form' => $generic ], 'bot' );
	}

	// 4. Min-time: at least 3 s between the token being issued (render or first interaction) and submit.
	if ( time() - Token::issued_at( $token ) < Token::MIN_AGE ) {
		return td_forms_reply( $request, $is_js, $key, $input, 403, [ '_form' => __( 'That was quick. Please wait a moment and send the form again.', 'techdella' ) ], 'too_fast' );
	}

	// 5. Rate limit.
	if ( ! td_forms_rate_limit_hit( $ip ) ) {
		return td_forms_reply( $request, $is_js, $key, $input, 429, [ '_form' => __( 'Too many submissions. Please wait a minute and try again.', 'techdella' ) ], 'rate_limit' );
	}

	// 6. Validate + sanitise.
	$fields             = (array) $schema['fields'];
	[ $clean, $errors ] = Validator::validate( $fields, $input );
	if ( $errors ) {
		return td_forms_reply( $request, $is_js, $key, $input, 400, $errors, 'validation', $clean );
	}

	// 7. Enrich + build.
	$payload = Payload::build( $key, $fields, $clean, td_forms_meta( $request, $input ), ! empty( $input['td_test'] ) && ! td_is_production() );
	$payload = (array) apply_filters( 'td_form_payload', $payload, $key );

	// 8. Forward; queue on failure.
	$idem   = wp_generate_uuid4();
	$result = td_forms_forward( $key, $payload, $idem );
	$queued = false;
	if ( ! $result['ok'] ) {
		$queued = td_forms_enqueue( $key, $payload, $idem, $result['error'] ) > 0;
		if ( ! $queued ) {
			return td_forms_reply( $request, $is_js, $key, $input, 500, [ '_form' => __( 'Something went wrong on our side. Please try again in a few minutes.', 'techdella' ) ], 'server' );
		}
	}
	do_action( 'td_form_submitted', $key, $queued );

	$redirect = td_forms_thankyou_path( $key );
	$body     = [
		'ok'       => true,
		'redirect' => $redirect,
	];
	if ( 'lead_magnet' === $key ) {
		$download = td_forms_resource_download( (string) ( $clean['resource_slug'] ?? '' ) );
		if ( $download ) {
			$body['download'] = [
				'url'   => $download['url'],
				'title' => $download['title'],
			];
			$body['redirect'] = add_query_arg( 'dl', rawurlencode( td_forms_token()->sign( (string) $download['id'], time() + HOUR_IN_SECONDS ) ), $redirect );
		}
	}
	if ( $queued ) {
		$body['queued'] = true;
	}
	if ( ! $is_js ) {
		return td_forms_redirect( home_url( $body['redirect'] ) );
	}
	return new WP_REST_Response( $body, $queued ? 202 : 200 );
}

/**
 * Enrichment (§11.2 step 3).
 *
 * @param WP_REST_Request     $request Request.
 * @param array<string,mixed> $input   Raw input.
 * @return array<string,mixed>
 */
function td_forms_meta( WP_REST_Request $request, array $input ): array {
	$page = Payload::clean_url( is_string( $input['td_page'] ?? null ) ? (string) $input['td_page'] : '' );
	if ( '' === $page || ! td_forms_is_own_url( $page ) ) {
		$ref  = Payload::clean_url( (string) $request->get_header( 'referer' ) );
		$page = td_forms_is_own_url( $ref ) ? $ref : '';
	}
	$cookies = $request->get_header( 'cookie' ) ? $_COOKIE : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- parsed + sanitised by Payload::utm().
	$first   = Payload::utm( isset( $cookies['td_utm_first'] ) ? wp_unslash( (string) $cookies['td_utm_first'] ) : null );
	$last    = Payload::utm( isset( $cookies['td_utm_last'] ) ? wp_unslash( (string) $cookies['td_utm_last'] ) : null );
	return [
		'site'         => td_forms_site_domain(),
		'site_key'     => td_site(),
		'environment'  => td_environment(),
		'page_url'     => $page,
		'referrer'     => Payload::clean_url( is_string( $input['td_ref'] ?? null ) ? (string) $input['td_ref'] : '' ),
		'utm_first'    => $first ? $first : (object) [],
		'utm_last'     => $last ? $last : (object) [],
		'submitted_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
	];
}

/**
 * Error/bot reply: JSON for fetch, 303 back to the form for the no-JS POST.
 *
 * @param WP_REST_Request      $request Request.
 * @param bool                 $is_js   JSON request.
 * @param string               $key     Form key.
 * @param array<string,mixed>  $input   Raw input.
 * @param int                  $status  HTTP status.
 * @param array<string,string> $errors  Errors.
 * @param string               $code    Machine code.
 * @param array<string,mixed>  $clean   Sanitised values for re-filling the form.
 */
function td_forms_reply( WP_REST_Request $request, bool $is_js, string $key, array $input, int $status, array $errors, string $code, array $clean = [] ): WP_REST_Response {
	if ( $is_js ) {
		$response = new WP_REST_Response(
			[
				'ok'     => false,
				'code'   => $code,
				'errors' => $errors,
			],
			$status
		);
		$response->header( 'Cache-Control', 'no-store' );
		return $response;
	}

	// No-JS: back to the referring page (own hosts only) with a signed flash id.
	$back = Payload::clean_url( is_string( $input['td_page'] ?? null ) ? (string) $input['td_page'] : '' );
	if ( '' === $back || ! td_forms_is_own_url( $back ) ) {
		$ref  = Payload::clean_url( (string) $request->get_header( 'referer' ) );
		$back = td_forms_is_own_url( $ref ) ? $ref : home_url( '/' );
	}
	$form_id = is_string( $input['td_form_id'] ?? null ) ? sanitize_html_class( (string) $input['td_form_id'] ) : '';
	$schema  = td_form_schema( $key );
	if ( ! $clean && $schema ) {
		[ $clean ] = Validator::validate( (array) $schema['fields'], $input );
	}
	$id = bin2hex( random_bytes( 12 ) );
	set_transient(
		'td_fe_' . $id,
		[
			'key'     => $key,
			'form_id' => $form_id,
			'errors'  => $errors,
			'old'     => $clean,
		],
		10 * MINUTE_IN_SECONDS
	);
	$url = add_query_arg( 'td_fe', rawurlencode( td_forms_token()->sign( $id, time() + 10 * MINUTE_IN_SECONDS ) ), $back );
	return td_forms_redirect( $url . ( $form_id ? '#' . $form_id : '' ) );
}

/**
 * 303 See Other.
 *
 * @param string $url Absolute URL.
 */
function td_forms_redirect( string $url ): WP_REST_Response {
	$response = new WP_REST_Response( null, 303 );
	$response->header( 'Location', esc_url_raw( $url ) );
	$response->header( 'Cache-Control', 'no-store' );
	return $response;
}

/**
 * Read (once per request) the no-JS error flash for a form instance.
 *
 * @param string $key     Form key.
 * @param string $form_id Form DOM id.
 * @return array{errors: array<string,string>, old: array<string,mixed>}|null
 */
function td_forms_flash( string $key, string $form_id ): ?array {
	static $flash = false;
	if ( false === $flash ) {
		$flash = null;
		$raw   = isset( $_GET['td_fe'] ) ? sanitize_text_field( wp_unslash( $_GET['td_fe'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- signed value, read-only.
		$id    = '' !== $raw ? td_forms_token()->open( $raw, time() ) : null;
		if ( null !== $id && ctype_xdigit( $id ) ) {
			$data = get_transient( 'td_fe_' . $id );
			if ( is_array( $data ) ) {
				$flash = $data;
				delete_transient( 'td_fe_' . $id );
			}
		}
	}
	if ( ! is_array( $flash ) || ( $flash['key'] ?? '' ) !== $key ) {
		return null;
	}
	if ( '' !== (string) ( $flash['form_id'] ?? '' ) && $flash['form_id'] !== $form_id ) {
		return null;
	}
	return [
		'errors' => (array) ( $flash['errors'] ?? [] ),
		'old'    => (array) ( $flash['old'] ?? [] ),
	];
}

// Pages showing a no-JS error flash or a download grant are per-visitor: never page-cache them.
add_action(
	'template_redirect',
	static function () {
		if ( isset( $_GET['td_fe'] ) || isset( $_GET['dl'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			td_forms_nocache();
		}
	},
	1
);
