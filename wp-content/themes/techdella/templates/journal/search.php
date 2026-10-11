<?php
/**
 * Journal search results (noindex via the SEO module): search form + post cards.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="td-container td-section" style="padding-bottom:40px">
	<?php echo td_chip( __( 'Search', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<h1>
		<?php
		/* translators: %s: search terms */
		printf( esc_html__( 'Results for “%s”', 'techdella' ), esc_html( get_search_query() ) );
		?>
	</h1>
	<?php get_search_form(); ?>
</header>
<?php
$td_grid_label = __( 'Search results', 'techdella' );
require __DIR__ . '/loop-cards.php';
get_footer();
