<?php
/**
 * Section heading block: wraps td_section_head().
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

?>
<div <?php echo td_block_wrapper( 'td-section-head-block td-container' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php
	echo td_section_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		[
			'label' => (string) ( $attributes['label'] ?? '' ),
			'title' => (string) ( $attributes['title'] ?? '' ),
			'intro' => (string) ( $attributes['intro'] ?? '' ),
			'align' => (string) ( $attributes['align'] ?? 'split' ),
			'level' => (int) ( $attributes['level'] ?? 2 ),
		]
	);
	?>
</div>
