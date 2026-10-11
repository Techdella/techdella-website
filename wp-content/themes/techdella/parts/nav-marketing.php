<?php
/**
 * Navigation (marketing): sticky header, services mega-menu, mobile sheet (§2.3).
 * Without JS, "Services" is a plain link to /services/ and the mobile menu is a <details>.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/nav-common.php';

$td_services  = td_nav_services();
$td_hub       = td_url( 'marketing', '/services/' );
$td_cta       = td_button( __( 'Get an audit', 'techdella' ), td_url( 'marketing', '/growth-audit/' ), 'primary', [ 'size' => 'sm' ] );
$td_links     = [
	[ __( 'Industries', 'techdella' ), td_url( 'marketing', '/solution/' ) ],
	[ __( 'Customers', 'techdella' ), td_url( 'marketing', '/case-studies/' ) ],
	[ __( 'Resources', 'techdella' ), td_url( 'marketing', '/resources/' ) ],
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
				<?php echo td_nav_primary_items( $td_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</ul>
		</nav>
		<div class="td-nav__actions">
			<?php echo $td_cta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php
			$td_sheet_items  = '<ul class="td-sheet__list" role="list">';
			$td_sheet_items .= '<li><a class="td-sheet__link" href="' . esc_url( $td_hub ) . '">' . esc_html__( 'Services', 'techdella' ) . '</a><ul class="td-sheet__sub" role="list">' . $td_sheet_services . '</ul></li>';
			$td_sheet_items .= td_nav_primary_items( $td_links, 'sheet' );
			$td_sheet_items .= '</ul>';
			echo td_nav_sheet( $td_sheet_items, $td_cta ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</div>
</header>
