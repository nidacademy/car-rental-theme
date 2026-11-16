<?php
/**
 * Site-wide additions:
 * - Cars page: "Quick picks" strip (Most popular, Best for families, Best price this week).
 * - Currency picked automatically on the first visit (browser language and time zone).
 * - Rank Math SEO: car pages get Rank Math's titles and descriptions, plus our car details
 *   (description fallback, Product schema, %car_price% / %car_type% / %car_seats% variables).
 * - Personal booking pages are never cached.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Quick picks                                                         */
/* ------------------------------------------------------------------ */

function orego_quick_picks() {
	$cars = array();
	foreach ( get_posts( array( 'post_type' => 'orego_car', 'post_status' => 'publish', 'numberposts' => 60 ) ) as $p ) {
		$d = orego_car_data( $p->ID );
		if ( (float) $d['price'] <= 0 ) {
			continue;
		}
		$cars[ $p->ID ] = array( 'id' => $p->ID, 'price' => (float) $d['price'], 'seats' => (int) $d['seats'], 'rating' => (float) $d['rating'], 'bk' => 0, 'free7' => true );
	}
	if ( count( $cars ) < 3 ) {
		return array();
	}
	foreach ( get_posts( array( 'post_type' => 'orego_booking', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) ) as $b ) {
		$c = (int) get_post_meta( $b, 'og_car', true );
		if ( isset( $cars[ $c ] ) && ! in_array( get_post_meta( $b, 'og_bstatus', true ), array( 'cancelled', 'declined', 'rejected' ), true ) ) {
			$cars[ $c ]['bk']++;
		}
	}
	$week = array();
	for ( $i = 0; $i < 7; $i++ ) {
		$week[] = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +' . $i . ' days' ) );
	}
	foreach ( $cars as $id => $c ) {
		if ( function_exists( 'orego_blocked_nights' ) && array_intersect( $week, orego_blocked_nights( $id ) ) ) {
			$cars[ $id ]['free7'] = false;
		}
	}
	$used = array();
	$pick = function ( $cmp, $filter = null ) use ( $cars, &$used ) {
		$list = array_filter( $cars, fn( $c ) => ! isset( $used[ $c['id'] ] ) && ( ! $filter || $filter( $c ) ) );
		if ( ! $list ) {
			$list = array_filter( $cars, fn( $c ) => ! isset( $used[ $c['id'] ] ) );
		}
		usort( $list, $cmp );
		$c = reset( $list );
		if ( $c ) {
			$used[ $c['id'] ] = 1;
		}
		return $c;
	};
	return array_filter( array(
		array( 'Most popular', $pick( fn( $a, $b ) => array( $b['bk'], $b['rating'], $a['price'] ) <=> array( $a['bk'], $a['rating'], $b['price'] ) ), 'fire' ),
		array( 'Best for families', $pick( fn( $a, $b ) => array( $b['seats'], $a['price'] ) <=> array( $a['seats'], $b['price'] ) ), 'family' ),
		array( 'Best price this week', $pick( fn( $a, $b ) => $a['price'] <=> $b['price'], fn( $c ) => $c['free7'] ), 'tag' ),
	), fn( $x ) => ! empty( $x[1] ) );
}

function orego_quick_picks_html() {
	$picks = orego_quick_picks();
	if ( count( $picks ) < 3 ) {
		return '';
	}
	$ic = array(
		'fire'   => '<path d="M12 21c-3.9 0-6.5-2.6-6.5-6.2 0-3.4 2.4-5.4 3.9-7.6.4 1.6 1.3 2.7 2.4 3.3.2-2.8 1.4-5.6 3.4-7.5.2 2.6 1.3 4.2 2.4 5.7 1 1.4 1.9 2.8 1.9 5.1 0 4-2.9 7.2-7.5 7.2z"/>',
		'family' => '<circle cx="8" cy="7.5" r="2.5"/><circle cx="16.5" cy="9" r="2"/><path d="M3.5 19a4.5 4.5 0 0 1 9 0M12.5 19a4 4 0 0 1 8 0"/>',
		'tag'    => '<path d="M3.5 12.5V4.5a1 1 0 0 1 1-1h8l8 8-9 9z"/><circle cx="8" cy="8" r="1.5"/>',
	);
	$h = '<section class="ogqp" aria-labelledby="ogqp-t"><div class="ogqp-hd"><h2 id="ogqp-t">Quick picks</h2><span>Chosen for you this week</span></div><div class="ogqp-row">';
	foreach ( $picks as $p ) {
		list( $label, $c, $icon ) = $p;
		$id   = $c['id'];
		$link = get_permalink( $id );
		if ( function_exists( 'orego_trip_args' ) ) {
			$link = add_query_arg( orego_trip_args(), $link );
		}
		$d    = orego_car_data( $id );
		$meta = array_filter( array( (int) $d['seats'] ? (int) $d['seats'] . ' seats' : '', 'auto' === $d['trans'] ? 'Automatic' : ( $d['trans'] ? 'Manual' : '' ) ) );
		$h   .= '<a class="ogqp-c is-' . esc_attr( $icon ) . '" href="' . esc_url( $link ) . '">'
			. '<span class="ogqp-img">' . ( has_post_thumbnail( $id ) ? get_the_post_thumbnail( $id, 'medium', array( 'alt' => '', 'loading' => 'lazy' ) ) : '' ) . '</span>'
			. '<span class="ogqp-tx"><span class="ogqp-l"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $ic[ $icon ] . '</svg>' . esc_html( $label ) . '</span>'
			. '<b class="ogqp-n">' . esc_html( get_the_title( $id ) ) . '</b>'
			. '<span class="ogqp-m">' . esc_html( implode( ' · ', $meta ) ) . '</span>'
			. '<span class="ogqp-ft"><span class="ogqp-p"><b class="og-money" dir="ltr" data-usd="' . esc_attr( $c['price'] ) . '">' . esc_html( function_exists( 'orego_money' ) ? orego_money( $c['price'], false ) : '$' . $c['price'] ) . '</b><small>/ day</small></span>'
			. '<span class="ogqp-go" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span></span></span>'
			. '</a>';
	}
	return $h . '</div></section>';
}

add_filter( 'do_shortcode_tag', function ( $out, $tag ) {
	if ( 'orego_cars' !== $tag || is_admin() ) {
		return $out;
	}
	foreach ( array_keys( $_GET ) as $k ) { // phpcs:ignore
		if ( preg_match( '/^(q|f_|from|to|pickup|sort|brand|type|page)/', $k ) ) {
			return $out; // the visitor is searching or filtering: show the results only
		}
	}
	return orego_quick_picks_html() . $out;
}, 30, 2 );

add_action( 'wp_head', function () {
	if ( ! orego_is_page( 'cars' ) ) {
		return;
	}
	echo '<style id="ogqp-css">'
		. '.ogqp{margin:0 0 26px}'
		. '.ogqp-hd{display:flex;align-items:baseline;gap:10px;margin:0 0 12px}'
		. '.ogqp-hd h2{margin:0!important;font-size:18px!important;line-height:1.2!important;font-weight:800!important;color:#15171C!important;letter-spacing:-.01em}'
		. '.ogqp-hd span{font-size:13px;color:#6B7280}'
		. '.ogqp-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}'
		. '.ogqp-c{display:flex;align-items:stretch;gap:14px;padding:10px;border:1px solid #ECEEF1;border-radius:20px;background:#FFFFFF;text-decoration:none!important;color:#15171C;box-shadow:0 1px 2px rgba(21,23,28,.04),0 12px 28px -24px rgba(21,23,28,.5);transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}'
		. '.ogqp-c:hover{transform:translateY(-2px);border-color:#DADDE2;box-shadow:0 1px 2px rgba(21,23,28,.05),0 18px 34px -24px rgba(21,23,28,.55)}'
		. '.ogqp-img{flex:none;width:118px;align-self:stretch;min-height:104px;border-radius:14px;background:radial-gradient(ellipse 80% 55% at 50% 72%,#E9EBEF 0%,#F6F7F9 70%);display:flex;align-items:center;justify-content:center;padding:6px;box-sizing:border-box;overflow:hidden}'
		. '.ogqp-img img{width:100%;height:auto;max-height:100%;object-fit:contain;mix-blend-mode:multiply;transition:transform .25s ease}'
		. '.ogqp-c:hover .ogqp-img img{transform:scale(1.05)}'
		. '.ogqp-tx{flex:1;display:flex;flex-direction:column;justify-content:center;gap:4px;min-width:0;padding:2px 4px 2px 0}'
		. '.ogqp-l{display:inline-flex;align-items:center;gap:5px;align-self:flex-start;margin-bottom:2px;padding:4px 9px 4px 7px;border-radius:999px;background:#F3F4F6;color:#15171C;font-size:11px;font-weight:700;letter-spacing:.01em}'
		. '.ogqp-l svg{flex:none}'
		. '.is-fire .ogqp-l svg{color:#EA580C}.is-family .ogqp-l svg{color:#2563EB}.is-tag .ogqp-l svg{color:#16A34A}'
		. '.ogqp-n{font-size:15px;line-height:1.25;font-weight:800;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}'
		. '.ogqp-m{font-size:12.5px;color:#6B7280}'
		. '.ogqp-ft{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:6px}'
		. '.ogqp-p{display:inline-flex;align-items:baseline;gap:4px}.ogqp-p b{font-size:17px;font-weight:800;color:#15171C;letter-spacing:-.01em}.ogqp-p small{font-size:12.5px;color:#6B7280}'
		. '.ogqp-go{flex:none;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:#15171C;color:#FFFFFF;transition:transform .18s ease}'
		. '.ogqp-c:hover .ogqp-go{transform:translateX(2px)}'
		. 'html[dir=rtl] .ogqp-go svg{transform:scaleX(-1)}'
		. '@media (max-width:1024px){.ogqp-hd{padding:0 2px}.ogqp-row{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;gap:10px;margin:0 -16px;padding:2px 16px 8px;scrollbar-width:none;scroll-padding:0 16px}.ogqp-row::-webkit-scrollbar{display:none}.ogqp-c{flex:0 0 84%;max-width:340px;scroll-snap-align:start;gap:12px}.ogqp-img{width:104px;min-height:96px}}'
		. 'html.og-dark .ogqp-hd h2{color:#F3F4F6!important}html.og-dark .ogqp-hd span,html.og-dark .ogqp-m,html.og-dark .ogqp-p small{color:#9CA3AF}'
		. 'html.og-dark .ogqp-c{background:#161A21;border-color:#262B34;color:#F3F4F6}html.og-dark .ogqp-n,html.og-dark .ogqp-p b{color:#F3F4F6}'
		. 'html.og-dark .ogqp-l{background:#232833;color:#F3F4F6}html.og-dark .ogqp-img{background:radial-gradient(ellipse 80% 55% at 50% 72%,#E3E5E9 0%,#F4F5F7 70%)}html.og-dark .ogqp-go{background:#F3F4F6;color:#15171C}'
		. '</style>' . "\n";
}, 30 );

/* Terms and Privacy pages: easy-to-read text. */
add_action( 'wp_head', function () {
	if ( ! is_page( array( 'terms', 'privacy-policy' ) ) ) {
		return;
	}
	echo '<style id="og-legal-css">'
		. '.og-legal{max-width:760px;margin:0 auto!important;text-align:start}'
		. '.og-legal .elementor-widget-container,.og-legal{color:#374151;font-size:16px;line-height:1.7}'
		. '.og-legal h2{margin:34px 0 10px!important;font-size:20px!important;line-height:1.3!important;font-weight:800!important;color:#15171C!important;letter-spacing:-.01em}'
		. '.og-legal p{margin:0 0 12px}'
		. '.og-legal ul{margin:0 0 12px;padding-inline-start:20px}.og-legal li{margin:6px 0}'
		. '.og-legal a{color:#15171C;text-decoration:underline;text-underline-offset:2px}'
		. '.og-legal .og-legal-upd{display:inline-block;margin:0 0 6px;padding:5px 12px;border-radius:999px;background:#F3F4F6;font-size:13px;color:#6B7280}'
		. '@media (max-width:600px){.og-legal,.og-legal .elementor-widget-container{font-size:15px}.og-legal h2{font-size:18px!important;margin-top:28px!important}}'
		. 'html.og-dark .og-legal,html.og-dark .og-legal .elementor-widget-container{color:#D1D5DB}html.og-dark .og-legal h2,html.og-dark .og-legal a{color:#F3F4F6!important}html.og-dark .og-legal .og-legal-upd{background:#1E232C;color:#9CA3AF}'
		. '</style>' . "\n";
}, 30 );

/* ------------------------------------------------------------------ */
/* Currency on the first visit                                         */
/* ------------------------------------------------------------------ */

add_action( 'wp_head', function () {
	if ( is_admin() || ! function_exists( 'orego_currency_enabled' ) ) {
		return;
	}
	$codes = array();
	foreach ( (array) orego_currency_enabled() as $k => $c ) {
		$codes[] = strtoupper( is_array( $c ) ? (string) ( $c['code'] ?? $k ) : (string) $k );
	}
	$codes = array_values( array_filter( array_unique( $codes ) ) );
	// Before the header script reads the saved currency: pick one once, from the browser.
	echo '<script id="og-cur-auto">(function(){try{if(localStorage.getItem("og_cur"))return;var ok=' . wp_json_encode( $codes ) . ',L=(navigator.languages&&navigator.languages.length?navigator.languages:[navigator.language||"en"]).map(function(x){return String(x).toLowerCase()}),z="";try{z=Intl.DateTimeFormat().resolvedOptions().timeZone||""}catch(e){}'
		. 'var l=L[0]||"en",c="USD";'
		. 'if(z==="Africa/Casablanca"||/-ma$/.test(l)||/^(ar|ary|zgh|ber)/.test(l))c="MAD";'
		. 'else if(/^(ja)/.test(l)||z==="Asia/Tokyo")c="JPY";'
		. 'else if(/^(fr|es|de|it|pt|nl|el|fi|et|lv|lt|sk|sl|ga|mt)/.test(l)&&!/-(ca|ch|us|br|mx|ar|co|cl|pe|ve|dz|tn|sn|ci|ma)$/.test(l)||(/^Europe\//.test(z)&&!/^Europe\/(London|Zurich|Oslo|Stockholm|Copenhagen|Warsaw|Prague|Budapest|Bucharest|Istanbul|Moscow|Kiev|Kyiv)$/.test(z)))c="EUR";'
		// Also note the current language, so the language script keeps this currency (it changes it only when the language changes).
		. 'var g="en";try{var s=JSON.parse(localStorage.getItem("__GT_TRANSLATE_LANGS"));if(s&&s.tgtLang)g=s.tgtLang}catch(e){}'
		. 'if(ok.indexOf(c)>-1){localStorage.setItem("og_cur",c);localStorage.setItem("og_cur_lang",g);localStorage.setItem("og_cur_auto","1")}}catch(e){}})();</script>' . "\n";
}, -10000 );

/* ------------------------------------------------------------------ */
/* Rank Math SEO                                                       */
/* ------------------------------------------------------------------ */

if ( ! function_exists( 'orego_rm_on' ) ) {
	function orego_rm_on() {
		return defined( 'RANK_MATH_VERSION' );
	}
}

/* Car pages: when no description is written in Rank Math, use the car's own summary. */
add_filter( 'rank_math/frontend/description', function ( $desc ) {
	if ( '' === trim( (string) $desc ) && is_singular( 'orego_car' ) && function_exists( 'orego_car_description' ) ) {
		$p = get_queried_object();
		return orego_car_description( $p, orego_car_data( $p->ID ), orego_car_options() );
	}
	return $desc;
} );

/* Car pages: Product schema with the daily price, added to Rank Math's schema. */
add_filter( 'rank_math/json_ld', function ( $data ) {
	if ( ! is_singular( 'orego_car' ) ) {
		return $data;
	}
	$p   = get_queried_object();
	$d   = orego_car_data( $p->ID );
	$img = get_the_post_thumbnail_url( $p->ID, 'large' );
	$ld  = array(
		'@type'       => 'Product',
		'@id'         => get_permalink( $p ) . '#car',
		'name'        => get_the_title( $p ),
		'description' => function_exists( 'orego_car_description' ) ? orego_car_description( $p, $d, orego_car_options() ) : '',
		'url'         => get_permalink( $p ),
		'brand'       => array( '@type' => 'Brand', 'name' => orego_brand() ),
	);
	if ( $img ) {
		$ld['image'] = $img;
	}
	if ( (float) $d['price'] > 0 ) {
		$ld['offers'] = array( '@type' => 'Offer', 'price' => number_format( (float) $d['price'], 2, '.', '' ), 'priceCurrency' => 'USD', 'availability' => 'https://schema.org/InStock', 'url' => get_permalink( $p ) );
	}
	$data['oregoCar'] = $ld;
	return $data;
}, 20 );

/* Car pages with Rank Math: keep loading the main photo first. */
add_action( 'wp_head', function () {
	if ( orego_rm_on() && is_singular( 'orego_car' ) && has_post_thumbnail() ) {
		$img = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( $img ) {
			echo '<link rel="preload" as="image" href="' . esc_url( $img[0] ) . '" fetchpriority="high">' . "\n";
		}
	}
}, 3 );

/* Variables for Rank Math titles and descriptions: %car_price%, %car_type%, %car_seats%. */
add_action( 'rank_math/vars/register_extra_replacements', function () {
	if ( ! function_exists( 'rank_math_register_var_replacement' ) ) {
		return;
	}
	$car = function () {
		$id = get_the_ID();
		return $id && 'orego_car' === get_post_type( $id ) ? orego_car_data( $id ) : null;
	};
	rank_math_register_var_replacement( 'car_price', array( 'name' => 'Car price per day', 'description' => 'Daily price of the car, e.g. $60', 'variable' => 'car_price', 'example' => '$60' ), function () use ( $car ) {
		$d = $car();
		return $d && (float) $d['price'] > 0 ? '$' . rtrim( rtrim( number_format( (float) $d['price'], 2, '.', '' ), '0' ), '.' ) : '';
	} );
	rank_math_register_var_replacement( 'car_type', array( 'name' => 'Car type', 'description' => 'SUV, Sedan, Luxury...', 'variable' => 'car_type', 'example' => 'SUV' ), function () {
		$t = get_the_terms( get_the_ID(), 'car_type' );
		return $t && ! is_wp_error( $t ) ? $t[0]->name : '';
	} );
	rank_math_register_var_replacement( 'car_seats', array( 'name' => 'Car seats', 'description' => 'Number of seats', 'variable' => 'car_seats', 'example' => '5' ), function () use ( $car ) {
		$d = $car();
		return $d ? (string) (int) $d['seats'] : '';
	} );
} );

/* Rank Math sitemap: leave out the private booking pages. */
add_filter( 'rank_math/sitemap/exclude_post_type', function ( $exclude, $type ) {
	return in_array( $type, array( 'orego_booking', 'orego_message' ), true ) ? true : $exclude;
}, 10, 2 );

/* ------------------------------------------------------------------ */
/* Never cache personal pages                                          */
/* ------------------------------------------------------------------ */

add_action( 'template_redirect', function () {
	$personal = isset( $_GET['manage'] ) || isset( $_GET['mb'] ) || is_page( 'my-booking' ); // phpcs:ignore
	if ( $personal ) {
		do_action( 'litespeed_control_set_nocache', 'personal booking page' );
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow' );
	}
}, 0 );
