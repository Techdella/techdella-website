<?php
/**
 * Shared helpers for the content blocks (case-card, case-grid, post-*, toc,
 * author-box …). Loaded with require_once from each render.php; every
 * function is guarded so a second load (or an integrator's require) is safe.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'td_a3_href' ) ) {
	/**
	 * Resolve a block URL: "/path/" → this site, "studios:/start/" → another site, else as given.
	 *
	 * @param string $url URL or path.
	 */
	function td_a3_href( string $url ): string {
		if ( preg_match( '/^(marketing|journal|studios):(\/.*)$/', $url, $m ) ) {
			return td_url( $m[1], $m[2] );
		}
		return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
	}
}

if ( ! function_exists( 'td_a3_is_preview' ) ) {
	/**
	 * Editor (ServerSideRender over REST) or the /styleguide/ page: places where an
	 * empty block should explain itself instead of rendering nothing.
	 */
	function td_a3_is_preview(): bool {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}
		return function_exists( 'is_page' ) && ( is_page( 'styleguide' ) || is_page_template( 'page-styleguide.php' ) );
	}
}

if ( ! function_exists( 'td_a3_notice' ) ) {
	/**
	 * Editor/styleguide-only notice for a block with nothing to show.
	 *
	 * @param string $text Message.
	 */
	function td_a3_notice( string $text ): string {
		return td_a3_is_preview() ? '<p class="td-a3-notice">' . esc_html( $text ) . '</p>' : '';
	}
}

if ( ! function_exists( 'td_a3_text' ) ) {
	/**
	 * Escaped text with [CONFIRM]/[PRICE] tokens made visible.
	 *
	 * @param mixed $value Text.
	 */
	function td_a3_text( $value ): string {
		return td_mark_placeholders( esc_html( (string) $value ) );
	}
}

if ( ! function_exists( 'td_a3_case_from_post' ) ) {
	/**
	 * Card data for a case_study or studio_work post. Case studies without
	 * permission_confirmed return null (§7: never show unconfirmed proof).
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>|null
	 */
	function td_a3_case_from_post( int $post_id ): ?array {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status || ! in_array( $post->post_type, [ 'case_study', 'studio_work' ], true ) ) {
			return null;
		}
		$type = $post->post_type;
		if ( 'case_study' === $type && ! td_field( 'permission_confirmed', $post_id ) ) {
			return null;
		}
		$metrics = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$num = trim( (string) td_field( "metric_{$i}_num", $post_id ) );
			if ( '' !== $num ) {
				$metrics[] = [
					'num'   => $num,
					'label' => (string) td_field( "metric_{$i}_label", $post_id ),
				];
			}
		}
		$industry = '';
		$terms    = taxonomy_exists( 'td_industry' ) ? get_the_terms( $post_id, 'td_industry' ) : false;
		if ( is_array( $terms ) && $terms ) {
			$industry = $terms[0]->name;
		}
		$disciplines = [];
		foreach ( (array) td_field( 'disciplines', $post_id ) as $row ) {
			$d = is_array( $row ) ? (string) ( $row['discipline'] ?? '' ) : (string) $row;
			if ( '' !== trim( $d ) ) {
				$disciplines[] = $d;
			}
		}
		$image = (int) get_post_thumbnail_id( $post_id );
		if ( ! $image ) {
			$gallery = td_field( 'gallery', $post_id );
			$first   = is_array( $gallery ) ? reset( $gallery ) : false;
			$image   = is_array( $first ) ? (int) ( $first['ID'] ?? $first['id'] ?? 0 ) : (int) $first;
		}
		$status = '';
		if ( 'studio_work' === $type ) {
			$status = 'Live' === td_field( 'status', $post_id ) ? 'Live' : 'Concept';
		}
		return [
			'type'        => $type,
			'title'       => get_the_title( $post ),
			'url'         => (string) get_permalink( $post ),
			'client'      => (string) td_field( 'client', $post_id ),
			'industry'    => $industry,
			'country'     => (string) td_field( 'country', $post_id ),
			'timeframe'   => (string) td_field( 'timeframe', $post_id ),
			'excerpt'     => has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 26 ),
			'metrics'     => $metrics,
			'status'      => $status,
			'disciplines' => $disciplines,
			'image'       => $image,
			'imageAlt'    => '',
			'visual'      => '',
		];
	}
}

if ( ! function_exists( 'td_a3_case_normalize' ) ) {
	/**
	 * Normalize an inline (attribute) case item to the shape td_a3_case_from_post() returns.
	 *
	 * @param array<string,mixed> $item Inline item.
	 * @param string              $type case_study|studio_work.
	 * @return array<string,mixed>
	 */
	function td_a3_case_normalize( array $item, string $type ): array {
		$status = '';
		if ( 'studio_work' === $type || ! empty( $item['status'] ) ) {
			$status = 'Live' === ( $item['status'] ?? '' ) ? 'Live' : 'Concept';
		}
		return [
			'type'        => $type,
			'title'       => (string) ( $item['title'] ?? '' ),
			'url'         => td_a3_href( (string) ( $item['url'] ?? '' ) ),
			'client'      => (string) ( $item['client'] ?? '' ),
			'industry'    => (string) ( $item['industry'] ?? '' ),
			'country'     => (string) ( $item['country'] ?? '' ),
			'timeframe'   => (string) ( $item['timeframe'] ?? '' ),
			'excerpt'     => (string) ( $item['excerpt'] ?? '' ),
			'metrics'     => array_values( array_filter( (array) ( $item['metrics'] ?? [] ), static fn( $m ) => is_array( $m ) && '' !== (string) ( $m['num'] ?? '' ) ) ),
			'status'      => $status,
			'disciplines' => array_map( 'strval', (array) ( $item['disciplines'] ?? [] ) ),
			'image'       => $item['image'] ?? '',
			'imageAlt'    => (string) ( $item['imageAlt'] ?? '' ),
			'visual'      => (string) ( $item['visual'] ?? '' ),
		];
	}
}

if ( ! function_exists( 'td_a3_work_visual' ) ) {
	/**
	 * Neutral UI placeholder for a work card without an image (HTML/CSS, no fake data).
	 *
	 * @param string $kind table|phones|chat|video.
	 */
	function td_a3_work_visual( string $kind ): string {
		switch ( $kind ) {
			case 'phones':
				return '<div class="td-work-visual td-work-visual--phones" aria-hidden="true"><span class="td-work-visual__phone"><i></i><i></i><i></i><b></b></span><span class="td-work-visual__phone td-work-visual__phone--alt"><b></b><i></i><i></i></span></div>';
			case 'chat':
				return '<div class="td-work-visual td-work-visual--chat" aria-hidden="true"><span class="td-work-visual__msg td-work-visual__msg--out"></span><span class="td-work-visual__msg"></span><span class="td-work-visual__msg td-work-visual__msg--short"></span><span class="td-work-visual__chips"><i></i><i></i></span></div>';
			case 'video':
				return '<div class="td-work-visual td-work-visual--video" aria-hidden="true"><span class="td-work-visual__screen"><i></i><i></i><span class="td-work-visual__play"></span><span class="td-work-visual__bar"><b></b></span></span></div>';
			default:
				return '<div class="td-work-visual td-work-visual--table" aria-hidden="true"><span class="td-work-visual__panel"><b></b><i></i><i></i><i></i></span></div>';
		}
	}
}

if ( ! function_exists( 'td_a3_case_card' ) ) {
	/**
	 * Card markup. Variants: "card" (sand proof card with metrics) and "cover"
	 * (visual on top; Studios work, with a visible Concept label).
	 *
	 * @param array<string,mixed> $c       Normalized case data.
	 * @param string              $variant card|cover.
	 * @param int                 $level   Heading level for the title.
	 */
	function td_a3_case_card( array $c, string $variant = 'card', int $level = 3 ): string {
		if ( '' === trim( (string) $c['title'] ) ) {
			return '';
		}
		$level   = in_array( $level, [ 2, 3, 4 ], true ) ? $level : 3;
		$concept = 'Concept' === $c['status'];
		$url     = (string) $c['url'];
		$title   = esc_html( (string) $c['title'] );
		$title   = '' !== $url ? '<a class="td-case-card__link" href="' . esc_url( $url ) . '">' . $title . '</a>' : $title;
		$meta    = array_filter( [ $c['industry'], $c['timeframe'], $c['country'] ], static fn( $v ) => '' !== trim( (string) $v ) );

		if ( 'cover' === $variant ) {
			$tag = $c['disciplines'] ? implode( ' · ', $c['disciplines'] ) : (string) $c['industry'];
			$tag = $concept ? trim( 'Concept · ' . $tag, ' ·' ) : $tag;
			$out = '<article class="td-case-card td-case-card--cover' . ( $concept ? ' td-case-card--concept' : '' ) . '">';
			$out .= '<div class="td-case-card__visual">';
			if ( $concept ) {
				$out .= '<span class="td-label-example">' . esc_html__( 'Concept', 'techdella' ) . '</span>';
			} elseif ( 'Live' === $c['status'] ) {
				$out .= '<span class="td-case-card__live"><span class="td-dot" aria-hidden="true"></span>' . esc_html__( 'Live', 'techdella' ) . '</span>';
			}
			$img  = $c['image'] ? td_image(
				$c['image'],
				[
					'alt'   => (string) $c['imageAlt'],
					'sizes' => '(max-width: 900px) 100vw, 600px',
				]
			) : '';
			$out .= '' !== $img && ! str_starts_with( $img, '<!--' ) ? $img : td_a3_work_visual( (string) $c['visual'] );
			$out .= '</div><div class="td-case-card__row">';
			$out .= sprintf( '<h%1$d class="td-case-card__title">%2$s</h%1$d>', $level, $title );
			if ( '' !== $tag ) {
				$out .= '<span class="td-case-card__tag' . ( $concept ? '' : ' td-case-card__tag--ink' ) . '">' . esc_html( $tag ) . '</span>';
			}
			$out .= '</div>';
			if ( '' !== $c['excerpt'] ) {
				$out .= '<p class="td-case-card__text">' . td_a3_text( $c['excerpt'] ) . '</p>';
			}
			return $out . '</article>';
		}

		$label = 'studio_work' === $c['type'] ? __( 'Work', 'techdella' ) : __( 'Case study', 'techdella' );
		if ( '' !== (string) $c['client'] && false === stripos( (string) $c['title'], (string) $c['client'] ) ) {
			$label .= ' · ' . $c['client'];
		}
		$out  = '<article class="td-case-card td-case-card--card' . ( $concept ? ' td-case-card--concept' : '' ) . '">';
		$out .= '<span class="td-case-card__chip">' . esc_html( $label ) . '</span>';
		if ( $concept ) {
			$out .= '<span class="td-case-card__concept">' . esc_html__( 'Concept', 'techdella' ) . '</span>';
		}
		$out .= sprintf( '<h%1$d class="td-case-card__title">%2$s</h%1$d>', $level, $title );
		if ( '' !== $c['excerpt'] ) {
			$out .= '<p class="td-case-card__text">' . td_a3_text( $c['excerpt'] ) . '</p>';
		}
		if ( $c['metrics'] ) {
			$out .= '<dl class="td-case-card__metrics">';
			foreach ( array_slice( (array) $c['metrics'], 0, 3 ) as $i => $m ) {
				$out .= '<div class="td-case-card__metric' . ( 0 === $i ? ' td-case-card__metric--hl' : '' ) . '"><dt>' . td_a3_text( $m['label'] ?? '' ) . '</dt><dd>' . td_a3_text( $m['num'] ) . '</dd></div>';
			}
			$out .= '</dl>';
		}
		if ( $meta ) {
			$out .= '<p class="td-case-card__meta">' . td_a3_text( implode( ' · ', $meta ) ) . '</p>';
		}
		return $out . '</article>';
	}
}
