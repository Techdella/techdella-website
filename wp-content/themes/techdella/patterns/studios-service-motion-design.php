<?php
/**
 * Title: Studios service: Motion design
 * Slug: techdella/studios-service-motion-design
 * Description: studio_service page (Studios artboard pattern): hero, deliverables, process, packages, work, FAQ, CTA. Primary keyword to validate: motion design studio.
 * Inserter: no
 * TD Site: studios
 * TD Post Type: studio_service
 * TD Slug: motion-design
 * TD Title: Motion design
 * TD Description: Motion design studio for B2B products: explainers, product demos, launch films and animated ads made for your website, sales calls and every ad platform.
 *
 * @package techdella
 */

defined( 'ABSPATH' ) || exit;

$td_blocks = [
	[
		'hero',
		[
			'parentLabel'    => 'Studios',
			'parentUrl'      => '/',
			'eyebrow'        => 'Motion design',
			'title'          => 'Motion design that explains your product in *seconds*',
			'lead'           => 'Explainers, product demos, launch films and animated ads, made for your site, your sales calls and every ad platform.',
			'primaryLabel'   => 'Start a project',
			'primaryUrl'     => '/start/',
			'secondaryLabel' => 'See packages',
			'secondaryUrl'   => '#packages',
			'image'          => 'stock/creator-recording-video-vertical',
			'imageAlt'       => 'A presenter recording a video to camera',
			'imageShape'     => 'arch',
			'layout'         => 'split',
			'background'     => 'grid',
		],
	],
	[
		'service-switcher',
		[
			'label'   => 'Studio services',
			'items'   => [
				[
					'label' => 'MVP development',
					'url'   => '/services/mvp-development/',
				],
				[
					'label' => 'AI automation',
					'url'   => '/services/ai-automation/',
				],
				[
					'label' => 'UI/UX design',
					'url'   => '/services/ui-ux-design/',
				],
				[
					'label' => 'Website design',
					'url'   => '/services/website-design/',
				],
				[
					'label' => 'Branding',
					'url'   => '/services/branding/',
				],
				[
					'label' => 'Graphic design',
					'url'   => '/services/graphic-design/',
				],
				[
					'label' => 'Motion design',
					'url'   => '/services/motion-design/',
				],
			],
			'current' => '/services/motion-design/',
		],
	],
	[
		'feature-grid',
		[
			'anchor'    => 'deliverables',
			'label'     => 'Deliverables',
			'title'     => 'What you *get*',
			'style'     => 'icon',
			'columns'   => 3,
			'highlight' => -2,
			'items'     => [
				[
					'title' => 'Script and storyboard',
					'text'  => 'The message and the frames, agreed before animation starts.',
					'icon'  => 'list',
				],
				[
					'title' => 'Animation',
					'text'  => 'Motion graphics on your brand, with sound design.',
					'icon'  => 'chart',
				],
				[
					'title' => 'Product demos',
					'text'  => 'Your real product UI, animated to show how it works.',
					'icon'  => 'target',
				],
				[
					'title' => 'Ad cuts',
					'text'  => 'Short versions in 9:16, 1:1 and 16:9 for each platform.',
					'icon'  => 'nodes',
				],
				[
					'title' => 'Captions',
					'text'  => 'Burned-in or caption files, because most people watch muted.',
					'icon'  => 'check',
				],
			],
		],
	],
	[
		'feature-grid',
		[
			'anchor'  => 'process',
			'label'   => 'Process',
			'title'   => 'How it *runs*',
			'style'   => 'numbered',
			'surface' => 'canvas',
			'items'   => [
				[
					'title' => 'Brief',
					'text'  => 'The audience, the message and where the video runs.',
				],
				[
					'title' => 'Script',
					'text'  => 'Script and storyboard for approval.',
				],
				[
					'title' => 'Style frames',
					'text'  => 'A few key frames to lock the look.',
				],
				[
					'title' => 'Animate',
					'text'  => 'Animation, sound and revisions.',
				],
				[
					'title' => 'Deliver',
					'text'  => 'Final cuts in every format you need.',
				],
			],
		],
	],
	[
		'video',
		[
			'heading' => 'Motion *samples*',
			'intro'   => 'Short ad creatives we made for a fictional brand, Northwind, one per platform.',
			'chip'    => 'Examples',
			'ratio'   => '9:16',
			'items'   => [
				[
					'scene'       => 'mo-04a-tiktok-hook',
					'title'       => 'TikTok hook ad (example for a fictional brand)',
					'description' => 'A 10-second TikTok ad sample: a one-second hook, the product in three steps, then the call to action. Made for the fictional brand Northwind.',
					'duration'    => 10,
					'caption'     => 'Example · TikTok: hook in the first second.',
				],
				[
					'scene'       => 'mo-04b-meta-carousel',
					'title'       => 'Meta carousel ad (example for a fictional brand)',
					'description' => 'A 10-second Meta carousel ad sample, one benefit per card. Made for the fictional brand Northwind.',
					'duration'    => 10,
					'caption'     => 'Example · Meta: one benefit per card.',
				],
				[
					'scene'       => 'mo-04c-linkedin-document',
					'title'       => 'LinkedIn document ad (example for a fictional brand)',
					'description' => 'A 10-second LinkedIn document ad sample that teaches before it sells. Made for the fictional brand Northwind.',
					'duration'    => 10,
					'caption'     => 'Example · LinkedIn: teach first, then ask.',
				],
			],
		],
	],
	[
		'pricing-cards',
		[
			'anchor'     => 'packages',
			'label'      => 'Packages',
			'title'      => 'Clear scope, clear *price*',
			'background' => 'paper',
			'intro'      => 'Every package lists deliverables, duration, revisions and exclusions. Prices are confirmed in your scope.',
			'cards'      => [
				[
					'featured'    => true,
					'badge'       => 'Start here',
					'kicker'      => 'Up to 30 seconds · 2 revision rounds',
					'title'       => 'Motion Graphics Package',
					'text'        => 'Explainers, product demos and animated ads.',
					'price'       => 'From {{price:motion_package}}',
					'buttonLabel' => 'Get a scope',
					'buttonUrl'   => '/start/',
				],
				[
					'kicker'      => 'Up to 90 seconds · 2 revision rounds',
					'title'       => 'Launch film',
					'text'        => 'A longer piece for a launch page or keynote.',
					'price'       => 'From {{price:launch_film}}',
					'buttonLabel' => 'Get a scope',
					'buttonUrl'   => '/start/',
				],
			],
		],
	],
	[
		'case-grid',
		[
			'source'  => 'studio_work',
			'variant' => 'cover',
			'count'   => 3,
			'title'   => 'Recent *work*',
			'intro'   => 'Concept projects are labelled Concept.',
			'surface' => 'canvas',
		],
	],
	[
		'faq',
		[
			'title'      => 'Motion design *questions*',
			'items'      => [
				[
					'question' => 'How long should an explainer be?',
					'answer'   => 'For ads, 6 to 15 seconds. For a homepage or sales call, 45 to 90 seconds is usually enough.',
				],
				[
					'question' => 'Do you do voiceover?',
					'answer'   => 'Tell us in your brief. We confirm voice, language and licensing options in your quote.',
				],
				[
					'question' => 'Which formats do I get?',
					'answer'   => 'MP4 in 9:16, 1:1 and 16:9 as the brief needs, plus a poster image and captions.',
				],
				[
					'question' => 'Can you animate our real product?',
					'answer'   => 'Yes. Send us access or screen recordings and we rebuild the screens as clean motion.',
				],
			],
			'background' => 'paper',
		],
	],
	[
		'cta-dark',
		[
			'title'       => 'Have an idea? Let’s make it *real*',
			'lead'        => 'Send a short brief. We’ll reply with a scope and an estimate.',
			'buttonLabel' => 'Start a project',
			'buttonUrl'   => '/start/',
			'variant'     => 'sand',
		],
	],
];

foreach ( $td_blocks as $td_block ) {
	echo get_comment_delimited_block_content( 'techdella/' . $td_block[0], $td_block[1], '' ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- serialized block markup; attributes JSON-encoded by core.
}
