<?php
/**
 * Trip search: reads the searched trip (dates, times, locations) from the address,
 * hides cars taken on those dates and carries the trip through the car page into the booking page.
 * (The old hero search form [orego_search] was removed; the Cars page has its own search bar.)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Trip details from the address bar, validated. */
function orego_trip() {
	static $t = null;
	if ( null !== $t ) {
		return $t;
	}
	$g   = function ( $k ) {
		return isset( $_GET[ $k ] ) ? sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) : ''; // phpcs:ignore
	};
	$ymd = '/^\d{4}-\d{2}-\d{2}$/';
	$hm  = '/^([01]\d|2[0-3]):[0-5]\d$/';
	$t   = array();
	$from = $g( 'from' );
	$to   = $g( 'to' );
	if ( preg_match( $ymd, $from ) && preg_match( $ymd, $to ) && $to > $from && $from >= current_time( 'Y-m-d' ) ) {
		$t['from'] = $from;
		$t['to']   = $to;
	}
	foreach ( array( 'ptime', 'rtime' ) as $k ) {
		if ( preg_match( $hm, $g( $k ) ) ) {
			$t[ $k ] = $g( $k );
		}
	}
	$locs = function_exists( 'orego_locations' ) ? orego_locations() : array();
	foreach ( array( 'pickup', 'dropoff' ) as $k ) {
		if ( in_array( $g( $k ), $locs, true ) ) {
			$t[ $k ] = $g( $k );
		}
	}
	return $t;
}

/** Query args to carry the trip to the next page. */
function orego_trip_args() {
	return array_map( 'rawurlencode', orego_trip() );
}

/** Is this car taken on any night of the searched dates? */
function orego_trip_blocked( $car_id ) {
	$t = orego_trip();
	if ( empty( $t['from'] ) || ! function_exists( 'orego_blocked_nights' ) ) {
		return false;
	}
	$taken = array_flip( orego_blocked_nights( $car_id ) );
	for ( $d = strtotime( $t['from'] . ' 00:00:00 UTC' ), $end = strtotime( $t['to'] . ' 00:00:00 UTC' ); $d < $end; $d += DAY_IN_SECONDS ) {
		if ( isset( $taken[ gmdate( 'Y-m-d', $d ) ] ) ) {
			return true;
		}
	}
	return false;
}

/* Browse Cars: when dates were searched, say so above the cars */
function orego_trip_notice() {
	$t = orego_trip();
	if ( empty( $t['from'] ) ) {
		return '';
	}
	$f = date_i18n( 'j F', strtotime( $t['from'] . ' 12:00' ) );
	$e = date_i18n( 'j F Y', strtotime( $t['to'] . ' 12:00' ) );
	return '<div class="ogs-note"><span>Cars available from <b>' . esc_html( $f ) . '</b> to <b>' . esc_html( $e ) . '</b>' . ( ! empty( $t['pickup'] ) ? ' · ' . esc_html( $t['pickup'] ) : '' ) . '</span><a href="' . esc_url( orego_purl( 'cars' ) ) . '">View all cars</a></div>';
}

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'orego-search', false, array(), '1.0' );
	wp_enqueue_style( 'orego-search' );
	wp_add_inline_style( 'orego-search', <<<'CSS'
.ogs-note{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;grid-column:1/-1;background:var(--hx-primary-soft,#e8f1f0);color:var(--hx-primary,#0f5754);border-radius:14px;padding:12px 16px;font-family:var(--hx-font,Cairo,Tahoma,sans-serif);font-size:15px;margin-bottom:18px}
.ogs-note a{color:var(--hx-primary,#0f5754);font-weight:700;text-decoration:underline}
html[dir=ltr] .ogx2-links a,html[dir=ltr] .ogx2-menu a,html[dir=ltr] .ogx2-row>a,html[dir=ltr] .ogx2-sub a,html[dir=ltr] .ogs-chip span,html[dir=ltr] .ogs-flbl,html[dir=ltr] .og-cars .og-tag,html[dir=ltr] .og-cars .og-specs li,html[dir=ltr] .ogx-sub,html[dir=ltr] .ogx-tiles b,html[dir=ltr] .ogd-sim-tag{text-transform:capitalize}
CSS
	);
}, 30 );
