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

// Delete background bulk jobs (Top-up / Send Notification to all members or VIPs).
delete_option( 'iue_bulk_jobs_active' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off cleanup on uninstall.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name = %s OR option_name LIKE %s",
		'iue_bulk_jobs',
		$wpdb->esc_like( 'iue_bulk_job_' ) . '%'
	)
);
wp_clear_scheduled_hook( 'init_plugin_suite_user_engine_run_bulk_jobs' );

// Write-behind buffer state (1.7.2). Buffered rows in the object cache expire by themselves.
delete_option( 'iue_wb_state' );
delete_option( 'iue_wb_active' );
delete_option( 'iue_wb_enabled_at' );
wp_clear_scheduled_hook( 'init_plugin_suite_user_engine_write_behind_flush' );

// Inbox: auto-delete of read messages and the Dashboard widget snapshot (1.7.4).
delete_option( 'iue_inbox_autodelete_state' );
delete_option( 'iue_inbox_autodelete_last' );
delete_option( 'iue_dashboard_stats_snapshot' );
delete_transient( 'iue_inbox_autodelete_lock' );
wp_clear_scheduled_hook( 'init_plugin_suite_user_engine_inbox_auto_delete' );
wp_clear_scheduled_hook( 'init_plugin_suite_user_engine_inbox_auto_delete_resume' );
wp_clear_scheduled_hook( 'init_plugin_suite_user_engine_refresh_dashboard_inbox_stats' );
