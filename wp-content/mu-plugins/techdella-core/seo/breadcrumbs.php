<?php
/**
 * Breadcrumbs (§8.3 "breadcrumbs everywhere"): td_breadcrumb_items() is the
 * trail (also used for the BreadcrumbList node); td_breadcrumbs() prints
 * accessible markup. Styles: seo/breadcrumbs.css (class-based, tokens only).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hub crumb per post type (live URL bases, §9.3).
 *
 * @return array<string,array{0:string,1:string}> post type => [ label, path ].
 */
function td_seo_breadcrumb_hubs(): array {
	return (array) apply_filters(
		'td_breadcrumb_hubs',
		[
			'service'        => [ __( 'Services', 'techdella' ), '/services/' ],
			'studio_service' => [ __( 'Services', 'techdella' ), '/services/' ],
			'solution'       => [ __( 'Industries', 'techdella' ), '/solution/' ],
			'location'       => [ __( 'Locations', 'techdella' ), '/locations/' ],
			'case_study'     => [ __( 'Case studies', 'techdella' ), '/case-studies/' ],
			'td_tool'        => [ __( 'Tools', 'techdella' ), '/tools/' ],
			'td_resource'    => [ __( 'Resources', 'techdella' ), '/resources/' ],
			'em-glossary'    => [ __( 'Glossary', 'techdella' ), '/glossary/' ],
			'business'       => [ __( 'Business library', 'techdella' ), '/business/' ],
			'studio_work'    => [ __( 'Work', 'techdella' ), '/work/' ],
		]
	);
}

/**
 * The hub crumb for a post type, when the hub exists (a post type archive or
 * a published page at the hub path).
 *
 * @param string $type Post type.
 * @return array{label:string,url:string}|null
 */
function td_seo_breadcrumb_hub( string $type ): ?array {
	$hubs = td_seo_breadcrumb_hubs();
	if ( ! isset( $hubs[ $type ] ) ) {
		return null;
	}
	[ $label, $path ] = $hubs[ $type ];
	$obj              = get_post_type_object( $type );
	if ( $obj && $obj->has_archive ) {
		$link = get_post_type_archive_link( $type );
		if ( $link ) {
			return [
				'label' => $label,
				'url'   => $link,
			];
		}
	}
	$page = get_page_by_path( trim( $path, '/' ) );
	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		return [
			'label' => $label,
			'url'   => (string) get_permalink( $page ),
		];
	}
	return null;
}

/**
 * Crumbs for a term and its parents.
 *
 * @param WP_Term $term Term.
 * @return array<int,array{label:string,url:string}>
 */
function td_seo_breadcrumb_term_trail( WP_Term $term ): array {
	$items = [];
	foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
		$ancestor = get_term( (int) $ancestor_id, $term->taxonomy );
		if ( $ancestor instanceof WP_Term ) {
			$link    = get_term_link( $ancestor );
			$items[] = [
				'label' => td_seo_text( $ancestor->name ),
				'url'   => is_string( $link ) ? $link : '',
			];
		}
	}
	$link    = get_term_link( $term );
	$items[] = [
		'label' => td_seo_text( $term->name ),
		'url'   => is_string( $link ) ? $link : '',
	];
	return $items;
}

/**
 * The primary category of a post (Rank Math's primary term, else the first).
 *
 * @param WP_Post $post Post.
 */
function td_seo_primary_category( WP_Post $post ): ?WP_Term {
	$primary = (int) get_post_meta( $post->ID, 'rank_math_primary_category', true );
	if ( $primary ) {
		$term = get_term( $primary, 'category' );
		if ( $term instanceof WP_Term ) {
			return $term;
		}
	}
	$cats = get_the_category( $post->ID );
	foreach ( $cats as $cat ) {
		if ( 'uncategorized' !== $cat->slug ) {
			return $cat;
		}
	}
	return null;
}

/**
 * The breadcrumb trail for the current request, home first, current last.
 *
 * @return array<int,array{label:string,url:string}>
 */
function td_breadcrumb_items(): array {
	$items = [
		[
			'label' => __( 'Home', 'techdella' ),
			'url'   => home_url( '/' ),
		],
	];
	$here  = td_canonical_url();

	if ( is_front_page() ) {
		return (array) apply_filters( 'td_breadcrumb_items', $items );
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			if ( 'post' === $post->post_type ) {
				$page_for_posts = (int) get_option( 'page_for_posts' );
				if ( $page_for_posts ) {
					$items[] = [
						'label' => td_seo_text( get_the_title( $page_for_posts ) ),
						'url'   => (string) get_permalink( $page_for_posts ),
					];
				}
				$cat = td_seo_primary_category( $post );
				if ( $cat ) {
					$items = array_merge( $items, td_seo_breadcrumb_term_trail( $cat ) );
				}
			} else {
				$hub = td_seo_breadcrumb_hub( $post->post_type );
				if ( $hub ) {
					$items[] = $hub;
				}
			}
			foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor_id ) {
				$items[] = [
					'label' => td_seo_text( get_the_title( (int) $ancestor_id ) ),
					'url'   => (string) get_permalink( (int) $ancestor_id ),
				];
			}
			$items[] = [
				'label' => td_seo_text( get_the_title( $post ) ),
				'url'   => '' !== $here ? $here : (string) get_permalink( $post ),
			];
		}
	} elseif ( is_home() ) {
		$page_for_posts = (int) get_option( 'page_for_posts' );
		$items[]        = [
			'label' => $page_for_posts ? td_seo_text( get_the_title( $page_for_posts ) ) : __( 'Latest', 'techdella' ),
			'url'   => $here,
		];
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$items = array_merge( $items, td_seo_breadcrumb_term_trail( $term ) );
		}
	} elseif ( is_post_type_archive() ) {
		$type    = get_query_var( 'post_type' );
		$type    = is_array( $type ) ? (string) reset( $type ) : (string) $type;
		$hub     = td_seo_breadcrumb_hub( $type );
		$obj     = get_post_type_object( $type );
		$items[] = [
			'label' => $hub ? $hub['label'] : ( $obj ? (string) $obj->labels->name : '' ),
			'url'   => $here,
		];
	} elseif ( is_author() ) {
		$user    = get_queried_object();
		$items[] = [
			'label' => $user instanceof WP_User ? $user->display_name : __( 'Author', 'techdella' ),
			'url'   => $here,
		];
	} elseif ( is_search() ) {
		$items[] = [
			/* translators: %s: search query. */
			'label' => sprintf( __( 'Search results for “%s”', 'techdella' ), get_search_query( false ) ),
			'url'   => '',
		];
	} elseif ( is_404() ) {
		$items[] = [
			'label' => __( 'Page not found', 'techdella' ),
			'url'   => '',
		];
	} elseif ( is_date() ) {
		$items[] = [
			'label' => td_seo_text( get_the_archive_title() ),
			'url'   => $here,
		];
	}

	$paged = (int) get_query_var( 'paged' );
	if ( $paged > 1 && ! is_singular() ) {
		$items[] = [
			/* translators: %d: page number. */
			'label' => sprintf( __( 'Page %d', 'techdella' ), $paged ),
			'url'   => $here,
		];
		// The archive crumb before it links to page 1.
		$prev = count( $items ) - 2;
		if ( $prev > 0 ) {
			$items[ $prev ]['url'] = (string) preg_replace( '#/page/\d+/?$#', '/', $items[ $prev ]['url'] );
		}
	}

	$items = array_values(
		array_filter(
			$items,
			static fn( $item ) => '' !== trim( (string) ( $item['label'] ?? '' ) )
		)
	);
	return (array) apply_filters( 'td_breadcrumb_items', $items );
}

/**
 * Print the breadcrumb trail: <nav aria-label="Breadcrumb"><ol>…</ol></nav>,
 * last item aria-current="page". Nothing on the home page.
 *
 * @param array<string,mixed> $args class (extra class on <nav>).
 */
function td_breadcrumbs( array $args = [] ): void {
	$items = td_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return;
	}
	$class = 'td-breadcrumbs' . ( ! empty( $args['class'] ) ? ' ' . (string) $args['class'] : '' );
	$last  = count( $items ) - 1;
	echo '<nav class="' . esc_attr( $class ) . '" aria-label="' . esc_attr__( 'Breadcrumb', 'techdella' ) . '"><ol class="td-breadcrumbs__list">';
	foreach ( $items as $i => $item ) {
		echo '<li class="td-breadcrumbs__item">';
		if ( $i === $last ) {
			echo '<span class="td-breadcrumbs__current" aria-current="page">' . esc_html( $item['label'] ) . '</span>';
		} elseif ( '' !== (string) $item['url'] ) {
			echo '<a class="td-breadcrumbs__link" href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
		} else {
			echo '<span class="td-breadcrumbs__text">' . esc_html( $item['label'] ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';
}
