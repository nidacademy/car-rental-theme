<?php
/**
 * Exotic Cars Fleet Dashboard: Your Cars -> Dashboard
 * Overview (KPIs, availability search, cars, recent activity), fleet Calendar, Statistics, CSV export.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=orego_car', 'Dashboard', 'Dashboard', 'edit_posts', 'orego-dashboard', 'orego_dash_render', 0 );
}, 15 );

/* ------------------------------------------------------------------ */
/* Data helpers                                                        */
/* ------------------------------------------------------------------ */

function orego_dash_bookings() {
	static $all = null;
	if ( null !== $all ) {
		return $all;
	}
	$all = array();
	if ( ! function_exists( 'orego_bk_info' ) ) {
		return $all;
	}
	foreach ( get_posts( array( 'post_type' => 'orego_booking', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC' ) ) as $p ) {
		$i            = orego_bk_info( $p->ID );
		$i['id']      = $p->ID;
		$i['created'] = $p->post_date;
		$all[]        = $i;
	}
	return $all;
}

function orego_dash_cars() {
	static $c = null;
	if ( null === $c ) {
		$c = get_posts( array( 'post_type' => 'orego_car', 'post_status' => array( 'publish', 'draft', 'pending', 'future' ), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	}
	return $c;
}

function orego_dash_ymd_diff( $a, $b ) {
	return (int) round( ( strtotime( $b . ' 00:00:00 UTC' ) - strtotime( $a . ' 00:00:00 UTC' ) ) / DAY_IN_SECONDS );
}

function orego_dash_money( $v ) {
	$v = (float) $v;
	return '$' . number_format( $v, floor( $v ) == $v ? 0 : 2 );
}

function orego_dash_en( $kind, $v ) {
	$m = array(
		'trans' => array( 'auto' => 'Automatic', 'manual' => 'Manual' ),
		'fuel'  => array( 'gasoline' => 'Petrol', 'hybrid' => 'Hybrid', 'diesel' => 'Diesel', 'electric' => 'Electric' ),
		'st'    => array( 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'onroad' => 'On road', 'declined' => 'Declined', 'cancelled' => 'Cancelled', 'completed' => 'Returned' ),
	);
	return $m[ $kind ][ $v ] ?? ucfirst( (string) $v );
}

function orego_dash_date( $ymd, $fmt = 'M j' ) {
	return $ymd ? wp_date( $fmt, strtotime( $ymd . ' 12:00:00' ), new DateTimeZone( 'UTC' ) ) : '';
}

function orego_dash_ic( $n, $s = 18 ) {
	$p = array(
		'grid'   => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
		'list'   => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
		'cal'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
		'book'   => '<path d="M9 11l2 2 4-4"/><rect x="4" y="3" width="16" height="18" rx="2"/>',
		'chart'  => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>',
		'pin'    => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'plus'   => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
		'eye'    => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
		'export' => '<path d="M12 15V3M7 8l5-5 5 5"/><path d="M20 15v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4"/>',
		'down'   => '<path d="m6 9 6 6 6-6"/>',
		'edit'   => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
		'car'    => '<path d="M5 16H3v-4l2-5h11l3 5h2v4h-2"/><circle cx="7.5" cy="16.5" r="2"/><circle cx="16.5" cy="16.5" r="2"/><path d="M9.5 16.5h5"/>',
		'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'phone'  => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z"/>',
		'mail'   => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
		'check'  => '<path d="M20 6 9 17l-5-5"/>',
		'x'      => '<path d="M18 6 6 18M6 6l12 12"/>',
		'money'  => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
		'key'    => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
		'gauge'  => '<path d="M12 14l4-4"/><path d="M3.3 19a10 10 0 1 1 17.4 0"/>',
		'inbox'  => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1Z"/>',
		'filter' => '<path d="M3 6h18M7 12h10M10 18h4"/>',
		'prev'   => '<path d="m15 18-6-6 6-6"/>',
		'next'   => '<path d="m9 18 6-6-6-6"/>',
		'ext'    => '<path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
	);
	return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $n ] . '</svg>';
}

/* ------------------------------------------------------------------ */
/* CSV export                                                          */
/* ------------------------------------------------------------------ */

add_action( 'admin_post_orego_export', function () {
	check_admin_referer( 'orego_export' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Not allowed.' );
	}
	$type = sanitize_key( $_GET['what'] ?? 'bookings' );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=orego-' . $type . '-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // Excel-friendly UTF-8 (Arabic text)
	if ( 'cars' === $type ) {
		fputcsv( $out, array( 'ID', 'Car', 'Status', 'Type', 'Price/day', 'Seats', 'Transmission', 'Fuel', 'Location', 'Rating', 'URL' ) );
		foreach ( orego_dash_cars() as $c ) {
			$d = orego_car_data( $c->ID );
			$t = wp_get_post_terms( $c->ID, 'car_type', array( 'fields' => 'names' ) );
			fputcsv( $out, array( $c->ID, $c->post_title, $c->post_status, implode( ', ', (array) $t ), $d['price'], $d['seats'], orego_dash_en( 'trans', $d['trans'] ), orego_dash_en( 'fuel', $d['fuel'] ), $d['location'], $d['rating'], get_permalink( $c ) ) );
		}
	} else {
		fputcsv( $out, array( 'Booking', 'Status', 'Car', 'Pickup date', 'Pickup time', 'Pickup location', 'Return date', 'Return time', 'Drop-off location', 'Days', 'Price/day', 'Total', 'Customer', 'Email', 'Phone', 'Notes', 'Requested on' ) );
		foreach ( orego_dash_bookings() as $b ) {
			fputcsv( $out, array( '#' . $b['id'], orego_dash_en( 'st', $b['status'] ), get_the_title( $b['car'] ), $b['start'], $b['ptime'], $b['pickup'], $b['end'], $b['rtime'], $b['dropoff'], $b['days'], $b['pday'], $b['total'], $b['name'], $b['email'], $b['phone'], $b['notes'], $b['created'] ) );
		}
	}
	fclose( $out );
	exit;
} );

/* ------------------------------------------------------------------ */
/* Page                                                                */
/* ------------------------------------------------------------------ */

function orego_dash_url( $args = array() ) {
	return add_query_arg( $args, admin_url( 'edit.php?post_type=orego_car&page=orego-dashboard' ) );
}

function orego_dash_render() {
	$tab   = sanitize_key( $_GET['tab'] ?? 'overview' );
	$tab   = in_array( $tab, array( 'overview', 'calendar', 'stats' ), true ) ? $tab : 'overview';
	$all   = orego_dash_bookings();
	$pend  = count( array_filter( $all, fn( $b ) => 'pending' === $b['status'] ) );
	$exp   = wp_nonce_url( admin_url( 'admin-post.php?action=orego_export' ), 'orego_export' );
	echo '<style>' . orego_dash_css() . '</style>'; // phpcs:ignore
	?>
<div class="ogdx">
	<main class="ogdx-main">
		<header class="ogdx-head">
			<div>
				<h1><?php echo esc_html( array( 'overview' => 'Dashboard', 'calendar' => 'Fleet Calendar', 'stats' => 'Statistics' )[ $tab ] ); ?></h1>
				<p><?php echo esc_html( array( 'overview' => 'Your fleet and bookings at a glance', 'calendar' => 'Every car and booking by day, month or year', 'stats' => 'How your rentals perform over the last 6 months' )[ $tab ] ); ?></p>
			</div>
			<details class="ogdx-export">
				<summary><?php echo orego_dash_ic( 'export', 18 ); // phpcs:ignore ?>Export<?php echo orego_dash_ic( 'down', 16 ); // phpcs:ignore ?></summary>
				<div><a href="<?php echo esc_url( add_query_arg( 'what', 'bookings', $exp ) ); ?>">Bookings (CSV)</a><a href="<?php echo esc_url( add_query_arg( 'what', 'cars', $exp ) ); ?>">Cars (CSV)</a></div>
			</details>
		</header>
		<?php
		if ( 'calendar' === $tab ) {
			orego_dash_calendar();
		} elseif ( 'stats' === $tab ) {
			orego_dash_stats();
		} else {
			orego_dash_overview();
		}
		?>
	</main>
</div>
	<?php
}

/* ---------- Overview ---------- */

function orego_dash_overview() {
	$all    = orego_dash_bookings();
	$cars   = orego_dash_cars();
	$today  = current_time( 'Y-m-d' );
	$month  = current_time( 'Y-m' );
	$pend   = array_filter( $all, fn( $b ) => 'pending' === $b['status'] );
	$active = array_filter( $all, fn( $b ) => 'onroad' === $b['status'] || ( 'confirmed' === $b['status'] && $b['start'] <= $today && $b['end'] > $today ) );
	$rev    = array_sum( array_map( fn( $b ) => $b['total'], array_filter( $all, fn( $b ) => in_array( $b['status'], array( 'confirmed', 'onroad', 'completed' ), true ) && substr( $b['start'], 0, 7 ) === $month ) ) );
	$pub    = array_filter( $cars, fn( $c ) => 'publish' === $c->post_status );
	$horizon = gmdate( 'Y-m-d', strtotime( $today . ' +30 days UTC' ) );
	$nights = 0;
	foreach ( $all as $b ) {
		if ( ! in_array( $b['status'], array( 'confirmed', 'onroad' ), true ) ) {
			continue;
		}
		$s = max( $b['start'], $today );
		$e = min( $b['end'], $horizon );
		if ( $e > $s ) {
			$nights += orego_dash_ymd_diff( $s, $e );
		}
	}
	$util = count( $pub ) ? round( 100 * $nights / ( count( $pub ) * 30 ) ) : 0;
	$kpis = array(
		array( 'inbox', 'Pending requests', count( $pend ), count( $pend ) ? 'Waiting for your approval' : 'All caught up', 'amber', admin_url( 'edit.php?post_type=orego_booking&og_status=pending' ) ),
		array( 'key', 'On rental now', count( $active ), 'Out with customers today', 'green', orego_dash_url( array( 'tab' => 'calendar' ) ) ),
		array( 'money', 'Revenue this month', orego_bk_money( $rev ), 'Confirmed & returned, ' . wp_date( 'F' ), 'blue', orego_dash_url( array( 'tab' => 'stats' ) ) ),
		array( 'gauge', 'Fleet booked, next 30 days', $util . '%', count( $pub ) . ' published ' . ( 1 === count( $pub ) ? 'car' : 'cars' ), 'violet', orego_dash_url( array( 'tab' => 'calendar' ) ) ),
	);
	$locs  = array_values( array_unique( array_filter( array_map( fn( $c ) => orego_car_data( $c->ID )['location'], $cars ) ) ) );
	$types = get_terms( array( 'taxonomy' => 'car_type', 'hide_empty' => true ) );
	?>
	<section class="ogdx-kpis">
		<?php foreach ( $kpis as $k ) : ?>
			<a class="ogdx-kpi is-<?php echo esc_attr( $k[4] ); ?>" href="<?php echo esc_url( $k[5] ); ?>"><span class="ogdx-kpi-ic"><?php echo orego_dash_ic( $k[0], 20 ); // phpcs:ignore ?></span><span><small><?php echo esc_html( $k[1] ); ?></small><b><?php echo esc_html( $k[2] ); ?></b><em><?php echo esc_html( $k[3] ); ?></em></span></a>
		<?php endforeach; ?>
	</section>

	<section class="ogdx-filters" aria-label="Find available cars">
		<label><span>Location</span><select id="ogdx-loc"><option value="">All locations</option><?php foreach ( $locs as $l ) : ?><option value="<?php echo esc_attr( $l ); ?>"><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></label>
		<label><span>Pick Up Date</span><input type="date" id="ogdx-from" min="<?php echo esc_attr( $today ); ?>"></label>
		<label><span>Drop off Date</span><input type="date" id="ogdx-to" min="<?php echo esc_attr( $today ); ?>"></label>
		<label><span>Car Type</span><select id="ogdx-type"><option value="">All types</option><?php if ( ! is_wp_error( $types ) ) : foreach ( $types as $t ) : ?><option value="<?php echo esc_attr( $t->slug ); ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; endif; ?></select></label>
		<label><span>Show</span><select id="ogdx-show"><option value="all">All cars</option><option value="live">Published only</option></select></label>
	</section>

	<div class="ogdx-cols">
		<section class="ogdx-card">
			<div class="ogdx-card-h">
				<div><h2>Available Cars</h2><p id="ogdx-count"></p></div>
				<button type="button" class="ogdx-ghost" id="ogdx-reset"><?php echo orego_dash_ic( 'filter', 16 ); // phpcs:ignore ?>Reset filters</button>
			</div>
			<?php if ( ! $cars ) : ?>
				<div class="ogdx-empty"><?php echo orego_dash_ic( 'car', 36 ); // phpcs:ignore ?><b>No cars yet</b><a class="ogdx-btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=orego_car' ) ); ?>">Add your first car</a></div>
			<?php else : ?>
			<div class="ogdx-cars" id="ogdx-cars">
				<?php
				foreach ( $cars as $c ) :
					$d      = orego_car_data( $c->ID );
					$blk    = function_exists( 'orego_blocked_nights' ) ? orego_blocked_nights( $c->ID ) : array();
					$terms  = get_the_terms( $c->ID, 'car_type' );
					$slugs  = ( $terms && ! is_wp_error( $terms ) ) ? implode( ' ', wp_list_pluck( $terms, 'slug' ) ) : '';
					$tname  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '–';
					$busy   = in_array( $today, $blk, true );
					$next   = '';
					foreach ( $blk as $n ) {
						if ( $n > $today ) {
							$next = $n;
							break;
						}
					}
					$draft = 'publish' !== $c->post_status;
					?>
				<article class="ogdx-car" data-blocked="<?php echo esc_attr( wp_json_encode( $blk ) ); ?>" data-loc="<?php echo esc_attr( $d['location'] ); ?>" data-types="<?php echo esc_attr( $slugs ); ?>" data-live="<?php echo $draft ? '0' : '1'; ?>">
					<a class="ogdx-car-img" href="<?php echo esc_url( get_edit_post_link( $c->ID ) ); ?>" tabindex="-1" aria-hidden="true">
						<?php echo has_post_thumbnail( $c ) ? get_the_post_thumbnail( $c, 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ) : '<span class="ogdx-noimg">' . orego_dash_ic( 'car', 40 ) . '</span>'; // phpcs:ignore ?>
						<?php $og_lbl = $draft ? 'Draft' : ( function_exists( 'orego_car_state_label' ) ? orego_car_state_label( $c->ID ) : ( $busy ? 'On rental' : 'Available' ) ); ?><span class="ogdx-badge <?php echo esc_attr( 'Draft' === $og_lbl ? 'is-draft' : ( 'Available' === $og_lbl ? 'is-free' : 'is-busy' ) ); ?>" data-default="<?php echo esc_attr( $og_lbl ); ?>"><?php echo esc_html( $og_lbl ); ?></span>
					</a>
					<div class="ogdx-car-body">
						<div class="ogdx-car-top"><h3><a href="<?php echo esc_url( get_edit_post_link( $c->ID ) ); ?>"><?php echo esc_html( $c->post_title ); ?></a></h3>
							<span class="ogdx-acts"><a href="<?php echo esc_url( get_edit_post_link( $c->ID ) ); ?>" title="Edit car" aria-label="Edit <?php echo esc_attr( $c->post_title ); ?>"><?php echo orego_dash_ic( 'edit', 16 ); // phpcs:ignore ?></a><a href="<?php echo esc_url( get_permalink( $c ) ); ?>" target="_blank" rel="noopener" title="View on site" aria-label="View <?php echo esc_attr( $c->post_title ); ?> on the site"><?php echo orego_dash_ic( 'ext', 16 ); // phpcs:ignore ?></a></span></div>
						<dl class="ogdx-specs"><div><dt>Type:</dt><dd><?php echo esc_html( $tname ); ?></dd></div><div><dt>Gear:</dt><dd><?php echo esc_html( orego_dash_en( 'trans', $d['trans'] ) ); ?></dd></div><div><dt>Fuel:</dt><dd><?php echo esc_html( orego_dash_en( 'fuel', $d['fuel'] ) ); ?></dd></div></dl>
						<div class="ogdx-car-foot"><b><?php echo '' === $d['price'] ? '<span class="ogdx-warn">No price</span>' : esc_html( orego_dash_money( $d['price'] ) ) . '<small> /day</small>'; // phpcs:ignore ?></b><span class="ogdx-next"><?php echo $next ? esc_html( 'Next booking ' . orego_dash_date( $next ) ) : 'No upcoming bookings'; ?></span></div>
					</div>
				</article>
				<?php endforeach; ?>
			</div>
			<div class="ogdx-empty" id="ogdx-none" hidden><?php echo orego_dash_ic( 'car', 36 ); // phpcs:ignore ?><b>No cars match</b><span>Try other dates or clear the filters.</span></div>
			<?php endif; ?>
		</section>

		<?php orego_dash_activity( $all ); ?>
	</div>
	<script><?php echo orego_dash_js(); // phpcs:ignore ?></script>
	<?php
}

function orego_dash_activity( $all ) {
	$recent = array_slice( $all, 0, 8 );
	?>
	<section class="ogdx-card ogdx-act">
		<div class="ogdx-card-h"><h2>Recent Activity</h2><a class="ogdx-link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=orego_booking' ) ); ?>">All bookings</a></div>
		<?php if ( ! $recent ) : ?>
			<div class="ogdx-empty"><?php echo orego_dash_ic( 'inbox', 36 ); // phpcs:ignore ?><b>No bookings yet</b><span>New requests from the website appear here the moment they arrive.</span></div>
		<?php else : ?>
		<ul class="ogdx-feed" role="tablist">
			<?php foreach ( $recent as $k => $b ) : ?>
			<li><button type="button" role="tab" class="ogdx-feed-i<?php echo 0 === $k ? ' is-on' : ''; ?>" aria-selected="<?php echo 0 === $k ? 'true' : 'false'; ?>" data-id="<?php echo (int) $b['id']; ?>">
				<span class="ogdx-thumb"><?php echo has_post_thumbnail( $b['car'] ) ? get_the_post_thumbnail( $b['car'], 'thumbnail', array( 'alt' => '' ) ) : orego_dash_ic( 'car', 22 ); // phpcs:ignore ?></span>
				<span class="ogdx-feed-t"><b><?php echo esc_html( get_the_title( $b['car'] ) ); ?></b><small><?php echo orego_dash_ic( 'clock', 12 ); // phpcs:ignore ?><?php echo esc_html( $b['days'] . ' day rental · ' . orego_dash_date( $b['start'] ) ); ?></small><small><?php echo esc_html( $b['name'] ); ?></small></span>
				<span class="ogdx-feed-r"><b><?php echo esc_html( orego_bk_money( $b['total'], $b['id'] ?? ( $id ?? null ) ) ); ?></b><i class="ogdx-st is-<?php echo esc_attr( $b['status'] ); ?>"><?php echo esc_html( orego_dash_en( 'st', $b['status'] ) ); ?></i></span>
			</button></li>
			<?php endforeach; ?>
		</ul>
		<?php foreach ( $recent as $k => $b ) : $ini = mb_strtoupper( mb_substr( trim( $b['name'] ), 0, 1 ) ); ?>
		<div class="ogdx-info" data-for="<?php echo (int) $b['id']; ?>"<?php echo 0 === $k ? '' : ' hidden'; ?>>
			<div class="ogdx-info-h"><span>Booking #<?php echo (int) $b['id']; ?></span><i class="ogdx-st is-<?php echo esc_attr( $b['status'] ); ?>"><?php echo esc_html( orego_dash_en( 'st', $b['status'] ) ); ?></i></div>
			<div class="ogdx-person"><span class="ogdx-avatar"><?php echo esc_html( $ini ); ?></span><span><b><?php echo esc_html( $b['name'] ); ?></b><small><?php echo esc_html( $b['email'] ); ?></small></span></div>
			<div class="ogdx-route">
				<div class="ogdx-route-line"><i class="a"></i><span></span><i class="b"></i></div>
				<div class="ogdx-route-pts">
					<div><small>Pick up</small><b><?php echo esc_html( orego_dash_date( $b['start'], 'D, M j' ) ); ?> · <?php echo esc_html( $b['ptime'] ); ?></b><span><?php echo esc_html( $b['pickup'] ); ?></span></div>
					<div><small>Return</small><b><?php echo esc_html( orego_dash_date( $b['end'], 'D, M j' ) ); ?> · <?php echo esc_html( $b['rtime'] ); ?></b><span><?php echo esc_html( $b['dropoff'] ); ?></span></div>
				</div>
			</div>
			<dl class="ogdx-facts">
				<div><dt>Duration</dt><dd><?php echo esc_html( $b['days'] . ( 1 === $b['days'] ? ' day' : ' days' ) ); ?></dd></div>
				<div><dt>Daily rate</dt><dd><?php echo esc_html( orego_bk_money( $b['pday'], $b['id'] ?? ( $id ?? null ) ) ); ?></dd></div>
				<div><dt>Total</dt><dd><?php echo esc_html( orego_bk_money( $b['total'], $b['id'] ?? ( $id ?? null ) ) ); ?></dd></div>
				<div><dt>Payment</dt><dd>At pickup</dd></div>
				<div><dt>Phone</dt><dd dir="ltr"><?php echo esc_html( $b['phone'] ); ?></dd></div>
				<div><dt>Requested</dt><dd><?php echo esc_html( wp_date( 'M j, H:i', strtotime( get_gmt_from_date( $b['created'] ) . ' UTC' ) ) ); ?></dd></div>
			</dl>
			<?php if ( $b['notes'] ) : ?><p class="ogdx-note">“<?php echo esc_html( $b['notes'] ); ?>”</p><?php endif; ?>
			<div class="ogdx-info-acts">
				<?php if ( 'pending' === $b['status'] && function_exists( 'orego_bk_action_url' ) ) : ?>
					<a class="ogdx-btn is-ok" href="<?php echo esc_url( orego_bk_action_url( $b['id'], 'confirmed' ) ); ?>"><?php echo orego_dash_ic( 'check', 16 ); // phpcs:ignore ?>Approve</a>
					<a class="ogdx-btn is-no" href="<?php echo esc_url( orego_bk_action_url( $b['id'], 'declined' ) ); ?>"><?php echo orego_dash_ic( 'x', 16 ); // phpcs:ignore ?>Decline</a>
				<?php else : ?>
					<a class="ogdx-btn is-soft" href="<?php echo esc_url( get_edit_post_link( $b['id'] ) ); ?>">Open booking</a>
				<?php endif; ?>
				<a class="ogdx-round" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $b['phone'] ) ); ?>" title="Call" aria-label="Call <?php echo esc_attr( $b['name'] ); ?>"><?php echo orego_dash_ic( 'phone', 17 ); // phpcs:ignore ?></a>
				<a class="ogdx-round" href="mailto:<?php echo esc_attr( $b['email'] ); ?>" title="Email" aria-label="Email <?php echo esc_attr( $b['name'] ); ?>"><?php echo orego_dash_ic( 'mail', 17 ); // phpcs:ignore ?></a>
			</div>
		</div>
		<?php endforeach; ?>
		<?php endif; ?>
	</section>
	<?php
}

/* ---------- Calendar ---------- */

/** Everything that occupies a car: bookings, manual closures, off-the-road maintenance. End date is exclusive for closures, the return day for bookings. */
function orego_dash_cal_bars( $c, $all ) {
	$bars = array();
	foreach ( $all as $b ) {
		if ( (int) $b['car'] !== $c->ID || ! in_array( $b['status'], array( 'pending', 'confirmed', 'onroad', 'completed' ), true ) ) {
			continue;
		}
		$bars[] = array( 's' => $b['start'], 'e' => $b['end'], 'st' => $b['status'], 'lbl' => $b['name'] . ' · ' . orego_bk_money( $b['total'], $b['id'] ), 'url' => get_edit_post_link( $b['id'] ), 'pt' => (string) $b['ptime'], 'rt' => (string) $b['rtime'], 'bk' => true, 'name' => $b['name'] );
	}
	foreach ( preg_split( '/\r\n|\r|\n/', (string) get_post_meta( $c->ID, 'og_booked', true ) ) as $line ) {
		if ( preg_match_all( '/(\d{4}-\d{2}-\d{2})/', $line, $m ) && $m[1] ) {
			$e      = $m[1][1] ?? $m[1][0];
			$e      = $e > $m[1][0] ? $e : gmdate( 'Y-m-d', strtotime( $m[1][0] . ' +1 day UTC' ) );
			$bars[] = array( 's' => $m[1][0], 'e' => $e, 'st' => 'block', 'lbl' => 'Closed manually', 'url' => get_edit_post_link( $c->ID ), 'bk' => false );
		}
	}
	if ( function_exists( 'orego_car_maint' ) ) {
		foreach ( orego_car_maint( $c->ID )['log'] as $r ) {
			if ( ! empty( $r['from'] ) ) {
				$bars[] = array( 's' => $r['from'], 'e' => gmdate( 'Y-m-d', strtotime( ( $r['to'] ? $r['to'] : $r['from'] ) . ' +1 day UTC' ) ), 'st' => 'block', 'lbl' => 'Off the road' . ( ! empty( $r['reason'] ) ? ': ' . $r['reason'] : '' ), 'url' => admin_url( 'admin.php?page=orego-maint&car=' . $c->ID ), 'bk' => false );
			}
		}
	}
	return $bars;
}

function orego_dash_calendar() {
	$today = current_time( 'Y-m-d' );
	$view  = sanitize_key( $_GET['view'] ?? 'month' );
	$view  = in_array( $view, array( 'day', 'month', 'year' ), true ) ? $view : 'month';
	$d     = sanitize_text_field( wp_unslash( $_GET['d'] ?? ( $_GET['from'] ?? '' ) ) );
	$d     = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ? $d : $today;
	$cars  = orego_dash_cars();
	$all   = orego_dash_bookings();
	$u     = fn( $v, $date = null ) => orego_dash_url( array_filter( array( 'tab' => 'calendar', 'view' => $v, 'd' => $date ) ) );
	$step  = array( 'day' => '1 day', 'month' => '1 month', 'year' => '1 year' )[ $view ];
	$base  = 'month' === $view ? substr( $d, 0, 7 ) . '-01' : ( 'year' === $view ? substr( $d, 0, 4 ) . '-01-01' : $d );
	$prev  = gmdate( 'Y-m-d', strtotime( $base . ' -' . $step . ' UTC' ) );
	$next  = gmdate( 'Y-m-d', strtotime( $base . ' +' . $step . ' UTC' ) );
	$title = 'day' === $view ? orego_dash_date( $d, 'l, F j, Y' ) : ( 'month' === $view ? orego_dash_date( $base, 'F Y' ) : substr( $d, 0, 4 ) );
	$unit  = array( 'day' => 'day', 'month' => 'month', 'year' => 'year' )[ $view ];
	?>
	<section class="ogdx-card ogdx-cal">
		<div class="ogdx-card-h ogdx-cal-h">
			<div><h2><?php echo esc_html( $title ); ?></h2>
			<p class="ogdx-legend"><span><i class="is-confirmed"></i>Confirmed</span><span><i class="is-pending"></i>Pending</span><span><i class="is-completed"></i>Returned</span><span><i class="is-block"></i>Closed / maintenance</span></p></div>
			<div class="ogdx-cal-tools">
				<div class="ogdx-seg" role="tablist" aria-label="Calendar view">
					<?php foreach ( array( 'day' => 'Day', 'month' => 'Month', 'year' => 'Year' ) as $k => $l ) : ?>
						<a role="tab" aria-selected="<?php echo $k === $view ? 'true' : 'false'; ?>" class="<?php echo $k === $view ? 'is-on' : ''; ?>" href="<?php echo esc_url( $u( $k, $d ) ); ?>"><?php echo esc_html( $l ); ?></a>
					<?php endforeach; ?>
				</div>
				<div class="ogdx-pager"><a href="<?php echo esc_url( $u( $view, $prev ) ); ?>" aria-label="Previous <?php echo esc_attr( $unit ); ?>"><?php echo orego_dash_ic( is_rtl() ? 'next' : 'prev', 18 ); // phpcs:ignore ?></a><a href="<?php echo esc_url( $u( $view ) ); ?>">Today</a><a href="<?php echo esc_url( $u( $view, $next ) ); ?>" aria-label="Next <?php echo esc_attr( $unit ); ?>"><?php echo orego_dash_ic( is_rtl() ? 'prev' : 'next', 18 ); // phpcs:ignore ?></a></div>
			</div>
		</div>
		<?php if ( ! $cars ) : ?>
			<div class="ogdx-empty"><?php echo orego_dash_ic( 'car', 36 ); // phpcs:ignore ?><b>No cars yet</b></div>
		<?php
		elseif ( 'year' === $view ) :
			$y = (int) substr( $d, 0, 4 );
			?>
		<div class="ogdx-tl-wrap">
			<div class="ogdx-tl is-year" style="--n:12">
				<div class="ogdx-tl-row ogdx-tl-head"><span class="ogdx-tl-car">Car</span>
					<?php for ( $mo = 1; $mo <= 12; $mo++ ) : $ms = sprintf( '%04d-%02d-01', $y, $mo ); ?>
						<a class="ogdx-tl-day<?php echo substr( $today, 0, 7 ) === substr( $ms, 0, 7 ) ? ' is-today' : ''; ?>" href="<?php echo esc_url( $u( 'month', $ms ) ); ?>"><b><?php echo esc_html( orego_dash_date( $ms, 'M' ) ); ?></b></a>
					<?php endfor; ?>
				</div>
				<?php
				$tot = array_fill( 1, 12, array( 0, 0 ) );
				foreach ( $cars as $c ) :
					$bars = orego_dash_cal_bars( $c, $all );
					?>
				<div class="ogdx-tl-row">
					<a class="ogdx-tl-car" href="<?php echo esc_url( get_edit_post_link( $c->ID ) ); ?>"><span class="ogdx-thumb"><?php echo has_post_thumbnail( $c ) ? get_the_post_thumbnail( $c, 'thumbnail', array( 'alt' => '' ) ) : orego_dash_ic( 'car', 18 ); // phpcs:ignore ?></span><span><b><?php echo esc_html( $c->post_title ); ?></b><?php if ( 'publish' !== $c->post_status ) : ?><small>Draft</small><?php endif; ?></span></a>
					<div class="ogdx-yr">
					<?php
					for ( $mo = 1; $mo <= 12; $mo++ ) {
						$ms   = sprintf( '%04d-%02d-01', $y, $mo );
						$me   = gmdate( 'Y-m-d', strtotime( $ms . ' +1 month UTC' ) );
						$dim  = (int) gmdate( 't', strtotime( $ms . ' UTC' ) );
						$used = 0;
						$cnt  = 0;
						$blk  = 0;
						foreach ( $bars as $b ) {
							$s = max( $b['s'], $ms );
							$e = min( $b['e'], $me );
							$n = orego_dash_ymd_diff( $s, $e );
							if ( $n <= 0 ) {
								continue;
							}
							if ( $b['bk'] ) {
								$used += $n;
								$cnt  += ( $b['s'] >= $ms && $b['s'] < $me ) ? 1 : 0;
							} else {
								$blk += $n;
							}
						}
						$pct           = min( 100, (int) round( $used / $dim * 100 ) );
						$tot[ $mo ][0] += $used;
						$tot[ $mo ][1] += $dim;
						$tip           = orego_dash_date( $ms, 'F' ) . ': ' . $used . ' of ' . $dim . ' days booked' . ( $cnt ? ', ' . $cnt . ( 1 === $cnt ? ' booking' : ' bookings' ) : '' ) . ( $blk ? ', ' . $blk . ' days closed' : '' );
						echo '<a class="ogdx-yc' . ( $pct ? '' : ' is-zero' ) . '" href="' . esc_url( $u( 'month', $ms ) ) . '" title="' . esc_attr( $tip ) . '" style="--p:' . (int) $pct . '"><b>' . ( $pct ? (int) $pct . '%' : '–' ) . '</b>' . ( $cnt ? '<small>' . (int) $cnt . ( 1 === $cnt ? ' booking' : ' bookings' ) . '</small>' : '' ) . ( $blk ? '<i class="ogdx-yblk" title="' . esc_attr( $blk . ' days closed' ) . '"></i>' : '' ) . '</a>';
					}
					?>
					</div>
				</div>
				<?php endforeach; ?>
				<div class="ogdx-tl-row ogdx-yr-tot"><span class="ogdx-tl-car"><b>Whole fleet</b></span><div class="ogdx-yr">
					<?php foreach ( $tot as $mo => $t ) : $p = $t[1] ? (int) round( $t[0] / $t[1] * 100 ) : 0; ?>
						<span class="ogdx-yc is-tot" style="--p:<?php echo (int) $p; ?>"><b><?php echo $p ? (int) $p . '%' : '–'; ?></b></span>
					<?php endforeach; ?>
				</div></div>
			</div>
		</div>
		<p class="ogdx-cal-note">Percent of days booked in each month. Click a month to open it.</p>
		<?php
		elseif ( 'day' === $view ) :
			$hours = 24;
			$hr    = function ( $t, $def ) {
				return preg_match( '/^(\d{1,2})/', (string) $t, $m ) ? min( 24, max( 0, (int) $m[1] ) ) : $def;
			};
			$moves = array();
			?>
		<div class="ogdx-tl-wrap">
			<div class="ogdx-tl is-day" style="--n:<?php echo (int) $hours; ?>">
				<div class="ogdx-tl-row ogdx-tl-head"><span class="ogdx-tl-car">Car</span>
					<?php for ( $h = 0; $h < $hours; $h++ ) : ?>
						<span class="ogdx-tl-day<?php echo $h % 2 ? ' is-odd' : ''; ?>"><b><?php echo $h % 2 ? '' : esc_html( sprintf( '%02d', $h ) ); ?></b></span>
					<?php endfor; ?>
				</div>
				<?php
				foreach ( $cars as $c ) :
					$bars = orego_dash_cal_bars( $c, $all );
					?>
				<div class="ogdx-tl-row">
					<a class="ogdx-tl-car" href="<?php echo esc_url( get_edit_post_link( $c->ID ) ); ?>"><span class="ogdx-thumb"><?php echo has_post_thumbnail( $c ) ? get_the_post_thumbnail( $c, 'thumbnail', array( 'alt' => '' ) ) : orego_dash_ic( 'car', 18 ); // phpcs:ignore ?></span><span><b><?php echo esc_html( $c->post_title ); ?></b><?php if ( 'publish' !== $c->post_status ) : ?><small>Draft</small><?php endif; ?></span></a>
					<div class="ogdx-tl-track">
						<?php
						if ( $d === $today ) {
							$nh = (int) current_time( 'G' );
							echo '<span class="ogdx-tl-now" style="grid-column:' . ( $nh + 1 ) . '"></span>';
						}
						foreach ( $bars as $b ) {
							if ( $b['bk'] ) {
								if ( $d < $b['s'] || $d > $b['e'] ) {
									continue;
								}
								$sh = $d === $b['s'] ? $hr( $b['pt'], 0 ) : 0;
								$eh = $d === $b['e'] ? $hr( $b['rt'], 24 ) : 24;
								if ( $d === $b['s'] ) {
									$moves[] = array( $sh, 'Pick-up', $b['pt'], $c->post_title, $b['name'], $b['url'], $b['st'] );
								}
								if ( $d === $b['e'] ) {
									$moves[] = array( $eh, 'Return', $b['rt'], $c->post_title, $b['name'], $b['url'], $b['st'] );
								}
							} else {
								if ( $d < $b['s'] || $d >= $b['e'] ) {
									continue;
								}
								$sh = 0;
								$eh = 24;
							}
							if ( $eh <= $sh ) {
								continue;
							}
							$lbl = ( $b['bk'] ? ( $d === $b['s'] ? 'Pick-up ' . $b['pt'] . ' · ' : '' ) . ( $d === $b['e'] ? 'Return ' . $b['rt'] . ' · ' : '' ) : '' ) . $b['lbl'];
							echo '<a class="ogdx-bar is-' . esc_attr( $b['st'] ) . ( $sh > 0 ? '' : ' cut-s' ) . ( $eh < 24 ? '' : ' cut-e' ) . '" style="grid-column:' . ( $sh + 1 ) . ' / ' . ( $eh + 1 ) . '" href="' . esc_url( $b['url'] ) . '" title="' . esc_attr( $lbl ) . '"><span>' . esc_html( $b['bk'] ? $lbl : 'Closed' ) . '</span></a>';
						}
						?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php usort( $moves, fn( $a, $b ) => $a[0] <=> $b[0] ); ?>
		<div class="ogdx-moves">
			<h3>Pick-ups &amp; returns</h3>
			<?php if ( ! $moves ) : ?>
				<p class="ogdx-cal-note">Nothing planned on this day.</p>
			<?php else : ?>
				<ul><?php foreach ( $moves as $m ) : ?><li><a href="<?php echo esc_url( $m[5] ); ?>"><b class="ogdx-mt"><?php echo esc_html( $m[2] ? $m[2] : '–' ); ?></b><span class="ogdx-mk is-<?php echo 'Pick-up' === $m[1] ? 'out' : 'in'; ?>"><?php echo esc_html( $m[1] ); ?></span><span class="ogdx-mc"><?php echo esc_html( $m[3] ); ?></span><span class="ogdx-mn"><?php echo esc_html( $m[4] ); ?></span><i class="ogdx-st is-<?php echo esc_attr( $m[6] ); ?>"><?php echo esc_html( ucfirst( $m[6] ) ); ?></i></a></li><?php endforeach; ?></ul>
			<?php endif; ?>
		</div>
		<?php
		else :
			$from = $base;
			$days = (int) gmdate( 't', strtotime( $from . ' UTC' ) );
			?>
		<div class="ogdx-tl-wrap">
			<div class="ogdx-tl" style="--n:<?php echo (int) $days; ?>">
				<div class="ogdx-tl-row ogdx-tl-head"><span class="ogdx-tl-car">Car</span>
					<?php for ( $i = 0; $i < $days; $i++ ) : $dd = gmdate( 'Y-m-d', strtotime( $from . ' +' . $i . ' days UTC' ) ); $wd = (int) gmdate( 'N', strtotime( $dd . ' UTC' ) ); ?>
						<a class="ogdx-tl-day<?php echo $dd === $today ? ' is-today' : ''; ?><?php echo $wd >= 6 ? ' is-we' : ''; ?>" href="<?php echo esc_url( $u( 'day', $dd ) ); ?>" title="Open this day"><small><?php echo esc_html( orego_dash_date( $dd, 'D' ) ); ?></small><b><?php echo esc_html( orego_dash_date( $dd, 'j' ) ); ?></b></a>
					<?php endfor; ?>
				</div>
				<?php
				foreach ( $cars as $c ) :
					$bars = orego_dash_cal_bars( $c, $all );
					?>
				<div class="ogdx-tl-row">
					<a class="ogdx-tl-car" href="<?php echo esc_url( get_edit_post_link( $c->ID ) ); ?>"><span class="ogdx-thumb"><?php echo has_post_thumbnail( $c ) ? get_the_post_thumbnail( $c, 'thumbnail', array( 'alt' => '' ) ) : orego_dash_ic( 'car', 18 ); // phpcs:ignore ?></span><span><b><?php echo esc_html( $c->post_title ); ?></b><?php if ( 'publish' !== $c->post_status ) : ?><small>Draft</small><?php endif; ?></span></a>
					<div class="ogdx-tl-track">
						<?php
						$ti = orego_dash_ymd_diff( $from, $today );
						if ( $ti >= 0 && $ti < $days ) {
							echo '<span class="ogdx-tl-now" style="grid-column:' . ( $ti + 1 ) . '"></span>';
						}
						foreach ( $bars as $bar ) {
							$s = orego_dash_ymd_diff( $from, $bar['s'] );
							$e = orego_dash_ymd_diff( $from, $bar['e'] );
							if ( $e <= 0 || $s >= $days ) {
								continue;
							}
							$cs  = max( 0, $s ) + 1;
							$ce  = min( $days, $e ) + 1;
							$lbl = orego_dash_date( $bar['s'] ) . ' → ' . orego_dash_date( $bar['e'] ) . ' · ' . $bar['lbl'];
							echo '<a class="ogdx-bar is-' . esc_attr( $bar['st'] ) . ( $s < 0 ? ' cut-s' : '' ) . ( $e > $days ? ' cut-e' : '' ) . '" style="grid-column:' . (int) $cs . ' / ' . (int) $ce . '" href="' . esc_url( $bar['url'] ) . '" title="' . esc_attr( $lbl ) . '"><span>' . esc_html( 'block' === $bar['st'] ? 'Closed' : $bar['lbl'] ) . '</span></a>';
						}
						?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<p class="ogdx-cal-note">Click a date to see that day hour by hour.</p>
		<?php endif; ?>
	</section>
	<style>
	.ogdx-cal-h{flex-wrap:wrap;gap:12px}
	.ogdx-cal-tools{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
	.ogdx-seg{display:inline-flex;background:#F1F3F6;border-radius:12px;padding:3px;gap:2px}
	.ogdx-seg a{height:32px;padding:0 14px;display:flex;align-items:center;border-radius:9px;font-weight:600;font-size:13px;color:var(--su,#6B7280);text-decoration:none}
	.ogdx-seg a:hover{color:var(--ink,#15171C)}
	.ogdx-seg a.is-on{background:#fff;color:var(--ink,#15171C);box-shadow:0 1px 3px rgba(16,24,40,.12)}
	a.ogdx-tl-day{color:inherit;text-decoration:none;border-radius:8px}a.ogdx-tl-day:hover{background:#F1F3F6}
	.ogdx-cal-note{color:var(--su,#6B7280);font-size:12.5px;margin:12px 0 0}
	.ogdx-tl.is-day .ogdx-tl-day b{font-size:11.5px;color:var(--su,#6B7280);font-weight:600}
	.ogdx-tl.is-year{min-width:900px}
	.ogdx-yr{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:4px;padding:6px 0}
	.ogdx-yc{position:relative;min-height:44px;border-radius:9px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-decoration:none;color:#14532D;background:color-mix(in srgb,#22C55E calc(var(--p) * 0.85%),#F4F6F8)}
	.ogdx-yc b{font-size:13px;font-weight:700}.ogdx-yc small{font-size:10px;opacity:.85}
	.ogdx-yc.is-zero{color:#9CA3AF;background:#F7F8FA}
	.ogdx-yc:hover{outline:2px solid var(--a,#3563E9);outline-offset:-2px}
	.ogdx-yc.is-tot{color:#1E3A8A;background:color-mix(in srgb,#3B82F6 calc(var(--p) * 0.8%),#F4F6F8)}
	.ogdx-yblk{position:absolute;top:5px;inset-inline-end:5px;width:7px;height:7px;border-radius:2px;background:repeating-linear-gradient(-45deg,#9CA3AF 0 2px,#E5E7EB 2px 4px)}
	.ogdx-yr-tot{border-bottom:0}
	.ogdx-moves{margin-top:18px}
	.ogdx-moves h3{font-size:14px;margin:0 0 10px}
	.ogdx-moves ul{list-style:none;margin:0;padding:0;display:grid;gap:6px}
	.ogdx-moves a{display:grid;grid-template-columns:60px 80px minmax(0,1.2fr) minmax(0,1fr) auto;gap:12px;align-items:center;padding:10px 14px;border:1px solid var(--ln,#E6E8EC);border-radius:12px;color:var(--ink,#15171C);text-decoration:none}
	.ogdx-moves a:hover{border-color:var(--a,#3563E9)}
	.ogdx-mt{font-variant-numeric:tabular-nums}
	.ogdx-mk{font-size:12px;font-weight:700;border-radius:999px;padding:3px 10px;text-align:center}.ogdx-mk.is-out{background:#ECFDF3;color:#15803D}.ogdx-mk.is-in{background:#EFF6FF;color:#1D4ED8}
	.ogdx-mc{font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ogdx-mn{color:var(--su,#6B7280);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
	@media (max-width:782px){.ogdx-moves a{grid-template-columns:56px 1fr;row-gap:4px}.ogdx-mc,.ogdx-mn{grid-column:2}}
	</style>
	<?php
}

/* ---------- Statistics ---------- */

function orego_dash_stats() {
	$all    = orego_dash_bookings();
	$earned = array_filter( $all, fn( $b ) => in_array( $b['status'], array( 'confirmed', 'onroad', 'completed' ), true ) );
	$months = array();
	for ( $i = 5; $i >= 0; $i-- ) {
		$k            = wp_date( 'Y-m', strtotime( current_time( 'Y-m-01' ) . " -$i months" ) );
		$months[ $k ] = array( 'rev' => 0, 'n' => 0 );
	}
	foreach ( $earned as $b ) {
		$k = substr( $b['start'], 0, 7 );
		if ( isset( $months[ $k ] ) ) {
			$months[ $k ]['rev'] += $b['total'];
			$months[ $k ]['n']++;
		}
	}
	$max      = max( 1, max( array_column( $months, 'rev' ) ) );
	$decided  = array_filter( $all, fn( $b ) => in_array( $b['status'], array( 'confirmed', 'onroad', 'completed', 'declined' ), true ) );
	$approval = $decided ? round( 100 * count( $earned ) / count( $decided ) ) : 0;
	$avgDays  = $earned ? round( array_sum( array_column( $earned, 'days' ) ) / count( $earned ), 1 ) : 0;
	$avgVal   = $earned ? array_sum( array_column( $earned, 'total' ) ) / count( $earned ) : 0;
	$total    = array_sum( array_column( $earned, 'total' ) );
	$byCar    = array();
	foreach ( $earned as $b ) {
		$byCar[ $b['car'] ]          = $byCar[ $b['car'] ] ?? array( 'rev' => 0, 'n' => 0, 'days' => 0 );
		$byCar[ $b['car'] ]['rev']  += $b['total'];
		$byCar[ $b['car'] ]['n']++;
		$byCar[ $b['car'] ]['days'] += $b['days'];
	}
	uasort( $byCar, fn( $a, $b ) => $b['rev'] <=> $a['rev'] );
	$st = array();
	foreach ( $all as $b ) {
		$st[ $b['status'] ] = ( $st[ $b['status'] ] ?? 0 ) + 1;
	}
	?>
	<section class="ogdx-kpis">
		<div class="ogdx-kpi is-blue"><span class="ogdx-kpi-ic"><?php echo orego_dash_ic( 'money', 20 ); // phpcs:ignore ?></span><span><small>Revenue, all time</small><b><?php echo esc_html( orego_bk_money( $total ) ); ?></b><em>Confirmed & completed</em></span></div>
		<div class="ogdx-kpi is-green"><span class="ogdx-kpi-ic"><?php echo orego_dash_ic( 'book', 20 ); // phpcs:ignore ?></span><span><small>Average booking</small><b><?php echo esc_html( orego_bk_money( round( $avgVal ) ) ); ?></b><em><?php echo esc_html( $avgDays . ' days on average' ); ?></em></span></div>
		<div class="ogdx-kpi is-violet"><span class="ogdx-kpi-ic"><?php echo orego_dash_ic( 'check', 20 ); // phpcs:ignore ?></span><span><small>Approval rate</small><b><?php echo esc_html( $approval . '%' ); ?></b><em>Of requests you decided</em></span></div>
		<div class="ogdx-kpi is-amber"><span class="ogdx-kpi-ic"><?php echo orego_dash_ic( 'inbox', 20 ); // phpcs:ignore ?></span><span><small>All requests</small><b><?php echo (int) count( $all ); ?></b><em><?php echo (int) ( $st['pending'] ?? 0 ); ?> waiting for you</em></span></div>
	</section>
	<div class="ogdx-cols ogdx-cols-even">
		<section class="ogdx-card">
			<div class="ogdx-card-h"><div><h2>Revenue by month</h2><p>Confirmed & returned bookings, by pickup month</p></div></div>
			<div class="ogdx-bars">
				<?php foreach ( $months as $k => $m ) : $h = $m['rev'] ? max( 4, round( 100 * $m['rev'] / $max ) ) : 0; ?>
				<div class="ogdx-bcol"><span class="ogdx-bval"><?php echo $m['rev'] ? esc_html( orego_bk_money( round( $m['rev'] ) ) ) : '–'; ?></span><span class="ogdx-btrack"><span class="ogdx-bfill<?php echo current_time( 'Y-m' ) === $k ? ' is-now' : ''; ?>" style="height:<?php echo (int) $h; ?>%"></span></span><span class="ogdx-blbl"><?php echo esc_html( wp_date( 'M', strtotime( $k . '-15' ) ) ); ?><small><?php echo (int) $m['n']; ?> bookings</small></span></div>
				<?php endforeach; ?>
			</div>
		</section>
		<section class="ogdx-card">
			<div class="ogdx-card-h"><div><h2>Top cars</h2><p>Ranked by revenue</p></div></div>
			<?php if ( ! $byCar ) : ?>
				<div class="ogdx-empty"><?php echo orego_dash_ic( 'chart', 36 ); // phpcs:ignore ?><b>No confirmed bookings yet</b><span>Approve requests and your best performers show up here.</span></div>
			<?php else : $top = max( 1, max( array_column( $byCar, 'rev' ) ) ); ?>
			<ol class="ogdx-rank">
				<?php foreach ( array_slice( $byCar, 0, 6, true ) as $cid => $r ) : ?>
				<li><span class="ogdx-thumb"><?php echo has_post_thumbnail( $cid ) ? get_the_post_thumbnail( $cid, 'thumbnail', array( 'alt' => '' ) ) : orego_dash_ic( 'car', 18 ); // phpcs:ignore ?></span><span class="ogdx-rank-t"><b><?php echo esc_html( get_the_title( $cid ) ); ?></b><small><?php echo (int) $r['n']; ?> bookings · <?php echo (int) $r['days']; ?> days</small><i style="width:<?php echo (int) round( 100 * $r['rev'] / $top ); ?>%"></i></span><b><?php echo esc_html( orego_bk_money( $r['rev'] ) ); ?></b></li>
				<?php endforeach; ?>
			</ol>
			<?php endif; ?>
		</section>
	</div>
	<section class="ogdx-card">
		<div class="ogdx-card-h"><div><h2>Requests by status</h2><p>All booking requests received</p></div></div>
		<?php $sum = max( 1, array_sum( $st ) ); ?>
		<div class="ogdx-stack"><?php foreach ( array( 'confirmed', 'onroad', 'completed', 'pending', 'declined', 'cancelled' ) as $s ) : if ( empty( $st[ $s ] ) ) { continue; } ?><span class="is-<?php echo esc_attr( $s ); ?>" style="flex:<?php echo (int) $st[ $s ]; ?>" title="<?php echo esc_attr( orego_dash_en( 'st', $s ) . ': ' . $st[ $s ] ); ?>"></span><?php endforeach; ?><?php if ( ! $st ) : ?><span class="is-empty" style="flex:1"></span><?php endif; ?></div>
		<p class="ogdx-legend"><?php foreach ( array( 'confirmed', 'onroad', 'completed', 'pending', 'declined', 'cancelled' ) as $s ) : ?><span><i class="is-<?php echo esc_attr( $s ); ?>"></i><?php echo esc_html( orego_dash_en( 'st', $s ) ); ?> <b><?php echo (int) ( $st[ $s ] ?? 0 ); ?></b> (<?php echo (int) round( 100 * ( $st[ $s ] ?? 0 ) / $sum ); ?>%)</span><?php endforeach; ?></p>
	</section>
	<?php
	do_action( 'orego_dash_stats_after', $all );
}

/* ------------------------------------------------------------------ */
/* Assets                                                              */
/* ------------------------------------------------------------------ */

function orego_dash_js() {
	return <<<'JS'
(function(){
var cars=[].slice.call(document.querySelectorAll('.ogdx-car'));
var loc=document.getElementById('ogdx-loc'),from=document.getElementById('ogdx-from'),to=document.getElementById('ogdx-to'),type=document.getElementById('ogdx-type'),show=document.getElementById('ogdx-show'),count=document.getElementById('ogdx-count'),none=document.getElementById('ogdx-none');
function add(k,n){var p=k.split('-'),d=new Date(Date.UTC(+p[0],p[1]-1,+p[2]+n));return d.toISOString().slice(0,10)}
function apply(){
 if(!cars.length)return;
 var a=from&&from.value,b=to&&to.value;if(a&&to){to.min=add(a,1);if(b&&b<=a){to.value='';b=''}}
 var n=0,free=0;
 cars.forEach(function(c){
  var ok=(!loc.value||c.dataset.loc===loc.value)&&(!type.value||(' '+c.dataset.types+' ').indexOf(' '+type.value+' ')>-1)&&(show.value!=='live'||c.dataset.live==='1');
  var badge=c.querySelector('.ogdx-badge');
  if(ok&&a&&b){var bl=JSON.parse(c.dataset.blocked||'[]'),clash=false;for(var k=a;k<b;k=add(k,1)){if(bl.indexOf(k)>-1){clash=true;break}}
   badge.textContent=clash?'Booked':'Free for these dates';badge.className='ogdx-badge '+(clash?'is-busy':'is-free');c.classList.toggle('is-dim',clash);if(!clash)free++}
  else{badge.textContent=badge.dataset.default;badge.className='ogdx-badge '+(badge.dataset.default==='Draft'?'is-draft':badge.dataset.default==='Available'?'is-free':'is-busy');c.classList.remove('is-dim')}
  c.hidden=!ok;if(ok)n++});
 cars.sort(function(x,y){return (x.classList.contains('is-dim')?1:0)-(y.classList.contains('is-dim')?1:0)}).forEach(function(c){c.parentNode.appendChild(c)});
 count.textContent=a&&b?(free+' of '+n+' cars free for these dates'):(n+(n===1?' car':' cars'));
 none.hidden=n>0}
[loc,from,to,type,show].forEach(function(el){if(el)el.addEventListener('change',apply)});
var r=document.getElementById('ogdx-reset');if(r)r.addEventListener('click',function(){[loc,type].forEach(function(e){e.value=''});show.value='all';from.value='';to.value='';apply()});
apply();
var items=[].slice.call(document.querySelectorAll('.ogdx-feed-i'));
items.forEach(function(it){it.addEventListener('click',function(){
 items.forEach(function(x){x.classList.toggle('is-on',x===it);x.setAttribute('aria-selected',x===it?'true':'false')});
 [].forEach.call(document.querySelectorAll('.ogdx-info'),function(p){p.hidden=p.dataset.for!==it.dataset.id})})});
document.addEventListener('click',function(e){var d=document.querySelector('.ogdx-export[open]');if(d&&!d.contains(e.target))d.removeAttribute('open')});
})();
JS;
}

function orego_dash_css() {
	return <<<'CSS'
body.orego_car_page_orego-dashboard{background:#F6F7F9}
body.orego_car_page_orego-dashboard #wpcontent{padding-inline-start:0}
body.orego_car_page_orego-dashboard #wpbody-content{padding-bottom:0}
body.orego_car_page_orego-dashboard #wpfooter{display:none}
body.orego_car_page_orego-dashboard .notice:not(.ogdx *){margin:16px 24px 0}
.ogdx{--a:#3563E9;--a2:#2A52CC;--as:#EAF0FD;--ink:#15171C;--mu:#6B7280;--su:#9AA1AD;--ln:#ECEEF2;display:block;min-height:calc(100vh - 32px);color:var(--ink);font-size:14px}
.ogdx *,.ogdx *::before,.ogdx *::after{box-sizing:border-box}
.ogdx h1,.ogdx h2,.ogdx h3,.ogdx p,.ogdx dl,.ogdx dd,.ogdx ol,.ogdx ul{margin:0;padding:0}
.ogdx ul,.ogdx ol{list-style:none}
.ogdx a{text-decoration:none}
.ogdx a:focus,.ogdx button:focus{box-shadow:none}
.ogdx a:focus-visible,.ogdx button:focus-visible,.ogdx summary:focus-visible,.ogdx select:focus-visible,.ogdx input:focus-visible{outline:2px solid var(--a);outline-offset:2px}
.ogdx-nav{background:#FFFFFF;border-inline-end:1px solid var(--ln);padding:22px 14px;position:sticky;top:32px;align-self:start;height:calc(100vh - 32px);overflow:auto}
.ogdx-nav-h{font-size:13px;color:var(--su);padding:0 12px;margin:4px 0 10px!important}
.ogdx-nav a{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:12px;font-size:15px;font-weight:500;color:#374151;margin-bottom:4px;transition:background .15s,color .15s}
.ogdx-nav a svg{color:#6B7280;flex:none}
.ogdx-nav a:hover{background:#F4F5F7;color:var(--ink)}
.ogdx-nav a.is-on{background:var(--a);color:#FFFFFF;font-weight:600;box-shadow:0 8px 20px rgba(53,99,233,.25)}
.ogdx-nav a.is-on svg{color:#FFFFFF}
.ogdx-nav em{margin-inline-start:auto;font-style:normal;font-size:12px;font-weight:700;background:#F59E0B;color:#FFFFFF;border-radius:999px;padding:1px 8px}
.ogdx-nav hr{border:0;border-top:1px solid var(--ln);margin:18px 6px}
.ogdx-main{padding:26px 28px 40px;min-width:0;max-width:1600px}
.ogdx-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:22px}
.ogdx-head h1{font-size:28px;font-weight:700;line-height:1.25;padding:0}
.ogdx-head p{font-size:15px;color:var(--mu);margin-top:6px!important}
.ogdx-export{position:relative}
.ogdx-export summary{display:flex;align-items:center;gap:8px;list-style:none;cursor:pointer;background:var(--a);color:#FFFFFF;font-weight:600;font-size:15px;padding:11px 16px;border-radius:12px;user-select:none}
.ogdx-export summary::-webkit-details-marker{display:none}
.ogdx-export summary:hover{background:var(--a2)}
.ogdx-export div{position:absolute;inset-inline-end:0;top:calc(100% + 6px);background:#FFFFFF;border:1px solid var(--ln);border-radius:12px;box-shadow:0 16px 36px rgba(21,23,28,.12);padding:6px;min-width:180px;z-index:20}
.ogdx-export div a{display:block;padding:10px 12px;border-radius:8px;color:var(--ink);font-weight:500}
.ogdx-export div a:hover{background:#F4F5F7;color:var(--a)}
.ogdx-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:18px}
.ogdx-kpi{display:flex;gap:14px;align-items:flex-start;background:#FFFFFF;border:1px solid var(--ln);border-radius:18px;padding:18px;color:var(--ink);transition:box-shadow .15s,transform .15s}
a.ogdx-kpi:hover{box-shadow:0 10px 26px rgba(21,23,28,.07);transform:translateY(-1px)}
.ogdx-kpi-ic{width:42px;height:42px;flex:none;border-radius:12px;display:flex;align-items:center;justify-content:center}
.ogdx-kpi>span:last-child{display:flex;flex-direction:column;min-width:0}
.ogdx-kpi small{font-size:13px;color:var(--mu)}
.ogdx-kpi b{font-size:26px;font-weight:700;line-height:1.25;margin-top:2px}
.ogdx-kpi em{font-style:normal;font-size:12.5px;color:var(--su);margin-top:2px}
.ogdx-kpi.is-amber .ogdx-kpi-ic{background:#FEF3C7;color:#B45309}
.ogdx-kpi.is-green .ogdx-kpi-ic{background:#DCFCE7;color:#15803D}
.ogdx-kpi.is-blue .ogdx-kpi-ic{background:var(--as);color:var(--a)}
.ogdx-kpi.is-violet .ogdx-kpi-ic{background:#EDE9FE;color:#6D28D9}
.ogdx-filters{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:18px}
.ogdx-filters label{display:flex;flex-direction:column;gap:7px}
.ogdx-filters label span{font-size:13px;color:var(--mu)}
.ogdx-filters select,.ogdx-filters input{width:100%;max-width:none;height:46px;border:1px solid var(--ln);border-radius:12px;background:#FFFFFF;padding:0 14px;font-size:14px;font-weight:500;color:var(--ink);box-shadow:none}
.ogdx-filters select:focus,.ogdx-filters input:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(53,99,233,.12)}
.ogdx-cols{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:18px;align-items:start}
.ogdx-cols-even{grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);margin-bottom:18px}
.ogdx-card{background:#FFFFFF;border:1px solid var(--ln);border-radius:20px;padding:22px}
.ogdx-card-h{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:18px}
.ogdx-card-h h2{font-size:19px;font-weight:700;line-height:1.35;margin:0;padding:0}
.ogdx-card-h p{font-size:13px;color:var(--mu);margin-top:3px!important}
.ogdx-ghost{display:inline-flex;align-items:center;gap:8px;border:1.5px solid var(--a);color:var(--a);background:#FFFFFF;border-radius:12px;padding:8px 14px;font-weight:600;font-size:14px;cursor:pointer}
.ogdx-ghost:hover{background:var(--as)}
.ogdx-link{color:var(--a);font-weight:600;font-size:14px}
.ogdx-cars{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px}
.ogdx-car{background:#FAFBFC;border:1px solid var(--ln);border-radius:18px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .15s,opacity .2s}
.ogdx-car[hidden]{display:none}
.ogdx-car:hover{box-shadow:0 12px 28px rgba(21,23,28,.08)}
.ogdx-car.is-dim{opacity:.5}
.ogdx-car-img{position:relative;display:block;aspect-ratio:16/10;background:linear-gradient(135deg,#EEF0F3,#E2E5EA)}
.ogdx-car-img img{width:100%;height:100%;object-fit:cover;display:block}
.ogdx-noimg{display:flex;width:100%;height:100%;align-items:center;justify-content:center;color:var(--su)}
.ogdx-badge{position:absolute;top:10px;inset-inline-start:10px;font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;background:#FFFFFF}
.ogdx-badge.is-free{color:#15803D;background:#DCFCE7}
.ogdx-badge.is-busy{color:#B45309;background:#FEF3C7}
.ogdx-badge.is-draft{color:#4B5563;background:#F3F4F6}
.ogdx-car-body{padding:14px 16px 16px;display:flex;flex-direction:column;gap:10px;flex:1}
.ogdx-car-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.ogdx-car-top h3{font-size:15.5px;font-weight:700;line-height:1.4;margin:0}
.ogdx-car-top h3 a{color:var(--ink)}
.ogdx-car-top h3 a:hover{color:var(--a)}
.ogdx-acts{display:flex;gap:6px;flex:none}
.ogdx-acts a{width:32px;height:32px;border-radius:9px;border:1px solid var(--ln);background:#FFFFFF;color:#4B5563;display:flex;align-items:center;justify-content:center}
.ogdx-acts a:hover{color:var(--a);border-color:var(--a)}
.ogdx-specs{display:flex;flex-wrap:wrap;gap:4px 14px;font-size:13px}
.ogdx-specs div{display:flex;gap:4px}
.ogdx-specs dt{color:var(--su)}
.ogdx-specs dd{color:var(--ink);font-weight:500}
.ogdx-car-foot{display:flex;justify-content:space-between;align-items:baseline;gap:8px;margin-top:auto;padding-top:10px;border-top:1px solid var(--ln)}
.ogdx-car-foot b{font-size:19px;font-weight:700;color:var(--a)}
.ogdx-car-foot small{font-size:12.5px;color:var(--su);font-weight:400}
.ogdx-next{font-size:12px;color:var(--mu)}
.ogdx-warn{font-size:13px;color:#B91C1C}
.ogdx-act{position:sticky;top:52px}
.ogdx-feed{display:grid;gap:8px;max-height:330px;overflow:auto;margin:0 -6px 16px!important;padding:0 6px!important}
.ogdx-feed li{margin:0}
.ogdx-feed-i{width:100%;display:flex;align-items:center;gap:12px;text-align:start;background:#FFFFFF;border:1px solid var(--ln);border-radius:14px;padding:10px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.ogdx-feed-i:hover{border-color:#D5D9E0}
.ogdx-feed-i.is-on{border-color:var(--a);box-shadow:0 0 0 3px rgba(53,99,233,.1)}
.ogdx-thumb{width:56px;height:44px;flex:none;border-radius:10px;overflow:hidden;background:#F1F3F6;display:flex;align-items:center;justify-content:center;color:var(--su)}
.ogdx-thumb img{width:100%;height:100%;object-fit:cover}
.ogdx-feed-t{display:flex;flex-direction:column;min-width:0;flex:1;gap:1px}
.ogdx-feed-t b{font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ogdx-feed-t small{display:flex;align-items:center;gap:4px;font-size:12px;color:var(--mu)}
.ogdx-feed-r{display:flex;flex-direction:column;align-items:flex-end;gap:4px}
.ogdx-feed-r b{font-size:14px;color:var(--a)}
.ogdx-st{font-style:normal;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;white-space:nowrap}
.ogdx-st.is-pending{background:#FEF3C7;color:#B45309}
.ogdx-st.is-confirmed{background:#DCFCE7;color:#15803D}
.ogdx-st.is-completed{background:#DBEAFE;color:#1D4ED8}.ogdx-st.is-onroad{background:#EDE9FE;color:#6D28D9}
.ogdx-st.is-declined{background:#FEE2E2;color:#B91C1C}
.ogdx-st.is-cancelled{background:#F3F4F6;color:#4B5563}
.ogdx-info{border:1px solid var(--ln);border-radius:16px;padding:16px;background:#FAFBFC}
.ogdx-info[hidden]{display:none}
.ogdx-info-h{display:flex;justify-content:space-between;align-items:center;font-size:14px;font-weight:600;color:var(--mu);margin-bottom:14px}
.ogdx-person{display:flex;align-items:center;gap:12px}
.ogdx-avatar{width:44px;height:44px;flex:none;border-radius:50%;background:linear-gradient(135deg,#3563E9,#5A5BE6);color:#FFFFFF;font-size:18px;font-weight:700;display:flex;align-items:center;justify-content:center}
.ogdx-person b{display:block;font-size:15px}
.ogdx-person small{display:block;font-size:12.5px;color:var(--mu);word-break:break-all}
.ogdx-route{margin:18px 0 6px}
.ogdx-route-line{display:flex;align-items:center;gap:6px}
.ogdx-route-line i{width:14px;height:14px;flex:none;border-radius:50%;background:var(--a)}
.ogdx-route-line i.b{border-radius:4px}
.ogdx-route-line span{flex:1;height:2px;background:repeating-linear-gradient(90deg,#C7D2FE 0 6px,transparent 6px 10px)}
.ogdx-route-pts{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px}
.ogdx-route-pts div:last-child{text-align:end}
.ogdx-route-pts small{display:block;font-size:11.5px;color:var(--su)}
.ogdx-route-pts b{display:block;font-size:13px;margin-top:2px}
.ogdx-route-pts span{display:block;font-size:12px;color:var(--mu);margin-top:2px}
.ogdx-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px 10px;padding:14px 0;margin-top:12px!important;border-top:1px solid var(--ln);border-bottom:1px solid var(--ln)}
.ogdx-facts dt{font-size:11.5px;color:var(--su)}
.ogdx-facts dd{font-size:13.5px;font-weight:600;margin-top:2px}
.ogdx-note{font-size:13px;color:#4B5563;background:#FFFFFF;border-radius:10px;padding:10px 12px;margin-top:12px!important;line-height:1.6}
.ogdx-info-acts{display:flex;align-items:center;gap:8px;margin-top:14px}
.ogdx-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:40px;padding:0 16px;border-radius:12px;font-weight:600;font-size:14px;background:var(--a);color:#FFFFFF;border:0}
.ogdx-btn:hover{background:var(--a2);color:#FFFFFF}
.ogdx-btn.is-ok{background:#16A34A}.ogdx-btn.is-ok:hover{background:#15803D}
.ogdx-btn.is-no{background:#FFFFFF;color:#B91C1C;border:1px solid #FECACA}.ogdx-btn.is-no:hover{background:#FEF2F2;color:#B91C1C}
.ogdx-btn.is-soft{background:var(--as);color:var(--a)}.ogdx-btn.is-soft:hover{background:#DCE6FB;color:var(--a)}
.ogdx-info-acts .ogdx-btn{flex:1}
.ogdx-round{width:40px;height:40px;flex:none;border-radius:50%;border:1px solid var(--ln);background:#FFFFFF;color:var(--ink);display:flex;align-items:center;justify-content:center}
.ogdx-round:hover{border-color:var(--a);color:var(--a)}
.ogdx-empty{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;padding:40px 16px;color:var(--su)}
.ogdx-empty[hidden]{display:none}
.ogdx-empty b{font-size:16px;color:var(--ink)}
.ogdx-empty span{font-size:13.5px;color:var(--mu);max-width:300px;line-height:1.6}
.ogdx-empty .ogdx-btn{margin-top:8px}
.ogdx-pager{display:flex;gap:6px}
.ogdx-pager a{height:38px;min-width:38px;padding:0 12px;border:1px solid var(--ln);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--ink);font-weight:600;background:#FFFFFF}
.ogdx-pager a:hover{border-color:var(--a);color:var(--a)}
.ogdx-legend{display:flex;flex-wrap:wrap;gap:14px;font-size:12.5px;color:var(--mu);margin-top:8px!important}
.ogdx-legend span{display:inline-flex;align-items:center;gap:6px}
.ogdx-legend i{width:12px;height:12px;border-radius:4px;display:inline-block}
.ogdx-legend i.is-confirmed,.ogdx-bar.is-confirmed,.ogdx-stack .is-confirmed{background:#22C55E}
.ogdx-legend i.is-pending,.ogdx-bar.is-pending,.ogdx-stack .is-pending{background:#F59E0B}
.ogdx-legend i.is-completed,.ogdx-bar.is-completed,.ogdx-stack .is-completed{background:#3B82F6}.ogdx-legend i.is-onroad,.ogdx-bar.is-onroad,.ogdx-stack .is-onroad{background:#8B5CF6}
.ogdx-legend i.is-declined,.ogdx-stack .is-declined{background:#EF4444}
.ogdx-legend i.is-cancelled,.ogdx-stack .is-cancelled{background:#9CA3AF}
.ogdx-legend i.is-block,.ogdx-bar.is-block{background:repeating-linear-gradient(-45deg,#CBD0D8 0 4px,#E5E7EB 4px 8px)}
.ogdx-tl-wrap{overflow-x:auto;margin:0 -22px -22px;padding:0 22px 22px}
.ogdx-tl{min-width:1100px}
.ogdx-tl-row{display:grid;grid-template-columns:220px minmax(0,1fr);border-bottom:1px solid var(--ln);min-height:58px;align-items:center}
.ogdx-tl-head{display:grid;grid-template-columns:220px repeat(var(--n),minmax(0,1fr));min-height:50px;position:sticky;top:0;background:#FFFFFF;z-index:2}
.ogdx-tl-head .ogdx-tl-car{font-size:12.5px;color:var(--su);font-weight:600}
.ogdx-tl-day{text-align:center;display:flex;flex-direction:column;line-height:1.2;padding:6px 0}
.ogdx-tl-day small{font-size:10.5px;color:var(--su)}
.ogdx-tl-day b{font-size:13px;font-weight:600}
.ogdx-tl-day.is-we{background:#FAFBFC}
.ogdx-tl-day.is-today b{background:var(--a);color:#FFFFFF;border-radius:999px;width:24px;height:24px;line-height:24px;margin:2px auto 0}
.ogdx-tl-car{display:flex;align-items:center;gap:10px;padding-inline-end:12px;color:var(--ink);min-width:0}
.ogdx-tl-car .ogdx-thumb{width:44px;height:34px}
.ogdx-tl-car b{display:block;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px}
.ogdx-tl-car small{font-size:11px;color:var(--su)}
.ogdx-tl-track{position:relative;display:grid;grid-template-columns:repeat(var(--n),minmax(0,1fr));height:100%;min-height:58px;align-items:center;background-image:linear-gradient(90deg,var(--ln) 1px,transparent 1px);background-size:calc(100% / var(--n)) 100%}
.ogdx-tl-now{grid-row:1;height:100%;background:rgba(53,99,233,.07)}
.ogdx-bar{grid-row:1;height:30px;margin:0 2px;border-radius:9px;color:#FFFFFF;font-size:11.5px;font-weight:600;display:flex;align-items:center;padding:0 8px;overflow:hidden;z-index:1}
.ogdx-bar span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ogdx-bar.is-block{color:#4B5563}
.ogdx-bar:hover{filter:brightness(.95);color:#FFFFFF}
.ogdx-bar.is-block:hover{color:#15171C}
.ogdx-bar.cut-s{border-start-start-radius:0;border-end-start-radius:0;margin-inline-start:0}
.ogdx-bar.cut-e{border-start-end-radius:0;border-end-end-radius:0;margin-inline-end:0}
.ogdx-bars{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px;height:280px;align-items:end}
.ogdx-bcol{display:flex;flex-direction:column;align-items:center;gap:8px;height:100%}
.ogdx-bval{font-size:12.5px;font-weight:600;color:var(--ink)}
.ogdx-btrack{flex:1;width:100%;max-width:56px;background:#F4F5F7;border-radius:12px;display:flex;align-items:flex-end;overflow:hidden}
.ogdx-bfill{width:100%;background:#BFD0F7;border-radius:12px;transition:height .4s}
.ogdx-bfill.is-now{background:var(--a)}
.ogdx-blbl{display:flex;flex-direction:column;align-items:center;font-size:13px;font-weight:600}
.ogdx-blbl small{font-size:11px;color:var(--su);font-weight:400}
.ogdx-rank{display:grid;gap:12px}
.ogdx-rank li{display:flex;align-items:center;gap:12px;margin:0}
.ogdx-rank-t{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px}
.ogdx-rank-t b{font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ogdx-rank-t small{font-size:12px;color:var(--mu)}
.ogdx-rank-t i{display:block;height:6px;border-radius:999px;background:var(--a);opacity:.85}
.ogdx-rank li>b{font-size:15px;color:var(--a)}
.ogdx-stack{display:flex;height:18px;border-radius:999px;overflow:hidden;gap:3px;background:#F4F5F7}
.ogdx-stack .is-empty{background:#F4F5F7}
@media (max-width:1400px){.ogdx-filters{grid-template-columns:repeat(3,minmax(0,1fr))}.ogdx-cols{grid-template-columns:minmax(0,1fr) 340px}}
@media (max-width:1200px){.ogdx-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.ogdx-cols,.ogdx-cols-even{grid-template-columns:1fr}.ogdx-act{position:static}}
@media (max-width:960px){.ogdx{grid-template-columns:1fr}.ogdx-nav{position:static;height:auto;display:flex;flex-wrap:wrap;gap:6px;padding:12px}.ogdx-nav-h,.ogdx-nav hr{display:none}.ogdx-nav a{margin:0;padding:9px 12px;font-size:14px}.ogdx-main{padding:18px 14px 32px}}
@media (max-width:600px){.ogdx-kpis,.ogdx-filters{grid-template-columns:1fr}.ogdx-head{flex-direction:column}.ogdx-facts{grid-template-columns:1fr 1fr}}
@media (prefers-reduced-motion:reduce){.ogdx *{transition:none!important}}
CSS;
}

/* ------------------------------------------------------------------ */
/* "Your Cars" menu = the app menu                                    */
/* ------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	global $submenu;
	$p = 'edit.php?post_type=orego_car';
	if ( empty( $submenu[ $p ] ) ) {
		return;
	}
	$dash   = 'edit.php?post_type=orego_car&page=orego-dashboard';
	$n      = count( get_posts( array( 'post_type' => 'orego_booking', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => 'og_bstatus', 'meta_value' => 'pending' ) ) );
	$bubble = $n ? ' <span class="awaiting-mod"><span class="pending-count">' . (int) $n . '</span></span>' : '';
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$submenu[ $p ] = array(
		array( orego_L( 'Dashboard', 'لوحة التحكم' ), 'edit_posts', 'orego-dashboard', orego_L( 'Dashboard', 'لوحة التحكم' ) ),
		array( orego_L( 'Listing', 'قائمة السيارات' ), 'edit_posts', 'orego-listing', orego_L( 'Listing', 'قائمة السيارات' ) ),
		array( orego_L( 'Calendar', 'التقويم' ), 'edit_posts', $dash . '&tab=calendar', orego_L( 'Fleet Calendar', 'تقويم الأسطول' ) ),
		array( orego_L( 'Bookings', 'الحجوزات' ) . $bubble, 'edit_posts', 'orego-bookings', orego_L( 'Bookings', 'الحجوزات' ) ),
		array( orego_L( 'Statistics', 'الإحصائيات' ), 'edit_posts', $dash . '&tab=stats', orego_L( 'Statistics', 'الإحصائيات' ) ),
		array( orego_L( 'Add Car', 'إضافة سيارة' ), 'edit_posts', 'post-new.php?post_type=orego_car', orego_L( 'Add Car', 'إضافة سيارة' ) ),
		array( orego_L( 'Car Types', 'فئات السيارات' ), 'manage_categories', 'edit-tags.php?taxonomy=car_type&post_type=orego_car', orego_L( 'Car Types', 'فئات السيارات' ) ),
		array( orego_L( 'Pickup Locations', 'مواقع الاستلام' ), 'manage_options', 'orego-places', orego_L( 'Pickup Locations', 'مواقع الاستلام' ) ),
		array( orego_L( 'Currencies', 'العملات' ), 'manage_options', 'orego-currency', orego_L( 'Currencies', 'العملات' ) ),
	);
	$submenu[ $p ] = apply_filters( 'orego_app_menu', $submenu[ $p ] ); // phpcs:ignore
}, 1000 );

/* Highlight Calendar / Statistics when those tabs are open */
add_filter( 'submenu_file', function ( $submenu_file ) {
	if ( isset( $_GET['page'] ) && 'orego-dashboard' === $_GET['page'] ) { // phpcs:ignore
		$tab = sanitize_key( $_GET['tab'] ?? '' ); // phpcs:ignore
		if ( in_array( $tab, array( 'calendar', 'stats' ), true ) ) {
			return 'edit.php?post_type=orego_car&page=orego-dashboard&tab=' . $tab;
		}
		return 'orego-dashboard';
	}
	return $submenu_file;
} );
