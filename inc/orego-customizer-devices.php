<?php
/**
 * Customizer: realistic phone and tablet previews.
 * WordPress previews phones at 320x480 and tablets at 720px wide, which falls under this site's
 * 768px tablet breakpoint, so "tablet" showed the phone layout. Here the phone is 390x844 and the
 * tablet 768x1024, in a device frame, centred and scaled down to fit the screen.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'customize_controls_enqueue_scripts', function () {
	wp_register_style( 'orego-cz-devices', false, array(), '1.0' );
	wp_enqueue_style( 'orego-cz-devices' );
	wp_add_inline_style( 'orego-cz-devices', <<<'CSS'
.wp-full-overlay.preview-mobile,.wp-full-overlay.preview-tablet{background:#E7E9EE}
.wp-full-overlay.preview-mobile .wp-full-overlay-main,.wp-full-overlay.preview-tablet .wp-full-overlay-main{
	position:absolute;left:50%;top:var(--og-cy,50%);right:auto;bottom:auto;margin:0;
	width:var(--og-dw);height:var(--og-dh);max-width:none;max-height:none;box-sizing:content-box;
	border:var(--og-bz) solid #15171C;border-radius:var(--og-r);overflow:hidden;background:#fff;
	transform:translate(-50%,-50%) scale(var(--og-ds,1));transform-origin:50% 50%;
	box-shadow:0 0 0 1px #2B2F38,0 32px 70px -24px rgba(21,23,28,.45);
	transition:transform .2s ease,top .2s ease;
}
.wp-full-overlay.preview-mobile{--og-dw:390px;--og-dh:844px;--og-bz:12px;--og-r:48px}
.wp-full-overlay.preview-tablet{--og-dw:768px;--og-dh:1024px;--og-bz:16px;--og-r:32px}
.wp-full-overlay.preview-mobile .wp-full-overlay-main iframe,.wp-full-overlay.preview-tablet .wp-full-overlay-main iframe{border-radius:calc(var(--og-r) - var(--og-bz))}
#og-dev-cap{position:absolute;left:50%;transform:translateX(-50%);bottom:var(--og-capb,14px);z-index:5;font:600 12px/1 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#50575E;background:#fff;border:1px solid #DCDCDE;border-radius:999px;padding:6px 12px;white-space:nowrap;pointer-events:none}
#og-dev-cap[hidden]{display:none}
@media (prefers-reduced-motion:reduce){.wp-full-overlay.preview-mobile .wp-full-overlay-main,.wp-full-overlay.preview-tablet .wp-full-overlay-main{transition:none}}
CSS
	);

	wp_add_inline_script( 'customize-controls', <<<'JS'
(function(api){
	var SIZES={mobile:{w:390,h:844,b:12,name:'Phone'},tablet:{w:768,h:1024,b:16,name:'Tablet'}};
	function fit(){
		var ov=document.querySelector('.wp-full-overlay'),root=document.documentElement;if(!ov)return;
		var dev=api.previewedDevice?api.previewedDevice.get():'desktop',d=SIZES[dev],cap=document.getElementById('og-dev-cap');
		if(!d){if(cap)cap.hidden=true;return;}
		// Free area: the window, minus the open sidebar, the footer bar and the header builder bar.
		var side=ov.classList.contains('expanded')?(document.getElementById('customize-controls')||{}).offsetWidth||0:0;
		var footH=(document.getElementById('customize-footer-actions')||{}).offsetHeight||0;
		var bar=document.getElementById('oghb-bar'),barH=0;
		if(bar&&document.body.classList.contains('oghb-on'))barH=bar.getBoundingClientRect().height||0;
		var bottomUsed=Math.max(barH,0),pad=28,capH=34;
		var availW=window.innerWidth-side-pad*2,availH=window.innerHeight-bottomUsed-pad*2-capH;
		var s=Math.min(1,availW/(d.w+d.b*2),availH/(d.h+d.b*2));s=Math.max(.2,Math.floor(s*100)/100);
		root.style.setProperty('--og-ds',s);
		root.style.setProperty('--og-cy',(pad+(availH)/2)+'px');
		if(!cap){cap=document.createElement('div');cap.id='og-dev-cap';ov.appendChild(cap);}
		cap.hidden=false;cap.textContent=d.name+' '+d.w+' × '+d.h+(s<1?' · shown at '+Math.round(s*100)+'%':'');
		root.style.setProperty('--og-capb',(bottomUsed+10)+'px');
	}
	var t;function soon(){clearTimeout(t);t=setTimeout(fit,30);}
	api.bind('ready',function(){
		if(api.previewedDevice)api.previewedDevice.bind(soon);
		window.addEventListener('resize',soon);
		var ov=document.querySelector('.wp-full-overlay');
		var mo=new MutationObserver(soon);
		if(ov)mo.observe(ov,{attributes:true,attributeFilter:['class']});
		mo.observe(document.body,{attributes:true,attributeFilter:['class','style']});
		if(window.ResizeObserver){var ro=new ResizeObserver(soon);var b=document.getElementById('oghb-bar');if(b)ro.observe(b);setTimeout(function(){var b2=document.getElementById('oghb-bar');if(b2)ro.observe(b2);},1500);}
		fit();
	});
})(wp.customize);
JS
	);
} );
