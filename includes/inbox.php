<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Get inbox table name
function init_plugin_suite_user_engine_get_inbox_table() {
	global $wpdb;
	return $wpdb->prefix . 'init_user_engine_inbox';
}

// Insert a new inbox message
function init_plugin_suite_user_engine_insert_inbox( $user_id, $title, $content, $type = 'system', $metadata = [], $expire_at = null, $priority = 'normal', $link = '', $pinned = 0 ) {
	global $wpdb;

	$data = [
		'user_id'    => $user_id,
		'title'      => $title,
		'content'    => $content,
		'type'       => $type,
		'status'     => 'unread',
		'created_at' => current_time( 'timestamp' ),
		'priority'   => $priority,
		'pinned'     => $pinned ? 1 : 0,
		'link'       => $link ?: null,
		'metadata'   => maybe_serialize( $metadata ),
	];

	if ( $expire_at ) {
		$data['expire_at'] = is_numeric( $expire_at ) ? (int) $expire_at : strtotime( $expire_at );
	}

	$data = apply_filters( 'init_plugin_suite_user_engine_inbox_insert_data', $data, $user_id );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->insert( init_plugin_suite_user_engine_get_inbox_table(), $data );

	do_action( 'init_plugin_suite_user_engine_inbox_inserted', $wpdb->insert_id, $user_id, $data );

	return $wpdb->insert_id;
}

// Send inbox helper
function init_plugin_suite_user_engine_send_inbox( $user_id, $title, $content, $type = 'system', $meta = [], $expire = null, $priority = 'normal', $link = '', $pinned = 0 ) {
	return init_plugin_suite_user_engine_insert_inbox( $user_id, $title, $content, $type, $meta, $expire, $priority, $link, $pinned );
}

// Gửi cho nhiều người (nâng cấp: dùng bulk insert)
function init_plugin_suite_user_engine_send_inbox_to_users(
    $user_ids,
    $title,
    $content,
    $type      = 'system',
    $meta      = [],
    $expire    = null,
    $priority  = 'normal',
    $link      = '',
    $pinned    = 0
) {
    // Cho phép tuỳ chỉnh kích thước batch qua filter (mặc định 500)
    $chunk_size = (int) apply_filters( 'init_plugin_suite_user_engine_inbox_bulk_chunk_size', 500, $user_ids, $title, $type );

    // Dùng hàm bulk mới cho hiệu năng
    $inserted = init_plugin_suite_user_engine_send_inbox_to_users_bulk(
        $user_ids,
        $title,
        $content,
        $type,
        $meta,
        $expire,
        $priority,
        $link,
        $pinned,
        $chunk_size
    );

    // Fallback an toàn: nếu bulk thất bại vì lý do nào đó, quay về loop từng user (giữ tương thích)
    if ( ! $inserted ) {
        $inserted = 0;
        foreach ( (array) $user_ids as $uid ) {
            $id = init_plugin_suite_user_engine_send_inbox( $uid, $title, $content, $type, $meta, $expire, $priority, $link, $pinned );
            if ( $id ) {
                $inserted++;
            }
        }
    }

    return (int) $inserted;
}

// Get inbox messages (paginated)
function init_plugin_suite_user_engine_get_inbox( $user_id, $page = 1, $per_page = 20, $filter = 'all' ) {
    global $wpdb;

    $offset = ( $page - 1 ) * $per_page;
    $table  = init_plugin_suite_user_engine_get_inbox_table();

    $where  = [ 'user_id = %d' ];
    $params = [ (int) $user_id ];

    // ===== filter logic =====
    if ( 'unread' === $filter ) {
        $where[] = "status = 'unread'";
    }
    elseif ( in_array( $filter, [ 'system', 'rewards', 'activity' ], true ) ) {

        $map = init_plugin_suite_user_engine_get_inbox_group_map();

        if ( ! empty( $map[ $filter ] ) ) {
            $types = array_map( 'sanitize_key', (array) $map[ $filter ] );

            $placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );
            $where[] = "type IN ($placeholders)";
            $params  = array_merge( $params, $types );
        }
    }
    elseif ( 'other' === $filter ) {

        $map = init_plugin_suite_user_engine_get_inbox_group_map();
        $all = array_map( 'sanitize_key', array_merge( ...array_values( $map ) ) );

        if ( ! empty( $all ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $all ), '%s' ) );
            $where[] = "type NOT IN ($placeholders)";
            $params  = array_merge( $params, $all );
        }
    }

    $where_sql = implode( ' AND ', $where );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $results = $wpdb->get_results(
        // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
        $wpdb->prepare(
        	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge( $params, [ (int) $per_page, (int) $offset ] )
        ),
        ARRAY_A
    );

    foreach ( $results as &$item ) {
        $item['metadata'] = maybe_unserialize( $item['metadata'] );
    }

    return $results;
}

// Count all inbox messages
function init_plugin_suite_user_engine_count_inbox( $user_id, $filter = 'all' ) {
    global $wpdb;

    $table = init_plugin_suite_user_engine_get_inbox_table();

    $where  = [ 'user_id = %d' ];
    $params = [ $user_id ];

    if ( 'unread' === $filter ) {
        $where[] = "status = 'unread'";
    }
    elseif ( in_array( $filter, [ 'system', 'rewards', 'activity' ], true ) ) {

        $map = init_plugin_suite_user_engine_get_inbox_group_map();

        if ( ! empty( $map[ $filter ] ) ) {
            $types = $map[ $filter ];
            $placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );

            $where[] = "type IN ($placeholders)";
            $params  = array_merge( $params, $types );
        }
    }
    elseif ( 'other' === $filter ) {

        $map = init_plugin_suite_user_engine_get_inbox_group_map();
        $all = array_merge( ...array_values( $map ) );

        if ( ! empty( $all ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $all ), '%s' ) );

            $where[] = "type NOT IN ($placeholders)";
            $params  = array_merge( $params, $all );
        }
    }

    $where_sql = implode( ' AND ', $where );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
    return (int) $wpdb->get_var(
        $wpdb->prepare(
        	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}",
            $params
        )
    );
}

// Get count of unread inbox messages for a user
function init_plugin_suite_user_engine_get_unread_inbox_count( $user_id ) {
	global $wpdb;

	if ( ! $user_id ) {
		return 0;
	}

	$table = init_plugin_suite_user_engine_get_inbox_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$count = $wpdb->get_var(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND status = 'unread'",
			$user_id
		)
	);

	return (int) $count;
}

// Mark inbox message as read
function init_plugin_suite_user_engine_mark_inbox_read( $message_id, $user_id ) {
	global $wpdb;
	$table = init_plugin_suite_user_engine_get_inbox_table();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	return $wpdb->update(
		$table,
		[ 'status' => 'read' ],
		[ 'id' => $message_id, 'user_id' => $user_id ]
	);
}

// Mark inbox message as claimed
function init_plugin_suite_user_engine_mark_inbox_claimed( $message_id, $user_id ) {
	global $wpdb;
	$table = init_plugin_suite_user_engine_get_inbox_table();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	return $wpdb->update(
		$table,
		[ 'status' => 'claimed' ],
		[ 'id' => $message_id, 'user_id' => $user_id ]
	);
}

// Get single inbox item
function init_plugin_suite_user_engine_get_inbox_item( $message_id, $user_id ) {
	global $wpdb;
	$table = init_plugin_suite_user_engine_get_inbox_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$row = $wpdb->get_row(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$table} WHERE id = %d AND user_id = %d",
			$message_id, $user_id
		),
		ARRAY_A
	);

	if ( $row ) {
		$row['metadata'] = maybe_unserialize( $row['metadata'] );
	}

	return $row;
}

// Delete one inbox item
function init_plugin_suite_user_engine_delete_inbox( $message_id, $user_id ) {
	global $wpdb;
	$table = init_plugin_suite_user_engine_get_inbox_table();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	return $wpdb->delete( $table, [ 'id' => $message_id, 'user_id' => $user_id ] );
}

// Delete all inbox items of a user
function init_plugin_suite_user_engine_delete_all_inbox( $user_id ) {
	global $wpdb;
	$table = init_plugin_suite_user_engine_get_inbox_table();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	return $wpdb->delete( $table, [ 'user_id' => $user_id ] );
}

// GET /inbox
function init_plugin_suite_user_engine_api_get_inbox( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
	}

	$page     = max( 1, (int) $request->get_param( 'page' ) );
	$per_page = max( 1, min( 50, (int) $request->get_param( 'per_page' ) ) );
	$filter   = sanitize_key( $request->get_param( 'filter' ) ?: 'all' );
	$total 	  = init_plugin_suite_user_engine_count_inbox( $user_id, $filter );
	$data 	  = init_plugin_suite_user_engine_get_inbox( $user_id, $page, $per_page, $filter );

	$formatted = array_map( 'init_plugin_suite_user_engine_format_inbox', $data );

	return rest_ensure_response( [
		'page'        => $page,
		'per_page'    => $per_page,
		'total'       => $total,
		'total_pages' => ceil( $total / $per_page ),
		'data'        => $formatted,
	] );
}

// POST /inbox/mark-read
function init_plugin_suite_user_engine_api_mark_inbox_read( WP_REST_Request $request ) {
	$user_id    = get_current_user_id();
	$message_id = (int) $request->get_param( 'id' );

	if ( ! $user_id || ! $message_id ) {
		return new WP_Error( 'invalid_request', 'Invalid request', [ 'status' => 400 ] );
	}

	$updated = init_plugin_suite_user_engine_mark_inbox_read( $message_id, $user_id );

	if ( ! $updated ) {
		return new WP_Error( 'update_failed', 'Cannot mark as read', [ 'status' => 500 ] );
	}

	return rest_ensure_response( [ 'status' => 'marked', 'id' => $message_id ] );
}

// POST /inbox/mark-all-read
function init_plugin_suite_user_engine_api_mark_inbox_all_read( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
	}

	global $wpdb;
	$table = init_plugin_suite_user_engine_get_inbox_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$updated = $wpdb->query(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"UPDATE {$table} SET status = 'read' WHERE user_id = %d AND status = 'unread'",
			$user_id
		)
	);

	return rest_ensure_response( [ 'status' => 'all_marked', 'count' => $updated ] );
}

// POST /inbox/delete
function init_plugin_suite_user_engine_api_delete_inbox_item( WP_REST_Request $request ) {
	$user_id    = get_current_user_id();
	$message_id = (int) $request->get_param( 'id' );

	if ( ! $user_id || ! $message_id ) {
		return new WP_Error( 'invalid_request', 'Invalid request', [ 'status' => 400 ] );
	}

	$deleted = init_plugin_suite_user_engine_delete_inbox( $message_id, $user_id );

	if ( ! $deleted ) {
		return new WP_Error( 'delete_failed', 'Cannot delete message', [ 'status' => 500 ] );
	}

	return rest_ensure_response( [ 'status' => 'deleted', 'id' => $message_id ] );
}

// POST /inbox/delete-all
function init_plugin_suite_user_engine_api_delete_inbox_all( WP_REST_Request $request ) {
	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
	}

	$count = init_plugin_suite_user_engine_delete_all_inbox( $user_id );

	return rest_ensure_response( [ 'status' => 'all_deleted', 'count' => $count ] );
}

// Format inbox
function init_plugin_suite_user_engine_format_inbox( $item ) {
	$link = ! empty( $item['link'] ) ? esc_url_raw( $item['link'] ) : '';

	$formatted = [
		'id'        => (int) $item['id'],
		'title'     => $item['title'],
		'content'   => $item['content'],
		'type'      => $item['type'],
		'status'    => $item['status'],
		'time'      => init_plugin_suite_user_engine_time_ago( $item['created_at'] ),
		'priority'  => $item['priority'],
		'pinned'    => (bool) $item['pinned'],
		'link'      => $link,
		'metadata'  => maybe_unserialize( $item['metadata'] ),
	];

	return apply_filters( 'init_plugin_suite_user_engine_format_inbox', $formatted, $item );
}

// Đăng ký cron job weekly để dọn dẹp inbox mồ côi
function init_plugin_suite_user_engine_schedule_cleanup() {
    if (!wp_next_scheduled('init_plugin_suite_user_engine_cleanup_orphaned_inbox')) {
        wp_schedule_event(time(), 'weekly', 'init_plugin_suite_user_engine_cleanup_orphaned_inbox');
    }
}
add_action('wp', 'init_plugin_suite_user_engine_schedule_cleanup');

// Hook để thực hiện cleanup khi cron chạy
add_action('init_plugin_suite_user_engine_cleanup_orphaned_inbox', 'init_plugin_suite_user_engine_cleanup_orphaned_inbox_handler');

/**
 * Hàm xử lý dọn dẹp inbox mồ côi
 * Xóa các inbox thuộc về user_id không tồn tại nữa
 */
function init_plugin_suite_user_engine_cleanup_orphaned_inbox_handler() {
    global $wpdb;
    
    $inbox_table = $wpdb->prefix . 'init_user_engine_inbox';
    $users_table = $wpdb->prefix . 'users';
    
    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query("DELETE i FROM {$inbox_table} i LEFT JOIN {$users_table} u ON i.user_id = u.ID WHERE u.ID IS NULL");
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Lấy danh sách type hiện có trong inbox
function init_plugin_suite_user_engine_get_inbox_types() {
    global $wpdb;
    $table = init_plugin_suite_user_engine_get_inbox_table();

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $rows = $wpdb->get_col( "SELECT DISTINCT type FROM {$table} WHERE type IS NOT NULL AND type <> '' ORDER BY type ASC" );
    if ( ! is_array( $rows ) ) {
        $rows = array();
    }
    return array_map( 'sanitize_text_field', $rows );
}

// Handle cleanup inbox by type
add_action( 'admin_post_iue_cleanup_inbox_type', 'init_plugin_suite_user_engine_handle_cleanup_inbox_type' );
function init_plugin_suite_user_engine_handle_cleanup_inbox_type() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to perform this action.', 'init-user-engine' ) );
    }

    check_admin_referer( 'iue_cleanup_inbox_type' );

    $type  = isset( $_POST['iue_cleanup_type'] ) ? sanitize_text_field( wp_unslash( $_POST['iue_cleanup_type'] ) ) : '';
    $types = init_plugin_suite_user_engine_get_inbox_types();

    if ( empty( $type ) || ! in_array( $type, $types, true ) ) {
        wp_safe_redirect( add_query_arg(
            array(
                'page'               => 'init-user-engine-inbox-stats',
                'iue_cleanup_done'   => 1,
                'iue_cleanup_status' => 'invalid',
            ),
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    global $wpdb;
    $table = init_plugin_suite_user_engine_get_inbox_table();

    // Xoá theo type (sử dụng prepare để an toàn)
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE type = %s", $type ) );

    wp_safe_redirect( add_query_arg(
        array(
            'page'               => 'init-user-engine-inbox-stats',
            'iue_cleanup_done'   => 1,
            'iue_cleanup_status' => 'ok',
            'iue_cleanup_type'   => rawurlencode( $type ),
            'iue_deleted'        => (int) $deleted,
        ),
        admin_url( 'admin.php' )
    ) );
    exit;
}

/**
 * Bulk-insert inbox messages cho nhiều user trong 1 hoặc vài query.
 *
 * @param int[]        $user_ids   Mảng user ID.
 * @param string       $title
 * @param string       $content
 * @param string       $type
 * @param array        $metadata
 * @param int|string|null $expire_at  UNIX ts, string parse-able, hoặc null.
 * @param string       $priority   normal|high|low...
 * @param string       $link
 * @param int|bool     $pinned
 * @param int          $chunk_size Số bản ghi mỗi lô (500–1000 là ổn).
 *
 * @return int Tổng số rows được insert.
 */
function init_plugin_suite_user_engine_insert_inbox_bulk(
    $user_ids,
    $title,
    $content,
    $type      = 'system',
    $metadata  = [],
    $expire_at = null,
    $priority  = 'normal',
    $link      = '',
    $pinned    = 0,
    $chunk_size = 500
) {
    global $wpdb;

    $table = init_plugin_suite_user_engine_get_inbox_table();

    // Chuẩn hoá input
    $user_ids = array_values( array_unique( array_map( 'intval', (array) $user_ids ) ) );
    $user_ids = array_filter( $user_ids, static function( $v ) { return $v > 0; } );
    if ( empty( $user_ids ) ) {
        return 0;
    }

    $created_at = current_time( 'timestamp' );
    $pinned     = $pinned ? 1 : 0;
    $meta_str   = maybe_serialize( $metadata );
    $link_str   = $link ?: ''; // để trống thay vì NULL cho đơn giản & ổn định.

    // Chuẩn hoá expire
    if ( $expire_at ) {
        $expire_at = is_numeric( $expire_at ) ? (int) $expire_at : strtotime( $expire_at );
        if ( $expire_at <= 0 ) {
            $expire_at = null;
        }
    } else {
        $expire_at = null;
    }

    $total_inserted = 0;

    // Chia lô để tránh query quá dài
    foreach ( array_chunk( $user_ids, max( 1, (int) $chunk_size ) ) as $batch ) {
        $values_sql = [];
        $params     = [];

        foreach ( $batch as $uid ) {
            // Cho phép tuỳ biến từng hàng trước khi build SQL
            $row = apply_filters( 'init_plugin_suite_user_engine_inbox_bulk_row_data', [
                'user_id'    => $uid,
                'title'      => $title,
                'content'    => $content,
                'type'       => $type,
                'status'     => 'unread',
                'created_at' => $created_at,
                'priority'   => $priority,
                'pinned'     => $pinned,
                'link'       => $link_str,
                'metadata'   => $meta_str,
                'expire_at'  => $expire_at,
            ], $uid );

            // Hàng với expire_at NULL thì chèn NULL trực tiếp để không đổi semantics
            $placeholders =
                "(%d,%s,%s,%s,'unread',%d,%s,%d,%s,%s," . ( is_null( $row['expire_at'] ) ? "NULL" : "%d" ) . ")";

            $values_sql[] = $placeholders;

            // Thứ tự tham số khớp với placeholders
            $params[] = (int) $row['user_id'];  // %d
            $params[] = (string) $row['title']; // %s
            $params[] = (string) $row['content'];
            $params[] = (string) $row['type'];
            $params[] = (int)    $row['created_at'];
            $params[] = (string) $row['priority'];
            $params[] = (int)    $row['pinned'];
            $params[] = (string) $row['link'];
            $params[] = (string) $row['metadata'];

            if ( ! is_null( $row['expire_at'] ) ) {
                $params[] = (int) $row['expire_at'];
            }
        }

        // Cột theo đúng thứ tự placeholders ở trên
        $sql  = "INSERT INTO {$table} 
                (user_id,title,content,type,status,created_at,priority,pinned,link,metadata,expire_at)
                VALUES " . implode( ',', $values_sql );

        // Transaction cho an toàn & tốc độ (InnoDB)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( 'START TRANSACTION' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $result = $wpdb->query( $wpdb->prepare( $sql, $params ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( 'COMMIT' );

        $inserted = (int) $result;
        $total_inserted += $inserted;

        /**
         * Action: đã insert xong một batch.
         *
         * @param int   $inserted   Số row chèn thành công trong batch.
         * @param array $batch      Danh sách user_id của batch.
         * @param array $context    Ngữ cảnh dùng để log/metrics.
         */
        do_action( 'init_plugin_suite_user_engine_inbox_bulk_inserted', $inserted, $batch, [
            'title'      => $title,
            'type'       => $type,
            'priority'   => $priority,
            'pinned'     => $pinned,
            'has_expire' => ! is_null( $expire_at ),
        ] );
    }

    return $total_inserted;
}

/**
 * Helper: Gửi inbox cho nhiều user bằng bulk insert (thay cho loop).
 */
function init_plugin_suite_user_engine_send_inbox_to_users_bulk(
    $user_ids,
    $title,
    $content,
    $type      = 'system',
    $meta      = [],
    $expire    = null,
    $priority  = 'normal',
    $link      = '',
    $pinned    = 0,
    $chunk_size = 500
) {
    return init_plugin_suite_user_engine_insert_inbox_bulk(
        $user_ids, $title, $content, $type, $meta, $expire, $priority, $link, $pinned, $chunk_size
    );
}

/**
 * Map inbox type → group
 */
function init_plugin_suite_user_engine_get_inbox_group_map() {
    return [
        'system' => [
            'system',
            'welcome',
            'vip',
            'withdraw_request_result',
            'level_up',
            'checkin',
        ],
        'rewards' => [
            'lucky_wheel_result',
            'loot_box_reward',
            'review_reward',
            'gift',
            'gift_received',
        ],
        'activity' => [
            'chapter_update',
            'recommendation',
            'comment_like',
            'comment_reply',
            'tag_notification',
        ],
    ];
}
