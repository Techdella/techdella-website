<?php
/**
 * Lead capture mock (LeadGen hero visual). Illustrative only; no numbers.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';

$td_offer = trim( (string) ( $attributes['offer'] ?? '' ) );
if ( '' === $td_offer ) {
	echo td_a3_notice( __( 'Lead capture mock: add an offer title.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_leads  = array_values( array_filter( (array) ( $attributes['leads'] ?? [] ), static fn( $l ) => is_array( $l ) && ! empty( $l['who'] ) ) );
$td_stages = array_slice( array_values( array_filter( array_map( 'strval', (array) ( $attributes['stages'] ?? [] ) ) ) ), 0, 5 );
$td_img    = ! empty( $attributes['image'] ) ? td_image(
	$attributes['image'],
	[
		'alt'   => (string) ( $attributes['imageAlt'] ?? '' ),
		'sizes' => '(max-width: 900px) 100vw, 300px',
	]
) : '';
$td_img    = str_starts_with( $td_img, '<!--' ) ? '' : $td_img;
$td_bars   = [ 100, 60, 34, 16, 9 ];
?>
<div <?php echo td_block_wrapper( 'td-lead-mock-block' . ( ! empty( $attributes['contained'] ) ? ' td-container' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<figure class="td-lead-mock" aria-label="<?php echo esc_attr( (string) ( $attributes['caption'] ?? '' ) ); ?>">
		<div class="td-lead-mock__top">
			<div class="td-lead-mock__page">
				<span class="td-lead-mock__url"><?php echo esc_html( (string) ( $attributes['url'] ?? '' ) ); ?></span>
				<strong class="td-lead-mock__offer"><?php echo td_a3_text( $td_offer ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				<?php foreach ( array_slice( (array) ( $attributes['fields'] ?? [] ), 0, 4 ) as $td_f ) : ?>
					<span class="td-lead-mock__field" aria-hidden="true"><?php echo esc_html( (string) $td_f ); ?></span>
				<?php endforeach; ?>
				<?php if ( ! empty( $attributes['button'] ) ) : ?>
					<span class="td-lead-mock__button" aria-hidden="true"><?php echo esc_html( (string) $attributes['button'] ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( $td_leads ) : ?>
				<div class="td-lead-mock__leads">
					<strong class="td-lead-mock__leads-title"><?php echo esc_html( (string) ( $attributes['leadsTitle'] ?? '' ) ); ?></strong>
					<ul role="list">
						<?php foreach ( $td_leads as $td_l ) : ?>
							<li><span><?php echo esc_html( (string) $td_l['who'] ); ?></span><span class="td-lead-mock__status<?php echo ! empty( $td_l['hot'] ) ? ' td-lead-mock__status--hot' : ''; ?>"><?php echo esc_html( (string) ( $td_l['status'] ?? '' ) ); ?></span></li>
						<?php endforeach; ?>
					</ul>
					<p class="td-lead-mock__caption"><?php echo esc_html( (string) ( $attributes['caption'] ?? '' ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( count( $td_stages ) > 1 ) : ?>
			<div class="td-lead-mock__funnel">
				<span class="td-lead-mock__funnel-label"><?php echo esc_html( (string) ( $attributes['stagesLabel'] ?? '' ) ); ?></span>
				<ol class="td-lead-mock__bars">
					<?php foreach ( $td_stages as $td_i => $td_st ) : ?>
						<li style="<?php echo esc_attr( '--td-bar:' . $td_bars[ $td_i ] . '%' ); ?>"><span class="td-lead-mock__bar<?php echo count( $td_stages ) - 1 === $td_i ? ' td-lead-mock__bar--last' : ''; ?>" aria-hidden="true"></span><span class="td-lead-mock__stage"><?php echo esc_html( $td_st ); ?></span></li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $td_img ) : ?>
			<div class="td-lead-mock__photo">
				<?php echo $td_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( ! empty( $attributes['imageCaption'] ) ) : ?>
					<span class="td-lead-mock__photo-cap"><?php echo esc_html( (string) $attributes['imageCaption'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( ! $td_leads ) : ?>
			<p class="td-lead-mock__caption td-lead-mock__caption--alone"><?php echo esc_html( (string) ( $attributes['caption'] ?? '' ) ); ?></p>
		<?php endif; ?>
	</figure>
</div>
