<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Get current cash balance of a user
function init_plugin_suite_user_engine_get_cash( $user_id ) {
	return (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_cash', 0 );
}

// Set cash value for a user (auto-fix negative)
function init_plugin_suite_user_engine_set_cash( $user_id, $value ) {
	$value = max( 0, (int) $value );
	init_plugin_suite_user_engine_update_meta( $user_id, 'iue_cash', $value );
}

// Add cash to user and return new total
function init_plugin_suite_user_engine_add_cash( $user_id, $amount ) {
	$cash = init_plugin_suite_user_engine_get_cash( $user_id ) + (int) $amount;
	init_plugin_suite_user_engine_set_cash( $user_id, $cash );
	return $cash;
}

/**
 * POST /exchange – Convert Cash -> Coin via configured rate.
 * Body JSON: { "cash": <int>, "idempotency_key": "<optional string>" }
 * Headers supported: Idempotency-Key: <optional string>
 */
function init_plugin_suite_user_engine_api_exchange_cash_to_coin( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return new WP_Error( 'unauthorized', __( 'Unauthorized', 'init-user-engine' ), [ 'status' => 401 ] );
    }

    // --- Read settings ---
    $settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
    $rate     = isset( $settings['rate_coin_per_cash'] ) ? (float) $settings['rate_coin_per_cash'] : 0;

    if ( $rate <= 0 ) {
        return new WP_Error( 'exchange_disabled', __( 'Exchange is currently disabled.', 'init-user-engine' ), [ 'status' => 400 ] );
    }

    // Optional limits (filter-based)
	$min_cash = (int) apply_filters( 'init_plugin_suite_user_engine_exchange_min_cash', 1, $user_id, $settings );
	$max_cash = (int) apply_filters( 'init_plugin_suite_user_engine_exchange_max_cash', 0, $user_id, $settings ); // 0 = unlimited

	// Ensure sane bounds
	$min_cash = max( 1, absint( $min_cash ) );
	$max_cash = absint( $max_cash );

    // --- Read input ---
    $data        = $request->get_json_params();
    $cash_amount = isset( $data['cash'] ) ? absint( $data['cash'] ) : 0;

    // Idempotency key: header first, then body
    $idemp_key = '';
    $hdr_key   = $request->get_header( 'Idempotency-Key' );
    if ( is_string( $hdr_key ) && $hdr_key !== '' ) {
        $idemp_key = sanitize_text_field( $hdr_key );
    } elseif ( ! empty( $data['idempotency_key'] ) ) {
        $idemp_key = sanitize_text_field( (string) $data['idempotency_key'] );
    }

    if ( $cash_amount <= 0 ) {
        return new WP_Error( 'invalid_amount', __( 'Please provide a valid Cash amount greater than 0.', 'init-user-engine' ), [ 'status' => 400 ] );
    }
    if ( $cash_amount < $min_cash ) {
        // translators: %d is number
        return new WP_Error( 'below_min', sprintf( __( 'Minimum per exchange is %d Cash.', 'init-user-engine' ), $min_cash ), [ 'status' => 400 ] );
    }
    if ( $max_cash > 0 && $cash_amount > $max_cash ) {
        // translators: %d is number
        return new WP_Error( 'above_max', sprintf( __( 'Maximum per exchange is %d Cash.', 'init-user-engine' ), $max_cash ), [ 'status' => 400 ] );
    }

    // --- Rate limit per user (5 requests / minute) ---
    $rl_key   = 'iue_xchg_rl_' . $user_id;
    $attempts = (int) get_transient( $rl_key );
    if ( $attempts >= 5 ) {
        return new WP_Error( 'rate_limited', __( 'Too many exchange attempts. Please try again later.', 'init-user-engine' ), [ 'status' => 429 ] );
    }
    set_transient( $rl_key, $attempts + 1, MINUTE_IN_SECONDS );

    // --- Idempotency (10 minutes) ---
    if ( $idemp_key !== '' ) {
        $idem_store_key = 'iue_xchg_idem_' . $user_id . '_' . hash( 'sha256', $idemp_key );
        if ( get_transient( $idem_store_key ) ) {
            return new WP_Error( 'duplicate_request', __( 'Duplicate exchange request detected.', 'init-user-engine' ), [ 'status' => 409 ] );
        }
        // mark seen; will be extended on success response as well
        set_transient( $idem_store_key, 1, 10 * MINUTE_IN_SECONDS );
    }

    // --- Mutex lock (prevent concurrent double-spend) ---
    $lock_key = 'iue_xchg_lock_' . $user_id;
    if ( get_transient( $lock_key ) ) {
        return new WP_Error( 'busy', __( 'Another exchange is in progress. Please wait a moment.', 'init-user-engine' ), [ 'status' => 409 ] );
    }
    // hold lock for a short window; auto-expires in 15s in case of fatal
    set_transient( $lock_key, 1, 15 );

    try {
        // Read balances just-in-time under lock
        $current_cash = (int) init_plugin_suite_user_engine_get_cash( $user_id );
        $current_coin = (int) init_plugin_suite_user_engine_get_coin( $user_id );

        if ( $cash_amount > $current_cash ) {
            return new WP_Error( 'insufficient_funds', __( 'Not enough Cash to exchange.', 'init-user-engine' ), [ 'status' => 400 ] );
        }

        // Calculate coins (avoid FP edge by epsilon)
        $coins_to_add = (int) floor( ($cash_amount * $rate) + 1e-6 );
        if ( $coins_to_add <= 0 ) {
            return new WP_Error( 'zero_result', __( 'The exchange would result in 0 Coin. Increase the Cash amount.', 'init-user-engine' ), [ 'status' => 400 ] );
        }

        // --- Apply updates (best-effort atomic) ---
        // 1) Deduct cash
        $new_cash = init_plugin_suite_user_engine_add_cash( $user_id, -$cash_amount );
        if ( $new_cash === null || $new_cash === false ) {
            return new WP_Error( 'update_failed', __( 'Could not deduct Cash.', 'init-user-engine' ), [ 'status' => 500 ] );
        }
        if ( (int) $new_cash < 0 ) {
            // Rollback & abort if somehow negative
            init_plugin_suite_user_engine_add_cash( $user_id, $cash_amount );
            return new WP_Error( 'race_condition', __( 'Balance changed. Please try again.', 'init-user-engine' ), [ 'status' => 409 ] );
        }

        // 2) Add coin
        $new_coin = init_plugin_suite_user_engine_add_coin( $user_id, $coins_to_add );
        if ( $new_coin === null || $new_coin === false ) {
            // Rollback Cash if coin failed
            init_plugin_suite_user_engine_add_cash( $user_id, $cash_amount );
            return new WP_Error( 'update_failed', __( 'Could not add Coin.', 'init-user-engine' ), [ 'status' => 500 ] );
        }

        // Logs
        init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', -$cash_amount, 'exchange', 'deduct' );
        init_plugin_suite_user_engine_log_transaction( $user_id, 'coin',  $coins_to_add, 'exchange', 'add' );

        do_action( 'init_plugin_suite_user_engine_after_exchange', $user_id, $cash_amount, $coins_to_add, $rate );

        // Extend idem record with a small body so clients can safely retry (optional)
        if ( ! empty( $idem_store_key ) ) {
            set_transient( $idem_store_key, [
                'status'        => 'exchanged',
                'rate'          => $rate,
                'cash_spent'    => $cash_amount,
                'coin_received' => $coins_to_add,
                'balances'      => [ 'cash' => (int) $new_cash, 'coin' => (int) $new_coin ],
            ], 10 * MINUTE_IN_SECONDS );
        }

        return new WP_REST_Response( [
            'status'        => 'exchanged',
            'rate'          => $rate,
            'cash_spent'    => $cash_amount,
            'coin_received' => $coins_to_add,
            'balances'      => [
                'cash' => (int) $new_cash,
                'coin' => (int) $new_coin,
            ],
        ], 200 );

    } finally {
        // Always release lock
        delete_transient( $lock_key );
    }
}
