<?php
/**
 * Exotic Cars: Customize -> Header, organised by header part (logo, menu, icons, search, button),
 * with live preview (no reload for styling) and edit shortcuts (pencil icons) on every part.
 * Builds on the settings registered in orego-customize.php.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ogh_defaults() {
	return array(
		'header_gap'         => '',
		'logo_color'         => '',
		'logo_tag_size'      => '',
		'logo_icon_size'     => '',
		'nav_color'          => '',
		'nav_transform'      => 'none',
		'lang_globe'         => true,
		'globe_bg'           => '',
		'globe_fg'           => '',
		'globe_size'         => '',
		'chev_show'          => true,
		'cur_fg'             => '',
		'cur_bg'             => '',
		'cur_bg_op'          => '',
		'cur_radius'         => '',
		'cur_h'              => '',
		'cur_font'           => '',
		'cur_flag'           => '',
		'search_placeholder' => 'Search for a car…',
		'search_width'       => '',
		'search_height'      => '',
		'search_radius'      => '',
		'search_bg'          => '',
		'search_bg_op'       => '',
		'search_border'      => '',
		'search_fg'          => '',
	);
}

function ogh_mod( $k ) {
	$d = ogh_defaults();
	return get_theme_mod( 'orego_' . $k, $d[ $k ] ?? '' );
}

/** True when an older header setting was changed by the owner (or is being previewed). */
function ogh_is_set( $k ) {
	return null !== get_theme_mod( 'orego_' . $k, null );
}

function ogh_rgba( $hex, $op ) {
	$h = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $h ) ) {
		$h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
	}
	$rgb = sscanf( $h, '%02x%02x%02x' );
	return 'rgba(' . implode( ',', array_map( 'intval', $rgb ) ) . ',' . max( 0, min( 100, (int) $op ) ) / 100 . ')';
}

/** Search box markup (also used by the live preview). */
function ogh_search_html() {
	if ( ! orego_mod( 'header_search' ) || 'glass' !== orego_mod( 'header_style' ) ) {
		return '';
	}
	$ph = trim( (string) ogh_mod( 'search_placeholder' ) );
	return '<form class="ogx2-search" action="' . esc_url( orego_purl( 'cars' ) ) . '" method="get" role="search"><label class="screen-reader-text" for="ogx2-q">' . esc_html( $ph ? $ph : 'Search for a car' ) . '</label><input id="ogx2-q" type="search" name="q" placeholder="' . esc_attr( $ph ) . '" autocomplete="off"><button type="submit" aria-label="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></button></form>';
}

function ogh_cta_html() {
	if ( ! orego_mod( 'cta_show' ) || '' === trim( (string) orego_mod( 'cta_text' ) ) ) {
		return '';
	}
	return '<a class="ogx2-cta" href="' . esc_url( orego_cta_url() ) . '"' . ( orego_mod( 'cta_new_tab' ) ? ' target="_blank" rel="noopener"' : '' ) . '>' . esc_html( orego_mod( 'cta_text' ) ) . '</a>';
}

/** CSS for the per-part options. Strong selectors so they win over the header style presets. */
function ogh_css() {
	$P   = 'html body .ogx2';
	$c   = function ( $k ) {
		return sanitize_hex_color( (string) ogh_mod( $k ) );
	};
	$n   = function ( $k ) {
		$v = ogh_mod( $k );
		return ( '' === $v || null === $v || false === $v ) ? null : (int) $v;
	};
	$old = function ( $k ) {
		return ogh_is_set( $k ) ? orego_mod( $k ) : null;
	};
	$o = '';

	/* layout */
	if ( null !== ( $v = $n( 'header_gap' ) ) ) {
		$o .= '@media (min-width:1025px){' . $P . ' .ogx2-c,' . $P . ' .ogx2-hri{gap:' . $v . 'px!important}}';
	}

	/* logo */
	if ( $x = $c( 'logo_color' ) ) {
		$o .= "$P .ogx2-logo,$P .ogx2-logo b,$P .ogx2-logo small,$P .ogx2-mark{color:$x!important}";
	}
	if ( null !== ( $v = $n( 'logo_tag_size' ) ) ) {
		$o .= "$P .ogx2-word small{font-size:{$v}px}";
	}
	if ( null !== ( $v = $n( 'logo_icon_size' ) ) ) {
		$o .= "$P .ogx2-mark svg{width:{$v}px;height:{$v}px}";
	}

	/* menu */
	$a = array();
	if ( null !== ( $v = $old( 'nav_size' ) ) ) {
		$a[] = 'font-size:' . (int) $v . 'px';
	}
	if ( null !== ( $v = $old( 'nav_weight' ) ) ) {
		$a[] = 'font-weight:' . (int) $v;
	}
	if ( null !== ( $v = $old( 'nav_pad_x' ) ) ) {
		$a[] = 'padding:0 ' . (int) $v . 'px';
	}
	if ( $x = $c( 'nav_color' ) ) {
		$a[] = 'color:' . $x . '!important';
	}
	$t = (string) ogh_mod( 'nav_transform' );
	if ( in_array( $t, array( 'uppercase', 'capitalize' ), true ) ) {
		$a[] = 'text-transform:' . $t . ( 'uppercase' === $t ? ';letter-spacing:.05em' : '' );
	}
	if ( $a ) {
		$o .= "$P .ogx2-links li>a{" . implode( ';', $a ) . '}';
	}
	if ( $x = sanitize_hex_color( (string) orego_mod( 'nav_hover' ) ) ) {
		$o .= "$P .ogx2-links li>a:hover,$P .ogx2-links li:focus-within>a,$P .ogx2-links li.is-current>a{color:$x!important;opacity:1}";
	}

	/* language icon */
	$lg = "$P .ogx2-dd[data-dd=lang] .ogx2-pill";
	if ( ! ogh_mod( 'lang_globe' ) ) {
		$o .= "$P .ogx2-globe{display:none!important}";
		if ( 'code' !== orego_mod( 'pill_style' ) ) {
			$o .= "$lg .ogx2-flag{display:inline-flex!important}";
		}
	}
	$a = array();
	if ( $x = $c( 'globe_bg' ) ) {
		$a[] = 'background:' . $x . '!important';
	}
	if ( $x = $c( 'globe_fg' ) ) {
		$a[] = 'color:' . $x . '!important';
	}
	if ( null !== ( $v = $n( 'globe_size' ) ) ) {
		$a[] = "width:{$v}px!important;height:{$v}px!important";
		$o  .= "$P .ogx2-globe svg{width:" . round( $v * .56 ) . 'px;height:' . round( $v * .56 ) . 'px}';
	}
	if ( $a ) {
		$o .= "$P .ogx2-globe{" . implode( ';', $a ) . '}';
	}
	if ( ! ogh_mod( 'chev_show' ) ) {
		$o .= "$P .ogx2-pill>svg{display:none!important}";
	}
	if ( ogh_is_set( 'lang_bg' ) || ogh_is_set( 'lang_bg_op' ) ) {
		$bg = sanitize_hex_color( (string) orego_mod( 'lang_bg' ) );
		$o .= "$lg{background:" . ogh_rgba( $bg ? $bg : '#FFFFFF', (int) orego_mod( 'lang_bg_op' ) ) . '!important}';
	}
	if ( null !== ( $v = $old( 'lang_radius' ) ) ) {
		$o .= "$lg{border-radius:" . (int) $v . 'px}';
	}
	$a = array();
	if ( null !== ( $v = $old( 'lang_h_d' ) ) ) {
		$a[] = 'height:' . (int) $v . 'px';
	}
	if ( null !== ( $v = $old( 'lang_font_d' ) ) ) {
		$a[] = 'font-size:' . (int) $v . 'px';
	}
	if ( $a ) {
		$o .= '@media (min-width:641px){' . $lg . '{' . implode( ';', $a ) . '}}';
	}

	/* currency icon */
	$cu = "$P .ogx2-dd[data-dd=cur] .ogx2-pill";
	$a  = array();
	if ( $x = $c( 'cur_fg' ) ) {
		$a[] = 'color:' . $x;
	}
	if ( $c( 'cur_bg' ) || null !== $n( 'cur_bg_op' ) ) {
		$a[] = 'background:' . ogh_rgba( $c( 'cur_bg' ) ? $c( 'cur_bg' ) : '#FFFFFF', null !== $n( 'cur_bg_op' ) ? $n( 'cur_bg_op' ) : 12 ) . '!important';
	}
	if ( null !== ( $v = $n( 'cur_radius' ) ) ) {
		$a[] = "border-radius:{$v}px";
	}
	if ( null !== ( $v = $n( 'cur_h' ) ) ) {
		$a[] = "height:{$v}px";
	}
	if ( null !== ( $v = $n( 'cur_font' ) ) ) {
		$a[] = "font-size:{$v}px";
	}
	if ( $a ) {
		$o .= "$cu{" . implode( ';', $a ) . '}';
	}
	if ( null !== ( $v = $n( 'cur_flag' ) ) ) {
		$o .= "$cu .ogx2-flag{width:{$v}px;height:{$v}px}";
	}

	/* search */
	if ( null !== ( $v = $n( 'search_width' ) ) ) {
		$o .= "$P .ogx2-search{width:min({$v}px,40vw)}";
	}
	$a = array();
	if ( null !== ( $v = $n( 'search_height' ) ) ) {
		$a[] = "height:{$v}px";
	}
	if ( null !== ( $v = $n( 'search_radius' ) ) ) {
		$a[] = "border-radius:{$v}px";
	}
	if ( $c( 'search_bg' ) || null !== $n( 'search_bg_op' ) ) {
		$a[] = 'background:' . ogh_rgba( $c( 'search_bg' ) ? $c( 'search_bg' ) : '#FFFFFF', null !== $n( 'search_bg_op' ) ? $n( 'search_bg_op' ) : 16 ) . '!important';
	}
	if ( $x = $c( 'search_border' ) ) {
		$a[] = 'border-color:' . $x . '!important';
	}
	if ( $x = $c( 'search_fg' ) ) {
		$a[] = 'color:' . $x;
		$o  .= "$P .ogx2-search input::placeholder{color:$x;opacity:.75}$P .ogx2-search button{color:$x}";
	}
	if ( $a ) {
		$o .= "$P .ogx2-search input{" . implode( ';', $a ) . '}';
	}

	/* book button */
	$a = array();
	if ( null !== ( $v = $old( 'cta_height' ) ) ) {
		$a[] = 'height:' . (int) $v . 'px';
	}
	if ( null !== ( $v = $old( 'cta_radius' ) ) ) {
		$a[] = 'border-radius:' . (int) $v . 'px';
	}
	if ( ogh_is_set( 'cta_bg' ) && ( $x = sanitize_hex_color( (string) orego_mod( 'cta_bg' ) ) ) ) {
		$a[] = 'background:' . $x . '!important';
	}
	if ( ogh_is_set( 'cta_fg' ) && ( $x = sanitize_hex_color( (string) orego_mod( 'cta_fg' ) ) ) ) {
		$a[] = 'color:' . $x . '!important';
	}
	if ( $a ) {
		$o .= "$P .ogx2-c .ogx2-cta,$P .ogx2-acts .ogx2-cta{" . implode( ';', $a ) . '}';
	}
	return $o;
}

add_action( 'wp_head', function () {
	echo '<style id="orego-header-parts">' . ogh_css() . '</style>' . "\n"; // phpcs:ignore
}, 41 );

/* Edit shortcuts: keep the pencil buttons inside the header and readable on the dark bar. */
add_action( 'wp_head', function () {
	if ( ! is_customize_preview() ) {
		return;
	}
	echo '<style id="orego-header-shortcuts">.ogx2 .customize-partial-edit-shortcut button,.ogx2-promo .customize-partial-edit-shortcut button{left:-6px!important;top:-6px!important;width:26px;height:26px;min-width:26px;padding:3px!important;border:2px solid #fff!important;z-index:20}.ogx2 .ogx2-logo,.ogx2 .ogx2-links,.ogx2 .ogx2-search,.ogx2 .ogx2-dd,.ogx2 .ogx2-cta,.ogx2-promo{position:relative}</style>';
}, 99 );

/* ------------------------------------------------------------------ */
/* Customizer                                                          */
/* ------------------------------------------------------------------ */

add_action( 'customize_register', function ( $wp ) {
	$L = 'orego_L';
	$d = ogh_defaults();

	$wp->add_panel( 'ogh_header', array(
		'title'       => $L( 'Header', 'الهيدر' ),
		'description' => $L( 'Customize each part of the header: logo, menu, icons, search and button. Click the pencil icon on any part in the preview to jump to its settings. Leave a field empty to keep the current design.', 'خصّص كل جزء من الهيدر: الشعار والقائمة والأيقونات والبحث والزر. اضغط على أيقونة القلم فوق أي جزء في المعاينة للانتقال إلى إعداداته. اترك الحقل فارغًا للإبقاء على التصميم الحالي.' ),
		'priority'    => 24,
	) );
	$secs = array(
		'ogh_layout' => $L( 'Layout & colors', 'التخطيط والألوان' ),
		'ogh_logo'   => $L( 'Logo', 'الشعار' ),
		'ogh_menu'   => $L( 'Menu', 'القائمة' ),
		'ogh_lang'   => $L( 'Language icon', 'أيقونة اللغة' ),
		'ogh_cur'    => $L( 'Currency icon', 'أيقونة العملة' ),
		'ogh_search' => $L( 'Search', 'البحث' ),
		'ogh_btn'    => $L( 'Book button', 'زر الحجز' ),
	);
	$p = 10;
	foreach ( $secs as $id => $t ) {
		$wp->add_section( $id, array( 'title' => $t, 'panel' => 'ogh_header', 'priority' => $p += 10 ) );
	}
	foreach ( array( 'orego_drawer' => 90, 'orego_promo' => 100 ) as $id => $pr ) {
		if ( $s = $wp->get_section( $id ) ) {
			$s->panel    = 'ogh_header';
			$s->priority = $pr;
		}
	}
	if ( $pn = $wp->get_panel( 'orego_chrome' ) ) {
		$pn->title       = $L( 'Footer', 'الفوتر' );
		$pn->description = $L( 'Change the website footer. Sizes are in pixels.', 'غيّر فوتر الموقع. المقاسات بالبكسل.' );
		$pn->priority    = 26;
	}

	/* new settings */
	$add = function ( $key, $label, $type, $extra = array() ) use ( $wp, $d ) {
		$choices = $extra['choices'] ?? array();
		$max     = $extra['input_attrs']['max'] ?? 9999;
		$min     = $extra['input_attrs']['min'] ?? 0;
		$san     = array(
			'color'    => 'sanitize_hex_color',
			'checkbox' => function ( $v ) {
				return (bool) $v;
			},
			'text'     => 'sanitize_text_field',
			'number'   => function ( $v ) use ( $min, $max ) {
				return ( '' === $v || null === $v || ! is_numeric( $v ) ) ? '' : max( $min, min( $max, (int) $v ) );
			},
			'select'   => function ( $v ) use ( $choices, $d, $key ) {
				return array_key_exists( $v, $choices ) ? $v : $d[ $key ];
			},
		);
		$wp->add_setting( 'orego_' . $key, array( 'default' => $d[ $key ], 'sanitize_callback' => $san[ $type ], 'transport' => 'postMessage' ) );
		$args = array_merge( array( 'label' => $label, 'section' => 'ogh_layout', 'settings' => 'orego_' . $key ), $extra );
		if ( 'color' === $type ) {
			$wp->add_control( new WP_Customize_Color_Control( $wp, 'orego_' . $key, $args ) );
		} else {
			if ( 'number' === $type ) {
				$args['input_attrs'] = array_merge( array( 'placeholder' => orego_L( 'Auto', 'تلقائي' ) ), $args['input_attrs'] ?? array() );
			}
			$wp->add_control( 'orego_' . $key, array_merge( $args, array( 'type' => $type ) ) );
		}
	};
	$px   = function ( $min, $max ) {
		return array( 'input_attrs' => array( 'min' => $min, 'max' => $max, 'step' => 1 ) );
	};
	$auto = array( 'description' => $L( 'Empty = current design.', 'فارغ = التصميم الحالي.' ) );

	$add( 'header_gap', $L( 'Space between the header parts', 'المسافة بين أجزاء الهيدر' ), 'number', $px( 0, 80 ) + $auto );
	$add( 'logo_color', $L( 'Logo color', 'لون الشعار' ), 'color', $auto );
	$add( 'logo_tag_size', $L( 'Tagline size', 'حجم العبارة' ), 'number', $px( 6, 24 ) + $auto );
	$add( 'logo_icon_size', $L( 'Car icon size', 'حجم أيقونة السيارة' ), 'number', $px( 16, 80 ) + $auto );
	$add( 'nav_color', $L( 'Link color', 'لون الروابط' ), 'color', $auto );
	$add( 'nav_transform', $L( 'Letter case', 'حالة الأحرف' ), 'select', array( 'choices' => array( 'none' => $L( 'As written', 'كما هي' ), 'uppercase' => $L( 'UPPERCASE', 'أحرف كبيرة' ), 'capitalize' => $L( 'Capitalize Each Word', 'أول حرف كبير' ) ) ) );
	$add( 'lang_globe', $L( 'Show the globe icon (Clean style)', 'إظهار أيقونة الكرة الأرضية (الشكل النظيف)' ), 'checkbox', array( 'description' => $L( 'When off, the flag of the language is shown instead.', 'عند الإيقاف يظهر علم اللغة بدلًا منها.' ) ) );
	$add( 'globe_bg', $L( 'Globe circle color', 'لون دائرة الكرة' ), 'color', $auto );
	$add( 'globe_fg', $L( 'Globe icon color', 'لون أيقونة الكرة' ), 'color', $auto );
	$add( 'globe_size', $L( 'Globe circle size', 'حجم دائرة الكرة' ), 'number', $px( 20, 64 ) + $auto );
	$add( 'chev_show', $L( 'Show the small arrow on the language & currency buttons', 'إظهار السهم الصغير في زري اللغة والعملة' ), 'checkbox' );
	$add( 'cur_fg', $L( 'Text color', 'لون النص' ), 'color', $auto );
	$add( 'cur_bg', $L( 'Background color', 'لون الخلفية' ), 'color', $auto );
	$add( 'cur_bg_op', $L( 'Background opacity (%)', 'شفافية الخلفية (٪)' ), 'number', $px( 0, 100 ) + $auto );
	$add( 'cur_radius', $L( 'Corner roundness', 'استدارة الزوايا' ), 'number', $px( 0, 40 ) + $auto );
	$add( 'cur_h', $L( 'Button height', 'ارتفاع الزر' ), 'number', $px( 24, 64 ) + $auto );
	$add( 'cur_font', $L( 'Text size', 'حجم النص' ), 'number', $px( 10, 24 ) + $auto );
	$add( 'cur_flag', $L( 'Flag size', 'حجم العلم' ), 'number', $px( 12, 44 ) + $auto );
	$add( 'search_placeholder', $L( 'Placeholder text', 'النص الإرشادي' ), 'text' );
	$add( 'search_width', $L( 'Width (max)', 'العرض (الأقصى)' ), 'number', $px( 160, 700 ) + $auto );
	$add( 'search_height', $L( 'Height', 'الارتفاع' ), 'number', $px( 28, 72 ) + $auto );
	$add( 'search_radius', $L( 'Corner roundness', 'استدارة الزوايا' ), 'number', $px( 0, 40 ) + $auto );
	$add( 'search_bg', $L( 'Background color', 'لون الخلفية' ), 'color', $auto );
	$add( 'search_bg_op', $L( 'Background opacity (%)', 'شفافية الخلفية (٪)' ), 'number', $px( 0, 100 ) + $auto );
	$add( 'search_border', $L( 'Border color', 'لون الإطار' ), 'color', $auto );
	$add( 'search_fg', $L( 'Text & icon color', 'لون النص والأيقونة' ), 'color', $auto );

	/* put every control in its part, in order */
	$map = array(
		'ogh_layout'   => array( 'header_style', 'header_overlay', 'header_sticky', 'header_bg', 'header_fg', 'header_height', 'header_height_m', 'header_pad_x', 'header_gap', 'header_width', 'header_max', 'header_shadow', 'header_border' ),
		'ogh_logo'     => array( 'logo_image', 'logo_height', 'logo_text', 'logo_size', 'logo_color', 'logo_tagline', 'logo_tag_size', 'logo_icon', 'logo_icon_size' ),
		'ogh_menu'     => array( 'show_nav', 'nav_align', 'nav_color', 'nav_hover', 'nav_style', 'nav_size', 'nav_weight', 'nav_transform', 'nav_gap', 'nav_pad_x', 'nav_radius' ),
		'ogh_lang'     => array( 'show_lang', 'lang_globe', 'globe_bg', 'globe_fg', 'globe_size', 'pill_style', 'chev_show', 'lang_pos', 'lang_fg', 'lang_bg', 'lang_bg_op', 'lang_radius', 'lang_show_d', 'lang_code_d', 'lang_flag_d', 'lang_h_d', 'lang_font_d', 'lang_offset_d', 'lang_show_m', 'lang_code_m', 'lang_flag_m', 'lang_h_m', 'lang_font_m', 'lang_offset_m' ),
		'ogh_cur'      => array( 'show_currency', 'cur_fg', 'cur_bg', 'cur_bg_op', 'cur_radius', 'cur_h', 'cur_font', 'cur_flag' ),
		'ogh_search'   => array( 'header_search', 'search_placeholder', 'search_width', 'search_height', 'search_radius', 'search_bg', 'search_bg_op', 'search_border', 'search_fg' ),
		'ogh_btn'      => array( 'cta_show', 'cta_text', 'cta_url', 'cta_new_tab', 'cta_bg', 'cta_fg', 'cta_height', 'cta_radius' ),
		'orego_drawer' => array( 'drawer_side', 'drawer_width', 'drawer_font', 'drawer_cta', 'drawer_langs', 'burger_d', 'burger_m' ),
	);
	foreach ( $map as $sec => $keys ) {
		foreach ( $keys as $i => $k ) {
			if ( $ctl = $wp->get_control( 'orego_' . $k ) ) {
				$ctl->section  = $sec;
				$ctl->priority = 10 + $i;
			}
		}
	}
	if ( $ctl = $wp->get_control( 'orego_header_search' ) ) {
		$ctl->label       = $L( 'Show the car search box', 'إظهار مربع البحث عن السيارات' );
		$ctl->description = $L( 'Desktop, Clean header style.', 'على الكمبيوتر، مع الشكل النظيف للهيدر.' );
	}
	if ( $ctl = $wp->get_control( 'orego_show_currency' ) ) {
		$ctl->description = $L( 'On phones the currency is in the menu. The currency also follows the visitor language automatically.', 'على الجوال توجد العملة داخل القائمة. وتتبع العملة لغة الزائر تلقائيًا.' );
	}
	foreach ( array( 'orego_h_layout', 'orego_h_logo', 'orego_header', 'orego_h_nav', 'orego_h_btn', 'orego_h_lang' ) as $s ) {
		$wp->remove_section( $s );
	}

	/* live preview */
	$css_keys = array( 'header_height', 'header_height_m', 'header_pad_x', 'header_width', 'header_max', 'header_shadow', 'header_border', 'header_bg', 'header_fg', 'header_gap', 'nav_hover', 'nav_color', 'nav_transform', 'logo_height', 'logo_size', 'logo_color', 'logo_tag_size', 'logo_icon_size', 'nav_align', 'nav_size', 'nav_weight', 'nav_gap', 'nav_pad_x', 'nav_radius', 'nav_style', 'cta_bg', 'cta_fg', 'cta_height', 'cta_radius', 'pill_style', 'lang_globe', 'globe_bg', 'globe_fg', 'globe_size', 'chev_show', 'lang_pos', 'lang_fg', 'lang_bg', 'lang_bg_op', 'lang_radius', 'lang_show_d', 'lang_code_d', 'lang_flag_d', 'lang_h_d', 'lang_font_d', 'lang_offset_d', 'lang_show_m', 'lang_code_m', 'lang_flag_m', 'lang_h_m', 'lang_font_m', 'lang_offset_m', 'burger_d', 'burger_m', 'cur_fg', 'cur_bg', 'cur_bg_op', 'cur_radius', 'cur_h', 'cur_font', 'cur_flag', 'search_width', 'search_height', 'search_radius', 'search_bg', 'search_bg_op', 'search_border', 'search_fg', 'drawer_side', 'drawer_width', 'drawer_font', 'promo_bg', 'promo_fg', 'promo_size', 'promo_pad_y', 'promo_align' );
	$parts    = array(
		'ogh_logo_p'   => array( '.ogx2-in > .ogx2-logo', array( 'logo_text', 'logo_image', 'logo_tagline', 'logo_icon' ), 'orego_chrome_logo' ),
		'ogh_menu_p'   => array( '.ogx2-in > .ogx2-links', array( 'show_nav' ), null ),
		'ogh_lang_p'   => array( '.ogx2-acts > .ogx2-dd[data-dd="lang"]', array( 'show_lang' ), null ),
		'ogh_cur_p'    => array( '.ogx2-acts > .ogx2-dd[data-dd="cur"]', array( 'show_currency' ), null ),
		'ogh_search_p' => array( '.ogx2-in > .ogx2-search', array( 'header_search', 'search_placeholder' ), 'ogh_search_html' ),
		'ogh_btn_p'    => array( '.ogx2-acts > .ogx2-cta', array( 'cta_text', 'cta_show', 'cta_url', 'cta_new_tab' ), 'ogh_cta_html' ),
		'ogh_promo_p'  => array( '.ogx2-promo', array( 'promo_text', 'promo_show', 'promo_url' ), 'orego_promo_html' ),
	);
	$live = $css_keys;
	foreach ( $parts as $pp ) {
		$live = array_merge( $live, $pp[1] );
	}
	foreach ( $live as $k ) {
		if ( $s = $wp->get_setting( 'orego_' . $k ) ) {
			$s->transport = 'postMessage';
		}
	}
	if ( isset( $wp->selective_refresh ) ) {
		$css_settings = array_map( function ( $k ) {
			return 'orego_' . $k;
		}, $css_keys );
		$wp->selective_refresh->add_partial( 'ogh_css_main', array(
			'selector'            => '#orego-chrome-vars',
			'settings'            => $css_settings,
			'container_inclusive' => false,
			'fallback_refresh'    => false,
			'render_callback'     => function () {
				return orego_chrome_dynamic_css();
			},
		) );
		$wp->selective_refresh->add_partial( 'ogh_css_parts', array(
			'selector'            => '#orego-header-parts',
			'settings'            => $css_settings,
			'container_inclusive' => false,
			'fallback_refresh'    => false,
			'render_callback'     => function () {
				return ogh_css();
			},
		) );
		foreach ( $parts as $id => $pp ) {
			$cb = $pp[2];
			$wp->selective_refresh->add_partial( $id, array(
				'selector'            => $pp[0],
				'settings'            => array_map( function ( $k ) {
					return 'orego_' . $k;
				}, $pp[1] ),
				'container_inclusive' => true,
				'fallback_refresh'    => true,
				'render_callback'     => function () use ( $cb ) {
					return $cb ? call_user_func( $cb ) : false;
				},
			) );
		}
	}
}, 30 );

/* No pencil icons on the CSS-only partials (they live in <head>). */
add_action( 'customize_preview_init', function () {
	add_action( 'wp_footer', function () {
		echo '<script>(function(api){if(!api||!api.selectiveRefresh)return;api.selectiveRefresh.bind("partial-content-rendered",function(){});api.bind("preview-ready",function(){["ogh_css_main","ogh_css_parts"].forEach(function(id){var p=api.selectiveRefresh.partial(id);if(p){p.createEditShortcutForPlacement=function(){}}})})})(window.wp&&wp.customize);</script>';
	}, 99 );
} );

/* Appearance menu: Header + Footer shortcuts */
add_action( 'admin_menu', function () {
	global $submenu;
	if ( ! isset( $submenu['themes.php'] ) || ! current_user_can( 'customize' ) ) {
		return;
	}
	foreach ( $submenu['themes.php'] as $i => $row ) {
		if ( isset( $row[2] ) && false !== strpos( $row[2], 'autofocus[panel]=orego_chrome' ) ) {
			$submenu['themes.php'][ $i ][0] = orego_L( 'Footer', 'الفوتر' ); // phpcs:ignore
		}
	}
	$submenu['themes.php'][] = array( orego_L( 'Header', 'الهيدر' ), 'customize', 'customize.php?autofocus[section]=ogh_builder', orego_L( 'Header', 'الهيدر' ) ); // phpcs:ignore
}, 1003 );
