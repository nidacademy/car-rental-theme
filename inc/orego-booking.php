<?php
/**
 * Exotic Cars Booking
 * - "احجز رحلتك" page: [orego_booking] (step 1: dates + locations, step 2: confirm, pay at pickup)
 * - Bookings in the dashboard under Your Cars (pending until approved)
 * - Pickup/drop-off locations list
 * - Availability: pending + confirmed bookings and manual blocks close days on the calendar
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Data                                                                */
/* ------------------------------------------------------------------ */

add_action( 'init', function () {
	register_post_type( 'orego_booking', array(
		'labels'          => array(
			'name'               => 'Bookings',
			'singular_name'      => 'Booking',
			'menu_name'          => 'الحجوزات',
			'all_items'          => 'الحجوزات',
			'edit_item'          => 'تفاصيل الحجز',
			'search_items'       => 'ابحث في الحجوزات',
			'not_found'          => 'لا توجد حجوزات بعد',
			'not_found_in_trash' => 'لا توجد حجوزات في السلة',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => 'edit.php?post_type=orego_car',
		'show_in_rest'    => false,
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'    => true,
	) );
} );

function orego_bk_statuses() {
	return array(
		'pending'   => array( 'Pending', '#B45309', '#FEF3C7' ),
		'confirmed' => array( 'Confirmed', '#15803D', '#DCFCE7' ),
		'onroad'    => array( 'On road', '#6D28D9', '#EDE9FE' ), // picked up, the car is with the customer
		'declined'  => array( 'Declined', '#B91C1C', '#FEE2E2' ),
		'cancelled' => array( 'Cancelled', '#4B5563', '#F3F4F6' ),
		'completed' => array( 'Returned', '#1D4ED8', '#DBEAFE' ), // the car is back
	);
}

function orego_locations() {
	$l = get_option( 'orego_locations' );
	if ( ! is_array( $l ) || ! $l ) {
		$l = array( 'Portland International Airport (PDX)', 'Downtown Portland' );
	}
	return array_values( array_filter( array_map( 'trim', $l ) ) );
}

function orego_booking_page_id() {
	return (int) get_option( 'orego_booking_page' );
}

function orego_booking_url( $car_id ) {
	$p = orego_booking_page_id();
	return $p ? add_query_arg( 'car', (int) $car_id, get_permalink( $p ) ) : get_permalink( $car_id );
}

/** Bookings that hold the car (pending + confirmed), as [start, end] (end = return day). */
function orego_bk_ranges( $car_id, $exclude = 0, $statuses = array( 'pending', 'confirmed', 'onroad' ) ) {
	$ids = get_posts( array(
		'post_type'    => 'orego_booking',
		'post_status'  => 'publish',
		'numberposts'  => -1,
		'fields'       => 'ids',
		'post__not_in' => $exclude ? array( (int) $exclude ) : array(),
		'meta_query'   => array(
			array( 'key' => 'og_car', 'value' => (int) $car_id ),
			array( 'key' => 'og_bstatus', 'value' => $statuses, 'compare' => 'IN' ),
		),
	) );
	$out   = array();
	$today = current_time( 'Y-m-d' );
	foreach ( $ids as $b ) {
		$s = get_post_meta( $b, 'og_start', true );
		$e = get_post_meta( $b, 'og_end', true );
		if ( 'pending' === get_post_meta( $b, 'og_bstatus', true ) && $s < $today ) {
			continue; // request never answered and pickup date passed: don't keep the car blocked
		}
		// Rented and the return day has passed, but not marked Returned yet: the car is still out, keep it unavailable until it is.
		if ( in_array( get_post_meta( $b, 'og_bstatus', true ), array( 'confirmed', 'onroad' ), true ) && $s && $e && $s <= $today && $e < $today ) {
			$out[] = array( $today, gmdate( 'Y-m-d', strtotime( $today . ' 00:00:00 UTC' ) + 730 * DAY_IN_SECONDS ) );
			continue;
		}
		if ( $s && $e && $e >= $today ) {
			$out[] = array( $s, $e );
		}
	}
	return $out;
}

/** Every night (Y-m-d) the car is taken, from bookings and manual blocks. */
function orego_blocked_nights( $car_id, $exclude = 0 ) {
	$nights = array();
	$add    = function ( $a, $b ) use ( &$nights ) {
		$s = strtotime( $a . ' 00:00:00 UTC' );
		$e = strtotime( $b . ' 00:00:00 UTC' );
		if ( ! $s ) {
			return;
		}
		if ( ! $e || $e <= $s ) {
			$e = $s + DAY_IN_SECONDS;
		}
		for ( $t = $s; $t < $e && count( $nights ) < 3000; $t += DAY_IN_SECONDS ) {
			$nights[ gmdate( 'Y-m-d', $t ) ] = true;
		}
	};
	// Bookings: a night is taken only when they hold every car of this model (Car details -> number of cars).
	if ( function_exists( 'orego_car_night_counts' ) ) {
		$units = orego_car_units( $car_id );
		foreach ( orego_car_night_counts( $car_id, $exclude ) as $night => $n ) {
			if ( $n >= $units ) {
				$nights[ $night ] = true;
			}
		}
	} else {
		foreach ( orego_bk_ranges( $car_id, $exclude ) as $r ) {
			$add( $r[0], $r[1] );
		}
	}
	// Maintenance and days blocked by hand close the whole model.
	foreach ( (array) apply_filters( 'orego_blocked_ranges', array(), $car_id ) as $r ) {
		if ( is_array( $r ) && ! empty( $r[0] ) ) {
			$add( $r[0], $r[1] ?? $r[0] );
		}
	}
	foreach ( preg_split( '/\r\n|\r|\n/', (string) get_post_meta( $car_id, 'og_booked', true ) ) as $line ) {
		if ( preg_match_all( '/(\d{4}-\d{2}-\d{2})/', $line, $m ) && $m[1] ) {
			$add( $m[1][0], $m[1][1] ?? $m[1][0] );
		}
	}
	$today = current_time( 'Y-m-d' );
	$list  = array_values( array_filter( array_keys( $nights ), function ( $d ) use ( $today ) {
		return $d >= $today;
	} ) );
	sort( $list );
	return $list;
}

function orego_bk_date( $ymd ) {
	return $ymd ? date_i18n( 'l j F Y', strtotime( $ymd . ' 12:00:00' ) ) : '';
}

function orego_bk_info( $id ) {
	$m = function ( $k ) use ( $id ) {
		return get_post_meta( $id, $k, true );
	};
	return array(
		'car'     => (int) $m( 'og_car' ),
		'start'   => $m( 'og_start' ),
		'end'     => $m( 'og_end' ),
		'ptime'   => $m( 'og_ptime' ),
		'rtime'   => $m( 'og_rtime' ),
		'pickup'  => $m( 'og_pickup' ),
		'dropoff' => $m( 'og_dropoff' ),
		'name'    => $m( 'og_name' ),
		'email'   => $m( 'og_email' ),
		'phone'   => $m( 'og_phone' ),
		'notes'   => $m( 'og_notes' ),
		'days'    => (int) $m( 'og_days' ),
		'pday'    => (float) $m( 'og_price_day' ),
		'total'   => (float) $m( 'og_total' ),
		'status'  => $m( 'og_bstatus' ) ? $m( 'og_bstatus' ) : 'pending',
		'anote'   => $m( 'og_admin_note' ),
	);
}

/** Booking currency: [code, rate from USD]. A booking keeps the currency and rate it was made with. */
function orego_bk_cur( $id = null ) {
	if ( $id ) {
		$c = (string) get_post_meta( $id, 'og_cur', true );
		if ( preg_match( '/^[A-Z]{3}$/', $c ) ) {
			$r = (float) get_post_meta( $id, 'og_rate', true );
			return array( $c, $r > 0 ? $r : 1 );
		}
		return array( 'USD', 1 ); // bookings made before the setting existed
	}
	// No booking yet: the currency the visitor chose on the site (sent with the quote and the booking form), else USD.
	$rt = function_exists( 'orego_rates' ) ? orego_rates() : array( 'USD' => 1 );
	$c  = isset( $_REQUEST['og_cur'] ) ? strtoupper( sanitize_key( wp_unslash( $_REQUEST['og_cur'] ) ) ) : 'USD';
	$c  = preg_match( '/^[A-Z]{3}$/', $c ) && isset( $rt[ $c ] ) ? $c : 'USD'; // only currencies shown on the site
	return array( $c, (float) ( $rt[ $c ] ?? 1 ) );
}

function orego_cur_fmt( $v, $code ) {
	if ( function_exists( 'orego_currency_format' ) ) { // Customize -> Currencies
		return orego_currency_format( $v, $code );
	}
	$v = round( (float) $v, 2 );
	$n = number_format( abs( $v ), floor( abs( $v ) ) == abs( $v ) ? 0 : 2 );
	$s = $v < 0 ? '-' : '';
	return 'MAD' === $code ? $s . $n . ' MAD' : ( 'EUR' === $code ? $s . '€' . $n : $s . '$' . $n );
}

/** USD amount shown in the booking currency (of booking $id, or the current setting). */
function orego_bk_money( $v, $id = null ) {
	list( $c, $r ) = orego_bk_cur( $id );
	return orego_cur_fmt( (float) $v * $r, $c );
}

/** Amount typed by the admin in the booking currency, back to USD for storage. */
function orego_bk_to_usd( $v, $id ) {
	list( , $r ) = orego_bk_cur( $id );
	return round( (float) $v / $r, 4 );
}

/* ------------------------------------------------------------------ */
/* Emails                                                              */
/* ------------------------------------------------------------------ */

function orego_bk_lines( $id ) {
	$i     = orego_bk_info( $id );
	$lines = array(
		'Booking number: #' . $id,
		'Car: ' . get_the_title( $i['car'] ),
		'Pick-up: ' . orego_bk_date( $i['start'] ) . ' - ' . $i['ptime'] . ' - ' . $i['pickup'],
		'Return: ' . orego_bk_date( $i['end'] ) . ' - ' . $i['rtime'] . ' - ' . $i['dropoff'],
		'Duration: ' . $i['days'] . ( 1 === (int) $i['days'] ? ' day' : ' days' ),
		'Total: ' . orego_bk_money( $i['total'], $id ) . ' (pay at pick-up)',
	);
	return apply_filters( 'orego_bk_lines', $lines, $id );
}

function orego_bk_mail_customer( $id, $type ) {
	if ( function_exists( 'orego_mail_send_booking' ) ) { // branded HTML email (orego-email-template.php)
		orego_mail_send_booking( $id, $type );
		return;
	}
	$i = orego_bk_info( $id );
	if ( ! is_email( $i['email'] ) ) {
		return;
	}
	$texts = array(
		'received'  => array( 'We received your booking request #' . $id, 'Thank you! We have received your booking request and it is now waiting for approval. We will email you as soon as it is confirmed.' ),
		'confirmed' => array( 'Your booking #' . $id . ' is confirmed', 'Good news: your booking is confirmed. Your car will be ready on time.' ),
		'declined'  => array( 'About your booking request #' . $id, 'We are sorry, we could not confirm your booking request this time. We would be happy to help you choose another car or other dates.' ),
		'cancelled' => array( 'Your booking #' . $id . ' has been cancelled', 'Your booking has been cancelled. If you have any questions, just reply to this email.' ),
	);
	if ( empty( $texts[ $type ] ) ) {
		return;
	}
	$body  = 'Hello ' . $i['name'] . ",\n\n" . $texts[ $type ][1] . "\n\n" . implode( "\n", orego_bk_lines( $id ) );
	if ( $i['anote'] && 'received' !== $type ) {
		$body .= "\n\n" . $i['anote'];
	}
	$body  = apply_filters( 'orego_bk_mail_body', $body, $id, $type );
	$body .= "\n\nThe " . orego_brand() . " team\n" . home_url( '/' );
	wp_mail( $i['email'], $texts[ $type ][0], $body, array( 'Reply-To: ' . get_option( 'admin_email' ) ) );
}

function orego_bk_mail_admin( $id ) {
	if ( function_exists( 'orego_mail_send_admin' ) ) {
		orego_mail_send_admin( $id );
		return;
	}
	$i    = orego_bk_info( $id );
	$body = "New booking request waiting for your approval.\n\n" . implode( "\n", orego_bk_lines( $id ) ) . "\n\nCustomer: " . $i['name'] . "\nPhone: " . $i['phone'] . "\nEmail: " . $i['email'] . ( $i['notes'] ? "\nNotes: " . $i['notes'] : '' ) . "\n\nReview the request: " . admin_url( 'post.php?post=' . $id . '&action=edit' );
	wp_mail( get_option( 'admin_email' ), 'New booking request #' . $id . ' - ' . get_the_title( $i['car'] ), $body, array( 'Reply-To: ' . $i['email'] ) );
}

/* ------------------------------------------------------------------ */
/* Booking request (AJAX)                                              */
/* ------------------------------------------------------------------ */

add_action( 'wp_ajax_orego_book', 'orego_handle_booking' );
add_action( 'wp_ajax_nopriv_orego_book', 'orego_handle_booking' );

function orego_handle_booking() {
	$fail = function ( $msg, $extra = array() ) {
		wp_send_json_error( array_merge( array( 'message' => $msg ), $extra ), 400 );
	};
	if ( ! check_ajax_referer( 'orego_book', 'nonce', false ) ) {
		$fail( 'This page has expired. Refresh the page and try again.' );
	}
	if ( ! empty( $_POST['website'] ) ) {
		$fail( 'The request could not be sent.' );
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rk = 'orego_bk_rl_' . md5( $ip );
	$rn = (int) get_transient( $rk );
	if ( $rn >= 5 && ! current_user_can( 'edit_posts' ) ) {
		$fail( 'Too many requests. Please try again in a moment.' );
	}
	$car = absint( $_POST['car'] ?? 0 );
	$p   = get_post( $car );
	if ( ! $p || 'orego_car' !== $p->post_type || ( 'publish' !== $p->post_status && ! current_user_can( 'edit_post', $car ) ) ) {
		$fail( 'This car is not available for booking.' );
	}
	$start = sanitize_text_field( wp_unslash( $_POST['start'] ?? '' ) );
	$end   = sanitize_text_field( wp_unslash( $_POST['end'] ?? '' ) );
	$re    = '/^\d{4}-\d{2}-\d{2}$/';
	if ( ! preg_match( $re, $start ) || ! preg_match( $re, $end ) || $end <= $start || $start < current_time( 'Y-m-d' ) ) {
		$fail( 'Choose a valid pick-up and return date.' );
	}
	$days = (int) round( ( strtotime( $end . ' 00:00:00 UTC' ) - strtotime( $start . ' 00:00:00 UTC' ) ) / DAY_IN_SECONDS );
	if ( $days < 1 || $days > 60 ) {
		$fail( 'A booking must last between 1 and 60 days.' );
	}
	// One booking at a time per car, so two visitors can't take the same days at the same moment.
	$lock = 'orego_bk_lock_' . $car;
	if ( ! add_option( $lock, time(), '', 'no' ) ) {
		if ( time() - (int) get_option( $lock ) < 30 ) {
			$fail( 'Another booking for this car is being processed. Please try again in a few seconds.' );
		}
		update_option( $lock, time(), 'no' );
	}
	register_shutdown_function( function () use ( $lock ) {
		delete_option( $lock );
	} );
	$blocked = orego_blocked_nights( $car );
	$set     = array_flip( $blocked );
	for ( $t = strtotime( $start . ' 00:00:00 UTC' ), $n = 0; $n < $days; $n++, $t += DAY_IN_SECONDS ) {
		if ( isset( $set[ gmdate( 'Y-m-d', $t ) ] ) ) {
			$fail( 'Some of the days you chose are no longer available. Choose other dates.', array( 'blocked' => $blocked ) );
		}
	}
	$locs = orego_locations();
	$pick = sanitize_text_field( wp_unslash( $_POST['pickup'] ?? '' ) );
	$drop = sanitize_text_field( wp_unslash( $_POST['dropoff'] ?? '' ) );
	if ( ! in_array( $pick, $locs, true ) ) {
		$fail( 'Choose a pick-up location.' );
	}
	if ( '' === $drop ) {
		$drop = $pick;
	} elseif ( ! in_array( $drop, $locs, true ) ) {
		$fail( 'Choose a return location.' );
	}
	$tre   = '/^([01]\d|2[0-3]):[0-5]\d$/';
	$ptime = sanitize_text_field( wp_unslash( $_POST['ptime'] ?? '10:00' ) );
	$rtime = sanitize_text_field( wp_unslash( $_POST['rtime'] ?? '10:00' ) );
	$ptime = preg_match( $tre, $ptime ) ? $ptime : '10:00';
	$rtime = preg_match( $tre, $rtime ) ? $rtime : '10:00';
	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$phone = trim( preg_replace( '/[^\d+\s()\-]/', '', wp_unslash( $_POST['phone'] ?? '' ) ) );
	$notes = mb_substr( sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ), 0, 1000 );
	if ( mb_strlen( $name ) < 2 ) {
		$fail( 'Enter your full name.' );
	}
	if ( ! is_email( $email ) ) {
		$fail( 'Enter a valid email address.' );
	}
	if ( strlen( preg_replace( '/\D/', '', $phone ) ) < 7 ) {
		$fail( 'Enter a valid phone number.' );
	}
	if ( empty( $_POST['agree'] ) ) {
		$fail( 'Please accept the rental terms.' );
	}
	$d     = orego_car_data( $car );
	$price = (float) $d['price'];
	if ( $price <= 0 ) {
		$fail( 'This car has no price yet. Contact us to book it.' );
	}
	$quote = function_exists( 'orego_quote' ) ? orego_quote( $car, $start, $end ) : null;
	if ( is_wp_error( $quote ) ) {
		$fail( $quote->get_error_message() );
	}
	$bid = wp_insert_post( array(
		'post_type'   => 'orego_booking',
		'post_status' => 'publish',
		'post_title'  => get_the_title( $car ) . ' - ' . $name,
	), true );
	if ( is_wp_error( $bid ) || ! $bid ) {
		$fail( 'An unexpected error occurred. Please try again.' );
	}
	$meta = array(
		'og_car'       => $car,
		'og_start'     => $start,
		'og_end'       => $end,
		'og_ptime'     => $ptime,
		'og_rtime'     => $rtime,
		'og_pickup'    => $pick,
		'og_dropoff'   => $drop,
		'og_name'      => $name,
		'og_email'     => $email,
		'og_phone'     => $phone,
		'og_notes'     => $notes,
		'og_days'      => $days,
		'og_price_day' => $price,
		'og_total'     => $quote ? $quote['total'] : round( $price * $days, 2 ),
		'og_bstatus'   => 'pending',
		'og_cur'       => orego_bk_cur()[0],
		'og_rate'      => orego_bk_cur()[1],
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $bid, $k, $v );
	}
	wp_update_post( array( 'ID' => $bid, 'post_title' => '#' . $bid . ' - ' . get_the_title( $car ) . ' - ' . $name ) );
	set_transient( $rk, $rn + 1, HOUR_IN_SECONDS );
	do_action( 'orego_booking_created', $bid, $quote );
	orego_bk_mail_customer( $bid, 'received' );
	orego_bk_mail_admin( $bid );
	wp_send_json_success( apply_filters( 'orego_booking_response', array( 'ref' => $bid ), $bid ) );
}

/* ------------------------------------------------------------------ */
/* Dashboard: bookings list, actions, edit screen, locations           */
/* ------------------------------------------------------------------ */

function orego_bk_badge( $status ) {
	$s = orego_bk_statuses();
	$v = $s[ $status ] ?? $s['pending'];
	return '<span style="display:inline-block;padding:3px 10px;border-radius:999px;font-weight:600;font-size:12px;color:' . esc_attr( $v[1] ) . ';background:' . esc_attr( $v[2] ) . '">' . esc_html( $v[0] ) . '</span>';
}

function orego_bk_set_status( $id, $status, $notify = true ) {
	if ( ! isset( orego_bk_statuses()[ $status ] ) ) {
		return false;
	}
	$old = get_post_meta( $id, 'og_bstatus', true );
	if ( $old === $status ) {
		return true;
	}
	update_post_meta( $id, 'og_bstatus', $status );
	do_action( 'orego_bk_status_changed', $id, $status, $old );
	if ( $notify && in_array( $status, array( 'confirmed', 'onroad', 'declined', 'cancelled', 'completed' ), true ) ) { // every step emails the customer
		orego_bk_mail_customer( $id, $status );
	}
	return true;
}

add_filter( 'manage_orego_booking_posts_columns', function ( $c ) {
	return array(
		'cb'          => $c['cb'],
		'title'       => 'الحجز',
		'og_dates'    => 'التواريخ',
		'og_customer' => 'العميل',
		'og_total'    => 'الإجمالي',
		'og_status'   => 'الحالة',
		'date'        => 'تاريخ الطلب',
	);
} );

add_action( 'manage_orego_booking_posts_custom_column', function ( $col, $id ) {
	$i = orego_bk_info( $id );
	if ( 'og_dates' === $col ) {
		echo esc_html( date_i18n( 'j M', strtotime( $i['start'] ) ) . ' ← ' . date_i18n( 'j M Y', strtotime( $i['end'] ) ) ) . '<br><span style="color:#646970">' . esc_html( $i['days'] . ' ' . ( $i['days'] >= 3 && $i['days'] <= 10 ? 'أيام' : 'يوم' ) . ' · ' . $i['pickup'] ) . '</span>';
	}
	if ( 'og_customer' === $col ) {
		echo esc_html( $i['name'] ) . '<br><a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $i['phone'] ) ) . '" dir="ltr">' . esc_html( $i['phone'] ) . '</a>';
	}
	if ( 'og_total' === $col ) {
		echo '<strong>' . esc_html( orego_bk_money( $i['total'], $id ) ) . '</strong>';
	}
	if ( 'og_status' === $col ) {
		echo orego_bk_badge( $i['status'] ); // phpcs:ignore
	}
}, 10, 2 );

function orego_bk_action_url( $id, $status ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=orego_bk_status&id=' . (int) $id . '&status=' . $status ), 'orego_bk_' . $id . '_' . $status );
}

add_filter( 'post_row_actions', function ( $a, $post ) {
	if ( 'orego_booking' !== $post->post_type ) {
		return $a;
	}
	unset( $a['inline hide-if-no-js'], $a['view'] );
	$st  = get_post_meta( $post->ID, 'og_bstatus', true );
	$new = array();
	if ( 'pending' === $st ) {
		$new['og_ok'] = '<a href="' . esc_url( orego_bk_action_url( $post->ID, 'confirmed' ) ) . '" style="color:#15803D;font-weight:600">موافقة</a>';
		$new['og_no'] = '<a href="' . esc_url( orego_bk_action_url( $post->ID, 'declined' ) ) . '" style="color:#B91C1C">رفض</a>';
	}
	if ( 'confirmed' === $st ) {
		$new['og_road'] = '<a href="' . esc_url( orego_bk_action_url( $post->ID, 'onroad' ) ) . '">On road</a>';
	}
	if ( 'onroad' === $st ) {
		$new['og_done'] = '<a href="' . esc_url( orego_bk_action_url( $post->ID, 'completed' ) ) . '">Returned</a>';
	}
	if ( 'confirmed' === $st ) {
		$new['og_done']   = '<a href="' . esc_url( orego_bk_action_url( $post->ID, 'completed' ) ) . '">Returned</a>';
		$new['og_cancel'] = '<a href="' . esc_url( orego_bk_action_url( $post->ID, 'cancelled' ) ) . '" style="color:#B91C1C">إلغاء</a>';
	}
	$a['edit'] = '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">التفاصيل</a>';
	return $new + $a;
}, 10, 2 );

add_action( 'admin_post_orego_bk_status', function () {
	$id     = absint( $_GET['id'] ?? 0 );
	$status = sanitize_key( $_GET['status'] ?? '' );
	check_admin_referer( 'orego_bk_' . $id . '_' . $status );
	if ( ! $id || 'orego_booking' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'غير مسموح.' );
	}
	orego_bk_set_status( $id, $status );
	wp_safe_redirect( add_query_arg( 'og_msg', $status, wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=orego_booking' ) ) );
	exit;
} );

add_action( 'admin_notices', function () {
	if ( empty( $_GET['og_msg'] ) ) {
		return;
	}
	$s = orego_bk_statuses()[ sanitize_key( $_GET['og_msg'] ) ] ?? null;
	if ( $s ) {
		echo '<div class="notice notice-success is-dismissible"><p>تم تحديث الحجز إلى: ' . esc_html( $s[0] ) . '</p></div>';
	}
} );

/* Status filter links above the list */
add_filter( 'views_edit-orego_booking', function ( $views ) {
	$base = admin_url( 'edit.php?post_type=orego_booking' );
	$cur  = sanitize_key( $_GET['og_status'] ?? '' );
	$out  = array( 'all' => '<a href="' . esc_url( $base ) . '"' . ( $cur ? '' : ' class="current"' ) . '>الكل</a>' );
	foreach ( orego_bk_statuses() as $k => $v ) {
		$n = count( get_posts( array( 'post_type' => 'orego_booking', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => 'og_bstatus', 'meta_value' => $k ) ) );
		if ( $n ) {
			$out[ $k ] = '<a href="' . esc_url( add_query_arg( 'og_status', $k, $base ) ) . '"' . ( $cur === $k ? ' class="current"' : '' ) . '>' . esc_html( $v[0] ) . ' <span class="count">(' . $n . ')</span></a>';
		}
	}
	if ( isset( $views['trash'] ) ) {
		$out['trash'] = $views['trash'];
	}
	return $out;
} );

add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() || 'orego_booking' !== $q->get( 'post_type' ) ) {
		return;
	}
	$st = sanitize_key( $_GET['og_status'] ?? '' );
	if ( $st && isset( orego_bk_statuses()[ $st ] ) ) {
		$q->set( 'meta_key', 'og_bstatus' );
		$q->set( 'meta_value', $st );
	}
} );

/* Pending counter on the menu */
add_action( 'admin_menu', function () {
	global $submenu, $menu;
	$n = count( get_posts( array( 'post_type' => 'orego_booking', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => 'og_bstatus', 'meta_value' => 'pending' ) ) );
	if ( ! $n ) {
		return;
	}
	$bubble = ' <span class="awaiting-mod"><span class="pending-count">' . $n . '</span></span>';
	foreach ( $submenu['edit.php?post_type=orego_car'] ?? array() as $k => $item ) {
		if ( 'edit.php?post_type=orego_booking' === $item[2] ) {
			$submenu['edit.php?post_type=orego_car'][ $k ][0] .= $bubble; // phpcs:ignore
		}
	}
	foreach ( $menu as $k => $item ) {
		if ( 'edit.php?post_type=orego_car' === ( $item[2] ?? '' ) ) {
			$menu[ $k ][0] .= $bubble; // phpcs:ignore
		}
	}
}, 999 );

/* Booking details screen */
add_action( 'add_meta_boxes_orego_booking', function () {
	remove_meta_box( 'submitdiv', 'orego_booking', 'side' );
	add_meta_box( 'orego_bk_details', 'تفاصيل الحجز', 'orego_bk_details_box', 'orego_booking', 'normal', 'high' );
	add_meta_box( 'orego_bk_status', 'الحالة', 'orego_bk_status_box', 'orego_booking', 'side', 'high' );
} );

function orego_bk_details_box( $post ) {
	$i    = orego_bk_info( $post->ID );
	$rows = array(
		'رقم الحجز'  => '#' . $post->ID,
		'السيارة'      => '<a href="' . esc_url( get_edit_post_link( $i['car'] ) ) . '">' . esc_html( get_the_title( $i['car'] ) ) . '</a>',
		'الاستلام'     => esc_html( orego_bk_date( $i['start'] ) . ' · ' . $i['ptime'] ) . '<br>' . esc_html( $i['pickup'] ),
		'الإرجاع'      => esc_html( orego_bk_date( $i['end'] ) . ' · ' . $i['rtime'] ) . '<br>' . esc_html( $i['dropoff'] ),
		'المدة'        => esc_html( $i['days'] . ' ' . ( $i['days'] >= 3 && $i['days'] <= 10 ? 'أيام' : 'يوم' ) . ' × ' . orego_bk_money( $i['pday'], $post->ID ) ),
		'الإجمالي'     => '<strong>' . esc_html( orego_bk_money( $i['total'], $post->ID ) ) . '</strong> (الدفع عند الاستلام)',
		'العميل'       => esc_html( $i['name'] ),
		'الهاتف'       => '<a dir="ltr" href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $i['phone'] ) ) . '">' . esc_html( $i['phone'] ) . '</a>',
		'البريد'        => '<a href="mailto:' . esc_attr( $i['email'] ) . '">' . esc_html( $i['email'] ) . '</a>',
		'ملاحظات العميل' => $i['notes'] ? nl2br( esc_html( $i['notes'] ) ) : '–',
	);
	echo '<table class="widefat striped" style="border:0"><tbody>';
	foreach ( $rows as $k => $v ) {
		echo '<tr><th style="width:150px;font-weight:600">' . esc_html( $k ) . '</th><td>' . $v . '</td></tr>'; // phpcs:ignore
	}
	echo '</tbody></table>';
}

function orego_bk_status_box( $post ) {
	wp_nonce_field( 'orego_bk_edit', 'orego_bk_edit_nonce' );
	$i = orego_bk_info( $post->ID );
	echo '<p>' . orego_bk_badge( $i['status'] ) . '</p>'; // phpcs:ignore
	echo '<p><label for="og_bstatus" style="font-weight:600">تغيير الحالة</label><br><select name="og_bstatus" id="og_bstatus" style="width:100%">';
	foreach ( orego_bk_statuses() as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $i['status'], $k, false ) . '>' . esc_html( $v[0] ) . '</option>';
	}
	echo '</select></p>';
	echo '<p><label for="og_admin_note" style="font-weight:600">رسالة للعميل (اختيارية)</label><textarea name="og_admin_note" id="og_admin_note" rows="3" style="width:100%" placeholder="تُضاف إلى بريد التأكيد أو الرفض">' . esc_textarea( $i['anote'] ) . '</textarea></p>';
	echo '<p><label><input type="checkbox" name="og_notify" value="1" checked> إرسال بريد للعميل عند تغيير الحالة</label></p>';
	submit_button( 'حفظ', 'primary large', 'save', false, array( 'style' => 'width:100%' ) );
}

add_action( 'save_post_orego_booking', function ( $id ) {
	if ( ! isset( $_POST['orego_bk_edit_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['orego_bk_edit_nonce'] ) ), 'orego_bk_edit' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	update_post_meta( $id, 'og_admin_note', sanitize_textarea_field( wp_unslash( $_POST['og_admin_note'] ?? '' ) ) );
	orego_bk_set_status( $id, sanitize_key( $_POST['og_bstatus'] ?? 'pending' ), ! empty( $_POST['og_notify'] ) );
} );
