<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handle create/disable/delete VIP code (admin, mirrors redeem-codes-handler.php)
 */
add_action( 'admin_init', function () {

    // =============================
    // CREATE CODE
    // =============================
    if (
        isset( $_POST['iue_vip_code_nonce'] )
        && wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['iue_vip_code_nonce'] ) ),
            'iue_vip_code_create'
        )
    ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'init-user-engine' ) );
        }

        global $wpdb;

        $table = $wpdb->prefix . 'init_user_engine_vip_codes';

        // ===== sanitize inputs =====
        $code_raw  = isset( $_POST['iue_code'] ) ? sanitize_text_field( wp_unslash( $_POST['iue_code'] ) ) : '';
        $type      = sanitize_key( $_POST['iue_type'] ?? 'single' );
        $vip_days  = absint( $_POST['iue_vip_days'] ?? 0 );
        $max_uses  = intval( $_POST['iue_max_uses'] ?? 1 );
        $user_lock = intval( $_POST['iue_user_lock'] ?? 0 );

        // single batch quantity
        $qty = intval( $_POST['iue_single_qty'] ?? 1 );
        $qty = max( 1, min( 500, $qty ) );

        $now     = time();
        $user_id = get_current_user_id();

        /*
        =====================================
        ============ SINGLE MODE ============
        =====================================
        */
        if ( 'single' === $type ) {

            // ===== CASE 1: admin nhập 1 mã thủ công (qty = 1) → giữ nguyên =====
            if ( $code_raw !== '' && $qty === 1 ) {

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->insert(
                    $table,
                    [
                        'code'        => $code_raw, // EXACT, không random
                        'type'        => 'single',
                        'vip_days'    => $vip_days,
                        'max_uses'    => 1,
                        'user_lock'   => null,
                        'status'      => 'active',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                        'created_by'  => $user_id,
                    ]
                );

                wp_safe_redirect( admin_url( 'admin.php?page=init-user-engine-vip-codes' ) );
                exit;
            }

            // ===== CASE 2: batch generate (qty > 1) =====
            // Chỉ dùng prefix + random khi qty > 1
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query( 'START TRANSACTION' );

            for ( $i = 0; $i < $qty; $i++ ) {

                $suffix = wp_generate_password( 6, false, false );

                $final_code = $code_raw !== ''
                    ? $code_raw . '_' . $suffix
                    : wp_generate_password( 10, false, false );

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->insert(
                    $table,
                    [
                        'code'        => $final_code,
                        'type'        => 'single',
                        'vip_days'    => $vip_days,
                        'max_uses'    => 1,
                        'user_lock'   => null,
                        'status'      => 'active',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                        'created_by'  => $user_id,
                    ]
                );
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query( 'COMMIT' );

            wp_safe_redirect( admin_url( 'admin.php?page=init-user-engine-vip-codes' ) );
            exit;
        }

        /*
        =====================================
        ===== NORMAL (multi / locked) ========
        =====================================
        */

        if ( 'single' === $type || 'user_locked' === $type ) {
            $max_uses = 1;
        }

        // Tôn trọng custom code, chỉ random khi không nhập gì
        $code = $code_raw !== ''
            ? $code_raw
            : wp_generate_password( 10, false, false );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            $table,
            [
                'code'        => $code,
                'type'        => $type,
                'vip_days'    => $vip_days,
                'max_uses'    => $max_uses,
                'user_lock'   => $user_lock ?: null,
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
                'created_by'  => $user_id,
            ]
        );

        // ===== inbox cho user_locked =====
        if ( 'user_locked' === $type && $user_lock > 0 ) {

            $title = __( 'You received a VIP code!', 'init-user-engine' );

            $content = sprintf(
                /* translators: %s: VIP code string assigned to the user */
                __( 'You have been assigned a VIP code: %s. You can use it in the Redeem VIP section.', 'init-user-engine' ),
                $code
            );

            $meta = [
                'vip_code'   => (string) $code,
                'vip_days'   => (int) $vip_days,
                'created_by' => (int) $user_id,
            ];

            init_plugin_suite_user_engine_send_inbox(
                $user_lock,
                $title,
                $content,
                'system',
                $meta,
                null,
                'high',
            );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=init-user-engine-vip-codes' ) );
        exit;
    }

    /*
    =====================================
    ============ DISABLE ================
    =====================================
    */
    if ( isset( $_GET['vip_disable'], $_GET['_wpnonce'] ) ) {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'init-user-engine' ) );
        }

        $id    = absint( $_GET['vip_disable'] );
        $nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

        if ( wp_verify_nonce( $nonce, "iue_vip_code_disable_$id" ) ) {

            global $wpdb;
            $table = $wpdb->prefix . 'init_user_engine_vip_codes';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $table,
                [ 'status' => 'disabled', 'updated_at' => time() ],
                [ 'id' => $id ]
            );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=init-user-engine-vip-codes' ) );
        exit;
    }

    // =============================
    // DELETE CODE (unused only)
    // =============================
    if ( isset( $_GET['vip_delete'], $_GET['_wpnonce'] ) ) {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'init-user-engine' ) );
        }

        $id    = absint( $_GET['vip_delete'] );
        $nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

        if ( wp_verify_nonce( $nonce, "iue_vip_code_delete_$id" ) ) {

            global $wpdb;
            $table = $wpdb->prefix . 'init_user_engine_vip_codes';

            // chỉ xóa khi chưa dùng
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete(
                $table,
                [
                    'id'         => $id,
                    'used_count' => 0,
                ],
                [ '%d', '%d' ]
            );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=init-user-engine-vip-codes' ) );
        exit;
    }

} );

/**
 * REST: POST /redeem-vip-code
 * Body JSON: { "code": "ABC123" }
 * Yêu cầu: user phải đăng nhập (permission_callback đã check ở route)
 */
function init_plugin_suite_user_engine_api_redeem_vip_code( WP_REST_Request $request ) {
    global $wpdb;

    $user_id = get_current_user_id();
    $code    = isset( $request['code'] ) ? (string) $request['code'] : '';
    $code    = trim( wp_unslash( $code ) );

    if ( '' === $code ) {
        return [
            'success' => false,
            'message' => __( 'Please enter a VIP code.', 'init-user-engine' ),
        ];
    }

    // ===== Global VIP guards (disable purchase / disable stacking) =====
    // Kiểm tra TRƯỚC khi lock hàng để trả lỗi sớm, không tốn transaction.
    $activation_check = init_plugin_suite_user_engine_check_vip_activation_allowed( $user_id );
    if ( is_wp_error( $activation_check ) ) {
        return [
            'success' => false,
            'message' => $activation_check->get_error_message(),
        ];
    }

    $table = $wpdb->prefix . 'init_user_engine_vip_codes';
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
            'message' => __( 'Invalid VIP code.', 'init-user-engine' ),
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

    // ===== Re-check VIP activation guards INSIDE the transaction =====
    // Lock the user's VIP expiry meta row (if it exists) so two concurrent
    // redemptions by the same user (e.g. two different codes submitted at
    // once) can't both slip past the "disable stacking" check before either
    // one has written the new expiry back. This closes the race window that
    // the earlier pre-transaction check (fast-fail, for UX only) cannot.
    if ( init_plugin_suite_user_engine_is_vip_stacking_disabled() ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $locked_expire = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'iue_vip_expire' FOR UPDATE",
                $user_id
            )
        );
        if ( $locked_expire && (int) $locked_expire > $now ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query( 'ROLLBACK' );
            return [
                'success' => false,
                'message' => __( 'You already have an active VIP membership. Please wait until it expires before activating again.', 'init-user-engine' ),
            ];
        }
    }
    if ( init_plugin_suite_user_engine_is_vip_purchase_disabled() ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( 'ROLLBACK' );
        return [
            'success' => false,
            'message' => __( 'VIP activation is currently disabled.', 'init-user-engine' ),
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

    // ===== Cộng VIP days & log giao dịch (hàm nội bộ)
    $vip_days_added = max( 0, (int) $row->vip_days );
    $new_expiry     = init_plugin_suite_user_engine_get_vip_expiry( $user_id );

    if ( $vip_days_added > 0 ) {
        $new_expiry = init_plugin_suite_user_engine_add_vip_days( $user_id, $vip_days_added );

        // Ghi lại vào VIP log để hiển thị đồng bộ trong hồ sơ user (giống purchase_vip)
        $vip_log   = (array) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_vip_log', [] );
        $vip_log[] = [
            'package'  => 'vip_code',
            'days'     => $vip_days_added,
            'currency' => 'vip_code',
            'code'     => (string) $row->code,
            'time'     => $now,
        ];
        if ( count( $vip_log ) > 50 ) {
            $vip_log = array_slice( $vip_log, -50 );
        }
        init_plugin_suite_user_engine_update_meta( $user_id, 'iue_vip_log', $vip_log );
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
    $title = __( 'VIP Code Redeemed', 'init-user-engine' );

    $content = $vip_days_added > 0
        /* translators: %d: number of VIP days added */
        ? sprintf( __( 'You received %d day(s) of VIP membership.', 'init-user-engine' ), $vip_days_added )
        : __( 'Redeem successful!', 'init-user-engine' );

    init_plugin_suite_user_engine_send_inbox(
        $user_id,
        $title,
        $content,
        'system',
        [ 'vip_code' => (string) $row->code ],
        null,
        'normal',
        '',
        0
    );

    // ===== Trả về client
    return [
        'success'    => true,
        'message'    => __( 'VIP code redeemed successfully!', 'init-user-engine' ),
        'vip_days'   => (int) $vip_days_added,
        'new_expiry' => (int) $new_expiry,
        'is_vip'     => true,
    ];
}
