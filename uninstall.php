<?php
/**
 * Uninstall for Init User Engine
 *
 * Only deletes plugin settings option.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Option key
// The main plugin file is not loaded during uninstall, so the constant is never
// defined here: fall back to the real option name.
$option_name = defined( 'INIT_PLUGIN_SUITE_IUE_OPTION' )
	? INIT_PLUGIN_SUITE_IUE_OPTION
	: 'init_plugin_suite_user_engine_settings';

// Single site
delete_option( $option_name );

// Delete persistent top-up logs
delete_option( 'init_plugin_suite_user_engine_topup_logs' );

// Delete leftover wallet lock rows (normally removed as soon as a request finishes).
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off cleanup on uninstall.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_iue_lock_' ) . '%'
	)
);
