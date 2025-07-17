<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$user_id = absint( $iue_user_data['user_id'] ?? 0 );
if ( ! $user_id ) return;

$user     = get_userdata( $user_id );
$avatar   = $iue_user_data['avatar'] ?? '';
$coin     = absint( $iue_user_data['coin'] ?? 0 );
$cash     = absint( $iue_user_data['cash'] ?? 0 );
$level    = absint( $iue_user_data['level'] ?? 1 );
$today    = init_plugin_suite_user_engine_today();
$last     = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_last', '' );
$streak   = (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_streak', 0 );
$rewarded = (bool) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_rewarded', false );

$checked_in = ( $last === $today ) ? '1' : '0';
$show_timer = ( $checked_in === '1' && ! $rewarded );

$exp_now      = (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_exp', 0 );
$exp_required = init_plugin_suite_user_engine_exp_required( $level );

$unread_count = (int) init_plugin_suite_user_engine_get_unread_inbox_count( $user_id );
?>

<div class="iue-dashboard">
	<div class="iue-user-info">
		<div class="iue-user-avatar">
			<?php echo wp_kses_post( $avatar ); ?>
		</div>
		<div class="iue-user-meta">
			<div class="iue-username">
				<?php echo esc_html( $user->display_name ); ?>
				<a href="#"
					role="button"
					class="iue-level-badge-link"
					data-action="exp-log"
					data-exp="<?php echo esc_attr( $exp_now ); ?>"
					data-exp-max="<?php echo esc_attr( $exp_required ); ?>"
					data-level="<?php echo esc_attr( $level ); ?>"
				>
					<?php echo wp_kses_post( init_plugin_suite_user_engine_level_badge( $level ) ); ?>
				</a>
			</div>
			<ul class="iue-user-stats">
				<li class="iue-coin">
					<span class="iue-icon" data-iue-icon="coin"></span>
					<span class="iue-value-coin"><?php echo esc_html( $coin ); ?></span>
				</li>
				<li class="iue-cash">
					<span class="iue-icon" data-iue-icon="cash"></span>
					<span class="iue-value-cash"><?php echo esc_html( $cash ); ?></span>
				</li>
			</ul>
		</div>
	</div>

	<div class="iue-checkin-box" data-checkin="<?php echo esc_attr( $checked_in ); ?>" data-rewarded="<?php echo $rewarded ? '1' : '0'; ?>">
		<div class="iue-checkin-left">
			<span class="<?php echo esc_attr( 'iue-icon' . ( $streak > 2 ? ' iue-red-fire' : '' ) ); ?>" data-iue-icon="fire"></span>
			<span class="iue-checkin-streak"><?php echo esc_html( $streak ); ?></span>
		</div>
		<div class="iue-checkin-right">
			<button class="iue-checkin-button<?php echo ( $checked_in === '1' ? ' iue-hidden' : '' ); ?>" type="button">
				<?php echo esc_html__( 'Check In', 'init-user-engine' ); ?>
			</button>
			<div class="iue-checkin-timer<?php echo $show_timer ? '' : ' iue-hidden'; ?>">
				<span class="iue-icon" data-iue-icon="clock"></span>
				<span class="iue-timer-countdown">00:00</span>
			</div>
		</div>
	</div>

	<ul class="iue-dashboard-menu">
		<li>
			<a href="#" role="button" class="iue-menu-link" data-action="vip">
				<span class="iue-icon" data-iue-icon="diamond"></span>
				<span><?php esc_html_e( 'VIP Membership', 'init-user-engine' ); ?></span>
			</a>
		</li>
		<li>
			<a href="#" role="button" class="iue-menu-link" data-action="inbox">
				<span class="iue-icon" data-iue-icon="inbox"></span>
				<span>
					<?php esc_html_e( 'Inbox', 'init-user-engine' ); ?>
					<?php if ( $unread_count > 0 ) : ?>
						<span class="iue-badge"><?php echo esc_html( $unread_count ); ?></span>
					<?php endif; ?>
				</span>
			</a>
		</li>
		<li>
			<a href="#" role="button" class="iue-menu-link" data-action="history">
				<span class="iue-icon" data-iue-icon="history"></span>
				<span><?php esc_html_e( 'Transaction History', 'init-user-engine' ); ?></span>
			</a>
		</li>
		<li>
			<a href="#" role="button" class="iue-menu-link" data-action="referral">
				<span class="iue-icon" data-iue-icon="referral"></span>
				<span><?php esc_html_e( 'Referral', 'init-user-engine' ); ?></span>
			</a>
		</li>
		<li>
			<a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>" target="_blank">
				<span class="iue-icon" data-iue-icon="user"></span>
				<span><?php esc_html_e( 'Edit Profile', 'init-user-engine' ); ?></span>
			</a>
		</li>
		<li class="iue-separator"></li>
		<li>
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">
				<span class="iue-icon" data-iue-icon="logout"></span>
				<span><?php esc_html_e( 'Logout', 'init-user-engine' ); ?></span>
			</a>
		</li>
	</ul>
</div>