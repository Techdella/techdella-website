<?php
/**
 * SSRF guard for the server-side tool fetch (§12.1). Pure PHP, unit-tested.
 *
 * Rules: http/https only, ports 80/443 (filterable in the module), no userinfo,
 * the host is a real DNS name or an IP literal (no "2130706433"-style numeric
 * hosts), and EVERY address the name resolves to must be public — not private,
 * loopback, link-local, CGNAT, multicast, documentation, benchmarking or
 * otherwise reserved, for IPv4 and IPv6 (including IPv4-mapped/compatible,
 * NAT64 and 6to4 forms). The caller pins the connection to the vetted address
 * and repeats the check for every redirect hop.
 */

namespace TechDella\Core\Tools;

defined( 'ABSPATH' ) || exit;

/**
 * URL + IP checks.
 */
final class Ssrf {

	/** Blocked IPv4 ranges (RFC 6890 special-purpose + multicast/reserved). */
	public const BLOCKED_V4 = [
		'0.0.0.0/8',
		'10.0.0.0/8',
		'100.64.0.0/10',
		'127.0.0.0/8',
		'169.254.0.0/16',
		'172.16.0.0/12',
		'192.0.0.0/24',
		'192.0.2.0/24',
		'192.31.196.0/24',
		'192.52.193.0/24',
		'192.88.99.0/24',
		'192.168.0.0/16',
		'192.175.48.0/24',
		'198.18.0.0/15',
		'198.51.100.0/24',
		'203.0.113.0/24',
		'224.0.0.0/4',
		'240.0.0.0/4',
		'255.255.255.255/32',
	];

	/** Blocked IPv6 ranges. IPv4-mapped/compatible addresses are unwrapped and checked as IPv4 first. */
	public const BLOCKED_V6 = [
		'::/128',
		'::1/128',
		'::/96',
		'64:ff9b::/96',
		'64:ff9b:1::/48',
		'100::/64',
		'2001::/23',
		'2001:db8::/32',
		'2002::/16',
		'3fff::/20',
		'5f00::/16',
		'fc00::/7',
		'fe80::/10',
		'fec0::/10',
		'ff00::/8',
	];

	/**
	 * Whether an IP address (v4 or v6) is publicly routable.
	 *
	 * @param string $ip Address (IPv6 may be bracketed).
	 */
	public static function is_public_ip( string $ip ): bool {
		$ip  = trim( $ip, '[] ' );
		$bin = false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? inet_pton( $ip ) : false;
		if ( false === $bin ) {
			return false;
		}
		if ( 4 === strlen( $bin ) ) {
			return ! self::in_any( $bin, self::BLOCKED_V4 );
		}
		// IPv4-mapped ::ffff:a.b.c.d → check the IPv4 part.
		if ( str_repeat( "\0", 10 ) . "\xff\xff" === substr( $bin, 0, 12 ) ) {
			$v4 = (string) inet_ntop( substr( $bin, 12 ) );
			return self::is_public_ip( $v4 );
		}
		return ! self::in_any( $bin, self::BLOCKED_V6 );
	}

	/**
	 * Whether a packed address is inside any CIDR in the list (same family only).
	 *
	 * @param string   $bin   Packed address (inet_pton).
	 * @param string[] $cidrs CIDR list.
	 */
	private static function in_any( string $bin, array $cidrs ): bool {
		foreach ( $cidrs as $cidr ) {
			[ $net, $bits ] = explode( '/', $cidr );
			$net_bin        = inet_pton( $net );
			if ( false === $net_bin || strlen( $net_bin ) !== strlen( $bin ) ) {
				continue;
			}
			if ( self::prefix_match( $bin, $net_bin, (int) $bits ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Compare the first $bits bits of two packed addresses.
	 *
	 * @param string $a    Packed address.
	 * @param string $b    Packed network.
	 * @param int    $bits Prefix length.
	 */
	private static function prefix_match( string $a, string $b, int $bits ): bool {
		$bytes = intdiv( $bits, 8 );
		if ( $bytes > 0 && substr( $a, 0, $bytes ) !== substr( $b, 0, $bytes ) ) {
			return false;
		}
		$rest = $bits % 8;
		if ( 0 === $rest ) {
			return true;
		}
		$mask = ( 0xff << ( 8 - $rest ) ) & 0xff;
		return ( ord( $a[ $bytes ] ) & $mask ) === ( ord( $b[ $bytes ] ) & $mask );
	}

	/**
	 * Normalise and check a URL's shape (no DNS).
	 *
	 * @param string $url   URL typed by the visitor (scheme optional: https:// is added).
	 * @param int[]  $ports Allowed ports.
	 * @return array{ok:bool,error:string,url:string,scheme:string,host:string,port:int}
	 */
	public static function check_url( string $url, array $ports = [ 80, 443 ] ): array {
		$fail = static fn( string $error ): array => [
			'ok'     => false,
			'error'  => $error,
			'url'    => '',
			'scheme' => '',
			'host'   => '',
			'port'   => 0,
		];
		$url  = trim( $url );
		if ( '' === $url || strlen( $url ) > 2048 || preg_match( '/[\s\x00-\x1f\x7f]/', $url ) ) {
			return $fail( 'invalid_url' );
		}
		if ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $url ) ) {
			if ( preg_match( '#^[a-z][a-z0-9+.-]*:#i', $url ) && ! preg_match( '#^[^:/]+:\d+#', $url ) ) {
				return $fail( 'scheme' ); // javascript:, file:, data: …
			}
			$url = 'https://' . ltrim( $url, '/' );
		}
		$parts = parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure class, no WordPress.
		$scheme = strtolower( (string) ( is_array( $parts ) ? ( $parts['scheme'] ?? '' ) : strtok( $url, ':' ) ) );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return $fail( 'scheme' );
		}
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return $fail( 'invalid_url' );
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return $fail( 'userinfo' );
		}
		$port = isset( $parts['port'] ) ? (int) $parts['port'] : ( 'https' === $scheme ? 443 : 80 );
		if ( ! in_array( $port, $ports, true ) ) {
			return $fail( 'port' );
		}
		$host = strtolower( rtrim( trim( (string) $parts['host'], '[]' ), '.' ) );
		if ( ! self::is_ip( $host ) && ! self::is_hostname( $host ) ) {
			return $fail( 'host' );
		}
		if ( 'localhost' === $host || str_ends_with( $host, '.localhost' ) || str_ends_with( $host, '.local' ) || str_ends_with( $host, '.internal' ) ) {
			return $fail( 'private' );
		}
		if ( self::is_ip( $host ) && ! self::is_public_ip( $host ) ) {
			return $fail( 'private' );
		}
		$path = (string) ( $parts['path'] ?? '/' );
		$path = '' === $path ? '/' : $path;
		$auth = str_contains( $host, ':' ) ? '[' . $host . ']' : $host;
		$norm = $scheme . '://' . $auth . ( isset( $parts['port'] ) ? ':' . $port : '' ) . $path . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
		return [
			'ok'     => true,
			'error'  => '',
			'url'    => $norm,
			'scheme' => $scheme,
			'host'   => $host,
			'port'   => $port,
		];
	}

	/**
	 * Whether the string is an IP literal.
	 *
	 * @param string $host Host.
	 */
	public static function is_ip( string $host ): bool {
		return false !== filter_var( trim( $host, '[]' ), FILTER_VALIDATE_IP );
	}

	/**
	 * A DNS name with a letter-led TLD (rejects numeric/hex hosts like 2130706433 or 0x7f.1).
	 *
	 * @param string $host Host (lowercase).
	 */
	public static function is_hostname( string $host ): bool {
		if ( strlen( $host ) > 253 || ! str_contains( $host, '.' ) ) {
			return false;
		}
		$labels = explode( '.', $host );
		foreach ( $labels as $label ) {
			if ( ! preg_match( '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $label ) ) {
				return false;
			}
		}
		return (bool) preg_match( '/^(?:[a-z]{2,63}|xn--[a-z0-9-]{1,59})$/', (string) end( $labels ) );
	}

	/**
	 * Resolve a host and vet every address. One private address blocks the host
	 * (so a DNS answer can't smuggle an internal address in next to a public one).
	 *
	 * @param string                          $host     Host (name or IP literal).
	 * @param callable(string):array<string> $resolver Returns the A/AAAA addresses for a name.
	 * @return array{ok:bool,error:string,ip:string,ips:string[]}
	 */
	public static function resolve( string $host, callable $resolver ): array {
		$host = trim( $host, '[]' );
		$ips  = self::is_ip( $host ) ? [ $host ] : array_values( array_unique( array_filter( array_map( 'strval', (array) $resolver( $host ) ) ) ) );
		if ( ! $ips ) {
			return [
				'ok'    => false,
				'error' => 'dns',
				'ip'    => '',
				'ips'   => [],
			];
		}
		foreach ( $ips as $ip ) {
			if ( ! self::is_public_ip( $ip ) ) {
				return [
					'ok'    => false,
					'error' => 'private',
					'ip'    => '',
					'ips'   => $ips,
				];
			}
		}
		// Prefer IPv4 (wider reachability from shared hosting).
		$v4 = array_values( array_filter( $ips, static fn( $ip ) => false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) );
		return [
			'ok'    => true,
			'error' => '',
			'ip'    => $v4[0] ?? $ips[0],
			'ips'   => $ips,
		];
	}

	/**
	 * Resolve a redirect Location against the current URL.
	 *
	 * @param string $base     Current absolute URL.
	 * @param string $location Location header value.
	 */
	public static function absolute_url( string $base, string $location ): string {
		$location = trim( $location );
		if ( preg_match( '#^[a-z][a-z0-9+.-]*:#i', $location ) ) {
			return $location;
		}
		$b      = parse_url( $base ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		$scheme = (string) ( $b['scheme'] ?? 'https' );
		$host   = (string) ( $b['host'] ?? '' );
		$origin = $scheme . '://' . $host . ( isset( $b['port'] ) ? ':' . $b['port'] : '' );
		if ( str_starts_with( $location, '//' ) ) {
			return $scheme . ':' . $location;
		}
		if ( str_starts_with( $location, '/' ) ) {
			return $origin . $location;
		}
		if ( str_starts_with( $location, '?' ) ) {
			return $origin . (string) ( $b['path'] ?? '/' ) . $location;
		}
		$dir = preg_replace( '#/[^/]*$#', '/', (string) ( $b['path'] ?? '/' ) );
		return $origin . ( '' === $dir ? '/' : $dir ) . $location;
	}
	/**
	 * Follow a URL through at most $max_redirects redirects, vetting and pinning
	 * every hop. The transport is injected so this loop is unit-testable.
	 *
	 * $http( string $url, string $host, int $port, string $ip, float $timeout ) must connect to
	 * $ip only (never re-resolve $host) and return
	 * [ 'status' => int, 'headers' => array<string,string> (lowercase), 'body' => string ]
	 * or [ 'error' => 'timeout'|'unreachable' ].
	 *
	 * @param string                          $url           Start URL.
	 * @param callable(string):array<string> $resolver      DNS resolver.
	 * @param callable                        $http          Pinned transport.
	 * @param float                           $timeout       Seconds for all hops together.
	 * @param int[]                           $ports         Allowed ports.
	 * @param int                             $max_redirects Max redirects.
	 * @return array{ok:bool,error:string,status:int,headers:array<string,string>,body:string,url:string,redirects:int}
	 */
	public static function fetch( string $url, callable $resolver, callable $http, float $timeout = 5.0, array $ports = [ 80, 443 ], int $max_redirects = 3 ): array {
		$out      = static fn( bool $ok, string $error, string $final = '', int $hops = 0, int $status = 0, array $headers = [], string $body = '' ): array => [
			'ok'        => $ok,
			'error'     => $error,
			'status'    => $status,
			'headers'   => $headers,
			'body'      => $body,
			'url'       => $final,
			'redirects' => $hops,
		];
		$deadline = microtime( true ) + $timeout;
		for ( $hop = 0; $hop <= $max_redirects; $hop++ ) {
			$check = self::check_url( $url, $ports );
			if ( ! $check['ok'] ) {
				return $out( false, $check['error'], $url, $hop );
			}
			$dns = self::resolve( $check['host'], $resolver );
			if ( ! $dns['ok'] ) {
				return $out( false, $dns['error'], $check['url'], $hop );
			}
			$left = $deadline - microtime( true );
			if ( $left <= 0.2 ) {
				return $out( false, 'timeout', $check['url'], $hop );
			}
			$res = (array) $http( $check['url'], $check['host'], $check['port'], $dns['ip'], $left );
			if ( isset( $res['error'] ) ) {
				return $out( false, 'timeout' === $res['error'] ? 'timeout' : 'unreachable', $check['url'], $hop );
			}
			$status  = (int) ( $res['status'] ?? 0 );
			$headers = array_change_key_case( (array) ( $res['headers'] ?? [] ), CASE_LOWER );
			if ( $status >= 300 && $status < 400 && ! empty( $headers['location'] ) ) {
				if ( $hop === $max_redirects ) {
					return $out( false, 'redirects', $check['url'], $hop, $status );
				}
				$url = self::absolute_url( $check['url'], (string) $headers['location'] );
				continue;
			}
			return $out( true, '', $check['url'], $hop, $status, $headers, (string) ( $res['body'] ?? '' ) );
		}
		return $out( false, 'redirects', $url, $max_redirects );
	}
}
