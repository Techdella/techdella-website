<?php
/**
 * Feature grid: rule | numbered | icon | checks | tiles.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

// No section title → item titles are the section's headings (keeps the H1 → H2 order).
$td_item_h = empty( $attributes['title'] ) ? 'h2' : 'h3';
$td_items  = array_values( array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['title'] ) ) );
if ( ! $td_items ) {
	return;
}
$td_href    = static function ( string $url ): string {
	if ( preg_match( '/^(marketing|journal|studios):(\/.*)$/', $url, $m ) ) {
		return td_url( $m[1], $m[2] );
	}
	return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
};
$td_text    = static fn( $v ): string => td_mark_placeholders( esc_html( (string) $v ) );
$td_style   = in_array( $attributes['style'] ?? '', [ 'numbered', 'icon', 'checks', 'tiles' ], true ) ? $attributes['style'] : 'rule';
$td_surface = in_array( $attributes['surface'] ?? '', [ 'canvas', 'sand', 'dark' ], true ) ? $attributes['surface'] : 'paper';
$td_align   = in_array( $attributes['align'] ?? '', [ 'split', 'center' ], true ) ? $attributes['align'] : 'left';
$td_cols    = max( 0, min( 4, (int) ( $attributes['columns'] ?? 0 ) ) );
$td_hl      = (int) ( $attributes['highlight'] ?? -1 );
if ( -1 === $td_hl ) {
	$td_hl = in_array( $td_style, [ 'rule', 'numbered' ], true ) ? count( $td_items ) - 1 : -2;
}
$td_bare = ! empty( $attributes['bare'] );

// Inline outline icons (prototype set). Generic shapes, not logos.
$td_icons = [
	'list'   => '<path d="M4 6h16M4 12h10M4 18h7"/>',
	'lines'  => '<path d="M4 6h16M4 12h16M4 18h9"/>',
	'nodes'  => '<circle cx="6" cy="6" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="12" cy="18" r="2.5"/><path d="M8 7.5l3 8.5M16 7.5l-3 8.5"/>',
	'chart'  => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 15l3-3 3 2 4-5"/>',
	'person' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/>',
	'chat'   => '<path d="M4 5h16v11H8l-4 4z"/>',
	'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
	'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/>',
	'check'  => '<path d="M5 12l5 5L20 7"/>',
];
$td_icon  = static function ( string $name ) use ( $td_icons ): string {
	if ( isset( $td_icons[ $name ] ) ) {
		return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">' . $td_icons[ $name ] . '</svg>';
	}
	return td_icon( $name );
};

$td_head = '';
if ( 'checks' !== $td_style && ( ! empty( $attributes['title'] ) || ! empty( $attributes['label'] ) ) ) {
	if ( 'left' === $td_align ) {
		$td_head = '<div class="td-feature-grid__head">' . td_chip( (string) ( $attributes['label'] ?? '' ) )
			. ( ! empty( $attributes['title'] ) ? '<h2 class="td-feature-grid__title">' . td_accent( (string) $attributes['title'] ) . '</h2>' : '' )
			. ( ! empty( $attributes['intro'] ) ? '<p class="td-feature-grid__intro">' . $td_text( $attributes['intro'] ) . '</p>' : '' )
			. '</div>';
	} else {
		$td_head = td_section_head(
			[
				'label' => (string) ( $attributes['label'] ?? '' ),
				'title' => (string) ( $attributes['title'] ?? '' ),
				'intro' => (string) ( $attributes['intro'] ?? '' ),
				'align' => $td_align,
			]
		);
	}
}

$td_grid_style = $td_cols ? '--td-fg-cols:' . $td_cols : '';
ob_start();
if ( 'checks' === $td_style ) :
	?>
	<div class="td-feature-grid__card">
		<?php if ( ! empty( $attributes['title'] ) ) : ?>
			<h2 class="td-feature-grid__card-title"><?php echo td_accent( (string) $attributes['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
		<?php endif; ?>
		<?php if ( ! empty( $attributes['lead'] ) ) : ?>
			<p class="td-feature-grid__lead"><?php echo $td_text( $attributes['lead'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<ul class="td-feature-grid__checks" role="list">
			<?php foreach ( $td_items as $td_item ) : ?>
				<li><span class="td-feature-grid__check" aria-hidden="true"><?php echo $td_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span><strong><?php echo $td_text( $td_item['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong><?php echo ! empty( $td_item['text'] ) ? ' ' . $td_text( $td_item['text'] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
else :
	?>
	<ul class="td-feature-grid__grid" role="list"<?php echo $td_grid_style ? ' style="' . esc_attr( $td_grid_style ) . '"' : ''; ?>>
		<?php foreach ( $td_items as $td_i => $td_item ) : ?>
			<?php
			$td_tone = in_array( $td_item['tone'] ?? '', [ 'canvas', 'sand', 'dark' ], true ) ? $td_item['tone'] : ( 'icon' === $td_style ? 'paper' : '' );
			$td_on   = $td_i === $td_hl;
			$td_cls  = 'td-feature-grid__item' . ( $td_tone ? ' td-feature-grid__item--' . $td_tone : '' ) . ( $td_on ? ' td-feature-grid__item--hl' : '' );
			$td_url  = ! empty( $td_item['url'] ) ? $td_href( (string) $td_item['url'] ) : '';
			?>
			<li class="<?php echo esc_attr( $td_cls ); ?>">
				<?php if ( 'numbered' === $td_style ) : ?>
					<span class="td-feature-grid__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $td_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
				<?php elseif ( 'icon' === $td_style && ! empty( $td_item['icon'] ) ) : ?>
					<span class="td-feature-grid__icon"><?php echo $td_icon( (string) $td_item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php endif; ?>
				<<?php echo $td_item_h; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- h2|h3 literal. ?> class="td-feature-grid__item-title">
					<?php if ( $td_url ) : ?>
						<a href="<?php echo esc_url( $td_url ); ?>"><?php echo $td_text( $td_item['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php else : ?>
						<?php echo $td_text( $td_item['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</<?php echo $td_item_h; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php if ( ! empty( $td_item['text'] ) ) : ?>
					<p class="td-feature-grid__text"><?php echo $td_text( $td_item['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
endif;
$td_body = (string) ob_get_clean();

$td_class = 'td-feature-grid td-feature-grid--' . $td_style . ( $td_bare ? ' td-feature-grid--bare' : ' td-section' . ( 'paper' !== $td_surface ? ' td-section--' . $td_surface : '' ) );
?>
<div <?php echo td_block_wrapper( $td_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( ! $td_bare ) : ?>
		<div class="td-container td-feature-grid__inner">
	<?php endif; ?>
	<?php echo $td_head . $td_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php if ( ! $td_bare ) : ?>
		</div>
	<?php endif; ?>
</div>
