<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================
// Migration Cron Runner (IUE)
// ==========================

add_action( 'init_plugin_suite_iue_migration_event', 'init_plugin_suite_iue_migration_runner' );

function init_plugin_suite_iue_migration_runner() {
    // LOCK
    if ( get_transient( 'iue_migration_lock' ) ) return;
    set_transient( 'iue_migration_lock', 1, 60 ); // Lock 1 phút là đủ

    init_plugin_suite_user_engine_maybe_migrate_logs();

    // Check còn data không
    $done_version = (int) get_option( 'iue_log_migration_done', 0 );

    if ( $done_version < INIT_PLUGIN_SUITE_IUE_LOG_MIGRATION_VERSION ) {
        // Schedule lại sau 5s
        wp_schedule_single_event( time() + 5, 'init_plugin_suite_iue_migration_event' );
    }

    delete_transient( 'iue_migration_lock' );
}

register_activation_hook( INIT_PLUGIN_SUITE_IUE_FILE, function () {
	$done_version = (int) get_option( 'iue_log_migration_done', 0 );

	if (
	    $done_version < INIT_PLUGIN_SUITE_IUE_LOG_MIGRATION_VERSION &&
	    ! wp_next_scheduled( 'init_plugin_suite_iue_migration_event' )
	) {
	    wp_schedule_single_event( time() + 5, 'init_plugin_suite_iue_migration_event' );
	}
} );
