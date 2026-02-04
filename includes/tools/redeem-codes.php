<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function init_plugin_suite_user_engine_render_redeem_codes_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'init-user-engine' ) );
    }

    global $wpdb;
    $table_codes = $wpdb->prefix . 'init_user_engine_redeem_codes';
    $wp_users    = $wpdb->users;

    // ===== Pagination cho danh sách codes =====
    $per_page = 20;
    // Đọc GET chỉ để hiển thị/paginate, không thay đổi dữ liệu
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $page     = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $offset   = ( $page - 1 ) * $per_page;

    /**
     * Tổng số dòng — thêm cache ngắn hạn để tránh cảnh báo NoCaching.
     * Cache group: 'iue', key gồm trang bảng để khi dữ liệu thay đổi vẫn chỉ caching rất ngắn.
     */
    $cache_key_total = 'iue_rc_total';
    $total = wp_cache_get( $cache_key_total, 'iue' );
    if ( false === $total ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_codes}" );
        wp_cache_set( $cache_key_total, $total, 'iue', 60 ); // 60s
    }

    // Lấy danh sách code
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $codes = $wpdb->get_results(
        $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- dynamic table name từ $wpdb->prefix an toàn
            "SELECT * FROM {$table_codes} ORDER BY id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );

    $total_pages = ( $per_page > 0 ) ? (int) ceil( $total / $per_page ) : 1;

    // ===== Xem lịch sử usage của 1 code (từ metadata) =====
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $view_code_id = isset( $_GET['view'] ) ? max( 0, intval( $_GET['view'] ) ) : 0;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $view_nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $usage_page    = isset( $_GET['upaged'] ) ? max( 1, intval( $_GET['upaged'] ) ) : 1;
    $usage_perpage = 10;
    $usage_offset  = ( $usage_page - 1 ) * $usage_perpage;

    $usage_rows  = [];
    $usage_total = 0;
    $code_row    = null;

    if ( $view_code_id > 0 && wp_verify_nonce( $view_nonce, 'iue_redeem_view_' . $view_code_id ) ) {
        // Lấy code để đọc metadata
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $code_row = $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- dynamic table name từ $wpdb->prefix an toàn
                "SELECT * FROM {$table_codes} WHERE id = %d LIMIT 1",
                $view_code_id
            )
        );

        if ( $code_row ) {
            $meta = [];
            if ( isset( $code_row->metadata ) && $code_row->metadata !== '' ) {
                $decoded = json_decode( (string) $code_row->metadata, true );
                if ( is_array( $decoded ) ) {
                    $meta = $decoded;
                }
            }

            $used_by = [];
            if ( isset( $meta['used_by'] ) && is_array( $meta['used_by'] ) ) {
                $used_by = $meta['used_by'];
            }

            // Chuẩn hóa mảng used_by (mỗi item: user_id, used_at, username, display_name…)
            // Sắp xếp theo used_at DESC
            usort(
                $used_by,
                function( $a, $b ) {
                    $ta = isset( $a['used_at'] ) ? (int) $a['used_at'] : 0;
                    $tb = isset( $b['used_at'] ) ? (int) $b['used_at'] : 0;
                    return $tb <=> $ta;
                }
            );

            $usage_total = count( $used_by );

            if ( $usage_total > 0 ) {
                $slice = array_slice( $used_by, $usage_offset, $usage_perpage );

                // nạp thông tin user từ wp_users nếu cần
                if ( ! empty( $slice ) ) {
                    $user_ids = array_values(
                        array_unique(
                            array_map(
                                function( $x ) {
                                    return isset( $x['user_id'] ) ? (int) $x['user_id'] : 0;
                                },
                                $slice
                            )
                        )
                    );

                    $user_map = [];
                    if ( ! empty( $user_ids ) ) {
                        // Cache ngắn cho map users theo danh sách ID
                        $user_cache_key = 'iue_users_map_' . md5( wp_json_encode( $user_ids ) );
                        $user_map = wp_cache_get( $user_cache_key, 'iue' );
                        if ( false === $user_map ) {
                            // Tạo placeholders an toàn cho IN (...)
                            $placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                            $rows = $wpdb->get_results(
                                $wpdb->prepare(
                                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                                    "SELECT ID, display_name, user_login FROM {$wp_users} WHERE ID IN ($placeholders)",
                                    $user_ids // chấp nhận mảng
                                )
                            );
                            $user_map = [];
                            foreach ( (array) $rows as $r ) {
                                $user_map[ (int) $r->ID ] = [
                                    'display_name' => $r->display_name,
                                    'user_login'   => $r->user_login,
                                ];
                            }
                            wp_cache_set( $user_cache_key, $user_map, 'iue', 300 ); // 5 phút
                        }
                    }

                    foreach ( $slice as $row ) {
                        $uid = isset( $row['user_id'] ) ? (int) $row['user_id'] : 0;
                        $usage_rows[] = [
                            'user_id'      => $uid,
                            'used_at'      => isset( $row['used_at'] ) ? (int) $row['used_at'] : 0,
                            'meta'         => is_array( $row ) ? $row : [],
                            'display_name' => $user_map[ $uid ]['display_name'] ?? ( $row['display_name'] ?? '' ),
                            'user_login'   => $user_map[ $uid ]['user_login']   ?? ( $row['username']     ?? '' ),
                        ];
                    }
                }
            }
        }
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Redeem Codes', 'init-user-engine' ); ?></h1>

        <!-- ============================ -->
        <!-- FORM TẠO CODE -->
        <!-- ============================ -->
        <h2><?php esc_html_e( 'Create a Redeem Code', 'init-user-engine' ); ?></h2>

        <form method="post" style="margin-top: 20px;">
            <?php wp_nonce_field( 'iue_redeem_code_create', 'iue_redeem_code_nonce' ); ?>

            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Code', 'init-user-engine' ); ?></label></th>
                    <td>
                        <input type="text" name="iue_code" class="regular-text"
                               placeholder="<?php esc_attr_e( 'Leave empty to auto-generate', 'init-user-engine' ); ?>">
                    </td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Usage Type', 'init-user-engine' ); ?></label></th>
                    <td>
                        <select name="iue_type" id="iue_type">
                            <option value="single"><?php esc_html_e( 'Single use', 'init-user-engine' ); ?></option>
                            <option value="multi"><?php esc_html_e( 'Multi-use (X times)', 'init-user-engine' ); ?></option>
                            <option value="user_locked"><?php esc_html_e( 'Locked to specific user', 'init-user-engine' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr class="redeem-single-qty-row">
                    <th><label for="iue_single_qty"><?php esc_html_e( 'Quantity', 'init-user-engine' ); ?></label></th>
                    <td>
                        <input type="number" id="iue_single_qty" name="iue_single_qty" class="small-text" min="1" max="100" value="1">
                        <p class="description">
                            <?php esc_html_e( 'Number of single-use codes to generate. The Code field will be used as prefix.', 'init-user-engine' ); ?>
                        </p>
                    </td>
                </tr>

                <tr class="redeem-multi-row" style="display:none;">
                    <th><label><?php esc_html_e( 'Max Uses (X)', 'init-user-engine' ); ?></label></th>
                    <td>
                        <input type="number" name="iue_max_uses" min="1" placeholder="<?php esc_attr_e( 'Example: 5', 'init-user-engine' ); ?>">
                    </td>
                </tr>

                <tr class="redeem-user-row" style="display:none;">
                    <th><label><?php esc_html_e( 'Select User', 'init-user-engine' ); ?></label></th>
                    <td style="position: relative;">
                        <input type="text" id="iue_user_search" class="regular-text"
                               placeholder="<?php esc_attr_e( 'Search username or display name...', 'init-user-engine' ); ?>">
                        <div id="iue_user_results" class="iue-user-results"></div>
                        <div id="iue_user_selected" class="iue-user-selected"></div>
                        <input type="hidden" name="iue_user_lock" id="iue_user_lock" value="">
                        <p class="description"><?php esc_html_e( 'Only 1 user can be selected.', 'init-user-engine' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Coin Amount', 'init-user-engine' ); ?></label></th>
                    <td><input type="number" name="iue_coin" placeholder="0"></td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Cash Amount', 'init-user-engine' ); ?></label></th>
                    <td><input type="number" name="iue_cash" placeholder="0"></td>
                </tr>
            </table>

            <?php submit_button( __( 'Create Redeem Code', 'init-user-engine' ) ); ?>
        </form>

        <hr>

        <!-- ============================ -->
        <!-- LIST CODE ĐÃ TẠO -->
        <!-- ============================ -->
        <h2 style="margin-top: 30px;"><?php esc_html_e( 'Existing Redeem Codes', 'init-user-engine' ); ?></h2>

        <?php if ( empty( $codes ) ) : ?>

            <p><?php esc_html_e( 'No redeem codes found.', 'init-user-engine' ); ?></p>

        <?php else : ?>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Code', 'init-user-engine' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'init-user-engine' ); ?></th>
                        <th><?php esc_html_e( 'Locked User', 'init-user-engine' ); ?></th>
                        <th><?php esc_html_e( 'Coin', 'init-user-engine' ); ?></th>
                        <th><?php esc_html_e( 'Cash', 'init-user-engine' ); ?></th>
                        <th><?php esc_html_e( 'Used', 'init-user-engine' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'init-user-engine' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'init-user-engine' ); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ( $codes as $c ) : ?>
                        <?php
                        // Đọc metadata để biết used_by (để hiển thị nhanh số lượng + có link xem)
                        $meta = [];
                        if ( isset( $c->metadata ) && $c->metadata !== '' ) {
                            $decoded = json_decode( (string) $c->metadata, true );
                            if ( is_array( $decoded ) ) {
                                $meta = $decoded;
                            }
                        }
                        $used_by        = ( isset( $meta['used_by'] ) && is_array( $meta['used_by'] ) ) ? $meta['used_by'] : [];
                        $used_count_meta = count( $used_by );

                        // Xây link xem usage (nonce)
                        $view_url = wp_nonce_url(
                            add_query_arg(
                                [
                                    'page'   => 'init-user-engine-redeem-codes',
                                    'view'   => (int) $c->id,
                                    'upaged' => 1,
                                ],
                                admin_url( 'admin.php' )
                            ),
                            'iue_redeem_view_' . (int) $c->id
                        );

                        // Disable link
                        $disable_url = wp_nonce_url(
                            admin_url( 'admin.php?page=init-user-engine-redeem-codes&disable=' . (int) $c->id ),
                            'iue_redeem_disable_' . (int) $c->id
                        );
                        ?>
                        <tr>
                            <td><code><?php echo esc_html( $c->code ); ?></code></td>
                            <td><?php echo esc_html( $c->type ); ?></td>

                            <td>
                                <?php
                                if ( (int) $c->user_lock > 0 ) {
                                    // lấy tên user lock nếu cần
                                    $lock = get_userdata( (int) $c->user_lock );
                                    if ( $lock ) {
                                        printf(
                                            /* translators: 1: display name, 2: user_login, 3: user_id */
                                            esc_html__( '%1$s (%2$s) — ID: %3$d', 'init-user-engine' ),
                                            esc_html( $lock->display_name ),
                                            esc_html( $lock->user_login ),
                                            (int) $c->user_lock
                                        );
                                    } else {
                                        /* translators: %d: user ID */
                                        printf( esc_html__( 'User ID: %d', 'init-user-engine' ), (int) $c->user_lock );
                                    }
                                } else {
                                    echo '<span class="description">' . esc_html__( 'Not locked', 'init-user-engine' ) . '</span>';
                                }
                                ?>
                            </td>

                            <td><?php echo (int) $c->coin_amount; ?></td>
                            <td><?php echo (int) $c->cash_amount; ?></td>

                            <td>
                                <?php
                                // Hiển thị used_count (cột DB) / max_uses, kèm số thống kê thực tế theo metadata
                                echo (int) $c->used_count;
                                if ( $c->max_uses ) {
                                    echo ' / ' . (int) $c->max_uses;
                                }
                                // Nếu metadata có số khác, show thêm note nhỏ
                                if ( $used_count_meta !== (int) $c->used_count ) {
                                    // translators: 1: label text, 2: logged count number
                                    printf(
                                        ' <span class="description">(%1$s: %2$d)</span>',
                                        esc_html__( 'logged', 'init-user-engine' ),
                                        (int) $used_count_meta
                                    );
                                }
                                ?>
                            </td>

                            <td>
                                <?php if ( $c->status === 'active' ) : ?>
                                    <span style="color:green;font-weight:bold;"><?php esc_html_e( 'Active', 'init-user-engine' ); ?></span>
                                <?php else : ?>
                                    <span style="color:red;font-weight:bold;"><?php esc_html_e( 'Disabled', 'init-user-engine' ); ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="<?php echo esc_url( $view_url ); ?>">
                                    <?php esc_html_e( 'View Usage', 'init-user-engine' ); ?>
                                </a>
                                &nbsp;|&nbsp;
                                <a href="<?php echo esc_url( $disable_url ); ?>"
                                   onclick="return confirm('<?php echo esc_attr__( 'Disable this code?', 'init-user-engine' ); ?>');">
                                   <?php esc_html_e( 'Disable', 'init-user-engine' ); ?>
                                </a>
                                &nbsp;|&nbsp;
                                <?php if ( (int) $c->used_count === 0 ) : ?>
                                    <?php
                                    $delete_url = wp_nonce_url(
                                        admin_url( 'admin.php?page=init-user-engine-redeem-codes&delete=' . (int) $c->id ),
                                        'iue_redeem_delete_' . (int) $c->id
                                    );
                                    ?>
                                    <a href="<?php echo esc_url( $delete_url ); ?>"
                                       onclick="return confirm('<?php echo esc_attr__( 'Delete this unused code permanently?', 'init-user-engine' ); ?>');"
                                       style="color:#b32d2e;">
                                       <?php esc_html_e( 'Delete', 'init-user-engine' ); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="description"><?php esc_html_e( 'Used', 'init-user-engine' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- PAGINATION -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        // paginate_links trả về HTML, cần kèm escaping phù hợp
                        echo wp_kses_post(
                            paginate_links( [
                                'base'      => add_query_arg( 'paged', '%#%' ),
                                'format'    => '',
                                'current'   => $page,
                                'total'     => $total_pages,
                                'prev_text' => __( '&laquo; Previous', 'init-user-engine' ),
                                'next_text' => __( 'Next &raquo;', 'init-user-engine' ),
                            ] )
                        );
                        ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <?php if ( $view_code_id > 0 && $code_row ) : ?>
            <hr />
            <h2>
                <?php esc_html_e( 'Usage History', 'init-user-engine' ); ?>
                <?php
                echo ' — ' . esc_html__( 'Code', 'init-user-engine' ) . ': ';
                echo '<code>' . esc_html( $code_row->code ) . '</code>';
                ?>
            </h2>
            <?php
            if ( empty( $usage_rows ) ) :
                ?>
                <p><?php esc_html_e( 'No usage yet.', 'init-user-engine' ); ?></p>
                <?php
            else :
                ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'User', 'init-user-engine' ); ?></th>
                            <th><?php esc_html_e( 'Time', 'init-user-engine' ); ?></th>
                            <th><?php esc_html_e( 'Meta', 'init-user-engine' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $usage_rows as $u ) : ?>
                            <tr>
                                <td>
                                    <?php
                                    $name  = $u['display_name'] ?: ( '#' . (int) $u['user_id'] );
                                    $login = $u['user_login']   ?: '';
                                    printf(
                                        /* translators: 1: display name, 2: user_login, 3: user_id */
                                        esc_html__( '%1$s (%2$s) — ID: %3$d', 'init-user-engine' ),
                                        esc_html( $name ),
                                        esc_html( $login ),
                                        (int) $u['user_id']
                                    );
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $ts = (int) $u['used_at'];
                                    echo $ts > 0
                                        ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ) )
                                        : esc_html__( 'Unknown', 'init-user-engine' );
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $pairs = [];
                                    foreach ( (array) $u['meta'] as $mk => $mv ) {
                                        if ( $mk === 'user_id' || $mk === 'used_at' ) {
                                            continue;
                                        }
                                        if ( is_scalar( $mv ) ) {
                                            $pairs[] = sanitize_text_field( (string) $mk ) . ': ' . sanitize_text_field( (string) $mv );
                                        }
                                    }
                                    echo ! empty( $pairs )
                                        ? esc_html( implode( '; ', $pairs ) )
                                        : '<span class="description">' . esc_html__( 'None', 'init-user-engine' ) . '</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                $usage_total_pages = ( $usage_perpage > 0 ) ? (int) ceil( $usage_total / $usage_perpage ) : 1;
                if ( $usage_total_pages > 1 ) :
                    $base_url = add_query_arg(
                        [
                            'page'     => 'init-user-engine-redeem-codes',
                            'view'     => $view_code_id,
                            '_wpnonce' => $view_nonce,
                            'upaged'   => '%#%',
                        ],
                        admin_url( 'admin.php' )
                    );
                    ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo wp_kses_post(
                                paginate_links( [
                                    'base'      => esc_url( $base_url ),
                                    'format'    => '',
                                    'current'   => $usage_page,
                                    'total'     => $usage_total_pages,
                                    'prev_text' => __( '&laquo; Previous', 'init-user-engine' ),
                                    'next_text' => __( 'Next &raquo;', 'init-user-engine' ),
                                ] )
                            );
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <p>
                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=init-user-engine-redeem-codes' ) ); ?>">
                    <?php esc_html_e( 'Close', 'init-user-engine' ); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
<?php } ?>
