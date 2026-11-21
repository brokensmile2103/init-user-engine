<?php
/**
 * Block members.
 *
 * A blocked member cannot log in anywhere (login form, wp-login.php, XML-RPC,
 * Application Passwords) until an administrator unblocks them. Blocking signs the
 * member out of every device straight away.
 *
 * Admins block and unblock from Users (row link and bulk actions) or from the
 * User Engine box on the user's profile.
 *
 * @package Init_User_Engine
 * @since   1.7.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User meta key holding the block data: array( 'time' => int, 'by' => int ).
 */
define( 'INIT_PLUGIN_SUITE_IUE_BLOCK_META', 'iue_blocked' );

// =====================================================================
// Core
// =====================================================================

/**
 * Get the block data of a member.
 *
 * @since 1.7.9
 *
 * @param int $user_id User ID.
 * @return array|null array( 'time' => int, 'by' => int ), or null when not blocked.
 */
function init_plugin_suite_user_engine_get_user_block( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return null;
	}

	$data = get_user_meta( $user_id, INIT_PLUGIN_SUITE_IUE_BLOCK_META, true );
	if ( empty( $data ) ) {
		return null;
	}

	if ( ! is_array( $data ) ) {
		$data = array();
	}

	return array(
		'time' => isset( $data['time'] ) ? absint( $data['time'] ) : 0,
		'by'   => isset( $data['by'] ) ? absint( $data['by'] ) : 0,
	);
}

/**
 * Whether a member is blocked.
 *
 * @since 1.7.9
 *
 * @param int $user_id User ID.
 * @return bool
 */
function init_plugin_suite_user_engine_is_user_blocked( $user_id ) {
	$user_id = absint( $user_id );
	$blocked = $user_id && null !== init_plugin_suite_user_engine_get_user_block( $user_id );

	/**
	 * Filters whether a member is blocked.
	 *
	 * @since 1.7.9
	 *
	 * @param bool $blocked Whether the member is blocked.
	 * @param int  $user_id User ID.
	 */
	return (bool) apply_filters( 'init_plugin_suite_user_engine_is_user_blocked', $blocked, $user_id );
}

/**
 * Whether a member can ever be blocked: administrators (and super admins) cannot.
 *
 * @since 1.7.9
 *
 * @param int $user_id User ID.
 * @return bool
 */
function init_plugin_suite_user_engine_user_can_be_blocked( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id || ! get_userdata( $user_id ) ) {
		return false;
	}

	return ! user_can( $user_id, 'manage_options' ) && ! is_super_admin( $user_id );
}

/**
 * Whether the current user may block / unblock a member.
 *
 * @since 1.7.9
 *
 * @param int $user_id Target user ID.
 * @return bool
 */
function init_plugin_suite_user_engine_current_user_can_block( $user_id ) {
	$user_id = absint( $user_id );

	return $user_id
		&& get_current_user_id() !== $user_id
		&& current_user_can( 'edit_users' )
		&& current_user_can( 'edit_user', $user_id )
		&& init_plugin_suite_user_engine_user_can_be_blocked( $user_id );
}

/**
 * Block a member and sign them out of every device.
 *
 * @since 1.7.9
 *
 * @param int $user_id User ID.
 * @param int $by      ID of the admin who blocked the member.
 * @return bool True when the member was blocked by this call.
 */
function init_plugin_suite_user_engine_block_user( $user_id, $by = 0 ) {
	$user_id = absint( $user_id );

	if ( ! init_plugin_suite_user_engine_user_can_be_blocked( $user_id ) ) {
		return false;
	}

	if ( null !== init_plugin_suite_user_engine_get_user_block( $user_id ) ) {
		return false;
	}

	update_user_meta(
		$user_id,
		INIT_PLUGIN_SUITE_IUE_BLOCK_META,
		array(
			'time' => time(),
			'by'   => absint( $by ),
		)
	);

	// Sign out of every device: without this a member who is already logged in
	// would keep using the site until the login cookie expires.
	if ( class_exists( 'WP_Session_Tokens' ) ) {
		WP_Session_Tokens::get_instance( $user_id )->destroy_all();
	}

	/**
	 * Fires after a member is blocked.
	 *
	 * @since 1.7.9
	 *
	 * @param int $user_id User ID.
	 * @param int $by      ID of the admin who blocked the member.
	 */
	do_action( 'init_plugin_suite_user_engine_user_blocked', $user_id, absint( $by ) );

	return true;
}

/**
 * Unblock a member.
 *
 * @since 1.7.9
 *
 * @param int $user_id User ID.
 * @param int $by      ID of the admin who unblocked the member.
 * @return bool True when the member was unblocked by this call.
 */
function init_plugin_suite_user_engine_unblock_user( $user_id, $by = 0 ) {
	$user_id = absint( $user_id );

	if ( ! $user_id || null === init_plugin_suite_user_engine_get_user_block( $user_id ) ) {
		return false;
	}

	delete_user_meta( $user_id, INIT_PLUGIN_SUITE_IUE_BLOCK_META );

	/**
	 * Fires after a member is unblocked.
	 *
	 * @since 1.7.9
	 *
	 * @param int $user_id User ID.
	 * @param int $by      ID of the admin who unblocked the member.
	 */
	do_action( 'init_plugin_suite_user_engine_user_unblocked', $user_id, absint( $by ) );

	return true;
}

/**
 * Message shown to a blocked member who tries to log in.
 *
 * @since 1.7.9
 *
 * @return string
 */
function init_plugin_suite_user_engine_blocked_message() {
	/**
	 * Filters the message shown to a blocked member who tries to log in.
	 *
	 * @since 1.7.9
	 *
	 * @param string $message Message.
	 */
	return (string) apply_filters(
		'init_plugin_suite_user_engine_blocked_message',
		__( 'Your account has been blocked. Please contact the site administrator.', 'init-user-engine' )
	);
}

// =====================================================================
// Login guards
// =====================================================================

add_filter( 'authenticate', 'init_plugin_suite_user_engine_block_authenticate', 99 );

/**
 * Refuse the login of a blocked member.
 *
 * Runs after the password check (priority 99), so the block is only revealed to
 * someone who typed the right password. Covers the login form of the plugin,
 * wp-login.php, XML-RPC and every login that goes through wp_authenticate().
 *
 * @since 1.7.9
 *
 * @param WP_User|WP_Error|null $user Result so far.
 * @return WP_User|WP_Error|null
 */
function init_plugin_suite_user_engine_block_authenticate( $user ) {
	if ( $user instanceof WP_User && init_plugin_suite_user_engine_is_user_blocked( $user->ID ) ) {
		return new WP_Error( 'iue_account_blocked', init_plugin_suite_user_engine_blocked_message() );
	}

	return $user;
}

add_action( 'wp_authenticate_application_password_errors', 'init_plugin_suite_user_engine_block_application_password', 10, 2 );

/**
 * Refuse Application Passwords of a blocked member.
 *
 * @since 1.7.9
 *
 * @param WP_Error $error Error object.
 * @param WP_User  $user  User.
 * @return void
 */
function init_plugin_suite_user_engine_block_application_password( $error, $user ) {
	if ( $error instanceof WP_Error && $user instanceof WP_User && init_plugin_suite_user_engine_is_user_blocked( $user->ID ) ) {
		$error->add( 'iue_account_blocked', init_plugin_suite_user_engine_blocked_message() );
	}
}

add_filter( 'determine_current_user', 'init_plugin_suite_user_engine_block_current_user', 100 );

/**
 * Treat a blocked member as logged out, whatever cookie or token they still hold.
 *
 * Blocking already signs the member out; this is the safety net for logins that
 * survive it (e.g. a block added straight to the database).
 *
 * @since 1.7.9
 *
 * @param int|false $user_id User ID found so far.
 * @return int|false
 */
function init_plugin_suite_user_engine_block_current_user( $user_id ) {
	if ( $user_id && init_plugin_suite_user_engine_is_user_blocked( (int) $user_id ) ) {
		return false;
	}

	return $user_id;
}

// =====================================================================
// Admin: Users screen
// =====================================================================

/**
 * Admin URL that blocks / unblocks one member.
 *
 * @since 1.7.9
 *
 * @param int    $user_id User ID.
 * @param string $to      'block' or 'unblock'.
 * @return string
 */
function init_plugin_suite_user_engine_block_action_url( $user_id, $to ) {
	$user_id = absint( $user_id );
	$to      = ( 'unblock' === $to ) ? 'unblock' : 'block';

	return wp_nonce_url(
		add_query_arg(
			array(
				'action'  => 'iue_block_user',
				'user_id' => $user_id,
				'to'      => $to,
			),
			admin_url( 'admin-post.php' )
		),
		'iue_block_user_' . $user_id
	);
}

add_filter( 'user_row_actions', 'init_plugin_suite_user_engine_block_row_action', 10, 2 );

/**
 * Add "Block" / "Unblock" to the row actions on Users.
 *
 * @since 1.7.9
 *
 * @param string[] $actions Row actions.
 * @param WP_User  $user    User of the row.
 * @return string[]
 */
function init_plugin_suite_user_engine_block_row_action( $actions, $user ) {
	if ( ! $user instanceof WP_User || ! init_plugin_suite_user_engine_current_user_can_block( $user->ID ) ) {
		return $actions;
	}

	if ( init_plugin_suite_user_engine_is_user_blocked( $user->ID ) ) {
		$actions['iue_unblock'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( init_plugin_suite_user_engine_block_action_url( $user->ID, 'unblock' ) ),
			esc_html__( 'Unblock', 'init-user-engine' )
		);
	} else {
		$actions['iue_block'] = sprintf(
			'<a href="%1$s" style="color:#b32d2e" onclick="return confirm(\'%2$s\');">%3$s</a>',
			esc_url( init_plugin_suite_user_engine_block_action_url( $user->ID, 'block' ) ),
			esc_js( __( 'Block this member? They will be logged out and cannot log in until you unblock them.', 'init-user-engine' ) ),
			esc_html__( 'Block', 'init-user-engine' )
		);
	}

	return $actions;
}

add_action( 'admin_post_iue_block_user', 'init_plugin_suite_user_engine_handle_block_user' );

/**
 * Handle the "Block" / "Unblock" link (Users row and user profile).
 *
 * URL: admin-post.php?action=iue_block_user&user_id=###&to=block|unblock&_wpnonce=...
 *
 * @since 1.7.9
 *
 * @return void
 */
function init_plugin_suite_user_engine_handle_block_user() {
	$user_id = isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The nonce is checked right below and needs the user ID.
	$to      = ( isset( $_GET['to'] ) && 'unblock' === sanitize_key( wp_unslash( $_GET['to'] ) ) ) ? 'unblock' : 'block'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! current_user_can( 'edit_users' ) ) {
		init_plugin_suite_user_engine_security_event(
			'admin-post/iue_block_user',
			'no_permission_block_user',
			array( 'target_user_id' => $user_id )
		);
		wp_die( esc_html__( 'You do not have permission to perform this action.', 'init-user-engine' ), '', array( 'response' => 403 ) );
	}

	check_admin_referer( 'iue_block_user_' . $user_id );

	$done    = 0;
	$skipped = 0;

	if ( init_plugin_suite_user_engine_current_user_can_block( $user_id ) ) {
		$changed = ( 'unblock' === $to )
			? init_plugin_suite_user_engine_unblock_user( $user_id, get_current_user_id() )
			: init_plugin_suite_user_engine_block_user( $user_id, get_current_user_id() );

		$done = $changed ? 1 : 0;
	} else {
		$skipped = 1;
	}

	$back = wp_get_referer();
	if ( ! $back ) {
		$back = admin_url( 'users.php' );
	}

	wp_safe_redirect( init_plugin_suite_user_engine_block_result_url( $back, $to, $done, $skipped ) );
	exit;
}

/**
 * Add the result of a block / unblock to a URL, for the admin notice.
 *
 * @since 1.7.9
 *
 * @param string $url     URL to go back to.
 * @param string $to      'block' or 'unblock'.
 * @param int    $done    Members changed.
 * @param int    $skipped Members that cannot be blocked (admins, yourself).
 * @return string
 */
function init_plugin_suite_user_engine_block_result_url( $url, $to, $done, $skipped ) {
	$url = remove_query_arg( array( 'iue_block_done', 'iue_block_to', 'iue_block_skipped', '_iue_block_notice' ), $url );

	return add_query_arg(
		array(
			'iue_block_to'      => ( 'unblock' === $to ) ? 'unblock' : 'block',
			'iue_block_done'    => absint( $done ),
			'iue_block_skipped' => absint( $skipped ),
			'_iue_block_notice' => wp_create_nonce( 'iue_block_notice_' . get_current_user_id() ),
		),
		$url
	);
}

add_filter( 'bulk_actions-users', 'init_plugin_suite_user_engine_block_bulk_actions' );

/**
 * Add "Block" and "Unblock" to the bulk actions on Users.
 *
 * @since 1.7.9
 *
 * @param string[] $actions Bulk actions.
 * @return string[]
 */
function init_plugin_suite_user_engine_block_bulk_actions( $actions ) {
	if ( current_user_can( 'edit_users' ) ) {
		$actions['iue_block']   = __( 'Block', 'init-user-engine' );
		$actions['iue_unblock'] = __( 'Unblock', 'init-user-engine' );
	}

	return $actions;
}

add_filter( 'handle_bulk_actions-users', 'init_plugin_suite_user_engine_handle_block_bulk', 10, 3 );

/**
 * Handle the "Block" / "Unblock" bulk actions. WordPress has already checked the
 * bulk-users nonce at this point.
 *
 * @since 1.7.9
 *
 * @param string $sendback Redirect URL.
 * @param string $doaction Bulk action.
 * @param int[]  $user_ids Selected users.
 * @return string
 */
function init_plugin_suite_user_engine_handle_block_bulk( $sendback, $doaction, $user_ids ) {
	if ( 'iue_block' !== $doaction && 'iue_unblock' !== $doaction ) {
		return $sendback;
	}

	if ( ! current_user_can( 'edit_users' ) ) {
		init_plugin_suite_user_engine_security_event( 'users.php/' . $doaction, 'no_permission_block_user' );
		return $sendback;
	}

	$to      = ( 'iue_unblock' === $doaction ) ? 'unblock' : 'block';
	$by      = get_current_user_id();
	$done    = 0;
	$skipped = 0;

	foreach ( array_unique( array_map( 'absint', (array) $user_ids ) ) as $user_id ) {
		if ( ! init_plugin_suite_user_engine_current_user_can_block( $user_id ) ) {
			$skipped++;
			continue;
		}

		$changed = ( 'unblock' === $to )
			? init_plugin_suite_user_engine_unblock_user( $user_id, $by )
			: init_plugin_suite_user_engine_block_user( $user_id, $by );

		if ( $changed ) {
			$done++;
		}
	}

	return init_plugin_suite_user_engine_block_result_url( $sendback, $to, $done, $skipped );
}

add_action( 'admin_notices', 'init_plugin_suite_user_engine_block_admin_notice' );

/**
 * Notice after blocking / unblocking (Users and user profile screens).
 *
 * @since 1.7.9
 *
 * @return void
 */
function init_plugin_suite_user_engine_block_admin_notice() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display only; the notice nonce is checked below.
	if ( ! isset( $_GET['iue_block_to'], $_GET['iue_block_done'], $_GET['_iue_block_notice'] ) || ! current_user_can( 'edit_users' ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_GET['_iue_block_notice'] ) );
	if ( ! wp_verify_nonce( $nonce, 'iue_block_notice_' . get_current_user_id() ) ) {
		return;
	}

	$to      = ( 'unblock' === sanitize_key( wp_unslash( $_GET['iue_block_to'] ) ) ) ? 'unblock' : 'block';
	$done    = absint( wp_unslash( $_GET['iue_block_done'] ) );
	$skipped = isset( $_GET['iue_block_skipped'] ) ? absint( wp_unslash( $_GET['iue_block_skipped'] ) ) : 0;
	// phpcs:enable

	$messages = array();

	if ( 'unblock' === $to ) {
		/* translators: %s: number of members */
		$messages[] = sprintf( _n( '%s member unblocked.', '%s members unblocked.', $done, 'init-user-engine' ), number_format_i18n( $done ) );
	} else {
		/* translators: %s: number of members */
		$messages[] = sprintf( _n( '%s member blocked and logged out.', '%s members blocked and logged out.', $done, 'init-user-engine' ), number_format_i18n( $done ) );
	}

	if ( $skipped ) {
		/* translators: %s: number of accounts */
		$messages[] = sprintf( _n( '%s account was skipped: administrators and your own account cannot be blocked.', '%s accounts were skipped: administrators and your own account cannot be blocked.', $skipped, 'init-user-engine' ), number_format_i18n( $skipped ) );
	}

	printf(
		'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
		esc_attr( $done ? 'notice-success' : 'notice-warning' ),
		esc_html( implode( ' ', $messages ) )
	);
}

add_filter( 'manage_users_columns', 'init_plugin_suite_user_engine_block_column' );

/**
 * Add the "Blocked" column on Users.
 *
 * @since 1.7.9
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function init_plugin_suite_user_engine_block_column( $columns ) {
	if ( current_user_can( 'edit_users' ) ) {
		$columns['iue_blocked'] = __( 'Blocked', 'init-user-engine' );
	}

	return $columns;
}

add_filter( 'manage_users_custom_column', 'init_plugin_suite_user_engine_block_column_value', 10, 3 );

/**
 * Value of the "Blocked" column.
 *
 * @since 1.7.9
 *
 * @param string $output  Output so far.
 * @param string $column  Column name.
 * @param int    $user_id User ID.
 * @return string
 */
function init_plugin_suite_user_engine_block_column_value( $output, $column, $user_id ) {
	if ( 'iue_blocked' !== $column ) {
		return $output;
	}

	$block = init_plugin_suite_user_engine_get_user_block( $user_id );
	if ( null === $block ) {
		return '<span aria-hidden="true">—</span>';
	}

	return '<strong style="color:#b32d2e">' . esc_html__( 'Blocked', 'init-user-engine' ) . '</strong>'
		. init_plugin_suite_user_engine_block_meta_html( $block );
}

/**
 * "date · by admin" line of a block, as HTML.
 *
 * @since 1.7.9
 *
 * @param array $block Block data.
 * @return string
 */
function init_plugin_suite_user_engine_block_meta_html( array $block ) {
	$parts = array();

	if ( $block['time'] ) {
		$parts[] = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $block['time'] );
	}

	if ( $block['by'] ) {
		$admin = get_userdata( $block['by'] );
		if ( $admin ) {
			/* translators: %s: display name of the admin who blocked the member */
			$parts[] = sprintf( __( 'by %s', 'init-user-engine' ), $admin->display_name );
		}
	}

	if ( ! $parts ) {
		return '';
	}

	return '<br><span class="description">' . esc_html( implode( ' · ', $parts ) ) . '</span>';
}

add_filter( 'views_users', 'init_plugin_suite_user_engine_block_view' );

/**
 * Add the "Blocked (n)" view on Users.
 *
 * @since 1.7.9
 *
 * @param string[] $views Views.
 * @return string[]
 */
function init_plugin_suite_user_engine_block_view( $views ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return $views;
	}

	$count = (int) ( new WP_User_Query(
		array(
			'meta_key'    => INIT_PLUGIN_SUITE_IUE_BLOCK_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'number'      => 1,
			'fields'      => 'ID',
			'count_total' => true,
		)
	) )->get_total();

	$current = init_plugin_suite_user_engine_is_block_view();

	if ( ! $count && ! $current ) {
		return $views;
	}

	if ( $current && isset( $views['all'] ) ) {
		$views['all'] = str_replace( array( ' class="current"', ' aria-current="page"' ), '', $views['all'] );
	}

	$views['iue_blocked'] = sprintf(
		'<a href="%1$s"%2$s>%3$s <span class="count">(%4$s)</span></a>',
		esc_url( add_query_arg( 'iue_blocked', '1', admin_url( 'users.php' ) ) ),
		$current ? ' class="current" aria-current="page"' : '',
		esc_html__( 'Blocked', 'init-user-engine' ),
		esc_html( number_format_i18n( $count ) )
	);

	return $views;
}

/**
 * Whether the Users screen is showing the "Blocked" view.
 *
 * @since 1.7.9
 *
 * @return bool
 */
function init_plugin_suite_user_engine_is_block_view() {
	global $pagenow;

	return is_admin() && 'users.php' === $pagenow && ! empty( $_GET['iue_blocked'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
}

add_action( 'pre_get_users', 'init_plugin_suite_user_engine_block_view_query' );

/**
 * Show only blocked members in the "Blocked" view.
 *
 * @since 1.7.9
 *
 * @param WP_User_Query $query Query.
 * @return void
 */
function init_plugin_suite_user_engine_block_view_query( $query ) {
	if ( ! init_plugin_suite_user_engine_is_block_view() || ! current_user_can( 'edit_users' ) || ! function_exists( 'get_current_screen' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'users' !== $screen->id ) {
		return;
	}

	$meta_query   = (array) $query->get( 'meta_query' );
	$meta_query[] = array(
		'key'     => INIT_PLUGIN_SUITE_IUE_BLOCK_META,
		'compare' => 'EXISTS',
	);
	$query->set( 'meta_query', $meta_query );
}

/**
 * Block status + "Block / Unblock Account" button for the User Engine box on the
 * user's profile.
 *
 * @since 1.7.9
 *
 * @param int $user_id User ID.
 * @return void
 */
function init_plugin_suite_user_engine_render_block_toggle( $user_id ) {
	if ( ! init_plugin_suite_user_engine_current_user_can_block( $user_id ) ) {
		return;
	}

	$block = init_plugin_suite_user_engine_get_user_block( $user_id );
	?>
	<p class="iue-meta" style="margin-top:6px;">
		<?php if ( null === $block ) : ?>
			<a class="button button-secondary" href="<?php echo esc_url( init_plugin_suite_user_engine_block_action_url( $user_id, 'block' ) ); ?>"
				onclick="return confirm('<?php echo esc_js( __( 'Block this member? They will be logged out and cannot log in until you unblock them.', 'init-user-engine' ) ); ?>');">
				<?php esc_html_e( 'Block Account', 'init-user-engine' ); ?>
			</a>
			<span class="description" style="margin-left:8px;"><?php esc_html_e( 'Currently: Can log in', 'init-user-engine' ); ?></span>
		<?php else : ?>
			<a class="button button-secondary" href="<?php echo esc_url( init_plugin_suite_user_engine_block_action_url( $user_id, 'unblock' ) ); ?>">
				<?php esc_html_e( 'Unblock Account', 'init-user-engine' ); ?>
			</a>
			<span class="description" style="margin-left:8px;">
				<?php esc_html_e( 'Currently: BLOCKED', 'init-user-engine' ); ?><?php echo wp_kses( init_plugin_suite_user_engine_block_meta_html( $block ), array( 'br' => array(), 'span' => array( 'class' => array() ) ) ); ?>
			</span>
		<?php endif; ?>
	</p>
	<?php
}
