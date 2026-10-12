<?php
/**
 * Article covers (IMG-12, "template-driven, title + one image"): 1600×900 JPEG per article.
 *
 * Layout: a small logo mark top-left, a category label and the title (Geist SemiBold, up to three
 * lines, one Instrument Serif italic accent word at most) on the left, and the TechDella arch on the
 * right. The arch holds either a topic illustration drawn here with GD (search results for SEO, an
 * inbox for email, an ad card for paid, chat bubbles for AI …) or, for some posts, one of our photos.
 * Everything that matters sits inside the centred 1200×630 region (with a margin), so the same
 * file crops cleanly to 1.9:1 in cards and link previews. Drawn at 2× and downsampled, which antialiases every shape.
 *
 * Set as the featured image only where an article has none (or has an older generated cover), so
 * an editor's own image always wins. `wp td covers:generate`.
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

const TD_COVER_VERSION = 2;

/** Supersampling factor: draw at 2×, then downsample to 1600×900. */
const TD_COVER_SS = 2;

/** The arch (right column): x1, y1, x2, y2 at 1×. Inside the 200–1400 × 135–765 safe area. */
const TD_COVER_ARCH = [ 940, 150, 1400, 750 ];

/**
 * Photos available for covers (theme image derivatives), in a fixed order.
 *
 * @return string[] Absolute file paths.
 */
function td_cover_photos(): array {
	$out = [];
	foreach ( [ 'woman-working-laptop-desk', 'website-wireframe-sketches', 'designer-desk-swatches-wide', 'shop-owner-checking-phone', 'designer-drawing-tablet-studio', 'product-flatlay-notebook-candle-square' ] as $name ) {
		$file = get_theme_file_path( 'assets/img/stock/' . $name . '-1200.jpg' );
		if ( is_readable( $file ) ) {
			$out[] = $file;
		}
	}
	return $out;
}

/**
 * Pick the illustration for a post from its category label first, then its title/slug.
 *
 * @param string $label Category label.
 * @param string $text  Title (and slug) text.
 * @return string Motif key (see td_cover_motifs()).
 */
function td_cover_motif_for( string $label, string $text = '' ): string {
	$map = [
		'letter'     => '/\b(founder|founders\' letter|letter from)\b/',
		'ai'         => '/\b(ai|llms?|chatgpt|gpt|perplexity|claude|gemini|aeo|geo|ai overviews?|vibe cod\w*|machine learning|ai agents?|prompts?)\b/',
		'automation' => '/\b(automat\w*|workflows?|zapier|integrations?|crm)\b/',
		'email'      => '/\b(e-?mails?|newsletters?|inbox\w*|deliverability|dmarc|dkim|spf|bimi|cold outreach)\b/',
		'leads'      => '/\b(leads?|lead gen\w*|funnels?|conversions?|cro|landing pages?|lead magnets?|mqls?|sqls?)\b/',
		'paid'       => '/\b(paid|ads|advert\w*|ppc|cpc|cpm|roas|performance max|campaigns?)\b/',
		'search'     => '/\b(seo|search|serps?|keywords?|backlinks?|rank\w*|sitemaps?|crawl\w*|indexing|schema|snippets?)\b/',
		'growth'     => '/\b(analytics?|metrics?|kpis?|growth|traffic|data|dashboards?|attribution|measur\w*|ga4|reporting|retention|churn)\b/',
		'finance'    => '/\b(fund\w*|safe|invest\w*|valuation|cap table|financ\w*|revenue|cash\w*|tax\w*|payroll|budget\w*|equity|dilution|money|pricing|costs?|loans?|bank\w*|accounting|profit\w*|grants?)\b/',
		'design'     => '/\b(design\w*|brand\w*|logos?|ui|ux|colou?rs?|fonts?|typography|visual\w*|names?|naming)\b/',
		'code'       => '/\b(cod(e|ing)|develop\w*|mvps?|software|apps?|websites?|wordpress|apis?|tech stack|no-code|saas|launch\w*)\b/',
		'social'     => '/\b(social|instagram|tiktok|linkedin|youtube|videos?|influenc\w*|creators?|reels?|community)\b/',
		'doc'        => '/\b(content|blog\w*|copy\w*|writ\w*|articles?|storytelling|pr)\b/',
		'store'      => '/\b(business(es)?|shops?|stores?|retail\w*|restaurants?|bakery|salons?|boutiques?|farm\w*|cafe|catering|e-?commerce|small business)\b/',
	];
	// Pass 1: the label; pass 2: the title. The generic shopfront only wins once neither has a
	// sharper topic, so "How to start a SaaS business" gets the code window, not the shop.
	$generic = '';
	foreach ( [ $label, $text ] as $pass => $source ) {
		$source = strtolower( $source );
		foreach ( $map as $motif => $re ) {
			if ( '' === $source || ( 'letter' === $motif && 1 === $pass ) ) {
				continue; // The letter motif is for the Founder category only, not every "founder" title.
			}
			if ( preg_match( $re, $source ) ) {
				if ( 'store' !== $motif ) {
					return $motif;
				}
				$generic = $motif;
			}
		}
	}
	return '' !== $generic ? $generic : 'board';
}

/**
 * Motif key → drawing function.
 *
 * @return array<string,callable>
 */
function td_cover_motifs(): array {
	return [
		'search'     => 'td_cover_motif_search',
		'email'      => 'td_cover_motif_email',
		'paid'       => 'td_cover_motif_paid',
		'ai'         => 'td_cover_motif_ai',
		'growth'     => 'td_cover_motif_growth',
		'leads'      => 'td_cover_motif_leads',
		'finance'    => 'td_cover_motif_finance',
		'design'     => 'td_cover_motif_design',
		'code'       => 'td_cover_motif_code',
		'automation' => 'td_cover_motif_automation',
		'social'     => 'td_cover_motif_social',
		'doc'        => 'td_cover_motif_doc',
		'letter'     => 'td_cover_motif_letter',
		'store'      => 'td_cover_motif_store',
		'board'      => 'td_cover_motif_board',
	];
}

// Drawing primitives. Coordinates are in 1× (1600×900) units and scaled by TD_COVER_SS.

/**
 * Scale a 1× coordinate to the supersampled canvas.
 *
 * @param float $v Value at 1×.
 */
function td_cover_px( float $v ): int {
	return (int) round( $v * TD_COVER_SS );
}

/**
 * Allocate a colour from a hex string.
 *
 * @param GdImage $img   Image.
 * @param string  $hex   RRGGBB.
 * @param int     $alpha 0 (opaque) … 127 (transparent).
 */
function td_cover_rgb( $img, string $hex, int $alpha = 0 ): int {
	$n = (int) hexdec( $hex );
	return (int) imagecolorallocatealpha( $img, ( $n >> 16 ) & 0xFF, ( $n >> 8 ) & 0xFF, $n & 0xFF, $alpha );
}

/**
 * Deterministic pseudo-random integer (LCG), so a post always gets the same drawing.
 *
 * @param int $seed State, advanced in place.
 * @param int $min  Minimum.
 * @param int $max  Maximum.
 */
function td_cover_rand( int &$seed, int $min, int $max ): int {
	$seed = ( $seed * 1103515245 + 12345 ) & 0x7FFFFFFF;
	return $min + ( $seed >> 8 ) % ( $max - $min + 1 );
}

/**
 * Filled rounded rectangle, as one polygon (so translucent fills don't double up).
 *
 * @param GdImage $img Image.
 * @param float   $x1  Left.
 * @param float   $y1  Top.
 * @param float   $x2  Right.
 * @param float   $y2  Bottom.
 * @param float   $r   Corner radius.
 * @param int     $col Colour.
 */
function td_cover_rrect( $img, float $x1, float $y1, float $x2, float $y2, float $r, int $col ): void {
	$r = max( 0.0, min( $r, ( $x2 - $x1 ) / 2, ( $y2 - $y1 ) / 2 ) );
	if ( $r < 1 ) {
		imagefilledrectangle( $img, td_cover_px( $x1 ), td_cover_px( $y1 ), td_cover_px( $x2 ) - 1, td_cover_px( $y2 ) - 1, $col );
		return;
	}
	$pts     = [];
	$corners = [ [ $x2 - $r, $y1 + $r, -90 ], [ $x2 - $r, $y2 - $r, 0 ], [ $x1 + $r, $y2 - $r, 90 ], [ $x1 + $r, $y1 + $r, 180 ] ];
	$steps   = max( 4, (int) ceil( $r / 2 ) );
	foreach ( $corners as $c ) {
		for ( $i = 0; $i <= $steps; $i++ ) {
			$a     = deg2rad( $c[2] + 90 * $i / $steps );
			$pts[] = td_cover_px( $c[0] + $r * cos( $a ) );
			$pts[] = td_cover_px( $c[1] + $r * sin( $a ) );
		}
	}
	imagefilledpolygon( $img, $pts, $col );
}

/**
 * Filled circle.
 *
 * @param GdImage $img Image.
 * @param float   $cx  Centre x.
 * @param float   $cy  Centre y.
 * @param float   $d   Diameter.
 * @param int     $col Colour.
 */
function td_cover_dot( $img, float $cx, float $cy, float $d, int $col ): void {
	imagefilledellipse( $img, td_cover_px( $cx ), td_cover_px( $cy ), td_cover_px( $d ), td_cover_px( $d ), $col );
}

/**
 * Pill-shaped bar (placeholder text line).
 *
 * @param GdImage $img Image.
 * @param float   $x   Left.
 * @param float   $y   Top.
 * @param float   $w   Width.
 * @param float   $h   Height.
 * @param int     $col Colour.
 */
function td_cover_bar( $img, float $x, float $y, float $w, float $h, int $col ): void {
	td_cover_rrect( $img, $x, $y, $x + $w, $y + $h, $h / 2, $col );
}

/**
 * Filled polygon from 1× points.
 *
 * @param GdImage $img Image.
 * @param float[] $pts Flat x,y list.
 * @param int     $col Colour.
 */
function td_cover_poly( $img, array $pts, int $col ): void {
	imagefilledpolygon( $img, array_map( 'td_cover_px', $pts ), $col );
}

/**
 * Thick line with round caps.
 *
 * @param GdImage $img Image.
 * @param float[] $pts Flat x,y list (a polyline).
 * @param float   $t   Thickness.
 * @param int     $col Colour.
 */
function td_cover_line( $img, array $pts, float $t, int $col ): void {
	imagesetthickness( $img, td_cover_px( $t ) );
	$n = count( $pts );
	for ( $i = 0; $i + 3 < $n; $i += 2 ) {
		imageline( $img, td_cover_px( $pts[ $i ] ), td_cover_px( $pts[ $i + 1 ] ), td_cover_px( $pts[ $i + 2 ] ), td_cover_px( $pts[ $i + 3 ] ), $col );
	}
	imagesetthickness( $img, 1 );
	for ( $i = 0; $i + 1 < $n; $i += 2 ) {
		td_cover_dot( $img, $pts[ $i ], $pts[ $i + 1 ], $t, $col );
	}
}

/**
 * A card: soft layered shadow, then the rounded fill.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param float                 $x1   Left.
 * @param float                 $y1   Top.
 * @param float                 $x2   Right.
 * @param float                 $y2   Bottom.
 * @param float                 $r    Radius.
 * @param string                $fill Colour key (default 'card').
 */
function td_cover_card( $img, array $c, float $x1, float $y1, float $x2, float $y2, float $r = 18, string $fill = 'card' ): void {
	// Eight faint layers, each tighter and lower than the last, read as one soft blur.
	for ( $i = 8; $i >= 1; $i-- ) {
		$e = $i * 2.2 - 2;
		td_cover_rrect( $img, $x1 - $e, $y1 - $e + 4 + $i * 0.8, $x2 + $e, $y2 + $e + 4 + $i * 0.8, $r + $e, $c['sh1'] );
	}
	td_cover_rrect( $img, $x1 - 0.5, $y1, $x2 + 0.5, $y2 + 1.5, $r, $c['sh3'] );
	td_cover_rrect( $img, $x1, $y1, $x2, $y2, $r, $c[ $fill ] );
}

/**
 * Text at 1× coordinates (baseline-left), size in px.
 *
 * @param GdImage $img  Image.
 * @param float   $px   Font size in px.
 * @param float   $x    Left.
 * @param float   $y    Baseline.
 * @param int     $col  Colour.
 * @param string  $font TTF path.
 * @param string  $text Text.
 */
function td_cover_text( $img, float $px, float $x, float $y, int $col, string $font, string $text ): void {
	imagettftext( $img, $px * 0.75 * TD_COVER_SS, 0, td_cover_px( $x ), td_cover_px( $y ), $col, $font, $text );
}

/**
 * Text advance width at 1×, size in px.
 *
 * @param float  $px   Font size in px.
 * @param string $font TTF path.
 * @param string $text Text.
 */
function td_cover_tw( float $px, string $font, string $text ): float {
	$box = imagettfbbox( $px * 0.75 * TD_COVER_SS, 0, $font, $text );
	return false === $box ? 0.0 : ( $box[2] - $box[0] ) / TD_COVER_SS;
}

/**
 * Word space at a size (GD's own space is tight for display sizes, so open it up a little).
 *
 * @param float  $px   Font size in px.
 * @param string $font TTF path.
 */
function td_cover_space( float $px, string $font ): float {
	return ( td_cover_tw( $px, $font, 'a a' ) - td_cover_tw( $px, $font, 'aa' ) ) * 1.2;
}

// Title layout.

/**
 * Lay the title out in at most three lines, shrinking the size for long titles and ending with an
 * ellipsis if it still doesn't fit. The last word becomes the serif accent when it is a plain
 * lower-case word (never an acronym, number or year).
 *
 * @param string $title Title.
 * @param string $sans  Sans TTF.
 * @param string $serif Serif TTF.
 * @param float  $width Column width.
 * @return array{size:float,lines:array<int,array<int,array{0:string,1:bool}>>} Size in px; lines of [word, is_accent].
 */
function td_cover_title_layout( string $title, string $sans, string $serif, float $width ): array {
	$words = preg_split( '/\s+/u', trim( $title ), -1, PREG_SPLIT_NO_EMPTY );
	$words = is_array( $words ) && $words ? $words : [ $title ];
	// Keep one-letter words ("a", "A", "&") with the word after them, so no line ends on one.
	for ( $i = count( $words ) - 2; $i >= 0; $i-- ) {
		if ( 1 === mb_strlen( $words[ $i ] ) ) {
			array_splice( $words, $i, 2, [ $words[ $i ] . ' ' . $words[ $i + 1 ] ] );
		}
	}
	$last = count( $words ) - 1;
	$core = $last > 1 ? (string) preg_replace( '/[.!?,:;]+$/u', '', $words[ $last ] ) : '';
	$acc  = '' !== $core && preg_match( '/^\p{Lu}?\p{Ll}{3,}$/u', $core ) ? $last : -1;

	$wrap = static function ( float $px, float $width ) use ( $words, $acc, $sans, $serif ): array {
		$space = td_cover_space( $px, $sans );
		$lines = [];
		$line  = [];
		$used  = 0.0;
		foreach ( $words as $i => $word ) {
			$is_acc = $i === $acc;
			$w      = $is_acc ? td_cover_tw( $px * 1.14, $serif, $word ) : td_cover_tw( $px, $sans, $word );
			$need   = $line ? $used + $space + $w : $w;
			if ( $line && $need > $width ) {
				$lines[] = $line;
				$line    = [];
				$need    = $w;
			}
			$line[] = [ $word, $is_acc ];
			$used   = $need;
		}
		$lines[] = $line; // $words is never empty, so the last line always holds a word.
		return $lines;
	};

	$size = 74.0;
	$min  = 50.0;
	for ( ; $size > $min; $size -= 4 ) {
		$lines = $wrap( $size, $width );
		$n     = count( $lines );
		if ( $n <= 3 ) {
			// Balance: the narrowest column that keeps the same line count (no lonely last word).
			for ( $try = $width - 20; $n > 1 && $try > $width * 0.55; $try -= 20 ) {
				$tighter = $wrap( $size, $try );
				if ( count( $tighter ) !== $n ) {
					break;
				}
				$lines = $tighter;
			}
			return [
				'size'  => $size,
				'lines' => $lines,
			];
		}
	}
	$size  = $min;
	$lines = $wrap( $size, $width );
	if ( count( $lines ) > 3 ) {
		// Ellipsis guard: refill the third line with plain words until "…" fits.
		$rest  = array_merge( ...array_slice( $lines, 2 ) );
		$third = [];
		foreach ( $rest as $tok ) {
			$try  = array_merge( $third, [ [ $tok[0], false ] ] );
			$text = implode( ' ', array_column( $try, 0 ) ) . '…';
			if ( td_cover_tw( $size, $sans, $text ) > $width ) {
				break;
			}
			$third = $try;
		}
		if ( $third ) {
			$n              = count( $third ) - 1;
			$third[ $n ][0] = rtrim( $third[ $n ][0], ' .,;:–—-' ) . '…';
		}
		$lines = [ $lines[0], $lines[1], $third ];
	}
	return [
		'size'  => $size,
		'lines' => $lines,
	];
}

// Motifs. Each draws inside the arch (roughly x 975–1365, y 290–715) with a little overlap
// allowed for depth; at most one small orange accent.

/**
 * SEO / search: a search bar over a stack of results.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_search( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 975, 292, 1365, 350, 29 );
	td_cover_dot( $img, 1007, 321, 24, $c['ink'] );
	td_cover_dot( $img, 1007, 321, 15, $c['card'] );
	td_cover_line( $img, [ 1014, 328, 1022, 336 ], 4.5, $c['ink'] );
	td_cover_bar( $img, 1038, 316, td_cover_rand( $seed, 140, 200 ), 10, $c['mid'] );
	$y = 374;
	for ( $i = 0; $i < 3; $i++ ) {
		$x1 = 0 === $i ? 955 : 975;
		td_cover_card( $img, $c, $x1, $y, 1365, $y + 100, 18 );
		td_cover_dot( $img, $x1 + 30, $y + 26, 18, $c['soft2'] );
		td_cover_bar( $img, $x1 + 48, $y + 22, td_cover_rand( $seed, 80, 120 ), 8, $c['mid'] );
		td_cover_bar( $img, $x1 + 22, $y + 46, td_cover_rand( $seed, 210, 290 ), 13, 0 === $i ? $c['ink'] : $c['soft2'] );
		td_cover_bar( $img, $x1 + 22, $y + 70, td_cover_rand( $seed, 260, 320 ), 8, $c['line'] );
		if ( 0 === $i ) {
			td_cover_dot( $img, 1337, $y + 26, 12, $c['orange'] );
		}
		$y += 114;
	}
}

/**
 * Email: an inbox list with an envelope in front.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_email( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 985, 296, 1365, 612, 22 );
	td_cover_bar( $img, 1011, 322, 90, 12, $c['ink'] );
	td_cover_bar( $img, 1289, 324, 50, 8, $c['line'] );
	for ( $i = 0; $i < 4; $i++ ) {
		$y = 356 + $i * 62;
		if ( $i > 0 ) {
			imagefilledrectangle( $img, td_cover_px( 1011 ), td_cover_px( $y - 6 ), td_cover_px( 1339 ), td_cover_px( $y - 6 ) + 1, $c['line'] );
		}
		td_cover_dot( $img, 1031, $y + 24, 38, 0 === $i % 2 ? $c['soft2'] : $c['soft'] );
		td_cover_bar( $img, 1063, $y + 12, td_cover_rand( $seed, 80, 130 ), 10, $c['ink'] );
		td_cover_bar( $img, 1063, $y + 31, td_cover_rand( $seed, 170, 250 ), 8, $c['line'] );
		if ( 0 === $i ) {
			td_cover_dot( $img, 1333, $y + 17, 11, $c['orange'] );
		}
	}
	// Envelope.
	td_cover_card( $img, $c, 930, 560, 1150, 712, 16 );
	td_cover_poly( $img, [ 942, 572, 1138, 572, 1040, 646 ], $c['soft'] );
	td_cover_line( $img, [ 944, 700, 1012, 640 ], 3, $c['line'] );
	td_cover_line( $img, [ 1136, 700, 1068, 640 ], 3, $c['line'] );
}

/**
 * Paid ads: an ad card and a small results bar chart.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_paid( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 1010, 282, 1360, 590, 22 );
	td_cover_rrect( $img, 1026, 298, 1344, 452, 14, $c['soft'] );
	td_cover_dot( $img, 1284, 344, 44, $c['card'] );
	td_cover_poly( $img, [ 1026, 452, 1026, 430, 1108, 372, 1168, 416, 1214, 386, 1290, 438, 1344, 418, 1344, 452 ], $c['soft2'] );
	td_cover_rrect( $img, 1026, 432, 1344, 452, 0, $c['soft2'] );
	td_cover_rrect( $img, 1026, 430, 1344, 452, 14, $c['soft2'] );
	td_cover_bar( $img, 1026, 470, 64, 8, $c['mid'] );
	td_cover_bar( $img, 1026, 490, td_cover_rand( $seed, 220, 280 ), 13, $c['ink'] );
	td_cover_bar( $img, 1026, 514, td_cover_rand( $seed, 160, 220 ), 8, $c['line'] );
	td_cover_bar( $img, 1228, 542, 116, 32, $c['ink'] );
	td_cover_bar( $img, 1252, 554, 68, 8, $c['card'] );
	// Results chart.
	td_cover_card( $img, $c, 944, 548, 1180, 712, 18 );
	td_cover_bar( $img, 966, 570, 70, 8, $c['mid'] );
	$h = [ 30, 46, 40, 62, 88 ];
	foreach ( $h as $i => $bh ) {
		$bh += td_cover_rand( $seed, -6, 6 );
		$x   = 966 + $i * 41;
		td_cover_rrect( $img, $x, 690 - $bh, $x + 27, 690, 6, 4 === $i ? $c['orange'] : ( $i % 2 ? $c['soft2'] : $c['ink'] ) );
	}
}

/**
 * AI: a short chat, one answer bubble and a spark.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_ai( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 975, 296, 1250, 386, 24 );
	td_cover_bar( $img, 999, 322, td_cover_rand( $seed, 190, 220 ), 10, $c['mid'] );
	td_cover_bar( $img, 999, 346, td_cover_rand( $seed, 110, 170 ), 10, $c['line'] );
	td_cover_card( $img, $c, 1060, 410, 1365, 560, 24, 'deep' );
	$y = 440;
	for ( $i = 0; $i < 4; $i++ ) {
		td_cover_bar( $img, 1086, $y, 3 === $i ? td_cover_rand( $seed, 90, 140 ) : td_cover_rand( $seed, 200, 250 ), 10, 0 === $i ? $c['ondeep'] : $c['deepline'] );
		$y += 24;
	}
	// Spark (four-point star) on the answer's corner.
	$sx = 1060;
	$sy = 410;
	td_cover_poly( $img, [ $sx, $sy - 26, $sx + 7, $sy - 7, $sx + 26, $sy, $sx + 7, $sy + 7, $sx, $sy + 26, $sx - 7, $sy + 7, $sx - 26, $sy, $sx - 7, $sy - 7 ], $c['orange'] );
	td_cover_card( $img, $c, 975, 584, 1095, 636, 26 );
	foreach ( [ 1007, 1035, 1063 ] as $dx ) {
		td_cover_dot( $img, $dx, 610, 11, $c['mid'] );
	}
	td_cover_card( $img, $c, 975, 662, 1365, 714, 26 );
	td_cover_bar( $img, 1001, 683, 150, 10, $c['line'] );
	td_cover_dot( $img, 1339, 688, 36, $c['ink'] );
	td_cover_poly( $img, [ 1339, 678, 1348, 692, 1330, 692 ], $c['card'] );
}

/**
 * Analytics / growth: a line chart rising.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_growth( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 975, 330, 1365, 712, 24 );
	td_cover_bar( $img, 1001, 386, 90, 9, $c['mid'] );
	td_cover_bar( $img, 1001, 406, 150, 16, $c['ink'] );
	foreach ( [ 470, 530, 590, 650 ] as $gy ) {
		imagefilledrectangle( $img, td_cover_px( 1001 ), td_cover_px( $gy ), td_cover_px( 1339 ), td_cover_px( $gy ) + 1, $c['line'] );
	}
	$pts = [];
	$n   = 6;
	for ( $i = 0; $i < $n; $i++ ) {
		$x     = 1011 + $i * ( 318 / ( $n - 1 ) );
		$y     = 666 - $i * 38 + ( $i > 0 && $i < $n - 1 ? td_cover_rand( $seed, -26, 22 ) : 0 );
		$pts[] = $x;
		$pts[] = $y;
	}
	td_cover_poly( $img, array_merge( $pts, [ 1329, 684, 1011, 684 ] ), $c['tint'] );
	td_cover_line( $img, $pts, 6, $c['ink'] );
	td_cover_dot( $img, 1329, $pts[ 2 * $n - 1 ], 30, $c['card'] );
	td_cover_dot( $img, 1329, $pts[ 2 * $n - 1 ], 16, $c['orange'] );
	// Floating KPI pill.
	td_cover_card( $img, $c, 940, 300, 1120, 352, 26 );
	td_cover_poly( $img, [ 970, 336, 982, 316, 994, 336 ], $c['ink'] );
	td_cover_bar( $img, 1006, 320, 90, 12, $c['ink'] );
}

/**
 * Lead generation: a funnel ending in a contact card.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_leads( $img, array $c, int $seed ): void {
	$cols = [ 'card', 'soft2', 'mid', 'ink' ];
	$y    = 316;
	foreach ( $cols as $i => $col ) {
		$top = 340 - $i * 70;
		$bot = $top - 58;
		td_cover_poly( $img, [ 1170 - $top / 2, $y + 5, 1170 + $top / 2, $y + 5, 1170 + $bot / 2, $y + 59, 1170 - $bot / 2, $y + 59 ], $c['sh2'] );
		td_cover_poly( $img, [ 1170 - $top / 2, $y, 1170 + $top / 2, $y, 1170 + $bot / 2, $y + 54, 1170 - $bot / 2, $y + 54 ], $c[ $col ] );
		$y += 64;
	}
	td_cover_dot( $img, 1170, $y + 6, 16, $c['orange'] );
	td_cover_card( $img, $c, 1025, $y + 34, 1315, $y + 120, 20 );
	td_cover_dot( $img, 1065, $y + 77, 44, $c['soft2'] );
	td_cover_bar( $img, 1101, $y + 58, td_cover_rand( $seed, 110, 150 ), 12, $c['ink'] );
	td_cover_bar( $img, 1101, $y + 82, td_cover_rand( $seed, 140, 180 ), 8, $c['line'] );
}

/**
 * Finance: a card, a ledger and a stack of coins.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_finance( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 990, 300, 1320, 500, 24, 'deep' );
	td_cover_rrect( $img, 1018, 332, 1066, 368, 9, $c['soft2'] );
	td_cover_bar( $img, 1018, 420, td_cover_rand( $seed, 150, 200 ), 11, $c['deepline'] );
	td_cover_bar( $img, 1018, 446, 90, 11, $c['deepline'] );
	td_cover_dot( $img, 1284, 350, 28, $c['ondeep'] );
	// Ledger.
	td_cover_card( $img, $c, 975, 528, 1180, 712, 20 );
	for ( $i = 0; $i < 4; $i++ ) {
		$y = 556 + $i * 38;
		td_cover_bar( $img, 999, $y, td_cover_rand( $seed, 60, 90 ), 9, $c['mid'] );
		td_cover_bar( $img, 1110, $y, 46, 9, 3 === $i ? $c['ink'] : $c['line'] );
	}
	// Coins.
	foreach ( [ [ 1236, 3 ], [ 1314, 5 ] ] as $stack ) {
		for ( $i = 0; $i < $stack[1]; $i++ ) {
			$cy = 692 - $i * 16;
			imagefilledellipse( $img, td_cover_px( $stack[0] ), td_cover_px( $cy + 8 ), td_cover_px( 92 ), td_cover_px( 34 ), $c['soft2'] );
			imagefilledellipse( $img, td_cover_px( $stack[0] ), td_cover_px( $cy ), td_cover_px( 92 ), td_cover_px( 34 ), $c['card'] );
		}
		$top = 692 - ( $stack[1] - 1 ) * 16;
		imagefilledellipse( $img, td_cover_px( $stack[0] ), td_cover_px( $top ), td_cover_px( 62 ), td_cover_px( 22 ), $c['soft'] );
	}
	imagefilledellipse( $img, td_cover_px( 1314 ), td_cover_px( 628 ), td_cover_px( 20 ), td_cover_px( 8 ), $c['orange'] );
}

/**
 * Design / brand: swatches above a layout grid.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_design( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 975, 296, 1365, 392, 22 );
	foreach ( [ 'ink', 'body', 'soft2', 'orange' ] as $i => $k ) {
		td_cover_dot( $img, 1023 + $i * 66, 344, 52, $c[ $k ] );
	}
	td_cover_bar( $img, 1279, 330, 60, 9, $c['mid'] );
	td_cover_bar( $img, 1279, 350, 40, 9, $c['line'] );
	td_cover_card( $img, $c, 975, 414, 1365, 712, 22 );
	td_cover_bar( $img, 999, 438, td_cover_rand( $seed, 110, 150 ), 12, $c['ink'] );
	$split = td_cover_rand( $seed, 0, 1 ) ? 1170 : 1200;
	td_cover_rrect( $img, 999, 466, $split - 8, 600, 12, $c['soft'] );
	td_cover_rrect( $img, $split + 8, 466, 1341, 528, 12, $c['soft2'] );
	td_cover_rrect( $img, $split + 8, 538, 1341, 600, 12, $c['line'] );
	td_cover_bar( $img, 999, 620, 320, 8, $c['line'] );
	td_cover_bar( $img, 999, 638, 240, 8, $c['line'] );
	td_cover_bar( $img, 999, 662, 96, 30, $c['ink'] );
}

/**
 * Dev / build: a code window.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_code( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 965, 300, 1365, 712, 22, 'deep' );
	foreach ( [ 993, 1013, 1033 ] as $x ) {
		td_cover_dot( $img, $x, 330, 12, $c['deepline'] );
	}
	td_cover_rrect( $img, 965, 356, 1365, 357, 0, $c['deepline'] );
	$hot   = td_cover_rand( $seed, 2, 9 );
	$depth = 0;
	for ( $i = 0; $i < 12; $i++ ) {
		$y = 380 + $i * 26;
		td_cover_bar( $img, 989, $y, 14, 8, $c['deepline'] );
		$x    = 1019 + $depth * 26;
		$segs = td_cover_rand( $seed, 1, 3 );
		for ( $s = 0; $s < $segs && $x < 1300; $s++ ) {
			$w   = min( td_cover_rand( $seed, 30, 110 ), 1335 - $x );
			$col = $i === $hot && 1 === $s ? $c['orange'] : ( 0 === $s ? $c['ondeep'] : $c['codemid'] );
			td_cover_bar( $img, $x, $y, $w, 8, $col );
			$x += $w + 10;
		}
		$depth = max( 0, min( 3, $depth + td_cover_rand( $seed, -1, 1 ) ) );
	}
}

/**
 * Automation: three connected workflow nodes.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_automation( $img, array $c, int $seed ): void {
	td_cover_line( $img, [ 1085, 380, 1085, 420, 1255, 420, 1255, 458 ], 4, $c['mid'] );
	td_cover_line( $img, [ 1255, 530, 1255, 566, 1085, 566, 1085, 604 ], 4, $c['mid'] );
	$nodes = [ [ 975, 306, 'soft2' ], [ 1145, 458, 'deep' ], [ 975, 604, 'soft' ] ];
	foreach ( $nodes as $n ) {
		td_cover_card( $img, $c, $n[0], $n[1], $n[0] + 220, $n[1] + 74, 20 );
		td_cover_rrect( $img, $n[0] + 16, $n[1] + 16, $n[0] + 58, $n[1] + 58, 12, $c[ $n[2] ] );
		td_cover_bar( $img, $n[0] + 74, $n[1] + 22, td_cover_rand( $seed, 80, 120 ), 11, $c['ink'] );
		td_cover_bar( $img, $n[0] + 74, $n[1] + 44, td_cover_rand( $seed, 50, 90 ), 8, $c['line'] );
	}
	td_cover_bar( $img, 1171, 487, 22, 6, $c['ondeep'] );
	td_cover_bar( $img, 1171, 499, 14, 6, $c['ondeep'] );
	td_cover_dot( $img, 1170, 420, 18, $c['card'] );
	td_cover_dot( $img, 1170, 420, 12, $c['orange'] );
}

/**
 * Social / video: a phone with a feed.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_social( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 1040, 292, 1290, 716, 40, 'deep' );
	td_cover_rrect( $img, 1052, 304, 1278, 704, 30, $c['card'] );
	td_cover_bar( $img, 1135, 314, 60, 12, $c['deep'] );
	td_cover_dot( $img, 1080, 356, 28, $c['soft2'] );
	td_cover_bar( $img, 1102, 346, td_cover_rand( $seed, 70, 100 ), 9, $c['ink'] );
	td_cover_bar( $img, 1102, 360, 50, 7, $c['line'] );
	td_cover_rrect( $img, 1066, 380, 1264, 548, 14, $c['soft'] );
	td_cover_dot( $img, 1214, 420, 34, $c['card'] );
	td_cover_poly( $img, [ 1066, 548, 1066, 520, 1130, 470, 1180, 514, 1214, 490, 1264, 530, 1264, 548 ], $c['soft2'] );
	td_cover_dot( $img, 1076, 570, 14, $c['orange'] );
	td_cover_dot( $img, 1098, 570, 14, $c['line'] );
	td_cover_bar( $img, 1066, 590, td_cover_rand( $seed, 140, 180 ), 8, $c['line'] );
	td_cover_bar( $img, 1066, 606, 110, 8, $c['line'] );
	td_cover_rrect( $img, 1066, 628, 1264, 704, 14, $c['soft2'] );
	// Floating comment.
	td_cover_card( $img, $c, 960, 470, 1100, 520, 25 );
	td_cover_dot( $img, 985, 495, 22, $c['soft2'] );
	td_cover_bar( $img, 1004, 490, 72, 10, $c['ink'] );
}

/**
 * Content: an article page with a pull quote.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_doc( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 1000, 292, 1340, 714, 18 );
	td_cover_bar( $img, 1028, 322, td_cover_rand( $seed, 220, 260 ), 16, $c['ink'] );
	td_cover_bar( $img, 1028, 346, td_cover_rand( $seed, 140, 190 ), 16, $c['ink'] );
	td_cover_bar( $img, 1028, 378, 110, 8, $c['mid'] );
	td_cover_rrect( $img, 1028, 400, 1312, 512, 12, $c['soft'] );
	$y = 530;
	for ( $i = 0; $i < 3; $i++ ) {
		td_cover_bar( $img, 1028, $y, td_cover_rand( $seed, 220, 284 ), 8, $c['line'] );
		$y += 17;
	}
	td_cover_rrect( $img, 1028, 594, 1032, 648, 0, $c['orange'] );
	td_cover_bar( $img, 1046, 600, td_cover_rand( $seed, 200, 240 ), 10, $c['mid'] );
	td_cover_bar( $img, 1046, 622, td_cover_rand( $seed, 120, 180 ), 10, $c['mid'] );
	td_cover_bar( $img, 1028, 670, 270, 8, $c['line'] );
	td_cover_bar( $img, 1028, 687, 180, 8, $c['line'] );
}

/**
 * Founder letter: a page with a signature.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_letter( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 1030, 280, 1350, 690, 16, 'soft' );
	td_cover_card( $img, $c, 990, 300, 1320, 714, 16 );
	td_cover_bar( $img, 1020, 336, 96, 12, $c['ink'] );
	$y = 372;
	for ( $i = 0; $i < 9; $i++ ) {
		td_cover_bar( $img, 1020, $y, 8 === $i ? 140 : td_cover_rand( $seed, 220, 270 ), 8, $c['line'] );
		$y += 21;
	}
	// Signature: a looping scribble (a prolate cycloid) drifting up to the right.
	$pts = [];
	for ( $i = 0; $i <= 120; $i++ ) {
		$t     = $i / 120 * 6 * M_PI;
		$pts[] = 1030 + $t * 8 - 15 * sin( $t );
		$pts[] = 630 - $t * 1.2 - 20 * cos( $t ) * ( 0.55 + 0.45 * sin( $t / 3 + 0.6 ) );
	}
	td_cover_line( $img, $pts, 3, $c['ink'] );
	td_cover_bar( $img, 1020, 676, 90, 8, $c['mid'] );
	td_cover_dot( $img, 1292, 336, 12, $c['orange'] );
}

/**
 * Business guides: a simple shopfront (sign, awning, window, door).
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_store( $img, array $c, int $seed ): void {
	// Mirror the shop (door left or right) around the arch's centre line for variety.
	$flip = 1 === td_cover_rand( $seed, 0, 1 );
	$box  = static function ( float $x1, float $y1, float $x2, float $y2, float $r, int $col ) use ( $img, $flip ): void {
		if ( $flip ) {
			[ $x1, $x2 ] = [ 2340 - $x2, 2340 - $x1 ];
		}
		td_cover_rrect( $img, $x1, $y1, $x2, $y2, $r, $col );
	};
	td_cover_card( $img, $c, 1000, 372, 1340, 714, 18 );
	// Sign.
	td_cover_card( $img, $c, 1050, 296, 1290, 344, 14, 'deep' );
	td_cover_bar( $img, 1170 - 75, 315, 150, 10, $c['ondeep'] );
	// Awning: stripes with a scalloped edge.
	$n  = td_cover_rand( $seed, 0, 1 ) ? 6 : 8;
	$sw = 360 / $n;
	$on = td_cover_rand( $seed, 0, 2 ) ? $c['soft2'] : $c['ink'];
	for ( $i = 0; $i < $n; $i++ ) {
		$x   = 990 + $i * $sw;
		$col = $i % 2 ? $c['canvas'] : $on;
		td_cover_rrect( $img, $x, 372, $x + $sw, 424, 0, $col );
		td_cover_dot( $img, $x + $sw / 2, 424, $sw, $col );
	}
	// Window with shelves, and the door.
	$box( 1028, 494, 1186, 640, 12, $c['soft'] );
	foreach ( [ 542, 592 ] as $sy ) {
		$box( 1040, $sy, 1174, $sy + 4, 0, $c['soft2'] );
		$x = 1046;
		for ( $j = 0, $k = td_cover_rand( $seed, 3, 4 ); $j < $k; $j++ ) {
			$bw = td_cover_rand( $seed, 16, 24 );
			$bh = td_cover_rand( $seed, 18, 30 );
			$box( $x, $sy - $bh, $x + $bw, $sy, 4, ( $j + $sy ) % 3 ? $c['card'] : $c['mid'] );
			$x += $bw + 8;
		}
	}
	$box( 1028, 662, 1138, 670, 4, $c['line'] );
	$box( 1028, 680, 1098, 688, 4, $c['line'] );
	$box( 1212, 494, 1312, 714, 10, $c['deep'] );
	$box( 1226, 508, 1298, 588, 6, $c['deepline'] );
	td_cover_dot( $img, $flip ? 2340 - 1230 : 1230, 610, 10, $c['orange'] );
}

/**
 * Startups / general business: a simple roadmap board.
 *
 * @param GdImage               $img  Image.
 * @param array<int|string,int> $c    Colours.
 * @param int                   $seed Seed.
 */
function td_cover_motif_board( $img, array $c, int $seed ): void {
	td_cover_card( $img, $c, 970, 300, 1370, 712, 24 );
	td_cover_bar( $img, 996, 326, td_cover_rand( $seed, 100, 140 ), 12, $c['ink'] );
	td_cover_dot( $img, 1322, 332, 28, $c['soft2'] );
	td_cover_dot( $img, 1344, 332, 28, $c['ink'] );
	$hot = td_cover_rand( $seed, 0, 2 );
	foreach ( [ 996, 1120, 1244 ] as $col => $x ) {
		td_cover_bar( $img, $x, 370, td_cover_rand( $seed, 40, 64 ), 8, $c['mid'] );
		// Fill the column to the same bottom edge with two or three cards of varied height.
		$cards  = td_cover_rand( $seed, 2, 3 );
		$avail  = 686 - 392 - ( $cards - 1 ) * 12;
		$weight = [];
		for ( $i = 0; $i < $cards; $i++ ) {
			$weight[] = td_cover_rand( $seed, 6, 10 );
		}
		$sum = array_sum( $weight );
		$y   = 392;
		foreach ( $weight as $i => $wt ) {
			$h = $avail * $wt / $sum;
			td_cover_rrect( $img, $x, $y, $x + 100, $y + $h, 12, ( $col + $i ) % 4 ? $c['canvas'] : $c['soft'] );
			td_cover_bar( $img, $x + 12, $y + 14, td_cover_rand( $seed, 50, 74 ), 8, $c['ink'] );
			td_cover_bar( $img, $x + 12, $y + 30, td_cover_rand( $seed, 36, 60 ), 7, $c['soft2'] );
			td_cover_dot( $img, $x + 22, $y + $h - 20, 18, $c['soft2'] );
			if ( $hot === $col && 0 === $i ) {
				td_cover_dot( $img, $x + 84, $y + 18, 10, $c['orange'] );
			}
			$y += $h + 12;
		}
	}
}

// Render.

/**
 * Colour palettes (page background, arch, title, label). Cards inside the arch stay light.
 *
 * @return array<int,array<string,string>>
 */
function td_cover_palettes(): array {
	$canvas = [
		'bg'    => 'F7F5F2',
		'arch'  => 'EFE7DD',
		'title' => '0D0D0D',
		'label' => '6B6560',
	];
	$sand   = [
		'bg'    => 'EFE7DD',
		'arch'  => 'F7F5F2',
		'title' => '0D0D0D',
		'label' => '6B6560',
	];
	$dark   = [
		'bg'    => '1A1715',
		'arch'  => 'EFE7DD',
		'title' => 'FFFFFF',
		'label' => 'C9C4BE',
	];
	return [ $canvas, $sand, $canvas, $sand, $dark ];
}

/**
 * Draw the arch shape, or mask a photo into it.
 *
 * @param GdImage $img   Image (supersampled).
 * @param int     $fill  Arch colour.
 * @param int     $bg    Page background colour (for the photo mask).
 * @param string  $photo Photo path ('' for a flat arch).
 */
function td_cover_arch( $img, int $fill, int $bg, string $photo ): bool {
	[ $x1, $y1, $x2, $y2 ] = TD_COVER_ARCH;
	$r                     = ( $x2 - $x1 ) / 2;
	$br                    = 24;
	$cx                    = $x1 + $r;
	$cy                    = $y1 + $r;
	$src                   = '' !== $photo ? @imagecreatefromjpeg( $photo ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( ! $src ) {
		$pts = [];
		for ( $i = 0; $i <= 64; $i++ ) {
			$a     = M_PI + M_PI * $i / 64;
			$pts[] = $cx + $r * cos( $a );
			$pts[] = $cy + $r * sin( $a );
		}
		for ( $i = 0; $i <= 8; $i++ ) {
			$a     = M_PI / 2 * $i / 8;
			$pts[] = $x2 - $br + $br * cos( $a );
			$pts[] = $y2 - $br + $br * sin( $a );
		}
		for ( $i = 0; $i <= 8; $i++ ) {
			$a     = M_PI / 2 + M_PI / 2 * $i / 8;
			$pts[] = $x1 + $br + $br * cos( $a );
			$pts[] = $y2 - $br + $br * sin( $a );
		}
		td_cover_poly( $img, $pts, $fill );
		return false;
	}
	// Photo: cover-fit into the arch's box, then paint the page colour over the corners.
	$aw    = td_cover_px( $x2 - $x1 );
	$ah    = td_cover_px( $y2 - $y1 );
	$sw    = imagesx( $src );
	$sh    = imagesy( $src );
	$scale = max( $aw / $sw, $ah / $sh );
	$cw    = (int) ceil( $aw / $scale );
	$ch    = (int) ceil( $ah / $scale );
	imagecopyresampled( $img, $src, td_cover_px( $x1 ), td_cover_px( $y1 ), (int) ( ( $sw - $cw ) / 2 ), (int) ( ( $sh - $ch ) / 2 ), $aw, $ah, $cw, $ch );
	imagedestroy( $src );
	$masks = [
		// [ outer corner x, outer corner y, arc centre x, arc centre y, radius, start angle, sweep ].
		[ $x1 - 1, $y1 - 1, $cx, $cy, $r, M_PI, M_PI / 2 ],
		[ $x2 + 1, $y1 - 1, $cx, $cy, $r, 0, -M_PI / 2 ],
		[ $x1 - 1, $y2 + 1, $x1 + $br, $y2 - $br, $br, M_PI, -M_PI / 2 ],
		[ $x2 + 1, $y2 + 1, $x2 - $br, $y2 - $br, $br, 0, M_PI / 2 ],
	];
	foreach ( $masks as $m ) {
		$pts = [ $m[0], $m[1], $m[0], $m[3] ];
		for ( $i = 0; $i <= 32; $i++ ) {
			$a     = $m[5] + $m[6] * $i / 32;
			$pts[] = $m[2] + $m[4] * cos( $a );
			$pts[] = $m[3] + $m[4] * sin( $a );
		}
		td_cover_poly( $img, $pts, $bg );
	}
	return true;
}

/**
 * Draw a cover.
 *
 * @param string $title   Title.
 * @param string $label   Category label.
 * @param string $photo   Photo path ('' for an illustration).
 * @param string $file    Output JPEG path.
 * @param string $motif   Illustration key ('' picks one from the label and title).
 * @param int    $seed    Variation seed (the post ID), picks the palette and the drawing details.
 */
function td_cover_render( string $title, string $label, string $photo, string $file, string $motif = '', int $seed = 0 ): bool {
	$w     = 1600;
	$h     = 900;
	$sans  = __DIR__ . '/fonts/Geist-SemiBold.ttf';
	$serif = __DIR__ . '/fonts/InstrumentSerif-Italic.ttf';
	wp_raise_memory_limit( 'image' ); // The 2× canvas needs ~40 MB.
	$img = imagecreatetruecolor( $w * TD_COVER_SS, $h * TD_COVER_SS );
	if ( ! $img ) {
		return false;
	}
	imagealphablending( $img, true );
	$palettes = td_cover_palettes();
	$pal      = $palettes[ abs( crc32( 'pal' . $seed ) ) % count( $palettes ) ];
	$c        = [
		'bg'       => td_cover_rgb( $img, $pal['bg'] ),
		'archc'    => td_cover_rgb( $img, $pal['arch'] ),
		'title'    => td_cover_rgb( $img, $pal['title'] ),
		'label'    => td_cover_rgb( $img, $pal['label'] ),
		'card'     => td_cover_rgb( $img, 'FFFFFF' ),
		'canvas'   => td_cover_rgb( $img, 'F5F2EE' ),
		'ink'      => td_cover_rgb( $img, '0D0D0D' ),
		'body'     => td_cover_rgb( $img, '57534E' ),
		'mid'      => td_cover_rgb( $img, 'B5AEA7' ),
		'line'     => td_cover_rgb( $img, 'E7E4DF' ),
		'soft'     => td_cover_rgb( $img, 'EFE7DD' ),
		'soft2'    => td_cover_rgb( $img, 'DCD0C1' ),
		'tint'     => td_cover_rgb( $img, 'F6F1EA' ),
		'deep'     => td_cover_rgb( $img, '1A1715' ),
		'deepline' => td_cover_rgb( $img, '3A3532' ),
		'codemid'  => td_cover_rgb( $img, '7D7570' ),
		'ondeep'   => td_cover_rgb( $img, 'C9C4BE' ),
		'orange'   => td_cover_rgb( $img, 'E8470A' ),
		'sh1'      => td_cover_rgb( $img, '3A2A1A', 124 ),
		'sh2'      => td_cover_rgb( $img, '3A2A1A', 118 ),
		'sh3'      => td_cover_rgb( $img, '3A2A1A', 112 ),
	];
	imagefilledrectangle( $img, 0, 0, $w * TD_COVER_SS, $h * TD_COVER_SS, $c['bg'] );

	// The arch (right column): a photo, or a topic illustration on a flat arch.
	$has_photo = td_cover_arch( $img, $c['archc'], $c['bg'], $photo );
	if ( ! $has_photo ) {
		$motifs = td_cover_motifs();
		$motif  = isset( $motifs[ $motif ] ) ? $motif : td_cover_motif_for( $label, $title );
		call_user_func( $motifs[ $motif ], $img, $c, abs( crc32( 'motif' . $seed ) ) );
	}

	// Logo mark, top-left inside the safe area.
	$mark = get_theme_file_path( 'assets/img/brand/techdella-mark-360.png' );
	if ( is_readable( $mark ) ) {
		$m = imagecreatefrompng( $mark );
		if ( $m ) {
			imagecopyresampled( $img, $m, td_cover_px( 200 ), td_cover_px( 150 ), 0, 0, td_cover_px( 44 ), td_cover_px( 44 ), imagesx( $m ), imagesy( $m ) );
			imagedestroy( $m );
		}
	}

	// Title block: label, then up to three lines, centred vertically on the arch.
	$col    = 660.0;
	$layout = td_cover_title_layout( $title, $sans, $serif, $col );
	$size   = $layout['size'];
	$lh     = $size * 1.08;
	$lines  = $layout['lines'];
	$block  = 14 + 34 + ( count( $lines ) - 1 ) * $lh + $size * 0.74;
	$top    = 470 - $block / 2;

	$label = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $label ) : strtoupper( $label );
	td_cover_dot( $img, 205, $top + 8, 10, $c['orange'] );
	$chars  = preg_split( '//u', $label, -1, PREG_SPLIT_NO_EMPTY );
	$prefix = '';
	foreach ( is_array( $chars ) ? $chars : [] as $i => $ch ) {
		// Advance = width of the prefix plus a reference glyph, minus the glyph (ink box ≠ advance).
		$adv = '' === $prefix ? 0.0 : td_cover_tw( 17, $sans, $prefix . 'H' ) - td_cover_tw( 17, $sans, 'H' );
		td_cover_text( $img, 17, 224 + $adv + $i * 1.8, $top + 14, $c['label'], $sans, $ch );
		$prefix .= $ch;
	}

	$space = td_cover_space( $size, $sans );
	$y     = $top + 14 + 34 + $size * 0.74;
	foreach ( $lines as $line ) {
		$x = 200.0;
		foreach ( $line as $tok ) {
			if ( $tok[1] ) {
				td_cover_text( $img, $size * 1.14, $x, $y, $c['title'], $serif, $tok[0] );
				$x += td_cover_tw( $size * 1.14, $serif, $tok[0] ) + $space;
			} else {
				td_cover_text( $img, $size, $x, $y, $c['title'], $sans, $tok[0] );
				$x += td_cover_tw( $size, $sans, $tok[0] ) + $space;
			}
		}
		$y += $lh;
	}

	$out = imagecreatetruecolor( $w, $h );
	if ( ! $out ) {
		imagedestroy( $img );
		return false;
	}
	// Downsample and shrink the whole composition 7% about the centre: the layout fills the
	// centred 1200×630 region exactly, so this gives that crop a margin of its own.
	$k = 0.93;
	imagefilledrectangle( $out, 0, 0, $w, $h, td_cover_rgb( $out, $pal['bg'] ) );
	imagecopyresampled( $out, $img, (int) round( $w * ( 1 - $k ) / 2 ), (int) round( $h * ( 1 - $k ) / 2 ), 0, 0, (int) round( $w * $k ), (int) round( $h * $k ), $w * TD_COVER_SS, $h * TD_COVER_SS );
	imagedestroy( $img );
	imageinterlace( $out, true );
	$ok = imagejpeg( $out, $file, 86 );
	imagedestroy( $out );
	return (bool) $ok;
}

/**
 * Draw (or redraw) one post's generated cover. An editor's own featured image always wins.
 * The title's {{year}} token is drawn as the current year; the hash includes it, so a new year
 * redraws the cover. About one post in four shows a photo in the arch; the rest get the topic
 * illustration. Both choices are fixed per post ID.
 *
 * @param int      $id     Post ID.
 * @param bool     $force  Redraw even when unchanged.
 * @param string[] $photos Photo pool (td_cover_photos()).
 * @return string 'drawn', 'skipped' or 'error'.
 */
function td_cover_generate( int $id, bool $force = false, array $photos = [] ): string {
	$thumb = (int) get_post_thumbnail_id( $id );
	$ours  = (int) get_post_meta( $id, '_td_cover', true );
	$post  = get_post( $id );
	if ( ! $post instanceof WP_Post || ( $thumb && $thumb !== $ours ) || ! function_exists( 'imagettftext' ) ) {
		return 'skipped';
	}
	$photos = $photos ? $photos : td_cover_photos();
	$title  = wp_strip_all_tags( td_year_tokens( $post->post_title ) );
	$label  = td_og_label( $post );
	$motif  = td_cover_motif_for( $label, $title . ' ' . str_replace( '-', ' ', (string) $post->post_name ) );
	$photo  = $photos && 0 === abs( crc32( 'photo' . $id ) ) % 4 ? $photos[ $id % count( $photos ) ] : '';
	$hash   = md5( TD_COVER_VERSION . '|' . $title . '|' . $label . '|' . $motif . '|' . basename( $photo ) );
	if ( $ours && ! $force && get_post_meta( $ours, '_td_cover_hash', true ) === $hash ) {
		return 'skipped';
	}
	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'td-covers';
	wp_mkdir_p( $dir );
	$file = $dir . '/' . $post->post_name . '-' . substr( $hash, 0, 8 ) . '.jpg';
	if ( ! td_cover_render( $title, $label, $photo, $file, $motif, $id ) ) {
		return 'error';
	}
	$att = wp_insert_attachment(
		[
			'post_mime_type' => 'image/jpeg',
			'post_title'     => $title . ' cover',
			'post_status'    => 'inherit',
		],
		$file,
		$id
	);
	if ( is_wp_error( $att ) || ! $att ) {
		return 'error';
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $file ) );
	update_post_meta( $att, '_wp_attachment_image_alt', '' );
	update_post_meta( $att, '_td_cover_hash', $hash );
	set_post_thumbnail( $id, $att );
	update_post_meta( $id, '_td_cover', $att );
	if ( $ours ) {
		wp_delete_attachment( $ours, true );
	}
	return 'drawn';
}

/**
 * Generate covers and set them as featured images where an article has none of its own.
 *
 * ## OPTIONS
 *
 * [--post_type=<types>]
 * : Comma-separated (default: post on the Journal; business and em-glossary on Marketing).
 *
 * [--force]
 * : Redraw existing generated covers.
 *
 * @param string[]             $args  Unused.
 * @param array<string,string> $assoc Options.
 */
function td_cli_covers_generate( array $args, array $assoc ): void {
	if ( ! function_exists( 'imagettftext' ) ) {
		WP_CLI::error( 'PHP GD with FreeType is required.' );
	}
	$types  = ! empty( $assoc['post_type'] ) ? array_map( 'trim', explode( ',', (string) $assoc['post_type'] ) ) : ( in_array( td_site(), [ 'journal', 'studios' ], true ) ? [ 'post' ] : [ 'business', 'em-glossary' ] );
	$types  = array_values( array_filter( $types, 'post_type_exists' ) );
	$force  = (bool) WP_CLI\Utils\get_flag_value( $assoc, 'force', false );
	$photos = td_cover_photos();
	$ids    = $types ? get_posts(
		[
			'post_type'      => $types,
			'post_status'    => [ 'publish', 'draft', 'pending' ],
			'posts_per_page' => -1,
			'fields'         => 'ids',
		]
	) : [];
	$done   = 0;
	foreach ( $ids as $id ) {
		$result = td_cover_generate( (int) $id, $force, $photos );
		if ( 'error' === $result ) {
			WP_CLI::warning( '#' . $id . ': could not draw the cover.' );
		} elseif ( 'drawn' === $result ) {
			++$done;
		}
	}
	WP_CLI::success( sprintf( '%d cover(s) drawn.', $done ) );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'td covers:generate', 'td_cli_covers_generate' );
}
