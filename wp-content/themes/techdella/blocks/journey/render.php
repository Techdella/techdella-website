<?php
/**
 * Journey: ordered stages, horizontal on desktop, vertical on phones.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';

$td_stages = array_values( array_filter( (array) ( $attributes['stages'] ?? [] ), static fn( $s ) => is_array( $s ) && ! empty( $s['title'] ) ) );
if ( ! $td_stages ) {
	echo td_a3_notice( __( 'Journey: add stages.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_hl      = (int) ( $attributes['highlight'] ?? -1 );
$td_surface = in_array( $attributes['surface'] ?? '', [ 'paper', 'sand' ], true ) ? (string) $attributes['surface'] : 'canvas';
$td_has_h   = ! empty( $attributes['title'] );
?>
<section <?php echo td_block_wrapper( 'td-journey td-section td-journey--' . $td_surface ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-journey__inner">
		<?php if ( $td_has_h || ! empty( $attributes['label'] ) ) : ?>
			<div class="td-journey__head">
				<?php echo td_chip( (string) ( $attributes['label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $td_has_h ) : ?>
					<h2 class="td-journey__title"><?php echo td_accent( (string) $attributes['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<?php endif; ?>
				<?php if ( ! empty( $attributes['intro'] ) ) : ?>
					<p class="td-journey__intro"><?php echo td_a3_text( $attributes['intro'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<ol class="td-journey__stages" style="<?php echo esc_attr( '--td-journey-n:' . count( $td_stages ) ); ?>">
			<?php foreach ( $td_stages as $td_i => $td_s ) : ?>
				<li class="td-journey__stage<?php echo $td_i === $td_hl ? ' td-journey__stage--hl' : ''; ?>">
					<span class="td-journey__num" aria-hidden="true"><?php echo esc_html( (string) ( $td_i + 1 ) ); ?></span>
					<?php if ( $td_has_h ) : ?>
						<h3 class="td-journey__name"><?php echo td_a3_text( $td_s['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
					<?php else : ?>
						<strong class="td-journey__name"><?php echo td_a3_text( $td_s['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					<?php endif; ?>
					<?php if ( ! empty( $td_s['text'] ) ) : ?>
						<p class="td-journey__text"><?php echo td_a3_text( $td_s['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
