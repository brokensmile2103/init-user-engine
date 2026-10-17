<?php
/**
 * Wallet primitives: atomic balance changes and short per-user locks.
 *
 * Every Coin/Cash change must go through init_plugin_suite_user_engine_wallet_change()
 * (or the add/spend helpers built on it). It changes the balance with a single
 * UPDATE statement, so two requests running at the same time can never both
 * spend the same balance, and a credit can never overwrite a spend (or the
 * other way round).
 *
 * The lock helpers are for flows that read, decide and then write several
 * values (check-in, VIP purchase, exchange...). They serialize those flows per
 * user. The balance itself never depends on the lock: it is protected by the
 * atomic UPDATE even when a lock could not be taken.
 *
 * @package Init_User_Engine
 * @since   1.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Locks
// -----------------------------------------------------------------------------

/**
 * Locks held by the current PHP request, keyed by option name.
 *
 * @since 1.6.8
 *
 * @return array Reference to the registry: option name => array( 'value' => string, 'depth' => int ).
 */
function &init_plugin_suite_user_engine_held_locks() {
	static $held = array();
	return $held;
}

/**
 * Option name that stores a lock.
 *
 * @since 1.6.8
 *
 * @param string $name Lock name.
 * @return string
 */
function init_plugin_suite_user_engine_lock_option_name( $name ) {
	return '_iue_lock_' . md5( (string) $name );
}

/**
 * Whether the current request already holds a lock.
 *
 * @since 1.6.8
 *
 * @param string $name Lock name.
 * @return bool
 */
function init_plugin_suite_user_engine_is_lock_held( $name ) {
	$held = &init_plugin_suite_user_engine_held_locks();
	return isset( $held[ init_plugin_suite_user_engine_lock_option_name( $name ) ] );
}

/**
 * Acquire a named lock shared by every PHP process of the site.
 *
 * The lock is a row in the options table. Creating it uses INSERT IGNORE on the
 * unique option_name column, so only one process can win. A lock left behind by
 * a crashed process expires after $ttl seconds and is then taken over with a
 * compare-and-swap UPDATE. Locks are re-entrant inside the same request and are
 * released automatically at shutdown.
 *
 * @since 1.6.8
 *
 * @param string    $name Lock name.
 * @param int|float $wait Optional. Seconds to wait for the lock. 0 = try once. Default 10.
 * @param int       $ttl  Optional. Seconds after which a lock is considered stale. Default 30.
 * @return bool True when the lock is held by this request.
 */
function init_plugin_suite_user_engine_acquire_lock( $name, $wait = 10, $ttl = 30 ) {
	global $wpdb;

	$option = init_plugin_suite_user_engine_lock_option_name( $name );
	$held   = &init_plugin_suite_user_engine_held_locks();

	if ( isset( $held[ $option ] ) ) {
		++$held[ $option ]['depth'];
		return true;
	}

	$ttl      = max( 5, (int) $ttl );
	$wait     = max( 0, (float) $wait );
	$deadline = microtime( true ) + $wait;
	$token    = wp_generate_uuid4();

	do {
		$value = ( time() + $ttl ) . '|' . $token;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- A lock must hit the database directly; caching it would defeat its purpose.
		$inserted = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
				$option,
				$value
			)
		);

		if ( 1 === $inserted ) {
			init_plugin_suite_user_engine_register_lock( $option, $value );
			return true;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Must read the live row, never a cached copy.
		$current = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				$option
			)
		);

		if ( null === $current ) {
			// Released between our two queries: try again right away.
			continue;
		}

		$parts   = explode( '|', (string) $current, 2 );
		$expires = (int) $parts[0];

		if ( $expires < time() ) {
			// Stale lock (its owner died): take it over only if nobody else did first.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Compare-and-swap on the live row.
			$taken = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
					$value,
					$option,
					$current
				)
			);

			if ( 1 === $taken ) {
				init_plugin_suite_user_engine_register_lock( $option, $value );
				return true;
			}

			continue;
		}

		if ( $wait <= 0 ) {
			break;
		}

		usleep( wp_rand( 25000, 75000 ) );
	} while ( microtime( true ) < $deadline );

	return false;
}

/**
 * Remember a lock taken by this request and make sure it is released at shutdown.
 *
 * @since 1.6.8
 * @access private
 *
 * @param string $option Option name of the lock.
 * @param string $value  Value written by this request (expiry|token).
 * @return void
 */
function init_plugin_suite_user_engine_register_lock( $option, $value ) {
	static $shutdown_registered = false;

	$held            = &init_plugin_suite_user_engine_held_locks();
	$held[ $option ] = array(
		'value' => $value,
		'depth' => 1,
	);

	if ( ! $shutdown_registered ) {
		register_shutdown_function( 'init_plugin_suite_user_engine_release_all_locks' );
		$shutdown_registered = true;
	}
}

/**
 * Release a lock taken with init_plugin_suite_user_engine_acquire_lock().
 *
 * Only the request that owns the lock can delete it.
 *
 * @since 1.6.8
 *
 * @param string $name Lock name.
 * @return bool False when this request did not hold the lock.
 */
function init_plugin_suite_user_engine_release_lock( $name ) {
	$option = init_plugin_suite_user_engine_lock_option_name( $name );
	$held   = &init_plugin_suite_user_engine_held_locks();

	if ( ! isset( $held[ $option ] ) ) {
		return false;
	}

	--$held[ $option ]['depth'];

	if ( $held[ $option ]['depth'] > 0 ) {
		return true;
	}

	init_plugin_suite_user_engine_delete_lock_row( $option, $held[ $option ]['value'] );
	unset( $held[ $option ] );

	return true;
}

/**
 * Release every lock still held by this request (shutdown handler).
 *
 * @since 1.6.8
 *
 * @return void
 */
function init_plugin_suite_user_engine_release_all_locks() {
	$held = &init_plugin_suite_user_engine_held_locks();

	foreach ( $held as $option => $lock ) {
		init_plugin_suite_user_engine_delete_lock_row( $option, $lock['value'] );
	}

	$held = array();
}

/**
 * Delete a lock row if it still belongs to us.
 *
 * @since 1.6.8
 * @access private
 *
 * @param string $option Option name of the lock.
 * @param string $value  Value written by this request.
 * @return void
 */
function init_plugin_suite_user_engine_delete_lock_row( $option, $value ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Lock rows never go through the options API.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
			$option,
			$value
		)
	);
}

/**
 * Lock every balance-changing flow of one user.
 *
 * On the first acquisition in a request the user's meta cache is dropped, so
 * values read afterwards come from the database and include the writes made by
 * the request that held the lock before us.
 *
 * @since 1.6.8
 *
 * @param int       $user_id User ID.
 * @param int|float $wait    Optional. Seconds to wait. Default 10.
 * @return bool True when the lock is held.
 */
function init_plugin_suite_user_engine_lock_user( $user_id, $wait = 10 ) {
	$user_id = absint( $user_id );

	if ( ! $user_id ) {
		return false;
	}

	$name     = 'user_' . $user_id;
	$was_held = init_plugin_suite_user_engine_is_lock_held( $name );

	/**
	 * Filters how long (seconds) a per-user lock may live before another request can take it over.
	 *
	 * @since 1.6.8
	 *
	 * @param int $ttl     Lifetime in seconds. Default 30.
	 * @param int $user_id User ID.
	 */
	$ttl = (int) apply_filters( 'init_plugin_suite_user_engine_user_lock_ttl', 30, $user_id );

	if ( ! init_plugin_suite_user_engine_acquire_lock( $name, $wait, $ttl ) ) {
		return false;
	}

	if ( ! $was_held ) {
		wp_cache_delete( $user_id, 'user_meta' );
	}

	return true;
}

/**
 * Release the per-user lock.
 *
 * @since 1.6.8
 *
 * @param int $user_id User ID.
 * @return bool
 */
function init_plugin_suite_user_engine_unlock_user( $user_id ) {
	$user_id = absint( $user_id );
	return $user_id ? init_plugin_suite_user_engine_release_lock( 'user_' . $user_id ) : false;
}

/**
 * Standard error returned when a per-user lock could not be taken in time.
 *
 * @since 1.6.8
 *
 * @return WP_Error
 */
function init_plugin_suite_user_engine_busy_error() {
	return new WP_Error(
		'busy',
		__( 'Another request is being processed. Please try again in a moment.', 'init-user-engine' ),
		array( 'status' => 409 )
	);
}

// -----------------------------------------------------------------------------
// Atomic balance changes
// -----------------------------------------------------------------------------

/**
 * User meta key that stores a wallet.
 *
 * @since 1.6.8
 *
 * @param string $currency 'coin' or 'cash'.
 * @return string Empty string for an unknown currency.
 */
function init_plugin_suite_user_engine_wallet_meta_key( $currency ) {
	$keys = array(
		'coin' => 'iue_coin',
		'cash' => 'iue_cash',
	);

	return isset( $keys[ $currency ] ) ? $keys[ $currency ] : '';
}

/**
 * Find the usermeta row that stores a wallet, straight from the database (no cache).
 *
 * The lookup goes through the `user_id` index of the usermeta table, so it
 * reads only the rows of one user, however big the table is. A wallet
 * normally has one row; if old data left duplicates, the oldest row (lowest
 * umeta_id) is the wallet, the same row get_user_meta( $user_id, $key, true )
 * returns. The oldest row is picked here in PHP on purpose: with
 * `ORDER BY umeta_id LIMIT 1` MySQL/MariaDB may decide to walk the primary key
 * of the whole table instead of using the `user_id` index.
 *
 * @since 1.7.1
 * @access private
 *
 * @param int    $user_id  User ID.
 * @param string $meta_key Wallet meta key (see init_plugin_suite_user_engine_wallet_meta_key()).
 * @return array|null|false {
 *     Wallet row, null when the user has no wallet row yet, false on a database error.
 *
 *     @type int $id    umeta_id of the row.
 *     @type int $value Stored balance as an integer (legacy data may be negative).
 * }
 */
function init_plugin_suite_user_engine_wallet_find_row( $user_id, $meta_key ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The live row is needed for an atomic UPDATE by primary key.
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT umeta_id, meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s",
			absint( $user_id ),
			$meta_key
		),
		ARRAY_A
	);

	if ( '' !== $wpdb->last_error ) {
		return false;
	}

	if ( empty( $rows ) ) {
		return null;
	}

	$wallet = null;

	foreach ( $rows as $row ) {
		$id = (int) $row['umeta_id'];

		if ( null === $wallet || $id < $wallet['id'] ) {
			$wallet = array(
				'id'    => $id,
				'value' => (int) $row['meta_value'],
			);
		}
	}

	return $wallet;
}

/**
 * Read a balance straight from the database (no cache).
 *
 * @since 1.6.8
 * @since 1.7.1 Uses init_plugin_suite_user_engine_wallet_find_row().
 *
 * @param int    $user_id  User ID.
 * @param string $currency 'coin' or 'cash'.
 * @return int
 */
function init_plugin_suite_user_engine_wallet_read( $user_id, $currency ) {
	$meta_key = init_plugin_suite_user_engine_wallet_meta_key( $currency );

	if ( '' === $meta_key ) {
		return 0;
	}

	$row = init_plugin_suite_user_engine_wallet_find_row( $user_id, $meta_key );

	return $row ? max( 0, $row['value'] ) : 0;
}

/**
 * Atomically add to or take from a user's Coin/Cash balance.
 *
 * - $delta > 0: credit.
 * - $delta < 0 and $allow_partial = false: spend. Fails with `not_enough_{currency}`
 *   and changes nothing unless the whole amount is available at that instant.
 * - $delta < 0 and $allow_partial = true: deduct as much as possible, never below 0.
 *
 * How it works: the wallet row is first looked up through the `user_id` index,
 * then changed by one UPDATE that targets that row by primary key. The new
 * value is computed by the database (`meta_value + n`), never in PHP, and when
 * spending, the funds check is part of the same UPDATE's WHERE clause, so
 * concurrent requests can never overspend or overwrite each other. If the row
 * changed between the lookup and the UPDATE (deleted, or the funds were spent
 * by another request), the UPDATE changes nothing and the change is retried.
 *
 * Before 1.7.1 the row was selected inside the UPDATE with a subquery on the
 * same table. MySQL/MariaDB cannot use the primary key for that, so every
 * change scanned the whole usermeta table (about 0.3 s per change at 800k rows,
 * growing with the table).
 *
 * The legacy hooks `init_plugin_suite_user_engine_coin_changed` /
 * `..._cash_changed` still fire. No VIP bonus or amount filter is applied here.
 *
 * @since 1.6.8
 * @since 1.7.1 Updates the wallet row by primary key instead of a self-referencing subquery.
 *
 * @param int    $user_id       User ID.
 * @param string $currency      'coin' or 'cash'.
 * @param int    $delta         Amount to add (positive) or remove (negative).
 * @param bool   $allow_partial Optional. Clamp at 0 instead of failing. Default false.
 * @return array|WP_Error {
 *     @type int $old   Balance before the change.
 *     @type int $new   Balance after the change.
 *     @type int $delta Amount actually applied.
 * }
 */
function init_plugin_suite_user_engine_wallet_change( $user_id, $currency, $delta, $allow_partial = false ) {
	global $wpdb;

	$user_id  = absint( $user_id );
	$delta    = (int) $delta;
	$meta_key = init_plugin_suite_user_engine_wallet_meta_key( $currency );

	if ( ! $user_id || '' === $meta_key ) {
		return new WP_Error( 'invalid_wallet', __( 'Invalid wallet.', 'init-user-engine' ), array( 'status' => 400 ) );
	}

	if ( 0 === $delta ) {
		$balance = init_plugin_suite_user_engine_wallet_read( $user_id, $currency );

		return array(
			'old'   => $balance,
			'new'   => $balance,
			'delta' => 0,
		);
	}

	$amount   = abs( $delta );
	$is_spend = ( $delta < 0 && ! $allow_partial );
	$applied  = null;

	for ( $attempt = 0; $attempt < 3 && null === $applied; $attempt++ ) {
		$row = init_plugin_suite_user_engine_wallet_find_row( $user_id, $meta_key );

		if ( false === $row ) {
			return init_plugin_suite_user_engine_wallet_db_error();
		}

		if ( null === $row ) {
			if ( $delta < 0 ) {
				// Nothing to take from a wallet that does not exist yet.
				return $allow_partial
					? init_plugin_suite_user_engine_wallet_unchanged( $user_id, $currency )
					: init_plugin_suite_user_engine_wallet_insufficient_error( $currency );
			}

			// First credit ever for this wallet: create the row. "unique" keeps a second process from adding another.
			wp_cache_delete( $user_id, 'user_meta' );

			if ( add_user_meta( $user_id, $meta_key, (string) $amount, true ) ) {
				$applied = $delta;
			}

			// Otherwise another request created the row in the meantime: credit that row on the next attempt.
			continue;
		}

		if ( $is_spend && $row['value'] < $amount ) {
			// Fast fail. The UPDATE below checks the funds again, so this is never the only guard.
			return init_plugin_suite_user_engine_wallet_insufficient_error( $currency );
		}

		if ( $delta < 0 && $allow_partial && 0 === $row['value'] ) {
			return init_plugin_suite_user_engine_wallet_unchanged( $user_id, $currency );
		}

		if ( $delta > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic increment by primary key; the meta API cannot do it.
			$affected = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->usermeta} SET meta_value = CAST(meta_value AS SIGNED) + %d WHERE umeta_id = %d AND user_id = %d AND meta_key = %s",
					$amount,
					$row['id'],
					$user_id,
					$meta_key
				)
			);
		} elseif ( $allow_partial ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic decrement clamped at 0, by primary key.
			$affected = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->usermeta} SET meta_value = GREATEST( 0, CAST(meta_value AS SIGNED) - %d ) WHERE umeta_id = %d AND user_id = %d AND meta_key = %s",
					$amount,
					$row['id'],
					$user_id,
					$meta_key
				)
			);
		} else {
			// The funds check lives in the WHERE clause: the row only changes if the balance covers the amount.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic conditional decrement by primary key.
			$affected = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->usermeta} SET meta_value = CAST(meta_value AS SIGNED) - %d WHERE umeta_id = %d AND user_id = %d AND meta_key = %s AND CAST(meta_value AS SIGNED) >= %d",
					$amount,
					$row['id'],
					$user_id,
					$meta_key,
					$amount
				)
			);
		}

		if ( false === $affected ) {
			return init_plugin_suite_user_engine_wallet_db_error();
		}

		if ( $affected > 0 ) {
			$applied = ( $delta < 0 && $allow_partial ) ? -min( $amount, max( 0, $row['value'] ) ) : $delta;
		}

		// Nothing changed: the row was deleted or (spend) the funds were spent since the lookup. Look it up again.
	}

	if ( null === $applied ) {
		if ( $is_spend ) {
			// The database refused the funds check on every attempt.
			return init_plugin_suite_user_engine_wallet_insufficient_error( $currency );
		}

		if ( $delta < 0 ) {
			return init_plugin_suite_user_engine_wallet_unchanged( $user_id, $currency );
		}

		return init_plugin_suite_user_engine_wallet_db_error();
	}

	wp_cache_delete( $user_id, 'user_meta' );

	$new = init_plugin_suite_user_engine_wallet_read( $user_id, $currency );
	$old = max( 0, $new - $applied );

	if ( 'coin' === $currency ) {
		/** This action is documented in includes/coin.php */
		do_action( 'init_plugin_suite_user_engine_coin_changed', $user_id, $new, $old );
	} else {
		/** This action is documented in includes/cash.php */
		do_action( 'init_plugin_suite_user_engine_cash_changed', $user_id, $new, $old );
	}

	return array(
		'old'   => $old,
		'new'   => $new,
		'delta' => $applied,
	);
}

/**
 * Result of a wallet change that changed nothing.
 *
 * @since 1.7.1
 * @access private
 *
 * @param int    $user_id  User ID.
 * @param string $currency 'coin' or 'cash'.
 * @return array { @type int $old, @type int $new, @type int $delta }
 */
function init_plugin_suite_user_engine_wallet_unchanged( $user_id, $currency ) {
	$balance = init_plugin_suite_user_engine_wallet_read( $user_id, $currency );

	return array(
		'old'   => $balance,
		'new'   => $balance,
		'delta' => 0,
	);
}

/**
 * Error returned when the database could not change a balance.
 *
 * @since 1.7.1
 * @access private
 *
 * @return WP_Error
 */
function init_plugin_suite_user_engine_wallet_db_error() {
	return new WP_Error( 'wallet_db_error', __( 'Could not update the balance. Please try again.', 'init-user-engine' ), array( 'status' => 500 ) );
}

/**
 * "Not enough Coin/Cash" error with the admin-configured label.
 *
 * @since 1.6.8
 *
 * @param string $currency 'coin' or 'cash'.
 * @return WP_Error
 */
function init_plugin_suite_user_engine_wallet_insufficient_error( $currency ) {
	$label = 'cash' === $currency
		? init_plugin_suite_user_engine_get_cash_label()
		: init_plugin_suite_user_engine_get_coin_label();

	return new WP_Error(
		'cash' === $currency ? 'not_enough_cash' : 'not_enough_coin',
		/* translators: %s is the currency label (e.g., Coin, Cash). */
		sprintf( __( 'Not enough %s.', 'init-user-engine' ), $label ),
		array( 'status' => 400 )
	);
}

/**
 * Spend Coin atomically. Nothing is taken unless the whole amount is available.
 *
 * @since 1.6.8
 *
 * @param int $user_id User ID.
 * @param int $amount  Amount to spend (>= 0).
 * @return int|WP_Error New balance, or WP_Error `not_enough_coin`.
 */
function init_plugin_suite_user_engine_spend_coin( $user_id, $amount ) {
	$result = init_plugin_suite_user_engine_wallet_change( $user_id, 'coin', -absint( $amount ) );
	return is_wp_error( $result ) ? $result : $result['new'];
}

/**
 * Spend Cash atomically. Nothing is taken unless the whole amount is available.
 *
 * @since 1.6.8
 *
 * @param int $user_id User ID.
 * @param int $amount  Amount to spend (>= 0).
 * @return int|WP_Error New balance, or WP_Error `not_enough_cash`.
 */
function init_plugin_suite_user_engine_spend_cash( $user_id, $amount ) {
	$result = init_plugin_suite_user_engine_wallet_change( $user_id, 'cash', -absint( $amount ) );
	return is_wp_error( $result ) ? $result : $result['new'];
}

// -----------------------------------------------------------------------------
// Live balance sync
// -----------------------------------------------------------------------------

/**
 * Whether the current user's balance changed during this request.
 *
 * @since 1.6.9
 *
 * @param bool $touch Optional. Mark the balance as changed. Default false.
 * @return bool
 */
function init_plugin_suite_user_engine_balance_touched( $touch = false ) {
	static $touched = false;

	if ( $touch ) {
		$touched = true;
	}

	return $touched;
}

/**
 * Whether this request can carry the live balance headers (REST or admin-ajax only,
 * never a full page that a page cache could store and serve to someone else).
 *
 * @since 1.6.9
 *
 * @return bool
 */
function init_plugin_suite_user_engine_is_balance_sync_request() {
	$is_rest = defined( 'REST_REQUEST' ) && REST_REQUEST;

	/**
	 * Filters whether balance changes are reported to the browser in this request.
	 *
	 * @since 1.6.9
	 *
	 * @param bool $enabled True for REST and admin-ajax requests.
	 */
	return (bool) apply_filters( 'init_plugin_suite_user_engine_balance_sync_enabled', $is_rest || wp_doing_ajax() );
}

/**
 * Send the new balance of the signed-in user as a response header.
 *
 * Front-end scripts read X-IUE-Coin / X-IUE-Cash from every response and update
 * every balance on the page, whatever feature changed it. Sent right away so it
 * also works for handlers that end with wp_send_json(); REST responses get the
 * final values again in init_plugin_suite_user_engine_rest_balance_headers().
 *
 * @since 1.6.9
 *
 * @param int    $user_id  User whose balance changed.
 * @param string $currency 'coin' or 'cash'.
 * @param int    $value    New balance.
 * @return void
 */
function init_plugin_suite_user_engine_send_balance_header( $user_id, $currency, $value ) {
	$current = get_current_user_id();

	if ( ! $current || (int) $user_id !== $current || ! init_plugin_suite_user_engine_is_balance_sync_request() ) {
		return;
	}

	init_plugin_suite_user_engine_balance_touched( true );

	if ( headers_sent() ) {
		return;
	}

	header( ( 'cash' === $currency ? 'X-IUE-Cash' : 'X-IUE-Coin' ) . ': ' . max( 0, (int) $value ) );
}

add_action( 'init_plugin_suite_user_engine_coin_changed', 'init_plugin_suite_user_engine_sync_coin_header', 99, 2 );

/**
 * Report a Coin change to the browser.
 *
 * @since 1.6.9
 *
 * @param int $user_id User ID.
 * @param int $value   New balance.
 * @return void
 */
function init_plugin_suite_user_engine_sync_coin_header( $user_id, $value ) {
	init_plugin_suite_user_engine_send_balance_header( $user_id, 'coin', $value );
}

add_action( 'init_plugin_suite_user_engine_cash_changed', 'init_plugin_suite_user_engine_sync_cash_header', 99, 2 );

/**
 * Report a Cash change to the browser.
 *
 * @since 1.6.9
 *
 * @param int $user_id User ID.
 * @param int $value   New balance.
 * @return void
 */
function init_plugin_suite_user_engine_sync_cash_header( $user_id, $value ) {
	init_plugin_suite_user_engine_send_balance_header( $user_id, 'cash', $value );
}

add_filter( 'rest_post_dispatch', 'init_plugin_suite_user_engine_rest_balance_headers', 99, 1 );

/**
 * Attach both final balances to a REST response when the user's balance changed.
 *
 * Read from the database at the very end, so the values are right even if the
 * request changed the balance several times or rolled a change back.
 *
 * @since 1.6.9
 *
 * @param WP_HTTP_Response $response Response.
 * @return WP_HTTP_Response
 */
function init_plugin_suite_user_engine_rest_balance_headers( $response ) {
	if ( ! init_plugin_suite_user_engine_balance_touched() || ! ( $response instanceof WP_HTTP_Response ) ) {
		return $response;
	}

	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return $response;
	}

	$response->header( 'X-IUE-Coin', (string) init_plugin_suite_user_engine_wallet_read( $user_id, 'coin' ) );
	$response->header( 'X-IUE-Cash', (string) init_plugin_suite_user_engine_wallet_read( $user_id, 'cash' ) );

	return $response;
}

add_filter( 'rest_exposed_cors_headers', 'init_plugin_suite_user_engine_expose_balance_headers' );

/**
 * Let browsers read the balance headers on cross-origin REST calls too.
 *
 * @since 1.6.9
 *
 * @param string[] $headers Exposed headers.
 * @return string[]
 */
function init_plugin_suite_user_engine_expose_balance_headers( $headers ) {
	$headers[] = 'X-IUE-Coin';
	$headers[] = 'X-IUE-Cash';

	return $headers;
}

/**
 * Current balances of a user, read from the database.
 *
 * @since 1.6.9
 *
 * @param int $user_id User ID.
 * @return array { @type int $coin, @type int $cash }
 */
function init_plugin_suite_user_engine_get_balances( $user_id ) {
	return array(
		'coin' => init_plugin_suite_user_engine_wallet_read( $user_id, 'coin' ),
		'cash' => init_plugin_suite_user_engine_wallet_read( $user_id, 'cash' ),
	);
}
