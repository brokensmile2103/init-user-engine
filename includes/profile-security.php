<?php
/**
 * Edit Profile hardening.
 *
 * Helpers used by the Edit Profile modal (REST `profile/me` and `profile/update`):
 * field length limits (configurable on the settings page), input type checks,
 * removal of invisible / text-direction characters, strict URL checks, a request
 * rate limit, and a safe password change that asks for the current password.
 *
 * @package Init_User_Engine
 * @since   1.7.6
 */

defined( 'ABSPATH' ) || exit;

// -----------------------------------------------------------------------------
// Field length limits
// -----------------------------------------------------------------------------

/**
 * Default and maximum allowed value of each profile field length limit.
 *
 * The "cap" is a hard ceiling the setting can never go above (for the display
 * name it is the size of the `display_name` column in the users table).
 *
 * @since 1.7.6
 *
 * @return array<string, array{default: int, cap: int}> Keyed by field.
 */
function init_plugin_suite_user_engine_profile_limit_bounds() {
	return array(
		'display_name' => array(
			'default' => 50,
			'cap'     => 250,
		),
		'bio'          => array(
			'default' => 2000,
			'cap'     => 10000,
		),
		'discord'      => array(
			'default' => 100,
			'cap'     => 255,
		),
		'url'          => array(
			'default' => 255,
			'cap'     => 2048,
		),
	);
}

/**
 * Clamp a raw limit value to 1..cap, or return the default when it is not a positive number.
 *
 * @since 1.7.6
 *
 * @param mixed $value Raw value.
 * @param int   $def   Default value.
 * @param int   $cap   Hard ceiling.
 * @return int
 */
function init_plugin_suite_user_engine_clamp_profile_limit( $value, $def, $cap ) {
	$value = is_numeric( $value ) ? (int) $value : 0;

	if ( $value < 1 ) {
		return (int) $def;
	}

	return min( (int) $cap, $value );
}

/**
 * Maximum number of characters allowed in each Edit Profile field.
 *
 * Keys: `display_name`, `bio`, `discord`, `url` (Facebook, Twitter and Website).
 *
 * @since 1.7.6
 *
 * @return array<string, int>
 */
function init_plugin_suite_user_engine_get_profile_field_limits() {
	$settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, array() );
	$settings = is_array( $settings ) ? $settings : array();
	$bounds   = init_plugin_suite_user_engine_profile_limit_bounds();
	$limits   = array();

	foreach ( $bounds as $field => $bound ) {
		$key              = 'profile_max_' . $field;
		$limits[ $field ] = init_plugin_suite_user_engine_clamp_profile_limit(
			isset( $settings[ $key ] ) ? $settings[ $key ] : $bound['default'],
			$bound['default'],
			$bound['cap']
		);
	}

	/**
	 * Filters the maximum number of characters allowed in each Edit Profile field.
	 *
	 * Values are clamped again afterwards, so a filter can never go above the hard ceiling.
	 *
	 * @since 1.7.6
	 *
	 * @param array<string, int> $limits Keys: display_name, bio, discord, url.
	 */
	$filtered = apply_filters( 'init_plugin_suite_user_engine_profile_field_limits', $limits );
	$filtered = is_array( $filtered ) ? $filtered : array();

	foreach ( $bounds as $field => $bound ) {
		$limits[ $field ] = init_plugin_suite_user_engine_clamp_profile_limit(
			isset( $filtered[ $field ] ) ? $filtered[ $field ] : $limits[ $field ],
			$limits[ $field ],
			$bound['cap']
		);
	}

	return $limits;
}

/**
 * Length of a string in characters (not bytes).
 *
 * @since 1.7.6
 *
 * @param string $text Text.
 * @return int
 */
function init_plugin_suite_user_engine_profile_strlen( $text ) {
	// WordPress provides mb_strlen() in wp-includes/compat.php when mbstring is missing.
	return mb_strlen( (string) $text, 'UTF-8' );
}

/**
 * Return an error when a field is longer than its limit.
 *
 * @since 1.7.6
 *
 * @param string $value Normalized value.
 * @param string $field Request field name (display_name, bio, facebook...).
 * @param string $label Translated field label.
 * @param int    $max   Maximum number of characters.
 * @return true|WP_Error
 */
function init_plugin_suite_user_engine_check_profile_length( $value, $field, $label, $max ) {
	if ( init_plugin_suite_user_engine_profile_strlen( $value ) <= $max ) {
		return true;
	}

	return new WP_Error(
		'field_too_long',
		sprintf(
			/* translators: 1: Field label (e.g. Bio). 2: Maximum number of characters. */
			__( '%1$s is too long (maximum %2$d characters).', 'init-user-engine' ),
			$label,
			$max
		),
		array(
			'status' => 400,
			'field'  => $field,
			'max'    => (int) $max,
		)
	);
}

// -----------------------------------------------------------------------------
// Input normalization
// -----------------------------------------------------------------------------

/**
 * Read one request field as a string.
 *
 * Missing or null fields become an empty string. Numbers are accepted as text.
 * Arrays, objects and booleans are rejected, so they can never reach string
 * functions (on PHP 8 `trim( array() )` is a fatal error).
 *
 * @since 1.7.6
 *
 * @param array  $data  Request body.
 * @param string $field Field name.
 * @return string|WP_Error
 */
function init_plugin_suite_user_engine_profile_string_param( $data, $field ) {
	if ( ! isset( $data[ $field ] ) ) {
		return '';
	}

	$value = $data[ $field ];

	if ( is_string( $value ) ) {
		return $value;
	}

	if ( is_int( $value ) || is_float( $value ) ) {
		return (string) $value;
	}

	return new WP_Error(
		'invalid_param',
		__( 'Invalid profile data.', 'init-user-engine' ),
		array(
			'status' => 400,
			'field'  => $field,
		)
	);
}

/**
 * Remove invisible characters, text-direction controls and control characters.
 *
 * Stops "blank" or look-alike names built with zero-width spaces, Hangul fillers
 * or right-to-left overrides. Characters needed by real text and emoji are kept:
 * zero-width joiner / non-joiner (U+200C, U+200D) and variation selectors.
 *
 * @since 1.7.6
 *
 * @param string $text           Text (valid UTF-8).
 * @param bool   $allow_newlines Optional. Keep line breaks and tabs. Default false.
 * @return string
 */
function init_plugin_suite_user_engine_strip_invisible_chars( $text, $allow_newlines = false ) {
	$text = (string) $text;

	// Soft hyphen, Arabic letter mark, Hangul fillers, Mongolian vowel separator,
	// zero-width space, LRM/RLM, bidi embeddings/overrides/isolates, word joiner and
	// invisible operators, BOM, half-width Hangul filler.
	$invisible = '/[\x{00AD}\x{061C}\x{115F}\x{1160}\x{180E}\x{200B}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{2066}-\x{206F}\x{3164}\x{FEFF}\x{FFA0}]/u';
	$cleaned   = preg_replace( $invisible, '', $text );

	if ( null === $cleaned ) {
		return '';
	}

	// Control characters (C0, DEL and C1).
	$controls = $allow_newlines ? '/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]/u' : '/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u';
	$cleaned  = preg_replace( $controls, $allow_newlines ? '' : ' ', $cleaned );

	return null === $cleaned ? '' : $cleaned;
}

/**
 * Normalize a display name: no HTML, no invisible characters, single spaces.
 *
 * @since 1.7.6
 *
 * @param string $raw Raw value.
 * @return string
 */
function init_plugin_suite_user_engine_normalize_display_name( $raw ) {
	$name = wp_check_invalid_utf8( (string) $raw, true );
	$name = wp_strip_all_tags( $name );
	$name = init_plugin_suite_user_engine_strip_invisible_chars( $name );
	$name = preg_replace( '/\s+/u', ' ', $name );

	return trim( (string) $name );
}

/**
 * Normalize the bio: plain text, line breaks kept, no invisible characters.
 *
 * @since 1.7.6
 *
 * @param string $raw Raw value.
 * @return string
 */
function init_plugin_suite_user_engine_normalize_bio( $raw ) {
	$bio = str_replace( array( "\r\n", "\r" ), "\n", (string) $raw );
	$bio = sanitize_textarea_field( $bio );
	$bio = init_plugin_suite_user_engine_strip_invisible_chars( $bio, true );

	return trim( $bio );
}

/**
 * Normalize a single-line text field (Discord).
 *
 * @since 1.7.6
 *
 * @param string $raw Raw value.
 * @return string
 */
function init_plugin_suite_user_engine_normalize_profile_text( $raw ) {
	$text = sanitize_text_field( (string) $raw );
	$text = init_plugin_suite_user_engine_strip_invisible_chars( $text );

	return trim( $text );
}

/**
 * Allowed values of the Gender field (the empty string means "Prefer not to say").
 *
 * @since 1.7.6
 *
 * @return string[]
 */
function init_plugin_suite_user_engine_profile_allowed_genders() {
	/**
	 * Filters the allowed values of the Gender field.
	 *
	 * @since 1.7.6
	 *
	 * @param string[] $genders Default: '', 'male', 'female', 'other'.
	 */
	$genders = apply_filters( 'init_plugin_suite_user_engine_profile_genders', array( '', 'male', 'female', 'other' ) );
	$genders = array_map( 'sanitize_key', (array) $genders );

	if ( ! in_array( '', $genders, true ) ) {
		$genders[] = '';
	}

	return $genders;
}

// -----------------------------------------------------------------------------
// URL fields
// -----------------------------------------------------------------------------

/**
 * Domains accepted for a URL field. An empty list means any domain.
 *
 * Subdomains are accepted too (m.facebook.com, mobile.twitter.com...).
 *
 * @since 1.7.6
 *
 * @param string $field Field name: facebook, twitter or website.
 * @return string[]
 */
function init_plugin_suite_user_engine_profile_url_hosts( $field ) {
	$hosts = array(
		'facebook' => array( 'facebook.com', 'fb.com', 'fb.me' ),
		'twitter'  => array( 'twitter.com', 'x.com' ),
		'website'  => array(),
	);

	$list = isset( $hosts[ $field ] ) ? $hosts[ $field ] : array();

	/**
	 * Filters the domains accepted for a profile URL field.
	 *
	 * Return an empty array to accept any domain.
	 *
	 * @since 1.7.6
	 *
	 * @param string[] $list  Domains (without "www.").
	 * @param string   $field Field name: facebook, twitter or website.
	 */
	$list = apply_filters( 'init_plugin_suite_user_engine_profile_url_hosts', $list, $field );

	return array_values( array_filter( array_map( 'strtolower', array_map( 'strval', (array) $list ) ) ) );
}

/**
 * Validate and clean a profile URL (http/https only, optional domain list).
 *
 * @since 1.7.6
 *
 * @param string $raw   Raw value.
 * @param string $field Field name: facebook, twitter or website.
 * @param string $label Translated field label.
 * @param int    $max   Maximum number of characters.
 * @return string|WP_Error Clean URL, or an empty string when the field is empty.
 */
function init_plugin_suite_user_engine_validate_profile_url( $raw, $field, $label, $max ) {
	$raw = trim( init_plugin_suite_user_engine_strip_invisible_chars( wp_check_invalid_utf8( (string) $raw, true ) ) );

	if ( '' === $raw ) {
		return '';
	}

	// Check the raw length too, so a huge value is refused before any parsing.
	$length = init_plugin_suite_user_engine_check_profile_length( $raw, $field, $label, $max );
	if ( is_wp_error( $length ) ) {
		return $length;
	}

	$invalid = new WP_Error(
		'invalid_url',
		sprintf(
			/* translators: %s: Field label (e.g. Facebook). */
			__( '%s must be a valid link starting with http:// or https://.', 'init-user-engine' ),
			$label
		),
		array(
			'status' => 400,
			'field'  => $field,
		)
	);

	// Anything with an explicit scheme other than http(s) is refused (mailto:, ftp:, javascript:...).
	if ( preg_match( '/^[a-z][a-z0-9+.\-]*:/i', $raw ) && ! preg_match( '#^https?://#i', $raw ) ) {
		return $invalid;
	}

	$url = esc_url_raw( $raw, array( 'http', 'https' ) );
	if ( '' === $url ) {
		return $invalid;
	}

	$parts = wp_parse_url( $url );
	if (
		! is_array( $parts )
		|| empty( $parts['scheme'] )
		|| ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true )
		|| empty( $parts['host'] )
		// "https://facebook.com@example.com" really opens example.com: refuse user:pass@ links.
		|| isset( $parts['user'] )
		|| isset( $parts['pass'] )
	) {
		return $invalid;
	}

	$host = strtolower( rtrim( $parts['host'], '.' ) );

	// A real domain name: letters, digits, dots and hyphens (IDN allowed), with at least one dot.
	if ( ! preg_match( '/^(?=.{1,253}$)[\p{L}\p{N}](?:[\p{L}\p{N}\-]*[\p{L}\p{N}])?(?:\.[\p{L}\p{N}](?:[\p{L}\p{N}\-]*[\p{L}\p{N}])?)+$/u', $host ) ) {
		return $invalid;
	}

	$allowed = init_plugin_suite_user_engine_profile_url_hosts( $field );

	if ( $allowed ) {
		$matched = false;

		foreach ( $allowed as $domain ) {
			if ( $host === $domain || substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
				$matched = true;
				break;
			}
		}

		if ( ! $matched ) {
			return new WP_Error(
				'invalid_url_domain',
				sprintf(
					/* translators: 1: Field label (e.g. Facebook). 2: Comma-separated list of domains. */
					__( '%1$s must be a link to %2$s.', 'init-user-engine' ),
					$label,
					implode( ', ', $allowed )
				),
				array(
					'status' => 400,
					'field'  => $field,
				)
			);
		}
	}

	$length = init_plugin_suite_user_engine_check_profile_length( $url, $field, $label, $max );

	return is_wp_error( $length ) ? $length : $url;
}

// -----------------------------------------------------------------------------
// Rate limits
// -----------------------------------------------------------------------------

/**
 * Count one hit on a per-user rate limit and tell whether the limit is reached.
 *
 * @since 1.7.6
 * @access private
 *
 * @param string $key    Transient key.
 * @param int    $max    Hits allowed per window.
 * @param int    $window Window length in seconds.
 * @return bool True when the request may continue.
 */
function init_plugin_suite_user_engine_hit_rate_limit( $key, $max, $window ) {
	$hits = (int) get_transient( $key );

	if ( $max > 0 && $hits >= $max ) {
		return false;
	}

	set_transient( $key, $hits + 1, max( 1, (int) $window ) );

	return true;
}

/**
 * Rate limit of the Edit Profile save button (default: 10 saves per minute).
 *
 * @since 1.7.6
 *
 * @param int $user_id User ID.
 * @return true|WP_Error
 */
function init_plugin_suite_user_engine_profile_rate_limit( $user_id ) {
	/**
	 * Filters how many times a member may save the Edit Profile form per minute.
	 *
	 * Return 0 to disable the limit.
	 *
	 * @since 1.7.6
	 *
	 * @param int $max     Saves per minute. Default 10.
	 * @param int $user_id User ID.
	 */
	$max = absint( apply_filters( 'init_plugin_suite_user_engine_profile_update_rate_limit', 10, $user_id ) );

	if ( init_plugin_suite_user_engine_hit_rate_limit( 'iue_profile_rl_' . absint( $user_id ), $max, MINUTE_IN_SECONDS ) ) {
		return true;
	}

	return new WP_Error(
		'rate_limited',
		__( 'Too many attempts. Please wait a moment and try again.', 'init-user-engine' ),
		array( 'status' => 429 )
	);
}

// -----------------------------------------------------------------------------
// Password change
// -----------------------------------------------------------------------------

/**
 * Whether the member must enter the current password to set a new one.
 *
 * @since 1.7.6
 *
 * @param int $user_id User ID.
 * @return bool
 */
function init_plugin_suite_user_engine_requires_current_password( $user_id ) {
	/**
	 * Filters whether the current password is required to change the password in the Edit Profile modal.
	 *
	 * Turning this off is not recommended: anyone with access to a signed-in browser
	 * could then take over the account.
	 *
	 * @since 1.7.6
	 *
	 * @param bool $required Default true.
	 * @param int  $user_id  User ID.
	 */
	return (bool) apply_filters( 'init_plugin_suite_user_engine_require_current_password', true, absint( $user_id ) );
}

/**
 * Check a new password with the same rules as the registration form.
 *
 * @since 1.7.6
 *
 * @param string  $password New password.
 * @param WP_User $user     User.
 * @return true|WP_Error
 */
function init_plugin_suite_user_engine_validate_new_password( $password, $user ) {
	$error = static function ( $code, $message ) {
		return new WP_Error(
			$code,
			$message,
			array(
				'status' => 400,
				'field'  => 'new_password',
			)
		);
	};

	/**
	 * Filters the maximum length of a new password.
	 *
	 * @since 1.7.6
	 *
	 * @param int $max Default 128.
	 */
	$max = max( 8, absint( apply_filters( 'init_plugin_suite_user_engine_password_max_length', 128 ) ) );

	if ( strlen( $password ) < 6 ) {
		return $error( 'weak_password', __( 'Password must be at least 6 characters.', 'init-user-engine' ) );
	}

	if ( init_plugin_suite_user_engine_profile_strlen( $password ) > $max ) {
		return $error(
			'password_too_long',
			sprintf(
				/* translators: %d: Maximum number of characters. */
				__( 'Password is too long (maximum %d characters).', 'init-user-engine' ),
				$max
			)
		);
	}

	if ( ! preg_match( '/^(?=.*[a-zA-Z])(?=.*\d)/', $password ) ) {
		return $error( 'weak_password', __( 'Password must contain both letters and numbers.', 'init-user-engine' ) );
	}

	if ( wp_check_password( $password, $user->user_pass, $user->ID ) ) {
		return $error( 'same_password', __( 'The new password must be different from the current password.', 'init-user-engine' ) );
	}

	/**
	 * Filters the result of the new password check.
	 *
	 * Return a WP_Error to refuse the password (for example a stronger policy).
	 *
	 * @since 1.7.6
	 *
	 * @param true|WP_Error $result   True when accepted.
	 * @param string        $password New password.
	 * @param WP_User       $user     User.
	 */
	$result = apply_filters( 'init_plugin_suite_user_engine_validate_new_password', true, $password, $user );

	return is_wp_error( $result ) ? $result : true;
}

/**
 * Check the current password, with a limit on wrong attempts.
 *
 * Default: 5 wrong attempts per 15 minutes, then the password cannot be changed
 * until the window ends.
 *
 * @since 1.7.6
 *
 * @param WP_User $user     User.
 * @param string  $password Password typed by the member.
 * @return true|WP_Error
 */
function init_plugin_suite_user_engine_verify_current_password( $user, $password ) {
	$key = 'iue_pw_fail_' . absint( $user->ID );

	/**
	 * Filters how many wrong current passwords are allowed per 15 minutes.
	 *
	 * @since 1.7.6
	 *
	 * @param int $max     Default 5.
	 * @param int $user_id User ID.
	 */
	$max   = max( 1, absint( apply_filters( 'init_plugin_suite_user_engine_current_password_attempts', 5, $user->ID ) ) );
	$fails = (int) get_transient( $key );

	if ( $fails >= $max ) {
		return new WP_Error(
			'too_many_password_attempts',
			__( 'Too many wrong passwords. Please try again in 15 minutes, or use "Lost password".', 'init-user-engine' ),
			array(
				'status' => 429,
				'field'  => 'current_password',
			)
		);
	}

	if ( '' === $password ) {
		return new WP_Error(
			'current_password_required',
			__( 'Please enter your current password to set a new one.', 'init-user-engine' ),
			array(
				'status' => 400,
				'field'  => 'current_password',
			)
		);
	}

	if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
		set_transient( $key, $fails + 1, 15 * MINUTE_IN_SECONDS );

		return new WP_Error(
			'incorrect_current_password',
			__( 'Your current password is incorrect.', 'init-user-engine' ),
			array(
				'status' => 403,
				'field'  => 'current_password',
			)
		);
	}

	delete_transient( $key );

	return true;
}

/**
 * Change the signed-in member's password and sign out their other devices.
 *
 * Uses wp_update_user(), so WordPress sends its "password changed" email and
 * issues a new login cookie for this browser (the member stays signed in here).
 * Every other session of the member is then ended.
 *
 * @since 1.7.6
 *
 * @param int    $user_id  User ID (must be the signed-in user).
 * @param string $password New password (already validated).
 * @return string|WP_Error New REST nonce for this browser, or an error.
 */
function init_plugin_suite_user_engine_change_own_password( $user_id, $password ) {
	$user_id  = absint( $user_id );
	$captured = array(
		'token'  => '',
		'cookie' => '',
	);

	$capture = static function ( $logged_in_cookie, $expire, $expiration, $cookie_user_id, $scheme, $token ) use ( &$captured, $user_id ) {
		if ( (int) $cookie_user_id === $user_id ) {
			$captured['token']  = (string) $token;
			$captured['cookie'] = (string) $logged_in_cookie;
		}
	};

	add_action( 'set_logged_in_cookie', $capture, 10, 6 );

	$result = wp_update_user(
		array(
			'ID'        => $user_id,
			'user_pass' => wp_slash( $password ),
		)
	);

	remove_action( 'set_logged_in_cookie', $capture, 10 );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( 'password_update_failed', __( 'Could not update your password. Please try again.', 'init-user-engine' ), array( 'status' => 500 ) );
	}

	if ( '' !== $captured['token'] && class_exists( 'WP_Session_Tokens' ) ) {
		WP_Session_Tokens::get_instance( $user_id )->destroy_others( $captured['token'] );

		// Let wp_create_nonce() see the new session, so the nonce we return matches the new cookie.
		$_COOKIE[ LOGGED_IN_COOKIE ] = $captured['cookie'];
	}

	/**
	 * Fires after a member changed their password from the Edit Profile modal.
	 *
	 * @since 1.7.6
	 *
	 * @param int $user_id User ID.
	 */
	do_action( 'init_plugin_suite_user_engine_password_changed', $user_id );

	return wp_create_nonce( 'wp_rest' );
}

// -----------------------------------------------------------------------------
// Avatar upload
// -----------------------------------------------------------------------------

/**
 * File types accepted for avatar uploads.
 *
 * @since 1.7.6
 *
 * @return array<string, string> Extension regex => MIME type, as used by wp_handle_upload().
 */
function init_plugin_suite_user_engine_avatar_mimes() {
	$mimes = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'gif'          => 'image/gif',
		'webp'         => 'image/webp',
	);

	/**
	 * Filters the file types accepted for avatar uploads.
	 *
	 * Only image types the image editor can open make sense here. SVG is never accepted.
	 *
	 * @since 1.7.6
	 *
	 * @param array<string, string> $mimes Extension regex => MIME type.
	 */
	$mimes = (array) apply_filters( 'init_plugin_suite_user_engine_avatar_mimes', $mimes );

	foreach ( $mimes as $ext => $mime ) {
		if ( 0 !== strpos( (string) $mime, 'image/' ) || false !== stripos( (string) $mime, 'svg' ) ) {
			unset( $mimes[ $ext ] );
		}
	}

	return $mimes;
}

/**
 * Rate limit of avatar uploads (default: 10 uploads per hour).
 *
 * @since 1.7.6
 *
 * @param int $user_id User ID.
 * @return true|WP_Error
 */
function init_plugin_suite_user_engine_avatar_rate_limit( $user_id ) {
	/**
	 * Filters how many avatars a member may upload per hour.
	 *
	 * Return 0 to disable the limit.
	 *
	 * @since 1.7.6
	 *
	 * @param int $max     Uploads per hour. Default 10.
	 * @param int $user_id User ID.
	 */
	$max = absint( apply_filters( 'init_plugin_suite_user_engine_avatar_upload_rate_limit', 10, $user_id ) );

	if ( init_plugin_suite_user_engine_hit_rate_limit( 'iue_avatar_rl_' . absint( $user_id ), $max, HOUR_IN_SECONDS ) ) {
		return true;
	}

	return new WP_Error(
		'rate_limited',
		__( 'You have uploaded too many avatars. Please try again later.', 'init-user-engine' ),
		array( 'status' => 429 )
	);
}

/**
 * File name prefix ("avatar-{user}-{time}") of an avatar URL saved by this plugin.
 *
 * @since 1.7.6
 *
 * @param int    $user_id    User ID.
 * @param string $avatar_url Avatar URL (any of its sizes).
 * @return string Empty string when the URL is not one of this user's avatars.
 */
function init_plugin_suite_user_engine_avatar_prefix( $user_id, $avatar_url ) {
	$upload_dir = wp_upload_dir();
	$base_url   = trailingslashit( $upload_dir['baseurl'] ) . 'init-user-engine/avatars/';
	$avatar_url = (string) $avatar_url;

	// Compare without the scheme, so http/https differences do not matter.
	$strip = static function ( $url ) {
		return preg_replace( '#^https?:#i', '', $url );
	};

	if ( 0 !== strpos( $strip( $avatar_url ), $strip( $base_url ) ) ) {
		return '';
	}

	$relative = substr( $strip( $avatar_url ), strlen( $strip( $base_url ) ) );

	// The file must sit directly in the avatars folder (no sub-folder, no "..").
	if ( ! preg_match( '/^(avatar-(\d+)-\d+)-(?:50|80|orig)(?:-\d+)?\.(?:jpe?g|png|gif|webp)$/i', $relative, $m ) ) {
		return '';
	}

	return ( (int) $m[2] === (int) $user_id ) ? $m[1] : '';
}

/**
 * Delete every file (50px, 80px and original) of one avatar of a user.
 *
 * Only files of this plugin's avatars folder whose name belongs to the user are deleted.
 *
 * @since 1.7.6
 *
 * @param int    $user_id    User ID.
 * @param string $avatar_url Avatar URL (any of its sizes).
 * @return int Number of files deleted.
 */
function init_plugin_suite_user_engine_delete_avatar_files( $user_id, $avatar_url ) {
	$prefix = init_plugin_suite_user_engine_avatar_prefix( $user_id, $avatar_url );

	if ( '' === $prefix ) {
		return 0;
	}

	$upload_dir = wp_upload_dir();
	$dir        = trailingslashit( $upload_dir['basedir'] ) . 'init-user-engine/avatars/';
	$files      = glob( $dir . $prefix . '-*' );
	$deleted    = 0;

	if ( ! is_array( $files ) ) {
		return 0;
	}

	$pattern = '/^' . preg_quote( $prefix, '/' ) . '-(?:50|80|orig)(?:-\d+)?\.(?:jpe?g|png|gif|webp)$/i';

	foreach ( $files as $file ) {
		if ( is_file( $file ) && preg_match( $pattern, basename( $file ) ) ) {
			wp_delete_file( $file );
			++$deleted;
		}
	}

	return $deleted;
}
