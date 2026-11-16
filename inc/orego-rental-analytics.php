<?php
/**
 * Exotic Cars Rental Pro: extra analytics on Your Cars -> Statistics.
 * Money collected and outstanding, deposits held, utilisation and profit per car, booking behaviour.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_nights_in( $s, $e, $from, $to ) {
	$a = max( $s, $from );
	$b = min( $e, $to );
	return $b > $a ? (int) round( ( strtotime( $b . ' 00:00:00 UTC' ) - strtotime( $a . ' 00:00:00 UTC' ) ) / DAY_IN_SECONDS ) : 0;
}

add_action( 'orego_dash_stats_after', function ( $all ) {
	$today  = current_time( 'Y-m-d' );
	$month  = substr( $today, 0, 7 );
	$year   = substr( $today, 0, 4 );
	$d90    = gmdate( 'Y-m-d', strtotime( $today . ' -90 days UTC' ) );
	$n30    = gmdate( 'Y-m-d', strtotime( $today . ' +30 days UTC' ) );
	$earned = array_filter( $all, fn( $b ) => in_array( $b['status'], array( 'confirmed', 'onroad', 'completed' ), true ) );

	// Money.
	$coll = 0;
	$cmon = 0;
	$outs = 0;
	$held = 0;
	$kept = 0;
	$chg  = 0;
	$disc = 0;
	$extr = 0;
	foreach ( $all as $b ) {
		foreach ( orego_json_meta( $b['id'], 'og_payments' ) as $p ) {
			$v     = ( 'refund' === $p['type'] ? -1 : 1 ) * (float) $p['amount'];
			$coll += $v;
			if ( substr( $p['date'], 0, 7 ) === $month ) {
				$cmon += $v;
			}
		}
		if ( ! in_array( $b['status'], array( 'confirmed', 'onroad', 'completed' ), true ) ) {
			continue;
		}
		$sum   = orego_pay_summary( $b['id'] );
		$chg  += $sum['charges'];
		$outs += ( 'completed' === $b['status'] || $b['start'] <= $today ) && $sum['balance'] > 0 ? $sum['balance'] : 0;
		if ( 'held' === $sum['dep']['status'] ) {
			$held += $sum['dep']['amount'];
		}
		$kept += $sum['dep']['kept'];
		$q     = orego_bk_quote( $b['id'] );
		$disc += (float) $q['discount'];
		$extr += (float) $q['extras_total'];
	}

	// Fleet: utilisation (last 90 days, next 30 days), revenue and maintenance costs this year.
	$cars = get_posts( array( 'post_type' => 'orego_car', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	$rows = array();
	$tot  = array( 'n90' => 0, 'n30' => 0 );
	foreach ( $cars as $c ) {
		$r = array( 'car' => $c, 'n90' => 0, 'n30' => 0, 'rev' => 0, 'cost' => 0 );
		foreach ( $earned as $b ) {
			if ( (int) $b['car'] !== $c->ID ) {
				continue;
			}
			$r['n90'] += orego_nights_in( $b['start'], $b['end'], $d90, $today );
			$r['n30'] += orego_nights_in( $b['start'], $b['end'], $today, $n30 );
			if ( substr( $b['start'], 0, 4 ) === $year ) {
				$r['rev'] += orego_pay_summary( $b['id'] )['due'];
			}
		}
		if ( function_exists( 'orego_car_maint' ) ) {
			foreach ( orego_car_maint( $c->ID )['log'] as $m ) {
				if ( substr( $m['date'], 0, 4 ) === $year ) {
					$r['cost'] += (float) $m['cost'];
				}
			}
		}
		$tot['n90'] += $r['n90'];
		$tot['n30'] += $r['n30'];
		$rows[]      = $r;
	}
	usort( $rows, fn( $a, $b ) => ( $b['rev'] - $b['cost'] ) <=> ( $a['rev'] - $a['cost'] ) );
	$nc   = max( 1, count( $cars ) );
	$u90  = round( 100 * $tot['n90'] / ( $nc * 90 ) );
	$u30  = round( 100 * $tot['n30'] / ( $nc * 30 ) );

	// Booking behaviour.
	$cnt    = count( $all );
	$canc   = count( array_filter( $all, fn( $b ) => 'cancelled' === $b['status'] ) );
	$lead   = array();
	$places = array();
	$cust   = array();
	foreach ( $all as $b ) {
		if ( ! empty( $b['created'] ) ) {
			$lead[] = max( 0, orego_nights_in( substr( $b['created'], 0, 10 ), $b['start'], '0000-00-00', '9999-12-31' ) );
		}
		if ( $b['pickup'] ) {
			$places[ $b['pickup'] ] = ( $places[ $b['pickup'] ] ?? 0 ) + 1;
		}
		$e = strtolower( trim( $b['email'] ) );
		if ( $e ) {
			$cust[ $e ] = ( $cust[ $e ] ?? 0 ) + 1;
		}
	}
	arsort( $places );
	$repeat = count( array_filter( $cust, fn( $n ) => $n > 1 ) );
	$avglead = $lead ? round( array_sum( $lead ) / count( $lead ) ) : 0;
	$m = 'orego_bk_money';
	?>
	<section class="ogdx-kpis ogan-kpis">
		<div class="ogdx-kpi is-green"><span><small>Collected, all time</small><b><?php echo esc_html( $m( $coll ) ); ?></b><em><?php echo esc_html( $m( $cmon ) . ' this month' ); ?></em></span></div>
		<div class="ogdx-kpi is-amber"><span><small>Still to collect</small><b><?php echo esc_html( $m( $outs ) ); ?></b><em>Started or finished rentals</em></span></div>
		<div class="ogdx-kpi is-blue"><span><small>Deposits held now</small><b><?php echo esc_html( $m( $held ) ); ?></b><em><?php echo esc_html( $m( $kept ) . ' kept for damage' ); ?></em></span></div>
		<div class="ogdx-kpi is-violet"><span><small>Fleet booked</small><b><?php echo (int) $u90; ?>%</b><em><?php echo esc_html( 'Last 90 days · next 30: ' . $u30 . '%' ); ?></em></span></div>
	</section>
	<section class="ogdx-card ogan">
		<div class="ogdx-card-h"><h2>Profit by car, <?php echo esc_html( $year ); ?></h2><span class="ogan-sub">Rental income (with extras and charges) minus maintenance costs</span></div>
		<div class="ogan-scroll"><table class="ogan-t"><thead><tr><th>Car</th><th>Booked, last 90 days</th><th>Booked, next 30 days</th><th class="is-r">Income</th><th class="is-r">Maintenance</th><th class="is-r">Profit</th></tr></thead><tbody>
		<?php foreach ( $rows as $r ) : $p90 = round( 100 * $r['n90'] / 90 ); $p30 = round( 100 * $r['n30'] / 30 ); ?>
			<tr><td><?php echo esc_html( $r['car']->post_title ); ?></td>
			<td><span class="ogan-bar"><i style="width:<?php echo (int) min( 100, $p90 ); ?>%"></i></span><?php echo (int) $p90; ?>% <small>(<?php echo (int) $r['n90']; ?> days)</small></td>
			<td><span class="ogan-bar"><i style="width:<?php echo (int) min( 100, $p30 ); ?>%"></i></span><?php echo (int) $p30; ?>%</td>
			<td class="is-r"><?php echo esc_html( $m( $r['rev'] ) ); ?></td><td class="is-r"><?php echo esc_html( $r['cost'] ? '-' . $m( $r['cost'] ) : $m( 0 ) ); ?></td><td class="is-r"><b class="<?php echo $r['rev'] - $r['cost'] < 0 ? 'is-neg' : ''; ?>"><?php echo esc_html( ( $r['rev'] - $r['cost'] < 0 ? '-' : '' ) . $m( abs( $r['rev'] - $r['cost'] ) ) ); ?></b></td></tr>
		<?php endforeach; ?>
		</tbody></table></div>
	</section>
	<section class="ogan-grid">
		<div class="ogdx-card ogan">
			<div class="ogdx-card-h"><h2>Booking behaviour</h2></div>
			<dl class="ogan-dl">
				<div><dt>Booked how far ahead</dt><dd><?php echo (int) $avglead; ?> days on average</dd></div>
				<div><dt>Cancellation rate</dt><dd><?php echo esc_html( $cnt ? round( 100 * $canc / $cnt ) . '%' : '–' ); ?> <small>(<?php echo (int) $canc; ?> of <?php echo (int) $cnt; ?>)</small></dd></div>
				<div><dt>Returning customers</dt><dd><?php echo (int) $repeat; ?> of <?php echo (int) count( $cust ); ?></dd></div>
				<div><dt>Extras sold</dt><dd><?php echo esc_html( $m( $extr ) ); ?></dd></div>
				<div><dt>Long-rental discounts given</dt><dd><?php echo esc_html( $m( $disc ) ); ?></dd></div>
				<div><dt>Extra charges after return</dt><dd><?php echo esc_html( $m( $chg ) ); ?></dd></div>
			</dl>
		</div>
		<div class="ogdx-card ogan">
			<div class="ogdx-card-h"><h2>Pick-up places</h2></div>
			<?php if ( ! $places ) : ?><p class="ogan-sub">No bookings yet.</p><?php else : $pm = max( $places ); ?>
			<ul class="ogan-places"><?php foreach ( array_slice( $places, 0, 6, true ) as $pl => $n ) : ?><li><span><?php echo esc_html( $pl ); ?></span><span class="ogan-bar"><i style="width:<?php echo (int) round( 100 * $n / $pm ); ?>%"></i></span><b><?php echo (int) $n; ?></b></li><?php endforeach; ?></ul>
			<?php endif; ?>
		</div>
	</section>
	<style>.ogan-kpis{margin-top:18px}.ogan{margin-top:18px}.ogan-sub{color:#6B7280;font-size:13px}.ogan-scroll{overflow-x:auto}.ogan-t{width:100%;border-collapse:collapse}.ogan-t th{font-size:12px;color:#6B7280;text-align:start;font-weight:600;padding:8px;border-bottom:1px solid #EEF0F3;white-space:nowrap}.ogan-t td{padding:9px 8px;border-bottom:1px solid #F1F2F4;white-space:nowrap}.ogan-t .is-r{text-align:end}.ogan-t small{color:#9CA3AF}.ogan-bar{display:inline-block;vertical-align:middle;width:70px;height:6px;border-radius:6px;background:#EEF0F3;margin-inline-end:8px;overflow:hidden}.ogan-bar i{display:block;height:100%;background:var(--hx-primary,#0f5754);border-radius:6px}.is-neg{color:#B91C1C}.ogan-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.ogan-dl{margin:0;display:grid;gap:10px}.ogan-dl div{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid #F1F2F4;padding-bottom:8px}.ogan-dl dt{color:#4B5563}.ogan-dl dd{margin:0;font-weight:600}.ogan-dl small{color:#9CA3AF;font-weight:400}.ogan-places{list-style:none;margin:0;padding:0;display:grid;gap:10px}.ogan-places li{display:grid;grid-template-columns:1fr 90px 30px;align-items:center;gap:10px}.ogan-places .ogan-bar{width:90px}@media (max-width:900px){.ogan-grid{grid-template-columns:1fr}}</style>
	<?php
} );
