<?php
/**
 * Tools: SSRF guard (§12.1) — IP ranges, URL shape, DNS vetting, redirects.
 */

use PHPUnit\Framework\TestCase;
use TechDella\Core\Tools\Ssrf;

final class ToolsSsrfTest extends TestCase {

	public static function setUpBeforeClass(): void {
		td_tools_unit_load();
	}

	public function test_private_and_reserved_ips_are_blocked(): void {
		foreach ( [
			'127.0.0.1', '127.255.255.254', '10.1.2.3', '172.16.0.1', '172.31.255.255', '192.168.1.1',
			'169.254.169.254', '100.64.0.1', '0.0.0.0', '224.0.0.1', '240.0.0.1', '255.255.255.255',
			'192.0.2.10', '198.18.0.5', '203.0.113.9',
			'::1', '::', 'fe80::1', 'fc00::1', 'fd12:3456::1', 'ff02::1', '2001:db8::1',
			'::ffff:127.0.0.1', '::ffff:10.0.0.1', '::ffff:169.254.169.254', '64:ff9b::a00:1', '2002:a00:1::1',
			'[::1]', 'not-an-ip', '',
		] as $ip ) {
			$this->assertFalse( Ssrf::is_public_ip( $ip ), $ip );
		}
	}

	public function test_public_ips_are_allowed(): void {
		foreach ( [ '93.184.216.34', '8.8.8.8', '172.32.0.1', '1.1.1.1', '2606:4700:4700::1111', '2a00:1450:4001::200e', '::ffff:8.8.8.8' ] as $ip ) {
			$this->assertTrue( Ssrf::is_public_ip( $ip ), $ip );
		}
	}

	public function test_url_shape(): void {
		$ok = Ssrf::check_url( 'example.com/page?x=1' );
		$this->assertTrue( $ok['ok'] );
		$this->assertSame( 'https://example.com/page?x=1', $ok['url'] );
		$this->assertSame( 443, $ok['port'] );

		$cases = [
			'http://127.0.0.1/'             => 'private',
			'http://169.254.169.254/latest' => 'private',
			'http://[::1]/'                 => 'private',
			'http://[::ffff:7f00:1]/'       => 'private',
			'http://localhost/'             => 'host',
			'http://app.localhost/'         => 'private',
			'http://db.internal/'           => 'private',
			'http://2130706433/'            => 'host',
			'http://0x7f.0.0.1/'            => 'host',
			'ftp://example.com/'            => 'scheme',
			'file:///etc/passwd'            => 'scheme',
			'javascript:alert(1)'           => 'scheme',
			'gopher://example.com/'         => 'scheme',
			'http://user:pw@example.com/'   => 'userinfo',
			'http://example.com:22/'        => 'port',
			'http://exa mple.com/'          => 'invalid_url',
			''                              => 'invalid_url',
		];
		foreach ( $cases as $url => $error ) {
			$res = Ssrf::check_url( $url );
			$this->assertFalse( $res['ok'], $url );
			$this->assertSame( $error, $res['error'], $url );
		}
	}

	public function test_dns_name_resolving_to_private_ip_is_blocked(): void {
		$resolver = static fn( string $host ): array => [
			'internal.example.com' => [ '10.0.0.5' ],
			'mixed.example.com'    => [ '93.184.216.34', '127.0.0.1' ],
			'v6.example.com'       => [ 'fd00::1' ],
			'good.example.com'     => [ '2606:4700::1', '93.184.216.34' ],
		][ $host ] ?? [];

		$this->assertSame( 'private', Ssrf::resolve( 'internal.example.com', $resolver )['error'] );
		$this->assertSame( 'private', Ssrf::resolve( 'mixed.example.com', $resolver )['error'] );
		$this->assertSame( 'private', Ssrf::resolve( 'v6.example.com', $resolver )['error'] );
		$this->assertSame( 'dns', Ssrf::resolve( 'nxdomain.example.com', $resolver )['error'] );
		$good = Ssrf::resolve( 'good.example.com', $resolver );
		$this->assertTrue( $good['ok'] );
		$this->assertSame( '93.184.216.34', $good['ip'] ); // IPv4 preferred.
	}

	public function test_fetch_pins_ip_and_blocks_redirects_to_private_targets(): void {
		$resolver = static fn( string $host ): array => [
			'site.example.com'     => [ '93.184.216.34' ],
			'evil.example.com'     => [ '93.184.216.35' ],
			'internal.example.com' => [ '10.0.0.5' ],
		][ $host ] ?? [];
		$calls = [];
		$http  = static function ( string $url, string $host, int $port, string $ip ) use ( &$calls ): array {
			$calls[] = [ $url, $ip ];
			$routes  = [
				'https://evil.example.com/a'     => [ 'status' => 302, 'headers' => [ 'Location' => 'http://127.0.0.1/admin' ] ],
				'https://evil.example.com/b'     => [ 'status' => 301, 'headers' => [ 'location' => 'https://internal.example.com/' ] ],
				'https://evil.example.com/c'     => [ 'status' => 302, 'headers' => [ 'location' => 'http://[::1]:80/' ] ],
				'https://evil.example.com/d'     => [ 'status' => 302, 'headers' => [ 'location' => 'http://169.254.169.254/latest/meta-data/' ] ],
				'https://evil.example.com/loop'  => [ 'status' => 302, 'headers' => [ 'location' => '/loop' ] ],
				'https://site.example.com/old'   => [ 'status' => 301, 'headers' => [ 'location' => '/new/' ] ],
				'https://site.example.com/new/'  => [ 'status' => 200, 'headers' => [ 'content-type' => 'text/html' ], 'body' => '<title>x</title>' ],
			];
			return $routes[ $url ] ?? [ 'error' => 'unreachable' ];
		};

		foreach ( [ 'a', 'b', 'c', 'd' ] as $path ) {
			$calls = [];
			$res   = Ssrf::fetch( 'https://evil.example.com/' . $path, $resolver, $http );
			$this->assertFalse( $res['ok'], $path );
			$this->assertSame( 'private', $res['error'], $path );
			$this->assertCount( 1, $calls, 'never connects to the private hop: ' . $path );
		}

		$res = Ssrf::fetch( 'https://evil.example.com/loop', $resolver, $http );
		$this->assertSame( 'redirects', $res['error'] );

		$calls = [];
		$res   = Ssrf::fetch( 'https://site.example.com/old', $resolver, $http );
		$this->assertTrue( $res['ok'] );
		$this->assertSame( 'https://site.example.com/new/', $res['url'] );
		$this->assertSame( 1, $res['redirects'] );
		$this->assertSame( '93.184.216.34', $calls[0][1] ); // Pinned to the vetted address.
	}

	public function test_absolute_url(): void {
		$this->assertSame( 'https://a.com/x', Ssrf::absolute_url( 'https://a.com/p/q', '/x' ) );
		$this->assertSame( 'https://a.com/p/x', Ssrf::absolute_url( 'https://a.com/p/q', 'x' ) );
		$this->assertSame( 'https://b.com/', Ssrf::absolute_url( 'https://a.com/p', '//b.com/' ) );
		$this->assertSame( 'http://c.com/', Ssrf::absolute_url( 'https://a.com/p', 'http://c.com/' ) );
	}
}
