<?php
/**
 * Prices in one place, in US dollars and Nigerian naira (owner's request, 11 Oct 2026).
 *
 * Block text uses tokens such as `From {{price:growth_sprint}} / month`; a render_block filter
 * swaps each for <span class="td-price" data-usd data-ngn>, printed in USD. The pricing-cards
 * script shows naira to visitors whose device time zone is Africa/Lagos (no tracking, no IP
 * lookup), and anyone can switch currency. Pages are cached and crawled in USD.
 * NGN = USD × 200 (the ₦300,000 Growth Audit set by the owner), never below ₦300,000.
 * The team edits prices in Growth → Prices (option td_prices, per site); the table below is the
 * default for any price not set there. docs/PRICING.md lists every price and its benchmark.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default prices (owner-approved, 11 Oct 2026): key => [ USD, NGN ].
 *
 * @return array<string,array{0:string,1:string}>
 */
function td_price_defaults(): array {
	return [
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
	];
}

/**
 * What each price is, for Growth → Prices: key => [ group, name ].
 *
 * @return array<string,array{0:string,1:string}>
 */
function td_price_labels(): array {
	$m = __( 'Marketing', 'techdella' );
	$s = __( 'Studios', 'techdella' );
	return [
		'growth_audit'    => [ $m, __( 'Growth Audit (one-time)', 'techdella' ) ],
		'growth_sprint'   => [ $m, __( 'Growth Sprint (from)', 'techdella' ) ],
		'growth_mgmt'     => [ $m, __( 'Growth Management (from, per month)', 'techdella' ) ],
		'ads_setup'       => [ $m, __( 'Paid ads: setup and tracking (one-time)', 'techdella' ) ],
		'ads_one'         => [ $m, __( 'Paid ads: one platform (per month)', 'techdella' ) ],
		'ads_multi'       => [ $m, __( 'Paid ads: two or more platforms (per month)', 'techdella' ) ],
		'ads_creative'    => [ $m, __( 'Ad creative package (from)', 'techdella' ) ],
		'ads_min_budget'  => [ $m, __( 'Minimum ad budget (per month)', 'techdella' ) ],
		'mvp_discovery'   => [ $s, __( 'MVP discovery (from)', 'techdella' ) ],
		'mvp_build'       => [ $s, __( 'MVP build (from)', 'techdella' ) ],
		'ai_setup'        => [ $s, __( 'AI automation setup (from)', 'techdella' ) ],
		'ai_assistant'    => [ $s, __( 'AI assistant (from)', 'techdella' ) ],
		'uiux_sprint'     => [ $s, __( 'UI/UX design sprint (from)', 'techdella' ) ],
		'design_system'   => [ $s, __( 'Design system (from)', 'techdella' ) ],
		'landing_page'    => [ $s, __( 'Landing page (from)', 'techdella' ) ],
		'website'         => [ $s, __( 'Website (from)', 'techdella' ) ],
		'brand_identity'  => [ $s, __( 'Brand identity (from)', 'techdella' ) ],
		'brand_refresh'   => [ $s, __( 'Brand refresh (from)', 'techdella' ) ],
		'motion_package'  => [ $s, __( 'Motion package (from)', 'techdella' ) ],
		'launch_film'     => [ $s, __( 'Launch film (from)', 'techdella' ) ],
		'creative_assets' => [ $s, __( 'Creative assets (from)', 'techdella' ) ],
		'pitch_deck'      => [ $s, __( 'Pitch deck (from)', 'techdella' ) ],
	];
}

/**
 * Every published price: key => [ USD, NGN ], with the team's edits from Growth → Prices.
 *
 * @return array<string,array{0:string,1:string}>
 */
function td_prices(): array {
	static $prices = null;
	if ( null === $prices ) {
		$prices = td_price_defaults();
		if ( class_exists( '\\TechDella\\Core\\Admin\\Prices' ) ) {
			$prices = \TechDella\Core\Admin\Prices::merge( $prices, get_option( 'td_prices', [] ) );
		}
	}
	return (array) apply_filters( 'td_prices', $prices );
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
	// Owner, 12 Oct 2026: visitors can't switch currency; their location decides (pricing-cards view.js).
	return '';
}
