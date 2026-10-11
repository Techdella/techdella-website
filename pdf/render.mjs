#!/usr/bin/env node
/**
 * Render the lead-magnet PDFs (§13.3).
 *
 *   npm run pdf                      # render every pdf/templates/*.html → pdf/out/<slug>.pdf
 *   npm run pdf -- --release         # same, but FAIL if any [CONFIRM…] / [PRICE…] token remains
 *   npm run pdf -- --only=email-deliverability-checklist
 *   npm run pdf -- --previews        # also write PNG previews (page 1 + one inner page) to previews/pdf/ (needs pdftoppm)
 *
 * Fails (exit 1) when any PDF is ≥ 3 MB (§13.3), when a template has no <title>, or (with --release)
 * when placeholder tokens remain. Writes pdf/out/report.json: pages, bytes, placeholder counts.
 * Uses the repo's Playwright Chromium. Never runs `playwright install`.
 */
import { chromium } from '@playwright/test';
import { readdir, mkdir, writeFile, stat } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const ROOT = path.dirname( fileURLToPath( import.meta.url ) );
const TEMPLATES = path.join( ROOT, 'templates' );
const OUT = path.join( ROOT, 'out' );
const PREVIEWS = path.join( ROOT, '..', 'previews', 'pdf' );
const MAX_BYTES = 3 * 1024 * 1024;

const args = process.argv.slice( 2 );
const release = args.includes( '--release' );
const previews = args.includes( '--previews' );
const only = ( args.find( ( a ) => a.startsWith( '--only=' ) ) || '' ).slice( 7 );

/** Count PDF page objects (/Type /Page, not /Pages). */
function countPages( buf ) {
	const m = buf.toString( 'latin1' ).match( /\/Type\s*\/Page(?![a-z])/g );
	return m ? m.length : 0;
}

/** Read the /Title from the PDF Info dictionary (Chromium writes it as a UTF-16BE or literal string). */
function pdfTitle( buf ) {
	const s = buf.toString( 'latin1' );
	const hex = s.match( /\/Title\s*<([0-9A-Fa-f]+)>/ );
	if ( hex ) {
		const b = Buffer.from( hex[ 1 ], 'hex' );
		if ( b[ 0 ] === 0xfe && b[ 1 ] === 0xff ) {
			return b.subarray( 2 ).swap16().toString( 'utf16le' );
		}
		return b.toString( 'latin1' );
	}
	const lit = s.match( /\/Title\s*\(((?:\\.|[^\\)])*)\)/ );
	return lit ? lit[ 1 ] : '';
}

const files = ( await readdir( TEMPLATES ) )
	.filter( ( f ) => f.endsWith( '.html' ) && ! f.startsWith( '_' ) )
	.filter( ( f ) => ! only || f === `${ only }.html` )
	.sort();

if ( ! files.length ) {
	console.error( 'No templates found in pdf/templates/.' );
	process.exit( 1 );
}

await mkdir( OUT, { recursive: true } );
const browser = await chromium.launch();
const context = await browser.newContext();
const report = { generated_at: new Date().toISOString(), release, pdfs: [] };
const errors = [];

for ( const file of files ) {
	const slug = file.replace( /\.html$/, '' );
	const page = await context.newPage();
	await page.goto( pathToFileURL( path.join( TEMPLATES, file ) ).href, { waitUntil: 'load' } );
	await page.emulateMedia( { media: 'print' } );
	await page.evaluate( () => document.fonts.ready );

	const info = await page.evaluate( () => {
		const text = document.body.innerText;
		return {
			title: document.title,
			id: document.querySelector( 'meta[name="td-pdf-id"]' )?.content || '',
			author: document.querySelector( 'meta[name="author"]' )?.content || '',
			lang: document.documentElement.lang,
			confirm: ( text.match( /\[CONFIRM/g ) || [] ).length,
			price: ( text.match( /\[PRICE|\[MINIMUM SPEND/g ) || [] ).length,
			fontsOk: [ ...document.fonts ].filter( ( f ) => f.status === 'loaded' ).map( ( f ) => `${ f.family } ${ f.weight } ${ f.style }` ),
			images: [ ...document.images ].filter( ( i ) => ! i.complete || ! i.naturalWidth ).map( ( i ) => i.src ),
			h1: document.querySelectorAll( 'h1' ).length,
		};
	} );

	if ( ! info.title ) {
		errors.push( `${ slug }: missing <title>` );
	}
	if ( info.h1 !== 1 ) {
		errors.push( `${ slug }: expected exactly one <h1>, found ${ info.h1 }` );
	}
	if ( info.images.length ) {
		errors.push( `${ slug }: images failed to load: ${ info.images.join( ', ' ) }` );
	}

	const target = path.join( OUT, `${ slug }.pdf` );
	const buf = await page.pdf( {
		path: target,
		format: 'A4',
		printBackground: true,
		preferCSSPageSize: true,
		displayHeaderFooter: false,
		tagged: true,
		outline: true,
	} );
	await page.close();

	const { size } = await stat( target );
	const row = {
		id: info.id,
		slug,
		title: info.title,
		pdf_title: pdfTitle( buf ),
		pages: countPages( buf ),
		bytes: size,
		mb: Math.round( ( size / 1048576 ) * 100 ) / 100,
		confirm_tokens: info.confirm,
		price_tokens: info.price,
		fonts_loaded: info.fontsOk.length,
		file: path.relative( path.join( ROOT, '..' ), target ),
	};
	report.pdfs.push( row );

	if ( size >= MAX_BYTES ) {
		errors.push( `${ slug }: ${ row.mb } MB is over the 3 MB limit (§13.3)` );
	}
	if ( release && ( info.confirm || info.price ) ) {
		errors.push( `${ slug }: ${ info.confirm } [CONFIRM] and ${ info.price } [PRICE] tokens remain (--release)` );
	}

	if ( previews ) {
		await mkdir( PREVIEWS, { recursive: true } );
		const inner = Math.min( 3, row.pages );
		try {
			for ( const [ n, name ] of [ [ 1, 'p1' ], [ inner, `p${ inner }` ] ] ) {
				execFileSync( 'pdftoppm', [ '-f', String( n ), '-l', String( n ), '-r', '70', '-png', '-singlefile', target, path.join( PREVIEWS, `${ slug }-${ name }` ) ] );
			}
		} catch ( e ) {
			console.warn( `previews skipped (pdftoppm unavailable?): ${ e.message }` );
		}
	}

	console.log(
		`${ ( info.id || '' ).padEnd( 6 ) } ${ slug.padEnd( 42 ) } ${ String( row.pages ).padStart( 3 ) } pp  ${ String( row.mb ).padStart( 5 ) } MB  ${ info.confirm } CONFIRM  ${ info.price } PRICE`
	);
}

await browser.close();
report.errors = errors;
await writeFile( path.join( OUT, 'report.json' ), JSON.stringify( report, null, 2 ) + '\n' );

const totalConfirm = report.pdfs.reduce( ( n, r ) => n + r.confirm_tokens + r.price_tokens, 0 );
console.log( `\n${ report.pdfs.length } PDF(s) → pdf/out/ · ${ totalConfirm } placeholder token(s) remaining · report: pdf/out/report.json` );
if ( errors.length ) {
	console.error( '\nFAILED:\n- ' + errors.join( '\n- ' ) );
	process.exit( 1 );
}
