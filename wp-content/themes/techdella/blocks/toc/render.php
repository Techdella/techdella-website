<?php
/**
 * Table of contents (server-side from the post's headings).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';
require_once __DIR__ . '/lib.php';

$td_depth = 2 === (int) ( $attributes['depth'] ?? 3 ) ? 2 : 3;
$td_id    = (int) ( $attributes['postId'] ?? 0 );
$td_id    = $td_id ? $td_id : ( is_singular() ? (int) get_queried_object_id() : (int) get_the_ID() );
$td_items = $td_id ? td_toc_items( $td_id, $td_depth ) : [];
if ( ! $td_items && td_a3_is_preview() ) {
	foreach ( (array) ( $attributes['items'] ?? [] ) as $td_it ) {
		if ( is_array( $td_it ) && ! empty( $td_it['text'] ) && ! empty( $td_it['id'] ) ) {
			$td_items[] = [
				'level' => 3 === (int) ( $td_it['level'] ?? 2 ) ? 3 : 2,
				'text'  => (string) $td_it['text'],
				'id'    => sanitize_title( (string) $td_it['id'] ),
			];
		}
	}
}
if ( count( $td_items ) < max( 1, (int) ( $attributes['minItems'] ?? 2 ) ) ) {
	echo td_a3_notice( __( 'Table of contents: the post needs at least two H2/H3 headings.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_title = (string) ( $attributes['title'] ?? '' );
$td_uid   = wp_unique_id( 'td-toc-' );

// Nest H3s under the preceding H2.
$td_tree = [];
foreach ( $td_items as $td_it ) {
	if ( 3 === $td_it['level'] && $td_tree ) {
		$td_tree[ count( $td_tree ) - 1 ]['children'][] = $td_it;
	} else {
		$td_tree[] = $td_it + [ 'children' => [] ];
	}
}
?>
<nav <?php echo td_block_wrapper( 'td-toc', [ 'aria-labelledby' => $td_uid ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-td-toc>
	<details class="td-toc__details" open>
		<summary class="td-toc__summary"><span id="<?php echo esc_attr( $td_uid ); ?>"><?php echo esc_html( '' !== $td_title ? $td_title : __( 'On this page', 'techdella' ) ); ?></span><span class="td-toc__chev" aria-hidden="true"><?php echo td_icon( 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></summary>
		<ol class="td-toc__list" role="list">
			<?php foreach ( $td_tree as $td_node ) : ?>
				<li>
					<a class="td-toc__link" href="<?php echo esc_attr( '#' . $td_node['id'] ); ?>"><?php echo esc_html( $td_node['text'] ); ?></a>
					<?php if ( $td_node['children'] ) : ?>
						<ol class="td-toc__sub" role="list">
							<?php foreach ( $td_node['children'] as $td_child ) : ?>
								<li><a class="td-toc__link td-toc__link--sub" href="<?php echo esc_attr( '#' . $td_child['id'] ); ?>"><?php echo esc_html( $td_child['text'] ); ?></a></li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</details>
</nav>
