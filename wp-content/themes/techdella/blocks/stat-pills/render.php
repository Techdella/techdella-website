<?php
/**
 * Stat pills block.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_a     = $attributes;
$td_stats = array_values( array_filter( (array) ( $td_a['stats'] ?? [] ), 'is_array' ) );
$td_pill  = static function ( array $s, string $mod = '' ): string {
	return '<li class="td-stat-pills__pill' . ( $mod ? ' td-stat-pills__pill--' . $mod : '' ) . '"><strong class="td-stat-pills__value">' . esc_html( (string) ( $s['value'] ?? '' ) ) . '</strong><span class="td-stat-pills__label">' . td_mark_placeholders( esc_html( (string) ( $s['label'] ?? '' ) ) ) . '</span></li>';
};
$td_deco  = static fn( string $mod = '' ): string => '<li class="td-stat-pills__deco' . ( $mod ? ' td-stat-pills__deco--' . $mod : '' ) . '" aria-hidden="true">' . ( 'dot' === $mod ? '<span class="td-dot"></span>' : '' ) . '</li>';
// Optional fills for the outline pills: a client logo (first) and a product/site shot (last).
$td_media = static function ( $image, string $alt, string $mod ) use ( $td_deco ): string {
	if ( empty( $image ) ) {
		return $td_deco();
	}
	$html = td_image(
		$image,
		[
			'alt'       => $alt,
			'sizes'     => 'logo' === $mod ? '160px' : '220px',
			'img_class' => 'td-stat-pills__img',
		]
	);
	return '<li class="td-stat-pills__deco td-stat-pills__deco--' . $mod . '"' . ( '' === $alt ? ' aria-hidden="true"' : '' ) . '>' . $html . '</li>';
};
$td_url   = (string) ( $td_a['linkUrl'] ?? '' );
$td_url   = str_starts_with( $td_url, '/' ) ? home_url( $td_url ) : $td_url;
?>
<section <?php echo td_block_wrapper( 'td-stat-pills' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-stat-pills__inner">
		<div class="td-stat-pills__head">
			<?php echo td_chip( (string) ( $td_a['label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( ! empty( $td_a['title'] ) ) : ?>
				<h2 class="td-stat-pills__title"><?php echo td_accent( (string) $td_a['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $td_a['meta'] ) ) : ?>
				<p class="td-stat-pills__meta"><?php echo td_mark_placeholders( esc_html( (string) $td_a['meta'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $td_stats ) : ?>
			<div class="td-stat-pills__rows">
				<ul class="td-stat-pills__row" role="list">
					<?php
					echo $td_media( $td_a['logo'] ?? '', (string) ( $td_a['logoAlt'] ?? '' ), 'logo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $td_pill( $td_stats[0], 'wide' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $td_media( $td_a['image'] ?? '', (string) ( $td_a['imageAlt'] ?? '' ), 'image' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</ul>
				<?php if ( count( $td_stats ) > 1 ) : ?>
					<ul class="td-stat-pills__row" role="list">
						<?php
						echo $td_pill( $td_stats[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $td_deco( 'dot' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						if ( isset( $td_stats[2] ) ) {
							echo $td_pill( $td_stats[2], 'dark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $td_a['linkLabel'] ) && '' !== $td_url ) : ?>
			<a class="td-link td-stat-pills__link" href="<?php echo esc_url( $td_url ); ?>"><?php echo esc_html( (string) $td_a['linkLabel'] ); ?></a>
		<?php endif; ?>
	</div>
</section>
