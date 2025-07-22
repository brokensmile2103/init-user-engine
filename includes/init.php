<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================
// Create Database
// ==========================

register_activation_hook( __FILE__, 'init_plugin_suite_user_engine_on_activation' );
add_action( 'wpmu_new_blog', 'init_plugin_suite_user_engine_on_new_blog', 10, 6 );

/**
 * Xử lý khi plugin được activate (site đơn hoặc toàn mạng)
 */
function init_plugin_suite_user_engine_on_activation() {
    if ( is_multisite() ) {
        $sites = get_sites( [ 'number' => 0 ] ); // lấy tất cả site
        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );
            init_plugin_suite_user_engine_create_inbox_table();
            restore_current_blog();
        }
    } else {
        init_plugin_suite_user_engine_create_inbox_table();
    }
}

/**
 * Xử lý khi tạo site mới trong multisite
 */
function init_plugin_suite_user_engine_on_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    switch_to_blog( $blog_id );
    init_plugin_suite_user_engine_create_inbox_table();
    restore_current_blog();
}

/**
 * Hàm tạo bảng inbox
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
