<?php
/**
 * Report mock: example KPI card, optionally inside the Paid "everything visible" section.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_a     = $attributes;
$td_kpis  = array_filter( (array) ( $td_a['kpis'] ?? [] ), static fn( $k ) => is_array( $k ) && isset( $k['label'], $k['value'] ) );
$td_bars  = array_values( array_filter( (array) ( $td_a['bars'] ?? [] ), static fn( $b ) => is_array( $b ) && isset( $b['label'] ) ) );
$td_items = array_filter( (array) ( $td_a['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['title'] ) );
$td_badge = trim( (string) ( $td_a['badge'] ?? '' ) );
$td_badge = '' !== $td_badge ? $td_badge : __( 'Example report', 'techdella' );
$td_max   = 0.0;
foreach ( $td_bars as $td_b ) {
	$td_max = max( $td_max, (float) preg_replace( '/[^0-9.]/', '', (string) ( $td_b['value'] ?? '0' ) ) );
}
$td_shades = [ 'ink', 'body', 'num', 'num' ];

ob_start();
?>
<figure class="td-report">
	<div class="td-report__head">
		<strong class="td-report__title"><?php echo esc_html( (string) ( $td_a['cardTitle'] ?? '' ) ); ?></strong>
		<figcaption class="td-report__badge"><?php echo esc_html( $td_badge ); ?></figcaption>
	</div>
	<?php if ( $td_kpis ) : ?>
		<dl class="td-report__kpis">
			<?php foreach ( $td_kpis as $td_k ) : ?>
				<div class="td-report__kpi<?php echo ! empty( $td_k['highlight'] ) ? ' td-report__kpi--hl' : ''; ?>">
					<dt><?php echo esc_html( (string) $td_k['label'] ); ?></dt>
					<dd><?php echo esc_html( (string) $td_k['value'] ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>
	<?php if ( $td_bars ) : ?>
		<div class="td-report__bars">
			<?php if ( ! empty( $td_a['barsLabel'] ) ) : ?>
				<span class="td-report__bars-label"><?php echo esc_html( (string) $td_a['barsLabel'] ); ?></span>
			<?php endif; ?>
			<ul class="td-report__rows" role="list">
				<?php foreach ( $td_bars as $td_i => $td_b ) : ?>
					<?php
					$td_val   = (float) preg_replace( '/[^0-9.]/', '', (string) ( $td_b['value'] ?? '0' ) );
					$td_pct   = $td_max > 0 ? round( 100 * $td_val / $td_max ) : 0;
					$td_shade = ! empty( $td_b['highlight'] ) ? 'hl' : $td_shades[ min( $td_i, 3 ) ];
					?>
					<li class="td-report__row">
						<span class="td-report__row-label"><?php echo esc_html( (string) $td_b['label'] ); ?></span>
						<span class="td-report__track" aria-hidden="true"><span class="td-report__fill td-report__fill--<?php echo esc_attr( $td_shade ); ?>" style="<?php echo esc_attr( 'width:' . $td_pct . '%' ); ?>"></span></span>
						<span class="td-report__row-value"><?php echo esc_html( (string) ( $td_b['value'] ?? '' ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $td_a['note'] ) ) : ?>
		<p class="td-report__note"><?php echo esc_html( (string) $td_a['note'] ); ?></p>
	<?php endif; ?>
</figure>
<?php
$td_card = (string) ob_get_clean();

if ( empty( $td_a['title'] ) ) :
	?>
	<div <?php echo td_block_wrapper( 'td-report-mock' . ( ! empty( $td_a['contained'] ) ? ' td-container' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php echo $td_card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
	return;
endif;
?>
<section <?php echo td_block_wrapper( 'td-report-mock td-report-mock--section td-section td-section--dark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-report-mock__inner">
		<div class="td-report-mock__copy">
			<?php echo td_chip( (string) ( $td_a['label'] ?? '' ), 'td-chip--dark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<h2 class="td-report-mock__title"><?php echo td_accent( (string) $td_a['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
			<?php if ( $td_items ) : ?>
				<ul class="td-report-mock__tiles" role="list">
					<?php foreach ( $td_items as $td_item ) : ?>
						<li><strong><?php echo esc_html( (string) $td_item['title'] ); ?></strong><span><?php echo esc_html( (string) ( $td_item['text'] ?? '' ) ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="td-report-mock__visual"><?php echo $td_card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	</div>
</section>
