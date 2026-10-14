<?php
/**
 * A small, dependency-free PDF writer for quotes (A4, Helvetica, text, filled boxes, lines).
 * Hostinger disables proc_open, so no external PDF tool; the standard 14 fonts need no embedding.
 * Text is WinAnsi: characters outside it (e.g. ₦) must be spelled out by the caller ("NGN").
 *
 * @package techdella-core
 */

namespace TechDella\Core\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Builds a single- or multi-page PDF from simple drawing calls. Coordinates are in points from the
 * top-left corner (converted to PDF's bottom-left origin on output).
 */
final class QuotePdf {

	public const W = 595.28;
	public const H = 841.89;

	/**
	 * Content stream per page.
	 *
	 * @var string[]
	 */
	private array $pages = [];

	/**
	 * Document title (metadata).
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * New document with one empty page.
	 *
	 * @param string $title Document title (metadata).
	 */
	public function __construct( string $title ) {
		$this->title = $title;
		$this->add_page();
	}

	/**
	 * Start a new page.
	 */
	public function add_page(): void {
		$this->pages[] = '';
	}

	/**
	 * Append to the current page's content stream.
	 *
	 * @param string $op PDF operators.
	 */
	private function out( string $op ): void {
		$this->pages[ count( $this->pages ) - 1 ] .= $op . "\n";
	}

	/**
	 * Colour from #rrggbb as "r g b".
	 *
	 * @param string $hex Hex colour.
	 */
	private static function rgb( string $hex ): string {
		$hex = ltrim( $hex, '#' );
		return sprintf( '%.3F %.3F %.3F', hexdec( substr( $hex, 0, 2 ) ) / 255, hexdec( substr( $hex, 2, 2 ) ) / 255, hexdec( substr( $hex, 4, 2 ) ) / 255 );
	}

	/**
	 * WinAnsi-safe, escaped PDF string.
	 *
	 * @param string $text UTF-8 text.
	 */
	public static function esc( string $text ): string {
		$map  = [
			'’' => "'",
			'‘' => "'",
			'“' => '"',
			'”' => '"',
			'–' => '-',
			'—' => '-',
			'…' => '...',
			'₦' => 'NGN ',
			' ' => ' ',
		];
		$text = strtr( $text, $map );
		$conv = function_exists( 'iconv' ) ? iconv( 'UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text ) : false;
		$text = false === $conv ? (string) preg_replace( '/[^\x20-\x7E]/', '?', $text ) : $conv;
		return strtr(
			$text,
			[
				'\\' => '\\\\',
				'('  => '\\(',
				')'  => '\\)',
				"\r" => '',
				"\n" => ' ',
			]
		);
	}

	/**
	 * Approximate Helvetica width of a string in points (good enough for wrapping and right-aligning).
	 *
	 * @param string $text Text.
	 * @param float  $size Font size.
	 * @param bool   $bold Bold.
	 */
	public static function width( string $text, float $size, bool $bold = false ): float {
		$w = 0.0;
		foreach ( str_split( self::esc( $text ) ) as $c ) {
			if ( ' ' === $c ) {
				$w += 0.278;
			} elseif ( ctype_upper( $c ) ) {
				$w += $bold ? 0.70 : 0.667;
			} elseif ( ctype_digit( $c ) ) {
				$w += 0.556;
			} elseif ( in_array( $c, [ 'i', 'l', 'j', 't', 'f', 'r', '.', ',', ':', ';', "'", '|', '!' ], true ) ) {
				$w += $bold ? 0.33 : 0.28;
			} elseif ( in_array( $c, [ 'm', 'w', 'M', 'W' ], true ) ) {
				$w += 0.833;
			} else {
				$w += $bold ? 0.611 : 0.556;
			}
		}
		return $w * $size;
	}

	/**
	 * Draw text.
	 *
	 * @param float  $x     X.
	 * @param float  $y     Baseline Y (from the top).
	 * @param string $text  Text.
	 * @param float  $size  Size.
	 * @param string $color Hex colour.
	 * @param bool   $bold  Bold.
	 * @param string $align left|right.
	 */
	public function text( float $x, float $y, string $text, float $size = 10, string $color = '#0D0D0D', bool $bold = false, string $align = 'left' ): void {
		if ( 'right' === $align ) {
			$x -= self::width( $text, $size, $bold );
		}
		$this->out( sprintf( 'BT /%s %.2F Tf %s rg %.2F %.2F Td (%s) Tj ET', $bold ? 'F2' : 'F1', $size, self::rgb( $color ), $x, self::H - $y, self::esc( $text ) ) );
	}

	/**
	 * Wrapped paragraph; returns the Y below the last line.
	 *
	 * @param float  $x       X.
	 * @param float  $y       First baseline.
	 * @param float  $width   Max width.
	 * @param string $text    Text.
	 * @param float  $size    Size.
	 * @param string $color   Colour.
	 * @param float  $leading Line height.
	 * @param bool   $bold    Bold.
	 */
	public function para( float $x, float $y, float $width, string $text, float $size = 10, string $color = '#57534E', float $leading = 0, bool $bold = false ): float {
		$leading = $leading > 0 ? $leading : $size * 1.45;
		$line    = '';
		foreach ( preg_split( '/\s+/', trim( $text ) ) as $word ) {
			$try = '' === $line ? $word : $line . ' ' . $word;
			if ( '' !== $line && self::width( $try, $size, $bold ) > $width ) {
				$this->text( $x, $y, $line, $size, $color, $bold );
				$y   += $leading;
				$line = $word;
			} else {
				$line = $try;
			}
		}
		if ( '' !== $line ) {
			$this->text( $x, $y, $line, $size, $color, $bold );
			$y += $leading;
		}
		return $y;
	}

	/**
	 * Filled rectangle (Y from the top).
	 *
	 * @param float  $x     X.
	 * @param float  $y     Top.
	 * @param float  $w     Width.
	 * @param float  $h     Height.
	 * @param string $color Fill colour.
	 */
	public function rect( float $x, float $y, float $w, float $h, string $color ): void {
		$this->out( sprintf( '%s rg %.2F %.2F %.2F %.2F re f', self::rgb( $color ), $x, self::H - $y - $h, $w, $h ) );
	}

	/**
	 * Horizontal rule.
	 *
	 * @param float  $x1    From X.
	 * @param float  $x2    To X.
	 * @param float  $y     Y.
	 * @param string $color Colour.
	 * @param float  $width Line width.
	 */
	public function line( float $x1, float $x2, float $y, string $color = '#E7E4DF', float $width = 0.8 ): void {
		$this->out( sprintf( '%s RG %.2F w %.2F %.2F m %.2F %.2F l S', self::rgb( $color ), $width, $x1, self::H - $y, $x2, self::H - $y ) );
	}

	/**
	 * The PDF bytes.
	 */
	public function output(): string {
		$objs   = [];
		$objs[] = '<< /Type /Catalog /Pages 2 0 R >>';
		$kids   = [];
		$n      = count( $this->pages );
		for ( $i = 0; $i < $n; $i++ ) {
			$kids[] = ( 6 + $i * 2 ) . ' 0 R';
		}
		$objs[] = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . '] /Count ' . $n . ' >>';
		$objs[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
		$objs[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
		$objs[] = '<< /Title (' . self::esc( $this->title ) . ') /Producer (TechDella) /CreationDate (D:' . gmdate( 'YmdHis' ) . 'Z) >>';
		foreach ( $this->pages as $i => $stream ) {
			$content_id = 7 + $i * 2;
			$objs[]     = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::W . ' ' . self::H . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $content_id . ' 0 R >>';
			$objs[]     = '<< /Length ' . strlen( $stream ) . " >>\nstream\n" . $stream . 'endstream';
		}
		$pdf     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offsets = [];
		foreach ( $objs as $i => $obj ) {
			$offsets[] = strlen( $pdf );
			$pdf      .= ( $i + 1 ) . " 0 obj\n" . $obj . "\nendobj\n";
		}
		$xref = strlen( $pdf );
		$pdf .= 'xref' . "\n0 " . ( count( $objs ) + 1 ) . "\n0000000000 65535 f \n";
		foreach ( $offsets as $off ) {
			$pdf .= sprintf( "%010d 00000 n \n", $off );
		}
		$pdf .= 'trailer' . "\n<< /Size " . ( count( $objs ) + 1 ) . " /Root 1 0 R /Info 5 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
		return $pdf;
	}
}
