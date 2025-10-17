<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function init_plugin_suite_user_engine_render_topup_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'init-user-engine' ) );
    }

    // Load currency labels directly from options (no helper)
    $opts       = (array) get_option( constant( 'INIT_PLUGIN_SUITE_IUE_OPTION' ), [] );
    $coin_label = isset( $opts['label_coin'] ) ? trim( wp_strip_all_tags( (string) $opts['label_coin'] ) ) : '';
    $cash_label = isset( $opts['label_cash'] ) ? trim( wp_strip_all_tags( (string) $opts['label_cash'] ) ) : '';
    $coin_label = $coin_label !== '' ? $coin_label : 'Coin';
    $cash_label = $cash_label !== '' ? $cash_label : 'Cash';

    $success = false;
    $error   = '';

    if (
        isset( $_POST['iue_topup_nonce'] )
        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iue_topup_nonce'] ) ), 'iue_topup' )
    ) {
        // RAW ADJUST cho phép số âm, bỏ qua mọi bonus/VIP.
        $amount       = intval( wp_unslash( $_POST['iue_amount'] ?? 0 ) );
        $type         = sanitize_key( $_POST['iue_topup_type'] ?? 'coin' );
        $target_radio = sanitize_key( $_POST['iue_target'] ?? 'selected' ); // 'all' | 'vip' | 'selected'

        // Xác định danh sách user theo radio (KHÔNG back-compat checkboxes)
        if ( $target_radio === 'all' ) {
            $user_ids = get_users( [ 'fields' => 'ID' ] );
            $send_all = true;
            $send_vip = false;
        } elseif ( $target_radio === 'vip' ) {
            // YÊU CẦU hàm VIP có sẵn, không fallback
            $user_ids = (array) init_plugin_suite_user_engine_get_active_vip_users( 'ids' );
            $send_all = false;
            $send_vip = true;
        } else {
            $raw      = sanitize_text_field( wp_unslash( $_POST['iue_user_ids'] ?? '' ) );
            $user_ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
            $send_all = false;
            $send_vip = false;
        }

        // amount = 0; type sai; không có user => lỗi
        if ( 0 === $amount || empty( $user_ids ) || ! in_array( $type, [ 'coin', 'cash' ], true ) ) {
            $error = __( 'Please fill in all required fields correctly.', 'init-user-engine' );
        } else {
            // Gom inbox theo batch: $inbox_batches[ 'coin'|'cash' ][ 'add'|'deduct' ][ $applied ] = [ user_id, ... ]
            $inbox_batches = [
                'coin' => [ 'add' => [], 'deduct' => [] ],
                'cash' => [ 'add' => [], 'deduct' => [] ],
            ];

            foreach ( $user_ids as $user_id ) {
                if ( 'coin' === $type ) {
                    $before = (int) init_plugin_suite_user_engine_get_coin( $user_id );
                    $target = max( 0, $before + (int) $amount );
                    init_plugin_suite_user_engine_set_coin( $user_id, $target );

                    $delta = $target - $before;
                    if ( 0 !== $delta ) {
                        $applied = abs( $delta );
                        $change  = ( $delta < 0 ) ? 'deduct' : 'add';

                        init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $applied, 'topup_admin', $change );

                        if ( $applied > 0 ) {
                            if ( ! isset( $inbox_batches['coin'][ $change ][ $applied ] ) ) {
                                $inbox_batches['coin'][ $change ][ $applied ] = [];
                            }
                            $inbox_batches['coin'][ $change ][ $applied ][] = $user_id;
                        }
                    }
                } else {
                    $before = (int) init_plugin_suite_user_engine_get_cash( $user_id );
                    $target = max( 0, $before + (int) $amount );
                    init_plugin_suite_user_engine_set_cash( $user_id, $target );

                    $delta = $target - $before;
                    if ( 0 !== $delta ) {
                        $applied = abs( $delta );
                        $change  = ( $delta < 0 ) ? 'deduct' : 'add';

                        init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', $applied, 'topup_admin', $change );

                        if ( $applied > 0 ) {
                            if ( ! isset( $inbox_batches['cash'][ $change ][ $applied ] ) ) {
                                $inbox_batches['cash'][ $change ][ $applied ] = [];
                            }
                            $inbox_batches['cash'][ $change ][ $applied ][] = $user_id;
                        }
                    }
                }
            }

            // BẮN INBOX THEO LÔ (bulk)
            $send_bulk_for_type = function( $money_type, $label ) use ( $inbox_batches ) {
                foreach ( [ 'add', 'deduct' ] as $chg ) {
                    if ( empty( $inbox_batches[ $money_type ][ $chg ] ) ) continue;

                    foreach ( $inbox_batches[ $money_type ][ $chg ] as $applied => $uids ) {
                        if ( empty( $uids ) ) continue;

                        if ( 'deduct' === $chg ) {
                            // translators: %s = currency label (e.g., Coin or Cash)
                            $title   = sprintf( __( 'Admin Adjustment %s', 'init-user-engine' ), $label );
                            // translators: 1: amount, 2: currency label
                            $content = sprintf( __( '%1$d %2$s has been deducted by admin.', 'init-user-engine' ), (int) $applied, $label );
                            $icon    = 'warning';
                        } else {
                            // translators: %s = currency label (e.g., Coin or Cash)
                            $title   = sprintf( __( 'Admin Top-up %s', 'init-user-engine' ), $label );
                            // translators: 1: amount, 2: currency label
                            $content = sprintf( __( 'You have received %1$d %2$s from the admin.', 'init-user-engine' ), (int) $applied, $label );
                            $icon    = 'gift';
                        }

                        init_plugin_suite_user_engine_send_inbox_to_users_bulk(
                            $uids,
                            $title,
                            $content,
                            $icon,
                            [ 'topup_by_admin' => 1 ],
                            null,
                            'normal',
                            '',
                            0,
                            (int) apply_filters( 'init_user_engine_inbox_bulk_chunk_size', 500, $uids, $title, $money_type )
                        );
                    }
                }
            };

            $send_bulk_for_type( 'coin', $coin_label );
            $send_bulk_for_type( 'cash', $cash_label );

            // Ghi log chuỗi (CHUẨN HOÁ TARGET), KHÔNG kiểm tra function_exists
            $target_label = $send_all ? 'ALL' : ( $send_vip ? 'VIP' : '' );
            if ( $target_label === '' ) {
                $target_label = ( count( $user_ids ) === 1 )
                    ? ( 'uid:' . (int) $user_ids[0] )     // 1 user cụ thể
                    : ( 'user:' . count( $user_ids ) );   // nhiều user = số lượng
            }
            init_plugin_suite_user_engine_add_topup_log( $amount, $type, $target_label );

            $success = true;
        }
    }

    // Lấy log: YÊU CẦU helper có sẵn, KHÔNG fallback parse option
    $logs = (array) init_plugin_suite_user_engine_get_topup_logs();

    // Helper: lấy user từ target nếu là uid:{id} / user_id:{id}
    $iue_extract_user_from_target = static function( $target ) {
        if ( preg_match( '/^(?:uid|user_id):(\d+)$/', (string) $target, $m ) ) {
            $uid = (int) $m[1];
            if ( $uid > 0 ) {
                $u = get_userdata( $uid );
                if ( $u ) return $u;
            }
        }
        return null;
    };

    // Helper: render Target (gắn link khi là user cụ thể)
    $iue_render_target = static function( $target ) use ( $iue_extract_user_from_target ) {
        $u = $iue_extract_user_from_target( $target );
        if ( $u ) {
            $name  = $u->display_name ?: $u->user_login;
            $login = $u->user_login;
            $id    = (int) $u->ID;
            $url   = esc_url( admin_url( 'user-edit.php?user_id=' . $id ) );
            return sprintf(
                '<a href="%1$s">%2$s</a> <span style="color:#666;">(@%3$s, #%4$d)</span>',
                $url, esc_html( $name ), esc_html( $login ), $id
            );
        }
        // Mặc định: hiển thị dạng thân thiện (VIP/ALL/user:{count})
        return esc_html( init_plugin_suite_user_engine_pretty_target( $target ) );
    };

    ?>

    <div class="wrap iue-topup-wrapper">
        <h1><?php esc_html_e( 'Top-up Coin / Cash', 'init-user-engine' ); ?></h1>

        <?php if ( $success ) : ?>
            <div class="notice notice-success"><p><?php esc_html_e( 'Top-up successful.', 'init-user-engine' ); ?></p></div>
        <?php elseif ( $error ) : ?>
            <div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field( 'iue_topup', 'iue_topup_nonce' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="iue_amount"><?php esc_html_e( 'Amount', 'init-user-engine' ); ?></label></th>
                    <td>
                        <input type="number" name="iue_amount" id="iue_amount" step="1" required class="regular-text" placeholder="1000 / -1000">
                        <p class="description"><?php esc_html_e( 'Positive to add, negative to deduct. Deduction will not go below 0.', 'init-user-engine' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Top-up Type', 'init-user-engine' ); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="iue_topup_type" value="coin" checked>
                                <?php esc_html_e( 'Coin', 'init-user-engine' ); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="iue_topup_type" value="cash">
                                <?php esc_html_e( 'Cash', 'init-user-engine' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Recipients', 'init-user-engine' ); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="iue_target" value="selected" checked>
                                <?php esc_html_e( 'Selected users', 'init-user-engine' ); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="iue_target" value="vip">
                                <?php esc_html_e( 'Apply to active VIPs', 'init-user-engine' ); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="iue_target" value="all">
                                <?php esc_html_e( 'Apply to all members', 'init-user-engine' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'Choose who will receive this top-up.', 'init-user-engine' ); ?></p>
                        </fieldset>
                    </td>
                </tr>

                <tr id="iue-row-select-users">
                    <th scope="row"><?php esc_html_e( 'Select Users', 'init-user-engine' ); ?></th>
                    <td style="position: relative;">
                        <input type="text" id="iue_user_search" class="regular-text"
                            placeholder="<?php esc_attr_e( 'Search by username or display name...', 'init-user-engine' ); ?>">
                        <div id="iue_user_results" class="iue-user-results"></div>
                        <div id="iue_user_selected" class="iue-user-selected"></div>
                        <input type="hidden" name="iue_user_ids" id="iue_user_ids" value="">
                        <p class="description"><?php esc_html_e( 'Selected user IDs will appear here.', 'init-user-engine' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Top-up Now', 'init-user-engine' ) ); ?>
        </form>

        <!-- ====================
             LOG: Hiển thị dưới cùng (KHÔNG có cột User, gắn link vào Target)
        ===================== -->
        <hr style="margin:24px 0;">
        <h2><?php esc_html_e( 'Recent Top-up Logs', 'init-user-engine' ); ?></h2>

        <?php if ( empty( $logs ) ) : ?>
            <p><?php esc_html_e( 'No logs yet.', 'init-user-engine' ); ?></p>
        <?php else : ?>
            <table class="widefat striped" style="max-width:100%;overflow:auto;margin-bottom:5px;">
                <thead>
                    <tr>
                        <th style="width:80px;"><?php esc_html_e( 'Quantity', 'init-user-engine' ); ?></th>
                        <th style="width:80px;"><?php esc_html_e( 'Type', 'init-user-engine' ); ?></th>
                        <th><?php esc_html_e( 'Target', 'init-user-engine' ); ?></th>
                        <th style="width:180px;"><?php esc_html_e( 'Time', 'init-user-engine' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Mới nhất lên trước
                    $logs = array_reverse( $logs );
                    foreach ( $logs as $row ) :
                        $qty    = isset( $row['quantity'] ) ? (int) $row['quantity'] : 0;
                        $t      = isset( $row['type'] ) ? (string) $row['type'] : '';
                        $target = isset( $row['target'] ) ? (string) $row['target'] : '';
                        $time   = isset( $row['time'] ) ? (string) $row['time'] : '';
                    ?>
                        <tr>
                            <td><?php echo esc_html( $qty ); ?></td>
                            <td style="text-transform: capitalize;"><?php echo esc_html( $t ); ?></td>
                            <td><?php echo wp_kses_post( $iue_render_target( $target ) ); // Target có thể là link user ?></td>
                            <td><?php echo esc_html( $time ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="description"><?php esc_html_e( 'Showing up to the latest 100 entries.', 'init-user-engine' ); ?></p>
        <?php endif; ?>
    </div>
    <?php
}
