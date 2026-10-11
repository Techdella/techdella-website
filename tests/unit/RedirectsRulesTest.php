<?php
/**
 * Redirects: CSV parsing, matcher (exact/prefix/regex, never substring), chains, Rank Math mapping.
 */

use PHPUnit\Framework\TestCase;
use TechDella\Core\Cli\RedirectRules;

final class RedirectsRulesTest extends TestCase {

	/**
	 * Parsed rules from the real data/redirects.csv.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private static array $rules = [];

	public static function setUpBeforeClass(): void {
		td_cli_unit_load();
		$parsed = RedirectRules::parse_csv( (string) file_get_contents( dirname( __DIR__, 2 ) . '/data/redirects.csv' ) );
		self::$rules = $parsed['rules'];
	}

	private static function target( string $path, bool $conditional = false ): ?string {
		$hit = RedirectRules::match( RedirectRules::active( self::$rules, $conditional ), $path );
		return $hit ? $hit['target'] : null;
	}

	public function test_real_csv_parses_without_errors(): void {
		$parsed = RedirectRules::parse_csv( (string) file_get_contents( dirname( __DIR__, 2 ) . '/data/redirects.csv' ) );
		$this->assertSame( [], $parsed['errors'] );
		$this->assertCount( 70, $parsed['rules'] );
		$this->assertCount( 6, array_filter( $parsed['rules'], static fn( $r ) => $r['conditional'] ) );
	}

	public function test_conditions(): void {
		$csv   = "source,target,code,condition,notes\n/a/,/b/,301,,x\n/c/,/d/,301,GSC check first,\"note, with comma\"\n/e/,/f/,301,prefix,\n/g/*,/h/$1,301,regex ^/g/(.+)$,\n/bad,/x/,999,,\nnoslash,/x/,301,,\n";
		$out   = RedirectRules::parse_csv( $csv );
		$kinds = array_column( $out['rules'], 'kind' );
		$this->assertSame( [ 'exact', 'exact', 'prefix', 'regex' ], $kinds );
		$this->assertTrue( $out['rules'][1]['conditional'] );
		$this->assertSame( 'note, with comma', $out['rules'][1]['notes'] );
		$this->assertCount( 2, $out['errors'] );
		$this->assertCount( 3, RedirectRules::active( $out['rules'] ) );
		$this->assertCount( 4, RedirectRules::active( $out['rules'], true ) );
	}

	public function test_exact_matches_ignore_trailing_slash_case_and_query(): void {
		$this->assertSame( '/tools/', self::target( '/free-tools/' ) );
		$this->assertSame( '/tools/', self::target( '/free-tools' ) );
		$this->assertSame( '/tools/', self::target( '/FREE-TOOLS/?utm_source=x' ) );
		$this->assertSame( '/services/seo/', self::target( '/service/seo/' ) );
	}

	public function test_no_substring_matching_regression(): void {
		// §9.2: the live solution page must never redirect.
		$this->assertNull( self::target( '/solution/health-marketing-agency/' ) );
		$this->assertNull( self::target( '/services/seo/' ) );
		$this->assertNull( self::target( '/service/seo-extra/' ) );
		$this->assertNull( self::target( '/my/free-tools/' ) );
		$this->assertNull( self::target( '/solutionsx/' ) );
	}

	public function test_prefix_keeps_remainder(): void {
		$this->assertSame( '/services/', self::target( '/solutions/' ) );
		$this->assertSame( '/solution/fintech-marketing-agency/', self::target( '/solutions/fintech-marketing-agency/' ) );
		$this->assertSame( '/services/marketing-automation/', self::target( '/solution/ai-marketing-agency/' ) );
		$prefix = RedirectRules::make_rule( '/old/', '/new-page', '301', 'prefix' );
		$this->assertSame( '/new-page', RedirectRules::match( [ $prefix ], '/old/deep/x/' )['target'] );
	}

	public function test_exact_beats_prefix_and_regex(): void {
		$this->assertSame( 'https://blog.techdella.com/what-is-vibe-coding/', self::target( '/guide/what-is-vibe-coding/' ) );
		$this->assertSame( 'https://blog.techdella.com/', self::target( '/guide/' ) );
		$this->assertSame( 'https://blog.techdella.com/some-unlisted-guide/', self::target( '/guide/some-unlisted-guide/' ) );
	}

	public function test_regex_substitution(): void {
		$rule = RedirectRules::make_rule( '/guide/*', 'https://blog.techdella.com/$1', '301', 'regex ^/guide/(.+)$' );
		$this->assertSame( 'https://blog.techdella.com/a-b/', RedirectRules::match( [ $rule ], '/guide/a-b/' )['target'] );
		$this->assertNull( RedirectRules::match( [ $rule ], '/guides/a/' ) );
		$this->assertIsString( RedirectRules::make_rule( '/x/', '/y/', '301', 'regex ([' ) );
	}

	public function test_conditional_rows_skipped_unless_included(): void {
		$this->assertNull( self::target( '/locations/germany/' ) );
		$this->assertSame( '/locations/europe/', self::target( '/locations/germany/', true ) );
	}

	public function test_domain_rewrite(): void {
		$map = [
			'https://blog.techdella.com'    => 'http://localhost:8082',
			'https://studios.techdella.com' => 'http://localhost:8083/',
		];
		$this->assertSame( 'http://localhost:8082/x/', RedirectRules::rewrite_domain( 'https://blog.techdella.com/x/', $map ) );
		$this->assertSame( 'http://localhost:8083', RedirectRules::rewrite_domain( 'https://studios.techdella.com', $map ) );
		$this->assertSame( 'https://hub.techdella.com/', RedirectRules::rewrite_domain( 'https://hub.techdella.com/', $map ) );
		$this->assertSame( 'https://blog.techdella.com.evil/', RedirectRules::rewrite_domain( 'https://blog.techdella.com.evil/', $map ) );
		$this->assertSame( '/tools/', RedirectRules::rewrite_domain( '/tools/', $map ) );
	}

	public function test_one_hop_chain_detection(): void {
		$rules = [
			RedirectRules::make_rule( '/a/', '/b/' ),
			RedirectRules::make_rule( '/b/', '/c/' ),
			RedirectRules::make_rule( '/x/', 'http://localhost:8081/b/' ),
			RedirectRules::make_rule( '/y/', 'https://blog.techdella.com/b/' ),
		];
		$chains = RedirectRules::chains( $rules, [ 'http://localhost:8081' ] );
		$this->assertSame( [ '/a/', '/x/' ], array_column( $chains, 'source' ) );
		$this->assertSame( [], RedirectRules::chains( RedirectRules::active( self::$rules ), [ 'https://techdella.com' ] ) );
	}

	public function test_rank_math_rows(): void {
		$exact = RedirectRules::to_rank_math( RedirectRules::make_rule( '/free-tools/', '/tools/' ), 'http://localhost:8081/tools/' );
		$this->assertSame( 'free-tools', $exact[0]['sources'][0]['pattern'] );
		$this->assertSame( 'exact', $exact[0]['sources'][0]['comparison'] );

		$prefix = RedirectRules::to_rank_math( RedirectRules::make_rule( '/solutions/', '/solution/', '301', 'prefix' ), 'http://h/solution/' );
		$this->assertSame( [ 'exact', 'regex' ], array_map( static fn( $r ) => $r['sources'][0]['comparison'], $prefix ) );
		$this->assertSame( '^solutions/(.+)$', $prefix[1]['sources'][0]['pattern'] );
		$this->assertSame( 'http://h/solution/$1/', $prefix[1]['url_to'] );

		$regex = RedirectRules::to_rank_math( RedirectRules::make_rule( '/guide/*', 'https://b/$1', '301', 'regex ^/guide/(.+)$' ), 'https://b/$1' );
		$this->assertSame( '^guide/(.+)$', $regex[0]['sources'][0]['pattern'] );
		$this->assertSame( 'https://b/$1/', $regex[0]['url_to'] );
		$this->assertSame( 2, $regex[0]['rank'] );

		// Rank Math regex is wrapped in @…@ after stripslashes(): special chars go in classes.
		$this->assertSame( 'a[.]b', RedirectRules::rm_quote( 'a.b' ) );
		$this->assertIsString( RedirectRules::to_rank_math( RedirectRules::make_rule( '/x/', '/y/', '301', 'regex ^/x\.y$' ), '/y/' ) );
		$this->assertIsString( RedirectRules::to_rank_math( RedirectRules::make_rule( '/', '/y/' ), '/y/' ) );
	}

	public function test_csv_round_trip(): void {
		$csv   = RedirectRules::to_csv( self::$rules );
		$again = RedirectRules::parse_csv( $csv );
		$this->assertSame( array_column( self::$rules, 'source' ), array_column( $again['rules'], 'source' ) );
		$this->assertSame( array_column( self::$rules, 'kind' ), array_column( $again['rules'], 'kind' ) );
	}
}
