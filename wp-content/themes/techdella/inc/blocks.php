<?php
/**
 * Blocks (§2.3): every folder in blocks/ with a block.json is a server-rendered
 * block (render.php), registered as techdella/<folder>. The same render.php is
 * used by PHP templates through td_block(), so templates and the editor share
 * one component library.
 *
 * Editing UI: without an ACF Pro licence (D4 [CONFIRM]) blocks get a generic
 * editor (assets/js/editor.js) that builds sidebar controls from each
 * block.json's attributes and previews with ServerSideRender. Attribute hints
 * for the generic editor live under "techdella" in block.json:
 *   "techdella": { "controls": { "intro": "textarea", "image": "media", "items": "json" } }
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block folders.
 *
 * @return array<string,string> name => absolute dir.
 */
function td_block_dirs(): array {
	$dirs = [];
	foreach ( (array) glob( get_theme_file_path( 'blocks/*/block.json' ) ) as $json ) {
		$dirs[ basename( dirname( (string) $json ) ) ] = dirname( (string) $json );
	}
	ksort( $dirs );
	return $dirs;
}

add_action(
	'init',
	static function () {
		$build = get_theme_file_path( 'assets/build/editor.js' );
		if ( is_readable( $build ) ) {
			wp_register_script(
				'techdella-blocks-editor',
				get_theme_file_uri( 'assets/build/editor.js' ),
				[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ],
				td_build_version( 'editor.js' ),
				true
			);
		}
		$editor_meta = [];
		foreach ( td_block_dirs() as $name => $dir ) {
			$type = register_block_type( $dir );
			if ( $type ) {
				$raw                        = (array) json_decode( (string) file_get_contents( $dir . '/block.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$editor_meta[ $type->name ] = [
					'controls' => $raw['techdella']['controls'] ?? new stdClass(),
					'help'     => $raw['techdella']['help'] ?? '',
				];
			}
		}
		if ( is_readable( $build ) ) {
			wp_add_inline_script( 'techdella-blocks-editor', 'window.TD_BLOCKS=' . wp_json_encode( $editor_meta ) . ';', 'before' );
		}
	}
);

// Heading ids for the table of contents must exist whether or not a toc block renders first.
require_once get_theme_file_path( 'blocks/toc/lib.php' );

add_filter(
	'block_categories_all',
	static function ( array $categories ): array {
		array_unshift(
			$categories,
			[
				'slug'  => 'techdella',
				'title' => __( 'TechDella', 'techdella' ),
			]
		);
		return $categories;
	}
);

/**
 * Render a TechDella block from PHP (templates, parts, patterns).
 *
 * @param string              $name       Block folder name, e.g. "hero-orbit".
 * @param array<string,mixed> $attributes Block attributes.
 * @param string              $content    Inner HTML, if the block takes any.
 */
function td_block( string $name, array $attributes = [], string $content = '' ): string {
	return render_block(
		[
			'blockName'    => 'techdella/' . $name,
			'attrs'        => $attributes,
			'innerBlocks'  => [],
			'innerHTML'    => $content,
			'innerContent' => [ $content ],
		]
	);
}

/**
 * Wrapper attributes for a block's root element, working both inside the
 * editor/front-end block render and when called through td_block().
 *
 * @param string              $css_class Base class, e.g. "td-hero-orbit".
 * @param array<string,mixed> $extra Extra attributes (id, style, data-*).
 */
function td_block_wrapper( string $css_class, array $extra = [] ): string {
	if ( class_exists( 'WP_Block_Supports' ) && null !== WP_Block_Supports::$block_to_render ) {
		return get_block_wrapper_attributes( array_merge( [ 'class' => $css_class ], $extra ) );
	}
	$out = 'class="' . esc_attr( $css_class ) . '"';
	foreach ( $extra as $k => $v ) {
		$out .= ' ' . esc_attr( (string) $k ) . '="' . esc_attr( (string) $v ) . '"';
	}
	return $out;
}
