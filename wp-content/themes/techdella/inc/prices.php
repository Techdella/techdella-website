<?php
/**
 * Prices in one place, in US dollars and Nigerian naira (owner's request, 11 Oct 2026).
 *
 * Block text uses tokens such as `From {{price:growth_sprint}} / month`; a render_block filter
 * swaps each for <span class="td-price" data-usd data-ngn>, printed in USD. The pricing-cards
 * script shows naira to visitors whose device time zone is Africa/Lagos (no tracking, no IP
 * lookup), and anyone can switch currency. Pages are cached and crawled in USD.
 * NGN = USD × 200 (the ₦300,000 Growth Audit set by the owner), never below ₦300,000.
 * docs/PRICING.md lists every price and its benchmark.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every published price: key => [ USD, NGN ].
 *
 * @return array<string,array{0:string,1:string}>
 */
function td_prices(): array {
	return (array) apply_filters(
		'td_prices',
		[
			// Marketing.
			'growth_audit'    => [ '$1,500', '₦300,000' ],
			'growth_sprint'   => [ '$3,500', '₦700,000' ],
			'growth_mgmt'     => [ '$2,500', '₦500,000' ],
			'ads_setup'       => [ '$1,000', '₦300,000' ],
			'ads_one'         => [ '$1,000', '₦300,000' ],
			'ads_multi'       => [ '$1,800', '₦450,000' ],
			'ads_creative'    => [ '$750', '₦300,000' ],
			'ads_min_budget'  => [ '$2,000', '₦500,000' ],
			// Studios.
			'mvp_discovery'   => [ '$3,500', '₦700,000' ],
			'mvp_build'       => [ '$15,000', '₦3,000,000' ],
			'ai_setup'        => [ '$2,500', '₦500,000' ],
			'ai_assistant'    => [ '$5,000', '₦1,000,000' ],
			'uiux_sprint'     => [ '$3,000', '₦600,000' ],
			'design_system'   => [ '$6,000', '₦1,200,000' ],
			'landing_page'    => [ '$1,500', '₦300,000' ],
			'website'         => [ '$5,000', '₦1,000,000' ],
			'brand_identity'  => [ '$3,000', '₦600,000' ],
			'brand_refresh'   => [ '$1,800', '₦400,000' ],
			'motion_package'  => [ '$1,500', '₦300,000' ],
			'launch_film'     => [ '$4,000', '₦800,000' ],
			'creative_assets' => [ '$800', '₦300,000' ],
			'pitch_deck'      => [ '$1,200', '₦300,000' ],
		]
	);
}

/**
 * Replace {{price:key}} tokens in rendered HTML. Unknown keys stay visible so they get noticed.
 *
 * @param string $html Rendered block HTML.
 */
function td_price_tokens( string $html ): string {
	if ( ! str_contains( $html, '{{price:' ) ) {
		return $html;
	}
	$prices = td_prices();
	return (string) preg_replace_callback(
		'/\{\{price:([a-z0-9_]+)\}\}/',
		static function ( array $m ) use ( $prices ): string {
			if ( ! isset( $prices[ $m[1] ] ) ) {
				return $m[0];
			}
			[ $usd, $ngn ] = $prices[ $m[1] ];
			return '<span class="td-price" data-usd="' . esc_attr( $usd ) . '" data-ngn="' . esc_attr( $ngn ) . '">' . esc_html( $usd ) . '</span>';
		},
		$html
	);
}
add_filter( 'render_block', static fn( $html ) => td_price_tokens( (string) $html ), 20 );

/**
 * The USD/NGN switch (shown by the pricing-cards script; hidden without JavaScript).
 */
function td_currency_switch(): string {
	return '<div class="td-currency" data-td-currency hidden><span class="td-currency__label">' . esc_html__( 'Prices in', 'techdella' ) . '</span>'
		. '<button type="button" class="td-currency__btn" data-currency="usd" aria-pressed="true">USD $</button>'
		. '<button type="button" class="td-currency__btn" data-currency="ngn" aria-pressed="false">NGN ₦</button></div>';
}
