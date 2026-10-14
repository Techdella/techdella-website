<?php
/**
 * Thank-you pages (§11.2): one per form key used on this site, created by
 * `wp td setup` (td_setup). All noindex. Each fires a consent-aware
 * generate_lead event (pushed to dataLayer; the analytics loader decides
 * whether GA may run). The lead-magnet page shows the PDF link right away
 * when it carries a valid signed ?dl= grant (§13.2).
 *
 * The thank-you CPT (/thank-you/) is registered by the cpt module.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default thank-you page content per key.
 *
 * @return array<string,array{title:string,body:string[]}>
 */
function td_forms_thankyou_content(): array {
	return [
		'contact'       => [
			'title' => __( 'Thanks, we’ve got your message', 'techdella' ),
			'body'  => [ __( 'We reply within one business day with a few times for a call.', 'techdella' ), __( 'While you wait, see how we approach growth on our case studies page.', 'techdella' ) ],
		],
		'audit'         => [
			'title' => __( 'Audit request received', 'techdella' ),
			'body'  => [ __( 'Here’s what happens next. We review your site and confirm the audit is a good fit. You get a short scoping email with the fee and timeline. Then we deliver the audit and walk your team through the roadmap.', 'techdella' ) ],
		],
		'studios_brief' => [
			'title' => __( 'Brief received', 'techdella' ),
			'body'  => [ __( 'We’ll review your brief and reply with a recommended package, or a paid discovery proposal for larger builds.', 'techdella' ) ],
		],
		'newsletter'    => [
			'title' => __( 'You’re subscribed', 'techdella' ),
			'body'  => [ __( 'Check your inbox for a welcome email. If it isn’t there in a few minutes, look in your spam or promotions folder.', 'techdella' ) ],
		],
		'lead_magnet'   => [
			'title' => __( 'Your guide is ready', 'techdella' ),
			'body'  => [ __( 'We’ve also emailed you the download link, so you can come back to it later.', 'techdella' ) ],
		],
		'tool_gate'     => [
			'title' => __( 'Your results are on their way', 'techdella' ),
			'body'  => [ __( 'Check your inbox in a minute or two.', 'techdella' ) ],
		],
		'popup_exit'    => [
			'title' => __( 'It’s on its way', 'techdella' ),
			'body'  => [ __( 'Check your inbox for the link.', 'techdella' ) ],
		],
		'popup_scroll'  => [
			'title' => __( 'It’s on its way', 'techdella' ),
			'body'  => [ __( 'Check your inbox for the link.', 'techdella' ) ],
		],
		'mvp_calc'      => [
			'title' => __( 'Your estimate is on its way', 'techdella' ),
			'body'  => [ __( 'Reply to that email if you want to talk it through with the Studios team.', 'techdella' ) ],
		],
		'guest_post'    => [
			'title' => __( 'Pitch received', 'techdella' ),
			'body'  => [ __( 'An editor will reply within two weeks.', 'techdella' ) ],
		],
	];
}

/**
 * Create the thank-you pages for this site (idempotent; never overwrites edits).
 *
 * @param string $site Site key.
 */
function td_forms_setup_thankyou_pages( string $site ): void {
	if ( ! post_type_exists( 'thank-you' ) ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::warning( 'forms: the thank-you post type is not registered; thank-you pages skipped.' );
		}
		return;
	}
	$content = td_forms_thankyou_content();
	$status  = (string) apply_filters( 'td_forms_thankyou_status', 'publish' );
	foreach ( td_forms_keys_for_site( $site ) as $key ) {
		$slug = td_forms_slug( $key );
		if ( get_page_by_path( $slug, OBJECT, 'thank-you' ) ) {
			continue;
		}
		$c    = $content[ $key ] ?? [
			'title' => __( 'Thank you', 'techdella' ),
			'body'  => [],
		];
		$body = '';
		foreach ( $c['body'] as $p ) {
			$body .= "<!-- wp:paragraph -->\n<p>" . esc_html( $p ) . "</p>\n<!-- /wp:paragraph -->\n\n";
		}
		$id = wp_insert_post(
			[
				'post_type'    => 'thank-you',
				'post_status'  => $status,
				'post_name'    => $slug,
				'post_title'   => $c['title'],
				'post_content' => $body,
				'meta_input'   => [
					'_td_noindex'  => 1,
					'_td_form_key' => $key,
				],
			],
			true
		);
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			if ( is_wp_error( $id ) ) {
				WP_CLI::warning( "forms: could not create /thank-you/{$slug}/: " . $id->get_error_message() );
			} else {
				WP_CLI::log( "forms: created /thank-you/{$slug}/ (#{$id})" );
			}
		}
	}
}
add_action( 'td_setup', 'td_forms_setup_thankyou_pages' );

/**
 * Form key for the current thank-you single ('' elsewhere).
 */
function td_forms_current_thankyou_key(): string {
	if ( ! post_type_exists( 'thank-you' ) || ! is_singular( 'thank-you' ) ) {
		return '';
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return '';
	}
	$key = (string) get_post_meta( $post->ID, '_td_form_key', true );
	if ( '' === $key ) {
		$key = str_replace( '-', '_', $post->post_name );
	}
	return in_array( $key, td_forms_keys(), true ) ? $key : 'other';
}

// All thank-you pages are noindex (§8.3).
add_filter(
	'td_noindex',
	static function ( $noindex ) {
		return ( post_type_exists( 'thank-you' ) && is_singular( 'thank-you' ) ) ? true : $noindex;
	}
);

// Belt and braces: meta robots noindex even if no module consumes td_noindex (wp_robots keys are idempotent).
add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		if ( post_type_exists( 'thank-you' ) && is_singular( 'thank-you' ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['max-image-preview'] );
		}
		return $robots;
	}
);

// generate_lead: pushed to dataLayer only; GA4 is loaded (or not) by the consent-aware analytics loader.
add_action(
	'wp_footer',
	static function () {
		$key = td_forms_current_thankyou_key();
		if ( '' === $key ) {
			return;
		}
		$event = wp_json_encode(
			[
				'event'    => 'generate_lead',
				'form_key' => $key,
			]
		);
		echo '<script>window.dataLayer=window.dataLayer||[];window.dataLayer.push(' . $event . ');</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode of fixed keys.
	}
);

/**
 * Resolve a resource's PDF for download (td_resource CPT, field "pdf").
 *
 * @param string $slug Resource slug.
 * @return array{id:int,url:string,title:string}|null
 */
function td_forms_resource_download( string $slug ): ?array {
	if ( '' === $slug || ! post_type_exists( 'td_resource' ) ) {
		return null;
	}
	$post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'td_resource' );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return null;
	}
	return td_forms_resource_pdf( (int) $post->ID );
}

/**
 * PDF URL of a resource post.
 *
 * @param int $post_id Resource id.
 * @return array{id:int,url:string,title:string}|null
 */
function td_forms_resource_pdf( int $post_id ): ?array {
	$pdf = td_field( 'pdf', $post_id );
	$url = '';
	if ( is_numeric( $pdf ) ) {
		$url = (string) wp_get_attachment_url( (int) $pdf );
	} elseif ( is_array( $pdf ) ) {
		$url = (string) ( $pdf['url'] ?? ( isset( $pdf['ID'] ) ? wp_get_attachment_url( (int) $pdf['ID'] ) : '' ) );
	} elseif ( is_string( $pdf ) ) {
		$url = $pdf;
	}
	if ( '' === $url ) {
		return null;
	}
	return [
		'id'    => $post_id,
		'url'   => esc_url_raw( $url ),
		'title' => get_the_title( $post_id ),
	];
}

/**
 * Download box for the lead-magnet thank-you page: only with a valid signed
 * ?dl= grant (1 hour, issued by the submit route), never guessable (§13.2).
 * Templates may call this directly; the_content gets it automatically.
 *
 * @param string|null $grant Signed grant (default: $_GET['dl']).
 */
function td_forms_thankyou_download_html( ?string $grant = null ): string {
	if ( null === $grant ) {
		$grant = isset( $_GET['dl'] ) ? sanitize_text_field( wp_unslash( $_GET['dl'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- signed grant.
	}
	$id  = '' !== $grant ? td_forms_token()->open( $grant, time() ) : null;
	$pdf = ( null !== $id && ctype_digit( $id ) ) ? td_forms_resource_pdf( (int) $id ) : null;
	if ( ! $pdf ) {
		return '';
	}
	$box  = '<div class="td-form__download-box">';
	$box .= '<p><strong>' . esc_html( $pdf['title'] ) . '</strong></p>';
	$box .= '<p><a class="td-btn td-btn--primary" href="' . esc_url( $pdf['url'] ) . '" download>' . esc_html__( 'Download the PDF', 'techdella' ) . '<span class="td-btn__arrow">' . td_icon( 'arrow-right' ) . '</span></a></p>';
	return $box . '</div>';
}

add_filter(
	'the_content',
	static function ( $content ) {
		if ( 'lead_magnet' !== td_forms_current_thankyou_key() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		return td_forms_thankyou_download_html() . $content;
	},
	20
);

/**
 * Lead-magnet PDFs (uploads/td-files/<random>/) are never indexed and never listed (§13.2):
 * an .htaccess there sends X-Robots-Tag: noindex (Apache and LiteSpeed read it), and an empty
 * index.html stops directory listings. Written on td setup (every deploy) and when the importer
 * uploads a PDF.
 */
function td_forms_protect_private_files(): void {
	$dir = trailingslashit( (string) wp_upload_dir( null, false )['basedir'] ) . 'td-files';
	if ( ! wp_mkdir_p( $dir ) ) {
		return;
	}
	$rules = "# TechDella: lead-magnet PDFs are not indexed or listed (written by wp td setup).\n"
		. "<IfModule mod_headers.c>\n\tHeader set X-Robots-Tag \"noindex, nofollow\"\n</IfModule>\n";
	$file  = $dir . '/.htaccess';
	if ( ! is_readable( $file ) || file_get_contents( $file ) !== $rules ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		file_put_contents( $file, $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	if ( ! file_exists( $dir . '/index.html' ) ) {
		file_put_contents( $dir . '/index.html', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
}
add_action( 'td_setup', 'td_forms_protect_private_files' );
