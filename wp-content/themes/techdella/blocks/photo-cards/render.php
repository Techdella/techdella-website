<?php
/**
 * Photo + cards (Studios).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';

$td_cards = array_slice( array_values( array_filter( (array) ( $attributes['cards'] ?? [] ), static fn( $c ) => is_array( $c ) && ! empty( $c['title'] ) ) ), 0, 4 );
if ( empty( $attributes['title'] ) && ! $td_cards ) {
	echo td_a3_notice( __( 'Photo + cards: add a title and cards.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_icons = [
	'scope'     => '<path d="M4 6h16M4 12h10M4 18h7"/>',
	'milestone' => '<path d="M5 21V4h11l-2 4 2 4H5"/>',
	'review'    => '<path d="M5 12l5 5L20 7"/>',
	'clock'     => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
];
$td_img   = ! empty( $attributes['image'] ) ? td_image(
	$attributes['image'],
	[
		'alt'   => '',
		'sizes' => '(max-width: 1280px) 100vw, 1240px',
	]
) : '';
$td_img   = str_starts_with( $td_img, '<!--' ) ? '' : $td_img;
?>
<section <?php echo td_block_wrapper( 'td-photo-cards' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container">
		<div class="td-photo-cards__panel<?php echo '' === $td_img ? ' td-photo-cards__panel--plain' : ''; ?>">
			<?php if ( '' !== $td_img ) : ?>
				<div class="td-photo-cards__bg" aria-hidden="true"><?php echo $td_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>
			<div class="td-photo-cards__copy">
				<?php if ( ! empty( $attributes['title'] ) ) : ?>
					<h2 class="td-photo-cards__title"><?php echo td_accent( (string) $attributes['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<?php endif; ?>
				<?php if ( ! empty( $attributes['intro'] ) ) : ?>
					<p class="td-photo-cards__intro"><?php echo td_a3_text( $attributes['intro'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $td_cards ) : ?>
				<ul class="td-photo-cards__cards" role="list">
					<?php foreach ( $td_cards as $td_i => $td_c ) : ?>
						<li class="td-photo-cards__card<?php echo 0 === $td_i ? ' td-photo-cards__card--sand' : ''; ?>">
							<span class="td-photo-cards__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><?php echo $td_icons[ $td_c['icon'] ?? '' ] ?? $td_icons['review']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG paths. ?></svg></span>
							<h3 class="td-photo-cards__card-title"><?php echo td_a3_text( $td_c['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
							<?php if ( ! empty( $td_c['linkLabel'] ) && ! empty( $td_c['url'] ) ) : ?>
								<a class="td-photo-cards__link" href="<?php echo esc_url( td_a3_href( (string) $td_c['url'] ) ); ?>"><?php echo esc_html( (string) $td_c['linkLabel'] ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
