<?php
/**
 * `wp td legacy:upgrade` (owner, 12 Oct 2026: "update our old articles to fit the new style"):
 * brings the blog's existing posts into the new article layout without rewriting anyone's text
 * (§8.1 scaled content: no mass AI rewrites). Per old post (not one imported from content/):
 *
 *  - picks the one service the post leads to (cta_service) from its title, categories and tags;
 *  - adds one inline call to action before a middle H2 (the article template adds the service CTA,
 *    TL;DR and author box at the end already);
 *  - draws a cover where the post has no featured image (covers v2).
 *
 * Idempotent (a version marker per post). Plain tables, images and headings need no change: the
 * article styles handle them. Run with --dry-run first; on production add --production.
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

const TD_LEGACY_UPGRADE_VERSION = 1;

/**
 * The service an old post leads to, from its words (first match wins; most specific first).
 *
 * @param string $text Title, categories and tags, lowercase.
 */
function td_legacy_service_for( string $text ): string {
	$rules = [
		'ai-search-optimization' => '/\b(ai search|chatgpt|perplexity|ai overview|llm|generative engine|aeo|geo)\b/',
		'email-marketing'        => '/\b(email|newsletter|cold email|inbox|deliverability|subject line)\b/',
		'paid-ads'               => '/\b(ads?|advertis\w*|ppc|google ads|meta ads|facebook ads|tiktok|influencer)\b/',
		'marketing-automation'   => '/\b(automat\w*|crm|workflow|zapier|chatbot)\b/',
		'seo'                    => '/\b(seo|search engine|keyword|backlink|rank\w*|local seo|google)\b/',
		'lead-generation'        => '/\b(lead|leads|landing page|conversion|funnel|customers?|sales)\b/',
		'studios'                => '/\b(mvp|saas|app|software|website|no-code|product launch|launch a saas|vibe coding)\b/',
	];
	foreach ( $rules as $service => $re ) {
		if ( preg_match( $re, $text ) ) {
			return $service;
		}
	}
	return 'seo';
}

/**
 * The inline CTA block for a service.
 *
 * @param string $service Service key.
 */
function td_legacy_inline_cta( string $service ): string {
	$studios = 'studios' === $service;
	$attrs   = $studios ? [
		'title'       => __( 'Planning a product or website?', 'techdella' ),
		'text'        => __( 'Get a price range and timeline in two minutes with our MVP cost calculator.', 'techdella' ),
		'buttonLabel' => __( 'Estimate my build', 'techdella' ),
		'buttonUrl'   => 'studios:/mvp-calculator/',
	] : [
		'title'       => __( 'Not sure what to fix first?', 'techdella' ),
		'text'        => __( 'Our Growth Audit reviews your search, ads, website and follow-up, and ranks what to do next.', 'techdella' ),
		'buttonLabel' => __( 'Get a growth audit', 'techdella' ),
		'buttonUrl'   => 'marketing:/growth-audit/',
	];
	return "\n\n" . get_comment_delimited_block_content( 'techdella/inline-cta', $attrs, '' ) . "\n\n";
}

/**
 * Insert the CTA before the H2 nearest the middle of the post (none for short posts).
 *
 * @param string $content Post content.
 * @param string $cta     Block markup.
 */
function td_legacy_insert_cta( string $content, string $cta ): string {
	if ( str_contains( $content, 'wp:techdella/inline-cta' ) || str_contains( $content, 'wp:techdella/post-cta' ) ) {
		return $content;
	}
	if ( ! preg_match_all( '/(?:<!-- wp:heading[^>]*-->\s*)?<h2[\s>]/i', $content, $m, PREG_OFFSET_CAPTURE ) || count( $m[0] ) < 3 ) {
		return $content;
	}
	$mid  = strlen( $content ) / 2;
	$best = null;
	foreach ( $m[0] as [ , $pos ] ) {
		if ( null === $best || abs( $pos - $mid ) < abs( $best - $mid ) ) {
			$best = $pos;
		}
	}
	return substr( $content, 0, (int) $best ) . ltrim( $cta ) . substr( $content, (int) $best );
}

/**
 * Upgrade old blog posts to the new article layout without rewriting their text.
 *
 * ## OPTIONS
 *
 * [--dry-run]
 * : Report only.
 *
 * [--limit=<n>]
 * : Upgrade at most n posts this run.
 *
 * [--production]
 * : Required on production (after a --dry-run).
 *
 * ## EXAMPLES
 *
 *     wp td legacy:upgrade --dry-run
 *     wp td legacy:upgrade --limit=50
 *
 * @param string[]             $args  Unused.
 * @param array<string,string> $assoc Options.
 */
function td_cli_legacy_upgrade( array $args, array $assoc ): void {
	$dry = (bool) WP_CLI\Utils\get_flag_value( $assoc, 'dry-run', false );
	if ( td_is_production() && ! $dry && ! WP_CLI\Utils\get_flag_value( $assoc, 'production', false ) ) {
		WP_CLI::error( 'On production, add --production (after a --dry-run).' );
	}
	$limit = (int) ( $assoc['limit'] ?? 0 );
	$ids   = get_posts(
		[
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				[
					'key'     => '_td_import_source',
					'compare' => 'NOT EXISTS',
				],
				[
					'relation' => 'OR',
					[
						'key'     => '_td_legacy_upgrade',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => '_td_legacy_upgrade',
						'value'   => TD_LEGACY_UPGRADE_VERSION,
						'compare' => '<',
						'type'    => 'NUMERIC',
					],
				],
			],
		]
	);
	$rows  = [];
	$done  = 0;
	foreach ( $ids as $id ) {
		if ( $limit && $done >= $limit ) {
			break;
		}
		$post    = get_post( (int) $id );
		$terms   = wp_get_post_terms( (int) $id, [ 'category', 'post_tag' ], [ 'fields' => 'names' ] );
		$text    = strtolower( $post->post_title . ' ' . implode( ' ', is_array( $terms ) ? $terms : [] ) );
		$service = (string) td_field( 'cta_service', (int) $id );
		$service = '' !== $service ? $service : td_legacy_service_for( $text );
		$content = td_legacy_insert_cta( (string) $post->post_content, td_legacy_inline_cta( $service ) );
		$changes = [];
		if ( '' === (string) td_field( 'cta_service', (int) $id ) ) {
			$changes[] = 'cta: ' . $service;
		}
		if ( $content !== $post->post_content ) {
			$changes[] = 'inline CTA';
		}
		$cover = '';
		if ( ! has_post_thumbnail( (int) $id ) ) {
			$changes[] = 'cover';
		}
		if ( ! $dry ) {
			if ( '' === (string) td_field( 'cta_service', (int) $id ) ) {
				update_post_meta( (int) $id, 'cta_service', $service );
			}
			if ( $content !== $post->post_content ) {
				// Keep the modified date: layout changes are not a content update.
				add_filter( 'wp_insert_post_data', 'td_legacy_keep_modified', 10, 2 );
				wp_update_post(
					wp_slash(
						[
							'ID'           => (int) $id,
							'post_content' => $content,
						]
					)
				);
				remove_filter( 'wp_insert_post_data', 'td_legacy_keep_modified', 10 );
			}
			if ( ! has_post_thumbnail( (int) $id ) && function_exists( 'td_cover_generate' ) ) {
				$cover = td_cover_generate( (int) $id, false, td_cover_photos() );
			}
			update_post_meta( (int) $id, '_td_legacy_upgrade', TD_LEGACY_UPGRADE_VERSION );
		}
		$rows[] = [
			'id'      => $id,
			'post'    => wp_trim_words( $post->post_title, 8, '…' ),
			'changes' => $changes ? implode( ', ', $changes ) : 'none',
			'result'  => $dry ? 'would upgrade' : ( 'error' === $cover ? 'upgraded (cover failed)' : 'upgraded' ),
		];
		++$done;
	}
	WP_CLI\Utils\format_items( 'table', $rows, [ 'id', 'post', 'changes', 'result' ] );
	WP_CLI::success( sprintf( '%d old post(s) %s.', count( $rows ), $dry ? 'to upgrade' : 'upgraded' ) );
}

/**
 * Keep post_modified unchanged for layout-only updates.
 *
 * @param array<string,mixed> $data    Post data.
 * @param array<string,mixed> $postarr Raw input.
 * @return array<string,mixed>
 */
function td_legacy_keep_modified( array $data, array $postarr ): array {
	$old = ! empty( $postarr['ID'] ) ? get_post( (int) $postarr['ID'] ) : null;
	if ( $old ) {
		$data['post_modified']     = $old->post_modified;
		$data['post_modified_gmt'] = $old->post_modified_gmt;
	}
	return $data;
}
