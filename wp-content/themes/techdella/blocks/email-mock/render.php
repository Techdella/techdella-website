<?php
/**
 * Email mock: designs | collage | sequence. Always labelled as an example.
 *
 * @var array<string,mixed> $attributes
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/case-card/lib.php';

$td_variant = in_array( $attributes['variant'] ?? '', [ 'collage', 'sequence' ], true ) ? (string) $attributes['variant'] : 'designs';
$td_emails  = array_values( array_filter( (array) ( $attributes['emails'] ?? [] ), static fn( $e ) => is_array( $e ) && ! empty( $e['subject'] ) ) );
$td_seq     = array_values( array_filter( (array) ( $attributes['sequence'] ?? [] ), static fn( $s ) => is_array( $s ) && ! empty( $s['title'] ) ) );
$td_caption = trim( (string) ( $attributes['caption'] ?? '' ) );
$td_caption = '' !== $td_caption ? $td_caption : ( 'sequence' === $td_variant ? __( 'Example sequence · example data', 'techdella' ) : __( 'Example emails', 'techdella' ) );

if ( ( 'sequence' === $td_variant && ! $td_seq ) || ( 'sequence' !== $td_variant && ! $td_emails ) ) {
	echo td_a3_notice( __( 'Email mock: add emails (or sequence steps).', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

/**
 * One email card.
 *
 * @param array<string,mixed> $e    Email.
 * @param bool                $mini Compact (collage) version.
 */
$td_email = static function ( array $e, bool $mini = false ): string {
	$type = in_array( $e['type'] ?? '', [ 'nurture', 'invite' ], true ) ? (string) $e['type'] : 'newsletter';
	$out  = '<article class="td-email td-email--' . esc_attr( $type ) . ( $mini ? ' td-email--mini' : '' ) . '">';
	if ( 'newsletter' === $type ) {
		if ( $mini && ! empty( $e['from'] ) ) {
			$out .= '<p class="td-email__bar"><span>' . esc_html( sprintf( /* translators: %s: sender name */ __( 'From: %s', 'techdella' ), (string) $e['from'] ) ) . '</span><span>' . esc_html__( 'Inbox', 'techdella' ) . '</span></p>';
		}
		$img  = ! empty( $e['image'] ) ? td_image(
			$e['image'],
			[
				'alt'   => '',
				'sizes' => '400px',
			]
		) : '';
		$out .= '<div class="td-email__header">' . ( str_starts_with( $img, '<!--' ) ? '' : $img ) . '<span class="td-email__arch" aria-hidden="true"></span>';
		if ( ! empty( $e['tag'] ) ) {
			$out .= '<span class="td-email__tag td-email__tag--overlay">' . esc_html( (string) $e['tag'] ) . '</span>';
		}
		$out .= '</div><div class="td-email__body">';
	} else {
		$out .= '<div class="td-email__body">';
		if ( ! empty( $e['tag'] ) ) {
			$out .= '<span class="td-email__tag">' . esc_html( (string) $e['tag'] ) . '</span>';
		}
	}
	$out .= '<h3 class="td-email__subject">' . td_a3_text( $e['subject'] ) . '</h3>';
	if ( ! empty( $e['preview'] ) ) {
		$out .= '<p class="td-email__preview">' . td_a3_text( $e['preview'] ) . '</p>';
	} elseif ( 'newsletter' === $type ) {
		$out .= '<span class="td-email__lines" aria-hidden="true"><i></i><i></i>' . ( $mini ? '' : '<i></i>' ) . '</span>';
	}
	$stats = array_filter( (array) ( $e['stats'] ?? [] ), static fn( $s ) => is_array( $s ) && ! empty( $s['num'] ) );
	if ( $stats ) {
		$out .= '<dl class="td-email__stats">';
		foreach ( $stats as $s ) {
			$out .= '<div><dt>' . td_a3_text( $s['label'] ?? '' ) . '</dt><dd>' . td_a3_text( $s['num'] ) . '</dd></div>';
		}
		$out .= '</dl>';
	}
	if ( 'invite' === $type && ( ! empty( $e['detail'] ) || ! empty( $e['month'] ) ) ) {
		$out .= '<p class="td-email__event">';
		if ( ! empty( $e['month'] ) ) {
			$out .= '<span class="td-email__date"><span>' . esc_html( (string) $e['month'] ) . '</span><strong>' . td_a3_text( $e['day'] ?? '' ) . '</strong></span>';
		}
		$out .= '<span>' . td_a3_text( $e['detail'] ?? '' ) . '</span></p>';
	}
	if ( ! empty( $e['cta'] ) ) {
		$out .= '<span class="td-email__cta" aria-hidden="true">' . esc_html( (string) $e['cta'] ) . ( 'invite' === $type ? ' <span class="td-email__cta-dot">●</span>' : '' ) . '</span>';
	}
	return $out . '</div></article>';
};

/**
 * Sequence list.
 *
 * @param array<int,array<string,mixed>> $steps Steps.
 * @param string                         $title Title.
 * @param bool                           $dark  Dark compact card (collage).
 */
$td_sequence = static function ( array $steps, string $title, bool $dark ): string {
	$out = '<div class="td-email-seq' . ( $dark ? ' td-email-seq--dark' : '' ) . '">';
	if ( '' !== $title ) {
		$out .= '<p class="td-email-seq__title">' . esc_html( $title ) . '</p>';
	}
	$out  .= '<ol class="td-email-seq__list">';
	$last  = count( $steps ) - 1;
	foreach ( $steps as $i => $s ) {
		$day    = (string) ( $s['day'] ?? '' );
		$status = (string) ( $s['status'] ?? '' );
		$out   .= '<li class="td-email-seq__step' . ( $i === $last ? ' td-email-seq__step--last' : '' ) . ( 0 === $i ? ' td-email-seq__step--first' : '' ) . '">';
		$out   .= '<span class="td-email-seq__day"><span class="td-sr">' . esc_html__( 'Day', 'techdella' ) . ' </span>' . esc_html( $day ) . '</span>';
		$out   .= '<span class="td-email-seq__name">' . td_a3_text( $s['title'] ) . '</span>';
		if ( ! $dark && ( '' !== $status || ! empty( $s['metric'] ) ) ) {
			$out .= '<span class="td-email-seq__status">' . esc_html( $status );
			if ( ! empty( $s['metric'] ) ) {
				$out .= '<span class="td-email-seq__metric">' . esc_html( (string) $s['metric'] ) . ' <abbr title="' . esc_attr__( 'Example data, not client results', 'techdella' ) . '">' . esc_html__( '(example)', 'techdella' ) . '</abbr></span>';
			}
			$out .= '</span>';
		}
		$out .= '</li>';
	}
	return $out . '</ol></div>';
};

$td_section = ! empty( $attributes['title'] );
ob_start();
if ( 'designs' === $td_variant ) :
	?>
	<div class="td-email-mock td-email-mock--designs">
		<?php if ( ! $td_section ) : ?>
			<p class="td-email-mock__caption"><?php echo esc_html( $td_caption ); ?></p>
		<?php endif; ?>
		<ul class="td-email-mock__grid" role="list">
			<?php foreach ( $td_emails as $td_e ) : ?>
				<li><?php echo $td_email( $td_e ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
elseif ( 'collage' === $td_variant ) :
	$td_img = ! empty( $attributes['image'] ) ? td_image(
		$attributes['image'],
		[
			'alt'   => (string) ( $attributes['imageAlt'] ?? '' ),
			'sizes' => '220px',
		]
	) : '';
	?>
	<figure class="td-email-mock td-email-mock--collage">
		<figcaption class="td-email-mock__caption"><?php echo esc_html( $td_caption ); ?></figcaption>
		<div class="td-email-mock__main"><?php echo $td_email( $td_emails[0], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php if ( $td_seq ) : ?>
			<div class="td-email-mock__seq"><?php echo $td_sequence( $td_seq, (string) ( $attributes['sequenceTitle'] ?? '' ), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>
		<?php if ( '' !== $td_img && ! str_starts_with( $td_img, '<!--' ) ) : ?>
			<div class="td-email-mock__photo">
				<?php echo $td_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( ! empty( $attributes['imageCaption'] ) ) : ?>
					<span class="td-email-mock__photo-cap"><?php echo esc_html( (string) $attributes['imageCaption'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $attributes['reply'] ) ) : ?>
			<p class="td-email-mock__reply"><span class="td-dot" aria-hidden="true"></span><span><strong><?php esc_html_e( 'Reply:', 'techdella' ); ?></strong> <?php echo td_a3_text( $attributes['reply'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></p>
		<?php endif; ?>
	</figure>
	<?php
else :
	?>
	<figure class="td-email-mock td-email-mock--sequence">
		<?php echo $td_sequence( $td_seq, (string) ( $attributes['sequenceTitle'] ?? '' ), false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<figcaption class="td-email-mock__caption"><?php echo esc_html( $td_caption ); ?></figcaption>
	</figure>
	<?php
endif;
$td_mock = (string) ob_get_clean();

if ( $td_section ) :
	$td_surface = 'paper' === ( $attributes['surface'] ?? '' ) ? '' : ' td-section--canvas';
	?>
	<section <?php echo td_block_wrapper( 'td-email-mock-block td-section' . $td_surface ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="td-container">
			<div class="td-section-head">
				<div class="td-section-head__title">
					<?php echo td_chip( (string) ( $attributes['label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="td-h2 td-email-mock__title"><?php echo td_accent( (string) $attributes['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				</div>
				<p class="td-email-mock__caption td-email-mock__caption--head"><?php echo esc_html( '' !== trim( (string) ( $attributes['intro'] ?? '' ) ) ? (string) $attributes['intro'] : $td_caption ); ?></p>
			</div>
			<?php echo $td_mock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
		</div>
	</section>
<?php else : ?>
	<div <?php echo td_block_wrapper( 'td-email-mock-block' . ( ! empty( $attributes['contained'] ) ? ' td-container' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php echo $td_mock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
	</div>
	<?php
endif;
