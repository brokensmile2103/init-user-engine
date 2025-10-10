<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Add settings page to admin menu
add_action( 'admin_menu', function () {
	add_menu_page(
		__( 'Init User Engine', 'init-user-engine' ),
		__( 'User Engine', 'init-user-engine' ),
		'manage_options',
		INIT_PLUGIN_SUITE_IUE_SLUG,
		'init_plugin_suite_user_engine_render_settings_page',
		'dashicons-admin-users',
		102
	);

	add_submenu_page(
		INIT_PLUGIN_SUITE_IUE_SLUG,
		__( 'Settings', 'init-user-engine' ),
		__( 'Settings', 'init-user-engine' ),
		'manage_options',
		INIT_PLUGIN_SUITE_IUE_SLUG,
		'init_plugin_suite_user_engine_render_settings_page'
	);

	add_submenu_page(
		INIT_PLUGIN_SUITE_IUE_SLUG,
		__( 'Send Notification', 'init-user-engine' ),
		__( 'Send Notification', 'init-user-engine' ),
		'manage_options',
		'init-user-engine-send-notification',
		'init_plugin_suite_user_engine_render_send_notification_page'
	);

	require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'tools/send-notification.php';

	add_submenu_page(
        INIT_PLUGIN_SUITE_IUE_SLUG,
        __('Inbox Statistics', 'init-user-engine'),
        __('Inbox Statistics', 'init-user-engine'),
        'manage_options',
        'init-user-engine-inbox-stats',
        'init_plugin_suite_user_engine_render_inbox_stats_page'
    );

    require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'tools/inbox-statistics.php';

	add_submenu_page(
		INIT_PLUGIN_SUITE_IUE_SLUG,
		__( 'Top-up Coin/Cash', 'init-user-engine' ),
		__( 'Top-up Coin/Cash', 'init-user-engine' ),
		'manage_options',
		'init-user-engine-topup',
		'init_plugin_suite_user_engine_render_topup_page'
	);

	require_once INIT_PLUGIN_SUITE_IUE_INCLUDES_PATH . 'tools/topup.php';

} );

// Register settings
add_action( 'admin_init', function () {
	register_setting(
		INIT_PLUGIN_SUITE_IUE_OPTION,
		INIT_PLUGIN_SUITE_IUE_OPTION,
		'init_plugin_suite_user_engine_sanitize_settings'
	);
} );

// Sanitize input
function init_plugin_suite_user_engine_sanitize_settings( $input ) {
	$output = [];

	$output['theme_color'] 				 = sanitize_hex_color( $input['theme_color'] ?? '#0073aa' );

	$output['label_coin']           	 = sanitize_text_field( $input['label_coin'] ?? 'Coin' );
	$output['label_cash']           	 = sanitize_text_field( $input['label_cash'] ?? 'Cash' );

	$output['hide_admin_bar_subscriber'] = ! empty( $input['hide_admin_bar_subscriber'] ) ? 1 : 0;
	$output['disable_gravatar'] 		 = ! empty( $input['disable_gravatar'] ) ? 1 : 0;
	$output['disable_captcha'] 			 = ! empty( $input['disable_captcha'] ) ? 1 : 0;

	$output['turnstile_site_key']   	 = sanitize_text_field( $input['turnstile_site_key'] ?? '' );
	$output['turnstile_secret_key'] 	 = sanitize_text_field( $input['turnstile_secret_key'] ?? '' );

	$turnstile_theme 					 = $input['turnstile_theme'] ?? 'auto';
	$output['turnstile_theme'] 			 = in_array( $turnstile_theme, [ 'auto', 'light', 'dark' ], true ) ? $turnstile_theme : 'auto';

	$output['checkin_coin']         	 = absint( $input['checkin_coin'] ?? 10 );
	$output['checkin_exp']          	 = absint( $input['checkin_exp'] ?? 50 );
	$output['checkin_cash']         	 = absint( $input['checkin_cash'] ?? 0 );

	$output['comment_exp']       		 = absint( $input['comment_exp'] ?? 10 );
	$output['comment_coin']      		 = absint( $input['comment_coin'] ?? 2 );
	$output['comment_daily_cap'] 		 = absint( $input['comment_daily_cap'] ?? 0 );

	$output['online_minutes']       	 = absint( $input['online_minutes'] ?? 10 );
	$output['online_coin']          	 = absint( $input['online_coin'] ?? 100 );
	$output['online_exp']           	 = absint( $input['online_exp'] ?? 50 );
	$output['online_cash']          	 = absint( $input['online_cash'] ?? 0 );

	$output['custom_register_url']  	 = esc_url_raw( $input['custom_register_url'] ?? '' );
	$output['custom_lostpass_url']  	 = esc_url_raw( $input['custom_lostpass_url'] ?? '' );

	for ( $i = 1; $i <= 6; $i++ ) {
		$key = 'vip_price_' . $i;

		$default_prices = [
			1 => 7000,
			2 => 30000,
			3 => 90000,
			4 => 180000,
			5 => 360000,
			6 => 999999,
		];

		$output[ $key ] = absint( $input[ $key ] ?? $default_prices[ $i ] );
	}

	$output['vip_bonus_coin'] 	= absint( $input['vip_bonus_coin'] ?? 0 );
	$output['vip_bonus_exp']  	= absint( $input['vip_bonus_exp'] ?? 0 );

	$output['ref_reward_coin']  = absint( $input['ref_reward_coin'] ?? 100 );
	$output['ref_reward_exp']   = absint( $input['ref_reward_exp'] ?? 50 );
	$output['ref_reward_cash']  = absint( $input['ref_reward_cash'] ?? 0 );
	$output['ref_new_coin']     = absint( $input['ref_new_coin'] ?? 50 );
	$output['ref_new_exp']      = absint( $input['ref_new_exp'] ?? 20 );
	$output['ref_new_cash']     = absint( $input['ref_new_cash'] ?? 0 );

	return $output;
}

// Render settings page
function init_plugin_suite_user_engine_render_settings_page() {
	$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Init User Engine Settings', 'init-user-engine' ); ?></h1>

		<?php settings_errors(); ?>

		<form method="post" action="options.php">
			<?php settings_fields( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Theme Color', 'init-user-engine' ); ?></th>
					<td>
						<input type="text" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[theme_color]"
							value="<?php echo esc_attr( $options['theme_color'] ?? '#0073aa' ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Used as the main highlight color in user modals.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Coin Label', 'init-user-engine' ); ?></th>
					<td>
						<input type="text" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[label_coin]"
							value="<?php echo esc_attr( $options['label_coin'] ?? 'Coin' ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Singular label used to represent the virtual currency (e.g., Xu, Coin).', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cash Label', 'init-user-engine' ); ?></th>
					<td>
						<input type="text" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[label_cash]"
							value="<?php echo esc_attr( $options['label_cash'] ?? 'Cash' ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Label for premium currency or real-money equivalent (e.g., Cash, Kim cương).', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Hide Admin Bar (Subscriber)', 'init-user-engine' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[hide_admin_bar_subscriber]"
								value="1"
								<?php checked( $options['hide_admin_bar_subscriber'] ?? 1, 1 ); ?>
							/>
							<?php esc_html_e( 'Hide admin bar on frontend for users with Subscriber role.', 'init-user-engine' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Disable Gravatar Completely', 'init-user-engine' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[disable_gravatar]"
								value="1"
								<?php checked( $options['disable_gravatar'] ?? 0, 1 ); ?>
							/>
							<?php esc_html_e( 'Disable all Gravatar calls and use a local default SVG instead.', 'init-user-engine' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Improves performance by preventing external requests to gravatar.com.', 'init-user-engine' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Disable Captcha', 'init-user-engine' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[disable_captcha]"
								value="1"
								<?php checked( $options['disable_captcha'] ?? 0, 1 ); ?>
							/>
							<?php esc_html_e( 'Turn off captcha validation during registration.', 'init-user-engine' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Use only if you trust your traffic or rely on external protection (e.g., Cloudflare).', 'init-user-engine' ); ?></p>
					</td>
				</tr>

				<tr>
					<th colspan="2"><h2><?php esc_html_e( 'Cloudflare Turnstile', 'init-user-engine' ); ?></h2></th>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Site Key', 'init-user-engine' ); ?></th>
					<td>
						<input type="text" class="regular-text"
							name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[turnstile_site_key]"
							value="<?php echo esc_attr( $options['turnstile_site_key'] ?? '' ); ?>"
							autocomplete="off" />
						<p class="description">
							<?php esc_html_e( 'Public site key from your Cloudflare Turnstile dashboard. Required to render the widget on the frontend.', 'init-user-engine' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Secret Key', 'init-user-engine' ); ?></th>
					<td>
						<input type="password" class="regular-text"
							name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[turnstile_secret_key]"
							value="<?php echo esc_attr( $options['turnstile_secret_key'] ?? '' ); ?>"
							autocomplete="off" />
						<p class="description">
							<?php esc_html_e( 'Private secret key used for server-side verification of Turnstile responses.', 'init-user-engine' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Theme', 'init-user-engine' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[turnstile_theme]">
							<?php
							$current_theme = $options['turnstile_theme'] ?? 'auto';
							$themes = [
								'auto'  => __( 'Auto (match system/OS preference)', 'init-user-engine' ),
								'light' => __( 'Light mode', 'init-user-engine' ),
								'dark'  => __( 'Dark mode', 'init-user-engine' ),
							];
							foreach ( $themes as $val => $label ) {
								printf(
									'<option value="%1$s"%2$s>%3$s</option>',
									esc_attr( $val ),
									selected( $current_theme, $val, false ),
									esc_html( $label )
								);
							}
							?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Choose the visual style of the captcha widget (auto, light, or dark).', 'init-user-engine' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th colspan="2"><h2><?php esc_html_e( 'Custom Links', 'init-user-engine' ); ?></h2></th>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Custom Register URL', 'init-user-engine' ); ?></th>
					<td>
						<input type="url" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[custom_register_url]"
							value="<?php echo esc_attr( $options['custom_register_url'] ?? '' ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Redirect here when user clicks "Register". Leave blank to use default wp-login.php?action=register.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Custom Lost Password URL', 'init-user-engine' ); ?></th>
					<td>
						<input type="url" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[custom_lostpass_url]"
							value="<?php echo esc_attr( $options['custom_lostpass_url'] ?? '' ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Redirect here when user clicks "Lost password". Leave blank to use default wp-login.php?action=lostpassword.', 'init-user-engine' ); ?></p>
					</td>
				</tr>

				<tr>
					<th colspan="2"><h2><?php esc_html_e( 'Check-in Reward', 'init-user-engine' ); ?></h2></th>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Coin Reward', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[checkin_coin]"
							value="<?php echo esc_attr( $options['checkin_coin'] ?? 10 ); ?>" />
						<p class="description"><?php esc_html_e( 'Coin awarded each time the user checks in daily.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'EXP Reward', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[checkin_exp]"
							value="<?php echo esc_attr( $options['checkin_exp'] ?? 50 ); ?>" />
						<p class="description"><?php esc_html_e( 'EXP gained from daily check-in.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cash Reward', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[checkin_cash]"
							value="<?php echo esc_attr( $options['checkin_cash'] ?? 0 ); ?>" />
						<p class="description"><?php esc_html_e( 'Cash rewarded upon check-in (rarely used, mostly 0).', 'init-user-engine' ); ?></p>
					</td>
				</tr>

				<tr>
					<th colspan="2"><h2><?php esc_html_e( 'Comment Reward', 'init-user-engine' ); ?></h2></th>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'EXP per Comment', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0"
							name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[comment_exp]"
							value="<?php echo esc_attr( $options['comment_exp'] ?? 10 ); ?>" />
						<p class="description"><?php esc_html_e( 'EXP awarded for each valid comment.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Coin per Comment', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0"
							name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[comment_coin]"
							value="<?php echo esc_attr( $options['comment_coin'] ?? 2 ); ?>" />
						<p class="description"><?php esc_html_e( 'Coin awarded for each valid comment.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Daily Comment Cap', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0"
							name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[comment_daily_cap]"
							value="<?php echo esc_attr( $options['comment_daily_cap'] ?? 0 ); ?>" />
						<p class="description"><?php esc_html_e( 'Maximum number of comments per day eligible for rewards. 0 = unlimited. System uses daily check-in as reset anchor.', 'init-user-engine' ); ?></p>
					</td>
				</tr>

				<tr>
					<th colspan="2"><h2><?php esc_html_e( 'Online Reward', 'init-user-engine' ); ?></h2></th>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Minutes Required', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[online_minutes]"
							value="<?php echo esc_attr( $options['online_minutes'] ?? 10 ); ?>" />
						<p class="description"><?php esc_html_e( 'Minimum minutes user must stay online to receive reward.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Coin Reward', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[online_coin]"
							value="<?php echo esc_attr( $options['online_coin'] ?? 100 ); ?>" />
						<p class="description"><?php esc_html_e( 'Coin rewarded when online duration is met.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'EXP Reward', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[online_exp]"
							value="<?php echo esc_attr( $options['online_exp'] ?? 50 ); ?>" />
						<p class="description"><?php esc_html_e( 'EXP rewarded after being online for required time.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cash Reward', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[online_cash]"
							value="<?php echo esc_attr( $options['online_cash'] ?? 0 ); ?>" />
						<p class="description"><?php esc_html_e( 'Cash rewarded for being active online.', 'init-user-engine' ); ?></p>
					</td>
				</tr>

				<tr>
					<th colspan="2"><h2><?php esc_html_e( 'VIP Pricing (by Coin)', 'init-user-engine' ); ?></h2></th>
				</tr>

				<?php
				$vip_labels = [
					1 => '7 days',
					2 => '30 days',
					3 => '90 days',
					4 => '180 days',
					5 => '360 days',
					6 => 'Lifetime',
				];

				$default_prices = [
					1 => 7000,
					2 => 30000,
					3 => 90000,
					4 => 180000,
					5 => 360000,
					6 => 999999,
				];

				foreach ( $vip_labels as $i => $label ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<td>
							<?php
							$field_name = sprintf( '%s[vip_price_%d]', INIT_PLUGIN_SUITE_IUE_OPTION, $i );
							$price_value = $options[ 'vip_price_' . $i ] ?? $default_prices[ $i ];
							?>
							<input type="number" min="0" name="<?php echo esc_attr( $field_name ); ?>"
							       value="<?php echo esc_attr( $price_value ); ?>" />
							<p class="description"><?php esc_html_e( 'Set to 0 to disable this VIP package.', 'init-user-engine' ); ?></p>
						</td>
					</tr>
				<?php endforeach; ?>

				<tr>
					<th colspan="2"><h2><?php esc_html_e( 'VIP Bonus', 'init-user-engine' ); ?></h2></th>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Bonus Coin (%)', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[vip_bonus_coin]"
						       value="<?php echo esc_attr( $options['vip_bonus_coin'] ?? 0 ); ?>" />
						<p class="description"><?php esc_html_e( 'Extra Coin gained when VIP, in percent.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Bonus EXP (%)', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[vip_bonus_exp]"
						       value="<?php echo esc_attr( $options['vip_bonus_exp'] ?? 0 ); ?>" />
						<p class="description"><?php esc_html_e( 'Extra EXP gained when VIP, in percent.', 'init-user-engine' ); ?></p>
					</td>
				</tr>

				<tr>
					<th colspan="2"><h2><?php esc_html_e( 'Referral Reward', 'init-user-engine' ); ?></h2></th>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Coin for Referrer', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[ref_reward_coin]" value="<?php echo esc_attr( $options['ref_reward_coin'] ?? 100 ); ?>" />
						<p class="description"><?php esc_html_e( 'Coin reward given to the person who shared their referral link.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'EXP for Referrer', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[ref_reward_exp]" value="<?php echo esc_attr( $options['ref_reward_exp'] ?? 50 ); ?>" />
						<p class="description"><?php esc_html_e( 'EXP granted to referrer when someone registers through their link.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cash for Referrer', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[ref_reward_cash]" value="<?php echo esc_attr( $options['ref_reward_cash'] ?? 0 ); ?>" />
						<p class="description"><?php esc_html_e( 'Cash awarded to the referrer.', 'init-user-engine' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Coin for New User', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[ref_new_coin]" value="<?php echo esc_attr( $options['ref_new_coin'] ?? 50 ); ?>" />
						<p class="description"><?php esc_html_e( 'Coin bonus for new user who signs up via referral.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'EXP for New User', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[ref_new_exp]" value="<?php echo esc_attr( $options['ref_new_exp'] ?? 20 ); ?>" />
						<p class="description"><?php esc_html_e( 'EXP bonus for the new referred user.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cash for New User', 'init-user-engine' ); ?></th>
					<td>
						<input type="number" min="0" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_IUE_OPTION ); ?>[ref_new_cash]" value="<?php echo esc_attr( $options['ref_new_cash'] ?? 0 ); ?>" />
						<p class="description"><?php esc_html_e( 'Cash given to new user (rarely used).', 'init-user-engine' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
