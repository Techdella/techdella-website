<?php
/**
 * Title: Marketing · Lead generation service
 * Slug: techdella/marketing-lead-generation
 * Categories: techdella
 * Description: Lead generation page (prototype LeadGen): hero with lead mock, strip, what's included, journey, connected services, process + testimonial, FAQ, CTA.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: service
 * TD Slug: lead-generation
 * TD Title: Lead generation
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
$td_div = static function ( string $block, string $css_class, array $inner, string $style = '' ): array {
	$base = [
		'core/group'   => 'wp-block-group',
		'core/columns' => 'wp-block-columns',
		'core/column'  => 'wp-block-column',
	][ $block ];
	$open = '<div class="' . esc_attr( trim( $base . ' ' . $css_class ) ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
	$attrs = $css_class ? [ 'className' => $css_class ] : [];
	return [
		'blockName'    => $block,
		'attrs'        => $attrs,
		'innerBlocks'  => $inner,
		'innerHTML'    => $open . '</div>',
		'innerContent' => array_merge( [ $open ], array_fill( 0, count( $inner ), null ), [ '</div>' ] ),
	];
};
// A full-width section with a container: optional surface (canvas|sand|dark) and inline style.
$td_section = static fn( array $inner, string $surface = '', string $style = '' ): array => $td_div(
	'core/group',
	trim( 'td-section ' . ( $surface ? 'td-section--' . $surface : '' ) ),
	[ $td_div( 'core/group', 'td-container', $inner ) ],
	$style
);
// Two columns (left, right); widths as flex-basis percentages.
$td_cols = static fn( array $left, array $right, int $left_pct = 58 ): array => $td_div(
	'core/columns',
	'td-cols',
	[
		$td_div( 'core/column', '', $left, 'flex-basis:' . $left_pct . '%' ),
		$td_div( 'core/column', '', $right, 'flex-basis:' . ( 100 - $left_pct ) . '%' ),
	]
);

$td_blocks = [
	$td_b( 'service-switcher', [ 'current' => '/services/lead-generation/' ] ),
	$td_b(
		'hero',
		[
			'parentLabel'    => 'Services',
			'parentUrl'      => '/services/',
			'eyebrow'        => 'Lead generation',
			'title'          => 'B2B lead generation that fills your *pipeline*',
			'lead'           => 'Our B2B lead generation services combine offers buyers want, landing pages that convert and targeted outreach, so your sales team talks to qualified buyers, not random form fills. Every lead is scored and tracked to its source in your CRM.',
			'primaryLabel'   => 'Plan my lead engine',
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => 'What’s included',
			'secondaryUrl'   => '#included',
			'platformsLabel' => 'Tools we connect',
			'platforms'      => [
				[ 'name' => 'HubSpot', 'mark' => 'H', 'slug' => 'hubspot', 'ink' => true ],
				[ 'name' => 'Sales Navigator', 'mark' => 'in', 'slug' => 'linkedin' ],
				[ 'name' => 'Typeform', 'mark' => 'T' ],
				[ 'name' => 'Calendly', 'mark' => 'C' ],
			],
		],
		[
			$td_b(
				'lead-mock',
				[
					'url'     => 'yourcompany.com/checklist',
					'offer'   => 'The SOC 2 readiness checklist for SaaS teams',
					'fields'  => [ 'Work email', 'Company size' ],
					'button'  => 'Get the checklist',
					'leads'   => [
						[ 'who' => 'Head of IT · 80 staff', 'status' => 'Hot', 'hot' => true ],
						[ 'who' => 'CTO · 45 staff', 'status' => 'Hot', 'hot' => true ],
						[ 'who' => 'Student', 'status' => 'Nurture' ],
					],
					'stages'  => [ 'Visits', 'Leads', 'Qualified', 'Calls' ],
					'caption' => 'Example lead view',
				]
			),
		]
	),
	$td_div(
		'core/group',
		'td-container',
		[
			$td_b(
				'feature-grid',
				[
					'style'   => 'rule',
					'bare'    => true,
					'columns' => 4,
					'items'   => [
						[ 'title' => 'Qualified leads, not raw form fills' ],
						[ 'title' => 'Landing pages built in-house' ],
						[ 'title' => 'Every lead scored before sales calls' ],
						[ 'title' => 'Tracked to source in your CRM' ],
					],
				]
			),
		],
		'padding-block:8px 24px'
	),
	$td_b(
		'feature-grid',
		[
			'anchor'  => 'included',
			'title'   => 'What’s included in our *lead generation*',
			'intro'   => 'Inbound and outbound, connected to your CRM and sales process.',
			'style'   => 'icon',
			'columns' => 3,
			'items'   => [
				[ 'icon' => 'target', 'tone' => 'canvas', 'title' => 'Offers & lead magnets', 'text' => 'Checklists, benchmarks, calculators and audits your buyers actually want.' ],
				[ 'icon' => 'lines', 'title' => 'Landing pages & forms', 'text' => 'One page per campaign, one clear action, and forms that qualify as they capture.' ],
				[ 'icon' => 'chat', 'title' => 'Outbound outreach', 'text' => 'Personalized LinkedIn and email outreach to accounts that match your ideal customer.' ],
				[ 'icon' => 'chart', 'tone' => 'sand', 'title' => 'Lead scoring & qualification', 'text' => 'Fit and intent scores so sales calls the right people first.' ],
				[ 'icon' => 'nodes', 'title' => 'CRM setup & routing', 'text' => 'Every lead lands in your CRM with source, score and owner.' ],
				[ 'icon' => 'search', 'tone' => 'dark', 'title' => 'Source tracking', 'text' => 'Know which channel, ad and page produced every qualified lead.' ],
			],
		]
	),
	$td_b(
		'journey',
		[
			'title'     => 'From stranger to *sales call*',
			'intro'     => 'Every lead follows a clear path, and we measure each step.',
			'stages'    => [
				[ 'title' => 'Sees an ad or article', 'text' => 'Paid, SEO or AI search' ],
				[ 'title' => 'Lands on an offer', 'text' => 'Focused landing page' ],
				[ 'title' => 'Becomes a lead', 'text' => 'Form or booking' ],
				[ 'title' => 'Scored & routed', 'text' => 'AI automation' ],
				[ 'title' => 'Nurtured or called', 'text' => 'Email or sales' ],
			],
			'highlight' => 3,
		]
	),
	$td_b(
		'connected-services',
		[
			'title'   => 'Lead gen works better *connected*',
			'intro'   => 'Search and ads bring the traffic. Lead gen turns it into names. Email and automation turn names into meetings.',
			'current' => '/services/lead-generation/',
			'items'   => [
				[ 'relation' => 'Traffic', 'title' => 'SEO', 'text' => 'Organic visitors with intent.', 'url' => '/services/seo/' ],
				[ 'relation' => 'Traffic', 'title' => 'AI Search', 'text' => 'Referrals from AI answers.', 'url' => '/services/ai-search-optimization/' ],
				[ 'relation' => 'Traffic', 'title' => 'Paid ads', 'text' => 'Targeted clicks to each offer.', 'url' => '/services/paid-ads/' ],
				[ 'relation' => 'You are here', 'title' => 'Lead generation', 'text' => 'Turns visitors into leads.', 'url' => '/services/lead-generation/' ],
				[ 'relation' => 'Next', 'title' => 'Email', 'text' => 'Nurtures leads not ready yet.', 'url' => '/services/email-marketing/' ],
				[ 'relation' => 'Next', 'title' => 'AI automation', 'text' => 'Scores and routes in minutes.', 'url' => '/services/marketing-automation/' ],
			],
		]
	),
	$td_section(
		[
			$td_cols(
				[
					$td_b(
						'feature-grid',
						[
							'title'   => 'How we build your *lead engine*',
							'style'   => 'numbered',
							'bare'    => true,
							'columns' => 2,
							'items'   => [
								[ 'title' => 'Define your ICP', 'text' => 'Who buys, why, and what “qualified” means.' ],
								[ 'title' => 'Build the offer', 'text' => 'Lead magnet, page and form.' ],
								[ 'title' => 'Drive traffic', 'text' => 'Ads, search and outreach.' ],
								[ 'title' => 'Qualify & hand off', 'text' => 'Scored leads straight to sales.' ],
							],
						]
					),
				],
				[
					$td_b(
						'testimonial',
						[
							'quote'   => 'Techdella reframed how we were thinking about our market entirely. We went from scattered tactics to a positioning and GTM system that actually made sense for where we were heading. The difference was immediate.',
							'name'    => 'Levi Barbosa',
							'role'    => 'CEO',
							'company' => 'WezOps',
							'image'   => 'people/levi-barbosa-wezops',
							'logo'    => 'clients/wezops',
							'variant' => 'arch',
						]
					),
				]
			),
		]
	),
	$td_b(
		'faq',
		[
			'title' => 'Lead generation *questions*',
			'items' => [
				[ 'question' => 'What counts as a qualified lead?', 'answer' => 'We define it with you before we start: company size, role, need and timing. Only leads that match are counted and passed to sales.' ],
				[ 'question' => 'Do you buy or sell lead lists?', 'answer' => 'No. We build your own pipeline through offers, content, ads and personalized outreach.' ],
				[ 'question' => 'How quickly will we see leads?', 'answer' => 'Paid and outbound can start producing leads soon after launch; SEO-driven leads build over months. We’ll set realistic expectations in your plan.' ],
				[ 'question' => 'Which CRM do you work with?', 'answer' => 'HubSpot, Pipedrive, Zoho and most CRMs with an API. If you don’t have one, we’ll help you set it up.' ],
				[ 'question' => 'Do you write the lead magnets?', 'answer' => 'Yes. We research, write and design checklists, guides and worksheets with your team’s expertise, and build the landing page and follow-up emails around each one.' ],
			],
		]
	),
	$td_b(
		'cta-dark',
		[
			'title'       => 'Fill your *pipeline*',
			'lead'        => 'Tell us who you sell to. We’ll design the offer, pages and outreach to reach them.',
			'buttonLabel' => 'Plan my lead engine',
			'buttonUrl'   => '/growth-audit/',
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
