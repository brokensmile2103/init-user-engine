<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$user_id = get_current_user_id();
$avatar  = init_plugin_suite_user_engine_get_avatar( $user_id, 50, );
$bubble_avatar  = init_plugin_suite_user_engine_get_avatar( $user_id, 50, [ 'overlay' => true ] );

$user_data = [
	'user_id' => $user_id,
	'avatar'  => $bubble_avatar,
	'coin'    => init_plugin_suite_user_engine_get_coin( $user_id ),
	'cash'    => init_plugin_suite_user_engine_get_cash( $user_id ),
	'level'   => init_plugin_suite_user_engine_get_level( $user_id ),
];

$unread_count 	= (int) init_plugin_suite_user_engine_get_unread_inbox_count( $user_id );
$has_checked_in = init_plugin_suite_user_engine_has_checked_in_today( $user_id );

$show_dot = ( $unread_count > 0 || ! $has_checked_in );
?>
<div class="init-user-engine-avatar-wrapper logged-in">
	<div class="init-user-engine-avatar is-logged-in<?php echo ( $show_dot ? ' iue-has-unread' : '' ); ?>" id="init-user-engine-avatar">
		<?php echo wp_kses_post( $avatar ); ?>
		<?php if ( $show_dot ) : ?>
			<span class="iue-unread-dot"></span>
		<?php endif; ?>
	</div>
	<div class="iue-dashboard-wrapper">
		<?php
		init_plugin_suite_user_engine_load_template( 'dashboard', [
			'iue_user_data' => $user_data,
		] );
		?>
	</div>
</div>
