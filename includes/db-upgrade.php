<?php
/**
 * Nâng cấp index cho các bảng dữ liệu lớn của plugin (Inbox, Transaction log, EXP log).
 *
 * Vì sao cần file riêng:
 * Trên site lớn, các bảng này có thể lên tới hàng triệu dòng (vd Inbox 1.2M+,
 * Transaction log 7M+). Thêm/xóa index bằng dbDelta() ngay trong 1 request
 * admin là rủi ro: request có thể timeout giữa chừng, 2 request admin (vd
 * Heartbeat) có thể cùng chạy ALTER, và 1 ALTER phải chờ metadata lock sẽ
 * chặn luôn mọi truy vấn khác vào bảng đó. Vì vậy việc nâng cấp index:
 *
 * 1. Chạy nền qua WP-Cron (site nhỏ/mới cài thì chạy ngay vì gần như tức thì).
 * 2. Dùng ALTER TABLE ... ALGORITHM=INPLACE, LOCK=NONE: online, không khóa
 *    bảng; nếu server không làm online được thì ALTER báo lỗi thay vì khóa.
 * 3. Giới hạn lock_wait_timeout ngắn để không bao giờ "xếp hàng" chặn truy vấn
 *    khác quá vài giây; thất bại thì tự thử lại sau 1 giờ.
 * 4. Có mutex để không bao giờ có 2 tiến trình cùng ALTER 1 lúc.
 * 5. Chỉ thêm index còn thiếu và chỉ xóa index đơn cột `user_id` khi đã có
 *    index ghép bắt đầu bằng `user_id` thay thế (index thừa, chỉ tốn ghi).
 *
 * Admin cũng có thể tự chạy các câu ALTER tương ứng bằng tay lúc ít truy cập:
 * plugin sẽ tự nhận ra index đã có và đánh dấu hoàn tất.
 *
 * @package Init_User_Engine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Phiên bản bộ index hiện tại. Tăng số này khi thay đổi init_plugin_suite_user_engine_get_index_plan().
 */
define( 'INIT_PLUGIN_SUITE_IUE_DB_INDEX_VERSION', 1 );

/**
 * Tên WP-Cron event chạy nâng cấp index.
 */
define( 'INIT_PLUGIN_SUITE_IUE_DB_INDEX_EVENT', 'init_plugin_suite_user_engine_db_index_upgrade' );

/**
 * Số lần thử lại tối đa (mỗi lần cách nhau 1 giờ) trước khi bỏ cuộc.
 */
define( 'INIT_PLUGIN_SUITE_IUE_DB_INDEX_MAX_ATTEMPTS', 24 );

add_action( INIT_PLUGIN_SUITE_IUE_DB_INDEX_EVENT, 'init_plugin_suite_user_engine_run_index_upgrade' );
add_action( 'admin_init', 'init_plugin_suite_user_engine_maybe_upgrade_indexes_on_admin' );

/**
 * Bộ index mong muốn cho từng bảng (tên bảng không kèm prefix).
 *
 * - keys:      index cần có (tên => danh sách cột). Index nào còn thiếu sẽ được thêm.
 *              Bỏ qua nếu đã có index trùng tên hoặc trùng y hệt danh sách cột.
 * - redundant: index được phép xóa (tên => danh sách cột), CHỈ khi còn 1 index
 *              khác bắt đầu bằng đúng các cột đó (nên mọi truy vấn vẫn dùng được).
 *
 * @return array
 */
function init_plugin_suite_user_engine_get_index_plan() {
	return [
		'init_user_engine_inbox'           => [
			'keys'      => [
				// Đếm tin chưa đọc / "Mark all as read" / tab "Unread":
				// chỉ đọc index, không phải nhảy vào từng dòng dữ liệu.
				'user_status'         => [ 'user_id', 'status' ],
				// Danh sách Inbox: WHERE user_id = ? ORDER BY pinned DESC, created_at DESC
				// → đọc thẳng theo thứ tự index, không cần filesort.
				'user_pinned_created' => [ 'user_id', 'pinned', 'created_at' ],
				'status'              => [ 'status' ],
				'priority'            => [ 'priority' ],
				'pinned'              => [ 'pinned' ],
				'created_at'          => [ 'created_at' ],
			],
			'redundant' => [
				'user_id' => [ 'user_id' ],
			],
		],
		'init_user_engine_transaction_log' => [
			'keys'      => [
				'user_type'      => [ 'user_id', 'type' ],
				'user_logged_at' => [ 'user_id', 'logged_at' ],
				'source'         => [ 'source' ],
				'logged_at'      => [ 'logged_at' ],
			],
			'redundant' => [
				'user_id' => [ 'user_id' ],
			],
		],
		'init_user_engine_exp_log'         => [
			'keys'      => [
				'user_logged_at' => [ 'user_id', 'logged_at' ],
				'source'         => [ 'source' ],
				'logged_at'      => [ 'logged_at' ],
			],
			'redundant' => [
				'user_id' => [ 'user_id' ],
			],
		],
	];
}

/**
 * Bộ index đã được nâng cấp xong cho site hiện tại hay chưa.
 *
 * @return bool
 */
function init_plugin_suite_user_engine_indexes_up_to_date() {
	return (int) get_option( 'iue_db_index_version', 0 ) >= INIT_PLUGIN_SUITE_IUE_DB_INDEX_VERSION;
}

/**
 * Database có phải SQLite (vd WordPress Playground, plugin SQLite Database Integration) hay không.
 *
 * @return bool
 */
function init_plugin_suite_user_engine_db_is_sqlite() {
	return ( defined( 'DB_ENGINE' ) && 'sqlite' === DB_ENGINE ) || defined( 'SQLITE_DB_DROPIN_VERSION' );
}

/**
 * Kiểm tra 1 bảng có tồn tại hay không.
 *
 * @param string $table Tên bảng đầy đủ (đã có prefix).
 * @return bool
 */
function init_plugin_suite_user_engine_db_table_exists( $table ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

	return $found === $table;
}

/**
 * Lấy danh sách index hiện có của 1 bảng.
 *
 * @param string $table Tên bảng đầy đủ (đã có prefix).
 * @return array Tên index => danh sách cột (chữ thường, đúng thứ tự trong index).
 */
function init_plugin_suite_user_engine_get_table_indexes( $table ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Tên bảng do plugin tự tạo từ $wpdb->prefix, không phải dữ liệu người dùng.
	$rows = $wpdb->get_results( "SHOW INDEX FROM `{$table}`", ARRAY_A );

	$indexes = [];
	foreach ( (array) $rows as $row ) {
		if ( ! isset( $row['Key_name'], $row['Seq_in_index'], $row['Column_name'] ) ) {
			continue;
		}
		$indexes[ $row['Key_name'] ][ (int) $row['Seq_in_index'] ] = strtolower( $row['Column_name'] );
	}

	foreach ( $indexes as $name => $columns ) {
		ksort( $columns );
		$indexes[ $name ] = array_values( $columns );
	}

	return $indexes;
}

/**
 * So sánh index hiện có với bộ index mong muốn → danh sách index cần thêm / cần xóa.
 *
 * @param array $existing Kết quả của init_plugin_suite_user_engine_get_table_indexes().
 * @param array $plan     1 phần tử của init_plugin_suite_user_engine_get_index_plan().
 * @return array{0: array, 1: array} [ index cần thêm (tên => cột), tên index cần xóa ].
 */
function init_plugin_suite_user_engine_diff_indexes( array $existing, array $plan ) {
	$add   = [];
	$drop  = [];
	$final = $existing;

	foreach ( $plan['keys'] as $name => $columns ) {
		if ( isset( $existing[ $name ] ) || in_array( $columns, array_values( $existing ), true ) ) {
			continue;
		}
		$add[ $name ]   = $columns;
		$final[ $name ] = $columns;
	}

	foreach ( $plan['redundant'] as $name => $columns ) {
		if ( ! isset( $existing[ $name ] ) || $existing[ $name ] !== $columns ) {
			continue;
		}

		foreach ( $final as $other_name => $other_columns ) {
			if ( $other_name === $name || 'PRIMARY' === $other_name ) {
				continue;
			}
			if ( count( $other_columns ) > count( $columns ) && array_slice( $other_columns, 0, count( $columns ) ) === $columns ) {
				$drop[] = $name;
				unset( $final[ $name ] );
				break;
			}
		}
	}

	return [ $add, $drop ];
}

/**
 * Rút gọn thông báo lỗi DB về dạng text ngắn (1 số driver, vd SQLite, trả về cả khối HTML).
 *
 * @param string $error Thông báo lỗi gốc.
 * @return string
 */
function init_plugin_suite_user_engine_short_db_error( $error ) {
	$error = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $error ) ) );

	if ( preg_match( '/Error message was:\s*(.+?)(?:\s+Backtrace:|$)/', $error, $m ) ) {
		$error = $m[1];
	}

	return function_exists( 'mb_substr' ) ? mb_substr( $error, 0, 300 ) : substr( $error, 0, 300 );
}

/**
 * Áp dụng thay đổi index cho 1 bảng.
 *
 * MySQL/MariaDB: gộp tất cả vào 1 câu ALTER online (ALGORITHM=INPLACE, LOCK=NONE) — chỉ quét
 * bảng 1 lần, ghi/đọc vẫn chạy bình thường trong lúc tạo index.
 *
 * @param string $table Tên bảng đầy đủ (đã có prefix).
 * @param array  $add   Index cần thêm (tên => cột).
 * @param array  $drop  Tên index cần xóa.
 * @return true|WP_Error
 */
function init_plugin_suite_user_engine_apply_index_changes( $table, array $add, array $drop ) {
	global $wpdb;

	if ( empty( $add ) && empty( $drop ) ) {
		return true;
	}

	// Tên index/cột lấy từ init_plugin_suite_user_engine_get_index_plan() (hằng của plugin)
	// hoặc từ SHOW INDEX, đều đã được lọc lại ở đây cho chắc chắn.
	$ident = static function ( $name ) {
		return '`' . preg_replace( '/[^A-Za-z0-9_]/', '', (string) $name ) . '`';
	};

	$parts = [];
	foreach ( $add as $name => $columns ) {
		$parts[] = 'ADD KEY ' . $ident( $name ) . ' (' . implode( ', ', array_map( $ident, $columns ) ) . ')';
	}
	foreach ( $drop as $name ) {
		$parts[] = 'DROP KEY ' . $ident( $name );
	}

	$suppress = $wpdb->suppress_errors( true );

	if ( init_plugin_suite_user_engine_db_is_sqlite() ) {
		// SQLite (site thử nghiệm/Playground, dữ liệu nhỏ): không có cú pháp online DDL, và
		// lớp giả lập MySQL không trả về đầy đủ SHOW INDEX → chỉ THÊM từng index (index đã có
		// thì bỏ qua), không bao giờ xóa index nào.
		$error = '';
		foreach ( $add as $name => $columns ) {
			$part = 'ADD KEY ' . $ident( $name ) . ' (' . implode( ', ', array_map( $ident, $columns ) ) . ')';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- DDL không hỗ trợ placeholder; tên bảng/index/cột đã được lọc ở trên.
			if ( false === $wpdb->query( "ALTER TABLE `{$table}` {$part}" ) && false === stripos( (string) $wpdb->last_error, 'already exists' ) ) {
				$error = init_plugin_suite_user_engine_short_db_error( $wpdb->last_error );
				break;
			}
		}
		$wpdb->suppress_errors( $suppress );

		return '' === $error ? true : new WP_Error( 'iue_index_alter_failed', $error );
	}

	// Không bao giờ để ALTER phải chờ metadata lock quá vài giây: trong lúc chờ, mọi truy vấn
	// khác vào bảng sẽ bị xếp hàng phía sau. Hết thời gian thì bỏ qua, thử lại sau.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$prev_lock_wait = (int) $wpdb->get_var( 'SELECT @@SESSION.lock_wait_timeout' );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( 'SET SESSION lock_wait_timeout = 3' );

	$sql = "ALTER TABLE `{$table}` " . implode( ', ', $parts ) . ', ALGORITHM=INPLACE, LOCK=NONE';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- DDL không hỗ trợ placeholder; tên bảng/index/cột đã được lọc ở trên.
	$result = $wpdb->query( $sql );
	$error  = init_plugin_suite_user_engine_short_db_error( $wpdb->last_error );

	if ( $prev_lock_wait > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'SET SESSION lock_wait_timeout = %d', $prev_lock_wait ) );
	}

	$wpdb->suppress_errors( $suppress );

	if ( false === $result ) {
		return new WP_Error( 'iue_index_alter_failed', '' !== $error ? $error : 'ALTER TABLE failed' );
	}

	return true;
}

/**
 * Giữ mutex để không bao giờ có 2 tiến trình cùng nâng cấp index.
 *
 * Hàm add_option() chỉ thành công cho đúng 1 tiến trình (option là UNIQUE). Lock quá 1 giờ được
 * coi là treo (vd PHP bị kill giữa chừng) và được giành lại.
 *
 * @return bool
 */
function init_plugin_suite_user_engine_acquire_index_lock() {
	$now = time();

	if ( add_option( 'iue_db_index_lock', $now, '', 'no' ) ) {
		return true;
	}

	$locked_at = (int) get_option( 'iue_db_index_lock', 0 );
	if ( $locked_at > 0 && ( $now - $locked_at ) < HOUR_IN_SECONDS ) {
		return false;
	}

	update_option( 'iue_db_index_lock', $now, 'no' );

	return true;
}

/**
 * Chạy nâng cấp index cho mọi bảng (callback của WP-Cron event, cũng được gọi trực tiếp
 * khi dữ liệu còn nhỏ).
 *
 * @return bool True nếu đã hoàn tất (hoặc vốn đã hoàn tất từ trước).
 */
function init_plugin_suite_user_engine_run_index_upgrade() {
	global $wpdb;

	if ( init_plugin_suite_user_engine_indexes_up_to_date() ) {
		return true;
	}

	if ( ! init_plugin_suite_user_engine_acquire_index_lock() ) {
		return false;
	}

	// Tạo index trên bảng lớn có thể mất vài chục giây: không để PHP dừng giữa chừng.
	ignore_user_abort( true );
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- ALTER TABLE trên bảng hàng triệu dòng có thể mất vài phút và không chia nhỏ được; chỉ chạy nền (WP-Cron) hoặc khi bảng còn nhỏ.
	}

	$errors = [];

	foreach ( init_plugin_suite_user_engine_get_index_plan() as $suffix => $plan ) {
		$table = $wpdb->prefix . $suffix;

		if ( ! init_plugin_suite_user_engine_db_table_exists( $table ) ) {
			// Bảng chưa được tạo (sẽ được tạo ở lần kiểm tra bảng kế tiếp) → thử lại sau.
			$errors[] = $table . ': table not found';
			continue;
		}

		list( $add, $drop ) = init_plugin_suite_user_engine_diff_indexes(
			init_plugin_suite_user_engine_get_table_indexes( $table ),
			$plan
		);

		$result = init_plugin_suite_user_engine_apply_index_changes( $table, $add, $drop );
		if ( is_wp_error( $result ) ) {
			$errors[] = $table . ': ' . $result->get_error_message();
		}
	}

	if ( empty( $errors ) ) {
		update_option( 'iue_db_index_version', INIT_PLUGIN_SUITE_IUE_DB_INDEX_VERSION );
		delete_option( 'iue_db_index_attempts' );
		delete_option( 'iue_db_index_last_error' );
	} else {
		$attempts = (int) get_option( 'iue_db_index_attempts', 0 ) + 1;
		update_option( 'iue_db_index_attempts', $attempts, 'no' );
		update_option( 'iue_db_index_last_error', implode( ' | ', $errors ), 'no' );

		if ( $attempts < INIT_PLUGIN_SUITE_IUE_DB_INDEX_MAX_ATTEMPTS && ! wp_next_scheduled( INIT_PLUGIN_SUITE_IUE_DB_INDEX_EVENT ) ) {
			wp_schedule_single_event( time() + HOUR_IN_SECONDS, INIT_PLUGIN_SUITE_IUE_DB_INDEX_EVENT );
		}
	}

	delete_option( 'iue_db_index_lock' );

	return empty( $errors );
}

/**
 * Tổng số dòng (ước lượng, rất rẻ) của các bảng cần nâng cấp index.
 *
 * @return int
 */
function init_plugin_suite_user_engine_index_tables_row_estimate() {
	global $wpdb;

	if ( init_plugin_suite_user_engine_db_is_sqlite() ) {
		return 0;
	}

	$tables = [];
	foreach ( array_keys( init_plugin_suite_user_engine_get_index_plan() ) as $suffix ) {
		$tables[] = $wpdb->prefix . $suffix;
	}

	$placeholders = implode( ', ', array_fill( 0, count( $tables ), '%s' ) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rows = $wpdb->get_var(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $placeholders chỉ gồm các %s.
			"SELECT SUM(TABLE_ROWS) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ({$placeholders})",
			$tables
		)
	);

	return (int) $rows;
}

/**
 * Lên lịch (hoặc chạy ngay nếu dữ liệu nhỏ) việc nâng cấp index.
 *
 * @param bool $allow_inline Cho phép chạy ngay trong request hiện tại khi tổng dữ liệu nhỏ
 *                           (site mới cài / site nhỏ: tạo index gần như tức thì).
 * @return void
 */
function init_plugin_suite_user_engine_maybe_schedule_index_upgrade( $allow_inline = false ) {
	if ( init_plugin_suite_user_engine_indexes_up_to_date() ) {
		return;
	}

	if ( (int) get_option( 'iue_db_index_attempts', 0 ) >= INIT_PLUGIN_SUITE_IUE_DB_INDEX_MAX_ATTEMPTS ) {
		return;
	}

	if ( $allow_inline && init_plugin_suite_user_engine_index_tables_row_estimate() < 50000 ) {
		init_plugin_suite_user_engine_run_index_upgrade();
		return;
	}

	if ( ! wp_next_scheduled( INIT_PLUGIN_SUITE_IUE_DB_INDEX_EVENT ) ) {
		wp_schedule_single_event( time() + 30, INIT_PLUGIN_SUITE_IUE_DB_INDEX_EVENT );
	}
}

/**
 * Kích hoạt nâng cấp index từ khu vực quản trị.
 *
 * - Bỏ qua AJAX/REST/Cron (vd Heartbeat) để không chạy song song với request admin thật.
 * - Site lớn: chỉ lên lịch WP-Cron. Nếu WP-Cron không chạy (event trễ quá 1 giờ) thì chạy
 *   trực tiếp ở lượt tải trang admin kế tiếp làm phương án dự phòng.
 *
 * @return void
 */
function init_plugin_suite_user_engine_maybe_upgrade_indexes_on_admin() {
	if ( init_plugin_suite_user_engine_indexes_up_to_date() ) {
		return;
	}

	if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$next = wp_next_scheduled( INIT_PLUGIN_SUITE_IUE_DB_INDEX_EVENT );
	if ( $next && $next < ( time() - HOUR_IN_SECONDS ) ) {
		// Gỡ event bị kẹt trước: nếu lần chạy này thất bại, hàm chạy sẽ tự lên lịch lại sau
		// 1 giờ, tránh việc mỗi lượt tải trang admin lại thử ALTER thêm 1 lần.
		wp_unschedule_event( $next, INIT_PLUGIN_SUITE_IUE_DB_INDEX_EVENT );
		init_plugin_suite_user_engine_run_index_upgrade();
		return;
	}

	init_plugin_suite_user_engine_maybe_schedule_index_upgrade( true );
}
