<?php
/**
 * Appearance -> Customize -> Car Cards: one place to style the car cards used across the site
 * (homepage, Cars page, similar cars). A ready-made design to start from, then colours, shape, parts and texts.
 * An "Exotic Cars Listing" Elementor widget can still override any of it on its own page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_card_designs() {
	return array(
		'classic'    => array( 'Classic', 'Light card with a soft shadow.' ),
		'minimal'    => array( 'Minimal', 'Flat, thin border, text button.' ),
		'luxury'     => array( 'Luxury', 'Dark card with gold details.' ),
		'showcase'   => array( 'Showcase', 'Car floating on a tinted panel.' ),
		'horizontal' => array( 'Horizontal', 'Photo beside the details, 2 per row.' ),
		'compact'    => array( 'Compact', 'Smaller cards, full-width button.' ),
	);
}

function orego_card_defaults() {
	return array(
		'design' => 'classic', 'cols' => '', 'accent' => '', 'bg' => '', 'title' => '', 'price' => '', 'btn_bg' => '', 'btn_fg' => '',
		'radius' => '', 'ratio' => '', 'fit' => '', 'shadow' => '', 'hover' => '',
		'show_tag' => true, 'show_fav' => true, 'show_meta' => true, 'show_specs' => true, 'show_loc' => true, 'show_perks' => true,
		'btn' => 'View details', 'per' => '/ day', 'perk1' => 'Free delivery', 'perk2' => '24/7 support', 'perk3' => 'Optional insurance',
	);
}

function orego_card_opt( $k ) {
	$d = orego_card_defaults();
	return get_theme_mod( 'orego_card_' . $k, $d[ $k ] ?? '' );
}

/** Settings that have their own value on phones (stored as orego_card_m_*). Texts stay shared. */
function orego_card_dev_keys() {
	return array( 'design', 'cols', 'accent', 'bg', 'title', 'price', 'btn_bg', 'btn_fg', 'radius', 'ratio', 'fit', 'shadow', 'hover', 'show_tag', 'show_fav', 'show_meta', 'show_specs', 'show_loc', 'show_perks' );
}

/** Phone value of a card setting. */
function orego_card_mopt( $k ) {
	$d = orego_card_defaults();
	return get_theme_mod( 'orego_card_m_' . $k, $d[ $k ] ?? '' );
}

/* First run: phones start with the look they had before (desktop values), then the two are separate. */
add_action( 'init', function () {
	if ( get_option( 'orego_card_m_init' ) ) {
		return;
	}
	foreach ( orego_card_dev_keys() as $k ) {
		if ( 'cols' === $k ) {
			continue;
		}
		$v = get_theme_mod( 'orego_card_' . $k, null );
		if ( null !== $v && null === get_theme_mod( 'orego_card_m_' . $k, null ) ) {
			set_theme_mod( 'orego_card_m_' . $k, $v );
		}
	}
	update_option( 'orego_card_m_init', 1 );
} );

/** Parts hidden on both desktop and phone are left out of the page; parts hidden on one only are hidden with CSS. */
function orego_card_hide() {
	$h = array();
	foreach ( array( 'tag', 'fav', 'meta', 'specs', 'loc', 'perks' ) as $k ) {
		if ( ! orego_card_opt( 'show_' . $k ) && ! orego_card_mopt( 'show_' . $k ) ) {
			$h[] = $k;
		}
	}
	return implode( ',', $h );
}

function orego_card_perks() {
	return implode( '|', array_filter( array( orego_card_opt( 'perk1' ), orego_card_opt( 'perk2' ), orego_card_opt( 'perk3' ) ), fn( $v ) => '' !== trim( (string) $v ) ) );
}

add_filter( 'body_class', function ( $c ) {
	$d = orego_card_opt( 'design' );
	$c[] = 'og-cd';
	$c[] = 'og-cd-' . ( isset( orego_card_designs()[ $d ] ) ? $d : 'classic' );
	$m = orego_card_mopt( 'design' );
	$c[] = 'og-cdm-' . ( isset( orego_card_designs()[ $m ] ) ? $m : 'classic' );
	return $c;
} );

/* ------------------------------------------------------------------ */
/* Styles                                                              */
/* ------------------------------------------------------------------ */

function orego_card_design_css() {
	return <<<'CSS'
body.og-cd-minimal .og-cars .og-v{background:#fff;box-shadow:none;border:1px solid #E6E8EC;border-radius:14px}
body.og-cd-minimal .og-cars .og-v:hover{box-shadow:0 10px 26px rgba(21,23,28,.08)}
body.og-cd-minimal .og-cars .og-media{background:#F4F5F7}
body.og-cd-minimal .og-cars .og-tag{background:#15171C;color:#fff}
body.og-cd-minimal .og-cars .og-fav{background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.12)}
body.og-cd-minimal .og-cars .og-more{background:transparent;color:#15171C;padding:9px 0;border-radius:0}
body.og-cd-minimal .og-cars .og-more:hover{background:transparent;color:var(--hx-primary)}
body.og-cd-minimal .og-cars .og-price{font-weight:700}

body.og-cd-luxury .og-cars .og-v{background:#14120F;color:#F5F1EA;border:1px solid rgba(212,178,124,.18);box-shadow:0 18px 40px -22px rgba(0,0,0,.7)}
body.og-cd-luxury .og-cars .og-v:hover{border-color:rgba(212,178,124,.45);box-shadow:0 22px 46px -20px rgba(0,0,0,.75)}
body.og-cd-luxury .og-cars .og-media{margin:10px 10px 0;border-radius:14px;overflow:hidden;background:#F4F1EC}
body.og-cd-luxury .og-cars .og-tag{top:22px;inset-inline-start:22px}
body.og-cd-luxury .og-cars .og-fav{top:20px;inset-inline-end:20px}
body.og-cd-luxury .og-cars .og-name,body.og-cd-luxury .og-cars .og-name a{color:#fff}
body.og-cd-luxury .og-cars .og-name a:hover{color:#EBCB94}
body.og-cd-luxury .og-cars .og-meta,body.og-cd-luxury .og-cars .og-specs,body.og-cd-luxury .og-cars .og-loc,body.og-cd-luxury .og-cars .og-perks{color:rgba(245,241,234,.62)}
body.og-cd-luxury .og-cars .og-meta strong{color:#fff}
body.og-cd-luxury .og-cars .og-specs svg,body.og-cd-luxury .og-cars .og-loc svg,body.og-cd-luxury .og-cars .og-perks svg{color:#D4B27C}
body.og-cd-luxury .og-cars .og-foot{border-top-color:rgba(255,255,255,.1)}
body.og-cd-luxury .og-cars .og-price{color:#EBCB94}
body.og-cd-luxury .og-cars .og-price small{color:rgba(245,241,234,.5)}
body.og-cd-luxury .og-cars .og-more{background:transparent;border:1px solid rgba(212,178,124,.6);color:#EBCB94}
body.og-cd-luxury .og-cars .og-more:hover{background:#D4B27C;border-color:#D4B27C;color:#14120F}
body.og-cd-luxury .og-cars .og-tag{background:rgba(20,18,15,.78);color:#EBCB94;border:1px solid rgba(212,178,124,.4)}
body.og-cd-luxury .og-cars .og-fav{background:rgba(20,18,15,.72);color:#fff}

body.og-cd-showcase .og-cars .og-v{background:#fff;padding:10px;border-radius:26px;box-shadow:0 12px 32px rgba(21,23,28,.07)}
body.og-cd-showcase .og-cars .og-media{border-radius:18px;overflow:hidden;background:linear-gradient(160deg,var(--hx-primary-soft,#EEF2FF) 0%,#FFFFFF 85%)}
body.og-cd-showcase .og-cars .og-media img{object-fit:contain;padding:12px 10px 4px}
body.og-cd-showcase .og-cars .og-tag{top:22px;inset-inline-start:22px}
body.og-cd-showcase .og-cars .og-fav{top:20px;inset-inline-end:20px}
body.og-cd-showcase .og-cars .og-body{padding:16px 10px 8px}
body.og-cd-showcase .og-cars .og-foot{border-top:0;padding-top:6px}
body.og-cd-showcase .og-cars .og-more{background:var(--hx-primary);color:#fff;border-radius:12px;padding:10px 16px}
body.og-cd-showcase .og-cars .og-more:hover{filter:brightness(1.08);color:#fff}

@media (min-width:768px){
body.og-cd-horizontal .og-cars:not(.og-cars-none){grid-template-columns:repeat(2,minmax(0,1fr))}
body.og-cd-horizontal .og-cars .og-v{flex-direction:row;border-radius:18px}
body.og-cd-horizontal .og-cars .og-media{flex:0 0 42%;aspect-ratio:auto;min-height:100%}
body.og-cd-horizontal .og-cars .og-body{flex:1;min-width:0}
body.og-cd-horizontal .og-cars .og-perks{display:none}
body.og-cd-horizontal .og-cars .og-fav{inset-inline-end:auto;inset-inline-start:calc(42% - 48px)}
}
@media (min-width:768px) and (max-width:1100px){body.og-cd-horizontal .og-cars:not(.og-cars-none){grid-template-columns:1fr}}

body.og-cd-compact .og-cars{gap:18px}
body.og-cd-compact .og-cars .og-v{border-radius:14px}
body.og-cd-compact .og-cars .og-media{aspect-ratio:16/10}
body.og-cd-compact .og-cars .og-body{padding:14px 16px 16px;gap:6px}
body.og-cd-compact .og-cars .og-name{font-size:16px}
body.og-cd-compact .og-cars .og-perks{display:none}
body.og-cd-compact .og-cars .og-foot{flex-direction:column;align-items:stretch;gap:10px}
body.og-cd-compact .og-cars .og-price{font-size:19px}
body.og-cd-compact .og-cars .og-more{justify-content:center;border-radius:10px;background:var(--hx-primary);color:#fff}
body.og-cd-compact .og-cars .og-more:hover{filter:brightness(1.08);color:#fff}
@media (min-width:1025px){body.og-cd-compact .og-cars:not(.og-cars-none){grid-template-columns:repeat(4,minmax(0,1fr))}}
CSS;
}

/** The Customizer choices, layered on top of the design (Elementor widget settings still win). */
function orego_card_custom_css( $mobile = false ) {
	$o = function ( $k ) use ( $mobile ) {
		return $mobile ? orego_card_mopt( $k ) : orego_card_opt( $k );
	};
	$p = 'html body.og-cd .og-cars ';
	$c = '';
	if ( $o( 'accent' ) ) {
		$c .= 'html body.og-cd .og-cars{--hx-primary:' . $o( 'accent' ) . ';--hx-primary-soft:color-mix(in srgb,' . $o( 'accent' ) . ' 12%,#fff)}';
	}
	if ( $o( 'bg' ) ) {
		$c .= $p . '.og-v{background:' . $o( 'bg' ) . '}';
	}
	if ( $o( 'title' ) ) {
		$c .= $p . '.og-name,' . $p . '.og-name a{color:' . $o( 'title' ) . '}';
	}
	if ( $o( 'price' ) ) {
		$c .= $p . '.og-price{color:' . $o( 'price' ) . '}';
	}
	if ( $o( 'btn_bg' ) ) {
		$c .= $p . '.og-more{background:' . $o( 'btn_bg' ) . ';border-color:' . $o( 'btn_bg' ) . ';padding-left:15px;padding-right:15px}' . $p . '.og-more:hover{background:' . $o( 'btn_bg' ) . ';filter:brightness(1.08)}';
	}
	if ( $o( 'btn_fg' ) ) {
		$c .= $p . '.og-more,' . $p . '.og-more:hover{color:' . $o( 'btn_fg' ) . '}';
	}
	if ( '' !== (string) $o( 'radius' ) ) {
		$c .= $p . '.og-v{border-radius:' . (int) $o( 'radius' ) . 'px}';
	}
	if ( $o( 'ratio' ) ) {
		$c .= $p . '.og-media{aspect-ratio:' . esc_attr( $o( 'ratio' ) ) . '}';
	}
	if ( $o( 'fit' ) ) {
		$c .= $p . '.og-media img{object-fit:' . esc_attr( $o( 'fit' ) ) . '}';
	}
	$sh = array( 'none' => 'none', 'soft' => '0 6px 24px rgba(21,23,28,.06)', 'strong' => '0 18px 44px rgba(21,23,28,.16)' );
	if ( isset( $sh[ $o( 'shadow' ) ] ) ) {
		$c .= $p . '.og-v,' . $p . '.og-v:hover{box-shadow:' . $sh[ $o( 'shadow' ) ] . '}';
	}
	if ( 'none' === $o( 'hover' ) ) {
		$c .= $p . '.og-v:hover{transform:none}' . $p . '.og-v:hover .og-media img{transform:none}';
	} elseif ( 'lift' === $o( 'hover' ) ) {
		$c .= $p . '.og-v:hover{transform:translateY(-4px)}' . $p . '.og-v:hover .og-media img{transform:none}';
	} elseif ( 'zoom' === $o( 'hover' ) ) {
		$c .= $p . '.og-v:hover{transform:none}' . $p . '.og-media{overflow:hidden}' . $p . '.og-media img{transition:transform .5s ease}' . $p . '.og-v:hover .og-media img{transform:scale(1.06)}';
	}
	if ( $mobile ) {
		if ( in_array( (string) $o( 'cols' ), array( '1', '2' ), true ) ) {
			$c .= 'html body.og-cd .og-cars:not(.og-cars-none){grid-template-columns:repeat(' . (int) $o( 'cols' ) . ',minmax(0,1fr))}';
		}
	} elseif ( in_array( (string) $o( 'cols' ), array( '2', '3', '4' ), true ) ) {
		$c .= '@media (min-width:1025px){html body.og-cd .og-cars:not(.og-cars-none){grid-template-columns:repeat(' . (int) $o( 'cols' ) . ',minmax(0,1fr))}}';
	}
	foreach ( array( 'tag', 'fav', 'meta', 'specs', 'loc', 'perks' ) as $k ) {
		if ( ! $o( 'show_' . $k ) ) {
			$c .= $p . '.og-' . $k . '{display:none!important}';
		}
	}
	return $c;
}

add_action( 'wp_head', function () {
	$css = orego_card_design_css();
	echo '<style id="og-card-design">@media (min-width:768px){' . $css . orego_card_custom_css( false ) . '}@media (max-width:767px){' . str_replace( 'body.og-cd-', 'body.og-cdm-', $css ) . orego_card_custom_css( true ) . '}</style>'; // phpcs:ignore
}, 40 );

/* ------------------------------------------------------------------ */
/* Customizer                                                          */
/* ------------------------------------------------------------------ */

add_action( 'customize_register', function ( $wp ) {
	if ( ! class_exists( 'OreGO_Card_Design_Control' ) ) {
		class OreGO_Card_Design_Control extends WP_Customize_Control {
			public $type = 'orego_card_design';

			public function render_content() {
				$cur = $this->value();
				echo '<span class="customize-control-title">' . esc_html( $this->label ) . '</span>';
				if ( $this->description ) {
					echo '<span class="description customize-control-description">' . esc_html( $this->description ) . '</span>';
				}
				echo '<div class="ogcd-grid">';
				foreach ( orego_card_designs() as $k => $d ) {
					echo '<label class="ogcd-opt' . ( $cur === $k ? ' is-on' : '' ) . '"><input type="radio" name="_customize-radio-' . esc_attr( $this->id ) . '" value="' . esc_attr( $k ) . '" ' . checked( $cur, $k, false ) . ' ';
					$this->link();
					echo '><span class="ogcd-prev ogcd-' . esc_attr( $k ) . '"><i class="ogcd-img"></i><i class="ogcd-l1"></i><i class="ogcd-l2"></i><i class="ogcd-btn"></i></span><b>' . esc_html( $d[0] ) . '</b><small>' . esc_html( $d[1] ) . '</small></label>';
				}
				echo '</div>';
			}
		}
	}
	if ( ! class_exists( 'OreGO_Card_Device_Switch' ) ) {
		class OreGO_Card_Device_Switch extends WP_Customize_Control {
			public $type = 'orego_card_device';

			public function render_content() {
				echo '<span class="customize-control-title">Editing the cards for</span>';
				echo '<div class="ogcd-dev" role="tablist"><button type="button" class="button" data-dev="desktop" aria-pressed="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h8"/><path d="M10 15v4"/><path d="M7 19h5"/><rect x="15" y="11" width="7" height="10" rx="1.6"/></svg><span>Desktop &amp; tablet</span></button><button type="button" class="button" data-dev="mobile" aria-pressed="false"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2.5" width="12" height="19" rx="2.6"/><path d="M11 18.5h2"/></svg><span>Phone</span></button></div>';
				echo '<span class="description customize-control-description">Desktop and phone have their own settings: changing one does not change the other. The texts at the bottom are shared.</span>';
			}
		}
	}
	$d   = orego_card_defaults();
	$sec = 'orego_cards';
	$wp->add_section( $sec, array( 'title' => 'Car Cards', 'priority' => 26, 'description' => 'Style the car cards shown on the homepage, the Cars page and "Similar cars". Pick a design, then fine-tune it. Leave a colour empty to keep the design’s own colour.' ) );
	$wp->add_setting( 'orego_card_ui_device', array( 'default' => 'desktop', 'sanitize_callback' => 'sanitize_key', 'transport' => 'postMessage' ) );
	$wp->add_control( new OreGO_Card_Device_Switch( $wp, 'orego_card_ui_device', array( 'section' => $sec, 'priority' => 0 ) ) );
	$pick = function ( $choices, $def ) {
		return function ( $v ) use ( $choices, $def ) {
			return array_key_exists( (string) $v, $choices ) ? (string) $v : $def;
		};
	};
	$bool = function ( $v ) {
		return (bool) $v;
	};
	$prio = 1;

	foreach ( array( '' => 'desktop', 'm_' => 'mobile' ) as $px => $dev ) {
		$set = function ( $k, $san ) use ( $wp, $d, $px ) {
			$wp->add_setting( 'orego_card_' . $px . $k, array( 'default' => $d[ $k ], 'sanitize_callback' => $san, 'transport' => 'refresh' ) );
		};
		$id = function ( $k ) use ( $px ) {
			return 'orego_card_' . $px . $k;
		};

		$set( 'design', $pick( orego_card_designs(), 'classic' ) );
		$wp->add_control( new OreGO_Card_Design_Control( $wp, $id( 'design' ), array( 'label' => 'Choose design', 'section' => $sec, 'priority' => $prio++ ) ) );

		$cols = 'mobile' === $dev ? array( '' => 'Design default', '1' => '1', '2' => '2' ) : array( '' => 'Design default', '2' => '2', '3' => '3', '4' => '4' );
		$set( 'cols', $pick( $cols, '' ) );
		$wp->add_control( $id( 'cols' ), array( 'label' => 'mobile' === $dev ? 'Cards per row on phone' : 'Cards per row on desktop', 'section' => $sec, 'type' => 'select', 'choices' => $cols, 'priority' => $prio++ ) );

		foreach ( array( 'accent' => array( 'Accent colour', 'Buttons, icons and highlights.' ), 'bg' => array( 'Card background', '' ), 'title' => array( 'Car name colour', '' ), 'price' => array( 'Price colour', '' ), 'btn_bg' => array( 'Button background', '' ), 'btn_fg' => array( 'Button text colour', '' ) ) as $k => $l ) {
			$set( $k, 'sanitize_hex_color' );
			$wp->add_control( new WP_Customize_Color_Control( $wp, $id( $k ), array( 'label' => $l[0], 'description' => $l[1], 'section' => $sec, 'priority' => $prio++ ) ) );
		}

		$set( 'radius', function ( $v ) {
			return '' === trim( (string) $v ) ? '' : (string) max( 0, min( 40, (int) $v ) );
		} );
		$wp->add_control( $id( 'radius' ), array( 'label' => 'Corner radius (px)', 'description' => 'Empty = design default.', 'section' => $sec, 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 40, 'placeholder' => 'Design default' ), 'priority' => $prio++ ) );
		$opts = array(
			'ratio'  => array( 'Photo shape', array( '' => 'Design default', '4/3' => '4:3', '3/2' => '3:2', '16/9' => 'Wide 16:9', '1/1' => 'Square' ) ),
			'fit'    => array( 'Photo fit', array( '' => 'Design default', 'cover' => 'Fill (crop)', 'contain' => 'Show whole car' ) ),
			'shadow' => array( 'Shadow', array( '' => 'Design default', 'none' => 'None', 'soft' => 'Soft', 'strong' => 'Strong' ) ),
			'hover'  => array( 'Hover effect', array( '' => 'Design default', 'lift' => 'Lift the card', 'zoom' => 'Zoom the photo', 'none' => 'None' ) ),
		);
		if ( 'mobile' === $dev ) {
			unset( $opts['hover'] );
		}
		foreach ( $opts as $k => $o ) {
			$set( $k, $pick( $o[1], '' ) );
			$wp->add_control( $id( $k ), array( 'label' => $o[0], 'section' => $sec, 'type' => 'select', 'choices' => $o[1], 'priority' => $prio++ ) );
		}

		foreach ( array( 'tag' => 'Show the car type badge', 'fav' => 'Show the favourite (heart) button', 'meta' => 'Show rating and year', 'specs' => 'Show seats, gearbox and fuel', 'loc' => 'Show the location', 'perks' => 'Show the perks line' ) as $k => $l ) {
			$set( 'show_' . $k, $bool );
			$wp->add_control( $id( 'show_' . $k ), array( 'label' => $l, 'section' => $sec, 'type' => 'checkbox', 'priority' => $prio++ ) );
		}
	}

	$wp->add_setting( 'orego_card_texts_note', array( 'sanitize_callback' => '__return_empty_string' ) );
	$wp->add_control( new WP_Customize_Control( $wp, 'orego_card_texts_note', array( 'section' => $sec, 'type' => 'hidden', 'label' => 'Texts (desktop and phone)', 'priority' => $prio++ ) ) );
	foreach ( array( 'perk1' => 'Perk 1', 'perk2' => 'Perk 2', 'perk3' => 'Perk 3', 'per' => 'Text after the price', 'btn' => 'Button text' ) as $k => $l ) {
		$wp->add_setting( 'orego_card_' . $k, array( 'default' => $d[ $k ], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
		$wp->add_control( 'orego_card_' . $k, array( 'label' => $l, 'section' => $sec, 'type' => 'text', 'priority' => $prio++, 'description' => 'btn' === $k ? 'Leave empty to hide the button.' : '' ) );
	}
}, 20 );

add_action( 'customize_controls_print_styles', function () {
	echo '<style>
	#customize-controls .ogcd-dev{display:grid;grid-template-columns:1fr 1fr;gap:4px;margin:6px 0 10px;padding:4px;background:#F0F0F1;border:1px solid #DCDCDE;border-radius:10px}
	#customize-controls .ogcd-dev .button,#customize-controls .ogcd-dev .button:focus{display:flex;align-items:center;justify-content:center;gap:7px;min-width:0;height:38px;margin:0;padding:0 8px;border:0;border-radius:7px;background:transparent;color:#50575E;font-size:13px;font-weight:600;line-height:1;box-shadow:none;white-space:nowrap;transition:background .15s,color .15s,box-shadow .15s}
	#customize-controls .ogcd-dev .button:hover{background:rgba(255,255,255,.6);color:#1D2327}
	#customize-controls .ogcd-dev .button:focus-visible{outline:2px solid #2271B1;outline-offset:1px}
	#customize-controls .ogcd-dev .button[aria-pressed=true]{background:#fff;color:#2271B1;box-shadow:0 1px 3px rgba(0,0,0,.12),0 0 0 1px rgba(0,0,0,.04)}
	#customize-controls .ogcd-dev .button svg{flex:0 0 auto;display:block}
	#customize-controls .ogcd-dev .button span{overflow:hidden;text-overflow:ellipsis}
	#customize-control-orego_card_ui_device{position:sticky;top:0;z-index:5;background:#f0f0f1;padding-bottom:8px;border-bottom:1px solid #dcdcde;margin-bottom:12px}
	#customize-control-orego_card_texts_note{border-top:1px solid #dcdcde;padding-top:14px;margin-top:6px}
	#customize-control-orego_card_texts_note .customize-control-title{font-size:14px}
	.ogcd-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px}
	.ogcd-opt{display:flex;flex-direction:column;gap:3px;padding:8px;border:2px solid #DCDCDE;border-radius:12px;background:#fff;cursor:pointer;transition:border-color .15s}
	.ogcd-opt:hover{border-color:#8C8F94}
	.ogcd-opt input{position:absolute;opacity:0;pointer-events:none}
	.ogcd-opt:has(input:checked),.ogcd-opt.is-on:not(:has(input)){border-color:#2271B1;box-shadow:0 0 0 1px #2271B1}
	.ogcd-opt b{font-size:12.5px}.ogcd-opt small{font-size:11px;color:#646970;line-height:1.35}
	.ogcd-prev{position:relative;display:block;height:92px;border-radius:9px;background:#FCFCFD;box-shadow:0 2px 8px rgba(0,0,0,.08);overflow:hidden;margin-bottom:4px}
	.ogcd-prev i{position:absolute;display:block;border-radius:3px}
	.ogcd-img{left:0;right:0;top:0;height:46px;background:linear-gradient(135deg,#E6E8EC,#D9DCE2)}
	.ogcd-l1{left:8px;top:54px;width:60%;height:7px;background:#15171C}
	.ogcd-l2{left:8px;top:66px;width:40%;height:5px;background:#C3C7CF}
	.ogcd-btn{right:8px;bottom:8px;width:34%;height:12px;border-radius:999px!important;background:#DCE5FF}
	.ogcd-minimal{box-shadow:none;border:1px solid #DCDCDE}.ogcd-minimal .ogcd-img{background:#F1F2F4}.ogcd-minimal .ogcd-btn{background:none;border-bottom:2px solid #15171C;border-radius:0!important;height:8px;width:26%}
	.ogcd-luxury{background:#14120F}.ogcd-luxury .ogcd-img{background:radial-gradient(circle at 50% 80%,#3A342B,#14120F)}.ogcd-luxury .ogcd-l1{background:#fff}.ogcd-luxury .ogcd-l2{background:#D4B27C}.ogcd-luxury .ogcd-btn{background:none;border:1px solid #D4B27C}
	.ogcd-showcase{padding:5px}.ogcd-showcase .ogcd-img{left:5px;right:5px;top:5px;height:44px;border-radius:7px;background:linear-gradient(160deg,#DCE5FF,#fff)}.ogcd-showcase .ogcd-btn{background:#3563E9;border-radius:4px!important}
	.ogcd-horizontal .ogcd-img{right:auto;width:42%;height:100%;bottom:0}.ogcd-horizontal .ogcd-l1{left:48%;top:18px;width:44%}.ogcd-horizontal .ogcd-l2{left:48%;top:32px;width:30%}.ogcd-horizontal .ogcd-btn{width:30%}
	.ogcd-compact .ogcd-img{height:40px}.ogcd-compact .ogcd-l1{top:48px;height:6px}.ogcd-compact .ogcd-l2{top:59px}.ogcd-compact .ogcd-btn{left:8px;right:8px;width:auto;border-radius:3px!important;background:#3563E9}
	</style>';
} );

add_action( 'customize_controls_print_footer_scripts', function () {
	?>
	<script>
	(function(api){api.bind('ready',function(){
		var keys=<?php echo wp_json_encode( orego_card_dev_keys() ); ?>,cur='desktop';
		function kind(id){if(id.indexOf('orego_card_m_')===0)return 'mobile';var k=id.replace('orego_card_','');return keys.indexOf(k)>-1?'desktop':'';}
		function show(dev){cur=dev==='mobile'?'mobile':'desktop';
			api.control.each(function(c){var k=kind(c.id);if(!k)return;c.container.toggle(k===cur);});
			document.querySelectorAll('.ogcd-dev .button').forEach(function(b){b.setAttribute('aria-pressed',b.dataset.dev===cur?'true':'false');});}
		function setPreview(dev){var fb=document.querySelector('#customize-footer-actions .devices button[data-device="'+dev+'"]');if(fb&&!fb.classList.contains('active')){fb.click();}var pd=api.previewedDevice||(api.state&&api.state('previewedDevice'));if(pd&&pd.get()!==dev)pd.set(dev);}
		document.addEventListener('click',function(e){var b=e.target.closest&&e.target.closest('.ogcd-dev .button');if(!b)return;e.preventDefault();show(b.dataset.dev);setPreview(b.dataset.dev);},true);
		if(api.previewedDevice)api.previewedDevice.bind(function(d){show(d);});
		api.section('orego_cards',function(sec){sec.expanded.bind(function(open){if(open)show(api.previewedDevice?api.previewedDevice.get():'desktop');});});
		show(api.previewedDevice?api.previewedDevice.get():'desktop');
	});})(wp.customize);
	</script>
	<?php
}, 1001 ); // after customize-controls.js

/* Appearance -> Car Cards shortcut */
add_action( 'admin_menu', function () {
	global $submenu;
	if ( isset( $submenu['themes.php'] ) && current_user_can( 'customize' ) ) {
		$submenu['themes.php'][] = array( 'Car Cards', 'customize', 'customize.php?autofocus[section]=orego_cards', 'Car Cards' ); // phpcs:ignore
	}
}, 1003 );
