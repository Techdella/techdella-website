<?php
/**
 * Title: Marketing · Email marketing service
 * Slug: techdella/marketing-email-marketing
 * Categories: techdella
 * Description: Email marketing page (prototype Email): hero with email collage, strip, what's included, email designs, connected services, process + testimonial, FAQ, CTA.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: service
 * TD Slug: email-marketing
 * TD Title: Email marketing
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
	$td_b( 'service-switcher', [ 'current' => '/services/email-marketing/' ] ),
	$td_b(
		'hero',
		[
			'parentLabel'    => 'Services',
			'parentUrl'      => '/services/',
			'eyebrow'        => 'Email marketing',
			'title'          => 'Email marketing that turns leads into *clients*',
			'lead'           => 'Our B2B email marketing services cover nurture sequences, newsletters and email automation, done for you. We plan the program, write and design every email, set up deliverability, and report on the replies and meetings it brings.',
			'primaryLabel'   => 'Plan my email program',
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => 'See email designs',
			'secondaryUrl'   => '#designs',
			'platformsLabel' => 'Platforms we work in',
			'platforms'      => [
				[ 'name' => 'HubSpot', 'mark' => 'H', 'slug' => 'hubspot', 'ink' => true ],
				[ 'name' => 'Mailchimp', 'mark' => 'M', 'slug' => 'mailchimp' ],
				[ 'name' => 'Klaviyo', 'mark' => 'K' ],
				[ 'name' => 'Brevo', 'mark' => 'B' ],
				[ 'name' => 'ActiveCampaign', 'mark' => 'A' ],
			],
		],
		[
			$td_b(
				'email-mock',
				[
					'variant'       => 'collage',
					'emails'        => [
						[ 'type' => 'newsletter', 'from' => 'YourCo', 'tag' => 'The Monthly Brief', 'subject' => '3 changes in AI search this month, and what to do about them', 'cta' => 'Read the brief', 'image' => 'stock/designer-desk-swatches-wide' ],
					],
					'sequenceTitle' => 'Nurture sequence',
					'sequence'      => [
						[ 'day' => '0', 'title' => 'Welcome + checklist' ],
						[ 'day' => '3', 'title' => 'Case study' ],
						[ 'day' => '7', 'title' => 'Common mistakes' ],
						[ 'day' => '10', 'title' => 'Book a call' ],
					],
					'reply'         => 'Reply: “Can we talk next week?”',
					'image'         => 'stock/product-flatlay-notebook-candle-square',
					'imageAlt'      => '',
					'imageCaption'  => 'Copy & design in-house',
					'caption'       => 'Example emails',
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
						[ 'title' => 'Strategy, copy & design in one team' ],
						[ 'title' => 'Automated sequences that run 24/7' ],
						[ 'title' => 'Deliverability set up properly' ],
						[ 'title' => 'Reported on meetings, not just opens' ],
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
			'title'   => 'What’s included in our *email marketing*',
			'intro'   => 'From the first welcome email to the sales handoff.',
			'style'   => 'icon',
			'columns' => 3,
			'items'   => [
				[ 'icon' => 'list', 'tone' => 'canvas', 'title' => 'Nurture sequences', 'text' => 'Automated email series that educate, build trust and invite the right leads to talk to sales.' ],
				[ 'icon' => 'lines', 'title' => 'Newsletters', 'text' => 'A regular, genuinely useful email that keeps you top of mind, written and designed for you.' ],
				[ 'icon' => 'person', 'title' => 'Segmentation', 'text' => 'Different messages for different industries, roles and stages.' ],
				[ 'icon' => 'check', 'tone' => 'sand', 'title' => 'Deliverability setup', 'text' => 'SPF, DKIM and DMARC authentication and list hygiene, so your emails reach the inbox.' ],
				[ 'icon' => 'chat', 'title' => 'Copywriting & design', 'text' => 'On-brand templates and copy written to get replies, not just opens.' ],
				[ 'icon' => 'chart', 'tone' => 'dark', 'title' => 'Reporting', 'text' => 'Opens and clicks, plus the replies, meetings and deals each email drives.' ],
			],
		]
	),
	$td_b(
		'email-mock',
		[
			'anchor'  => 'designs',
			'variant' => 'designs',
			'title'   => 'Emails people *actually* read',
			'caption' => 'Example email designs',
			'emails'  => [
				[ 'type' => 'newsletter', 'tag' => 'Newsletter', 'from' => 'YourCo', 'subject' => 'The Monthly Brief: what changed in search', 'cta' => 'Read more' ],
				[
					'type'    => 'nurture',
					'tag'     => 'Nurture · Day 3',
					'subject' => 'How Paycape reached 15,000 monthly visitors',
					'preview' => 'Hi Ada, a quick story about a fintech team that had the right product but couldn’t be found…',
					'stats'   => [
						[ 'num' => '15,000', 'label' => 'monthly organic visitors' ],
						[ 'num' => '50 days', 'label' => 'to first result' ],
					],
					'cta'     => 'Read the case study',
				],
				[ 'type' => 'invite', 'tag' => 'Invite', 'subject' => 'Free workshop: is your brand in AI answers?', 'detail' => '45 minutes, live Q&A, recording included.', 'cta' => 'Save my seat' ],
			],
		]
	),
	$td_b(
		'connected-services',
		[
			'title'   => 'Email works better *connected*',
			'intro'   => 'Leads from search, ads and outreach arrive automatically, and your articles become newsletter content.',
			'current' => '/services/email-marketing/',
			'items'   => [
				[ 'relation' => 'Content', 'title' => 'SEO', 'text' => 'Articles reused in newsletters.', 'url' => '/services/seo/' ],
				[ 'relation' => 'Audience', 'title' => 'AI Search', 'text' => 'Researchers who found you.', 'url' => '/services/ai-search-optimization/' ],
				[ 'relation' => 'Leads in', 'title' => 'Paid ads', 'text' => 'Lead forms feed sequences.', 'url' => '/services/paid-ads/' ],
				[ 'relation' => 'Leads in', 'title' => 'Lead generation', 'text' => 'Magnet downloads start nurture.', 'url' => '/services/lead-generation/' ],
				[ 'relation' => 'You are here', 'title' => 'Email', 'text' => 'Warms leads up to a call.', 'url' => '/services/email-marketing/' ],
				[ 'relation' => 'Triggers', 'title' => 'AI automation', 'text' => 'Right email at the right moment.', 'url' => '/services/marketing-automation/' ],
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
							'title'   => 'How we set up your *email*',
							'style'   => 'numbered',
							'bare'    => true,
							'columns' => 2,
							'items'   => [
								[ 'title' => 'Audit & plan', 'text' => 'List health, platform and journeys.' ],
								[ 'title' => 'Set up', 'text' => 'Authentication, segments, templates.' ],
								[ 'title' => 'Write & design', 'text' => 'Sequences and newsletters.' ],
								[ 'title' => 'Test & improve', 'text' => 'Subject lines, timing, offers.' ],
							],
						]
					),
				],
				[
					$td_b(
						'testimonial',
						[
							'quote'   => 'The clarity they brought to our strategy saved us months of trial and error — and probably a lot of money too…',
							'name'    => 'Akin Oyegoke',
							'role'    => 'CEO',
							'company' => 'Johan Consults',
							'image'   => 'people/akin-oyegoke-johan-consults',
							'logo'    => 'clients/johan-consults',
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
			'title' => 'Email marketing *questions*',
			'items' => [
				[ 'question' => 'Which email platform should we use?', 'answer' => 'We work with the one you have. If you’re starting fresh, we usually recommend HubSpot for B2B sales teams, or Brevo for smaller budgets.' ],
				[ 'question' => 'Do you write and design the emails?', 'answer' => 'Yes. Strategy, copy, templates and design are all handled by our team.' ],
				[ 'question' => 'Can you fix emails going to spam?', 'answer' => 'We set up SPF, DKIM and DMARC, clean your list and adjust sending habits, which resolves most deliverability problems.' ],
				[ 'question' => 'How do you measure success?', 'answer' => 'Replies, meetings booked and deals influenced, alongside open and click rates.' ],
				[ 'question' => 'Do you send cold email?', 'answer' => 'Our email programs are for people who asked to hear from you. Personalized outreach to new accounts sits in lead generation, with its own sending setup so it never puts your main domain at risk.' ],
			],
		]
	),
	$td_b(
		'cta-dark',
		[
			'title'       => 'Turn leads into *conversations*',
			'lead'        => 'We’ll review your current emails and show you where leads are going cold.',
			'buttonLabel' => 'Plan my email program',
			'buttonUrl'   => '/growth-audit/',
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
