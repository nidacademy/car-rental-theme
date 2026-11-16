<?php
/**
 * Exotic Cars header & footer as part of the site theme:
 * printed on every front-end page through the theme hooks (wp_body_open / wp_footer),
 * whether the page is built with Elementor or with the Kira templates.
 * Kira's own old header, footer and floating WhatsApp button are retired.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** True inside the Elementor editor's page preview. */
function orego_is_editor() {
	if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore
		return true;
	}
	return class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode();
}

function orego_chrome_enabled() {
	return ! is_admin() && ! wp_doing_ajax() && ! is_feed() && ! is_embed() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
}

add_action( 'wp_body_open', function () {
	if ( orego_chrome_enabled() && shortcode_exists( 'orego_header' ) ) {
		echo do_shortcode( '[orego_header]' ); // phpcs:ignore
	}
}, 5 );

add_action( 'wp_footer', function () {
	if ( orego_chrome_enabled() && shortcode_exists( 'orego_footer' ) ) {
		echo do_shortcode( '[orego_footer]' ); // phpcs:ignore
	}
}, 1 );

/* Kira templates: hide the theme's old header/footer so only the Exotic Cars ones show */
add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'orego-theme-chrome', false, array(), '1.0' );
	wp_enqueue_style( 'orego-theme-chrome' );
	wp_add_inline_style( 'orego-theme-chrome', 'body>.skip-link,body>.site-header,body>.site-footer,body>.wa-float{display:none!important}body{margin:0}' . ( orego_is_editor() ? '.ogx2{position:relative!important;top:auto!important}.ogx2,.ogx2-promo,.ogfb{pointer-events:none}' : '' ) );
}, 27 );
