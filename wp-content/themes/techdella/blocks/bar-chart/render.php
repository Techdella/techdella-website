<?php
/**
 * Bar chart block (article component): one series, horizontal bars, values printed.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_values( array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && isset( $i['label'], $i['value'] ) && is_numeric( $i['value'] ) ) );
if ( ! $td_items ) {
	return;
}
$td_unit = (string) ( $attributes['unit'] ?? '' );
$td_max  = (float) ( $attributes['max'] ?? 0 );
$td_max  = $td_max > 0 ? $td_max : max( array_map( static fn( $i ) => (float) $i['value'], $td_items ) );
$td_max  = $td_max > 0 ? $td_max : 1;
$td_hl   = (int) ( $attributes['highlight'] ?? -1 );
$td_fmt  = static function ( $v ) use ( $td_unit ): string {
	$n = (float) $v;
	$s = number_format_i18n( $n, floor( $n ) === $n ? 0 : 1 );
	if ( '$' === $td_unit ) {
		return '$' . $s;
	}
	return '%' === $td_unit ? $s . '%' : trim( $s . ' ' . $td_unit );
};
?>
<figure <?php echo td_block_wrapper( 'td-barchart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( ! empty( $attributes['title'] ) ) : ?>
		<p class="td-barchart__title"><?php echo esc_html( (string) $attributes['title'] ); ?></p>
	<?php endif; ?>
	<ul class="td-barchart__list" role="list">
		<?php foreach ( $td_items as $td_i => $td_b ) : ?>
			<?php $td_w = max( 1.5, min( 100, ( (float) $td_b['value'] / $td_max ) * 100 ) ); ?>
			<li class="td-barchart__row<?php echo $td_i === $td_hl ? ' td-barchart__row--hl' : ''; ?>" title="<?php echo esc_attr( (string) $td_b['label'] . ': ' . $td_fmt( $td_b['value'] ) ); ?>">
				<span class="td-barchart__label"><?php echo esc_html( (string) $td_b['label'] ); ?></span>
				<span class="td-barchart__track" aria-hidden="true"><span class="td-barchart__bar" style="width:<?php echo esc_attr( (string) round( $td_w, 2 ) ); ?>%"></span></span>
				<span class="td-barchart__value"><?php echo esc_html( $td_fmt( $td_b['value'] ) ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php if ( ! empty( $attributes['source'] ) ) : ?>
		<figcaption class="td-barchart__source">
			<?php
			esc_html_e( 'Source:', 'techdella' );
			echo ' ';
			$td_url = trim( (string) ( $attributes['url'] ?? '' ) );
			echo '' !== $td_url ? td_lite_inline( '[' . (string) $attributes['source'] . '](' . $td_url . ')' ) : esc_html( (string) $attributes['source'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</figcaption>
	<?php endif; ?>
</figure>
