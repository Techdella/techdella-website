<?php
/**
 * CTA (dark arch) block.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_a    = $attributes;
$td_sand = 'sand' === ( $td_a['variant'] ?? '' );
$td_url  = (string) ( $td_a['buttonUrl'] ?? '/' );
if ( preg_match( '/^(marketing|journal|studios):(\/.*)$/', $td_url, $td_m ) ) {
	$td_url = td_url( $td_m[1], $td_m[2] );
} elseif ( str_starts_with( $td_url, '/' ) ) {
	$td_url = home_url( $td_url );
}
$td_grad = wp_unique_id( 'td-arch-grad-' );
?>
<section <?php echo td_block_wrapper( 'td-cta td-cta--' . ( $td_sand ? 'sand' : 'dark' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container">
		<div class="td-cta__panel">
			<?php if ( ! empty( $td_a['arch'] ) ) : ?>
				<?php if ( $td_sand ) : ?>
					<svg class="td-cta__arch td-cta__arch--ink" viewBox="0 0 400 480" aria-hidden="true" focusable="false"><path d="M40 480 V190 A160 160 0 0 1 360 190 V480" fill="none" stroke="currentColor" stroke-width="36"/><path d="M200 60 V480" stroke="currentColor" stroke-width="28"/></svg>
				<?php else : ?>
					<svg class="td-cta__arch" viewBox="0 0 400 480" aria-hidden="true" focusable="false"><defs><linearGradient id="<?php echo esc_attr( $td_grad ); ?>" x1="0" y1="0" x2="400" y2="480" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#D62F14"/><stop offset="1" stop-color="#F98A0F"/></linearGradient></defs><path d="M40 480 V190 A160 160 0 0 1 360 190 V480" fill="none" stroke="url(#<?php echo esc_attr( $td_grad ); ?>)" stroke-width="36"/><path d="M200 60 V480" stroke="url(#<?php echo esc_attr( $td_grad ); ?>)" stroke-width="28"/></svg>
				<?php endif; ?>
			<?php endif; ?>
			<h2 class="td-cta__title"><?php echo td_accent( (string) ( $td_a['title'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
			<?php if ( ! empty( $td_a['lead'] ) ) : ?>
				<p class="td-cta__lead"><?php echo td_mark_placeholders( esc_html( (string) $td_a['lead'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
			<?php
			if ( ! empty( $td_a['buttonLabel'] ) ) {
				echo td_button( (string) $td_a['buttonLabel'], $td_url, $td_sand ? 'primary' : 'light', [ 'class' => 'td-cta__btn' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
	</div>
</section>
