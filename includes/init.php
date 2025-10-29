<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================
// Create Database
// ==========================
register_activation_hook( __FILE__, 'init_plugin_suite_user_engine_on_activation' );
add_action( 'wpmu_new_blog', 'init_plugin_suite_user_engine_on_new_blog', 10, 6 );
add_action( 'admin_init', 'init_plugin_suite_user_engine_check_table' );

/**
 * Xử lý khi plugin được activate (site đơn hoặc toàn mạng)
 */
function init_plugin_suite_user_engine_on_activation() {
    if ( is_multisite() ) {
        $sites = get_sites( [ 'number' => 0 ] );
        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );
            init_plugin_suite_user_engine_create_inbox_table();
            init_plugin_suite_user_engine_create_redeem_code_table();
            restore_current_blog();
        }
    } else {
        init_plugin_suite_user_engine_create_inbox_table();
        init_plugin_suite_user_engine_create_redeem_code_table();
    }
}

/**
 * Xử lý khi tạo site mới trong multisite
 */
function init_plugin_suite_user_engine_on_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    switch_to_blog( $blog_id );
    init_plugin_suite_user_engine_create_inbox_table();
    init_plugin_suite_user_engine_create_redeem_code_table();
    restore_current_blog();
}

/**
 * Kiểm tra và tạo bảng nếu cần thiết (admin_init hook)
 */
function init_plugin_suite_user_engine_check_table() {
    if ( ! current_user_can( 'administrator' ) ) {
        return;
    }
    
    global $wpdb;

    // INBOX
    $inbox_table = $wpdb->prefix . 'init_user_engine_inbox';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$inbox_table'" ) !== $inbox_table ) {
        init_plugin_suite_user_engine_create_inbox_table();
    }

    // REDEEM CODE
    $redeem_table = $wpdb->prefix . 'init_user_engine_redeem_codes';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$redeem_table'" ) !== $redeem_table ) {
        init_plugin_suite_user_engine_create_redeem_code_table();
    }
}

/**
 * Hàm tạo bảng inbox (đã có)
 */
function init_plugin_suite_user_engine_create_inbox_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'init_user_engine_inbox';
    $charset_collate = $wpdb->get_charset_collate();
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        type VARCHAR(50) NOT NULL DEFAULT 'system',
        status VARCHAR(20) NOT NULL DEFAULT 'unread',
        priority VARCHAR(10) NOT NULL DEFAULT 'normal',
        pinned TINYINT(1) NOT NULL DEFAULT 0,
        link TEXT DEFAULT NULL,
        metadata LONGTEXT DEFAULT NULL,
        expire_at BIGINT UNSIGNED DEFAULT NULL,
        created_at BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY priority (priority),
        KEY pinned (pinned)
    ) $charset_collate;";
    
    dbDelta( $sql );
}

/**
 * Hàm tạo bảng Redeem Code
 * - code: case-sensitive (COLLATE utf8mb4_bin) & UNIQUE
 * - type: 'single' | 'multi' | 'user_locked'
 * - max_uses: số lượt tối đa (single = 1, user_locked = 1)
 * - used_count: số lượt đã dùng
 * - user_lock: khóa cho 1 user cụ thể (nullable)
 * - coin_amount / cash_amount: số lượng Coin/Cash (đơn vị do hệ thống định nghĩa), dùng BIGINT để tránh float
 * - status: 'active' | 'disabled' | 'expired'
 * - valid_from / valid_to: khoảng thời gian hợp lệ (timestamp)
 * - created_by: admin tạo
 * - metadata: lưu JSON mở rộng
 * Indexes được tối ưu cho các truy vấn phổ biến: tra cứu theo code, lọc theo trạng thái/thời gian, báo cáo, v.v.
 */
function init_plugin_suite_user_engine_create_redeem_code_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'init_user_engine_redeem_codes';
    $charset_collate = $wpdb->get_charset_collate();

    // Lấy collation hiện tại của DB để ép cột code dùng *_bin (case-sensitive)
    // Nếu site dùng utf8mb4, ta set cột code -> utf8mb4_bin. Nếu không, MySQL sẽ bỏ qua không lỗi.
    $code_collation = 'utf8mb4_bin';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        code VARCHAR(64) COLLATE $code_collation NOT NULL,
        type VARCHAR(20) NOT NULL DEFAULT 'single',
        max_uses INT UNSIGNED DEFAULT NULL,
        used_count INT UNSIGNED NOT NULL DEFAULT 0,
        user_lock BIGINT UNSIGNED DEFAULT NULL,
        coin_amount BIGINT SIGNED NOT NULL DEFAULT 0,
        cash_amount BIGINT SIGNED NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        valid_from BIGINT UNSIGNED DEFAULT NULL,
        valid_to BIGINT UNSIGNED DEFAULT NULL,
        created_by BIGINT UNSIGNED DEFAULT NULL,
        metadata LONGTEXT DEFAULT NULL,
        created_at BIGINT UNSIGNED NOT NULL,
        updated_at BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY code (code),
        KEY type (type),
        KEY status (status),
        KEY user_lock (user_lock),
        KEY valid_to (valid_to),
        KEY created_at (created_at),
        KEY created_by (created_by),
        KEY status_valid_to (status, valid_to),
        KEY type_status (type, status)
    ) $charset_collate;";

    dbDelta( $sql );
}
