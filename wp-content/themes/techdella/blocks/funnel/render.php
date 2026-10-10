<?php
/**
 * Funnel stages with channel chips.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_values( array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['title'] ) ) );
if ( ! $td_items ) {
	return;
}
$td_href = static function ( string $url ): string {
	if ( preg_match( '/^(marketing|journal|studios):(\/.*)$/', $url, $m ) ) {
		return td_url( $m[1], $m[2] );
	}
	return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
};
?>
<section <?php echo td_block_wrapper( 'td-funnel td-section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-funnel__inner">
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
		<ol class="td-funnel__stages" role="list">
			<?php foreach ( $td_items as $td_i => $td_item ) : ?>
				<?php $td_tone = in_array( $td_item['tone'] ?? '', [ 'sand', 'dark' ], true ) ? $td_item['tone'] : 'canvas'; ?>
				<li class="td-funnel__stage td-funnel__stage--<?php echo esc_attr( $td_tone ); ?>">
					<span class="td-funnel__step"><?php echo esc_html( str_pad( (string) ( $td_i + 1 ), 2, '0', STR_PAD_LEFT ) . ' · ' . mb_strtoupper( (string) ( $td_item['stage'] ?? '' ) ) ); ?></span>
					<h3 class="td-funnel__title"><?php echo esc_html( (string) $td_item['title'] ); ?></h3>
					<?php if ( ! empty( $td_item['text'] ) ) : ?>
						<p class="td-funnel__text"><?php echo esc_html( (string) $td_item['text'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $td_item['chips'] ) && is_array( $td_item['chips'] ) ) : ?>
						<ul class="td-funnel__chips" role="list" aria-label="<?php esc_attr_e( 'Channels', 'techdella' ); ?>">
							<?php foreach ( $td_item['chips'] as $td_chip ) : ?>
								<?php
								if ( ! is_array( $td_chip ) || empty( $td_chip['label'] ) ) {
									continue;
								}
								?>
								<li>
									<?php if ( ! empty( $td_chip['url'] ) ) : ?>
										<a class="td-funnel__chip td-funnel__chip--link" href="<?php echo esc_url( $td_href( (string) $td_chip['url'] ) ); ?>"><?php echo esc_html( (string) $td_chip['label'] ); ?></a>
									<?php else : ?>
										<span class="td-funnel__chip"><?php echo esc_html( (string) $td_chip['label'] ); ?></span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
