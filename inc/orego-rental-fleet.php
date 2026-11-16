<?php
/**
 * Exotic Cars Rental Pro: vehicle maintenance and documents.
 * - Car box "Maintenance & documents": mileage, next service (date / miles), insurance and registration expiry.
 * - Your Cars -> Maintenance: fleet overview, alerts, service log; a record can take the car off the road for some days.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_maint_types() {
	return array( 'service' => 'Service', 'oil' => 'Oil change', 'tyres' => 'Tyres', 'brakes' => 'Brakes', 'repair' => 'Repair', 'inspection' => 'Inspection', 'cleaning' => 'Cleaning', 'other' => 'Other' );
}

function orego_car_maint( $car_id ) {
	$m   = fn( $k ) => get_post_meta( $car_id, $k, true );
	$log = json_decode( (string) $m( 'og_maint' ), true );
	$log = is_array( $log ) ? $log : array();
	usort( $log, fn( $a, $b ) => strcmp( $b['date'], $a['date'] ) );
	return array(
		'km'           => (int) $m( 'og_odometer' ),
		'service_date' => (string) $m( 'og_service_date' ),
		'service_km'   => (int) $m( 'og_service_km' ),
		'ins_exp'      => (string) $m( 'og_ins_exp' ),
		'reg_exp'      => (string) $m( 'og_reg_exp' ),
		'log'          => $log,
	);
}

/** Alerts for one car: overdue (red) or soon (amber). */
function orego_car_alerts( $car_id ) {
	$d     = orego_car_maint( $car_id );
	$today = current_time( 'Y-m-d' );
	$soon  = gmdate( 'Y-m-d', strtotime( $today . ' +14 days UTC' ) );
	$out   = array();
	$date  = function ( $ymd, $what ) use ( $today, $soon, &$out ) {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
			return;
		}
		if ( $ymd < $today ) {
			$out[] = array( 'level' => 'over', 'text' => $what . ' overdue since ' . wp_date( 'M j', strtotime( $ymd . ' 12:00:00' ) ) );
		} elseif ( $ymd <= $soon ) {
			$out[] = array( 'level' => 'soon', 'text' => $what . ' due ' . wp_date( 'M j', strtotime( $ymd . ' 12:00:00' ) ) );
		}
	};
	$date( $d['service_date'], 'Service' );
	$date( $d['ins_exp'], 'Insurance' );
	$date( $d['reg_exp'], 'Registration' );
	if ( $d['service_km'] && $d['km'] ) {
		if ( $d['km'] >= $d['service_km'] ) {
			$out[] = array( 'level' => 'over', 'text' => 'Service due: ' . number_format( $d['km'] ) . ' of ' . number_format( $d['service_km'] ) . ' miles' );
		} elseif ( $d['service_km'] - $d['km'] <= 500 ) {
			$out[] = array( 'level' => 'soon', 'text' => 'Service in ' . number_format( $d['service_km'] - $d['km'] ) . ' miles' );
		}
	}
	return $out;
}

function orego_fleet_alerts() {
	$all = array();
	foreach ( get_posts( array( 'post_type' => 'orego_car', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => -1, 'fields' => 'ids' ) ) as $c ) {
		foreach ( orego_car_alerts( $c ) as $a ) {
			$a['car'] = $c;
			$all[]    = $a;
		}
	}
	usort( $all, fn( $a, $b ) => ( 'over' === $b['level'] ) <=> ( 'over' === $a['level'] ) );
	return $all;
}

/* Maintenance records that take the car off the road block those days for bookings. */
add_filter( 'orego_blocked_ranges', function ( $ranges, $car_id ) {
	foreach ( orego_car_maint( $car_id )['log'] as $r ) {
		if ( ! empty( $r['from'] ) && ! empty( $r['to'] ) && $r['to'] >= $r['from'] ) {
			$ranges[] = array( $r['from'], gmdate( 'Y-m-d', strtotime( $r['to'] . ' 00:00:00 UTC' ) + DAY_IN_SECONDS ) ); // last day included
		}
	}
	return $ranges;
}, 10, 2 );

/* ------------------------------------------------------------------ */
/* Car box                                                             */
/* ------------------------------------------------------------------ */

add_action( 'add_meta_boxes_orego_car', function () {
	add_meta_box( 'orego_car_maint', 'Maintenance & documents', 'orego_car_maint_box', 'orego_car', 'side', 'default' );
} );

function orego_car_maint_box( $post ) {
	wp_nonce_field( 'orego_car_maint', 'orego_car_maint_nonce' );
	$d = orego_car_maint( $post->ID );
	$f = function ( $name, $label, $type, $val, $ph = '' ) {
		echo '<p><label for="' . esc_attr( $name ) . '" style="font-weight:600;display:block;margin-bottom:4px">' . esc_html( $label ) . '</label><input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $ph ) . '" style="width:100%"' . ( 'number' === $type ? ' min="0"' : '' ) . '></p>';
	};
	$f( 'og_odometer', 'Current mileage', 'number', $d['km'] ? $d['km'] : '', 'Updated at each return' );
	$f( 'og_service_date', 'Next service date', 'date', $d['service_date'] );
	$f( 'og_service_km', 'Next service at (miles)', 'number', $d['service_km'] ? $d['service_km'] : '' );
	$f( 'og_ins_exp', 'Insurance expires', 'date', $d['ins_exp'] );
	$f( 'og_reg_exp', 'Registration / inspection expires', 'date', $d['reg_exp'] );
	foreach ( orego_car_alerts( $post->ID ) as $a ) {
		echo '<p style="margin:4px 0;color:' . ( 'over' === $a['level'] ? '#B91C1C' : '#B45309' ) . '">● ' . esc_html( $a['text'] ) . '</p>';
	}
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=orego-maint&car=' . $post->ID ) ) . '">Service log (' . count( $d['log'] ) . ')</a></p>';
}

add_action( 'save_post_orego_car', function ( $post_id ) {
	if ( ! isset( $_POST['orego_car_maint_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['orego_car_maint_nonce'] ) ), 'orego_car_maint' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( 'og_odometer', 'og_service_km' ) as $k ) {
		$v = trim( (string) ( $_POST[ $k ] ?? '' ) );
		update_post_meta( $post_id, $k, '' === $v ? '' : (string) max( 0, (int) $v ) );
	}
	foreach ( array( 'og_service_date', 'og_ins_exp', 'og_reg_exp' ) as $k ) {
		$v = sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) );
		update_post_meta( $post_id, $k, preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '' );
	}
}, 20 );

/* ------------------------------------------------------------------ */
/* Your Cars -> Maintenance                                             */
/* ------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=orego_car', 'Maintenance', 'Maintenance', 'edit_posts', 'orego-maint', 'orego_maint_page' );
}, 30 );

add_filter( 'orego_app_menu', function ( $items ) {
	$n   = count( array_filter( orego_fleet_alerts(), fn( $a ) => 'over' === $a['level'] ) );
	$lbl = 'Maintenance' . ( $n ? ' <span class="awaiting-mod"><span class="pending-count">' . (int) $n . '</span></span>' : '' );
	// insert after Statistics
	$out = array();
	foreach ( $items as $it ) {
		$out[] = $it;
		if ( false !== strpos( (string) $it[2], 'tab=stats' ) ) {
			$out[] = array( $lbl, 'edit_posts', 'orego-maint', 'Maintenance' );
		}
	}
	if ( count( $out ) === count( $items ) ) {
		$out[] = array( $lbl, 'edit_posts', 'orego-maint', 'Maintenance' );
	}
	return $out;
}, 5 );

add_action( 'admin_post_orego_maint_add', function () {
	check_admin_referer( 'orego_maint_add' );
	$car = absint( $_POST['car'] ?? 0 );
	if ( 'orego_car' !== get_post_type( $car ) || ! current_user_can( 'edit_post', $car ) ) {
		wp_die( 'Not allowed.' );
	}
	$dt   = fn( $k ) => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $_POST[ $k ] ?? '' ) ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : '';
	$type = sanitize_key( $_POST['type'] ?? 'service' );
	$rec  = array(
		'id'    => substr( md5( uniqid( '', true ) ), 0, 10 ),
		'date'  => $dt( 'date' ) ? $dt( 'date' ) : current_time( 'Y-m-d' ),
		'type'  => array_key_exists( $type, orego_maint_types() ) ? $type : 'other',
		'km'    => '' === trim( (string) ( $_POST['km'] ?? '' ) ) ? '' : max( 0, (int) $_POST['km'] ),
		'cost'  => max( 0, round( (float) ( $_POST['cost'] ?? 0 ), 2 ) ),
		'notes' => mb_substr( sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ), 0, 1000 ),
		'from'  => $dt( 'from' ),
		'to'    => $dt( 'to' ) && $dt( 'to' ) >= $dt( 'from' ) ? $dt( 'to' ) : $dt( 'from' ),
		'reason' => $dt( 'from' ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) ), 0, 200 ) : '',
		'by'    => wp_get_current_user()->display_name,
	);
	$log   = orego_car_maint( $car )['log'];
	$log[] = $rec;
	update_post_meta( $car, 'og_maint', wp_slash( wp_json_encode( array_values( $log ) ) ) );
	if ( '' !== $rec['km'] && (int) $rec['km'] > (int) get_post_meta( $car, 'og_odometer', true ) ) {
		update_post_meta( $car, 'og_odometer', (int) $rec['km'] );
	}
	$nd = $dt( 'next_date' );
	if ( $nd ) {
		update_post_meta( $car, 'og_service_date', $nd );
	}
	if ( '' !== trim( (string) ( $_POST['next_km'] ?? '' ) ) ) {
		update_post_meta( $car, 'og_service_km', max( 0, (int) $_POST['next_km'] ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=orego-maint&car=' . $car . '&og_done=saved:1' ) );
	exit;
} );

add_action( 'admin_post_orego_maint_del', function () {
	$car = absint( $_POST['car'] ?? 0 );
	check_admin_referer( 'orego_maint_del_' . $car );
	if ( 'orego_car' !== get_post_type( $car ) || ! current_user_can( 'edit_post', $car ) ) {
		wp_die( 'Not allowed.' );
	}
	$rid = sanitize_key( $_POST['rid'] ?? '' );
	$log = array_values( array_filter( orego_car_maint( $car )['log'], fn( $r ) => $r['id'] !== $rid ) );
	update_post_meta( $car, 'og_maint', wp_slash( wp_json_encode( $log ) ) );
	wp_safe_redirect( admin_url( 'admin.php?page=orego-maint&car=' . $car ) );
	exit;
} );

/** Data for the record window (record + car facts). */
function orego_maint_rec_view( $r, $types ) {
	static $cache = array();
	$c = (int) $r['car'];
	if ( ! isset( $cache[ $c ] ) ) {
		$d           = orego_car_maint( $c );
		$cache[ $c ] = array(
			'car_name' => get_the_title( $c ),
			'img'      => (string) get_the_post_thumbnail_url( $c, 'thumbnail' ),
			'km_now'   => $d['km'] ? number_format( $d['km'] ) . ' mi' : '',
			'next'     => trim( ( $d['service_date'] ? wp_date( 'M j, Y', strtotime( $d['service_date'] . ' 12:00:00' ) ) : '' ) . ( $d['service_km'] ? ' · ' . number_format( $d['service_km'] ) . ' mi' : '' ), ' ·' ),
			'count'    => count( $d['log'] ),
			'edit'     => (string) get_edit_post_link( $c, 'raw' ),
		);
	}
	return array_merge( $cache[ $c ], array(
		'car'       => $c,
		'id'        => $r['id'],
		'type'      => $r['type'],
		'type_name' => $types[ $r['type'] ] ?? $r['type'],
		'date'      => $r['date'],
		'date_h'    => wp_date( 'M j, Y', strtotime( $r['date'] . ' 12:00:00' ) ),
		'cost'      => $r['cost'],
		'notes'     => $r['notes'] ?? '',
		'from'      => $r['from'] ?? '',
		'to'        => $r['to'] ?? '',
		'reason'    => $r['reason'] ?? '',
		'by'        => $r['by'] ?? '',
		'edited'    => $r['edited'] ?? '',
	) );
}

add_action( 'admin_post_orego_maint_edit', function () {
	check_admin_referer( 'orego_maint_edit' );
	$car = absint( $_POST['car'] ?? 0 );
	if ( 'orego_car' !== get_post_type( $car ) || ! current_user_can( 'edit_post', $car ) ) {
		wp_die( 'Not allowed.' );
	}
	$rid   = sanitize_key( $_POST['rid'] ?? '' );
	$dt    = fn( $k ) => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $_POST[ $k ] ?? '' ) ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : '';
	$type  = sanitize_key( $_POST['type'] ?? 'service' );
	$off   = ! empty( $_POST['off'] ) && $dt( 'from' );
	$log   = orego_car_maint( $car )['log'];
	$found = false;
	foreach ( $log as &$r ) {
		if ( ( $r['id'] ?? '' ) !== $rid ) {
			continue;
		}
		$found       = true;
		$r['type']   = array_key_exists( $type, orego_maint_types() ) ? $type : 'other';
		$r['date']   = $dt( 'date' ) ? $dt( 'date' ) : $r['date'];
		$r['cost']   = max( 0, round( (float) ( $_POST['cost'] ?? 0 ), 2 ) );
		$r['notes']  = mb_substr( sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ), 0, 1000 );
		$r['from']   = $off ? $dt( 'from' ) : '';
		$r['to']     = $off ? ( $dt( 'to' ) && $dt( 'to' ) >= $dt( 'from' ) ? $dt( 'to' ) : $dt( 'from' ) ) : '';
		$r['reason'] = $off ? mb_substr( sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) ), 0, 200 ) : '';
		$r['edited'] = wp_date( 'M j, Y' ) . ' by ' . wp_get_current_user()->display_name;
	}
	unset( $r );
	if ( $found ) {
		update_post_meta( $car, 'og_maint', wp_slash( wp_json_encode( array_values( $log ) ) ) );
	}
	$back = absint( $_POST['back'] ?? 0 );
	wp_safe_redirect( admin_url( 'admin.php?page=orego-maint' . ( $back ? '&car=' . $back : '' ) . '&og_done=saved:1' ) );
	exit;
} );
function orego_maint_page() {
	$cars  = get_posts( array( 'post_type' => 'orego_car', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	$sel   = absint( $_GET['car'] ?? 0 );
	$today = current_time( 'Y-m-d' );
	$year  = substr( $today, 0, 4 );
	$types = orego_maint_types();
	$alert = orego_fleet_alerts();
	if ( function_exists( 'orego_ui_head' ) ) {
		orego_ui_head( 'Maintenance', 'Service and repairs for every car. Days a car is off the road can’t be booked.' );
	} else {
		echo '<div class="wrap"><h1>Maintenance</h1>';
	}
	?>
	<div class="ogm">
	<?php if ( $alert ) : ?>
	<section class="ogdx-card">
		<div class="ogdx-card-h"><h2>Needs attention</h2></div>
		<ul class="ogm-alerts">
		<?php foreach ( $alert as $a ) : ?><li class="is-<?php echo esc_attr( $a['level'] ); ?>"><a href="<?php echo esc_url( get_edit_post_link( $a['car'] ) ); ?>"><b><?php echo esc_html( get_the_title( $a['car'] ) ); ?></b><span><?php echo esc_html( $a['text'] ); ?></span></a></li><?php endforeach; ?>
		</ul>
	</section>
	<?php endif; ?>
	<section class="ogdx-card">
		<div class="ogdx-card-h"><h2>Fleet</h2></div>
		<div class="ogm-scroll"><table class="ogm-t ogm-fleet"><thead><tr><th>Car</th><th>Status</th><th>Last record</th><th class="is-r">Records</th><th class="is-r">Costs <?php echo esc_html( $year ); ?></th></tr></thead><tbody>
		<?php
		foreach ( $cars as $c ) :
			$d    = orego_car_maint( $c->ID );
			$last = $d['log'][0] ?? null;
			$cost = array_sum( array_map( fn( $r ) => substr( $r['date'], 0, 4 ) === $year ? (float) $r['cost'] : 0, $d['log'] ) );
			$now  = null;
			$next = null;
			foreach ( $d['log'] as $r ) {
				if ( empty( $r['from'] ) || empty( $r['to'] ) || $r['to'] < $today ) {
					continue;
				}
				if ( $r['from'] <= $today ) {
					$now = ( ! $now || $r['to'] > $now['to'] ) ? $r : $now;
				} elseif ( ! $next || $r['from'] < $next['from'] ) {
					$next = $r;
				}
			}
			$img = get_the_post_thumbnail_url( $c->ID, 'thumbnail' );
			$fmt = fn( $ymd ) => wp_date( 'M j', strtotime( $ymd . ' 12:00:00' ) );
			?>
			<tr class="<?php echo $sel === $c->ID ? 'is-sel' : ''; ?>">
			<td><a class="ogm-car" href="<?php echo esc_url( admin_url( 'admin.php?page=orego-maint&car=' . $c->ID ) ); ?>"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt=""><?php else : ?><span class="ogm-noimg"></span><?php endif; ?><b><?php echo esc_html( $c->post_title ); ?></b></a></td>
			<td>
			<?php if ( $now ) : ?>
				<span class="ogm-st is-off">Off the road until <?php echo esc_html( $fmt( $now['to'] ) ); ?></span><?php echo ! empty( $now['reason'] ) ? '<small class="ogm-why">' . esc_html( $now['reason'] ) . '</small>' : ''; ?>
			<?php elseif ( $next ) : ?>
				<span class="ogm-st is-soon">Off the road <?php echo esc_html( $fmt( $next['from'] ) . ( $next['to'] !== $next['from'] ? ' – ' . $fmt( $next['to'] ) : '' ) ); ?></span><?php echo ! empty( $next['reason'] ) ? '<small class="ogm-why">' . esc_html( $next['reason'] ) . '</small>' : ''; ?>
			<?php else : ?>
				<span class="ogm-st is-ok">Available</span>
			<?php endif; ?>
			</td>
			<td><?php if ( $last ) : ?><b class="ogm-lt"><?php echo esc_html( $types[ $last['type'] ] ?? $last['type'] ); ?></b><small class="ogm-why"><?php echo esc_html( wp_date( 'M j, Y', strtotime( $last['date'] . ' 12:00:00' ) ) . ( (float) $last['cost'] ? ' · ' . orego_dash_money( $last['cost'] ) : '' ) ); ?></small><?php else : ?><span class="ogm-mute">No records</span><?php endif; ?></td>
			<td class="is-r"><?php echo (int) count( $d['log'] ); ?></td>
			<td class="is-r"><b><?php echo esc_html( orego_dash_money( $cost ) ); ?></b></td>
			</tr>
		<?php endforeach; ?>
		</tbody></table></div>
		<p class="ogm-help">Click a car to see only its records. Days a car is off the road can’t be booked.</p>
	</section>
	<section class="ogdx-card ogm-add">
		<div class="ogdx-card-h"><h2>Add a record</h2></div>
		<form class="ogm-f2" id="ogm-f2" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="orego_maint_add"><?php wp_nonce_field( 'orego_maint_add' ); ?>
			<div class="ogm-grid">
				<label class="ogm-fld"><span>Car</span><select name="car" required><?php foreach ( $cars as $c ) : ?><option value="<?php echo (int) $c->ID; ?>" <?php selected( $sel, $c->ID ); ?>><?php echo esc_html( $c->post_title ); ?></option><?php endforeach; ?></select></label>
				<label class="ogm-fld"><span>Type</span><select name="type"><?php foreach ( $types as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label>
				<label class="ogm-fld"><span>Date</span><input type="date" name="date" value="<?php echo esc_attr( $today ); ?>" required></label>
				<label class="ogm-fld"><span>Cost</span><span class="ogm-money"><b>$</b><input type="number" name="cost" min="0" step="0.01" placeholder="0.00"></span></label>
				<label class="ogm-fld is-notes"><span>Notes</span><input type="text" name="notes" maxlength="1000" placeholder="Garage, parts, invoice number…"></label>
			</div>
			<div class="ogm-off" id="ogm-offbox">
				<label class="ogm-sw"><input type="checkbox" id="ogm-off-on"><i aria-hidden="true"></i><span><b>Off the road</b><small>The car can’t be booked on these days.</small></span></label>
				<div class="ogm-grid is-off" id="ogm-off" hidden>
					<label class="ogm-fld"><span>From</span><input type="date" name="from" id="ogm-from" min="<?php echo esc_attr( $today ); ?>" disabled></label>
					<label class="ogm-fld"><span>To</span><input type="date" name="to" id="ogm-to" min="<?php echo esc_attr( $today ); ?>" disabled></label>
					<label class="ogm-fld is-notes"><span>Reason</span><input type="text" name="reason" id="ogm-reason" maxlength="200" list="ogm-reasons" placeholder="e.g. Waiting for parts" disabled></label>
					<datalist id="ogm-reasons"><option value="In the garage"><option value="Waiting for parts"><option value="Accident repair"><option value="Inspection"><option value="Bodywork"></datalist>
				</div>
			</div>
			<div class="ogm-actions"><button type="submit" class="ogdx-btn">Add record</button></div>
		</form>
		<script>
		(function(){var on=document.getElementById('ogm-off-on'),box=document.getElementById('ogm-off'),wrap=document.getElementById('ogm-offbox'),f=document.getElementById('ogm-from'),t=document.getElementById('ogm-to'),r=document.getElementById('ogm-reason');if(!on)return;
		on.addEventListener('change',function(){var v=on.checked;box.hidden=!v;wrap.classList.toggle('is-on',v);[f,t,r].forEach(function(i){i.disabled=!v});f.required=v;t.required=v;if(v)f.focus()});
		f.addEventListener('change',function(){t.min=f.value||t.min;if(!t.value||t.value<f.value)t.value=f.value})})();
		</script>
	</section>
	<?php
	$show = $sel ? array( get_post( $sel ) ) : $cars;
	$rows = array();
	foreach ( $show as $c ) {
		if ( ! $c ) {
			continue;
		}
		foreach ( orego_car_maint( $c->ID )['log'] as $r ) {
			$r['car'] = $c->ID;
			$rows[]   = $r;
		}
	}
	usort( $rows, fn( $a, $b ) => strcmp( $b['date'], $a['date'] ) );
	?>
	<section class="ogdx-card">
		<div class="ogdx-card-h"><h2>Service log<?php echo $sel ? ': ' . esc_html( get_the_title( $sel ) ) : ''; ?></h2><?php if ( $sel ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=orego-maint' ) ); ?>">All cars</a><?php endif; ?></div>
		<?php if ( ! $rows ) : ?><p class="ogm-help">No records yet.</p><?php else : ?>
		<div class="ogm-scroll"><table class="ogm-t"><thead><tr><th>Date</th><?php if ( ! $sel ) : ?><th>Car</th><?php endif; ?><th>Type</th><th>Mileage</th><th>Off the road</th><th>Notes</th><th class="is-r">Cost</th><th></th></tr></thead><tbody>
		<?php foreach ( array_slice( $rows, 0, 200 ) as $r ) : ?>
			<tr><td><?php echo esc_html( wp_date( 'M j, Y', strtotime( $r['date'] . ' 12:00:00' ) ) ); ?></td><?php if ( ! $sel ) : ?><td><?php echo esc_html( get_the_title( $r['car'] ) ); ?></td><?php endif; ?><td><?php echo esc_html( $types[ $r['type'] ] ?? $r['type'] ); ?></td><td><?php echo '' !== $r['km'] ? esc_html( number_format( (int) $r['km'] ) ) : '–'; ?></td><td><?php echo $r['from'] ? esc_html( wp_date( 'M j', strtotime( $r['from'] . ' 12:00:00' ) ) . ( $r['to'] && $r['to'] !== $r['from'] ? ' – ' . wp_date( 'M j', strtotime( $r['to'] . ' 12:00:00' ) ) : '' ) ) . ( ! empty( $r['reason'] ) ? '<small class="ogm-why">' . esc_html( $r['reason'] ) . '</small>' : '' ) : '–'; ?></td><td><?php echo esc_html( $r['notes'] ); ?></td><td class="is-r"><?php echo esc_html( orego_dash_money( $r['cost'] ) ); ?></td>
			<td class="is-r"><div class="ogm-acts"><button type="button" class="ogm-view" data-rec="<?php echo esc_attr( wp_json_encode( orego_maint_rec_view( $r, $types ) ) ); ?>">View</button><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Delete this record?')"><input type="hidden" name="action" value="orego_maint_del"><input type="hidden" name="car" value="<?php echo (int) $r['car']; ?>"><input type="hidden" name="rid" value="<?php echo esc_attr( $r['id'] ); ?>"><?php wp_nonce_field( 'orego_maint_del_' . $r['car'] ); ?><button type="submit" class="ogm-x" aria-label="Delete">×</button></form></div></td></tr>
		<?php endforeach; ?>
		</tbody></table></div>
		<?php endif; ?>
	</section>
	</div>
	<dialog class="ogm-dlg" id="ogm-dlg" aria-labelledby="ogm-d-car">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ogm-dlg-f">
			<input type="hidden" name="action" value="orego_maint_edit"><?php wp_nonce_field( 'orego_maint_edit' ); ?>
			<input type="hidden" name="car" value=""><input type="hidden" name="rid" value=""><input type="hidden" name="back" value="<?php echo (int) $sel; ?>">
			<header class="ogm-dlg-h">
				<div class="ogm-dlg-car"><img id="ogm-d-img" alt="" hidden><div><b id="ogm-d-car"></b><small id="ogm-d-meta"></small></div></div>
				<button type="button" class="ogm-dlg-x" data-close aria-label="Close">&times;</button>
			</header>
			<div class="ogm-dlg-b">
				<div class="ogm-facts" id="ogm-d-facts"></div>
				<div class="ogm-grid is-dlg">
					<label class="ogm-fld"><span>Type</span><select name="type"><?php foreach ( $types as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label>
					<label class="ogm-fld"><span>Date</span><input type="date" name="date" required></label>
					<label class="ogm-fld"><span>Cost</span><span class="ogm-money"><b>$</b><input type="number" name="cost" min="0" step="0.01" placeholder="0.00"></span></label>
					<label class="ogm-fld is-notes"><span>Notes</span><input type="text" name="notes" maxlength="1000" placeholder="Garage, parts, invoice number…"></label>
				</div>
				<div class="ogm-off" id="ogm-e-box">
					<label class="ogm-sw"><input type="checkbox" name="off" value="1" id="ogm-e-on"><i aria-hidden="true"></i><span><b>Off the road</b><small>The car can’t be booked on these days.</small></span></label>
					<div class="ogm-grid is-off" id="ogm-e-off" hidden>
						<label class="ogm-fld"><span>From</span><input type="date" name="from" disabled></label>
						<label class="ogm-fld"><span>To</span><input type="date" name="to" disabled></label>
						<label class="ogm-fld is-notes"><span>Reason</span><input type="text" name="reason" maxlength="200" list="ogm-reasons" placeholder="e.g. Waiting for parts" disabled></label>
					</div>
				</div>
			</div>
			<footer class="ogm-dlg-ft"><small id="ogm-d-by"></small><span><button type="button" class="ogm-btn2" data-close>Cancel</button><button type="submit" class="ogdx-btn">Save changes</button></span></footer>
		</form>
	</dialog>
	<script>
	(function(){
	var d=document.getElementById('ogm-dlg');if(!d||!d.showModal)return;
	var F=d.querySelector('form'),E=function(n){return F.elements[n]},sw=document.getElementById('ogm-e-on'),off=document.getElementById('ogm-e-off'),box=document.getElementById('ogm-e-box');
	function setOff(v){sw.checked=v;off.hidden=!v;box.classList.toggle('is-on',v);['from','to','reason'].forEach(function(n){E(n).disabled=!v});E('from').required=v;E('to').required=v}
	sw.addEventListener('change',function(){setOff(sw.checked);if(sw.checked){if(!E('from').value)E('from').value=E('date').value;if(!E('to').value)E('to').value=E('from').value;E('from').focus()}});
	E('from').addEventListener('change',function(){E('to').min=E('from').value;if(!E('to').value||E('to').value<E('from').value)E('to').value=E('from').value});
	function fact(l,v,href){var s=document.createElement(href?'a':'span');s.className='ogm-fact';if(href)s.href=href;var a=document.createElement('small');a.textContent=l;var b=document.createElement('b');b.textContent=v;s.appendChild(a);s.appendChild(b);return s}
	document.addEventListener('click',function(e){
	 var b=e.target.closest('.ogm-view');
	 if(b){var r=JSON.parse(b.getAttribute('data-rec')||'{}');
	  E('car').value=r.car;E('rid').value=r.id;E('type').value=r.type;E('date').value=r.date;E('cost').value=r.cost?r.cost:'';E('notes').value=r.notes||'';
	  E('from').value=r.from||'';E('to').value=r.to||'';E('reason').value=r.reason||'';E('to').min=r.from||'';setOff(!!r.from);
	  var im=document.getElementById('ogm-d-img');if(r.img){im.src=r.img;im.hidden=false}else im.hidden=true;
	  document.getElementById('ogm-d-car').textContent=r.car_name;document.getElementById('ogm-d-meta').textContent=(r.type_name||'')+' · '+(r.date_h||'');
	  var fx=document.getElementById('ogm-d-facts');fx.innerHTML='';fx.appendChild(fact('Mileage',r.km_now||'–'));fx.appendChild(fact('Next service',r.next||'–'));fx.appendChild(fact('Records',String(r.count||0)));fx.appendChild(fact('Car details','Open →',r.edit));
	  document.getElementById('ogm-d-by').textContent=(r.by?'Added by '+r.by:'')+(r.edited?' · edited '+r.edited:'');
	  d.showModal();return}
	 if(e.target.closest('[data-close]')||e.target===d)d.close()});
	})();
	</script>
	<style>.ogm .ogdx-card{margin-bottom:16px}.ogm-help{color:#646970;margin:10px 0 0}.ogm-scroll{overflow-x:auto}.ogm-t{width:100%;border-collapse:collapse}.ogm-t th{font-size:12px;color:#6B7280;text-align:start;font-weight:600;padding:8px;border-bottom:1px solid #EEF0F3;white-space:nowrap}.ogm-t td{padding:9px 8px;border-bottom:1px solid #F1F2F4}.ogm-t .is-r{text-align:end}.ogm-t tr.is-sel td{background:#F7F8FA}.ogm-t form{margin:0}.ogm-x{border:0;background:none;color:#9CA3AF;font-size:18px;cursor:pointer}.ogm-x:hover{color:#B91C1C}.ogm-alerts{list-style:none;margin:0;padding:0;display:grid;gap:8px}.ogm-alerts a{display:flex;justify-content:space-between;gap:12px;padding:10px 14px;border-radius:10px;text-decoration:none;color:inherit}.ogm-alerts .is-over a{background:#FEE2E2}.ogm-alerts .is-soon a{background:#FEF3C7}.ogm-form{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end}.ogm-form label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:#4B5563}.ogm-form label.is-wide{flex:1 1 260px}.ogm-form fieldset{display:flex;gap:10px;border:1px solid #E6E8EC;border-radius:10px;padding:6px 10px 10px}.ogm-form legend{font-size:12px;color:#4B5563;padding:0 4px}.ogm-form input,.ogm-form select{min-height:36px}
	.ogm-why{display:block;color:#6B7280;font-size:12px}
	.ogm-f2{display:grid;gap:16px}
	.ogm-grid{display:grid;grid-template-columns:minmax(170px,1.4fr) minmax(130px,1fr) minmax(140px,1fr) minmax(120px,.9fr) minmax(200px,2fr);gap:14px}
	.ogm-grid.is-off{grid-template-columns:minmax(140px,1fr) minmax(140px,1fr) minmax(200px,2.4fr);margin-top:14px}
	.ogm-fld{display:flex;flex-direction:column;gap:6px;min-width:0}
	.ogm-fld>span:first-child{font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#94A3B8}
	.ogm-fld input,.ogm-fld select{width:100%;max-width:none;min-height:42px;border:1px solid #D6DAE1;border-radius:10px;padding:0 12px;font-size:14px;background-color:#fff;box-shadow:none}.ogm-fld select{padding-right:32px}
	.ogm-fld input:focus,.ogm-fld select:focus{border-color:var(--hx-primary,#2563EB);box-shadow:0 0 0 3px color-mix(in srgb,var(--hx-primary,#2563EB) 18%,transparent);outline:0}
	.ogm-fld input::placeholder{color:#B4BAC4}
	.ogm-money{display:flex;align-items:stretch;border:1px solid #D6DAE1;border-radius:10px;overflow:hidden;background:#fff}
	.ogm-money:focus-within{border-color:var(--hx-primary,#2563EB);box-shadow:0 0 0 3px color-mix(in srgb,var(--hx-primary,#2563EB) 18%,transparent)}
	.ogm-money b{display:flex;align-items:center;padding:0 11px;background:#F7F8FA;color:#6B7280;border-inline-end:1px solid #E7E9EE;font-weight:600}
	.ogm-money input{border:0!important;box-shadow:none!important;border-radius:0}
	.ogm-off{border:1px solid #E7E9EE;border-radius:14px;padding:14px 16px;background:#FAFBFC;transition:background .15s,border-color .15s}
	.ogm-off.is-on{background:#FFFBEB;border-color:#FDE68A}
	.ogm-sw{display:flex;align-items:center;gap:12px;cursor:pointer}
	.ogm-sw input{position:absolute;opacity:0}
	.ogm-sw i{position:relative;flex:none;width:40px;height:22px;border-radius:999px;background:#D1D5DB;transition:background .15s}
	.ogm-sw i:after{content:"";position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:transform .15s}
	.ogm-sw input:checked+i{background:#D97706}.ogm-sw input:checked+i:after{transform:translateX(18px)}
	.ogm-sw input:focus-visible+i{outline:2px solid var(--hx-primary,#2563EB);outline-offset:2px}
	.ogm-sw b{display:block;font-size:14px;color:#111827}.ogm-sw small{display:block;font-size:12px;color:#6B7280}
	.ogm-actions{display:flex;justify-content:flex-end}
	@media (max-width:1100px){.ogm-grid{grid-template-columns:1fr 1fr}.ogm-grid .is-notes{grid-column:1/-1}.ogm-grid.is-off{grid-template-columns:1fr 1fr}}
	@media (max-width:600px){.ogm-grid,.ogm-grid.is-off{grid-template-columns:1fr}.ogm-actions .ogdx-btn{width:100%}}
	.ogm-car{display:flex;align-items:center;gap:12px;text-decoration:none;color:#111827}.ogm-car img,.ogm-car .ogm-noimg{width:52px;height:36px;border-radius:8px;object-fit:cover;background:#EEF0F3;flex:none;display:inline-block}.ogm-car:hover b{color:var(--hx-primary,#2563EB)}
	.ogm-fleet td{vertical-align:middle}
	.ogm-st{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:600;white-space:nowrap}.ogm-st:before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor}
	.ogm-st.is-ok{background:#ECFDF3;color:#15803D}.ogm-st.is-off{background:#FEE2E2;color:#B91C1C}.ogm-st.is-soon{background:#FEF3C7;color:#B45309}
	.ogm-lt{font-weight:600;color:#111827}.ogm-mute{color:#9CA3AF}
	a.ogm-view{text-decoration:none;display:inline-block}
	.ogm-acts{display:inline-flex;align-items:center;gap:6px;justify-content:flex-end}
	.ogm-view{border:1px solid #D6DAE1;background:#fff;border-radius:8px;padding:5px 12px;font-size:12px;font-weight:600;color:#374151;cursor:pointer;white-space:nowrap}.ogm-view:hover{border-color:var(--hx-primary,#2563EB);color:var(--hx-primary,#2563EB)}
	.ogm-dlg{width:min(720px,calc(100vw - 32px));max-height:calc(100vh - 48px);padding:0;border:0;border-radius:18px;box-shadow:0 24px 60px rgba(16,24,40,.25)}
	.ogm-dlg::backdrop{background:rgba(15,23,42,.45);backdrop-filter:blur(2px)}
	.ogm-dlg-f{display:flex;flex-direction:column;max-height:calc(100vh - 48px)}
	.ogm-dlg-h{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:18px 22px;border-bottom:1px solid #EEF0F3}
	.ogm-dlg-car{display:flex;align-items:center;gap:12px}.ogm-dlg-car img{width:64px;height:44px;border-radius:8px;object-fit:cover}.ogm-dlg-car b{display:block;font-size:16px;color:#111827}.ogm-dlg-car small{color:#6B7280}
	.ogm-dlg-x{border:0;background:transparent;font-size:24px;line-height:1;color:#9CA3AF;width:36px;height:36px;border-radius:10px;cursor:pointer}.ogm-dlg-x:hover{background:#F3F4F6;color:#111827}
	.ogm-dlg-b{padding:18px 22px;overflow:auto;display:grid;gap:16px}
	.ogm-facts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
	.ogm-fact{background:#F8FAFC;border:1px solid #F0F2F5;border-radius:10px;padding:8px 10px;text-decoration:none;color:inherit}.ogm-fact small{display:block;font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#94A3B8}.ogm-fact b{font-size:13.5px;color:#111827}a.ogm-fact b{color:var(--hx-primary,#2563EB)}a.ogm-fact:hover{border-color:var(--hx-primary,#2563EB)}
	.ogm-grid.is-dlg{grid-template-columns:1fr 1fr 1fr}.ogm-grid.is-dlg .is-notes{grid-column:1/-1}
	.ogm-dlg .ogm-grid.is-off{grid-template-columns:1fr 1fr}.ogm-dlg .ogm-grid.is-off .is-notes{grid-column:1/-1}
	.ogm-dlg-ft{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 22px;border-top:1px solid #EEF0F3;background:#FAFBFC}.ogm-dlg-ft small{color:#9CA3AF}.ogm-dlg-ft span{display:flex;gap:8px}
	.ogm-btn2{border:1px solid #D6DAE1;background:#fff;border-radius:10px;padding:0 16px;min-height:40px;font-weight:600;cursor:pointer}.ogm-btn2:hover{background:#F3F4F6}
	@media (max-width:600px){.ogm-facts{grid-template-columns:1fr 1fr}.ogm-grid.is-dlg{grid-template-columns:1fr}}
	</style>
	<?php
	if ( function_exists( 'orego_ui_foot' ) ) {
		orego_ui_foot();
	} else {
		echo '</div>';
	}
}

/* Alert banner on the Your Cars dashboard. */
add_action( 'admin_notices', function () {
	if ( empty( $_GET['page'] ) || 'orego-dashboard' !== $_GET['page'] || ! empty( $_GET['tab'] ) ) {
		return;
	}
	$a = orego_fleet_alerts();
	if ( ! $a ) {
		return;
	}
	$over = count( array_filter( $a, fn( $x ) => 'over' === $x['level'] ) );
	echo '<div class="notice ' . ( $over ? 'notice-error' : 'notice-warning' ) . '"><p><b>Maintenance:</b> ' . esc_html( count( $a ) . ( 1 === count( $a ) ? ' item needs' : ' items need' ) . ' attention' . ( $over ? ' (' . $over . ' overdue)' : '' ) . '.' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=orego-maint' ) ) . '">Open Maintenance</a></p></div>';
} );
