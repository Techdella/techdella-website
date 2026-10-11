<?php
/**
 * Timeline (Options B): ordered list, alternating sides on desktop.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_values( array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['title'] ) ) );
if ( ! $td_items ) {
	return;
}
$td_text = static fn( $v ): string => td_mark_placeholders( esc_html( (string) $v ) );
?>
<section <?php echo td_block_wrapper( 'td-timeline td-section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container">
		<?php
		echo td_section_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			[
				'label' => (string) ( $attributes['label'] ?? '' ),
				'title' => (string) ( $attributes['title'] ?? '' ),
				'intro' => (string) ( $attributes['intro'] ?? '' ),
				'align' => 'center',
			]
		);
		?>
		<ol class="td-timeline__list" role="list">
			<?php foreach ( $td_items as $td_i => $td_item ) : ?>
				<li class="td-timeline__step td-timeline__step--<?php echo 0 === $td_i % 2 ? 'left' : 'right'; ?>">
					<span class="td-timeline__num<?php echo 0 === $td_i ? ' td-timeline__num--hl' : ''; ?>" aria-hidden="true"><?php echo esc_html( (string) ( $td_i + 1 ) ); ?></span>
					<div class="td-timeline__card<?php echo ! empty( $td_item['dark'] ) ? ' td-timeline__card--dark' : ''; ?>">
						<h3 class="td-timeline__title"><span class="td-sr"><?php echo esc_html( sprintf( /* translators: %d: step number */ __( 'Step %d:', 'techdella' ), $td_i + 1 ) ); ?> </span><?php echo $td_text( $td_item['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
						<?php if ( ! empty( $td_item['text'] ) ) : ?>
							<p class="td-timeline__text"><?php echo $td_text( $td_item['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $td_item['result'] ) ) : ?>
							<span class="td-timeline__result"><?php echo esc_html( sprintf( /* translators: %s: deliverable */ __( 'You get: %s', 'techdella' ), lcfirst( (string) $td_item['result'] ) ) ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $td_item['image'] ) ) : ?>
						<div class="td-timeline__aside td-timeline__aside--image">
							<?php
							echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$td_item['image'],
								[
									'alt'   => (string) ( $td_item['imageAlt'] ?? '' ),
									'sizes' => '(max-width: 760px) 100vw, 420px',
								]
							);
							?>
						</div>
					<?php elseif ( ! empty( $td_item['note'] ) ) : ?>
						<p class="td-timeline__aside"><?php echo esc_html( (string) $td_item['note'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
