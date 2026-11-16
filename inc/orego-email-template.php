<?php
/**
 * Branded HTML emails for the booking system (customer + admin), built to work in Gmail, Apple Mail and Outlook,
 * and to fit phones: columns stack on small screens, the button goes full width.
 * Request received, confirmed, declined, cancelled, completed (car returned), pick-up and return reminders,
 * and the "new booking request" email to the admin. Sender name: Exotic Cars. A plain-text version is included.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Email-safe image (JPEG) of an attachment, cached in uploads/orego-email/. Outlook cannot show WebP. */
function orego_mail_img( $att_id, $width = 560, $name = '' ) {
	$att_id = (int) $att_id;
	if ( ! $att_id ) {
		return '';
	}
	$up   = wp_upload_dir();
	$dir  = $up['basedir'] . '/orego-email';
	$file = $dir . '/' . ( $name ? $name : 'img-' . $att_id . '-' . $width ) . '.jpg';
	$url  = $up['baseurl'] . '/orego-email/' . basename( $file );
	if ( file_exists( $file ) ) {
		return $url;
	}
	$src = get_attached_file( $att_id );
	if ( ! $src || ! file_exists( $src ) || ! function_exists( 'imagecreatetruecolor' ) ) {
		return (string) wp_get_attachment_image_url( $att_id, 'medium_large' );
	}
	$ext = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
	$im  = null;
	if ( 'webp' === $ext && function_exists( 'imagecreatefromwebp' ) ) {
		$im = @imagecreatefromwebp( $src ); // phpcs:ignore
	} elseif ( in_array( $ext, array( 'jpg', 'jpeg' ), true ) ) {
		$im = @imagecreatefromjpeg( $src ); // phpcs:ignore
	} elseif ( 'png' === $ext ) {
		$im = @imagecreatefrompng( $src ); // phpcs:ignore
	}
	if ( ! $im ) {
		return (string) wp_get_attachment_image_url( $att_id, 'medium_large' );
	}
	wp_mkdir_p( $dir );
	$w   = imagesx( $im );
	$h   = imagesy( $im );
	$nw  = min( $width * 2, $w );
	$nh  = (int) round( $h * $nw / $w );
	$dst = imagecreatetruecolor( $nw, $nh );
	imagefill( $dst, 0, 0, imagecolorallocate( $dst, 255, 255, 255 ) );
	imagecopyresampled( $dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h );
	imagejpeg( $dst, $file, 85 );
	return $url;
}

/** Wording for each email. step: position in the tracker (0 = no tracker). */
function orego_mail_types() {
	return array(
		'received'  => array( 'badge' => 'Request received', 'dot' => '#F59E0B', 'title' => 'We received your booking request', 'intro' => 'Thank you for choosing ' . orego_brand() . '. Your request is now with our team, and we will email you as soon as it is confirmed.', 'btn' => 'manage', 'step' => 1 ),
		'confirmed' => array( 'badge' => 'Booking confirmed', 'dot' => '#22C55E', 'title' => 'Your car is reserved', 'intro' => 'Great news: your booking is confirmed. Your car will be cleaned, checked and ready for you on time.', 'btn' => 'manage', 'bring' => true, 'step' => 2 ),
		'onroad'    => array( 'badge' => 'On the road', 'dot' => '#8B5CF6', 'title' => 'Enjoy your trip', 'intro' => 'You have picked up your car. Have a great trip! The details for returning it are below. If anything comes up on the road, reply to this email or call us.', 'btn' => 'manage', 'step' => 3 ),
		'fees'      => array( 'badge' => 'Payment due', 'dot' => '#F59E0B', 'title' => 'Extra charges for your rental', 'intro' => 'After checking your rental, we added the extra charges below. You can pay them at our desk, or reply to this email to arrange payment.', 'btn' => 'manage', 'step' => 0 ),
		'declined'  => array( 'badge' => 'Not confirmed', 'dot' => '#EF4444', 'title' => 'We could not confirm your request', 'intro' => 'We are sorry, this car is not available for your request. We would be happy to help you choose another car or other dates.', 'btn' => 'cars', 'step' => 0 ),
		'cancelled' => array( 'badge' => 'Booking cancelled', 'dot' => '#9CA3AF', 'title' => 'Your booking has been cancelled', 'intro' => 'Your booking has been cancelled. If this was a mistake or you have a question, simply reply to this email.', 'btn' => 'cars', 'step' => 0 ),
		'completed' => array( 'badge' => 'Car returned', 'dot' => '#60A5FA', 'title' => 'Thank you for riding with us', 'intro' => 'Your car has been returned and your rental is complete. We hope you enjoyed the trip, and we look forward to welcoming you again.', 'btn' => 'again', 'step' => 4 ),
		'pickup'    => array( 'badge' => 'Pick-up tomorrow', 'dot' => '#60A5FA', 'title' => 'Your car is ready for tomorrow', 'intro' => '', 'btn' => 'manage', 'bring' => true, 'step' => 2 ),
		'return'    => array( 'badge' => 'Return tomorrow', 'dot' => '#60A5FA', 'title' => 'Your rental ends tomorrow', 'intro' => '', 'btn' => 'manage', 'step' => 3 ),
	);
}

function orego_mail_subjects( $type, $id ) {
	$s = array(
		'received'  => 'We received your booking request #' . $id,
		'confirmed' => 'Your booking #' . $id . ' is confirmed',
		'onroad'    => 'Enjoy your trip! (booking #' . $id . ')',
		'fees'      => 'Extra charges for your booking #' . $id,
		'declined'  => 'About your booking request #' . $id,
		'cancelled' => 'Your booking #' . $id . ' has been cancelled',
		'completed' => 'Thank you for riding with ' . orego_brand() . ' (booking #' . $id . ')',
		'pickup'    => 'Reminder: your car is ready tomorrow (booking #' . $id . ')',
		'return'    => 'Reminder: return tomorrow (booking #' . $id . ')',
	);
	return $s[ $type ] ?? ( 'Your booking #' . $id );
}

/** Pick-up / return data of a booking for the trip card. */
function orego_mail_trip( $id ) {
	$i   = orego_bk_info( $id );
	$fmt = function ( $d ) {
		$t = strtotime( $d . ' 12:00:00' );
		return $t ? array( wp_date( 'D', $t ), wp_date( 'j M Y', $t ) ) : array( '', $d );
	};
	return array(
		'pick' => array( 'label' => 'Pick-up', 'day' => $fmt( $i['start'] ), 'time' => $i['ptime'], 'place' => $i['pickup'] ),
		'ret'  => array( 'label' => 'Return', 'day' => $fmt( $i['end'] ), 'time' => $i['rtime'], 'place' => $i['dropoff'] ),
	);
}

/** The full HTML email. */
function orego_mail_layout( $o ) {
	$e    = 'esc_html';
	$up   = get_template_directory_uri() . '/assets/email/';
	$site = home_url( '/' );
	$cars = orego_purl( 'cars' );
	$font = "'Helvetica Neue',Helvetica,Arial,sans-serif";
	$ink  = '#111318';

	$h  = '<!doctype html><html lang="en" xmlns="http://www.w3.org/1999/xhtml"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta name="color-scheme" content="light only"><meta name="supported-color-schemes" content="light"><title>' . $e( $o['title'] ) . '</title>';
	$h .= '<style>body{margin:0!important;padding:0!important;width:100%!important;background:#EEF0F3}table{border-collapse:collapse}img{border:0;line-height:100%;outline:none;text-decoration:none}a{text-decoration:none}'
		. '.col{display:inline-block;vertical-align:top}'
		. '@media screen and (max-width:620px){.wrap{width:100%!important}.px{padding-left:20px!important;padding-right:20px!important}.hero-h{font-size:26px!important;line-height:32px!important}.col{display:block!important;width:100%!important;max-width:100%!important}.col-gap{height:12px!important}.btn a{display:block!important}.btn,.btn td{width:100%!important}.step-t{font-size:10px!important}.big{font-size:20px!important}}</style>';
	$h .= '</head><body style="margin:0;padding:0;background:#EEF0F3;">';
	$h .= '<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">' . $e( $o['pre'] ?? $o['title'] ) . str_repeat( '&#8204;&nbsp;', 40 ) . '</div>';
	$h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EEF0F3;"><tr><td align="center" style="padding:24px 10px 32px;">';
	$h .= '<table role="presentation" class="wrap" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;">';

	/* dark hero: logo, status, headline */
	$h .= '<tr><td style="background:' . $ink . ';border-radius:22px 22px 0 0;" class="px" ><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
	$h .= '<tr><td class="px" style="padding:30px 36px 0;"><a href="' . esc_url( $site ) . '"><img src="' . esc_url( $up . 'logo-light.png' ) . '" width="150" alt="' . esc_attr( orego_brand() ) . '" style="display:block;width:150px;height:auto;color:#ffffff;font:700 20px ' . $font . ';"></a></td></tr>';
	$h .= '<tr><td class="px" style="padding:34px 36px 0;font-family:' . $font . ';"><span style="display:inline-block;padding:7px 13px 7px 11px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#E5E7EB;"><span style="color:' . $o['dot'] . ';font-size:13px;">&#9679;</span>&nbsp; ' . $e( $o['badge'] ) . '</span></td></tr>';
	$h .= '<tr><td class="px" style="padding:16px 36px 0;font-family:' . $font . ';"><h1 class="hero-h" style="margin:0;font-size:32px;line-height:38px;font-weight:800;letter-spacing:-.02em;color:#FFFFFF;">' . $e( $o['title'] ) . '</h1></td></tr>';
	if ( ! empty( $o['ref'] ) ) {
		$h .= '<tr><td class="px" style="padding:10px 36px 0;font-family:' . $font . ';font-size:14px;color:#9CA3AF;">' . $e( $o['ref'] ) . '</td></tr>';
	}
	/* progress tracker */
	if ( ! empty( $o['step'] ) ) {
		$steps = array( 'Requested', 'Confirmed', 'On the road', 'Returned' );
		$h    .= '<tr><td class="px" style="padding:28px 36px 0;font-family:' . $font . ';"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>';
		foreach ( $steps as $k => $label ) {
			$n    = $k + 1;
			$done = $n <= $o['step'];
			$h   .= '<td width="25%" valign="top" style="padding:0 3px;"><div style="height:4px;border-radius:4px;background:' . ( $done ? '#FFFFFF' : 'rgba(255,255,255,.18)' ) . ';font-size:0;line-height:0;">&nbsp;</div><div class="step-t" style="padding-top:9px;font-size:11px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:' . ( $done ? '#FFFFFF' : '#6B7280' ) . ';">' . $e( $label ) . '</div></td>';
		}
		$h .= '</tr></table></td></tr>';
	}
	$h .= '<tr><td style="padding:0 0 32px;font-size:0;line-height:0;">&nbsp;</td></tr>';
	$h .= '</table></td></tr>';

	/* white body */
	$h .= '<tr><td style="background:#FFFFFF;border-radius:0 0 22px 22px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
	$h .= '<tr><td class="px" style="padding:32px 36px 0;font-family:' . $font . ';">';
	if ( ! empty( $o['greet'] ) ) {
		$h .= '<p style="margin:0 0 10px;font-size:17px;line-height:26px;font-weight:700;color:' . $ink . ';">' . $e( $o['greet'] ) . '</p>';
	}
	foreach ( (array) ( $o['intro'] ?? array() ) as $p ) {
		$h .= '<p style="margin:0 0 4px;font-size:16px;line-height:26px;color:#4B5563;">' . nl2br( $e( $p ) ) . '</p>';
	}
	$h .= '</td></tr>';

	/* car photo + name */
	if ( ! empty( $o['car'] ) ) {
		$h .= '<tr><td class="px" style="padding:26px 36px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FFFFFF;border:1px solid #E8EAEE;border-radius:18px;">';
		if ( ! empty( $o['car']['img'] ) ) {
			$h .= '<tr><td align="center" style="padding:18px 18px 0;"><img src="' . esc_url( $o['car']['img'] ) . '" width="360" alt="' . esc_attr( $o['car']['name'] ) . '" style="display:block;width:100%;max-width:360px;height:auto;"></td></tr>';
		}
		$h .= '<tr><td align="center" style="padding:12px 18px 20px;font-family:' . $font . ';"><div style="font-size:19px;line-height:25px;font-weight:800;color:' . $ink . ';">' . $e( $o['car']['name'] ) . '</div>';
		if ( ! empty( $o['car']['meta'] ) ) {
			$h .= '<div style="padding-top:4px;font-size:13px;color:#6B7280;">' . $e( $o['car']['meta'] ) . '</div>';
		}
		$h .= '</td></tr></table></td></tr>';
	}

	/* pick-up / return: two columns that stack on phones */
	if ( ! empty( $o['trip'] ) ) {
		$col = function ( $c ) use ( $e, $font, $ink ) {
			return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E8EAEE;border-radius:16px;"><tr><td style="padding:16px 18px;font-family:' . $font . ';">'
				. '<div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9CA3AF;">' . $e( $c['label'] ) . '</div>'
				. '<div class="big" style="padding-top:8px;font-size:22px;line-height:26px;font-weight:800;color:' . $ink . ';">' . $e( $c['day'][1] ) . '</div>'
				. '<div style="padding-top:3px;font-size:14px;color:#4B5563;">' . $e( $c['day'][0] ) . ( $c['time'] ? ' &middot; ' . $e( $c['time'] ) : '' ) . '</div>'
				. ( $c['place'] ? '<div style="padding-top:10px;font-size:13px;line-height:19px;color:#6B7280;">&#9906;&nbsp;' . $e( $c['place'] ) . '</div>' : '' )
				. '</td></tr></table>';
		};
		$h .= '<tr><td class="px" style="padding:16px 36px 0;font-size:0;">'
			. '<div class="col" style="width:100%;max-width:258px;">' . $col( $o['trip']['pick'] ) . '</div>'
			. '<div class="col col-gap" style="width:12px;height:1px;font-size:0;line-height:0;">&nbsp;</div>'
			. '<div class="col" style="width:100%;max-width:258px;">' . $col( $o['trip']['ret'] ) . '</div>'
			. '</td></tr>';
	}

	/* rows (summary / price) */
	if ( ! empty( $o['rows'] ) ) {
		$h .= '<tr><td class="px" style="padding:20px 36px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F6F7F8;border-radius:16px;"><tr><td style="padding:6px 18px;">';
		$h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
		$n  = count( $o['rows'] );
		foreach ( array_values( $o['rows'] ) as $k => $r ) {
			$total = ! empty( $r[2] );
			$line  = $k < $n - 1 ? 'border-bottom:1px solid #E8EAEE;' : '';
			$h    .= '<tr><td valign="top" style="padding:13px 0;' . $line . 'font-family:' . $font . ';font-size:14px;line-height:20px;color:' . ( $total ? $ink : '#6B7280' ) . ';font-weight:' . ( $total ? '800' : '400' ) . ';">' . $e( $r[0] ) . '</td>'
				. '<td valign="top" align="right" style="padding:13px 0;' . $line . 'font-family:' . $font . ';font-size:' . ( $total ? '20px' : '14px' ) . ';line-height:' . ( $total ? '24px' : '20px' ) . ';font-weight:' . ( $total ? '800' : '600' ) . ';color:' . $ink . ';">' . wp_kses( $r[1], array( 'br' => array(), 'span' => array( 'style' => array() ) ) ) . '</td></tr>';
		}
		$h .= '</table></td></tr></table></td></tr>';
	}

	/* boxes */
	foreach ( (array) ( $o['boxes'] ?? array() ) as $b ) {
		$h .= '<tr><td class="px" style="padding:16px 36px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E8EAEE;border-radius:16px;"><tr><td style="padding:16px 18px;font-family:' . $font . ';"><div style="font-size:14px;font-weight:800;color:' . $ink . ';">' . $e( $b['title'] ) . '</div>';
		if ( ! empty( $b['list'] ) ) {
			foreach ( $b['list'] as $li ) {
				$h .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:9px;"><tr><td valign="top" style="width:22px;"><span style="display:inline-block;width:16px;height:16px;border-radius:50%;background:' . $ink . ';color:#fff;font-size:10px;line-height:16px;text-align:center;font-weight:700;">&#10003;</span></td><td style="font-family:' . $font . ';font-size:14px;line-height:18px;color:#4B5563;">' . $e( $li ) . '</td></tr></table>';
			}
		} else {
			$h .= '<div style="padding-top:6px;font-size:14px;line-height:22px;color:#4B5563;">' . nl2br( $e( $b['text'] ) ) . '</div>';
		}
		$h .= '</td></tr></table></td></tr>';
	}

	/* button */
	if ( ! empty( $o['btn'] ) ) {
		$h .= '<tr><td class="px" align="center" style="padding:28px 36px 0;"><table role="presentation" class="btn" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" bgcolor="' . $ink . '" style="border-radius:14px;"><a href="' . esc_url( $o['btn'][1] ) . '" style="display:inline-block;padding:16px 34px;font-family:' . $font . ';font-size:16px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:14px;">' . $e( $o['btn'][0] ) . '&nbsp;&nbsp;&rarr;</a></td></tr></table></td></tr>';
	}

	/* help */
	$h .= '<tr><td class="px" style="padding:30px 36px 34px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #EEF0F3;"><tr><td style="padding-top:22px;font-family:' . $font . ';font-size:14px;line-height:22px;color:#6B7280;text-align:center;"><b style="color:' . $ink . ';">Need help?</b><br>' . $e( $o['help'] ?? 'Just reply to this email and our team will get back to you.' ) . '</td></tr></table></td></tr>';
	$h .= '</table></td></tr>';

	/* footer */
	$links = array( 'Cars' => $cars, 'About us' => home_url( '/about-us/' ), 'Contact' => home_url( '/contact-us/' ) );
	$fl    = array();
	foreach ( $links as $l => $u ) {
		$fl[] = '<a href="' . esc_url( $u ) . '" style="color:#6B7280;text-decoration:none;font-weight:600;">' . $e( $l ) . '</a>';
	}
	$h .= '<tr><td align="center" style="padding:26px 16px 0;font-family:' . $font . ';font-size:13px;line-height:20px;color:#9CA3AF;">' . implode( '&nbsp;&nbsp;&middot;&nbsp;&nbsp;', $fl ) . '<br><span style="display:inline-block;padding-top:10px;">&copy; ' . wp_date( 'Y' ) . ' ' . esc_html( orego_brand() ) . ' &middot; Car rental</span></td></tr>';
	$h .= '</table></td></tr></table></body></html>';
	return $h;
}

/** Price rows for a booking. */
/* Extra charges email: each charge, what was already paid, and what is left to pay. */
function orego_mail_fee_rows( $id ) {
	$rows = array();
	foreach ( ( function_exists( 'orego_json_meta' ) ? orego_json_meta( $id, 'og_charges' ) : array() ) as $c ) {
		$rows[] = array( esc_html( $c['label'] ), esc_html( orego_bk_money( (float) $c['amount'], $id ) ) );
	}
	if ( function_exists( 'orego_pay_summary' ) ) {
		$sum    = orego_pay_summary( $id );
		$rows[] = array( 'Rental', esc_html( orego_bk_money( $sum['total'], $id ) ) );
		if ( $sum['paid'] > 0 ) {
			$rows[] = array( 'Already paid', '- ' . esc_html( orego_bk_money( $sum['paid'], $id ) ) );
		}
		$rows[] = array( 'Left to pay', esc_html( orego_bk_money( max( 0, $sum['balance'] ), $id ) ), true );
	}
	return $rows;
}

function orego_mail_price_rows( $id, $admin = false ) {
	$i    = orego_bk_info( $id );
	$rows = array();
	if ( $admin ) {
		$rows[] = array( 'Customer', esc_html( $i['name'] ) . '<br><span style="color:#6B7280;font-weight:400;">' . esc_html( $i['phone'] ) . '<br>' . esc_html( $i['email'] ) . '</span>' );
	}
	$days = (int) $i['days'];
	if ( (float) $i['pday'] > 0 ) {
		$rows[] = array( 'Daily rate', esc_html( orego_bk_money( $i['pday'], $id ) ) . ' &times; ' . $days . ( 1 === $days ? ' day' : ' days' ) );
	} else {
		$rows[] = array( 'Duration', $days . ( 1 === $days ? ' day' : ' days' ) );
	}
	$rows[] = array( 'Total', esc_html( orego_bk_money( $i['total'], $id ) ) . '<br><span style="font-size:12px;font-weight:600;color:#6B7280;">Pay at pick-up</span>', true );
	return $rows;
}

function orego_mail_car( $id ) {
	$i     = orego_bk_info( $id );
	$car   = (int) $i['car'];
	$img   = has_post_thumbnail( $car ) ? orego_mail_img( get_post_thumbnail_id( $car ), 440, 'car3-' . $car . '-' . (int) get_post_thumbnail_id( $car ) ) : '';
	$terms = get_the_terms( $car, 'car_type' );
	$meta  = array();
	if ( $terms && ! is_wp_error( $terms ) ) {
		$meta[] = $terms[0]->name;
	}
	if ( function_exists( 'orego_car_data' ) ) {
		$d   = orego_car_data( $car );
		$opt = function_exists( 'orego_car_options' ) ? orego_car_options() : array();
		if ( ! empty( $d['seats'] ) ) {
			$meta[] = (int) $d['seats'] . ' seats';
		}
		if ( ! empty( $opt['trans'][ $d['trans'] ] ) ) {
			$meta[] = $opt['trans'][ $d['trans'] ];
		}
	}
	return array( 'img' => $img, 'name' => get_the_title( $car ), 'meta' => implode( ' · ', $meta ) );
}

/** Send an HTML email with a plain-text copy, from "Exotic Cars". */
function orego_mail_send( $to, $subject, $html, $headers = array(), $text = '' ) {
	if ( '' === $text ) {
		$text = preg_replace( '#<(style|title|head)[^>]*>.*?</\1>#si', '', $html );
		$text = preg_replace( array( '#<br\s*/?>#i', '#</(p|div|tr|h1|table)>#i' ), array( "\n", "\n" ), $text );
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
		$text = trim( preg_replace( array( "/[ \t\x{200C}\x{00A0}]+/u", "/\n\s*\n\s*\n+/" ), array( ' ', "\n\n" ), $text ) );
	}
	$alt  = function ( $m ) use ( $text ) {
		$m->AltBody = $text;
	};
	$from = function () {
		return orego_brand();
	};
	add_action( 'phpmailer_init', $alt );
	add_filter( 'wp_mail_from_name', $from );
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	$ok        = wp_mail( $to, $subject, $html, $headers );
	remove_action( 'phpmailer_init', $alt );
	remove_filter( 'wp_mail_from_name', $from );
	return $ok;
}

/** Customer email for a booking. $subject / $intro override the defaults (reminders). */
function orego_mail_send_booking( $id, $type, $subject = '', $intro = '' ) {
	$html = orego_mail_booking_html( $id, $type, $intro );
	$i    = orego_bk_info( $id );
	if ( ! $html || ! is_email( $i['email'] ) ) {
		return false;
	}
	return orego_mail_send( $i['email'], $subject ? $subject : orego_mail_subjects( $type, $id ), $html, array( 'Reply-To: ' . get_option( 'admin_email' ) ) );
}

function orego_mail_booking_html( $id, $type, $intro = '' ) {
	$i = orego_bk_info( $id );
	$t = orego_mail_types()[ $type ] ?? null;
	if ( ! $t ) {
		return '';
	}
	$cars  = orego_purl( 'cars' );
	$rules = function_exists( 'orego_rules' ) ? orego_rules() : array();
	$intro = $intro ? $intro : $t['intro'];
	$intro = trim( preg_split( "/\n\nPlease bring:/", $intro )[0] );
	$boxes = array();
	if ( ! empty( $t['bring'] ) && ! empty( $rules['bring'] ) ) {
		$list = array_filter( array_map( 'trim', explode( "\n", $rules['bring'] ) ) );
		if ( $list ) {
			$boxes[] = array( 'title' => 'Please bring at pick-up', 'list' => $list );
		}
	}
	if ( $i['anote'] && 'received' !== $type ) {
		$boxes[] = array( 'title' => 'A note from our team', 'text' => $i['anote'] );
	}
	$btn = null;
	if ( 'manage' === $t['btn'] && function_exists( 'orego_manage_url' ) ) {
		$btn = array( 'View your booking', orego_manage_url( $id ) );
	} elseif ( 'cars' === $t['btn'] ) {
		$btn = array( 'Browse available cars', $cars );
	} elseif ( 'again' === $t['btn'] ) {
		$btn = array( 'Book your next trip', $cars );
	}
	return orego_mail_layout( array(
		'badge' => $t['badge'],
		'dot'   => $t['dot'],
		'title' => $t['title'],
		'ref'   => 'Booking #' . $id,
		'step'  => $t['step'],
		'pre'   => $intro,
		'greet' => 'Hello ' . $i['name'] . ',',
		'intro' => array( $intro ),
		'car'   => orego_mail_car( $id ),
		'trip'  => orego_mail_trip( $id ),
		'rows'  => 'fees' === $type ? orego_mail_fee_rows( $id ) : orego_mail_price_rows( $id ),
		'boxes' => $boxes,
		'btn'   => $btn,
	) );
}

/** "New booking request" email to the admin. */
function orego_mail_send_admin( $id ) {
	$i     = orego_bk_info( $id );
	$boxes = $i['notes'] ? array( array( 'title' => 'Customer notes', 'text' => $i['notes'] ) ) : array();
	$html  = orego_mail_layout( array(
		'badge' => 'Action needed',
		'dot'   => '#F59E0B',
		'title' => 'New booking request',
		'ref'   => 'Booking #' . $id . ' · ' . $i['name'],
		'step'  => 1,
		'pre'   => $i['name'] . ' wants to book ' . get_the_title( $i['car'] ),
		'intro' => array( $i['name'] . ' sent a booking request. It is waiting for your approval.' ),
		'car'   => orego_mail_car( $id ),
		'trip'  => orego_mail_trip( $id ),
		'rows'  => orego_mail_price_rows( $id, true ),
		'boxes' => $boxes,
		'btn'   => array( 'Review the request', admin_url( 'post.php?post=' . $id . '&action=edit' ) ),
		'help'  => 'Reply to this email to write to the customer directly.',
	) );
	return orego_mail_send( get_option( 'admin_email' ), 'New booking request #' . $id . ' - ' . get_the_title( $i['car'] ), $html, array( 'Reply-To: ' . $i['email'] ) );
}
