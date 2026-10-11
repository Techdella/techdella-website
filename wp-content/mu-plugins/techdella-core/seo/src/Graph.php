<?php
/**
 * JSON-LD graph (§8.2): one @graph per page, each node once (deduplicated by
 *
 * @id), forbidden types removed. Pure PHP.
 */

namespace TechDella\Core\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * A page's structured-data graph.
 */
final class Graph {

	/**
	 * Types never emitted (§8.2): FAQPage (gov/health only), HowTo
	 * (deprecated), AggregateRating (self-serving reviews are ineligible).
	 */
	public const FORBIDDEN = [ 'FAQPage', 'HowTo', 'AggregateRating' ];

	/**
	 * Nodes keyed by @id (or a generated key when a node has none).
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private array $nodes = [];

	/**
	 * Add a node. A node whose @id already exists is merged into the first
	 * one: keys the first node already has win, missing keys are added.
	 *
	 * @param array<string,mixed> $node Node.
	 */
	public function add( array $node ): void {
		$node = self::clean( $node );
		if ( ! $node || self::is_forbidden( $node ) ) {
			return;
		}
		$key = isset( $node['@id'] ) && is_string( $node['@id'] ) && '' !== $node['@id'] ? $node['@id'] : '_:' . md5( self::to_json( $node ) );
		if ( isset( $this->nodes[ $key ] ) ) {
			$this->nodes[ $key ] = $this->nodes[ $key ] + $node;
			return;
		}
		$this->nodes[ $key ] = $node;
	}

	/**
	 * Add several nodes.
	 *
	 * @param array<int,array<string,mixed>> $nodes Nodes.
	 */
	public function add_all( array $nodes ): void {
		foreach ( $nodes as $node ) {
			if ( is_array( $node ) ) {
				$this->add( $node );
			}
		}
	}

	/**
	 * Whether a node with this @id exists.
	 *
	 * @param string $id Node @id.
	 */
	public function has( string $id ): bool {
		return isset( $this->nodes[ $id ] );
	}

	/**
	 * The nodes, in insertion order.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function nodes(): array {
		return array_values( $this->nodes );
	}

	/**
	 * Empty the graph.
	 */
	public function reset(): void {
		$this->nodes = [];
	}

	/**
	 * Point Review.itemReviewed references that resolve to no node in the
	 * graph at a fallback (the Organization), so a review block on a page
	 * without a Service node still validates.
	 *
	 * @param string $fallback_id Fallback @id.
	 */
	public function resolve_reviews( string $fallback_id ): void {
		foreach ( $this->nodes as $key => $node ) {
			if ( ! self::has_type( $node, 'Review' ) ) {
				continue;
			}
			$ref = $node['itemReviewed']['@id'] ?? null;
			if ( is_array( $node['itemReviewed'] ?? null ) && 1 === count( $node['itemReviewed'] ) && is_string( $ref ) && ! isset( $this->nodes[ $ref ] ) ) {
				$this->nodes[ $key ]['itemReviewed'] = [ '@id' => $fallback_id ];
			}
		}
	}

	/**
	 * The full document: { "@context": "https://schema.org", "@graph": [...] }.
	 *
	 * @param array<int,array<string,mixed>>|null $nodes Nodes (default: this graph's).
	 * @return array<string,mixed>
	 */
	public function document( ?array $nodes = null ): array {
		$list = [];
		foreach ( $nodes ?? $this->nodes() as $node ) {
			$node = self::clean( (array) $node );
			if ( $node && ! self::is_forbidden( $node ) ) {
				$list[] = $node;
			}
		}
		return [
			'@context' => 'https://schema.org',
			'@graph'   => $list,
		];
	}

	/**
	 * JSON for a <script type="application/ld+json"> (safe inside HTML).
	 *
	 * @param array<string,mixed> $document From document().
	 */
	public static function to_json( array $document ): string {
		return (string) json_encode( $document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PARTIAL_OUTPUT_ON_ERROR ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- pure class.
	}

	/**
	 * BreadcrumbList node from breadcrumb items.
	 *
	 * @param array<int,array{label:string,url:string}> $items Trail, home first.
	 * @param string                                    $id    Node @id.
	 * @return array<string,mixed>|null Null when there is no trail (home page).
	 */
	public static function breadcrumb_list( array $items, string $id ): ?array {
		if ( count( $items ) < 2 ) {
			return null;
		}
		$list = [];
		$pos  = 1;
		foreach ( $items as $item ) {
			$entry = [
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => (string) $item['label'],
			];
			if ( '' !== (string) ( $item['url'] ?? '' ) ) {
				$entry['item'] = (string) $item['url'];
			}
			$list[] = $entry;
		}
		return [
			'@type'           => 'BreadcrumbList',
			'@id'             => $id,
			'itemListElement' => $list,
		];
	}

	/**
	 * Whether a node has a type.
	 *
	 * @param array<string,mixed> $node Node.
	 * @param string              $type Type.
	 */
	public static function has_type( array $node, string $type ): bool {
		return in_array( $type, (array) ( $node['@type'] ?? [] ), true );
	}

	/**
	 * Whether a node is of a forbidden type.
	 *
	 * @param array<string,mixed> $node Node.
	 */
	public static function is_forbidden( array $node ): bool {
		return (bool) array_intersect( (array) ( $node['@type'] ?? [] ), self::FORBIDDEN );
	}

	/**
	 * Recursively drop empty values (null, '', []), `aggregateRating`
	 * properties and nested nodes of forbidden types. Never outputs
	 * unfilled placeholders like [URLS].
	 *
	 * @param array<int|string,mixed> $data Node or list.
	 * @return array<int|string,mixed>
	 */
	public static function clean( array $data ): array {
		$is_list = array_is_list( $data );
		$out     = [];
		foreach ( $data as $key => $value ) {
			if ( 'aggregateRating' === $key ) {
				continue;
			}
			if ( is_array( $value ) ) {
				if ( isset( $value['@type'] ) && self::is_forbidden( $value ) ) {
					continue;
				}
				$value = self::clean( $value );
				if ( [] === $value ) {
					continue;
				}
			} elseif ( null === $value || '' === $value || false === $value ) {
				continue;
			} elseif ( is_string( $value ) && preg_match( '/^\[(CONFIRM|PRICE|URLS|YOUR PRICE|MINIMUM SPEND)[^\]]*\]$/', trim( $value ) ) ) {
				continue;
			}
			if ( $is_list ) {
				$out[] = $value;
			} else {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}
}
