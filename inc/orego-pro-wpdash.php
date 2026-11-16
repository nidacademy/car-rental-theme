<?php
/**
 * WordPress Dashboard boxes for the booking system.
 * Each box can be shown or hidden in Dashboard -> Screen Options, and dragged like any other box.
 * - Bookings overview: how many bookings are in each step.
 * - Today: pick-ups, returns and late returns.
 * - Cars on the road: which cars are out, for how long, and when they are due back.
 * - Revenue: this month, last month, money received this month, money still owed.
 * - Coming up: pick-ups in the next 7 days.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_wd_all() {
	return function_exists( 'orego_dash_bookings' ) ? orego_dash_bookings() : array();
}

function orego_wd_money( $usd ) {
	return function_exists( 'orego_dash_money' ) ? orego_dash_money( $usd ) : '$' . number_format_i18n( $usd, 0 );
}

function orego_wd_url( $args = array() ) {
	return function_exists( 'orego_ui_url' ) ? orego_ui_url( 'orego-bookings', $args ) : admin_url( 'edit.php?post_type=orego_car&page=orego-bookings' );
}

function orego_wd_line( $b, $right ) {
	return '<li><a href="' . esc_url( orego_wd_url( array( 'booking' => $b['id'] ) ) ) . '"><span class="ogwd-car">' . ( has_post_thumbnail( $b['car'] ) ? get_the_post_thumbnail( $b['car'], 'thumbnail', array( 'alt' => '' ) ) : '' ) . '</span>'
		. '<span class="ogwd-tx"><b>' . esc_html( get_the_title( $b['car'] ) ) . '</b><small>#' . (int) $b['id'] . ' · ' . esc_html( $b['name'] ) . '</small></span>'
		. '<span class="ogwd-r">' . $right . '</span></a></li>';
}

add_action( 'wp_dashboard_setup', function () {
	if ( ! current_user_can( 'edit_posts' ) || ! function_exists( 'orego_bk_statuses' ) ) {
		return;
	}
	$all   = orego_wd_all();
	$today = current_time( 'Y-m-d' );

	/* 1. Overview */
	wp_add_dashboard_widget( 'orego_wd_overview', 'Bookings overview', function () use ( $all, $today ) {
		$st   = orego_bk_statuses();
		$n    = array_fill_keys( array_keys( $st ), 0 );
		$week = 0;
		foreach ( $all as $b ) {
			if ( isset( $n[ $b['status'] ] ) ) {
				$n[ $b['status'] ]++;
			}
			if ( strtotime( $b['created'] ) >= strtotime( $today . ' -6 days' ) ) {
				$week++;
			}
		}
		echo '<div class="ogwd-tiles">';
		foreach ( $st as $k => $v ) {
			echo '<a class="ogwd-tile" href="' . esc_url( orego_wd_url( array( 'status' => $k ) ) ) . '" style="--c:' . esc_attr( $v[1] ) . ';--bg:' . esc_attr( $v[2] ) . '"><b>' . (int) $n[ $k ] . '</b><span><i></i>' . esc_html( $v[0] ) . '</span></a>';
		}
		echo '</div><p class="ogwd-foot"><b>' . (int) $week . '</b> new ' . ( 1 === $week ? 'booking' : 'bookings' ) . ' in the last 7 days · <a href="' . esc_url( orego_wd_url() ) . '">All bookings →</a></p>';
	} );

	/* 2. Today */
	wp_add_dashboard_widget( 'orego_wd_today', 'Today', function () use ( $all, $today ) {
		$pick = array_filter( $all, fn( $b ) => 'confirmed' === $b['status'] && $b['start'] === $today );
		$ret  = array_filter( $all, fn( $b ) => 'onroad' === $b['status'] && $b['end'] === $today );
		$late = array_filter( $all, fn( $b ) => 'onroad' === $b['status'] && $b['end'] < $today );
		$sec  = function ( $title, $list, $right, $empty ) {
			echo '<h4 class="ogwd-h">' . esc_html( $title ) . ' <em>' . count( $list ) . '</em></h4>';
			if ( ! $list ) {
				echo '<p class="ogwd-empty">' . esc_html( $empty ) . '</p>';
				return;
			}
			echo '<ul class="ogwd-list">';
			foreach ( $list as $b ) {
				echo orego_wd_line( $b, $right( $b ) ); // phpcs:ignore
			}
			echo '</ul>';
		};
		$sec( 'Pick-ups', $pick, fn( $b ) => '<span class="ogwd-pill is-confirmed">' . esc_html( $b['ptime'] ) . '</span>', 'No pick-ups today.' );
		$sec( 'Returns', $ret, fn( $b ) => '<span class="ogwd-pill is-onroad">' . esc_html( $b['rtime'] ) . '</span>', 'No returns today.' );
		if ( $late ) {
			$sec( 'Late returns', $late, fn( $b ) => '<span class="ogwd-pill is-late">' . esc_html( human_time_diff( strtotime( $b['end'] . ' ' . $b['rtime'] ), current_time( 'timestamp' ) ) ) . ' late</span>', '' );
		}
	} );

	/* 3. Cars on the road */
	add_meta_box( 'orego_wd_onroad', 'Cars on the road', function () use ( $all ) {
		$list = array_values( array_filter( $all, fn( $b ) => 'onroad' === $b['status'] ) );
		usort( $list, fn( $a, $b ) => strcmp( $a['end'] . $a['rtime'], $b['end'] . $b['rtime'] ) );
		if ( ! $list ) {
			echo '<p class="ogwd-empty">No car is on the road right now.</p>';
			return;
		}
		echo '<ul class="ogwd-list">';
		foreach ( $list as $b ) {
			$chip = function_exists( 'orego_onroad_chip' ) ? orego_onroad_chip( $b ) : '';
			echo orego_wd_line( $b, $chip ? $chip : esc_html( 'Back ' . wp_date( 'M j', strtotime( $b['end'] . ' 12:00' ) ) ) ); // phpcs:ignore
		}
		echo '</ul><p class="ogwd-foot"><a href="' . esc_url( orego_wd_url( array( 'status' => 'onroad' ) ) ) . '">See all on the road →</a></p>';
	}, 'dashboard', 'side', 'high' );

	/* 4. Revenue */
	add_meta_box( 'orego_wd_money', 'Revenue', function () use ( $all ) {
		$m     = current_time( 'Y-m' );
		$lm    = gmdate( 'Y-m', strtotime( $m . '-01 -1 month' ) );
		$earn  = array( 'confirmed', 'onroad', 'completed' );
		$cur   = 0;
		$last  = 0;
		$paid  = 0;
		$owed  = 0;
		foreach ( $all as $b ) {
			if ( ! in_array( $b['status'], $earn, true ) ) {
				continue;
			}
			$mon = substr( $b['start'], 0, 7 );
			$cur += $mon === $m ? (float) $b['total'] : 0;
			$last += $mon === $lm ? (float) $b['total'] : 0;
			if ( function_exists( 'orego_json_meta' ) ) {
				foreach ( orego_json_meta( $b['id'], 'og_payments' ) as $p ) {
					if ( substr( (string) $p['date'], 0, 7 ) === $m ) {
						$paid += ( 'refund' === ( $p['type'] ?? '' ) ? -1 : 1 ) * (float) $p['amount'];
					}
				}
			}
			if ( function_exists( 'orego_pay_summary' ) ) {
				$owed += max( 0, orego_pay_summary( $b['id'] )['balance'] );
			}
		}
		$diff = $last > 0 ? round( ( $cur - $last ) / $last * 100 ) : null;
		echo '<div class="ogwd-money">'
			. '<div class="is-main"><small>Booked this month</small><b>' . esc_html( orego_wd_money( $cur ) ) . '</b>' . ( null !== $diff ? '<em class="' . ( $diff >= 0 ? 'is-up' : 'is-down' ) . '">' . ( $diff >= 0 ? '▲ ' : '▼ ' ) . abs( $diff ) . '% vs last month</em>' : '<em>' . esc_html( wp_date( 'F' ) ) . '</em>' ) . '</div>'
			. '<div><small>Last month</small><b>' . esc_html( orego_wd_money( $last ) ) . '</b></div>'
			. '<div><small>Received this month</small><b>' . esc_html( orego_wd_money( $paid ) ) . '</b></div>'
			. '<div><small>Still to collect</small><b class="' . ( $owed > 0 ? 'is-due' : '' ) . '">' . esc_html( orego_wd_money( $owed ) ) . '</b></div>'
			. '</div>';
		if ( function_exists( 'orego_dash_url' ) ) {
			echo '<p class="ogwd-foot"><a href="' . esc_url( orego_dash_url( array( 'tab' => 'stats' ) ) ) . '">Full statistics →</a></p>';
		}
	}, 'dashboard', 'side', 'high' );

	/* 5. Coming up */
	wp_add_dashboard_widget( 'orego_wd_next', 'Coming up (next 7 days)', function () use ( $all, $today ) {
		$to   = gmdate( 'Y-m-d', strtotime( $today . ' +7 days' ) );
		$list = array_values( array_filter( $all, fn( $b ) => in_array( $b['status'], array( 'pending', 'confirmed' ), true ) && $b['start'] > $today && $b['start'] <= $to ) );
		usort( $list, fn( $a, $b ) => strcmp( $a['start'] . $a['ptime'], $b['start'] . $b['ptime'] ) );
		if ( ! $list ) {
			echo '<p class="ogwd-empty">No pick-ups in the next 7 days.</p>';
			return;
		}
		echo '<ul class="ogwd-list">';
		foreach ( $list as $b ) {
			$cls = 'pending' === $b['status'] ? 'is-pending' : 'is-confirmed';
			echo orego_wd_line( $b, '<span class="ogwd-pill ' . $cls . '">' . esc_html( wp_date( 'D j M', strtotime( $b['start'] . ' 12:00' ) ) . ' · ' . $b['ptime'] ) . '</span>' ); // phpcs:ignore
		}
		echo '</ul>';
	} );
} );

add_action( 'admin_head-index.php', function () {
	?>
<style id="ogwd-css">
#orego_wd_overview .inside,#orego_wd_today .inside,#orego_wd_onroad .inside,#orego_wd_money .inside,#orego_wd_next .inside{margin:0;padding:14px 14px 12px}
.ogwd-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
.ogwd-tile{display:flex;flex-direction:column;gap:4px;padding:12px 12px 10px;border-radius:12px;background:var(--bg);color:#15171C;text-decoration:none;transition:transform .12s}
.ogwd-tile:hover{transform:translateY(-1px);color:#15171C}
.ogwd-tile b{font-size:24px;line-height:1;font-weight:800;color:var(--c)}
.ogwd-tile span{display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600}
.ogwd-tile i{width:7px;height:7px;border-radius:50%;background:var(--c)}
.ogwd-foot{margin:12px 0 0;font-size:12.5px;color:#6B7280}
.ogwd-foot a{text-decoration:none;font-weight:600}
.ogwd-h{display:flex;align-items:center;gap:8px;margin:12px 0 6px;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#6B7280}
.ogwd-h:first-child{margin-top:0}
.ogwd-h em{font-style:normal;padding:1px 7px;border-radius:99px;background:#F3F4F6;color:#15171C;letter-spacing:0}
.ogwd-empty{margin:0 0 4px;color:#9CA3AF;font-size:13px}
.ogwd-list{margin:0;padding:0;list-style:none}
.ogwd-list li{margin:0}
.ogwd-list li+li{border-top:1px solid #F1F2F4}
.ogwd-list a{display:flex;align-items:center;gap:10px;padding:8px 2px;text-decoration:none;color:#15171C}
.ogwd-list a:hover{background:#F9FAFB}
.ogwd-car{flex:none;width:44px;height:32px;border-radius:8px;background:#F3F4F6;overflow:hidden}
.ogwd-car img{width:100%;height:100%;object-fit:cover;display:block}
.ogwd-tx{flex:1;min-width:0;display:flex;flex-direction:column}
.ogwd-tx b{font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ogwd-tx small{font-size:12px;color:#6B7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ogwd-r{flex:none;text-align:end}
.ogwd-r .ogro-chip{margin:0}
.ogwd-pill{display:inline-block;padding:3px 9px;border-radius:99px;font-size:12px;font-weight:700;white-space:nowrap}
.ogwd-pill.is-confirmed{background:#DCFCE7;color:#15803D}.ogwd-pill.is-pending{background:#FEF3C7;color:#B45309}.ogwd-pill.is-onroad{background:#EDE9FE;color:#6D28D9}.ogwd-pill.is-late{background:#FEE2E2;color:#B91C1C}
.ogro-chip{display:inline-flex;padding:3px 9px;border-radius:99px;background:#EDE9FE;color:#6D28D9;font-size:12px;font-weight:700;white-space:nowrap}.ogro-chip.is-late{background:#FEE2E2;color:#B91C1C}
.ogwd-money{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
.ogwd-money>div{padding:10px 12px;border-radius:12px;background:#F6F7F9}
.ogwd-money>div.is-main{grid-column:1/-1;background:#15171C;color:#FFFFFF}
.ogwd-money small{display:block;font-size:12px;color:#6B7280}
.ogwd-money .is-main small{color:#9CA3AF}
.ogwd-money b{display:block;margin-top:3px;font-size:16px;font-weight:800}
.ogwd-money .is-main b{font-size:26px}
.ogwd-money em{display:block;margin-top:3px;font-style:normal;font-size:12px;color:#9CA3AF}
.ogwd-money em.is-up{color:#4ADE80}.ogwd-money em.is-down{color:#F87171}
.ogwd-money b.is-due{color:#B91C1C}
</style>
	<?php
} );
