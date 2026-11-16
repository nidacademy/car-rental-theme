<?php
/**
 * Booking flow additions:
 * - Booking page: choice between "Pay a deposit now by card" and "Pay at pick-up" (when online payments are on).
 * - Confirmation screen: booking number, "Manage my booking" button, and the deposit payment.
 * - Manage booking page: "Pay your deposit" card and payment messages.
 * - "My booking" page ([orego_my_booking]): find a booking with its number and email, then manage it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Extra data sent back to the booking page after a booking is created. */
add_filter( 'orego_booking_response', function ( $data, $bid ) {
	$data['manage'] = function_exists( 'orego_manage_url' ) ? orego_manage_url( $bid ) : '';
	$data['t']      = (string) get_post_meta( $bid, 'og_token', true );
	$data['email']  = (string) get_post_meta( $bid, 'og_email', true );
	if ( function_exists( 'orego_pay_can_pay' ) && orego_pay_can_pay( $bid ) ) {
		$data['pay'] = array( 'amount' => orego_pay_deposit_label( $bid ), 'pct' => orego_pay_cfg()['pct'] );
	}
	return $data;
}, 10, 2 );

function orego_pro_page_url( $slug ) {
	$p = get_page_by_path( $slug );
	return $p && 'publish' === $p->post_status ? get_permalink( $p ) : '';
}

/* Booking page: payment choice + confirmation screen (step 3). */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'orego_booking_page_id' ) || ! is_page( orego_booking_page_id() ) || isset( $_GET['manage'] ) ) {
		return;
	}
	$pay = function_exists( 'orego_pay_ready' ) && orego_pay_ready();
	$cfg = array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'pay'   => $pay,
		'pct'   => $pay ? (float) orego_pay_cfg()['pct'] : 0,
		'def'   => $pay ? orego_pay_cfg()['default'] : 'pickup',
		'terms' => orego_pro_page_url( 'terms' ),
	);
	?>
<style id="ogpb-css">
.ogpb-opts{display:grid;gap:10px}
.ogpb-opt{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1.5px solid #E6E8EC;border-radius:14px;background:#FFFFFF;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.ogpb-opt input{position:absolute;opacity:0;pointer-events:none}
.ogpb-opt i{flex:none;width:20px;height:20px;margin-top:1px;border:2px solid #C9CDD3;border-radius:50%;box-sizing:border-box;position:relative}
.ogpb-opt b{display:block;font-size:15px;color:#15171C}
.ogpb-opt small{display:block;margin-top:2px;font-size:13px;line-height:1.45;color:#6B7280}
.ogpb-opt:has(input:checked){border-color:#15171C;box-shadow:0 0 0 1px #15171C}
.ogpb-opt:has(input:checked) i{border-color:#15171C}
.ogpb-opt:has(input:checked) i::after{content:"";position:absolute;inset:3px;border-radius:50%;background:#15171C}
.ogpb-opt:has(input:focus-visible){outline:2px solid #15171C;outline-offset:2px}
.ogpb-lock{display:inline-flex;align-items:center;gap:6px;margin-top:6px;font-size:12px;color:#15803D;font-weight:600}
.ogpb-done{display:grid;gap:10px;max-width:420px;margin:22px auto 0}
.ogpb-done .ogpb-b{display:flex;align-items:center;justify-content:center;gap:8px;min-height:50px;padding:0 20px;border-radius:14px;border:0;font:inherit;font-weight:700;font-size:15px;text-decoration:none;cursor:pointer}
.ogpb-b.is-dark{background:#15171C;color:#FFFFFF}
.ogpb-b.is-light{background:#FFFFFF;color:#15171C;border:1.5px solid #E6E8EC!important}
.ogpb-done{margin-bottom:10px}
.ogb-done a.ogb-btn.ogpb-sec{background:#FFFFFF!important;color:#15171C!important;border:1.5px solid #E6E8EC!important;box-shadow:none!important}
.ogpb-msg{font-size:14px;color:#6B7280;text-align:center;margin:0}
.ogpb-msg.is-err{color:#B91C1C}
.ogpb-spin{width:18px;height:18px;border:2.5px solid rgba(255,255,255,.35);border-top-color:#FFFFFF;border-radius:50%;animation:ogpbS .8s linear infinite}
@keyframes ogpbS{to{transform:rotate(360deg)}}
.ogb-agree a{color:inherit;text-decoration:underline;text-underline-offset:2px}
.ogpb-tel{display:flex;align-items:stretch;gap:8px;min-width:0}
.ogpb-tel>input{flex:1;min-width:0}
.ogpb-cc{position:relative;flex:none;display:flex;align-items:center;gap:6px;padding:0 30px 0 12px;border:1px solid #E6E8EC;border-radius:12px;background:#F8F9FA url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2315171C' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 11px center;font-size:15px;font-weight:700;color:#15171C;white-space:nowrap;cursor:pointer}
.ogpb-cc select{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;font-size:16px;border:0;padding:0;margin:0}
.ogpb-cc:focus-within{border-color:#15171C;box-shadow:0 0 0 3px rgba(21,23,28,.08)}
.ogpb-tel.is-bad>input,.ogpb-tel.is-bad .ogpb-cc{border-color:#DC2626!important}
.ogpb-tel-err{display:block;margin-top:6px;font-size:12.5px;line-height:1.4;color:#B91C1C}
.ogpb-tel-err[hidden]{display:none}
html.og-dark .ogpb-cc{background-color:#1E232C;border-color:#2E3440;color:#F3F4F6}
</style>
<script id="ogpb-js">
(function(){
var C=<?php echo wp_json_encode( $cfg ); ?>,choice=C.def;
/* Payment choice on step 2 */
var one=document.querySelector('.ogb-pay');
if(one&&C.pay){
 var w=document.createElement('div');w.className='ogpb-opts';
 w.innerHTML='<label class="ogpb-opt"><input type="radio" name="og_paychoice" value="deposit"><i></i><span><b>Pay a '+C.pct+'% deposit now by card</b><small>Your car is held for you. The rest is paid at pick-up.</small><span class="ogpb-lock"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>Secure payment by Stripe</span></span></label>'
  +'<label class="ogpb-opt"><input type="radio" name="og_paychoice" value="pickup"><i></i><span><b>Pay at pick-up</b><small>Cash or card when you collect the car. Nothing is charged now.</small></span></label>';
 one.replaceWith(w);
 [].forEach.call(w.querySelectorAll('input'),function(r){if(r.value===choice)r.checked=true;r.addEventListener('change',function(){if(r.checked)choice=r.value})});
}
/* Phone number: country code picker + length check. The booking receives "+212 612345678". */
(function(){
 var vis=document.querySelector('.ogb-field input[name="phone"]');if(!vis)return;
 var L=[['MA','Morocco',212,9,9,'6 12 34 56 78'],['FR','France',33,9,9,'6 12 34 56 78'],['ES','Spain',34,9,9,'612 34 56 78'],['DE','Germany',49,10,11,'1512 3456789'],['IT','Italy',39,9,11,'312 345 6789'],['GB','United Kingdom',44,10,10,'7400 123456'],['US','United States',1,10,10,'201 555 0123'],['BE','Belgium',32,8,9,'470 12 34 56'],['NL','Netherlands',31,9,9,'6 12345678'],['PT','Portugal',351,9,9,'912 345 678'],['CH','Switzerland',41,9,9,'78 123 45 67'],
  ['DZ','Algeria',213,9,9,''],['AU','Australia',61,9,9,''],['AT','Austria',43,7,13,''],['BR','Brazil',55,10,11,''],['CA','Canada',1,10,10,''],['CN','China',86,11,11,''],['CI','Côte d’Ivoire',225,10,10,''],['DK','Denmark',45,8,8,''],['EG','Egypt',20,10,10,''],['FI','Finland',358,6,10,''],['GR','Greece',30,10,10,''],['IN','India',91,10,10,''],['IE','Ireland',353,9,9,''],['JP','Japan',81,10,10,''],['JO','Jordan',962,9,9,''],['KW','Kuwait',965,8,8,''],['LB','Lebanon',961,7,8,''],['LU','Luxembourg',352,8,9,''],['MR','Mauritania',222,8,8,''],['NO','Norway',47,8,8,''],['PL','Poland',48,9,9,''],['QA','Qatar',974,8,8,''],['RU','Russia',7,10,10,''],['SA','Saudi Arabia',966,9,9,''],['SN','Senegal',221,9,9,''],['SE','Sweden',46,7,10,''],['TN','Tunisia',216,8,8,''],['TR','Türkiye',90,10,10,''],['AE','United Arab Emirates',971,8,9,'']];
 function flag(cc){return cc.replace(/./g,function(c){return String.fromCodePoint(127397+c.charCodeAt(0))})}
 var by={};L.forEach(function(r){by[r[0]]=r});
 var guess='MA';try{var z=Intl.DateTimeFormat().resolvedOptions().timeZone||'',ln=(navigator.language||'').split('-'),rg=(ln[1]||'').toUpperCase(),lg=(ln[0]||'').toLowerCase();
  if(z==='Africa/Casablanca')guess='MA';else if(by[rg])guess=rg;else guess=({fr:'FR',es:'ES',de:'DE',it:'IT',pt:'PT',nl:'NL',ar:'MA',ja:'JP'})[lg]||'MA'}catch(e){}
 var wrap=document.createElement('div');wrap.className='ogpb-tel';
 var cc=document.createElement('span');cc.className='ogpb-cc';
 var lab=document.createElement('span');lab.className='ogpb-cc-l';lab.setAttribute('aria-hidden','true');
 var sel=document.createElement('select');sel.setAttribute('aria-label','Country code');
 L.forEach(function(r,i){var o=document.createElement('option');o.value=r[0];o.textContent=flag(r[0])+' '+r[1]+' (+'+r[2]+')';sel.appendChild(o);if(i===10){var s=document.createElement('option');s.disabled=true;s.textContent='──────────';sel.appendChild(s)}});
 cc.appendChild(lab);cc.appendChild(sel);
 var hid=document.createElement('input');hid.type='hidden';hid.name='phone';
 var msg=document.createElement('small');msg.className='ogpb-tel-err';msg.hidden=true;
 vis.removeAttribute('name');vis.setAttribute('autocomplete','tel-national');vis.setAttribute('inputmode','tel');vis.id=vis.id||'ogb-phone-in';
 var lbl=vis.closest('label');if(lbl)lbl.htmlFor=vis.id; /* tapping the label goes to the number box, not the country list */
 vis.parentNode.insertBefore(wrap,vis);wrap.appendChild(cc);wrap.appendChild(vis);wrap.parentNode.appendChild(msg);wrap.parentNode.appendChild(hid);
 function cur(){return by[sel.value]||by.MA}
 function digits(){var d=vis.value.replace(/\D/g,'');var r=cur();if(d.charAt(0)==='0'&&!(r[0]==='IT'&&/^0[1-9]/.test(d)))d=d.replace(/^0+/,'');return d}
 function ok(){var d=digits(),r=cur();return d.length>=r[3]&&d.length<=r[4]}
 function sync(){var r=cur();lab.textContent=flag(r[0])+' +'+r[2];vis.placeholder=r[5]||'Phone number';hid.value=ok()?'+'+r[2]+' '+digits():''}
 function show(force){var d=vis.value.replace(/\D/g,'');if(!d&&!force){msg.hidden=true;wrap.classList.remove('is-bad');return}var bad=!ok();msg.hidden=!bad;wrap.classList.toggle('is-bad',bad);var r=cur();msg.textContent=bad?(r[3]===r[4]?'Enter a '+r[1]+' number with '+r[3]+' digits (without the country code).':'Enter a '+r[1]+' number with '+r[3]+' to '+r[4]+' digits (without the country code).'):''}
 /* a pasted "+33 6..." or "0033 6..." picks the country by itself */
 vis.addEventListener('input',function(){var v=vis.value.trim();if(/^(\+|00)/.test(v)){var d=v.replace(/^00/,'').replace(/\D/g,''),hit=null;L.forEach(function(r){var s=String(r[2]);if(d.indexOf(s)===0&&(!hit||s.length>String(hit[2]).length))hit=r});if(hit){sel.value=hit[0];vis.value=d.slice(String(hit[2]).length)}}sync();if(!wrap.classList.contains('is-bad'))msg.hidden=true;else show()});
 vis.addEventListener('blur',function(){show(false)});
 sel.addEventListener('change',function(){sync();show(false);vis.focus()});
 /* when the visitor tries to go on, point at the phone box if it is wrong */
 document.addEventListener('click',function(e){var b=e.target.closest('button,.ogb-btn,[data-go]');if(!b||!wrap.offsetParent)return;if(!ok()&&/confirm|book|continue|send/i.test(b.textContent||'')){show(true)}},true);
 sel.value=guess;sync();
})();
/* Terms link in the agreement */
var ag=document.querySelector('.ogb-agree span');
if(ag&&C.terms&&ag.innerHTML.indexOf('rental terms')>-1){ag.innerHTML=ag.innerHTML.replace('rental terms','<a href="'+C.terms+'" target="_blank" rel="noopener">rental terms</a>')}
function payStart(d,btn,msg){
 if(btn){btn.disabled=true;btn.innerHTML='<span class="ogpb-spin"></span>Opening secure payment…'}
 var fd=new FormData();fd.append('action','orego_pay_start');fd.append('id',d.ref);fd.append('t',d.t);
 fetch(C.ajax,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(res){
  if(res&&res.success&&res.data.url){location.href=res.data.url;return}
  throw new Error((res&&res.data&&res.data.message)||'');
 }).catch(function(e){if(btn){btn.disabled=false;btn.textContent='Pay deposit now ('+d.pay.amount+')'}if(msg){msg.className='ogpb-msg is-err';msg.textContent='The payment page could not open. Please try again, or pay at pick-up.'}});
}
/* Step 3: after the booking is sent */
window.ogBookDone=function(d){
 var box=document.querySelector('.ogb-done');if(!box||!d)return;
 var w=document.createElement('div');w.className='ogpb-done';
 var msg=document.createElement('p');msg.className='ogpb-msg';
 if(d.email)msg.textContent='A confirmation has been sent to '+d.email+'.';
 if(d.pay){
  var b=document.createElement('button');b.type='button';b.className='ogpb-b is-dark';b.textContent='Pay deposit now ('+d.pay.amount+')';
  b.addEventListener('click',function(){payStart(d,b,msg)});w.appendChild(b);
 }
 if(d.manage){var a=document.createElement('a');a.className='ogpb-b '+(d.pay?'is-light':'is-dark');a.href=d.manage;a.textContent='View or manage my booking';w.appendChild(a)}
 w.appendChild(msg);
 var browse=box.querySelector('a.ogb-btn');
 if(browse){browse.classList.add('ogpb-sec');browse.parentNode.insertBefore(w,browse)}else box.appendChild(w);
 if(d.pay&&choice==='deposit'){payStart(d,w.querySelector('button'),msg)}
};
})();
</script>
	<?php
}, 60 );

/* Manage booking page: payment messages and the "Pay your deposit" card. */
add_filter( 'do_shortcode_tag', function ( $out, $tag ) {
	if ( 'orego_booking' !== $tag || empty( $_GET['manage'] ) || ! function_exists( 'orego_manage_auth' ) ) {
		return $out;
	}
	$id = orego_manage_auth( $_GET['manage'], sanitize_text_field( wp_unslash( $_GET['t'] ?? '' ) ) );
	if ( ! $id ) {
		return $out;
	}
	$msgs = array(
		'done'      => array( 'ok', 'Thank you, your deposit is paid. It has been added to your booking.' ),
		'check'     => array( 'ok', 'Thanks! We are confirming your payment with the bank. This page will show it shortly.' ),
		'cancelled' => array( 'err', 'The payment was cancelled. Nothing was charged. You can pay your deposit below, or at pick-up.' ),
		'error'     => array( 'err', 'The payment page could not open. Please try again, or pay at pick-up.' ),
	);
	$k = sanitize_key( $_GET['pay'] ?? '' );
	if ( isset( $msgs[ $k ] ) ) {
		$out = preg_replace( '#(<div class="ogb-top"><h1>Your booking \#\d+</h1></div>)#', '$1<p class="ogmg-' . $msgs[ $k ][0] . '">' . esc_html( $msgs[ $k ][1] ) . '</p>', $out, 1 );
	}
	if ( function_exists( 'orego_pay_can_pay' ) && orego_pay_can_pay( $id ) ) {
		$card = '<section class="ogb-card"><h2>Pay your deposit</h2><p class="ogb-hint">Pay ' . esc_html( orego_pay_cfg()['pct'] ) . '% now (' . esc_html( orego_pay_deposit_label( $id ) ) . ') to hold your car. The rest is paid at pick-up.</p>'
			. '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="orego_pay_go"><input type="hidden" name="id" value="' . (int) $id . '"><input type="hidden" name="t" value="' . esc_attr( (string) get_post_meta( $id, 'og_token', true ) ) . '">'
			. '<button type="submit" class="ogb-btn">Pay deposit by card</button></form><p class="ogb-hint" style="margin-top:8px">Secure payment by Stripe.</p></section>';
		$out  = preg_replace( '#(<aside>)#', '$1' . $card, $out, 1 );
	}
	return $out;
}, 20, 2 );

/* ------------------------------------------------------------------ */
/* "My booking" page                                                   */
/* ------------------------------------------------------------------ */

add_shortcode( 'orego_my_booking', function () {
	$err = sanitize_key( $_GET['mb'] ?? '' );
	$msg = array(
		'nf'   => 'We could not find a booking with this number and email. Check your confirmation email and try again.',
		'many' => 'Too many tries. Please wait a few minutes and try again.',
	);
	ob_start();
	?>
<div class="ogmb">
	<form class="ogmb-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="orego_find_booking">
		<?php wp_nonce_field( 'orego_find_booking', '_ogmb' ); ?>
		<?php if ( isset( $msg[ $err ] ) ) : ?><p class="ogmb-err" role="alert"><?php echo esc_html( $msg[ $err ] ); ?></p><?php endif; ?>
		<label class="ogmb-f"><span>Booking number</span><input type="text" name="ref" inputmode="numeric" autocomplete="off" placeholder="e.g. 123" required value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['ref'] ?? '' ) ) ); ?>"></label>
		<label class="ogmb-f"><span>Email used for the booking</span><input type="email" name="email" autocomplete="email" placeholder="you@example.com" required></label>
		<button type="submit" class="ogmb-btn">Find my booking</button>
		<p class="ogmb-note">You can see your booking<?php echo function_exists( 'orego_pay_ready' ) && orego_pay_ready() ? ', pay your deposit' : ''; ?>, ask for a change or cancel it (free up to <?php echo (int) ( function_exists( 'orego_rules' ) ? orego_rules()['cancel_hours'] : 48 ); ?> hours before pick-up).</p>
	</form>
</div>
<style>
.ogmb{max-width:520px;margin:0 auto}
.ogmb-card{display:grid;gap:16px;padding:28px;border:1px solid #ECEEF1;border-radius:22px;background:#FFFFFF;box-shadow:0 18px 40px -28px rgba(21,23,28,.35)}
.ogmb-f{display:grid;gap:6px}
.ogmb-f span{font-size:13px;font-weight:700;color:#15171C}
.ogmb-f input{height:52px;padding:0 16px;border:1.5px solid #E6E8EC;border-radius:14px;background:#F8F9FA;font:inherit;font-size:16px;color:#15171C}
.ogmb-f input:focus{outline:none;border-color:#15171C;background:#FFFFFF}
.ogmb-btn{height:54px;border:0;border-radius:14px;background:#15171C;color:#FFFFFF;font:inherit;font-weight:700;font-size:16px;cursor:pointer}
.ogmb-btn:hover{background:#000000}
.ogmb-note{margin:0;font-size:13.5px;line-height:1.5;color:#6B7280;text-align:center}
.ogmb-err{margin:0;padding:12px 14px;border-radius:12px;background:#FEE2E2;color:#7F1D1D;font-size:14px}
@media (max-width:560px){.ogmb-card{padding:20px;border-radius:18px}}
</style>
	<?php
	return ob_get_clean();
} );

add_action( 'admin_post_nopriv_orego_find_booking', 'orego_pro_find_booking' );
add_action( 'admin_post_orego_find_booking', 'orego_pro_find_booking' );
function orego_pro_find_booking() {
	$back = orego_pro_page_url( 'my-booking' );
	$back = $back ? $back : home_url( '/' );
	if ( ! isset( $_POST['_ogmb'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ogmb'] ) ), 'orego_find_booking' ) ) {
		wp_safe_redirect( $back );
		exit;
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rk = 'ogmb_rl_' . md5( $ip );
	$n  = (int) get_transient( $rk );
	if ( $n >= 8 ) {
		wp_safe_redirect( add_query_arg( 'mb', 'many', $back ) );
		exit;
	}
	$ref   = absint( preg_replace( '/\D/', '', wp_unslash( $_POST['ref'] ?? '' ) ) );
	$email = strtolower( trim( sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) ) );
	if ( $ref && $email && 'orego_booking' === get_post_type( $ref ) && 'publish' === get_post_status( $ref )
		&& hash_equals( strtolower( trim( (string) get_post_meta( $ref, 'og_email', true ) ) ), $email ) ) {
		delete_transient( $rk );
		wp_safe_redirect( orego_manage_url( $ref ) );
		exit;
	}
	set_transient( $rk, $n + 1, 10 * MINUTE_IN_SECONDS );
	wp_safe_redirect( add_query_arg( array( 'mb' => 'nf', 'ref' => $ref ? $ref : null ), $back ) );
	exit;
}
