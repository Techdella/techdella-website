<?php
/**
 * Styleguide (/styleguide/, noindex): brand rules from System.dc.html, then
 * every registered techdella/* block rendered from its block.json example.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'td_noindex', '__return_true' );
if ( ! function_exists( 'td_breadcrumbs' ) ) {
	// The SEO module (which honours td_noindex) is not loaded: print robots ourselves.
	remove_action( 'wp_head', 'wp_robots', 1 );
	add_action(
		'wp_head',
		static function () {
			echo '<meta name="robots" content="noindex, nofollow">' . "\n";
		},
		1
	);
}

add_action(
	'wp_head',
	static function () {
		// Page-only styles: kept out of the shared bundle.
		?>
<style id="td-styleguide-css">
.td-sg{background:var(--td-canvas)}
.td-sg__wrap{padding-block:64px 96px;display:flex;flex-direction:column;gap:64px}
.td-sg__header{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-end;gap:24px;border-bottom:1px solid var(--td-line);padding-bottom:32px}
.td-sg__header h1{font-size:clamp(44px,5vw,64px);line-height:1;letter-spacing:-.05em;margin-top:14px}
.td-sg__header p{max-width:440px;font-size:16px;color:var(--td-body)}
.td-sg__mono{font-family:var(--td-font-mono);font-size:12px;color:var(--td-muted);letter-spacing:.02em}
.td-sg__h2{font-size:32px;letter-spacing:-.04em;margin-bottom:22px}
.td-sg__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(300px,100%),1fr));gap:14px}
.td-sg__grid--wide{grid-template-columns:repeat(auto-fit,minmax(min(340px,100%),1fr))}
.td-sg__card{background:var(--td-paper);border:1px solid var(--td-line);border-radius:24px;padding:28px;display:flex;flex-direction:column;gap:12px}
.td-sg__card strong{font-size:22px;letter-spacing:-.03em;line-height:1.25}
.td-sg__card p,.td-sg__note{font-size:15px;color:var(--td-body)}
.td-sg__note{font-size:13.5px}
.td-sg__balance{display:flex;height:64px;border-radius:16px;overflow:hidden;border:1px solid var(--td-line);margin-bottom:22px;font-size:13px}
.td-sg__balance span{display:flex;align-items:center;padding:0 18px;white-space:nowrap;overflow:hidden}
.td-sg__balance span:nth-child(1){flex:60;background:var(--td-paper)}
.td-sg__balance span:nth-child(2){flex:30;background:var(--td-ink);color:var(--td-paper)}
.td-sg__balance span:nth-child(3){flex:8;background:var(--td-ink);color:var(--td-paper);justify-content:center;font-weight:600;padding:0;position:relative}
.td-sg__balance span:nth-child(3)::before{content:"";position:absolute;inset:0;background:var(--td-gradient);opacity:.9}
.td-sg__balance span:nth-child(3) b{position:relative;color:var(--td-ink);background:var(--td-paper);border-radius:var(--td-r-pill);padding:2px 8px;font-size:12px}
.td-sg__swatches{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(170px,100%),1fr));gap:12px;margin:0;padding:0;list-style:none}
.td-sg__swatch{background:var(--td-paper);border:1px solid var(--td-line);border-radius:18px;overflow:hidden}
.td-sg__chip-color{height:90px;border-bottom:1px solid var(--td-line-soft)}
.td-sg__swatch div:last-child{padding:12px 14px;font-size:13px;line-height:1.45}
.td-sg__swatch strong{display:block}
.td-sg__swatch code{font-family:var(--td-font-mono);color:var(--td-muted);font-size:12.5px}
.td-sg__swatch span{color:var(--td-body)}
.td-sg__type{background:var(--td-paper);border:1px solid var(--td-line);border-radius:24px;padding:32px;display:flex;flex-direction:column;gap:22px}
.td-sg__type-row{display:flex;flex-wrap:wrap;gap:8px 32px;align-items:baseline;border-bottom:1px solid var(--td-line-soft);padding-bottom:20px;min-width:0}
.td-sg__type-row:last-child{border-bottom:0;padding-bottom:0}
.td-sg__type-row .td-sg__mono{width:150px;flex:0 0 150px}
.td-sg__type-row>:last-child{min-width:0;flex:1 1 320px}
.td-sg__display{font-weight:600;font-size:clamp(44px,6vw,72px);line-height:1;letter-spacing:-.05em}
.td-sg__h2demo{font-weight:600;font-size:clamp(32px,4vw,48px);line-height:1.05;letter-spacing:-.045em}
.td-sg__h3demo{font-weight:600;font-size:24px;letter-spacing:-.03em}
.td-sg__row{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.td-sg__cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.td-sg__mini{border-radius:18px;padding:14px;height:110px;display:flex;flex-direction:column;justify-content:flex-end}
.td-sg__mini b{font-size:26px;letter-spacing:-.04em;line-height:1.1}
.td-sg__mini span{font-size:12px}
.td-sg__mini--paper{background:var(--td-paper);border:1px solid var(--td-line)}
.td-sg__mini--paper span{color:var(--td-body)}
.td-sg__mini--sand{background:var(--td-sand)}
.td-sg__mini--sand span{color:var(--td-ink-soft)}
.td-sg__mini--ink{background:var(--td-ink);color:var(--td-paper)}
.td-sg__mini--ink span{color:var(--td-on-dark)}
.td-sg__mini--ink i{font-style:normal;color:var(--td-orange-on-dark)}
.td-sg__arches{display:flex;gap:12px;align-items:flex-end}
.td-sg__arches .td-arch{flex:0 0 auto}
.td-sg__arches .td-arch--lg{width:110px;height:140px;border-radius:999px 999px 18px 18px}
.td-sg__arches .td-arch--sm{width:90px;height:115px;border-radius:999px 999px 16px 16px}
.td-sg__arches picture,.td-sg__arches img{width:100%;height:100%;object-fit:cover}
.td-sg__arches svg{color:var(--td-orange)}
.td-sg__do strong{font-size:18px}
.td-sg__blocks{background:var(--td-paper)}
.td-sg__blocks-head{padding-block:64px 24px}
.td-sg__block{border-top:1px dashed var(--td-line)}
.td-sg__block-label{padding-block:14px 0}
.td-sg__block-label .td-sg__mono{display:inline-block;background:var(--td-canvas);border-radius:6px;padding:3px 8px}
.td-sg__block-body{padding-block:24px}
.td-sg__block[data-td-block="techdella/testimonial"] .td-testimonial{max-width:560px}
.td-sg__block-body--inline{max-width:calc(var(--td-max) + var(--td-gutter)*2);margin-inline:auto;padding-inline:var(--td-gutter)}
.td-sg__toc{display:flex;flex-wrap:wrap;gap:6px;margin:16px 0 0;padding:0;list-style:none}
.td-sg__toc a{display:inline-flex;align-items:center;min-height:36px;padding:4px 12px;border-radius:var(--td-r-pill);background:var(--td-canvas);font-family:var(--td-font-mono);font-size:12.5px;text-decoration:none}
@media (max-width:640px){.td-sg__cards{grid-template-columns:1fr 1fr 1fr}.td-sg__type{padding:22px}.td-sg__balance span:nth-child(1){padding:0 10px}}
</style>
		<?php
	},
	20
);

get_header();

$td_swatches = [
	[ 'Ink', '#0D0D0D', '--td-ink', 'Type, primary buttons, dark sections' ],
	[ 'Paper', '#FFFFFF', '--td-paper', 'Main background, cards' ],
	[ 'Canvas', '#F7F5F2', '--td-canvas', 'Alternate sections' ],
	[ 'Sand', '#EFE7DD', '--td-sand', 'Feature panels, from the photo art' ],
	[ 'Signal orange', '#E8470A', '--td-orange', 'Dots, arrows, logo, one highlight' ],
	[ 'Orange tint', '#FDF0E9', '--td-orange-tint', 'Soft highlight behind small text' ],
	[ 'Orange text', '#C23B07', '--td-orange-text', 'Orange that passes contrast for small text' ],
	[ 'Stone', '#57534E', '--td-body', 'Body copy' ],
];

// Every registered TechDella block, in name order (other workers' blocks appear automatically).
$td_blocks = array_filter(
	WP_Block_Type_Registry::get_instance()->get_all_registered(),
	static fn( $type ) => str_starts_with( (string) $type->name, 'techdella/' )
);
ksort( $td_blocks );
// Blocks that render a bare component (no section/container of their own).
$td_inline_blocks = [ 'techdella/button', 'techdella/label-chip', 'techdella/testimonial', 'techdella/tldr', 'techdella/post-cta', 'techdella/author-box', 'techdella/post-card', 'techdella/toc' ];
?>
<div class="td-sg">
	<div class="td-container td-sg__wrap">

		<header class="td-sg__header">
			<div>
				<?php echo td_logo( 'default', 34 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<h1><?php echo td_accent( 'Brand & UI *kit*' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
			</div>
			<p><?php esc_html_e( 'The rules behind techdella.com, the blog and Studios. Neutral-first, black for confidence, orange as a signal, never as wallpaper.', 'techdella' ); ?></p>
		</header>

		<section aria-labelledby="sg-principles">
			<h2 class="td-sr" id="sg-principles"><?php esc_html_e( 'Principles', 'techdella' ); ?></h2>
			<div class="td-sg__grid">
				<div class="td-sg__card"><span class="td-sg__mono">01</span><strong><?php esc_html_e( 'Neutral first', 'techdella' ); ?></strong><p><?php esc_html_e( 'White and warm neutrals carry the page. Black carries type, buttons and dark sections.', 'techdella' ); ?></p></div>
				<div class="td-sg__card"><span class="td-sg__mono">02</span><strong><?php esc_html_e( 'Orange is a signal', 'techdella' ); ?></strong><p><?php esc_html_e( 'Under 10% of any screen: the logo, a dot, an arrow, one highlight. If it’s everywhere, it means nothing.', 'techdella' ); ?></p></div>
				<div class="td-sg__card"><span class="td-sg__mono">03</span><strong><?php esc_html_e( 'The arch is our shape', 'techdella' ); ?></strong><p><?php esc_html_e( 'Taken from the logo. Use it for portraits, image frames and large background marks.', 'techdella' ); ?></p></div>
			</div>
		</section>

		<section aria-labelledby="sg-color">
			<h2 class="td-sg__h2" id="sg-color"><?php esc_html_e( 'Color & balance', 'techdella' ); ?></h2>
			<div class="td-sg__balance" role="img" aria-label="<?php esc_attr_e( '60% white and warm neutrals, 30% ink, at most 10% orange', 'techdella' ); ?>">
				<span><?php esc_html_e( '60% · White & warm neutrals', 'techdella' ); ?></span><span><?php esc_html_e( '30% · Ink', 'techdella' ); ?></span><span><b>≤10%</b></span>
			</div>
			<ul class="td-sg__swatches" role="list">
				<?php foreach ( $td_swatches as $td_sw ) : ?>
					<li class="td-sg__swatch">
						<div class="td-sg__chip-color" style="<?php echo esc_attr( 'background:var(' . $td_sw[2] . ')' ); ?>"></div>
						<div><strong><?php echo esc_html( $td_sw[0] ); ?></strong><code><?php echo esc_html( $td_sw[1] ); ?></code> <code><?php echo esc_html( $td_sw[2] ); ?></code><br><span><?php echo esc_html( $td_sw[3] ); ?></span></div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section aria-labelledby="sg-type">
			<h2 class="td-sg__h2" id="sg-type"><?php esc_html_e( 'Typography', 'techdella' ); ?></h2>
			<div class="td-sg__type">
				<div class="td-sg__type-row"><span class="td-sg__mono">Display · 600 · −5%</span><span class="td-sg__display"><?php echo td_accent( 'Get found *faster*' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></div>
				<div class="td-sg__type-row"><span class="td-sg__mono">H2 · 600 · −4.5%</span><span class="td-sg__h2demo"><?php esc_html_e( 'Start with the audit', 'techdella' ); ?></span></div>
				<div class="td-sg__type-row"><span class="td-sg__mono">H3 · 600 · −3%</span><span class="td-sg__h3demo"><?php esc_html_e( 'Search Visibility Audit', 'techdella' ); ?></span></div>
				<div class="td-sg__type-row"><span class="td-sg__mono">Body · 400 · 17px</span><span class="td-body"><?php esc_html_e( 'We help established B2B companies show up on Google and in AI answers, for the searches that turn into revenue.', 'techdella' ); ?></span></div>
				<div class="td-sg__type-row"><span class="td-sg__mono">Small · 400 · 14px</span><span class="td-small td-muted"><?php esc_html_e( 'Captions, meta lines and helper text.', 'techdella' ); ?></span></div>
				<div class="td-sg__type-row"><span class="td-sg__mono">Accent · serif italic</span><span class="td-body"><?php esc_html_e( 'One word per headline in', 'techdella' ); ?> <em class="td-accent" style="font-size:22px;color:var(--td-ink)">Instrument Serif italic</em>. <?php esc_html_e( 'It replaces the old orange italic, and stays ink-colored.', 'techdella' ); ?></span></div>
			</div>
		</section>

		<section aria-labelledby="sg-components">
			<h2 class="td-sg__h2" id="sg-components"><?php esc_html_e( 'Components', 'techdella' ); ?></h2>
			<div class="td-sg__grid td-sg__grid--wide">
				<div class="td-sg__card">
					<span class="td-sg__mono">BUTTONS</span>
					<div class="td-sg__row">
						<?php
						echo td_button( __( 'Primary action', 'techdella' ), home_url( '/growth-audit/' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo td_button( __( 'Secondary', 'techdella' ), home_url( '/services/' ), 'secondary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
						<a class="td-link" href="<?php echo esc_url( home_url( '/case-studies/' ) ); ?>"><?php esc_html_e( 'Text link', 'techdella' ); ?></a>
					</div>
					<div class="td-sg__row">
						<?php echo td_button( __( 'Small', 'techdella' ), home_url( '/growth-audit/' ), 'primary', [ 'size' => 'sm' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span style="background:var(--td-ink);padding:8px;border-radius:var(--td-r-pill);display:inline-flex"><?php echo td_button( __( 'Light (on dark)', 'techdella' ), home_url( '/growth-audit/' ), 'light', [ 'size' => 'sm' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</div>
					<p class="td-sg__note"><?php esc_html_e( 'The orange circle-arrow is the brand’s signature on every primary action.', 'techdella' ); ?></p>
				</div>
				<div class="td-sg__card">
					<span class="td-sg__mono">LABELS &amp; CHIPS</span>
					<div class="td-sg__row">
						<?php echo td_chip( __( 'Section label', 'techdella' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo td_chip( __( 'Upper label', 'techdella' ), 'td-chip--upper' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="td-tag"><?php esc_html_e( 'Neutral chip', 'techdella' ); ?></span>
						<span class="td-tag td-tag--highlight"><?php esc_html_e( 'Highlight', 'techdella' ); ?></span>
						<span class="td-tag td-tag--selected"><?php esc_html_e( 'Selected', 'techdella' ); ?></span>
					</div>
					<div class="td-sg__row"><?php echo td_platform_badge( 'Google Ads', 'google-ads', 'G', true ) . td_platform_badge( 'Semrush', 'semrush', 'S' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				</div>
				<div class="td-sg__card">
					<span class="td-sg__mono">CARDS</span>
					<div class="td-sg__cards">
						<div class="td-sg__mini td-sg__mini--paper"><b>11</b><span>Paper</span></div>
						<div class="td-sg__mini td-sg__mini--sand"><b>5</b><span>Sand</span></div>
						<div class="td-sg__mini td-sg__mini--ink"><b>50<i>d</i></b><span>Ink</span></div>
					</div>
				</div>
				<div class="td-sg__card">
					<span class="td-sg__mono">THE ARCH</span>
					<div class="td-sg__arches">
						<div class="td-arch td-arch--lg">
						<?php
						echo td_picture( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'team/joanna-okedara-kalu',
							[
								'alt'   => '',
								'sizes' => '110px',
								'width' => 110,
							]
						);
						?>
						</div>
						<div class="td-arch td-arch--sm">
						<?php
						echo td_picture( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'team/akeem-feysupo',
							[
								'alt'   => '',
								'sizes' => '90px',
								'width' => 90,
							]
						);
						?>
						</div>
						<svg width="90" height="115" viewBox="0 0 90 115" aria-hidden="true" focusable="false"><path d="M8 115 V45 A37 37 0 0 1 82 45 V115" fill="none" stroke="currentColor" stroke-width="10"/><path d="M45 20 V115" stroke="currentColor" stroke-width="8"/></svg>
					</div>
					<p class="td-sg__note"><?php esc_html_e( 'Portraits and feature images sit in arch frames; large outline arches can mark dark sections.', 'techdella' ); ?></p>
				</div>
			</div>
		</section>

		<section class="td-sg__grid td-sg__grid--wide" aria-label="<?php esc_attr_e( 'Do and don’t', 'techdella' ); ?>">
			<div class="td-sg__card td-sg__do">
				<strong>✓ <?php esc_html_e( 'Do', 'techdella' ); ?></strong>
				<p><?php esc_html_e( 'Use one orange moment per section: a label dot, the button arrow, or a single highlighted number.', 'techdella' ); ?></p>
				<p><?php esc_html_e( 'Let photography bring warmth. Our team portraits already carry the brand’s arches and tones.', 'techdella' ); ?></p>
				<p><?php esc_html_e( 'Use black for primary buttons; it reads more premium and keeps orange meaningful.', 'techdella' ); ?></p>
			</div>
			<div class="td-sg__card td-sg__do">
				<strong>✕ <?php esc_html_e( 'Don’t', 'techdella' ); ?></strong>
				<p><?php esc_html_e( 'Fill large backgrounds, cards or headlines with orange or red.', 'techdella' ); ?></p>
				<p><?php esc_html_e( 'Put white text on #E8470A. It fails contrast; use ink, or #C23B07 for orange text.', 'techdella' ); ?></p>
				<p><?php esc_html_e( 'Mix in other accent colors. Status colors (green for success) are the only exception.', 'techdella' ); ?></p>
			</div>
		</section>
	</div>

	<div class="td-sg__blocks" id="blocks">
		<div class="td-container td-sg__blocks-head">
			<h2 class="td-sg__h2"><?php esc_html_e( 'Blocks', 'techdella' ); ?></h2>
			<p class="td-body"><?php esc_html_e( 'Every TechDella block, rendered from its block.json example.', 'techdella' ); ?></p>
			<ul class="td-sg__toc" role="list">
				<?php foreach ( $td_blocks as $td_name => $td_type ) : ?>
					<li><a href="#<?php echo esc_attr( 'block-' . sanitize_title( str_replace( 'techdella/', '', $td_name ) ) ); ?>"><?php echo esc_html( str_replace( 'techdella/', '', $td_name ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php foreach ( $td_blocks as $td_name => $td_type ) : ?>
			<?php
			$td_slug  = sanitize_title( str_replace( 'techdella/', '', $td_name ) );
			$td_attrs = is_array( $td_type->example ) && isset( $td_type->example['attributes'] ) ? (array) $td_type->example['attributes'] : [];
			$td_html  = render_block(
				[
					'blockName'    => $td_name,
					'attrs'        => $td_attrs,
					'innerBlocks'  => [],
					'innerHTML'    => '',
					'innerContent' => [],
				]
			);
			?>
			<section class="td-sg__block" id="<?php echo esc_attr( 'block-' . $td_slug ); ?>" data-td-block="<?php echo esc_attr( $td_name ); ?>" aria-label="<?php echo esc_attr( $td_name ); ?>">
				<div class="td-container td-sg__block-label"><span class="td-sg__mono"><?php echo esc_html( $td_name ); ?></span></div>
				<div class="td-sg__block-body<?php echo in_array( $td_name, $td_inline_blocks, true ) ? ' td-sg__block-body--inline' : ''; ?>">
					<?php echo $td_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output. ?>
				</div>
			</section>
		<?php endforeach; ?>

		<?php if ( isset( $td_blocks['techdella/bento-grid'] ) ) : ?>
			<section class="td-sg__block" id="block-bento-grid-about" aria-label="techdella/bento-grid (about)">
				<div class="td-container td-sg__block-label"><span class="td-sg__mono">techdella/bento-grid · about</span></div>
				<div class="td-sg__block-body">
					<?php
					echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'bento-grid',
						[
							'rowHeight' => 210,
							'padding'   => 'none',
							'tiles'     => [
								[
									'type'       => 'image',
									'span'       => 3,
									'spanTablet' => 3,
									'rows'       => 2,
									'image'      => 'photos/techdella-team-group',
									'alt'        => 'The TechDella team',
									'title'      => 'Who we are',
									'text'       => 'A senior team of strategists, marketers, designers and builders.',
								],
								[
									'type'        => 'stat',
									'span'        => 3,
									'spanTablet'  => 3,
									'variant'     => 'sand',
									'value'       => '11',
									'label'       => 'Published case studies',
									'avatars'     => [ 'people/levi-barbosa-wezops', 'people/akin-oyegoke-johan-consults' ],
									'avatarsMore' => '+9',
								],
								[
									'type'       => 'stat',
									'span'       => 3,
									'spanTablet' => 3,
									'variant'    => 'canvas',
									'value'      => '5',
									'label'      => 'Countries our clients operate in',
									'note'       => 'Nigeria · US · Mexico · SA · Lebanon',
								],
								[
									'type'       => 'quote',
									'span'       => 3,
									'spanTablet' => 3,
									'rows'       => 2,
									'quote'      => 'The clarity they brought to our strategy saved us months of trial and error — and probably a lot of money too…',
									'name'       => 'Akin Oyegoke',
									'role'       => 'CEO, Johan Consults',
									'image'      => 'people/akin-oyegoke-johan-consults',
								],
								[
									'type'       => 'link',
									'span'       => 6,
									'spanTablet' => 6,
									'image'      => 'team/joanna-okedara-kalu',
									'alt'        => 'Joanna Okedara-Kalu',
									'chip'       => 'About us',
									'title'      => 'Every engagement starts with a discovery call to understand your market.',
									'linkLabel'  => 'Book a call',
									'url'        => '/book-a-discovery-call/',
								],
							],
						]
					);
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( isset( $td_blocks['techdella/testimonial'] ) ) : ?>
			<section class="td-sg__block" id="block-testimonial-dark" aria-label="techdella/testimonial (dark)">
				<div class="td-container td-sg__block-label"><span class="td-sg__mono">techdella/testimonial · dark</span></div>
				<div class="td-sg__block-body td-sg__block-body--inline" style="max-width:520px;margin-inline:0">
					<?php
					echo td_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'testimonial',
						[
							'quote'   => 'The clarity they brought to our strategy saved us months of trial and error — and probably a lot of money too…',
							'name'    => 'Akin Oyegoke',
							'role'    => 'CEO',
							'company' => 'Johan Consults',
							'image'   => 'people/akin-oyegoke-johan-consults',
							'variant' => 'dark',
						]
					);
					?>
				</div>
			</section>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
