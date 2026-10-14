<?php
/**
 * Thank-you page after a form submission (§11.2). Always noindex (seo/forms
 * modules); the content filter adds the lead-magnet download box.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<section class="td-section">
		<div class="td-container td-container--narrow td-stack" style="--td-stack-gap:24px">
			<?php echo td_chip( __( 'Thank you', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<h1><?php echo td_accent( get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
			<div class="td-prose"><?php the_content(); ?></div>
			<div class="td-cluster">
				<?php
				$td_next = td_field( 'next_step_cta' );
				if ( is_array( $td_next ) && ! empty( $td_next['url'] ) ) {
					echo td_button( (string) ( $td_next['label'] ?? __( 'Next step', 'techdella' ) ), (string) $td_next['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				echo td_button( __( 'Back to the homepage', 'techdella' ), home_url( '/' ), 'secondary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		</div>
	</section>
	<?php
endwhile;
get_footer();
