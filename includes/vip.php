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

// Purchase a VIP package (supports coin / cash / both)
function init_plugin_suite_user_engine_purchase_vip( $user_id, $package_id, $currency = 'coin' ) {
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

	$options          = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
	$allowed_currency = $options['vip_payment_currency'] ?? 'coin';

	// Validate currency against global setting.
	if ( $allowed_currency === 'coin' && $currency !== 'coin' ) {
		return new WP_Error( 'invalid_currency', __( 'VIP can only be purchased with Coin.', 'init-user-engine' ), [ 'status' => 400 ] );
	}
	if ( $allowed_currency === 'cash' && $currency !== 'cash' ) {
		return new WP_Error( 'invalid_currency', __( 'VIP can only be purchased with Cash.', 'init-user-engine' ), [ 'status' => 400 ] );
	}
	if ( $allowed_currency === 'both' && ! in_array( $currency, [ 'coin', 'cash' ], true ) ) {
		return new WP_Error( 'invalid_currency', __( 'Invalid currency selected.', 'init-user-engine' ), [ 'status' => 400 ] );
	}

	// Get price.
	if ( $currency === 'cash' ) {
		$price_key = 'vip_cash_price_' . $package_id;
	} else {
		$price_key = 'vip_price_' . $package_id;
	}
	$price = absint( $options[ $price_key ] ?? 0 );

	if ( $price < 1 ) {
		return new WP_Error( 'vip_disabled', __( 'This VIP package is disabled.', 'init-user-engine' ) );
	}

	// Check balance & deduct.
	if ( $currency === 'cash' ) {
		$current_cash = init_plugin_suite_user_engine_get_cash( $user_id );
		if ( $current_cash < $price ) {
			return new WP_Error( 'not_enough_cash', __( 'Not enough Cash.', 'init-user-engine' ) );
		}
		init_plugin_suite_user_engine_set_cash( $user_id, $current_cash - $price );
		init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', $price, 'vip_package_' . $package_id, 'deduct' );
	} else {
		$current_coin = init_plugin_suite_user_engine_get_coin( $user_id );
		if ( $current_coin < $price ) {
			return new WP_Error( 'not_enough_coin', __( 'Not enough Coin.', 'init-user-engine' ) );
		}
		init_plugin_suite_user_engine_set_coin( $user_id, $current_coin - $price );
		init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $price, 'vip_package_' . $package_id, 'deduct' );
	}

	// Extend VIP.
	init_plugin_suite_user_engine_add_vip_days( $user_id, $vip_days[ $package_id ] );

	// Save VIP log.
	$log   = (array) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_vip_log', [] );
	$log[] = [
		'package'  => $package_id,
		'days'     => $vip_days[ $package_id ],
		'currency' => $currency,
		$currency  => $price,
		'time'     => current_time( 'timestamp' ),
	];

	if ( count( $log ) > 50 ) {
		$log = array_slice( $log, -50 );
	}

	init_plugin_suite_user_engine_update_meta( $user_id, 'iue_vip_log', $log );

	$currency_label = $currency === 'cash'
		? ( $options['label_cash'] ?? 'Cash' )
		: ( $options['label_coin'] ?? 'Coin' );

	$content = sprintf(
		// translators: %1$s = days/lifetime, %2$s = formatted price, %3$s = currency label.
		__( 'You have successfully purchased VIP for %1$s days using %2$s %3$s. Enjoy your exclusive benefits!', 'init-user-engine' ),
		$vip_days[ $package_id ] >= 9999 ? __( 'lifetime', 'init-user-engine' ) : number_format_i18n( $vip_days[ $package_id ] ),
		number_format_i18n( $price ),
		$currency_label
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
			'days'       => $vip_days[ $package_id ],
			'currency'   => $currency,
			$currency    => $price,
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
	$currency   = sanitize_text_field( $request->get_param( 'currency' ) ?: 'coin' );

	if ( ! $user_id ) {
		return new WP_Error( 'unauthorized', __( 'You must be logged in to purchase VIP.', 'init-user-engine' ), [ 'status' => 401 ] );
	}

	if ( ! $package_id || $package_id < 1 || $package_id > 6 ) {
		return new WP_Error( 'invalid_package', __( 'Invalid VIP package selected.', 'init-user-engine' ), [ 'status' => 400 ] );
	}

	$result = init_plugin_suite_user_engine_purchase_vip( $user_id, $package_id, $currency );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$new_expiry = init_plugin_suite_user_engine_get_vip_expiry( $user_id );

	return rest_ensure_response( [
		'success'     => true,
		'new_expiry'  => $new_expiry,
		'is_vip'      => true,
		'package_id'  => $package_id,
		'currency'    => $currency,
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

/**
 * Add VIP-related classes to body
 */
add_filter( 'body_class', 'init_plugin_suite_user_engine_add_vip_body_class' );
function init_plugin_suite_user_engine_add_vip_body_class( $classes ) {

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return $classes;
	}

	$expire = (int) init_plugin_suite_user_engine_get_meta(
		$user_id,
		'iue_vip_expire',
		0
	);

	if ( ! $expire ) {
		return $classes; // chưa từng VIP
	}

	$now = current_time( 'timestamp' );

	if ( $expire > $now ) {

		// Đang là VIP
		$classes[] = 'iue-vip';

		/**
		 * Threshold (seconds) để coi là "sắp hết hạn"
		 * Mặc định: 1 ngày
		 */
		$expire_soon_threshold = (int) apply_filters(
			'init_plugin_suite_user_engine_vip_expire_soon_threshold',
			DAY_IN_SECONDS,
			$user_id,
			$expire
		);

		if ( ( $expire - $now ) <= $expire_soon_threshold ) {
			$classes[] = 'iue-expire-soon';
		}

	} else {
		// Từng là VIP nhưng đã hết hạn
		$classes[] = 'iue-vip-expired';
	}

	/**
	 * Allow other developers to add/remove body classes related to IUE VIP
	 *
	 * @param array $classes
	 * @param int   $user_id
	 * @param int   $expire
	 */
	return apply_filters(
		'init_plugin_suite_user_engine_body_vip_classes',
		$classes,
		$user_id,
		$expire
	);
}
