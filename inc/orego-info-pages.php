<?php
/**
 * FAQ and Map pages: content blocks that read the live booking settings
 * (pick-up points, cancellation hours, what to bring), so the pages stay correct
 * when those settings change. Used as shortcodes inside the Elementor pages:
 * [orego_faq]  [orego_map]
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* The Locations page was removed: send old links to the Map page. */
add_action( 'template_redirect', function () {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore
	if ( 'locations' === $path && get_post_status( orego_pid( 'map' ) ) === 'publish' ) {
		wp_safe_redirect( orego_purl( 'map' ), 301 );
		exit;
	}
}, 1 );

function ogi_rules() {
	$r = get_option( 'orego_rules' );
	return is_array( $r ) ? $r : array();
}

function ogi_locations() {
	$l = get_option( 'orego_locations' );
	return is_array( $l ) ? array_values( array_filter( array_map( 'trim', $l ) ) ) : array();
}

/** "Agadir Al Massira Airport (AGA)" -> [city, code, name] */
function ogi_loc_parts( $name ) {
	$code = preg_match( '/\(([A-Z0-9]{3,4})\)\s*$/', $name, $m ) ? $m[1] : '';
	$clean = trim( preg_replace( '/\s*\([^)]*\)\s*$/', '', $name ) );
	$city  = preg_split( '/[\s\-]+/', $clean )[0];
	return array( $city, $code, $clean );
}

function ogi_cars_url( $args = array() ) {
	$u = orego_purl( 'cars' );
	return $args ? add_query_arg( array_map( 'rawurlencode', $args ), $u ) : $u;
}

function ogi_icon( $k ) {
	$p = array(
		'plane'  => '<path d="M2.5 19h19"/><path d="M3.9 13.6 6 12.4l3.2 1.1 3.4-2-5.4-4.3 1.8-1 7.3 3 3.4-1.9a1.9 1.9 0 0 1 2.6.7 1.9 1.9 0 0 1-.7 2.6L7.6 18a2 2 0 0 1-2.3-.2L3.5 16.2a1.6 1.6 0 0 1 .4-2.6Z"/>',
		'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'pin'    => '<path d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'flight' => '<path d="M4 7h16v12H4z"/><path d="M8 3v4M16 3v4M4 11h16"/>',
		'hand'   => '<path d="M7 11V6a2 2 0 0 1 4 0v4"/><path d="M11 10V4a2 2 0 0 1 4 0v6"/><path d="M15 10V6a2 2 0 0 1 4 0v8a7 7 0 0 1-7 7h-1a7 7 0 0 1-6-3.4L3 14.6a2 2 0 0 1 3.4-2.1L7 13.5"/>',
		'key'    => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.8-9.8M17 6l3 3M14.5 8.5l2 2"/>',
		'tag'    => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
		'cal'    => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/><path d="m9 15 2 2 4-4"/>',
		'wallet' => '<path d="M3 7a2 2 0 0 1 2-2h13v4"/><path d="M3 7v11a2 2 0 0 0 2 2h15V9H5a2 2 0 0 1-2-2Z"/><circle cx="16" cy="14.5" r="1.2"/>',
		'shield' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
		'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'map'    => '<path d="m9 4-6 2v14l6-2 6 2 6-2V4l-6 2-6-2Z"/><path d="M9 4v14M15 6v14"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $p[ $k ] ?? '' ) . '</svg>';
}

/* ---------- shared styles ---------- */
function ogi_css() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return '<style id="ogi-css">
.ogi{font-family:Cairo,Tahoma,sans-serif;color:#15171C}
.ogi *{box-sizing:border-box}
.ogi-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:46px;padding:0 20px;border-radius:12px;background:#15171C;color:#fff!important;font-size:15px;font-weight:700;text-decoration:none!important;transition:filter .15s,transform .15s}
.ogi-btn:hover{filter:brightness(1.15)}
.ogi-btn svg{width:17px;height:17px}
.ogi-btn.is-ghost{background:transparent;color:#15171C!important;border:1px solid #D9D3C7}
.ogi-btn.is-ghost:hover{background:#F6EEDF;filter:none}
.ogi-faq{max-width:860px;margin:0 auto;display:flex;flex-direction:column;gap:12px}
.ogi-faq details{background:#fff;border:1px solid #ECE7DE;border-radius:16px;transition:box-shadow .2s}
.ogi-faq details[open]{box-shadow:0 10px 30px -14px rgba(21,23,28,.18)}
.ogi-faq summary{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 24px;cursor:pointer;list-style:none;font-size:17px;font-weight:700;color:#15171C}
.ogi-faq summary::-webkit-details-marker{display:none}
.ogi-faq summary::after{content:"";width:10px;height:10px;flex:none;border-right:2px solid #9A7434;border-bottom:2px solid #9A7434;transform:rotate(45deg) translateY(-3px);transition:transform .2s}
.ogi-faq details[open] summary::after{transform:rotate(-135deg) translateY(-3px)}
.ogi-faq .ogi-a{padding:0 24px 22px;font-size:15px;line-height:1.75;color:#4B5563}
.ogi-faq .ogi-a p{margin:0 0 8px}
.ogi-faq .ogi-a ul{margin:4px 0 0;padding-inline-start:20px}
.ogi-faq .ogi-a a{color:#9A7434;font-weight:700}
.ogi-fgroup{margin:28px 0 4px;font-size:13px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#9A7434}
.ogi-fgroup:first-child{margin-top:0}
.ogi-more{max-width:860px;margin:36px auto 0;display:flex;align-items:center;justify-content:space-between;gap:20px;padding:26px 28px;border-radius:20px;background:#F6EEDF}
.ogi-more b{display:block;font-size:18px;margin-bottom:4px}
.ogi-more p{margin:0;color:#6B7280;font-size:15px}
html[dir=rtl] .ogi-btn svg{transform:scaleX(-1)}
@media (max-width:767px){.ogi-more{flex-direction:column;align-items:flex-start}.ogi-faq summary{padding:18px 18px;font-size:16px}.ogi-faq .ogi-a{padding:0 18px 18px}.ogi-steps{margin-top:40px}}
</style>';
}

/* ---------- FAQ ---------- */
function ogi_faq_items() {
	$r     = ogi_rules();
	$hours = (int) ( $r['cancel_hours'] ?? 48 );
	$bring = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ( $r['bring'] ?? '' ) ) ) );
	$locs  = ogi_locations();
	$disc  = array_filter( (array) ( $r['discounts'] ?? array() ), function ( $d ) {
		return ! empty( $d['days'] ) && ! empty( $d['pct'] );
	} );
	usort( $disc, function ( $a, $b ) {
		return (int) $a['days'] - (int) $b['days'];
	} );
	$cur = array();
	foreach ( (array) json_decode( (string) get_option( 'orego_currency_list' ), true ) as $c ) {
		if ( ! empty( $c['on'] ) && ! empty( $c['code'] ) ) {
			$cur[] = $c['code'];
		}
	}
	$ul = function ( $list ) {
		return '<ul>' . implode( '', array_map( function ( $x ) {
			return '<li>' . esc_html( $x ) . '</li>';
		}, $list ) ) . '</ul>';
	};
	$contact = orego_purl( 'contact' );
	$g       = array();

	$g['Booking & payment'] = array(
		array( 'How do I book a car?', '<p>Choose a car on the <a href="' . esc_url( ogi_cars_url() ) . '">Cars page</a>, pick your dates and pick-up point, and send your booking. It takes about a minute.</p>' ),
		array( 'How do I pay?', '<p>You pay at pick-up. The price you see when you book is the price you pay, with no hidden fees.</p>' ),
		array( 'Can I cancel for free?', '<p>Yes. Cancel up to ' . $hours . ' hours before pick-up and you pay nothing.</p>' ),
		array( 'Can I change my booking?', '<p>Yes. <a href="' . esc_url( $contact ) . '">Contact us</a> by WhatsApp, phone or email with your new dates and we will update it.</p>' ),
	);
	if ( $cur ) {
		$g['Booking & payment'][] = array( 'Which currencies can I see prices in?', '<p>You can show prices in ' . esc_html( implode( ', ', $cur ) ) . '. Pick your currency at the top of the page.</p>' );
	}
	$g['Pick-up & return'] = array();
	if ( $bring ) {
		$g['Pick-up & return'][] = array( 'What do I need at pick-up?', '<p>Please bring:</p>' . $ul( $bring ) );
	}
	if ( $locs ) {
		$g['Pick-up & return'][] = array( 'Where can I pick up the car?', '<p>At these airports:</p>' . $ul( $locs ) . '<p>See them on the <a href="' . esc_url( orego_purl( 'map' ) ?: home_url( '/map/' ) ) . '">map</a>.</p>' );
	}
	$g['Pick-up & return'][] = array( 'What are your opening hours?', '<p>We are open every day, from 08:00 to 20:00.</p>' );
	$g['Pick-up & return'][] = array( 'Where do I add my flight number?', '<p>In the Notes box when you book. If your plans change, <a href="' . esc_url( $contact ) . '">message us</a>.</p>' );
	if ( $disc ) {
		$lines = array();
		foreach ( $disc as $d ) {
			$lines[] = 'Rentals of ' . (int) $d['days'] . ' days or more get ' . rtrim( rtrim( number_format( (float) $d['pct'], 2, '.', '' ), '0' ), '.' ) . '% off.';
		}
		$g['Prices & discounts'] = array(
			array( 'Is there a discount for longer trips?', '<p>' . esc_html( implode( ' ', $lines ) ) . ' It is applied automatically when you book.</p>' ),
		);
	}
	return array_filter( $g );
}

add_shortcode( 'orego_faq', function () {
	$groups = ogi_faq_items();
	$o      = ogi_css() . '<div class="ogi ogi-faq">';
	$ld     = array();
	foreach ( $groups as $title => $items ) {
		$o .= '<p class="ogi-fgroup">' . esc_html( $title ) . '</p>';
		foreach ( $items as $i => $qa ) {
			$o   .= '<details' . ( 'Booking & payment' === $title && 0 === $i ? ' open' : '' ) . '><summary>' . esc_html( $qa[0] ) . '</summary><div class="ogi-a">' . $qa[1] . '</div></details>';
			$ld[] = array( '@type' => 'Question', 'name' => $qa[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( str_replace( '</li>', '. </li>', $qa[1] ) ) ) ) ) );
		}
	}
	$o .= '</div>';
	$o .= '<div class="ogi ogi-more"><div><b>Still have a question?</b><p>A real person from our team will answer you.</p></div><a class="ogi-btn" href="' . esc_url( orego_purl( 'contact' ) ) . '">Contact us' . ogi_icon( 'arrow' ) . '</a></div>';
	if ( $ld ) {
		$o .= '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ld ) ) . '</script>';
	}
	return $o;
} );

/* ---------- Map of Morocco: [orego_map] ---------- */

/** Coordinates of Moroccan airports, by IATA code. */
function ogi_airport_coords() {
	return array(
		'AGA' => array( 30.3250, -9.4131 ),  // Agadir Al Massira
		'RBA' => array( 34.0515, -6.7515 ),  // Rabat-Salé
		'RAK' => array( 31.6069, -8.0363 ),  // Marrakech Menara
		'OUD' => array( 34.7872, -1.9240 ),  // Oujda Angads
		'CMN' => array( 33.3675, -7.5900 ),  // Casablanca Mohammed V
		'TNG' => array( 35.7269, -5.9169 ),  // Tangier Ibn Battouta
		'FEZ' => array( 33.9273, -4.9779 ),  // Fès-Saïss
		'NDR' => array( 34.9888, -3.0282 ),  // Nador El Aroui
		'ESU' => array( 31.3975, -9.6817 ),  // Essaouira Mogador
		'OZZ' => array( 30.9391, -6.9094 ),  // Ouarzazate
		'TTU' => array( 35.5943, -5.3200 ),  // Tétouan Sania Ramel
		'ERH' => array( 31.9475, -4.3983 ),  // Errachidia
		'AHU' => array( 35.1771, -3.8395 ),  // Al Hoceima
		'EUN' => array( 27.1517, -13.2192 ), // Laâyoune Hassan I
		'VIL' => array( 23.7183, -15.9320 ), // Dakhla
		'GLN' => array( 29.0267, -10.0503 ), // Guelmim
		'TTA' => array( 28.4482, -11.1613 ), // Tan Tan
		'BEM' => array( 32.4019, -6.3159 ),  // Beni Mellal
	);
}

/**
 * Map position for a pick-up location that is not a known Moroccan airport code:
 * looked up once by name on OpenStreetMap and remembered (failed lookups are retried after a day).
 */
function ogi_geo( $name ) {
	$name = trim( (string) $name );
	if ( '' === $name ) {
		return null;
	}
	$all = get_option( 'orego_loc_geo' );
	$all = is_array( $all ) ? $all : array();
	$key = md5( strtolower( $name ) );
	if ( isset( $all[ $key ] ) && ( ! empty( $all[ $key ]['lat'] ) || ( time() - (int) ( $all[ $key ]['t'] ?? 0 ) ) < DAY_IN_SECONDS ) ) {
		return ! empty( $all[ $key ]['lat'] ) ? $all[ $key ] : null;
	}
	$r   = wp_remote_get( 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode( $name ), array(
		'timeout' => 8,
		'headers' => array( 'User-Agent' => 'ExoticCars-website/1.0 (+' . home_url( '/' ) . ')', 'Accept-Language' => 'en' ),
	) );
	$j   = is_wp_error( $r ) ? null : json_decode( wp_remote_retrieve_body( $r ), true );
	$row = array( 'name' => $name, 't' => time() );
	if ( is_array( $j ) && ! empty( $j[0]['lat'] ) ) {
		$parts       = array_map( 'trim', explode( ',', (string) $j[0]['display_name'] ) );
		$row['lat']  = (float) $j[0]['lat'];
		$row['lng']  = (float) $j[0]['lon'];
		$row['place'] = end( $parts );
	}
	$all[ $key ] = $row;
	update_option( 'orego_loc_geo', $all, false );
	return ! empty( $row['lat'] ) ? $row : null;
}

/* Look new locations up as soon as they are saved, so the map is ready. */
add_action( 'update_option_orego_locations', function ( $old, $new ) {
	foreach ( (array) $new as $name ) {
		list( , $code, $clean ) = ogi_loc_parts( (string) $name );
		$xy = ogi_airport_coords();
		if ( ! $code || ! isset( $xy[ $code ] ) ) {
			ogi_geo( $clean ? $clean : $name );
		}
	}
}, 10, 2 );
add_shortcode( 'orego_map', function () {
	$xy   = ogi_airport_coords();
	$pins = array();
	foreach ( ogi_locations() as $name ) {
		list( $city, $code, $clean ) = ogi_loc_parts( $name );
		$place = 'Morocco';
		if ( $code && isset( $xy[ $code ] ) ) {
			$lat = $xy[ $code ][0];
			$lng = $xy[ $code ][1];
		} else {
			$g = ogi_geo( $clean ? $clean : $name );
			if ( ! $g ) {
				continue;
			}
			$lat   = $g['lat'];
			$lng   = $g['lng'];
			$place = (string) ( $g['place'] ?? '' );
			$short = trim( preg_replace( '/\s+/', ' ', preg_replace( '/\b(international|airport|aeropuerto|aéroport|aeroporto|flughafen)\b/iu', '', $clean ) ) );
			$city  = '' !== $short ? $short : $clean;
		}
		$pins[] = array(
			'city' => $city,
			'code' => $code,
			'name' => $clean,
			'lat'  => $lat,
			'lng'  => $lng,
			'cars' => ogi_cars_url( array( 'pickup' => $name ) ),
			'dir'  => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $clean . ( $place ? ', ' . $place : '' ) ),
		);
	}
	if ( ! $pins ) {
		return ogi_css() . '<p class="ogi ogi-empty">The map will show our pick-up points soon.</p>';
	}
	$list = '';
	foreach ( $pins as $i => $p ) {
		$list .= '<div class="ogm2-row"><button type="button" class="ogm2-item" data-i="' . $i . '" aria-label="' . esc_attr( $p['city'] . ', ' . $p['name'] ) . '"><span class="ogm2-dot">' . ( $i + 1 ) . '</span><span class="ogm2-txt"><b>' . esc_html( $p['city'] ) . '</b><small>' . esc_html( $p['name'] ) . '</small></span><span class="ogm2-code notranslate">' . esc_html( $p['code'] ) . '</span><svg class="ogm2-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></button>'
			. '<div class="ogm2-acts"><a class="ogm2-go2" href="' . esc_url( $p['cars'] ) . '">Find cars' . ogi_icon( 'arrow' ) . '</a><a class="ogm2-dir2" href="' . esc_url( $p['dir'] ) . '" target="_blank" rel="noopener">' . ogi_icon( 'map' ) . 'Directions</a></div></div>';
	}
	$css = '<style id="ogm2-css">
.ogm2{display:grid;grid-template-columns:340px minmax(0,1fr);gap:0;border:1px solid #ECE7DE;border-radius:24px;overflow:hidden;background:#fff;box-shadow:0 24px 60px -30px rgba(21,23,28,.35);font-family:Cairo,Tahoma,sans-serif}
.ogm2-side{display:flex;flex-direction:column;border-inline-end:1px solid #ECE7DE;background:#fff}
.ogm2-side h3{margin:0;padding:22px 22px 6px;font-size:13px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#9A7434}
.ogm2-list{display:flex;flex-direction:column;padding:8px 12px 12px;gap:4px;overflow:auto}
.ogm2-item{display:flex;align-items:center;gap:14px;width:100%;padding:14px 12px;border:1px solid transparent;border-radius:14px;background:none;cursor:pointer;text-align:start;color:#15171C;font:inherit;transition:background .15s,border-color .15s}
.ogm2-item:hover{background:#FAF7F2}
.ogm2-item.is-on{background:#F6EEDF;border-color:#E8D7B5}
.ogm2-dot{width:32px;height:32px;flex:none;display:grid;place-items:center;border-radius:50%;background:#15171C;color:#E8C98F;font-size:14px;font-weight:800}
.ogm2-txt{display:flex;flex-direction:column;min-width:0;flex:1}
.ogm2-txt b{font-size:17px;font-weight:700;line-height:1.3}
.ogm2-txt small{font-size:13px;color:#6B7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ogm2-code{font-size:12px;font-weight:800;letter-spacing:.08em;color:#9A7434}
.ogm2-code:empty{display:none!important}
.ogm2-note{margin-top:auto;padding:16px 22px 20px;border-top:1px solid #ECE7DE;font-size:14px;line-height:1.6;color:#6B7280}
.ogm2-note b{color:#15171C}
.ogm2-map{position:relative;min-height:600px;background:#E9EEF0;z-index:0}
.ogm2-map .leaflet-container{position:absolute;inset:0;font-family:inherit;background:#E9EEF0}
.ogm2-map .leaflet-tile-pane{filter:saturate(.7) brightness(1.03) contrast(.96)}
html.og-dark .ogm2-map .leaflet-tile-pane{filter:invert(1) hue-rotate(180deg) brightness(.92) contrast(.88) saturate(.6)}
.ogm2-pin{width:38px;height:48px;position:relative}
.ogm2-pin svg{width:38px;height:48px;display:block;filter:drop-shadow(0 6px 8px rgba(21,23,28,.35));transition:transform .2s}
.ogm2-pin span{position:absolute;top:8px;left:0;width:38px;text-align:center;font:800 14px/20px Cairo,Tahoma,sans-serif;color:#E8C98F}
.ogm2-pin.is-on svg{transform:scale(1.18) translateY(-3px)}
.ogm2 .leaflet-popup-content-wrapper{border-radius:16px;padding:0;box-shadow:0 18px 40px -16px rgba(21,23,28,.45)}
.ogm2 .leaflet-popup-content{margin:0;width:264px!important}
.ogm2 .leaflet-popup-content p{margin:0}
.ogm2-pop{padding:18px 18px 16px;font-family:Cairo,Tahoma,sans-serif;color:#15171C}
.ogm2-pop-h{display:flex;align-items:flex-start;gap:10px;padding-inline-end:22px}
.ogm2-pop-h b{flex:1;min-width:0;font-size:18px;font-weight:800;line-height:1.25}
.ogm2-pop-code{flex:none;margin-top:2px;padding:3px 8px;border-radius:7px;background:#F3F4F6;color:#15171C;font-size:11px;font-weight:800;letter-spacing:.08em}
.ogm2-pop small{display:block;margin:3px 0 0;font-size:13px;line-height:1.4;color:#6B7280}
.ogm2-pop .ogm2-pop-t{display:flex;align-items:center;gap:8px;margin:12px 0 14px!important;padding:9px 10px;border-radius:10px;background:#F6F7F8;font-size:13px;line-height:1.3;color:#374151}
.ogm2-pop-t svg{width:16px;height:16px;flex:none;color:#15171C}
.ogm2-pop-a{display:flex;flex-direction:column;gap:8px}
.ogm2-pop a.ogm2-go,.ogm2-pop a.ogm2-dir{display:flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 14px;border-radius:11px;font-size:14px;font-weight:700;line-height:1;white-space:nowrap;text-decoration:none!important;transition:filter .15s,background .15s}
.ogm2-pop a.ogm2-go{background:#15171C;color:#fff!important}
.ogm2-pop a.ogm2-go:hover{filter:brightness(1.2)}
.ogm2-pop a.ogm2-dir{margin:0;background:#fff;border:1px solid #E5E7EB;color:#15171C!important}
.ogm2-pop a.ogm2-dir:hover{background:#F3F4F6}
.ogm2-pop a svg{width:16px;height:16px;flex:none}
html[dir=rtl] .ogm2-pop a.ogm2-go svg{transform:scaleX(-1)}
html.og-dark .ogm2-pop-code{background:#232933;color:#E8EAEE}
html.og-dark .ogm2-pop .ogm2-pop-t{background:#1D222B;color:#C9CED6}
html.og-dark .ogm2-pop-t svg{color:#E8EAEE}
html.og-dark .ogm2-pop a.ogm2-dir{background:#161A21;border-color:#2A303A}
.ogm2 .leaflet-popup-close-button{top:8px!important;right:8px!important}
.ogm2{grid-template-columns:360px minmax(0,1fr);border:1px solid #E7E2D8;border-radius:22px;box-shadow:0 1px 2px rgba(21,23,28,.04),0 18px 40px -28px rgba(21,23,28,.28)}
.ogm2-side{border-inline-end:1px solid #EEEAE2}
.ogm2-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:22px 22px 12px}
.ogm2-side .ogm2-head h3{margin:0;padding:0}
.ogm2-count{font-size:12px;font-weight:700;color:#6B7280;background:#F4F2EE;border-radius:999px;padding:4px 10px;white-space:nowrap}
.ogm2-list{padding:2px 12px 12px;gap:4px}
.ogm2-row{border-radius:16px;transition:background .15s,box-shadow .15s}
.ogm2-row.is-on{background:#FBF6EC;box-shadow:inset 0 0 0 1px #EBDDBF}
.ogm2-item{padding:14px 12px;border:0!important;border-radius:16px;background:none!important}
.ogm2-row:not(.is-on) .ogm2-item:hover{background:#F8F6F2!important}
.ogm2-item:focus-visible{outline:2px solid #9A7434;outline-offset:-2px}
.ogm2-code{background:#F4F2EE;color:#8A6A2E;border-radius:8px;padding:4px 8px;font-size:11px}
.ogm2-row.is-on .ogm2-code{background:#fff}
.ogm2-chev{width:16px;height:16px;flex:none;color:#B8B2A7;transition:transform .2s,color .2s}
.ogm2-row.is-on .ogm2-chev{transform:rotate(90deg);color:#9A7434}
html[dir=rtl] .ogm2-chev{transform:scaleX(-1)}
.ogm2-acts{display:none;gap:8px;padding:0 12px 14px 58px}
.ogm2-row.is-on .ogm2-acts{display:flex}
.ogm2-go2{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;height:40px;border-radius:11px;background:#15171C;color:#fff!important;font-size:14px;font-weight:700;text-decoration:none!important;transition:filter .15s}
.ogm2-go2:hover{filter:brightness(1.2)}
.ogm2-go2 svg,.ogm2-dir2 svg{width:16px;height:16px}
.ogm2-dir2{display:inline-flex;align-items:center;gap:6px;height:40px;padding:0 14px;border-radius:11px;border:1px solid #E2DCCF;background:#fff;color:#15171C!important;font-size:14px;font-weight:700;text-decoration:none!important}
.ogm2-dir2:hover{background:#F6EEDF}
.ogm2-note{display:flex;gap:10px;align-items:flex-start;margin:auto 16px 16px;padding:14px 16px;border:0;border-radius:14px;background:#F8F6F2;font-size:13px;line-height:1.55}
.ogm2-note svg{width:18px;height:18px;flex:none;color:#9A7434;margin-top:1px}
.ogm2-map .leaflet-tile-pane{filter:saturate(.45) brightness(1.05) contrast(.94)}
.ogm2 .leaflet-top.leaflet-right .leaflet-control{margin:14px 14px 0 0}
.ogm2 .leaflet-bar{border:0!important;border-radius:12px!important;overflow:hidden;box-shadow:0 6px 18px -6px rgba(21,23,28,.3)!important}
.ogm2 .leaflet-bar a{width:38px!important;height:38px!important;line-height:38px!important;color:#15171C!important;border-bottom-color:#EFEDE8!important;font-size:18px!important}
.ogm2 .leaflet-bar a:hover{background:#F8F6F2!important}
.ogm2-reset{width:38px;height:38px;margin-top:8px!important;padding:0;border:0;border-radius:12px;background:#fff;color:#15171C;display:grid;place-items:center;cursor:pointer;box-shadow:0 6px 18px -6px rgba(21,23,28,.3)}
.ogm2-reset:hover{background:#F8F6F2}
.ogm2-reset svg{width:18px;height:18px}
.ogm2 .leaflet-control-attribution{font-size:10px;background:rgba(255,255,255,.8)!important;border-radius:8px 0 0 0;padding:1px 6px}
html.og-dark .ogm2{border-color:#2A303A}
html.og-dark .ogm2-side{border-color:#2A303A}
html.og-dark .ogm2-count,html.og-dark .ogm2-code{background:#232933;color:#D9B77A}
html.og-dark .ogm2-row.is-on{background:#2A2418;box-shadow:inset 0 0 0 1px #5C4A2A}
html.og-dark .ogm2-row:not(.is-on) .ogm2-item:hover{background:#1D222B!important}
html.og-dark .ogm2-note{background:#1D222B}
html.og-dark .ogm2-go2{background:#ECEEF1;color:#0E1116!important}
html.og-dark .ogm2-dir2{background:#161A21;border-color:#2A303A;color:#E8EAEE!important}
html.og-dark .ogm2 .leaflet-bar a,html.og-dark .ogm2-reset{background:#161A21!important;color:#E8EAEE!important;border-bottom-color:#2A303A!important}
html.og-dark .ogm2 .leaflet-control-attribution{background:rgba(22,26,33,.8)!important;color:#A3AAB6}
html.og-dark .ogm2,html.og-dark .ogm2-side{background:#161A21;border-color:#2A303A}
html.og-dark .ogm2-item{color:#E8EAEE}
html.og-dark .ogm2-item:hover{background:#1D222B}
html.og-dark .ogm2-item.is-on{background:#2A2418;border-color:#5C4A2A}
html.og-dark .ogm2-note{border-color:#2A303A;color:#A3AAB6}
html.og-dark .ogm2-note b{color:#E8EAEE}
html.og-dark .ogm2-map,html.og-dark .ogm2-map .leaflet-container{background:#1b1f26}
html.og-dark .ogm2 .leaflet-popup-content-wrapper,html.og-dark .ogm2 .leaflet-popup-tip{background:#161A21}
html.og-dark .ogm2-pop{color:#E8EAEE}
html.og-dark .ogm2-pop a.ogm2-go{background:#ECEEF1;color:#0E1116!important}
html.og-dark .ogm2-pin path{fill:#15171C!important;stroke:#E8C98F!important}
html.og-dark .ogm2-pin span{color:#E8C98F!important}
html.og-dark .ogm2-dot{background:#E8C98F!important;color:#15171C!important}
@media (max-width:900px){.ogm2-row{flex:0 0 auto;scroll-snap-align:start}.ogm2-acts,.ogm2-chev{display:none!important}.ogm2-head{padding:16px 16px 8px}.ogm2{grid-template-columns:minmax(0,1fr)}.ogm2-side,.ogm2-map{min-width:0}.ogm2-side{border-inline-end:0;border-bottom:1px solid #ECE7DE}.ogm2-list{flex-direction:row;overflow-x:auto;padding:4px 12px 14px;scroll-snap-type:x mandatory}.ogm2-item{flex:0 0 auto;width:auto;min-width:200px;scroll-snap-align:start;border-color:#ECE7DE}.ogm2-note{display:none}.ogm2-map{min-height:440px}}
/* phones: the map fills the screen between the header and the tab bar */
html.og-map-lock,html.og-map-lock body{overflow:hidden!important;overscroll-behavior:none}
/* desktop & tablet: the map box fills the screen under the header */
@media (min-width:768px){
body.og-map-full .ogm2{position:fixed;left:0;right:0;top:var(--ogm2-top,72px);bottom:0;z-index:5;border:0;border-radius:0;box-shadow:none;grid-template-rows:minmax(0,1fr)}
body.og-map-full .ogm2-map{min-height:0}
body.og-map-full .ogm2-side{min-height:0;border-top:1px solid #EEEAE2}
body.og-map-full .ogm2-list{min-height:0}
html.og-dark body.og-map-full .ogm2-side{border-top-color:#2A303A}
}
@media (min-width:768px) and (max-width:900px){
body.og-map-full .ogm2{grid-template-rows:auto minmax(0,1fr)}
}
@media (max-width:767px){
body.og-map-full .ogm2{position:fixed;left:0;right:0;top:var(--ogm2-top,64px);bottom:var(--ogm2-bot,0px);z-index:5;display:block;border:0;border-radius:0;box-shadow:none;background:#E9EEF0}
body.og-map-full .ogm2-map{position:absolute;inset:0;min-height:0}
body.og-map-full .ogm2-side{position:absolute;left:0;right:0;bottom:26px;z-index:700;background:transparent!important;border:0;pointer-events:none}
body.og-map-full .ogm2-side h3,body.og-map-full .ogm2-note{display:none}
body.og-map-full .ogm2-list{pointer-events:auto;padding:4px 14px 6px;gap:10px;scrollbar-width:none}
body.og-map-full .ogm2-list::-webkit-scrollbar{display:none}
body.og-map-full .ogm2-row,body.og-map-full .ogm2-row.is-on{background:none;box-shadow:none}
body.og-map-full .ogm2-item{min-width:236px;padding:12px 14px;background:#fff!important;border:1px solid rgba(21,23,28,.06)!important;box-shadow:0 12px 28px -12px rgba(21,23,28,.45)}
body.og-map-full .ogm2-head{display:none}
body.og-map-full .ogm2-side{bottom:14px}
body.og-map-full .ogm2-list{gap:8px;padding:4px 12px}
body.og-map-full .ogm2-item{min-width:0!important;gap:8px;padding:5px 14px 5px 5px!important;border-radius:999px!important;box-shadow:0 6px 16px -8px rgba(21,23,28,.45)!important}
body.og-map-full .ogm2-dot{width:28px;height:28px;font-size:13px}
body.og-map-full .ogm2-txt{flex:none}
body.og-map-full .ogm2-txt b{font-size:15px;line-height:1.2;white-space:nowrap}
body.og-map-full .ogm2-txt small{display:none}
body.og-map-full .ogm2-code{background:none!important;padding:0;font-size:11px;color:#9A7434}
body.og-map-full .ogm2-item.is-on .ogm2-dot{background:#9A7434;color:#fff}
body.og-map-full .ogm2-item.is-on{background:#F6EEDF!important;border-color:#E8D7B5!important}
body.og-map-full .ogm2 .leaflet-top{top:12px}
body.og-map-full .ogm2 .leaflet-left{left:4px}
html.og-dark body.og-map-full .ogm2-item{background:#161A21!important;border-color:#2A303A!important}
html.og-dark body.og-map-full .ogm2-item.is-on{background:#2A2418!important;border-color:#5C4A2A!important}
}
/* site colours: black, white and soft greys (like the header, Cars page and filters) */
.ogm2{border-color:#E5E7EB}
.ogm2-side{border-color:#EEF0F2}
.ogm2-side .ogm2-head h3{color:#15171C}
.ogm2-count{background:#F3F4F6;color:#4B5563}
.ogm2-dot{background:#15171C;color:#fff}
.ogm2-code{background:#F3F4F6;color:#15171C}
.ogm2-row.is-on{background:#F6F7F8;box-shadow:inset 0 0 0 1px #E5E7EB}
.ogm2-row.is-on .ogm2-code{background:#fff}
.ogm2-row.is-on .ogm2-chev{color:#15171C}
.ogm2-row:not(.is-on) .ogm2-item:hover{background:#F9FAFB!important}
.ogm2-item:focus-visible{outline-color:#15171C}
.ogm2-dir2{border-color:#E5E7EB}
.ogm2-dir2:hover{background:#F3F4F6}
.ogm2-note{background:#F6F7F8}
.ogm2-note svg{color:#15171C}
.ogm2-pin path{stroke:#fff!important}
.ogm2-pin span{color:#fff!important}
.ogm2-pop a.ogm2-dir{color:#15171C!important}
@media (max-width:767px){
body.og-map-full .ogm2-code{color:#6B7280!important}
body.og-map-full .ogm2-item.is-on{background:#15171C!important;border-color:#15171C!important}
body.og-map-full .ogm2-item.is-on b{color:#fff}
body.og-map-full .ogm2-item.is-on .ogm2-code{color:#D1D5DB!important}
body.og-map-full .ogm2-item.is-on .ogm2-dot{background:#fff;color:#15171C}
}
html.og-dark .ogm2{border-color:#2A303A}
html.og-dark .ogm2-side .ogm2-head h3{color:#E8EAEE}
html.og-dark .ogm2-count,html.og-dark .ogm2-code{background:#232933!important;color:#E8EAEE!important}
html.og-dark .ogm2-dot{background:#ECEEF1!important;color:#0E1116!important}
html.og-dark .ogm2-row.is-on{background:#1D222B;box-shadow:inset 0 0 0 1px #2A303A}
html.og-dark .ogm2-row.is-on .ogm2-chev{color:#E8EAEE}
html.og-dark .ogm2-note svg{color:#E8EAEE}
html.og-dark .ogm2-pin path{fill:#15171C!important;stroke:#fff!important}
html.og-dark .ogm2-pin span{color:#fff!important}
html.og-dark .ogm2-pop a.ogm2-dir{color:#E8EAEE!important}
@media (max-width:767px){
html.og-dark body.og-map-full .ogm2-item.is-on{background:#ECEEF1!important;border-color:#ECEEF1!important}
html.og-dark body.og-map-full .ogm2-item.is-on b{color:#0E1116}
html.og-dark body.og-map-full .ogm2-item.is-on .ogm2-code{background:none!important;color:#4B5563!important}
html.og-dark body.og-map-full .ogm2-item.is-on .ogm2-dot{background:#0E1116!important;color:#fff!important}
}
</style>';
	$data = wp_json_encode( $pins );
	$js   = '<script>(function(){var P=' . $data . ';var box=document.currentScript.previousElementSibling;'
		. 'var mq=window.matchMedia("(max-width:767px)");function layout(){document.body.classList.add("og-map-full");document.documentElement.classList.add("og-map-lock");'
		. 'if(window.pageYOffset)window.scrollTo(0,0);var h=document.querySelector("header.ogx2")||document.querySelector(".ogx2");var top=h?Math.max(0,Math.round(h.getBoundingClientRect().bottom)):0;'
		. 'var t=document.querySelector("nav.og-tabs");var bot=0;if(t&&getComputedStyle(t).display!=="none"){var tr=t.getBoundingClientRect();bot=Math.max(0,Math.round(window.innerHeight-tr.top));}'
		. 'document.body.classList.remove("og-tabs-away");box.style.setProperty("--ogm2-top",top+"px");box.style.setProperty("--ogm2-bot",bot+"px");}'
		. 'try{history.scrollRestoration="manual";}catch(e){}window.scrollTo(0,0);layout();window.addEventListener("load",layout);setTimeout(layout,600);setTimeout(layout,1500);'
		. 'function boot(){if(!window.L){return setTimeout(boot,80);}var el=box.querySelector(".ogm2-map > div");'
		. 'var map=L.map(el,{scrollWheelZoom:false,zoomControl:false,minZoom:2,maxZoom:15,attributionControl:true});'
		. 'map.attributionControl.setPrefix(\'<a href="https://leafletjs.com" target="_blank" rel="noopener">Leaflet</a>\');'
		. 'L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png",{maxZoom:19,attribution:\'&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors\'}).addTo(map);'
		. 'var pin=function(n){return L.divIcon({className:"",html:\'<div class="ogm2-pin"><svg viewBox="0 0 38 48"><path d="M19 47s17-15.6 17-28A17 17 0 0 0 2 19c0 12.4 17 28 17 28Z" fill="#15171C" stroke="#FFFFFF" stroke-width="2"/></svg><span>\'+n+\'</span></div>\',iconSize:[38,48],iconAnchor:[19,46],popupAnchor:[0,-40]});};'
		. 'var esc=function(s){var d=document.createElement("div");d.textContent=s;return d.innerHTML;};var M=[],B=[];'
		. 'P.forEach(function(p,i){var m=L.marker([p.lat,p.lng],{icon:pin(i+1),title:p.city,riseOnHover:true}).addTo(map);'
		. 'var IC={clock:\'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>\',arrow:\'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>\',map:\'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 4-6 2v14l6-2 6 2 6-2V4l-6 2-6-2Z"/><path d="M9 4v14M15 6v14"/></svg>\'};'
		. 'm.bindPopup(\'<div class="ogm2-pop"><div class="ogm2-pop-h"><b>\'+esc(p.city)+\'</b>\'+(p.code?\'<span class="ogm2-pop-code notranslate">\'+esc(p.code)+\'</span>\':\'\')+\'</div><small>\'+esc(p.name)+\'</small><p class="ogm2-pop-t">\'+IC.clock+\'<span>Open every day, 08:00 to 20:00</span></p><div class="ogm2-pop-a"><a class="ogm2-go" href="\'+p.cars+\'">Find cars here\'+IC.arrow+\'</a><a class="ogm2-dir" href="\'+p.dir+\'" target="_blank" rel="noopener">\'+IC.map+\'Directions</a></div></div>\',{maxWidth:280,minWidth:240,autoPanPaddingTopLeft:L.point(16,16),autoPanPaddingBottomRight:L.point(76,96)});'
		. 'm.on("popupopen",function(){sel(i,false);});M.push(m);B.push([p.lat,p.lng]);});'
		. 'var items=box.querySelectorAll(".ogm2-item");'
		. 'function sel(i,fly){items.forEach(function(b){var on=+b.dataset.i===i;b.classList.toggle("is-on",on);b.setAttribute("aria-pressed",on?"true":"false");var r=b.closest(".ogm2-row");if(r)r.classList.toggle("is-on",on);});if(mq.matches&&items[i]){var rw=items[i].closest(".ogm2-row")||items[i],lst=rw.parentElement,st=lst.scrollLeft,tg=st+rw.getBoundingClientRect().left-lst.getBoundingClientRect().left-14;lst.scrollTo({left:tg,behavior:"smooth"});setTimeout(function(){if(Math.abs(lst.scrollLeft-st)<2&&Math.abs(tg-st)>2)lst.scrollLeft=tg;},450);}M.forEach(function(m,j){var e=m.getElement();if(e){var d=e.querySelector(".ogm2-pin");if(d)d.classList.toggle("is-on",j===i);}});'
		. 'if(fly){map.flyTo(M[i].getLatLng(),9,{duration:.8});setTimeout(function(){M[i].openPopup();},820);}}'
		. 'items.forEach(function(b){b.addEventListener("click",function(){sel(+b.dataset.i,true);});});'
		. 'var all=L.latLngBounds(B).pad(B.length>1?.15:0);function fit(){var full=mq.matches;if(B.length===1){map.setView(B[0],8);}else{map.fitBounds(all,full?{paddingTopLeft:[24,56],paddingBottomRight:[24,80]}:{padding:[24,24]});}}fit();'
		. 'map.setMaxBounds(L.latLngBounds(all.getSouthWest(),all.getNorthEast()).pad(1.2));'
		. 'L.control.zoom({position:"topright"}).addTo(map);'
		. 'var Rs=L.Control.extend({options:{position:"topright"},onAdd:function(){var b=L.DomUtil.create("button","ogm2-reset");b.type="button";b.title="Show all airports";b.setAttribute("aria-label","Show all airports");b.innerHTML=\'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 9V5a1 1 0 0 1 1-1h4M20 9V5a1 1 0 0 0-1-1h-4M4 15v4a1 1 0 0 0 1 1h4M20 15v4a1 1 0 0 1-1 1h-4"/></svg>\';L.DomEvent.disableClickPropagation(b);L.DomEvent.on(b,"click",function(){map.closePopup();sel(-1,false);fit();});return b;}});new Rs().addTo(map);'
		. 'el.addEventListener("click",function(){map.scrollWheelZoom.enable();});'
		. 'setTimeout(function(){map.invalidateSize();fit();},300);var rt;window.addEventListener("resize",function(){clearTimeout(rt);rt=setTimeout(function(){layout();map.invalidateSize();fit();},150);});'
		. 'if(window.ResizeObserver){var w0=el.clientWidth,h0=el.clientHeight;new ResizeObserver(function(){if(Math.abs(el.clientWidth-w0)>40||Math.abs(el.clientHeight-h0)>40){w0=el.clientWidth;h0=el.clientHeight;map.invalidateSize();fit();}}).observe(el);}'
		. 'map.scrollWheelZoom.enable();}'
		. 'if(!document.getElementById("leaflet-css")){var l=document.createElement("link");l.id="leaflet-css";l.rel="stylesheet";l.href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css";document.head.appendChild(l);}'
		. 'if(!window.L&&!document.getElementById("leaflet-js")){var s=document.createElement("script");s.id="leaflet-js";s.src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js";document.head.appendChild(s);}'
		. 'boot();})();</script>';
	return $css . '<div class="ogm2"><aside class="ogm2-side"><div class="ogm2-head"><h3>Our airports</h3><span class="ogm2-count">' . count( $pins ) . ' ' . ( 1 === count( $pins ) ? 'location' : 'locations' ) . '</span></div><div class="ogm2-list">' . $list . '</div><p class="ogm2-note">' . ogi_icon( 'clock' ) . '<span><b>Open every day, 08:00 to 20:00.</b> Collect your car the moment you land.</span></p></aside><div class="ogm2-map"><div></div></div></div>' . $js;
} );
