<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Get current EXP of a user
function init_plugin_suite_user_engine_get_exp( $user_id ) {
	return (int) get_user_meta( $user_id, 'iue_exp', true );
}

// Get current level (min 1)
function init_plugin_suite_user_engine_get_level( $user_id ) {
	$level = (int) get_user_meta( $user_id, 'iue_level', true );
	return max( 1, $level );
}

// Calculate required EXP to level up
function init_plugin_suite_user_engine_exp_required( $level ) {
	return apply_filters( 'init_plugin_suite_user_engine_exp_required', 1000 + ( ( $level - 1 ) * 500 ), $level );
}

// Set EXP value for a user
function init_plugin_suite_user_engine_set_exp( $user_id, $exp ) {
	update_user_meta( $user_id, 'iue_exp', max( 0, (int) $exp ) );
}

// Set level value for a user
function init_plugin_suite_user_engine_set_level( $user_id, $level ) {
	update_user_meta( $user_id, 'iue_level', max( 1, (int) $level ) );
}

// Add EXP, handle level-up, reward coins, and trigger actions
function init_plugin_suite_user_engine_add_exp( $user_id, $exp_added = 0 ) {
	$exp_added = (int) $exp_added;

	// Bonus EXP nếu là VIP
	if ( init_plugin_suite_user_engine_is_vip( $user_id ) ) {
		$options = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
		$bonus   = absint( $options['vip_bonus_exp'] ?? 0 );

		if ( $bonus > 0 ) {
			$exp_added += (int) round( $exp_added * $bonus / 100 );
		}
	}

	$exp_added = apply_filters( 'init_plugin_suite_user_engine_calculated_exp_amount', $exp_added, $user_id );

	$current_exp   = init_plugin_suite_user_engine_get_exp( $user_id );
	$current_level = init_plugin_suite_user_engine_get_level( $user_id );

	$total_exp = $current_exp + $exp_added;
	$level     = $current_level;

	$level_up_count = 0;
	$total_bonus_coin = 0;

	while ( $total_exp >= init_plugin_suite_user_engine_exp_required( $level ) ) {
		$total_exp -= init_plugin_suite_user_engine_exp_required( $level );
		$level++;
		$level_up_count++;

		$reward_coin = 100 + ( $level * 10 );
		init_plugin_suite_user_engine_add_coin( $user_id, $reward_coin );
		init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $reward_coin, 'level_up_' . $level, 'add' );

		$total_bonus_coin += $reward_coin;

		do_action( 'init_plugin_suite_user_engine_level_up', $user_id, $level, $reward_coin );

		if ( $level_up_count === 1 ) {
			$content = sprintf(
				// translators: %1$d is the level number, %2$d is the bonus coins.
			    __( 'Congratulations! You reached level %1$d and received %2$d coins as a bonus.', 'init-user-engine' ),
			    $level,
			    $reward_coin
			);

			init_plugin_suite_user_engine_send_inbox(
				$user_id,
				__( 'Level Up!', 'init-user-engine' ),
				$content,
				'level_up'
			);
		}
	}

	init_plugin_suite_user_engine_set_exp( $user_id, $total_exp );
	init_plugin_suite_user_engine_set_level( $user_id, $level );

	do_action( 'init_plugin_suite_user_engine_exp_added', $user_id, $exp_added );

	return [
		'current_exp'       => $total_exp,
		'current_level'     => $level,
		'level_up_count'    => $level_up_count,
		'total_bonus_coin'  => $total_bonus_coin,
		'exp_added'         => $exp_added,
	];
}
