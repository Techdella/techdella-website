<?php
/**
 * Validate and sanitise a submission against a form's field schema (§11.2 step 2).
 *
 * Pure PHP (only __() from WordPress), so it is unit-tested without WordPress.
 * Unknown input keys are dropped. Every field in the schema ends up in the
 * clean array, even when invalid, so the no-JS fallback can re-fill the form.
 */

namespace TechDella\Core\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Field-schema validator.
 */
final class Validator {

	/**
	 * Validate input.
	 *
	 * @param array<string,array<string,mixed>> $fields Field schema (name => definition).
	 * @param array<string,mixed>               $input  Raw input.
	 * @return array{0: array<string,mixed>, 1: array<string,string>} [ clean, errors ].
	 */
	public static function validate( array $fields, array $input ): array {
		$clean  = [];
		$errors = [];
		foreach ( $fields as $name => $field ) {
			[ $value, $error ] = self::field( $field, $input[ $name ] ?? null );
			$clean[ $name ]    = $value;
			if ( '' !== $error ) {
				$errors[ $name ] = $error;
			}
		}
		return [ $clean, $errors ];
	}

	/**
	 * Validate one field.
	 *
	 * @param array<string,mixed> $field Definition.
	 * @param mixed               $raw   Raw value.
	 * @return array{0: mixed, 1: string} [ value, error ].
	 */
	public static function field( array $field, $raw ): array {
		$type     = (string) ( $field['type'] ?? 'text' );
		$required = ! empty( $field['required'] );
		$max      = (int) ( $field['max'] ?? ( 'textarea' === $type ? 4000 : 200 ) );

		switch ( $type ) {
			case 'checkbox':
				$value = self::truthy( $raw );
				if ( $required && ! $value ) {
					return [ false, __( 'Please tick this box to continue.', 'techdella' ) ];
				}
				return [ $value, '' ];

			case 'pills':
				if ( ! empty( $field['multiple'] ) ) {
					$values  = is_array( $raw ) ? $raw : ( null === $raw || '' === $raw ? [] : [ $raw ] );
					$allowed = array_map( 'strval', array_keys( (array) ( $field['options'] ?? [] ) ) );
					$out     = [];
					$bad     = false;
					foreach ( $values as $v ) {
						if ( ! is_scalar( $v ) ) {
							$bad = true;
							continue;
						}
						$v = (string) $v;
						if ( in_array( $v, $allowed, true ) ) {
							$out[] = $v;
						} elseif ( '' !== $v ) {
							$bad = true;
						}
					}
					$out = array_values( array_unique( $out ) );
					if ( $bad ) {
						return [ $out, __( 'Choose from the options shown.', 'techdella' ) ];
					}
					if ( $required && ! $out ) {
						return [ $out, __( 'Choose at least one option.', 'techdella' ) ];
					}
					return [ $out, '' ];
				}
				// Single pills behave like a select.
				// Fall through.
			case 'select':
				$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';
				if ( '' === $value ) {
					return [ '', $required ? __( 'Choose an option.', 'techdella' ) : '' ];
				}
				if ( ! array_key_exists( $value, (array) ( $field['options'] ?? [] ) ) ) {
					return [ '', __( 'Choose from the options shown.', 'techdella' ) ];
				}
				return [ $value, '' ];

			case 'hidden':
				if ( 'json' === ( $field['format'] ?? '' ) ) {
					return self::json( $raw, $required, $max );
				}
				$value = self::text( $raw, false );
				if ( '' === $value ) {
					return [ '', $required ? __( 'This form is missing information. Reload the page and try again.', 'techdella' ) : '' ];
				}
				return [ self::cut( $value, $max ), '' ];
		}

		$value = self::text( $raw, 'textarea' === $type );
		if ( '' === $value ) {
			return [ '', $required ? self::required_message( $type ) : '' ];
		}
		if ( self::len( $value ) > $max ) {
			/* translators: %d: maximum number of characters. */
			return [ self::cut( $value, $max ), sprintf( __( 'Keep this under %d characters.', 'techdella' ), $max ) ];
		}

		if ( 'email' === $type ) {
			$value = strtolower( $value );
			if ( false === filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
				return [ $value, __( 'Enter a valid email address, like name@company.com.', 'techdella' ) ];
			}
		} elseif ( 'tel' === $type ) {
			$digits = (string) preg_replace( '/\D+/', '', $value );
			if ( ! str_starts_with( $value, '+' ) || strlen( $digits ) < 7 || strlen( $digits ) > 15 || preg_match( '/[^\d\s()+.\-]/', $value ) ) {
				return [ $value, __( 'Enter your number with the country code, like +234 803 000 0000.', 'techdella' ) ];
			}
		} elseif ( 'url' === $type ) {
			$url = self::url( $value );
			if ( '' === $url ) {
				return [ $value, __( 'Enter a valid web address, like https://yourcompany.com.', 'techdella' ) ];
			}
			$value = $url;
		}
		return [ $value, '' ];
	}

	/**
	 * Normalise a URL: add https:// when no scheme is given; http(s) only.
	 *
	 * @param string $value Raw.
	 */
	public static function url( string $value ): string {
		$value = trim( $value );
		if ( '' === $value || preg_match( '/\s/', $value ) ) {
			return '';
		}
		if ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $value ) ) {
			$value = 'https://' . ltrim( $value, '/' );
		}
		if ( false === filter_var( $value, FILTER_VALIDATE_URL ) ) {
			return '';
		}
		$scheme = strtolower( (string) parse_url( $value, PHP_URL_SCHEME ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure class.
		$host   = (string) parse_url( $value, PHP_URL_HOST ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) || ! str_contains( $host, '.' ) ) {
			return '';
		}
		return $value;
	}

	/**
	 * Plain text: strip tags and control characters, normalise whitespace.
	 *
	 * @param mixed $raw       Raw value.
	 * @param bool  $multiline Keep line breaks.
	 */
	public static function text( $raw, bool $multiline ): string {
		if ( ! is_scalar( $raw ) ) {
			return '';
		}
		$value = (string) $raw;
		if ( ! mb_check_encoding( $value, 'UTF-8' ) ) {
			$value = mb_convert_encoding( $value, 'UTF-8', 'UTF-8' );
		}
		$value = strip_tags( $value ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- pure class, no WP.
		$value = str_replace( [ "\r\n", "\r" ], "\n", $value );
		$value = (string) preg_replace( '/[\x00-\x09\x0B-\x1F\x7F]/u', ' ', $value );
		if ( $multiline ) {
			$value = (string) preg_replace( '/[ \t]+/u', ' ', $value );
			$value = (string) preg_replace( "/\n{3,}/", "\n\n", $value );
		} else {
			$value = (string) preg_replace( '/\s+/u', ' ', $value );
		}
		return trim( $value );
	}

	/**
	 * Decode a JSON object of scalars (e.g. the MVP calculator inputs).
	 *
	 * @param mixed $raw      Raw (string or array).
	 * @param bool  $required Required.
	 * @param int   $max      Max encoded length.
	 * @return array{0: array<string,mixed>, 1: string}
	 */
	private static function json( $raw, bool $required, int $max ): array {
		if ( is_string( $raw ) && '' !== trim( $raw ) ) {
			if ( strlen( $raw ) > $max ) {
				return [ [], __( 'This form is missing information. Reload the page and try again.', 'techdella' ) ];
			}
			$raw = json_decode( $raw, true );
		}
		if ( ! is_array( $raw ) || ! $raw ) {
			return [ [], $required ? __( 'This form is missing information. Reload the page and try again.', 'techdella' ) : '' ];
		}
		$out = [];
		foreach ( $raw as $k => $v ) {
			$k = substr( (string) preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $k ) ), 0, 40 );
			if ( '' === $k || count( $out ) >= 40 ) {
				continue;
			}
			if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) {
				$out[ $k ] = $v;
			} elseif ( is_string( $v ) ) {
				$out[ $k ] = self::cut( self::text( $v, false ), 200 );
			} elseif ( is_array( $v ) && array_is_list( $v ) ) {
				$out[ $k ] = array_values( array_map( static fn( $x ) => self::cut( self::text( $x, false ), 100 ), array_filter( $v, 'is_scalar' ) ) );
			}
		}
		return [ $out, '' ];
	}

	/**
	 * Checkbox truthiness.
	 *
	 * @param mixed $raw Raw.
	 */
	public static function truthy( $raw ): bool {
		if ( is_bool( $raw ) ) {
			return $raw;
		}
		return is_scalar( $raw ) && in_array( strtolower( (string) $raw ), [ '1', 'on', 'yes', 'true' ], true );
	}

	/**
	 * Required-field message per type.
	 *
	 * @param string $type Field type.
	 */
	private static function required_message( string $type ): string {
		if ( 'email' === $type ) {
			return __( 'Enter your email address.', 'techdella' );
		}
		if ( 'url' === $type ) {
			return __( 'Enter your website address.', 'techdella' );
		}
		return __( 'This field is required.', 'techdella' );
	}

	/**
	 * Multibyte length.
	 *
	 * @param string $s String.
	 */
	private static function len( string $s ): int {
		return mb_strlen( $s, 'UTF-8' );
	}

	/**
	 * Multibyte cut.
	 *
	 * @param string $s   String.
	 * @param int    $max Max chars.
	 */
	private static function cut( string $s, int $max ): string {
		return mb_substr( $s, 0, $max, 'UTF-8' );
	}
}
