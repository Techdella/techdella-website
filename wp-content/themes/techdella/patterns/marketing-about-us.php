<?php
/**
 * Title: Marketing · About us
 * Slug: techdella/marketing-about-us
 * Categories: techdella
 * Description: About page (Main bento-about + founder + team + values). AboutPage + Person schema come from the SEO module.
 * Inserter: no
 * TD Site: marketing
 * TD Post Type: page
 * TD Slug: about-us
 * TD Title: About us
 * TD Description: Meet TechDella, a B2B growth marketing agency that runs search, paid ads, lead generation, email and AI automation as one system. See who we are.
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
		'hero',
		[
			'eyebrow'        => 'About us',
			'title'          => 'We help companies get easier to *choose*',
			'lead'           => 'TechDella brings search, paid, lead generation, email and AI automation together, so your marketing works as one system instead of disconnected tactics. TechDella Studios builds the products and sites it runs on.',
			'primaryLabel'   => 'Book a discovery call',
			'primaryUrl'     => '/book-a-discovery-call/',
			'secondaryLabel' => 'See case studies',
			'secondaryUrl'   => '/case-studies/',
			'image'          => 'photos/techdella-team-group',
			'imageAlt'       => 'The TechDella team',
			'background'     => 'plain',
		]
	),
	$td_b(
		'bento-grid',
		[
			'rowHeight' => 210,
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
					'chip'       => 'How we start',
					'title'      => 'Every engagement starts with a discovery call to understand your market.',
					'linkLabel'  => 'Book a call',
					'url'        => '/book-a-discovery-call/',
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
		'arch-people',
		[
			'anchor'         => 'team',
			'label'          => 'The team',
			'title'          => 'The people doing the *work*',
			'intro'          => 'A small, senior team. You work directly with the people who plan and ship your marketing.',
			'primaryLabel'   => 'Book a discovery call',
			'primaryUrl'     => 'marketing:/book-a-discovery-call/',
			'secondaryLabel' => 'TechDella Studios',
			'secondaryUrl'   => 'studios:/',
			'source'         => 'inline',
			'surface'        => 'canvas',
			'people'         => [
				[
					'name'  => 'Joanna Okedara-Kalu',
					'role'  => 'Founder & CEO',
					'image' => 'team/joanna-okedara-kalu',
				],
				[
					'name'  => 'Chinedu Emmanuel Kalu',
					'role'  => 'COO & Co-founder',
					'image' => 'team/chinedu-emmanuel-kalu',
				],
				[
					'name'  => 'Akeem Feysupo',
					'role'  => 'Growth Strategist',
					'image' => 'team/akeem-feysupo',
				],
				[
					'name'  => 'Abel Saka',
					'role'  => 'Brand Designer & UI/UX',
					'image' => 'team/abel-saka',
				],
				[
					'name'  => 'Omolola Akiyode',
					'role'  => 'TechDella Team',
					'image' => 'team/omolola-akiyode',
				],
				[
					'name'  => 'Mercy Olumakinwa',
					'role'  => 'TechDella Team',
					'image' => 'team/mercy-olumakinwa',
				],
			],
		]
	),
	$td_b(
		'feature-grid',
		[
			'label'   => 'How we work',
			'title'   => 'What you can *expect*',
			'intro'   => 'We only promise what we can control.',
			'style'   => 'numbered',
			'columns' => 4,
			'items'   => [
				[
					'title' => 'Clear priorities',
					'text'  => 'Every engagement starts with an audit and a roadmap ranked by impact, so you know what we’ll do first and why.',
				],
				[
					'title' => 'One connected system',
					'text'  => 'Search, paid, email and automation share data and goals instead of running as separate projects.',
				],
				[
					'title' => 'Quality work',
					'text'  => 'Senior people plan and ship the work. Defined deliverables, not open-ended retainers.',
				],
				[
					'title' => 'Honest reporting',
					'text'  => 'Monthly reporting on what we did, what changed and what comes next. No guaranteed rankings.',
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
			'arch'        => true,
		]
	),
];

echo serialize_blocks( $td_blocks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup; attributes are JSON-encoded by core.
