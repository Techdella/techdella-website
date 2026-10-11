<?php
/**
 * Shared helpers for the Phase 3 Marketing CPT templates (case studies,
 * solutions, locations, glossary, resources, tools, 404).
 *
 * Template-scoped: loaded with require_once from each template, so nothing
 * here runs on other pages. CSS for these templates is inlined once in
 * <head> (§8.4 "critical CSS inlined per template") from m2.css.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'td_m2_styles' ) ) {
	/**
	 * Print the template CSS in <head>. Call before get_header().
	 */
	function td_m2_styles(): void {
		add_action(
			'wp_head',
			static function () {
				$file = __DIR__ . '/m2.css';
				if ( ! is_readable( $file ) ) {
					return;
				}
				$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );
				$css = (string) preg_replace( '/\s+/', ' ', $css );
				echo '<style id="td-m2-css">' . wp_strip_all_tags( $css ) . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme CSS file.
			},
			20
		);
	}
}

if ( ! function_exists( 'td_m2_ids' ) ) {
	/**
	 * Normalize a relationship / post_object / gallery value to a list of IDs.
	 *
	 * @param mixed $value Field value (IDs, WP_Post objects, ACF arrays).
	 * @return int[]
	 */
	function td_m2_ids( $value ): array {
		if ( null === $value || '' === $value || false === $value ) {
			return [];
		}
		$list = is_array( $value ) && ! isset( $value['ID'] ) && ! isset( $value['id'] ) ? $value : [ $value ];
		$ids  = [];
		foreach ( $list as $item ) {
			if ( $item instanceof WP_Post ) {
				$ids[] = (int) $item->ID;
			} elseif ( is_array( $item ) ) {
				$ids[] = (int) ( $item['ID'] ?? $item['id'] ?? 0 );
			} elseif ( is_numeric( $item ) ) {
				$ids[] = (int) $item;
			}
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}
}

if ( ! function_exists( 'td_m2_rows' ) ) {
	/**
	 * A repeater value as a list of rows (arrays), dropping empty rows.
	 *
	 * @param mixed $value Field value.
	 * @return array<int,array<string,mixed>>
	 */
	function td_m2_rows( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}
		return array_values(
			array_filter(
				$value,
				static fn( $row ) => is_array( $row ) && '' !== trim( implode( '', array_map( static fn( $v ) => is_scalar( $v ) ? (string) $v : '', $row ) ) )
			)
		);
	}
}

if ( ! function_exists( 'td_m2_faq' ) ) {
	/**
	 * FAQ repeater rows → faq block items.
	 *
	 * @param mixed $value faq field.
	 * @return array<int,array{question:string,answer:string}>
	 */
	function td_m2_faq( $value ): array {
		$items = [];
		foreach ( td_m2_rows( $value ) as $row ) {
			$q = trim( (string) ( $row['question'] ?? '' ) );
			$a = trim( (string) ( $row['answer'] ?? '' ) );
			if ( '' !== $q && '' !== $a ) {
				$items[] = [
					'question' => $q,
					'answer'   => $a,
				];
			}
		}
		return $items;
	}
}

if ( ! function_exists( 'td_m2_accent' ) ) {
	/**
	 * Headline text with one *accent* word for td_accent()/hero titles (§2.2).
	 * Accents $word when it appears in the title, else the last word.
	 *
	 * @param string $title Plain title.
	 * @param string $word  Preferred accent word or phrase.
	 */
	function td_m2_accent( string $title, string $word = '' ): string {
		$title = trim( str_replace( '*', '', wp_strip_all_tags( $title ) ) );
		if ( '' === $title ) {
			return '';
		}
		$word = trim( $word );
		if ( '' !== $word ) {
			$pos = stripos( $title, $word );
			if ( false !== $pos ) {
				return substr( $title, 0, $pos ) . '*' . substr( $title, $pos, strlen( $word ) ) . '*' . substr( $title, $pos + strlen( $word ) );
			}
		}
		if ( ! str_contains( $title, ' ' ) ) {
			return '*' . $title . '*';
		}
		$parts = explode( ' ', $title );
		$last  = (string) array_pop( $parts );
		return implode( ' ', $parts ) . ' *' . $last . '*';
	}
}

if ( ! function_exists( 'td_m2_paras' ) ) {
	/**
	 * Escaped paragraphs from a textarea value, with [CONFIRM] tokens marked.
	 *
	 * @param mixed $text Text.
	 */
	function td_m2_paras( $text ): string {
		$text = trim( (string) $text );
		if ( '' === $text ) {
			return '';
		}
		// Lines starting with "- " become a list; everything else is paragraphs.
		$out   = '';
		$items = [];
		foreach ( preg_split( '/\n{2,}/', str_replace( "\r", '', $text ) ) as $block ) {
			$lines = array_map( 'trim', explode( "\n", trim( $block ) ) );
			foreach ( $lines as $line ) {
				if ( str_starts_with( $line, '- ' ) ) {
					$items[] = '<li>' . esc_html( substr( $line, 2 ) ) . '</li>';
					continue;
				}
				if ( $items ) {
					$out  .= '<ul>' . implode( '', $items ) . '</ul>';
					$items = [];
				}
				if ( preg_match( '/^[A-Z][^.!?]{2,40}:$/u', $line ) ) {
					// A short "Heading:" line (e.g. "Key Takeaway:") is a subheading.
					$out .= '<h3 class="td-m2-story__sub">' . esc_html( rtrim( $line, ':' ) ) . '</h3>';
				} elseif ( '' !== $line ) {
					$out .= '<p>' . esc_html( $line ) . '</p>';
				}
			}
			if ( $items ) {
				$out  .= '<ul>' . implode( '', $items ) . '</ul>';
				$items = [];
			}
		}
		return td_mark_placeholders( $out );
	}
}

if ( ! function_exists( 'td_m2_breadcrumbs' ) ) {
	/**
	 * Breadcrumbs in a container, under the hero (§8.3).
	 */
	function td_m2_breadcrumbs(): void {
		if ( ! function_exists( 'td_breadcrumbs' ) ) {
			return;
		}
		echo '<div class="td-container td-m2-crumbs">';
		td_breadcrumbs();
		echo '</div>';
	}
}

if ( ! function_exists( 'td_m2_lead' ) ) {
	/**
	 * Lead text for a post: its excerpt, else the first words of its content.
	 *
	 * @param WP_Post|int|null $post  Post.
	 * @param int              $words Word count for the fallback.
	 */
	function td_m2_lead( $post = null, int $words = 32 ): string {
		$post = get_post( $post );
		if ( ! $post ) {
			return '';
		}
		if ( has_excerpt( $post ) ) {
			return trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
		}
		return wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ), $words );
	}
}

if ( ! function_exists( 'td_m2_card' ) ) {
	/**
	 * A simple linked card (hub grids): kicker, title, text, meta line.
	 *
	 * @param array<string,string> $card  kicker, title, text, meta, url, tone (paper|canvas|sand).
	 * @param int                  $level Heading level.
	 */
	function td_m2_card( array $card, int $level = 2 ): string {
		$tone = in_array( $card['tone'] ?? '', [ 'canvas', 'sand' ], true ) ? $card['tone'] : 'paper';
		$tag  = 'h' . max( 2, min( 4, $level ) );
		$out  = '<article class="td-m2-card td-m2-card--' . esc_attr( $tone ) . '">';
		if ( ! empty( $card['kicker'] ) ) {
			$out .= '<p class="td-m2-card__kicker"><span class="td-dot" aria-hidden="true"></span>' . esc_html( $card['kicker'] ) . '</p>';
		}
		$out .= '<' . $tag . ' class="td-m2-card__title"><a class="td-m2-card__link" href="' . esc_url( (string) ( $card['url'] ?? '#' ) ) . '">' . esc_html( (string) ( $card['title'] ?? '' ) ) . '</a></' . $tag . '>';
		if ( ! empty( $card['text'] ) ) {
			$out .= '<p class="td-m2-card__text">' . td_mark_placeholders( esc_html( (string) $card['text'] ) ) . '</p>';
		}
		if ( ! empty( $card['meta'] ) ) {
			$out .= '<p class="td-m2-card__meta">' . td_mark_placeholders( esc_html( (string) $card['meta'] ) ) . '</p>';
		}
		$out .= '<span class="td-m2-card__arrow" aria-hidden="true">' . td_icon( 'arrow-up-right' ) . '</span>';
		return $out . '</article>';
	}
}

if ( ! function_exists( 'td_m2_query_arg' ) ) {
	/**
	 * A sanitized slug from the query string (hub filter chips are plain links).
	 *
	 * @param string $key Query arg.
	 */
	function td_m2_query_arg( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter link, no state change.
		return isset( $_GET[ $key ] ) ? sanitize_title( wp_unslash( (string) $_GET[ $key ] ) ) : '';
	}
}

if ( ! function_exists( 'td_m2_chips' ) ) {
	/**
	 * Filter chips as links (works without JS). The current one carries aria-current.
	 *
	 * @param string                                                 $label   Accessible nav label.
	 * @param array<int,array{label:string,url:string,current:bool}> $chips Chips.
	 */
	function td_m2_chips( string $label, array $chips ): string {
		if ( count( $chips ) < 2 ) {
			return '';
		}
		$out = '<nav class="td-m2-chips" aria-label="' . esc_attr( $label ) . '"><ul role="list">';
		foreach ( $chips as $chip ) {
			$out .= '<li><a class="td-m2-chips__chip' . ( $chip['current'] ? ' is-current' : '' ) . '" href="' . esc_url( $chip['url'] ) . '"' . ( $chip['current'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $chip['label'] ) . '</a></li>';
		}
		return $out . '</ul></nav>';
	}
}

if ( ! function_exists( 'td_m2_service_link' ) ) {
	/**
	 * Title + URL for a service post ID (published only).
	 *
	 * @param int $id Post ID.
	 * @return array{title:string,url:string}|null
	 */
	function td_m2_service_link( int $id ): ?array {
		$post = $id ? get_post( $id ) : null;
		if ( ! $post || 'publish' !== $post->post_status ) {
			return null;
		}
		return [
			'title' => get_the_title( $post ),
			'url'   => (string) get_permalink( $post ),
		];
	}
}
