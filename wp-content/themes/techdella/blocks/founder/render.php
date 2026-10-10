<?php
/**
 * Founder letter block + client quote strip.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_a     = $attributes;
$td_stats = array_slice( array_filter( (array) ( $td_a['stats'] ?? [] ), 'is_array' ), 0, 3 );
$td_paras = array_filter( array_map( 'trim', (array) preg_split( '/\n\s*\n/', (string) ( $td_a['body'] ?? '' ) ) ) );
$td_quote = trim( (string) ( $td_a['quote'] ?? '' ) );
?>
<section <?php echo td_block_wrapper( 'td-founder' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-founder__inner">
		<div class="td-founder__row">
			<div class="td-founder__letter">
				<?php echo td_chip( (string) ( $td_a['label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<h2 class="td-founder__title"><?php echo td_accent( (string) ( $td_a['title'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<?php if ( ! empty( $td_a['note'] ) ) : ?>
					<p class="td-founder__note"><?php echo td_mark_placeholders( esc_html( (string) $td_a['note'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
				<?php foreach ( $td_paras as $td_p ) : ?>
					<p class="td-founder__p"><?php echo td_mark_placeholders( esc_html( $td_p ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endforeach; ?>
				<?php if ( $td_stats ) : ?>
					<ul class="td-founder__stats" role="list">
						<?php foreach ( $td_stats as $td_s ) : ?>
							<li class="td-founder__stat"><strong><?php echo esc_html( (string) ( $td_s['value'] ?? '' ) ); ?></strong><span><?php echo esc_html( (string) ( $td_s['label'] ?? '' ) ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $td_a['image'] ) ) : ?>
				<div class="td-founder__portrait">
					<?php
					echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$td_a['image'],
						[
							'alt'       => (string) ( $td_a['imageAlt'] ?? '' ),
							'sizes'     => '(max-width: 700px) 90vw, 320px',
							'img_class' => 'td-founder__img',
						]
					);
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( '' !== $td_quote ) : ?>
			<figure class="td-founder__quote">
				<?php
				if ( ! empty( $td_a['quoteImage'] ) ) {
					echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$td_a['quoteImage'],
						[
							'alt'       => '',
							'sizes'     => '68px',
							'width'     => 68,
							'class'     => 'td-founder__qphoto',
							'img_class' => 'td-founder__qimg',
						]
					);
				}
				?>
				<blockquote class="td-founder__qtext"><p>“<?php echo esc_html( $td_quote ); ?>”</p></blockquote>
				<figcaption class="td-founder__qwho"><strong><?php echo esc_html( (string) ( $td_a['quoteName'] ?? '' ) ); ?></strong><span><?php echo esc_html( (string) ( $td_a['quoteRole'] ?? '' ) ); ?></span></figcaption>
			</figure>
		<?php endif; ?>
	</div>
</section>
