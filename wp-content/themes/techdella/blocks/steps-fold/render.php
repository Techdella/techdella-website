<?php
/**
 * Steps (fold-out) block: <details>/<summary> accordion, no JS needed.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_a     = $attributes;
$td_steps = array_values( array_filter( (array) ( $td_a['steps'] ?? [] ), 'is_array' ) );
$td_pills = array_filter( (array) ( $td_a['pills'] ?? [] ), 'is_array' );
$td_last  = count( $td_steps ) - 1;
$td_bg    = 'paper' === ( $td_a['background'] ?? '' ) ? 'paper' : 'canvas';
$td_title = implode( '<br>', array_map( 'td_accent', explode( "\n", (string) ( $td_a['title'] ?? '' ) ) ) );
?>
<section <?php echo td_block_wrapper( 'td-steps td-steps--' . $td_bg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-steps__inner">
		<div class="td-steps__intro">
			<?php echo td_chip( (string) ( $td_a['label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( '' !== $td_title ) : ?>
				<h2 class="td-steps__title"><?php echo $td_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- td_accent() escapes. ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $td_a['intro'] ) ) : ?>
				<p class="td-steps__lead"><?php echo td_mark_placeholders( esc_html( (string) $td_a['intro'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $td_a['image'] ) ) : ?>
				<figure class="td-steps__photo">
					<?php
					echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$td_a['image'],
						[
							'alt'   => (string) ( $td_a['imageAlt'] ?? '' ),
							'sizes' => '(max-width: 900px) 100vw, 520px',
						]
					);
					?>
					<?php if ( ! empty( $td_a['cardTitle'] ) ) : ?>
						<figcaption class="td-steps__card">
							<?php
							if ( ! empty( $td_a['cardImage'] ) ) {
								echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									$td_a['cardImage'],
									[
										'alt'       => '',
										'sizes'     => '38px',
										'width'     => 38,
										'img_class' => 'td-steps__avatar',
									]
								);
							}
							?>
							<span><strong><?php echo esc_html( (string) $td_a['cardTitle'] ); ?></strong><?php echo ! empty( $td_a['cardText'] ) ? '<span>' . esc_html( (string) $td_a['cardText'] ) . '</span>' : ''; ?></span>
						</figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>
		</div>
		<div class="td-steps__list">
			<?php foreach ( $td_steps as $td_i => $td_s ) : ?>
				<?php $td_dark = ! empty( $td_a['lastDark'] ) && $td_i === $td_last && $td_last > 0; ?>
				<details class="td-steps__item<?php echo $td_dark ? ' td-steps__item--dark' : ''; ?>"<?php echo 0 === $td_i && ! empty( $td_a['openFirst'] ) ? ' open' : ''; ?>>
					<summary class="td-steps__summary">
						<span class="td-steps__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $td_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="td-steps__head">
							<span class="td-sr"><?php /* translators: %d: step number */ echo esc_html( sprintf( __( 'Step %d:', 'techdella' ), $td_i + 1 ) ); ?> </span>
							<strong class="td-steps__name"><?php echo esc_html( (string) ( $td_s['title'] ?? '' ) ); ?></strong>
							<?php if ( ! empty( $td_s['deliverable'] ) ) : ?>
								<span class="td-steps__deliverable"><?php echo esc_html( (string) $td_s['deliverable'] ); ?></span>
							<?php endif; ?>
						</span>
						<span class="td-steps__toggle" aria-hidden="true"><?php echo td_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</summary>
					<div class="td-steps__body">
						<p><?php echo td_mark_placeholders( esc_html( (string) ( $td_s['body'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						<?php if ( ! empty( $td_s['tags'] ) ) : ?>
							<ul class="td-steps__tags" role="list">
								<?php foreach ( (array) $td_s['tags'] as $td_tag ) : ?>
									<li class="td-tag"><?php echo esc_html( (string) $td_tag ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</details>
			<?php endforeach; ?>
			<?php if ( $td_pills ) : ?>
				<ul class="td-steps__pills" role="list">
					<?php foreach ( $td_pills as $td_p ) : ?>
						<li class="td-steps__pill"><?php echo td_icon( (string) ( $td_p['icon'] ?? 'check' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( (string) ( $td_p['label'] ?? '' ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
