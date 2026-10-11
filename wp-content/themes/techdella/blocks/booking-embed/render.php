<?php
/**
 * Booking embed: a Startbuddi booking page in an iframe (lazy), with a plain link as fallback.
 * Only https://app.startbuddi.com/ is embedded.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_src  = esc_url_raw( trim( (string) ( $attributes['src'] ?? '' ) ) );
$td_host = (string) wp_parse_url( $td_src, PHP_URL_HOST );
if ( '' === $td_src || 'https' !== wp_parse_url( $td_src, PHP_URL_SCHEME ) || ! in_array( $td_host, (array) apply_filters( 'td_booking_embed_hosts', [ 'app.startbuddi.com' ] ), true ) ) {
	echo td_a3_notice( __( 'Booking embed: add a Startbuddi booking link (https://app.startbuddi.com/…).', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_height = max( 480, min( 1400, (int) ( $attributes['height'] ?? 760 ) ) );
$td_ftitle = trim( (string) ( $attributes['frameTitle'] ?? '' ) );
$td_ftitle = '' !== $td_ftitle ? $td_ftitle : __( 'Book a call', 'techdella' );
$td_title  = trim( (string) ( $attributes['title'] ?? '' ) );
$td_head   = 'td-booking-' . substr( md5( $td_src ), 0, 8 );
?>
<section <?php echo td_block_wrapper( 'td-section td-booking' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo '' !== $td_title ? ' aria-labelledby="' . esc_attr( $td_head ) . '"' : ''; ?>>
	<div class="td-container td-booking__inner">
		<?php if ( '' !== $td_title || ! empty( $attributes['label'] ) ) : ?>
			<div class="td-booking__head">
				<?php if ( ! empty( $attributes['label'] ) ) : ?>
					<?php echo td_chip( (string) $attributes['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
				<?php if ( '' !== $td_title ) : ?>
					<h2 class="td-booking__title" id="<?php echo esc_attr( $td_head ); ?>"><?php echo td_accent( $td_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<?php endif; ?>
				<?php if ( ! empty( $attributes['lead'] ) ) : ?>
					<p class="td-booking__lead"><?php echo esc_html( (string) $attributes['lead'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="td-booking__frame" style="--td-booking-h:<?php echo (int) $td_height; ?>px">
			<iframe src="<?php echo esc_url( $td_src ); ?>" title="<?php echo esc_attr( $td_ftitle ); ?>" width="100%" height="<?php echo (int) $td_height; ?>" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="clipboard-write; payment"></iframe>
		</div>
		<p class="td-booking__fallback">
			<?php
			printf(
				/* translators: %s: link to the booking page */
				esc_html__( 'Calendar not loading? %s', 'techdella' ),
				'<a class="td-link" href="' . esc_url( $td_src ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open the booking page in a new tab', 'techdella' ) . '</a>'
			);
			?>
		</p>
	</div>
</section>
