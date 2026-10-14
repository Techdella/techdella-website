<?php
/**
 * Tools: HTML → SEO facts, robots.txt → AI-crawler access, AI tool contracts.
 */

use PHPUnit\Framework\TestCase;
use TechDella\Core\Tools\AiTools;
use TechDella\Core\Tools\PageFacts;

final class ToolsPageFactsTest extends TestCase {

	public static function setUpBeforeClass(): void {
		td_tools_unit_load();
	}

	public function test_html_facts(): void {
		$html  = <<<'HTML'
<!doctype html><html lang="en"><head><meta charset="utf-8">
<title>  B2B SEO services | Example  </title>
<meta name="Description" content="We help B2B teams get found.">
<meta name="robots" content="index,follow">
<meta name="viewport" content="width=device-width">
<link rel="canonical" href="https://example.com/seo/">
<meta property="og:title" content="x">
<script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"Organization"},{"@type":["WebPage","FAQPage"],"breadcrumb":{"@type":"BreadcrumbList"}}]}</script>
<style>.x{}</style></head>
<body><nav><a href="/">Home</a></nav><main>
<h1>B2B SEO <em>services</em></h1><p>We run search for software companies that sell to other businesses.</p>
<h2>Why it works</h2><h3>Detail</h3>
<img src="/a.png"><img src="/b.png" alt=""><img src="/c.png" alt="Chart">
<a href="https://example.com/x">in</a><a href="https://www.example.com/y">in2</a><a href="https://other.com/" rel="nofollow noopener">out</a><a href="#top">skip</a><a href="mailto:a@b.c">mail</a>
<script>var hidden = "words that should not count";</script>
<div itemscope itemtype="https://schema.org/Product"></div>
</main></body></html>
HTML;
		$f     = PageFacts::from_html( $html, 'https://example.com/seo/' );
		$this->assertSame( 'B2B SEO services | Example', $f['title'] );
		$this->assertSame( 'We help B2B teams get found.', $f['meta_description'] );
		$this->assertSame( 'https://example.com/seo/', $f['canonical'] );
		$this->assertSame( 'index,follow', $f['meta_robots'] );
		$this->assertTrue( $f['viewport'] );
		$this->assertTrue( $f['og_title'] );
		$this->assertFalse( $f['og_image'] );
		$this->assertSame( 'en', $f['lang'] );
		$this->assertSame( [ 'B2B SEO services' ], $f['h1'] );
		$this->assertSame( [ 1, 2, 3 ], array_column( $f['headings'], 'level' ) );
		$this->assertSame( 3, $f['images'] );
		$this->assertSame( 1, $f['images_missing_alt'] );
		$this->assertSame( [ '/a.png' ], $f['missing_alt_srcs'] );
		$this->assertSame( 1, $f['images_empty_alt'] );
		$this->assertSame( 3, $f['links_internal'] );
		$this->assertSame( 1, $f['links_external'] );
		$this->assertSame( 1, $f['links_nofollow'] );
		$this->assertEqualsCanonicalizing( [ 'Organization', 'WebPage', 'FAQPage', 'BreadcrumbList', 'Product' ], $f['schema_types'] );
		$this->assertStringStartsWith( 'We run search', $f['first_paragraph'] );
		$this->assertStringNotContainsString( 'should not count', $f['first_words'] );
		$this->assertGreaterThan( 15, $f['word_count'] );
		$this->assertLessThan( 30, $f['word_count'] );
	}

	public function test_broken_html_does_not_throw(): void {
		$f = PageFacts::from_html( '<p>unclosed <b>tags <h1>Title', 'https://x.com/' );
		$this->assertSame( '', $f['title'] );
		$this->assertSame( [ 'Title' ], $f['h1'] );
	}

	public function test_ai_crawler_rules(): void {
		$robots = "# comment\nUser-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\n"
			. "User-agent: GPTBot\nUser-agent: CCBot\nDisallow: /\n\n"
			. "User-agent: Google-Extended\nDisallow: /\nAllow: /blog/\n\n"
			. "User-agent: PerplexityBot\nDisallow:\n";
		$r      = PageFacts::ai_rules( $robots );
		$this->assertSame( 'blocked', $r['GPTBot'] );
		$this->assertSame( 'blocked', $r['CCBot'] );
		$this->assertSame( 'partial', $r['Google-Extended'] );
		$this->assertSame( 'allowed', $r['PerplexityBot'] );
		$this->assertSame( 'allowed', $r['ClaudeBot'] ); // Falls back to "*".
		$this->assertSame( 'blocked', PageFacts::ai_rules( "User-agent: *\nDisallow: /" )['ClaudeBot'] );
		$this->assertSame( 'allowed', PageFacts::ai_rules( '' )['OAI-SearchBot'] );
	}

	public function test_ai_input_sanitising(): void {
		$res = AiTools::sanitize_input( 'ai-business-name-generator', [ 'industry' => ' <b>Fintech</b> ', 'keywords' => 'pay, flow', 'tone' => 'modern' ] );
		$this->assertTrue( $res['ok'] );
		$this->assertSame( 'Fintech', $res['input']['industry'] );

		$bad = AiTools::sanitize_input( 'ai-business-name-generator', [ 'industry' => '', 'keywords' => str_repeat( 'a', 301 ), 'tone' => 'loud' ] );
		$this->assertSame( [ 'industry' => 'required', 'keywords' => 'too_long', 'tone' => 'invalid' ], $bad['errors'] );
		$this->assertFalse( AiTools::sanitize_input( 'nope', [] )['ok'] );
	}

	public function test_ai_request_body_uses_structured_output(): void {
		$body = AiTools::request_body( 'persona-builder', [ 'product' => 'CRM', 'audience' => 'Ops leads' ], '', 4000 );
		$this->assertSame( AiTools::DEFAULT_MODEL, $body['model'] );
		$this->assertSame( 4000, $body['max_tokens'] );
		$this->assertSame( 'json_schema', $body['output_config']['format']['type'] );
		$this->assertFalse( $body['output_config']['format']['schema']['additionalProperties'] );
		$this->assertArrayNotHasKey( 'thinking', $body );
		$this->assertStringContainsString( 'product: CRM', $body['messages'][0]['content'] );
	}

	public function test_ai_response_parsing(): void {
		$names = [];
		for ( $i = 0; $i < 12; $i++ ) {
			$names[] = [
				'name'  => 'Name ' . $i,
				'style' => 0 === $i ? 'weird' : 'invented',
				'why'   => '<b>Short</b> reason.',
			];
		}
		$names[] = [
			'name'  => 'name 1',
			'style' => 'compound',
			'why'   => 'dupe',
		];
		$res = AiTools::parse_response(
			'ai-business-name-generator',
			[
				'stop_reason' => 'end_turn',
				'content'     => [
					[ 'type' => 'thinking', 'thinking' => '' ],
					[ 'type' => 'text', 'text' => wp_json_encode_or_native( [ 'names' => $names ] ) ],
				],
			]
		);
		$this->assertTrue( $res['ok'] );
		$this->assertCount( 10, $res['data']['names'] );
		$this->assertSame( 'other', $res['data']['names'][0]['style'] );
		$this->assertSame( 'Short reason.', $res['data']['names'][0]['why'] );

		$this->assertSame( 'refusal', AiTools::parse_response( 'persona-builder', [ 'stop_reason' => 'refusal' ] )['error'] );
		$this->assertSame( 'truncated', AiTools::parse_response( 'persona-builder', [ 'stop_reason' => 'max_tokens' ] )['error'] );
		$this->assertSame( 'bad_output', AiTools::parse_response( 'persona-builder', [ 'content' => [ [ 'type' => 'text', 'text' => 'not json' ] ] ] )['error'] );
		$this->assertSame( 'bad_output', AiTools::parse_response( 'persona-builder', [ 'content' => [ [ 'type' => 'text', 'text' => '{"persona_name":""}' ] ] ] )['error'] );
	}
}

/**
 * json_encode without WordPress.
 *
 * @param mixed $data Data.
 */
function wp_json_encode_or_native( $data ): string {
	return (string) json_encode( $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
}
