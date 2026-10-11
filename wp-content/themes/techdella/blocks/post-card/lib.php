<?php
/**
 * Journal helpers shared by post-card, post-grid, post-featured, post-header and author-box.
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';

if ( ! function_exists( 'td_a3_reading_time' ) ) {
	/**
	 * Minutes to read (230 wpm, at least 1).
	 *
	 * @param int $post_id Post ID.
	 */
	function td_a3_reading_time( int $post_id ): int {
		$words = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );
		return max( 1, (int) round( $words / 230 ) );
	}
}

if ( ! function_exists( 'td_a3_person' ) ) {
	/**
	 * A named person for a WP user: their td_team profile (role, photo, LinkedIn)
	 * when one is linked through the wp_user field, else the user's display name.
	 * Never returns a team/brand byline (§8.1 E-E-A-T): returns null when the
	 * user has no real name.
	 *
	 * @param int $user_id User ID.
	 * @return array{name:string,role:string,bio:string,photo:int|string,linkedin:string,url:string}|null
	 */
	function td_a3_person( int $user_id ): ?array {
		$user = $user_id ? get_userdata( $user_id ) : false;
		if ( ! $user ) {
			return null;
		}
		$person = [
			'name'     => (string) $user->display_name,
			'role'     => '',
			'bio'      => (string) get_the_author_meta( 'description', $user_id ),
			'photo'    => 0,
			'linkedin' => '',
			'url'      => (string) get_author_posts_url( $user_id ),
		];
		if ( post_type_exists( 'td_team' ) ) {
			$team = get_posts(
				[
					'post_type'      => 'td_team',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						[
							'key'   => 'wp_user',
							'value' => (string) $user_id,
						],
					],
				]
			);
			if ( $team ) {
				$tid                = (int) $team[0];
				$photo              = td_field( 'photo', $tid );
				$person['name']     = get_the_title( $tid );
				$person['role']     = (string) td_field( 'role', $tid );
				$person['bio']      = (string) ( td_field( 'bio', $tid ) ?? $person['bio'] );
				$person['photo']    = is_array( $photo ) ? (int) ( $photo['ID'] ?? $photo['id'] ?? 0 ) : (int) $photo;
				$person['linkedin'] = (string) td_field( 'linkedin', $tid );
			}
		}
		$generic = [ 'techdella team', 'techdella', 'admin', 'administrator', '' ];
		if ( in_array( strtolower( trim( $person['name'] ) ), $generic, true ) ) {
			return null;
		}
		return $person;
	}
}

if ( ! function_exists( 'td_a3_primary_category' ) ) {
	/**
	 * First category of a post (Rank Math primary category when set).
	 *
	 * @param int $post_id Post ID.
	 */
	function td_a3_primary_category( int $post_id ): ?WP_Term {
		$primary = (int) get_post_meta( $post_id, 'rank_math_primary_category', true );
		if ( $primary ) {
			$term = get_term( $primary, 'category' );
			if ( $term instanceof WP_Term ) {
				return $term;
			}
		}
		$cats = get_the_category( $post_id );
		foreach ( $cats as $cat ) {
			if ( 'uncategorized' !== $cat->slug ) {
				return $cat;
			}
		}
		return $cats[0] ?? null;
	}
}

if ( ! function_exists( 'td_a3_post_data' ) ) {
	/**
	 * Card data for a published post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>|null
	 */
	function td_a3_post_data( int $post_id ): ?array {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return null;
		}
		$cat    = td_a3_primary_category( $post_id );
		$author = td_a3_person( (int) $post->post_author );
		return [
			'title'    => get_the_title( $post ),
			'url'      => (string) get_permalink( $post ),
			'category' => $cat ? $cat->name : '',
			'catUrl'   => $cat ? (string) get_category_link( $cat ) : '',
			'minutes'  => td_a3_reading_time( $post_id ),
			'date'     => get_the_date( 'M j, Y', $post ),
			'dateIso'  => get_the_date( 'c', $post ),
			'author'   => $author ? $author['name'] : '',
			'authorP'  => $author,
			'excerpt'  => has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ), 28 ),
			'image'    => (int) get_post_thumbnail_id( $post ),
			'cover'    => '',
			'example'  => false,
		];
	}
}

if ( ! function_exists( 'td_a3_post_cover' ) ) {
	/**
	 * Template-driven cover (IMG-12) for a post without a featured image:
	 * the category set large in type, on one of four neutral surfaces.
	 *
	 * @param array<string,mixed> $p    Post data.
	 * @param int                 $tone 0–3 surface index.
	 */
	function td_a3_post_cover( array $p, int $tone ): string {
		$tones = [ 'ink', 'sand', 'canvas', 'line' ];
		$word  = '' !== (string) $p['cover'] ? (string) $p['cover'] : ( '' !== (string) $p['category'] ? (string) $p['category'] : __( 'Journal', 'techdella' ) );
		return '<span class="td-post-cover td-post-cover--' . esc_attr( $tones[ $tone % 4 ] ) . '" aria-hidden="true"><span class="td-post-cover__word">' . td_accent( $word ) . '</span></span>';
	}
}

if ( ! function_exists( 'td_a3_post_card' ) ) {
	/**
	 * Journal card: cover, meta line (category · read time, or author · date), title.
	 *
	 * @param array<string,mixed> $p    Post data (td_a3_post_data() or a normalized inline item).
	 * @param array<string,mixed> $args level (2|3|4), tone (int), meta (read|date), size (md|sm).
	 */
	function td_a3_post_card( array $p, array $args = [] ): string {
		$level = in_array( (int) ( $args['level'] ?? 3 ), [ 2, 3, 4 ], true ) ? (int) ( $args['level'] ?? 3 ) : 3;
		$tone  = (int) ( $args['tone'] ?? 0 );
		$meta  = [];
		if ( '' !== (string) $p['category'] ) {
			$meta[] = (string) $p['category'];
		}
		if ( 'date' === ( $args['meta'] ?? 'read' ) ) {
			if ( '' !== (string) $p['author'] ) {
				$meta[] = (string) $p['author'];
			}
			if ( '' !== (string) $p['date'] ) {
				$meta[] = (string) $p['date'];
			}
		} elseif ( ! empty( $p['minutes'] ) ) {
			/* translators: %d: minutes */
			$meta[] = sprintf( _n( '%d min read', '%d min read', (int) $p['minutes'], 'techdella' ), (int) $p['minutes'] );
		}
		$img = $p['image'] ? td_image(
			$p['image'],
			[
				'alt'   => '',
				'sizes' => '(max-width: 760px) 100vw, 400px',
			]
		) : '';
		$img = str_starts_with( $img, '<!--' ) ? '' : $img;

		$out  = ( function_exists( 'td_block_css' ) ? td_block_css( 'post-card' ) : '' ) . '<article class="td-post-card td-post-card--' . ( 'sm' === ( $args['size'] ?? 'md' ) ? 'sm' : 'md' ) . '">';
		$out .= '<div class="td-post-card__media">' . ( '' !== $img ? $img : td_a3_post_cover( $p, $tone ) );
		if ( ! empty( $p['example'] ) ) {
			$out .= '<span class="td-label-example">' . esc_html__( 'Example', 'techdella' ) . '</span>';
		}
		$out .= '</div>';
		if ( $meta ) {
			$out .= '<p class="td-post-card__meta">' . esc_html( implode( ' · ', $meta ) ) . '</p>';
		}
		$title = esc_html( (string) $p['title'] );
		$out  .= sprintf(
			'<h%1$d class="td-post-card__title">%2$s</h%1$d>',
			$level,
			'' !== (string) $p['url'] ? '<a class="td-post-card__link" href="' . esc_url( (string) $p['url'] ) . '">' . $title . '</a>' : $title
		);
		return $out . '</article>';
	}
}

if ( ! function_exists( 'td_a3_post_inline' ) ) {
	/**
	 * Normalize an inline example item (block examples, before posts exist).
	 *
	 * @param array<string,mixed> $item Inline item: title, category, cover, minutes, url.
	 * @return array<string,mixed>
	 */
	function td_a3_post_inline( array $item ): array {
		return [
			'title'    => (string) ( $item['title'] ?? '' ),
			'url'      => td_a3_href( (string) ( $item['url'] ?? '' ) ),
			'category' => (string) ( $item['category'] ?? '' ),
			'catUrl'   => '',
			'minutes'  => (int) ( $item['minutes'] ?? 0 ),
			'date'     => '',
			'dateIso'  => '',
			'author'   => (string) ( $item['author'] ?? '' ),
			'authorP'  => null,
			'excerpt'  => (string) ( $item['excerpt'] ?? '' ),
			'image'    => $item['image'] ?? '',
			'cover'    => (string) ( $item['cover'] ?? '' ),
			'example'  => true,
		];
	}
}
