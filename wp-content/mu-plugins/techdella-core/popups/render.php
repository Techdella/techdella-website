<?php
/**
 * Popups front end (§13.1): matching, markup (hidden until view.js decides),
 * JSON config, inline end-of-post cards.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Popups\Targeting;

/**
 * Targeting context of the current request.
 *
 * @return array<string,mixed>
 */
function td_popups_context(): array {
	$uri        = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '/';
	$path       = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$post_type  = '';
	$categories = [];
	if ( is_singular() ) {
		$post      = get_queried_object();
		$post_type = $post instanceof WP_Post ? $post->post_type : '';
		if ( $post instanceof WP_Post && is_object_in_taxonomy( $post_type, 'category' ) ) {
			$categories = wp_get_post_categories( $post->ID, [ 'fields' => 'slugs' ] );
		}
	}
	return (array) apply_filters(
		'td_popups_context',
		[
			'site'       => td_site(),
			'path'       => '' !== $path ? $path : '/',
			'post_type'  => $post_type,
			'categories' => is_array( $categories ) ? $categories : [],
			'is_tool'    => is_singular( 'td_tool' ),
		]
	);
}

/**
 * Popups that target this page (inline cards included; at most one per type).
 *
 * @return array<int,array<string,mixed>>
 */
function td_popups_for_request(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	if ( is_admin() || is_feed() || is_embed() || is_404() || is_search() || wp_doing_ajax() || is_customize_preview() ) {
		$cache = [];
		return $cache;
	}
	$cache = Targeting::for_context( td_popups(), td_popups_context() );
	$cache = (array) apply_filters( 'td_popups_for_request', $cache );
	return $cache;
}

/**
 * The offer URL (resource page on Marketing, or the Journal newsletter page).
 *
 * @param array<string,mixed> $popup Popup.
 */
function td_popup_offer_url( array $popup ): string {
	if ( 'resource' === $popup['offer_type'] && '' !== $popup['resource'] ) {
		return td_url( 'marketing', '/resources/' . $popup['resource'] . '/' );
	}
	return td_url( 'journal', '/newsletter/' );
}

/**
 * The form inside a popup.
 *
 * @param array<string,mixed> $popup Popup.
 * @param string              $id    DOM id prefix.
 */
function td_popup_form( array $popup, string $id ): string {
	$key    = (string) $popup['form_key'];
	$offer  = 'resource' === $popup['offer_type'] ? (string) $popup['resource'] : 'newsletter';
	$uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '/';
	$post   = get_queried_object();
	$hidden = [
		'offer'         => $offer,
		'resource_slug' => 'resource' === $popup['offer_type'] ? $offer : '',
		'page'          => (string) wp_parse_url( $uri, PHP_URL_PATH ),
		'post_slug'     => $post instanceof WP_Post ? $post->post_name : '',
	];
	$label  = (string) ( $popup['variants'][0]['button'] ?? '' );
	if ( function_exists( 'td_form_render' ) ) {
		$html = td_form_render(
			$key,
			[
				'variant'       => 'compact',
				'title'         => '',
				'intro'         => '',
				'hidden'        => array_filter( $hidden, 'strlen' ),
				'id_prefix'     => $id . '-form',
				'heading_level' => 3,
				'submit_label'  => '' !== $label ? $label : __( 'Send', 'techdella' ),
			]
		);
		if ( '' !== $html ) {
			return $html;
		}
	}
	// Fallback (forms module missing): a plain email form to the same REST route.
	$out  = '<form class="td-popup__form" method="post" action="' . esc_url( rest_url( 'techdella/v1/submit/' . $key ) ) . '" data-td-popup-form="' . esc_attr( $key ) . '">';
	$out .= '<label class="td-popup__label" for="' . esc_attr( $id ) . '-email">' . esc_html__( 'Email', 'techdella' ) . '</label>';
	$out .= '<input class="td-input" type="email" id="' . esc_attr( $id ) . '-email" name="email" required autocomplete="email">';
	foreach ( array_filter( $hidden, 'strlen' ) as $name => $value ) {
		$out .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
	}
	$out .= '<button type="submit" class="td-btn td-btn--primary td-btn--sm"><span class="td-form__submit-label">' . esc_html( '' !== $label ? $label : __( 'Send', 'techdella' ) ) . '</span></button>';
	$out .= '<p class="td-popup__done" role="status" hidden>' . esc_html__( 'Thanks. Check your inbox.', 'techdella' ) . '</p>';
	$out .= '</form>';
	return $out;
}

/**
 * Markup for one popup.
 *
 * @param array<string,mixed> $popup   Normalised popup.
 * @param string              $context footer (dialog, hidden) | inline (card in content).
 */
function td_popup_markup( array $popup, string $context = 'footer' ): string {
	if ( empty( $popup['variants'] ) ) {
		return '';
	}
	static $n = 0;
	++$n;
	$id     = 'td-popup-' . sanitize_html_class( (string) $popup['id'] ) . ( $n > 1 ? '-' . $n : '' );
	$inline = 'inline' === $context || 'inline' === $popup['type'];
	$type   = $inline ? 'inline' : (string) $popup['type'];
	$modal  = 'exit' === $type;
	$attrs  = [
		'class'              => 'td-popup td-popup--' . $type,
		'id'                 => $id,
		'data-td-popup'      => (string) $popup['id'],
		'data-type'          => $type,
		'data-form-key'      => (string) $popup['form_key'],
		'data-variants'      => (string) count( (array) $popup['variants'] ),
		'data-button-b'      => (string) ( $popup['variants'][1]['button'] ?? '' ),
		'data-button-a'      => (string) ( $popup['variants'][0]['button'] ?? '' ),
		'aria-labelledby'    => $id . '-title-a',
	];
	if ( ! $inline ) {
		$attrs['role']       = 'dialog';
		$attrs['aria-modal'] = $modal ? 'true' : 'false';
	}
	$out = '<' . ( $inline ? 'aside' : 'div' );
	foreach ( $attrs as $k => $v ) {
		$out .= ' ' . $k . '="' . esc_attr( $v ) . '"';
	}
	$out .= $inline ? '>' : ' hidden>';
	$out .= '<div class="td-popup__panel"' . ( $modal ? ' tabindex="-1"' : '' ) . '>';
	if ( ! $inline ) {
		$out .= '<button type="button" class="td-popup__close" data-td-popup-close aria-label="' . esc_attr__( 'Close', 'techdella' ) . '">'
			. '<svg aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button>';
	}
	$level = 'topbar' === $type ? 'p' : 'h2';
	foreach ( array_values( (array) $popup['variants'] ) as $i => $variant ) {
		$letter = 0 === $i ? 'a' : 'b';
		$out   .= '<div class="td-popup__copy" data-variant="' . $letter . '"' . ( 'b' === $letter ? ' hidden' : '' ) . '>';
		$out   .= '<' . $level . ' class="td-popup__title" id="' . esc_attr( $id . '-title-' . $letter ) . '">' . td_accent( (string) $variant['title'] ) . '</' . $level . '>';
		if ( '' !== (string) $variant['text'] ) {
			$out .= '<p class="td-popup__text">' . esc_html( (string) $variant['text'] ) . '</p>';
		}
		$out .= '</div>';
	}
	if ( 'topbar' === $type ) {
		$label = (string) ( $popup['variants'][0]['button'] ?? __( 'Learn more', 'techdella' ) );
		$out  .= '<a class="td-btn td-btn--light td-btn--sm td-popup__cta" href="' . esc_url( td_popup_offer_url( $popup ) ) . '" data-td-popup-cta><span class="td-form__submit-label">' . esc_html( $label ) . '</span></a>';
	} else {
		$out .= '<div class="td-popup__form-wrap">' . td_popup_form( $popup, $id ) . '</div>';
	}
	$out .= '</div></' . ( $inline ? 'aside' : 'div' ) . '>';
	return $out;
}

// Footer: matching dialogs (hidden) + config for view.js.
add_action(
	'wp_footer',
	static function () {
		$popups = array_values( array_filter( td_popups_for_request(), static fn( $p ) => 'inline' !== $p['type'] ) );
		if ( ! $popups ) {
			return;
		}
		$config = [
			'isTool' => is_singular( 'td_tool' ),
			'popups' => array_map(
				static fn( $p ) => [
					'id'       => $p['id'],
					'type'     => $p['type'],
					'trigger'  => $p['trigger'],
					'value'    => $p['trigger_value'],
					'days'     => $p['frequency_days'],
					'formKey'  => $p['form_key'],
					'variants' => count( (array) $p['variants'] ),
				],
				$popups
			),
		];
		foreach ( $popups as $popup ) {
			echo td_popup_markup( $popup ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_popup_markup().
		}
		echo '<script type="application/json" id="td-popups-config">' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON, tags hex-escaped.
	},
	20
);

// Inline end-of-post cards: appended to the main post content.
add_filter(
	'the_content',
	static function ( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() || get_the_ID() !== get_queried_object_id() ) {
			return $content;
		}
		foreach ( td_popups_for_request() as $popup ) {
			if ( 'inline' === $popup['type'] ) {
				$content .= td_popup_markup( $popup, 'inline' );
				break;
			}
		}
		return $content;
	},
	20
);
