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
const TD_IMPORT_RESERVED = [ 'title', 'slug', 'type', 'site', 'excerpt', 'status', 'format', 'author', 'reviewer', 'featured_image', 'image', 'categories', 'tags', 'taxonomies', 'parent', 'menu_order', 'template', 'date' ];

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
		/** @var SplFileInfo $file */
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
	if ( ! class_exists( 'Parsedown' ) ) {
		require_once TD_CORE_DIR . '/lib/Parsedown.php';
	}
	$parser = new Parsedown();
	$html   = (string) $parser->text( $markdown );
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
	return implode( "\n\n", $out );
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
 * Write one field: ACF (with light type mapping) when it knows the field,
 * else post meta.
 *
 * @param int    $post_id Post ID.
 * @param string $name    Field name.
 * @param mixed  $value   Value from front matter.
 */
function td_import_set_field( int $post_id, string $name, $value ): void {
	$name  = sanitize_key( $name );
	$field = td_import_acf_fields( $post_id )[ $name ] ?? null;
	if ( is_array( $field ) && function_exists( 'update_field' ) ) {
		$type = (string) ( $field['type'] ?? '' );
		if ( 'repeater' === $type && is_array( $value ) && array_is_list( $value ) && ! empty( $field['sub_fields'] ) ) {
			$sub   = (string) $field['sub_fields'][0]['name'];
			$value = array_map( static fn( $v ) => is_array( $v ) ? $v : [ $sub => $v ], $value );
		} elseif ( 'user' === $type ) {
			$value = td_import_user_id( $value );
		} elseif ( 'date_picker' === $type ) {
			$value = str_replace( '-', '', (string) $value );
		} elseif ( in_array( $type, [ 'relationship', 'post_object' ], true ) ) {
			$ids = [];
			foreach ( (array) $value as $slug ) {
				$found = get_posts(
					[
						'name'           => sanitize_title( (string) $slug ),
						'post_type'      => (array) ( $field['post_type'] ?: 'any' ),
						'post_status'    => 'any',
						'posts_per_page' => 1,
						'fields'         => 'ids',
					]
				);
				if ( $found ) {
					$ids[] = (int) $found[0];
				}
			}
			$value = 'post_object' === $type ? ( $ids[0] ?? 0 ) : $ids;
		}
		update_field( (string) $field['key'], $value, $post_id );
		return;
	}
	update_post_meta( $post_id, $name, is_array( $value ) ? $value : (string) ( is_bool( $value ) ? (int) $value : $value ) );
}

/**
 * Sideload a featured image from the repo.
 *
 * @param string $rel     Path relative to the repo root (e.g. assets/images/x.jpg).
 * @param string $base    Repo root.
 * @param int    $post_id Post ID.
 * @return int|string Attachment ID or error message.
 */
function td_import_featured( string $rel, string $base, int $post_id ) {
	$rel  = ltrim( str_replace( '..', '', $rel ), '/' );
	$file = '';
	foreach ( [ $base . '/' . $rel, '/var/www/repo/' . $rel ] as $candidate ) {
		if ( is_readable( $candidate ) ) {
			$file = $candidate;
			break;
		}
	}
	if ( '' === $file ) {
		return 'image not found: ' . $rel;
	}
	$existing = get_posts(
		[
			'post_type'      => 'attachment',
			'meta_key'       => '_td_source_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $rel, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page' => 1,
			'fields'         => 'ids',
		]
	);
	if ( $existing ) {
		return (int) $existing[0];
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( basename( $file ) );
	copy( $file, $tmp );
	$id = media_handle_sideload(
		[
			'name'     => basename( $file ),
			'tmp_name' => $tmp,
		],
		$post_id
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return $id->get_error_message();
	}
	update_post_meta( (int) $id, '_td_source_file', $rel );
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
			'name'           => $slug,
			'post_type'      => $type,
			'post_status'    => [ 'publish', 'future', 'private', 'draft', 'pending' ],
			'posts_per_page' => 1,
		]
	);
	$post     = $existing ? $existing[0] : null;
	if ( $post && in_array( $post->post_status, [ 'publish', 'future', 'private' ], true ) ) {
		$row['result'] = 'skipped: already ' . $post->post_status . ' (edit it in WordPress)';
		return $row;
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
	}
	// Never publish from here, even if a filter tries (§0.4).
	$data['post_status'] = in_array( $data['post_status'], [ 'draft', 'pending' ], true ) ? $data['post_status'] : 'draft';
	$id                  = $post ? wp_update_post( wp_slash( $data ), true ) : wp_insert_post( wp_slash( $data ), true );
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
	update_post_meta( $id, '_td_import_source', $row['file'] );
	update_post_meta( $id, '_td_confirm_count', $row['confirm'] );

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
