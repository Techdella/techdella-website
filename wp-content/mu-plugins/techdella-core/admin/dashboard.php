<?php
/**
 * "TechDella HQ" dashboard (§14.2): replaces every default widget with
 * quick actions, the review queue, refresh-due list, last published, a
 * site-health strip and the Startbuddi link.
 */

defined( 'ABSPATH' ) || exit;

use TechDella\Core\Admin\Refresh;
use TechDella\Core\Cpt\Registry;

/**
 * Quick actions valid on this site for the current user.
 *
 * @return array<int,array{label:string,url:string}>
 */
function td_hq_quick_actions(): array {
	$types   = Registry::for_site( td_site() );
	$actions = [
		[ 'Write a guide', 'post', td_is_site( 'journal' ) ],
		[ 'Add a glossary term', 'em-glossary', true ],
		[ 'Add a case study', 'case_study', true ],
		[ 'Add work (Studios)', 'studio_work', true ],
		[ 'New resource', 'td_resource', true ],
	];
	$out     = [];
	foreach ( $actions as [ $label, $type, $site_ok ] ) {
		$object = get_post_type_object( $type );
		if ( $site_ok && isset( $types[ $type ] ) && $object && current_user_can( $object->cap->create_posts ) ) {
			$out[] = [
				'label' => $label,
				'url'   => admin_url( 'post-new.php' . ( 'post' === $type ? '' : '?post_type=' . $type ) ),
			];
		}
	}
	/**
	 * Admin URL of the popups screen (popups module). Empty hides the action.
	 *
	 * @param string $url URL.
	 */
	$popups = (string) apply_filters( 'td_popups_admin_url', post_type_exists( 'td_popup' ) ? admin_url( 'edit.php?post_type=td_popup' ) : '' );
	if ( '' !== $popups && ( current_user_can( 'td_manage_popups' ) || current_user_can( 'manage_options' ) ) ) {
		$out[] = [
			'label' => 'Edit popups',
			'url'   => $popups,
		];
	}
	return $out;
}

/**
 * Post types shown in the dashboard queues (everything with a menu group).
 *
 * @return string[]
 */
function td_hq_post_types(): array {
	return array_values(
		array_filter(
			array_keys( Registry::for_site( td_site() ) ),
			static fn( $t ) => post_type_exists( $t ) && ! in_array( $t, [ 'document' ], true )
		)
	);
}

/**
 * Items due for a refresh (cached for an hour).
 *
 * @return array<int,array{id:int,title:string,type:string,days_overdue:int,reviewed:bool,due_at:int}>
 */
function td_hq_refresh_due(): array {
	$cached = get_transient( 'td_hq_refresh_due' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$items = [];
	$now   = time();
	foreach ( Registry::for_site( td_site() ) as $type => $def ) {
		$months = (int) ( $def['cadence'] ?? 0 );
		if ( $months <= 0 || ! post_type_exists( $type ) ) {
			continue;
		}
		$ids = get_posts(
			[
				'post_type'        => $type,
				'post_status'      => 'publish',
				'posts_per_page'   => 500,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			]
		);
		foreach ( $ids as $id ) {
			$status = Refresh::status( get_post_meta( (int) $id, 'last_reviewed', true ), (string) get_post_field( 'post_modified_gmt', (int) $id ), $months, $now );
			if ( $status['due'] ) {
				$items[] = [
					'id'    => (int) $id,
					'title' => get_the_title( (int) $id ),
					'type'  => $type,
				] + $status;
			}
		}
	}
	$items = Refresh::top( $items, 10 );
	set_transient( 'td_hq_refresh_due', $items, HOUR_IN_SECONDS );
	return $items;
}

/**
 * Normalise a health result from another module into rows.
 *
 * @param mixed  $result Result (bool | {ok,label} | list of those | [label => bool]).
 * @param string $label  Default label.
 * @return array<int,array{ok:bool|null,label:string}>
 */
function td_hq_health_rows( $result, string $label ): array {
	if ( is_bool( $result ) ) {
		return [
			[
				'ok'    => $result,
				'label' => $label,
			],
		];
	}
	if ( ! is_array( $result ) ) {
		return [];
	}
	if ( array_key_exists( 'ok', $result ) ) {
		return [
			[
				'ok'    => (bool) $result['ok'],
				'label' => (string) ( $result['label'] ?? $result['message'] ?? $label ),
			],
		];
	}
	$rows = [];
	foreach ( $result as $key => $row ) {
		if ( is_bool( $row ) ) {
			$rows[] = [
				'ok'    => $row,
				'label' => is_string( $key ) ? $key : $label,
			];
		} elseif ( is_array( $row ) ) {
			$rows = array_merge( $rows, td_hq_health_rows( $row, is_string( $key ) ? $key : $label ) );
		}
	}
	return $rows;
}

/**
 * Site-health rows for the strip.
 *
 * @return array<int,array{ok:bool|null,label:string}>
 */
function td_hq_health(): array {
	$rows = [];
	foreach ( [
		'td_redirects_health' => __( 'Redirects', 'techdella' ),
		'td_seo_health'       => __( 'SEO / sitemap', 'techdella' ),
	] as $fn => $label ) {
		if ( function_exists( $fn ) ) {
			$rows = array_merge( $rows, td_hq_health_rows( call_user_func( $fn ), $label ) );
		} else {
			$rows[] = [
				'ok'    => null,
				/* translators: %s: check name. */
				'label' => sprintf( __( '%s: check not available yet', 'techdella' ), $label ),
			];
		}
	}
	$psi = td_psi_summary();
	$rows[] = [
		'ok'    => $psi['ok'],
		'label' => $psi['label'],
	];
	return $rows;
}

/**
 * PageSpeed Insights summary for the homepage (cached daily by cron).
 *
 * @return array{ok:bool|null,label:string}
 */
function td_psi_summary(): array {
	if ( '' === (string) get_option( 'td_psi_api_key', '' ) ) {
		return [
			'ok'    => null,
			'label' => __( 'PageSpeed (home): not configured — set the td_psi_api_key option', 'techdella' ),
		];
	}
	$result = get_option( 'td_psi_result' );
	if ( ! is_array( $result ) || ! isset( $result['score'] ) ) {
		return [
			'ok'    => null,
			'label' => __( 'PageSpeed (home): first daily check pending', 'techdella' ),
		];
	}
	$score = (int) round( (float) $result['score'] * 100 );
	return [
		'ok'    => $score >= 90,
		/* translators: 1: score, 2: LCP, 3: date. */
		'label' => sprintf( __( 'PageSpeed (home, mobile): %1$d · LCP %2$s · %3$s', 'techdella' ), $score, (string) ( $result['lcp'] ?? '–' ), wp_date( 'j M', (int) ( $result['fetched'] ?? time() ) ) ),
	];
}

/**
 * Daily PSI fetch (only when an API key is set).
 */
function td_psi_refresh(): void {
	$key = (string) get_option( 'td_psi_api_key', '' );
	if ( '' === $key ) {
		return;
	}
	$url      = add_query_arg(
		[
			'url'      => rawurlencode( home_url( '/' ) ),
			'strategy' => 'mobile',
			'category' => 'performance',
			'key'      => rawurlencode( $key ),
		],
		'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'
	);
	$response = wp_remote_get( $url, [ 'timeout' => 60 ] );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return;
	}
	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	$lh   = is_array( $body ) ? (array) ( $body['lighthouseResult'] ?? [] ) : [];
	if ( ! isset( $lh['categories']['performance']['score'] ) ) {
		return;
	}
	update_option(
		'td_psi_result',
		[
			'score'   => (float) $lh['categories']['performance']['score'],
			'lcp'     => (string) ( $lh['audits']['largest-contentful-paint']['displayValue'] ?? '' ),
			'cls'     => (string) ( $lh['audits']['cumulative-layout-shift']['displayValue'] ?? '' ),
			'fetched' => time(),
		],
		false
	);
}
add_action( 'td_psi_refresh', 'td_psi_refresh' );
add_action(
	'admin_init',
	static function () {
		if ( '' !== (string) get_option( 'td_psi_api_key', '' ) && ! wp_next_scheduled( 'td_psi_refresh' ) ) {
			wp_schedule_event( time() + 60, 'daily', 'td_psi_refresh' );
		}
	}
);

// Clear the refresh cache when content changes.
add_action( 'save_post', static fn() => delete_transient( 'td_hq_refresh_due' ) );

/**
 * Edit link or plain title.
 *
 * @param int $id Post ID.
 */
function td_hq_post_link( int $id ): string {
	$title = get_the_title( $id );
	$title = '' === $title ? __( '(no title)', 'techdella' ) : $title;
	$link  = get_edit_post_link( $id );
	return $link ? '<a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a>' : esc_html( $title );
}

/**
 * Label of a post type.
 *
 * @param string $type Post type.
 */
function td_hq_type_label( string $type ): string {
	$object = get_post_type_object( $type );
	return $object ? (string) $object->labels->singular_name : $type;
}

/**
 * Widget: TechDella HQ (quick actions + Startbuddi).
 */
function td_hq_widget_main(): void {
	$actions = td_hq_quick_actions();
	echo '<div class="td-hq">';
	if ( $actions ) {
		echo '<ul class="td-hq__actions">';
		foreach ( $actions as $action ) {
			echo '<li><a class="button button-primary td-hq__action" href="' . esc_url( $action['url'] ) . '">' . esc_html( $action['label'] ) . '</a></li>';
		}
		echo '</ul>';
	}
	$sb = (string) get_option( 'td_startbuddi_url', 'https://startbuddi.com' ); // [CONFIRM] workspace URL.
	echo '<p class="td-hq__crm"><a href="' . esc_url( $sb ) . '" target="_blank" rel="noopener">' . esc_html__( 'Leads & CRM live in Startbuddi →', 'techdella' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'techdella' ) . '</span></a></p>';
	echo '<p class="description">' . esc_html__( 'WordPress stores no leads: every form forwards to Startbuddi (D7).', 'techdella' ) . '</p>';
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=td-help' ) ) . '">' . esc_html__( 'How-to guides for the team', 'techdella' ) . '</a></p>';
	echo '</div>';
}

/**
 * Widget: Needs review.
 */
function td_hq_widget_review(): void {
	$query = new WP_Query(
		[
			'post_type'      => td_hq_post_types(),
			'post_status'    => 'pending',
			'posts_per_page' => 10,
			'orderby'        => 'modified',
			'order'          => 'ASC',
			'no_found_rows'  => false,
		]
	);
	if ( ! $query->have_posts() ) {
		echo '<p>' . esc_html__( 'Nothing waiting for review.', 'techdella' ) . '</p>';
		return;
	}
	/* translators: %d: number of items. */
	echo '<p>' . esc_html( sprintf( _n( '%d item is waiting for an Editor.', '%d items are waiting for an Editor.', (int) $query->found_posts, 'techdella' ), (int) $query->found_posts ) ) . '</p><ul class="td-hq__list">';
	foreach ( $query->posts as $post ) {
		$author = get_the_author_meta( 'display_name', (int) $post->post_author );
		/* translators: 1: type, 2: author. */
		echo '<li>' . td_hq_post_link( (int) $post->ID ) . ' <span class="td-hq__meta">' . esc_html( sprintf( __( '%1$s · by %2$s', 'techdella' ), td_hq_type_label( $post->post_type ), $author ) ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- td_hq_post_link escapes.
	}
	echo '</ul>';
}

/**
 * Widget: Refresh due.
 */
function td_hq_widget_refresh(): void {
	echo '<p class="description">' . esc_html__( 'From last_reviewed (or the last update): money pages every 3 months, guides and glossary every 6 months.', 'techdella' ) . '</p>';
	$items = td_hq_refresh_due();
	if ( ! $items ) {
		echo '<p>' . esc_html__( 'Nothing is due for a refresh.', 'techdella' ) . '</p>';
	} else {
		echo '<ul class="td-hq__list">';
		foreach ( $items as $item ) {
			$note = $item['reviewed']
				/* translators: %d: days. */
				? sprintf( _n( 'review %d day overdue', 'review %d days overdue', $item['days_overdue'], 'techdella' ), $item['days_overdue'] )
				/* translators: %d: days. */
				: sprintf( _n( 'never reviewed · %d day overdue', 'never reviewed · %d days overdue', $item['days_overdue'], 'techdella' ), $item['days_overdue'] );
			echo '<li>' . td_hq_post_link( $item['id'] ) . ' <span class="td-hq__meta">' . esc_html( td_hq_type_label( $item['type'] ) . ' · ' . $note ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- td_hq_post_link escapes.
		}
		echo '</ul>';
	}
	echo '<p class="td-hq__meta">' . esc_html__( 'GSC decay list (clicks down 30% over 90 days): coming with', 'techdella' ) . ' <code>wp td audit decay</code> — ' . '<a href="' . esc_url( admin_url( 'admin.php?page=td-help#refresh' ) ) . '">' . esc_html__( 'how it works', 'techdella' ) . '</a>.</p>';
}

/**
 * Widget: Last published.
 */
function td_hq_widget_published(): void {
	$posts = get_posts(
		[
			'post_type'      => td_hq_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'orderby'        => 'date',
			'order'          => 'DESC',
		]
	);
	if ( ! $posts ) {
		echo '<p>' . esc_html__( 'Nothing published yet.', 'techdella' ) . '</p>';
		return;
	}
	echo '<ul class="td-hq__list">';
	foreach ( $posts as $post ) {
		echo '<li>' . td_hq_post_link( (int) $post->ID ) . ' <span class="td-hq__meta">' . esc_html( td_hq_type_label( $post->post_type ) . ' · ' . get_the_date( 'j M Y', $post ) ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- td_hq_post_link escapes.
	}
	echo '</ul>';
}

/**
 * Widget: Site health strip + setup checklist.
 */
function td_hq_widget_health(): void {
	echo '<ul class="td-hq__health">';
	foreach ( td_hq_health() as $row ) {
		$state = null === $row['ok'] ? 'unknown' : ( $row['ok'] ? 'ok' : 'bad' );
		$word  = [
			'ok'      => __( 'OK', 'techdella' ),
			'bad'     => __( 'Check', 'techdella' ),
			'unknown' => __( 'n/a', 'techdella' ),
		][ $state ];
		echo '<li class="td-hq__health-item td-hq__health-item--' . esc_attr( $state ) . '"><strong>' . esc_html( $word ) . '</strong> ' . esc_html( $row['label'] ) . '</li>';
	}
	echo '</ul>';
	if ( current_user_can( 'manage_options' ) ) {
		$checks = [
			[ td_acf_pro_active(), __( 'ACF Pro licence active (D4) [CONFIRM licence] — without it, list fields use the JSON box.', 'techdella' ) ],
			[ defined( 'RANK_MATH_VERSION' ), __( 'Rank Math active (titles, meta, sitemaps).', 'techdella' ) ],
			[ defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT, __( 'Theme/plugin file editor disabled.', 'techdella' ) ],
			[ null, __( '2FA on for every admin and editor (WP 2FA plugin or Hostinger) [CONFIRM].', 'techdella' ) ],
		];
		echo '<h3 class="td-hq__sub">' . esc_html__( 'Setup', 'techdella' ) . '</h3><ul class="td-hq__health">';
		foreach ( $checks as [ $ok, $label ] ) {
			$state = null === $ok ? 'unknown' : ( $ok ? 'ok' : 'bad' );
			echo '<li class="td-hq__health-item td-hq__health-item--' . esc_attr( $state ) . '"><strong>' . esc_html( null === $ok ? __( 'To do', 'techdella' ) : ( $ok ? __( 'OK', 'techdella' ) : __( 'Check', 'techdella' ) ) ) . '</strong> ' . esc_html( $label ) . '</li>';
		}
		echo '</ul>';
	}
}

add_action(
	'wp_dashboard_setup',
	static function () {
		global $wp_meta_boxes;
		$wp_meta_boxes['dashboard'] = []; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Replacing the default widgets is the point (§14.2).
		remove_action( 'welcome_panel', 'wp_welcome_panel' );

		wp_add_dashboard_widget( 'td_hq_main', __( 'TechDella HQ', 'techdella' ), 'td_hq_widget_main', null, null, 'normal', 'high' );
		wp_add_dashboard_widget( 'td_hq_review', __( 'Needs review', 'techdella' ), 'td_hq_widget_review', null, null, 'normal', 'high' );
		wp_add_dashboard_widget( 'td_hq_refresh', __( 'Refresh due', 'techdella' ), 'td_hq_widget_refresh', null, null, 'side', 'high' );
		wp_add_dashboard_widget( 'td_hq_published', __( 'Last published', 'techdella' ), 'td_hq_widget_published', null, null, 'normal', 'default' );
		wp_add_dashboard_widget( 'td_hq_health', __( 'Site health', 'techdella' ), 'td_hq_widget_health', null, null, 'side', 'default' );
	},
	9999
);

add_filter(
	'admin_title',
	static function ( $title ) {
		global $pagenow;
		return 'index.php' === $pagenow ? str_replace( __( 'Dashboard' ), __( 'TechDella HQ', 'techdella' ), (string) $title ) : $title; // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Core string.
	}
);
