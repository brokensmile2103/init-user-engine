<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Xử lí đăng ký
add_action( 'user_register', 'init_plugin_suite_user_engine_check_referral', 10, 1 );
function init_plugin_suite_user_engine_check_referral( $new_user_id ) {
    // Kiểm tra cookie
    if ( empty( $_COOKIE['iue_ref'] ) ) {
        return;
    }

    $ref_code = sanitize_text_field( wp_unslash( $_COOKIE['iue_ref'] ) );
    $referrer_id = init_plugin_suite_user_engine_decode_user_id( $ref_code );

    // Kiểm tra hợp lệ
    if ( ! $referrer_id || $referrer_id === $new_user_id || get_userdata( $referrer_id ) === false ) {
        return;
    }

    // Lưu meta referrer cho new user
    update_user_meta( $new_user_id, 'iue_referred_by', $referrer_id );

    // Cộng thưởng cho referrer
    $options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
    $ref_coin = intval( $options['ref_reward_coin'] ?? 100 );
    $ref_exp  = intval( $options['ref_reward_exp']  ?? 50 );
    $ref_cash = intval( $options['ref_reward_cash'] ?? 0 );

    if ( $ref_exp > 0 ) {
        init_plugin_suite_user_engine_add_exp( $referrer_id, $ref_exp );
        init_plugin_suite_user_engine_log_exp( $referrer_id, $ref_exp, 'referral', 'add' );
    }

    if ( $ref_coin > 0 ) {
        init_plugin_suite_user_engine_add_coin( $referrer_id, $ref_coin );
        init_plugin_suite_user_engine_log_transaction( $referrer_id, 'coin', $ref_coin, 'referral', 'add' );
    }

    if ( $ref_cash > 0 ) {
        init_plugin_suite_user_engine_add_cash( $referrer_id, $ref_cash );
        init_plugin_suite_user_engine_log_transaction( $referrer_id, 'cash', $ref_cash, 'referral', 'add' );
    }

    // Cộng thưởng cho người dùng mới
    $new_coin = intval( $options['ref_new_coin'] ?? 50 );
    $new_exp  = intval( $options['ref_new_exp']  ?? 20 );
    $new_cash = intval( $options['ref_new_cash'] ?? 0 );

    if ( $new_exp > 0 ) {
        init_plugin_suite_user_engine_add_exp( $new_user_id, $new_exp );
        init_plugin_suite_user_engine_log_exp( $new_user_id, $new_exp, 'referral_new', 'add' );
    }

    if ( $new_coin > 0 ) {
        init_plugin_suite_user_engine_add_coin( $new_user_id, $new_coin );
        init_plugin_suite_user_engine_log_transaction( $new_user_id, 'coin', $new_coin, 'referral_new', 'add' );
    }

    if ( $new_cash > 0 ) {
        init_plugin_suite_user_engine_add_cash( $new_user_id, $new_cash );
        init_plugin_suite_user_engine_log_transaction( $new_user_id, 'cash', $new_cash, 'referral_new', 'add' );
    }

    // Gửi thông báo cho người giới thiệu
    $new_user = get_userdata( $new_user_id );
    if ( $new_user ) {
        $content = sprintf(
            // translators: %d is the number of invited friends.
            __( 'Your friend %s has joined using your referral link.', 'init-user-engine' ),
            $new_user->user_login
        );

        init_plugin_suite_user_engine_send_inbox(
            $referrer_id,
            __( 'New Referral Signup', 'init-user-engine' ), // i18n
            $content,
            'referral'
        );
    }

    do_action(
        'init_plugin_suite_user_engine_referral_completed',
        $referrer_id,
        $new_user_id,
        [
            'referrer_rewards' => [
                'exp'  => $ref_exp,
                'coin' => $ref_coin,
                'cash' => $ref_cash,
            ],
            'new_user_rewards' => [
                'exp'  => $new_exp,
                'coin' => $new_coin,
                'cash' => $new_cash,
            ]
        ]
    );

    // Xóa cookie để tránh dùng lại
    setcookie( 'iue_ref', '', time() - YEAR_IN_SECONDS, '/' );
    unset( $_COOKIE['iue_ref'] );
}

// GET /referral-log – Lấy lịch sử giới thiệu
function init_plugin_suite_user_engine_api_get_referral_log( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return new WP_Error( 'unauthorized', __( 'Unauthorized', 'init-user-engine' ), [ 'status' => 401 ] );
    }

    global $wpdb;

    // Tìm các user có meta iue_referred_by = $user_id
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $user_ids = $wpdb->get_col( $wpdb->prepare(
        "
        SELECT user_id
        FROM {$wpdb->usermeta}
        WHERE meta_key = 'iue_referred_by'
        AND meta_value = %d
        ",
        $user_id
    ) );

    if ( empty( $user_ids ) ) {
        return rest_ensure_response( [
            'total' => 0,
            'data'  => [],
        ] );
    }

    // Lấy thông tin cơ bản của những user được mời
    $data = array_map( function( $ref_user_id ) {
        $user      = get_userdata( $ref_user_id );
        $timestamp = strtotime( $user->user_registered );

        return [
            'ID'        => $ref_user_id,
            'username'  => $user->user_login,
            'email'     => $user->user_email,
            'registered'=> wp_date( 'Y-m-d H:i', $timestamp ),
            'timestamp' => $timestamp,
        ];
    }, $user_ids );

    return rest_ensure_response( [
        'total' => count( $data ),
        'data'  => $data,
    ] );
}
