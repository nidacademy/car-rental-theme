<?php
/**
 * Exotic Cars: Appearance -> Customize -> Header & Footer
 * Every option here feeds [orego_header] (orego-chrome.php). The footer has its own builder (orego-footer-builder.php).
 * Defaults reproduce the current design exactly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_mod_defaults() {
	return array(
		/* logo */
		'logo_image' => 0, 'logo_text' => '', 'logo_tagline' => '', 'logo_icon' => false, 'logo_height' => 44, 'logo_size' => 23,
		/* header size */
		'header_height' => 72, 'header_height_m' => 64, 'header_pad_x' => 72, 'header_width' => 'full', 'header_max' => 1320, 'header_shadow' => true, 'header_border' => false,
		/* header style */
		'header_style' => 'glass', 'header_overlay' => true, 'header_search' => true,
		/* header colors & visibility */
		'header_bg' => '', 'header_fg' => '#FFFFFF', 'nav_hover' => '', 'header_sticky' => true, 'show_nav' => true, 'show_lang' => true, 'show_currency' => true,
		/* menu links */
		'nav_align' => 'center', 'nav_size' => 15, 'nav_weight' => '600', 'nav_gap' => 6, 'nav_pad_x' => 14, 'nav_radius' => 10, 'nav_style' => 'pill',
		/* button & switchers */
		'cta_show' => true, 'cta_text' => 'Book now', 'cta_url' => '', 'cta_new_tab' => false, 'cta_bg' => '#FFFFFF', 'cta_fg' => '', 'cta_height' => 40, 'cta_radius' => 12, 'pill_style' => 'both',
		/* language button (desktop / mobile) */
		'lang_show_d' => true, 'lang_show_m' => true, 'lang_code_d' => true, 'lang_code_m' => true, 'lang_flag_d' => 24, 'lang_flag_m' => 24, 'lang_h_d' => 40, 'lang_h_m' => 38, 'lang_font_d' => 14, 'lang_font_m' => 14, 'lang_radius' => 12, 'lang_fg' => '', 'lang_bg' => '#FFFFFF', 'lang_bg_op' => 10, 'lang_pos' => 'first', 'lang_offset_d' => 0, 'lang_offset_m' => 0, 'burger_d' => 42, 'burger_m' => 40, 'drawer_langs' => true,
		/* mobile menu */
		'drawer_side' => 'start', 'drawer_width' => 420, 'drawer_font' => 19, 'drawer_cta' => true,
		/* promo */
		'promo_show' => true, 'promo_text' => 'Flexible on the car? Book a category and save: economy from $49 a day and we pick the car for you →', 'promo_url' => '', 'promo_bg' => '', 'promo_fg' => '#FFFFFF', 'promo_size' => 14, 'promo_pad_y' => 9, 'promo_align' => 'center',
		/* footer copyright (read by the footer builder) */
		'copyright' => '© {year} ' . orego_brand() . '. All rights reserved.',
	);
}

function orego_mod( $key ) {
	$d = orego_mod_defaults();
	return get_theme_mod( 'orego_' . $key, $d[ $key ] ?? '' );
}

function orego_cta_url() {
	$u = orego_mod( 'cta_url' );
	return $u ? $u : orego_purl( 'cars' );
}

function orego_promo_html() {
	if ( has_filter( 'orego_promo_html' ) ) {
		return (string) apply_filters( 'orego_promo_html', '' ); // desktop + phone versions (orego-pro-device-split.php)
	}
	$t = trim( (string) orego_mod( 'promo_text' ) );
	if ( ! orego_mod( 'promo_show' ) || '' === $t ) {
		return '';
	}
	$u = orego_mod( 'promo_url' );
	$t = esc_html( $t );
	return '<div class="ogx2-promo">' . ( $u ? '<a href="' . esc_url( $u ) . '">' . $t . '</a>' : '<span>' . $t . '</span>' ) . '</div>';
}

function orego_header_classes() {
	$c = array( 'ogx2' );
	if ( 'glass' === orego_mod( 'header_style' ) ) {
		$c[] = 'ogx2-glass';
		// Transparent over the photo hero: home, About and Contact pages.
		if ( orego_mod( 'header_overlay' ) && ( is_front_page() || is_page( array( 'about-us', 'contact-us' ) ) ) ) {
			$c[] = 'is-overlay';
		}
	}
	if ( ! orego_mod( 'show_nav' ) ) {
		$c[] = 'no-links';
	}
	if ( ! orego_mod( 'header_sticky' ) ) {
		$c[] = 'not-sticky';
	}
	return implode( ' ', $c );
}

/* ------------------------------------------------------------------ */
/* CSS from the settings                                               */
/* ------------------------------------------------------------------ */

function orego_chrome_dynamic_css() {
	$m   = 'orego_mod';
	$n   = function ( $k ) use ( $m ) {
		return (int) $m( $k );
	};
	$col = function ( $k ) use ( $m ) {
		return sanitize_hex_color( (string) $m( $k ) );
	};
	$css = ':root{';
	foreach ( array( '--ogh-bg' => 'header_bg', '--og-teal' => 'header_bg', '--ogh-fg' => 'header_fg', '--ogp-bg' => 'promo_bg', '--ogp-fg' => 'promo_fg' ) as $var => $k ) {
		if ( $col( $k ) ) {
			$css .= $var . ':' . $col( $k ) . ';';
		}
	}
	$css .= '}';

	/* header size (also shared as --ogh-h for sticky elements) */
	$css .= ':root{--ogh-h:' . ( $m( 'header_sticky' ) ? $n( 'header_height' ) : 0 ) . 'px;--ogh-hh:' . $n( 'header_height' ) . 'px}@media (max-width:640px){:root{--ogh-h:' . ( $m( 'header_sticky' ) ? $n( 'header_height_m' ) : 0 ) . 'px;--ogh-hh:' . $n( 'header_height_m' ) . 'px}}';
	$css .= '.ogx2-in{height:' . $n( 'header_height' ) . 'px;padding-inline:clamp(16px,4.5vw,' . $n( 'header_pad_x' ) . 'px)}';
	if ( 'boxed' === $m( 'header_width' ) ) {
		$css .= '.ogx2-in{max-width:' . $n( 'header_max' ) . 'px;margin-inline:auto}';
	}
	if ( ! $m( 'header_shadow' ) ) {
		$css .= '.ogx2{box-shadow:none}';
	}
	if ( $m( 'header_border' ) ) {
		$css .= '.ogx2{border-bottom:1px solid color-mix(in srgb,var(--ogh-fg,#fff) 16%,transparent)}';
	}
	$css .= '@media (max-width:640px){.ogx2-in{height:' . $n( 'header_height_m' ) . 'px}.ogx2-dd .ogx2-panel{top:calc(' . $n( 'header_height_m' ) . 'px + var(--wp-admin--admin-bar--height,0px) + 8px)}}';

	/* logo */
	$css .= '.ogx2-logoimg{height:' . $n( 'logo_height' ) . 'px}.ogx2-word b{font-size:' . $n( 'logo_size' ) . 'px}';
	$css .= '@media (max-width:640px){.ogx2-logoimg{height:' . max( 24, round( $n( 'logo_height' ) * .82 ) ) . 'px}.ogx2-word b{font-size:' . max( 14, round( $n( 'logo_size' ) * .83 ) ) . 'px}}';

	/* menu links */
	$css .= '.ogx2-links ul{gap:' . $n( 'nav_gap' ) . 'px}.ogx2-links li>a{font-size:' . $n( 'nav_size' ) . 'px;font-weight:' . (int) $m( 'nav_weight' ) . ';padding:0 ' . $n( 'nav_pad_x' ) . 'px;border-radius:' . $n( 'nav_radius' ) . 'px}';
	if ( 'underline' === $m( 'nav_style' ) || 'plain' === $m( 'nav_style' ) ) {
		$css .= '.ogx2-links li>a,.ogx2-links li>a:hover,.ogx2-links li:focus-within>a,.ogx2-links li.is-current>a{background:none!important}';
	}
	if ( 'underline' === $m( 'nav_style' ) ) {
		$css .= '.ogx2-links li>a{position:relative}.ogx2-links li>a::after{content:"";position:absolute;inset-inline:' . $n( 'nav_pad_x' ) . 'px;bottom:4px;height:2px;border-radius:2px;background:currentColor;transform:scaleX(0);transition:transform .2s}.ogx2-links li>a:hover::after,.ogx2-links li.is-current>a::after{transform:scaleX(1)}';
	}
	if ( $col( 'nav_hover' ) ) {
		$css .= '.ogx2-links li>a:hover,.ogx2-links li:focus-within>a,.ogx2-links li.is-current>a{color:' . $col( 'nav_hover' ) . '!important;opacity:1}';
	}
	if ( 'start' === $m( 'nav_align' ) || 'end' === $m( 'nav_align' ) ) {
		$css .= '@media (min-width:1025px){.ogx2:not(.no-links) .ogx2-in{display:flex}' . ( 'start' === $m( 'nav_align' ) ? '.ogx2-links{margin-inline-start:32px}.ogx2-acts{margin-inline-start:auto}' : '.ogx2-links{margin-inline-start:auto}.ogx2-acts{margin-inline-start:20px}' ) . '}';
	}

	/* button & switchers */
	$css .= '.ogx2-acts .ogx2-cta{height:' . $n( 'cta_height' ) . 'px;border-radius:' . $n( 'cta_radius' ) . 'px' . ( $col( 'cta_bg' ) ? ';background:' . $col( 'cta_bg' ) : '' ) . ( $col( 'cta_fg' ) ? ';color:' . $col( 'cta_fg' ) . '!important' : '' ) . '}.ogx2-acts .ogx2-cta:hover{filter:brightness(.94)}';
	if ( 'flag' === $m( 'pill_style' ) ) {
		$css .= '.ogx2-pill b{display:none}';
	} elseif ( 'code' === $m( 'pill_style' ) ) {
		$css .= '.ogx2-pill .ogx2-flag{display:none}.ogx2-pill{padding-inline:12px 10px}';
	}

	/* mobile menu */
	$css .= '.ogx2-drawer{width:min(' . $n( 'drawer_width' ) . 'px,100vw)}.ogx2-row>a{font-size:' . $n( 'drawer_font' ) . 'px}';
	if ( 'end' === $m( 'drawer_side' ) ) {
		$css .= '.ogx2-drawer{inset-inline-start:auto;inset-inline-end:0;transform:translateX(calc(100% * var(--og-dir)));border-start-end-radius:0;border-end-end-radius:0;border-start-start-radius:22px;border-end-start-radius:22px}.ogx2-drawer.is-open{transform:none}';
	}

	/* promo */
	$css .= '.ogx2-promo{font-size:' . $n( 'promo_size' ) . 'px;padding-block:' . $n( 'promo_pad_y' ) . 'px;text-align:' . ( 'start' === $m( 'promo_align' ) ? 'start' : 'center' ) . '}';
	if ( 'start' === $m( 'promo_align' ) ) {
		$css .= '.ogx2-promo{padding-inline:clamp(16px,4.5vw,' . $n( 'header_pad_x' ) . 'px)}';
	}

	/* language button (desktop / mobile) */
	$lg  = '.ogx2-dd[data-dd=lang]';
	$bg  = $col( 'lang_bg' ) ? $col( 'lang_bg' ) : '#FFFFFF';
	$rgb = sscanf( ltrim( $bg, '#' ), '%02x%02x%02x' );
	$op  = max( 0, min( 100, $n( 'lang_bg_op' ) ) ) / 100;
	$css .= $lg . ' .ogx2-pill{border-radius:' . $n( 'lang_radius' ) . 'px;background:rgba(' . implode( ',', $rgb ) . ',' . $op . ')' . ( $col( 'lang_fg' ) ? ';color:' . $col( 'lang_fg' ) : '' ) . '}';
	$css .= $lg . ' .ogx2-pill:hover,' . $lg . ' .ogx2-pill[aria-expanded=true]{background:rgba(' . implode( ',', $rgb ) . ',' . min( 1, $op + .08 ) . ')}';
	$css .= $lg . '{order:' . ( 'first' === $m( 'lang_pos' ) ? 1 : 2 ) . '}.ogx2-dd[data-dd=cur]{order:' . ( 'first' === $m( 'lang_pos' ) ? 2 : 1 ) . '}.ogx2-acts .ogx2-cta{order:3}.ogx2-burger{order:4}';
	$css .= '@media (min-width:641px){' . $lg . ' .ogx2-pill{height:' . $n( 'lang_h_d' ) . 'px;font-size:' . $n( 'lang_font_d' ) . 'px}' . $lg . ' .ogx2-pill .ogx2-flag{width:' . $n( 'lang_flag_d' ) . 'px;height:' . $n( 'lang_flag_d' ) . 'px}' . $lg . '{margin-inline-start:' . $n( 'lang_offset_d' ) . 'px}' . ( $m( 'lang_code_d' ) ? '' : $lg . ' .ogx2-pill b{display:none}' ) . ( $m( 'lang_show_d' ) ? '' : $lg . '{display:none}' ) . '.ogx2-burger{width:' . $n( 'burger_d' ) . 'px;height:' . $n( 'burger_d' ) . 'px}}';
	$css .= '@media (max-width:640px){' . $lg . ' .ogx2-pill{height:' . $n( 'lang_h_m' ) . 'px;font-size:' . $n( 'lang_font_m' ) . 'px}' . $lg . ' .ogx2-pill .ogx2-flag{width:' . $n( 'lang_flag_m' ) . 'px;height:' . $n( 'lang_flag_m' ) . 'px}' . $lg . '{margin-inline-start:' . $n( 'lang_offset_m' ) . 'px}' . ( $m( 'lang_code_m' ) ? $lg . ' .ogx2-pill b{display:inline}' : $lg . ' .ogx2-pill b{display:none}' ) . ( $m( 'lang_show_m' ) ? '' : $lg . '{display:none}' ) . '.ogx2-burger{width:' . $n( 'burger_m' ) . 'px;height:' . $n( 'burger_m' ) . 'px}}';
	return $css;
}

add_action( 'wp_head', function () {
	echo '<style id="orego-chrome-vars">' . orego_chrome_dynamic_css() . '</style>' . "\n"; // phpcs:ignore
}, 40 );

/* ------------------------------------------------------------------ */
/* The Customizer panel                                                */
/* ------------------------------------------------------------------ */

add_action( 'customize_register', function ( $wp ) {
	$L = 'orego_L';
	$d = orego_mod_defaults();

	$add = function ( $key, $section, $label, $type, $extra = array() ) use ( $wp, $d ) {
		$choices = $extra['choices'] ?? array();
		$min     = $extra['input_attrs']['min'] ?? 0;
		$max     = $extra['input_attrs']['max'] ?? 9999;
		$san     = array(
			'color'    => 'sanitize_hex_color',
			'checkbox' => function ( $v ) {
				return (bool) $v;
			},
			'url'      => 'esc_url_raw',
			'text'     => 'sanitize_text_field',
			'textarea' => 'sanitize_text_field',
			'html'     => 'wp_kses_post',
			'image'    => 'absint',
			'number'   => function ( $v ) use ( $min, $max, $d, $key ) {
				return is_numeric( $v ) ? max( $min, min( $max, (int) $v ) ) : $d[ $key ];
			},
			'select'   => function ( $v ) use ( $choices, $d, $key ) {
				return array_key_exists( $v, $choices ) ? $v : $d[ $key ];
			},
		);
		$san['radio'] = $san['select'];
		$wp->add_setting( 'orego_' . $key, array( 'default' => $d[ $key ], 'sanitize_callback' => $san[ $type ], 'transport' => 'refresh' ) );
		$args = array_merge( array( 'label' => $label, 'section' => $section, 'settings' => 'orego_' . $key ), $extra );
		if ( 'color' === $type ) {
			$wp->add_control( new WP_Customize_Color_Control( $wp, 'orego_' . $key, $args ) );
		} elseif ( 'image' === $type ) {
			$wp->add_control( new WP_Customize_Media_Control( $wp, 'orego_' . $key, array_merge( $args, array( 'mime_type' => 'image' ) ) ) );
		} else {
			$wp->add_control( 'orego_' . $key, array_merge( $args, array( 'type' => 'html' === $type ? 'text' : $type ) ) );
		}
	};
	$px = function ( $min, $max, $step = 1 ) {
		return array( 'input_attrs' => array( 'min' => $min, 'max' => $max, 'step' => $step ) );
	};
	$desc = function ( $en, $ar ) use ( $L ) {
		return array( 'description' => $L( $en, $ar ) );
	};
	$weights = array( '400' => $L( 'Regular', 'عادي' ), '500' => $L( 'Medium', 'متوسط' ), '600' => $L( 'Semi-bold', 'شبه عريض' ), '700' => $L( 'Bold', 'عريض' ), '800' => $L( 'Extra bold', 'عريض جدًا' ) );
	$widths  = array( 'full' => $L( 'Full width', 'عرض كامل' ), 'boxed' => $L( 'Boxed (max width)', 'محدود (أقصى عرض)' ) );

	$wp->add_panel( 'orego_chrome', array(
		'title'       => $L( 'Header & Footer', 'الهيدر والفوتر' ),
		'description' => $L( 'Change the website header, menu, promo bar and footer. Sizes are in pixels. Changes show in the preview before you publish them; to undo a field, use its Default button or retype the default shown in its description.', 'غيّر هيدر الموقع والقائمة وشريط العروض والفوتر. المقاسات بالبكسل. تظهر التغييرات في المعاينة قبل نشرها.' ),
		'priority'    => 25,
	) );
	$sections = array(
		'orego_h_layout' => $L( 'Header: size & spacing', 'الهيدر: الحجم والمسافات' ),
		'orego_h_logo'   => $L( 'Header: logo', 'الهيدر: الشعار' ),
		'orego_header'   => $L( 'Header: colors & visibility', 'الهيدر: الألوان والإظهار' ),
		'orego_h_nav'    => $L( 'Header: menu links', 'الهيدر: روابط القائمة' ),
		'orego_h_btn'    => $L( 'Header: button & switchers', 'الهيدر: الزر والمبدّلات' ),
		'orego_h_lang'   => $L( 'Header: language button', 'الهيدر: زر اللغة' ),
		'orego_drawer'   => $L( 'Mobile menu', 'قائمة الجوال' ),
		'orego_promo'    => $L( 'Promo bar', 'شريط العروض' ),
		'orego_footer'   => $L( 'Footer: content & colors', 'الفوتر: المحتوى والألوان' ),
	);
	$p = 10;
	foreach ( $sections as $id => $title ) {
		$wp->add_section( $id, array( 'title' => $title, 'panel' => 'orego_chrome', 'priority' => $p += 10 ) );
	}

	/* Header: size & spacing */
	$add( 'header_height', 'orego_h_layout', $L( 'Height on desktop', 'الارتفاع على الكمبيوتر' ), 'number', $px( 48, 140 ) + $desc( 'Default 72', 'الافتراضي 72' ) );
	$add( 'header_height_m', 'orego_h_layout', $L( 'Height on phones', 'الارتفاع على الجوال' ), 'number', $px( 44, 110 ) + $desc( 'Default 64', 'الافتراضي 64' ) );
	$add( 'header_pad_x', 'orego_h_layout', $L( 'Side padding (largest)', 'المسافة الجانبية (الأقصى)' ), 'number', $px( 0, 200 ) + $desc( 'Shrinks on smaller screens. Default 72', 'تصغر على الشاشات الأصغر. الافتراضي 72' ) );
	$add( 'header_width', 'orego_h_layout', $L( 'Content width', 'عرض المحتوى' ), 'radio', array( 'choices' => $widths ) );
	$add( 'header_max', 'orego_h_layout', $L( 'Max width when boxed', 'أقصى عرض عند التحديد' ), 'number', $px( 800, 2000, 10 ) + $desc( 'Default 1320', 'الافتراضي 1320' ) );
	$add( 'header_shadow', 'orego_h_layout', $L( 'Soft shadow under the header', 'ظل خفيف أسفل الهيدر' ), 'checkbox' );
	$add( 'header_border', 'orego_h_layout', $L( 'Thin line under the header', 'خط رفيع أسفل الهيدر' ), 'checkbox' );

	/* Header: logo */
	$add( 'logo_image', 'orego_h_logo', $L( 'Logo image', 'صورة الشعار' ), 'image', $desc( 'Optional. A light (white) logo looks best on a dark header. Leave empty to use the text logo.', 'اختياري. الشعار الفاتح يبدو أفضل على هيدر داكن. اتركه فارغًا لاستخدام الشعار النصي.' ) );
	$add( 'logo_height', 'orego_h_logo', $L( 'Logo image height', 'ارتفاع صورة الشعار' ), 'number', $px( 20, 120 ) + $desc( 'Default 44', 'الافتراضي 44' ) );
	$add( 'logo_text', 'orego_h_logo', $L( 'Logo text', 'نص الشعار' ), 'text', $desc( 'Shown only when there is no logo image. Empty = your site title.', 'يظهر فقط عند عدم وجود صورة شعار. فارغ = اسم الموقع.' ) );
	$add( 'logo_size', 'orego_h_logo', $L( 'Logo text size', 'حجم نص الشعار' ), 'number', $px( 12, 48 ) + $desc( 'Default 23', 'الافتراضي 23' ) );
	$add( 'logo_tagline', 'orego_h_logo', $L( 'Tagline under the logo', 'العبارة تحت الشعار' ), 'text', $desc( 'Leave empty to hide it.', 'اتركها فارغة لإخفائها.' ) );
	$add( 'logo_icon', 'orego_h_logo', $L( 'Show the car icon next to the text logo', 'إظهار أيقونة السيارة بجانب الشعار النصي' ), 'checkbox' );

	/* Header: colors & visibility */
	$add( 'header_style', 'orego_header', $L( 'Header style', 'شكل الهيدر' ), 'radio', array( 'choices' => array( 'glass' => $L( 'Clean: plain links, search box, globe language button', 'نظيف: روابط بسيطة ومربع بحث وزر لغة بأيقونة الكرة' ), 'classic' => $L( 'Classic: coloured bar with pill buttons', 'كلاسيكي: شريط ملوّن مع أزرار دائرية' ) ) ) );
	$add( 'header_overlay', 'orego_header', $L( 'Clean style: transparent over the homepage photo', 'الشكل النظيف: شفاف فوق صورة الصفحة الرئيسية' ), 'checkbox', $desc( 'Turns solid when the visitor scrolls. Other pages always use the solid header.', 'يصبح ملوّنًا عند التمرير. باقي الصفحات تستخدم الهيدر الملوّن دائمًا.' ) );
	$add( 'header_search', 'orego_header', $L( 'Show the car search box (desktop)', 'إظهار مربع البحث عن السيارات (الكمبيوتر)' ), 'checkbox' );
	$add( 'header_bg', 'orego_header', $L( 'Background color (empty = theme primary)', 'لون الخلفية' ), 'color', $desc( 'Also used for the menu drawer and highlights. Dark colors work best.', 'يُستخدم أيضًا للقائمة الجانبية والتمييز. الألوان الداكنة هي الأنسب.' ) );
	$add( 'header_fg', 'orego_header', $L( 'Text color', 'لون النص' ), 'color' );
	$add( 'nav_hover', 'orego_header', $L( 'Menu link color on hover / current page', 'لون رابط القائمة عند المرور / الصفحة الحالية' ), 'color', $desc( 'Optional. Empty = same as the text color.', 'اختياري. فارغ = نفس لون النص.' ) );
	$add( 'header_sticky', 'orego_header', $L( 'Keep the header at the top while scrolling', 'تثبيت الهيدر أعلى الصفحة أثناء التمرير' ), 'checkbox' );
	$add( 'show_nav', 'orego_header', $L( 'Show the menu links on desktop', 'إظهار روابط القائمة على الكمبيوتر' ), 'checkbox', $desc( 'Edit the links in Menus → Main Menu. When off, the ☰ button is used on every screen.', 'عدّل الروابط من القوائم ← Main Menu. عند إيقافها يُستخدم زر ☰ على كل الشاشات.' ) );
	$add( 'show_lang', 'orego_header', $L( 'Show the language switcher', 'إظهار مبدّل اللغة' ), 'checkbox' );
	$add( 'show_currency', 'orego_header', $L( 'Show the currency switcher', 'إظهار مبدّل العملة' ), 'checkbox' );

	/* Header: menu links */
	$add( 'nav_align', 'orego_h_nav', $L( 'Position of the links', 'موضع الروابط' ), 'radio', array( 'choices' => array( 'start' => $L( 'Next to the logo', 'بجانب الشعار' ), 'center' => $L( 'Centered', 'في المنتصف' ), 'end' => $L( 'Next to the buttons', 'بجانب الأزرار' ) ) ) );
	$add( 'nav_style', 'orego_h_nav', $L( 'Hover & current page style', 'شكل المرور والصفحة الحالية' ), 'radio', array( 'choices' => array( 'pill' => $L( 'Soft background', 'خلفية خفيفة' ), 'underline' => $L( 'Underline', 'خط سفلي' ), 'plain' => $L( 'Text only', 'نص فقط' ) ) ) );
	$add( 'nav_size', 'orego_h_nav', $L( 'Font size', 'حجم الخط' ), 'number', $px( 11, 24 ) + $desc( 'Default 15', 'الافتراضي 15' ) );
	$add( 'nav_weight', 'orego_h_nav', $L( 'Font weight', 'سُمك الخط' ), 'select', array( 'choices' => $weights ) );
	$add( 'nav_gap', 'orego_h_nav', $L( 'Space between links', 'المسافة بين الروابط' ), 'number', $px( 0, 60 ) + $desc( 'Default 6', 'الافتراضي 6' ) );
	$add( 'nav_pad_x', 'orego_h_nav', $L( 'Padding inside each link', 'الحشو داخل كل رابط' ), 'number', $px( 0, 40 ) + $desc( 'Default 14', 'الافتراضي 14' ) );
	$add( 'nav_radius', 'orego_h_nav', $L( 'Link corner roundness', 'استدارة زوايا الرابط' ), 'number', $px( 0, 30 ) + $desc( 'Default 10', 'الافتراضي 10' ) );

	/* Header: button & switchers */
	$add( 'cta_show', 'orego_h_btn', $L( 'Show the button', 'إظهار الزر' ), 'checkbox' );
	$add( 'cta_text', 'orego_h_btn', $L( 'Button text', 'نص الزر' ), 'text' );
	$add( 'cta_url', 'orego_h_btn', $L( 'Button link', 'رابط الزر' ), 'url', $desc( 'Leave empty to open the Browse Cars page.', 'اتركه فارغًا لفتح صفحة السيارات.' ) );
	$add( 'cta_new_tab', 'orego_h_btn', $L( 'Open the link in a new tab', 'فتح الرابط في لسان جديد' ), 'checkbox' );
	$add( 'cta_bg', 'orego_h_btn', $L( 'Button background', 'خلفية الزر' ), 'color' );
	$add( 'cta_fg', 'orego_h_btn', $L( 'Button text color', 'لون نص الزر' ), 'color', $desc( 'Empty = the header background color.', 'فارغ = لون خلفية الهيدر.' ) );
	$add( 'cta_height', 'orego_h_btn', $L( 'Button height', 'ارتفاع الزر' ), 'number', $px( 28, 64 ) + $desc( 'Default 40', 'الافتراضي 40' ) );
	$add( 'cta_radius', 'orego_h_btn', $L( 'Button corner roundness', 'استدارة زوايا الزر' ), 'number', $px( 0, 40 ) + $desc( 'Default 12 (40 = fully round)', 'الافتراضي 12 (40 = دائري بالكامل)' ) );
	$add( 'pill_style', 'orego_h_btn', $L( 'Language & currency buttons show', 'ما يظهر في أزرار اللغة والعملة' ), 'radio', array( 'choices' => array( 'both' => $L( 'Flag and code', 'العلم والرمز' ), 'flag' => $L( 'Flag only', 'العلم فقط' ), 'code' => $L( 'Code only', 'الرمز فقط' ) ) ) );

	/* Header: language button */
	$dm = function ( $en, $ar ) use ( $L ) {
		return $L( $en, $ar );
	};
	$add( 'lang_pos', 'orego_h_lang', $L( 'Position', 'الموضع' ), 'radio', array( 'choices' => array( 'first' => $L( 'Before the currency button', 'قبل زر العملة' ), 'after' => $L( 'After the currency button', 'بعد زر العملة' ) ) ) );
	$add( 'lang_fg', 'orego_h_lang', $L( 'Text color', 'لون النص' ), 'color', $desc( 'Empty = header text color.', 'فارغ = لون نص الهيدر.' ) );
	$add( 'lang_bg', 'orego_h_lang', $L( 'Background color', 'لون الخلفية' ), 'color' );
	$add( 'lang_bg_op', 'orego_h_lang', $L( 'Background opacity (%)', 'شفافية الخلفية (٪)' ), 'number', $px( 0, 100 ) + $desc( '0 = transparent, 100 = solid. Default 10', '0 = شفاف، 100 = مصمت. الافتراضي 10' ) );
	$add( 'lang_radius', 'orego_h_lang', $L( 'Corner roundness', 'استدارة الزوايا' ), 'number', $px( 0, 40 ) + $desc( 'Default 12', 'الافتراضي 12' ) );
	foreach ( array( 'd' => $L( 'Desktop', 'الكمبيوتر' ), 'm' => $L( 'Mobile', 'الجوال' ) ) as $k => $dev ) {
		$add( 'lang_show_' . $k, 'orego_h_lang', $dev . ': ' . $L( 'show the language button', 'إظهار زر اللغة' ), 'checkbox' );
		$add( 'lang_code_' . $k, 'orego_h_lang', $dev . ': ' . $L( 'show the code (AR, FR…)', 'إظهار الرمز (AR, FR…)' ), 'checkbox' );
		$add( 'lang_flag_' . $k, 'orego_h_lang', $dev . ': ' . $L( 'flag size', 'حجم العلم' ), 'number', $px( 14, 44 ) + $desc( 'Default 24', 'الافتراضي 24' ) );
		$add( 'lang_h_' . $k, 'orego_h_lang', $dev . ': ' . $L( 'button height', 'ارتفاع الزر' ), 'number', $px( 24, 64 ) + $desc( 'm' === $k ? 'Default 38' : 'Default 40', 'm' === $k ? 'الافتراضي 38' : 'الافتراضي 40' ) );
		$add( 'lang_font_' . $k, 'orego_h_lang', $dev . ': ' . $L( 'text size', 'حجم النص' ), 'number', $px( 10, 22 ) + $desc( 'Default 14', 'الافتراضي 14' ) );
		$add( 'lang_offset_' . $k, 'orego_h_lang', $dev . ': ' . $L( 'move sideways (px)', 'تحريك جانبي (بكسل)' ), 'number', $px( -80, 80 ) + $desc( 'Negative moves it the other way. Default 0', 'القيمة السالبة تحرّكه في الاتجاه الآخر. الافتراضي 0' ) );
		$add( 'burger_' . $k, 'orego_h_lang', $dev . ': ' . $L( 'menu (☰) button size', 'حجم زر القائمة (☰)' ), 'number', $px( 30, 64 ) + $desc( 'm' === $k ? 'Default 40' : 'Default 42', 'm' === $k ? 'الافتراضي 40' : 'الافتراضي 42' ) );
	}
	$add( 'drawer_langs', 'orego_h_lang', $L( 'Show the language row inside the menu', 'إظهار صف اللغات داخل القائمة' ), 'checkbox', $desc( 'Turn languages on/off and pick the default in Appearance → Languages.', 'فعّل اللغات أو أوقفها واختر الافتراضية من المظهر ← اللغات.' ) );

	/* Mobile menu */
	$add( 'drawer_side', 'orego_drawer', $L( 'Slides in from', 'ينزلق من' ), 'radio', array( 'choices' => array( 'start' => $L( 'The logo side', 'جهة الشعار' ), 'end' => $L( 'The menu button side', 'جهة زر القائمة' ) ) ) );
	$add( 'drawer_width', 'orego_drawer', $L( 'Width on larger screens', 'العرض على الشاشات الأكبر' ), 'number', $px( 280, 640 ) + $desc( 'Phones always use the full width. Default 420', 'الجوال يستخدم العرض الكامل دائمًا. الافتراضي 420' ) );
	$add( 'drawer_font', 'orego_drawer', $L( 'Link size', 'حجم الروابط' ), 'number', $px( 13, 30 ) + $desc( 'Default 19', 'الافتراضي 19' ) );
	$add( 'drawer_cta', 'orego_drawer', $L( 'Show the button at the bottom of the menu', 'إظهار الزر أسفل القائمة' ), 'checkbox' );

	/* Promo bar */
	$add( 'promo_show', 'orego_promo', $L( 'Show the promo bar', 'إظهار شريط العروض' ), 'checkbox' );
	$add( 'promo_text', 'orego_promo', $L( 'Text', 'النص' ), 'textarea' );
	$add( 'promo_url', 'orego_promo', $L( 'Link (optional)', 'الرابط (اختياري)' ), 'url' );
	$add( 'promo_bg', 'orego_promo', $L( 'Background color', 'لون الخلفية' ), 'color' );
	$add( 'promo_fg', 'orego_promo', $L( 'Text color', 'لون النص' ), 'color' );
	$add( 'promo_size', 'orego_promo', $L( 'Text size', 'حجم النص' ), 'number', $px( 10, 22 ) + $desc( 'Default 14', 'الافتراضي 14' ) );
	$add( 'promo_pad_y', 'orego_promo', $L( 'Space above and below the text', 'المسافة فوق النص وتحته' ), 'number', $px( 2, 30 ) + $desc( 'Default 9', 'الافتراضي 9' ) );
	$add( 'promo_align', 'orego_promo', $L( 'Text alignment', 'محاذاة النص' ), 'radio', array( 'choices' => array( 'center' => $L( 'Centered', 'في المنتصف' ), 'start' => $L( 'Aligned with the logo', 'محاذاة الشعار' ) ) ) );

	/* Footer copyright text: the footer builder (Customize -> Footer) uses it */
	$add( 'copyright', 'orego_footer', $L( 'Copyright text', 'نص حقوق النشر' ), 'html', $desc( '{year} is replaced with the current year. Leave empty to hide it.', 'يُستبدل {year} بالسنة الحالية. اتركه فارغًا لإخفائه.' ) );
} );

/* Shortcut in the Appearance menu */
add_action( 'admin_menu', function () {
	global $submenu;
	if ( isset( $submenu['themes.php'] ) && current_user_can( 'customize' ) ) {
		$submenu['themes.php'][] = array( orego_L( 'Header & Footer', 'الهيدر والفوتر' ), 'customize', 'customize.php?autofocus[panel]=orego_chrome', orego_L( 'Header & Footer', 'الهيدر والفوتر' ) ); // phpcs:ignore
	}
}, 1002 );

/* Purge the page cache after publishing changes */
add_action( 'customize_save_after', function () {
	do_action( 'litespeed_purge_all' );
} );
