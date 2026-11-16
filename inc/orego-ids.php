<?php
/**
 * Named pages and images, so the code never depends on post numbers.
 * - orego_pid( 'cars' ) -> the Cars page ID, orego_purl( 'cars' ) -> its address.
 * - orego_aid( 'logo' ) -> an image ID (logo, welcome car picture...).
 * The demo importer saves the numbers of the pages and images it creates in the options
 * "orego_page_ids" and "orego_media_ids". Without them, pages are found by their address (slug).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Page key => address (slug) used to find the page when no number is saved. */
function orego_page_slugs() {
	return array(
		'cars'       => 'cars',
		'book'       => 'book',
		'about'      => 'about-us',
		'contact'    => 'contact-us',
		'welcome'    => 'home',
		'faq'        => 'faq',
		'map'        => 'map',
		'my-booking' => 'my-booking',
		'terms'      => 'terms',
		'privacy'    => 'privacy-policy',
		'home'       => 'car-rental',
		'brands'     => 'brands',
	);
}

/** ID of a named page (0 when it does not exist). */
function orego_pid( $key ) {
	static $cache = array();
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$map = get_option( 'orego_page_ids' );
	$id  = is_array( $map ) && ! empty( $map[ $key ] ) ? (int) $map[ $key ] : 0;
	if ( $id && 'page' !== get_post_type( $id ) ) {
		$id = 0;
	}
	if ( ! $id ) {
		$slugs = orego_page_slugs();
		if ( isset( $slugs[ $key ] ) ) {
			$p  = get_page_by_path( $slugs[ $key ] );
			$id = $p ? (int) $p->ID : 0;
		}
	}
	$cache[ $key ] = $id;
	return $id;
}

/** Address of a named page (falls back to its usual address). */
function orego_purl( $key ) {
	$id = orego_pid( $key );
	if ( $id ) {
		return get_permalink( $id );
	}
	$slugs = orego_page_slugs();
	return home_url( '/' . ( $slugs[ $key ] ?? '' ) . ( isset( $slugs[ $key ] ) ? '/' : '' ) );
}

/** Is the current page this named page? (false when the page does not exist) */
function orego_is_page( $key ) {
	$id = orego_pid( $key );
	return $id && is_page( $id );
}

/** ID of a named image (0 when not set). */
function orego_aid( $key ) {
	$map = get_option( 'orego_media_ids' );
	$id  = is_array( $map ) && ! empty( $map[ $key ] ) ? (int) $map[ $key ] : 0;
	return $id && 'attachment' === get_post_type( $id ) ? $id : 0;
}

/* The Cars page gets its own body class, so its styles do not depend on the page number. */
add_filter( 'body_class', function ( $c ) {
	if ( orego_is_page( 'cars' ) ) {
		$c[] = 'og-cars-page';
	}
	return $c;
} );

/** The business name shown on the site and in emails (Settings -> General -> Site Title). */
function orego_brand() {
	$n = trim( wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ) );
	return '' !== $n ? $n : 'Car Rental';
}
