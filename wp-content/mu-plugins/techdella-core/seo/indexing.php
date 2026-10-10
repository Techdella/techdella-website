<?php
/**
 * Indexing controls (§8.3): canonical URLs, the noindex decision (meta
 * robots + X-Robots-Tag), attachment redirects, search exclusions and the
 * "production must not be noindex" guard.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Seo\Canonical;
use TechDella\Core\Seo\Noindex;

/**
 * The noindex decision for the current request (see Noindex::decide()).
 *
 * @return array{noindex:bool,nofollow:bool,index:bool,reason:string}
 */
function td_seo_noindex_decision(): array {
	global $wp_query;
	if ( ! $wp_query instanceof WP_Query || ! did_action( 'parse_query' ) ) {
		// Before the main query (wp_die pages, early hooks): only staging decides.
		return Noindex::decide( [ 'env' => td_environment() ] );
	}
	$ctx = [
		'env'           => td_environment(),
		'is_search'     => is_search(),
		'is_404'        => is_404(),
		'is_attachment' => is_attachment(),
		'is_singular'   => is_singular(),
		'is_tag'        => is_tag(),
		'is_author'     => is_author(),
		'noindex_types' => td_seo_noindex_types(),
	];
	if ( $ctx['is_singular'] ) {
		$post                = get_queried_object();
		$ctx['post_type']    = $post instanceof WP_Post ? $post->post_type : '';
		$ctx['post_noindex'] = $post instanceof WP_Post && '1' === (string) get_post_meta( $post->ID, '_td_noindex', true );
	}
	if ( $ctx['is_tag'] ) {
		$term             = get_queried_object();
		$ctx['tag_count'] = $term instanceof WP_Term ? (int) $term->count : 0;
	}
	if ( $ctx['is_author'] ) {
		$user_id               = (int) get_queried_object_id();
		$ctx['author_is_team'] = null !== td_seo_team_post_for_user( $user_id );
		$ctx['author_posts']   = (int) count_user_posts( $user_id, 'post', true );
	}
	$ctx['forced'] = (bool) apply_filters( 'td_noindex', false );
	return Noindex::decide( $ctx );
}

/**
 * Whether the current request is noindex.
 */
function td_is_noindex(): bool {
	return td_seo_noindex_decision()['noindex'];
}

/**
 * The canonical URL for the current request: self-referencing, absolute,
 * https in production, trailing slash, no query string; paginated archives
 * are self-canonical. '' for search results and 404s.
 */
function td_canonical_url(): string {
	global $wp_rewrite;
	if ( is_search() || is_404() ) {
		return '';
	}
	$url   = '';
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	if ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_home() ) {
		$page_id = (int) get_option( 'page_for_posts' );
		$url     = $page_id ? (string) get_permalink( $page_id ) : home_url( '/' );
	} elseif ( is_singular() ) {
		$url   = (string) get_permalink( get_queried_object_id() );
		$page  = (int) get_query_var( 'page' );
		$paged = 1;
		if ( $page > 1 ) {
			$url = trailingslashit( $url ) . $page . '/';
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		$link = $term instanceof WP_Term ? get_term_link( $term ) : '';
		$url  = is_string( $link ) ? $link : '';
	} elseif ( is_post_type_archive() ) {
		$type = get_query_var( 'post_type' );
		$type = is_array( $type ) ? (string) reset( $type ) : (string) $type;
		$link = get_post_type_archive_link( $type );
		$url  = $link ? $link : '';
	} elseif ( is_author() ) {
		$url = get_author_posts_url( (int) get_queried_object_id() );
	} elseif ( is_date() ) {
		if ( is_day() ) {
			$url = get_day_link( (int) get_query_var( 'year' ), (int) get_query_var( 'monthnum' ), (int) get_query_var( 'day' ) );
		} elseif ( is_month() ) {
			$url = get_month_link( (int) get_query_var( 'year' ), (int) get_query_var( 'monthnum' ) );
		} else {
			$url = get_year_link( (int) get_query_var( 'year' ) );
		}
	}
	if ( '' === $url ) {
		return '';
	}
	if ( $paged > 1 ) {
		$base = $wp_rewrite instanceof WP_Rewrite ? $wp_rewrite->pagination_base : 'page';
		$url  = Canonical::paged( $url, $paged, $base );
	}
	$keep = get_option( 'permalink_structure' ) ? [] : [ 'p', 'page_id', 'cat', 'tag', 'author', 'post_type', 'paged', 'page', 'm' ];
	return Canonical::clean( $url, td_is_production(), $keep );
}

// X-Robots-Tag for noindex pages. Sent at template_redirect and again just
// before the header is printed (templates may add td_noindex filters).
$td_seo_robots_header = static function () {
	static $sent = false;
	if ( $sent || headers_sent() || is_admin() || 'staging' === td_environment() || is_feed() || is_robots() ) {
		return;
	}
	$decision = td_seo_noindex_decision();
	if ( $decision['noindex'] ) {
		header( 'X-Robots-Tag: ' . Noindex::directive( $decision ), true );
		$sent = true;
	}
};
add_action( 'template_redirect', $td_seo_robots_header, 99 );
add_action( 'get_header', $td_seo_robots_header, 0 );
unset( $td_seo_robots_header );

// Meta robots when Rank Math is not running (core wp_robots prints one tag).
add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		if ( td_seo_rank_math() ) {
			return $robots;
		}
		$decision = td_seo_noindex_decision();
		if ( $decision['noindex'] ) {
			unset( $robots['index'], $robots['max-image-preview'] );
			$robots['noindex'] = true;
			if ( $decision['nofollow'] ) {
				unset( $robots['follow'] );
				$robots['nofollow'] = true;
			} else {
				unset( $robots['nofollow'] );
				$robots['follow'] = true;
			}
		}
		return $robots;
	},
	999
);

// Canonical when Rank Math is not running (Rank Math prints its own; see rank-math.php).
add_action(
	'wp_head',
	static function () {
		if ( td_seo_rank_math() ) {
			return;
		}
		remove_action( 'wp_head', 'rel_canonical' );
		$url = td_canonical_url();
		if ( '' !== $url ) {
			echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
		}
	},
	0
);

// Attachment pages: 301 to the parent; noindex (above) when there is none.
add_action(
	'template_redirect',
	static function () {
		if ( ! is_attachment() ) {
			return;
		}
		$parent = wp_get_post_parent_id( (int) get_queried_object_id() );
		if ( $parent && 'publish' === get_post_status( $parent ) ) {
			wp_safe_redirect( (string) get_permalink( $parent ), 301, 'TechDella' );
			exit;
		}
	},
	1
);

// document / thank-you never appear in on-site search (§7).
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}
		$types = $query->get( 'post_type' );
		if ( empty( $types ) || 'any' === $types ) {
			$types = get_post_types( [ 'exclude_from_search' => false ] );
		}
		$query->set( 'post_type', array_values( array_diff( (array) $types, td_seo_noindex_types() ) ) );
	}
);

// Production must never be noindex by accident (§16).
add_action(
	'admin_notices',
	static function () {
		if ( ! td_is_production() || '1' === (string) get_option( 'blog_public' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
			esc_html__( 'This production site is hidden from search engines.', 'techdella' ),
			esc_html__( '"Discourage search engines" is on (blog_public = 0). Turn it off now.', 'techdella' ),
			esc_url( admin_url( 'options-reading.php' ) ),
			esc_html__( 'Reading settings', 'techdella' )
		);
	}
);
