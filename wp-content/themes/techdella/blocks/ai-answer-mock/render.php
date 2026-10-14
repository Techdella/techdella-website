<?php
/**
 * AI answer mock (§2.3). Always labelled "Example".
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_answer = (string) ( $attributes['answer'] ?? '' );
if ( '' === $td_answer ) {
	return;
}
$td_caption = trim( (string) ( $attributes['caption'] ?? '' ) );
$td_caption = '' !== $td_caption ? $td_caption : __( 'Example AI answer', 'techdella' );
$td_variant = 'plain' === ( $attributes['variant'] ?? '' ) ? 'plain' : 'sand';
$td_sources = array_values( array_filter( (array) ( $attributes['sources'] ?? [] ), static fn( $s ) => is_array( $s ) && ! empty( $s['label'] ) ) );

// *Brand* → <mark>, [n] → citation sup. Escaped first.
$td_html = esc_html( $td_answer );
$td_html = (string) preg_replace( '/\*([^*]+)\*/', '<mark class="td-ai-answer__brand">$1</mark>', $td_html, 1 );
$td_html = (string) preg_replace( '/\[(\d{1,2})\]/', '<sup class="td-ai-answer__cite">$1</sup>', $td_html );
?>
<div <?php echo td_block_wrapper( 'td-ai-answer-block' . ( ! empty( $attributes['contained'] ) ? ' td-container' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<figure class="td-ai-answer td-ai-answer--<?php echo esc_attr( $td_variant ); ?>">
		<?php if ( ! empty( $attributes['question'] ) ) : ?>
			<p class="td-ai-answer__question"><span class="td-sr"><?php esc_html_e( 'Question:', 'techdella' ); ?> </span><?php echo esc_html( (string) $attributes['question'] ); ?></p>
		<?php endif; ?>
		<div class="td-ai-answer__card">
			<?php if ( ! empty( $attributes['platform'] ) ) : ?>
				<span class="td-ai-answer__platform"><?php echo esc_html( (string) $attributes['platform'] ); ?></span>
			<?php endif; ?>
			<p class="td-ai-answer__text"><span class="td-sr"><?php esc_html_e( 'Answer:', 'techdella' ); ?> </span><?php echo $td_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></p>
			<?php if ( $td_sources ) : ?>
				<ol class="td-ai-answer__sources" role="list" aria-label="<?php esc_attr_e( 'Sources', 'techdella' ); ?>">
					<?php foreach ( $td_sources as $td_i => $td_s ) : ?>
						<li class="td-ai-answer__source<?php echo ! empty( $td_s['own'] ) ? ' td-ai-answer__source--own' : ''; ?>"><?php echo esc_html( ( $td_i + 1 ) . ' · ' . $td_s['label'] ); ?><?php echo ! empty( $td_s['own'] ) ? '<span class="td-sr"> ' . esc_html__( '(cited)', 'techdella' ) . '</span>' : ''; ?></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</div>
		<figcaption class="td-ai-answer__caption"><?php echo esc_html( $td_caption ); ?></figcaption>
	</figure>
</div>
