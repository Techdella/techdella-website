<?php
/**
 * Before / after comparison. Rendered as a two-item list so it reads in order without visuals.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_sides = [
	'before' => is_array( $attributes['before'] ?? null ) ? $attributes['before'] : [],
	'after'  => is_array( $attributes['after'] ?? null ) ? $attributes['after'] : [],
];
if ( ! array_filter( $td_sides ) ) {
	return;
}
$td_layout  = 'center' === ( $attributes['layout'] ?? '' ) ? 'center' : 'split';
$td_surface = in_array( $attributes['surface'] ?? '', [ 'paper', 'sand' ], true ) ? $attributes['surface'] : 'canvas';
$td_after   = 'dark' === ( $attributes['afterStyle'] ?? '' ) ? 'dark' : 'paper';
$td_class   = 'td-before-after td-before-after--' . $td_layout . ' td-section' . ( 'paper' !== $td_surface ? ' td-section--' . $td_surface : '' ) . ( ! empty( $attributes['strike'] ) ? ' td-before-after--strike' : '' );
$td_labels  = [
	'before' => __( 'Before', 'techdella' ),
	'after'  => __( 'After', 'techdella' ),
];
?>
<section <?php echo td_block_wrapper( $td_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-before-after__inner">
		<?php if ( ! empty( $attributes['title'] ) || ! empty( $attributes['label'] ) ) : ?>
			<div class="td-before-after__head">
				<?php echo td_chip( (string) ( $attributes['label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( ! empty( $attributes['title'] ) ) : ?>
					<h2 class="td-before-after__title"><?php echo td_accent( (string) $attributes['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<?php endif; ?>
				<?php if ( ! empty( $attributes['intro'] ) ) : ?>
					<p class="td-before-after__intro"><?php echo td_mark_placeholders( esc_html( (string) $attributes['intro'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<ul class="td-before-after__cards" role="list">
			<?php foreach ( $td_sides as $td_key => $td_side ) : ?>
				<?php
				$td_card = 'td-before-after__card td-before-after__card--' . $td_key . ( 'after' === $td_key ? ' td-before-after__card--' . $td_after : '' );
				$td_mark = 'after' === $td_key ? '✓' : '✕';
				?>
				<li class="<?php echo esc_attr( $td_card ); ?>">
					<span class="td-before-after__label">
						<?php if ( 'after' === $td_key ) : ?>
							<span class="td-dot" aria-hidden="true"></span>
						<?php endif; ?>
						<?php echo esc_html( (string) ( $td_side['label'] ?? $td_labels[ $td_key ] ) ); ?>
					</span>
					<?php if ( ! empty( $td_side['title'] ) ) : ?>
						<span class="td-before-after__card-title"><?php echo esc_html( (string) $td_side['title'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $td_side['text'] ) ) : ?>
						<span class="td-before-after__text"><?php echo td_mark_placeholders( esc_html( (string) $td_side['text'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $td_side['items'] ) && is_array( $td_side['items'] ) ) : ?>
						<ul class="td-before-after__points" role="list">
							<?php foreach ( $td_side['items'] as $td_point ) : ?>
								<li><span class="td-before-after__icon" aria-hidden="true"><?php echo esc_html( $td_mark ); ?></span><?php echo esc_html( (string) $td_point ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
