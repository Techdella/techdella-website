<?php
/**
 * Article components (guides, glossary, business library): shared helpers.
 *
 * Content comes from Markdown directives (`:::callout tip "Title"` … `:::`, see content/README.md),
 * which the importer turns into techdella/* blocks. Text attributes accept a small, safe subset of
 * Markdown: **bold**, *italic*, [links](url), paragraphs and "- " lists.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inline Markdown on escaped text: **bold**, *italic*, [text](url).
 *
 * @param string $text Raw text.
 */
function td_lite_inline( string $text ): string {
	$html = esc_html( $text );
	$html = (string) preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $html );
	$html = (string) preg_replace( '/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/u', '<em>$1</em>', $html );
	$html = (string) preg_replace_callback(
		'/\[([^\]]+)\]\(([^)\s]+)\)/u',
		static function ( array $m ): string {
			$url = html_entity_decode( $m[2], ENT_QUOTES );
			if ( preg_match( '/^(marketing|journal|studios):(\/.*)$/', $url, $s ) && function_exists( 'td_url' ) ) {
				$url = td_url( $s[1], $s[2] );
			} elseif ( str_starts_with( $url, '/' ) ) {
				$url = home_url( $url );
			}
			$ext = ! str_starts_with( $url, home_url() ) && preg_match( '#^https?://#', $url );
			return '<a href="' . esc_url( $url ) . '"' . ( $ext ? ' rel="noopener"' : '' ) . '>' . $m[1] . '</a>';
		},
		$html
	);
	return function_exists( 'td_mark_placeholders' ) ? td_mark_placeholders( $html ) : $html;
}

/**
 * Block-level lite Markdown: paragraphs and "- " lists.
 *
 * @param string $text Raw text.
 */
function td_lite( string $text ): string {
	$out = '';
	foreach ( preg_split( '/\n\s*\n/', trim( str_replace( "\r", '', $text ) ) ) as $chunk ) {
		$lines = array_values( array_filter( array_map( 'trim', explode( "\n", $chunk ) ), static fn( $l ) => '' !== $l ) );
		if ( ! $lines ) {
			continue;
		}
		if ( count( array_filter( $lines, static fn( $l ) => str_starts_with( $l, '- ' ) ) ) === count( $lines ) ) {
			$out .= '<ul>' . implode( '', array_map( static fn( $l ) => '<li>' . td_lite_inline( substr( $l, 2 ) ) . '</li>', $lines ) ) . '</ul>';
		} else {
			$out .= '<p>' . implode( '<br>', array_map( 'td_lite_inline', $lines ) ) . '</p>';
		}
	}
	return $out;
}

/**
 * A list attribute as rows: arrays pass through; strings split on newlines.
 *
 * @param mixed $value Attribute value.
 * @return array<int,mixed>
 */
function td_rows( $value ): array {
	if ( is_string( $value ) ) {
		$value = array_filter( array_map( 'trim', explode( "\n", $value ) ), static fn( $l ) => '' !== $l );
	}
	return array_values( array_filter( (array) $value, static fn( $r ) => is_array( $r ) || '' !== trim( (string) $r ) ) );
}

/**
 * Tables scroll sideways on phones, so their wrapper must be reachable by keyboard (axe
 * scrollable-region-focusable): give core/table figures tabindex="0" and a region label.
 */
add_filter(
	'render_block_core/table',
	static function ( string $html ): string {
		if ( str_contains( $html, 'tabindex=' ) ) {
			return $html;
		}
		return (string) preg_replace( '/<figure class="wp-block-table([^"]*)"/', '<figure class="wp-block-table$1" tabindex="0" role="region" aria-label="' . esc_attr__( 'Table', 'techdella' ) . '"', $html, 1 );
	}
);
