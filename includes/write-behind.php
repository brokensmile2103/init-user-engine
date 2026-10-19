<?php
/**
 * Write-behind buffer for the Transaction log and EXP log tables.
 *
 * On very busy sites every reward, purchase and check-in writes one row to
 * these tables: hundreds of thousands of single-row INSERTs (and commits) a
 * day. With "Buffer writes in object cache" on, log rows are first put in the
 * persistent object cache (Redis, Memcached…) and a WP-Cron event writes them
 * to the database once a minute, both tables in one run, with multi-row
 * INSERTs: a few thousand writes a day instead.
 *
 * Only log rows are buffered. Coin/Cash/EXP balances are never buffered: they
 * are still changed atomically in the database right away.
 *
 * How it stays correct without bloating the cache:
 *
 * - One small cache key per log row (`e:{gen}:{seq}`), never one big array that
 *   has to be read, changed and written back (which loses rows when requests
 *   run at the same time and hits the 1 MB value limit of Memcached).
 * - Sequence numbers come from wp_cache_incr(), the only atomic operation of
 *   the WordPress cache API. One queue holds the rows of both tables.
 * - The flush writes a chunk of rows and moves its cursor ("written up to
 *   seq N", stored in the options table) in the same database transaction, so
 *   a crash can never write a row twice or skip one.
 * - A generation id (`gen`) is stored next to the counter. If the cache is
 *   flushed or restarted, a new generation starts and the cursor resets, so
 *   the queue never gets stuck. Rows still waiting in a wiped cache are lost:
 *   only log history, never balances.
 * - A hole in the sequence (a request that took a number but has not stored
 *   its row yet) stops the flush until the next run; after 2 minutes it is
 *   skipped and counted.
 * - When more than `max_pending` rows (default 20,000) are waiting, new rows
 *   go straight to the database, so the cache can never grow without limit.
 * - Rows expire from the cache after one day.
 *
 * The flush only ever runs from WP-Cron: the built-in WP-Cron, or a system
 * cron job on sites that set DISABLE_WP_CRON (that constant never means the
 * site has no cron). It never runs inside a visitor's or an admin's request,
 * except when the admin clicks "Write buffered logs now" or deactivates the
 * plugin.
 *
 * @package Init_User_Engine
 * @since   1.7.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cron schedule name of the buffer flush.
 */
define( 'INIT_PLUGIN_SUITE_IUE_WB_SCHEDULE', 'init_plugin_suite_user_engine_write_behind' );

/**
 * Cron hook that writes buffered log rows to the database.
 */
define( 'INIT_PLUGIN_SUITE_IUE_WB_EVENT', 'init_plugin_suite_user_engine_write_behind_flush' );

/**
 * Object cache group of the buffer.
 */
define( 'INIT_PLUGIN_SUITE_IUE_WB_GROUP', 'iue_write_behind' );

/**
 * Option that holds the flush state (cursor, counters, last error).
 */
define( 'INIT_PLUGIN_SUITE_IUE_WB_STATE', 'iue_wb_state' );

/**
 * Autoloaded flag: 1 while the buffer is on or still has rows to write.
 */
define( 'INIT_PLUGIN_SUITE_IUE_WB_ACTIVE', 'iue_wb_active' );

add_filter( 'cron_schedules', 'init_plugin_suite_user_engine_write_behind_cron_schedules' ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected -- One-minute flush is the point of the feature; the interval is filterable.
add_action( INIT_PLUGIN_SUITE_IUE_WB_EVENT, 'init_plugin_suite_user_engine_write_behind_flush_event' );
add_action( 'init', 'init_plugin_suite_user_engine_write_behind_maybe_schedule' );
add_action( 'update_option_' . INIT_PLUGIN_SUITE_IUE_OPTION, 'init_plugin_suite_user_engine_write_behind_settings_saved', 10, 2 );
add_action( 'add_option_' . INIT_PLUGIN_SUITE_IUE_OPTION, 'init_plugin_suite_user_engine_write_behind_settings_added', 10, 2 );
add_action( 'admin_post_iue_wb_flush_now', 'init_plugin_suite_user_engine_handle_write_behind_flush_now' );

// -----------------------------------------------------------------------------
// Settings
// -----------------------------------------------------------------------------

/**
 * Whether the site has a persistent object cache (Redis, Memcached…).
 *
 * The default in-memory cache only lives for one request, so buffering in it
 * would lose every row.
 *
 * @since 1.7.2
 *
 * @return bool
 */
function init_plugin_suite_user_engine_write_behind_supported() {
	return (bool) wp_using_ext_object_cache();
}

/**
 * Whether "Buffer writes in object cache" is turned on in the settings.
 *
 * @since 1.7.2
 *
 * @return bool
 */
function init_plugin_suite_user_engine_write_behind_setting() {
	$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, array() );

	return ! empty( $options['buffer_log_writes'] );
}

/**
 * Whether new log rows are buffered right now.
 *
 * @since 1.7.2
 *
 * @return bool
 */
function init_plugin_suite_user_engine_write_behind_enabled() {
	$enabled = init_plugin_suite_user_engine_write_behind_setting() && init_plugin_suite_user_engine_write_behind_supported();

	/**
	 * Filters whether Transaction/EXP log rows are buffered in the object cache.
	 *
	 * Returning true has no effect without a persistent object cache.
	 *
	 * @since 1.7.2
	 *
	 * @param bool $enabled Setting "Buffer writes in object cache" (and a persistent object cache is present).
	 */
	return (bool) apply_filters( 'init_plugin_suite_user_engine_write_behind_enabled', $enabled ) && init_plugin_suite_user_engine_write_behind_supported();
}

/**
 * Seconds between two flushes.
 *
 * @since 1.7.2
 *
 * @return int
 */
function init_plugin_suite_user_engine_flush_interval() {
	/**
	 * Filters how often (seconds) buffered log rows are written to the database.
	 *
	 * @since 1.7.2
	 *
	 * @param int $seconds Default 60.
	 */
	return max( 30, (int) apply_filters( 'init_plugin_suite_user_engine_flush_interval', 60 ) );
}

/**
 * Most rows that may wait in the buffer. Above it, new rows go straight to the database.
 *
 * @since 1.7.2
 *
 * @return int
 */
function init_plugin_suite_user_engine_write_behind_max_pending() {
	/**
	 * Filters how many log rows may wait in the object cache before new rows are written directly.
	 *
	 * About 200 bytes per row: 20,000 rows is roughly 4–5 MB of cache.
	 *
	 * @since 1.7.2
	 *
	 * @param int $max Default 20000.
	 */
	return max( 100, (int) apply_filters( 'init_plugin_suite_user_engine_write_behind_max_pending', 20000 ) );
}

/**
 * Turn buffering off for the current request (or back on).
 *
 * Used by flows that must write the log in their own database transaction,
 * such as the background Top-up job: a buffered row would survive a rollback.
 *
 * @since 1.7.2
 *
 * @param bool|null $bypass Optional. True to write directly, false to restore. Null to read.
 * @return bool Whether buffering is bypassed.
 */
function init_plugin_suite_user_engine_write_behind_bypass( $bypass = null ) {
	static $state = false;

	if ( null !== $bypass ) {
		$state = (bool) $bypass;
	}

	return $state;
}

// -----------------------------------------------------------------------------
// Cron schedule
// -----------------------------------------------------------------------------

/**
 * Đăng ký chu kỳ cron riêng cho lượt ghi buffer.
 *
 * @since 1.7.2
 *
 * @param array $schedules Các chu kỳ hiện có.
 * @return array
 */
function init_plugin_suite_user_engine_write_behind_cron_schedules( $schedules ) {
	$schedules[ INIT_PLUGIN_SUITE_IUE_WB_SCHEDULE ] = array(
		'interval' => init_plugin_suite_user_engine_flush_interval(),
		'display'  => __( 'Init User Engine: write buffered logs', 'init-user-engine' ),
	);

	return $schedules;
}

/**
 * Keep the flush event scheduled while the buffer is on or still has rows.
 *
 * Costs nothing when the feature is off: the flag is autoloaded and the cron
 * array is already in memory.
 *
 * @since 1.7.2
 *
 * @return void
 */
function init_plugin_suite_user_engine_write_behind_maybe_schedule() {
	if ( ! get_option( INIT_PLUGIN_SUITE_IUE_WB_ACTIVE ) ) {
		return;
	}

	if ( ! wp_next_scheduled( INIT_PLUGIN_SUITE_IUE_WB_EVENT ) ) {
		wp_schedule_event( time() + init_plugin_suite_user_engine_flush_interval(), INIT_PLUGIN_SUITE_IUE_WB_SCHEDULE, INIT_PLUGIN_SUITE_IUE_WB_EVENT );
	}
}

/**
 * Settings saved: start the flush event when the buffer was turned on.
 *
 * When it is turned off, rows already buffered are still written by the
 * event, which then removes itself.
 *
 * @since 1.7.2
 *
 * @param mixed $old_value Previous settings.
 * @param mixed $value     New settings.
 * @return void
 */
function init_plugin_suite_user_engine_write_behind_settings_saved( $old_value, $value ) {
	$was = is_array( $old_value ) && ! empty( $old_value['buffer_log_writes'] );
	$now = is_array( $value ) && ! empty( $value['buffer_log_writes'] );

	if ( ! $now ) {
		return;
	}

	if ( ! $was ) {
		update_option( 'iue_wb_enabled_at', time(), false );
	}

	update_option( INIT_PLUGIN_SUITE_IUE_WB_ACTIVE, 1, true );
	init_plugin_suite_user_engine_write_behind_maybe_schedule();
}

/**
 * Settings saved for the first time (the option did not exist yet).
 *
 * @since 1.7.2
 *
 * @param string $option Option name.
 * @param mixed  $value  Settings.
 * @return void
 */
function init_plugin_suite_user_engine_write_behind_settings_added( $option, $value ) {
	init_plugin_suite_user_engine_write_behind_settings_saved( array(), $value );
}

// -----------------------------------------------------------------------------
// Buffer keys
// -----------------------------------------------------------------------------

/**
 * Current buffer generation. A new one starts when the cache was wiped.
 *
 * @since 1.7.2
 * @access private
 *
 * @param bool $create Optional. Create a generation when there is none. Default true.
 * @return string Empty string when there is none and $create is false.
 */
function init_plugin_suite_user_engine_write_behind_generation( $create = true ) {
	$gen = wp_cache_get( 'gen', INIT_PLUGIN_SUITE_IUE_WB_GROUP );

	if ( is_string( $gen ) && '' !== $gen ) {
		return $gen;
	}

	if ( ! $create ) {
		return '';
	}

	// wp_cache_add() only succeeds for one process; everybody then reads the winner's value.
	wp_cache_add( 'gen', strtolower( wp_generate_password( 8, false, false ) ), INIT_PLUGIN_SUITE_IUE_WB_GROUP );
	$gen = wp_cache_get( 'gen', INIT_PLUGIN_SUITE_IUE_WB_GROUP, true );

	return is_string( $gen ) ? $gen : '';
}

/**
 * Highest sequence number handed out in a generation.
 *
 * @since 1.7.2
 * @access private
 *
 * @param string $gen Generation.
 * @return int
 */
function init_plugin_suite_user_engine_write_behind_head( $gen ) {
	return (int) wp_cache_get( 'seq:' . $gen, INIT_PLUGIN_SUITE_IUE_WB_GROUP, true );
}

// -----------------------------------------------------------------------------
// Writing to the buffer
// -----------------------------------------------------------------------------

/**
 * Put one log row in the buffer.
 *
 * @since 1.7.2
 *
 * @param string $table 'txn' (Transaction log) or 'exp' (EXP log).
 * @param array  $row   Column => value, exactly as it will be inserted.
 * @return bool True when buffered; false means the caller must write it directly.
 */
function init_plugin_suite_user_engine_write_behind_push( $table, array $row ) {
	if ( ! in_array( $table, array( 'txn', 'exp' ), true ) || init_plugin_suite_user_engine_write_behind_bypass() || ! init_plugin_suite_user_engine_write_behind_enabled() ) {
		return false;
	}

	$gen = init_plugin_suite_user_engine_write_behind_generation();

	if ( '' === $gen ) {
		return false;
	}

	$counter = 'seq:' . $gen;
	$seq     = wp_cache_incr( $counter, 1, INIT_PLUGIN_SUITE_IUE_WB_GROUP );

	if ( false === $seq ) {
		wp_cache_add( $counter, 0, INIT_PLUGIN_SUITE_IUE_WB_GROUP );
		$seq = wp_cache_incr( $counter, 1, INIT_PLUGIN_SUITE_IUE_WB_GROUP );
	}

	if ( false === $seq || (int) $seq < 1 ) {
		return false;
	}

	$seq  = (int) $seq;
	$key  = 'e:' . $gen . ':' . $seq;
	$done = wp_cache_get( 'done:' . $gen, INIT_PLUGIN_SUITE_IUE_WB_GROUP );

	// Too much waiting (cron stopped?): write directly, and leave a marker so the flush does not wait for this number.
	if ( false !== $done && ( $seq - (int) $done ) > init_plugin_suite_user_engine_write_behind_max_pending() ) {
		wp_cache_set( $key, 'skip', INIT_PLUGIN_SUITE_IUE_WB_GROUP, DAY_IN_SECONDS );
		return false;
	}

	$stored = wp_cache_set(
		$key,
		array(
			't' => $table,
			'r' => $row,
		),
		INIT_PLUGIN_SUITE_IUE_WB_GROUP,
		DAY_IN_SECONDS
	);

	return (bool) $stored;
}

// -----------------------------------------------------------------------------
// Flushing the buffer to the database
// -----------------------------------------------------------------------------

/**
 * Flush state, read straight from the database.
 *
 * @since 1.7.2
 *
 * @return array
 */
function init_plugin_suite_user_engine_write_behind_state() {
	$state = init_plugin_suite_user_engine_bulk_job_db_get( INIT_PLUGIN_SUITE_IUE_WB_STATE );

	return wp_parse_args(
		is_array( $state ) ? $state : array(),
		array(
			'gen'        => '',
			'cursor'     => 0,
			'last_flush' => 0,
			'last_rows'  => 0,
			'rows_total' => 0,
			'skipped'    => 0,
			'lost_gens'  => 0,
			'gap_seq'    => 0,
			'gap_since'  => 0,
			'last_error' => '',
		)
	);
}

/**
 * WP-Cron handler.
 *
 * @since 1.7.2
 *
 * @return void
 */
function init_plugin_suite_user_engine_write_behind_flush_event() {
	init_plugin_suite_user_engine_write_behind_flush();

	// Turned off and nothing left to write: stop the event.
	if ( ! init_plugin_suite_user_engine_write_behind_setting() && 0 === init_plugin_suite_user_engine_write_behind_pending() ) {
		wp_clear_scheduled_hook( INIT_PLUGIN_SUITE_IUE_WB_EVENT );
		update_option( INIT_PLUGIN_SUITE_IUE_WB_ACTIVE, 0, true );
	}
}

/**
 * Rows waiting in the buffer.
 *
 * @since 1.7.2
 *
 * @return int
 */
function init_plugin_suite_user_engine_write_behind_pending() {
	$gen = init_plugin_suite_user_engine_write_behind_generation( false );

	if ( '' === $gen ) {
		return 0;
	}

	$state  = init_plugin_suite_user_engine_write_behind_state();
	$cursor = $state['gen'] === $gen ? (int) $state['cursor'] : 0;

	return max( 0, init_plugin_suite_user_engine_write_behind_head( $gen ) - $cursor );
}

/**
 * Write buffered rows to the database, oldest first, until the buffer is empty
 * or the time budget is used.
 *
 * @since 1.7.2
 *
 * @param float|null $budget Optional. Seconds. Default 20 (half of max_execution_time when lower, 60 without a time limit).
 * @return int|false Rows written, or false when another process is flushing.
 */
function init_plugin_suite_user_engine_write_behind_flush( $budget = null ) {
	if ( null === $budget ) {
		$limit  = (int) ini_get( 'max_execution_time' );
		$budget = $limit > 0 ? min( 20, max( 3, (int) floor( $limit / 2 ) ) ) : 60;
	}

	if ( ! init_plugin_suite_user_engine_acquire_lock( 'write_behind_flush', 0, (int) ceil( $budget ) + 300 ) ) {
		return false;
	}

	$written = 0;

	try {
		$written = init_plugin_suite_user_engine_write_behind_flush_locked( microtime( true ) + (float) $budget );
	} finally {
		init_plugin_suite_user_engine_release_lock( 'write_behind_flush' );
	}

	return $written;
}

/**
 * The flush itself (caller holds the lock).
 *
 * @since 1.7.2
 * @access private
 *
 * @param float $deadline microtime( true ) at which to stop.
 * @return int Rows written.
 */
function init_plugin_suite_user_engine_write_behind_flush_locked( $deadline ) {
	$state = init_plugin_suite_user_engine_write_behind_state();
	$gen   = init_plugin_suite_user_engine_write_behind_generation( false );

	if ( '' === $gen ) {
		// Cache empty (wiped, or nothing ever buffered).
		if ( '' !== $state['gen'] ) {
			$state['gen']    = '';
			$state['cursor'] = 0;
			++$state['lost_gens'];
		}

		$state['last_flush'] = time();
		$state['last_rows']  = 0;
		init_plugin_suite_user_engine_bulk_job_db_set( INIT_PLUGIN_SUITE_IUE_WB_STATE, $state );

		return 0;
	}

	if ( $state['gen'] !== $gen ) {
		// New generation: the previous one was wiped from the cache (or this is the first flush).
		if ( '' !== $state['gen'] ) {
			++$state['lost_gens'];
		}

		$state['gen']       = $gen;
		$state['cursor']    = 0;
		$state['gap_seq']   = 0;
		$state['gap_since'] = 0;
	}

	$written = 0;
	$chunk   = 500;

	/**
	 * Filters how many buffered rows are written per database transaction.
	 *
	 * @since 1.7.2
	 *
	 * @param int $chunk Default 500.
	 */
	$chunk = max( 1, (int) apply_filters( 'init_plugin_suite_user_engine_write_behind_chunk', $chunk ) );

	while ( microtime( true ) < $deadline ) {
		$head = init_plugin_suite_user_engine_write_behind_head( $gen );

		if ( $head <= (int) $state['cursor'] ) {
			break;
		}

		$from = (int) $state['cursor'] + 1;
		$to   = min( $head, (int) $state['cursor'] + $chunk );
		$keys = array();

		for ( $seq = $from; $seq <= $to; $seq++ ) {
			$keys[ $seq ] = 'e:' . $gen . ':' . $seq;
		}

		$values  = wp_cache_get_multiple( array_values( $keys ), INIT_PLUGIN_SUITE_IUE_WB_GROUP, true );
		$rows    = array(
			'txn' => array(),
			'exp' => array(),
		);
		$reached = (int) $state['cursor'];
		$stopped = false;

		foreach ( $keys as $seq => $key ) {
			$value = isset( $values[ $key ] ) ? $values[ $key ] : false;

			if ( false === $value ) {
				// Number taken, row not stored (yet). Wait for it, then give up after 2 minutes.
				if ( (int) $state['gap_seq'] !== $seq ) {
					$state['gap_seq']   = $seq;
					$state['gap_since'] = time();
					$stopped            = true;
					break;
				}

				if ( time() - (int) $state['gap_since'] < 2 * MINUTE_IN_SECONDS ) {
					$stopped = true;
					break;
				}

				++$state['skipped'];
			} elseif ( is_array( $value ) && isset( $value['t'], $value['r'] ) && isset( $rows[ $value['t'] ] ) ) {
				$rows[ $value['t'] ][] = $value['r'];
			}

			// 'skip' markers (row written directly because the buffer was full) are just passed.
			$reached = $seq;
		}

		if ( $reached > (int) $state['cursor'] ) {
			$count = init_plugin_suite_user_engine_write_behind_commit( $state, $rows, $reached );

			if ( false === $count ) {
				return $written;
			}

			$written += $count;

			init_plugin_suite_user_engine_write_behind_forget( array_slice( $keys, 0, $reached - $from + 1 ), $rows );
		}

		if ( $stopped ) {
			break;
		}
	}

	wp_cache_set( 'done:' . $gen, (int) $state['cursor'], INIT_PLUGIN_SUITE_IUE_WB_GROUP );

	$state['last_flush'] = time();
	$state['last_rows']  = $written;
	init_plugin_suite_user_engine_bulk_job_db_set( INIT_PLUGIN_SUITE_IUE_WB_STATE, $state );

	if ( $written > 0 ) {
		/**
		 * Fires after buffered log rows were written to the database.
		 *
		 * @since 1.7.2
		 *
		 * @param int $written Rows written in this run.
		 */
		do_action( 'init_plugin_suite_user_engine_write_behind_flushed', $written );
	}

	return $written;
}

/**
 * Insert one chunk of rows and move the cursor, in one database transaction.
 *
 * @since 1.7.2
 * @access private
 *
 * @param array $state   Flush state, by reference. Updated only when the commit succeeds.
 * @param array $rows    'txn' => rows, 'exp' => rows.
 * @param int   $reached New cursor.
 * @return int|false Rows inserted, false on a database error (nothing is kept).
 */
function init_plugin_suite_user_engine_write_behind_commit( array &$state, array $rows, $reached ) {
	global $wpdb;

	// EXP history of a member deleted meanwhile is not kept (same rule as when the member is deleted).
	$rows['exp'] = init_plugin_suite_user_engine_write_behind_drop_deleted_users( $rows['exp'] );

	$new               = $state;
	$new['cursor']     = (int) $reached;
	$new['gap_seq']    = 0;
	$new['gap_since']  = 0;
	$new['last_error'] = '';
	$new['rows_total'] = (int) $state['rows_total'] + count( $rows['txn'] ) + count( $rows['exp'] );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Rows and cursor must commit together.
	$wpdb->query( 'START TRANSACTION' );

	$ok = init_plugin_suite_user_engine_write_behind_insert(
		init_plugin_suite_user_engine_txn_table(),
		array( 'user_id', 'type', 'amount', 'original_amount', 'change_type', 'source', 'vip_bonus', 'bonus_percent', 'logged_at' ),
		array( '%d', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%s' ),
		$rows['txn']
	) && init_plugin_suite_user_engine_write_behind_insert(
		init_plugin_suite_user_engine_exp_table(),
		array( 'user_id', 'amount', 'change_type', 'source', 'vip_bonus', 'logged_at' ),
		array( '%d', '%d', '%s', '%s', '%d', '%s' ),
		$rows['exp']
	) && init_plugin_suite_user_engine_bulk_job_db_set( INIT_PLUGIN_SUITE_IUE_WB_STATE, $new );

	if ( ! $ok ) {
		$error = $wpdb->last_error;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'ROLLBACK' );

		// Keep the cursor where it was: the same rows are tried again on the next run.
		$state['last_error'] = '' !== $error ? $error : __( 'Could not write buffered logs to the database.', 'init-user-engine' );
		$state['last_flush'] = time();
		init_plugin_suite_user_engine_bulk_job_db_set( INIT_PLUGIN_SUITE_IUE_WB_STATE, $state );

		return false;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( 'COMMIT' );

	$state = $new;

	return count( $rows['txn'] ) + count( $rows['exp'] );
}

/**
 * Multi-row INSERT of buffered rows (no-op for an empty list).
 *
 * @since 1.7.2
 * @access private
 *
 * @param string   $table   Table name.
 * @param string[] $columns Columns, in order.
 * @param string[] $formats Placeholder per column.
 * @param array[]  $rows    Rows (column => value).
 * @return bool
 */
function init_plugin_suite_user_engine_write_behind_insert( $table, array $columns, array $formats, array $rows ) {
	global $wpdb;

	if ( empty( $rows ) ) {
		return true;
	}

	$tuple  = '(' . implode( ',', $formats ) . ')';
	$values = array();
	$params = array();

	foreach ( $rows as $row ) {
		$values[] = $tuple;

		foreach ( $columns as $i => $column ) {
			$value    = isset( $row[ $column ] ) ? $row[ $column ] : '';
			$params[] = '%d' === $formats[ $i ] ? (int) $value : (string) $value;
		}
	}

	$sql = "INSERT INTO {$table} (" . implode( ',', $columns ) . ') VALUES ' . implode( ',', $values );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table and column names are fixed; every value goes through prepare().
	$result = $wpdb->query( $wpdb->prepare( $sql, $params ) );

	return false !== $result;
}

/**
 * Drop EXP rows of members who no longer exist.
 *
 * @since 1.7.2
 * @access private
 *
 * @param array[] $rows EXP rows.
 * @return array[]
 */
function init_plugin_suite_user_engine_write_behind_drop_deleted_users( array $rows ) {
	global $wpdb;

	if ( empty( $rows ) ) {
		return $rows;
	}

	$ids          = array_values( array_unique( array_map( 'absint', wp_list_pluck( $rows, 'user_id' ) ) ) );
	$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One query per chunk.
	$existing = $wpdb->get_col(
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- All IDs are passed as one array.
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $placeholders is a list of %d.
			"SELECT ID FROM {$wpdb->users} WHERE ID IN ({$placeholders})",
			$ids
		)
	);

	$existing = array_flip( array_map( 'absint', (array) $existing ) );

	return array_values(
		array_filter(
			$rows,
			static function ( $row ) use ( $existing ) {
				return isset( $existing[ absint( $row['user_id'] ) ] );
			}
		)
	);
}

/**
 * After a commit: remove the written keys and refresh the history caches of the members concerned.
 *
 * @since 1.7.2
 * @access private
 *
 * @param string[] $keys Cache keys written.
 * @param array    $rows 'txn' => rows, 'exp' => rows.
 * @return void
 */
function init_plugin_suite_user_engine_write_behind_forget( array $keys, array $rows ) {
	if ( function_exists( 'wp_cache_delete_multiple' ) ) {
		wp_cache_delete_multiple( array_values( $keys ), INIT_PLUGIN_SUITE_IUE_WB_GROUP );
	} else {
		foreach ( $keys as $key ) {
			wp_cache_delete( $key, INIT_PLUGIN_SUITE_IUE_WB_GROUP );
		}
	}

	foreach ( array_unique( array_map( 'absint', wp_list_pluck( $rows['txn'], 'user_id' ) ) ) as $user_id ) {
		init_plugin_suite_user_engine_invalidate_txn_cache( $user_id );
	}

	foreach ( array_unique( array_map( 'absint', wp_list_pluck( $rows['exp'], 'user_id' ) ) ) as $user_id ) {
		init_plugin_suite_user_engine_invalidate_exp_cache( $user_id );
	}
}

// -----------------------------------------------------------------------------
// Admin
// -----------------------------------------------------------------------------

/**
 * Minutes without a flush after which the settings page shows a warning.
 *
 * @since 1.7.2
 *
 * @return int
 */
function init_plugin_suite_user_engine_write_behind_stall_minutes() {
	/**
	 * Filters after how many minutes without a flush the settings page warns that buffered logs are not being written.
	 *
	 * Keep it longer than the interval of the system cron job on sites that set DISABLE_WP_CRON.
	 *
	 * @since 1.7.2
	 *
	 * @param int $minutes Default 15.
	 */
	return max( 1, (int) apply_filters( 'init_plugin_suite_user_engine_write_behind_stall_minutes', 15 ) );
}

/**
 * Admin-post: write buffered rows now (explicit admin action).
 *
 * @since 1.7.2
 *
 * @return void
 */
function init_plugin_suite_user_engine_handle_write_behind_flush_now() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'init-user-engine' ) );
	}

	check_admin_referer( 'iue_wb_flush_now' );

	$written = init_plugin_suite_user_engine_write_behind_flush( 10 );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'        => INIT_PLUGIN_SUITE_IUE_SLUG,
				'iue_wb_done' => false === $written ? 'busy' : (int) $written,
			),
			admin_url( 'admin.php' )
		) . '#iue-write-behind'
	);
	exit;
}

/**
 * Deactivation: write what is still buffered (best effort, a few seconds), then stop the event.
 *
 * @since 1.7.2
 *
 * @return void
 */
function init_plugin_suite_user_engine_write_behind_on_deactivation() {
	if ( init_plugin_suite_user_engine_write_behind_supported() && get_option( INIT_PLUGIN_SUITE_IUE_WB_ACTIVE ) ) {
		init_plugin_suite_user_engine_write_behind_flush( 10 );
	}

	wp_clear_scheduled_hook( INIT_PLUGIN_SUITE_IUE_WB_EVENT );
}

/**
 * Settings row: the option, the buffer status and the "write now" button.
 *
 * @since 1.7.2
 *
 * @param array $options Plugin settings.
 * @return void
 */
function init_plugin_suite_user_engine_render_write_behind_settings( $options ) {
	$supported = init_plugin_suite_user_engine_write_behind_supported();
	$checked   = ! empty( $options['buffer_log_writes'] );
	$name      = INIT_PLUGIN_SUITE_IUE_OPTION . '[buffer_log_writes]';
	?>
	<tr id="iue-write-behind">
		<th colspan="2"><h2><?php esc_html_e( 'Performance', 'init-user-engine' ); ?></h2></th>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Buffer writes in object cache', 'init-user-engine' ); ?></th>
		<td>
			<?php if ( $supported ) : ?>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?> />
					<?php esc_html_e( 'Write Transaction and EXP history to the object cache first, then to the database once a minute', 'init-user-engine' ); ?>
				</label>
			<?php else : ?>
				<?php if ( $checked ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="1" />
				<?php endif; ?>
				<label>
					<input type="checkbox" disabled="disabled" <?php checked( $checked ); ?> />
					<?php esc_html_e( 'Write Transaction and EXP history to the object cache first, then to the database once a minute', 'init-user-engine' ); ?>
				</label>
				<p class="description" style="color:#b32d2e;"><?php esc_html_e( 'Needs a persistent object cache (Redis, Memcached…). None was found, so history rows are written to the database right away.', 'init-user-engine' ); ?></p>
			<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'For very busy sites: one multi-row write per minute instead of one write per reward, purchase or check-in. Only history rows are buffered; Coin, Cash and EXP balances are always saved right away. New rows can take up to a minute to appear in the history. If the object cache is flushed or restarted, rows not yet written are lost (history only).', 'init-user-engine' ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Rows are written by WP-Cron. If your site runs WP-Cron from a system cron job, they are written at each run of that job.', 'init-user-engine' ); ?>
			</p>
			<?php
			if ( $supported && ( $checked || get_option( INIT_PLUGIN_SUITE_IUE_WB_ACTIVE ) ) ) {
				init_plugin_suite_user_engine_render_write_behind_status();
			}
			?>
		</td>
	</tr>
	<?php
}

/**
 * Buffer status box (inside the settings row).
 *
 * @since 1.7.2
 * @access private
 *
 * @return void
 */
function init_plugin_suite_user_engine_render_write_behind_status() {
	$state   = init_plugin_suite_user_engine_write_behind_state();
	$pending = init_plugin_suite_user_engine_write_behind_pending();
	$stall   = init_plugin_suite_user_engine_write_behind_stall_minutes();
	$last    = (int) $state['last_flush'];
	$stalled = $pending > 0 && ( time() - max( $last, (int) get_option( 'iue_wb_enabled_at', 0 ) ) ) >= $stall * MINUTE_IN_SECONDS;
	$next    = wp_next_scheduled( INIT_PLUGIN_SUITE_IUE_WB_EVENT );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
	$done = isset( $_GET['iue_wb_done'] ) ? sanitize_key( wp_unslash( $_GET['iue_wb_done'] ) ) : '';
	?>
	<div style="margin-top:10px;padding:10px 12px;background:#f6f7f7;border-left:4px solid <?php echo $stalled ? '#dba617' : '#2271b1'; ?>;">
		<?php if ( '' !== $done ) : ?>
			<p style="margin-top:0;">
				<strong>
					<?php
					if ( 'busy' === $done ) {
						esc_html_e( 'Another process is writing the buffer right now. Try again in a moment.', 'init-user-engine' );
					} else {
						/* translators: %s: number of rows. */
						echo esc_html( sprintf( __( '%s buffered rows written to the database.', 'init-user-engine' ), number_format_i18n( (int) $done ) ) );
					}
					?>
				</strong>
			</p>
		<?php endif; ?>
		<p style="margin:0 0 4px;">
			<?php
			/* translators: %s: number of rows. */
			echo esc_html( sprintf( __( 'Waiting in the buffer: %s rows', 'init-user-engine' ), number_format_i18n( $pending ) ) );
			?>
		</p>
		<p style="margin:0 0 4px;">
			<?php
			if ( $last > 0 ) {
				echo esc_html(
					sprintf(
						/* translators: 1: date and time, 2: number of rows. */
						__( 'Last write: %1$s (%2$s rows)', 'init-user-engine' ),
						wp_date( 'Y-m-d H:i:s', $last ),
						number_format_i18n( (int) $state['last_rows'] )
					)
				);
			} else {
				esc_html_e( 'Last write: not yet', 'init-user-engine' );
			}
			?>
			<?php if ( $next ) : ?>
				<?php
				/* translators: %s: date and time. */
				echo ' · ' . esc_html( sprintf( __( 'next scheduled: %s', 'init-user-engine' ), wp_date( 'Y-m-d H:i:s', $next ) ) );
				?>
			<?php endif; ?>
		</p>
		<p style="margin:0 0 4px;">
			<?php
			/* translators: %s: number of rows. */
			echo esc_html( sprintf( __( 'Written since enabled: %s rows', 'init-user-engine' ), number_format_i18n( (int) $state['rows_total'] ) ) );
			?>
		</p>
		<?php if ( (int) $state['skipped'] > 0 || (int) $state['lost_gens'] > 0 ) : ?>
			<p class="description" style="margin:0 0 4px;">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: number of rows skipped, 2: number of cache resets. */
						__( 'Rows skipped (never stored in the cache): %1$s · Cache resets seen: %2$s', 'init-user-engine' ),
						number_format_i18n( (int) $state['skipped'] ),
						number_format_i18n( (int) $state['lost_gens'] )
					)
				);
				?>
			</p>
		<?php endif; ?>
		<?php if ( '' !== $state['last_error'] ) : ?>
			<p style="margin:0 0 4px;color:#b32d2e;">
				<?php
				/* translators: %s: database error. */
				echo esc_html( sprintf( __( 'Last error: %s', 'init-user-engine' ), $state['last_error'] ) );
				?>
			</p>
		<?php endif; ?>
		<?php if ( $stalled ) : ?>
			<p style="margin:6px 0 4px;color:#996800;">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: minutes. */
						_n( 'No buffered rows written for %d minute.', 'No buffered rows written for %d minutes.', $stall, 'init-user-engine' ),
						$stall
					)
				);
				?>
			</p>
			<p class="description" style="margin:0 0 6px;"><?php esc_html_e( 'Rows are written by WP-Cron. If your site runs WP-Cron from a system cron job, they are written at its next run. When the buffer is full, new rows go straight to the database, so nothing is lost while cron is not running.', 'init-user-engine' ); ?></p>
		<?php endif; ?>
		<?php if ( $pending > 0 ) : ?>
			<p style="margin:8px 0 0;">
				<button type="submit" form="iue-wb-flush-now" class="button button-small"><?php esc_html_e( 'Write buffered logs now', 'init-user-engine' ); ?></button>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Hidden form used by the "Write buffered logs now" button (outside the settings form).
 *
 * @since 1.7.2
 *
 * @return void
 */
function init_plugin_suite_user_engine_render_write_behind_flush_form() {
	?>
	<form id="iue-wb-flush-now" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none;">
		<?php wp_nonce_field( 'iue_wb_flush_now' ); ?>
		<input type="hidden" name="action" value="iue_wb_flush_now" />
	</form>
	<?php
}
