<?php
/**
 * Myth vs fact block (article component).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_values( array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['myth'] ) ) );
if ( ! $td_items ) {
	return;
}
?>
<section <?php echo td_block_wrapper( 'td-mythfact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( (string) ( $attributes['title'] ?? __( 'Myth vs fact', 'techdella' ) ) ); ?>">
	<?php if ( ! empty( $attributes['title'] ) ) : ?>
		<p class="td-mythfact__title"><?php echo esc_html( (string) $attributes['title'] ); ?></p>
	<?php endif; ?>
	<dl class="td-mythfact__list">
		<?php foreach ( $td_items as $td_m ) : ?>
			<div class="td-mythfact__row">
				<dt class="td-mythfact__myth"><span class="td-mythfact__tag"><?php esc_html_e( 'Myth', 'techdella' ); ?></span><?php echo td_lite_inline( (string) $td_m['myth'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dt>
				<dd class="td-mythfact__fact"><span class="td-mythfact__tag td-mythfact__tag--fact"><?php esc_html_e( 'Fact', 'techdella' ); ?></span><?php echo td_lite_inline( (string) ( $td_m['fact'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd>
			</div>
		<?php endforeach; ?>
	</dl>
</section>
