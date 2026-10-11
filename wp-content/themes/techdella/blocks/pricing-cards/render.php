<?php
/**
 * Pricing cards block ("Ways to start").
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_a     = $attributes;
$td_cards = array_filter( (array) ( $td_a['cards'] ?? [] ), 'is_array' );
$td_bg    = 'paper' === ( $td_a['background'] ?? '' ) ? 'paper' : 'canvas';
$td_title = implode( '<br>', array_map( 'td_accent', explode( "\n", (string) ( $td_a['title'] ?? '' ) ) ) );
?>
<section <?php echo td_block_wrapper( 'td-pricing td-pricing--' . $td_bg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-pricing__inner">
		<div class="td-section-head">
			<div class="td-section-head__title">
				<?php echo td_chip( (string) ( $td_a['label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( '' !== $td_title ) : ?>
					<h2 class="td-pricing__title"><?php echo $td_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- td_accent() escapes. ?></h2>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $td_a['intro'] ) ) : ?>
				<p><?php echo td_mark_placeholders( esc_html( (string) $td_a['intro'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
		</div>
		<ul class="td-pricing__grid" role="list">
			<?php foreach ( $td_cards as $td_c ) : ?>
				<?php
				$td_feat = ! empty( $td_c['featured'] );
				$td_url  = (string) ( $td_c['buttonUrl'] ?? '' );
				$td_url  = str_starts_with( $td_url, '/' ) ? home_url( $td_url ) : $td_url;
				?>
				<li class="td-pricing__card<?php echo $td_feat ? ' td-pricing__card--featured' : ''; ?>">
					<div class="td-pricing__top">
						<span class="td-pricing__kicker"><?php echo esc_html( (string) ( $td_c['kicker'] ?? '' ) ); ?></span>
						<?php if ( ! empty( $td_c['badge'] ) ) : ?>
							<span class="td-tag td-tag--highlight td-pricing__badge"><?php echo esc_html( (string) $td_c['badge'] ); ?></span>
						<?php endif; ?>
					</div>
					<h3 class="td-pricing__name"><?php echo esc_html( (string) ( $td_c['title'] ?? '' ) ); ?></h3>
					<p class="td-pricing__text"><?php echo td_mark_placeholders( esc_html( (string) ( $td_c['text'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<div class="td-pricing__foot">
						<strong class="td-pricing__price"><?php echo td_mark_placeholders( esc_html( (string) ( $td_c['price'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
						<?php
						if ( ! empty( $td_c['buttonLabel'] ) && '' !== $td_url ) {
							echo td_button( (string) $td_c['buttonLabel'], $td_url, $td_feat ? 'light' : 'primary', [ 'size' => 'sm' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
