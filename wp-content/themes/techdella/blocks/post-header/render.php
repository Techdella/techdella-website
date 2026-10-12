<?php
/**
 * Post header (H1, crumb, dek, named author).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/post-card/lib.php';

$td_id     = (int) ( $attributes['postId'] ?? 0 );
$td_id     = $td_id ? $td_id : ( is_singular( [ 'post', 'business' ] ) ? (int) get_queried_object_id() : 0 );
$td_post   = $td_id ? get_post( $td_id ) : null;
$td_title  = '';
$td_dek    = (string) ( $attributes['dek'] ?? '' );
$td_cat    = null;
$td_person = null;
$td_min    = 0;
$td_cat_n  = '';
$td_cat_u  = '';
if ( $td_post && in_array( $td_post->post_type, [ 'post', 'business' ], true ) ) {
	$td_title  = '' !== trim( (string) ( $attributes['title'] ?? '' ) ) ? (string) $attributes['title'] : get_the_title( $td_post );
	$td_dek    = '' !== trim( $td_dek ) ? $td_dek : ( has_excerpt( $td_post ) ? get_the_excerpt( $td_post ) : '' );
	$td_cat    = td_a3_primary_category( $td_id );
	$td_cat_n  = $td_cat ? $td_cat->name : '';
	$td_cat_u  = $td_cat ? (string) get_category_link( $td_cat ) : '';
	$td_person = td_a3_person( (int) $td_post->post_author );
	$td_min    = td_a3_reading_time( $td_id );
} elseif ( td_a3_is_preview() && ! empty( $attributes['item']['title'] ) ) {
	$td_item   = (array) $attributes['item'];
	$td_title  = (string) $td_item['title'];
	$td_dek    = (string) ( $td_item['dek'] ?? '' );
	$td_cat_n  = (string) ( $td_item['category'] ?? '' );
	$td_min    = (int) ( $td_item['minutes'] ?? 0 );
	$td_person = [
		'name'  => (string) ( $td_item['author'] ?? '' ),
		'role'  => (string) ( $td_item['role'] ?? '' ),
		'photo' => $td_item['image'] ?? '',
		'url'   => '',
	];
}
if ( '' === $td_title ) {
	echo td_a3_notice( __( 'Post header: shows the current post on a single post.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_posts_page = (int) get_option( 'page_for_posts' );
$td_journal    = $td_posts_page ? (string) get_permalink( $td_posts_page ) : home_url( '/' );
$td_crumb      = td_posts_hub_label();
if ( $td_post && 'business' === $td_post->post_type ) { // Business library lives on Marketing under /business/.
	$td_journal = (string) get_post_type_archive_link( 'business' );
	$td_crumb   = __( 'Business guides', 'techdella' );
}
$td_avatar = $td_person && ! empty( $td_person['photo'] ) ? td_image(
	$td_person['photo'],
	[
		'alt'       => '',
		'sizes'     => '44px',
		'img_class' => 'td-avatar',
		'width'     => 44,
	]
) : '';
$td_avatar = str_starts_with( $td_avatar, '<!--' ) ? '' : $td_avatar;
$td_sub    = array_filter(
	[
		$td_person ? (string) ( $td_person['role'] ?? '' ) : '',
		/* translators: %d: minutes */
		$td_min ? sprintf( _n( '%d min read', '%d min read', $td_min, 'techdella' ), $td_min ) : '',
	]
);
?>
<header <?php echo td_block_wrapper( 'td-post-header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="td-container td-post-header__inner">
		<nav class="td-post-header__crumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'techdella' ); ?>">
			<a href="<?php echo esc_url( $td_journal ); ?>"><?php echo esc_html( $td_crumb ); ?></a>
			<?php if ( '' !== $td_cat_n ) : ?>
				<span aria-hidden="true">/</span>
				<?php if ( '' !== $td_cat_u ) : ?>
					<a class="td-chip" href="<?php echo esc_url( $td_cat_u ); ?>"><?php echo esc_html( $td_cat_n ); ?></a>
				<?php else : ?>
					<span class="td-chip"><?php echo esc_html( $td_cat_n ); ?></span>
				<?php endif; ?>
			<?php endif; ?>
		</nav>
		<h1 class="td-post-header__title"><?php echo td_accent( $td_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
		<?php if ( $td_post && function_exists( 'td_is_sponsored' ) && td_is_sponsored( $td_id ) ) : ?>
			<?php $td_sponsor = trim( (string) td_field( 'sponsor_name', $td_id ) ); ?>
			<p class="td-post-header__sponsored"><span class="td-post-header__sponsored-tag"><?php esc_html_e( 'Sponsored', 'techdella' ); ?></span>
				<?php
				echo esc_html(
					'' !== $td_sponsor
						/* translators: %s: sponsor */
						? sprintf( __( 'Paid placement by %s. Our editors reviewed it, and its links are marked as sponsored.', 'techdella' ), $td_sponsor )
						: __( 'Paid placement. Our editors reviewed it, and its links are marked as sponsored.', 'techdella' )
				);
				?>
			</p>
		<?php endif; ?>
		<?php if ( '' !== trim( $td_dek ) ) : ?>
			<p class="td-post-header__dek"><?php echo td_a3_text( $td_dek ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<?php
		$td_byline = $td_post ? td_byline_html( $td_id ) : '';
		if ( '' !== $td_byline ) {
			echo $td_byline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in td_byline_html().
		} elseif ( $td_person && '' !== (string) $td_person['name'] ) {
			// Styleguide preview item.
			echo '<div class="td-byline"><div class="td-byline__text"><p class="td-byline__who"><span class="td-byline__by">' . esc_html__( 'By', 'techdella' ) . '</span> <strong>' . esc_html( (string) $td_person['name'] ) . '</strong>' . ( $td_sub ? '<span class="td-byline__role">' . esc_html( implode( ' · ', $td_sub ) ) . '</span>' : '' ) . '</p></div></div>';
		}
		?>
	</div>
</header>
