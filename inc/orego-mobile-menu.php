<?php
/**
 * Phone menu (the sheet opened by "Menu" in the bottom tab bar), phones only (< 768px).
 * - Light header with the dark logo and a round close button.
 * - Page links as a list card with an icon per page.
 * - Language and currency as two "Preferences" rows that open a list with a check mark.
 * The original language / currency buttons are kept and moved, so their click code still works.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	$names = array();
	if ( function_exists( 'orego_currency_list' ) ) {
		foreach ( (array) orego_currency_list() as $c ) {
			if ( ! empty( $c['code'] ) ) {
				$names[ $c['code'] ] = array( (string) ( $c['name'] ?? $c['code'] ), (string) ( $c['symbol'] ?? '' ) );
			}
		}
	}
	$logo = wp_get_attachment_image_url( orego_aid( 'logo' ), 'medium' );
	?>
<style id="og-mmenu">
@media (max-width:767px){
html body .ogx2-drawer{background:#F4F5F7;display:flex;flex-direction:column;overflow-y:auto;overscroll-behavior:contain}
html body .ogx2-drawer>*{flex-shrink:0}
html body .ogx2-drawer .ogx2-nav{overflow:visible;max-height:none}
html body .ogx2-drawer .ogx2-dh{position:sticky;top:0;z-index:3;display:flex;align-items:center;justify-content:space-between;height:auto;min-height:0;padding:calc(14px + env(safe-area-inset-top)) 18px 14px;background:rgba(244,245,247,.92);-webkit-backdrop-filter:saturate(1.6) blur(14px);backdrop-filter:saturate(1.6) blur(14px);border:0}
html body .ogx2-drawer .ogx2-dh .ogx2-logo img{height:40px;width:auto;max-width:190px;object-fit:contain}
html body .ogx2-drawer .ogx2-close{width:42px;height:42px;border-radius:50%;background:#fff;color:#15171C;border:0;box-shadow:0 1px 2px rgba(16,24,40,.06),0 0 0 1px #E6E8EC;display:grid;place-items:center}
html body .ogx2-drawer .ogx2-close svg{width:18px;height:18px}
.ogm-h{margin:18px 22px 8px;font:700 12px/1.2 Cairo,Tahoma,sans-serif;letter-spacing:.08em;text-transform:uppercase;color:#8A919C}
html body .ogx2-drawer .ogx2-nav{padding:0 16px;margin:0}
html body .ogx2-drawer .ogx2-nav ul{margin:0;padding:6px;list-style:none;background:#fff;border-radius:20px;box-shadow:0 1px 2px rgba(16,24,40,.04),0 0 0 1px #ECEEF1}
html body .ogx2-drawer .ogx2-nav li{margin:0;border:0;background:none}
html body .ogx2-drawer .ogx2-nav li+li{border-top:1px solid #F1F2F4}
html body .ogx2-drawer .ogx2-nav li::before{display:none}
html body .ogx2-drawer .ogx2-nav .ogx2-row{position:relative;display:flex;align-items:center;gap:14px;min-height:60px;padding:0 12px;border-radius:14px;background:none;box-shadow:none;border:0}
html body .ogx2-drawer .ogx2-nav .ogx2-row::before{display:none}
html body .ogx2-drawer .ogx2-nav .ogx2-row a{flex:1;min-width:0;padding:0;font:700 17px/1.2 Cairo,Tahoma,sans-serif;color:#15171C;background:none;border:0}
html body .ogx2-drawer .ogx2-nav .ogx2-row a::after{content:"";position:absolute;inset:0}
html body .ogx2-drawer .ogx2-nav .ogx2-arrow{color:#B4B9C2;width:20px;height:20px;display:grid;place-items:center}
html body .ogx2-drawer .ogx2-nav .ogx2-arrow svg{width:18px;height:18px}
.ogm-ic{flex:0 0 40px;width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:#F2F3F5;color:#15171C}
.ogm-ic svg{width:20px;height:20px}
html body .ogx2-drawer .ogx2-nav li.is-current .ogm-ic{background:#15171C;color:#fff}
html body .ogx2-drawer .ogx2-nav li.is-current .ogx2-row{background:#F6F7F8}
html body .ogx2-drawer .ogx2-nav li.is-current .ogx2-row a{color:#15171C!important}
html body .ogx2-drawer .ogx2-nav li.is-current .ogx2-arrow{color:#15171C}
.ogm-prefs{margin:0 16px;padding:6px;background:#fff;border-radius:20px;box-shadow:0 1px 2px rgba(16,24,40,.04),0 0 0 1px #ECEEF1}
.ogm-pref+.ogm-pref{border-top:1px solid #F1F2F4}
.ogm-row{all:unset;box-sizing:border-box;display:flex;align-items:center;gap:14px;width:100%;min-height:60px;padding:0 12px;border-radius:14px;cursor:pointer;font-family:Cairo,Tahoma,sans-serif;-webkit-tap-highlight-color:transparent}
.ogm-row:focus-visible{outline:2px solid #15171C;outline-offset:-2px}
.ogm-row>b{flex:1;min-width:0;font-size:16px;font-weight:700;color:#15171C}
.ogm-val{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:#6B7280;white-space:nowrap}
.ogm-val .ogx2-flag{width:22px;height:22px;border-radius:50%;overflow:hidden;box-shadow:0 0 0 1px #E3E6EA;display:block}
.ogm-val .ogx2-flag svg,.ogm-val .ogx2-flag img{width:100%;height:100%;display:block}
.ogm-chev{width:18px;height:18px;color:#B4B9C2;transition:transform .25s ease}
.ogm-pref.is-open .ogm-chev{transform:rotate(90deg);color:#15171C}
html[dir=rtl] .ogm-chev{transform:scaleX(-1)}
html[dir=rtl] .ogm-pref.is-open .ogm-chev{transform:rotate(90deg)}
.ogm-pick{display:grid;grid-template-rows:0fr;transition:grid-template-rows .28s ease}
.ogm-pref.is-open .ogm-pick{grid-template-rows:1fr}
.ogm-pick>div{overflow:hidden;min-height:0}
html body .ogx2-drawer .ogm-pick .ogx2-dlangs{padding:0;margin:0;border:0}
html body .ogx2-drawer .ogm-pick .ogx2-dlangs>p{display:none}
html body .ogx2-drawer .ogm-pick .ogx2-dlangs-grid{display:flex;flex-direction:column;gap:2px;max-width:none;margin:0;padding:2px 6px 8px}
html body .ogx2-drawer .ogm-pick .ogx2-dlang{flex-direction:row;align-items:center;gap:12px;width:100%;min-height:48px;padding:0 12px;border:0;border-radius:12px;background:transparent;color:#15171C;text-align:start}
html body .ogx2-drawer .ogm-pick .ogx2-dlang:hover{background:#F6F7F8}
html body .ogx2-drawer .ogm-pick .ogx2-dlang .ogx2-flag{flex:0 0 26px;width:26px;height:26px}
html body .ogx2-drawer .ogm-pick .ogx2-dlang b{display:block;flex:1;min-width:0;font-size:15px;font-weight:600;color:#15171C}
html body .ogx2-drawer .ogm-pick .ogx2-dlang small{font-size:12px;font-weight:700;color:#9AA1AD;letter-spacing:.04em}
html body .ogx2-drawer .ogm-pick .ogx2-dlang::after{content:"";flex:0 0 22px;width:22px;height:22px;border-radius:50%;box-shadow:inset 0 0 0 1.5px #D5D9DF}
html body .ogx2-drawer .ogm-pick .ogx2-dlang.is-on{background:#F6F7F8;color:#15171C}
html body .ogx2-drawer .ogm-pick .ogx2-dlang.is-on b{font-weight:800}
html body .ogx2-drawer .ogm-pick .ogx2-dlang.is-on::after{box-shadow:none;background:#15171C url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E") center/13px no-repeat}
html body .ogx2-drawer .ogx2-dfoot{position:sticky;bottom:0;margin-top:auto;padding:14px 16px calc(14px + env(safe-area-inset-bottom));background:linear-gradient(to top,#F4F5F7 70%,rgba(244,245,247,0));border:0;z-index:2}
html body .ogx2-drawer .ogx2-dfoot .ogx2-cta{display:flex;align-items:center;justify-content:center;gap:10px;height:56px;border-radius:16px;background:#15171C;color:#fff;font:800 17px/1 Cairo,Tahoma,sans-serif;box-shadow:0 10px 24px -10px rgba(21,23,28,.55)}
html body .ogx2-drawer .ogx2-dfoot .ogx2-cta svg{width:20px;height:20px}
html[dir=rtl] body .ogx2-drawer .ogx2-dfoot .ogx2-cta svg{transform:scaleX(-1)}
.ogm-space{height:12px;flex:0 0 auto}
.ogm-legal{margin-top:12px}
.ogm-links{display:flex;flex-direction:column;gap:2px;padding:2px 6px 8px}
.ogm-link{display:flex;align-items:center;justify-content:space-between;min-height:48px;padding:0 12px 0 66px;border-radius:12px;font:600 15px/1.2 Cairo,Tahoma,sans-serif;color:#15171C!important;text-decoration:none!important}
.ogm-link::after{content:"";width:7px;height:7px;border-top:2px solid #B4B9C2;border-right:2px solid #B4B9C2;transform:rotate(45deg);margin-inline-end:6px}
html[dir=rtl] .ogm-link::after{transform:rotate(-135deg)}
.ogm-link:hover,.ogm-link.is-on{background:#F6F7F8}
.ogm-link.is-on{font-weight:800}
html[dir=rtl] .ogm-link{padding:0 66px 0 12px}
html body footer.ogfb{display:none!important}
}
</style>
<script id="og-mmenu-js">
(function(){
var LEGAL=<?php echo wp_json_encode( array_values( array_filter( array( array( 'My booking', ( get_page_by_path( 'my-booking' ) ? get_permalink( get_page_by_path( 'my-booking' ) ) : '' ) ), array( 'FAQ', (string) orego_purl( 'faq' ) ), array( 'Terms & conditions', ( get_page_by_path( 'terms' ) ? get_permalink( get_page_by_path( 'terms' ) ) : '' ) ), array( 'Privacy policy', (string) orego_purl( 'privacy' ) ) ), fn( $l ) => '' !== $l[1] ) ), JSON_UNESCAPED_UNICODE ); ?>;
var NAMES=<?php echo wp_json_encode( $names, JSON_UNESCAPED_UNICODE ); ?>,LOGO=<?php echo wp_json_encode( $logo ? $logo : '' ); ?>;
var TR={en:['Explore','Preferences','Language','Currency'],ar:['تصفّح','الإعدادات','اللغة','العملة'],fr:['Explorer','Préférences','Langue','Devise'],es:['Explorar','Preferencias','Idioma','Moneda'],de:['Entdecken','Einstellungen','Sprache','Währung'],it:['Esplora','Preferenze','Lingua','Valuta'],pt:['Explorar','Preferências','Idioma','Moeda']};
function curLang(){var b=document.querySelector('.ogx2-drawer .ogx2-dlang.is-on[data-setlang]');return (b&&b.getAttribute('data-setlang'))||'en';}
function labels(){var t=TR[curLang()]||TR.en;document.querySelectorAll('[data-ogm-t]').forEach(function(e){e.textContent=t[+e.getAttribute('data-ogm-t')];});}
var S=function(p){return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'+p+'</svg>';};
var IC={
 car:S('<path d="M5 17h14M3 13l2.2-5.2A2 2 0 0 1 7 6.5h10a2 2 0 0 1 1.8 1.3L21 13v4a1 1 0 0 1-1 1h-1.5M3 13v4a1 1 0 0 0 1 1h1.5M3 13h18"/><circle cx="7.5" cy="17.5" r="1.8"/><circle cx="16.5" cy="17.5" r="1.8"/>'),
 info:S('<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.6v.1"/>'),
 chat:S('<path d="M21 12a8.5 8.5 0 0 1-12.3 7.6L3 21l1.4-5.4A8.5 8.5 0 1 1 21 12z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/>'),
 grid:S('<rect x="3.5" y="3.5" width="7" height="7" rx="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2"/>'),
 home:S('<path d="M3.5 10.5 12 4l8.5 6.5V19a1.5 1.5 0 0 1-1.5 1.5h-4v-6h-6v6H5A1.5 1.5 0 0 1 3.5 19z"/>'),
 page:S('<path d="M7 3.5h7l4.5 4.5V19a1.5 1.5 0 0 1-1.5 1.5H7A1.5 1.5 0 0 1 5.5 19V5A1.5 1.5 0 0 1 7 3.5z"/><path d="M14 3.5V8h4.5M9 13h6M9 16.5h4"/>'),
 globe:S('<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/>'),
 coins:S('<ellipse cx="9" cy="7" rx="5.5" ry="2.5"/><path d="M3.5 7v4c0 1.4 2.5 2.5 5.5 2.5s5.5-1.1 5.5-2.5V7"/><path d="M3.5 11v4c0 1.4 2.5 2.5 5.5 2.5 1 0 1.9-.1 2.7-.3"/><ellipse cx="15.5" cy="15" rx="5" ry="2.3"/><path d="M10.5 15v2.7c0 1.3 2.2 2.3 5 2.3s5-1 5-2.3V15"/>'),
 chev:'<svg class="ogm-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>',
 arrow:S('<path d="M5 12h14M13 6l6 6-6 6"/>')
};
function icFor(href){href=(href||'').toLowerCase();
 if(/vehicle|\/cars?\b|car-/.test(href))return IC.car;if(/about/.test(href))return IC.info;if(/contact/.test(href))return IC.chat;
 if(/\/map\b/.test(href))return S('<path d="m9 4-6 2v14l6-2 6 2 6-2V4l-6 2-6-2Z"/><path d="M9 4v14M15 6v14"/>');if(/location/.test(href))return S('<path d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>');if(/deal|offer/.test(href))return S('<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Z"/><circle cx="7.5" cy="7.5" r="1.5"/>');if(/faq/.test(href))return S('<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6v.6"/><path d="M12 17h.01"/>');if(/collection|brand/.test(href))return IC.grid;try{if(new URL(href,location.href).pathname==='/')return IC.home;}catch(e){}return IC.page;}
function build(){
 var d=document.getElementById('ogx2-drawer');if(!d||d.dataset.ogm)return;
 if(!window.matchMedia('(max-width:767px)').matches)return;
 d.dataset.ogm='1';
 if(LOGO){var im=d.querySelector('.ogx2-dh .ogx2-logo img');if(im){im.src=LOGO;im.removeAttribute('srcset');im.removeAttribute('sizes');}}
 var nav=d.querySelector('.ogx2-nav');
 if(nav){nav.querySelectorAll('.ogx2-row').forEach(function(r){if(r.querySelector('.ogm-ic'))return;var a=r.querySelector('a');var s=document.createElement('span');s.className='ogm-ic';s.innerHTML=icFor(a&&a.getAttribute('href'));r.insertBefore(s,r.firstChild);});
  var h=document.createElement('p');h.className='ogm-h notranslate';h.setAttribute('translate','no');h.setAttribute('data-ogm-t','0');nav.parentNode.insertBefore(h,nav);}
 var lang=d.querySelector('.ogx2-dlangs:not(.ogx2-dcurs)'),cur=d.querySelector('.ogx2-dcurs');
 if(!lang&&!cur)return;
 d.querySelectorAll('.ogx2-dcur').forEach(function(b){var c=b.getAttribute('data-dcur'),n=NAMES[c];if(n&&!b.querySelector('b')){var x=document.createElement('b');x.textContent=n[0];b.insertBefore(x,b.querySelector('small'));}});
 var ph=document.createElement('p');ph.className='ogm-h notranslate';ph.setAttribute('translate','no');ph.setAttribute('data-ogm-t','1');
 var box=document.createElement('div');box.className='ogm-prefs';
 function pref(key,ti,icon,src){var w=document.createElement('div');w.className='ogm-pref';w.dataset.k=key;
  var id='ogm-pick-'+key;
  w.innerHTML='<button type="button" class="ogm-row" aria-expanded="false" aria-controls="'+id+'"><span class="ogm-ic">'+icon+'</span><b class="notranslate" translate="no" data-ogm-t="'+ti+'"></b><span class="ogm-val notranslate" translate="no"></span>'+IC.chev+'</button><div class="ogm-pick" id="'+id+'"><div></div></div>';
  w.querySelector('.ogm-pick>div').appendChild(src);box.appendChild(w);
  w.querySelector('.ogm-row').addEventListener('click',function(){var open=!w.classList.contains('is-open');box.querySelectorAll('.ogm-pref').forEach(function(o){o.classList.remove('is-open');o.querySelector('.ogm-row').setAttribute('aria-expanded','false');});if(open){w.classList.add('is-open');this.setAttribute('aria-expanded','true');}});}
 var anchor=lang||cur;anchor.parentNode.insertBefore(ph,anchor);
 if(LEGAL.length){var lg=document.createElement('div');lg.className='ogm-prefs ogm-legal';var lw=document.createElement('div');lw.className='ogm-pref';
  var here=location.pathname.replace(/\/+$/,'');
  lw.innerHTML='<button type="button" class="ogm-row" aria-expanded="false" aria-controls="ogm-pick-legal"><span class="ogm-ic">'+S('<path d="M12 3 5 6v5c0 4.5 3 8.5 7 10 4-1.5 7-5.5 7-10V6z"/><path d="M9.5 12.5h5M9.5 9.5h5M9.5 15.5h3"/>')+'</span><b>Help &amp; legal</b>'+IC.chev+'</button><div class="ogm-pick" id="ogm-pick-legal"><div class="ogm-links"></div></div>';
  var ls=lw.querySelector('.ogm-links');LEGAL.forEach(function(l){var a=document.createElement('a');a.className='ogm-link';a.href=l[1];a.textContent=l[0];try{if(new URL(l[1]).pathname.replace(/\/+$/,'')===here){a.classList.add('is-on');lw.classList.add('is-open')}}catch(e){}ls.appendChild(a)});
  lw.querySelector('.ogm-row').setAttribute('aria-expanded',lw.classList.contains('is-open')?'true':'false');
  lw.querySelector('.ogm-row').addEventListener('click',function(){var o=!lw.classList.contains('is-open');lw.classList.toggle('is-open',o);this.setAttribute('aria-expanded',o?'true':'false')});
  lg.appendChild(lw);ph.parentNode.insertBefore(lg,ph);var lsp=document.createElement('div');lsp.className='ogm-space';ph.parentNode.insertBefore(lsp,ph);}
ph.parentNode.insertBefore(box,ph.nextSibling);
 if(lang)pref('lang',2,IC.globe,lang);
 if(cur)pref('cur',3,IC.coins,cur);
 var sp=document.createElement('div');sp.className='ogm-space';box.parentNode.insertBefore(sp,box.nextSibling);
 var cta=d.querySelector('.ogx2-dfoot .ogx2-cta');
 /* phones: the big button opens My booking instead of Book now */
 var mbk=null;LEGAL.forEach(function(l){if(l[0]==='My booking')mbk=l[1]});if(cta&&mbk){cta.setAttribute('href',mbk);cta.textContent='My booking';cta.removeAttribute('target');}
 if(cta&&!cta.querySelector('svg'))cta.insertAdjacentHTML('beforeend',IC.arrow);
 function vals(){
  labels();
  var l=box.querySelector('[data-k=lang] .ogx2-dlang.is-on'),c=box.querySelector('[data-k=cur] .ogx2-dlang.is-on');
  var lv=box.querySelector('[data-k=lang] .ogm-val'),cv=box.querySelector('[data-k=cur] .ogm-val');
  if(lv&&l){var f=l.querySelector('.ogx2-flag'),b=l.querySelector('b');lv.innerHTML=(f?f.outerHTML:'')+'<span></span>';lv.lastChild.textContent=b?b.textContent:(l.querySelector('small')||{}).textContent||'';}
  if(cv&&c){var code=c.getAttribute('data-dcur'),n=NAMES[code],f2=c.querySelector('.ogx2-flag');cv.innerHTML=(f2?f2.outerHTML:'')+'<span></span>';cv.lastChild.textContent=code+(n&&n[1]&&n[1]!==code?' · '+n[1]:'');}
 }
 vals();
 new MutationObserver(vals).observe(box,{subtree:true,attributes:true,attributeFilter:['class']});
 box.addEventListener('click',function(e){var b=e.target.closest('.ogx2-dlang');if(!b)return;setTimeout(function(){vals();var w=b.closest('.ogm-pref');if(w){w.classList.remove('is-open');w.querySelector('.ogm-row').setAttribute('aria-expanded','false');}},120);});
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',build);else build();
var mq=window.matchMedia('(max-width:767px)');if(mq.addEventListener)mq.addEventListener('change',build);
})();
</script>
	<?php
}, 120 );
