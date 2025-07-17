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

	<p class="iue-form-group iue-register-submit">
		<button type="submit" class="iue-submit">
			<?php esc_html_e( 'Register', 'init-user-engine' ); ?>
		</button>
	</p>
</form>
