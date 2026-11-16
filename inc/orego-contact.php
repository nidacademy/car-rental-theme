<?php
/**
 * Contact form: [orego_contact_form] shortcode.
 * Each message is saved under wp-admin > Messages and emailed to the site admin address.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Where messages are stored. Visitors never see these. */
add_action( 'init', function () {
	register_post_type( 'orego_message', array(
		'labels'          => array(
			'name'          => 'Messages',
			'singular_name' => 'Message',
			'all_items'     => 'All messages',
			'edit_item'     => 'Message',
			'search_items'  => 'Search messages',
			'not_found'     => 'No messages yet.',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_position'   => 26,
		'menu_icon'       => 'dashicons-email-alt',
		'supports'        => array( 'title', 'editor' ),
		'capability_type' => 'post',
		'map_meta_cap'    => true,
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		'show_in_rest'    => false,
	) );
} );

/* Sender details shown above the message in wp-admin. */
add_action( 'add_meta_boxes_orego_message', function () {
	add_meta_box( 'orego_message_from', 'From', function ( $post ) {
		$email = get_post_meta( $post->ID, 'ocf_email', true );
		$phone = get_post_meta( $post->ID, 'ocf_phone', true );
		echo '<p><strong>' . esc_html( get_post_meta( $post->ID, 'ocf_name', true ) ) . '</strong></p>';
		echo '<p><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></p>';
		if ( $phone ) {
			echo '<p><a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a></p>';
		}
		echo '<p>Topic: ' . esc_html( get_post_meta( $post->ID, 'ocf_topic', true ) ) . '</p>';
	}, 'orego_message', 'side', 'high' );
} );

add_filter( 'manage_orego_message_posts_columns', function ( $cols ) {
	return array(
		'cb'        => $cols['cb'],
		'title'     => 'Subject',
		'ocf_from'  => 'From',
		'ocf_topic' => 'Topic',
		'date'      => 'Received',
	);
} );

add_action( 'manage_orego_message_posts_custom_column', function ( $col, $id ) {
	if ( 'ocf_from' === $col ) {
		echo esc_html( get_post_meta( $id, 'ocf_name', true ) ) . '<br><a href="mailto:' . esc_attr( get_post_meta( $id, 'ocf_email', true ) ) . '">' . esc_html( get_post_meta( $id, 'ocf_email', true ) ) . '</a>';
	} elseif ( 'ocf_topic' === $col ) {
		echo esc_html( get_post_meta( $id, 'ocf_topic', true ) );
	}
}, 10, 2 );

function orego_contact_topics() {
	return array(
		'booking' => 'A new booking',
		'change'  => 'Change or cancel a booking',
		'long'    => 'Long-term rental',
		'other'   => 'Something else',
	);
}

/* The page holding the form must not be cached, or its security token goes stale. */
add_action( 'wp', function () {
	if ( ! is_singular() ) {
		return;
	}
	$id   = get_queried_object_id();
	$data = (string) get_post_meta( $id, '_elementor_data', true ) . (string) get_post_field( 'post_content', $id );
	if ( false !== strpos( $data, 'orego_contact_form' ) ) {
		do_action( 'litespeed_control_set_nocache', 'contact form page' );
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}
} );

/* Handle a submitted form. */
function orego_contact_handle() {
	$back = wp_get_referer();
	if ( ! $back || 0 !== strpos( $back, home_url() ) ) {
		$back = home_url( '/contact-us/' );
	}
	$back = remove_query_arg( array( 'ocf' ), $back );
	$fail = function ( $code ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'ocf', $code, $back ) . '#og-contact-form' );
		exit;
	};

	if ( ! isset( $_POST['ocf_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ocf_nonce'] ) ), 'orego_contact' ) ) {
		$fail( 'expired' );
	}

	// Bots fill the hidden field or submit instantly. Pretend it worked.
	$started = isset( $_POST['ocf_t'] ) ? absint( $_POST['ocf_t'] ) : 0;
	if ( ! empty( $_POST['ocf_website'] ) || ( time() - $started ) < 3 ) {
		wp_safe_redirect( add_query_arg( 'ocf', 'sent', $back ) . '#og-contact-form' );
		exit;
	}

	// At most 5 messages per visitor per hour.
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$limit = 'ocf_rl_' . md5( $ip . wp_salt( 'nonce' ) );
	$count = (int) get_transient( $limit );
	if ( $count >= 5 ) {
		$fail( 'limit' );
	}

	$name    = isset( $_POST['ocf_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ocf_name'] ) ) : '';
	$email   = isset( $_POST['ocf_email'] ) ? sanitize_email( wp_unslash( $_POST['ocf_email'] ) ) : '';
	$phone   = isset( $_POST['ocf_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['ocf_phone'] ) ) : '';
	$topics  = orego_contact_topics();
	$topic   = isset( $_POST['ocf_topic'], $topics[ $_POST['ocf_topic'] ] ) ? $topics[ sanitize_key( $_POST['ocf_topic'] ) ] : $topics['other'];
	$message = isset( $_POST['ocf_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ocf_message'] ) ) : '';

	$name    = mb_substr( $name, 0, 80 );
	$phone   = mb_substr( $phone, 0, 30 );
	$message = mb_substr( $message, 0, 5000 );
	if ( '' === $name || ! is_email( $email ) || mb_strlen( $message ) < 5 ) {
		$fail( 'missing' );
	}

	$id = wp_insert_post( array(
		'post_type'    => 'orego_message',
		'post_status'  => 'private',
		'post_title'   => $topic . ' - ' . $name,
		'post_content' => $message,
	), true );
	if ( is_wp_error( $id ) ) {
		$fail( 'error' );
	}
	update_post_meta( $id, 'ocf_name', $name );
	update_post_meta( $id, 'ocf_email', $email );
	update_post_meta( $id, 'ocf_phone', $phone );
	update_post_meta( $id, 'ocf_topic', $topic );
	set_transient( $limit, $count + 1, HOUR_IN_SECONDS );

	$body  = "New message from the contact page.\n\n";
	$body .= "Name: {$name}\nEmail: {$email}\n";
	$body .= $phone ? "Phone: {$phone}\n" : '';
	$body .= "Topic: {$topic}\n\n{$message}\n\n";
	$body .= 'All messages: ' . admin_url( 'edit.php?post_type=orego_message' ) . "\n";
	wp_mail(
		get_option( 'admin_email' ),
		'New message: ' . $topic . ' - ' . str_replace( array( "\r", "\n" ), '', $name ),
		$body,
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);

	wp_safe_redirect( add_query_arg( 'ocf', 'sent', $back ) . '#og-contact-form' );
	exit;
}
add_action( 'admin_post_nopriv_orego_contact', 'orego_contact_handle' );
add_action( 'admin_post_orego_contact', 'orego_contact_handle' );

/* The form itself. */
add_shortcode( 'orego_contact_form', function () {
	$state = isset( $_GET['ocf'] ) ? sanitize_key( $_GET['ocf'] ) : '';
	ob_start();
	?>
	<div class="og-cf" id="og-contact-form">
		<?php if ( 'sent' === $state ) : ?>
			<div class="og-cf__done" role="status">
				<span class="og-cf__done-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
				<h3>Message received</h3>
				<p>Thanks for writing to us. We reply within a few hours, by email or by phone if you left a number.</p>
				<a class="og-cf__again" href="<?php echo esc_url( remove_query_arg( 'ocf' ) ); ?>#og-contact-form">Send another message</a>
			</div>
		<?php else : ?>
			<?php
			$errors = array(
				'missing' => 'Please add your name, a valid email and your message.',
				'expired' => 'The form expired. Please send it again.',
				'limit'   => 'You have sent several messages already. Please wait a little or call us.',
				'error'   => 'Something went wrong. Please try again or call us.',
			);
			if ( isset( $errors[ $state ] ) ) :
				?>
				<p class="og-cf__error" role="alert"><?php echo esc_html( $errors[ $state ] ); ?></p>
			<?php endif; ?>
			<form class="og-cf__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="orego_contact">
				<?php wp_nonce_field( 'orego_contact', 'ocf_nonce', true ); ?>
				<input type="hidden" name="ocf_t" value="<?php echo esc_attr( time() ); ?>">
				<div class="og-cf__hp" aria-hidden="true"><label>Website<input type="text" name="ocf_website" tabindex="-1" autocomplete="off"></label></div>

				<div class="og-cf__row">
					<label class="og-cf__field"><span>Full name</span>
						<input type="text" name="ocf_name" required maxlength="80" autocomplete="name">
					</label>
					<label class="og-cf__field"><span>Email</span>
						<input type="email" name="ocf_email" required maxlength="120" autocomplete="email">
					</label>
				</div>
				<div class="og-cf__row">
					<label class="og-cf__field"><span>Phone or WhatsApp <em>(optional)</em></span>
						<input type="tel" name="ocf_phone" maxlength="30" autocomplete="tel">
					</label>
					<label class="og-cf__field"><span>Topic</span>
						<select name="ocf_topic">
							<?php foreach ( orego_contact_topics() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
				<label class="og-cf__field"><span>Message</span>
					<textarea name="ocf_message" rows="5" required maxlength="5000" placeholder="Your dates, pick-up airport and the car you have in mind help us answer faster."></textarea>
				</label>
				<div class="og-cf__foot">
					<button type="submit" class="og-cf__btn">Send message</button>
					<p class="og-cf__note">We only use your details to reply to you.</p>
				</div>
			</form>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'orego-contact', false, array(), '1.0' );
	wp_enqueue_style( 'orego-contact' );
	wp_add_inline_style( 'orego-contact', <<<'CSS'
.og-cf{font-family:Cairo,Tahoma,sans-serif;color:#15171C}
.og-cf__form{display:flex;flex-direction:column;gap:18px}
.og-cf__row{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.og-cf__field{display:flex;flex-direction:column;gap:8px;margin:0}
.og-cf__field>span{font-size:14px;font-weight:600;color:#15171C}
.og-cf__field em{font-style:normal;font-weight:400;color:#6B7280}
.og-cf input[type=text],.og-cf input[type=email],.og-cf input[type=tel],.og-cf select,.og-cf textarea{width:100%;box-sizing:border-box;font:inherit;font-size:16px;color:#15171C;background:#FAF7F2;border:1px solid #E4DDD0;border-radius:12px;padding:13px 16px;line-height:1.5;transition:border-color .15s,box-shadow .15s,background-color .15s;-webkit-appearance:none;appearance:none}
.og-cf select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5l5 5 5-5' fill='none' stroke='%239A7434' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 16px center;padding-inline-end:42px}
html[dir=rtl] .og-cf select{background-position:left 16px center}
.og-cf textarea{resize:vertical;min-height:140px}
.og-cf textarea::placeholder{color:#8A8F98}
.og-cf input:hover,.og-cf select:hover,.og-cf textarea:hover{border-color:#D4C6AC}
.og-cf input:focus,.og-cf select:focus,.og-cf textarea:focus{outline:none;background-color:#FFFFFF;border-color:#B8914F;box-shadow:0 0 0 4px rgba(212,178,124,.28)}
.og-cf__hp{position:absolute!important;left:-9999px;width:1px;height:1px;overflow:hidden}
.og-cf__foot{display:flex;align-items:center;gap:18px;flex-wrap:wrap;padding-top:4px}
.og-cf__btn{font-family:inherit;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#0B0A09;background:#EBCB94;border:0;border-radius:8px;padding:16px 30px;cursor:pointer;transition:background-color .15s,transform .1s}
html[dir=rtl] .og-cf__btn{letter-spacing:0}
.og-cf__btn:hover{background:#D4B27C}
.og-cf__btn:active{transform:translateY(1px)}
.og-cf__btn:focus-visible{outline:2px solid #0B0A09;outline-offset:3px}
.og-cf__note{margin:0;font-size:13px;color:#6B7280}
.og-cf__error{margin:0 0 18px;padding:12px 16px;border-radius:12px;background:#FDF0EE;border:1px solid #F1C9C2;color:#9B2C1F;font-size:14px}
.og-cf__done{text-align:start;padding:8px 0}
.og-cf__done-icon{display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:50%;background:#0B0A09;color:#EBCB94;margin-bottom:18px}
.og-cf__done h3{font-size:26px;font-weight:800;line-height:1.15;margin:0 0 10px;color:#15171C}
.og-cf__done p{font-size:16px;line-height:1.7;color:#6B7280;margin:0 0 18px;max-width:46ch}
.og-cf__again{font-weight:700;color:#9A7434;text-decoration:underline;text-underline-offset:3px}
@media (max-width:640px){.og-cf__row{grid-template-columns:1fr}.og-cf__btn{width:100%}}
CSS
	);
} );
