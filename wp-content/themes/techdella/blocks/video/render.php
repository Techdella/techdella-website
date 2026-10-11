<?php
/**
 * Video (§6.3): self-hosted <video> with WebM + MP4, poster and captions.
 * loop: muted, no autoplay attribute (view.js plays it near the viewport unless the visitor
 * prefers reduced motion; poster only then), pause button (WCAG 2.2.2).
 * player: controls, no autoplay, captions on by default.
 * scene: a motion sample imported with `wp td media:videos` (td_videos option); the ratio picks
 * the format, and files set on the block override it.
 * items: several clips in a row (each item takes scene, title, description, duration, label,
 * caption and inherits the rest). heading/intro/chip/surface wrap the video(s) in a section.
 * Adds a VideoObject to the page's schema graph for each clip with a title, poster, MP4 and date.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'td_video_figure' ) ) {
	/**
	 * One <figure> for one clip, or '' when it has no file.
	 *
	 * @param array<string,mixed> $a     Clip attributes.
	 * @param string              $extra Extra wrapper attributes (block supports), or ''.
	 */
	function td_video_figure( array $a, string $extra = '' ): string {
		$src   = static function ( $v ): string {
			if ( is_numeric( $v ) && (int) $v > 0 ) {
				return (string) wp_get_attachment_url( (int) $v );
			}
			$v = trim( (string) $v );
			return ( '' !== $v && ( str_starts_with( $v, 'http' ) || str_starts_with( $v, '/' ) ) ) ? $v : '';
		};
		$ratio = in_array( $a['ratio'] ?? '', [ '16:9', '1:1', '9:16' ], true ) ? (string) $a['ratio'] : '16:9';
		$scene = sanitize_key( (string) ( $a['scene'] ?? '' ) );
		if ( '' !== $scene ) {
			$set = (array) ( ( (array) get_option( 'td_videos', [] ) )[ $scene ] ?? [] );
			$fmt = [
				'16:9' => 'wide',
				'1:1'  => 'square',
				'9:16' => 'tall',
			][ $ratio ];
			foreach ( [ 'mp4', 'webm', 'poster' ] as $key ) {
				if ( empty( $a[ $key ] ) && ! empty( $set[ $fmt ][ $key ] ) ) {
					$a[ $key ] = (int) $set[ $fmt ][ $key ];
				}
			}
			if ( empty( $a['captions'] ) && ! empty( $set['vtt'] ) ) {
				$a['captions'] = (int) $set['vtt'];
			}
		}
		$mp4    = $src( $a['mp4'] ?? '' );
		$webm   = $src( $a['webm'] ?? '' );
		$poster = $src( $a['poster'] ?? '' );
		$vtt    = $src( $a['captions'] ?? '' );
		if ( '' === $mp4 && '' === $webm ) {
			if ( ! function_exists( 'td_a3_notice' ) ) {
				return '';
			}
			return td_a3_notice( '' !== $scene ? __( 'Video: this scene is not in the Media Library yet. Run wp td media:videos.', 'techdella' ) : __( 'Video: add an MP4 or WebM file.', 'techdella' ) );
		}
		$mode  = 'player' === ( $a['mode'] ?? '' ) ? 'player' : 'loop';
		$dims  = [
			'16:9' => [ 1920, 1080 ],
			'1:1'  => [ 1080, 1080 ],
			'9:16' => [ 1080, 1920 ],
		][ $ratio ];
		$title = trim( (string) ( $a['title'] ?? '' ) );

		// VideoObject (§8.2): only with everything Google needs.
		$date = is_numeric( $a['mp4'] ?? '' ) ? (string) get_post_time( 'c', true, (int) $a['mp4'] ) : '';
		if ( function_exists( 'td_schema_add_video' ) && '' !== $title && '' !== $poster && '' !== $mp4 && '' !== $date ) {
			$video = [
				'name'         => $title,
				'description'  => '' !== trim( (string) ( $a['description'] ?? '' ) ) ? (string) $a['description'] : $title,
				'thumbnailUrl' => $poster,
				'uploadDate'   => $date,
				'contentUrl'   => $mp4,
			];
			if ( (int) ( $a['duration'] ?? 0 ) > 0 ) {
				$video['duration'] = 'PT' . (int) $a['duration'] . 'S';
			}
			td_schema_add_video( $video );
		}

		$attrs = [
			'class'         => 'td-video__el',
			'width'         => $dims[0],
			'height'        => $dims[1],
			'preload'       => 'none',
			'playsinline'   => true,
			// Loop videos show the poster as the frame's background instead: off-screen sections
			// are skipped (content-visibility), so their posters don't load until scrolled near.
			'poster'        => 'player' === $mode ? $poster : '',
			'data-td-video' => $mode,
			'aria-label'    => $title,
		];
		if ( 'loop' === $mode ) {
			$attrs['muted'] = true;
			$attrs['loop']  = true;
		} else {
			$attrs['controls'] = true;
		}
		$html = '';
		foreach ( $attrs as $k => $v ) {
			if ( true === $v ) {
				$html .= ' ' . $k;
			} elseif ( '' !== (string) $v ) {
				$html .= ' ' . $k . '="' . esc_attr( (string) $v ) . '"';
			}
		}
		$class = 'td-video td-video--' . $mode . ' td-video--r' . str_replace( ':', 'x', $ratio );
		$out   = '<figure ' . ( '' !== $extra ? $extra : 'class="' . esc_attr( $class ) . '"' ) . '><div class="td-video__frame"' . ( 'loop' === $mode && '' !== $poster ? ' style="background-image:url(&quot;' . esc_url( $poster ) . '&quot;)"' : '' ) . '>';
		$out  .= '<video' . $html . '>';
		$out  .= '' !== $webm ? '<source src="' . esc_url( $webm ) . '" type="video/webm">' : '';
		$out  .= '' !== $mp4 ? '<source src="' . esc_url( $mp4 ) . '" type="video/mp4">' : '';
		$out  .= '' !== $vtt ? '<track kind="captions" src="' . esc_url( $vtt ) . '" srclang="en" label="' . esc_attr__( 'English', 'techdella' ) . '"' . ( 'player' === $mode ? ' default' : '' ) . '>' : '';
		$out  .= '</video>';
		if ( ! empty( $a['label'] ) ) {
			$out .= '<span class="td-label-example">' . esc_html( (string) $a['label'] ) . '</span>';
		}
		if ( 'loop' === $mode ) {
			$out .= '<button type="button" class="td-video__toggle" data-td-video-toggle aria-pressed="false" hidden>'
				. '<span class="td-sr" data-label-pause="' . esc_attr__( 'Pause video', 'techdella' ) . '" data-label-play="' . esc_attr__( 'Play video', 'techdella' ) . '">' . esc_html__( 'Pause video', 'techdella' ) . '</span>'
				. '<span class="td-video__icon" aria-hidden="true"></span></button>';
		}
		$out .= '</div>';
		if ( ! empty( $a['caption'] ) ) {
			$out .= '<figcaption class="td-video__caption">' . esc_html( (string) $a['caption'] ) . '</figcaption>';
		}
		return $out . '</figure>';
	}
}

$td_a     = $attributes;
$td_items = array_values( array_filter( (array) ( $td_a['items'] ?? [] ), 'is_array' ) );
$td_head  = trim( (string) ( $td_a['heading'] ?? '' ) );
$td_surf  = in_array( $td_a['surface'] ?? '', [ 'canvas', 'sand', 'dark' ], true ) ? (string) $td_a['surface'] : '';

if ( ! $td_items && '' === $td_head ) {
	// One clip, no section: the figure is the block wrapper (as before).
	$td_ratio = in_array( $td_a['ratio'] ?? '', [ '16:9', '1:1', '9:16' ], true ) ? (string) $td_a['ratio'] : '16:9';
	$td_mode  = 'player' === ( $td_a['mode'] ?? '' ) ? 'player' : 'loop';
	echo td_video_figure( $td_a, td_block_wrapper( 'td-video td-video--' . $td_mode . ' td-video--r' . str_replace( ':', 'x', $td_ratio ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	return;
}

$td_shared = array_diff_key( $td_a, array_flip( [ 'items', 'heading', 'intro', 'chip', 'surface', 'anchor', 'className' ] ) );
$td_clips  = '';
foreach ( $td_items ? $td_items : [ $td_shared ] as $td_item ) {
	$td_clips .= td_video_figure( array_merge( $td_shared, $td_item ) );
}
if ( '' === $td_clips ) {
	return;
}
?>
<section <?php echo td_block_wrapper( trim( 'td-section td-video-section ' . ( $td_surf ? 'td-section--' . $td_surf : '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container">
		<?php
		if ( '' !== $td_head ) {
			echo td_section_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				[
					'label' => (string) ( $td_a['chip'] ?? '' ),
					'title' => $td_head,
					'intro' => (string) ( $td_a['intro'] ?? '' ),
					'align' => 'split',
				]
			);
		}
		?>
		<?php if ( $td_items ) : // Swipeable on phones, so it must be focusable for keyboard scrolling. ?>
		<div class="td-video-row" role="group" tabindex="0" aria-label="<?php echo esc_attr( wp_strip_all_tags( str_replace( '*', '', $td_head ? $td_head : __( 'Videos', 'techdella' ) ) ) ); ?>">
		<?php else : ?>
		<div class="td-video-single">
		<?php endif; ?>
			<?php echo $td_clips; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
		</div>
	</div>
</section>
