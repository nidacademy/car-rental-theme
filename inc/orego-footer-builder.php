<?php
/**
 * Exotic Cars footer builder (twin of the header builder).
 * Customize -> Footer: drag items into 3 rows x 3 columns, separately for Desktop and Tablet / Mobile.
 * Each item and row has its own settings section, opened from the gear icons.
 * The footer shortcode [orego_footer] is drawn from this layout.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_fb_items() {
	return array(
		'logo'      => array( 'Logo', 'ogf_logo' ),
		'menu'      => array( 'Footer menu', 'ogf_menu' ),
		'menu2'     => array( 'Second menu', 'ogf_menu2' ),
		'copyright' => array( 'Copyright', 'ogf_copy' ),
		'html'      => array( 'Text / HTML', 'ogf_html' ),
		'social'    => array( 'Social icons', 'ogf_social' ),
		'button'    => array( 'Button', 'ogf_btn' ),
		'contact'   => array( 'Contact info', 'ogf_contact' ),
	);
}

function orego_fb_empty_dev() {
	$e = array();
	foreach ( array( 'top', 'main', 'bottom' ) as $r ) {
		$e[ $r ] = array( 'left' => array(), 'center' => array(), 'right' => array() );
	}
	return $e;
}

/** Default = the footer as it looks today. */
function orego_fb_default_layout() {
	$d                     = orego_fb_empty_dev();
	$m                     = orego_fb_empty_dev();
	$d['main']['left']     = array( 'logo' );
	$d['main']['center']   = array( 'menu' );
	$d['main']['right']    = array( 'copyright' );
	$m['main']['center']   = array( 'logo', 'menu', 'copyright' );
	return array( 'desktop' => $d, 'mobile' => $m );
}

function orego_fb_sanitize( $v ) {
	$d = json_decode( is_string( $v ) ? $v : '', true );
	if ( ! is_array( $d ) ) {
		return wp_json_encode( orego_fb_default_layout() );
	}
	$items = array_keys( orego_fb_items() );
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

function orego_fb_layout() {
	$raw = get_theme_mod( 'orego_fb_layout', '' );
	return json_decode( orego_fb_sanitize( $raw ? $raw : wp_json_encode( orego_fb_default_layout() ) ), true );
}

function orego_fb_defaults() {
	$copy = function_exists( 'orego_mod' ) ? (string) orego_mod( 'copyright' ) : '';
	return array(
		// colours & width
		'fb_bg'          => '#FFFFFF',
		'fb_fg'          => '#15171C',
		'fb_link'        => '#6B7280',
		'fb_link_hover'  => '#15171C',
		'fb_muted'       => '#8A919C',
		'fb_width'       => 'full',
		'fb_max'         => 1240,
		'fb_pad_x'       => 72,
		'fb_border'      => true,
		'fb_shadow'      => true,
		'fb_radius'      => 0,
		'fb_margin'      => 0,
		// rows
		'fb_top_h'       => 48,
		'fb_top_bg'      => '',
		'fb_top_fg'      => '',
		'fb_top_border'  => true,
		'fb_main_h'      => 88,
		'fb_bottom_h'    => 52,
		'fb_bottom_bg'   => '',
		'fb_bottom_fg'   => '',
		'fb_bottom_border' => true,
		// items
		'fb_logo'        => orego_aid( 'logo' ),
		'fb_logo_h'      => 42,
		'fb_menu1'       => 0,
		'fb_menu2'       => 0,
		'fb_link_size'   => 15,
		'fb_link_weight' => '500',
		'fb_link_gap'    => 32,
		'fb_copy'        => '' !== trim( $copy ) ? $copy : '© {year} ' . orego_brand() . '. All rights reserved.',
		'fb_copy_size'   => 14,
		'fb_html'        => '',
		'fb_html_size'   => 14,
		'fb_social_style' => 'plain',
		'fb_social_size' => 18,
		'fb_btn_text'    => 'Book now',
		'fb_btn_url'     => '',
		'fb_btn_bg'      => '#15171C',
		'fb_btn_fg'      => '#FFFFFF',
		'fb_btn_radius'  => 10,
		'fb_phone'       => '',
		'fb_email'       => '',
		'fb_address'     => '',
	);
}

function orego_fb_mod( $k ) {
	$d = orego_fb_defaults();
	return get_theme_mod( 'orego_' . $k, $d[ $k ] ?? '' );
}

/* ------------------------------------------------------------------ */
/* Items                                                               */
/* ------------------------------------------------------------------ */

function orego_fb_preview_note( $txt ) {
	return is_customize_preview() ? '<span class="ogfb-empty">' . esc_html( $txt ) . '</span>' : '';
}

function orego_fb_logo() {
	$id  = absint( orego_fb_mod( 'fb_logo' ) );
	$h   = max( 16, (int) orego_fb_mod( 'fb_logo_h' ) );
	$src = $id ? wp_get_attachment_image_src( $id, 'medium' ) : false;
	$img = '';
	if ( $src ) {
		$w   = ( ! empty( $src[1] ) && ! empty( $src[2] ) ) ? (int) round( $h * $src[1] / $src[2] ) : 0;
		$img = '<img class="ogfb-logoimg" src="' . esc_url( $src[0] ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" height="' . $h . '"' . ( $w ? ' width="' . $w . '"' : '' ) . ' style="height:' . $h . 'px;width:' . ( $w ? $w . 'px' : 'auto' ) . ';max-width:none" decoding="async">';
	}
	if ( ! $img ) {
		$img = '<span class="ogfb-logotxt">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
	}
	return '<a class="ogfb-logo notranslate" href="' . esc_url( home_url( '/' ) ) . '">' . $img . '</a>';
}

function orego_fb_nav( $which = 1 ) {
	$id = (int) orego_fb_mod( 1 === $which ? 'fb_menu1' : 'fb_menu2' );
	if ( 1 === $which && ! $id ) {
		$tree = function_exists( 'orego_menu_tree' ) ? orego_menu_tree( 'orego_footer', 'Footer Menu' ) : array();
	} else {
		$menu = $id ? wp_get_nav_menu_object( $id ) : false;
		$tree = ( $menu && function_exists( 'orego_menu_tree' ) ) ? orego_menu_tree( '__orego_none__', $menu->name ) : array();
	}
	if ( ! $tree ) {
		return orego_fb_preview_note( 'Choose a menu' );
	}
	$o = '<nav class="ogfb-nav ogfb-nav' . (int) $which . '" aria-label="' . ( 1 === $which ? 'Footer menu' : 'Footer menu 2' ) . '"><ul>';
	foreach ( $tree as $it ) {
		$o .= '<li><a href="' . esc_url( $it['url'] ) . '"' . ( $it['current'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $it['title'] ) . '</a></li>';
	}
	return $o . '</ul></nav>';
}

function orego_fb_copy() {
	$t = trim( (string) orego_fb_mod( 'fb_copy' ) );
	if ( '' === $t ) {
		return orego_fb_preview_note( 'Add your copyright text' );
	}
	return '<p class="ogfb-copy">' . wp_kses_post( str_replace( '{year}', wp_date( 'Y' ), $t ) ) . '</p>';
}

function orego_fb_html() {
	$h = trim( (string) orego_fb_mod( 'fb_html' ) );
	if ( '' === $h ) {
		return orego_fb_preview_note( 'Add your text in Footer → Text / HTML' );
	}
	return '<div class="ogfb-html">' . wp_kses_post( do_shortcode( str_replace( '{year}', wp_date( 'Y' ), $h ) ) ) . '</div>';
}

function orego_fb_social() {
	if ( ! function_exists( 'orego_hb_social_icons' ) ) {
		return '';
	}
	$ic    = orego_hb_social_icons();
	$links = array();
	foreach ( $ic as $k => $x ) {
		$u = trim( (string) get_theme_mod( 'orego_social_' . $k, '' ) );
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
	$o = '<div class="ogfb-social' . ( 'circle' === orego_fb_mod( 'fb_social_style' ) ? ' is-circle' : '' ) . ( $demo ? ' is-demo' : '' ) . '">';
	foreach ( $links as $k => $u ) {
		$o .= '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( $ic[ $k ][0] ) . '"><svg viewBox="0 0 24 24" aria-hidden="true">' . $ic[ $k ][1] . '</svg></a>';
	}
	return $o . '</div>';
}

function orego_fb_button() {
	$t = trim( (string) orego_fb_mod( 'fb_btn_text' ) );
	$u = trim( (string) orego_fb_mod( 'fb_btn_url' ) );
	if ( '' === $u ) {
		$u = orego_purl( 'cars' );
	}
	return '<a class="ogfb-btn" href="' . esc_url( $u ) . '">' . esc_html( '' !== $t ? $t : 'Book now' ) . '</a>';
}

function orego_fb_contact() {
	$a    = 'fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"';
	$rows = array();
	$ph   = trim( (string) orego_fb_mod( 'fb_phone' ) );
	$em   = trim( (string) orego_fb_mod( 'fb_email' ) );
	$ad   = trim( (string) orego_fb_mod( 'fb_address' ) );
	if ( '' !== $ph ) {
		$rows[] = '<li><svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $a . ' d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg><a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $ph ) ) . '" dir="ltr">' . esc_html( $ph ) . '</a></li>';
	}
	if ( '' !== $em ) {
		$rows[] = '<li><svg viewBox="0 0 24 24" aria-hidden="true"><rect ' . $a . ' x="3" y="5" width="18" height="14" rx="2"/><path ' . $a . ' d="m3 7 9 6 9-6"/></svg><a href="mailto:' . esc_attr( antispambot( $em ) ) . '">' . esc_html( antispambot( $em ) ) . '</a></li>';
	}
	if ( '' !== $ad ) {
		$rows[] = '<li><svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $a . ' d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21Z"/><circle ' . $a . ' cx="12" cy="9.5" r="2.5"/></svg><span>' . esc_html( $ad ) . '</span></li>';
	}
	if ( ! $rows ) {
		return orego_fb_preview_note( 'Add your phone, email or address' );
	}
	return '<ul class="ogfb-contact">' . implode( '', $rows ) . '</ul>';
}

function orego_fb_item( $k ) {
	switch ( $k ) {
		case 'logo':
			return orego_fb_logo();
		case 'menu':
			return orego_fb_nav( 1 );
		case 'menu2':
			return orego_fb_nav( 2 );
		case 'copyright':
			return orego_fb_copy();
		case 'html':
			return orego_fb_html();
		case 'social':
			return orego_fb_social();
		case 'button':
			return orego_fb_button();
		case 'contact':
			return orego_fb_contact();
	}
	return '';
}

function orego_fb_footer() {
	$lay = orego_fb_layout();
	$o   = '<footer class="ogfb" id="og-contact">';
	foreach ( array( 'desktop' => 'd', 'mobile' => 'm' ) as $dv => $s ) {
		$o .= '<div class="ogfb-' . $s . '">';
		foreach ( array( 'top', 'main', 'bottom' ) as $r ) {
			$cols = $lay[ $dv ][ $r ];
			if ( ! array_filter( $cols ) ) {
				continue;
			}
			$o .= '<div class="ogfb-r ogfb-r-' . $r . '"><div class="ogfb-ri">';
			foreach ( array( 'left' => 'l', 'center' => 'c', 'right' => 'r' ) as $c => $cs ) {
				$inner = '';
				foreach ( $cols[ $c ] as $k ) {
					$inner .= orego_fb_item( $k );
				}
				$o .= '<div class="ogfb-c ogfb-c-' . $cs . ( '' === $inner ? ' is-empty' : '' ) . '">' . $inner . '</div>';
			}
			$o .= '</div></div>';
		}
		$o .= '</div>';
	}
	return $o . '</footer>';
}

/* the footer shortcode draws the builder footer */
add_action( 'init', function () {
	remove_shortcode( 'orego_footer' );
	add_shortcode( 'orego_footer', 'orego_fb_footer' );
}, 99 );

/* ------------------------------------------------------------------ */
/* CSS                                                                 */
/* ------------------------------------------------------------------ */

function orego_fb_static_css() {
	return <<<'CSS'
.ogfb{position:relative;font-family:Cairo,Tahoma,sans-serif}
.ogfb *{box-sizing:border-box}
.ogfb-m{display:none}
@media (max-width:1024px){.ogfb-d{display:none}.ogfb-m{display:block}}
.ogfb-ri{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:16px 32px}
.ogfb-c{display:flex;align-items:center;flex-wrap:wrap;gap:12px 24px;min-width:0}
.ogfb-c-l{justify-content:flex-start}
.ogfb-c-c{justify-content:center}
.ogfb-c-r{justify-content:flex-end}
.ogfb-m .ogfb-ri{display:flex;flex-direction:column;align-items:center;text-align:center;gap:14px}
.ogfb-m .ogfb-c{flex-direction:column;justify-content:center;gap:14px}
.ogfb-m .ogfb-c.is-empty{display:none}
.ogfb-logo{display:inline-flex;align-items:center;flex:none;text-decoration:none!important;color:inherit!important}
.ogfb-logoimg{display:block;width:auto;max-width:none}
.ogfb-logotxt{font-weight:800;font-size:20px}
.ogfb-nav ul{display:flex;flex-wrap:wrap;align-items:center;list-style:none;margin:0;padding:0}
.ogfb-m .ogfb-nav ul{justify-content:center}
.ogfb-nav li{margin:0}
.ogfb-nav a{text-decoration:none!important;transition:color .15s}
.ogfb-copy{margin:0}
.ogfb-copy a,.ogfb-html a{color:inherit!important;text-decoration:underline}
.ogfb-html{line-height:1.5}
.ogfb-social{display:flex;align-items:center;gap:4px}
.ogfb-social a{display:inline-flex;align-items:center;justify-content:center;border-radius:50%;color:inherit!important;text-decoration:none!important;opacity:.8;transition:opacity .15s,background .15s}
.ogfb-social a:hover{opacity:1;background:rgba(21,23,28,.06)}
.ogfb-social.is-circle a{background:rgba(21,23,28,.06);opacity:1}
.ogfb-social svg{display:block}
.ogfb-btn{display:inline-flex;align-items:center;justify-content:center;height:44px;padding:0 22px;font-weight:700;font-size:15px;text-decoration:none!important;transition:filter .15s}
.ogfb-btn:hover{filter:brightness(1.15)}
.ogfb-contact{display:flex;flex-wrap:wrap;gap:8px 22px;list-style:none;margin:0;padding:0}
.ogfb-m .ogfb-contact{flex-direction:column;align-items:center}
.ogfb-contact li{display:flex;align-items:center;gap:8px;margin:0}
.ogfb-contact svg{width:17px;height:17px;flex:none;opacity:.75}
.ogfb-contact a{color:inherit!important;text-decoration:none!important}
.ogfb-contact a:hover{text-decoration:underline!important}
.ogfb-empty,.ogfb-social.is-demo{opacity:.55;font-style:italic;font-size:13px}
.ogfb a:focus-visible{outline:2px solid currentColor;outline-offset:3px;border-radius:4px}
CSS;
}

function orego_fb_css() {
	$m   = 'orego_fb_mod';
	$n   = function ( $k ) {
		return (int) orego_fb_mod( $k );
	};
	$col = function ( $k ) {
		return sanitize_hex_color( (string) orego_fb_mod( $k ) );
	};
	$fg  = $col( 'fb_fg' ) ?: '#15171C';
	$bg  = $col( 'fb_bg' ) ?: '#FFFFFF';
	$css = '.ogfb{background:' . $bg . ';color:' . $fg . ';margin-top:' . $n( 'fb_margin' ) . 'px';
	if ( $m( 'fb_border' ) ) {
		$css .= ';border-top:1px solid color-mix(in srgb,' . $fg . ' 9%,transparent)';
	}
	if ( $m( 'fb_shadow' ) ) {
		$css .= ';box-shadow:0 -10px 30px -26px rgba(21,23,28,.3)';
	}
	if ( $n( 'fb_radius' ) ) {
		$css .= ';border-radius:' . $n( 'fb_radius' ) . 'px ' . $n( 'fb_radius' ) . 'px 0 0';
	}
	$css .= '}';
	$css .= '.ogfb-ri{padding-inline:clamp(16px,4.5vw,' . $n( 'fb_pad_x' ) . 'px)' . ( 'boxed' === $m( 'fb_width' ) ? ';max-width:' . $n( 'fb_max' ) . 'px;margin-inline:auto' : '' ) . '}';
	$css .= '.ogfb-r-main .ogfb-ri{min-height:' . $n( 'fb_main_h' ) . 'px;padding-block:20px}';
	foreach ( array( 'top', 'bottom' ) as $r ) {
		$css .= '.ogfb-r-' . $r . ' .ogfb-ri{min-height:' . $n( 'fb_' . $r . '_h' ) . 'px;padding-block:12px}';
		$rb = $col( 'fb_' . $r . '_bg' );
		$rf = $col( 'fb_' . $r . '_fg' );
		if ( $rb || $rf ) {
			$css .= '.ogfb-r-' . $r . '{' . ( $rb ? 'background:' . $rb . ';' : '' ) . ( $rf ? 'color:' . $rf : '' ) . '}';
		}
		if ( $m( 'fb_' . $r . '_border' ) ) {
			$css .= '.ogfb-r-' . $r . '{border-' . ( 'top' === $r ? 'bottom' : 'top' ) . ':1px solid color-mix(in srgb,' . $fg . ' 8%,transparent)}';
		}
	}
	$link  = $col( 'fb_link' ) ?: '#6B7280';
	$hover = $col( 'fb_link_hover' ) ?: $fg;
	$muted = $col( 'fb_muted' ) ?: '#8A919C';
	$css  .= '.ogfb-nav ul{gap:8px ' . $n( 'fb_link_gap' ) . 'px}.ogfb-nav a{color:' . $link . '!important;font-size:' . $n( 'fb_link_size' ) . 'px;font-weight:' . (int) $m( 'fb_link_weight' ) . '}';
	$css  .= '.ogfb-nav a:hover,.ogfb-nav a:focus-visible,.ogfb-nav a[aria-current=page]{color:' . $hover . '!important}';
	$css  .= '.ogfb-copy{font-size:' . $n( 'fb_copy_size' ) . 'px;color:' . $muted . '}.ogfb-html{font-size:' . $n( 'fb_html_size' ) . 'px}.ogfb-contact{font-size:' . $n( 'fb_html_size' ) . 'px}';
	$ss    = $n( 'fb_social_size' );
	$css  .= '.ogfb-social svg{width:' . $ss . 'px;height:' . $ss . 'px}.ogfb-social a{width:' . ( $ss + 16 ) . 'px;height:' . ( $ss + 16 ) . 'px}';
	$css  .= '.ogfb-btn{background:' . ( $col( 'fb_btn_bg' ) ?: '#15171C' ) . ';color:' . ( $col( 'fb_btn_fg' ) ?: '#FFFFFF' ) . '!important;border-radius:' . $n( 'fb_btn_radius' ) . 'px}';
	return $css;
}

add_action( 'wp_head', function () {
	echo '<style id="orego-fb-static">' . orego_fb_static_css() . '</style>' . "\n"; // phpcs:ignore
}, 43 );
/* the footer's own settings load last so they win over page styles */
add_action( 'wp_footer', function () {
	echo '<style id="orego-fb-css">' . orego_fb_css() . '</style>' . "\n"; // phpcs:ignore
}, 999 );

/* ------------------------------------------------------------------ */
/* Customizer                                                          */
/* ------------------------------------------------------------------ */

add_action( 'customize_register', function ( $wp ) {
	$d = orego_fb_defaults();

	if ( ! class_exists( 'OreGO_FB_Control' ) ) {
		class OreGO_FB_Control extends WP_Customize_Control {
			public $type = 'orego_fb';
			public function render_content() {
				echo '<div class="ogfb-side">'
					. '<div class="ogfb-stabs"><button type="button" class="is-on" data-t="general">General</button><button type="button" data-t="design">Design</button></div>'
					. '<div class="ogfb-pane" data-t="general"><div class="ogfb-inuse"></div>'
					. '<h4 class="ogfb-h">Available Items</h4><p class="ogfb-hint">Drag an item into the footer builder below the preview.</p><div class="ogfb-avail"></div></div>'
					. '<div class="ogfb-pane" data-t="design" hidden>'
					. '<button type="button" class="ogfb-link" data-sec="ogf_layout">Colors &amp; width</button>'
					. '<button type="button" class="ogfb-link" data-sec="ogf_row_top">Top row</button>'
					. '<button type="button" class="ogfb-link" data-sec="ogf_row_main">Main row</button>'
					. '<button type="button" class="ogfb-link" data-sec="ogf_row_bottom">Bottom row</button>'
					. '</div></div>';
			}
		}
	}

	/* the old flat footer sections give way to the builder */
	foreach ( array( 'orego_footer', 'orego_f_layout' ) as $old ) {
		if ( $wp->get_section( $old ) ) {
			foreach ( $wp->controls() as $c ) {
				if ( $c->section === $old ) {
					$wp->remove_control( $c->id );
				}
			}
			$wp->remove_section( $old );
		}
	}

	$wp->add_section( 'ogf_builder', array( 'title' => 'Footer', 'priority' => 26 ) );
	$wp->add_setting( 'orego_fb_layout', array( 'default' => wp_json_encode( orego_fb_default_layout() ), 'sanitize_callback' => 'orego_fb_sanitize', 'transport' => 'postMessage' ) );
	$wp->add_control( new OreGO_FB_Control( $wp, 'orego_fb_layout', array( 'section' => 'ogf_builder', 'settings' => 'orego_fb_layout' ) ) );

	$titles = array(
		'ogf_logo'       => 'Logo',
		'ogf_menu'       => 'Footer menu',
		'ogf_menu2'      => 'Second menu',
		'ogf_copy'       => 'Copyright',
		'ogf_html'       => 'Text / HTML',
		'ogf_social'     => 'Social icons',
		'ogf_btn'        => 'Button',
		'ogf_contact'    => 'Contact info',
		'ogf_layout'     => 'Colors & width',
		'ogf_row_top'    => 'Top row',
		'ogf_row_main'   => 'Main row',
		'ogf_row_bottom' => 'Bottom row',
	);
	foreach ( $titles as $id => $t ) {
		$wp->add_section( $id, array( 'title' => $t, 'priority' => 210 ) );
	}

	$san = array(
		'color'    => 'sanitize_hex_color',
		'checkbox' => 'wp_validate_boolean',
		'url'      => 'esc_url_raw',
		'text'     => 'sanitize_text_field',
		'textarea' => 'wp_kses_post',
		'number'   => 'absint',
		'select'   => 'sanitize_key',
		'radio'    => 'sanitize_key',
		'menu'     => 'absint',
		'email'    => 'sanitize_email',
	);
	$p   = 10;
	$add = function ( $key, $section, $label, $type, $extra = array() ) use ( $wp, $d, $san, &$p ) {
		$wp->add_setting( 'orego_' . $key, array( 'default' => $d[ $key ], 'sanitize_callback' => $san[ $type ], 'transport' => 'postMessage' ) );
		$args = array_merge( array( 'label' => $label, 'section' => $section, 'settings' => 'orego_' . $key, 'priority' => $p++ ), $extra );
		if ( 'color' === $type ) {
			$wp->add_control( new WP_Customize_Color_Control( $wp, 'orego_' . $key, $args ) );
		} else {
			$wp->add_control( 'orego_' . $key, array_merge( $args, array( 'type' => 'menu' === $type ? 'select' : $type ) ) );
		}
	};
	$px = function ( $min, $max, $step = 1 ) {
		return array( 'input_attrs' => array( 'min' => $min, 'max' => $max, 'step' => $step ) );
	};
	$menus = array();
	foreach ( wp_get_nav_menus() as $mn ) {
		$menus[ $mn->term_id ] = $mn->name;
	}
	$weights = array( '400' => 'Regular', '500' => 'Medium', '600' => 'Semi-bold', '700' => 'Bold', '800' => 'Extra bold' );

	/* Colors & width */
	$add( 'fb_bg', 'ogf_layout', 'Background color', 'color' );
	$add( 'fb_fg', 'ogf_layout', 'Text color', 'color' );
	$add( 'fb_link', 'ogf_layout', 'Link color', 'color' );
	$add( 'fb_link_hover', 'ogf_layout', 'Link color on hover', 'color' );
	$add( 'fb_muted', 'ogf_layout', 'Copyright text color', 'color' );
	$add( 'fb_width', 'ogf_layout', 'Content width', 'radio', array( 'choices' => array( 'full' => 'Full width', 'boxed' => 'Boxed (centered)' ) ) );
	$add( 'fb_max', 'ogf_layout', 'Max width when boxed', 'number', $px( 800, 2000, 10 ) );
	$add( 'fb_pad_x', 'ogf_layout', 'Side padding', 'number', $px( 0, 200 ) );
	$add( 'fb_border', 'ogf_layout', 'Thin line on top of the footer', 'checkbox' );
	$add( 'fb_shadow', 'ogf_layout', 'Soft shadow on top', 'checkbox' );
	$add( 'fb_radius', 'ogf_layout', 'Top corner roundness', 'number', $px( 0, 60 ) );
	$add( 'fb_margin', 'ogf_layout', 'Space above the footer', 'number', $px( 0, 200 ) );

	/* rows */
	$add( 'fb_main_h', 'ogf_row_main', 'Minimum height', 'number', $px( 40, 300 ) );
	foreach ( array( 'top', 'bottom' ) as $r ) {
		$add( 'fb_' . $r . '_h', 'ogf_row_' . $r, 'Minimum height', 'number', $px( 24, 240 ) );
		$add( 'fb_' . $r . '_bg', 'ogf_row_' . $r, 'Background color', 'color', array( 'description' => 'Empty = same as the footer.' ) );
		$add( 'fb_' . $r . '_fg', 'ogf_row_' . $r, 'Text color', 'color' );
		$add( 'fb_' . $r . '_border', 'ogf_row_' . $r, 'Thin dividing line', 'checkbox' );
	}

	/* items */
	$wp->add_setting( 'orego_fb_logo', array( 'default' => $d['fb_logo'], 'sanitize_callback' => 'absint', 'transport' => 'postMessage' ) );
	$wp->add_control( new WP_Customize_Media_Control( $wp, 'orego_fb_logo', array( 'section' => 'ogf_logo', 'label' => 'Logo image', 'mime_type' => 'image', 'priority' => 5 ) ) );
	$add( 'fb_logo_h', 'ogf_logo', 'Logo height', 'number', $px( 16, 120 ) );

	$md = array( 'description' => 'Create and edit menus in Appearance → Menus.' );
	$add( 'fb_menu1', 'ogf_menu', 'Menu to show', 'menu', array( 'choices' => array( 0 => 'Footer Menu (default)' ) + $menus ) + $md );
	$add( 'fb_link_size', 'ogf_menu', 'Link size', 'number', $px( 11, 26 ) );
	$add( 'fb_link_weight', 'ogf_menu', 'Link weight', 'select', array( 'choices' => $weights ) );
	$add( 'fb_link_gap', 'ogf_menu', 'Space between links', 'number', $px( 0, 100 ) );
	$add( 'fb_menu2', 'ogf_menu2', 'Menu to show', 'menu', array( 'choices' => array( 0 => '— Choose a menu —' ) + $menus ) + $md );

	$add( 'fb_copy', 'ogf_copy', 'Copyright text', 'textarea', array( 'description' => '{year} is replaced with the current year. Links are allowed.' ) );
	$add( 'fb_copy_size', 'ogf_copy', 'Text size', 'number', $px( 10, 24 ) );

	$add( 'fb_html', 'ogf_html', 'Text or HTML', 'textarea', array( 'description' => 'For example: Open every day, 08:00 to 20:00. Links are allowed. {year} = current year.' ) );
	$add( 'fb_html_size', 'ogf_html', 'Text size', 'number', $px( 10, 24 ) );

	/* social: the same links as the header, with the footer's own look */
	if ( function_exists( 'orego_hb_social_icons' ) ) {
		$p = 10;
		foreach ( orego_hb_social_icons() as $k => $ic ) {
			if ( $wp->get_setting( 'orego_social_' . $k ) ) {
				$wp->add_control( 'ogf_social_' . $k, array(
					'section'     => 'ogf_social',
					'settings'    => 'orego_social_' . $k,
					'label'       => $ic[0],
					'type'        => 'whatsapp' === $k ? 'text' : 'url',
					'priority'    => $p++,
					'description' => 'facebook' === $k ? 'The same links as the header social icons.' : ( 'whatsapp' === $k ? 'Phone number (with country code) or a link.' : '' ),
					'input_attrs' => 'whatsapp' === $k ? array() : array( 'placeholder' => 'https://' ),
				) );
			}
		}
	}
	$add( 'fb_social_style', 'ogf_social', 'Icon style', 'select', array( 'choices' => array( 'plain' => 'Plain', 'circle' => 'In a circle' ) ) );
	$add( 'fb_social_size', 'ogf_social', 'Icon size', 'number', $px( 12, 32 ) );

	$add( 'fb_btn_text', 'ogf_btn', 'Button text', 'text' );
	$add( 'fb_btn_url', 'ogf_btn', 'Button link', 'url', array( 'description' => 'Empty = the Cars page.', 'input_attrs' => array( 'placeholder' => 'https://' ) ) );
	$add( 'fb_btn_bg', 'ogf_btn', 'Button color', 'color' );
	$add( 'fb_btn_fg', 'ogf_btn', 'Button text color', 'color' );
	$add( 'fb_btn_radius', 'ogf_btn', 'Corner roundness', 'number', $px( 0, 30 ) );

	$add( 'fb_phone', 'ogf_contact', 'Phone', 'text', array( 'input_attrs' => array( 'placeholder' => '+212 6XX XXX XXX' ) ) );
	$add( 'fb_email', 'ogf_contact', 'Email', 'email' );
	$add( 'fb_address', 'ogf_contact', 'Address', 'text' );
	$wp->add_control( 'ogf_contact_size', array( 'section' => 'ogf_contact', 'settings' => 'orego_fb_html_size', 'label' => 'Text size', 'type' => 'number', 'priority' => $p++, 'description' => 'Shared with Text / HTML.', 'input_attrs' => array( 'min' => 10, 'max' => 24, 'step' => 1 ) ) );

	/* live preview */
	if ( isset( $wp->selective_refresh ) ) {
		$css = array();
		$all = array( 'orego_fb_layout' );
		foreach ( array_keys( $d ) as $k ) {
			$all[] = 'orego_' . $k;
		}
		foreach ( array( 'fb_bg', 'fb_fg', 'fb_link', 'fb_link_hover', 'fb_muted', 'fb_width', 'fb_max', 'fb_pad_x', 'fb_border', 'fb_shadow', 'fb_radius', 'fb_margin', 'fb_top_h', 'fb_top_bg', 'fb_top_fg', 'fb_top_border', 'fb_main_h', 'fb_bottom_h', 'fb_bottom_bg', 'fb_bottom_fg', 'fb_bottom_border', 'fb_link_size', 'fb_link_weight', 'fb_link_gap', 'fb_copy_size', 'fb_html_size', 'fb_social_size', 'fb_btn_bg', 'fb_btn_fg', 'fb_btn_radius' ) as $k ) {
			$css[] = 'orego_' . $k;
		}
		$content = array_values( array_diff( $all, $css ) );
		if ( function_exists( 'orego_hb_social_icons' ) ) {
			foreach ( array_keys( orego_hb_social_icons() ) as $k ) {
				$content[] = 'orego_social_' . $k;
			}
		}
		$wp->selective_refresh->add_partial( 'ogfb_css', array(
			'selector'            => '#orego-fb-css',
			'settings'            => $css,
			'container_inclusive' => false,
			'fallback_refresh'    => false,
			'render_callback'     => 'orego_fb_css',
		) );
		$wp->selective_refresh->add_partial( 'ogfb_footer', array(
			'selector'            => 'footer.ogfb',
			'settings'            => $content,
			'container_inclusive' => true,
			'fallback_refresh'    => true,
			'render_callback'     => 'orego_fb_footer',
		) );
	}
}, 60 );

/* No pencil on the CSS partial; the footer pencil opens the builder. */
add_action( 'customize_preview_init', function () {
	add_action( 'wp_footer', function () {
		echo '<script>(function(api){if(!api)return;var on=false,t=null;'
			. 'function down(){if(!on)return;window.scrollTo(0,document.documentElement.scrollHeight);}'
			. 'function soon(){clearTimeout(t);t=setTimeout(down,60);}'
			/* the Customizer puts a title (tooltip) on style blocks it can refresh; browsers treat titled styles as optional and may ignore them */
			. 'function untitle(){var l=document.querySelectorAll("style[title]");for(var i=0;i<l.length;i++){var st=l[i];st.removeAttribute("title");var tx=st.textContent;st.textContent="";st.textContent=tx;}}'
			. 'api.bind("preview-ready",function(){'
			. 'untitle();setTimeout(untitle,300);setTimeout(untitle,1500);'
			. 'if(api.selectiveRefresh){var p=api.selectiveRefresh.partial("ogfb_css");if(p){p.createEditShortcutForPlacement=function(){};}api.selectiveRefresh.bind("partial-content-rendered",function(){untitle();setTimeout(untitle,50);soon();});}'
			. 'api.preview.bind("og-fb-focus",function(v){var was=on;on=!!v;if(on){down();setTimeout(down,350);setTimeout(down,1200);if(!was){var f=document.querySelector("footer.ogfb");if(f){f.style.transition="box-shadow .3s";f.style.boxShadow="inset 0 0 0 2px #2271b1";setTimeout(function(){f.style.boxShadow="";},1400);}}}});'
			. 'window.addEventListener("resize",soon);window.addEventListener("load",soon);'
			. 'if(window.ResizeObserver){new ResizeObserver(soon).observe(document.body);}'
			. '});})(window.wp&&wp.customize);</script>';
	}, 99 );
} );

/* Appearance -> Footer opens the builder */
add_action( 'admin_menu', function () {
	global $submenu;
	if ( empty( $submenu['themes.php'] ) ) {
		return;
	}
	foreach ( $submenu['themes.php'] as $i => $row ) {
		if ( isset( $row[2] ) && false !== strpos( $row[2], 'autofocus[section]=orego_footer' ) ) {
			$submenu['themes.php'][ $i ][2] = 'customize.php?autofocus[section]=ogf_builder'; // phpcs:ignore
		}
	}
}, 1020 );

/* Builder UI in the Customizer */
add_action( 'customize_controls_enqueue_scripts', function () {
	wp_enqueue_script( 'jquery-ui-sortable' );
	$items = array();
	$secs  = array();
	foreach ( orego_fb_items() as $k => $it ) {
		$items[ $k ] = $it[0];
		$secs[ $k ]  = $it[1];
	}
	$subs = array( 'ogf_logo', 'ogf_menu', 'ogf_menu2', 'ogf_copy', 'ogf_html', 'ogf_social', 'ogf_btn', 'ogf_contact', 'ogf_layout', 'ogf_row_top', 'ogf_row_main', 'ogf_row_bottom' );
	$cfg  = array(
		'items'  => $items,
		'sec'    => $secs,
		'rowsec' => array( 'top' => 'ogf_row_top', 'main' => 'ogf_row_main', 'bottom' => 'ogf_row_bottom' ),
		'rowl'   => array( 'top' => 'Top row', 'main' => 'Main row', 'bottom' => 'Bottom row' ),
		'subs'   => $subs,
		'def'    => orego_fb_default_layout(),
		'home'   => home_url( '/' ),
		'cars'   => orego_purl( 'cars' ),
		't'      => array(
			'desktop' => 'Desktop',
			'mobile'  => 'Tablet / Mobile',
			'hide'    => 'Hide',
			'show'    => 'Footer builder',
			'set'     => 'Settings',
			'rm'      => 'Remove',
			'none'    => 'All items are in use on this device.',
			'crumb'   => 'Customizing ▸ Footer',
		),
	);
	wp_add_inline_script( 'customize-controls', 'window.OGFB=' . wp_json_encode( $cfg ) . ';' . orego_fb_controls_js() );
	wp_register_style( 'orego-fb-controls', false, array(), '1.0' );
	wp_enqueue_style( 'orego-fb-controls' );
	wp_add_inline_style( 'orego-fb-controls', orego_fb_controls_css( $subs ) );
} );

function orego_fb_controls_css( $subs ) {
	$hide = implode( ',', array_map( function ( $s ) {
		return '#accordion-section-' . $s;
	}, $subs ) );
	return $hide . '{display:none!important}' . <<<'CSS'
#customize-control-orego_fb_layout{margin:0}
.ogfb-stabs{display:flex;margin:-12px -12px 16px;border-bottom:1px solid #dcdcde;background:#fff}
.ogfb-stabs button{flex:1;padding:14px 8px;border:0;border-bottom:3px solid transparent;background:none;font-weight:600;font-size:13px;letter-spacing:.04em;text-transform:uppercase;color:#50575e;cursor:pointer}
.ogfb-stabs button.is-on{color:#1d2327;border-bottom-color:#2271b1;background:#f6f7f7}
.ogfb-link{display:flex;align-items:center;justify-content:space-between;width:100%;padding:14px 16px;margin:0 0 10px;border:1px solid #e2e4e7;border-radius:6px;background:#fff;font-size:14px;font-weight:600;color:#1d2327;cursor:pointer;text-align:start}
.ogfb-link::after{content:"";width:8px;height:8px;border-right:2px solid #8c8f94;border-top:2px solid #8c8f94;transform:rotate(45deg)}
html[dir=rtl] .ogfb-link::after{transform:rotate(-135deg)}
.ogfb-link:hover{border-color:#2271b1;color:#2271b1}
.ogfb-h{margin:20px 0 4px;font-size:14px}
.ogfb-hint{margin:0 0 10px;color:#646970;font-size:12px}
.ogfb-avail{min-height:56px;padding:12px;border:1px dashed #a7aaad;border-radius:4px;background:#f6f7f7}
.ogfb-avail .ogfb-it{width:100%;margin:0 0 8px;border-left:3px solid #2271b1}
.ogfb-avail .ogfb-gear,.ogfb-avail .ogfb-x{display:none}
.ogfb-none{margin:0;color:#646970;font-size:12px;font-style:italic}
.ogfb-it{display:inline-flex;align-items:center;gap:4px;height:34px;padding:0 4px 0 8px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;font-size:13px;color:#1d2327;cursor:grab;box-sizing:border-box;white-space:nowrap;user-select:none}
.ogfb-it:hover{border-color:#2271b1}
.ogfb-grip{color:#8c8f94;letter-spacing:-2px;font-size:14px}
.ogfb-lbl{flex:1;font-weight:500}
.ogfb-gear,.ogfb-x{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;padding:0;border:0;border-radius:3px;background:none;color:#646970;cursor:pointer}
.ogfb-gear:hover,.ogfb-x:hover{background:#f0f0f1;color:#2271b1}
.ogfb-x:hover{color:#d63638}
.ogfb-ph{display:inline-block;width:110px;height:38px;border:1px dashed #2271b1;border-radius:4px;background:#f0f6fc;vertical-align:middle}
.ogfb-avail .ogfb-ph{display:block;width:100%;margin:0 0 8px}
#ogfb-bar{position:absolute;left:0;right:0;bottom:0;z-index:20;height:var(--ogfb-h,252px);background:#f0f0f1;border-top:1px solid #c3c4c7;box-shadow:0 -4px 16px rgba(0,0,0,.08);display:none;flex-direction:column;font-size:13px}
body.ogfb-on #ogfb-bar{display:flex}
body.ogfb-on.ogfb-min #ogfb-bar{display:none}
body.ogfb-on:not(.ogfb-min) .wp-full-overlay-main{bottom:var(--ogfb-h,252px)}
body.ogfb-on:not(.ogfb-min) .wp-full-overlay-main,body.oghb-on:not(.oghb-min) .wp-full-overlay-main{height:auto!important}
#ogfb-show{position:absolute;right:16px;bottom:16px;z-index:21;display:none;padding:9px 14px;border:0;border-radius:4px;background:#2271b1;color:#fff;font-weight:600;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.2)}
body.ogfb-on.ogfb-min #ogfb-show{display:block}
.ogfb-top{display:flex;align-items:flex-end;justify-content:space-between;padding:10px 16px 0;border-bottom:1px solid #dcdcde}
.ogfb-tabs{display:flex;gap:6px}
.ogfb-tabs button{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border:0;border-bottom:3px solid transparent;background:#e0e0e0;font-weight:600;letter-spacing:.03em;text-transform:uppercase;font-size:12px;color:#50575e;cursor:pointer}
.ogfb-tabs button.is-on{background:#fff;color:#1d2327;border-bottom-color:#2271b1}
.ogfb-title{margin:0 auto 10px 16px;font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#50575e;align-self:center}
.ogfb-hide{margin-bottom:8px;padding:7px 14px;border:1px solid #2271b1;border-radius:3px;background:#fff;color:#2271b1;font-style:italic;font-weight:600;cursor:pointer}
.ogfb-rows{flex:1;overflow:auto;padding:12px 16px;display:flex;flex-direction:column;gap:8px}
.ogfb-row{display:flex;align-items:stretch;min-height:50px;flex:none}
.ogfb-rowgear{flex:none;width:32px;border:0;border-radius:3px 0 0 3px;background:#2271b1;color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0}
.ogfb-rowgear:hover{background:#135e96}
.ogfb-col{display:flex;align-items:center;align-content:center;gap:6px;padding:6px 8px;border:1px dashed #a7aaad;border-left:0;background:#fafafa;min-width:0;flex-wrap:wrap}
.ogfb-left{flex:1 1 0;justify-content:flex-start}
.ogfb-center{flex:1 1 0;justify-content:center}
.ogfb-right{flex:1 1 0;justify-content:flex-end}
.ogfb-col.ui-sortable-over,.ogfb-avail.ui-sortable-over{background:#f0f6fc}
.ui-sortable-helper.ogfb-it{box-shadow:0 6px 18px rgba(0,0,0,.2);cursor:grabbing;z-index:600000!important}
CSS;
}

function orego_fb_controls_js() {
	return <<<'JS'
(function($,api){
var D=window.OGFB,dev='desktop',tab='general',min=false,$bar,$show,pending=null;
var gear='<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>';
var R=['top','main','bottom'],C=['left','center','right'];
function esc(s){return String(s).replace(/[&<>"]/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]})}
function lay(){var l;try{l=JSON.parse(api('orego_fb_layout').get())}catch(e){}if(!l||!l.desktop||!l.mobile)l=JSON.parse(JSON.stringify(D.def));R.forEach(function(r){['desktop','mobile'].forEach(function(dv){l[dv][r]=l[dv][r]||{};C.forEach(function(c){l[dv][r][c]=l[dv][r][c]||[]})})});return l}
function used(l,dv){var u=[];R.forEach(function(r){C.forEach(function(c){u=u.concat(l[dv][r][c])})});return u}
function item(k){return '<div class="ogfb-it" data-k="'+k+'"><span class="ogfb-grip">⠿</span><span class="ogfb-lbl">'+esc(D.items[k])+'</span><button type="button" class="ogfb-gear" title="'+esc(D.t.set)+'">'+gear+'</button><button type="button" class="ogfb-x" title="'+esc(D.t.rm)+'">✕</button></div>'}
function save(l){api('orego_fb_layout').set(JSON.stringify(l))}
function collect(){var l=lay(),d={};$bar.find('.ogfb-row').each(function(){var r=$(this).data('r');d[r]={};$(this).find('.ogfb-col').each(function(){d[r][$(this).data('c')]=$(this).children('.ogfb-it').map(function(){return $(this).data('k')}).get()})});l[dev]=d;return l}
function render(){
 if(!$bar)return;var l=lay(),d=l[dev],h='';
 R.forEach(function(r){h+='<div class="ogfb-row" data-r="'+r+'"><button type="button" class="ogfb-rowgear" data-r="'+r+'" title="'+esc(D.rowl[r])+'">'+gear+'</button>';C.forEach(function(c){h+='<div class="ogfb-col ogfb-'+c+'" data-c="'+c+'">'+d[r][c].map(item).join('')+'</div>'});h+='</div>'});
 $bar.find('.ogfb-rows').html(h);
 $bar.find('.ogfb-tabs button').each(function(){$(this).toggleClass('is-on',$(this).data('dev')===dev)});
 side();sort();fit();
}
function fit(){if(!$bar)return;var h=$bar.find('.ogfb-top').outerHeight()+24;$bar.find('.ogfb-row').each(function(){h+=$(this).outerHeight()+8});h=Math.max(220,Math.min(h,Math.round(window.innerHeight*.55)));document.documentElement.style.setProperty('--ogfb-h',h+'px')}
function side(){
 var $s=$('.ogfb-side');if(!$s.length)return;var l=lay(),all=[];used(l,'desktop').concat(used(l,'mobile')).forEach(function(k){if(all.indexOf(k)<0)all.push(k)});
 $s.find('.ogfb-inuse').html(all.map(function(k){return '<button type="button" class="ogfb-link" data-sec="'+D.sec[k]+'">'+esc(D.items[k])+'</button>'}).join(''));
 var u=used(l,dev),av=Object.keys(D.items).filter(function(k){return u.indexOf(k)<0});
 $s.find('.ogfb-avail').html(av.length?av.map(item).join(''):'<p class="ogfb-none">'+esc(D.t.none)+'</p>');
 $s.find('.ogfb-stabs button').each(function(){$(this).toggleClass('is-on',$(this).data('t')===tab)});$s.find('.ogfb-pane').each(function(){this.hidden=$(this).data('t')!==tab});
}
function sort(){
 var $z=$('.ogfb-col,.ogfb-avail');try{$z.sortable('destroy')}catch(e){}
 $z.sortable({connectWith:'.ogfb-col,.ogfb-avail',items:'> .ogfb-it',placeholder:'ogfb-ph',tolerance:'pointer',helper:'clone',appendTo:document.body,zIndex:600000,cancel:'button',
  stop:function(){save(collect());setTimeout(render,0)}});
}
function setDev(v,fromPreview){dev=v;if(!fromPreview){var cur=api.previewedDevice.get();if(v==='desktop'&&cur!=='desktop')api.previewedDevice.set('desktop');if(v==='mobile'&&cur==='desktop')api.previewedDevice.set('mobile')}render()}
function anyOpen(){var o=false;['ogf_builder'].concat(D.subs).forEach(function(id){var s=api.section(id);if(s&&s.expanded())o=true});return o}
function vis(){var on=anyOpen();$('body').toggleClass('ogfb-on',on).toggleClass('ogfb-min',min);if(on)$('body').removeClass('oghb-on');setTimeout(fit,0);state()}
function state(){try{api.previewer.send('og-fb-focus',anyOpen())}catch(e){}}
function go(id){var s=api.section(id);if(s)s.focus()}
/* show the footer in the preview */
function clean(u){try{var x=new URL(u,location.href);return x.origin+x.pathname.replace(/\/+$/,'')}catch(e){return String(u||'')}}
function toFooter(){var raw=String(api.previewer.previewUrl.get()||'');if(clean(raw)===clean(D.home)||/[?&]og_langprev=/.test(raw)){api.previewer.previewUrl.set(D.cars);return}state()}
api.bind('ready',function(){
 $bar=$('<div id="ogfb-bar"><div class="ogfb-top"><div class="ogfb-tabs"><button type="button" data-dev="desktop"><span class="dashicons dashicons-desktop"></span>'+esc(D.t.desktop)+'</button><button type="button" data-dev="mobile"><span class="dashicons dashicons-smartphone"></span>'+esc(D.t.mobile)+'</button></div><span class="ogfb-title">Footer builder</span><button type="button" class="ogfb-hide">✕ '+esc(D.t.hide)+'</button></div><div class="ogfb-rows"></div></div>').appendTo('.wp-full-overlay');
 $show=$('<button type="button" id="ogfb-show">'+esc(D.t.show)+'</button>').appendTo('.wp-full-overlay');
 $bar.on('click','.ogfb-tabs button',function(){setDev($(this).data('dev'))});
 $bar.on('click','.ogfb-hide',function(){min=true;vis()});$show.on('click',function(){min=false;vis()});
 $bar.on('click','.ogfb-rowgear',function(){go(D.rowsec[$(this).data('r')])});
 $(document).on('click','.ogfb-it .ogfb-gear',function(e){e.stopPropagation();go(D.sec[$(this).closest('.ogfb-it').data('k')])});
 $(document).on('click','#ogfb-bar .ogfb-it .ogfb-x',function(e){e.stopPropagation();$(this).closest('.ogfb-it').remove();save(collect());render()});
 $(document).on('click','.ogfb-side .ogfb-link',function(){go($(this).data('sec'))});
 $(document).on('click','.ogfb-stabs button',function(){tab=$(this).data('t');side()});
 api.previewedDevice.bind(function(v){var nd=v==='desktop'?'desktop':'mobile';if(nd!==dev)setDev(nd,true)});
 api.previewer.bind('ready',function(){setTimeout(state,100)});
 dev=api.previewedDevice.get()==='desktop'?'desktop':'mobile';
 ['ogf_builder'].concat(D.subs).forEach(function(id){api.section(id,function(s){s.expanded.bind(function(on){setTimeout(vis,0);if(on)toFooter()});
  if(id!=='ogf_builder'){s.contentContainer.find('.customize-action').text(D.t.crumb);
   s.contentContainer.on('click keydown','.customize-section-back',function(e){if(e.type==='keydown'&&e.which!==13)return;setTimeout(function(){go('ogf_builder')},0)})}})});
 api.control('orego_fb_layout',function(c){c.deferred.embedded.done(function(){render()})});
 api('orego_fb_layout').bind(function(){if(!$('.ui-sortable-helper').length)render()});
 render();vis();
});
})(jQuery,wp.customize);
JS;
}
