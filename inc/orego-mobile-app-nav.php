<?php
/**
 * Phones only (up to 767px): app-style navigation.
 * - Top bar (search pill + filter button) on the Brands page and car pages only; other pages keep the site header.
 *   It replaces the site header; on pages with a photo at the top (header "over the photo") it floats over it.
 * - Bottom tab bar: Home, Cars, Brands, Contact, Menu (Menu opens the existing side menu with language and currency).
 *   On car pages and the booking page, their own bottom bar (Book now / Continue) sits right above it.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_app_nav_where() {
	static $w = null;
	if ( null !== $w ) {
		return $w;
	}
	$editor = ( function_exists( 'orego_is_editor' ) && orego_is_editor() ) || isset( $_GET['elementor-preview'] ); // phpcs:ignore
	$book   = (int) get_option( 'orego_booking_page' );
	$w      = array(
		'on'   => ! is_admin() && ! $editor && ! ( function_exists( 'orego_welcome_is_page' ) && orego_welcome_is_page() ),
		'top'  => orego_is_page( 'brands' ) || is_singular( 'orego_car' ), // search + filter bar instead of the header: Brands page and car pages only
		'tabs' => true, // every page; car and booking pages stack their own bottom bar above it
	);
	return $w;
}

add_filter( 'body_class', function ( $c ) {
	$w = orego_app_nav_where();
	if ( $w['on'] ) {
		$c[] = 'og-app';
		if ( $w['top'] ) {
			$c[] = 'og-app-top';
		}
		if ( $w['tabs'] ) {
			$c[] = 'og-app-tabs';
		}
	}
	return $c;
} );

function orego_app_nav_icon( $n ) {
	$p = array(
		'back'   => '<path d="M19 12H6M11 6.5 5.5 12l5.5 5.5"/>',
		'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'filter' => '<circle cx="7" cy="7" r="2.4"/><path d="M12 7h8M4 17h8"/><circle cx="17" cy="17" r="2.4"/>',
		'home'   => '<path d="M4 10.5 12 4l8 6.5V19a1 1 0 0 1-1 1h-4.5v-5.5h-5V20H5a1 1 0 0 1-1-1z"/>',
		'car'    => '<path d="M5 16.5h14M6.2 11 8 7.2A2 2 0 0 1 9.8 6h4.4a2 2 0 0 1 1.8 1.2L17.8 11M4.5 11h15a1 1 0 0 1 1 1v4.5a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1V12a1 1 0 0 1 1-1z"/><circle cx="7.5" cy="17.5" r="1.6"/><circle cx="16.5" cy="17.5" r="1.6"/>',
		'brands' => '<rect x="4" y="4" width="6.5" height="6.5" rx="3.25"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="3.25"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="3.25"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="3.25"/>',
		'chat'   => '<path d="M5 18.5V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H8.5z"/><path d="M9 10h6M9 13h3.5"/>',
		'menu'   => '<path d="M4.5 7h15M4.5 12h15M4.5 17h15"/>',
		'map'    => '<path d="M9 5 4.5 6.8v12.4L9 17.4l6 1.8 4.5-1.8V5L15 6.8z"/><path d="M9 5v12.4M15 6.8v12.4"/>',
		'map-on' => '<path fill="currentColor" d="M9 5 4.5 6.8v12.4L9 17.4l6 1.8 4.5-1.8V5L15 6.8z"/><path stroke="#fff" d="M9 5v12.4M15 6.8v12.4"/>',
		'info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 7.8v.1"/>',
		'info-on' => '<circle cx="12" cy="12" r="8.5" fill="currentColor"/><path stroke="#fff" d="M12 11v5M12 7.8v.1"/>',
		'booking' => '<rect x="5" y="3.5" width="14" height="17" rx="2.5"/><path d="M9 8.5h6M9 12h6M9 15.5h3.5"/>',
		'booking-on' => '<rect x="5" y="3.5" width="14" height="17" rx="2.5" fill="currentColor"/><path stroke="#fff" d="M9 8.5h6M9 12h6M9 15.5h3.5"/>',
		'home-on'   => '<path fill="currentColor" d="M4 10.5 12 4l8 6.5V19a1 1 0 0 1-1 1h-4.5v-5.5h-5V20H5a1 1 0 0 1-1-1z"/>',
		'brands-on' => '<g fill="currentColor"><rect x="4" y="4" width="6.5" height="6.5" rx="3.25"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="3.25"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="3.25"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="3.25"/></g>',
		'chat-on'   => '<path fill="currentColor" d="M5 18.5V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H8.5z"/><path stroke="#fff" d="M9 10h6M9 13h3.5"/>',
		'menu-on'   => '<path stroke-width="2.4" d="M4.5 7h15M4.5 12h15M4.5 17h15"/>',
		'car-solid' => '<path fill="currentColor" stroke="none" d="M7.6 6.6A2.6 2.6 0 0 1 10 5h4a2.6 2.6 0 0 1 2.4 1.6l1.5 3.6h.6A1.5 1.5 0 0 1 20 11.7v4.6a1.2 1.2 0 0 1-1.2 1.2h-.7a2.4 2.4 0 0 1-4.7 0h-2.8a2.4 2.4 0 0 1-4.7 0h-.7A1.2 1.2 0 0 1 4 16.3v-4.6a1.5 1.5 0 0 1 1.5-1.5h.6zm1.6.5-1.3 3.1h8.2l-1.3-3.1a.9.9 0 0 0-.8-.6H10a.9.9 0 0 0-.8.6zM8.7 13.4a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm6.6 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $n ] . '</svg>';
}

add_action( 'wp_head', function () {
	if ( ! orego_app_nav_where()['on'] ) {
		return;
	}
	?>
<style id="og-app-nav">
.og-appbar,.og-tabs{display:none}
@media (max-width:767px){
/* top bar replaces the site header */
body.og-app-top header.ogx2{display:none!important}
body.og-app-top{--og-app-h:82px}
.og-appbar{display:flex;align-items:center;position:absolute;left:0;right:0;top:0;z-index:150;padding:calc(env(safe-area-inset-top) + 12px) 16px 12px;background:#FFFFFF;box-shadow:0 8px 24px -18px rgba(21,23,28,.35);transition:background-color .2s,box-shadow .2s}
body.og-app-top .og-app-spacer{display:block;height:calc(env(safe-area-inset-top) + 82px)}
/* pages with a photo at the top: the bar floats over it until you scroll */
body.og-app-overlay .og-appbar{background:transparent;box-shadow:none}
body.og-app-overlay .og-app-spacer{display:none}
body.og-app-overlay .og-appbar.is-solid{background:#FFFFFF;box-shadow:0 8px 24px -18px rgba(21,23,28,.35)}
.og-appbar-search{flex:1;min-width:0;display:flex;align-items:center;height:58px;border-radius:16px;background:#F3F3F4;padding:0 8px 0 18px}
html[dir=rtl] .og-appbar-search{padding:0 18px 0 8px}
body.og-app-overlay .og-appbar:not(.is-solid) .og-appbar-search{background:rgba(255,255,255,.94)}
.og-appbar-search>svg{flex:none;width:22px;height:22px;color:#8A8D94}
.og-appbar-search input{flex:1;min-width:0;height:100%;border:0!important;background:transparent!important;box-shadow:none!important;outline:none;padding:0 12px!important;margin:0;font:inherit;font-size:17px;color:#15171C}
.og-appbar-search input::placeholder{color:#9A9CA3}
.og-appbar-search:focus-within{box-shadow:0 0 0 2px #15171C}
.og-appbar-filter{flex:none;position:relative;display:flex;align-items:center;justify-content:center;width:44px;height:44px;border:0;border-radius:12px;background:transparent;color:#15171C;cursor:pointer;padding:0;text-decoration:none}
.og-appbar-filter svg{width:22px;height:22px}
.og-appbar-filter:active{background:#E6E6E8}
.og-appbar-filter b{position:absolute;top:4px;right:2px;min-width:17px;height:17px;padding:0 4px;border-radius:999px;background:#15171C;color:#fff;font:700 10px/17px system-ui,sans-serif;text-align:center}
.og-appbar-filter b:empty{display:none}
/* Cars page: its sticky filter/sort bar sits under the app bar */
body.og-app-top .og-fbar{top:calc(env(safe-area-inset-top) + 82px)!important}
/* Cars page on phones: no title / count block and no Filters / Sort bar; the search bar's filter icon opens the filters */
body.og-app-top .elementor > .e-con:has(.og-br-title){display:none!important}
body.og-app-top .og-fbar{display:none!important}
body.og-app-top .og-cars-wrap{padding-top:0!important}
body.og-app-top .og-side-wrap{height:0!important;min-height:0!important;padding:0!important;margin:0!important;overflow:visible;position:static!important;z-index:auto!important}
body.og-app-top .og-side.is-open{z-index:100000}
/* the old sticky Filters bar style (orego-cars-mobile.php) clipped the filters panel to its box: off when the search bar is used */
body.og-app-top .elementor .elementor-element.og-side-wrap{clip-path:none!important;box-shadow:none!important;background:transparent!important;position:static!important;top:auto!important;z-index:auto!important}
body.og-app-top .og-side-wrap>.elementor-widget-container{padding:0!important;margin:0!important}
body.og-app-top .e-con:has(> .og-side-wrap),body.og-app-top .e-con-inner:has(> .og-side-wrap){gap:0!important;row-gap:0!important;padding-top:16px!important}
.og-sort-group{padding:14px 0 16px;border-bottom:1px solid #F1F2F5}
.og-sort-group .og-fg-t{margin-bottom:10px}
.og-sort-group .og-sort{display:block!important;width:100%}
.og-sort-group .og-sort select{width:100%!important;min-width:0!important}
.og-qchip{display:flex;align-items:center;gap:10px;margin:0 0 14px;font-size:14px;color:#4B4D53}
.og-qchip b{color:#15171C}
.og-qchip a{margin-inline-start:auto;font-weight:700;color:#15171C;text-decoration:underline;text-underline-offset:3px}
/* Collections already has this search on the page: keep just one */
body.og-app-top .ogc-search{display:none}
body.og-app-top .ogc{padding-top:12px}
/* bottom tab bar */
.og-tabs{display:flex;align-items:center;position:fixed;left:0;right:0;bottom:0;z-index:150;padding:8px 8px calc(8px + env(safe-area-inset-bottom));background:rgba(255,255,255,.97);box-shadow:0 -6px 20px -14px rgba(21,23,28,.25);-webkit-backdrop-filter:saturate(1.4) blur(14px);backdrop-filter:saturate(1.4) blur(14px);border-top:1px solid #E8E9EC}
body:not(.og-app-tabs) .og-tabs{display:none}
body.og-app-tabs{padding-bottom:calc(74px + env(safe-area-inset-bottom))}
.og-tab{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;min-height:54px;border:0;background:none;padding:0;color:#9A9CA3;font:inherit;font-size:12px;font-weight:500;text-decoration:none;cursor:pointer;-webkit-tap-highlight-color:transparent}
.og-tab svg{width:25px;height:25px}
.og-tab.is-on{color:#15171C;font-weight:700}
.og-tab:focus-visible{outline:2px solid #15171C;outline-offset:-4px;border-radius:12px}
/* Cars: the big black round button in the middle */
.og-tab-main{flex:0 0 76px}
.og-tab-fab{display:flex;align-items:center;justify-content:center;width:58px;height:58px;border-radius:50%;background:#15171C;color:#FFFFFF;box-shadow:0 8px 18px -8px rgba(21,23,28,.55);transition:transform .15s}
.og-tab-fab svg{width:28px;height:28px}
.og-tab-main:active .og-tab-fab{transform:scale(.94)}
.og-tab-main.is-on .og-tab-fab{box-shadow:0 0 0 4px #FFFFFF,0 0 0 6px #15171C}
.og-tab-main:focus-visible{outline:none}.og-tab-main:focus-visible .og-tab-fab{outline:2px solid #15171C;outline-offset:3px}
/* car page (Book now) and booking page (total + Continue): their bar sits right above the tab bar */
body.og-app-tabs.og-car-page{padding-bottom:calc(74px + 76px + env(safe-area-inset-bottom))}
body.og-app-tabs.og-car-page .ogx-cta{bottom:calc(74px + env(safe-area-inset-bottom))!important;padding-bottom:10px!important;box-shadow:none!important}
body.og-app-tabs .ogb-bar{bottom:calc(74px + env(safe-area-inset-bottom))!important;padding-bottom:12px!important}
body.og-app-tabs:has(.ogb-bar){padding-bottom:calc(74px + 78px + env(safe-area-inset-bottom))}
}
</style>
	<?php
}, 40 );

add_action( 'wp_body_open', 'orego_app_nav_top', 1 );
function orego_app_nav_top() {
	static $done = false;
	$w = orego_app_nav_where();
	if ( $done || ! $w['on'] || ! $w['top'] ) {
		return;
	}
	$done = true;
	$cars = function_exists( 'orego_cz_cars_page_url' ) ? orego_cz_cars_page_url() : home_url( '/vehicles/' );
	$q    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore
	?>
<div class="og-appbar" id="og-appbar" role="navigation" aria-label="Quick navigation">
	<form class="og-appbar-search" role="search" action="<?php echo esc_url( $cars ); ?>" method="get">
		<?php echo orego_app_nav_icon( 'search' ); // phpcs:ignore ?>
		<input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Search" aria-label="Search cars" autocomplete="off" enterkeyhint="search">
	</form>
</div>
<div class="og-app-spacer" aria-hidden="true" hidden></div>
	<?php
}

add_action( 'wp_footer', function () {
	$w = orego_app_nav_where();
	if ( ! $w['on'] ) {
		return;
	}
	if ( $w['top'] ) {
		orego_app_nav_top(); // in case the theme has no wp_body_open; the script moves it to the top
	}
	$cars    = function_exists( 'orego_cz_cars_page_url' ) ? orego_cz_cars_page_url() : home_url( '/vehicles/' );
	$coll    = ( orego_pid( 'brands' ) ? get_post( orego_pid( 'brands' ) ) : null ); // Brands page
	$contact = get_page_by_path( 'contact-us' );
	$here    = untrailingslashit( strtok( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), '?' ) ); // phpcs:ignore
	$tabs    = array(
		array( 'home', 'Home', home_url( '/' ) ),
		array( 'map', 'Map', orego_purl( 'map' ) ),
		array( 'car', 'Cars', $cars ), // the big round button in the middle
		array( 'chat', 'Contact', $contact ? get_permalink( $contact ) : home_url( '/contact-us/' ) ),
	);
	?>
<?php if ( $w['tabs'] ) : ?>
<nav class="og-tabs" aria-label="Main">
	<?php foreach ( $tabs as $t ) :
		$path = untrailingslashit( (string) wp_parse_url( $t[2], PHP_URL_PATH ) );
		$on   = $path === $here;
		?>
		<?php if ( 'car' === $t[0] ) : ?>
		<a class="og-tab og-tab-main<?php echo $on ? ' is-on' : ''; ?>" href="<?php echo esc_url( $t[2] ); ?>" aria-label="Cars"<?php echo $on ? ' aria-current="page"' : ''; ?>><span class="og-tab-fab"><?php echo orego_app_nav_icon( 'car-solid' ); // phpcs:ignore ?></span></a>
		<?php else : ?>
		<a class="og-tab<?php echo $on ? ' is-on' : ''; ?>" href="<?php echo esc_url( $t[2] ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>><?php echo orego_app_nav_icon( $on ? $t[0] . '-on' : $t[0] ); // phpcs:ignore ?><span><?php echo esc_html( $t[1] ); ?></span></a>
		<?php endif; ?>
	<?php endforeach; ?>
	<button type="button" class="og-tab" id="og-app-menu" aria-label="Menu: pages, language and currency"><?php echo orego_app_nav_icon( 'menu' ); // phpcs:ignore ?><span>Menu</span></button>
</nav>
<?php endif; ?>
<script id="og-app-nav-js">
(function(){
	var mq=window.matchMedia('(max-width:767px)'),bar=document.getElementById('og-appbar');
	if(bar){
		// keep the bar first in the page
		var sp=bar.nextElementSibling&&bar.nextElementSibling.classList.contains('og-app-spacer')?bar.nextElementSibling:null;
		if(document.body.firstElementChild!==bar){document.body.insertBefore(bar,document.body.firstChild);if(sp)bar.after(sp);}
		if(sp)sp.hidden=false;
		// photo at the top (header was "over the photo"): float the bar, turn solid on scroll
		var hd=document.querySelector('header.ogx2');
		if(hd&&hd.classList.contains('is-overlay'))document.body.classList.add('og-app-overlay');
		function solid(){bar.classList.toggle('is-solid',window.scrollY>12);}
		window.addEventListener('scroll',solid,{passive:true});solid();
		// back: previous page on this site, else the homepage
		var bk=document.getElementById('og-app-back');if(bk)bk.addEventListener('click',function(){
			var same=false;try{same=document.referrer&&new URL(document.referrer).origin===location.origin;}catch(e){}
			if(same&&history.length>1){history.back();}else{location.href=<?php echo wp_json_encode( home_url( '/' ) ); ?>;}
		});
		// filter: on the Cars page open its filters, elsewhere go there with the filters open
		var fb=document.querySelector('.og-fbtn'),fl=document.getElementById('og-app-filter'),fc=document.getElementById('og-app-fcount');
		if(fb&&fl){fl.addEventListener('click',function(e){if(!mq.matches)return;e.preventDefault();fb.click();var sd=document.querySelector('.og-side');if(sd&&!sd.classList.contains('is-open')){sd.classList.add('is-open');document.body.classList.add('og-fopen');var sc=document.querySelector('.og-scrim');if(sc){sc.hidden=false;sc.classList.add('is-on');}}});
			var badge=document.querySelector('.og-fbadge');if(badge&&fc){var sync=function(){fc.textContent=badge.hidden?'':badge.textContent;};new MutationObserver(sync).observe(badge,{attributes:true,childList:true,characterData:true,subtree:true});sync();}}
	}
	if(fbOpenFromHash()){}
	function fbOpenFromHash(){if(location.hash!=='#filters')return false;var go=function(){var b=document.querySelector('.og-fbtn');if(b&&mq.matches){b.click();history.replaceState(null,'',location.pathname+location.search);}};if(document.readyState==='complete')setTimeout(go,300);else window.addEventListener('load',function(){setTimeout(go,300);});return true;}
	// Cars page on phones: "Sort by" goes to the top of the filters panel (the Filters / Sort bar is hidden)
	function placeSort(){var srt=document.querySelector('.og-sort'),body=document.querySelector('.og-side .og-side-body');if(!srt||!body||!document.body.classList.contains('og-app-top'))return;var g=document.getElementById('og-sort-group');
		if(mq.matches){if(!g){g=document.createElement('div');g.id='og-sort-group';g.className='og-sort-group';g.innerHTML='<div class="og-fg-t">Sort by</div>';body.insertBefore(g,body.firstChild);}if(srt.parentNode!==g)g.appendChild(srt);}}
	window.addEventListener('load',function(){setTimeout(placeSort,50);});if(mq.addEventListener)mq.addEventListener('change',function(){setTimeout(placeSort,50);});
	// Show what was searched, with a way to clear it (the page title is hidden on phones)
	var qv=new URLSearchParams(location.search).get('q'),grid=document.querySelector('.og-cars-wrap .og-cars');
	if(qv&&grid&&document.body.classList.contains('og-app-top')){var chip=document.createElement('p');chip.className='og-qchip';chip.innerHTML='<span>Results for <b></b></span><a>Clear</a>';chip.querySelector('b').textContent='“'+qv+'”';chip.querySelector('a').href=location.pathname;grid.parentNode.insertBefore(chip,grid);}
	// Menu tab: the site's side menu (pages, language, currency)
	var mb=document.getElementById('og-app-menu');
	if(mb)mb.addEventListener('click',function(){var b=document.querySelector('.ogx2-burger');if(b)b.click();});
})();
</script>
	<?php
}, 30 );

/* Bottom tab bar: slides away while scrolling down, comes back on scroll up or at the end of the page. */
add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	?>
<style id="og-tabs-autohide">
@media (max-width:767px){
body.og-app-tabs .og-tabs{transition:transform .32s cubic-bezier(.2,.8,.2,1);will-change:transform}
body.og-app-tabs.og-tabs-away .og-tabs{transform:translateY(calc(100% + 44px))}
body.og-app-tabs.og-car-page .ogx-cta,body.og-app-tabs .ogb-bar{transition:bottom .32s cubic-bezier(.2,.8,.2,1),padding-bottom .32s cubic-bezier(.2,.8,.2,1)}
body.og-app-tabs.og-tabs-away.og-car-page .ogx-cta{bottom:0!important;padding-bottom:calc(10px + env(safe-area-inset-bottom))!important}
body.og-app-tabs.og-tabs-away .ogb-bar{bottom:0!important;padding-bottom:calc(12px + env(safe-area-inset-bottom))!important}
}
@media (prefers-reduced-motion:reduce){body.og-app-tabs .og-tabs,body.og-app-tabs.og-car-page .ogx-cta,body.og-app-tabs .ogb-bar{transition:none}}
</style>
<script id="og-tabs-autohide-js">
(function(){
var b=document.body;if(!b.classList.contains('og-app-tabs'))return;
var mq=window.matchMedia('(max-width:767px)'),last=window.scrollY,acc=0;
function locked(){var h=document.documentElement;return h.classList.contains('ogx2-lock')||b.classList.contains('og-fopen');}
function set(away){b.classList.toggle('og-tabs-away',away);}
function run(){var y=Math.max(0,window.scrollY),d=y-last;last=y;
 if(!mq.matches||locked()){set(false);acc=0;return;}
 var end=y+window.innerHeight>=document.documentElement.scrollHeight-40;
 if(y<60||end){set(false);acc=0;return;}
 if((d>0&&acc<0)||(d<0&&acc>0))acc=0;acc+=d;
 if(acc>24)set(true);else if(acc<-24)set(false);}
window.addEventListener('scroll',run,{passive:true});
window.addEventListener('resize',function(){if(!mq.matches)set(false);});
b.addEventListener('focusin',function(e){if(e.target.closest&&e.target.closest('.og-tabs'))set(false);});
})();
</script>
	<?php
}, 130 );
