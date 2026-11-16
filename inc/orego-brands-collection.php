<?php
/**
 * Brands (Your Cars -> Brands): a brand has a name and a logo (an uploaded image or one of the
 * built-in logos in the theme's assets/brands/). Cars can be ticked with their brand.
 * The old Collections / Brands page was removed; its addresses open the Cars page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Brands                                                              */
/* ------------------------------------------------------------------ */

/** Brands are switched off (not needed). The brand data is kept, hidden; set the option orego_brands_on to 1 to bring them back. */
function orego_brands_on() {
	return (bool) get_option( 'orego_brands_on', false );
}

add_action( 'init', function () {
	register_taxonomy( 'car_brand', 'orego_car', array(
		'labels'            => array(
			'name'          => 'Brands',
			'singular_name' => 'Brand',
			'menu_name'     => 'Brands',
			'all_items'     => 'All brands',
			'add_new_item'  => 'Add a brand',
			'edit_item'     => 'Edit brand',
			'search_items'  => 'Search brands',
			'not_found'     => 'No brands yet.',
			'back_to_items' => 'Back to brands',
		),
		'public'            => false,
		'show_ui'           => orego_brands_on(),
		'show_in_menu'      => orego_brands_on(),
		'show_admin_column' => orego_brands_on(),
		'hierarchical'      => true, // checkbox list on the car screen
		'show_in_rest'      => false,
		'rewrite'           => false,
		'query_var'         => false,
	) );
}, 9 );

/* "Your Cars" menu is rebuilt from a fixed list (orego-dashboard.php): put Brands right after Car Types. */
add_filter( 'orego_app_menu', function ( $items ) {
	if ( ! orego_brands_on() ) {
		return $items;
	}
	$brands = array( function_exists( 'orego_L' ) ? orego_L( 'Brands', 'الماركات' ) : 'Brands', 'manage_categories', 'edit-tags.php?taxonomy=car_brand&post_type=orego_car', 'Brands' );
	$out    = array();
	$done   = false;
	foreach ( $items as $it ) {
		$out[] = $it;
		if ( ! $done && false !== strpos( (string) ( $it[2] ?? '' ), 'taxonomy=car_type' ) ) {
			$out[] = $brands;
			$done  = true;
		}
	}
	if ( ! $done ) {
		$out[] = $brands;
	}
	return $out;
}, 20 );

/** Built-in logos: slug => label. */
function orego_brand_builtin_logos() {
	$out = array();
	foreach ( glob( get_template_directory() . '/assets/brands/*.svg' ) as $f ) {
		$slug         = basename( $f, '.svg' );
		$out[ $slug ] = ucfirst( $slug );
	}
	return $out;
}

/** The logo of a brand as HTML (inline SVG for built-in logos so they follow the icon colour). */
function orego_brand_logo_html( $term ) {
	$id = (int) get_term_meta( $term->term_id, 'og_logo_id', true );
	if ( $id && ( $url = wp_get_attachment_image_url( $id, 'thumbnail' ) ) ) {
		return '<img src="' . esc_url( $url ) . '" alt="" loading="lazy" decoding="async">';
	}
	$slug = sanitize_key( (string) get_term_meta( $term->term_id, 'og_logo_builtin', true ) );
	$file = get_template_directory() . '/assets/brands/' . $slug . '.svg';
	if ( $slug && is_readable( $file ) ) {
		$svg = (string) file_get_contents( $file ); // phpcs:ignore
		$svg = preg_replace( '#<title>.*?</title>#s', '', $svg );
		return str_replace( '<svg ', '<svg aria-hidden="true" focusable="false" ', $svg );
	}
	return '<span class="ogc-letter" aria-hidden="true">' . esc_html( mb_strtoupper( mb_substr( $term->name, 0, 1 ) ) ) . '</span>';
}

/** Where a brand leads: the Cars page with only that brand. */
function orego_brand_url( $term ) {
	$cars = function_exists( 'orego_cz_cars_page_url' ) ? orego_cz_cars_page_url() : home_url( '/vehicles/' );
	// Brands ticked on cars filter exactly; otherwise search the name in the car titles.
	return $term->count > 0 ? add_query_arg( 'brand', $term->slug, $cars ) : add_query_arg( 'q', $term->name, $cars );
}

/* Logo fields on the Add / Edit brand screens. */
function orego_brand_logo_fields( $term = null ) {
	$id      = $term ? (int) get_term_meta( $term->term_id, 'og_logo_id', true ) : 0;
	$builtin = $term ? (string) get_term_meta( $term->term_id, 'og_logo_builtin', true ) : '';
	$img     = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
	wp_enqueue_media();
	ob_start();
	?>
	<div class="ogb-logo-field">
		<p><label for="og_logo_builtin"><strong>Built-in logo</strong></label><br>
		<select name="og_logo_builtin" id="og_logo_builtin"><option value="">None</option>
		<?php foreach ( orego_brand_builtin_logos() as $slug => $label ) : ?><option value="<?php echo esc_attr( $slug ); ?>"<?php selected( $builtin, $slug ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
		</select></p>
		<p><strong>Or upload a logo</strong> <span class="description">(square, transparent PNG or SVG works best; it replaces the built-in logo)</span><br>
		<input type="hidden" name="og_logo_id" id="og_logo_id" value="<?php echo esc_attr( $id ); ?>">
		<span class="ogb-prev"><?php echo $img ? '<img src="' . esc_url( $img ) . '" alt="">' : ''; ?></span>
		<button type="button" class="button ogb-pick"><?php echo $img ? 'Change image' : 'Choose image'; ?></button>
		<button type="button" class="button-link ogb-unpick"<?php echo $img ? '' : ' hidden'; ?>>Remove</button></p>
	</div>
	<style>.ogb-prev img{width:56px;height:56px;object-fit:contain;border-radius:50%;background:#F1F1F1;padding:8px;vertical-align:middle;margin-inline-end:8px}</style>
	<script>
	jQuery(function($){var f;$(document).on('click','.ogb-pick',function(e){e.preventDefault();f=f||wp.media({title:'Brand logo',library:{type:'image'},multiple:false,button:{text:'Use this logo'}});f.off('select').on('select',function(){var a=f.state().get('selection').first().toJSON();$('#og_logo_id').val(a.id);$('.ogb-prev').html('<img src="'+(a.sizes&&a.sizes.thumbnail?a.sizes.thumbnail.url:a.url)+'" alt="">');$('.ogb-pick').text('Change image');$('.ogb-unpick').prop('hidden',false);});f.open();});
	$(document).on('click','.ogb-unpick',function(e){e.preventDefault();$('#og_logo_id').val('');$('.ogb-prev').empty();$('.ogb-pick').text('Choose image');$(this).prop('hidden',true);});
	// After "Add brand" the form is cleared without reloading: clear the logo too.
	$(document).ajaxComplete(function(e,x,o){if(o&&o.data&&String(o.data).indexOf('action=add-tag')>-1&&String(o.data).indexOf('taxonomy=car_brand')>-1){$('#og_logo_id').val('');$('.ogb-prev').empty();$('#og_logo_builtin').val('');$('.ogb-pick').text('Choose image');$('.ogb-unpick').prop('hidden',true);}});});
	</script>
	<?php
	return ob_get_clean();
}

add_action( 'car_brand_add_form_fields', function () {
	echo '<div class="form-field">' . orego_brand_logo_fields() . '</div>'; // phpcs:ignore
} );

add_action( 'car_brand_edit_form_fields', function ( $term ) {
	echo '<tr class="form-field"><th scope="row">Logo</th><td>' . orego_brand_logo_fields( $term ) . '</td></tr>'; // phpcs:ignore
} );

function orego_brand_save_logo( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( isset( $_POST['og_logo_id'] ) ) { // phpcs:ignore -- WordPress checked the term form nonce
		$id = absint( $_POST['og_logo_id'] ); // phpcs:ignore
		$id && wp_attachment_is_image( $id ) ? update_term_meta( $term_id, 'og_logo_id', $id ) : delete_term_meta( $term_id, 'og_logo_id' );
	}
	if ( isset( $_POST['og_logo_builtin'] ) ) { // phpcs:ignore
		$slug = sanitize_key( wp_unslash( $_POST['og_logo_builtin'] ) ); // phpcs:ignore
		isset( orego_brand_builtin_logos()[ $slug ] ) ? update_term_meta( $term_id, 'og_logo_builtin', $slug ) : delete_term_meta( $term_id, 'og_logo_builtin' );
	}
	do_action( 'litespeed_purge_all' );
}
add_action( 'created_car_brand', 'orego_brand_save_logo' );
add_action( 'edited_car_brand', 'orego_brand_save_logo' );
add_action( 'delete_car_brand', function () {
	do_action( 'litespeed_purge_all' );
} );

/* Logo column in the brands list. */
add_filter( 'manage_edit-car_brand_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		if ( 'name' === $k ) {
			$new['og_logo'] = 'Logo';
		}
		if ( 'description' !== $k ) {
			$new[ $k ] = 'posts' === $k ? 'Cars' : $v;
		}
	}
	return $new;
} );
add_filter( 'manage_car_brand_custom_column', function ( $out, $col, $term_id ) {
	if ( 'og_logo' === $col ) {
		$out = '<span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:#F1F1F1;color:#15171C" class="ogb-col">' . orego_brand_logo_html( get_term( $term_id, 'car_brand' ) ) . '</span>';
	}
	return $out;
}, 10, 3 );
add_action( 'admin_head-edit-tags.php', function () {
	echo '<style>.column-og_logo{width:56px}.ogb-col svg{width:20px;height:20px;fill:currentColor}.ogb-col img{width:24px;height:24px;object-fit:contain}.ogb-col .ogc-letter{font-weight:700}.taxonomy-car_brand .term-description-wrap,.taxonomy-car_brand .term-parent-wrap{display:none}</style>';
} );

/* Cars page: ?brand=slug shows only that brand's cars. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || empty( $_GET['brand'] ) || 'orego_car' !== $q->get( 'post_type' ) ) { // phpcs:ignore
		return;
	}
	$slug = sanitize_title( wp_unslash( $_GET['brand'] ) ); // phpcs:ignore
	if ( ! term_exists( $slug, 'car_brand' ) ) {
		return;
	}
	$tax   = (array) $q->get( 'tax_query' );
	$tax[] = array( 'taxonomy' => 'car_brand', 'field' => 'slug', 'terms' => $slug );
	$q->set( 'tax_query', $tax );
} );

/* The page used to live at /collections/: send old links to /brands/. */
add_action( 'template_redirect', function () {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore
	// While the Brands page is not published, its old addresses open the Cars page.
	if ( 'collections' === $path || ( 'brands' === $path && 'publish' !== get_post_status( orego_pid( 'brands' ) ) ) ) {
		$q = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY ); // phpcs:ignore
		$to = 'publish' === get_post_status( orego_pid( 'brands' ) ) ? orego_purl( 'brands' ) : orego_purl( 'cars' );
		wp_safe_redirect( $to . ( $q ? '?' . $q : '' ), 302 );
		exit;
	}
}, 1 );
