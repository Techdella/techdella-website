<?php
/**
 * Numbered steps block (article component).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_values( array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['title'] ) ) );
if ( ! $td_items ) {
	return;
}
?>
<section <?php echo td_block_wrapper( 'td-numsteps' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( (string) ( $attributes['title'] ?? __( 'Steps', 'techdella' ) ) ); ?>">
	<?php if ( ! empty( $attributes['title'] ) ) : ?>
		<p class="td-numsteps__title"><?php echo esc_html( (string) $attributes['title'] ); ?></p>
	<?php endif; ?>
	<ol class="td-numsteps__list">
		<?php foreach ( $td_items as $td_s ) : ?>
			<li class="td-numsteps__item">
				<p class="td-numsteps__name"><?php echo td_lite_inline( (string) $td_s['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php if ( ! empty( $td_s['text'] ) ) : ?>
					<div class="td-numsteps__text"><?php echo td_lite( (string) $td_s['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
