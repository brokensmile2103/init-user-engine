<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin User "Metabox" (postbox style) for Init User Engine
 * Screen: profile.php & user-edit.php
 */

/**
 * Hooks to render our postbox on user profile screens
 */
add_action( 'show_user_profile', 'init_plugin_suite_user_engine_render_admin_user_metabox' );
add_action( 'edit_user_profile',  'init_plugin_suite_user_engine_render_admin_user_metabox' );

/**
 * Optional: small CSS for progress bar & layout (only on profile screens)
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
	// Only load on profile pages
	if ( $hook !== 'profile.php' && $hook !== 'user-edit.php' ) return;

	$css = '
		.iue-admin-cards { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
		@media (max-width: 1100px){ .iue-admin-cards { grid-template-columns:1fr; } }
		.iue-card { background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:14px; }
		.iue-kpis { display:flex; flex-wrap:wrap; gap:14px; }
		.iue-kpi { background:#f6f7f7; border:1px solid #dcdcdc; padding:10px 12px; border-radius:6px; min-width:140px; }
		.iue-kpi b { font-size:15px; }
		.iue-progress { background:#eef1f3; border-radius:999px; height:10px; overflow:hidden; }
		.iue-progress > span { display:block; height:10px; background:#2271b1; width:0; }
		.iue-meta { color:#555; }
		.iue-flex { display:flex; gap:18px; align-items:center; }
		.iue-flex .iue-badge { background:#e7f5ff; color:#0a66c2; border:1px solid #b5dcff; padding:2px 8px; border-radius:999px; font-weight:600; }
		.iue-list { margin:8px 0 0; padding-left:18px; }

		/* New: simple scroll boxes for logs/inbox */
		.iue-scroll { max-height: 320px; overflow: auto; border:1px solid #eee; background:#fafafa; padding:10px; border-radius:6px; }
		.iue-log, .iue-inbox { list-style: none; margin:0; padding:0; }
		.iue-log-item, .iue-inbox-item { display:flex; gap:8px; align-items:flex-start; padding:6px 0; border-bottom:1px dashed #e3e3e3; }
		.iue-log-item:last-child, .iue-inbox-item:last-child { border-bottom:none; }
		.iue-badge { background:#e7f5ff; color:#0a66c2; border:1px solid #b5dcff; padding:1px 6px; border-radius:999px; font-weight:600; font-size:11px; line-height:18px; }
		.iue-amount { min-width:90px; font-weight:700; }
		.iue-right { margin-left:auto; color:#666; font-size:11px; white-space:nowrap; }
		.iue-title { font-weight:600; }
		.iue-dim { color:#666; font-size:12px; }
	';
	wp_register_style(
	    'iue-admin-inline-style',
	    false,
	    [],
	    defined( 'INIT_PLUGIN_SUITE_IUE_VERSION' ) ? INIT_PLUGIN_SUITE_IUE_VERSION : '1.0.0'
	);
	wp_enqueue_style( 'iue-admin-inline-style' );
	wp_add_inline_style( 'iue-admin-inline-style', $css );
} );

/**
 * Main renderer
 *
 * @param WP_User $user
 */
function init_plugin_suite_user_engine_render_admin_user_metabox( $user ) {
	if ( ! ( $user instanceof WP_User ) ) {
		$user = get_userdata( absint( $user ) );
		if ( ! $user ) return;
	}

	// Capability: allow user to see own box or editors/admins to view others
	if ( ! current_user_can( 'edit_user', $user->ID ) ) return;

	$user_id = (int) $user->ID;

	// ------- Get core values with safe fallbacks -------
	$coin = init_plugin_suite_user_engine_safe_get_coin( $user_id );
	$cash = init_plugin_suite_user_engine_safe_get_cash( $user_id );

	$level = (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_level', 1 );
	$exp_now = (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_exp', 0 );

	$exp_required = 0;
	if ( function_exists( 'init_plugin_suite_user_engine_exp_required' ) ) {
		$exp_required = (int) init_plugin_suite_user_engine_exp_required( $level );
	}

	// VIP state
	$is_vip = false;
	if ( function_exists( 'init_plugin_suite_user_engine_is_vip' ) ) {
		$is_vip = (bool) init_plugin_suite_user_engine_is_vip( $user_id );
	}
	$vip_expiry = function_exists( 'init_plugin_suite_user_engine_get_vip_expiry' )
		? (int) init_plugin_suite_user_engine_get_vip_expiry( $user_id )
		: (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_vip_expire', 0 );

	$vip_log = function_exists( 'init_plugin_suite_user_engine_get_vip_log' )
		? (array) init_plugin_suite_user_engine_get_vip_log( $user_id )
		: (array) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_vip_log', [] );

	$vip_purchases = is_array( $vip_log ) ? count( $vip_log ) : 0;
	$is_lifetime   = init_plugin_suite_user_engine_vip_has_lifetime( $vip_log );

	// Inbox stats
	$inbox = init_plugin_suite_user_engine_get_inbox_quick_stats( $user_id );

	// EXP progress
	$progress = 0;
	if ( $exp_required > 0 ) {
		$progress = max( 0, min( 100, round( ( $exp_now / $exp_required ) * 100, 1 ) ) );
	}

	// VIP remaining text
	$vip_label = __( 'Not VIP', 'init-user-engine' );
	if ( $is_lifetime ) {
		$vip_label = __( 'VIP: Lifetime', 'init-user-engine' );
	} elseif ( $is_vip && $vip_expiry > 0 ) {
		$vip_label = sprintf(
			/* translators: %s is human time diff */
			__( 'VIP: %s left', 'init-user-engine' ),
			human_time_diff( current_time( 'timestamp' ), $vip_expiry )
		);
	} elseif ( $vip_expiry > 0 && $vip_expiry <= current_time( 'timestamp' ) ) {
		$vip_label = __( 'VIP expired', 'init-user-engine' );
	}

	?>
	<h2><?php esc_html_e( 'User Overview', 'init-user-engine' ); ?></h2>

	<div class="metabox-holder">
		<div class="postbox">
			<h2 class="hndle"><span><?php esc_html_e( 'Snapshot', 'init-user-engine' ); ?></span></h2>
			<div class="inside">
				<div class="iue-kpis">
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'Coin', 'init-user-engine' ); ?></div>
						<strong><?php echo esc_html( number_format_i18n( $coin ) ); ?></strong>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'Cash', 'init-user-engine' ); ?></div>
						<strong><?php echo esc_html( number_format_i18n( $cash ) ); ?></strong>
					</div>
					<?php
					// Extra KPIs injected via filter
					$__iue_extra_kpis = init_plugin_suite_user_engine_get_admin_user_extra_stats( $user_id );
					if ( ! empty( $__iue_extra_kpis ) ) :
						foreach ( $__iue_extra_kpis as $__iue_item ) : ?>
							<div class="iue-kpi">
								<div class="iue-meta"><?php echo esc_html( $__iue_item['label'] ); ?></div>
								<strong><?php echo wp_kses_post( $__iue_item['value'] ); ?></strong>
							</div>
						<?php endforeach;
					endif;
					?>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'Level', 'init-user-engine' ); ?></div>
						<strong><?php echo esc_html( $level ); ?></strong>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'EXP', 'init-user-engine' ); ?></div>
						<strong><?php echo esc_html( number_format_i18n( $exp_now ) ); ?>
							<?php if ( $exp_required > 0 ) : ?>
								<span class="iue-meta">/ <?php echo esc_html( number_format_i18n( $exp_required ) ); ?></span>
							<?php endif; ?>
						</strong>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'VIP Purchases', 'init-user-engine' ); ?></div>
						<strong><?php echo esc_html( number_format_i18n( $vip_purchases ) ); ?></strong>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'VIP Status', 'init-user-engine' ); ?></div>
						<strong><?php echo esc_html( $vip_label ); ?></strong>
					</div>
				</div>

				<?php if ( $exp_required > 0 ) : ?>
					<p class="iue-meta" style="margin-top:12px;"><?php esc_html_e( 'Level Progress', 'init-user-engine' ); ?></p>
					<div class="iue-progress" aria-label="<?php echo esc_attr( $progress . '%' ); ?>" title="<?php echo esc_attr( $progress . '%' ); ?>">
						<span style="width: <?php echo esc_attr( $progress ); ?>%;"></span>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="iue-admin-cards">
			<div class="iue-card">
				<h3><?php esc_html_e( 'VIP Details', 'init-user-engine' ); ?></h3>
				<ul class="iue-list">
					<li>
						<?php esc_html_e( 'Current state:', 'init-user-engine' ); ?>
						<strong><?php echo esc_html( $is_lifetime ? __( 'Lifetime', 'init-user-engine' ) : ( $is_vip ? __( 'Active', 'init-user-engine' ) : __( 'Inactive', 'init-user-engine' ) ) ); ?></strong>
					</li>
					<li>
						<?php esc_html_e( 'Expiry:', 'init-user-engine' ); ?>
						<strong>
							<?php
							if ( $is_lifetime ) {
								esc_html_e( 'Never expires', 'init-user-engine' );
							} elseif ( $vip_expiry > 0 ) {
								echo esc_html( wp_date( 'M j, Y H:i', $vip_expiry ) );
							} else {
								esc_html_e( 'N/A', 'init-user-engine' );
							}
							?>
						</strong>
					</li>
					<?php
					// Optional totals from log
					$tot_days = 0; $tot_coin = 0;
					if ( is_array( $vip_log ) ) {
						foreach ( $vip_log as $row ) {
							$tot_days += (int) ( $row['days'] ?? 0 );
							$tot_coin += (int) ( $row['coin'] ?? 0 );
						}
					}
					?>
					<li>
						<?php esc_html_e( 'Total VIP days purchased (all time):', 'init-user-engine' ); ?>
						<strong><?php echo esc_html( number_format_i18n( $tot_days ) ); ?></strong>
					</li>
					<li>
						<?php esc_html_e( 'Total Coin spent for VIP:', 'init-user-engine' ); ?>
						<strong><?php echo esc_html( number_format_i18n( $tot_coin ) ); ?></strong>
					</li>
				</ul>

				<?php
				// Generate secure admin-post URL for removing VIP
				$__iue_remove_vip_url = wp_nonce_url(
					admin_url( 'admin-post.php?action=iue_remove_vip&user_id=' . $user_id ),
					'iue_remove_vip_' . $user_id
				);

				// Show button only if currently VIP, lifetime, or has an expiry set
				if ( $is_lifetime || $is_vip || (int) $vip_expiry > 0 ) : ?>
					<p class="iue-meta" style="margin-top:10px;">
						<a class="button button-secondary" href="<?php echo esc_url( $__iue_remove_vip_url ); ?>"
						   onclick="return confirm('<?php echo esc_attr__( 'Are you sure you want to remove this user’s VIP status?', 'init-user-engine' ); ?>');">
							<?php esc_html_e( 'Remove VIP', 'init-user-engine' ); ?>
						</a>
					</p>
				<?php endif; ?>

				<?php
				// ==================== NEW: Recent Transactions (up to 100) ====================
				$__tx_log = [];
				if ( function_exists( 'init_plugin_suite_user_engine_get_transaction_log' ) ) {
					$__tx_all = (array) init_plugin_suite_user_engine_get_transaction_log( $user_id );
					// latest first
					$__tx_log = array_slice( array_reverse( array_values( $__tx_all ) ), 0, 100 );
				}
				?>
				<h4 style="margin-top:20px;"><?php esc_html_e( 'Recent Transactions', 'init-user-engine' ); ?></h4>
				<?php if ( ! empty( $__tx_log ) ) : ?>
					<div class="iue-scroll" aria-label="<?php esc_attr_e( 'Transaction log', 'init-user-engine' ); ?>">
						<ul class="iue-log">
							<?php foreach ( $__tx_log as $__e ) :
								if ( ! is_array( $__e ) ) { continue; }
								$__type  = strtoupper( (string) ( $__e['type'] ?? '' ) );
								$__sign  = ( ( $__e['change'] ?? 'add' ) === 'deduct' ) ? '-' : '+';
								$__amt   = absint( $__e['amount'] ?? 0 );
								$__msg   = function_exists( 'init_plugin_suite_user_engine_format_log_message' )
									? init_plugin_suite_user_engine_format_log_message( $__e )
									: ucfirst( str_replace( '_', ' ', (string) ( $__e['source'] ?? 'unknown' ) ) );
								$__time  = (string) ( $__e['time'] ?? wp_date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) );
								?>
								<li class="iue-log-item">
									<span class="iue-badge"><?php echo esc_html( $__type ); ?></span>
									<span class="iue-amount"><?php echo esc_html( $__sign . number_format_i18n( $__amt ) ); ?></span>
									<span class="iue-dim"><?php echo esc_html( $__msg ); ?></span>
									<span class="iue-right"><?php echo esc_html( $__time ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php else : ?>
					<p class="iue-meta"><?php esc_html_e( 'No transactions found.', 'init-user-engine' ); ?></p>
				<?php endif; ?>
				<!-- ==================== /NEW ==================== -->
			</div>

			<div class="iue-card">
				<h3><?php esc_html_e( 'Inbox (User)', 'init-user-engine' ); ?></h3>
				<div class="iue-kpis" style="margin-top:6px;">
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'Total Messages', 'init-user-engine' ); ?></div>
						<strong><?php echo esc_html( number_format_i18n( $inbox['total'] ) ); ?></strong>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'Unread', 'init-user-engine' ); ?></div>
						<strong><?php echo esc_html( number_format_i18n( $inbox['unread'] ) ); ?></strong>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'Last 7 Days', 'init-user-engine' ); ?></div>
						<strong><?php echo esc_html( number_format_i18n( $inbox['last7'] ) ); ?></strong>
					</div>
				</div>
				<p class="iue-meta" style="margin-top:10px;">
					<?php esc_html_e( 'Last message:', 'init-user-engine' ); ?>
					<strong>
						<?php
						echo $inbox['last_time'] ? esc_html( wp_date( 'M j, Y H:i', $inbox['last_time'] ) ) : esc_html__( 'N/A', 'init-user-engine' );
						?>
					</strong>
				</p>
				<p class="iue-meta">
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=init-user-engine-inbox-stats' ) ); ?>">
						<?php esc_html_e( 'Open Inbox Statistics', 'init-user-engine' ); ?>
					</a>
				</p>

				<?php
				// ==================== NEW: Recent Inbox (up to 100) ====================
				$__inbox_rows = [];
				if ( function_exists( 'init_plugin_suite_user_engine_get_inbox' ) && function_exists( 'init_plugin_suite_user_engine_get_inbox_table' ) ) {
					// Lấy 100 item mới nhất (hàm đã ORDER BY created_at DESC)
					$__inbox_rows = (array) init_plugin_suite_user_engine_get_inbox( $user_id, 1, 100 );
				}
				?>
				<h4 style="margin-top:20px;"><?php esc_html_e( 'Recent Inbox', 'init-user-engine' ); ?></h4>
				<?php if ( ! empty( $__inbox_rows ) ) : ?>
					<div class="iue-scroll" aria-label="<?php esc_attr_e( 'Recent inbox messages', 'init-user-engine' ); ?>">
						<ul class="iue-inbox">
							<?php foreach ( $__inbox_rows as $__m ) :
								if ( ! is_array( $__m ) ) { continue; }
								$__status   = strtoupper( (string) ( $__m['status'] ?? 'unknown' ) );
								$__type     = strtoupper( (string) ( $__m['type'] ?? 'system' ) );
								$__title    = (string) ( $__m['title'] ?? '' );
								$__content  = isset( $__m['content'] ) ? wp_strip_all_tags( (string) $__m['content'] ) : '';
								$__content  = $__content !== '' ? wp_html_excerpt( $__content, 120, '…' ) : '';
								$__created  = isset( $__m['created_at'] ) ? (int) $__m['created_at'] : current_time( 'timestamp' );
								?>
								<li class="iue-inbox-item">
									<div style="flex:1;">
										<div class="iue-title"><?php echo esc_html( $__title ); ?></div>
										<?php if ( $__content ) : ?>
											<div class="iue-dim"><?php echo esc_html( $__content ); ?></div>
										<?php endif; ?>
									</div>
									<span class="iue-badge"><?php echo esc_html( $__type ); ?></span>
									<span class="iue-badge" style="background:#f5f5f5;color:#444;border-color:#e1e1e1;"><?php echo esc_html( $__status ); ?></span>
									<span class="iue-right"><?php echo esc_html( wp_date( 'M j, Y H:i', $__created ) ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php else : ?>
					<p class="iue-meta"><?php esc_html_e( 'No inbox messages found.', 'init-user-engine' ); ?></p>
				<?php endif; ?>
				<!-- ==================== /NEW ==================== -->
			</div>
		</div>
	</div>
	<?php
}

/**
 * ---------- Helper functions (safe getters & inbox quick stats) ----------
 */

/** Safe get Coin with fallback to meta key iue_coin */
function init_plugin_suite_user_engine_safe_get_coin( $user_id ) {
	if ( function_exists( 'init_plugin_suite_user_engine_get_coin' ) ) {
		return (int) init_plugin_suite_user_engine_get_coin( $user_id );
	}
	return (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_coin', 0 );
}

/** Safe get Cash with fallback to meta key iue_cash */
function init_plugin_suite_user_engine_safe_get_cash( $user_id ) {
	if ( function_exists( 'init_plugin_suite_user_engine_get_cash' ) ) {
		return (int) init_plugin_suite_user_engine_get_cash( $user_id );
	}
	return (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_cash', 0 );
}

/**
 * Detect Lifetime VIP from log entries (days >= 9999)
 *
 * @param array $vip_log
 * @return bool
 */
function init_plugin_suite_user_engine_vip_has_lifetime( $vip_log ) {
	if ( ! is_array( $vip_log ) ) return false;
	foreach ( $vip_log as $row ) {
		if ( (int) ( $row['days'] ?? 0 ) >= 9999 ) return true;
	}
	return false;
}

/**
 * Inbox quick stats for a user
 *
 * @param int $user_id
 * @return array{total:int, unread:int, last7:int, last_time:int|null}
 */
function init_plugin_suite_user_engine_get_inbox_quick_stats( $user_id ) {
	global $wpdb;

	$stats = [
		'total'     => 0,
		'unread'    => 0,
		'last7'     => 0,
		'last_time' => null,
	];

	if ( ! function_exists( 'init_plugin_suite_user_engine_get_inbox_table' ) ) {
		// Cannot query inbox without table name helper
		// Try best-effort using unread_count function only
		if ( function_exists( 'init_plugin_suite_user_engine_get_unread_inbox_count' ) ) {
			$stats['unread'] = (int) init_plugin_suite_user_engine_get_unread_inbox_count( $user_id );
		}
		return $stats;
	}

	$table = init_plugin_suite_user_engine_get_inbox_table();

	// Check table exists
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
		return $stats;
	}

	$now = current_time( 'timestamp' );
	$seven_days_ago = $now - ( 7 * DAY_IN_SECONDS );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$stats['total']  = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
		$user_id
	) );

	// Use helper for unread if available to keep logic consistent
	if ( function_exists( 'init_plugin_suite_user_engine_get_unread_inbox_count' ) ) {
		$stats['unread'] = (int) init_plugin_suite_user_engine_get_unread_inbox_count( $user_id );
	} else {
		$stats['unread'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND status = 'unread'",
			$user_id
		) );
	}

	$stats['last7'] = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND created_at >= %d",
		$user_id, $seven_days_ago
	) );

	$stats['last_time'] = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT MAX(created_at) FROM {$table} WHERE user_id = %d",
		$user_id
	) );
	// phpcs:enable

	if ( ! $stats['last_time'] ) {
		$stats['last_time'] = null;
	}

	return $stats;
}

/**
 * Return extra KPI rows for the Admin User metabox (after Cash).
 *
 * Structure each item as:
 * [
 *   'label' => (string) KPI label (plain text, will be esc_html),
 *   'value' => (string) KPI value (HTML allowed, will be wp_kses_post),
 * ]
 *
 * @param int $user_id
 * @return array<int, array{label:string,value:string}>
 */
function init_plugin_suite_user_engine_get_admin_user_extra_stats( $user_id ) {
	$items = apply_filters( 'init_plugin_suite_user_engine_admin_user_extra_stats', [], $user_id );

	// Normalize to safe array
	$normalized = [];
	if ( is_array( $items ) ) {
		foreach ( $items as $row ) {
			if ( ! is_array( $row ) ) continue;
			$label = isset( $row['label'] ) ? (string) $row['label'] : '';
			$value = isset( $row['value'] ) ? (string) $row['value'] : '';
			if ( $label === '' ) continue;
			$normalized[] = [
				'label' => $label,
				'value' => $value,
			];
		}
	}
	return $normalized;
}

/**
 * Handle admin-post to remove VIP from a user.
 * URL pattern: admin-post.php?action=iue_remove_vip&user_id=###&_wpnonce=...
 */
add_action( 'admin_post_iue_remove_vip', function () {
	if ( ! is_admin() ) {
		wp_die();
	}

	$user_id = isset( $_GET['user_id'] )
		? absint( wp_unslash( $_GET['user_id'] ) )
		: 0;

	if ( ! $user_id ) {
		wp_safe_redirect( add_query_arg( 'iue_vip_removed', '0', admin_url() ) );
		exit;
	}

	// Nonce + capability checks (UNSLASH + SANITIZE trước khi verify)
	$nonce = isset( $_GET['_wpnonce'] )
		? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) )
		: '';

	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'iue_remove_vip_' . $user_id ) ) {
		wp_die( esc_html__( 'Security check failed.', 'init-user-engine' ) );
	}
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this user.', 'init-user-engine' ) );
	}

	$now = current_time( 'timestamp' );

	// Fetch current log & expiry using available helpers or meta
	$vip_expiry = function_exists( 'init_plugin_suite_user_engine_get_vip_expiry' )
		? (int) init_plugin_suite_user_engine_get_vip_expiry( $user_id )
		: (int) get_user_meta( $user_id, 'iue_vip_expire', true );

	$vip_log = function_exists( 'init_plugin_suite_user_engine_get_vip_log' )
		? (array) init_plugin_suite_user_engine_get_vip_log( $user_id )
		: (array) get_user_meta( $user_id, 'iue_vip_log', true );

	if ( ! is_array( $vip_log ) ) $vip_log = [];

	// SOFT-CANCEL STRATEGY:
	// - Set expiry to 0 (inactive)
	// - Add a log entry {action: cancel}
	// - If any "lifetime" entries exist (days >= 9999), neutralize them by converting to 0 days but keep note
	$had_lifetime = false;
	foreach ( $vip_log as &$__row ) {
		$days_val = isset( $__row['days'] ) ? (int) $__row['days'] : 0;
		if ( $days_val >= 9999 ) {
			$had_lifetime = true;
			$__row['note'] = ( isset( $__row['note'] ) ? (string) $__row['note'] . '; ' : '' ) . 'lifetime_cancelled';
			$__row['days'] = 0; // neutralize lifetime flag for UI checker
		}
	}
	unset( $__row );

	$vip_log[] = [
		'action'      => 'cancel',
		'by'          => get_current_user_id(),
		'time'        => $now,
		'prev_expiry' => (int) $vip_expiry,
		'note'        => $had_lifetime ? 'admin_cancel_vip (lifetime neutralized)' : 'admin_cancel_vip',
	];

	// Persist changes
	update_user_meta( $user_id, 'iue_vip_expire', 0 );
	update_user_meta( $user_id, 'iue_vip_log', $vip_log );

	/**
	 * Hook for external integrations/auditing
	 *
	 * @param int   $user_id
	 * @param int   $prev_expiry
	 * @param array $vip_log_after
	 */
	do_action( 'init_plugin_suite_user_engine_vip_removed', $user_id, (int) $vip_expiry, $vip_log );

	// Redirect back (profile or user-edit) with notice + nonce
	$back = wp_get_referer();
	if ( ! $back ) {
		$back = get_edit_user_link( $user_id );
	}

	$notice_nonce = wp_create_nonce( 'iue_notice_' . get_current_user_id() );

	wp_safe_redirect( add_query_arg(
		array(
			'iue_vip_removed' => '1',
			'_iue_notice'     => $notice_nonce,
		),
		$back
	) );
	exit;
} );

// Show an admin notice after VIP removal
add_action( 'admin_notices', function () {
	// Đọc GET an toàn (unslash + sanitize)
	$flag = isset( $_GET['iue_vip_removed'] )
		? sanitize_text_field( wp_unslash( $_GET['iue_vip_removed'] ) )
		: '';

	$notice_nonce = isset( $_GET['_iue_notice'] )
		? sanitize_text_field( wp_unslash( $_GET['_iue_notice'] ) )
		: '';

	// Verify nonce cho notice để thỏa WordPress.Security.NonceVerification.Recommended
	if ( empty( $flag ) || empty( $notice_nonce ) || ! wp_verify_nonce( $notice_nonce, 'iue_notice_' . get_current_user_id() ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && ! in_array( $screen->id, array( 'profile', 'user-edit' ), true ) ) {
		// Chỉ hiển thị ở trang hồ sơ user
		return;
	}

	$ok   = ( '1' === $flag );
	$class = $ok ? 'updated' : 'error';
	$msg   = $ok
		? __( 'VIP status has been removed for this user.', 'init-user-engine' )
		: __( 'Failed to remove VIP status.', 'init-user-engine' );

	// ESCAPE tại điểm xuất: class -> esc_attr, nội dung -> esc_html
	printf(
		'<div class="%1$s notice is-dismissible"><p>%2$s</p></div>',
		esc_attr( $class ),
		esc_html( $msg )
	);
} );
