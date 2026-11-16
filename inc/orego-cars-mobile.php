<?php
/**
 * Browse cars page (id 25): compact title area and one sticky Filters + Sort bar on phones and tablets.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', function () {
	if ( ! orego_is_page( 'cars' ) ) {
		return;
	}
	?>
<style id="og-cars-mobile">
@media (max-width:1024px){
.elementor-25 .elementor-element-5bcbdba{padding-top:18px!important;border-bottom:0!important;gap:0!important}
.elementor-25 .elementor-element-1e3f799,.elementor-25 .elementor-element-1e3f799>.e-con-inner{padding-top:0!important;padding-bottom:0!important;gap:0!important}
.elementor-25 .elementor-element-93180fd{flex-direction:row!important;flex-wrap:nowrap!important;align-items:center!important;justify-content:space-between!important;gap:12px!important;width:100%!important}.elementor-25 .elementor-element-09a869f{order:1;flex:none!important;margin:0!important}.elementor-25 .elementor-element-fdfe9c2{order:2;flex:0 1 auto!important;width:auto!important;margin:0!important;text-align:end}.elementor-25 .elementor-element-fdfe9c2 .elementor-heading-title{text-align:end!important;white-space:nowrap}.elementor-25 .elementor-element-caffd72{display:none!important}
.elementor-25 .elementor-element-09a869f .og-avail-pill{font-size:12.5px;padding:4px 11px;gap:7px}
.elementor-25 .elementor-element-fdfe9c2 .elementor-heading-title{font-size:26px!important;line-height:1.15!important;margin:0!important}
.elementor-25 .elementor-element-caffd72 .elementor-icon-list-item{align-items:flex-start!important}
.elementor-25 .elementor-element-caffd72 .elementor-icon-list-icon{margin-top:4px}
.elementor-25 .elementor-element-caffd72 .elementor-icon-list-icon svg,.elementor-25 .elementor-element-caffd72 .elementor-icon-list-icon i{width:13px!important;height:13px!important;font-size:13px!important}
.elementor-25 .elementor-element-caffd72 .elementor-icon-list-text{font-size:14px!important;line-height:1.45!important;padding-inline-start:6px!important}
body.og-sort-moved .elementor-25 .elementor-element-a12ea68{display:none!important}
/* one sticky bar: Filters + Sort */
.elementor-25 .elementor-element-39b84d6{position:sticky!important;top:calc(var(--wp-admin--admin-bar--height,0px) + var(--ogh-h,0px))!important;z-index:40!important;background:var(--og-barbg,#f3f3f4);box-shadow:0 0 0 100vmax var(--og-barbg,#f3f3f4);clip-path:inset(0 -100vmax);padding:10px 0!important;margin-top:0!important;transition:box-shadow .2s}
.elementor-25 .elementor-element-39b84d6.is-stuck{clip-path:inset(0 -100vmax -12px);box-shadow:0 0 0 100vmax var(--og-barbg,#f3f3f4),0 10px 12px -10px rgba(15,23,42,.18)}
/* filters open: the panel and its dark backdrop live inside this bar, so stop clipping it and lift it above the page */
body.og-fopen .elementor-25 .elementor-element-39b84d6{clip-path:none!important;box-shadow:none!important;z-index:100000!important}
.og-fbar{display:flex!important;align-items:center;gap:10px;padding:0!important;margin:0!important}
.og-fbar .og-fbtn{flex:1 1 0;justify-content:center;height:44px;margin:0}
.og-fbar .og-sort{flex:1 1 0;min-width:0;margin:0;display:block}
.og-fbar .og-sort select{width:100%;min-width:0!important;height:44px!important;padding:0 38px 0 16px!important;font-size:14px!important;font-weight:700!important;border:1.5px solid #e2e5ea!important;box-shadow:0 4px 14px rgba(21,23,28,.08)!important;background-position:right 14px center!important;text-overflow:ellipsis}
html[dir=rtl] .og-fbar .og-sort select{padding:0 16px 0 38px!important;background-position:left 14px center!important}
.og-fbar .og-fbar-n{display:none!important}
.elementor-25 .elementor-element-b45c340,.elementor-25 .elementor-element-b45c340>.e-con-inner{padding-top:6px!important}
}
</style>
<?php
}, 60 );

add_action( 'wp_footer', function () {
	if ( ! orego_is_page( 'cars' ) ) {
		return;
	}
	?>
<script id="og-cars-mobile-js">
(function(){var mq=window.matchMedia('(max-width:1024px)'),s=document.querySelector('.og-sort'),fb=document.querySelector('.og-fbar');if(!s||!fb)return;document.documentElement.style.setProperty('--og-barbg',getComputedStyle(document.body).backgroundColor);var home=s.parentNode,next=s.nextSibling;
function place(){if(mq.matches){if(s.parentNode!==fb)fb.appendChild(s);document.body.classList.add('og-sort-moved')}else{if(s.parentNode!==home)home.insertBefore(s,next);document.body.classList.remove('og-sort-moved')}}
place();if(mq.addEventListener)mq.addEventListener('change',place);else mq.addListener(place);
var bar=document.querySelector('.elementor-25 .elementor-element-39b84d6');if(bar&&'IntersectionObserver' in window){var sen=document.createElement('div');sen.style.cssText='height:1px;margin-bottom:-1px';bar.parentNode.insertBefore(sen,bar);new IntersectionObserver(function(e){bar.classList.toggle('is-stuck',!e[0].isIntersecting&&e[0].boundingClientRect.top<0)}).observe(sen)}})();
</script>
<?php
}, 30 );