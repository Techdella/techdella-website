<?php
/**
 * Built for: label + audience pills.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_values( array_filter( array_map( static fn( $i ) => is_array( $i ) ? (string) ( $i['label'] ?? '' ) : (string) $i, (array) ( $attributes['items'] ?? [] ) ) ) );
if ( ! $td_items ) {
	return;
}
$td_id = wp_unique_id( 'td-built-for-' );
?>
<section <?php echo td_block_wrapper( 'td-built-for' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-labelledby="<?php echo esc_attr( $td_id ); ?>">
	<div class="td-container td-built-for__inner">
		<p class="td-built-for__label" id="<?php echo esc_attr( $td_id ); ?>"><?php echo esc_html( (string) ( $attributes['label'] ?? '' ) ); ?></p>
		<ul class="td-built-for__list" role="list">
			<?php foreach ( $td_items as $td_item ) : ?>
				<li><?php echo esc_html( $td_item ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
