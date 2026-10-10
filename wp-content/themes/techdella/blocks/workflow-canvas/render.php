<?php
/**
 * Workflow canvas (§2.3 Automation). HTML nodes in an ordered list, SVG connector
 * lines between them; CSS lights the nodes in sequence (view-free, no JS).
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';

$td_steps = array_values(
	array_filter(
		(array) ( $attributes['steps'] ?? [] ),
		static fn( $s ) => is_array( $s ) && ( ! empty( $s['title'] ) || ! empty( $s['branches'] ) )
	)
);
if ( ! $td_steps ) {
	echo td_a3_notice( __( 'Workflow canvas: add steps.', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$td_caption = trim( (string) ( $attributes['caption'] ?? '' ) );
$td_caption = '' !== $td_caption ? $td_caption : __( 'Example workflow', 'techdella' );
$td_bubble  = is_array( $attributes['bubble'] ?? null ) ? $attributes['bubble'] : [];
$td_section = ! empty( $attributes['title'] );
$td_svg     = '<svg class="td-workflow__line" viewBox="0 0 2 24" width="2" height="24" preserveAspectRatio="none" aria-hidden="true" focusable="false"><line x1="1" y1="0" x2="1" y2="24" /></svg>';
$td_icons   = [
	'trigger' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M13 2L4 14h7l-1 8 9-12h-7z"/></svg>',
	'ai'      => '<span aria-hidden="true">AI</span>',
	'crm'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true" focusable="false"><path d="M5 12l5 5L20 7"/></svg>',
	'step'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/></svg>',
];
$td_classes = 'td-workflow' . ( ! empty( $attributes['animate'] ) || ! isset( $attributes['animate'] ) ? ' td-workflow--animate' : '' ) . ( $td_bubble && ! empty( $td_bubble['text'] ) ? ' td-workflow--bubble' : '' );

ob_start();
?>
<figure class="<?php echo esc_attr( $td_classes ); ?>" style="<?php echo esc_attr( '--td-wf-n:' . count( $td_steps ) ); ?>">
	<figcaption class="td-workflow__caption"><?php echo esc_html( $td_caption ); ?></figcaption>
	<ol class="td-workflow__steps">
		<?php foreach ( $td_steps as $td_i => $td_s ) : ?>
			<?php
			$td_kind = in_array( $td_s['kind'] ?? '', [ 'trigger', 'ai', 'crm', 'branch' ], true ) ? (string) $td_s['kind'] : 'step';
			?>
			<li class="td-workflow__step td-workflow__step--<?php echo esc_attr( $td_kind ); ?>" style="<?php echo esc_attr( '--td-wf-i:' . $td_i ); ?>">
				<?php
				if ( $td_i > 0 ) {
					echo $td_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				}
				?>
				<?php if ( 'branch' === $td_kind ) : ?>
					<ul class="td-workflow__branches">
						<?php foreach ( array_filter( (array) ( $td_s['branches'] ?? [] ), 'is_array' ) as $td_b ) : ?>
							<li class="td-workflow__node td-workflow__node--branch<?php echo ! empty( $td_b['hot'] ) ? ' td-workflow__node--hot' : ''; ?>">
								<span class="td-workflow__label"><?php echo esc_html( (string) ( $td_b['label'] ?? '' ) ); ?></span>
								<strong><?php echo td_a3_text( $td_b['title'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<div class="td-workflow__node">
						<span class="td-workflow__icon"><?php echo $td_icons[ $td_kind ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
						<span class="td-workflow__text">
							<?php if ( ! empty( $td_s['label'] ) ) : ?>
								<span class="td-workflow__label"><?php echo esc_html( (string) $td_s['label'] ); ?><span class="td-sr">:</span></span>
							<?php endif; ?>
							<strong><?php echo td_a3_text( $td_s['title'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
						</span>
					</div>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php if ( ! empty( $td_bubble['text'] ) ) : ?>
		<p class="td-workflow__bubble">
			<?php if ( ! empty( $td_bubble['meta'] ) ) : ?>
				<span class="td-workflow__bubble-meta"><?php echo esc_html( (string) $td_bubble['meta'] ); ?></span>
			<?php endif; ?>
			<span class="td-workflow__bubble-text"><?php echo td_a3_text( $td_bubble['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</p>
	<?php endif; ?>
</figure>
<?php
$td_canvas = (string) ob_get_clean();

if ( $td_section ) :
	?>
	<section <?php echo td_block_wrapper( 'td-workflow-block td-section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="td-container">
			<?php
			echo td_section_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				[
					'label' => (string) ( $attributes['label'] ?? '' ),
					'title' => (string) $attributes['title'],
					'intro' => (string) ( $attributes['intro'] ?? '' ),
				]
			);
			echo $td_canvas; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			?>
		</div>
	</section>
<?php else : ?>
	<div <?php echo td_block_wrapper( 'td-workflow-block' . ( ! empty( $attributes['contained'] ) ? ' td-container' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php echo $td_canvas; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
	</div>
	<?php
endif;
