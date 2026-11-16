<?php
/**
 * Online deposit by card (Stripe Checkout).
 * - Your Cars -> Online payments: turn it on, choose the deposit (% of the rental) and paste your Stripe keys.
 * - Booking page: "Pay a deposit now by card" or "Pay at pick-up". With the deposit, the customer goes to
 *   Stripe's secure payment page right after sending the booking, then comes back to their booking page.
 * - The payment is added to the booking's payments (Your Cars -> Bookings -> a booking) automatically,
 *   from the return page and from Stripe's webhook (whichever comes first, never twice).
 * Card details never touch this site: Stripe hosts the payment page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_pay_cfg() {
	$c = get_option( 'orego_pay' );
	$c = is_array( $c ) ? $c : array();
	$c = wp_parse_args( $c, array(
		'enabled' => 0,
		'pct'     => 20,
		'mode'    => 'test',
		'pk_test' => '',
		'sk_test' => '',
		'pk_live' => '',
		'sk_live' => '',
		'whsec'   => '',
		'default' => 'deposit',
	) );
	$c['pct'] = min( 100, max( 1, (float) $c['pct'] ) );
	return $c;
}

function orego_pay_secret() {
	$c = orego_pay_cfg();
	return 'live' === $c['mode'] ? (string) $c['sk_live'] : (string) $c['sk_test'];
}

/* Online deposits are on, and a secret key is set for the current mode. */
function orego_pay_ready() {
	$c = orego_pay_cfg();
	return ! empty( $c['enabled'] ) && '' !== orego_pay_secret();
}

/* Deposit for a booking, in the booking's currency: [amount, currency code]. */
function orego_pay_deposit( $id ) {
	$total = (float) get_post_meta( $id, 'og_total', true ); // USD
	$rate  = (float) get_post_meta( $id, 'og_rate', true );
	$rate  = $rate > 0 ? $rate : 1;
	$code  = strtoupper( (string) get_post_meta( $id, 'og_cur', true ) );
	$code  = $code ? $code : 'USD';
	$amt   = $total * $rate * orego_pay_cfg()['pct'] / 100;
	$zero  = in_array( $code, array( 'JPY', 'KRW', 'VND', 'CLP', 'XOF', 'XAF' ), true );
	return array( $zero ? round( $amt ) : round( $amt, 2 ), $code, $zero );
}

function orego_pay_deposit_label( $id ) {
	list( $amt ) = orego_pay_deposit( $id );
	return function_exists( 'orego_bk_money' ) && function_exists( 'orego_bk_to_usd' ) ? orego_bk_money( orego_bk_to_usd( $amt, $id ), $id ) : (string) $amt;
}

/* Sum already paid online for this booking (in USD). */
function orego_pay_online_paid( $id ) {
	$sum = 0;
	foreach ( ( function_exists( 'orego_json_meta' ) ? orego_json_meta( $id, 'og_payments' ) : array() ) as $p ) {
		if ( ! empty( $p['stripe'] ) && 'refund' !== ( $p['type'] ?? '' ) ) {
			$sum += (float) $p['amount'];
		}
	}
	return $sum;
}

function orego_pay_can_pay( $id ) {
	if ( ! orego_pay_ready() ) {
		return false;
	}
	$st = (string) get_post_meta( $id, 'og_bstatus', true );
	return in_array( $st, array( 'pending', 'confirmed' ), true ) && orego_pay_online_paid( $id ) <= 0;
}

function orego_stripe( $method, $path, $body = array() ) {
	$args = array(
		'method'  => $method,
		'timeout' => 25,
		'headers' => array( 'Authorization' => 'Bearer ' . orego_pay_secret(), 'Stripe-Version' => '2024-06-20' ),
	);
	if ( $body ) {
		$args['body'] = $body;
	}
	$r = wp_remote_request( 'https://api.stripe.com/v1/' . $path, $args );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$j = json_decode( wp_remote_retrieve_body( $r ), true );
	if ( wp_remote_retrieve_response_code( $r ) >= 400 || ! is_array( $j ) ) {
		return new WP_Error( 'stripe', $j['error']['message'] ?? 'Payment service error.' );
	}
	return $j;
}

/* Create a Stripe Checkout page for the deposit and return its address. */
function orego_pay_start_url( $id ) {
	if ( ! orego_pay_can_pay( $id ) ) {
		return new WP_Error( 'pay', 'Online payment is not available for this booking.' );
	}
	list( $amt, $code, $zero ) = orego_pay_deposit( $id );
	$unit = $zero ? (int) $amt : (int) round( $amt * 100 );
	if ( $unit <= 0 ) {
		return new WP_Error( 'pay', 'Nothing to pay.' );
	}
	$manage = orego_manage_url( $id );
	$car    = get_the_title( (int) get_post_meta( $id, 'og_car', true ) );
	$s      = orego_stripe( 'POST', 'checkout/sessions', array(
		'mode'                                         => 'payment',
		'client_reference_id'                          => (string) $id,
		'customer_email'                               => (string) get_post_meta( $id, 'og_email', true ),
		'line_items[0][quantity]'                      => 1,
		'line_items[0][price_data][currency]'          => strtolower( $code ),
		'line_items[0][price_data][unit_amount]'       => $unit,
		'line_items[0][price_data][product_data][name]' => sprintf( 'Deposit for booking #%d (%s)', $id, $car ),
		'metadata[booking]'                            => (string) $id,
		'payment_intent_data[metadata][booking]'       => (string) $id,
		'success_url'                                  => add_query_arg( 'paid', '{CHECKOUT_SESSION_ID}', $manage ),
		'cancel_url'                                   => add_query_arg( 'pay', 'cancelled', $manage ),
	) );
	if ( is_wp_error( $s ) ) {
		return $s;
	}
	$list   = (array) get_post_meta( $id, 'og_stripe_sessions', true );
	$list[] = $s['id'];
	update_post_meta( $id, 'og_stripe_sessions', array_values( array_filter( $list ) ) );
	return $s['url'];
}

/* Record a paid Checkout session on the booking (once). */
function orego_pay_record( $id, $session ) {
	if ( ! is_array( $session ) || 'paid' !== ( $session['payment_status'] ?? '' ) || (int) ( $session['metadata']['booking'] ?? 0 ) !== (int) $id ) {
		return false;
	}
	$list = orego_json_meta( $id, 'og_payments' );
	foreach ( $list as $p ) {
		if ( ( $p['stripe'] ?? '' ) === $session['id'] ) {
			return true; // already recorded
		}
	}
	$code = strtoupper( (string) $session['currency'] );
	$zero = in_array( $code, array( 'JPY', 'KRW', 'VND', 'CLP', 'XOF', 'XAF' ), true );
	$amt  = $zero ? (float) $session['amount_total'] : $session['amount_total'] / 100;
	$list[] = array(
		'id'     => substr( md5( $session['id'] ), 0, 10 ),
		'type'   => 'payment',
		'amount' => orego_bk_to_usd( $amt, $id ),
		'method' => 'card',
		'date'   => current_time( 'Y-m-d' ),
		'note'   => 'Online deposit (Stripe)',
		'by'     => 'Online',
		'at'     => current_time( 'mysql' ),
		'stripe' => $session['id'],
	);
	orego_set_json_meta( $id, 'og_payments', $list );
	$to = get_option( 'admin_email' );
	wp_mail( $to, sprintf( 'Deposit paid for booking #%d', $id ), sprintf( "%s paid a deposit of %s %s by card for booking #%d.\n\n%s", get_post_meta( $id, 'og_name', true ), number_format( $amt, $zero ? 0 : 2 ), $code, $id, admin_url( 'post.php?post=' . $id . '&action=edit' ) ) );
	do_action( 'orego_deposit_paid', $id, $amt, $code );
	return true;
}

/* AJAX from the booking page: start the payment. */
add_action( 'wp_ajax_orego_pay_start', 'orego_pay_ajax_start' );
add_action( 'wp_ajax_nopriv_orego_pay_start', 'orego_pay_ajax_start' );
function orego_pay_ajax_start() {
	$id = orego_manage_auth( $_POST['id'] ?? 0, sanitize_text_field( wp_unslash( $_POST['t'] ?? '' ) ) );
	if ( ! $id ) {
		wp_send_json_error( array( 'message' => 'Booking not found.' ), 400 );
	}
	$url = orego_pay_start_url( $id );
	if ( is_wp_error( $url ) ) {
		wp_send_json_error( array( 'message' => $url->get_error_message() ), 400 );
	}
	wp_send_json_success( array( 'url' => $url ) );
}

/* "Pay deposit" button on the booking's own page (manage link) */
add_action( 'admin_post_nopriv_orego_pay_go', 'orego_pay_go' );
add_action( 'admin_post_orego_pay_go', 'orego_pay_go' );
function orego_pay_go() {
	$id = orego_manage_auth( $_POST['id'] ?? 0, sanitize_text_field( wp_unslash( $_POST['t'] ?? '' ) ) );
	if ( ! $id ) {
		wp_die( 'Booking not found.' );
	}
	$url = orego_pay_start_url( $id );
	if ( is_wp_error( $url ) ) {
		wp_safe_redirect( add_query_arg( 'pay', 'error', orego_manage_url( $id ) ) );
		exit;
	}
	wp_redirect( $url ); // phpcs:ignore -- Stripe's payment page
	exit;
}

/* Back from Stripe: check the payment with Stripe, then show the booking. */
add_action( 'template_redirect', function () {
	if ( empty( $_GET['manage'] ) || empty( $_GET['paid'] ) ) {
		return;
	}
	$id = orego_manage_auth( $_GET['manage'], sanitize_text_field( wp_unslash( $_GET['t'] ?? '' ) ) );
	$cs = sanitize_text_field( wp_unslash( $_GET['paid'] ) );
	if ( ! $id || ! preg_match( '/^cs_[A-Za-z0-9_]+$/', $cs ) ) {
		return;
	}
	$ok = false;
	if ( in_array( $cs, (array) get_post_meta( $id, 'og_stripe_sessions', true ), true ) ) {
		$s  = orego_stripe( 'GET', 'checkout/sessions/' . rawurlencode( $cs ) );
		$ok = ! is_wp_error( $s ) && orego_pay_record( $id, $s );
	}
	wp_safe_redirect( add_query_arg( 'pay', $ok ? 'done' : 'check', remove_query_arg( 'paid', orego_manage_url( $id ) ) ) );
	exit;
}, 1 );

/* Stripe webhook: https://your-site/wp-json/orego/v1/stripe (event: checkout.session.completed). */
add_action( 'rest_api_init', function () {
	register_rest_route( 'orego/v1', '/stripe', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			$secret = (string) orego_pay_cfg()['whsec'];
			$body   = $req->get_body();
			$sig    = (string) $req->get_header( 'stripe-signature' );
			if ( '' === $secret || ! preg_match( '/t=(\d+)/', $sig, $t ) || ! preg_match_all( '/v1=([a-f0-9]+)/', $sig, $v ) ) {
				return new WP_REST_Response( array( 'ok' => false ), 400 );
			}
			$exp = hash_hmac( 'sha256', $t[1] . '.' . $body, $secret );
			$hit = false;
			foreach ( $v[1] as $s ) {
				$hit = $hit || hash_equals( $exp, $s );
			}
			if ( ! $hit || abs( time() - (int) $t[1] ) > 600 ) {
				return new WP_REST_Response( array( 'ok' => false ), 400 );
			}
			$e = json_decode( $body, true );
			if ( 'checkout.session.completed' === ( $e['type'] ?? '' ) ) {
				$s  = $e['data']['object'];
				$id = (int) ( $s['metadata']['booking'] ?? 0 );
				if ( $id && 'orego_booking' === get_post_type( $id ) ) {
					orego_pay_record( $id, $s );
				}
			}
			return new WP_REST_Response( array( 'ok' => true ), 200 );
		},
	) );
} );

/* ------------------------------------------------------------------ */
/* Settings page                                                       */
/* ------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=orego_car', 'Online payments', 'Online payments', 'manage_options', 'orego-payments', 'orego_pay_page' );
}, 21 );

add_action( 'admin_post_orego_pay_save', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'orego_pay_save' ) ) {
		wp_die( 'Not allowed.' );
	}
	$old = orego_pay_cfg();
	$new = array(
		'enabled' => empty( $_POST['enabled'] ) ? 0 : 1,
		'pct'     => min( 100, max( 1, (float) ( $_POST['pct'] ?? 20 ) ) ),
		'mode'    => 'live' === ( $_POST['mode'] ?? '' ) ? 'live' : 'test',
		'default' => 'pickup' === ( $_POST['default'] ?? '' ) ? 'pickup' : 'deposit',
	);
	// Keys: an empty box keeps the saved key (they are never shown again in full).
	foreach ( array( 'pk_test', 'sk_test', 'pk_live', 'sk_live', 'whsec' ) as $k ) {
		$v         = trim( sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) ) );
		$new[ $k ] = '' === $v ? $old[ $k ] : $v;
		if ( ! empty( $_POST[ 'clear_' . $k ] ) ) {
			$new[ $k ] = '';
		}
	}
	update_option( 'orego_pay', $new, false );
	do_action( 'litespeed_purge_all' );
	wp_safe_redirect( admin_url( 'edit.php?post_type=orego_car&page=orego-payments&saved=1' ) );
	exit;
} );

function orego_pay_page() {
	$c    = orego_pay_cfg();
	$mask = fn( $v ) => '' === $v ? '' : substr( $v, 0, 8 ) . '…' . substr( $v, -4 );
	$key  = function ( $k, $label, $ph ) use ( $c, $mask ) {
		echo '<tr><th><label for="' . esc_attr( $k ) . '">' . esc_html( $label ) . '</label></th><td><input type="password" autocomplete="off" class="regular-text" id="' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" placeholder="' . esc_attr( $c[ $k ] ? 'Saved: ' . $mask( $c[ $k ] ) : $ph ) . '">'
			. ( $c[ $k ] ? ' <label><input type="checkbox" name="clear_' . esc_attr( $k ) . '" value="1"> Remove</label>' : '' ) . '</td></tr>';
	};
	$test_ok = '' !== $c['sk_test'];
	$live_ok = '' !== $c['sk_live'];
	?>
	<div class="wrap">
		<h1>Online payments</h1>
		<p>Let customers pay a deposit by card when they book. Payments go through <b>Stripe</b>: card details are typed on Stripe's secure page, never on this site. Each paid deposit is added to the booking automatically.</p>
		<?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Saved.</p></div><?php endif; ?>
		<?php if ( $c['enabled'] && ! orego_pay_ready() ) : ?><div class="notice notice-warning"><p>Online payments are on, but no <?php echo 'live' === $c['mode'] ? 'live' : 'test'; ?> secret key is saved yet, so customers don't see the option.</p></div><?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="orego_pay_save"><?php wp_nonce_field( 'orego_pay_save' ); ?>
			<table class="form-table" role="presentation">
				<tr><th>Card deposits</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( $c['enabled'], 1 ); ?>> Offer "Pay a deposit now by card" on the booking page</label></td></tr>
				<tr><th><label for="pct">Deposit</label></th><td><input type="number" id="pct" name="pct" min="1" max="100" step="1" value="<?php echo esc_attr( $c['pct'] ); ?>" style="width:80px"> % of the rental total <p class="description">The rest is paid at pick-up. 10–20% is common.</p></td></tr>
				<tr><th>Selected by default</th><td><label><input type="radio" name="default" value="deposit" <?php checked( $c['default'], 'deposit' ); ?>> Pay deposit now</label> &nbsp; <label><input type="radio" name="default" value="pickup" <?php checked( $c['default'], 'pickup' ); ?>> Pay at pick-up</label></td></tr>
				<tr><th>Mode</th><td><label><input type="radio" name="mode" value="test" <?php checked( $c['mode'], 'test' ); ?>> Test (no real money; use Stripe's test card 4242 4242 4242 4242)</label><br><label><input type="radio" name="mode" value="live" <?php checked( $c['mode'], 'live' ); ?>> Live (real payments)</label></td></tr>
			</table>
			<h2>Stripe keys</h2>
			<p>From your Stripe Dashboard → Developers → API keys. Saved keys are hidden; leave a box empty to keep the saved key.<br>Status: test keys <b><?php echo $test_ok ? 'saved' : 'missing'; ?></b> · live keys <b><?php echo $live_ok ? 'saved' : 'missing'; ?></b></p>
			<table class="form-table" role="presentation">
				<?php
				$key( 'sk_test', 'Test secret key', 'sk_test_…' );
				$key( 'pk_test', 'Test publishable key', 'pk_test_…' );
				$key( 'sk_live', 'Live secret key', 'sk_live_…' );
				$key( 'pk_live', 'Live publishable key', 'pk_live_…' );
				$key( 'whsec', 'Webhook signing secret (optional)', 'whsec_…' );
				?>
				<tr><th>Webhook address</th><td><code><?php echo esc_html( rest_url( 'orego/v1/stripe' ) ); ?></code><p class="description">Optional but recommended: in Stripe → Developers → Webhooks, add this address with the event <b>checkout.session.completed</b>, then paste its signing secret above. It records the payment even if the customer closes the page before coming back.</p></td></tr>
			</table>
			<p><button type="submit" class="button button-primary">Save</button></p>
		</form>
		<h2>Good to know</h2>
		<ul style="list-style:disc;padding-left:20px;max-width:780px">
			<li>The deposit is charged in the currency the customer booked in (USD, EUR, MAD…).</li>
			<li>If you decline a booking that has a paid deposit, refund it from your Stripe Dashboard, then add the refund on the booking (Payments → Refund).</li>
			<li>CMI (Moroccan bank cards through your bank) needs a merchant contract with your bank; it can be added later the same way.</li>
		</ul>
	</div>
	<?php
}
