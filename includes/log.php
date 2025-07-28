<?php
if ( ! defined( 'ABSPATH' ) ) exit;

	// Log a transaction (coin, cash) with type/source/change
	function init_plugin_suite_user_engine_log_transaction( $user_id, $type, $amount, $source, $change = 'add' ) {
		if ( ! in_array( $type, [ 'coin', 'cash' ], true ) ) {
			return false;
		}

		if ( ! in_array( $change, [ 'add', 'deduct' ], true ) ) {
			return false;
		}

		$log = (array) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_coin_cash_log', [] );

		$is_vip = init_plugin_suite_user_engine_is_vip( $user_id );

		$log[] = [
			'type'      => $type,
			'amount'    => absint( $amount ),
			'change'    => $change,
			'source'    => $source,
			'time'      => current_time( 'Y-m-d H:i:s' ),
			'vip_bonus' => $is_vip,
		];

		if ( count( $log ) > 100 ) {
			$log = array_slice( $log, -100 );
		}

		init_plugin_suite_user_engine_update_meta( $user_id, 'iue_coin_cash_log', $log );

		do_action( 'init_plugin_suite_user_engine_transaction_logged', $user_id, end( $log ) );

		return true;
	}

	// Get user's transaction log
	function init_plugin_suite_user_engine_get_transaction_log( $user_id ) {
		$log = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_coin_cash_log', [] );
		return is_array( $log ) ? $log : [];
	}

	// Format a log entry into readable message
	function init_plugin_suite_user_engine_format_log_message( $entry ) {
		$source = $entry['source'] ?? 'unknown';
		$type   = $entry['type'] ?? '';
		$amount = absint( $entry['amount'] ?? 0 );

		switch ( $source ) {
			case 'daily_login':
				return __( 'Daily login bonus', 'init-user-engine' );
			case 'user_register':
				return __( 'Welcome bonus for registration', 'init-user-engine' );
			case 'update_profile':
				return __( 'Profile updated', 'init-user-engine' );
			case ( preg_match( '/^level_up_(\d+)$/', $source, $m ) ? true : false ):
				// translators: %d is the new level number
				return sprintf( __( 'Level up to %d', 'init-user-engine' ), $m[1] );
			case ( preg_match( '/^milestone_(\d+)$/', $source, $m ) ? true : false ):
				// translators: %d is the number of days in the check-in streak
				return sprintf( __( 'Check-in streak reached %d days', 'init-user-engine' ), $m[1] );
			case 'checkin':
				return __( 'Daily check-in reward', 'init-user-engine' );
			case 'reward':
				return __( 'Bonus reward after being online', 'init-user-engine' );
			case 'referral':
				return __( 'Referral reward for inviting a friend', 'init-user-engine' );
			case 'referral_new':
				return __( 'Welcome reward for signing up via referral', 'init-user-engine' );
			case 'woo_order':
				return __( 'Reward from WooCommerce order', 'init-user-engine' );
			case 'comment_post':
				return __( 'Comment posted', 'init-user-engine' );
			case 'publish_post':
				return __( 'First time post published', 'init-user-engine' );
			case 'unlock_reward':
				return __( 'Chapter unlock reward', 'init-user-engine' );
			default:
				return ucfirst( str_replace( '_', ' ', $source ) );
		}
	}

// Log EXP separately
function init_plugin_suite_user_engine_log_exp( $user_id, $amount, $source = '', $change = 'add' ) {
	if ( ! in_array( $change, [ 'add', 'deduct' ], true ) ) {
		return false;
	}

	$log = (array) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_exp_log', [] );

	$is_vip = init_plugin_suite_user_engine_is_vip( $user_id );

	$log[] = [
		'amount'    => absint( $amount ),
		'change'    => $change,
		'source'    => $source,
		'time'      => current_time( 'Y-m-d H:i:s' ),
		'vip_bonus' => $is_vip,
	];

	if ( count( $log ) > 100 ) {
		$log = array_slice( $log, -100 );
	}

	init_plugin_suite_user_engine_update_meta( $user_id, 'iue_exp_log', $log );

	do_action( 'init_plugin_suite_user_engine_exp_logged', $user_id, end( $log ) );

	return true;
}

function init_plugin_suite_user_engine_get_exp_log( $user_id ) {
	$log = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_exp_log', [] );
	return is_array( $log ) ? $log : [];
}

function init_plugin_suite_user_engine_format_exp_log( $entry ) {
	$source = $entry['source'] ?? 'unknown';

	switch ( $source ) {
		case 'daily_login':
			return __( 'EXP from daily login', 'init-user-engine' );
		case 'user_register':
			return __( 'Welcome EXP for registration', 'init-user-engine' );
		case 'update_profile':
			return __( 'EXP for profile update', 'init-user-engine' );
		case ( preg_match( '/^level_up_(\d+)$/', $source, $m ) ? true : false ):
			// translators: %d is the new level number
			return sprintf( __( 'EXP reset after level %d up', 'init-user-engine' ), $m[1] );
		case ( preg_match( '/^milestone_(\d+)$/', $source, $m ) ? true : false ):
			// translators: %d is the number of days in the milestone
			return sprintf( __( 'EXP for %d-day streak milestone', 'init-user-engine' ), $m[1] );
		case 'reward':
			return __( 'EXP reward after staying online', 'init-user-engine' );
		case 'referral':
			return __( 'EXP gained for inviting a friend', 'init-user-engine' );
		case 'referral_new':
			return __( 'EXP gained from referral signup', 'init-user-engine' );
		case 'woo_order':
			return __( 'EXP from WooCommerce order', 'init-user-engine' );
		case 'comment_post':
			return __( 'EXP from posting a comment', 'init-user-engine' );
		case 'publish_post':
			return __( 'EXP from publishing a post', 'init-user-engine' );
		default:
			return ucfirst( str_replace( '_', ' ', $source ) );
	}
}

// REST: transaction history
function init_plugin_suite_user_engine_api_get_transactions( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
	}

	$log_all = init_plugin_suite_user_engine_get_transaction_log( $user_id ) ?: [];

	$page        = max( 1, (int) $request->get_param( 'page' ) );
	$per_page    = max( 1, min( 50, (int) $request->get_param( 'per_page' ) ) );
	$total       = count( $log_all );
	$total_pages = ceil($total / $per_page);
	$offset      = ( $page - 1 ) * $per_page;

	$log_page = array_slice( array_reverse( $log_all ), $offset, $per_page );

	$data = array_values( array_map( function( $entry ) {
		if ( ! is_array( $entry ) ) return null;

		return [
			'type'    => strtoupper( $entry['type'] ?? 'UNKNOWN' ),
			'amount'  => (int) ( $entry['amount'] ?? 0 ),
			'change'  => ( $entry['change'] === 'deduct' ) ? '-' : '+',
			'source'  => $entry['source'] ?? 'unknown',
			'message' => init_plugin_suite_user_engine_format_log_message( $entry ),
			'time'    => $entry['time'] ?? current_time( 'Y-m-d H:i:s' ),
		];
	}, array_filter( $log_page, 'is_array' ) ) );

	return rest_ensure_response( [
		'page'        => $page,
		'per_page'    => $per_page,
		'total'       => $total,
		'total_pages' => $total_pages,
		'data'        => $data,
	] );
}

// REST: EXP history
function init_plugin_suite_user_engine_api_get_exp_log( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
	}

	$log_all = init_plugin_suite_user_engine_get_exp_log( $user_id ) ?: [];

	$page        = max( 1, (int) $request->get_param( 'page' ) );
	$per_page    = max( 1, min( 50, (int) $request->get_param( 'per_page' ) ) );
	$total       = count( $log_all );
	$total_pages = ceil( $total / $per_page );
	$offset      = ( $page - 1 ) * $per_page;

	$log_page = array_slice( array_reverse( $log_all ), $offset, $per_page );

	$data = array_values( array_map( function( $entry ) {
		if ( ! is_array( $entry ) ) return null;

		return [
			'amount'  => absint( $entry['amount'] ?? 0 ),
			'change'  => ( $entry['change'] === 'deduct' ) ? '-' : '+',
			'source'  => $entry['source'] ?? 'unknown',
			'message' => init_plugin_suite_user_engine_format_exp_log( $entry ),
			'time'    => $entry['time'] ?? current_time( 'Y-m-d H:i:s' ),
		];
	}, array_filter( $log_page, 'is_array' ) ) );

	return rest_ensure_response( [
		'page'        => $page,
		'per_page'    => $per_page,
		'total'       => $total,
		'total_pages' => $total_pages,
		'data'        => $data,
	] );
}

// REST: daily task
function init_plugin_suite_user_engine_api_get_daily_tasks( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
	}

	$today = init_plugin_suite_user_engine_today();
	$log   = init_plugin_suite_user_engine_get_transaction_log( $user_id );

	$tasks = [
		[
			'key'     => 'checkin',
			'title'   => __( 'Check in today', 'init-user-engine' ),
			'reward'  => [ 'type' => 'coin', 'amount' => 10 ],
			'check'   => function( $user_id ) use ( $today ) {
				$last = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_last', '' );
				return $last === $today;
			},
		],
		[
			'key'     => 'online_reward',
			'title'   => __( 'Stay active today', 'init-user-engine' ),
			'reward'  => [ 'type' => 'coin', 'amount' => 100 ],
			'check'   => function( $user_id ) use ( $today ) {
				$last     = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_last', '' );
				$rewarded = (bool) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_rewarded', false );
				return ( $last === $today && $rewarded );
			},
		],
	];

	// Gộp reward theo từng source (tổng cộng 1 task duy nhất)
	$task_log_sources = [
		'update_profile' => __( 'Update your profile today', 'init-user-engine' ),
		'comment_post'   => __( 'Post a comment today', 'init-user-engine' ),
		'referral'       => __( 'Invite a friend', 'init-user-engine' ),
		'publish_post'   => __( 'Publish your first post', 'init-user-engine' ),
	];

	foreach ( $task_log_sources as $source => $label ) {
		$total_amount = 0;
		$type         = null;

		foreach ( $log as $e ) {
			if ( $e['source'] !== $source ) continue;
			if ( $e['change'] !== 'add' ) continue;

			if ( in_array( $source, [ 'update_profile', 'comment_post' ] ) ) {
				if ( substr( $e['time'], 0, 10 ) !== $today ) continue;
			}

			$total_amount += absint( $e['amount'] ?? 0 );
			$type = $e['type'] ?? $type;
		}

		if ( $total_amount > 0 && $type ) {
			$tasks[] = [
				'key'    => $source,
				'title'  => $label,
				'reward' => [
					'type'   => $type,
					'amount' => $total_amount,
				],
				'check' => fn() => true,
			];
		}
	}

	$tasks = apply_filters( 'init_plugin_suite_user_engine_daily_tasks', $tasks, $user_id );

	$output = [];

	foreach ( $tasks as $task ) {
		$completed = is_callable( $task['check'] ) ? call_user_func( $task['check'], $user_id ) : false;

		$output[] = [
			'title'     => $task['title'],
			'completed' => (bool) $completed,
			'reward'    => $task['reward'],
		];
	}

	return rest_ensure_response( $output );
}
