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
		$raw   = (string) get_post_field( 'post_content', $post_id );
		$words = str_word_count( wp_strip_all_tags( $raw ) );
		// Article blocks (callout, faq, steps …) keep their text in the block comment's JSON attributes.
		if ( preg_match_all( '/<!-- wp:techdella\/[a-z0-9-]+ (\{.*?\}) \/?-->/s', $raw, $m ) ) {
			foreach ( $m[1] as $json ) {
				$attrs = json_decode( $json, true );
				if ( is_array( $attrs ) ) {
					array_walk_recursive(
						$attrs,
						static function ( $v ) use ( &$words ) {
							if ( is_string( $v ) && str_contains( $v, ' ' ) ) {
								$words += str_word_count( wp_strip_all_tags( $v ) );
							}
						}
					);
				}
			}
		}
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
					'post_status'    => [ 'publish', 'draft', 'pending', 'private' ], // Profiles have no front end of their own.
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
				$person['photo']    = $person['photo'] ? $person['photo'] : (int) get_post_thumbnail_id( $tid );
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
		$tones = [ 'sand', 'canvas', 'ink', 'canvas' ];
		$word  = '' !== (string) $p['cover'] ? (string) $p['cover'] : ( '' !== (string) $p['category'] ? (string) $p['category'] : td_posts_hub_label() );
		return '<span class="td-post-cover td-post-cover--' . esc_attr( $tones[ $tone % 4 ] ) . '" aria-hidden="true"><span class="td-post-cover__arch"></span><span class="td-post-cover__word">' . esc_html( $word ) . '</span></span>';
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
		$level   = in_array( (int) ( $args['level'] ?? 3 ), [ 2, 3, 4 ], true ) ? (int) ( $args['level'] ?? 3 ) : 3;
		$tone    = (int) ( $args['tone'] ?? 0 );
		$size    = 'sm' === ( $args['size'] ?? 'md' ) ? 'sm' : 'md';
		$excerpt = 'md' === $size && ( $args['excerpt'] ?? true ) ? trim( wp_strip_all_tags( (string) $p['excerpt'] ) ) : '';
		$img     = $p['image'] ? td_image(
			$p['image'],
			[
				'alt'   => '',
				'sizes' => '(max-width: 640px) 100vw, (max-width: 1000px) 50vw, 400px',
			]
		) : '';
		$img     = str_starts_with( $img, '<!--' ) ? '' : $img;

		$out  = ( function_exists( 'td_block_css' ) ? td_block_css( 'post-card' ) : '' ) . '<article class="td-post-card td-post-card--' . $size . '">';
		$out .= '<div class="td-post-card__media">' . ( '' !== $img ? $img : td_a3_post_cover( $p, $tone ) );
		if ( ! empty( $p['example'] ) ) {
			$out .= '<span class="td-label-example">' . esc_html__( 'Example', 'techdella' ) . '</span>';
		}
		$out .= '</div><div class="td-post-card__body">';
		$top  = [];
		if ( '' !== (string) $p['category'] ) {
			$top[] = '<span class="td-post-card__cat">' . esc_html( (string) $p['category'] ) . '</span>';
		}
		if ( ! empty( $p['minutes'] ) ) {
			/* translators: %d: minutes */
			$top[] = '<span>' . esc_html( sprintf( _n( '%d min read', '%d min read', (int) $p['minutes'], 'techdella' ), (int) $p['minutes'] ) ) . '</span>';
		}
		if ( $top ) {
			$out .= '<p class="td-post-card__meta">' . implode( '', $top ) . '</p>';
		}
		$title = esc_html( (string) $p['title'] );
		$out  .= sprintf(
			'<h%1$d class="td-post-card__title">%2$s</h%1$d>',
			$level,
			'' !== (string) $p['url'] ? '<a class="td-post-card__link" href="' . esc_url( (string) $p['url'] ) . '">' . $title . '</a>' : $title
		);
		if ( '' !== $excerpt ) {
			$out .= '<p class="td-post-card__excerpt">' . esc_html( wp_trim_words( $excerpt, 26, '…' ) ) . '</p>';
		}
		$who = (string) $p['author'];
		if ( '' !== $who || '' !== (string) $p['date'] ) {
			$initials = '' !== $who ? mb_strtoupper( implode( '', array_map( static fn( $w ) => mb_substr( $w, 0, 1 ), array_slice( preg_split( '/\s+/', $who ), 0, 2 ) ) ) ) : '';
			// The author's team photo; initials until one is uploaded.
			$photo  = is_array( $p['authorP'] ?? null ) ? (int) ( $p['authorP']['photo'] ?? 0 ) : 0;
			$avatar = $photo ? td_image(
				$photo,
				[
					'alt'       => '',
					'sizes'     => '26px',
					'img_class' => 'td-post-card__avatar-img',
					'width'     => 26,
				]
			) : '';
			$avatar = str_starts_with( $avatar, '<!--' ) ? '' : $avatar;
			$avatar = '' !== $avatar ? '<span class="td-post-card__avatar td-post-card__avatar--photo" aria-hidden="true">' . $avatar . '</span>' : '';
			$avatar = '' === $avatar && '' !== $initials ? '<span class="td-post-card__avatar" aria-hidden="true">' . esc_html( $initials ) . '</span>' : $avatar;
			$out   .= '<p class="td-post-card__foot">'
				. $avatar
				. ( '' !== $who ? '<span class="td-post-card__author">' . esc_html( $who ) . '</span>' : '' )
				. ( '' !== (string) $p['date'] ? '<time datetime="' . esc_attr( (string) $p['dateIso'] ) . '">' . esc_html( (string) $p['date'] ) . '</time>' : '' )
				. '</p>';
		}
		return $out . '</div></article>';
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

if ( ! function_exists( 'td_byline_person' ) ) {
	/**
	 * A person from a stored value: a user (ID, array, WP_User) or a td_team / user slug.
	 *
	 * @param mixed $value Field value.
	 */
	function td_byline_person( $value ): ?array {
		if ( is_array( $value ) ) {
			$value = $value['ID'] ?? ( $value[0] ?? 0 );
		}
		if ( $value instanceof WP_User ) {
			return td_a3_person( (int) $value->ID );
		}
		if ( is_numeric( $value ) ) {
			return td_a3_person( (int) $value );
		}
		$slug = sanitize_title( (string) $value );
		if ( '' === $slug ) {
			return null;
		}
		if ( post_type_exists( 'td_team' ) ) {
			$team = get_page_by_path( $slug, OBJECT, 'td_team' );
			if ( $team instanceof WP_Post && function_exists( 'td_team_user_id' ) ) {
				$uid = td_team_user_id( $team, false );
				if ( $uid ) {
					return td_a3_person( $uid );
				}
				return [
					'name'     => get_the_title( $team ),
					'role'     => (string) td_field( 'role', $team->ID ),
					'bio'      => (string) td_field( 'bio', $team->ID ),
					'photo'    => (int) get_post_thumbnail_id( $team ),
					'linkedin' => (string) td_field( 'linkedin', $team->ID ),
					'url'      => '',
				];
			}
		}
		$user = get_user_by( 'slug', $slug );
		return $user ? td_a3_person( (int) $user->ID ) : null;
	}
}

if ( ! function_exists( 'td_byline' ) ) {
	/**
	 * Byline facts for an article (§8.1): author, reviewer, published, last reviewed, reading time.
	 *
	 * @param int $post_id Post ID.
	 * @return array{author:?array,reviewer:?array,published:string,published_iso:string,reviewed:string,reviewed_iso:string,reviewed_label:string,minutes:int}
	 */
	function td_byline( int $post_id ): array {
		$post     = get_post( $post_id );
		$author   = $post ? td_a3_person( (int) $post->post_author ) : null;
		$reviewer = td_byline_person( td_field( 'reviewer', $post_id ) );
		if ( $reviewer && $author && $reviewer['name'] === $author['name'] ) {
			$reviewer = null;
		}
		$fmt      = 'j M Y';
		$reviewed = trim( (string) td_field( 'last_reviewed', $post_id ) );
		$rev_ts   = '' !== $reviewed ? strtotime( $reviewed ) : false;
		$label    = __( 'Last reviewed', 'techdella' );
		if ( ! $rev_ts && $post && strtotime( $post->post_modified_gmt ) - strtotime( $post->post_date_gmt ) > DAY_IN_SECONDS ) {
			$rev_ts = strtotime( $post->post_modified_gmt . ' UTC' );
			$label  = __( 'Updated', 'techdella' );
		}
		return [
			'author'         => $author,
			'reviewer'       => $reviewer,
			'published'      => $post ? (string) get_the_date( $fmt, $post ) : '',
			'published_iso'  => $post ? (string) get_the_date( 'c', $post ) : '',
			'reviewed'       => $rev_ts ? wp_date( $fmt, $rev_ts ) : '',
			'reviewed_iso'   => $rev_ts ? wp_date( 'c', $rev_ts ) : '',
			'reviewed_label' => $label,
			'minutes'        => td_a3_reading_time( $post_id ),
		];
	}
}

if ( ! function_exists( 'td_byline_html' ) ) {
	/**
	 * The byline: avatar, "By" author (linked to their author page) and role, then reviewer,
	 * published and last-reviewed dates and reading time. Empty without a named author.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $time    Show the reading time.
	 */
	function td_byline_html( int $post_id, bool $time = true ): string {
		$b = td_byline( $post_id );
		$a = $b['author'];
		if ( ! $a || '' === (string) $a['name'] ) {
			return '';
		}
		$avatar = ! empty( $a['photo'] ) ? td_image(
			$a['photo'],
			[
				'alt'       => '',
				'sizes'     => '48px',
				'img_class' => 'td-avatar',
				'width'     => 48,
			]
		) : '';
		$avatar = str_starts_with( $avatar, '<!--' ) ? '' : $avatar;
		if ( '' === $avatar ) {
			$initials = implode( '', array_map( static fn( $w ) => mb_substr( $w, 0, 1 ), array_slice( preg_split( '/\s+/', trim( (string) $a['name'] ) ), 0, 2 ) ) );
			$avatar   = '<span class="td-byline__initials" aria-hidden="true">' . esc_html( mb_strtoupper( $initials ) ) . '</span>';
		}
		$name = ! empty( $a['url'] ) ? '<a href="' . esc_url( (string) $a['url'] ) . '" rel="author">' . esc_html( (string) $a['name'] ) . '</a>' : '<strong>' . esc_html( (string) $a['name'] ) . '</strong>';
		$meta = [];
		if ( $b['reviewer'] ) {
			$r      = $b['reviewer'];
			$rname  = ! empty( $r['url'] ) ? '<a href="' . esc_url( (string) $r['url'] ) . '">' . esc_html( (string) $r['name'] ) . '</a>' : esc_html( (string) $r['name'] );
			$meta[] = '<span>' . esc_html__( 'Reviewed by', 'techdella' ) . ' ' . $rname . '</span>';
		}
		if ( '' !== $b['published'] ) {
			$meta[] = '<span>' . esc_html__( 'Published', 'techdella' ) . ' <time datetime="' . esc_attr( $b['published_iso'] ) . '">' . esc_html( $b['published'] ) . '</time></span>';
		}
		if ( '' !== $b['reviewed'] ) {
			$meta[] = '<span>' . esc_html( $b['reviewed_label'] ) . ' <time datetime="' . esc_attr( $b['reviewed_iso'] ) . '">' . esc_html( $b['reviewed'] ) . '</time></span>';
		}
		if ( $time && $b['minutes'] ) {
			/* translators: %d: minutes */
			$meta[] = '<span>' . esc_html( sprintf( _n( '%d min read', '%d min read', $b['minutes'], 'techdella' ), $b['minutes'] ) ) . '</span>';
		}
		return '<div class="td-byline">' . $avatar
			. '<div class="td-byline__text"><p class="td-byline__who"><span class="td-byline__by">' . esc_html__( 'By', 'techdella' ) . '</span> ' . $name
			. ( '' !== (string) $a['role'] ? '<span class="td-byline__role">' . esc_html( (string) $a['role'] ) . '</span>' : '' ) . '</p>'
			. ( $meta ? '<p class="td-byline__meta">' . implode( '<span class="td-byline__dot" aria-hidden="true">·</span>', $meta ) . '</p>' : '' )
			. '</div></div>';
	}
}
