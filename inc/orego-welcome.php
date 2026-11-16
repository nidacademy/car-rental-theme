<?php
/**
 * Welcome screen (/welcome/): logo, "Welcome to Exotic Cars", a big car photo and a
 * "Start your journey" slider. Visitors drag the knob to the right to enter the site.
 * On phones every entry from outside the site starts here; desktop skips it.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_welcome_url() {
	return orego_purl( 'welcome' );
}

/* The welcome page (id 253) is now the site's home page. */
function orego_welcome_is_page() {
	return orego_is_page( 'welcome' ) || ( is_front_page() && orego_pid( 'welcome' ) === (int) get_option( 'page_on_front' ) );
}

/* Old links to /welcome/ go to the home page. */
add_action( 'template_redirect', function () {
	if ( 'welcome' === trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ) ) { // phpcs:ignore
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 1 );

/* The page is drawn here, full screen, without the site header and footer. */
add_filter( 'template_include', function ( $tpl ) {
	if ( ! orego_welcome_is_page() ) {
		return $tpl;
	}
	do_action( 'litespeed_control_set_nocache', 'welcome screen' );
	orego_welcome_render();
	exit;
}, 99 );

function orego_welcome_render() {
	$logo  = wp_get_attachment_image_url( orego_aid( 'logo' ), 'full' ); // Exotic Cars logo for light backgrounds
	$car   = function_exists( 'orego_intro_mod' ) ? ( absint( orego_intro_mod( 'og_welcome_car' ) ) ?: orego_aid( 'car' ) ) : orego_aid( 'car' ); // Dacia Sandero: faces left, so it can run off the right edge
	$src   = ( function_exists( 'orego_intro_photo_d' ) && orego_intro_photo_d() ) ? '' : wp_get_attachment_image_url( $car, 'large' );
	$set   = wp_get_attachment_image_srcset( $car, 'full' );
	$fonts = get_stylesheet_directory_uri() . '/assets/fonts/';
	$name  = get_bloginfo( 'name' );
	?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<?php do_action( 'orego_welcome_head_first' ); ?>
<?php if ( defined( 'RANK_MATH_VERSION' ) ) : do_action( 'rank_math/head' ); elseif ( is_front_page() ) : ?><meta name="description" content="Discover, reserve and drive the ideal car for every trip with <?php echo esc_attr( orego_brand() ); ?> car rental."><link rel="canonical" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php else : ?><meta name="robots" content="noindex,follow"><?php endif; ?>
<meta name="theme-color" content="#F3F3F5">
<title><?php echo defined( 'RANK_MATH_VERSION' ) ? esc_html( wp_get_document_title() ) : ( is_front_page() ? esc_html( orego_brand() ) . ' &ndash; Car Rental' : 'Welcome &ndash; ' . esc_html( orego_brand() ) ); ?></title>
<link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url( $fonts . 'plex-arabic-700-latin.woff2' ); ?>" crossorigin>
<?php if ( $src ) : ?><link rel="preload" as="image" href="<?php echo esc_url( $src ); ?>" imagesrcset="<?php echo esc_attr( (string) $set ); ?>" imagesizes="(min-width:960px) 60vw, (min-width:700px) 700px, 152vw"><?php endif; ?>
<style>
@font-face{font-family:'Plex';font-weight:400;font-display:swap;src:url('<?php echo esc_url( $fonts . 'plex-arabic-400-latin.woff2' ); ?>') format('woff2')}
@font-face{font-family:'Plex';font-weight:700;font-display:swap;src:url('<?php echo esc_url( $fonts . 'plex-arabic-700-latin.woff2' ); ?>') format('woff2')}
:root{--ink:#1B1C1F;--muted:#6E7076;--pill:#0B0B0D}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;background:#FFFFFF}
body{font-family:'Plex',system-ui,-apple-system,'Segoe UI',sans-serif;color:var(--ink);overflow:hidden;-webkit-font-smoothing:antialiased;overscroll-behavior:none;-webkit-tap-highlight-color:transparent}
/* Exactly the visible screen, whatever the phone's browser bars are doing */
.ow{position:fixed;inset:0;display:flex;flex-direction:column;padding:calc(env(safe-area-inset-top) + 30px) 26px calc(env(safe-area-inset-bottom) + 22px);background:linear-gradient(180deg,#F1F1F3 0%,#F8F8F9 26%,#FFFFFF 44%);transition:transform .55s cubic-bezier(.7,0,.3,1),opacity .55s ease}
.ow-in{position:relative;flex:1;display:flex;flex-direction:column;width:100%;max-width:520px;margin:0 auto;min-height:0}
.ow-top{position:relative;z-index:2}
.ow-track{z-index:2}
.ow-logo{display:flex;justify-content:center}
.ow-logo img{display:block;width:min(64vw,250px);height:auto}
.ow-kicker{margin-top:52px;font-size:19px;font-weight:400;letter-spacing:.04em;text-transform:uppercase;color:var(--muted)}
.ow-title{margin-top:2px;font-size:clamp(38px,12vw,62px);line-height:1.04;font-weight:700;letter-spacing:-.01em;text-transform:uppercase;white-space:nowrap}
.ow-sub{margin-top:16px;max-width:21em;font-size:17px;line-height:1.6;color:var(--muted)}
.ow-sub b{color:var(--ink);font-weight:700}
/* the car fills the space between the text and the slider, running off the right edge */
.ow-stage{position:relative;flex:1;min-height:150px;margin:0 -26px;container-type:size;--w:150vw;--w:clamp(140cqw,150cqh,165cqw)}
.ow-floor{position:absolute;left:calc(38px - var(--w) * .02);width:calc(var(--w) * .92);top:calc(50% + var(--w) * .2);height:calc(var(--w) * .1);transform:translateY(-50%);border-radius:50%;background:radial-gradient(closest-side,rgba(21,23,28,.16),rgba(21,23,28,0));pointer-events:none}
.ow-car{position:absolute;left:-8%;left:calc(38px - var(--w) * .09);top:50%;width:150%;width:var(--w);max-width:none;height:auto;transform:translateY(-55%);mix-blend-mode:multiply;-webkit-mask-image:linear-gradient(90deg,transparent 6%,#000 10%),linear-gradient(180deg,transparent 18%,#000 28%,#000 84%,transparent 96%);-webkit-mask-composite:source-in;mask-image:linear-gradient(90deg,transparent 6%,#000 10%),linear-gradient(180deg,transparent 18%,#000 28%,#000 84%,transparent 96%);mask-composite:intersect;pointer-events:none;user-select:none;-webkit-user-drag:none}
/* slider */
.ow-track{position:relative;flex:none;height:80px;border-radius:999px;background:var(--pill);box-shadow:0 18px 40px -16px rgba(0,0,0,.55),inset 0 1px 0 rgba(255,255,255,.08);overflow:hidden;touch-action:none;user-select:none;-webkit-user-select:none}
.ow-fill{position:absolute;left:0;top:0;bottom:0;width:0;border-radius:999px;background:linear-gradient(90deg,rgba(255,255,255,.04),rgba(255,255,255,.14))}
.ow-label{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:0 var(--pr,74px) 0 var(--pl,84px);font-size:15px;font-weight:700;letter-spacing:.07em;white-space:nowrap;overflow:hidden;text-transform:uppercase;color:#fff;pointer-events:none}
.ow-chev{position:absolute;right:18px;top:50%;transform:translateY(-50%);display:flex;gap:1px;pointer-events:none}
.ow-chev svg{width:10px;height:16px;color:#fff;opacity:.18;animation:ow-chev 1.6s ease-in-out infinite}
.ow-chev svg:nth-child(2){animation-delay:.15s}.ow-chev svg:nth-child(3){animation-delay:.3s}.ow-chev svg:nth-child(4){animation-delay:.45s}
@keyframes ow-chev{0%,100%{opacity:.18}50%{opacity:1}}
.ow-knob{position:absolute;left:7px;top:7px;width:66px;height:66px;border-radius:50%;border:0;background:#fff;color:#0B0B0D;display:flex;align-items:center;justify-content:center;cursor:grab;box-shadow:0 6px 18px rgba(0,0,0,.35);touch-action:none;will-change:transform}
.ow-knob:active{cursor:grabbing}
.ow-knob:focus-visible{outline:3px solid #fff;outline-offset:3px}
.ow-knob svg{width:34px;height:34px}
.ow-knob .ow-ok{display:none}
.ow-knob .ow-carico{transition:transform .35s cubic-bezier(.3,1.4,.5,1)}.is-done .ow-knob .ow-carico{transform:rotate(90deg)}
.ow-track.is-hint .ow-knob{animation:ow-nudge 1.1s ease-in-out}
@keyframes ow-nudge{0%,100%{transform:translateX(0)}35%{transform:translateX(26px)}60%{transform:translateX(4px)}}
.ow-hint{height:0;overflow:visible;text-align:center;font-size:12px;color:var(--muted);opacity:0;transform:translateY(8px);transition:opacity .3s}
.ow-hint.is-on{opacity:1}
body.is-leaving .ow{transform:translateX(-100%);opacity:.2}
@media (max-height:700px){.ow{padding-top:calc(env(safe-area-inset-top) + 20px)}.ow-kicker{margin-top:36px;font-size:16px}.ow-sub{margin-top:10px;font-size:15px}.ow-logo img{width:min(56vw,200px)}.ow-track{height:72px}.ow-knob{width:58px;height:58px}}
@media (min-width:700px){.ow-car{width:700px;left:50%;transform:translate(-50%,-50%)}}
/* Desktop: text and slider on the left, a big car on the right, all on one screen */
@media (min-width:960px){
.ow{padding:40px clamp(40px,5vw,88px);background:radial-gradient(ellipse 46% 60% at 72% 56%,#E9EAEE 0%,#F3F3F6 45%,rgba(255,255,255,0) 78%),linear-gradient(180deg,#F6F6F8 0%,#FFFFFF 60%)}
.ow-in{max-width:1280px;display:grid;grid-template-columns:minmax(400px,470px) minmax(0,1fr);grid-template-rows:auto auto auto;grid-template-areas:"top stage" "track stage" "hint stage";column-gap:clamp(24px,4vw,72px);align-content:center;align-items:center}
.ow-top{grid-area:top}
.ow-logo{justify-content:flex-start}
.ow-logo img{width:230px}
.ow-kicker{margin-top:56px;font-size:20px}
.ow-title{font-size:clamp(56px,5.4vw,84px);margin-top:4px}
.ow-sub{margin-top:18px;font-size:19px;max-width:23em}
.ow-track{grid-area:track;margin-top:44px;width:100%;max-width:440px;height:84px}
.ow-knob{width:70px;height:70px}
.ow-hint{grid-area:hint;text-align:left;padding-left:8px;height:auto;margin-top:12px}
.ow-stage{grid-area:stage;align-self:center;height:min(76vh,680px);min-height:0;margin:0}
.ow-stage>.ow-car,.ow-stage>.ow-floor{--w:min(104cqw,128cqh)}
.ow-car{left:50%;top:50%;width:var(--w);transform:translate(-50%,-52%);-webkit-mask-image:none;mask-image:none}
.ow-floor{left:calc(50% - var(--w) * .43);width:calc(var(--w) * .86);top:calc(50% + var(--w) * .215);height:calc(var(--w) * .09)}
}
@media (min-width:960px) and (max-height:700px){.ow-kicker{margin-top:32px}.ow-track{margin-top:30px;height:76px}.ow-knob{width:62px;height:62px}.ow-title{font-size:clamp(48px,4.6vw,70px)}}
@media (prefers-reduced-motion:reduce){.ow,.ow-chev svg,.ow-track.is-hint .ow-knob{animation:none!important;transition:none!important}}
</style>
<?php if ( function_exists( 'orego_intro_welcome_style' ) ) { echo orego_intro_welcome_style(); } // phpcs:ignore ?>
<?php if ( function_exists( 'orego_handoff_welcome_head' ) ) { orego_handoff_welcome_head(); } ?>
</head>
<body>
<main class="ow<?php echo function_exists( 'orego_intro_welcome_classes' ) ? esc_attr( orego_intro_welcome_classes() ) : ''; ?>" id="ow"><div class="ow-in">
	<div class="ow-top">
		<?php if ( $logo ) : ?><span class="ow-logo"><img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="900" height="239"><?php echo function_exists( 'orego_intro_light_logo' ) ? orego_intro_light_logo() : ''; // phpcs:ignore ?></span><?php endif; ?>
		<p class="ow-kicker">Welcome to</p>
		<h1 class="ow-title"><?php echo esc_html( orego_brand() ); ?></h1>
		<p class="ow-sub"><b>Discover</b>, <b>Reserve</b>, and <b>Drive</b> the ideal vehicle for every trip.</p>
	</div>
	<div class="ow-stage" aria-hidden="true">
		<span class="ow-floor"></span>
		<?php echo function_exists( 'orego_intro_welcome_cars' ) ? orego_intro_welcome_cars() : ''; // phpcs:ignore ?>
	</div>
	<div class="ow-track" id="ow-track">
		<span class="ow-fill" id="ow-fill"></span>
		<span class="ow-label" id="ow-label">Start your journey</span>
		<span class="ow-chev" id="ow-chev" aria-hidden="true"><?php for ( $i = 0; $i < 4; $i++ ) : ?><svg viewBox="0 0 12 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 3 6 6-6 6"/></svg><?php endfor; ?></span>
		<button type="button" class="ow-knob" id="ow-knob" aria-label="Slide right to start your journey">
			<svg class="ow-carico" viewBox="0 0 48 48" fill="currentColor" aria-hidden="true"><path d="M13 14.5a9.5 9.5 0 1 1 0 19 9.5 9.5 0 0 1 0-19Zm0 6a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/><path d="M21.5 21.4h21a1.6 1.6 0 0 1 1.6 1.6v2a1.6 1.6 0 0 1-1.6 1.6h-.6v4.6h-4.1v-4.6h-2.2v3.2h-4.1v-3.2h-10Z"/></svg>
			<svg class="ow-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
		</button>
	</div>
	<p class="ow-hint" id="ow-hint">Slide the car to the right</p>
</div></main>
<script>
(function(){
	var track=document.getElementById('ow-track'),knob=document.getElementById('ow-knob'),fill=document.getElementById('ow-fill'),label=document.getElementById('ow-label'),chev=document.getElementById('ow-chev'),hint=document.getElementById('ow-hint');
	var PAD=7,x=0,max=0,start=0,drag=false,done=false,moved=false;
	// Where to go after the swipe: always the Cars page.
	var next=<?php echo wp_json_encode( orego_purl( 'cars' ) ); ?>; // swipe opens the Cars page
	function measure(){max=track.clientWidth-knob.offsetWidth-PAD*2;}
	// Keep the label between the knob and the arrows: shrink it, and on very narrow phones use a shorter text.
	function textW(){var r=document.createRange();r.selectNodeContents(label);return r.getBoundingClientRect().width;}
	function fitLabel(){
		// The label is centred between the knob (+12px) and the arrows (+12px).
		var tb=track.getBoundingClientRect(),cb=chev.getBoundingClientRect(),pl=PAD+knob.offsetWidth+12,pr=tb.right-cb.left+12,room=tb.width-pl-pr;
		track.style.setProperty('--pl',pl+'px');track.style.setProperty('--pr',pr+'px');
		var texts=['Start your journey','Slide to start'];
		for(var i=0;i<texts.length;i++){label.textContent=texts[i];for(var fs=15;fs>=11;fs-=.5){label.style.fontSize=fs+'px';if(textW()<=room)return;}}
	}
	function paint(v){x=Math.max(0,Math.min(max,v));knob.style.transform='translateX('+x+'px)';fill.style.width=(x+knob.offsetWidth+PAD)+'px';var p=max?x/max:0;label.style.opacity=String(1-p*1.4);chev.style.opacity=String(1-p*2);}
	function finish(){
		if(done)return;done=true;try{if(navigator.vibrate)navigator.vibrate(15);}catch(e){}knob.style.transition='transform .25s ease';fill.style.transition='width .25s ease';paint(max);track.classList.add('is-done');knob.setAttribute('aria-label','Opening the site');
		try{sessionStorage.setItem('og_welcomed_s','1');<?php echo 'if(' . orego_intro_is_m_js() . '?' . ( orego_lang_on_m() ? 'true' : 'false' ) . ':' . ( orego_lang_on_d() ? 'true' : 'false' ) . ")sessionStorage.setItem('og_lang_after_welcome','1');"; ?>localStorage.setItem('og_last',String(Date.now()));}catch(e){} // the next page opens the language chooser
		// Browsers that cross-fade between pages: go as soon as the knob lands. Others: slide away first.
		if('PageRevealEvent' in window||'onpagereveal' in window){setTimeout(function(){location.href=next;},260);}
		else{setTimeout(function(){document.body.classList.add('is-leaving');},220);setTimeout(function(){location.href=next;},620);}
	}
	function back(){knob.style.transition='transform .35s cubic-bezier(.3,1.4,.5,1)';fill.style.transition='width .35s ease';label.style.transition='opacity .3s';paint(0);}
	knob.addEventListener('pointerdown',function(e){if(done)return;measure();drag=true;moved=false;start=e.clientX-x;knob.style.transition='none';fill.style.transition='none';track.classList.remove('is-hint');try{knob.setPointerCapture(e.pointerId);}catch(_){}});
	knob.addEventListener('pointermove',function(e){if(!drag)return;var v=e.clientX-start;if(Math.abs(v)>3)moved=true;paint(v);});
	function up(){if(!drag)return;drag=false;if(x>=max*0.82){finish();}else{back();if(!moved){hint.classList.add('is-on');track.classList.remove('is-hint');void track.offsetWidth;track.classList.add('is-hint');}}}
	knob.addEventListener('pointerup',up);knob.addEventListener('pointercancel',up);
	// Keyboard: Enter, Space or the right arrow open the site.
	knob.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '||e.key==='ArrowRight'){e.preventDefault();measure();finish();}});
	knob.addEventListener('click',function(e){if(e.detail===0){measure();finish();}}); // screen readers "click" without a pointer
	window.addEventListener('resize',function(){if(!done){measure();paint(0);fitLabel();}});
	measure();fitLabel();if(document.fonts&&document.fonts.ready)document.fonts.ready.then(fitLabel);
	// A gentle nudge so people see the knob moves.
	if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches){setTimeout(function(){if(!drag&&!done)track.classList.add('is-hint');},1200);}
	// Back button after leaving: show the screen again, ready.
	window.addEventListener('pageshow',function(ev){if(ev.persisted){done=false;document.body.classList.remove('is-leaving');track.classList.remove('is-done');back();}});
})();
</script>
<?php if ( function_exists( 'orego_intro_welcome_preview_scripts' ) ) { orego_intro_welcome_preview_scripts(); } ?>
</body>
</html>
	<?php
}

/* Phones: show the welcome screen every time someone arrives from outside the site (typed address, bookmark, Google, WhatsApp...). Moving between pages or pressing Back skips it. */
add_action( 'wp_head', function () {
	if ( ( function_exists( 'orego_welcome_on' ) && ! orego_welcome_on() ) || orego_welcome_is_page() || is_admin() || is_customize_preview() || isset( $_GET['elementor-preview'] ) || ( function_exists( 'orego_is_editor' ) && orego_is_editor() ) ) { // phpcs:ignore
		return;
	}
	$w = wp_make_link_relative( orego_welcome_url() );
	echo '<script id="og-welcome-gate">(function(){try{if(document.referrer&&new URL(document.referrer).origin===location.origin)return;var nv=performance.getEntriesByType&&performance.getEntriesByType("navigation")[0];if(nv&&nv.type==="back_forward")return;}catch(e){}'
		. 'if(window.self!==window.top)return;'
		. 'if(!(' . orego_intro_is_m_js() . '?' . ( orego_welcome_on_m() ? 'true' : 'false' ) . ':' . ( orego_welcome_on_d() ? 'true' : 'false' ) . '))return;'
		. 'if(/bot|crawl|spider|slurp|lighthouse|pagespeed|preview/i.test(navigator.userAgent))return;'
		. 'location.replace(' . wp_json_encode( $w ) . '+"?next="+encodeURIComponent(location.pathname+location.search+location.hash));})();</script>' . "\n";
}, -100 );
