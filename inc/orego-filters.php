<?php
/**
 * Browse cars filters: [orego_car_filters]
 * Desktop: sticky side panel with collapsible groups. Phones and tablets: a "Filters" bar that opens a bottom sheet.
 * Filters: car type, transmission, fuel, seats, pick-up location, daily price (range slider). Live counts, active chips.
 * Works with the cards from [orego_cars] / the Exotic Cars Listing widget (logic lives in orego_cars_js()).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_filter_facets() {
	$opt   = orego_car_options();
	$types = array();
	$fuel  = array();
	$seats = array();
	$locs  = array();
	$trans = array();
	$brands = array();
	foreach ( get_posts( array( 'post_type' => 'orego_car', 'post_status' => orego_car_statuses(), 'numberposts' => -1 ) ) as $p ) {
		$d  = orego_car_data( $p->ID );
		$tt = get_the_terms( $p->ID, 'car_type' );
		foreach ( ( $tt && ! is_wp_error( $tt ) ) ? $tt : array() as $t ) {
			$types[ $t->slug ] = array( $t->name, ( $types[ $t->slug ][1] ?? 0 ) + 1 );
		}
		if ( isset( $opt['fuel'][ $d['fuel'] ] ) ) {
			$fuel[ $d['fuel'] ] = array( $opt['fuel'][ $d['fuel'] ], ( $fuel[ $d['fuel'] ][1] ?? 0 ) + 1 );
		}
		$trans[ $d['trans'] ] = ( $trans[ $d['trans'] ] ?? 0 ) + 1;
		$bb = get_the_terms( $p->ID, 'car_brand' );
		foreach ( ( $bb && ! is_wp_error( $bb ) ) ? $bb : array() as $t ) {
			$brands[ $t->slug ] = array( $t->name, ( $brands[ $t->slug ][1] ?? 0 ) + 1 );
		}
		if ( (int) $d['seats'] > 0 ) {
			$seats[ (int) $d['seats'] ] = true;
		}
		if ( '' !== trim( (string) $d['location'] ) ) {
			$k          = sanitize_title( $d['location'] );
			$locs[ $k ] = array( $d['location'], ( $locs[ $k ][1] ?? 0 ) + 1 );
		}
	}
	ksort( $seats );
	uasort( $types, fn( $a, $b ) => $b[1] <=> $a[1] );
	uasort( $brands, fn( $a, $b ) => strcasecmp( $a[0], $b[0] ) );
	return compact( 'types', 'fuel', 'seats', 'locs', 'trans', 'brands' );
}

function orego_car_filters_html() {
	$f   = orego_filter_facets();
	$ic  = function ( $n ) {
		return function_exists( 'orego_svg' ) ? orego_svg( $n ) : '';
	};
	$chev = '<svg class="og-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
	$group = function ( $key, $icon, $title, $body, $open = true ) use ( $ic, $chev ) {
		return '<details class="og-fg" data-g="' . esc_attr( $key ) . '"' . ( $open ? ' open' : '' ) . '><summary><span class="og-fg-t">' . $ic( $icon ) . esc_html( $title ) . '</span><span class="og-fg-sel" aria-hidden="true"></span>' . $chev . '</summary><div class="og-fg-b">' . $body . '</div></details>';
	};
	$chip = function ( $attr, $val, $label, $n ) {
		return '<button type="button" class="og-chip" data-' . $attr . '="' . esc_attr( $val ) . '" aria-pressed="false"><span>' . esc_html( $label ) . '</span><i class="og-n">' . (int) $n . '</i></button>';
	};
	wp_enqueue_script( 'orego-cars' );

	$html = '';
	if ( $f['types'] ) {
		$b = '';
		foreach ( $f['types'] as $slug => $t ) {
			$b .= $chip( 'type', $slug, $t[0], $t[1] );
		}
		$html .= $group( 'type', 'car', 'Car type', '<div class="og-chips">' . $b . '</div>' );
	}
	if ( count( $f['brands'] ) > 1 && ( ! function_exists( 'orego_brands_on' ) || orego_brands_on() ) ) {
		$b = '';
		foreach ( $f['brands'] as $slug => $t ) {
			$b .= $chip( 'brand', $slug, $t[0], $t[1] );
		}
		$html .= $group( 'brand', 'car', 'Brand', '<div class="og-chips notranslate" translate="no">' . $b . '</div>' );
	}
	$html .= $group( 'trans', 'gear', 'Transmission', '<div class="og-seg" role="group" aria-label="Transmission"><button type="button" class="og-segb" data-trans="" aria-pressed="true">Any</button><button type="button" class="og-segb" data-trans="auto" aria-pressed="false">Automatic</button><button type="button" class="og-segb" data-trans="manual" aria-pressed="false">Manual</button></div>' );
	if ( count( $f['fuel'] ) > 1 ) {
		$b = '';
		foreach ( $f['fuel'] as $k => $t ) {
			$b .= $chip( 'fuel', $k, $t[0], $t[1] );
		}
		$html .= $group( 'fuel', 'fuel', 'Fuel', '<div class="og-chips">' . $b . '</div>' );
	}
	if ( count( $f['seats'] ) > 1 ) {
		$b = '';
		foreach ( array_keys( $f['seats'] ) as $n ) {
			$b .= '<button type="button" class="og-chip og-chip-sm" data-seats="' . (int) $n . '" aria-pressed="false"><span>' . (int) $n . '+</span></button>';
		}
		$html .= $group( 'seats', 'users', 'Seats', '<div class="og-chips">' . $b . '</div>' );
	}
	$html .= $group( 'price', 'usd', 'Price per day', '<div class="og-range"><div class="og-range-track"><span class="og-range-fill"></span><input type="range" class="og-rmin" min="0" max="100" value="0" step="1" aria-label="Minimum daily price"><input type="range" class="og-rmax" min="0" max="100" value="100" step="1" aria-label="Maximum daily price"></div><div class="og-rate"><label><small>Min</small><span><b class="og-cur-sym">$</b><input type="number" min="0" inputmode="numeric" class="og-min" placeholder="0" aria-label="Minimum daily price"></span></label><i aria-hidden="true">–</i><label><small>Max</small><span><b class="og-cur-sym">$</b><input type="number" min="0" inputmode="numeric" class="og-max" placeholder="Any" aria-label="Maximum daily price"></span></label></div></div>' );
	if ( count( $f['locs'] ) > 1 ) {
		$b = '';
		foreach ( $f['locs'] as $k => $t ) {
			$b .= $chip( 'loc', $k, $t[0], $t[1] );
		}
		$html .= $group( 'loc', 'pin', 'Pick-up location', '<div class="og-chips">' . $b . '</div>' );
	}

	return '<div class="og-fbar"><button type="button" class="og-fbtn" aria-controls="og-side" aria-expanded="false">' . $ic( 'filt' ) . '<span>Filters</span><b class="og-fbadge" hidden>0</b></button><span class="og-fbar-n"><span class="og-count-text"></span></span></div>'
		. '<div class="og-scrim" hidden></div>'
		. '<aside class="og-side" id="og-side" aria-label="Filters">'
		. '<div class="og-side-head"><span class="og-grab" aria-hidden="true"></span><h2>' . $ic( 'filt' ) . 'Filters</h2><button type="button" class="og-reset" hidden>Clear all</button><button type="button" class="og-side-x" aria-label="Close filters"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button></div>'
		. '<div class="og-active" hidden></div>'
		. '<div class="og-side-body">' . $html . '</div>'
		. '<div class="og-side-foot"><button type="button" class="og-reset og-reset-b">Clear</button><button type="button" class="og-show">Show <b class="og-show-n">0</b> cars</button></div>'
		. '</aside>';
}
add_shortcode( 'orego_car_filters', 'orego_car_filters_html' );

add_action( 'wp_head', function () {
	echo '<style id="og-filters">' . orego_filters_css() . '</style>'; // phpcs:ignore
}, 35 );

function orego_filters_css() {
	return <<<'CSS'
.og-side{--fa:var(--hx-primary,#0f5754);--fas:var(--hx-primary-soft,#E8F1F0);--fl:#E8EAEE;--fm:#6B7280;background:#fff;border:1px solid #EDEFF2;border-radius:22px;box-shadow:0 10px 30px rgba(21,23,28,.06);font-family:Cairo,Tahoma,sans-serif;color:#15171C;display:flex;flex-direction:column;overflow:hidden}
.og-side *{box-sizing:border-box}
.og-side-head{display:flex;align-items:center;gap:10px;padding:18px 20px 14px;border-bottom:1px solid #F1F2F5}
.og-side-head h2{display:flex;align-items:center;gap:9px;margin:0;font:800 17px/1.2 Cairo,Tahoma,sans-serif;color:#15171C;flex:1}
.og-side-head h2 svg{width:18px;height:18px;color:var(--fa)}
.og-grab{display:none}
.og-reset{border:0;background:none;color:var(--fa);font:700 13px Cairo,Tahoma,sans-serif;cursor:pointer;padding:6px 4px;border-radius:8px}
.og-reset:hover{text-decoration:underline}
.og-reset[hidden]{display:none}
.og-side-x{display:none;border:0;background:#F3F4F6;color:#15171C;width:36px;height:36px;border-radius:50%;place-items:center;cursor:pointer;padding:0}
.og-active{display:flex;flex-wrap:wrap;gap:6px;padding:12px 20px 0}
.og-active[hidden]{display:none}
.og-pill{display:inline-flex;align-items:center;gap:6px;border:0;background:var(--fas);color:var(--fa);font:600 12.5px/1 Cairo,Tahoma,sans-serif;padding:7px 8px 7px 11px;border-radius:999px;cursor:pointer}
.og-pill svg{width:12px;height:12px}
.og-pill:hover{filter:brightness(.97)}
.og-side-body{padding:4px 20px 8px;overflow:auto}
.og-fg{border-bottom:1px solid #F1F2F5}
.og-fg:last-child{border-bottom:0}
.og-fg>summary{list-style:none;display:flex;align-items:center;gap:8px;padding:16px 0;cursor:pointer;user-select:none}
.og-fg>summary::-webkit-details-marker{display:none}
.og-fg-t{display:flex;align-items:center;gap:9px;font:700 14.5px/1.3 Cairo,Tahoma,sans-serif;color:#15171C;flex:1}
.og-fg-t svg{width:16px;height:16px;color:#9AA1AD}
.og-fg-sel{font:700 11px/1 Cairo,Tahoma,sans-serif;color:#fff;background:var(--fa);border-radius:999px;padding:4px 7px;min-width:20px;text-align:center}
.og-fg-sel:empty{display:none}
.og-chev{color:#9AA1AD;transition:transform .2s}
.og-fg[open] .og-chev{transform:rotate(180deg)}
.og-fg-b{padding:0 0 18px}
.og-chips{display:flex;flex-wrap:wrap;gap:8px}
.og-chip{display:inline-flex;align-items:center;gap:7px;font:600 13.5px/1.2 Cairo,Tahoma,sans-serif;color:#15171C;background:#fff;border:1.5px solid var(--fl);border-radius:12px;padding:8px 10px 8px 12px;cursor:pointer;transition:border-color .15s,background .15s,color .15s,opacity .15s}
.og-chip-sm{padding:8px 14px}
.og-chip .og-n{font-style:normal;font-size:11px;font-weight:700;color:var(--fm);background:#F3F4F6;border-radius:999px;padding:2px 7px;min-width:20px;text-align:center}
.og-chip:hover{border-color:#C9CED6}
.og-chip[aria-pressed=true]{border-color:var(--fa);background:var(--fas);color:var(--fa)}
.og-chip[aria-pressed=true] .og-n{background:var(--fa);color:#fff}
.og-chip.is-zero{opacity:.45}
.og-chip:focus-visible,.og-segb:focus-visible,.og-fbtn:focus-visible,.og-show:focus-visible{outline:2px solid var(--fa);outline-offset:2px}
.og-seg{display:grid;grid-template-columns:repeat(3,1fr);background:#F3F4F6;border-radius:12px;padding:4px;gap:2px}
.og-segb{border:0;background:transparent;font:600 13px/1.2 Cairo,Tahoma,sans-serif;color:var(--fm);padding:9px 4px;border-radius:9px;cursor:pointer;transition:background .15s,color .15s}
.og-segb[aria-pressed=true]{background:#fff;color:#15171C;box-shadow:0 1px 3px rgba(16,24,40,.12)}
.og-range{padding-top:6px}
.og-range-track{position:relative;height:28px;margin:0 9px 12px}
.og-range-track::before{content:"";position:absolute;left:0;right:0;top:12px;height:4px;border-radius:4px;background:#E5E7EB}
.og-range-fill{position:absolute;top:12px;height:4px;border-radius:4px;background:var(--fa);left:0;right:0}
.og-range input[type=range]{position:absolute;left:-9px;right:-9px;width:calc(100% + 18px);top:0;height:28px;margin:0;background:none;pointer-events:none;-webkit-appearance:none;appearance:none}
.og-range input[type=range]::-webkit-slider-thumb{-webkit-appearance:none;pointer-events:auto;width:20px;height:20px;border-radius:50%;background:#fff;border:2px solid var(--fa);box-shadow:0 2px 6px rgba(0,0,0,.18);cursor:grab}
.og-range input[type=range]::-moz-range-thumb{pointer-events:auto;width:18px;height:18px;border-radius:50%;background:#fff;border:2px solid var(--fa);box-shadow:0 2px 6px rgba(0,0,0,.18);cursor:grab}
.og-range input[type=range]::-webkit-slider-runnable-track{background:none}
.og-range input[type=range]::-moz-range-track{background:none}
.og-rate{display:flex;align-items:flex-end;gap:8px}
.og-rate label{flex:1;display:flex;flex-direction:column;gap:4px;margin:0;min-width:0}
.og-rate small{font-size:11px;font-weight:700;color:var(--fm);text-transform:uppercase;letter-spacing:.04em}
.og-rate label>span{display:flex;align-items:center;gap:4px;border:1.5px solid var(--fl);border-radius:12px;padding:0 10px;background:#fff}
.og-rate label>span:focus-within{border-color:var(--fa)}
.og-rate b{font-weight:600;font-size:12px;color:var(--fm)}
.og-rate input{border:0!important;background:transparent!important;box-shadow:none!important;padding:10px 0!important;margin:0;width:100%;min-width:0;font:600 14px Cairo,Tahoma,sans-serif;color:#15171C;outline:none;-moz-appearance:textfield}
.og-rate input::-webkit-outer-spin-button,.og-rate input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.og-rate>i{color:#C9CDD4;font-style:normal;padding-bottom:10px}
.og-side-foot{display:none}
.og-fbar{display:none}
.og-scrim[hidden]{display:none}
html[dir=rtl] .og-pill{padding:7px 11px 7px 8px}
html[dir=rtl] .og-chip{padding:8px 12px 8px 10px}

@media (min-width:1025px){
.og-side{max-height:calc(100vh - var(--ogh-h,72px) - var(--wp-admin--admin-bar--height,0px) - 40px)}
.og-side-wrap{position:sticky;top:calc(var(--ogh-h,72px) + var(--wp-admin--admin-bar--height,0px) + 16px);align-self:flex-start}
}
@media (max-width:1024px){
.og-side-wrap{position:relative!important;top:auto!important;width:100%!important;max-width:100%!important}
.og-fbar{display:flex;align-items:center;gap:12px;position:sticky;top:calc(var(--ogh-h,64px) + var(--wp-admin--admin-bar--height,0px));z-index:20;padding:10px 0;background:linear-gradient(var(--og-page-bg,#F4F5F7) 80%,transparent)}
.og-fbtn{display:inline-flex;align-items:center;gap:8px;border:1.5px solid #E2E5EA;background:#fff;color:#15171C;font:700 14px/1 Cairo,Tahoma,sans-serif;padding:12px 16px;border-radius:999px;cursor:pointer;box-shadow:0 4px 14px rgba(21,23,28,.08)}
.og-fbtn svg{width:16px;height:16px;color:var(--hx-primary,#0f5754)}
.og-fbadge{background:var(--hx-primary,#0f5754);color:#fff;font-size:11px;border-radius:999px;padding:3px 7px;min-width:20px;text-align:center}
.og-fbadge[hidden]{display:none}
.og-fbar-n{font:600 13px Cairo,Tahoma,sans-serif;color:#6B7280}
.og-side{position:fixed;left:0;right:0;bottom:0;z-index:100000;max-height:88vh;border-radius:24px 24px 0 0;border:0;box-shadow:0 -10px 40px rgba(0,0,0,.18);transform:translateY(105%);transition:transform .3s cubic-bezier(.2,.8,.2,1);visibility:hidden}
.og-side.is-open{transform:none;visibility:visible}
.og-grab{display:block;position:absolute;top:8px;left:50%;width:40px;height:4px;margin-left:-20px;border-radius:4px;background:#D9DCE2}
.og-side-head{position:relative;padding:22px 20px 14px}
.og-side-x{display:grid}
.og-side-body{flex:1;padding:4px 20px 12px;-webkit-overflow-scrolling:touch;overscroll-behavior:contain}
.og-side-foot{display:flex;gap:10px;padding:12px 20px calc(12px + env(safe-area-inset-bottom));border-top:1px solid #F1F2F5;background:#fff}
.og-reset-b{flex:0 0 auto;border:1.5px solid #E2E5EA!important;border-radius:14px;padding:13px 18px;color:#15171C}
.og-reset-b:hover{text-decoration:none}
.og-show{flex:1;border:0;border-radius:14px;background:var(--hx-primary,#0f5754);color:#fff;font:700 15px/1 Cairo,Tahoma,sans-serif;padding:15px;cursor:pointer}
.og-side-head .og-reset{display:none}
.og-scrim{position:fixed;inset:0;z-index:99999;background:rgba(11,10,9,.45);opacity:0;transition:opacity .25s}
.og-scrim.is-on{opacity:1}
body.og-fopen{overflow:hidden}
.og-fg>summary{padding:15px 0}
.og-chip{padding:10px 12px 10px 14px}
}
@media (prefers-reduced-motion:reduce){.og-side,.og-scrim,.og-chev{transition:none}}
CSS;
}
