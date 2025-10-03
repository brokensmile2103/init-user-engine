<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Award EXP + Coin when user comments (daily cap anchored by daily check-in)
add_action( 'wp_insert_comment', function ( $comment_id, $comment_object ) {
	$user_id = (int) ( $comment_object->user_id ?? 0 );
	if ( $user_id <= 0 ) {
		return; // chỉ user login mới được thưởng
	}

	// Chỉ bỏ qua pingback/trackback. Mọi comment thường (kể cả comment_type='comment') đều thưởng
	$ctype = (string) ( $comment_object->comment_type ?? '' );
	if ( in_array( $ctype, [ 'pingback', 'trackback' ], true ) ) {
		return;
	}

	// Lấy config (fallback mặc định nếu chưa lưu UI)
	$settings   = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
	$exp_each   = isset( $settings['comment_exp'] )       ? absint( $settings['comment_exp'] )       : 10; // default
	$coin_each  = isset( $settings['comment_coin'] )      ? absint( $settings['comment_coin'] )      : 2;  // default
	$daily_cap  = isset( $settings['comment_daily_cap'] ) ? absint( $settings['comment_daily_cap'] ) : 0;  // 0 = unlimited

	// Nếu cả 2 đều 0 thì thôi
	if ( $exp_each <= 0 && $coin_each <= 0 ) {
		return;
	}

	// Neo bằng ngày check-in cuối cùng
	$checkin_last = (string) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_last', '' );

	// Anchor + counter cho comment-reward
	$anchor = (string) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_comment_anchor', '' );
	$count  = (int)    init_plugin_suite_user_engine_get_meta( $user_id, 'iue_comment_awarded_count', 0 );

	// Nếu khác mốc check-in -> reset counter và cập nhật anchor
	if ( $anchor !== $checkin_last ) {
		$anchor = $checkin_last;
		$count  = 0;
		init_plugin_suite_user_engine_update_meta( $user_id, 'iue_comment_anchor', $anchor );
		init_plugin_suite_user_engine_update_meta( $user_id, 'iue_comment_awarded_count', $count );
	}

	// Nếu đã chạm cap (và cap > 0) thì dừng
	if ( $daily_cap > 0 && $count >= $daily_cap ) {
		return;
	}

	// Thưởng qua action sẵn có của hệ thống
	if ( $exp_each > 0 ) {
		do_action( 'init_plugin_suite_user_engine_add_exp',  $user_id, $exp_each,  'comment_post' );
	}
	if ( $coin_each > 0 ) {
		do_action( 'init_plugin_suite_user_engine_add_coin', $user_id, $coin_each, 'comment_post' );
	}

	// Tăng counter
	init_plugin_suite_user_engine_update_meta( $user_id, 'iue_comment_awarded_count', $count + 1 );
}, 10, 2 );

// Award EXP + coin on first-time post publish
add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {
	if ( $new_status === 'publish' && $old_status !== 'publish' && $post->post_type === 'post' ) {
		if ( $post->post_author ) {
			// Lấy exp và coin từ filter duy nhất
			$rewards = apply_filters( 'init_plugin_suite_user_engine_publish_post_rewards', [
				'exp'  => 20,
				'coin' => 5,
			], $post );

			// Đảm bảo có dữ liệu hợp lệ
			$exp  = isset( $rewards['exp'] )  ? (int) $rewards['exp']  : 0;
			$coin = isset( $rewards['coin'] ) ? (int) $rewards['coin'] : 0;

			if ( $exp > 0 ) {
				do_action( 'init_plugin_suite_user_engine_add_exp',  $post->post_author, $exp, 'publish_post' );
			}
			if ( $coin > 0 ) {
				do_action( 'init_plugin_suite_user_engine_add_coin', $post->post_author, $coin, 'publish_post' );
			}
		}
	}
}, 10, 3 );

// Award EXP + coin on user registration
add_action( 'user_register', function ( $user_id ) {
	// Lấy exp và coin từ filter duy nhất
	$rewards = apply_filters( 'init_plugin_suite_user_engine_user_register_rewards', [
		'exp'  => 50,
		'coin' => 20,
	], $user_id );

	$exp  = isset( $rewards['exp'] )  ? (int) $rewards['exp']  : 0;
	$coin = isset( $rewards['coin'] ) ? (int) $rewards['coin'] : 0;

	if ( $exp > 0 ) {
		do_action( 'init_plugin_suite_user_engine_add_exp',  $user_id, $exp, 'user_register' );
	}
	if ( $coin > 0 ) {
		do_action( 'init_plugin_suite_user_engine_add_coin', $user_id, $coin, 'user_register' );
	}

	// Gửi inbox thông báo cho user
	if ( $exp > 0 || $coin > 0 ) {
		$content = sprintf(
			// translators: %1$d is EXP amount, %2$d is coin amount
			__( 'You received +%1$d EXP and +%2$d coins for signing up. Let the journey begin!', 'init-user-engine' ),
			$exp,
			$coin
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
	}
});

// Award EXP + coin when user updates profile (once only)
add_action( 'profile_update', function ( $user_id, $old_user_data ) {
	$already = get_user_meta( $user_id, 'iue_profile_bonus_given', true );
	if ( $already === '1' ) {
		return;
	}

	update_user_meta( $user_id, 'iue_profile_bonus_given', 1 );

	// Lấy exp và coin từ filter duy nhất
	$rewards = apply_filters( 'init_plugin_suite_user_engine_update_profile_rewards', [
		'exp'  => 30,
		'coin' => 10,
	], $user_id, $old_user_data );

	$exp  = isset( $rewards['exp'] )  ? (int) $rewards['exp']  : 0;
	$coin = isset( $rewards['coin'] ) ? (int) $rewards['coin'] : 0;

	if ( $exp > 0 ) {
		do_action( 'init_plugin_suite_user_engine_add_exp',  $user_id, $exp, 'update_profile' );
	}
	if ( $coin > 0 ) {
		do_action( 'init_plugin_suite_user_engine_add_coin', $user_id, $coin, 'update_profile' );
	}
}, 10, 2 );

// Award EXP + coin on first login of the day
add_action( 'init', function () {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id = get_current_user_id();
	$today   = init_plugin_suite_user_engine_today();
	$last    = get_user_meta( $user_id, 'iue_last_login_bonus', true );

	if ( $last === $today ) {
		return;
	}

	update_user_meta( $user_id, 'iue_last_login_bonus', $today );

	// Lấy exp và coin từ filter duy nhất
	$rewards = apply_filters( 'init_plugin_suite_user_engine_daily_login_rewards', [
		'exp'  => 10,
		'coin' => 5,
	], $user_id, $today );

	$exp  = isset( $rewards['exp'] )  ? (int) $rewards['exp']  : 0;
	$coin = isset( $rewards['coin'] ) ? (int) $rewards['coin'] : 0;

	if ( $exp > 0 ) {
		do_action( 'init_plugin_suite_user_engine_add_exp',  $user_id, $exp, 'daily_login' );
	}
	if ( $coin > 0 ) {
		do_action( 'init_plugin_suite_user_engine_add_coin', $user_id, $coin, 'daily_login' );
	}
});

// Award EXP + coin when user completes a WooCommerce order
add_action( 'woocommerce_order_status_completed', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->get_user_id() ) {
		return;
	}

	$user_id = $order->get_user_id();
	$total   = (float) $order->get_total();

	// Default reward calculation
	$default_rewards = [
		'coin' => max( 1, floor( $total / 10000 ) ), // 10k = 1 coin
		'exp'  => max( 5, floor( $total / 5000 ) ),  // 5k = 1 exp
	];

	// Cho phép custom reward qua filter
	$rewards = apply_filters(
		'init_plugin_suite_user_engine_woo_order_rewards',
		$default_rewards,
		$user_id,
		$order,
		$total
	);

	$exp  = isset( $rewards['exp'] )  ? (int) $rewards['exp']  : 0;
	$coin = isset( $rewards['coin'] ) ? (int) $rewards['coin'] : 0;

	if ( $exp > 0 ) {
		do_action( 'init_plugin_suite_user_engine_add_exp',  $user_id, $exp, 'woo_order' );
	}
	if ( $coin > 0 ) {
		do_action( 'init_plugin_suite_user_engine_add_coin', $user_id, $coin, 'woo_order' );
	}

	// Gửi thông báo hộp thư đến
	if ( $exp > 0 || $coin > 0 ) {
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
	}
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

/**
 * Force IUE avatar to take precedence over Nextend (and others)
 * - Hook vào pre_get_avatar_data (ưu tiên rất cao)
 * - Nếu user có meta 'iue_custom_avatar' thì dùng nó, set found_avatar=true
 * - Giữ nguyên các args khác để tránh side effects
 */
add_filter( 'pre_get_avatar_data', function( $args, $id_or_email ) {
    $user_id = 0;

    if ( is_numeric( $id_or_email ) ) {
        $user_id = (int) $id_or_email;
    } elseif ( is_object( $id_or_email ) ) {
        // Comment object / WP_User / WP_Comment etc.
        if ( isset( $id_or_email->user_id ) && $id_or_email->user_id ) {
            $user_id = (int) $id_or_email->user_id;
        } elseif ( $id_or_email instanceof WP_User ) {
            $user_id = (int) $id_or_email->ID;
        }
    } elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
        $u = get_user_by( 'email', $id_or_email );
        $user_id = $u ? (int) $u->ID : 0;
    }

    if ( ! $user_id ) {
        return $args; // Không xác định user => để mặc định
    }

    // Tùy chọn disable gravatar
    $options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION );
    $disable_gravatar = ! empty( $options['disable_gravatar'] );

    // Lấy avatar IUE
    $custom_50 = get_user_meta( $user_id, 'iue_custom_avatar', true );
    if ( $custom_50 && filter_var( $custom_50, FILTER_VALIDATE_URL ) ) {
        $size = (int) ( $args['size'] ?? 50 );

        // Map kích thước đơn giản 50/80
        $use_url = $custom_50;
        if ( $size >= 80 ) {
            $use_url = str_replace( '-50.', '-80.', $custom_50 );
        }

        // Gán lại dữ liệu avatar
        $args['url']          = esc_url( $use_url );
        $args['found_avatar'] = true;         // báo với WP là đã tìm được
        $args['height']       = $size;
        $args['width']        = $size;

        return $args; // QUAN TRỌNG: return sớm để override mọi thứ khác
    }

    // Không có avatar IUE:
    if ( $disable_gravatar ) {
        $args['url']          = trailingslashit( INIT_PLUGIN_SUITE_IUE_ASSETS_URL ) . 'img/default-avatar.svg';
        $args['found_avatar'] = true;
        $args['height']       = (int) ( $args['size'] ?? 50 );
        $args['width']        = (int) ( $args['size'] ?? 50 );
        return $args;
    }

    // Mặc định: để Nextend/WP xử lý
    return $args;
}, 9999, 2 );

/**
 * (Tùy chọn) Đồng bộ filter get_avatar_url để những nơi gọi trực tiếp URL vẫn được override
 * Ưu tiên cao để chắc chắn thắng
 */
add_filter( 'get_avatar_url', function( $url, $id_or_email, $args ) {
    $data = apply_filters( 'pre_get_avatar_data', array(
        'size'  => $args['size'] ?? 50,
        'url'   => $url,
    ), $id_or_email );

    return isset( $data['url'] ) ? $data['url'] : $url;
}, 9999, 3 );

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

// Hook vào action khi VIP bị gỡ
add_action( 'init_plugin_suite_user_engine_vip_removed', function( $user_id, $prev_expiry, $vip_log_after ) {
	// Tiêu đề và nội dung inbox message
	$title   = __( 'Your VIP status has been removed', 'init-user-engine' );
	$content = __( 'An administrator has cancelled your VIP membership. If you think this is a mistake, please contact support.', 'init-user-engine' );

	// Gửi tin nhắn hệ thống đến user
	if ( function_exists( 'init_plugin_suite_user_engine_send_inbox' ) ) {
		init_plugin_suite_user_engine_send_inbox(
			$user_id,
			$title,
			$content,
			'system',
			[ 'action' => 'vip_removed', 'prev_expiry' => $prev_expiry ],
			null,
			'high'
		);
	}
}, 10, 3 );
