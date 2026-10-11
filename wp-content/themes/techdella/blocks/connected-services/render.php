<?php
/**
 * Connected services strip (SEO / AISearch / Automation "CONNECTED" sections).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['title'] ) && ! empty( $i['url'] ) );
if ( ! $td_items ) {
	return;
}
$td_href    = static function ( string $url ): string {
	if ( preg_match( '/^(marketing|journal|studios):(\/.*)$/', $url, $m ) ) {
		return td_url( $m[1], $m[2] );
	}
	return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
};
$td_path    = static fn( string $url ): string => trailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
$td_current = (string) ( $attributes['current'] ?? '' );
$td_current = '' !== $td_current ? $td_path( $td_href( $td_current ) ) : ( isset( $_SERVER['REQUEST_URI'] ) ? $td_path( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '' );
$td_surface = in_array( $attributes['surface'] ?? '', [ 'paper', 'canvas' ], true ) ? $attributes['surface'] : 'dark';
?>
<section <?php echo td_block_wrapper( 'td-connected td-section' . ( 'paper' !== $td_surface ? ' td-section--' . $td_surface : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-connected__inner">
		<?php
		echo td_section_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			[
				'label' => (string) ( $attributes['label'] ?? '' ),
				'title' => (string) ( $attributes['title'] ?? '' ),
				'intro' => (string) ( $attributes['intro'] ?? '' ),
			]
		);
		?>
		<ul class="td-connected__grid" role="list">
			<?php
			foreach ( $td_items as $td_item ) :
				$td_url = $td_href( (string) $td_item['url'] );
				$td_on  = '' !== $td_current && $td_path( $td_url ) === $td_current;
				?>
				<li>
					<a class="td-connected__card<?php echo $td_on ? ' td-connected__card--current' : ''; ?>" href="<?php echo esc_url( $td_url ); ?>"<?php echo $td_on ? ' aria-current="page"' : ''; ?>>
						<span class="td-connected__relation">
							<?php if ( $td_on ) : ?>
								<span class="td-dot" aria-hidden="true"></span><?php esc_html_e( 'You are here', 'techdella' ); ?>
							<?php else : ?>
								<?php echo esc_html( (string) ( $td_item['relation'] ?? '' ) ); ?>
							<?php endif; ?>
						</span>
						<strong class="td-connected__title"><?php echo esc_html( (string) $td_item['title'] ); ?></strong>
						<?php if ( ! empty( $td_item['text'] ) ) : ?>
							<span class="td-connected__text"><?php echo esc_html( (string) $td_item['text'] ); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( ! empty( $attributes['footerLabel'] ) && ! empty( $attributes['footerUrl'] ) ) : ?>
			<a class="td-connected__footer" href="<?php echo esc_url( $td_href( (string) $attributes['footerUrl'] ) ); ?>"><?php echo esc_html( (string) $attributes['footerLabel'] ); ?><span class="td-connected__footer-arrow"><?php echo td_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
		<?php endif; ?>
	</div>
</section>
