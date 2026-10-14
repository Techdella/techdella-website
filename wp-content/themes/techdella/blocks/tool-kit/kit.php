<?php
/**
 * Shared markup for the free-tool blocks (§12.1). Not a block itself: each
 * blocks/tool-<name>/render.php builds its fields with td_tool_field() and
 * wraps them with td_tool_render(), which also:
 *   - prints the shared tool CSS once (assets/build/blocks/tool-kit.css);
 *   - enqueues only this tool's JS (assets/build/tools/<name>.js, deferred);
 *   - renders the email gate (tool_gate form, §11.1) when the tool page's
 *     `gate` field is "email" — results then show after the form succeeds.
 *
 * Behaviour lives in tool-kit/kit.js (bundled into each tool's JS).
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'td_tool_render' ) ) :

	/**
	 * Print the shared tool CSS once per page (front end only; the editor loads main.css).
	 */
	function td_tool_kit_css(): string {
		static $done = false;
		if ( $done || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return '';
		}
		$done = true;
		$file = get_theme_file_path( 'assets/build/blocks/tool-kit.css' );
		if ( ! is_readable( $file ) ) {
			return '';
		}
		$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- built, trusted CSS.
		return '<style id="td-block-tool-kit">' . ( function_exists( 'td_css_fix_urls' ) ? td_css_fix_urls( $css ) : $css ) . '</style>';
	}

	/**
	 * Enqueue one tool's script (deferred, footer). Depends on the main bundle (window.td).
	 *
	 * @param string $name Tool name (folder suffix).
	 */
	function td_tool_enqueue( string $name ): void {
		$rel = 'assets/build/tools/' . $name . '.js';
		if ( ! is_readable( get_theme_file_path( $rel ) ) ) {
			return;
		}
		wp_enqueue_script(
			'td-tool-' . $name,
			get_theme_file_uri( $rel ),
			wp_script_is( 'techdella', 'registered' ) ? [ 'techdella' ] : [],
			function_exists( 'td_build_version' ) ? td_build_version( 'tools/' . $name . '.js' ) : null,
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);
	}

	/**
	 * One labelled form control.
	 *
	 * @param string              $tool Tool name (id prefix).
	 * @param string              $name Field name.
	 * @param array<string,mixed> $args type (text|url|textarea|select|checkbox), label, required, placeholder,
	 *                                  help, options [value => label], value, max, rows, half, autocomplete.
	 */
	function td_tool_field( string $tool, string $name, array $args ): string {
		$a        = wp_parse_args(
			$args,
			[
				'type'         => 'text',
				'label'        => $name,
				'required'     => false,
				'placeholder'  => '',
				'help'         => '',
				'options'      => [],
				'value'        => '',
				'max'          => 200,
				'rows'         => 6,
				'half'         => false,
				'autocomplete' => 'off',
				'checked'      => false,
			]
		);
		$id       = 'td-tool-' . sanitize_html_class( $tool ) . '-' . sanitize_html_class( $name );
		$help_id  = $id . '-help';
		$err_id   = $id . '-error';
		$describe = trim( ( $a['help'] ? $help_id . ' ' : '' ) . $err_id );
		$req      = $a['required'] ? ' required aria-required="true"' : '';
		$common   = sprintf( ' id="%s" name="%s" aria-describedby="%s"%s', esc_attr( $id ), esc_attr( $name ), esc_attr( $describe ), $req );
		$opt      = $a['required'] ? '' : ' <span class="td-tool__opt">' . esc_html__( '(optional)', 'techdella' ) . '</span>';

		if ( 'checkbox' === $a['type'] ) {
			$out  = '<div class="td-tool__field td-tool__field--check">';
			$out .= sprintf( '<input type="checkbox" class="td-tool__check" value="1"%s%s>', $common, $a['checked'] ? ' checked' : '' );
			$out .= '<label for="' . esc_attr( $id ) . '">' . esc_html( (string) $a['label'] ) . '</label>';
			$out .= '</div>';
			return $out;
		}

		$out  = '<div class="td-tool__field' . ( $a['half'] ? ' td-tool__field--half' : '' ) . '" data-field="' . esc_attr( $name ) . '">';
		$out .= '<label class="td-tool__label" for="' . esc_attr( $id ) . '">' . esc_html( (string) $a['label'] ) . $opt . '</label>';
		if ( 'textarea' === $a['type'] ) {
			$out .= sprintf(
				'<textarea class="td-tool__input td-tool__input--area"%s rows="%d" maxlength="%d" placeholder="%s">%s</textarea>',
				$common,
				(int) $a['rows'],
				(int) $a['max'],
				esc_attr( (string) $a['placeholder'] ),
				esc_textarea( (string) $a['value'] )
			);
		} elseif ( 'select' === $a['type'] ) {
			$out .= '<select class="td-tool__input td-tool__input--select"' . $common . '>';
			foreach ( (array) $a['options'] as $value => $label ) {
				$out .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( (string) $value ), selected( (string) $a['value'], (string) $value, false ), esc_html( (string) $label ) );
			}
			$out .= '</select>';
		} else {
			// URLs are typed loosely ("example.com"), so type=text + inputmode=url; validated in JS.
			$out .= sprintf(
				'<input class="td-tool__input" type="text"%s maxlength="%d" placeholder="%s" value="%s" autocomplete="%s"%s>',
				$common,
				(int) $a['max'],
				esc_attr( (string) $a['placeholder'] ),
				esc_attr( (string) $a['value'] ),
				esc_attr( (string) $a['autocomplete'] ),
				'url' === $a['type'] ? ' inputmode="url" data-type="url" spellcheck="false"' : ''
			);
		}
		if ( $a['help'] ) {
			$out .= '<p class="td-tool__help" id="' . esc_attr( $help_id ) . '">' . esc_html( (string) $a['help'] ) . '</p>';
		}
		$out .= '<p class="td-tool__field-error" id="' . esc_attr( $err_id ) . '" hidden></p>';
		$out .= '</div>';
		return $out;
	}

	/**
	 * The whole tool: form, no-JS note, optional email gate, results live region.
	 *
	 * @param string              $name       Tool name (tool-<name> folder).
	 * @param array<string,mixed> $attributes Block attributes (gate, slug).
	 * @param string              $fields     Field markup (from td_tool_field()).
	 * @param array<string,mixed> $opts       submit (label), ai (bool), note (string under the button).
	 */
	function td_tool_render( string $name, array $attributes, string $fields, array $opts = [] ): string {
		td_tool_enqueue( $name );
		$gate = ! empty( $attributes['gate'] ) && function_exists( 'td_form_render' );
		$slug = sanitize_title( (string) ( $attributes['slug'] ?? '' ) );
		$slug = '' !== $slug ? $slug : $name;

		$out  = td_tool_kit_css();
		$out .= '<div ' . td_block_wrapper(
			'td-tool td-tool--' . sanitize_html_class( $name ),
			[
				'data-td-tool-app' => $name,
				'data-gate'        => $gate ? '1' : '0',
				'data-slug'        => $slug,
				'data-rest'        => esc_url_raw( rest_url( 'techdella/v1/' ) ),
			]
		) . '>';
		$out .= '<form class="td-tool__form" novalidate data-td-tool-form>';
		$out .= '<div class="td-tool__grid">' . $fields . '</div>';
		$out .= '<div class="td-tool__actions">';
		$out .= '<button type="submit" class="td-btn td-btn--primary td-tool__submit"><span class="td-tool__submit-label">' . esc_html( (string) ( $opts['submit'] ?? __( 'Run the tool', 'techdella' ) ) ) . '</span><span class="td-btn__arrow">' . td_icon( 'arrow-right' ) . '</span></button>';
		if ( ! empty( $opts['note'] ) ) {
			$out .= '<p class="td-tool__note">' . esc_html( (string) $opts['note'] ) . '</p>';
		}
		$out .= '</div>';
		$out .= '<p class="td-tool__error" role="alert" hidden></p>';
		$out .= '</form>';
		$out .= '<noscript><p class="td-tool__noscript">' . esc_html__( 'This tool runs in your browser. Turn on JavaScript to use it.', 'techdella' ) . '</p></noscript>';

		if ( $gate ) {
			$out .= '<div class="td-tool__gate" data-td-tool-gate hidden>';
			$out .= '<p class="td-tool__gate-note" tabindex="-1">' . esc_html__( 'Your results are ready. Enter your email to see them.', 'techdella' ) . '</p>';
			$out .= td_block(
				'form',
				[
					'formKey'        => 'tool_gate',
					'variant'        => 'inline',
					'anchor'         => 'td-tool-gate-' . $name,
					'title'          => __( 'See your *results*', 'techdella' ),
					'intro'          => __( 'Enter your email and your results appear right here.', 'techdella' ),
					'submitLabel'    => __( 'Show my results', 'techdella' ),
					'successMessage' => __( 'Thanks. Your results are below.', 'techdella' ),
					'hidden'         => [
						'tool_slug'      => $slug,
						'result_summary' => '',
					],
				]
			);
			$out .= '</div>';
		}

		$out .= '<div class="td-tool__results" data-td-tool-results aria-live="polite"></div>';
		$out .= '</div>';
		return $out;
	}

endif;
