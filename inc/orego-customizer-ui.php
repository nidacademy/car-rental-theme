<?php
/**
 * Exotic Cars: one look for the whole Customizer.
 * - Every menu entry (sections and panels) is a white card with an arrow, on a light grey background.
 * - Settings sit in white cards.
 * - Exotic Cars sections that mix content and styling get General / Design tabs.
 * - Footer becomes a single section (like Header).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'customize_register', function ( $wp ) {
	/* Footer: one section with tabs instead of a panel with two sections */
	$f = $wp->get_section( 'orego_footer' );
	if ( $f ) {
		foreach ( $wp->controls() as $c ) {
			if ( 'orego_f_layout' === $c->section ) {
				$c->section   = 'orego_footer';
				$c->priority += 100;
			}
		}
		$f->panel    = '';
		$f->title    = orego_L( 'Footer', 'الفوتر' );
		$f->priority = 26;
		$wp->remove_section( 'orego_f_layout' );
		$wp->remove_panel( 'orego_chrome' );
	}
}, 50 );

add_action( 'admin_menu', function () {
	global $submenu;
	if ( empty( $submenu['themes.php'] ) ) {
		return;
	}
	foreach ( $submenu['themes.php'] as $i => $row ) {
		if ( isset( $row[2] ) && false !== strpos( $row[2], 'autofocus[panel]=orego_chrome' ) ) {
			$submenu['themes.php'][ $i ][2] = 'customize.php?autofocus[section]=orego_footer'; // phpcs:ignore
		}
	}
}, 1010 );

add_action( 'customize_controls_enqueue_scripts', function () {
	wp_register_style( 'orego-cz-ui', false, array(), '1.0' );
	wp_enqueue_style( 'orego-cz-ui' );
	wp_add_inline_style( 'orego-cz-ui', orego_cz_css() );
	wp_add_inline_script( 'customize-controls', 'window.OGCZ=' . wp_json_encode( array(
		'general' => orego_L( 'General', 'عام' ),
		'design'  => orego_L( 'Design', 'التصميم' ),
	) ) . ';' . orego_cz_js() );
}, 20 );

function orego_cz_css() {
	return <<<'CSS'
/* grey canvas */
#customize-theme-controls .customize-pane-parent,#customize-theme-controls .customize-pane-child,#customize-controls .wp-full-overlay-sidebar-content{background:#f0f0f1}
#customize-theme-controls .customize-pane-parent{padding:14px 0 24px}
/* menu entries as cards */
#customize-theme-controls .customize-pane-parent>.accordion-section:not(#accordion-section-themes),#customize-theme-controls .control-panel-content>.accordion-section{border:0!important;background:none;margin:0 14px 10px}
#customize-theme-controls .customize-pane-parent>.accordion-section:not(#accordion-section-themes)>.accordion-section-title,#customize-theme-controls .control-panel-content>.accordion-section>.accordion-section-title{display:flex;align-items:center;min-height:50px;padding:0 44px 0 18px;border:1px solid #e2e4e7!important;border-radius:6px;background:#fff;font-size:14px;font-weight:600;color:#1d2327;box-shadow:none;transition:border-color .15s,color .15s}
#customize-theme-controls .customize-pane-parent>.accordion-section:not(#accordion-section-themes)>.accordion-section-title:hover,#customize-theme-controls .customize-pane-parent>.accordion-section:not(#accordion-section-themes)>.accordion-section-title:focus,#customize-theme-controls .control-panel-content>.accordion-section>.accordion-section-title:hover,#customize-theme-controls .control-panel-content>.accordion-section>.accordion-section-title:focus{border-color:#2271b1!important;color:#2271b1;background:#fff}
#customize-theme-controls .accordion-section-title:after{color:#8c8f94;right:16px}
#customize-theme-controls .accordion-section-title:hover:after,#customize-theme-controls .accordion-section-title:focus:after{color:#2271b1}
#customize-theme-controls .control-panel-content>.panel-meta{margin:0 0 14px}
#customize-theme-controls .control-panel-content{padding-bottom:20px}
/* active theme box */
#customize-theme-controls #accordion-section-themes{margin:0 14px 16px;border:0}
#customize-theme-controls #accordion-section-themes>.accordion-section-title{border:1px solid #e2e4e7;border-radius:6px}
/* section headers */
#customize-controls .customize-section-title,#customize-controls .panel-meta.customize-info{border-bottom:1px solid #dcdcde}
/* settings as cards */
#customize-theme-controls .control-section-default .accordion-section-content{padding:14px}
#customize-theme-controls .control-section-default .customize-control:not(.customize-control-orego_hb):not(.customize-control-hidden){box-sizing:border-box;width:100%;margin:0 0 10px;padding:14px 16px;background:#fff;border:1px solid #e2e4e7;border-radius:6px}
#customize-theme-controls .control-section-default .customize-control-hidden{display:none!important}
#customize-theme-controls .customize-control .customize-control-title{font-size:13px;font-weight:600;color:#1d2327;margin-bottom:6px}
#customize-theme-controls .customize-control .description{font-size:12px;color:#646970}
#customize-theme-controls .customize-control-checkbox label,#customize-theme-controls .customize-control-radio label{font-size:13px}
#customize-theme-controls .customize-control input[type=text],#customize-theme-controls .customize-control input[type=url],#customize-theme-controls .customize-control input[type=number],#customize-theme-controls .customize-control select,#customize-theme-controls .customize-control textarea{border-radius:4px}
/* General / Design tabs */
.ogcz-tabs{display:flex;margin:-14px -14px 14px;border-bottom:1px solid #dcdcde;background:#fff;position:sticky;top:0;z-index:5}
.ogcz-tabs button{flex:1;padding:14px 8px;border:0;border-bottom:3px solid transparent;background:none;font-weight:600;font-size:13px;letter-spacing:.04em;text-transform:uppercase;color:#50575e;cursor:pointer}
.ogcz-tabs button:hover{color:#1d2327}
.ogcz-tabs button.is-on{color:#1d2327;border-bottom-color:#2271b1;background:#f6f7f7}
#customize-theme-controls .customize-control.ogcz-off{display:none!important}
CSS;
}

function orego_cz_js() {
	return <<<'JS'
(function($,api){
var T=window.OGCZ;
var STYLE_SEL=/(style|align|weight|layout|width|transform|fit|ratio|hover|shadow|side|pos|cols|case|font)/;
var STYLE_CHK=/(shadow|border|rounded)/;
function isDesign(c){
 var t=c.params.type,id=c.id;
 if(id.indexOf('oghb_f_')===0)return null;
 if(t==='color'||t==='number')return true;
 if((t==='select'||t==='radio')&&STYLE_SEL.test(id))return true;
 if(t==='checkbox'&&STYLE_CHK.test(id))return true;
 return false;
}
function setup(s){
 var id=s.id;if(!/^(orego_|ogh_|ogf_)/.test(id)||id==='ogh_builder'||s.params.type!=='default')return;
 if(s.contentContainer.find('.ogcz-tabs').length)return;
 var g=[],d=[];
 s.controls().forEach(function(c){var r=isDesign(c);if(r===null)return;(r?d:g).push(c)});
 if(!g.length||!d.length)return;
 var $t=$('<div class="ogcz-tabs"><button type="button" class="is-on" data-t="g">'+T.general+'</button><button type="button" data-t="d">'+T.design+'</button></div>');
 var $head=s.contentContainer.children('.customize-section-description-container').first();
 if($head.length)$head.after($t);else s.contentContainer.prepend($t);
 function show(w){$t.find('button').each(function(){$(this).toggleClass('is-on',$(this).data('t')===w)});
  g.forEach(function(c){c.container.toggleClass('ogcz-off',w!=='g')});d.forEach(function(c){c.container.toggleClass('ogcz-off',w!=='d')})}
 $t.on('click','button',function(){show($(this).data('t'))});
 show('g');
 /* when a pencil/shortcut focuses a control, open the tab it lives on */
 g.concat(d).forEach(function(c){var orig=c.focus;c.focus=function(){show(d.indexOf(c)>-1?'d':'g');return orig.apply(c,arguments)}});
}
api.bind('ready',function(){
 api.section.each(function(s){s.expanded.bind(function(v){if(v)s.deferred.embedded.done(function(){setTimeout(function(){setup(s)},0)})})});
});
})(jQuery,wp.customize);
JS;
}

/* og-no-cz-shortcuts: Appearance menu shows no shortcuts to Customize sections (they live in Customize) */
add_action( 'admin_menu', function () {
	global $submenu;
	if ( empty( $submenu['themes.php'] ) ) {
		return;
	}
	foreach ( $submenu['themes.php'] as $i => $row ) {
		if ( isset( $row[2] ) && false !== strpos( $row[2], 'customize.php?' ) && false !== strpos( $row[2], 'autofocus' ) ) {
			unset( $submenu['themes.php'][ $i ] );
		}
	}
}, 99999 );
