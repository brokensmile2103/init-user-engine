<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handle create redeem code
 */
add_action( 'admin_init', function () {

    // CREATE CODE
    if (
        isset( $_POST['iue_redeem_code_nonce'] )
        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iue_redeem_code_nonce'] ) ), 'iue_redeem_code_create' )
    ) {
        global $wpdb;
        $table = $wpdb->prefix . 'init_user_engine_redeem_codes';

        // Đọc input có isset + unslash + sanitize (không dùng trực tiếp $_POST trong ?:)
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $code_raw  = isset( $_POST['iue_code'] ) ? wp_unslash( $_POST['iue_code'] ) : '';
        $code      = sanitize_text_field( $code_raw !== '' ? $code_raw : wp_generate_password( 10, false, false ) );
        $type      = sanitize_key( isset( $_POST['iue_type'] ) ? wp_unslash( $_POST['iue_type'] ) : 'single' );
        $coin      = intval( isset( $_POST['iue_coin'] ) ? wp_unslash( $_POST['iue_coin'] ) : 0 );
        $cash      = intval( isset( $_POST['iue_cash'] ) ? wp_unslash( $_POST['iue_cash'] ) : 0 );
        $max_uses  = intval( isset( $_POST['iue_max_uses'] ) ? wp_unslash( $_POST['iue_max_uses'] ) : 1 );
        $user_lock = intval( isset( $_POST['iue_user_lock'] ) ? wp_unslash( $_POST['iue_user_lock'] ) : 0 );

        if ( 'single' === $type || 'user_locked' === $type ) {
            $max_uses = 1;
        }

        // Insert tạo code (transaction-less, data-changing → không cache)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert(
            $table,
            [
                'code'        => $code,
                'type'        => $type,
                'coin_amount' => $coin,
                'cash_amount' => $cash,
                'max_uses'    => $max_uses,
                'user_lock'   => $user_lock ?: null,
                'status'      => 'active',
                'created_at'  => time(),
                'updated_at'  => time(),
                'created_by'  => get_current_user_id(),
            ]
        );

        // Gửi inbox cho user_locked
        if ( 'user_locked' === $type && $user_lock > 0 ) {

            $title = __( 'You received a redeem code!', 'init-user-engine' );

            $content = sprintf(
                /* translators: %s is the redeem code assigned to the user */
                __( 'You have been assigned a redeem code: %s. You can use it in the Redeem section.', 'init-user-engine' ),
                $code
            );

            $meta = [
                'redeem_code' => (string) $code,
                'coin'        => (int) $coin,
                'cash'        => (int) $cash,
                'created_by'  => (int) get_current_user_id(),
            ];

            init_plugin_suite_user_engine_send_inbox(
                (int) $user_lock,
                $title,
                $content,
                'system',
                $meta,
                null,
                'high',
            );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=init-user-engine-redeem-codes' ) );
        exit;
    }

    // DISABLE CODE
    if ( isset( $_GET['disable'], $_GET['_wpnonce'] ) ) {

        $id    = absint( $_GET['disable'] );
        $nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

        if ( wp_verify_nonce( $nonce, "iue_redeem_disable_$id" ) ) {
            global $wpdb;
            $table = $wpdb->prefix . 'init_user_engine_redeem_codes';

            // Update trạng thái (data-changing → không cache)
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $table,
                [ 'status' => 'disabled', 'updated_at' => time() ],
                [ 'id' => $id ]
            );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=init-user-engine-redeem-codes' ) );
        exit;
    }

} );

/**
 * REST: POST /redeem-code
 * Body JSON: { "code": "ABC123" }
 * Yêu cầu: user phải đăng nhập (permission_callback đã check ở route)
 */
function init_plugin_suite_user_engine_api_redeem_code( WP_REST_Request $request ) {
    global $wpdb;

    // ===== Load labels từ options =====
    $opts       = (array) get_option( constant( 'INIT_PLUGIN_SUITE_IUE_OPTION' ), [] );
    $coin_label = isset( $opts['label_coin'] ) ? trim( wp_strip_all_tags( (string) $opts['label_coin'] ) ) : '';
    $cash_label = isset( $opts['label_cash'] ) ? trim( wp_strip_all_tags( (string) $opts['label_cash'] ) ) : '';
    $coin_label = '' !== $coin_label ? $coin_label : 'Coin';
    $cash_label = '' !== $cash_label ? $cash_label : 'Cash';

    $user_id = get_current_user_id();
    $code    = isset( $request['code'] ) ? (string) $request['code'] : '';
    $code    = trim( wp_unslash( $code ) );

    if ( '' === $code ) {
        return [
            'success' => false,
            'message' => __( 'Please enter a redeem code.', 'init-user-engine' ),
        ];
    }

    $table = $wpdb->prefix . 'init_user_engine_redeem_codes';
    $now   = time();

    // === Transaction để đảm bảo atomicity
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( 'START TRANSACTION' );

    // Khoá hàng để tránh race condition
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $row = $wpdb->get_row(
        $wpdb->prepare(
            // Tên bảng động từ $wpdb->prefix là an toàn, cần ignore interpolated rule
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT * FROM {$table} WHERE code = %s AND status = 'active' LIMIT 1 FOR UPDATE",
            $code
        )
    );

    if ( ! $row ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( 'ROLLBACK' );
        return [
            'success' => false,
            'message' => __( 'Invalid redeem code.', 'init-user-engine' ),
        ];
    }

    // Kiểm tra thời gian hiệu lực
    if ( ! empty( $row->valid_from ) && (int) $row->valid_from > 0 && $now < (int) $row->valid_from ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( 'ROLLBACK' );
        return [
            'success' => false,
            'message' => __( 'This code is not active yet.', 'init-user-engine' ),
        ];
    }
    if ( ! empty( $row->valid_to ) && (int) $row->valid_to > 0 && $now > (int) $row->valid_to ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( 'ROLLBACK' );
        return [
            'success' => false,
            'message' => __( 'This code has expired.', 'init-user-engine' ),
        ];
    }

    // Kiểm tra user lock
    if ( 'user_locked' === $row->type && (int) $row->user_lock !== (int) $user_id ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( 'ROLLBACK' );
        return [
            'success' => false,
            'message' => __( 'This code is assigned to another user.', 'init-user-engine' ),
        ];
    }

    // Parse metadata & CHẶN user dùng lại (bắt buộc)
    $metadata = json_decode( (string) $row->metadata, true );
    if ( ! is_array( $metadata ) ) {
        $metadata = [];
    }
    if ( ! isset( $metadata['used_by'] ) || ! is_array( $metadata['used_by'] ) ) {
        $metadata['used_by'] = [];
    }
    foreach ( $metadata['used_by'] as $redeem ) {
        if ( (int) ( $redeem['user_id'] ?? 0 ) === (int) $user_id ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query( 'ROLLBACK' );
            return [
                'success' => false,
                'message' => __( 'You have already used this code.', 'init-user-engine' ),
            ];
        }
    }

    // Kiểm tra lượt dùng tổng
    $used_count = (int) $row->used_count;
    $max_uses   = (int) $row->max_uses;
    if ( $max_uses > 0 && $used_count >= $max_uses ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( 'ROLLBACK' );
        return [
            'success' => false,
            'message' => __( 'This code has already been used up.', 'init-user-engine' ),
        ];
    }

    // ===== Tăng lượt dùng (optimistic) → data-changing, không cache
    $new_used = $used_count + 1;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $updated  = $wpdb->update(
        $table,
        [ 'used_count' => $new_used, 'updated_at' => $now ],
        [ 'id' => (int) $row->id, 'used_count' => $used_count ],
        [ '%d', '%d' ],
        [ '%d', '%d' ]
    );

    if ( 1 !== $updated ) {
        // Ai đó vừa tranh slot
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( 'ROLLBACK' );
        return [
            'success' => false,
            'message' => __( 'This code has already been used.', 'init-user-engine' ),
        ];
    }

    // ===== Cộng Coin/Cash & log giao dịch (hàm nội bộ)
    $coin_added = max( 0, (int) $row->coin_amount );
    $cash_added = max( 0, (int) $row->cash_amount );

    if ( $coin_added > 0 ) {
        init_plugin_suite_user_engine_add_coin( $user_id, $coin_added );
        init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $coin_added, 'redeem_code', 'add' );
    }
    if ( $cash_added > 0 ) {
        init_plugin_suite_user_engine_add_cash( $user_id, $cash_added );
        init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', $cash_added, 'redeem_code', 'add' );
    }

    // ===== Ghi lại người dùng đã redeem (bắt buộc)
    $current_user = wp_get_current_user();
    $metadata['used_by'][] = [
        'user_id'      => (int) $user_id,
        'used_at'      => (int) $now,
        'username'     => ( $current_user && $current_user->exists() ) ? (string) $current_user->user_login   : '',
        'display_name' => ( $current_user && $current_user->exists() ) ? (string) $current_user->display_name : '',
    ];

    // ===== Disable code nếu cần
    $should_disable = false;
    if ( 'multi' !== $row->type ) {
        $should_disable = true; // single, user_locked: dùng 1 lần là disable
    }
    if ( 'multi' === $row->type && $max_uses > 0 && $new_used >= $max_uses ) {
        $should_disable = true; // multi: full quota thì disable
    }

    // Build update data theo đúng thứ tự formats
    $update_data   = [
        'metadata'   => wp_json_encode( $metadata ),
        'updated_at' => $now,
    ];
    $update_format = [ '%s', '%d' ];

    if ( $should_disable ) {
        $update_data['status'] = 'disabled';
        $update_format[]       = '%s';
    }

    // Update DB (data-changing → không cache)
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        $table,
        $update_data,
        [ 'id' => (int) $row->id ],
        $update_format,
        [ '%d' ]
    );

    // Xong database → commit
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( 'COMMIT' );

    // ===== Gửi inbox (sau khi commit)
    $parts = [];
    if ( $coin_added > 0 ) {
        /* translators: 1: amount, 2: coin label */
        $parts[] = sprintf( __( '%1$d %2$s', 'init-user-engine' ), (int) $coin_added, $coin_label );
    }
    if ( $cash_added > 0 ) {
        /* translators: 1: amount, 2: cash label */
        $parts[] = sprintf( __( '%1$d %2$s', 'init-user-engine' ), (int) $cash_added, $cash_label );
    }

    $title = __( 'Redeem Code Success', 'init-user-engine' );

    $content = ! empty( $parts )
        /* translators: %s: amount */
        ? sprintf( __( 'You received: %s.', 'init-user-engine' ), implode( ' & ', $parts ) )
        : __( 'Redeem successful!', 'init-user-engine' );

    init_plugin_suite_user_engine_send_inbox(
        $user_id,
        $title,
        $content,
        'system',
        [ 'redeem_code' => (string) $row->code ],
        null,
        'normal',
        '',
        0
    );

    // ===== Trả về client
    return [
        'success' => true,
        'message' => __( 'Redeem successful!', 'init-user-engine' ),
        'coin'    => (int) $coin_added,
        'cash'    => (int) $cash_added,
    ];
}
