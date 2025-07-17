<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'init_user_engine', 'init_plugin_suite_user_engine_render_shortcode' );

// Shortcode handler: render avatar based on login state
function init_plugin_suite_user_engine_render_shortcode() {
	if ( is_user_logged_in() ) {
		return init_plugin_suite_user_engine_render_avatar_logged_in();
	} else {
		return init_plugin_suite_user_engine_render_avatar_guest();
	}
}

// Load template file (theme override supported)
function init_plugin_suite_user_engine_load_template( $filename, $args = [] ) {
	$template = locate_template( 'init-user-engine/' . $filename . '.php' );
	if ( ! $template ) {
		$template = INIT_PLUGIN_SUITE_IUE_TEMPLATES_PATH . $filename . '.php';
	}

	if ( ! file_exists( $template ) ) {
		return;
	}

	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args, EXTR_SKIP );
	}

	include $template;
}

// Render guest avatar template
function init_plugin_suite_user_engine_render_avatar_guest() {
	ob_start();
	init_plugin_suite_user_engine_load_template( 'guest-avatar' );
	return ob_get_clean();
}

// Render logged-in user avatar template
function init_plugin_suite_user_engine_render_avatar_logged_in() {
	ob_start();
	init_plugin_suite_user_engine_load_template( 'user-avatar' );
	return ob_get_clean();
}
