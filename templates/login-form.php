<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );

$custom_register_url = $settings['custom_register_url'] ?? '';
$custom_lostpass_url = $settings['custom_lostpass_url'] ?? '';

// Custom Lost Password URL (nếu admin có đặt) luôn được ưu tiên hơn form trong modal.
$use_lostpass_modal = init_plugin_suite_user_engine_use_lostpassword_modal( $settings );

$registration_disabled 		 = ! empty( $settings['disable_registration'] );
$should_render_register_form = empty( $custom_register_url ) && ! $registration_disabled;
?>

<div id="iue-form-login" class="iue-login-form">
	<?php wp_login_form( [
		'echo'           => true,
		'id_username'    => 'user_login',
		'id_password'    => 'user_pass',
		'id_remember'    => 'rememberme',
		'id_submit'      => 'wp-submit',
		'class_submit'   => 'iue-submit',
		'remember'       => true
	] ); ?>
</div>

<?php if ( $should_render_register_form ) : ?>
	<div id="iue-form-register" class="iue-register-form iue-hidden">
		<?php init_plugin_suite_user_engine_load_template( 'register-form' ); ?>
	</div>
<?php endif; ?>

<?php if ( $use_lostpass_modal ) : ?>
	<div id="iue-form-lostpassword" class="iue-lostpassword-form iue-hidden">
		<?php init_plugin_suite_user_engine_load_template( 'lostpassword-form' ); ?>
	</div>
<?php endif; ?>

<div class="iue-reset-link">
	<a href="<?php echo esc_url( $custom_lostpass_url ? $custom_lostpass_url : wp_lostpassword_url() ); ?>" id="iue-lostpass-link" data-iue-modal="<?php echo $use_lostpass_modal ? '1' : '0'; ?>">
		<?php esc_html_e( 'Forgot password?', 'init-user-engine' ); ?>
	</a>
</div>

<?php if ( ! $registration_disabled ) : ?>
	<div class="iue-register-link">
		<?php if ( $custom_register_url ) : ?>
			<a href="<?php echo esc_url( $custom_register_url ); ?>" id="iue-register-link" data-has-custom-url="1" data-url="<?php echo esc_url( $custom_register_url ); ?>">
				<?php esc_html_e( 'Create a new account', 'init-user-engine' ); ?>
			</a>
		<?php else : ?>
			<a href="#" id="iue-register-link" data-has-custom-url="0">
				<?php esc_html_e( 'Create a new account', 'init-user-engine' ); ?>
			</a>
		<?php endif; ?>
	</div>
<?php endif; ?>
