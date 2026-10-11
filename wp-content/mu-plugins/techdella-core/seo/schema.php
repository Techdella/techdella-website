<?php
/**
 * Structured data (§8.2): one <script type="application/ld+json"> per page
 * holding a single @graph. Each node is emitted once, linked by @id.
 *
 * All pages: Organization (@id on the Marketing domain for all three sites),
 * WebSite (+ SearchAction on Journal), WebPage, BreadcrumbList. Per template:
 * Service, Article (+ Review), BlogPosting, DefinedTerm, SoftwareApplication,
 * DigitalDocument, AboutPage + Person, VideoObject. Never FAQPage, HowTo or
 * AggregateRating (Graph strips them).
 *
 * Printed in wp_footer so blocks rendered in the content (testimonial →
 * Review) can add nodes with td_schema_add_node().
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Seo\Graph;

/**
 * The page's graph (nodes added by templates and blocks).
 */
function td_schema(): Graph {
	static $graph = null;
	if ( null === $graph ) {
		$graph = new Graph();
	}
	return $graph;
}

/**
 * Add a node to the page's single @graph. A node with an existing @id is
 * merged (first one wins on conflicts). Forbidden types are dropped.
 *
 * @param array<string,mixed> $node JSON-LD node (with @type, ideally @id).
 */
function td_schema_add_node( array $node ): void {
	td_schema()->add( $node );
}

/**
 * Add a VideoObject for a self-hosted video shown on this page.
 *
 * @param array<string,mixed> $video name, description, thumbnailUrl, uploadDate (ISO 8601),
 *                                   duration (ISO 8601, e.g. PT15S), contentUrl; optional @id.
 */
function td_schema_add_video( array $video ): void {
	if ( empty( $video['name'] ) || empty( $video['thumbnailUrl'] ) || empty( $video['uploadDate'] ) || empty( $video['contentUrl'] ) ) {
		return;
	}
	$base = is_singular() ? (string) get_permalink() : td_schema_page_url();
	td_schema_add_node(
		array_merge(
			[
				'@type' => 'VideoObject',
				'@id'   => $base . '#video-' . substr( md5( (string) $video['contentUrl'] ), 0, 8 ),
			],
			$video
		)
	);
}

/**
 * The Organization @id (Marketing domain, same on all three sites).
 */
function td_schema_org_id(): string {
	return td_url( 'marketing', '/#org' );
}

/**
 * A stable Person @id (Marketing About page anchor), same on all sites.
 *
 * @param string $name Person name.
 */
function td_schema_person_id( string $name ): string {
	return td_url( 'marketing', '/about-us/#' . sanitize_title( $name ) );
}

/**
 * The current page URL for @ids: the canonical, or the clean current URL.
 */
function td_schema_page_url(): string {
	$url = td_canonical_url();
	if ( '' !== $url ) {
		return $url;
	}
	if ( is_search() ) {
		return add_query_arg( 's', rawurlencode( get_search_query( false ) ), home_url( '/' ) );
	}
	return home_url( '/' );
}

/**
 * Image URL from an ACF image value (ID, array, URL) or a theme manifest key.
 *
 * @param mixed $value Field value.
 */
function td_schema_image_url( $value ): string {
	if ( is_array( $value ) ) {
		$value = $value['url'] ?? ( $value['ID'] ?? ( $value['id'] ?? '' ) );
	}
	if ( is_numeric( $value ) && (int) $value > 0 ) {
		$url = wp_get_attachment_image_url( (int) $value, 'full' );
		return $url ? $url : '';
	}
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( '' === $value ) {
		return '';
	}
	if ( preg_match( '#^https?://#', $value ) ) {
		return $value;
	}
	if ( function_exists( 'td_image_manifest' ) ) {
		$entry = td_image_manifest()[ $value ] ?? null;
		if ( is_array( $entry ) && ! empty( $entry['widths'] ) ) {
			$widths = array_map( 'intval', (array) $entry['widths'] );
			return get_theme_file_uri( 'assets/img/' . $value . '-' . max( $widths ) . '.' . ( $entry['fallback'] ?? 'jpg' ) );
		}
	}
	return '';
}

/**
 * ISO 8601 date (Y-m-d) from a field value (Ymd, Y-m-d, d/m/Y…), or ''.
 *
 * @param mixed $value Date.
 */
function td_schema_date( $value ): string {
	$value = is_string( $value ) || is_numeric( $value ) ? trim( (string) $value ) : '';
	if ( '' === $value ) {
		return '';
	}
	if ( preg_match( '/^\d{8}$/', $value ) ) {
		$value = substr( $value, 0, 4 ) . '-' . substr( $value, 4, 2 ) . '-' . substr( $value, 6, 2 );
	}
	$time = strtotime( $value );
	return $time ? gmdate( 'Y-m-d', $time ) : '';
}

/**
 * User ID from an ACF user field value.
 *
 * @param mixed $value Field value.
 */
function td_schema_user_id( $value ): int {
	if ( $value instanceof WP_User ) {
		return (int) $value->ID;
	}
	if ( is_array( $value ) ) {
		$value = $value['ID'] ?? ( $value[0] ?? 0 );
		if ( $value instanceof WP_User ) {
			return (int) $value->ID;
		}
	}
	return is_numeric( $value ) ? (int) $value : 0;
}

/**
 * Country/Place nodes for market names.
 *
 * @param string[] $markets Market names.
 * @return array<int,array<string,string>>
 */
function td_schema_places( array $markets ): array {
	$countries = [ 'Nigeria', 'United States', 'USA', 'United Kingdom', 'UK', 'Canada', 'United Arab Emirates', 'UAE', 'Mexico', 'South Africa', 'Lebanon', 'Germany', 'India', 'Pakistan', 'Ghana', 'Kenya' ];
	$out       = [];
	foreach ( $markets as $market ) {
		$market = td_seo_text( (string) $market );
		if ( '' === $market || false !== strpos( $market, '[' ) ) {
			continue;
		}
		$out[] = [
			'@type' => in_array( $market, $countries, true ) ? 'Country' : 'Place',
			'name'  => $market,
		];
	}
	return $out;
}

/**
 * Person node for a td_team profile.
 *
 * @param WP_Post $team td_team post.
 * @return array<string,mixed>
 */
function td_schema_person_from_team( WP_Post $team ): array {
	$name     = td_seo_text( $team->post_title );
	$linkedin = td_field( 'linkedin', $team->ID );
	$photo    = td_schema_image_url( td_field( 'photo', $team->ID ) );
	if ( '' === $photo && has_post_thumbnail( $team ) ) {
		$photo = (string) wp_get_attachment_image_url( (int) get_post_thumbnail_id( $team ), 'full' );
	}
	$user_id = td_seo_team_user_id( $team );
	$url     = $user_id && td_seo_author_indexable( $user_id ) ? get_author_posts_url( $user_id ) : td_url( 'marketing', '/about-us/' );
	$bio     = td_field( 'bio', $team->ID );
	return [
		'@type'       => 'Person',
		'@id'         => td_schema_person_id( $name ),
		'name'        => $name,
		'jobTitle'    => td_seo_text( (string) td_field( 'role', $team->ID ) ),
		'description' => is_string( $bio ) ? td_seo_text( $bio ) : '',
		'image'       => $photo,
		'url'         => $url,
		'sameAs'      => is_string( $linkedin ) && wp_http_validate_url( $linkedin ) ? [ $linkedin ] : [],
		'worksFor'    => [ '@id' => td_schema_org_id() ],
	];
}

/**
 * Person node for a WP user (via their td_team profile when one exists).
 *
 * @param int $user_id User ID.
 * @return array<string,mixed>
 */
function td_schema_person_from_user( int $user_id ): array {
	$team = td_seo_team_post_for_user( $user_id );
	if ( $team ) {
		return td_schema_person_from_team( $team );
	}
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return [];
	}
	$home = untrailingslashit( home_url() );
	$same = array_values(
		array_filter(
			[ (string) get_user_meta( $user_id, 'linkedin', true ), (string) $user->user_url ],
			static fn( $url ) => '' !== $url && untrailingslashit( $url ) !== $home && wp_http_validate_url( $url )
		)
	);
	return [
		'@type'  => 'Person',
		'@id'    => get_author_posts_url( $user_id ) . '#person',
		'name'   => $user->display_name,
		'url'    => td_seo_author_indexable( $user_id ) ? get_author_posts_url( $user_id ) : '',
		'sameAs' => $same,
	];
}

/**
 * The founder (§2.4), enriched from her td_team profile when present.
 *
 * @return array<string,mixed>
 */
function td_schema_founder(): array {
	$name = 'Joanna Okedara-Kalu';
	$team = td_seo_team_post_by_name( $name );
	if ( $team ) {
		return td_schema_person_from_team( $team );
	}
	return [
		'@type'    => 'Person',
		'@id'      => td_schema_person_id( $name ),
		'name'     => $name,
		'jobTitle' => 'Founder & CEO',
		'worksFor' => [ '@id' => td_schema_org_id() ],
	];
}

/**
 * The Organization node.
 *
 * @return array<string,mixed>
 */
function td_schema_organization(): array {
	$logo_path = (string) wp_parse_url( td_og_fallback_image(), PHP_URL_PATH );
	$same_as   = get_option( 'td_social_profiles', [] );
	$same_as   = array_values(
		array_filter(
			array_map( 'trim', array_map( 'strval', is_array( $same_as ) ? $same_as : [] ) ),
			static fn( $url ) => '' !== $url && false === strpos( $url, '[' ) && wp_http_validate_url( $url )
		)
	);
	return [
		'@type'       => 'Organization',
		'@id'         => td_schema_org_id(),
		'name'        => 'TechDella',
		'legalName'   => 'Tech Della Solutions LTD',
		'url'         => td_url( 'marketing', '/' ),
		'email'       => 'hello@techdella.com',
		// Registered office (owner, 11 Oct 2026). An Organization address, not LocalBusiness (§8.2).
		'address'     => [
			'@type'           => 'PostalAddress',
			'streetAddress'   => 'Office 7B, Yetmob Plaza, Elewure, off Akala Express, Oluyole',
			'addressLocality' => 'Ibadan',
			'addressRegion'   => 'Oyo State',
			'addressCountry'  => 'NG',
		],
		'description' => 'B2B growth marketing agency: search (SEO and AI search optimization), paid ads, lead generation, email marketing and AI marketing automation, with TechDella Studios for product and brand builds.',
		'logo'        => [
			'@type'  => 'ImageObject',
			'@id'    => td_url( 'marketing', '/#logo' ),
			'url'    => td_url( 'marketing', $logo_path ),
			'width'  => 1200,
			'height' => 272,
		],
		'founder'     => [ '@id' => td_schema_person_id( 'Joanna Okedara-Kalu' ) ],
		'sameAs'      => $same_as,
	];
}

/**
 * The WebSite node (SearchAction on Journal only).
 *
 * @return array<string,mixed>
 */
function td_schema_website(): array {
	$names = [
		'marketing' => 'TechDella',
		'journal'   => 'TechDella Journal',
		'studios'   => 'TechDella Studios',
	];
	$node  = [
		'@type'      => 'WebSite',
		'@id'        => home_url( '/#website' ),
		'url'        => home_url( '/' ),
		'name'       => $names[ td_site() ],
		'publisher'  => [ '@id' => td_schema_org_id() ],
		'inLanguage' => 'en-US',
	];
	if ( td_is_site( 'journal' ) ) {
		$node['potentialAction'] = [
			'@type'       => 'SearchAction',
			'target'      => [
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			],
			'query-input' => 'required name=search_term_string',
		];
	}
	return $node;
}

/**
 * The WebPage type for the current request.
 */
function td_schema_webpage_type(): string {
	if ( is_front_page() ) {
		return 'WebPage';
	}
	if ( is_search() ) {
		return 'SearchResultsPage';
	}
	if ( is_page() ) {
		$slug = (string) get_post_field( 'post_name', (int) get_queried_object_id() );
		if ( in_array( $slug, [ 'about-us', 'about' ], true ) ) {
			return 'AboutPage';
		}
		if ( 'book-a-discovery-call' === $slug ) {
			return 'ContactPage';
		}
	}
	if ( is_home() || is_archive() ) {
		return 'CollectionPage';
	}
	return 'WebPage';
}

/**
 * Nodes for the current template (main entity first).
 *
 * @param string $page_id WebPage @id.
 * @return array<int,array<string,mixed>>
 */
function td_schema_template_nodes( string $page_id ): array {
	$org   = [ '@id' => td_schema_org_id() ];
	$nodes = [];

	if ( is_post_type_archive( 'em-glossary' ) ) {
		$nodes[] = td_schema_glossary_set();
		return $nodes;
	}
	if ( ! is_singular() ) {
		return $nodes;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return $nodes;
	}
	$id        = (int) $post->ID;
	$url       = (string) get_permalink( $post );
	$title     = td_seo_text( get_the_title( $post ) );
	$desc      = td_seo_meta_description( $id );
	$image     = td_og_image_url( $id );
	$published = (string) get_post_time( 'c', true, $post );
	$modified  = (string) get_post_modified_time( 'c', true, $post );
	$kw        = td_field( 'primary_kw', $id );
	$kw        = is_string( $kw ) ? td_seo_text( $kw ) : '';
	$main      = [ 'mainEntityOfPage' => [ '@id' => $page_id ] ];

	switch ( $post->post_type ) {
		case 'service':
		case 'studio_service':
		case 'solution':
		case 'location':
			$service = [
				'@type'       => 'Service',
				'@id'         => $url . '#service',
				'name'        => $title,
				'serviceType' => '' !== $kw ? $kw : $title,
				'description' => $desc,
				'url'         => $url,
				'image'       => $image,
				'provider'    => $org,
				'areaServed'  => td_schema_places( td_seo_markets() ),
			] + $main;
			if ( 'solution' === $post->post_type ) {
				$terms    = get_the_terms( $post, 'td_industry' );
				$industry = is_array( $terms ) && $terms ? $terms[0]->name : td_field( 'industry', $id );
				$industry = $industry instanceof WP_Term ? $industry->name : ( is_string( $industry ) && ! is_numeric( $industry ) ? $industry : $title );

				$service['audience'] = [
					'@type'        => 'BusinessAudience',
					'audienceType' => td_seo_text( $industry ),
				];
			}
			if ( 'location' === $post->post_type ) {
				$market                = td_field( 'market', $id );
				$market                = is_string( $market ) && '' !== trim( $market ) ? $market : $title;
				$service['areaServed'] = td_schema_places( [ $market ] );
				$nap                   = td_field( 'nap', $id );
				// LocalBusiness only for a real staffed address (§8.2) — never a placeholder.
				if ( is_string( $nap ) && '' !== trim( $nap ) && ! preg_match( '/\[[A-Z ]+/', $nap ) ) {
					$nodes[] = [
						'@type'              => 'LocalBusiness',
						'@id'                => $url . '#localbusiness',
						'name'               => 'TechDella ' . td_seo_text( $market ),
						'parentOrganization' => $org,
						'url'                => $url,
						'image'              => td_og_fallback_image(),
						'address'            => [
							'@type'         => 'PostalAddress',
							'streetAddress' => td_seo_text( $nap ),
						],
					];
				}
			}
			$price = td_field( 'price', $id );
			if ( is_numeric( $price ) && (float) $price > 0 ) {
				$currency          = td_field( 'price_currency', $id );
				$service['offers'] = [
					'@type'         => 'Offer',
					'price'         => (string) $price,
					'priceCurrency' => is_string( $currency ) && preg_match( '/^[A-Z]{3}$/', $currency ) ? $currency : 'USD',
					'url'           => $url,
				];
			}
			array_unshift( $nodes, $service );
			break;

		case 'case_study':
			$client  = td_field( 'client', $id );
			$client  = is_string( $client ) ? td_seo_text( $client ) : '';
			$country = td_field( 'country', $id );
			$article = [
				'@type'         => 'Article',
				'@id'           => $url . '#article',
				'headline'      => $title,
				'description'   => $desc,
				'image'         => $image,
				'datePublished' => $published,
				'dateModified'  => $modified,
				'author'        => $org,
				'publisher'     => $org,
				'inLanguage'    => 'en-US',
				'about'         => '' !== $client ? [
					'@type'   => 'Organization',
					'name'    => $client,
					'address' => is_string( $country ) && '' !== trim( $country ) ? [
						'@type'          => 'PostalAddress',
						'addressCountry' => td_seo_text( $country ),
					] : null,
				] : null,
			] + $main;
			$nodes[] = $article;
			$quote   = td_field( 'quote', $id );
			$author  = td_field( 'quote_author', $id );
			if ( is_string( $quote ) && '' !== trim( $quote ) && is_string( $author ) && '' !== trim( $author ) && td_field( 'permission_confirmed', $id ) ) {
				$role    = td_field( 'quote_role', $id );
				$nodes[] = [
					'@type'        => 'Review',
					'@id'          => $url . '#review',
					'itemReviewed' => $org,
					'reviewBody'   => td_seo_text( $quote ),
					'author'       => [
						'@type'    => 'Person',
						'name'     => td_seo_text( $author ),
						'jobTitle' => is_string( $role ) ? td_seo_text( $role ) : '',
						'worksFor' => '' !== $client ? [
							'@type' => 'Organization',
							'name'  => $client,
						] : null,
					],
				];
			}
			break;

		case 'post':
			$author_node = td_schema_person_from_user( (int) $post->post_author );
			if ( $author_node ) {
				$nodes[] = $author_node;
			}
			$text     = td_seo_text( strip_shortcodes( $post->post_content ) );
			$words    = (int) preg_match_all( '/[\p{L}\p{N}][\p{L}\p{N}\'’-]*/u', $text );
			$sections = array_map( static fn( $c ) => td_seo_text( $c->name ), get_the_category( $id ) );
			array_unshift(
				$nodes,
				[
					'@type'          => 'BlogPosting',
					'@id'            => $url . '#article',
					'headline'       => $title,
					'description'    => $desc,
					'image'          => $image,
					'datePublished'  => $published,
					'dateModified'   => $modified,
					'author'         => $author_node ? [ '@id' => $author_node['@id'] ] : $org,
					'publisher'      => $org,
					'wordCount'      => $words,
					'articleSection' => array_values( array_diff( $sections, [ 'Uncategorized' ] ) ),
					'keywords'       => $kw,
					'inLanguage'     => 'en-US',
				] + $main
			);
			break;

		case 'em-glossary':
			$short   = td_field( 'short_definition', $id );
			$nodes[] = [
				'@type'            => 'DefinedTerm',
				'@id'              => $url . '#term',
				'name'             => $title,
				'description'      => is_string( $short ) && '' !== trim( $short ) ? td_seo_text( $short ) : $desc,
				'url'              => $url,
				'inDefinedTermSet' => [ '@id' => td_url( 'marketing', '/glossary/#set' ) ],
			] + $main;
			$nodes[] = td_schema_glossary_set();
			break;

		case 'td_tool':
			$nodes[] = [
				'@type'               => 'SoftwareApplication',
				'@id'                 => $url . '#app',
				'name'                => $title,
				'description'         => $desc,
				'url'                 => $url,
				'image'               => $image,
				'applicationCategory' => 'BusinessApplication',
				'operatingSystem'     => 'Web browser',
				'isAccessibleForFree' => true,
				'offers'              => [
					'@type'         => 'Offer',
					'price'         => '0',
					'priceCurrency' => 'USD',
				],
				'publisher'           => $org,
			] + $main;
			break;

		case 'td_resource':
			$pages   = td_field( 'pages', $id );
			$cover   = td_schema_image_url( td_field( 'cover', $id ) );
			$nodes[] = [
				'@type'          => 'DigitalDocument',
				'@id'            => $url . '#document',
				'name'           => $title,
				'description'    => $desc,
				'url'            => $url,
				'image'          => '' !== $cover ? $cover : $image,
				'encodingFormat' => 'application/pdf',
				'numberOfPages'  => is_numeric( $pages ) && (int) $pages > 0 ? (int) $pages : null,
				'author'         => $org,
				'publisher'      => $org,
				'inLanguage'     => 'en-US',
				'dateModified'   => $modified,
			] + $main;
			break;

		case 'studio_work':
			$status  = td_field( 'status', $id );
			$nodes[] = [
				'@type'              => 'CreativeWork',
				'@id'                => $url . '#work',
				'name'               => $title,
				'description'        => $desc,
				'url'                => $url,
				'image'              => $image,
				'creator'            => $org,
				'creativeWorkStatus' => is_string( $status ) ? td_seo_text( $status ) : '',
			] + $main;
			break;

		case 'page':
			if ( 'AboutPage' === td_schema_webpage_type() ) {
				$nodes[] = td_schema_founder();
				foreach ( td_seo_team_posts() as $team ) {
					$nodes[] = td_schema_person_from_team( $team );
				}
			}
			break;
	}
	return $nodes;
}

/**
 * The glossary DefinedTermSet (the /glossary/ hub on Marketing).
 *
 * @return array<string,mixed>
 */
function td_schema_glossary_set(): array {
	return [
		'@type'     => 'DefinedTermSet',
		'@id'       => td_url( 'marketing', '/glossary/#set' ),
		'name'      => 'TechDella marketing glossary',
		'url'       => td_url( 'marketing', '/glossary/' ),
		'publisher' => [ '@id' => td_schema_org_id() ],
	];
}

/**
 * All nodes for the current page, in order, after the td_schema_graph filter.
 *
 * @return array<int,array<string,mixed>>
 */
function td_schema_graph_nodes(): array {
	$page_url = td_schema_page_url();
	$page_id  = $page_url . '#webpage';
	$crumbs   = Graph::breadcrumb_list( td_breadcrumb_items(), $page_url . '#breadcrumb' );
	$template = td_schema_template_nodes( $page_id );

	$webpage = [
		'@type'      => td_schema_webpage_type(),
		'@id'        => $page_id,
		'url'        => $page_url,
		'name'       => td_seo_text( wp_get_document_title() ),
		'isPartOf'   => [ '@id' => home_url( '/#website' ) ],
		'inLanguage' => 'en-US',
		'breadcrumb' => $crumbs ? [ '@id' => $crumbs['@id'] ] : null,
	];
	if ( is_front_page() || 'AboutPage' === $webpage['@type'] ) {
		$webpage['about'] = [ '@id' => td_schema_org_id() ];
	}
	if ( is_singular() ) {
		$post_id                       = (int) get_queried_object_id();
		$webpage['description']        = td_seo_meta_description( $post_id );
		$webpage['datePublished']      = (string) get_post_time( 'c', true, $post_id );
		$webpage['dateModified']       = (string) get_post_modified_time( 'c', true, $post_id );
		$webpage['primaryImageOfPage'] = [
			'@type' => 'ImageObject',
			'url'   => td_og_image_url( $post_id ),
		];
		$main                          = $template[0] ?? null;
		if ( $main && isset( $main['@id'] ) && ! empty( $main['mainEntityOfPage'] ) ) {
			$webpage['mainEntity'] = [ '@id' => $main['@id'] ];
		}
		if ( 'post' === get_post_type( $post_id ) || 'em-glossary' === get_post_type( $post_id ) ) {
			$reviewer = td_schema_user_id( td_field( 'reviewer', $post_id ) );
			if ( $reviewer ) {
				$person = td_schema_person_from_user( $reviewer );
				if ( $person ) {
					$template[]            = $person;
					$webpage['reviewedBy'] = [ '@id' => $person['@id'] ];
				}
			}
			$webpage['lastReviewed'] = td_schema_date( td_field( 'last_reviewed', $post_id ) );
		}
	} elseif ( is_post_type_archive( 'em-glossary' ) ) {
		$webpage['mainEntity'] = [ '@id' => td_url( 'marketing', '/glossary/#set' ) ];
	}
	if ( 'AboutPage' === $webpage['@type'] && empty( $webpage['mainEntity'] ) ) {
		$webpage['mainEntity'] = [ '@id' => td_schema_org_id() ];
	}

	$graph = new Graph();
	$graph->add( td_schema_organization() );
	$graph->add( td_schema_founder() );
	$graph->add( td_schema_website() );
	$graph->add( $webpage );
	if ( $crumbs ) {
		$graph->add( $crumbs );
	}
	$graph->add_all( $template );
	$graph->add_all( td_schema()->nodes() );

	$nodes = (array) apply_filters( 'td_schema_graph', $graph->nodes() );

	$final = new Graph();
	$final->add_all( $nodes );
	$final->resolve_reviews( td_schema_org_id() );
	return $final->nodes();
}

/**
 * Print the page's single JSON-LD graph.
 */
function td_schema_print(): void {
	if ( is_admin() || is_feed() || is_embed() || is_robots() ) {
		return;
	}
	$graph = new Graph();
	echo '<script type="application/ld+json" id="td-schema">' . Graph::to_json( $graph->document( td_schema_graph_nodes() ) ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with JSON_HEX_TAG|JSON_HEX_AMP.
}
add_action( 'wp_footer', 'td_schema_print', 5 );
