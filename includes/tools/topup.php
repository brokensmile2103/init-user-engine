<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function init_plugin_suite_user_engine_render_topup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'init-user-engine' ) );
	}

	// Load currency labels directly from options (no helper here)
	$opts       = (array) get_option( constant( 'INIT_PLUGIN_SUITE_IUE_OPTION' ), [] );
	$coin_label = isset( $opts['label_coin'] ) ? trim( wp_strip_all_tags( (string) $opts['label_coin'] ) ) : '';
	$cash_label = isset( $opts['label_cash'] ) ? trim( wp_strip_all_tags( (string) $opts['label_cash'] ) ) : '';
	$coin_label = $coin_label !== '' ? $coin_label : 'Coin';
	$cash_label = $cash_label !== '' ? $cash_label : 'Cash';

	$success = false;
	$error   = '';

	if (
		isset( $_POST['iue_topup_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iue_topup_nonce'] ) ), 'iue_topup' )
	) {
		// Cho phép số âm (delta). RAW ADJUST: bỏ qua mọi bonus/VIP.
		$amount   = intval( wp_unslash( $_POST['iue_amount'] ?? 0 ) );
		$type     = sanitize_key( $_POST['iue_topup_type'] ?? 'coin' );
		$send_all = ! empty( $_POST['iue_send_all'] );
		$user_ids = [];

		if ( $send_all ) {
			$user_ids = get_users( [ 'fields' => 'ID' ] );
		} else {
			$raw      = sanitize_text_field( wp_unslash( $_POST['iue_user_ids'] ?? '' ) );
			$user_ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
		}

		// amount = 0 thì không làm gì; type sai; không có user => lỗi
		if ( 0 === $amount || empty( $user_ids ) || ! in_array( $type, [ 'coin', 'cash' ], true ) ) {
			$error = __( 'Please fill in all required fields correctly.', 'init-user-engine' );
		} else {
			foreach ( $user_ids as $user_id ) {
				if ( 'coin' === $type ) {
					// RAW adjust coin (no bonus/VIP)
					$before = (int) init_plugin_suite_user_engine_get_coin( $user_id );
					$target = max( 0, $before + (int) $amount );
					init_plugin_suite_user_engine_set_coin( $user_id, $target );

					$delta   = $target - $before; // có thể âm/dương/0
					if ( 0 !== $delta ) {
						$applied = abs( $delta );
						$change  = ( $delta < 0 ) ? 'deduct' : 'add';

						init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $applied, 'topup_admin', $change );

						if ( 'deduct' === $change ) {
							// translators: %s = currency label (e.g., Coin or Cash)
							$title   = sprintf( __( 'Admin Adjustment %s', 'init-user-engine' ), $coin_label );
							// translators: 1: amount, 2: currency label
							$content = sprintf( __( '%1$d %2$s has been deducted by admin.', 'init-user-engine' ), $applied, $coin_label );
							$icon    = 'warning';
						} else {
							// translators: %s = currency label (e.g., Coin or Cash)
							$title   = sprintf( __( 'Admin Top-up %s', 'init-user-engine' ), $coin_label );
							// translators: 1: amount, 2: currency label
							$content = sprintf( __( 'You have received %1$d %2$s from the admin.', 'init-user-engine' ), $applied, $coin_label );
							$icon    = 'gift';
						}

						init_plugin_suite_user_engine_send_inbox(
							$user_id,
							$title,
							$content,
							$icon,
							[ 'topup_by_admin' => 1 ],
							null,
							'normal'
						);
					}
				} else {
					// RAW adjust cash (no bonus/VIP)
					$before = (int) init_plugin_suite_user_engine_get_cash( $user_id );
					$target = max( 0, $before + (int) $amount );
					init_plugin_suite_user_engine_set_cash( $user_id, $target );

					$delta   = $target - $before;
					if ( 0 !== $delta ) {
						$applied = abs( $delta );
						$change  = ( $delta < 0 ) ? 'deduct' : 'add';

						init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', $applied, 'topup_admin', $change );

						if ( 'deduct' === $change ) {
							// translators: %s = currency label (e.g., Coin or Cash)
							$title   = sprintf( __( 'Admin Adjustment %s', 'init-user-engine' ), $cash_label );
							// translators: 1: amount, 2: currency label
							$content = sprintf( __( '%1$d %2$s has been deducted by admin.', 'init-user-engine' ), $applied, $cash_label );
							$icon    = 'warning';
						} else {
							// translators: %s = currency label (e.g., Coin or Cash)
							$title   = sprintf( __( 'Admin Top-up %s', 'init-user-engine' ), $cash_label );
							// translators: 1: amount, 2: currency label
							$content = sprintf( __( 'You have received %1$d %2$s from the admin.', 'init-user-engine' ), $applied, $cash_label );
							$icon    = 'gift';
						}

						init_plugin_suite_user_engine_send_inbox(
							$user_id,
							$title,
							$content,
							$icon,
							[ 'topup_by_admin' => 1 ],
							null,
							'normal'
						);
					}
				}
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
						<input type="number" name="iue_amount" id="iue_amount" step="1" required class="regular-text" placeholder="1000 / -1000">
						<p class="description"><?php esc_html_e( 'Positive to add, negative to deduct. Deduction will not go below 0.', 'init-user-engine' ); ?></p>
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
