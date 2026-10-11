<?php
/**
 * Journal author archive: the team member's profile (td_team: photo, role, bio,
 * LinkedIn) and their posts. Indexing is decided by the SEO module (team members
 * with at least 3 posts are indexable, §8.3).
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'blocks/post-card/lib.php' );

get_header();

$td_author = get_queried_object();
$td_uid    = $td_author instanceof WP_User ? (int) $td_author->ID : 0;
$td_person = td_a3_person( $td_uid );
$td_name   = $td_person ? $td_person['name'] : ( $td_author instanceof WP_User ? $td_author->display_name : '' );
$td_photo  = $td_person && $td_person['photo'] ? td_image(
	$td_person['photo'],
	[
		'alt'   => '',
		'sizes' => '160px',
		'width' => 160,
	]
) : '';
$td_photo  = str_starts_with( $td_photo, '<!--' ) ? '' : $td_photo;
?>
<header class="td-container td-section td-author-head">
	<?php if ( '' !== $td_photo ) : ?>
		<figure class="td-author-head__photo"><?php echo $td_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
	<?php endif; ?>
	<div>
		<?php echo td_chip( __( 'Author', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h1 class="td-h1"><?php echo esc_html( $td_name ); ?></h1>
		<?php if ( $td_person && '' !== $td_person['role'] ) : ?>
			<p class="td-author-head__role"><?php echo esc_html( $td_person['role'] ); ?></p>
		<?php endif; ?>
		<?php if ( $td_person && '' !== trim( $td_person['bio'] ) ) : ?>
			<p class="td-author-head__bio"><?php echo td_mark_placeholders( esc_html( $td_person['bio'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<?php if ( $td_person && '' !== $td_person['linkedin'] ) : ?>
			<p class="td-author-head__links"><a class="td-link" href="<?php echo esc_url( $td_person['linkedin'] ); ?>" rel="me noopener"><?php esc_html_e( 'LinkedIn', 'techdella' ); ?></a></p>
		<?php endif; ?>
	</div>
</header>
<?php
/* translators: %s: author name */
$td_grid_label = sprintf( __( 'Posts by %s', 'techdella' ), $td_name );
require __DIR__ . '/loop-cards.php';
get_footer();
