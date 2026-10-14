<?php
/**
 * llms.txt builder (§8.1, §8.3).
 */

namespace TechDella\Tests;

use PHPUnit\Framework\TestCase;
use TechDella\Core\Seo\LlmsTxt;

final class SeoLlmsTxtTest extends TestCase {

	public function test_builds_markdown(): void {
		$txt = LlmsTxt::build(
			[
				'title'    => 'TechDella',
				'summary'  => "B2B growth\n marketing.",
				'facts'    => [
					'Founder' => 'Joanna Okedara-Kalu (Founder & CEO)',
					'Empty'   => '',
				],
				'sections' => [
					'Services' => [
						[
							'title'       => 'SEO',
							'url'         => 'https://techdella.com/services/seo/',
							'description' => '<p>Get found &amp; grow.</p>',
						],
						[
							'title' => 'Duplicate',
							'url'   => 'https://techdella.com/services/seo/',
						],
						[
							'title' => '',
							'url'   => 'https://techdella.com/x/',
						],
					],
					'Empty'    => [],
					'Contact'  => [
						[
							'title' => 'Book a discovery call',
							'url'   => 'https://techdella.com/book-a-discovery-call/',
						],
					],
				],
			]
		);
		$this->assertStringStartsWith( "# TechDella\n\n> B2B growth marketing.\n\n## About\n\n- Founder: Joanna Okedara-Kalu (Founder & CEO)\n", $txt );
		$this->assertStringContainsString( "## Services\n\n- [SEO](https://techdella.com/services/seo/): Get found & grow.\n\n", $txt );
		$this->assertStringNotContainsString( 'Duplicate', $txt );
		$this->assertStringNotContainsString( '## Empty', $txt );
		$this->assertStringNotContainsString( 'Empty:', $txt );
		$this->assertStringEndsWith( "- [Book a discovery call](https://techdella.com/book-a-discovery-call/)\n", $txt );
	}

	public function test_placeholders_never_leak(): void {
		$this->assertSame( 'From', LlmsTxt::line( 'From [PRICE]' ) );
		$this->assertSame( 'Address', LlmsTxt::line( 'Address [CONFIRM addresses]' ) );
	}

	public function test_brackets_in_titles_do_not_break_links(): void {
		$txt = LlmsTxt::build(
			[
				'title'    => 'T',
				'sections' => [
					'S' => [
						[
							'title' => 'A [b] c',
							'url'   => 'https://x.test/a b/',
						],
					],
				],
			]
		);
		$this->assertStringContainsString( '- [A (b) c](https://x.test/a%20b/)', $txt );
	}
}
