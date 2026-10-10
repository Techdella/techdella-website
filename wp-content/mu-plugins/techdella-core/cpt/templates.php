<?php
/**
 * Locked page patterns (§14.2): every post type opens with its sections in
 * place. Layout-only types lock insertion ('insert'); prose types stay open
 * but their structural blocks cannot be moved or removed (per-block lock).
 *
 * Block names are the theme's techdella/<name> blocks (§2.3).
 */

defined( 'ABSPATH' ) || exit;

/**
 * A techdella/<name> block template entry.
 *
 * @param string              $name  Block folder name.
 * @param array<string,mixed> $attrs Attributes.
 * @param bool                $lock  Lock move/remove.
 * @return array<int,mixed>
 */
function td_tpl_block( string $name, array $attrs = [], bool $lock = true ): array {
	if ( $lock ) {
		$attrs['lock'] = [
			'move'   => true,
			'remove' => true,
		];
	}
	return [ 'techdella/' . $name, $attrs ];
}

/**
 * A core heading placeholder.
 *
 * @param string $text  Heading text.
 * @param int    $level Level.
 * @return array<int,mixed>
 */
function td_tpl_heading( string $text, int $level = 2 ): array {
	return [
		'core/heading',
		[
			'level'   => $level,
			'content' => $text,
			'lock'    => [
				'move'   => true,
				'remove' => true,
			],
		],
	];
}

/**
 * A core paragraph with a placeholder.
 *
 * @param string $placeholder Placeholder.
 * @return array<int,mixed>
 */
function td_tpl_paragraph( string $placeholder ): array {
	return [ 'core/paragraph', [ 'placeholder' => $placeholder ] ];
}

/**
 * Block templates and lock per post type.
 *
 * @return array<string,array{template:array<int,mixed>,lock:string|false}>
 */
function td_block_templates(): array {
	$service = [
		'template' => [
			td_tpl_block( 'hero', [], false ),
			td_tpl_block( 'feature-grid', [], false ),
			td_tpl_block( 'before-after', [], false ),
			td_tpl_block( 'steps-fold', [], false ),
			td_tpl_block( 'testimonial', [], false ),
			td_tpl_block( 'faq', [], false ),
			td_tpl_block( 'cta-dark', [], false ),
		],
		'lock'     => 'insert',
	];

	return [
		'service'        => $service,
		'studio_service' => $service,
		'solution'       => [
			'template' => [
				td_tpl_block( 'hero', [], false ),
				td_tpl_block( 'section-head', [], false ),
				td_tpl_block( 'feature-grid', [], false ), // 3 industry pains.
				td_tpl_block( 'steps-fold', [], false ),   // Services used, in order.
				td_tpl_block( 'case-grid', [], false ),    // Proof, or an honest first-steps section.
				td_tpl_block( 'faq', [], false ),
				td_tpl_block( 'cta-dark', [], false ),
			],
			'lock'     => 'insert',
		],
		'location'       => [
			'template' => [
				td_tpl_block( 'hero', [], false ),
				td_tpl_block( 'stat-pills', [], false ),
				td_tpl_block( 'feature-grid', [], false ), // Local channels and costs.
				td_tpl_block( 'case-grid', [], false ),    // Proof from this market only.
				td_tpl_block( 'faq', [], false ),
				td_tpl_block( 'cta-dark', [], false ),
			],
			'lock'     => 'insert',
		],
		'case_study'     => [
			'template' => [
				td_tpl_block( 'hero' ),
				td_tpl_block( 'stat-pills' ),
				td_tpl_heading( 'Challenge' ),
				td_tpl_paragraph( 'Where the client started, in their words. 2–4 sentences.' ),
				td_tpl_heading( 'Approach' ),
				td_tpl_paragraph( 'What we did and in which order. Name the platforms and tactics.' ),
				td_tpl_heading( 'Results' ),
				td_tpl_paragraph( 'What changed, with client-confirmed numbers and the timeframe.' ),
				td_tpl_block( 'testimonial' ),
				td_tpl_block( 'case-grid' ),
			],
			'lock'     => false,
		],
		'em-glossary'    => [
			'template' => [
				td_tpl_block( 'tldr' ),
				td_tpl_paragraph( 'Answer-first intro: what the term means for a B2B team, using the term in the first sentence.' ),
				td_tpl_heading( 'Why it matters' ),
				td_tpl_paragraph( 'What changes for pipeline, cost or rankings when you get this right (or wrong).' ),
				td_tpl_heading( 'Example from our work' ),
				td_tpl_paragraph( 'A real example: client (with permission), what we did, what happened. Mark unverified details [CONFIRM].' ),
				td_tpl_heading( 'How to do it' ),
				td_tpl_paragraph( 'Steps a reader can follow today. Link to the related service.' ),
				td_tpl_heading( 'Related terms' ),
				td_tpl_paragraph( 'Link 3–5 related glossary terms.' ),
			],
			'lock'     => false,
		],
		'post'           => [
			'template' => [
				td_tpl_block( 'tldr' ),
				td_tpl_paragraph( 'Answer-first intro: 2–3 sentences that answer the headline directly and use the primary keyword.' ),
				td_tpl_paragraph( 'Your first-hand angle: what we saw, did or measured that others do not say.' ),
				td_tpl_heading( 'Start with a section heading' ),
				td_tpl_paragraph( 'Short paragraphs. Link one service and three glossary terms.' ),
				td_tpl_block( 'author-box' ),
			],
			'lock'     => false,
		],
		'td_tool'        => [
			'template' => [
				td_tpl_block( 'hero' ),
				td_tpl_heading( 'How to use it' ),
				td_tpl_paragraph( 'Three to five steps a first-time visitor can follow.' ),
				td_tpl_heading( 'Why it matters' ),
				td_tpl_paragraph( 'What the result tells you and what to do next.' ),
				td_tpl_heading( 'Worked example' ),
				td_tpl_paragraph( 'A real input and the result it gives, explained.' ),
				td_tpl_block( 'faq' ),
				td_tpl_block( 'cta-dark' ),
			],
			'lock'     => false,
		],
		'td_resource'    => [
			'template' => [
				td_tpl_block( 'hero', [], false ),
				td_tpl_block( 'feature-grid', [], false ), // What's inside / who it's for.
				td_tpl_block( 'form', [ 'formKey' => 'lead_magnet' ], false ),
				td_tpl_block( 'faq', [], false ),
			],
			'lock'     => 'insert',
		],
		'studio_work'    => [
			'template' => [
				td_tpl_block( 'hero' ),
				td_tpl_block( 'stat-pills' ),
				td_tpl_heading( 'The brief' ),
				td_tpl_paragraph( 'What the client (or this concept) needed. Concepts say so in the first sentence.' ),
				td_tpl_heading( 'What we built' ),
				td_tpl_paragraph( 'Screens, flows and decisions. Add the gallery below.' ),
				[ 'core/gallery', [] ],
				td_tpl_block( 'cta-dark' ),
			],
			'lock'     => false,
		],
		'thank-you'      => [
			'template' => [
				td_tpl_heading( 'Thanks, we have it.', 1 ),
				td_tpl_paragraph( 'What happens next and when (e.g. "We reply within one working day").' ),
				td_tpl_block( 'cta-dark' ),
			],
			'lock'     => false,
		],
	];
}

add_action(
	'init',
	static function () {
		foreach ( td_block_templates() as $type => $tpl ) {
			$object = get_post_type_object( $type );
			if ( ! $object ) {
				continue;
			}
			$object->template      = $tpl['template'];
			$object->template_lock = $tpl['lock'];
		}
	},
	30
);
