<?php
/**
 * Forms: schema shape + validation/sanitisation.
 */

use PHPUnit\Framework\TestCase;
use TechDella\Core\Forms\Validator;

final class FormsSchemaTest extends TestCase {

	public static function setUpBeforeClass(): void {
		td_forms_unit_load();
	}

	public function test_every_key_has_a_definition_with_email(): void {
		$defs = td_forms_definitions();
		$this->assertSame( td_forms_keys(), array_keys( $defs ) );
		foreach ( $defs as $key => $def ) {
			$this->assertArrayHasKey( 'email', $def['fields'], $key );
			$this->assertTrue( $def['fields']['email']['required'], $key );
			$this->assertNotEmpty( $def['sites'], $key );
			foreach ( $def['fields'] as $name => $f ) {
				$this->assertContains( $f['type'], [ 'text', 'email', 'url', 'textarea', 'select', 'pills', 'checkbox', 'hidden' ], "$key.$name" );
				$this->assertNotEmpty( $f['sb'] ?? $name, "$key.$name has a Startbuddi name" );
			}
		}
	}

	public function test_nurture_forms_have_unticked_consent_checkbox(): void {
		foreach ( td_forms_definitions() as $key => $def ) {
			$has = isset( $def['fields']['consent_marketing'] );
			if ( ! empty( $def['nurture'] ) ) {
				$this->assertTrue( $has, $key );
			}
			if ( $has ) {
				$this->assertSame( 'checkbox', $def['fields']['consent_marketing']['type'] );
				$this->assertTrue( $def['fields']['consent_marketing']['meta'] );
				$this->assertSame( 'newsletter' === $key, $def['fields']['consent_marketing']['required'], $key );
			}
		}
	}

	public function test_spec_options(): void {
		$defs = td_forms_definitions();
		$this->assertSame( [ 'traffic', 'competitors', 'ai', 'leads', 'ads', 'followup' ], array_keys( $defs['audit']['fields']['goals']['options'] ) );
		$this->assertCount( 4, $defs['studios_brief']['fields']['budget']['options'] );
		$this->assertArrayHasKey( '2000-5000', $defs['audit']['fields']['monthly_budget']['options'] );
	}

	public function test_valid_audit_submission(): void {
		$fields             = td_forms_definitions()['audit']['fields'];
		[ $clean, $errors ] = Validator::validate(
			$fields,
			[
				'website'        => 'example.com',
				'name'           => "  Ada <b>Obi</b> ",
				'email'          => 'ADA@Example.com',
				'goals'          => [ 'traffic', 'ai', 'traffic' ],
				'monthly_budget' => '2000-5000',
				'notes'          => "Line one\r\n\r\n\r\n\r\nLine two",
				'evil'           => 'dropped',
			]
		);
		$this->assertSame( [], $errors );
		$this->assertSame( 'https://example.com', $clean['website'] );
		$this->assertSame( 'Ada Obi', $clean['name'] );
		$this->assertSame( 'ada@example.com', $clean['email'] );
		$this->assertSame( [ 'traffic', 'ai' ], $clean['goals'] );
		$this->assertSame( "Line one\n\nLine two", $clean['notes'] );
		$this->assertArrayNotHasKey( 'evil', $clean );
		$this->assertSame( '', $clean['role'] );
	}

	public function test_validation_errors(): void {
		$fields      = td_forms_definitions()['audit']['fields'];
		[ , $errors ] = Validator::validate(
			$fields,
			[
				'website'        => 'not a url',
				'email'          => 'nope',
				'goals'          => [ 'hack' ],
				'monthly_budget' => 'billions',
				'notes'          => str_repeat( 'x', 4001 ),
			]
		);
		$this->assertEqualsCanonicalizing( [ 'website', 'name', 'email', 'goals', 'monthly_budget', 'notes' ], array_keys( $errors ) );
	}

	public function test_required_consent_and_radio_pills(): void {
		[ , $errors ] = Validator::validate( td_forms_definitions()['newsletter']['fields'], [ 'email' => 'a@b.co' ] );
		$this->assertArrayHasKey( 'consent_marketing', $errors );
		[ $clean, $errors ] = Validator::validate( td_forms_definitions()['newsletter']['fields'], [ 'email' => 'a@b.co', 'consent_marketing' => 'on' ] );
		$this->assertSame( [], $errors );
		$this->assertTrue( $clean['consent_marketing'] );

		$f = td_forms_definitions()['studios_brief']['fields']['budget'];
		$this->assertSame( [ '5k-15k', '' ], Validator::field( $f, '5k-15k' ) );
		$this->assertNotSame( '', Validator::field( $f, [ 'x' ] )[1] );
	}

	public function test_json_hidden_field(): void {
		$f = td_forms_definitions()['mvp_calc']['fields']['inputs'];
		[ $value, $error ] = Validator::field( $f, '{"Screens":12,"platform":"web <b>x</b>","nested":{"a":1},"list":["a","b"]}' );
		$this->assertSame( '', $error );
		$this->assertSame( [ 'screens' => 12, 'platform' => 'web x', 'list' => [ 'a', 'b' ] ], $value );
	}

	public function test_url_rules(): void {
		$this->assertSame( '', Validator::url( 'javascript:alert(1)' ) );
		$this->assertSame( '', Validator::url( 'ftp://example.com' ) );
		$this->assertSame( '', Validator::url( 'localhost' ) );
		$this->assertSame( 'http://example.com/a', Validator::url( 'http://example.com/a' ) );
	}
}
