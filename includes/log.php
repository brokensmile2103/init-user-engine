<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================
// Internal DB helpers
// ==========================

/**
 * Trả về tên bảng transaction log (có prefix).
 *
 * @return string
 */
function init_plugin_suite_user_engine_txn_table() {
	global $wpdb;
	return $wpdb->prefix . 'init_user_engine_transaction_log';
}

/**
 * Trả về tên bảng EXP log (có prefix).
 *
 * @return string
 */
function init_plugin_suite_user_engine_exp_table() {
	global $wpdb;
	return $wpdb->prefix . 'init_user_engine_exp_log';
}

// ==========================
// Cache helpers
// ==========================

function init_plugin_suite_user_engine_cache_group() {
	return 'iue_logs';
}

function init_plugin_suite_user_engine_cache_ttl() {
	return 10 * MINUTE_IN_SECONDS;
}

/**
 * Cache key cho danh sách log (dùng trong get_transaction_log / get_exp_log).
 *
 * @param string $type    'txn' | 'exp'
 * @param int    $user_id
 * @return string
 */
function init_plugin_suite_user_engine_cache_key( $type, $user_id ) {
	return "{$type}_log_user_" . (int) $user_id;
}

/**
 * Cache key cho COUNT(*) của REST API pagination.
 * Tách riêng khỏi cache list vì vòng đời và mục đích khác nhau.
 *
 * @param string $type    'txn' | 'exp'
 * @param int    $user_id
 * @return string
 */
function init_plugin_suite_user_engine_count_cache_key( $type, $user_id ) {
	return "{$type}_count_user_" . (int) $user_id;
}

// ==========================
// Transaction log (coin/cash)
// ==========================

/**
 * Ghi một giao dịch coin/cash vào DB.
 *
 * Tương đương hàm cũ dùng user meta iue_coin_cash_log.
 * Giữ nguyên toàn bộ logic VIP bonus và hook.
 *
 * @param int    $user_id
 * @param string $type    'coin' | 'cash'
 * @param int    $amount  Số lượng (dương)
 * @param string $source  Nguồn giao dịch
 * @param string $change  'add' | 'deduct'
 * @return bool
 */
function init_plugin_suite_user_engine_log_transaction( $user_id, $type, $amount, $source, $change = 'add' ) {
	if ( ! in_array( $type, [ 'coin', 'cash' ], true ) ) {
		return false;
	}

	if ( ! in_array( $change, [ 'add', 'deduct' ], true ) ) {
		return false;
	}

	$is_vip          = init_plugin_suite_user_engine_is_vip( $user_id );
	$original_amount = absint( $amount );
	$final_amount    = $original_amount;
	$bonus_percent   = 0;

	// Apply VIP bonus (chỉ áp dụng cho coin và change = add)
	if ( $type === 'coin' && $change === 'add' && $is_vip ) {
		$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
		$bonus   = absint( $options['vip_bonus_coin'] ?? 0 );

		if ( $bonus > 0 ) {
			$bonus_percent = $bonus;
			$final_amount += (int) round( $original_amount * $bonus / 100 );
		}
	}

	global $wpdb;
	$table = init_plugin_suite_user_engine_txn_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$inserted = $wpdb->insert(
		$table,
		[
			'user_id'         => (int) $user_id,
			'type'            => $type,
			'amount'          => $final_amount,
			'original_amount' => $original_amount,
			'change_type'     => $change,
			'source'          => sanitize_text_field( $source ),
			'vip_bonus'       => $is_vip ? 1 : 0,
			'bonus_percent'   => $bonus_percent,
			'logged_at'       => current_time( 'Y-m-d H:i:s' ),
		],
		[ '%d', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%s' ]
	);

	if ( ! $inserted ) {
		return false;
	}

	// Tái tạo $entry theo format cũ để các hook cũ vẫn nhận được đúng dữ liệu
	$entry = [
		'type'          => $type,
		'amount'        => $final_amount,
		'original'      => $original_amount,
		'change'        => $change,
		'source'        => $source,
		'time'          => current_time( 'Y-m-d H:i:s' ),
		'vip_bonus'     => $is_vip,
		'bonus_percent' => $bonus_percent,
	];

	do_action( 'init_plugin_suite_user_engine_transaction_logged', $user_id, $entry );

	return true;
}

/**
 * Lấy toàn bộ transaction log của user từ DB.
 *
 * Kết quả trả về theo format mảng cũ (tương thích ngược) để các đoạn code
 * khác trong plugin đọc log vẫn hoạt động bình thường.
 *
 * @param int $user_id
 * @return array  Mảng các entry theo thứ tự tăng dần (cũ → mới), tối đa 100 entry.
 */
function init_plugin_suite_user_engine_get_transaction_log( $user_id ) {
	$user_id   = (int) $user_id;
	$cache_key = init_plugin_suite_user_engine_cache_key( 'txn', $user_id );
	$group     = init_plugin_suite_user_engine_cache_group();

	$cached = wp_cache_get( $cache_key, $group );
	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;
	$table = init_plugin_suite_user_engine_txn_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table WHERE user_id = %d ORDER BY logged_at ASC, id ASC LIMIT 100", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id
		),
		ARRAY_A
	);

	if ( empty( $rows ) ) {
		wp_cache_set( $cache_key, [], $group, init_plugin_suite_user_engine_cache_ttl() );
		return [];
	}

	$data = array_map( 'init_plugin_suite_user_engine_txn_row_to_legacy', $rows );

	wp_cache_set( $cache_key, $data, $group, init_plugin_suite_user_engine_cache_ttl() );

	return $data;
}

/**
 * Chuyển 1 row DB transaction sang format mảng legacy (giống meta cũ).
 *
 * @param array $row
 * @return array
 */
function init_plugin_suite_user_engine_txn_row_to_legacy( array $row ) {
	return [
		'type'          => $row['type'],
		'amount'        => (int) $row['amount'],
		'original'      => (int) $row['original_amount'],
		'change'        => $row['change_type'],
		'source'        => $row['source'],
		'time'          => $row['logged_at'],
		'vip_bonus'     => (bool) $row['vip_bonus'],
		'bonus_percent' => (int) $row['bonus_percent'],
		// Thêm id DB để dùng nội bộ nếu cần
		'_id'           => (int) $row['id'],
	];
}

/**
 * Format một log entry thành chuỗi thông báo dễ đọc.
 * Giữ nguyên toàn bộ switch-case và chuỗi i18n cũ.
 *
 * @param array $entry  Entry theo format legacy hoặc row DB.
 * @return string
 */
function init_plugin_suite_user_engine_format_log_message( $entry ) {
	$source = $entry['source'] ?? 'unknown';
	$type   = $entry['type'] ?? '';
	$amount = absint( $entry['amount'] ?? 0 );

	$message = '';

	switch ( $source ) {
		case 'daily_login':
			$message = __( 'Daily login bonus', 'init-user-engine' );
			break;
		case 'user_register':
			$message = __( 'Welcome bonus for registration', 'init-user-engine' );
			break;
		case 'update_profile':
			$message = __( 'Profile updated', 'init-user-engine' );
			break;
		case ( preg_match( '/^level_up_(\d+)$/', $source, $m ) ? true : false ):
			// translators: %d is the new level number
			$message = sprintf( __( 'Level up to %d', 'init-user-engine' ), $m[1] );
			break;
		case ( preg_match( '/^milestone_(\d+)$/', $source, $m ) ? true : false ):
			// translators: %d is the number of days in the check-in streak
			$message = sprintf( __( 'Check-in streak reached %d days', 'init-user-engine' ), $m[1] );
			break;
		case 'checkin':
			$message = __( 'Daily check-in reward', 'init-user-engine' );
			break;
		case 'reward':
			$message = __( 'Bonus reward after being online', 'init-user-engine' );
			break;
		case 'referral':
			$message = __( 'Referral reward for inviting a friend', 'init-user-engine' );
			break;
		case 'referral_new':
			$message = __( 'Welcome reward for signing up via referral', 'init-user-engine' );
			break;
		case 'woo_order':
			$message = __( 'Reward from WooCommerce order', 'init-user-engine' );
			break;
		case 'comment_post':
			$message = __( 'Comment posted', 'init-user-engine' );
			break;
		case 'publish_post':
			$message = __( 'First time post published', 'init-user-engine' );
			break;
		case 'unlock_reward':
			$message = __( 'Chapter unlock reward', 'init-user-engine' );
			break;
		case 'redeem_code':
			$message = __( 'Redeem code reward', 'init-user-engine' );
			break;
		default:
			$message = ucfirst( str_replace( '_', ' ', $source ) );
			break;
	}

	/**
	 * Filter to customize log message format
	 *
	 * @param string $message The formatted message
	 * @param array  $entry   The original log entry data
	 * @param string $source  The source of the log entry
	 * @param string $type    The type of the log entry
	 * @param int    $amount  The amount value
	 *
	 * @since 1.0.0
	 */
	return apply_filters( 'init_plugin_suite_user_engine_format_log_message', $message, $entry, $source, $type, $amount );
}

// ==========================
// EXP log
// ==========================

/**
 * Ghi một entry EXP vào DB.
 *
 * Tương đương hàm cũ dùng user meta iue_exp_log.
 *
 * @param int    $user_id
 * @param int    $amount
 * @param string $source
 * @param string $change 'add' | 'deduct'
 * @return bool
 */
function init_plugin_suite_user_engine_log_exp( $user_id, $amount, $source = '', $change = 'add' ) {
	if ( ! in_array( $change, [ 'add', 'deduct' ], true ) ) {
		return false;
	}

	$is_vip = init_plugin_suite_user_engine_is_vip( $user_id );

	global $wpdb;
	$table = init_plugin_suite_user_engine_exp_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$inserted = $wpdb->insert(
		$table,
		[
			'user_id'     => (int) $user_id,
			'amount'      => absint( $amount ),
			'change_type' => $change,
			'source'      => sanitize_text_field( $source ),
			'vip_bonus'   => $is_vip ? 1 : 0,
			'logged_at'   => current_time( 'Y-m-d H:i:s' ),
		],
		[ '%d', '%d', '%s', '%s', '%d', '%s' ]
	);

	if ( ! $inserted ) {
		return false;
	}

	// Tái tạo $entry theo format cũ để hook cũ vẫn hoạt động
	$entry = [
		'amount'    => absint( $amount ),
		'change'    => $change,
		'source'    => $source,
		'time'      => current_time( 'Y-m-d H:i:s' ),
		'vip_bonus' => $is_vip,
	];

	do_action( 'init_plugin_suite_user_engine_exp_logged', $user_id, $entry );

	return true;
}

/**
 * Lấy EXP log của user từ DB.
 *
 * @param int $user_id
 * @return array  Format legacy (tương thích ngược), tối đa 100 entry.
 */
function init_plugin_suite_user_engine_get_exp_log( $user_id ) {
	$user_id   = (int) $user_id;
	$cache_key = init_plugin_suite_user_engine_cache_key( 'exp', $user_id );
	$group     = init_plugin_suite_user_engine_cache_group();

	$cached = wp_cache_get( $cache_key, $group );
	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;
	$table = init_plugin_suite_user_engine_exp_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table WHERE user_id = %d ORDER BY logged_at ASC, id ASC LIMIT 100", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id
		),
		ARRAY_A
	);

	if ( empty( $rows ) ) {
		wp_cache_set( $cache_key, [], $group, init_plugin_suite_user_engine_cache_ttl() );
		return [];
	}

	$data = array_map( 'init_plugin_suite_user_engine_exp_row_to_legacy', $rows );

	wp_cache_set( $cache_key, $data, $group, init_plugin_suite_user_engine_cache_ttl() );

	return $data;
}

/**
 * Chuyển 1 row DB EXP sang format mảng legacy (giống meta cũ).
 *
 * @param array $row
 * @return array
 */
function init_plugin_suite_user_engine_exp_row_to_legacy( array $row ) {
	return [
		'amount'    => (int) $row['amount'],
		'change'    => $row['change_type'],
		'source'    => $row['source'],
		'time'      => $row['logged_at'],
		'vip_bonus' => (bool) $row['vip_bonus'],
		'_id'       => (int) $row['id'],
	];
}

/**
 * Format một EXP log entry thành label.
 * Giữ nguyên toàn bộ switch-case và chuỗi i18n cũ.
 *
 * @param array $entry
 * @return string
 */
function init_plugin_suite_user_engine_format_exp_log( $entry ) {
	$source = $entry['source'] ?? 'unknown';
	$label  = '';

	switch ( $source ) {
		case 'daily_login':
			$label = __( 'EXP from daily login', 'init-user-engine' );
			break;
		case 'user_register':
			$label = __( 'Welcome EXP for registration', 'init-user-engine' );
			break;
		case 'update_profile':
			$label = __( 'EXP for profile update', 'init-user-engine' );
			break;
		case ( preg_match( '/^level_up_(\d+)$/', $source, $m ) ? true : false ):
			// translators: %d is level
			$label = sprintf( __( 'EXP reset after level %d up', 'init-user-engine' ), $m[1] );
			break;
		case ( preg_match( '/^milestone_(\d+)$/', $source, $m ) ? true : false ):
			// translators: %d is streak
			$label = sprintf( __( 'EXP for %d-day streak milestone', 'init-user-engine' ), $m[1] );
			break;
		case 'reward':
			$label = __( 'EXP reward after staying online', 'init-user-engine' );
			break;
		case 'referral':
			$label = __( 'EXP gained for inviting a friend', 'init-user-engine' );
			break;
		case 'referral_new':
			$label = __( 'EXP gained from referral signup', 'init-user-engine' );
			break;
		case 'woo_order':
			$label = __( 'EXP from WooCommerce order', 'init-user-engine' );
			break;
		case 'comment_post':
			$label = __( 'EXP from posting a comment', 'init-user-engine' );
			break;
		case 'publish_post':
			$label = __( 'EXP from publishing a post', 'init-user-engine' );
			break;
		default:
			$label = ucfirst( str_replace( '_', ' ', $source ) );
			break;
	}

	/**
	 * Allow developers to modify or add custom labels for EXP log sources.
	 *
	 * @since 1.0.1
	 *
	 * @param string $label  The formatted label.
	 * @param string $source The original source key.
	 * @param array  $entry  The full EXP log entry.
	 */
	return apply_filters( 'init_plugin_suite_user_engine_exp_log_label', $label, $source, $entry );
}

// ==========================
// REST API: Transaction history
// ==========================

/**
 * REST handler: lấy lịch sử giao dịch coin/cash của user hiện tại.
 * Hỗ trợ phân trang qua ?page=&per_page=
 *
 * COUNT(*) được cache riêng (txn_count_user_{id}) để tránh query nặng mỗi lần
 * chuyển trang. Cache tự động bị xóa khi có giao dịch mới được ghi.
 * Các page result không cache vì offset thay đổi liên tục và lợi ích thấp.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function init_plugin_suite_user_engine_api_get_transactions( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
	}

	global $wpdb;
	$table     = init_plugin_suite_user_engine_txn_table();
	$group     = init_plugin_suite_user_engine_cache_group();
	$count_key = init_plugin_suite_user_engine_count_cache_key( 'txn', $user_id );
	$page      = max( 1, (int) $request->get_param( 'page' ) );
	$per_page  = max( 1, min( 50, (int) $request->get_param( 'per_page' ) ) );
	$offset    = ( $page - 1 ) * $per_page;

	// Cache COUNT(*) — query này chạy mỗi lần chuyển trang nên cần cache
	$total = wp_cache_get( $count_key, $group );
	if ( false === $total ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			)
		);
		wp_cache_set( $count_key, $total, $group, init_plugin_suite_user_engine_cache_ttl() );
	}

	$total_pages = $total > 0 ? (int) ceil( $total / $per_page ) : 1;

	// Page result không cache — offset thay đổi liên tục, index đã đủ nhanh
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table WHERE user_id = %d ORDER BY logged_at DESC, id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id, $per_page, $offset
		),
		ARRAY_A
	);

	$data = [];
	foreach ( (array) $rows as $row ) {
		if ( ! is_array( $row ) ) continue;

		$entry  = init_plugin_suite_user_engine_txn_row_to_legacy( $row );
		$data[] = [
			'type'    => strtoupper( $row['type'] ?? 'UNKNOWN' ),
			'amount'  => (int) ( $row['amount'] ?? 0 ),
			'change'  => ( $row['change_type'] === 'deduct' ) ? '-' : '+',
			'source'  => $row['source'] ?? 'unknown',
			'message' => init_plugin_suite_user_engine_format_log_message( $entry ),
			'time'    => $row['logged_at'] ?? current_time( 'Y-m-d H:i:s' ),
		];
	}

	return rest_ensure_response( [
		'page'        => $page,
		'per_page'    => $per_page,
		'total'       => $total,
		'total_pages' => $total_pages,
		'data'        => $data,
	] );
}

// ==========================
// REST API: EXP history
// ==========================

/**
 * REST handler: lấy lịch sử EXP của user hiện tại.
 *
 * COUNT(*) được cache riêng (exp_count_user_{id}), tự động xóa khi có EXP mới.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function init_plugin_suite_user_engine_api_get_exp_log( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
	}

	global $wpdb;
	$table     = init_plugin_suite_user_engine_exp_table();
	$group     = init_plugin_suite_user_engine_cache_group();
	$count_key = init_plugin_suite_user_engine_count_cache_key( 'exp', $user_id );
	$page      = max( 1, (int) $request->get_param( 'page' ) );
	$per_page  = max( 1, min( 50, (int) $request->get_param( 'per_page' ) ) );
	$offset    = ( $page - 1 ) * $per_page;

	// Cache COUNT(*) — tương tự transaction endpoint
	$total = wp_cache_get( $count_key, $group );
	if ( false === $total ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			)
		);
		wp_cache_set( $count_key, $total, $group, init_plugin_suite_user_engine_cache_ttl() );
	}

	$total_pages = $total > 0 ? (int) ceil( $total / $per_page ) : 1;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table WHERE user_id = %d ORDER BY logged_at DESC, id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id, $per_page, $offset
		),
		ARRAY_A
	);

	$data = [];
	foreach ( (array) $rows as $row ) {
		if ( ! is_array( $row ) ) continue;

		$entry  = init_plugin_suite_user_engine_exp_row_to_legacy( $row );
		$data[] = [
			'amount'  => absint( $row['amount'] ?? 0 ),
			'change'  => ( $row['change_type'] === 'deduct' ) ? '-' : '+',
			'source'  => $row['source'] ?? 'unknown',
			'message' => init_plugin_suite_user_engine_format_exp_log( $entry ),
			'time'    => $row['logged_at'] ?? current_time( 'Y-m-d H:i:s' ),
		];
	}

	return rest_ensure_response( [
		'page'        => $page,
		'per_page'    => $per_page,
		'total'       => $total,
		'total_pages' => $total_pages,
		'data'        => $data,
	] );
}

// ==========================
// REST API: Daily tasks
// ==========================

/**
 * REST handler: lấy danh sách daily tasks và trạng thái hoàn thành.
 * Không thay đổi logic, chỉ dùng lại get_transaction_log() — đã được refactor sang DB.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function init_plugin_suite_user_engine_api_get_daily_tasks( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
	}

	$today    = init_plugin_suite_user_engine_today();
	$log      = init_plugin_suite_user_engine_get_transaction_log( $user_id );
	$settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );

	$checkin_coin = isset( $settings['checkin_coin'] ) ? absint( $settings['checkin_coin'] ) : 10;
	$online_coin  = isset( $settings['online_coin'] )  ? absint( $settings['online_coin'] )  : 100;

	$tasks = [
		[
			'key'    => 'checkin',
			'title'  => __( 'Check in today', 'init-user-engine' ),
			'reward' => [ 'type' => 'coin', 'amount' => $checkin_coin ],
			'check'  => function( $user_id ) use ( $today ) {
				$last = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_last', '' );
				return $last === $today;
			},
		],
		[
			'key'    => 'online_reward',
			'title'  => __( 'Stay active today', 'init-user-engine' ),
			'reward' => [ 'type' => 'coin', 'amount' => $online_coin ],
			'check'  => function( $user_id ) use ( $today ) {
				$last     = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_last', '' );
				$rewarded = (bool) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_rewarded', false );
				return ( $last === $today && $rewarded );
			},
		],
	];

	$task_log_sources = [
		'update_profile' => __( 'Update your profile today', 'init-user-engine' ),
		'comment_post'   => __( 'Post a comment today', 'init-user-engine' ),
		'referral'       => __( 'Invite a friend', 'init-user-engine' ),
		'publish_post'   => __( 'Publish your first post', 'init-user-engine' ),
	];

	foreach ( $task_log_sources as $source => $label ) {
		$total_amount = 0;
		$type         = null;

		foreach ( $log as $e ) {
			if ( ! is_array( $e ) ) continue;
			if ( ! isset( $e['source'], $e['change'], $e['time'], $e['amount'] ) ) continue;
			if ( $e['source'] !== $source ) continue;
			if ( $e['change'] !== 'add' ) continue;
			if ( substr( $e['time'], 0, 10 ) !== $today ) continue;

			$total_amount += absint( $e['amount'] );
			$type = $e['type'] ?? $type;
		}

		if ( $total_amount > 0 && $type ) {
			$tasks[] = [
				'key'    => $source,
				'title'  => $label,
				'reward' => [
					'type'   => $type,
					'amount' => $total_amount,
				],
				'check' => '__return_true',
			];
		}
	}

	$tasks = apply_filters( 'init_plugin_suite_user_engine_daily_tasks', $tasks, $user_id );

	$output = [];

	foreach ( $tasks as $task ) {
		$completed = false;

		if ( is_callable( $task['check'] ) ) {
			try {
				$ref       = new ReflectionFunction( $task['check'] );
				$arg_count = $ref->getNumberOfParameters();

				$completed = $arg_count > 0
					? call_user_func( $task['check'], $user_id )
					: call_user_func( $task['check'] );
			} catch ( Throwable $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[IUE] Failed to execute task check callback: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
			}
		}

		$output[] = [
			'title'     => $task['title'] ?? 'Untitled task',
			'completed' => (bool) $completed,
			'reward'    => $task['reward'] ?? [ 'type' => 'coin', 'amount' => 0 ],
		];
	}

	return rest_ensure_response( $output );
}

// ==========================
// Top-up logs (không thay đổi)
// ==========================

/**
 * Add admin top-up log (latest 100 entries)
 * Format: quantity|type(coin|cash)|target(VIP|ALL|user:{count}|uid:{id}|user_id:{id})|time
 *
 * @param int         $quantity Positive or negative number.
 * @param string      $type     'coin' or 'cash'.
 * @param string      $target   'VIP', 'ALL', 'user:{count}', 'uid:{id}', or 'user_id:{id}'.
 * @param string|null $time     Optional timestamp. Default: current_time().
 * @return void
 */
function init_plugin_suite_user_engine_add_topup_log( $quantity, $type, $target, $time = null ) {
	$quantity = (int) $quantity;
	$type     = $type === 'cash' ? 'cash' : 'coin';
	$time     = $time ?: current_time( 'mysql' );

	$sanitize = static function( $s ) {
		return str_replace( [ '|', ';' ], '', (string) $s );
	};

	$target = $sanitize( $target );

	$entry = implode( '|', [
		$quantity,
		$sanitize( $type ),
		$target,
		$sanitize( $time ),
	] );

	$key = 'init_plugin_suite_user_engine_topup_logs';
	$raw = get_option( $key, '' );
	$raw = is_string( $raw ) ? trim( $raw ) : '';

	if ( $raw === '' ) {
		$raw = $entry;
	} else {
		$raw .= ';' . $entry;
	}

	$parts = array_filter( array_map( 'trim', explode( ';', $raw ) ) );
	if ( count( $parts ) > 100 ) {
		$parts = array_slice( $parts, -100 );
	}

	update_option( $key, implode( ';', $parts ), false );
}

/**
 * Get all top-up logs as array
 *
 * @return array
 */
function init_plugin_suite_user_engine_get_topup_logs() {
	$key = 'init_plugin_suite_user_engine_topup_logs';
	$raw = get_option( $key, '' );
	$raw = is_string( $raw ) ? trim( $raw ) : '';
	if ( $raw === '' ) return [];

	$rows = array_filter( array_map( 'trim', explode( ';', $raw ) ) );
	return array_map( function( $line ) {
		[ $qty, $type, $target, $time ] = array_pad( explode( '|', $line ), 4, '' );
		return [
			'quantity' => (int) $qty,
			'type'     => $type,
			'target'   => $target,
			'time'     => $time,
		];
	}, $rows );
}

/**
 * Clear expired or old logs (optional cleanup helper)
 *
 * @param int $keep_days Keep logs newer than X days (default 30).
 */
function init_plugin_suite_user_engine_prune_topup_logs( $keep_days = 30 ) {
	$cut  = strtotime( "-{$keep_days} days" );
	$logs = init_plugin_suite_user_engine_get_topup_logs();

	$logs = array_filter( $logs, function( $log ) use ( $cut ) {
		return isset( $log['time'] ) && strtotime( $log['time'] ) >= $cut;
	} );

	$lines = array_map( function( $log ) {
		return implode( '|', [
			$log['quantity'],
			$log['type'],
			$log['target'],
			$log['time'],
		] );
	}, $logs );

	update_option( 'init_plugin_suite_user_engine_topup_logs', implode( ';', $lines ), false );
}

/**
 * Pretty display for log target:
 * VIP | ALL | user:{count} | uid:{id} | user_id:{id}
 */
function init_plugin_suite_user_engine_pretty_target( $target ) {
	$target = (string) $target;

	if ( $target === 'VIP' ) return __( 'Active VIPs', 'init-user-engine' );
	if ( $target === 'ALL' ) return __( 'All members', 'init-user-engine' );

	if ( preg_match( '/^user:(\d+)$/', $target, $m ) ) {
		$n = max( 0, (int) $m[1] );
		// translators: %d = number of users.
		return sprintf( _n( '%d user', '%d users', $n, 'init-user-engine' ), $n );
	}

	if ( preg_match( '/^(?:uid|user_id):(\d+)$/', $target, $m ) ) {
		$uid = (int) $m[1];
		if ( $uid > 0 ) {
			$u = get_userdata( $uid );
			if ( $u ) {
				$name  = $u->display_name ?: $u->user_login;
				$login = $u->user_login;
				/* translators: 1: Display Name, 2: user_login, 3: ID */
				return sprintf( __( 'User: %1$s (@%2$s, #%3$d)', 'init-user-engine' ), $name, $login, $u->ID );
			}
			// translators: %d = user ID that was not found.
			return sprintf( __( 'User ID #%d (not found)', 'init-user-engine' ), $uid );
		}
	}

	return $target;
}

// ==========================
// Cache invalidation
// ==========================

/**
 * Xóa cache danh sách transaction log của user.
 * Gọi sau khi ghi giao dịch mới.
 *
 * @param int $user_id
 */
function init_plugin_suite_user_engine_invalidate_txn_cache( $user_id ) {
	$group = init_plugin_suite_user_engine_cache_group();
	wp_cache_delete( init_plugin_suite_user_engine_cache_key( 'txn', $user_id ), $group );
	wp_cache_delete( init_plugin_suite_user_engine_count_cache_key( 'txn', $user_id ), $group );
}

/**
 * Xóa cache danh sách EXP log của user.
 * Gọi sau khi ghi EXP mới.
 *
 * @param int $user_id
 */
function init_plugin_suite_user_engine_invalidate_exp_cache( $user_id ) {
	$group = init_plugin_suite_user_engine_cache_group();
	wp_cache_delete( init_plugin_suite_user_engine_cache_key( 'exp', $user_id ), $group );
	wp_cache_delete( init_plugin_suite_user_engine_count_cache_key( 'exp', $user_id ), $group );
}

add_action(
	'init_plugin_suite_user_engine_transaction_logged',
	function( $user_id ) {
		init_plugin_suite_user_engine_invalidate_txn_cache( $user_id );
	}
);

add_action(
	'init_plugin_suite_user_engine_exp_logged',
	function( $user_id ) {
		init_plugin_suite_user_engine_invalidate_exp_cache( $user_id );
	}
);
