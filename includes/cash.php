<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Get current cash balance of a user
function init_plugin_suite_user_engine_get_cash( $user_id ) {
	return (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_cash', 0 );
}

// Set cash value for a user (auto-fix negative)
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

// Add cash to user and return new total
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

    $cash = init_plugin_suite_user_engine_get_cash( $user_id );
    $amount = apply_filters( 'init_plugin_suite_user_engine_calculated_cash_amount', $amount, $user_id );
    $cash += $amount;

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
            // translators: %s is the cash label (e.g., Cash, Kim cương).
            return new WP_Error( 'insufficient_funds', sprintf( __( 'Not enough %s to exchange.', 'init-user-engine' ), $cash_label ), [ 'status' => 400 ] );
        }

        // Calculate coins (avoid FP edge by epsilon)
        $coins_to_add = (int) floor( ($cash_amount * $rate) + 1e-6 );
        if ( $coins_to_add <= 0 ) {
            // translators: %1$s is the coin label, %2$s is the cash label (e.g., Coin, Cash).
            return new WP_Error( 'zero_result', sprintf( __( 'The exchange would result in 0 %1$s. Increase the %2$s amount.', 'init-user-engine' ), $coin_label, $cash_label ), [ 'status' => 400 ] );
        }

        // --- Apply updates (best-effort atomic) ---
        // Lưu ý: Đổi tiền (Cash <-> Coin) KHÔNG được cộng thêm % thưởng VIP,
        // nên mọi lệnh gọi add_cash()/add_coin() bên dưới đều truyền $apply_vip_bonus = false.

        // 1) Deduct cash
        $new_cash = init_plugin_suite_user_engine_add_cash( $user_id, -$cash_amount, false );
        if ( $new_cash === null || $new_cash === false ) {
            // translators: %s is the cash label (e.g., Cash, Kim cương).
            return new WP_Error( 'update_failed', sprintf( __( 'Could not deduct %s.', 'init-user-engine' ), $cash_label ), [ 'status' => 500 ] );
        }
        if ( (int) $new_cash < 0 ) {
            // Rollback & abort if somehow negative
            init_plugin_suite_user_engine_add_cash( $user_id, $cash_amount, false );
            return new WP_Error( 'race_condition', __( 'Balance changed. Please try again.', 'init-user-engine' ), [ 'status' => 409 ] );
        }

        // 2) Add coin (no VIP bonus on exchange)
        $new_coin = init_plugin_suite_user_engine_add_coin( $user_id, $coins_to_add, false );
        if ( $new_coin === null || $new_coin === false ) {
            // Rollback Cash if coin failed
            init_plugin_suite_user_engine_add_cash( $user_id, $cash_amount, false );
            // translators: %s is the coin label (e.g., Coin, Xu).
            return new WP_Error( 'update_failed', sprintf( __( 'Could not add %s.', 'init-user-engine' ), $coin_label ), [ 'status' => 500 ] );
        }

        // Logs (không cộng bonus VIP cho log của giao dịch đổi tiền)
        init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', -$cash_amount, 'exchange', 'deduct', false );
        init_plugin_suite_user_engine_log_transaction( $user_id, 'coin',  $coins_to_add, 'exchange', 'add', false );

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

    // --- Idempotency (10 minutes) ---
    if ( $idemp_key !== '' ) {
        $idem_store_key = 'iue_xchg_coin_idem_' . $user_id . '_' . hash( 'sha256', $idemp_key );
        if ( get_transient( $idem_store_key ) ) {
            return new WP_Error( 'duplicate_request', __( 'Duplicate exchange request detected.', 'init-user-engine' ), [ 'status' => 409 ] );
        }
        set_transient( $idem_store_key, 1, 10 * MINUTE_IN_SECONDS );
    }

    // --- Mutex lock (prevent concurrent double-spend) ---
    $lock_key = 'iue_xchg_coin_lock_' . $user_id;
    if ( get_transient( $lock_key ) ) {
        return new WP_Error( 'busy', __( 'Another exchange is in progress. Please wait a moment.', 'init-user-engine' ), [ 'status' => 409 ] );
    }
    set_transient( $lock_key, 1, 15 );

    try {
        // Read balances just-in-time under lock
        $current_coin = (int) init_plugin_suite_user_engine_get_coin( $user_id );
        $current_cash = (int) init_plugin_suite_user_engine_get_cash( $user_id );

        if ( $coin_amount > $current_coin ) {
            // translators: %s is the coin label (e.g., Coin, Xu).
            return new WP_Error( 'insufficient_funds', sprintf( __( 'Not enough %s to exchange.', 'init-user-engine' ), $coin_label ), [ 'status' => 400 ] );
        }

        // Calculate cash (avoid FP edge by epsilon)
        $cash_to_add = (int) floor( ( $coin_amount * $rate ) + 1e-6 );
        if ( $cash_to_add <= 0 ) {
            // translators: %1$s is the cash label, %2$s is the coin label (e.g., Cash, Coin).
            return new WP_Error( 'zero_result', sprintf( __( 'The exchange would result in 0 %1$s. Increase the %2$s amount.', 'init-user-engine' ), $cash_label, $coin_label ), [ 'status' => 400 ] );
        }

        // --- Apply updates (best-effort atomic) ---
        // Lưu ý: Đổi tiền (Coin <-> Cash) KHÔNG được cộng thêm % thưởng VIP,
        // nên mọi lệnh gọi add_cash()/add_coin() bên dưới đều truyền $apply_vip_bonus = false.

        // 1) Deduct coin
        $new_coin = init_plugin_suite_user_engine_add_coin( $user_id, -$coin_amount, false );
        if ( $new_coin === null || $new_coin === false ) {
            // translators: %s is the coin label (e.g., Coin, Xu).
            return new WP_Error( 'update_failed', sprintf( __( 'Could not deduct %s.', 'init-user-engine' ), $coin_label ), [ 'status' => 500 ] );
        }
        if ( (int) $new_coin < 0 ) {
            // Rollback & abort if somehow negative
            init_plugin_suite_user_engine_add_coin( $user_id, $coin_amount, false );
            return new WP_Error( 'race_condition', __( 'Balance changed. Please try again.', 'init-user-engine' ), [ 'status' => 409 ] );
        }

        // 2) Add cash (no VIP bonus on exchange)
        $new_cash = init_plugin_suite_user_engine_add_cash( $user_id, $cash_to_add, false );
        if ( $new_cash === null || $new_cash === false ) {
            // Rollback Coin if cash failed
            init_plugin_suite_user_engine_add_coin( $user_id, $coin_amount, false );
            // translators: %s is the cash label (e.g., Cash, Kim cương).
            return new WP_Error( 'update_failed', sprintf( __( 'Could not add %s.', 'init-user-engine' ), $cash_label ), [ 'status' => 500 ] );
        }

        // Logs (không cộng bonus VIP cho log của giao dịch đổi tiền)
        init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', -$coin_amount, 'exchange_reverse', 'deduct', false );
        init_plugin_suite_user_engine_log_transaction( $user_id, 'cash',  $cash_to_add, 'exchange_reverse', 'add', false );

        do_action( 'init_plugin_suite_user_engine_after_exchange_reverse', $user_id, $coin_amount, $cash_to_add, $rate );

        // Extend idem record
        if ( ! empty( $idem_store_key ) ) {
            set_transient( $idem_store_key, [
                'status'        => 'exchanged',
                'rate'          => $rate,
                'coin_spent'    => $coin_amount,
                'cash_received' => $cash_to_add,
                'balances'      => [ 'coin' => (int) $new_coin, 'cash' => (int) $new_cash ],
            ], 10 * MINUTE_IN_SECONDS );
        }

        return new WP_REST_Response( [
            'status'        => 'exchanged',
            'rate'          => $rate,
            'coin_spent'    => $coin_amount,
            'cash_received' => $cash_to_add,
            'balances'      => [
                'coin' => (int) $new_coin,
                'cash' => (int) $new_cash,
            ],
        ], 200 );

    } finally {
        // Always release lock
        delete_transient( $lock_key );
    }
}
