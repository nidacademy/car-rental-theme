<?php
/**
 * Desktop / Phone settings in Customize for: Design colours, Promo bar, Browse Cars, Dark mode.
 * - Each section gets a Desktop | Phone switch at the top (it also switches the preview).
 * - Phone settings are copies saved as "<setting>_ph". Until one is changed it follows the desktop value.
 * - Phone = screens up to 767px wide (the same rule the rest of the site uses).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The phone value of a setting: its own saved value, or the desktop value. */
function orego_ph( $id, $desktop_value ) {
	$v = get_theme_mod( $id . '_ph', null );
	return null === $v ? $desktop_value : $v;
}

/** Did the visitor-facing phone value change from desktop? */
function orego_ph_differs( $id, $desktop_value ) {
	$v = get_theme_mod( $id . '_ph', null );
	return null !== $v && (string) $v !== (string) $desktop_value;
}

/** Sections and the settings that get a phone copy. 'desk' = desktop only, 'phone' = phone only (already separate). */
function orego_ph_sections() {
	$browse = array( 'title', 'subtitle', 'show_sort', 'sort', 'per', 'more_text', 'show_filters', 'f_type', 'f_trans', 'f_fuel', 'f_seats', 'f_price', 'f_loc' );
	return array(
		'orego_design' => array(
			'split' => array( 'orego_design_theme', 'orego_design_primary', 'orego_design_accent', 'orego_design_price', 'orego_design_badge', 'orego_design_ink', 'orego_design_bg' ),
		),
		'orego_promo'  => array(
			'split' => array( 'orego_promo_show', 'orego_promo_text', 'orego_promo_url', 'orego_promo_bg', 'orego_promo_fg', 'orego_promo_size', 'orego_promo_pad_y', 'orego_promo_align' ),
		),
		'orego_browse' => array(
			'split' => array_map( function ( $k ) {
				return 'orego_browse_' . $k;
			}, $browse ),
			'desk'  => array( 'orego_browse_cols', 'orego_browse_cols_t', 'orego_browse_filters_side', 'orego_browse_filters_open' ),
			'phone' => array( 'orego_browse_cols_m' ),
		),
		'orego_dark'   => array(
			'split' => array( 'og_dark_default', 'og_dark_toggle', 'og_dark_bg', 'og_dark_surface', 'og_dark_text' ),
		),
	);
}

/* ------------------------------------------------------------------ */
/* Customize                                                           */
/* ------------------------------------------------------------------ */

add_action( 'customize_register', function ( $wp ) {
	if ( ! class_exists( 'OreGO_Ph_Switch' ) ) {
		class OreGO_Ph_Switch extends WP_Customize_Control {
			public $type = 'orego_ph_switch';
			public function render_content() {
				echo '<span class="customize-control-title">Editing for</span>'
					. '<div class="ogcd-dev ogph-dev" role="group"><button type="button" class="button" data-dev="desktop" aria-pressed="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg><span>Desktop</span></button>'
					. '<button type="button" class="button" data-dev="mobile" aria-pressed="false"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2.5" width="12" height="19" rx="2.6"/><path d="M11 18.5h2"/></svg><span>Phone</span></button></div>'
					. '<span class="description customize-control-description">Desktop and phone have their own settings here. Phone settings follow the desktop ones until you change them.</span>';
			}
		}
	}
	foreach ( orego_ph_sections() as $sec => $cfg ) {
		if ( ! $wp->get_section( $sec ) ) {
			continue;
		}
		$wp->add_setting( 'orego_ph_sw_' . $sec, array( 'sanitize_callback' => '__return_empty_string', 'transport' => 'postMessage' ) );
		$wp->add_control( new OreGO_Ph_Switch( $wp, 'orego_ph_sw_' . $sec, array( 'section' => $sec, 'priority' => -10, 'settings' => 'orego_ph_sw_' . $sec ) ) );
		foreach ( $cfg['split'] as $id ) {
			$set  = $wp->get_setting( $id );
			$ctrl = $wp->get_control( $id );
			if ( ! $set || ! $ctrl ) {
				continue;
			}
			$nid = $id . '_ph';
			$wp->add_setting( $nid, array(
				'default'           => $set->value(), // shows the desktop value until changed
				'sanitize_callback' => $set->sanitize_callback,
				'transport'         => 'refresh',
				'capability'        => $set->capability,
			) );
			$c                  = clone $ctrl;
			$c->id              = $nid;
			$c->settings        = array( 'default' => $wp->get_setting( $nid ) );
			$c->setting         = $wp->get_setting( $nid );
			$c->instance_number = $ctrl->instance_number + 100000; // right after the desktop ones, same order
			$wp->add_control( $c );
		}
		// Phone copies that depend on another phone setting
		if ( 'orego_browse' === $sec ) {
			$on = function () use ( $wp ) {
				$s = $wp->get_setting( 'orego_browse_show_filters_ph' );
				return $s ? (bool) $s->value() : true;
			};
			foreach ( array( 'f_type', 'f_trans', 'f_fuel', 'f_seats', 'f_price', 'f_loc' ) as $g ) {
				if ( $c = $wp->get_control( 'orego_browse_' . $g . '_ph' ) ) {
					$c->active_callback = $on;
				}
			}
			if ( $c = $wp->get_control( 'orego_browse_more_text_ph' ) ) {
				$c->active_callback = function () use ( $wp ) {
					$s = $wp->get_setting( 'orego_browse_per_ph' );
					return $s ? (int) $s->value() > 0 : true;
				};
			}
		}
	}
}, 999 );

add_action( 'customize_controls_print_styles', function () {
	echo '<style>'
		. '#customize-controls.ogph-on-m .ogph-d,#customize-controls:not(.ogph-on-m) .ogph-m{display:none!important}</style>';
} );

add_action( 'customize_controls_print_footer_scripts', function () {
	$map = array();
	foreach ( orego_ph_sections() as $sec => $cfg ) {
		$d = array_merge( $cfg['split'], $cfg['desk'] ?? array() );
		$m = array_merge( array_map( function ( $id ) {
			return $id . '_ph';
		}, $cfg['split'] ), $cfg['phone'] ?? array() );
		$map[ $sec ] = array( 'd' => $d, 'm' => $m );
	}
	?>
<script>
(function(api){if(!api)return;var MAP=<?php echo wp_json_encode( $map ); ?>;
api.bind('ready',function(){
	var root=document.getElementById('customize-controls');
	Object.keys(MAP).forEach(function(s){
		MAP[s].d.forEach(function(id){api.control(id,function(c){c.container.addClass('ogph-d');});});
		MAP[s].m.forEach(function(id){api.control(id,function(c){c.container.addClass('ogph-m');});});
	});
	function show(dev){var m=dev!=='desktop';root.classList.toggle('ogph-on-m',m);
		document.querySelectorAll('.ogph-dev .button').forEach(function(b){b.setAttribute('aria-pressed',(b.dataset.dev==='mobile')===m?'true':'false');});}
	document.addEventListener('click',function(e){var b=e.target.closest&&e.target.closest('.ogph-dev .button');if(!b)return;e.preventDefault();
		show(b.dataset.dev);if(api.previewedDevice&&api.previewedDevice.get()!==b.dataset.dev)api.previewedDevice.set(b.dataset.dev);},true);
	if(api.previewedDevice){api.previewedDevice.bind(show);show(api.previewedDevice.get());}else show('desktop');
});})(window.wp&&wp.customize);
</script>
	<?php
}, 1000 ); // after customize-controls.js

/* ------------------------------------------------------------------ */
/* Site: shared show/hide helpers                                      */
/* ------------------------------------------------------------------ */

add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	echo '<style id="og-ph-base">@media (max-width:767px){.og-dv-d{display:none!important}}@media (min-width:768px){.og-dv-m{display:none!important}}</style>' . "\n";
}, 38 );

/* ------------------------------------------------------------------ */
/* Design colours                                                      */
/* ------------------------------------------------------------------ */

function orego_tokens_ph() {
	$all  = orego_design_presets();
	$name = orego_ph( 'orego_design_theme', get_theme_mod( 'orego_design_theme', 'emerald' ) );
	$p    = $all[ $name ] ?? $all['emerald'];
	$keys = array( 1 => 'primary', 2 => 'accent', 3 => 'price', 4 => 'badge', 5 => 'ink', 6 => 'bg' );
	$t    = array();
	foreach ( $keys as $i => $k ) {
		$own     = sanitize_hex_color( (string) orego_ph( 'orego_design_' . $k, get_theme_mod( 'orego_design_' . $k, '' ) ) );
		$t[ $k ] = $own ? $own : $p[ $i ];
	}
	return $t;
}

add_action( 'wp_head', function () {
	if ( is_admin() || ! function_exists( 'orego_tokens' ) ) {
		return;
	}
	$t = orego_tokens_ph();
	if ( $t === orego_tokens() ) {
		return;
	}
	$ink = implode( ',', orego_hex_rgb( $t['ink'] ) );
	$css = '--hx-primary:' . $t['primary'] . ';--hx-primary-dark:' . orego_mix( $t['primary'], '#000000', .2 ) . ';--hx-primary-soft:' . orego_mix( $t['primary'], '#ffffff', .9 ) . ';--hx-primary-softer:' . orego_mix( $t['primary'], '#ffffff', .95 ) . ';--hx-primary-border:' . orego_mix( $t['primary'], '#ffffff', .7 ) . ';'
		. '--hx-accent:' . $t['accent'] . ';--hx-accent-title:' . orego_mix( $t['accent'], '#ffffff', .12 ) . ';--hx-price:' . $t['price'] . ';--hx-badge:' . $t['badge'] . ';--hx-badge-bg:' . orego_mix( $t['badge'], '#ffffff', .9 ) . ';'
		. '--hx-ink:' . $t['ink'] . ';--hx-text:rgba(' . $ink . ',.75);--hx-muted:rgba(' . $ink . ',.55);--hx-bg:' . $t['bg'];
	echo '<style id="orego-design-tokens-ph">@media (max-width:767px){:root{' . $css . '}}</style>' . "\n"; // phpcs:ignore
}, 39 );

/* ------------------------------------------------------------------ */
/* Promo bar                                                           */
/* ------------------------------------------------------------------ */

/** One promo line (text with or without a link). */
function orego_promo_inner( $text, $url ) {
	$t = esc_html( trim( (string) $text ) );
	return $url ? '<a href="' . esc_url( $url ) . '">' . $t . '</a>' : '<span>' . $t . '</span>';
}

add_filter( 'orego_promo_html', function () {
	$d  = array(
		'show' => (bool) orego_mod( 'promo_show' ),
		'text' => trim( (string) orego_mod( 'promo_text' ) ),
		'url'  => (string) orego_mod( 'promo_url' ),
	);
	$m  = array(
		'show' => (bool) orego_ph( 'orego_promo_show', $d['show'] ),
		'text' => trim( (string) orego_ph( 'orego_promo_text', $d['text'] ) ),
		'url'  => (string) orego_ph( 'orego_promo_url', $d['url'] ),
	);
	$don = $d['show'] && '' !== $d['text'];
	$mon = $m['show'] && '' !== $m['text'];
	if ( ! $don && ! $mon ) {
		return '';
	}
	$cls = 'ogx2-promo' . ( $don ? '' : ' og-dv-m' ) . ( $mon ? '' : ' og-dv-d' );
	if ( ! $don || ! $mon || ( $d['text'] === $m['text'] && $d['url'] === $m['url'] ) ) {
		$one = $don ? $d : $m;
		return '<div class="' . $cls . '">' . orego_promo_inner( $one['text'], $one['url'] ) . '</div>';
	}
	$a = orego_promo_inner( $d['text'], $d['url'] );
	$b = orego_promo_inner( $m['text'], $m['url'] );
	$a = preg_replace( '#^<(a|span)#', '<$1 class="og-dv-d"', $a );
	$b = preg_replace( '#^<(a|span)#', '<$1 class="og-dv-m"', $b );
	return '<div class="' . $cls . '">' . $a . $b . '</div>';
} );

add_action( 'wp_head', function () {
	if ( is_admin() || ! function_exists( 'orego_mod' ) ) {
		return;
	}
	$css = '';
	foreach ( array( 'promo_bg' => '--ogp-bg', 'promo_fg' => '--ogp-fg' ) as $k => $var ) {
		if ( orego_ph_differs( 'orego_' . $k, orego_mod( $k ) ) ) {
			$c = sanitize_hex_color( (string) get_theme_mod( 'orego_' . $k . '_ph' ) );
			if ( $c ) {
				$css .= ':root{' . $var . ':' . $c . '}';
			}
		}
	}
	if ( orego_ph_differs( 'orego_promo_size', orego_mod( 'promo_size' ) ) ) {
		$css .= 'html body .ogx2-promo{font-size:' . max( 10, min( 22, (int) get_theme_mod( 'orego_promo_size_ph' ) ) ) . 'px}';
	}
	if ( orego_ph_differs( 'orego_promo_pad_y', orego_mod( 'promo_pad_y' ) ) ) {
		$css .= 'html body .ogx2-promo{padding-block:' . max( 2, min( 30, (int) get_theme_mod( 'orego_promo_pad_y_ph' ) ) ) . 'px}';
	}
	if ( orego_ph_differs( 'orego_promo_align', orego_mod( 'promo_align' ) ) ) {
		$css .= 'start' === get_theme_mod( 'orego_promo_align_ph' ) ? 'html body .ogx2-promo{text-align:start;padding-inline:16px}' : 'html body .ogx2-promo{text-align:center;padding-inline:12px}';
	}
	if ( $css ) {
		echo '<style id="og-promo-ph">@media (max-width:767px){' . $css . '}</style>' . "\n"; // phpcs:ignore
	}
}, 99 );

/* ------------------------------------------------------------------ */
/* Browse Cars: phone values (the page itself reads these)             */
/* ------------------------------------------------------------------ */

function orego_browse_opt_ph( $k ) {
	return orego_ph( 'orego_browse_' . $k, orego_browse_opt( $k ) );
}

/* ------------------------------------------------------------------ */
/* Dark mode: phone values                                             */
/* ------------------------------------------------------------------ */

function orego_dark_mod_ph( $k ) {
	$v = orego_ph( $k, orego_dark_mod( $k ) );
	if ( in_array( $k, array( 'og_dark_bg', 'og_dark_surface', 'og_dark_text' ), true ) ) {
		$v = sanitize_hex_color( (string) $v );
		return $v ? $v : orego_dark_mod( $k );
	}
	return $v;
}

/* ------------------------------------------------------------------ */
/* Phone width known from the first line                               */
/* The Elementor page template prints its viewport tag at the end of  */
/* the head, so scripts above it saw a 980px "desktop" on phones.     */
/* The same tag at the top fixes that (the later copy is identical).   */
/* ------------------------------------------------------------------ */
add_action( 'wp_head', function () {
	if ( ! is_admin() ) {
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />' . "\n";
	}
}, -100001 );

/* ------------------------------------------------------------------ */
/* Design colours: "Page background" and "Badges & warnings"           */
/* - Page background: when filled in, it replaces the light tint of    */
/*   the primary colour that the pages use behind everything.          */
/* - Badges & warnings: the "Most popular" badge and every error or    */
/*   warning message (booking form, phone number, search, dates).      */
/* ------------------------------------------------------------------ */

add_action( 'customize_register', function ( $wp ) {
	foreach ( array( '', '_ph' ) as $sfx ) {
		if ( $c = $wp->get_control( 'orego_design_bg' . $sfx ) ) {
			$c->description = 'Empty = a light tint of the primary colour.';
		}
		if ( $c = $wp->get_control( 'orego_design_badge' . $sfx ) ) {
			$c->description = 'The "Most popular" badge and error / warning messages (booking form, phone number, search). Empty = from the theme.';
		}
	}
}, 1000 );

/** The page background the visitor chose (empty = keep the primary tint). */
function orego_page_bg( $ph = false ) {
	$d = sanitize_hex_color( (string) get_theme_mod( 'orego_design_bg', '' ) );
	if ( ! $ph ) {
		return (string) $d;
	}
	return (string) sanitize_hex_color( (string) orego_ph( 'orego_design_bg', (string) $d ) );
}

add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	$kit = (int) get_option( 'elementor_active_kit' );
	$bg  = function ( $c ) use ( $kit ) {
		return 'html body{background:' . $c . '!important}' . ( $kit ? 'html body.elementor-kit-' . $kit . '{--e-global-color-ogbg:' . $c . '}' : '' );
	};
	$css = '';
	$d   = orego_page_bg();
	$m   = orego_page_bg( true );
	if ( '' !== $d ) {
		$css .= $bg( $d );
	}
	if ( $m !== $d ) {
		$css .= '@media (max-width:767px){' . ( '' !== $m ? $bg( $m ) : 'html body{background:var(--hx-primary-softer)!important}' . ( $kit ? 'html body.elementor-kit-' . $kit . '{--e-global-color-ogbg:var(--hx-primary-softer)}' : '' ) ) . '}';
	}
	// Badges & warnings
	$css .= '.is-fire .ogqp-l svg{color:var(--hx-badge,#EA580C)!important}'
		. '.ogb-agree.is-bad,.ogb-agree.is-bad a{color:var(--hx-badge,#DC2626)!important}'
		. '.ogb-agree.is-bad input{outline-color:var(--hx-badge,#DC2626)!important}'
		. '.ogb-err{color:var(--hx-badge,#B91C1C)!important;background:var(--hx-badge-bg,#FEF2F2)!important;border-color:color-mix(in srgb,var(--hx-badge,#B91C1C) 30%,#fff)!important}'
		. '.ogpb-msg.is-err,.ogpb-tel-err{color:var(--hx-badge,#B91C1C)!important}'
		. '.ogpb-tel.is-bad>input,.ogpb-tel.is-bad .ogpb-cc{border-color:var(--hx-badge,#DC2626)!important}';
	echo '<style id="og-design-bg-badge">' . $css . '</style>' . "\n"; // phpcs:ignore
}, 99 );

/* ------------------------------------------------------------------ */
/* Main buttons follow the theme's primary colour (they were black):   */
/* car page "Book now", phone tab bar centre button, menu "My booking",*/
/* search button, Quick picks arrow, FAQ "Contact us", "Find my booking"*/
/* ------------------------------------------------------------------ */
add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	$b = 'html body .ogx-rent,html body .og-tab-fab,html body .ogx2-dfoot .ogx2-cta-full,html body .ogtb-go,html body .ogqp-go,html body .ogi-btn,html body .ogmb-btn';
	$h = 'html body .ogx-rent:hover,html body .og-tab:hover .og-tab-fab,html body .ogx2-dfoot .ogx2-cta-full:hover,html body .ogtb-go:hover,html body .ogqp-c:hover .ogqp-go,html body .ogi-btn:hover,html body .ogmb-btn:hover';
	// Header button: follows the theme unless Header -> Button has its own colour
	if ( '' === (string) sanitize_hex_color( (string) get_theme_mod( 'orego_cta_bg', '' ) ) ) {
		$b .= ',html body .ogx2-c .ogx2-cta:not(.ogx2-cta-full),html body .ogx2-acts .ogx2-cta:not(.ogx2-cta-full)';
		$h .= ',html body .ogx2-c .ogx2-cta:not(.ogx2-cta-full):hover,html body .ogx2-acts .ogx2-cta:not(.ogx2-cta-full):hover';
	}
	echo '<style id="og-theme-buttons">' . $b . '{background:var(--hx-primary,#15171C)!important;border-color:var(--hx-primary,#15171C)!important;color:#fff!important}'
		. $h . '{background:var(--hx-primary-dark,#000)!important}</style>' . "\n";
}, 100 );
