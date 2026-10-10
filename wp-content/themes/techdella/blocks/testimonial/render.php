<?php
/**
 * Testimonial block. Review schema only when the quote is about this page (§2.3).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

$td_a     = $attributes;
$td_quote = trim( (string) ( $td_a['quote'] ?? '' ) );
if ( '' === $td_quote ) {
	return;
}
$td_name    = (string) ( $td_a['name'] ?? '' );
$td_role    = trim( (string) ( $td_a['role'] ?? '' ) );
$td_company = trim( (string) ( $td_a['company'] ?? '' ) );
$td_byline  = implode( ', ', array_filter( [ $td_role, $td_company ] ) );
$td_variant = 'dark' === ( $td_a['variant'] ?? '' ) ? 'dark' : 'arch';

if ( ! empty( $td_a['aboutThisPage'] ) && function_exists( 'td_schema_add_node' ) && is_singular() ) {
	$td_permalink = (string) get_permalink();
	$td_author    = [
		'@type' => 'Person',
		'name'  => $td_name,
	];
	if ( '' !== $td_role ) {
		$td_author['jobTitle'] = $td_role;
	}
	if ( '' !== $td_company ) {
		$td_author['worksFor'] = [
			'@type' => 'Organization',
			'name'  => $td_company,
		];
	}
	td_schema_add_node(
		[
			'@type'        => 'Review',
			'@id'          => $td_permalink . '#review-' . substr( md5( $td_quote ), 0, 8 ),
			'itemReviewed' => [ '@id' => $td_permalink . '#service' ],
			'author'       => $td_author,
			'reviewBody'   => $td_quote,
		]
	);
}
?>
<figure <?php echo td_block_wrapper( 'td-testimonial td-testimonial--' . $td_variant ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( 'dark' === $td_variant ) : ?>
		<?php echo td_icon( 'quote', 'td-testimonial__mark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php elseif ( ! empty( $td_a['logo'] ) ) : ?>
		<?php
		$td_logo_m = is_string( $td_a['logo'] ) ? ( td_image_manifest()[ $td_a['logo'] ] ?? null ) : null;
		$td_logo_w = $td_logo_m ? (int) round( $td_logo_m['width'] * 28 / max( 1, $td_logo_m['height'] ) ) : 0;
		echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$td_a['logo'],
			[
				'alt'       => $td_company,
				'sizes'     => ( $td_logo_w ? $td_logo_w : 120 ) . 'px',
				'width'     => $td_logo_w,
				'class'     => 'td-testimonial__logo',
				'img_class' => 'td-testimonial__logo-img',
				'style'     => 'height:28px;width:' . ( $td_logo_w ? $td_logo_w . 'px' : 'auto' ),
			]
		);
		?>
	<?php endif; ?>
	<blockquote class="td-testimonial__quote"><p>“<?php echo esc_html( $td_quote ); ?>”</p></blockquote>
	<figcaption class="td-testimonial__person">
		<?php
		if ( ! empty( $td_a['image'] ) ) {
			echo td_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$td_a['image'],
				[
					'alt'       => '',
					'sizes'     => '46px',
					'width'     => 46,
					'class'     => 'td-testimonial__photo',
					'img_class' => 'td-testimonial__photo-img',
				]
			);
		}
		?>
		<span class="td-testimonial__who"><strong><?php echo esc_html( $td_name ); ?></strong><?php echo '' !== $td_byline ? '<span>' . esc_html( $td_byline ) . '</span>' : ''; ?></span>
	</figcaption>
</figure>
