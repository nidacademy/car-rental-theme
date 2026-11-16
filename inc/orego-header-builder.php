<?php
/**
 * Exotic Cars header builder.
 * Customize -> Header: drag items into 3 rows x 3 columns, separately for Desktop and Tablet / Mobile.
 * The header ([orego_header] in orego-chrome.php) is drawn from this layout.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_hb_items() {
	$L = function_exists( 'orego_L' ) ? 'orego_L' : function ( $en ) {
		return $en;
	};
	return array(
		'logo'   => array( $L( 'Logo', 'الشعار' ), 'ogh_logo' ),
		'menu'   => array( $L( 'Primary Navigation', 'القائمة الرئيسية' ), 'ogh_menu' ),
		'menu2'  => array( $L( 'Secondary Navigation', 'القائمة الثانوية' ), 'ogh_menu2' ),
		'search' => array( $L( 'Search', 'البحث' ), 'ogh_search' ),
		'lang'   => array( $L( 'Language', 'اللغة' ), 'ogh_lang' ),
		'cur'    => array( $L( 'Currency', 'العملة' ), 'ogh_cur' ),
		'button' => array( $L( 'Button', 'الزر' ), 'ogh_btn' ),
		'html'   => array( 'HTML', 'ogh_html' ),
		'social' => array( $L( 'Social', 'التواصل الاجتماعي' ), 'ogh_social' ),
		'toggle' => array( $L( 'Menu Toggle', 'زر القائمة' ), 'orego_drawer' ),
	);
}

function orego_hb_empty_dev() {
	$e = array();
	foreach ( array( 'top', 'main', 'bottom' ) as $r ) {
		$e[ $r ] = array( 'left' => array(), 'center' => array(), 'right' => array() );
	}
	return $e;
}

function orego_hb_default_layout() {
	$d                      = orego_hb_empty_dev();
	$m                      = orego_hb_empty_dev();
	$d['main']['left']      = array( 'logo', 'menu' );
	$d['main']['center']    = array( 'search' );
	$d['main']['right']     = array( 'lang', 'cur', 'button' );
	$m['main']['left']      = array( 'lang' );
	$m['main']['center']    = array( 'logo' );
	$m['main']['right']     = array( 'toggle' );
	return array( 'desktop' => $d, 'mobile' => $m );
}

function orego_hb_sanitize( $v ) {
	$d = json_decode( is_string( $v ) ? $v : '', true );
	if ( ! is_array( $d ) ) {
		return wp_json_encode( orego_hb_default_layout() );
	}
	$items = array_keys( orego_hb_items() );
	$out   = array();
	foreach ( array( 'desktop', 'mobile' ) as $dv ) {
		$seen = array();
		foreach ( array( 'top', 'main', 'bottom' ) as $r ) {
			foreach ( array( 'left', 'center', 'right' ) as $c ) {
				$list = array();
				foreach ( (array) ( $d[ $dv ][ $r ][ $c ] ?? array() ) as $k ) {
					$k = sanitize_key( (string) $k );
					if ( in_array( $k, $items, true ) && ! isset( $seen[ $k ] ) ) {
						$seen[ $k ] = 1;
						$list[]     = $k;
					}
				}
				$out[ $dv ][ $r ][ $c ] = $list;
			}
		}
	}
	return wp_json_encode( $out );
}

function orego_hb_layout() {
	$raw = get_theme_mod( 'orego_hb_layout', '' );
	$l   = json_decode( orego_hb_sanitize( $raw ? $raw : wp_json_encode( orego_hb_default_layout() ) ), true );
	return $l;
}

function orego_hb_mod( $k, $def = '' ) {
	$d = orego_hb_defaults();
	return get_theme_mod( 'orego_' . $k, $d[ $k ] ?? $def );
}

function orego_hb_defaults() {
	return array(
		'hb_top_h'         => 40,
		'hb_top_bg'        => '',
		'hb_top_fg'        => '',
		'hb_top_border'    => true,
		'hb_bottom_h'      => 52,
		'hb_bottom_bg'     => '',
		'hb_bottom_fg'     => '',
		'hb_bottom_border' => true,
		'hb_menu1'         => 0,
		'hb_menu2'         => 0,
		'hb_html'          => '',
		'hb_html_size'     => 14,
		'social_facebook'  => '',
		'social_instagram' => '',
		'social_whatsapp'  => '',
		'social_tiktok'    => '',
		'social_x'         => '',
		'social_youtube'   => '',
		'social_style'     => 'plain',
		'social_size'      => 18,
	);
}

/* ------------------------------------------------------------------ */
/* Items                                                               */
/* ------------------------------------------------------------------ */

function orego_hb_nav( $which = 1 ) {
	$id = (int) orego_hb_mod( 1 === $which ? 'hb_menu1' : 'hb_menu2' );
	if ( 1 === $which && ! $id ) {
		$tree = orego_menu_tree( 'orego_main', 'Main Menu' );
	} else {
		$menu = $id ? wp_get_nav_menu_object( $id ) : false;
		$tree = $menu ? orego_menu_tree( '__orego_none__', $menu->name ) : array();
	}
	$cls = 'ogx2-links ogx2-links' . (int) $which;
	if ( ! $tree ) {
		return is_customize_preview() ? '<nav class="' . $cls . ' is-empty"><ul><li><a href="#">' . esc_html( orego_L( 'Choose a menu', 'اختر قائمة' ) ) . '</a></li></ul></nav>' : '';
	}
	$o = '<nav class="' . $cls . '" aria-label="' . ( 1 === $which ? 'Main menu' : 'Secondary menu' ) . '"><ul>';
	foreach ( $tree as $it ) {
		$o .= '<li class="' . ( $it['current'] ? 'is-current' : '' ) . ( $it['kids'] ? ' has-kids' : '' ) . '"><a href="' . esc_url( $it['url'] ) . '"' . ( $it['current'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $it['title'] ) . ( $it['kids'] ? orego_chrome_ic( 'chev' ) : '' ) . '</a>';
		if ( $it['kids'] ) {
			$o .= '<div class="ogx2-menu">';
			foreach ( $it['kids'] as $k ) {
				$o .= '<a href="' . esc_url( $k['url'] ) . '"' . ( $k['current'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $k['title'] ) . '</a>';
			}
			$o .= '</div>';
		}
		$o .= '</li>';
	}
	return $o . '</ul></nav>';
}

function orego_hb_search() {
	static $n = 0;
	$n++;
	$id = 'ogx2-q' . ( $n > 1 ? $n : '' );
	$ph = trim( (string) ( function_exists( 'ogh_mod' ) ? ogh_mod( 'search_placeholder' ) : 'Search for a car…' ) );
	return '<form class="ogx2-search" action="' . esc_url( orego_purl( 'cars' ) ) . '" method="get" role="search"><label class="screen-reader-text" for="' . $id . '">' . esc_html( $ph ? $ph : 'Search for a car' ) . '</label><input id="' . $id . '" type="search" name="q" placeholder="' . esc_attr( $ph ) . '" autocomplete="off"><button type="submit" aria-label="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></button></form>';
}

function orego_hb_button() {
	$t = trim( (string) orego_mod( 'cta_text' ) );
	$t = '' !== $t ? $t : 'Book now';
	return '<a class="ogx2-cta" href="' . esc_url( orego_cta_url() ) . '"' . ( orego_mod( 'cta_new_tab' ) ? ' target="_blank" rel="noopener"' : '' ) . '>' . esc_html( $t ) . '</a>';
}

function orego_hb_lang() {
	$o = '<div class="ogx2-dd notranslate" data-dd="lang"><button type="button" class="ogx2-pill" aria-haspopup="true" aria-expanded="false" aria-label="Language"><span class="ogx2-globe" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"/></svg></span><span class="ogx2-flag">' . orego_flag( 'en' ) . '</span><b>EN</b>' . orego_chrome_ic( 'chev' ) . '</button><div class="ogx2-panel" role="menu" hidden><p class="ogx2-ph">LANGUAGE · اللغة</p>';
	foreach ( orego_langs() as $code => $l ) {
		$o .= '<button type="button" role="menuitemradio" aria-checked="' . ( 'en' === $code ? 'true' : 'false' ) . '" data-lang="' . esc_attr( $code ) . '" data-short="' . esc_attr( $l[1] ) . '" lang="' . esc_attr( $code ) . '"><span class="ogx2-flag">' . orego_flag( $code ) . '</span><span class="ogx2-name">' . esc_html( $l[0] ) . '</span><span class="ogx2-tick">' . orego_chrome_ic( 'check' ) . '</span></button>';
	}
	return $o . '</div></div>';
}

function orego_hb_cur() {
	$rates = orego_rates();
	$o     = '<div class="ogx2-dd notranslate" data-dd="cur"><button type="button" class="ogx2-pill" aria-haspopup="true" aria-expanded="false" aria-label="Currency"><span class="ogx2-flag">' . orego_flag( $rates['default'] ) . '</span><b>' . esc_html( $rates['default'] ) . '</b>' . orego_chrome_ic( 'chev' ) . '</button><div class="ogx2-panel" role="menu" hidden><p class="ogx2-ph">CURRENCY</p>';
	foreach ( orego_currencies() as $code => $c ) {
		$o .= '<button type="button" role="menuitemradio" aria-checked="' . ( $rates['default'] === $code ? 'true' : 'false' ) . '" data-cur="' . esc_attr( $code ) . '"><span class="ogx2-flag">' . orego_flag( $code ) . '</span><span class="ogx2-name">' . esc_html( $code ) . '<small>' . esc_html( $c[0] ) . '</small></span><span class="ogx2-tick">' . orego_chrome_ic( 'check' ) . '</span></button>';
	}
	return $o . '</div></div>';
}

function orego_hb_html() {
	$h = trim( (string) orego_hb_mod( 'hb_html' ) );
	if ( '' === $h ) {
		return is_customize_preview() ? '<div class="ogx2-html is-empty">' . esc_html( orego_L( 'Add your text in Header → HTML', 'أضف نصك من الهيدر ← HTML' ) ) . '</div>' : '';
	}
	return '<div class="ogx2-html">' . wp_kses_post( do_shortcode( str_replace( '{year}', wp_date( 'Y' ), $h ) ) ) . '</div>';
}

function orego_hb_social_icons() {
	$a = 'fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"';
	return array(
		'facebook'  => array( 'Facebook', '<path ' . $a . ' d="M15 3h-2a4 4 0 0 0-4 4v14M6 11h8"/>' ),
		'instagram' => array( 'Instagram', '<rect ' . $a . ' x="3" y="3" width="18" height="18" rx="5"/><circle ' . $a . ' cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1.2" fill="currentColor"/>' ),
		'whatsapp'  => array( 'WhatsApp', '<path ' . $a . ' d="M3.5 20.5l1.4-4.2A8.5 8.5 0 1 1 8 19.3z"/><path ' . $a . ' d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 1c-1-.5-1.5-1-2-2l1-1-1-2z"/>' ),
		'tiktok'    => array( 'TikTok', '<path ' . $a . ' d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path ' . $a . ' d="M14 3c.4 2.6 2.3 4.4 5 4.6"/>' ),
		'x'         => array( 'X', '<path ' . $a . ' d="M4 4l16 16M20 4L4 20"/>' ),
		'youtube'   => array( 'YouTube', '<rect ' . $a . ' x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10 9.2v5.6l4.8-2.8z" fill="currentColor"/>' ),
	);
}

function orego_hb_social() {
	$links = array();
	foreach ( orego_hb_social_icons() as $k => $ic ) {
		$u = trim( (string) orego_hb_mod( 'social_' . $k ) );
		if ( '' === $u ) {
			continue;
		}
		if ( 'whatsapp' === $k && preg_match( '/^\+?[\d\s\-]+$/', $u ) ) {
			$u = 'https://wa.me/' . preg_replace( '/\D/', '', $u );
		}
		$links[ $k ] = $u;
	}
	$demo = false;
	if ( ! $links ) {
		if ( ! is_customize_preview() ) {
			return '';
		}
		$links = array( 'facebook' => '#', 'instagram' => '#', 'whatsapp' => '#' );
		$demo  = true;
	}
	$ic = orego_hb_social_icons();
	$o  = '<div class="ogx2-social' . ( 'circle' === orego_hb_mod( 'social_style' ) ? ' is-circle' : '' ) . ( $demo ? ' is-empty' : '' ) . '">';
	foreach ( $links as $k => $u ) {
		$o .= '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( $ic[ $k ][0] ) . '"><svg viewBox="0 0 24 24" aria-hidden="true">' . $ic[ $k ][1] . '</svg></a>';
	}
	return $o . '</div>';
}

function orego_hb_toggle() {
	return '<button type="button" class="ogx2-burger" aria-controls="ogx2-drawer" aria-expanded="false" aria-label="Open menu"><i></i><i></i><i></i></button>';
}

function orego_hb_item( $k ) {
	switch ( $k ) {
		case 'logo':
			return orego_chrome_logo();
		case 'menu':
			return orego_hb_nav( 1 );
		case 'menu2':
			return orego_hb_nav( 2 );
		case 'search':
			return orego_hb_search();
		case 'lang':
			return orego_hb_lang();
		case 'cur':
			return orego_hb_cur();
		case 'button':
			return orego_hb_button();
		case 'html':
			return orego_hb_html();
		case 'social':
			return orego_hb_social();
		case 'toggle':
			return orego_hb_toggle();
	}
	return '';
}

function orego_hb_header() {
	$lay = orego_hb_layout();
	$o   = '<header class="' . esc_attr( orego_header_classes() . ' ogx2-hb' ) . '">';
	foreach ( array( 'desktop' => 'd', 'mobile' => 'm' ) as $dv => $s ) {
		$o .= '<div class="ogx2-hb-' . $s . '">';
		foreach ( array( 'top', 'main', 'bottom' ) as $r ) {
			$cols = $lay[ $dv ][ $r ];
			if ( 'main' !== $r && ! array_filter( $cols ) ) {
				continue;
			}
			$o .= '<div class="ogx2-hr ogx2-hr-' . $r . '"><div class="ogx2-hri">';
			foreach ( array( 'left' => 'l', 'center' => 'c', 'right' => 'r' ) as $c => $cs ) {
				$o .= '<div class="ogx2-c ogx2-c-' . $cs . '">';
				foreach ( $cols[ $c ] as $k ) {
					$o .= orego_hb_item( $k );
				}
				$o .= '</div>';
			}
			$o .= '</div></div>';
		}
		$o .= '</div>';
	}
	return $o . '</header>';
}

/* ------------------------------------------------------------------ */
/* CSS                                                                 */
/* ------------------------------------------------------------------ */

function orego_hb_static_css() {
	return <<<'CSS'
.ogx2-hb .ogx2-hb-m{display:none}
@media (max-width:1024px){.ogx2-hb .ogx2-hb-d{display:none}.ogx2-hb .ogx2-hb-m{display:block}}
.ogx2-hri{display:flex;align-items:center;gap:16px;padding-inline:clamp(16px,4.5vw,72px)}
.ogx2-c{display:flex;align-items:center;gap:14px;min-width:0}
.ogx2-c-l{flex:1 0 auto;justify-content:flex-start}
.ogx2-c-c{flex:0 1 auto;justify-content:center}
.ogx2-c-r{flex:1 0 auto;justify-content:flex-end}
.ogx2-hb-m .ogx2-hri{display:grid;grid-template-columns:1fr auto 1fr;gap:8px}
.ogx2-hb-m .ogx2-c{gap:8px}
.ogx2-hb-m .ogx2-c-l,.ogx2-hb-m .ogx2-c-r{min-width:0}
html body .ogx2-hb .ogx2-c>*{order:0!important;margin-inline-start:0}
html body .ogx2-hb .ogx2-c>.ogx2-links{display:block}
html body .ogx2-hb .ogx2-c>.ogx2-search{display:flex;align-items:center;position:relative;width:min(380px,30vw);margin:0}
html body .ogx2-hb .ogx2-hb-m .ogx2-c>.ogx2-search{width:min(420px,100%)}
html body .ogx2-hb .ogx2-c>.ogx2-burger{display:inline-flex}
html body .ogx2-hb .ogx2-c>.ogx2-dd{display:block}
html body .ogx2-hb .ogx2-c>.ogx2-cta{display:inline-flex;margin-inline-start:0}
.ogx2-glass .ogx2-c .ogx2-cta{background:#FFFFFF;color:var(--hx-ink,#121212)!important;border-radius:8px;height:46px;padding:0 26px;font-size:16px;font-weight:700}
.ogx2-glass .ogx2-c .ogx2-cta:hover{background:#F4F4F5;filter:none}
.ogx2-c-l .ogx2-panel{inset-inline-end:auto;inset-inline-start:0}
.ogx2-hb-d .ogx2-c>.ogx2-logo+.ogx2-links{margin-inline-start:14px}
.ogx2-links2 li>a{font-size:14px!important;opacity:.8}
.ogx2-links.is-empty a{opacity:.6!important;font-style:italic}
.ogx2-html{font-size:14px;line-height:1.45;color:inherit;white-space:nowrap}
.ogx2-html a{color:inherit!important;text-decoration:underline}
.ogx2-html.is-empty,.ogx2-social.is-empty{opacity:.55;font-style:italic}
.ogx2-social{display:flex;align-items:center;gap:4px}
.ogx2-social a{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;color:inherit!important;opacity:.85;text-decoration:none!important;transition:opacity .15s,background .15s}
.ogx2-social a:hover{opacity:1;background:rgba(255,255,255,.12)}
.ogx2-social.is-circle a{background:rgba(255,255,255,.12)}
.ogx2-social svg{width:18px;height:18px;display:block}
.ogx2-hr-top{background:rgba(0,0,0,.22);font-size:14px}
.ogx2-hr-top .ogx2-pill{height:30px}
.ogx2-hr-top .ogx2-globe{width:26px!important;height:26px!important}
CSS;
}

function orego_hb_css() {
	$n    = function ( $k ) {
		return (int) orego_hb_mod( $k );
	};
	$col  = function ( $k ) {
		return sanitize_hex_color( (string) orego_hb_mod( $k ) );
	};
	$lay  = orego_hb_layout();
	$hd   = (int) orego_mod( 'header_height' );
	$hm   = (int) orego_mod( 'header_height_m' );
	$top  = $n( 'hb_top_h' );
	$bot  = $n( 'hb_bottom_h' );
	$css  = '.ogx2-hri{padding-inline:clamp(16px,4.5vw,' . (int) orego_mod( 'header_pad_x' ) . 'px)}';
	if ( 'boxed' === orego_mod( 'header_width' ) ) {
		$css .= '.ogx2-hri{max-width:' . (int) orego_mod( 'header_max' ) . 'px;margin-inline:auto}';
	}
	$css .= '.ogx2-hb-d .ogx2-hr-main .ogx2-hri{height:' . $hd . 'px}.ogx2-hb-m .ogx2-hr-main .ogx2-hri{height:' . $hm . 'px}';
	$css .= '.ogx2-hr-top .ogx2-hri{height:' . $top . 'px}.ogx2-hr-bottom .ogx2-hri{height:' . $bot . 'px}';
	foreach ( array( 'top', 'bottom' ) as $r ) {
		$bg = $col( 'hb_' . $r . '_bg' );
		$fg = $col( 'hb_' . $r . '_fg' );
		if ( $bg || $fg ) {
			$css .= '.ogx2-hr-' . $r . '{' . ( $bg ? 'background:' . $bg . ';' : '' ) . ( $fg ? 'color:' . $fg . ';--ogh-fg:' . $fg : '' ) . '}';
		}
		if ( orego_hb_mod( 'hb_' . $r . '_border' ) ) {
			$css .= '.ogx2-hr-' . $r . '{border-' . ( 'top' === $r ? 'bottom' : 'top' ) . ':1px solid color-mix(in srgb,var(--ogh-fg,#fff) 12%,transparent)}';
		}
	}
	$css .= '.ogx2-html{font-size:' . $n( 'hb_html_size' ) . 'px}.ogx2-social svg{width:' . $n( 'social_size' ) . 'px;height:' . $n( 'social_size' ) . 'px}.ogx2-social a{width:' . ( $n( 'social_size' ) + 16 ) . 'px;height:' . ( $n( 'social_size' ) + 16 ) . 'px}';
	/* total header height, used by the transparent header and sticky elements */
	$tot = function ( $dv, $main ) use ( $lay, $top, $bot ) {
		return $main + ( array_filter( $lay[ $dv ]['top'] ) ? $top + 1 : 0 ) + ( array_filter( $lay[ $dv ]['bottom'] ) ? $bot + 1 : 0 );
	};
	$st   = orego_mod( 'header_sticky' );
	$td   = $tot( 'desktop', $hd );
	$tm   = $tot( 'mobile', $hm );
	$css .= ':root{--ogh-hh:' . $td . 'px;--ogh-h:' . ( $st ? $td : 0 ) . 'px}@media (max-width:1024px){:root{--ogh-hh:' . $tm . 'px;--ogh-h:' . ( $st ? $tm : 0 ) . 'px}}';
	return $css;
}

add_action( 'wp_head', function () {
	echo '<style id="orego-hb-static">' . orego_hb_static_css() . '</style><style id="orego-hb-css">' . orego_hb_css() . '</style>' . "\n"; // phpcs:ignore
}, 42 );

add_action( 'after_setup_theme', function () {
	register_nav_menus( array( 'orego_secondary' => 'Header secondary menu' ) );
}, 20 );

/* ------------------------------------------------------------------ */
/* Customizer                                                          */
/* ------------------------------------------------------------------ */

add_action( 'customize_register', function ( $wp ) {
	$L = 'orego_L';
	$d = orego_hb_defaults();

	if ( ! class_exists( 'OreGO_HB_Control' ) ) {
		class OreGO_HB_Control extends WP_Customize_Control {
			public $type = 'orego_hb';
			public function render_content() {
				$L = 'orego_L';
				echo '<div class="oghb-side">'
					. '<div class="oghb-stabs"><button type="button" class="is-on" data-t="general">' . esc_html( $L( 'General', 'عام' ) ) . '</button><button type="button" data-t="design">' . esc_html( $L( 'Design', 'التصميم' ) ) . '</button></div>'
					. '<div class="oghb-pane" data-t="general"><div class="oghb-inuse"></div>'
					. '<h4 class="oghb-h">' . esc_html( $L( 'Available Items', 'العناصر المتاحة' ) ) . '</h4><p class="oghb-hint">' . esc_html( $L( 'Drag an item into the builder below the preview.', 'اسحب عنصرًا إلى أداة البناء أسفل المعاينة.' ) ) . '</p><div class="oghb-avail"></div>'
					. '<hr><button type="button" class="oghb-link" data-sec="ogh_transparent">' . esc_html( $L( 'Transparent Header', 'الهيدر الشفاف' ) ) . '</button>'
					. '<hr><button type="button" class="oghb-link" data-sec="ogh_sticky">' . esc_html( $L( 'Sticky Header', 'الهيدر الثابت' ) ) . '</button></div>'
					. '<div class="oghb-pane" data-t="design" hidden>'
					. '<button type="button" class="oghb-link" data-sec="ogh_layout">' . esc_html( $L( 'Colors & width', 'الألوان والعرض' ) ) . '</button>'
					. '<button type="button" class="oghb-link" data-sec="ogh_row_top">' . esc_html( $L( 'Top row', 'الصف العلوي' ) ) . '</button>'
					. '<button type="button" class="oghb-link" data-sec="ogh_row_main">' . esc_html( $L( 'Main row', 'الصف الرئيسي' ) ) . '</button>'
					. '<button type="button" class="oghb-link" data-sec="ogh_row_bottom">' . esc_html( $L( 'Bottom row', 'الصف السفلي' ) ) . '</button>'
					. '<button type="button" class="oghb-link" data-sec="orego_promo">' . esc_html( $L( 'Promo bar', 'شريط العروض' ) ) . '</button>'
					. '</div></div>';
			}
		}
	}

	/* the builder section (root level) */
	$wp->add_section( 'ogh_builder', array( 'title' => $L( 'Header', 'الهيدر' ), 'priority' => 24 ) );
	$wp->add_setting( 'orego_hb_layout', array( 'default' => wp_json_encode( orego_hb_default_layout() ), 'sanitize_callback' => 'orego_hb_sanitize', 'transport' => 'refresh' ) );
	$wp->add_control( new OreGO_HB_Control( $wp, 'orego_hb_layout', array( 'section' => 'ogh_builder', 'settings' => 'orego_hb_layout' ) ) );

	/* sub-sections (opened from the builder; hidden from the main list) */
	$titles = array(
		'ogh_logo'        => $L( 'Logo', 'الشعار' ),
		'ogh_menu'        => $L( 'Primary Navigation', 'القائمة الرئيسية' ),
		'ogh_menu2'       => $L( 'Secondary Navigation', 'القائمة الثانوية' ),
		'ogh_search'      => $L( 'Search', 'البحث' ),
		'ogh_lang'        => $L( 'Language', 'اللغة' ),
		'ogh_cur'         => $L( 'Currency', 'العملة' ),
		'ogh_btn'         => $L( 'Button', 'الزر' ),
		'ogh_html'        => 'HTML',
		'ogh_social'      => $L( 'Social', 'التواصل الاجتماعي' ),
		'orego_drawer'    => $L( 'Menu Toggle & mobile menu', 'زر القائمة وقائمة الجوال' ),
		'ogh_transparent' => $L( 'Transparent Header', 'الهيدر الشفاف' ),
		'ogh_sticky'      => $L( 'Sticky Header', 'الهيدر الثابت' ),
		'ogh_layout'      => $L( 'Colors & width', 'الألوان والعرض' ),
		'ogh_row_top'     => $L( 'Top row', 'الصف العلوي' ),
		'ogh_row_main'    => $L( 'Main row', 'الصف الرئيسي' ),
		'ogh_row_bottom'  => $L( 'Bottom row', 'الصف السفلي' ),
		'orego_promo'     => $L( 'Promo bar', 'شريط العروض' ),
	);
	foreach ( $titles as $id => $t ) {
		$s = $wp->get_section( $id );
		if ( ! $s ) {
			$wp->add_section( $id, array( 'title' => $t, 'priority' => 200 ) );
		} else {
			$s->title    = $t;
			$s->panel    = '';
			$s->priority = 200;
		}
	}
	$wp->remove_panel( 'ogh_header' );

	/* builder replaces these on/off switches */
	foreach ( array( 'show_nav', 'show_lang', 'show_currency', 'header_search', 'cta_show', 'lang_show_d', 'lang_show_m', 'nav_align', 'lang_pos' ) as $k ) {
		$wp->remove_control( 'orego_' . $k );
	}
	$move = array(
		'header_overlay'  => array( 'ogh_transparent', 10 ),
		'header_sticky'   => array( 'ogh_sticky', 10 ),
		'header_height'   => array( 'ogh_row_main', 10 ),
		'header_height_m' => array( 'ogh_row_main', 11 ),
	);
	foreach ( $move as $k => $m ) {
		if ( $c = $wp->get_control( 'orego_' . $k ) ) {
			$c->section  = $m[0];
			$c->priority = $m[1];
		}
	}
	if ( $c = $wp->get_control( 'orego_header_overlay' ) ) {
		$c->label = $L( 'Transparent over the homepage photo (Clean style)', 'شفاف فوق صورة الصفحة الرئيسية (الشكل النظيف)' );
	}

	/* new settings */
	$add = function ( $key, $section, $label, $type, $extra = array(), $transport = 'postMessage' ) use ( $wp, $d ) {
		$san = array(
			'color'    => 'sanitize_hex_color',
			'checkbox' => function ( $v ) {
				return (bool) $v;
			},
			'url'      => 'esc_url_raw',
			'text'     => 'sanitize_text_field',
			'textarea' => 'wp_kses_post',
			'number'   => 'absint',
			'select'   => 'sanitize_key',
			'menu'     => 'absint',
		);
		$wp->add_setting( 'orego_' . $key, array( 'default' => $d[ $key ], 'sanitize_callback' => $san[ $type ], 'transport' => $transport ) );
		$args = array_merge( array( 'label' => $label, 'section' => $section, 'settings' => 'orego_' . $key, 'priority' => 20 ), $extra );
		if ( 'color' === $type ) {
			$wp->add_control( new WP_Customize_Color_Control( $wp, 'orego_' . $key, $args ) );
		} else {
			$wp->add_control( 'orego_' . $key, array_merge( $args, array( 'type' => 'menu' === $type ? 'select' : $type ) ) );
		}
	};
	$px    = function ( $min, $max ) {
		return array( 'input_attrs' => array( 'min' => $min, 'max' => $max, 'step' => 1 ) );
	};
	$menus = array();
	foreach ( wp_get_nav_menus() as $m ) {
		$menus[ $m->term_id ] = $m->name;
	}
	$menu_desc = array( 'description' => $L( 'Create and edit menus in Appearance → Menus.', 'أنشئ القوائم وعدّلها من المظهر ← القوائم.' ) );

	$add( 'hb_menu1', 'ogh_menu', $L( 'Menu to show', 'القائمة المعروضة' ), 'menu', array( 'choices' => array( 0 => $L( 'Header menu (Main Menu)', 'قائمة الهيدر (Main Menu)' ) ) + $menus, 'priority' => 5 ) + $menu_desc );
	$add( 'hb_menu2', 'ogh_menu2', $L( 'Menu to show', 'القائمة المعروضة' ), 'menu', array( 'choices' => array( 0 => $L( '— Choose a menu —', '— اختر قائمة —' ) ) + $menus, 'priority' => 5 ) + $menu_desc );
	foreach ( array( 'top' => $L( 'Top row', 'الصف العلوي' ), 'bottom' => $L( 'Bottom row', 'الصف السفلي' ) ) as $r => $rl ) {
		$add( 'hb_' . $r . '_h', 'ogh_row_' . $r, $L( 'Height', 'الارتفاع' ), 'number', $px( 24, 140 ) );
		$add( 'hb_' . $r . '_bg', 'ogh_row_' . $r, $L( 'Background color', 'لون الخلفية' ), 'color', array( 'description' => $L( 'Empty = slightly darker than the header.', 'فارغ = أغمق قليلًا من الهيدر.' ) ) );
		$add( 'hb_' . $r . '_fg', 'ogh_row_' . $r, $L( 'Text color', 'لون النص' ), 'color' );
		$add( 'hb_' . $r . '_border', 'ogh_row_' . $r, $L( 'Thin dividing line', 'خط فاصل رفيع' ), 'checkbox' );
	}
	$add( 'hb_html', 'ogh_html', $L( 'Text or HTML', 'نص أو HTML' ), 'textarea', array( 'description' => $L( 'For example: Call us +212 600 000 000 · Free delivery. Links are allowed. {year} = current year.', 'مثال: اتصل بنا ‎+212 600 000 000 · توصيل مجاني. الروابط مسموحة.' ) ) );
	$add( 'hb_html_size', 'ogh_html', $L( 'Text size', 'حجم النص' ), 'number', $px( 10, 24 ) );
	foreach ( orego_hb_social_icons() as $k => $ic ) {
		$add( 'social_' . $k, 'ogh_social', $ic[0], 'whatsapp' === $k ? 'text' : 'url', 'whatsapp' === $k ? array( 'description' => $L( 'Phone number (with country code) or a link.', 'رقم الهاتف (مع رمز الدولة) أو رابط.' ) ) : array( 'input_attrs' => array( 'placeholder' => 'https://' ) ) );
	}
	$add( 'social_style', 'ogh_social', $L( 'Icon style', 'شكل الأيقونات' ), 'select', array( 'choices' => array( 'plain' => $L( 'Plain', 'بسيط' ), 'circle' => $L( 'In a circle', 'داخل دائرة' ) ), 'priority' => 30 ) );
	$add( 'social_size', 'ogh_social', $L( 'Icon size', 'حجم الأيقونات' ), 'number', $px( 12, 32 ) + array( 'priority' => 31 ) );

	/* hidden "focus" settings so every item has a pencil shortcut in the preview */
	foreach ( array( 'lang' => 'ogh_lang', 'cur' => 'ogh_cur', 'toggle' => 'orego_drawer' ) as $k => $sec ) {
		$wp->add_setting( 'oghb_f_' . $k, array( 'default' => '', 'transport' => 'postMessage', 'sanitize_callback' => '__return_empty_string' ) );
		$wp->add_control( 'oghb_f_' . $k, array( 'section' => $sec, 'type' => 'hidden', 'priority' => 1 ) );
	}

	/* live preview */
	$css_keys = array( 'hb_top_h', 'hb_top_bg', 'hb_top_fg', 'hb_top_border', 'hb_bottom_h', 'hb_bottom_bg', 'hb_bottom_fg', 'hb_bottom_border', 'hb_html_size', 'social_size', 'header_height', 'header_height_m', 'header_pad_x', 'header_width', 'header_max' );
	if ( isset( $wp->selective_refresh ) ) {
		foreach ( array( 'ogh_logo_p', 'ogh_menu_p', 'ogh_lang_p', 'ogh_cur_p', 'ogh_search_p', 'ogh_btn_p' ) as $old ) {
			$wp->selective_refresh->remove_partial( $old );
		}
		$o = function ( $a ) {
			return array_map( function ( $k ) {
				return 0 === strpos( $k, 'oghb_' ) ? $k : 'orego_' . $k;
			}, $a );
		};
		$wp->selective_refresh->add_partial( 'oghb_css', array(
			'selector'            => '#orego-hb-css',
			'settings'            => $o( $css_keys ),
			'container_inclusive' => false,
			'fallback_refresh'    => false,
			'render_callback'     => 'orego_hb_css',
		) );
		$parts = array(
			'oghb_logo'   => array( '.ogx2-c > .ogx2-logo', array( 'logo_text', 'logo_image', 'logo_tagline', 'logo_icon' ), 'orego_chrome_logo' ),
			'oghb_menu'   => array( '.ogx2-c > .ogx2-links1', array( 'hb_menu1' ), function () {
				return orego_hb_nav( 1 );
			} ),
			'oghb_menu2'  => array( '.ogx2-c > .ogx2-links2', array( 'hb_menu2' ), function () {
				return orego_hb_nav( 2 );
			} ),
			'oghb_search' => array( '.ogx2-c > .ogx2-search', array( 'search_placeholder' ), 'orego_hb_search' ),
			'oghb_lang'   => array( '.ogx2-c > .ogx2-dd[data-dd="lang"]', array( 'oghb_f_lang' ), null ),
			'oghb_cur'    => array( '.ogx2-c > .ogx2-dd[data-dd="cur"]', array( 'oghb_f_cur' ), null ),
			'oghb_btn'    => array( '.ogx2-c > .ogx2-cta', array( 'cta_text', 'cta_url', 'cta_new_tab' ), 'orego_hb_button' ),
			'oghb_html'   => array( '.ogx2-c > .ogx2-html', array( 'hb_html' ), 'orego_hb_html' ),
			'oghb_social' => array( '.ogx2-c > .ogx2-social', array( 'social_facebook', 'social_instagram', 'social_whatsapp', 'social_tiktok', 'social_x', 'social_youtube', 'social_style' ), 'orego_hb_social' ),
			'oghb_toggle' => array( '.ogx2-c > .ogx2-burger', array( 'oghb_f_toggle' ), null ),
		);
		foreach ( $parts as $id => $pp ) {
			foreach ( $o( $pp[1] ) as $sid ) {
				if ( $s = $wp->get_setting( $sid ) ) {
					$s->transport = 'postMessage';
				}
			}
			$cb = $pp[2];
			$wp->selective_refresh->add_partial( $id, array(
				'selector'            => $pp[0],
				'settings'            => $o( $pp[1] ),
				'container_inclusive' => true,
				'fallback_refresh'    => true,
				'render_callback'     => function () use ( $cb ) {
					return $cb ? call_user_func( $cb ) : false;
				},
			) );
		}
		foreach ( $o( $css_keys ) as $sid ) {
			if ( $s = $wp->get_setting( $sid ) ) {
				$s->transport = 'postMessage';
			}
		}
	}
}, 40 );

/* Builder UI in the Customizer */
add_action( 'customize_controls_enqueue_scripts', function () {
	wp_enqueue_script( 'jquery-ui-sortable' );
	$items = array();
	$secs  = array();
	foreach ( orego_hb_items() as $k => $it ) {
		$items[ $k ] = $it[0];
		$secs[ $k ]  = $it[1];
	}
	$cfg = array(
		'items'   => $items,
		'sec'     => $secs,
		'rowsec'  => array( 'top' => 'ogh_row_top', 'main' => 'ogh_row_main', 'bottom' => 'ogh_row_bottom' ),
		'rowl'    => array( 'top' => orego_L( 'Top row', 'الصف العلوي' ), 'main' => orego_L( 'Main row', 'الصف الرئيسي' ), 'bottom' => orego_L( 'Bottom row', 'الصف السفلي' ) ),
		'subs'    => array( 'ogh_logo', 'ogh_menu', 'ogh_menu2', 'ogh_search', 'ogh_lang', 'ogh_cur', 'ogh_btn', 'ogh_html', 'ogh_social', 'orego_drawer', 'ogh_transparent', 'ogh_sticky', 'ogh_layout', 'ogh_row_top', 'ogh_row_main', 'ogh_row_bottom', 'orego_promo' ),
		'def'     => orego_hb_default_layout(),
		't'       => array(
			'desktop' => orego_L( 'Desktop', 'الكمبيوتر' ),
			'mobile'  => orego_L( 'Tablet / Mobile', 'اللوحي / الجوال' ),
			'hide'    => orego_L( 'Hide', 'إخفاء' ),
			'show'    => orego_L( 'Header builder', 'أداة بناء الهيدر' ),
			'set'     => orego_L( 'Settings', 'الإعدادات' ),
			'rm'      => orego_L( 'Remove', 'إزالة' ),
			'none'    => orego_L( 'All items are in use on this device.', 'كل العناصر مستخدمة على هذا الجهاز.' ),
			'crumb'   => orego_L( 'Customizing ▸ Header', 'تخصيص ▸ الهيدر' ),
			'notog'   => orego_L( 'Tip: add the Menu Toggle so visitors can open the menu on phones.', 'نصيحة: أضف زر القائمة ليتمكن الزوار من فتح القائمة على الجوال.' ),
		),
	);
	wp_add_inline_script( 'customize-controls', 'window.OGHB=' . wp_json_encode( $cfg ) . ';' . orego_hb_controls_js() );
	wp_register_style( 'orego-hb-controls', false, array(), '1.0' );
	wp_enqueue_style( 'orego-hb-controls' );
	wp_add_inline_style( 'orego-hb-controls', orego_hb_controls_css( $cfg['subs'] ) );
} );

function orego_hb_controls_css( $subs ) {
	$hide = implode( ',', array_map( function ( $s ) {
		return '#accordion-section-' . $s;
	}, $subs ) );
	return $hide . '{display:none!important}' . <<<'CSS'
#customize-control-orego_hb_layout{margin:0}
.oghb-stabs{display:flex;margin:-12px -12px 16px;border-bottom:1px solid #dcdcde;background:#fff}
.oghb-stabs button{flex:1;padding:14px 8px;border:0;border-bottom:3px solid transparent;background:none;font-weight:600;font-size:13px;letter-spacing:.04em;text-transform:uppercase;color:#50575e;cursor:pointer}
.oghb-stabs button.is-on{color:#1d2327;border-bottom-color:#2271b1;background:#f6f7f7}
.oghb-link{display:flex;align-items:center;justify-content:space-between;width:100%;padding:14px 16px;margin:0 0 10px;border:1px solid #e2e4e7;border-radius:6px;background:#fff;font-size:14px;font-weight:600;color:#1d2327;cursor:pointer;text-align:start}
.oghb-link::after{content:"";width:8px;height:8px;border-right:2px solid #8c8f94;border-top:2px solid #8c8f94;transform:rotate(45deg)}
html[dir=rtl] .oghb-link::after{transform:rotate(-135deg)}
.oghb-link:hover{border-color:#2271b1;color:#2271b1}
.oghb-h{margin:20px 0 4px;font-size:14px}
.oghb-hint{margin:0 0 10px;color:#646970;font-size:12px}
.oghb-avail{min-height:56px;padding:12px;border:1px dashed #a7aaad;border-radius:4px;background:#f6f7f7}
.oghb-avail .oghb-it{width:100%;margin:0 0 8px;border-left:3px solid #2271b1}
.oghb-avail .oghb-gear,.oghb-avail .oghb-x{display:none}
.oghb-empty{margin:0;color:#646970;font-size:12px;font-style:italic}
.oghb-side hr{margin:18px 0;border:0;border-top:1px solid #dcdcde}
.oghb-it{display:inline-flex;align-items:center;gap:4px;height:34px;padding:0 4px 0 8px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;font-size:13px;color:#1d2327;cursor:grab;box-sizing:border-box;white-space:nowrap;user-select:none}
.oghb-it:hover{border-color:#2271b1}
.oghb-grip{color:#8c8f94;letter-spacing:-2px;font-size:14px}
.oghb-lbl{flex:1;font-weight:500}
.oghb-gear,.oghb-x{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;padding:0;border:0;border-radius:3px;background:none;color:#646970;cursor:pointer}
.oghb-gear:hover,.oghb-x:hover{background:#f0f0f1;color:#2271b1}
.oghb-x:hover{color:#d63638}
.oghb-ph{display:inline-block;width:110px;height:38px;border:1px dashed #2271b1;border-radius:4px;background:#f0f6fc;vertical-align:middle}
.oghb-avail .oghb-ph{display:block;width:100%;margin:0 0 8px}
#oghb-bar{position:absolute;left:0;right:0;bottom:0;z-index:20;height:var(--oghb-h,252px);background:#f0f0f1;border-top:1px solid #c3c4c7;box-shadow:0 -4px 16px rgba(0,0,0,.08);display:none;flex-direction:column;font-size:13px}
body.oghb-on #oghb-bar{display:flex}
body.oghb-on.oghb-min #oghb-bar{display:none}
body.oghb-on:not(.oghb-min) .wp-full-overlay-main{bottom:var(--oghb-h,252px)}
#oghb-show{position:absolute;right:16px;bottom:16px;z-index:21;display:none;padding:9px 14px;border:0;border-radius:4px;background:#2271b1;color:#fff;font-weight:600;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.2)}
body.oghb-on.oghb-min #oghb-show{display:block}
.oghb-top{display:flex;align-items:flex-end;justify-content:space-between;padding:10px 16px 0;border-bottom:1px solid #dcdcde}
.oghb-tabs{display:flex;gap:6px}
.oghb-tabs button{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border:0;border-bottom:3px solid transparent;background:#e0e0e0;font-weight:600;letter-spacing:.03em;text-transform:uppercase;font-size:12px;color:#50575e;cursor:pointer}
.oghb-tabs button.is-on{background:#fff;color:#1d2327;border-bottom-color:#2271b1}
.oghb-hide{margin-bottom:8px;padding:7px 14px;border:1px solid #2271b1;border-radius:3px;background:#fff;color:#2271b1;font-style:italic;font-weight:600;cursor:pointer}
.oghb-rows{flex:1;overflow:auto;padding:12px 16px;display:flex;flex-direction:column;gap:8px}
.oghb-row{display:flex;align-items:stretch;min-height:50px;flex:none}
.oghb-rowgear{flex:none;width:32px;border:0;border-radius:3px 0 0 3px;background:#2271b1;color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0}
.oghb-rowgear:hover{background:#135e96}
.oghb-col{display:flex;align-items:center;align-content:center;gap:6px;padding:6px 8px;border:1px dashed #a7aaad;border-left:0;background:#fafafa;min-width:0;flex-wrap:wrap}
.oghb-left{flex:1 1 0;justify-content:flex-start}
.oghb-center{flex:0 0 22%;justify-content:center}
.oghb-right{flex:1 1 0;justify-content:flex-end}
.oghb-col.ui-sortable-over,.oghb-avail.ui-sortable-over{background:#f0f6fc}
.oghb-warn{margin:0 16px 8px;color:#996800;font-size:12px}
.ui-sortable-helper.oghb-it{box-shadow:0 6px 18px rgba(0,0,0,.2);cursor:grabbing;z-index:600000!important}
CSS;
}

function orego_hb_controls_js() {
	return <<<'JS'
(function($,api){
var D=window.OGHB,dev='desktop',tab='general',min=false,$bar,$show;
var gear='<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>';
var R=['top','main','bottom'],C=['left','center','right'];
function esc(s){return String(s).replace(/[&<>"]/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]})}
function lay(){var l;try{l=JSON.parse(api('orego_hb_layout').get())}catch(e){}if(!l||!l.desktop||!l.mobile)l=JSON.parse(JSON.stringify(D.def));R.forEach(function(r){['desktop','mobile'].forEach(function(dv){l[dv][r]=l[dv][r]||{};C.forEach(function(c){l[dv][r][c]=l[dv][r][c]||[]})})});return l}
function used(l,dv){var u=[];R.forEach(function(r){C.forEach(function(c){u=u.concat(l[dv][r][c])})});return u}
function item(k){return '<div class="oghb-it" data-k="'+k+'"><span class="oghb-grip">⠿</span><span class="oghb-lbl">'+esc(D.items[k])+'</span><button type="button" class="oghb-gear" title="'+esc(D.t.set)+'">'+gear+'</button><button type="button" class="oghb-x" title="'+esc(D.t.rm)+'">✕</button></div>'}
function save(l){api('orego_hb_layout').set(JSON.stringify(l))}
function collect(){var l=lay(),d={};$bar.find('.oghb-row').each(function(){var r=$(this).data('r');d[r]={};$(this).find('.oghb-col').each(function(){d[r][$(this).data('c')]=$(this).children('.oghb-it').map(function(){return $(this).data('k')}).get()})});l[dev]=d;return l}
function render(){
 if(!$bar)return;var l=lay(),d=l[dev],h='';
 R.forEach(function(r){h+='<div class="oghb-row" data-r="'+r+'"><button type="button" class="oghb-rowgear" data-r="'+r+'" title="'+esc(D.rowl[r])+'">'+gear+'</button>';C.forEach(function(c){h+='<div class="oghb-col oghb-'+c+'" data-c="'+c+'">'+d[r][c].map(item).join('')+'</div>'});h+='</div>'});
 $bar.find('.oghb-rows').html(h);
 $bar.find('.oghb-tabs button').each(function(){$(this).toggleClass('is-on',$(this).data('dev')===dev)});
 $bar.find('.oghb-warn').remove();if(dev==='mobile'&&used(l,'mobile').indexOf('toggle')<0)$bar.find('.oghb-top').after('<p class="oghb-warn">'+esc(D.t.notog)+'</p>');
 side();sort();fit();
}
function fit(){if(!$bar)return;var h=$bar.find('.oghb-top').outerHeight()+($bar.find('.oghb-warn').outerHeight(true)||0)+24;$bar.find('.oghb-row').each(function(){h+=$(this).outerHeight()+8});h=Math.max(220,Math.min(h,Math.round(window.innerHeight*.55)));document.documentElement.style.setProperty('--oghb-h',h+'px')}
function side(){
 var $s=$('.oghb-side');if(!$s.length)return;var l=lay(),all=[];used(l,'desktop').concat(used(l,'mobile')).forEach(function(k){if(all.indexOf(k)<0)all.push(k)});
 $s.find('.oghb-inuse').html(all.map(function(k){return '<button type="button" class="oghb-link" data-sec="'+D.sec[k]+'">'+esc(D.items[k])+'</button>'}).join(''));
 var u=used(l,dev),av=Object.keys(D.items).filter(function(k){return u.indexOf(k)<0});
 $s.find('.oghb-avail').html(av.length?av.map(item).join(''):'<p class="oghb-empty">'+esc(D.t.none)+'</p>');
 $s.find('.oghb-stabs button').each(function(){$(this).toggleClass('is-on',$(this).data('t')===tab)});$s.find('.oghb-pane').each(function(){this.hidden=$(this).data('t')!==tab});
}
function sort(){
 var $z=$('.oghb-col,.oghb-avail');try{$z.sortable('destroy')}catch(e){}
 $z.sortable({connectWith:'.oghb-col,.oghb-avail',items:'> .oghb-it',placeholder:'oghb-ph',tolerance:'pointer',helper:'clone',appendTo:document.body,zIndex:600000,cancel:'button',
  stop:function(){save(collect());setTimeout(render,0)}});
}
function setDev(v,fromPreview){dev=v;if(!fromPreview){var cur=api.previewedDevice.get();if(v==='desktop'&&cur!=='desktop')api.previewedDevice.set('desktop');if(v==='mobile'&&cur==='desktop')api.previewedDevice.set('mobile')}render()}
function anyOpen(){var o=false;['ogh_builder'].concat(D.subs).forEach(function(id){var s=api.section(id);if(s&&s.expanded())o=true});return o}
function vis(){$('body').toggleClass('oghb-on',anyOpen()).toggleClass('oghb-min',min);setTimeout(fit,0)}
function go(id){var s=api.section(id);if(s)s.focus()}
api.bind('ready',function(){
 $bar=$('<div id="oghb-bar"><div class="oghb-top"><div class="oghb-tabs"><button type="button" data-dev="desktop"><span class="dashicons dashicons-desktop"></span>'+esc(D.t.desktop)+'</button><button type="button" data-dev="mobile"><span class="dashicons dashicons-smartphone"></span>'+esc(D.t.mobile)+'</button></div><button type="button" class="oghb-hide">✕ '+esc(D.t.hide)+'</button></div><div class="oghb-rows"></div></div>').appendTo('.wp-full-overlay');
 $show=$('<button type="button" id="oghb-show">'+esc(D.t.show)+'</button>').appendTo('.wp-full-overlay');
 $bar.on('click','.oghb-tabs button',function(){setDev($(this).data('dev'))});
 $bar.on('click','.oghb-hide',function(){min=true;vis()});$show.on('click',function(){min=false;vis()});
 $bar.on('click','.oghb-rowgear',function(){go(D.rowsec[$(this).data('r')])});
 $(document).on('click','.oghb-it .oghb-gear',function(e){e.stopPropagation();go(D.sec[$(this).closest('.oghb-it').data('k')])});
 $(document).on('click','#oghb-bar .oghb-it .oghb-x',function(e){e.stopPropagation();$(this).closest('.oghb-it').remove();save(collect());render()});
 $(document).on('click','.oghb-side .oghb-link',function(){go($(this).data('sec'))});
 $(document).on('click','.oghb-stabs button',function(){tab=$(this).data('t');side()});
 api.previewedDevice.bind(function(v){var nd=v==='desktop'?'desktop':'mobile';if(nd!==dev)setDev(nd,true)});
 dev=api.previewedDevice.get()==='desktop'?'desktop':'mobile';
 ['ogh_builder'].concat(D.subs).forEach(function(id){api.section(id,function(s){s.expanded.bind(function(){setTimeout(vis,0)});
  if(id!=='ogh_builder'){s.contentContainer.find('.customize-action').text(D.t.crumb);
   s.contentContainer.on('click keydown','.customize-section-back',function(e){if(e.type==='keydown'&&e.which!==13)return;setTimeout(function(){go('ogh_builder')},0)})}})});
 api.control('orego_hb_layout',function(c){c.deferred.embedded.done(function(){render()})});
 api('orego_hb_layout').bind(function(){if(!$('.ui-sortable-helper').length)render()});
 render();vis();
});
})(jQuery,wp.customize);
JS;
}
