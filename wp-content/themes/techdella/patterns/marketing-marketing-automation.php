<?php
/**
 * Title: Marketing · AI marketing automation service
 * Slug: techdella/marketing-marketing-automation
 * Categories: techdella
 * Description: AI marketing automation page (prototype Automation): hero with workflow canvas, strip, what we automate, before/after, connected services, process + case study, FAQ, CTA.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: service
 * TD Slug: marketing-automation
 * TD Title: AI marketing automation
 * TD Description: AI marketing automation that replies to every lead in minutes, scores it and updates your CRM, built on the tools you use with people reviewing output.
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
	$td_b( 'service-switcher', [ 'current' => '/services/marketing-automation/' ] ),
	$td_b(
		'hero',
		[
			'parentLabel'    => 'Services',
			'parentUrl'      => '/services/',
			'eyebrow'        => 'AI automations',
			'title'          => 'AI marketing automation that never lets a lead go *cold*',
			'lead'           => 'AI marketing automation answers every new lead in minutes, scores it, and updates your CRM without copy-paste. We build instant follow-up, AI lead scoring and CRM workflows on the tools you already use, with people reviewing every AI step.',
			'primaryLabel'   => 'Map my automations',
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => 'What we automate',
			'secondaryUrl'   => '#included',
			'platformsLabel' => 'Tools we build with',
			'platforms'      => [
				[
					'name' => 'Zapier',
					'mark' => 'Z',
					'slug' => 'zapier',
					'ink'  => true,
				],
				[
					'name' => 'Make',
					'mark' => 'M',
					'slug' => 'make',
				],
				[
					'name' => 'n8n',
					'mark' => 'n8',
					'slug' => 'n8n',
				],
				[
					'name' => 'HubSpot',
					'mark' => 'H',
					'slug' => 'hubspot',
				],
				[
					'name' => 'Slack',
					'mark' => 'S',
					'slug' => 'slack',
				],
				[
					'name' => 'WhatsApp Business',
					'mark' => 'W',
					'slug' => 'whatsapp',
				],
			],
		],
		[
			$td_b(
				'workflow-canvas',
				[
					'caption' => 'Example workflow',
					'steps'   => [
						[
							'kind'  => 'trigger',
							'label' => 'Trigger',
							'title' => 'New form submission',
						],
						[
							'kind'  => 'ai',
							'label' => 'AI step',
							'title' => 'Score, summarize, draft reply',
						],
						[
							'kind'     => 'branch',
							'branches' => [
								[
									'label' => 'If hot',
									'title' => 'WhatsApp reply + Slack alert to sales',
									'hot'   => true,
								],
								[
									'label' => 'If warm',
									'title' => 'Start email nurture sequence',
								],
							],
						],
						[
							'kind'  => 'crm',
							'label' => 'CRM',
							'title' => 'Contact, score & owner saved',
						],
					],
					'bubble'  => [
						'meta' => 'WhatsApp · 2 min after form',
						'text' => 'Hi Ada, thanks for reaching out about SOC 2. Here’s a link to book a call with our team.',
					],
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
						[ 'title' => 'Leads answered in minutes, not days' ],
						[ 'title' => 'No more copy-paste between tools' ],
						[ 'title' => 'Human review where it matters' ],
						[ 'title' => 'Built on tools you already use' ],
					],
				]
			),
		],
		[
			'style' => [
				'spacing' => [
					'padding' => [
						'top'    => '8px',
						'bottom' => '24px',
					],
				],
			],
		]
	),
	$td_b(
		'feature-grid',
		[
			'anchor'  => 'included',
			'title'   => 'What we *automate*',
			'intro'   => 'The repetitive work between marketing and sales, handled automatically.',
			'style'   => 'icon',
			'columns' => 3,
			'items'   => [
				[
					'icon'  => 'chat',
					'tone'  => 'canvas',
					'title' => 'Instant lead follow-up',
					'text'  => 'Every new lead gets a personal reply on email or WhatsApp within minutes, with a link to book.',
				],
				[
					'icon'  => 'target',
					'title' => 'AI lead scoring & summaries',
					'text'  => 'Each lead summarized and scored, so sales knows who to call first.',
				],
				[
					'icon'  => 'nodes',
					'title' => 'CRM sync & routing',
					'text'  => 'Forms, ads and inboxes synced to your CRM, with the right owner assigned.',
				],
				[
					'icon'  => 'person',
					'tone'  => 'sand',
					'title' => 'AI website assistant',
					'text'  => 'Answers common questions and books calls, trained on your own content.',
				],
				[
					'icon'  => 'check',
					'title' => 'Built, tested and maintained',
					'text'  => 'We document every workflow and monitor it, so nothing silently breaks.',
				],
				[
					'icon'  => 'chart',
					'tone'  => 'dark',
					'title' => 'Automated reporting',
					'text'  => 'Weekly numbers posted to Slack or email, no spreadsheets.',
				],
			],
		]
	),
	$td_b(
		'before-after',
		[
			'title'      => 'What changes when it’s *automated*',
			'layout'     => 'center',
			'surface'    => 'canvas',
			'afterStyle' => 'dark',
			'before'     => [
				'label' => 'Before',
				'items' => [ 'Leads sit in an inbox until someone checks', 'Details copied by hand into the CRM', 'Sales calls everyone in the order they arrived', 'Reports built manually every Friday' ],
			],
			'after'      => [
				'label' => 'After',
				'items' => [ 'Every lead answered within minutes', 'CRM updated automatically, with a summary', 'Hottest leads routed to sales first', 'Reports delivered to Slack every Monday' ],
			],
		]
	),
	$td_b(
		'connected-services',
		[
			'title'       => 'Automation *connects* everything',
			'intro'       => 'It’s the glue between channels: every lead from search, ads and outreach gets the right next step automatically.',
			'current'     => '/services/marketing-automation/',
			'items'       => [
				[
					'relation' => 'Input',
					'title'    => 'SEO',
					'text'     => 'Contact forms from organic visitors.',
					'url'      => '/services/seo/',
				],
				[
					'relation' => 'Input',
					'title'    => 'AI Search',
					'text'     => 'Inquiries from AI referrals.',
					'url'      => '/services/ai-search-optimization/',
				],
				[
					'relation' => 'Input',
					'title'    => 'Paid ads',
					'text'     => 'Meta, TikTok and LinkedIn lead forms.',
					'url'      => '/services/paid-ads/',
				],
				[
					'relation' => 'Input',
					'title'    => 'Lead generation',
					'text'     => 'Downloads and outreach replies.',
					'url'      => '/services/lead-generation/',
				],
				[
					'relation' => 'Output',
					'title'    => 'Email',
					'text'     => 'Sequences triggered automatically.',
					'url'      => '/services/email-marketing/',
				],
				[
					'relation' => 'You are here',
					'title'    => 'AI automation',
					'text'     => 'Connects it all, in minutes.',
					'url'      => '/services/marketing-automation/',
				],
			],
			'footerLabel' => 'Need a custom AI agent or app? TechDella Studios builds them',
			'footerUrl'   => 'studios:/',
		]
	),
	$td_b(
		'video',
		[
			'heading'     => 'One lead, handled in *seconds*',
			'intro'       => 'A form is submitted. The contact lands in your CRM, the right email goes out and your team gets a Slack alert.',
			'chip'        => 'In 10 seconds',
			'scene'       => 'mo-05-workflow',
			'title'       => 'A lead moving through an automated workflow',
			'description' => 'A 10-second animation: form, CRM, email and Slack nodes light up as a new lead is handled automatically.',
			'duration'    => 10,
		]
	),
	$td_section(
		[
			$td_cols(
				[
					$td_b(
						'feature-grid',
						[
							'title'   => 'How we build your *automations*',
							'style'   => 'numbered',
							'bare'    => true,
							'columns' => 2,
							'items'   => [
								[
									'title' => 'Map the workflow',
									'text'  => 'Where leads come from and where they stall.',
								],
								[
									'title' => 'Build & connect',
									'text'  => 'On your CRM and existing tools.',
								],
								[
									'title' => 'Test with people',
									'text'  => 'Every AI step reviewed before launch.',
								],
								[
									'title' => 'Monitor & improve',
									'text'  => 'Documented, monitored and handed over.',
								],
							],
						]
					),
				],
				[
					$td_b(
						'case-card',
						[
							'variant' => 'card',
							'item'    => [
								'title'    => 'How Epic Entertainment Games scaled credit operations',
								'client'   => 'Epic Entertainment Games',
								'industry' => 'Gaming & casino software',
								'excerpt'  => 'Requests from partners and internal teams consolidated into one structured, repeatable workflow, so growth stopped creating chaos.',
								'visual'   => 'table',
								'url'      => '/case-studies/epic-entertainment-games/',
							],
						]
					),
				]
			),
		]
	),
	$td_b(
		'faq',
		[
			'title' => 'AI automation *questions*',
			'items' => [
				[
					'question' => 'Will AI reply to customers without anyone checking?',
					'answer'   => 'Only where you’re comfortable. We use templates and guardrails for routine replies, and route anything sensitive to a person.',
				],
				[
					'question' => 'Which tools do you use?',
					'answer'   => 'Zapier, Make or n8n for workflows, your CRM, Slack and WhatsApp Business, with AI models for scoring and drafting.',
				],
				[
					'question' => 'Is our customer data safe?',
					'answer'   => 'We build inside your accounts, limit access to what each workflow needs, and document where every piece of data goes.',
				],
				[
					'question' => 'How is this different from TechDella Studios?',
					'answer'   => 'Here we automate marketing and sales workflows on existing tools. Studios builds custom apps and AI agents from scratch.',
				],
				[
					'question' => 'What happens if a workflow breaks?',
					'answer'   => 'We monitor every workflow and get an alert when a step fails. Failed runs are retried or flagged to a person, so no lead is lost silently.',
				],
			],
		]
	),
	$td_b(
		'cta-dark',
		[
			'title'       => 'Never miss a *lead* again',
			'lead'        => 'We’ll map where leads stall today and which steps to automate first.',
			'buttonLabel' => 'Map my automations',
			'buttonUrl'   => '/growth-audit/',
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
