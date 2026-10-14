<?php
/**
 * cli: :::article blocks in Markdown.
 */

use PHPUnit\Framework\TestCase;
use TechDella\Core\Cli\Directives;

final class CliDirectivesTest extends TestCase {

	public static function setUpBeforeClass(): void {
		td_cli_unit_load();
	}

	public function test_callout_becomes_a_block_and_leaves_a_placeholder(): void {
		$out = Directives::extract( "Intro.\n\n:::callout tip \"Start here\"\nList the **questions** first.\n:::\n\nOutro." );
		$this->assertSame( [], $out['errors'] );
		$this->assertStringContainsString( Directives::PLACEHOLDER . '0', $out['markdown'] );
		$this->assertStringNotContainsString( ':::', $out['markdown'] );
		$this->assertSame( 'techdella/callout', $out['blocks'][0]['name'] );
		$this->assertSame(
			[
				'variant' => 'tip',
				'title'   => 'Start here',
				'text'    => 'List the **questions** first.',
			],
			$out['blocks'][0]['attrs']
		);
	}

	public function test_stats_require_a_source(): void {
		$ok = Directives::extract( ":::stats \"In numbers\" highlight=1\n50% | of conversions from inbound | TechDella case study | /case-studies/paycape/\n50 days | to first result | TechDella case study\n:::" );
		$this->assertSame( 1, $ok['blocks'][0]['attrs']['highlight'] );
		$this->assertSame( '/case-studies/paycape/', $ok['blocks'][0]['attrs']['items'][0]['url'] );
		$bad = Directives::extract( ":::stats\n73% | of buyers prefer this\n:::" );
		$this->assertCount( 1, $bad['errors'] );
		$this->assertSame( [], $bad['blocks'] );
	}

	public function test_myth_fact_pairs(): void {
		$out  = Directives::extract( ":::myth-fact\nMyth: SEO is dead.\nFact: AI answers lean on pages that rank.\nMyth: More pages win.\nFact: Better pages win.\n:::" );
		$rows = $out['blocks'][0]['attrs']['items'];
		$this->assertCount( 2, $rows );
		$this->assertSame( 'Better pages win.', $rows[1]['fact'] );
	}

	public function test_chart_steps_pros_cons_faq_and_cta(): void {
		$md  = implode(
			"\n",
			[
				':::chart "Where research starts" unit=% max=100 highlight=0',
				'Search | 42',
				'AI assistants | 23',
				'source: Example survey 2026 | https://example.com/s',
				':::',
				':::steps "Run an audit"',
				'1. Pull the data | 90 days of analytics.',
				'2. Map the funnel | Find the leak.',
				':::',
				':::pros-cons "Agency" "In-house"',
				'+ Senior from day one',
				'- Knows the product',
				':::',
				':::faq',
				'### How long does SEO take?',
				'Usually 3 to 6 months.',
				'### Is it worth it?',
				'Yes, for most B2B teams.',
				':::',
				':::cta "Want help?" "Get an audit" /growth-audit/',
				'We rank every fix.',
				':::',
			]
		);
		$out = Directives::extract( $md );
		$this->assertSame( [], $out['errors'] );
		$this->assertSame( [ 'techdella/bar-chart', 'techdella/steps', 'techdella/pros-cons', 'techdella/faq', 'techdella/inline-cta' ], array_column( $out['blocks'], 'name' ) );
		$this->assertSame( 42.0, $out['blocks'][0]['attrs']['items'][0]['value'] );
		$this->assertSame( 'https://example.com/s', $out['blocks'][0]['attrs']['url'] );
		$this->assertSame( 'Find the leak.', $out['blocks'][1]['attrs']['items'][1]['text'] );
		$this->assertSame( 'In-house', $out['blocks'][2]['attrs']['consLabel'] );
		$this->assertSame( 'Usually 3 to 6 months.', $out['blocks'][3]['attrs']['items'][0]['answer'] );
		$this->assertSame( '/growth-audit/', $out['blocks'][4]['attrs']['buttonUrl'] );
	}

	public function test_unclosed_and_unknown_directives_are_errors(): void {
		$this->assertNotEmpty( Directives::extract( ":::callout tip\nNo end" )['errors'] );
		$this->assertNotEmpty( Directives::extract( ":::sparkle\nx\n:::" )['errors'] );
	}
}
