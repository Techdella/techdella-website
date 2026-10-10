<?php
/**
 * Shared helpers for the three site headers and footers (parts/nav-*.php,
 * parts/footer-*.php). Loaded with require_once from those parts.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'td_nav_services' ) ) {

	/**
	 * The six Marketing services for the mega-menu and footers. Uses the
	 * "services" menu location when one is assigned.
	 *
	 * @return array<int,array{label:string,url:string,desc:string}>
	 */
	function td_nav_services(): array {
		$items = td_nav_menu_items( 'services' );
		if ( $items ) {
			return $items;
		}
		$defaults = [
			[ 'SEO', '/services/seo/', 'Rank on Google for the searches that turn into revenue.' ],
			[ 'AI Search Optimization', '/services/ai-search-optimization/', 'Get recommended in ChatGPT, Perplexity and AI Overviews.' ],
			[ 'Paid ads', '/services/paid-ads/', 'Google, Meta, TikTok and LinkedIn, with creatives made in-house.' ],
			[ 'Lead generation', '/services/lead-generation/', 'Landing pages, lead magnets and forms that capture demand.' ],
			[ 'Email marketing', '/services/email-marketing/', 'Nurture sequences that turn interest into booked calls.' ],
			[ 'AI marketing automation', '/services/marketing-automation/', 'Every lead answered, scored and routed in minutes.' ],
		];
		return array_map(
			static fn( array $d ): array => [
				'label' => $d[0],
				'url'   => td_url( 'marketing', $d[1] ),
				'desc'  => $d[2],
			],
			$defaults
		);
	}

	/**
	 * Top-level items of the menu assigned to a location (empty when none).
	 *
	 * @param string $location Menu location.
	 * @return array<int,array{label:string,url:string,desc:string}>
	 */
	function td_nav_menu_items( string $location ): array {
		$locations = get_nav_menu_locations();
		if ( empty( $locations[ $location ] ) ) {
			return [];
		}
		$items = wp_get_nav_menu_items( (int) $locations[ $location ] );
		if ( ! is_array( $items ) ) {
			return [];
		}
		$out = [];
		foreach ( $items as $item ) {
			if ( (int) $item->menu_item_parent > 0 ) {
				continue;
			}
			$out[] = [
				'label' => (string) $item->title,
				'url'   => (string) $item->url,
				'desc'  => (string) $item->description,
			];
		}
		return $out;
	}

	/**
	 * Whether a URL is the current page (for aria-current).
	 *
	 * @param string $url Absolute URL.
	 */
	function td_nav_is_current( string $url ): bool {
		$req  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '/';
		$host = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$port = (string) wp_parse_url( home_url( '/' ), PHP_URL_PORT );
		$u    = wp_parse_url( $url );
		if ( ! $u || ! empty( $u['fragment'] ) ) {
			return false;
		}
		if ( isset( $u['host'] ) && ( $u['host'] !== $host || (string) ( $u['port'] ?? '' ) !== $port ) ) {
			return false;
		}
		return trailingslashit( (string) ( $u['path'] ?? '/' ) ) === trailingslashit( $req );
	}

	/**
	 * Whether the current request is inside a URL's section (e.g. /services/…).
	 *
	 * @param string $url Absolute URL.
	 */
	function td_nav_in_section( string $url ): bool {
		$req  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '/';
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return '' !== trim( $path, '/' ) && str_starts_with( trailingslashit( $req ), trailingslashit( $path ) );
	}

	/**
	 * Primary links: the "primary" menu when assigned, else the given defaults.
	 * Returns <li> markup.
	 *
	 * @param array<int,array{0:string,1:string}> $defaults [ label, absolute url ].
	 * @param string                              $context  bar|sheet.
	 */
	function td_nav_primary_items( array $defaults, string $context = 'bar' ): string {
		$class = 'sheet' === $context ? 'td-sheet__link' : 'td-nav__link';
		if ( has_nav_menu( 'primary' ) ) {
			return (string) wp_nav_menu(
				[
					'theme_location' => 'primary',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'depth'          => 1,
					'echo'           => false,
					'fallback_cb'    => false,
					'link_before'    => '',
					'link_after'     => '',
					'td_link_class'  => $class,
				]
			);
		}
		$out = '';
		foreach ( $defaults as $link ) {
			$current = td_nav_is_current( $link[1] );
			$out    .= sprintf(
				'<li><a class="%s" href="%s"%s>%s</a></li>',
				esc_attr( $class ),
				esc_url( $link[1] ),
				$current ? ' aria-current="page"' : '',
				esc_html( $link[0] )
			);
		}
		return $out;
	}

	// Link class for wp_nav_menu() items rendered by td_nav_primary_items().
	add_filter(
		'nav_menu_link_attributes',
		static function ( array $atts, $item, $args ): array {
			if ( is_object( $args ) && ! empty( $args->td_link_class ) ) {
				$atts['class'] = trim( ( $atts['class'] ?? '' ) . ' ' . $args->td_link_class );
			}
			return $atts;
		},
		10,
		3
	);

	/**
	 * Brand link: official logo + optional serif suffix ("Journal", "Studios").
	 *
	 * @param string $suffix  Serif word after the logo.
	 * @param string $variant Logo variant (default|white).
	 * @param string $tag     Element: a (link home) or span.
	 */
	function td_nav_brand( string $suffix = '', string $variant = 'default', string $tag = 'a' ): string {
		$inner = td_logo( $variant, 28 ) . ( '' !== $suffix ? '<span class="td-brand__suffix">' . esc_html( $suffix ) . '</span>' : '' );
		if ( 'a' !== $tag ) {
			return '<span class="td-brand">' . $inner . '</span>';
		}
		return '<a class="td-brand" href="' . esc_url( home_url( '/' ) ) . '">' . $inner . '<span class="td-sr">' . esc_html__( 'home', 'techdella' ) . '</span></a>';
	}

	/**
	 * Mobile sheet + no-JS <details> fallback. JS (assets/js/nav.js) lifts the
	 * sheet out of the <details> and drives it from the button instead.
	 *
	 * @param string $items Sheet body markup (escaped).
	 * @param string $cta   CTA button markup (escaped).
	 */
	function td_nav_sheet( string $items, string $cta ): string {
		$sheet  = '<div class="td-sheet" id="td-sheet" data-td-sheet>';
		$sheet .= '<div class="td-sheet__head"><span class="td-sheet__title">' . esc_html__( 'Menu', 'techdella' ) . '</span>';
		$sheet .= '<button type="button" class="td-sheet__close" data-td-sheet-close hidden>' . td_icon( 'close' ) . '<span class="td-sr">' . esc_html__( 'Close menu', 'techdella' ) . '</span></button></div>';
		$sheet .= '<nav class="td-sheet__nav" aria-label="' . esc_attr__( 'Mobile', 'techdella' ) . '">' . $items . '</nav>';
		$sheet .= '<div class="td-sheet__cta">' . $cta . '</div></div>';

		$out  = '<details class="td-nav__menu" data-td-nav-details><summary class="td-nav__burger">' . td_icon( 'menu' ) . '<span>' . esc_html__( 'Menu', 'techdella' ) . '</span></summary>' . $sheet . '</details>';
		$out .= '<button type="button" class="td-nav__burger td-nav__burger--js" aria-expanded="false" aria-controls="td-sheet" data-td-sheet-open hidden>' . td_icon( 'menu' ) . '<span>' . esc_html__( 'Menu', 'techdella' ) . '</span></button>';
		return $out;
	}

	/**
	 * Social links from the td_social_profiles option (name => url).
	 * Empty/invalid URLs are skipped; never outputs "#" links.
	 *
	 * @return array<string,string>
	 */
	function td_social_links(): array {
		$profiles = get_option( 'td_social_profiles', [] );
		$out      = [];
		foreach ( is_array( $profiles ) ? $profiles : [] as $name => $url ) {
			$url = esc_url_raw( (string) $url );
			if ( '' !== $url && preg_match( '#^https?://#', $url ) && is_string( $name ) && '' !== $name ) {
				$out[ $name ] = $url;
			}
		}
		return $out;
	}

	/**
	 * Legal links (they live on the Marketing site, §9.3).
	 *
	 * @return array<string,string> label => url.
	 */
	function td_legal_links(): array {
		return [
			__( 'Privacy policy', 'techdella' ) => td_url( 'marketing', '/privacy-policy/' ),
			__( 'Terms', 'techdella' )          => td_url( 'marketing', '/terms/' ),
			__( 'Cookies', 'techdella' )        => td_url( 'marketing', '/cookies/' ),
			__( 'Disclaimer', 'techdella' )     => td_url( 'marketing', '/disclaimer/' ),
		];
	}

	/**
	 * <li><a> list markup.
	 *
	 * @param array<string,string> $links    label => url.
	 * @param bool                 $external Add rel="me noopener" (social).
	 */
	function td_footer_list( array $links, bool $external = false ): string {
		$out = '';
		foreach ( $links as $label => $url ) {
			$out .= '<li><a href="' . esc_url( $url ) . '"' . ( $external ? ' rel="me noopener"' : '' ) . '>' . esc_html( (string) $label ) . '</a></li>';
		}
		return $out;
	}

	/**
	 * Footer column links: the menu at $location when assigned, else defaults.
	 *
	 * @param string               $location Menu location.
	 * @param array<string,string> $defaults label => url.
	 * @return array<string,string>
	 */
	function td_footer_links( string $location, array $defaults ): array {
		$items = td_nav_menu_items( $location );
		if ( ! $items ) {
			return $defaults;
		}
		$out = [];
		foreach ( $items as $i ) {
			$out[ $i['label'] ] = $i['url'];
		}
		return $out;
	}

	/**
	 * Bottom bar: copyright + legal links + optional social list.
	 *
	 * @param bool $with_social Include social links (when the site has no social column).
	 */
	function td_footer_bottom( bool $with_social = false ): string {
		$out = '<div class="td-footer__bottom"><p>&copy; ' . esc_html( gmdate( 'Y' ) ) . ' ' . esc_html__( 'TechDella. All rights reserved.', 'techdella' ) . '</p>';
		if ( $with_social && td_social_links() ) {
			$out .= '<ul class="td-footer__inline" role="list" aria-label="' . esc_attr__( 'Social', 'techdella' ) . '">' . td_footer_list( td_social_links(), true ) . '</ul>';
		}
		$out .= '<nav aria-label="' . esc_attr__( 'Legal', 'techdella' ) . '"><ul class="td-footer__inline" role="list">' . td_footer_list( td_legal_links() ) . '</ul></nav></div>';
		return $out;
	}
}
