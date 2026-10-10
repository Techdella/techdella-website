<?php
/**
 * Content model (§7): sites, live URL bases, SEO flags, capability types.
 */

namespace TechDella\Tests;

use PHPUnit\Framework\TestCase;
use TechDella\Core\Cpt\Registry;

final class CptRegistryTest extends TestCase {

	/**
	 * @return array<string,array{0:string,1:string|false,2:string[]}>
	 */
	public static function live_bases(): array {
		return [
			'service'        => [ 'service', 'services', [ 'marketing' ] ],
			'solution'       => [ 'solution', 'solution', [ 'marketing' ] ],
			'location'       => [ 'location', 'locations', [ 'marketing' ] ],
			'case_study'     => [ 'case_study', 'case-studies', [ 'marketing', 'studios' ] ],
			'td_tool'        => [ 'td_tool', 'tools', [ 'marketing' ] ],
			'em-glossary'    => [ 'em-glossary', 'glossary', [ 'marketing' ] ],
			'td_resource'    => [ 'td_resource', 'resources', [ 'marketing' ] ],
			'business'       => [ 'business', 'business', [ 'marketing' ] ],
			'thank-you'      => [ 'thank-you', 'thank-you', [ 'marketing', 'journal', 'studios' ] ],
			'document'       => [ 'document', 'document', [ 'marketing' ] ],
			'studio_service' => [ 'studio_service', 'services', [ 'studios' ] ],
			'studio_work'    => [ 'studio_work', 'work', [ 'studios' ] ],
			'td_team'        => [ 'td_team', false, [ 'marketing', 'journal', 'studios' ] ],
			'td_testimonial' => [ 'td_testimonial', false, [ 'marketing', 'journal', 'studios' ] ],
			'platform'       => [ 'platform', false, [ 'marketing', 'journal', 'studios' ] ],
		];
	}

	/**
	 * @param string       $type  Post type.
	 * @param string|false $slug  Expected rewrite base.
	 * @param string[]     $sites Expected sites.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'live_bases' )]
	public function test_live_url_base_and_sites( string $type, $slug, array $sites ): void {
		$def = Registry::post_types()[ $type ];
		$this->assertSame( $slug, $def['slug'] ?? false );
		$this->assertSame( $sites, $def['sites'] );
	}

	public function test_no_legacy_td_solution_type(): void {
		$this->assertArrayNotHasKey( 'td_solution', Registry::post_types() );
	}

	public function test_business_keeps_its_archive(): void {
		$this->assertSame( 'business', Registry::post_types()['business']['archive'] );
	}

	public function test_brand_types_have_no_front_end(): void {
		foreach ( [ 'td_team', 'td_testimonial', 'platform' ] as $type ) {
			$this->assertFalse( Registry::post_types()[ $type ]['public'], $type );
		}
	}

	public function test_seo_flags(): void {
		$this->assertSame( [ 'thank-you', 'document' ], Registry::noindex() );
		$excluded = Registry::excluded_from_sitemap();
		sort( $excluded );
		$this->assertSame( [ 'document', 'platform', 'td_team', 'td_testimonial', 'thank-you' ], $excluded );
	}

	public function test_document_is_private_and_never_on_journal_or_studios(): void {
		$this->assertTrue( Registry::post_types()['document']['private'] );
		$this->assertArrayNotHasKey( 'document', Registry::for_site( 'journal' ) );
		$this->assertArrayNotHasKey( 'document', Registry::for_site( 'studios' ) );
	}

	public function test_permission_and_checklist_types(): void {
		$this->assertSame( [ 'case_study', 'td_testimonial' ], Registry::with_flag( 'permission' ) );
		$checklist = Registry::with_flag( 'checklist' );
		foreach ( [ 'post', 'service', 'solution', 'location', 'case_study', 'em-glossary', 'td_resource', 'studio_service', 'studio_work' ] as $type ) {
			$this->assertContains( $type, $checklist );
		}
	}

	public function test_capability_types_have_distinct_plurals_and_popups(): void {
		$plurals = array_column( Registry::capability_types(), 1 );
		$this->assertSame( $plurals, array_values( array_unique( $plurals ) ) );
		$this->assertContains( 'td_popups', $plurals );
		$this->assertSame( 'publish_td_case_studies', Registry::primitive_caps( 'td_case_studies' )['publish_posts'] );
	}

	public function test_taxonomies(): void {
		$tax = Registry::taxonomies();
		$this->assertSame( [ 'solution', 'case_study', 'post' ], $tax['td_industry']['types'] );
		$this->assertSame( [ 'post', 'em-glossary' ], $tax['td_service_tag']['types'] );
		$this->assertSame( [ 'marketing', 'studios' ], array_keys( $tax['division']['terms'] ) );
		$this->assertSame( [ 'em-glossary' ], $tax['glossary_category']['types'] );
	}

	public function test_every_site_type_has_a_menu_group(): void {
		foreach ( Registry::post_types() as $type => $def ) {
			$this->assertContains( $def['menu'], [ 'td-content', 'td-growth', 'td-studios', 'td-brand' ], $type );
		}
	}
}
