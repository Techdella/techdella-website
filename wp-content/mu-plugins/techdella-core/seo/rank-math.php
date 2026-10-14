<?php
/**
 * Rank Math integration (D5): Rank Math prints titles, meta description,
 * robots, canonical and OG tags; we make each agree with our rules and turn
 * its schema output off so there is exactly one JSON-LD graph (§8.2).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Seo\Canonical;

// Rank Math modules we never run: schema ("rich-snippet", our JSON-LD wins)
// and its llms.txt generator (ours is built from the CMS, llms.php).
add_filter(
	'option_rank_math_modules',
	static function ( $modules ) {
		return is_array( $modules ) ? array_values( array_diff( $modules, [ 'rich-snippet', 'llms' ] ) ) : $modules;
	},
	99
);

// Belt and braces: no Rank Math JSON-LD even if the schema module is on.
add_filter( 'rank_math/json_ld', '__return_empty_array', PHP_INT_MAX );

// One robots meta and one canonical: Rank Math prints them, core must not.
add_action(
	'wp_head',
	static function () {
		if ( td_seo_rank_math() ) {
			remove_action( 'wp_head', 'wp_robots', 1 );
			remove_action( 'wp_head', 'rel_canonical' );
		}
	},
	-1
);

// Robots: apply the noindex decision (thank-you, document, search, thin tags,
// authors, staging, td_noindex, _td_noindex); keep team author archives indexable.
add_filter(
	'rank_math/frontend/robots',
	static function ( $robots ) {
		$robots   = is_array( $robots ) ? $robots : [];
		$decision = td_seo_noindex_decision();
		if ( $decision['noindex'] ) {
			$robots['index']  = 'noindex';
			$robots['follow'] = $decision['nofollow'] ? 'nofollow' : 'follow';
		} elseif ( $decision['index'] ) {
			$robots['index'] = 'index';
		}
		return $robots;
	},
	99
);

// Canonical: ours (clean, trailing slash, https in production). An editor's
// explicit Rank Math canonical override is kept, but cleaned.
add_filter(
	'rank_math/frontend/canonical',
	static function ( $canonical ) {
		if ( is_search() || is_404() ) {
			return '';
		}
		if ( is_singular() ) {
			$override = (string) get_post_meta( (int) get_queried_object_id(), 'rank_math_canonical_url', true );
			if ( '' !== $override ) {
				return Canonical::clean( $override, td_is_production() );
			}
		}
		$ours = td_canonical_url();
		if ( '' !== $ours ) {
			return $ours;
		}
		return is_string( $canonical ) && '' !== $canonical ? Canonical::clean( $canonical, td_is_production() ) : $canonical;
	},
	99
);

// Paginated archives keep rel prev/next clean too.
foreach ( [ 'prev', 'next' ] as $td_seo_rel ) {
	add_filter(
		"rank_math/frontend/{$td_seo_rel}_rel_link",
		static function ( $link ) {
			return (string) preg_replace_callback(
				'/href="([^"]+)"/',
				static fn( array $m ) => 'href="' . esc_url( Canonical::clean( html_entity_decode( $m[1] ), td_is_production() ) ) . '"',
				(string) $link
			);
		}
	);
}
unset( $td_seo_rel );

// Meta description (§8.5): never auto-generated from the excerpt/content.
// On singular pages only the description an editor wrote is printed.
add_filter(
	'rank_math/frontend/description',
	static function ( $description ) {
		if ( is_singular() && '' === td_seo_meta_description( (int) get_queried_object_id() ) ) {
			return '';
		}
		return $description;
	},
	99
);
foreach ( [ 'facebook', 'twitter' ] as $td_seo_network ) {
	add_filter(
		"rank_math/opengraph/{$td_seo_network}/og_description",
		static function ( $description ) {
			if ( ! is_singular() ) {
				return $description;
			}
			$post_id = (int) get_queried_object_id();
			$social  = (string) get_post_meta( $post_id, 'rank_math_facebook_description', true );
			if ( '' !== $social ) {
				return $description;
			}
			return td_seo_meta_description( $post_id );
		},
		99
	);
	add_filter(
		"rank_math/opengraph/{$td_seo_network}/twitter_description",
		static function ( $description ) {
			if ( ! is_singular() ) {
				return $description;
			}
			$post_id = (int) get_queried_object_id();
			$social  = (string) get_post_meta( $post_id, 'rank_math_twitter_description', true );
			return '' !== $social ? $description : td_seo_meta_description( $post_id );
		},
		99
	);
}
unset( $td_seo_network );
