<?php
/**
 * Pricing cards block ("Ways to start").
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_a     = $attributes;
$td_cards = array_filter( (array) ( $td_a['cards'] ?? [] ), 'is_array' );
$td_bg    = 'paper' === ( $td_a['background'] ?? '' ) ? 'paper' : 'canvas';
$td_title = implode( '<br>', array_map( 'td_accent', explode( "\n", (string) ( $td_a['title'] ?? '' ) ) ) );
?>
<section <?php echo td_block_wrapper( 'td-pricing td-pricing--' . $td_bg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-pricing__inner">
		<div class="td-section-head">
			<div class="td-section-head__title">
				<?php echo td_chip( (string) ( $td_a['label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( '' !== $td_title ) : ?>
					<h2 class="td-pricing__title"><?php echo $td_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- td_accent() escapes. ?></h2>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $td_a['intro'] ) ) : ?>
				<p><?php echo td_mark_placeholders( esc_html( (string) $td_a['intro'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
		</div>
		<?php
		// Prices follow the visitor's location (owner, 12 Oct 2026: no currency switch). A priced card
		// gets a quote link; the quote form below sends a PDF quote in the visitor's currency.
		$td_quote = ! empty( $td_a['quote'] ?? true ) && function_exists( 'td_form_render' ) && in_array( 'quote', td_forms_keys_for_site(), true );
		$td_quote = $td_quote && (bool) array_filter( $td_cards, static fn( $c ) => str_contains( (string) ( $c['price'] ?? '' ), '{{price:' ) );
		// One quote form per page (the first priced block); later blocks link up to it.
		$td_quote_here = $td_quote && empty( $GLOBALS['td_quote_form_shown'] );
		if ( $td_quote_here ) {
			$GLOBALS['td_quote_form_shown'] = true; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- page-scoped flag.
		}
		?>
		<ul class="td-pricing__grid" role="list">
			<?php foreach ( $td_cards as $td_c ) : ?>
				<?php
				$td_feat = ! empty( $td_c['featured'] );
				$td_url  = (string) ( $td_c['buttonUrl'] ?? '' );
				$td_url  = str_starts_with( $td_url, '/' ) ? home_url( $td_url ) : $td_url;
				?>
				<li class="td-pricing__card<?php echo $td_feat ? ' td-pricing__card--featured' : ''; ?>">
					<div class="td-pricing__top">
						<span class="td-pricing__kicker"><?php echo esc_html( (string) ( $td_c['kicker'] ?? '' ) ); ?></span>
						<?php if ( ! empty( $td_c['badge'] ) ) : ?>
							<span class="td-tag td-tag--highlight td-pricing__badge"><?php echo esc_html( (string) $td_c['badge'] ); ?></span>
						<?php endif; ?>
					</div>
					<h3 class="td-pricing__name"><?php echo esc_html( (string) ( $td_c['title'] ?? '' ) ); ?></h3>
					<p class="td-pricing__text"><?php echo td_mark_placeholders( esc_html( (string) ( $td_c['text'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<div class="td-pricing__foot">
						<strong class="td-pricing__price"><?php echo td_mark_placeholders( esc_html( (string) ( $td_c['price'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
						<?php
						if ( ! empty( $td_c['buttonLabel'] ) && '' !== $td_url ) {
							echo td_button( (string) $td_c['buttonLabel'], $td_url, $td_feat ? 'light' : 'primary', [ 'size' => 'sm' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
					<?php if ( $td_quote && preg_match( '/\{\{price:([a-z0-9_]+)\}\}/', (string) ( $td_c['price'] ?? '' ), $td_pm ) ) : ?>
						<a class="td-pricing__quote" href="#quote" data-quote-package="<?php echo esc_attr( $td_pm[1] ); ?>"><?php esc_html_e( 'Get a PDF quote', 'techdella' ); ?><span aria-hidden="true"> →</span></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $td_quote_here ) : ?>
			<div class="td-pricing__quotebox" id="quote">
				<div class="td-pricing__quotecopy">
					<p class="td-pricing__quotelabel"><?php esc_html_e( 'Want it in writing?', 'techdella' ); ?></p>
					<h3 class="td-pricing__quotetitle"><?php echo td_accent( __( 'Get a PDF quote in *your currency*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
					<p><?php esc_html_e( 'Pick a package, tell us your country and phone number, and download your quote straight away. Prices are in naira for Nigeria and US dollars everywhere else.', 'techdella' ); ?></p>
				</div>
				<?php
				echo td_form_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by td_form_render().
					'quote',
					[
						'variant'   => 'light',
						'title'     => '',
						'intro'     => '',
						'id_prefix' => 'quote-form',
					]
				);
				?>
			</div>
		<?php endif; ?>
	</div>
</section>
