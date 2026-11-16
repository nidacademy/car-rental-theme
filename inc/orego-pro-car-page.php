<?php
/**
 * Car page additions:
 * - The small print under Book now: mileage (from the rental conditions) + free cancellation hours.
 * - Phones: features two per row. Desktop: photo thumbnails under the main photo.
 * (The Rental conditions and Availability sections were removed at the owner's request.)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_cond_fields() {
	return array(
		'min_age'  => array( 'Minimum driver age', 'number', 'years' ),
		'licence'  => array( 'Driving licence held for at least', 'number', 'years' ),
		'km'       => array( 'Mileage per day (0 = unlimited)', 'number', 'km' ),
		'fuel'     => array( 'Fuel policy', 'select', array( 'full' => 'Full to full', 'same' => 'Same level back', 'prepaid' => 'Prepaid full tank' ) ),
		'insurance' => array( 'Insurance included', 'text', '' ),
		'deposit'  => array( 'Security deposit', 'number', '$' ),
		'extra'    => array( 'Other conditions (one per line)', 'textarea', '' ),
	);
}

function orego_cond_defaults() {
	$d = get_option( 'orego_cond' );
	$d = is_array( $d ) ? $d : array();
	return wp_parse_args( $d, array(
		'min_age'   => 21,
		'licence'   => 2,
		'km'        => 0,
		'fuel'      => 'full',
		'insurance' => 'Basic insurance (third-party liability and collision damage waiver with excess)',
		'deposit'   => 300,
		'extra'     => "Valid passport or national ID card\nNo smoking in the car",
	) );
}

/* Conditions for one car: its own values, else the defaults. */
function orego_car_conditions( $id ) {
	$c   = orego_cond_defaults();
	$own = get_post_meta( $id, 'og_cond', true );
	foreach ( is_array( $own ) ? $own : array() as $k => $v ) {
		if ( '' !== $v && null !== $v && isset( $c[ $k ] ) ) {
			$c[ $k ] = $v;
		}
	}
	if ( function_exists( 'orego_car_pricing' ) && orego_car_pricing( $id )['deposit'] > 0 && ( ! is_array( $own ) || '' === ( $own['deposit'] ?? '' ) ) ) {
		$c['deposit'] = orego_car_pricing( $id )['deposit']; // the deposit set in "Pricing & deposit"
	}
	return $c;
}

function orego_cond_mileage( $c ) {
	return (int) $c['km'] > 0 ? number_format_i18n( (int) $c['km'] ) . ' km per day included' : 'Unlimited mileage';
}

/* ------------------------------------------------------------------ */
/* Car page                                                            */
/* ------------------------------------------------------------------ */

/* Keep the small print under the Book now button true (mileage + free cancellation). */
add_filter( 'orego_car_main_html', function ( $html, $id ) {
	$c     = orego_car_conditions( $id );
	$hours = function_exists( 'orego_rules' ) ? (int) orego_rules()['cancel_hours'] : 48;
	$html  = preg_replace( '#<p class="ogx-note">.*?</p>#s', '<p class="ogx-note">' . esc_html( orego_cond_mileage( $c ) . ' · Free cancellation up to ' . $hours . ' hours before pick-up' ) . '</p>', $html, 1 );
	return $html;
}, 10, 2 );

/* Phones: car features two per row, like on desktop. */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'orego_car' ) ) {
		return;
	}
	echo '<style id="og-feats-2col">@media (max-width:767px){'
		. 'html body .ogx-fsec .ogx-feats{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:12px 10px!important}'
		. 'html body .ogx-fsec .ogx-feats li{min-width:0;gap:8px!important;font-size:14px!important;line-height:1.3!important;align-items:center!important}'
		. 'html body .ogx-fsec .ogx-feats svg{flex:none;width:22px!important;height:22px!important;padding:4.5px!important}'
		. '}</style>' . "\n";
}, 40 );

/* Desktop: thumbnails of every photo under the main car photo; click one to show it. */
add_action( 'wp_footer', function () {
	if ( ! is_singular( 'orego_car' ) ) {
		return;
	}
	?>
<style id="og-car-thumbs">
.ogx-thumbs{display:none}
.ogx-galcol{min-width:0;display:flex;flex-direction:column}
@media (min-width:901px){
.ogx-galcol{position:sticky;top:16px;align-self:start;z-index:1}
.ogx-galcol .ogd-gal{position:relative!important;top:auto!important}
.ogx-thumbs{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:14px}
.ogx-thumbs.is-many{display:flex;overflow-x:auto;scrollbar-width:thin}
.ogx-thumbs.is-many .ogx-th{flex:0 0 calc(25% - 9px)}
.ogx-th{position:relative;aspect-ratio:4/3;padding:0;border:1.5px solid #ECEEF1;border-radius:14px;background:#FFFFFF;overflow:hidden;cursor:pointer;transition:border-color .15s,box-shadow .15s,transform .15s}
.ogx-th img{width:100%;height:100%;object-fit:contain;display:block;padding:6px;box-sizing:border-box}
.ogx-th:hover{border-color:#C9CDD3;transform:translateY(-1px)}
.ogx-th.is-on{border-color:#15171C;box-shadow:0 0 0 1px #15171C}
.ogx-th:focus-visible{outline:2px solid #15171C;outline-offset:2px}
}
html.og-dark .ogx-th{background:#161A21;border-color:#262B34}html.og-dark .ogx-th.is-on{border-color:#F3F4F6;box-shadow:0 0 0 1px #F3F4F6}
</style>
<script id="og-car-thumbs-js">
(function(){
var g=document.querySelector('.ogd-gal');if(!g)return;var s=g.querySelectorAll('.ogd-slide');if(s.length<2)return;
var w=document.createElement('div');w.className='ogx-thumbs'+(s.length>4?' is-many':'');w.setAttribute('aria-label','Photos');
[].forEach.call(s,function(sl,i){var im=sl.querySelector('img');if(!im)return;var b=document.createElement('button');b.type='button';b.className='ogx-th'+(i===0?' is-on':'');b.setAttribute('aria-label','Photo '+(i+1));
 var t=document.createElement('img');t.alt='';t.loading='lazy';t.src=im.currentSrc||im.src;if(im.srcset){t.srcset=im.srcset;t.sizes='160px';}b.appendChild(t);
 b.addEventListener('click',function(){if(g.ogGo)g.ogGo(i);});w.appendChild(b);});
var col=document.createElement('div');col.className='ogx-galcol';g.parentNode.insertBefore(col,g);col.appendChild(g);col.appendChild(w); /* photo + thumbnails stay in the same column */
g.addEventListener('og:slide',function(e){[].forEach.call(w.children,function(b,i){b.classList.toggle('is-on',i===e.detail)});var on=w.children[e.detail];if(on&&w.classList.contains('is-many'))on.scrollIntoView({block:'nearest',inline:'nearest',behavior:'smooth'});});
})();
</script>
	<?php
}, 50 );
