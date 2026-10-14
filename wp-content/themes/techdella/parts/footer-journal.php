<?php
/**
 * Footer (journal): brand row with cross-site links (Blog.dc.html) + legal bar.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/nav-common.php';

$td_links = td_footer_links(
	'footer-1',
	[
		__( 'Search marketing', 'techdella' ) => td_url( 'marketing', '/' ),
		__( 'Studios', 'techdella' )          => td_url( 'studios', '/' ),
		__( 'Get an audit', 'techdella' )     => td_url( 'marketing', '/growth-audit/' ),
		__( 'Newsletter', 'techdella' )       => td_url( 'journal', '/newsletter/' ),
		__( 'Write for us', 'techdella' )     => td_url( 'journal', '/write-for-us/' ),
	]
);
?>
<footer class="td-footer td-footer--compact td-footer--journal">
	<div class="td-container td-footer__inner">
		<div class="td-footer__row">
			<?php echo td_nav_brand( __( 'Journal', 'techdella' ), 'default', 'span' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<nav aria-label="<?php esc_attr_e( 'Footer', 'techdella' ); ?>"><ul class="td-footer__inline td-footer__inline--lg" role="list"><?php echo td_footer_list( $td_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul></nav>
		</div>
		<?php echo td_footer_bottom( true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</footer>
