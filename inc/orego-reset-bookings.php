<?php
/**
 * Your Cars -> Reset bookings: start the booking system fresh, as if it was never used.
 * Deletes every booking (with its customer details, payments, notes and handovers) and the dates blocked by hand on cars.
 * Optional: maintenance blocks and contact-form messages. Cars, prices, brands, settings and pages are kept.
 * Safety: admins only, a backup CSV download, and the word RESET must be typed to confirm.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=orego_car', 'Reset bookings', 'Reset bookings', 'manage_options', 'orego-reset', 'orego_reset_page' );
}, 40 );

add_filter( 'orego_app_menu', function ( $items ) {
	if ( current_user_can( 'manage_options' ) ) {
		$items[] = array( 'Reset bookings', 'manage_options', 'orego-reset', 'Reset bookings' );
	}
	return $items;
}, 50 );

/** What a reset would remove. */
function orego_reset_counts() {
	global $wpdb;
	$by = array();
	foreach ( $wpdb->get_results( "SELECT pm.meta_value s, COUNT(*) n FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'og_bstatus' WHERE p.post_type = 'orego_booking' GROUP BY pm.meta_value" ) as $r ) { // phpcs:ignore
		$by[ $r->s ? $r->s : 'pending' ] = (int) $r->n;
	}
	$meta_cars = function ( $key ) use ( $wpdb ) {
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'orego_car' AND pm.meta_key = %s AND pm.meta_value NOT IN ('', 'a:0:{}', '[]')", $key ) ); // phpcs:ignore
	};
	return array(
		'bookings' => array_sum( $by ),
		'by'       => $by,
		'blocked'  => $meta_cars( 'og_booked' ),
		'maint'    => $meta_cars( 'og_maint' ),
		'messages' => post_type_exists( 'orego_message' ) ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'orego_message'" ) : 0, // phpcs:ignore
	);
}

function orego_reset_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$c    = orego_reset_counts();
	$done = isset( $_GET['og_reset_done'] ) ? json_decode( wp_unslash( (string) $_GET['og_reset_done'] ), true ) : null; // phpcs:ignore
	$err  = isset( $_GET['og_reset_err'] ) ? sanitize_text_field( wp_unslash( $_GET['og_reset_err'] ) ) : ''; // phpcs:ignore
	$sts  = function_exists( 'orego_bk_statuses' ) ? orego_bk_statuses() : array();
	?>
<div class="wrap ogrs">
	<h1>Reset bookings</h1>
	<p class="ogrs-lead">Start the booking system fresh, as if it had never been used. Your cars, prices, brands, discounts, settings and pages are not touched.</p>

	<?php if ( is_array( $done ) ) : ?>
		<div class="ogrs-ok"><b>Done. The booking system is fresh.</b> Deleted <?php echo (int) ( $done['bookings'] ?? 0 ); ?> bookings<?php echo ! empty( $done['blocked'] ) ? ', cleared blocked dates on ' . (int) $done['blocked'] . ' cars' : ''; ?><?php echo ! empty( $done['maint'] ) ? ', cleared maintenance on ' . (int) $done['maint'] . ' cars' : ''; ?><?php echo ! empty( $done['messages'] ) ? ', deleted ' . (int) $done['messages'] . ' messages' : ''; ?>.</div>
	<?php endif; ?>
	<?php if ( $err ) : ?>
		<div class="ogrs-bad"><?php echo esc_html( $err ); ?></div>
	<?php endif; ?>

	<div class="ogrs-card">
		<h2>What will be deleted</h2>
		<div class="ogrs-stats">
			<div><b><?php echo (int) $c['bookings']; ?></b><span>Bookings</span></div>
			<?php foreach ( $c['by'] as $k => $n ) : ?>
				<div class="is-sub"><b><?php echo (int) $n; ?></b><span><?php echo esc_html( $sts[ $k ][0] ?? ucfirst( $k ) ); ?></span></div>
			<?php endforeach; ?>
		</div>
		<p class="ogrs-note">With each booking: the customer's name, email and phone, notes, payments, pick-up / return records and the private "manage booking" link.</p>

		<div class="ogrs-backup">
			<div><b>1. Download a backup first</b><span>A spreadsheet (CSV) with every booking and customer detail. Keep it somewhere safe: it contains personal data.</span></div>
			<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=orego_reset_export' ), 'orego_reset_export' ) ); ?>">Download backup (CSV)</a>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ogrs-form" id="ogrs-form">
			<input type="hidden" name="action" value="orego_reset_bookings">
			<?php wp_nonce_field( 'orego_reset_bookings', 'ogrs_nonce' ); ?>
			<b class="ogrs-step">2. Choose what to clear</b>
			<label class="is-locked"><input type="checkbox" checked disabled> All bookings (<?php echo (int) $c['bookings']; ?>)</label>
			<label><input type="checkbox" name="blocked" value="1" checked> Dates blocked by hand on cars (<?php echo (int) $c['blocked'] . ( 1 === (int) $c['blocked'] ? ' car' : ' cars' ); ?>)</label>
			<label><input type="checkbox" name="maint" value="1"> Maintenance blocks (<?php echo (int) $c['maint'] . ( 1 === (int) $c['maint'] ? ' car' : ' cars' ); ?>)</label>
			<?php if ( post_type_exists( 'orego_message' ) ) : ?>
				<label><input type="checkbox" name="messages" value="1"> Contact form messages (<?php echo (int) $c['messages']; ?>)</label>
			<?php endif; ?>

			<b class="ogrs-step">3. Confirm</b>
			<p class="ogrs-warn">This cannot be undone. Type <code>RESET</code> to confirm.</p>
			<input type="text" name="confirm" id="ogrs-confirm" autocomplete="off" placeholder="RESET" class="ogrs-input">
			<button type="submit" class="button ogrs-go" id="ogrs-go" disabled>Reset the booking system</button>
		</form>
	</div>
</div>
<style>
.ogrs{max-width:760px}
.ogrs h1{font-size:26px;font-weight:700}
.ogrs-lead{font-size:14px;color:#50575E;margin:6px 0 18px}
.ogrs-card{background:#fff;border:1px solid #E2E4E7;border-radius:14px;padding:24px 26px}
.ogrs-card h2{margin:0 0 14px;font-size:17px}
.ogrs-stats{display:flex;flex-wrap:wrap;gap:10px}
.ogrs-stats div{display:flex;flex-direction:column;min-width:96px;padding:12px 14px;border-radius:12px;background:#F6F7F7}
.ogrs-stats div b{font-size:24px;line-height:1.1}
.ogrs-stats div span{font-size:12px;color:#646970;margin-top:3px}
.ogrs-stats .is-sub b{font-size:18px}
.ogrs-note{color:#646970;font-size:13px;margin:12px 0 0}
.ogrs-backup{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:22px 0 0;padding:16px 18px;border-radius:12px;background:#F0F6FC;border:1px solid #C5D9ED}
.ogrs-backup div{display:flex;flex-direction:column;gap:4px}
.ogrs-backup span{font-size:13px;color:#50575E}
.ogrs-form{display:flex;flex-direction:column;gap:10px;margin-top:22px}
.ogrs-step{margin-top:6px;font-size:14px}
.ogrs-form label{display:flex;align-items:center;gap:8px;font-size:14px}
.ogrs-form label.is-locked{color:#50575E}
.ogrs-warn{margin:0;color:#B32D2E;font-weight:600}
.ogrs-input{max-width:220px;height:40px;font-size:15px;letter-spacing:.08em}
.ogrs .ogrs-go{align-self:flex-start;height:42px;padding:0 20px;background:#B32D2E;border-color:#B32D2E;color:#fff;font-weight:600}
.ogrs .ogrs-go:hover,.ogrs .ogrs-go:focus{background:#8A2424;border-color:#8A2424;color:#fff}
.ogrs .ogrs-go:disabled{background:#E2A3A3!important;border-color:#E2A3A3!important;color:#fff!important;cursor:not-allowed}
.ogrs-ok{margin:0 0 16px;padding:12px 16px;border-radius:10px;background:#EDFAEF;border:1px solid #9CD7A6;font-size:14px}
.ogrs-bad{margin:0 0 16px;padding:12px 16px;border-radius:10px;background:#FCF0F1;border:1px solid #E6A1A4;font-size:14px}
@media (max-width:600px){.ogrs-backup{flex-direction:column;align-items:flex-start}}
</style>
<script>
(function(){var i=document.getElementById('ogrs-confirm'),b=document.getElementById('ogrs-go'),f=document.getElementById('ogrs-form');if(!i||!b||!f)return;
i.addEventListener('input',function(){b.disabled=i.value.trim()!=='RESET';});
f.addEventListener('submit',function(e){if(i.value.trim()!=='RESET'||!window.confirm('Delete all bookings now? This cannot be undone.')){e.preventDefault();}});})();
</script>
	<?php
}

/* Backup: every booking as a CSV file. */
add_action( 'admin_post_orego_reset_export', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'orego_reset_export' ) ) {
		wp_die( 'Not allowed.' );
	}
	$ids = get_posts( array( 'post_type' => 'orego_booking', 'post_status' => array_keys( get_post_stati() ), 'numberposts' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=bookings-backup-' . wp_date( 'Y-m-d-His' ) . '.csv' );
	$out  = fopen( 'php://output', 'w' );
	$cols = array( 'og_bstatus', 'og_car', 'og_start', 'og_end', 'og_ptime', 'og_rtime', 'og_pickup', 'og_dropoff', 'og_name', 'og_email', 'og_phone', 'og_notes', 'og_days', 'og_price_day', 'og_total', 'og_deposit', 'og_cur', 'og_admin_note' );
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore -- lets Excel read accents
	fputcsv( $out, array_merge( array( 'booking', 'created', 'car_name' ), $cols ) );
	foreach ( $ids as $id ) {
		$row = array( $id, get_post_field( 'post_date', $id ), get_the_title( (int) get_post_meta( $id, 'og_car', true ) ) );
		foreach ( $cols as $k ) {
			$v     = get_post_meta( $id, $k, true );
			$row[] = is_scalar( $v ) ? $v : wp_json_encode( $v );
		}
		fputcsv( $out, $row );
	}
	fclose( $out ); // phpcs:ignore
	exit;
} );

/* The reset itself. */
add_action( 'admin_post_orego_reset_bookings', function () {
	$back = admin_url( 'edit.php?post_type=orego_car&page=orego-reset' );
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['ogrs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ogrs_nonce'] ) ), 'orego_reset_bookings' ) ) { // phpcs:ignore
		wp_die( 'Not allowed.' );
	}
	if ( 'RESET' !== trim( (string) wp_unslash( $_POST['confirm'] ?? '' ) ) ) { // phpcs:ignore
		wp_safe_redirect( add_query_arg( 'og_reset_err', rawurlencode( 'Nothing was deleted: type RESET to confirm.' ), $back ) );
		exit;
	}
	@set_time_limit( 300 ); // phpcs:ignore
	$done = array( 'bookings' => 0, 'blocked' => 0, 'maint' => 0, 'messages' => 0 );
	$c    = orego_reset_counts();

	// Bookings (every status, also in the trash), with all their data.
	do {
		$ids = get_posts( array( 'post_type' => 'orego_booking', 'post_status' => array_keys( get_post_stati() ), 'numberposts' => 200, 'fields' => 'ids' ) );
		foreach ( $ids as $id ) {
			if ( wp_delete_post( $id, true ) ) {
				$done['bookings']++;
			}
		}
	} while ( $ids );

	if ( ! empty( $_POST['blocked'] ) ) { // phpcs:ignore
		delete_post_meta_by_key( 'og_booked' );
		$done['blocked'] = $c['blocked'];
	}
	if ( ! empty( $_POST['maint'] ) ) { // phpcs:ignore
		delete_post_meta_by_key( 'og_maint' );
		$done['maint'] = $c['maint'];
	}
	if ( ! empty( $_POST['messages'] ) && post_type_exists( 'orego_message' ) ) { // phpcs:ignore
		foreach ( get_posts( array( 'post_type' => 'orego_message', 'post_status' => array_keys( get_post_stati() ), 'numberposts' => -1, 'fields' => 'ids' ) ) as $mid ) {
			if ( wp_delete_post( $mid, true ) ) {
				$done['messages']++;
			}
		}
	}

	delete_option( 'orego_last_summary' );
	wp_cache_flush();
	do_action( 'litespeed_purge_all' );
	wp_safe_redirect( add_query_arg( 'og_reset_done', rawurlencode( wp_json_encode( $done ) ), $back ) );
	exit;
} );
