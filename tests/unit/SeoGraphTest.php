<?php
/**
 * JSON-LD graph builder (§8.2): dedup by @id, forbidden types, cleaning.
 */

namespace TechDella\Tests;

use PHPUnit\Framework\TestCase;
use TechDella\Core\Seo\Graph;

final class SeoGraphTest extends TestCase {

	private const ORG = 'https://techdella.com/#org';

	public function test_nodes_with_the_same_id_are_emitted_once(): void {
		$graph = new Graph();
		$graph->add( [ '@type' => 'Organization', '@id' => self::ORG, 'name' => 'TechDella' ] );
		$graph->add( [ '@type' => 'Organization', '@id' => self::ORG, 'name' => 'Other', 'url' => 'https://techdella.com/' ] );
		$nodes = $graph->nodes();
		$this->assertCount( 1, $nodes );
		$this->assertSame( 'TechDella', $nodes[0]['name'] ); // First wins.
		$this->assertSame( 'https://techdella.com/', $nodes[0]['url'] ); // Missing keys merged.
	}

	public function test_forbidden_types_are_dropped(): void {
		$graph = new Graph();
		$graph->add( [ '@type' => 'FAQPage', '@id' => 'x#faq' ] );
		$graph->add( [ '@type' => 'HowTo', '@id' => 'x#howto' ] );
		$graph->add( [ '@type' => 'AggregateRating', 'ratingValue' => 5 ] );
		$graph->add(
			[
				'@type'           => 'Service',
				'@id'             => 'x#service',
				'aggregateRating' => [ '@type' => 'AggregateRating', 'ratingValue' => 5 ],
				'subjectOf'       => [ '@type' => 'FAQPage' ],
			]
		);
		$nodes = $graph->nodes();
		$this->assertCount( 1, $nodes );
		$this->assertSame( [ '@type' => 'Service', '@id' => 'x#service' ], $nodes[0] );
		$json = Graph::to_json( $graph->document( [ [ '@type' => 'FAQPage' ], [ '@type' => 'Thing', 'name' => 'ok' ] ] ) );
		$this->assertStringNotContainsString( 'FAQPage', $json );
	}

	public function test_empty_values_and_placeholders_are_removed(): void {
		$clean = Graph::clean(
			[
				'@type'  => 'Organization',
				'sameAs' => [],
				'name'   => 'TechDella',
				'url'    => '',
				'logo'   => null,
				'same'   => '[URLS]',
				'list'   => [ 'a', '', 'b' ],
			]
		);
		$this->assertSame(
			[
				'@type' => 'Organization',
				'name'  => 'TechDella',
				'list'  => [ 'a', 'b' ],
			],
			$clean
		);
	}

	public function test_document_and_json_are_html_safe(): void {
		$graph = new Graph();
		$graph->add( [ '@type' => 'Thing', '@id' => 'a', 'name' => '</script><b>' ] );
		$json = Graph::to_json( $graph->document() );
		$this->assertStringNotContainsString( '</script>', $json );
		$data = json_decode( $json, true );
		$this->assertSame( 'https://schema.org', $data['@context'] );
		$this->assertSame( '</script><b>', $data['@graph'][0]['name'] );
	}

	public function test_dangling_review_points_at_org(): void {
		$graph = new Graph();
		$graph->add( [ '@type' => 'Organization', '@id' => self::ORG ] );
		$graph->add( [ '@type' => 'Review', '@id' => 'p#review', 'itemReviewed' => [ '@id' => 'p#service' ], 'reviewBody' => 'Q' ] );
		$graph->add( [ '@type' => 'Review', '@id' => 'q#review', 'itemReviewed' => [ '@id' => self::ORG ], 'reviewBody' => 'Q' ] );
		$graph->resolve_reviews( self::ORG );
		$this->assertSame( self::ORG, $graph->nodes()[1]['itemReviewed']['@id'] );

		$graph2 = new Graph();
		$graph2->add( [ '@type' => 'Service', '@id' => 'p#service' ] );
		$graph2->add( [ '@type' => 'Review', '@id' => 'p#review', 'itemReviewed' => [ '@id' => 'p#service' ] ] );
		$graph2->resolve_reviews( self::ORG );
		$this->assertSame( 'p#service', $graph2->nodes()[1]['itemReviewed']['@id'] );
	}

	public function test_breadcrumb_list(): void {
		$this->assertNull( Graph::breadcrumb_list( [ [ 'label' => 'Home', 'url' => 'https://techdella.com/' ] ], 'x#breadcrumb' ) );
		$list = Graph::breadcrumb_list(
			[
				[ 'label' => 'Home', 'url' => 'https://techdella.com/' ],
				[ 'label' => 'Services', 'url' => 'https://techdella.com/services/' ],
				[ 'label' => 'SEO', 'url' => '' ],
			],
			'x#breadcrumb'
		);
		$this->assertSame( 'BreadcrumbList', $list['@type'] );
		$this->assertCount( 3, $list['itemListElement'] );
		$this->assertSame( 2, $list['itemListElement'][1]['position'] );
		$this->assertArrayNotHasKey( 'item', $list['itemListElement'][2] );
	}
}
