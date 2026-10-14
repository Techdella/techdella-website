<?php
/**
 * Article components in Markdown (content/README.md → "Article blocks"):
 *
 *     :::callout tip "Start with the questions"
 *     Text with **bold** and [links](/path/).
 *     :::
 *
 * extract() pulls each `:::name args` … `:::` section out of the Markdown, leaving a placeholder
 * paragraph, and returns the block name and attributes it maps to. The importer swaps each
 * placeholder for the serialized techdella/* block after the Markdown is converted.
 *
 * @package techdella-core
 */

namespace TechDella\Core\Cli;

/**
 * Markdown directive parser (pure; unit-tested).
 */
final class Directives {

	public const PLACEHOLDER = 'TDDIRECTIVE';

	/**
	 * Pull directives out of Markdown.
	 *
	 * @param string $markdown Markdown body.
	 * @return array{markdown: string, blocks: array<int,array{name:string,attrs:array<string,mixed>}>, errors: string[]}
	 */
	public static function extract( string $markdown ): array {
		$lines  = explode( "\n", str_replace( "\r", '', $markdown ) );
		$out    = [];
		$blocks = [];
		$errors = [];
		$count  = count( $lines );
		for ( $i = 0; $i < $count; $i++ ) {
			if ( ! preg_match( '/^:::([a-z][a-z-]*)\s*(.*)$/', $lines[ $i ], $m ) ) {
				$out[] = $lines[ $i ];
				continue;
			}
			$body = [];
			$end  = $i + 1;
			while ( $end < $count && ! preg_match( '/^:::\s*$/', $lines[ $end ] ) ) {
				$body[] = $lines[ $end ];
				++$end;
			}
			if ( $end >= $count ) {
				$errors[] = 'unclosed :::' . $m[1] . ' (line ' . ( $i + 1 ) . ')';
				$out[]    = $lines[ $i ];
				continue;
			}
			$block = self::build( $m[1], self::args( $m[2] ), trim( implode( "\n", $body ) ) );
			if ( is_string( $block ) ) {
				$errors[] = $block . ' (line ' . ( $i + 1 ) . ')';
			} else {
				$out[]    = '';
				$out[]    = self::PLACEHOLDER . count( $blocks );
				$out[]    = '';
				$blocks[] = $block;
			}
			$i = $end;
		}
		return [
			'markdown' => implode( "\n", $out ),
			'blocks'   => $blocks,
			'errors'   => $errors,
		];
	}

	/**
	 * Parse `word "quoted text" key=value` arguments.
	 *
	 * @param string $raw Argument string.
	 * @return array{words: string[], quoted: string[], keys: array<string,string>}
	 */
	public static function args( string $raw ): array {
		$out = [
			'words'  => [],
			'quoted' => [],
			'keys'   => [],
		];
		preg_match_all( '/(\w+)=("([^"]*)"|\S+)|"([^"]*)"|(\S+)/u', $raw, $m, PREG_SET_ORDER );
		foreach ( $m as $t ) {
			if ( '' !== ( $t[1] ?? '' ) ) {
				$out['keys'][ strtolower( $t[1] ) ] = '' !== ( $t[3] ?? '' ) ? $t[3] : trim( $t[2], '"' );
			} elseif ( isset( $t[4] ) && '' !== $t[4] ) {
				$out['quoted'][] = $t[4];
			} elseif ( isset( $t[5] ) ) {
				$out['words'][] = $t[5];
			}
		}
		return $out;
	}

	/**
	 * Map one directive to a block.
	 *
	 * @param string                                                               $name Directive name.
	 * @param array{words: string[], quoted: string[], keys: array<string,string>} $a    Arguments.
	 * @param string                                                               $body Body text.
	 * @return array{name:string,attrs:array<string,mixed>}|string Block, or an error message.
	 */
	public static function build( string $name, array $a, string $body ) {
		$title = $a['quoted'][0] ?? ( $a['keys']['title'] ?? '' );
		$lines = array_values( array_filter( array_map( 'trim', explode( "\n", $body ) ), static fn( $l ) => '' !== $l ) );
		$items = static fn() => array_values( array_map( static fn( $l ) => (string) preg_replace( '/^([-*+]|\d+[.)])\s+/', '', $l ), $lines ) );
		$split = static fn( string $l ) => array_map( 'trim', explode( '|', $l ) );
		switch ( $name ) {
			case 'callout':
				$variant = $a['words'][0] ?? 'note';
				if ( ! in_array( $variant, [ 'note', 'tip', 'warning', 'key' ], true ) ) {
					return 'callout: variant must be note, tip, warning or key';
				}
				return self::block(
					'callout',
					[
						'variant' => $variant,
						'title'   => $title,
						'text'    => $body,
					]
				);
			case 'tldr':
				return self::block(
					'tldr',
					[
						'label' => '' !== $title ? $title : 'The short answer',
						'text'  => $body,
					]
				);
			case 'takeaways':
				return self::block(
					'takeaways',
					[
						'title' => '' !== $title ? $title : 'Key takeaways',
						'items' => $items(),
					]
				);
			case 'checklist':
				return self::block(
					'checklist',
					[
						'title' => $title,
						'items' => $items(),
					]
				);
			case 'steps':
				$rows = [];
				foreach ( $items() as $l ) {
					$p      = $split( $l );
					$rows[] = [
						'title' => $p[0],
						'text'  => $p[1] ?? '',
					];
				}
				return self::block(
					'steps',
					[
						'title' => $title,
						'items' => $rows,
					]
				);
			case 'stats':
				$rows = [];
				foreach ( $items() as $l ) {
					$p = $split( $l );
					if ( count( $p ) < 3 || '' === $p[2] ) {
						return 'stats: each line needs value | label | source | url (a source is required)';
					}
					$rows[] = [
						'value'  => $p[0],
						'label'  => $p[1],
						'source' => $p[2],
						'url'    => $p[3] ?? '',
					];
				}
				return self::block(
					'stats',
					[
						'title'     => $title,
						'items'     => $rows,
						'highlight' => isset( $a['keys']['highlight'] ) ? (int) $a['keys']['highlight'] : 0,
					]
				);
			case 'myth-fact':
				$rows = [];
				foreach ( $lines as $l ) {
					if ( preg_match( '/^myth:\s*(.+)$/i', $l, $mm ) ) {
						$rows[] = [
							'myth' => $mm[1],
							'fact' => '',
						];
					} elseif ( preg_match( '/^fact:\s*(.+)$/i', $l, $mm ) && $rows ) {
						$rows[ count( $rows ) - 1 ]['fact'] = $mm[1];
					}
				}
				if ( ! $rows || in_array( '', array_column( $rows, 'fact' ), true ) ) {
					return 'myth-fact: write "Myth: …" then "Fact: …" lines';
				}
				return self::block(
					'myth-fact',
					[
						'title' => '' !== $title ? $title : 'Myth vs fact',
						'items' => $rows,
					]
				);
			case 'pros-cons':
				$pros = [];
				$cons = [];
				foreach ( $lines as $l ) {
					if ( str_starts_with( $l, '+ ' ) ) {
						$pros[] = substr( $l, 2 );
					} elseif ( str_starts_with( $l, '- ' ) ) {
						$cons[] = substr( $l, 2 );
					}
				}
				return self::block(
					'pros-cons',
					[
						'title'     => $a['keys']['title'] ?? '',
						'prosLabel' => $a['quoted'][0] ?? 'Pros',
						'consLabel' => $a['quoted'][1] ?? 'Cons',
						'pros'      => $pros,
						'cons'      => $cons,
					]
				);
			case 'quote':
				return self::block(
					'pull-quote',
					[
						'quote' => $body,
						'name'  => $a['quoted'][0] ?? '',
						'role'  => $a['quoted'][1] ?? '',
						'url'   => $a['keys']['url'] ?? '',
					]
				);
			case 'chart':
				$rows   = [];
				$source = '';
				$url    = '';
				foreach ( $lines as $l ) {
					if ( preg_match( '/^source:\s*(.+)$/i', $l, $mm ) ) {
						$p      = $split( $mm[1] );
						$source = $p[0];
						$url    = $p[1] ?? '';
						continue;
					}
					$p = $split( (string) preg_replace( '/^[-*]\s+/', '', $l ) );
					if ( 2 === count( $p ) && is_numeric( str_replace( [ ',', '%', '$' ], '', $p[1] ) ) ) {
						$rows[] = [
							'label' => $p[0],
							'value' => (float) str_replace( [ ',', '%', '$' ], '', $p[1] ),
						];
					}
				}
				if ( ! $rows || '' === $source ) {
					return 'chart: needs "Label | value" lines and a "source: Name | url" line';
				}
				return self::block(
					'bar-chart',
					[
						'title'     => $title,
						'unit'      => $a['keys']['unit'] ?? '',
						'max'       => (float) ( $a['keys']['max'] ?? 0 ),
						'highlight' => isset( $a['keys']['highlight'] ) ? (int) $a['keys']['highlight'] : -1,
						'items'     => $rows,
						'source'    => $source,
						'url'       => $url,
					]
				);
			case 'cta':
				$url = '';
				foreach ( $a['words'] as $w ) {
					if ( preg_match( '#^(/|https?://|(marketing|journal|studios):/)#', $w ) ) {
						$url = $w;
					}
				}
				if ( '' === $title || '' === $url ) {
					return 'cta: needs "Title" "Button label" /url/';
				}
				return self::block(
					'inline-cta',
					[
						'title'       => $title,
						'buttonLabel' => $a['quoted'][1] ?? 'Talk to us',
						'buttonUrl'   => $url,
						'text'        => $body,
					]
				);
			case 'faq':
				$rows = [];
				foreach ( explode( "\n", $body ) as $l ) {
					if ( preg_match( '/^#{2,4}\s+(.+)$/', trim( $l ), $mm ) ) {
						$rows[] = [
							'question' => trim( $mm[1] ),
							'answer'   => '',
						];
					} elseif ( $rows ) {
						$rows[ count( $rows ) - 1 ]['answer'] .= $l . "\n";
					}
				}
				$rows = array_map(
					static fn( $r ) => [
						'question' => $r['question'],
						'answer'   => trim( $r['answer'] ),
					],
					$rows
				);
				if ( ! $rows ) {
					return 'faq: write each question as a "### Question" line followed by its answer';
				}
				return self::block(
					'faq',
					[
						'title'      => '' !== $title ? $title : 'Frequently asked *questions*',
						'items'      => $rows,
						'background' => 'paper',
					]
				);
		}
		return 'unknown directive :::' . $name;
	}

	/**
	 * A block entry.
	 *
	 * @param string              $name  Block name without namespace.
	 * @param array<string,mixed> $attrs Attributes.
	 * @return array{name:string,attrs:array<string,mixed>}
	 */
	private static function block( string $name, array $attrs ): array {
		return [
			'name'  => 'techdella/' . $name,
			'attrs' => array_filter( $attrs, static fn( $v ) => '' !== $v && [] !== $v ),
		];
	}
}
