<?php
/**
 * Navigation (journal): logo + "Journal", links back to Marketing and Studios,
 * article search, audit CTA (Blog.dc.html).
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/nav-common.php';

$td_cta    = td_button( __( 'Get an audit', 'techdella' ), td_url( 'marketing', '/growth-audit/' ), 'primary', [ 'size' => 'sm' ] );
$td_links  = [
	[ __( 'Search marketing', 'techdella' ), td_url( 'marketing', '/' ) ],
	[ __( 'Studios', 'techdella' ), td_url( 'studios', '/' ) ],
];
$td_search = static function ( string $id ): string {
	return '<form class="td-nav__search" role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '">'
		. '<label class="td-sr" for="' . esc_attr( $id ) . '">' . esc_html__( 'Search articles', 'techdella' ) . '</label>'
		. '<input id="' . esc_attr( $id ) . '" type="search" name="s" placeholder="' . esc_attr__( 'Search articles', 'techdella' ) . '" value="' . esc_attr( get_search_query() ) . '">'
		. '</form>';
};
?>
<header class="td-nav td-nav--journal" data-td-nav>
	<div class="td-container td-nav__bar">
		<?php echo td_nav_brand( __( 'Journal', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<nav class="td-nav__primary" aria-label="<?php esc_attr_e( 'Main', 'techdella' ); ?>">
			<ul class="td-nav__list" role="list"><?php echo td_nav_primary_items( $td_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
		</nav>
		<div class="td-nav__actions">
			<?php echo $td_search( 'td-nav-search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $td_cta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php
			$td_sheet_items = $td_search( 'td-sheet-search' ) . '<ul class="td-sheet__list" role="list">' . td_nav_primary_items( $td_links, 'sheet' ) . '</ul>';
			echo td_nav_sheet( $td_sheet_items, $td_cta ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</div>
</header>
