<?php
/**
 * robots.txt builder (§8.3, §5.3).
 */

namespace TechDella\Tests;

use PHPUnit\Framework\TestCase;
use TechDella\Core\Seo\RobotsTxt;

final class SeoRobotsTxtTest extends TestCase {

	private const SITEMAP = 'https://techdella.com/sitemap_index.xml';

	public function test_default_rules_and_sitemap(): void {
		$txt = RobotsTxt::build( self::SITEMAP );
		$this->assertStringStartsWith( "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nDisallow: /?s=\nDisallow: /search/\n", $txt );
		$this->assertStringContainsString( "\nSitemap: " . self::SITEMAP . "\n", $txt );
		$this->assertStringNotContainsString( 'Disallow: /' . "\n" . 'User', $txt );
	}

	public function test_allow_all_does_not_block_ai_bots(): void {
		$txt = RobotsTxt::build( self::SITEMAP, 'allow-all' );
		foreach ( RobotsTxt::AI_BOTS as $bot ) {
			$this->assertStringNotContainsString( 'User-agent: ' . $bot, $txt );
			$this->assertStringContainsString( $bot, $txt ); // Named in the policy comment.
		}
		$this->assertSame( 1, substr_count( $txt, 'User-agent:' ) );
	}

	public function test_block_training_blocks_only_training_bots(): void {
		$txt = RobotsTxt::build( self::SITEMAP, 'block-training' );
		$this->assertStringContainsString( "User-agent: GPTBot\nDisallow: /\n", $txt );
		$this->assertStringContainsString( "User-agent: Google-Extended\nDisallow: /\n", $txt );
		foreach ( [ 'OAI-SearchBot', 'PerplexityBot', 'ClaudeBot' ] as $bot ) {
			$this->assertStringNotContainsString( 'User-agent: ' . $bot, $txt );
		}
		$this->assertStringContainsString( 'Sitemap: ' . self::SITEMAP, $txt );
	}

	public function test_unknown_policy_falls_back_to_allow_all(): void {
		$this->assertSame( RobotsTxt::build( self::SITEMAP, 'allow-all' ), RobotsTxt::build( self::SITEMAP, 'nonsense' ) );
	}

	public function test_staging_disallows_everything(): void {
		$txt = RobotsTxt::build( self::SITEMAP, 'allow-all', true );
		$this->assertStringContainsString( "User-agent: *\nDisallow: /\n", $txt );
		$this->assertStringNotContainsString( 'Sitemap:', $txt );
		$this->assertStringNotContainsString( 'Allow:', $txt );
	}
}
