<?php
/**
 * Bento grid block (§2.3): 12-col grid of typed tiles.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_tiles = array_filter( (array) ( $attributes['tiles'] ?? [] ), 'is_array' );
if ( ! $td_tiles ) {
	return;
}

// "studios:/start/" → td_url( 'studios', '/start/' ); "/x/" → this site.
$td_href = static function ( string $url ): string {
	if ( preg_match( '/^(marketing|journal|studios):(\/.*)$/', $url, $m ) ) {
		return td_url( $m[1], $m[2] );
	}
	return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
};
$td_text = static fn( $v ): string => td_mark_placeholders( esc_html( (string) $v ) );
// *word* → <strong>word</strong> (mock copy only).
$td_bold = static fn( $v ): string => (string) preg_replace( '/\*([^*]+)\*/', '<strong>$1</strong>', esc_html( (string) $v ) );

$td_defaults = [
	'stat'          => 'sand',
	'image'         => 'photo',
	'photo'         => 'photo',
	'quote'         => 'dark',
	'link'          => 'paper',
	'platform-list' => 'dark',
	'search'        => 'sand',
	'form'          => 'paper',
	'sequence'      => 'canvas',
	'flow'          => 'dark',
];
$td_tablet   = [ 1 => 2, 2 => 2, 3 => 3, 4 => 2, 5 => 3 ];
$td_row_h    = (int) ( $attributes['rowHeight'] ?? 0 );
$td_padding  = (string) ( $attributes['padding'] ?? 'default' );
$td_style    = $td_row_h > 0 ? '--td-bento-row:' . $td_row_h . 'px' : '';
$td_classes  = 'td-bento td-bento--pad-' . sanitize_html_class( $td_padding ) . ( $td_row_h > 0 ? ' td-bento--fixed-rows' : '' );
$td_wrap     = $td_style ? [ 'style' => $td_style ] : [];
?>
<section <?php echo td_block_wrapper( $td_classes, $td_wrap ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container">
		<?php
		if ( ! empty( $attributes['title'] ) ) {
			echo td_section_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				[
					'label' => (string) ( $attributes['label'] ?? '' ),
					'title' => (string) $attributes['title'],
					'intro' => (string) ( $attributes['intro'] ?? '' ),
				]
			);
		}
		?>
		<div class="td-bento__grid">
			<?php
			foreach ( $td_tiles as $td_t ) :
				$td_type    = isset( $td_defaults[ $td_t['type'] ?? '' ] ) ? (string) $td_t['type'] : 'stat';
				$td_variant = in_array( $td_t['variant'] ?? '', [ 'paper', 'canvas', 'sand', 'dark', 'photo' ], true ) ? $td_t['variant'] : $td_defaults[ $td_type ];
				$td_span    = max( 1, min( 12, (int) ( $td_t['span'] ?? 4 ) ) );
				$td_span_t  = isset( $td_t['spanTablet'] ) ? max( 1, min( 6, (int) $td_t['spanTablet'] ) ) : ( $td_tablet[ $td_span ] ?? 6 );
				$td_rows    = max( 1, min( 3, (int) ( $td_t['rows'] ?? 1 ) ) );
				$td_url     = (string) ( $td_t['url'] ?? '' );
				// Tiles with their own links can't be a link themselves.
				$td_is_link = '' !== $td_url && ! in_array( $td_type, [ 'search', 'link' ], true );
				$td_tag     = $td_is_link ? 'a' : 'div';
				$td_attrs   = sprintf(
					' class="%s" style="%s"',
					esc_attr( 'td-bento__tile td-bento__tile--' . $td_type . ' td-bento__tile--' . $td_variant . ( $td_is_link ? ' td-bento__tile--link' : '' ) ),
					esc_attr( "--s:{$td_span};--t:{$td_span_t};--r:{$td_rows}" )
				);
				if ( $td_is_link ) {
					$td_attrs .= ' href="' . esc_url( $td_href( $td_url ) ) . '"';
				}
				echo '<' . $td_tag . $td_attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag is a/div, attrs escaped above.

				$td_head = static function () use ( $td_t, $td_text ): void {
					if ( ! empty( $td_t['eyebrow'] ) ) {
						echo '<span class="td-bento__eyebrow">' . $td_text( $td_t['eyebrow'] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					if ( ! empty( $td_t['title'] ) ) {
						echo '<h3 class="td-bento__title">' . $td_text( $td_t['title'] ) . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
				};

				switch ( $td_type ) {
					case 'search':
						?>
						<div class="td-bento__col">
							<?php $td_head(); ?>
							<?php if ( ! empty( $td_t['text'] ) ) : ?>
								<p class="td-bento__text"><?php echo $td_text( $td_t['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $td_t['links'] ) ) : ?>
								<div class="td-bento__links">
									<?php foreach ( (array) $td_t['links'] as $td_l ) : ?>
										<a class="td-bento__pill<?php echo 'light' === ( $td_l['style'] ?? '' ) ? ' td-bento__pill--light' : ''; ?>" href="<?php echo esc_url( $td_href( (string) ( $td_l['url'] ?? '#' ) ) ); ?>"><?php echo esc_html( (string) ( $td_l['label'] ?? '' ) ); ?> <span aria-hidden="true">→</span></a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
						<div class="td-bento__col td-bento__mock" aria-hidden="true">
							<?php if ( ! empty( $td_t['serp'] ) ) : ?>
								<div class="td-bento__serp">
									<span class="td-bento__serp-meta"><?php echo esc_html( (string) ( $td_t['serp']['meta'] ?? '' ) ); ?></span>
									<strong class="td-bento__serp-title"><?php echo esc_html( (string) ( $td_t['serp']['title'] ?? '' ) ); ?></strong>
									<span class="td-bento__serp-desc"><?php echo esc_html( (string) ( $td_t['serp']['desc'] ?? '' ) ); ?></span>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $td_t['answer'] ) ) : ?>
								<div class="td-bento__answer">
									<span class="td-bento__answer-label"><?php echo esc_html( (string) ( $td_t['answer']['label'] ?? '' ) ); ?></span>
									<span><?php echo $td_bold( $td_t['answer']['text'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php if ( ! empty( $td_t['answer']['tag'] ) ) : ?>
										<span class="td-bento__answer-tag"><span class="td-dot"></span><?php echo esc_html( (string) $td_t['answer']['tag'] ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $td_t['caption'] ) ) : ?>
								<span class="td-bento__caption"><?php echo esc_html( (string) $td_t['caption'] ); ?></span>
							<?php endif; ?>
						</div>
						<?php
						break;

					case 'platform-list':
						$td_head();
						?>
						<?php if ( ! empty( $td_t['platforms'] ) ) : ?>
							<ul class="td-bento__platforms" role="list">
								<?php foreach ( (array) $td_t['platforms'] as $td_p ) : ?>
									<li><span class="td-bento__mark" aria-hidden="true"><?php echo esc_html( (string) ( $td_p['mark'] ?? mb_substr( (string) ( $td_p['name'] ?? '' ), 0, 1 ) ) ); ?></span><?php echo esc_html( (string) ( $td_p['name'] ?? '' ) ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<div class="td-bento__foot">
							<?php if ( ! empty( $td_t['thumb'] ) ) : ?>
								<div class="td-bento__thumb">
									<?php
									echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										$td_t['thumb'],
										[
											'alt'   => '',
											'sizes' => '96px',
											'width' => 96,
										]
									);
									?>
									<?php if ( ! empty( $td_t['thumbCaption'] ) ) : ?>
										<span class="td-bento__thumb-cap"><?php echo esc_html( (string) $td_t['thumbCaption'] ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $td_t['text'] ) ) : ?>
								<span class="td-bento__text"><?php echo $td_text( $td_t['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
						</div>
						<?php
						break;

					case 'form':
						$td_head();
						?>
						<div class="td-bento__form" aria-hidden="true">
							<strong><?php echo esc_html( (string) ( $td_t['formTitle'] ?? '' ) ); ?></strong>
							<span class="td-bento__field"></span>
							<span class="td-bento__field"></span>
							<span class="td-bento__submit"><?php echo esc_html( (string) ( $td_t['formButton'] ?? '' ) ); ?></span>
						</div>
						<?php
						break;

					case 'sequence':
						$td_head();
						?>
						<ul class="td-bento__seq" role="list">
							<?php foreach ( (array) ( $td_t['rows'] ?? [] ) as $td_r ) : ?>
								<li>
									<?php if ( ! empty( $td_r['strong'] ) ) : ?>
										<strong><?php echo esc_html( (string) ( $td_r['label'] ?? '' ) ); ?></strong>
									<?php else : ?>
										<span><?php echo esc_html( (string) ( $td_r['label'] ?? '' ) ); ?></span>
									<?php endif; ?>
									<span class="td-bento__seq-status"><?php echo ! empty( $td_r['dot'] ) ? '<span class="td-dot" aria-hidden="true"></span>' : ''; ?><?php echo esc_html( (string) ( $td_r['status'] ?? '' ) ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
						<?php if ( ! empty( $td_t['caption'] ) ) : ?>
							<span class="td-bento__caption"><?php echo esc_html( (string) $td_t['caption'] ); ?></span>
						<?php endif; ?>
						<?php
						break;

					case 'flow':
						?>
						<div class="td-bento__col">
							<?php $td_head(); ?>
							<?php if ( ! empty( $td_t['text'] ) ) : ?>
								<p class="td-bento__text"><?php echo $td_text( $td_t['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
							<?php endif; ?>
						</div>
						<ol class="td-bento__flow" role="list">
							<?php foreach ( array_values( (array) ( $td_t['steps'] ?? [] ) ) as $td_i => $td_s ) : ?>
								<?php if ( $td_i > 0 ) : ?>
									<li class="td-bento__flow-arrow" aria-hidden="true">→</li>
								<?php endif; ?>
								<li class="td-bento__flow-step<?php echo ! empty( $td_s['highlight'] ) ? ' td-bento__flow-step--hi' : ''; ?>"><?php echo esc_html( (string) ( $td_s['label'] ?? '' ) ); ?><?php echo ! empty( $td_s['highlight'] ) ? ' <span class="td-dot" aria-hidden="true"></span>' : ''; ?></li>
							<?php endforeach; ?>
						</ol>
						<?php
						break;

					case 'photo':
					case 'image':
						echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							$td_t['image'] ?? '',
							[
								'alt'   => (string) ( $td_t['alt'] ?? '' ),
								'sizes' => '(max-width: 700px) 100vw, ' . ( (int) round( 1240 * $td_span / 12 ) ) . 'px',
								'class' => 'td-bento__bg',
							]
						);
						if ( 'photo' === $td_type ) :
							?>
							<div class="td-bento__shade" aria-hidden="true"></div>
							<div class="td-bento__overlay"><?php $td_head(); ?></div>
						<?php else : ?>
							<div class="td-bento__card">
								<?php if ( ! empty( $td_t['title'] ) ) : ?>
									<h3 class="td-bento__title"><?php echo $td_text( $td_t['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
								<?php endif; ?>
								<?php if ( ! empty( $td_t['text'] ) ) : ?>
									<p class="td-bento__text"><?php echo $td_text( $td_t['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
								<?php endif; ?>
							</div>
							<?php
						endif;
						break;

					case 'quote':
						?>
						<figure class="td-bento__quote">
							<?php echo td_icon( 'quote', 'td-bento__quote-mark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<blockquote><p><?php echo esc_html( (string) ( $td_t['quote'] ?? '' ) ); ?></p></blockquote>
							<figcaption class="td-bento__person">
								<?php
								if ( ! empty( $td_t['image'] ) ) {
									echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										$td_t['image'],
										[
											'alt'       => '',
											'sizes'     => '46px',
											'width'     => 46,
											'img_class' => 'td-bento__avatar',
										]
									);
								}
								?>
								<span><strong><?php echo esc_html( (string) ( $td_t['name'] ?? '' ) ); ?></strong><span><?php echo esc_html( (string) ( $td_t['role'] ?? '' ) ); ?></span></span>
							</figcaption>
						</figure>
						<?php
						break;

					case 'link':
						if ( ! empty( $td_t['image'] ) ) {
							echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$td_t['image'],
								[
									'alt'   => (string) ( $td_t['alt'] ?? '' ),
									'sizes' => '(max-width: 700px) 100vw, 300px',
									'class' => 'td-bento__side',
								]
							);
						}
						?>
						<div class="td-bento__body">
							<?php if ( ! empty( $td_t['chip'] ) ) : ?>
								<span class="td-bento__outline-chip"><?php echo esc_html( (string) $td_t['chip'] ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $td_t['title'] ) ) : ?>
								<h3 class="td-bento__title td-bento__title--sm"><?php echo $td_text( $td_t['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
							<?php endif; ?>
							<?php if ( '' !== $td_url && ! empty( $td_t['linkLabel'] ) ) : ?>
								<a class="td-link" href="<?php echo esc_url( $td_href( $td_url ) ); ?>"><?php echo esc_html( (string) $td_t['linkLabel'] ); ?></a>
							<?php endif; ?>
						</div>
						<?php
						break;

					default: // stat.
						$td_avatars = array_filter( (array) ( $td_t['avatars'] ?? [] ) );
						?>
						<div class="td-bento__top">
							<?php if ( $td_avatars || ! empty( $td_t['avatarsMore'] ) ) : ?>
								<span class="td-bento__avatars" aria-hidden="true">
									<?php
									foreach ( $td_avatars as $td_img ) {
										echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											$td_img,
											[
												'alt'       => '',
												'sizes'     => '32px',
												'width'     => 32,
												'img_class' => 'td-bento__avatar',
											]
										);
									}
									if ( ! empty( $td_t['avatarsMore'] ) ) {
										echo '<span class="td-bento__more">' . esc_html( (string) $td_t['avatarsMore'] ) . '</span>';
									}
									?>
								</span>
							<?php endif; ?>
							<?php if ( ! empty( $td_t['note'] ) ) : ?>
								<span class="td-bento__note"><?php echo $td_text( $td_t['note'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
						</div>
						<p class="td-bento__stat"><strong><?php echo esc_html( (string) ( $td_t['value'] ?? '' ) ); ?></strong><span><?php echo $td_text( $td_t['label'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></p>
						<?php
						break;
				}
				echo '</' . $td_tag . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			endforeach;
			?>
		</div>
	</div>
</section>
