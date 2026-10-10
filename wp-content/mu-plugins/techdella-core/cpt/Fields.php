<?php
/**
 * Field definitions (§7): the ACF JSON in acf-json/ is the single source of
 * truth. This class turns those groups into post-meta schemas so every field
 * also works without ACF (or without ACF Pro) over REST, WP-CLI and in
 * templates through td_field().
 *
 * Pure PHP: no WordPress calls, so it is unit-tested directly.
 */

namespace TechDella\Core\Cpt;

defined( 'ABSPATH' ) || exit;

/**
 * ACF JSON → meta schema mapping, sanitising and validation.
 */
final class Fields {

	/**
	 * ACF field types that only exist in ACF Pro (D4).
	 */
	public const PRO_TYPES = [ 'repeater', 'flexible_content', 'gallery', 'clone' ];

	/**
	 * Layout-only field types that store nothing.
	 */
	private const NO_VALUE_TYPES = [ 'tab', 'message', 'accordion', 'separator' ];

	/**
	 * Parsed groups, keyed by post type.
	 *
	 * @var array<string,array<string,array<string,mixed>>>
	 */
	private array $by_type = [];

	/**
	 * Load every group_*.json in a directory.
	 *
	 * @param string $dir Directory with ACF JSON files.
	 */
	public static function from_dir( string $dir ): self {
		$groups = [];
		foreach ( (array) glob( rtrim( $dir, '/' ) . '/group_*.json' ) as $file ) {
			$json = json_decode( (string) file_get_contents( (string) $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( is_array( $json ) ) {
				$groups[] = $json;
			}
		}
		return new self( $groups );
	}

	/**
	 * Constructor.
	 *
	 * @param array<int,array<string,mixed>> $groups ACF field groups.
	 */
	public function __construct( array $groups ) {
		foreach ( $groups as $group ) {
			if ( isset( $group['active'] ) && ! $group['active'] ) {
				continue;
			}
			foreach ( self::group_post_types( $group ) as $type ) {
				foreach ( (array) ( $group['fields'] ?? [] ) as $field ) {
					if ( ! is_array( $field ) || empty( $field['name'] ) || in_array( $field['type'] ?? '', self::NO_VALUE_TYPES, true ) ) {
						continue;
					}
					$this->by_type[ $type ][ (string) $field['name'] ] = $field;
				}
			}
		}
	}

	/**
	 * Post types a group is attached to (post_type == X rules only).
	 *
	 * @param array<string,mixed> $group ACF group.
	 * @return string[]
	 */
	private static function group_post_types( array $group ): array {
		$types = [];
		foreach ( (array) ( $group['location'] ?? [] ) as $and ) {
			foreach ( (array) $and as $rule ) {
				if ( 'post_type' === ( $rule['param'] ?? '' ) && '==' === ( $rule['operator'] ?? '' ) ) {
					$types[] = (string) $rule['value'];
				}
			}
		}
		return array_values( array_unique( $types ) );
	}

	/**
	 * Post types that have fields.
	 *
	 * @return string[]
	 */
	public function post_types(): array {
		return array_keys( $this->by_type );
	}

	/**
	 * Raw ACF field definitions for a post type, keyed by field name.
	 *
	 * @param string $post_type Post type.
	 * @return array<string,array<string,mixed>>
	 */
	public function fields( string $post_type ): array {
		return $this->by_type[ $post_type ] ?? [];
	}

	/**
	 * Whether a field needs ACF Pro to be edited with ACF.
	 *
	 * @param array<string,mixed> $field ACF field.
	 */
	public static function is_pro_only( array $field ): bool {
		return in_array( $field['type'] ?? '', self::PRO_TYPES, true );
	}

	/**
	 * Whether a field stores an array (JSON-schema array/object).
	 *
	 * @param array<string,mixed> $field ACF field.
	 */
	public static function is_structured( array $field ): bool {
		return in_array( self::schema( $field )['type'], [ 'array', 'object' ], true );
	}

	/**
	 * JSON schema for one ACF field (used for register_post_meta show_in_rest).
	 *
	 * @param array<string,mixed> $field ACF field.
	 * @return array<string,mixed>
	 */
	public static function schema( array $field ): array {
		$type     = (string) ( $field['type'] ?? 'text' );
		$multiple = ! empty( $field['multiple'] );
		$help     = isset( $field['instructions'] ) ? (string) $field['instructions'] : '';
		$schema   = [ 'type' => 'string' ];

		switch ( $type ) {
			case 'number':
			case 'range':
				$schema = [ 'type' => 'number' ];
				break;
			case 'true_false':
				$schema = [ 'type' => 'boolean' ];
				break;
			case 'image':
			case 'file':
				$schema = [ 'type' => 'integer' ];
				break;
			case 'post_object':
			case 'user':
			case 'taxonomy':
				$many   = $multiple || in_array( $field['field_type'] ?? '', [ 'checkbox', 'multi_select' ], true );
				$schema = $many ? [
					'type'  => 'array',
					'items' => [ 'type' => 'integer' ],
				] : [ 'type' => 'integer' ];
				break;
			case 'relationship':
			case 'gallery':
				$schema = [
					'type'  => 'array',
					'items' => [ 'type' => 'integer' ],
				];
				break;
			case 'checkbox':
				$schema = [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ],
				];
				break;
			case 'select':
				if ( $multiple ) {
					$schema = [
						'type'  => 'array',
						'items' => [ 'type' => 'string' ],
					];
				}
				break;
			case 'link':
				$schema = [
					'type'                 => 'object',
					'properties'           => [
						'title'  => [ 'type' => 'string' ],
						'url'    => [ 'type' => 'string' ],
						'target' => [ 'type' => 'string' ],
					],
					'additionalProperties' => false,
				];
				break;
			case 'repeater':
				$schema = [
					'type'  => 'array',
					'items' => self::object_schema( (array) ( $field['sub_fields'] ?? [] ) ),
				];
				break;
			case 'flexible_content':
				$props = [ 'acf_fc_layout' => [ 'type' => 'string' ] ];
				foreach ( (array) ( $field['layouts'] ?? [] ) as $layout ) {
					$props += self::object_schema( (array) ( $layout['sub_fields'] ?? [] ) )['properties'];
				}
				$schema = [
					'type'  => 'array',
					'items' => [
						'type'                 => 'object',
						'properties'           => $props,
						'additionalProperties' => false,
					],
				];
				break;
		}
		if ( '' !== $help ) {
			$schema['description'] = $help;
		}
		return $schema;
	}

	/**
	 * Object schema for a set of sub fields.
	 *
	 * @param array<int,array<string,mixed>> $sub_fields Sub fields.
	 * @return array<string,mixed>
	 */
	private static function object_schema( array $sub_fields ): array {
		$props = [];
		foreach ( $sub_fields as $sub ) {
			if ( ! empty( $sub['name'] ) ) {
				$sub_schema = self::schema( $sub );
				unset( $sub_schema['description'] );
				$props[ (string) $sub['name'] ] = $sub_schema;
			}
		}
		return [
			'type'                 => 'object',
			'properties'           => $props,
			'additionalProperties' => false,
		];
	}

	/**
	 * Default value for a schema (valid against it, for REST).
	 *
	 * @param array<string,mixed> $schema JSON schema.
	 * @return mixed
	 */
	public static function default_for( array $schema ) {
		switch ( $schema['type'] ) {
			case 'array':
				return [];
			case 'object':
				return [];
			case 'boolean':
				return false;
			case 'integer':
				return 0;
			case 'number':
				return 0;
			default:
				return '';
		}
	}

	/**
	 * Coerce a value to a schema: casts scalars, drops unknown object keys.
	 * Used as the meta sanitize callback, so ACF's string IDs ("12") and
	 * "1"/"0" booleans are stored in one predictable shape.
	 *
	 * @param mixed               $value  Raw value.
	 * @param array<string,mixed> $schema JSON schema.
	 * @return mixed
	 */
	public static function sanitize( $value, array $schema ) {
		switch ( $schema['type'] ) {
			case 'boolean':
				return in_array( $value, [ true, 1, '1', 'true', 'on', 'yes' ], true );
			case 'integer':
				return is_numeric( $value ) ? (int) $value : 0;
			case 'number':
				return is_numeric( $value ) ? $value + 0 : 0;
			case 'string':
				return is_scalar( $value ) ? (string) $value : '';
			case 'array':
				if ( ! is_array( $value ) ) {
					return [];
				}
				$items = (array) ( $schema['items'] ?? [ 'type' => 'string' ] );
				$out   = [];
				foreach ( $value as $item ) {
					if ( 'object' === ( $items['type'] ?? '' ) && ! is_array( $item ) ) {
						continue;
					}
					if ( 'integer' === ( $items['type'] ?? '' ) && ( ! is_numeric( $item ) || (int) $item <= 0 ) ) {
						continue;
					}
					$out[] = self::sanitize( $item, $items );
				}
				return $out;
			case 'object':
				if ( ! is_array( $value ) ) {
					return [];
				}
				$out = [];
				foreach ( (array) ( $schema['properties'] ?? [] ) as $prop => $prop_schema ) {
					if ( array_key_exists( $prop, $value ) ) {
						$out[ $prop ] = self::sanitize( $value[ $prop ], (array) $prop_schema );
					}
				}
				return $out;
		}
		return $value;
	}

	/**
	 * Validate a decoded value against a schema. Returns human-readable errors
	 * (empty array = valid). Used by the no-Pro JSON fallback box.
	 *
	 * @param mixed               $value  Decoded value.
	 * @param array<string,mixed> $schema JSON schema.
	 * @param string              $path   Path for messages.
	 * @return string[]
	 */
	public static function validate( $value, array $schema, string $path = 'value' ): array {
		$type = $schema['type'];
		if ( 'array' === $type ) {
			if ( ! is_array( $value ) || ( [] !== $value && array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) ) {
				return [ "{$path} must be a list: [ … ]" ];
			}
			$errors = [];
			foreach ( $value as $i => $item ) {
				$errors = array_merge( $errors, self::validate( $item, (array) ( $schema['items'] ?? [ 'type' => 'string' ] ), "{$path}[{$i}]" ) );
			}
			return $errors;
		}
		if ( 'object' === $type ) {
			if ( ! is_array( $value ) || ( [] !== $value && array_keys( $value ) === range( 0, count( $value ) - 1 ) ) ) {
				return [ "{$path} must be an object: { … }" ];
			}
			$props  = (array) ( $schema['properties'] ?? [] );
			$errors = [];
			foreach ( $value as $key => $item ) {
				if ( ! isset( $props[ $key ] ) ) {
					$errors[] = "{$path}.{$key} is not a known field (allowed: " . implode( ', ', array_keys( $props ) ) . ')';
					continue;
				}
				$errors = array_merge( $errors, self::validate( $item, (array) $props[ $key ], "{$path}.{$key}" ) );
			}
			return $errors;
		}
		$ok = match ( $type ) {
			'integer' => is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ),
			'number'  => is_int( $value ) || is_float( $value ),
			'boolean' => is_bool( $value ),
			default   => is_string( $value ) || is_int( $value ) || is_float( $value ),
		};
		return $ok ? [] : [ "{$path} must be a {$type}" ];
	}

	/**
	 * An example value for a schema, shown as help in the JSON fallback box.
	 *
	 * @param array<string,mixed> $schema JSON schema.
	 * @return mixed
	 */
	public static function example( array $schema ) {
		switch ( $schema['type'] ) {
			case 'array':
				return [ self::example( (array) ( $schema['items'] ?? [ 'type' => 'string' ] ) ) ];
			case 'object':
				$out = [];
				foreach ( (array) ( $schema['properties'] ?? [] ) as $prop => $prop_schema ) {
					$out[ $prop ] = 'acf_fc_layout' === $prop ? 'stat' : self::example( (array) $prop_schema );
				}
				return $out;
			case 'integer':
				return 123;
			case 'number':
				return 1;
			case 'boolean':
				return true;
			default:
				return '…';
		}
	}

	/**
	 * Convert ACF repeater/flexible rows keyed by field key (field_…) to rows
	 * keyed by field name (our canonical storage), recursively.
	 *
	 * @param mixed               $value ACF input value.
	 * @param array<string,mixed> $field ACF field.
	 * @return mixed
	 */
	public static function rows_from_acf( $value, array $field ) {
		$type = $field['type'] ?? '';
		if ( ! is_array( $value ) || ! in_array( $type, [ 'repeater', 'flexible_content' ], true ) ) {
			return $value;
		}
		$rows = [];
		foreach ( $value as $row_id => $row ) {
			if ( 'acfcloneindex' === $row_id || ! is_array( $row ) ) {
				continue;
			}
			$subs = self::sub_fields_for_row( $field, $row );
			$out  = 'flexible_content' === $type ? [ 'acf_fc_layout' => (string) ( $row['acf_fc_layout'] ?? '' ) ] : [];
			foreach ( $subs as $sub ) {
				$key = (string) $sub['key'];
				if ( array_key_exists( $key, $row ) ) {
					$out[ (string) $sub['name'] ] = self::rows_from_acf( $row[ $key ], $sub );
				} elseif ( array_key_exists( (string) $sub['name'], $row ) ) {
					$out[ (string) $sub['name'] ] = self::rows_from_acf( $row[ (string) $sub['name'] ], $sub );
				}
			}
			$rows[] = $out;
		}
		return $rows;
	}

	/**
	 * Convert canonical rows (keyed by name) to ACF's loaded format (keyed by
	 * field key), recursively — what acf/pre_load_value must return.
	 *
	 * @param mixed               $value Stored value.
	 * @param array<string,mixed> $field ACF field.
	 * @return mixed
	 */
	public static function rows_to_acf( $value, array $field ) {
		$type = $field['type'] ?? '';
		if ( ! in_array( $type, [ 'repeater', 'flexible_content' ], true ) ) {
			return $value;
		}
		if ( ! is_array( $value ) ) {
			return [];
		}
		$rows = [];
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$out = 'flexible_content' === $type ? [ 'acf_fc_layout' => (string) ( $row['acf_fc_layout'] ?? '' ) ] : [];
			foreach ( self::sub_fields_for_row( $field, $row ) as $sub ) {
				if ( array_key_exists( (string) $sub['name'], $row ) ) {
					$out[ (string) $sub['key'] ] = self::rows_to_acf( $row[ (string) $sub['name'] ], $sub );
				}
			}
			$rows[] = $out;
		}
		return $rows;
	}

	/**
	 * Sub fields that apply to a row (the row's layout for flexible content).
	 *
	 * @param array<string,mixed> $field ACF field.
	 * @param array<mixed>        $row   Row.
	 * @return array<int,array<string,mixed>>
	 */
	private static function sub_fields_for_row( array $field, array $row ): array {
		if ( 'flexible_content' !== ( $field['type'] ?? '' ) ) {
			return (array) ( $field['sub_fields'] ?? [] );
		}
		foreach ( (array) ( $field['layouts'] ?? [] ) as $layout ) {
			if ( ( $layout['name'] ?? '' ) === ( $row['acf_fc_layout'] ?? null ) ) {
				return (array) ( $layout['sub_fields'] ?? [] );
			}
		}
		return [];
	}
}
