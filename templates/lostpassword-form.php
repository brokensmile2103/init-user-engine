<?php
/**
 * Lost password form rendered inside the login modal.
 *
 * Form gửi thẳng tới wp-login.php?action=lostpassword — dùng nguyên luồng
 * "Lost your password?" gốc của WordPress (retrieve_password(), email, hook
 * lostpassword_form / lostpassword_post của các plugin khác...). Plugin chỉ
 * đưa người dùng quay lại đúng trang hiện tại sau khi gửi (xem
 * includes/hooks.php và assets/js/guest.js).
 *
 * Theme có thể override bằng file: your-theme/init-user-engine/lostpassword-form.php
 *
 * @package Init_User_Engine
 */

defined( 'ABSPATH' ) || exit;
?>

<form id="iue-lostpassword-form" name="lostpasswordform" class="iue-form" action="<?php echo esc_url( network_site_url( 'wp-login.php?action=lostpassword', 'login_post' ) ); ?>" method="post">
	<p class="iue-lostpassword-intro">
		<?php esc_html_e( 'Please enter your username or email address. You will receive an email message with instructions on how to reset your password.', 'init-user-engine' ); ?>
	</p>

	<p class="iue-form-group iue-lostpassword-user">
		<label for="iue_lostpassword_user_login"><?php esc_html_e( 'Username or Email Address', 'init-user-engine' ); ?></label><br>
		<input type="text" name="user_login" id="iue_lostpassword_user_login" class="iue-input" autocomplete="username" autocapitalize="off" spellcheck="false" required>
	</p>

	<div class="iue-wp-form-captcha">
		<?php
		/** This action is documented in wp-login.php */
		do_action( 'lostpassword_form' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook gốc của WordPress, gọi lại để Turnstile/captcha của plugin khác hiển thị trong form.
		?>
	</div>

	<input type="hidden" name="redirect_to" value="">
	<input type="hidden" name="iue_lostpassword" value="1">

	<p class="iue-form-group iue-lostpassword-submit">
		<button type="submit" class="iue-submit">
			<?php esc_html_e( 'Get New Password', 'init-user-engine' ); ?>
		</button>
	</p>
</form>
