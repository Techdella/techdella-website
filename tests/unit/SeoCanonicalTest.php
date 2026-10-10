<?php
/**
 * Canonical URL cleaner (§8.3).
 */

namespace TechDella\Tests;

use PHPUnit\Framework\TestCase;
use TechDella\Core\Seo\Canonical;

final class SeoCanonicalTest extends TestCase {

	/**
	 * @return array<string,array{0:string,1:bool,2:string}>
	 */
	public static function urls(): array {
		return [
			'adds trailing slash'          => [ 'https://techdella.com/services/seo', false, 'https://techdella.com/services/seo/' ],
			'strips utm + ref'             => [ 'https://techdella.com/services/seo/?utm_source=x&ref=y', false, 'https://techdella.com/services/seo/' ],
			'strips gclid/fbclid'          => [ 'https://techdella.com/?gclid=1&fbclid=2', false, 'https://techdella.com/' ],
			'strips tool ?q='              => [ 'https://techdella.com/tools/utm-builder/?q=hello', false, 'https://techdella.com/tools/utm-builder/' ],
			'strips unknown vars'          => [ 'https://techdella.com/glossary/seo/?foo=bar#x', false, 'https://techdella.com/glossary/seo/' ],
			'forces https in production'   => [ 'http://techdella.com/about-us/', true, 'https://techdella.com/about-us/' ],
			'keeps http locally'           => [ 'http://localhost:8081/about-us/?utm_medium=y', false, 'http://localhost:8081/about-us/' ],
			'keeps local port'             => [ 'http://localhost:8082/page/2/', false, 'http://localhost:8082/page/2/' ],
			'lowercases host'              => [ 'https://Blog.TechDella.com/Post-Slug', false, 'https://blog.techdella.com/Post-Slug/' ],
			'collapses double slashes'     => [ 'https://techdella.com//services//seo', false, 'https://techdella.com/services/seo/' ],
			'no slash on files'            => [ 'https://techdella.com/sitemap_index.xml?x=1', false, 'https://techdella.com/sitemap_index.xml' ],
			'home without path'            => [ 'https://techdella.com', false, 'https://techdella.com/' ],
			'drops default port on https'  => [ 'https://techdella.com:443/x', false, 'https://techdella.com/x/' ],
		];
	}

	/**
	 * @dataProvider urls
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'urls' )]
	public function test_clean( string $in, bool $https, string $expected ): void {
		$this->assertSame( $expected, Canonical::clean( $in, $https ) );
	}

	public function test_relative_or_empty_is_rejected(): void {
		$this->assertSame( '', Canonical::clean( '' ) );
		$this->assertSame( '', Canonical::clean( '/services/seo/' ) );
	}

	public function test_keep_whitelisted_vars_for_plain_permalinks(): void {
		$this->assertSame( 'http://localhost/?p=12', Canonical::clean( 'http://localhost/?utm_source=a&p=12', false, [ 'p' ] ) );
	}

	public function test_paginated_archives_are_self_canonical(): void {
		$this->assertSame( 'https://blog.techdella.com/category/seo/page/3/', Canonical::paged( 'https://blog.techdella.com/category/seo/', 3 ) );
		$this->assertSame( 'https://blog.techdella.com/category/seo/', Canonical::paged( 'https://blog.techdella.com/category/seo/', 1 ) );
	}
}
