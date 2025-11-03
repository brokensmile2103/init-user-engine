<?php
/**
 * Plugin Name: Init User Engine
 * Plugin URI: https://inithtml.com/plugin/init-user-engine/
 * Description: Lightweight, gamified user engine with EXP, wallet, check-in, VIP, inbox, and referral – powered by REST API and Vanilla JS.
 * Version: 1.3.9
 * Author: Init HTML
 * Author URI: https://inithtml.com/
 * Text Domain: init-user-engine
 * Domain Path: /languages
 * Requires at least: 5.5
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

// =======================
// Constant Definitions
// =======================

define( 'INIT_PLUGIN_SUITE_IUE_VERSION',        '1.3.9' );
define( 'INIT_PLUGIN_SUITE_IUE_SLUG',           'init-user-engine' );
define( 'INIT_PLUGIN_SUITE_IUE_OPTION',         'init_plugin_suite_user_engine_settings' );
define( 'INIT_PLUGIN_SUITE_IUE_NAMESPACE',      'inituser/v1' );
define( 'INIT_PLUGIN_SUITE_IUE_URL',            plugin_dir_url( __FILE__ ) );
define( 'INIT_PLUGIN_SUITE_IUE_PATH',           plugin_dir_path( __FILE__ ) );
define( 'INIT_PLUGIN_SUITE_IUE_ASSETS_URL',     INIT_PLUGIN_SUITE_IUE_URL . 'assets/' );
define( 'INIT_PLUGIN_SUITE_IUE_ASSETS_PATH',    INIT_PLUGIN_SUITE_IUE_PATH . 'assets/' );
define( 'INIT_PLUGIN_SUITE_IUE_TEMPLATES_PATH', INIT_PLUGIN_SUITE_IUE_PATH . 'templates/' );
define( 'INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH',  INIT_PLUGIN_SUITE_IUE_PATH . 'includes/' );
define( 'INIT_PLUGIN_SUITE_IUE_REF_SALT',       987586218 );

// =======================
// Load Core Functions
// =======================

require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'init.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'core.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'exp.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'coin.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'cash.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'shortcode.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'rest-api.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'log.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'inbox.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'hooks.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'vip.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'referral.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'redeem-codes-handler.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'utils.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'user-metabox.php';
require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'settings-page.php';

if ( is_admin() ) {
    require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'ajax.php';
}

// ==========================
// Register Scripts & Data
// ==========================

// Guest
add_action( 'wp_enqueue_scripts', 'init_plugin_suite_user_engine_enqueue_guest_assets' );

function init_plugin_suite_user_engine_enqueue_guest_assets() {
    if ( is_user_logged_in() ) {
        return;
    }

    $settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );

    wp_enqueue_style(
        'init-user-engine-guest',
        INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'css/style-guest.css',
        [],
        INIT_PLUGIN_SUITE_IUE_VERSION
    );

    $theme_color = isset( $settings['theme_color'] ) ? sanitize_hex_color( $settings['theme_color'] ) : '#0073aa';
    $theme_active_color = init_plugin_suite_user_engine_darken_color( $theme_color, 20 );

    $custom_css = ":root {
        --iue-theme-color: {$theme_color};
        --iue-theme-active-color: {$theme_active_color};
    }";

    wp_add_inline_style( 'init-user-engine-guest', $custom_css );

    wp_enqueue_script(
        'init-user-engine-guest',
        INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'js/guest.js',
        [],
        INIT_PLUGIN_SUITE_IUE_VERSION,
        true
    );

    wp_localize_script( 'init-user-engine-guest', 'InitUserEngineData', [
        'rest_url' => esc_url_raw( rest_url( INIT_PLUGIN_SUITE_IUE_NAMESPACE ) ),
        'i18n'     => [
            'title_login'          => __( 'Login', 'init-user-engine' ),
            'title_register'       => __( 'Register', 'init-user-engine' ),
            'back_to_login'        => __( 'Back to login', 'init-user-engine' ),
            'register'             => __( 'Create a new account', 'init-user-engine' ),
            'registering'          => __( 'Registering...', 'init-user-engine' ),
            'register_success'     => __( 'Welcome! You can now log in.', 'init-user-engine' ),
            'username_too_short'   => __( 'Username must be at least 3 characters.', 'init-user-engine' ),
            'username_invalid'     => __( 'Username can only contain letters, numbers and underscores.', 'init-user-engine' ),
            'email_invalid'        => __( 'Please enter a valid email address.', 'init-user-engine' ),
            'password_too_short'   => __( 'Password must be at least 6 characters.', 'init-user-engine' ),
            'password_weak'        => __( 'Password must contain both letters and numbers.', 'init-user-engine' ),
            'captcha_required'     => __( 'Please complete the captcha.', 'init-user-engine' ),
            'captcha_not_loaded'   => __( 'Captcha not loaded. Please wait...', 'init-user-engine' ),
            'captcha_expired'      => __( 'Captcha expired. Loading new one...', 'init-user-engine' ),
            'too_many_attempts'    => __( 'Too many attempts. Please wait before trying again.', 'init-user-engine' ),
            'registration_failed'  => __( 'Registration failed', 'init-user-engine' ),
            'placeholder_username' => __( 'Username or Email Address', 'init-user-engine' ),
            'placeholder_password' => __( 'Password', 'init-user-engine' ),
            'show_password'        => __( 'Show password', 'init-user-engine' ),
            'hide_password'        => __( 'Hide password', 'init-user-engine' ),
        ]
    ] );
}

// Login modal
add_action( 'wp_footer', 'init_plugin_suite_user_engine_render_login_modal' );
function init_plugin_suite_user_engine_render_login_modal() {
    if ( is_user_logged_in() ) return;

    ?>
    <div id="init-user-engine-login-modal">
        <div class="iue-overlay"></div>
        <div class="iue-content">
            <div class="iue-header">
                <h3><?php esc_html_e( 'Login', 'init-user-engine' ); ?></h3>
                <button class="iue-close" id="init-user-engine-modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24"><path d="m21 21-9-9m0 0L3 3m9 9 9-9m-9 9-9 9" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                </button>
            </div>
            <div class="iue-body">
                <?php init_plugin_suite_user_engine_load_template( 'login-form' ); ?>
            </div>
        </div>
    </div>
    <?php
}

// Logged-in
add_action( 'wp_enqueue_scripts', 'init_plugin_suite_user_engine_enqueue_loggedin_assets' );
function init_plugin_suite_user_engine_enqueue_loggedin_assets() {
    if ( ! is_user_logged_in() ) return;

    $settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );

    wp_enqueue_style(
        'init-user-engine-user',
        INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'css/style-user.css',
        [],
        INIT_PLUGIN_SUITE_IUE_VERSION
    );

    $theme_color = isset( $settings['theme_color'] ) ? sanitize_hex_color( $settings['theme_color'] ) : '#0073aa';
    $theme_active_color = init_plugin_suite_user_engine_darken_color( $theme_color, 20 );

    $custom_css = ":root {
        --iue-theme-color: {$theme_color};
        --iue-theme-active-color: {$theme_active_color};
    }";

    wp_add_inline_style( 'init-user-engine-user', $custom_css );
    
    wp_enqueue_script(
        'init-user-engine-user',
        INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'js/member.js',
        [],
        INIT_PLUGIN_SUITE_IUE_VERSION,
        true
    );

    $label_coin     = array_key_exists( 'label_coin', $settings ) ? sanitize_text_field( $settings['label_coin'] ) : 'Coin';
    $label_cash     = array_key_exists( 'label_cash', $settings ) ? sanitize_text_field( $settings['label_cash'] ) : 'Cash';
    $user_id        = get_current_user_id();
    $is_vip         = init_plugin_suite_user_engine_is_vip();

    $online_minutes = array_key_exists( 'online_minutes', $settings ) ? absint( $settings['online_minutes'] ) : 10;

    // VIP rút ngắn 1 nửa
    if ( $is_vip ) {
        $online_minutes = max( 1, ceil( $online_minutes / 2 ) );
    }

    $online_minutes = apply_filters( 'init_plugin_suite_user_engine_online_minutes', $online_minutes, $user_id, $is_vip );
    $cash_to_coin_rate = isset( $settings['rate_coin_per_cash'] ) ? (float) $settings['rate_coin_per_cash'] : 0;

    $avatar_max_upload_mb = 10;
    if ( isset( $settings['avatar_max_upload_mb'] ) && is_numeric( $settings['avatar_max_upload_mb'] ) ) {
        $avatar_max_upload_mb = max( 0, (float) $settings['avatar_max_upload_mb'] );
    }

    $localized_data = [
        'rest_url'             => esc_url_raw( rest_url( INIT_PLUGIN_SUITE_IUE_NAMESPACE ) ),
        'nonce'                => wp_create_nonce( 'wp_rest' ),
        'current_user'         => $user_id,
        'user_coin'            => init_plugin_suite_user_engine_get_coin( $user_id ),
        'online_minutes'       => $online_minutes,
        'label_coin'           => $label_coin,
        'label_cash'           => $label_cash,
        'rate_coin_per_cash'   => $cash_to_coin_rate,
        'user_cash'            => init_plugin_suite_user_engine_get_cash( $user_id ),
        'can_upload_avatar'    => init_plugin_suite_user_engine_can_upload_avatar( $user_id ),
        'avatar_max_upload_mb' => $avatar_max_upload_mb,

        'is_vip'         => $is_vip, // bool
        'vip_expiry'     => init_plugin_suite_user_engine_get_vip_expiry(),
        'vip_prices'     => apply_filters( 'init_plugin_suite_user_engine_vip_prices', [
            'vip_price_1' => absint( $settings['vip_price_1'] ?? 7000 ),
            'vip_price_2' => absint( $settings['vip_price_2'] ?? 30000 ),
            'vip_price_3' => absint( $settings['vip_price_3'] ?? 90000 ),
            'vip_price_4' => absint( $settings['vip_price_4'] ?? 180000 ),
            'vip_price_5' => absint( $settings['vip_price_5'] ?? 360000 ),
            'vip_price_6' => absint( $settings['vip_price_6'] ?? 999999 ),
        ] ),

        'referral_code'    => init_plugin_suite_user_engine_encode_user_id( $user_id ),
        'referral_rewards' => apply_filters( 'init_plugin_suite_user_engine_referral_rewards', [
            'ref_reward_coin' => absint( $settings['ref_reward_coin'] ?? 100 ),
            'ref_reward_exp'  => absint( $settings['ref_reward_exp']  ?? 50 ),
            'ref_reward_cash' => absint( $settings['ref_reward_cash'] ?? 0 ),

            'ref_new_coin'    => absint( $settings['ref_new_coin'] ?? 50 ),
            'ref_new_exp'     => absint( $settings['ref_new_exp']  ?? 20 ),
            'ref_new_cash'    => absint( $settings['ref_new_cash'] ?? 0 ),
        ] ),

        'i18n' => [
            'already_checked_in'       => __( 'Checked in', 'init-user-engine' ),
            'checkin_success'          => __( 'Checked in successfully!', 'init-user-engine' ),
            'reward_claimed'           => __( 'You received your reward!', 'init-user-engine' ),
            'reward_too_early'         => __( 'Still too early to claim!', 'init-user-engine' ),
            'checking_in'              => __( 'Checking in...', 'init-user-engine' ),
            'mark_all_read_success'    => __( 'All messages marked as read.', 'init-user-engine' ),
            'delete_all_success'       => __( 'All messages deleted.', 'init-user-engine' ),
            'error'                    => __( 'Error!', 'init-user-engine' ),
            'transaction_title'        => __( 'Transaction History', 'init-user-engine' ),
            'daily_task_title'         => __( 'Daily Tasks', 'init-user-engine' ),
            'daily_task_empty'         => __( 'No daily tasks found.', 'init-user-engine' ),
            'daily_task_load_error'    => __( 'Failed to load daily tasks.', 'init-user-engine' ),
            'inbox_title'              => __( 'Inbox', 'init-user-engine' ),
            'no_messages'              => __( 'No messages in your inbox.', 'init-user-engine' ),
            'mark_all_read'            => __( 'Mark All as Read', 'init-user-engine' ),
            'delete_all'               => __( 'Delete All', 'init-user-engine' ),
            'mark_as_read'             => __( 'Mark as Read', 'init-user-engine' ),
            'delete'                   => __( 'Delete', 'init-user-engine' ),
            'no_title'                 => __( 'No title', 'init-user-engine' ),
            'load_inbox_error'         => __( 'Failed to load inbox.', 'init-user-engine' ),
            'mark_all_read_success'    => __( 'All messages marked as read.', 'init-user-engine' ),
            'delete_all_success'       => __( 'All messages deleted.', 'init-user-engine' ),
            'confirm_action'           => __( 'Click again to confirm', 'init-user-engine' ),

            'vip_title'                => __( 'VIP Membership', 'init-user-engine' ),
            'vip_status_prefix'        => __( 'Current status:', 'init-user-engine' ),
            'vip_until'                => __( 'VIP until', 'init-user-engine' ),
            'vip_not'                  => __( 'Not a VIP', 'init-user-engine' ),

            'vip_7d'                   => __( 'VIP 7 days', 'init-user-engine' ),
            'vip_30d'                  => __( 'VIP 30 days', 'init-user-engine' ),
            'vip_90d'                  => __( 'VIP 90 days', 'init-user-engine' ),
            'vip_180d'                 => __( 'VIP 180 days', 'init-user-engine' ),
            'vip_360d'                 => __( 'VIP 360 days', 'init-user-engine' ),
            'vip_lifetime'             => __( 'VIP Lifetime', 'init-user-engine' ),

            'vip_note_title'           => __( 'Note:', 'init-user-engine' ),
            'vip_note_extend'          => __( 'VIP will be extended if purchased again before expiration.', 'init-user-engine' ),

            'vip_buy_btn'              => __( 'Buy Now', 'init-user-engine' ),
            'vip_purchase_success'     => __( 'VIP purchased successfully!', 'init-user-engine' ),
            'vip_purchase_fail'        => __( 'Could not purchase VIP package.', 'init-user-engine' ),
            'vip_error_generic'        => __( 'An error occurred during VIP purchase.', 'init-user-engine' ),
            'vip_unavailable'          => __( 'Unavailable', 'init-user-engine' ),

            'referral_title'           => __( 'Invite Friends', 'init-user-engine' ),
            'referral_heading'         => __( 'Invite your friends and earn rewards', 'init-user-engine' ),
            'referral_code_label'      => __( 'Your Referral Code', 'init-user-engine' ),
            'referral_copy'            => __( 'Copy Link', 'init-user-engine' ),
            'referral_copied'          => __( 'Copied!', 'init-user-engine' ),
            'referral_share'           => __( 'Share via', 'init-user-engine' ),
            'referral_bonus_note'      => __( 'You and your friend will receive bonus when they register.', 'init-user-engine' ),
            'referral_benefits'        => __( 'Referral Benefits', 'init-user-engine' ),
            'you_get'                  => __( 'You get:', 'init-user-engine' ),
            'friend_get'               => __( 'Your friend gets:', 'init-user-engine' ),
            'referral_history'         => __( 'Your Referral History', 'init-user-engine' ),
            'no_referrals'             => __( 'No referral data yet.', 'init-user-engine' ),
            'load_fail'                => __( 'Could not load referral history.', 'init-user-engine' ),

            'no_exp_log'               => __( 'No EXP activity yet.', 'init-user-engine' ),
            'exp_log_title'            => __( 'Experience Log', 'init-user-engine' ),
            'exp_log_load_fail'        => __( 'Failed to load experience log.', 'init-user-engine' ),
            'exp_log_exp'              => __( 'EXP', 'init-user-engine' ),
            'exp_log_unknown'          => __( 'Unknown', 'init-user-engine' ),

            'exchange_title'           => __( 'Exchange', 'init-user-engine' ),
            'exchange_disabled'        => __( 'Exchange is currently disabled.', 'init-user-engine' ),
            'exchange_rate'            => __( 'Rate', 'init-user-engine' ),
            'exchange_amount'          => __( 'Amount', 'init-user-engine' ),
            'exchange_receive'         => __( 'You will receive', 'init-user-engine' ),
            'exchange_max'             => __( 'Max', 'init-user-engine' ),
            'exchange_submit'          => __( 'Convert', 'init-user-engine' ),
            'exchange_processing'      => __( 'Processing...', 'init-user-engine' ),
            'exchange_invalid'         => __( 'Enter a valid amount.', 'init-user-engine' ),
            'exchange_insufficient'    => __( 'Not enough Cash.', 'init-user-engine' ),
            'exchange_success'         => __( 'Exchanged successfully!', 'init-user-engine' ),
            'exchange_error'           => __( 'Exchange failed.', 'init-user-engine' ),
            'exchange_note'            => __( 'Conversion is irreversible. Please review before confirming.', 'init-user-engine' ),

            'upload_avatar'            => __( 'Upload Avatar', 'init-user-engine' ),
            'avatar_drop_text'         => __( 'Drop image here or click to upload', 'init-user-engine' ),
            'avatar_save'              => __( 'Save Avatar', 'init-user-engine' ),
            'avatar_uploading'         => __( 'Uploading...', 'init-user-engine' ),
            'avatar_invalid'           => __( 'Please select a valid image.', 'init-user-engine' ),
            'avatar_too_large'         => sprintf( /* translators: %s = max upload size in MB */ __( 'Image too large (max %sMB)', 'init-user-engine' ), $avatar_max_upload_mb ),
            'avatar_upload_fail'       => __( 'Upload failed. Please try again.', 'init-user-engine' ),
            'avatar_remove'            => __( 'Remove Avatar', 'init-user-engine' ),
            'avatar_remove_confirm'    => __( 'Are you sure you want to remove your avatar?', 'init-user-engine' ),
            'avatar_removing'          => __( 'Removing...', 'init-user-engine' ),
            'avatar_remove_fail'       => __( 'Failed to remove avatar. Please try again.', 'init-user-engine' ),

            'edit_profile_title'       => __( 'Edit Profile', 'init-user-engine' ),
            'display_name'             => __( 'Display Name', 'init-user-engine' ),
            'display_name_placeholder' => __( 'Your public display name', 'init-user-engine' ),
            'bio'                      => __( 'Bio', 'init-user-engine' ),
            'bio_placeholder'          => __( 'Short self introduction', 'init-user-engine' ),
            'new_password'             => __( 'New Password', 'init-user-engine' ),
            'leave_blank_to_keep'      => __( 'Leave blank to keep current password', 'init-user-engine' ),

            'facebook_placeholder'     => __( 'https://facebook.com/yourprofile', 'init-user-engine' ),
            'twitter_placeholder'      => __( 'https://twitter.com/yourhandle', 'init-user-engine' ),
            'discord_placeholder'      => __( 'Your Discord username or invite', 'init-user-engine' ),
            'website_placeholder'      => __( 'https://yourwebsite.com', 'init-user-engine' ),

            'gender'                   => __( 'Gender', 'init-user-engine' ),
            'gender_unspecified'       => __( 'Prefer not to say', 'init-user-engine' ),
            'gender_male'              => __( 'Male', 'init-user-engine' ),
            'gender_female'            => __( 'Female', 'init-user-engine' ),
            'gender_other'             => __( 'Other', 'init-user-engine' ),

            'save'                     => __( 'Save', 'init-user-engine' ),
            'update_success'           => __( 'Profile updated successfully!', 'init-user-engine' ),
            'update_failed'            => __( 'Could not update profile.', 'init-user-engine' ),
            'fetch_profile_failed'     => __( 'Could not load profile data.', 'init-user-engine' ),
            'error_generic'            => __( 'An error occurred while updating.', 'init-user-engine' ),

            'redeem_now'                => __( 'Redeem now', 'init-user-engine' ),
            'redeem_title'              => __( 'Redeem Code', 'init-user-engine' ),
            'redeem_placeholder'        => __( 'Enter redeem code...', 'init-user-engine' ),
            'redeem_submit'             => __( 'Redeem', 'init-user-engine' ),
            'redeem_processing'         => __( 'Processing...', 'init-user-engine' ),

            'redeem_success'            => __( 'Redeem successful!', 'init-user-engine' ),
            'redeem_you_will_receive'   => __( 'You will receive:', 'init-user-engine' ),

            'redeem_invalid'            => __( 'Invalid redeem code.', 'init-user-engine' ),
            'redeem_expired'            => __( 'This code has expired.', 'init-user-engine' ),
            'redeem_not_started'        => __( 'This code is not active yet.', 'init-user-engine' ),
            'redeem_used'               => __( 'This code has already been used.', 'init-user-engine' ),
            'redeem_used_up'            => __( 'This code has already been used up.', 'init-user-engine' ),
            'redeem_assigned_other'     => __( 'This code is assigned to another user.', 'init-user-engine' ),
            'redeem_empty'              => __( 'Please enter a redeem code.', 'init-user-engine' ),

            'redeem_error'              => __( 'Failed to redeem code.', 'init-user-engine' ),
        ],
    ];

    $localized_data = apply_filters( 'init_plugin_suite_user_engine_localized_data', $localized_data, $user_id );
    wp_localize_script( 'init-user-engine-user', 'InitUserEngineData', $localized_data );
}

// ==========================
// Settings link
// ==========================

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'init_plugin_suite_user_engine_add_settings_link');
// Add a "Settings" link to the plugin row in the Plugins admin screen
function init_plugin_suite_user_engine_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=' . INIT_PLUGIN_SUITE_IUE_SLUG) . '">' . __('Settings', 'init-user-engine') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// ==========================
// Admin assets
// ==========================

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( strpos( $hook, 'init-user-engine' ) !== false ) {
        wp_enqueue_style( 'iue-send-notice-style', INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'css/admin.css', [], INIT_PLUGIN_SUITE_IUE_VERSION );
        wp_enqueue_script( 'iue-send-notice', INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'js/admin.js', [ 'jquery' ], INIT_PLUGIN_SUITE_IUE_VERSION, true );
        wp_localize_script( 'iue-send-notice', 'InitPluginSuiteUserEngineAdminNoticeData', [ 'nonce' => wp_create_nonce( 'iue_send_notice' ) ] );
    }
} );
