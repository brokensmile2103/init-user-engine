<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Return today's date in Y-m-d format
function init_plugin_suite_user_engine_today() {
	return current_time( 'Y-m-d' );
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

	$is_vip = $args['force_badge'] || init_plugin_suite_user_engine_is_vip( $user_id );
	$avatar = get_avatar( $user_id, $size, '', $args['alt'], [ 'class' => $args['class'] ] );

	// Nếu không cần overlay và không VIP → return nguyên avatar
	if ( ! $args['overlay'] && ! $is_vip ) {
		return $avatar;
	}

	// Build overlay nếu có
	$overlay_html = $args['overlay']
		? '<span class="iue-avatar-overlay"><span class="iue-icon" data-iue-icon="camera"></span></span>'
		: '';

	return sprintf(
		'<div class="iue-avatar-wrapper"%s>%s%s%s</div>',
		$args['overlay'] ? ' data-iue-avatar-trigger' : '',
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

// Biểu tượng
add_action( 'admin_enqueue_scripts', function ( $hook ) {
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
