<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function init_plugin_suite_user_engine_render_topup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'init-user-engine' ) );
	}

	$success = false;
	$error = '';

	if (
		isset( $_POST['iue_topup_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iue_topup_nonce'] ) ), 'iue_topup' )
	) {
		$amount    = (int) ($_POST['iue_amount'] ?? 0);
		$type      = sanitize_key( $_POST['iue_topup_type'] ?? 'coin' );
		$send_all  = ! empty( $_POST['iue_send_all'] );
		$user_ids  = [];

		if ( $send_all ) {
			$user_ids = get_users( [ 'fields' => 'ID' ] );
		} else {
			$raw = sanitize_text_field( wp_unslash( $_POST['iue_user_ids'] ?? '' ) );
			$user_ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
		}

		if ( $amount <= 0 || empty( $user_ids ) || ! in_array( $type, [ 'coin', 'cash' ], true ) ) {
			$error = __( 'Please fill in all required fields correctly.', 'init-user-engine' );
		} else {
			foreach ( $user_ids as $user_id ) {
				if ( $type === 'coin' ) {
					$new_coin = init_plugin_suite_user_engine_add_coin( $user_id, $amount );
					init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $amount, 'topup_admin', 'add' );

					$title   = __( 'Admin Top-up Coin', 'init-user-engine' );
					$content = sprintf( __( 'You have received %d Coins from the admin.', 'init-user-engine' ), $amount );
				} else {
					$new_cash = init_plugin_suite_user_engine_add_cash( $user_id, $amount );
					init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', $amount, 'topup_admin', 'add' );

					$title   = __( 'Admin Top-up Cash', 'init-user-engine' );
					$content = sprintf( __( 'You have received %d Cash from the admin.', 'init-user-engine' ), $amount );
				}

				init_plugin_suite_user_engine_send_inbox(
					$user_id,
					$title,
					$content,
					'gift',
					[ 'topup_by_admin' => 1 ],
					null,
					'normal'
				);
			}
			$success = true;
		}
	}

	?>
	<div class="wrap iue-topup-wrapper">
		<h1><?php esc_html_e( 'Top-up Coin / Cash', 'init-user-engine' ); ?></h1>

		<?php if ( $success ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Top-up successful.', 'init-user-engine' ); ?></p></div>
		<?php elseif ( $error ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'iue_topup', 'iue_topup_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="iue_amount"><?php esc_html_e( 'Amount', 'init-user-engine' ); ?></label></th>
					<td>
						<input type="number" name="iue_amount" id="iue_amount" min="1" required class="regular-text" placeholder="1000">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Top-up Type', 'init-user-engine' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="iue_topup_type" value="coin" checked>
								<?php esc_html_e( 'Coin', 'init-user-engine' ); ?>
							</label><br>
							<label>
								<input type="radio" name="iue_topup_type" value="cash">
								<?php esc_html_e( 'Cash', 'init-user-engine' ); ?>
							</label>
						</fieldset>
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
				        <label><input type="checkbox" name="iue_send_all" value="1"> <?php esc_html_e( 'Apply to all members', 'init-user-engine' ); ?></label>
				        <p class="description"><?php esc_html_e( 'This will override the user ID field and top-up to all users.', 'init-user-engine' ); ?></p>
				    </td>
				</tr>
			</table>

			<?php submit_button( __( 'Top-up Now', 'init-user-engine' ) ); ?>
		</form>
	</div>
	<?php
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( strpos( $hook, 'init-user-engine' ) !== false ) {
		wp_enqueue_style( 'iue-send-notice-style', INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'css/admin.css', [], INIT_PLUGIN_SUITE_IUE_VERSION );
		wp_enqueue_script( 'iue-send-notice', INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'js/admin.js', [ 'jquery' ], INIT_PLUGIN_SUITE_IUE_VERSION, true );
		wp_localize_script( 'iue-send-notice', 'InitPluginSuiteUserEngineAdminNoticeData', [
			'nonce' => wp_create_nonce( 'iue_send_notice' )
		] );
	}
} );
