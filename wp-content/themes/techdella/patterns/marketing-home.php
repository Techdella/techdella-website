<?php
/**
 * Title: Marketing · Home
 * Slug: techdella/marketing-home
 * Categories: techdella
 * Description: Home page (prototype Main): hero, clients, services bento, how we work, Paycape results, about bento, ways to start, founder letter, final CTA.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: page
 * TD Slug: home
 * TD Title: Home
 * TD Description: TechDella is a B2B growth marketing agency. We help you get found in search and AI answers, reach buyers with ads, and turn interest into qualified leads.
 * TD Front Page: yes
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

$td_b = static fn( string $name, array $attrs = [] ): array => [
	'blockName'    => 'techdella/' . $name,
	'attrs'        => $attrs,
	'innerBlocks'  => [],
	'innerHTML'    => '',
	'innerContent' => [],
];

$td_blocks = [
	$td_b(
		'hero-orbit',
		[
			'chip'           => 'SEO · AI search · Paid ads · Email · Lead gen · AI automation',
			'title'          => 'Get found. Get noticed. *Grow.*',
			'lead'           => 'TechDella is a B2B growth marketing agency. We help established businesses show up where customers search, reach them with ads we create in-house, and turn interest into leads with email and AI automation.',
			'primaryLabel'   => 'Get a growth audit',
			'primaryUrl'     => '/growth-audit/',
			'secondaryLabel' => 'See how we work',
			'secondaryUrl'   => '#how',
			'trustText'      => 'Trusted by teams at Paycape, Johan Consults and WezOps',
			'trustAvatars'   => [ 'people/levi-barbosa-wezops', 'people/akin-oyegoke-johan-consults', 'team/joanna-okedara-kalu' ],
			'platforms'      => [
				[
					'name' => 'Google Search & Ads',
					'mark' => 'G',
					'slug' => 'google-search',
					'ink'  => true,
				],
				[
					'name' => 'AI answers',
					'mark' => 'AI',
				],
				[
					'name' => 'LinkedIn Ads',
					'mark' => 'in',
					'slug' => 'linkedin',
				],
				[
					'name' => 'Meta · Facebook & Instagram',
					'mark' => 'M',
					'slug' => 'meta',
					'ink'  => true,
				],
				[
					'name' => 'TikTok Ads',
					'mark' => 'T',
					'slug' => 'tiktok',
				],
			],
			'stats'          => [
				[
					'value' => '15,000',
					'label' => 'monthly organic visitors · Paycape',
				],
				[
					'value' => '50%',
					'label' => 'of conversions from inbound · Paycape',
				],
			],
			'leadChip'       => 'New qualified lead',
		]
	),
	$td_b(
		'logo-strip',
		[
			'label' => "Trusted by\ngrowing brands",
			'logos' => [
				[
					'name'   => 'Paycape',
					'image'  => 'clients/paycape',
					'height' => 26,
				],
				[
					'name'   => 'Johan Consults',
					'image'  => 'clients/johan-consults',
					'height' => 30,
				],
				[
					'name'   => 'WezOps',
					'image'  => 'clients/wezops',
					'height' => 24,
				],
				[
					'name'   => 'startbuddi',
					'height' => 24,
				],
				[
					'name'   => 'Shopinbos',
					'image'  => 'clients/shopinbos',
					'height' => 14,
				],
			],
		]
	),
	$td_b(
		'bento-grid',
		[
			'anchor' => 'services',
			'label'  => 'Services',
			'title'  => 'One connected *growth* system',
			'intro'  => 'Each service stands on its own. Together they cover the whole journey, from the first search to the signed client.',
			'tiles'  => [
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
		'steps-fold',
		[
			'anchor'    => 'how',
			'label'     => 'How we work',
			'title'     => "Clear steps.\n*No guesswork.*",
			'intro'     => 'Every engagement follows the same five steps, so you always know what’s happening and what you’ll get.',
			'image'     => 'photos/techdella-team-group',
			'imageAlt'  => 'The TechDella team',
			'cardImage' => 'team/joanna-okedara-kalu',
			'cardTitle' => 'One point of contact',
			'cardText'  => 'from discovery to reporting',
			'steps'     => [
				[
					'title'       => 'Discovery call',
					'deliverable' => 'You get: a fit check and next steps',
					'body'        => 'A 30-minute call to understand your goals, market, budget and what you’ve already tried. If we’re not the right fit, we’ll tell you.',
					'tags'        => [ 'Goals', 'Budget', 'Timeline' ],
				],
				[
					'title'       => 'Audit & strategy',
					'deliverable' => 'You get: a prioritized growth plan',
					'body'        => 'We review your search visibility, ads, landing pages, email and tracking, then rank every opportunity by impact and effort.',
				],
				[
					'title'       => 'Scope & setup',
					'deliverable' => 'You get: fixed scope and working tracking',
					'body'        => 'We agree deliverables and timelines in writing, then set up accounts, analytics, pixels, CRM and creative briefs.',
				],
				[
					'title'       => 'Create & launch',
					'deliverable' => 'You get: live campaigns and content',
					'body'        => 'Our team writes, designs and builds: pages, ad creatives, videos, emails and automations, then launches in priority order.',
				],
				[
					'title'       => 'Optimize & report',
					'deliverable' => 'You get: a monthly report and review call',
					'body'        => 'We test, improve and report on leads and revenue, not vanity metrics. Then we plan the next month together.',
				],
			],
			'pills'     => [
				[
					'icon'  => 'dashboard',
					'label' => 'Shared dashboard',
				],
				[
					'icon'  => 'chat',
					'label' => 'Monthly review call',
				],
				[
					'icon'  => 'lock',
					'label' => 'You own every account',
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
		'bento-grid',
		[
			'anchor'    => 'work',
			'rowHeight' => 210,
			'padding'   => 'flush-top',
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
	),
	$td_b(
		'pricing-cards',
		[
			'anchor' => 'pricing',
			'label'  => 'Ways to start',
			'title'  => "Start with an audit.\nGrow from *there*.",
			'intro'  => 'Clear offers with defined deliverables. No open-ended retainers.',
			'cards'  => [
				[
					'featured'    => true,
					'kicker'      => 'One-time',
					'badge'       => 'Start here',
					'title'       => 'Growth Audit',
					'text'        => 'Search, AI visibility, ads, funnel and tracking reviewed, with a prioritized plan.',
					'price'       => '[PRICE]',
					'buttonLabel' => 'Get started',
					'buttonUrl'   => '/growth-audit/',
				],
				[
					'kicker' => 'Fixed scope',
					'title'  => 'Growth Sprint',
					'text'   => 'We implement the top priorities: SEO fixes, a campaign launch, an email sequence or an automation.',
					'price'  => 'From [PRICE]',
				],
				[
					'kicker' => 'Monthly',
					'title'  => 'Growth Management',
					'text'   => 'Ongoing search, paid, email and automation work with contracted monthly deliverables.',
					'price'  => '[PRICE] / month',
				],
			],
		]
	),
	$td_b(
		'founder',
		[
			'label'      => 'From the founder',
			'title'      => 'Hi, I’m *Joanna*.',
			'body'       => "I founded TechDella to help companies become easier to understand, easier to trust and easier to choose. We bring search, paid, email and automation together, so your marketing works as one system instead of disconnected tactics.\n\nWe only promise what we can control: clear priorities, quality work and honest reporting.",
			'stats'      => [
				[
					'value' => '11',
					'label' => 'Published case studies',
				],
				[
					'value' => '5',
					'label' => 'Client countries',
				],
			],
			'image'      => 'team/joanna-okedara-kalu',
			'imageAlt'   => 'Joanna Okedara-Kalu, Founder and CEO',
			'note'       => '[CONFIRM founder sign-off]',
			'quote'      => 'Techdella reframed how we were thinking about our market entirely. We went from scattered tactics to a positioning and GTM system that actually made sense for where we were heading. The difference was immediate.',
			'quoteName'  => 'Levi Barbosa',
			'quoteRole'  => 'CEO, WezOps',
			'quoteImage' => 'people/levi-barbosa-wezops',
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
