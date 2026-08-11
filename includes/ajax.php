<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_iue_user_search', function () {
	check_ajax_referer( 'iue_send_notice', '_ajax_nonce' );

	$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
	
	if ( strlen( $term ) < 2 ) {
		wp_send_json( [] );
	}

	$users = get_users( [
		'search'         => "*$term*",
		'search_columns' => [ 'user_login', 'user_email', 'display_name' ],
		'number'         => 10,
		'fields'         => [ 'ID', 'display_name', 'user_login' ]
	] );

	$data = array_map( function ( $u ) {
		return [
			'id'    => $u->ID,
			'name'  => $u->display_name,
			'login' => $u->user_login,
		];
	}, $users );

	wp_send_json( $data );
} );

// Test API: kiểm tra Cloudflare Turnstile Secret Key trực tiếp từ trang Settings, trước khi lưu
add_action( 'wp_ajax_iue_test_turnstile', function () {
	check_ajax_referer( 'iue_test_turnstile', '_ajax_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'You are not allowed to do this.', 'init-user-engine' ) ], 403 );
	}

	$secret_key = isset( $_POST['secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['secret_key'] ) ) : '';
	$site_key   = isset( $_POST['site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['site_key'] ) ) : '';

	if ( '' === $site_key || '' === $secret_key ) {
		wp_send_json_error( [ 'message' => __( 'Please enter both Site Key and Secret Key before testing.', 'init-user-engine' ) ] );
	}

	$result = init_plugin_suite_user_engine_test_turnstile_secret_key( $secret_key );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( [ 'message' => $result->get_error_message() ] );
	}

	wp_send_json_success( [
		'message' => __( 'Secret Key is valid — Cloudflare accepted the request. Site Key can only be fully confirmed once the widget actually renders in the browser (e.g. on the registration form).', 'init-user-engine' ),
	] );
} );
