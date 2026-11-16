<?php
/**
 * Exotic Cars site chrome: header with language + currency switchers and the side drawer menu (the footer is drawn by orego-footer-builder.php),
 * and the currency converter used by every price on the website.
 * Shortcodes: [orego_header]
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	register_nav_menus( array( 'orego_footer' => 'Footer links' ) );
}, 20 );

/* ------------------------------------------------------------------ */
/* Data                                                                */
/* ------------------------------------------------------------------ */

function orego_flag( $code ) {
	$f = array(
		'ar' => '<rect width="32" height="32" fill="#006C35"/><path d="M7 11.5c1.6-1.4 3.2 1 4.8-.4s3.2 1 4.8-.4 3.2 1 4.8-.4 2.6.6 3.6.8M8.5 15c1.4-1 2.8.6 4.2-.3s2.8.6 4.2-.3 2.8.6 4.2-.3" stroke="#FFFFFF" stroke-width="1.4" fill="none" stroke-linecap="round"/><path d="M8 20.5h15.5" stroke="#FFFFFF" stroke-width="1.6" stroke-linecap="round"/><path d="m23 19 2.6 1.5L23 22z" fill="#FFFFFF"/>',
		'ary' => '<rect width="32" height="32" fill="#C1272D"/><path d="m16 8.5 2.4 7.3h-4.8L19.9 11H12.1l6.3 4.8z" fill="none" stroke="#006233" stroke-width="1.4" stroke-linejoin="round" transform="translate(0 2)"/>',
		'en' => '<rect width="32" height="32" fill="#012169"/><path d="M0 0 32 32M32 0 0 32" stroke="#FFFFFF" stroke-width="6"/><path d="M0 0 32 32M32 0 0 32" stroke="#C8102E" stroke-width="2"/><path d="M16 0v32M0 16h32" stroke="#FFFFFF" stroke-width="10"/><path d="M16 0v32M0 16h32" stroke="#C8102E" stroke-width="6"/>',
		'fr' => '<rect width="11" height="32" fill="#0055A4"/><rect x="11" width="10" height="32" fill="#FFFFFF"/><rect x="21" width="11" height="32" fill="#EF4135"/>',
		'es' => '<rect width="32" height="32" fill="#AA151B"/><rect y="8" width="32" height="16" fill="#F1BF00"/>',
		'de' => '<rect width="32" height="11" fill="#000000"/><rect y="11" width="32" height="10" fill="#DD0000"/><rect y="21" width="32" height="11" fill="#FFCE00"/>',
		'it' => '<rect width="11" height="32" fill="#009246"/><rect x="11" width="10" height="32" fill="#FFFFFF"/><rect x="21" width="11" height="32" fill="#CE2B37"/>',
		'pt' => '<rect width="32" height="32" fill="#FF0000"/><rect width="13" height="32" fill="#006600"/><circle cx="13" cy="16" r="5" fill="#FFCC00"/><circle cx="13" cy="16" r="3" fill="#FFFFFF"/>',
		'nl' => '<rect width="32" height="11" fill="#AE1C28"/><rect y="11" width="32" height="10" fill="#FFFFFF"/><rect y="21" width="32" height="11" fill="#21468B"/>',
		'ru' => '<rect width="32" height="11" fill="#FFFFFF"/><rect y="11" width="32" height="10" fill="#0039A6"/><rect y="21" width="32" height="11" fill="#D52B1E"/>',
		'zh-CN' => '<rect width="32" height="32" fill="#DE2910"/><path d="m10 5 1.8 5.4h5.7l-4.6 3.3 1.8 5.4L10 15.8l-4.7 3.3 1.8-5.4-4.6-3.3h5.7z" fill="#FFDE00"/><circle cx="20" cy="6" r="1.3" fill="#FFDE00"/><circle cx="23" cy="10" r="1.3" fill="#FFDE00"/><circle cx="23" cy="15" r="1.3" fill="#FFDE00"/><circle cx="20" cy="19" r="1.3" fill="#FFDE00"/>',
		'USD' => '<rect width="32" height="32" fill="#FFFFFF"/><path d="M0 2.3h32M0 6.9h32M0 11.4h32M0 16h32M0 20.6h32M0 25.1h32M0 29.7h32" stroke="#B22234" stroke-width="2.3"/><rect width="15" height="16" fill="#3C3B6E"/><g fill="#FFFFFF"><circle cx="3.5" cy="3.5" r="1"/><circle cx="8" cy="3.5" r="1"/><circle cx="12" cy="3.5" r="1"/><circle cx="5.5" cy="7.5" r="1"/><circle cx="10" cy="7.5" r="1"/><circle cx="3.5" cy="11.5" r="1"/><circle cx="8" cy="11.5" r="1"/><circle cx="12" cy="11.5" r="1"/></g>',
		'MAD' => '<rect width="32" height="32" fill="#C1272D"/><path d="m16 8.5 2.4 7.3h-4.8L19.9 11H12.1l6.3 4.8z" fill="none" stroke="#006233" stroke-width="1.4" stroke-linejoin="round" transform="translate(0 2)"/>',
		'EUR' => '<rect width="32" height="32" fill="#003399"/><g fill="#FFCC00"><circle cx="16" cy="7" r="1.3"/><circle cx="20.5" cy="8.2" r="1.3"/><circle cx="23.8" cy="11.5" r="1.3"/><circle cx="25" cy="16" r="1.3"/><circle cx="23.8" cy="20.5" r="1.3"/><circle cx="20.5" cy="23.8" r="1.3"/><circle cx="16" cy="25" r="1.3"/><circle cx="11.5" cy="23.8" r="1.3"/><circle cx="8.2" cy="20.5" r="1.3"/><circle cx="7" cy="16" r="1.3"/><circle cx="8.2" cy="11.5" r="1.3"/><circle cx="11.5" cy="8.2" r="1.3"/></g>',
	);
	if ( ! isset( $f[ $code ] ) && function_exists( 'orego_currency_flag_inner' ) && preg_match( '/^[A-Z]{3}$/', (string) $code ) ) {
		$f[ $code ] = orego_currency_flag_inner( $code ); // added currencies: their image, or a badge with the symbol
	}
	if ( ! isset( $f[ $code ] ) && preg_match( '/^[a-z]{2,3}(-[A-Za-z]{2})?$/', (string) $code ) && file_exists( WP_PLUGIN_DIR . '/gtranslate/flags/svg/' . $code . '.svg' ) ) {
		return '<img class="og-flagimg" src="' . esc_url( plugins_url( 'gtranslate/flags/svg/' . $code . '.svg' ) ) . '" alt="" aria-hidden="true" loading="lazy">';
	}
	return '<svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">' . ( $f[ $code ] ?? '' ) . '</svg>';
}

function orego_langs() {
	return function_exists( 'orego_enabled_langs' ) ? orego_enabled_langs() : array( 'en' => array( 'English', 'EN' ) );
}

function orego_rates() {
	if ( function_exists( 'orego_currency_list' ) ) { // Customize -> Currencies
		$out = array( 'USD' => 1 );
		foreach ( orego_currency_enabled() as $c ) {
			$out[ $c['code'] ] = (float) $c['rate'];
		}
		$out['default'] = orego_currency_default();
		$out['booking'] = $out['default'];
		return $out;
	}
	$r = get_option( 'orego_rates' );
	$r = is_array( $r ) ? $r : array();
	return array(
		'USD'     => 1,
		'MAD'     => isset( $r['MAD'] ) && (float) $r['MAD'] > 0 ? (float) $r['MAD'] : 9.2,
		'EUR'     => isset( $r['EUR'] ) && (float) $r['EUR'] > 0 ? (float) $r['EUR'] : 0.86,
		'default' => in_array( $r['default'] ?? 'USD', array( 'USD', 'MAD', 'EUR' ), true ) ? ( $r['default'] ?? 'USD' ) : 'USD',
		'booking' => in_array( $r['booking'] ?? 'USD', array( 'USD', 'MAD', 'EUR' ), true ) ? ( $r['booking'] ?? 'USD' ) : 'USD',
	);
}

function orego_currencies() {
	if ( function_exists( 'orego_currency_enabled' ) ) { // Customize -> Currencies
		$out = array();
		foreach ( orego_currency_enabled() as $c ) {
			$out[ $c['code'] ] = array( $c['name'], $c['symbol'] );
		}
		return $out;
	}
	return array( 'USD' => array( 'US Dollar', '$' ), 'MAD' => array( 'Moroccan Dirham', 'MAD' ), 'EUR' => array( 'Euro', '€' ) );
}

function orego_chrome_ic( $n ) {
	$p = array(
		'chev'  => '<path d="m6 9 6 6 6-6"/>',
		'check' => '<path d="M20 6 9 17l-5-5"/>',
		'x'     => '<path d="M18 6 6 18M6 6l12 12"/>',
		'side'  => '<path d="m15 18-6-6 6-6"/>',
		'car'   => '<path d="M5 17H3v-5l2.2-5.5A2 2 0 0 1 7 5.2h9.6a2 2 0 0 1 1.8 1.1L21 12v5h-2"/><path d="M3.5 12h17"/><circle cx="7.5" cy="17.5" r="2.2"/><circle cx="16.5" cy="17.5" r="2.2"/><path d="M9.7 17.5h4.6"/>',
	);
	return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $n ] . '</svg>';
}

function orego_chrome_logo() {
	$img = (int) orego_mod( 'logo_image' );
	if ( ! $img ) {
		$img = (int) get_theme_mod( 'custom_logo' );
	}
	$name  = trim( (string) orego_mod( 'logo_text' ) );
	$label = '' !== $name ? $name : get_bloginfo( 'name' );
	if ( $img && wp_attachment_is_image( $img ) ) {
		$inner = wp_get_attachment_image( $img, 'medium', false, array( 'class' => 'ogx2-logoimg', 'alt' => $label, 'loading' => 'eager' ) );
	} else {
		$tag   = trim( (string) orego_mod( 'logo_tagline' ) );
		$inner = ( orego_mod( 'logo_icon' ) ? '<span class="ogx2-mark">' . str_replace( 'width="18" height="18"', 'width="40" height="40"', orego_chrome_ic( 'car' ) ) . '</span>' : '' )
			. '<span class="ogx2-word"><b>' . esc_html( $label ) . '</b>' . ( '' !== $tag ? '<small>' . esc_html( $tag ) . '</small>' : '' ) . '</span>';
	}
	return '<a class="ogx2-logo notranslate" href="' . esc_url( orego_purl( 'cars' ) ) . '" aria-label="' . esc_attr( $label ) . '">' . $inner . '</a>';
}

/** Menu items as a tree, with "current" flags. */
function orego_menu_tree( $location, $fallback_name ) {
	$locs = get_nav_menu_locations();
	$menu = ! empty( $locs[ $location ] ) ? wp_get_nav_menu_object( $locs[ $location ] ) : wp_get_nav_menu_object( $fallback_name );
	if ( ! $menu ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $menu->term_id );
	if ( ! $items ) {
		return array();
	}
	$qid  = get_queried_object_id();
	$here = untrailingslashit( strtok( ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . ( $_SERVER['REQUEST_URI'] ?? '' ), '#' ) ); // phpcs:ignore
	$tree = array();
	$kids = array();
	foreach ( $items as $it ) {
		$cur = ( 'post_type' === $it->type && (int) $it->object_id === (int) $qid && $qid ) || ( 'custom' === $it->type && untrailingslashit( $it->url ) === $here );
		$row = array( 'title' => $it->title, 'url' => $it->url, 'current' => $cur, 'kids' => array() );
		if ( $it->menu_item_parent ) {
			$kids[ (int) $it->menu_item_parent ][] = $row;
		} else {
			$tree[ $it->ID ] = $row;
		}
	}
	foreach ( $kids as $pid => $list ) {
		if ( isset( $tree[ $pid ] ) ) {
			$tree[ $pid ]['kids'] = $list;
			foreach ( $list as $k ) {
				if ( $k['current'] ) {
					$tree[ $pid ]['current'] = true;
				}
			}
		}
	}
	return array_values( $tree );
}

/* ------------------------------------------------------------------ */
/* Header                                                              */
/* ------------------------------------------------------------------ */

add_shortcode( 'orego_header', function () {
	$langs = orego_langs();
	$curs  = orego_currencies();
	$rates = orego_rates();
	$tree  = orego_menu_tree( 'orego_main', 'Main Menu' );
	$cfg   = function_exists( 'orego_currency_js_cfg' ) ? orego_currency_js_cfg() : array( 'rates' => array( 'USD' => 1, 'MAD' => $rates['MAD'], 'EUR' => $rates['EUR'] ), 'def' => $rates['default'] );
	ob_start();
	?>
<?php echo orego_promo_html(); // phpcs:ignore ?>
<?php echo orego_hb_header(); // phpcs:ignore ?>
<div class="ogx2-scrim" hidden></div>
<aside class="ogx2-drawer" id="ogx2-drawer" aria-label="Menu" aria-hidden="true" inert>
	<div class="ogx2-dh"><?php echo orego_chrome_logo(); // phpcs:ignore ?><button type="button" class="ogx2-close" aria-label="Close menu"><?php echo orego_chrome_ic( 'x' ); // phpcs:ignore ?></button></div>
	<nav class="ogx2-nav" aria-label="Main menu">
		<ul>
		<?php foreach ( $tree as $i => $it ) : ?>
			<li class="<?php echo $it['current'] ? 'is-current' : ''; ?>">
				<div class="ogx2-row">
					<a href="<?php echo esc_url( $it['url'] ); ?>"<?php echo $it['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $it['title'] ); ?></a>
					<?php if ( $it['kids'] ) : ?>
						<button type="button" class="ogx2-more" aria-expanded="false" aria-controls="ogx2-sub-<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( 'Show ' . $it['title'] ); ?>"><?php echo orego_chrome_ic( 'side' ); // phpcs:ignore ?></button>
					<?php else : ?>
						<span class="ogx2-arrow" aria-hidden="true"><?php echo orego_chrome_ic( 'side' ); // phpcs:ignore ?></span>
					<?php endif; ?>
				</div>
				<?php if ( $it['kids'] ) : ?>
				<ul class="ogx2-sub" id="ogx2-sub-<?php echo (int) $i; ?>" hidden>
					<?php foreach ( $it['kids'] as $k ) : ?><li><a href="<?php echo esc_url( $k['url'] ); ?>"<?php echo $k['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $k['title'] ); ?></a></li><?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
		</ul>
	</nav>
	<?php echo function_exists( 'orego_drawer_langs_html' ) ? orego_drawer_langs_html() : ''; // phpcs:ignore ?>
	<?php if ( orego_mod( 'show_currency' ) ) : ?><div class="ogx2-dlangs ogx2-dcurs notranslate"><p>CURRENCY</p><div class="ogx2-dlangs-grid"><?php foreach ( $curs as $code => $c ) : ?><button type="button" class="ogx2-dlang ogx2-dcur<?php echo $rates['default'] === $code ? ' is-on' : ''; ?>" data-dcur="<?php echo esc_attr( $code ); ?>"><span class="ogx2-flag"><?php echo orego_flag( $code ); // phpcs:ignore ?></span><small><?php echo esc_html( $code ); ?></small></button><?php endforeach; ?></div></div><?php endif; ?>
	<?php if ( orego_mod( 'cta_show' ) && orego_mod( 'drawer_cta' ) && '' !== trim( (string) orego_mod( 'cta_text' ) ) ) : ?><div class="ogx2-dfoot"><a class="ogx2-cta ogx2-cta-full" href="<?php echo esc_url( orego_cta_url() ); ?>"><?php echo esc_html( orego_mod( 'cta_text' ) ); ?></a></div><?php endif; ?>
</aside>
<?php echo ( function_exists( 'orego_lang_popup_html' ) && ! orego_is_editor() ) ? orego_lang_popup_html() : ''; // phpcs:ignore ?>
<?php if ( ! function_exists( 'orego_is_editor' ) || ! orego_is_editor() ) : ?><div class="ogx2-gt" hidden><?php echo do_shortcode( '[gtranslate]' ); ?></div><?php endif; ?>
<script>window.OGCFG=<?php echo wp_json_encode( $cfg ); ?>;<?php echo orego_chrome_js(); // phpcs:ignore ?>
(function(){function mark(){var c=window.ogCur&&window.ogCur.code;[].forEach.call(document.querySelectorAll('.ogx2-dcur'),function(b){b.classList.toggle('is-on',b.getAttribute('data-dcur')===c)})}
document.addEventListener('click',function(e){var b=e.target.closest('.ogx2-dcur');if(!b)return;var h=document.querySelector('.ogx2-dd[data-dd="cur"] [data-cur="'+b.getAttribute('data-dcur')+'"]');if(h)h.click();else{var c=b.getAttribute('data-dcur');try{localStorage.setItem('og_cur',c)}catch(x){}window.ogCur.code=c;window.ogCur.rate=(window.OGCFG.rates||{})[c]||1;if(window.ogApplyMoney)window.ogApplyMoney();document.dispatchEvent(new CustomEvent('og:currency',{detail:c}))}mark()});
document.addEventListener('og:currency',mark);if(document.readyState!=='loading')mark();else document.addEventListener('DOMContentLoaded',mark)})();</script>
	<?php
	return ob_get_clean();
} );

/* ------------------------------------------------------------------ */
/* Styles                                                              */
/* ------------------------------------------------------------------ */

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'orego-chrome', false, array(), '1.0' );
	wp_enqueue_style( 'orego-chrome' );
	wp_add_inline_style( 'orego-chrome', orego_chrome_css() );
}, 26 );

function orego_chrome_css() {
	return <<<'CSS'
:root{--og-teal:#2B5856;--og-teal-2:#234A48;--og-teal-soft:#E6EEED;--og-dir:1}
html[dir=rtl]{--og-dir:-1}
.ogx2{position:sticky;top:var(--wp-admin--admin-bar--height,0px);z-index:100;background:var(--ogh-bg,#2B5856);color:var(--ogh-fg,#FFFFFF);font-family:Cairo,Tahoma,sans-serif;box-shadow:0 1px 0 rgba(0,0,0,.08)}
.ogx2 *,.ogx2-drawer *{box-sizing:border-box}
.ogx2-in{display:flex;align-items:center;justify-content:space-between;gap:14px;height:76px;padding-inline:clamp(16px,4.5vw,72px)}
.ogx2-logo{display:inline-flex;align-items:center;gap:10px;color:var(--ogh-fg,#FFFFFF)!important;text-decoration:none!important;direction:ltr}
.ogx2-mark{display:flex;color:inherit}
.ogx2-word{display:flex;flex-direction:column;line-height:1}
.ogx2-word b{font-family:Cairo,Tahoma,sans-serif;font-size:22px;font-weight:800;letter-spacing:-.3px}
.ogx2-word small{font-size:10px;font-weight:600;letter-spacing:.16em;opacity:.85;margin-top:4px}
.ogx2-acts{display:flex;align-items:center;gap:12px}
.ogx2-dd{position:relative}
.ogx2-pill{display:inline-flex;align-items:center;gap:9px;height:46px;padding-block:0;padding-inline:8px 12px;border-radius:14px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.08);color:var(--ogh-fg,#FFFFFF);font:800 16px/1 Cairo,Tahoma,sans-serif;cursor:pointer;transition:background .15s,border-color .15s}
.ogx2-pill:hover,.ogx2-pill[aria-expanded=true]{background:rgba(255,255,255,.16);border-color:rgba(255,255,255,.3)}
.ogx2-pill>svg{opacity:.85;transition:transform .2s}
.ogx2-pill[aria-expanded=true]>svg{transform:rotate(180deg)}
.ogx2-flag{width:28px;height:28px;flex:none;border-radius:50%;overflow:hidden;display:block}
.ogx2-flag svg{width:100%;height:100%;display:block}
.ogx2-pill .ogx2-flag{box-shadow:0 0 0 2px rgba(255,255,255,.95)}
.ogx2-burger{width:48px;height:48px;border-radius:12px;border:0;background:rgba(255,255,255,.16);display:inline-flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;cursor:pointer;padding:0;transition:background .15s}
.ogx2-burger:hover{background:rgba(255,255,255,.24)}
.ogx2-burger i{display:block;width:22px;height:2.5px;border-radius:2px;background:var(--ogh-fg,#FFFFFF)}
.ogx2-panel{position:absolute;top:calc(100% + 10px);inset-inline-end:0;width:236px;max-height:72vh;overflow:auto;background:#FFFFFF;color:#15171C;border-radius:18px;padding:10px;box-shadow:0 24px 50px rgba(8,30,28,.28);z-index:120}
.ogx2-panel[hidden]{display:none}
.ogx2-ph{margin:0;padding:6px 10px 10px;font:800 12px/1.3 Cairo,Tahoma,sans-serif;letter-spacing:.06em;color:#9AA1AD}
.ogx2-panel button{display:flex;align-items:center;gap:12px;width:100%;padding:7px 10px;margin:0 0 2px;border:0;border-radius:12px;background:transparent;color:#15171C;font:700 16px/1.3 Cairo,Tahoma,sans-serif;text-align:start;cursor:pointer}
.ogx2-panel button:hover{background:#F3F6F6}
.ogx2-panel button[aria-checked=true]{background:var(--og-teal-soft);color:var(--og-teal)}
.ogx2-panel .ogx2-flag{box-shadow:0 0 0 1px #E3E6EA}
.ogx2-name{display:flex;flex-direction:column;line-height:1.25}
.ogx2-name small{font-size:12px;font-weight:500;color:#6B7280}
.ogx2-tick{margin-inline-start:auto;display:none;color:var(--og-teal)}
.ogx2-panel button[aria-checked=true] .ogx2-tick{display:flex}
.ogx2 button:focus-visible,.ogx2-drawer a:focus-visible,.ogx2-drawer button:focus-visible{outline:2px solid #FFFFFF;outline-offset:2px}
.ogx2-panel button:focus-visible,.ogx2-nav a:focus-visible,.ogx2-nav button:focus-visible{outline:2px solid var(--og-teal);outline-offset:1px}
.ogx2-scrim{position:fixed;inset:0;background:rgba(10,24,23,.45);z-index:190;opacity:0;transition:opacity .25s}
.ogx2-scrim.is-on{opacity:1}
.ogx2-drawer{position:fixed;top:0;bottom:0;inset-inline-start:0;width:min(420px,100vw);background:#FFFFFF;z-index:200;display:flex;flex-direction:column;transform:translateX(calc(-100% * var(--og-dir)));transition:transform .3s cubic-bezier(.2,.8,.2,1),visibility .3s;visibility:hidden;border-start-end-radius:22px;border-end-end-radius:22px;overflow:hidden;font-family:Cairo,Tahoma,sans-serif;box-shadow:0 0 60px rgba(8,30,28,.2)}
.admin-bar .ogx2-drawer{top:var(--wp-admin--admin-bar--height,0px)}
.ogx2-drawer.is-open{transform:none;visibility:visible}
.ogx2-dh{display:flex;align-items:center;justify-content:space-between;height:88px;padding-inline:22px;background:var(--ogh-bg,#2B5856);color:var(--ogh-fg,#FFFFFF);flex:none}
.ogx2-close{width:44px;height:44px;border-radius:50%;border:0;background:rgba(255,255,255,.18);color:inherit;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0}
.ogx2-close:hover{background:rgba(255,255,255,.28)}
.ogx2-nav{overflow:auto;padding:20px 16px 28px}
.ogx2-nav ul{list-style:none;margin:0;padding:0}
.ogx2-nav>ul>li{position:relative;margin:0 0 6px}
.ogx2-row{display:flex;align-items:center;border-radius:14px;transition:background .15s}
.ogx2-row:hover{background:#F5F7F7}
.ogx2-row>a{flex:1;display:block;padding:18px 22px;font-size:19px;font-weight:700;line-height:1.3;color:#15171C!important;text-decoration:none!important}
.ogx2-arrow,.ogx2-more{width:52px;height:52px;flex:none;display:flex;align-items:center;justify-content:center;color:#9AA1AD;background:none;border:0;border-radius:12px;cursor:pointer;padding:0}
.ogx2-more:hover{color:var(--og-teal)}
.ogx2-arrow svg,.ogx2-more svg{transition:transform .2s}
html:not([dir=rtl]) .ogx2-arrow svg,html:not([dir=rtl]) .ogx2-more svg{transform:scaleX(-1)}
.ogx2-more[aria-expanded=true] svg{transform:rotate(-90deg)}
html:not([dir=rtl]) .ogx2-more[aria-expanded=true] svg{transform:rotate(90deg)}
.ogx2-nav li.is-current>.ogx2-row{background:var(--og-teal-soft)}
.ogx2-nav li.is-current>.ogx2-row>a{color:var(--og-teal)!important}
.ogx2-nav li.is-current>.ogx2-row{box-shadow:inset calc(5px * var(--og-dir)) 0 0 var(--og-teal)}
.ogx2-sub{padding:4px 0 10px!important;padding-inline-start:22px!important}
.ogx2-sub[hidden]{display:none}
.ogx2-sub a{display:block;padding:11px 14px;border-radius:10px;font-size:16px;font-weight:600;color:#4B5563!important;text-decoration:none!important}
.ogx2-sub a:hover,.ogx2-sub a[aria-current=page]{background:#F5F7F7;color:var(--og-teal)!important}
html.ogx2-lock{overflow:hidden}
@media (max-width:640px){
.ogx2-in{height:66px;gap:8px}
.ogx2-acts{gap:8px}
.ogx2-word small{display:none}
.ogx2-word b{font-size:19px}
.ogx2-mark svg{width:32px;height:32px}
.ogx2-pill{height:42px;gap:6px;padding-inline:6px 8px;font-size:14px}
.ogx2-pill>svg{display:none}
.ogx2-flag{width:24px;height:24px}
.ogx2-panel .ogx2-flag{width:28px;height:28px}
.ogx2-burger{width:42px;height:42px}
.ogx2-dd .ogx2-panel{position:fixed;top:calc(66px + var(--wp-admin--admin-bar--height,0px) + 8px);inset-inline:12px;width:auto}
.ogx2-drawer{width:100vw;border-radius:0}
}
@media (max-width:380px){.ogx2-pill b{display:none}}
@media (prefers-reduced-motion:reduce){.ogx2-drawer,.ogx2-scrim,.ogx2-pill>svg{transition:none}}
.ogx2{box-shadow:0 1px 0 rgba(255,255,255,.06),0 6px 20px rgba(8,30,28,.12)}
.ogx2-in{height:72px}
.ogx2-word b{font-size:23px}
.ogx2-mark svg{width:36px;height:36px}
.ogx2-links{display:none}
.ogx2-links ul{display:flex;align-items:center;gap:6px;list-style:none;margin:0;padding:0}
.ogx2-links li{position:relative;margin:0}
.ogx2-links li>a{display:flex;align-items:center;gap:6px;height:40px;padding:0 14px;border-radius:10px;color:var(--ogh-fg,#FFFFFF)!important;opacity:.85;text-decoration:none!important;font:600 15px/1 Cairo,Tahoma,sans-serif;transition:background .15s,color .15s}
.ogx2-links li>a:hover,.ogx2-links li:focus-within>a{opacity:1;background:rgba(255,255,255,.08)}
.ogx2-links li.is-current>a{opacity:1;background:rgba(255,255,255,.12)}
.ogx2-links li>a svg{width:15px;height:15px;opacity:.75;transition:transform .2s}
.ogx2-links li.has-kids:hover>a svg,.ogx2-links li.has-kids:focus-within>a svg{transform:rotate(180deg)}
.ogx2-menu{position:absolute;top:calc(100% + 10px);left:50%;transform:translate(-50%,6px);min-width:220px;background:#FFFFFF;border-radius:16px;padding:8px;box-shadow:0 24px 50px rgba(8,30,28,.25);opacity:0;visibility:hidden;transition:opacity .15s,transform .15s,visibility .15s;z-index:120}
.ogx2-menu::before{content:"";position:absolute;left:0;right:0;top:-12px;height:12px}
.ogx2-links li:hover>.ogx2-menu,.ogx2-links li:focus-within>.ogx2-menu{opacity:1;visibility:visible;transform:translate(-50%,0)}
.ogx2-menu a{display:block;padding:10px 14px;border-radius:10px;color:#15171C!important;text-decoration:none!important;font:600 15px/1.4 Cairo,Tahoma,sans-serif;white-space:nowrap}
.ogx2-menu a:hover,.ogx2-menu a[aria-current=page]{background:var(--og-teal-soft);color:var(--og-teal)!important}
.ogx2-acts{gap:8px}
.ogx2-pill{height:40px;gap:7px;padding-inline:6px 10px;border:0;border-radius:12px;background:rgba(255,255,255,.1);font-size:14px;font-weight:700}
.ogx2-pill:hover,.ogx2-pill[aria-expanded=true]{background:rgba(255,255,255,.18)}
.ogx2-pill .ogx2-flag{width:24px;height:24px;box-shadow:0 0 0 1.5px rgba(255,255,255,.85)}
.ogx2-pill>svg{width:14px;height:14px;opacity:.7}
.ogx2-cta{display:inline-flex;align-items:center;justify-content:center;height:40px;padding:0 18px;border-radius:12px;background:#FFFFFF;color:var(--og-teal)!important;text-decoration:none!important;font:800 15px/1 Cairo,Tahoma,sans-serif;margin-inline-start:6px;transition:background .15s,transform .15s}
.ogx2-cta:hover{background:var(--og-teal-soft)}
.ogx2-burger{width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,.12)}
.ogx2-burger i{width:20px;height:2px}
.ogx2-dfoot{margin-top:auto;padding:16px 20px calc(20px + env(safe-area-inset-bottom));border-top:1px solid #EEF1F1}
.ogx2-cta-full{display:flex;width:100%;height:52px;margin:0;background:var(--og-teal);color:#FFFFFF!important;font-size:17px;border-radius:14px}
.ogx2-cta-full:hover{background:var(--og-teal-2)}
@media (min-width:1025px){
.ogx2-in{display:grid;grid-template-columns:1fr auto 1fr}
.ogx2-logo{justify-self:start}
.ogx2-acts{justify-self:end}
.ogx2-links{display:block}
.ogx2-burger{display:none}
}
@media (max-width:1024px){.ogx2-acts .ogx2-cta{display:none}}
@media (max-width:640px){.ogx2-in{height:64px}.ogx2-pill{height:38px}.ogx2-burger{width:40px;height:40px}}
/* Phones and tablets: language on one side, logo in the middle, menu on the other side; currency moves into the menu */
@media (max-width:1024px){.ogx2 .ogx2-in{display:grid!important;grid-template-columns:1fr auto 1fr;align-items:center;column-gap:8px}.ogx2 .ogx2-acts{display:contents}.ogx2 .ogx2-logo{grid-column:2;grid-row:1;justify-self:center}.ogx2 .ogx2-dd[data-dd=lang]{grid-column:1;grid-row:1;justify-self:start}.ogx2 .ogx2-dd[data-dd=cur]{display:none}.ogx2 .ogx2-burger{grid-column:3;grid-row:1;justify-self:end}.ogx2 .ogx2-links,.ogx2 .ogx2-search,.ogx2 .ogx2-acts .ogx2-cta{display:none}}
.ogx2-dcurs .ogx2-dlangs-grid{grid-template-columns:repeat(3,minmax(0,1fr));max-width:320px}
.ogx2-promo{background:var(--ogp-bg,#1F4341);color:var(--ogp-fg,#FFFFFF);text-align:center;padding:9px 16px;font:500 14px/1.5 Cairo,Tahoma,sans-serif}
.ogx2-promo a,.ogx2-promo span{color:inherit!important;text-decoration:none!important;opacity:.92}
.ogx2-promo a:hover{opacity:1;text-decoration:underline!important}
.ogx2.not-sticky{position:relative;top:0}
.ogx2-logoimg{display:block;height:44px;width:auto;max-width:200px;object-fit:contain}
@media (min-width:1025px){.ogx2.no-links .ogx2-in{display:flex}.ogx2.no-links .ogx2-burger{display:inline-flex}}
@media (max-width:640px){.ogx2-promo{font-size:12.5px;padding:7px 12px}.ogx2-logoimg{height:36px}}
@media (prefers-reduced-motion:reduce){.ogx2-menu,.ogx2-links a svg{transition:none}}
/* Clean header style */
.ogx2-globe{display:none}
.ogx2-search{display:none}
.ogx2.is-overlay{margin-bottom:calc(-1 * var(--ogh-hh,72px));background:linear-gradient(180deg,rgba(0,0,0,.38) 0%,rgba(0,0,0,0) 100%)!important;box-shadow:none!important;transition:background-color .25s,box-shadow .25s}
.ogx2.is-overlay.is-solid{background:var(--ogh-bg,var(--hx-primary))!important;box-shadow:0 6px 20px rgba(0,0,0,.16)!important}
.ogx2-glass .ogx2-links li>a{background:none!important;font-size:17px;font-weight:500;opacity:.9;padding:0 12px}
.ogx2-glass .ogx2-links li>a:hover,.ogx2-glass .ogx2-links li:focus-within>a{opacity:1}
.ogx2-glass .ogx2-links li.is-current>a{opacity:1;text-decoration:underline;text-decoration-thickness:2px;text-underline-offset:9px}
.ogx2-glass .ogx2-pill{background:none!important;border:0;padding-inline:4px 6px;gap:8px;font-size:16px;font-weight:600}
.ogx2-glass .ogx2-pill:hover,.ogx2-glass .ogx2-pill[aria-expanded=true]{background:rgba(255,255,255,.12)!important}
.ogx2-glass [data-dd=lang] .ogx2-pill .ogx2-flag{display:none}
.ogx2-glass .ogx2-globe{display:flex;width:36px;height:36px;flex:none;border-radius:50%;background:#FFFFFF;color:#121212;align-items:center;justify-content:center}
.ogx2-glass .ogx2-pill>svg{width:13px;height:13px}
.ogx2-glass .ogx2-acts .ogx2-cta{background:#FFFFFF;color:var(--hx-ink,#121212)!important;border-radius:8px;height:46px;padding:0 26px;font-size:16px;font-weight:700;margin-inline-start:10px}
.ogx2-glass .ogx2-acts .ogx2-cta:hover{background:#F4F4F5;filter:none}
@media (min-width:1025px){
.ogx2-glass .ogx2-in{display:flex!important;gap:16px}
.ogx2-glass .ogx2-links{margin-inline-start:28px}
.ogx2-glass .ogx2-search{display:flex;align-items:center;position:relative;margin-inline:auto;width:min(380px,30vw)}
.ogx2-glass .ogx2-acts{margin-inline-start:0}
.ogx2-glass.no-links .ogx2-search{margin-inline:auto}
}
@media (min-width:1025px) and (max-width:1199px){.ogx2-glass .ogx2-search{display:none}.ogx2-glass .ogx2-acts{margin-inline-start:auto}}
.ogx2-search input{width:100%;height:48px;border-radius:10px;border:1px solid rgba(255,255,255,.35)!important;background:rgba(255,255,255,.16)!important;color:#FFFFFF;padding-inline:16px 46px!important;font:500 15px var(--hx-font,Cairo,Tahoma,sans-serif);box-shadow:none!important;outline:none;-webkit-backdrop-filter:blur(8px);backdrop-filter:blur(8px)}
.ogx2-search input::placeholder{color:rgba(255,255,255,.78)}
.ogx2-search input:focus{border-color:#FFFFFF!important;background:rgba(255,255,255,.24)!important}
.ogx2-search button{position:absolute;inset-inline-end:6px;top:50%;transform:translateY(-50%);width:38px;height:38px;border:0;border-radius:8px;background:none;color:#FFFFFF;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0}
.ogx2-search button:hover{background:rgba(255,255,255,.14)}
@media (prefers-reduced-motion:reduce){.ogx2.is-overlay{transition:none}}
CSS;
}

/* ------------------------------------------------------------------ */
/* Behaviour: dropdowns, language (GTranslate), currency, drawer       */
/* ------------------------------------------------------------------ */

function orego_chrome_js() {
	return <<<'JS'
(function(){
var C=window.OGCFG||{rates:{USD:1},def:'USD'};
/* currency converter (prices stay in USD on the server) */
function getCur(){var c=null;try{c=localStorage.getItem('og_cur')}catch(e){}return C.rates[c]?c:(C.rates[C.def]?C.def:(Object.keys(C.rates)[0]||'USD'))}
window.ogCur={code:getCur()};window.ogCur.rate=C.rates[window.ogCur.code];
/* formats come from Customize -> Currencies */
window.ogFmt=function(v,c,dec){var k=(C.curs||{})[c]||{s:c,p:'after_space',d:'auto'},neg=Number(v)<0,d;v=Math.abs(Math.round(Number(v)*100)/100)||0;
 if(k.d==='0'){v=Math.round(v);d=0}else if(k.d==='2'){d=2}else{d=(dec!=null)?dec:(Math.round(v*100)%100?2:0)}
 var n=v.toLocaleString('en-US',{minimumFractionDigits:d,maximumFractionDigits:d});
 return (neg?'-':'')+(k.p==='before'?k.s+n:k.p==='before_space'?k.s+' '+n:k.p==='after'?n+k.s:n+' '+k.s)};
window.ogSym=function(c){var k=(C.curs||{})[c||window.ogCur.code];return k?k.s:(c||'')};
window.ogMoney=function(usd,dec){var c=window.ogCur.code;return window.ogFmt(Number(usd)*(C.rates[c]||1),c,dec)};
window.ogApplyMoney=function(){
 [].forEach.call(document.querySelectorAll('.og-money'),function(el){var u=parseFloat(el.getAttribute('data-usd'));if(isNaN(u))return;var dd=el.getAttribute('data-dec');el.textContent=window.ogMoney(u,dd===null?null:+dd)});
 [].forEach.call(document.querySelectorAll('.og-cur-sym'),function(el){el.textContent=window.ogSym()})};
function ready(f){if(document.readyState!=='loading')f();else document.addEventListener('DOMContentLoaded',f)}
ready(function(){
 var root=document.querySelector('.ogx2');if(!root||root.dataset.ready)return;root.dataset.ready='1';
 window.ogApplyMoney();
 if(root.classList.contains('is-overlay')){var sol=function(){root.classList.toggle('is-solid',window.scrollY>12)};sol();window.addEventListener('scroll',sol,{passive:true})}
 var dds=[].slice.call(root.querySelectorAll('.ogx2-dd'));
 function closeAll(except){dds.forEach(function(d){if(d===except)return;d.querySelector('.ogx2-pill').setAttribute('aria-expanded','false');d.querySelector('.ogx2-panel').hidden=true})}
 dds.forEach(function(d){var b=d.querySelector('.ogx2-pill'),p=d.querySelector('.ogx2-panel');
  b.addEventListener('click',function(e){e.stopPropagation();var open=p.hidden;closeAll(d);p.hidden=!open;b.setAttribute('aria-expanded',open?'true':'false');if(open){var f=p.querySelector('[aria-checked=true]')||p.querySelector('button');if(f)f.focus()}});
  p.addEventListener('keydown',function(e){var items=[].slice.call(p.querySelectorAll('button')),i=items.indexOf(document.activeElement);
   if(e.key==='ArrowDown'){e.preventDefault();items[(i+1)%items.length].focus()}if(e.key==='ArrowUp'){e.preventDefault();items[(i-1+items.length)%items.length].focus()}if(e.key==='Escape'){closeAll();b.focus()}})});
 document.addEventListener('click',function(e){if(!e.target.closest('.ogx2-dd'))closeAll()});
 function setPill(d,btn,label){var pill=d.querySelector('.ogx2-pill');pill.querySelector('.ogx2-flag').innerHTML=btn.querySelector('.ogx2-flag').innerHTML;pill.querySelector('b').textContent=label;
  [].forEach.call(d.querySelectorAll('[aria-checked]'),function(x){x.setAttribute('aria-checked',x===btn?'true':'false')})}
 /* language */
 var lds=[].slice.call(root.querySelectorAll('[data-dd=lang]'));
 function curLang(){try{var s=JSON.parse(localStorage.getItem('__GT_TRANSLATE_LANGS'));if(s&&typeof s.tgtLang==='string')return s.tgtLang}catch(e){}return 'en'}
 function markLang(code){lds.forEach(function(ld){var b=ld.querySelector('[data-lang="'+code+'"]')||ld.querySelector('[data-lang=en]');if(b)setPill(ld,b,b.getAttribute('data-short'))})}
 markLang(curLang());window.ogMarkLang=function(l){markLang(l)};
 lds.forEach(function(ld){[].forEach.call(ld.querySelectorAll('[data-lang]'),function(b){b.addEventListener('click',function(){var l=b.getAttribute('data-lang');closeAll();markLang(l);
  if(window.ogSetLang){window.ogSetLang(l)}else if(typeof window.doGTranslate==='function'){window.doGTranslate('en|'+l)}})})});
 /* currency */
 var cds=[].slice.call(root.querySelectorAll('[data-dd=cur]')),cd=cds[0]||document.createElement('div');
 function markCur(c){cds.forEach(function(d){var b=d.querySelector('[data-cur="'+c+'"]');if(b)setPill(d,b,c)})}
 markCur(window.ogCur.code);
 cds.forEach(function(d){[].forEach.call(d.querySelectorAll('[data-cur]'),function(b){b.addEventListener('click',function(){var c=b.getAttribute('data-cur');closeAll();try{localStorage.setItem('og_cur',c)}catch(e){}
  window.ogCur.code=c;window.ogCur.rate=C.rates[c];markCur(c);window.ogApplyMoney();document.dispatchEvent(new CustomEvent('og:currency',{detail:c}))})})});
   window.ogCurForLang=function(l){var c=(C.lmap||{})[l]||C.def;if(!C.rates[c])return;try{localStorage.setItem('og_cur',c);localStorage.setItem('og_cur_lang',l)}catch(e){}if(c===window.ogCur.code)return;var b=cd.querySelector('[data-cur="'+c+'"]');if(b){b.click()}else{window.ogCur.code=c;window.ogCur.rate=C.rates[c];window.ogApplyMoney();document.dispatchEvent(new CustomEvent('og:currency',{detail:c}))}};
 /* drawer */
 var drawer=document.getElementById('ogx2-drawer'),scrim=document.querySelector('.ogx2-scrim'),burgers=[].slice.call(root.querySelectorAll('.ogx2-burger')),burger=burgers[0];
 if(drawer&&scrim&&drawer.parentNode!==document.body){document.body.appendChild(scrim);document.body.appendChild(drawer)}
 function openD(){closeAll();scrim.hidden=false;drawer.removeAttribute('inert');drawer.setAttribute('aria-hidden','false');requestAnimationFrame(function(){scrim.classList.add('is-on');drawer.classList.add('is-open')});burger.setAttribute('aria-expanded','true');document.documentElement.classList.add('ogx2-lock');setTimeout(function(){drawer.querySelector('.ogx2-close').focus()},60)}
 function closeD(){if(!drawer.classList.contains('is-open'))return;scrim.classList.remove('is-on');drawer.classList.remove('is-open');drawer.setAttribute('aria-hidden','true');drawer.setAttribute('inert','');burger.setAttribute('aria-expanded','false');document.documentElement.classList.remove('ogx2-lock');setTimeout(function(){scrim.hidden=true},260);burger.focus()}
 if(drawer&&burger){burgers.forEach(function(x){x.addEventListener('click',function(){burger=x;openD()})});scrim.addEventListener('click',closeD);drawer.querySelector('.ogx2-close').addEventListener('click',closeD);
  drawer.addEventListener('click',function(e){var m=e.target.closest('.ogx2-more');if(m){var s=document.getElementById(m.getAttribute('aria-controls'));var o=m.getAttribute('aria-expanded')!=='true';m.setAttribute('aria-expanded',o?'true':'false');s.hidden=!o;return}if(e.target.closest('a')&&e.target.closest('a').getAttribute('href').charAt(0)==='#')closeD()});
  drawer.addEventListener('keydown',function(e){if(e.key!=='Tab')return;var f=[].slice.call(drawer.querySelectorAll('a,button')).filter(function(x){return x.offsetParent!==null});if(!f.length)return;var a=f[0],z=f[f.length-1];if(e.shiftKey&&document.activeElement===a){e.preventDefault();z.focus()}else if(!e.shiftKey&&document.activeElement===z){e.preventDefault();a.focus()}});
  /* sub-menus stay closed until tapped */}
 document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeAll();if(drawer)closeD()}});
});
})();
JS;
}

/* ------------------------------------------------------------------ */
/* Dashboard: exchange rates                                           */
/* ------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=orego_car', 'Currencies', 'Currencies', 'manage_options', 'orego-currency', 'orego_currency_page' );
}, 17 );

add_action( 'admin_post_orego_rates_save', function () {
	check_admin_referer( 'orego_rates_save' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	$r = array(
		'MAD'     => max( 0.0001, (float) ( $_POST['MAD'] ?? 9.2 ) ),
		'EUR'     => max( 0.0001, (float) ( $_POST['EUR'] ?? 0.86 ) ),
		'default' => isset( $_POST['default'] ) && in_array( $_POST['default'], array( 'USD', 'MAD', 'EUR' ), true ) ? sanitize_key( $_POST['default'] ) : orego_rates()['default'],
	);
	$r['default'] = strtoupper( $r['default'] );
	$bk           = strtoupper( sanitize_key( $_POST['booking'] ?? 'USD' ) );
	$r['booking'] = in_array( $bk, array( 'USD', 'MAD', 'EUR' ), true ) ? $bk : 'USD';
	update_option( 'orego_rates', $r, false );
	do_action( 'litespeed_purge_all' );
	wp_safe_redirect( admin_url( 'edit.php?post_type=orego_car&page=orego-currency&og_done=saved:1' ) );
	exit;
} );

function orego_currency_page() {
	$L   = 'orego_L';
	$url = admin_url( 'customize.php?autofocus[section]=orego_currencies' );
	orego_ui_head( $L( 'Currencies', 'العملات' ), $L( 'Your car prices are set in US dollars. Visitors choose their currency in the header, and they book and pay in that currency, converted with these rates.', 'أسعار سياراتك بالدولار الأمريكي. يختار الزوار العملة من الهيدر، ويحجزون ويدفعون بها حسب هذه الأسعار.' ) );
	?>
	<div class="ogcur">
		<section class="ogdx-card ogu-form">
			<div class="ogdx-card-h"><h2><?php echo esc_html( $L( 'Your currencies', 'عملاتك' ) ); ?></h2></div>
			<table class="ogcur-t">
				<thead><tr><th><?php echo esc_html( $L( 'Currency', 'العملة' ) ); ?></th><th><?php echo esc_html( $L( 'Rate', 'السعر' ) ); ?></th><th><?php echo esc_html( $L( 'A $50 car', 'سيارة بـ 50$' ) ); ?></th><th></th></tr></thead>
				<tbody>
				<?php foreach ( orego_currency_list() as $c ) : ?>
					<tr<?php echo $c['on'] ? '' : ' class="is-off"'; ?>>
						<td><b><?php echo esc_html( $c['code'] ); ?></b> <?php echo esc_html( $c['name'] ); ?></td>
						<td><?php echo 'USD' === $c['code'] ? esc_html( $L( 'Base', 'الأساس' ) ) : esc_html( '1 USD = ' . $c['rate'] . ' ' . $c['code'] ); ?></td>
						<td dir="ltr"><?php echo esc_html( orego_currency_format( 50 * $c['rate'], $c['code'] ) ); ?></td>
						<td><?php echo $c['def'] ? '<span class="ogcur-tag">' . esc_html( $L( 'Default', 'الافتراضية' ) ) . '</span>' : ''; ?><?php echo $c['on'] ? '' : '<span class="ogcur-tag off">' . esc_html( $L( 'Hidden', 'مخفية' ) ) . '</span>'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</section>
		<div class="ogcur-bar"><a class="ogdx-btn" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $L( 'Add or edit currencies', 'إضافة العملات أو تعديلها' ) ); ?></a></div>
	</div>
	<style>
	.ogcur{display:grid;gap:16px;max-width:760px}.ogcur .ogdx-card{margin:0}
	.ogcur-t{width:100%;border-collapse:collapse;font-size:14px}.ogcur-t th{text-align:start;font-size:12px;color:#6B7280;font-weight:600;padding:8px 10px;border-bottom:1px solid #ECEEF2}.ogcur-t td{padding:10px;border-bottom:1px solid #F3F4F6}.ogcur-t tr.is-off td{color:#9CA3AF}
	.ogcur-tag{display:inline-block;font-size:11px;font-weight:600;border-radius:999px;padding:2px 8px;background:#EEF2FF;color:#3730A3;margin-inline-end:4px}.ogcur-tag.off{background:#F3F4F6;color:#6B7280}
	.ogcur-bar{display:flex;justify-content:flex-end}
	</style>
	<?php
	orego_ui_foot();
}