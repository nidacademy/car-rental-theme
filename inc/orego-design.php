<?php
/**
 * Exotic Cars design system
 * - Colour tokens (--hx-*) on :root, from a ready-made theme + optional Customizer overrides
 * - Local fonts: Cairo (Arabic) + Assistant (Latin) under the family name "Cairo",
 *   so every existing "Cairo" font setting (Elementor + site code) picks them up
 * - Maps the tokens onto the header/footer, the car/booking styles and Elementor's global colours
 * Customizer: Appearance -> Customize -> Design: colours & theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_design_presets() {
	// primary, accent, price, badge, ink, bg
	return array(
		'emerald'  => array( 'Emerald', '#0f5754', '#ea9302', '#0f5754', '#c62828', '#121212', '#ffffff' ),
		'ocean'    => array( 'Ocean', '#1d4ed8', '#f59e0b', '#1d4ed8', '#dc2626', '#0f172a', '#ffffff' ),
		'midnight' => array( 'Midnight', '#111827', '#d4a017', '#111827', '#b91c1c', '#111827', '#ffffff' ),
		'rose'     => array( 'Rose', '#be185d', '#f59e0b', '#be185d', '#e11d48', '#1f2937', '#fff7fb' ),
		'royal'    => array( 'Royal', '#6d28d9', '#f59e0b', '#6d28d9', '#dc2626', '#1e1b4b', '#ffffff' ),
		'sunset'   => array( 'Sunset', '#c2410c', '#0f766e', '#c2410c', '#b91c1c', '#1c1917', '#fffaf5' ),
		'desert'   => array( 'Desert', '#8b5e34', '#d97706', '#8b5e34', '#b91c1c', '#292524', '#fdf8f1' ),
		'maroc'    => array( 'Maroc', '#9f1d20', '#c9a227', '#9f1d20', '#dc2626', '#1a1a1a', '#fffdf8' ),
		'olive'    => array( 'Olive', '#4d6b2f', '#e0a100', '#4d6b2f', '#c62828', '#1c1f17', '#fbfcf7' ),
		'teal'     => array( 'Teal', '#0e7490', '#f97316', '#0e7490', '#dc2626', '#0f172a', '#ffffff' ),
	);
}

function orego_hex_rgb( $hex ) {
	$h = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $h ) ) {
		$h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
	}
	return array( hexdec( substr( $h, 0, 2 ) ), hexdec( substr( $h, 2, 2 ) ), hexdec( substr( $h, 4, 2 ) ) );
}

/** Mix colour $a with $b; $t = share of $b (0..1). */
function orego_mix( $a, $b, $t ) {
	$x = orego_hex_rgb( $a );
	$y = orego_hex_rgb( $b );
	$o = '#';
	for ( $i = 0; $i < 3; $i++ ) {
		$o .= str_pad( dechex( (int) round( $x[ $i ] + ( $y[ $i ] - $x[ $i ] ) * $t ) ), 2, '0', STR_PAD_LEFT );
	}
	return $o;
}

/** The active tokens: theme values, each overridable in the Customizer. */
function orego_tokens() {
	$all  = orego_design_presets();
	$name = get_theme_mod( 'orego_design_theme', 'emerald' );
	$p    = $all[ $name ] ?? $all['emerald'];
	$keys = array( 1 => 'primary', 2 => 'accent', 3 => 'price', 4 => 'badge', 5 => 'ink', 6 => 'bg' );
	$t    = array();
	foreach ( $keys as $i => $k ) {
		$own     = sanitize_hex_color( (string) get_theme_mod( 'orego_design_' . $k, '' ) );
		$t[ $k ] = $own ? $own : $p[ $i ];
	}
	return $t;
}

function orego_design_css() {
	$t   = orego_tokens();
	$ink = implode( ',', orego_hex_rgb( $t['ink'] ) );
	$kit = (int) get_option( 'elementor_active_kit' );
	$f   = get_template_directory_uri() . '/assets/orego-fonts/';
	$css = ':root{'
		. '--hx-primary:' . $t['primary'] . ';--hx-primary-dark:' . orego_mix( $t['primary'], '#000000', .2 ) . ';--hx-primary-soft:' . orego_mix( $t['primary'], '#ffffff', .9 ) . ';--hx-primary-softer:' . orego_mix( $t['primary'], '#ffffff', .95 ) . ';--hx-primary-border:' . orego_mix( $t['primary'], '#ffffff', .7 ) . ';'
		. '--hx-accent:' . $t['accent'] . ';--hx-accent-title:' . orego_mix( $t['accent'], '#ffffff', .12 ) . ';--hx-price:' . $t['price'] . ';--hx-badge:' . $t['badge'] . ';--hx-badge-bg:' . orego_mix( $t['badge'], '#ffffff', .9 ) . ';'
		. '--hx-ink:' . $t['ink'] . ';--hx-text:rgba(' . $ink . ',.75);--hx-muted:rgba(' . $ink . ',.55);--hx-bg:' . $t['bg'] . ';--hx-line:#ced4da;--hx-addon:#e9ecef;'
		. '--hx-font:"Cairo","SF Arabic","Geeza Pro","Segoe UI",Tahoma,sans-serif;'
		// header, footer, promo, drawer follow the tokens (Customizer colour fields still win when set)
		. '--ogh-bg:var(--hx-primary);--og-teal:var(--hx-primary);--og-teal-2:var(--hx-primary-dark);--og-teal-soft:var(--hx-primary-soft);--ogf-bg:var(--hx-primary);--ogp-bg:var(--hx-primary-dark)'
		. '}';
	// Elementor global colours used on the pages
	if ( $kit ) {
		$css .= 'body.elementor-kit-' . $kit . '{--e-global-color-ogaccent:var(--hx-primary);--e-global-color-ogaccsoft:var(--hx-primary-soft);--e-global-color-ogink:var(--hx-ink);--e-global-color-ogmuted:var(--hx-muted);--e-global-color-ogbg:var(--hx-primary-softer);--e-global-color-ogpromo:var(--hx-primary-dark);--e-global-color-ogfooter:var(--hx-primary)}';
	}
	// local fonts: Arabic letters from Cairo, Latin letters from Assistant
	$ar  = 'U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC';
	$lat = 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD';
	$lx  = 'U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF';
	$css .= '@font-face{font-family:"Cairo";font-style:normal;font-weight:200 1000;font-display:swap;src:url(' . $f . 'cairo-arabic.woff2) format("woff2-variations"),url(' . $f . 'cairo-arabic.woff2) format("woff2");unicode-range:' . $ar . '}';
	$css .= '@font-face{font-family:"Cairo";font-style:normal;font-weight:200 800;font-display:swap;src:url(' . $f . 'assistant-latin-ext.woff2) format("woff2-variations"),url(' . $f . 'assistant-latin-ext.woff2) format("woff2");unicode-range:' . $lx . '}';
	$css .= '@font-face{font-family:"Cairo";font-style:normal;font-weight:200 800;font-display:swap;src:url(' . $f . 'assistant-latin.woff2) format("woff2-variations"),url(' . $f . 'assistant-latin.woff2) format("woff2");unicode-range:' . $lat . '}';
	// base
	$css .= 'body{background:var(--hx-bg);color:var(--hx-ink);font-family:var(--hx-font)}';
	$css .= ':where(a,button,input,select,textarea,summary,[tabindex]):focus-visible{outline:2px solid var(--hx-accent);outline-offset:2px}';
	// car cards: gentle photo zoom on hover
	$css .= '.og-cars .og-media{overflow:hidden}.og-cars .og-media img{transition:transform .35s ease}.og-cars .og-v:hover .og-media img{transform:scale(1.03)}@media (prefers-reduced-motion:reduce){.og-cars .og-media img{transition:none}.og-cars .og-v:hover .og-media img{transform:none}}';
	// prices use the price token
	$css .= '.og-cars .og-price,.ogx-price strong,.ogd-sim-price span,.ogb-total strong{color:var(--hx-price)}';
	return $css;
}

add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	echo '<style id="orego-design-tokens">' . orego_design_css() . '</style>' . "\n"; // phpcs:ignore
}, 39 );

/* Preload the Arabic font so text doesn't jump on first paint */
add_action( 'wp_head', function () {
	if ( ! is_admin() ) {
		echo '<link rel="preload" href="' . esc_url( get_template_directory_uri() . '/assets/orego-fonts/cairo-arabic.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
}, 2 );

/* Customizer: Design: colours & theme */
add_action( 'customize_register', function ( $wp ) {
	$L = 'orego_L';
	$wp->add_section( 'orego_design', array(
		'title'       => $L( 'Design: colours & theme', 'التصميم: الألوان والسمة' ),
		'description' => $L( 'Pick a ready-made theme, then fine-tune any colour. Leave a colour empty to use the theme’s. The header, footer and promo bar follow these colours unless you set their own in the Header and Footer sections.', 'اختر سمة جاهزة ثم عدّل أي لون. اترك اللون فارغًا لاستخدام لون السمة. الهيدر والفوتر وشريط العروض تتبع هذه الألوان ما لم تحدّد لها ألوانًا خاصة.' ),
		'priority'    => 24,
	) );
	$choices = array();
	foreach ( orego_design_presets() as $k => $p ) {
		$choices[ $k ] = $p[0] . ' — ' . $p[1] . ' / ' . $p[2];
	}
	$wp->add_setting( 'orego_design_theme', array( 'default' => 'emerald', 'transport' => 'refresh', 'sanitize_callback' => function ( $v ) {
		return array_key_exists( $v, orego_design_presets() ) ? $v : 'emerald';
	} ) );
	if ( ! class_exists( 'OreGO_Theme_Swatch_Control' ) ) {
		class OreGO_Theme_Swatch_Control extends WP_Customize_Control {
			public $type = 'orego_swatch';
			public function render_content() {
				$name = '_customize-radio-' . $this->id;
				echo '<span class="customize-control-title">' . esc_html( $this->label ) . '</span><div class="ogsw-grid" role="radiogroup">';
				foreach ( orego_design_presets() as $k => $p ) {
					echo '<label class="ogsw"><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $k ) . '" ' . $this->get_link() . checked( $this->value(), $k, false ) . '>'
						. '<span class="ogsw-card"><span class="ogsw-prev" style="background:' . esc_attr( $p[6] ) . '"><span class="ogsw-bar" style="background:' . esc_attr( $p[1] ) . '"><i style="background:' . esc_attr( $p[2] ) . '"></i></span><span class="ogsw-lines"><b style="background:' . esc_attr( $p[5] ) . '"></b><b style="background:' . esc_attr( $p[5] ) . ';opacity:.35"></b></span><span class="ogsw-btn" style="background:' . esc_attr( $p[1] ) . '"></span><span class="ogsw-dot" style="background:' . esc_attr( $p[2] ) . '"></span></span>'
						. '<span class="ogsw-name">' . esc_html( $p[0] ) . '</span><span class="ogsw-chips"><i style="background:' . esc_attr( $p[1] ) . '"></i><i style="background:' . esc_attr( $p[2] ) . '"></i><i style="background:' . esc_attr( $p[4] ) . '"></i></span></span></label>';
				}
				echo '</div>';
			}
		}
	}
	$wp->add_control( new OreGO_Theme_Swatch_Control( $wp, 'orego_design_theme', array( 'label' => $L( 'Ready-made theme', 'السمة الجاهزة' ), 'section' => 'orego_design' ) ) );
	$fields = array(
		'primary' => array( 'Primary (buttons, header, footer, borders)', 'اللون الرئيسي (الأزرار، الهيدر، الفوتر)' ),
		'accent'  => array( 'Accent (highlights, focus outline)', 'لون التمييز (الإبراز وإطار التركيز)' ),
		'price'   => array( 'Prices', 'الأسعار' ),
		'badge'   => array( 'Badges & warnings', 'الشارات والتنبيهات' ),
		'ink'     => array( 'Text', 'النص' ),
		'bg'      => array( 'Page background', 'خلفية الصفحة' ),
	);
	foreach ( $fields as $k => $lbl ) {
		$wp->add_setting( 'orego_design_' . $k, array( 'default' => '', 'transport' => 'refresh', 'sanitize_callback' => 'sanitize_hex_color' ) );
		$wp->add_control( new WP_Customize_Color_Control( $wp, 'orego_design_' . $k, array( 'label' => $L( $lbl[0], $lbl[1] ), 'section' => 'orego_design', 'description' => $L( 'Empty = from the theme.', 'فارغ = من السمة.' ) ) ) );
	}
} );

/* Swatch picker styles */
add_action( 'customize_controls_print_styles', function () {
	echo '<style>.ogsw-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:4px}.ogsw{position:relative;display:block;cursor:pointer;margin:0}.ogsw input{position:absolute;opacity:0;pointer-events:none}.ogsw-card{display:block;padding:8px;border:2px solid #e2e4e7;border-radius:8px;background:#fff;transition:border-color .15s,box-shadow .15s}.ogsw:hover .ogsw-card{border-color:#a7aaad}.ogsw input:checked+.ogsw-card{border-color:#2271b1;box-shadow:0 0 0 2px rgba(34,113,177,.18)}.ogsw input:focus-visible+.ogsw-card{outline:2px solid #2271b1;outline-offset:2px}.ogsw-prev{position:relative;display:block;height:64px;border-radius:5px;overflow:hidden;border:1px solid rgba(0,0,0,.06)}.ogsw-bar{display:flex;align-items:center;justify-content:flex-end;height:16px;padding:0 6px}.ogsw-bar i{width:14px;height:5px;border-radius:3px}.ogsw-lines{display:block;padding:8px 8px 0}.ogsw-lines b{display:block;height:5px;border-radius:3px;margin-bottom:4px;width:70%}.ogsw-lines b+b{width:45%}.ogsw-btn{position:absolute;left:8px;bottom:8px;width:34px;height:10px;border-radius:3px}.ogsw-dot{position:absolute;right:8px;bottom:7px;width:12px;height:12px;border-radius:50%}.ogsw-name{display:block;margin-top:7px;font-size:13px;font-weight:600;color:#1d2327}.ogsw-chips{display:flex;gap:4px;margin-top:4px}.ogsw-chips i{width:14px;height:14px;border-radius:50%;box-shadow:inset 0 0 0 1px rgba(0,0,0,.12)}.ogsw input:checked+.ogsw-card .ogsw-name::after{content:" \2713";color:#2271b1}</style>';
} );
