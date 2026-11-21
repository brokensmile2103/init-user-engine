<?php
/**
 * Security events.
 *
 * Init User Engine does not keep any security log itself. It only announces
 * dangerous refused requests through one action, so a theme or a security plugin
 * can record them:
 *
 *     do_action( 'init_plugin_suite_user_engine_security_event', $endpoint, $action, $status_code, $context );
 *
 * Only clear signs of probing are announced: a logged-in member calling an admin
 * action of the plugin (no permission or a forged nonce), or calling an endpoint
 * the interface never offers them. Ordinary refusals (not logged in, wrong input,
 * rate limits, expired pages of admins…) are not announced, so a log stays short
 * and real attacks stand out.
 *
 * @package Init_User_Engine
 * @since   1.7.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Announce a dangerous refused request.
 *
 * Nothing is announced for guests (they cannot reach these actions anyway) or
 * when nobody listens to the action.
 *
 * @since 1.7.9
 *
 * @param string $endpoint Server-defined name of the endpoint, e.g. "ajax/iue_bulk_jobs_run".
 * @param string $action   Short code of what happened, e.g. "no_permission".
 * @param array  $context  Optional. Server-side IDs that help, e.g. array( 'target_user_id' => 12 ).
 * @param int    $status   Optional. HTTP status. Default 403.
 * @return void
 */
function init_plugin_suite_user_engine_security_event( $endpoint, $action, array $context = array(), $status = 403 ) {
	if ( ! is_user_logged_in() || ! has_action( 'init_plugin_suite_user_engine_security_event' ) ) {
		return;
	}

	$clean = array();
	foreach ( $context as $key => $value ) {
		$clean[ sanitize_key( $key ) ] = absint( $value );
	}

	/**
	 * Fires when Init User Engine refuses a dangerous request.
	 *
	 * The plugin itself records nothing. All values are defined by the server: the
	 * endpoint and action are fixed strings and the context only holds IDs, never
	 * raw text sent by the client.
	 *
	 * @since 1.7.9
	 *
	 * @param string $endpoint    Endpoint, e.g. "ajax/iue_bulk_jobs_run", "admin-post/iue_remove_vip",
	 *                            "rest/inituser/v1/avatar".
	 * @param string $action      What happened, e.g. "no_permission", "invalid_nonce".
	 * @param int    $status_code HTTP status (403).
	 * @param array  $context     IDs, e.g. array( 'target_user_id' => 12 ). May be empty.
	 */
	do_action(
		'init_plugin_suite_user_engine_security_event',
		substr( (string) $endpoint, 0, 200 ),
		sanitize_key( $action ),
		(int) $status,
		$clean
	);
}

/**
 * Admin actions of the plugin (admin-ajax.php and admin-post.php) and the
 * capability each one needs.
 *
 * @since 1.7.9
 *
 * @return array<string, string> Action => capability.
 */
function init_plugin_suite_user_engine_security_admin_actions() {
	$actions = array(
		'ajax/iue_user_search'               => 'manage_options',
		'ajax/iue_test_turnstile'            => 'manage_options',
		'ajax/iue_bulk_jobs_status'          => 'manage_options',
		'ajax/iue_bulk_jobs_run'             => 'manage_options',
		'admin-post/iue_cancel_bulk_job'     => 'manage_options',
		'admin-post/iue_unlock_display_name' => 'manage_options',
		'admin-post/iue_cleanup_inbox_type'  => 'manage_options',
		'admin-post/iue_wb_flush_now'        => 'manage_options',
		'admin-post/iue_remove_vip'          => 'manage_options',
		'admin-post/iue_toggle_avatar_ban'   => 'manage_options',
		'admin-post/iue_block_user'          => 'edit_users',
	);

	/**
	 * Filters the admin actions watched for permission probing.
	 *
	 * @since 1.7.9
	 *
	 * @param array<string, string> $actions "ajax/<action>" or "admin-post/<action>" => capability.
	 */
	return (array) apply_filters( 'init_plugin_suite_user_engine_security_admin_actions', $actions );
}

/**
 * Endpoint name of the current admin-ajax / admin-post request, when it is one of
 * the plugin's admin actions.
 *
 * @since 1.7.9
 *
 * @return string Empty when the request is not one of them.
 */
function init_plugin_suite_user_engine_security_current_admin_action() {
	global $pagenow;

	if ( wp_doing_ajax() ) {
		$kind = 'ajax';
	} elseif ( 'admin-post.php' === $pagenow ) {
		$kind = 'admin-post';
	} else {
		return '';
	}

	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only compared with a fixed list.
	if ( '' === $action ) {
		return '';
	}

	$endpoint = $kind . '/' . $action;

	return array_key_exists( $endpoint, init_plugin_suite_user_engine_security_admin_actions() ) ? $endpoint : '';
}

add_action( 'admin_init', 'init_plugin_suite_user_engine_security_watch_admin_actions', 1 );

/**
 * A logged-in member without the needed capability calls an admin action of the
 * plugin. The handler still refuses the request as before; this only announces it.
 *
 * @since 1.7.9
 *
 * @return void
 */
function init_plugin_suite_user_engine_security_watch_admin_actions() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$endpoint = init_plugin_suite_user_engine_security_current_admin_action();
	if ( '' === $endpoint ) {
		return;
	}

	$actions = init_plugin_suite_user_engine_security_admin_actions();
	if ( current_user_can( $actions[ $endpoint ] ) ) {
		return;
	}

	// The block handler announces this one itself (it knows more).
	if ( 'admin-post/iue_block_user' === $endpoint ) {
		return;
	}

	$context = array();
	if ( isset( $_REQUEST['user_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$target = absint( wp_unslash( $_REQUEST['user_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $target && get_userdata( $target ) ) {
			$context['target_user_id'] = $target;
		}
	}

	init_plugin_suite_user_engine_security_event( $endpoint, 'no_permission', $context );
}

add_filter( 'rest_post_dispatch', 'init_plugin_suite_user_engine_security_watch_rest', 10, 3 );

/**
 * A logged-in member is refused by the permission check of a REST route of the
 * plugin (rest_forbidden). Business refusals ("too early", "VIP only"…) are not
 * announced.
 *
 * @since 1.7.9
 *
 * @param WP_HTTP_Response $response Response.
 * @param WP_REST_Server   $server   Server.
 * @param WP_REST_Request  $request  Request.
 * @return WP_HTTP_Response
 */
function init_plugin_suite_user_engine_security_watch_rest( $response, $server, $request ) {
	if ( ! $response instanceof WP_HTTP_Response || 403 !== (int) $response->get_status() || ! is_user_logged_in() ) {
		return $response;
	}

	$route = $request instanceof WP_REST_Request ? (string) $request->get_route() : '';
	if ( 0 !== strpos( $route, '/' . INIT_PLUGIN_SUITE_IUE_NAMESPACE . '/' ) ) {
		return $response;
	}

	$data = $response->get_data();
	if ( ! is_array( $data ) || ! isset( $data['code'] ) || 'rest_forbidden' !== $data['code'] ) {
		return $response;
	}

	// The registered route pattern, never the raw URL.
	init_plugin_suite_user_engine_security_event( 'rest' . init_plugin_suite_user_engine_security_route_name( $route ), 'no_permission' );

	return $response;
}

/**
 * Registered route of the plugin that matches a requested route, so the event
 * never carries text typed by the client.
 *
 * @since 1.7.9
 *
 * @param string $route Requested route.
 * @return string Registered route, or "/inituser/v1/*" when none matches.
 */
function init_plugin_suite_user_engine_security_route_name( $route ) {
	$prefix = '/' . INIT_PLUGIN_SUITE_IUE_NAMESPACE;
	$routes = rest_get_server()->get_routes( INIT_PLUGIN_SUITE_IUE_NAMESPACE );

	foreach ( array_keys( $routes ) as $pattern ) {
		if ( preg_match( '@^' . $pattern . '$@i', $route ) ) {
			return $pattern;
		}
	}

	return $prefix . '/*';
}
