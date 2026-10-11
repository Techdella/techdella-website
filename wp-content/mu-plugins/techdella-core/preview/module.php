<?php
/**
 * Page patterns → pages (Phase 3/4).
 *
 * Every full-page pattern in the theme's patterns/ folder can say which page it builds:
 *
 *   * TD Site: marketing
 *   * TD Post Type: page            (or service, studio_service, td_resource …)
 *   * TD Slug: growth-audit         (path for hierarchical pages: about-us, services)
 *   * TD Title: Growth audit
 *   * TD Front Page: yes            (optional; sets it as the static front page)
 *   * TD Template: blank            (optional; page template slug saved as _wp_page_template)
 *   * TD Description: …            (optional; 140–155 chars, saved as Rank Math's meta description)
 *
 * `wp td preview:seed` creates/updates those posts from the pattern markup. On local sites
 * they are published so the site can be browsed; anywhere else they are created as drafts
 * only (§0.4 — a person reviews and publishes). Existing content is never overwritten unless
 * the post was created by this command and not edited since (or --force on local).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Parse the theme's page patterns that declare a TD Slug.
 *
 * @return array<int,array{file:string,site:string,type:string,slug:string,title:string,front:bool,template:string,desc:string,content:string}>
 */
function td_preview_patterns(): array {
	$out = [];
	foreach ( (array) glob( get_theme_file_path( 'patterns/*.php' ) ) as $file ) {
		$head = get_file_data(
			(string) $file,
			[
				'site'     => 'TD Site',
				'type'     => 'TD Post Type',
				'slug'     => 'TD Slug',
				'title'    => 'TD Title',
				'front'    => 'TD Front Page',
				'template' => 'TD Template',
				'desc'     => 'TD Description',
			]
		);
		if ( '' === $head['slug'] || ( '' !== $head['site'] && td_site() !== $head['site'] ) ) {
			continue;
		}
		ob_start();
		include $file; // Patterns are PHP so they can call td_url()/esc_* for links.
		$content = (string) ob_get_clean();
		$out[]   = [
			'file'     => basename( (string) $file ),
			'site'     => $head['site'] ? $head['site'] : td_site(),
			'type'     => $head['type'] ? $head['type'] : 'page',
			'slug'     => trim( $head['slug'], '/' ),
			'title'    => $head['title'] ? $head['title'] : $head['slug'],
			'front'    => 'yes' === strtolower( $head['front'] ),
			'template' => $head['template'],
			'desc'     => $head['desc'],
			'content'  => trim( $content ),
		];
	}
	// Parents before children so hierarchical paths resolve.
	usort( $out, static fn( $a, $b ) => substr_count( $a['slug'], '/' ) <=> substr_count( $b['slug'], '/' ) );
	return $out;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Create/update pages from the theme's page patterns (local: published; elsewhere: drafts).
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Local and staging only — overwrite content even if the post was edited after seeding
	 * (WordPress keeps the old content as a revision). Never on production.
	 *
	 * [--publish]
	 * : Staging only — publish the pattern pages so the private staging site can be browsed.
	 *
	 * [--dry-run]
	 * : List what would happen.
	 */
	WP_CLI::add_command(
		'td preview:seed',
		static function ( array $args, array $assoc ) {
			$local   = 'local' === td_environment();
			$staging = 'staging' === td_environment();
			$publish = $local || ( $staging && ! empty( $assoc['publish'] ) );
			$status  = $publish ? 'publish' : 'draft';
			$force   = ( $local || $staging ) && ! empty( $assoc['force'] );
			if ( ! $local && ! $staging && ( ! empty( $assoc['force'] ) || ! empty( $assoc['publish'] ) ) ) {
				WP_CLI::error( '--force and --publish only work on local and staging sites.' );
			}
			$dry  = ! empty( $assoc['dry-run'] );
			$rows = [];
			foreach ( td_preview_patterns() as $p ) {
				if ( ! post_type_exists( $p['type'] ) ) {
					$rows[] = [ $p['file'], $p['type'], $p['slug'], 'skipped: no post type' ];
					continue;
				}
				$parts    = explode( '/', $p['slug'] );
				$name     = (string) array_pop( $parts );
				$parent   = 0;
				$existing = null;
				if ( is_post_type_hierarchical( $p['type'] ) ) {
					$existing = get_page_by_path( $p['slug'], OBJECT, $p['type'] );
					if ( $parts ) {
						$parent_post = get_page_by_path( implode( '/', $parts ), OBJECT, $p['type'] );
						$parent      = $parent_post ? (int) $parent_post->ID : 0;
					}
				} else {
					$found    = get_posts(
						[
							'post_type'      => $p['type'],
							// post_name__in, not `name`: a `name` query is "singular" and hides drafts
							// from WP-CLI (no user), which made every run create a duplicate draft.
							'post_name__in'  => [ $name ],
							'post_status'    => 'any',
							'posts_per_page' => 1,
						]
					);
					$existing = $found ? $found[0] : null;
				}
				$data = [
					'post_type'    => $p['type'],
					'post_title'   => $p['title'],
					'post_name'    => $name,
					'post_parent'  => $parent,
					'post_content' => $p['content'],
					'post_status'  => $status,
				];
				$hash = md5( $p['content'] );
				if ( $existing ) {
					$seeded = (string) get_post_meta( $existing->ID, '_td_seed_hash', true );
					$edited = '' === $seeded || md5( $existing->post_content ) !== $seeded;
					if ( $edited && ! $force ) {
						$rows[] = [ $p['file'], $p['type'], $p['slug'], 'kept (edited by a person)' ];
						continue;
					}
					$data['ID'] = $existing->ID;
					if ( ! $publish ) {
						$data['post_status'] = $existing->post_status; // Never change status on production.
					}
				}
				if ( $dry ) {
					$rows[] = [ $p['file'], $p['type'], $p['slug'], $existing ? 'would update' : 'would create ' . $status ];
					continue;
				}
				// Seeding is a system action: bypass the editorial gates where it may publish (local, staging).
				if ( $publish ) {
					add_filter( 'td_checklist_bypass', '__return_true' );
				}
				$id = wp_insert_post( wp_slash( $data ), true );
				if ( is_wp_error( $id ) ) {
					$rows[] = [ $p['file'], $p['type'], $p['slug'], 'error: ' . $id->get_error_message() ];
					continue;
				}
				update_post_meta( (int) $id, '_td_seed_hash', md5( (string) get_post_field( 'post_content', (int) $id ) ) );
				// Meta description: only fill it when empty, so an editor's wording always wins.
				if ( '' !== $p['desc'] && '' === (string) get_post_meta( (int) $id, 'rank_math_description', true ) ) {
					update_post_meta( (int) $id, 'rank_math_description', $p['desc'] );
				}
				if ( $p['template'] ) {
					update_post_meta( (int) $id, '_wp_page_template', $p['template'] );
				}
				if ( $p['front'] ) {
					update_option( 'show_on_front', 'page' );
					update_option( 'page_on_front', (int) $id );
				}
				$rows[] = [ $p['file'], $p['type'], $p['slug'], ( $existing ? 'updated' : 'created' ) . ' ' . get_post_status( (int) $id ) . ' #' . $id ];
				unset( $hash );
			}
			WP_CLI\Utils\format_items( 'table', array_map( static fn( $r ) => array_combine( [ 'pattern', 'type', 'slug', 'result' ], $r ), $rows ), [ 'pattern', 'type', 'slug', 'result' ] );
		}
	);
}
