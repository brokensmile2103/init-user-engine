<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Get current cash balance of a user
function init_plugin_suite_user_engine_get_cash( $user_id ) {
	return (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_cash', 0 );
}

/**
 * Overwrite a user's Cash balance (clamped to >= 0).
 *
 * This is an absolute write meant for admin tools. Relative changes must use
 * init_plugin_suite_user_engine_add_cash() / init_plugin_suite_user_engine_spend_cash(),
 * which are atomic; reading the balance and writing it back with this function
 * can overwrite a concurrent change.
 *
 * @param int $user_id User ID.
 * @param int $value   New balance.
 * @return void
 */
function init_plugin_suite_user_engine_set_cash( $user_id, $value ) {
    $value      = max( 0, (int) $value );
    $old_value  = (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_cash', 0 );

    // Update meta
    init_plugin_suite_user_engine_update_meta( $user_id, 'iue_cash', $value );

    // Fire action after cash value changes
    /**
     * Fires after a user's cash balance has been updated.
     *
     * @param int $user_id   The ID of the user whose cash balance changed.
     * @param int $value     The new cash balance value.
     * @param int $old_value The previous cash balance value.
     */
    do_action( 'init_plugin_suite_user_engine_cash_changed', $user_id, $value, $old_value );
}

/**
 * Add Cash to a user (atomic) and return the new balance.
 *
 * A negative $amount deducts as much as is available, never below 0. To charge
 * a price use init_plugin_suite_user_engine_spend_cash() instead, which fails
 * without touching the balance when the user cannot afford it.
 *
 * @since 1.6.8 The change is applied with one atomic UPDATE (no read-modify-write).
 *
 * @param int  $user_id         User ID.
 * @param int  $amount          Amount to add (negative to deduct).
 * @param bool $apply_vip_bonus Optional. Add the VIP bonus % to positive amounts. Default true.
 * @return int New balance.
 */
function init_plugin_suite_user_engine_add_cash( $user_id, $amount, $apply_vip_bonus = true ) {
    $amount = (int) $amount;

    // Chỉ cộng bonus nếu là VIP, amount > 0, và cho phép áp dụng bonus
    // (Đổi tiền Coin <-> Cash không được cộng thêm % thưởng VIP)
    if ( $apply_vip_bonus && $amount > 0 && init_plugin_suite_user_engine_is_vip( $user_id ) ) {
        $options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
        $bonus   = absint( $options['vip_bonus_cash'] ?? 0 );

        if ( $bonus > 0 ) {
            $amount += (int) round( $amount * $bonus / 100 );
        }
    }

    $amount = (int) apply_filters( 'init_plugin_suite_user_engine_calculated_cash_amount', $amount, $user_id );

    $result = init_plugin_suite_user_engine_wallet_change( $user_id, 'cash', $amount, true );

    if ( is_wp_error( $result ) ) {
        return init_plugin_suite_user_engine_get_cash( $user_id );
    }

    return $result['new'];
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

    // Admin-configured currency labels, used in all user-facing messages below.
    $coin_label = init_plugin_suite_user_engine_get_coin_label();
    $cash_label = init_plugin_suite_user_engine_get_cash_label();

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
        // translators: %s is the cash label (e.g., Cash, Kim cương).
        return new WP_Error( 'invalid_amount', sprintf( __( 'Please provide a valid %s amount greater than 0.', 'init-user-engine' ), $cash_label ), [ 'status' => 400 ] );
    }
    if ( $cash_amount < $min_cash ) {
        // translators: %1$d is number, %2$s is the cash label (e.g., Cash, Kim cương).
        return new WP_Error( 'below_min', sprintf( __( 'Minimum per exchange is %1$d %2$s.', 'init-user-engine' ), $min_cash, $cash_label ), [ 'status' => 400 ] );
    }
    if ( $max_cash > 0 && $cash_amount > $max_cash ) {
        // translators: %1$d is number, %2$s is the cash label (e.g., Cash, Kim cương).
        return new WP_Error( 'above_max', sprintf( __( 'Maximum per exchange is %1$d %2$s.', 'init-user-engine' ), $max_cash, $cash_label ), [ 'status' => 400 ] );
    }

    // --- Rate limit per user (5 requests / minute) ---
    $rl_key   = 'iue_xchg_rl_' . $user_id;
    $attempts = (int) get_transient( $rl_key );
    if ( $attempts >= 5 ) {
        return new WP_Error( 'rate_limited', __( 'Too many exchange attempts. Please try again later.', 'init-user-engine' ), [ 'status' => 429 ] );
    }
    set_transient( $rl_key, $attempts + 1, MINUTE_IN_SECONDS );

    // Calculate coins (avoid FP edge by epsilon)
    $coins_to_add = (int) floor( ( $cash_amount * $rate ) + 1e-6 );
    if ( $coins_to_add <= 0 ) {
        // translators: %1$s is the coin label, %2$s is the cash label (e.g., Coin, Cash).
        return new WP_Error( 'zero_result', sprintf( __( 'The exchange would result in 0 %1$s. Increase the %2$s amount.', 'init-user-engine' ), $coin_label, $cash_label ), [ 'status' => 400 ] );
    }

    // --- One wallet operation per user at a time (atomic lock, auto-released on fatal) ---
    if ( ! init_plugin_suite_user_engine_lock_user( $user_id, 5 ) ) {
        return init_plugin_suite_user_engine_busy_error();
    }

    try {
        // --- Idempotency (10 minutes), checked while holding the lock ---
        $idem_store_key = '';
        if ( '' !== $idemp_key ) {
            $idem_store_key = 'iue_xchg_idem_' . $user_id . '_' . hash( 'sha256', $idemp_key );
            if ( get_transient( $idem_store_key ) ) {
                return new WP_Error( 'duplicate_request', __( 'Duplicate exchange request detected.', 'init-user-engine' ), [ 'status' => 409 ] );
            }
        }

        // Lưu ý: Đổi tiền (Cash <-> Coin) KHÔNG được cộng thêm % thưởng VIP.

        // 1) Take the Cash. Atomic: nothing changes unless the whole amount is available right now.
        $spent = init_plugin_suite_user_engine_wallet_change( $user_id, 'cash', -$cash_amount );
        if ( is_wp_error( $spent ) ) {
            if ( 'not_enough_cash' === $spent->get_error_code() ) {
                // translators: %s is the cash label (e.g., Cash, Kim cương).
                return new WP_Error( 'insufficient_funds', sprintf( __( 'Not enough %s to exchange.', 'init-user-engine' ), $cash_label ), [ 'status' => 400 ] );
            }
            return $spent;
        }

        // 2) Give the Coin (no VIP bonus on exchange). add_coin() is atomic too.
        $new_coin = init_plugin_suite_user_engine_add_coin( $user_id, $coins_to_add, false );
        $new_cash = (int) $spent['new'];

        // Logs (không cộng bonus VIP cho log của giao dịch đổi tiền)
        init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', $cash_amount, 'exchange', 'deduct', false );
        init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $coins_to_add, 'exchange', 'add', false );

        do_action( 'init_plugin_suite_user_engine_after_exchange', $user_id, $cash_amount, $coins_to_add, $rate );

        $payload = [
            'status'        => 'exchanged',
            'rate'          => $rate,
            'cash_spent'    => $cash_amount,
            'coin_received' => $coins_to_add,
            'balances'      => [
                'cash' => (int) $new_cash,
                'coin' => (int) $new_coin,
            ],
        ];

        // Remember the key only after a successful exchange, so a failed attempt can be retried.
        if ( '' !== $idem_store_key ) {
            set_transient( $idem_store_key, $payload, 10 * MINUTE_IN_SECONDS );
        }

        return new WP_REST_Response( $payload, 200 );

    } finally {
        init_plugin_suite_user_engine_unlock_user( $user_id );
    }
}

/**
 * POST /exchange-reverse – Convert Coin -> Cash via configured rate.
 * Body JSON: { "coin": <int>, "idempotency_key": "<optional string>" }
 * Headers supported: Idempotency-Key: <optional string>
 */
function init_plugin_suite_user_engine_api_exchange_coin_to_cash( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return new WP_Error( 'unauthorized', __( 'Unauthorized', 'init-user-engine' ), [ 'status' => 401 ] );
    }

    // Admin-configured currency labels, used in all user-facing messages below.
    $coin_label = init_plugin_suite_user_engine_get_coin_label();
    $cash_label = init_plugin_suite_user_engine_get_cash_label();

    // --- Read settings ---
    $settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
    $rate     = isset( $settings['rate_cash_per_coin'] ) ? (float) $settings['rate_cash_per_coin'] : 0;

    if ( $rate <= 0 ) {
        return new WP_Error( 'exchange_disabled', __( 'Exchange is currently disabled.', 'init-user-engine' ), [ 'status' => 400 ] );
    }

    // Optional limits (filter-based)
    $min_coin = (int) apply_filters( 'init_plugin_suite_user_engine_exchange_min_coin', 1, $user_id, $settings );
    $max_coin = (int) apply_filters( 'init_plugin_suite_user_engine_exchange_max_coin', 0, $user_id, $settings ); // 0 = unlimited

    // Ensure sane bounds
    $min_coin = max( 1, absint( $min_coin ) );
    $max_coin = absint( $max_coin );

    // --- Read input ---
    $data        = $request->get_json_params();
    $coin_amount = isset( $data['coin'] ) ? absint( $data['coin'] ) : 0;

    // Idempotency key: header first, then body
    $idemp_key = '';
    $hdr_key   = $request->get_header( 'Idempotency-Key' );
    if ( is_string( $hdr_key ) && $hdr_key !== '' ) {
        $idemp_key = sanitize_text_field( $hdr_key );
    } elseif ( ! empty( $data['idempotency_key'] ) ) {
        $idemp_key = sanitize_text_field( (string) $data['idempotency_key'] );
    }

    if ( $coin_amount <= 0 ) {
        // translators: %s is the coin label (e.g., Coin, Xu).
        return new WP_Error( 'invalid_amount', sprintf( __( 'Please provide a valid %s amount greater than 0.', 'init-user-engine' ), $coin_label ), [ 'status' => 400 ] );
    }
    if ( $coin_amount < $min_coin ) {
        // translators: %1$d is number, %2$s is the coin label (e.g., Coin, Xu).
        return new WP_Error( 'below_min', sprintf( __( 'Minimum per exchange is %1$d %2$s.', 'init-user-engine' ), $min_coin, $coin_label ), [ 'status' => 400 ] );
    }
    if ( $max_coin > 0 && $coin_amount > $max_coin ) {
        // translators: %1$d is number, %2$s is the coin label (e.g., Coin, Xu).
        return new WP_Error( 'above_max', sprintf( __( 'Maximum per exchange is %1$d %2$s.', 'init-user-engine' ), $max_coin, $coin_label ), [ 'status' => 400 ] );
    }

    // --- Rate limit per user (5 requests / minute) ---
    $rl_key   = 'iue_xchg_coin_rl_' . $user_id;
    $attempts = (int) get_transient( $rl_key );
    if ( $attempts >= 5 ) {
        return new WP_Error( 'rate_limited', __( 'Too many exchange attempts. Please try again later.', 'init-user-engine' ), [ 'status' => 429 ] );
    }
    set_transient( $rl_key, $attempts + 1, MINUTE_IN_SECONDS );

    // Calculate cash (avoid FP edge by epsilon)
    $cash_to_add = (int) floor( ( $coin_amount * $rate ) + 1e-6 );
    if ( $cash_to_add <= 0 ) {
        // translators: %1$s is the cash label, %2$s is the coin label (e.g., Cash, Coin).
        return new WP_Error( 'zero_result', sprintf( __( 'The exchange would result in 0 %1$s. Increase the %2$s amount.', 'init-user-engine' ), $cash_label, $coin_label ), [ 'status' => 400 ] );
    }

    // --- One wallet operation per user at a time (atomic lock, auto-released on fatal) ---
    if ( ! init_plugin_suite_user_engine_lock_user( $user_id, 5 ) ) {
        return init_plugin_suite_user_engine_busy_error();
    }

    try {
        // --- Idempotency (10 minutes), checked while holding the lock ---
        $idem_store_key = '';
        if ( '' !== $idemp_key ) {
            $idem_store_key = 'iue_xchg_coin_idem_' . $user_id . '_' . hash( 'sha256', $idemp_key );
            if ( get_transient( $idem_store_key ) ) {
                return new WP_Error( 'duplicate_request', __( 'Duplicate exchange request detected.', 'init-user-engine' ), [ 'status' => 409 ] );
            }
        }

        // Lưu ý: Đổi tiền (Coin <-> Cash) KHÔNG được cộng thêm % thưởng VIP.

        // 1) Take the Coin. Atomic: nothing changes unless the whole amount is available right now.
        $spent = init_plugin_suite_user_engine_wallet_change( $user_id, 'coin', -$coin_amount );
        if ( is_wp_error( $spent ) ) {
            if ( 'not_enough_coin' === $spent->get_error_code() ) {
                // translators: %s is the coin label (e.g., Coin, Xu).
                return new WP_Error( 'insufficient_funds', sprintf( __( 'Not enough %s to exchange.', 'init-user-engine' ), $coin_label ), [ 'status' => 400 ] );
            }
            return $spent;
        }

        // 2) Give the Cash (no VIP bonus on exchange). add_cash() is atomic too.
        $new_cash = init_plugin_suite_user_engine_add_cash( $user_id, $cash_to_add, false );
        $new_coin = (int) $spent['new'];

        // Logs (không cộng bonus VIP cho log của giao dịch đổi tiền)
        init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $coin_amount, 'exchange_reverse', 'deduct', false );
        init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', $cash_to_add, 'exchange_reverse', 'add', false );

        do_action( 'init_plugin_suite_user_engine_after_exchange_reverse', $user_id, $coin_amount, $cash_to_add, $rate );

        $payload = [
            'status'        => 'exchanged',
            'rate'          => $rate,
            'coin_spent'    => $coin_amount,
            'cash_received' => $cash_to_add,
            'balances'      => [
                'coin' => (int) $new_coin,
                'cash' => (int) $new_cash,
            ],
        ];

        // Remember the key only after a successful exchange, so a failed attempt can be retried.
        if ( '' !== $idem_store_key ) {
            set_transient( $idem_store_key, $payload, 10 * MINUTE_IN_SECONDS );
        }

        return new WP_REST_Response( $payload, 200 );

    } finally {
        init_plugin_suite_user_engine_unlock_user( $user_id );
    }
}
