<?php
/**
 * Title: Marketing · Services hub
 * Slug: techdella/marketing-services
 * Categories: techdella
 * Description: Services hub (Main bento + Options hub-spoke and timeline): hero, services bento, connected system, how we work, Paycape results, FAQ, CTA.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: page
 * TD Slug: services
 * TD Title: Services
 * TD Description: TechDella's B2B marketing services: SEO, AI search optimization, paid ads, lead generation, email marketing and AI automation, built to work as one system.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

// Block array builders (serialized with serialize_blocks() at the end; core JSON-encodes the attributes).
$td_b   = static fn( string $name, array $attrs = [], array $inner = [] ): array => [
	'blockName'    => str_contains( $name, '/' ) ? $name : 'techdella/' . $name,
	'attrs'        => $attrs,
	'innerBlocks'  => $inner,
	'innerHTML'    => '',
	'innerContent' => array_fill( 0, count( $inner ), null ),
];
$td_div = static function ( string $block, string $css_class, array $inner, array $extra = [] ): array {
	$base  = [
		'core/group'   => 'wp-block-group',
		'core/columns' => 'wp-block-columns',
		'core/column'  => 'wp-block-column',
	][ $block ];
	$attrs = array_merge( $css_class ? [ 'className' => $css_class ] : [], $extra );
	// Inline style exactly as core's save() writes it for these attributes (column width, group padding).
	$style = '';
	if ( isset( $attrs['width'] ) ) {
		$style = 'flex-basis:' . $attrs['width'];
	}
	foreach ( $attrs['style']['spacing']['padding'] ?? [] as $side => $value ) {
		$style .= ( $style ? ';' : '' ) . 'padding-' . $side . ':' . $value;
	}
	$open = '<div class="' . esc_attr( trim( $base . ' ' . $css_class ) ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
	return [
		'blockName'    => $block,
		'attrs'        => $attrs,
		'innerBlocks'  => $inner,
		'innerHTML'    => $open . '</div>',
		'innerContent' => array_merge( [ $open ], array_fill( 0, count( $inner ), null ), [ '</div>' ] ),
	];
};
// A full-width section with a container: optional surface (canvas|sand|dark) and extra group attributes.
$td_section = static fn( array $inner, string $surface = '', array $extra = [] ): array => $td_div(
	'core/group',
	trim( 'td-section ' . ( $surface ? 'td-section--' . $surface : '' ) ),
	[ $td_div( 'core/group', 'td-container', $inner ) ],
	$extra
);
// Two columns (left, right); widths as column `width` attributes (core saves them as flex-basis).
$td_cols = static fn( array $left, array $right, int $left_pct = 58 ): array => $td_div(
	'core/columns',
	'td-cols',
	[
		$td_div( 'core/column', '', $left, [ 'width' => $left_pct . '%' ] ),
		$td_div( 'core/column', '', $right, [ 'width' => ( 100 - $left_pct ) . '%' ] ),
	]
);

$td_blocks = [
	$td_b( 'service-switcher', [ 'current' => '/services/' ] ),
	$td_b(
		'hero',
		[
			'eyebrow'        => 'Services',
			'title'          => 'Marketing services that work as *one* system',
			'lead'           => 'TechDella offers six B2B marketing services: SEO, AI search optimization, paid ads, lead generation, email marketing and AI marketing automation. Each one stands on its own; together they take a buyer from the first search to a booked call.',
			'primaryLabel'   => 'Get a growth audit',
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => 'See how we work',
			'secondaryUrl'   => '#how',
			'layout'         => 'center',
		]
	),
	$td_b(
		'bento-grid',
		[
			'padding' => 'flush-top',
			'tiles'   => [
				[
					'type'       => 'search',
					'span'       => 7,
					'spanTablet' => 6,
					'eyebrow'    => '01 · Get found',
					'title'      => 'SEO & AI Search Optimization',
					'text'       => 'Rank on Google and get recommended when buyers ask ChatGPT, Perplexity or Google’s AI.',
					'links'      => [
						[
							'label' => 'SEO',
							'url'   => '/services/seo/',
							'style' => 'dark',
						],
						[
							'label' => 'AI Search',
							'url'   => '/services/ai-search-optimization/',
							'style' => 'light',
						],
					],
					'serp'       => [
						'meta'  => '#1 · yourcompany.com',
						'title' => 'SOC 2 Consulting for SaaS Companies',
						'desc'  => 'Fixed-fee readiness for growing teams…',
					],
					'answer'     => [
						'label' => 'AI answer · sources',
						'text'  => '“…firms frequently recommended include *YourCo*…”',
						'tag'   => 'cited',
					],
					'caption'    => 'Example result',
				],
				[
					'type'         => 'platform-list',
					'span'         => 5,
					'spanTablet'   => 6,
					'url'          => '/services/paid-ads/',
					'eyebrow'      => '02 · Get noticed',
					'title'        => 'Paid ads, made in-house',
					'platforms'    => [
						[
							'name' => 'Google',
							'mark' => 'G',
							'slug' => 'google-ads',
						],
						[
							'name' => 'Meta',
							'mark' => 'M',
							'slug' => 'meta',
						],
						[
							'name' => 'TikTok',
							'mark' => 'T',
							'slug' => 'tiktok',
						],
						[
							'name' => 'LinkedIn',
							'mark' => 'in',
							'slug' => 'linkedin',
						],
					],
					'thumb'        => 'stock/creator-recording-video-vertical',
					'thumbCaption' => 'Example · 3 SEO myths ▶',
					'text'         => 'Strategy, creatives and media buying under one roof. →',
				],
				[
					'type'       => 'form',
					'span'       => 4,
					'url'        => '/services/lead-generation/',
					'eyebrow'    => '03 · Capture',
					'title'      => 'Lead generation',
					'formTitle'  => 'Get the free SOC 2 checklist',
					'formButton' => 'Download',
				],
				[
					'type'    => 'sequence',
					'span'    => 4,
					'url'     => '/services/email-marketing/',
					'eyebrow' => '04 · Nurture',
					'title'   => 'Email marketing',
					'rows'    => [
						[
							'label'  => 'Day 0 · Your checklist',
							'status' => '62% open',
							'strong' => true,
						],
						[
							'label'  => 'Day 3 · Case study',
							'status' => 'Sent',
						],
						[
							'label'  => 'Day 7 · Book a call',
							'status' => 'Queued',
							'dot'    => true,
						],
					],
					'caption' => 'Example sequence',
				],
				[
					'type'    => 'photo',
					'span'    => 4,
					'url'     => 'studios:/',
					'image'   => 'stock/designer-drawing-tablet-studio',
					'alt'     => 'Designer sketching on a drawing tablet in a sunlit studio',
					'eyebrow' => 'Creative',
					'title'   => 'Ads, videos and visuals designed by our Studios team',
				],
				[
					'type'    => 'flow',
					'span'    => 12,
					'url'     => '/services/marketing-automation/',
					'eyebrow' => '05 · Follow up',
					'title'   => 'AI automations',
					'text'    => 'Every lead answered in minutes, scored, logged in your CRM and routed to the right person.',
					'steps'   => [
						[ 'label' => 'New lead' ],
						[
							'label'     => 'AI scores',
							'highlight' => true,
						],
						[ 'label' => 'CRM updated' ],
						[ 'label' => 'Email sent' ],
						[ 'label' => 'Sales alerted' ],
					],
				],
			],
		]
	),
	$td_b(
		'hub-spoke',
		[
			'title'       => 'Everything *connects* to growth',
			'intro'       => 'Six services and our Studios team, one plan. Data from each channel makes the others work better.',
			'centerLabel' => 'Your growth',
			'items'       => [
				[
					'title' => 'SEO',
					'text'  => 'Get found',
					'url'   => '/services/seo/',
				],
				[
					'title' => 'AI Search',
					'text'  => 'Get found',
					'url'   => '/services/ai-search-optimization/',
				],
				[
					'title' => 'Paid ads',
					'text'  => 'Google · Meta · TikTok · LinkedIn',
					'url'   => '/services/paid-ads/',
					'tone'  => 'dark',
				],
				[
					'title' => 'Lead generation',
					'text'  => 'Capture',
					'url'   => '/services/lead-generation/',
				],
				[
					'title' => 'Email marketing',
					'text'  => 'Nurture',
					'url'   => '/services/email-marketing/',
				],
				[
					'title' => 'AI automations',
					'text'  => 'Follow up',
					'url'   => '/services/marketing-automation/',
					'tone'  => 'sand',
				],
				[
					'title' => 'Creatives',
					'text'  => 'by Studios',
					'url'   => 'studios:/',
					'image' => 'stock/designer-desk-swatches-wide',
				],
			],
		]
	),
	$td_b(
		'feature-grid',
		[
			'title'   => 'More ways to *get found*',
			'intro'   => 'Specialist SEO services for sites with technical debt or local markets.',
			'style'   => 'tiles',
			'columns' => 2,
			'items'   => [
				[
					'title' => 'Technical SEO',
					'text'  => 'Crawling, indexing, speed, schema and migrations.',
					'url'   => '/services/technical-seo/',
				],
				[
					'title' => 'Local SEO',
					'text'  => 'Google Business Profile, listings and real location pages.',
					'url'   => '/services/local-seo/',
				],
			],
		]
	),
	$td_b(
		'timeline',
		[
			'anchor' => 'how',
			'title'  => 'From first call to *results*',
			'intro'  => 'Five clear steps. You always know what’s happening and what you’ll get.',
			'items'  => [
				[
					'title'  => 'Discovery call',
					'text'   => 'Goals, market, budget and what’s been tried.',
					'result' => 'A fit check',
					'note'   => 'day one',
				],
				[
					'title'  => 'Audit & strategy',
					'text'   => 'Search, ads, funnel and tracking reviewed and ranked by impact.',
					'result' => 'A growth plan',
					'note'   => 'understand first',
				],
				[
					'title'  => 'Scope & setup',
					'text'   => 'Fixed scope, then tracking, accounts, CRM and creative briefs.',
					'result' => 'Tracking that works',
					'note'   => 'agreed in writing',
				],
				[
					'title'    => 'Create & launch',
					'text'     => 'We write, design and build the pages, creatives, emails and automations.',
					'result'   => 'Live campaigns',
					'image'    => 'photos/techdella-team-group',
					'imageAlt' => 'The TechDella team planning a launch',
				],
				[
					'title'  => 'Optimize & report',
					'text'   => 'Testing and improving every week, reporting on leads and revenue.',
					'result' => 'A monthly report',
					'note'   => 'then we repeat',
					'dark'   => true,
				],
			],
		]
	),
	$td_b(
		'stat-pills',
		[
			'label'     => 'Results',
			'title'     => 'What we did for *Paycape*',
			'meta'      => 'Fintech · 5-month engagement · Q3 2025',
			'stats'     => [
				[
					'value' => '50%',
					'label' => 'of conversions came from inbound',
				],
				[
					'value' => '15,000',
					'label' => 'monthly organic visitors',
				],
				[
					'value' => '50 days',
					'label' => 'to first result',
				],
			],
			'linkLabel' => 'Read the Paycape case study',
			'linkUrl'   => '/case-studies/paycape/',
		]
	),
	$td_b(
		'faq',
		[
			'title' => 'Services *questions*',
			'items' => [
				[
					'question' => 'Do we have to buy every service?',
					'answer'   => 'No. Each service works on its own. Most clients start with one or two, usually chosen in the growth audit, and add others when the data says they’ll pay back.',
				],
				[
					'question' => 'Where should we start?',
					'answer'   => 'With a growth audit. It reviews search, AI visibility, ads, funnel and tracking, and ranks every opportunity by impact, so you start with the work that matters most.',
				],
				[
					'question' => 'Who does the work?',
					'answer'   => 'Our own team of strategists, marketers, designers and builders. Creatives, videos and custom builds come from TechDella Studios.',
				],
				[
					'question' => 'Which markets do you work in?',
					'answer'   => 'Our clients operate in Nigeria, the US, Mexico, South Africa and Lebanon. We work remotely with teams in any English-speaking market.',
				],
				[
					'question' => 'How do you report?',
					'answer'   => 'A shared dashboard, a monthly report and a review call, focused on leads and revenue rather than vanity metrics.',
				],
			],
		]
	),
	$td_b(
		'cta-dark',
		[
			'title'       => 'Ready to *grow?*',
			'lead'        => 'Get an audit of your search, ads and funnel, and a clear plan for what to fix first.',
			'buttonLabel' => 'Get a growth audit',
			'buttonUrl'   => '/growth-audit/',
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
