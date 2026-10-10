<?php
/**
 * Display name change limit.
 *
 * Lets members change their display name only once every N days (like TikTok).
 * The limit is enforced in one place (`wp_pre_insert_user_data`), so it covers
 * every path that saves a user: the profile modal (REST), wp-admin/profile.php,
 * the core REST API (`/wp/v2/users/me`), WooCommerce "Account details" and any
 * other plugin calling wp_update_user() for the signed-in member. The profile
 * modal, profile.php and WooCommerce also get a clear error message instead of
 * a silent revert. Administrators can end a member's waiting period early
 * with the "Unlock Display Name" button in the user overview box.
 *
 * Only changes a member makes to their own account are limited and counted.
 * Users who can edit other users (administrators) are never limited, and a
 * change an administrator makes to a member's account does not start the
 * member's waiting period.
 *
 * @package Init_User_Engine
 * @since   1.7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * User meta key storing the Unix timestamp of the member's last display name change.
 *
 * @since 1.7.0
 */
define( 'INIT_PLUGIN_SUITE_IUE_DISPLAY_NAME_CHANGED_META', 'iue_display_name_changed_at' );

// -----------------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------------

/**
 * Number of days a member has to wait between two display name changes.
 *
 * @since 1.7.0
 *
 * @param int $user_id Optional. User ID. Default 0.
 * @return int Days, 0 when changes are unlimited.
 */
function init_plugin_suite_user_engine_get_display_name_change_days( $user_id = 0 ) {
	$settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, array() );
	$days     = isset( $settings['display_name_change_days'] ) ? absint( $settings['display_name_change_days'] ) : 0;

	/**
	 * Filters how many days a member has to wait between two display name changes.
	 *
	 * @since 1.7.0
	 *
	 * @param int $days    Days from the settings page. 0 means unlimited.
	 * @param int $user_id User ID (0 when not known).
	 */
	return absint( apply_filters( 'init_plugin_suite_user_engine_display_name_change_days', $days, absint( $user_id ) ) );
}

/**
 * Whether the display name change limit applies to a change of this user's display name.
 *
 * True only when a limit is set, the signed-in user is changing their own
 * account, and that user cannot edit other users.
 *
 * @since 1.7.0
 *
 * @param int $user_id ID of the user being changed.
 * @return bool
 */
function init_plugin_suite_user_engine_display_name_limit_applies( $user_id ) {
	$user_id = absint( $user_id );

	$applies = $user_id
		&& get_current_user_id() === $user_id
		&& ! user_can( $user_id, 'edit_users' )
		&& init_plugin_suite_user_engine_get_display_name_change_days( $user_id ) > 0;

	/**
	 * Filters whether the display name change limit applies to this user.
	 *
	 * Return false to let a user (or role) change their display name freely.
	 *
	 * @since 1.7.0
	 *
	 * @param bool $applies Whether the limit applies.
	 * @param int  $user_id User ID.
	 */
	return (bool) apply_filters( 'init_plugin_suite_user_engine_display_name_limit_applies', $applies, $user_id );
}

/**
 * Timestamp of the user's last own display name change.
 *
 * @since 1.7.0
 *
 * @param int $user_id User ID.
 * @return int Unix timestamp, 0 when never changed.
 */
function init_plugin_suite_user_engine_get_display_name_changed_at( $user_id ) {
	return absint( get_user_meta( absint( $user_id ), INIT_PLUGIN_SUITE_IUE_DISPLAY_NAME_CHANGED_META, true ) );
}

/**
 * When the user may change their display name again.
 *
 * @since 1.7.0
 *
 * @param int $user_id User ID.
 * @return int Unix timestamp, 0 when the user can change it right now.
 */
function init_plugin_suite_user_engine_get_display_name_next_change( $user_id ) {
	$user_id = absint( $user_id );

	if ( ! init_plugin_suite_user_engine_display_name_limit_applies( $user_id ) ) {
		return 0;
	}

	return init_plugin_suite_user_engine_get_display_name_locked_until( $user_id );
}

/**
 * End of the member's current waiting period, whoever is looking.
 *
 * Unlike init_plugin_suite_user_engine_get_display_name_next_change(), this does
 * not depend on the signed-in user, so administrators can see a member's status.
 * Users who can edit other users are never locked.
 *
 * @since 1.7.0
 *
 * @param int $user_id User ID.
 * @return int Unix timestamp, 0 when the member is not waiting.
 */
function init_plugin_suite_user_engine_get_display_name_locked_until( $user_id ) {
	$user_id = absint( $user_id );
	$days    = init_plugin_suite_user_engine_get_display_name_change_days( $user_id );

	if ( ! $user_id || ! $days || user_can( $user_id, 'edit_users' ) ) {
		return 0;
	}

	$changed_at = init_plugin_suite_user_engine_get_display_name_changed_at( $user_id );

	if ( ! $changed_at ) {
		return 0;
	}

	$next = $changed_at + $days * DAY_IN_SECONDS;

	return $next > time() ? $next : 0;
}

/**
 * Let a member change their display name again right away.
 *
 * Clears the record of the member's last display name change, so the waiting
 * period ends immediately. The next change the member makes starts a new one.
 *
 * @since 1.7.0
 *
 * @param int $user_id User ID.
 * @return bool True when the member was waiting and is now unlocked.
 */
function init_plugin_suite_user_engine_unlock_display_name( $user_id ) {
	$user_id = absint( $user_id );

	if ( ! $user_id || ! init_plugin_suite_user_engine_get_display_name_locked_until( $user_id ) ) {
		return false;
	}

	delete_user_meta( $user_id, INIT_PLUGIN_SUITE_IUE_DISPLAY_NAME_CHANGED_META );

	/**
	 * Fires after an administrator lets a member change their display name again.
	 *
	 * @since 1.7.0
	 *
	 * @param int $user_id User ID.
	 */
	do_action( 'init_plugin_suite_user_engine_display_name_unlocked', $user_id );

	return true;
}

/**
 * Whether the user has to wait before changing their display name again.
 *
 * @since 1.7.0
 *
 * @param int $user_id User ID.
 * @return bool
 */
function init_plugin_suite_user_engine_is_display_name_locked( $user_id ) {
	return init_plugin_suite_user_engine_get_display_name_next_change( $user_id ) > 0;
}

/**
 * Format a timestamp with the site's date and time formats.
 *
 * @since 1.7.0
 * @access private
 *
 * @param int $timestamp Unix timestamp.
 * @return string
 */
function init_plugin_suite_user_engine_format_display_name_date( $timestamp ) {
	return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), absint( $timestamp ) );
}

/**
 * Sentence telling the member how often the display name can be changed.
 *
 * @since 1.7.0
 *
 * @param int $user_id User ID.
 * @return string Empty string when there is no limit for this user.
 */
function init_plugin_suite_user_engine_get_display_name_rule_text( $user_id ) {
	if ( ! init_plugin_suite_user_engine_display_name_limit_applies( $user_id ) ) {
		return '';
	}

	$days = init_plugin_suite_user_engine_get_display_name_change_days( $user_id );

	return sprintf(
		/* translators: %d: Number of days. */
		_n(
			'You can change your display name once every %d day.',
			'You can change your display name once every %d days.',
			$days,
			'init-user-engine'
		),
		$days
	);
}

/**
 * Full message shown when the display name cannot be changed yet.
 *
 * @since 1.7.0
 *
 * @param int $user_id User ID.
 * @return string Empty string when the display name is not locked.
 */
function init_plugin_suite_user_engine_get_display_name_locked_text( $user_id ) {
	$next = init_plugin_suite_user_engine_get_display_name_next_change( $user_id );

	if ( ! $next ) {
		return '';
	}

	return trim(
		init_plugin_suite_user_engine_get_display_name_rule_text( $user_id ) . ' ' . sprintf(
			/* translators: %s: Date and time when the display name can be changed again. */
			__( 'You can change it again on %s.', 'init-user-engine' ),
			init_plugin_suite_user_engine_format_display_name_date( $next )
		)
	);
}

/**
 * Error returned when a member tries to change a locked display name.
 *
 * @since 1.7.0
 *
 * @param int $user_id User ID.
 * @return WP_Error
 */
function init_plugin_suite_user_engine_display_name_locked_error( $user_id ) {
	return new WP_Error(
		'display_name_locked',
		init_plugin_suite_user_engine_get_display_name_locked_text( $user_id ),
		array(
			'status'      => 403,
			'next_change' => init_plugin_suite_user_engine_get_display_name_next_change( $user_id ),
		)
	);
}

/**
 * Check a requested display name against the limit, without saving anything.
 *
 * Used to show a clear error before the user is saved. The final, race-safe
 * check happens in init_plugin_suite_user_engine_enforce_display_name_limit().
 *
 * @since 1.7.0
 *
 * @param int    $user_id      User ID.
 * @param string $display_name Requested display name.
 * @return true|WP_Error True when the change is allowed (or is not a change).
 */
function init_plugin_suite_user_engine_check_display_name_change( $user_id, $display_name ) {
	$user = get_userdata( absint( $user_id ) );

	if ( ! $user || (string) $user->display_name === (string) $display_name ) {
		return true;
	}

	if ( init_plugin_suite_user_engine_is_display_name_locked( $user->ID ) ) {
		return init_plugin_suite_user_engine_display_name_locked_error( $user->ID );
	}

	return true;
}

/**
 * Data about the limit for the profile modal (GET /profile/me).
 *
 * @since 1.7.0
 *
 * @param int $user_id User ID.
 * @return array {
 *     @type int    $days        Days between two changes, 0 when unlimited for this user.
 *     @type bool   $locked      Whether the display name cannot be changed right now.
 *     @type int    $next_change Unix timestamp of the next allowed change, 0 when allowed now.
 *     @type string $notice      Text to show under the field, empty when nothing to say.
 * }
 */
function init_plugin_suite_user_engine_get_display_name_limit_data( $user_id ) {
	$applies = init_plugin_suite_user_engine_display_name_limit_applies( $user_id );
	$next    = $applies ? init_plugin_suite_user_engine_get_display_name_next_change( $user_id ) : 0;

	return array(
		'days'        => $applies ? init_plugin_suite_user_engine_get_display_name_change_days( $user_id ) : 0,
		'locked'      => $next > 0,
		'next_change' => $next,
		'notice'      => $next > 0
			? init_plugin_suite_user_engine_get_display_name_locked_text( $user_id )
			: init_plugin_suite_user_engine_get_display_name_rule_text( $user_id ),
	);
}

// -----------------------------------------------------------------------------
// Enforcement (every path that saves a user)
// -----------------------------------------------------------------------------

add_filter( 'wp_pre_insert_user_data', 'init_plugin_suite_user_engine_enforce_display_name_limit', 20, 3 );

/**
 * Keep the old display name when a member changes it too soon, and record allowed changes.
 *
 * The check and the record run under the per-user lock, so two requests sent
 * at the same moment cannot both change the display name.
 *
 * @since 1.7.0
 *
 * @param array    $data    User data about to be saved.
 * @param bool     $update  Whether the user is being updated.
 * @param int|null $user_id User ID when updating, null otherwise.
 * @return array
 */
function init_plugin_suite_user_engine_enforce_display_name_limit( $data, $update, $user_id = null ) {
	if ( ! $update || ! $user_id || ! is_array( $data ) || ! array_key_exists( 'display_name', $data ) ) {
		return $data;
	}

	$user_id = absint( $user_id );

	// Only changes a member makes to their own account are limited and counted.
	if ( get_current_user_id() !== $user_id || user_can( $user_id, 'edit_users' ) ) {
		return $data;
	}

	$old_user = get_userdata( $user_id );

	if ( ! $old_user || (string) $old_user->display_name === (string) $data['display_name'] ) {
		return $data;
	}

	$limited = init_plugin_suite_user_engine_display_name_limit_applies( $user_id );

	if ( ! $limited ) {
		// No limit right now: only remember the change, so a limit set later starts from it.
		update_user_meta( $user_id, INIT_PLUGIN_SUITE_IUE_DISPLAY_NAME_CHANGED_META, time() );
		return $data;
	}

	if ( ! init_plugin_suite_user_engine_lock_user( $user_id, 3 ) ) {
		// Cannot check safely: keep the current display name.
		$data['display_name'] = $old_user->display_name;
		return $data;
	}

	try {
		if ( init_plugin_suite_user_engine_is_display_name_locked( $user_id ) ) {
			$data['display_name'] = $old_user->display_name;
		} else {
			update_user_meta( $user_id, INIT_PLUGIN_SUITE_IUE_DISPLAY_NAME_CHANGED_META, time() );
		}
	} finally {
		init_plugin_suite_user_engine_unlock_user( $user_id );
	}

	return $data;
}

// -----------------------------------------------------------------------------
// Clear error messages
// -----------------------------------------------------------------------------

add_action( 'user_profile_update_errors', 'init_plugin_suite_user_engine_display_name_profile_errors', 10, 3 );

/**
 * Show an error on wp-admin/profile.php instead of silently keeping the old display name.
 *
 * @since 1.7.0
 *
 * @param WP_Error $errors WP_Error object (passed by reference).
 * @param bool     $update Whether this is a user update.
 * @param stdClass $user   User object built from the submitted form.
 * @return void
 */
function init_plugin_suite_user_engine_display_name_profile_errors( $errors, $update, $user ) {
	if ( ! $update || empty( $user->ID ) || ! isset( $user->display_name ) ) {
		return;
	}

	$check = init_plugin_suite_user_engine_check_display_name_change( $user->ID, $user->display_name );

	if ( is_wp_error( $check ) ) {
		$errors->add( 'display_name_locked', $check->get_error_message(), array( 'form-field' => 'display_name' ) );
	}
}

add_action( 'woocommerce_save_account_details_errors', 'init_plugin_suite_user_engine_display_name_woocommerce_errors', 10, 2 );

/**
 * Show an error on the WooCommerce "Account details" form.
 *
 * @since 1.7.0
 *
 * @param WP_Error $errors WP_Error object.
 * @param stdClass $user   User object built from the submitted form.
 * @return void
 */
function init_plugin_suite_user_engine_display_name_woocommerce_errors( $errors, $user ) {
	if ( ! is_wp_error( $errors ) || ! is_object( $user ) || empty( $user->ID ) || ! isset( $user->display_name ) ) {
		return;
	}

	$check = init_plugin_suite_user_engine_check_display_name_change( $user->ID, $user->display_name );

	if ( is_wp_error( $check ) ) {
		$errors->add( 'display_name_locked', $check->get_error_message() );
	}
}

// -----------------------------------------------------------------------------
// wp-admin/profile.php
// -----------------------------------------------------------------------------

add_action( 'admin_enqueue_scripts', 'init_plugin_suite_user_engine_display_name_profile_assets' );

/**
 * On the member's own profile screen, explain the limit next to the "Display name publicly as" field.
 *
 * When the display name is locked the field is disabled. The server-side check
 * stays the real protection.
 *
 * @since 1.7.0
 *
 * @param string $hook_suffix Current admin page.
 * @return void
 */
function init_plugin_suite_user_engine_display_name_profile_assets( $hook_suffix ) {
	if ( 'profile.php' !== $hook_suffix ) {
		return;
	}

	$user_id = get_current_user_id();
	$limit   = init_plugin_suite_user_engine_get_display_name_limit_data( $user_id );

	if ( '' === $limit['notice'] ) {
		return;
	}

	wp_enqueue_script(
		'init-user-engine-display-name-limit',
		INIT_PLUGIN_SUITE_IUE_ASSETS_URL . 'js/display-name-limit.js',
		array(),
		INIT_PLUGIN_SUITE_IUE_VERSION,
		true
	);

	wp_localize_script(
		'init-user-engine-display-name-limit',
		'InitUserEngineDisplayNameLimit',
		array(
			'locked' => (bool) $limit['locked'],
			'notice' => $limit['notice'],
		)
	);
}

// -----------------------------------------------------------------------------
// Admin: unlock a member's display name (user overview box on user-edit.php)
// -----------------------------------------------------------------------------

/**
 * Whether the signed-in user can unlock this member's display name.
 *
 * @since 1.7.0
 *
 * @param int $user_id Member's user ID.
 * @return bool
 */
function init_plugin_suite_user_engine_can_unlock_display_name( $user_id ) {
	return current_user_can( 'manage_options' ) && current_user_can( 'edit_user', absint( $user_id ) );
}

/**
 * Render the display name status and the "Unlock Display Name" button.
 *
 * Shown in the user overview box, next to "Remove VIP" and "Ban Avatar Upload".
 * Prints nothing when no limit is set or the user can never be limited.
 *
 * @since 1.7.0
 *
 * @param int $user_id Member's user ID.
 * @return void
 */
function init_plugin_suite_user_engine_render_display_name_unlock( $user_id ) {
	$user_id = absint( $user_id );

	if (
		! init_plugin_suite_user_engine_can_unlock_display_name( $user_id )
		|| ! init_plugin_suite_user_engine_get_display_name_change_days( $user_id )
		|| user_can( $user_id, 'edit_users' )
	) {
		return;
	}

	$locked_until = init_plugin_suite_user_engine_get_display_name_locked_until( $user_id );
	?>
	<p class="iue-meta" style="margin-top:6px;">
		<?php if ( $locked_until ) : ?>
			<?php
			$unlock_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'  => 'iue_unlock_display_name',
						'user_id' => $user_id,
					),
					admin_url( 'admin-post.php' )
				),
				'iue_unlock_display_name_' . $user_id
			);
			?>
			<a class="button button-secondary" href="<?php echo esc_url( $unlock_url ); ?>"
				onclick="return confirm('<?php echo esc_js( __( 'Let this user change their display name again now?', 'init-user-engine' ) ); ?>');">
				<?php esc_html_e( 'Unlock Display Name', 'init-user-engine' ); ?>
			</a>
			<span class="description" style="margin-left:8px;">
				<?php
				printf(
					/* translators: %s: Date and time when the member can change their display name again. */
					esc_html__( 'Display name: locked until %s', 'init-user-engine' ),
					esc_html( init_plugin_suite_user_engine_format_display_name_date( $locked_until ) )
				);
				?>
			</span>
		<?php else : ?>
			<span class="description">
				<?php esc_html_e( 'Display name: can be changed now', 'init-user-engine' ); ?>
			</span>
		<?php endif; ?>
	</p>
	<?php
}

add_action( 'admin_post_iue_unlock_display_name', 'init_plugin_suite_user_engine_handle_unlock_display_name' );

/**
 * Handle the "Unlock Display Name" button.
 *
 * URL: admin-post.php?action=iue_unlock_display_name&user_id=###&_wpnonce=...
 *
 * @since 1.7.0
 *
 * @return void
 */
function init_plugin_suite_user_engine_handle_unlock_display_name() {
	$user_id = isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The nonce is checked right below and needs the user ID.

	check_admin_referer( 'iue_unlock_display_name_' . $user_id );

	if ( ! $user_id || ! init_plugin_suite_user_engine_can_unlock_display_name( $user_id ) ) {
		wp_die( esc_html__( 'You do not have permission to perform this action.', 'init-user-engine' ), '', array( 'response' => 403 ) );
	}

	$flag = init_plugin_suite_user_engine_unlock_display_name( $user_id ) ? 'done' : 'none';

	$back = wp_get_referer();
	if ( ! $back ) {
		$back = get_edit_user_link( $user_id );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'iue_display_name_unlock' => $flag,
				'_iue_notice'             => wp_create_nonce( 'iue_notice_' . get_current_user_id() ),
			),
			$back
		)
	);
	exit;
}

add_action( 'admin_notices', 'init_plugin_suite_user_engine_display_name_unlock_notice' );

/**
 * Show the result of "Unlock Display Name" on the user screens.
 *
 * @since 1.7.0
 *
 * @return void
 */
function init_plugin_suite_user_engine_display_name_unlock_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$flag         = isset( $_GET['iue_display_name_unlock'] ) ? sanitize_key( wp_unslash( $_GET['iue_display_name_unlock'] ) ) : '';
	$notice_nonce = isset( $_GET['_iue_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['_iue_notice'] ) ) : '';

	if ( '' === $flag || '' === $notice_nonce || ! wp_verify_nonce( $notice_nonce, 'iue_notice_' . get_current_user_id() ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && ! in_array( $screen->id, array( 'profile', 'user-edit' ), true ) ) {
		return;
	}

	$message = 'done' === $flag
		? __( 'This user can change their display name again now.', 'init-user-engine' )
		: __( 'This user was not waiting to change their display name.', 'init-user-engine' );

	printf(
		'<div class="updated notice is-dismissible"><p>%s</p></div>',
		esc_html( $message )
	);
}
