<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Award EXP + coin when user comments
add_action( 'wp_insert_comment', function ( $comment_id, $comment_object ) {
	if ( $comment_object->user_id ) {
		do_action( 'init_plugin_suite_user_engine_add_exp',  $comment_object->user_id, 10, 'comment_post' );
		do_action( 'init_plugin_suite_user_engine_add_coin', $comment_object->user_id, 2,  'comment_post' );
	}
}, 10, 2 );

// Award EXP + coin on first-time post publish
add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {
	if ( $new_status === 'publish' && $old_status !== 'publish' && $post->post_type === 'post' ) {
		if ( $post->post_author ) {
			do_action( 'init_plugin_suite_user_engine_add_exp',  $post->post_author, 20, 'publish_post' );
			do_action( 'init_plugin_suite_user_engine_add_coin', $post->post_author, 5,  'publish_post' );
		}
	}
}, 10, 3 );

// Award EXP + coin on user registration
add_action( 'user_register', function ( $user_id ) {
	do_action( 'init_plugin_suite_user_engine_add_exp',  $user_id, 50, 'user_register' );
	do_action( 'init_plugin_suite_user_engine_add_coin', $user_id, 20, 'user_register' );

	$content = sprintf(
		// translators: %1$d is EXP amount, %2$d is coin amount
		__( 'You received +%1$d EXP and +%2$d coins for signing up. Let the journey begin!', 'init-user-engine' ),
		50,
		20
	);

	init_plugin_suite_user_engine_insert_inbox(
		$user_id,
		__( 'Welcome to the community!', 'init-user-engine' ),
		$content,
		'welcome',
		[],
		null,
		'high',
		home_url()
	);
});

// Award EXP + coin when user updates profile (once only)
add_action( 'profile_update', function ( $user_id, $old_user_data ) {
	$already = get_user_meta( $user_id, 'iue_profile_bonus_given', true );
	if ( $already === '1' ) return;

	update_user_meta( $user_id, 'iue_profile_bonus_given', 1 );
	do_action( 'init_plugin_suite_user_engine_add_exp',  $user_id, 30, 'update_profile' );
	do_action( 'init_plugin_suite_user_engine_add_coin', $user_id, 10, 'update_profile' );
}, 10, 2 );

// Award EXP + coin on first login of the day
add_action( 'init', function () {
	if ( ! is_user_logged_in() ) return;

	$user_id = get_current_user_id();
	$today   = init_plugin_suite_user_engine_today();
	$last    = get_user_meta( $user_id, 'iue_last_login_bonus', true );

	if ( $last === $today ) return;

	update_user_meta( $user_id, 'iue_last_login_bonus', $today );
	do_action( 'init_plugin_suite_user_engine_add_exp',  $user_id, 10, 'daily_login' );
	do_action( 'init_plugin_suite_user_engine_add_coin', $user_id, 5,  'daily_login' );
});

// Award EXP + coin when user completes a WooCommerce order
add_action( 'woocommerce_order_status_completed', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->get_user_id() ) return;

	$user_id = $order->get_user_id();
	$total   = (float) $order->get_total();

	// Tính thưởng dựa trên tổng tiền đơn hàng
	$coin = max( 1, floor( $total / 10000 ) ); // 10k = 1 coin
	$exp  = max( 5, floor( $total / 5000 ) );  // 5k = 1 exp

	do_action( 'init_plugin_suite_user_engine_add_exp',  $user_id, $exp,  'woo_order' );
	do_action( 'init_plugin_suite_user_engine_add_coin', $user_id, $coin, 'woo_order' );

	// Gửi thông báo hộp thư đến
	$title   = __( 'Thanks for your purchase!', 'init-user-engine' );
	$content = sprintf(
		// translators: %1$d = EXP, %2$d = coin
		__( 'You received +%1$d EXP and +%2$d coins for your order. Keep growing!', 'init-user-engine' ),
		$exp,
		$coin
	);

	init_plugin_suite_user_engine_insert_inbox(
		$user_id,
		$title,
		$content,
		'woo_reward',
		[ 'order_id' => $order_id ],
		null,
		'normal',
		$order->get_view_order_url()
	);
}, 10 );

// Someone replied to your comment
add_action( 'wp_insert_comment', function( $comment_id, $comment ) {
	// Only handle if it's a reply
	if ( ! $comment->comment_parent || ! $comment->user_id ) return;

	$parent_comment = get_comment( $comment->comment_parent );
	if ( ! $parent_comment || ! $parent_comment->user_id ) return;

	// Don't notify if replying to own comment
	if ( $parent_comment->user_id == $comment->user_id ) return;

	// Send inbox notification
	init_plugin_suite_user_engine_insert_inbox(
		$parent_comment->user_id,
		__( 'You have a new reply to your comment', 'init-user-engine' ),
		sprintf(
			// translators: 1 = comment author's name, 2 = reply content
			__( '<strong>%1$s</strong> replied to your comment: <em>%2$s</em>', 'init-user-engine' ),
			esc_html( get_comment_author( $comment ) ),
			wp_trim_words( $comment->comment_content, 20 )
		),
		'comment_reply',
		[ 'comment_id' => $comment_id ],
		null,
		'normal',
		get_comment_link( $comment_id )
	);
}, 20, 2 );

// Dùng avatar của IUE
add_filter( 'get_avatar_url', 'init_plugin_suite_user_engine_custom_avatar_url', 10, 3 );
function init_plugin_suite_user_engine_custom_avatar_url( $url, $id_or_email, $args ) {
	$user_id = 0;

	if ( is_numeric( $id_or_email ) ) {
		$user_id = (int) $id_or_email;
	} elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) ) {
		$user_id = (int) $id_or_email->user_id;
	} elseif ( is_string( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
		$user_id = $user ? $user->ID : 0;
	}

	$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION );
	$disable_gravatar = ! empty( $options['disable_gravatar'] );

	if ( $user_id ) {
		$custom_50 = get_user_meta( $user_id, 'iue_custom_avatar', true );
		if ( $custom_50 && filter_var( $custom_50, FILTER_VALIDATE_URL ) ) {
			if ( isset( $args['size'] ) && (int) $args['size'] >= 80 ) {
				$custom_80 = str_replace( '-50.', '-80.', $custom_50 );
				return esc_url( $custom_80 );
			}
			return esc_url( $custom_50 );
		}
	}

	if ( $disable_gravatar ) {
		return INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'img/default-avatar.svg';
	}

	return $url;
}

// Ẩn admin-bar
add_filter( 'show_admin_bar', function ( $show ) {
	$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION );

	if (
		! is_admin() &&
		! current_user_can( 'edit_posts' ) &&
		( $options['hide_admin_bar_subscriber'] ?? 1 )
	) {
		return false;
	}

	return $show;
});

// Award EXP + coin when user submits a multi-criteria review
add_action( 'init_plugin_suite_review_system_after_criteria_review', function ( $post_id, $user_id, $avg_score, $content, $scores ) {
	if ( ! $user_id ) return;

	do_action( 'init_plugin_suite_user_engine_add_exp',  $user_id, 15, 'submit_review' );
	do_action( 'init_plugin_suite_user_engine_add_coin', $user_id, 5,  'submit_review' );

	// Optional inbox notification
	$title = __( 'Thanks for your review!', 'init-user-engine' );
	$message = sprintf(
		// translators: %1$d = EXP, %2$d = coin
		__( 'You earned +%1$d EXP and +%2$d coins for submitting a review. Keep it up!', 'init-user-engine' ),
		15,
		5
	);

	init_plugin_suite_user_engine_insert_inbox(
		$user_id,
		$title,
		$message,
		'review_reward',
		[ 'post_id' => $post_id ],
		null,
		'normal',
		get_permalink( $post_id )
	);
}, 10, 5 );
