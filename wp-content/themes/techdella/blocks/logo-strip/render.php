<?php
/**
 * Logo strip block.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_logos = array_filter( (array) ( $attributes['logos'] ?? [] ), static fn( $l ) => is_array( $l ) && ! empty( $l['name'] ) );
if ( ! $td_logos ) {
	return;
}
$td_label = (string) ( $attributes['label'] ?? '' );
$td_heading_id = wp_unique_id( 'td-logos-' );
?>
<section <?php echo td_block_wrapper( 'td-logo-strip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-labelledby="<?php echo esc_attr( $td_heading_id ); ?>">
	<div class="td-container td-logo-strip__inner">
		<h2 class="td-logo-strip__label" id="<?php echo esc_attr( $td_heading_id ); ?>"><?php echo nl2br( esc_html( '' !== $td_label ? $td_label : __( 'Clients', 'techdella' ) ) ); ?></h2>
		<ul class="td-logo-strip__list" role="list">
			<?php foreach ( $td_logos as $td_logo ) : ?>
				<?php
				$td_h    = max( 10, min( 48, (int) ( $td_logo['height'] ?? 24 ) ) );
				$td_img  = $td_logo['image'] ?? '';
				$td_name = (string) $td_logo['name'];
				$td_key  = is_string( $td_img ) ? $td_img : '';
				$td_m    = '' !== $td_key ? ( td_image_manifest()[ $td_key ] ?? null ) : null;
				$td_w    = $td_m ? (int) round( $td_m['width'] * $td_h / max( 1, $td_m['height'] ) ) : 0;
				?>
				<li class="td-logo-strip__item">
					<?php if ( $td_m || ( is_numeric( $td_img ) && (int) $td_img > 0 ) ) : ?>
						<?php
						echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							$td_img,
							[
								'alt'       => $td_name,
								'sizes'     => ( $td_w ? $td_w : 160 ) . 'px',
								'width'     => $td_w,
								'img_class' => 'td-logo-strip__img',
								'style'     => 'height:' . $td_h . 'px;width:' . ( $td_w ? $td_w . 'px' : 'auto' ),
							]
						);
						?>
					<?php else : ?>
						<span class="td-logo-strip__text" style="<?php echo esc_attr( '--td-logo-h:' . $td_h . 'px' ); ?>"><?php echo esc_html( $td_name ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
