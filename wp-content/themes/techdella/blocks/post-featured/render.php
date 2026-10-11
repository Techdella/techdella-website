<?php
/**
 * Featured post panel (Journal home).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/post-card/lib.php';

$td_id = (int) ( $attributes['postId'] ?? 0 );
if ( ! $td_id ) {
	$td_sticky = array_map( 'intval', (array) get_option( 'sticky_posts', [] ) );
	$td_ids    = get_posts(
		[
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'post__in'       => $td_sticky ? $td_sticky : [],
			'no_found_rows'  => true,
		]
	);
	if ( ! $td_ids && $td_sticky ) {
		$td_ids = get_posts(
			[
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			]
		);
	}
	$td_id = $td_ids ? (int) $td_ids[0] : 0;
}
$td_p      = $td_id ? td_a3_post_data( $td_id ) : null;
$td_person = $td_p ? $td_p['authorP'] : null;
$td_photo  = $attributes['image'] ?? '';
if ( ! $td_p && td_a3_is_preview() && ! empty( $attributes['item']['title'] ) ) {
	$td_item   = (array) $attributes['item'];
	$td_p      = td_a3_post_inline( $td_item );
	$td_person = [
		'name'  => (string) ( $td_item['author'] ?? '' ),
		'role'  => (string) ( $td_item['role'] ?? '' ),
		'photo' => $td_item['image'] ?? '',
	];
}
if ( ! $td_p ) {
	echo td_a3_notice( __( 'Featured post: no published posts yet.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
if ( ! $td_photo && $td_person && ! empty( $td_person['photo'] ) ) {
	$td_photo = $td_person['photo'];
}
$td_word    = '' !== trim( (string) ( $attributes['word'] ?? '' ) ) ? (string) $attributes['word'] : (string) $td_p['category'];
$td_summary = '' !== trim( (string) ( $attributes['summary'] ?? '' ) ) ? (string) $attributes['summary'] : (string) $td_p['excerpt'];
$td_img     = $td_photo ? td_image(
	$td_photo,
	[
		'alt'   => $td_person ? (string) $td_person['name'] : '',
		'sizes' => '300px',
	]
) : '';
$td_img     = str_starts_with( $td_img, '<!--' ) ? '' : $td_img;
$td_chips   = array_slice( array_filter( array_map( 'strval', (array) ( $attributes['chips'] ?? [] ) ) ), 0, 3 );
?>
<section <?php echo td_block_wrapper( 'td-post-featured' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container">
		<div class="td-post-featured__panel">
			<div class="td-post-featured__copy">
				<p class="td-post-featured__label">
					<span class="td-post-featured__mark" aria-hidden="true"><?php echo td_logo( 'mark', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span><strong><?php echo esc_html( (string) ( $attributes['label'] ?? '' ) ); ?></strong>
					<?php if ( '' !== (string) $td_p['category'] ) : ?>
						<br><span class="td-post-featured__in">
							<?php esc_html_e( 'In', 'techdella' ); ?>
							<?php if ( '' !== (string) $td_p['catUrl'] ) : ?>
								<a href="<?php echo esc_url( (string) $td_p['catUrl'] ); ?>"><?php echo esc_html( (string) $td_p['category'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( (string) $td_p['category'] ); ?>
							<?php endif; ?>
						</span>
					<?php endif; ?>
					</span>
					<?php if ( ! empty( $td_p['example'] ) ) : ?>
						<span class="td-post-featured__example"><?php esc_html_e( 'Example', 'techdella' ); ?></span>
					<?php endif; ?>
				</p>
				<p class="td-post-featured__word" aria-hidden="true"><?php echo esc_html( $td_word ); ?><sup>+</sup></p>
				<h2 class="td-post-featured__title"><a href="<?php echo esc_url( '' !== (string) $td_p['url'] ? (string) $td_p['url'] : '#' ); ?>"><?php echo esc_html( (string) $td_p['title'] ); ?></a></h2>
				<?php if ( '' !== $td_summary ) : ?>
					<p class="td-post-featured__summary"><?php echo td_a3_text( $td_summary ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
				<p class="td-post-featured__byline">
					<?php if ( $td_person && '' !== (string) $td_person['name'] ) : ?>
						<span><strong><?php echo esc_html( (string) $td_person['name'] ); ?></strong><?php echo '' !== (string) ( $td_person['role'] ?? '' ) ? '<br><span class="td-post-featured__role">' . esc_html( (string) $td_person['role'] ) . '</span>' : ''; ?></span>
						<span class="td-post-featured__sep" aria-hidden="true">/</span>
					<?php endif; ?>
					<?php if ( ! empty( $td_p['minutes'] ) ) : ?>
						<span><?php echo esc_html( sprintf( /* translators: %d: minutes */ _n( '%d min read', '%d min read', (int) $td_p['minutes'], 'techdella' ), (int) $td_p['minutes'] ) ); ?></span>
					<?php endif; ?>
				</p>
				<div class="td-post-featured__ctas">
					<?php
					if ( '' !== (string) $td_p['url'] ) {
						echo td_button( (string) ( $attributes['ctaLabel'] ?? __( 'Read the guide', 'techdella' ) ), (string) $td_p['url'], 'primary', [ 'size' => 'sm' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					if ( '' !== (string) $td_p['catUrl'] ) :
						?>
						<a class="td-link td-post-featured__all" href="<?php echo esc_url( (string) $td_p['catUrl'] ); ?>"><?php echo esc_html( sprintf( /* translators: %s: category name */ __( 'All %s posts', 'techdella' ), (string) $td_p['category'] ) ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<div class="td-post-featured__visual">
				<?php if ( '' !== $td_img ) : ?>
					<div class="td-arch td-post-featured__photo"><?php echo $td_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php endif; ?>
				<?php foreach ( $td_chips as $td_i => $td_chip ) : ?>
					<span class="td-post-featured__chip td-post-featured__chip--<?php echo esc_attr( (string) ( $td_i + 1 ) ); ?>"><span class="td-post-featured__check" aria-hidden="true">✓</span><?php echo esc_html( $td_chip ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
