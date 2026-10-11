<?php
/**
 * The noindex decision table (§8.3, §5.3). Pure PHP: the caller collects the
 * request context from WordPress and this class decides.
 */

namespace TechDella\Core\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Noindex rules.
 */
final class Noindex {

	/** Tag archives with fewer posts than this are noindex. */
	public const MIN_TAG_POSTS = 5;

	/** Team authors need at least this many posts for an indexable archive. */
	public const MIN_AUTHOR_POSTS = 3;

	/**
	 * Decide robots for a request.
	 *
	 * Context keys (all optional):
	 *  env            local|staging|production
	 *  is_search, is_tag, is_author, is_attachment, is_404, is_singular  bool
	 *  tag_count      int   posts in the tag
	 *  author_is_team bool  the author has a td_team profile
	 *  author_posts   int   published posts by the author
	 *  post_type      string
	 *  noindex_types  string[]  post types forced noindex (thank-you, document)
	 *  post_noindex   bool  post meta _td_noindex = 1
	 *  has_parent     bool  attachment has a published parent (it is redirected instead)
	 *  forced         bool  result of the td_noindex filter
	 *
	 * @param array<string,mixed> $ctx Request context.
	 * @return array{noindex:bool,nofollow:bool,index:bool,reason:string} `index` = force indexable
	 *         (overrides a plugin default, e.g. team author archives).
	 */
	public static function decide( array $ctx ): array {
		$result = static fn( bool $noindex, string $reason, bool $nofollow = false, bool $index = false ): array => [
			'noindex'  => $noindex,
			'nofollow' => $nofollow,
			'index'    => $index,
			'reason'   => $reason,
		];

		if ( 'staging' === ( $ctx['env'] ?? '' ) ) {
			return $result( true, 'staging', true );
		}
		if ( ! empty( $ctx['forced'] ) ) {
			return $result( true, 'filter' );
		}
		if ( ! empty( $ctx['is_search'] ) ) {
			return $result( true, 'search' );
		}
		if ( ! empty( $ctx['is_404'] ) ) {
			return $result( true, '404' );
		}
		if ( ! empty( $ctx['is_attachment'] ) ) {
			return $result( true, 'attachment' );
		}
		if ( ! empty( $ctx['is_singular'] ) ) {
			$types = array_map( 'strval', (array) ( $ctx['noindex_types'] ?? [] ) );
			if ( in_array( (string) ( $ctx['post_type'] ?? '' ), $types, true ) ) {
				return $result( true, 'post_type' );
			}
			if ( ! empty( $ctx['post_noindex'] ) ) {
				return $result( true, 'post_meta' );
			}
		}
		if ( ! empty( $ctx['is_tag'] ) && (int) ( $ctx['tag_count'] ?? 0 ) < self::MIN_TAG_POSTS ) {
			return $result( true, 'thin_tag' );
		}
		if ( ! empty( $ctx['is_author'] ) ) {
			if ( ! empty( $ctx['author_is_team'] ) && (int) ( $ctx['author_posts'] ?? 0 ) >= self::MIN_AUTHOR_POSTS ) {
				return $result( false, 'team_author', false, true );
			}
			return $result( true, 'author' );
		}
		return $result( false, '' );
	}

	/**
	 * The robots directive string for a decision.
	 *
	 * @param array{noindex:bool,nofollow:bool} $decision From decide().
	 */
	public static function directive( array $decision ): string {
		if ( empty( $decision['noindex'] ) ) {
			return '';
		}
		return 'noindex, ' . ( empty( $decision['nofollow'] ) ? 'follow' : 'nofollow' );
	}
}
