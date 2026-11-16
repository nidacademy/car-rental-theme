<?php
/**
 * Customizer: opening a section moves the preview to what that section changes.
 * - Car Cards, Browse Cars, Currencies: the Cars page.
 * - Homepage settings, Welcome & language pages: the home (welcome) page.
 * - Header, footer, menus, colours and other site-wide parts: stay on the current page,
 *   unless it is the welcome page (no header/footer there), then the Cars page.
 * - Menu Toggle & mobile menu: also switches the preview to phone size.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The page that holds the car filters (the Cars page). */
function orego_cz_cars_page_url() {
	if ( get_post_status( orego_pid( 'cars' ) ) === 'publish' ) {
		return orego_purl( 'cars' );
	}
	global $wpdb;
	$id = (int) $wpdb->get_var( "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_elementor_data' WHERE p.post_type = 'page' AND p.post_status = 'publish' AND m.meta_value LIKE '%orego_car_filters%' ORDER BY p.ID LIMIT 1" ); // phpcs:ignore
	return $id ? get_permalink( $id ) : home_url( '/' );
}

add_action( 'customize_controls_enqueue_scripts', function () {
	$home = home_url( '/' );
	$cars = orego_cz_cars_page_url();
	// "site": a part of every page except the welcome page.
	$top  = array( 'site' => 1, 'scroll' => 'top' );
	$foot = array( 'site' => 1, 'scroll' => 'footer' );
	$map  = array(
		'orego_browse'      => array( 'url' => $cars, 'scroll' => 'top' ),
		'orego_cards'       => array( 'url' => $cars, 'scroll' => '.og-cars' ),
		'orego_currencies'  => array( 'url' => $cars, 'scroll' => '.og-cars' ),
		'static_front_page' => array( 'url' => $home, 'scroll' => 'top' ),
		'orego_footer'      => $foot,
		'nav_menu[18]'      => $foot,
		'orego_drawer'      => array( 'site' => 1, 'scroll' => 'top', 'device' => 'mobile' ),
		'nav_menu[17]'      => $top,
		'menu_locations'    => $top,
		'title_tagline'     => $top,
		'orego_promo'       => $top,
		'orego_design'      => $top,
		'colors'            => $top,
		'header_image'      => $top,
		'background_image'  => $top,
		'custom_css'        => $top,
		'panel:nav_menus'   => $top,
	);
	foreach ( array( 'ogh_builder', 'ogh_layout', 'ogh_logo', 'ogh_menu', 'ogh_menu2', 'ogh_lang', 'ogh_cur', 'ogh_search', 'ogh_btn', 'ogh_html', 'ogh_social', 'ogh_transparent', 'ogh_sticky', 'ogh_row_top', 'ogh_row_main', 'ogh_row_bottom' ) as $id ) {
		$map[ $id ] = $top;
	}
	wp_add_inline_script( 'customize-controls', 'window.ogCzNav=' . wp_json_encode( array( 'map' => $map, 'home' => $home, 'cars' => $cars ) ) . ';' . <<<'JS'
(function(api){
	var C=window.ogCzNav||{},MAP=C.map||{},pending=null;
	function clean(u){try{var x=new URL(u,location.href);return x.origin+x.pathname.replace(/\/+$/,'');}catch(e){return String(u||'');}}
	function send(where){api.previewer.send('og-cz-scroll',where);}
	function go(t){
		if(t.device&&api.previewedDevice)api.previewedDevice.set(t.device);
		var raw=String(api.previewer.previewUrl.get()||''),cur=clean(raw),target=t.url||null;
		// Site-wide parts: the welcome page and the language preview don't show them.
		if(!target&&t.site&&(cur===clean(C.home)||/[?&]og_langprev=/.test(raw)))target=C.cars;
		if(target&&(cur!==clean(target)||/[?&]og_langprev=/.test(raw))){pending=t.scroll||'top';api.previewer.previewUrl.set(target);return;}
		send(t.scroll||'top');
	}
	api.bind('ready',function(){
		// When the preview finishes loading a page we asked for, scroll it.
		api.previewer.bind('ready',function(){if(pending){var w=pending;pending=null;setTimeout(function(){send(w);},150);}});
		Object.keys(MAP).forEach(function(id){
			if(id.indexOf('panel:')===0){
				api.panel(id.slice(6),function(p){p.expanded.bind(function(open){if(open)go(MAP[id]);});});
			}else{
				api.section(id,function(s){s.expanded.bind(function(open){if(open)go(MAP[id]);});});
			}
		});
		// Menus added later get the same behaviour.
		api.section.bind('add',function(s){
			if(MAP[s.id]||String(s.id).indexOf('nav_menu[')!==0)return;
			s.expanded.bind(function(open){if(open)go({site:1,scroll:'top'});});
		});
	});
})(wp.customize);
JS
	);
} );

/* Inside the preview: scroll to what the open section changes. */
add_action( 'customize_preview_init', function () {
	wp_add_inline_script( 'customize-preview', <<<'JS'
(function(api){
	function scrollTo(where){
		if(!where||where==='top'){window.scrollTo({top:0,behavior:'smooth'});return;}
		var el=where==='footer'?document.querySelector('.ogf2, footer.site-footer, footer'):document.querySelector(where);
		if(!el){window.scrollTo({top:0,behavior:'smooth'});return;}
		if(where==='footer'){window.scrollTo({top:document.documentElement.scrollHeight,behavior:'smooth'});return;}
		var head=parseInt(getComputedStyle(document.documentElement).getPropertyValue('--ogh-h'),10)||72;
		window.scrollTo({top:Math.max(0,el.getBoundingClientRect().top+window.pageYOffset-head-24),behavior:'smooth'});
	}
	api.bind('preview-ready',function(){api.preview.bind('og-cz-scroll',scrollTo);});
})(wp.customize);
JS
	);
} );
