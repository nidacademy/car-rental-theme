<?php
/**
 * Appearance -> Import demo: installs the plugins the theme needs, then copies the demo website
 * (pages, cars, images, menus, Customizer and booking settings) into this site in one click.
 * Content lives in demo/content.json, images in demo/media/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Plugins the theme works with. */
function exotic_cars_plugins() {
	return array(
		'elementor'  => array( 'name' => 'Elementor', 'file' => 'elementor/elementor.php', 'why' => 'Builds the pages.' ),
		'gtranslate' => array( 'name' => 'GTranslate', 'file' => 'gtranslate/gtranslate.php', 'why' => 'Translates the site into 7 languages.' ),
	);
}

function exotic_cars_plugin_state( $p ) {
	if ( is_plugin_active( $p['file'] ) ) {
		return 'active';
	}
	return file_exists( WP_PLUGIN_DIR . '/' . $p['file'] ) ? 'installed' : 'missing';
}

add_action( 'admin_menu', function () {
	add_theme_page( 'Import demo', 'Import demo', 'manage_options', 'exotic-cars-demo', 'exotic_cars_demo_page' );
} );

/* After the theme is switched on: point to the importer until the demo is imported. */
add_action( 'admin_notices', function () {
	if ( get_option( 'exotic_cars_demo_imported' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && 'appearance_page_exotic-cars-demo' === $screen->id ) {
		return;
	}
	echo '<div class="notice notice-info"><p><strong>Exotic Cars:</strong> install the demo website in one click. <a class="button button-primary" style="margin-inline-start:8px" href="' . esc_url( admin_url( 'themes.php?page=exotic-cars-demo' ) ) . '">Import demo</a></p></div>';
} );

function exotic_cars_demo_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$done    = get_option( 'exotic_cars_demo_imported' );
	$plugins = exotic_cars_plugins();
	$ready   = true;
	echo '<div class="wrap"><h1>Import the Exotic Cars demo</h1>';
	if ( isset( $_GET['ec_msg'] ) ) { // phpcs:ignore
		$msg = sanitize_text_field( wp_unslash( $_GET['ec_msg'] ) ); // phpcs:ignore
		$ok  = empty( $_GET['ec_err'] ); // phpcs:ignore
		echo '<div class="notice ' . ( $ok ? 'notice-success' : 'notice-error' ) . '"><p>' . esc_html( $msg ) . '</p></div>';
	}
	echo '<p style="max-width:720px">This copies the demo website into your site: the pages, 9 example cars with photos, menus, colours, header and footer, languages, currencies and booking settings. Afterwards you can change everything: texts and photos in Elementor, design in Appearance &rarr; Customize, cars and prices in Your Cars.</p>';

	echo '<h2>1. Required plugins</h2><table class="widefat striped" style="max-width:720px"><tbody>';
	foreach ( $plugins as $slug => $p ) {
		$st = exotic_cars_plugin_state( $p );
		if ( 'active' !== $st ) {
			$ready = false;
		}
		echo '<tr><td style="width:160px"><strong>' . esc_html( $p['name'] ) . '</strong></td><td>' . esc_html( $p['why'] ) . '</td><td style="width:200px">';
		if ( 'active' === $st ) {
			echo '<span style="color:#008a20">&#10003; Active</span>';
		} else {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0">';
			wp_nonce_field( 'exotic_cars_plugin' );
			echo '<input type="hidden" name="action" value="exotic_cars_plugin"><input type="hidden" name="plugin" value="' . esc_attr( $slug ) . '">';
			echo '<button class="button">' . ( 'installed' === $st ? 'Activate' : 'Install &amp; activate' ) . '</button></form>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';

	echo '<h2>2. Import</h2>';
	if ( $done ) {
		echo '<p>The demo was imported on ' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $done ) ) . '. <a href="' . esc_url( home_url( '/' ) ) . '" target="_blank">View the site</a> &middot; <a href="' . esc_url( admin_url( 'customize.php' ) ) . '">Customize</a></p>';
	}
	echo '<p style="max-width:720px;color:#646970">Pages with the same address as a demo page (for example "privacy-policy") are replaced with the demo version. Your other pages, posts and media are not touched. Importing takes up to a minute.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="this.querySelector(\'button\').disabled=true;this.querySelector(\'button\').textContent=\'Importing… please wait\';">';
	wp_nonce_field( 'exotic_cars_import' );
	echo '<input type="hidden" name="action" value="exotic_cars_import">';
	echo '<button class="button button-primary button-hero"' . ( $ready ? '' : ' disabled' ) . '>' . ( $done ? 'Import the demo again' : 'Import the demo' ) . '</button>';
	if ( ! $ready ) {
		echo '<p style="color:#b32d2e">Install and activate the plugins above first.</p>';
	}
	echo '</form></div>';
}

/* Install / activate a required plugin from WordPress.org. */
add_action( 'admin_post_exotic_cars_plugin', function () {
	check_admin_referer( 'exotic_cars_plugin' );
	if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
		wp_die( 'You are not allowed to install plugins.' );
	}
	$plugins = exotic_cars_plugins();
	$slug    = sanitize_key( $_POST['plugin'] ?? '' );
	$back    = admin_url( 'themes.php?page=exotic-cars-demo' );
	if ( ! isset( $plugins[ $slug ] ) ) {
		wp_safe_redirect( $back );
		exit;
	}
	$p = $plugins[ $slug ];
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( ! file_exists( WP_PLUGIN_DIR . '/' . $p['file'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$api = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );
		if ( is_wp_error( $api ) ) {
			wp_safe_redirect( add_query_arg( array( 'ec_msg' => rawurlencode( $p['name'] . ': ' . $api->get_error_message() ), 'ec_err' => 1 ), $back ) );
			exit;
		}
		$up  = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$res = $up->install( $api->download_link );
		if ( ! $res || is_wp_error( $res ) ) {
			$err = is_wp_error( $res ) ? $res->get_error_message() : 'the download failed. Install it from Plugins -> Add New.';
			wp_safe_redirect( add_query_arg( array( 'ec_msg' => rawurlencode( $p['name'] . ': ' . $err ), 'ec_err' => 1 ), $back ) );
			exit;
		}
	}
	$act = activate_plugin( $p['file'] );
	// Stay on this screen: no plugin welcome / setup wizard redirect.
	delete_transient( 'elementor_activation_redirect' );
	delete_option( 'gtranslate_do_activation_redirect' );
	$msg = is_wp_error( $act ) ? $p['name'] . ': ' . $act->get_error_message() : $p['name'] . ' is active.';
	wp_safe_redirect( add_query_arg( array( 'ec_msg' => rawurlencode( $msg ), 'ec_err' => is_wp_error( $act ) ? 1 : 0 ), $back ) );
	exit;
} );

/* Run the import. */
add_action( 'admin_post_exotic_cars_import', function () {
	check_admin_referer( 'exotic_cars_import' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You are not allowed to import the demo.' );
	}
	$back = admin_url( 'themes.php?page=exotic-cars-demo' );
	$res  = exotic_cars_import_demo();
	if ( is_wp_error( $res ) ) {
		wp_safe_redirect( add_query_arg( array( 'ec_msg' => rawurlencode( 'The import stopped: ' . $res->get_error_message() ), 'ec_err' => 1 ), $back ) );
	} else {
		wp_safe_redirect( add_query_arg( array( 'ec_msg' => rawurlencode( 'Done! The demo website is installed: ' . $res ) ), $back ) );
	}
	exit;
} );

/**
 * The importer. Returns a short summary or WP_Error.
 */
function exotic_cars_import_demo() {
	$dir  = __DIR__;
	$json = file_get_contents( $dir . '/content.json' ); // phpcs:ignore
	$d    = $json ? json_decode( $json, true ) : null;
	if ( ! is_array( $d ) ) {
		return new WP_Error( 'data', 'demo/content.json is missing or damaged.' );
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 600 ); // phpcs:ignore
	}
	wp_raise_memory_limit( 'admin' );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/post.php';
	kses_remove_filters(); // keep the page markup exactly as designed

	$site     = untrailingslashit( home_url() );
	$site_esc = str_replace( '/', '\\/', $site );
	$up       = wp_upload_dir();
	if ( ! empty( $up['error'] ) ) {
		return new WP_Error( 'uploads', $up['error'] );
	}
	$media_map = array(); // old attachment ID => new
	$path_map  = array(); // old "2026/10/a.webp" => new relative path (when renamed)
	$page_map  = array();
	$car_map   = array();
	$term_map  = array(); // taxonomy => old term ID => new

	/* 1. Images */
	foreach ( (array) $d['media'] as $m ) {
		$src = $dir . '/media/' . $m['file'];
		if ( ! file_exists( $src ) ) {
			continue;
		}
		// Already imported before? Reuse it.
		$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_exotic_cars_demo', 'value' => (string) $m['old_id'] ) ) ) );
		if ( $found ) {
			$media_map[ (int) $m['old_id'] ] = (int) $found[0];
			$path_map[ $m['file'] ]          = get_post_meta( $found[0], '_wp_attached_file', true );
			continue;
		}
		$sub  = dirname( $m['file'] );
		$tdir = trailingslashit( $up['basedir'] ) . $sub;
		wp_mkdir_p( $tdir );
		$name = wp_unique_filename( $tdir, basename( $m['file'] ) );
		$dest = $tdir . '/' . $name;
		if ( ! copy( $src, $dest ) ) {
			return new WP_Error( 'copy', 'Could not copy ' . basename( $m['file'] ) . ' into the uploads folder.' );
		}
		$rel  = $sub . '/' . $name;
		$type = wp_check_filetype( $name );
		$id   = wp_insert_attachment( array(
			'post_title'     => $m['title'],
			'post_excerpt'   => (string) $m['caption'],
			'post_status'    => 'inherit',
			'post_mime_type' => $type['type'] ? $type['type'] : $m['mime'],
			'guid'           => trailingslashit( $up['baseurl'] ) . $rel,
		), $dest );
		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
		if ( '' !== (string) $m['alt'] ) {
			update_post_meta( $id, '_wp_attachment_image_alt', $m['alt'] );
		}
		update_post_meta( $id, '_exotic_cars_demo', (string) $m['old_id'] );
		$media_map[ (int) $m['old_id'] ] = (int) $id;
		$path_map[ $m['file'] ]          = $rel;
	}

	/* Text fix-ups: site address, renamed image files */
	$fix = function ( $s ) use ( $site, $site_esc, $path_map ) {
		if ( ! is_string( $s ) ) {
			return $s;
		}
		$s = str_replace( array( '{{SITE_ESC}}', '{{SITE}}' ), array( $site_esc, $site ), $s );
		foreach ( $path_map as $old => $new ) {
			if ( $old !== $new ) {
				$oi = pathinfo( $old );
				$ni = pathinfo( $new );
				foreach ( array( '/', '\\/' ) as $sl ) {
					$a = str_replace( '/', $sl, $oi['dirname'] . '/' . $oi['filename'] );
					$b = str_replace( '/', $sl, $ni['dirname'] . '/' . $ni['filename'] );
					$s = str_replace( $a . '.', $b . '.', $s );
					$s = str_replace( $a . '-', $b . '-', $s );
				}
			}
		}
		return $s;
	};
	$fix_deep = function ( $v ) use ( &$fix_deep, $fix ) {
		if ( is_array( $v ) ) {
			foreach ( $v as $k => $x ) {
				$v[ $k ] = $fix_deep( $x );
			}
			return $v;
		}
		return $fix( $v );
	};
	/* Image IDs inside Elementor settings: {"url":..., "id":N} */
	$remap_ids = function ( $v ) use ( &$remap_ids, &$media_map ) {
		if ( ! is_array( $v ) ) {
			return $v;
		}
		if ( isset( $v['id'], $v['url'] ) && is_numeric( $v['id'] ) && isset( $media_map[ (int) $v['id'] ] ) ) {
			$v['id'] = $media_map[ (int) $v['id'] ];
		}
		foreach ( $v as $k => $x ) {
			if ( is_array( $x ) ) {
				$v[ $k ] = $remap_ids( $x );
			}
		}
		return $v;
	};
	$mid = function ( $old ) use ( &$media_map ) {
		return isset( $media_map[ (int) $old ] ) ? $media_map[ (int) $old ] : 0;
	};

	/* 2. Car types and brands */
	foreach ( (array) $d['terms'] as $tax => $terms ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		foreach ( $terms as $t ) {
			$ex = get_term_by( 'slug', $t['slug'], $tax );
			$id = $ex ? (int) $ex->term_id : 0;
			if ( ! $id ) {
				$r  = wp_insert_term( $t['name'], $tax, array( 'slug' => $t['slug'], 'description' => (string) $t['description'] ) );
				$id = is_wp_error( $r ) ? 0 : (int) $r['term_id'];
			}
			if ( $id ) {
				$term_map[ $tax ][ (int) $t['old_id'] ] = $id;
				foreach ( (array) $t['meta'] as $k => $v ) {
					update_term_meta( $id, $k, $v );
				}
			}
		}
	}

	/* 3. Pages (a page with the same address is replaced) */
	foreach ( (array) $d['pages'] as $p ) {
		$ex  = get_page_by_path( $p['slug'], OBJECT, 'page' );
		$arr = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $p['title'],
			'post_name'    => $p['slug'],
			'post_content' => $fix( $p['content'] ),
			'post_excerpt' => $fix( $p['excerpt'] ),
			'menu_order'   => (int) $p['menu_order'],
		);
		if ( $ex ) {
			$arr['ID'] = $ex->ID;
		}
		$id = wp_insert_post( wp_slash( $arr ), true );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		$page_map[ (int) $p['old_id'] ] = (int) $id;
		if ( $ex ) {
			foreach ( array_keys( get_post_meta( $id ) ) as $k ) {
				if ( 0 === strpos( $k, '_elementor' ) || '_wp_page_template' === $k ) {
					delete_post_meta( $id, $k );
				}
			}
		}
		foreach ( (array) $p['meta'] as $k => $v ) {
			if ( '_elementor_data' === $k ) {
				$data = json_decode( $fix( is_string( $v ) ? $v : wp_json_encode( $v ) ), true );
				if ( is_array( $data ) ) {
					update_post_meta( $id, $k, wp_slash( wp_json_encode( $remap_ids( $data ) ) ) );
				}
				continue;
			}
			if ( '_thumbnail_id' === $k ) {
				$v = $mid( $v );
			}
			update_post_meta( $id, $k, wp_slash( $remap_ids( $fix_deep( $v ) ) ) );
		}
	}

	/* 4. Cars */
	foreach ( (array) $d['cars'] as $c ) {
		$ex  = get_page_by_path( $c['slug'], OBJECT, 'orego_car' );
		$arr = array(
			'post_type'    => 'orego_car',
			'post_status'  => 'publish',
			'post_title'   => $c['title'],
			'post_name'    => $c['slug'],
			'post_content' => $fix( $c['content'] ),
			'post_excerpt' => $fix( $c['excerpt'] ),
			'menu_order'   => (int) $c['menu_order'],
			'post_date'    => $c['date'],
		);
		if ( $ex ) {
			$arr['ID'] = $ex->ID;
		}
		$id = wp_insert_post( wp_slash( $arr ), true );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		$car_map[ (int) $c['old_id'] ] = (int) $id;
		foreach ( (array) $c['meta'] as $k => $v ) {
			if ( '_thumbnail_id' === $k ) {
				$v = $mid( $v );
				if ( ! $v ) {
					continue;
				}
			} elseif ( 'og_gallery' === $k ) {
				$v = implode( ',', array_filter( array_map( $mid, array_filter( explode( ',', (string) $v ) ) ) ) );
			}
			update_post_meta( $id, $k, wp_slash( $fix_deep( $v ) ) );
		}
		foreach ( (array) $c['terms'] as $tax => $slugs ) {
			if ( taxonomy_exists( $tax ) ) {
				wp_set_object_terms( $id, (array) $slugs, $tax );
			}
		}
	}
	/* Images belong to their car, like on the demo site */
	foreach ( $car_map as $old => $new ) {
		$thumb = get_post_thumbnail_id( $new );
		if ( $thumb ) {
			wp_update_post( array( 'ID' => $thumb, 'post_parent' => $new ) );
		}
	}

	/* 5. Named pages and images used by the theme */
	$ids = array();
	foreach ( (array) $d['page_ids'] as $k => $old ) {
		if ( isset( $page_map[ (int) $old ] ) ) {
			$ids[ $k ] = $page_map[ (int) $old ];
		}
	}
	update_option( 'orego_page_ids', $ids );
	$mids = array();
	foreach ( (array) $d['media_ids'] as $k => $old ) {
		if ( $mid( $old ) ) {
			$mids[ $k ] = $mid( $old );
		}
	}
	update_option( 'orego_media_ids', $mids );

	/* 6. Menus */
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	foreach ( (array) $d['menus'] as $menu ) {
		$obj = wp_get_nav_menu_object( $menu['name'] );
		if ( $obj ) {
			foreach ( (array) wp_get_nav_menu_items( $obj->term_id, array( 'post_status' => 'any' ) ) as $old_item ) {
				wp_delete_post( $old_item->ID, true );
			}
			$menu_id = (int) $obj->term_id;
		} else {
			$menu_id = wp_create_nav_menu( $menu['name'] );
			if ( is_wp_error( $menu_id ) ) {
				continue;
			}
		}
		$item_map = array();
		$items    = (array) $menu['items'];
		usort( $items, function ( $a, $b ) {
			return $a['order'] <=> $b['order'];
		} );
		foreach ( $items as $it ) {
			$args = array(
				'menu-item-title'     => $it['title'],
				'menu-item-status'    => 'publish',
				'menu-item-position'  => (int) $it['order'],
				'menu-item-classes'   => implode( ' ', (array) $it['classes'] ),
				'menu-item-target'    => (string) $it['target'],
				'menu-item-parent-id' => isset( $item_map[ (int) $it['parent'] ] ) ? $item_map[ (int) $it['parent'] ] : 0,
			);
			if ( 'post_type' === $it['type'] && 'page' === $it['object'] && isset( $page_map[ (int) $it['object_id'] ] ) ) {
				$args += array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map[ (int) $it['object_id'] ] );
			} elseif ( 'taxonomy' === $it['type'] && isset( $term_map[ $it['object'] ][ (int) $it['object_id'] ] ) ) {
				$args += array( 'menu-item-type' => 'taxonomy', 'menu-item-object' => $it['object'], 'menu-item-object-id' => $term_map[ $it['object'] ][ (int) $it['object_id'] ] );
			} else {
				$args += array( 'menu-item-type' => 'custom', 'menu-item-url' => $fix( $it['url'] ) );
			}
			$new = wp_update_nav_menu_item( $menu_id, 0, $args );
			if ( ! is_wp_error( $new ) ) {
				$item_map[ (int) $it['old_id'] ] = (int) $new;
			}
		}
		foreach ( (array) $menu['locations'] as $loc ) {
			$locations[ $loc ] = $menu_id;
		}
	}
	set_theme_mod( 'nav_menu_locations', $locations );

	/* 7. Customizer settings */
	foreach ( (array) $d['theme_mods'] as $k => $v ) {
		if ( false !== strpos( $k, 'backup' ) ) {
			continue;
		}
		if ( is_numeric( $v ) && preg_match( '/logo|image|img|_bg|_car|icon|photo|picture/', $k ) && isset( $media_map[ (int) $v ] ) ) {
			$v = $media_map[ (int) $v ];
		} elseif ( 'og_home_page' === $k && isset( $page_map[ (int) $v ] ) ) {
			$v = $page_map[ (int) $v ];
		}
		set_theme_mod( $k, $fix_deep( $v ) );
	}
	if ( ! empty( $d['custom_css'] ) ) {
		wp_update_custom_css_post( $fix( $d['custom_css'] ) );
	}

	/* 8. Booking, languages, currencies... */
	foreach ( (array) $d['options'] as $k => $v ) {
		if ( null !== $v && false !== $v ) {
			update_option( $k, $fix_deep( $v ) );
		}
	}
	foreach ( (array) $d['option_page_refs'] as $k => $old ) {
		if ( isset( $page_map[ (int) $old ] ) ) {
			update_option( $k, $page_map[ (int) $old ] );
		}
	}

	/* 9. Site name, home page, permalinks */
	foreach ( (array) $d['site'] as $k => $v ) {
		update_option( $k, $v );
	}
	if ( ! empty( $d['front']['page_on_front'] ) && isset( $page_map[ (int) $d['front']['page_on_front'] ] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_map[ (int) $d['front']['page_on_front'] ] );
	}
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	/* 10. Elementor: global colours and fonts of the demo, fresh styles */
	if ( defined( 'ELEMENTOR_VERSION' ) && ! empty( $d['kit_settings'] ) ) {
		$kit = (int) get_option( 'elementor_active_kit' );
		if ( ! $kit && class_exists( '\Elementor\Plugin' ) ) {
			$kit = (int) \Elementor\Plugin::$instance->kits_manager->get_active_id();
		}
		if ( $kit ) {
			update_post_meta( $kit, '_elementor_page_settings', wp_slash( $remap_ids( $fix_deep( $d['kit_settings'] ) ) ) );
		}
	}
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	kses_init_filters();
	flush_rewrite_rules( false );
	update_option( 'exotic_cars_demo_imported', time() );
	do_action( 'litespeed_purge_all' );

	return sprintf( '%d pages, %d cars, %d images.', count( $page_map ), count( $car_map ), count( $media_map ) );
}
