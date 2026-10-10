<?php
/**
 * Service switcher: links between service pages, current one marked aria-current="page".
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_href  = static function ( string $url ): string {
	if ( preg_match( '/^(marketing|journal|studios):(\/.*)$/', $url, $m ) ) {
		return td_url( $m[1], $m[2] );
	}
	return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
};
$td_items = array_filter( (array) ( $attributes['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['label'] ) && ! empty( $i['url'] ) );
if ( ! $td_items ) {
	$td_items = [
		[ 'label' => __( 'SEO', 'techdella' ), 'url' => '/services/seo/' ],
		[ 'label' => __( 'AI Search', 'techdella' ), 'url' => '/services/ai-search-optimization/' ],
		[ 'label' => __( 'Paid ads', 'techdella' ), 'url' => '/services/paid-ads/' ],
		[ 'label' => __( 'Lead generation', 'techdella' ), 'url' => '/services/lead-generation/' ],
		[ 'label' => __( 'Email marketing', 'techdella' ), 'url' => '/services/email-marketing/' ],
		[ 'label' => __( 'AI automations', 'techdella' ), 'url' => '/services/marketing-automation/' ],
	];
}

$td_path    = static fn( string $url ): string => trailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
$td_current = (string) ( $attributes['current'] ?? '' );
$td_request = isset( $_SERVER['REQUEST_URI'] ) ? $td_path( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '';
?>
<nav <?php echo td_block_wrapper( 'td-service-switcher', [ 'aria-label' => (string) ( $attributes['label'] ?? __( 'Services', 'techdella' ) ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container">
		<ul class="td-service-switcher__list" role="list">
			<?php
			foreach ( $td_items as $td_item ) :
				$td_url = $td_href( (string) $td_item['url'] );
				if ( '' !== $td_current ) {
					$td_on = $td_current === $td_item['label'] || $td_path( $td_current ) === $td_path( $td_url );
				} else {
					$td_on = '' !== $td_request && $td_request === $td_path( $td_url );
				}
				?>
				<li><a class="td-service-switcher__link" href="<?php echo esc_url( $td_url ); ?>"<?php echo $td_on ? ' aria-current="page"' : ''; ?>><?php echo esc_html( (string) $td_item['label'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</nav>
