<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Check if user is VIP
function init_plugin_suite_user_engine_is_vip( $user_id = 0 ) {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $user_id ) return false;

	$expire = (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_vip_expire', 0 );
	return $expire > current_time( 'timestamp' );
}

// Get VIP expiry timestamp
function init_plugin_suite_user_engine_get_vip_expiry( $user_id = 0 ) {
	$user_id = $user_id ?: get_current_user_id();
	return (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_vip_expire', 0 );
}

// Add VIP days to a user
function init_plugin_suite_user_engine_add_vip_days( $user_id, $days ) {
	$current_expiry = init_plugin_suite_user_engine_get_vip_expiry( $user_id );
	$new_expiry = max( current_time( 'timestamp' ), $current_expiry ) + ( $days * DAY_IN_SECONDS );
	init_plugin_suite_user_engine_update_meta( $user_id, 'iue_vip_expire', $new_expiry );
	return $new_expiry;
}

// Purchase a VIP package
function init_plugin_suite_user_engine_purchase_vip( $user_id, $package_id ) {
	$vip_days = [
		1 => 7,
		2 => 30,
		3 => 90,
		4 => 180,
		5 => 360,
		6 => 9999, // Lifetime
	];

	if ( ! isset( $vip_days[ $package_id ] ) ) {
		return new WP_Error( 'invalid_package', __( 'Invalid VIP package.', 'init-user-engine' ) );
	}

	$options   = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
	$price_key = 'vip_price_' . $package_id;
	$price     = absint( $options[ $price_key ] ?? 0 );

	if ( $price < 1 ) {
		return new WP_Error( 'vip_disabled', __( 'This VIP package is disabled.', 'init-user-engine' ) );
	}

	$current_coin = init_plugin_suite_user_engine_get_coin( $user_id );
	if ( $current_coin < $price ) {
		return new WP_Error( 'not_enough_coin', __( 'Not enough Coin.', 'init-user-engine' ) );
	}

	// Deduct coin
	init_plugin_suite_user_engine_set_coin( $user_id, $current_coin - $price );

	// Extend VIP
	init_plugin_suite_user_engine_add_vip_days( $user_id, $vip_days[ $package_id ] );

	// Log transaction
	init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $price, 'vip_package_' . $package_id, 'deduct' );

	// Save VIP log
	$log   = (array) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_vip_log', [] );
	$log[] = [
		'package' => $package_id,
		'days'    => $vip_days[ $package_id ],
		'coin'    => $price,
		'time'    => current_time( 'timestamp' ),
	];

	if ( count( $log ) > 50 ) {
		$log = array_slice( $log, -50 );
	}

	init_plugin_suite_user_engine_update_meta( $user_id, 'iue_vip_log', $log );

	$content = sprintf(
		// translators: %d is the VIP package ID.
		__( 'You have successfully purchased VIP for %s days. Enjoy your exclusive benefits!', 'init-user-engine' ),
		$vip_days[ $package_id ] >= 9999 ? __( 'lifetime', 'init-user-engine' ) : number_format_i18n( $vip_days[ $package_id ] )
	);

	init_plugin_suite_user_engine_send_inbox(
		$user_id,
		__( 'VIP Purchase Successful', 'init-user-engine' ),
		$content,
		'vip'
	);

	do_action(
	    'init_plugin_suite_user_engine_vip_purchased',
	    $user_id,
	    $package_id,
	    [
	        'days' => $vip_days[ $package_id ],
	        'coin' => $price,
	        'new_expiry' => init_plugin_suite_user_engine_get_vip_expiry( $user_id ),
	    ]
	);

	return true;
}

// Get user's VIP purchase log
function init_plugin_suite_user_engine_get_vip_log( $user_id = 0 ) {
	$user_id = $user_id ?: get_current_user_id();
	$log = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_vip_log', [] );
	return is_array( $log ) ? $log : [];
}

function init_plugin_suite_user_engine_api_purchase_vip( WP_REST_Request $request ) {
	$user_id    = get_current_user_id();
	$package_id = absint( $request->get_param( 'package_id' ) );

	if ( ! $user_id ) {
		return new WP_Error( 'unauthorized', __( 'You must be logged in to purchase VIP.', 'init-user-engine' ), [ 'status' => 401 ] );
	}

	if ( ! $package_id || $package_id < 1 || $package_id > 6 ) {
		return new WP_Error( 'invalid_package', __( 'Invalid VIP package selected.', 'init-user-engine' ), [ 'status' => 400 ] );
	}

	$result = init_plugin_suite_user_engine_purchase_vip( $user_id, $package_id );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$new_expiry = init_plugin_suite_user_engine_get_vip_expiry( $user_id );

	return rest_ensure_response( [
		'success'     => true,
		'new_expiry'  => $new_expiry,
		'is_vip'      => true,
		'package_id'  => $package_id,
	] );
}

/**
 * Get all ACTIVE VIP users (not expired)
 *
 * @param string $return 'ids' (default) or 'objects'
 * @return array
 */
function init_plugin_suite_user_engine_get_active_vip_users( $return = 'ids' ) {
    $now = current_time( 'timestamp' );

    $fields = ($return === 'objects') ? 'all' : 'ids';

    $query = new WP_User_Query( [
        'fields' => $fields,
        'number' => -1, // lấy hết
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        'meta_query' => [
            [
                'key'     => 'iue_vip_expire',
                'value'   => $now,
                'compare' => '>',
                'type'    => 'NUMERIC',
            ],
        ],
        'orderby' => 'meta_value_num',
        'order'   => 'DESC',
    ] );

    $results = $query->get_results();

    if ( $return === 'objects' ) {
        return is_array( $results ) ? $results : [];
    }

    // đảm bảo mảng số nguyên
    return array_map( 'intval', is_array( $results ) ? $results : [] );
}

/**
 * (Optional) Đếm nhanh số VIP còn hạn
 */
function init_plugin_suite_user_engine_count_active_vip_users() {
    $now = current_time( 'timestamp' );
    $q = new WP_User_Query( [
        'fields' => 'ID',
        'number' => 1,
        'count_total' => true,
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        'meta_query' => [
            [
                'key'     => 'iue_vip_expire',
                'value'   => $now,
                'compare' => '>',
                'type'    => 'NUMERIC',
            ],
        ],
    ] );
    return (int) $q->get_total();
}
