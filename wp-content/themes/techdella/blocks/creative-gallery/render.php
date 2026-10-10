<?php
/**
 * Creative gallery / hero collage (§2.3, §6.1). Non-live creatives are labelled "Example".
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_items = array_values( array_filter( (array) ( $attributes['items'] ?? [] ), 'is_array' ) );
if ( ! $td_items ) {
	return;
}
$td_href      = static function ( string $url ): string {
	if ( preg_match( '/^(marketing|journal|studios):(\/.*)$/', $url, $m ) ) {
		return td_url( $m[1], $m[2] );
	}
	return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
};
$td_layout    = 'collage' === ( $attributes['layout'] ?? '' ) ? 'collage' : 'gallery';
$td_surface   = 'paper' === ( $attributes['surface'] ?? '' ) ? 'paper' : 'canvas';
$td_note      = (string) ( $attributes['note'] ?? '' );
$td_filters   = 'gallery' === $td_layout && ! empty( $attributes['filters'] );
$td_platforms = [];

ob_start();
foreach ( $td_items as $td_i => $td_item ) {
	$td_type     = (string) ( $td_item['type'] ?? 'mock' );
	$td_platform = (string) ( $td_item['platform'] ?? '' );
	if ( '' !== $td_platform && 'link' !== $td_type ) {
		$td_platforms[ sanitize_title( $td_platform ) ] = $td_platform;
	}
	$td_size = in_array( $td_item['size'] ?? '', [ 'tall', 'wide' ], true ) ? ' td-creatives__item--' . $td_item['size'] : '';
	$td_attr = ' data-platform="' . esc_attr( sanitize_title( $td_platform ) ) . '"';
	echo '<li class="td-creatives__item td-creatives__item--' . esc_attr( (string) $td_i ) . esc_attr( $td_size ) . '"' . $td_attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$td_img_html = '';
	if ( 'image' === $td_type && ! empty( $td_item['image'] ) ) {
		$td_img_html = td_image(
			$td_item['image'],
			[
				'alt'       => (string) ( $td_item['alt'] ?? '' ),
				'sizes'     => '(max-width: 760px) 100vw, 400px',
				'img_class' => 'td-creatives__img',
			]
		);
		if ( str_starts_with( $td_img_html, '<!--' ) ) {
			$td_img_html = ''; // Key not built yet → fall back to a mock tile.
		}
	}

	if ( 'link' === $td_type ) {
		?>
		<a class="td-creatives__link" href="<?php echo esc_url( $td_href( (string) ( $td_item['url'] ?? '/' ) ) ); ?>">
			<span class="td-creatives__eyebrow"><?php echo esc_html( (string) ( $td_item['eyebrow'] ?? '' ) ); ?></span>
			<strong class="td-creatives__link-title"><?php echo td_accent( (string) ( $td_item['title'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
			<span class="td-creatives__link-cta"><?php echo esc_html( (string) ( $td_item['cta'] ?? '' ) ); ?><span class="td-creatives__arrow"><?php echo td_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></span>
		</a>
		<?php
	} elseif ( '' !== $td_img_html ) {
		$td_live = ! empty( $td_item['live'] );
		?>
		<figure class="td-creatives__figure">
			<?php echo $td_img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<figcaption class="<?php echo $td_live ? 'td-sr' : 'td-ad__label'; ?>"><?php echo $td_live ? esc_html( $td_platform . ' ' . __( 'ad', 'techdella' ) ) : esc_html__( 'Example', 'techdella' ); ?></figcaption>
		</figure>
		<?php
	} else {
		$td_mock = $td_item;
		unset( $td_mock['type'], $td_mock['size'], $td_mock['platform'] );
		$td_mock['fluid']     = 'gallery' === $td_layout;
		$td_mock['contained'] = false;
		echo td_block( 'ad-mock', $td_mock ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</li>';
}
$td_list = (string) ob_get_clean();

$td_grid = '<ul class="td-creatives__grid td-creatives__grid--' . esc_attr( $td_layout ) . '" role="list">' . $td_list . '</ul>';

if ( 'collage' === $td_layout ) :
	?>
	<div <?php echo td_block_wrapper( 'td-creatives td-creatives--collage' . ( ! empty( $attributes['contained'] ) ? ' td-container' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="td-creatives__stage">
			<?php echo $td_grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( '' !== $td_note ) : ?>
				<p class="td-creatives__note"><?php echo esc_html( $td_note ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return;
endif;
?>
<section <?php echo td_block_wrapper( 'td-creatives td-creatives--gallery td-section' . ( 'canvas' === $td_surface ? ' td-section--canvas' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-creatives__inner">
		<?php
		echo td_section_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			[
				'label' => (string) ( $attributes['label'] ?? '' ),
				'title' => (string) ( $attributes['title'] ?? '' ),
				'intro' => (string) ( $attributes['intro'] ?? '' ),
			]
		);
		?>
		<?php if ( $td_filters && count( $td_platforms ) > 1 ) : ?>
			<div class="td-creatives__filters" role="group" aria-label="<?php esc_attr_e( 'Filter by platform', 'techdella' ); ?>" hidden>
				<button type="button" class="td-creatives__filter" data-filter="" aria-pressed="true"><?php esc_html_e( 'All', 'techdella' ); ?></button>
				<?php foreach ( $td_platforms as $td_slug => $td_name ) : ?>
					<button type="button" class="td-creatives__filter" data-filter="<?php echo esc_attr( $td_slug ); ?>" aria-pressed="false"><?php echo esc_html( $td_name ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php echo $td_grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( '' !== $td_note ) : ?>
			<p class="td-creatives__note"><?php echo esc_html( $td_note ); ?></p>
		<?php endif; ?>
	</div>
</section>
