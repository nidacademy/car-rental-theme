<?php
/**
 * Dark mode for the website.
 * - A moon/sun button in the header (desktop: before "Book now"; phones: before the menu button).
 * - The visitor's choice is remembered; the default (light / follow the device / dark) is set in
 *   Appearance -> Customize -> Dark mode.
 * - How it works: in dark mode a small script reads the site's own stylesheets once and writes a dark
 *   copy of every colour rule (white surfaces -> dark, dark text -> light, black buttons -> white,
 *   coloured accents kept). So Elementor pages and anything added later follow automatically.
 * - The welcome (swipe) page keeps its own design.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_dark_defaults() {
	return array(
		'og_dark_default' => 'light',   // light | auto | dark
		'og_dark_toggle'  => true,
		'og_dark_bg'      => '#0E1116', // page
		'og_dark_surface' => '#161A21', // cards, header, boxes
		'og_dark_text'    => '#E8EAEE',
	);
}

function orego_dark_mod( $k ) {
	$d = orego_dark_defaults();
	$v = get_theme_mod( $k, $d[ $k ] );
	if ( in_array( $k, array( 'og_dark_bg', 'og_dark_surface', 'og_dark_text' ), true ) ) {
		$v = sanitize_hex_color( (string) $v );
		return $v ? $v : $d[ $k ];
	}
	return $v;
}

/** Colours for the dark engine. */
function orego_dark_palette( $own = null ) {
	$mix = function ( $a, $b, $t ) {
		return function_exists( 'orego_mix' ) ? orego_mix( $a, $b, $t ) : $a;
	};
	$bg   = $own ? $own['og_dark_bg'] : orego_dark_mod( 'og_dark_bg' );
	$surf = $own ? $own['og_dark_surface'] : orego_dark_mod( 'og_dark_surface' );
	$text = $own ? $own['og_dark_text'] : orego_dark_mod( 'og_dark_text' );
	return array(
		'bg'    => $bg,
		'surf'  => $surf,
		'surf2' => $mix( $surf, '#ffffff', .045 ),
		'surf3' => $mix( $surf, '#ffffff', .09 ),
		'line'  => $mix( $surf, '#ffffff', .13 ),
		'text'  => $text,
		'inv'   => '#ECEEF1',
		'invTx' => $bg,
	);
}

function orego_dark_active_here() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	if ( isset( $_GET['elementor-preview'] ) || ( function_exists( 'orego_is_editor' ) && orego_is_editor() ) ) { // phpcs:ignore
		return false;
	}
	return true;
}

/* ---------- Customizer ---------- */
add_action( 'customize_register', function ( $wp ) {
	$d = orego_dark_defaults();
	$wp->add_section( 'orego_dark', array(
		'title'       => 'Dark mode',
		'priority'    => 30,
		'description' => 'Visitors switch between light and dark with the moon/sun button in the header; their choice is remembered on their device. Tip: use the button in the preview to see dark mode here.',
	) );
	$wp->add_setting( 'og_dark_default', array(
		'default'           => $d['og_dark_default'],
		'sanitize_callback' => function ( $v ) {
			return in_array( $v, array( 'light', 'auto', 'dark' ), true ) ? $v : 'light';
		},
	) );
	$wp->add_control( 'og_dark_default', array(
		'section'     => 'orego_dark',
		'label'       => 'For new visitors, show',
		'type'        => 'radio',
		'choices'     => array(
			'light' => 'Light',
			'auto'  => 'Follow their phone / computer setting',
			'dark'  => 'Dark',
		),
	) );
	$wp->add_setting( 'og_dark_toggle', array( 'default' => true, 'sanitize_callback' => 'wp_validate_boolean' ) );
	$wp->add_control( 'og_dark_toggle', array(
		'section' => 'orego_dark',
		'label'   => 'Show the light/dark button in the header',
		'type'    => 'checkbox',
	) );
	foreach ( array(
		'og_dark_bg'      => array( 'Page background', 'Behind everything.' ),
		'og_dark_surface' => array( 'Cards & boxes', 'Car cards, header, footer, forms. A bit lighter than the page.' ),
		'og_dark_text'    => array( 'Text', 'Main text colour; secondary text is dimmed from it.' ),
	) as $id => $l ) {
		$wp->add_setting( $id, array( 'default' => $d[ $id ], 'sanitize_callback' => 'sanitize_hex_color' ) );
		$wp->add_control( new WP_Customize_Color_Control( $wp, $id, array(
			'section'     => 'orego_dark',
			'label'       => $l[0],
			'description' => $l[1],
		) ) );
	}
} );

/* ---------- Head: choose the mode before anything is drawn ---------- */
add_action( 'wp_head', function () {
	if ( ! orego_dark_active_here() ) {
		return;
	}
	$p   = orego_dark_palette();
	$cfg = array(
		'def'  => orego_dark_mod( 'og_dark_default' ),
		'btn'  => (bool) orego_dark_mod( 'og_dark_toggle' ),
		'pal'  => $p,
		'logo' => array(
			'match' => 'exotic-cars-logo-dark',
			'src'   => (string) wp_get_attachment_image_url( orego_aid( 'logo_dark' ), 'medium' ),
			'set'   => (string) wp_get_attachment_image_srcset( orego_aid( 'logo_dark' ), 'medium' ),
		),
	);
	if ( function_exists( 'orego_dark_mod_ph' ) ) {
		$pp = array();
		foreach ( array( 'og_dark_bg', 'og_dark_surface', 'og_dark_text' ) as $k ) {
			$pp[ $k ] = orego_dark_mod_ph( $k );
		}
		$ph = array( 'def' => orego_dark_mod_ph( 'og_dark_default' ), 'btn' => (bool) orego_dark_mod_ph( 'og_dark_toggle' ), 'pal' => orego_dark_palette( $pp ) );
		if ( $ph['def'] !== $cfg['def'] || $ph['btn'] !== $cfg['btn'] || $ph['pal'] !== $p ) {
			$cfg['ph'] = $ph;
		}
	}
	echo '<script id="og-dark-boot">window.OGDK=' . wp_json_encode( $cfg ) . ';(function(){var c=window.OGDK,m=null;if(c.ph&&window.matchMedia&&matchMedia("(max-width:767px)").matches){c.def=c.ph.def;c.btn=c.ph.btn;c.pal=c.ph.pal;}try{m=localStorage.getItem("og_theme");}catch(e){}'
		. 'if(m!=="dark"&&m!=="light"){m=c.def==="auto"?(window.matchMedia&&matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light"):c.def;}'
		. 'c.mode=m;if(m==="dark"){document.documentElement.classList.add("og-dark","og-dk-pending");}})();</script>' . "\n"; // phpcs:ignore

	$css = 'html.og-dark{color-scheme:dark;background:' . $p['bg'] . '}'
		. 'html.og-dark body{background:' . $p['bg'] . '!important;color:' . $p['text'] . '}'
		// hide the page for a moment while the dark colours are prepared (shown anyway after 1.2s)
		. '@keyframes ogdkShow{to{visibility:visible}}'
		. 'html.og-dk-pending body{visibility:hidden;animation:ogdkShow 0s 1.2s forwards}'
		// car photo panels stay light (the photos have white backgrounds), slightly dimmed
		. 'html.og-dark .og-media,html.og-dark .ogx-gal,html.og-dark .ogd-gal,html.og-dark .ogb-car-img{filter:brightness(.93)}'
		// the light/dark button
		. '.og-theme-btn{flex:none;width:40px;height:40px;margin-inline-end:10px;padding:0;border-radius:50%;border:1px solid rgba(21,23,28,.12);background:transparent;color:inherit;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s,border-color .15s}'
		. '.og-theme-btn:hover{background:rgba(21,23,28,.06)}'
		. '.og-theme-btn svg{width:19px;height:19px;display:block}'
		. '.og-theme-btn .og-ti-sun{display:none}'
		. 'html.og-dark .og-theme-btn{border-color:rgba(255,255,255,.16)}'
		. 'html.og-dark .og-theme-btn:hover{background:rgba(255,255,255,.08)}'
		. 'html.og-dark .og-theme-btn .og-ti-sun{display:block}html.og-dark .og-theme-btn .og-ti-moon{display:none}'
		. '.ogx2-hb-m .og-theme-btn{width:38px;height:38px;margin-inline-end:4px;border-color:transparent}'
		. '.og-theme-btn:focus-visible{outline:2px solid currentColor;outline-offset:2px}';
	if ( ! empty( $cfg['ph'] ) ) {
		$q    = $cfg['ph']['pal'];
		$css .= '@media (max-width:767px){html.og-dark{background:' . $q['bg'] . '}html.og-dark body{background:' . $q['bg'] . '!important;color:' . $q['text'] . '}}';
	}
	echo '<style id="og-dark-base">' . $css . '</style>' . "\n"; // phpcs:ignore
}, 0 );

/* ---------- Footer: the dark engine and the button ---------- */
add_action( 'wp_footer', function () {
	if ( ! orego_dark_active_here() ) {
		return;
	}
	echo '<script id="og-dark-engine">' . orego_dark_js() . '</script>' . "\n"; // phpcs:ignore
}, 1 );

function orego_dark_js() {
	return <<<'JS'
(function(){
var C=window.OGDK;if(!C)return;
var H=document.documentElement,P=C.pal,built=false,done=typeof WeakSet!=='undefined'?new WeakSet():null,out=null,mo=null;
/* ---- colours ---- */
function parse(s){
	s=String(s).trim().toLowerCase();var m;
	if(s==='white')return{r:255,g:255,b:255,a:1};if(s==='black')return{r:0,g:0,b:0,a:1};
	if(s[0]==='#'){var h=s.slice(1);if(h.length===3||h.length===4)h=h.split('').map(function(x){return x+x;}).join('');if(h.length!==6&&h.length!==8)return null;
		return{r:parseInt(h.substr(0,2),16),g:parseInt(h.substr(2,2),16),b:parseInt(h.substr(4,2),16),a:h.length===8?parseInt(h.substr(6,2),16)/255:1};}
	if((m=s.match(/^rgba?\(([^)]*)\)$/))){var p=m[1].split(/[\s,\/]+/).filter(Boolean);if(p.length<3)return null;
		var f=function(v,i){return /%$/.test(v)?(i===3?parseFloat(v)/100:parseFloat(v)*2.55):parseFloat(v);};
		return{r:f(p[0],0),g:f(p[1],1),b:f(p[2],2),a:p.length>3?f(p[3],3):1};}
	return null;
}
function lum(c){return(.2126*c.r+.7152*c.g+.0722*c.b)/255;}
function sat(c){return(Math.max(c.r,c.g,c.b)-Math.min(c.r,c.g,c.b))/255;}
function str(c){var r=Math.round(c.r),g=Math.round(c.g),b=Math.round(c.b);return c.a<1?'rgba('+r+','+g+','+b+','+(+c.a.toFixed(3))+')':'rgb('+r+','+g+','+b+')';}
function hexc(h,a){var c=parse(h);c.a=a==null?1:a;return c;}
function mix(c,d,t){return{r:c.r+(d.r-c.r)*t,g:c.g+(d.g-c.g)*t,b:c.b+(d.b-c.b)*t,a:c.a};}
function grey(v,a){v=Math.max(0,Math.min(255,v*255));return{r:v,g:v,b:v,a:a};}
var BG=hexc(P.bg),WHITE={r:255,g:255,b:255,a:1},TEXT=hexc(P.text);
/* backgrounds */
function mBg(c,flags){
	if(!c||c.a===0)return null;var l=lum(c),s=sat(c);
	if(s<.13){
		if(l>.8){if(c.a<.5)return null;var o=hexc(l>=.985?P.surf:l>=.94?P.surf2:P.surf3,c.a);return o;}
		if(l<.27){if(flags&&flags.keepDark)return null;if(c.a<.85)return null;flags&&(flags.inv=true);return l<.12?hexc(P.inv,c.a):mix(hexc(P.inv,c.a),BG,.1);}
		var g=1-l;return grey(.12+g*.25,c.a);
	}
	if(l>.82)return mix(c,BG,.84);        // pale tints of the brand colour
	return null;                          // brand colours stay
}
/* text, icons */
function mFg(c,flags){
	if(!c||c.a===0)return null;var l=lum(c),s=sat(c);
	if(flags&&flags.inv){if(s<.13&&l>.6)return hexc(P.invTx,c.a);return null;}
	if(flags&&flags.onColor)return null;
	if(s<.13){if(l>=.6)return null;var t=Math.max(0,Math.min(1,(l)/.6));return mix(TEXT,BG,t*.42);}
	if(l<.45)return mix(c,WHITE,l<.25?.55:.4);
	return null;
}
/* borders, outlines, rings */
function mBd(c,flags){
	if(!c||c.a===0)return null;var l=lum(c),s=sat(c);
	if(s<.13){if(l>.6){var o=hexc(P.line,Math.min(1,c.a<1?c.a*1.6:1));return o;}if(l<.2&&c.a>.5)return hexc(P.text,c.a);return null;}
	if(l>.82)return mix(c,BG,.75);
	return null;
}
var TOK=/#[0-9a-f]{3,8}\b|rgba?\([^)]*\)|(?:^|[\s,(])(?:white|black)(?=$|[\s,)])/gi;
function swap(v,fn,flags){
	var ch=false;var r=v.replace(TOK,function(t){var lead=t.match(/^[\s,(]/)?t[0]:'',tok=lead?t.slice(1):t;var c=parse(tok),n=c&&fn(c,flags);if(!n)return t;ch=true;return lead+str(n);});
	return ch?r:null;
}
/* ---- which declarations to rewrite ---- */
var MEDIA=/og-media|ogx-gal|ogd-gal|ogb-car-img|oglp-visual|\.ow[-\s.{]|og-dk/;
function kindOfVar(name,c){
	if(/line|border|ring|outline/.test(name))return mBd;
	if(/(^|-)(bg|background|surface|soft|softer|addon|card|paper|panel|fill-bg)(-|$)|bg$|-bg-/.test(name))return mBg;
	if(/ink|text|muted|fg|heading|title|label|copy|color-?text|-tx/.test(name))return mFg;
	return null; // other variables: their uses are converted where they are used
}
/* variables: read what they held in light mode */
var VC={},VCS=null;
function resolve(v){
	if(v.indexOf('var(')<0)return v;
	if(!VCS)VCS=getComputedStyle(document.body);
	for(var g=0;g<6&&v.indexOf('var(')>-1;g++){
		v=v.replace(/var\(\s*(--[\w-]+)\s*(?:,\s*([^()]*?(?:\([^()]*\)[^()]*?)*))?\)/g,function(m,n,fb){var x=VC[n];if(x===undefined){x=VCS.getPropertyValue(n).trim();VC[n]=x;}return x||(fb||'').trim();});
	}
	return v;
}
function decls(st,sel){
	var outd=[],flags={keepDark:/oglp|elementor|e-con|og-hero/.test(sel)},media=MEDIA.test(sel),hasColor=false,i,p,v,pr,n;
	var bc=st.getPropertyValue('background-color');if(bc){var bcc=parse(resolve(bc).trim());if(bcc&&bcc.a>.5&&sat(bcc)>=.13)flags.onColor=true;}
	// backgrounds first, so text knows if the box was flipped to light
	for(i=0;i<st.length;i++){p=st[i];if(p!=='background-color'&&p!=='background-image')continue;
		v=st.getPropertyValue(p);if(!v||media)continue;v=resolve(v);
		if(p==='background-image'&&!/gradient/.test(v))continue;
		n=p==='background-image'?swap(v,mBg,{keepDark:true}):swap(v,mBg,flags);if(n)outd.push(p+':'+n+(st.getPropertyPriority(p)?'!important':''));}
	for(i=0;i<st.length;i++){p=st[i];v=st.getPropertyValue(p);if(!v)continue;if(p.indexOf('--')!==0)v=resolve(v);pr=st.getPropertyPriority(p)?'!important':'';
		if(p==='color'||p==='fill'||p==='stroke'||p==='caret-color'||p==='text-decoration-color'||p==='-webkit-text-fill-color'){if(p==='color')hasColor=true;n=swap(v,mFg,flags);}
		else if(/^border-(top|right|bottom|left|block-start|block-end|inline-start|inline-end)-color$|^outline-color$|^column-rule-color$/.test(p)){n=media?null:swap(v,mBd,flags);}
		else if(p==='box-shadow'){n=swap(v,function(c){return c.a>=.9?mBd(c,flags):null;},flags);}
		else if(p.indexOf('--')===0){var t=v.trim(),c1=parse(t);if(c1){var fn=kindOfVar(p,c1);n=fn?(function(){var r=fn(c1,{keepDark:true});return r?str(r):null;})():null;}else if(/gradient/.test(t)&&!media){n=swap(t,mBg,{keepDark:true});}else n=null;}
		else continue;
		if(n)outd.push(p+':'+n+(flags.inv&&(p==='color'||p==='fill'||p==='stroke')?'!important':pr));}
	if(flags.inv&&!hasColor)outd.push('color:'+P.invTx+'!important');
	return outd;
}
/* ---- selectors ---- */
function splitSel(s){var a=[],d=0,cur='';for(var i=0;i<s.length;i++){var ch=s[i];if(ch==='('||ch==='[')d++;else if(ch===')'||ch===']')d--;if(ch===','&&d===0){a.push(cur);cur='';}else cur+=ch;}a.push(cur);return a;}
function pre(sel){return splitSel(sel).map(function(s){s=s.trim();if(!s)return s;
	if(/^:root\b/.test(s))return s.replace(/^:root/,'html.og-dark');
	if(/^html\b/.test(s))return s.replace(/^html/,'html.og-dark');
	return 'html.og-dark '+s;}).join(',');}
/* ---- walk the stylesheets ---- */
function rules(list,acc){
	for(var i=0;i<list.length;i++){var r=list[i];
		try{
		if(r.type===1){var sel=r.selectorText||'';if(!sel||/og-dark|og-dk-|og-theme-btn/.test(sel))continue;var d=decls(r.style,sel);if(d.length)acc.push(pre(sel)+'{'+d.join(';')+'}');}
		else if(r.type===4){var a=[];rules(r.cssRules,a);if(a.length)acc.push('@media '+r.conditionText+'{'+a.join('')+'}');}
		else if(r.type===12){var b=[];rules(r.cssRules,b);if(b.length)acc.push('@supports '+r.conditionText+'{'+b.join('')+'}');}
		else if(r.type===3&&r.styleSheet){sheet(r.styleSheet,acc);}
		else if(r.cssRules&&r.type!==7){var e=[];rules(r.cssRules,e);if(e.length)acc.push(e.join(''));}
		}catch(x){}
	}
}
function sheet(sh,acc){
	if(!sh||(done&&done.has(sh)))return;var n=sh.ownerNode;if(n&&(n.id==='og-dark-css'||n.id==='og-dark-base'))return;
	var list;try{list=sh.cssRules;}catch(e){return;}if(!list)return;if(done)done.add(sh);rules(list,acc);
}
function pass(){
	var acc=[];VCS=null;if(out&&out.sheet)out.sheet.disabled=true;for(var i=0;i<document.styleSheets.length;i++)sheet(document.styleSheets[i],acc);
	if(out&&out.sheet)out.sheet.disabled=false;
	if(acc.length){if(!out){out=document.createElement('style');out.id='og-dark-css';document.head.appendChild(out);}out.appendChild(document.createTextNode(acc.join('\n')));}
	inline(document.body);
}
/* inline style="" and svg fill/stroke attributes */
function inline(root){
	if(!root||!root.querySelectorAll)return;
	var els=root.querySelectorAll('[style*="color"],[style*="background"],[style*="border"],[fill],[stroke]');
	for(var i=0;i<els.length;i++){var el=els[i];if(el.__ogdk||el.closest('.oglp-card'))continue;el.__ogdk=1;
		var st=el.style,orig=el.getAttribute('style')||'',ch=[];
		if(st&&st.length){var flags={};
			[['background-color',mBg],['color',mFg],['border-color',mBd],['border-top-color',mBd],['border-bottom-color',mBd],['border-left-color',mBd],['border-right-color',mBd],['fill',mFg],['stroke',mFg]].forEach(function(x){var v=st.getPropertyValue(x[0]);if(!v||/var\(/.test(v))return;var n=swap(v,x[1],flags);if(n)ch.push([x[0],n]);});}
		['fill','stroke'].forEach(function(a){var v=el.getAttribute(a);if(!v||v==='none'||v==='currentColor')return;var c=parse(v),n=c&&mFg(c,{});if(n)ch.push(['attr:'+a,str(n),v]);});
		if(ch.length)el.__ogdkCh={style:orig,ch:ch};
	}
	applyInline(root);
}
function applyInline(root){
	var on=H.classList.contains('og-dark');var els=(root||document).querySelectorAll('[style],[fill],[stroke]');
	for(var i=0;i<els.length;i++){var el=els[i],x=el.__ogdkCh;if(!x)continue;
		x.ch.forEach(function(c){if(c[0].indexOf('attr:')===0){el.setAttribute(c[0].slice(5),on?c[1]:c[2]);}else if(on)el.style.setProperty(c[0],c[1],'important');});
		if(!on)el.setAttribute('style',x.style);}
}
/* ---- logo: white logo in dark mode ---- */
function logos(){
	var on=H.classList.contains('og-dark'),L=C.logo;if(!L||!L.src)return;
	var imgs=document.querySelectorAll('img');
	for(var i=0;i<imgs.length;i++){var im=imgs[i];
		if(!im.__ogdkLogo){if((im.getAttribute('src')||'').indexOf(L.match)<0)continue;im.__ogdkLogo={src:im.getAttribute('src'),set:im.getAttribute('srcset')};}
		var o=im.__ogdkLogo;if(on){im.setAttribute('src',L.src);if(L.set)im.setAttribute('srcset',L.set);else im.removeAttribute('srcset');}
		else{im.setAttribute('src',o.src);if(o.set)im.setAttribute('srcset',o.set);else im.removeAttribute('srcset');}}
}
/* ---- the button ---- */
var ICON='<svg class="og-ti-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"/></svg><svg class="og-ti-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>';
function label(){return H.classList.contains('og-dark')?'Switch to light mode':'Switch to dark mode';}
function mkBtn(){var b=document.createElement('button');b.type='button';b.className='og-theme-btn notranslate';b.setAttribute('data-og-theme-toggle','');b.innerHTML=ICON;b.setAttribute('aria-label',label());b.title=label();return b;}
function buttons(){
	if(!C.btn)return;
	[['.ogx2-hb-d .ogx2-c-r','.ogx2-cta'],['.ogx2-hb-m .ogx2-c-r','.ogx2-burger']].forEach(function(t){
		document.querySelectorAll(t[0]).forEach(function(col){if(col.querySelector('[data-og-theme-toggle]'))return;var ref=col.querySelector(t[1]);var b=mkBtn();if(ref)col.insertBefore(b,ref);else col.appendChild(b);});
	});
}
function refreshBtns(){document.querySelectorAll('[data-og-theme-toggle]').forEach(function(b){b.setAttribute('aria-label',label());b.title=label();b.setAttribute('aria-pressed',H.classList.contains('og-dark')?'true':'false');});}
function set(mode,save){
	var dark=mode==='dark';if(dark&&!built){built=true;pass();}
	H.classList.toggle('og-dark',dark);C.mode=mode;
	if(save){try{localStorage.setItem('og_theme',mode);}catch(e){}}
	applyInline(document);logos();refreshBtns();
	try{document.dispatchEvent(new CustomEvent('og:theme',{detail:{mode:mode}}));}catch(e){}
}
window.ogSetTheme=function(m){set(m==='dark'?'dark':'light',true);};
document.addEventListener('click',function(e){var b=e.target.closest&&e.target.closest('[data-og-theme-toggle]');if(!b)return;e.preventDefault();set(H.classList.contains('og-dark')?'light':'dark',true);});
/* follow the device when the visitor hasn't chosen */
if(C.def==='auto'&&window.matchMedia){var mq=matchMedia('(prefers-color-scheme: dark)');var saved=null;try{saved=localStorage.getItem('og_theme');}catch(e){}
	if(!saved&&mq.addEventListener)mq.addEventListener('change',function(ev){var s=null;try{s=localStorage.getItem('og_theme');}catch(e){}if(!s)set(ev.matches?'dark':'light',false);});}
/* start */
buttons();
if(H.classList.contains('og-dark')){built=true;pass();logos();}
refreshBtns();
H.classList.remove('og-dk-pending');
function later(){if(built)pass();buttons();if(H.classList.contains('og-dark'))logos();refreshBtns();}
document.addEventListener('DOMContentLoaded',later);window.addEventListener('load',later);
/* things added later (menus, calendars, header refresh in the Customizer) */
if(window.MutationObserver){var tmr=null;mo=new MutationObserver(function(list){var need=false;for(var i=0;i<list.length&&!need;i++){if(list[i].addedNodes.length)need=true;}if(!need)return;clearTimeout(tmr);tmr=setTimeout(function(){buttons();refreshBtns();if(built){pass();}if(H.classList.contains('og-dark'))logos();},60);});
	mo.observe(document.documentElement,{childList:true,subtree:true});}
})();
JS;
}
