<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Optional helper to check if a meta key exists
function init_plugin_suite_user_engine_has_meta( $user_id, $key ) {
	return metadata_exists( 'user', $user_id, $key );
}

// Get user meta with default fallback
function init_plugin_suite_user_engine_get_meta( $user_id, $key, $default = '' ) {
	$value = get_user_meta( $user_id, $key, true );
	return $value === '' ? $default : $value;
}

// Update user meta safely
function init_plugin_suite_user_engine_update_meta( $user_id, $key, $value ) {
	return update_user_meta( $user_id, $key, $value );
}

// Delete user meta if exists
function init_plugin_suite_user_engine_delete_meta( $user_id, $key ) {
	return delete_user_meta( $user_id, $key );
}

// Check if the user has checked in today
function init_plugin_suite_user_engine_has_checked_in_today( $user_id ) {
	if ( ! $user_id ) {
		return false;
	}

	$last_checkin = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_last', '' );
	$today        = init_plugin_suite_user_engine_today();

	return ( $last_checkin === $today );
}