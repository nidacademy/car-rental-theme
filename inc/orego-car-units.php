<?php
/**
 * Several cars of the same model.
 * Each car post has "og_units" (how many of this model you own, default 1). A booking takes one of them
 * for its nights; a night is full when the bookings on it reach that number (see orego_blocked_nights()).
 * Full cars show "Reserved" on their card on the site.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** How many cars of this model you have. */
function orego_car_units( $car_id ) {
	$n = (int) get_post_meta( $car_id, 'og_units', true );
	return $n > 0 ? min( 99, $n ) : 1;
}

/** Bookings holding a car of this model on each night: [Y-m-d => count]. */
function orego_car_night_counts( $car_id, $exclude = 0 ) {
	$count = array();
	if ( ! function_exists( 'orego_bk_ranges' ) ) {
		return $count;
	}
	foreach ( orego_bk_ranges( $car_id, $exclude ) as $r ) {
		$s = strtotime( $r[0] . ' 00:00:00 UTC' );
		$e = strtotime( $r[1] . ' 00:00:00 UTC' );
		if ( ! $s ) {
			continue;
		}
		if ( ! $e || $e <= $s ) {
			$e = $s + DAY_IN_SECONDS;
		}
		for ( $t = $s, $i = 0; $t < $e && $i < 3000; $t += DAY_IN_SECONDS, $i++ ) {
			$d           = gmdate( 'Y-m-d', $t );
			$count[ $d ] = ( $count[ $d ] ?? 0 ) + 1;
		}
	}
	return $count;
}

/** Cars of this model still free tonight (0 when closed for maintenance or blocked by hand). */
function orego_car_free_today( $car_id ) {
	$today = current_time( 'Y-m-d' );
	if ( in_array( $today, orego_blocked_nights( $car_id ), true ) ) {
		return 0;
	}
	$c = orego_car_night_counts( $car_id );
	return max( 0, orego_car_units( $car_id ) - ( $c[ $today ] ?? 0 ) );
}

/** First day from $from on when at least one car of this model is free. */
function orego_car_next_free( $car_id, $from = '' ) {
	$taken = array_flip( orego_blocked_nights( $car_id ) );
	$t     = strtotime( ( $from ? $from : current_time( 'Y-m-d' ) ) . ' 00:00:00 UTC' );
	for ( $i = 0; $i < 400; $i++, $t += DAY_IN_SECONDS ) {
		if ( ! isset( $taken[ gmdate( 'Y-m-d', $t ) ] ) ) {
			return gmdate( 'Y-m-d', $t );
		}
	}
	return '';
}

/**
 * Should this car's card say "Reserved"?
 * With searched dates: when every car of the model is taken on one of those nights. Otherwise: when none is free today.
 * Returns '' or the label to show.
 */
function orego_car_reserved_label( $car_id ) {
	if ( function_exists( 'orego_trip' ) ) {
		$trip = orego_trip();
		if ( ! empty( $trip['from'] ) && function_exists( 'orego_trip_blocked' ) ) {
			return orego_trip_blocked( $car_id ) ? 'Reserved on your dates' : '';
		}
	}
	if ( orego_car_free_today( $car_id ) > 0 ) {
		return '';
	}
	$next = orego_car_next_free( $car_id );
	return $next ? 'Reserved · free from ' . date_i18n( 'j M', strtotime( $next . ' 12:00:00' ) ) : 'Reserved';
}

/* ------------------------------------------------------------------ */
/* Car details box: saving "how many cars"                            */
/* ------------------------------------------------------------------ */

add_action( 'save_post_orego_car', function ( $post_id ) {
	if ( ! isset( $_POST['orego_car_nonce'], $_POST['og_units'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['orego_car_nonce'] ) ), 'orego_car_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, 'og_units', (string) max( 1, min( 99, (int) wp_unslash( $_POST['og_units'] ) ) ) );
}, 20 );

/* Admin car list: how many free today. */
add_filter( 'manage_orego_car_posts_columns', function ( $cols ) {
	$cols['og_units'] = 'متاح اليوم';
	return $cols;
}, 20 );

add_action( 'manage_orego_car_posts_custom_column', function ( $col, $id ) {
	if ( 'og_units' !== $col ) {
		return;
	}
	$free  = orego_car_free_today( $id );
	$units = orego_car_units( $id );
	$color = $free ? ( $free < $units ? '#B45309' : '#15803D' ) : '#B91C1C';
	echo '<strong style="color:' . esc_attr( $color ) . '">' . (int) $free . ' / ' . (int) $units . '</strong>';
}, 10, 2 );

/* ------------------------------------------------------------------ */
/* "Reserved" on the car cards                                         */
/* ------------------------------------------------------------------ */

add_action( 'wp_head', function () {
	echo '<style id="og-reserved">'
		. '.og-cars .og-media{position:relative}'
		. '.og-cars .og-v.og-is-reserved .og-media img{filter:grayscale(.45);opacity:.8}'
		. '.og-cars .og-reserved{position:absolute;bottom:12px;inset-inline-start:12px;display:inline-flex;align-items:center;gap:7px;max-width:calc(100% - 24px);background:rgba(21,23,28,.88);color:#FFFFFF;font:700 12px/1.3 Cairo,Tahoma,sans-serif;padding:6px 12px;border-radius:999px;box-shadow:0 6px 16px -6px rgba(21,23,28,.5);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}'
		. '.og-cars .og-reserved::before{content:"";flex:0 0 auto;width:7px;height:7px;border-radius:50%;background:#F87171}'
		. '@media (max-width:767px){.og-cars .og-reserved{white-space:normal;align-items:flex-start;bottom:8px;inset-inline-start:8px;max-width:calc(100% - 16px);font-size:11px;padding:5px 10px;border-radius:12px}.og-cars .og-reserved::before{margin-top:4px}}'
		. 'body .ogx-rent.is-unavailable{width:100%;border:0;font-family:inherit;background:#E5E7EB;color:#6B7280!important;box-shadow:none;cursor:not-allowed;animation:none!important;transform:none!important}'
		. 'body .ogx-rent.is-unavailable:hover{background:#E5E7EB}'
		. '.ogx-back-on{margin:8px 0 0;text-align:center;font:600 13px/1.4 Cairo,Tahoma,sans-serif;color:#B91C1C}'
		. '</style>';
}, 36 );

/* ------------------------------------------------------------------ */
/* Keep cached pages in step with bookings                             */
/* ------------------------------------------------------------------ */

/* Pages are cached; refresh them when a booking or a car's availability changes... */
function orego_units_purge_soon() {
	static $queued = false;
	if ( $queued ) {
		return;
	}
	$queued = true;
	add_action( 'shutdown', function () {
		do_action( 'litespeed_purge_all' );
		foreach ( get_posts( array( 'post_type' => 'page', 'numberposts' => 50, 'fields' => 'ids', 'meta_key' => '_elementor_element_cache' ) ) as $p ) {
			delete_post_meta( $p, '_elementor_element_cache' );
		}
	} );
}
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
	add_action( $hook, function ( $mid, $post_id, $key ) {
		if ( in_array( $key, array( 'og_bstatus', 'og_start', 'og_end', 'og_car', 'og_units', 'og_booked', 'og_maint' ), true ) ) {
			orego_units_purge_soon();
		}
	}, 10, 3 );
}
add_action( 'before_delete_post', function ( $id ) {
	if ( 'orego_booking' === get_post_type( $id ) ) {
		orego_units_purge_soon();
	}
} );

/* ...and every night just after midnight, when bookings start and end. */
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'orego_units_nightly' ) ) {
		$midnight = strtotime( 'tomorrow 00:05', current_time( 'timestamp' ) ) - (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		wp_schedule_event( $midnight, 'daily', 'orego_units_nightly' );
	}
} );
add_action( 'orego_units_nightly', 'orego_units_purge_soon' );