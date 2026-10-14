<?php
/**
 * Tabs. Server output is a stack of panels (works without JS); view.js turns it
 * into ARIA tabs with roving tabindex.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_values( array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['label'] ) ) );
if ( ! $td_items ) {
	return;
}
$td_uid   = ! empty( $attributes['anchor'] ) ? sanitize_html_class( (string) $attributes['anchor'] ) . '-tabs' : wp_unique_id( 'td-tabs-' );
$td_text  = static fn( $v ): string => td_mark_placeholders( esc_html( (string) $v ) );
$td_label = (string) ( $attributes['ariaLabel'] ?? '' );
$td_label = '' !== $td_label ? $td_label : wp_strip_all_tags( str_replace( '*', '', (string) ( $attributes['title'] ?? '' ) ) );
$td_mocks = [ 'ad-mock', 'serp-mock', 'ai-answer-mock', 'report-mock' ];
?>
<section <?php echo td_block_wrapper( 'td-tabs td-section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-tabs__inner" data-td-tabs>
		<?php
		echo td_section_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			[
				'label' => (string) ( $attributes['label'] ?? '' ),
				'title' => (string) ( $attributes['title'] ?? '' ),
				'intro' => (string) ( $attributes['intro'] ?? '' ),
			]
		);
		?>
		<div class="td-tabs__list" role="tablist" aria-label="<?php echo esc_attr( $td_label ); ?>" hidden>
			<?php foreach ( $td_items as $td_i => $td_item ) : ?>
				<button type="button" class="td-tabs__tab" role="tab" id="<?php echo esc_attr( "{$td_uid}-tab-{$td_i}" ); ?>" aria-controls="<?php echo esc_attr( "{$td_uid}-panel-{$td_i}" ); ?>" aria-selected="<?php echo 0 === $td_i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $td_i ? '0' : '-1'; ?>"><?php echo esc_html( (string) $td_item['label'] ); ?></button>
			<?php endforeach; ?>
		</div>
		<div class="td-tabs__panels">
			<?php foreach ( $td_items as $td_i => $td_item ) : ?>
				<?php
				$td_dark   = ! empty( $td_item['dark'] );
				$td_visual = '';
				$td_mock   = is_array( $td_item['mock'] ?? null ) ? $td_item['mock'] : [];
				if ( $td_mock && in_array( $td_mock['block'] ?? '', $td_mocks, true ) ) {
					$td_name = (string) $td_mock['block'];
					unset( $td_mock['block'] );
					$td_mock['contained'] = false;
					$td_visual            = td_block( $td_name, $td_mock );
				} elseif ( ! empty( $td_item['image'] ) ) {
					$td_visual = td_image(
						$td_item['image'],
						[
							'alt'   => (string) ( $td_item['imageAlt'] ?? '' ),
							'sizes' => '(max-width: 900px) 100vw, 520px',
						]
					);
				}
				?>
				<div class="td-tabs__panel" id="<?php echo esc_attr( "{$td_uid}-panel-{$td_i}" ); ?>" data-td-tab-panel>
					<div class="td-tabs__copy<?php echo $td_dark ? ' td-tabs__copy--dark' : ''; ?>">
						<?php if ( ! empty( $td_item['job'] ) ) : ?>
							<span class="td-tabs__job"><?php echo esc_html( (string) $td_item['job'] ); ?></span>
						<?php endif; ?>
						<h3 class="td-tabs__title"><?php echo esc_html( (string) ( $td_item['title'] ?? $td_item['label'] ) ); ?>
							<?php if ( ! empty( $td_item['subtitle'] ) ) : ?>
								<span class="td-tabs__subtitle"><?php echo esc_html( (string) $td_item['subtitle'] ); ?></span>
							<?php endif; ?>
						</h3>
						<?php if ( ! empty( $td_item['text'] ) ) : ?>
							<p class="td-tabs__text"><?php echo $td_text( $td_item['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $td_item['facts'] ) && is_array( $td_item['facts'] ) ) : ?>
							<dl class="td-tabs__facts">
								<?php foreach ( $td_item['facts'] as $td_fact ) : ?>
									<?php
									if ( ! is_array( $td_fact ) || empty( $td_fact['label'] ) ) {
										continue;
									}
									?>
									<div class="td-tabs__fact"><dt><?php echo esc_html( (string) $td_fact['label'] ); ?></dt><dd><?php echo $td_text( $td_fact['text'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd></div>
								<?php endforeach; ?>
							</dl>
						<?php endif; ?>
						<?php if ( ! empty( $td_item['bullets'] ) && is_array( $td_item['bullets'] ) ) : ?>
							<ul class="td-tabs__bullets">
								<?php foreach ( $td_item['bullets'] as $td_b ) : ?>
									<li><?php echo $td_text( $td_b ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
					<?php if ( '' !== $td_visual ) : ?>
						<div class="td-tabs__visual"><?php echo $td_visual; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
