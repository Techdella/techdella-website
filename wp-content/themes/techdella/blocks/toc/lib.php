<?php
/**
 * Table of contents helpers: headings are read server-side from the post's
 * saved content, and the matching ids are added to the rendered H2/H3s by a
 * the_content filter (registered when this file loads; the integrator can
 * require it from functions.php so ids are always present).
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'td_toc_key' ) ) {
	/**
	 * Comparison key for heading text, stable across wptexturize/entities.
	 *
	 * @param string $html Heading inner HTML.
	 */
	function td_toc_key( string $html ): string {
		$text = html_entity_decode( wp_strip_all_tags( wptexturize( $html ) ), ENT_QUOTES, 'UTF-8' );
		return (string) preg_replace( '/[^a-z0-9]+/', '', strtolower( remove_accents( $text ) ) );
	}
}

if ( ! function_exists( 'td_toc_items' ) ) {
	/**
	 * H2/H3 headings of a post, in order, with the ids the rendered content will carry.
	 *
	 * @param int $post_id Post ID.
	 * @param int $depth   Deepest level (2 or 3).
	 * @return array<int,array{level:int,text:string,id:string,key:string,explicit:bool}>
	 */
	function td_toc_items( int $post_id, int $depth = 3 ): array {
		static $cache = [];
		if ( isset( $cache[ $post_id ] ) ) {
			return array_values( array_filter( $cache[ $post_id ], static fn( $i ) => $i['level'] <= $depth ) );
		}
		$content = (string) get_post_field( 'post_content', $post_id );
		$items   = [];
		$used    = [];
		if ( preg_match_all( '/<h([23])\b([^>]*)>(.*?)<\/h\1>/is', $content, $m, PREG_SET_ORDER ) ) {
			// Explicit ids first, so generated ids never collide with them.
			foreach ( $m as $h ) {
				if ( preg_match( '/\bid=["\']([^"\']+)["\']/', $h[2], $idm ) ) {
					$used[ $idm[1] ] = true;
				}
			}
			foreach ( $m as $h ) {
				$text = trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( $h[3] ) ) );
				if ( '' === $text ) {
					continue;
				}
				$explicit = (bool) preg_match( '/\bid=["\']([^"\']+)["\']/', $h[2], $idm );
				if ( $explicit ) {
					$id = $idm[1];
				} else {
					$base = sanitize_title( $text );
					$base = '' !== $base ? $base : 'section';
					$id   = $base;
					for ( $n = 2; isset( $used[ $id ] ); $n++ ) {
						$id = $base . '-' . $n;
					}
					$used[ $id ] = true;
				}
				$items[] = [
					'level'    => (int) $h[1],
					'text'     => html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ),
					'id'       => $id,
					'key'      => td_toc_key( $h[3] ),
					'explicit' => $explicit,
				];
			}
		}
		$cache[ $post_id ] = $items;
		return array_values( array_filter( $items, static fn( $i ) => $i['level'] <= $depth ) );
	}
}

if ( ! function_exists( 'td_toc_add_ids' ) ) {
	/**
	 * Content filter (the_content): give each H2/H3 without an id the id td_toc_items() promised.
	 * Headings that are not in the saved content (from dynamic blocks) are left alone.
	 *
	 * @param string $content Rendered content.
	 */
	function td_toc_add_ids( string $content ): string {
		$post_id = (int) get_the_ID();
		if ( ! $post_id || ! preg_match( '/<h[23]\b/i', $content ) ) {
			return $content;
		}
		$queue = [];
		foreach ( td_toc_items( $post_id ) as $item ) {
			if ( ! $item['explicit'] ) {
				$queue[ $item['level'] . ':' . $item['key'] ][] = $item['id'];
			}
		}
		if ( ! $queue ) {
			return $content;
		}
		return (string) preg_replace_callback(
			'/<h([23])\b([^>]*)>(.*?)<\/h\1>/is',
			static function ( array $h ) use ( &$queue ): string {
				if ( preg_match( '/\bid=["\']/', $h[2] ) ) {
					return $h[0];
				}
				$k = $h[1] . ':' . td_toc_key( $h[3] );
				if ( empty( $queue[ $k ] ) ) {
					return $h[0];
				}
				$id = (string) array_shift( $queue[ $k ] );
				return '<h' . $h[1] . ' id="' . esc_attr( $id ) . '"' . $h[2] . '>' . $h[3] . '</h' . $h[1] . '>';
			},
			$content
		);
	}
}

if ( ! has_filter( 'the_content', 'td_toc_add_ids' ) ) {
	// After do_blocks (9), wptexturize/wpautop (10) and shortcodes (11).
	add_filter( 'the_content', 'td_toc_add_ids', 12 );
}
