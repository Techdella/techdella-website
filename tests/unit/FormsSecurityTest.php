<?php
/**
 * Forms: token signing/verification, encryption round-trip, retry schedule, rate limit.
 */

use PHPUnit\Framework\TestCase;
use TechDella\Core\Forms\Crypto;
use TechDella\Core\Forms\Retry;
use TechDella\Core\Forms\Token;

final class FormsSecurityTest extends TestCase {

	public static function setUpBeforeClass(): void {
		td_forms_unit_load();
	}

	public function test_token_round_trip_and_failures(): void {
		$t     = new Token( str_repeat( 'k', 32 ) );
		$now   = 1700000000;
		$token = $t->issue( 'audit', $now );
		$this->assertSame( $now, Token::issued_at( $token ) );
		$this->assertSame( '', $t->verify( $token, 'audit', $now + 5 ) );
		$this->assertSame( 'too_fast', $t->verify( $token, 'audit', $now + 1 ) );
		$this->assertSame( 'form', $t->verify( $token, 'contact', $now + 5 ) );
		$this->assertSame( 'expired', $t->verify( $token, 'audit', $now + Token::MAX_AGE_RENDERED + 1 ) );
		$this->assertSame( 'future', $t->verify( $token, 'audit', $now - 3600 ) );
		$this->assertSame( 'signature', ( new Token( str_repeat( 'x', 32 ) ) )->verify( $token, 'audit', $now + 5 ) );
		$this->assertSame( 'signature', $t->verify( str_replace( '.1700000000.', '.1700000001.', $token ), 'audit', $now + 5 ) );
		$this->assertSame( 'malformed', $t->verify( 'abc', 'audit', $now ) );
	}

	public function test_signed_values(): void {
		$t      = new Token( 'secret-secret-secret' );
		$signed = $t->sign( '42', 2000 );
		$this->assertSame( '42', $t->open( $signed, 1999 ) );
		$this->assertNull( $t->open( $signed, 2001 ) );
		$this->assertNull( $t->open( str_replace( 'NDI', 'NDM', $signed ), 1999 ) );
		$this->assertNull( $t->open( 'x.y', 1 ) );
	}

	public function test_encryption_round_trip(): void {
		$c   = new Crypto( Crypto::derive_key( 'salt' ) );
		$enc = $c->encrypt( '{"email":"ada@example.com"}' );
		$this->assertStringNotContainsString( 'ada@example.com', $enc );
		$this->assertNotSame( $enc, $c->encrypt( '{"email":"ada@example.com"}' ), 'random nonce' );
		$this->assertSame( '{"email":"ada@example.com"}', $c->decrypt( $enc ) );
		$this->assertNull( ( new Crypto( Crypto::derive_key( 'other' ) ) )->decrypt( $enc ) );
		$this->assertNull( $c->decrypt( 'not-base64!' ) );
		$this->expectException( InvalidArgumentException::class );
		new Crypto( 'short' );
	}

	public function test_retry_schedule(): void {
		$this->assertSame( [ 60, 300, 1800, 7200, 43200 ], array_map( [ Retry::class, 'next_delay' ], [ 1, 2, 3, 4, 5 ] ) );
		$this->assertNull( Retry::next_delay( 6 ) );
		$this->assertFalse( Retry::should_notify( 4 ) );
		$this->assertTrue( Retry::should_notify( 5 ) );
		$this->assertFalse( Retry::should_notify( 6 ) );
		$this->assertTrue( Retry::should_purge( 0, 14 * 86400 ) );
		$this->assertFalse( Retry::should_purge( 0, 14 * 86400 - 1 ) );
		$this->assertSame( 'http_503', Retry::error_class( 503 ) );
		$this->assertSame( 'wp_error:http_request_failed', Retry::error_class( 0, 'http_request_failed' ) );
	}

	public function test_rate_limit_window(): void {
		$state = null;
		for ( $i = 0; $i < 5; $i++ ) {
			[ $ok, $state ] = Retry::rate_limit( $state, 100 + $i );
			$this->assertTrue( $ok );
		}
		[ $ok, $state ] = Retry::rate_limit( $state, 110 );
		$this->assertFalse( $ok );
		[ $ok ] = Retry::rate_limit( $state, 161 );
		$this->assertTrue( $ok );
	}
}
