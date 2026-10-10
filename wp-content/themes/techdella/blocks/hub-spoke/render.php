<?php
/**
 * Hub & spoke (Options B). Nodes on an ellipse around the centre; SVG spokes are decorative.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_slice( array_values( array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['title'] ) ) ), 0, 8 );
if ( ! $td_items ) {
	return;
}
$td_href = static function ( string $url ): string {
	if ( preg_match( '/^(marketing|journal|studios):(\/.*)$/', $url, $m ) ) {
		return td_url( $m[1], $m[2] );
	}
	return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
};
$td_n    = count( $td_items );
$td_pos  = [];
foreach ( $td_items as $td_i => $td_item ) {
	$td_angle        = -M_PI / 2 + ( 2 * M_PI * $td_i / $td_n );
	$td_pos[ $td_i ] = [ round( 50 + 37 * cos( $td_angle ), 2 ), round( 50 + 40 * sin( $td_angle ), 2 ) ];
}
?>
<section <?php echo td_block_wrapper( 'td-hub td-section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-hub__inner">
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
		<div class="td-hub__map">
			<svg class="td-hub__lines" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
				<ellipse cx="50" cy="50" rx="37" ry="40" fill="none" stroke="var(--td-line)" stroke-width="1.5" vector-effect="non-scaling-stroke"/>
				<ellipse cx="50" cy="50" rx="22" ry="24" fill="none" stroke="var(--td-sand)" stroke-width="1.2" stroke-dasharray="4 7" vector-effect="non-scaling-stroke"/>
				<?php foreach ( $td_pos as $td_p ) : ?>
					<line x1="50" y1="50" x2="<?php echo esc_attr( (string) $td_p[0] ); ?>" y2="<?php echo esc_attr( (string) $td_p[1] ); ?>" stroke="#D6D1CA" stroke-width="1.4" vector-effect="non-scaling-stroke"/>
				<?php endforeach; ?>
			</svg>
			<div class="td-hub__center">
				<?php echo td_logo( 'mark', 54 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( (string) ( $attributes['centerLabel'] ?? '' ) ); ?></span>
			</div>
			<ul class="td-hub__nodes" role="list">
				<?php foreach ( $td_items as $td_i => $td_item ) : ?>
					<?php
					$td_tone = in_array( $td_item['tone'] ?? '', [ 'dark', 'sand' ], true ) ? $td_item['tone'] : 'paper';
					$td_tag  = ! empty( $td_item['url'] ) ? 'a' : 'span';
					?>
					<li class="td-hub__node" style="<?php echo esc_attr( '--x:' . $td_pos[ $td_i ][0] . '%;--y:' . $td_pos[ $td_i ][1] . '%' ); ?>">
						<<?php echo esc_html( $td_tag ); ?> class="td-hub__card td-hub__card--<?php echo esc_attr( $td_tone ); ?>"<?php echo 'a' === $td_tag ? ' href="' . esc_url( $td_href( (string) $td_item['url'] ) ) . '"' : ''; ?>>
							<?php if ( ! empty( $td_item['image'] ) ) : ?>
								<span class="td-hub__thumb">
									<?php
									echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										$td_item['image'],
										[
											'alt'   => '',
											'sizes' => '40px',
											'width' => 40,
										]
									);
									?>
								</span>
							<?php endif; ?>
							<span class="td-hub__copy">
								<strong><?php echo esc_html( (string) $td_item['title'] ); ?></strong>
								<?php if ( ! empty( $td_item['text'] ) ) : ?>
									<span><?php echo esc_html( (string) $td_item['text'] ); ?></span>
								<?php endif; ?>
							</span>
						</<?php echo esc_html( $td_tag ); ?>>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
