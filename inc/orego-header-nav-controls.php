<?php
/**
 * Customize -> Header: nicer controls.
 * - Every number in the Header sections becomes a slider + number + unit + reset.
 * - Primary Navigation: grouped Design tab (Colours / Hover & current page / Text / Spacing),
 *   picture cards for the hover style, button rows for weight and letter case,
 *   options that only apply to one style show only with that style,
 *   plus hover background, underline colour & thickness and letter spacing.
 * All with live preview.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ogn_mod( $k, $def = '' ) {
	return get_theme_mod( 'orego_' . $k, $def );
}

/** Fonts for the menu links: key => [label, css family, Google Fonts name or '' / 'plex' / 'system']. */
function ogn_fonts() {
	$ar = '"Cairo","SF Arabic","Geeza Pro",Tahoma,sans-serif'; // Arabic letters keep the site font
	return array(
		''           => array( 'Site font', '', '' ),
		'plex'       => array( 'IBM Plex Sans', '"OgPlex",' . $ar, 'plex' ),
		'system'     => array( 'System font', '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,' . $ar, 'system' ),
		'inter'      => array( 'Inter', '"Inter",' . $ar, 'Inter' ),
		'poppins'    => array( 'Poppins', '"Poppins",' . $ar, 'Poppins' ),
		'montserrat' => array( 'Montserrat', '"Montserrat",' . $ar, 'Montserrat' ),
		'manrope'    => array( 'Manrope', '"Manrope",' . $ar, 'Manrope' ),
		'dmsans'     => array( 'DM Sans', '"DM Sans",' . $ar, 'DM Sans' ),
		'jakarta'    => array( 'Plus Jakarta Sans', '"Plus Jakarta Sans",' . $ar, 'Plus Jakarta Sans' ),
		'outfit'     => array( 'Outfit', '"Outfit",' . $ar, 'Outfit' ),
		'sora'       => array( 'Sora', '"Sora",' . $ar, 'Sora' ),
		'spacegrot'  => array( 'Space Grotesk', '"Space Grotesk",' . $ar, 'Space Grotesk' ),
		'playfair'   => array( 'Playfair Display', '"Playfair Display",Georgia,' . $ar, 'Playfair Display' ),
	);
}

/** Google Fonts stylesheet URL for one family (weights 400-800 where available). */
function ogn_gfont_url( $name, $text = '' ) {
	$w = in_array( $name, array( 'Playfair Display', 'Space Grotesk' ), true ) ? '400;500;600;700' : '400;500;600;700;800';
	if ( 'Space Grotesk' === $name ) {
		$w = '400;500;600;700';
	}
	return 'https://fonts.googleapis.com/css2?family=' . str_replace( ' ', '+', $name ) . ':wght@' . $w . '&display=swap' . ( $text ? '&text=' . rawurlencode( $text ) : '' );
}

/** @font-face for the local IBM Plex files. */
function ogn_plex_faces() {
	$u = get_stylesheet_directory_uri() . '/assets/fonts/';
	$o = '';
	foreach ( array( 400, 500, 600, 700 ) as $w ) {
		$o .= '@font-face{font-family:"OgPlex";font-weight:' . $w . ';font-display:swap;src:url(' . $u . 'plex-arabic-' . $w . '-latin.woff2) format("woff2");unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+2000-206F,U+20AC,U+2122,U+2212}';
		$o .= '@font-face{font-family:"OgPlex";font-weight:' . $w . ';font-display:swap;src:url(' . $u . 'plex-arabic-' . $w . '-arabic.woff2) format("woff2");unicode-range:U+0600-06FF,U+0750-077F,U+08A0-08FF,U+FB50-FDFF,U+FE70-FEFF}';
	}
	return $o;
}

/** CSS for the extra navigation options. */
function ogn_css() {
	$P     = 'html body .ogx2 .ogx2-links';
	$style = (string) ogn_mod( 'nav_style', 'pill' );
	$o     = '';
	$bg    = sanitize_hex_color( (string) ogn_mod( 'nav_hover_bg' ) );
	if ( $bg && 'pill' === $style ) {
		$o .= "$P li>a:hover,$P li:focus-within>a,$P li.is-current>a{background:$bg!important}";
	}
	if ( 'underline' === $style ) {
		$lc = sanitize_hex_color( (string) ogn_mod( 'nav_line' ) );
		$lw = ogn_mod( 'nav_line_w', '' );
		if ( $lc ) {
			$o .= "$P li>a::after{background:$lc!important}";
		}
		if ( '' !== $lw && null !== $lw ) {
			$o .= "$P li>a::after{height:" . max( 1, min( 8, (int) $lw ) ) . 'px!important}';
		}
	}
	$fonts = ogn_fonts();
	$fk    = (string) ogn_mod( 'nav_font', '' );
	if ( '' !== $fk && isset( $fonts[ $fk ] ) ) {
		$src = $fonts[ $fk ][2];
		if ( 'plex' === $src ) {
			$o = ogn_plex_faces() . $o;
		} elseif ( 'system' !== $src && is_customize_preview() ) {
			$o = '@import url(' . esc_url_raw( ogn_gfont_url( $src ) ) . ');' . $o; // live preview
		}
		$o .= "$P li>a{font-family:" . $fonts[ $fk ][1] . '!important}';
	}
	$ls = ogn_mod( 'nav_ls', '' );
	if ( '' !== $ls && null !== $ls ) {
		$o .= "$P li>a{letter-spacing:" . round( (float) $ls, 2 ) . 'px!important}';
	}
	return $o;
}

add_action( 'wp_head', function () {
	$fonts = ogn_fonts();
	$fk    = (string) ogn_mod( 'nav_font', '' );
	if ( ! is_customize_preview() && isset( $fonts[ $fk ] ) && ! in_array( $fonts[ $fk ][2], array( '', 'plex', 'system' ), true ) ) {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="' . esc_url( ogn_gfont_url( $fonts[ $fk ][2] ) ) . '">' . "\n";
	}
	// menu links stay on one line; tighter spacing on small laptop screens
	echo '<style id="ogh-nav-fit">html body .ogx2 .ogx2-links li>a{white-space:nowrap}@media (min-width:1025px) and (max-width:1280px){html body .ogx2 .ogx2-links li>a{padding-left:9px!important;padding-right:9px!important}html body .ogx2 .ogx2-links ul{gap:2px!important}}html body .ogx2 .ogx2-menu a:hover,html body .ogx2 .ogx2-menu a:focus-visible,html body .ogx2 .ogx2-menu a[aria-current=page]{background:#F3F4F6!important;color:#15171C!important}html body .ogx2 .ogx2-menu a:focus-visible{outline:2px solid #15171C;outline-offset:-2px}</style>' . "\n";
	echo '<style id="ogh-nav-plus">' . ogn_css() . '</style>' . "\n"; // phpcs:ignore
}, 41 );

/* ---------- settings, order, live preview ---------- */
add_action( 'customize_register', function ( $wp ) {
	$sec = 'ogh_menu';
	if ( ! $wp->get_section( $sec ) ) {
		return;
	}
	$hex = function ( $v ) {
		return (string) sanitize_hex_color( (string) $v );
	};
	$num = function ( $v ) {
		return ( '' === $v || null === $v ) ? '' : (string) round( (float) $v, 2 );
	};
	$wp->add_setting( 'orego_nav_hover_bg', array( 'default' => '', 'sanitize_callback' => $hex, 'transport' => 'postMessage' ) );
	$wp->add_control( new WP_Customize_Color_Control( $wp, 'orego_nav_hover_bg', array(
		'section'     => $sec,
		'label'       => 'Hover background',
		'description' => 'The soft box behind the link. Empty = current design.',
	) ) );
	$wp->add_setting( 'orego_nav_line', array( 'default' => '', 'sanitize_callback' => $hex, 'transport' => 'postMessage' ) );
	$wp->add_control( new WP_Customize_Color_Control( $wp, 'orego_nav_line', array(
		'section'     => $sec,
		'label'       => 'Underline colour',
		'description' => 'Empty = same as the link.',
	) ) );
	$wp->add_setting( 'orego_nav_line_w', array( 'default' => 2, 'sanitize_callback' => $num, 'transport' => 'postMessage' ) );
	$wp->add_control( 'orego_nav_line_w', array(
		'section'     => $sec,
		'label'       => 'Underline thickness',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 1, 'max' => 8, 'step' => 1 ),
	) );
	$wp->add_setting( 'orego_nav_ls', array( 'default' => '', 'sanitize_callback' => $num, 'transport' => 'postMessage' ) );
	$wp->add_control( 'orego_nav_ls', array(
		'section'     => $sec,
		'label'       => 'Letter spacing',
		'type'        => 'number',
		'input_attrs' => array( 'min' => -1, 'max' => 4, 'step' => 0.1 ),
	) );

	$choices = array();
	foreach ( ogn_fonts() as $k => $f ) {
		$choices[ $k ] = $f[0];
	}
	$wp->add_setting( 'orego_nav_font', array(
		'default'           => '',
		'transport'         => 'postMessage',
		'sanitize_callback' => function ( $v ) {
			return array_key_exists( (string) $v, ogn_fonts() ) ? (string) $v : '';
		},
	) );
	$wp->add_control( 'orego_nav_font', array(
		'section'     => $sec,
		'label'       => 'Font',
		'description' => 'Arabic text keeps the site’s Arabic font.',
		'type'        => 'select',
		'choices'     => $choices,
	) );

	// clearer labels and order
	$order = array(
		'orego_nav_color'     => array( 30, 'Link colour', 'Empty = current design.' ),
		'orego_nav_hover'     => array( 31, 'Hover & current page colour', 'Empty = same as the link colour.' ),
		'orego_nav_hover_bg'  => array( 32, null, null ),
		'orego_nav_line'      => array( 33, null, null ),
		'orego_nav_style'     => array( 40, 'Style', '' ),
		'orego_nav_line_w'    => array( 41, null, null ),
		'orego_nav_radius'    => array( 42, 'Box roundness', '' ),
		'orego_nav_font'      => array( 49, null, null ),
		'orego_nav_size'      => array( 50, 'Font size', '' ),
		'orego_nav_weight'    => array( 51, 'Font weight', '' ),
		'orego_nav_transform' => array( 52, 'Letter case', '' ),
		'orego_nav_ls'        => array( 53, null, null ),
		'orego_nav_gap'       => array( 60, 'Space between links', '' ),
		'orego_nav_pad_x'     => array( 61, 'Padding inside each link', '' ),
	);
	foreach ( $order as $id => $o ) {
		if ( $c = $wp->get_control( $id ) ) {
			$c->priority = $o[0];
			if ( null !== $o[1] ) {
				$c->label = $o[1];
			}
			if ( null !== $o[2] ) {
				$c->description = $o[2];
			}
		}
	}

	if ( isset( $wp->selective_refresh ) ) {
		$wp->selective_refresh->add_partial( 'ogh_nav_plus', array(
			'selector'            => '#ogh-nav-plus',
			'settings'            => array( 'orego_nav_hover_bg', 'orego_nav_line', 'orego_nav_line_w', 'orego_nav_ls', 'orego_nav_style', 'orego_nav_font' ),
			'container_inclusive' => false,
			'fallback_refresh'    => false,
			'render_callback'     => 'ogn_css',
		) );
	}
}, 40 );

add_action( 'customize_preview_init', function () {
	add_action( 'wp_footer', function () {
		echo '<script>(function(api){if(!api||!api.selectiveRefresh)return;api.bind("preview-ready",function(){var p=api.selectiveRefresh.partial("ogh_nav_plus");if(p){p.createEditShortcutForPlacement=function(){};}});})(window.wp&&wp.customize);</script>';
	}, 99 );
} );

/* ---------- the controls ---------- */
add_action( 'customize_controls_print_footer_scripts', function () {
	global $wp_customize;
	$defs = array();
	foreach ( $wp_customize->controls() as $c ) {
		if ( preg_match( '/^og[hf]_/', (string) $c->section ) && $c->setting ) {
			$defs[ $c->id ] = $c->setting->default;
		}
	}
	$fam = array();
	foreach ( ogn_fonts() as $f ) {
		if ( ! in_array( $f[2], array( '', 'plex', 'system' ), true ) ) {
			$fam[] = 'family=' . str_replace( ' ', '+', $f[2] ) . ':wght@600';
		}
	}
	echo '<link rel="stylesheet" href="' . esc_url( 'https://fonts.googleapis.com/css2?' . implode( '&', $fam ) . '&display=swap&text=' . rawurlencode( 'CarsAboutusContact ' ) ) . '">';
	?>
	<style>
	<?php echo ogn_plex_faces(); // phpcs:ignore ?>
	/* slider */
	.ogn-num{display:flex;align-items:center;gap:10px;margin-top:4px}
	.ogn-num input[type=range]{flex:1;min-width:0;margin:0;accent-color:#2271b1;height:22px}
	.ogn-num .ogn-box{display:flex;align-items:center;border:1px solid #8c8f94;border-radius:4px;background:#fff;height:32px;overflow:hidden}
	.ogn-num .ogn-box input[type=number]{width:52px!important;min-width:0;height:30px;border:0!important;box-shadow:none!important;padding:0 2px 0 8px;text-align:right;-moz-appearance:textfield}
	.ogn-num .ogn-box input::-webkit-inner-spin-button{opacity:.5}
	.ogn-num .ogn-unit{padding:0 8px 0 2px;color:#646970;font-size:12px}
	.ogn-reset{width:28px;height:28px;flex:none;border:0;border-radius:50%;background:none;color:#8c8f94;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;visibility:hidden}
	.ogn-reset.is-on{visibility:visible}
	.ogn-reset:hover{background:#f0f0f1;color:#2271b1}
	.ogn-reset svg{width:16px;height:16px}
	/* group headings */
	.ogn-h{margin:-14px -16px 14px;padding:10px 16px;background:#f6f7f7;border-bottom:1px solid #e2e4e7;border-radius:6px 6px 0 0;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#50575e}
	/* picture cards */
	.ogn-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px}
	.ogn-card{display:flex;flex-direction:column;align-items:center;gap:8px;min-width:0;overflow:hidden;padding:12px 4px 10px;border:1.5px solid #dcdcde;border-radius:8px;background:#fff;cursor:pointer;font-size:12px;font-weight:600;color:#3c434a}
	.ogn-card:hover{border-color:#8c8f94}
	.ogn-card.is-on{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1;color:#2271b1}
	.ogn-pv{display:flex;gap:2px;align-items:center;height:24px;max-width:100%}
	.ogn-pv i{font-style:normal;font-size:10px;font-weight:600;color:#1d2327;padding:4px 5px;border-radius:6px;line-height:1;position:relative}
	.ogn-pv i+i{color:#8c8f94}
	.ogn-pv-pill i:first-child{background:#e7eaee}
	.ogn-pv-underline i:first-child::after{content:"";position:absolute;left:6px;right:6px;bottom:-1px;height:2px;border-radius:2px;background:currentColor}
	.ogn-pv-plain i:first-child{color:#2271b1}
	/* button rows */
	.ogn-seg{display:flex;border:1px solid #c3c4c7;border-radius:6px;overflow:hidden;background:#fff}
	.ogn-seg button{flex:1;min-width:0;padding:8px 2px 6px;border:0;border-inline-start:1px solid #e2e4e7;background:none;cursor:pointer;color:#3c434a;display:flex;flex-direction:column;align-items:center;gap:2px}
	.ogn-seg button:first-child{border-inline-start:0}
	.ogn-seg button:hover{background:#f6f7f7}
	.ogn-seg button.is-on{background:#2271b1;color:#fff}
	.ogn-seg b{font-size:15px;line-height:1.2}
	.ogn-seg small{font-size:10px;opacity:.75;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
	#customize-theme-controls .customize-control.ogn-hide{display:none!important}
	.ogn-fonts{display:flex;flex-direction:column;gap:6px;max-height:300px;overflow:auto;padding:2px}
	.ogn-font{display:flex;flex-direction:column;align-items:flex-start;gap:2px;padding:9px 12px;border:1.5px solid #dcdcde;border-radius:8px;background:#fff;cursor:pointer;text-align:left}
	.ogn-font:hover{border-color:#8c8f94}
	.ogn-font.is-on{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}
	.ogn-font-s{font-size:16px;font-weight:600;color:#1d2327;white-space:pre}
	.ogn-font-n{font-size:11px;color:#646970}
	.ogn-font.is-on .ogn-font-n{color:#2271b1}
	.ogn-native{display:none!important}
	</style>
	<script>
	(function($,api){
	if(!api)return;
	var DEF=<?php echo wp_json_encode( $defs ); ?>;
	var RESET='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>';
	function unitFor(id){return /_op$|_op_/.test(id)?'%':'px';}
	function setting(c){return c.setting||api(c.id);}

	/* number -> slider + number + unit + reset */
	function slider(c){
		var $in=c.container.find('input[type=number]').first();if(!$in.length||c.container.find('.ogn-num').length)return;
		var s=setting(c);if(!s)return;
		var min=parseFloat($in.attr('min')),max=parseFloat($in.attr('max')),step=parseFloat($in.attr('step'))||1;
		if(isNaN(min))min=0;if(isNaN(max)||max>500)max=Math.max(min+100,parseFloat(s())||0);
		var def=DEF[c.id];var defNum=(def===''||def==null)?null:parseFloat(def);
		var $w=$('<div class="ogn-num"></div>'),$r=$('<input type="range">').attr({min:min,max:max,step:step,'aria-label':c.params.label||''});
		var $box=$('<span class="ogn-box"></span>'),$u=$('<span class="ogn-unit"></span>').text(unitFor(c.id));
		var $rs=$('<button type="button" class="ogn-reset" title="Reset to default" aria-label="Reset to default"></button>').html(RESET);
		$in.after($w);$box.append($in).append($u);$w.append($r).append($box).append($rs);
		if(defNum!==null)$in.attr('placeholder',defNum);
		function show(){var v=s();var n=(v===''||v==null)?defNum:parseFloat(v);$r.val(n==null||isNaN(n)?min:n);$rs.toggleClass('is-on',!(v===''||v==null||(defNum!==null&&parseFloat(v)===defNum)));if(document.activeElement!==$in[0])$in.val(v===''||v==null?'':v);}
		$r.on('input',function(){s.set(this.value);});
		$rs.on('click',function(){s.set(def==null?'':def);});
		s.bind(show);show();
	}

	/* picture cards for the hover style */
	function cards(c){
		if(c.container.find('.ogn-cards').length)return;var s=setting(c);var $list=c.container.find('input[type=radio]').closest('span, label').parent();
		var $w=$('<div class="ogn-cards" role="radiogroup"></div>');
		var labels={pill:'Soft box',underline:'Underline',plain:'Text only'};
		var ch={};c.container.find('input[type=radio]').each(function(){ch[this.value]=$(this).siblings('label').text()||this.value;});
		Object.keys(ch).forEach(function(k){
			var $b=$('<button type="button" class="ogn-card" role="radio"></button>').attr('data-v',k);
			$b.append('<span class="ogn-pv ogn-pv-'+k+'"><i>Cars</i><i>About</i></span>').append($('<span></span>').text(labels[k]||ch[k]));
			$b.on('click',function(){s.set(k);});$w.append($b);
		});
		c.container.find('input[type=radio]').closest('.customize-inside-control-row, span, label').addClass('ogn-native');
		c.container.append($w);
		function show(){$w.find('.ogn-card').each(function(){var on=$(this).data('v')===s();$(this).toggleClass('is-on',on).attr('aria-checked',on?'true':'false');});}
		s.bind(show);show();
	}

	/* button rows for selects */
	function seg(c,render){
		if(c.container.find('.ogn-seg').length)return;var s=setting(c),$sel=c.container.find('select').first();if(!$sel.length)return;
		var $w=$('<div class="ogn-seg"></div>');
		var ch={},keys=[];$sel.find('option').each(function(){ch[this.value]=$(this).text();keys.push(this.value);});
		keys.forEach(function(k){var $b=$('<button type="button"></button>').attr('data-v',k).html(render(k,ch[k]));$b.attr('title',ch[k]);$b.on('click',function(){s.set(k);});$w.append($b);});
		$sel.addClass('ogn-native').after($w);
		function show(){$w.find('button').each(function(){$(this).toggleClass('is-on',String($(this).data('v'))===String(s()));});}
		s.bind(show);show();
	}
	var SHORT={'400':'Regular','500':'Medium','600':'Semi','700':'Bold','800':'Extra'};

	/* headings + only-when-relevant options */
	var GROUPS={orego_nav_color:'Colours',orego_nav_style:'Hover & current page',orego_nav_font:'Text',orego_nav_gap:'Spacing'};
	var FONTS=<?php $ff = array(); foreach ( ogn_fonts() as $k => $f ) { $ff[ $k ] = array( $f[0], $f[1] ); } echo wp_json_encode( $ff ); ?>;
	function fontList(c){
		if(c.container.find('.ogn-fonts').length)return;var s=setting(c),$sel=c.container.find('select').first();if(!$sel.length)return;
		var $w=$('<div class="ogn-fonts" role="listbox"></div>');
		Object.keys(FONTS).forEach(function(k){var f=FONTS[k];var $b=$('<button type="button" class="ogn-font" role="option"></button>').attr('data-v',k);
			$b.append($('<span class="ogn-font-s"></span>').text('Cars  About us  Contact').css('font-family',f[1]||'inherit')).append($('<span class="ogn-font-n"></span>').text(f[0]));
			$b.on('click',function(){s.set(k);});$w.append($b);});
		$sel.addClass('ogn-native').after($w);
		function show(){$w.find('.ogn-font').each(function(){var on=String($(this).data('v'))===String(s());$(this).toggleClass('is-on',on).attr('aria-selected',on?'true':'false');});}
		s.bind(show);show();
	}
	function heading(c){if(GROUPS[c.id]&&!c.container.find('.ogn-h').length)c.container.prepend($('<div class="ogn-h"></div>').text(GROUPS[c.id]));}
	function conditions(){
		var st=api('orego_nav_style');if(!st)return;
		function apply(){var v=st();
			[['orego_nav_hover_bg','pill'],['orego_nav_radius','pill'],['orego_nav_line','underline'],['orego_nav_line_w','underline']].forEach(function(x){api.control(x[0],function(c){c.container.toggleClass('ogn-hide',v!==x[1]);});});}
		st.bind(apply);apply();
	}

	function enhance(c){
		if(!c||!c.container||!/^og[hf]_/.test(c.section()))return;
		if(c.params.type==='number')slider(c);
		if(c.id==='orego_nav_style')cards(c);
		if(c.id==='orego_nav_font')fontList(c);
		if(c.id==='orego_nav_weight')seg(c,function(k,l){return '<b style="font-weight:'+k+'">Aa</b><small>'+(SHORT[k]||l)+'</small>';});
		if(c.id==='orego_nav_transform')seg(c,function(k,l){var t={none:'Aa',uppercase:'AA',capitalize:'Aa Aa'}[k]||l;return '<b>'+t+'</b><small>'+({none:'As written',uppercase:'Upper',capitalize:'Each word'}[k]||l)+'</small>';});
		heading(c);
	}
	api.bind('ready',function(){
		api.control.each(function(c){c.deferred.embedded.done(function(){enhance(c);});});
		api.control.bind('add',function(c){c.deferred.embedded.done(function(){enhance(c);});});
		conditions();
	});
	})(jQuery,window.wp&&wp.customize);
	</script>
	<?php
}, 1005 );
