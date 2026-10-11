<?php
/**
 * Stats block (article component): sourced headline numbers.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_slice( array_values( array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && '' !== trim( (string) ( $i['value'] ?? '' ) ) ) ), 0, 4 );
if ( ! $td_items ) {
	return;
}
$td_hl = (int) ( $attributes['highlight'] ?? 0 );
?>
<figure <?php echo td_block_wrapper( 'td-stats td-stats--' . count( $td_items ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( ! empty( $attributes['title'] ) ) : ?>
		<figcaption class="td-stats__title"><?php echo esc_html( (string) $attributes['title'] ); ?></figcaption>
	<?php endif; ?>
	<ul class="td-stats__list" role="list">
		<?php foreach ( $td_items as $td_i => $td_s ) : ?>
			<li class="td-stats__item<?php echo $td_i === $td_hl ? ' td-stats__item--hl' : ''; ?>">
				<strong class="td-stats__value"><?php echo esc_html( (string) $td_s['value'] ); ?></strong>
				<span class="td-stats__label"><?php echo td_lite_inline( (string) ( $td_s['label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php if ( ! empty( $td_s['source'] ) ) : ?>
					<span class="td-stats__source">
						<?php
						$td_src = (string) $td_s['source'];
						$td_url = (string) ( $td_s['url'] ?? '' );
						echo '' !== $td_url ? td_lite_inline( '[' . $td_src . '](' . $td_url . ')' ) : esc_html( $td_src ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</figure>
