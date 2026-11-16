<?php
/**
 * Exotic Cars Rental Pro: pricing rules, deposit, policies.
 * - Car: minimum days, security deposit, private costs (box "Pricing & deposit").
 * - Your Cars -> Discounts: long-rental discounts only (weekend and seasonal prices are switched off).
 * - orego_quote(): the single price calculation used by the booking page (AJAX) and the booking handler.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Settings                                                            */
/* ------------------------------------------------------------------ */

function orego_rules() {
	$r = get_option( 'orego_rules' );
	$r = is_array( $r ) ? $r : array();
	return array(
		'discounts'     => isset( $r['discounts'] ) && is_array( $r['discounts'] ) ? $r['discounts'] : array(),
		'weekend'       => isset( $r['weekend'] ) && is_array( $r['weekend'] ) ? array_map( 'intval', $r['weekend'] ) : array( 5, 6 ),
		'cancel_hours'  => isset( $r['cancel_hours'] ) ? max( 0, (int) $r['cancel_hours'] ) : 48,
		'rem_pickup'    => isset( $r['rem_pickup'] ) ? (bool) $r['rem_pickup'] : true,
		'rem_return'    => isset( $r['rem_return'] ) ? (bool) $r['rem_return'] : true,
		'rem_summary'   => isset( $r['rem_summary'] ) ? (bool) $r['rem_summary'] : true,
		'summary_hour'  => isset( $r['summary_hour'] ) ? min( 23, max( 0, (int) $r['summary_hour'] ) ) : 8,
		'cost_day'      => isset( $r['cost_day'] ) ? max( 0, (float) $r['cost_day'] ) : 0,
		'cost_trip'     => isset( $r['cost_trip'] ) ? max( 0, (float) $r['cost_trip'] ) : 0,
		'bring'         => isset( $r['bring'] ) ? (string) $r['bring'] : "Your driving licence\nYour ID or passport\nThe card used for the security deposit",
	);
}

/* ------------------------------------------------------------------ */
/* Car pricing data                                                    */
/* ------------------------------------------------------------------ */

function orego_car_pricing( $car_id ) {
	$m       = fn( $k ) => get_post_meta( $car_id, $k, true );
	$seasons = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $m( 'og_seasons' ) ) as $line ) {
		if ( preg_match( '/(\d{4}-\d{2}-\d{2})\D+(\d{4}-\d{2}-\d{2})\D+?([\d.]+)\s*$/', trim( $line ), $x ) && $x[2] >= $x[1] && (float) $x[3] > 0 ) {
			$seasons[] = array( $x[1], $x[2], (float) $x[3] );
		}
	}
	return array(
		'base'     => (float) $m( 'og_price' ),
		'weekend'  => 0, // weekend prices switched off: only long-rental discounts change the price
		'seasons'  => array(), // seasonal prices switched off
		'min_days' => max( 1, (int) $m( 'og_min_days' ) ),
		'deposit'  => '' !== $m( 'og_deposit' ) ? max( 0, (float) $m( 'og_deposit' ) ) : 0,
		'cost_day'  => '' !== $m( 'og_cost_day' ) ? max( 0, (float) $m( 'og_cost_day' ) ) : null,
		'cost_trip' => '' !== $m( 'og_cost_trip' ) ? max( 0, (float) $m( 'og_cost_trip' ) ) : null,
	);
}

/** Price of one night (the night that starts on $ymd). */
function orego_night_price( $pr, $ymd, $weekend_days ) {
	foreach ( $pr['seasons'] as $s ) {
		if ( $ymd >= $s[0] && $ymd <= $s[1] ) {
			return array( $s[2], 'season' );
		}
	}
	if ( $pr['weekend'] > 0 && in_array( (int) gmdate( 'w', strtotime( $ymd . ' 12:00:00 UTC' ) ), $weekend_days, true ) ) {
		return array( $pr['weekend'], 'weekend' );
	}
	return array( $pr['base'], 'base' );
}

/**
 * Full price of a rental. Returns array or WP_Error.
 * ('extras' / 'extras_total' stay in the result, always empty: extras were removed.)
 */
function orego_quote( $car_id, $start, $end ) {
	$re = '/^\d{4}-\d{2}-\d{2}$/';
	if ( ! preg_match( $re, (string) $start ) || ! preg_match( $re, (string) $end ) || $end <= $start ) {
		return new WP_Error( 'dates', 'Choose a valid pick-up and return date.' );
	}
	$pr    = orego_car_pricing( $car_id );
	$rules = orego_rules();
	if ( $pr['base'] <= 0 ) {
		return new WP_Error( 'price', 'This car has no price yet. Contact us to book it.' );
	}
	$days = (int) round( ( strtotime( $end . ' 00:00:00 UTC' ) - strtotime( $start . ' 00:00:00 UTC' ) ) / DAY_IN_SECONDS );
	if ( $days < $pr['min_days'] ) {
		return new WP_Error( 'min', sprintf( 'This car can be booked for %d days minimum.', $pr['min_days'] ) );
	}
	$sub    = 0;
	$groups = array();
	for ( $i = 0, $t = strtotime( $start . ' 00:00:00 UTC' ); $i < $days; $i++, $t += DAY_IN_SECONDS ) {
		list( $p, $kind ) = orego_night_price( $pr, gmdate( 'Y-m-d', $t ), $rules['weekend'] );
		$sub             += $p;
		$k                = $kind . '|' . $p;
		$groups[ $k ]     = ( $groups[ $k ] ?? 0 ) + 1;
	}
	$rows = array();
	$name = array( 'base' => 'day', 'weekend' => 'weekend day', 'season' => 'season day' );
	foreach ( $groups as $k => $n ) {
		list( $kind, $p ) = explode( '|', $k );
		$rows[]           = array( 'label' => $n . ' ' . $name[ $kind ] . ( 1 === $n ? '' : 's' ) . ' × ' . orego_bk_money( (float) $p ), 'amount' => round( $n * (float) $p, 2 ), 'kind' => 'rent' );
	}
	// Long-rental discount: the best tier reached.
	$pct = 0;
	foreach ( $rules['discounts'] as $d ) {
		$only = array_map( 'intval', (array) ( $d['cars'] ?? array() ) );
		if ( $only && ! in_array( (int) $car_id, $only, true ) ) {
			continue; // this discount is limited to other cars
		}
		if ( (int) ( $d['days'] ?? 0 ) > 0 && $days >= (int) $d['days'] && (float) ( $d['pct'] ?? 0 ) > $pct ) {
			$pct = min( 90, (float) $d['pct'] );
		}
	}
	$disc = $pct > 0 ? round( $sub * $pct / 100, 2 ) : 0;
	if ( $disc > 0 ) {
		$rows[] = array( 'label' => 'Long-rental discount (' . rtrim( rtrim( number_format( $pct, 1 ), '0' ), '.' ) . '%)', 'amount' => -$disc, 'kind' => 'discount' );
	}
	$total = round( $sub - $disc, 2 );
	return array(
		'days'         => $days,
		'base'         => $pr['base'],
		'subtotal'     => round( $sub, 2 ),
		'discount'     => $disc,
		'discount_pct' => $pct,
		'extras'       => array(),
		'extras_total' => 0,
		'total'        => $total,
		'avg'          => round( $sub / $days, 2 ),
		'deposit'      => $pr['deposit'],
		'rows'         => $rows,
	);
}

/* Booking page asks the server for the price whenever the dates change. */
add_action( 'wp_ajax_orego_quote', 'orego_ajax_quote' );
add_action( 'wp_ajax_nopriv_orego_quote', 'orego_ajax_quote' );
function orego_ajax_quote() {
	if ( ! check_ajax_referer( 'orego_book', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'This page has expired. Refresh the page and try again.' ), 400 );
	}
	$car = absint( $_POST['car'] ?? 0 );
	if ( 'orego_car' !== get_post_type( $car ) ) {
		wp_send_json_error( array( 'message' => 'This car is not available for booking.' ), 400 );
	}
	$q = orego_quote( $car, sanitize_text_field( wp_unslash( $_POST['start'] ?? '' ) ), sanitize_text_field( wp_unslash( $_POST['end'] ?? '' ) ) );
	if ( is_wp_error( $q ) ) {
		wp_send_json_error( array( 'message' => $q->get_error_message() ), 400 );
	}
	wp_send_json_success( $q );
}

/* ------------------------------------------------------------------ */
/* Booking: store the price breakdown, token, deposit                  */
/* ------------------------------------------------------------------ */

add_action( 'orego_booking_created', function ( $bid, $quote ) {
	if ( is_array( $quote ) ) {
		update_post_meta( $bid, 'og_quote', wp_slash( wp_json_encode( $quote ) ) );
		update_post_meta( $bid, 'og_deposit', $quote['deposit'] );
	}
	orego_bk_token( $bid );
}, 10, 2 );

function orego_bk_quote( $id ) {
	$q = json_decode( (string) get_post_meta( $id, 'og_quote', true ), true );
	if ( is_array( $q ) && isset( $q['rows'] ) ) {
		return $q;
	}
	$i = orego_bk_info( $id ); // older bookings: simple days x price
	return array(
		'days'         => $i['days'],
		'base'         => $i['pday'],
		'subtotal'     => $i['total'],
		'discount'     => 0,
		'extras'       => array(),
		'extras_total' => 0,
		'total'        => $i['total'],
		'deposit'      => (float) get_post_meta( $id, 'og_deposit', true ),
		'rows'         => array( array( 'label' => $i['days'] . ' ' . ( 1 === $i['days'] ? 'day' : 'days' ) . ' × ' . orego_bk_money( $i['pday'], $id ), 'amount' => $i['total'], 'kind' => 'rent' ) ),
	);
}

/** Private token for the customer's "Manage booking" link (created on first use). */
function orego_bk_token( $id ) {
	$t = (string) get_post_meta( $id, 'og_token', true );
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $t ) ) {
		$t = bin2hex( random_bytes( 16 ) );
		update_post_meta( $id, 'og_token', $t );
	}
	return $t;
}

function orego_manage_url( $id ) {
	$page = function_exists( 'orego_booking_page_id' ) ? orego_booking_page_id() : 0;
	$base = $page ? get_permalink( $page ) : home_url( '/' );
	return add_query_arg( array( 'manage' => (int) $id, 't' => orego_bk_token( $id ) ), $base );
}

/* Emails: price breakdown + deposit. */
add_filter( 'orego_bk_lines', function ( $lines, $id ) {
	$q = orego_bk_quote( $id );
	if ( $q['discount'] > 0 || ! empty( $q['extras'] ) || count( $q['rows'] ) > 1 ) {
		$extra = array();
		foreach ( $q['rows'] as $r ) {
			$extra[] = '  ' . $r['label'] . ': ' . ( $r['amount'] < 0 ? '-' : '' ) . orego_bk_money( abs( $r['amount'] ), $id );
		}
		array_splice( $lines, count( $lines ) - 1, 0, $extra );
	}
	if ( $q['deposit'] > 0 ) {
		$lines[] = 'Refundable security deposit at pick-up: ' . orego_bk_money( $q['deposit'], $id );
	}
	return $lines;
}, 10, 2 );

/* ------------------------------------------------------------------ */
/* Car state for the dashboard badge                                   */
/* ------------------------------------------------------------------ */

function orego_car_state_label( $car_id ) {
	$today = current_time( 'Y-m-d' );
	if ( function_exists( 'orego_car_units' ) && orego_car_units( $car_id ) > 1 ) {
		$free = orego_car_free_today( $car_id );
		$all  = orego_car_units( $car_id );
		if ( $free > 0 ) {
			return $free === $all ? 'Available' : $free . ' of ' . $all . ' available';
		}
	}
	foreach ( orego_bk_ranges( $car_id, 0, array( 'confirmed', 'onroad' ) ) as $r ) {
		if ( $r[0] <= $today && $r[1] > $today ) {
			return 'On rental';
		}
	}
	foreach ( orego_bk_ranges( $car_id, 0, array( 'pending' ) ) as $r ) {
		if ( $r[0] <= $today && $r[1] > $today ) {
			return 'Reserved';
		}
	}
	if ( in_array( $today, orego_blocked_nights( $car_id ), true ) ) {
		return 'Unavailable';
	}
	return 'Available';
}

/* ------------------------------------------------------------------ */
/* Car box: Pricing & deposit                                          */
/* ------------------------------------------------------------------ */

add_action( 'add_meta_boxes_orego_car', function () {
	add_meta_box( 'orego_car_pricing', 'Pricing & deposit', 'orego_car_pricing_box', 'orego_car', 'normal', 'high' );
} );

function orego_car_pricing_box( $post ) {
	wp_nonce_field( 'orego_car_pricing', 'orego_car_pricing_nonce' );
	$m = fn( $k ) => get_post_meta( $post->ID, $k, true );
	$s = 'font-weight:600;display:block;margin-bottom:6px';
	echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px">';

	echo '<p><label for="og_min_days" style="' . $s . '">Minimum rental (days)</label><input type="number" min="1" max="60" name="og_min_days" id="og_min_days" value="' . esc_attr( $m( 'og_min_days' ) ) . '" style="width:100%" placeholder="1"></p>';
	echo '<p><label for="og_deposit" style="' . $s . '">Security deposit ($)</label><input type="number" step="1" min="0" name="og_deposit" id="og_deposit" value="' . esc_attr( $m( 'og_deposit' ) ) . '" style="width:100%" placeholder="0 = no deposit"></p>';
	echo '</div>';

	echo '<p style="color:#646970;margin:0">Long-rental discounts are set in Your Cars → Discounts. The deposit is refundable and collected at pick-up.</p>';
}

add_action( 'save_post_orego_car', function ( $post_id ) {
	if ( ! isset( $_POST['orego_car_pricing_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['orego_car_pricing_nonce'] ) ), 'orego_car_pricing' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$num = function ( $k ) {
		$v = isset( $_POST[ $k ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) ) : '';
		return '' === $v ? '' : (string) max( 0, (float) $v );
	};
	if ( isset( $_POST['og_price_weekend'] ) ) { update_post_meta( $post_id, 'og_price_weekend', $num( 'og_price_weekend' ) ); }
	update_post_meta( $post_id, 'og_deposit', $num( 'og_deposit' ) );
	if ( isset( $_POST['og_cost_day'] ) ) { update_post_meta( $post_id, 'og_cost_day', $num( 'og_cost_day' ) ); }
	if ( isset( $_POST['og_cost_trip'] ) ) { update_post_meta( $post_id, 'og_cost_trip', $num( 'og_cost_trip' ) ); }
	$md = $num( 'og_min_days' );
	update_post_meta( $post_id, 'og_min_days', '' === $md ? '' : (string) min( 60, max( 1, (int) $md ) ) );
	$lines = array();
	foreach ( preg_split( '/\r\n|\r|\n/', sanitize_textarea_field( wp_unslash( $_POST['og_seasons'] ?? '' ) ) ) as $l ) {
		if ( preg_match( '/(\d{4}-\d{2}-\d{2})\D+(\d{4}-\d{2}-\d{2})\D+?([\d.]+)\s*$/', trim( $l ), $x ) ) {
			$lines[] = $x[1] . ' to ' . $x[2] . ' = ' . (float) $x[3];
		}
	}
	if ( isset( $_POST['og_seasons'] ) ) { update_post_meta( $post_id, 'og_seasons', implode( "\n", $lines ) ); }
}, 20 );

/* ------------------------------------------------------------------ */
/* Your Cars -> Discounts                                              */
/* ------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=orego_car', 'Discounts', 'Discounts', 'manage_options', 'orego-rules', 'orego_rules_page' );
}, 30 );

add_action( 'admin_post_orego_rules_save', function () {
	check_admin_referer( 'orego_rules_save' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	$p    = wp_unslash( $_POST );
	$disc = array();
	foreach ( (array) ( $p['disc_days'] ?? array() ) as $i => $d ) {
		$d   = (int) $d;
		$pct = (float) ( $p['disc_pct'][ $i ] ?? 0 );
		if ( $d > 1 && $pct > 0 ) {
			$ids    = array_map( 'intval', explode( ',', (string) ( $p['disc_cars'][ $i ] ?? '' ) ) );
			$ids    = array_values( array_unique( array_filter( $ids, fn( $x ) => $x > 0 && 'orego_car' === get_post_type( $x ) ) ) );
			$disc[] = array( 'days' => $d, 'pct' => min( 90, $pct ), 'cars' => $ids );
		}
	}
	usort( $disc, fn( $a, $b ) => $a['days'] <=> $b['days'] );
	$cur = orego_rules();
	update_option( 'orego_rules', array(
		'discounts'    => $disc,
		'weekend'      => $cur['weekend'], // not used any more
		// Not on this page any more: keep the current values.
		'cancel_hours' => $cur['cancel_hours'],
		'rem_pickup'   => $cur['rem_pickup'],
		'rem_return'   => $cur['rem_return'],
		'rem_summary'  => $cur['rem_summary'],
		'summary_hour' => $cur['summary_hour'],
		'bring'        => $cur['bring'],
		'cost_day'     => $cur['cost_day'],
		'cost_trip'    => $cur['cost_trip'],
	), false );
	wp_safe_redirect( admin_url( 'edit.php?post_type=orego_car&page=orego-rules&saved=1' ) );
	exit;
} );

function orego_rules_page() {
	$r    = orego_rules();
	$disc = $r['discounts'];
	// Real bookings per car (cancelled and rejected ones are left out).
	$bk = array();
	foreach ( get_posts( array( 'post_type' => 'orego_booking', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $bid ) {
		if ( in_array( get_post_meta( $bid, 'og_bstatus', true ), array( 'cancelled', 'rejected', 'declined' ), true ) ) {
			continue;
		}
		$bk[ (int) get_post_meta( $bid, 'og_car', true ) ][] = max( 1, (int) get_post_meta( $bid, 'og_days', true ) );
	}
	$cars = array();
	foreach ( get_posts( array( 'post_type' => 'orego_car', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $c ) {
		$p = orego_car_pricing( $c->ID );
		if ( $p['base'] > 0 ) {
			$cars[] = array( 'id' => $c->ID, 'name' => $c->post_title, 'base' => $p['base'], 'min' => $p['min_days'], 'img' => (string) get_the_post_thumbnail_url( $c->ID, 'thumbnail' ), 'bk' => $bk[ $c->ID ] ?? array() );
		}
	}
	if ( function_exists( 'orego_ui_head' ) ) {
		orego_ui_head( 'Discounts', 'Reward longer rentals. Choose the rental length, the discount and the cars it applies to.' );
	} else {
		echo '<div class="wrap"><h1>Discounts</h1>';
	}
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Discounts saved.</p></div>';
	}
	$chips = function ( $sel ) use ( $cars ) {
		$h = '<button type="button" class="ogc-chip is-all' . ( $sel ? '' : ' is-on' ) . '" data-id="all"><span class="ogc-ck"></span>All cars</button>';
		foreach ( $cars as $c ) {
			$on  = in_array( (int) $c['id'], $sel, true );
			$img = $c['img'] ? '<img src="' . esc_url( $c['img'] ) . '" alt="">' : '<span class="ogc-noimg"></span>';
			$h  .= '<button type="button" class="ogc-chip' . ( $on ? ' is-on' : '' ) . '" data-id="' . (int) $c['id'] . '">' . $img . esc_html( $c['name'] ) . '<span class="ogc-ck"></span></button>';
		}
		return $h;
	};
	$rule = function ( $d ) use ( $chips ) {
		$sel = array_map( 'intval', (array) ( $d['cars'] ?? array() ) );
		return '<article class="ogc-rule">'
			. '<div class="ogc-body"><div class="ogc-fields">'
			. '<label class="ogc-f"><span class="ogc-fl">Minimum rental</span><span class="ogc-in"><input type="number" min="2" max="60" name="disc_days[]" value="' . esc_attr( $d['days'] ) . '" placeholder="7"><i>days</i></span></label>'
			. '<label class="ogc-f"><span class="ogc-fl">Discount</span><span class="ogc-in"><input type="number" min="0" max="90" step="0.5" name="disc_pct[]" value="' . esc_attr( $d['pct'] ) . '" placeholder="10"><i>% off</i></span></label>'
			. '<div class="ogc-f is-wide"><span class="ogc-fl">Applies to</span><input type="hidden" name="disc_cars[]" value="' . esc_attr( implode( ',', $sel ) ) . '">'
			. '<div class="ogc-dd"><button type="button" class="ogc-dd-btn" aria-haspopup="listbox" aria-expanded="false" aria-label="Applies to"><span class="ogc-dd-val">All cars</span><span class="ogc-dd-ar" aria-hidden="true"></span></button><div class="ogc-dd-menu" role="listbox" aria-multiselectable="true" hidden>' . $chips( $sel ) . '</div></div></div>'
			. '</div><div class="ogc-ex"></div></div>'
			. '<button type="button" class="ogc-del" aria-label="Delete discount" title="Delete discount">&times;</button>'
			. '</article>';
	};
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ogc" id="ogc-form">
		<input type="hidden" name="action" value="orego_rules_save"><?php wp_nonce_field( 'orego_rules_save' ); ?>
		<div class="ogc-top"><div><h2>Your discounts</h2><p>When several discounts match a rental, the customer gets the biggest one.</p></div><button type="button" class="ogdx-btn ogc-new" id="ogc-add">+ New discount</button></div>
		<div class="ogc-list" id="ogc-list"><?php foreach ( $disc as $d ) { echo $rule( $d ); } // phpcs:ignore ?></div>
		<div class="ogc-empty" id="ogc-empty"<?php echo $disc ? ' hidden' : ''; ?>><b>%</b><h3>No discounts yet</h3><p>Every rental is charged the full daily price.</p></div>

		<section class="ogc-card">
			<div class="ogc-top is-sm"><div><h2>By car</h2><p>The discounts each car gets, with its real bookings.</p></div><div class="ogc-kpis" id="ogc-kpis"></div></div>
			<div class="ogc-cars" id="ogc-cars"></div>
		</section>

		<div class="ogc-bar"><span id="ogc-dirty" class="ogc-help">All changes saved</span><button type="submit" class="ogdx-btn">Save discounts</button></div>
		<template id="ogc-tpl"><?php echo $rule( array( 'days' => '', 'pct' => '', 'cars' => array() ) ); // phpcs:ignore ?></template>
		<script type="application/json" id="ogc-data"><?php echo wp_json_encode( $cars ); ?></script>
	</form>

	<script>
	(function(){
	var $=function(id){return document.getElementById(id)},form=$('ogc-form');if(!form)return;
	var cars=JSON.parse($('ogc-data').textContent||'[]'),list=$('ogc-list'),dirty=$('ogc-dirty');
	function n(v){v=parseFloat(String(v==null?'':v).replace(',','.'));return isNaN(v)?0:v}
	function money(v){var a=Math.abs(v),s=a.toLocaleString('en-US',{minimumFractionDigits:a%1?2:0,maximumFractionDigits:2});return (v<0?'-$':'$')+s}
	function el(t,c,txt){var e=document.createElement(t);if(c)e.className=c;if(txt!=null)e.textContent=txt;return e}
	function sel(rule){var v=rule.querySelector('[name="disc_cars[]"]').value;return v?v.split(',').map(Number):[]}
	function rules(){var R=[];[].forEach.call(list.querySelectorAll('.ogc-rule'),function(r){var d=parseInt(r.querySelector('[name="disc_days[]"]').value,10),p=n(r.querySelector('[name="disc_pct[]"]').value);R.push({el:r,days:d>1?d:0,pct:p>0?Math.min(90,p):0,cars:sel(r)})});return R}
	function applies(r,id){return r.days&&r.pct&&(!r.cars.length||r.cars.indexOf(id)>-1)}
	function render(){
	 var R=rules();$('ogc-empty').hidden=R.length>0;
	 R.forEach(function(r){
	  
	  r.el.classList.toggle('is-off',!(r.days&&r.pct));
	  [].forEach.call(r.el.querySelectorAll('.ogc-chip'),function(c){var id=c.getAttribute('data-id');c.classList.toggle('is-on',id==='all'?!r.cars.length:r.cars.indexOf(+id)>-1)});
	  var vb=r.el.querySelector('.ogc-dd-val'),nms=cars.filter(function(x){return r.cars.indexOf(x.id)>-1}).map(function(x){return x.name});vb.textContent=!nms.length?'All cars':(nms.length<=2?nms.join(', '):nms.slice(0,2).join(', ')+' +'+(nms.length-2)+' more');
	  var ex=r.el.querySelector('.ogc-ex'),ok=!!(r.days&&r.pct);ex.innerHTML='';ex.classList.toggle('is-empty',!ok);
	  if(!ok){ex.appendChild(el('span','','Enter the minimum days and the discount to turn this discount on.'));return}
	  var c=null;cars.some(function(x){if(applies(r,x.id)){c=x;return true}});
	  ex.appendChild(el('span','ogc-exc',r.cars.length?r.cars.length+(r.cars.length===1?' car':' cars'):'All '+cars.length+' cars'));
	  if(c){var full=c.base*r.days,pay=Math.round(full*(100-r.pct))/100,who=el('span','ogc-exw'),pr=el('span','ogc-exp');
	   if(c.img){var im=el('img');im.src=c.img;im.alt='';who.appendChild(im)}who.appendChild(el('span','','Example: '+c.name+' · '+r.days+' days'));ex.appendChild(who);
	   pr.appendChild(el('s','',money(full)));pr.appendChild(el('span','ogc-exa','→'));pr.appendChild(el('b','',money(pay)));pr.appendChild(el('em','','Save '+money(Math.round((full-pay)*100)/100)));ex.appendChild(pr)}});
	 var K={cars:0,n:0,disc:0,profit:0,days:0};
	 function stat(box,label,val,cls,bar){var d=el('div','ogc-s'+(cls?' '+cls:''));d.appendChild(el('small','',label));d.appendChild(el('b','',val));if(bar!=null){var t=el('span','ogc-sb'),i=el('i');i.style.width=Math.round(bar*100)+'%';t.appendChild(i);d.appendChild(t)}box.appendChild(d)}
	 var box=$('ogc-cars');box.innerHTML='';
	 cars.forEach(function(c){var row=el('div','ogc-car'),m=R.filter(function(r){return applies(r,c.id)}).sort(function(a,b){return a.days-b.days});
	  var nm=el('div','ogc-cn');if(c.img){var im=el('img');im.src=c.img;im.alt='';nm.appendChild(im)}else nm.appendChild(el('span','ogc-noimg'));
	  var t=el('div');t.appendChild(el('b','',c.name));t.appendChild(el('small','',money(c.base)+' / day'));nm.appendChild(t);row.appendChild(nm);
	  var tags=el('div','ogc-tags');
	  if(!m.length)tags.appendChild(el('span','ogc-tag is-none','No discount'));
	  m.forEach(function(r){var tg=el('span','ogc-tag');tg.appendChild(el('b','','-'+r.pct+'%'));tg.appendChild(document.createTextNode(' from '+r.days+' days'));tags.appendChild(tg)});
	  row.appendChild(tags);
	  var b=c.bk||[],bn=b.length,tot=b.reduce(function(a,x){return a+x},0),avg=bn?Math.round(tot/bn*10)/10:0,minD=0,maxP=0;
	  m.forEach(function(r){if(!minD||r.days<minD)minD=r.days;if(r.pct>maxP)maxP=r.pct});
	  var dsum=0,psum=0;b.forEach(function(x){var p=0;m.forEach(function(r){if(x>=r.days&&r.pct>p)p=r.pct});var full=c.base*x,dd=Math.round(full*p)/100;dsum+=dd;psum+=full-dd});
	  dsum=Math.round(dsum*100)/100;psum=Math.round(psum*100)/100;K.n+=bn;K.disc+=dsum;K.profit+=psum;K.days+=tot;if(m.length)K.cars++;
	  var st=el('div','ogc-st');
	  stat(st,'Bookings',String(bn));
	  stat(st,'Avg. rental',bn?avg+(avg===1?' day':' days'):'–');
	  stat(st,'Discount',dsum?'-'+money(dsum):'–',dsum?'is-disc':'');
	  stat(st,'Profit',bn?money(psum):'–',bn?'is-good':'');
	  row.appendChild(st);box.appendChild(row)});
	 var kp=$('ogc-kpis');kp.innerHTML='';
	 [['Cars with a discount',K.cars+' / '+cars.length],['Total discount',K.disc?'-'+money(Math.round(K.disc*100)/100):'–'],['Total profit',K.n?money(Math.round(K.profit*100)/100):'–'],['Average rental',K.n?Math.round(K.days/K.n*10)/10+' days':'–']].forEach(function(x){var d=el('div','ogc-k');d.appendChild(el('small','',x[0]));d.appendChild(el('b','',x[1]));kp.appendChild(d)})}
	function changed(){dirty.textContent='Unsaved changes';dirty.classList.add('is-dirty');render()}
	$('ogc-add').addEventListener('click',function(){list.appendChild($('ogc-tpl').content.cloneNode(true));var r=list.lastElementChild;r.scrollIntoView({behavior:'smooth',block:'center'});r.querySelector('input').focus();changed()});
	form.addEventListener('click',function(e){
	 var del=e.target.closest('.ogc-del');if(del){del.closest('.ogc-rule').remove();changed();return}
	 var dbtn=e.target.closest('.ogc-dd-btn');if(dbtn){var dd=dbtn.parentNode,open=dd.classList.contains('is-open');closeAll();if(!open){dd.classList.add('is-open');dd.querySelector('.ogc-dd-menu').hidden=false;dbtn.setAttribute('aria-expanded','true')}return}
	 var chip=e.target.closest('.ogc-chip');if(!chip)return;
	 var rule=chip.closest('.ogc-rule'),h=rule.querySelector('[name="disc_cars[]"]'),s=sel(rule),id=chip.getAttribute('data-id');
	 if(id==='all'){s=[]}else{id=+id;var i=s.indexOf(id);if(i>-1)s.splice(i,1);else s.push(id);if(s.length===cars.length)s=[]}
	 h.value=s.join(',');changed()});
	function closeAll(){[].forEach.call(form.querySelectorAll('.ogc-dd.is-open'),function(d){d.classList.remove('is-open');d.querySelector('.ogc-dd-menu').hidden=true;d.querySelector('.ogc-dd-btn').setAttribute('aria-expanded','false')})}
	document.addEventListener('click',function(e){if(!e.target.closest('.ogc-dd'))closeAll()});
	document.addEventListener('keydown',function(e){if(e.key==='Escape')closeAll()});
	form.addEventListener('input',changed);
	window.addEventListener('beforeunload',function(e){if(dirty.classList.contains('is-dirty')){e.preventDefault();e.returnValue=''}});
	form.addEventListener('submit',function(){dirty.classList.remove('is-dirty')});
	render();
	})();
	</script>
	<style>
	.ogc{--p:var(--hx-primary,#2563EB);display:grid;gap:16px;max-width:1100px}
	.ogc h2{margin:0;font-size:17px;font-weight:700;color:#111827}.ogc h3{margin:6px 0 2px;font-size:15px}
	.ogc-top{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.ogc-top p{margin:3px 0 0;color:#6B7280;font-size:13px}
	.ogc-top.is-sm{margin-bottom:14px}
	.ogc-list{display:grid;gap:14px}
	.ogc-rule{position:relative;display:grid;grid-template-columns:112px minmax(0,1fr) 36px;gap:20px;background:#fff;border:1px solid #E7E9EE;border-radius:18px;padding:20px;box-shadow:0 1px 2px rgba(16,24,40,.04);transition:border-color .15s,box-shadow .15s}
	.ogc-rule:focus-within{border-color:color-mix(in srgb,var(--p) 40%,#fff);box-shadow:0 4px 18px rgba(16,24,40,.07)}
	.ogc-badge{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;border-radius:14px;color:#fff;background:linear-gradient(140deg,var(--p),color-mix(in srgb,var(--p) 55%,#000));min-height:96px;align-self:start}
	.ogc-rule.is-off .ogc-badge{background:#E5E7EB;color:#6B7280}
	.ogc-bp{font-size:26px;line-height:1;font-weight:800;letter-spacing:-.02em}.ogc-bd{font-size:12px;opacity:.9;font-weight:600}
	.ogc-line{display:flex;align-items:center;gap:10px;flex-wrap:wrap;color:#374151;font-weight:500;font-size:14px}
	.ogc-in{display:inline-flex;align-items:center;border:1px solid #D6DAE1;border-radius:10px;background:#fff;overflow:hidden;transition:border-color .15s,box-shadow .15s}
	.ogc-in:focus-within{border-color:var(--p);box-shadow:0 0 0 3px color-mix(in srgb,var(--p) 18%,transparent)}
	.ogc-in input{border:0!important;box-shadow:none!important;background:transparent;min-height:40px;width:64px;padding:0 10px;font-size:15px;font-weight:700}
	.ogc-in input::placeholder{color:#C0C6CF;font-weight:400}
	.ogc-in i{font-style:normal;font-weight:600;color:#6B7280;font-size:13px;padding:0 12px;background:#F7F8FA;align-self:stretch;display:flex;align-items:center;border-inline-start:1px solid #E7E9EE;white-space:nowrap}
	.ogc-lab{margin:16px 0 8px;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#94A3B8}
	.ogc-chips{display:flex;flex-wrap:wrap;gap:8px}
	.ogc-chip{display:inline-flex;align-items:center;gap:8px;height:36px;padding:0 12px 0 5px;border:1px solid #D6DAE1;border-radius:999px;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s}
	.ogc-chip.is-all{padding-left:12px}
	.ogc-chip:hover{border-color:color-mix(in srgb,var(--p) 50%,#fff)}
	.ogc-chip img,.ogc-noimg{width:26px;height:26px;border-radius:50%;object-fit:cover;background:#EEF0F3;display:inline-block;flex:none}
	.ogc-ck{width:16px;height:16px;border-radius:50%;border:1.5px solid #C9CED8;display:inline-grid;place-items:center;flex:none;font-size:10px;line-height:1;box-sizing:border-box}
	.ogc-chip.is-on{background:color-mix(in srgb,var(--p) 9%,#fff);border-color:var(--p);color:color-mix(in srgb,var(--p) 80%,#000)}
	.ogc-chip.is-on .ogc-ck{background:var(--p);border-color:var(--p);color:#fff}.ogc-chip.is-on .ogc-ck:after{content:"\2713"}
	.ogc-chip:focus-visible{outline:2px solid var(--p);outline-offset:2px}
	.ogc-ex{margin:14px 0 0;font-size:13px;color:#6B7280;background:#F8FAFC;border-radius:10px;padding:9px 12px}.ogc-ex s{color:#9CA3AF}.ogc-ex b{color:#15803D}
	.ogc-del{border:0;background:transparent;color:#9CA3AF;font-size:22px;line-height:1;width:36px;height:36px;border-radius:10px;cursor:pointer}.ogc-del:hover{background:#FEE2E2;color:#B91C1C}
	.ogc-empty{text-align:center;background:#fff;border:1px dashed #D6DAE1;border-radius:18px;padding:34px 20px;color:#6B7280}.ogc-empty b{display:inline-grid;place-items:center;width:44px;height:44px;border-radius:12px;background:color-mix(in srgb,var(--p) 10%,#fff);color:var(--p);font-size:18px}.ogc-empty p{margin:0}
	.ogc-card{background:#fff;border:1px solid #E7E9EE;border-radius:18px;padding:20px 22px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
	.ogc-cars{display:grid}
	.ogc-car{display:grid;grid-template-columns:minmax(200px,300px) 1fr;gap:16px;align-items:center;padding:10px 0;border-top:1px solid #F1F2F5}.ogc-car:first-child{border-top:0}
	.ogc-cn{display:flex;align-items:center;gap:10px}.ogc-cn img,.ogc-cn .ogc-noimg{width:40px;height:40px;border-radius:10px;object-fit:cover}.ogc-cn b{display:block;font-size:13.5px;color:#111827}.ogc-cn small{color:#6B7280;font-size:12px}
	.ogc-tags{display:flex;flex-wrap:wrap;gap:6px}
	.ogc-tag{display:inline-flex;align-items:center;gap:4px;border-radius:999px;padding:4px 10px;font-size:12px;color:#374151;background:#F3F4F6}.ogc-tag b{color:#15803D}.ogc-tag.is-none{color:#9CA3AF;background:transparent;border:1px dashed #E5E7EB}
	.ogc-bar{position:sticky;bottom:0;z-index:5;display:flex;justify-content:space-between;align-items:center;gap:12px;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);border:1px solid #E7E9EE;border-radius:14px;padding:12px 16px;box-shadow:0 -4px 16px rgba(16,24,40,.06)}
	.ogc-help{color:#6B7280;font-size:13px}.ogc-help.is-dirty{color:#B45309;font-weight:600}
	.ogc-dd{position:relative;max-width:480px}
	.ogc-dd-btn{width:100%;display:flex;align-items:center;justify-content:space-between;gap:10px;min-height:42px;padding:0 14px;border:1px solid #D6DAE1;border-radius:10px;background:#fff;font-size:14px;font-weight:600;color:#111827;cursor:pointer;text-align:left;transition:border-color .15s,box-shadow .15s}
	.ogc-dd-btn:hover{border-color:#B8BFCA}
	.ogc-dd.is-open .ogc-dd-btn,.ogc-dd-btn:focus-visible{border-color:var(--p);box-shadow:0 0 0 3px color-mix(in srgb,var(--p) 18%,transparent);outline:0}
	.ogc-dd-val{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
	.ogc-dd-ar{width:7px;height:7px;border-right:2px solid #6B7280;border-bottom:2px solid #6B7280;transform:translateY(-2px) rotate(45deg);flex:none;transition:transform .15s}
	.ogc-dd.is-open .ogc-dd-ar{transform:translateY(2px) rotate(-135deg)}
	.ogc-dd-menu{position:absolute;z-index:30;top:calc(100% + 6px);left:0;right:0;background:#fff;border:1px solid #E7E9EE;border-radius:12px;box-shadow:0 14px 34px rgba(16,24,40,.16);padding:6px;max-height:330px;overflow:auto}
	.ogc-dd-menu .ogc-chip{display:flex;width:100%;height:auto;min-height:44px;padding:6px 10px;border:0;border-radius:8px;background:transparent;gap:12px;text-align:left;font-weight:500;color:#374151}
	.ogc-dd-menu .ogc-chip:hover{background:#F5F7FA}
	.ogc-dd-menu .ogc-chip.is-on{background:color-mix(in srgb,var(--p) 7%,#fff);color:#111827}
	.ogc-dd-menu .ogc-ck{order:-1;border-radius:5px}
	.ogc-dd-menu .ogc-chip.is-all{padding-left:10px;font-weight:700;border-bottom:1px solid #F1F2F5;border-radius:8px 8px 0 0;margin-bottom:4px}
	.ogc-dd-menu .ogc-chip img,.ogc-dd-menu .ogc-noimg{width:44px;height:30px;border-radius:6px;object-fit:cover}
	.ogc-rule{grid-template-columns:minmax(0,1fr) 32px;gap:16px;padding:22px}
	.ogc-badge{min-height:92px}.ogc-bp{font-size:24px}
	.ogc-fields{display:grid;grid-template-columns:160px 160px minmax(220px,1fr);gap:14px;align-items:end}
	.ogc-f{display:flex;flex-direction:column;gap:7px;min-width:0}
	.ogc-fl{font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#94A3B8}
	.ogc-f .ogc-in{display:flex;width:100%}.ogc-f .ogc-in input{flex:1;width:auto;min-width:0;min-height:42px}
	.ogc-f .ogc-dd{max-width:none}
	.ogc-ex{margin:16px 0 0;display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:10px 12px;border:1px dashed #E3E7ED;background:#FAFBFC;border-radius:12px;font-size:13px;color:#4B5563}
	.ogc-ex.is-empty{color:#9CA3AF}
	.ogc-exc{font-weight:700;font-size:12px;color:var(--p);background:color-mix(in srgb,var(--p) 9%,#fff);border-radius:999px;padding:3px 10px;white-space:nowrap}
	.ogc-exw{display:inline-flex;align-items:center;gap:8px;min-width:0}.ogc-exw img{width:38px;height:26px;border-radius:5px;object-fit:cover;flex:none}
	.ogc-exp{margin-left:auto;display:inline-flex;align-items:center;gap:8px;white-space:nowrap}.ogc-exp s{color:#9CA3AF}.ogc-exp b{font-size:15px;color:#111827}.ogc-exa{color:#9CA3AF}
	.ogc-exp em{font-style:normal;font-size:12px;font-weight:700;color:#15803D;background:#ECFDF3;border-radius:999px;padding:3px 9px}
	.ogc-car{grid-template-columns:minmax(220px,280px) minmax(150px,1fr) auto}
	.ogc-st{display:grid;grid-template-columns:repeat(4,108px);gap:8px}
	.ogc-s{background:#F8FAFC;border:1px solid #F0F2F5;border-radius:10px;padding:7px 10px;min-width:0}
	.ogc-s small{display:block;font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#94A3B8;white-space:nowrap}
	.ogc-s b{display:block;font-size:14px;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ogc-s.is-good b{color:#15803D}.ogc-s.is-disc b{color:#B45309}
	.ogc-sb{display:block;height:4px;border-radius:4px;background:#E5E7EB;margin-top:5px;overflow:hidden}.ogc-sb i{display:block;height:100%;background:#16A34A;border-radius:4px}
	.ogc-kpis{display:flex;gap:10px;flex-wrap:wrap}
	.ogc-k{border:1px solid #EEF0F3;border-radius:12px;padding:8px 14px;min-width:130px;background:#fff}.ogc-k small{display:block;font-size:11px;color:#6B7280;font-weight:600}.ogc-k b{font-size:18px;color:#111827}
	@media (max-width:1150px){.ogc-car{grid-template-columns:1fr 1fr}.ogc-st{grid-column:1/-1;grid-template-columns:repeat(4,minmax(0,1fr))}}
	@media (max-width:700px){.ogc-st{grid-template-columns:repeat(2,minmax(0,1fr))}.ogc-k{flex:1}}
	@media (max-width:1000px){.ogc-fields{grid-template-columns:1fr 1fr}.ogc-f.is-wide{grid-column:1/-1}}
	@media (max-width:700px){.ogc-exp{margin-left:0}}
	@media (max-width:700px){.ogc-rule{grid-template-columns:1fr;gap:14px;padding:16px}.ogc-badge{flex-direction:row;gap:10px;min-height:56px}.ogc-del{position:absolute;top:10px;right:10px;background:rgba(255,255,255,.85)}.ogc-car{grid-template-columns:1fr;gap:8px}}
	</style>
	<?php
	if ( function_exists( 'orego_ui_foot' ) ) {
		orego_ui_foot();
	} else {
		echo '</div>';
	}
}

add_filter( 'orego_app_menu', function ( $items ) {
	$items[] = array( 'Discounts', 'manage_options', 'orego-rules', 'Discounts' );
	return $items;
}, 10 );
