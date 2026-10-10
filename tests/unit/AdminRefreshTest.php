<?php
/**
 * Refresh-due calculation (§10.1: money pages quarterly, guides 6 months).
 */

namespace TechDella\Tests;

use PHPUnit\Framework\TestCase;
use TechDella\Core\Admin\Refresh;

final class AdminRefreshTest extends TestCase {

	private function ts( string $date ): int {
		return (int) strtotime( $date . ' 00:00:00 UTC' );
	}

	public function test_parses_acf_and_iso_dates(): void {
		$this->assertSame( $this->ts( '2026-04-10' ), Refresh::parse_date( '20260410' ) );
		$this->assertSame( $this->ts( '2026-04-10' ), Refresh::parse_date( '2026-04-10' ) );
		$this->assertSame( $this->ts( '2026-04-10' ), Refresh::parse_date( '2026-04-10 13:45:00' ) );
		$this->assertNull( Refresh::parse_date( '' ) );
		$this->assertNull( Refresh::parse_date( '20261340' ) );
		$this->assertNull( Refresh::parse_date( null ) );
	}

	public function test_due_date_adds_calendar_months_and_clamps(): void {
		$this->assertSame( $this->ts( '2026-07-10' ), Refresh::due_at( $this->ts( '2026-04-10' ), 3 ) );
		$this->assertSame( $this->ts( '2027-02-28' ), Refresh::due_at( $this->ts( '2026-08-31' ), 6 ) );
		$this->assertSame( $this->ts( '2028-02-29' ), Refresh::due_at( $this->ts( '2027-08-31' ), 6 ) );
		$this->assertSame( $this->ts( '2027-01-15' ), Refresh::due_at( $this->ts( '2026-10-15' ), 3 ) );
		$this->assertSame( $this->ts( '2027-12-01' ), Refresh::due_at( $this->ts( '2026-12-01' ), 12 ) );
	}

	public function test_money_page_reviewed_four_months_ago_is_due(): void {
		$status = Refresh::status( '20260601', '2026-10-01 10:00:00', 3, $this->ts( '2026-10-10' ) );
		$this->assertTrue( $status['due'] );
		$this->assertTrue( $status['reviewed'] );
		$this->assertSame( 39, $status['days_overdue'] ); // Due 1 Sep.
	}

	public function test_guide_reviewed_four_months_ago_is_not_due(): void {
		$status = Refresh::status( '2026-06-01', '2026-06-01 10:00:00', 6, $this->ts( '2026-10-10' ) );
		$this->assertFalse( $status['due'] );
		$this->assertSame( 0, $status['days_overdue'] );
		$this->assertSame( $this->ts( '2026-12-01' ), $status['due_at'] );
	}

	public function test_falls_back_to_last_update_when_never_reviewed(): void {
		$status = Refresh::status( '', '2025-12-31 23:00:00', 6, $this->ts( '2026-10-10' ) );
		$this->assertTrue( $status['due'] );
		$this->assertFalse( $status['reviewed'] );
	}

	public function test_due_on_the_day(): void {
		$this->assertTrue( Refresh::status( '20260710', '', 3, $this->ts( '2026-10-10' ) )['due'] );
	}

	public function test_no_cadence_is_never_due(): void {
		$this->assertFalse( Refresh::status( '20200101', '2020-01-01 00:00:00', 0, $this->ts( '2026-10-10' ) )['due'] );
	}

	public function test_top_sorts_most_overdue_first(): void {
		$top = Refresh::top(
			[
				[
					'id'           => 1,
					'days_overdue' => 3,
				],
				[
					'id'           => 2,
					'days_overdue' => 40,
				],
				[
					'id'           => 3,
					'days_overdue' => 10,
				],
			],
			2
		);
		$this->assertSame( [ 2, 3 ], array_column( $top, 'id' ) );
	}
}
