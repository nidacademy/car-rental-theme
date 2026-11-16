<?php
$GLOBALS['og_card_n'] = 0; // first four car photos load right away (they are what visitors see first)
/**
 * Exotic Cars Listing: the "السيارات" dashboard section (custom post type + type taxonomy + car details box)
 * and the shortcodes that show cars on the site:
 *   [orego_cars]          car cards grid (optional: limit="9")
 *   [orego_car_filters]   filter sidebar (types come from السيارات > الفئات)
 *   [orego_car_count]     "N سيارة متاحة" badge
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Data model                                                          */
/* ------------------------------------------------------------------ */

add_action( 'init', function () {
	register_post_type( 'orego_car', array(
		'labels'        => array(
			'name'                  => 'Your Cars',
			'singular_name'         => 'سيارة',
			'menu_name'             => 'Your Cars',
			'all_items'             => 'كل السيارات',
			'add_new'               => 'إضافة سيارة',
			'add_new_item'          => 'إضافة سيارة جديدة',
			'edit_item'             => 'تعديل السيارة',
			'new_item'              => 'سيارة جديدة',
			'view_item'             => 'عرض السيارة',
			'search_items'          => 'ابحث في السيارات',
			'not_found'             => 'لا توجد سيارات',
			'not_found_in_trash'    => 'لا توجد سيارات في السلة',
			'featured_image'        => 'صورة السيارة',
			'set_featured_image'    => 'تعيين صورة السيارة',
			'remove_featured_image' => 'إزالة صورة السيارة',
			'use_featured_image'    => 'استخدام كصورة للسيارة',
		),
		'public'        => true,
		'show_in_rest'  => false, // classic editor: simple form with the details box
		'menu_icon'     => 'dashicons-car',
		'menu_position' => 5,
		'supports'      => array( 'title', 'editor', 'thumbnail' ),
		'has_archive'   => false,
		'rewrite'       => array( 'slug' => 'car' ),
	) );

	register_taxonomy( 'car_type', 'orego_car', array(
		'labels'            => array(
			'name'          => 'الفئات',
			'singular_name' => 'الفئة',
			'menu_name'     => 'الفئات',
			'all_items'     => 'كل الفئات',
			'add_new_item'  => 'إضافة فئة جديدة',
			'edit_item'     => 'تعديل الفئة',
			'search_items'  => 'ابحث في الفئات',
		),
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'car-type' ),
	) );
} );

function orego_car_options() {
	return array(
		'trans' => array( 'auto' => 'Automatic', 'manual' => 'Manual' ),
		'fuel'  => array( 'gasoline' => 'Petrol', 'hybrid' => 'Hybrid', 'diesel' => 'Diesel', 'electric' => 'Electric' ),
	);
}

function orego_car_data( $id ) {
	$m = function ( $k, $d = '' ) use ( $id ) {
		$v = get_post_meta( $id, $k, true );
		return '' === $v ? $d : $v;
	};
	return array(
		'price'    => $m( 'og_price', '' ),
		'year'     => $m( 'og_year', '' ),
		'seats'    => $m( 'og_seats', '5' ),
		'trans'    => $m( 'og_trans', 'auto' ),
		'fuel'     => $m( 'og_fuel', 'gasoline' ),
		'rating'   => $m( 'og_rating', '' ),
		'location' => $m( 'og_location', 'Morocco' ),
	);
}

/* ------------------------------------------------------------------ */
/* Dashboard: car details box, list columns                            */
/* ------------------------------------------------------------------ */

add_action( 'add_meta_boxes_orego_car', function () {
	add_meta_box( 'orego_car_details', 'تفاصيل السيارة', 'orego_car_details_box', 'orego_car', 'normal', 'high' );
} );

function orego_car_details_box( $post ) {
	wp_nonce_field( 'orego_car_save', 'orego_car_nonce' );
	$d   = orego_car_data( $post->ID );
	$opt = orego_car_options();
	if ( '' === $d['year'] && 'auto-draft' === $post->post_status ) {
		$d['year'] = gmdate( 'Y' );
	}
	$sel = function ( $name, $choices, $cur ) {
		$h = '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">';
		foreach ( $choices as $k => $label ) {
			$h .= '<option value="' . esc_attr( $k ) . '"' . selected( $cur, $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		return $h . '</select>';
	};
	?>
	<style>
		.og-admin-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px 20px;padding:6px 0}
		.og-admin-grid label{display:block;font-weight:600;margin-bottom:6px}
		.og-admin-grid input,.og-admin-grid select{width:100%}
		.og-admin-help{color:#646970;margin:12px 0 0}
	</style>
	<div class="og-admin-grid">
		<p><label for="og_price">السعر اليومي ($) *</label><input type="number" step="0.01" min="0" name="og_price" id="og_price" value="<?php echo esc_attr( $d['price'] ); ?>" required></p>
		<p><label for="og_year">سنة الصنع</label><input type="number" min="1990" max="2100" name="og_year" id="og_year" value="<?php echo esc_attr( $d['year'] ); ?>"></p>
		<p><label for="og_seats">عدد المقاعد</label><input type="number" min="1" max="20" name="og_seats" id="og_seats" value="<?php echo esc_attr( $d['seats'] ); ?>"></p>
		<p><label for="og_trans">ناقل الحركة</label><?php echo $sel( 'og_trans', $opt['trans'], $d['trans'] ); // phpcs:ignore ?></p>
		<p><label for="og_fuel">الوقود</label><?php echo $sel( 'og_fuel', $opt['fuel'], $d['fuel'] ); // phpcs:ignore ?></p>
		<p><label for="og_rating">التقييم (0 إلى 5)</label><input type="number" step="0.1" min="0" max="5" name="og_rating" id="og_rating" value="<?php echo esc_attr( $d['rating'] ); ?>" placeholder="اتركه فارغًا إن لم يوجد"></p>
		<p><label for="og_location">الموقع</label><input type="text" name="og_location" id="og_location" value="<?php echo esc_attr( $d['location'] ); ?>"></p>
		<p><label for="og_units"><?php echo esc_html( orego_L( 'Number of cars of this model', 'عدد السيارات من هذا الموديل' ) ); ?></label><input type="number" min="1" max="99" step="1" name="og_units" id="og_units" value="<?php echo esc_attr( function_exists( 'orego_car_units' ) ? orego_car_units( $post->ID ) : 1 ); ?>" required>
		<?php if ( function_exists( 'orego_car_free_today' ) && 'auto-draft' !== $post->post_status ) : ?><small style="display:block;margin-top:6px;color:#646970"><?php echo esc_html( sprintf( orego_L( 'Free today: %1$d of %2$d', 'المتاح اليوم: %1$d من %2$d' ), orego_car_free_today( $post->ID ), orego_car_units( $post->ID ) ) ); ?></small><?php endif; ?></p>
	</div>
	<p class="og-admin-help"><?php echo esc_html( orego_L( 'Number of cars: each booking takes one car for its dates. When every car of this model is booked on a day, that day closes for booking and the car card shows “Reserved” on the website.', 'عدد السيارات: كل حجز يأخذ سيارة واحدة لأيامه. عندما تُحجز كل السيارات في يوم ما، يُغلق هذا اليوم في الحجز وتظهر كلمة «محجوزة» على بطاقة السيارة في الموقع.' ) ); ?></p>
	<p class="og-admin-help">اكتب اسم السيارة في العنوان (مثل: 2026 Toyota Corolla)، واختر الفئة من مربع «الفئات»، وأضف الصورة من «صورة السيارة». تظهر السيارة في الموقع بمجرد النشر.</p>
	<?php if ( function_exists( 'orego_car_specs_box' ) ) : ?>
	<style>.og-more-d{margin-top:18px;padding-top:16px;border-top:1px solid #F0F0F1}.og-more-h{margin:0 0 2px;font-size:14px;font-weight:700}</style>
	<div class="og-more-d"><h3 class="og-more-h"><?php echo esc_html( orego_L( 'More details', 'تفاصيل إضافية' ) ); ?></h3><?php orego_car_specs_box( $post ); ?></div>
	<?php endif; ?>
	<?php
}

add_action( 'save_post_orego_car', function ( $post_id ) {
	if ( ! isset( $_POST['orego_car_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['orego_car_nonce'] ) ), 'orego_car_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$opt = orego_car_options();
	$num = function ( $k, $min, $max, $dec = 2 ) {
		if ( ! isset( $_POST[ $k ] ) || '' === trim( (string) wp_unslash( $_POST[ $k ] ) ) ) {
			return '';
		}
		$v = (float) wp_unslash( $_POST[ $k ] );
		return (string) round( max( $min, min( $max, $v ) ), $dec );
	};
	$vals = array(
		'og_price'    => $num( 'og_price', 0, 100000, 2 ),
		'og_year'     => $num( 'og_year', 1990, 2100, 0 ),
		'og_seats'    => $num( 'og_seats', 1, 20, 0 ),
		'og_rating'   => $num( 'og_rating', 0, 5, 1 ),
		'og_trans'    => isset( $_POST['og_trans'], $opt['trans'][ $_POST['og_trans'] ] ) ? sanitize_key( $_POST['og_trans'] ) : 'auto',
		'og_fuel'     => isset( $_POST['og_fuel'], $opt['fuel'][ $_POST['og_fuel'] ] ) ? sanitize_key( $_POST['og_fuel'] ) : 'gasoline',
		'og_location' => isset( $_POST['og_location'] ) ? sanitize_text_field( wp_unslash( $_POST['og_location'] ) ) : '',
	);
	foreach ( $vals as $k => $v ) {
		if ( '' === $v ) {
			delete_post_meta( $post_id, $k );
		} else {
			update_post_meta( $post_id, $k, $v );
		}
	}
} );

add_filter( 'manage_orego_car_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		if ( 'title' === $k ) {
			$new['og_thumb'] = 'الصورة';
		}
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['og_price'] = 'السعر اليومي';
		}
	}
	return $new;
} );

add_action( 'manage_orego_car_posts_custom_column', function ( $col, $id ) {
	if ( 'og_thumb' === $col ) {
		echo has_post_thumbnail( $id ) ? get_the_post_thumbnail( $id, array( 60, 40 ), array( 'style' => 'width:60px;height:40px;object-fit:cover;border-radius:4px' ) ) : '–';
	}
	if ( 'og_price' === $col ) {
		$p = get_post_meta( $id, 'og_price', true );
		echo '' === $p ? '<span style="color:#b32d2e">غير محدد</span>' : esc_html( '$' . number_format( (float) $p, 2 ) );
	}
}, 10, 2 );

/* ------------------------------------------------------------------ */
/* Front end                                                           */
/* ------------------------------------------------------------------ */

function orego_svg( $name ) {
	$p = array(
		'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
		'gear'  => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
		'fuel'  => '<path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18M2 22h14M7 9h4M15 13h2a2 2 0 0 1 2 2v3a1.5 1.5 0 0 0 3 0V9l-3-3"/>',
		'pin'   => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'bolt'  => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
		'heart' => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7Z"/>',
		'arrow' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
		'car'   => '<path d="M5 16H3v-4l2-5h11l3 5h2v4h-2"/><circle cx="7.5" cy="16.5" r="2"/><circle cx="16.5" cy="16.5" r="2"/><path d="M9.5 16.5h5"/>',
		'filt'  => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
		'usd'   => '<path d="M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
	);
	return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $name ] . '</svg>';
}

function orego_count_label( $n ) {
	$n = (int) $n;
	if ( 0 === $n ) {
		return 'No cars available';
	}
	if ( 1 === $n ) {
		return '1 car available';
	}
	if ( 2 === $n ) {
		return '2 cars available';
	}
	return $n . ' cars available';
}

/** Editors also see drafts (marked), so cars can be checked before publishing. */
function orego_car_statuses() {
	return current_user_can( 'edit_posts' ) ? array( 'publish', 'draft', 'pending', 'future' ) : array( 'publish' );
}

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'orego-cars', false, array(), '1.0' );
	wp_enqueue_style( 'orego-cars' );
	wp_add_inline_style( 'orego-cars', orego_cars_css() );
	wp_register_script( 'orego-cars', '', array(), '1.0', true );
} );

add_shortcode( 'orego_car_count', function () {
	$q = new WP_Query( array( 'post_type' => 'orego_car', 'post_status' => orego_car_statuses(), 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
	return '<span class="og-avail-pill"><span class="og-dot"></span><span class="og-count-text">' . esc_html( orego_count_label( count( $q->posts ) ) ) . '</span></span>';
} );

add_shortcode( 'orego_cars', function ( $atts ) {
	$atts = shortcode_atts( array( 'limit' => -1, 'orderby' => 'date', 'types' => '', 'ids' => '', 'hide' => function_exists( 'orego_card_hide' ) ? orego_card_hide() : '', 'btn' => function_exists( 'orego_card_opt' ) ? orego_card_opt( 'btn' ) : 'View details', 'per' => function_exists( 'orego_card_opt' ) ? orego_card_opt( 'per' ) : '/ day', 'perks' => function_exists( 'orego_card_perks' ) ? orego_card_perks() : 'Free delivery|24/7 support|Optional insurance', 'ask' => 'Price on request' ), $atts );
	$hide = array_filter( array_map( 'trim', explode( ',', (string) $atts['hide'] ) ) );
	$show = fn( $part ) => ! in_array( $part, $hide, true );
	$args = array(
		'post_type'      => 'orego_car',
		'post_status'    => orego_car_statuses(),
		'posts_per_page' => (int) $atts['limit'] ? (int) $atts['limit'] : -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	);
	$ord  = array( 'price_asc' => array( 'meta_value_num', 'ASC', 'og_price' ), 'price_desc' => array( 'meta_value_num', 'DESC', 'og_price' ), 'rating' => array( 'meta_value_num', 'DESC', 'og_rating' ), 'title' => array( 'title', 'ASC', '' ), 'oldest' => array( 'date', 'ASC', '' ), 'random' => array( 'rand', 'ASC', '' ) );
	if ( isset( $ord[ $atts['orderby'] ] ) ) {
		list( $args['orderby'], $args['order'], $mk ) = $ord[ $atts['orderby'] ];
		if ( $mk ) {
			$args['meta_key'] = $mk; // phpcs:ignore
		}
	}
	$types = array_filter( array_map( 'sanitize_title', explode( ',', (string) $atts['types'] ) ) );
	if ( $types ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'car_type', 'field' => 'slug', 'terms' => $types ) ); // phpcs:ignore
	}
	$ids = array_filter( array_map( 'absint', explode( ',', (string) $atts['ids'] ) ) );
	if ( $ids ) {
		$args['post__in'] = $ids;
		$args['orderby']  = 'post__in';
	}
	$q    = new WP_Query( $args );
	$opt = orego_car_options();
	if ( ! $q->have_posts() ) {
		$msg = current_user_can( 'edit_posts' )
			? '<strong>No cars added yet</strong><span>Add your first car from Dashboard → Your Cars → Add Car.</span><a href="' . esc_url( admin_url( 'post-new.php?post_type=orego_car' ) ) . '">Add car</a>'
			: '<strong>No cars available right now</strong><span>Check back soon.</span>';
		return '<div class="og-cars og-cars-none"><div class="og-empty">' . $msg . '</div></div>';
	}
	wp_enqueue_script( 'orego-cars' );
	wp_add_inline_script( 'orego-cars', orego_cars_js() );
	$out   = '<div class="og-grid og-cars">' . ( function_exists( 'orego_trip_notice' ) ? orego_trip_notice() : '' );
	$shown = 0;
	while ( $q->have_posts() ) {
		$q->the_post();
		$id    = get_the_ID();
		// All cars of this model taken: marked "Reserved" (today); with searched dates the car is left out.
		$reserved = function_exists( 'orego_car_reserved_label' ) ? orego_car_reserved_label( $id ) : '';
		if ( $reserved && function_exists( 'orego_trip' ) && ! empty( orego_trip()['from'] ) ) {
			continue;
		}
		$og_bt     = get_the_terms( $id, 'car_brand' );
		$og_brands = ( $og_bt && ! is_wp_error( $og_bt ) ) ? implode( ' ', wp_list_pluck( $og_bt, 'slug' ) ) : '';
		$shown++;
		$d     = orego_car_data( $id );
		$terms = get_the_terms( $id, 'car_type' );
		$terms = ( $terms && ! is_wp_error( $terms ) ) ? $terms : array();
		$slugs = implode( ' ', wp_list_pluck( $terms, 'slug' ) );
		$price = '' === $d['price'] ? 0 : (float) $d['price'];
		$link  = function_exists( 'orego_trip_args' ) ? add_query_arg( orego_trip_args(), get_permalink( $id ) ) : get_permalink( $id );
		$name  = get_the_title();
		$meta  = array();
		if ( '' !== $d['rating'] ) {
			$meta[] = '<span class="og-star">★</span> <strong>' . esc_html( number_format( (float) $d['rating'], 1 ) ) . '</strong>';
		}
		if ( '' !== $d['year'] ) {
			$meta[] = esc_html( $d['year'] );
		}
		$out .= '<article class="og-card og-v' . ( 'publish' !== get_post_status() ? ' og-is-draft' : '' ) . ( $reserved ? ' og-is-reserved' : '' ) . '" data-reserved="' . ( $reserved ? '1' : '0' ) . '" data-types="' . esc_attr( $slugs ) . '" data-brands="' . esc_attr( $og_brands ) . '" data-trans="' . esc_attr( $d['trans'] ) . '" data-price="' . esc_attr( $price ) . '" data-rate="' . esc_attr( '' === $d['rating'] ? 0 : $d['rating'] ) . '" data-fuel="' . esc_attr( $d['fuel'] ) . '" data-seats="' . esc_attr( (int) $d['seats'] ) . '" data-loc="' . esc_attr( sanitize_title( $d['location'] ) ) . '">';
		$out .= '<a class="og-media" href="' . esc_url( $link ) . '" tabindex="-1" aria-hidden="true">' . ( has_post_thumbnail() ? get_the_post_thumbnail( $id, 'medium_large', ( ++$GLOBALS['og_card_n'] <= 4 ) ? array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'alt' => esc_attr( $name ) ) : array( 'loading' => 'lazy', 'alt' => esc_attr( $name ) ) ) : '<span class="og-noimg">' . orego_svg( 'car' ) . '</span>' ) . ( $reserved ? '<span class="og-reserved">' . esc_html( $reserved ) . '</span>' : '' ) . '</a>';
		if ( $terms && $show( 'tag' ) ) {
			$out .= '<span class="og-tag notranslate" translate="no">' . esc_html( $terms[0]->name ) . '</span>'; // stays in English: the Arabic names are too long for the badge
		}
		if ( 'publish' !== get_post_status() ) {
			$out .= '<span class="og-draft">Draft · only admins can see this</span>';
		}
		if ( $show( 'fav' ) ) $out .= '<button type="button" class="og-fav" data-id="' . esc_attr( $id ) . '" aria-pressed="false" aria-label="' . esc_attr( 'Add ' . $name . ' to favourites' ) . '">' . orego_svg( 'heart' ) . '</button>';
		$out .= '<div class="og-body">';
		$out .= '<h3 class="og-name"><a href="' . esc_url( $link ) . '">' . esc_html( $name ) . '</a></h3>';
		if ( $meta && $show( 'meta' ) ) {
			$out .= '<p class="og-meta">' . implode( ' <span class="og-sep">•</span> ', $meta ) . '</p>';
		}
		if ( $show( 'specs' ) ) $out .= '<ul class="og-specs"><li>' . orego_svg( 'users' ) . esc_html( $d['seats'] ) . '</li><li>' . orego_svg( 'gear' ) . esc_html( $opt['trans'][ $d['trans'] ] ?? '' ) . '</li><li>' . orego_svg( 'electric' === $d['fuel'] ? 'bolt' : 'fuel' ) . esc_html( $opt['fuel'][ $d['fuel'] ] ?? '' ) . '</li></ul>';
		if ( '' !== $d['location'] && $show( 'loc' ) ) {
			$out .= '<p class="og-loc">' . orego_svg( 'pin' ) . esc_html( $d['location'] ) . '</p>';
		}
		$pk = array_values( array_filter( array_map( 'trim', explode( '|', (string) $atts['perks'] ) ) ) );
		if ( $pk && $show( 'perks' ) ) {
			$out .= '<p class="og-perks">';
			foreach ( $pk as $pi => $pt ) {
				$out .= 0 === $pi ? '<span class="og-perk-first">' . orego_svg( 'bolt' ) . esc_html( $pt ) . '</span>' : '<span>' . esc_html( $pt ) . '</span>';
			}
			$out .= '</p>';
		}
		$out .= '<div class="og-foot"><div class="og-price">' . ( '' === $d['price'] ? '<small>' . esc_html( $atts['ask'] ) . '</small>' : '<span dir="ltr" class="og-money" data-usd="' . esc_attr( $price ) . '" data-dec="2">$' . esc_html( number_format( $price, 2 ) ) . '</span><small> ' . esc_html( $atts['per'] ) . '</small>' ) . '</div>' . ( '' !== trim( (string) $atts['btn'] ) ? '<a class="og-more" href="' . esc_url( $link ) . '">' . esc_html( $atts['btn'] ) . orego_svg( 'arrow' ) . '</a>' : '' ) . '</div>';
		$out .= '</div></article>';
	}
	wp_reset_postdata();
	if ( ! $shown ) {
		$out  = str_replace( 'class="og-grid og-cars"', 'class="og-grid og-cars og-cars-none"', $out );
		$out .= '<div class="og-empty"><strong>No cars available on these dates</strong><span>Try other dates or view all cars.</span><a href="' . esc_url( orego_purl( 'cars' ) ) . '">View all cars</a></div>';
	}
	return $out . '</div>';
} );


function orego_cars_css() {
	return '
.og-avail-pill{display:inline-flex;align-items:center;gap:8px;background:var(--hx-primary-soft);color:var(--hx-primary);border:1px solid var(--hx-primary-border);border-radius:999px;padding:6px 14px;font:600 14px/1.4 Cairo,Tahoma,sans-serif}
.og-avail-pill .og-dot{width:8px;height:8px;border-radius:50%;background:var(--hx-primary)}
.og-cars{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:26px;font-family:Cairo,Tahoma,sans-serif}
.og-cars.og-cars-none{display:block}
@media(max-width:1024px){.og-cars{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:767px){.og-cars{grid-template-columns:1fr;gap:18px}}
.og-cars .og-v{position:relative;display:flex;flex-direction:column;background:#FCFCFD;border-radius:20px;overflow:hidden;box-shadow:0 6px 24px rgba(21,23,28,.06);color:#15171C;transition:transform .2s ease,box-shadow .2s ease}
.og-cars .og-v:hover{transform:translateY(-3px);box-shadow:0 16px 36px rgba(21,23,28,.10)}
.og-cars .og-is-draft{outline:2px dashed #9AA1AD;outline-offset:-2px}
.og-cars .og-media{display:block;aspect-ratio:4/3;background:linear-gradient(135deg,#E6E8EC,#D9DCE2)}
.og-cars .og-media img{width:100%;height:100%;object-fit:cover;display:block}
.og-cars .og-noimg{display:flex;height:100%;align-items:center;justify-content:center;color:#9AA1AD}
.og-cars .og-noimg svg{width:48px;height:48px}

.og-cars .og-tag{position:absolute;top:14px;inset-inline-start:12px;background:rgba(255,255,255,.92);color:#15171C;font:700 12px/1.4 Cairo,Tahoma,sans-serif;padding:4px 11px;border-radius:999px}
.og-cars .og-draft{position:absolute;top:156px;inset-inline-start:12px;background:#15171C;color:#FFFFFF;font:600 11px/1.4 Cairo,Tahoma,sans-serif;padding:3px 10px;border-radius:999px}
.og-cars .og-fav{position:absolute;top:12px;inset-inline-end:12px;width:36px;height:36px;border-radius:50%;border:0;background:rgba(255,255,255,.92);color:#15171C;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;transition:transform .15s}
.og-cars .og-fav:hover{transform:scale(1.06)}
.og-cars .og-fav[aria-pressed=true]{color:var(--hx-primary)}
.og-cars .og-fav[aria-pressed=true] svg{fill:var(--hx-primary)}
.og-cars .og-body{display:flex;flex-direction:column;gap:9px;padding:18px 20px 20px;flex:1}
.og-cars .og-name{margin:0;font:700 18px/1.4 Cairo,Tahoma,sans-serif;color:#15171C}
.og-cars .og-name a{color:inherit;text-decoration:none}
.og-cars .og-name a:hover{color:var(--hx-primary)}
.og-cars .og-meta{margin:0;font-size:13px;line-height:1.4;color:#9AA1AD}
.og-cars .og-meta strong{color:#15171C;font-weight:700}
.og-cars .og-star{color:#F5B800}
.og-cars .og-sep{color:#C9CDD4;margin:0 4px}
.og-cars .og-specs{list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap;gap:6px 16px;font-size:13px;line-height:1.4;color:#6B7280}
.og-cars .og-specs li{display:flex;align-items:center;gap:5px;margin:0}
.og-cars .og-specs svg{width:14px;height:14px;color:#9AA1AD}
.og-cars .og-loc{margin:0;display:flex;align-items:center;gap:6px;font-size:13px;line-height:1.4;color:#6B7280}
.og-cars .og-loc svg{width:14px;height:14px;color:var(--hx-primary)}
.og-cars .og-perks{margin:0;display:flex;flex-wrap:wrap;font-size:12px;line-height:1.7;color:#9AA1AD}
.og-cars .og-perks span{display:inline-flex;align-items:center;gap:4px}
.og-cars .og-perks span+span::before{content:"·";margin:0 8px;color:#C9CDD4}
.og-cars .og-perks svg{width:12px;height:12px;color:#3DD68C}
.og-cars .og-foot{margin-top:auto;padding-top:14px;border-top:1px solid #E6E8EC;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.og-cars .og-price{font:800 22px/1.2 Cairo,Tahoma,sans-serif;color:#15171C}
.og-cars .og-price small{font-size:13px;font-weight:400;color:#9AA1AD}
.og-cars .og-more{display:inline-flex;align-items:center;gap:6px;background:var(--hx-primary-soft);color:var(--hx-primary);font:600 13px/1.3 Cairo,Tahoma,sans-serif;padding:9px 15px;border-radius:999px;text-decoration:none;transition:background .15s,color .15s}
.og-cars .og-more:hover{background:var(--hx-primary);color:#FFFFFF}
.og-cars .og-more svg{width:14px;height:14px}
html.translated-ltr .og-cars .og-more svg{transform:scaleX(-1)}
.og-empty{grid-column:1/-1;text-align:center;background:#FFFFFF;border:1px dashed #D9DCE2;border-radius:20px;padding:48px 20px;font-family:Cairo,Tahoma,sans-serif;display:flex;flex-direction:column;align-items:center;gap:6px}
.og-empty[hidden]{display:none}
.og-empty strong{font-size:18px;color:#15171C}
.og-empty span{font-size:15px;color:#6B7280}
.og-empty a,.og-empty button{margin-top:12px;font:600 14px Cairo,Tahoma,sans-serif;color:#FFFFFF;background:var(--hx-primary);border:0;border-radius:999px;padding:10px 22px;cursor:pointer;text-decoration:none}
@media (prefers-reduced-motion:reduce){.og-cars .og-v,.og-cars .og-fav{transition:none}.og-cars .og-v:hover{transform:none}}
';
}

function orego_cars_js() {
	return <<<'JS'
(function(){
function label(n){return n===0?"No cars available":n===1?"1 car available":n+" cars available";}
var X='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
function init(){
 var grid=document.querySelector(".og-cars:not(.og-cars-none)");if(!grid||grid.dataset.ready)return;grid.dataset.ready="1";
 var cards=[].slice.call(grid.querySelectorAll(".og-v"));cards.forEach(function(c,i){c.dataset.idx=i;});
 var favs=[];try{favs=JSON.parse(localStorage.getItem("og_favs")||"[]");}catch(e){}
 [].forEach.call(grid.querySelectorAll(".og-fav"),function(b){if(favs.indexOf(b.dataset.id)>-1)b.setAttribute("aria-pressed","true");});
 grid.addEventListener("click",function(e){var b=e.target.closest(".og-fav");if(!b)return;e.preventDefault();var on=b.getAttribute("aria-pressed")!=="true";b.setAttribute("aria-pressed",on?"true":"false");favs=favs.filter(function(x){return x!==b.dataset.id;});if(on)favs.push(b.dataset.id);try{localStorage.setItem("og_favs",JSON.stringify(favs));}catch(e){}});
 var side=document.querySelector(".og-side");
 var qq=((new URLSearchParams(location.search)).get("q")||"").trim().toLowerCase();
 var st={type:[],fuel:[],loc:[],brand:[],trans:"",seats:0,min:null,max:null};
 var empty=document.createElement("div");empty.className="og-empty";empty.hidden=true;empty.innerHTML="<strong>No cars match your choice</strong><span>Try removing a filter or widening the price range.</span><button type=\"button\">Clear filters</button>";grid.appendChild(empty);
 function rate(){return window.ogCur&&window.ogCur.rate?window.ogCur.rate:1;}
 function price(c){return (parseFloat(c.dataset.price)||0)*rate();}
 function ok(c,skip){
  var types=(c.dataset.types||"").split(" "),p=price(c);
  if(qq&&(c.textContent||"").toLowerCase().indexOf(qq)<0)return false;
  if(skip!=="type"&&st.type.length&&!st.type.some(function(t){return types.indexOf(t)>-1;}))return false;
  if(skip!=="fuel"&&st.fuel.length&&st.fuel.indexOf(c.dataset.fuel)<0)return false;
  if(skip!=="loc"&&st.loc.length&&st.loc.indexOf(c.dataset.loc)<0)return false;
  if(skip!=="brand"&&st.brand.length&&!st.brand.some(function(t){return (c.dataset.brands||"").split(" ").indexOf(t)>-1;}))return false;
  if(skip!=="trans"&&st.trans&&c.dataset.trans!==st.trans)return false;
  if(skip!=="seats"&&st.seats&&(parseInt(c.dataset.seats,10)||0)<st.seats)return false;
  if(skip!=="price"&&st.min!==null&&p<st.min)return false;
  if(skip!=="price"&&st.max!==null&&p>st.max)return false;
  return true;}
 function active(){return st.type.length+st.fuel.length+st.loc.length+st.brand.length+(st.trans?1:0)+(st.seats?1:0)+((st.min!==null||st.max!==null)?1:0);}
 function q(s){return side?[].slice.call(side.querySelectorAll(s)):[];}
 function money(v){return window.ogMoney?window.ogMoney(v/rate()):"$"+Math.round(v);}
 var rmin=side&&side.querySelector(".og-rmin"),rmax=side&&side.querySelector(".og-rmax"),mn=side&&side.querySelector(".og-min"),mx=side&&side.querySelector(".og-max"),fill=side&&side.querySelector(".og-range-fill");
 function bounds(){var ps=cards.map(price);var lo=ps.length?Math.floor(Math.min.apply(null,ps)):0,hi=ps.length?Math.ceil(Math.max.apply(null,ps)):100;if(hi<=lo)hi=lo+1;
  [rmin,rmax].forEach(function(r){if(r){r.min=lo;r.max=hi;}});if(rmin)rmin.value=st.min!==null?st.min:lo;if(rmax)rmax.value=st.max!==null?st.max:hi;if(mn)mn.placeholder=lo;if(mx)mx.placeholder=hi;paint();}
 function paint(){if(!rmin||!rmax||!fill)return;var lo=+rmin.min,hi=+rmin.max,a=+rmin.value,b=+rmax.value,w=hi-lo||1;var s=(a-lo)/w*100,e=(b-lo)/w*100;var rtl=document.documentElement.dir==="rtl";fill.style.left=(rtl?100-e:s)+"%";fill.style.right=(rtl?s:100-e)+"%";}
 function pills(){var box=side&&side.querySelector(".og-active");if(!box)return;var h=[];
  function add(g,v,l){h.push('<button type="button" class="og-pill" data-g="'+g+'" data-v="'+v+'">'+l+X+'</button>');}
  ["type","fuel","loc","brand"].forEach(function(g){st[g].forEach(function(v){var c=side.querySelector('.og-chip[data-'+g+'="'+v+'"] span');add(g,v,c?c.textContent:v);});});
  if(st.trans)add("trans",st.trans,st.trans==="auto"?"Automatic":"Manual");
  if(st.seats)add("seats",st.seats,st.seats+"+ seats");
  if(st.min!==null||st.max!==null)add("price","",(st.min!==null?money(st.min):"")+" – "+(st.max!==null?money(st.max):""));
  box.innerHTML=h.join("");box.hidden=!h.length;}
 function apply(){var n=0;cards.forEach(function(c){var y=ok(c);c.style.display=y?"":"none";if(y)n++;});
  [].forEach.call(document.querySelectorAll(".og-count-text"),function(e){e.textContent=label(n);});empty.hidden=n>0;
  var a=active();
  if(side){side.classList.toggle("is-active",!!a);
   q(".og-chip[data-type],.og-chip[data-fuel],.og-chip[data-loc],.og-chip[data-brand]").forEach(function(ch){var g=ch.dataset.brand!==undefined?"brand":ch.dataset.type!==undefined?"type":ch.dataset.fuel!==undefined?"fuel":"loc",v=ch.dataset[g],k=0;cards.forEach(function(c){if(!ok(c,g))return;if((g==="type"||g==="brand")?(c.dataset[g==="type"?"types":"brands"]||"").split(" ").indexOf(v)>-1:c.dataset[g]===v)k++;});var ni=ch.querySelector(".og-n");if(ni)ni.textContent=k;ch.classList.toggle("is-zero",!k&&ch.getAttribute("aria-pressed")!=="true");});
   q(".og-fg").forEach(function(g){var key=g.dataset.g,c=0;if(key==="type"||key==="fuel"||key==="loc"||key==="brand")c=st[key].length;else if(key==="trans")c=st.trans?1:0;else if(key==="seats")c=st.seats?1:0;else if(key==="price")c=(st.min!==null||st.max!==null)?1:0;var s=g.querySelector(".og-fg-sel");if(s)s.textContent=c?c:"";});
   q(".og-reset").forEach(function(r){if(!r.classList.contains("og-reset-b"))r.hidden=!a;});
   q(".og-show").forEach(function(e){e.innerHTML=n?"Show <b class=\"og-show-n\">"+n+"</b> "+(n===1?"car":"cars"):"No cars match";});pills();}
  var bd=document.querySelector(".og-fbadge");if(bd){bd.textContent=a;bd.hidden=!a;}}
 function reset(){st={type:[],fuel:[],loc:[],brand:[],trans:"",seats:0,min:null,max:null};q("[aria-pressed]").forEach(function(x){x.setAttribute("aria-pressed",x.classList.contains("og-segb")&&!x.dataset.trans?"true":"false");});if(mn)mn.value="";if(mx)mx.value="";bounds();apply();}
 empty.querySelector("button").addEventListener("click",reset);
 if(side){
  side.addEventListener("click",function(e){
   var p=e.target.closest(".og-pill");if(p){var g=p.dataset.g,v=p.dataset.v;
    if(g==="type"||g==="fuel"||g==="loc"||g==="brand"){st[g]=st[g].filter(function(x){return x!==v;});var c=side.querySelector('.og-chip[data-'+g+'="'+v+'"]');if(c)c.setAttribute("aria-pressed","false");}
    else if(g==="trans"){st.trans="";q(".og-segb").forEach(function(b){b.setAttribute("aria-pressed",b.dataset.trans?"false":"true");});}
    else if(g==="seats"){st.seats=0;q(".og-chip[data-seats]").forEach(function(b){b.setAttribute("aria-pressed","false");});}
    else if(g==="price"){st.min=st.max=null;if(mn)mn.value="";if(mx)mx.value="";bounds();}
    apply();return;}
   if(e.target.closest(".og-reset")){reset();return;}
   var sb=e.target.closest(".og-segb");if(sb){q(".og-segb").forEach(function(b){b.setAttribute("aria-pressed",b===sb?"true":"false");});st.trans=sb.dataset.trans||"";apply();return;}
   var b=e.target.closest(".og-chip");if(!b)return;var on=b.getAttribute("aria-pressed")!=="true";
   if(b.dataset.seats!==undefined){q(".og-chip[data-seats]").forEach(function(x){x.setAttribute("aria-pressed","false");});b.setAttribute("aria-pressed",on?"true":"false");st.seats=on?parseInt(b.dataset.seats,10):0;}
   else{var g=b.dataset.brand!==undefined?"brand":b.dataset.type!==undefined?"type":b.dataset.fuel!==undefined?"fuel":"loc",v=b.dataset[g];b.setAttribute("aria-pressed",on?"true":"false");st[g]=st[g].filter(function(x){return x!==v;});if(on)st[g].push(v);}
   apply();});
  function fromRange(){var a=+rmin.value,b=+rmax.value;if(a>b){if(this===rmin){a=b;rmin.value=a;}else{b=a;rmax.value=b;}}st.min=a>+rmin.min?a:null;st.max=b<+rmax.max?b:null;if(mn)mn.value=st.min!==null?a:"";if(mx)mx.value=st.max!==null?b:"";paint();apply();}
  if(rmin){rmin.addEventListener("input",fromRange);rmax.addEventListener("input",fromRange);}
  function num(el){var x=parseFloat(el.value);return isNaN(x)?null:x;}
  if(mn)mn.addEventListener("input",function(){st.min=num(mn);if(rmin)rmin.value=st.min!==null?st.min:rmin.min;paint();apply();});
  if(mx)mx.addEventListener("input",function(){st.max=num(mx);if(rmax)rmax.value=st.max!==null?st.max:rmax.max;paint();apply();});
  /* phones and tablets: bottom sheet */
  var btn=document.querySelector(".og-fbtn"),scrim=document.querySelector(".og-scrim"),last=null;
  function open(){last=document.activeElement;side.classList.add("is-open");document.body.classList.add("og-fopen");if(scrim){scrim.hidden=false;requestAnimationFrame(function(){scrim.classList.add("is-on");});}if(btn)btn.setAttribute("aria-expanded","true");var x=side.querySelector(".og-side-x");if(x)setTimeout(function(){x.focus();},50);}
  function close(){side.classList.remove("is-open");document.body.classList.remove("og-fopen");if(scrim){scrim.classList.remove("is-on");setTimeout(function(){scrim.hidden=true;},250);}if(btn)btn.setAttribute("aria-expanded","false");if(last&&last.focus)last.focus();}
  if(btn)btn.addEventListener("click",open);
  if(scrim)scrim.addEventListener("click",close);
  [".og-side-x",".og-show"].forEach(function(s){var el=side.querySelector(s);if(el)el.addEventListener("click",close);});
  document.addEventListener("keydown",function(e){if(e.key==="Escape"&&side.classList.contains("is-open"))close();});
  var mq=window.matchMedia("(max-width:1024px)");
  function groups(){q(".og-fg").forEach(function(g,i){if(mq.matches){if(!g.dataset.touched)g.open=i<2||g.querySelector('[aria-pressed=true]:not(.og-segb[data-trans=""])')!==null;}else if(!g.dataset.touched){g.open=true;}});if(!mq.matches)close();}
  q(".og-fg>summary").forEach(function(s){s.addEventListener("click",function(){s.parentNode.dataset.touched="1";});});
  if(mq.addEventListener)mq.addEventListener("change",groups);groups();
 }
 var sort=document.getElementById("og-sort");
 if(sort)sort.addEventListener("change",function(){var m=sort.value,arr=cards.slice();
  if(m==="price-asc")arr.sort(function(a,b){return a.dataset.price-b.dataset.price;});
  else if(m==="price-desc")arr.sort(function(a,b){return b.dataset.price-a.dataset.price;});
  else if(m==="rating")arr.sort(function(a,b){return b.dataset.rate-a.dataset.rate;});
  else arr.sort(function(a,b){return a.dataset.idx-b.dataset.idx;});
  arr.forEach(function(c,i){c.style.order=i;});});
 var qt=(new URLSearchParams(location.search)).get("type");
 if(qt&&side){var ch=side.querySelector('.og-chip[data-type="'+qt.replace(/[^a-z0-9_-]/gi,'')+'"]');if(ch){ch.setAttribute("aria-pressed","true");st.type=[ch.dataset.type];}}
 document.addEventListener("og:currency",function(){st.min=st.max=null;if(mn)mn.value="";if(mx)mx.value="";bounds();apply();});
 bounds();apply();
}
if(document.readyState!=="loading")init();else document.addEventListener("DOMContentLoaded",init);
window.addEventListener("elementor/frontend/init",function(){setTimeout(init,300);});
})();
JS;}

/* ================================================================== */
/* Car details page (template for every car in Your Cars)              */
/* ================================================================== */

function orego_feature_groups() {
	return apply_filters( 'orego_feature_groups', array(
		'safety' => array( 'Safety', 'shield', array( 'backup_camera' => 'Backup camera', 'blind_spot' => 'Blind spot warning', 'brake_assist' => 'Brake assist', 'lane_departure' => 'Lane departure warning', 'adaptive_cruise' => 'Adaptive cruise control', 'lane_keeping' => 'Lane keeping assist' ) ),
		'connect' => array( 'Connectivity', 'signal', array( 'bluetooth' => 'Bluetooth', 'usb_input' => 'USB input', 'carplay' => 'Apple CarPlay', 'android_auto' => 'Android Auto', 'aux' => 'AUX input' ) ),
		'more' => array( 'More features', 'car', array( 'gps' => 'GPS navigation', 'keyless' => 'Keyless entry', 'usb_charger' => 'USB charger', 'heated_seats' => 'Heated seats', 'sunroof' => 'Sunroof', 'third_row' => 'Third row seating' ) ),
	) );
}

function orego_car_images( $id ) {
	$ids = array();
	if ( has_post_thumbnail( $id ) ) {
		$ids[] = (int) get_post_thumbnail_id( $id );
	}
	foreach ( array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $id, 'og_gallery', true ) ) ) ) as $g ) {
		if ( ! in_array( $g, $ids, true ) && wp_attachment_is_image( $g ) ) {
			$ids[] = $g;
		}
	}
	return $ids;
}

function orego_car_bookings( $id ) {
	$out   = array();
	$today = gmdate( 'Y-m-d' );
	foreach ( preg_split( '/\r\n|\r|\n/', (string) get_post_meta( $id, 'og_booked', true ) ) as $line ) {
		if ( preg_match_all( '/(\d{4}-\d{2}-\d{2})/', $line, $m ) && $m[1] ) {
			$a = $m[1][0];
			$b = $m[1][1] ?? $a;
			if ( $b >= $today ) {
				$out[] = array( $a, $b );
			}
		}
	}
	if ( function_exists( 'orego_bk_ranges' ) ) {
		$out = array_merge( $out, orego_bk_ranges( $id, 0, array( 'confirmed', 'onroad' ) ) );
	}
	usort( $out, fn( $x, $y ) => strcmp( $x[0], $y[0] ) );
	return $out;
}

function orego_money( $v, $dec = true ) {
	$v = (float) $v;
	return '$' . ( $dec || floor( $v ) != $v ? number_format( $v, 2 ) : number_format( $v, 0 ) );
}

/* ---------- Dashboard: extra boxes ---------- */

add_action( 'add_meta_boxes_orego_car', function () {
	add_meta_box( 'orego_car_gallery', 'صور إضافية للسيارة', 'orego_car_gallery_box', 'orego_car', 'normal', 'high' );
	add_meta_box( 'orego_car_features', 'مميزات السيارة', 'orego_car_features_box', 'orego_car', 'normal', 'default' );
	add_meta_box( 'orego_car_trips', 'المسافة والرحلات المحجوزة', 'orego_car_trips_box', 'orego_car', 'normal', 'default' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$s = get_current_screen();
	if ( $s && 'orego_car' === $s->post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_enqueue_media();
	}
} );

function orego_car_gallery_box( $post ) {
	wp_nonce_field( 'orego_car_extra', 'orego_car_extra_nonce' );
	wp_enqueue_script( 'jquery-ui-sortable' );
	$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, 'og_gallery', true ) ) ) );
	echo '<p style="color:#646970;margin-top:0">الصورة الرئيسية تأتي من «صورة السيارة». أضف هنا باقي الصور لتظهر في معرض صفحة السيارة.</p>';
	echo '<input type="hidden" name="og_gallery" id="og_gallery" value="' . esc_attr( implode( ',', $ids ) ) . '">';
	echo '<ul id="og-gal-prev" class="og-gal-list">';
	foreach ( $ids as $i ) {
		$u = wp_get_attachment_image_url( $i, 'thumbnail' );
		if ( $u ) {
			echo '<li class="og-gal-it" data-id="' . (int) $i . '"><img src="' . esc_url( $u ) . '" alt=""><button type="button" class="og-gal-x" aria-label="Remove this photo" title="Remove this photo">&times;</button></li>';
		}
	}
	echo '</ul><p class="og-gal-help"' . ( count( $ids ) > 1 ? '' : ' hidden' ) . '>Drag the photos to change their order. Click &times; to remove one.</p>';
	echo '<button type="button" class="button" id="og-gal-btn">اختيار الصور</button> <button type="button" class="button-link" id="og-gal-clear" style="margin-inline-start:10px;color:#b32d2e"' . ( $ids ? '' : ' hidden' ) . '>إزالة الكل</button>';
	?>
	<style>
	.og-gal-list{display:flex;flex-wrap:wrap;gap:10px;margin:0 0 10px;padding:0;list-style:none}
	.og-gal-it{position:relative;width:96px;height:72px;margin:0;border-radius:8px;overflow:visible;cursor:move;background:#F3F4F6}
	.og-gal-it img{display:block;width:96px;height:72px;object-fit:cover;border-radius:8px;box-shadow:0 0 0 1px rgba(0,0,0,.08)}
	.og-gal-x{position:absolute;top:-8px;right:-8px;width:24px;height:24px;padding:0;border:2px solid #FFFFFF;border-radius:50%;background:#15171C;color:#FFFFFF;font-size:16px;line-height:18px;text-align:center;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,.25)}
	.og-gal-x:hover,.og-gal-x:focus{background:#B32D2E;outline:none}
	.og-gal-ph{width:96px;height:72px;border:2px dashed #C3C4C7;border-radius:8px;background:transparent}
	.og-gal-help{margin:0 0 10px;color:#646970;font-size:12.5px}
	</style>
	<script>
	jQuery(function($){var f,$list=$('#og-gal-prev'),$in=$('#og_gallery');
	function sync(){var ids=$list.children('.og-gal-it').map(function(){return $(this).data('id')}).get();$in.val(ids.join(','));$('.og-gal-help').prop('hidden',ids.length<2);$('#og-gal-clear').prop('hidden',!ids.length);}
	function tile(id,u){return $('<li class="og-gal-it"><img alt=""><button type="button" class="og-gal-x" aria-label="Remove this photo" title="Remove this photo">&times;</button></li>').attr('data-id',id).data('id',id).find('img').attr('src',u).end();}
	if($.fn.sortable){$list.sortable({items:'.og-gal-it',placeholder:'og-gal-ph',tolerance:'pointer',update:sync});}
	$list.on('click','.og-gal-x',function(e){e.preventDefault();$(this).closest('.og-gal-it').remove();sync();});
	$('#og-gal-btn').on('click',function(e){e.preventDefault();if(f){f.open();return;}
	f=wp.media({title:'صور السيارة',multiple:'add',library:{type:'image'},button:{text:'استخدام الصور'}});
	f.on('open',function(){var s=f.state().get('selection');s.reset();($in.val()||'').split(',').forEach(function(id){if(id){var a=wp.media.attachment(id);a.fetch();s.add(a);}});});
	f.on('select',function(){var keep={},order=[];f.state().get('selection').each(function(a){keep[a.id]=a;});
	/* keep the current order for photos still selected, then add the new ones at the end */
	$list.children('.og-gal-it').each(function(){var id=$(this).data('id');if(keep[id]){order.push(id);delete keep[id];}else{$(this).remove();}});
	Object.keys(keep).forEach(function(id){var a=keep[id],z=a.get('sizes'),u=z&&z.thumbnail?z.thumbnail.url:a.get('url');$list.append(tile(a.id,u));});
	sync();});
	f.open();});
	$('#og-gal-clear').on('click',function(e){e.preventDefault();if(!confirm('Remove all extra photos?'))return;$list.empty();sync();});
	});
	</script>
	<?php
}

function orego_car_features_box( $post ) {
	$have = (array) get_post_meta( $post->ID, 'og_features', true );
	echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:18px">';
	foreach ( orego_feature_groups() as $g ) {
		echo '<fieldset><legend style="font-weight:600;margin-bottom:6px">' . esc_html( $g[0] ) . '</legend>';
		foreach ( $g[2] as $k => $label ) {
			echo '<label style="display:block;margin:4px 0"><input type="checkbox" name="og_features[]" value="' . esc_attr( $k ) . '"' . checked( in_array( $k, $have, true ), true, false ) . '> ' . esc_html( $label ) . '</label>';
		}
		echo '</fieldset>';
	}
	echo '</div>';
}

function orego_car_trips_box( $post ) {
	$dist = get_post_meta( $post->ID, 'og_distance', true );
	echo '<p><label for="og_distance" style="font-weight:600;display:block;margin-bottom:6px">الأميال المشمولة يوميًا</label><input type="number" min="0" name="og_distance" id="og_distance" value="' . esc_attr( '' === $dist ? 200 : $dist ) . '" style="width:140px"></p>';
	echo '<p><label for="og_reviews" style="font-weight:600;display:block;margin-bottom:6px">عدد التقييمات</label><input type="number" min="0" name="og_reviews" id="og_reviews" value="' . esc_attr( get_post_meta( $post->ID, 'og_reviews', true ) ) . '" style="width:140px"> <span style="color:#646970">يظهر بجانب التقييم، مثل: 4.9 (124 تقييمًا)</span></p>';
	echo '<p><label for="og_booked" style="font-weight:600;display:block;margin-bottom:6px">أيام مغلقة يدويًا (صيانة أو حجز خارجي)</label><textarea name="og_booked" id="og_booked" rows="4" style="width:100%;direction:ltr;text-align:left" placeholder="2026-10-16 to 2026-10-17">' . esc_textarea( get_post_meta( $post->ID, 'og_booked', true ) ) . '</textarea></p>';
	echo '<p style="color:#646970;margin:0">الحجوزات من الموقع تُغلق الأيام تلقائيًا. استخدم هذا الحقل فقط لإغلاق أيام أخرى، سطر لكل فترة: 2026-10-16 to 2026-10-17 (يوم الإرجاع يبقى متاحًا للاستلام).</p>';
}

add_action( 'save_post_orego_car', function ( $post_id ) {
	if ( ! isset( $_POST['orego_car_extra_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['orego_car_extra_nonce'] ) ), 'orego_car_extra' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$gal = implode( ',', array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['og_gallery'] ?? '' ) ) ) ) ) );
	update_post_meta( $post_id, 'og_gallery', $gal );
	$valid = array();
	foreach ( orego_feature_groups() as $g ) {
		$valid = array_merge( $valid, array_keys( $g[2] ) );
	}
	$f = array_values( array_intersect( array_map( 'sanitize_key', (array) wp_unslash( $_POST['og_features'] ?? array() ) ), $valid ) );
	update_post_meta( $post_id, 'og_features', $f );
	update_post_meta( $post_id, 'og_distance', isset( $_POST['og_distance'] ) && '' !== $_POST['og_distance'] ? absint( $_POST['og_distance'] ) : 200 );
	update_post_meta( $post_id, 'og_booked', sanitize_textarea_field( wp_unslash( $_POST['og_booked'] ?? '' ) ) );
	if ( isset( $_POST['og_reviews'] ) && '' !== $_POST['og_reviews'] ) {
		update_post_meta( $post_id, 'og_reviews', absint( $_POST['og_reviews'] ) );
	} else {
		delete_post_meta( $post_id, 'og_reviews' );
	}
} );

/* ---------- Front end: the page ---------- */

function orego_icon( $name, $size = 16 ) {
	$p = array(
		'back'    => '<path d="M5 12h14M12 5l7 7-7 7"/>',
		'heart'   => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7Z"/>',
		'camera'  => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
		'pin'     => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'users'   => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
		'gear'    => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
		'fuel'    => '<path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18M2 22h14M7 9h4M15 13h2a2 2 0 0 1 2 2v3a1.5 1.5 0 0 0 3 0V9l-3-3"/>',
		'car'     => '<path d="M5 16H3v-4l2-5h11l3 5h2v4h-2"/><circle cx="7.5" cy="16.5" r="2"/><circle cx="16.5" cy="16.5" r="2"/><path d="M9.5 16.5h5"/>',
		'bolt'    => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
		'shield'  => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
		'check'   => '<path d="M20 6 9 17l-5-5"/>',
		'cal'     => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
		'card'    => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
		'gauge'   => '<path d="M12 14l4-4"/><path d="M3.3 19a10 10 0 1 1 17.4 0"/>',
		'shieldc' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
		'signal'  => '<path d="M4.9 19.1a10 10 0 0 1 0-14.2M7.8 16.2a6 6 0 0 1 0-8.4M16.2 7.8a6 6 0 0 1 0 8.4M19.1 4.9a10 10 0 0 1 0 14.2"/><circle cx="12" cy="12" r="2"/>',
		'nosmoke' => '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
		'eye'     => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
		'warn'    => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
		'msg'     => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
		'arrow'   => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
		'shift'   => '<circle cx="6" cy="5" r="2"/><circle cx="12" cy="5" r="2"/><circle cx="18" cy="5" r="2"/><circle cx="6" cy="19" r="2"/><circle cx="12" cy="19" r="2"/><path d="M6 7v10M12 7v10M18 7v5H6"/>',
		'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
		'headset' => '<path d="M3 14v-2a9 9 0 0 1 18 0v2"/><path d="M21 16a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2zM3 16a2 2 0 0 0 2 2h1v-6H5a2 2 0 0 0-2 2z"/>',
		'calok'   => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
		'next'    => '<path d="m15 18-6-6 6-6"/>',
		'prev'    => '<path d="m9 18 6-6-6-6"/>',
	);
	return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $name ] . '</svg>';
}

add_action( 'template_redirect', function () {
	if ( ! is_singular( 'orego_car' ) ) {
		return;
	}
	$post = get_queried_object();
	$id   = $post->ID;
	$d    = orego_car_data( $id );
	$opt  = orego_car_options();
	$main = apply_filters( 'orego_car_main_html', orego_car_main( $id, $d, $opt ), $id, $d );
	$sim  = orego_car_similar( $id );
	$head = ''; // header & footer come from the theme hooks (orego-theme-chrome.php)
	$foot = '';
	add_action( 'wp_enqueue_scripts', function () {
		wp_register_style( 'orego-car-page', false, array(), '1.0' );
		wp_enqueue_style( 'orego-car-page' );
		wp_add_inline_style( 'orego-car-page', orego_car_page_css() );
	}, 30 );
	add_action( 'wp_head', function () use ( $post, $d, $opt ) {
		echo orego_car_meta_tags( $post, $d, $opt ); // phpcs:ignore
	}, 2 );
	add_filter( 'document_title_parts', function ( $t ) use ( $post ) {
		$t['title'] = get_the_title( $post );
		return $t;
	} );
	?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'og-car-page' ); ?>>
<?php wp_body_open(); ?>
<?php echo $head; // phpcs:ignore ?>
<main class="ogd">
<?php echo $main; // phpcs:ignore ?>
<?php echo $sim; // phpcs:ignore ?>
</main>
<?php echo $foot; // phpcs:ignore ?>
<script>
(function(){
var g=document.querySelector('.ogd-gal');if(g){var s=g.querySelectorAll('.ogd-slide'),i=0,c=g.querySelector('.ogd-count b');
function go(n){s[i].classList.remove('is-on');i=(n+s.length)%s.length;s[i].classList.add('is-on');if(c)c.textContent=i+1;g.dispatchEvent(new CustomEvent('og:slide',{detail:i}));}
g.ogGo=function(n){go(n);};
var p=g.querySelector('.ogd-prev'),n=g.querySelector('.ogd-next');if(p)p.addEventListener('click',function(){go(i-1);});if(n)n.addEventListener('click',function(){go(i+1);});
var x=null;g.addEventListener('touchstart',function(e){x=e.touches[0].clientX;},{passive:true});g.addEventListener('touchend',function(e){if(x===null||s.length<2)return;var dx=e.changedTouches[0].clientX-x;if(Math.abs(dx)>40)go(i+(dx<0?-1:1));x=null;});}
var favs=[];try{favs=JSON.parse(localStorage.getItem('og_favs')||'[]');}catch(e){}
[].forEach.call(document.querySelectorAll('.ogd-fav'),function(b){if(favs.indexOf(b.dataset.id)>-1)b.setAttribute('aria-pressed','true');b.addEventListener('click',function(){var on=b.getAttribute('aria-pressed')!=='true';b.setAttribute('aria-pressed',on?'true':'false');favs=favs.filter(function(v){return v!==b.dataset.id;});if(on)favs.push(b.dataset.id);try{localStorage.setItem('og_favs',JSON.stringify(favs));}catch(e){}});});
})();
</script>
<?php wp_footer(); ?>
</body>
</html>
	<?php
	exit;
}, 20 );

function orego_count_ar( $n, $one, $two, $few, $many ) {
	$n = (int) $n;
	if ( 1 === $n ) {
		return $one;
	}
	if ( 2 === $n ) {
		return $two;
	}
	return number_format( $n ) . ' ' . ( $n >= 3 && $n <= 10 ? $few : $many );
}

function orego_car_main( $id, $d, $opt ) {
	$name    = get_the_title( $id );
	$imgs    = orego_car_images( $id );
	$terms   = get_the_terms( $id, 'car_type' );
	$type    = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
	$dist    = get_post_meta( $id, 'og_distance', true );
	$dist    = '' === $dist ? 200 : (int) $dist;
	$feat    = (array) get_post_meta( $id, 'og_features', true );
	$trips   = orego_car_bookings( $id );
	$reviews = (int) get_post_meta( $id, 'og_reviews', true );
	$elec    = 'electric' === $d['fuel'];
	$seats   = (int) $d['seats'];
	$seatTxt = ( 1 === $seats ? '1 seat' : $seats . ' seats' );
	$trans   = $opt['trans'][ $d['trans'] ] ?? '';
	$fuel    = $opt['fuel'][ $d['fuel'] ] ?? '';
	$email   = get_option( 'admin_email' );
	$n       = count( $imgs );
	$sub     = array_filter( array( $type, $seatTxt, $trans ) );
	$desc    = trim( (string) get_post_field( 'post_content', $id ) );
	ob_start();
	?>
<div class="ogx">
	<a class="ogx-back" href="<?php echo esc_url( orego_purl( 'cars' ) ); ?>"><?php echo orego_icon( 'back', 15 ); // phpcs:ignore ?>Back to cars</a>
	<div class="ogx-top">
		<div class="ogx-gal ogd-gal">
			<?php if ( $n ) : foreach ( $imgs as $k => $img ) : ?>
				<div class="ogd-slide<?php echo 0 === $k ? ' is-on' : ''; ?>"><?php echo wp_get_attachment_image( $img, 'large', false, array( 'alt' => esc_attr( $name ), 'loading' => 0 === $k ? 'eager' : 'lazy', 'sizes' => '(max-width: 900px) 100vw, 680px' ) ); ?></div>
			<?php endforeach; else : ?>
				<div class="ogd-slide is-on ogx-noimg"><?php echo orego_icon( 'car', 64 ); // phpcs:ignore ?></div>
			<?php endif; ?>
			<button type="button" class="ogx-fav ogd-fav" data-id="<?php echo esc_attr( $id ); ?>" aria-pressed="false" aria-label="<?php echo esc_attr( 'Add ' . $name . ' to favourites' ); ?>"><?php echo orego_icon( 'heart', 18 ); // phpcs:ignore ?></button>
			<?php if ( $n > 1 ) : ?>
				<button type="button" class="ogx-arr ogd-prev" aria-label="Previous photo"><?php echo orego_icon( 'prev', 18 ); // phpcs:ignore ?></button>
				<button type="button" class="ogx-arr ogd-next" aria-label="Next photo"><?php echo orego_icon( 'next', 18 ); // phpcs:ignore ?></button>
			<?php endif; ?>
			<span class="ogx-count ogd-count" dir="ltr"><b>1</b>/<?php echo (int) max( 1, $n ); ?></span>
		</div>

		<div class="ogx-info">
			<h1><?php echo esc_html( $name ); ?></h1>
			<?php if ( $sub ) : ?><p class="ogx-sub"><?php echo implode( '<i>•</i>', array_map( 'esc_html', $sub ) ); // phpcs:ignore ?></p><?php endif; ?>

			<ul class="ogx-tiles">
				<li><?php echo orego_icon( 'shift', 22 ); // phpcs:ignore ?><span><b><?php echo esc_html( $trans ); ?></b><small>Transmission</small></span></li>
				<li><?php echo orego_icon( $elec ? 'bolt' : 'fuel', 22 ); // phpcs:ignore ?><span><b><?php echo esc_html( $fuel ); ?></b><small>Fuel</small></span></li>
				<li><?php echo orego_icon( 'user', 22 ); // phpcs:ignore ?><span><b><?php echo esc_html( $seatTxt ); ?></b><small>Capacity</small></span></li>
			</ul>

			<div class="ogx-pr">
				<div class="ogx-price">
					<?php if ( '' !== $d['price'] ) : ?>
						<strong dir="ltr" class="og-money" data-usd="<?php echo esc_attr( (float) $d['price'] ); ?>"><?php echo esc_html( orego_money( $d['price'], false ) ); ?></strong><span>/ day</span>
					<?php else : ?>
						<strong class="ogx-ask">Price on request</strong>
					<?php endif; ?>
				</div>
				<?php if ( '' !== $d['rating'] ) : ?>
					<div class="ogx-rate"><span class="ogx-star">★</span><b><?php echo esc_html( number_format( (float) $d['rating'], 1 ) ); ?></b><?php if ( $reviews ) : ?><small>(<?php echo esc_html( ( 1 === (int) $reviews ? '1 review' : (int) $reviews . ' reviews' ) ); ?>)</small><?php endif; ?></div>
				<?php endif; ?>
			</div>

			<?php if ( $desc ) : ?>
				<div class="ogx-desc"><h2>Description</h2><?php echo wp_kses_post( wpautop( $desc ) ); ?></div>
			<?php endif; ?>

			<ul class="ogx-perks">
				<li><?php echo orego_icon( 'calok', 22 ); // phpcs:ignore ?><span><b>Free</b><small>Cancellation</small></span></li>
				<li><?php echo orego_icon( 'headset', 22 ); // phpcs:ignore ?><span><b dir="ltr">24/7</b><small>Support</small></span></li>
				<li><?php echo orego_icon( 'shield', 22 ); // phpcs:ignore ?><span><b>Optional</b><small>Insurance</small></span></li>
			</ul>

			<?php
			// Every car of this model booked (today, or on the searched dates): no booking button until one is back.
			$og_res = function_exists( 'orego_car_reserved_label' ) ? orego_car_reserved_label( $id ) : '';
			?>
			<div class="ogx-cta<?php echo $og_res ? ' is-unavailable' : ''; ?>">
			<?php if ( $og_res ) : ?>
				<button type="button" class="ogx-rent is-unavailable" disabled aria-disabled="true"><span>Car not available</span></button>
				<p class="ogx-back-on"><?php
				$og_next = ( 0 === strpos( $og_res, 'Reserved on' ) || ! function_exists( 'orego_car_next_free' ) ) ? '' : orego_car_next_free( $id );
				echo esc_html( $og_next ? 'Available again from ' . date_i18n( 'l j F', strtotime( $og_next . ' 12:00:00' ) ) : 'Not available on your dates. Try other dates.' );
				?></p>
			<?php else : ?>
				<a class="ogx-rent" href="<?php echo esc_url( function_exists( 'orego_booking_url' ) ? add_query_arg( function_exists( 'orego_trip_args' ) ? orego_trip_args() : array(), orego_booking_url( $id ) ) : 'mailto:' . $email . '?subject=' . rawurlencode( 'Booking request: ' . $name ) ); ?>"><span>Book now</span><?php echo orego_icon( 'arrow', 20 ); // phpcs:ignore ?></a>
			<?php endif; ?>
			</div>
			<p class="ogx-note"><?php echo esc_html( $dist ); ?> miles included per day · Free delivery · Free cancellation up to 48 hours before pick-up</p>
		</div>
	</div>

	<div class="ogx-more">
		<?php
		$specs  = function_exists( 'orego_car_specs' ) ? orego_car_specs( $id ) : array();
		$groups = array();
		foreach ( orego_feature_groups() as $gk => $g ) {
			$items = array_intersect_key( $g[2], array_flip( $feat ) );
			if ( $items ) {
				$groups[ $gk ] = array( $g[0], $items );
			}
		}
		$fcount = array_sum( array_map( fn( $g ) => count( $g[1] ), $groups ) );
		if ( $groups || $specs ) :
			?>
		<section class="ogx-sec ogx-fsec">
			<div class="ogx-sh"><h2><?php echo $specs && $groups ? 'Details &amp; features' : ( $groups ? 'Features' : 'Car details' ); ?></h2><?php if ( $fcount ) : ?><span class="ogx-cnt"><?php echo esc_html( $fcount . ( 1 === $fcount ? ' feature' : ' features' ) ); ?></span><?php endif; ?></div>
			<?php if ( $specs ) : ?>
				<dl class="ogx-specs"><?php foreach ( $specs as $r ) : ?><div><dt><?php echo esc_html( $r[0] ); ?></dt><dd><?php echo esc_html( $r[1] ); ?></dd></div><?php endforeach; ?></dl>
			<?php endif; ?>
			<?php foreach ( $groups as $g ) : ?>
				<h3><?php echo esc_html( $g[0] ); ?></h3>
				<ul class="ogx-feats"><?php foreach ( $g[1] as $label ) : ?><li><?php echo orego_icon( 'check', 15 ); // phpcs:ignore ?><?php echo esc_html( $label ); ?></li><?php endforeach; ?></ul>
			<?php endforeach; ?>
			<style>
			.ogx-more .ogx-fsec{grid-column:auto}
			.ogx-sh{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}.ogx-sh h2{margin:0!important}
			.ogx-cnt{flex:none;font-size:12px;font-weight:700;color:#15803D;background:#ECFDF3;border-radius:999px;padding:4px 11px}
			.ogx-specs{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));column-gap:28px;margin:0 0 4px;padding:2px 18px;background:#F8F9FB;border-radius:14px}
			.ogx-specs div{display:flex;justify-content:space-between;align-items:baseline;gap:12px;padding:12px 0;border-bottom:1px dashed #E1E4E9}
			.ogx-specs div:last-child,.ogx-specs div:nth-last-child(2):nth-child(odd){border-bottom:0}
			.ogx-specs dt{color:#6B7280;font-size:13.5px;margin:0}.ogx-specs dd{margin:0;font-weight:700;color:#15171C;font-size:13.5px;text-align:end}
			.ogx-fsec h3{display:flex;align-items:center;gap:10px;font-size:11.5px!important;letter-spacing:.07em;text-transform:uppercase;color:#9AA1AD!important;margin:22px 0 10px!important}
			.ogx-fsec h3:after{content:"";flex:1;height:1px;background:#EEF0F3}
			.ogx-fsec .ogx-specs+h3,.ogx-fsec .ogx-sh+h3{margin-top:4px!important}
			.ogx-fsec .ogx-specs+h3{margin-top:22px!important}
			.ogx-fsec .ogx-feats{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px 20px}
			.ogx-fsec .ogx-feats li{display:flex;align-items:center;gap:10px;padding:0;border:0;border-radius:0;background:none;font-size:14px;line-height:1.35;color:#15171C}
			.ogx-fsec .ogx-feats svg{width:24px;height:24px;padding:5px;border-radius:50%;background:#ECFDF3;color:#15803D;box-sizing:border-box}
			.ogx-rules svg{width:38px;height:38px;padding:9px;border-radius:12px;background:#F4F5F7;box-sizing:border-box;margin-top:0!important}
			.ogx-rules li{align-items:center}
			@media (max-width:600px){.ogx-specs{grid-template-columns:1fr}.ogx-specs div:nth-last-child(2):nth-child(odd){border-bottom:1px dashed #E1E4E9}}
			</style>
		</section>
		<?php endif; ?>

		<section class="ogx-sec">
			<h2>Rules of the road</h2>
			<ul class="ogx-rules">
				<li><?php echo orego_icon( 'nosmoke', 20 ); // phpcs:ignore ?><div><b>No smoking</b><p>Smoking in the car results in a $150 cleaning fee.</p></div></li>
				<li><?php echo orego_icon( 'eye', 20 ); // phpcs:ignore ?><div><b>Keep the car tidy</b><p>Excessively dirty cars may incur a $150 cleaning fee.</p></div></li>
				<li><?php echo orego_icon( $elec ? 'bolt' : 'fuel', 20 ); // phpcs:ignore ?><div><b><?php echo $elec ? 'Return it with the same charge level' : 'Return it with the same fuel level'; ?></b><p><?php echo $elec ? 'Missing charge is billed at market price.' : 'Missing fuel is billed at market price.'; ?></p></div></li>
				<li><?php echo orego_icon( 'warn', 20 ); // phpcs:ignore ?><div><b>No off-roading</b><p>Off-road damage is not covered by insurance.</p></div></li>
			</ul>
			<p class="ogx-small">Insurance is not included in the price and can be added when booking. The car may contain a device that collects driving and location data for recovery and protection purposes.</p>
		</section>
	</div>
</div>
	<?php
	return ob_get_clean();
}

function orego_car_similar( $id ) {
	$terms = wp_get_post_terms( $id, 'car_type', array( 'fields' => 'ids' ) );
	$base  = array( 'post_type' => 'orego_car', 'post_status' => orego_car_statuses(), 'posts_per_page' => 3, 'post__not_in' => array( $id ), 'no_found_rows' => true );
	$list  = array();
	if ( $terms && ! is_wp_error( $terms ) ) {
		$list = get_posts( $base + array( 'tax_query' => array( array( 'taxonomy' => 'car_type', 'terms' => $terms ) ) ) );
	}
	if ( count( $list ) < 3 ) {
		$base['post__not_in']   = array_merge( array( $id ), wp_list_pluck( $list, 'ID' ) );
		$base['posts_per_page'] = 3 - count( $list );
		$list                   = array_merge( $list, get_posts( $base ) );
	}
	if ( ! $list ) {
		return '';
	}
	ob_start();
	?>
<section class="ogd-wrap ogd-similar">
	<div class="ogd-sim-head">
		<div><h2>Similar cars you may like</h2><p>Explore more of our fleet</p></div>
		<a href="<?php echo esc_url( orego_purl( 'cars' ) ); ?>">View all<?php echo orego_icon( 'arrow', 16 ); // phpcs:ignore ?></a>
	</div>
	<?php global $shortcode_tags; echo isset( $shortcode_tags['orego_cars'] ) ? call_user_func( $shortcode_tags['orego_cars'], array( 'ids' => implode( ',', wp_list_pluck( $list, 'ID' ) ), 'limit' => count( $list ) ), '', 'orego_cars' ) : ''; // phpcs:ignore ?>
</section>
	<?php
	return ob_get_clean();
}

function orego_car_page_css() {
	return <<<'CSS'
body.og-car-page{margin:0;background:#FFFFFF}
.ogd{font-family:Cairo,Tahoma,sans-serif;color:#15171C;background:#FFFFFF;padding:28px 24px 80px}
.ogd *,.ogd *::before,.ogd *::after{box-sizing:border-box}
.ogd h1,.ogd h2,.ogd h3,.ogd p,.ogd ul{margin:0;padding:0}
.ogd ul{list-style:none}
.ogd a{text-decoration:none}
.ogx,.ogd-wrap{max-width:1200px;margin:0 auto}
.ogx-back{display:inline-flex;align-items:center;gap:8px;font-size:14px;color:#6B7280;margin-bottom:20px}
.ogx-back:hover{color:#15171C}
.ogx-top{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);gap:48px;align-items:start}
.ogx-gal{position:sticky;top:calc(var(--ogh-h,72px) + var(--wp-admin--admin-bar--height,0px) + 16px);aspect-ratio:4/3;border-radius:24px;overflow:hidden;background:linear-gradient(135deg,#E6E8EC,#D9DCE2)}
.ogd-slide{position:absolute;inset:0;opacity:0;transition:opacity .35s ease}
.ogd-slide.is-on{opacity:1}
.ogd-slide img{width:100%;height:100%;object-fit:cover;display:block}
.ogx-noimg{display:flex;align-items:center;justify-content:center;color:#9AA1AD}
.ogx-fav{position:absolute;top:16px;inset-inline-end:16px;width:40px;height:40px;border-radius:50%;border:0;background:rgba(255,255,255,.92);color:#15171C;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;z-index:2}
.ogx-fav[aria-pressed=true] svg{fill:#15171C}
.ogx-count{position:absolute;bottom:16px;inset-inline-end:16px;background:rgba(21,23,28,.62);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);color:#FFFFFF;font:600 14px/1 Cairo,Tahoma,sans-serif;padding:9px 14px;border-radius:12px;z-index:2}
.ogx-count b{font-weight:600}
.ogx-arr{position:absolute;top:50%;transform:translateY(-50%);width:42px;height:42px;border-radius:50%;border:0;background:rgba(255,255,255,.9);color:#15171C;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;z-index:2;opacity:0;transition:opacity .2s}
.ogx-gal:hover .ogx-arr,.ogx-arr:focus-visible{opacity:1}
.ogd-prev{inset-inline-start:16px}
.ogd-next{inset-inline-end:16px}
.ogx-info{padding-top:6px}
.ogx-info h1{font-size:34px;font-weight:700;line-height:1.3;color:#15171C}
.ogx-sub{display:flex;flex-wrap:wrap;align-items:center;gap:4px 12px;font-size:16px;color:#4B5563;margin-top:8px!important}
.ogx-sub i{font-style:normal;color:#9AA1AD;font-size:12px}
.ogx-tiles,.ogx-perks{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:24px!important}
.ogx-tiles li,.ogx-perks li{display:flex;align-items:center;gap:12px;border-radius:16px;padding:14px 16px;min-height:64px}
.ogx-tiles li{background:#F4F5F7}
.ogx-perks li{background:#FFFFFF;border:1px solid #E6E8EC}
.ogx-tiles svg,.ogx-perks svg{flex:none;color:#15171C}
.ogx-tiles span,.ogx-perks span{display:flex;flex-direction:column;min-width:0;line-height:1.35}
.ogx-tiles b,.ogx-perks b{font-size:14.5px;font-weight:700;color:#15171C;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ogx-tiles small,.ogx-perks small{font-size:12.5px;color:#6B7280}
.ogx-pr{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:28px}
.ogx-price{display:flex;align-items:baseline;gap:8px}
.ogx-price strong{font-size:38px;font-weight:700;line-height:1.1;color:#15171C}
.ogx-price strong.ogx-ask{font-size:24px}
.ogx-price span{font-size:17px;color:#4B5563}
.ogx-rate{display:flex;align-items:center;gap:6px;font-size:16px}
.ogx-star{color:#F5B800;font-size:20px;line-height:1}
.ogx-rate b{font-weight:700}
.ogx-rate small{font-size:14px;color:#6B7280}
.ogx-desc{margin-top:28px}
.ogx-desc h2{font-size:18px;font-weight:700;margin-bottom:6px!important}
.ogx-desc p{font-size:16px;line-height:1.75;color:#4B5563}
.ogx-desc p+p{margin-top:10px}
.ogx-perks{margin-top:28px!important}
.ogx-cta{margin-top:24px}
.ogx-rent{display:flex;align-items:center;justify-content:center;position:relative;height:58px;border-radius:999px;background:#15171C;color:#FFFFFF!important;font-size:18px;font-weight:700;transition:background .15s,transform .15s}
.ogx-rent svg{position:absolute;inset-inline-end:24px}
.ogx-rent:hover{background:#2A2D36}
.ogx-rent:active{transform:scale(.99)}
.ogx-rent:focus-visible{outline:3px solid var(--hx-primary);outline-offset:3px}
.ogx-note{font-size:12.5px;color:#9AA1AD;text-align:center;margin-top:12px!important;line-height:1.6}
.ogx-more{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-top:56px}
.ogx-sec{border:1px solid #E6E8EC;border-radius:20px;padding:24px}
.ogx-sec:first-child{grid-column:1/-1}
.ogx-sec h2{font-size:20px;font-weight:700;margin-bottom:14px!important}
.ogx-sec h3{font-size:14px;font-weight:700;color:#6B7280;margin:16px 0 10px!important}
.ogx-sec h2+h3{margin-top:0!important}
.ogx-feats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.ogx-feats li{display:flex;align-items:center;gap:8px;background:#F4F5F7;border-radius:12px;padding:10px 14px;font-size:14px}
.ogx-feats svg{flex:none;color:#15171C}
.ogx-trips{display:grid;gap:8px}
.ogx-trips li{display:flex;align-items:center;gap:10px;background:#F4F5F7;border-radius:12px;padding:12px 14px;font-size:14px}
.ogx-trips em{margin-inline-start:auto;font-style:normal;font-size:12px;font-weight:700;background:#15171C;color:#FFFFFF;border-radius:999px;padding:3px 10px}
.ogx-rules{display:grid;gap:14px}
.ogx-rules li{display:flex;gap:12px;align-items:flex-start}
.ogx-rules svg{flex:none;color:#15171C;margin-top:2px}
.ogx-rules b{display:block;font-size:14.5px;font-weight:700}
.ogx-rules p{font-size:13px;line-height:1.6;color:#6B7280}
.ogx-small{font-size:12px;line-height:1.6;color:#9AA1AD;margin-top:14px!important}
.ogd-sim-head{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:22px}
.ogd-sim-head h2{font-size:24px;font-weight:700;line-height:1.3}
.ogd-sim-head p{font-size:15px;color:#6B7280;margin-top:4px}
.ogd-sim-head a{display:inline-flex;align-items:center;gap:8px;font-size:15px;font-weight:700;color:#15171C}
.ogd-noimg{display:flex;align-items:center;justify-content:center;height:100%;color:#9AA1AD}
html.translated-ltr .ogd{direction:ltr}
html.translated-ltr .ogx-back svg,html.translated-ltr .ogx-rent svg,html.translated-ltr .ogd-sim-head a svg{transform:scaleX(-1)}
@media (max-width:1024px){.ogx-top{gap:32px}.ogx-feats{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:900px){
.ogd{padding:0 16px 64px}
.ogx-back{margin:14px 0 12px}
.ogx-top{grid-template-columns:1fr;gap:0}
.ogx-gal{position:relative;top:auto;margin:0 -16px;border-radius:0;aspect-ratio:4/3}
.ogx-arr{opacity:1;width:36px;height:36px}
.ogx-info{padding-top:22px}
.ogx-info h1{font-size:27px}
.ogx-sub{font-size:15px}
.ogx-tiles,.ogx-perks{gap:8px}
.ogx-tiles li,.ogx-perks li{flex-direction:row;padding:12px;gap:8px;min-height:60px}
.ogx-tiles svg,.ogx-perks svg{width:20px;height:20px}
.ogx-tiles b,.ogx-perks b{font-size:13.5px}
.ogx-tiles small,.ogx-perks small{font-size:11.5px}
.ogx-price strong{font-size:32px}
.ogx-cta{position:sticky;bottom:12px;z-index:5}
.ogx-rent{height:54px;box-shadow:0 10px 28px rgba(21,23,28,.25)}
.ogx-more{grid-template-columns:1fr;margin-top:36px;gap:16px}
.ogx-sec{padding:20px}
.ogx-feats{grid-template-columns:1fr 1fr}
}
@media (max-width:380px){.ogx-tiles li,.ogx-perks li{flex-direction:column;align-items:flex-start;gap:6px}}
@media (prefers-reduced-motion:reduce){.ogd-slide,.ogx-arr{transition:none}}
CSS;
}

/* ---------- SEO + share previews for car pages ---------- */

function orego_car_description( $post, $d, $opt ) {
	$name  = get_the_title( $post );
	$parts = array( $name . ' for rent' . ( $d['location'] ? ' in ' . $d['location'] : '' ) );
	if ( '' !== $d['price'] ) {
		$parts[0] .= ' from ' . orego_money( $d['price'], false ) . ' per day';
	}
	$seats   = (int) $d['seats'];
	$specs   = array_filter( array(
		$seats ? $seats . ( 1 === $seats ? ' seat' : ' seats' ) : '',
		$opt['trans'][ $d['trans'] ] ?? '',
		$opt['fuel'][ $d['fuel'] ] ?? '',
	) );
	$parts[] = implode( ', ', $specs );
	$parts[] = 'Free delivery and free cancellation up to 48 hours before pick-up';
	return implode( '. ', array_filter( $parts ) ) . '.';
}

function orego_car_meta_tags( $post, $d, $opt ) {
	// Leave it to an SEO plugin if one is installed later.
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || class_exists( 'SEOPress\\Core\\Kernel' ) ) {
		return '';
	}
	$title = get_the_title( $post );
	$desc  = orego_car_description( $post, $d, $opt );
	$url   = get_permalink( $post );
	$img   = has_post_thumbnail( $post ) ? wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'large' ) : false;
	$out   = '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	$out  .= '<meta property="og:type" content="product">' . "\n";
	$out  .= '<meta property="og:locale" content="en_US">' . "\n";
	$out  .= '<meta property="og:site_name" content="' . esc_attr( orego_brand() ) . '">' . "\n";
	$out  .= '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	$out  .= '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	$out  .= '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	if ( $img ) {
		$out .= '<meta property="og:image" content="' . esc_url( $img[0] ) . '">' . "\n";
		$out .= '<meta property="og:image:width" content="' . (int) $img[1] . '">' . "\n";
		$out .= '<meta property="og:image:height" content="' . (int) $img[2] . '">' . "\n";
		$out .= '<meta property="og:image:alt" content="' . esc_attr( $title ) . '">' . "\n";
	}
	$out .= '<meta name="twitter:card" content="' . ( $img ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	$out .= '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	$out .= '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	if ( $img ) {
		$out .= '<link rel="preload" as="image" href="' . esc_url( $img[0] ) . '" fetchpriority="high">' . "\n";
	}
	$ld = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'name'        => $title,
		'description' => $desc,
		'url'         => $url,
		'brand'       => array( '@type' => 'Brand', 'name' => orego_brand() ),
	);
	if ( $img ) {
		$ld['image'] = $img[0];
	}
	if ( '' !== $d['price'] ) {
		$ld['offers'] = array( '@type' => 'Offer', 'price' => number_format( (float) $d['price'], 2, '.', '' ), 'priceCurrency' => 'USD', 'availability' => 'https://schema.org/InStock', 'url' => $url );
	}
	$out .= '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	return $out;
}

/* ---------- New photo uploads: generate WebP sizes instead of PNG/JPEG ---------- */
add_filter( 'image_editor_output_format', function ( $formats ) {
	if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		$formats['image/png']  = 'image/webp';
		$formats['image/jpeg'] = 'image/webp';
	}
	return $formats;
} );

/* ================================================================== */
/* Site header menu location (used by the header in orego-chrome.php)  */
/* ================================================================== */

add_action( 'after_setup_theme', function () {
	register_nav_menus( array( 'orego_main' => 'Header menu' ) );
}, 20 );


