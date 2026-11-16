<?php
/**
 * Appearance -> Customize -> Browse Cars: settings for the Cars page.
 * Title and subtitle, car count and sort menu, default order, how many cars show at first
 * (with a "Show more" button), cards per row, and which filters appear.
 * The look of each card stays in Customize -> Car Cards.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_browse_defaults() {
	return array(
		'title'        => 'Browse cars',
		'subtitle'     => '',
		'show_sort'    => true,
		'sort'         => 'new',
		'per'          => 0,
		'more_text'    => 'Show more cars',
		'cols'         => '',
		'cols_t'       => '',
		'cols_m'       => '',
		'show_filters' => true,
		'filters_side' => 'start',
		'filters_open' => 'open',
		'f_type'       => true,
		'f_trans'      => true,
		'f_fuel'       => true,
		'f_seats'      => true,
		'f_price'      => true,
		'f_loc'        => true,
	);
}

function orego_browse_opt( $k ) {
	$d = orego_browse_defaults();
	return get_theme_mod( 'orego_browse_' . $k, $d[ $k ] ?? '' );
}

function orego_browse_filter_groups() {
	return array( 'type' => 'Car type', 'trans' => 'Transmission', 'fuel' => 'Fuel', 'seats' => 'Seats', 'price' => 'Price per day', 'loc' => 'Pick-up location' );
}

/** Filters are shown only when switched on and at least one group is left. */
function orego_browse_filters_on( $ph = false ) {
	$o = ( $ph && function_exists( 'orego_browse_opt_ph' ) ) ? 'orego_browse_opt_ph' : 'orego_browse_opt';
	if ( ! $o( 'show_filters' ) ) {
		return false;
	}
	foreach ( array_keys( orego_browse_filter_groups() ) as $g ) {
		if ( $o( 'f_' . $g ) ) {
			return true;
		}
	}
	return false;
}

/** Phone value of a Browse setting (the desktop value when there is no phone copy). */
function orego_browse_o( $k, $ph ) {
	return ( $ph && function_exists( 'orego_browse_opt_ph' ) ) ? orego_browse_opt_ph( $k ) : orego_browse_opt( $k );
}

/** True on the page that holds the car filters (the Cars page). */
function orego_browse_is_page() {
	static $is = null;
	if ( null !== $is ) {
		return $is;
	}
	if ( ! did_action( 'wp' ) ) {
		return false;
	}
	$is = false;
	if ( is_page() ) {
		$id = get_queried_object_id();
		$is = false !== strpos( (string) get_post_meta( $id, '_elementor_data', true ) . (string) get_post_field( 'post_content', $id ), 'orego_car_filters' );
	}
	return $is;
}

add_filter( 'body_class', function ( $c ) {
	if ( ! orego_browse_is_page() ) {
		return $c;
	}
	$c[] = 'og-browse';
	// -d = desktop and tablet (768px and up), -m = phone
	foreach ( array( 'd' => false, 'm' => true ) as $sfx => $ph ) {
		if ( ! orego_browse_o( 'show_sort', $ph ) ) {
			$c[] = 'og-browse-nosort-' . $sfx;
		}
		if ( ! orego_browse_filters_on( $ph ) ) {
			$c[] = 'og-browse-nofilters-' . $sfx;
		}
	}
	if ( orego_browse_filters_on() && 'end' === orego_browse_opt( 'filters_side' ) ) {
		$c[] = 'og-browse-side-end';
	}
	return $c;
} );

/* Elementor keeps a ready-made copy of each widget; build the title fresh so these settings apply. */
add_filter( 'elementor/element/is_dynamic_content', function ( $dynamic, $raw ) {
	return $dynamic || false !== strpos( ' ' . (string) ( $raw['settings']['_css_classes'] ?? '' ) . ' ', ' og-br-title ' );
}, 10, 2 );

/* Title and subtitle: replace the text of the page heading marked og-br-title. */
add_filter( 'elementor/widget/render_content', function ( $content, $widget ) {
	if ( ! orego_browse_is_page() || false === strpos( ' ' . (string) $widget->get_settings( '_css_classes' ) . ' ', ' og-br-title ' ) ) {
		return $content;
	}
	$td = trim( (string) orego_browse_opt( 'title' ) );
	$tm = trim( (string) orego_browse_o( 'title', true ) );
	if ( '' !== $td || '' !== $tm ) {
		$content = preg_replace_callback( '#(<h[1-6][^>]*>)(.*?)(</h[1-6]>)#s', function ( $m ) use ( $td, $tm ) {
			$d = '' !== $td ? esc_html( $td ) : $m[2]; // empty = the Elementor title
			$p = '' !== $tm ? esc_html( $tm ) : $m[2];
			return $m[1] . ( $d === $p ? $d : '<span class="og-dv-d">' . $d . '</span><span class="og-dv-m">' . $p . '</span>' ) . $m[3];
		}, $content, 1 );
	}
	$sd = trim( (string) orego_browse_opt( 'subtitle' ) );
	$sm = trim( (string) orego_browse_o( 'subtitle', true ) );
	if ( $sd === $sm ) {
		if ( '' !== $sd ) {
			$content .= '<p class="og-br-sub">' . nl2br( esc_html( $sd ) ) . '</p>';
		}
	} else {
		if ( '' !== $sd ) {
			$content .= '<p class="og-br-sub og-dv-d">' . nl2br( esc_html( $sd ) ) . '</p>';
		}
		if ( '' !== $sm ) {
			$content .= '<p class="og-br-sub og-dv-m">' . nl2br( esc_html( $sm ) ) . '</p>';
		}
	}
	return $content;
}, 10, 2 );

add_action( 'wp_head', function () {
	if ( ! orego_browse_is_page() ) {
		return;
	}
	$css  = '.og-br-sub{margin:10px 0 0;max-width:62ch;font:400 17px/1.65 Cairo,Tahoma,sans-serif;color:#6B7280}';
	$css .= '@media (min-width:768px){body.og-browse-nosort-d .og-sort{display:none!important}}@media (max-width:767px){body.og-browse-nosort-m .og-sort{display:none!important}}';
	$css .= '.og-pg-off{display:none!important}';
	$css .= '.og-more-wrap{display:flex;flex-direction:column;align-items:center;gap:12px;margin-top:36px;font-family:Cairo,Tahoma,sans-serif}.og-more-wrap[hidden]{display:none}';
	$css .= '.og-more-n{margin:0;font-size:14px;font-weight:600;color:#6B7280}';
	$css .= '.og-more-btn{font:700 15px/1.2 Cairo,Tahoma,sans-serif;color:#15171C;background:#FFFFFF;border:1.5px solid #E2E5EA;border-radius:999px;padding:14px 30px;cursor:pointer;box-shadow:0 4px 14px rgba(21,23,28,.06);transition:border-color .15s,box-shadow .15s,transform .1s}';
	$css .= '.og-more-btn:hover{border-color:var(--hx-primary,#15171C);box-shadow:0 8px 22px rgba(21,23,28,.10)}.og-more-btn:active{transform:translateY(1px)}.og-more-btn:focus-visible{outline:2px solid var(--hx-primary,#15171C);outline-offset:3px}';

	// Desktop & tablet use the desktop settings, phones the phone settings.
	foreach ( array( 'd' => array( false, '(min-width:768px)' ), 'm' => array( true, '(max-width:767px)' ) ) as $sfx => $dv ) {
		$in = '';
		// Filters: hide groups that are switched off.
		foreach ( array_keys( orego_browse_filter_groups() ) as $g ) {
			if ( ! orego_browse_o( 'f_' . $g, $dv[0] ) ) {
				$in .= '.og-side .og-fg[data-g="' . $g . '"],.og-mpill[data-g="' . $g . '"]{display:none!important}';
			}
		}
		// No filters: drop the panel and its phone button, keep the phone bar (it holds the sort menu).
		$nf  = 'body.og-browse-nofilters-' . $sfx;
		$in .= $nf . ' .og-side,' . $nf . ' .og-fbtn,' . $nf . ' .og-scrim,' . $nf . ' .og-mpill{display:none!important}';
		$in .= $nf . '.og-browse-nosort-' . $sfx . ' .og-side-wrap{display:none!important}';
		$css .= '@media ' . $dv[1] . '{' . $in . '}';
	}
	$css .= '@media (min-width:1025px){body.og-browse-nofilters-d .og-side-wrap{display:none!important}body.og-browse-nofilters-d .og-cars-wrap{width:100%!important;max-width:100%!important}';
	$css .= 'body.og-browse-side-end .e-con:has(> .og-side-wrap),body.og-browse-side-end .e-con-inner:has(> .og-side-wrap){flex-direction:row-reverse}}';

	// Cars per row on each screen size. Empty keeps the default (desktop follows Car Cards).
	$grid = 'html body.og-browse .og-cars:not(.og-cars-none){grid-template-columns:repeat(%d,minmax(0,1fr))}';
	foreach ( array( 'cols' => '(min-width:1025px)', 'cols_t' => '(min-width:768px) and (max-width:1024px)', 'cols_m' => '(max-width:767px)' ) as $k => $mq ) {
		$n = (int) orego_browse_opt( $k );
		if ( $n >= 1 && $n <= 4 ) {
			$css .= '@media ' . $mq . '{' . sprintf( $grid, $n ) . '}';
		}
	}
	// Two cards side by side on a phone: tighter cards so the text fits.
	if ( 2 === (int) orego_browse_opt( 'cols_m' ) ) {
		$css .= '@media (max-width:767px){html body.og-browse .og-cars:not(.og-cars-none){gap:12px}body.og-browse .og-cars .og-v{font-size:13px}body.og-browse .og-cars .og-more{font-size:12px;padding-inline:8px}}';
	}
	echo '<style id="og-browse">' . $css . '</style>'; // phpcs:ignore
}, 40 );

/* Default order, filter groups open or closed, and "Show more". */
add_action( 'wp_footer', function () {
	if ( ! orego_browse_is_page() ) {
		return;
	}
	$cfg = array(
		'per'  => max( 0, (int) orego_browse_opt( 'per' ) ),
		'sort' => (string) orego_browse_opt( 'sort' ),
		'open' => (string) orego_browse_opt( 'filters_open' ),
		'more' => (string) ( orego_browse_opt( 'more_text' ) ?: 'Show more cars' ),
		'ph'   => array(
			'per'  => max( 0, (int) orego_browse_o( 'per', true ) ),
			'sort' => (string) orego_browse_o( 'sort', true ),
			'more' => (string) ( orego_browse_o( 'more_text', true ) ?: 'Show more cars' ),
		),
	);
	?>
<script id="og-browse-js">
(function(){
	var C=<?php echo wp_json_encode( $cfg ); ?>;
	if(C.ph&&window.matchMedia&&window.matchMedia('(max-width:767px)').matches){C.per=C.ph.per;C.sort=C.ph.sort;C.more=C.ph.more;}
	function run(){
		var grid=document.querySelector('.og-cars:not(.og-cars-none)');
		if(!grid)return;

		// Default order.
		var s=document.getElementById('og-sort');
		if(s&&C.sort&&C.sort!=='new'&&s.querySelector('option[value="'+C.sort+'"]')){s.value=C.sort;s.dispatchEvent(new Event('change'));}

		// Filter groups open or closed (desktop; phones keep their own behaviour).
		if(C.open!=='open'&&window.matchMedia('(min-width:1025px)').matches){
			var groups=[].slice.call(document.querySelectorAll('.og-side .og-fg')).filter(function(g){return getComputedStyle(g).display!=='none';});
			groups.forEach(function(g,i){g.open=C.open==='first'&&i===0;});
		}

		// Show the first N cars, then a "Show more" button.
		if(!C.per)return;
		var cards=[].slice.call(grid.querySelectorAll('.og-v')),limit=C.per,sig=null;
		var wrap=document.createElement('div');wrap.className='og-more-wrap';wrap.hidden=true;
		wrap.innerHTML='<p class="og-more-n" aria-live="polite"></p><button type="button" class="og-more-btn"></button>';
		grid.parentNode.insertBefore(wrap,grid.nextSibling);
		var btn=wrap.querySelector('button'),info=wrap.querySelector('p');btn.textContent=C.more;
		function ord(c){return c.style.order!==''?+c.style.order:(+c.dataset.idx||0);}
		function layout(){
			var vis=cards.filter(function(c){return c.style.display!=='none';});
			vis.sort(function(a,b){return ord(a)-ord(b);});
			var key=vis.map(function(c){return c.dataset.idx;}).sort().join(',');
			if(key!==sig){sig=key;limit=C.per;} // the filters changed: start again from the first cars
			cards.forEach(function(c){c.classList.remove('og-pg-off');});
			vis.forEach(function(c,i){if(i>=limit)c.classList.add('og-pg-off');});
			wrap.hidden=vis.length<=limit;
			info.textContent='Showing '+Math.min(limit,vis.length)+' of '+vis.length+' cars';
		}
		btn.addEventListener('click',function(){limit+=C.per;layout();});
		var queued=false;new MutationObserver(function(){if(queued)return;queued=true;Promise.resolve().then(function(){queued=false;layout();});}).observe(grid,{attributes:true,subtree:true,attributeFilter:['style']});
		layout();
	}
	if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(run,0);});else setTimeout(run,0);
})();
</script>
	<?php
}, 100 );

/* Customizer section, right after Car Cards. */
add_action( 'customize_register', function ( $wp ) {
	$d   = orego_browse_defaults();
	$sec = 'orego_browse';
	$wp->add_section( $sec, array(
		'title'       => 'Browse Cars',
		'priority'    => 27,
		'description' => 'Control the Cars page: its title, how many cars show at first, the order and the filters. The look of each car card is set in Car Cards. Use the Desktop / Phone switch to set phones apart.',
	) );
	$prio = 1;
	$bool = function ( $v ) {
		return (bool) $v;
	};
	$pick = function ( $choices, $def ) {
		return function ( $v ) use ( $choices, $def ) {
			return array_key_exists( (string) $v, $choices ) ? (string) $v : $def;
		};
	};
	$add = function ( $k, $san, $control ) use ( $wp, $d, $sec, &$prio ) {
		$wp->add_setting( 'orego_browse_' . $k, array( 'default' => $d[ $k ], 'sanitize_callback' => $san, 'transport' => 'refresh' ) );
		$wp->add_control( 'orego_browse_' . $k, array_merge( array( 'section' => $sec, 'priority' => $prio++ ), $control ) );
	};
	$heading = function ( $id, $text ) use ( $wp, $sec, &$prio ) {
		$wp->add_setting( 'orego_browse_h_' . $id, array( 'sanitize_callback' => '__return_empty_string' ) );
		$wp->add_control( 'orego_browse_h_' . $id, array( 'section' => $sec, 'priority' => $prio++, 'type' => 'hidden', 'description' => '<strong style="display:block;margin:14px 0 0;padding-top:12px;border-top:1px solid #dcdcde;font-size:13px;color:#1d2327;text-transform:uppercase;letter-spacing:.04em">' . esc_html( $text ) . '</strong>' ) );
	};
	$filters_on = function () use ( $wp ) {
		return (bool) $wp->get_setting( 'orego_browse_show_filters' )->value();
	};

	$heading( 'top', 'Top of the page' );
	$add( 'title', 'sanitize_text_field', array( 'label' => 'Page title', 'type' => 'text', 'description' => 'Empty = keep the title set in Elementor.' ) );
	$add( 'subtitle', 'sanitize_textarea_field', array( 'label' => 'Text under the title', 'type' => 'textarea', 'description' => 'Optional, for example your pick-up airports. Leave empty to hide it.' ) );
	$add( 'show_sort', $bool, array( 'label' => 'Show the sort menu', 'type' => 'checkbox' ) );

	$heading( 'cols', 'Cars per row' );
	$cd  = array( '' => 'Same as Car Cards', '1' => '1', '2' => '2', '3' => '3', '4' => '4' );
	$ct  = array( '' => 'Default (2)', '1' => '1', '2' => '2', '3' => '3' );
	$cm  = array( '' => 'Default (1)', '1' => '1', '2' => '2' );
	$add( 'cols', $pick( $cd, '' ), array( 'label' => 'Desktop', 'type' => 'select', 'choices' => $cd, 'description' => 'Screens wider than 1024px.' ) );
	$add( 'cols_t', $pick( $ct, '' ), array( 'label' => 'Tablet', 'type' => 'select', 'choices' => $ct, 'description' => 'Screens 768px to 1024px.' ) );
	$add( 'cols_m', $pick( $cm, '' ), array( 'label' => 'Phone', 'type' => 'select', 'choices' => $cm, 'description' => 'Screens under 768px.' ) );

	$heading( 'list', 'Car list' );
	$sorts = array( 'new' => 'Newest first', 'price-asc' => 'Price: low to high', 'price-desc' => 'Price: high to low', 'rating' => 'Top rated' );
	$add( 'sort', $pick( $sorts, 'new' ), array( 'label' => 'Default order', 'type' => 'select', 'choices' => $sorts, 'description' => 'Visitors can still change it with the sort menu.' ) );
	$add( 'per', function ( $v ) {
		return max( 0, min( 60, (int) $v ) );
	}, array( 'label' => 'Cars to show at first', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 60, 'step' => 1 ), 'description' => '0 = show all cars. Otherwise a "Show more" button loads the next ones.' ) );
	$add( 'more_text', 'sanitize_text_field', array( 'label' => '"Show more" button text', 'type' => 'text', 'active_callback' => function () use ( $wp ) {
		return (int) $wp->get_setting( 'orego_browse_per' )->value() > 0;
	} ) );
	$heading( 'filters', 'Filters' );
	$add( 'show_filters', $bool, array( 'label' => 'Show the filters', 'type' => 'checkbox', 'description' => 'When off, the cars use the full width.' ) );
	$sides = array( 'start' => 'Before the cars (left in English, right in Arabic)', 'end' => 'After the cars (right in English, left in Arabic)' );
	$add( 'filters_side', $pick( $sides, 'start' ), array( 'label' => 'Filters position on desktop', 'type' => 'radio', 'choices' => $sides, 'active_callback' => $filters_on ) );
	$opens = array( 'open' => 'All open', 'first' => 'Only the first one open', 'closed' => 'All closed' );
	$add( 'filters_open', $pick( $opens, 'open' ), array( 'label' => 'Filter groups on desktop', 'type' => 'select', 'choices' => $opens, 'active_callback' => $filters_on ) );
	foreach ( orego_browse_filter_groups() as $g => $label ) {
		$add( 'f_' . $g, $bool, array( 'label' => 'Show filter: ' . $label, 'type' => 'checkbox', 'active_callback' => $filters_on ) );
	}
}, 21 );

/* Saved settings show up at once for visitors. */
add_action( 'customize_save_after', function () {
	do_action( 'litespeed_purge_all' );
} );
