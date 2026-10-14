<?php
/**
 * Fields (§7): ACF JSON → post-meta schema, sanitising, validation, and the
 * ACF Pro row bridge.
 */

namespace TechDella\Tests;

use PHPUnit\Framework\TestCase;
use TechDella\Core\Cpt\Fields;
use TechDella\Core\Cpt\Registry;

final class CptFieldsTest extends TestCase {

	private static ?Fields $fields = null;

	private static function fields(): Fields {
		self::$fields ??= Fields::from_dir( TD_TEST_CORE_DIR . '/acf-json' );
		return self::$fields;
	}

	public function test_one_json_group_per_type_named_group_td_type(): void {
		foreach ( glob( TD_TEST_CORE_DIR . '/acf-json/*.json' ) as $file ) {
			$group = json_decode( (string) file_get_contents( $file ), true );
			$type  = $group['location'][0][0]['value'];
			$this->assertSame( 'group_td_' . str_replace( '-', '_', $type ) . '.json', basename( $file ) );
			$this->assertSame( basename( $file, '.json' ), $group['key'] );
			$this->assertArrayHasKey( $type, Registry::post_types(), $type );
		}
	}

	public function test_spec_field_names_exist(): void {
		$expect = [
			'service'        => [ 'hero_title', 'hero_accent_word', 'primary_kw', 'secondary_kws', 'platforms', 'bento', 'process', 'faq', 'related_services', 'related_case_studies', 'cta_variant' ],
			'solution'       => [ 'industry', 'pains', 'services_used', 'proof', 'compliance_notes', 'faq' ],
			'location'       => [ 'market', 'currency', 'timezone', 'local_proof', 'local_channels', 'nap', 'faq' ],
			'case_study'     => [ 'client', 'industry', 'country', 'services', 'metric_1_num', 'metric_3_label', 'timeframe', 'challenge', 'approach', 'results', 'quote', 'quote_author', 'quote_role', 'permission_confirmed', 'gallery', 'video' ],
			'td_tool'        => [ 'tool_component', 'primary_kw', 'how_to', 'faq', 'ad_slots', 'gate' ],
			'em-glossary'    => [ 'short_definition', 'category', 'related_terms', 'related_service', 'last_reviewed', 'reviewer' ],
			'td_resource'    => [ 'pdf', 'cover', 'pages', 'form', 'bullets' ],
			'thank-you'      => [ 'next_step_cta' ],
			'post'           => [ 'tldr', 'primary_kw', 'reviewer', 'last_reviewed', 'sources', 'cta_service' ],
			'studio_service' => [ 'hero_title', 'faq', 'related_services' ],
			'studio_work'    => [ 'client', 'status', 'disciplines', 'gallery', 'video', 'case_study' ],
			'td_team'        => [ 'role', 'bio', 'linkedin', 'expertise', 'photo' ],
			'td_testimonial' => [ 'quote', 'name', 'role', 'company', 'source_url', 'permission_confirmed' ],
			'platform'       => [ 'name', 'logo_svg', 'brand_rules_url' ],
		];
		foreach ( $expect as $type => $names ) {
			foreach ( $names as $name ) {
				$this->assertArrayHasKey( $name, self::fields()->fields( $type ), "{$type}.{$name}" );
			}
		}
	}

	public function test_every_field_has_help_text(): void {
		$walk = function ( array $fields, string $path ) use ( &$walk ): void {
			foreach ( $fields as $field ) {
				$this->assertNotSame( '', trim( (string) ( $field['instructions'] ?? '' ) ), "{$path}.{$field['name']} has no help text" );
				$walk( (array) ( $field['sub_fields'] ?? [] ), "{$path}.{$field['name']}" );
				foreach ( (array) ( $field['layouts'] ?? [] ) as $layout ) {
					$walk( (array) $layout['sub_fields'], "{$path}.{$field['name']}.{$layout['name']}" );
				}
			}
		};
		foreach ( self::fields()->post_types() as $type ) {
			$walk( array_values( self::fields()->fields( $type ) ), $type );
		}
	}

	public function test_short_definition_help_says_what_good_looks_like(): void {
		$this->assertStringContainsString( '40 words', self::fields()->fields( 'em-glossary' )['short_definition']['instructions'] );
	}

	public function test_schema_mapping(): void {
		$f = self::fields()->fields( 'service' );
		$this->assertSame(
			[
				'type'  => 'array',
				'items' => [
					'type'                 => 'object',
					'properties'           => [
						'question' => [ 'type' => 'string' ],
						'answer'   => [ 'type' => 'string' ],
					],
					'additionalProperties' => false,
				],
			],
			array_diff_key( Fields::schema( $f['faq'] ), [ 'description' => 1 ] )
		);
		$this->assertSame( 'integer', Fields::schema( $f['platforms'] )['items']['type'] );
		$this->assertSame( 'string', Fields::schema( $f['cta_variant'] )['type'] );
		$bento = Fields::schema( $f['bento'] )['items']['properties'];
		$this->assertArrayHasKey( 'acf_fc_layout', $bento );
		$this->assertArrayHasKey( 'testimonial', $bento );
		$this->assertSame( 'boolean', Fields::schema( self::fields()->fields( 'case_study' )['permission_confirmed'] )['type'] );
		$this->assertSame( 'integer', Fields::schema( self::fields()->fields( 'post' )['reviewer'] )['type'] );
		$this->assertSame( 'object', Fields::schema( self::fields()->fields( 'thank-you' )['next_step_cta'] )['type'] );
	}

	public function test_pro_only_detection(): void {
		$f = self::fields()->fields( 'case_study' );
		$this->assertTrue( Fields::is_pro_only( $f['gallery'] ) );
		$this->assertFalse( Fields::is_pro_only( $f['permission_confirmed'] ) );
		$this->assertTrue( Fields::is_structured( $f['services'] ) );
	}

	public function test_sanitize_coerces_acf_values(): void {
		$this->assertSame( [ 12, 7 ], Fields::sanitize( [ '12', '7', '', 'x' ], [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ] ) );
		$this->assertTrue( Fields::sanitize( '1', [ 'type' => 'boolean' ] ) );
		$this->assertFalse( Fields::sanitize( '0', [ 'type' => 'boolean' ] ) );
		$schema = Fields::schema( self::fields()->fields( 'service' )['faq'] );
		$this->assertSame(
			[
				[
					'question' => 'Q',
					'answer'   => '5',
				],
			],
			Fields::sanitize(
				[
					[
						'question' => 'Q',
						'answer'   => 5,
						'evil'     => '<script>',
					],
					'not-a-row',
				],
				$schema
			)
		);
		$this->assertSame( [], Fields::sanitize( 3, $schema ) ); // ACF's row count never leaks in.
	}

	public function test_validate_reports_shape_errors(): void {
		$schema = Fields::schema( self::fields()->fields( 'service' )['faq'] );
		$this->assertSame( [], Fields::validate( [ [ 'question' => 'Q', 'answer' => 'A' ] ], $schema, 'faq' ) );
		$this->assertSame( [ 'faq must be a list: [ … ]' ], Fields::validate( [ 'question' => 'Q' ], $schema, 'faq' ) );
		$errors = Fields::validate( [ [ 'q' => 'Q' ] ], $schema, 'faq' );
		$this->assertStringContainsString( 'faq[0].q is not a known field', $errors[0] );
		$this->assertSame( [ 'x[0] must be a integer' ], Fields::validate( [ 'a' ], [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ], 'x' ) );
	}

	public function test_example_matches_its_schema(): void {
		foreach ( self::fields()->post_types() as $type ) {
			foreach ( self::fields()->fields( $type ) as $name => $field ) {
				if ( Fields::is_structured( $field ) ) {
					$schema = Fields::schema( $field );
					$this->assertSame( [], Fields::validate( Fields::example_for( $field ), $schema ), "{$type}.{$name}" );
				}
			}
		}
	}

	public function test_acf_pro_rows_round_trip(): void {
		$faq  = self::fields()->fields( 'service' )['faq'];
		$rows = [
			[
				'question' => 'How long?',
				'answer'   => '50 days to first result.',
			],
		];
		$acf  = Fields::rows_to_acf( $rows, $faq );
		$this->assertSame(
			[
				[
					'field_td_service_faq_question' => 'How long?',
					'field_td_service_faq_answer'   => '50 days to first result.',
				],
			],
			$acf
		);
		$input = [ 'row-0' => $acf[0] ] + [ 'acfcloneindex' => [] ];
		$this->assertSame( $rows, Fields::rows_from_acf( $input, $faq ) );

		$bento = self::fields()->fields( 'service' )['bento'];
		$tiles = [
			[
				'acf_fc_layout' => 'stat',
				'number'        => '50%',
				'label'         => 'of conversions from inbound',
				'source'        => 'Paycape case study',
			],
		];
		$this->assertSame( $tiles, Fields::rows_from_acf( Fields::rows_to_acf( $tiles, $bento ), $bento ) );
	}
}
