<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Render the inbox statistics page
function init_plugin_suite_user_engine_render_inbox_stats_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'init-user-engine'));
    }

    global $wpdb;
    $table = init_plugin_suite_user_engine_get_inbox_table();
    
    // Check if table exists
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Inbox Statistics', 'init-user-engine'); ?></h1>
            <div class="notice notice-error">
                <p><?php esc_html_e('Inbox table not found. Please ensure the User Engine plugin is properly installed.', 'init-user-engine'); ?></p>
            </div>
        </div>
        <?php
        return;
    }

    // Get comprehensive statistics
    $stats = init_plugin_suite_user_engine_get_comprehensive_inbox_stats();
    $advanced_stats = init_plugin_suite_user_engine_get_advanced_inbox_analytics();
    
    // Handle date range filter
    $date_range = isset($_GET['range']) ? sanitize_text_field(wp_unslash($_GET['range'])) : '7days'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $filtered_stats = init_plugin_suite_user_engine_get_filtered_inbox_stats($date_range);
    
    ?>
    <div class="wrap iue-inbox-stats-page">
        <h1><?php esc_html_e('Inbox Statistics', 'init-user-engine'); ?></h1>
        
        <!-- Date Range Filter -->
        <div class="iue-stats-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="init-user-engine-inbox-stats">
                <label for="range"><?php esc_html_e('Date Range:', 'init-user-engine'); ?></label>
                <select name="range" id="range" onchange="this.form.submit()">
                    <option value="7days" <?php selected($date_range, '7days'); ?>><?php esc_html_e('Last 7 Days', 'init-user-engine'); ?></option>
                    <option value="30days" <?php selected($date_range, '30days'); ?>><?php esc_html_e('Last 30 Days', 'init-user-engine'); ?></option>
                    <option value="90days" <?php selected($date_range, '90days'); ?>><?php esc_html_e('Last 90 Days', 'init-user-engine'); ?></option>
                    <option value="all" <?php selected($date_range, 'all'); ?>><?php esc_html_e('All Time', 'init-user-engine'); ?></option>
                </select>
            </form>
        </div>

        <!-- Overview Stats Grid -->
        <div class="iue-stats-overview">
            <div class="iue-stats-card iue-stats-total">
                <div class="iue-stats-icon">📧</div>
                <div class="iue-stats-content">
                    <h3><?php echo esc_html(number_format($stats['total_messages'])); ?></h3>
                    <p><?php esc_html_e('Total Messages', 'init-user-engine'); ?></p>
                </div>
            </div>
            
            <div class="iue-stats-card iue-stats-unread">
                <div class="iue-stats-icon">🔔</div>
                <div class="iue-stats-content">
                    <h3><?php echo esc_html(number_format($stats['unread_messages'])); ?></h3>
                    <p><?php esc_html_e('Unread Messages', 'init-user-engine'); ?></p>
                    <small><?php echo esc_html($stats['unread_percentage']); ?>% of total</small>
                </div>
            </div>
            
            <div class="iue-stats-card iue-stats-today">
                <div class="iue-stats-icon">📅</div>
                <div class="iue-stats-content">
                    <h3><?php echo esc_html(number_format($stats['today_messages'])); ?></h3>
                    <p><?php esc_html_e('Sent Today', 'init-user-engine'); ?></p>
                </div>
            </div>
            
            <div class="iue-stats-card iue-stats-recipients">
                <div class="iue-stats-icon">👥</div>
                <div class="iue-stats-content">
                    <h3><?php echo esc_html(number_format($stats['total_recipients'])); ?></h3>
                    <p><?php esc_html_e('Total Recipients', 'init-user-engine'); ?></p>
                </div>
            </div>
        </div>

        <div class="iue-stats-layout">
            <!-- Left Column -->
            <div class="iue-stats-left">
                
                <!-- Message Types Chart -->
                <div class="iue-stats-section">
                    <h2><?php esc_html_e('📊 Message Types Distribution', 'init-user-engine'); ?></h2>
                    <?php if (!empty($stats['message_types'])): ?>
                        <div class="iue-type-distribution">
                            <?php foreach ($stats['message_types'] as $type => $count): ?>
                                <div class="iue-type-item">
                                    <div class="iue-type-header">
                                        <span class="iue-type-name"><?php echo esc_html(ucfirst($type)); ?></span>
                                        <span class="iue-type-count"><?php echo esc_html(number_format($count)); ?></span>
                                    </div>
                                    <div class="iue-type-bar">
                                        <div class="iue-type-fill" style="width: <?php echo esc_attr($stats['total_messages'] > 0 ? round(($count / $stats['total_messages']) * 100, 1) : 0); ?>%;"></div>
                                    </div>
                                    <div class="iue-type-percentage"><?php echo esc_html($stats['total_messages'] > 0 ? round(($count / $stats['total_messages']) * 100, 1) : 0); ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="iue-no-data"><?php esc_html_e('No message type data available.', 'init-user-engine'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Priority Distribution -->
                <div class="iue-stats-section">
                    <h2><?php esc_html_e('🚨 Priority Levels', 'init-user-engine'); ?></h2>
                    <div class="iue-priority-distribution">
                        <?php if (!empty($stats['priority_levels'])): ?>
                            <?php foreach ($stats['priority_levels'] as $priority => $count): ?>
                                <div class="iue-priority-card iue-priority-<?php echo esc_attr($priority); ?>">
                                    <div class="iue-priority-count"><?php echo esc_html(number_format($count)); ?></div>
                                    <div class="iue-priority-label"><?php echo esc_html(ucfirst($priority)); ?></div>
                                    <div class="iue-priority-percent"><?php echo esc_html($stats['total_messages'] > 0 ? round(($count / $stats['total_messages']) * 100, 1) : 0); ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="iue-no-data"><?php esc_html_e('No priority data available.', 'init-user-engine'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Engagement Analytics -->
                <div class="iue-stats-section">
                    <h2><?php esc_html_e('📈 Engagement Analytics', 'init-user-engine'); ?></h2>
                    <div class="iue-engagement-grid">
                        <div class="iue-engagement-item">
                            <h4><?php esc_html_e('Overall Read Rate', 'init-user-engine'); ?></h4>
                            <div class="iue-engagement-value"><?php echo esc_html($stats['read_percentage']); ?>%</div>
                            <div class="iue-engagement-bar">
                                <div class="iue-engagement-fill" style="width: <?php echo esc_attr($stats['read_percentage']); ?>%;"></div>
                            </div>
                        </div>
                        
                        <div class="iue-engagement-item">
                            <h4><?php esc_html_e('Avg Messages per User', 'init-user-engine'); ?></h4>
                            <div class="iue-engagement-value"><?php echo esc_html($stats['avg_messages_per_user']); ?></div>
                        </div>
                        
                        <div class="iue-engagement-item">
                            <h4><?php esc_html_e('Pinned Messages', 'init-user-engine'); ?></h4>
                            <div class="iue-engagement-value"><?php echo esc_html(number_format($stats['pinned_messages'])); ?></div>
                        </div>
                        
                        <div class="iue-engagement-item">
                            <h4><?php esc_html_e('Active Recipients (30d)', 'init-user-engine'); ?></h4>
                            <div class="iue-engagement-value"><?php echo esc_html(number_format($advanced_stats['active_recipients'] ?? 0)); ?></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="iue-stats-right">
                
                <!-- Daily Activity Chart -->
                <div class="iue-stats-section">
                    <h2><?php esc_html_e('📊 Daily Activity', 'init-user-engine'); ?></h2>
                    <div class="iue-daily-chart">
                        <?php if (!empty($filtered_stats['daily_activity'])): ?>
                            <?php 
                            $max_daily = max($filtered_stats['daily_activity']);
                            foreach ($filtered_stats['daily_activity'] as $date => $count): 
                                $height = $max_daily > 0 ? (($count / $max_daily) * 120) : 0;
                                $day_name = wp_date('M j', strtotime($date));
                                $day_short = wp_date('D', strtotime($date));
                            ?>
                                <div class="iue-daily-column">
                                    <div class="iue-daily-bar" 
                                         style="height: <?php echo esc_attr($height); ?>px;" 
                                         title="<?php echo esc_attr(sprintf('%s: %d messages', $day_name, $count)); ?>">
                                    </div>
                                    <div class="iue-daily-label"><?php echo esc_html($day_short); ?></div>
                                    <div class="iue-daily-date"><?php echo esc_html(wp_date('j', strtotime($date))); ?></div>
                                    <div class="iue-daily-count"><?php echo esc_html($count); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="iue-no-data"><?php esc_html_e('No activity data for selected period.', 'init-user-engine'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Recipients -->
                <div class="iue-stats-section">
                    <h2><?php esc_html_e('🏆 Top Recipients', 'init-user-engine'); ?></h2>
                    <?php if (!empty($stats['top_recipients'])): ?>
                        <div class="iue-top-recipients">
                            <?php foreach ($stats['top_recipients'] as $index => $recipient): ?>
                                <div class="iue-recipient-item iue-rank-<?php echo esc_attr($index + 1); ?>">
                                    <div class="iue-recipient-rank">#<?php echo esc_html($index + 1); ?></div>
                                    <div class="iue-recipient-info">
                                        <div class="iue-recipient-name"><?php echo esc_html($recipient['display_name']); ?></div>
                                        <div class="iue-recipient-details">
                                            <span class="iue-total-messages"><?php echo esc_html(number_format($recipient['total_messages'])); ?> <?php esc_html_e('total', 'init-user-engine'); ?></span>
                                            <?php if ($recipient['unread_count'] > 0): ?>
                                                <span class="iue-unread-count"><?php echo esc_html($recipient['unread_count']); ?> <?php esc_html_e('unread', 'init-user-engine'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="iue-no-data"><?php esc_html_e('No recipient data available.', 'init-user-engine'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Recent Messages Summary -->
                <div class="iue-stats-section">
                    <h2><?php esc_html_e('📝 Recent Activity Summary', 'init-user-engine'); ?></h2>
                    <div class="iue-recent-summary">
                        <div class="iue-summary-item">
                            <span class="iue-summary-label"><?php esc_html_e('This Week:', 'init-user-engine'); ?></span>
                            <span class="iue-summary-value"><?php echo esc_html(number_format($stats['week_messages'])); ?></span>
                        </div>
                        <div class="iue-summary-item">
                            <span class="iue-summary-label"><?php esc_html_e('This Month:', 'init-user-engine'); ?></span>
                            <span class="iue-summary-value"><?php echo esc_html(number_format($stats['month_messages'])); ?></span>
                        </div>
                        <div class="iue-summary-item">
                            <span class="iue-summary-label"><?php esc_html_e('Peak Day:', 'init-user-engine'); ?></span>
                            <span class="iue-summary-value"><?php echo esc_html($advanced_stats['peak_day'] ?? __('N/A', 'init-user-engine')); ?></span>
                        </div>
                        <div class="iue-summary-item">
                            <span class="iue-summary-label"><?php esc_html_e('Avg Daily:', 'init-user-engine'); ?></span>
                            <span class="iue-summary-value"><?php echo esc_html(number_format($advanced_stats['avg_daily'] ?? 0, 1)); ?></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer Info -->
        <div class="iue-stats-footer">
            <div class="iue-refresh-info">
                <button type="button" class="button button-primary" onclick="location.reload();">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Refresh Data', 'init-user-engine'); ?>
                </button>
                <span class="iue-last-updated">
                    <?php 
                    // translators: %s is the formatted date and time when the data was last updated
                    echo esc_html(sprintf(__('Last updated: %s', 'init-user-engine'), wp_date('M j, Y H:i')));
                    ?>
                </span>
            </div>
        </div>
    </div>
    <?php
}

// Get comprehensive inbox statistics
function init_plugin_suite_user_engine_get_comprehensive_inbox_stats() {
    global $wpdb;
    $table = init_plugin_suite_user_engine_get_inbox_table();
    
    $stats = [];
    
    // Basic counts
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $stats['total_messages'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $stats['unread_messages'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'unread'");
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $stats['today_messages'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE created_at >= %d", strtotime('today', current_time('timestamp'))));
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $stats['week_messages'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE created_at >= %d", strtotime('monday this week', current_time('timestamp'))));
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $stats['month_messages'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE created_at >= %d", strtotime('first day of this month', current_time('timestamp'))));
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $stats['total_recipients'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$table}");
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $stats['pinned_messages'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE pinned = 1");
    
    // Percentages
    $stats['unread_percentage'] = $stats['total_messages'] > 0 ? round(($stats['unread_messages'] / $stats['total_messages']) * 100, 1) : 0;
    $stats['read_percentage'] = 100 - $stats['unread_percentage'];
    $stats['avg_messages_per_user'] = $stats['total_recipients'] > 0 ? round($stats['total_messages'] / $stats['total_recipients'], 1) : 0;
    
    // Message types
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $message_types = $wpdb->get_results("SELECT type, COUNT(*) as count FROM {$table} GROUP BY type ORDER BY count DESC", ARRAY_A);
    $stats['message_types'] = [];
    foreach ($message_types as $type) {
        $stats['message_types'][$type['type']] = (int) $type['count'];
    }
    
    // Priority levels
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $priority_levels = $wpdb->get_results("SELECT priority, COUNT(*) as count FROM {$table} GROUP BY priority ORDER BY count DESC", ARRAY_A);
    $stats['priority_levels'] = [];
    foreach ($priority_levels as $priority) {
        $stats['priority_levels'][$priority['priority']] = (int) $priority['count'];
    }
    
    // Top recipients
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $top_recipients = $wpdb->get_results("
        SELECT 
            i.user_id,
            u.display_name,
            COUNT(*) as total_messages,
            SUM(CASE WHEN i.status = 'unread' THEN 1 ELSE 0 END) as unread_count
        FROM {$table} i
        LEFT JOIN {$wpdb->users} u ON i.user_id = u.ID
        GROUP BY i.user_id
        ORDER BY total_messages DESC
        LIMIT 10
    ", ARRAY_A);
    // phpcs:enable
    
    $stats['top_recipients'] = [];
    foreach ($top_recipients as $recipient) {
        $stats['top_recipients'][] = [
            'user_id' => (int) $recipient['user_id'],
            'display_name' => $recipient['display_name'] ?: __('Unknown User', 'init-user-engine'),
            'total_messages' => (int) $recipient['total_messages'],
            'unread_count' => (int) $recipient['unread_count']
        ];
    }
    
    return $stats;
}

// Get filtered statistics based on date range
function init_plugin_suite_user_engine_get_filtered_inbox_stats($range = '7days') {
    global $wpdb;
    $table = init_plugin_suite_user_engine_get_inbox_table();
    
    $stats = [];
    
    // Determine date range
    switch ($range) {
        case '7days':
            $days = 7;
            break;
        case '30days':
            $days = 30;
            break;
        case '90days':
            $days = 90;
            break;
        case 'all':
        default:
            $days = null;
            break;
    }
    
    // Daily activity
    $stats['daily_activity'] = [];
    
    if ($days) {
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = wp_date('Y-m-d', strtotime("-{$i} days"));
            $day_start = strtotime($date, current_time('timestamp'));
            $day_end = $day_start + DAY_IN_SECONDS - 1;
            
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE created_at BETWEEN %d AND %d",
                $day_start, $day_end
            ));
            // phpcs:enable
            
            $stats['daily_activity'][$date] = $count;
        }
    } else {
        // For "all time", get last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = wp_date('Y-m-d', strtotime("-{$i} days"));
            $day_start = strtotime($date, current_time('timestamp'));
            $day_end = $day_start + DAY_IN_SECONDS - 1;
            
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE created_at BETWEEN %d AND %d",
                $day_start, $day_end
            ));
            // phpcs:enable
            
            $stats['daily_activity'][$date] = $count;
        }
    }
    
    return $stats;
}

// Get advanced analytics
function init_plugin_suite_user_engine_get_advanced_inbox_analytics() {
    global $wpdb;
    $table = init_plugin_suite_user_engine_get_inbox_table();
    
    $analytics = [];
    
    // Active recipients in last 30 days
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $analytics['active_recipients'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM {$table} WHERE created_at >= %d",
        current_time('timestamp') - (30 * DAY_IN_SECONDS)
    ));
    // phpcs:enable
    
    // Peak day (day with most messages)
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $peak_day_data = $wpdb->get_row($wpdb->prepare("
        SELECT 
            DATE(FROM_UNIXTIME(created_at)) as date,
            COUNT(*) as count
        FROM {$table}
        WHERE created_at >= %d
        GROUP BY DATE(FROM_UNIXTIME(created_at))
        ORDER BY count DESC
        LIMIT 1
    ", current_time('timestamp') - (90 * DAY_IN_SECONDS)), ARRAY_A);
    // phpcs:enable
    
    if ($peak_day_data) {
        $analytics['peak_day'] = wp_date('M j, Y', strtotime($peak_day_data['date'])) . ' (' . $peak_day_data['count'] . ')';
    } else {
        $analytics['peak_day'] = __('N/A', 'init-user-engine');
    }
    
    // Average daily messages (last 30 days)
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $avg_daily = $wpdb->get_var($wpdb->prepare("
        SELECT AVG(daily_count) FROM (
            SELECT COUNT(*) as daily_count
            FROM {$table}
            WHERE created_at >= %d
            GROUP BY DATE(FROM_UNIXTIME(created_at))
        ) daily_stats
    ", current_time('timestamp') - (30 * DAY_IN_SECONDS)));
    // phpcs:enable
    
    $analytics['avg_daily'] = $avg_daily ? round($avg_daily, 1) : 0;
    
    return $analytics;
}

// Add dashboard widget (simplified version)
add_action('wp_dashboard_setup', 'init_plugin_suite_user_engine_add_inbox_dashboard_widget');
function init_plugin_suite_user_engine_add_inbox_dashboard_widget() {
    if (current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'init_user_engine_inbox_stats_widget',
            __('Inbox Overview', 'init-user-engine'),
            'init_plugin_suite_user_engine_render_inbox_dashboard_widget'
        );
    }
}

// Simplified dashboard widget (links to full page) with caching
function init_plugin_suite_user_engine_render_inbox_dashboard_widget() {
    global $wpdb;
    $table = init_plugin_suite_user_engine_get_inbox_table();
    
    // Check if table exists
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        echo '<div style="text-align: center; padding: 20px; color: #d63638;">';
        echo '<h3>' . esc_html__('📭 Inbox Table Not Found', 'init-user-engine') . '</h3>';
        echo '<p>' . esc_html__('Please check your plugin installation.', 'init-user-engine') . '</p>';
        echo '</div>';
        return;
    }
    
    // Cache key for dashboard stats
    $cache_key = 'iue_dashboard_stats';
    $cache_expiry = 5 * MINUTE_IN_SECONDS; // Cache for 5 minutes
    
    // Try to get cached data
    $cached_stats = get_transient($cache_key);
    
    if (false === $cached_stats) {
        // Get basic stats from database
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total_messages = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $unread_messages = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'unread'");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $today_messages = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE created_at >= %d", strtotime('today')));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total_recipients = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$table}");
        
        // Calculate read percentage
        $read_percentage = $total_messages > 0 ? round((($total_messages - $unread_messages) / $total_messages) * 100, 1) : 0;
        
        // Calculate average messages per user
        $avg_per_user = $total_recipients > 0 ? round($total_messages / $total_recipients, 1) : 0;
        
        // Store in cache
        $cached_stats = array(
            'total_messages' => $total_messages,
            'unread_messages' => $unread_messages,
            'today_messages' => $today_messages,
            'total_recipients' => $total_recipients,
            'read_percentage' => $read_percentage,
            'avg_per_user' => $avg_per_user,
            'cached_at' => current_time('timestamp')
        );
        
        set_transient($cache_key, $cached_stats, $cache_expiry);
    }
    
    // Extract cached values
    extract($cached_stats);
    
    ?>
    <div class="iue-dashboard-widget">
        <div class="iue-widget-stats">
            <div class="iue-widget-stat">
                <span class="iue-stat-number"><?php echo esc_html(number_format($total_messages)); ?></span>
                <span class="iue-stat-label"><?php esc_html_e('Total Messages', 'init-user-engine'); ?></span>
            </div>
            <div class="iue-widget-stat">
                <span class="iue-stat-number iue-unread"><?php echo esc_html(number_format($unread_messages)); ?></span>
                <span class="iue-stat-label"><?php esc_html_e('Unread', 'init-user-engine'); ?></span>
            </div>
            <div class="iue-widget-stat">
                <span class="iue-stat-number iue-today"><?php echo esc_html(number_format($today_messages)); ?></span>
                <span class="iue-stat-label"><?php esc_html_e('Today', 'init-user-engine'); ?></span>
            </div>
            <div class="iue-widget-stat">
                <span class="iue-stat-number iue-recipients"><?php echo esc_html(number_format($total_recipients)); ?></span>
                <span class="iue-stat-label"><?php esc_html_e('Recipients', 'init-user-engine'); ?></span>
            </div>
        </div>
        
        <div class="iue-widget-summary">
            <div class="iue-summary-item">
                <span class="iue-summary-label"><?php esc_html_e('Read Rate:', 'init-user-engine'); ?></span>
                <span class="iue-summary-value"><?php echo esc_html($read_percentage); ?>%</span>
            </div>
            <div class="iue-summary-item">
                <span class="iue-summary-label"><?php esc_html_e('Avg per User:', 'init-user-engine'); ?></span>
                <span class="iue-summary-value"><?php echo esc_html($avg_per_user); ?></span>
            </div>
        </div>
        
        <div class="iue-widget-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=init-user-engine-inbox-stats')); ?>" class="button button-primary">
                <?php esc_html_e('View Full Statistics', 'init-user-engine'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=init-user-engine-send-notification')); ?>" class="button">
                <?php esc_html_e('Send Notification', 'init-user-engine'); ?>
            </a>
        </div>
    </div>
    <?php

    wp_enqueue_style( 'iue-send-notice-style', INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'css/admin.css', [], INIT_PLUGIN_SUITE_IUE_VERSION );
}
