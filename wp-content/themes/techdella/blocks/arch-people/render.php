<?php
/**
 * Arch people: team portraits (td_team CPT or inline list).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';

$td_people = [];
if ( 'inline' !== ( $attributes['source'] ?? 'auto' ) && post_type_exists( 'td_team' ) ) {
	$td_ids = get_posts(
		[
			'post_type'      => 'td_team',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'orderby'        => [
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			],
			'fields'         => 'ids',
			'no_found_rows'  => true,
		]
	);
	foreach ( $td_ids as $td_id ) {
		$td_photo    = td_field( 'photo', (int) $td_id );
		$td_photo    = is_array( $td_photo ) ? (int) ( $td_photo['ID'] ?? $td_photo['id'] ?? 0 ) : (int) $td_photo;
		$td_people[] = [
			'name'     => get_the_title( (int) $td_id ),
			'role'     => (string) td_field( 'role', (int) $td_id ),
			'image'    => $td_photo ? $td_photo : (int) get_post_thumbnail_id( (int) $td_id ),
			'linkedin' => (string) td_field( 'linkedin', (int) $td_id ),
		];
	}
}
if ( ! $td_people ) {
	$td_people = array_values( array_filter( (array) ( $attributes['people'] ?? [] ), static fn( $p ) => is_array( $p ) && ! empty( $p['name'] ) ) );
}
if ( ! $td_people ) {
	echo td_a3_notice( __( 'Arch people: no team members yet (add td_team posts or inline people).', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_cards   = array_values( array_filter( (array) ( $attributes['asideCards'] ?? [] ), static fn( $c ) => is_array( $c ) && ! empty( $c['title'] ) ) );
$td_aside   = ! empty( $attributes['asideTitle'] ) || $td_cards;
$td_surface = 'paper' === ( $attributes['surface'] ?? '' ) ? '' : ' td-section--canvas';
$td_title   = (string) ( $attributes['title'] ?? '' );
$td_list_id = wp_unique_id( 'td-people-' );
?>
<section <?php echo td_block_wrapper( 'td-arch-people td-section' . $td_surface ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-arch-people__inner">
		<?php if ( '' !== $td_title || ! empty( $attributes['label'] ) ) : ?>
			<div class="td-arch-people__head">
				<?php echo td_chip( (string) ( $attributes['label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( '' !== $td_title ) : ?>
					<h2 class="td-arch-people__title" id="<?php echo esc_attr( $td_list_id ); ?>-h"><?php echo td_accent( $td_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<?php endif; ?>
				<?php if ( ! empty( $attributes['intro'] ) ) : ?>
					<p class="td-arch-people__intro"><?php echo td_a3_text( $attributes['intro'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $attributes['primaryLabel'] ) || ! empty( $attributes['secondaryLabel'] ) ) : ?>
					<div class="td-arch-people__ctas">
						<?php
						if ( ! empty( $attributes['primaryLabel'] ) ) {
							echo td_button( (string) $attributes['primaryLabel'], td_a3_href( (string) ( $attributes['primaryUrl'] ?? '/' ) ), 'primary', [ 'size' => 'sm' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						if ( ! empty( $attributes['secondaryLabel'] ) ) {
							echo td_button( (string) $attributes['secondaryLabel'], td_a3_href( (string) ( $attributes['secondaryUrl'] ?? '/' ) ), 'secondary', [ 'size' => 'sm' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="td-arch-people__body<?php echo $td_aside ? ' td-arch-people__body--aside' : ''; ?>">
			<?php if ( $td_aside ) : ?>
				<div class="td-arch-people__aside">
					<?php if ( ! empty( $attributes['asideTitle'] ) ) : ?>
						<h3 class="td-arch-people__aside-title"><?php echo td_a3_text( $attributes['asideTitle'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $attributes['asideText'] ) ) : ?>
						<p class="td-arch-people__aside-text"><?php echo td_a3_text( $attributes['asideText'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<?php endif; ?>
					<?php if ( $td_cards ) : ?>
						<ul class="td-arch-people__cards" role="list">
							<?php foreach ( $td_cards as $td_c ) : ?>
								<li>
									<a class="td-arch-people__card<?php echo ! empty( $td_c['dark'] ) ? ' td-arch-people__card--dark' : ''; ?>" href="<?php echo esc_url( td_a3_href( (string) ( $td_c['url'] ?? '/' ) ) ); ?>">
										<?php if ( ! empty( $td_c['tag'] ) ) : ?>
											<span class="td-arch-people__card-tag"><?php echo esc_html( (string) $td_c['tag'] ); ?></span>
										<?php endif; ?>
										<span class="td-arch-people__card-foot">
											<strong><?php echo esc_html( (string) $td_c['title'] ); ?></strong>
											<span class="td-arch-people__card-arrow"><?php echo td_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="td-arch-people__scroller" role="region" tabindex="0" aria-label="<?php esc_attr_e( 'Team members', 'techdella' ); ?>">
				<ul class="td-arch-people__list" role="list">
					<?php foreach ( $td_people as $td_p ) : ?>
						<li class="td-arch-people__person" id="<?php echo esc_attr( sanitize_title( (string) ( $td_p['name'] ?? '' ) ) ); ?>">
							<div class="td-arch td-arch-people__photo">
								<?php
								$td_img = ! empty( $td_p['image'] ) ? td_image(
									$td_p['image'],
									[
										'alt'   => '',
										'sizes' => '240px',
									]
								) : '';
								echo $td_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</div>
							<p class="td-arch-people__name">
								<?php if ( ! empty( $td_p['linkedin'] ) ) : ?>
									<a href="<?php echo esc_url( (string) $td_p['linkedin'] ); ?>" rel="noopener"><?php echo esc_html( (string) $td_p['name'] ); ?><span class="td-sr"> <?php esc_html_e( 'on LinkedIn', 'techdella' ); ?></span></a>
								<?php else : ?>
									<?php echo esc_html( (string) $td_p['name'] ); ?>
								<?php endif; ?>
							</p>
							<?php if ( ! empty( $td_p['role'] ) ) : ?>
								<p class="td-arch-people__role"><?php echo esc_html( (string) $td_p['role'] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</section>
