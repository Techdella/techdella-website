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
		'sizes' => '280px',
		'width' => 280,
	]
) : '';
$td_photo  = str_starts_with( $td_photo, '<!--' ) ? '' : $td_photo;
$td_count  = $td_uid ? (int) count_user_posts( $td_uid, 'post', true ) : 0;
$td_first  = (string) strtok( $td_name, ' ' );
// Topics they write about: the categories of their posts, most used first.
$td_topics = [];
if ( $td_uid ) {
	foreach ( get_posts(
		[
			'author'         => $td_uid,
			'posts_per_page' => 60,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		]
	) as $td_pid ) {
		foreach ( get_the_category( $td_pid ) as $td_cat ) {
			if ( 'uncategorized' !== $td_cat->slug ) {
				$td_topics[ $td_cat->term_id ] = ( $td_topics[ $td_cat->term_id ] ?? 0 ) + 1;
			}
		}
	}
	arsort( $td_topics );
	$td_topics = array_slice( array_keys( $td_topics ), 0, 4 );
}
$td_expertise = [];
if ( $td_person && post_type_exists( 'td_team' ) ) {
	foreach ( (array) td_field(
		'expertise',
		(int) ( get_posts(
			[
				'post_type'      => 'td_team',
				'post_status'    => [ 'publish', 'draft', 'private' ],
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'wp_user', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'         => (string) $td_uid, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		)[0] ?? 0 )
	) as $td_e ) {
		$td_e = is_array( $td_e ) ? (string) reset( $td_e ) : (string) $td_e;
		if ( '' !== trim( $td_e ) ) {
			$td_expertise[] = trim( $td_e );
		}
	}
}
$td_initials = mb_strtoupper( implode( '', array_map( static fn( $w ) => mb_substr( $w, 0, 1 ), array_slice( preg_split( '/\s+/', trim( $td_name ) ), 0, 2 ) ) ) );
echo td_block_css( 'post-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<header class="td-author-head">
	<div class="td-container td-author-head__inner">
		<figure class="td-author-head__photo">
			<?php echo '' !== $td_photo ? $td_photo : '<span class="td-author-head__initials" aria-hidden="true">' . esc_html( $td_initials ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</figure>
		<div class="td-author-head__copy">
			<?php echo td_chip( __( 'Author', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<h1 class="td-author-head__name"><?php echo esc_html( $td_name ); ?></h1>
			<?php if ( $td_person && '' !== $td_person['role'] ) : ?>
				<p class="td-author-head__role"><?php echo esc_html( $td_person['role'] ); ?></p>
			<?php endif; ?>
			<?php if ( $td_person && '' !== trim( $td_person['bio'] ) ) : ?>
				<p class="td-author-head__bio"><?php echo td_mark_placeholders( esc_html( $td_person['bio'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
			<?php if ( $td_expertise ) : ?>
				<ul class="td-author-head__tags" role="list" aria-label="<?php esc_attr_e( 'Expertise', 'techdella' ); ?>">
					<?php foreach ( $td_expertise as $td_e ) : ?>
						<li><?php echo esc_html( $td_e ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<dl class="td-author-head__stats">
				<div><dt><?php esc_html_e( 'Articles', 'techdella' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $td_count ) ); ?></dd></div>
				<?php if ( $td_topics ) : ?>
					<div><dt><?php esc_html_e( 'Writes about', 'techdella' ); ?></dt><dd>
						<?php
						$td_links = array_map(
							static fn( $tid ) => '<a href="' . esc_url( (string) get_category_link( (int) $tid ) ) . '">' . esc_html( get_cat_name( (int) $tid ) ) . '</a>',
							$td_topics
						);
						echo implode( ', ', $td_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
						?>
					</dd></div>
				<?php endif; ?>
				<?php if ( $td_person && '' !== $td_person['linkedin'] ) : ?>
					<div><dt><?php esc_html_e( 'Elsewhere', 'techdella' ); ?></dt><dd><a href="<?php echo esc_url( $td_person['linkedin'] ); ?>" rel="me noopener"><?php esc_html_e( 'LinkedIn', 'techdella' ); ?></a></dd></div>
				<?php endif; ?>
			</dl>
		</div>
	</div>
</header>
<div class="td-container td-author-list-head">
	<h2 class="td-author-list-head__title">
		<?php
		/* translators: %s: author's first name */
		echo td_accent( sprintf( __( 'Articles by *%s*', 'techdella' ), $td_first ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</h2>
</div>
<?php
/* translators: %s: author name */
$td_grid_label = sprintf( __( 'Posts by %s', 'techdella' ), $td_name );
$td_card_level = 3;
require __DIR__ . '/loop-cards.php';
get_footer();
