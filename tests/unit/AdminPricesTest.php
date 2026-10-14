<?php
/**
 * Growth → Prices: parsing what editors type, naira from dollars, merging over the defaults.
 */

namespace TechDella\Tests;

use PHPUnit\Framework\TestCase;
use TechDella\Core\Admin\Prices;

final class AdminPricesTest extends TestCase {

	public function test_parses_typed_amounts(): void {
		$this->assertSame( 1500, Prices::parse( '1500' ) );
		$this->assertSame( 1500, Prices::parse( '$1,500' ) );
		$this->assertSame( 1500, Prices::parse( ' 1 500 ' ) );
		$this->assertSame( 1500, Prices::parse( '1500.00' ) );
		$this->assertSame( 300000, Prices::parse( '₦300,000' ) );
		$this->assertNull( Prices::parse( '' ) );
		$this->assertNull( Prices::parse( 'ask us' ) );
		$this->assertNull( Prices::parse( '0' ) );
		$this->assertNull( Prices::parse( '-50' ) );
	}

	public function test_naira_from_dollars_has_a_floor(): void {
		$this->assertSame( 700000, Prices::ngn_from_usd( 3500 ) );
		$this->assertSame( 300000, Prices::ngn_from_usd( 750 ) );
	}

	public function test_merge_overrides_only_saved_prices(): void {
		$defaults = [
			'growth_audit'  => [ '$1,500', '₦300,000' ],
			'growth_sprint' => [ '$3,500', '₦700,000' ],
			'website'       => [ '$5,000', '₦1,000,000' ],
		];
		$saved    = [
			'growth_audit'  => [
				'usd' => 1800,
				'ngn' => 350000,
			],
			'growth_sprint' => [
				'usd' => 4000,
				'ngn' => null,
			],
			'website'       => [ 'usd' => 0 ],
			'unknown'       => [ 'usd' => 99 ],
		];
		$out      = Prices::merge( $defaults, $saved );
		$this->assertSame( [ '$1,800', '₦350,000' ], $out['growth_audit'] );
		$this->assertSame( [ '$4,000', '₦800,000' ], $out['growth_sprint'] );
		$this->assertSame( [ '$5,000', '₦1,000,000' ], $out['website'] );
		$this->assertArrayNotHasKey( 'unknown', $out );
		$this->assertSame( $defaults, Prices::merge( $defaults, false ) );
	}
}
