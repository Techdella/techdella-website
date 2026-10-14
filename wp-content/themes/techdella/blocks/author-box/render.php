<?php
/**
 * Author box: named author, reviewer, last reviewed (§8.1 E-E-A-T).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/post-card/lib.php';

$td_id       = (int) ( $attributes['postId'] ?? 0 );
$td_id       = $td_id ? $td_id : (int) get_the_ID();
$td_post     = $td_id ? get_post( $td_id ) : null;
$td_author   = null;
$td_reviewer = null;
$td_date     = '';
$td_example  = false;

if ( $td_post && 'post' === $td_post->post_type ) {
	$td_author = td_a3_person( (int) $td_post->post_author );
	$td_rev    = td_field( 'reviewer', $td_id );
	$td_rev_id = is_array( $td_rev ) ? (int) ( $td_rev['ID'] ?? $td_rev['id'] ?? 0 ) : ( is_object( $td_rev ) && isset( $td_rev->ID ) ? (int) $td_rev->ID : (int) $td_rev );
	if ( $td_rev_id && $td_rev_id !== (int) $td_post->post_author ) {
		$td_reviewer = td_a3_person( $td_rev_id );
	}
	$td_raw  = trim( (string) td_field( 'last_reviewed', $td_id ) );
	$td_ts   = '' !== $td_raw ? strtotime( $td_raw ) : false;
	$td_date = $td_ts ? gmdate( 'Y-m-d', $td_ts ) : '';
} elseif ( td_a3_is_preview() && ! empty( $attributes['item']['author'] ) ) {
	$td_item     = (array) $attributes['item'];
	$td_example  = true;
	$td_author   = [
		'name'     => (string) $td_item['author'],
		'role'     => (string) ( $td_item['role'] ?? '' ),
		'bio'      => (string) ( $td_item['bio'] ?? '' ),
		'photo'    => $td_item['image'] ?? '',
		'linkedin' => '',
		'url'      => '',
	];
	$td_reviewer = ! empty( $td_item['reviewer'] ) ? [
		'name' => (string) $td_item['reviewer'],
		'role' => (string) ( $td_item['reviewerRole'] ?? '' ),
		'url'  => '',
	] : null;
	$td_date     = (string) ( $td_item['lastReviewed'] ?? '' );
}
if ( ! $td_author ) {
	echo td_a3_notice( __( 'Author box: the post needs a named author (link the user to a td_team profile).', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_photo = ! empty( $td_author['photo'] ) ? td_image(
	$td_author['photo'],
	[
		'alt'   => '',
		'sizes' => '64px',
		'width' => 64,
	]
) : '';
$td_photo = str_starts_with( $td_photo, '<!--' ) ? '' : $td_photo;
?>
<aside <?php echo td_block_wrapper( 'td-author-box' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php esc_attr_e( 'About the author', 'techdella' ); ?>">
	<?php if ( '' !== $td_photo ) : ?>
		<div class="td-author-box__photo"><?php echo $td_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php endif; ?>
	<div class="td-author-box__body">
		<p class="td-author-box__name">
			<span class="td-author-box__k"><?php esc_html_e( 'Written by', 'techdella' ); ?></span>
			<?php if ( ! empty( $td_author['url'] ) ) : ?>
				<a href="<?php echo esc_url( (string) $td_author['url'] ); ?>" rel="author"><?php echo esc_html( (string) $td_author['name'] ); ?></a>
			<?php else : ?>
				<strong><?php echo esc_html( (string) $td_author['name'] ); ?></strong>
			<?php endif; ?>
			<?php if ( '' !== (string) $td_author['role'] ) : ?>
				<span class="td-author-box__role"><?php echo esc_html( (string) $td_author['role'] ); ?></span>
			<?php endif; ?>
			<?php if ( $td_example ) : ?>
				<span class="td-author-box__example"><?php esc_html_e( 'Example', 'techdella' ); ?></span>
			<?php endif; ?>
		</p>
		<?php if ( '' !== trim( (string) $td_author['bio'] ) ) : ?>
			<p class="td-author-box__bio"><?php echo td_a3_text( $td_author['bio'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $td_author['linkedin'] ) ) : ?>
			<p class="td-author-box__links"><a class="td-link" href="<?php echo esc_url( (string) $td_author['linkedin'] ); ?>" rel="noopener me"><?php echo esc_html( sprintf( /* translators: %s: person name */ __( '%s on LinkedIn', 'techdella' ), (string) $td_author['name'] ) ); ?></a></p>
		<?php endif; ?>
		<?php if ( ( ! empty( $attributes['showReviewer'] ) || ! isset( $attributes['showReviewer'] ) ) && ( $td_reviewer || '' !== $td_date ) ) : ?>
			<dl class="td-author-box__review">
				<?php if ( $td_reviewer ) : ?>
					<div>
						<dt><?php esc_html_e( 'Reviewed by', 'techdella' ); ?></dt>
						<dd>
							<?php if ( ! empty( $td_reviewer['url'] ) ) : ?>
								<a href="<?php echo esc_url( (string) $td_reviewer['url'] ); ?>"><?php echo esc_html( (string) $td_reviewer['name'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( (string) $td_reviewer['name'] ); ?>
							<?php endif; ?>
							<?php echo '' !== (string) ( $td_reviewer['role'] ?? '' ) ? esc_html( ', ' . $td_reviewer['role'] ) : ''; ?>
						</dd>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $td_date ) : ?>
					<div>
						<dt><?php esc_html_e( 'Last reviewed', 'techdella' ); ?></dt>
						<dd><time datetime="<?php echo esc_attr( $td_date ); ?>"><?php echo esc_html( (string) wp_date( 'F j, Y', (int) strtotime( $td_date . ' 12:00:00' ) ) ); ?></time></dd>
					</div>
				<?php endif; ?>
			</dl>
		<?php endif; ?>
	</div>
</aside>
