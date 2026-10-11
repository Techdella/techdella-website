<?php
/**
 * Footer (marketing): brand + tagline, Services / Explore / Company / Social columns,
 * copyright + legal links (Main.dc.html).
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/nav-common.php';

$td_services = [];
foreach ( td_nav_services() as $td_s ) {
	$td_services[ $td_s['label'] ] = $td_s['url'];
}
$td_company  = td_footer_links(
	'footer-2',
	[
		__( 'How we work', 'techdella' )       => td_url( 'marketing', '/#how' ),
		__( 'Customers', 'techdella' )         => td_url( 'marketing', '/case-studies/' ),
		__( 'Pricing', 'techdella' )           => td_url( 'marketing', '/pricing/' ),
		__( 'About us', 'techdella' )          => td_url( 'marketing', '/about-us/' ),
		__( 'Journal', 'techdella' )           => td_url( 'journal', '/' ),
		__( 'TechDella Studios', 'techdella' ) => td_url( 'studios', '/' ),
	]
);
$td_explore  = [
	__( 'Case studies', 'techdella' )    => td_url( 'marketing', '/case-studies/' ),
	__( 'Locations', 'techdella' )       => td_url( 'marketing', '/locations/' ),
	__( 'Glossary', 'techdella' )        => td_url( 'marketing', '/glossary/' ),
	__( 'Free tools', 'techdella' )      => td_url( 'marketing', '/tools/' ),
	__( 'Business guides', 'techdella' ) => td_url( 'marketing', '/business/' ),
];
$td_services = td_footer_links( 'footer-1', $td_services );
$td_social   = td_social_links();
?>
<footer class="td-footer td-footer--marketing">
	<div class="td-container td-footer__inner">
		<div class="td-footer__top">
			<div class="td-footer__brand">
				<?php echo td_nav_brand( '', 'default', 'span' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p class="td-footer__tagline"><?php esc_html_e( 'Get found. Get noticed. Grow.', 'techdella' ); ?></p>
			</div>
			<nav class="td-footer__col" aria-labelledby="td-footer-services">
				<h2 class="td-footer__heading" id="td-footer-services"><?php esc_html_e( 'Services', 'techdella' ); ?></h2>
				<ul role="list"><?php echo td_footer_list( $td_services ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
			</nav>
			<nav class="td-footer__col" aria-labelledby="td-footer-explore">
				<h2 class="td-footer__heading" id="td-footer-explore"><?php esc_html_e( 'Explore', 'techdella' ); ?></h2>
				<ul role="list"><?php echo td_footer_list( $td_explore ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
			</nav>
			<nav class="td-footer__col" aria-labelledby="td-footer-company">
				<h2 class="td-footer__heading" id="td-footer-company"><?php esc_html_e( 'Company', 'techdella' ); ?></h2>
				<ul role="list"><?php echo td_footer_list( $td_company ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
			</nav>
			<?php if ( $td_social ) : ?>
				<nav class="td-footer__col" aria-labelledby="td-footer-social">
					<h2 class="td-footer__heading" id="td-footer-social"><?php esc_html_e( 'Social', 'techdella' ); ?></h2>
					<ul role="list"><?php echo td_footer_list( $td_social, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
				</nav>
			<?php endif; ?>
		</div>
		<?php echo td_footer_bottom(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</footer>
