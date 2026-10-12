<?php
/**
 * Startbuddi live chat (owner, 12 Oct 2026) on all three sites.
 *
 * The widget key is public (it is printed in every page's HTML), so the default lives here;
 * SB_CHAT_KEY in wp-config.php overrides it, and an empty value turns the chat off. The script is
 * injected after the first interaction or 4 seconds, never in the critical path (§8.4 LCP/INP).
 * Off locally (offline tests) unless SB_CHAT_KEY is set, and off in the admin.
 *
 * @package techdella-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The chat widget key, or '' when the chat is off.
 */
function td_chat_key(): string {
	if ( defined( 'SB_CHAT_KEY' ) ) {
		return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) SB_CHAT_KEY );
	}
	$env = getenv( 'SB_CHAT_KEY' );
	if ( false !== $env ) {
		return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $env );
	}
	return 'local' === TD_ENV ? '' : 'bve22QQUmfrHVEdn08b9O1qvA3yh1JAT';
}

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( '' !== td_chat_key() ) {
			$classes[] = 'td-has-chat';
		}
		return $classes;
	}
);

add_action(
	'wp_footer',
	static function () {
		$key = td_chat_key();
		if ( '' === $key || is_admin() ) {
			return;
		}
		$src = 'https://app.startbuddi.com/api/v1.js?key=' . rawurlencode( $key );
		?>
<script>
(function(){var done=false,ev=['pointerdown','keydown','scroll','touchstart'];
function load(){if(done)return;done=true;ev.forEach(function(e){removeEventListener(e,load,true)});
var s=document.createElement('script');s.async=true;s.src=<?php echo wp_json_encode( $src ); ?>;document.body.appendChild(s);}
ev.forEach(function(e){addEventListener(e,load,{capture:true,passive:true,once:true})});
addEventListener('load',function(){setTimeout(load,4000)});})();
</script>
		<?php
	},
	50
);
