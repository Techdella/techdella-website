<?php
/**
 * Team how-tos inside the admin (§14.2): admin.php?page=td-help.
 * Loom links are placeholders until the team records them.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'index.php', __( 'How to', 'techdella' ), __( 'How to', 'techdella' ), 'read', 'td-help', 'td_render_help_page' );
	}
);

/**
 * How-to sections.
 *
 * @return array<string,array{title:string,steps:string[]}>
 */
function td_help_sections(): array {
	$sections = [
		'guide'      => [
			'title' => __( 'Publish a guide', 'techdella' ),
			'steps' => [
				__( 'Content → Guides → Write a guide. The page opens with the TL;DR, an intro paragraph and the author box already in place.', 'techdella' ),
				__( 'Fill the TL;DR, primary keyword, reviewer and last reviewed date in the fields below the editor.', 'techdella' ),
				__( 'Write the answer-first intro using the primary keyword, add your own example or screenshot, and link one service and three glossary terms.', 'techdella' ),
				__( 'Write the meta description (140–155 characters) in the Rank Math box.', 'techdella' ),
				__( 'Open "Pre-publish checklist" in the sidebar. When every line is done, an Editor can publish. Writers use "Submit for review".', 'techdella' ),
			],
		],
		'case-study' => [
			'title' => __( 'Add a case study', 'techdella' ),
			'steps' => [
				__( 'Content → Case studies → Add case study.', 'techdella' ),
				__( 'Fill client, industry, country, the three metrics (client-confirmed numbers only), challenge, approach and results.', 'techdella' ),
				__( 'Paste the quote verbatim. Never edit it.', 'techdella' ),
				__( 'Tick "Client permission confirmed" only when the client approved the page in writing. Without it the case study cannot be published.', 'techdella' ),
				__( 'On Studios, add the "Studios" division so it shows on studios.techdella.com.', 'techdella' ),
			],
		],
		'hero-image' => [
			'title' => __( 'Swap a hero image', 'techdella' ),
			'steps' => [
				__( 'Media → Add New: upload the image (at least 2400px wide, warm and real, no stock clichés).', 'techdella' ),
				__( 'Write alt text that describes what is shown. Do not stuff keywords.', 'techdella' ),
				__( 'Open the page, select the hero block, and choose the new image in the block sidebar.', 'techdella' ),
				__( 'Update, then check the page on a phone.', 'techdella' ),
			],
		],
		'popup'      => [
			'title' => __( 'Create a popup', 'techdella' ),
			'steps' => [
				__( 'Growth → Popups → Add new.', 'techdella' ),
				__( 'Pick the type (slide-in, top bar, exit intent on desktop only), the trigger and the pages it targets.', 'techdella' ),
				__( 'Link the offer (a resource or the newsletter) and the form key.', 'techdella' ),
				__( 'Never target booking, audit, start, thank-you or tool pages. On mobile only the slide-in or top bar is allowed.', 'techdella' ),
			],
		],
		'prices'     => [
			'title' => __( 'Change a price', 'techdella' ),
			'steps' => [
				__( 'Growth → Prices.', 'techdella' ),
				__( 'Type the new dollar amount. Leave naira empty to use dollars × 200 (never below ₦300,000), or type your own naira price.', 'techdella' ),
				__( 'Save prices. Every page showing that price updates. Empty both boxes to go back to the default.', 'techdella' ),
			],
		],
		'refresh'    => [
			'title' => __( 'Refresh due list', 'techdella' ),
			'steps' => [
				__( 'The dashboard lists items whose last review is older than their cadence: money pages every 3 months, guides and glossary every 6 months.', 'techdella' ),
				__( 'After a review, set "Last reviewed" and "Reviewer" on the item. It drops off the list.', 'techdella' ),
				__( 'The Search Console decay list (clicks down 30% over 90 days) comes from the WP-CLI command wp td audit decay.', 'techdella' ),
			],
		],
	];
	return (array) apply_filters( 'td_help_sections', $sections );
}

/**
 * Render the help page.
 */
function td_render_help_page(): void {
	echo '<div class="wrap td-help"><h1>' . esc_html__( 'How to', 'techdella' ) . '</h1>';
	echo '<p>' . esc_html__( 'Short guides for the team. Each one will get a 2-minute Loom video.', 'techdella' ) . '</p>';
	foreach ( td_help_sections() as $id => $section ) {
		echo '<section class="td-help__card" id="' . esc_attr( $id ) . '"><h2>' . esc_html( $section['title'] ) . '</h2><ol>';
		foreach ( $section['steps'] as $step ) {
			echo '<li>' . esc_html( $step ) . '</li>';
		}
		echo '</ol><p>' . esc_html__( 'Video:', 'techdella' ) . ' ' . td_mark_placeholders( esc_html__( 'Loom link [team to record]', 'techdella' ) ) . '</p></section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped before marking.
	}
	echo '</div>';
}
