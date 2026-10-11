<?php
/**
 * Small markup helpers shared by blocks, parts and templates (§2.3).
 * Every helper returns escaped HTML.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Button: primary (black pill + orange arrow circle), secondary (white pill,
 * ink border) or light (white pill + arrow, for dark sections).
 *
 * @param string              $label   Text.
 * @param string              $url     Link.
 * @param string              $variant primary|secondary|light.
 * @param array<string,mixed> $args    size (md|sm), class, attrs (array of extra attributes).
 */
function td_button( string $label, string $url, string $variant = 'primary', array $args = [] ): string {
	$size    = ( $args['size'] ?? 'md' ) === 'sm' ? ' td-btn--sm' : '';
	$classes = 'td-btn td-btn--' . sanitize_html_class( $variant ) . $size . ( ! empty( $args['class'] ) ? ' ' . $args['class'] : '' );
	$attrs   = '';
	foreach ( (array) ( $args['attrs'] ?? [] ) as $k => $v ) {
		$attrs .= ' ' . esc_attr( (string) $k ) . '="' . esc_attr( (string) $v ) . '"';
	}
	$arrow = 'secondary' === $variant ? '' : '<span class="td-btn__arrow">' . td_icon( 'arrow-up-right' ) . '</span>';
	return sprintf( '<a class="%s" href="%s"%s>%s%s</a>', esc_attr( $classes ), esc_url( $url ), $attrs, esc_html( $label ), $arrow );
}

/**
 * Label chip: orange dot + label.
 *
 * @param string $text  Label.
 * @param string $css_class Extra classes (td-chip--upper, td-chip--dark, td-chip--plain).
 */
function td_chip( string $text, string $css_class = '' ): string {
	if ( '' === trim( $text ) ) {
		return '';
	}
	return '<span class="td-chip' . ( $css_class ? ' ' . esc_attr( $css_class ) : '' ) . '">' . esc_html( $text ) . '</span>';
}

/**
 * Section heading: chip + H2 (with *accent*) + optional intro paragraph.
 *
 * @param array<string,mixed> $args label, title, intro, level (2|3), align (split|center), id.
 */
function td_section_head( array $args ): string {
	$args  = wp_parse_args(
		$args,
		[
			'label' => '',
			'title' => '',
			'intro' => '',
			'level' => 2,
			'align' => 'split',
			'id'    => '',
		]
	);
	$level = in_array( (int) $args['level'], [ 1, 2, 3 ], true ) ? (int) $args['level'] : 2;
	$out   = '<div class="td-section-head' . ( 'center' === $args['align'] ? ' td-section-head--center' : '' ) . '">';
	$out  .= '<div class="td-section-head__title">' . td_chip( (string) $args['label'] );
	if ( '' !== $args['title'] ) {
		$out .= sprintf( '<h%1$d class="td-h2"%3$s>%2$s</h%1$d>', $level, td_accent( (string) $args['title'] ), $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : '' );
	}
	$out .= '</div>';
	if ( '' !== $args['intro'] ) {
		$out .= '<p>' . td_mark_placeholders( esc_html( (string) $args['intro'] ) ) . '</p>';
	}
	return $out . '</div>';
}

/**
 * URL of an official platform logo file (assets/logos/platforms/<slug>.*,
 * copied into the theme by `npm run images`), or '' when none exists.
 *
 * @param string $slug Platform slug, e.g. "google-ads".
 */
function td_platform_logo_url( string $slug ): string {
	foreach ( [ 'svg', 'png', 'webp' ] as $ext ) {
		if ( '' !== $slug && is_readable( get_theme_file_path( "assets/img/logos/platforms/{$slug}.{$ext}" ) ) ) {
			return get_theme_file_uri( "assets/img/logos/platforms/{$slug}.{$ext}" );
		}
	}
	return '';
}

/**
 * Platform badge: official logo file when one exists in
 * assets/img/logos/platforms/<slug>.*, else the prototype's text badge (§0.6).
 *
 * @param string $name  Platform name ("Google Ads").
 * @param string $slug  File slug ("google-ads").
 * @param string $mark  Text mark for the fallback ("G").
 * @param bool   $ink   Ink (dark) mark background.
 */
function td_platform_badge( string $name, string $slug = '', string $mark = '', bool $ink = false ): string {
	$slug      = $slug ? $slug : sanitize_title( $name );
	$mark      = $mark ? $mark : mb_substr( $name, 0, 1 );
	$url       = td_platform_logo_url( $slug );
	$logo      = $url ? '<img src="' . esc_url( $url ) . '" alt="" width="18" height="18" loading="lazy">' : '';
	$mark_html = $logo ? $logo : esc_html( $mark );
	return '<span class="td-badge"><span class="td-badge__mark' . ( $ink && ! $logo ? ' td-badge__mark--ink' : '' ) . '" aria-hidden="true">' . $mark_html . '</span>' . esc_html( $name ) . '</span>';
}
