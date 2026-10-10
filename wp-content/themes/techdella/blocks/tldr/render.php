<?php
/**
 * TL;DR / answer-first box.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';

$td_text = trim( (string) ( $attributes['text'] ?? '' ) );
if ( '' === $td_text ) {
	$td_id   = (int) ( $attributes['postId'] ?? 0 );
	$td_id   = $td_id ? $td_id : (int) get_the_ID();
	$td_text = $td_id ? trim( (string) td_field( 'tldr', $td_id ) ) : '';
}
if ( '' === $td_text ) {
	echo td_a3_notice( __( 'TL;DR: fill the post’s tldr field.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_style = 'sentence' === ( $attributes['style'] ?? '' ) ? 'sentence' : 'summary';
?>
<aside <?php echo td_block_wrapper( 'td-tldr td-tldr--' . $td_style ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( (string) ( $attributes['label'] ?? __( 'Summary', 'techdella' ) ) ); ?>">
	<?php if ( ! empty( $attributes['label'] ) ) : ?>
		<p class="td-tldr__label"><?php echo esc_html( (string) $attributes['label'] ); ?></p>
	<?php endif; ?>
	<p class="td-tldr__text"><?php echo nl2br( td_a3_text( $td_text ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
</aside>
