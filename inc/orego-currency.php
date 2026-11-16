<?php
/**
 * Currencies: one editable list used by the header switcher, every price, bookings and emails.
 * Edited in Appearance -> Customize -> Currencies. Car prices are stored in US dollars;
 * each currency has a rate "1 USD = x".
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Formats offered for the symbol. */
function orego_currency_positions() {
	return array(
		'before'       => 'Before: $55',
		'before_space' => 'Before, with a space: CHF 55',
		'after_space'  => 'After, with a space: 55 MAD',
		'after'        => 'After: 55€',
	);
}

/** The list the site started with (rates come from the old settings). */
function orego_currency_seed() {
	$old = get_option( 'orego_rates' );
	$old = is_array( $old ) ? $old : array();
	$def = in_array( $old['default'] ?? 'USD', array( 'USD', 'MAD', 'EUR' ), true ) ? $old['default'] : 'USD';
	return array(
		array( 'code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'pos' => 'before', 'dec' => 'auto', 'rate' => 1, 'on' => true, 'def' => 'USD' === $def, 'langs' => array( 'en' ), 'img' => '' ),
		array( 'code' => 'MAD', 'name' => 'Moroccan Dirham', 'symbol' => 'MAD', 'pos' => 'after_space', 'dec' => '0', 'rate' => (float) ( $old['MAD'] ?? 9.2 ) ?: 9.2, 'on' => true, 'def' => 'MAD' === $def, 'langs' => array( 'ar', 'ary' ), 'img' => '' ),
		array( 'code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'pos' => 'before', 'dec' => 'auto', 'rate' => (float) ( $old['EUR'] ?? 0.86 ) ?: 0.86, 'on' => true, 'def' => 'EUR' === $def, 'langs' => array( 'fr', 'es', 'de', 'it', 'pt' ), 'img' => '' ),
	);
}

/** Clean up a list (from the Customizer or the database). Always keeps USD at rate 1 and exactly one default. */
function orego_currency_clean( $list ) {
	if ( is_string( $list ) ) {
		$list = json_decode( $list, true ); // the Customizer has already removed slashes
	}
	if ( ! is_array( $list ) ) {
		return orego_currency_seed();
	}
	$pos  = orego_currency_positions();
	$out  = array();
	$seen = array();
	foreach ( $list as $c ) {
		if ( ! is_array( $c ) ) {
			continue;
		}
		$code = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) ( $c['code'] ?? '' ) ) );
		if ( 3 !== strlen( $code ) || isset( $seen[ $code ] ) ) {
			continue;
		}
		$seen[ $code ] = true;
		$langs         = $c['langs'] ?? array();
		$langs         = is_array( $langs ) ? $langs : explode( ',', (string) $langs );
		$out[]         = array(
			'code'   => $code,
			'name'   => mb_substr( sanitize_text_field( (string) ( $c['name'] ?? $code ) ), 0, 40 ) ?: $code,
			'symbol' => mb_substr( sanitize_text_field( (string) ( $c['symbol'] ?? $code ) ), 0, 6 ) ?: $code,
			'pos'    => isset( $pos[ $c['pos'] ?? '' ] ) ? $c['pos'] : 'after_space',
			'dec'    => in_array( (string) ( $c['dec'] ?? 'auto' ), array( 'auto', '0', '2' ), true ) ? (string) $c['dec'] : 'auto',
			'rate'   => 'USD' === $code ? 1 : max( 0.000001, round( (float) ( $c['rate'] ?? 1 ), 6 ) ),
			'on'     => 'USD' === $code && empty( $c['on'] ) && empty( $c['def'] ) ? false : ! empty( $c['on'] ),
			'def'    => ! empty( $c['def'] ),
			'langs'  => array_values( array_unique( array_filter( array_map( function ( $l ) {
				return preg_replace( '/[^a-zA-Z-]/', '', trim( (string) $l ) );
			}, $langs ) ) ) ),
			'img'    => esc_url_raw( (string) ( $c['img'] ?? '' ) ),
		);
	}
	// Prices are stored in US dollars, so USD must stay in the list (it can be hidden from visitors).
	if ( ! isset( $seen['USD'] ) ) {
		array_unshift( $out, orego_currency_seed()[0] );
		$out[0]['def'] = false;
	}
	// Exactly one default, and it must be shown.
	$def = null;
	foreach ( $out as $i => $c ) {
		if ( $c['def'] && $c['on'] && null === $def ) {
			$def = $i;
		}
		$out[ $i ]['def'] = false;
	}
	if ( null === $def ) {
		foreach ( $out as $i => $c ) {
			if ( $c['on'] ) {
				$def = $i;
				break;
			}
		}
	}
	if ( null === $def ) { // nothing shown: show USD
		foreach ( $out as $i => $c ) {
			if ( 'USD' === $c['code'] ) {
				$out[ $i ]['on'] = true;
				$def             = $i;
			}
		}
	}
	$out[ $def ]['def'] = true;
	return $out;
}

/** All currencies, shown or hidden, in the saved order. */
function orego_currency_list() {
	$raw = get_option( 'orego_currency_list', null );
	return orego_currency_clean( null === $raw || '' === $raw ? orego_currency_seed() : $raw );
}

/** Currencies visitors can pick in the header. */
function orego_currency_enabled() {
	return array_values( array_filter( orego_currency_list(), fn( $c ) => $c['on'] ) );
}

function orego_currency_get( $code ) {
	foreach ( orego_currency_list() as $c ) {
		if ( $c['code'] === $code ) {
			return $c;
		}
	}
	return null;
}

function orego_currency_default() {
	foreach ( orego_currency_list() as $c ) {
		if ( $c['def'] ) {
			return $c['code'];
		}
	}
	return 'USD';
}

/** Language code => currency code it switches to. */
function orego_currency_lang_map() {
	$m = array();
	foreach ( orego_currency_enabled() as $c ) {
		foreach ( $c['langs'] as $l ) {
			if ( ! isset( $m[ $l ] ) ) {
				$m[ $l ] = $c['code'];
			}
		}
	}
	return $m;
}

/** Format an amount that is already in $code. $dec: force 0 or 2 decimals when the currency allows it. */
function orego_currency_format( $v, $code, $dec = null ) {
	$c = orego_currency_get( $code );
	if ( ! $c ) {
		$c = array( 'symbol' => $code, 'pos' => 'after_space', 'dec' => 'auto' );
	}
	$v   = round( (float) $v, 2 );
	$neg = $v < 0;
	$v   = abs( $v );
	if ( '0' === $c['dec'] ) {
		$v = round( $v );
		$d = 0;
	} elseif ( '2' === $c['dec'] ) {
		$d = 2;
	} else {
		$d = null !== $dec ? (int) $dec : ( floor( $v ) == $v ? 0 : 2 );
	}
	$n = number_format( $v, $d );
	$s = $c['symbol'];
	switch ( $c['pos'] ) {
		case 'before':
			$o = $s . $n;
			break;
		case 'before_space':
			$o = $s . ' ' . $n;
			break;
		case 'after':
			$o = $n . $s;
			break;
		default:
			$o = $n . ' ' . $s;
	}
	return ( $neg ? '-' : '' ) . $o;
}

/** What the browser needs: formats of every currency, rates of the shown ones, the language map. */
function orego_currency_js_cfg() {
	$curs  = array();
	$rates = array();
	foreach ( orego_currency_list() as $c ) {
		$curs[ $c['code'] ] = array( 's' => $c['symbol'], 'p' => $c['pos'], 'd' => $c['dec'] );
		if ( $c['on'] ) {
			$rates[ $c['code'] ] = (float) $c['rate'];
		}
	}
	return array( 'rates' => $rates, 'def' => orego_currency_default(), 'curs' => $curs, 'lmap' => (object) orego_currency_lang_map() );
}

/** Flag for a currency without a built-in one: its image, or a dark badge with the symbol. */
function orego_currency_flag_inner( $code ) {
	$c = orego_currency_get( $code );
	if ( $c && $c['img'] ) {
		return '<image href="' . esc_url( $c['img'] ) . '" width="32" height="32" preserveAspectRatio="xMidYMid slice"/>';
	}
	$s  = $c ? $c['symbol'] : $code;
	$fs = mb_strlen( $s ) >= 3 ? 9.5 : ( 2 === mb_strlen( $s ) ? 12 : 16 );
	return '<rect width="32" height="32" fill="#15171C"/><text x="16" y="16" dy=".36em" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-weight="700" font-size="' . $fs . '" fill="#FFFFFF">' . esc_html( $s ) . '</text>';
}

/* ------------------------------------------------------------------ */
/* Customizer                                                          */
/* ------------------------------------------------------------------ */

function orego_currency_presets() {
	return array(
		'GBP' => array( 'British Pound', '£', 'before', 'auto', 0.79 ),
		'CHF' => array( 'Swiss Franc', 'CHF', 'before_space', 'auto', 0.88 ),
		'CAD' => array( 'Canadian Dollar', 'CA$', 'before', 'auto', 1.37 ),
		'AUD' => array( 'Australian Dollar', 'A$', 'before', 'auto', 1.52 ),
		'SAR' => array( 'Saudi Riyal', 'SAR', 'after_space', '0', 3.75 ),
		'AED' => array( 'UAE Dirham', 'AED', 'after_space', '0', 3.67 ),
		'QAR' => array( 'Qatari Riyal', 'QAR', 'after_space', '0', 3.64 ),
		'KWD' => array( 'Kuwaiti Dinar', 'KWD', 'after_space', '2', 0.31 ),
		'EGP' => array( 'Egyptian Pound', 'EGP', 'after_space', '0', 48 ),
		'TND' => array( 'Tunisian Dinar', 'TND', 'after_space', '2', 3.1 ),
		'DZD' => array( 'Algerian Dinar', 'DZD', 'after_space', '0', 134 ),
		'TRY' => array( 'Turkish Lira', '₺', 'before', 'auto', 34 ),
		'SEK' => array( 'Swedish Krona', 'kr', 'after_space', '0', 10.5 ),
		'NOK' => array( 'Norwegian Krone', 'kr', 'after_space', '0', 10.8 ),
		'DKK' => array( 'Danish Krone', 'kr.', 'after_space', '0', 6.9 ),
		'PLN' => array( 'Polish Zloty', 'zł', 'after_space', 'auto', 3.9 ),
		'JPY' => array( 'Japanese Yen', '¥', 'before', '0', 150 ),
		'CNY' => array( 'Chinese Yuan', 'CN¥', 'before', 'auto', 7.2 ),
		'INR' => array( 'Indian Rupee', '₹', 'before', '0', 84 ),
		'RUB' => array( 'Russian Ruble', '₽', 'after_space', '0', 95 ),
		'BRL' => array( 'Brazilian Real', 'R$', 'before_space', 'auto', 5.6 ),
		'MXN' => array( 'Mexican Peso', 'MX$', 'before', 'auto', 19.5 ),
	);
}

add_action( 'customize_register', function ( $wp ) {
	if ( ! class_exists( 'OreGO_Currency_List_Control' ) ) {
		class OreGO_Currency_List_Control extends WP_Customize_Control {
			public $type = 'orego_currency_list';

			public function render_content() {
				echo '<span class="customize-control-title">' . esc_html( $this->label ) . '</span>';
				if ( $this->description ) {
					echo '<span class="description customize-control-description">' . wp_kses_post( $this->description ) . '</span>';
				}
				echo '<input type="hidden" class="ogcur-value" ';
				$this->link();
				echo ' value="' . esc_attr( (string) $this->value() ) . '">';
				echo '<div class="ogcur-rows"></div>';
				echo '<div class="ogcur-add"><select class="ogcur-preset" aria-label="Currency to add"><option value="">Add a currency…</option>';
				foreach ( orego_currency_presets() as $k => $p ) {
					echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $k . ' · ' . $p[0] ) . '</option>';
				}
				echo '<option value="custom">Other (type it in)</option></select><button type="button" class="button ogcur-add-btn">Add</button></div>';
			}
		}
	}

	$wp->add_section( 'orego_currencies', array(
		'title'       => 'Currencies',
		'priority'    => 28,
		'description' => 'The currencies visitors can choose in the header. Car prices are set in US dollars and converted with the rates below. Visitors book and pay in the currency they chose.',
	) );
	$wp->add_setting( 'orego_currency_list', array(
		'type'              => 'option',
		'default'           => wp_json_encode( orego_currency_seed(), JSON_UNESCAPED_UNICODE ),
		'transport'         => 'refresh',
		'sanitize_callback' => function ( $v ) {
			return wp_json_encode( orego_currency_clean( $v ), JSON_UNESCAPED_UNICODE );
		},
	) );
	$wp->add_control( new OreGO_Currency_List_Control( $wp, 'orego_currency_list', array(
		'label'       => 'Your currencies',
		'description' => 'Open a currency to edit it. Rates are not updated automatically: check them from time to time.',
		'section'     => 'orego_currencies',
	) ) );
}, 22 );

/* Saved currencies show up at once for visitors. */
add_action( 'customize_save_orego_currency_list', function () {
	add_action( 'customize_save_after', function () {
		do_action( 'litespeed_purge_all' );
	} );
} );

add_action( 'customize_controls_enqueue_scripts', function () {
	wp_enqueue_media();
	$langs = function_exists( 'orego_langs' ) ? orego_langs() : array( 'en' => array( 'English', 'EN' ) );
	$lab   = array();
	foreach ( $langs as $k => $l ) {
		$lab[ $k ] = is_array( $l ) ? $l[0] : (string) $l;
	}
	$data = array(
		'positions' => orego_currency_positions(),
		'presets'   => orego_currency_presets(),
		'langs'     => $lab,
		'builtin'   => array( 'USD', 'MAD', 'EUR' ),
	);
	wp_register_style( 'orego-cz-cur', false, array(), '1.0' );
	wp_enqueue_style( 'orego-cz-cur' );
	wp_add_inline_style( 'orego-cz-cur', <<<'CSS'
.ogcur-rows{display:grid;gap:8px;margin:10px 0 12px}
.ogcur-row{background:#fff;border:1px solid #DCDCDE;border-radius:10px;overflow:hidden}
.ogcur-row.is-off .ogcur-head{opacity:.6}
.ogcur-head{display:flex;align-items:center;gap:10px;width:100%;padding:10px 12px;border:0;background:none;text-align:start;cursor:pointer;font:inherit}
.ogcur-head:hover{background:#F6F7F7}
.ogcur-badge{flex:0 0 auto;min-width:34px;height:24px;padding:0 6px;border-radius:6px;background:#1D2327;color:#fff;font-weight:700;font-size:12px;display:inline-flex;align-items:center;justify-content:center}
.ogcur-t{flex:1;min-width:0}.ogcur-t b{display:block;font-size:13px;color:#1D2327}.ogcur-t small{display:block;color:#646970;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ogcur-tag{font-size:11px;font-weight:600;border-radius:999px;padding:2px 8px;background:#E7F1FB;color:#135E96}
.ogcur-tag.off{background:#F0F0F1;color:#646970}
.ogcur-chev{color:#8C8F94;transition:transform .15s}.ogcur-row.is-open .ogcur-chev{transform:rotate(180deg)}
.ogcur-body{display:none;padding:4px 12px 14px;border-top:1px solid #F0F0F1}
.ogcur-row.is-open .ogcur-body{display:grid;gap:10px}
.ogcur-f{display:grid;gap:4px}.ogcur-f>span{font-size:12px;font-weight:600;color:#1D2327}.ogcur-f small{color:#646970;font-weight:400}
.ogcur-f input[type=text],.ogcur-f input[type=number],.ogcur-f select{width:100%;max-width:none}
.ogcur-2{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.ogcur-chk{display:flex;align-items:center;gap:8px;font-size:13px}
.ogcur-langs{display:flex;flex-wrap:wrap;gap:6px}.ogcur-langs label{display:inline-flex;align-items:center;gap:4px;font-size:12px;background:#F6F7F7;border:1px solid #DCDCDE;border-radius:6px;padding:3px 7px}
.ogcur-ex{margin:0;font-size:12px;color:#1D2327;background:#F6F7F7;border-radius:6px;padding:7px 9px}
.ogcur-img{display:flex;align-items:center;gap:8px}.ogcur-img img{width:28px;height:28px;border-radius:50%;object-fit:cover;border:1px solid #DCDCDE}
.ogcur-foot{display:flex;justify-content:space-between;gap:8px;padding-top:4px}
.ogcur-del{color:#B32D2E!important;border-color:#B32D2E!important}
.ogcur-add{display:flex;gap:6px}.ogcur-add select{flex:1;max-width:none}
.ogcur-err{color:#B32D2E;font-size:12px;margin:0}
CSS
	);
	wp_add_inline_script( 'customize-controls', 'window.ogCurCz=' . wp_json_encode( $data ) . ';' . <<<'JS'
(function(api,$){
var D=window.ogCurCz;
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function fmt(v,c){var d=c.dec==='0'?0:c.dec==='2'?2:(Math.round(v*100)%100?2:0);if(c.dec==='0')v=Math.round(v);var n=v.toLocaleString('en-US',{minimumFractionDigits:d,maximumFractionDigits:d}),s=c.symbol||c.code;return c.pos==='before'?s+n:c.pos==='before_space'?s+' '+n:c.pos==='after'?n+s:n+' '+s}
api.controlConstructor.orego_currency_list=api.Control.extend({ready:function(){
 var ctl=this,box=ctl.container,rows=box.find('.ogcur-rows'),list=[],open={};
 try{list=JSON.parse(ctl.setting.get())||[]}catch(e){list=[]}
 function save(){ctl.setting.set(JSON.stringify(list))}
 function setDef(i){list.forEach(function(c,j){c.def=j===i});if(i>-1)list[i].on=true}
 function render(){
  rows.empty();
  list.forEach(function(c,i){
   var base=c.code==='USD',ex=fmt(50*(base?1:Number(c.rate)||0),c);
   var langs=Object.keys(D.langs).map(function(k){return '<label><input type="checkbox" data-lang="'+esc(k)+'"'+((c.langs||[]).indexOf(k)>-1?' checked':'')+'>'+esc(D.langs[k])+'</label>'}).join('');
   var pos=Object.keys(D.positions).map(function(k){return '<option value="'+k+'"'+(c.pos===k?' selected':'')+'>'+esc(D.positions[k])+'</option>'}).join('');
   var row=$('<div class="ogcur-row'+(c.on?'':' is-off')+(open[c.code+i]?' is-open':'')+'"></div>');
   row.append('<button type="button" class="ogcur-head" aria-expanded="'+(open[c.code+i]?'true':'false')+'"><span class="ogcur-badge">'+esc(c.symbol||c.code)+'</span><span class="ogcur-t"><b>'+esc(c.code)+' · '+esc(c.name)+'</b><small>'+(base?'Base currency of your prices':'1 USD = '+esc(c.rate)+' '+esc(c.code))+'</small></span>'+(c.def?'<span class="ogcur-tag">Default</span>':'')+(c.on?'':'<span class="ogcur-tag off">Hidden</span>')+'<span class="dashicons dashicons-arrow-down-alt2 ogcur-chev"></span></button>');
   var b=$('<div class="ogcur-body"></div>');
   b.append('<div class="ogcur-2"><label class="ogcur-f"><span>Code</span><input type="text" data-k="code" maxlength="3" value="'+esc(c.code)+'"'+(base?' readonly':'')+'></label><label class="ogcur-f"><span>Symbol</span><input type="text" data-k="symbol" maxlength="6" value="'+esc(c.symbol)+'"></label></div>');
   b.append('<label class="ogcur-f"><span>Name</span><input type="text" data-k="name" maxlength="40" value="'+esc(c.name)+'"></label>');
   if(!base)b.append('<label class="ogcur-f"><span>Exchange rate <small>1 USD = ? '+esc(c.code)+'</small></span><input type="number" data-k="rate" step="any" min="0.000001" value="'+esc(c.rate)+'"></label>');
   b.append('<div class="ogcur-2"><label class="ogcur-f"><span>Symbol position</span><select data-k="pos">'+pos+'</select></label><label class="ogcur-f"><span>Decimals</span><select data-k="dec"><option value="auto"'+(c.dec==='auto'?' selected':'')+'>Only when needed</option><option value="0"'+(c.dec==='0'?' selected':'')+'>Never (round)</option><option value="2"'+(c.dec==='2'?' selected':'')+'>Always 2</option></select></label></div>');
   b.append('<p class="ogcur-ex">A $50 car shows as <b>'+esc(ex)+'</b></p>');
   b.append('<label class="ogcur-chk"><input type="checkbox" data-k="on"'+(c.on?' checked':'')+'> Show in the currency switcher</label>');
   b.append('<label class="ogcur-chk"><input type="radio" name="ogcur-def-'+ctl.id+'" data-k="def"'+(c.def?' checked':'')+'> Default for new visitors</label>');
   b.append('<div class="ogcur-f"><span>Switch to it automatically for <small>(languages)</small></span><div class="ogcur-langs">'+langs+'</div></div>');
   if(D.builtin.indexOf(c.code)<0)b.append('<div class="ogcur-f"><span>Flag <small>(optional, else a badge with the symbol)</small></span><div class="ogcur-img">'+(c.img?'<img src="'+esc(c.img)+'" alt="">':'')+'<button type="button" class="button ogcur-pick">'+(c.img?'Change':'Choose image')+'</button>'+(c.img?'<button type="button" class="button-link ogcur-unpick">Remove</button>':'')+'</div></div>');
   b.append('<div class="ogcur-foot"><span><button type="button" class="button ogcur-up"'+(i===0?' disabled':'')+' aria-label="Move up">↑</button> <button type="button" class="button ogcur-down"'+(i===list.length-1?' disabled':'')+' aria-label="Move down">↓</button></span>'+(base?'<small style="color:#646970;align-self:center">Can be hidden, not removed</small>':'<button type="button" class="button ogcur-del">Remove</button>')+'</div>');
   row.append(b);
   row.on('click','.ogcur-head',function(){open[c.code+i]=!open[c.code+i];render()});
   row.on('change input','[data-k]',function(e){var k=$(this).data('k'),v;
    if(k==='on'){c.on=this.checked;if(!c.on&&c.def){var n=list.findIndex(function(x,j){return j!==i&&x.on});setDef(n)}}
    else if(k==='def'){setDef(i)}
    else if(k==='code'){v=this.value.toUpperCase().replace(/[^A-Z]/g,'').slice(0,3);this.value=v;c.code=v}
    else if(k==='rate'){c.rate=parseFloat(this.value)||0}
    else c[k]=this.value;
    save();if(e.type==='change')render();
   });
   row.on('change','[data-lang]',function(){var l=$(this).data('lang');c.langs=(c.langs||[]).filter(function(x){return x!==l});if(this.checked){c.langs.push(l);list.forEach(function(o){if(o!==c)o.langs=(o.langs||[]).filter(function(x){return x!==l})})}save();render()});
   row.on('click','.ogcur-del',function(){if(!window.confirm('Remove '+c.code+'? Bookings already made in '+c.code+' keep showing their amounts.'))return;var wasDef=c.def;list.splice(i,1);if(wasDef){var n=list.findIndex(function(x){return x.on});setDef(n)}save();render()});
   row.on('click','.ogcur-up,.ogcur-down',function(){var j=$(this).hasClass('ogcur-up')?i-1:i+1;if(j<0||j>=list.length)return;var t=list[i];list[i]=list[j];list[j]=t;open={};open[t.code+j]=true;save();render()});
   row.on('click','.ogcur-pick',function(){var f=wp.media({title:'Flag for '+c.code,library:{type:'image'},multiple:false,button:{text:'Use this image'}});f.on('select',function(){var a=f.state().get('selection').first().toJSON();c.img=(a.sizes&&a.sizes.thumbnail?a.sizes.thumbnail.url:a.url);save();render()});f.open()});
   row.on('click','.ogcur-unpick',function(){c.img='';save();render()});
   rows.append(row);
  });
 }
 box.on('click','.ogcur-add-btn',function(){
  var k=box.find('.ogcur-preset').val();if(!k)return;
  var p=D.presets[k],c;
  if(k==='custom')c={code:'',name:'New currency',symbol:'',pos:'after_space',dec:'auto',rate:1,on:true,def:false,langs:[],img:''};
  else{if(list.some(function(x){return x.code===k})){window.alert(k+' is already in your list.');return}c={code:k,name:p[0],symbol:p[1],pos:p[2],dec:p[3],rate:p[4],on:true,def:false,langs:[],img:''}}
  list.push(c);open={};open[c.code+(list.length-1)]=true;box.find('.ogcur-preset').val('');save();render();
 });
 render();
}});
})(wp.customize,jQuery);
JS
	);
} );
