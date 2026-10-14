<?php
/**
 * Pre-publish checklist rules (§10.1).
 */

namespace TechDella\Tests;

use PHPUnit\Framework\TestCase;
use TechDella\Core\Admin\Checklist;

final class AdminChecklistTest extends TestCase {

	private const HOSTS = [ 'techdella.com', 'blog.techdella.com', 'localhost' ];

	public function test_counts_relative_and_same_site_links(): void {
		$html = '<p><a href="/services/seo/">SEO</a> <a href="https://techdella.com/glossary/aeo/">AEO</a>'
			. ' <a href="https://www.techdella.com/case-studies/paycape/">Paycape</a> <a href="//blog.techdella.com/post/">Post</a></p>';
		$this->assertSame( 4, Checklist::internal_links( $html, self::HOSTS ) );
	}

	public function test_ignores_external_anchor_mailto_tel(): void {
		$html = '<a href="https://google.com/">G</a><a href="#faq">FAQ</a><a href="mailto:a@b.c">M</a>'
			. '<a href="tel:+234">T</a><a href="javascript:void(0)">J</a><a href="https://techdella.com.evil.io/">X</a>';
		$this->assertSame( 0, Checklist::internal_links( $html, self::HOSTS ) );
	}

	public function test_duplicate_targets_count_once_and_ports_are_ignored(): void {
		$html = '<a href="/services/seo/">a</a><a href="/services/seo/#faq">b</a><a href="http://localhost:8081/x/">c</a>';
		$this->assertSame( 2, Checklist::internal_links( $html, self::HOSTS ) );
	}

	public function test_single_quoted_href_and_relative_path(): void {
		$this->assertSame( 1, Checklist::internal_links( "<a class='x' href='pricing/'>p</a>", self::HOSTS ) );
	}

	public function test_alt_text_detection(): void {
		$html = '<img src="a.jpg" alt="Team at the whiteboard"><img src="b.jpg" alt=""><img src="c.jpg"><img alt="  " src="d.jpg">';
		$this->assertSame( 3, Checklist::images_missing_alt( $html ) );
		$this->assertSame( 0, Checklist::images_missing_alt( '<p>No images</p>' ) );
		$this->assertSame( 0, Checklist::images_missing_alt( "<img alt='Chart of clicks' src='x'>" ) );
	}

	public function test_placeholder_detection(): void {
		$this->assertSame( [ '[CONFIRM]', '[PRICE]' ], Checklist::placeholders( 'From [CONFIRM] at [PRICE] a month, [CONFIRM] again' ) );
		$this->assertSame( [ '[CONFIRM industries]' ], Checklist::placeholders( 'Industries [CONFIRM industries]' ) );
		$this->assertSame( [ '[MINIMUM SPEND]' ], Checklist::placeholders( 'Ads from [MINIMUM SPEND]' ) );
		$this->assertSame( [], Checklist::placeholders( 'Confirmed [link] text' ) );
	}

	public function test_keyword_in_intro_uses_first_non_empty_paragraph(): void {
		$html = "<!-- wp:techdella/tldr /-->\n<p> </p><p>Our <strong>B2B SEO</strong> services turn search into pipeline.</p><p>Technical SEO later.</p>";
		$this->assertTrue( Checklist::keyword_in_intro( $html, 'b2b seo' ) );
		$this->assertFalse( Checklist::keyword_in_intro( $html, 'technical seo' ) );
		$this->assertFalse( Checklist::keyword_in_intro( '<h2>B2B SEO</h2>', 'b2b seo' ) );
	}

	public function test_keyword_match_is_case_punctuation_and_boundary_aware(): void {
		$this->assertTrue( Checklist::contains_keyword( 'What is AI-search optimization?', 'AI search optimization' ) );
		$this->assertTrue( Checklist::contains_keyword( 'Founders&#8217; guide to SEO', 'founders guide' ) );
		$this->assertFalse( Checklist::contains_keyword( 'b2bseo tips', 'b2b seo' ) );
		$this->assertFalse( Checklist::contains_keyword( 'SEOs', 'seo' ) );
		$this->assertFalse( Checklist::contains_keyword( 'anything', '' ) );
	}

	public function test_run_reports_every_blocking_failure(): void {
		$results  = Checklist::run(
			[
				'title'      => 'Untitled',
				'content'    => '<p>Hello [CONFIRM]</p><img src="x.jpg">',
				'primary_kw' => 'b2b seo',
				'hosts'      => self::HOSTS,
			]
		);
		$failures = Checklist::failures( $results );
		$this->assertSame( [ 'meta_description', 'keyword_title', 'keyword_intro', 'internal_links', 'image_alt', 'placeholders' ], array_keys( $failures ) );
		$this->assertFalse( $results['meta_length']['blocking'] );
	}

	public function test_run_passes_a_good_page_and_h1_can_come_from_hero_title(): void {
		$content = '<p>Our b2b seo work starts with <a href="/services/seo/">SEO</a>, <a href="/glossary/aeo/">AEO</a>'
			. ' and <a href="https://blog.techdella.com/topic-clusters/">topic clusters</a>.</p><img src="a.jpg" alt="Search console chart">';
		$results = Checklist::run(
			[
				'title'            => 'SEO',
				'h1'               => [ 'B2B SEO that turns search into pipeline' ],
				'content'          => $content,
				'primary_kw'       => 'B2B SEO',
				'meta_description' => str_repeat( 'a', 150 ),
				'hosts'            => self::HOSTS,
			]
		);
		$this->assertSame( [], Checklist::failures( $results ) );
		$this->assertTrue( $results['meta_length']['ok'] );
	}

	public function test_normalise_host(): void {
		$this->assertSame( 'techdella.com', Checklist::normalise_host( 'https://www.TechDella.com/path' ) );
		$this->assertSame( 'localhost', Checklist::normalise_host( 'localhost:8081' ) );
	}
}
