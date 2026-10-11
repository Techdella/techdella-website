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
<style>
	.td-author-head{display:grid;gap:24px 40px;align-items:center;padding-bottom:48px}
	@media (min-width:760px){.td-author-head{grid-template-columns:160px minmax(0,1fr)}}
	.td-author-head__photo{width:160px;aspect-ratio:4/5;margin:0;border-radius:var(--td-arch);overflow:hidden;background:var(--td-sand)}
	.td-author-head__photo img{display:block;width:100%;height:100%;object-fit:cover}
	.td-author-head h1{margin:8px 0 6px}
	.td-author-head__role{margin:0 0 12px;color:var(--td-muted);font-weight:500}
	.td-author-head__bio{margin:0;max-width:640px}
	.td-author-head__links{margin:16px 0 0}
</style>
<header class="td-container td-section td-author-head">
	<?php if ( '' !== $td_photo ) : ?>
		<figure class="td-author-head__photo"><?php echo $td_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
	<?php endif; ?>
	<div>
		<?php echo td_chip( __( 'Author', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h1><?php echo esc_html( $td_name ); ?></h1>
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
