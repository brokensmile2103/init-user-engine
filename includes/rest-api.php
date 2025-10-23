<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Register REST API routes for check-in, reward, and transaction log
add_action( 'rest_api_init', 'init_plugin_suite_user_engine_register_rest_routes' );

function init_plugin_suite_user_engine_register_rest_routes() {
    $namespace = INIT_PLUGIN_SUITE_IUE_NAMESPACE;

    // POST /register – Đăng ký người dùng mới
    register_rest_route( $namespace, '/register', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_register_user',
        'permission_callback' => '__return_true',
    ]);

    // GET /captcha – Tạo captcha đơn giản
    register_rest_route( $namespace, '/captcha', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_user_engine_api_get_captcha',
        'permission_callback' => '__return_true',
    ]);

    // POST /checkin – Điểm danh hằng ngày
    register_rest_route( $namespace, '/checkin', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_checkin',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ] );

    // POST /claim-reward – Nhận thưởng sau khi online 10 phút
    register_rest_route( $namespace, '/claim-reward', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_claim_reward',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ] );

    // GET /transactions – Lấy lịch sử giao dịch
    register_rest_route( $namespace, '/transactions', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_user_engine_api_get_transactions',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    // GET /daily-tasks – Lấy nhiệm vụ hàng ngày
    register_rest_route( $namespace, '/daily-tasks', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_user_engine_api_get_daily_tasks',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    // GET /exp-log – Lấy log EXP riêng
    register_rest_route( $namespace, '/exp-log', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_user_engine_api_get_exp_log',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ] );

    // GET /inbox – Lấy danh sách inbox
    register_rest_route( $namespace, '/inbox', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_user_engine_api_get_inbox',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ] );

    // POST /inbox/mark-read
    register_rest_route( $namespace, '/inbox/mark-read', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_mark_inbox_read',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    // POST /inbox/mark-all-read
    register_rest_route( $namespace, '/inbox/mark-all-read', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_mark_inbox_all_read',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    // POST /inbox/delete – Xoá 1 tin nhắn
    register_rest_route( $namespace, '/inbox/delete', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_delete_inbox_item',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ] );

    // POST /inbox/delete-all – Xoá toàn bộ hộp thư
    register_rest_route( $namespace, '/inbox/delete-all', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_delete_inbox_all',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ] );

    // POST /vip/purchase – Mua gói VIP
    register_rest_route( $namespace, '/vip/purchase', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_purchase_vip',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ] );

    // GET /referral-log – Lấy lịch sử giới thiệu
    register_rest_route( $namespace, '/referral-log', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_user_engine_api_get_referral_log',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ] );

    // POST /avatar – Upload avatar mới
    register_rest_route( $namespace, '/avatar', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_upload_avatar',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    // POST /avatar/remove – Xóa custom avatar để dùng mặc định
    register_rest_route( $namespace, '/avatar/remove', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_remove_avatar',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    // POST /profile/update – Cập nhật thông tin hồ sơ người dùng
    register_rest_route( $namespace, '/profile/update', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_update_profile',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    // GET /profile/me – Lấy thông tin hồ sơ người dùng hiện tại
    register_rest_route( $namespace, '/profile/me', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_user_engine_api_get_profile',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    // POST /exchange – Convert Cash to Coin
    register_rest_route( $namespace, '/exchange', [
        'methods'             => 'POST',
        'callback'            => 'init_plugin_suite_user_engine_api_exchange_cash_to_coin',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ] );
}

// Enhanced Captcha với better validation
function init_plugin_suite_user_engine_api_get_captcha() {
    $session_id = wp_generate_password(16, false, false);
    $timestamp  = time();

    // 0: ký hiệu + - × ; 1: chữ plus/minus/times ; 2: ngân hàng kiến thức số cơ bản ; 3: biến thể ngữ cảnh (mẹo)
    $mode = wp_rand(0, 3);

    // ===== MODE 0: Biểu thức ký hiệu =====
    if ( $mode === 0 ) {
        $ops = ['+', '-', '×'];
        $op  = $ops[array_rand($ops)];
        $a   = wp_rand(2, 19);
        $b   = wp_rand(1, 9);

        if ( $op === '-' && $b > $a ) {
            list($a, $b) = [$b, $a];
        }

        if ( $op === '+' )       { $answer = $a + $b; }
        elseif ( $op === '-' )  { $answer = $a - $b; }
        else                    { $answer = $a * $b; }

        // translators: %1$d and %3$d are numbers; %2$s is the operator (+, −, ×).
        $question = sprintf( __('%1$d %2$s %3$d = ?', 'init-user-engine'), $a, $op, $b );

    // ===== MODE 1: Biểu thức chữ =====
    } elseif ( $mode === 1 ) {
        $ops = ['plus', 'minus', 'times'];
        $op  = $ops[array_rand($ops)];
        $a   = wp_rand(2, 19);
        $b   = wp_rand(1, 9);

        if ( $op === 'minus' && $b > $a ) {
            list($a, $b) = [$b, $a];
        }

        if ( $op === 'plus' ) {
            $answer  = $a + $b;
            // translators: %1$d and %2$d are numbers.
            $question = sprintf( __('What is %1$d plus %2$d?', 'init-user-engine'), $a, $b );
        } elseif ( $op === 'minus' ) {
            $answer  = $a - $b;
            // translators: %1$d and %2$d are numbers.
            $question = sprintf( __('What is %1$d minus %2$d?', 'init-user-engine'), $a, $b );
        } else {
            $answer  = $a * $b;
            // translators: %1$d and %2$d are numbers.
            $question = sprintf( __('What is %1$d times %2$d?', 'init-user-engine'), $a, $b );
        }

    // ===== MODE 2: Ngân hàng kiến thức số cơ bản (mở rộng) =====
    } elseif ( $mode === 2 ) {
        $questions = [
            ['question' => __('How many days in a week?', 'init-user-engine'),                             'answer' => 7],
            ['question' => __('How many months in a year?', 'init-user-engine'),                           'answer' => 12],
            ['question' => __('How many minutes in an hour?', 'init-user-engine'),                         'answer' => 60],
            ['question' => __('How many hours in a day?', 'init-user-engine'),                             'answer' => 24],
            ['question' => __('How many days in February (non-leap year)?', 'init-user-engine'),           'answer' => 28],
            ['question' => __('How many sides does a square have?', 'init-user-engine'),                   'answer' => 4],
            ['question' => __('How many sides does a triangle have?', 'init-user-engine'),                 'answer' => 3],
            ['question' => __('How many legs does a spider have?', 'init-user-engine'),                    'answer' => 8],
            ['question' => __('How many letters are in the English alphabet?', 'init-user-engine'),        'answer' => 26],
            ['question' => __('What is 10 divided by 2?', 'init-user-engine'),                             'answer' => 5],
            ['question' => __('What is 100 divided by 25?', 'init-user-engine'),                           'answer' => 4],
            ['question' => __('What is 2 cubed?', 'init-user-engine'),                                     'answer' => 8],
            ['question' => __('What is 3 squared?', 'init-user-engine'),                                   'answer' => 9],
            ['question' => __('What is 5 times 6?', 'init-user-engine'),                                   'answer' => 30],
            ['question' => __('What is 9 minus 4?', 'init-user-engine'),                                   'answer' => 5],
            ['question' => __('What is 7 plus 8?', 'init-user-engine'),                                    'answer' => 15],
            ['question' => __('How many quarters make a whole (1.00)?', 'init-user-engine'),               'answer' => 4],
            ['question' => __('How many seasons are in a year?', 'init-user-engine'),                      'answer' => 4],
            ['question' => __('How many wheels does a bicycle have?', 'init-user-engine'),                 'answer' => 2],
            ['question' => __('How many fingers do humans normally have in total?', 'init-user-engine'),   'answer' => 10],
            ['question' => __('How many days are in a fortnight?', 'init-user-engine'),                    'answer' => 14],
            ['question' => __('How many decades are in a century?', 'init-user-engine'),                   'answer' => 10],
            ['question' => __('How many zeros are in one thousand?', 'init-user-engine'),                  'answer' => 3],
            ['question' => __('How many centimeters are in a meter?', 'init-user-engine'),                 'answer' => 100],
            ['question' => __('How many millimeters are in a centimeter?', 'init-user-engine'),            'answer' => 10],
            ['question' => __('How many days are in June?', 'init-user-engine'),                           'answer' => 30],
            ['question' => __('How many days are in July?', 'init-user-engine'),                           'answer' => 31],
            ['question' => __('How many even numbers are there between 1 and 5?', 'init-user-engine'),     'answer' => 2],  // 2,4
            ['question' => __('How many vowels are in the word "code"?', 'init-user-engine'),              'answer' => 2],  // o,e
            ['question' => __('How many corners does a rectangle have?', 'init-user-engine'),              'answer' => 4],
            ['question' => __('How many feet are in a yard?', 'init-user-engine'),                         'answer' => 3],
            ['question' => __('How many halves are in a whole?', 'init-user-engine'),                      'answer' => 2],
            ['question' => __('How many thirds are in a whole?', 'init-user-engine'),                      'answer' => 3],
            ['question' => __('How many letters are in the word "seven"?', 'init-user-engine'),            'answer' => 5],
            ['question' => __('How many minutes are in 2 hours?', 'init-user-engine'),                     'answer' => 120],
            ['question' => __('How many days are in 3 weeks?', 'init-user-engine'),                        'answer' => 21],
            ['question' => __('How many months are in 2 years?', 'init-user-engine'),                      'answer' => 24],
            ['question' => __('How many quarters are in a year?', 'init-user-engine'),                     'answer' => 4],
            ['question' => __('How many digits are there in a PIN like "1234"?', 'init-user-engine'),      'answer' => 4],
            ['question' => __('How many letters are in the word "ten"?', 'init-user-engine'),              'answer' => 3],
        ];

        /**
         * Cho phép mở rộng ngân hàng câu hỏi qua hook.
         * Dev có thể add thêm phần tử ['question' => '...', 'answer' => (int)].
         */
        $questions = apply_filters( 'init_user_engine_captcha_bank', $questions );

        $selected = $questions[array_rand($questions)];
        $question = $selected['question'];
        $answer   = (int) $selected['answer'];

    // ===== MODE 3: Biến thể ngữ cảnh (tránh hardcode/phishing mẫu) =====
    } else {
        // Tạo một vài mẫu hỏi đổi câu chữ nhưng vẫn trả lời số
        $templates = [
            // translators: %d is a number.
            function() { $x = wp_rand(2,9); return [ sprintf( __('Give the next even number after %d.', 'init-user-engine'), $x ), ($x % 2 === 0) ? $x+2 : $x+1 ]; },

            // (no placeholders) kept as sprintf for consistency; translators note not required.
            function() { $x = wp_rand(3,9); return [ sprintf( __('How many letters are there in the word "cat"? (hint: c-a-t)', 'init-user-engine') ), 3 ]; },

            // translators: %1$d and %2$d are numbers (counts of apples).
            function() { $a = wp_rand(2,7); $b = wp_rand(2,7); return [ sprintf( __('Add %1$d apples and %2$d apples.', 'init-user-engine'), $a, $b ), $a+$b ]; },

            // (no placeholders) kept as sprintf for consistency; translators note not required.
            function() { $x = wp_rand(2,5); return [ sprintf( __('How many sides does a regular pentagon have?', 'init-user-engine') ), 5 ]; },

            // translators: %s is a repeated numeric string like "1111".
            function() { $x = wp_rand(2,6); return [ sprintf( __('Count the digits in "%s".', 'init-user-engine'), str_repeat('1', $x) ), $x ]; },

            // translators: %d is a number to be doubled.
            function() { $a = wp_rand(2,9); return [ sprintf( __('Double %d is?', 'init-user-engine'), $a ), 2*$a ]; },

            // translators: %d is the resulting number after halving.
            function() { $a = wp_rand(2,9); return [ sprintf( __('Half of %d is?', 'init-user-engine'), 2*$a ), $a ]; },
        ];

        $maker    = $templates[array_rand($templates)];
        list($question, $answer) = $maker();
        $answer = (int) $answer;
    }

    // ===== Token hóa + chống tái sử dụng =====
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
    $ip         = function_exists('init_plugin_suite_user_engine_get_real_ip') ? init_plugin_suite_user_engine_get_real_ip() : '';

    // Gắn UA+IP hash nhẹ để giảm lạm dụng token cross-client
    $key_data    = $session_id . '|' . $timestamp . '|' . substr( md5( $user_agent . $ip ), 0, 8 );
    $captcha_key = 'iue_captcha_' . hash('sha256', $key_data);

    $captcha_data = [
        'answer'    => (int) $answer,
        'session'   => $session_id,
        'timestamp' => $timestamp,
        'ip'        => $ip,
        'attempts'  => 0,
    ];

    set_transient( $captcha_key, $captcha_data, 15 * MINUTE_IN_SECONDS );

    return [
        'question' => $question,
        'token'    => base64_encode( $key_data ),
        'expires'  => $timestamp + ( 15 * MINUTE_IN_SECONDS ),
    ];
}

// Enhanced register function
function init_plugin_suite_user_engine_api_register_user( WP_REST_Request $request ) {
    // ---- CHECK GLOBAL REGISTRATION SETTING ----
    $settings = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
    
    if ( ! empty( $settings['disable_registration'] ) ) {
        return new WP_Error(
            'registration_disabled',
            __( 'New registrations are currently disabled.', 'init-user-engine' ),
            [ 'status' => 403 ]
        );
    }

    $data     = $request->get_json_params();
    $username = sanitize_user( $data['username'] ?? '' );
    $email    = sanitize_email( $data['email'] ?? '' );
    $password = $data['password'] ?? '';

    $honeypot = sanitize_text_field( $data['iue_hp'] ?? '' );
    if ( ! empty( $honeypot ) ) {
        return new WP_Error( 'bot_detected', __( 'Bot submission detected.', 'init-user-engine' ), [ 'status' => 403 ] );
    }

    // Check setting disable_captcha
    $disable_captcha = ! empty( $settings['disable_captcha'] );

    // ---- CAPTCHA / TURNSTILE ----
    if ( ! $disable_captcha ) {
        $has_turnstile = ! empty( $settings['turnstile_site_key'] ) && ! empty( $settings['turnstile_secret_key'] );

        if ( $has_turnstile ) {
            // Ưu tiên JSON payload
            $turnstile_token = sanitize_text_field( $data['turnstile_token'] ?? '' );
            if ( $turnstile_token === '' ) {
                $turnstile_token = sanitize_text_field( $data['cf_turnstile_response'] ?? '' );
            }

            // Hỗ trợ form submit (application/x-www-form-urlencoded hoặc multipart) qua REST
            if ( $turnstile_token === '' ) {
                $maybe_token = $request->get_param( 'cf-turnstile-response' );
                if ( is_string( $maybe_token ) ) {
                    $turnstile_token = sanitize_text_field( wp_unslash( $maybe_token ) );
                }
            }

            $ip         = function_exists( 'init_plugin_suite_user_engine_get_real_ip' ) ? init_plugin_suite_user_engine_get_real_ip() : '';
            $verify_res = init_plugin_suite_user_engine_verify_turnstile( $turnstile_token, $ip );
            if ( is_wp_error( $verify_res ) ) {
                return $verify_res;
            }
        } else {
            // Fallback: captcha cũ
            $captcha_token  = sanitize_text_field( $data['captcha_token'] ?? '' );
            $captcha_answer = intval( $data['captcha_answer'] ?? 0 );

            $captcha_result = init_plugin_suite_user_engine_validate_captcha( $captcha_token, $captcha_answer );
            if ( is_wp_error( $captcha_result ) ) {
             return $captcha_result;
            }
        }
    }

    // ---- Rate limiting per IP ----
    $ip       = function_exists( 'init_plugin_suite_user_engine_get_real_ip' ) ? init_plugin_suite_user_engine_get_real_ip() : '';
    $rate_key = 'iue_register_rate_' . hash( 'sha256', $ip ?: 'unknown' );
    $attempts = get_transient( $rate_key ) ?: 0;

    if ( $attempts >= 5 ) {
        return new WP_Error( 'rate_limit', __( 'Too many registration attempts. Please try again later.', 'init-user-engine' ), [ 'status' => 429 ] );
    }

    // ---- Validate dữ liệu ----
    if ( strlen( $username ) < 3 ) {
        return new WP_Error( 'invalid_username', __( 'Username must be at least 3 characters.', 'init-user-engine' ), [ 'status' => 400 ] );
    }

    if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $username ) ) {
        return new WP_Error( 'invalid_username_chars', __( 'Username can only contain letters, numbers and underscores.', 'init-user-engine' ), [ 'status' => 400 ] );
    }

    if ( ! is_email( $email ) ) {
        return new WP_Error( 'invalid_email', __( 'Please enter a valid email address.', 'init-user-engine' ), [ 'status' => 400 ] );
    }

    if ( username_exists( $username ) ) {
        return new WP_Error( 'username_taken', __( 'Username already exists.', 'init-user-engine' ), [ 'status' => 400 ] );
    }

    if ( email_exists( $email ) ) {
        return new WP_Error( 'email_taken', __( 'Email already registered.', 'init-user-engine' ), [ 'status' => 400 ] );
    }

    if ( strlen( $password ) < 6 ) {
        return new WP_Error( 'weak_password', __( 'Password must be at least 6 characters.', 'init-user-engine' ), [ 'status' => 400 ] );
    }

    if ( ! preg_match( '/^(?=.*[a-zA-Z])(?=.*\d)/', $password ) ) {
        return new WP_Error( 'weak_password', __( 'Password must contain both letters and numbers.', 'init-user-engine' ), [ 'status' => 400 ] );
    }

    $errors = apply_filters( 'init_plugin_suite_user_engine_validate_register_fields', [], [
        'username' => $username,
        'email'    => $email,
        'password' => $password,
    ] );
    if ( ! empty( $errors ) && is_array( $errors ) ) {
        return new WP_REST_Response( [
            'status' => 'validation_failed',
            'errors' => array_values( $errors ),
        ], 400 );
    }

    set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );

    $user_id = wp_create_user( $username, $password, $email );

    if ( is_wp_error( $user_id ) ) {
        return new WP_Error( 'registration_failed', $user_id->get_error_message(), [ 'status' => 500 ] );
    }

    delete_transient( $rate_key );

    do_action( 'init_plugin_suite_user_engine_after_register', $user_id );

    return new WP_REST_Response( [
        'status'  => 'registered',
        'message' => __( 'Registration successful. You can now log in.', 'init-user-engine' ),
    ], 200 );
}

// Enhanced captcha validation
function init_plugin_suite_user_engine_validate_captcha($token, $user_answer) {
    if (empty($token) || $user_answer === 0) {
        return new WP_Error('captcha_required', __('Please complete the captcha.', 'init-user-engine'), ['status' => 400]);
    }

    // Decode token
    $key_data = base64_decode($token);
    if (!$key_data) {
        return new WP_Error('captcha_invalid', __('Invalid captcha token.', 'init-user-engine'), ['status' => 400]);
    }

    $captcha_key = 'iue_captcha_' . hash('sha256', $key_data);
    $captcha_data = get_transient($captcha_key);

    if (false === $captcha_data || !is_array($captcha_data)) {
        return new WP_Error('captcha_expired', __('Captcha has expired. Please try again.', 'init-user-engine'), ['status' => 400]);
    }

    // Check attempts (prevent brute force)
    if ($captcha_data['attempts'] >= 3) {
        delete_transient($captcha_key);
        return new WP_Error('captcha_attempts', __('Too many captcha attempts. Please get a new one.', 'init-user-engine'), ['status' => 400]);
    }

    // Validate answer
    if (intval($captcha_data['answer']) !== intval($user_answer)) {
        // Increment attempts
        $captcha_data['attempts']++;
        set_transient($captcha_key, $captcha_data, 15 * MINUTE_IN_SECONDS);
        
        return new WP_Error('captcha_wrong', __('Incorrect captcha answer. Please try again.', 'init-user-engine'), ['status' => 400]);
    }

    // Validate IP (optional extra security)
    $current_ip = init_plugin_suite_user_engine_get_real_ip();
    if ($captcha_data['ip'] !== $current_ip) {
        delete_transient($captcha_key);
        return new WP_Error('captcha_ip_mismatch', __('Captcha validation failed. Please try again.', 'init-user-engine'), ['status' => 400]);
    }

    // Success - cleanup
    delete_transient($captcha_key);
    return true;
}

// Handle daily check-in, reward EXP/coin, update streak and milestones
function init_plugin_suite_user_engine_api_checkin( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
    }

    $today   = init_plugin_suite_user_engine_today(); // dùng current_time() bên trong
    $last    = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_last', '' );
    $streak  = (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_streak', 0 );
    $total   = (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_total', 0 );

    if ( $last === $today ) {
        return new WP_REST_Response( [ 'status' => 'already_checked_in' ], 200 );
    }

    // Tính hôm qua dựa trên timezone site (tránh lệch UTC)
    $yesterday = gmdate( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) );
    $streak    = ( $last === $yesterday ) ? $streak + 1 : 1;
    $total    += 1;

    init_plugin_suite_user_engine_update_meta( $user_id, 'iue_checkin_last', $today );
    init_plugin_suite_user_engine_update_meta( $user_id, 'iue_checkin_streak', $streak );
    init_plugin_suite_user_engine_update_meta( $user_id, 'iue_checkin_total', $total );

    // Lấy settings (vẫn hợp lệ nếu giá trị = 0)
    $settings     = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
    $exp_added    = isset( $settings['checkin_exp'] )     ? absint( $settings['checkin_exp'] )     : 50;
    $coin_added   = isset( $settings['checkin_coin'] )    ? absint( $settings['checkin_coin'] )    : 10;
    $cash_added   = isset( $settings['checkin_cash'] )    ? absint( $settings['checkin_cash'] )    : 0;
    
    $is_vip    = init_plugin_suite_user_engine_is_vip();
    $minutes   = isset( $settings['online_minutes'] ) ? absint( $settings['online_minutes'] ) : 10;

    if ( $is_vip ) {
        $minutes = max( 1, ceil( $minutes / 2 ) );
    }

    $minutes = apply_filters( 'init_plugin_suite_user_engine_online_minutes', $minutes, $user_id, $is_vip );
    $online_wait = $minutes * 60;

    // Khởi tạo đầu ra
    $new_exp_data = [
        'current_exp'      => (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_exp', 0 ),
        'current_level'    => (int) init_plugin_suite_user_engine_get_level( $user_id ),
        'level_up_count'   => 0,
        'total_bonus_coin' => 0,
        'exp_added'        => 0,
    ];
    $new_coin = init_plugin_suite_user_engine_get_coin( $user_id );
    $new_cash = init_plugin_suite_user_engine_get_cash( $user_id );

    // Cộng EXP
    if ( $exp_added > 0 ) {
        $new_exp_data = init_plugin_suite_user_engine_add_exp( $user_id, $exp_added );
        init_plugin_suite_user_engine_log_exp( $user_id, $exp_added, 'checkin', 'add' );
    }

    // Cộng Coin
    if ( $coin_added > 0 ) {
        $new_coin = init_plugin_suite_user_engine_add_coin( $user_id, $coin_added );
        init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $coin_added, 'checkin', 'add' );
    }

    // Cộng Cash
    if ( $cash_added > 0 ) {
        $new_cash = init_plugin_suite_user_engine_add_cash( $user_id, $cash_added );
        init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', $cash_added, 'checkin', 'add' );
    }

    // Bonus theo mốc streak
    $milestones = apply_filters( 'init_plugin_suite_user_engine_checkin_milestones', [ 7, 30, 90, 180, 365 ] );
    foreach ( $milestones as $m ) {
        if ( $streak % $m === 0 ) {
            $bonus_coin = $m * 2;
            $bonus_exp  = $m;

            $new_coin     = init_plugin_suite_user_engine_add_coin( $user_id, $bonus_coin );
            $new_exp_data = init_plugin_suite_user_engine_add_exp( $user_id, $bonus_exp );

            init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $bonus_coin, "milestone_$m", 'add' );
            init_plugin_suite_user_engine_log_exp( $user_id, $bonus_exp, "milestone_$m", 'add' );

            // translators: %d is streak days
            $title   = sprintf( __( 'You reached a %d-day check-in streak!', 'init-user-engine' ), $m );
            $content = __( 'Your consistency is impressive! We just sent you a bonus reward.', 'init-user-engine' );

            init_plugin_suite_user_engine_send_inbox(
                $user_id,
                $title,
                $content,
                'checkin'
            );
        }
    }

    // Setup thời gian chờ nhận thưởng (dùng current_time)
    init_plugin_suite_user_engine_update_meta( $user_id, 'iue_claim_after_timestamp', current_time( 'timestamp' ) + $online_wait );
    init_plugin_suite_user_engine_update_meta( $user_id, 'iue_checkin_rewarded', false );

    do_action( 'init_plugin_suite_user_engine_after_checkin', $user_id, [
        'streak' => $streak,
        'exp'    => $new_exp_data['exp_added'],
        'coin'   => $coin_added,
        'cash'   => $cash_added,
    ] );

    return new WP_REST_Response( [
        'status'            => 'success',
        'streak'            => $streak,
        'exp'               => $new_exp_data['current_exp'],
        'coin'              => $new_coin,
        'cash'              => $new_cash,
        'level'             => $new_exp_data['current_level'],
        'level_up_count'    => $new_exp_data['level_up_count'],
        'total_bonus_coin'  => $new_exp_data['total_bonus_coin'],
        'exp_added'         => $new_exp_data['exp_added'],
    ], 200 );
}

// Handle bonus claim after being online for X minutes
function init_plugin_suite_user_engine_api_claim_reward( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
    }

    $now          = current_time( 'timestamp' );
    $today        = init_plugin_suite_user_engine_today();
    $last_checkin = init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_last', '' );
    $rewarded     = (bool) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_checkin_rewarded', false );
    $claim_after  = (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_claim_after_timestamp', 0 );

    if ( $last_checkin !== $today ) {
        return new WP_REST_Response( [ 'status' => 'not_checked_in' ], 400 );
    }

    if ( $rewarded ) {
        return new WP_REST_Response( [ 'status' => 'already_rewarded' ], 200 );
    }

    if ( $now < $claim_after ) {
        return new WP_REST_Response( [
            'status' => 'too_early',
            'wait'   => $claim_after - $now,
        ], 403 );
    }

    // Đánh dấu đã nhận thưởng
    init_plugin_suite_user_engine_update_meta( $user_id, 'iue_checkin_rewarded', true );

    // Lấy từ cài đặt
    $settings   = get_option( INIT_PLUGIN_SUITE_IUE_OPTION, [] );
    $exp_added  = isset( $settings['online_exp'] )  ? absint( $settings['online_exp'] )  : 50;
    $coin_added = isset( $settings['online_coin'] ) ? absint( $settings['online_coin'] ) : 100;
    $cash_added = isset( $settings['online_cash'] ) ? absint( $settings['online_cash'] ) : 0;

    // Khởi tạo dữ liệu đầu ra
    $new_exp_data = [
        'current_exp'      => (int) init_plugin_suite_user_engine_get_meta( $user_id, 'iue_exp', 0 ),
        'current_level'    => (int) init_plugin_suite_user_engine_get_level( $user_id ),
        'level_up_count'   => 0,
        'total_bonus_coin' => 0,
        'exp_added'        => 0,
    ];
    $new_coin = init_plugin_suite_user_engine_get_coin( $user_id );
    $new_cash = init_plugin_suite_user_engine_get_cash( $user_id );

    // Cộng EXP
    if ( $exp_added > 0 ) {
        $new_exp_data = init_plugin_suite_user_engine_add_exp( $user_id, $exp_added );
        init_plugin_suite_user_engine_log_exp( $user_id, $exp_added, 'reward', 'add' );
    }

    // Cộng Coin
    if ( $coin_added > 0 ) {
        $new_coin = init_plugin_suite_user_engine_add_coin( $user_id, $coin_added );
        init_plugin_suite_user_engine_log_transaction( $user_id, 'coin', $coin_added, 'reward', 'add' );
    }

    // Cộng Cash
    if ( $cash_added > 0 ) {
        $new_cash = init_plugin_suite_user_engine_add_cash( $user_id, $cash_added );
        init_plugin_suite_user_engine_log_transaction( $user_id, 'cash', $cash_added, 'reward', 'add' );
    }

    do_action( 'init_plugin_suite_user_engine_after_claim_reward', $user_id, [
        'exp'  => $exp_added,
        'coin' => $coin_added,
        'cash' => $cash_added,
    ] );

    return new WP_REST_Response( [
        'status'            => 'reward_claimed',
        'exp'               => $new_exp_data['current_exp'],
        'coin'              => $new_coin,
        'cash'              => $new_cash,
        'level'             => $new_exp_data['current_level'],
        'level_up_count'    => $new_exp_data['level_up_count'],
        'total_bonus_coin'  => $new_exp_data['total_bonus_coin'],
        'exp_added'         => $new_exp_data['exp_added'],
    ], 200 );
}

// Upload ảnh avatar
function init_plugin_suite_user_engine_api_upload_avatar( WP_REST_Request $request ) {
    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'unauthorized', 'Not logged in.', [ 'status' => 403 ] );
    }

    // Guard: policy + VIP + per-user ban
    if ( ! init_plugin_suite_user_engine_can_upload_avatar( get_current_user_id() ) ) {
        return new WP_Error(
            'forbidden_avatar_upload',
            __( 'You are not allowed to upload an avatar.', 'init-user-engine' ),
            [ 'status' => 403 ]
        );
    }

    $file = $request->get_file_params()['avatar'] ?? null;
    if ( ! $file || ! $file['tmp_name'] ) {
        return new WP_Error( 'no_file', 'No file uploaded.', [ 'status' => 400 ] );
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $upload_dir = wp_upload_dir();
    $avatar_subdir = '/init-user-engine/avatars/';
    $avatar_dir = $upload_dir['basedir'] . $avatar_subdir;

    if ( ! file_exists( $avatar_dir ) ) {
        wp_mkdir_p( $avatar_dir );
    }

    $move = wp_handle_upload( $file, [ 'test_form' => false, 'upload_error_handler' => null ] );
    if ( isset( $move['error'] ) ) {
        return new WP_Error( 'upload_failed', $move['error'], [ 'status' => 500 ] );
    }

    $tmp_file = $move['file'];

    $editor = wp_get_image_editor( $tmp_file );
    if ( is_wp_error( $editor ) ) {
        wp_delete_file( $tmp_file );
        return $editor;
    }

    $size = $editor->get_size();
    $short = min( $size['width'], $size['height'] );
    $x = floor( ( $size['width']  - $short ) / 2 );
    $y = floor( ( $size['height'] - $short ) / 2 );

    $editor->crop( $x, $y, $short, $short );

    $user_id = get_current_user_id();
    $timestamp = time();
    $basename = "avatar-{$user_id}-{$timestamp}";

    // Resize & save 50x50
    $editor->resize( 50, 50, true );
    $saved_50 = $editor->save( "{$avatar_dir}{$basename}-50.jpg" );

    // Reload editor & resize 80x80
    $editor = wp_get_image_editor( $tmp_file );
    if ( ! is_wp_error( $editor ) ) {
        $editor->crop( $x, $y, $short, $short );
        $editor->resize( 80, 80, true );
        $saved_80 = $editor->save( "{$avatar_dir}{$basename}-80.jpg" );
    }

    wp_delete_file( $tmp_file );

    if ( is_wp_error( $saved_50 ) ) {
        return $saved_50;
    }

    $relative_url = str_replace( $upload_dir['basedir'], '', $saved_50['path'] );
    $url_50 = $upload_dir['baseurl'] . $relative_url;

    update_user_meta( $user_id, 'iue_custom_avatar', esc_url_raw( $url_50 ) );

    return [
        'url_50' => esc_url_raw( $url_50 ),
        'url_80' => isset( $saved_80['path'] )
            ? esc_url_raw( $upload_dir['baseurl'] . str_replace( $upload_dir['basedir'], '', $saved_80['path'] ) )
            : '',
    ];
}

// Xóa ảnh avatar về dùng mặc định
function init_plugin_suite_user_engine_api_remove_avatar( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
    }

    // Guard: preserve evidence when user is banned from avatar changes
    $ban_val = get_user_meta( $user_id, 'iue_avatar_ban', true );
    if ( $ban_val !== '' && $ban_val !== null && $ban_val !== '0' && $ban_val !== 0 ) {
        return new WP_Error(
            'avatar_locked',
            __( 'Avatar removal is locked for review.', 'init-user-engine' ),
            [ 'status' => 423 ] // 423 Locked
        );
    }

    /**
     * Allow project-level override (e.g. moderators).
     * Return false to block removal even if not banned.
     */
    $can_remove = (bool) apply_filters( 'init_user_engine_can_remove_avatar', true, $user_id, $ban_val );
    if ( ! $can_remove ) {
        return new WP_Error(
            'forbidden_remove_avatar',
            __( 'You are not allowed to remove the avatar.', 'init-user-engine' ),
            [ 'status' => 403 ]
        );
    }

    $avatar_url = get_user_meta( $user_id, 'iue_custom_avatar', true );

    if ( $avatar_url ) {
        $upload_dir = wp_upload_dir();
        $base_url   = $upload_dir['baseurl'];
        $base_dir   = $upload_dir['basedir'];

        if ( strpos( $avatar_url, $base_url ) === 0 ) {
            $relative_path = ltrim( str_replace( $base_url, '', $avatar_url ), '/' );
            $avatar_path = $base_dir . '/' . $relative_path;

            $filename = basename( $avatar_path );
            $basename = preg_replace( '/-\d+\.(jpg|jpeg|png|webp)$/i', '', $filename );

            $avatar_dir = dirname( $avatar_path );
            $sizes = [ 50, 80 ];

            foreach ( $sizes as $size ) {
                $path = "{$avatar_dir}/{$basename}-{$size}.jpg";
                if ( file_exists( $path ) ) {
                    wp_delete_file( $path );
                }
            }
        }

        delete_user_meta( $user_id, 'iue_custom_avatar' );
    }

    $default_avatar_url = get_avatar_url( $user_id, [ 'size' => 50 ] );

    return rest_ensure_response([
        'success' => true,
        'url'     => esc_url( $default_avatar_url ),
    ]);
}

// Lấy dữ liệu người dùng
function init_plugin_suite_user_engine_api_get_profile( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
    }

    $user = get_userdata( $user_id );

    $profile = [
        'display_name' => $user->display_name,
        'bio'          => get_user_meta( $user_id, 'description', true ),
        'facebook'     => get_user_meta( $user_id, 'iue_facebook', true ),
        'twitter'      => get_user_meta( $user_id, 'iue_twitter', true ),
        'discord'      => get_user_meta( $user_id, 'iue_discord', true ),
        'website'      => get_user_meta( $user_id, 'iue_website', true ),
        'gender'       => get_user_meta( $user_id, 'iue_gender', true ),
    ];

    return rest_ensure_response( $profile );
}

// Cập nhật thông tin
function init_plugin_suite_user_engine_api_update_profile( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
    }

    $data = $request->get_json_params();

    $honeypot = sanitize_text_field( $data['iue_hp'] ?? '' );
    if ( ! empty( $honeypot ) ) {
        return new WP_Error( 'spam_detected', __( 'Spam detected.', 'init-user-engine' ), [ 'status' => 400 ] );
    }

    // Lấy thông tin user hiện tại
    $user_info = get_userdata( $user_id );

    // Sanitize tên hiển thị
    $display_name = sanitize_text_field( $data['display_name'] ?? '' );

    // Loại bỏ toàn bộ khoảng trắng kể cả unicode
    $display_name = trim( preg_replace( '/\p{Z}+|[\s]+/u', '', $display_name ) );

    // Nếu sanitize xong mà rỗng => fallback sang nickname hoặc username
    if ( empty( $display_name ) ) {
        $nickname = get_user_meta( $user_id, 'nickname', true );
        if ( ! empty( trim( $nickname ) ) ) {
            $display_name = trim( $nickname );
        } else {
            $display_name = $user_info->user_login;
        }
    }

    $bio      = sanitize_textarea_field( $data['bio'] ?? '' );
    $password = $data['new_password'] ?? '';

    $facebook = esc_url_raw( $data['facebook'] ?? '' );
    $twitter  = esc_url_raw( $data['twitter'] ?? '' );
    $discord  = sanitize_text_field( $data['discord'] ?? '' );
    $website  = esc_url_raw( $data['website'] ?? '' );
    $gender   = sanitize_key( $data['gender'] ?? '' );

    // Cập nhật user core
    wp_update_user( [
        'ID'           => $user_id,
        'display_name' => $display_name,
    ] );

    // Cập nhật mật khẩu nếu có
    if ( ! empty( $password ) ) {
        wp_set_password( $password, $user_id );
    }

    // Cập nhật các meta
    update_user_meta( $user_id, 'description', $bio );
    update_user_meta( $user_id, 'iue_facebook', $facebook );
    update_user_meta( $user_id, 'iue_twitter', $twitter );
    update_user_meta( $user_id, 'iue_discord', $discord );
    update_user_meta( $user_id, 'iue_website', $website );
    update_user_meta( $user_id, 'iue_gender', $gender );

    do_action( 'init_plugin_suite_user_engine_after_update_profile', $user_id, $data );

    return rest_ensure_response( [
        'success' => true,
        'data'    => [
            'display_name' => $display_name,
            'bio'          => $bio,
        ],
    ] );
}
