<?php
/**
 * Exotic Cars Booking page UI: [orego_booking]
 * Step 1 تفاصيل الرحلة (calendar + times + pickup/drop-off), Step 2 التأكيد (details + pay at pickup).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_bk_ic( $name, $size = 18 ) {
	$p = array(
		'cal'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
		'pin'   => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'user'  => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
		'cash'  => '<path d="M18 7V5.5A1.5 1.5 0 0 0 16.5 4H5.5a2.5 2.5 0 0 0 0 5"/><path d="M3 6.5v11A2.5 2.5 0 0 0 5.5 20H19a1 1 0 0 0 1-1v-3"/><path d="M3 6.5A2.5 2.5 0 0 0 5.5 9H19a1 1 0 0 1 1 1v3"/><path d="M21 12.5v3.5h-4a1.75 1.75 0 0 1 0-3.5z"/>', // wallet
		'check' => '<path d="M20 6 9 17l-5-5"/>',
		'back'  => '<path d="M5 12h14M12 5l7 7-7 7"/>',
		'right' => '<path d="m9 18 6-6-6-6"/>',
		'left'  => '<path d="m15 18-6-6 6-6"/>',
		'car'   => '<path d="M5 16H3v-4l2-5h11l3 5h2v4h-2"/><circle cx="7.5" cy="16.5" r="2"/><circle cx="16.5" cy="16.5" r="2"/><path d="M9.5 16.5h5"/>',
		'arrow' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
	);
	return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $name ] . '</svg>';
}

add_shortcode( 'orego_booking', function () {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true ); // availability must always be fresh
	}
	do_action( 'litespeed_control_set_nocache', 'orego booking page' );
	if ( isset( $_GET['manage'] ) && function_exists( 'orego_manage_render' ) ) {
		return '<style>' . orego_bk_css() . '</style>' . orego_manage_render(); // customer's private booking page
	}

	$car = absint( $_GET['car'] ?? 0 );
	$p   = $car ? get_post( $car ) : null;
	$ok  = $p && 'orego_car' === $p->post_type && ( 'publish' === $p->post_status || current_user_can( 'edit_post', $car ) );
	$css = '<style>' . orego_bk_css() . '</style>';
	if ( ! $ok ) {
		return $css . '<div class="ogb"><div class="ogb-empty">' . orego_bk_ic( 'car', 40 ) . '<h1>Choose a car first</h1><p>Start by choosing the car you want to book, then press “Book now”.</p><a class="ogb-btn" href="' . esc_url( orego_purl( 'cars' ) ) . '">Browse cars</a></div></div>';
	}
	$d     = orego_car_data( $car );
	$price = (float) $d['price'];
	$name  = get_the_title( $car );
	if ( $price <= 0 ) {
		return $css . '<div class="ogb"><div class="ogb-empty">' . orego_bk_ic( 'car', 40 ) . '<h1>' . esc_html( $name ) . '</h1><p>This car has no price yet. Contact us to book it.</p><a class="ogb-btn" href="#og-contact">Contact us</a></div></div>';
	}
	$terms = get_the_terms( $car, 'car_type' );
	$type  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
	$img   = has_post_thumbnail( $car ) ? get_the_post_thumbnail( $car, 'medium_large', array( 'alt' => esc_attr( $name ) ) ) : '<span class="ogb-noimg">' . orego_bk_ic( 'car', 40 ) . '</span>';
	$locs  = orego_locations();
	$cfg   = array(
		'car'     => $car,
		'price'   => $price,
		'blocked' => orego_blocked_nights( $car ),
		'today'   => current_time( 'Y-m-d' ),
		'sow'     => (int) get_option( 'start_of_week', 6 ),
		'maxDays' => 60,
		'horizon' => 365,
		'ajax'    => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'orego_book' ),
		'oneLoc'  => 1 === count( $locs ),
	);
	$og_pr          = function_exists( 'orego_car_pricing' ) ? orego_car_pricing( $car ) : array( 'min_days' => 1, 'deposit' => 0 );
	$cfg['minDays'] = $og_pr['min_days'];
$og_bc          = function_exists( 'orego_bk_cur' ) ? orego_bk_cur() : array( 'USD', 1 );
$cfg['bcur']    = array( 'code' => $og_bc[0], 'rate' => $og_bc[1] );
	$cfg['deposit'] = $og_pr['deposit'];
	$cfg['quote']   = function_exists( 'orego_quote' );
	$opts = '';
	foreach ( $locs as $l ) {
		$opts .= '<option value="' . esc_attr( $l ) . '">' . esc_html( $l ) . '</option>';
	}
	$times = '';
	for ( $m = 7 * 60; $m <= 21 * 60; $m += 30 ) {
		$t      = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
		$times .= '<option value="' . $t . '"' . ( '10:00' === $t ? ' selected' : '' ) . '>' . $t . '</option>';
	}
	ob_start();
	echo $css; // phpcs:ignore
	?>
<div class="ogb" id="ogb">
	<div class="ogb-top">
		<a class="ogb-back" href="<?php echo esc_url( get_permalink( $car ) ); ?>"><?php echo orego_bk_ic( 'back', 15 ); // phpcs:ignore ?>Back to the car</a>
		<h1>Book your trip</h1>
		<ol class="ogb-steps" aria-label="Booking steps">
			<li class="is-on" data-s="1"><b>1</b><span><strong>Trip details</strong><small>Dates and location</small></span></li>
			<li data-s="2"><b>2</b><span><strong>Confirm</strong><small>Confirmation and payment</small></span></li>
		</ol>
	</div>

	<div class="ogb-grid">
		<div class="ogb-main">
			<div class="ogb-step" data-step="1">
				<p class="ogb-err" role="alert" hidden></p>
				<section class="ogb-card">
					<h2><span class="ogb-ic"><?php echo orego_bk_ic( 'cal' ); // phpcs:ignore ?></span>Choose your dates</h2>
					<p class="ogb-hint" id="ogb-hint">Choose your pick-up day.</p>
					<div class="ogb-cal">
						<button type="button" class="ogb-nav ogb-prev" aria-label="Previous month"><?php echo orego_bk_ic( 'right' ); // phpcs:ignore ?></button>
						<button type="button" class="ogb-nav ogb-next" aria-label="Next month"><?php echo orego_bk_ic( 'left' ); // phpcs:ignore ?></button>
						<div class="ogb-months"></div>
					</div>
					<div class="ogb-legend"><span><i class="lg-sel"></i>Your selection</span><span><i class="lg-off"></i>Unavailable</span><span><i class="lg-today"></i>Today</span></div>
					<div class="ogb-times">
						<label class="ogb-field"><span>Pick-up time</span><span class="ogb-sel"><?php echo orego_bk_ic( 'clock', 17 ); // phpcs:ignore ?><select id="ogb-ptime" dir="ltr"><?php echo $times; // phpcs:ignore ?></select></span></label>
						<label class="ogb-field"><span>Return time</span><span class="ogb-sel"><?php echo orego_bk_ic( 'clock', 17 ); // phpcs:ignore ?><select id="ogb-rtime" dir="ltr"><?php echo $times; // phpcs:ignore ?></select></span></label>
					</div>
				</section>
				<section class="ogb-card">
					<h2><span class="ogb-ic"><?php echo orego_bk_ic( 'pin' ); // phpcs:ignore ?></span>Pick-up and return</h2>
					<div class="ogb-locs">
						<label class="ogb-field"><span>Pick-up location</span><span class="ogb-sel"><?php echo orego_bk_ic( 'pin', 17 ); // phpcs:ignore ?><select id="ogb-pick" required><option value="">Choose a location...</option><?php echo $opts; // phpcs:ignore ?></select></span></label>
						<label class="ogb-field"><span>Return location</span><span class="ogb-sel"><?php echo orego_bk_ic( 'pin', 17 ); // phpcs:ignore ?><select id="ogb-drop"><option value="">Same as pick-up</option><?php echo $opts; // phpcs:ignore ?></select></span></label>
					</div>
				</section>
			</div>

			<div class="ogb-step" data-step="2" hidden>
				<p class="ogb-err" role="alert" hidden></p>
				<section class="ogb-card">
					<div class="ogb-h2row"><h2><span class="ogb-ic"><?php echo orego_bk_ic( 'cal' ); // phpcs:ignore ?></span>Your trip</h2><button type="button" class="ogb-link" data-go="1">Edit</button></div>
					<dl class="ogb-review" id="ogb-review"></dl>
				</section>
				<form class="ogb-card" id="ogb-form" novalidate>
					<h2><span class="ogb-ic"><?php echo orego_bk_ic( 'user' ); // phpcs:ignore ?></span>Your details</h2>
					<div class="ogb-fields">
						<label class="ogb-field ogb-full"><span>Full name *</span><input name="name" autocomplete="name" required></label>
						<label class="ogb-field"><span>Email *</span><input type="email" name="email" autocomplete="email" dir="ltr" required></label>
						<label class="ogb-field"><span>Phone number *</span><input type="tel" name="phone" autocomplete="tel" dir="ltr" required></label>
						<label class="ogb-field ogb-full"><span>Notes (optional)</span><textarea name="notes" rows="3" maxlength="1000" placeholder="Flight number, child seat, any special request..."></textarea></label>
						<input type="text" name="website" class="ogb-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
					</div>
					<h2 class="ogb-h2b"><span class="ogb-ic"><?php echo orego_bk_ic( 'cash' ); // phpcs:ignore ?></span>Payment method</h2>
					<div class="ogb-pay"><span class="ogb-radio" aria-hidden="true"></span><div><b>Pay at pick-up</b><small>Cash or card when you collect the car. Nothing is charged now.</small></div></div>
					<label class="ogb-agree"><input type="checkbox" name="agree" value="1" required><span>I agree to the rental terms and cancellation policy.</span></label>
				</form>
			</div>

			<div class="ogb-step" data-step="3" hidden>
				<section class="ogb-card ogb-done">
					<span class="ogb-done-ic"><?php echo orego_bk_ic( 'check', 34 ); // phpcs:ignore ?></span>
					<h2>We have received your booking request</h2>
					<p class="ogb-ref">Booking number <b dir="ltr" id="ogb-ref"></b></p>
					<p>Your request is now waiting for our approval. We will email you a confirmation soon. You pay when you pick up the car.</p>
					<dl class="ogb-review" id="ogb-review-done"></dl>
					<a class="ogb-btn" href="<?php echo esc_url( orego_purl( 'cars' ) ); ?>">Browse cars</a>
				</section>
			</div>
		</div>

		<aside class="ogb-side">
			<div class="ogb-card ogb-sum">
				<div class="ogb-car"><div class="ogb-car-img"><?php echo $img; // phpcs:ignore ?></div><div><strong><?php echo esc_html( $name ); ?></strong><?php if ( $type ) : ?><small><?php echo esc_html( $type ); ?></small><?php endif; ?></div></div>
				<dl class="ogb-rows">
					<div><dt>Pick-up</dt><dd id="ogb-s-start">–</dd></div>
					<div><dt>Return</dt><dd id="ogb-s-end">–</dd></div>
					<div><dt>Daily price</dt><dd dir="ltr" id="ogb-s-pday"><?php echo esc_html( orego_bk_money( $price ) ); ?></dd></div>
					<div><dt>Duration</dt><dd id="ogb-s-days">–</dd></div>
					<div id="ogb-s-disc" hidden><dt>Discount</dt><dd dir="ltr"></dd></div>
				</dl>
				<div class="ogb-total"><span>Total</span><strong dir="ltr" id="ogb-s-total">$0</strong></div>
<p class="ogb-usdnote" id="ogb-usdnote" hidden></p>
<?php if ( $og_pr['deposit'] > 0 ) : ?><p class="ogb-depnote">+ <span dir="ltr" id="ogb-s-dep"><?php echo esc_html( orego_bk_money( $og_pr['deposit'] ) ); ?></span> refundable security deposit at pick-up</p><?php endif; ?>
<?php if ( $og_pr['min_days'] > 1 ) : ?><p class="ogb-depnote">Minimum rental: <?php echo (int) $og_pr['min_days']; ?> days</p><?php endif; ?>
				<button type="button" class="ogb-btn ogb-go" disabled><span>Continue</span><?php echo orego_bk_ic( 'arrow', 18 ); // phpcs:ignore ?></button>
				<p class="ogb-note"><?php echo orego_bk_ic( 'cash', 14 ); // phpcs:ignore ?>Pay at pick-up, nothing is charged now</p>
			</div>
		</aside>
	</div>

	<div class="ogb-bar">
		<div><small>Total</small><strong dir="ltr" class="ogb-bar-total">$0</strong></div>
		<button type="button" class="ogb-btn ogb-go" disabled><span>Continue</span><?php echo orego_bk_ic( 'arrow', 18 ); // phpcs:ignore ?></button>
	</div>
	<script type="application/json" id="ogb-data"><?php echo wp_json_encode( $cfg ); ?></script>
	<script><?php echo orego_bk_js(); // phpcs:ignore ?></script>
</div>
	<?php
	return ob_get_clean();
} );

function orego_bk_js() {
	return <<<'JS'
(function(){
var root=document.getElementById('ogb');if(!root||root.dataset.ready)return;root.dataset.ready='1';
var C=JSON.parse(document.getElementById('ogb-data').textContent);
var blocked=new Set(C.blocked),S={start:null,end:null,step:1};
function pad(n){return(n<10?'0':'')+n}
function key(d){return d.getUTCFullYear()+'-'+pad(d.getUTCMonth()+1)+'-'+pad(d.getUTCDate())}
function parse(k){var p=k.split('-');return new Date(Date.UTC(+p[0],p[1]-1,+p[2]))}
function add(k,n){var d=parse(k);d.setUTCDate(d.getUTCDate()+n);return key(d)}
function diff(a,b){return Math.round((parse(b)-parse(a))/864e5)}
var L=(function(){var t=null;try{var s=JSON.parse(localStorage.getItem('__GT_TRANSLATE_LANGS'));t=s&&s.tgtLang}catch(e){}t=t||'en';return (t==='ar'||t==='ary')?'ar-u-nu-latn':(t==='en'?'en-US':t)})(),Z={timeZone:'UTC'};
function F(o){return new Intl.DateTimeFormat(L,Object.assign({},Z,o))}
var fMonth=F({month:'long',year:'numeric'}),fLong=F({weekday:'long',day:'numeric',month:'long',year:'numeric'}),fShort=F({weekday:'short',day:'numeric',month:'short'}),fWd=F({weekday:'short'});
var B={code:'USD',rate:1};function syncB(){if(window.ogCur&&window.ogCur.code&&window.ogCur.rate){B.code=window.ogCur.code;B.rate=window.ogCur.rate}}syncB();
function fmtC(v,c){if(window.ogFmt)return window.ogFmt(v,c);v=Math.round(v*100)/100;var n=Math.abs(v).toLocaleString('en-US',{minimumFractionDigits:v%1?2:0,maximumFractionDigits:2}),s=v<0?'-':'';return c==='USD'?s+'$'+n:c==='EUR'?s+'€'+n:s+n+' MAD'}
function money(v){return fmtC(Number(v)*B.rate,B.code)}
function otherCur(){return false}
function daysTxt(n){return n===1?'1 day':n+' days'}
var today=C.today,last=add(today,C.horizon);
var view=parse(today);view.setUTCDate(1);
var monthsEl=root.querySelector('.ogb-months'),prev=root.querySelector('.ogb-prev'),next=root.querySelector('.ogb-next'),hint=document.getElementById('ogb-hint');
var mq=window.matchMedia('(min-width:760px)');
function free(a,b){for(var k=a;k<b;k=add(k,1)){if(blocked.has(k))return false}return true}
function canEnd(k){return !!S.start&&!S.end&&k>S.start&&diff(S.start,k)>=(C.minDays||1)&&diff(S.start,k)<=C.maxDays&&k<=last&&free(S.start,k)}
function month(first){
 var w=document.createElement('div');w.className='ogb-month';
 var h=document.createElement('h3');h.textContent=fMonth.format(first);w.appendChild(h);
 var g=document.createElement('div');g.className='ogb-days';
 for(var i=0;i<7;i++){var s=document.createElement('span');s.className='ogb-wd';s.textContent=fWd.format(new Date(Date.UTC(2024,0,7+((C.sow+i)%7))));g.appendChild(s)}
 var lead=(first.getUTCDay()-C.sow+7)%7;for(i=0;i<lead;i++)g.appendChild(document.createElement('span'));
 var y=first.getUTCFullYear(),m=first.getUTCMonth(),n=new Date(Date.UTC(y,m+1,0)).getUTCDate();
 for(var d=1;d<=n;d++){
  var k=key(new Date(Date.UTC(y,m,d))),b=document.createElement('button');b.type='button';b.className='ogb-day';b.dataset.k=k;b.textContent=d;
  var past=k<today||k>last,off=blocked.has(k),en=canEnd(k);
  var ok=!past&&(!off||en);
  if(past)b.classList.add('is-past');if(off)b.classList.add('is-off');if(en&&off)b.classList.add('is-turn');
  if(k===today)b.classList.add('is-today');
  if(k===S.start)b.classList.add('is-start');if(k===S.end)b.classList.add('is-end');
  if(S.start&&S.end&&k>S.start&&k<S.end)b.classList.add('is-in');
  if(S.start&&S.end)g.classList.add('has-range');
  b.disabled=!ok;b.setAttribute('aria-pressed',(k===S.start||k===S.end)?'true':'false');
  b.setAttribute('aria-label',fLong.format(parse(k))+(off&&!en?', unavailable':''));
  g.appendChild(b)}
 w.appendChild(g);return w}
function render(){
 monthsEl.innerHTML='';var count=mq.matches?2:1;
 for(var i=0;i<count;i++)monthsEl.appendChild(month(new Date(Date.UTC(view.getUTCFullYear(),view.getUTCMonth()+i,1))));
 var t=parse(today);prev.disabled=view.getUTCFullYear()*12+view.getUTCMonth()<=t.getUTCFullYear()*12+t.getUTCMonth();
 var l=parse(last);next.disabled=view.getUTCFullYear()*12+view.getUTCMonth()+count-1>=l.getUTCFullYear()*12+l.getUTCMonth();
 hint.textContent=!S.start?'Choose your pick-up day.':!S.end?'Now choose your return day.':'Duration: '+daysTxt(diff(S.start,S.end))+'. Pick another day to start over.'}
prev.addEventListener('click',function(){view.setUTCMonth(view.getUTCMonth()-1);render()});
next.addEventListener('click',function(){view.setUTCMonth(view.getUTCMonth()+1);render()});
if(mq.addEventListener)mq.addEventListener('change',render);
monthsEl.addEventListener('click',function(e){var b=e.target.closest('.ogb-day');if(!b||b.disabled)return;var k=b.dataset.k;
 if(canEnd(k))S.end=k;else if(!blocked.has(k)){S.start=k;S.end=null}
 err(null);render();update();
 var f=monthsEl.querySelector('[data-k="'+k+'"]');if(f)f.focus()});
var pick=document.getElementById('ogb-pick'),drop=document.getElementById('ogb-drop'),pt=document.getElementById('ogb-ptime'),rt=document.getElementById('ogb-rtime');
(function(){var Q=new URLSearchParams(location.search),f=Q.get('from'),t=Q.get('to'),re=/^\d{4}-\d{2}-\d{2}$/;
 if(re.test(f||'')&&re.test(t||'')&&t>f&&f>=today&&diff(f,t)<=C.maxDays&&free(f,t)){S.start=f;S.end=t;view=parse(f);view.setUTCDate(1)}
 function setSel(el,v){if(!v)return;for(var i=0;i<el.options.length;i++){if(el.options[i].value===v){el.value=v;return}}}
 setSel(pt,Q.get('ptime'));setSel(rt,Q.get('rtime'));setSel(pick,Q.get('pickup'));if(Q.get('dropoff')&&Q.get('dropoff')!==Q.get('pickup'))setSel(drop,Q.get('dropoff'));})();
if(C.oneLoc&&pick.options.length>1)pick.selectedIndex=1;
[pick,drop,pt,rt].forEach(function(el){el.addEventListener('change',function(){err(null);update()})});
var gos=root.querySelectorAll('.ogb-go');
function ready1(){return !!(S.start&&S.end&&pick.value)}
var Q=null,qk='';
function curK(){return S.start&&S.end?S.start+'|'+S.end+'|'+B.code:''}
function fetchQ(){syncB();var k=curK();if(!k||!C.quote||k===qk)return;qk=k;var fd=new FormData();fd.append('action','orego_quote');fd.append('og_cur',B.code);fd.append('nonce',C.nonce);fd.append('car',C.car);fd.append('start',S.start);fd.append('end',S.end);
 fetch(C.ajax,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(res){if(k!==curK())return;if(res&&res.success){Q=res.data;Q.k=k;update();if(S.step===2)review('ogb-review')}else if(res&&res.data&&res.data.message){err(res.data.message)}}).catch(function(){})}
function qOK(){return Q&&Q.k===curK()}
function amount(n){return qOK()?Q.total:Math.round(n*C.price*100)/100}
function update(){
 var n=S.start&&S.end?diff(S.start,S.end):0;fetchQ();
 var dR=document.getElementById('ogb-s-disc');if(dR){dR.hidden=!(qOK()&&Q.discount>0);if(qOK())dR.querySelector('dd').textContent='-'+money(Q.discount)}
 document.getElementById('ogb-s-start').textContent=S.start?fShort.format(parse(S.start))+' · '+pt.value:'–';
 document.getElementById('ogb-s-end').textContent=S.end?fShort.format(parse(S.end))+' · '+rt.value:'–';
 document.getElementById('ogb-s-days').textContent=n?daysTxt(n):'–';
 var tot=money(amount(n));document.getElementById('ogb-s-total').textContent=tot;root.querySelector('.ogb-bar-total').textContent=tot;var un=document.getElementById('ogb-usdnote');if(un){un.hidden=!(otherCur()&&n);if(otherCur())un.textContent='≈ '+window.ogMoney(amount(n))+' · you pay in '+B.code;}var pd=document.getElementById('ogb-s-pday');if(pd)pd.textContent=money(C.price);var dp=document.getElementById('ogb-s-dep');if(dp)dp.textContent=money(C.deposit);
 [].forEach.call(gos,function(b){b.disabled=S.step===1?false:false;b.querySelector('span').textContent=S.step===1?'Continue':'Confirm booking';b.classList.toggle('is-wait',S.step===1&&!ready1())})}
function err(msg){var e=root.querySelector('.ogb-step:not([hidden]) .ogb-err');if(!e)return;if(!msg){e.hidden=true;return}e.textContent=msg;e.hidden=false;e.scrollIntoView({behavior:'smooth',block:'center'})}
function review(el){
 var dl=document.getElementById(el),n=diff(S.start,S.end);
 var rows=[['Pick-up',fLong.format(parse(S.start))+' · '+pt.value,pick.value],['Return',fLong.format(parse(S.end))+' · '+rt.value,drop.value||pick.value],['Duration',daysTxt(n),'']];
 if(qOK()){Q.rows.forEach(function(r){rows.push([r.label,(r.amount<0?'-':'')+money(Math.abs(r.amount)),''])})}else{rows.push(['Price',daysTxt(n)+' × '+money(C.price),''])}
 rows.push(['Total',money(amount(n)),'Pay at pick-up'+(C.deposit>0?' · + '+money(C.deposit)+' refundable deposit':'')]);
 dl.innerHTML='';rows.forEach(function(r){var w=document.createElement('div'),dt=document.createElement('dt'),dd=document.createElement('dd');dt.textContent=r[0];dd.textContent=r[1];if(r[2]){var s=document.createElement('small');s.textContent=r[2];dd.appendChild(s)}w.appendChild(dt);w.appendChild(dd);dl.appendChild(w)})}
function go(step){
 S.step=step;[].forEach.call(root.querySelectorAll('.ogb-step'),function(s){s.hidden=+s.dataset.step!==step});
 [].forEach.call(root.querySelectorAll('.ogb-steps li'),function(li){var n=+li.dataset.s;li.classList.toggle('is-on',n===step);li.classList.toggle('is-done',n<step)});
 root.classList.toggle('is-finished',step===3);
 if(step===2)review('ogb-review');
 update();var top=root.getBoundingClientRect().top+window.pageYOffset-90;window.scrollTo({top:top<0?0:top,behavior:'smooth'})}
root.addEventListener('click',function(e){var b=e.target.closest('[data-go]');if(b){go(+b.dataset.go)}});
var form=document.getElementById('ogb-form');
function act(){
 if(S.step===1){
  if(!S.start)return err('Choose your pick-up day on the calendar.');
  if(!S.end)return err('Choose your return day on the calendar.');
  if(!pick.value){err('Choose a pick-up location.');pick.focus();return}
  return go(2)}
 if(S.step===2)submit()}
[].forEach.call(gos,function(b){b.addEventListener('click',act)});
form.addEventListener('submit',function(e){e.preventDefault();submit()});
function submit(){
 var f=form.elements;
 if(f.name.value.trim().length<2){err('Enter your full name.');f.name.focus();return}
 if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email.value.trim())){err('Enter a valid email address.');f.email.focus();return}
 if(f.phone.value.replace(/\D/g,'').length<7){err('Enter a valid phone number.');f.phone.focus();return}
 if(!f.agree.checked){err(null);var ag=f.agree.closest('.ogb-agree');if(ag){ag.classList.remove('is-bad');void ag.offsetWidth;ag.classList.add('is-bad');if(!ag.dataset.w){ag.dataset.w=1;f.agree.addEventListener('change',function(){if(f.agree.checked)ag.classList.remove('is-bad')})}ag.scrollIntoView({behavior:'smooth',block:'center'})}f.agree.focus({preventScroll:true});return}
 err(null);
 var fd=new FormData(form);fd.append('action','orego_book');syncB();fd.append('og_cur',B.code);fd.append('nonce',C.nonce);fd.append('car',C.car);fd.append('start',S.start);fd.append('end',S.end);fd.append('ptime',pt.value);fd.append('rtime',rt.value);fd.append('pickup',pick.value);fd.append('dropoff',drop.value);
 [].forEach.call(gos,function(b){b.disabled=true;b.classList.add('is-busy');b.querySelector('span').textContent='Sending...'});
 fetch(C.ajax,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(res){
  if(res&&res.success){document.getElementById('ogb-ref').textContent='#'+res.data.ref;if(window.ogBookDone)window.ogBookDone(res.data);review('ogb-review-done');go(3);return}
  var d=(res&&res.data)||{};
  if(d.blocked){blocked=new Set(d.blocked);S.start=S.end=null;go(1);render();setTimeout(function(){err(d.message)},350);return}
  err(d.message||'Something went wrong. Please try again.')
 }).catch(function(){err('Could not connect. Check your internet and try again.')}).then(function(){
  [].forEach.call(gos,function(b){b.disabled=false;b.classList.remove('is-busy')});update()})}
document.addEventListener('og:currency',function(){syncB();update();if(S.step===2)review('ogb-review')});
render();update();
})();
JS;
}

function orego_bk_css() {
	return <<<'CSS'
.ogb-depnote{margin:6px 0 0;font-size:13px;color:var(--mu,#6B7280);text-align:center}
.ogb{--a:var(--hx-primary);--a2:var(--hx-primary-dark);--as:var(--hx-primary-soft);--ink:var(--hx-ink);--mu:var(--hx-muted);--su:#9AA1AD;--ln:#E6E8EC;--bg:#F4F5F7;font-family:Cairo,Tahoma,sans-serif;color:var(--ink);max-width:1200px;margin:0 auto;padding:28px 24px 72px;box-sizing:border-box}
.ogb *,.ogb *::before,.ogb *::after{box-sizing:border-box}
.ogb h1,.ogb h2,.ogb h3,.ogb p,.ogb dl,.ogb dd,.ogb ol{margin:0;padding:0}
.ogb button,.ogb input,.ogb select,.ogb textarea{font-family:inherit}
.ogb-back{display:inline-flex;align-items:center;gap:8px;font-size:14px;color:var(--mu);text-decoration:none}
.ogb-back:hover{color:var(--ink)}
.ogb-top h1{font-size:32px;font-weight:800;line-height:1.3;margin-top:12px}
.ogb-steps{list-style:none;display:flex;align-items:center;gap:14px;margin-top:20px!important}
.ogb-steps li{display:flex;align-items:center;gap:10px;color:var(--su)}
.ogb-steps li+li::before{content:"";width:56px;height:2px;border-radius:2px;background:var(--ln);margin-inline-end:4px}
.ogb-steps li.is-done+li::before,.ogb-steps li.is-on:not(:first-child)::before{background:var(--a)}
.ogb-steps b{width:34px;height:34px;flex:none;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;background:#FFFFFF;border:2px solid var(--ln);color:var(--su)}
.ogb-steps span{display:flex;flex-direction:column;line-height:1.3}
.ogb-steps strong{font-size:15px;font-weight:700}
.ogb-steps small{font-size:12px}
.ogb-steps li.is-on{color:var(--ink)}
.ogb-steps li.is-on b{background:var(--a);border-color:var(--a);color:#FFFFFF}
.ogb-steps li.is-done{color:var(--ink)}
.ogb-steps li.is-done b{background:var(--as);border-color:var(--as);color:var(--a)}
.ogb-grid{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:28px;align-items:start;margin-top:26px}
.ogb-main,.ogb-step{display:grid;gap:20px}
.ogb-step[hidden]{display:none}
.ogb-card{background:#FFFFFF;border:1px solid var(--ln);border-radius:20px;padding:24px}
.ogb-card h2{display:flex;align-items:center;gap:10px;font-size:19px;font-weight:700;line-height:1.4}
.ogb-ic{width:34px;height:34px;flex:none;border-radius:10px;background:var(--as);color:var(--a);display:flex;align-items:center;justify-content:center}
.ogb-hint{font-size:14px;color:var(--mu);margin-top:8px!important}
.ogb-cal{position:relative;margin-top:16px}
.ogb-nav{position:absolute;top:-2px;width:36px;height:36px;border-radius:10px;border:1px solid var(--ln);background:#FFFFFF;color:var(--ink);display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;z-index:1}
.ogb-nav:hover:not(:disabled){border-color:var(--ink)}
.ogb-nav:disabled{opacity:.35;cursor:default}
.ogb-prev{inset-inline-start:0}
.ogb-next{inset-inline-end:0}
.ogb-months{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:36px}
.ogb-month h3{text-align:center;font-size:16px;font-weight:700;line-height:32px;margin-bottom:12px!important}
.ogb-days{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));row-gap:4px}
.ogb-wd{text-align:center;font-size:12px;font-weight:600;color:var(--su);padding-bottom:8px}
.ogb-day{position:relative;height:44px;border:0;background:transparent;font-size:15px;font-weight:600;color:var(--ink);cursor:pointer;padding:0;border-radius:12px;transition:background .12s}
.ogb-day:hover:not(:disabled){background:var(--bg)}
.ogb-day:focus-visible{outline:2px solid var(--a);outline-offset:1px;z-index:1}
.ogb-day.is-today{box-shadow:inset 0 0 0 1.5px var(--ink)}
.ogb-day.is-past{color:#C9CDD4;cursor:default}
.ogb-day.is-off{color:#B5BAC3;text-decoration:line-through;cursor:not-allowed;background:repeating-linear-gradient(-45deg,transparent 0 5px,#F1F2F4 5px 7px)}
.ogb-day.is-turn{text-decoration:none;color:var(--ink);cursor:pointer}
.ogb-day.is-in{background:var(--as);border-radius:0;color:var(--a2)}
.ogb-day.is-start,.ogb-day.is-end{background:var(--a)!important;color:#FFFFFF!important;text-decoration:none;box-shadow:none}
.has-range .ogb-day.is-start{border-start-end-radius:0;border-end-end-radius:0}
.has-range .ogb-day.is-end{border-start-start-radius:0;border-end-start-radius:0}
.ogb-legend{display:flex;flex-wrap:wrap;gap:18px;margin-top:16px;font-size:13px;color:var(--mu)}
.ogb-legend span{display:inline-flex;align-items:center;gap:7px}
.ogb-legend i{width:14px;height:14px;border-radius:4px;display:inline-block}
.lg-sel{background:var(--a)}
.lg-off{background:repeating-linear-gradient(-45deg,#FFFFFF 0 3px,#D9DCE2 3px 5px);border:1px solid var(--ln)}
.lg-today{box-shadow:inset 0 0 0 1.5px var(--ink)}
.ogb-times,.ogb-locs,.ogb-fields{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px}
.ogb-times{padding-top:20px;border-top:1px solid var(--ln)}
.ogb-field{display:flex;flex-direction:column;gap:7px;margin:0}
.ogb-field>span:first-child{font-size:14px;font-weight:600;color:var(--ink)}
.ogb-full{grid-column:1/-1}
.ogb-sel{position:relative;display:block}
.ogb-sel svg{position:absolute;top:50%;transform:translateY(-50%);inset-inline-start:14px;color:var(--su);pointer-events:none}
.ogb select,.ogb input:not([type=checkbox]),.ogb textarea{width:100%;min-height:50px;border:1px solid var(--ln)!important;border-radius:12px!important;background:#FFFFFF!important;color:var(--ink);font-size:15px!important;padding:12px 14px!important;box-shadow:none!important;outline:none;transition:border-color .15s,box-shadow .15s;margin:0}
.ogb select{appearance:none;-webkit-appearance:none;cursor:pointer;padding-inline-start:42px!important;padding-inline-end:38px!important;background:#FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2.4' stroke-linecap='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat left 14px center!important}
.ogb select[dir=ltr]{text-align:right}
.ogb textarea{min-height:90px;resize:vertical;line-height:1.6}
.ogb select:focus,.ogb input:focus,.ogb textarea:focus{border-color:var(--a)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--hx-primary) 15%,transparent)!important}
.ogb-hp{position:absolute!important;left:-9999px!important;width:1px!important;height:1px!important;opacity:0}
.ogb-h2row{display:flex;justify-content:space-between;align-items:center}
.ogb-link{border:0;background:none;color:var(--a);font-size:14px;font-weight:700;cursor:pointer;padding:6px}
.ogb-review{display:grid;gap:0;margin-top:14px!important}
.ogb-review div{display:flex;justify-content:space-between;gap:16px;padding:12px 0;border-bottom:1px solid #F0F1F4;font-size:14.5px}
.ogb-review div:last-child{border-bottom:0}
.ogb-review dt{color:var(--mu);flex:none}
.ogb-review dd{text-align:end;font-weight:600}
.ogb-review dd small{display:block;font-weight:400;color:var(--mu);font-size:13px}
.ogb-review div:last-child dd{font-size:18px;font-weight:800}
.ogb-h2b{margin-top:26px}
.ogb-pay{display:flex;gap:14px;align-items:flex-start;margin-top:14px;border:2px solid var(--a);background:#F8FAFF;border-radius:14px;padding:16px}
.ogb-radio{width:20px;height:20px;flex:none;border-radius:50%;border:6px solid var(--a);background:#FFFFFF;margin-top:2px}
.ogb-pay b{display:block;font-size:15px}
.ogb-pay small{display:block;font-size:13px;color:var(--mu);line-height:1.6;margin-top:2px}
.ogb-agree{display:flex;gap:10px;align-items:flex-start;margin-top:18px;font-size:14px;line-height:1.6;color:#374151;cursor:pointer}
.ogb-agree input{width:18px;height:18px;margin-top:3px;flex:none;accent-color:var(--a)}
.ogb-agree.is-bad{color:#DC2626;font-weight:600;animation:ogbShake .4s ease}.ogb-agree.is-bad input{outline:2px solid #DC2626;outline-offset:2px;border-radius:3px}.ogb-agree.is-bad a{color:#DC2626}@keyframes ogbShake{0%,100%{transform:none}20%,60%{transform:translateX(-5px)}40%,80%{transform:translateX(5px)}}@media (prefers-reduced-motion:reduce){.ogb-agree.is-bad{animation:none}}
.ogb-err{background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C;border-radius:12px;padding:12px 16px;font-size:14px;font-weight:600}
.ogb-err[hidden]{display:none}
.ogb-side{position:sticky;top:calc(var(--ogh-h,72px) + var(--wp-admin--admin-bar--height,0px) + 20px)}
.ogb-car{display:flex;gap:14px;align-items:center;padding-bottom:18px;border-bottom:1px solid var(--ln)}
.ogb-car-img{width:96px;height:72px;flex:none;border-radius:12px;overflow:hidden;background:linear-gradient(135deg,#E6E8EC,#D9DCE2)}
.ogb-car-img img{width:100%;height:100%;object-fit:cover;display:block}
.ogb-noimg{display:flex;width:100%;height:100%;align-items:center;justify-content:center;color:var(--su)}
.ogb-car strong{display:block;font-size:16px;font-weight:700;line-height:1.4}
.ogb-car small{display:block;font-size:13px;color:var(--mu)}
.ogb-rows{margin-top:6px!important}
.ogb-rows div{display:flex;justify-content:space-between;gap:12px;padding:10px 0;font-size:14px}
.ogb-rows dt{color:var(--mu)}
.ogb-rows dd{font-weight:600;text-align:end}
.ogb-total{display:flex;justify-content:space-between;align-items:baseline;padding:16px 0;margin-top:4px;border-top:1px solid var(--ln)}
.ogb-total span{font-size:15px;font-weight:700}
.ogb-total strong{font-size:28px;font-weight:800}
.ogb-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;height:54px;border:0;border-radius:14px;background:var(--a);color:#FFFFFF!important;font-size:17px;font-weight:700;cursor:pointer;text-decoration:none;transition:background .15s,opacity .15s}
.ogb-btn:hover:not(:disabled){background:var(--a2)}
.ogb-btn:focus-visible{outline:3px solid color-mix(in srgb,var(--hx-primary) 35%,transparent);outline-offset:2px}
.ogb-btn.is-wait{opacity:.55}
.ogb-btn.is-busy{opacity:.7;cursor:progress}
.ogb-btn:disabled{cursor:default}
.ogb-usdnote{font-size:12.5px;color:var(--mu);text-align:center;margin:-6px 0 12px!important}.ogb-usdnote[hidden]{display:none}.ogb-eq{display:block;font-size:11.5px;font-weight:500;color:var(--mu)}.ogb-eq[hidden]{display:none}.ogb-rows>div[hidden]{display:none!important}
.ogb-note{display:flex;align-items:center;justify-content:center;gap:6px;font-size:12.5px;color:var(--mu);margin-top:12px!important;text-align:center}
.ogb-bar{display:none}
.ogb.is-finished .ogb-side,.ogb.is-finished .ogb-bar{display:none!important}
.ogb.is-finished .ogb-grid{grid-template-columns:minmax(0,640px);justify-content:center}
.ogb-done{text-align:center;padding:40px 28px}
.ogb-done-ic{width:72px;height:72px;margin:0 auto 18px;border-radius:50%;background:#DCFCE7;color:#15803D;display:flex;align-items:center;justify-content:center}
.ogb-done h2{display:block;font-size:24px;font-weight:800}
.ogb-done p{font-size:15px;line-height:1.75;color:#4B5563;margin-top:10px!important}
.ogb-ref b{font-size:18px;color:var(--ink)}
.ogb-done .ogb-review{text-align:start;margin:22px 0!important;background:var(--bg);border-radius:14px;padding:4px 18px}
.ogb-empty{max-width:520px;margin:40px auto;text-align:center;background:#FFFFFF;border:1px solid var(--ln);border-radius:20px;padding:44px 28px;color:var(--su)}
.ogb-empty h1{font-size:24px;font-weight:800;color:var(--ink);margin:12px 0 8px}
.ogb-empty p{font-size:15px;color:var(--mu);margin-bottom:22px!important;line-height:1.7}
.ogb-empty .ogb-btn{max-width:260px;margin:0 auto}
html.translated-ltr .ogb{direction:ltr}
html.translated-ltr .ogb-btn svg,html.translated-ltr .ogb-back svg,html.translated-ltr .ogb-nav svg{transform:scaleX(-1)}
html.translated-ltr .ogb select[dir=ltr]{text-align:left}
@media (max-width:1000px){.ogb-grid{grid-template-columns:minmax(0,1fr) 320px;gap:20px}}
@media (max-width:900px){
.ogb{padding:18px 16px 110px}
.ogb-top h1{font-size:26px}
.ogb-steps li+li::before{width:28px}
.ogb-steps small{display:none}
.ogb-grid{grid-template-columns:1fr;margin-top:18px}
.ogb-side{position:static;order:-1}
.ogb-sum .ogb-rows,.ogb-sum .ogb-total,.ogb-sum .ogb-go,.ogb-sum .ogb-note{display:none}
.ogb-sum{padding:14px}
.ogb-car{padding-bottom:0;border-bottom:0}
.ogb-car-img{width:76px;height:57px}
.ogb-card{padding:18px;border-radius:18px}
.ogb-months{grid-template-columns:1fr}
.ogb-day{height:42px}
.ogb-times,.ogb-locs,.ogb-fields{grid-template-columns:1fr}
.ogb-bar{display:flex;align-items:center;gap:14px;position:fixed;inset-inline:0;bottom:0;z-index:90;background:#FFFFFF;border-top:1px solid var(--ln);box-shadow:0 -8px 24px rgba(21,23,28,.08);padding:12px 16px calc(12px + env(safe-area-inset-bottom))}
.ogb-bar>div{display:flex;flex-direction:column;line-height:1.2}
.ogb-bar small{font-size:12px;color:var(--mu)}
.ogb-bar strong{font-size:22px;font-weight:800}
.ogb-bar .ogb-btn{flex:1;height:50px}
}
@media (prefers-reduced-motion:reduce){.ogb-day,.ogb-btn{transition:none}}
CSS;
}
