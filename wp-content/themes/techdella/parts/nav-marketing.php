<?php
/**
 * Navigation (marketing): sticky header, mega-menus for Services, Industries and Resources,
 * mobile sheet (§2.3). Without JS, "Services" is a plain link to /services/, Industries and
 * Resources are listed in the footer, and the mobile menu is a <details>.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/nav-common.php';

$td_services  = td_nav_services();
$td_hub       = td_url( 'marketing', '/services/' );
$td_cta       = td_button( __( 'Get an audit', 'techdella' ), td_url( 'marketing', '/growth-audit/' ), 'primary', [ 'size' => 'sm' ] );
$td_links     = [
	[ __( 'Customers', 'techdella' ), td_url( 'marketing', '/case-studies/' ) ],
	[ __( 'Journal', 'techdella' ), td_url( 'journal', '/' ) ],
	[ __( 'Studios', 'techdella' ), td_url( 'studios', '/' ) ],
];
$td_in_hub    = td_nav_in_section( $td_hub );
$td_mega_list = '';
foreach ( $td_services as $td_s ) {
	$td_mega_list .= sprintf(
		'<li><a class="td-mega__link" href="%s"%s><strong>%s</strong><span>%s</span></a></li>',
		esc_url( $td_s['url'] ),
		td_nav_is_current( $td_s['url'] ) ? ' aria-current="page"' : '',
		esc_html( $td_s['label'] ),
		esc_html( $td_s['desc'] )
	);
}
$td_industries = td_nav_posts( 'solution' );
$td_markets    = td_nav_posts( 'location', 12 );
$td_guides     = td_nav_posts( 'td_resource', 12 );
$td_tools      = td_nav_posts( 'td_tool', 12 );
$td_learn      = [
	[
		'label' => __( 'Journal: B2B marketing guides', 'techdella' ),
		'url'   => td_url( 'journal', '/' ),
	],
	[
		'label' => __( 'Marketing glossary', 'techdella' ),
		'url'   => td_url( 'marketing', '/glossary/' ),
	],
	[
		'label' => __( 'How to start a business', 'techdella' ),
		'url'   => td_url( 'marketing', '/business/' ),
	],
	[
		'label' => __( 'Case studies', 'techdella' ),
		'url'   => td_url( 'marketing', '/case-studies/' ),
	],
];
/**
 * A label-only mega-menu item (no hub page): a button that opens the panel.
 *
 * @param string $id    Panel id.
 * @param string $label Button label.
 * @param string $panel Panel inner HTML (escaped).
 */
$td_mega_item      = static function ( string $id, string $label, string $panel ): string {
	return '<li class="td-nav__item td-nav__item--mega" data-td-mega>'
		. '<button type="button" class="td-nav__link td-nav__link--button" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '" data-td-mega-toggle hidden>'
		. esc_html( $label ) . td_icon( 'chevron-down' ) . '</button>'
		. '<div class="td-mega" id="' . esc_attr( $id ) . '" data-td-mega-panel hidden><div class="td-mega__inner">' . $panel . '</div></div></li>';
};
$td_side_card      = static function ( string $label, string $title, string $text, string $url ): string {
	return '<a class="td-mega__studios" href="' . esc_url( $url ) . '"><span class="td-mega__label td-mega__label--dark">' . esc_html( $label ) . '</span>'
		. '<strong class="td-mega__studios-title">' . td_accent( $title ) . '</strong><span class="td-mega__studios-text">' . esc_html( $text ) . '</span>'
		. '<span class="td-mega__studios-go" aria-hidden="true">' . td_icon( 'arrow-up-right' ) . '</span></a>';
};
$td_ind_panel      = $td_industries ? '<div class="td-mega__col"><p class="td-mega__label">' . esc_html__( 'Industries we work with', 'techdella' ) . '</p>'
	. td_nav_mega_links( $td_industries, 'td-mega__list td-mega__list--3' )
	. ( $td_markets ? '<p class="td-mega__label td-mega__label--gap">' . esc_html__( 'Markets we serve', 'techdella' ) . '</p>' . td_nav_mega_links( $td_markets, 'td-mega__list td-mega__list--4' ) : '' ) . '</div>'
	. $td_side_card( __( 'Not sure where to start?', 'techdella' ), __( 'Get a growth *audit*', 'techdella' ), __( 'We review your search, ads and funnel and tell you what to fix first.', 'techdella' ), td_url( 'marketing', '/growth-audit/' ) ) : '';
$td_res_panel      = '<div class="td-mega__col td-mega__col--split">'
	. ( $td_guides ? '<div><p class="td-mega__label">' . esc_html__( 'Free guides (PDF)', 'techdella' ) . '</p>' . td_nav_mega_links( $td_guides ) . '</div>' : '' )
	. ( $td_tools ? '<div><p class="td-mega__label">' . esc_html__( 'Free tools', 'techdella' ) . '</p>' . td_nav_mega_links( $td_tools ) . '</div>' : '' )
	. '<div><p class="td-mega__label">' . esc_html__( 'Learn', 'techdella' ) . '</p>' . td_nav_mega_links( $td_learn ) . '</div></div>'
	. $td_side_card( __( 'Journal', 'techdella' ), __( 'Guides for B2B *growth*', 'techdella' ), __( 'SEO, AI search, paid ads, lead generation, email and automation.', 'techdella' ), td_url( 'journal', '/' ) );
$td_sheet_sub      = static function ( string $label, array $links ): string {
	if ( ! $links ) {
		return '';
	}
	$out = '<li><span class="td-sheet__link td-sheet__link--label">' . esc_html( $label ) . '</span><ul class="td-sheet__sub" role="list">';
	foreach ( $links as $l ) {
		$out .= '<li><a class="td-sheet__sublink" href="' . esc_url( $l['url'] ) . '">' . esc_html( $l['label'] ) . '</a></li>';
	}
	return $out . '</ul></li>';
};
$td_sheet_services = '';
foreach ( $td_services as $td_s ) {
	$td_sheet_services .= '<li><a class="td-sheet__sublink" href="' . esc_url( $td_s['url'] ) . '">' . esc_html( $td_s['label'] ) . '</a></li>';
}
?>
<header class="td-nav td-nav--marketing" data-td-nav>
	<div class="td-container td-nav__bar">
		<?php echo td_nav_brand(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<nav class="td-nav__primary" aria-label="<?php esc_attr_e( 'Main', 'techdella' ); ?>">
			<ul class="td-nav__list" role="list">
				<li class="td-nav__item td-nav__item--mega" data-td-mega>
					<a class="td-nav__link<?php echo $td_in_hub ? ' is-section' : ''; ?>" href="<?php echo esc_url( $td_hub ); ?>"<?php echo td_nav_is_current( $td_hub ) ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Services', 'techdella' ); ?></a>
					<button type="button" class="td-nav__mega-toggle" aria-expanded="false" aria-controls="td-mega" data-td-mega-toggle hidden>
						<?php echo td_icon( 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="td-sr"><?php esc_html_e( 'Show services', 'techdella' ); ?></span>
					</button>
					<div class="td-mega" id="td-mega" data-td-mega-panel hidden>
						<div class="td-mega__inner">
							<div class="td-mega__col">
								<p class="td-mega__label"><?php esc_html_e( 'Services', 'techdella' ); ?></p>
								<ul class="td-mega__grid" role="list"><?php echo $td_mega_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></ul>
								<a class="td-link td-mega__all" href="<?php echo esc_url( $td_hub ); ?>"><?php esc_html_e( 'All services', 'techdella' ); ?></a>
							</div>
							<a class="td-mega__studios" href="<?php echo esc_url( td_url( 'studios', '/' ) ); ?>">
								<span class="td-mega__label td-mega__label--dark"><?php esc_html_e( 'Build', 'techdella' ); ?></span>
								<strong class="td-mega__studios-title"><?php echo td_accent( __( 'TechDella *Studios*', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
								<span class="td-mega__studios-text"><?php esc_html_e( 'Websites, MVPs, branding and motion, designed and built by our product team.', 'techdella' ); ?></span>
								<span class="td-mega__studios-go" aria-hidden="true"><?php echo td_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</a>
						</div>
					</div>
				</li>
				<?php
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built.
				echo '' !== $td_ind_panel ? $td_mega_item( 'td-mega-industries', __( 'Industries', 'techdella' ), $td_ind_panel ) : '';
				echo $td_mega_item( 'td-mega-resources', __( 'Resources', 'techdella' ), $td_res_panel );
				echo td_nav_primary_items( $td_links );
				// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</ul>
		</nav>
		<div class="td-nav__actions">
			<?php echo $td_cta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php
			$td_sheet_items  = '<ul class="td-sheet__list" role="list">';
			$td_sheet_items .= '<li><a class="td-sheet__link" href="' . esc_url( $td_hub ) . '">' . esc_html__( 'Services', 'techdella' ) . '</a><ul class="td-sheet__sub" role="list">' . $td_sheet_services . '</ul></li>';
			$td_sheet_items .= $td_sheet_sub( __( 'Industries', 'techdella' ), $td_industries );
			$td_sheet_items .= $td_sheet_sub( __( 'Markets', 'techdella' ), $td_markets );
			$td_sheet_items .= $td_sheet_sub( __( 'Resources', 'techdella' ), array_merge( $td_learn, $td_guides, $td_tools ) );
			$td_sheet_items .= td_nav_primary_items( $td_links, 'sheet' );
			$td_sheet_items .= '</ul>';
			echo td_nav_sheet( $td_sheet_items, $td_cta ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</div>
</header>
