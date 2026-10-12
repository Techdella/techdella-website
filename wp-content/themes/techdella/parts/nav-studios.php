<?php
/**
 * Navigation (studios): logo + "Studios", section links, link back to
 * Marketing, "Start a project" CTA (Studios.dc.html).
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/nav-common.php';

$td_cta   = td_button( __( 'Start a project', 'techdella' ), td_url( 'studios', '/start/' ), 'primary', [ 'size' => 'sm' ] );
$td_links = [
	[ __( 'Work', 'techdella' ), td_url( 'studios', '/work/' ) ],
	[ __( 'Services', 'techdella' ), td_url( 'studios', '/#services' ) ],
	[ __( 'Packages', 'techdella' ), td_url( 'studios', '/#packages' ) ],
	[ __( 'Team', 'techdella' ), td_url( 'studios', '/about/' ) ],
	[ __( 'Guides', 'techdella' ), td_url( 'studios', '/guides/' ) ],
	[ __( 'Search marketing ↗', 'techdella' ), td_url( 'marketing', '/' ) ],
];
?>
<header class="td-nav td-nav--studios" data-td-nav>
	<div class="td-container td-nav__bar">
		<?php echo td_nav_brand( __( 'Studios', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<nav class="td-nav__primary" aria-label="<?php esc_attr_e( 'Main', 'techdella' ); ?>">
			<ul class="td-nav__list" role="list"><?php echo td_nav_primary_items( $td_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
		</nav>
		<div class="td-nav__actions">
			<?php echo $td_cta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo td_nav_sheet( '<ul class="td-sheet__list" role="list">' . td_nav_primary_items( $td_links, 'sheet' ) . '</ul>', $td_cta ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
</header>
