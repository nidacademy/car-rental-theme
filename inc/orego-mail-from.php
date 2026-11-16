<?php
/**
 * Optional sender address for every site email (option "orego_mail_from").
 * Empty = the WordPress default. Use a real mailbox on a domain your host is allowed to send for
 * (its SPF record must include your host), otherwise Gmail and others may drop the emails.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_mail_from_address() {
	$a = (string) get_option( 'orego_mail_from', '' );
	return is_email( $a ) ? $a : '';
}

add_filter( 'wp_mail_from', function ( $from ) {
	$a = orego_mail_from_address();
	return $a ? $a : $from;
}, 99 );

/* The "envelope" sender must match too, or the receiving server still checks the temporary domain. */
add_action( 'phpmailer_init', function ( $m ) {
	$a = orego_mail_from_address();
	if ( $a ) {
		$m->Sender = $a;
	}
}, 99 );

/* Keep the last sending error, to see problems in one place. */
add_action( 'wp_mail_failed', function ( $e ) {
	update_option( 'orego_mail_last_error', array( 'time' => current_time( 'mysql' ), 'error' => $e->get_error_message() ), false );
} );