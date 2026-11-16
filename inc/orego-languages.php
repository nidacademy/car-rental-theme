<?php
/**
 * Exotic Cars languages (free GTranslate, translation in the visitor's browser).
 * - 7 languages: English (source), Arabic, French, Spanish, German, Italian, Portuguese
 * - Appearance -> Languages: enable/disable, default language, first-visit popup
 * - First-visit popup + language row in the menu drawer
 * - Remembers the choice; Latin-script languages switch the layout to left-to-right
 * - The dashboard is never translated (GTranslate only runs on the website)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Languages GTranslate can show: code => English name, read from the GTranslate plugin (cached). */
function orego_gt_lang_names() {
	static $n = null;
	if ( null !== $n ) {
		return $n;
	}
	$n = get_transient( 'orego_gt_lang_names' );
	if ( is_array( $n ) && $n ) {
		return $n;
	}
	$n    = array();
	$file = WP_PLUGIN_DIR . '/gtranslate/gtranslate.php';
	if ( is_readable( $file ) ) {
		$src = (string) file_get_contents( $file ); // phpcs:ignore
		if ( preg_match( '/\$lang_array\s*=\s*array\((.*?)\);/s', $src, $m ) && preg_match_all( "/'([a-zA-Z-]{2,6})'\s*=>\s*'([^']+)'/", $m[1], $pairs, PREG_SET_ORDER ) ) {
			foreach ( $pairs as $p ) {
				$n[ $p[1] ] = $p[2];
			}
		}
	}
	set_transient( 'orego_gt_lang_names', $n, DAY_IN_SECONDS );
	return $n;
}

/** All languages: GTranslate code => [native name, short code, direction, English name]. */
function orego_lang_catalog() {
	static $cat = null;
	if ( null !== $cat ) {
		return $cat;
	}
	$cat = array(
		'en' => array( 'English', 'EN', 'ltr', 'English' ),
		'ar' => array( 'العربية', 'AR', 'rtl', 'Arabic' ),
		'fr' => array( 'Français', 'FR', 'ltr', 'French' ),
		'es' => array( 'Español', 'ES', 'ltr', 'Spanish' ),
		'de' => array( 'Deutsch', 'DE', 'ltr', 'German' ),
		'it' => array( 'Italiano', 'IT', 'ltr', 'Italian' ),
		'pt' => array( 'Português', 'PT', 'ltr', 'Portuguese' ),
	);
	$rtl = array( 'ar', 'iw', 'he', 'fa', 'ur', 'ps', 'yi', 'sd', 'ug' );
	foreach ( orego_gt_lang_names() as $code => $english ) {
		if ( isset( $cat[ $code ] ) ) {
			continue;
		}
		$loc    = str_replace( array( 'iw', 'jw' ), array( 'he', 'jv' ), $code );
		$native = class_exists( 'Locale' ) ? (string) Locale::getDisplayName( $loc, $loc ) : '';
		if ( '' === $native || strtolower( $native ) === strtolower( $loc ) ) {
			$native = $english;
		}
		$native       = function_exists( 'mb_convert_case' ) ? mb_strtoupper( mb_substr( $native, 0, 1 ) ) . mb_substr( $native, 1 ) : ucfirst( $native );
		$cat[ $code ] = array( $native, strtoupper( $code ), in_array( strtolower( strtok( $code, '-' ) ), $rtl, true ) ? 'rtl' : 'ltr', $english );
	}
	return $cat;
}
function orego_lang_cfg() {
	$c   = get_option( 'orego_langs_cfg' );
	$c   = is_array( $c ) ? $c : array();
	$all = array_keys( orego_lang_catalog() );
	// The languages are the ones chosen in GTranslate (Settings -> GTranslate), in the same order.
	$gt  = get_option( 'GTranslate' );
	$src = ( is_array( $gt ) && ! empty( $gt['incl_langs'] ) && is_array( $gt['incl_langs'] ) ) ? $gt['incl_langs'] : (array) ( $c['enabled'] ?? $all );
	$on  = array_values( array_intersect( $src, $all ) );
	if ( ! in_array( 'en', $on, true ) ) {
		array_unshift( $on, 'en' ); // English is the source language, always on
	}
	$def = $c['default'] ?? 'en';
	return array(
		'enabled' => $on,
		'default' => in_array( $def, $on, true ) ? $def : 'en',
		'popup'   => array_key_exists( 'popup', $c ) ? (bool) $c['popup'] : true,
	);
}

/** Enabled languages in the shape the header uses: code => [native name, short code]. */
function orego_enabled_langs() {
	$cat = orego_lang_catalog();
	$out = array();
	foreach ( orego_lang_cfg()['enabled'] as $code ) {
		$out[ $code ] = array( $cat[ $code ][0], $cat[ $code ][1] );
	}
	return $out;
}

/* When the languages are changed in Settings -> GTranslate, the site's language chooser follows. */
add_action( 'update_option_GTranslate', function ( $old, $new ) {
	static $busy = false;
	if ( $busy || ! is_array( $new ) ) {
		return;
	}
	$old  = is_array( $old ) ? $old : array();
	$inc  = array_values( (array) ( $new['incl_langs'] ?? array() ) );
	$finc = array_values( (array) ( $new['fincl_langs'] ?? array() ) );
	$list = $inc;
	if ( $finc !== array_values( (array) ( $old['fincl_langs'] ?? array() ) ) && $inc === array_values( (array) ( $old['incl_langs'] ?? array() ) ) ) {
		$list = $finc; // only the other list was edited
	}
	if ( ! in_array( 'en', $list, true ) ) {
		array_unshift( $list, 'en' ); // English is the source language
	}
	$c            = get_option( 'orego_langs_cfg' );
	$c            = is_array( $c ) ? $c : array();
	$c['enabled'] = $list;
	if ( ! empty( $c['default'] ) && ! in_array( $c['default'], $list, true ) ) {
		$c['default'] = 'en';
	}
	update_option( 'orego_langs_cfg', $c );
	if ( $list !== $inc || $list !== $finc ) {
		$busy              = true;
		$new['incl_langs']  = $list;
		$new['fincl_langs'] = $list;
		update_option( 'GTranslate', $new );
		$busy = false;
	}
	do_action( 'litespeed_purge_all' );
}, 10, 2 );

/** Keep GTranslate's own language list in step with ours. */
function orego_sync_gtranslate() {
	$o = get_option( 'GTranslate' );
	if ( ! is_array( $o ) ) {
		return;
	}
	$cfg                       = orego_lang_cfg();
	$o['default_language']     = 'en';
	$o['fincl_langs']          = $cfg['enabled'];
	$o['incl_langs']           = $cfg['enabled'];
	$o['detect_browser_language'] = '';
	update_option( 'GTranslate', $o );
}

/* ------------------------------------------------------------------ */
/* Website: popup, drawer row, behaviour                               */
/* ------------------------------------------------------------------ */

function orego_lang_buttons( $cls ) {
	$h = '';
	foreach ( orego_enabled_langs() as $code => $l ) {
		$h .= '<button type="button" class="' . esc_attr( $cls ) . '" data-setlang="' . esc_attr( $code ) . '" lang="' . esc_attr( $code ) . '" aria-pressed="false"><span class="ogx2-flag">' . orego_flag( $code ) . '</span><b>' . esc_html( $l[0] ) . '</b><small>' . esc_html( $l[1] ) . '</small></button>';
	}
	return $h;
}

function orego_drawer_langs_html() {
	if ( ! orego_mod( 'drawer_langs' ) || ! orego_mod( 'show_lang' ) || count( orego_enabled_langs() ) < 2 ) {
		return '';
	}
	return '<div class="ogx2-dlangs notranslate"><p>LANGUAGE · اللغة</p><div class="ogx2-dlangs-grid">' . orego_lang_buttons( 'ogx2-dlang' ) . '</div></div>';
}

function orego_lang_popup_html() {
	$cfg = orego_lang_cfg();
	if ( ! $cfg['popup'] || count( $cfg['enabled'] ) < 2 ) {
		return '';
	}
	$cat  = orego_lang_catalog();
	$rows = '';
	foreach ( $cfg['enabled'] as $code ) {
		$l     = $cat[ $code ];
		$def   = $cfg['default'] === $code;
		$hint  = $l[0] === $l[3] ? '' : $l[3]; // no second line when it repeats the name
		$rows .= '<button type="button" class="oglp-row' . ( $def ? ' is-default' : '' ) . '" data-setlang="' . esc_attr( $code ) . '" lang="' . esc_attr( $code ) . '" aria-pressed="false"><span class="ogx2-flag">' . orego_flag( $code ) . '</span><span class="oglp-txt"><b>' . esc_html( $l[0] ) . '</b><small>' . esc_html( $hint ) . '</small></span><i class="oglp-code">' . esc_html( $l[1] ) . '</i></button>';
	}
	$globe = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"/></svg>';
	return '<div class="oglp notranslate" id="oglp" role="dialog" aria-modal="true" aria-labelledby="oglp-t" hidden><div class="oglp-card">'
		. '<div class="oglp-head"><button type="button" class="oglp-back" aria-label="Back"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button><span class="oglp-mt">Language</span><button type="button" class="oglp-ok" aria-label="Confirm language"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></button><span class="oglp-brand">' . ( ( $lg = wp_get_attachment_image_url( orego_aid( 'logo_dark' ), 'medium' ) ) ? '<img src="' . esc_url( $lg ) . '" alt="' . esc_attr( orego_brand() ) . '" width="150" height="45">' : $globe ) . '</span><span class="oglp-sp"></span>'
		. '<button type="button" class="oglp-x" aria-label="Close">' . orego_chrome_ic( 'x' ) . '</button></div>'
		. '<div class="oglp-titles"><h2 id="oglp-t">Choose your language</h2><p class="oglp-kick">Welcome · Bienvenue · مرحبا</p></div>'
		. '<div class="oglp-list">' . $rows . '</div><p class="oglp-foot">You can change the language anytime from the menu.</p>' . ( ( $car = wp_get_attachment_image_url( orego_aid( 'car' ), 'large' ) ) ? '<div class="oglp-visual" aria-hidden="true"><span class="oglp-floor"></span><img src="' . esc_url( $car ) . '" alt=""></div>' : '' ) . '</div></div>';
}

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'orego-langs', false, array(), '1.0' );
	wp_enqueue_style( 'orego-langs' );
	// The language page photo (Customize -> Welcome & language pages), or none.
	$og_lbg = function_exists( 'orego_intro_img' ) ? orego_intro_img( 'og_lang_bg' ) : '';
	wp_add_inline_style( 'orego-langs', str_replace( '{{OG_LANG_BG}}', $og_lbg ? 'url(' . esc_url( $og_lbg ) . ') right center/cover no-repeat,' : '', orego_langs_css() ) );
}, 28 );

add_action( 'wp_footer', function () {
	if ( is_admin() || ! shortcode_exists( 'orego_header' ) || ( function_exists( 'orego_is_editor' ) && orego_is_editor() ) ) {
		return;
	}
	$cfg = orego_lang_cfg();
	$cat = orego_lang_catalog();
	$js  = array( 'enabled' => $cfg['enabled'], 'def' => $cfg['default'], 'popup' => $cfg['popup'] && ( ! function_exists( 'orego_lang_page_on' ) || orego_lang_page_on() ), 'after' => ! function_exists( 'orego_lang_page_on' ) || orego_lang_page_on(), 'dev' => function_exists( 'orego_lang_on_d' ) ? array( 'd' => array( orego_lang_on_d(), orego_lang_on_d() ), 'm' => array( orego_lang_on_m(), orego_lang_on_m() ) ) : null, 'rtl' => array_keys( array_filter( $cat, fn( $l ) => 'rtl' === $l[2] ) ) );
	echo '<script>window.OGLANG=' . wp_json_encode( $js ) . ';' . orego_langs_js() . '</script>'; // phpcs:ignore
}, 50 );

function orego_langs_js() {
	return <<<'JS'
(function(){
var L=window.OGLANG;if(!L)return;
function gtLang(){try{var s=JSON.parse(localStorage.getItem('__GT_TRANSLATE_LANGS'));if(s&&typeof s.tgtLang==='string')return s.tgtLang}catch(e){}return null}
function dir(l){var rtl=L.rtl.indexOf(l)>-1,h=document.documentElement;h.setAttribute('dir',rtl?'rtl':'ltr');h.classList.toggle('translated-ltr',!rtl);h.classList.toggle('og-ltr',!rtl);if(document.body)document.body.classList.toggle('rtl',rtl)}
function mark(l){[].forEach.call(document.querySelectorAll('[data-setlang]'),function(b){var on=b.getAttribute('data-setlang')===l;b.setAttribute('aria-pressed',on?'true':'false');b.classList.toggle('is-on',on)});if(window.ogMarkLang)window.ogMarkLang(l)}
window.ogSetLang=function(l){if(L.enabled.indexOf(l)<0)l=L.def;dir(l);mark(l);try{localStorage.setItem('og_lang',l);localStorage.setItem('og_lang_seen','1')}catch(e){}
 if(window.ogCurForLang)window.ogCurForLang(l);var now=gtLang()||'en';if(now===l)return;
 if(typeof window.doGTranslate==='function'){window.doGTranslate('en|'+l)}else{try{localStorage.setItem('__GT_TRANSLATE_LANGS',JSON.stringify({srcLang:'en',tgtLang:l}))}catch(e){}location.reload()}};
var cur=gtLang();
if(cur&&L.enabled.indexOf(cur)<0){window.ogSetLang(L.def);cur=L.def}
dir(cur||'en');mark(cur||'en');
document.addEventListener('click',function(e){var b=e.target.closest('[data-setlang]');if(!b)return;e.preventDefault();var l=b.getAttribute('data-setlang');closePop(false);window.ogSetLang(l)});
var pop=document.getElementById('oglp'),seen=false;try{seen=!!localStorage.getItem('og_lang_seen')}catch(e){}
function openPop(){if(!pop)return;pop.hidden=false;document.documentElement.classList.add('ogx2-lock');var has=false;try{has=!!localStorage.getItem('og_lang')}catch(e){}pop.classList.toggle('is-first',!has);if(!has){[].forEach.call(pop.querySelectorAll('.oglp-row.is-on'),function(x){x.classList.remove('is-on')});var ae=document.activeElement;ae&&ae.blur&&ae.blur();return}var f=pop.querySelector('.is-on')||pop.querySelector('[data-setlang]');setTimeout(function(){f&&f.focus({preventScroll:true})},50)}
function closePop(applyDefault){if(!pop||pop.hidden)return;pop.hidden=true;document.documentElement.classList.remove('ogx2-lock');try{localStorage.setItem('og_lang_seen','1')}catch(e){}if(applyDefault)window.ogSetLang(gtLang()||L.def)}
if(pop){pop.querySelector('.oglp-x').addEventListener('click',function(){closePop(true)});pop.addEventListener('click',function(e){if(e.target===pop)closePop(true)});document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!pop.hidden)closePop(true)})}
/* phones: tapping a language applies it right away (the check button does the same); the back arrow closes */
if(pop){var pmq={matches:true};function pick(r){[].forEach.call(pop.querySelectorAll('.oglp-row'),function(x){x.classList.toggle('is-pick',x===r)})}
 pop.addEventListener('click',function(e){if(!pmq.matches)return;var r=e.target.closest('.oglp-row');if(r){e.preventDefault();e.stopPropagation();pick(r);var tl=r.getAttribute('data-setlang');closePop(false);window.ogSetLang(tl);return}if(e.target.closest('.oglp-back')){e.preventDefault();closePop(true);return}if(e.target.closest('.oglp-ok')){e.preventDefault();var p=pop.querySelector('.oglp-row.is-pick')||pop.querySelector('.oglp-row.is-on');var l=p?p.getAttribute('data-setlang'):null;closePop(false);if(l)window.ogSetLang(l)}},true);
 new MutationObserver(function(){if(pop.hidden)return;var has=false;try{has=!!localStorage.getItem('og_lang')}catch(e){}pick(has?pop.querySelector('.oglp-row.is-on'):null)}).observe(pop,{attributes:true,attributeFilter:['hidden']})}
try{var _dv=L.dev&&L.dev[window.matchMedia&&window.matchMedia('(max-width:767px)').matches?'m':'d'];if(_dv){L.popup=L.popup&&_dv[0];L.after=_dv[1]}}catch(e){}
var afterWelcome=false;try{afterWelcome=sessionStorage.getItem('og_lang_after_welcome')==='1';sessionStorage.removeItem('og_lang_after_welcome')}catch(e){}
var chosen=false;try{chosen=!!localStorage.getItem('og_lang')}catch(e){} if(afterWelcome&&pop&&!chosen&&L.after!==false){openPop()}
else if(!seen&&(!cur||window.OG_FIRST)&&L.popup&&pop){openPop()}
else if(!cur&&L.def!=='en'){var stored=null;try{stored=localStorage.getItem('og_lang')}catch(e){}if(!stored)window.ogSetLang(L.def)}
})();
JS;
}

function orego_langs_css() {
	return <<<'CSS'
.ogx2-dlangs{padding:14px 20px 18px;border-top:1px solid #EEF1F1}
.ogx2-dlangs p{margin:0 0 10px;text-align:center;font:800 12px/1.3 Cairo,Tahoma,sans-serif;letter-spacing:.06em;color:#9AA1AD}
.ogx2-dlangs-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;max-width:420px;margin:0 auto}
.ogx2-dlang{display:flex;flex-direction:column;align-items:center;gap:5px;padding:10px 4px;border:1px solid transparent;border-radius:12px;background:#F5F7F7;color:#15171C;cursor:pointer;font-family:Cairo,Tahoma,sans-serif}
.ogx2-dlang .ogx2-flag{width:30px;height:30px;box-shadow:0 0 0 1px #E3E6EA}
.ogx2-dlang b{display:none}
.ogx2-dlang small{font-size:13px;font-weight:800}
.ogx2-dlang:hover{border-color:#D5DEDD}
.ogx2-dlang.is-on{background:var(--hx-primary-soft);border-color:var(--hx-primary);color:var(--hx-primary)}
.oglp{position:fixed;inset:0;z-index:400;background:rgba(17,24,39,.42);-webkit-backdrop-filter:blur(6px) saturate(.9);backdrop-filter:blur(6px) saturate(.9);display:flex;align-items:center;justify-content:center;padding:16px;font-family:var(--hx-font,Cairo,Tahoma,sans-serif)}
.oglp[hidden]{display:none}
.oglp-card{position:relative;width:100%;max-width:560px;max-height:calc(100vh - 32px);display:flex;flex-direction:column;background:#111318;color:#FFFFFF;border-radius:26px;overflow:hidden;border:1px solid rgba(255,255,255,.08);box-shadow:0 40px 100px -30px rgba(0,0,0,.75);animation:oglpIn .3s cubic-bezier(.2,.8,.2,1)}
.oglp-card::before{content:"";position:absolute;inset:0 0 auto 0;height:180px;background:radial-gradient(ellipse 70% 100% at 50% 0%,rgba(255,255,255,.07),rgba(255,255,255,0));pointer-events:none}
@keyframes oglpIn{from{opacity:0;transform:translateY(16px) scale(.97)}to{opacity:1;transform:none}}
.oglp-head{position:relative;display:flex;align-items:center;gap:14px;padding:24px 26px 0;background:none;color:#FFFFFF}
.oglp-brand{display:flex;align-items:center}
.oglp-brand img{display:block;height:34px;width:auto}
.oglp-brand svg{width:26px;height:26px;color:#fff}
.oglp-sp{flex:1}
.oglp-x{width:40px;height:40px;flex:none;border-radius:50%;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#FFFFFF;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;transition:background .15s,border-color .15s}
.oglp-x:hover{background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.22)}
.oglp-x svg{width:18px;height:18px}
.oglp-titles{position:relative;padding:26px 26px 18px}
.oglp h2{margin:0;font-size:28px;font-weight:800;line-height:1.15;letter-spacing:-.02em;color:#FFFFFF}
.oglp-kick{margin:8px 0 0;font-size:13px;font-weight:600;letter-spacing:.02em;color:#8B9099}
.oglp-list{position:relative;overflow:auto;padding:0 26px 6px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;overscroll-behavior:contain}
.oglp-row{position:relative;display:flex;align-items:center;gap:13px;width:100%;min-width:0;margin:0;padding:13px 44px 13px 13px;border:1px solid rgba(255,255,255,.09);border-radius:16px;background:rgba(255,255,255,.035);color:#FFFFFF;cursor:pointer;text-align:start;transition:background .15s,border-color .15s,transform .15s}
html[dir=rtl] .oglp-row{padding:13px 13px 13px 44px}
.oglp-row:hover{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.18)}
.oglp-row:active{transform:scale(.985)}
.oglp-row::after{content:"";position:absolute;top:50%;inset-inline-end:14px;width:20px;height:20px;margin-top:-10px;border-radius:50%;box-shadow:inset 0 0 0 1.5px rgba(255,255,255,.22)}
.oglp-row.is-on{background:#FFFFFF;border-color:#FFFFFF;color:#111318}
.oglp-row.is-on .oglp-txt small{color:#6B7280}
.oglp-row.is-on::after{box-shadow:none;background:#111318 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E") center/12px no-repeat}
.oglp-row .ogx2-flag{width:38px;height:38px;flex:none;box-shadow:0 0 0 2px rgba(255,255,255,.12)}
.oglp-row.is-on .ogx2-flag{box-shadow:0 0 0 1px #E3E6EA}
.oglp-txt{flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;line-height:1.25}
.oglp-txt b{font-size:16px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.oglp-txt small{font-size:12.5px;font-weight:500;color:#6B7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.oglp-txt small:empty{display:none}
.oglp-row.is-default .oglp-txt small{color:#6B7280;font-weight:500}
.oglp-code,.oglp-globe{display:none}
.oglp-foot{position:relative;margin:0;padding:16px 26px 24px;text-align:center;font-size:12.5px;color:#6B7280}
.oglp-row:focus{outline:none}
.oglp-row:focus-visible{border-color:#FFFFFF;box-shadow:0 0 0 3px rgba(255,255,255,.2)}
.oglp-x:focus-visible{outline:2px solid #FFFFFF;outline-offset:2px}
@media (max-width:560px){.oglp{align-items:flex-end;padding:0}.oglp-card{max-width:none;border-radius:24px 24px 0 0;max-height:90vh;animation-name:oglpUp;border-bottom:0}.oglp-list{grid-template-columns:1fr;padding:0 18px 6px;gap:8px}.oglp-head{padding:20px 18px 0}.oglp-titles{padding:20px 18px 14px}.oglp h2{font-size:24px}.oglp-brand img{height:30px}.oglp-foot{padding:12px 18px calc(16px + env(safe-area-inset-bottom))}}
@keyframes oglpUp{from{transform:translateY(100%)}to{transform:none}}
.ogx2-flag .og-flagimg{display:block;width:100%;height:100%;object-fit:cover}
.oglp-row .ogx2-flag .og-flagimg{width:100%;height:100%;margin:0}
/* tablet and desktop: a wide, short picker (languages in rows of 3-4) */
@media (min-width:768px){.oglp-card{max-width:920px}.oglp-head{padding:22px 28px 0}.oglp-titles{display:flex;align-items:baseline;flex-wrap:wrap;gap:6px 16px;padding:16px 28px 20px}.oglp h2{font-size:26px}.oglp-kick{margin:0}.oglp-list{grid-template-columns:repeat(4,minmax(0,1fr));padding:0 28px 4px}.oglp-foot{padding:14px 28px 22px}}
@media (min-width:768px) and (max-width:1000px){.oglp-card{max-width:720px}.oglp-list{grid-template-columns:repeat(3,minmax(0,1fr))}}
.oglp-back,.oglp-ok,.oglp-mt{display:none}
/* Phones: a full-screen "Language" page: back arrow, title, confirm button; one language per row with a rectangular flag */
@media all{
.oglp{align-items:stretch;padding:0;background:#FFFFFF;-webkit-backdrop-filter:none;backdrop-filter:none}
.oglp-card{max-width:none;width:100%;height:100%;max-height:none;border-radius:0;border:0;background:#FFFFFF;color:#15171C;box-shadow:none;animation:oglpPage .28s cubic-bezier(.2,.8,.2,1)}
.oglp-card::before{display:none}
.oglp-head{display:grid;grid-template-columns:44px 1fr 56px;align-items:center;gap:8px;padding:calc(env(safe-area-inset-top) + 14px) 16px 14px;background:#FFFFFF;color:#15171C;border-bottom:1px solid #F1F2F4}
.oglp-brand,.oglp-sp,.oglp-head .oglp-x,.oglp-titles,.oglp-foot{display:none!important}
.oglp-back{display:flex;align-items:center;justify-content:center;width:44px;height:44px;margin-inline-start:-8px;border:0;border-radius:50%;background:none;color:#15171C;cursor:pointer;padding:0}
html[dir=rtl] .oglp-back svg{transform:scaleX(-1)}
.oglp-mt{display:block;text-align:center;font-size:24px;font-weight:700;letter-spacing:-.01em;color:#15171C}
.oglp-ok{display:flex;align-items:center;justify-content:center;justify-self:end;width:56px;height:36px;border:0;border-radius:12px;background:#15171C;color:#FFFFFF;cursor:pointer;padding:0;box-shadow:0 8px 18px -10px rgba(21,23,28,.6)}
.oglp-ok:active{transform:scale(.96)}
.oglp-list{flex:1;grid-template-columns:1fr;gap:12px;padding:18px 16px calc(24px + env(safe-area-inset-bottom));overflow:auto}
.oglp-row{min-height:66px;padding:0 18px;border:1.5px solid #ECEEF1;border-radius:16px;background:#FFFFFF;color:#15171C;flex-direction:row-reverse;justify-content:space-between;gap:14px;box-shadow:0 1px 2px rgba(16,24,40,.03)}
html[dir=rtl] .oglp-row{padding:0 18px}
.oglp-row::after{display:none}
.oglp-row:hover{background:#FFFFFF;border-color:#D5D9DF}
.oglp-row.is-on{background:#FFFFFF;border-color:#ECEEF1;color:#15171C}
.oglp-row.is-pick,.oglp-row.is-pick:hover{border-color:#15171C;background:#F8F9FA;box-shadow:0 0 0 1px #15171C}
.oglp-row .ogx2-flag,.oglp-row.is-on .ogx2-flag{width:46px;height:31px;flex:none;border-radius:6px;overflow:hidden;box-shadow:0 4px 10px -4px rgba(21,23,28,.35),0 0 0 1px rgba(21,23,28,.06);display:block}
.oglp-row .ogx2-flag svg{display:block;width:46px;height:46px;margin-top:-7.5px}
.oglp-txt{flex-direction:row;align-items:baseline;gap:6px;flex:1}
.oglp-txt b{font-size:17px;font-weight:500}
.oglp-row.is-pick .oglp-txt b{font-weight:700}
.oglp-txt small,.oglp-row.is-on .oglp-txt small{font-size:15px;color:#6B7280}
.oglp-txt small::before{content:"("}
.oglp-txt small::after{content:")"}
.oglp-row:focus-visible{border-color:#15171C;box-shadow:0 0 0 3px rgba(21,23,28,.15)}
.oglp-back:focus-visible,.oglp-ok:focus-visible{outline:2px solid #15171C;outline-offset:2px}
}
@keyframes oglpPage{from{opacity:0;transform:translateX(24px)}to{opacity:1;transform:none}}
.oglp-visual{display:none}
/* desktop: the language list on the left over the garage photo (Sandero on the right) */
@media (min-width:1025px){
.oglp{background:#0B0F15}
.oglp-card{display:grid!important;grid-template-columns:minmax(460px,600px) minmax(0,1fr);grid-template-rows:minmax(0,1fr) auto auto minmax(0,1fr);grid-template-areas:". visual" "head visual" "list visual" ". visual";color:#FFFFFF!important;background:linear-gradient(90deg,rgba(7,10,15,.82) 0%,rgba(7,10,15,.62) 28%,rgba(7,10,15,.18) 50%,rgba(7,10,15,0) 68%),linear-gradient(0deg,rgba(7,10,15,.45) 0%,rgba(7,10,15,0) 30%),{{OG_LANG_BG}}#0B0F15!important}
.oglp-head{grid-area:head;background:transparent!important;color:#FFFFFF!important;padding:0 44px 22px clamp(36px,5vw,88px)!important;border-bottom:0!important}
.oglp-mt{color:#FFFFFF!important;font-size:30px!important;letter-spacing:-.02em!important}
.oglp-back{background:rgba(255,255,255,.1)!important;color:#FFFFFF!important;border:1px solid rgba(255,255,255,.14)!important;margin-inline-start:0!important;-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px)}
.oglp-back:hover{background:rgba(255,255,255,.18)!important}
.oglp-ok{background:#FFFFFF!important;color:#15171C!important;box-shadow:0 10px 24px -12px rgba(0,0,0,.7)!important}
.oglp-list{grid-area:list;align-content:start;background:transparent!important;padding:4px 44px 8px clamp(36px,5vw,88px)!important;max-width:none;max-height:calc(100vh - 170px);gap:10px!important}
.oglp-row{min-height:62px!important;border:1px solid rgba(255,255,255,.12)!important;background:rgba(255,255,255,.07)!important;color:#FFFFFF!important;box-shadow:none!important;-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px);transition:background .18s ease,border-color .18s ease,transform .18s ease}
.oglp-row:hover{background:rgba(255,255,255,.12)!important;border-color:rgba(255,255,255,.24)!important}
.oglp-row.is-pick,.oglp-row.is-pick:hover{background:rgba(255,255,255,.16)!important;border-color:#FFFFFF!important;box-shadow:0 0 0 1px #FFFFFF!important}
.oglp-row .oglp-txt b{color:#FFFFFF!important}
.oglp-row .oglp-txt small{color:rgba(255,255,255,.55)!important}
.oglp-row:focus-visible{box-shadow:0 0 0 3px rgba(255,255,255,.35)!important}
.oglp-back:focus-visible,.oglp-ok:focus-visible{outline-color:#FFFFFF!important}
.oglp-list::-webkit-scrollbar{width:6px}.oglp-list::-webkit-scrollbar-thumb{background:rgba(255,255,255,.2);border-radius:3px}
.oglp-visual{grid-area:visual;display:block;background:transparent}
.oglp-visual img,.oglp-floor{display:none!important}
html[dir=rtl] .oglp-card{direction:ltr}
html[dir=rtl] .oglp-head,html[dir=rtl] .oglp-list{direction:rtl}
}
/* tablet: the language list centred on the page */
@media (min-width:768px) and (max-width:1024px){
.oglp-head{max-width:640px;width:100%;margin:0 auto}
.oglp-list{max-width:640px;width:100%;margin:0 auto}
}
@media (prefers-reduced-motion:reduce){.oglp-card{animation:none}.oglp-row{transition:none}}
CSS;
}

/* ------------------------------------------------------------------ */
/* Dashboard: Appearance -> Languages                                  */
/* ------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	add_theme_page( orego_L( 'Languages', 'اللغات' ), orego_L( 'Languages', 'اللغات' ), 'manage_options', 'orego-languages', 'orego_languages_page' );
} );

add_action( 'admin_post_orego_langs_save', function () {
	check_admin_referer( 'orego_langs_save' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	$all = array_keys( orego_lang_catalog() );
	$on  = array_values( array_intersect( $all, array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['enabled'] ?? array() ) ) ) );
	if ( ! in_array( 'en', $on, true ) ) {
		$on[] = 'en';
	}
	$on  = array_values( array_intersect( $all, $on ) );
	$def = sanitize_text_field( wp_unslash( $_POST['default'] ?? 'en' ) );
	update_option( 'orego_langs_cfg', array( 'enabled' => $on, 'default' => in_array( $def, $on, true ) ? $def : 'en', 'popup' => ! empty( $_POST['popup'] ) ), false );
	orego_sync_gtranslate();
	do_action( 'litespeed_purge_all' );
	wp_safe_redirect( admin_url( 'themes.php?page=orego-languages&saved=1' ) );
	exit;
} );

function orego_languages_page() {
	$cfg = orego_lang_cfg();
	$cat = orego_lang_catalog();
	$L   = 'orego_L';
	?>
	<div class="wrap">
		<h1><?php echo esc_html( $L( 'Languages', 'اللغات' ) ); ?></h1>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $L( 'Languages saved.', 'تم حفظ اللغات.' ) ); ?></p></div><?php endif; ?>
		<p><?php echo esc_html( $L( 'The website is written in English and translated automatically in the visitor’s browser (GTranslate). The dashboard is never translated.', 'الموقع مكتوب بالإنجليزية ويُترجم تلقائيًا في متصفح الزائر (GTranslate). لا تُترجم لوحة التحكم أبدًا.' ) ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="orego_langs_save"><?php wp_nonce_field( 'orego_langs_save' ); ?>
			<table class="form-table" role="presentation"><tbody>
				<tr><th scope="row"><?php echo esc_html( $L( 'Languages on the website', 'اللغات على الموقع' ) ); ?></th><td><fieldset>
				<?php foreach ( $cat as $code => $l ) : ?>
					<label style="display:flex;align-items:center;gap:10px;margin:0 0 10px"><input type="checkbox" name="enabled[]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $cfg['enabled'], true ) ); ?> <?php disabled( 'en' === $code ); ?>><span style="width:24px;height:24px;border-radius:50%;overflow:hidden;display:inline-block;box-shadow:0 0 0 1px #dcdcde"><?php echo orego_flag( $code ); // phpcs:ignore ?></span><strong><?php echo esc_html( $l[1] ); ?></strong> <?php echo esc_html( $l[0] . ' — ' . $l[3] ); ?><?php if ( 'en' === $code ) : ?><em style="color:#646970">(<?php echo esc_html( $L( 'source language, always on', 'لغة المصدر، مفعّلة دائمًا' ) ); ?>)</em><input type="hidden" name="enabled[]" value="en"><?php endif; ?></label>
				<?php endforeach; ?>
				<p class="description"><?php echo esc_html( $L( 'Visitors who had chosen a language you turn off are switched to the default language.', 'الزوار الذين اختاروا لغة تقوم بإيقافها يُحوّلون إلى اللغة الافتراضية.' ) ); ?></p>
				</fieldset></td></tr>
				<tr><th scope="row"><label for="og-def"><?php echo esc_html( $L( 'Default language', 'اللغة الافتراضية' ) ); ?></label></th><td><select id="og-def" name="default"><?php foreach ( $cfg['enabled'] as $code ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $cfg['default'], $code ); ?>><?php echo esc_html( $cat[ $code ][1] . ' — ' . $cat[ $code ][0] ); ?></option><?php endforeach; ?></select><p class="description"><?php echo esc_html( $L( 'Shown to new visitors who close the popup or don’t see it.', 'تظهر للزوار الجدد الذين يغلقون النافذة أو لا تظهر لهم.' ) ); ?></p></td></tr>
				<tr><th scope="row"><?php echo esc_html( $L( 'First-visit popup', 'نافذة الزيارة الأولى' ) ); ?></th><td><label><input type="checkbox" name="popup" value="1" <?php checked( $cfg['popup'] ); ?>> <?php echo esc_html( $L( 'Ask new visitors to choose their language', 'اسأل الزوار الجدد عن لغتهم' ) ); ?></label></td></tr>
			</tbody></table>
			<?php submit_button( $L( 'Save languages', 'حفظ اللغات' ) ); ?>
		</form>
	</div>
	<?php
}
