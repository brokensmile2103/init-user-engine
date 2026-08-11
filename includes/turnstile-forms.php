<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Mở rộng Cloudflare Turnstile (đã cấu hình ở Settings) sang các form mặc định
 * của WordPress: Login (wp-login.php + login modal dùng wp_login_form()),
 * Register (wp-login.php?action=register) và Lost Password (wp-login.php?action=lostpassword).
 *
 * Toàn bộ tính năng trong file này chỉ hoạt động khi:
 * 1. Đã nhập đủ Site Key + Secret Key ở mục "Cloudflare Turnstile"
 * 2. "Disable Captcha" đang tắt
 * 3. Option tương ứng (protect_wp_login_form / protect_wp_register_form / protect_wp_lostpassword_form) đang bật
 */

/**
 * Kiểm tra 1 form cụ thể (login|register|lostpassword) có nên được bảo vệ bởi Turnstile hay không.
 *
 * @param string $form 'login', 'register', hoặc 'lostpassword'.
 * @return bool
 */
function init_plugin_suite_user_engine_turnstile_should_protect( $form ) {
	$settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );

	// "Disable Captcha" là công tắc tổng, tắt hết mọi captcha kể cả Turnstile.
	if ( ! empty( $settings['disable_captcha'] ) ) {
		return false;
	}

	$has_turnstile = ! empty( $settings['turnstile_site_key'] ) && ! empty( $settings['turnstile_secret_key'] );
	if ( ! $has_turnstile ) {
		return false;
	}

	$option_map = [
		'login'        => 'protect_wp_login_form',
		'register'     => 'protect_wp_register_form',
		'lostpassword' => 'protect_wp_lostpassword_form',
	];

	if ( ! isset( $option_map[ $form ] ) ) {
		return false;
	}

	return ! empty( $settings[ $option_map[ $form ] ] );
}

/**
 * Xuất markup widget Turnstile dùng chung cho mọi form.
 *
 * @param string $element_id ID phần tử, truyền riêng cho mỗi form để tránh đụng nhau trong DOM.
 * @param string $size       Kích thước widget: 'flexible' (co giãn, tối thiểu 300px — dùng cho khung rộng
 *                            như modal của plugin) hoặc 'compact' (150px cố định — có thể dùng cho
 *                            #loginform gốc của WordPress vì khung đó hẹp hơn 300px, flexible vẫn sẽ tràn).
 * @return void
 */
function init_plugin_suite_user_engine_render_turnstile_field( $element_id, $size = 'flexible' ) {
	$settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
	$site_key = $settings['turnstile_site_key'] ?? '';
	$theme    = $settings['turnstile_theme'] ?? 'auto';
	$theme    = in_array( $theme, [ 'auto', 'light', 'dark' ], true ) ? $theme : 'auto';
	$size     = in_array( $size, [ 'flexible', 'compact', 'normal' ], true ) ? $size : 'flexible';
	?>
	<div id="<?php echo esc_attr( $element_id ); ?>" class="cf-turnstile"
		data-sitekey="<?php echo esc_attr( $site_key ); ?>"
		data-theme="<?php echo esc_attr( $theme ); ?>"
		data-size="<?php echo esc_attr( $size ); ?>"
		style="margin-bottom:10px">
	</div>
	<?php
}

// ==========================================================
// RENDER: form Login trong modal mặc định của plugin
// (login-form.php dùng wp_login_form(), hook login_form_middle
// là chỗ duy nhất chèn được nội dung vào bên trong thẻ <form> đó)
// ==========================================================
add_filter( 'login_form_middle', function ( $content, $args ) {
	if ( ! init_plugin_suite_user_engine_turnstile_should_protect( 'login' ) ) {
		return $content;
	}

	ob_start();
	init_plugin_suite_user_engine_render_turnstile_field( 'iue-turnstile-login', 'normal' );
	return $content . ob_get_clean();
}, 10, 2 );

// ==========================================================
// RENDER: form mặc định trên wp-login.php (login/register/lostpassword)
// ==========================================================
add_action( 'login_form', function () {
	if ( init_plugin_suite_user_engine_turnstile_should_protect( 'login' ) ) {
		init_plugin_suite_user_engine_render_turnstile_field( 'iue-turnstile-wplogin', 'normal' );
	}
} );

add_action( 'register_form', function () {
	if ( init_plugin_suite_user_engine_turnstile_should_protect( 'register' ) ) {
		init_plugin_suite_user_engine_render_turnstile_field( 'iue-turnstile-wpregister', 'normal' );
	}
} );

add_action( 'lostpassword_form', function () {
	if ( init_plugin_suite_user_engine_turnstile_should_protect( 'lostpassword' ) ) {
		init_plugin_suite_user_engine_render_turnstile_field( 'iue-turnstile-wplostpassword', 'normal' );
	}
} );

// ==========================================================
// ENQUEUE: script Turnstile (implicit render) riêng cho wp-login.php
// Modal frontend đã có cơ chế lazy-load riêng trong guest.js, không dùng chung với đây.
// ==========================================================
add_action( 'login_enqueue_scripts', function () {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để quyết định enqueue, không xử lý dữ liệu.
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'login';
	$action = '' !== $action ? $action : 'login';

	$form_by_action = [
		'login'            => 'login',
		'register'         => 'register',
		'lostpassword'     => 'lostpassword',
		'retrievepassword' => 'lostpassword',
	];

	if ( ! isset( $form_by_action[ $action ] ) ) {
		return;
	}

	if ( ! init_plugin_suite_user_engine_turnstile_should_protect( $form_by_action[ $action ] ) ) {
		return;
	}

	// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion, PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- Cloudflare Turnstile bắt buộc phải tải script trực tiếp từ challenges.cloudflare.com (không thể tự host/bundle) vì widget cần giao tiếp trực tiếp với hạ tầng xác thực của Cloudflare để hoạt động, tương tự cách Google reCAPTCHA yêu cầu tải script từ Google. Script này tự quản lý version của chính nó, không dùng version của WP core.
	wp_enqueue_script( 'init-user-engine-turnstile-api', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, true );
} );

// ==========================================================
// TEST API: cho phép admin kiểm tra Secret Key ngay tại trang Settings, trước khi lưu
// ==========================================================

/**
 * Gửi 1 request xác minh với token giả tới Cloudflare để kiểm tra Secret Key có hợp lệ hay không.
 * Không thể xác minh Site Key ở phía server — Site Key chỉ thật sự được xác nhận khi widget render trên trình duyệt.
 *
 * @param string $secret_key Secret Key cần kiểm tra (chưa lưu vào DB, đọc trực tiếp từ form).
 * @return true|WP_Error
 */
function init_plugin_suite_user_engine_test_turnstile_secret_key( $secret_key ) {
	$secret_key = trim( (string) $secret_key );

	if ( '' === $secret_key ) {
		return new WP_Error( 'iue_turnstile_test_missing_secret', __( 'Please enter both Site Key and Secret Key before testing.', 'init-user-engine' ) );
	}

	// phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Đây là lệnh gọi API xác thực server-to-server tới Cloudflare Turnstile (không phải tải asset JS/CSS), dùng token giả để kiểm tra Secret Key có hợp lệ hay không.
	$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
		'timeout' => 8,
		'body'    => [
			'secret'   => $secret_key,
			'response' => 'iue-test-token-' . wp_generate_password( 12, false, false ),
		],
	] );

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'iue_turnstile_test_http_error', __( "Could not reach Cloudflare. Please check your server's outbound connection.", 'init-user-engine' ) );
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'iue_turnstile_test_bad_response', __( 'Cloudflare returned an unexpected response. Please try again.', 'init-user-engine' ) );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) ) {
		return new WP_Error( 'iue_turnstile_test_parse_error', __( 'Could not parse the response from Cloudflare.', 'init-user-engine' ) );
	}

	// Token luôn giả nên success sẽ luôn false — cái cần đọc là error-codes để biết Secret Key có hợp lệ không.
	$error_codes = isset( $body['error-codes'] ) && is_array( $body['error-codes'] ) ? $body['error-codes'] : [];

	if ( in_array( 'invalid-input-secret', $error_codes, true ) || in_array( 'missing-input-secret', $error_codes, true ) ) {
		return new WP_Error( 'iue_turnstile_test_invalid_secret', __( 'Secret Key is invalid. Please double-check it in your Cloudflare Turnstile dashboard.', 'init-user-engine' ) );
	}

	return true;
}

add_filter( 'authenticate', 'init_plugin_suite_user_engine_verify_turnstile_on_login', 30, 3 );
/**
 * Xác thực Turnstile khi có submit form login qua wp-login.php.
 *
 * Chạy ở priority 30 (sau wp_authenticate_username_password/wp_authenticate_email_password ở 20,
 * cùng mức với wp_authenticate_cookie ở 30) để không phá vỡ luồng xác thực gốc của WordPress.
 *
 * @param WP_User|WP_Error|null $user     Kết quả xác thực hiện tại từ các callback trước đó.
 * @param string                $username Username hoặc email được submit.
 * @param string                $password Password được submit.
 * @return WP_User|WP_Error|null
 */
function init_plugin_suite_user_engine_verify_turnstile_on_login( $user, $username, $password ) {
	// Chỉ can thiệp đúng lượt submit form login thật sự (bỏ qua cookie auth, XML-RPC, Application Passwords, WP-CLI...).
	if ( empty( $GLOBALS['pagenow'] ) || 'wp-login.php' !== $GLOBALS['pagenow'] ) {
		return $user;
	}

	if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		return $user;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- wp-login.php không dùng nonce cho form login mặc định của WordPress.
	if ( ! isset( $_POST['log'], $_POST['pwd'] ) ) {
		return $user;
	}

	if ( ! init_plugin_suite_user_engine_turnstile_should_protect( 'login' ) ) {
		return $user;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- xem giải thích ở trên.
	$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
	$ip    = init_plugin_suite_user_engine_get_real_ip();

	$verify_res = init_plugin_suite_user_engine_verify_turnstile( $token, $ip );
	if ( is_wp_error( $verify_res ) ) {
		return new WP_Error( 'iue_turnstile_failed', __( '<strong>Error:</strong> Captcha verification failed. Please try again.', 'init-user-engine' ) );
	}

	return $user;
}

// ==========================================================
// VERIFY: Register mặc định của WordPress (wp-login.php?action=register)
// ==========================================================
add_filter( 'registration_errors', 'init_plugin_suite_user_engine_verify_turnstile_on_wp_register', 30, 3 );
/**
 * Xác thực Turnstile khi submit form đăng ký mặc định của WordPress (chỉ khả dụng khi
 * "Anyone can register" đang bật ở Settings → General).
 *
 * @param WP_Error $errors                Lỗi hiện có (nếu có) từ các bước validate trước đó.
 * @param string   $sanitized_user_login  Username đã sanitize.
 * @param string   $user_email            Email được submit.
 * @return WP_Error
 */
function init_plugin_suite_user_engine_verify_turnstile_on_wp_register( $errors, $sanitized_user_login, $user_email ) {
	if ( ! init_plugin_suite_user_engine_turnstile_should_protect( 'register' ) ) {
		return $errors;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- wp-login.php không dùng nonce cho form register mặc định của WordPress.
	$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
	$ip    = init_plugin_suite_user_engine_get_real_ip();

	$verify_res = init_plugin_suite_user_engine_verify_turnstile( $token, $ip );
	if ( is_wp_error( $verify_res ) ) {
		$errors->add( 'iue_turnstile_failed', __( '<strong>Error:</strong> Captcha verification failed. Please try again.', 'init-user-engine' ) );
	}

	return $errors;
}

// ==========================================================
// VERIFY: Lost Password (wp-login.php?action=lostpassword)
// ==========================================================
add_action( 'lostpassword_post', 'init_plugin_suite_user_engine_verify_turnstile_on_lostpassword', 10, 1 );
/**
 * Xác thực Turnstile khi submit form "Lost your password?" mặc định của WordPress.
 *
 * @param WP_Error $errors Đối tượng lỗi được WordPress truyền vào (theo tham chiếu đối tượng).
 * @return void
 */
function init_plugin_suite_user_engine_verify_turnstile_on_lostpassword( $errors ) {
	if ( ! init_plugin_suite_user_engine_turnstile_should_protect( 'lostpassword' ) ) {
		return;
	}

	if ( ! ( $errors instanceof WP_Error ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- wp-login.php không dùng nonce cho form lostpassword mặc định của WordPress.
	$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
	$ip    = init_plugin_suite_user_engine_get_real_ip();

	$verify_res = init_plugin_suite_user_engine_verify_turnstile( $token, $ip );
	if ( is_wp_error( $verify_res ) ) {
		$errors->add( 'iue_turnstile_failed', __( '<strong>Error:</strong> Captcha verification failed. Please try again.', 'init-user-engine' ) );
	}
}
