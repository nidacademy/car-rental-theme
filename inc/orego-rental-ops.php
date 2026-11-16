<?php
/**
 * Exotic Cars Rental Pro: payments, security deposit, extra charges and handover (pick-up / return) records.
 * Shown on the booking detail screen (Your Cars -> Bookings -> a booking).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Data                                                                */
/* ------------------------------------------------------------------ */

function orego_json_meta( $id, $key ) {
	$v = json_decode( (string) get_post_meta( $id, $key, true ), true );
	return is_array( $v ) ? $v : array();
}

function orego_set_json_meta( $id, $key, $val ) {
	update_post_meta( $id, $key, wp_slash( wp_json_encode( $val ) ) );
}

function orego_pay_methods() {
	return array( 'cash' => 'Cash', 'card' => 'Card', 'transfer' => 'Bank transfer', 'other' => 'Other' );
}

function orego_fuel_levels() {
	return array( '0' => 'Empty', '1' => '1/4', '2' => '1/2', '3' => '3/4', '4' => 'Full' );
}

/** Money summary of a booking. */
function orego_pay_summary( $id ) {
	$total   = (float) get_post_meta( $id, 'og_total', true );
	$charges = array_sum( array_map( fn( $c ) => (float) $c['amount'], orego_json_meta( $id, 'og_charges' ) ) );
	$paid    = 0;
	foreach ( orego_json_meta( $id, 'og_payments' ) as $p ) {
		$paid += ( 'refund' === ( $p['type'] ?? '' ) ? -1 : 1 ) * (float) $p['amount'];
	}
	$due     = round( $total + $charges, 2 );
	$paid    = round( $paid, 4 );
	$balance = round( $due - $paid, 4 );
	if ( $paid <= 0 ) {
		$state = 'unpaid';
		$label = 'Not paid yet (pay at pick-up)';
	} elseif ( $balance > 0.009 ) {
		$state = 'partial';
		$label = 'Partly paid, ' . orego_bk_money( $balance, $id ) . ' left';
	} elseif ( $balance < -0.009 ) {
		$state = 'over';
		$label = 'Overpaid by ' . orego_bk_money( -$balance, $id );
	} else {
		$state = 'paid';
		$label = 'Paid in full';
	}
	$dep = array(
		'amount' => (float) get_post_meta( $id, 'og_deposit', true ),
		'status' => (string) get_post_meta( $id, 'og_dep_status', true ),
		'kept'   => (float) get_post_meta( $id, 'og_dep_kept', true ),
	);
	return compact( 'total', 'charges', 'due', 'paid', 'balance', 'state', 'label', 'dep' );
}

function orego_dep_label( $s ) {
	$m = array(
		''         => 'Not collected yet',
		'held'     => 'Held',
		'returned' => 'Returned in full',
		'partial'  => 'Partly kept',
		'kept'     => 'Kept in full',
	);
	return $m[ $s ] ?? $m[''];
}

/* ------------------------------------------------------------------ */
/* Bill on the booking screen                                          */
/* ------------------------------------------------------------------ */

function orego_bk_bill_html( $id ) {
	$q   = function_exists( 'orego_bk_quote' ) ? orego_bk_quote( $id ) : null;
	$sum = orego_pay_summary( $id );
	$h   = '<dl class="ogu-bill">';
	if ( $q ) {
		foreach ( $q['rows'] as $r ) {
			$h .= '<div><dt>' . esc_html( $r['label'] ) . '</dt><dd>' . esc_html( ( $r['amount'] < 0 ? '-' : '' ) . orego_bk_money( abs( $r['amount'] ), $id ) ) . '</dd></div>';
		}
	}
	foreach ( orego_json_meta( $id, 'og_charges' ) as $c ) {
		$h .= '<div><dt>' . esc_html( $c['label'] ) . ' <small>(after return)</small></dt><dd>' . esc_html( orego_bk_money( $c['amount'], $id ) ) . '</dd></div>';
	}
	$h .= '<div class="is-total"><dt>Total' . ( $sum['charges'] > 0 ? ' with charges' : '' ) . '</dt><dd>' . esc_html( orego_bk_money( $sum['due'], $id ) ) . '</dd></div>';
	if ( $sum['dep']['amount'] > 0 ) {
		$h .= '<div><dt>Security deposit (refundable)</dt><dd>' . esc_html( orego_bk_money( $sum['dep']['amount'], $id ) ) . '</dd></div>';
	}
	return $h . '</dl>';
}

/* ------------------------------------------------------------------ */
/* Panels on the booking screen                                        */
/* ------------------------------------------------------------------ */

add_action( 'orego_booking_view_main', function ( $id, $b ) {
	$sum      = orego_pay_summary( $id );
	$pays     = orego_json_meta( $id, 'og_payments' );
	$charges  = orego_json_meta( $id, 'og_charges' );
	$out      = orego_json_meta( $id, 'og_ho_out' );
	$in       = orego_json_meta( $id, 'og_ho_in' );
	$post_url = esc_url( admin_url( 'admin-post.php' ) );
	$today    = current_time( 'Y-m-d' );
	$now      = current_time( 'Y-m-d\TH:i' );
	$fuel     = orego_fuel_levels();
	$car_km   = (int) get_post_meta( $b['car'], 'og_odometer', true );
	$per_day  = (int) get_post_meta( $b['car'], 'og_distance', true );
	$per_day  = $per_day ? $per_day : 200;
	$allowed  = $per_day * max( 1, (int) $b['days'] );
	$fields   = function ( $act ) use ( $id ) {
		return '<input type="hidden" name="action" value="' . esc_attr( $act ) . '"><input type="hidden" name="id" value="' . (int) $id . '">' . wp_nonce_field( $act . '_' . $id, '_wpnonce', true, false );
	};
	?>
	<section class="ogdx-card ogo" id="payments">
		<div class="ogdx-card-h"><h2>Payments &amp; deposit</h2><i class="ogo-pill is-<?php echo esc_attr( $sum['state'] ); ?>"><?php echo esc_html( 'paid' === $sum['state'] ? 'Paid' : ( 'partial' === $sum['state'] ? 'Partly paid' : ( 'over' === $sum['state'] ? 'Overpaid' : 'Unpaid' ) ) ); ?></i></div>
		<div class="ogo-sum">
			<div><small>To pay</small><b><?php echo esc_html( orego_bk_money( $sum['due'], $id ) ); ?></b></div>
			<div><small>Paid</small><b><?php echo esc_html( orego_bk_money( $sum['paid'], $id ) ); ?></b></div>
			<div><small>Balance</small><b class="<?php echo $sum['balance'] > 0.009 ? 'is-due' : ''; ?>"><?php echo esc_html( orego_bk_money( $sum['balance'], $id ) ); ?></b></div>
			<div><small>Deposit</small><b><?php echo esc_html( $sum['dep']['amount'] > 0 ? orego_bk_money( $sum['dep']['amount'], $id ) : '–' ); ?></b><em><?php echo esc_html( $sum['dep']['amount'] > 0 ? orego_dep_label( $sum['dep']['status'] ) : 'No deposit' ); ?></em></div>
		</div>
		<?php if ( $pays ) : ?>
		<table class="ogo-t"><thead><tr><th>Date</th><th>Type</th><th>Method</th><th>Note</th><th class="is-r">Amount</th><th></th></tr></thead><tbody>
		<?php foreach ( $pays as $p ) : ?>
			<tr><td><?php echo esc_html( wp_date( 'M j, Y', strtotime( $p['date'] . ' 12:00:00' ) ) ); ?></td><td><?php echo esc_html( 'refund' === $p['type'] ? 'Refund' : 'Payment' ); ?></td><td><?php echo esc_html( orego_pay_methods()[ $p['method'] ] ?? $p['method'] ); ?></td><td><?php echo esc_html( $p['note'] ); ?></td><td class="is-r"><?php echo esc_html( ( 'refund' === $p['type'] ? '-' : '' ) . orego_bk_money( $p['amount'], $id ) ); ?></td>
			<td class="is-r"><form method="post" action="<?php echo $post_url; // phpcs:ignore ?>" onsubmit="return confirm('Remove this payment?')"><?php echo $fields( 'orego_pay_del' ); // phpcs:ignore ?><input type="hidden" name="pid" value="<?php echo esc_attr( $p['id'] ); ?>"><button class="ogo-x" type="submit" aria-label="Remove">×</button></form></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<?php endif; ?>
		<form class="ogo-row" method="post" action="<?php echo $post_url; // phpcs:ignore ?>">
			<?php echo $fields( 'orego_pay_add' ); // phpcs:ignore ?>
			<label><span>Record</span><select name="type"><option value="payment">Payment</option><option value="refund">Refund</option></select></label>
			<label><span>Amount (<?php echo esc_html( orego_bk_cur( $id )[0] ); ?>)</span><input type="number" name="amount" min="0.01" step="0.01" required value="<?php echo esc_attr( $sum['balance'] > 0 ? round( $sum['balance'] * orego_bk_cur( $id )[1], 2 ) : '' ); ?>"></label>
			<label><span>Method</span><select name="method"><?php foreach ( orego_pay_methods() as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label>
			<label><span>Date</span><input type="date" name="date" value="<?php echo esc_attr( $today ); ?>" max="<?php echo esc_attr( $today ); ?>"></label>
			<label class="is-wide"><span>Note</span><input type="text" name="note" maxlength="200" placeholder="Optional, e.g. receipt number"></label>
			<button type="submit" class="ogdx-btn">Add</button>
		</form>
		<?php if ( $sum['dep']['amount'] > 0 ) : ?>
		<h3 class="ogu-h3">Security deposit</h3>
		<form class="ogo-row" method="post" action="<?php echo $post_url; // phpcs:ignore ?>">
			<?php echo $fields( 'orego_dep_save' ); // phpcs:ignore ?>
			<label><span>Status</span><select name="dep_status"><?php foreach ( array( '', 'held', 'returned', 'partial', 'kept' ) as $k ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $sum['dep']['status'], $k ); ?>><?php echo esc_html( orego_dep_label( $k ) ); ?></option><?php endforeach; ?></select></label>
			<label><span>Amount kept (<?php echo esc_html( orego_bk_cur( $id )[0] ); ?>)</span><input type="number" name="dep_kept" min="0" step="0.01" max="<?php echo esc_attr( round( $sum['dep']['amount'] * orego_bk_cur( $id )[1], 2 ) ); ?>" value="<?php echo esc_attr( $sum['dep']['kept'] ? round( $sum['dep']['kept'] * orego_bk_cur( $id )[1], 2 ) : '' ); ?>" placeholder="If partly kept"></label>
			<label><span>Method</span><select name="dep_method"><?php foreach ( orego_pay_methods() as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( get_post_meta( $id, 'og_dep_method', true ), $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label>
			<label class="is-wide"><span>Note</span><input type="text" name="dep_note" maxlength="200" value="<?php echo esc_attr( get_post_meta( $id, 'og_dep_note', true ) ); ?>" placeholder="e.g. card pre-authorisation, reason kept"></label>
			<button type="submit" class="ogdx-btn is-soft">Save deposit</button>
		</form>
		<?php endif; ?>
	</section>

	<section class="ogdx-card ogo" id="handover">
		<div class="ogdx-card-h"><h2>Handover</h2><i class="ogo-pill <?php echo $in ? 'is-paid' : ( $out ? 'is-partial' : '' ); ?>"><?php echo esc_html( 'completed' === $b['status'] ? 'Returned' : ( $out || 'onroad' === $b['status'] ? 'With customer' : 'Not handed over' ) ); ?></i></div>
		<div class="ogo-ho">
			<form method="post" enctype="multipart/form-data" action="<?php echo $post_url; // phpcs:ignore ?>">
				<?php echo $fields( 'orego_ho_save' ); // phpcs:ignore ?><input type="hidden" name="which" value="out">
				<h3>Pick-up</h3>
				<label><span>Date &amp; time</span><input type="datetime-local" name="at" value="<?php echo esc_attr( $out['at'] ?? $now ); ?>"></label>
				<label><span>Odometer (km)</span><input type="number" name="km" min="0" value="<?php echo esc_attr( $out['km'] ?? ( $car_km ? $car_km : '' ) ); ?>"></label>
				<label><span>Fuel / charge</span><select name="fuel"><?php foreach ( $fuel as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( (string) ( $out['fuel'] ?? '4' ), (string) $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label>
				<label><span>Condition &amp; existing damage</span><textarea name="notes" rows="3" placeholder="Scratches, dents, interior, accessories handed over"><?php echo esc_textarea( $out['notes'] ?? '' ); ?></textarea></label>
				<?php do_action( 'orego_handover_docs', $id, $out ); ?>
				<button type="submit" class="ogdx-btn is-soft"><?php echo $out ? 'Update pick-up' : 'Save pick-up'; ?></button>
				<?php if ( ! empty( $out['by'] ) ) : ?><p class="ogu-mute">Saved by <?php echo esc_html( $out['by'] ); ?></p><?php endif; ?>
			</form>
		</div>
		<h3 class="ogu-h3">Extra charges after return</h3>
		<?php if ( $charges ) : ?>
		<table class="ogo-t"><tbody>
		<?php foreach ( $charges as $c ) : ?>
			<tr><td><?php echo esc_html( $c['label'] ); ?></td><td class="is-r"><?php echo esc_html( orego_bk_money( $c['amount'], $id ) ); ?></td><td class="is-r"><form method="post" action="<?php echo $post_url; // phpcs:ignore ?>" onsubmit="return confirm('Remove this charge?')"><?php echo $fields( 'orego_charge_del' ); // phpcs:ignore ?><input type="hidden" name="cid" value="<?php echo esc_attr( $c['id'] ); ?>"><button class="ogo-x" type="submit" aria-label="Remove">×</button></form></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<?php endif; ?>
		<form class="ogo-row" method="post" action="<?php echo $post_url; // phpcs:ignore ?>">
			<?php echo $fields( 'orego_charge_add' ); // phpcs:ignore ?>
			<label class="is-wide"><span>Charge</span><input type="text" name="label" maxlength="120" required list="ogo-charge-types" placeholder="e.g. Fuel refill, Cleaning, Late return, Extra miles, Damage"></label>
			<datalist id="ogo-charge-types"><option value="Fuel refill"><option value="Cleaning fee"><option value="Late return"><option value="Extra miles"><option value="Damage"><option value="Traffic fine"></datalist>
			<label><span>Amount (<?php echo esc_html( orego_bk_cur( $id )[0] ); ?>)</span><input type="number" name="amount" min="0.01" step="0.01" required></label>
			<label class="ogu-check is-wide"><input type="checkbox" name="notify" value="1" checked> Email the customer the charges and what is left to pay</label>
			<button type="submit" class="ogdx-btn is-soft">Add charge</button>
		</form>
	</section>
	<?php
}, 10, 2 );

/* ------------------------------------------------------------------ */
/* Save handlers                                                       */
/* ------------------------------------------------------------------ */

function orego_ops_guard( $act ) {
	$id = absint( $_POST['id'] ?? 0 );
	check_admin_referer( $act . '_' . $id );
	if ( 'orego_booking' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Not allowed.' );
	}
	return $id;
}

function orego_ops_back( $id, $anchor, $msg = 'saved:1' ) {
	$url = function_exists( 'orego_ui_url' ) ? orego_ui_url( 'orego-bookings', array( 'booking' => $id, 'og_done' => $msg ) ) : admin_url( 'post.php?post=' . $id . '&action=edit' );
	wp_safe_redirect( $url . '#' . $anchor );
	exit;
}

add_action( 'admin_post_orego_pay_add', function () {
	$id  = orego_ops_guard( 'orego_pay_add' );
	$amt = orego_bk_to_usd( round( (float) ( $_POST['amount'] ?? 0 ), 2 ), $id );
	if ( $amt > 0 ) {
		$date   = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
		$list   = orego_json_meta( $id, 'og_payments' );
		$list[] = array(
			'id'     => substr( md5( uniqid( '', true ) ), 0, 10 ),
			'type'   => 'refund' === ( $_POST['type'] ?? '' ) ? 'refund' : 'payment',
			'amount' => $amt,
			'method' => array_key_exists( $_POST['method'] ?? '', orego_pay_methods() ) ? sanitize_key( $_POST['method'] ) : 'other',
			'date'   => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : current_time( 'Y-m-d' ),
			'note'   => mb_substr( sanitize_text_field( wp_unslash( $_POST['note'] ?? '' ) ), 0, 200 ),
			'by'     => wp_get_current_user()->display_name,
			'at'     => current_time( 'mysql' ),
		);
		orego_set_json_meta( $id, 'og_payments', $list );
	}
	orego_ops_back( $id, 'payments' );
} );

add_action( 'admin_post_orego_pay_del', function () {
	$id  = orego_ops_guard( 'orego_pay_del' );
	$pid = sanitize_key( $_POST['pid'] ?? '' );
	orego_set_json_meta( $id, 'og_payments', array_values( array_filter( orego_json_meta( $id, 'og_payments' ), fn( $p ) => $p['id'] !== $pid ) ) );
	orego_ops_back( $id, 'payments' );
} );

add_action( 'admin_post_orego_dep_save', function () {
	$id = orego_ops_guard( 'orego_dep_save' );
	$st = sanitize_key( $_POST['dep_status'] ?? '' );
	$st = in_array( $st, array( '', 'held', 'returned', 'partial', 'kept' ), true ) ? $st : '';
	$dep = (float) get_post_meta( $id, 'og_deposit', true );
	$kept = 'kept' === $st ? $dep : ( 'partial' === $st ? min( $dep, max( 0, orego_bk_to_usd( round( (float) ( $_POST['dep_kept'] ?? 0 ), 2 ), $id ) ) ) : 0 );
	update_post_meta( $id, 'og_dep_status', $st );
	update_post_meta( $id, 'og_dep_kept', $kept );
	update_post_meta( $id, 'og_dep_method', sanitize_key( $_POST['dep_method'] ?? '' ) );
	update_post_meta( $id, 'og_dep_note', mb_substr( sanitize_text_field( wp_unslash( $_POST['dep_note'] ?? '' ) ), 0, 200 ) );
	update_post_meta( $id, 'og_dep_date', current_time( 'Y-m-d' ) );
	orego_ops_back( $id, 'payments' );
} );

add_action( 'admin_post_orego_charge_add', function () {
	$id  = orego_ops_guard( 'orego_charge_add' );
	$amt = orego_bk_to_usd( round( (float) ( $_POST['amount'] ?? 0 ), 2 ), $id );
	$lbl = mb_substr( sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ), 0, 120 );
	if ( $amt > 0 && '' !== $lbl ) {
		$list   = orego_json_meta( $id, 'og_charges' );
		$list[] = array( 'id' => substr( md5( uniqid( '', true ) ), 0, 10 ), 'label' => $lbl, 'amount' => $amt, 'date' => current_time( 'Y-m-d' ) );
		orego_set_json_meta( $id, 'og_charges', $list );
		if ( ! empty( $_POST['notify'] ) && function_exists( 'orego_mail_send_booking' ) ) {
			orego_mail_send_booking( $id, 'fees' ); // "Extra charges for your booking"
		}
	}
	orego_ops_back( $id, 'handover' );
} );

add_action( 'admin_post_orego_charge_del', function () {
	$id  = orego_ops_guard( 'orego_charge_del' );
	$cid = sanitize_key( $_POST['cid'] ?? '' );
	orego_set_json_meta( $id, 'og_charges', array_values( array_filter( orego_json_meta( $id, 'og_charges' ), fn( $c ) => $c['id'] !== $cid ) ) );
	orego_ops_back( $id, 'handover' );
} );

add_action( 'admin_post_orego_ho_save', function () {
	$id    = orego_ops_guard( 'orego_ho_save' );
	$which = 'in' === ( $_POST['which'] ?? '' ) ? 'in' : 'out';
	$at    = sanitize_text_field( wp_unslash( $_POST['at'] ?? '' ) );
	$fuel  = sanitize_text_field( wp_unslash( $_POST['fuel'] ?? '4' ) );
	$rec   = array(
		'at'    => preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $at ) ? $at : current_time( 'Y-m-d\TH:i' ),
		'km'    => '' === trim( (string) ( $_POST['km'] ?? '' ) ) ? '' : max( 0, (int) $_POST['km'] ),
		'fuel'  => array_key_exists( $fuel, orego_fuel_levels() ) ? $fuel : '4',
		'notes' => mb_substr( sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ), 0, 2000 ),
		'id_type' => 'passport' === ( $_POST['id_type'] ?? '' ) ? 'passport' : 'id',
		'id_no'   => mb_substr( sanitize_text_field( wp_unslash( $_POST['id_no'] ?? '' ) ), 0, 40 ),
		'lic_no'  => mb_substr( sanitize_text_field( wp_unslash( $_POST['lic_no'] ?? '' ) ), 0, 40 ),
		'lic_exp' => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $_POST['lic_exp'] ?? '' ) ) ? $_POST['lic_exp'] : '',
		'by'    => wp_get_current_user()->display_name,
	);
	orego_set_json_meta( $id, 'og_ho_' . $which, $rec );
	do_action( 'orego_handover_saved', $id, $which ); // customer documents (orego-pro-handover.php)
	$car = (int) get_post_meta( $id, 'og_car', true );
	if ( '' !== $rec['km'] && $car && (int) $rec['km'] > (int) get_post_meta( $car, 'og_odometer', true ) ) {
		update_post_meta( $car, 'og_odometer', (int) $rec['km'] ); // keeps the car's mileage current for service reminders
	}
	$st = get_post_meta( $id, 'og_bstatus', true );
	// Saving the pick-up only stores the details: the owner changes the booking status by hand.
	if ( 'in' === $which && ! empty( $_POST['complete'] ) && in_array( $st, array( 'confirmed', 'onroad', 'pending' ), true ) ) {
		orego_bk_set_status( $id, 'completed' );
	}
	orego_ops_back( $id, 'handover' );
} );

/* ------------------------------------------------------------------ */
/* Styles for the panels                                               */
/* ------------------------------------------------------------------ */

add_action( 'admin_head', function () {
	if ( empty( $_GET['page'] ) || 'orego-bookings' !== $_GET['page'] || empty( $_GET['booking'] ) ) {
		return;
	}
	?>
	<style>
	.ogo{margin-top:18px}.ogo-pill{font-style:normal;font-size:12px;font-weight:700;padding:4px 10px;border-radius:999px;background:#F3F4F6;color:#4B5563}.ogo-pill.is-paid{background:#DCFCE7;color:#15803D}.ogo-pill.is-partial{background:#FEF3C7;color:#B45309}.ogo-pill.is-unpaid{background:#FEE2E2;color:#B91C1C}.ogo-pill.is-over{background:#DBEAFE;color:#1D4ED8}
	.ogo-sum{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:6px 0 14px}.ogo-sum>div{background:#F7F8FA;border-radius:12px;padding:12px 14px;display:flex;flex-direction:column;gap:2px}.ogo-sum small{color:#6B7280;font-size:12px}.ogo-sum b{font-size:18px}.ogo-sum em{font-style:normal;font-size:12px;color:#6B7280}.is-due{color:#B91C1C}
	.ogo-t{width:100%;border-collapse:collapse;margin:0 0 12px}.ogo-t th{font-size:12px;color:#6B7280;text-align:start;font-weight:600;padding:6px 8px;border-bottom:1px solid #EEF0F3}.ogo-t td{padding:8px;border-bottom:1px solid #F1F2F4}.ogo-t .is-r{text-align:end}.ogo-t form{margin:0}
	.ogo-x{border:0;background:none;color:#9CA3AF;font-size:18px;cursor:pointer;line-height:1}.ogo-x:hover{color:#B91C1C}
	.ogo-row{display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px;margin:0 0 6px}.ogo-row label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:#4B5563}.ogo-row label.is-wide{flex:1 1 220px}.ogo-row input,.ogo-row select{min-height:36px}
	.ogo-ho{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.ogo-ho form{display:flex;flex-direction:column;gap:10px;background:#F7F8FA;border-radius:12px;padding:14px}.ogo-ho h3{margin:0;font-size:15px}.ogo-ho label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:#4B5563}.ogo-ho label.ogu-check{flex-direction:row;align-items:center;gap:8px}.ogo-ho textarea{width:100%}.ogo-km{margin:0;font-size:13px}
	@media (max-width:900px){.ogo-sum{grid-template-columns:repeat(2,minmax(0,1fr))}.ogo-ho{grid-template-columns:1fr}}
	</style>
	<?php
} );
