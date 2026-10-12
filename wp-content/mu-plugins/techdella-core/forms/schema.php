<?php
/**
 * Form schema (§11.1): one definition per Startbuddi endpoint key.
 *
 * This file is the ONLY place that knows:
 *   - which fields each form has (type, required, max length, options, labels);
 *   - which Startbuddi field name each field is sent as ("sb" key)
 *     [CONFIRM in Startbuddi docs — the endpoint-form field-name format];
 *   - which forms add people to nurture (they carry the marketing-consent
 *     checkbox, unticked by default, §11.4);
 *   - which sites use each form (thank-you pages are only created there).
 *
 * Pure data + pure functions (only __() from WordPress), so unit tests can load it.
 *
 * Field definition keys:
 *   type        text|email|url|textarea|select|pills|checkbox|hidden
 *   label       visible label (legend for pills)
 *   required    bool
 *   max         max characters (text-like) — default 200
 *   options     [ value => label ] for select/pills; for "card" pills value => [ label, hint ]
 *   multiple    pills only: checkboxes (true) or radios (false)
 *   style       pills only: pill|card|row
 *   help        help text under the field
 *   placeholder, autocomplete, rows, width (full|half)
 *   format      hidden only: "json" → a JSON object of scalars
 *   sb          Startbuddi field name (defaults to the field name)
 *   meta        true → sent under payload.meta instead of top level (consent flags)
 *   legend_hidden pills: visually hide the legend (the step heading says it)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every form key, in the order of §11.1.
 *
 * @return string[]
 */
function td_forms_keys(): array {
	return [ 'contact', 'audit', 'studios_brief', 'newsletter', 'lead_magnet', 'tool_gate', 'popup_exit', 'popup_scroll', 'mvp_calc', 'quote', 'guest_post' ];
}

/**
 * The marketing-consent checkbox (§11.4). Unticked by default; sent as
 * meta.consent_marketing (true/false).
 *
 * @param bool   $required Whether ticking it is required (newsletter sign-ups).
 * @param string $label    Checkbox label.
 * @return array<string,mixed>
 */
function td_forms_consent_field( bool $required = false, string $label = '' ): array {
	return [
		'type'     => 'checkbox',
		'label'    => '' !== $label ? $label : __( 'Send me occasional emails with guides and offers from TechDella. Unsubscribe any time.', 'techdella' ),
		'required' => $required,
		'meta'     => true,
		'sb'       => 'consent_marketing', // [CONFIRM in Startbuddi docs].
		'consent'  => true,
	];
}

/**
 * Raw definitions for all forms.
 *
 * @return array<string,array<string,mixed>>
 */
function td_forms_definitions(): array {
	$email = [
		'type'         => 'email',
		'label'        => __( 'Email', 'techdella' ),
		'required'     => true,
		'max'          => 190,
		'autocomplete' => 'email',
		'sb'           => 'email',
	];
	$name  = [
		'type'         => 'text',
		'label'        => __( 'Full name', 'techdella' ),
		'required'     => true,
		'max'          => 120,
		'autocomplete' => 'name',
		'width'        => 'half',
		'sb'           => 'name',
	];
	$first = [
		'type'         => 'text',
		'label'        => __( 'First name', 'techdella' ),
		'required'     => false,
		'max'          => 80,
		'autocomplete' => 'given-name',
		'sb'           => 'first_name',
	];
	$co    = [
		'type'         => 'text',
		'label'        => __( 'Company', 'techdella' ),
		'required'     => false,
		'max'          => 160,
		'autocomplete' => 'organization',
		'width'        => 'half',
		'sb'           => 'company',
	];

	$monthly = [
		'under-2000' => __( 'Under $2,000 / month', 'techdella' ),
		'2000-5000'  => __( '$2,000 – $5,000 / month', 'techdella' ),
		'5000-15000' => __( '$5,000 – $15,000 / month', 'techdella' ),
		'15000-plus' => __( '$15,000+ / month', 'techdella' ),
		'not-sure'   => __( 'Not sure yet', 'techdella' ),
	];

	return [
		'contact'       => [
			'name'          => 'Techdella · Contact / Book a call',
			'sites'         => [ 'marketing', 'studios' ],
			'title'         => __( 'Book a discovery call', 'techdella' ),
			'intro'         => __( 'Tell us where you are and what you need. We reply within one business day.', 'techdella' ),
			'submit'        => __( 'Send message', 'techdella' ),
			'success_title' => __( 'Message *received*', 'techdella' ),
			'success'       => __( 'Thanks. We’ll reply within one business day with times for a call.', 'techdella' ),
			'fields'        => [
				'name'              => $name,
				'email'             => array_merge(
					$email,
					[
						'label' => __( 'Work email', 'techdella' ),
						'width' => 'half',
					]
				),
				'company'           => $co,
				'website'           => [
					'type'         => 'url',
					'label'        => __( 'Website', 'techdella' ),
					'max'          => 300,
					'placeholder'  => 'https://yourcompany.com',
					'autocomplete' => 'url',
					'width'        => 'half',
					'sb'           => 'website',
				],
				'service_interest'  => [
					'type'     => 'pills',
					'multiple' => true,
					'label'    => __( 'What do you need help with?', 'techdella' ),
					'help'     => __( 'Pick any.', 'techdella' ),
					'options'  => [
						'seo'        => __( 'SEO', 'techdella' ),
						'ai-search'  => __( 'AI search', 'techdella' ),
						'paid'       => __( 'Paid ads', 'techdella' ),
						'lead-gen'   => __( 'Lead generation', 'techdella' ),
						'email'      => __( 'Email marketing', 'techdella' ),
						'automation' => __( 'Marketing automation', 'techdella' ),
						'studios'    => __( 'Build (Studios)', 'techdella' ),
						'not-sure'   => __( 'Not sure yet', 'techdella' ),
					],
					'sb'       => 'service_interest',
				],
				'budget'            => [
					'type'    => 'select',
					'label'   => __( 'Monthly budget', 'techdella' ),
					'options' => $monthly,
					'sb'      => 'budget',
				],
				'message'           => [
					'type'     => 'textarea',
					'label'    => __( 'How can we help?', 'techdella' ),
					'required' => true,
					'max'      => 4000,
					'rows'     => 4,
					'sb'       => 'message',
				],
				'consent_marketing' => td_forms_consent_field(),
			],
		],
		'audit'         => [
			'name'          => 'Techdella · Growth Audit',
			'sites'         => [ 'marketing' ],
			'title'         => __( 'Request your audit', 'techdella' ),
			'intro'         => __( 'Takes two minutes. Nothing is billed until we confirm fit.', 'techdella' ),
			'submit'        => __( 'Request my audit', 'techdella' ),
			'success_title' => __( 'Request *received*', 'techdella' ),
			'success'       => __( 'Here’s what happens next: we review your site and confirm the audit is a good fit, you get a short scoping email with the fee and timeline, then we deliver the audit and walk your team through the roadmap.', 'techdella' ),
			'fields'        => [
				'website'           => [
					'type'         => 'url',
					'label'        => __( 'Website', 'techdella' ),
					'required'     => true,
					'max'          => 300,
					'placeholder'  => 'https://yourcompany.com',
					'autocomplete' => 'url',
					'sb'           => 'website',
				],
				'name'              => $name,
				'email'             => array_merge(
					$email,
					[
						'label' => __( 'Work email', 'techdella' ),
						'width' => 'half',
					]
				),
				'company'           => $co,
				'role'              => [
					'type'    => 'select',
					'label'   => __( 'Your role', 'techdella' ),
					'width'   => 'half',
					'options' => [
						'head-of-marketing' => __( 'Head of Marketing', 'techdella' ),
						'founder-ceo'       => __( 'Founder / CEO', 'techdella' ),
						'growth-manager'    => __( 'Growth Manager', 'techdella' ),
						'managing-partner'  => __( 'Managing Partner', 'techdella' ),
						'other'             => __( 'Other', 'techdella' ),
					],
					'sb'      => 'role',
				],
				'company_size'      => [
					'type'    => 'select',
					'label'   => __( 'Company size', 'techdella' ),
					'width'   => 'half',
					'options' => [
						'1-9'      => '1–9',
						'10-49'    => '10–49',
						'50-100'   => '50–100',
						'100-plus' => '100+',
					],
					'sb'      => 'company_size',
				],
				'monthly_budget'    => [
					'type'    => 'select',
					'label'   => __( 'Monthly marketing budget', 'techdella' ),
					'width'   => 'half',
					'options' => $monthly,
					'sb'      => 'monthly_budget',
				],
				'goals'             => [
					'type'     => 'pills',
					'multiple' => true,
					'required' => true,
					'label'    => __( 'What’s the main problem?', 'techdella' ),
					'help'     => __( 'Pick any.', 'techdella' ),
					'options'  => [
						'traffic'     => __( 'Organic traffic has stalled', 'techdella' ),
						'competitors' => __( 'Competitors outrank us', 'techdella' ),
						'ai'          => __( 'Not showing up in AI search', 'techdella' ),
						'leads'       => __( 'Traffic but poor-quality leads', 'techdella' ),
						'ads'         => __( 'Ads aren’t paying back', 'techdella' ),
						'followup'    => __( 'Leads aren’t followed up', 'techdella' ),
					],
					'sb'       => 'goals',
				],
				'notes'             => [
					'type'        => 'textarea',
					'label'       => __( 'Anything else?', 'techdella' ),
					'max'         => 4000,
					'rows'        => 3,
					'placeholder' => __( 'Key markets, competitors, previous SEO work…', 'techdella' ),
					'sb'          => 'notes',
				],
				'consent_marketing' => td_forms_consent_field(),
			],
		],
		'studios_brief' => [
			'name'          => 'Techdella Studios · Project brief',
			'sites'         => [ 'studios' ],
			'title'         => __( 'Start a project', 'techdella' ),
			'intro'         => __( 'Four quick questions. We’ll reply with a scope and an estimate.', 'techdella' ),
			'submit'        => __( 'Send brief', 'techdella' ),
			'success_title' => __( 'Brief *received*', 'techdella' ),
			'success'       => __( 'We’ll review your brief and reply with a recommended package, or a paid discovery proposal for larger builds.', 'techdella' ),
			'steps'         => [
				[
					'title'  => __( 'What are we making?', 'techdella' ),
					'label'  => __( 'Project type', 'techdella' ),
					'fields' => [ 'project_type' ],
				],
				[
					'title'  => __( 'What’s your budget?', 'techdella' ),
					'label'  => __( 'Budget', 'techdella' ),
					'intro'  => __( 'A realistic range helps us recommend the right package.', 'techdella' ),
					'fields' => [ 'budget' ],
				],
				[
					'title'  => __( 'When do you need it?', 'techdella' ),
					'label'  => __( 'Timeline', 'techdella' ),
					'fields' => [ 'timeline', 'stage' ],
				],
				[
					'title'  => __( 'Tell us about it', 'techdella' ),
					'label'  => __( 'Details', 'techdella' ),
					'fields' => [ 'details', 'name', 'email', 'company' ],
				],
			],
			'fields'        => [
				'project_type'      => [
					'type'          => 'pills',
					'multiple'      => true,
					'required'      => true,
					'style'         => 'card',
					'label'         => __( 'Project type', 'techdella' ),
					'legend_hidden' => true,
					'options'       => [
						'mvp'    => [ __( 'App or MVP', 'techdella' ), __( 'Web app, SaaS, prototype', 'techdella' ) ],
						'ai'     => [ __( 'AI automation', 'techdella' ), __( 'Agents, workflows, CRM', 'techdella' ) ],
						'ux'     => [ __( 'UI/UX design', 'techdella' ), __( 'Screens, flows, prototypes', 'techdella' ) ],
						'brand'  => [ __( 'Graphics & brand', 'techdella' ), __( 'Identity, campaign assets', 'techdella' ) ],
						'motion' => [ __( 'Motion & video', 'techdella' ), __( 'Explainers, demos, ads', 'techdella' ) ],
						'other'  => [ __( 'Not sure yet', 'techdella' ), __( 'Help me scope it', 'techdella' ) ],
					],
					'sb'            => 'project_type',
				],
				'budget'            => [
					'type'          => 'pills',
					'multiple'      => false,
					'required'      => true,
					'style'         => 'row',
					'label'         => __( 'Budget', 'techdella' ),
					'legend_hidden' => true,
					'options'       => [
						'under-2k' => __( 'Under $2,000', 'techdella' ),
						'2k-5k'    => __( '$2,000 – $5,000', 'techdella' ),
						'5k-15k'   => __( '$5,000 – $15,000', 'techdella' ),
						'15k-plus' => __( '$15,000+', 'techdella' ),
					],
					'sb'            => 'budget',
				],
				'timeline'          => [
					'type'          => 'pills',
					'multiple'      => false,
					'required'      => true,
					'style'         => 'row',
					'label'         => __( 'Timeline', 'techdella' ),
					'legend_hidden' => true,
					'options'       => [
						'asap'       => __( 'ASAP', 'techdella' ),
						'1-2-months' => __( 'Within 1–2 months', 'techdella' ),
						'quarter'    => __( 'Within a quarter', 'techdella' ),
						'flexible'   => __( 'Flexible', 'techdella' ),
					],
					'sb'            => 'timeline',
				],
				'stage'             => [
					'type'     => 'pills',
					'multiple' => false,
					'label'    => __( 'Do you already have designs?', 'techdella' ),
					'options'  => [
						'designs-ready' => __( 'Yes, ready', 'techdella' ),
						'sketches'      => __( 'Some sketches', 'techdella' ),
						'need-design'   => __( 'No, need design', 'techdella' ),
					],
					'sb'       => 'stage',
				],
				'details'           => [
					'type'        => 'textarea',
					'label'       => __( 'The idea, in a few sentences', 'techdella' ),
					'required'    => true,
					'max'         => 4000,
					'rows'        => 4,
					'placeholder' => __( 'What it does, who it’s for, and what success looks like…', 'techdella' ),
					'sb'          => 'details',
				],
				'name'              => array_merge( $name, [ 'label' => __( 'Name', 'techdella' ) ] ),
				'email'             => array_merge( $email, [ 'width' => 'half' ] ),
				'company'           => array_merge( $co, [ 'width' => 'full' ] ),
				'consent_marketing' => td_forms_consent_field(),
			],
		],
		'newsletter'    => [
			'name'          => 'Techdella · Journal newsletter',
			'sites'         => [ 'journal', 'marketing', 'studios' ],
			'nurture'       => true,
			'title'         => __( 'Get the *Journal*', 'techdella' ),
			'intro'         => __( 'One practical email on search, ads and automation. Every two weeks.', 'techdella' ),
			'submit'        => __( 'Subscribe', 'techdella' ),
			'success_title' => __( 'You’re *in*', 'techdella' ),
			'success'       => __( 'Check your inbox for a welcome email from us.', 'techdella' ),
			'fields'        => [
				'email'             => $email,
				'first_name'        => $first,
				'topic_interest'    => [
					'type'    => 'select',
					'label'   => __( 'Most interested in', 'techdella' ),
					'options' => [
						'all'        => __( 'A bit of everything', 'techdella' ),
						'ai-search'  => __( 'AI search', 'techdella' ),
						'seo'        => __( 'SEO', 'techdella' ),
						'paid'       => __( 'Paid ads', 'techdella' ),
						'email'      => __( 'Email', 'techdella' ),
						'automation' => __( 'Automation', 'techdella' ),
					],
					'sb'      => 'topic_interest',
				],
				'consent_marketing' => td_forms_consent_field( true, __( 'Yes, send me the TechDella Journal newsletter. Unsubscribe any time.', 'techdella' ) ),
			],
		],
		'lead_magnet'   => [
			'name'          => 'Techdella · Resource download',
			'sites'         => [ 'marketing', 'journal', 'studios' ],
			'nurture'       => true,
			'title'         => __( 'Get the *guide*', 'techdella' ),
			'intro'         => __( 'Free PDF. We also email you the link.', 'techdella' ),
			'submit'        => __( 'Get the PDF', 'techdella' ),
			'success_title' => __( 'Your guide is *ready*', 'techdella' ),
			'success'       => __( 'Download it below. We’ve also emailed you the link.', 'techdella' ),
			'fields'        => [
				'first_name'        => array_merge(
					$first,
					[
						'required' => true,
						'width'    => 'half',
					]
				),
				'email'             => array_merge(
					$email,
					[
						'label' => __( 'Work email', 'techdella' ),
						'width' => 'half',
					]
				),
				'company'           => array_merge( $co, [ 'width' => 'full' ] ),
				'resource_slug'     => [
					'type'     => 'hidden',
					'required' => true,
					'max'      => 120,
					'sb'       => 'resource_slug',
				],
				'consent_marketing' => td_forms_consent_field(),
			],
		],
		'tool_gate'     => [
			'name'          => 'Techdella · Free tool',
			'sites'         => [ 'marketing' ],
			'nurture'       => true,
			'title'         => __( 'Email me the *results*', 'techdella' ),
			'intro'         => __( 'We send the full report to your inbox.', 'techdella' ),
			'submit'        => __( 'Send my results', 'techdella' ),
			'success_title' => __( 'Results *sent*', 'techdella' ),
			'success'       => __( 'Check your inbox in a minute or two.', 'techdella' ),
			'fields'        => [
				'email'             => $email,
				'tool_slug'         => [
					'type'     => 'hidden',
					'required' => true,
					'max'      => 120,
					'sb'       => 'tool_slug',
				],
				'result_summary'    => [
					'type' => 'hidden',
					'max'  => 1000,
					'sb'   => 'result_summary',
				],
				'consent_marketing' => td_forms_consent_field(),
			],
		],
		'popup_exit'    => [
			'name'          => 'Techdella · Exit-intent popup',
			'sites'         => [ 'marketing' ],
			'nurture'       => true,
			'title'         => __( 'Before you *go*', 'techdella' ),
			'intro'         => '',
			'submit'        => __( 'Send it to me', 'techdella' ),
			'success_title' => __( 'On its *way*', 'techdella' ),
			'success'       => __( 'Check your inbox for the link.', 'techdella' ),
			'fields'        => [
				'email'             => $email,
				'offer'             => [
					'type' => 'hidden',
					'max'  => 120,
					'sb'   => 'offer',
				],
				'page'              => [
					'type' => 'hidden',
					'max'  => 300,
					'sb'   => 'page',
				],
				'consent_marketing' => td_forms_consent_field(),
			],
		],
		'popup_scroll'  => [
			'name'          => 'Techdella · Post slide-in',
			'sites'         => [ 'journal' ],
			'nurture'       => true,
			'title'         => __( 'Keep *reading*', 'techdella' ),
			'intro'         => '',
			'submit'        => __( 'Send it to me', 'techdella' ),
			'success_title' => __( 'On its *way*', 'techdella' ),
			'success'       => __( 'Check your inbox for the link.', 'techdella' ),
			'fields'        => [
				'email'             => $email,
				'offer'             => [
					'type' => 'hidden',
					'max'  => 120,
					'sb'   => 'offer',
				],
				'post_slug'         => [
					'type' => 'hidden',
					'max'  => 200,
					'sb'   => 'post_slug',
				],
				'consent_marketing' => td_forms_consent_field(),
			],
		],
		'mvp_calc'      => [
			'name'          => 'Techdella Studios · MVP calculator',
			'sites'         => [ 'studios' ],
			'title'         => __( 'Email me this *estimate*', 'techdella' ),
			'intro'         => __( 'We send the breakdown and a short note on what would change it.', 'techdella' ),
			'submit'        => __( 'Email my estimate', 'techdella' ),
			'success_title' => __( 'Estimate *sent*', 'techdella' ),
			'success'       => __( 'Check your inbox. Reply to that email if you want to talk it through.', 'techdella' ),
			'fields'        => [
				'email'             => $email,
				'name'              => array_merge(
					$name,
					[
						'required' => false,
						'width'    => 'full',
					]
				),
				'inputs'            => [
					'type'   => 'hidden',
					'format' => 'json',
					'max'    => 2000,
					'sb'     => 'estimate_inputs',
				],
				'estimate'          => [
					'type' => 'hidden',
					'max'  => 200,
					'sb'   => 'estimate',
				],
				'consent_marketing' => td_forms_consent_field(),
			],
		],
		'quote'         => [
			'name'          => 'Techdella · Quote request',
			'sites'         => [ 'marketing', 'studios' ],
			'title'         => __( 'Get a *quote*', 'techdella' ),
			'intro'         => __( 'Pick a package and tell us where you are. You get a PDF quote in your currency straight away.', 'techdella' ),
			'submit'        => __( 'Get my quote', 'techdella' ),
			'success_title' => __( 'Your quote is *ready*', 'techdella' ),
			'success'       => __( 'Open the PDF below. We will follow up within one business day.', 'techdella' ),
			'fields'        => [
				'name'              => $name,
				'email'             => array_merge( $email, [ 'width' => 'half' ] ),
				'company'           => $co,
				'country'           => [
					'type'         => 'select',
					'label'        => __( 'Country', 'techdella' ),
					'required'     => true,
					'options'      => function_exists( 'td_quote_countries' ) ? td_quote_countries() : [],
					'autocomplete' => 'country',
					'width'        => 'half',
					'sb'           => 'country',
				],
				'phone'             => [
					'type'         => 'tel',
					'label'        => __( 'Phone (with country code)', 'techdella' ),
					'required'     => true,
					'max'          => 24,
					'placeholder'  => '+234 803 000 0000',
					'autocomplete' => 'tel',
					'sb'           => 'phone',
				],
				'package'           => [
					'type'     => 'select',
					'label'    => __( 'Package', 'techdella' ),
					'required' => true,
					'options'  => function_exists( 'td_quote_packages' ) ? td_quote_packages() : [],
					'sb'       => 'package',
				],
				'notes'             => [
					'type'     => 'textarea',
					'label'    => __( 'Anything we should know? (optional)', 'techdella' ),
					'required' => false,
					'max'      => 2000,
					'rows'     => 3,
					'sb'       => 'notes',
				],
				'consent_marketing' => td_forms_consent_field(),
			],
		],
		'guest_post'    => [
			'name'          => 'Techdella · Write for us',
			'sites'         => [ 'journal' ],
			'title'         => __( 'Pitch a *post*', 'techdella' ),
			'intro'         => __( 'We edit every guest post and reply to every pitch within two weeks.', 'techdella' ),
			'submit'        => __( 'Send pitch', 'techdella' ),
			'success_title' => __( 'Pitch *received*', 'techdella' ),
			'success'       => __( 'Thanks. An editor will reply within two weeks.', 'techdella' ),
			'fields'        => [
				'name'    => $name,
				'email'   => array_merge( $email, [ 'width' => 'half' ] ),
				'topic'   => [
					'type'     => 'text',
					'label'    => __( 'Proposed topic', 'techdella' ),
					'required' => true,
					'max'      => 200,
					'sb'       => 'topic',
				],
				'outline' => [
					'type'     => 'textarea',
					'label'    => __( 'Outline', 'techdella' ),
					'help'     => __( 'The angle, the main points, and what first-hand experience you bring.', 'techdella' ),
					'required' => true,
					'max'      => 4000,
					'rows'     => 5,
					'sb'       => 'outline',
				],
				'samples' => [
					'type'     => 'textarea',
					'label'    => __( 'Writing samples', 'techdella' ),
					'help'     => __( 'Links to two or three published pieces, one per line.', 'techdella' ),
					'required' => true,
					'max'      => 1000,
					'rows'     => 3,
					'sb'       => 'samples',
				],
			],
		],
	];
}
