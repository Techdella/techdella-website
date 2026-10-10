<?php
/**
 * Shared helpers used by the core plugin and the theme.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Which of the three sites this install is (D3): marketing|journal|studios.
 */
function td_site(): string {
	$site = (string) TD_SITE;
	return in_array( $site, [ 'marketing', 'journal', 'studios' ], true ) ? $site : 'marketing';
}

/**
 * Whether this install is one of the given sites.
 *
 * @param string ...$sites Site keys.
 */
function td_is_site( string ...$sites ): bool {
	return in_array( td_site(), $sites, true );
}

/**
 * Deployment environment: local|staging|production.
 */
function td_environment(): string {
	$env = (string) TD_ENV;
	return in_array( $env, [ 'local', 'staging', 'production' ], true ) ? $env : 'production';
}

/**
 * True only on the live sites.
 */
function td_is_production(): bool {
	return 'production' === td_environment();
}

/**
 * Production domain per site.
 *
 * @return array<string,string>
 */
function td_site_domains(): array {
	return [
		'marketing' => 'https://techdella.com',
		'journal'   => 'https://blog.techdella.com',
		'studios'   => 'https://studios.techdella.com',
	];
}

/**
 * Absolute URL on any of the three sites (§4.1). Never hard-code domains in
 * templates: in dev this resolves to the localhost ports, in production to
 * the real domains.
 *
 * @param string $site marketing|journal|studios.
 * @param string $path Path starting with "/".
 */
function td_url( string $site = 'marketing', string $path = '/' ): string {
	$path = '/' . ltrim( $path, '/' );
	if ( $site === td_site() ) {
		return home_url( $path );
	}
	$override = [
		'marketing' => TD_URL_MARKETING,
		'journal'   => TD_URL_JOURNAL,
		'studios'   => TD_URL_STUDIOS,
	][ $site ] ?? '';
	$domains = td_site_domains();
	$base    = '' !== $override ? $override : ( $domains[ $site ] ?? $domains['marketing'] );
	return untrailingslashit( $base ) . $path;
}

/**
 * Read a content field: ACF when available, else post meta (fields are also
 * registered as post meta so they work without ACF and over REST/WP-CLI).
 *
 * @param string   $name    Field name.
 * @param int|null $post_id Post ID (defaults to the current post).
 * @return mixed
 */
function td_field( string $name, ?int $post_id = null ) {
	$post_id = $post_id ?? get_the_ID();
	if ( ! $post_id ) {
		return null;
	}
	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $name, $post_id );
		if ( null !== $value && false !== $value && '' !== $value ) {
			return $value;
		}
	}
	$value = get_post_meta( (int) $post_id, $name, true );
	return '' === $value ? null : $value;
}

/**
 * Render a headline with its one serif-italic accent word (§2.2).
 * Marks the accent with *asterisks*: "Get found. Get noticed. *Grow.*".
 * Output is escaped; only the <em> is added.
 *
 * @param string $text Headline text.
 */
function td_accent( string $text ): string {
	$escaped = esc_html( $text );
	return (string) preg_replace( '/\*([^*]+)\*/', '<em class="td-accent">$1</em>', $escaped, 1 );
}

/**
 * Wrap [CONFIRM]/[PRICE]/[MINIMUM SPEND] tokens so they are visible on
 * staging and findable by `wp td audit placeholders` (§0.5, §16).
 *
 * @param string $html Already-escaped HTML.
 */
function td_mark_placeholders( string $html ): string {
	return (string) preg_replace(
		'/\[(CONFIRM|PRICE|YOUR PRICE|MINIMUM SPEND|URLS|PHOTO)([^\]]*)\]/',
		'<span class="td-confirm">[$1$2]</span>',
		$html
	);
}

/**
 * Inline SVG icon from the theme's assets/svg/ directory (aria-hidden).
 *
 * @param string $name  Icon file name without extension.
 * @param string $class Extra class.
 */
function td_icon( string $name, string $class = '' ): string {
	static $cache = [];
	$name = sanitize_file_name( $name );
	if ( ! isset( $cache[ $name ] ) ) {
		$file           = get_theme_file_path( "assets/svg/{$name}.svg" );
		$cache[ $name ] = is_readable( $file ) ? trim( (string) file_get_contents( $file ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
	if ( '' === $cache[ $name ] ) {
		return '';
	}
	$attrs = ' aria-hidden="true" focusable="false"' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' );
	return (string) preg_replace( '/<svg\b/', '<svg' . $attrs, $cache[ $name ], 1 );
}
