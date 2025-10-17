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
$option_name = defined( 'INIT_PLUGIN_SUITE_IUE_OPTION' )
	? INIT_PLUGIN_SUITE_IUE_OPTION
	: 'init_user_engine_options';

// Single site
delete_option( $option_name );

// Delete persistent top-up logs
delete_option( 'init_plugin_suite_user_engine_topup_logs' );
