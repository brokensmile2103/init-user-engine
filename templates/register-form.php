<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<form id="iue-register-form" class="iue-form" onsubmit="return false;">
	<p class="iue-form-group iue-register-username">
		<label for="iue_register_username"><?php esc_html_e( 'Username', 'init-user-engine' ); ?></label><br>
		<input type="text" name="username" id="iue_register_username" class="iue-input" autocomplete="username"
			placeholder="<?php esc_attr_e( 'Pick a cool username', 'init-user-engine' ); ?>" required>
	</p>

	<p class="iue-form-group iue-register-email">
		<label for="iue_register_email"><?php esc_html_e( 'Email', 'init-user-engine' ); ?></label><br>
		<input type="email" name="email" id="iue_register_email" class="iue-input" autocomplete="email"
			placeholder="<?php esc_attr_e( 'We’ll never spam you', 'init-user-engine' ); ?>" required>
	</p>

	<p class="iue-form-group iue-register-password">
		<label for="iue_register_password"><?php esc_html_e( 'Password', 'init-user-engine' ); ?></label><br>
		<input type="password" name="password" id="iue_register_password" class="iue-input" autocomplete="new-password"
			placeholder="<?php esc_attr_e( 'Make it strong', 'init-user-engine' ); ?>" required>
	</p>

	<p class="iue-form-group iue-hidden">
		<label for="iue_hp">Leave this field empty</label>
		<input type="text" name="iue_hp" id="iue_hp" autocomplete="off" tabindex="-1">
	</p>

	<?php
	$options          = get_option( INIT_PLUGIN_SUITE_IUE_OPTION );
	$disable_captcha  = ! empty( $options['disable_captcha'] );
	$turnstile_key    = $options['turnstile_site_key'] ?? '';
	$turnstile_theme  = $options['turnstile_theme'] ?? 'auto';
	?>

	<?php if ( ! $disable_captcha ) : ?>
		<?php if ( ! empty( $turnstile_key ) && ! empty( $options['turnstile_secret_key'] ) ) : ?>
			<p class="iue-form-group iue-register-captcha">
				<label><?php esc_html_e( 'Human Verification', 'init-user-engine' ); ?></label><br>
				<div id="iue-turnstile"
					data-sitekey="<?php echo esc_attr( $turnstile_key ); ?>"
					data-theme="<?php echo esc_attr( in_array( $turnstile_theme, ['auto','light','dark'], true ) ? $turnstile_theme : 'auto' ); ?>">
				</div>
			</p>
		<?php else : ?>
			<!-- Fallback captcha cũ -->
			<p class="iue-form-group iue-register-captcha">
				<label for="iue_register_captcha_answer"><?php esc_html_e( 'Captcha', 'init-user-engine' ); ?></label><br>
				<span id="iue-captcha-question" class="iue-captcha-question"><?php esc_html_e( 'Loading...', 'init-user-engine' ); ?></span><br>
				<input type="number" name="captcha_answer" id="iue_register_captcha_answer" class="iue-input"
					placeholder="<?php esc_attr_e( 'Type your answer here', 'init-user-engine' ); ?>" required>
			</p>
		<?php endif; ?>
	<?php endif; ?>

	<p class="iue-form-group iue-register-submit">
		<button type="submit" class="iue-submit">
			<?php esc_html_e( 'Register', 'init-user-engine' ); ?>
		</button>
	</p>
</form>
