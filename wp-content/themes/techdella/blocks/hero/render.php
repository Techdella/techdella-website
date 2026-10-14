<?php
/**
 * Hero (page) block. A right-hand slot can be passed as $content
 * (td_block( 'hero', $attrs, $slot_html )); otherwise the image is shown.
 *
 * @var array<string,mixed> $attributes
 * @var string              $content
 */

defined( 'ABSPATH' ) || exit;

$td_a      = $attributes;
$td_href   = static fn( string $url ): string => str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
$td_slot   = trim( (string) ( $content ?? '' ) );
$td_image  = $td_a['image'] ?? '';
$td_layout = 'center' === ( $td_a['layout'] ?? '' ) ? 'center' : 'split';
$td_bg     = in_array( $td_a['background'] ?? '', [ 'grid', 'plain', 'canvas' ], true ) ? $td_a['background'] : 'grid';
$td_plats  = array_filter( (array) ( $td_a['platforms'] ?? [] ), 'is_array' );
$td_set    = array_slice( array_values( array_filter( (array) ( $td_a['imageSet'] ?? [] ), static fn( $p ) => is_array( $p ) && ! empty( $p['image'] ) ) ), 0, 6 );
$td_set    = count( $td_set ) >= 2 && 'center' !== $td_layout ? $td_set : [];
$td_aside  = '' !== $td_slot || $td_set || ( '' !== $td_image && 0 !== $td_image && 'center' !== $td_layout );
$td_class  = 'td-hero td-hero--' . $td_layout . ' td-hero--bg-' . $td_bg . ( $td_aside ? ' td-hero--has-aside' : '' );
?>
<section <?php echo td_block_wrapper( $td_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( 'grid' === $td_bg ) : ?>
		<div class="td-hero__grid" aria-hidden="true"></div>
	<?php endif; ?>
	<div class="td-container td-hero__inner">
		<div class="td-hero__copy">
			<?php if ( ! empty( $td_a['parentLabel'] ) && ! empty( $td_a['eyebrow'] ) ) : ?>
				<p class="td-hero__crumb">
					<a href="<?php echo esc_url( $td_href( (string) ( $td_a['parentUrl'] ?? '/' ) ) ); ?>"><?php echo esc_html( (string) $td_a['parentLabel'] ); ?></a>
					<span aria-hidden="true">/</span>
					<span class="td-hero__crumb-current"><span class="td-dot" aria-hidden="true"></span><?php echo esc_html( (string) $td_a['eyebrow'] ); ?></span>
				</p>
			<?php elseif ( ! empty( $td_a['eyebrow'] ) ) : ?>
				<?php echo td_chip( (string) $td_a['eyebrow'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
			<h1 class="td-hero__title"><?php echo td_accent( (string) ( $td_a['title'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
			<?php if ( ! empty( $td_a['lead'] ) ) : ?>
				<p class="td-hero__lead"><?php echo td_mark_placeholders( esc_html( (string) $td_a['lead'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $td_a['primaryLabel'] ) || ! empty( $td_a['secondaryLabel'] ) ) : ?>
				<div class="td-hero__ctas">
					<?php
					if ( ! empty( $td_a['primaryLabel'] ) ) {
						echo td_button( (string) $td_a['primaryLabel'], $td_href( (string) ( $td_a['primaryUrl'] ?? '/' ) ), 'primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					if ( ! empty( $td_a['secondaryLabel'] ) ) {
						echo td_button( (string) $td_a['secondaryLabel'], $td_href( (string) ( $td_a['secondaryUrl'] ?? '#' ) ), 'secondary', [ 'class' => 'td-btn--soft' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			<?php endif; ?>
			<?php if ( $td_plats ) : ?>
				<div class="td-hero__platforms">
					<?php if ( ! empty( $td_a['platformsLabel'] ) ) : ?>
						<span class="td-hero__platforms-label"><?php echo esc_html( (string) $td_a['platformsLabel'] ); ?></span>
					<?php endif; ?>
					<ul class="td-hero__badges" role="list">
						<?php foreach ( $td_plats as $td_p ) : ?>
							<li><?php echo td_platform_badge( (string) ( $td_p['name'] ?? '' ), (string) ( $td_p['slug'] ?? '' ), (string) ( $td_p['mark'] ?? '' ), ! empty( $td_p['ink'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( $td_aside ) : ?>
			<div class="td-hero__aside">
				<?php if ( '' !== $td_slot ) : ?>
					<?php echo $td_slot; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block/template markup. ?>
				<?php elseif ( $td_set ) : ?>
					<ul class="td-hero__mosaic td-hero__mosaic--<?php echo count( $td_set ); ?>" role="list">
						<?php foreach ( $td_set as $td_i => $td_p ) : ?>
							<li class="td-hero__mosaic-item">
								<div class="td-hero__mosaic-photo">
									<?php
									echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										$td_p['image'],
										[
											'alt'      => '',
											'sizes'    => '(max-width: 900px) 30vw, 180px',
											'priority' => $td_i < 3,
										]
									);
									?>
								</div>
								<?php if ( ! empty( $td_p['name'] ) ) : ?>
									<p class="td-hero__mosaic-name"><?php echo esc_html( (string) $td_p['name'] ); ?>
										<?php if ( ! empty( $td_p['role'] ) ) : ?>
											<span><?php echo esc_html( (string) $td_p['role'] ); ?></span>
										<?php endif; ?>
									</p>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<figure class="td-hero__media<?php echo 'arch' === ( $td_a['imageShape'] ?? '' ) ? ' td-hero__media--arch' : ''; ?>">
						<?php
						echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							$td_image,
							[
								'alt'      => (string) ( $td_a['imageAlt'] ?? '' ),
								'sizes'    => '(max-width: 900px) 100vw, 600px',
								'priority' => true,
							]
						);
						?>
						<?php if ( ! empty( $td_a['imageCaption'] ) ) : ?>
							<figcaption class="td-hero__caption"><?php echo esc_html( (string) $td_a['imageCaption'] ); ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
