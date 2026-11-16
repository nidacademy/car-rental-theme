<?php
/**
 * Appearance -> Customize -> Welcome & language pages: turn the intro screens on or off,
 * separately for desktop and for phones (tablets follow the phone settings, like the pictures).
 * - Welcome (swipe) screen on: visitors arriving from outside the site see the swipe screen first.
 *   Off: the site opens on a normal home page (the Cars page by default).
 * - Language page on: new visitors choose their language (after the swipe, or on their first visit).
 *   Off: it never opens by itself; visitors can still change language from the header.
 * The choice is made in the browser by screen width (767px and below = phone), so cached pages work for both.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Older single switches, used as starting values for the new ones */
function orego_intro_old( $key ) {
	return (bool) get_theme_mod( $key, true );
}

function orego_welcome_on_d() {
	return (bool) get_theme_mod( 'og_welcome_on_d', orego_intro_old( 'og_welcome_on' ) );
}
function orego_welcome_on_m() {
	return (bool) get_theme_mod( 'og_welcome_on_m', orego_intro_old( 'og_welcome_on' ) );
}
function orego_lang_on_d() {
	return (bool) get_theme_mod( 'og_lang_on_d', orego_intro_old( 'og_lang_on' ) );
}
function orego_lang_on_m() {
	return (bool) get_theme_mod( 'og_lang_on_m', orego_intro_old( 'og_lang_on' ) );
}

/** On for at least one device (older code asks this). */
function orego_welcome_on() {
	return orego_welcome_on_d() || orego_welcome_on_m();
}
function orego_lang_page_on() {
	return orego_lang_on_d() || orego_lang_on_m();
}

/** JS expression: true on phones and tablets (same width rule as the rest of the site). */
function orego_intro_is_m_js() {
	return "!!(window.matchMedia&&window.matchMedia('(max-width:767px)').matches)";
}

function orego_home_page_id() {
	$id = absint( get_theme_mod( 'og_home_page', orego_pid( 'cars' ) ) );
	if ( ! $id || orego_pid( 'welcome' ) === $id || 'page' !== get_post_type( $id ) ) {
		return orego_pid( 'cars' );
	}
	// A draft page can be previewed in Customize; visitors only get it once it is published.
	return ( 'publish' === get_post_status( $id ) || is_customize_preview() ) ? $id : orego_pid( 'cars' );
}

/** Pages that can be the home page: published ones and drafts (not the swipe page). */
function orego_home_page_choices() {
	$out = array();
	foreach ( get_pages( array( 'post_status' => 'publish,draft', 'sort_column' => 'menu_order,post_title' ) ) as $p ) {
		if ( orego_pid( 'welcome' ) === (int) $p->ID ) {
			continue;
		}
		$out[ $p->ID ] = ( '' !== $p->post_title ? $p->post_title : '#' . $p->ID ) . ( 'draft' === $p->post_status ? ' (draft: published when you press Publish)' : '' );
	}
	return $out;
}

add_action( 'customize_register', function ( $wp_customize ) {
	$sec = 'orego_intro';
	if ( ! $wp_customize->get_section( $sec ) ) {
		return;
	}
	$box = function ( $id, $prio, $label, $desc, $default ) use ( $wp_customize, $sec ) {
		$wp_customize->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => 'wp_validate_boolean', 'transport' => 'refresh' ) );
		$wp_customize->add_control( $id, array(
			'section'     => $sec,
			'priority'    => $prio,
			'type'        => 'checkbox',
			'label'       => $label,
			'description' => $desc,
		) );
	};
	// Desktop
	$box( 'og_welcome_on_d', 2, 'Show the welcome (swipe) screen on desktop', 'Off: desktop visitors open the website directly on the home page.', orego_intro_old( 'og_welcome_on' ) );
	$box( 'og_lang_on_d', 4, 'Show the language page to new desktop visitors', 'Off: it never opens by itself on desktop. Visitors can still change the language from the header.', orego_intro_old( 'og_lang_on' ) );
	// Phones & tablets
	$box( 'og_welcome_on_m', 2, 'Show the welcome (swipe) screen on phones', 'Off: phone visitors open the website directly on the home page.', orego_intro_old( 'og_welcome_on' ) );
	$box( 'og_lang_on_m', 4, 'Show the language page to new phone visitors', 'Off: it never opens by itself on phones. Visitors can still change the language from the menu.', orego_intro_old( 'og_lang_on' ) );

	$wp_customize->add_setting( 'og_home_page', array(
		'default'           => orego_pid( 'cars' ),
		'transport'         => 'refresh',
		'sanitize_callback' => function ( $v ) {
			$v = absint( $v );
			return array_key_exists( $v, orego_home_page_choices() ) ? $v : orego_pid( 'cars' );
		},
	) );
	$wp_customize->add_control( 'og_home_page', array(
		'section'     => $sec,
		'priority'    => 3,
		'type'        => 'select',
		'choices'     => orego_home_page_choices(),
		'label'       => 'Home page when the welcome screen is off',
		'description' => 'Used on desktop and on phones. Draft pages, like the old home page, can be chosen too.',
	) );
}, 30 );

/* Choosing a draft page as the home page publishes it, so visitors can see it */
add_action( 'customize_save_after', function () {
	$id = absint( get_theme_mod( 'og_home_page', orego_pid( 'cars' ) ) );
	if ( $id && orego_pid( 'welcome' ) !== $id && 'page' === get_post_type( $id ) && 'draft' === get_post_status( $id ) ) {
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
	}
}, 5 );

/* The device switch at the top of the section also shows / hides these switches */
add_filter( 'orego_intro_device_map', function ( $map ) {
	$map['desktop'] = array_merge( array( 'og_welcome_on_d', 'og_lang_on_d' ), $map['desktop'] );
	$map['mobile']  = array_merge( array( 'og_welcome_on_m', 'og_lang_on_m' ), $map['mobile'] );
	return $map;
} );

/* Customizer: the home page box shows when the current device has the welcome screen off */
add_action( 'customize_controls_print_footer_scripts', function () {
	?>
<script>
(function(api){if(!api)return;api.bind('ready',function(){
function dev(){var d=api.previewedDevice?api.previewedDevice.get():'desktop';return d==='desktop'?'d':'m';}
function upd(){var c=api.control('og_home_page');if(!c)return;var s=api('og_welcome_on_'+dev());c.active.set(!(s&&s.get()));}
['og_welcome_on_d','og_welcome_on_m'].forEach(function(id){api(id,function(v){v.bind(upd);});});
if(api.previewedDevice)api.previewedDevice.bind(function(){setTimeout(upd,0);});
document.addEventListener('click',function(e){if(e.target.closest&&e.target.closest('.ogi-dev .button'))setTimeout(upd,0);});
api.control('og_home_page',function(c){c.active.validate=function(){var s=api('og_welcome_on_'+dev());return !(s&&s.get());};upd();});
});})(window.wp&&wp.customize);
</script>
	<?php
}, 1000 ); // after customize-controls.js

/* The site address (/) is the welcome page only when it is on for both devices.
   Otherwise / is the chosen home page, and the device that has the welcome screen
   is sent to it (at its own address) by the welcome gate. */
add_filter( 'pre_option_page_on_front', function ( $v ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $v;
	}
	return ( orego_welcome_on_d() && orego_welcome_on_m() ) ? $v : orego_home_page_id();
} );

/* The welcome address: off everywhere -> home page; off only on this device -> skip to where the visitor was going */
add_action( 'template_redirect', function () {
	if ( ! orego_welcome_on() && orego_is_page( 'welcome' ) && ! is_customize_preview() ) {
		wp_safe_redirect( home_url( '/' ), 302 );
		exit;
	}
}, 2 );
add_action( 'orego_welcome_head_first', function () {
	if ( is_customize_preview() || ( orego_welcome_on_d() && orego_welcome_on_m() ) ) {
		return;
	}
	$home = wp_make_link_relative( get_permalink( orego_home_page_id() ) );
	echo '<script id="og-welcome-dev">(function(){var m=' . orego_intro_is_m_js() . ';if(m?' . ( orego_welcome_on_m() ? 'true' : 'false' ) . ':' . ( orego_welcome_on_d() ? 'true' : 'false' ) . ')return;'
		. 'var n=new URLSearchParams(location.search).get("next");if(!n||n.charAt(0)!=="/"||n.charAt(1)==="/")n=' . wp_json_encode( $home ) . ';location.replace(n);})();</script>' . "\n";
} );

/* Switching changes which page is the home page: clear the page cache */
add_action( 'customize_save_after', function () {
	do_action( 'litespeed_purge_all' );
} );
