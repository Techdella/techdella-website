<?php
/**
 * MVP cost calculator (Studios, §12.1; owner, 12 Oct 2026: estimate emailed through MVP_CALC).
 *
 * A planning range, not a quote: the base is the published Studios prices (Growth → Prices:
 * MVP Discovery & Prototype, MVP Build) and the package timelines (3–4 weeks discovery, 8–12
 * weeks build). Choices scale the build part by the rules in td_mvp_calc_rules(). The estimate is
 * worked out in the browser; the mvp_calc form below sends the inputs and the range to Startbuddi.
 *
 * @var array<string,mixed> $attributes
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/tool-kit/kit.php';

if ( ! function_exists( 'td_mvp_calc_rules' ) ) {
	/**
	 * Calculator rules: options per question, each with a multiplier on the build part (`x`), a
	 * share of the build price added (`add`) and weeks added (`weeks`). Filterable.
	 *
	 * @return array<string,mixed>
	 */
	function td_mvp_calc_rules(): array {
		$prices = function_exists( 'td_prices' ) ? td_prices() : [];
		$num    = static fn( string $p ): int => (int) preg_replace( '/\D+/', '', $p );
		$rules  = [
			'base'      => [
				'discovery' => [
					'usd' => $num( $prices['mvp_discovery'][0] ?? '$3,500' ),
					'ngn' => $num( $prices['mvp_discovery'][1] ?? '₦700,000' ),
				],
				'build'     => [
					'usd' => $num( $prices['mvp_build'][0] ?? '$15,000' ),
					'ngn' => $num( $prices['mvp_build'][1] ?? '₦3,000,000' ),
				],
				'weeks'     => [
					'discovery' => [ 3, 4 ],
					'build'     => [ 8, 12 ],
				],
				'spread'    => 1.3,
			],
			'questions' => [
				'stage'    => [
					'label'   => __( 'What do you need?', 'techdella' ),
					'type'    => 'one',
					'options' => [
						'prototype' => [ __( 'A clickable prototype', 'techdella' ), __( 'Discovery, scope and a prototype to test with users', 'techdella' ) ],
						'mvp'       => [ __( 'A working MVP', 'techdella' ), __( 'Discovery first, then the product built in milestones', 'techdella' ) ],
					],
					'default' => 'mvp',
				],
				'platform' => [
					'label'   => __( 'Where will people use it?', 'techdella' ),
					'type'    => 'one',
					'options' => [
						'web'    => [ __( 'Web app', 'techdella' ), __( 'Works in any browser, on desktop and phone', 'techdella' ), 1.0, 0 ],
						'mobile' => [ __( 'Mobile app', 'techdella' ), __( 'iOS and Android', 'techdella' ), 1.4, 2 ],
						'both'   => [ __( 'Web and mobile apps', 'techdella' ), __( 'A web app plus iOS and Android', 'techdella' ), 1.7, 4 ],
					],
					'default' => 'web',
				],
				'features' => [
					'label'   => __( 'Which features does the first version need?', 'techdella' ),
					'help'    => __( 'Sign-up, log-in and user accounts are always included.', 'techdella' ),
					'type'    => 'many',
					'options' => [
						'payments'     => [ __( 'Payments', 'techdella' ), '', 0.12, 1 ],
						'roles'        => [ __( 'Teams and roles', 'techdella' ), '', 0.06, 0.5 ],
						'dashboard'    => [ __( 'Dashboards and reports', 'techdella' ), '', 0.1, 1 ],
						'admin'        => [ __( 'Admin panel', 'techdella' ), '', 0.08, 1 ],
						'messaging'    => [ __( 'Chat or messaging', 'techdella' ), '', 0.12, 1 ],
						'bookings'     => [ __( 'Bookings and scheduling', 'techdella' ), '', 0.1, 1 ],
						'integrations' => [ __( 'Integrations (CRM, email, APIs)', 'techdella' ), '', 0.1, 1 ],
						'ai'           => [ __( 'AI features', 'techdella' ), '', 0.15, 1.5 ],
					],
					'default' => [],
				],
				'design'   => [
					'label'   => __( 'Design', 'techdella' ),
					'type'    => 'one',
					'options' => [
						'full'  => [ __( 'Design it for us', 'techdella' ), __( 'End-to-end UI/UX: flows, wireframes and the full interface', 'techdella' ), 1.0, 0 ],
						'ready' => [ __( 'We have designs', 'techdella' ), __( 'Ready-to-build screens in Figma', 'techdella' ), 0.9, -1 ],
					],
					'default' => 'full',
				],
				'timeline' => [
					'label'   => __( 'When do you need it?', 'techdella' ),
					'type'    => 'one',
					'options' => [
						'standard' => [ __( 'Standard timeline', 'techdella' ), __( 'Milestones at a steady pace', 'techdella' ), 1.0, 0 ],
						'rush'     => [ __( 'As soon as possible', 'techdella' ), __( 'A larger team on the build', 'techdella' ), 1.2, -2 ],
					],
					'default' => 'standard',
				],
			],
		];
		return (array) apply_filters( 'td_mvp_calc_rules', $rules );
	}
}

$td_rules = td_mvp_calc_rules();
$td_id    = wp_unique_id( 'td-mvp-' );

/**
 * One question as pill controls (same look as the forms).
 *
 * @param string              $key Question key.
 * @param array<string,mixed> $q   Question.
 * @param string              $id  Id prefix.
 */
$td_question = static function ( string $key, array $q, string $id ): string {
	$many  = 'many' === $q['type'];
	$out   = '<fieldset class="td-field td-field--pills td-mvp__q" data-q="' . esc_attr( $key ) . '">';
	$out  .= '<legend class="td-field__label">' . esc_html( (string) $q['label'] ) . '</legend>';
	$out  .= ! empty( $q['help'] ) ? '<p class="td-field__help">' . esc_html( (string) $q['help'] ) . '</p>' : '';
	$out  .= '<div class="td-pills ' . ( $many ? '' : 'td-pills--card' ) . '">';
	$i     = 0;
	$deflt = (array) $q['default'];
	foreach ( (array) $q['options'] as $value => $opt ) {
		++$i;
		$oid  = $id . '-' . $key . '-' . $i;
		$out .= '<label class="td-pill" for="' . esc_attr( $oid ) . '">'
			. '<input class="td-pill__input" type="' . ( $many ? 'checkbox' : 'radio' ) . '" id="' . esc_attr( $oid ) . '" name="' . esc_attr( $id . '-' . $key ) . '" value="' . esc_attr( (string) $value ) . '"' . ( in_array( (string) $value, $deflt, true ) ? ' checked' : '' ) . '>'
			. '<span class="td-pill__box"><span class="td-pill__label">' . esc_html( (string) $opt[0] ) . '</span>'
			. ( '' !== (string) ( $opt[1] ?? '' ) ? '<span class="td-pill__hint">' . esc_html( (string) $opt[1] ) . '</span>' : '' ) . '</span></label>';
	}
	return $out . '</div></fieldset>';
};

td_tool_enqueue( 'mvp-calculator' );
$td_json = wp_json_encode( $td_rules );
?>
<section id="calculator" <?php echo td_block_wrapper( 'td-mvp td-section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-labelledby="<?php echo esc_attr( $td_id ); ?>-h">
	<div class="td-container td-mvp__grid" data-td-mvp="<?php echo esc_attr( (string) $td_json ); ?>">
		<div class="td-mvp__panel td-form">
			<h2 class="td-mvp__title" id="<?php echo esc_attr( $td_id ); ?>-h"><?php echo td_accent( __( 'Build your *estimate*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
			<?php
			foreach ( (array) $td_rules['questions'] as $td_key => $td_q ) {
				echo $td_question( (string) $td_key, (array) $td_q, $td_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
			}
			?>
		</div>
		<aside class="td-mvp__result" aria-labelledby="<?php echo esc_attr( $td_id ); ?>-rh">
			<div class="td-mvp__sticky">
				<p class="td-mvp__label" id="<?php echo esc_attr( $td_id ); ?>-rh"><?php esc_html_e( 'Your estimate', 'techdella' ); ?></p>
				<?php echo td_currency_switch(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p class="td-mvp__range" aria-live="polite">
					<span class="td-price" data-td-mvp-low data-usd="" data-ngn="">—</span>
					<span aria-hidden="true">–</span><span class="td-sr"><?php esc_html_e( 'to', 'techdella' ); ?></span>
					<span class="td-price" data-td-mvp-high data-usd="" data-ngn="">—</span>
				</p>
				<p class="td-mvp__weeks"><strong data-td-mvp-weeks>—</strong> <?php esc_html_e( 'weeks to a first version', 'techdella' ); ?></p>
				<ul class="td-mvp__lines" role="list" data-td-mvp-lines></ul>
				<p class="td-mvp__note"><?php esc_html_e( 'A planning range, not a quote. Your price is fixed in writing after a scoping call.', 'techdella' ); ?></p>
				<?php echo td_button( __( 'Email me this estimate', 'techdella' ), '#estimate', 'light', [ 'class' => 'td-mvp__cta' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<noscript><p class="td-mvp__note"><?php esc_html_e( 'Turn on JavaScript to see the estimate, or send us your idea with the form below.', 'techdella' ); ?></p></noscript>
			</div>
		</aside>
	</div>
</section>
