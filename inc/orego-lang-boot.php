<?php
/**
 * Exotic Cars language boot: runs first in <head>.
 * - New visitors get the default language (Appearance -> Languages) applied on the very first page load, no reload.
 * - While Google translates a non-Arabic page, the page stays hidden for a moment so visitors never see the Arabic source flash.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', function () {
	if ( is_admin() || ( function_exists( 'orego_is_editor' ) && orego_is_editor() ) || ! function_exists( 'orego_lang_cfg' ) ) {
		return;
	}
	$cfg = orego_lang_cfg();
	$rtl = array_keys( array_filter( orego_lang_catalog(), fn( $l ) => 'rtl' === $l[2] ) );
	$b   = array( 'def' => $cfg['default'], 'enabled' => $cfg['enabled'], 'rtl' => $rtl, 'lmap' => (object) ( function_exists( 'orego_currency_lang_map' ) ? orego_currency_lang_map() : array( 'ar' => 'MAD', 'en' => 'USD' ) ), 'cdef' => function_exists( 'orego_currency_default' ) ? orego_currency_default() : 'EUR' );
	echo '<style id="og-lang-boot-css">html::after{content:"";position:fixed;inset:0;z-index:2147483646;background:#0B0A09;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .3s ease,visibility 0s linear .3s}html.og-pretrans::after{opacity:1;visibility:visible;transition:none}</style>';
	echo '<script id="og-lang-boot">(function(B){var ls;try{ls=window.localStorage}catch(e){}if(!ls)return;var h=document.documentElement,t=null;'
		. 'try{var s=JSON.parse(ls.getItem("__GT_TRANSLATE_LANGS"));if(s&&typeof s.tgtLang==="string"){t=s.tgtLang;if(s.srcLang!=="en"){if(t==="en"||B.enabled.indexOf(t)<0){ls.removeItem("__GT_TRANSLATE_LANGS");t=null}else{ls.setItem("__GT_TRANSLATE_LANGS",JSON.stringify({srcLang:"en",tgtLang:t}))}}}if(t&&t!=="en"&&B.enabled.indexOf(t)<0){t=(t==="ary"&&B.enabled.indexOf("ar")>-1)?"ar":null;if(t){ls.setItem("__GT_TRANSLATE_LANGS",JSON.stringify({srcLang:"en",tgtLang:t}));ls.setItem("og_lang",t)}else{ls.removeItem("__GT_TRANSLATE_LANGS")}}}catch(e){}'
		. 'if(/googtrans=\/[a-z]+\/ary/.test(document.cookie)){document.cookie="googtrans=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";document.cookie="googtrans=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;domain="+location.hostname}'
		. 'if(/googtrans=\/ar\//.test(document.cookie)){document.cookie="googtrans=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/"}'
		. 'if(!t&&!ls.getItem("og_lang")&&B.def!=="en"&&B.enabled.indexOf(B.def)>-1){t=B.def;try{ls.setItem("__GT_TRANSLATE_LANGS",JSON.stringify({srcLang:"en",tgtLang:t}));ls.setItem("og_lang",t)}catch(e){}window.OG_FIRST=1}'
		. 'try{var cl=t||"en";if(ls.getItem("og_cur_lang")!==cl){ls.setItem("og_cur",B.lmap[cl]||B.cdef);ls.setItem("og_cur_lang",cl)}}catch(e){}'
		. 'if(!t||t==="en")return;var r=B.rtl.indexOf(t)>-1;h.setAttribute("dir",r?"rtl":"ltr");if(r){h.classList.remove("translated-ltr","og-ltr")}h.setAttribute("lang",t);h.classList.add("og-pretrans");'
		. 'var done=false;function show(){if(done)return;done=true;h.classList.add("og-reveal");h.classList.remove("og-pretrans")}'
		. 'setTimeout(show,2500);if(window.MutationObserver){var mo=new MutationObserver(function(){if(document.querySelector("body font")){mo.disconnect();setTimeout(show,120)}});document.addEventListener("DOMContentLoaded",function(){mo.observe(document.body,{childList:true,subtree:true})})}'
		. '})(' . wp_json_encode( $b ) . ');</script>' . "\n";
}, 0 );

/** The site is written in English: the page starts left-to-right (also in the Elementor editor). The language script switches to right-to-left for Arabic. */
add_filter( 'language_attributes', function ( $out ) {
	if ( is_admin() ) {
		return $out;
	}
	$out = preg_replace( '/\sdir="[^"]*"/', '', $out );
	return $out . ' dir="ltr" class="translated-ltr og-ltr"';
} );
