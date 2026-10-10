<?php
/**
 * Noindex decision table (§8.3, §5.3).
 */

namespace TechDella\Tests;

use PHPUnit\Framework\TestCase;
use TechDella\Core\Seo\Noindex;

final class SeoNoindexTest extends TestCase {

	private const TYPES = [ 'thank-you', 'document' ];

	/**
	 * @return array<string,array{0:array<string,mixed>,1:bool,2:string}>
	 */
	public static function cases(): array {
		$single = static fn( string $type, array $extra = [] ): array => array_merge(
			[
				'env'           => 'production',
				'is_singular'   => true,
				'post_type'     => $type,
				'noindex_types' => self::TYPES,
			],
			$extra
		);
		return [
			'service page indexable'       => [ $single( 'service' ), false, '' ],
			'home indexable (local)'       => [ [ 'env' => 'local' ], false, '' ],
			'thank-you noindex'            => [ $single( 'thank-you' ), true, 'post_type' ],
			'document noindex'             => [ $single( 'document' ), true, 'post_type' ],
			'_td_noindex meta'             => [ $single( 'post', [ 'post_noindex' => true ] ), true, 'post_meta' ],
			'td_noindex filter'            => [ [ 'env' => 'production', 'forced' => true ], true, 'filter' ],
			'search results'               => [ [ 'env' => 'production', 'is_search' => true ], true, 'search' ],
			'attachment without parent'    => [ [ 'env' => 'production', 'is_attachment' => true ], true, 'attachment' ],
			'thin tag (4 posts)'           => [ [ 'is_tag' => true, 'tag_count' => 4 ], true, 'thin_tag' ],
			'tag with 5 posts'             => [ [ 'is_tag' => true, 'tag_count' => 5 ], false, '' ],
			'author, not team'             => [ [ 'is_author' => true, 'author_posts' => 10 ], true, 'author' ],
			'team author, 2 posts'         => [ [ 'is_author' => true, 'author_is_team' => true, 'author_posts' => 2 ], true, 'author' ],
			'team author, 3 posts'         => [ [ 'is_author' => true, 'author_is_team' => true, 'author_posts' => 3 ], false, 'team_author' ],
			'staging: everything'          => [ $single( 'service', [ 'env' => 'staging' ] ), true, 'staging' ],
		];
	}

	/**
	 * @dataProvider cases
	 * @param array<string,mixed> $ctx Context.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'cases' )]
	public function test_decide( array $ctx, bool $noindex, string $reason ): void {
		$decision = Noindex::decide( $ctx );
		$this->assertSame( $noindex, $decision['noindex'] );
		$this->assertSame( $reason, $decision['reason'] );
	}

	public function test_staging_is_nofollow_too(): void {
		$this->assertSame( 'noindex, nofollow', Noindex::directive( Noindex::decide( [ 'env' => 'staging' ] ) ) );
		$this->assertSame( 'noindex, follow', Noindex::directive( Noindex::decide( [ 'is_search' => true ] ) ) );
		$this->assertSame( '', Noindex::directive( Noindex::decide( [] ) ) );
	}

	public function test_team_author_forces_index(): void {
		$this->assertTrue( Noindex::decide( [ 'is_author' => true, 'author_is_team' => true, 'author_posts' => 7 ] )['index'] );
	}

	public function test_production_never_noindex_without_a_rule(): void {
		$this->assertFalse( Noindex::decide( [ 'env' => 'production', 'is_singular' => true, 'post_type' => 'page', 'noindex_types' => self::TYPES ] )['noindex'] );
	}
}
