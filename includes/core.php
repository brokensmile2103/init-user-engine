<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Optional helper to check if a meta key exists
function init_plugin_suite_user_engine_has_meta( $user_id, $key ) {
	return metadata_exists( 'user', $user_id, $key );
}

// Get user meta with default fallback
function init_plugin_suite_user_engine_get_meta( $user_id, $key, $default = '' ) {
	$value = get_user_meta( $user_id, $key, true );
	return $value === '' ? $default : $value;
}

// Update user meta safely
function init_plugin_suite_user_engine_update_meta( $user_id, $key, $value ) {
	return update_user_meta( $user_id, $key, $value );
}

// Delete user meta if exists
function init_plugin_suite_user_engine_delete_meta( $user_id, $key ) {
	return delete_user_meta( $user_id, $key );
}

// Check if the user has checked in today
function init_plugin_suite_user_engine_has_checked_in_today( $user_id ) {
	if ( ! $user_id ) {
		return false;
	}

	$last_checkin = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_last', '' );
	$today        = init_plugin_suite_user_engine_today();

	return ( $last_checkin === $today );
}

// Đăng ký cron job
add_action( 'init', 'init_plugin_suite_user_engine_schedule_cleanup_transients' );
add_action( 'init_plugin_suite_user_engine_cleanup_transients', 'init_plugin_suite_user_engine_do_cleanup' );

function init_plugin_suite_user_engine_schedule_cleanup_transients() {
	if ( ! wp_next_scheduled( 'init_plugin_suite_user_engine_cleanup_transients' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'init_plugin_suite_user_engine_cleanup_transients' );
	}
}

function init_plugin_suite_user_engine_do_cleanup() {
	global $wpdb;

	// Timeout của transient luôn được WordPress lưu theo Unix timestamp (UTC) → phải
	// so sánh với time(). current_time( 'timestamp' ) cộng thêm GMT offset của site
	// nên ở múi giờ dương (vd GMT+7) sẽ xóa nhầm cả transient còn hạn (captcha, rate limit).
	$current_time = time();

	// Dọn captcha transients hết hạn.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
			 WHERE a.option_name LIKE %s
			 AND a.option_name = CONCAT('_transient_timeout_', SUBSTRING(b.option_name, 12))
			 AND b.option_name LIKE %s
			 AND a.option_value < %d",
			'_transient_timeout_iue_captcha_%',
			'_transient_iue_captcha_%',
			$current_time
		)
	);

	// Dọn rate limit transients hết hạn.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
			 WHERE a.option_name LIKE %s
			 AND a.option_name = CONCAT('_transient_timeout_', SUBSTRING(b.option_name, 12))
			 AND b.option_name LIKE %s
			 AND a.option_value < %d",
			'_transient_timeout_iue_register_rate_%',
			'_transient_iue_register_rate_%',
			$current_time
		)
	);
}

// Cleanup khi deactivate
// Phải dùng file plugin chính: __FILE__ ở đây là includes/core.php nên hook cũ không bao giờ chạy.
// Các cron sẽ tự được đăng ký lại (hook init/wp) khi plugin được kích hoạt lại.
register_deactivation_hook( INIT_PLUGIN_SUITE_IUE_FILE, 'init_plugin_suite_user_engine_on_deactivation' );

/**
 * Hủy toàn bộ cron định kỳ của plugin khi deactivate.
 *
 * @return void
 */
function init_plugin_suite_user_engine_on_deactivation() {
	wp_clear_scheduled_hook( 'init_plugin_suite_user_engine_cleanup_transients' );
	wp_clear_scheduled_hook( 'init_plugin_suite_user_engine_cleanup_orphaned_inbox' );
	wp_clear_scheduled_hook( 'init_plugin_suite_user_engine_unpin_expired_inbox' );
}
