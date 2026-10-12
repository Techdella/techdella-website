<?php
/**
 * Newsletter (dark). Uses the forms module when present; otherwise a plain
 * POST form to the same REST route.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_uid   = wp_unique_id( 'td-newsletter-' );
$td_title = (string) ( $attributes['title'] ?? '' );
$td_form  = '';
if ( function_exists( 'td_form_render' ) ) {
	$td_form = td_form_render(
		'newsletter',
		[
			'variant'      => 'compact',
			'title'        => '',
			'intro'        => '',
			'submit_label' => (string) ( $attributes['submitLabel'] ?? __( 'Subscribe', 'techdella' ) ),
			'fields'       => array_values( array_filter( array_map( 'strval', (array) ( $attributes['fields'] ?? [ 'email' ] ) ) ) ),
			'id_prefix'    => $td_uid . '-form',
			'class'        => 'td-newsletter__form',
		]
	);
}
if ( '' === $td_form ) {
	$td_form = sprintf(
		'<form class="td-newsletter__fallback" method="post" action="%1$s"><p class="td-newsletter__field"><label for="%2$s-email">%3$s</label><input id="%2$s-email" type="email" name="email" required autocomplete="email" placeholder="you@company.com"></p><p class="td-newsletter__consent"><input id="%2$s-consent" type="checkbox" name="consent_marketing" value="1"><label for="%2$s-consent">%4$s</label></p><button type="submit" class="td-btn td-btn--light">%5$s<span class="td-btn__arrow">%6$s</span></button></form>',
		esc_url( rest_url( 'techdella/v1/submit/newsletter' ) ),
		esc_attr( $td_uid ),
		esc_html__( 'Email address', 'techdella' ),
		esc_html__( 'Send me occasional emails with guides and offers from TechDella. Unsubscribe any time.', 'techdella' ),
		esc_html( (string) ( $attributes['submitLabel'] ?? __( 'Subscribe', 'techdella' ) ) ),
		td_icon( 'arrow-up-right' )
	);
}
?>
<section <?php echo td_block_wrapper( 'td-newsletter', [ 'aria-labelledby' => $td_uid . '-title' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container">
		<div class="td-newsletter__panel">
			<div class="td-newsletter__copy">
				<?php if ( '' !== trim( (string) ( $attributes['label'] ?? '' ) ) ) : ?>
					<p class="td-newsletter__label"><span class="td-newsletter__dot" aria-hidden="true"></span><?php echo esc_html( (string) $attributes['label'] ); ?></p>
				<?php endif; ?>
				<h2 class="td-newsletter__title" id="<?php echo esc_attr( $td_uid . '-title' ); ?>"><?php echo td_accent( $td_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<?php if ( ! empty( $attributes['intro'] ) ) : ?>
					<p class="td-newsletter__intro"><?php echo esc_html( (string) $attributes['intro'] ); ?></p>
				<?php endif; ?>
				<?php $td_points = array_filter( array_map( 'strval', (array) ( $attributes['points'] ?? [] ) ) ); ?>
				<?php if ( $td_points ) : ?>
					<ul class="td-newsletter__points" role="list">
						<?php foreach ( $td_points as $td_point ) : ?>
							<li><span class="td-newsletter__check" aria-hidden="true"><?php echo td_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php echo esc_html( $td_point ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<div class="td-newsletter__formwrap">
				<?php echo $td_form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- forms module output / escaped fallback. ?>
			</div>
		</div>
	</div>
</section>
