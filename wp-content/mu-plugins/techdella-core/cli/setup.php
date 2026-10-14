<?php
/**
 * `wp td setup` (§4.2): idempotent site setup. Core steps live here; other
 * modules hook td_setup (cpt at 5, forms, admin at 20, popups, ads).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Legal pages every site needs (stubs, created as drafts when missing).
 * Slugs follow the live site (url-map.csv).
 *
 * @return array<string,string> slug => title.
 */
function td_setup_legal_pages(): array {
	return (array) apply_filters(
		'td_setup_legal_pages',
		[
			'privacy-policy' => 'Privacy Policy',
			'terms'          => 'Terms of use',
			'cookies'        => 'Cookies',
			'disclaimer'     => 'Disclaimer',
		]
	);
}

/**
 * Run the whole setup. Returns log lines.
 *
 * @return string[]
 */
function td_setup_run(): array {
	$log = [];

	// Permalinks, comments, timezone.
	global $wp_rewrite;
	if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$log[] = 'Permalinks set to /%postname%/';
	}
	update_option( 'default_comment_status', 'closed' );
	update_option( 'default_ping_status', 'closed' );
	update_option( 'close_comments_for_old_posts', 1 );
	$tz = (string) apply_filters( 'td_setup_timezone', 'Africa/Lagos' ); // [CONFIRM] team timezone.
	if ( get_option( 'timezone_string' ) !== $tz ) {
		update_option( 'timezone_string', $tz );
		$log[] = 'Timezone set to ' . $tz;
	}

	// WordPress's sample post and page, only while untouched (never anything a person edited).
	foreach ( [ [ 'hello-world', 'post' ], [ 'sample-page', 'page' ] ] as [ $slug, $type ] ) {
		$sample = get_page_by_path( $slug, OBJECT, $type );
		if ( $sample instanceof WP_Post && 'publish' === $sample->post_status && $sample->post_modified_gmt === $sample->post_date_gmt ) {
			wp_trash_post( $sample->ID );
			$log[] = "Trashed WordPress's sample {$type} /{$slug}/";
		}
	}

	// Studios guides (owner, 12 Oct 2026): posts live under a /guides/ posts page on Studios.
	if ( 'studios' === td_site() ) {
		$guides = get_page_by_path( 'guides', OBJECT, 'page' );
		if ( ! $guides instanceof WP_Post ) {
			$gid    = wp_insert_post(
				[
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => 'Guides',
					'post_name'    => 'guides',
					'post_content' => '',
				],
				true
			);
			$guides = is_wp_error( $gid ) ? null : get_post( $gid );
			$log[]  = 'Created /guides/ (the Studios guides page)';
		}
		if ( $guides instanceof WP_Post && (int) get_option( 'page_for_posts' ) !== $guides->ID ) {
			update_option( 'page_for_posts', $guides->ID );
			$log[] = 'Guides page set as the posts page';
		}
	}

	// Styleguide (published, noindex) on every site.
	$styleguide = get_page_by_path( 'styleguide', OBJECT, 'page' );
	if ( ! $styleguide instanceof WP_Post ) {
		$id = wp_insert_post(
			[
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Styleguide',
				'post_name'    => 'styleguide',
				'post_content' => '',
			],
			true
		);
		if ( ! is_wp_error( $id ) ) {
			$log[]      = 'Created /styleguide/ (published, noindex)';
			$styleguide = get_post( $id );
		}
	} elseif ( 'publish' !== $styleguide->post_status ) {
		wp_update_post(
			[
				'ID'          => $styleguide->ID,
				'post_status' => 'publish',
			]
		);
		$log[] = 'Published /styleguide/';
	}
	if ( $styleguide instanceof WP_Post ) {
		update_post_meta( $styleguide->ID, '_td_noindex', 1 );
	}

	// Legal stubs (drafts; a person writes and publishes them).
	foreach ( td_setup_legal_pages() as $slug => $title ) {
		if ( get_page_by_path( $slug, OBJECT, 'page' ) instanceof WP_Post ) {
			continue;
		}
		$found = get_posts(
			[
				'post_type'      => 'page',
				'name'           => $slug,
				'post_status'    => [ 'draft', 'pending', 'private', 'publish', 'future' ],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]
		);
		if ( $found ) {
			continue;
		}
		wp_insert_post(
			[
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => '<!-- wp:paragraph --><p>[CONFIRM] ' . esc_html( $title ) . ' text to be written and approved before publishing.</p><!-- /wp:paragraph -->',
			]
		);
		$log[] = sprintf( 'Created draft /%s/', $slug );
	}

	$log = array_merge( $log, td_setup_rank_math() );

	/**
	 * Other modules' setup steps (cpt 5, forms 10, popups/ads 10, admin 20).
	 *
	 * @param string $site Site key.
	 */
	do_action( 'td_setup', td_site() );

	flush_rewrite_rules( false );
	$log[] = 'Rewrite rules flushed';
	return $log;
}

/**
 * Rank Math (D5): redirections, sitemap and 404 monitor on; schema off.
 *
 * @return string[]
 */
function td_setup_rank_math(): array {
	if ( ! defined( 'RANK_MATH_VERSION' ) ) {
		return [ 'Rank Math not active: skipped its settings' ];
	}
	$log     = [];
	$modules = get_option( 'rank_math_modules', [] );
	$modules = is_array( $modules ) ? $modules : [];
	$want    = array_values( array_unique( array_diff( array_merge( $modules, [ 'redirections', 'sitemap', '404-monitor' ] ), [ 'rich-snippet' ] ) ) );
	if ( $want !== $modules ) {
		update_option( 'rank_math_modules', $want );
		$log[] = 'Rank Math modules: ' . implode( ', ', $want );
	}
	if ( class_exists( '\RankMath\Installer' ) ) {
		\RankMath\Installer::create_tables( [ 'redirections', '404-monitor' ] );
	}
	$general = get_option( 'rank-math-options-general', [] );
	if ( is_array( $general ) ) {
		$changed                                        = $general;
		$changed['redirections_header_code']            = '301';
		$changed['redirections_debug']                  = 'off';
		$changed['redirections_fallback']               = 'default'; // 404s stay 404s (no soft redirects to home).
		$changed['redirections_post_redirect']          = 'on'; // Auto-redirect when a slug changes.
		$changed['404_monitor_mode']                    = 'simple';
		$changed['404_monitor_ignore_query_parameters'] = 'on';
		if ( $changed !== $general ) {
			update_option( 'rank-math-options-general', $changed );
			$log[] = 'Rank Math redirect + 404 monitor settings applied';
		}
	}
	update_option( 'rank_math_wizard_completed', 1 );
	return $log;
}
