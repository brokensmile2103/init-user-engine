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
						<b><?php echo esc_html( number_format_i18n( $coin ) ); ?></b>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'Cash', 'init-user-engine' ); ?></div>
						<b><?php echo esc_html( number_format_i18n( $cash ) ); ?></b>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'Level', 'init-user-engine' ); ?></div>
						<b><?php echo esc_html( $level ); ?></b>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'EXP', 'init-user-engine' ); ?></div>
						<b><?php echo esc_html( number_format_i18n( $exp_now ) ); ?>
							<?php if ( $exp_required > 0 ) : ?>
								<span class="iue-meta">/ <?php echo esc_html( number_format_i18n( $exp_required ) ); ?></span>
							<?php endif; ?>
						</b>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'VIP Purchases', 'init-user-engine' ); ?></div>
						<b><?php echo esc_html( number_format_i18n( $vip_purchases ) ); ?></b>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'VIP Status', 'init-user-engine' ); ?></div>
						<b><?php echo esc_html( $vip_label ); ?></b>
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
			</div>

			<div class="iue-card">
				<h3><?php esc_html_e( 'Inbox (User)', 'init-user-engine' ); ?></h3>
				<div class="iue-kpis" style="margin-top:6px;">
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'Total Messages', 'init-user-engine' ); ?></div>
						<b><?php echo esc_html( number_format_i18n( $inbox['total'] ) ); ?></b>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'Unread', 'init-user-engine' ); ?></div>
						<b><?php echo esc_html( number_format_i18n( $inbox['unread'] ) ); ?></b>
					</div>
					<div class="iue-kpi">
						<div class="iue-meta"><?php esc_html_e( 'Last 7 Days', 'init-user-engine' ); ?></div>
						<b><?php echo esc_html( number_format_i18n( $inbox['last7'] ) ); ?></b>
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
