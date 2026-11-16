<?php
/**
 * Seamless step from the welcome (swipe) screen to the language page:
 * - the welcome screen downloads the next page and the language photo in advance
 * - the browser cross-fades from one screen to the other (where supported)
 * - on the next page the language page is shown from the very first frame,
 *   with nothing of the page behind it and no second slide-in animation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Language page background colour on phones (white, or dark when a phone photo is set). */
function orego_handoff_phone_bg() {
	return ( function_exists( 'orego_intro_img' ) && '' !== orego_intro_img( 'og_lang_bg_m' ) ) ? '#0B0F15' : '#FFFFFF';
}

/* Printed inside the welcome page's <head>. */
function orego_handoff_welcome_head() {
	$next = orego_purl( 'cars' );
	if ( $next ) {
		echo '<link rel="prefetch" href="' . esc_url( $next ) . '" as="document">' . "\n";
	}
	if ( function_exists( 'orego_intro_img' ) ) {
		$desk  = orego_intro_img( 'og_lang_bg' );
		$phone = orego_intro_img( 'og_lang_bg_m' );
		if ( '' !== $desk ) {
			echo '<link rel="preload" as="image" href="' . esc_url( $desk ) . '" media="(min-width:1025px)">' . "\n";
		}
		if ( '' !== $phone ) {
			echo '<link rel="preload" as="image" href="' . esc_url( $phone ) . '" media="(max-width:1024px)">' . "\n";
		}
	}
	echo '<style id="og-handoff">'
		. '@view-transition{navigation:auto}'
		. '::view-transition-old(root),::view-transition-new(root){animation-duration:.45s;animation-timing-function:ease}'
		// Browsers without the cross-fade: the screen slides away onto the language page colour.
		. 'body{transition:background-color .4s ease}'
		. 'body.is-leaving{background:' . orego_handoff_phone_bg() . '}'
		. '@media (min-width:1025px){body.is-leaving{background:#0B0F15}}'
		. '</style>' . "\n";
}

/* Every other page: when arriving from the swipe, show the language page from the first frame. */
add_action( 'wp_head', function () {
	if ( is_admin() || is_customize_preview() || isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore
		return;
	}
	if ( function_exists( 'orego_welcome_is_page' ) && orego_welcome_is_page() ) {
		return;
	}
	if ( function_exists( 'orego_is_editor' ) && orego_is_editor() ) {
		return;
	}
	echo '<style id="og-lp-boot-css">'
		. 'html.og-lp-boot{background:' . orego_handoff_phone_bg() . '}'
		. '@media (min-width:1025px){html.og-lp-boot{background:#0B0F15}}'
		. 'html.og-lp-boot body{visibility:hidden}'
		. 'html.og-lp-boot .oglp{display:flex!important;visibility:visible}'
		. 'html.og-lp-still .oglp-card{animation:none!important}'
		. '</style>' . "\n";
	// Runs before anything is drawn. The language script (end of the page) opens the page for real;
	// once the page is read, the cover is lifted and the language page simply stays.
	echo '<script id="og-lp-boot">(function(){'
		. 'try{if(sessionStorage.getItem("og_lang_after_welcome")!=="1"||localStorage.getItem("og_lang"))return;}catch(e){return}'
		. 'var h=document.documentElement;h.classList.add("og-lp-boot","og-lp-still");'
		. 'var s=document.createElement("style");s.textContent="@view-transition{navigation:auto}::view-transition-old(root),::view-transition-new(root){animation-duration:.45s;animation-timing-function:ease}";document.head.appendChild(s);'
		. 'document.addEventListener("DOMContentLoaded",function(){var p=document.getElementById("oglp");h.classList.remove("og-lp-boot");'
		. 'if(!p||p.hidden){h.classList.remove("og-lp-still");return}'
		. 'new MutationObserver(function(m,o){if(p.hidden){h.classList.remove("og-lp-still");o.disconnect()}}).observe(p,{attributes:true,attributeFilter:["hidden"]})});'
		. '})();</script>' . "\n";
}, -1001 );
