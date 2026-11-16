<?php
/**
 * Exotic Cars luxury hero: display font, brand logo helpers (used by the Elementor brands widget) and hero styles.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Car makes in the published fleet, from the car names ("2026 Toyota Corolla" -> Toyota). */
function orego_fleet_makes() {
	$makes = array();
	foreach ( get_posts( array( 'post_type' => 'orego_car', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $p ) {
		$t = trim( preg_replace( '/\b(19|20)\d{2}\b/', '', $p->post_title ) );
		$w = strtok( $t, ' ' );
		if ( $w ) {
			$makes[ mb_convert_case( mb_strtolower( $w ), MB_CASE_TITLE ) ] = true;
		}
	}
	$m = array_keys( $makes );
	sort( $m );
	return $m;
}

/** Brands shown as logos under the hero. Logo files: theme assets/brands/<slug>.svg (Simple Icons, single-colour). */
function orego_hero_brands() {
	$def = array( 'dacia' => 'Dacia', 'renault' => 'Renault', 'hyundai' => 'Hyundai', 'peugeot' => 'Peugeot', 'volkswagen' => 'Volkswagen', 'opel' => 'Opel', 'citroen' => 'Citroën', 'toyota' => 'Toyota' );
	$opt = get_option( 'orego_hero_brands' );
	return is_array( $opt ) && $opt ? $opt : $def;
}

function orego_brand_svg( $slug, $label ) {
	$file = get_template_directory() . '/assets/brands/' . sanitize_file_name( $slug ) . '.svg';
	if ( ! is_readable( $file ) || ! preg_match_all( '/<path[^>]*\sd="([^"]+)"/', (string) file_get_contents( $file ), $m ) ) {
		return '';
	}
	$paths = '';
	foreach ( $m[1] as $d ) {
		$paths .= '<path d="' . esc_attr( $d ) . '"/>';
	}
	return '<svg viewBox="0 0 24 24" fill="currentColor" role="img" aria-label="' . esc_attr( $label ) . '" focusable="false"><title>' . esc_html( $label ) . '</title>' . $paths . '</svg>';
}

add_action( 'wp_enqueue_scripts', function () {
	$f = get_template_directory_uri() . '/assets/orego-fonts/';
	wp_register_style( 'orego-hero', false, array(), '1.0' );
	wp_enqueue_style( 'orego-hero' );
	$css  = '';
	$css .= <<<'CSS'
:root{--og-gold-1:#EBCB94;--og-gold-2:#B98E57;--og-gold:#D4B27C;--og-night:#0B0A09}
.og-hero2{position:relative;overflow:hidden}
.og-hero2::before{content:"";position:absolute;inset:0;z-index:0;pointer-events:none;background:linear-gradient(to left,rgba(11,10,9,.95) 0%,rgba(11,10,9,.82) 32%,rgba(11,10,9,.35) 64%,rgba(11,10,9,.08) 100%),linear-gradient(to top,rgba(11,10,9,.9) 0%,rgba(11,10,9,0) 34%)}
html[dir=ltr] .og-hero2::before{background:linear-gradient(to right,rgba(11,10,9,.95) 0%,rgba(11,10,9,.82) 32%,rgba(11,10,9,.35) 64%,rgba(11,10,9,.08) 100%),linear-gradient(to top,rgba(11,10,9,.9) 0%,rgba(11,10,9,0) 34%)}
.og-hero2>*{position:relative;z-index:1}
/* Typography, colours, sizes and spacing of the hero widgets live in Elementor. These rules only add what Elementor can't: direction-aware letter-spacing and hover polish. */
html[dir=ltr] .og-h2-eyebrow .elementor-heading-title{letter-spacing:.32em}
html[dir=ltr] .og-h2-title .elementor-heading-title{letter-spacing:-.025em}
html[dir=ltr] .og-h2-btn .elementor-button{letter-spacing:.06em}
.og-h2-btn .elementor-button{transition:filter .15s,background-color .15s,border-color .15s}
.og-h2-gold .elementor-button:hover{filter:brightness(1.07)}
.og-h2-btn .elementor-button-icon svg{width:18px;height:18px}
.og-gold{display:inline-block;background:linear-gradient(90deg,var(--og-hl-1,var(--og-gold-1)),var(--og-hl-2,var(--og-hl-1,var(--og-gold-2))));-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent;padding-bottom:.08em}
.og-h2-sub p{margin:0}
.og-h2-btn a:focus-visible{outline:2px solid var(--og-gold);outline-offset:3px}
.og-h2-brands{width:auto!important;max-width:100%}
.og-hb{display:inline-flex;align-items:center;gap:22px;padding:9px 9px 9px 26px;border-radius:999px;background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.03));border:1px solid rgba(255,255,255,.12);box-shadow:inset 0 1px 0 rgba(255,255,255,.08),0 18px 40px -18px rgba(0,0,0,.6);-webkit-backdrop-filter:blur(16px) saturate(140%);backdrop-filter:blur(16px) saturate(140%);max-width:100%}
html[dir=rtl] .og-hb{padding:9px 26px 9px 9px}
.og-hb-label{flex:none;display:flex;align-items:center;gap:18px;color:rgba(255,255,255,.62);font:600 13px/1 var(--hx-font,Cairo,sans-serif);white-space:nowrap}
html[dir=ltr] .og-hb-label{text-transform:uppercase;letter-spacing:.16em;font-size:11.5px}
.og-hb-label::after{content:"";width:1px;height:26px;background:linear-gradient(180deg,transparent,rgba(212,178,124,.6),transparent)}
.og-hb-view{min-width:0}
.og-hb-move{display:flex}
.og-hb-list{display:flex;align-items:center;gap:10px;list-style:none;margin:0;padding:0}
.og-hb-list li{margin:0;flex:none}
.og-hb-dup{display:none}
.og-hb-list a,.og-hb-list span{display:grid;place-items:center;width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.8)!important;text-decoration:none!important;transition:background-color .25s,border-color .25s,color .25s,transform .25s,box-shadow .25s}
.og-hb-list svg{display:block;width:30px;height:30px}
.og-hb-list a:hover,.og-hb-list span:hover,.og-hb-list a:focus-visible{color:#F1D6A8!important;background:rgba(212,178,124,.14);border-color:rgba(212,178,124,.55);transform:translateY(-3px);box-shadow:0 10px 24px -10px rgba(212,178,124,.55);outline:none}
@media (max-width:1024px){.og-hb-list a,.og-hb-list span{width:46px;height:46px}.og-hb-list svg{width:23px;height:23px}}
@media (max-width:767px){
.og-h2-brands{width:100%!important}
.og-hb{display:flex;flex-direction:column;align-items:stretch;gap:14px;padding:16px 0 14px;border-radius:22px;width:100%}
html[dir=rtl] .og-hb{padding:16px 0 14px}
.og-hb-label{justify-content:center;gap:12px;font-size:12px}
.og-hb-label::before{content:"";width:22px;height:1px;background:rgba(212,178,124,.55)}
.og-hb-label::after{width:22px;height:1px;background:rgba(212,178,124,.55)}
.og-hb-view{overflow:hidden;-webkit-mask-image:linear-gradient(90deg,transparent,#000 14%,#000 86%,transparent);mask-image:linear-gradient(90deg,transparent,#000 14%,#000 86%,transparent)}
.og-hb-move{width:max-content;animation:og-hb-scroll 26s linear infinite}
html[dir=rtl] .og-hb-move{animation-name:og-hb-scroll-rtl}
.og-hb-view:active .og-hb-move{animation-play-state:paused}
.og-hb-dup{display:flex}
.og-hb-list{gap:10px;padding-inline-end:10px}
.og-hb-list a,.og-hb-list span{width:50px;height:50px}
.og-hb-list svg{width:25px;height:25px}
}
@keyframes og-hb-scroll{from{transform:translateX(0)}to{transform:translateX(-50%)}}
@keyframes og-hb-scroll-rtl{from{transform:translateX(0)}to{transform:translateX(50%)}}
@media (max-width:767px) and (prefers-reduced-motion:reduce){.og-hb-move{animation:none;width:auto}.og-hb-dup{display:none}.og-hb-view{overflow-x:auto;-webkit-mask-image:none;mask-image:none}.og-hb-list{padding:0 16px}}
CSS;
	// Arabic / right-to-left pages use their own hero photo (Appearance: option orego_hero_bg_rtl); other languages keep the Elementor background.
	$rtl_bg = (int) get_option( 'orego_hero_bg_rtl' );
	if ( $rtl_bg && ! ( function_exists( 'orego_is_editor' ) && orego_is_editor() ) && ( $rtl_url = wp_get_attachment_image_url( $rtl_bg, 'full' ) ) ) {
		$css .= 'html:not([dir=ltr]) .og-hero2.og-hero2.og-hero2{background-image:url(' . esc_url( $rtl_url ) . ')!important;background-position:center center!important}';
		$css .= '@media (max-width:767px){html:not([dir=ltr]) .og-hero2.og-hero2.og-hero2{background-position:22% center!important}}';
	}
	wp_add_inline_style( 'orego-hero', $css );
}, 31 );

add_filter( 'elementor/fonts/additional_fonts', function ( $fonts ) {
	$fonts['Cairo']     = 'system';
	$fonts['Assistant'] = 'system';
	return $fonts;
} );

/** Customizer: Homepage hero -> photo used when the site is in Arabic (other languages use the Elementor background). */
/* The "Homepage hero" Customizer section was removed: the old home page is a draft now. */
/** Elementor: Edit Heading -> Style -> "Gold highlight" colours for words wrapped in <span class="og-gold">. */
add_action( 'elementor/element/heading/section_title_style/before_section_end', function ( $el ) {
	$el->add_control( 'og_hl_head', array(
		'type'      => \Elementor\Controls_Manager::HEADING,
		'label'     => 'Gold highlight',
		'separator' => 'before',
	) );
	$el->add_control( 'og_hl_1', array(
		'label'     => 'Start colour',
		'type'      => \Elementor\Controls_Manager::COLOR,
		'selectors' => array( '{{WRAPPER}} .og-gold' => '--og-hl-1: {{VALUE}};' ),
	) );
	$el->add_control( 'og_hl_2', array(
		'label'     => 'End colour',
		'type'      => \Elementor\Controls_Manager::COLOR,
		'selectors' => array( '{{WRAPPER}} .og-gold' => '--og-hl-2: {{VALUE}};' ),
	) );
	$el->add_control( 'og_hl_note', array(
		'type'            => \Elementor\Controls_Manager::RAW_HTML,
		'raw'             => 'Colours the words wrapped in &lt;span class="og-gold"&gt;…&lt;/span&gt; in the title. Set only the start colour for a solid colour. Empty = default gold.',
		'content_classes' => 'elementor-descriptor',
	) );
} );