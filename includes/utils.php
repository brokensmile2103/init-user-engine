<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Return today's date in Y-m-d format
function init_plugin_suite_user_engine_today() {
	return current_time( 'Y-m-d' );
}

// Get the admin-configured label for the Coin virtual currency (falls back to 'Coin').
function init_plugin_suite_user_engine_get_coin_label() {
	$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
	$label   = isset( $options['label_coin'] ) ? trim( wp_strip_all_tags( (string) $options['label_coin'] ) ) : '';
	return '' !== $label ? $label : 'Coin';
}

/**
 * Có hiển thị form "Quên mật khẩu" ngay trong modal đăng nhập hay không.
 *
 * Thứ tự ưu tiên:
 * 1. Admin có đặt "Custom Lost Password URL" → luôn dùng URL đó (không dùng modal).
 * 2. Option "Lost Password in Modal" (mặc định BẬT, kể cả với site cài từ bản cũ
 *    chưa từng lưu option này).
 *
 * @param array|null $settings Settings của plugin (truyền vào để tránh gọi lại get_option()).
 * @return bool
 */
function init_plugin_suite_user_engine_use_lostpassword_modal( $settings = null ) {
	if ( ! is_array( $settings ) ) {
		$settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
		$settings = is_array( $settings ) ? $settings : [];
	}

	if ( ! empty( $settings['custom_lostpass_url'] ) ) {
		return false;
	}

	$enabled = ! isset( $settings['lostpass_modal'] ) || ! empty( $settings['lostpass_modal'] );

	/**
	 * Cho phép theme/plugin khác bật/tắt form "Quên mật khẩu" trong modal.
	 *
	 * @param bool  $enabled  Kết quả hiện tại.
	 * @param array $settings Settings của plugin.
	 */
	return (bool) apply_filters( 'init_plugin_suite_user_engine_lostpassword_modal_enabled', $enabled, $settings );
}

// Get the admin-configured label for the Cash currency (falls back to 'Cash').
function init_plugin_suite_user_engine_get_cash_label() {
	$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
	$label   = isset( $options['label_cash'] ) ? trim( wp_strip_all_tags( (string) $options['label_cash'] ) ) : '';
	return '' !== $label ? $label : 'Cash';
}

/**
 * Làm an toàn 1 ô dữ liệu trước khi ghi vào CSV, chống CSV/Formula Injection
 * (khi giá trị bắt đầu bằng =, +, -, @ hoặc tab và bị mở bằng Excel/Sheets).
 *
 * @param mixed $value Giá trị gốc.
 * @return string Giá trị an toàn để ghi vào CSV.
 */
function init_plugin_suite_user_engine_csv_safe_cell( $value ) {
	$value = (string) $value;

	if ( '' !== $value && false !== strpos( "=+-@\t\r", $value[0] ) ) {
		$value = "'" . $value;
	}

	return $value;
}

// Darken color
function init_plugin_suite_user_engine_darken_color( $hex, $percent = 20 ) {
	$hex = ltrim( $hex, '#' );

	// Hỗ trợ dạng rút gọn #abc
	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0].$hex[0] . $hex[1].$hex[1] . $hex[2].$hex[2];
	}

	// Convert từng kênh màu
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	// Trừ % từ mỗi kênh (vẫn đảm bảo >= 0)
	$r = max( 0, $r - ( $r * $percent / 100 ) );
	$g = max( 0, $g - ( $g * $percent / 100 ) );
	$b = max( 0, $b - ( $b * $percent / 100 ) );

	return sprintf( '#%02x%02x%02x', $r, $g, $b );
}

// Render level badge with dynamic color and SVG
function init_plugin_suite_user_engine_level_badge( $level = 1 ) {
	$level = absint( $level );
	if ( $level < 1 ) {
		$level = 1;
	}

	// Xác định rank theo level
	$rank = 'default';
	if ( $level >= 100 ) {
		$rank = 'diamond';
	} elseif ( $level >= 50 ) {
		$rank = 'platinum';
	} elseif ( $level >= 25 ) {
		$rank = 'silver';
	} elseif ( $level >= 10 ) {
		$rank = 'bronze';
	}

	// translators: %d is the user's level.
	$title = sprintf( __( 'Level %d', 'init-user-engine' ), $level );

	return apply_filters(
	    'init_plugin_suite_user_engine_render_level_badge',
	    sprintf(
	        '<span class="iue-badge-level iue-rank-%s" data-iue-rank="%s" data-iue-level="%d" title="%s">Lv.%d</span>',
	        esc_attr( $rank ),
	        esc_attr( $rank ),
	        (int) $level,
	        esc_attr( $title ),
	        (int) $level
	    ),
	    $level,
	    $rank
	);
}

// EXP reward handler (used in hook)
add_action( 'init_plugin_suite_user_engine_add_exp', function ( $user_id, $amount, $source ) {
	init_plugin_suite_user_engine_add_exp( $user_id, $amount );
	init_plugin_suite_user_engine_log_exp( $user_id, $amount, $source, 'add' );
}, 10, 3 );

// Coin reward handler + log transaction (used in hook)
add_action( 'init_plugin_suite_user_engine_add_coin', function ( $user_id, $amount, $source ) {
	init_plugin_suite_user_engine_add_coin( $user_id, $amount );
	init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $amount, $source, 'add' );
}, 10, 3 );

// Cash reward handler + log transaction (used in hook)
add_action( 'init_plugin_suite_user_engine_add_cash', function ( $user_id, $amount, $source ) {
	init_plugin_suite_user_engine_add_cash( $user_id, $amount );
	init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', $amount, $source, 'add' );
}, 10, 3 );

// Get avatar with VIP badge
function init_plugin_suite_user_engine_get_avatar( $user_id = 0, $size = 50, $args = [] ) {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $user_id ) return '';

	$default_args = [
		'class'       => 'iue-avatar-img',
		'alt'         => '',
		'force_badge' => false,
		'overlay'     => false,
	];
	$args = wp_parse_args( $args, $default_args );

	$is_vip 		   = $args['force_badge'] || init_plugin_suite_user_engine_is_vip( $user_id );
	$avatar 		   = get_avatar( $user_id, $size, '', $args['alt'], [ 'class' => $args['class'] ] );
	$can_upload_avatar = init_plugin_suite_user_engine_can_upload_avatar( $user_id );

	// Nếu không cần overlay và không VIP → return nguyên avatar
	if ( ! $args['overlay'] && ! $is_vip ) {
		return $avatar;
	}

	// Build overlay nếu có
	$overlay_html = $args['overlay'] && $can_upload_avatar
		? '<span class="iue-avatar-overlay"><span class="iue-icon" data-iue-icon="camera"></span></span>'
		: '';

	return sprintf(
		'<div class="iue-avatar-wrapper"%s>%s%s%s</div>',
		$args['overlay'] && $can_upload_avatar ? ' data-iue-avatar-trigger' : '',
		$avatar,
		$overlay_html,
		$is_vip ? '<span class="iue-vip-badge">VIP</span>' : ''
	);
}

// Mã hóa user ID thành chuỗi 8 ký tự
function init_plugin_suite_user_engine_encode_user_id( $user_id ) {
	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$base  = strlen($chars);
	$number = $user_id * INIT_PLUGIN_SUITE_IUE_REF_SALT;
	$out = '';

	while ( $number > 0 ) {
		$out = $chars[ $number % $base ] . $out;
		$number = floor( $number / $base );
	}

	// Pad về đúng 8 ký tự (ví dụ: "00aZ4rRt")
	return str_pad($out, 8, '0', STR_PAD_LEFT);
}

// Giải mã từ chuỗi 8 ký tự về user ID
function init_plugin_suite_user_engine_decode_user_id( $code ) {
	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$base  = strlen($chars);
	$number = 0;

	$code = ltrim($code, '0'); // bỏ số 0 đầu nếu có

	for ( $i = 0; $i < strlen($code); $i++ ) {
		$number = $number * $base + strpos($chars, $code[$i]);
	}

	// Chia lại để lấy user ID
	if ( $number % INIT_PLUGIN_SUITE_IUE_REF_SALT !== 0 ) return 0; // lỗi mã
	return intval( $number / INIT_PLUGIN_SUITE_IUE_REF_SALT );
}

// Tính thời gian
function init_plugin_suite_user_engine_time_ago( $timestamp ) {
	$now  = current_time( 'timestamp' );
	$diff = $now - (int) $timestamp;

	if ( $diff < 60 ) {
		return __( 'just now', 'init-user-engine' );
	}

	$units = [
		'year'   => YEAR_IN_SECONDS,
		'month'  => MONTH_IN_SECONDS,
		'week'   => WEEK_IN_SECONDS,
		'day'    => DAY_IN_SECONDS,
		'hour'   => HOUR_IN_SECONDS,
		'minute' => MINUTE_IN_SECONDS,
	];

	foreach ( $units as $unit => $seconds ) {
		$val = floor( $diff / $seconds );
		if ( $val >= 1 ) {
			switch ( $unit ) {
				case 'year':
					// translators: %d = number of years
					return sprintf( _n( '%d year', '%d years', $val, 'init-user-engine' ), $val );
				case 'month':
					// translators: %d = number of months
					return sprintf( _n( '%d month', '%d months', $val, 'init-user-engine' ), $val );
				case 'week':
					// translators: %d = number of weeks
					return sprintf( _n( '%d week', '%d weeks', $val, 'init-user-engine' ), $val );
				case 'day':
					// translators: %d = number of days
					return sprintf( _n( '%d day', '%d days', $val, 'init-user-engine' ), $val );
				case 'hour':
					// translators: %d = number of hours
					return sprintf( _n( '%d hour', '%d hours', $val, 'init-user-engine' ), $val );
				case 'minute':
					// translators: %d = number of minutes
					return sprintf( _n( '%d minute', '%d minutes', $val, 'init-user-engine' ), $val );
			}
		}
	}

	return __( 'just now', 'init-user-engine' ); // fallback
}

// =======================
// Admin menu icon
// =======================

/**
 * Register the custom SVG icon with the WordPress Icon Registration API (WP 7.1+).
 *
 * Only in wp-admin: the icon is used by the admin menu alone, so there is no reason to register
 * (and sanitize) it on every front-end or REST request. WP 7.2+: the collection is not public,
 * so it stays out of the editor's icon picker.
 *
 * @since 1.7.7
 */
function init_plugin_suite_user_engine_register_icons() {
	if ( ! function_exists( 'wp_register_icon_collection' ) || ! function_exists( 'wp_register_icon' ) ) {
		return;
	}

	$collection = [
		'label' => __( 'Init User Engine', 'init-user-engine' ),
	];
	if ( init_plugin_suite_user_engine_admin_menu_supports_icon_names() ) {
		$collection['public'] = false; // WP 7.2+
	}

	wp_register_icon_collection( 'init-user-engine', $collection );

	$content = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M22 12c0-5.51-4.49-10-10-10S2 6.49 2 12c0 2.9 1.25 5.51 3.23 7.34 0 .01 0 .01-.01.02.1.1.22.18.32.27.06.05.11.1.17.14.18.15.38.29.57.43l.2.14q.285.195.6.36c.07.04.15.09.22.13q.3.165.63.3c.08.04.16.08.24.11q.33.135.66.24c.08.03.16.06.24.08.24.07.48.13.72.19.07.02.14.04.22.05.28.06.56.1.85.13.04 0 .08.01.12.02.34.03.68.05 1.02.05s.68-.02 1.01-.05c.04 0 .08-.01.12-.02.29-.03.57-.07.85-.13.07-.01.14-.04.22-.05.24-.06.49-.11.72-.19.08-.03.16-.06.24-.08.22-.08.45-.15.66-.24.08-.03.16-.07.24-.11q.315-.135.63-.3c.08-.04.15-.09.22-.13.2-.12.4-.23.6-.36.07-.04.13-.09.2-.14.2-.14.39-.28.57-.43.06-.05.11-.1.17-.14.11-.09.22-.18.32-.27 0-.01 0-.01-.01-.02C20.75 17.51 22 14.9 22 12m-5.06 4.97c-2.71-1.82-7.15-1.82-9.88 0-.44.29-.8.63-1.1 1A8.48 8.48 0 0 1 3.5 12c0-4.69 3.81-8.5 8.5-8.5s8.5 3.81 8.5 8.5c0 2.32-.94 4.43-2.46 5.97-.29-.37-.66-.71-1.1-1"/><path fill="currentColor" d="M12 6.93c-2.07 0-3.75 1.68-3.75 3.75 0 2.03 1.59 3.68 3.7 3.74h.18a3.743 3.743 0 0 0 3.62-3.74c0-2.07-1.68-3.75-3.75-3.75"/></svg>';

	// WP 7.2+ draws the menu icon at 24px like core icons (strokes ~20px, with margin), so widen
	// the viewBox by 10% per side to balance it against core. WP 7.1 (20px data URI) keeps the original.
	if ( init_plugin_suite_user_engine_admin_menu_supports_icon_names() ) {
		$content = init_plugin_suite_user_engine_pad_svg_viewbox( $content, 0.1 );
	}

	wp_register_icon(
		'init-user-engine/init-user-engine',
		[
			'label'   => __( 'Init User Engine', 'init-user-engine' ),
			'content' => $content,
		]
	);
}
if ( is_admin() ) {
	add_action( 'init', 'init_plugin_suite_user_engine_register_icons', 1 );
}

/**
 * WP 7.2+ renders namespaced admin menu icon names ('init-user-engine/init-user-engine') as
 * inline SVG (<div class="wp-menu-image dashicons-before svg-icon"><svg …>), coloured by CSS.
 *
 * @since 1.7.7
 */
function init_plugin_suite_user_engine_admin_menu_supports_icon_names() {
	return function_exists( 'wp_is_valid_icon_name' ) && function_exists( 'wp_get_icon' );
}

/**
 * Widen an SVG viewBox by $ratio (of each side) on every side.
 *
 * @since 1.7.7
 */
function init_plugin_suite_user_engine_pad_svg_viewbox( $svg, $ratio ) {
	return preg_replace_callback(
		'/\bviewBox="([-\d.]+)\s+([-\d.]+)\s+([\d.]+)\s+([\d.]+)"/i',
		function ( $m ) use ( $ratio ) {
			$w  = (float) $m[3];
			$h  = (float) $m[4];
			$px = $w * $ratio;
			$py = $h * $ratio;
			return sprintf(
				'viewBox="%s %s %s %s"',
				round( (float) $m[1] - $px, 3 ),
				round( (float) $m[2] - $py, 3 ),
				round( $w + 2 * $px, 3 ),
				round( $h + 2 * $py, 3 )
			);
		},
		(string) $svg,
		1
	);
}

/**
 * Menu icon colour of the current admin colour scheme (what svg-painter.js will paint), used for
 * the WP 7.1 data URI so the icon is the right colour at once instead of black until svg-painter runs.
 *
 * @since 1.7.7
 */
function init_plugin_suite_user_engine_admin_icon_base_color() {
	static $color = null;
	if ( null !== $color ) {
		return $color;
	}

	$scheme = (string) get_user_option( 'admin_color' );
	if ( '' === $scheme ) {
		$scheme = 'fresh';
	}

	global $_wp_admin_css_colors;
	if ( ! empty( $_wp_admin_css_colors[ $scheme ]->icon_colors['base'] ) ) {
		$color = (string) $_wp_admin_css_colors[ $scheme ]->icon_colors['base'];
	} else {
		$core  = [
			'fresh'     => '#a7aaad',
			'light'     => '#999',
			'modern'    => '#f3f1f1',
			'blue'      => '#e5f8ff',
			'coffee'    => '#f3f2f1',
			'ectoplasm' => '#ece6f6',
			'midnight'  => '#f1f2f3',
			'ocean'     => '#f2fcff',
			'sunrise'   => '#f3f1f1',
		];
		$color = $core[ $scheme ] ?? '#a7aaad';
	}

	if ( ! preg_match( '/^#[0-9a-f]{3,8}$/i', $color ) ) {
		$color = '#a7aaad';
	}
	return $color;
}

/**
 * Icon for add_menu_page().
 *
 * - WP 7.2+: the icon name itself → core renders inline SVG, coloured by CSS.
 * - WP 7.1: base64 data URI, already painted with the admin colour scheme's icon colour.
 * - Older WP (no Icon API) or icon missing: the Dashicons fallback (menu-icon.css swaps in the SVG).
 *
 * @since 1.7.7
 *
 * @param string $icon_name Registered icon name.
 * @param string $fallback  Fallback Dashicons class.
 * @return string
 */
function init_plugin_suite_user_engine_admin_icon_url( $icon_name, $fallback = 'dashicons-admin-generic' ) {
	if ( ! is_admin() ) {
		return $fallback;
	}

	if ( init_plugin_suite_user_engine_admin_menu_supports_icon_names() ) {
		return $icon_name;
	}

	if ( function_exists( 'wp_get_icon' ) ) {
		$svg = wp_get_icon( $icon_name, [ 'size' => 20 ] );

		if ( is_string( $svg ) && '' !== trim( $svg ) ) {
			// WP 7.1's SVG sanitizer may lowercase viewBox to viewbox.
			$svg = preg_replace( '/\bviewbox\s*=/i', 'viewBox=', $svg );

			// A background image does not inherit currentColor → paint it with the scheme's icon colour.
			$svg = str_replace( 'currentColor', init_plugin_suite_user_engine_admin_icon_base_color(), $svg );

			return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- SVG data URI for add_menu_page().
		}
	}

	return $fallback;
}

// Fallback for WordPress without the Icon API (< 7.1): the CSS mask draws the icon over Dashicons.
add_action( 'admin_enqueue_scripts', function () {
	if ( function_exists( 'wp_register_icon' ) ) {
		return;
	}
	wp_enqueue_style( 'iue-admin-icon', INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'css/menu-icon.css', [], INIT_PLUGIN_SUITE_IUE_VERSION );
} );

// Enhanced IP detection
function init_plugin_suite_user_engine_get_real_ip() {
    $ip_keys = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_REAL_IP',           // Nginx proxy
        'REMOTE_ADDR'               // Standard
    ];

    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER)) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER[$key] ) );
            
            // Handle comma-separated IPs (X-Forwarded-For có thể có nhiều IP)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            
            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
            
            // Fallback: accept private IPs too (for local dev)
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return '127.0.0.1'; // Ultimate fallback
}

/**
 * Verify Cloudflare Turnstile token.
 *
 * @param string $token
 * @param string $remote_ip
 * @return true|WP_Error
 */
function init_plugin_suite_user_engine_verify_turnstile( $token, $remote_ip = '' ) {
	$settings    = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
	$secret_key  = $settings['turnstile_secret_key'] ?? '';

	if ( empty( $secret_key ) ) {
		return new WP_Error( 'turnstile_not_configured', __( 'Turnstile is not properly configured.', 'init-user-engine' ), [ 'status' => 500 ] );
	}

	$token = trim( (string) $token );
	if ( $token === '' ) {
		return new WP_Error( 'turnstile_required', __( 'Captcha verification is required.', 'init-user-engine' ), [ 'status' => 400 ] );
	}

	$args = [
		'timeout' => 8,
		'body'    => [
			'secret'   => $secret_key,
			'response' => $token,
			'remoteip' => $remote_ip,
		],
	];

	// phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Đây là lệnh gọi API xác thực server-to-server tới Cloudflare Turnstile (không phải tải asset JS/CSS), bắt buộc phải gọi đúng endpoint chính thức này để xác minh token.
	$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', $args );
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'turnstile_http_error', __( 'Captcha verification failed due to a network error. Please try again.', 'init-user-engine' ), [ 'status' => 502 ] );
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'turnstile_bad_response', __( 'Captcha verification service returned an unexpected response.', 'init-user-engine' ), [ 'status' => 502 ] );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) ) {
		return new WP_Error( 'turnstile_parse_error', __( 'Captcha verification response could not be parsed.', 'init-user-engine' ), [ 'status' => 502 ] );
	}

	if ( ! empty( $body['success'] ) ) {
		return true;
	}

	// Map một số error code phổ biến (tham khảo Cloudflare docs)
	$error_codes = $body['error-codes'] ?? [];
	if ( is_array( $error_codes ) && in_array( 'timeout-or-duplicate', $error_codes, true ) ) {
		return new WP_Error( 'turnstile_timeout', __( 'Captcha expired or already used. Please refresh and try again.', 'init-user-engine' ), [ 'status' => 400 ] );
	}

	return new WP_Error( 'turnstile_invalid', __( 'Captcha verification failed. Please try again.', 'init-user-engine' ), [ 'status' => 400 ] );
}

/**
 * Get a user's current age, computed from their stored date of birth.
 *
 * Ngày sinh được lưu ở user meta 'iue_dob' theo định dạng Y-m-d.
 * Tuổi được tính lại mỗi lần gọi (dựa trên timezone của site) nên luôn chính xác,
 * không bị lệch dần theo thời gian như khi lưu sẵn 1 con số tuổi cố định.
 *
 * @param int $user_id
 * @return int Tuổi hiện tại, hoặc 0 nếu user chưa nhập ngày sinh / dữ liệu không hợp lệ.
 */
function init_plugin_suite_user_engine_get_age( $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! $user_id ) {
		return 0;
	}

	$dob_raw = trim( (string) get_user_meta( $user_id, 'iue_dob', true ) );
	if ( $dob_raw === '' ) {
		return 0;
	}

	$dob = DateTime::createFromFormat( 'Y-m-d', $dob_raw );

	// Dữ liệu hỏng/sai định dạng (VD: bị chỉnh trực tiếp trong DB) → coi như chưa có.
	if ( ! $dob || $dob->format( 'Y-m-d' ) !== $dob_raw ) {
		return 0;
	}

	$today = new DateTime( 'today', wp_timezone() );
	if ( $dob > $today ) {
		return 0;
	}

	$age = $today->diff( $dob )->y;

	return (int) $age;
}

/**
 * Check if a user can upload an avatar.
 *
 * Logic:
 * - If user has meta 'iue_avatar_ban' truthy (not empty, not 0, not "0") → deny.
 * - If option 'avatar_upload_policy' is:
 *     - 'disable_all' → deny all.
 *     - 'vip_only' → allow only VIPs (via init_plugin_suite_user_engine_is_vip()).
 *     - 'default' → allow everyone.
 *
 * @param int $user_id
 * @return bool True if allowed, false if denied.
 */
function init_plugin_suite_user_engine_can_upload_avatar( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return false;
	}

	$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION );
	$policy  = $options['avatar_upload_policy'] ?? 'default';

	// 1. Check per-user ban meta
	$ban_val = get_user_meta( $user_id, 'iue_avatar_ban', true );
	if ( $ban_val !== '' && $ban_val !== null && $ban_val !== '0' && $ban_val !== 0 ) {
		return false;
	}

	// 2. Global policy
	switch ( $policy ) {
		case 'disable_all':
			return false;

		case 'vip_only':
			return init_plugin_suite_user_engine_is_vip( $user_id );

		default:
			return true;
	}
}
