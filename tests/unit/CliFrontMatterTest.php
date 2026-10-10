<?php
/**
 * cli: YAML front-matter parser.
 */

use PHPUnit\Framework\TestCase;
use TechDella\Core\Cli\FrontMatter;

final class CliFrontMatterTest extends TestCase {

	public static function setUpBeforeClass(): void {
		td_cli_unit_load();
	}

	public function test_splits_meta_and_body(): void {
		$doc = "---\ntitle: \"What is AI search optimization?\"\nslug: what-is-ai-search-optimization\n---\n\n# Hello\n\nBody [CONFIRM] text.\n";
		$out = FrontMatter::parse( $doc );
		$this->assertSame( 'What is AI search optimization?', $out['meta']['title'] );
		$this->assertSame( 'what-is-ai-search-optimization', $out['meta']['slug'] );
		$this->assertSame( "# Hello\n\nBody [CONFIRM] text.\n", $out['body'] );
	}

	public function test_no_front_matter_returns_body(): void {
		$out = FrontMatter::parse( "# Just markdown\n" );
		$this->assertSame( [], $out['meta'] );
		$this->assertSame( "# Just markdown\n", $out['body'] );
	}

	public function test_scalars_quotes_and_comments(): void {
		$meta = FrontMatter::parse_yaml(
			"a: plain text # comment\nb: 'it''s quoted # not a comment'\nc: \"line\\nbreak\"\nd: 42\ne: 1.5\nf: true\ng: no\nh: ~\ni:\n# full comment\nj: https://example.com/#anchor"
		);
		$this->assertSame( 'plain text', $meta['a'] );
		$this->assertSame( "it's quoted # not a comment", $meta['b'] );
		$this->assertSame( "line\nbreak", $meta['c'] );
		$this->assertSame( 42, $meta['d'] );
		$this->assertSame( 1.5, $meta['e'] );
		$this->assertTrue( $meta['f'] );
		$this->assertFalse( $meta['g'] );
		$this->assertNull( $meta['h'] );
		$this->assertNull( $meta['i'] );
		$this->assertSame( 'https://example.com/#anchor', $meta['j'] );
	}

	public function test_lists_inline_and_block(): void {
		$meta = FrontMatter::parse_yaml( "tags: [seo, \"ai, search\", 'geo']\nsecondary_kws:\n  - aeo\n  - generative engine optimization\nempty: []\nflat:\n- one\n- two" );
		$this->assertSame( [ 'seo', 'ai, search', 'geo' ], $meta['tags'] );
		$this->assertSame( [ 'aeo', 'generative engine optimization' ], $meta['secondary_kws'] );
		$this->assertSame( [], $meta['empty'] );
		$this->assertSame( [ 'one', 'two' ], $meta['flat'] );
	}

	public function test_nested_map_one_level_with_list(): void {
		$meta = FrontMatter::parse_yaml( "taxonomies:\n  category: [ai-search]\n  td_service_tag:\n    - ai-search-optimization\n    - seo\n  note: \"x: y\"\nafter: ok" );
		$this->assertSame( [ 'ai-search' ], $meta['taxonomies']['category'] );
		$this->assertSame( [ 'ai-search-optimization', 'seo' ], $meta['taxonomies']['td_service_tag'] );
		$this->assertSame( 'x: y', $meta['taxonomies']['note'] );
		$this->assertSame( 'ok', $meta['after'] );
	}

	public function test_block_scalars(): void {
		$meta = FrontMatter::parse_yaml( "angle: >\n  We run these checks\n  every month.\nnotes: |\n  one\n  two" );
		$this->assertSame( 'We run these checks every month.', $meta['angle'] );
		$this->assertSame( "one\ntwo", $meta['notes'] );
	}

	public function test_rejects_deep_nesting_and_garbage(): void {
		$this->expectException( InvalidArgumentException::class );
		FrontMatter::parse_yaml( "a:\n  b:\n    c: d" );
	}

	public function test_rejects_unreadable_line(): void {
		$this->expectException( InvalidArgumentException::class );
		FrontMatter::parse_yaml( "just words without colon" );
	}

	public function test_rejects_inline_maps(): void {
		$this->expectException( InvalidArgumentException::class );
		FrontMatter::parse_yaml( 'a: {b: c}' );
	}

	public function test_counts_confirm_tokens(): void {
		$this->assertSame( 3, FrontMatter::count_confirm( 'x [CONFIRM] y [CONFIRM: source] z [CONFIRM — owner] [PRICE]' ) );
	}
}
