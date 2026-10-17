<?php
/**
 * Background bulk jobs for the admin tools.
 *
 * "Top-up Coin/Cash" and "Send Notification" sent to *All members* or *Active
 * VIPs* used to run inside the admin request: get_users() loaded every user ID,
 * then one wallet change + one log row per member. On sites with tens of
 * thousands of members the request could hit the PHP time limit halfway, and
 * nobody could tell which members had been paid.
 *
 * Those two targets now create a job that runs in the background through
 * WP-Cron, in short time slices:
 *
 * - Members are walked by user ID (`ID > cursor ORDER BY ID LIMIT n`), never
 *   loaded all at once and never paged with OFFSET.
 * - Top-up: each member is handled in its own database transaction that holds
 *   the wallet change, the transaction log row, the Inbox message and the new
 *   job cursor. If PHP dies at any point, the transaction is rolled back and
 *   the member is handled again on the next run: nobody is paid twice and
 *   nobody is skipped.
 * - Notification: messages are inserted in bulk, 500 members per batch. If PHP
 *   dies during a batch, the next run skips members who already got the message.
 * - One runner at a time (site-wide lock), jobs run oldest first, and every job
 *   can be cancelled from its admin page.
 * - Jobs only ever run from WP-Cron: the built-in WP-Cron, or a system cron job
 *   on sites that set DISABLE_WP_CRON. Admin pages never run them on their own,
 *   so a site that moved heavy work to a system cron keeps it there. A job that
 *   makes no progress for a long time (default 15 minutes) shows a warning, and
 *   only then can the admin choose to process it from the open page.
 *
 * Job rows are read and written straight from the options table, never through
 * the options cache: a persistent object cache must never hand a runner an old
 * cursor, or a member could be paid twice.
 *
 * "Selected users" still runs right away, as before.
 *
 * @package Init_User_Engine
 * @since   1.7.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * WP-Cron hook that runs the bulk job queue.
 */
define( 'INIT_PLUGIN_SUITE_IUE_BULK_JOB_EVENT', 'init_plugin_suite_user_engine_run_bulk_jobs' );

/**
 * Option that lists the job IDs of the site, oldest first.
 */
define( 'INIT_PLUGIN_SUITE_IUE_BULK_JOB_INDEX', 'iue_bulk_jobs' );

/**
 * Autoloaded flag: 1 while at least one job is waiting or running.
 */
define( 'INIT_PLUGIN_SUITE_IUE_BULK_JOB_ACTIVE', 'iue_bulk_jobs_active' );

/**
 * How many finished jobs are kept for the history table.
 */
define( 'INIT_PLUGIN_SUITE_IUE_BULK_JOB_KEEP', 20 );

add_action( INIT_PLUGIN_SUITE_IUE_BULK_JOB_EVENT, 'init_plugin_suite_user_engine_run_bulk_jobs_event' );
add_action( 'pre_user_query', 'init_plugin_suite_user_engine_bulk_job_user_query_cursor' );
add_action( 'admin_init', 'init_plugin_suite_user_engine_bulk_jobs_watchdog' );
add_action( 'wp_ajax_iue_bulk_jobs_status', 'init_plugin_suite_user_engine_ajax_bulk_jobs_status' );
add_action( 'wp_ajax_iue_bulk_jobs_run', 'init_plugin_suite_user_engine_ajax_bulk_jobs_run' );
add_action( 'admin_post_iue_cancel_bulk_job', 'init_plugin_suite_user_engine_handle_cancel_bulk_job' );
add_action( 'admin_enqueue_scripts', 'init_plugin_suite_user_engine_bulk_jobs_assets' );

// -----------------------------------------------------------------------------
// Storage (straight from the options table, no cache)
// -----------------------------------------------------------------------------

/**
 * Read an option row straight from the database.
 *
 * @since 1.7.1
 * @access private
 *
 * @param string $option Option name.
 * @return mixed|null Unserialized value, or null when the row does not exist.
 */
function init_plugin_suite_user_engine_bulk_job_db_get( $option ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Job state must be read live; a cached copy could replay a payment.
	$value = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
			$option
		)
	);

	return null === $value ? null : maybe_unserialize( $value );
}

/**
 * Write an option row straight to the database (insert or update).
 *
 * @since 1.7.1
 * @access private
 *
 * @param string $option Option name.
 * @param mixed  $value  Value.
 * @return bool
 */
function init_plugin_suite_user_engine_bulk_job_db_set( $option, $value ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Written inside the per-member transaction, so it must not go through the options cache.
	$result = $wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no') ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
			$option,
			maybe_serialize( $value )
		)
	);

	wp_cache_delete( $option, 'options' );

	return false !== $result;
}

/**
 * Delete an option row straight from the database.
 *
 * @since 1.7.1
 * @access private
 *
 * @param string $option Option name.
 * @return void
 */
function init_plugin_suite_user_engine_bulk_job_db_delete( $option ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Job rows never go through the options API.
	$wpdb->delete( $wpdb->options, array( 'option_name' => $option ), array( '%s' ) );

	wp_cache_delete( $option, 'options' );
}

/**
 * Option name of a job.
 *
 * @since 1.7.1
 *
 * @param string $job_id Job ID.
 * @return string
 */
function init_plugin_suite_user_engine_bulk_job_option( $job_id ) {
	return 'iue_bulk_job_' . preg_replace( '/[^A-Za-z0-9]/', '', (string) $job_id );
}

/**
 * Option name of a job's cancel request.
 *
 * Kept apart from the job row: only the runner writes the job row, so a cancel
 * click can never be overwritten by the runner saving its progress.
 *
 * @since 1.7.1
 *
 * @param string $job_id Job ID.
 * @return string
 */
function init_plugin_suite_user_engine_bulk_job_cancel_option( $job_id ) {
	return 'iue_bulk_job_cancel_' . preg_replace( '/[^A-Za-z0-9]/', '', (string) $job_id );
}

/**
 * Get a job.
 *
 * @since 1.7.1
 *
 * @param string $job_id Job ID.
 * @return array|null
 */
function init_plugin_suite_user_engine_get_bulk_job( $job_id ) {
	$job = init_plugin_suite_user_engine_bulk_job_db_get( init_plugin_suite_user_engine_bulk_job_option( $job_id ) );

	return is_array( $job ) ? $job : null;
}

/**
 * Save a job.
 *
 * @since 1.7.1
 *
 * @param array $job Job.
 * @return bool
 */
function init_plugin_suite_user_engine_save_bulk_job( array $job ) {
	$job['updated_at'] = time();

	return init_plugin_suite_user_engine_bulk_job_db_set( init_plugin_suite_user_engine_bulk_job_option( $job['id'] ), $job );
}

/**
 * IDs of the site's jobs, oldest first.
 *
 * @since 1.7.1
 *
 * @return string[]
 */
function init_plugin_suite_user_engine_get_bulk_job_ids() {
	$ids = init_plugin_suite_user_engine_bulk_job_db_get( INIT_PLUGIN_SUITE_IUE_BULK_JOB_INDEX );

	return is_array( $ids ) ? array_values( array_map( 'strval', $ids ) ) : array();
}

/**
 * Get the jobs of the site, newest first.
 *
 * @since 1.7.1
 *
 * @param string $kind Optional. 'topup', 'notice' or '' for every kind. Default ''.
 * @return array[]
 */
function init_plugin_suite_user_engine_get_bulk_jobs( $kind = '' ) {
	$jobs = array();

	foreach ( array_reverse( init_plugin_suite_user_engine_get_bulk_job_ids() ) as $job_id ) {
		$job = init_plugin_suite_user_engine_get_bulk_job( $job_id );

		if ( $job && ( '' === $kind || $kind === $job['kind'] ) ) {
			$jobs[] = $job;
		}
	}

	return $jobs;
}

/**
 * Whether a job still has work to do.
 *
 * @since 1.7.1
 *
 * @param array $job Job.
 * @return bool
 */
function init_plugin_suite_user_engine_bulk_job_is_active( array $job ) {
	return in_array( $job['status'], array( 'queued', 'running' ), true );
}

/**
 * Whether a cancel was requested for a job.
 *
 * @since 1.7.1
 *
 * @param string $job_id Job ID.
 * @return bool
 */
function init_plugin_suite_user_engine_bulk_job_cancel_requested( $job_id ) {
	return null !== init_plugin_suite_user_engine_bulk_job_db_get( init_plugin_suite_user_engine_bulk_job_cancel_option( $job_id ) );
}

// -----------------------------------------------------------------------------
// Creating jobs
// -----------------------------------------------------------------------------

/**
 * Queue a bulk job.
 *
 * @since 1.7.1
 *
 * @param string $kind    'topup' or 'notice'.
 * @param string $target  'all' or 'vip'.
 * @param array  $payload Job data. Top-up: `amount` (int, may be negative), `currency`
 *                        ('coin'|'cash') and `label`. Notice: `title`, `content`, `type`,
 *                        `priority`, `link`, `pinned` and `expire` (Unix time or null).
 * @return array|WP_Error The new job.
 */
function init_plugin_suite_user_engine_create_bulk_job( $kind, $target, array $payload ) {
	if ( ! in_array( $kind, array( 'topup', 'notice' ), true ) || ! in_array( $target, array( 'all', 'vip' ), true ) ) {
		return new WP_Error( 'iue_bulk_job_invalid', __( 'Invalid background job.', 'init-user-engine' ) );
	}

	if ( ! init_plugin_suite_user_engine_acquire_lock( 'bulk_jobs_index', 5, 30 ) ) {
		return init_plugin_suite_user_engine_busy_error();
	}

	try {
		$job = array(
			'id'          => strtolower( wp_generate_password( 12, false, false ) ),
			'kind'        => $kind,
			'target'      => $target,
			'status'      => 'queued',
			'payload'     => $payload,
			'created_at'  => time(),
			// Same clock as get_active_vip_users() and the Inbox created_at column.
			'created_wp'  => (int) current_time( 'timestamp' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Must match iue_vip_expire and the Inbox created_at values, which use this clock.
			'created_by'  => get_current_user_id(),
			'started_at'  => 0,
			'finished_at' => 0,
			'cursor'      => 0,
			'total'       => 0,
			'processed'   => 0,
			'changed'     => 0,
			'failed'      => 0,
			'inflight'    => array(),
			'last_error'  => '',
		);

		$job['total'] = init_plugin_suite_user_engine_bulk_job_count_targets( $job );

		if ( 0 === $job['total'] ) {
			return new WP_Error( 'iue_bulk_job_empty', __( 'No members match the selected recipients.', 'init-user-engine' ) );
		}

		if ( ! init_plugin_suite_user_engine_save_bulk_job( $job ) ) {
			return new WP_Error( 'iue_bulk_job_db', __( 'Could not save the background job. Please try again.', 'init-user-engine' ) );
		}

		$ids   = init_plugin_suite_user_engine_get_bulk_job_ids();
		$ids[] = $job['id'];
		$ids   = init_plugin_suite_user_engine_prune_bulk_jobs( $ids );

		init_plugin_suite_user_engine_bulk_job_db_set( INIT_PLUGIN_SUITE_IUE_BULK_JOB_INDEX, $ids );
	} finally {
		init_plugin_suite_user_engine_release_lock( 'bulk_jobs_index' );
	}

	update_option( INIT_PLUGIN_SUITE_IUE_BULK_JOB_ACTIVE, 1, true );
	init_plugin_suite_user_engine_schedule_bulk_jobs();

	/**
	 * Fires after a background bulk job was queued.
	 *
	 * @since 1.7.1
	 *
	 * @param array $job Job.
	 */
	do_action( 'init_plugin_suite_user_engine_bulk_job_created', $job );

	return $job;
}

/**
 * Drop the oldest finished jobs so the history stays short.
 *
 * Jobs that are still waiting or running are never dropped.
 *
 * @since 1.7.1
 * @access private
 *
 * @param string[] $ids Job IDs, oldest first.
 * @return string[] Remaining job IDs.
 */
function init_plugin_suite_user_engine_prune_bulk_jobs( array $ids ) {
	$excess = count( $ids ) - INIT_PLUGIN_SUITE_IUE_BULK_JOB_KEEP;

	if ( $excess <= 0 ) {
		return $ids;
	}

	$keep = array();

	foreach ( $ids as $job_id ) {
		$job = init_plugin_suite_user_engine_get_bulk_job( $job_id );

		if ( $excess > 0 && ( ! $job || ! init_plugin_suite_user_engine_bulk_job_is_active( $job ) ) ) {
			init_plugin_suite_user_engine_bulk_job_db_delete( init_plugin_suite_user_engine_bulk_job_option( $job_id ) );
			init_plugin_suite_user_engine_bulk_job_db_delete( init_plugin_suite_user_engine_bulk_job_cancel_option( $job_id ) );
			--$excess;
			continue;
		}

		$keep[] = $job_id;
	}

	return $keep;
}

/**
 * Ask a job to stop. The runner stops at its next check (within one batch).
 *
 * Members already handled keep their top-up or message.
 *
 * @since 1.7.1
 *
 * @param string $job_id Job ID.
 * @return bool False when the job does not exist or is already finished.
 */
function init_plugin_suite_user_engine_cancel_bulk_job( $job_id ) {
	$job = init_plugin_suite_user_engine_get_bulk_job( $job_id );

	if ( ! $job || ! init_plugin_suite_user_engine_bulk_job_is_active( $job ) ) {
		return false;
	}

	init_plugin_suite_user_engine_bulk_job_db_set( init_plugin_suite_user_engine_bulk_job_cancel_option( $job_id ), get_current_user_id() );
	init_plugin_suite_user_engine_schedule_bulk_jobs();

	return true;
}

// -----------------------------------------------------------------------------
// Finding the members of a job
// -----------------------------------------------------------------------------

/**
 * WP_User_Query arguments that select the members of a job.
 *
 * Same members as before 1.7.1: get_users() for "All members" (members of the
 * current site on Multisite) and the iue_vip_expire rule of
 * init_plugin_suite_user_engine_get_active_vip_users() for "Active VIPs". VIP
 * status is checked against the time the job was created, so a VIP whose
 * membership runs out while the job runs still gets it.
 *
 * @since 1.7.1
 * @access private
 *
 * @param array $job Job.
 * @return array
 */
function init_plugin_suite_user_engine_bulk_job_query_args( array $job ) {
	$args = array(
		'fields'        => 'ID',
		'cache_results' => false,
	);

	if ( 'vip' === $job['target'] ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Same rule as init_plugin_suite_user_engine_get_active_vip_users().
		$args['meta_query'] = array(
			array(
				'key'     => 'iue_vip_expire',
				'value'   => (int) $job['created_wp'],
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
		);
	}

	/**
	 * Filters the WP_User_Query arguments that select the members of a background job.
	 *
	 * `number`, `orderby`, `order` and the ID cursor are set by the runner and cannot be changed here.
	 *
	 * @since 1.7.1
	 *
	 * @param array $args WP_User_Query arguments.
	 * @param array $job  Job.
	 */
	return (array) apply_filters( 'init_plugin_suite_user_engine_bulk_job_query_args', $args, $job );
}

/**
 * Count the members a job will go through (shown as the job's total).
 *
 * @since 1.7.1
 * @access private
 *
 * @param array $job Job.
 * @return int
 */
function init_plugin_suite_user_engine_bulk_job_count_targets( array $job ) {
	$args                = init_plugin_suite_user_engine_bulk_job_query_args( $job );
	$args['number']      = 1;
	$args['count_total'] = true;

	$query = new WP_User_Query( $args );

	return (int) $query->get_total();
}

/**
 * Next members of a job, after the job's cursor, lowest user ID first.
 *
 * @since 1.7.1
 * @access private
 *
 * @param array $job   Job.
 * @param int   $limit Batch size.
 * @return int[]
 */
function init_plugin_suite_user_engine_bulk_job_fetch_user_ids( array $job, $limit ) {
	$args                 = init_plugin_suite_user_engine_bulk_job_query_args( $job );
	$args['number']       = max( 1, (int) $limit );
	$args['orderby']      = 'ID';
	$args['order']        = 'ASC';
	$args['count_total']  = false;
	$args['iue_after_id'] = absint( $job['cursor'] );

	return array_map( 'absint', (array) get_users( $args ) );
}

/**
 * Add the `ID > cursor` condition to the user queries of the bulk jobs.
 *
 * Walking users by primary key stays fast however many members the site has,
 * unlike OFFSET paging, and does not skip anyone when users are added or
 * deleted while the job runs.
 *
 * @since 1.7.1
 *
 * @param WP_User_Query $query User query.
 * @return void
 */
function init_plugin_suite_user_engine_bulk_job_user_query_cursor( $query ) {
	$after = $query->get( 'iue_after_id' );

	if ( null === $after || '' === $after ) {
		return;
	}

	global $wpdb;

	$query->query_where .= $wpdb->prepare( " AND {$wpdb->users}.ID > %d", absint( $after ) );
}

// -----------------------------------------------------------------------------
// Running jobs
// -----------------------------------------------------------------------------

/**
 * Make sure the queue runner is scheduled.
 *
 * @since 1.7.1
 *
 * @return void
 */
function init_plugin_suite_user_engine_schedule_bulk_jobs() {
	if ( ! wp_next_scheduled( INIT_PLUGIN_SUITE_IUE_BULK_JOB_EVENT ) ) {
		wp_schedule_single_event( time(), INIT_PLUGIN_SUITE_IUE_BULK_JOB_EVENT );
	}
}

/**
 * WP-Cron handler.
 *
 * @since 1.7.1
 *
 * @return void
 */
function init_plugin_suite_user_engine_run_bulk_jobs_event() {
	init_plugin_suite_user_engine_run_bulk_jobs();
}

/**
 * Seconds a background slice may run before it hands over to the next one.
 *
 * @since 1.7.1
 * @access private
 *
 * @return float
 */
function init_plugin_suite_user_engine_bulk_job_time_budget() {
	$limit = (int) ini_get( 'max_execution_time' );

	if ( $limit > 0 ) {
		$budget = min( 20, max( 3, (int) floor( $limit / 2 ) ) );
	} else {
		// No PHP time limit (WP-CLI, or `php wp-cron.php` run from the command line by a system cron job): do more per run,
		// because the next slice may only start at the next system cron run.
		$budget = 60;
	}

	/**
	 * Filters how many seconds one background slice of the bulk job queue may run.
	 *
	 * @since 1.7.1
	 *
	 * @param int $budget Seconds. Default 20 (or half of max_execution_time when lower), 60 when PHP has no time limit.
	 */
	return (float) max( 1, (int) apply_filters( 'init_plugin_suite_user_engine_bulk_job_time_budget', $budget ) );
}

/**
 * Run the queue for a while: oldest job first, until the time budget is used.
 *
 * Only one process runs the queue at a time. When work is left, the next slice
 * is scheduled right away.
 *
 * @since 1.7.1
 *
 * @param float|null $budget Optional. Seconds to run. Default from init_plugin_suite_user_engine_bulk_job_time_budget().
 * @return bool True when this call ran the queue, false when another process holds it.
 */
function init_plugin_suite_user_engine_run_bulk_jobs( $budget = null ) {
	$budget = null === $budget ? init_plugin_suite_user_engine_bulk_job_time_budget() : max( 0.5, (float) $budget );

	// The lock only expires long after the slice ends, so two runners can never overlap.
	if ( ! init_plugin_suite_user_engine_acquire_lock( 'bulk_jobs_runner', 0, (int) ceil( $budget ) + 300 ) ) {
		return false;
	}

	$deadline = microtime( true ) + $budget;
	$active   = false;

	try {
		foreach ( init_plugin_suite_user_engine_get_bulk_job_ids() as $job_id ) {
			$job = init_plugin_suite_user_engine_get_bulk_job( $job_id );

			if ( ! $job || ! init_plugin_suite_user_engine_bulk_job_is_active( $job ) ) {
				continue;
			}

			if ( microtime( true ) >= $deadline ) {
				$active = true;
				break;
			}

			$job = init_plugin_suite_user_engine_process_bulk_job( $job, $deadline );

			if ( init_plugin_suite_user_engine_bulk_job_is_active( $job ) ) {
				$active = true;
				break;
			}
		}
	} finally {
		init_plugin_suite_user_engine_release_lock( 'bulk_jobs_runner' );
	}

	if ( $active ) {
		init_plugin_suite_user_engine_schedule_bulk_jobs();
	} else {
		update_option( INIT_PLUGIN_SUITE_IUE_BULK_JOB_ACTIVE, 0, true );
	}

	return true;
}

/**
 * Work on one job until it is finished or the deadline passes.
 *
 * @since 1.7.1
 * @access private
 *
 * @param array $job      Job.
 * @param float $deadline microtime( true ) at which to stop.
 * @return array The job as saved.
 */
function init_plugin_suite_user_engine_process_bulk_job( array $job, $deadline ) {
	if ( 'queued' === $job['status'] ) {
		$job['status']     = 'running';
		$job['started_at'] = time();
		init_plugin_suite_user_engine_save_bulk_job( $job );
	}

	while ( microtime( true ) < $deadline ) {
		if ( init_plugin_suite_user_engine_bulk_job_cancel_requested( $job['id'] ) ) {
			return init_plugin_suite_user_engine_finish_bulk_job( $job, 'cancelled' );
		}

		$batch = 'topup' === $job['kind'] ? 100 : 500;

		/**
		 * Filters how many members a background job loads per batch.
		 *
		 * @since 1.7.1
		 *
		 * @param int   $batch Default 100 for top-ups, 500 for notifications.
		 * @param array $job   Job.
		 */
		$batch    = max( 1, (int) apply_filters( 'init_plugin_suite_user_engine_bulk_job_batch_size', $batch, $job ) );
		$user_ids = init_plugin_suite_user_engine_bulk_job_fetch_user_ids( $job, $batch );

		if ( empty( $user_ids ) ) {
			return init_plugin_suite_user_engine_finish_bulk_job( $job, 'done' );
		}

		$job = 'topup' === $job['kind']
			? init_plugin_suite_user_engine_bulk_job_topup_batch( $job, $user_ids, $deadline )
			: init_plugin_suite_user_engine_bulk_job_notice_batch( $job, $user_ids );

		if ( ! init_plugin_suite_user_engine_bulk_job_is_active( $job ) ) {
			return $job;
		}
	}

	return $job;
}

/**
 * Mark a job as finished.
 *
 * @since 1.7.1
 * @access private
 *
 * @param array  $job    Job.
 * @param string $status 'done', 'cancelled' or 'failed'.
 * @return array
 */
function init_plugin_suite_user_engine_finish_bulk_job( array $job, $status ) {
	$job['status']      = $status;
	$job['finished_at'] = time();
	$job['inflight']    = array();

	init_plugin_suite_user_engine_save_bulk_job( $job );

	/**
	 * Fires when a background bulk job ends (done, cancelled or failed).
	 *
	 * @since 1.7.1
	 *
	 * @param array $job Job.
	 */
	do_action( 'init_plugin_suite_user_engine_bulk_job_finished', $job );

	return $job;
}

/**
 * Top-up one batch of members, one transaction per member.
 *
 * Wallet change, transaction log row, Inbox message and job cursor are
 * committed together, so a crash never pays a member twice nor skips one.
 *
 * @since 1.7.1
 * @access private
 *
 * @param array $job      Job.
 * @param int[] $user_ids Members, lowest ID first.
 * @param float $deadline microtime( true ) at which to stop.
 * @return array
 */
function init_plugin_suite_user_engine_bulk_job_topup_batch( array $job, array $user_ids, $deadline ) {
	global $wpdb;

	$amount   = (int) $job['payload']['amount'];
	$currency = 'cash' === $job['payload']['currency'] ? 'cash' : 'coin';
	$label    = (string) $job['payload']['label'];

	foreach ( $user_ids as $user_id ) {
		if ( microtime( true ) >= $deadline ) {
			break;
		}

		$saved = $job;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Wallet change, log, message and cursor must commit together.
		$wpdb->query( 'START TRANSACTION' );

		// RAW adjust like the Top-up tool always did: no VIP bonus, never below 0.
		$result = init_plugin_suite_user_engine_wallet_change( $user_id, $currency, $amount, true );

		if ( is_wp_error( $result ) ) {
			++$job['failed'];
			$job['last_error'] = $result->get_error_message();
		} elseif ( 0 !== (int) $result['delta'] ) {
			$applied = abs( (int) $result['delta'] );
			$change  = $result['delta'] < 0 ? 'deduct' : 'add';

			init_plugin_suite_user_engine_log_transaction( $user_id, $currency, $applied, 'topup_admin', $change, false );
			init_plugin_suite_user_engine_bulk_job_topup_message( $job, $user_id, $applied, $change, $label );

			++$job['changed'];
		}

		++$job['processed'];
		$job['cursor'] = $user_id;

		if ( ! init_plugin_suite_user_engine_save_bulk_job( $job ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Undo this member: the cursor could not be stored.
			$wpdb->query( 'ROLLBACK' );
			wp_cache_delete( $user_id, 'user_meta' );

			$saved['last_error'] = __( 'Could not save the job progress. It will be retried.', 'init-user-engine' );
			return $saved;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'COMMIT' );
	}

	return $job;
}

/**
 * Send the Inbox message of a top-up to one member (same text as before 1.7.1).
 *
 * @since 1.7.1
 * @access private
 *
 * @param array  $job     Job.
 * @param int    $user_id Member.
 * @param int    $applied Amount actually added or deducted.
 * @param string $change  'add' or 'deduct'.
 * @param string $label   Currency label.
 * @return void
 */
function init_plugin_suite_user_engine_bulk_job_topup_message( array $job, $user_id, $applied, $change, $label ) {
	if ( 'deduct' === $change ) {
		/* translators: %s = currency label (e.g., Coin or Cash) */
		$title = sprintf( __( 'Admin Adjustment %s', 'init-user-engine' ), $label );
		/* translators: 1: amount, 2: currency label */
		$content = sprintf( __( '%1$d %2$s has been deducted by admin.', 'init-user-engine' ), (int) $applied, $label );
		$icon    = 'warning';
	} else {
		/* translators: %s = currency label (e.g., Coin or Cash) */
		$title = sprintf( __( 'Admin Top-up %s', 'init-user-engine' ), $label );
		/* translators: 1: amount, 2: currency label */
		$content = sprintf( __( 'You have received %1$d %2$s from the admin.', 'init-user-engine' ), (int) $applied, $label );
		$icon    = 'gift';
	}

	init_plugin_suite_user_engine_insert_inbox(
		$user_id,
		$title,
		$content,
		$icon,
		array(
			'topup_by_admin' => 1,
			'bulk_job'       => $job['id'],
		)
	);
}

/**
 * Send the notification of a job to one batch of members (bulk insert).
 *
 * The batch is recorded as "in flight" before the insert. If the previous run
 * died during a batch, members of that batch who already got the message are
 * skipped, so nobody gets it twice.
 *
 * @since 1.7.1
 * @access private
 *
 * @param array $job      Job.
 * @param int[] $user_ids Members, lowest ID first.
 * @return array
 */
function init_plugin_suite_user_engine_bulk_job_notice_batch( array $job, array $user_ids ) {
	$p       = $job['payload'];
	$send    = $user_ids;
	$already = 0;

	if ( ! empty( $job['inflight'] ) ) {
		$send    = array_values( array_diff( $send, init_plugin_suite_user_engine_bulk_job_already_notified( $job, $user_ids ) ) );
		$already = count( $user_ids ) - count( $send );
	}

	$job['inflight'] = array( (int) reset( $user_ids ), (int) end( $user_ids ) );

	if ( ! init_plugin_suite_user_engine_save_bulk_job( $job ) ) {
		$job['last_error'] = __( 'Could not save the job progress. It will be retried.', 'init-user-engine' );
		return $job;
	}

	$inserted = 0;

	if ( ! empty( $send ) ) {
		$inserted = init_plugin_suite_user_engine_send_inbox_to_users(
			$send,
			$p['title'],
			$p['content'],
			$p['type'],
			array(
				'admin_notice' => 1,
				'bulk_job'     => $job['id'],
			),
			$p['expire'],
			$p['priority'],
			$p['link'],
			$p['pinned']
		);

		/** This action is documented in includes/tools/send-notification.php */
		do_action(
			'init_plugin_suite_user_engine_admin_send_notice',
			$send,
			$p['title'],
			$p['content'],
			$p['type'],
			$p['priority'],
			$p['link'],
			$p['pinned'],
			$p['expire'],
			$job['target']
		);
	}

	$job['processed'] += count( $user_ids );
	$job['changed']   += (int) $inserted + $already;
	$job['failed']    += max( 0, count( $send ) - (int) $inserted );
	$job['cursor']     = (int) end( $user_ids );
	$job['inflight']   = array();

	init_plugin_suite_user_engine_save_bulk_job( $job );

	return $job;
}

/**
 * Members of a batch who already have the job's message (crash recovery only).
 *
 * @since 1.7.1
 * @access private
 *
 * @param array $job      Job.
 * @param int[] $user_ids Members of the batch.
 * @return int[]
 */
function init_plugin_suite_user_engine_bulk_job_already_notified( array $job, array $user_ids ) {
	global $wpdb;

	$table        = init_plugin_suite_user_engine_get_inbox_table();
	$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
	$params       = array_merge(
		$user_ids,
		array(
			(int) $job['created_wp'],
			'%' . $wpdb->esc_like( '"bulk_job";s:' . strlen( $job['id'] ) . ':"' . $job['id'] . '"' ) . '%',
		)
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Live check, only after a crash; the table name is built from $wpdb->prefix.
	$found = $wpdb->get_col(
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- All values are passed as one array.
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $table is safe, $placeholders is a list of %d.
			"SELECT DISTINCT user_id FROM {$table} WHERE user_id IN ({$placeholders}) AND created_at >= %d AND metadata LIKE %s",
			$params
		)
	);

	return array_map( 'absint', (array) $found );
}

// -----------------------------------------------------------------------------
// Admin: watchdog, status, cancel, panel
// -----------------------------------------------------------------------------

/**
 * Re-schedule the queue if it has work but no event (e.g. after the plugin was
 * deactivated and activated again while a job was running).
 *
 * Costs nothing when no job is active: the flag is autoloaded.
 *
 * @since 1.7.1
 *
 * @return void
 */
function init_plugin_suite_user_engine_bulk_jobs_watchdog() {
	if ( get_option( INIT_PLUGIN_SUITE_IUE_BULK_JOB_ACTIVE ) ) {
		init_plugin_suite_user_engine_schedule_bulk_jobs();
	}
}

/**
 * Minutes without progress after which a job is reported as stalled.
 *
 * @since 1.7.1
 *
 * @return int
 */
function init_plugin_suite_user_engine_bulk_job_stall_minutes() {
	/**
	 * Filters after how many minutes without progress a background job is reported as stalled.
	 *
	 * Keep it longer than the interval of the system cron job on sites that set DISABLE_WP_CRON.
	 *
	 * @since 1.7.1
	 *
	 * @param int $minutes Default 15.
	 */
	return max( 1, (int) apply_filters( 'init_plugin_suite_user_engine_bulk_job_stall_minutes', 15 ) );
}

/**
 * Whether a job has made no progress for a long time (cron may not run on this site).
 *
 * Based on the job's own progress, never on how late the cron event is: on a
 * site that runs WP-Cron from a system cron job every few minutes, the event is
 * always "late" between two runs, and that is normal.
 *
 * @since 1.7.1
 *
 * @param array $job Job.
 * @return bool
 */
function init_plugin_suite_user_engine_bulk_job_is_stalled( array $job ) {
	if ( ! init_plugin_suite_user_engine_bulk_job_is_active( $job ) ) {
		return false;
	}

	$last = max( (int) $job['created_at'], (int) $job['started_at'], isset( $job['updated_at'] ) ? (int) $job['updated_at'] : 0 );

	return ( time() - $last ) >= init_plugin_suite_user_engine_bulk_job_stall_minutes() * MINUTE_IN_SECONDS;
}

/**
 * Check the nonce and capability of the background job ajax requests.
 *
 * @since 1.7.1
 * @access private
 *
 * @return string Job kind of the page: 'topup' or 'notice'.
 */
function init_plugin_suite_user_engine_bulk_jobs_ajax_guard() {
	check_ajax_referer( 'iue_bulk_jobs', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to access this page.', 'init-user-engine' ) ), 403 );
	}

	return isset( $_POST['kind'] ) && 'notice' === sanitize_key( wp_unslash( $_POST['kind'] ) ) ? 'notice' : 'topup';
}

/**
 * Send the job rows of a tool page as JSON.
 *
 * @since 1.7.1
 * @access private
 *
 * @param string $kind 'topup' or 'notice'.
 * @return void
 */
function init_plugin_suite_user_engine_bulk_jobs_send_status( $kind ) {
	$jobs    = init_plugin_suite_user_engine_get_bulk_jobs( $kind );
	$active  = false;
	$stalled = false;

	foreach ( $jobs as $job ) {
		if ( init_plugin_suite_user_engine_bulk_job_is_active( $job ) ) {
			$active  = true;
			$stalled = $stalled || init_plugin_suite_user_engine_bulk_job_is_stalled( $job );
		}
	}

	ob_start();
	init_plugin_suite_user_engine_render_bulk_job_rows( $jobs );

	wp_send_json_success(
		array(
			'html'    => ob_get_clean(),
			'active'  => $active,
			'stalled' => $stalled,
		)
	);
}

/**
 * Admin-ajax: progress of the jobs shown on a tool page. Read only.
 *
 * It never runs a job: jobs run from WP-Cron (built-in, or a system cron job
 * when DISABLE_WP_CRON is set). It only makes sure the cron event exists.
 *
 * @since 1.7.1
 *
 * @return void
 */
function init_plugin_suite_user_engine_ajax_bulk_jobs_status() {
	$kind = init_plugin_suite_user_engine_bulk_jobs_ajax_guard();

	if ( get_option( INIT_PLUGIN_SUITE_IUE_BULK_JOB_ACTIVE ) ) {
		init_plugin_suite_user_engine_schedule_bulk_jobs();
	}

	init_plugin_suite_user_engine_bulk_jobs_send_status( $kind );
}

/**
 * Admin-ajax: process the queue for a few seconds from the open admin page.
 *
 * Only sent after the admin clicked "Process from this page" on a stalled job.
 * Shares the runner lock with WP-Cron, so it can never run at the same time as
 * a cron run: when cron is busy, this request does nothing.
 *
 * @since 1.7.1
 *
 * @return void
 */
function init_plugin_suite_user_engine_ajax_bulk_jobs_run() {
	$kind = init_plugin_suite_user_engine_bulk_jobs_ajax_guard();

	init_plugin_suite_user_engine_run_bulk_jobs( 5 );

	init_plugin_suite_user_engine_bulk_jobs_send_status( $kind );
}

/**
 * Admin-post: cancel a job.
 *
 * @since 1.7.1
 *
 * @return void
 */
function init_plugin_suite_user_engine_handle_cancel_bulk_job() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'init-user-engine' ) );
	}

	check_admin_referer( 'iue_cancel_bulk_job' );

	$job_id = isset( $_POST['job_id'] ) ? sanitize_key( wp_unslash( $_POST['job_id'] ) ) : '';
	$job    = init_plugin_suite_user_engine_get_bulk_job( $job_id );

	if ( $job ) {
		init_plugin_suite_user_engine_cancel_bulk_job( $job_id );
	}

	$page = ( $job && 'notice' === $job['kind'] ) ? 'init-user-engine-send-notification' : 'init-user-engine-topup';

	wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
	exit;
}

/**
 * Load the progress script on the Top-up and Send Notification pages.
 *
 * @since 1.7.1
 *
 * @param string $hook Admin page hook.
 * @return void
 */
function init_plugin_suite_user_engine_bulk_jobs_assets( $hook ) {
	if ( false === strpos( (string) $hook, 'init-user-engine-topup' ) && false === strpos( (string) $hook, 'init-user-engine-send-notification' ) ) {
		return;
	}

	wp_enqueue_script( 'iue-bulk-jobs', INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'js/bulk-jobs.js', array(), INIT_PLUGIN_SUITE_IUE_VERSION, true );
	wp_localize_script(
		'iue-bulk-jobs',
		'InitPluginSuiteUserEngineBulkJobs',
		array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'iue_bulk_jobs' ),
			'interval' => 4000,
			'i18n'     => array(
				'processing' => __( 'Processing from this page… keep this tab open.', 'init-user-engine' ),
			),
		)
	);
}

/**
 * Human readable status of a job.
 *
 * @since 1.7.1
 * @access private
 *
 * @param array $job Job.
 * @return string
 */
function init_plugin_suite_user_engine_bulk_job_status_label( array $job ) {
	if ( init_plugin_suite_user_engine_bulk_job_is_active( $job ) && init_plugin_suite_user_engine_bulk_job_cancel_requested( $job['id'] ) ) {
		return __( 'Cancelling…', 'init-user-engine' );
	}

	$labels = array(
		'queued'    => __( 'Queued', 'init-user-engine' ),
		'running'   => __( 'Running', 'init-user-engine' ),
		'done'      => __( 'Done', 'init-user-engine' ),
		'cancelled' => __( 'Cancelled', 'init-user-engine' ),
		'failed'    => __( 'Failed', 'init-user-engine' ),
	);

	return isset( $labels[ $job['status'] ] ) ? $labels[ $job['status'] ] : (string) $job['status'];
}

/**
 * Render the background jobs table of a tool page.
 *
 * @since 1.7.1
 *
 * @param string $kind 'topup' or 'notice'.
 * @return void
 */
function init_plugin_suite_user_engine_render_bulk_jobs_panel( $kind ) {
	$jobs = init_plugin_suite_user_engine_get_bulk_jobs( $kind );

	if ( empty( $jobs ) ) {
		return;
	}

	$active = false;

	foreach ( $jobs as $job ) {
		if ( init_plugin_suite_user_engine_bulk_job_is_active( $job ) ) {
			$active = true;
			break;
		}
	}
	?>
	<hr style="margin:24px 0;">
	<h2><?php esc_html_e( 'Background Jobs', 'init-user-engine' ); ?></h2>
	<p class="description">
		<?php esc_html_e( '"All members" and "Active VIPs" run in the background in small batches, so large sites never hit a time limit. You can leave this page; progress updates by itself while it is open.', 'init-user-engine' ); ?>
	</p>
	<table class="widefat striped iue-bulk-jobs" style="margin-top:10px;" data-kind="<?php echo esc_attr( $kind ); ?>" data-active="<?php echo $active ? '1' : '0'; ?>">
		<thead>
			<tr>
				<th style="width:160px;"><?php esc_html_e( 'Created', 'init-user-engine' ); ?></th>
				<th style="width:120px;"><?php esc_html_e( 'Target', 'init-user-engine' ); ?></th>
				<th><?php esc_html_e( 'Details', 'init-user-engine' ); ?></th>
				<th style="width:220px;"><?php esc_html_e( 'Progress', 'init-user-engine' ); ?></th>
				<th style="width:110px;"><?php esc_html_e( 'Status', 'init-user-engine' ); ?></th>
				<th style="width:90px;"></th>
			</tr>
		</thead>
		<tbody>
			<?php init_plugin_suite_user_engine_render_bulk_job_rows( $jobs ); ?>
		</tbody>
	</table>
	<?php
}

/**
 * Render the rows of the background jobs table.
 *
 * @since 1.7.1
 * @access private
 *
 * @param array[] $jobs Jobs, newest first.
 * @return void
 */
function init_plugin_suite_user_engine_render_bulk_job_rows( array $jobs ) {
	foreach ( $jobs as $job ) {
		$total   = max( (int) $job['total'], (int) $job['processed'] );
		$percent = $total > 0 ? min( 100, (int) floor( $job['processed'] * 100 / $total ) ) : ( 'done' === $job['status'] ? 100 : 0 );
		$target  = 'vip' === $job['target'] ? __( 'Active VIPs', 'init-user-engine' ) : __( 'All members', 'init-user-engine' );

		if ( 'topup' === $job['kind'] ) {
			$details = sprintf( '%+d %s', (int) $job['payload']['amount'], (string) $job['payload']['label'] );
			/* translators: %s: number of members whose balance changed. */
			$changed = sprintf( __( '%s changed', 'init-user-engine' ), number_format_i18n( (int) $job['changed'] ) );
		} else {
			$details = (string) $job['payload']['title'];
			/* translators: %s: number of messages sent. */
			$changed = sprintf( __( '%s sent', 'init-user-engine' ), number_format_i18n( (int) $job['changed'] ) );
		}
		?>
		<tr>
			<td><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $job['created_at'] ) ); ?></td>
			<td><?php echo esc_html( $target ); ?></td>
			<td><?php echo esc_html( $details ); ?></td>
			<td>
				<div style="background:#dcdcde;border-radius:3px;height:8px;overflow:hidden;margin-bottom:4px;">
					<div style="background:#2271b1;height:8px;width:<?php echo (int) $percent; ?>%;"></div>
				</div>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: members processed, 2: total members, 3: percent. */
						__( '%1$s / %2$s (%3$d%%)', 'init-user-engine' ),
						number_format_i18n( (int) $job['processed'] ),
						number_format_i18n( $total ),
						$percent
					)
				);
				?>
				<br><span class="description"><?php echo esc_html( $changed ); ?></span>
				<?php if ( (int) $job['failed'] > 0 ) : ?>
					<br><span style="color:#b32d2e;">
						<?php
						/* translators: %s: number of members that failed. */
						echo esc_html( sprintf( __( '%s failed', 'init-user-engine' ), number_format_i18n( (int) $job['failed'] ) ) );
						?>
					</span>
				<?php endif; ?>
			</td>
			<td>
				<?php echo esc_html( init_plugin_suite_user_engine_bulk_job_status_label( $job ) ); ?>
				<?php if ( init_plugin_suite_user_engine_bulk_job_is_stalled( $job ) ) : ?>
					<p style="color:#996800;margin:6px 0 4px;">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: minutes. */
								_n( 'No progress for %d minute.', 'No progress for %d minutes.', init_plugin_suite_user_engine_bulk_job_stall_minutes(), 'init-user-engine' ),
								init_plugin_suite_user_engine_bulk_job_stall_minutes()
							)
						);
						?>
					</p>
					<p class="description" style="margin:0 0 6px;"><?php esc_html_e( 'Jobs run on WP-Cron. If this site runs WP-Cron from a system cron job, the job continues at its next run. If cron does not run on this site, you can process it from this page while it stays open.', 'init-user-engine' ); ?></p>
					<button type="button" class="button button-small iue-bulk-job-run"><?php esc_html_e( 'Process from this page', 'init-user-engine' ); ?></button>
				<?php endif; ?>
			</td>
			<td>
				<?php if ( init_plugin_suite_user_engine_bulk_job_is_active( $job ) && ! init_plugin_suite_user_engine_bulk_job_cancel_requested( $job['id'] ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="iue-bulk-job-cancel" data-confirm="<?php esc_attr_e( 'Stop this job? Members already processed keep what they received.', 'init-user-engine' ); ?>">
						<?php wp_nonce_field( 'iue_cancel_bulk_job' ); ?>
						<input type="hidden" name="action" value="iue_cancel_bulk_job">
						<input type="hidden" name="job_id" value="<?php echo esc_attr( $job['id'] ); ?>">
						<button type="submit" class="button button-small"><?php esc_html_e( 'Cancel', 'init-user-engine' ); ?></button>
					</form>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}
