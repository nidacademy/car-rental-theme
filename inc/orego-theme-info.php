<?php
/**
 * Exotic Cars: rich theme description on Appearance -> Themes (the style.css header only allows plain text).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_theme_rich_description() {
	$items = array( 'Fleet, prices & long-rental discounts', 'Online booking with a live calendar', 'Payments, deposits & extra charges', 'Automatic emails to customers and to you', 'Maintenance log for every car', 'Dashboard with income & profit', '7 languages, including right-to-left Arabic', 'Prices in MAD, USD & EUR', 'Drag-and-drop header builder', '10 colour themes & 6 car card designs', 'Elementor widgets for cars and brands', 'Mobile-first and ready for search engines' );
	$h     = '<span class="ogtd"><span class="ogtd-lead">A complete car rental theme to take bookings online.</span>';
	foreach ( $items as $li ) {
		$h .= '<span class="ogtd-li">' . esc_html( $li ) . '</span>';
	}
	return $h . '</span>';
}

add_filter( 'wp_prepare_themes_for_js', function ( $themes ) {
	$slug = get_stylesheet();
	if ( isset( $themes[ $slug ] ) ) {
		$themes[ $slug ]['description'] = orego_theme_rich_description();
	}
	return $themes;
} );

add_action( 'admin_head-themes.php', function () {
	echo '<style>.ogtd{display:block}.ogtd-lead{display:block;font-size:15px;line-height:1.6;color:#1d2327;margin:0 0 12px}.ogtd-li{display:block;position:relative;padding-inline-start:24px;font-size:14px;line-height:2;color:#3c434a}.ogtd-li::before{content:"\2713";position:absolute;inset-inline-start:2px;color:#2271b1;font-weight:700}</style>';
} );