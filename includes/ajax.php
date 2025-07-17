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
