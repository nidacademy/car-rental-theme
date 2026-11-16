<?php
/**
 * Exotic Cars admin UI
 * - Listing (cars) page with table/grid, search, filters, sorting, bulk + one-click actions
 * - Bookings page with filters, bulk actions, and a booking detail view
 * - Pickup Locations page
 * - Modern styling for the native car editor and Car Types screens
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=orego_car', 'Listing', 'Listing', 'edit_posts', 'orego-listing', 'orego_ui_listing' );
	add_submenu_page( 'edit.php?post_type=orego_car', 'Bookings', 'Bookings', 'edit_posts', 'orego-bookings', 'orego_ui_bookings' );
	add_submenu_page( 'edit.php?post_type=orego_car', 'Pickup Locations', 'Pickup Locations', 'manage_options', 'orego-places', 'orego_ui_locations' );
}, 16 );

function orego_ui_url( $page, $args = array() ) {
	return add_query_arg( $args, admin_url( 'edit.php?post_type=orego_car&page=' . $page ) );
}

/* Send the plain WordPress lists to the new pages */
add_action( 'load-edit.php', function () {
	$pt  = sanitize_key( $_GET['post_type'] ?? 'post' ); // phpcs:ignore
	$map = array( 'orego_car' => 'orego-listing', 'orego_booking' => 'orego-bookings' );
	if ( ! isset( $map[ $pt ] ) || ! empty( $_GET['og_native'] ) ) { // phpcs:ignore
		return;
	}
	$args = array();
	foreach ( array( 'trashed', 'untrashed', 'deleted' ) as $k ) {
		if ( isset( $_GET[ $k ] ) ) { // phpcs:ignore
			$args['og_done'] = $k . ':' . absint( $_GET[ $k ] ); // phpcs:ignore
		}
	}
	if ( ! empty( $_GET['og_status'] ) ) { // phpcs:ignore
		$args['status'] = sanitize_key( $_GET['og_status'] ); // phpcs:ignore
	}
	wp_safe_redirect( orego_ui_url( $map[ $pt ], $args ) );
	exit;
} );

add_action( 'load-post.php', function () {
	$id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore
	if ( $id && 'orego_booking' === get_post_type( $id ) && 'edit' === ( $_GET['action'] ?? '' ) ) { // phpcs:ignore
		wp_safe_redirect( orego_ui_url( 'orego-bookings', array( 'booking' => $id ) ) );
		exit;
	}
} );

/* Old locations page now lives at orego-places */
add_action( 'admin_page_access_denied', function () {
	if ( isset( $_GET['page'] ) && 'orego-locations' === $_GET['page'] ) { // phpcs:ignore
		wp_safe_redirect( orego_ui_url( 'orego-places' ) );
		exit;
	}
} );

add_filter( 'submenu_file', function ( $file ) {
	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $s && 'orego_car' === $s->post_type && 'post' === $s->base && 'add' !== $s->action ) {
		return 'orego-listing';
	}
	if ( $s && 'car_type' === $s->taxonomy ) {
		return 'edit-tags.php?taxonomy=car_type&post_type=orego_car';
	}
	return $file;
}, 20 );

/* ------------------------------------------------------------------ */
/* Shared bits                                                         */
/* ------------------------------------------------------------------ */

function orego_ui_ic( $n, $s = 16 ) {
	$p = array(
		'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'copy'    => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/>',
		'trash'   => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
		'restore' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
		'table'   => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 10v10"/>',
		'up'      => '<path d="m18 15-6-6-6 6"/>',
		'dn'      => '<path d="m6 9 6 6 6-6"/>',
		'pause'   => '<circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/>',
		'play'    => '<circle cx="12" cy="12" r="9"/><path d="m10 8 6 4-6 4z"/>',
		'flag'    => '<path d="M4 22V4M4 4h13l-2 4 2 4H4"/>',
		'star'    => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
		'seat'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
		'back'    => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
	);
	if ( isset( $p[ $n ] ) ) {
		return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $n ] . '</svg>';
	}
	return orego_dash_ic( $n, $s );
}

function orego_ui_notice() {
	$done = sanitize_text_field( wp_unslash( $_GET['og_done'] ?? '' ) ); // phpcs:ignore
	if ( ! $done ) {
		return;
	}
	list( $k, $n ) = array_pad( explode( ':', $done ), 2, 1 );
	$n   = max( 1, (int) $n );
	$msg = array(
		'trashed'   => $n . ' moved to trash.',
		'untrashed' => $n . ' restored.',
		'deleted'   => $n . ' permanently deleted.',
		'published' => $n . ' published.',
		'drafted'   => $n . ' switched to draft.',
		'duplicated' => 'Copy created as a draft.',
		'confirmed' => $n . ' approved. The customer was emailed.',
		'onroad'    => $n . ' marked as on road.',
		'declined'  => $n . ' declined. The customer was emailed.',
		'cancelled' => $n . ' cancelled. The customer was emailed.',
		'completed' => $n . ' marked as returned.',
		'pending'   => $n . ' set back to pending.',
		'saved'     => 'Saved.',
		'none'      => 'Nothing selected.',
	);
	if ( isset( $msg[ $k ] ) ) {
		echo '<div class="ogu-toast" role="status">' . orego_ui_ic( 'check', 16 ) . esc_html( $msg[ $k ] ) . '</div>'; // phpcs:ignore
	}
}

function orego_ui_head( $title, $sub, $actions = '' ) {
	echo '<style>' . orego_dash_css() . orego_ui_css() . '</style>'; // phpcs:ignore
	echo '<div class="ogdx"><main class="ogdx-main">';
	orego_ui_notice();
	echo '<header class="ogdx-head"><div><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $sub ) . '</p></div><div class="ogu-head-acts">' . $actions . '</div></header>'; // phpcs:ignore
}

function orego_ui_foot() {
	echo '</main></div><script>' . orego_ui_js() . '</script>'; // phpcs:ignore
}

function orego_ui_pager( $page, $pages, $base ) {
	if ( $pages < 2 ) {
		return '';
	}
	$h = '<nav class="ogu-pager" aria-label="Pages">';
	for ( $i = 1; $i <= $pages; $i++ ) {
		if ( $pages > 7 && $i > 2 && $i < $pages - 1 && abs( $i - $page ) > 1 ) {
			if ( 3 === $i || $pages - 2 === $i ) {
				$h .= '<span>…</span>';
			}
			continue;
		}
		$h .= $i === $page ? '<b aria-current="page">' . $i . '</b>' : '<a href="' . esc_url( add_query_arg( 'paged', $i, $base ) ) . '">' . $i . '</a>';
	}
	return $h . '</nav>';
}

function orego_ui_ago( $mysql ) {
	$t = strtotime( get_gmt_from_date( $mysql ) . ' UTC' );
	return human_time_diff( $t, time() ) . ' ago';
}

/* ------------------------------------------------------------------ */
/* Car actions                                                         */
/* ------------------------------------------------------------------ */

function orego_ui_car_act_url( $id, $do ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=orego_car_act&do=' . $do . '&id=' . (int) $id ), 'orego_car_act_' . $do . '_' . $id );
}

function orego_ui_car_do( $id, $do ) {
	$p = get_post( $id );
	if ( ! $p || 'orego_car' !== $p->post_type ) {
		return false;
	}
	if ( 'delete' === $do ? ! current_user_can( 'delete_post', $id ) : ! current_user_can( 'edit_post', $id ) ) {
		return false;
	}
	switch ( $do ) {
		case 'publish':
			return (bool) wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		case 'draft':
			return (bool) wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
		case 'trash':
			return (bool) wp_trash_post( $id );
		case 'restore':
			$ok = wp_untrash_post( $id );
			wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
			return (bool) $ok;
		case 'delete':
			return (bool) wp_delete_post( $id, true );
		case 'duplicate':
			$new = wp_insert_post( array( 'post_type' => 'orego_car', 'post_status' => 'draft', 'post_title' => $p->post_title . ' (copy)', 'post_content' => $p->post_content ) );
			if ( ! $new || is_wp_error( $new ) ) {
				return false;
			}
			foreach ( get_post_meta( $id ) as $k => $vals ) {
				if ( in_array( $k, array( '_edit_lock', '_edit_last', 'og_booked' ), true ) ) {
					continue;
				}
				foreach ( $vals as $v ) {
					add_post_meta( $new, $k, maybe_unserialize( $v ) );
				}
			}
			wp_set_object_terms( $new, wp_get_object_terms( $id, 'car_type', array( 'fields' => 'ids' ) ), 'car_type' );
			return $new;
	}
	return false;
}

add_action( 'admin_post_orego_car_act', function () {
	$id = absint( $_GET['id'] ?? 0 ); // phpcs:ignore
	$do = sanitize_key( $_GET['do'] ?? '' ); // phpcs:ignore
	check_admin_referer( 'orego_car_act_' . $do . '_' . $id );
	$r    = orego_ui_car_do( $id, $do );
	$word = array( 'publish' => 'published', 'draft' => 'drafted', 'trash' => 'trashed', 'restore' => 'untrashed', 'delete' => 'deleted', 'duplicate' => 'duplicated' );
	if ( 'duplicate' === $do && $r ) {
		wp_safe_redirect( admin_url( 'post.php?post=' . (int) $r . '&action=edit' ) );
		exit;
	}
	$back = remove_query_arg( array( 'og_done' ), wp_get_referer() ? wp_get_referer() : orego_ui_url( 'orego-listing' ) );
	wp_safe_redirect( add_query_arg( 'og_done', ( $word[ $do ] ?? 'saved' ) . ':1', $back ) );
	exit;
} );

add_action( 'admin_post_orego_car_bulk', function () {
	check_admin_referer( 'orego_car_bulk' );
	$do   = sanitize_key( $_POST['do'] ?? '' );
	$ids  = array_filter( array_map( 'absint', (array) ( $_POST['ids'] ?? array() ) ) );
	$n    = 0;
	foreach ( $ids as $id ) {
		if ( in_array( $do, array( 'publish', 'draft', 'trash', 'restore', 'delete' ), true ) && orego_ui_car_do( $id, $do ) ) {
			$n++;
		}
	}
	$word = array( 'publish' => 'published', 'draft' => 'drafted', 'trash' => 'trashed', 'restore' => 'untrashed', 'delete' => 'deleted' );
	$back = remove_query_arg( array( 'og_done' ), wp_get_referer() ? wp_get_referer() : orego_ui_url( 'orego-listing' ) );
	wp_safe_redirect( add_query_arg( 'og_done', $n ? ( $word[ $do ] ?? 'saved' ) . ':' . $n : 'none', $back ) );
	exit;
} );

/* ------------------------------------------------------------------ */
/* Listing page                                                        */
/* ------------------------------------------------------------------ */

function orego_ui_listing() {
	$uid    = get_current_user_id();
	$status = sanitize_key( $_GET['status'] ?? 'all' ); // phpcs:ignore
	$status = in_array( $status, array( 'all', 'publish', 'draft', 'trash' ), true ) ? $status : 'all';
	$s      = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore
	$type   = sanitize_title( wp_unslash( $_GET['type'] ?? '' ) ); // phpcs:ignore
	$sort   = sanitize_key( $_GET['sort'] ?? 'newest' ); // phpcs:ignore
	$view   = sanitize_key( $_GET['view'] ?? '' ); // phpcs:ignore
	if ( in_array( $view, array( 'table', 'grid' ), true ) ) {
		update_user_meta( $uid, 'orego_list_view', $view );
	} else {
		$view = get_user_meta( $uid, 'orego_list_view', true ) ? get_user_meta( $uid, 'orego_list_view', true ) : 'table';
	}
	$paged  = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore
	$counts = wp_count_posts( 'orego_car' );
	$stmap  = array( 'all' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'publish' => array( 'publish' ), 'draft' => array( 'draft', 'pending', 'future' ), 'trash' => array( 'trash' ) );
	$q      = array( 'post_type' => 'orego_car', 'post_status' => $stmap[ $status ], 'posts_per_page' => 20, 'paged' => $paged, 's' => $s );
	if ( $type ) {
		$q['tax_query'] = array( array( 'taxonomy' => 'car_type', 'field' => 'slug', 'terms' => $type ) );
	}
	$num = function ( $key ) {
		return array( 'relation' => 'OR', 'v' => array( 'key' => $key, 'type' => 'DECIMAL(10,2)' ), 'nv' => array( 'key' => $key, 'compare' => 'NOT EXISTS' ) );
	};
	switch ( $sort ) {
		case 'name':
			$q['orderby'] = 'title';
			$q['order']   = 'ASC';
			break;
		case 'price_asc':
		case 'price_desc':
			$q['meta_query'] = $num( 'og_price' );
			$q['orderby']    = array( 'v' => 'price_asc' === $sort ? 'ASC' : 'DESC' );
			break;
		case 'rating':
			$q['meta_query'] = $num( 'og_rating' );
			$q['orderby']    = array( 'v' => 'DESC' );
			break;
		default:
			$sort         = 'newest';
			$q['orderby'] = 'date';
			$q['order']   = 'DESC';
	}
	$query = new WP_Query( $q );
	$today = current_time( 'Y-m-d' );
	$up    = array();
	foreach ( orego_dash_bookings() as $b ) {
		if ( in_array( $b['status'], array( 'pending', 'confirmed', 'onroad' ), true ) && $b['end'] >= $today ) {
			$up[ $b['car'] ] = ( $up[ $b['car'] ] ?? 0 ) + 1;
		}
	}
	$base  = orego_ui_url( 'orego-listing', array_filter( array( 'status' => 'all' === $status ? '' : $status, 's' => $s, 'type' => $type, 'sort' => 'newest' === $sort ? '' : $sort ) ) );
	$total = (int) $counts->publish + (int) $counts->draft + (int) $counts->pending + (int) $counts->future + (int) $counts->private;
	$tabs  = array( 'all' => array( 'All', $total ), 'publish' => array( 'Published', (int) $counts->publish ), 'draft' => array( 'Drafts', (int) $counts->draft + (int) $counts->pending + (int) $counts->future ), 'trash' => array( 'Trash', (int) $counts->trash ) );
	$types = get_terms( array( 'taxonomy' => 'car_type', 'hide_empty' => false ) );
	$acts  = '<a class="ogdx-btn" href="' . esc_url( admin_url( 'post-new.php?post_type=orego_car' ) ) . '">' . orego_ui_ic( 'plus', 18 ) . 'Add Car</a>';
	orego_ui_head( 'Listing', $total . ' ' . ( 1 === $total ? 'car' : 'cars' ) . ' in your fleet · ' . (int) $counts->publish . ' live on the website', $acts );
	?>
	<nav class="ogu-tabs" aria-label="Status">
		<?php foreach ( $tabs as $k => $t ) : if ( 'trash' === $k && ! $t[1] ) { continue; } ?>
			<a href="<?php echo esc_url( orego_ui_url( 'orego-listing', array_filter( array( 'status' => 'all' === $k ? '' : $k ) ) ) ); ?>" class="<?php echo $status === $k ? 'is-on' : ''; ?>"><?php echo esc_html( $t[0] ); ?><em><?php echo (int) $t[1]; ?></em></a>
		<?php endforeach; ?>
	</nav>

	<form class="ogu-bar" method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
		<input type="hidden" name="post_type" value="orego_car"><input type="hidden" name="page" value="orego-listing">
		<?php if ( 'all' !== $status ) : ?><input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>"><?php endif; ?>
		<label class="ogu-search"><?php echo orego_ui_ic( 'search', 17 ); // phpcs:ignore ?><span class="screen-reader-text">Search cars</span><input type="search" name="s" value="<?php echo esc_attr( $s ); ?>" placeholder="Search cars by name…"></label>
		<select name="type" aria-label="Car type" data-autosubmit><option value="">All types</option><?php if ( ! is_wp_error( $types ) ) : foreach ( $types as $t ) : ?><option value="<?php echo esc_attr( $t->slug ); ?>"<?php selected( $type, $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option><?php endforeach; endif; ?></select>
		<select name="sort" aria-label="Sort" data-autosubmit><?php foreach ( array( 'newest' => 'Newest first', 'name' => 'Name A–Z', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'rating' => 'Top rated' ) as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $sort, $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select>
		<div class="ogu-views" role="group" aria-label="View">
			<a href="<?php echo esc_url( add_query_arg( 'view', 'table', $base ) ); ?>" class="<?php echo 'table' === $view ? 'is-on' : ''; ?>" title="Table view" aria-label="Table view"><?php echo orego_ui_ic( 'table', 18 ); // phpcs:ignore ?></a>
			<a href="<?php echo esc_url( add_query_arg( 'view', 'grid', $base ) ); ?>" class="<?php echo 'grid' === $view ? 'is-on' : ''; ?>" title="Grid view" aria-label="Grid view"><?php echo orego_ui_ic( 'grid', 18 ); // phpcs:ignore ?></a>
		</div>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ogu-bulkform">
		<input type="hidden" name="action" value="orego_car_bulk"><?php wp_nonce_field( 'orego_car_bulk' ); ?>
		<div class="ogu-bulk" hidden>
			<span><b class="ogu-sel-n">0</b> selected</span>
			<select name="do" aria-label="Bulk action"><?php if ( 'trash' === $status ) : ?><option value="restore">Restore</option><option value="delete">Delete permanently</option><?php else : ?><option value="publish">Publish</option><option value="draft">Switch to draft</option><option value="trash">Move to trash</option><?php endif; ?></select>
			<button type="submit" class="ogdx-btn" data-confirm-delete>Apply</button>
			<button type="button" class="ogu-plain ogu-clear">Clear</button>
		</div>

	<?php if ( ! $query->have_posts() ) : ?>
		<section class="ogdx-card"><div class="ogdx-empty"><?php echo orego_ui_ic( 'car', 40 ); // phpcs:ignore ?><b><?php echo $s || $type ? 'No cars match your search' : ( 'trash' === $status ? 'Trash is empty' : 'No cars yet' ); ?></b><span><?php echo $s || $type ? 'Try another name or clear the filters.' : 'Add your first car and it appears here and on the website.'; ?></span><?php if ( $s || $type ) : ?><a class="ogdx-btn is-soft" href="<?php echo esc_url( orego_ui_url( 'orego-listing' ) ); ?>">Clear filters</a><?php elseif ( 'trash' !== $status ) : ?><a class="ogdx-btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=orego_car' ) ); ?>">Add Car</a><?php endif; ?></div></section>
	<?php elseif ( 'grid' === $view ) : ?>
		<div class="ogu-grid">
		<?php while ( $query->have_posts() ) : $query->the_post(); orego_ui_car_card( get_post(), $up, $status ); endwhile; ?>
		</div>
	<?php else : ?>
		<section class="ogdx-card ogu-tablecard">
		<div class="ogu-tablewrap"><table class="ogu-table">
			<thead><tr><th class="ogu-cb"><input type="checkbox" class="ogu-all" aria-label="Select all"></th><th>Car</th><th>Price</th><th>Specs</th><th>Location</th><th>Bookings</th><th>Rating</th><th>Status</th><th class="ogu-r"><span class="screen-reader-text">Actions</span></th></tr></thead>
			<tbody>
			<?php while ( $query->have_posts() ) : $query->the_post(); orego_ui_car_row( get_post(), $up, $status ); endwhile; ?>
			</tbody>
		</table></div>
		</section>
	<?php endif; wp_reset_postdata(); ?>
	</form>
	<?php
	echo orego_ui_pager( $paged, (int) $query->max_num_pages, add_query_arg( 'view', $view, $base ) ); // phpcs:ignore
	orego_ui_foot();
}

function orego_ui_status_pill( $st ) {
	$m = array( 'publish' => array( 'Live', 'is-confirmed' ), 'draft' => array( 'Draft', 'is-cancelled' ), 'pending' => array( 'Pending review', 'is-pending' ), 'future' => array( 'Scheduled', 'is-completed' ), 'private' => array( 'Private', 'is-cancelled' ), 'trash' => array( 'In trash', 'is-declined' ) );
	$v = $m[ $st ] ?? array( ucfirst( $st ), 'is-cancelled' );
	return '<i class="ogdx-st ' . $v[1] . '">' . esc_html( $v[0] ) . '</i>';
}

function orego_ui_car_actions( $c, $status ) {
	$h = '';
	if ( 'trash' === $status ) {
		$h .= '<a href="' . esc_url( orego_ui_car_act_url( $c->ID, 'restore' ) ) . '" title="Restore" aria-label="Restore">' . orego_ui_ic( 'restore' ) . '</a>';
		$h .= '<a href="' . esc_url( orego_ui_car_act_url( $c->ID, 'delete' ) ) . '" class="is-danger" title="Delete permanently" aria-label="Delete permanently" data-confirm="Delete this car permanently? This cannot be undone.">' . orego_ui_ic( 'trash' ) . '</a>';
		return $h;
	}
	$h .= '<a href="' . esc_url( get_edit_post_link( $c->ID ) ) . '" title="Edit" aria-label="Edit">' . orego_ui_ic( 'edit' ) . '</a>';
	$h .= '<a href="' . esc_url( 'publish' === $c->post_status ? get_permalink( $c ) : get_preview_post_link( $c ) ) . '" target="_blank" rel="noopener" title="' . ( 'publish' === $c->post_status ? 'View on website' : 'Preview' ) . '" aria-label="View">' . orego_ui_ic( 'eye' ) . '</a>';
	$h .= '<a href="' . esc_url( orego_ui_car_act_url( $c->ID, 'duplicate' ) ) . '" title="Duplicate" aria-label="Duplicate">' . orego_ui_ic( 'copy' ) . '</a>';
	$h .= 'publish' === $c->post_status
		? '<a href="' . esc_url( orego_ui_car_act_url( $c->ID, 'draft' ) ) . '" title="Unpublish (hide from website)" aria-label="Unpublish">' . orego_ui_ic( 'pause' ) . '</a>'
		: '<a href="' . esc_url( orego_ui_car_act_url( $c->ID, 'publish' ) ) . '" class="is-go" title="Publish to website" aria-label="Publish">' . orego_ui_ic( 'play' ) . '</a>';
	$h .= '<a href="' . esc_url( orego_ui_car_act_url( $c->ID, 'trash' ) ) . '" class="is-danger" title="Move to trash" aria-label="Move to trash">' . orego_ui_ic( 'trash' ) . '</a>';
	return $h;
}

function orego_ui_car_row( $c, $up, $status ) {
	$d     = orego_car_data( $c->ID );
	$terms = get_the_terms( $c->ID, 'car_type' );
	$tname = ( $terms && ! is_wp_error( $terms ) ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : 'No type';
	$n     = $up[ $c->ID ] ?? 0;
	?>
	<tr>
		<td class="ogu-cb"><input type="checkbox" name="ids[]" value="<?php echo (int) $c->ID; ?>" aria-label="Select <?php echo esc_attr( $c->post_title ); ?>"></td>
		<td><a class="ogu-carcell" href="<?php echo esc_url( get_edit_post_link( $c->ID ) ); ?>"><span class="ogu-thumb"><?php echo has_post_thumbnail( $c ) ? get_the_post_thumbnail( $c, 'thumbnail', array( 'alt' => '' ) ) : orego_ui_ic( 'car', 22 ); // phpcs:ignore ?></span><span><b><?php echo esc_html( $c->post_title ? $c->post_title : '(no name)' ); ?></b><small><?php echo esc_html( $tname ); ?> · #<?php echo (int) $c->ID; ?></small></span></a></td>
		<td><?php echo '' === $d['price'] ? '<span class="ogu-warn">No price</span>' : '<b class="ogu-price">' . esc_html( orego_dash_money( $d['price'] ) ) . '</b><small class="ogu-mute"> /day</small>'; // phpcs:ignore ?></td>
		<td class="ogu-mute"><?php echo esc_html( $d['seats'] . ' seats · ' . orego_dash_en( 'trans', $d['trans'] ) . ' · ' . orego_dash_en( 'fuel', $d['fuel'] ) ); ?></td>
		<td class="ogu-mute"><?php echo esc_html( $d['location'] ? $d['location'] : '–' ); ?></td>
		<td><?php echo $n ? '<a class="ogu-chip" href="' . esc_url( orego_ui_url( 'orego-bookings', array( 'car' => $c->ID ) ) ) . '">' . (int) $n . ' upcoming</a>' : '<span class="ogu-mute">None</span>'; // phpcs:ignore ?></td>
		<td><?php echo '' === $d['rating'] ? '<span class="ogu-mute">–</span>' : '<span class="ogu-rating">' . orego_ui_ic( 'star', 14 ) . esc_html( number_format( (float) $d['rating'], 1 ) ) . '</span>'; // phpcs:ignore ?></td>
		<td><?php echo orego_ui_status_pill( $c->post_status ); // phpcs:ignore ?></td>
		<td class="ogu-r"><span class="ogu-acts"><?php echo orego_ui_car_actions( $c, $status ); // phpcs:ignore ?></span></td>
	</tr>
	<?php
}

function orego_ui_car_card( $c, $up, $status ) {
	$d     = orego_car_data( $c->ID );
	$terms = get_the_terms( $c->ID, 'car_type' );
	$tname = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'No type';
	$n     = $up[ $c->ID ] ?? 0;
	?>
	<article class="ogdx-car ogu-card">
		<a class="ogdx-car-img" href="<?php echo esc_url( get_edit_post_link( $c->ID ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo has_post_thumbnail( $c ) ? get_the_post_thumbnail( $c, 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ) : '<span class="ogdx-noimg">' . orego_ui_ic( 'car', 40 ) . '</span>'; // phpcs:ignore ?></a>
		<label class="ogu-card-cb"><input type="checkbox" name="ids[]" value="<?php echo (int) $c->ID; ?>" aria-label="Select <?php echo esc_attr( $c->post_title ); ?>"></label>
		<span class="ogu-card-st"><?php echo orego_ui_status_pill( $c->post_status ); // phpcs:ignore ?></span>
		<div class="ogdx-car-body">
			<div class="ogdx-car-top"><h3><a href="<?php echo esc_url( get_edit_post_link( $c->ID ) ); ?>"><?php echo esc_html( $c->post_title ? $c->post_title : '(no name)' ); ?></a></h3><?php if ( '' !== $d['rating'] ) : ?><span class="ogu-rating"><?php echo orego_ui_ic( 'star', 14 ); // phpcs:ignore ?><?php echo esc_html( number_format( (float) $d['rating'], 1 ) ); ?></span><?php endif; ?></div>
			<dl class="ogdx-specs"><div><dt>Type:</dt><dd><?php echo esc_html( $tname ); ?></dd></div><div><dt>Gear:</dt><dd><?php echo esc_html( orego_dash_en( 'trans', $d['trans'] ) ); ?></dd></div><div><dt>Fuel:</dt><dd><?php echo esc_html( orego_dash_en( 'fuel', $d['fuel'] ) ); ?></dd></div></dl>
			<div class="ogdx-car-foot"><b><?php echo '' === $d['price'] ? '<span class="ogdx-warn">No price</span>' : esc_html( orego_dash_money( $d['price'] ) ) . '<small> /day</small>'; // phpcs:ignore ?></b><span class="ogdx-next"><?php echo $n ? (int) $n . ' upcoming' : 'No bookings'; ?></span></div>
			<span class="ogu-acts ogu-acts-row"><?php echo orego_ui_car_actions( $c, $status ); // phpcs:ignore ?></span>
		</div>
	</article>
	<?php
}

/* ------------------------------------------------------------------ */
/* Bookings                                                            */
/* ------------------------------------------------------------------ */

function orego_ui_bk_act_url( $id, $do ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=orego_bk_act&do=' . $do . '&id=' . (int) $id ), 'orego_bk_act_' . $do . '_' . $id );
}

function orego_ui_bk_do( $id, $do ) {
	if ( 'orego_booking' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		return false;
	}
	if ( isset( orego_bk_statuses()[ $do ] ) ) {
		return orego_bk_set_status( $id, $do );
	}
	if ( 'trash' === $do ) {
		return (bool) wp_trash_post( $id );
	}
	if ( 'restore' === $do ) {
		$ok = wp_untrash_post( $id );
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		return (bool) $ok;
	}
	if ( 'delete' === $do && current_user_can( 'delete_post', $id ) ) {
		return (bool) wp_delete_post( $id, true );
	}
	return false;
}

function orego_ui_bk_word( $do ) {
	return array( 'trash' => 'trashed', 'restore' => 'untrashed', 'delete' => 'deleted' )[ $do ] ?? $do;
}

add_action( 'admin_post_orego_bk_act', function () {
	$id = absint( $_GET['id'] ?? 0 ); // phpcs:ignore
	$do = sanitize_key( $_GET['do'] ?? '' ); // phpcs:ignore
	check_admin_referer( 'orego_bk_act_' . $do . '_' . $id );
	$ok   = orego_ui_bk_do( $id, $do );
	$back = remove_query_arg( array( 'og_done' ), wp_get_referer() ? wp_get_referer() : orego_ui_url( 'orego-bookings' ) );
	if ( in_array( $do, array( 'trash', 'delete' ), true ) ) {
		$back = remove_query_arg( 'booking', $back );
	}
	wp_safe_redirect( add_query_arg( 'og_done', $ok ? orego_ui_bk_word( $do ) . ':1' : 'none', $back ) );
	exit;
} );

add_action( 'admin_post_orego_bk_bulk', function () {
	check_admin_referer( 'orego_bk_bulk' );
	$do  = sanitize_key( $_POST['do'] ?? '' );
	$n   = 0;
	foreach ( array_filter( array_map( 'absint', (array) ( $_POST['ids'] ?? array() ) ) ) as $id ) {
		if ( orego_ui_bk_do( $id, $do ) ) {
			$n++;
		}
	}
	$back = remove_query_arg( array( 'og_done' ), wp_get_referer() ? wp_get_referer() : orego_ui_url( 'orego-bookings' ) );
	wp_safe_redirect( add_query_arg( 'og_done', $n ? orego_ui_bk_word( $do ) . ':' . $n : 'none', $back ) );
	exit;
} );

add_action( 'admin_post_orego_bk_save', function () {
	$id = absint( $_POST['id'] ?? 0 );
	check_admin_referer( 'orego_bk_save_' . $id );
	if ( 'orego_booking' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Not allowed.' );
	}
	update_post_meta( $id, 'og_admin_note', sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ) );
	$st = sanitize_key( $_POST['status'] ?? '' );
	if ( isset( orego_bk_statuses()[ $st ] ) ) {
		orego_bk_set_status( $id, $st, ! empty( $_POST['notify'] ) );
	}
	wp_safe_redirect( orego_ui_url( 'orego-bookings', array( 'booking' => $id, 'og_done' => 'saved:1' ) ) );
	exit;
} );

function orego_ui_bk_actions( $b, $trash = false ) {
	$h = '';
	if ( $trash ) {
		$h .= '<a href="' . esc_url( orego_ui_bk_act_url( $b['id'], 'restore' ) ) . '" title="Restore" aria-label="Restore">' . orego_ui_ic( 'restore' ) . '</a>';
		$h .= '<a href="' . esc_url( orego_ui_bk_act_url( $b['id'], 'delete' ) ) . '" class="is-danger" title="Delete permanently" aria-label="Delete permanently" data-confirm="Delete this booking permanently?">' . orego_ui_ic( 'trash' ) . '</a>';
		return $h;
	}
	if ( 'pending' === $b['status'] ) {
		$h .= '<a href="' . esc_url( orego_ui_bk_act_url( $b['id'], 'confirmed' ) ) . '" class="is-go" title="Approve (emails the customer)" aria-label="Approve">' . orego_ui_ic( 'check' ) . '</a>';
		$h .= '<a href="' . esc_url( orego_ui_bk_act_url( $b['id'], 'declined' ) ) . '" class="is-danger" title="Decline (emails the customer)" aria-label="Decline" data-confirm="Decline this request? The customer will be emailed.">' . orego_ui_ic( 'x' ) . '</a>';
	} elseif ( 'onroad' === $b['status'] ) {
		$h .= '<a href="' . esc_url( orego_ui_bk_act_url( $b['id'], 'completed' ) ) . '" class="is-go" title="Mark as returned" aria-label="Mark as returned">' . orego_ui_ic( 'flag' ) . '</a>';
	} elseif ( 'confirmed' === $b['status'] ) {
		$h .= '<a href="' . esc_url( orego_ui_bk_act_url( $b['id'], 'onroad' ) ) . '" class="is-go" title="Mark as on road (car picked up)" aria-label="Mark as on road">' . orego_ui_ic( 'key' ) . '</a>';
		$h .= '<a href="' . esc_url( orego_ui_bk_act_url( $b['id'], 'completed' ) ) . '" class="is-go" title="Mark as returned" aria-label="Mark as returned">' . orego_ui_ic( 'flag' ) . '</a>';
		$h .= '<a href="' . esc_url( orego_ui_bk_act_url( $b['id'], 'cancelled' ) ) . '" class="is-danger" title="Cancel (emails the customer)" aria-label="Cancel booking" data-confirm="Cancel this confirmed booking? The customer will be emailed.">' . orego_ui_ic( 'x' ) . '</a>';
	}
	$h .= '<a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $b['phone'] ) ) . '" title="Call ' . esc_attr( $b['phone'] ) . '" aria-label="Call">' . orego_ui_ic( 'phone' ) . '</a>';
	$h .= '<a href="' . esc_url( orego_ui_url( 'orego-bookings', array( 'booking' => $b['id'] ) ) ) . '" title="Open booking" aria-label="Open booking">' . orego_ui_ic( 'eye' ) . '</a>';
	return $h;
}

function orego_ui_bookings() {
	if ( ! empty( $_GET['booking'] ) ) { // phpcs:ignore
		orego_ui_booking_view( absint( $_GET['booking'] ) ); // phpcs:ignore
		return;
	}
	$status = sanitize_key( $_GET['status'] ?? 'all' ); // phpcs:ignore
	$st_all = orego_bk_statuses();
	$status = ( 'all' === $status || 'trash' === $status || isset( $st_all[ $status ] ) ) ? $status : 'all';
	$s      = trim( sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ) ); // phpcs:ignore
	$car    = absint( $_GET['car'] ?? 0 ); // phpcs:ignore
	$when   = sanitize_key( $_GET['when'] ?? '' ); // phpcs:ignore
	$sort   = sanitize_key( $_GET['sort'] ?? 'newest' ); // phpcs:ignore
	$paged  = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore
	$today  = current_time( 'Y-m-d' );
	$month  = current_time( 'Y-m' );
	$all    = orego_dash_bookings();
	$trash  = array();
	foreach ( get_posts( array( 'post_type' => 'orego_booking', 'post_status' => 'trash', 'numberposts' => -1 ) ) as $p ) {
		$i = orego_bk_info( $p->ID );
		$i['id'] = $p->ID;
		$i['created'] = $p->post_date;
		$trash[] = $i;
	}
	$counts = array( 'all' => count( $all ) );
	foreach ( $all as $b ) {
		$counts[ $b['status'] ] = ( $counts[ $b['status'] ] ?? 0 ) + 1;
	}
	$list = 'trash' === $status ? $trash : $all;
	$list = array_values( array_filter( $list, function ( $b ) use ( $status, $s, $car, $when, $today, $month ) {
		if ( ! in_array( $status, array( 'all', 'trash' ), true ) && $b['status'] !== $status ) {
			return false;
		}
		if ( $car && (int) $b['car'] !== $car ) {
			return false;
		}
		if ( 'upcoming' === $when && ! ( $b['start'] > $today ) ) {
			return false;
		}
		if ( 'active' === $when && ! ( $b['start'] <= $today && $b['end'] > $today ) ) {
			return false;
		}
		if ( 'past' === $when && ! ( $b['end'] <= $today ) ) {
			return false;
		}
		if ( 'month' === $when && substr( $b['start'], 0, 7 ) !== $month ) {
			return false;
		}
		if ( '' !== $s ) {
			$hay = mb_strtolower( '#' . $b['id'] . ' ' . $b['id'] . ' ' . $b['name'] . ' ' . $b['email'] . ' ' . $b['phone'] . ' ' . get_the_title( $b['car'] ) );
			if ( false === mb_strpos( $hay, mb_strtolower( $s ) ) && false === strpos( preg_replace( '/\D/', '', $b['phone'] ), preg_replace( '/\D/', '', $s ) ?: 'x' ) ) {
				return false;
			}
		}
		return true;
	} ) );
	if ( 'pickup' === $sort ) {
		usort( $list, fn( $a, $b ) => strcmp( $a['start'], $b['start'] ) );
	} elseif ( 'total' === $sort ) {
		usort( $list, fn( $a, $b ) => $b['total'] <=> $a['total'] );
	} else {
		$sort = 'newest';
	}
	$per   = 20;
	$pages = max( 1, (int) ceil( count( $list ) / $per ) );
	$paged = min( $paged, $pages );
	$rows  = array_slice( $list, ( $paged - 1 ) * $per, $per );
	$up    = array_filter( $all, fn( $b ) => 'confirmed' === $b['status'] && $b['start'] > $today );
	$act   = array_filter( $all, fn( $b ) => 'onroad' === $b['status'] || ( 'confirmed' === $b['status'] && $b['start'] <= $today && $b['end'] > $today ) );
	$rev   = array_sum( array_map( fn( $b ) => $b['total'], array_filter( $all, fn( $b ) => in_array( $b['status'], array( 'confirmed', 'onroad', 'completed' ), true ) ) ) );
	$base  = orego_ui_url( 'orego-bookings', array_filter( array( 'status' => 'all' === $status ? '' : $status, 's' => $s, 'car' => $car ? $car : '', 'when' => $when, 'sort' => 'newest' === $sort ? '' : $sort ) ) );
	$exp   = wp_nonce_url( admin_url( 'admin-post.php?action=orego_export&what=bookings' ), 'orego_export' );
	orego_ui_head( 'Bookings', count( $all ) . ' ' . ( 1 === count( $all ) ? 'request' : 'requests' ) . ' received · payment is collected at pickup', '<a class="ogdx-btn is-soft" href="' . esc_url( $exp ) . '">' . orego_ui_ic( 'export', 17 ) . 'Export CSV</a>' );
	$kpis = array(
		array( 'inbox', 'Waiting for you', $counts['pending'] ?? 0, 'amber', orego_ui_url( 'orego-bookings', array( 'status' => 'pending' ) ) ),
		array( 'key', 'On rental now', count( $act ), 'green', orego_ui_url( 'orego-bookings', array( 'status' => 'confirmed', 'when' => 'active' ) ) ),
		array( 'cal', 'Upcoming confirmed', count( $up ), 'blue', orego_ui_url( 'orego-bookings', array( 'status' => 'confirmed', 'when' => 'upcoming' ) ) ),
		array( 'money', 'Booked revenue', orego_bk_money( $rev ), 'violet', orego_dash_url( array( 'tab' => 'stats' ) ) ),
	);
	?>
	<section class="ogdx-kpis">
		<?php foreach ( $kpis as $k ) : ?><a class="ogdx-kpi is-<?php echo esc_attr( $k[3] ); ?>" href="<?php echo esc_url( $k[4] ); ?>"><span class="ogdx-kpi-ic"><?php echo orego_ui_ic( $k[0], 20 ); // phpcs:ignore ?></span><span><small><?php echo esc_html( $k[1] ); ?></small><b><?php echo esc_html( $k[2] ); ?></b></span></a><?php endforeach; ?>
	</section>
	<nav class="ogu-tabs" aria-label="Status">
		<a href="<?php echo esc_url( orego_ui_url( 'orego-bookings' ) ); ?>" class="<?php echo 'all' === $status ? 'is-on' : ''; ?>">All<em><?php echo (int) $counts['all']; ?></em></a>
		<?php foreach ( $st_all as $k => $v ) : ?><a href="<?php echo esc_url( orego_ui_url( 'orego-bookings', array( 'status' => $k ) ) ); ?>" class="<?php echo $status === $k ? 'is-on' : ''; ?>"><i class="ogu-dot is-<?php echo esc_attr( $k ); ?>"></i><?php echo esc_html( orego_dash_en( 'st', $k ) ); ?><em><?php echo (int) ( $counts[ $k ] ?? 0 ); ?></em></a><?php endforeach; ?>
		<?php if ( $trash ) : ?><a href="<?php echo esc_url( orego_ui_url( 'orego-bookings', array( 'status' => 'trash' ) ) ); ?>" class="<?php echo 'trash' === $status ? 'is-on' : ''; ?>">Trash<em><?php echo count( $trash ); ?></em></a><?php endif; ?>
	</nav>
	<form class="ogu-bar" method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
		<input type="hidden" name="post_type" value="orego_car"><input type="hidden" name="page" value="orego-bookings">
		<?php if ( 'all' !== $status ) : ?><input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>"><?php endif; ?>
		<label class="ogu-search"><?php echo orego_ui_ic( 'search', 17 ); // phpcs:ignore ?><span class="screen-reader-text">Search bookings</span><input type="search" name="s" value="<?php echo esc_attr( $s ); ?>" placeholder="Name, email, phone or booking #…"></label>
		<select name="car" aria-label="Car" data-autosubmit><option value="">All cars</option><?php foreach ( orego_dash_cars() as $c ) : ?><option value="<?php echo (int) $c->ID; ?>"<?php selected( $car, $c->ID ); ?>><?php echo esc_html( $c->post_title ); ?></option><?php endforeach; ?></select>
		<select name="when" aria-label="Period" data-autosubmit><?php foreach ( array( '' => 'Any time', 'upcoming' => 'Upcoming', 'active' => 'On rental now', 'past' => 'Past', 'month' => 'Pickup this month' ) as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $when, $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select>
		<select name="sort" aria-label="Sort" data-autosubmit><?php foreach ( array( 'newest' => 'Newest requests', 'pickup' => 'Pickup date', 'total' => 'Highest total' ) as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $sort, $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ogu-bulkform">
		<input type="hidden" name="action" value="orego_bk_bulk"><?php wp_nonce_field( 'orego_bk_bulk' ); ?>
		<div class="ogu-bulk" hidden>
			<span><b class="ogu-sel-n">0</b> selected</span>
			<select name="do" aria-label="Bulk action"><?php if ( 'trash' === $status ) : ?><option value="restore">Restore</option><option value="delete">Delete permanently</option><?php else : ?><option value="confirmed">Approve (emails customers)</option><option value="onroad">Mark on road</option><option value="declined">Decline (emails customers)</option><option value="completed">Mark returned</option><option value="cancelled">Cancel (emails customers)</option><option value="trash">Move to trash</option><?php endif; ?></select>
			<button type="submit" class="ogdx-btn" data-confirm-delete>Apply</button>
			<button type="button" class="ogu-plain ogu-clear">Clear</button>
		</div>
	<?php if ( ! $rows ) : ?>
		<section class="ogdx-card"><div class="ogdx-empty"><?php echo orego_ui_ic( 'inbox', 40 ); // phpcs:ignore ?><b><?php echo ( $s || $car || $when ) ? 'No bookings match' : ( 'trash' === $status ? 'Trash is empty' : 'No bookings here yet' ); ?></b><span><?php echo ( $s || $car || $when ) ? 'Try another search or clear the filters.' : 'Requests from the “احجز رحلتك” page land here, waiting for your approval.'; ?></span><?php if ( $s || $car || $when ) : ?><a class="ogdx-btn is-soft" href="<?php echo esc_url( orego_ui_url( 'orego-bookings', array_filter( array( 'status' => 'all' === $status ? '' : $status ) ) ) ); ?>">Clear filters</a><?php endif; ?></div></section>
	<?php else : ?>
		<section class="ogdx-card ogu-tablecard">
		<div class="ogu-tablewrap"><table class="ogu-table">
			<thead><tr><th class="ogu-cb"><input type="checkbox" class="ogu-all" aria-label="Select all"></th><th>Booking</th><th>Customer</th><th>Car</th><th>Trip</th><th>Total</th><th>Status</th><th class="ogu-r"><span class="screen-reader-text">Actions</span></th></tr></thead>
			<tbody>
			<?php foreach ( $rows as $b ) : $open = orego_ui_url( 'orego-bookings', array( 'booking' => $b['id'] ) ); ?>
			<tr class="<?php echo 'pending' === $b['status'] && 'trash' !== $status ? 'is-hot' : ''; ?>">
				<td class="ogu-cb"><input type="checkbox" name="ids[]" value="<?php echo (int) $b['id']; ?>" aria-label="Select booking <?php echo (int) $b['id']; ?>"></td>
				<td><a class="ogu-ref" href="<?php echo esc_url( $open ); ?>">#<?php echo (int) $b['id']; ?></a><small class="ogu-sub"><?php echo esc_html( orego_ui_ago( $b['created'] ) ); ?></small></td>
				<td><span class="ogu-person"><span class="ogdx-avatar"><?php echo esc_html( mb_strtoupper( mb_substr( trim( $b['name'] ), 0, 1 ) ) ); ?></span><span><b><?php echo esc_html( $b['name'] ); ?></b><small dir="ltr"><?php echo esc_html( $b['phone'] ); ?></small></span></span></td>
				<td><span class="ogu-carcell"><span class="ogu-thumb is-sm"><?php echo has_post_thumbnail( $b['car'] ) ? get_the_post_thumbnail( $b['car'], 'thumbnail', array( 'alt' => '' ) ) : orego_ui_ic( 'car', 18 ); // phpcs:ignore ?></span><span><b><?php echo esc_html( get_the_title( $b['car'] ) ); ?></b></span></span></td>
				<td><b class="ogu-dates"><?php echo esc_html( orego_dash_date( $b['start'] ) . ' → ' . orego_dash_date( $b['end'] ) ); ?></b><small class="ogu-sub"><?php echo esc_html( $b['days'] . ( 1 === $b['days'] ? ' day' : ' days' ) . ' · ' . $b['pickup'] ); ?></small><?php echo function_exists( 'orego_onroad_chip' ) ? orego_onroad_chip( $b ) : ''; // phpcs:ignore ?></td>
				<td><b class="ogu-price"><?php echo esc_html( orego_bk_money( $b['total'], $b['id'] ?? ( $id ?? null ) ) ); ?></b></td>
				<td><i class="ogdx-st is-<?php echo esc_attr( $b['status'] ); ?>"><?php echo esc_html( orego_dash_en( 'st', $b['status'] ) ); ?></i></td>
				<td class="ogu-r"><span class="ogu-acts"><?php echo orego_ui_bk_actions( $b, 'trash' === $status ); // phpcs:ignore ?></span></td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
		</section>
	<?php endif; ?>
	</form>
	<?php
	echo orego_ui_pager( $paged, $pages, $base ); // phpcs:ignore
	orego_ui_foot();
}

function orego_ui_booking_view( $id ) {
	if ( 'orego_booking' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		orego_ui_head( 'Booking not found', 'It may have been deleted.' );
		echo '<a class="ogdx-btn is-soft" href="' . esc_url( orego_ui_url( 'orego-bookings' ) ) . '">Back to bookings</a>';
		orego_ui_foot();
		return;
	}
	$b       = orego_bk_info( $id );
	$p       = get_post( $id );
	$trashed = 'trash' === $p->post_status;
	$car     = get_post( $b['car'] );
	$cd      = $car ? orego_car_data( $car->ID ) : null;
	$others  = array_filter( orego_dash_bookings(), fn( $x ) => $x['id'] !== $id && '' !== $b['email'] && strtolower( $x['email'] ) === strtolower( $b['email'] ) );
	$back    = '<a class="ogdx-btn is-soft" href="' . esc_url( orego_ui_url( 'orego-bookings' ) ) . '">' . orego_ui_ic( 'back', 17 ) . 'All bookings</a>';
	orego_ui_head( 'Booking #' . $id, 'Requested ' . orego_ui_ago( $p->post_date ) . ' · ' . wp_date( 'M j, Y \a\t H:i', strtotime( get_gmt_from_date( $p->post_date ) . ' UTC' ) ), $back );
	?>
	<div class="ogu-detail">
		<div class="ogu-detail-main">
			<section class="ogdx-card">
				<div class="ogdx-card-h"><h2>Trip</h2><i class="ogdx-st is-<?php echo esc_attr( $b['status'] ); ?>"><?php echo esc_html( $trashed ? 'In trash' : orego_dash_en( 'st', $b['status'] ) ); ?></i></div>
				<?php if ( $car ) : ?>
				<a class="ogu-tripcar" href="<?php echo esc_url( get_edit_post_link( $car->ID ) ); ?>"><span class="ogu-thumb is-lg"><?php echo has_post_thumbnail( $car ) ? get_the_post_thumbnail( $car, 'medium', array( 'alt' => '' ) ) : orego_ui_ic( 'car', 28 ); // phpcs:ignore ?></span><span><b><?php echo esc_html( $car->post_title ); ?></b><small><?php echo esc_html( $cd['seats'] . ' seats · ' . orego_dash_en( 'trans', $cd['trans'] ) . ' · ' . orego_dash_en( 'fuel', $cd['fuel'] ) ); ?></small></span></a>
				<?php endif; ?>
				<div class="ogu-timeline">
					<div><i></i><small>Pick up</small><b><?php echo esc_html( orego_dash_date( $b['start'], 'l, M j, Y' ) ); ?> · <?php echo esc_html( $b['ptime'] ); ?></b><span><?php echo orego_ui_ic( 'pin', 14 ); // phpcs:ignore ?><?php echo esc_html( $b['pickup'] ); ?></span></div>
					<div><i class="is-end"></i><small>Return</small><b><?php echo esc_html( orego_dash_date( $b['end'], 'l, M j, Y' ) ); ?> · <?php echo esc_html( $b['rtime'] ); ?></b><span><?php echo orego_ui_ic( 'pin', 14 ); // phpcs:ignore ?><?php echo esc_html( $b['dropoff'] ); ?></span></div>
				</div>
				<?php if ( function_exists( 'orego_bk_bill_html' ) ) : ?>
				<?php echo orego_bk_bill_html( $id ); // phpcs:ignore ?>
				<?php else : ?>
				<dl class="ogu-bill">
					<div><dt><?php echo esc_html( $b['days'] . ( 1 === $b['days'] ? ' day' : ' days' ) . ' × ' . orego_bk_money( $b['pday'], $b['id'] ?? ( $id ?? null ) ) ); ?></dt><dd><?php echo esc_html( orego_bk_money( $b['total'], $b['id'] ?? ( $id ?? null ) ) ); ?></dd></div>
					<div><dt>Delivery</dt><dd class="is-free">Free</dd></div>
					<div class="is-total"><dt>Total, paid at pickup</dt><dd><?php echo esc_html( orego_bk_money( $b['total'], $b['id'] ?? ( $id ?? null ) ) ); ?></dd></div>
				</dl>
				<?php endif; ?>
			</section>
			<section class="ogdx-card">
				<div class="ogdx-card-h"><h2>Customer</h2></div>
				<div class="ogdx-person"><span class="ogdx-avatar"><?php echo esc_html( mb_strtoupper( mb_substr( trim( $b['name'] ), 0, 1 ) ) ); ?></span><span><b><?php echo esc_html( $b['name'] ); ?></b><small><?php echo esc_html( $b['email'] ); ?></small></span></div>
				<div class="ogu-contact">
					<a class="ogdx-btn is-soft" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $b['phone'] ) ); ?>"><?php echo orego_ui_ic( 'phone', 16 ); // phpcs:ignore ?><span dir="ltr"><?php echo esc_html( $b['phone'] ); ?></span></a>
					<a class="ogdx-btn is-soft" href="mailto:<?php echo esc_attr( $b['email'] ); ?>?subject=<?php echo rawurlencode( 'Booking #' . $id ); ?>"><?php echo orego_ui_ic( 'mail', 16 ); // phpcs:ignore ?>Email</a>
					<a class="ogdx-btn is-soft" href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D/', '', $b['phone'] ) ); ?>" target="_blank" rel="noopener">WhatsApp</a>
				</div>
				<?php if ( $b['notes'] ) : ?><p class="ogdx-note">“<?php echo esc_html( $b['notes'] ); ?>”</p><?php endif; ?>
				<?php if ( $others ) : ?>
				<h3 class="ogu-h3">Other bookings by this customer</h3>
				<ul class="ogu-mini">
					<?php foreach ( array_slice( $others, 0, 6 ) as $o ) : ?><li><a href="<?php echo esc_url( orego_ui_url( 'orego-bookings', array( 'booking' => $o['id'] ) ) ); ?>"><b>#<?php echo (int) $o['id']; ?></b><span><?php echo esc_html( get_the_title( $o['car'] ) . ' · ' . orego_dash_date( $o['start'] ) ); ?></span><i class="ogdx-st is-<?php echo esc_attr( $o['status'] ); ?>"><?php echo esc_html( orego_dash_en( 'st', $o['status'] ) ); ?></i></a></li><?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</section>
			<?php if ( ! $trashed ) { do_action( 'orego_booking_view_main', $id, $b ); } ?>
		</div>
		<aside class="ogu-detail-side">
			<?php if ( $trashed ) : ?>
			<section class="ogdx-card"><div class="ogdx-card-h"><h2>In trash</h2></div><p class="ogu-mute">Restore it to manage this booking again.</p><div class="ogu-contact"><a class="ogdx-btn" href="<?php echo esc_url( orego_ui_bk_act_url( $id, 'restore' ) ); ?>">Restore</a><a class="ogdx-btn is-no" href="<?php echo esc_url( orego_ui_bk_act_url( $id, 'delete' ) ); ?>" data-confirm="Delete this booking permanently?">Delete</a></div></section>
			<?php else : ?>
			<?php do_action( 'orego_booking_view_side', $id, $b ); ?>
			<?php if ( in_array( $b['status'], array( 'pending', 'confirmed', 'onroad' ), true ) ) : ?>
			<section class="ogdx-card ogu-quick">
				<?php if ( 'pending' === $b['status'] ) : ?>
					<p>This request is waiting for your answer.</p>
					<a class="ogdx-btn is-ok" href="<?php echo esc_url( orego_ui_bk_act_url( $id, 'confirmed' ) ); ?>"><?php echo orego_ui_ic( 'check', 17 ); // phpcs:ignore ?>Approve booking</a>
					<a class="ogdx-btn is-no" href="<?php echo esc_url( orego_ui_bk_act_url( $id, 'declined' ) ); ?>" data-confirm="Decline this request? The customer will be emailed."><?php echo orego_ui_ic( 'x', 17 ); // phpcs:ignore ?>Decline</a>
				<?php elseif ( 'confirmed' === $b['status'] ) : ?>
					<p>Confirmed. Mark it on road when the customer picks up the car.</p>
					<a class="ogdx-btn is-ok" href="<?php echo esc_url( orego_ui_bk_act_url( $id, 'onroad' ) ); ?>"><?php echo orego_ui_ic( 'key', 17 ); // phpcs:ignore ?>Mark as on road</a>
				<?php else : ?>
					<p>On road. Mark it returned once the car is back.</p>
					<a class="ogdx-btn" href="<?php echo esc_url( orego_ui_bk_act_url( $id, 'completed' ) ); ?>"><?php echo orego_ui_ic( 'flag', 17 ); // phpcs:ignore ?>Mark as returned</a>
				<?php endif; ?>
			</section>
			<?php endif; ?>
			<form class="ogdx-card ogu-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="orego_bk_save"><input type="hidden" name="id" value="<?php echo (int) $id; ?>"><?php wp_nonce_field( 'orego_bk_save_' . $id ); ?>
				<div class="ogdx-card-h"><h2>Status & message</h2></div>
				<label><span>Status</span><select name="status"><?php foreach ( orego_bk_statuses() as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $b['status'], $k ); ?>><?php echo esc_html( orego_dash_en( 'st', $k ) ); ?></option><?php endforeach; ?></select></label>
				<label><span>Message to the customer (optional)</span><textarea name="note" rows="4" placeholder="Added to the confirmation or decline email"><?php echo esc_textarea( $b['anote'] ); ?></textarea></label>
				<label class="ogu-check"><input type="checkbox" name="notify" value="1" checked> Email the customer when the status changes</label>
				<button type="submit" class="ogdx-btn">Save changes</button>
			</form>
			<section class="ogdx-card ogu-meta">
				<dl>
					<div><dt>Booking</dt><dd>#<?php echo (int) $id; ?></dd></div>
					<div><dt>Payment</dt><dd><?php echo esc_html( function_exists( 'orego_pay_summary' ) ? orego_pay_summary( $id )['label'] : 'At pickup, cash or card' ); ?></dd></div>
					<div><dt>Car page</dt><dd><?php if ( $car ) : ?><a href="<?php echo esc_url( get_permalink( $car ) ); ?>" target="_blank" rel="noopener">Open</a><?php else : ?>–<?php endif; ?></dd></div>
				</dl>
				<a class="ogu-trashlink" href="<?php echo esc_url( orego_ui_bk_act_url( $id, 'trash' ) ); ?>"><?php echo orego_ui_ic( 'trash', 15 ); // phpcs:ignore ?>Move to trash</a>
			</section>
			<?php endif; ?>
		</aside>
	</div>
	<?php
	orego_ui_foot();
}

/* ------------------------------------------------------------------ */
/* Pickup locations                                                    */
/* ------------------------------------------------------------------ */

add_action( 'admin_post_orego_locs_save', function () {
	check_admin_referer( 'orego_locs_save' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	$list = array_values( array_unique( array_filter( array_map( function ( $v ) {
		return trim( sanitize_text_field( $v ) );
	}, (array) wp_unslash( $_POST['loc'] ?? array() ) ) ) ) );
	update_option( 'orego_locations', $list, false );
	wp_safe_redirect( orego_ui_url( 'orego-places', array( 'og_done' => 'saved:1' ) ) );
	exit;
} );

function orego_ui_locations() {
	$locs = orego_locations();
	$use  = array();
	foreach ( orego_dash_bookings() as $b ) {
		$use[ $b['pickup'] ]  = ( $use[ $b['pickup'] ] ?? 0 ) + 1;
		if ( $b['dropoff'] !== $b['pickup'] ) {
			$use[ $b['dropoff'] ] = ( $use[ $b['dropoff'] ] ?? 0 ) + 1;
		}
	}
	orego_ui_head( 'Pickup Locations', 'Where customers can collect and return cars. Shown on the “Book your trip” booking page.' );
	?>
	<form class="ogdx-card ogu-locs" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="orego_locs_save"><?php wp_nonce_field( 'orego_locs_save' ); ?>
		<div class="ogdx-card-h"><div><h2>Locations</h2><p>The first one is suggested first. Use the arrows to change the order.</p></div><button type="button" class="ogdx-ghost ogu-addloc"><?php echo orego_ui_ic( 'plus', 16 ); // phpcs:ignore ?>Add location</button></div>
		<ol class="ogu-loclist">
			<?php foreach ( $locs as $l ) : ?>
			<li><span class="ogu-locic"><?php echo orego_ui_ic( 'pin', 18 ); // phpcs:ignore ?></span><input type="text" name="loc[]" value="<?php echo esc_attr( $l ); ?>" aria-label="Location name" required><span class="ogu-uses"><?php echo (int) ( $use[ $l ] ?? 0 ); ?> bookings</span><span class="ogu-acts"><button type="button" class="ogu-mv" data-dir="-1" aria-label="Move up"><?php echo orego_ui_ic( 'up' ); // phpcs:ignore ?></button><button type="button" class="ogu-mv" data-dir="1" aria-label="Move down"><?php echo orego_ui_ic( 'dn' ); // phpcs:ignore ?></button><button type="button" class="ogu-rm is-danger" aria-label="Remove location"><?php echo orego_ui_ic( 'trash' ); // phpcs:ignore ?></button></span></li>
			<?php endforeach; ?>
		</ol>
		<template id="ogu-loc-tpl"><li><span class="ogu-locic"><?php echo orego_ui_ic( 'pin', 18 ); // phpcs:ignore ?></span><input type="text" name="loc[]" value="" aria-label="Location name" placeholder="e.g. Agadir Al Massira Airport" required><span class="ogu-uses">New</span><span class="ogu-acts"><button type="button" class="ogu-mv" data-dir="-1" aria-label="Move up"><?php echo orego_ui_ic( 'up' ); // phpcs:ignore ?></button><button type="button" class="ogu-mv" data-dir="1" aria-label="Move down"><?php echo orego_ui_ic( 'dn' ); // phpcs:ignore ?></button><button type="button" class="ogu-rm is-danger" aria-label="Remove location"><?php echo orego_ui_ic( 'trash' ); // phpcs:ignore ?></button></span></li></template>
		<p class="ogu-mute ogu-locnote">Renaming a location doesn’t change bookings already made with the old name.</p>
		<div class="ogu-formfoot"><button type="submit" class="ogdx-btn">Save locations</button></div>
	</form>
	<?php
	orego_ui_foot();
}

/* ------------------------------------------------------------------ */
/* Native screens: car editor + car types get the same look            */
/* ------------------------------------------------------------------ */

add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s ) {
		return;
	}
	$is_car_edit = 'orego_car' === $s->post_type && 'post' === $s->base;
	$is_types    = 'edit-tags' === $s->base && 'car_type' === $s->taxonomy || 'term' === $s->base && 'car_type' === $s->taxonomy;
	if ( ! $is_car_edit && ! $is_types ) {
		return;
	}
	echo '<style>' . orego_ui_native_css() . '</style>'; // phpcs:ignore
	if ( $is_car_edit ) {
		echo '<script>document.addEventListener("DOMContentLoaded",function(){var h=document.querySelector(".wrap h1.wp-heading-inline");if(h){var a=document.createElement("a");a.href=' . wp_json_encode( orego_ui_url( 'orego-listing' ) ) . ';a.className="ogu-backlink";a.textContent=' . wp_json_encode( orego_L( '← All cars', '→ كل السيارات' ) ) . ';h.parentNode.insertBefore(a,h);}});</script>';
	}
} );

/* ------------------------------------------------------------------ */
/* Assets                                                              */
/* ------------------------------------------------------------------ */

function orego_ui_js() {
	return <<<'JS'
(function(){
[].forEach.call(document.querySelectorAll('[data-autosubmit]'),function(s){s.addEventListener('change',function(){s.form.submit()})});
[].forEach.call(document.querySelectorAll('[data-confirm]'),function(a){a.addEventListener('click',function(e){if(!confirm(a.getAttribute('data-confirm')))e.preventDefault()})});
[].forEach.call(document.querySelectorAll('.ogu-bulkform'),function(f){
 var bar=f.querySelector('.ogu-bulk'),n=f.querySelector('.ogu-sel-n'),all=f.querySelector('.ogu-all');
 function boxes(){return [].slice.call(f.querySelectorAll('input[name="ids[]"]'))}
 function upd(){var c=boxes().filter(function(b){return b.checked}).length;n.textContent=c;bar.hidden=!c;if(all){all.checked=c&&c===boxes().length;all.indeterminate=c&&c<boxes().length}
  boxes().forEach(function(b){var r=b.closest('tr,.ogu-card');if(r)r.classList.toggle('is-sel',b.checked)})}
 f.addEventListener('change',function(e){if(e.target===all){boxes().forEach(function(b){b.checked=all.checked})}upd()});
 var cl=f.querySelector('.ogu-clear');if(cl)cl.addEventListener('click',function(){boxes().forEach(function(b){b.checked=false});upd()});
 f.addEventListener('submit',function(e){var d=f.querySelector('select[name="do"]').value;if(d==='delete'&&!confirm('Delete the selected items permanently? This cannot be undone.'))e.preventDefault();if(['declined','cancelled'].indexOf(d)>-1&&!confirm('The selected customers will be emailed. Continue?'))e.preventDefault()});
 upd()});
var list=document.querySelector('.ogu-loclist');
if(list){var tpl=document.getElementById('ogu-loc-tpl');
 document.querySelector('.ogu-addloc').addEventListener('click',function(){var li=tpl.content.firstElementChild.cloneNode(true);list.appendChild(li);li.querySelector('input').focus()});
 list.addEventListener('click',function(e){var b=e.target.closest('button');if(!b)return;var li=b.closest('li');
  if(b.classList.contains('ogu-rm')){if(list.children.length<2){alert('Keep at least one location.');return}li.remove();return}
  if(b.classList.contains('ogu-mv')){var d=+b.dataset.dir;if(d<0&&li.previousElementSibling)list.insertBefore(li,li.previousElementSibling);if(d>0&&li.nextElementSibling)list.insertBefore(li.nextElementSibling,li);b.focus()}})}
var t=document.querySelector('.ogu-toast');if(t){setTimeout(function(){t.classList.add('is-out')},4200);if(history.replaceState){var u=new URL(location.href);u.searchParams.delete('og_done');history.replaceState(null,'',u)}}
})();
JS;
}

function orego_ui_css() {
	return <<<'CSS'
body[class*="orego_car_page_orego-"]{background:#F6F7F9}
body[class*="orego_car_page_orego-"] #wpcontent{padding-inline-start:0}
body[class*="orego_car_page_orego-"] #wpbody-content{padding-bottom:0}
body[class*="orego_car_page_orego-"] #wpfooter{display:none}
.ogdx{display:block}
.ogu-head-acts{display:flex;gap:10px;align-items:center}
.ogdx-head .ogdx-btn{height:44px;padding:0 18px}
.ogu-toast{position:fixed;bottom:24px;inset-inline-end:24px;z-index:9999;display:flex;align-items:center;gap:10px;background:#15171C;color:#FFFFFF;font-size:14px;font-weight:600;padding:13px 18px;border-radius:14px;box-shadow:0 18px 40px rgba(21,23,28,.25);transition:opacity .3s,transform .3s}
.ogu-toast svg{color:#4ADE80}
.ogu-toast.is-out{opacity:0;transform:translateY(10px);pointer-events:none}
.ogu-tabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px}
.ogu-tabs a{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;background:#FFFFFF;border:1px solid var(--ln);color:#374151;font-weight:600;font-size:13.5px}
.ogu-tabs a:hover{border-color:#C9CED6;color:var(--ink)}
.ogu-tabs a.is-on{background:var(--ink);border-color:var(--ink);color:#FFFFFF}
.ogu-tabs em{font-style:normal;font-size:12px;font-weight:700;background:#F1F3F6;color:#4B5563;border-radius:999px;padding:0 7px;line-height:20px}
.ogu-tabs a.is-on em{background:rgba(255,255,255,.18);color:#FFFFFF}
.ogu-dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.ogu-dot.is-pending{background:#F59E0B}.ogu-dot.is-confirmed{background:#22C55E}.ogu-dot.is-completed{background:#3B82F6}.ogu-dot.is-onroad{background:#8B5CF6}.ogu-dot.is-declined{background:#EF4444}.ogu-dot.is-cancelled{background:#9CA3AF}
.ogu-bar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:14px}
.ogu-bar select{height:44px;border:1px solid var(--ln);border-radius:12px;background-color:#FFFFFF;padding:0 34px 0 14px;font-size:14px;color:var(--ink);box-shadow:none;max-width:220px}
.ogu-search{position:relative;flex:1 1 280px;max-width:420px;display:block}
.ogu-search svg{position:absolute;top:50%;transform:translateY(-50%);inset-inline-start:14px;color:var(--su);pointer-events:none}
.ogu-search input{width:100%;height:44px;border:1px solid var(--ln);border-radius:12px;background:#FFFFFF;padding-inline:42px 14px;font-size:14px;box-shadow:none}
.ogu-search input:focus,.ogu-bar select:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(53,99,233,.12);outline:0}
.ogu-views{display:flex;margin-inline-start:auto;background:#FFFFFF;border:1px solid var(--ln);border-radius:12px;padding:3px}
.ogu-views a{width:38px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:var(--mu)}
.ogu-views a.is-on{background:var(--as);color:var(--a)}
.ogu-bulk{position:sticky;top:44px;z-index:5;display:flex;align-items:center;gap:12px;background:#15171C;color:#FFFFFF;border-radius:14px;padding:10px 12px 10px 18px;margin-bottom:12px;box-shadow:0 12px 30px rgba(21,23,28,.18)}
.ogu-bulk[hidden]{display:none}
.ogu-bulk select{height:38px;border-radius:10px;border:0;padding:0 30px 0 12px;font-size:14px}
.ogu-bulk .ogdx-btn{height:38px}
.ogu-plain{background:none;border:0;color:#D1D5DB;font-weight:600;cursor:pointer;margin-inline-start:auto}
.ogu-plain:hover{color:#FFFFFF}
.ogu-tablecard{padding:0;overflow:hidden}
.ogu-tablewrap{overflow-x:auto}
.ogu-table{width:100%;border-collapse:collapse;font-size:14px}
.ogu-table th{text-align:start;font-size:12px;font-weight:600;color:var(--su);text-transform:uppercase;letter-spacing:.04em;padding:14px 14px;background:#FAFBFC;border-bottom:1px solid var(--ln);white-space:nowrap}
.ogu-table td{padding:12px 14px;border-bottom:1px solid #F1F3F6;vertical-align:middle}
.ogu-table tbody tr:last-child td{border-bottom:0}
.ogu-table tbody tr{transition:background .12s}
.ogu-table tbody tr:hover{background:#FAFBFF}
.ogu-table tr.is-sel{background:#F3F6FE}
.ogu-table tr.is-hot td:first-child{box-shadow:inset 3px 0 0 #F59E0B}
.ogu-cb{width:36px}
.ogu-table input[type=checkbox],.ogu-card-cb input{width:18px;height:18px;border-radius:5px;margin:0}
.ogu-r{text-align:end;white-space:nowrap}
.ogu-carcell{display:flex;align-items:center;gap:12px;color:var(--ink);min-width:220px}
.ogu-carcell b{display:block;font-size:14px;font-weight:600}
a.ogu-carcell:hover b{color:var(--a)}
.ogu-carcell small,.ogu-sub{display:block;font-size:12.5px;color:var(--su);margin-top:2px}
.ogu-thumb{width:64px;height:48px;flex:none;border-radius:10px;overflow:hidden;background:#F1F3F6;display:flex;align-items:center;justify-content:center;color:var(--su)}
.ogu-thumb img{width:100%;height:100%;object-fit:cover}
.ogu-thumb.is-sm{width:44px;height:34px;border-radius:8px}
.ogu-thumb.is-lg{width:120px;height:84px;border-radius:12px}
.ogu-price{font-weight:700;color:var(--ink)}
.ogu-mute{color:var(--mu)}
.ogu-warn{color:#B91C1C;font-weight:600;font-size:13px}
.ogu-chip{display:inline-block;background:var(--as);color:var(--a);font-weight:600;font-size:12.5px;border-radius:999px;padding:3px 10px}
.ogu-rating{display:inline-flex;align-items:center;gap:4px;font-weight:600}
.ogu-rating svg{color:#F5B800;fill:#F5B800}
.ogu-acts{display:inline-flex;gap:4px}
.ogu-acts a,.ogu-acts button{width:34px;height:34px;border-radius:9px;border:1px solid transparent;background:transparent;color:#4B5563;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;padding:0}
.ogu-acts a:hover,.ogu-acts button:hover{background:#FFFFFF;border-color:var(--ln);color:var(--a)}
.ogu-acts .is-go:hover{color:#15803D}
.ogu-acts .is-danger:hover{color:#B91C1C;border-color:#FECACA;background:#FEF2F2}
.ogu-ref{font-weight:700;color:var(--ink);font-size:14.5px}
.ogu-ref:hover{color:var(--a)}
.ogu-person{display:flex;align-items:center;gap:10px}
.ogu-person .ogdx-avatar{width:36px;height:36px;font-size:15px}
.ogu-person b{display:block;font-weight:600}
.ogu-person small{display:block;font-size:12.5px;color:var(--su)}
.ogu-dates{font-weight:600;white-space:nowrap}
.ogu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:16px}
.ogu-card{position:relative;background:#FFFFFF}
.ogu-card.is-sel{box-shadow:0 0 0 2px var(--a)}
.ogu-card-cb{position:absolute;top:10px;inset-inline-start:10px;background:rgba(255,255,255,.92);border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;z-index:2}
.ogu-card-st{position:absolute;top:12px;inset-inline-end:12px;z-index:2}
.ogu-acts-row{border-top:1px solid var(--ln);padding-top:10px;justify-content:space-between;display:flex}
.ogu-pager{display:flex;gap:6px;justify-content:center;margin-top:18px}
.ogu-pager a,.ogu-pager b,.ogu-pager span{min-width:38px;height:38px;padding:0 10px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:600}
.ogu-pager a{background:#FFFFFF;border:1px solid var(--ln);color:var(--ink)}
.ogu-pager a:hover{border-color:var(--a);color:var(--a)}
.ogu-pager b{background:var(--ink);color:#FFFFFF}
.ogu-pager span{color:var(--su)}
.ogu-detail{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:18px;align-items:start}
.ogu-detail-main,.ogu-detail-side{display:grid;gap:18px}
.ogu-tripcar{display:flex;align-items:center;gap:14px;padding:12px;border:1px solid var(--ln);border-radius:14px;color:var(--ink);margin-bottom:18px}
.ogu-tripcar b{display:block;font-size:16px}
.ogu-tripcar small{display:block;color:var(--mu);font-size:13px;margin-top:2px}
.ogu-tripcar:hover{border-color:var(--a)}
.ogu-timeline{position:relative;display:grid;gap:22px;padding-inline-start:26px}
.ogu-timeline::before{content:"";position:absolute;inset-inline-start:7px;top:8px;bottom:8px;border-inline-start:2px dashed #C7D2FE}
.ogu-timeline div{position:relative}
.ogu-timeline i{position:absolute;inset-inline-start:-26px;top:3px;width:16px;height:16px;border-radius:50%;background:var(--a);box-shadow:0 0 0 4px var(--as)}
.ogu-timeline i.is-end{border-radius:5px}
.ogu-timeline small{display:block;font-size:12px;color:var(--su);font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.ogu-timeline b{display:block;font-size:15px;margin-top:2px}
.ogu-timeline span{display:flex;align-items:center;gap:6px;color:var(--mu);font-size:13.5px;margin-top:3px}
.ogu-bill{margin-top:22px!important;border-top:1px solid var(--ln);padding-top:6px!important}
.ogu-bill div{display:flex;justify-content:space-between;padding:9px 0;font-size:14px}
.ogu-bill dt{color:var(--mu)}
.ogu-bill dd{font-weight:600}
.ogu-bill dd.is-free{color:#15803D}
.ogu-bill .is-total{border-top:1px solid var(--ln);margin-top:4px;padding-top:12px}
.ogu-bill .is-total dt{color:var(--ink);font-weight:700}
.ogu-bill .is-total dd{font-size:22px;font-weight:800}
.ogu-contact{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.ogu-h3{font-size:14px;font-weight:700;margin:20px 0 10px!important}
.ogu-mini{display:grid;gap:6px}
.ogu-mini a{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--ln);border-radius:12px;color:var(--ink)}
.ogu-mini a:hover{border-color:var(--a)}
.ogu-mini span{flex:1;color:var(--mu);font-size:13px}
.ogu-quick{display:grid;gap:10px}
.ogu-quick p{font-size:14px;color:#374151}
.ogu-quick .ogdx-btn{width:100%;height:46px}
.ogu-form{display:grid;gap:14px}
.ogu-form .ogdx-card-h{margin-bottom:0}
.ogu-form label{display:flex;flex-direction:column;gap:6px;font-size:13px;color:var(--mu);font-weight:500}
.ogu-form select,.ogu-form textarea{width:100%;max-width:none;border:1px solid var(--ln);border-radius:12px;padding:10px 12px;font-size:14px;color:var(--ink);box-shadow:none;background-color:#FFFFFF}
.ogu-form select{height:44px;padding-block:0}
.ogu-form select:focus,.ogu-form textarea:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(53,99,233,.12)}
.ogu-form .ogu-check{flex-direction:row;align-items:center;gap:8px;color:#374151}
.ogu-form .ogdx-btn{height:46px}
.ogu-meta dl div{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #F1F3F6;font-size:13.5px}
.ogu-meta dt{color:var(--mu)}
.ogu-meta dd{font-weight:600}
.ogu-trashlink{display:inline-flex;align-items:center;gap:6px;margin-top:14px;color:#B91C1C;font-weight:600;font-size:13.5px}
.ogu-locs{max-width:820px}
.ogu-loclist{display:grid;gap:10px;counter-reset:l}
.ogu-loclist li{display:flex;align-items:center;gap:12px;border:1px solid var(--ln);border-radius:14px;padding:8px 8px 8px 14px;background:#FAFBFC;margin:0}
.ogu-locic{width:36px;height:36px;border-radius:10px;background:var(--as);color:var(--a);display:flex;align-items:center;justify-content:center;flex:none}
.ogu-loclist input{flex:1;height:42px;border:1px solid transparent;border-radius:10px;background:transparent;font-size:15px;font-weight:600;padding:0 10px;box-shadow:none;min-width:0}
.ogu-loclist input:hover{border-color:var(--ln);background:#FFFFFF}
.ogu-loclist input:focus{border-color:var(--a);background:#FFFFFF;box-shadow:0 0 0 3px rgba(53,99,233,.12)}
.ogu-uses{font-size:12.5px;color:var(--su);white-space:nowrap}
.ogu-locnote{font-size:13px;margin-top:14px!important}
.ogu-formfoot{display:flex;justify-content:flex-end;border-top:1px solid var(--ln);margin-top:18px;padding-top:16px}
.ogu-formfoot .ogdx-btn{height:46px;padding:0 24px}
@media (max-width:1200px){.ogu-detail{grid-template-columns:1fr}}
@media (max-width:782px){.ogu-bulk{top:46px}.ogu-views{margin-inline-start:0}.ogu-search{max-width:none}}
CSS;
}

function orego_ui_native_css() {
	return <<<'CSS'
body.post-type-orego_car,body.taxonomy-car_type{background:#F6F7F9}
body.post-type-orego_car #wpfooter,body.taxonomy-car_type #wpfooter{display:none}
.post-type-orego_car .wrap,.taxonomy-car_type .wrap{max-width:1320px;margin-top:18px}
.post-type-orego_car .wrap h1.wp-heading-inline,.taxonomy-car_type .wrap h1{font-size:26px;font-weight:700;color:#15171C;padding-top:4px}
.ogu-backlink{display:block;font-size:13.5px;font-weight:600;color:#6B7280;text-decoration:none;margin:0 0 6px}
.ogu-backlink:hover{color:#3563E9}
.post-type-orego_car .page-title-action,.taxonomy-car_type .page-title-action{background:#3563E9!important;border:0!important;color:#FFFFFF!important;border-radius:10px!important;padding:6px 14px!important;font-weight:600}
.post-type-orego_car .postbox,.taxonomy-car_type .form-wrap,.taxonomy-car_type .wp-list-table{border:1px solid #ECEEF2!important;border-radius:16px;box-shadow:none;background:#FFFFFF}
.post-type-orego_car .postbox{overflow:hidden;margin-bottom:18px}
.post-type-orego_car .postbox-header{border-bottom:1px solid #ECEEF2}
.post-type-orego_car .postbox .hndle{font-size:15px!important;font-weight:700!important;color:#15171C;padding:14px 18px!important}
.post-type-orego_car .postbox .inside{padding:6px 18px 18px!important;margin:0}
.post-type-orego_car #titlediv #title{border:1px solid #E6E8EC;border-radius:14px;font-size:22px;font-weight:600;padding:12px 18px;height:auto;box-shadow:none}
.post-type-orego_car #titlediv #title:focus{border-color:#3563E9;box-shadow:0 0 0 3px rgba(53,99,233,.12)}
.post-type-orego_car #wp-content-wrap{border-radius:14px;overflow:hidden;border:1px solid #ECEEF2;background:#FFFFFF}
.post-type-orego_car #postdivrich{margin-bottom:18px}
.post-type-orego_car input[type=text],.post-type-orego_car input[type=number],.post-type-orego_car select,.post-type-orego_car textarea,.taxonomy-car_type input[type=text],.taxonomy-car_type textarea,.taxonomy-car_type select{border:1px solid #E6E8EC;border-radius:10px;min-height:40px;box-shadow:none}
.post-type-orego_car input:focus,.post-type-orego_car select:focus,.post-type-orego_car textarea:focus,.taxonomy-car_type input:focus,.taxonomy-car_type textarea:focus{border-color:#3563E9!important;box-shadow:0 0 0 3px rgba(53,99,233,.12)!important}
.post-type-orego_car #publish,.taxonomy-car_type .button-primary{background:#3563E9;border-color:#3563E9;border-radius:10px;min-height:40px;padding:0 20px;font-weight:600;box-shadow:none}
.post-type-orego_car #publish:hover,.taxonomy-car_type .button-primary:hover{background:#2A52CC;border-color:#2A52CC}
.post-type-orego_car .button:not(.button-primary){border-radius:10px;border-color:#D9DCE2;color:#15171C}
.post-type-orego_car #major-publishing-actions{background:#FAFBFC;border-top:1px solid #ECEEF2;padding:14px 18px}
.post-type-orego_car #misc-publishing-actions{padding:6px 8px}
.post-type-orego_car #set-post-thumbnail img{border-radius:12px}
.post-type-orego_car #car_typechecklist li{padding:3px 0}
.post-type-orego_car .og-admin-grid label,.post-type-orego_car #orego_car_trips label,.post-type-orego_car #orego_car_features legend{color:#15171C}
.post-type-orego_car #orego_car_features fieldset{background:#FAFBFC;border:1px solid #ECEEF2;border-radius:12px;padding:12px 14px}
.post-type-orego_car #orego_car_features input[type=checkbox]{border-radius:5px}
.taxonomy-car_type #col-left .col-wrap{padding:0}
.taxonomy-car_type .form-wrap{padding:20px 22px}
.taxonomy-car_type .form-wrap h2{font-size:17px;font-weight:700;margin-top:0}
.taxonomy-car_type .wp-list-table{overflow:hidden}
.taxonomy-car_type .wp-list-table thead th,.taxonomy-car_type .wp-list-table tfoot th{background:#FAFBFC;font-size:12px;font-weight:600;color:#9AA1AD;text-transform:uppercase;letter-spacing:.04em;padding:12px 10px}
.taxonomy-car_type .wp-list-table td{padding:12px 10px;vertical-align:middle}
.taxonomy-car_type .wp-list-table .row-title{font-size:14px;font-weight:600;color:#15171C}
.taxonomy-car_type .wp-list-table tr:hover{background:#FAFBFF}
.taxonomy-car_type .tablenav .actions select,.taxonomy-car_type .search-box input{border-radius:10px;border-color:#E6E8EC}
.taxonomy-car_type .wp-list-table .column-posts a{display:inline-block;background:#EAF0FD;color:#3563E9;font-weight:600;border-radius:999px;padding:2px 10px}
CSS;
}
