<?php
/**
 * `wp td import-content` (§10.1 step 3): Markdown + YAML front matter →
 * WordPress posts as DRAFT or PENDING only (§0.4). Matched by slug + post type.
 * Published posts are never touched (a person owns them once live).
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Cli\FrontMatter;

/**
 * Folder name → [ site, post type ].
 *
 * @return array<string,array{0:string,1:string}>
 */
function td_import_folder_map(): array {
	return [
		'marketing'    => [ 'marketing', 'page' ],
		'journal'      => [ 'journal', 'post' ],
		'glossary'     => [ 'marketing', 'em-glossary' ],
		'case-studies' => [ 'marketing', 'case_study' ],
		'solutions'    => [ 'marketing', 'solution' ],
		'locations'    => [ 'marketing', 'location' ],
		'tools'        => [ 'marketing', 'td_tool' ],
		'resources'    => [ 'marketing', 'td_resource' ],
		'services'     => [ 'marketing', 'service' ],
		'studios'      => [ 'studios', 'page' ],
		'work'         => [ 'studios', 'studio_work' ],
	];
}

/**
 * Keys handled by the importer itself (everything else becomes a field).
 */
const TD_IMPORT_RESERVED = [ 'title', 'slug', 'type', 'site', 'excerpt', 'status', 'hold', 'format', 'author', 'reviewer', 'featured_image', 'image', 'pdf', 'gallery', 'fields', 'categories', 'tags', 'taxonomies', 'parent', 'menu_order', 'template', 'date' ];

/**
 * Markdown files under a path (a file or a directory, recursive), README excluded.
 *
 * @param string $path File or directory.
 * @return string[]
 */
function td_import_files( string $path ): array {
	if ( is_file( $path ) ) {
		return [ $path ];
	}
	$files = [];
	if ( ! is_dir( $path ) ) {
		return $files;
	}
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $file ) {
		// $file is an SplFileInfo.
		if ( 'md' === strtolower( $file->getExtension() ) && 'readme.md' !== strtolower( $file->getFilename() ) && ! str_starts_with( $file->getFilename(), '_' ) ) {
			$files[] = $file->getPathname();
		}
	}
	sort( $files );
	return $files;
}

/**
 * Infer [ site, post type ] from the file's folders (nearest known folder wins).
 *
 * @param string $file File path.
 * @return array{0:string,1:string}|null
 */
function td_import_infer( string $file ): ?array {
	$map   = td_import_folder_map();
	$parts = array_reverse( explode( '/', str_replace( '\\', '/', dirname( $file ) ) ) );
	foreach ( $parts as $part ) {
		if ( isset( $map[ $part ] ) ) {
			return $map[ $part ];
		}
	}
	return null;
}

/**
 * Markdown → block markup (paragraph, heading, list, quote, table, image;
 * anything else as a Custom HTML block). Body with `format: blocks` passes through.
 *
 * @param string $markdown Markdown.
 */
function td_import_markdown_to_blocks( string $markdown ): string {
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property names.
	if ( ! class_exists( 'Parsedown' ) ) {
		require_once TD_CORE_DIR . '/lib/Parsedown.php';
	}
	// Article components (:::callout, :::stats …) become techdella/* blocks; see content/README.md.
	$directives = \TechDella\Core\Cli\Directives::extract( $markdown );
	foreach ( $directives['errors'] as $error ) {
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::warning( 'Article block: ' . $error );
		}
	}
	$markdown = $directives['markdown'];
	$parser   = new Parsedown();
	$html     = (string) $parser->text( $markdown );
	// Cross-site links: (studios:/work/) → td_url( 'studios', '/work/' ), so content never hard-codes domains (§4.1).
	$html = (string) preg_replace_callback(
		'#href="(marketing|journal|studios):(/[^"]*)"#',
		static fn( array $m ): string => 'href="' . esc_url( td_url( $m[1], $m[2] ) ) . '"',
		$html
	);
	if ( '' === trim( $html ) ) {
		return '';
	}
	$doc  = new DOMDocument();
	$prev = libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="utf-8"?><div id="td-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	$root = $doc->getElementById( 'td-root' );
	if ( ! $root ) {
		return $html;
	}
	$out  = [];
	$html = static fn( DOMNode $n ): string => (string) $doc->saveHTML( $n );
	foreach ( iterator_to_array( $root->childNodes ) as $node ) {
		if ( ! $node instanceof DOMElement ) {
			if ( '' !== trim( (string) $node->textContent ) ) {
				$out[] = "<!-- wp:paragraph -->\n<p>" . esc_html( trim( (string) $node->textContent ) ) . "</p>\n<!-- /wp:paragraph -->";
			}
			continue;
		}
		$tag = strtolower( $node->tagName );
		if ( 'p' === $tag && 1 === $node->childNodes->length && $node->firstChild instanceof DOMElement && 'img' === strtolower( $node->firstChild->tagName ) ) {
			$out[] = "<!-- wp:image -->\n<figure class=\"wp-block-image\">" . $html( $node->firstChild ) . "</figure>\n<!-- /wp:image -->";
		} elseif ( 'p' === $tag ) {
			$out[] = "<!-- wp:paragraph -->\n" . $html( $node ) . "\n<!-- /wp:paragraph -->";
		} elseif ( preg_match( '/^h([1-6])$/', $tag, $m ) ) {
			$level = max( 2, (int) $m[1] ); // One H1 per page: the title (§8.5).
			$node->setAttribute( 'class', 'wp-block-heading' );
			$inner = '';
			foreach ( iterator_to_array( $node->childNodes ) as $child ) {
				$inner .= $html( $child );
			}
			$attrs = 2 === $level ? '' : ' {"level":' . $level . '}';
			$out[] = "<!-- wp:heading{$attrs} -->\n<h{$level} class=\"wp-block-heading\">{$inner}</h{$level}>\n<!-- /wp:heading -->";
		} elseif ( 'ul' === $tag || 'ol' === $tag ) {
			$items = '';
			foreach ( iterator_to_array( $node->childNodes ) as $li ) {
				if ( $li instanceof DOMElement && 'li' === strtolower( $li->tagName ) ) {
					$items .= "<!-- wp:list-item -->\n" . $html( $li ) . "\n<!-- /wp:list-item -->\n";
				}
			}
			$attrs = 'ol' === $tag ? ' {"ordered":true}' : '';
			$out[] = "<!-- wp:list{$attrs} -->\n<{$tag} class=\"wp-block-list\">\n{$items}</{$tag}>\n<!-- /wp:list -->";
		} elseif ( 'blockquote' === $tag ) {
			$inner = '';
			foreach ( iterator_to_array( $node->childNodes ) as $child ) {
				if ( $child instanceof DOMElement && 'p' === strtolower( $child->tagName ) ) {
					$inner .= "<!-- wp:paragraph -->\n" . $html( $child ) . "\n<!-- /wp:paragraph -->\n";
				}
			}
			$out[] = "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\">\n{$inner}</blockquote>\n<!-- /wp:quote -->";
		} elseif ( 'table' === $tag ) {
			$out[] = "<!-- wp:table -->\n<figure class=\"wp-block-table\">" . $html( $node ) . "</figure>\n<!-- /wp:table -->";
		} elseif ( 'hr' === $tag ) {
			$out[] = "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->";
		} else {
			$out[] = "<!-- wp:html -->\n" . $html( $node ) . "\n<!-- /wp:html -->";
		}
	}
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$content = implode( "\n\n", $out );
	foreach ( $directives['blocks'] as $n => $block ) {
		$content = str_replace(
			"<!-- wp:paragraph -->\n<p>" . \TechDella\Core\Cli\Directives::PLACEHOLDER . $n . "</p>\n<!-- /wp:paragraph -->",
			get_comment_delimited_block_content( $block['name'], $block['attrs'], '' ),
			$content
		);
	}
	return $content;
}

/**
 * Resolve a user from a login, email or td_team slug (via its wp_user field).
 *
 * @param mixed $value Login / email / team slug / ID.
 */
function td_import_user_id( $value ): int {
	if ( is_int( $value ) ) {
		return get_userdata( $value ) ? $value : 0;
	}
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return 0;
	}
	foreach ( [ 'login', 'email', 'slug' ] as $by ) {
		$user = get_user_by( $by, $value );
		if ( $user ) {
			return (int) $user->ID;
		}
	}
	if ( post_type_exists( 'td_team' ) ) {
		$team = get_page_by_path( sanitize_title( $value ), OBJECT, 'td_team' );
		if ( $team instanceof WP_Post ) {
			$user = td_field( 'wp_user', $team->ID );
			if ( is_array( $user ) ) {
				$user = $user['ID'] ?? 0;
			}
			return (int) ( $user instanceof WP_User ? $user->ID : $user );
		}
	}
	return 0;
}

/**
 * ACF fields that apply to a post (by its field groups' location rules), by name.
 *
 * @param int $post_id Post ID.
 * @return array<string,array<string,mixed>>
 */
function td_import_acf_fields( int $post_id ): array {
	if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
		return [];
	}
	$fields = [];
	foreach ( (array) acf_get_field_groups( [ 'post_id' => $post_id ] ) as $group ) {
		foreach ( (array) acf_get_fields( $group ) as $field ) {
			if ( is_array( $field ) && ! empty( $field['name'] ) ) {
				$fields[ (string) $field['name'] ] = $field;
			}
		}
	}
	return $fields;
}

/**
 * Split a front-matter repeater row "a | b | c" across sub-field names, in order.
 * Extra parts join the last sub-field; missing ones stay empty.
 *
 * @param string            $row  Row text.
 * @param array<int,string> $subs Sub-field names.
 * @return array<string,string>
 */
function td_import_split_row( string $row, array $subs ): array {
	$parts = array_map( 'trim', explode( ' | ', $row, max( 1, count( $subs ) ) ) );
	$out   = [];
	foreach ( $subs as $i => $sub ) {
		$out[ $sub ] = $parts[ $i ] ?? '';
	}
	return $out;
}

/**
 * Write one field: ACF (with light type mapping) when it knows the field,
 * else post meta.
 *
 * @param int    $post_id Post ID.
 * @param string $name    Field name.
 * @param mixed  $value   Value from front matter.
 */
function td_import_set_field( int $post_id, string $name, $value ): void {
	$name = sanitize_key( $name );
	// Repeater rows written as "a | b" fill the sub-fields in order (e.g. faq:
	// "Question? | Answer."). Uses the JSON field definitions, so it works with
	// free ACF too, where repeaters are saved as plain meta.
	$def = function_exists( 'td_cpt_fields' ) ? ( td_cpt_fields()->fields( (string) get_post_type( $post_id ) )[ $name ] ?? null ) : null;
	if ( is_array( $def ) && 'repeater' === ( $def['type'] ?? '' ) && is_array( $value ) && array_is_list( $value ) && ! empty( $def['sub_fields'] ) ) {
		$subs  = array_map( static fn( $f ) => (string) $f['name'], (array) $def['sub_fields'] );
		$value = array_map( static fn( $v ) => is_array( $v ) ? $v : td_import_split_row( (string) $v, $subs ), $value );
		// Sub-fields that point at posts or users take slugs/logins (e.g. services_used.service).
		foreach ( (array) $def['sub_fields'] as $sub_field ) {
			$sub_name = (string) $sub_field['name'];
			foreach ( $value as $i => $row ) {
				if ( is_array( $row ) && isset( $row[ $sub_name ] ) && '' !== $row[ $sub_name ] && ! is_numeric( $row[ $sub_name ] ) && in_array( $sub_field['type'] ?? '', [ 'post_object', 'relationship', 'user' ], true ) ) {
					$value[ $i ][ $sub_name ] = td_import_field_value( (array) $sub_field, $row[ $sub_name ] );
				}
			}
		}
	}
	$field = td_import_acf_fields( $post_id )[ $name ] ?? null;
	if ( is_array( $field ) && function_exists( 'update_field' ) ) {
		$type = (string) ( $field['type'] ?? '' );
		if ( 'repeater' === $type && is_array( $value ) && array_is_list( $value ) && ! empty( $field['sub_fields'] ) ) {
			$sub   = (string) $field['sub_fields'][0]['name'];
			$value = array_map( static fn( $v ) => is_array( $v ) ? $v : [ $sub => $v ], $value );
			// Sub-fields that point at posts or users take slugs/logins too (e.g. services_used.service).
			foreach ( (array) $field['sub_fields'] as $sub_field ) {
				$sub_name = (string) $sub_field['name'];
				foreach ( $value as $i => $row ) {
					if ( is_array( $row ) && isset( $row[ $sub_name ] ) && ! is_numeric( $row[ $sub_name ] ) ) {
						$value[ $i ][ $sub_name ] = td_import_field_value( $sub_field, $row[ $sub_name ] );
					}
				}
			}
		} else {
			$value = td_import_field_value( $field, $value );
		}
		update_field( (string) $field['key'], $value, $post_id );
		return;
	}
	update_post_meta( $post_id, $name, is_array( $value ) ? $value : (string) ( is_bool( $value ) ? (int) $value : $value ) );
}

/**
 * Convert a front-matter value for one ACF field: user logins, dates, and post slugs for
 * relationship/post_object fields (found by slug in any status, so drafts link too).
 *
 * @param array<string,mixed> $field ACF field definition.
 * @param mixed               $value Value from the front matter.
 * @return mixed
 */
function td_import_field_value( array $field, $value ) {
	$type = (string) ( $field['type'] ?? '' );
	if ( 'user' === $type ) {
		return td_import_user_id( $value );
	}
	if ( 'date_picker' === $type ) {
		return str_replace( '-', '', (string) $value );
	}
	if ( ! in_array( $type, [ 'relationship', 'post_object' ], true ) ) {
		return $value;
	}
	$ids = [];
	foreach ( (array) $value as $slug ) {
		$found = get_posts(
			[
				'post_name__in'  => [ sanitize_title( (string) $slug ) ],
				'post_type'      => ! empty( $field['post_type'] ) ? (array) $field['post_type'] : 'any',
				'post_status'    => [ 'publish', 'draft', 'pending', 'future', 'private' ],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]
		);
		if ( $found ) {
			$ids[] = (int) $found[0];
		}
	}
	return 'post_object' === $type ? ( $ids[0] ?? 0 ) : $ids;
}

/**
 * Sideload a file from the repo (featured image, or a resource PDF). Reuses the
 * attachment from an earlier import while the file is unchanged.
 *
 * @param string $rel          Path relative to the repo root (e.g. assets/images/x.jpg).
 * @param string $base         Repo root.
 * @param int    $post_id      Post ID.
 * @param bool   $private_path Upload under uploads/td-files/<random>/ (PDF lead magnets: URL not guessable, §13.2).
 * @return int|string Attachment ID or error message.
 */
function td_import_featured( string $rel, string $base, int $post_id, bool $private_path = false ) {
	$rel  = ltrim( str_replace( '..', '', $rel ), '/' );
	$file = '';
	foreach ( [ $base . '/' . $rel, '/var/www/repo/' . $rel ] as $candidate ) {
		if ( is_readable( $candidate ) ) {
			$file = $candidate;
			break;
		}
	}
	if ( '' === $file ) {
		return 'file not found: ' . $rel;
	}
	$hash     = (string) md5_file( $file );
	$existing = get_posts(
		[
			'post_type'      => 'attachment',
			'meta_key'       => '_td_source_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $rel, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page' => 1,
			'fields'         => 'ids',
		]
	);
	$old_hash = $existing ? (string) get_post_meta( (int) $existing[0], '_td_source_hash', true ) : '';
	if ( $existing && ( '' === $old_hash || $old_hash === $hash ) ) {
		return (int) $existing[0];
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( basename( $file ) );
	copy( $file, $tmp );
	if ( $private_path && function_exists( 'td_forms_protect_private_files' ) ) {
		td_forms_protect_private_files();
	}
	$dir_filter = static function ( array $dirs ): array {
		$sub            = '/td-files/' . bin2hex( random_bytes( 16 ) );
		$dirs['path']   = $dirs['basedir'] . $sub;
		$dirs['url']    = $dirs['baseurl'] . $sub;
		$dirs['subdir'] = $sub;
		return $dirs;
	};
	if ( $private_path ) {
		add_filter( 'upload_dir', $dir_filter );
	}
	$id = media_handle_sideload(
		[
			'name'     => basename( $file ),
			'tmp_name' => $tmp,
		],
		$post_id
	);
	remove_filter( 'upload_dir', $dir_filter );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return $id->get_error_message();
	}
	update_post_meta( (int) $id, '_td_source_file', $rel );
	update_post_meta( (int) $id, '_td_source_hash', $hash );
	if ( $existing ) {
		wp_delete_attachment( (int) $existing[0], true ); // Replaced by the new version.
	}
	return (int) $id;
}

/**
 * Import one file.
 *
 * @param string $file   File path.
 * @param string $status draft|pending.
 * @param bool   $dry    Dry run.
 * @param string $base   Repo root (for images).
 * @return array{file:string,type:string,slug:string,result:string,confirm:int}
 */
function td_import_file( string $file, string $status, bool $dry, string $base ): array {
	$row = [
		'file'    => basename( dirname( $file ) ) . '/' . basename( $file ),
		'type'    => '',
		'slug'    => '',
		'result'  => '',
		'confirm' => 0,
	];
	try {
		$parsed = FrontMatter::parse( (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	} catch ( InvalidArgumentException $e ) {
		$row['result'] = 'error: ' . $e->getMessage();
		return $row;
	}
	$meta           = $parsed['meta'];
	$body           = $parsed['body'];
	$row['confirm'] = FrontMatter::count_confirm( $body . ' ' . (string) wp_json_encode( $meta ) );

	$inferred    = td_import_infer( $file );
	$site        = (string) ( $meta['site'] ?? ( $inferred[0] ?? '' ) );
	$type        = (string) ( $meta['type'] ?? ( $inferred[1] ?? 'page' ) );
	$type        = 'studios' === $type ? 'page' : $type;
	$slug        = sanitize_title( (string) ( $meta['slug'] ?? preg_replace( '/\.md$/i', '', basename( $file ) ) ) );
	$row['type'] = $type;
	$row['slug'] = $slug;

	if ( '' !== $site && td_site() !== $site ) {
		$row['result'] = 'skipped: for ' . $site;
		return $row;
	}
	if ( ! post_type_exists( $type ) ) {
		$row['result'] = 'skipped: unknown post type ' . $type;
		return $row;
	}
	if ( '' === trim( (string) ( $meta['title'] ?? '' ) ) ) {
		$row['result'] = 'error: missing title';
		return $row;
	}

	$existing = get_posts(
		[
			// post_name__in, not `name`: a `name` query is "singular" and hides drafts from WP-CLI
			// (no user), which would create a duplicate draft on every run.
			'post_name__in'  => [ $slug ],
			'post_type'      => $type,
			'post_status'    => [ 'publish', 'future', 'private', 'draft', 'pending' ],
			'posts_per_page' => 1,
		]
	);
	$post     = $existing ? $existing[0] : null;
	$live     = $post && in_array( $post->post_status, [ 'publish', 'future', 'private' ], true );
	// --update-published (local/staging only): refresh a published copy, keeping its status.
	if ( $live && ! apply_filters( 'td_import_update_published', false ) ) {
		$row['result'] = 'skipped: already ' . $post->post_status . ' (edit it in WordPress)';
		return $row;
	}
	if ( $live ) {
		$status = $post->post_status;
	}

	$content = 'blocks' === ( $meta['format'] ?? '' ) ? trim( $body ) : td_import_markdown_to_blocks( $body );
	$data    = [
		'post_type'    => $type,
		'post_status'  => $status,
		'post_title'   => wp_strip_all_tags( (string) $meta['title'] ),
		'post_name'    => $slug,
		'post_content' => $content,
		'post_excerpt' => (string) ( $meta['excerpt'] ?? '' ),
	];
	if ( isset( $meta['menu_order'] ) ) {
		$data['menu_order'] = (int) $meta['menu_order'];
	}
	if ( ! empty( $meta['parent'] ) && is_post_type_hierarchical( $type ) ) {
		$parent = get_page_by_path( (string) $meta['parent'], OBJECT, $type );
		if ( $parent instanceof WP_Post ) {
			$data['post_parent'] = $parent->ID;
		}
	}
	if ( ! empty( $meta['author'] ) ) {
		$author = td_import_user_id( $meta['author'] );
		if ( $author ) {
			$data['post_author'] = $author;
		}
	}

	$row['result'] = ( $post ? 'updated' : 'created' ) . ( $dry ? ' (dry run)' : '' );
	if ( $dry ) {
		return $row;
	}
	if ( $post ) {
		$data['ID'] = $post->ID;
		// A page template left by the old theme makes wp_update_post fail ("Invalid page template"):
		// fall back to this theme's template for the post type.
		$tpl = (string) get_post_meta( $post->ID, '_wp_page_template', true );
		if ( '' !== $tpl && 'default' !== $tpl && ! isset( wp_get_theme()->get_page_templates( $post, $post->post_type )[ $tpl ] ) ) {
			$data['page_template'] = 'default';
		}
	}
	// Never publish from here, even if a filter tries (§0.4).
	// Never publishes (§0.4); --update-published keeps an already-live copy's status on local/staging.
	$data['post_status'] = in_array( $data['post_status'], [ 'draft', 'pending' ], true ) || $live ? $data['post_status'] : 'draft';
	if ( apply_filters( 'td_import_publish', false ) && in_array( td_environment(), [ 'local', 'staging' ], true ) ) {
		$data['post_status'] = 'publish'; // Staging preview only (wp td import-content --publish).
	}
	// `hold: true` keeps unverified items (e.g. a claimed project) off every site until a person confirms them.
	if ( ! empty( $meta['hold'] ) ) {
		$data['post_status'] = 'draft';
	}
	$id = $post ? wp_update_post( wp_slash( $data ), true ) : wp_insert_post( wp_slash( $data ), true );
	if ( is_wp_error( $id ) ) {
		$row['result'] = 'error: ' . $id->get_error_message();
		return $row;
	}
	$id = (int) $id;

	// Taxonomies.
	$terms = (array) ( $meta['taxonomies'] ?? [] );
	if ( isset( $meta['categories'] ) ) {
		$terms['category'] = $meta['categories'];
	}
	if ( isset( $meta['tags'] ) ) {
		$terms['post_tag'] = $meta['tags'];
	}
	$notes = [];
	foreach ( $terms as $taxonomy => $values ) {
		$taxonomy = sanitize_key( (string) $taxonomy );
		if ( ! taxonomy_exists( $taxonomy ) || ! is_object_in_taxonomy( $type, $taxonomy ) ) {
			$notes[] = 'taxonomy ' . $taxonomy . ' n/a';
			continue;
		}
		$ids = [];
		foreach ( (array) $values as $value ) {
			$value = trim( (string) $value );
			$term  = get_term_by( 'slug', sanitize_title( $value ), $taxonomy );
			if ( ! $term ) {
				$name    = preg_match( '/^[a-z0-9-]+$/', $value ) ? ucwords( str_replace( '-', ' ', $value ) ) : $value; // Slug → readable name; editors can rename.
				$created = wp_insert_term( $name, $taxonomy, [ 'slug' => sanitize_title( $value ) ] );
				$term    = is_wp_error( $created ) ? null : get_term( (int) $created['term_id'], $taxonomy );
			}
			if ( $term instanceof WP_Term ) {
				$ids[] = (int) $term->term_id;
			}
		}
		wp_set_object_terms( $id, $ids, $taxonomy );
	}

	// Fields (brief fields, ACF fields, anything else).
	if ( isset( $meta['reviewer'] ) ) {
		td_import_set_field( $id, 'reviewer', $meta['reviewer'] );
	}
	foreach ( $meta as $key => $value ) {
		if ( in_array( $key, TD_IMPORT_RESERVED, true ) ) {
			continue;
		}
		td_import_set_field( $id, (string) $key, $value );
	}
	// `fields:` sets fields whose names are reserved keys above (e.g. studio_work `status`).
	foreach ( (array) ( $meta['fields'] ?? [] ) as $key => $value ) {
		td_import_set_field( $id, (string) $key, $value );
	}
	update_post_meta( $id, '_td_import_source', $row['file'] );
	update_post_meta( $id, '_td_confirm_count', $row['confirm'] );

	// Gallery: repo image paths → attachment IDs in the `gallery` field (studio_work, case_study).
	if ( ! empty( $meta['gallery'] ) && is_array( $meta['gallery'] ) ) {
		$ids = [];
		foreach ( $meta['gallery'] as $rel ) {
			$att = td_import_featured( (string) $rel, $base, $id );
			if ( is_int( $att ) ) {
				$ids[] = $att;
			} else {
				$notes[] = $att;
			}
		}
		td_import_set_field( $id, 'gallery', $ids );
	}

	// Resource PDF (§13.2): uploaded under an unguessable path, stored in the `pdf` field.
	$pdf = (string) ( $meta['pdf'] ?? '' );
	if ( '' !== $pdf ) {
		$att = str_ends_with( strtolower( $pdf ), '.pdf' ) ? td_import_featured( $pdf, $base, $id, true ) : 'pdf must be a .pdf file: ' . $pdf;
		if ( is_int( $att ) ) {
			td_import_set_field( $id, 'pdf', $att );
		} else {
			$notes[] = $att;
		}
	}

	$image = (string) ( $meta['featured_image'] ?? ( $meta['image'] ?? '' ) );
	if ( '' !== $image ) {
		$att = td_import_featured( $image, $base, $id );
		if ( is_int( $att ) ) {
			set_post_thumbnail( $id, $att );
		} else {
			$notes[] = $att;
		}
	}
	if ( $notes ) {
		$row['result'] .= ' (' . implode( '; ', $notes ) . ')';
	}
	return $row;
}
