<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Get current cash balance of a user
function init_plugin_suite_user_engine_get_cash( $user_id ) {
	return (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_cash', 0 );
}

// Set cash value for a user (auto-fix negative)
function init_plugin_suite_user_engine_set_cash( $user_id, $value ) {
	$value = max( 0, (int) $value );
	init_plugin_suite_user_engine_update_meta( $user_id, 'iue_cash', $value );
}

// Add cash to user and return new total
function init_plugin_suite_user_engine_add_cash( $user_id, $amount ) {
	$cash = init_plugin_suite_user_engine_get_cash( $user_id ) + (int) $amount;
	init_plugin_suite_user_engine_set_cash( $user_id, $cash );
	return $cash;
}
