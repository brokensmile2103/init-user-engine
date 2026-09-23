<?php
/**
 * Paid check-in streak recovery.
 *
 * Lets a member spend Coin to keep a check-in streak alive after missing a few
 * days. The feature is opt-in: it stays off until "Missed Days Allowed" is set
 * above 0 in Settings, so updating the plugin never changes how an existing
 * site behaves.
 *
 * @package Init_User_Engine
 * @since   1.6.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the maximum number of missed days a member may cover with Coin.
 *
 * @since 1.6.5
 *
 * @param int $user_id Optional. User the limit applies to (passed to the filter). Default 0.
 * @return int Number of days. 0 means the feature is disabled.
 */
function init_plugin_suite_user_engine_get_streak_restore_max_days( $user_id = 0 ) {
	$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, array() );
	$days    = isset( $options['checkin_restore_days'] ) ? absint( $options['checkin_restore_days'] ) : 0;

	/**
	 * Filters how many missed check-in days can be covered with Coin.
	 *
	 * Return 0 to turn streak recovery off for the given user.
	 *
	 * @since 1.6.5
	 *
	 * @param int $days    Maximum number of missed days. Defaults to the value set in Settings.
	 * @param int $user_id User ID.
	 */
	return max( 0, (int) apply_filters( 'init_plugin_suite_user_engine_streak_restore_max_days', $days, absint( $user_id ) ) );
}

/**
 * Get the Coin price of one missed day, as configured in Settings.
 *
 * @since 1.6.5
 *
 * @return int Coin per missed day. 0 means keeping the streak is free.
 */
function init_plugin_suite_user_engine_get_streak_restore_cost_per_day() {
	$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, array() );

	return isset( $options['checkin_restore_cost'] ) ? absint( $options['checkin_restore_cost'] ) : 100;
}

/**
 * Work out whether a member can keep a broken streak right now, and at what price.
 *
 * An offer exists only when the feature is enabled, the member has a streak, has
 * not checked in today, and has missed between 1 and the allowed number of whole
 * days since the last check-in. Otherwise the streak simply resets as before.
 *
 * @since 1.6.5
 *
 * @param int $user_id User ID.
 * @return array|null {
 *     Offer details, or null when there is nothing to offer.
 *
 *     @type int  $streak       Current streak that would be kept.
 *     @type int  $missed_days  Whole days missed since the last check-in.
 *     @type int  $max_days     Maximum missed days allowed.
 *     @type int  $cost_per_day Coin per missed day.
 *     @type int  $cost         Total Coin needed to keep the streak.
 *     @type int  $balance      The member's current Coin balance.
 *     @type bool $can_afford   Whether the balance covers the cost.
 * }
 */
function init_plugin_suite_user_engine_get_streak_restore_offer( $user_id ) {
	$user_id  = absint( $user_id );
	$max_days = init_plugin_suite_user_engine_get_streak_restore_max_days( $user_id );

	if ( ! $user_id || $max_days < 1 ) {
		return null;
	}

	$last   = (string) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_last', '' );
	$streak = (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_streak', 0 );

	if ( '' === $last || $streak < 1 ) {
		return null;
	}

	// Compare plain calendar dates (site timezone, as stored) at midnight UTC so a DST shift can never skew the count.
	$utc       = new DateTimeZone( 'UTC' );
	$last_day  = DateTimeImmutable::createFromFormat( '!Y-m-d', $last, $utc );
	$today_day = DateTimeImmutable::createFromFormat( '!Y-m-d', init_plugin_suite_user_engine_today(), $utc );

	if ( ! $last_day || ! $today_day ) {
		return null;
	}

	$diff = $last_day->diff( $today_day );

	if ( $diff->invert ) {
		return null;
	}

	// Dates 2 days apart mean exactly 1 missed day (yesterday); consecutive days mean none.
	$missed_days = (int) $diff->days - 1;

	if ( $missed_days < 1 || $missed_days > $max_days ) {
		return null;
	}

	$cost_per_day = init_plugin_suite_user_engine_get_streak_restore_cost_per_day();

	/**
	 * Filters the Coin cost of keeping a streak.
	 *
	 * @since 1.6.5
	 *
	 * @param int $cost        Total cost (missed days x cost per day).
	 * @param int $missed_days Whole days missed since the last check-in.
	 * @param int $user_id     User ID.
	 */
	$cost    = max( 0, (int) apply_filters( 'init_plugin_suite_user_engine_streak_restore_cost', $missed_days * $cost_per_day, $missed_days, $user_id ) );
	$balance = init_plugin_suite_user_engine_get_coin( $user_id );

	return array(
		'streak'       => $streak,
		'missed_days'  => $missed_days,
		'max_days'     => $max_days,
		'cost_per_day' => $cost_per_day,
		'cost'         => $cost,
		'balance'      => $balance,
		'can_afford'   => $balance >= $cost,
	);
}

/**
 * Charge a member for keeping their streak and record why in the transaction log.
 *
 * Must run inside the per-user check-in lock. Nothing is charged unless the
 * offer is still valid, the price matches what the member was shown, the balance
 * covers it, and the log entry could be written.
 *
 * @since 1.6.5
 *
 * @param int        $user_id       User ID.
 * @param array|null $offer         Result of init_plugin_suite_user_engine_get_streak_restore_offer().
 * @param mixed      $expected_cost Optional. Price the member agreed to. When given, it must match the current price.
 * @return array|WP_Error {
 *     Charge details on success, WP_Error on failure (nothing charged).
 *
 *     @type int $cost        Coin charged.
 *     @type int $missed_days Missed days that were covered.
 *     @type int $balance     Coin balance after the charge.
 * }
 */
function init_plugin_suite_user_engine_pay_streak_restore( $user_id, $offer, $expected_cost = null ) {
	if ( empty( $offer ) ) {
		return new WP_Error(
			'restore_unavailable',
			__( 'Your check-in streak can no longer be kept.', 'init-user-engine' ),
			array( 'status' => 409 )
		);
	}

	$cost        = (int) $offer['cost'];
	$missed_days = (int) $offer['missed_days'];

	if ( null !== $expected_cost && absint( $expected_cost ) !== $cost ) {
		return new WP_Error(
			'restore_changed',
			__( 'The cost to keep your streak has changed. Please try again.', 'init-user-engine' ),
			array( 'status' => 409 )
		);
	}

	$balance = init_plugin_suite_user_engine_get_coin( $user_id );

	if ( $cost > 0 ) {
		if ( $balance < $cost ) {
			return new WP_Error(
				'not_enough_coin',
				sprintf(
					/* translators: %s is the coin label (e.g., Coin, Xu). */
					__( 'Not enough %s.', 'init-user-engine' ),
					init_plugin_suite_user_engine_get_coin_label()
				),
				array( 'status' => 400 )
			);
		}

		init_plugin_suite_user_engine_set_coin( $user_id, $balance - $cost );

		$logged = init_plugin_suite_user_engine_log_transaction(
			$user_id,
			'coin',
			$cost,
			'streak_restore_' . $missed_days,
			'deduct',
			false
		);

		if ( ! $logged ) {
			// No log entry means no charge: hand the Coin back and stop.
			init_plugin_suite_user_engine_set_coin( $user_id, $balance );

			return new WP_Error(
				'restore_failed',
				__( 'Could not keep your streak. Please try again.', 'init-user-engine' ),
				array( 'status' => 500 )
			);
		}
	}

	return array(
		'cost'        => $cost,
		'missed_days' => $missed_days,
		'balance'     => init_plugin_suite_user_engine_get_coin( $user_id ),
	);
}
