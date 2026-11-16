<?php
/**
 * Exotic Cars Rental Pro: customer self-service and automated emails.
 * - "Manage booking" page (private link in every customer email): status, price, payments, cancel within the policy, ask for a change.
 * - Reminders (WP-Cron, hourly check): pick-up and return reminders the day before, daily summary for the admin.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Returns booking id when the link is valid, else 0. */
function orego_manage_auth( $id, $token ) {
	$id = absint( $id );
	if ( ! $id || 'orego_booking' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
		return 0;
	}
	$real = (string) get_post_meta( $id, 'og_token', true );
	return ( '' !== $real && is_string( $token ) && hash_equals( $real, $token ) ) ? $id : 0;
}

/** Can the customer still cancel online? Returns true or a reason. */
function orego_can_cancel( $id ) {
	$i = orego_bk_info( $id );
	if ( ! in_array( $i['status'], array( 'pending', 'confirmed' ), true ) ) {
		return 'This booking can no longer be changed online.';
	}
	if ( ! empty( orego_json_meta( $id, 'og_ho_out' ) ) ) {
		return 'The car has already been picked up.';
	}
	$hours = orego_rules()['cancel_hours'];
	if ( $hours <= 0 ) {
		return 'Online cancellation is not available. Please contact us.';
	}
	$pick = strtotime( $i['start'] . ' ' . ( $i['ptime'] ? $i['ptime'] : '10:00' ) . ':00 ' . wp_timezone_string() );
	if ( $pick - time() < $hours * HOUR_IN_SECONDS ) {
		return sprintf( 'Free online cancellation ends %d hours before pick-up. Please contact us.', $hours );
	}
	return true;
}

function orego_manage_render() {
	$id = orego_manage_auth( $_GET['manage'] ?? 0, isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '' );
	if ( ! $id ) {
		return '<div class="ogb"><div class="ogb-empty">' . orego_bk_ic( 'car', 40 ) . '<h1>Link not valid</h1><p>This booking link is incorrect or has expired. Use the link in your latest email, or contact us.</p><a class="ogb-btn" href="' . esc_url( home_url( '/' ) ) . '">Home</a></div></div>';
	}
	$i     = orego_bk_info( $id );
	$q     = orego_bk_quote( $id );
	$sum   = orego_pay_summary( $id );
	$car   = get_post( $i['car'] );
	$can   = orego_can_cancel( $id );
	$done  = sanitize_key( $_GET['done'] ?? '' );
	$st    = array( 'pending' => 'Waiting for confirmation', 'confirmed' => 'Confirmed', 'onroad' => 'On the road', 'declined' => 'Not confirmed', 'cancelled' => 'Cancelled', 'completed' => 'Car returned' );
	$date  = fn( $ymd ) => wp_date( 'l, F j, Y', strtotime( $ymd . ' 12:00:00' ) );
	$bring = array_filter( array_map( 'trim', explode( "\n", orego_rules()['bring'] ) ) );
	$post  = esc_url( admin_url( 'admin-post.php' ) );
	$token = (string) get_post_meta( $id, 'og_token', true );
	ob_start();
	?>
<div class="ogb ogmg">
	<div class="ogb-top"><h1>Your booking #<?php echo (int) $id; ?></h1></div>
	<?php if ( 'cancelled' === $done ) : ?><p class="ogmg-ok">Your booking has been cancelled. We’ve sent you a confirmation email.</p><?php endif; ?>
	<?php if ( 'sent' === $done ) : ?><p class="ogmg-ok">Thanks, your message has been sent. We’ll get back to you by email.</p><?php endif; ?>
	<?php if ( 'err' === $done ) : ?><p class="ogmg-err">That didn’t work. Please try again or contact us.</p><?php endif; ?>
	<div class="ogmg-grid">
		<section class="ogb-card">
			<div class="ogmg-head"><span class="ogmg-st is-<?php echo esc_attr( $i['status'] ); ?>"><?php echo esc_html( $st[ $i['status'] ] ?? $i['status'] ); ?></span></div>
			<?php if ( $car ) : ?><div class="ogb-car"><div class="ogb-car-img"><?php echo has_post_thumbnail( $car ) ? get_the_post_thumbnail( $car, 'medium', array( 'alt' => '' ) ) : ''; // phpcs:ignore ?></div><div><strong><?php echo esc_html( $car->post_title ); ?></strong></div></div><?php endif; ?>
			<dl class="ogb-review">
				<div><dt>Pick-up</dt><dd><?php echo esc_html( $date( $i['start'] ) . ' · ' . $i['ptime'] ); ?><small><?php echo esc_html( $i['pickup'] ); ?></small></dd></div>
				<div><dt>Return</dt><dd><?php echo esc_html( $date( $i['end'] ) . ' · ' . $i['rtime'] ); ?><small><?php echo esc_html( $i['dropoff'] ); ?></small></dd></div>
				<?php foreach ( $q['rows'] as $r ) : ?><div><dt><?php echo esc_html( $r['label'] ); ?></dt><dd dir="ltr"><?php echo esc_html( ( $r['amount'] < 0 ? '-' : '' ) . orego_bk_money( abs( $r['amount'] ), $id ) ); ?></dd></div><?php endforeach; ?>
				<?php foreach ( orego_json_meta( $id, 'og_charges' ) as $c ) : ?><div><dt><?php echo esc_html( $c['label'] ); ?></dt><dd dir="ltr"><?php echo esc_html( orego_bk_money( $c['amount'], $id ) ); ?></dd></div><?php endforeach; ?>
				<div><dt><b>Total</b></dt><dd dir="ltr"><b><?php echo esc_html( orego_bk_money( $sum['due'], $id ) ); ?></b><small><?php echo esc_html( $sum['paid'] > 0 ? 'Paid ' . orego_bk_money( $sum['paid'], $id ) . ( $sum['balance'] > 0 ? ' · ' . orego_bk_money( $sum['balance'], $id ) . ' left to pay' : '' ) : 'Pay at pick-up (cash or card)' ); ?></small></dd></div>
				<?php if ( $sum['dep']['amount'] > 0 ) : ?><div><dt>Security deposit</dt><dd dir="ltr"><?php echo esc_html( orego_bk_money( $sum['dep']['amount'], $id ) ); ?><small>Refundable, collected at pick-up</small></dd></div><?php endif; ?>
			</dl>
		</section>
		<aside>
			<?php if ( 'confirmed' === $i['status'] && $bring ) : ?>
			<section class="ogb-card"><h2>Bring with you</h2><ul class="ogmg-list"><?php foreach ( $bring as $b ) : ?><li><?php echo esc_html( $b ); ?></li><?php endforeach; ?></ul></section>
			<?php endif; ?>
			<?php if ( in_array( $i['status'], array( 'pending', 'confirmed' ), true ) ) : ?>
			<section class="ogb-card">
				<h2>Need a change?</h2>
				<form method="post" action="<?php echo $post; // phpcs:ignore ?>">
					<input type="hidden" name="action" value="orego_manage"><input type="hidden" name="do" value="change"><input type="hidden" name="id" value="<?php echo (int) $id; ?>"><input type="hidden" name="t" value="<?php echo esc_attr( $token ); ?>">
					<label class="ogb-field"><span>Tell us what you’d like to change (dates, times, place, car…)</span><textarea name="msg" rows="3" maxlength="1000" required></textarea></label>
					<button type="submit" class="ogb-btn">Send request</button>
				</form>
			</section>
			<section class="ogb-card">
				<h2>Cancel booking</h2>
				<?php if ( true === $can ) : ?>
				<p class="ogb-hint">Free cancellation until <?php echo (int) orego_rules()['cancel_hours']; ?> hours before pick-up.</p>
				<form method="post" action="<?php echo $post; // phpcs:ignore ?>" onsubmit="return confirm('Cancel this booking?')">
					<input type="hidden" name="action" value="orego_manage"><input type="hidden" name="do" value="cancel"><input type="hidden" name="id" value="<?php echo (int) $id; ?>"><input type="hidden" name="t" value="<?php echo esc_attr( $token ); ?>">
					<button type="submit" class="ogb-btn ogmg-cancel">Cancel my booking</button>
				</form>
				<?php else : ?>
				<p class="ogb-hint"><?php echo esc_html( $can ); ?></p>
				<?php endif; ?>
			</section>
			<?php endif; ?>
			<section class="ogb-card"><h2>Contact us</h2><p class="ogb-hint">Reply to any of our emails, or write to <a href="mailto:<?php echo esc_attr( get_option( 'admin_email' ) ); ?>?subject=<?php echo rawurlencode( 'Booking #' . $id ); ?>"><?php echo esc_html( get_option( 'admin_email' ) ); ?></a>.</p></section>
		</aside>
	</div>
</div>
<style>.ogmg-grid{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:20px;align-items:start}.ogmg aside{display:grid;gap:16px}.ogmg h2{margin:0 0 10px;font-size:18px}.ogmg-head{margin-bottom:12px}.ogmg-st{display:inline-block;padding:6px 12px;border-radius:999px;font-weight:700;font-size:13px;background:#F3F4F6;color:#4B5563}.ogmg-st.is-pending{background:#FEF3C7;color:#B45309}.ogmg-st.is-confirmed{background:#DCFCE7;color:#15803D}.ogmg-st.is-completed{background:#DBEAFE;color:#1D4ED8}.ogmg-st.is-onroad{background:#EDE9FE;color:#6D28D9}.ogmg-st.is-cancelled,.ogmg-st.is-declined{background:#FEE2E2;color:#B91C1C}.ogmg-ok,.ogmg-err{padding:12px 16px;border-radius:12px;margin:0 0 16px}.ogmg-ok{background:#DCFCE7;color:#14532D}.ogmg-err{background:#FEE2E2;color:#7F1D1D}.ogmg-list{margin:0;padding-inline-start:18px}.ogmg-list li{margin:4px 0}.ogmg textarea{width:100%;margin:6px 0 10px}.ogmg .ogb-btn{width:100%;justify-content:center}.ogmg-cancel{background:#FFFFFF!important;color:#B91C1C!important;border:1px solid #FCA5A5!important}@media (max-width:800px){.ogmg-grid{grid-template-columns:1fr}}</style>
	<?php
	return ob_get_clean();
}

/* Cancel / change requests from the manage page (works for logged-out customers; the private token is the check). */
add_action( 'admin_post_nopriv_orego_manage', 'orego_manage_handle' );
add_action( 'admin_post_orego_manage', 'orego_manage_handle' );
function orego_manage_handle() {
	$token = isset( $_POST['t'] ) ? sanitize_text_field( wp_unslash( $_POST['t'] ) ) : '';
	$id    = orego_manage_auth( $_POST['id'] ?? 0, $token );
	if ( ! $id ) {
		wp_die( 'This booking link is not valid.', '', array( 'response' => 403 ) );
	}
	$back = fn( $done ) => add_query_arg( 'done', $done, orego_manage_url( $id ) );
	$rk   = 'orego_mg_' . $id;
	$n    = (int) get_transient( $rk );
	if ( $n >= 6 ) {
		wp_safe_redirect( $back( 'err' ) );
		exit;
	}
	set_transient( $rk, $n + 1, DAY_IN_SECONDS );
	$i  = orego_bk_info( $id );
	$do = sanitize_key( $_POST['do'] ?? '' );
	if ( 'cancel' === $do ) {
		if ( true !== orego_can_cancel( $id ) ) {
			wp_safe_redirect( $back( 'err' ) );
			exit;
		}
		update_post_meta( $id, 'og_cancelled_by', 'customer' );
		orego_bk_set_status( $id, 'cancelled', true );
		wp_mail( get_option( 'admin_email' ), 'Booking #' . $id . ' cancelled by the customer', $i['name'] . " cancelled booking #" . $id . " online.\n\n" . implode( "\n", orego_bk_lines( $id ) ) . "\n\nOpen: " . admin_url( 'admin.php?page=orego-bookings&booking=' . $id ), array( 'Reply-To: ' . $i['email'] ) );
		wp_safe_redirect( $back( 'cancelled' ) );
		exit;
	}
	if ( 'change' === $do ) {
		$msg = mb_substr( sanitize_textarea_field( wp_unslash( $_POST['msg'] ?? '' ) ), 0, 1000 );
		if ( '' === trim( $msg ) ) {
			wp_safe_redirect( $back( 'err' ) );
			exit;
		}
		$log   = orego_json_meta( $id, 'og_requests' );
		$log[] = array( 'at' => current_time( 'mysql' ), 'msg' => $msg );
		orego_set_json_meta( $id, 'og_requests', $log );
		wp_mail( get_option( 'admin_email' ), 'Change request for booking #' . $id, $i['name'] . " asks for a change to booking #" . $id . ":\n\n" . $msg . "\n\n" . implode( "\n", orego_bk_lines( $id ) ) . "\n\nPhone: " . $i['phone'] . "\nOpen: " . admin_url( 'admin.php?page=orego-bookings&booking=' . $id ), array( 'Reply-To: ' . $i['email'] ) );
		wp_safe_redirect( $back( 'sent' ) );
		exit;
	}
	wp_safe_redirect( $back( 'err' ) );
	exit;
}

/* Change requests are listed on the booking screen. */
add_action( 'orego_booking_view_side', function ( $id ) {
	$req = orego_json_meta( $id, 'og_requests' );
	$by  = get_post_meta( $id, 'og_cancelled_by', true );
	if ( ! $req && ! $by ) {
		return;
	}
	echo '<section class="ogdx-card"><div class="ogdx-card-h"><h2>From the customer</h2></div>';
	if ( 'customer' === $by ) {
		echo '<p class="ogu-mute">Cancelled online by the customer.</p>';
	}
	foreach ( array_reverse( $req ) as $r ) {
		echo '<p class="ogdx-note"><small>' . esc_html( wp_date( 'M j, H:i', strtotime( get_gmt_from_date( $r['at'] ) . ' UTC' ) ) ) . '</small><br>' . nl2br( esc_html( $r['msg'] ) ) . '</p>';
	}
	echo '</section>';
} );

/* Manage link + useful info in every customer email. */
add_filter( 'orego_bk_mail_body', function ( $body, $id, $type ) {
	if ( in_array( $type, array( 'received', 'confirmed', 'pickup', 'return' ), true ) ) {
		$body .= "\n\nView or manage your booking: " . orego_manage_url( $id );
	}
	if ( 'confirmed' === $type ) {
		$bring = array_filter( array_map( 'trim', explode( "\n", orego_rules()['bring'] ) ) );
		if ( $bring ) {
			$body .= "\n\nPlease bring:\n- " . implode( "\n- ", $bring );
		}
	}
	return $body;
}, 10, 3 );

/* ------------------------------------------------------------------ */
/* Reminders                                                           */
/* ------------------------------------------------------------------ */

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'orego_hourly' ) ) {
		wp_schedule_event( time() + 120, 'hourly', 'orego_hourly' );
	}
} );

add_action( 'orego_hourly', 'orego_run_reminders' );

function orego_send_customer( $id, $subject, $intro, $type ) {
	if ( function_exists( 'orego_mail_send_booking' ) ) {
		return orego_mail_send_booking( $id, $type, $subject, $intro );
	}
	$i = orego_bk_info( $id );
	if ( ! is_email( $i['email'] ) ) {
		return false;
	}
	$body  = 'Hello ' . $i['name'] . ",\n\n" . $intro . "\n\n" . implode( "\n", orego_bk_lines( $id ) );
	$body  = apply_filters( 'orego_bk_mail_body', $body, $id, $type );
	$body .= "\n\nThe " . orego_brand() . " team\n" . home_url( '/' );
	return wp_mail( $i['email'], $subject, $body, array( 'Reply-To: ' . get_option( 'admin_email' ) ) );
}

/** Runs every hour. $dry = true lists what would be sent without sending. */
function orego_run_reminders( $dry = false ) {
	$rules    = orego_rules();
	$today    = current_time( 'Y-m-d' );
	$tomorrow = gmdate( 'Y-m-d', strtotime( $today . ' +1 day UTC' ) );
	$log      = array();
	$ids      = get_posts( array( 'post_type' => 'orego_booking', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => 'og_bstatus', 'meta_value' => array( 'confirmed', 'onroad' ), 'meta_compare' => 'IN' ) );
	foreach ( $ids as $id ) {
		$i = orego_bk_info( $id );
		if ( $rules['rem_pickup'] && $i['start'] === $tomorrow && ! get_post_meta( $id, 'og_rem_pickup', true ) ) {
			$bring = array_filter( array_map( 'trim', explode( "\n", $rules['bring'] ) ) );
			$log[] = 'pickup #' . $id;
			if ( ! $dry ) {
				orego_send_customer( $id, 'Reminder: your car is ready tomorrow (booking #' . $id . ')', 'This is a reminder that you pick up your car tomorrow at ' . $i['ptime'] . ' at ' . $i['pickup'] . '.' . ( $bring ? "\n\nPlease bring:\n- " . implode( "\n- ", $bring ) : '' ), 'pickup' );
				update_post_meta( $id, 'og_rem_pickup', current_time( 'mysql' ) );
			}
		}
		if ( $rules['rem_return'] && $i['end'] === $tomorrow && ! get_post_meta( $id, 'og_rem_return', true ) ) {
			$log[] = 'return #' . $id;
			if ( ! $dry ) {
				orego_send_customer( $id, 'Reminder: return tomorrow (booking #' . $id . ')', 'This is a reminder that your rental ends tomorrow. Please return the car at ' . $i['rtime'] . ' at ' . $i['dropoff'] . ', with the same fuel level as at pick-up.', 'return' );
				update_post_meta( $id, 'og_rem_return', current_time( 'mysql' ) );
			}
		}
	}
	if ( $rules['rem_summary'] && (int) current_time( 'G' ) >= $rules['summary_hour'] && get_option( 'orego_last_summary' ) !== $today ) {
		$log[] = 'summary';
		if ( ! $dry ) {
			update_option( 'orego_last_summary', $today, false );
			$text = orego_daily_summary_text();
			if ( $text ) {
				wp_mail( get_option( 'admin_email' ), orego_brand() . ' today: ' . wp_date( 'l, M j' ), $text );
			}
		}
	}
	return $log;
}

/** Text of the admin's daily summary (empty when there is nothing to report). */
function orego_daily_summary_text() {
	$today = current_time( 'Y-m-d' );
	$all   = array();
	foreach ( get_posts( array( 'post_type' => 'orego_booking', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
		$all[ $id ] = orego_bk_info( $id );
	}
	$line = fn( $id, $b ) => '#' . $id . ' ' . get_the_title( $b['car'] ) . ' - ' . $b['name'] . ' (' . $b['phone'] . ')';
	$sec  = array();
	$pick = array_filter( $all, fn( $b ) => 'confirmed' === $b['status'] && $b['start'] === $today );
	$ret  = array_filter( $all, fn( $b ) => in_array( $b['status'], array( 'confirmed', 'onroad' ), true ) && $b['end'] === $today );
	$late = array_filter( $all, fn( $b ) => in_array( $b['status'], array( 'confirmed', 'onroad' ), true ) && $b['end'] < $today );
	$pend = array_filter( $all, fn( $b ) => 'pending' === $b['status'] && $b['start'] >= $today );
	$old  = array_filter( $all, fn( $b ) => 'pending' === $b['status'] && $b['start'] < $today );
	if ( $pick ) {
		$sec[] = "PICK-UPS TODAY\n" . implode( "\n", array_map( fn( $id ) => '- ' . $line( $id, $all[ $id ] ) . ' at ' . $all[ $id ]['ptime'] . ', ' . $all[ $id ]['pickup'], array_keys( $pick ) ) );
	}
	if ( $ret ) {
		$sec[] = "RETURNS TODAY\n" . implode( "\n", array_map( fn( $id ) => '- ' . $line( $id, $all[ $id ] ) . ' at ' . $all[ $id ]['rtime'] . ', ' . $all[ $id ]['dropoff'], array_keys( $ret ) ) );
	}
	if ( $late ) {
		$sec[] = "RENTALS PAST THEIR RETURN DATE (record the return or mark completed)\n" . implode( "\n", array_map( fn( $id ) => '- ' . $line( $id, $all[ $id ] ) . ', due ' . $all[ $id ]['end'], array_keys( $late ) ) );
	}
	if ( $pend ) {
		$sec[] = "REQUESTS WAITING FOR YOUR ANSWER\n" . implode( "\n", array_map( fn( $id ) => '- ' . $line( $id, $all[ $id ] ) . ', from ' . $all[ $id ]['start'], array_keys( $pend ) ) );
	}
	if ( $old ) {
		$sec[] = "UNANSWERED REQUESTS WHOSE PICK-UP DATE HAS PASSED (decline them to tidy up)\n" . implode( "\n", array_map( fn( $id ) => '- ' . $line( $id, $all[ $id ] ), array_keys( $old ) ) );
	}
	if ( function_exists( 'orego_fleet_alerts' ) && ( $a = orego_fleet_alerts() ) ) {
		$sec[] = "MAINTENANCE\n" . implode( "\n", array_map( fn( $x ) => '- ' . get_the_title( $x['car'] ) . ': ' . $x['text'], $a ) );
	}
	if ( ! $sec ) {
		return '';
	}
	return "Good morning,\n\nHere is your day at " . orego_brand() . ".\n\n" . implode( "\n\n", $sec ) . "\n\nOpen the dashboard: " . admin_url( 'admin.php?page=orego-dashboard' );
}

/* Private booking pages are never indexed. */
add_action( 'wp_head', function () {
	if ( isset( $_GET['manage'] ) ) {
		echo '<meta name="robots" content="noindex, nofollow">' . "\n";
	}
}, 1 );
