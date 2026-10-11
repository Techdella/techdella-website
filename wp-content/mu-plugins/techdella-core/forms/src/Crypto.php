<?php
/**
 * Queue payload encryption (§11.2): libsodium secretbox. The key is derived
 * from wp_salt('secure_auth'), so payloads are never stored in plain text and
 * a database dump alone cannot read them.
 *
 * Pure PHP (ext-sodium), unit-tested.
 */

namespace TechDella\Core\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Secretbox wrapper.
 */
final class Crypto {

	/**
	 * 32-byte key.
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * Constructor.
	 *
	 * @param string $key 32-byte key (use Crypto::derive_key()).
	 * @throws \InvalidArgumentException When the key length is wrong.
	 */
	public function __construct( string $key ) {
		if ( SODIUM_CRYPTO_SECRETBOX_KEYBYTES !== strlen( $key ) ) {
			throw new \InvalidArgumentException( 'Crypto key must be 32 bytes.' );
		}
		$this->key = $key;
	}

	/**
	 * Derive a 32-byte key from a salt string.
	 *
	 * @param string $salt e.g. wp_salt( 'secure_auth' ).
	 */
	public static function derive_key( string $salt ): string {
		return sodium_crypto_generichash( 'td-forms-queue|v1|' . $salt, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}

	/**
	 * Encrypt → base64( nonce . ciphertext ).
	 *
	 * @param string $plain Plain text.
	 */
	public function encrypt( string $plain ): string {
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		return base64_encode( $nonce . sodium_crypto_secretbox( $plain, $nonce, $this->key ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binary-safe storage.
	}

	/**
	 * Decrypt; null when tampered or the key changed (salts rotated).
	 *
	 * @param string $encoded Output of encrypt().
	 */
	public function decrypt( string $encoded ): ?string {
		$raw = base64_decode( $encoded, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
			return null;
		}
		$plain = sodium_crypto_secretbox_open( substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), $this->key );
		return false === $plain ? null : $plain;
	}
}
