<?php
/**
 * /llms.txt builder (§8.1, §8.3): a plain-Markdown summary of who we are,
 * the services with URLs, key guides and contact. Pure PHP; llms.php feeds
 * it data from the CMS.
 */

namespace TechDella\Core\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * The llms.txt content.
 */
final class LlmsTxt {

	/**
	 * Build the file.
	 *
	 * Data keys:
	 *  title     string  H1 (site name)
	 *  summary   string  one-paragraph blockquote
	 *  facts     array<string,string>  "Label" => "value" entity facts
	 *  sections  array<string,array<int,array{title:string,url:string,description?:string}>>
	 *  notes     string[] plain lines appended at the end (optional)
	 *
	 * @param array<string,mixed> $data Content.
	 */
	public static function build( array $data ): string {
		$out     = [];
		$out[]   = '# ' . self::line( (string) ( $data['title'] ?? 'TechDella' ) );
		$out[]   = '';
		$summary = self::line( (string) ( $data['summary'] ?? '' ) );
		if ( '' !== $summary ) {
			$out[] = '> ' . $summary;
			$out[] = '';
		}
		$facts = (array) ( $data['facts'] ?? [] );
		if ( $facts ) {
			$out[] = '## About';
			$out[] = '';
			foreach ( $facts as $label => $value ) {
				$value = self::line( (string) $value );
				if ( '' !== $value ) {
					$out[] = '- ' . self::line( (string) $label ) . ': ' . $value;
				}
			}
			$out[] = '';
		}
		foreach ( (array) ( $data['sections'] ?? [] ) as $heading => $items ) {
			$lines = [];
			$seen  = [];
			foreach ( (array) $items as $item ) {
				$title = self::line( (string) ( $item['title'] ?? '' ) );
				$url   = trim( (string) ( $item['url'] ?? '' ) );
				if ( '' === $title || '' === $url || isset( $seen[ $url ] ) ) {
					continue;
				}
				$seen[ $url ] = true;
				$desc         = self::line( (string) ( $item['description'] ?? '' ) );
				$lines[]      = '- [' . str_replace( [ '[', ']' ], [ '(', ')' ], $title ) . '](' . str_replace( [ ' ', ')' ], [ '%20', '%29' ], $url ) . ')' . ( '' !== $desc ? ': ' . $desc : '' );
			}
			if ( ! $lines ) {
				continue;
			}
			$out[] = '## ' . self::line( (string) $heading );
			$out[] = '';
			array_push( $out, ...$lines );
			$out[] = '';
		}
		foreach ( (array) ( $data['notes'] ?? [] ) as $note ) {
			$out[] = self::line( (string) $note );
		}
		return rtrim( implode( "\n", $out ) ) . "\n";
	}

	/**
	 * Collapse whitespace and strip tags/Markdown-breaking newlines; drop
	 * editorial placeholders ([CONFIRM], [PRICE] …) so they never leak.
	 *
	 * @param string $text Text.
	 */
	public static function line( string $text ): string {
		$text = html_entity_decode( strip_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- pure class.
		$text = (string) preg_replace( '/\[(CONFIRM|PRICE|YOUR PRICE|MINIMUM SPEND|URLS|PHOTO)[^\]]*\]/', '', $text );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}
}
