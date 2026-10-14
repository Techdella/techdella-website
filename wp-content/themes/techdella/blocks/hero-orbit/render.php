<?php
/**
 * Hero (orbit) block: Main hero with the platform orbit stage.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_a        = $attributes;
$td_href     = static fn( string $url ): string => str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
$td_avatars  = array_slice( array_filter( (array) ( $td_a['trustAvatars'] ?? [] ) ), 0, 4 );
$td_plats    = array_slice( array_filter( (array) ( $td_a['platforms'] ?? [] ), 'is_array' ), 0, 5 );
$td_stats    = array_slice( array_filter( (array) ( $td_a['stats'] ?? [] ), 'is_array' ), 0, 2 );
$td_lead_chp = trim( (string) ( $td_a['leadChip'] ?? '' ) );
?>
<section <?php echo td_block_wrapper( 'td-hero-orbit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-hero-orbit__grid" aria-hidden="true"></div>
	<div class="td-container td-hero-orbit__inner">
		<?php if ( ! empty( $td_a['chip'] ) ) : ?>
			<span class="td-hero-orbit__chip"><span class="td-dot" aria-hidden="true"></span><?php echo esc_html( (string) $td_a['chip'] ); ?></span>
		<?php endif; ?>
		<h1 class="td-hero-orbit__title"><?php echo td_accent( (string) ( $td_a['title'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
		<?php if ( ! empty( $td_a['lead'] ) ) : ?>
			<p class="td-hero-orbit__lead"><?php echo td_mark_placeholders( esc_html( (string) $td_a['lead'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<div class="td-hero-orbit__ctas">
			<?php
			if ( ! empty( $td_a['primaryLabel'] ) ) {
				echo td_button( (string) $td_a['primaryLabel'], $td_href( (string) ( $td_a['primaryUrl'] ?? '/' ) ), 'primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			if ( ! empty( $td_a['secondaryLabel'] ) ) {
				echo td_button( (string) $td_a['secondaryLabel'], $td_href( (string) ( $td_a['secondaryUrl'] ?? '#' ) ), 'secondary', [ 'class' => 'td-btn--soft' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
		<?php if ( ! empty( $td_a['trustText'] ) ) : ?>
			<p class="td-hero-orbit__trust">
				<?php if ( $td_avatars ) : ?>
					<span class="td-hero-orbit__avatars" aria-hidden="true">
						<?php
						foreach ( $td_avatars as $td_img ) {
							echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$td_img,
								[
									'alt'       => '',
									'sizes'     => '30px',
									'width'     => 30,
									'img_class' => 'td-hero-orbit__avatar',
								]
							);
						}
						?>
					</span>
				<?php endif; ?>
				<?php echo esc_html( (string) $td_a['trustText'] ); ?>
			</p>
		<?php endif; ?>

		<div class="td-hero-orbit__stage">
			<svg class="td-hero-orbit__arcs" viewBox="0 0 1000 400" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M110 400 A390 330 0 0 1 890 400" fill="none" stroke="#E7E4DF" stroke-width="1.5"/><path d="M270 400 A230 200 0 0 1 730 400" fill="none" stroke="#EFE7DD" stroke-width="1.2" stroke-dasharray="4 7"/></svg>
			<div class="td-hero-orbit__tile" aria-hidden="true"><?php echo td_logo( 'mark', 78 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php if ( $td_plats ) : ?>
				<ul class="td-hero-orbit__platforms" role="list" aria-label="<?php esc_attr_e( 'Platforms we work on', 'techdella' ); ?>">
					<?php foreach ( $td_plats as $td_i => $td_p ) : ?>
						<li class="td-hero-orbit__float td-hero-orbit__float--p<?php echo (int) $td_i + 1; ?>">
							<?php echo td_platform_badge( (string) ( $td_p['name'] ?? '' ), (string) ( $td_p['slug'] ?? '' ), (string) ( $td_p['mark'] ?? '' ), ! empty( $td_p['ink'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( '' !== $td_lead_chp ) : ?>
				<span class="td-hero-orbit__float td-hero-orbit__float--lead td-hero-orbit__leadchip" aria-hidden="true"><span class="td-dot"></span><?php echo esc_html( $td_lead_chp ); ?></span>
			<?php endif; ?>
			<?php foreach ( $td_stats as $td_i => $td_s ) : ?>
				<div class="td-hero-orbit__float td-hero-orbit__stat td-hero-orbit__stat--s<?php echo (int) $td_i + 1; ?>">
					<strong class="td-hero-orbit__stat-value"><?php echo esc_html( (string) ( $td_s['value'] ?? '' ) ); ?></strong>
					<span class="td-hero-orbit__stat-label"><?php echo esc_html( (string) ( $td_s['label'] ?? '' ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
