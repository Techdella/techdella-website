<?php
/**
 * Ad creative mock (§2.3, §6.1). Every mock is labelled "Example" unless liveClientAd is true.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_a       = $attributes;
$td_formats = [
	'tiktok'            => __( 'TikTok ad', 'techdella' ),
	'instagram'         => __( 'Instagram ad', 'techdella' ),
	'meta'              => __( 'Meta ad', 'techdella' ),
	'linkedin'          => __( 'LinkedIn ad', 'techdella' ),
	'linkedin-document' => __( 'LinkedIn document ad', 'techdella' ),
	'google-search'     => __( 'Google search ad', 'techdella' ),
	'google-display'    => __( 'Display banner', 'techdella' ),
	'reel'              => __( 'Reel', 'techdella' ),
	'carousel'          => __( 'Carousel', 'techdella' ),
	'video'             => __( 'Video', 'techdella' ),
	'motion'            => __( 'Motion', 'techdella' ),
];
$td_format  = isset( $td_formats[ $td_a['format'] ?? '' ] ) ? (string) $td_a['format'] : 'meta';
$td_live    = ! empty( $td_a['liveClientAd'] );
$td_brand   = (string) ( $td_a['brand'] ?? 'YourCo' );
$td_t       = static fn( string $key ): string => esc_html( (string) ( $td_a[ $key ] ?? '' ) );
$td_has     = static fn( string $key ): bool => '' !== trim( (string) ( $td_a[ $key ] ?? '' ) );
$td_img     = static function ( string $sizes ) use ( $td_a ): string {
	$image = $td_a['image'] ?? '';
	if ( '' === $image || 0 === $image ) {
		return '<span class="td-ad__placeholder" aria-hidden="true"></span>';
	}
	return td_image(
		$image,
		[
			'alt'       => (string) ( $td_a['imageAlt'] ?? '' ),
			'sizes'     => $sizes,
			'img_class' => 'td-ad__img',
		]
	);
};
$td_avatar  = '<span class="td-ad__avatar" aria-hidden="true">' . esc_html( mb_strtoupper( mb_substr( $td_brand, 0, 1 ) ) ) . '</span>';
$td_actions = '<span class="td-ad__actions" aria-hidden="true"><span>♥</span><span>✉</span><span>↗</span></span>';
$td_class   = 'td-ad td-ad--' . $td_format . ( ! empty( $td_a['fluid'] ) ? ' td-ad--fluid' : '' ) . ( $td_live ? ' td-ad--live' : '' );

ob_start();
switch ( $td_format ) {
	case 'tiktok':
	case 'reel':
		?>
		<div class="td-ad__media"><?php echo $td_img( '260px' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="td-ad__shade" aria-hidden="true"></span></div>
		<?php if ( $td_has( 'tag' ) ) : ?>
			<span class="td-ad__tag"><?php echo $td_t( 'tag' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php endif; ?>
		<?php if ( $td_has( 'overlay' ) ) : ?>
			<span class="td-ad__sticker"><?php echo $td_t( 'overlay' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php endif; ?>
		<?php echo 'tiktok' === $td_format ? $td_actions : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="td-ad__bottom">
			<?php if ( $td_has( 'headline' ) ) : ?>
				<strong class="td-ad__headline"><?php echo $td_t( 'headline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
			<?php endif; ?>
			<?php if ( $td_has( 'text' ) ) : ?>
				<span class="td-ad__text"><?php echo $td_t( 'text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php endif; ?>
			<?php if ( $td_has( 'cta' ) ) : ?>
				<span class="td-ad__cta"><?php echo $td_t( 'cta' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php endif; ?>
		</div>
		<?php
		break;

	case 'instagram':
		?>
		<div class="td-ad__media"><?php echo $td_img( '200px' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="td-ad__tag td-ad__tag--dark"><?php echo $td_has( 'tag' ) ? $td_t( 'tag' ) : esc_html__( 'Instagram', 'techdella' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></div>
		<div class="td-ad__bar"><strong><?php echo $td_has( 'cta' ) ? $td_t( 'cta' ) : esc_html__( 'Learn more', 'techdella' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong><span aria-hidden="true">→</span></div>
		<?php
		break;

	case 'meta':
	case 'linkedin':
	case 'linkedin-document':
		$td_sub = 'meta' === $td_format ? __( 'Sponsored', 'techdella' ) : __( 'Promoted', 'techdella' );
		?>
		<div class="td-ad__head"><?php echo $td_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="td-ad__who"><strong><?php echo esc_html( $td_brand ); ?></strong><span><?php echo esc_html( $td_has( 'tag' ) ? (string) $td_a['tag'] : $td_sub ); ?></span></span></div>
		<?php if ( 'meta' !== $td_format && $td_has( 'text' ) ) : ?>
			<p class="td-ad__copy"><?php echo $td_t( 'text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<?php if ( 'linkedin-document' === $td_format ) : ?>
			<div class="td-ad__doc" aria-hidden="true">
				<span class="td-ad__page"><strong><?php echo $td_has( 'headline' ) ? $td_t( 'headline' ) : esc_html__( 'Benchmark', 'techdella' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong><i></i><i class="td-ad__line--short"></i><b></b></span>
				<span class="td-ad__page"><small><?php esc_html_e( 'Page 2 / 8', 'techdella' ); ?></small><i></i><b class="td-ad__block--sand"></b></span>
			</div>
			<?php if ( $td_has( 'headline' ) ) : ?>
				<span class="td-sr"><?php echo $td_t( 'headline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php endif; ?>
		<?php else : ?>
			<div class="td-ad__media"><?php echo $td_img( '300px' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $td_has( 'overlay' ) ) : ?>
					<span class="td-ad__overlay"><?php echo $td_t( 'overlay' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="td-ad__bar">
			<span class="td-ad__bar-text"><?php echo $td_has( 'footer' ) ? $td_t( 'footer' ) : $td_t( 'headline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php if ( $td_has( 'cta' ) ) : ?>
				<span class="td-ad__button"><?php echo $td_t( 'cta' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php endif; ?>
		</div>
		<?php
		break;

	case 'google-search':
		?>
		<?php if ( $td_has( 'query' ) ) : ?>
			<p class="td-ad__query"><span aria-hidden="true">⌕</span><span class="td-sr"><?php esc_html_e( 'Search:', 'techdella' ); ?></span> <?php echo $td_t( 'query' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<div class="td-ad__result">
			<span class="td-ad__url"><strong><?php esc_html_e( 'Sponsored', 'techdella' ); ?></strong> · <?php echo $td_t( 'domain' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<strong class="td-ad__headline"><?php echo $td_t( 'headline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
			<?php if ( $td_has( 'text' ) ) : ?>
				<span class="td-ad__text"><?php echo $td_t( 'text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $td_a['sitelinks'] ) && is_array( $td_a['sitelinks'] ) ) : ?>
				<span class="td-ad__sitelinks">
					<?php foreach ( $td_a['sitelinks'] as $td_link ) : ?>
						<span><?php echo esc_html( (string) $td_link ); ?></span>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
		</div>
		<?php
		break;

	case 'google-display':
		?>
		<span class="td-ad__kind"><?php echo $td_has( 'tag' ) ? $td_t( 'tag' ) : esc_html__( 'Display banner', 'techdella' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<div class="td-ad__banner"><strong><?php echo $td_t( 'headline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong><?php echo $td_has( 'cta' ) ? '<span class="td-ad__cta td-ad__cta--signal">' . $td_t( 'cta' ) . '</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php
		break;

	case 'carousel':
		$td_slides = array_slice( array_filter( (array) ( $td_a['slides'] ?? [] ), 'is_array' ), 0, 5 );
		?>
		<span class="td-ad__kind"><?php echo $td_has( 'tag' ) ? $td_t( 'tag' ) : esc_html__( 'Carousel', 'techdella' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<ol class="td-ad__slides" role="list">
			<?php foreach ( array_values( $td_slides ) as $td_i => $td_s ) : ?>
				<li class="td-ad__slide td-ad__slide--<?php echo esc_attr( (string) ( $td_i % 3 ) ); ?>"><span><?php echo esc_html( (string) ( $td_s['label'] ?? '' ) ); ?></span><strong><?php echo esc_html( (string) ( $td_s['title'] ?? '' ) ); ?></strong></li>
			<?php endforeach; ?>
		</ol>
		<?php
		break;

	case 'video':
		?>
		<div class="td-ad__media"><?php echo $td_img( '(max-width: 760px) 100vw, 400px' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php if ( $td_has( 'tag' ) ) : ?>
			<span class="td-ad__tag td-ad__tag--light td-ad__tag--bottom"><?php echo $td_t( 'tag' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php endif; ?>
		<?php
		break;

	case 'motion':
		?>
		<span class="td-ad__kind"><?php echo $td_has( 'tag' ) ? $td_t( 'tag' ) : esc_html__( 'Motion', 'techdella' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<div class="td-ad__motion"><svg viewBox="0 0 100 120" aria-hidden="true" focusable="false"><path d="M10 120 V48 A40 40 0 0 1 90 48 V120" fill="none" stroke="currentColor" stroke-width="10"/><path class="td-ad__motion-bar" d="M50 18 V120" stroke-width="8"/></svg></div>
		<?php
		break;
}
$td_inner = (string) ob_get_clean();
?>
<div <?php echo td_block_wrapper( 'td-ad-mock' . ( ! empty( $td_a['contained'] ) ? ' td-container' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<figure class="<?php echo esc_attr( $td_class ); ?>">
		<?php echo $td_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<figcaption class="<?php echo $td_live ? 'td-sr' : 'td-ad__label'; ?>"><?php echo $td_live ? esc_html( $td_formats[ $td_format ] ) : esc_html__( 'Example', 'techdella' ) . '<span class="td-sr"> ' . esc_html( $td_formats[ $td_format ] ) . '</span>'; ?></figcaption>
	</figure>
</div>
