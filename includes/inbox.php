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

// Gửi cho nhiều người
function init_plugin_suite_user_engine_send_inbox_to_users( $user_ids, $title, $content, $type = 'system', $meta = [], $expire = null, $priority = 'normal', $link = '', $pinned = 0 ) {
	foreach ( $user_ids as $user_id ) {
		init_plugin_suite_user_engine_send_inbox( $user_id, $title, $content, $type, $meta, $expire, $priority, $link, $pinned );
	}
}

// Get inbox messages (paginated)
function init_plugin_suite_user_engine_get_inbox( $user_id, $page = 1, $per_page = 20 ) {
	global $wpdb;

	$offset = ( $page - 1 ) * $per_page;
	$table  = init_plugin_suite_user_engine_get_inbox_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$results = $wpdb->get_results(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$user_id, $per_page, $offset
		),
		ARRAY_A
	);

	foreach ( $results as &$item ) {
		$item['metadata'] = maybe_unserialize( $item['metadata'] );
	}

	return $results;
}

// Count all inbox messages
function init_plugin_suite_user_engine_count_inbox( $user_id ) {
	global $wpdb;
	$table = init_plugin_suite_user_engine_get_inbox_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
			$user_id
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

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
	$total    = init_plugin_suite_user_engine_count_inbox( $user_id );
	$data     = init_plugin_suite_user_engine_get_inbox( $user_id, $page, $per_page );

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

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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