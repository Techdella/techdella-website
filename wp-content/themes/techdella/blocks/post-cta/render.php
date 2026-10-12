<?php
/**
 * Post CTA: the post's one service CTA (§10.7).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';

$td_services = [
	'seo'                    => __( 'SEO', 'techdella' ),
	'technical-seo'          => __( 'technical SEO', 'techdella' ),
	'local-seo'              => __( 'local SEO', 'techdella' ),
	'ai-search-optimization' => __( 'AI search', 'techdella' ),
	'paid-ads'               => __( 'paid ads', 'techdella' ),
	'lead-generation'        => __( 'lead generation', 'techdella' ),
	'email-marketing'        => __( 'email marketing', 'techdella' ),
	'marketing-automation'   => __( 'marketing automation', 'techdella' ),
	'studios'                => __( 'building your product', 'techdella' ),
];
$td_service  = sanitize_key( (string) ( $attributes['service'] ?? '' ) );
if ( '' === $td_service ) {
	$td_id      = (int) ( $attributes['postId'] ?? 0 );
	$td_id      = $td_id ? $td_id : (int) get_the_ID();
	$td_service = $td_id ? sanitize_key( (string) td_field( 'cta_service', $td_id ) ) : '';
}
$td_title = trim( (string) ( $attributes['title'] ?? '' ) );
if ( 'studios' === td_site() ) {
	// Studios guides: the CTA names the Studios service (studio_service slug) and goes to /start/.
	$td_ss                  = '' !== $td_service && post_type_exists( 'studio_service' ) ? get_page_by_path( $td_service, OBJECT, 'studio_service' ) : null;
	$td_services['studios'] = $td_ss instanceof WP_Post ? strtolower( get_the_title( $td_ss ) ) : $td_services['studios'];
	$td_service             = 'studios';
}
if ( '' === $td_title && ! isset( $td_services[ $td_service ] ) ) {
	echo td_a3_notice( __( 'Post CTA: set the post’s cta_service field or a title.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_studios = 'studios' === $td_service;
$td_label   = $td_services[ $td_service ] ?? '';
/* translators: %s: service name, e.g. "paid ads" */
$td_title = '' !== $td_title ? $td_title : sprintf( __( 'Want help with %s?', 'techdella' ), $td_label );
$td_text  = trim( (string) ( $attributes['text'] ?? '' ) );
$td_text  = '' !== $td_text ? $td_text : ( $td_studios ? __( 'Send a short brief. We’ll reply with a scope and an estimate.', 'techdella' ) : __( 'Tell us where you are today. The Growth Audit shows what to fix first.', 'techdella' ) );
$td_btn   = trim( (string) ( $attributes['buttonLabel'] ?? '' ) );
$td_btn   = '' !== $td_btn ? $td_btn : ( $td_studios ? __( 'Start a project', 'techdella' ) : __( 'Get an audit', 'techdella' ) );
$td_url   = trim( (string) ( $attributes['buttonUrl'] ?? '' ) );
$td_url   = '' !== $td_url ? td_a3_href( $td_url ) : ( $td_studios ? td_url( 'studios', '/start/' ) : td_url( 'marketing', '/growth-audit/' ) );
?>
<aside <?php echo td_block_wrapper( 'td-post-cta' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php esc_attr_e( 'Work with us', 'techdella' ); ?>">
	<div class="td-post-cta__copy">
		<p class="td-post-cta__title"><?php echo td_a3_text( $td_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<p class="td-post-cta__text"><?php echo td_a3_text( $td_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	</div>
	<?php echo td_button( $td_btn, $td_url, 'light', [ 'size' => 'sm' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</aside>
