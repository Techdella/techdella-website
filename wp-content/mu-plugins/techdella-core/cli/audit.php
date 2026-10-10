<?php
/**
 * `wp td audit …` helpers (§8.3, §9.4, §10.1, §16) and the HTTP helper the
 * redirect checker shares. Loaded only under WP-CLI.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Cli\Decay;

/**
 * GET a URL without following redirects. On the local Docker stack the other
 * sites' localhost ports are not reachable from inside a container, so
 * http://localhost:808x is sent to the service name (wp-journal:8082…) with
 * the original Host header.
 *
 * @param string $url URL.
 * @return array{status:int,location:string,body:string,error:string}
 */
function td_cli_http_get( string $url ): array {
	$args = [
		'redirection' => 0,
		'timeout'     => 15,
		'user-agent'  => 'TechDella-Audit/1.0 (+wp td)',
		'headers'     => [],
		'sslverify'   => td_is_production(),
	];
	$req  = $url;
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	$port = (int) wp_parse_url( $url, PHP_URL_PORT );
	if ( 'local' === td_environment() && in_array( $host, [ 'localhost', '127.0.0.1' ], true ) && $port ) {
		$services = [
			8081 => 'wp-marketing',
			8082 => 'wp-journal',
			8083 => 'wp-studios',
		];
		$own      = (int) wp_parse_url( home_url( '/' ), PHP_URL_PORT );
		if ( isset( $services[ $port ] ) && $port !== $own && is_file( '/.dockerenv' ) ) {
			$req                     = (string) preg_replace( '#^(https?://)[^/]+#i', '$1' . $services[ $port ] . ':' . $port, $url );
			$args['headers']['Host'] = $host . ':' . $port;
		}
	}
	$req = (string) apply_filters( 'td_cli_http_url', $req, $url );
	$res = wp_remote_get( $req, $args );
	if ( is_wp_error( $res ) ) {
		return [
			'status'   => 0,
			'location' => '',
			'body'     => '',
			'error'    => $res->get_error_message(),
		];
	}
	$location = (string) wp_remote_retrieve_header( $res, 'location' );
	if ( '' !== $location && ! preg_match( '#^https?://#i', $location ) ) {
		$location = (string) preg_replace( '#^(https?://[^/]+).*$#', '$1', $url ) . '/' . ltrim( $location, '/' );
	}
	return [
		'status'   => (int) wp_remote_retrieve_response_code( $res ),
		'location' => $location,
		'body'     => (string) wp_remote_retrieve_body( $res ),
		'error'    => '',
	];
}

/**
 * Published, indexable posts of public types (candidates for orphan checks).
 *
 * @return WP_Post[]
 */
function td_audit_indexable_posts(): array {
	$types = array_diff( get_post_types( [ 'public' => true ] ), [ 'attachment', 'thank-you', 'document', 'td_team', 'td_testimonial', 'platform' ] );
	$posts = get_posts(
		[
			'post_type'      => array_values( $types ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		]
	);
	return array_values( array_filter( $posts, static fn( $p ) => ! get_post_meta( $p->ID, '_td_noindex', true ) && 'styleguide' !== $p->post_name ) );
}

/**
 * Normalised path of a URL on this site, or '' for other hosts.
 *
 * @param string $url URL or path.
 */
function td_audit_local_path( string $url ): string {
	$url  = html_entity_decode( trim( $url ) );
	$home = wp_parse_url( home_url( '/' ) );
	if ( '' === $url || str_starts_with( $url, '#' ) || preg_match( '#^(mailto|tel|javascript):#i', $url ) ) {
		return '';
	}
	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) ) {
		return '';
	}
	if ( isset( $parts['host'] ) && ( strtolower( $parts['host'] ) !== strtolower( (string) ( $home['host'] ?? '' ) ) || (int) ( $parts['port'] ?? 0 ) !== (int) ( $home['port'] ?? 0 ) ) ) {
		return '';
	}
	$path = '/' . trim( (string) ( $parts['path'] ?? '/' ), '/' );
	return '/' === $path ? '/' : $path . '/';
}

/**
 * Orphans: indexable published posts with no inbound link from other
 * published content or menus.
 *
 * @return array<int,array{id:int,type:string,url:string,title:string}>
 */
function td_audit_orphans(): array {
	$posts   = td_audit_indexable_posts();
	$inbound = []; // path => [ source post IDs ].
	foreach ( $posts as $post ) {
		if ( preg_match_all( '/href=["\']([^"\']+)["\']/i', (string) $post->post_content, $m ) ) {
			foreach ( $m[1] as $href ) {
				$path = td_audit_local_path( $href );
				if ( '' !== $path ) {
					$inbound[ $path ][ $post->ID ] = true;
				}
			}
		}
	}
	$in_menu = [];
	foreach ( wp_get_nav_menus() as $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			if ( ! empty( $item->object_id ) && 'custom' !== $item->type ) {
				$in_menu[ (int) $item->object_id ] = true;
			}
			$path = td_audit_local_path( (string) $item->url );
			if ( '' !== $path ) {
				$inbound[ $path ]['menu'] = true;
			}
		}
	}
	$front   = (int) get_option( 'page_on_front' );
	$orphans = [];
	foreach ( $posts as $post ) {
		if ( $post->ID === $front || isset( $in_menu[ $post->ID ] ) ) {
			continue;
		}
		$url     = (string) get_permalink( $post );
		$path    = td_audit_local_path( $url );
		$sources = $inbound[ $path ] ?? [];
		unset( $sources[ $post->ID ] );
		if ( ! $sources ) {
			$orphans[] = [
				'id'    => $post->ID,
				'type'  => $post->post_type,
				'url'   => $url,
				'title' => $post->post_title,
			];
		}
	}
	return $orphans;
}

/**
 * The placeholder pattern (§16).
 */
function td_audit_placeholder_regex(): string {
	return '/\[(?:CONFIRM|PRICE|YOUR PRICE|MINIMUM SPEND)\b[^\]]*\]?|lorem ipsum/i';
}

/**
 * Placeholders in published content and meta.
 *
 * @return array<int,array{where:string,url:string,match:string,count:int}>
 */
function td_audit_placeholders_db(): array {
	$rows  = [];
	$types = array_diff( get_post_types( [ 'public' => true ] ), [ 'attachment' ] );
	$posts = get_posts(
		[
			'post_type'      => array_values( $types ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		]
	);
	foreach ( $posts as $post ) {
		if ( 'styleguide' === $post->post_name ) {
			continue; // The styleguide shows the [CONFIRM] marker on purpose (noindex).
		}
		$texts = [
			'title'   => $post->post_title,
			'content' => $post->post_content,
			'excerpt' => $post->post_excerpt,
		];
		foreach ( (array) get_post_meta( $post->ID ) as $key => $values ) {
			if ( str_starts_with( (string) $key, '_' ) ) {
				continue;
			}
			$texts[ 'meta:' . $key ] = implode( ' ', array_map( 'strval', (array) $values ) );
		}
		foreach ( $texts as $where => $text ) {
			$count = preg_match_all( td_audit_placeholder_regex(), (string) $text, $m );
			if ( $count ) {
				$rows[] = [
					'where' => $post->post_type . '#' . $post->ID . ' ' . $where,
					'url'   => (string) get_permalink( $post ),
					'match' => implode( ', ', array_unique( array_slice( $m[0], 0, 3 ) ) ),
					'count' => (int) $count,
				];
			}
		}
	}
	return $rows;
}

/**
 * Placeholders in rendered pages.
 *
 * @param string[] $urls URLs.
 * @return array<int,array{where:string,url:string,match:string,count:int}>
 */
function td_audit_placeholders_rendered( array $urls ): array {
	$rows = [];
	foreach ( $urls as $url ) {
		$res  = td_cli_http_get( $url );
		$text = (string) preg_replace( '#<(script|style)\b.*?</\1>#is', ' ', $res['body'] );
		$text = html_entity_decode( wp_strip_all_tags( $text ) );
		if ( 200 !== $res['status'] ) {
			$rows[] = [
				'where' => 'rendered (HTTP ' . $res['status'] . ')',
				'url'   => $url,
				'match' => $res['error'],
				'count' => 0,
			];
			continue;
		}
		$count = preg_match_all( td_audit_placeholder_regex(), $text, $m );
		if ( $count ) {
			$rows[] = [
				'where' => 'rendered',
				'url'   => $url,
				'match' => implode( ', ', array_unique( array_slice( $m[0], 0, 3 ) ) ),
				'count' => (int) $count,
			];
		}
	}
	return $rows;
}

/**
 * Review cadence (months) for a post type: money pages 3, guides 6 (§10.1).
 *
 * @param string $type Post type.
 */
function td_audit_cadence( string $type ): int {
	if ( function_exists( 'td_cpt_definition' ) ) {
		$def = td_cpt_definition( $type );
		if ( is_array( $def ) && isset( $def['cadence'] ) ) {
			return (int) $def['cadence'];
		}
	}
	return match ( $type ) {
		'service', 'studio_service', 'solution', 'location', 'td_tool' => 3,
		'post', 'em-glossary', 'business' => 6,
		default => 0,
	};
}

/**
 * Pages past their review date.
 *
 * @param int $now Unix now.
 * @return array<int,array{url:string,type:string,last_reviewed:string,cadence:string}>
 */
function td_audit_review_due( int $now ): array {
	$rows = [];
	foreach ( td_audit_indexable_posts() as $post ) {
		$months = td_audit_cadence( $post->post_type );
		if ( $months <= 0 ) {
			continue;
		}
		$raw  = (string) td_field( 'last_reviewed', $post->ID );
		$when = preg_match( '/^(\d{4})-?(\d{2})-?(\d{2})/', $raw, $m ) ? gmmktime( 0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1] ) : (int) get_post_modified_time( 'U', true, $post );
		if ( Decay::review_overdue( (int) $when, $months, $now ) ) {
			$rows[] = [
				'url'           => (string) get_permalink( $post ),
				'type'          => $post->post_type,
				'last_reviewed' => '' !== $raw ? gmdate( 'Y-m-d', (int) $when ) : 'never (modified ' . gmdate( 'Y-m-d', (int) $when ) . ')',
				'cadence'       => $months . ' months',
			];
		}
	}
	return $rows;
}

/**
 * Crawl internal links from the home page and the sitemap.
 *
 * @param string $base Base URL.
 * @param int    $max  Max pages.
 * @return array<int,array{url:string,status:int,hops:int,final:string,from:string,note:string}>
 */
function td_audit_crawl( string $base, int $max ): array {
	$base  = untrailingslashit( $base );
	$host  = strtolower( (string) wp_parse_url( $base, PHP_URL_HOST ) ) . ':' . (int) wp_parse_url( $base, PHP_URL_PORT );
	$queue = [ $base . '/' => 'start' ];
	// Seed from the sitemap index (Rank Math).
	$index = td_cli_http_get( $base . '/sitemap_index.xml' );
	if ( 200 === $index['status'] && preg_match_all( '#<loc>([^<]+)</loc>#', $index['body'], $maps ) ) {
		foreach ( array_slice( $maps[1], 0, 50 ) as $map ) {
			$sm = td_cli_http_get( html_entity_decode( $map ) );
			if ( preg_match_all( '#<loc>([^<]+)</loc>#', $sm['body'], $locs ) ) {
				foreach ( $locs[1] as $loc ) {
					$queue[ html_entity_decode( $loc ) ] = $queue[ html_entity_decode( $loc ) ] ?? 'sitemap';
				}
			}
		}
	}
	$seen = [];
	$rows = [];
	while ( $queue && count( $seen ) < $max ) {
		$url  = (string) array_key_first( $queue );
		$from = (string) $queue[ $url ];
		unset( $queue[ $url ] );
		if ( isset( $seen[ $url ] ) ) {
			continue;
		}
		$seen[ $url ] = true;
		$hops         = 0;
		$current      = $url;
		$visited      = [ $url => true ];
		$note         = '';
		do {
			$res = td_cli_http_get( $current );
			if ( $res['status'] >= 300 && $res['status'] < 400 && '' !== $res['location'] ) {
				++$hops;
				$current = $res['location'];
				if ( isset( $visited[ $current ] ) ) {
					$note = 'loop';
					break;
				}
				$visited[ $current ] = true;
			} else {
				break;
			}
		} while ( $hops < 6 );
		if ( $hops > 1 ) {
			$note = $note ? $note : 'redirect chain';
		}
		$rows[] = [
			'url'    => $url,
			'status' => $res['status'],
			'hops'   => $hops,
			'final'  => $current,
			'from'   => $from,
			'note'   => $res['error'] ? $res['error'] : $note,
		];
		if ( 200 === $res['status'] && preg_match_all( '/<a\s[^>]*href=["\']([^"\'#]+)/i', $res['body'], $links ) ) {
			foreach ( $links[1] as $href ) {
				$href = html_entity_decode( $href );
				if ( str_starts_with( $href, '/' ) && ! str_starts_with( $href, '//' ) ) {
					$href = $base . $href;
				}
				$h = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) ) . ':' . (int) wp_parse_url( $href, PHP_URL_PORT );
				if ( $h !== $host || preg_match( '#/(wp-admin|wp-json|wp-login\.php|feed)(/|$)|\.(pdf|jpe?g|png|webp|avif|svg|zip|xml)$#i', (string) wp_parse_url( $href, PHP_URL_PATH ) ) ) {
					continue;
				}
				$href = (string) preg_replace( '/[?#].*$/', '', $href );
				if ( ! isset( $seen[ $href ] ) && ! isset( $queue[ $href ] ) ) {
					$queue[ $href ] = $url;
				}
			}
		}
	}
	return $rows;
}

/**
 * Write rows to uploads/td-audit/<name>-<date>.csv; returns the path.
 *
 * @param string                           $name Base name.
 * @param array<int,array<string,mixed>>   $rows Rows.
 */
function td_audit_save_csv( string $name, array $rows ): string {
	$uploads = wp_upload_dir();
	$dir     = trailingslashit( $uploads['basedir'] ) . 'td-audit';
	wp_mkdir_p( $dir );
	if ( ! is_file( $dir . '/index.php' ) ) {
		file_put_contents( $dir . '/index.php', "<?php\n// Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $dir . '/.htaccess', "Require all denied\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	$file = $dir . '/' . sanitize_file_name( $name . '-' . gmdate( 'Ymd-His' ) ) . '.csv';
	$fh   = fopen( $file, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	if ( false === $fh ) {
		return '';
	}
	if ( $rows ) {
		fputcsv( $fh, array_keys( $rows[0] ), ',', '"', '' );
		foreach ( $rows as $row ) {
			fputcsv( $fh, array_map( 'strval', $row ), ',', '"', '' );
		}
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	return $file;
}
