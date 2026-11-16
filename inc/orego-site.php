<?php
/**
 * Exotic Cars site styles that Elementor's own settings can't express
 * (moved here from Appearance -> Customize -> Additional CSS, which is left for the site owner).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'orego-site', false, array(), '1.0' );
	wp_enqueue_style( 'orego-site' );
	wp_add_inline_style( 'orego-site', <<<'CSS'
/* Homepage hero */
.orego-hero{overflow:hidden}
.orego-hero>.e-con-inner{position:relative;z-index:2}
/* Links that jump to a section stop below the pinned header */
html{scroll-padding-top:calc(var(--ogh-h,72px) + var(--wp-admin--admin-bar--height,0px) + 12px)}
/* Browse Cars: filter sidebar stays in view below the header */
@media (min-width:1025px){.og-side-wrap{position:sticky;top:calc(var(--ogh-h,72px) + var(--wp-admin--admin-bar--height,0px) + 16px)}}
/* Car page on phones: price and rating centred together */
@media (max-width:767px){body .ogx-pr{justify-content:center;gap:18px}.ogx-rate{position:relative;padding-inline-start:18px}.ogx-rate::before{content:"";position:absolute;inset-inline-start:0;top:50%;width:1px;height:26px;transform:translateY(-50%);background:#E2E5EA}}
/* Car page on phones and tablets: Book now stays pinned to the bottom of the screen */
@media (max-width:900px){body.og-car-page .ogx-cta{position:fixed;left:0;right:0;bottom:0;top:auto;z-index:60;margin:0;padding:10px 16px calc(10px + env(safe-area-inset-bottom));background:rgba(255,255,255,.94);-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px);border-top:1px solid #E6E8EC;box-shadow:0 -8px 24px -14px rgba(21,23,28,.22)}body.og-car-page{padding-bottom:calc(76px + env(safe-area-inset-bottom))}}
/* ...and gives a short wiggle every few seconds so visitors notice it (not for people who turned off motion) */
@keyframes og-cta-wiggle{0%,12%,100%{transform:translateX(0) rotate(0)}2%{transform:translateX(-6px) rotate(-1.5deg)}4%{transform:translateX(6px) rotate(1.5deg)}6%{transform:translateX(-4px) rotate(-1deg)}8%{transform:translateX(4px) rotate(1deg)}10%{transform:translateX(-2px) rotate(0)}}
@media (max-width:900px) and (prefers-reduced-motion:no-preference){body.og-car-page .ogx-cta .ogx-rent{animation:og-cta-wiggle 6s ease-in-out 1.5s infinite}body.og-car-page .ogx-cta .ogx-rent:active,body.og-car-page .ogx-cta .ogx-rent:focus-visible{animation:none}}
/* Colour under the page edges (desktop dark, phones white) */
html{background-color:#111827}
@media (max-width:767px){html{background-color:#FFFFFF}}
/* Left-to-right languages */
html[dir=ltr] .elementor{text-align:left}
html[dir=ltr] .elementor .e-fas-arrow-left,html[dir=ltr] .elementor .fa-arrow-left{transform:scaleX(-1)}
html[dir=ltr] .og-sort select{background-position:right 18px center;padding:12px 48px 12px 26px}
CSS
	);
}, 29 );
