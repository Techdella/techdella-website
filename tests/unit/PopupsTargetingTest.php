<?php
/**
 * Popups: normalisation and targeting rules (§13.1).
 */

use PHPUnit\Framework\TestCase;
use TechDella\Core\Popups\Targeting;

final class PopupsTargetingTest extends TestCase {

	public static function setUpBeforeClass(): void {
		td_popups_unit_load();
	}

	private static function ctx( array $over = [] ): array {
		return array_merge(
			[
				'site'       => 'marketing',
				'path'       => '/services/seo/',
				'post_type'  => 'service',
				'categories' => [],
			],
			$over
		);
	}

	public function test_defaults_per_site(): void {
		$this->assertSame( [ 'p1', 'p2' ], array_column( Targeting::defaults( 'journal' ), 'id' ) );
		$this->assertSame( [ 'p3' ], array_column( Targeting::defaults( 'marketing' ), 'id' ) );
		$this->assertSame( [ 'p4' ], array_column( Targeting::defaults( 'studios' ), 'id' ) );
		$p3 = Targeting::defaults( 'marketing' )[0];
		$this->assertSame( 'exit', $p3['type'] );
		$this->assertSame( 'popup_exit', $p3['form_key'] );
		$this->assertSame( 'b2b-growth-audit-worksheet', $p3['resource'] );
		$this->assertSame( 7, $p3['frequency_days'] );
	}

	public function test_normalize_clamps_and_cleans(): void {
		$p = Targeting::normalize(
			[
				'id'            => 'My Popup!',
				'type'          => 'exit',
				'trigger'       => 'scroll',
				'form_key'      => 'contact',
				'include'       => "/a/*\nhttps://techdella.com/b/<script>",
				'variants'      => [ [ 'title' => 'A' ], [ 'title' => 'B' ], [ 'title' => 'C' ], [ 'title' => '' ] ],
				'frequency_days'=> 0,
			]
		);
		$this->assertSame( 'mypopup', $p['id'] );
		$this->assertSame( 'exit', $p['trigger'] );
		$this->assertSame( 'newsletter', $p['form_key'] );
		$this->assertSame( [ '/a/*', '/b/script' ], $p['include'] );
		$this->assertCount( 2, $p['variants'] );
		$this->assertSame( 7, $p['frequency_days'] );
		$this->assertFalse( $p['enabled'] );
		$s = Targeting::normalize( [ 'type' => 'slidein', 'trigger' => 'exit', 'trigger_value' => 500 ] );
		$this->assertSame( 'scroll', $s['trigger'] );
		$this->assertSame( 100, $s['trigger_value'] );
	}

	public function test_p3_on_service_pages_only(): void {
		$p3 = Targeting::defaults( 'marketing' );
		$this->assertCount( 1, Targeting::for_context( $p3, self::ctx() ) );
		$this->assertCount( 0, Targeting::for_context( $p3, self::ctx( [ 'post_type' => 'page', 'path' => '/about-us/' ] ) ) );
		$this->assertCount( 0, Targeting::for_context( $p3, self::ctx( [ 'site' => 'journal' ] ) ) );
	}

	public function test_journal_categories_split_p1_p2(): void {
		$popups = Targeting::defaults( 'journal' );
		$ai     = Targeting::for_context( $popups, self::ctx( [ 'site' => 'journal', 'post_type' => 'post', 'path' => '/x/', 'categories' => [ 'ai-search' ] ] ) );
		$other  = Targeting::for_context( $popups, self::ctx( [ 'site' => 'journal', 'post_type' => 'post', 'path' => '/y/', 'categories' => [ 'paid' ] ] ) );
		$page   = Targeting::for_context( $popups, self::ctx( [ 'site' => 'journal', 'post_type' => 'page', 'path' => '/newsletter/' ] ) );
		$this->assertSame( [ 'p1' ], array_column( $ai, 'id' ) );
		$this->assertSame( [ 'p2' ], array_column( $other, 'id' ) );
		$this->assertSame( [], $page );
	}

	public function test_blocked_pages(): void {
		foreach ( [ '/book-a-discovery-call/', '/growth-audit/', '/start/', '/thank-you/audit/', '/growth-audit' ] as $path ) {
			$this->assertTrue( Targeting::is_blocked_page( $path ), $path );
		}
		$this->assertTrue( Targeting::is_blocked_page( '/whatever/', 'thank-you' ) );
		$this->assertFalse( Targeting::is_blocked_page( '/growth-audit-tips/' ) );
		$this->assertFalse( Targeting::is_blocked_page( '/services/start/' ) );
		$any = Targeting::normalize( [ 'enabled' => true, 'site' => 'studios', 'variants' => [ [ 'title' => 'x' ] ] ] );
		$this->assertFalse( Targeting::matches( $any, self::ctx( [ 'site' => 'studios', 'path' => '/start/' ] ) ) );
		$this->assertTrue( Targeting::matches( $any, self::ctx( [ 'site' => 'studios', 'path' => '/work/' ] ) ) );
	}

	public function test_include_exclude_globs_never_substring(): void {
		$this->assertTrue( Targeting::glob_match( '/services/*', '/services/seo/' ) );
		$this->assertTrue( Targeting::glob_match( '/services/*', '/services' ) );
		$this->assertFalse( Targeting::glob_match( '/services/*', '/services-old/' ) );
		$this->assertFalse( Targeting::glob_match( '/seo/', '/services/seo/' ) );
		$this->assertTrue( Targeting::glob_match( '/seo', '/SEO/?x=1' ) );
		$p = Targeting::normalize(
			[
				'enabled'  => true,
				'site'     => 'marketing',
				'include'  => [ '/services/*' ],
				'exclude'  => [ '/services/paid-ads/' ],
				'variants' => [ [ 'title' => 'x' ] ],
			]
		);
		$this->assertTrue( Targeting::matches( $p, self::ctx() ) );
		$this->assertFalse( Targeting::matches( $p, self::ctx( [ 'path' => '/services/paid-ads/' ] ) ) );
		$this->assertFalse( Targeting::matches( $p, self::ctx( [ 'path' => '/pricing/' ] ) ) );
	}

	public function test_disabled_or_empty_variants_never_match(): void {
		$p = Targeting::defaults( 'marketing' )[0];
		$this->assertFalse( Targeting::matches( array_merge( $p, [ 'enabled' => false ] ), self::ctx() ) );
		$this->assertFalse( Targeting::matches( array_merge( $p, [ 'variants' => [] ] ), self::ctx() ) );
	}

	public function test_one_popup_per_type(): void {
		$a = Targeting::normalize( [ 'id' => 'a', 'enabled' => true, 'type' => 'slidein', 'variants' => [ [ 'title' => 'a' ] ] ] );
		$b = Targeting::normalize( [ 'id' => 'b', 'enabled' => true, 'type' => 'slidein', 'variants' => [ [ 'title' => 'b' ] ] ] );
		$c = Targeting::normalize( [ 'id' => 'c', 'enabled' => true, 'type' => 'topbar', 'variants' => [ [ 'title' => 'c' ] ] ] );
		$this->assertSame( [ 'a', 'c' ], array_column( Targeting::for_context( [ $a, $b, $c ], self::ctx() ), 'id' ) );
	}
}
