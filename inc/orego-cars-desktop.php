<?php
/**
 * Cars page, tablet and desktop (768px+): layout like the reference design.
 * - Trip search bar on top (pick-up place, dates and times): only cars free on those dates are listed,
 *   and the trip is carried to the car page and the booking form (see orego-search.php).
 * - Desktop (1025px+): filters become a row of dropdown buttons with "Clear all filters" and Sort on the right,
 *   then "N cars available" above the cards. Phones keep their own layout.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_cd_ic( $n ) {
	$p = array(
		'pin'    => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'cal'    => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M8 3v4M16 3v4M3.5 10h17"/>',
		'clock'  => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
		'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
	);
	return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $n ] . '</svg>';
}

/** The trip search bar shown above the filters on the Cars page. */
function orego_tripbar_html() {
	$trip  = function_exists( 'orego_trip' ) ? orego_trip() : array();
	$locs  = function_exists( 'orego_locations' ) ? orego_locations() : array();
	$today = current_time( 'Y-m-d' );
	$from  = $trip['from'] ?? gmdate( 'Y-m-d', strtotime( $today . ' +1 day' ) );
	$to    = $trip['to'] ?? gmdate( 'Y-m-d', strtotime( $from . ' +3 days' ) );
	$pt    = $trip['ptime'] ?? '10:00';
	$rt    = $trip['rtime'] ?? '10:00';
	$times = function ( $sel ) {
		$o = '';
		for ( $m = 7 * 60; $m <= 21 * 60; $m += 30 ) {
			$v  = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
			$o .= '<option value="' . $v . '"' . selected( $sel, $v, false ) . '>' . $v . '</option>';
		}
		return $o;
	};
	$opts = '<option value="">Any location</option>';
	foreach ( $locs as $l ) {
		$opts .= '<option value="' . esc_attr( $l ) . '"' . selected( $trip['pickup'] ?? '', $l, false ) . '>' . esc_html( $l ) . '</option>';
	}
	$keep = '';
	foreach ( array( 'type', 'brand' ) as $k ) {
		if ( ! empty( $_GET[ $k ] ) ) { // phpcs:ignore
			$keep .= '<input type="hidden" name="' . $k . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) ) . '">'; // phpcs:ignore
		}
	}
	$on = ! empty( $trip['from'] );
	ob_start();
	?>
<form class="ogtb<?php echo $on ? ' is-on' : ''; ?>" action="<?php echo esc_url( orego_purl( 'cars' ) ); ?>" method="get" role="search" aria-label="Search cars by dates">
	<?php echo $keep; // phpcs:ignore ?>
	<?php $q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore ?>
	<label class="ogtb-f ogtb-q"><?php echo orego_cd_ic( 'search' ); // phpcs:ignore ?><span><small>Search</small><input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Car name or brand" autocomplete="off" enterkeyhint="search"></span></label>
	<label class="ogtb-f ogtb-loc"><?php echo orego_cd_ic( 'pin' ); // phpcs:ignore ?><span><small>Pick-up location</small><select name="pickup"><?php echo $opts; // phpcs:ignore ?></select></span></label>
	<label class="ogtb-f"><?php echo orego_cd_ic( 'cal' ); // phpcs:ignore ?><span><small>Pick-up date</small><input type="date" name="from" value="<?php echo esc_attr( $from ); ?>" min="<?php echo esc_attr( $today ); ?>" required></span></label>
	<label class="ogtb-f ogtb-t"><?php echo orego_cd_ic( 'clock' ); // phpcs:ignore ?><span><small>Pick-up time</small><select name="ptime" dir="ltr"><?php echo $times( $pt ); // phpcs:ignore ?></select></span></label>
	<label class="ogtb-f"><?php echo orego_cd_ic( 'cal' ); // phpcs:ignore ?><span><small>Return date</small><input type="date" name="to" value="<?php echo esc_attr( $to ); ?>" min="<?php echo esc_attr( $from ); ?>" required></span></label>
	<label class="ogtb-f ogtb-t"><?php echo orego_cd_ic( 'clock' ); // phpcs:ignore ?><span><small>Return time</small><select name="rtime" dir="ltr"><?php echo $times( $rt ); // phpcs:ignore ?></select></span></label>
	<button type="submit" class="ogtb-go" aria-label="Search available cars"><?php echo orego_cd_ic( 'search' ); // phpcs:ignore ?><span>Search</span></button>
</form>
	<?php
	return ob_get_clean();
}

/* Put the bar above the filters, and the "N cars available" heading above the cards (Cars page only). */
add_filter( 'do_shortcode_tag', function ( $out, $tag ) {
	if ( ! orego_is_page( 'cars' ) ) {
		return $out;
	}
	if ( 'orego_car_filters' === $tag ) {
		return orego_tripbar_html() . '<div class="og-drow-slot"></div>' . $out;
	}
	if ( 'orego_cars' === $tag ) {
		return '<h2 class="og-found"><span class="og-count-text"></span></h2>' . $out;
	}
	return $out;
}, 10, 2 );

add_action( 'wp_footer', function () {
	if ( ! ( orego_is_page( 'cars' ) || orego_is_page( 'brands' ) ) ) {
		return;
	}
	?>
<style id="og-cars-desktop">
.ogtb,.og-found{display:none}
.ogtb-q{display:none!important}
.ogtb-q+.ogtb-f::before{display:none}
@media (min-width:768px){
.ogtb{display:flex;align-items:stretch;gap:0;margin:0 0 18px;padding:8px;background:#fff;border:1px solid #ECEEF1;border-radius:20px;box-shadow:0 22px 44px -32px rgba(21,23,28,.4);font-family:Cairo,Tahoma,sans-serif;color:#15171C}
.ogtb *{box-sizing:border-box}
.ogtb-f{flex:1 1 0;min-width:0;display:flex;align-items:center;gap:12px;margin:0;padding:8px 16px;border-radius:14px;cursor:pointer;position:relative;transition:background .15s}
.ogtb-f+.ogtb-f::before{content:"";position:absolute;inset-inline-start:0;top:14px;bottom:14px;width:1px;background:#ECEEF1}
.ogtb-f:hover,.ogtb-f:focus-within{background:#F6F7F8}
.ogtb-f:hover::before,.ogtb-f:focus-within::before,.ogtb-f:hover+.ogtb-f::before,.ogtb-f:focus-within+.ogtb-f::before{opacity:0}
.ogtb-f>svg{flex:none;color:#9AA1AD}
.ogtb-f>span{display:flex;flex-direction:column;gap:2px;min-width:0;flex:1}
.ogtb-f small{font-size:12px;font-weight:600;color:#8A919C;line-height:1.2;white-space:nowrap}
.ogtb-f select,.ogtb-f input{width:100%;min-width:0;height:26px;margin:0;padding:0!important;border:0!important;outline:none;background:transparent!important;box-shadow:none!important;font:700 15px/1.2 Cairo,Tahoma,sans-serif;color:#15171C;cursor:pointer;-webkit-appearance:none;appearance:none;text-overflow:ellipsis}
.ogtb-f input::-webkit-calendar-picker-indicator{opacity:0;position:absolute;inset:0;width:auto;height:auto;cursor:pointer}
.ogtb-loc{flex:1.7 1 0}
.ogtb-t{flex:.85 1 0}
.ogtb-go{flex:none;display:inline-flex;align-items:center;justify-content:center;gap:8px;min-width:64px;padding:0 22px;margin-inline-start:6px;border:0;border-radius:14px;background:#15171C;color:#fff;font:800 15px/1 Cairo,Tahoma,sans-serif;cursor:pointer;box-shadow:0 10px 22px -12px rgba(21,23,28,.6)}
.ogtb-go:hover{background:#2A2D33}
.ogtb-go:focus-visible,.ogtb-f:focus-within{outline:2px solid #15171C;outline-offset:2px}
.ogtb-f:focus-within{outline-offset:-2px}
.og-grid .ogs-note{grid-column:1/-1}
}
@media (min-width:768px) and (max-width:1100px){
.ogtb{flex-wrap:wrap;row-gap:4px}
.ogtb-loc{flex:1 1 100%}
.ogtb-loc+.ogtb-f::before{display:none}
.ogtb-go span{display:none}
}
@media (min-width:1025px){
/* filters: one row of dropdowns above the cards */
body.og-cars-page .elementor > .e-con:has(.og-br-title){display:none!important}
body.og-cars-page .e-con:has(> .og-side-wrap),body.og-cars-page .e-con-inner:has(> .og-side-wrap){flex-direction:column!important;flex-wrap:nowrap!important;gap:0!important}
body.og-cars-page .elementor .elementor-element.og-side-wrap{position:relative!important;top:auto!important;width:100%!important;max-width:100%!important;flex:none!important;z-index:30;margin-bottom:6px}
body.og-cars-page .elementor .elementor-element.og-cars-wrap{width:100%!important;max-width:100%!important;flex:1 1 auto!important}
.og-drow-slot{display:none}
body.og-cars-page .og-side{max-height:none!important;overflow:visible!important;background:none!important;border:0!important;box-shadow:none!important;border-radius:0!important}
body.og-cars-page .og-side-head,body.og-cars-page .og-active,body.og-cars-page .og-side-foot{display:none!important}
body.og-cars-page .og-side-body{display:flex;flex-wrap:wrap;align-items:center;gap:6px;padding:0!important;overflow:visible!important}
body.og-cars-page .og-fg{position:relative;border:0!important}
body.og-cars-page .og-fg>summary{height:46px;padding:0 11px 0 14px!important;border:1.5px solid #E6E8EC;border-radius:12px;background:#fff;gap:10px;transition:border-color .15s,box-shadow .15s}
body.og-cars-page .og-fg>summary:hover{border-color:#C9CED6}
body.og-cars-page .og-fg[open]>summary{border-color:#15171C;box-shadow:0 0 0 3px rgba(21,23,28,.06)}
body.og-cars-page .og-fg-t{font-size:14px;font-weight:600;flex:none}
body.og-cars-page .og-fg-t svg{display:none}
body.og-cars-page .og-fg-sel{background:#15171C}
body.og-cars-page .og-fg-b{position:absolute;top:calc(100% + 8px);inset-inline-start:0;z-index:60;width:max-content;min-width:260px;max-width:380px;padding:16px!important;background:#fff;border:1px solid #ECEEF1;border-radius:16px;box-shadow:0 24px 48px -18px rgba(21,23,28,.32)}
body.og-cars-page .og-fg[data-g=price] .og-fg-b{width:320px}
body.og-cars-page .og-fg[data-g=trans] .og-fg-b{width:320px}
body.og-cars-page .og-chip[aria-pressed=true]{border-color:#15171C;background:#F3F4F6;color:#15171C}
body.og-cars-page .og-chip[aria-pressed=true] .og-n{background:#15171C}
body.og-cars-page .og-clear-d{height:46px;padding:0 14px;border:0;border-radius:12px;background:#F3F4F6;color:#15171C;font:600 14px Cairo,Tahoma,sans-serif;cursor:pointer}
body.og-cars-page .og-clear-d:hover{background:#E9EAED;text-decoration:none}
body.og-cars-page .og-clear-d[hidden]{display:none}
body.og-cars-page .og-sort-d{margin-inline-start:auto}
body.og-cars-page .og-sort-d .og-sort select{height:46px;border-radius:12px;min-width:0!important;width:auto!important;padding:0 36px 0 14px!important;background-position:right 12px center!important;font-size:14px!important}
html[dir=rtl] body.og-cars-page .og-sort-d .og-sort select{padding:0 14px 0 36px!important;background-position:left 12px center!important}
.og-found{display:block;margin:26px 0 20px;font:800 30px/1.2 Cairo,Tahoma,sans-serif;letter-spacing:-.02em;color:#15171C}
}
/* Phones and tablets: search bar, a row of filter pills (each opens the filter sheet at that filter), then "N cars available" */
@media (max-width:1024px){
body.og-cars-page .elementor > .e-con:has(.og-br-title){display:none!important}
body.og-cars-page .og-fbar{display:none!important}
.og-drow-slot{display:flex;align-items:center;gap:8px;overflow-x:auto;overflow-y:hidden;scrollbar-width:none;-webkit-overflow-scrolling:touch;margin:0 0 4px;padding:2px 1px 6px}
.og-drow-slot::-webkit-scrollbar{display:none}
.og-drow-slot:empty{display:none}
.og-mpill{flex:none;display:inline-flex;align-items:center;gap:7px;height:42px;padding:0 13px 0 14px;border:1.5px solid #E6E8EC;border-radius:12px;background:#fff;color:#15171C;font:600 14px/1 Cairo,Tahoma,sans-serif;white-space:nowrap;cursor:pointer;-webkit-tap-highlight-color:transparent}
.og-mpill svg{width:14px;height:14px;color:#9AA1AD}
.og-mpill b{min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:#15171C;color:#fff;font-size:11px;line-height:18px;text-align:center}
.og-mpill b:empty{display:none}
.og-mpill.is-on{border-color:#15171C}
.og-mpill-clear{background:#F3F4F6;border-color:#F3F4F6}
.og-mpill-clear[hidden]{display:none}
.og-drow-slot .og-sort{flex:none;margin:0}
.og-drow-slot .og-sort select{height:42px!important;min-width:0!important;width:auto!important;padding:0 34px 0 14px!important;border:1.5px solid #E6E8EC!important;border-radius:12px!important;box-shadow:none!important;font:600 14px Cairo,Tahoma,sans-serif!important;background-position:right 11px center!important}
html[dir=rtl] .og-drow-slot .og-sort select{padding:0 14px 0 34px!important;background-position:left 11px center!important}
.og-found{display:block;margin:14px 0 14px;font:800 22px/1.2 Cairo,Tahoma,sans-serif;letter-spacing:-.01em;color:#15171C}
}
@media (max-width:767px){
.ogtb{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin:4px 0 12px;padding:8px;background:#fff;border:1px solid #ECEEF1;border-radius:18px;box-shadow:0 18px 36px -28px rgba(21,23,28,.4);font-family:Cairo,Tahoma,sans-serif;color:#15171C}
.ogtb *{box-sizing:border-box}
.ogtb-f{display:flex;align-items:center;gap:10px;min-width:0;margin:0;padding:9px 11px;border-radius:12px;background:#F6F7F8;position:relative}
.ogtb-f>svg{flex:none;width:18px;height:18px;color:#9AA1AD}
.ogtb-f>span{display:flex;flex-direction:column;gap:1px;min-width:0;flex:1}
.ogtb-f small{font-size:11.5px;font-weight:600;color:#8A919C;line-height:1.2;white-space:nowrap}
.ogtb-f select,.ogtb-f input{width:100%;min-width:0;height:22px;margin:0;padding:0!important;border:0!important;outline:none;background:transparent!important;box-shadow:none!important;font:700 14px/1.2 Cairo,Tahoma,sans-serif;color:#15171C;-webkit-appearance:none;appearance:none}
.ogtb-f input::-webkit-calendar-picker-indicator{opacity:0;position:absolute;inset:0;width:auto;height:auto}
.ogtb-loc{grid-column:1/-1}
.ogtb-go{grid-column:1/-1;display:flex;align-items:center;justify-content:center;gap:8px;height:48px;border:0;border-radius:12px;background:#15171C;color:#fff;font:800 15px/1 Cairo,Tahoma,sans-serif}
.ogtb-go svg{width:18px;height:18px}
/* phones: a name search and the Search button (no location, dates or times) */
.ogtb{grid-template-columns:minmax(0,1fr) auto}
.ogtb>.ogtb-f{display:none}
.ogtb>.ogtb-q{display:flex!important}
.ogtb-q input{cursor:text}
.ogtb-q input::placeholder{color:#9AA1AD;font-weight:600}
.ogtb-q input::-webkit-search-cancel-button{-webkit-appearance:none}
.ogtb-go{grid-column:auto;height:auto;min-height:48px;padding:0 18px}
}
/* no "N cars available" heading: the filter row sits right above the cards */
html body .og-found{display:none!important}
@media (min-width:1025px){body.og-cars-page .elementor .elementor-element.og-side-wrap{margin-bottom:0!important}body.og-cars-page .elementor .elementor-element.og-cars-wrap{padding-top:28px!important}}
@media (max-width:1024px){body.og-cars-page .elementor .elementor-element.og-cars-wrap{padding-top:0!important;margin-top:-14px!important}}
/* equal spacing: search bar -> filters = filters -> cards */
@media (min-width:768px){html body .ogtb{margin-bottom:24px!important}}
@media (max-width:767px){html body .ogtb{margin-bottom:16px!important}}
@media (min-width:1025px){html body.og-cars-page .elementor .elementor-element.og-cars-wrap{padding-top:24px!important}}
@media (max-width:1024px){html body.og-cars-page .elementor .elementor-element.og-cars-wrap{margin-top:-18px!important}}
</style>
<script id="og-cars-desktop-js">
(function(){
var mq=window.matchMedia('(min-width:1025px)');
function setup(){
	var side=document.querySelector('.og-side'),body=side&&side.querySelector('.og-side-body');if(!body)return;
	if(mq.matches){
		/* close every group, they open as dropdowns */
		[].forEach.call(body.querySelectorAll('.og-fg'),function(g){g.open=false;g.dataset.touched='1';});
		/* "Clear all filters" button at the end of the row */
		if(!body.querySelector('.og-clear-d')){var c=document.createElement('button');c.type='button';c.className='og-reset og-clear-d';c.textContent='Clear all filters';c.hidden=!side.classList.contains('is-active');body.appendChild(c);}
		/* Sort on the right */
		var srt=document.querySelector('.og-sort');
		if(srt){var slot=body.querySelector('.og-sort-d');if(!slot){slot=document.createElement('div');slot.className='og-sort-d';body.appendChild(slot);}if(srt.parentNode!==slot)slot.appendChild(srt);}
	}
}
/* one dropdown open at a time; click outside or Esc closes */
document.addEventListener('toggle',function(e){var g=e.target;if(!mq.matches||!g.classList||!g.classList.contains('og-fg')||!g.open)return;[].forEach.call(document.querySelectorAll('.og-side .og-fg[open]'),function(o){if(o!==g)o.open=false;});},true);
document.addEventListener('click',function(e){if(!mq.matches||e.target.closest('.og-fg'))return;[].forEach.call(document.querySelectorAll('.og-side .og-fg[open]'),function(o){o.open=false;});});
document.addEventListener('keydown',function(e){if(e.key!=='Escape'||!mq.matches)return;[].forEach.call(document.querySelectorAll('.og-side .og-fg[open]'),function(o){o.open=false;var s=o.querySelector('summary');if(s)s.focus();});});
document.addEventListener('submit',function(e){var f=e.target;if(!f.classList||!f.classList.contains('ogtb')||window.matchMedia('(max-width:767px)').matches)return;var qi=f.querySelector('[name=q]');if(qi&&!qi.value.trim()){qi.disabled=true;setTimeout(function(){qi.disabled=false;},0);}},true);
/* phones: the date boxes are hidden, so do not send them (every car stays listed) */
document.addEventListener('submit',function(e){var f=e.target;if(!f.classList||!f.classList.contains('ogtb')||!window.matchMedia('(max-width:767px)').matches)return;['from','to','ptime','rtime','pickup'].forEach(function(n){var el=f.querySelector('[name='+n+']');if(el)el.disabled=true;});var qi=f.querySelector('[name=q]');if(qi&&!qi.value.trim())qi.disabled=true;setTimeout(function(){if(qi)qi.disabled=false;['from','to','ptime','rtime','pickup'].forEach(function(n){var el=f.querySelector('[name='+n+']');if(el)el.disabled=false;});},0);},true);
/* trip bar: return date never before pick-up date */
document.addEventListener('change',function(e){var f=e.target.closest('.ogtb');if(!f)return;var a=f.querySelector('[name=from]'),b=f.querySelector('[name=to]');if(e.target===a&&a.value){b.min=a.value;if(!b.value||b.value<=a.value){var d=new Date(a.value+'T12:00:00Z');d.setUTCDate(d.getUTCDate()+1);b.value=d.toISOString().slice(0,10);}}});
function run(){setup();setTimeout(setup,400);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run);else run();
if(mq.addEventListener)mq.addEventListener('change',function(){setTimeout(setup,50);});
})();
</script>
<script id="og-cars-mobile-row">
/* Phones and tablets: filter pills + sort in one swipeable row; a pill opens the filter sheet at that filter */
(function(){
var mq=window.matchMedia('(max-width:1024px)'),row=document.querySelector('.og-drow-slot'),side=document.querySelector('.og-side');
if(!row||!side)return;
var chev='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
function openAt(g){var fb=document.querySelector('.og-fbtn');if(fb)fb.click();else side.classList.add('is-open');
	[].forEach.call(side.querySelectorAll('.og-fg'),function(x){x.open=(x===g);x.dataset.touched='1';});
	setTimeout(function(){var b=side.querySelector('.og-side-body');if(b&&g)b.scrollTop=g.offsetTop-b.offsetTop-8;},60);}
function sync(){[].forEach.call(row.querySelectorAll('.og-mpill[data-g]'),function(p){var g=side.querySelector('.og-fg[data-g="'+p.dataset.g+'"]'),sel=g&&g.querySelector('.og-fg-sel'),n=sel?sel.textContent:'';var b=p.querySelector('b');if(b.textContent!==n)b.textContent=n;p.classList.toggle('is-on',!!n);});
	var c=row.querySelector('.og-mpill-clear');if(c)c.hidden=!side.classList.contains('is-active');}
function build(){
	if(!mq.matches)return;
	if(!row.querySelector('.og-mpill')){
		var h='';[].forEach.call(side.querySelectorAll('.og-fg'),function(g){var t=g.querySelector('.og-fg-t');h+='<button type="button" class="og-mpill" data-g="'+g.dataset.g+'"><span>'+(t?t.textContent.trim():g.dataset.g)+'</span><b></b>'+chev+'</button>';});
		h+='<button type="button" class="og-mpill og-mpill-clear" hidden>Clear all</button>';
		row.innerHTML=h;
		row.addEventListener('click',function(e){var p=e.target.closest('.og-mpill');if(!p)return;if(p.classList.contains('og-mpill-clear')){var r=side.querySelector('.og-reset');if(r)r.click();return;}openAt(side.querySelector('.og-fg[data-g="'+p.dataset.g+'"]'));});
		new MutationObserver(sync).observe(side,{subtree:true,childList:true,characterData:true,attributes:true,attributeFilter:['class']});
	}
	var srt=document.querySelector('.og-sort');if(srt&&srt.parentNode!==row)row.appendChild(srt);
	sync();
}
function run(){build();setTimeout(build,450);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run);else run();
if(mq.addEventListener)mq.addEventListener('change',function(){setTimeout(build,80);});
})();
</script>

<script id="og-cars-urlfilters">
/* Cars page: apply filters given in the link (car type and brand links): f_type, f_brand, f_trans, f_fuel, f_seats, f_loc, f_min, f_max, sort */
(function(){
var Q=new URLSearchParams(location.search),keys=['f_type','f_brand','f_trans','f_fuel','f_seats','f_loc','f_min','f_max','sort'];
if(!keys.some(function(k){return Q.get(k);}))return;
function esc(v){return String(v).replace(/[^a-z0-9_.-]/gi,'');}
function go(){
	var side=document.querySelector('.og-side'),grid=document.querySelector('.og-cars');
	if(!side||!grid||!grid.dataset.ready)return false;
	var click=function(sel){var b=side.querySelector(sel);if(b&&b.getAttribute('aria-pressed')!=='true')b.click();};
	if(Q.get('f_type'))click('.og-chip[data-type="'+esc(Q.get('f_type'))+'"]');
	if(Q.get('f_brand'))click('.og-chip[data-brand="'+esc(Q.get('f_brand'))+'"]');
	if(Q.get('f_fuel'))click('.og-chip[data-fuel="'+esc(Q.get('f_fuel'))+'"]');
	if(Q.get('f_loc'))click('.og-chip[data-loc="'+esc(Q.get('f_loc'))+'"]');
	if(Q.get('f_seats'))click('.og-chip[data-seats="'+esc(Q.get('f_seats'))+'"]');
	if(Q.get('f_trans'))click('.og-segb[data-trans="'+esc(Q.get('f_trans'))+'"]');
	var rate=(window.ogCur&&window.ogCur.rate)||1;
	[['f_min','.og-min'],['f_max','.og-max']].forEach(function(p){var v=parseFloat(Q.get(p[0]));var el=side.querySelector(p[1]);if(el&&v>0){el.value=Math.round(v*rate);el.dispatchEvent(new Event('input',{bubbles:true}));}});
	var so=document.getElementById('og-sort');var sv=Q.get('sort');if(so&&sv){var sel=so.tagName==='SELECT'?so:so.querySelector('select');if(sel){sel.value=sv;sel.dispatchEvent(new Event('change',{bubbles:true}));}}
	return true;
}
var n=0;(function tryGo(){if(go()||++n>40)return;setTimeout(tryGo,100);})();
})();
</script>
	<?php
}, 120 );

/* The Cars page used to live at /vehicles/: old links go to its current address. */
add_action( 'template_redirect', function () {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore
	if ( 'vehicles' === $path && 'vehicles' !== get_post_field( 'post_name', 25 ) ) {
		$q = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY ); // phpcs:ignore
		wp_safe_redirect( orego_purl( 'cars' ) . ( $q ? '?' . $q : '' ), 301 );
		exit;
	}
}, 1 );

/* Car type and brand pages: the Cars page with that type / brand already selected (cards, search and filters). */
function orego_term_cars_url( $term ) {
	$key = 'car_type' === $term->taxonomy ? 'f_type' : 'f_brand';
	return add_query_arg( $key, $term->slug, orego_purl( 'cars' ) );
}
add_filter( 'term_link', function ( $url, $term, $tax ) {
	return in_array( $tax, array( 'car_type', 'car_brand' ), true ) ? orego_term_cars_url( $term ) : $url;
}, 10, 3 );
add_action( 'template_redirect', function () {
	if ( is_tax( array( 'car_type', 'car_brand' ) ) ) {
		$t = get_queried_object();
		if ( $t && ! is_wp_error( $t ) ) {
			wp_safe_redirect( orego_term_cars_url( $t ), 302 );
			exit;
		}
	}
}, 2 );
