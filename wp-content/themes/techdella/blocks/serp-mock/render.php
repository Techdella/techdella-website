<?php
/**
 * SERP mock (§6.1: UI mocks built in HTML, not screenshots). Always labelled "Example".
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_results = array_filter( (array) ( $attributes['results'] ?? [] ), static fn( $r ) => is_array( $r ) && ! empty( $r['title'] ) );
if ( ! $td_results ) {
	return;
}
$td_caption = trim( (string) ( $attributes['caption'] ?? '' ) );
$td_caption = '' !== $td_caption ? $td_caption : __( 'Example result', 'techdella' );
$td_variant = 'plain' === ( $attributes['variant'] ?? '' ) ? 'plain' : 'sand';
$td_query   = (string) ( $attributes['query'] ?? '' );
?>
<div <?php echo td_block_wrapper( 'td-serp-mock-block' . ( ! empty( $attributes['contained'] ) ? ' td-container' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<figure class="td-serp-mock td-serp-mock--<?php echo esc_attr( $td_variant ); ?>">
		<?php if ( '' !== $td_query ) : ?>
			<p class="td-serp-mock__query"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg><span class="td-sr"><?php esc_html_e( 'Search:', 'techdella' ); ?> </span><?php echo esc_html( $td_query ); ?></p>
		<?php endif; ?>
		<ol class="td-serp-mock__results" role="list">
			<?php foreach ( $td_results as $td_r ) : ?>
				<?php
				$td_hl  = ! empty( $td_r['highlight'] ) || ! empty( $td_r['sponsored'] );
				$td_cls = 'td-serp-mock__result' . ( $td_hl ? ' td-serp-mock__result--hl' : ' td-serp-mock__result--muted' );
				?>
				<li class="<?php echo esc_attr( $td_cls ); ?>">
					<?php if ( ! empty( $td_r['rank'] ) ) : ?>
						<span class="td-serp-mock__rank"><?php echo esc_html( (string) $td_r['rank'] ); ?></span>
					<?php endif; ?>
					<span class="td-serp-mock__body">
						<span class="td-serp-mock__url">
							<?php if ( ! empty( $td_r['sponsored'] ) ) : ?>
								<strong><?php esc_html_e( 'Sponsored', 'techdella' ); ?></strong> ·
							<?php endif; ?>
							<?php echo esc_html( (string) ( $td_r['url'] ?? '' ) ); ?>
						</span>
						<span class="td-serp-mock__title"><?php echo esc_html( (string) $td_r['title'] ); ?></span>
						<?php if ( ! empty( $td_r['text'] ) ) : ?>
							<span class="td-serp-mock__text"><?php echo esc_html( (string) $td_r['text'] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $td_r['sitelinks'] ) && is_array( $td_r['sitelinks'] ) ) : ?>
							<span class="td-serp-mock__sitelinks">
								<?php foreach ( $td_r['sitelinks'] as $td_link ) : ?>
									<span><?php echo esc_html( (string) $td_link ); ?></span>
								<?php endforeach; ?>
							</span>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ol>
		<figcaption class="td-serp-mock__caption"><?php echo esc_html( $td_caption ); ?></figcaption>
	</figure>
</div>
