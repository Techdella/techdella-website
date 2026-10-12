<?php
/**
 * Quotes (owner, 12 Oct 2026): a visitor picks a package, gives their country and phone, and gets a
 * PDF quote in their currency (naira in Nigeria, US dollars elsewhere) straight away; the link also
 * goes to Startbuddi with the lead so it can be emailed. WordPress stores nothing (D7): the PDF is
 * drawn on request from a signed link that carries the quote details and expires after 30 days.
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/src/QuotePdf.php';

const TD_QUOTE_DAYS = 30;

/**
 * Countries for the quote form: ISO code => "Name (+dial code)". The client markets first.
 *
 * @return array<string,string>
 */
function td_quote_countries(): array {
	$list = [
		'NG' => [ 'Nigeria', '234' ],
		'US' => [ 'United States', '1' ],
		'GB' => [ 'United Kingdom', '44' ],
		'CA' => [ 'Canada', '1' ],
		'ZA' => [ 'South Africa', '27' ],
		'GH' => [ 'Ghana', '233' ],
		'KE' => [ 'Kenya', '254' ],
		'AE' => [ 'United Arab Emirates', '971' ],
		'MX' => [ 'Mexico', '52' ],
		'LB' => [ 'Lebanon', '961' ],
		'IE' => [ 'Ireland', '353' ],
		'DE' => [ 'Germany', '49' ],
		'FR' => [ 'France', '33' ],
		'NL' => [ 'Netherlands', '31' ],
		'ES' => [ 'Spain', '34' ],
		'IT' => [ 'Italy', '39' ],
		'SE' => [ 'Sweden', '46' ],
		'AU' => [ 'Australia', '61' ],
		'IN' => [ 'India', '91' ],
		'SA' => [ 'Saudi Arabia', '966' ],
		'QA' => [ 'Qatar', '974' ],
		'EG' => [ 'Egypt', '20' ],
		'RW' => [ 'Rwanda', '250' ],
		'UG' => [ 'Uganda', '256' ],
		'TZ' => [ 'Tanzania', '255' ],
		'CM' => [ 'Cameroon', '237' ],
		'SN' => [ 'Senegal', '221' ],
		'CI' => [ "Côte d'Ivoire", '225' ],
		'BR' => [ 'Brazil', '55' ],
	];
	$out  = [];
	foreach ( $list as $iso => [ $name, $dial ] ) {
		$out[ $iso ] = $name . ' (+' . $dial . ')';
	}
	$out['XX'] = __( 'Another country', 'techdella' );
	return $out;
}

/**
 * Currency for a country: naira in Nigeria, US dollars elsewhere (the two price lists we publish).
 *
 * @param string $iso ISO country code.
 */
function td_quote_currency( string $iso ): string {
	return 'NG' === strtoupper( $iso ) ? 'ngn' : 'usd';
}

/**
 * Packages a visitor can ask a quote for on this site: price key => name.
 *
 * @param string|null $site Site (default: this one).
 * @return array<string,string>
 */
function td_quote_packages( ?string $site = null ): array {
	$site   = $site ?? td_site();
	$labels = function_exists( 'td_price_labels' ) ? td_price_labels() : [];
	$group  = 'studios' === $site ? __( 'Studios', 'techdella' ) : __( 'Marketing', 'techdella' );
	$out    = [];
	foreach ( $labels as $key => [ $g, $name ] ) {
		if ( $g === $group && 'ads_min_budget' !== $key ) {
			$out[ $key ] = trim( (string) preg_replace( '/\s*\((from|one-time|from, per month|per month)\)\s*$/i', '', $name ) );
		}
	}
	$out['other'] = __( 'Not sure yet: something custom', 'techdella' );
	return $out;
}

/**
 * Signed quote token for validated form values (expires after TD_QUOTE_DAYS).
 *
 * @param array<string,mixed> $clean Validated quote form values.
 */
function td_quote_token( array $clean ): string {
	$data = [
		'p' => (string) ( $clean['package'] ?? '' ),
		'c' => strtoupper( (string) ( $clean['country'] ?? '' ) ),
		'n' => (string) ( $clean['name'] ?? '' ),
		'o' => (string) ( $clean['company'] ?? '' ),
		'e' => (string) ( $clean['email'] ?? '' ),
		't' => (string) ( $clean['phone'] ?? '' ),
		's' => td_site(),
		'd' => time(),
	];
	return td_forms_token()->sign( (string) wp_json_encode( $data ), time() + TD_QUOTE_DAYS * DAY_IN_SECONDS );
}

/**
 * The PDF link for a signed token.
 *
 * @param string $token Signed token.
 */
function td_quote_link( string $token ): string {
	return add_query_arg( 'q', rawurlencode( $token ), rest_url( 'techdella/v1/quote' ) );
}

/**
 * Quote details from a signed token (null when invalid or expired).
 *
 * @param string $token Signed token.
 * @return array<string,mixed>|null
 */
function td_quote_open( string $token ): ?array {
	$json = td_forms_token()->open( $token, time() );
	$data = null !== $json ? json_decode( $json, true ) : null;
	return is_array( $data ) && isset( $data['p'], $data['d'] ) ? $data : null;
}

/**
 * The quote PDF.
 *
 * @param array<string,mixed> $q Quote details (td_quote_open()).
 */
function td_quote_pdf( array $q ): string {
	$site      = in_array( $q['s'] ?? '', [ 'marketing', 'studios' ], true ) ? (string) $q['s'] : 'marketing';
	$currency  = td_quote_currency( (string) ( $q['c'] ?? '' ) );
	$packages  = td_quote_packages( $site );
	$key       = (string) $q['p'];
	$prices    = function_exists( 'td_prices' ) ? td_prices() : [];
	$price     = isset( $prices[ $key ] ) ? (string) $prices[ $key ][ 'ngn' === $currency ? 1 : 0 ] : '';
	$price     = str_replace( '₦', 'NGN ', $price );
	$issued    = (int) $q['d'];
	$number    = 'TD-' . gmdate( 'Ymd', $issued ) . '-' . strtoupper( substr( md5( $issued . $key . ( $q['e'] ?? '' ) ), 0, 4 ) );
	$brand     = 'studios' === $site ? 'TechDella Studios' : 'TechDella';
	$countries = td_quote_countries();
	$country   = (string) preg_replace( '/\s*\(\+\d+\)$/', '', $countries[ (string) ( $q['c'] ?? '' ) ] ?? '' );

	$pdf = new \TechDella\Core\Forms\QuotePdf( $brand . ' quote ' . $number );
	$l   = 56.0;
	$r   = \TechDella\Core\Forms\QuotePdf::W - 56;

	// Header band.
	$pdf->rect( 0, 0, \TechDella\Core\Forms\QuotePdf::W, 118, '#1A1715' );
	$pdf->rect( $l, 40, 18, 22, '#E8470A' );
	$pdf->text( $l + 28, 58, $brand, 18, '#FFFFFF', true );
	$pdf->text( $r, 52, 'QUOTE', 10, '#C9C4BE', true, 'right' );
	$pdf->text( $r, 70, $number, 12, '#FFFFFF', true, 'right' );
	$pdf->text( $l, 96, 'Tech Della Solutions LTD  ·  hello@techdella.com  ·  ' . wp_parse_url( td_url( $site, '/' ), PHP_URL_HOST ), 9, '#C9C4BE' );

	// Dates + client.
	$y = 160;
	$pdf->text( $l, $y, 'PREPARED FOR', 8, '#6B6560', true );
	$pdf->text( 330, $y, 'DETAILS', 8, '#6B6560', true );
	$y += 18;
	$pdf->text( $l, $y, (string) ( $q['n'] ?? '' ), 12, '#0D0D0D', true );
	$pdf->text( 330, $y, 'Issued: ' . wp_date( 'j F Y', $issued ), 10 );
	$y += 16;
	foreach ( array_values( array_filter( [ (string) ( $q['o'] ?? '' ), (string) ( $q['e'] ?? '' ), (string) ( $q['t'] ?? '' ), $country ] ) ) as $i => $line ) {
		$pdf->text( $l, $y + $i * 15, $line, 10, '#57534E' );
	}
	$pdf->text( 330, $y, 'Valid until: ' . wp_date( 'j F Y', $issued + TD_QUOTE_DAYS * DAY_IN_SECONDS ), 10 );
	$pdf->text( 330, $y + 15, 'Currency: ' . ( 'ngn' === $currency ? 'Nigerian naira (NGN)' : 'US dollars (USD)' ), 10 );

	// The package.
	$y = 290;
	$pdf->rect( $l, $y, $r - $l, 30, '#EFE7DD' );
	$pdf->text( $l + 14, $y + 19, 'PACKAGE', 8, '#57534E', true );
	$pdf->text( $r - 14, $y + 19, 'STARTING PRICE', 8, '#57534E', true, 'right' );
	$y   += 54;
	$name = $packages[ $key ] ?? __( 'Custom scope', 'techdella' );
	$pdf->text( $l + 14, $y, $name, 13, '#0D0D0D', true );
	$pdf->text( $r - 14, $y, '' !== $price ? 'From ' . $price : 'Quoted after a call', 13, '#0D0D0D', true, 'right' );
	$y = $pdf->para( $l + 14, $y + 20, 300, 'other' === $key || '' === $price ? 'Tell us what you need on a short call and we send a fixed price in writing.' : 'The published starting price for this package. Your exact price depends on scope and is fixed in writing before any work starts.', 9.5 );
	$pdf->line( $l, $r, $y + 8 );

	// What happens next.
	$y += 44;
	$pdf->text( $l, $y, 'What happens next', 13, '#0D0D0D', true );
	$y    += 22;
	$steps = [
		'Book a 45-minute discovery call, or reply to this quote by email.',
		'We agree the scope: deliverables, timeline, revisions and what is not included.',
		'You get a fixed price in writing. Work starts after you approve it.',
	];
	if ( str_starts_with( $key, 'ads_' ) ) {
		$steps[] = 'Ad spend is paid to the ad platforms directly and is not part of this price.';
	}
	foreach ( $steps as $i => $step ) {
		$pdf->rect( $l, $y - 11, 16, 16, '#F7F5F2' );
		$pdf->text( $l + 5, $y + 1, (string) ( $i + 1 ), 9, '#C23B07', true );
		$y = $pdf->para( $l + 28, $y, $r - $l - 28, $step, 10, '#0D0D0D' ) + 6;
	}

	// Footer.
	$y = \TechDella\Core\Forms\QuotePdf::H - 96;
	$pdf->line( $l, $r, $y );
	$pdf->text( $l, $y + 24, 'Book a call: ' . td_url( 'marketing', '/book-a-discovery-call/' ), 9.5, '#0D0D0D', true );
	$pdf->para( $l, $y + 42, $r - $l, 'This quote shows starting prices and is not an invoice. Tech Della Solutions LTD, Office 7B, Yetmob Plaza, Elewure, off Akala Express, Oluyole, Ibadan, Oyo State, Nigeria.', 8, '#6B6560' );

	return $pdf->output();
}

/**
 * GET /techdella/v1/quote?q=<signed> → the PDF (inline), never indexed.
 */
add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'techdella/v1',
			'/quote',
			[
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => [
					'q' => [
						'type'     => 'string',
						'required' => true,
					],
				],
				'callback'            => static function ( WP_REST_Request $request ) {
					$q = td_quote_open( (string) $request->get_param( 'q' ) );
					if ( ! $q ) {
						return new WP_Error( 'td_quote_expired', __( 'This quote link has expired. Ask for a new quote on the pricing page.', 'techdella' ), [ 'status' => 410 ] );
					}
					$pdf = td_quote_pdf( $q );
					if ( ! headers_sent() ) {
						header( 'Content-Type: application/pdf' );
						header( 'Content-Disposition: inline; filename="techdella-quote.pdf"' );
						header( 'Content-Length: ' . strlen( $pdf ) );
						header( 'X-Robots-Tag: noindex, nofollow' );
						header( 'Cache-Control: private, max-age=0, no-store' );
					}
					echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary PDF.
					exit;
				},
			]
		);
	}
);

/**
 * Download box for the quote thank-you page (?q= signed link from the submit route).
 *
 * @param string|null $token Signed token (default: $_GET['q']).
 */
function td_quote_thankyou_html( ?string $token = null ): string {
	if ( null === $token ) {
		$token = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- signed link.
	}
	if ( '' === $token || ! td_quote_open( $token ) ) {
		return '';
	}
	$url = td_quote_link( $token );
	return '<div class="td-form__download-box"><p><strong>' . esc_html__( 'Your quote is ready', 'techdella' ) . '</strong></p>'
		. '<p><a class="td-btn td-btn--primary" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open the quote (PDF)', 'techdella' ) . '<span class="td-btn__arrow">' . td_icon( 'arrow-right' ) . '</span></a></p>'
		. '<p class="td-form__note">' . esc_html__( 'Save the PDF or keep this page: the link works for 30 days.', 'techdella' ) . '</p></div>';
}

add_filter(
	'the_content',
	static function ( $content ) {
		if ( 'quote' !== td_forms_current_thankyou_key() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		return td_quote_thankyou_html() . $content;
	},
	20
);
