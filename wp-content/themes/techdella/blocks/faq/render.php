<?php
/**
 * FAQ block. Plain content: no FAQPage schema (§8.2).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['question'] ) );
if ( ! $td_items ) {
	return;
}
$td_bg = 'paper' === ( $attributes['background'] ?? '' ) ? 'paper' : 'canvas';
?>
<section <?php echo td_block_wrapper( 'td-faq td-faq--' . $td_bg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-faq__inner">
		<?php if ( ! empty( $attributes['title'] ) ) : ?>
			<h2 class="td-faq__title"><?php echo td_accent( (string) $attributes['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
		<?php endif; ?>
		<?php foreach ( array_values( $td_items ) as $td_i => $td_item ) : ?>
			<details class="td-faq__item"<?php echo 0 === $td_i && ! empty( $attributes['openFirst'] ) ? ' open' : ''; ?>>
				<summary class="td-faq__q"><span><?php echo esc_html( (string) $td_item['question'] ); ?></span><span class="td-faq__toggle" aria-hidden="true"><?php echo td_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></summary>
				<div class="td-faq__a">
					<?php
					foreach ( preg_split( '/\n\s*\n/', trim( (string) ( $td_item['answer'] ?? '' ) ) ) as $td_para ) {
						echo '<p>' . td_mark_placeholders( esc_html( trim( (string) $td_para ) ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</details>
		<?php endforeach; ?>
	</div>
</section>
