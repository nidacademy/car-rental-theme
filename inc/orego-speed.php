<?php
/**
 * Speed and SEO touch-ups applied to the finished page:
 * - page styles that were printed at the bottom are moved into <head>, so the page does not jump while loading
 * - Google Fonts load without blocking the first paint
 * - every page gets a meta description and a site icon when it has none
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Styles printed in the footer that change the page layout. */
function orego_speed_style_ids() {
	return array( 'og-cars-desktop', 'og-brands-find', 'og-lm-css', 'og-mmenu', 'og-tabs-autohide', 'orego-fb-css', 'og-lang-photo' );
}

/* Short descriptions for search results, by page ID. */
function orego_speed_descriptions() {
	return array(
		orego_pid( 'cars' )  => 'Browse our rental cars: SUVs, sedans and luxury cars with clear daily prices. Pick up at the airport and drive away.',
		orego_pid( 'book' )  => 'Book your rental car in two steps: choose your dates and pick-up point, then confirm. No hidden fees.',
		orego_pid( 'about' ) => orego_brand() . ' rents modern, well-kept cars at the airport. No hidden fees, no long queues, no surprises at the counter.',
		orego_pid( 'contact' ) => 'Contact ' . orego_brand() . ' by WhatsApp, phone or email. We answer every day from 08:00 to 20:00.',
		orego_pid( 'faq' ) => 'Answers to common questions about renting a car with ' . orego_brand() . ': documents, deposit, fuel, cancellation and more.',
		orego_pid( 'map' ) => 'See every ' . orego_brand() . ' pick-up point on the map and find cars at your airport.',
	);
}

function orego_speed_description() {
	if ( is_singular( 'orego_car' ) ) {
		return sprintf( 'Rent the %s with %s. Clear daily price and airport pick-up.', wp_strip_all_tags( get_the_title() ), orego_brand() );
	}
	$map = orego_speed_descriptions();
	$id  = (int) get_queried_object_id();
	return isset( $map[ $id ] ) ? $map[ $id ] : '';
}

function orego_speed_filter( $html ) {
	$head_end = stripos( $html, '</head>' );
	if ( false === $head_end || false === stripos( $html, '<html' ) ) {
		return $html;
	}
	$head = substr( $html, 0, $head_end );
	$rest = substr( $html, $head_end );

	// 1. Move layout styles from the body into the head.
	$ids   = implode( '|', array_map( 'preg_quote', orego_speed_style_ids() ) );
	$moved = '';
	$rest  = preg_replace_callback(
		'#<style id="(?:' . $ids . ')"[^>]*>.*?</style>\s*#s',
		function ( $m ) use ( &$moved ) {
			$moved .= $m[0];
			return '';
		},
		$rest
	);

	// 2. Google Fonts: load without blocking the first paint.
	$head = preg_replace_callback(
		'#<link rel="stylesheet" href="(https://fonts\.googleapis\.com/[^"]+)"[^>]*>#',
		function ( $m ) {
			return '<link rel="preload" as="style" href="' . $m[1] . '" onload="this.onload=null;this.rel=\'stylesheet\'"><noscript><link rel="stylesheet" href="' . $m[1] . '"></noscript>';
		},
		$head
	);

	// 3. Meta description when the page has none.
	$extra = '';
	if ( false === stripos( $head, 'name="description"' ) ) {
		$d = orego_speed_description();
		if ( '' !== $d ) {
			$extra .= '<meta name="description" content="' . esc_attr( $d ) . '">' . "\n";
		}
	}

	// 4. Site icon when none is set (stops the favicon.ico 404).
	if ( ! preg_match( '#<link[^>]+rel="(?:shortcut )?icon"#i', $head ) ) {
		$svg    = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#15171C"/><path d="M14 38v-5l5-9c1-2 3-3 5-3h16c2 0 4 1 5 3l5 9v5c0 2-1 3-3 3h-1a5 5 0 0 1-10 0H26a5 5 0 0 1-10 0h-1c-1 0-1-1-1-3Zm9-13-3 7h24l-3-7c0-1-1-1-2-1H25c-1 0-2 0-2 1Z" fill="#fff"/></svg>';
		$extra .= '<link rel="icon" href="data:image/svg+xml,' . rawurlencode( $svg ) . '">' . "\n";
	}

	// 5. Map page: connect to the map tile server early.
	if ( false !== strpos( $rest, 'ogm2' ) ) {
		$extra .= '<link rel="preconnect" href="https://tile.openstreetmap.org" crossorigin>' . "\n";
	}

	// 6. Small grey text on car cards: a little darker so it is easy to read.
	$extra .= '<style id="og-speed-contrast">.og-card .og-meta,.og-card .og-price small,.ogtb-f small,.ogfb-copy{color:#6B7280}</style>' . "\n";

	return $head . $extra . $moved . $rest;
}

add_action( 'template_redirect', function () {
	if ( is_admin() || wp_doing_ajax() || is_feed() || is_customize_preview() || isset( $_GET['elementor-preview'] ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { // phpcs:ignore
		return;
	}
	if ( function_exists( 'orego_is_editor' ) && orego_is_editor() ) {
		return;
	}
	ob_start( 'orego_speed_filter' );
}, -9999 ); // early: some pages are printed by their own template_redirect handler
