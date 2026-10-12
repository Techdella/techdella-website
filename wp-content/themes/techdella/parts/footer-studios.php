<?php
/**
 * Footer (studios): dark brand row with links (Studios.dc.html) + legal bar.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/nav-common.php';

$td_links = td_footer_links(
	'footer-1',
	[
		__( 'Work', 'techdella' )               => td_url( 'studios', '/work/' ),
		__( 'Packages', 'techdella' )           => td_url( 'studios', '/#packages' ),
		__( 'Start a project', 'techdella' )    => td_url( 'studios', '/start/' ),
		__( 'Guides', 'techdella' )             => td_url( 'studios', '/guides/' ),
		__( 'Search marketing ↗', 'techdella' ) => td_url( 'marketing', '/' ),
		__( 'Journal', 'techdella' )            => td_url( 'journal', '/' ),
	]
);
?>
<footer class="td-footer td-footer--compact td-footer--dark td-footer--studios">
	<div class="td-container td-footer__inner">
		<div class="td-footer__row">
			<?php echo td_nav_brand( __( 'Studios', 'techdella' ), 'white', 'span' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<nav aria-label="<?php esc_attr_e( 'Footer', 'techdella' ); ?>"><ul class="td-footer__inline td-footer__inline--lg" role="list"><?php echo td_footer_list( $td_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul></nav>
		</div>
		<?php echo td_footer_bottom( true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</footer>
