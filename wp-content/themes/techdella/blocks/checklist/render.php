<?php
/**
 * Checklist block (article component).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = td_rows( $attributes['items'] ?? [] );
if ( ! $td_items ) {
	return;
}
?>
<section <?php echo td_block_wrapper( 'td-checklist' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( (string) ( $attributes['title'] ?? __( 'Checklist', 'techdella' ) ) ); ?>">
	<?php if ( ! empty( $attributes['title'] ) ) : ?>
		<p class="td-checklist__title"><?php echo esc_html( (string) $attributes['title'] ); ?></p>
	<?php endif; ?>
	<ul class="td-checklist__list">
		<?php foreach ( $td_items as $td_item ) : ?>
			<li><span class="td-checklist__box" aria-hidden="true"></span><span><?php echo td_lite_inline( (string) $td_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></li>
		<?php endforeach; ?>
	</ul>
</section>
