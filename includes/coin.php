<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Get current coin balance of a user
function init_plugin_suite_user_engine_get_coin( $user_id ) {
	return (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_coin', 0 );
}

/**
 * Overwrite a user's Coin balance (clamped to >= 0).
 *
 * This is an absolute write meant for admin tools. Relative changes must use
 * init_plugin_suite_user_engine_add_coin() / init_plugin_suite_user_engine_spend_coin(),
 * which are atomic; reading the balance and writing it back with this function
 * can overwrite a concurrent change.
 *
 * @param int $user_id User ID.
 * @param int $value   New balance.
 * @return void
 */
function init_plugin_suite_user_engine_set_coin( $user_id, $value ) {
	$value      = max( 0, (int) $value );
	$old_value  = (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_coin', 0 );

	// Update meta
	init_plugin_suite_user_engine_update_meta( $user_id, 'iue_coin', $value );

	// Fire action after coin value changes
	/**
	 * Fires after a user's coin balance has been updated.
	 *
	 * @param int $user_id   The ID of the user whose coin balance changed.
	 * @param int $value     The new coin balance value.
	 * @param int $old_value The previous coin balance value.
	 */
	do_action( 'init_plugin_suite_user_engine_coin_changed', $user_id, $value, $old_value );
}

/**
 * Add Coin to a user (atomic) and return the new balance.
 *
 * A negative $amount deducts as much as is available, never below 0. To charge
 * a price use init_plugin_suite_user_engine_spend_coin() instead, which fails
 * without touching the balance when the user cannot afford it.
 *
 * @since 1.6.8 The change is applied with one atomic UPDATE (no read-modify-write).
 *
 * @param int  $user_id         User ID.
 * @param int  $amount          Amount to add (negative to deduct).
 * @param bool $apply_vip_bonus Optional. Add the VIP bonus % to positive amounts. Default true.
 * @return int New balance.
 */
function init_plugin_suite_user_engine_add_coin( $user_id, $amount, $apply_vip_bonus = true ) {
	$amount = (int) $amount;

	// Chỉ cộng bonus nếu là VIP, amount > 0, và cho phép áp dụng bonus
	// (Đổi tiền Coin <-> Cash không được cộng thêm % thưởng VIP)
	if ( $apply_vip_bonus && $amount > 0 && init_plugin_suite_user_engine_is_vip( $user_id ) ) {
		$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
		$bonus   = absint( $options['vip_bonus_coin'] ?? 0 );

		if ( $bonus > 0 ) {
			$amount += (int) round( $amount * $bonus / 100 );
		}
	}

	$amount = (int) apply_filters( 'init_plugin_suite_user_engine_calculated_coin_amount', $amount, $user_id );

	$result = init_plugin_suite_user_engine_wallet_change( $user_id, 'coin', $amount, true );

	if ( is_wp_error( $result ) ) {
		return init_plugin_suite_user_engine_get_coin( $user_id );
	}

	return $result['new'];
}
