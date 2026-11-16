<?php
/**
 * Pages built with an Elementor layout (Canvas / Full width) always use Elementor's template,
 * also on the homepage and inside the Elementor editor. Kira's front-page.php would otherwise
 * take over the homepage and the editor shows "content area was not found".
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'template_include', function ( $template ) {
	if ( ! defined( 'ELEMENTOR_PATH' ) || ! is_singular( 'page' ) ) {
		return $template;
	}
	$map = array(
		'elementor_canvas'        => 'canvas.php',
		'elementor_header_footer' => 'header-footer.php',
	);
	$tpl = get_post_meta( get_queried_object_id(), '_wp_page_template', true );
	if ( isset( $map[ $tpl ] ) ) {
		$file = ELEMENTOR_PATH . 'modules/page-templates/templates/' . $map[ $tpl ];
		if ( file_exists( $file ) ) {
			return $file;
		}
	}
	return $template;
}, 999 );
