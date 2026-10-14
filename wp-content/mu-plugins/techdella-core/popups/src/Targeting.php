<?php
/**
 * Popup data model + targeting rules (§13.1). Pure PHP, unit-tested.
 *
 * A popup (stored in the td_popups option) looks like:
 *   id, name, enabled, type (exit|slidein|topbar|inline), trigger (exit|scroll|time|pages),
 *   trigger_value (scroll %, seconds or page count), site, post_types[], categories[],
 *   exclude_categories[], include[], exclude[] (path globs: /services/*),
 *   offer_type (resource|newsletter), resource (td_resource slug), form_key,
 *   frequency_days, variants[] (≤ 2: title with *accent*, text, button).
 */

namespace TechDella\Core\Popups;

defined( 'ABSPATH' ) || exit;

/**
 * Normalise and match popups.
 */
final class Targeting {

	public const TYPES     = [ 'exit', 'slidein', 'topbar', 'inline' ];
	public const TRIGGERS  = [ 'exit', 'scroll', 'time', 'pages' ];
	public const FORM_KEYS = [ 'popup_exit', 'popup_scroll', 'newsletter', 'lead_magnet' ];
	public const SITES     = [ 'marketing', 'journal', 'studios' ];

	/**
	 * Pages that never show popups (§13.1), as path globs.
	 */
	public const BLOCKED_PATHS = [ '/book-a-discovery-call/*', '/growth-audit/*', '/start/*', '/thank-you/*', '/thank-you-*' ];

	/**
	 * Normalise raw (admin form / stored) data into a valid popup.
	 *
	 * @param array<string,mixed> $raw Raw data.
	 * @return array<string,mixed>
	 */
	public static function normalize( array $raw ): array {
		$list = static function ( $value ): array {
			if ( is_string( $value ) ) {
				$value = preg_split( '/[\r\n,]+/', $value );
			}
			$value = is_array( $value ) ? $value : [];
			return array_values( array_unique( array_filter( array_map( static fn( $v ) => trim( (string) $v ), $value ), static fn( $v ) => '' !== (string) $v ) ) );
		};
		$slug = static fn( $v ) => strtolower( (string) preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $v ) );

		$type    = in_array( $raw['type'] ?? '', self::TYPES, true ) ? (string) $raw['type'] : 'slidein';
		$trigger = in_array( $raw['trigger'] ?? '', self::TRIGGERS, true ) ? (string) $raw['trigger'] : 'scroll';
		if ( 'exit' === $type ) {
			$trigger = 'exit'; // Exit-intent modals are triggered by exit intent only.
		} elseif ( 'exit' === $trigger ) {
			$trigger = 'scroll';
		}
		$value = (int) ( $raw['trigger_value'] ?? 0 );
		$value = match ( $trigger ) {
			'scroll' => max( 10, min( 100, $value > 0 ? $value : 60 ) ),
			'time'   => max( 10, min( 600, $value > 0 ? $value : 30 ) ),
			'pages'  => max( 1, min( 20, $value > 0 ? $value : 2 ) ),
			default  => 0,
		};

		$variants = [];
		foreach ( array_slice( (array) ( $raw['variants'] ?? [] ), 0, 2 ) as $v ) {
			$v     = (array) $v;
			$title = trim( (string) ( $v['title'] ?? '' ) );
			if ( '' === $title ) {
				continue;
			}
			$variants[] = [
				'title'  => $title,
				'text'   => trim( (string) ( $v['text'] ?? '' ) ),
				'button' => trim( (string) ( $v['button'] ?? '' ) ),
			];
		}

		$form_key = in_array( $raw['form_key'] ?? '', self::FORM_KEYS, true ) ? (string) $raw['form_key'] : 'newsletter';
		$offer    = 'resource' === ( $raw['offer_type'] ?? '' ) ? 'resource' : 'newsletter';
		$id       = $slug( $raw['id'] ?? '' );

		return [
			'id'                 => '' !== $id ? $id : 'popup-' . substr( md5( serialize( $raw ) ), 0, 8 ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- hashing only.

			'name'               => trim( (string) ( $raw['name'] ?? '' ) ),
			'enabled'            => ! empty( $raw['enabled'] ),
			'type'               => $type,
			'trigger'            => $trigger,
			'trigger_value'      => $value,
			'site'               => in_array( $raw['site'] ?? '', self::SITES, true ) ? (string) $raw['site'] : 'marketing',
			'post_types'         => array_map( $slug, $list( $raw['post_types'] ?? [] ) ),
			'categories'         => array_map( $slug, $list( $raw['categories'] ?? [] ) ),
			'exclude_categories' => array_map( $slug, $list( $raw['exclude_categories'] ?? [] ) ),
			'include'            => array_map( [ self::class, 'clean_glob' ], $list( $raw['include'] ?? [] ) ),
			'exclude'            => array_map( [ self::class, 'clean_glob' ], $list( $raw['exclude'] ?? [] ) ),
			'offer_type'         => $offer,
			'resource'           => 'resource' === $offer ? $slug( $raw['resource'] ?? '' ) : '',
			'form_key'           => $form_key,
			'frequency_days'     => max( 1, min( 365, (int) ( $raw['frequency_days'] ?? 0 ) > 0 ? (int) $raw['frequency_days'] : 7 ) ),
			'variants'           => $variants,
		];
	}

	/**
	 * A path glob: leading slash, only safe characters and "*".
	 *
	 * @param string $glob Glob.
	 */
	public static function clean_glob( string $glob ): string {
		$glob = trim( $glob );
		if ( preg_match( '#^https?://[^/]+(/.*)?$#i', $glob, $m ) ) {
			$glob = $m[1] ?? '/';
		}
		$glob = (string) preg_replace( '#[^A-Za-z0-9_\-/.*]#', '', $glob );
		return '/' . ltrim( $glob, '/' );
	}

	/**
	 * Whether a path matches a glob ("*" = anything, incl. "/"; trailing
	 * slash and case ignored). Never a substring match.
	 *
	 * @param string $glob Glob.
	 * @param string $path Request path.
	 */
	public static function glob_match( string $glob, string $path ): bool {
		$norm  = static fn( string $p ): string => '/' === $p ? '/' : rtrim( strtolower( $p ), '/' );
		$glob  = $norm( self::clean_glob( $glob ) );
		$path  = $norm( '/' . ltrim( (string) preg_replace( '/[?#].*$/', '', $path ), '/' ) );
		$regex = '#^' . str_replace( '\*', '.*', preg_quote( $glob, '#' ) ) . '$#';
		// "/x/*" also matches "/x" itself.
		if ( str_ends_with( $glob, '/.*' ) || str_ends_with( $glob, '/*' ) ) {
			$regex = '#^' . str_replace( '\*', '.*', preg_quote( substr( $glob, 0, -2 ), '#' ) ) . '(/.*)?$#';
		}
		return (bool) preg_match( $regex, $path );
	}

	/**
	 * Whether a page is one that never shows popups.
	 *
	 * @param string $path      Request path.
	 * @param string $post_type Queried post type ('' if none).
	 */
	public static function is_blocked_page( string $path, string $post_type = '' ): bool {
		if ( 'thank-you' === $post_type ) {
			return true;
		}
		foreach ( self::BLOCKED_PATHS as $glob ) {
			if ( self::glob_match( $glob, $path ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether a popup targets this request.
	 *
	 * @param array<string,mixed> $popup Normalised popup.
	 * @param array<string,mixed> $ctx   site, path, post_type, categories[].
	 */
	public static function matches( array $popup, array $ctx ): bool {
		$path       = (string) ( $ctx['path'] ?? '/' );
		$post_type  = (string) ( $ctx['post_type'] ?? '' );
		$categories = array_map( 'strval', (array) ( $ctx['categories'] ?? [] ) );

		if ( empty( $popup['enabled'] ) || empty( $popup['variants'] ) ) {
			return false;
		}
		if ( ( $popup['site'] ?? '' ) !== ( $ctx['site'] ?? '' ) ) {
			return false;
		}
		if ( self::is_blocked_page( $path, $post_type ) ) {
			return false;
		}
		if ( ! empty( $popup['post_types'] ) && ! in_array( $post_type, (array) $popup['post_types'], true ) ) {
			return false;
		}
		if ( ! empty( $popup['categories'] ) && ! array_intersect( (array) $popup['categories'], $categories ) ) {
			return false;
		}
		if ( ! empty( $popup['exclude_categories'] ) && array_intersect( (array) $popup['exclude_categories'], $categories ) ) {
			return false;
		}
		if ( ! empty( $popup['include'] ) ) {
			$hit = false;
			foreach ( (array) $popup['include'] as $glob ) {
				$hit = $hit || self::glob_match( (string) $glob, $path );
			}
			if ( ! $hit ) {
				return false;
			}
		}
		foreach ( (array) ( $popup['exclude'] ?? [] ) as $glob ) {
			if ( self::glob_match( (string) $glob, $path ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Matching popups: at most one per type (first wins), inline cards excluded
	 * unless asked for.
	 *
	 * @param array<int,array<string,mixed>> $popups Normalised popups.
	 * @param array<string,mixed>            $ctx    Request context.
	 * @return array<int,array<string,mixed>>
	 */
	public static function for_context( array $popups, array $ctx ): array {
		$out   = [];
		$types = [];
		foreach ( $popups as $popup ) {
			if ( isset( $types[ $popup['type'] ] ) || ! self::matches( $popup, $ctx ) ) {
				continue;
			}
			$types[ $popup['type'] ] = true;
			$out[]                   = $popup;
		}
		return $out;
	}

	/**
	 * Default popups P1–P4 (§13.1) for one site.
	 *
	 * @param string $site Site key.
	 * @return array<int,array<string,mixed>>
	 */
	public static function defaults( string $site ): array {
		$all = [
			[
				'id'             => 'p1',
				'name'           => 'P1 · Journal AI Search + SEO posts → AI Search Readiness Checklist',
				'enabled'        => true,
				'type'           => 'slidein',
				'trigger'        => 'scroll',
				'trigger_value'  => 60,
				'site'           => 'journal',
				'post_types'     => [ 'post' ],
				'categories'     => [ 'ai-search', 'seo' ],
				'offer_type'     => 'resource',
				'resource'       => 'ai-search-readiness-checklist',
				'form_key'       => 'popup_scroll',
				'frequency_days' => 7,
				'variants'       => [
					[
						'title'  => 'Is your site ready for *AI search*?',
						'text'   => 'Get the AI Search Readiness Checklist: the checks we run before any AI search project.',
						'button' => 'Send me the checklist',
					],
				],
			],
			[
				'id'                 => 'p2',
				'name'               => 'P2 · Journal, all other posts → Newsletter',
				'enabled'            => true,
				'type'               => 'slidein',
				'trigger'            => 'scroll',
				'trigger_value'      => 60,
				'site'               => 'journal',
				'post_types'         => [ 'post' ],
				'exclude_categories' => [ 'ai-search', 'seo' ],
				'offer_type'         => 'newsletter',
				'form_key'           => 'newsletter',
				'frequency_days'     => 7,
				'variants'           => [
					[
						'title'  => 'Get the Journal by *email*',
						'text'   => 'New guides on search, paid, email and automation. Unsubscribe any time.',
						'button' => 'Subscribe',
					],
				],
			],
			[
				'id'             => 'p3',
				'name'           => 'P3 · Marketing service pages → B2B Growth Audit Worksheet',
				'enabled'        => true,
				'type'           => 'exit',
				'trigger'        => 'exit',
				'site'           => 'marketing',
				'post_types'     => [ 'service' ],
				'offer_type'     => 'resource',
				'resource'       => 'b2b-growth-audit-worksheet',
				'form_key'       => 'popup_exit',
				'frequency_days' => 7,
				'variants'       => [
					[
						'title'  => 'Before you go: audit your *growth*',
						'text'   => 'The B2B Growth Audit Worksheet walks you through the checks we start every engagement with.',
						'button' => 'Email me the worksheet',
					],
				],
			],
			[
				'id'             => 'p4',
				'name'           => 'P4 · Studios → MVP Scoping Guide',
				'enabled'        => true,
				'type'           => 'topbar',
				'trigger'        => 'time',
				'trigger_value'  => 10,
				'site'           => 'studios',
				'offer_type'     => 'resource',
				'resource'       => 'mvp-scoping-guide',
				'form_key'       => 'lead_magnet',
				'frequency_days' => 7,
				'variants'       => [
					[
						'title'  => 'Planning an MVP? Get the *MVP Scoping Guide*.',
						'text'   => '',
						'button' => 'Get the guide',
					],
				],
			],
		];
		$out = [];
		foreach ( $all as $raw ) {
			if ( $raw['site'] === $site ) {
				$out[] = self::normalize( $raw );
			}
		}
		return $out;
	}
}
