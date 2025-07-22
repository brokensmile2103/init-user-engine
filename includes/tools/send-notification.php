<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function init_plugin_suite_user_engine_render_send_notification_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'init-user-engine' ) );
	}

	$sent = false;
	$error = '';

	if ( isset( $_POST['iue_send_notice_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iue_send_notice_nonce'] ) ), 'iue_send_notice' ) ) {
		$title    = sanitize_text_field( wp_unslash( $_POST['iue_title'] ?? '' ) );
		$content  = wp_kses_post( wp_unslash( $_POST['iue_content'] ?? '' ) );
		
		// Lấy toàn bộ user ID nếu gửi toàn bộ
		if ( ! empty( $_POST['iue_send_all'] ) ) {
		    $users = get_users( [ 'fields' => 'ID' ] );
		    $user_ids = array_map( 'absint', $users );
		} else {
		    $user_ids_raw = sanitize_text_field( wp_unslash( $_POST['iue_user_ids'] ?? '' ) );
			$user_ids = array_filter( array_map( 'absint', explode( ',', $user_ids_raw ) ) );
		}

		$type     = sanitize_key( $_POST['iue_type'] ?? 'system' );
		$priority = sanitize_key( $_POST['iue_priority'] ?? 'normal' );
		$link     = esc_url_raw( wp_unslash( $_POST['iue_link'] ?? '' ) );
		$pinned   = ! empty( $_POST['iue_pinned'] ) ? 1 : 0;
		$expire   = ! empty( $_POST['iue_expire'] ) ? strtotime( sanitize_text_field( wp_unslash( $_POST['iue_expire'] ) ) ) : null;

		if ( empty( $title ) || empty( $content ) || empty( $user_ids ) ) {
			$error = __( 'Please fill in all required fields.', 'init-user-engine' );
		} else {
			init_plugin_suite_user_engine_send_inbox_to_users( $user_ids, $title, $content, $type, [], $expire, $priority, $link, $pinned );
			do_action( 'init_plugin_suite_user_engine_admin_send_notice', $user_ids, $title, $content, $type, $priority, $link, $pinned, $expire );
			$sent = true;
		}
	}

	?>
	<div class="wrap iue-notice-wrapper">
		<h1><?php esc_html_e( 'Send Notification', 'init-user-engine' ); ?></h1>

		<?php if ( $sent ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Notification sent successfully.', 'init-user-engine' ); ?></p></div>
		<?php elseif ( $error ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'iue_send_notice', 'iue_send_notice_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="iue_title"><?php esc_html_e( 'Title', 'init-user-engine' ); ?></label></th>
					<td>
						<input name="iue_title" type="text" id="iue_title"
							value="" class="regular-text" required
							placeholder="<?php esc_attr_e( 'Enter notification title...', 'init-user-engine' ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="iue_content"><?php esc_html_e( 'Content', 'init-user-engine' ); ?></label></th>
					<td>
						<textarea name="iue_content" id="iue_content" rows="6" class="large-text" required
							placeholder="<?php esc_attr_e( 'Write your message content here...', 'init-user-engine' ); ?>"></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Select Users', 'init-user-engine' ); ?></th>
					<td style="position: relative;">
						<input type="text" id="iue_user_search" class="regular-text"
							placeholder="<?php esc_attr_e( 'Search by username or display name...', 'init-user-engine' ); ?>">
						<div id="iue_user_results" class="iue-user-results"></div>
						<div id="iue_user_selected" class="iue-user-selected"></div>
						<input type="hidden" name="iue_user_ids" id="iue_user_ids" value="">
						<p class="description"><?php esc_html_e( 'Selected user IDs will appear here.', 'init-user-engine' ); ?></p>
					</td>
				</tr>
				<tr>
				    <th scope="row">&nbsp;</th>
				    <td>
				        <label><input type="checkbox" name="iue_send_all" value="1"> <?php esc_html_e( 'Send to all members', 'init-user-engine' ); ?></label>
				        <p class="description"><?php esc_html_e( 'This will override the user ID field and send to all registered users.', 'init-user-engine' ); ?></p>
				    </td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Type', 'init-user-engine' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="iue_type" value="system" checked> <?php esc_html_e( 'system', 'init-user-engine' ); ?></label><br>
							<label><input type="radio" name="iue_type" value="gift"> <?php esc_html_e( 'gift', 'init-user-engine' ); ?></label><br>
							<label><input type="radio" name="iue_type" value="event"> <?php esc_html_e( 'event', 'init-user-engine' ); ?></label><br>
							<label><input type="radio" name="iue_type" value="warning"> <?php esc_html_e( 'warning', 'init-user-engine' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Priority', 'init-user-engine' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="iue_priority" value="normal" checked> <?php esc_html_e( 'normal', 'init-user-engine' ); ?></label><br>
							<label><input type="radio" name="iue_priority" value="high"> <?php esc_html_e( 'high', 'init-user-engine' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="iue_link"><?php esc_html_e( 'Optional Link', 'init-user-engine' ); ?></label></th>
					<td>
						<input name="iue_link" type="url" id="iue_link" class="regular-text"
							placeholder="<?php esc_attr_e( 'https://example.com/optional-link', 'init-user-engine' ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">&nbsp;</th>
					<td><label><input type="checkbox" name="iue_pinned" value="1"> <?php esc_html_e( 'Pin this message', 'init-user-engine' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="iue_expire"><?php esc_html_e( 'Expire At (optional)', 'init-user-engine' ); ?></label></th>
					<td>
						<input name="iue_expire" type="datetime-local" id="iue_expire" class="regular-text"
							placeholder="<?php esc_attr_e( 'Optional expiration time', 'init-user-engine' ); ?>">
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Send Notification', 'init-user-engine' ) ); ?>
		</form>
	</div>
	<?php
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( strpos( $hook, 'init-user-engine' ) !== false ) {
		wp_enqueue_style( 'iue-send-notice-style', INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'css/admin.css', [], INIT_PLUGIN_SUITE_IUE_VERSION );
		wp_enqueue_script( 'iue-send-notice', INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'js/admin.js', [ 'jquery' ], INIT_PLUGIN_SUITE_IUE_VERSION, true );
		wp_localize_script( 'iue-send-notice', 'InitPluginSuiteUserEngineAdminNoticeData', [ 'nonce' => wp_create_nonce( 'iue_send_notice' ) ] );
	}
} );
