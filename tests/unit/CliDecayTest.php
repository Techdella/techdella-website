<?php
/**
 * cli: GSC decay + review cadence.
 */

use PHPUnit\Framework\TestCase;
use TechDella\Core\Cli\Decay;

final class CliDecayTest extends TestCase {

	public static function setUpBeforeClass(): void {
		td_cli_unit_load();
	}

	public function test_parses_gsc_compare_export(): void {
		$csv  = "\xEF\xBB\xBFTop pages,Last 3 months Clicks,Previous 3 months Clicks,Last 3 months Impressions,Previous 3 months Impressions\n";
		$csv .= "https://techdella.com/a/,70,100,1000,1000\nhttps://techdella.com/b/,\"1,200\",\"1,000\",1,1\n";
		$rows = Decay::parse_gsc( $csv );
		$this->assertSame( [ 'url' => 'https://techdella.com/a/', 'current' => 70, 'previous' => 100 ], $rows[0] );
		$this->assertSame( 1200, $rows[1]['current'] );
	}

	public function test_previous_column_detected_by_name_in_any_order(): void {
		$rows = Decay::parse_gsc( "Page,Previous period Clicks,Current Clicks\n/x/,10,5\n" );
		$this->assertSame( 5, $rows[0]['current'] );
		$this->assertSame( 10, $rows[0]['previous'] );
	}

	public function test_missing_columns_throw(): void {
		$this->expectException( InvalidArgumentException::class );
		Decay::parse_gsc( "Page,Clicks\n/x/,1\n" );
	}

	public function test_decay_threshold_and_noise_floor(): void {
		$rows = [
			[ 'url' => '/thirty/', 'current' => 70, 'previous' => 100 ],
			[ 'url' => '/twenty-nine/', 'current' => 71, 'previous' => 100 ],
			[ 'url' => '/half/', 'current' => 50, 'previous' => 100 ],
			[ 'url' => '/grew/', 'current' => 150, 'previous' => 100 ],
			[ 'url' => '/tiny/', 'current' => 0, 'previous' => 5 ],
			[ 'url' => '/new/', 'current' => 10, 'previous' => 0 ],
		];
		$out = Decay::decayed( $rows );
		$this->assertSame( [ '/half/', '/thirty/' ], array_column( $out, 'url' ) );
		$this->assertSame( 0.5, $out[0]['drop'] );
		$this->assertSame( 0.0, Decay::drop( 10, 0 ) );
	}

	public function test_review_cadence(): void {
		$reviewed = gmmktime( 0, 0, 0, 8, 31, 2026 );
		$this->assertFalse( Decay::review_overdue( $reviewed, 3, gmmktime( 0, 0, 0, 11, 29, 2026 ) ) );
		$this->assertTrue( Decay::review_overdue( $reviewed, 3, gmmktime( 0, 0, 0, 11, 30, 2026 ) ) );
		$this->assertTrue( Decay::review_overdue( $reviewed, 6, gmmktime( 0, 0, 0, 2, 28, 2027 ) ) );
		$this->assertFalse( Decay::review_overdue( $reviewed, 6, gmmktime( 0, 0, 0, 2, 27, 2027 ) ) );
		$this->assertFalse( Decay::review_overdue( $reviewed, 0, PHP_INT_MAX ) );
	}
}
