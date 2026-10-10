<?php
/**
 * Studios hero with phone screens (real screen images when supplied, neutral UI placeholders otherwise).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';

$td_screens = array_slice( array_values( array_filter( (array) ( $attributes['screens'] ?? [] ), static fn( $s ) => is_array( $s ) && ( ! empty( $s['title'] ) || ! empty( $s['image'] ) ) ) ), 0, 3 );

/**
 * Neutral placeholder UI for a phone screen.
 *
 * @param string $kind list|dashboard|toggle.
 */
$td_placeholder = static function ( string $kind ): string {
	switch ( $kind ) {
		case 'dashboard':
			return '<span class="td-phone__search"></span><span class="td-phone__pills"><i class="is-on"></i><i></i><i></i></span><span class="td-phone__panel"><i></i><b></b></span><span class="td-phone__grid"><i></i><i></i></span>';
		case 'toggle':
			return '<span class="td-phone__big"></span><svg class="td-phone__chart" viewBox="0 0 200 60" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 50 C30 40 50 44 70 30 S120 10 140 22 180 8 200 6" fill="none" stroke="currentColor" stroke-width="3"/></svg><span class="td-phone__grid"><i></i><i class="is-on"></i></span>';
		default:
			return '<span class="td-phone__rows"><i></i><i></i><i></i><i></i></span>';
	}
};
?>
<section <?php echo td_block_wrapper( 'td-studio-hero' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container">
		<div class="td-studio-hero__panel">
			<div class="td-studio-hero__copy">
				<?php if ( ! empty( $attributes['chip'] ) ) : ?>
					<?php echo td_chip( (string) $attributes['chip'], 'td-chip--dark td-studio-hero__chip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
				<h1 class="td-studio-hero__title"><?php echo td_accent( (string) ( $attributes['title'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
				<?php if ( ! empty( $attributes['lead'] ) ) : ?>
					<p class="td-studio-hero__lead"><?php echo td_a3_text( $attributes['lead'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
				<div class="td-studio-hero__ctas">
					<?php
					if ( ! empty( $attributes['primaryLabel'] ) ) {
						echo td_button( (string) $attributes['primaryLabel'], td_a3_href( (string) ( $attributes['primaryUrl'] ?? '/' ) ), 'light' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					if ( ! empty( $attributes['secondaryLabel'] ) ) :
						?>
						<a class="td-btn td-studio-hero__ghost" href="<?php echo esc_url( td_a3_href( (string) ( $attributes['secondaryUrl'] ?? '#' ) ) ); ?>"><?php echo esc_html( (string) $attributes['secondaryLabel'] ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $td_screens ) : ?>
				<ul class="td-studio-hero__screens" role="list" aria-label="<?php esc_attr_e( 'App screens', 'techdella' ); ?>">
					<?php foreach ( $td_screens as $td_i => $td_s ) : ?>
						<?php
						$td_img = ! empty( $td_s['image'] ) ? td_image(
							$td_s['image'],
							[
								'alt'      => (string) ( $td_s['alt'] ?? '' ),
								'sizes'    => '260px',
								'priority' => 0 === $td_i,
							]
						) : '';
						$td_img = str_starts_with( $td_img, '<!--' ) ? '' : $td_img;
						$td_cls = 'td-phone td-phone--' . ( $td_i + 1 ) . ( ! empty( $td_s['concept'] ) ? ' td-phone--concept' : '' ) . ( '' === $td_img ? ' td-phone--placeholder' : '' );
						?>
						<li class="<?php echo esc_attr( $td_cls ); ?>">
							<?php if ( ! empty( $td_s['concept'] ) ) : ?>
								<span class="td-label-example"><?php esc_html_e( 'Concept', 'techdella' ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $td_img ) : ?>
								<?php echo $td_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php else : ?>
								<span class="td-phone__head"><strong><?php echo esc_html( (string) ( $td_s['title'] ?? '' ) ); ?></strong><span class="td-sr"> <?php esc_html_e( '(placeholder screen)', 'techdella' ); ?></span></span>
								<span class="td-phone__ui" aria-hidden="true"><?php echo $td_placeholder( (string) ( $td_s['kind'] ?? 'list' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $td_s['note'] ) ) : ?>
								<span class="td-phone__note"><?php echo td_a3_text( $td_s['note'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
