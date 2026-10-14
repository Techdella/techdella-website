<?php
/**
 * Pros and cons block (article component).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_pros = td_rows( $attributes['pros'] ?? [] );
$td_cons = td_rows( $attributes['cons'] ?? [] );
if ( ! $td_pros && ! $td_cons ) {
	return;
}
$td_col = static function ( string $label, array $rows, string $mod ): string {
	$html = '<div class="td-proscons__col td-proscons__col--' . $mod . '"><p class="td-proscons__label">' . esc_html( $label ) . '</p><ul>';
	foreach ( $rows as $row ) {
		$html .= '<li>' . td_lite_inline( (string) $row ) . '</li>';
	}
	return $html . '</ul></div>';
};
?>
<section <?php echo td_block_wrapper( 'td-proscons' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( (string) ( $attributes['title'] ?? __( 'Pros and cons', 'techdella' ) ) ); ?>">
	<?php if ( ! empty( $attributes['title'] ) ) : ?>
		<p class="td-proscons__title"><?php echo esc_html( (string) $attributes['title'] ); ?></p>
	<?php endif; ?>
	<div class="td-proscons__grid">
		<?php
		echo $td_col( (string) ( $attributes['prosLabel'] ?? 'Pros' ), $td_pros, 'pros' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $td_col( (string) ( $attributes['consLabel'] ?? 'Cons' ), $td_cons, 'cons' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</section>
