<?php
/**
 * Video (§6.3): self-hosted <video> with WebM + MP4, poster and captions.
 * loop: muted, no autoplay attribute (view.js plays it near the viewport unless the visitor
 * prefers reduced motion; poster only then), pause button (WCAG 2.2.2).
 * player: controls, no autoplay, captions on by default.
 * Adds a VideoObject to the page's schema graph when it has a title, poster, MP4 and date.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_a      = $attributes;
$td_src    = static function ( $v ): string {
	if ( is_numeric( $v ) && (int) $v > 0 ) {
		return (string) wp_get_attachment_url( (int) $v );
	}
	$v = trim( (string) $v );
	return ( '' !== $v && ( str_starts_with( $v, 'http' ) || str_starts_with( $v, '/' ) ) ) ? $v : '';
};
$td_mp4    = $td_src( $td_a['mp4'] ?? '' );
$td_webm   = $td_src( $td_a['webm'] ?? '' );
$td_poster = $td_src( $td_a['poster'] ?? '' );
$td_vtt    = $td_src( $td_a['captions'] ?? '' );
if ( '' === $td_mp4 && '' === $td_webm ) {
	if ( function_exists( 'td_a3_notice' ) ) {
		echo td_a3_notice( __( 'Video: add an MP4 or WebM file.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	return;
}
$td_mode  = 'player' === ( $td_a['mode'] ?? '' ) ? 'player' : 'loop';
$td_ratio = in_array( $td_a['ratio'] ?? '', [ '16:9', '1:1', '9:16' ], true ) ? (string) $td_a['ratio'] : '16:9';
$td_dims  = [
	'16:9' => [ 1920, 1080 ],
	'1:1'  => [ 1080, 1080 ],
	'9:16' => [ 1080, 1920 ],
][ $td_ratio ];
$td_title = trim( (string) ( $td_a['title'] ?? '' ) );

// VideoObject (§8.2): only with everything Google needs.
$td_date = is_numeric( $td_a['mp4'] ?? '' ) ? (string) get_post_time( 'c', true, (int) $td_a['mp4'] ) : '';
if ( function_exists( 'td_schema_add_video' ) && '' !== $td_title && '' !== $td_poster && '' !== $td_mp4 && '' !== $td_date ) {
	$td_video = [
		'name'         => $td_title,
		'description'  => '' !== trim( (string) ( $td_a['description'] ?? '' ) ) ? (string) $td_a['description'] : $td_title,
		'thumbnailUrl' => $td_poster,
		'uploadDate'   => $td_date,
		'contentUrl'   => $td_mp4,
	];
	if ( (int) ( $td_a['duration'] ?? 0 ) > 0 ) {
		$td_video['duration'] = 'PT' . (int) $td_a['duration'] . 'S';
	}
	td_schema_add_video( $td_video );
}

$td_attrs = [
	'class'         => 'td-video__el',
	'width'         => $td_dims[0],
	'height'        => $td_dims[1],
	'preload'       => 'none',
	'playsinline'   => true,
	'poster'        => $td_poster,
	'data-td-video' => $td_mode,
	'aria-label'    => $td_title,
];
if ( 'loop' === $td_mode ) {
	$td_attrs['muted'] = true;
	$td_attrs['loop']  = true;
} else {
	$td_attrs['controls'] = true;
}
$td_html = '';
foreach ( $td_attrs as $td_k => $td_v ) {
	if ( true === $td_v ) {
		$td_html .= ' ' . $td_k;
	} elseif ( '' !== (string) $td_v ) {
		$td_html .= ' ' . $td_k . '="' . esc_attr( (string) $td_v ) . '"';
	}
}
?>
<figure <?php echo td_block_wrapper( 'td-video td-video--' . $td_mode . ' td-video--r' . str_replace( ':', 'x', $td_ratio ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-video__frame">
		<video<?php echo $td_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
			<?php if ( '' !== $td_webm ) : ?>
				<source src="<?php echo esc_url( $td_webm ); ?>" type="video/webm">
			<?php endif; ?>
			<?php if ( '' !== $td_mp4 ) : ?>
				<source src="<?php echo esc_url( $td_mp4 ); ?>" type="video/mp4">
			<?php endif; ?>
			<?php if ( '' !== $td_vtt ) : ?>
				<track kind="captions" src="<?php echo esc_url( $td_vtt ); ?>" srclang="en" label="<?php esc_attr_e( 'English', 'techdella' ); ?>"<?php echo 'player' === $td_mode ? ' default' : ''; ?>>
			<?php endif; ?>
		</video>
		<?php if ( ! empty( $td_a['label'] ) ) : ?>
			<span class="td-label-example"><?php echo esc_html( (string) $td_a['label'] ); ?></span>
		<?php endif; ?>
		<?php if ( 'loop' === $td_mode ) : ?>
			<button type="button" class="td-video__toggle" data-td-video-toggle aria-pressed="false" hidden>
				<span class="td-sr" data-label-pause="<?php esc_attr_e( 'Pause video', 'techdella' ); ?>" data-label-play="<?php esc_attr_e( 'Play video', 'techdella' ); ?>"><?php esc_html_e( 'Pause video', 'techdella' ); ?></span>
				<span class="td-video__icon" aria-hidden="true"></span>
			</button>
		<?php endif; ?>
	</div>
	<?php if ( ! empty( $td_a['caption'] ) ) : ?>
		<figcaption class="td-video__caption"><?php echo esc_html( (string) $td_a['caption'] ); ?></figcaption>
	<?php endif; ?>
</figure>
