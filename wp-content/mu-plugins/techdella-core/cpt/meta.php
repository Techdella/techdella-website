<?php
/**
 * Fields (§7, D4): ACF JSON load/save paths, post meta registration for every
 * field (REST + WP-CLI + templates work without ACF Pro), a bridge so ACF Pro
 * repeaters read/write the same canonical meta, and a fallback meta box for
 * fields ACF cannot edit on this install.
 *
 * Storage contract: meta key = field name. Repeater/flexible values are one
 * meta row holding a list of objects keyed by sub-field name, e.g.
 * faq = [ { question, answer }, … ]. td_field() returns that shape either way.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Cpt\Fields;

/*
 * ACF JSON lives in techdella-core/acf-json (version-controlled).
 */
add_filter(
	'acf/settings/load_json',
	static function ( $paths ): array {
		$paths   = (array) $paths;
		$paths[] = TD_CORE_DIR . '/acf-json';
		return array_values( array_unique( $paths ) );
	}
);
add_filter( 'acf/settings/save_json', static fn() => TD_CORE_DIR . '/acf-json' );

/**
 * Whether an ACF field (or group) key belongs to our JSON.
 *
 * @param array<string,mixed> $field ACF field.
 */
function td_is_td_acf_field( array $field ): bool {
	return str_starts_with( (string) ( $field['key'] ?? '' ), 'field_td_' )
		&& str_starts_with( (string) ( $field['parent'] ?? '' ), 'group_td_' );
}

/**
 * Register every field as post meta with a JSON schema.
 */
add_action(
	'init',
	static function () {
		$fields = td_cpt_fields();
		foreach ( $fields->post_types() as $type ) {
			if ( ! post_type_exists( $type ) ) {
				continue;
			}
			foreach ( $fields->fields( $type ) as $name => $field ) {
				$schema = Fields::schema( $field );
				$help   = (string) ( $schema['description'] ?? '' );
				unset( $schema['description'] );
				register_post_meta(
					$type,
					$name,
					[
						'type'              => $schema['type'],
						'description'       => $help,
						'single'            => true,
						'default'           => Fields::default_for( $schema ),
						'sanitize_callback' => static fn( $value ) => Fields::sanitize( $value, $schema ),
						'auth_callback'     => static fn( $allowed, $key, $post_id ) => current_user_can( 'edit_post', (int) $post_id ),
						'show_in_rest'      => [ 'schema' => $schema ],
					]
				);
			}
		}

		// SEO title/description: Rank Math's keys (D5). Registered so the
		// pre-publish checklist and our no-Rank-Math fallback panel can read and
		// save them over REST in the same request as the post (see admin/editor.php).
		foreach ( td_checklist_post_types() as $type ) {
			if ( ! post_type_exists( $type ) ) {
				continue;
			}
			foreach ( [ 'rank_math_title', 'rank_math_description' ] as $key ) {
				if ( registered_meta_key_exists( 'post', $key, $type ) ) {
					continue;
				}
				register_post_meta(
					$type,
					$key,
					[
						'type'              => 'string',
						'single'            => true,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'auth_callback'     => static fn( $allowed, $meta_key, $post_id ) => current_user_can( 'edit_post', (int) $post_id ),
						'show_in_rest'      => true,
					]
				);
			}
		}
	},
	20
);

/**
 * Post types that carry the pre-publish checklist (§10.1).
 *
 * @return string[]
 */
function td_checklist_post_types(): array {
	return (array) apply_filters( 'td_checklist_post_types', [ 'post', 'service', 'solution', 'location', 'case_study', 'em-glossary', 'td_resource', 'studio_service', 'studio_work' ] );
}

/*
 * Without Pro, hide Pro-only fields from ACF's UI (our fallback box edits them).
 */
add_filter(
	'acf/load_fields',
	static function ( $fields, $parent ) {
		if ( td_acf_pro_active() || ! is_array( $parent ) || ! str_starts_with( (string) ( $parent['key'] ?? '' ), 'group_td_' ) ) {
			return $fields;
		}
		return array_values( array_filter( (array) $fields, static fn( $f ) => ! Fields::is_pro_only( (array) $f ) ) );
	},
	10,
	2
);

/*
 * ACF Pro bridge: our repeaters/flexible fields read and write the canonical
 * single-row meta instead of ACF's faq_0_question rows, so data entered
 * before or after the Pro licence (D4) is the same data.
 * [CONFIRM: verify once on an install with ACF Pro.]
 */
add_filter(
	'acf/pre_update_value',
	static function ( $check, $value, $post_id, $field ) {
		if ( null !== $check || ! is_numeric( $post_id ) || ! is_array( $field ) || ! td_is_td_acf_field( $field ) || ! in_array( $field['type'] ?? '', [ 'repeater', 'flexible_content' ], true ) ) {
			return $check;
		}
		$rows = Fields::rows_from_acf( $value, $field );
		update_post_meta( (int) $post_id, (string) $field['name'], is_array( $rows ) ? $rows : [] );
		update_post_meta( (int) $post_id, '_' . $field['name'], (string) $field['key'] );
		return true;
	},
	10,
	4
);
add_filter(
	'acf/pre_load_value',
	static function ( $value, $post_id, $field ) {
		if ( null !== $value || ! is_numeric( $post_id ) || ! is_array( $field ) || ! td_is_td_acf_field( $field ) || ! in_array( $field['type'] ?? '', [ 'repeater', 'flexible_content' ], true ) ) {
			return $value;
		}
		return Fields::rows_to_acf( get_post_meta( (int) $post_id, (string) $field['name'], true ), $field );
	},
	10,
	3
);

/**
 * Fields on a post type that ACF cannot edit on this install.
 *
 * @param string $post_type Post type.
 * @return array<string,array<string,mixed>>
 */
function td_fallback_fields( string $post_type ): array {
	$all = td_cpt_fields()->fields( $post_type );
	if ( td_acf_pro_active() ) {
		return [];
	}
	if ( ! td_acf_active() ) {
		return $all;
	}
	return array_filter( $all, [ Fields::class, 'is_pro_only' ] );
}

add_action(
	'add_meta_boxes',
	static function ( string $post_type ) {
		if ( ! td_fallback_fields( $post_type ) ) {
			return;
		}
		add_meta_box(
			'td-fields-fallback',
			td_acf_active() ? __( 'TechDella lists (ACF Pro not active)', 'techdella' ) : __( 'TechDella fields', 'techdella' ),
			'td_render_fallback_box',
			$post_type,
			'normal',
			'default'
		);
	}
);

/**
 * Render the fallback field box.
 *
 * @param WP_Post $post Post.
 */
function td_render_fallback_box( WP_Post $post ): void {
	wp_nonce_field( 'td_fields_' . $post->ID, '_td_fields_nonce' );
	$errors = (array) get_transient( 'td_fields_errors_' . get_current_user_id() . '_' . $post->ID );
	delete_transient( 'td_fields_errors_' . get_current_user_id() . '_' . $post->ID );

	echo '<p class="description">';
	if ( td_acf_active() ) {
		esc_html_e( 'These list fields need ACF Pro for a friendly editor. Until the licence is in place, edit them here as JSON. Keep the field names shown in the example; invalid JSON is not saved.', 'techdella' );
	} else {
		esc_html_e( 'ACF is not active, so fields are edited here. Lists are JSON; invalid JSON is not saved.', 'techdella' );
	}
	echo '</p>';

	foreach ( td_fallback_fields( $post->post_type ) as $name => $field ) {
		$schema = Fields::schema( $field );
		$id     = 'td-field-' . sanitize_html_class( $name );
		$value  = get_post_meta( $post->ID, $name, true );
		$label  = (string) ( $field['label'] ?? $name );
		$help   = (string) ( $field['instructions'] ?? '' );

		echo '<div class="td-fallback-field" style="margin:0 0 18px">';
		if ( isset( $errors[ $name ] ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( (string) $errors[ $name ] ) . '</p></div>';
		}
		if ( 'boolean' === $schema['type'] ) {
			printf(
				'<label for="%1$s"><input type="hidden" name="td_fields[%2$s]" value="0"><input type="checkbox" id="%1$s" name="td_fields[%2$s]" value="1" %3$s> <strong>%4$s</strong></label>',
				esc_attr( $id ),
				esc_attr( $name ),
				checked( (bool) $value, true, false ),
				esc_html( $label )
			);
		} else {
			printf( '<label for="%s"><strong>%s</strong></label><br>', esc_attr( $id ), esc_html( $label ) );
			if ( Fields::is_structured( $field ) ) {
				$json = '' === $value || null === $value ? '[]' : (string) wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				printf(
					'<textarea id="%1$s" name="td_fields[%2$s]" rows="6" class="large-text code" spellcheck="false" aria-describedby="%1$s-help">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $json )
				);
				$help .= ' ' . __( 'Example:', 'techdella' ) . ' ' . wp_json_encode( Fields::example( $schema ), JSON_UNESCAPED_SLASHES );
			} elseif ( 'textarea' === ( $field['type'] ?? '' ) ) {
				printf( '<textarea id="%1$s" name="td_fields[%2$s]" rows="3" class="large-text" aria-describedby="%1$s-help">%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
			} else {
				printf(
					'<input type="%4$s" id="%1$s" name="td_fields[%2$s]" value="%3$s" class="regular-text" aria-describedby="%1$s-help">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( is_scalar( $value ) ? (string) $value : '' ),
					in_array( $schema['type'], [ 'integer', 'number' ], true ) ? 'number' : 'text'
				);
			}
		}
		if ( '' !== $help ) {
			printf( '<p class="description" id="%s-help">%s</p>', esc_attr( $id ), esc_html( $help ) );
		}
		echo '</div>';
	}
}

add_action(
	'save_post',
	static function ( int $post_id, WP_Post $post ) {
		if ( ! isset( $_POST['_td_fields_nonce'], $_POST['td_fields'] ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_td_fields_nonce'] ) ), 'td_fields_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$input  = (array) wp_unslash( $_POST['td_fields'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per field schema below.
		$errors = [];
		foreach ( td_fallback_fields( $post->post_type ) as $name => $field ) {
			if ( ! array_key_exists( $name, $input ) ) {
				continue;
			}
			$schema = Fields::schema( $field );
			$raw    = $input[ $name ];
			if ( Fields::is_structured( $field ) ) {
				$raw     = trim( (string) $raw );
				$decoded = '' === $raw ? [] : json_decode( $raw, true );
				if ( '' !== $raw && JSON_ERROR_NONE !== json_last_error() ) {
					/* translators: 1: field label, 2: JSON error. */
					$errors[ $name ] = sprintf( __( '%1$s was not saved: the JSON is invalid (%2$s).', 'techdella' ), (string) $field['label'], json_last_error_msg() );
					continue;
				}
				$problems = Fields::validate( $decoded, $schema, (string) $name );
				if ( $problems ) {
					/* translators: 1: field label, 2: problems. */
					$errors[ $name ] = sprintf( __( '%1$s was not saved: %2$s', 'techdella' ), (string) $field['label'], implode( '; ', array_slice( $problems, 0, 3 ) ) );
					continue;
				}
				update_post_meta( $post_id, $name, Fields::sanitize( $decoded, $schema ) );
				continue;
			}
			$value = 'textarea' === ( $field['type'] ?? '' ) ? sanitize_textarea_field( (string) $raw ) : sanitize_text_field( (string) $raw );
			update_post_meta( $post_id, $name, Fields::sanitize( $value, $schema ) );
		}
		if ( $errors ) {
			set_transient( 'td_fields_errors_' . get_current_user_id() . '_' . $post_id, $errors, 10 * MINUTE_IN_SECONDS );
		}
	},
	10,
	2
);
