<?php
/**
 * Platform badges (§2.3, §0.6): official logo file if present, else text badge.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

// Known platforms: slug => [ name, text mark, ink mark ]. Only those used in the prototypes.
$td_known = [
	'google-search-console' => [ 'Google Search Console', 'G', true ],
	'ga4'                   => [ 'GA4', 'GA', false ],
	'semrush'               => [ 'Semrush', 'S', false ],
	'wordpress-webflow'     => [ 'WordPress & Webflow', 'W', false ],
	'google-search'         => [ 'Google Search', 'G', true ],
	'google-ads'            => [ 'Google Ads', 'G', true ],
	'meta'                  => [ 'Meta Ads', 'M', true ],
	'tiktok'                => [ 'TikTok Ads', 'T', true ],
	'linkedin'              => [ 'LinkedIn Ads', 'in', true ],
	'youtube'               => [ 'YouTube', 'Y', false ],
	'chatgpt'               => [ 'ChatGPT', 'C', true ],
	'perplexity'            => [ 'Perplexity', 'P', false ],
	'google-ai-overviews'   => [ 'AI Overviews', 'G', false ],
	'gemini'                => [ 'Gemini', 'Ge', false ],
	'copilot'               => [ 'Copilot', 'Co', false ],
	'bing'                  => [ 'Bing', 'B', false ],
];

$td_items = [];
foreach ( (array) ( $attributes['items'] ?? [] ) as $td_item ) {
	if ( is_string( $td_item ) ) {
		$td_item = [ 'slug' => $td_item ];
	}
	if ( ! is_array( $td_item ) ) {
		continue;
	}
	$td_slug    = sanitize_title( (string) ( $td_item['slug'] ?? ( $td_item['name'] ?? '' ) ) );
	$td_def     = $td_known[ $td_slug ] ?? [ (string) ( $td_item['name'] ?? $td_slug ), '', false ];
	$td_name    = (string) ( $td_item['name'] ?? $td_def[0] );
	$td_items[] = [
		'name' => $td_name,
		'slug' => $td_slug,
		'mark' => (string) ( $td_item['mark'] ?? $td_def[1] ),
		'ink'  => (bool) ( $td_item['ink'] ?? $td_def[2] ),
		'note' => (string) ( $td_item['note'] ?? '' ),
	];
}
if ( ! $td_items || '' === $td_items[0]['name'] ) {
	return;
}

$td_layout  = 'grid' === ( $attributes['layout'] ?? '' ) ? 'grid' : 'row';
$td_surface = in_array( $attributes['surface'] ?? '', [ 'canvas', 'sand', 'dark' ], true ) ? $attributes['surface'] : 'paper';
$td_section = '' !== (string) ( $attributes['title'] ?? '' );
$td_wrap    = $td_section || ! empty( $attributes['contained'] );

ob_start();
?>
<?php if ( ! $td_section && ! empty( $attributes['label'] ) ) : ?>
	<span class="td-platform-badges__label"><?php echo esc_html( (string) $attributes['label'] ); ?></span>
<?php endif; ?>
<ul class="td-platform-badges__list td-platform-badges__list--<?php echo esc_attr( $td_layout ); ?>" role="list">
	<?php foreach ( $td_items as $td_p ) : ?>
		<li class="td-platform-badges__item">
			<?php echo td_platform_badge( $td_p['name'], $td_p['slug'], $td_p['mark'], $td_p['ink'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( 'grid' === $td_layout && '' !== $td_p['note'] ) : ?>
				<span class="td-platform-badges__note"><?php echo esc_html( $td_p['note'] ); ?></span>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
<?php
$td_inner = (string) ob_get_clean();
$td_class = 'td-platform-badges td-platform-badges--' . $td_layout . ( $td_section ? ' td-section' . ( 'paper' !== $td_surface ? ' td-section--' . $td_surface : '' ) : '' );
?>
<div <?php echo td_block_wrapper( $td_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $td_wrap ) : ?>
		<div class="td-container td-platform-badges__inner">
			<?php
			if ( $td_section ) {
				echo td_section_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					[
						'label' => (string) ( $attributes['label'] ?? '' ),
						'title' => (string) $attributes['title'],
						'intro' => (string) ( $attributes['intro'] ?? '' ),
					]
				);
			}
			echo $td_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	<?php else : ?>
		<?php echo $td_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
</div>
