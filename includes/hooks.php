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
			// translators: %1$d is EXP amount, %2$d is coin amount, %3$s is the coin label (e.g., Coin, Xu).
			__( 'You received +%1$d EXP and +%2$d %3$s for signing up. Let the journey begin!', 'init-user-engine' ),
			$exp,
			$coin,
			init_plugin_suite_user_engine_get_coin_label()
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
			// translators: %1$d = EXP, %2$d = coin amount, %3$s = coin label (e.g., Coin, Xu).
			__( 'You received +%1$d EXP and +%2$d %3$s for your order. Keep growing!', 'init-user-engine' ),
			$exp,
			$coin,
			init_plugin_suite_user_engine_get_coin_label()
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
add_filter( 'pre_get_avatar_data', 'init_plugin_suite_user_engine_filter_avatar_data', 9999, 2 );

/**
 * Áp avatar của Init User Engine (hoặc avatar mặc định khi tắt Gravatar) vào dữ liệu avatar.
 *
 * @param array $args        Dữ liệu avatar (size, url, found_avatar...).
 * @param mixed $id_or_email User ID, email, WP_User, WP_Comment, WP_Post...
 * @return array
 */
function init_plugin_suite_user_engine_filter_avatar_data( $args, $id_or_email ) {
	$user_id = 0;

	if ( is_numeric( $id_or_email ) ) {
		$user_id = (int) $id_or_email;
	} elseif ( is_object( $id_or_email ) ) {
		// Comment object / WP_User / WP_Comment etc...
		if ( isset( $id_or_email->user_id ) && $id_or_email->user_id ) {
			$user_id = (int) $id_or_email->user_id;
		} elseif ( $id_or_email instanceof WP_User ) {
			$user_id = (int) $id_or_email->ID;
		}
	} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$u       = get_user_by( 'email', $id_or_email );
		$user_id = $u ? (int) $u->ID : 0;
	}

	if ( ! $user_id ) {
		return $args; // Không xác định user => để mặc định.
	}

	// Tùy chọn disable gravatar.
	$options          = get_option( INIT_PLUGIN_SUITE_IUE_OPTION );
	$disable_gravatar = ! empty( $options['disable_gravatar'] );

	// Lấy avatar IUE.
	$custom_50 = get_user_meta( $user_id, 'iue_custom_avatar', true );
	if ( $custom_50 && filter_var( $custom_50, FILTER_VALIDATE_URL ) ) {
		$size = (int) ( $args['size'] ?? 50 );

		// Map kích thước đơn giản 50/80.
		$use_url = $custom_50;
		if ( $size >= 80 ) {
			$use_url = str_replace( '-50.', '-80.', $custom_50 );
		}

		// Gán lại dữ liệu avatar.
		$args['url']          = esc_url( $use_url );
		$args['found_avatar'] = true; // Báo với WP là đã tìm được.
		$args['height']       = $size;
		$args['width']        = $size;

		return $args; // QUAN TRỌNG: return sớm để override mọi thứ khác.
	}

	// Không có avatar IUE.
	if ( $disable_gravatar ) {
		$args['url']          = trailingslashit( INIT_PLUGIN_SUITE_IUE_ASSETS_URL ) . 'img/default-avatar.svg';
		$args['found_avatar'] = true;
		$args['height']       = (int) ( $args['size'] ?? 50 );
		$args['width']        = (int) ( $args['size'] ?? 50 );
		return $args;
	}

	// Mặc định: để Nextend/WP xử lý.
	return $args;
}

/**
 * (Tùy chọn) Đồng bộ filter get_avatar_url để những nơi gọi trực tiếp URL vẫn được override
 * Ưu tiên cao để chắc chắn thắng
 *
 * Gọi thẳng hàm xử lý của plugin thay vì apply_filters( 'pre_get_avatar_data' ):
 * get_avatar_url() vốn đã chạy pre_get_avatar_data 1 lần bên trong get_avatar_data(),
 * chạy lại toàn bộ filter đó (của mọi plugin khác) cho MỖI avatar là thừa và tốn kém
 * trên các trang có nhiều avatar (danh sách bình luận, bảng xếp hạng...).
 */
add_filter( 'get_avatar_url', function( $url, $id_or_email, $args ) {
	$data = init_plugin_suite_user_engine_filter_avatar_data(
		array(
			'size' => $args['size'] ?? 50,
			'url'  => $url,
		),
		$id_or_email
	);

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
		// translators: %1$d = EXP, %2$d = coin amount, %3$s = coin label (e.g., Coin, Xu).
		__( 'You earned +%1$d EXP and +%2$d %3$s for submitting a review. Keep it up!', 'init-user-engine' ),
		15,
		5,
		init_plugin_suite_user_engine_get_coin_label()
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

/**
 * Cổng "Yêu cầu đăng nhập" (Require Login Gate)
 *
 * Khi bật ở Settings, toàn bộ frontend (trừ các request hệ thống như REST,
 * AJAX, cron, feed, robots.txt...) sẽ hiển thị một trang trống mang màu chủ đề
 * của plugin thay vì nội dung thật của trang, và tự động mở modal đăng nhập
 * có sẵn của Init User Engine. wp_head()/wp_footer() vẫn được gọi đầy đủ nên
 * mọi hook khác của theme/plugin (bao gồm chính modal đăng nhập) vẫn hoạt động
 * bình thường.
 */
add_action( 'template_redirect', 'init_plugin_suite_user_engine_maybe_require_login' );
function init_plugin_suite_user_engine_maybe_require_login() {
	if ( is_user_logged_in() ) {
		return;
	}

	$settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
	if ( empty( $settings['require_login'] ) ) {
		return;
	}

	// Không chặn các request không phải là một trang xem thông thường.
	if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() || is_robots() || is_trackback() ) {
		return;
	}

	/**
	 * Cho phép theme/plugin khác loại trừ một request cụ thể khỏi cổng đăng nhập
	 * (ví dụ trang callback thanh toán, webhook, v.v.).
	 *
	 * @param bool $bypass Trả về true để bỏ qua cổng đăng nhập cho request hiện tại.
	 */
	if ( apply_filters( 'init_plugin_suite_user_engine_require_login_bypass', false ) ) {
		return;
	}

	init_plugin_suite_user_engine_render_require_login_gate();
	exit;
}

/**
 * In ra trang trống của cổng đăng nhập và mở modal đăng nhập có sẵn.
 * CSS/JS được tách sang 2 file riêng (assets/css/require-login.css và
 * assets/js/require-login.js) thay vì in inline để tuân thủ WPCS.
 */
function init_plugin_suite_user_engine_render_require_login_gate() {
	add_action( 'wp_enqueue_scripts', 'init_plugin_suite_user_engine_enqueue_require_login_assets', 20 );

	nocache_headers();

	$login_url = wp_login_url( home_url( add_query_arg( null, null ) ) );
	?><!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
		<?php wp_head(); ?>
	</head>
	<body <?php body_class( 'iue-require-login' ); ?>>
		<div class="iue-require-login-gate">
			<span class="iue-require-login-spinner" aria-hidden="true"></span>
			<p><?php esc_html_e( 'This site is available to logged-in members only.', 'init-user-engine' ); ?></p>
			<noscript>
				<p>
					<a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Click here to log in', 'init-user-engine' ); ?></a>
				</p>
			</noscript>
		</div>
		<?php wp_footer(); ?>
	</body>
	</html>
	<?php
}

/**
 * Enqueue CSS/JS riêng cho trang cổng đăng nhập. Phụ thuộc vào script/style
 * "guest" hiện có (đã tự động được enqueue vì is_user_logged_in() === false)
 * để dùng chung biến CSS màu chủ đề và hàm window.openLoginModal.
 */
function init_plugin_suite_user_engine_enqueue_require_login_assets() {
	wp_enqueue_style(
		'init-user-engine-require-login',
		INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'css/require-login.css',
		[ 'init-user-engine-guest' ],
		INIT_PLUGIN_SUITE_IUE_VERSION
	);

	wp_enqueue_script(
		'init-user-engine-require-login',
		INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'js/require-login.js',
		[ 'init-user-engine-guest' ],
		INIT_PLUGIN_SUITE_IUE_VERSION,
		true
	);
}

/**
 * Xử lý đăng nhập sai (sai tài khoản/mật khẩu) khi form được submit từ modal
 * đăng nhập của plugin (wp_login_form(), render trong templates/login-form.php).
 *
 * Mặc định, WordPress sẽ chuyển hướng lỗi này về wp-login.php — trải nghiệm
 * không phù hợp với các site chỉ dùng modal đăng nhập trên frontend. Thay vào
 * đó, hàm này đưa người dùng quay lại đúng trang họ vừa đứng, kèm 2 tham số
 * tạm thời trên URL để assets/js/guest.js tự mở lại modal và hiển thị thông
 * báo lỗi phù hợp:
 * - iue_login_failed=1     : cờ báo có lỗi đăng nhập cần xử lý
 * - iue_login_code=<code>  : mã lỗi (một trong các giá trị $allowed_codes bên dưới)
 *
 * Chỉ can thiệp khi request đến từ một trang frontend thông thường. Nếu người
 * dùng đăng nhập trực tiếp tại wp-login.php hoặc trong khu vực quản trị, hành
 * vi mặc định của WordPress được giữ nguyên.
 */
add_action( 'wp_login_failed', 'init_plugin_suite_user_engine_redirect_failed_login', 10, 2 );
function init_plugin_suite_user_engine_redirect_failed_login( $username, $error = null ) {
	unset( $username ); // Không cần dùng, tránh lộ username ra URL/log.

	$referer = wp_get_referer();

	if ( empty( $referer )
		|| false !== strpos( $referer, 'wp-login.php' )
		|| false !== strpos( $referer, 'wp-admin' )
	) {
		return;
	}

	$error_code = '';
	if ( $error instanceof WP_Error ) {
		$codes      = $error->get_error_codes();
		$error_code = ! empty( $codes ) ? sanitize_key( $codes[0] ) : '';
	}

	// Whitelist mã lỗi: chỉ cho phép các mã đăng nhập tiêu chuẩn của WordPress
	// (đây cũng là các mã mà form đăng nhập mặc định của WordPress vẫn tự hiển thị,
	// nên không phát sinh rủi ro dò tài khoản mới so với hành vi gốc).
	$allowed_codes = [ 'invalid_username', 'invalid_email', 'incorrect_password', 'empty_username', 'empty_password' ];
	if ( ! in_array( $error_code, $allowed_codes, true ) ) {
		$error_code = 'invalid_login';
	}

	$redirect_url = add_query_arg(
		[
			'iue_login_failed' => '1',
			'iue_login_code'   => $error_code,
		],
		$referer
	);

	wp_safe_redirect( $redirect_url );
	exit;
}

add_action( 'lost_password', 'init_plugin_suite_user_engine_redirect_failed_lostpassword', 10, 1 );

/**
 * Xử lý lỗi khi form "Quên mật khẩu" trong modal đăng nhập được submit
 * (templates/lostpassword-form.php — form POST thẳng tới wp-login.php?action=lostpassword).
 *
 * - Thành công: WordPress tự chuyển hướng theo field redirect_to (do guest.js
 *   điền sẵn URL trang hiện tại kèm iue_lostpass=sent), không cần xử lý ở đây.
 * - Thất bại: mặc định WordPress hiển thị lỗi ngay tại wp-login.php. Hàm này
 *   đưa người dùng quay lại đúng trang họ vừa đứng, kèm 2 tham số tạm thời để
 *   guest.js tự mở lại modal ở form "Quên mật khẩu" và hiển thị thông báo:
 *   - iue_lostpass=failed
 *   - iue_lostpass_code=<code> (một trong các giá trị $allowed_codes bên dưới)
 *
 * Chỉ can thiệp khi request là POST có cờ iue_lostpassword=1 (tức là đến từ
 * modal của plugin) và mã lỗi nằm trong whitelist. Mọi lỗi khác (vd: captcha
 * của plugin bên thứ 3) vẫn giữ hành vi gốc của WordPress để người dùng đọc
 * được đúng thông báo lỗi.
 *
 * @param WP_Error|null $errors Lỗi được WordPress truyền vào action 'lost_password'.
 * @return void
 */
function init_plugin_suite_user_engine_redirect_failed_lostpassword( $errors = null ) {
	if ( ! ( $errors instanceof WP_Error ) || ! $errors->has_errors() ) {
		return;
	}

	if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- wp-login.php không dùng nonce cho form lostpassword mặc định của WordPress; chỉ đọc cờ để quyết định chuyển hướng.
	if ( empty( $_POST['iue_lostpassword'] ) ) {
		return;
	}

	// Whitelist mã lỗi: đều là các mã mà form "Lost your password?" gốc của
	// WordPress vẫn tự hiển thị, nên không phát sinh rủi ro dò tài khoản mới.
	$allowed_codes = [
		'empty_username',
		'invalid_email',
		'invalidcombo',
		'retrieve_password_email_failure',
		'no_password_reset',
		'iue_turnstile_failed',
	];

	$error_code = sanitize_key( (string) $errors->get_error_code() );
	if ( ! in_array( $error_code, $allowed_codes, true ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- xem giải thích ở trên.
	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
	if ( '' === $redirect_to ) {
		$redirect_to = (string) wp_get_referer();
	}

	$redirect_to = wp_validate_redirect( $redirect_to, '' );

	if ( '' === $redirect_to
		|| false !== strpos( $redirect_to, 'wp-login.php' )
		|| false !== strpos( $redirect_to, 'wp-admin' )
	) {
		return;
	}

	$redirect_to = remove_query_arg( [ 'iue_lostpass', 'iue_lostpass_code' ], $redirect_to );

	$redirect_url = add_query_arg(
		[
			'iue_lostpass'      => 'failed',
			'iue_lostpass_code' => $error_code,
		],
		$redirect_to
	);

	wp_safe_redirect( $redirect_url );
	exit;
}

/**
 * Dọn dẹp dữ liệu phụ trợ khi 1 user bị xóa VĨNH VIỄN khỏi WordPress.
 *
 * Phạm vi dọn dẹp (đã cân nhắc kỹ):
 * - EXP log (bảng init_user_engine_exp_log)  → xóa toàn bộ theo user_id.
 * - Inbox   (bảng init_user_engine_inbox)    → xóa toàn bộ theo user_id.
 *
 * CỐ Ý KHÔNG đụng vào:
 * - Transaction log / coin/cash (bảng init_user_engine_transaction_log):
 *   giữ nguyên để không làm mất dữ liệu thống kê, báo cáo, đối soát của
 *   toàn hệ thống (vd tổng coin/cash đã phát ra), kể cả khi user đã bị xóa.
 * - usermeta (iue_checkin_last, iue_last_login_bonus, v.v...): WordPress
 *   core đã tự xóa toàn bộ usermeta của user ngay trong wp_delete_user()/
 *   wpmu_delete_user(), TRƯỚC KHI action 'deleted_user' được bắn ra, nên
 *   không cần và không nên xử lý lại ở đây.
 *
 * Action 'deleted_user' được WordPress core bắn ra ở cả 2 trường hợp nên
 * chỉ cần đăng ký đúng 1 lần là đủ:
 * - Single site: wp_delete_user()   – wp-admin/includes/user.php
 * - Multisite:   wpmu_delete_user() – wp-includes/ms-functions.php
 *
 * @param int $user_id ID của user vừa bị xóa.
 */
add_action( 'deleted_user', 'init_plugin_suite_user_engine_purge_data_on_user_deleted', 10, 1 );
function init_plugin_suite_user_engine_purge_data_on_user_deleted( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return;
	}

	// 1) Xóa lịch sử EXP của user.
	if ( function_exists( 'init_plugin_suite_user_engine_delete_exp_log_by_user' ) ) {
		init_plugin_suite_user_engine_delete_exp_log_by_user( $user_id );
	}

	// 2) Xóa toàn bộ Inbox của user (đã tự flush cache unread bên trong).
	if ( function_exists( 'init_plugin_suite_user_engine_delete_all_inbox' ) ) {
		init_plugin_suite_user_engine_delete_all_inbox( $user_id );
	}

	/**
	 * Cho phép plugin/add-on khác dọn dẹp thêm dữ liệu riêng của họ khi
	 * 1 user bị xóa vĩnh viễn khỏi Init User Engine (vd: log riêng, cache
	 * riêng của họ...). KHÔNG dùng hook này để xóa transaction log — đó
	 * là quyết định thiết kế có chủ đích, xem ghi chú phía trên.
	 *
	 * @param int $user_id ID của user vừa bị xóa.
	 */
	do_action( 'init_plugin_suite_user_engine_user_data_purged', $user_id );
}

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
