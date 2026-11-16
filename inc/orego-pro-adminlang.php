<?php
/**
 * Booking system language, separate from the WordPress dashboard language.
 * - Your Cars -> Language (just before "Reset bookings"): English, العربية, Français, Español, Deutsch, Italiano, Português,
 *   or "Same as WordPress". Saved for you; can also be made the default for every user.
 * - Quick switch in the admin bar ("Bookings: English").
 * It changes "Your Cars" only (bookings, cars, booking dashboard, settings). The public website and customer emails are not affected.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_admin_langs() {
	return array(
		'en' => array( 'English', 'English', '🇬🇧' ),
		'ar' => array( 'العربية', 'Arabic', '🇲🇦' ),
		'fr' => array( 'Français', 'French', '🇫🇷' ),
		'es' => array( 'Español', 'Spanish', '🇪🇸' ),
		'de' => array( 'Deutsch', 'German', '🇩🇪' ),
		'it' => array( 'Italiano', 'Italian', '🇮🇹' ),
		'pt' => array( 'Português', 'Portuguese', '🇵🇹' ),
	);
}

/* The choice: 'auto' (follow WordPress) or a language code. User choice first, then the site default. */
function orego_admin_lang_pref() {
	$ok  = array_keys( orego_admin_langs() );
	$uid = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
	$p   = $uid ? (string) get_user_meta( $uid, 'orego_admin_lang', true ) : '';
	if ( in_array( $p, $ok, true ) ) {
		return $p;
	}
	if ( 'auto' === $p ) {
		return 'auto';
	}
	$d = (string) get_option( 'orego_admin_lang_default', '' );
	return in_array( $d, $ok, true ) ? $d : 'auto';
}

/* The language actually used */
function orego_admin_lang() {
	$p = orego_admin_lang_pref();
	if ( 'auto' !== $p ) {
		return $p;
	}
	$loc = function_exists( 'get_user_locale' ) ? strtolower( substr( (string) get_user_locale(), 0, 2 ) ) : 'en';
	return isset( orego_admin_langs()[ $loc ] ) ? $loc : 'en';
}

/* The page belongs to the booking system */
function orego_admin_lang_screen() {
	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	return $s && ( 'orego_car' === $s->post_type || 'orego_booking' === $s->post_type || 'car_type' === $s->taxonomy || false !== strpos( (string) $s->id, 'orego' ) );
}

function orego_admin_lang_set( $k, $all = false ) {
	$ok = array_keys( orego_admin_langs() );
	$k  = in_array( $k, $ok, true ) ? $k : 'auto';
	update_user_meta( get_current_user_id(), 'orego_admin_lang', $k );
	if ( $all && current_user_can( 'manage_options' ) ) {
		update_option( 'orego_admin_lang_default', 'auto' === $k ? '' : $k, false );
	}
}

/* ------------------------------------------------------------------ */
/* Translating the booking screens (French, Spanish, German, Italian, Portuguese) */
/* ------------------------------------------------------------------ */

function orego_admin_tr_html( $html, $dict ) {
	if ( ! $dict ) {
		return $html;
	}
	$tr    = function ( $t ) use ( $dict ) {
		$raw = trim( html_entity_decode( $t, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( '' === $raw ) {
			return null;
		}
		if ( isset( $dict[ $raw ] ) ) {
			return $dict[ $raw ];
		}
		if ( false !== strpos( $raw, ' · ' ) ) { // "Automatic · Petrol"
			$parts = explode( ' · ', $raw );
			$hit   = false;
			foreach ( $parts as $i => $p ) {
				if ( isset( $dict[ $p ] ) ) {
					$parts[ $i ] = $dict[ $p ];
					$hit         = true;
				}
			}
			return $hit ? implode( ' · ', $parts ) : null;
		}
		return null;
	};
	$parts = preg_split( '#(<script\b.*?</script>|<style\b.*?</style>|<textarea\b.*?</textarea>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $parts as $i => $part ) {
		if ( preg_match( '#^<(script|style|textarea)\b#i', $part ) ) {
			continue;
		}
		$part = preg_replace_callback( '/>([^<>]+)</u', function ( $m ) use ( $tr ) {
			$t = $tr( $m[1] );
			if ( null === $t ) {
				return $m[0];
			}
			preg_match( '/^\s*/u', $m[1], $l );
			preg_match( '/\s*$/u', $m[1], $r );
			return '>' . $l[0] . esc_html( $t ) . $r[0] . '<';
		}, $part );
		$part = preg_replace_callback( '/\b(placeholder|title|aria-label|data-confirm)="([^"]*)"/u', function ( $m ) use ( $tr ) {
			$t = $tr( $m[2] );
			return null === $t ? $m[0] : $m[1] . '="' . esc_attr( $t ) . '"';
		}, $part );
		$parts[ $i ] = $part;
	}
	return implode( '', $parts );
}

/* Booking screens: translate the page content */
add_action( 'current_screen', function () {
	if ( ! function_exists( 'orego_admin_dict' ) || ! orego_admin_lang_screen() ) {
		return;
	}
	$dict = orego_admin_dict( orego_admin_lang() );
	if ( ! $dict ) {
		return;
	}
	ob_start( function ( $html ) use ( $dict ) {
		$a = strpos( $html, 'id="wpbody-content"' );
		$b = strpos( $html, '<!-- wpbody-content -->' );
		if ( false === $a || false === $b || $b <= $a ) {
			return $html;
		}
		return substr( $html, 0, $a ) . orego_admin_tr_html( substr( $html, $a, $b - $a ), $dict ) . substr( $html, $b );
	} );
} );

/* The "Your Cars" menu itself */
add_action( 'admin_menu', function () {
	global $menu, $submenu;
	if ( ! function_exists( 'orego_admin_dict' ) ) {
		return;
	}
	$dict = orego_admin_dict( orego_admin_lang() );
	$key  = 'edit.php?post_type=orego_car';
	if ( $dict ) {
		foreach ( (array) $menu as $i => $m ) {
			if ( isset( $m[2] ) && $key === $m[2] && isset( $dict[ wp_strip_all_tags( $m[0] ) ] ) ) {
				$menu[ $i ][0] = preg_replace( '/^[^<]+/u', $dict[ wp_strip_all_tags( $m[0] ) ], $m[0] ); // phpcs:ignore
			}
		}
		foreach ( (array) ( $submenu[ $key ] ?? array() ) as $i => $s ) {
			$plain = trim( wp_strip_all_tags( $s[0] ) );
			if ( isset( $dict[ $plain ] ) ) {
				$submenu[ $key ][ $i ][0] = str_replace( $plain, $dict[ $plain ], $s[0] ); // phpcs:ignore
			}
		}
	}
	// "Language" just before "Reset bookings"
	if ( ! empty( $submenu[ $key ] ) ) {
		$items = array_values( $submenu[ $key ] );
		$lang  = null;
		foreach ( $items as $i => $s ) {
			if ( 'orego-lang' === $s[2] ) {
				$lang = $s;
				unset( $items[ $i ] );
			}
		}
		if ( $lang ) {
			$items = array_values( $items );
			$at    = count( $items );
			foreach ( $items as $i => $s ) {
				if ( 'orego-reset' === $s[2] ) {
					$at = $i;
				}
			}
			array_splice( $items, $at, 0, array( $lang ) );
			$submenu[ $key ] = $items; // phpcs:ignore
		}
	}
}, 9999 );

/* ------------------------------------------------------------------ */
/* Your Cars -> Language                                               */
/* ------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=orego_car', 'Booking system language', 'Language', 'edit_posts', 'orego-lang', 'orego_admin_lang_page' );
}, 50 );

add_action( 'admin_post_orego_admin_lang_save', function () {
	check_admin_referer( 'orego_admin_lang_save' );
	orego_admin_lang_set( sanitize_key( $_POST['lang'] ?? 'auto' ), ! empty( $_POST['all'] ) );
	wp_safe_redirect( admin_url( 'edit.php?post_type=orego_car&page=orego-lang&saved=1' ) );
	exit;
} );

function orego_admin_lang_page() {
	$pref = orego_admin_lang_pref();
	$def  = (string) get_option( 'orego_admin_lang_default', '' );
	$wp   = function_exists( 'get_user_locale' ) ? get_user_locale() : 'en_US';
	?>
	<div class="wrap ogal">
		<h1>Booking system language</h1>
		<p class="ogal-intro">Choose the language of <b>Your Cars</b>: bookings, cars, the booking dashboard and its settings. The rest of WordPress, the public website and customer emails are not changed.</p>
		<?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Language saved.</p></div><?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="orego_admin_lang_save"><?php wp_nonce_field( 'orego_admin_lang_save' ); ?>
			<div class="ogal-grid">
				<?php foreach ( orego_admin_langs() as $k => $l ) : ?>
				<label class="ogal-opt">
					<input type="radio" name="lang" value="<?php echo esc_attr( $k ); ?>" <?php checked( $pref, $k ); ?>>
					<span class="ogal-flag" aria-hidden="true"><?php echo esc_html( $l[2] ); ?></span>
					<span class="ogal-tx"><b><?php echo esc_html( $l[0] ); ?></b><small><?php echo esc_html( $l[1] ); ?></small></span>
				</label>
				<?php endforeach; ?>
				<label class="ogal-opt">
					<input type="radio" name="lang" value="auto" <?php checked( $pref, 'auto' ); ?>>
					<span class="ogal-flag" aria-hidden="true">⚙️</span>
					<span class="ogal-tx"><b>Same as WordPress</b><small>Now: <?php echo esc_html( $wp ); ?></small></span>
				</label>
			</div>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<label class="ogal-all"><input type="checkbox" name="all" value="1" <?php checked( '' !== $def && $def === $pref ); ?>> Use this language for every user who has not chosen one</label>
			<?php endif; ?>
			<p><button type="submit" class="button button-primary button-hero">Save language</button></p>
		</form>
		<p class="description">You can also switch quickly from the admin bar at the top: <b>Bookings: <?php echo esc_html( orego_admin_langs()[ orego_admin_lang() ][0] ); ?></b>.</p>
	</div>
	<style>
	.ogal-intro{max-width:760px;font-size:14px}
	.ogal-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px;max-width:960px;margin:18px 0}
	.ogal-opt{position:relative;display:flex;align-items:center;gap:12px;padding:14px 16px;border:1.5px solid #DCDCDE;border-radius:14px;background:#FFFFFF;cursor:pointer;transition:border-color .15s,box-shadow .15s}
	.ogal-opt:hover{border-color:#8C8F94}
	.ogal-opt input{position:absolute;opacity:0}
	.ogal-opt:has(input:checked){border-color:#15171C;box-shadow:0 0 0 1px #15171C}
	.ogal-opt:has(input:checked)::after{content:"✓";position:absolute;top:10px;right:12px;width:20px;height:20px;border-radius:50%;background:#15171C;color:#FFFFFF;font-size:12px;line-height:20px;text-align:center}
	.ogal-opt:has(input:focus-visible){outline:2px solid #2271B1;outline-offset:2px}
	.ogal-flag{font-size:26px;line-height:1}
	.ogal-tx b{display:block;font-size:15px;color:#15171C}
	.ogal-tx small{display:block;color:#646970}
	.ogal-all{display:flex;align-items:center;gap:8px;margin:0 0 6px}
	</style>
	<?php
}

/* ------------------------------------------------------------------ */
/* Admin bar quick switch                                              */
/* ------------------------------------------------------------------ */

add_action( 'admin_bar_menu', function ( $bar ) {
	if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$pref  = orego_admin_lang_pref();
	$langs = orego_admin_langs();
	$bar->add_node( array(
		'id'    => 'orego-lang',
		'title' => '<span class="ab-icon dashicons dashicons-translation" style="top:2px"></span><span class="ab-label">Bookings: ' . esc_html( $langs[ orego_admin_lang() ][0] ) . '</span>',
		'href'  => admin_url( 'edit.php?post_type=orego_car&page=orego-lang' ),
		'meta'  => array( 'title' => 'Language of the booking system (Your Cars)' ),
	) );
	$back = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url();
	foreach ( array_merge( array_map( fn( $l ) => $l[0], $langs ), array( 'auto' => 'Same as WordPress' ) ) as $k => $label ) {
		$bar->add_node( array(
			'parent' => 'orego-lang',
			'id'     => 'orego-lang-' . $k,
			'title'  => ( $pref === $k ? '✓ ' : '<span style="display:inline-block;width:1em"></span> ' ) . esc_html( $label ),
			'href'   => wp_nonce_url( add_query_arg( array( 'action' => 'orego_admin_lang', 'lang' => $k, 'back' => rawurlencode( $back ) ), admin_url( 'admin-post.php' ) ), 'orego_admin_lang' ),
		) );
	}
}, 90 );

add_action( 'admin_post_orego_admin_lang', function () {
	check_admin_referer( 'orego_admin_lang' );
	orego_admin_lang_set( sanitize_key( $_GET['lang'] ?? 'auto' ) );
	$back = isset( $_GET['back'] ) ? esc_url_raw( rawurldecode( wp_unslash( $_GET['back'] ) ) ) : admin_url();
	wp_safe_redirect( $back ? $back : admin_url() );
	exit;
} );

/* Arabic booking system on a left-to-right dashboard (or the reverse): text direction of the booking pages */
add_filter( 'admin_body_class', function ( $c ) {
	if ( ! orego_admin_lang_screen() ) {
		return $c;
	}
	$ar = 'ar' === orego_admin_lang();
	if ( $ar && ! is_rtl() ) {
		$c .= ' orego-force-rtl';
	} elseif ( ! $ar && is_rtl() ) {
		$c .= ' orego-force-ltr';
	}
	return $c;
} );

add_action( 'admin_head', function () {
	?>
<style id="og-adminlang-css">
body.orego-force-rtl #wpbody-content{direction:rtl}
body.orego-force-rtl #wpbody-content .ogdx,body.orego-force-rtl #wpbody-content .postbox{text-align:right}
body.orego-force-ltr #wpbody-content{direction:ltr}
#wpadminbar #wp-admin-bar-orego-lang .ab-sub-wrapper a{min-width:170px}
</style>
	<?php
} );

/* The Your Cars menu is built from a fixed list: add Extras and Online payments after Discounts, and Language before Reset bookings. */
add_filter( 'orego_app_menu', function ( $items ) {
	$have = array_column( $items, 2 );
	$add  = function ( &$items, $row, $after_slug = '', $before_slug = '' ) {
		$slugs = array_column( $items, 2 );
		if ( in_array( $row[2], $slugs, true ) ) {
			return;
		}
		$at = count( $items );
		if ( $before_slug && false !== ( $i = array_search( $before_slug, $slugs, true ) ) ) {
			$at = $i;
		} elseif ( $after_slug && false !== ( $i = array_search( $after_slug, $slugs, true ) ) ) {
			$at = $i + 1;
		}
		array_splice( $items, $at, 0, array( $row ) );
	};
	if ( function_exists( 'orego_pay_page' ) ) {
		$add( $items, array( 'Online payments', 'manage_options', 'orego-payments', 'Online payments' ), 'orego-rules', 'orego-reset' );
	}
	$add( $items, array( 'Language', 'edit_posts', 'orego-lang', 'Booking system language' ), '', 'orego-reset' );
	return $items;
}, 999 );
