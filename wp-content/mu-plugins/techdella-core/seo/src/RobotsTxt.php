<?php
/**
 * robots.txt builder (§8.3, §5.3). Pure PHP.
 */

namespace TechDella\Core\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * robots.txt content.
 */
final class RobotsTxt {

	/** AI crawlers named in §8.3. */
	public const AI_BOTS = [ 'GPTBot', 'OAI-SearchBot', 'PerplexityBot', 'ClaudeBot', 'Google-Extended' ];

	/** Training-only crawlers blocked by the "block-training" policy (search bots stay allowed). */
	public const TRAINING_BOTS = [ 'GPTBot', 'Google-Extended' ];

	/** Valid values of the td_ai_crawler_policy option. */
	public const POLICIES = [ 'allow-all', 'block-training' ];

	/**
	 * Build robots.txt.
	 *
	 * @param string $sitemap_url Absolute sitemap index URL.
	 * @param string $policy      allow-all|block-training ([CONFIRM owner], default allow-all).
	 * @param bool   $staging     Staging: disallow everything.
	 */
	public static function build( string $sitemap_url, string $policy = 'allow-all', bool $staging = false ): string {
		if ( $staging ) {
			return "# Staging: not for search engines.\nUser-agent: *\nDisallow: /\n";
		}
		$policy = in_array( $policy, self::POLICIES, true ) ? $policy : 'allow-all';

		$lines = [
			'User-agent: *',
			'Disallow: /wp-admin/',
			'Allow: /wp-admin/admin-ajax.php',
			'Disallow: /?s=',
			'Disallow: /search/',
			'',
		];
		if ( 'block-training' === $policy ) {
			$allowed = array_values( array_diff( self::AI_BOTS, self::TRAINING_BOTS ) );
			$lines[] = '# AI crawlers: training-only bots blocked; AI search bots (' . implode( ', ', $allowed ) . ') follow the rules above.';
			foreach ( self::TRAINING_BOTS as $bot ) {
				$lines[] = 'User-agent: ' . $bot;
				$lines[] = 'Disallow: /';
				$lines[] = '';
			}
		} else {
			$lines[] = '# AI crawlers (' . implode( ', ', self::AI_BOTS ) . ') are allowed by the rules above.';
			$lines[] = '';
		}
		if ( '' !== $sitemap_url ) {
			$lines[] = 'Sitemap: ' . $sitemap_url;
		}
		return implode( "\n", $lines ) . "\n";
	}
}
