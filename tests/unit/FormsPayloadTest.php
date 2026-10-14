<?php
/**
 * Forms: payload builder, UTM parsing, URL cleaning, signature.
 */

use PHPUnit\Framework\TestCase;
use TechDella\Core\Forms\Payload;
use TechDella\Core\Forms\Validator;

final class FormsPayloadTest extends TestCase {

	public static function setUpBeforeClass(): void {
		td_forms_unit_load();
	}

	public function test_build_matches_spec_shape(): void {
		$fields      = td_forms_definitions()['lead_magnet']['fields'];
		[ $clean ]   = Validator::validate( $fields, [ 'email' => 'ada@example.com', 'first_name' => 'Ada', 'resource_slug' => 'ai-checklist', 'consent_marketing' => '1' ] );
		$payload     = Payload::build( 'lead_magnet', $fields, $clean, [ 'site' => 'techdella.com', 'submitted_at' => '2026-10-10T12:00:00Z' ] );
		$this->assertSame( 'lead_magnet', $payload['form'] );
		$this->assertSame( 'ada@example.com', $payload['email'] );
		$this->assertSame( 'ai-checklist', $payload['resource_slug'] );
		$this->assertArrayNotHasKey( 'consent_marketing', $payload );
		$this->assertTrue( $payload['meta']['consent_marketing'] );
		$this->assertSame( 'techdella.com', $payload['meta']['site'] );
		$this->assertArrayNotHasKey( 'test', $payload );
		$this->assertFalse( Payload::build( 'guest_post', td_forms_definitions()['guest_post']['fields'], [], [] )['meta']['consent_marketing'] );
		$this->assertTrue( Payload::build( 'lead_magnet', $fields, $clean, [], true )['test'] );
	}

	public function test_sb_mapping_renames(): void {
		$fields  = td_forms_definitions()['mvp_calc']['fields'];
		$payload = Payload::build( 'mvp_calc', $fields, [ 'email' => 'a@b.co', 'inputs' => [ 'x' => 1 ] ], [] );
		$this->assertSame( [ 'x' => 1 ], $payload['estimate_inputs'] );
	}

	public function test_utm(): void {
		$this->assertSame( [ 'source' => 'google', 'medium' => 'organic' ], Payload::utm( '{"source":"google","medium":"organic","evil":"x"}' ) );
		$this->assertSame( [ 'campaign' => 'q4' ], Payload::utm( rawurlencode( '{"campaign":"q4"}' ) ) );
		$this->assertSame( [], Payload::utm( 'garbage' ) );
		$this->assertSame( [], Payload::utm( null ) );
	}

	public function test_clean_url(): void {
		$this->assertSame( 'https://techdella.com/growth-audit/?utm_source=x', Payload::clean_url( 'https://techdella.com/growth-audit/?td_fe=abc&utm_source=x#f' ) );
		$this->assertSame( '', Payload::clean_url( 'javascript:alert(1)' ) );
	}

	public function test_signature(): void {
		$sig = Payload::signature( '{"a":1}', 'secret', 1700000000 );
		$this->assertSame( 't=1700000000,v1=' . hash_hmac( 'sha256', '1700000000.{"a":1}', 'secret' ), $sig );
	}
}
