<?php
/**
 * Appearance -> Customize -> "Welcome & language pages":
 * pictures for the welcome (swipe) page and the language page, with separate
 * settings for desktop and for phones & tablets.
 * - Welcome page: desktop layout from 960px, phone layout below.
 * - Language page: desktop layout from 1025px, phone layout below.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Settings with their defaults. */
function orego_intro_defaults() {
	return array(
		// desktop
		'og_welcome_bg'    => 0,               // background photo (0 = light design with the car picture)
		'og_welcome_dim'   => 55,              // how dark the photo gets behind the text (%)
		'og_welcome_car'   => orego_aid( 'car' ),
		'og_welcome_tone'  => 'auto',            // text on the photo: auto, dark or light (white)              // car picture used without a photo
		'og_lang_bg'       => orego_aid( 'lang_bg' ),             // language page photo
		'og_lang_dim'      => 82,              // how dark the left side gets behind the list (%)
		// phones & tablets
		'og_welcome_bg_m'  => 0,
		'og_welcome_pos_m' => 'right center',  // which part of the photo shows
		'og_welcome_dim_m' => 55,
		'og_welcome_car_m' => orego_aid( 'car' ),
		'og_welcome_tone_m' => 'auto',
		'og_lang_bg_m'     => 0,               // 0 = the white language page
		'og_lang_pos_m'    => 'right center',
		'og_lang_dim_m'    => 70,
	);
}

function orego_intro_mod( $key ) {
	$def = orego_intro_defaults();
	return get_theme_mod( $key, $def[ $key ] ?? '' );
}

function orego_intro_img( $key, $size = 'full' ) {
	$id = absint( orego_intro_mod( $key ) );
	return $id ? (string) wp_get_attachment_image_url( $id, $size ) : '';
}

/** Does the welcome page use a photo on desktop / on phones? */
function orego_intro_photo_d() {
	return '' !== orego_intro_img( 'og_welcome_bg' );
}
function orego_intro_photo_m() {
	return '' !== orego_intro_img( 'og_welcome_bg_m' );
}
/** Kept for older code: a photo on either screen. */
function orego_intro_has_photo() {
	return orego_intro_photo_d() || orego_intro_photo_m();
}

function orego_intro_num( $n ) {
	return rtrim( rtrim( number_format( (float) $n, 3, '.', '' ), '0' ), '.' );
}

function orego_intro_pos( $v ) {
	return in_array( $v, array( 'left center', 'center center', 'right center' ), true ) ? $v : 'right center';
}

/* ---------- Customizer ---------- */
add_action( 'customize_register', function ( $wp_customize ) {
	if ( ! class_exists( 'Orego_Intro_Heading' ) ) {
		class Orego_Intro_Heading extends WP_Customize_Control {
			public $type = 'og_heading';
			public function render_content() {
				echo '<span class="customize-control-title">' . esc_html( $this->label ) . '</span>';
				if ( $this->description ) {
					echo '<span class="description customize-control-description">' . esc_html( $this->description ) . '</span>';
				}
			}
		}
		class Orego_Intro_Device_Switch extends WP_Customize_Control {
			public $type = 'og_intro_device';
			public function render_content() {
				echo '<span class="customize-control-title">Editing for</span>';
				echo '<div class="ogcd-dev ogi-dev" role="tablist"><button type="button" class="button" data-dev="desktop" aria-pressed="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg><span>Desktop</span></button><button type="button" class="button" data-dev="mobile" aria-pressed="false"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2.5" width="12" height="19" rx="2.6"/><path d="M11 18.5h2"/></svg><span>Phone</span></button></div>';
				echo '<span class="description customize-control-description">Desktop and phone have their own settings and pictures: changing one does not change the other. Tablets use the phone settings.</span>';
			}
		}
	}

	$sec = 'orego_intro';
	$wp_customize->add_section( $sec, array(
		'title'       => 'Welcome & language pages',
		'priority'    => 31,
		'description' => 'Pictures for the welcome (swipe) page and the language page. Opening a setting shows that page in the preview.',
	) );
	$wp_customize->add_setting( 'og_intro_ui_device', array( 'default' => 'desktop', 'sanitize_callback' => 'sanitize_key', 'transport' => 'postMessage' ) );
	$wp_customize->add_control( new Orego_Intro_Device_Switch( $wp_customize, 'og_intro_ui_device', array( 'section' => $sec, 'priority' => 1 ) ) );

	$d    = orego_intro_defaults();
	$prio = 10;
	$head = function ( $id, $label, $desc = '' ) use ( $wp_customize, $sec, &$prio ) {
		$wp_customize->add_control( new Orego_Intro_Heading( $wp_customize, $id, array(
			'section' => $sec, 'settings' => array(), 'label' => $label, 'description' => $desc, 'priority' => $prio++,
		) ) );
	};
	$media = function ( $id, $label, $desc ) use ( $wp_customize, $sec, $d, &$prio ) {
		$wp_customize->add_setting( $id, array( 'default' => $d[ $id ], 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
		$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, $id, array(
			'section' => $sec, 'label' => $label, 'description' => $desc, 'mime_type' => 'image', 'priority' => $prio++,
		) ) );
	};
	$range = function ( $id, $label, $min, $max ) use ( $wp_customize, $sec, $d, &$prio ) {
		$wp_customize->add_setting( $id, array( 'default' => $d[ $id ], 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
		$wp_customize->add_control( $id, array(
			'section' => $sec, 'label' => $label, 'type' => 'range', 'priority' => $prio++,
			'input_attrs' => array( 'min' => $min, 'max' => $max, 'step' => 1 ),
		) );
	};
	$pos = function ( $id, $label ) use ( $wp_customize, $sec, $d, &$prio ) {
		$wp_customize->add_setting( $id, array( 'default' => $d[ $id ], 'sanitize_callback' => 'orego_intro_pos', 'transport' => 'refresh' ) );
		$wp_customize->add_control( $id, array(
			'section' => $sec, 'label' => $label, 'type' => 'select', 'priority' => $prio++,
			'choices' => array( 'left center' => 'Left', 'center center' => 'Middle', 'right center' => 'Right' ),
		) );
	};

	$tone = function ( $id, $label ) use ( $wp_customize, $sec, $d, &$prio ) {
		$wp_customize->add_setting( $id, array( 'default' => $d[ $id ], 'transport' => 'refresh', 'sanitize_callback' => function ( $v ) {
			return in_array( $v, array( 'auto', 'dark', 'light' ), true ) ? $v : 'auto';
		} ) );
		$wp_customize->add_control( $id, array(
			'section' => $sec, 'label' => $label, 'type' => 'radio', 'priority' => $prio++,
			'description' => 'Auto looks at the left side of the photo: dark text on light photos, white text with a shade on dark photos.',
			'choices' => array( 'auto' => 'Auto (match the photo)', 'dark' => 'Dark text and logo', 'light' => 'White text and logo' ),
		) );
	};

	// Desktop
	$head( 'og_intro_h_wd', 'Welcome (swipe) page' );
	$media( 'og_welcome_bg', 'Background photo', 'Fills the whole screen behind the logo, text and swipe button. Remove it to go back to the light design with the car picture.' );
	$tone( 'og_welcome_tone', 'Text on the photo' );
	$range( 'og_welcome_dim', 'Shade behind the text', 0, 90 );
	$media( 'og_welcome_car', 'Car picture (light design)', 'Used when there is no background photo. Best with a white background, car facing left.' );
	$head( 'og_intro_h_ld', 'Language page' );
	$media( 'og_lang_bg', 'Background photo', 'The list sits on the left, so put the car on the right side of the photo.' );
	$range( 'og_lang_dim', 'Darken the left side', 30, 95 );

	// Phones & tablets
	$head( 'og_intro_h_wm', 'Welcome (swipe) page' );
	$media( 'og_welcome_bg_m', 'Background photo', 'Fills the whole screen behind the logo, text and swipe button. A tall (portrait) photo works best. Remove it to go back to the light design with the car picture.' );
	$pos( 'og_welcome_pos_m', 'Show this part of the photo' );
	$tone( 'og_welcome_tone_m', 'Text on the photo' );
	$range( 'og_welcome_dim_m', 'Shade behind the text', 0, 90 );
	$media( 'og_welcome_car_m', 'Car picture (light design)', 'Used when there is no background photo. Best with a white background, car facing left.' );
	$head( 'og_intro_h_lm', 'Language page' );
	$media( 'og_lang_bg_m', 'Background photo', 'Optional. Empty = the white language page. With a photo, the list turns into dark glass on top of it.' );
	$pos( 'og_lang_pos_m', 'Show this part of the photo' );
	$range( 'og_lang_dim_m', 'Darken the photo', 30, 95 );
} );

/** Which device each control belongs to (for the switch). */
function orego_intro_device_map() {
	return apply_filters( 'orego_intro_device_map', array(
		'desktop' => array( 'og_intro_h_wd', 'og_welcome_bg', 'og_welcome_tone', 'og_welcome_dim', 'og_welcome_car', 'og_intro_h_ld', 'og_lang_bg', 'og_lang_dim' ),
		'mobile'  => array( 'og_intro_h_wm', 'og_welcome_bg_m', 'og_welcome_pos_m', 'og_welcome_tone_m', 'og_welcome_dim_m', 'og_welcome_car_m', 'og_intro_h_lm', 'og_lang_bg_m', 'og_lang_pos_m', 'og_lang_dim_m' ),
	) );
}

/* Customizer panel: device switch, headings, and the preview jumps to the right page. */
add_action( 'customize_controls_print_footer_scripts', function () {
	$welcome = home_url( '/' );
	$lang    = add_query_arg( 'og_langprev', '1', orego_purl( 'cars' ) );
	?>
	<style>
	#customize-control-og_intro_h_wd,#customize-control-og_intro_h_ld,#customize-control-og_intro_h_wm,#customize-control-og_intro_h_lm{margin:8px 0 0;padding:14px 0 0;border-top:1px solid #dcdcde}
	#sub-accordion-section-orego_intro .customize-control-og_heading .customize-control-title{font-size:14px;font-weight:700;text-transform:none}
	</style>
	<script>
	(function(api){
		if(!api)return;
		var W=<?php echo wp_json_encode( $welcome ); ?>,L=<?php echo wp_json_encode( $lang ); ?>;
		var DEV=<?php echo wp_json_encode( orego_intro_device_map() ); ?>,cur='desktop';
		var lang=['og_lang_bg','og_lang_dim','og_lang_bg_m','og_lang_pos_m','og_lang_dim_m','og_intro_h_ld','og_intro_h_lm'];
		var welcome=['og_welcome_bg','og_welcome_tone','og_welcome_tone_m','og_welcome_dim','og_welcome_car','og_welcome_bg_m','og_welcome_pos_m','og_welcome_dim_m','og_welcome_car_m','og_intro_h_wd','og_intro_h_wm'];
		function go(u){if(api.previewer&&api.previewer.previewUrl&&api.previewer.previewUrl()!==u)api.previewer.previewUrl.set(u);}
		function show(dev){
			cur=dev==='desktop'?'desktop':'mobile';
			DEV.desktop.forEach(function(id){api.control(id,function(c){c.container.toggle(cur==='desktop');});});
			DEV.mobile.forEach(function(id){api.control(id,function(c){c.container.toggle(cur==='mobile');});});
			document.querySelectorAll('.ogi-dev .button').forEach(function(b){b.setAttribute('aria-pressed',b.dataset.dev===cur?'true':'false');});
		}
		function setPreview(dev){var pd=api.previewedDevice;if(pd&&pd.get()!==dev)pd.set(dev);}
		api.bind('ready',function(){
			document.addEventListener('click',function(e){var b=e.target.closest&&e.target.closest('.ogi-dev .button');if(!b)return;e.preventDefault();e.stopPropagation();show(b.dataset.dev);setPreview(b.dataset.dev);},true);
			if(api.previewedDevice)api.previewedDevice.bind(function(d){show(d);});
			show(api.previewedDevice?api.previewedDevice.get():'desktop');
			api.section('orego_intro',function(sec){sec.expanded.bind(function(on){if(on){show(api.previewedDevice?api.previewedDevice.get():'desktop');go(W);}});});
			welcome.forEach(function(id){api(id,function(s){s.bind(function(){go(W);});});api.control(id,function(c){c.container.on('click focusin',function(){go(W);});});});
			lang.forEach(function(id){api(id,function(s){s.bind(function(){go(L);});});api.control(id,function(c){c.container.on('click focusin',function(){go(L);});});});
		});
	})(window.wp&&wp.customize);
	</script>
	<?php
}, 1002 );

/* ---------- Welcome page ---------- */

/**
 * Looks at a background photo once (the result is saved with the photo):
 * lum   = how bright the left side is, where the logo, text and swipe sit (0 dark .. 1 white)
 * white = the photo has a plain white left edge (a car cut out on white), so it can be fitted on the right
 */
function orego_intro_analyze( $id ) {
	$id   = absint( $id );
	$file = $id ? get_attached_file( $id ) : '';
	if ( ! $file || ! file_exists( $file ) ) {
		return array( 'lum' => 1, 'white' => true );
	}
	$key = filemtime( $file ) . ':' . filesize( $file );
	$c   = get_post_meta( $id, '_og_photo_scan', true );
	if ( is_array( $c ) && ( $c['k'] ?? '' ) === $key ) {
		return $c;
	}
	$res = array( 'lum' => .5, 'white' => false, 'k' => $key );
	$im  = function_exists( 'imagecreatefromstring' ) ? @imagecreatefromstring( (string) file_get_contents( $file ) ) : false; // phpcs:ignore
	if ( $im ) {
		$w  = imagesx( $im );
		$h  = imagesy( $im );
		$px = function ( $x, $y ) use ( $im ) {
			$c = imagecolorat( $im, (int) $x, (int) $y );
			return ( .2126 * ( ( $c >> 16 ) & 255 ) + .7152 * ( ( $c >> 8 ) & 255 ) + .0722 * ( $c & 255 ) ) / 255;
		};
		$sum = 0;
		$n   = 0;
		for ( $i = 0; $i < 24; $i++ ) {
			for ( $j = 0; $j < 24; $j++ ) {
				$sum += $px( ( $i + .5 ) / 24 * $w * .45, ( .12 + ( $j + .5 ) / 24 * .76 ) * $h );
				$n++;
			}
		}
		$edge = 0;
		$min  = 1;
		$m    = 0;
		for ( $j = 0; $j < 40; $j++ ) {
			for ( $i = 0; $i < 4; $i++ ) {
				$v     = $px( ( $i + .5 ) / 4 * $w * .05, ( $j + .5 ) / 40 * $h );
				$edge += $v;
				$min   = min( $min, $v );
				$m++;
			}
		}
		$res['lum']   = round( $sum / max( 1, $n ), 3 );
		$res['white'] = ( $edge / max( 1, $m ) ) > .93 && $min > .82;
		imagedestroy( $im );
	}
	update_post_meta( $id, '_og_photo_scan', $res );
	return $res;
}
/** Extra classes for the welcome page's <main>. */
function orego_intro_welcome_classes() {
	return ( orego_intro_photo_d() ? ' ow--pd' : '' ) . ( orego_intro_photo_m() ? ' ow--pm' : '' );
}

/** White logo, shown on the photo designs. */
function orego_intro_light_logo() {
	if ( ! orego_intro_has_photo() ) {
		return '';
	}
	$u = wp_get_attachment_image_url( orego_aid( 'logo_dark' ), 'full' );
	return $u ? '<img class="ow-logo-l" src="' . esc_url( $u ) . '" alt="" width="900" height="239" aria-hidden="true">' : '';
}

/** Car pictures for the light design (desktop and phone can differ). */
function orego_intro_welcome_cars() {
	$cd    = orego_intro_photo_d() ? 0 : absint( orego_intro_mod( 'og_welcome_car' ) );
	$cm    = orego_intro_photo_m() ? 0 : absint( orego_intro_mod( 'og_welcome_car_m' ) );
	$sizes = '(min-width:960px) 60vw, (min-width:700px) 700px, 152vw';
	$img   = function ( $id, $extra ) use ( $sizes ) {
		$src = wp_get_attachment_image_url( $id, 'large' );
		if ( ! $src ) {
			return '';
		}
		return '<img class="ow-car' . $extra . '" src="' . esc_url( $src ) . '" srcset="' . esc_attr( (string) wp_get_attachment_image_srcset( $id, 'full' ) ) . '" sizes="' . esc_attr( $sizes ) . '" width="1600" height="1200" alt="" fetchpriority="high" decoding="async">';
	};
	if ( $cd && $cm && $cd === $cm ) {
		return $img( $cd, '' );
	}
	return ( $cd ? $img( $cd, $cm ? ' ow-car--d' : '' ) : '' ) . ( $cm ? $img( $cm, $cd ? ' ow-car--m' : '' ) : '' );
}

/** Extra styles for the welcome page (printed inside its <head>). */
function orego_intro_welcome_style() {
	$css = '.ow-logo img.ow-logo-l{display:none}'
		. '@media (min-width:960px){.ow-car--m{display:none!important}}'
		. '@media (max-width:959px){.ow-car--d{display:none!important}}';

	$photo = function ( $cls, $url, $pos, $dim, $desk, $tone = 'light', $id = 0 ) {
		$scan = orego_intro_analyze( $id );
		if ( 'dark' !== $tone && 'light' !== $tone ) {
			$tone = $scan['lum'] > .62 ? 'dark' : 'light';
			if ( 'light' === $tone ) {
				$dim = max( absint( $dim ), 45 ); // white text always gets a shade behind it
			}
		}
		if ( 'dark' === $tone ) {
			// light photo: dark text and logo, optional white shade behind the text
			$a  = max( 0, min( 90, absint( $dim ) ) ) / 100;
			$n  = 'orego_intro_num';
			$bg = $desk
				? 'linear-gradient(90deg,rgba(255,255,255,' . $n( $a ) . ') 0%,rgba(255,255,255,' . $n( $a * .75 ) . ') 32%,rgba(255,255,255,' . $n( $a * .2 ) . ') 55%,rgba(255,255,255,0) 72%),'
				: 'linear-gradient(180deg,rgba(255,255,255,' . $n( $a ) . ') 0%,rgba(255,255,255,' . $n( $a * .4 ) . ') 45%,rgba(255,255,255,0) 70%),';
			$s  = 'html,body{background:#FFFFFF}'
				// desktop: the photo stays on the right and shrinks on smaller screens so it never runs under the text
				. '.ow.' . $cls . '{background:' . $bg . 'url(' . esc_url( $url ) . ') ' . ( $desk && ! empty( $scan['white'] ) ? 'right center/auto min(100%,calc(100vw - 600px))' : esc_attr( $pos ) . '/cover' ) . ' no-repeat,#FFFFFF}'
				. '.' . $cls . ' .ow-car,.' . $cls . ' .ow-floor{display:none!important}';
			return $desk ? '@media (min-width:960px){' . $s . '}' : '@media (max-width:959px){' . $s . '}';
		}
		$a  = max( 0, min( 90, absint( $dim ) ) ) / 100;
		$n  = 'orego_intro_num';
		$bg = $desk
			? 'linear-gradient(90deg,rgba(7,10,15,' . $n( $a ) . ') 0%,rgba(7,10,15,' . $n( $a * .75 ) . ') 30%,rgba(7,10,15,' . $n( $a * .2 ) . ') 55%,rgba(7,10,15,0) 72%),linear-gradient(0deg,rgba(7,10,15,' . $n( $a * .5 ) . ') 0%,rgba(7,10,15,0) 30%),'
			: 'linear-gradient(180deg,rgba(7,10,15,' . $n( $a ) . ') 0%,rgba(7,10,15,' . $n( $a * .35 ) . ') 42%,rgba(7,10,15,' . $n( $a * .45 ) . ') 62%,rgba(7,10,15,' . $n( min( .95, $a * 1.3 ) ) . ') 100%),';
		$s  = 'html,body{background:#0B0F15}'
			. '.ow.' . $cls . '{color:#FFFFFF;--ink:#FFFFFF;--muted:rgba(255,255,255,.78);background:' . $bg . 'url(' . esc_url( $url ) . ') ' . esc_attr( $pos ) . '/cover no-repeat,#0B0F15}'
			. '.' . $cls . ' .ow-car,.' . $cls . ' .ow-floor{display:none!important}'
			. '.' . $cls . ' .ow-logo img{display:none}.' . $cls . ' .ow-logo img.ow-logo-l{display:block}'
			. '.' . $cls . ' .ow-sub b{color:#FFFFFF}'
			. '.' . $cls . ' .ow-track{box-shadow:0 18px 40px -16px rgba(0,0,0,.7),inset 0 0 0 1px rgba(255,255,255,.16)}'
			. '.' . $cls . ' .ow-hint{color:rgba(255,255,255,.75)}';
		return $desk ? '@media (min-width:960px){' . $s . '}' : '@media (max-width:959px){' . $s . '}';
	};
	if ( orego_intro_photo_d() ) {
		$css .= $photo( 'ow--pd', orego_intro_img( 'og_welcome_bg' ), 'right center', orego_intro_mod( 'og_welcome_dim' ), true, (string) orego_intro_mod( 'og_welcome_tone' ), orego_intro_mod( 'og_welcome_bg' ) );
	}
	if ( orego_intro_photo_m() ) {
		$css .= $photo( 'ow--pm', orego_intro_img( 'og_welcome_bg_m' ), orego_intro_pos( (string) orego_intro_mod( 'og_welcome_pos_m' ) ), orego_intro_mod( 'og_welcome_dim_m' ), false, (string) orego_intro_mod( 'og_welcome_tone_m' ), orego_intro_mod( 'og_welcome_bg_m' ) );
	}
	return '<style id="og-welcome-photo">' . $css . '</style>';
}

/** Customizer preview needs its script on the welcome page (it is drawn without the site header/footer). */
function orego_intro_welcome_preview_scripts() {
	global $wp_customize;
	if ( ! is_customize_preview() || ! $wp_customize ) {
		return;
	}
	wp_print_scripts( array( 'customize-preview' ) );
	$wp_customize->customize_preview_settings();
}

/* ---------- Language page ---------- */
add_action( 'wp_footer', function () {
	$n = 'orego_intro_num';

	// Desktop: photo on the right, list on the darkened left.
	$bg  = orego_intro_img( 'og_lang_bg' );
	$a   = max( 30, min( 95, absint( orego_intro_mod( 'og_lang_dim' ) ) ) ) / 100;
	$img = '' !== $bg ? 'url(' . esc_url( $bg ) . ') right center/cover no-repeat,' : '';
	$css = '@media (min-width:1025px){html body .oglp .oglp-card{background:'
		. 'linear-gradient(90deg,rgba(7,10,15,' . $n( $a ) . ') 0%,rgba(7,10,15,' . $n( $a * .76 ) . ') 28%,rgba(7,10,15,' . $n( $a * .22 ) . ') 50%,rgba(7,10,15,0) 68%),'
		. 'linear-gradient(0deg,rgba(7,10,15,.45) 0%,rgba(7,10,15,0) 30%),'
		. $img . '#0B0F15!important}}';

	// Phones & tablets: optional photo behind the list (dark glass rows).
	$bgm = orego_intro_img( 'og_lang_bg_m' );
	if ( '' !== $bgm ) {
		$m    = max( 30, min( 95, absint( orego_intro_mod( 'og_lang_dim_m' ) ) ) ) / 100;
		$pos  = orego_intro_pos( (string) orego_intro_mod( 'og_lang_pos_m' ) );
		$css .= '@media (max-width:1024px){'
			. 'html body .oglp{background:#0B0F15!important}'
			. 'html body .oglp .oglp-card{color:#FFFFFF!important;background:linear-gradient(180deg,rgba(7,10,15,' . $n( $m ) . ') 0%,rgba(7,10,15,' . $n( $m * .8 ) . ') 45%,rgba(7,10,15,' . $n( min( .97, $m * 1.1 ) ) . ') 100%),url(' . esc_url( $bgm ) . ') ' . esc_attr( $pos ) . '/cover no-repeat,#0B0F15!important}'
			. 'html body .oglp .oglp-head{background:transparent!important;color:#FFFFFF!important;border-bottom-color:rgba(255,255,255,.08)!important}'
			. 'html body .oglp .oglp-list{background:transparent!important}'
			. 'html body .oglp .oglp-mt,html body .oglp .oglp-back{color:#FFFFFF!important}'
			. 'html body .oglp .oglp-ok{background:#FFFFFF!important;color:#15171C!important}'
			. 'html body .oglp .oglp-row{background:rgba(255,255,255,.08)!important;border-color:rgba(255,255,255,.14)!important;color:#FFFFFF!important;box-shadow:none!important;-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px)}'
			. 'html body .oglp .oglp-row.is-pick{background:rgba(255,255,255,.18)!important;border-color:#FFFFFF!important;box-shadow:0 0 0 1px #FFFFFF!important}'
			. 'html body .oglp .oglp-txt b{color:#FFFFFF!important}'
			. 'html body .oglp .oglp-txt small{color:rgba(255,255,255,.6)!important}'
			. '}';
	}
	echo '<style id="og-lang-photo">' . $css . '</style>'; // phpcs:ignore

	// Customizer: the language page stays closed in the preview, unless a language setting is being edited.
	if ( is_customize_preview() && ! isset( $_GET['og_langprev'] ) ) { // phpcs:ignore
		echo '<style>.oglp{display:none!important}</style>';
	}
	// Customizer: open the language page in the preview.
	if ( is_customize_preview() && isset( $_GET['og_langprev'] ) ) { // phpcs:ignore
		echo '<script>(function(){function o(){var p=document.querySelector(".oglp");if(!p)return;p.hidden=false;p.removeAttribute("aria-hidden");p.classList.add("is-open");}if(document.readyState!=="loading")o();else document.addEventListener("DOMContentLoaded",o);window.addEventListener("load",o);})();</script>';
	}
}, 60 );
