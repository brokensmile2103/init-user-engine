<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================
// Create Database
// ==========================
register_activation_hook( __FILE__, 'init_plugin_suite_user_engine_on_activation' );
add_action( 'wpmu_new_blog', 'init_plugin_suite_user_engine_on_new_blog', 10, 6 );

// Chỉ chạy check_table khi version trong DB khác với version hiện tại của code
add_action( 'admin_init', function() {
    // Lấy version đã lưu trong DB
    $current_db_version = get_option( 'iue_plugin_db_version', '0.0.0' );
    
    // So sánh với version hiện tại trong code
    if ( version_compare( $current_db_version, INIT_PLUGIN_SUITE_IUE_VERSION, '<' ) ) {
        init_plugin_suite_user_engine_check_table();
    }
} );

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
            init_plugin_suite_user_engine_create_transaction_log_table();
            init_plugin_suite_user_engine_create_exp_log_table();
            restore_current_blog();
        }
    } else {
        init_plugin_suite_user_engine_create_inbox_table();
        init_plugin_suite_user_engine_create_redeem_code_table();
        init_plugin_suite_user_engine_create_transaction_log_table();
        init_plugin_suite_user_engine_create_exp_log_table();
    }

    update_option( 'iue_plugin_db_version', INIT_PLUGIN_SUITE_IUE_VERSION );
}

/**
 * Xử lý khi tạo site mới trong multisite
 */
function init_plugin_suite_user_engine_on_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    switch_to_blog( $blog_id );
    init_plugin_suite_user_engine_create_inbox_table();
    init_plugin_suite_user_engine_create_redeem_code_table();
    init_plugin_suite_user_engine_create_transaction_log_table();
    init_plugin_suite_user_engine_create_exp_log_table();
    restore_current_blog();
}

/**
 * Kiểm tra và tạo bảng nếu cần thiết (admin_init hook)
 * Đồng thời chạy migration dữ liệu nếu chưa chạy.
 */
function init_plugin_suite_user_engine_check_table() {
    if ( ! current_user_can( 'administrator' ) ) {
        return;
    }

    global $wpdb;

    // INBOX
    $inbox_table = $wpdb->prefix . 'init_user_engine_inbox';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$inbox_table'" ) !== $inbox_table ) {
        init_plugin_suite_user_engine_create_inbox_table();
    }

    // REDEEM CODE
    $redeem_table = $wpdb->prefix . 'init_user_engine_redeem_codes';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$redeem_table'" ) !== $redeem_table ) {
        init_plugin_suite_user_engine_create_redeem_code_table();
    }

    // TRANSACTION LOG
    $txn_table = $wpdb->prefix . 'init_user_engine_transaction_log';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$txn_table'" ) !== $txn_table ) {
        init_plugin_suite_user_engine_create_transaction_log_table();
    }

    // EXP LOG
    $exp_table = $wpdb->prefix . 'init_user_engine_exp_log';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$exp_table'" ) !== $exp_table ) {
        init_plugin_suite_user_engine_create_exp_log_table();
    }

    // Chạy migration nếu chưa hoàn thành
    init_plugin_suite_user_engine_maybe_migrate_logs();

    update_option( 'iue_plugin_db_version', INIT_PLUGIN_SUITE_IUE_VERSION );
}

/**
 * Hàm tạo bảng inbox
 */
function init_plugin_suite_user_engine_create_inbox_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'init_user_engine_inbox';
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
 */
function init_plugin_suite_user_engine_create_redeem_code_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'init_user_engine_redeem_codes';
    $charset_collate = $wpdb->get_charset_collate();

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

/**
 * Tạo bảng transaction log (coin/cash).
 * Thay thế user meta iue_coin_cash_log.
 *
 * Columns:
 *   - type:           'coin' | 'cash'
 *   - amount:         số tiền cuối (đã cộng VIP bonus nếu có)
 *   - original_amount: số tiền gốc trước khi cộng bonus
 *   - change_type:    'add' | 'deduct'  (tránh dùng `change` vì là reserved word trong MySQL)
 *   - source:         nguồn giao dịch (daily_login, woo_order, ...)
 *   - vip_bonus:      0/1 — user có VIP lúc giao dịch không
 *   - bonus_percent:  phần trăm bonus VIP được áp dụng
 *   - logged_at:      datetime giao dịch
 */
function init_plugin_suite_user_engine_create_transaction_log_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'init_user_engine_transaction_log';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        type VARCHAR(10) NOT NULL DEFAULT 'coin',
        amount BIGINT SIGNED NOT NULL DEFAULT 0,
        original_amount BIGINT SIGNED NOT NULL DEFAULT 0,
        change_type VARCHAR(10) NOT NULL DEFAULT 'add',
        source VARCHAR(100) NOT NULL DEFAULT '',
        vip_bonus TINYINT(1) NOT NULL DEFAULT 0,
        bonus_percent INT UNSIGNED NOT NULL DEFAULT 0,
        logged_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY user_type (user_id, type),
        KEY user_logged_at (user_id, logged_at),
        KEY source (source),
        KEY logged_at (logged_at)
    ) $charset_collate;";

    dbDelta( $sql );
}

/**
 * Tạo bảng EXP log.
 * Thay thế user meta iue_exp_log.
 *
 * Columns:
 *   - amount:      lượng EXP thay đổi
 *   - change_type: 'add' | 'deduct'
 *   - source:      nguồn (daily_login, level_up_5, ...)
 *   - vip_bonus:   0/1 — user có VIP lúc giao dịch không
 *   - logged_at:   datetime
 */
function init_plugin_suite_user_engine_create_exp_log_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'init_user_engine_exp_log';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        amount BIGINT SIGNED NOT NULL DEFAULT 0,
        change_type VARCHAR(10) NOT NULL DEFAULT 'add',
        source VARCHAR(100) NOT NULL DEFAULT '',
        vip_bonus TINYINT(1) NOT NULL DEFAULT 0,
        logged_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY user_logged_at (user_id, logged_at),
        KEY source (source),
        KEY logged_at (logged_at)
    ) $charset_collate;";

    dbDelta( $sql );
}

// ==========================
// Migration: meta → DB table
// ==========================

/**
 * Migration version key. Tăng lên nếu cần chạy lại migration.
 */
define( 'INIT_PLUGIN_SUITE_IUE_LOG_MIGRATION_VERSION', 1 );

/**
 * Chạy migration một lần duy nhất (idempotent).
 * Batch 200 users mỗi lần, lưu offset vào option để resume nếu bị gián đoạn.
 *
 * Gọi trong admin_init nên chỉ chạy khi admin load trang.
 * Với site lớn (~10k users), migration có thể mất vài lần page load.
 */
function init_plugin_suite_user_engine_maybe_migrate_logs() {
    $done_version = (int) get_option( 'iue_log_migration_done', 0 );
    if ( $done_version >= INIT_PLUGIN_SUITE_IUE_LOG_MIGRATION_VERSION ) {
        return; // Đã migrate xong
    }

    // Nếu bảng chưa sẵn sàng thì bỏ qua (safety)
    global $wpdb;
    $txn_table = $wpdb->prefix . 'init_user_engine_transaction_log';
    $exp_table = $wpdb->prefix . 'init_user_engine_exp_log';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$txn_table'" ) !== $txn_table ) return;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$exp_table'" ) !== $exp_table ) return;

    $batch_size  = 200;
    $offset_key  = 'iue_log_migration_offset';
    $offset      = (int) get_option( $offset_key, 0 );

    // Lấy danh sách users có meta cần migrate (cả 2 loại)
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $user_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$wpdb->usermeta}
             WHERE meta_key IN ('iue_coin_cash_log', 'iue_exp_log')
             ORDER BY user_id ASC
             LIMIT %d OFFSET %d",
            $batch_size,
            $offset
        )
    );

    if ( empty( $user_ids ) ) {
        // Migrate xong toàn bộ
        update_option( 'iue_log_migration_done', INIT_PLUGIN_SUITE_IUE_LOG_MIGRATION_VERSION, false );
        delete_option( $offset_key );
        return;
    }

    foreach ( $user_ids as $user_id ) {
        $user_id = (int) $user_id;

        // --- Migrate transaction log ---
        $coin_cash_meta = get_user_meta( $user_id, 'iue_coin_cash_log', true );
        if ( ! empty( $coin_cash_meta ) && is_array( $coin_cash_meta ) ) {
            init_plugin_suite_user_engine_migrate_transaction_entries( $user_id, $coin_cash_meta );
            delete_user_meta( $user_id, 'iue_coin_cash_log' );
        }

        // --- Migrate EXP log ---
        $exp_meta = get_user_meta( $user_id, 'iue_exp_log', true );
        if ( ! empty( $exp_meta ) && is_array( $exp_meta ) ) {
            init_plugin_suite_user_engine_migrate_exp_entries( $user_id, $exp_meta );
            delete_user_meta( $user_id, 'iue_exp_log' );
        }
    }

    // Lưu offset để lần sau tiếp tục (nếu còn user)
    update_option( $offset_key, $offset + count( $user_ids ), false );

    // Nếu batch này < batch_size thì không còn user nào nữa
    if ( count( $user_ids ) < $batch_size ) {
        update_option( 'iue_log_migration_done', INIT_PLUGIN_SUITE_IUE_LOG_MIGRATION_VERSION, false );
        delete_option( $offset_key );
    }
}

/**
 * Insert các entries transaction (coin/cash) của 1 user vào DB.
 * Bỏ qua entry đã tồn tại (dựa trên user_id + logged_at + source + change_type + amount).
 *
 * @param int   $user_id
 * @param array $entries  Mảng log từ user meta.
 */
function init_plugin_suite_user_engine_migrate_transaction_entries( $user_id, array $entries ) {
    global $wpdb;
    $table = $wpdb->prefix . 'init_user_engine_transaction_log';

    foreach ( $entries as $entry ) {
        if ( ! is_array( $entry ) ) continue;

        $type            = in_array( $entry['type'] ?? '', [ 'coin', 'cash' ], true ) ? $entry['type'] : 'coin';
        $amount          = (int) ( $entry['amount'] ?? 0 );
        $original_amount = (int) ( $entry['original'] ?? $amount );
        $change_type     = in_array( $entry['change'] ?? '', [ 'add', 'deduct' ], true ) ? $entry['change'] : 'add';
        $source          = sanitize_text_field( $entry['source'] ?? '' );
        $vip_bonus       = ! empty( $entry['vip_bonus'] ) ? 1 : 0;
        $bonus_percent   = (int) ( $entry['bonus_percent'] ?? 0 );
        $logged_at       = ! empty( $entry['time'] ) ? $entry['time'] : current_time( 'Y-m-d H:i:s' );

        // Validate datetime format
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $logged_at ) ) {
            $logged_at = current_time( 'Y-m-d H:i:s' );
        }

        // Idempotent: skip nếu đã có bản ghi giống hệt (cùng user, source, time, amount, change_type)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE user_id = %d AND source = %s AND logged_at = %s AND amount = %d AND change_type = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $user_id, $source, $logged_at, $amount, $change_type
            )
        );
        if ( $exists ) continue;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            $table,
            [
                'user_id'         => $user_id,
                'type'            => $type,
                'amount'          => $amount,
                'original_amount' => $original_amount,
                'change_type'     => $change_type,
                'source'          => $source,
                'vip_bonus'       => $vip_bonus,
                'bonus_percent'   => $bonus_percent,
                'logged_at'       => $logged_at,
            ],
            [ '%d', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%s' ]
        );
    }
}

/**
 * Insert các entries EXP của 1 user vào DB.
 *
 * @param int   $user_id
 * @param array $entries  Mảng log từ user meta.
 */
function init_plugin_suite_user_engine_migrate_exp_entries( $user_id, array $entries ) {
    global $wpdb;
    $table = $wpdb->prefix . 'init_user_engine_exp_log';

    foreach ( $entries as $entry ) {
        if ( ! is_array( $entry ) ) continue;

        $amount      = (int) ( $entry['amount'] ?? 0 );
        $change_type = in_array( $entry['change'] ?? '', [ 'add', 'deduct' ], true ) ? $entry['change'] : 'add';
        $source      = sanitize_text_field( $entry['source'] ?? '' );
        $vip_bonus   = ! empty( $entry['vip_bonus'] ) ? 1 : 0;
        $logged_at   = ! empty( $entry['time'] ) ? $entry['time'] : current_time( 'Y-m-d H:i:s' );

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $logged_at ) ) {
            $logged_at = current_time( 'Y-m-d H:i:s' );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE user_id = %d AND source = %s AND logged_at = %s AND amount = %d AND change_type = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $user_id, $source, $logged_at, $amount, $change_type
            )
        );
        if ( $exists ) continue;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            $table,
            [
                'user_id'     => $user_id,
                'amount'      => $amount,
                'change_type' => $change_type,
                'source'      => $source,
                'vip_bonus'   => $vip_bonus,
                'logged_at'   => $logged_at,
            ],
            [ '%d', '%d', '%s', '%s', '%d', '%s' ]
        );
    }
}

// Sự kiện chạy khi update plugin thành công
add_action( 'upgrader_process_complete', 'init_plugin_suite_user_engine_on_update', 10, 2 );

function init_plugin_suite_user_engine_on_update( $upgrader_object, $options ) {
    // Kiểm tra xem có đúng là hành động update plugin không
    if ( isset( $options['action'] ) && $options['action'] === 'update' && $options['type'] === 'plugin' ) {
        if ( isset( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
            foreach ( $options['plugins'] as $plugin_path ) {
                // Nếu path của plugin đang update có chứa slug 'init-user-engine'
                if ( strpos( $plugin_path, INIT_PLUGIN_SUITE_IUE_SLUG ) !== false ) {
                    init_plugin_suite_user_engine_check_table();
                    break;
                }
            }
        }
    }
}
