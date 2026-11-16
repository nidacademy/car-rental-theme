<?php
/**
 * Exotic Cars theme.
 * - inc/      : the car rental system (cars, booking, dashboard, Customizer options, emails...).
 * - demo/     : the one-click demo importer (Appearance -> Import demo) and the demo content.
 * - assets/   : fonts, brand logos and base styles.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EXOTIC_CARS_VERSION', '1.2.1' );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 88, 'width' => 260, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
	$uri = get_template_directory_uri();
	wp_enqueue_style( 'exotic-cars-fonts', $uri . '/assets/fonts.css', array(), EXOTIC_CARS_VERSION );
	wp_enqueue_style( 'exotic-cars-main', $uri . '/assets/main.css', array( 'exotic-cars-fonts' ), EXOTIC_CARS_VERSION );
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 20 );

/* The car rental system. Skipped when the same code is already loaded elsewhere (a development copy). */
if ( ! function_exists( 'orego_mod' ) ) {
	foreach ( glob( __DIR__ . '/inc/*.php' ) as $exotic_cars_file ) {
		require_once $exotic_cars_file;
	}
	unset( $exotic_cars_file );
}

/* Updates from GitHub releases (Dashboard -> Updates). */
require_once __DIR__ . '/updater/github-updater.php';

/* One-click demo import. */
require_once __DIR__ . '/demo/importer.php';
