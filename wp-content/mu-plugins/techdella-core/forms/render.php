<?php
/**
 * Form renderer: td_form_render() gives server-rendered, accessible form markup for any key.
 * Works without JS (plain POST to the REST route → 303). view.js in the
 * theme's form block adds fetch(), inline validation, steps and the success state.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a form for a Startbuddi endpoint key.
 *
 * @param string              $key  Form key (see td_forms_keys()).
 * @param array<string,mixed> $args {
 *     Optional.
 *     @type string   $title           Heading ('' hides it). Accent word with *asterisks*.
 *     @type string   $intro           Intro line.
 *     @type string   $submit_label    Button label.
 *     @type string   $variant         light|dark|inline|compact.
 *     @type string   $success_title   Success heading.
 *     @type string   $success_message Success text.
 *     @type array    $hidden          Values for the schema's hidden fields (resource_slug, tool_slug, offer, post_slug, page…).
 *     @type string[] $fields          Subset/order of visible fields (required fields are always kept).
 *     @type string   $id_prefix       DOM id prefix (default "tdf-<key>").
 *     @type int      $heading_level   2|3|4 (default 2).
 *     @type string   $class           Extra classes on the root.
 *     @type bool     $steps           Use the schema's steps (default true).
 * }
 */
function td_form_render( string $key, array $args = [] ): string {
	static $count = [];
	$schema       = td_form_schema( $key );
	if ( ! $schema ) {
		return '';
	}
	$args    = wp_parse_args(
		$args,
		[
			'title'           => (string) ( $schema['title'] ?? '' ),
			'intro'           => (string) ( $schema['intro'] ?? '' ),
			'submit_label'    => (string) ( $schema['submit'] ?? __( 'Send', 'techdella' ) ),
			'variant'         => 'light',
			'success_title'   => (string) ( $schema['success_title'] ?? __( 'Thanks', 'techdella' ) ),
			'success_message' => (string) ( $schema['success'] ?? '' ),
			'hidden'          => [],
			'fields'          => [],
			'id_prefix'       => 'tdf-' . td_forms_slug( $key ),
			'heading_level'   => 2,
			'class'           => '',
			'steps'           => true,
		]
	);
	$variant = in_array( $args['variant'], [ 'light', 'dark', 'inline', 'compact' ], true ) ? (string) $args['variant'] : 'light';
	$level   = in_array( (int) $args['heading_level'], [ 2, 3, 4 ], true ) ? (int) $args['heading_level'] : 2;

	$count[ $key ] = ( $count[ $key ] ?? 0 ) + 1;
	$id            = sanitize_html_class( (string) $args['id_prefix'] ) . ( $count[ $key ] > 1 ? '-' . $count[ $key ] : '' );
	$fields        = (array) $schema['fields'];

	// Field subset/order: keep listed fields in that order, then any required/hidden/consent field not listed.
	if ( $args['fields'] ) {
		$picked = [];
		foreach ( (array) $args['fields'] as $name ) {
			if ( isset( $fields[ $name ] ) ) {
				$picked[ $name ] = $fields[ $name ];
			}
		}
		foreach ( $fields as $name => $f ) {
			if ( ! isset( $picked[ $name ] ) && ( ! empty( $f['required'] ) || 'hidden' === $f['type'] || ! empty( $f['consent'] ) ) ) {
				$picked[ $name ] = $f;
			}
		}
		$fields = $picked;
	}

	$flash  = td_forms_flash( $key, $id );
	$errors = $flash['errors'] ?? [];
	$old    = $flash['old'] ?? [];
	$hidden = (array) $args['hidden'];

	$steps = ( $args['steps'] && ! empty( $schema['steps'] ) && ! $args['fields'] && in_array( $variant, [ 'light', 'dark' ], true ) ) ? (array) $schema['steps'] : [];

	$out  = '<div class="' . esc_attr( trim( 'td-form td-form--' . $variant . ( $steps ? ' td-form--steps' : '' ) . ' ' . $args['class'] ) ) . '" id="' . esc_attr( $id ) . '" data-td-form="' . esc_attr( $key ) . '"';
	$out .= ' data-token-url="' . esc_url( rest_url( 'techdella/v1/form-token' ) ) . '"';
	if ( '' !== (string) TURNSTILE_SITE_KEY ) {
		$out .= ' data-turnstile="' . esc_attr( (string) TURNSTILE_SITE_KEY ) . '"';
	}
	$out .= ' data-msg-required="' . esc_attr__( 'This field is required.', 'techdella' ) . '"';
	$out .= ' data-msg-email="' . esc_attr__( 'Enter a valid email address, like name@company.com.', 'techdella' ) . '"';
	$out .= ' data-msg-url="' . esc_attr__( 'Enter a valid web address, like https://yourcompany.com.', 'techdella' ) . '"';
	$out .= ' data-msg-choose="' . esc_attr__( 'Choose at least one option.', 'techdella' ) . '"';
	$out .= ' data-msg-consent="' . esc_attr__( 'Please tick this box to continue.', 'techdella' ) . '"';
	/* translators: %d: maximum number of characters. */
	$out .= ' data-msg-max="' . esc_attr__( 'Keep this under %d characters.', 'techdella' ) . '"';
	$out .= ' data-msg-network="' . esc_attr__( 'We couldn’t reach the server. Check your connection and try again.', 'techdella' ) . '"';
	$out .= ' data-msg-sending="' . esc_attr__( 'Sending…', 'techdella' ) . '"';
	/* translators: 1: current step number, 2: number of steps. */
	$out .= ' data-msg-step="' . esc_attr__( 'Step %1$d of %2$d', 'techdella' ) . '"';
	$out .= ' data-msg-back="' . esc_attr__( '← Back', 'techdella' ) . '"';
	$out .= ' data-msg-next="' . esc_attr__( 'Continue', 'techdella' ) . '"';
	$out .= ' data-msg-summary="' . esc_attr__( 'Your brief', 'techdella' ) . '"';
	$out .= '>';

	$out .= '<div class="td-form__body">';
	if ( '' !== $args['title'] || '' !== $args['intro'] ) {
		$out .= '<div class="td-form__head">';
		if ( '' !== $args['title'] ) {
			$out .= sprintf( '<h%1$d class="td-form__title" id="%2$s-title">%3$s</h%1$d>', $level, esc_attr( $id ), td_accent( (string) $args['title'] ) );
		}
		if ( '' !== $args['intro'] ) {
			$out .= '<p class="td-form__intro">' . td_mark_placeholders( esc_html( (string) $args['intro'] ) ) . '</p>';
		}
		$out .= '</div>';
	}

	$out .= sprintf(
		'<form class="td-form__form" action="%1$s" method="post" accept-charset="UTF-8"%2$s>',
		esc_url( rest_url( 'techdella/v1/submit/' . $key ) ),
		'' !== $args['title'] ? ' aria-labelledby="' . esc_attr( $id ) . '-title"' : ' aria-label="' . esc_attr( (string) ( $schema['name'] ?? $key ) ) . '"'
	);

	// Form-level alert (bot/rate-limit/network errors).
	$form_error = (string) ( $errors['_form'] ?? '' );
	$out       .= '<div class="td-form__alert" role="alert" id="' . esc_attr( $id ) . '-alert"' . ( '' === $form_error ? ' hidden' : '' ) . '>' . esc_html( $form_error ) . '</div>';
	if ( $errors && '' === $form_error ) {
		$out .= '<div class="td-form__alert" role="alert">' . esc_html__( 'Please fix the highlighted fields.', 'techdella' ) . '</div>';
	}

	// Meta inputs: token (cache-safe; refreshed by view.js), page, referrer, instance id, honeypot.
	$out .= '<input type="hidden" name="td_token" value="' . esc_attr( td_forms_token()->issue( $key, time() ) ) . '">';
	$out .= '<input type="hidden" name="td_page" value="' . esc_url( td_forms_current_url() ) . '">';
	$out .= '<input type="hidden" name="td_ref" value="">';
	$out .= '<input type="hidden" name="td_form_id" value="' . esc_attr( $id ) . '">';
	$out .= '<div class="td-form__hp" aria-hidden="true"><label for="' . esc_attr( $id ) . '-hp">' . esc_html__( 'Leave this field empty', 'techdella' ) . '</label><input type="text" id="' . esc_attr( $id ) . '-hp" name="td_hp" value="" tabindex="-1" autocomplete="off"></div>';

	$render_field = static function ( string $name ) use ( $fields, $id, $errors, $old, $hidden ): string {
		if ( ! isset( $fields[ $name ] ) ) {
			return '';
		}
		$value = array_key_exists( $name, $old ) ? $old[ $name ] : ( $hidden[ $name ] ?? null );
		return td_forms_field_html( $name, (array) $fields[ $name ], $id, $value, (string) ( $errors[ $name ] ?? '' ) );
	};

	if ( $steps ) {
		$done  = [];
		$total = count( $steps );
		// Visible fields not placed in any step (e.g. consent) join the last step.
		$placed = [];
		foreach ( $steps as $step ) {
			$placed = array_merge( $placed, (array) $step['fields'] );
		}
		foreach ( $fields as $name => $f ) {
			if ( ! in_array( $name, $placed, true ) && 'hidden' !== $f['type'] ) {
				$steps[ $total - 1 ]['fields'][] = $name;
			}
		}
		foreach ( $steps as $i => $step ) {
			$sid  = $id . '-step-' . ( $i + 1 );
			$out .= '<div class="td-form__step" data-step="' . ( $i + 1 ) . '" data-step-label="' . esc_attr( (string) ( $step['label'] ?? '' ) ) . '" role="group" aria-labelledby="' . esc_attr( $sid ) . '">';
			$out .= sprintf( '<h%1$d class="td-form__step-title" id="%2$s" tabindex="-1">%3$s</h%1$d>', min( 6, $level + 1 ), esc_attr( $sid ), esc_html( (string) $step['title'] ) );
			if ( ! empty( $step['intro'] ) ) {
				$out .= '<p class="td-form__step-intro">' . esc_html( (string) $step['intro'] ) . '</p>';
			}
			$out .= '<div class="td-form__grid">';
			foreach ( (array) $step['fields'] as $name ) {
				$out   .= $render_field( (string) $name );
				$done[] = $name;
			}
			$out .= '</div>';
			if ( $i + 1 === $total ) {
				$out .= '<div class="td-form__summary" data-summary hidden></div>';
			}
			$out .= '</div>';
		}
		// Fields not placed in any step (hidden/consent).
		foreach ( array_diff( array_keys( $fields ), $done ) as $name ) {
			$out .= $render_field( (string) $name );
		}
	} else {
		$out .= '<div class="td-form__grid">';
		foreach ( array_keys( $fields ) as $name ) {
			$out .= $render_field( (string) $name );
		}
		$out .= '</div>';
	}

	if ( '' !== (string) TURNSTILE_SITE_KEY ) {
		$out .= '<div class="td-form__turnstile" data-turnstile-slot></div>';
		$out .= '<noscript><p class="td-form__note">' . esc_html__( 'This form uses a spam check that needs JavaScript. If you can’t turn it on, email us instead.', 'techdella' ) . ' [CONFIRM contact email]</p></noscript>';
	}

	$privacy = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
	$privacy = '' !== $privacy ? $privacy : td_url( 'marketing', '/privacy-policy/' );

	$out .= '<div class="td-form__actions">';
	$out .= '<button type="submit" class="td-btn td-btn--' . ( 'dark' === $variant ? 'light' : 'primary' ) . ' td-form__submit"><span class="td-form__submit-label">' . esc_html( (string) $args['submit_label'] ) . '</span><span class="td-btn__arrow">' . td_icon( 'arrow-up-right' ) . '</span></button>';
	$out .= '<p class="td-form__privacy">' . sprintf(
		/* translators: %s: privacy policy link. */
		esc_html__( 'We use your details only to reply to this request, via our CRM provider Startbuddi. See our %s.', 'techdella' ),
		'<a href="' . esc_url( $privacy ) . '">' . esc_html__( 'privacy policy', 'techdella' ) . '</a>'
	) . '</p>';
	$out .= '</div>';
	$out .= '</form>';
	$out .= '</div>';

	// Success state (shown in place by view.js; the no-JS path lands on the thank-you page instead).
	$out .= '<div class="td-form__success" id="' . esc_attr( $id ) . '-success" tabindex="-1" hidden>';
	$out .= '<span class="td-form__success-mark" aria-hidden="true">' . td_icon( 'check' ) . '</span>';
	$out .= sprintf( '<h%1$d class="td-form__title">%2$s</h%1$d>', $level, td_accent( (string) $args['success_title'] ) );
	if ( '' !== $args['success_message'] ) {
		$out .= '<p class="td-form__intro">' . esc_html( (string) $args['success_message'] ) . '</p>';
	}
	$out .= '<p class="td-form__download" data-download hidden><a class="td-btn td-btn--primary" href="#" download><span data-download-label>' . esc_html__( 'Download the PDF', 'techdella' ) . '</span><span class="td-btn__arrow">' . td_icon( 'arrow-right' ) . '</span></a></p>';
	$out .= '<button type="button" class="td-btn td-btn--secondary td-btn--sm td-form__again" data-again>' . esc_html__( 'Send another', 'techdella' ) . '</button>';
	$out .= '</div>';

	$out .= '</div>';
	return $out;
}

/**
 * HTML for one field.
 *
 * @param string              $name  Field name.
 * @param array<string,mixed> $f     Definition.
 * @param string              $form  Form DOM id.
 * @param mixed               $value Current value.
 * @param string              $error Error message.
 */
function td_forms_field_html( string $name, array $f, string $form, $value, string $error ): string {
	$type     = (string) ( $f['type'] ?? 'text' );
	$fid      = $form . '-' . str_replace( '_', '-', $name );
	$required = ! empty( $f['required'] );
	$err_id   = $fid . '-error';
	$help_id  = $fid . '-help';
	$describe = trim( ( ! empty( $f['help'] ) ? $help_id . ' ' : '' ) . $err_id );
	$invalid  = '' !== $error ? ' aria-invalid="true"' : '';
	$err_html = '<p class="td-field__error" id="' . esc_attr( $err_id ) . '"' . ( '' === $error ? ' hidden' : '' ) . '>' . esc_html( $error ) . '</p>';
	$help     = ! empty( $f['help'] ) ? '<p class="td-field__help" id="' . esc_attr( $help_id ) . '">' . esc_html( (string) $f['help'] ) . '</p>' : '';
	$opt      = $required ? '' : ' <span class="td-field__opt">' . esc_html__( '(optional)', 'techdella' ) . '</span>';
	$width    = 'half' === ( $f['width'] ?? '' ) ? ' td-field--half' : '';

	if ( 'hidden' === $type ) {
		$v = is_array( $value ) ? (string) wp_json_encode( $value ) : (string) ( $value ?? '' );
		return '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $v ) . '">';
	}

	if ( 'checkbox' === $type ) {
		return '<div class="td-field td-field--check' . ( '' !== $error ? ' is-invalid' : '' ) . '" data-field="' . esc_attr( $name ) . '">'
			. '<input type="checkbox" class="td-check" id="' . esc_attr( $fid ) . '" name="' . esc_attr( $name ) . '" value="1"' . ( $value ? ' checked' : '' ) . ( $required ? ' required data-consent' : '' ) . ' aria-describedby="' . esc_attr( $err_id ) . '"' . $invalid . '>'
			. '<label for="' . esc_attr( $fid ) . '">' . esc_html( (string) $f['label'] ) . '</label>'
			. $err_html . '</div>';
	}

	if ( 'pills' === $type ) {
		$multi  = ! empty( $f['multiple'] );
		$style  = in_array( $f['style'] ?? 'pill', [ 'pill', 'card', 'row' ], true ) ? (string) ( $f['style'] ?? 'pill' ) : 'pill';
		$values = array_map( 'strval', is_array( $value ) ? $value : ( null === $value || '' === $value ? [] : [ $value ] ) );
		$out    = '<fieldset class="td-field td-field--pills' . ( '' !== $error ? ' is-invalid' : '' ) . '" data-field="' . esc_attr( $name ) . '"' . ( $required ? ' data-required' : '' ) . ' aria-describedby="' . esc_attr( $describe ) . '">';
		$out   .= '<legend class="td-field__label' . ( ! empty( $f['legend_hidden'] ) ? ' td-sr' : '' ) . '">' . esc_html( (string) $f['label'] ) . $opt . '</legend>';
		$out   .= $help . '<div class="td-pills td-pills--' . esc_attr( $style ) . '">';
		$i      = 0;
		foreach ( (array) $f['options'] as $ov => $ol ) {
			++$i;
			$label = is_array( $ol ) ? (string) $ol[0] : (string) $ol;
			$hint  = is_array( $ol ) ? (string) ( $ol[1] ?? '' ) : '';
			$oid   = $fid . '-' . $i;
			$out  .= '<label class="td-pill" for="' . esc_attr( $oid ) . '">'
				. '<input class="td-pill__input" type="' . ( $multi ? 'checkbox' : 'radio' ) . '" id="' . esc_attr( $oid ) . '" name="' . esc_attr( $name . ( $multi ? '[]' : '' ) ) . '" value="' . esc_attr( (string) $ov ) . '"' . ( in_array( (string) $ov, $values, true ) ? ' checked' : '' ) . ( $required && ! $multi ? ' required' : '' ) . $invalid . '>'
				. '<span class="td-pill__box"><span class="td-pill__label">' . esc_html( $label ) . '</span>' . ( '' !== $hint ? '<span class="td-pill__hint">' . esc_html( $hint ) . '</span>' : '' ) . '</span>'
				. '</label>';
		}
		return $out . '</div>' . $err_html . '</fieldset>';
	}

	$out  = '<div class="td-field' . $width . ( '' !== $error ? ' is-invalid' : '' ) . '" data-field="' . esc_attr( $name ) . '">';
	$out .= '<label class="td-field__label" for="' . esc_attr( $fid ) . '">' . esc_html( (string) $f['label'] ) . $opt . '</label>';
	$attr = ' id="' . esc_attr( $fid ) . '" name="' . esc_attr( $name ) . '" aria-describedby="' . esc_attr( $describe ) . '"' . $invalid . ( $required ? ' required' : '' );
	if ( ! empty( $f['autocomplete'] ) ) {
		$attr .= ' autocomplete="' . esc_attr( (string) $f['autocomplete'] ) . '"';
	}
	if ( ! empty( $f['placeholder'] ) ) {
		$attr .= ' placeholder="' . esc_attr( (string) $f['placeholder'] ) . '"';
	}
	$v = is_scalar( $value ) ? (string) $value : '';

	if ( 'select' === $type ) {
		$out .= '<select class="td-input td-input--select"' . $attr . '>';
		$out .= '<option value="">' . esc_html__( 'Choose…', 'techdella' ) . '</option>';
		foreach ( (array) $f['options'] as $ov => $ol ) {
			$out .= '<option value="' . esc_attr( (string) $ov ) . '"' . selected( $v, (string) $ov, false ) . '>' . esc_html( is_array( $ol ) ? (string) $ol[0] : (string) $ol ) . '</option>';
		}
		$out .= '</select>';
	} elseif ( 'textarea' === $type ) {
		$out .= '<textarea class="td-input td-input--area" rows="' . (int) ( $f['rows'] ?? 4 ) . '" maxlength="' . (int) ( $f['max'] ?? 4000 ) . '"' . $attr . '>' . esc_textarea( $v ) . '</textarea>';
	} else {
		$input_type = in_array( $type, [ 'email', 'url' ], true ) ? $type : 'text';
		// URL fields accept "yourcompany.com" (the server adds https://), so they use type=text + inputmode=url.
		$out .= '<input class="td-input" type="' . ( 'url' === $input_type ? 'text' : $input_type ) . '"' . ( 'url' === $input_type ? ' inputmode="url" data-type="url"' : '' ) . ' maxlength="' . (int) ( $f['max'] ?? 200 ) . '" value="' . esc_attr( $v ) . '"' . $attr . '>';
	}
	return $out . $help . $err_html . '</div>';
}

/**
 * Current page URL (without our flash args), used as td_page for the no-JS path.
 */
function td_forms_current_url(): string {
	if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return home_url( '/' );
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$url = home_url( (string) wp_parse_url( $uri, PHP_URL_PATH ) );
	return (string) remove_query_arg( [ 'td_fe', 'dl' ], $url );
}
