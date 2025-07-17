<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Get current coin balance of a user
function init_plugin_suite_user_engine_get_coin( $user_id ) {
	return (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_coin', 0 );
}

// Set coin value for a user (clamped to >= 0)
function init_plugin_suite_user_engine_set_coin( $user_id, $value ) {
	$value = max( 0, (int) $value );
	init_plugin_suite_user_engine_update_meta( $user_id, 'iue_coin', $value );
}

// Add coin to user and return new total
function init_plugin_suite_user_engine_add_coin( $user_id, $amount ) {
	$amount = (int) $amount;

	// Bonus Coin nếu là VIP
	if ( init_plugin_suite_user_engine_is_vip( $user_id ) ) {
		$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
		$bonus   = absint( $options['vip_bonus_coin'] ?? 0 );

		if ( $bonus > 0 ) {
			$amount += (int) round( $amount * $bonus / 100 );
		}
	}

	$coin = init_plugin_suite_user_engine_get_coin( $user_id );
	$amount = apply_filters( 'init_plugin_suite_user_engine_calculated_coin_amount', $amount, $user_id );
	$coin += $amount;
	init_plugin_suite_user_engine_set_coin( $user_id, $coin );

	return $coin;
}
