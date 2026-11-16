<?php
/**
 * Booking -> Handover -> Pick-up: the customer's documents.
 * - ID card or passport (type, number, photos) and driving licence (number, photos).
 * - Photos and PDFs are stored in a locked folder (wp-content/uploads/orego-private/<booking>/),
 *   never in the media library, and can only be opened by logged-in staff.
 * - They are deleted with the booking when it is deleted permanently.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_docs_dir( $id = 0 ) {
	$up   = wp_upload_dir( null, false );
	$base = trailingslashit( $up['basedir'] ) . 'orego-private';
	if ( ! is_dir( $base ) ) {
		wp_mkdir_p( $base );
	}
	if ( ! file_exists( $base . '/.htaccess' ) ) {
		file_put_contents( $base . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore
	}
	if ( ! file_exists( $base . '/index.php' ) ) {
		file_put_contents( $base . '/index.php', "<?php // Silence.\n" ); // phpcs:ignore
	}
	if ( ! $id ) {
		return $base;
	}
	$dir = $base . '/' . (int) $id;
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
		file_put_contents( $dir . '/index.php', "<?php // Silence.\n" ); // phpcs:ignore
	}
	return $dir;
}

function orego_docs_list( $id ) {
	return function_exists( 'orego_json_meta' ) ? orego_json_meta( $id, 'og_docs' ) : array();
}

function orego_doc_url( $id, $fid ) {
	return wp_nonce_url( add_query_arg( array( 'action' => 'orego_doc_view', 'id' => (int) $id, 'f' => $fid ), admin_url( 'admin-post.php' ) ), 'orego_doc_' . $id . '_' . $fid );
}

/* Fields shown in the Pick-up form */
add_action( 'orego_handover_docs', function ( $id, $out ) {
	$docs  = orego_docs_list( $id );
	$group = function ( $kind ) use ( $id, $docs ) {
		$h = '';
		foreach ( $docs as $d ) {
			if ( $d['kind'] !== $kind ) {
				continue;
			}
			$url = orego_doc_url( $id, $d['id'] );
			$img = 0 === strpos( $d['mime'], 'image/' );
			$h  .= '<div class="ogho-file">'
				. '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" title="' . esc_attr( $d['name'] ) . '">' . ( $img ? '<img src="' . esc_url( $url ) . '" alt="">' : '<span class="ogho-pdf">PDF</span>' ) . '</a>'
				. '<label class="ogho-del" title="Remove"><input type="checkbox" name="og_doc_del[]" value="' . esc_attr( $d['id'] ) . '"><span>Remove</span></label>'
				. '</div>';
		}
		return $h ? '<div class="ogho-files">' . $h . '</div>' : '';
	};
	$type = $out['id_type'] ?? 'id';
	?>
	<div class="ogho-docs">
		<h4>Customer documents</h4>
		<div class="ogho-grid">
			<div class="ogho-box">
				<div class="ogho-row">
					<label><span>Document</span><select name="id_type"><option value="id" <?php selected( $type, 'id' ); ?>>National ID card</option><option value="passport" <?php selected( $type, 'passport' ); ?>>Passport</option></select></label>
					<label><span>Number</span><input type="text" name="id_no" maxlength="40" value="<?php echo esc_attr( $out['id_no'] ?? '' ); ?>" placeholder="e.g. AB123456"></label>
				</div>
				<?php echo $group( 'id' ); // phpcs:ignore ?>
				<label class="ogho-up"><input type="file" name="og_doc_id[]" accept="image/*,application/pdf" multiple><span>+ Add photos (front &amp; back)</span></label>
			</div>
			<div class="ogho-box">
				<div class="ogho-row">
					<label><span>Driving licence number</span><input type="text" name="lic_no" maxlength="40" value="<?php echo esc_attr( $out['lic_no'] ?? ( $out['doc'] ?? '' ) ); ?>" placeholder="e.g. 12/345678"></label>
					<label><span>Valid until</span><input type="date" name="lic_exp" value="<?php echo esc_attr( $out['lic_exp'] ?? '' ); ?>"></label>
				</div>
				<?php echo $group( 'lic' ); // phpcs:ignore ?>
				<label class="ogho-up"><input type="file" name="og_doc_lic[]" accept="image/*,application/pdf" multiple><span>+ Add photos (front &amp; back)</span></label>
			</div>
		</div>
		<p class="ogu-mute">Photos and PDFs are private: only logged-in staff can open them. Max 10 MB each.</p>
	</div>
	<?php
}, 10, 2 );

/* Save: document numbers, removed files, new uploads */
add_action( 'orego_handover_saved', function ( $id, $which ) {
	if ( 'out' !== $which ) {
		return;
	}
	$docs = orego_docs_list( $id );
	$del  = array_map( 'sanitize_key', (array) ( $_POST['og_doc_del'] ?? array() ) ); // phpcs:ignore
	if ( $del ) {
		$dir  = orego_docs_dir( $id );
		$docs = array_values( array_filter( $docs, function ( $d ) use ( $del, $dir ) {
			if ( in_array( $d['id'], $del, true ) ) {
				@unlink( $dir . '/' . $d['file'] ); // phpcs:ignore
				return false;
			}
			return true;
		} ) );
	}
	foreach ( array( 'id' => 'og_doc_id', 'lic' => 'og_doc_lic' ) as $kind => $field ) {
		if ( empty( $_FILES[ $field ]['name'] ) || ! is_array( $_FILES[ $field ]['name'] ) ) {
			continue;
		}
		$f = $_FILES[ $field ]; // phpcs:ignore
		foreach ( $f['name'] as $i => $name ) {
			if ( UPLOAD_ERR_OK !== (int) $f['error'][ $i ] || ! is_uploaded_file( $f['tmp_name'][ $i ] ) || (int) $f['size'][ $i ] > 10 * MB_IN_BYTES ) {
				continue;
			}
			$chk  = wp_check_filetype_and_ext( $f['tmp_name'][ $i ], $name, array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'heic' => 'image/heic', 'pdf' => 'application/pdf' ) );
			if ( empty( $chk['ext'] ) || empty( $chk['type'] ) ) {
				continue;
			}
			$fid  = substr( bin2hex( random_bytes( 8 ) ), 0, 12 );
			$file = $fid . '-' . bin2hex( random_bytes( 6 ) ) . '.' . $chk['ext'];
			if ( @move_uploaded_file( $f['tmp_name'][ $i ], orego_docs_dir( $id ) . '/' . $file ) ) { // phpcs:ignore
				$docs[] = array(
					'id'   => $fid,
					'kind' => $kind,
					'file' => $file,
					'name' => mb_substr( sanitize_file_name( $name ), 0, 80 ),
					'mime' => $chk['type'],
					'at'   => current_time( 'mysql' ),
					'by'   => wp_get_current_user()->display_name,
				);
			}
		}
	}
	orego_set_json_meta( $id, 'og_docs', $docs );
}, 10, 2 );

/* Open a document (logged-in staff only) */
add_action( 'admin_post_orego_doc_view', function () {
	$id  = absint( $_GET['id'] ?? 0 );
	$fid = sanitize_key( $_GET['f'] ?? '' );
	if ( ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'orego_doc_' . $id . '_' . $fid ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	foreach ( orego_docs_list( $id ) as $d ) {
		if ( $d['id'] === $fid ) {
			$path = orego_docs_dir( $id ) . '/' . $d['file'];
			if ( ! is_file( $path ) ) {
				break;
			}
			nocache_headers();
			header( 'Content-Type: ' . $d['mime'] );
			header( 'Content-Length: ' . filesize( $path ) );
			header( 'Content-Disposition: inline; filename="' . rawurlencode( $d['name'] ) . '"' );
			header( 'X-Content-Type-Options: nosniff' );
			header( 'Cache-Control: private, no-store' );
			readfile( $path ); // phpcs:ignore
			exit;
		}
	}
	wp_die( 'Document not found.', 404 );
} );

/* Booking deleted permanently: delete its documents too */
add_action( 'before_delete_post', function ( $post_id ) {
	if ( 'orego_booking' !== get_post_type( $post_id ) ) {
		return;
	}
	$dir = orego_docs_dir() . '/' . (int) $post_id;
	if ( is_dir( $dir ) ) {
		foreach ( (array) glob( $dir . '/*' ) as $f ) {
			@unlink( $f ); // phpcs:ignore
		}
		@rmdir( $dir ); // phpcs:ignore
	}
} );

/* Styles: one wide Pick-up card */
add_action( 'admin_head', function () {
	?>
<style id="ogho-css">
#handover .ogo-ho{grid-template-columns:1fr!important}
#handover .ogo-ho>form{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px 18px;align-items:start}
#handover .ogo-ho>form>h3,#handover .ogo-ho>form>label:has(textarea),#handover .ogo-ho>form>.ogho-docs,#handover .ogo-ho>form>.ogu-check,#handover .ogo-ho>form>button,#handover .ogo-ho>form>p{grid-column:1/-1}
#handover .ogo-ho>form>button{justify-self:start;min-width:200px}
.ogho-docs{margin-top:4px;padding-top:16px;border-top:1px solid #E6E8EC}
.ogho-docs h4{margin:0 0 12px;font-size:15px;font-weight:700;color:#15171C}
.ogho-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.ogho-box{display:grid;gap:12px;padding:14px;border:1px solid #E6E8EC;border-radius:14px;background:#FFFFFF}
.ogho-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.ogho-row label{display:grid;gap:6px;margin:0}
.ogho-files{display:flex;flex-wrap:wrap;gap:10px}
.ogho-file{display:grid;gap:4px;width:104px}
.ogho-file a{display:block;width:104px;height:74px;border-radius:10px;overflow:hidden;background:#F3F4F6;box-shadow:0 0 0 1px #E6E8EC}
.ogho-file img{width:100%;height:100%;object-fit:cover;display:block}
.ogho-pdf{display:flex;align-items:center;justify-content:center;height:100%;font-weight:800;color:#B91C1C}
.ogho-del{display:flex;align-items:center;gap:5px;font-size:12px;color:#6B7280;cursor:pointer}
.ogho-del input{margin:0}
.ogho-up{position:relative;display:flex;align-items:center;justify-content:center;min-height:46px;border:1.5px dashed #C9CDD3;border-radius:12px;background:#F8F9FA;color:#15171C;font-weight:600;font-size:13.5px;cursor:pointer}
.ogho-up:hover{border-color:#15171C}
.ogho-up input{position:absolute;inset:0;opacity:0;cursor:pointer}
.ogho-up.has-files span{color:#15803D}
@media (max-width:1100px){#handover .ogo-ho>form{grid-template-columns:repeat(2,minmax(0,1fr))}.ogho-grid{grid-template-columns:1fr}}
@media (max-width:600px){#handover .ogo-ho>form,.ogho-row{grid-template-columns:1fr}}
</style>
<script>
document.addEventListener('change',function(e){var i=e.target;if(!i.matches||!i.matches('.ogho-up input'))return;var s=i.nextElementSibling,n=i.files.length;i.parentNode.classList.toggle('has-files',n>0);s.textContent=n?(n===1?'1 file ready to save':n+' files ready to save'):'+ Add photos (front & back)';});
</script>
	<?php
} );

/* ------------------------------------------------------------------ */
/* Helper kept for later use (not automatic)                          */
/* ------------------------------------------------------------------ */

/**
 * Customers pay the rest before they drive away: when a booking goes On road,
 * whatever is still owed is recorded as paid (method: cash, editable in Payments).
 */
function orego_record_paid_at_pickup( $id ) {
	if ( ! function_exists( 'orego_pay_summary' ) || ! function_exists( 'orego_json_meta' ) ) {
		return false;
	}
	$sum = orego_pay_summary( $id );
	if ( $sum['balance'] <= 0.009 ) {
		return false;
	}
	$list   = orego_json_meta( $id, 'og_payments' );
	$list[] = array(
		'id'     => substr( md5( uniqid( 'pk', true ) ), 0, 10 ),
		'type'   => 'payment',
		'amount' => round( $sum['balance'], 4 ),
		'method' => 'cash',
		'date'   => current_time( 'Y-m-d' ),
		'note'   => 'Paid at pick-up (recorded automatically)',
		'by'     => is_user_logged_in() ? wp_get_current_user()->display_name : 'Automatic',
		'at'     => current_time( 'mysql' ),
		'auto'   => 1,
	);
	orego_set_json_meta( $id, 'og_payments', $list );
	return true;
}

/* Not hooked: payments are recorded by hand on Confirmed bookings (owner's choice). */

/* ------------------------------------------------------------------ */
/* On road: view only, with how long the car has been out               */
/* ------------------------------------------------------------------ */

/* Times for an On road booking: since (pick-up), due back, days out, days late. */
function orego_onroad_times( $id ) {
	$i   = orego_bk_info( $id );
	$out = function_exists( 'orego_json_meta' ) ? orego_json_meta( $id, 'og_ho_out' ) : array();
	$tz  = wp_timezone();
	try {
		$since = new DateTimeImmutable( ! empty( $out['at'] ) ? str_replace( 'T', ' ', $out['at'] ) : $i['start'] . ' ' . ( $i['ptime'] ? $i['ptime'] : '10:00' ), $tz );
		$due   = new DateTimeImmutable( $i['end'] . ' ' . ( $i['rtime'] ? $i['rtime'] : '10:00' ), $tz );
	} catch ( Exception $e ) {
		return null;
	}
	$now = new DateTimeImmutable( 'now', $tz );
	return array(
		'since' => $since,
		'due'   => $due,
		'out'   => max( 0, $now->getTimestamp() - $since->getTimestamp() ),
		'left'  => $due->getTimestamp() - $now->getTimestamp(),
		'place' => $i['dropoff'] ? $i['dropoff'] : $i['pickup'],
		'days'  => max( 1, (int) $i['days'] ),
	);
}

function orego_span_words( $sec ) {
	$sec = abs( (int) $sec );
	$d   = intdiv( $sec, DAY_IN_SECONDS );
	$h   = intdiv( $sec % DAY_IN_SECONDS, HOUR_IN_SECONDS );
	if ( $d >= 1 ) {
		return $d . ( 1 === $d ? ' day' : ' days' ) . ( $h ? ' ' . $h . ' h' : '' );
	}
	$m = intdiv( $sec % HOUR_IN_SECONDS, MINUTE_IN_SECONDS );
	return $h ? $h . ' h' . ( $m ? ' ' . $m . ' min' : '' ) : max( 1, $m ) . ' min';
}

/* Small tag in the bookings list: "Day 3 of 5" or "2 days late". */
function orego_onroad_chip( $b ) {
	if ( 'onroad' !== ( $b['status'] ?? '' ) || empty( $b['id'] ) ) {
		return '';
	}
	$t = orego_onroad_times( (int) $b['id'] );
	if ( ! $t ) {
		return '';
	}
	if ( $t['left'] < 0 ) {
		return '<span class="ogro-chip is-late">Late · ' . esc_html( orego_span_words( $t['left'] ) ) . '</span>';
	}
	$day = min( $t['days'], intdiv( $t['out'], DAY_IN_SECONDS ) + 1 );
	return '<span class="ogro-chip">Day ' . (int) $day . ' of ' . (int) $t['days'] . ' · back in ' . esc_html( orego_span_words( $t['left'] ) ) . '</span>';
}

/* Booking page: summary card first, then the payment / handover cards in read-only. */
add_action( 'orego_booking_view_main', function ( $id, $b ) {
	if ( 'onroad' !== ( $b['status'] ?? '' ) ) {
		return;
	}
	$t = orego_onroad_times( $id );
	if ( ! $t ) {
		return;
	}
	$late = $t['left'] < 0;
	$pct  = max( 4, min( 100, round( 100 * $t['out'] / max( 1, $t['due']->getTimestamp() - $t['since']->getTimestamp() ) ) ) );
	?>
	<section class="ogdx-card ogro">
		<div class="ogdx-card-h"><h2>On the road</h2><i class="ogro-chip<?php echo $late ? ' is-late' : ''; ?>"><?php echo esc_html( $late ? 'Late by ' . orego_span_words( $t['left'] ) : 'Due back in ' . orego_span_words( $t['left'] ) ); ?></i></div>
		<div class="ogro-grid">
			<div><small>Out since</small><b><?php echo esc_html( wp_date( 'D, M j · H:i', $t['since']->getTimestamp() ) ); ?></b><em><?php echo esc_html( orego_span_words( $t['out'] ) ); ?> ago</em></div>
			<div><small>Due back</small><b><?php echo esc_html( wp_date( 'D, M j · H:i', $t['due']->getTimestamp() ) ); ?></b><em><?php echo esc_html( $t['place'] ); ?></em></div>
			<div><small>Rental</small><b><?php echo (int) $t['days'] . ( 1 === $t['days'] ? ' day' : ' days' ); ?></b><em><?php echo esc_html( $late ? 'Overdue' : 'Day ' . min( $t['days'], intdiv( $t['out'], DAY_IN_SECONDS ) + 1 ) . ' of ' . $t['days'] ); ?></em></div>
		</div>
		<div class="ogro-bar<?php echo $late ? ' is-late' : ''; ?>"><span style="width:<?php echo (int) $pct; ?>%"></span></div>
		<p class="ogu-mute">View only while the car is on the road. Mark the booking as Returned when it is back to add extra charges.</p>
	</section>
	<?php
}, 5, 2 );

add_action( 'orego_booking_view_main', function ( $id, $b ) {
	if ( 'onroad' === ( $b['status'] ?? '' ) ) {
		ob_start();
	}
}, 9, 2 );
add_action( 'orego_booking_view_main', function ( $id, $b ) {
	if ( 'onroad' !== ( $b['status'] ?? '' ) ) {
		return;
	}
	$h = ob_get_clean();
	echo str_replace( 'class="ogdx-card ogo"', 'class="ogdx-card ogo is-ro"', $h ); // phpcs:ignore
}, 11, 2 );

add_action( 'admin_head', function () {
	?>
<style id="ogro-css">
.ogro-chip{display:inline-flex;align-items:center;margin-top:5px;padding:3px 9px;border-radius:999px;background:#EDE9FE;color:#6D28D9;font-size:12px;font-weight:700;font-style:normal;white-space:nowrap}
.ogro-chip.is-late{background:#FEE2E2;color:#B91C1C}
.ogu-table .ogro-chip{display:table;margin-top:6px}
.ogro-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.ogro-grid>div{padding:14px 16px;border-radius:14px;background:#F6F7F9}
.ogro-grid small{display:block;font-size:12.5px;color:#6B7280}
.ogro-grid b{display:block;margin-top:4px;font-size:16px;color:#15171C}
.ogro-grid em{display:block;margin-top:2px;font-style:normal;font-size:13px;color:#6B7280}
.ogro-bar{height:8px;margin:16px 0 8px;border-radius:99px;background:#EDE9FE;overflow:hidden}
.ogro-bar span{display:block;height:100%;border-radius:99px;background:#8B5CF6}
.ogro-bar.is-late{background:#FEE2E2}.ogro-bar.is-late span{background:#DC2626}
/* read-only cards while on the road */
.ogo.is-ro form.ogo-row,.ogo.is-ro button[type=submit],.ogo.is-ro .ogo-x,.ogo.is-ro .ogho-up,.ogo.is-ro .ogho-del,.ogo.is-ro .ogu-check{display:none!important}
.ogo.is-ro input,.ogo.is-ro select,.ogo.is-ro textarea{pointer-events:none;background:#F6F7F9!important;border-color:#ECEEF1!important;color:#15171C!important;opacity:1}
@media (max-width:800px){.ogro-grid{grid-template-columns:1fr}}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.ogo.is-ro input,.ogo.is-ro select,.ogo.is-ro textarea').forEach(function(el){el.readOnly=true;el.setAttribute('tabindex','-1');if(el.tagName==='SELECT'||el.type==='file'||el.type==='checkbox')el.disabled=true;});});
</script>
	<?php
} );
