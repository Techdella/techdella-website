<?php
/**
 * ads: ads.txt line, where ads load, slot cap.
 */

use PHPUnit\Framework\TestCase;
use TechDella\Core\Ads\Policy;

final class AdsPolicyTest extends TestCase {

	public static function setUpBeforeClass(): void {
		td_ads_unit_load();
	}

	public function test_ads_txt_line_strips_ca_prefix(): void {
		$this->assertSame( 'google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0', Policy::ads_txt_line( 'ca-pub-0000000000000000' ) );
		$this->assertSame( 'google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0', Policy::ads_txt_line( ' pub-1234567890123456 ' ) );
		$this->assertSame( 'ca-pub-1234567890123456', Policy::client( 'pub-1234567890123456' ) );
	}

	public function test_ads_txt_empty_when_unset_or_invalid(): void {
		foreach ( [ '', '[ca-pub-…]', 'ca-pub-12', 'pub-abc', 'ca-pub-1234567890123456; rm' ] as $bad ) {
			$this->assertSame( '', Policy::ads_txt_line( $bad ), $bad );
		}
	}

	public function test_ads_only_on_marketing_tools(): void {
		$this->assertTrue( Policy::enabled( 'marketing', 'tool' ) );
		$this->assertTrue( Policy::enabled( 'marketing', 'tools_hub' ) );
		$this->assertFalse( Policy::enabled( 'marketing', 'other' ) );
		$this->assertFalse( Policy::enabled( 'studios', 'tool' ) );
		$this->assertFalse( Policy::enabled( 'journal', 'journal_post' ) );
		$this->assertTrue( Policy::enabled( 'journal', 'journal_post', true ) );
		$this->assertFalse( Policy::enabled( 'journal', 'other', true ) );
	}

	public function test_slot_cap(): void {
		$this->assertTrue( Policy::slot_allowed( 'result', 0 ) );
		$this->assertTrue( Policy::slot_allowed( 'sidebar', 2 ) );
		$this->assertFalse( Policy::slot_allowed( 'content', 3 ) );
		$this->assertFalse( Policy::slot_allowed( 'header', 0 ) );
	}
}
