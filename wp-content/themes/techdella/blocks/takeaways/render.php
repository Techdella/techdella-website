<?php
/**
 * Key takeaways block (article component).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = td_rows( $attributes['items'] ?? [] );
if ( ! $td_items ) {
	return;
}
?>
<section <?php echo td_block_wrapper( 'td-takeaways' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( (string) ( $attributes['title'] ?? __( 'Key takeaways', 'techdella' ) ) ); ?>">
	<p class="td-takeaways__title"><?php echo esc_html( (string) ( $attributes['title'] ?? __( 'Key takeaways', 'techdella' ) ) ); ?></p>
	<ul class="td-takeaways__list">
		<?php foreach ( $td_items as $td_item ) : ?>
			<li><?php echo td_lite_inline( (string) $td_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
		<?php endforeach; ?>
	</ul>
</section>
