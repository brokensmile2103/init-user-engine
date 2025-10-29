=== Init User Engine – Gamified, Fast, Frontend-First ===
Contributors: brokensmile.2103
Tags: user, level, check-in, referral, vip
Requires at least: 5.5
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gamified user engine with EXP levels, coin wallet, check-in, VIP, inbox, and referral – powered by REST API and Vanilla JS.

== Description ==

**Init User Engine** is a lightweight, no-bloat user system for modern WordPress sites. It's designed for maximum frontend flexibility and gamified user engagement. All dynamic interfaces are rendered via JavaScript with real-time REST API interaction.

No jQuery. Minimal settings. Smart by default.

What you get:

- Display user avatar and dashboard via shortcode
- Show level, EXP, coin/cash, and full user wallet
- Let users check-in daily and receive timed rewards
- Auto-track referral registrations with reward system
- Allow users to buy VIP status using in-site currency
- Built-in inbox for notifications (uses custom DB table)
- Custom avatar support with upload & preview modal
- Send custom notifications to selected users or all members from wp-admin

This plugin is the core user system behind the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) – optimized for frontend-first interaction, extensibility, and real-time gamification.

GitHub repository: [https://github.com/brokensmile2103/init-user-engine](https://github.com/brokensmile2103/init-user-engine)

== Features ==

- Avatar shortcode `[init_user_engine]` + modal dashboard  
- Avatar system with upload, preview, and revert support  
- EXP & Level system with hookable progression logic  
- Coin & Cash wallet system with transaction logs  
- Daily check-in with streak milestones & online bonus timer  
- Inbox system with pagination, read/claim/delete  
- VIP membership system with coin-based purchase & expiry  
- Referral module with cookie-based signup tracking  
- REST API for all features (read/write/modify)  
- Action/filter hooks for full customization  
- Pure Vanilla JS frontend – no jQuery, no server bloat  
- Admin notification panel to send messages to selected users or all members  

== Screenshots ==

1. Settings with options for theme color, currency labels, and admin bar/Gravatar control.
2. Custom Links section for setting Register and Lost Password URLs.
3. Check-in Reward configuration, including coin, EXP, and cash per check-in.
4. Online Reward configuration based on active time with reward values.
5. VIP Pricing (by Coin) options for various durations, including lifetime.
6. VIP Bonus settings to configure extra Coin/EXP for VIP users.
7. Referral Reward settings for both referrer and new user.
8. Admin panel to send notifications with content, targeting, priority, and expiration.
9. Login modal interface for non-logged-in users.
10. Registration modal with username, email, and password fields.
11. Avatar button with dropdown panel showing user info, level, stats, and quick links.
12. VIP Membership modal with coin-based purchase options and expiration note.
13. Inbox modal showing system messages, rewards, and user notifications.
14. Transaction history modal showing all reward activities (check-in, referral, online time...).
15. Referral modal with shareable code/link, social sharing buttons, and referral history.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/init-user-engine`  
2. Activate it via the Plugins screen  
3. Use `[init_user_engine]` in any page/post/template  
4. You're done – modals and logic load automatically  

== Developer Hooks ==

=== Filters ===

- `init_plugin_suite_user_engine_online_minutes` – Modify required online minutes after check-in  
- `init_plugin_suite_user_engine_vip_prices` – Modify VIP package prices  
- `init_plugin_suite_user_engine_referral_rewards` – Modify referral rewards  
- `init_plugin_suite_user_engine_localized_data` – Modify frontend JS data  
- `init_plugin_suite_user_engine_calculated_coin_amount` – Modify coin reward before apply  
- `init_plugin_suite_user_engine_calculated_exp_amount` – Modify EXP reward before apply  
- `init_plugin_suite_user_engine_exp_required` – Modify EXP required per level  
- `init_plugin_suite_user_engine_checkin_milestones` – Set milestone streak days  
- `init_plugin_suite_user_engine_format_inbox` – Modify formatted inbox data  
- `init_plugin_suite_user_engine_render_level_badge` – Customize level badge HTML  
- `init_plugin_suite_user_engine_inbox_insert_data` – Modify inbox data before inserting into database  
- `init_plugin_suite_user_engine_validate_register_fields` – Validate or modify registration fields before account creation  
- `init_plugin_suite_user_engine_after_register` – Hook after successful user registration (pass user ID and submitted data)
- `init_plugin_suite_user_engine_daily_tasks` – Add or modify daily task list and logic
- `init_plugin_suite_user_engine_captcha_bank` – Extend or modify the internal captcha question bank used for fallback validation
- `init_plugin_suite_user_engine_format_log_message` – Customize transaction log message display with access to entry data, source, type, and amount

=== Actions ===

- `init_plugin_suite_user_engine_level_up` – When user levels up  
- `init_plugin_suite_user_engine_exp_added` – After EXP is added  
- `init_plugin_suite_user_engine_transaction_logged` – After coin/cash is logged  
- `init_plugin_suite_user_engine_exp_logged` – After EXP log is recorded  
- `init_plugin_suite_user_engine_inbox_inserted` – After new inbox message  
- `init_plugin_suite_user_engine_referral_completed` – When referral is completed  
- `init_plugin_suite_user_engine_after_checkin` – After user check-in  
- `init_plugin_suite_user_engine_after_claim_reward` – After user claims reward  
- `init_plugin_suite_user_engine_vip_purchased` – After VIP is purchased  
- `init_plugin_suite_user_engine_add_exp` – Triggered when adding EXP via hook  
- `init_plugin_suite_user_engine_add_coin` – Triggered when adding coin via hook  
- `init_plugin_suite_user_engine_coin_changed` – After user’s Coin balance is updated  
- `init_plugin_suite_user_engine_cash_changed` – After user’s Cash balance is updated  
- `init_plugin_suite_user_engine_admin_send_notice` – When admin sends notification via wp-admin.

=== REST API Endpoints ===

**Base:** `/wp-json/inituser/v1/`

- `POST /register` – Create a new user account  
- `POST /checkin` – Daily check-in  
- `POST /claim-reward` – Claim reward after online duration  
- `GET  /transactions` – Get coin/cash transaction log  
- `GET  /exp-log` – Get EXP log  
- `GET  /inbox` – Get inbox messages  
- `POST /inbox/mark-read` – Mark a message as read  
- `POST /inbox/mark-all-read` – Mark all as read  
- `POST /inbox/delete` – Delete a single message  
- `POST /inbox/delete-all` – Delete all messages  
- `POST /vip/purchase` – Purchase VIP package  
- `GET  /referral-log` – Get referral history  
- `POST /avatar` – Upload new avatar  
- `POST /avatar/remove` – Remove custom avatar and revert to default  
- `GET  /profile/me` – Get current user profile  
- `POST /profile/update` – Update profile information
- `GET  /daily-tasks` – Get list of completed daily tasks and rewards

== Frequently Asked Questions ==

= How do I customize the UI? =  
The frontend is written in modular Vanilla JS with minimal HTML structure.  
Override styles via your theme or inject custom JS as needed.

= Where is user data stored? =  
- `user_meta`: EXP, level, coin, cash, VIP, referral  
- `wp_init_user_engine_inbox`: inbox messages (custom DB table)

= Can I extend or integrate it? =  
Yes. The plugin is built around WordPress hooks and REST API. You can inject logic via `add_action`, `add_filter`, or build your own UI on top of the endpoints.

= Is it compatible with WooCommerce or BuddyPress? =  
Not officially, but it’s modular and can be integrated via code or future addons.

= How do I send messages to users manually? =  
Go to **Users → Init User Engine → Send Notification** in wp-admin.  
You can search users, customize message type, link, priority, and even set expiration.

== Changelog ==

= 1.3.7 – October 29, 2025 =
- Added Redeem Code Module (Gift Code / Voucher System):
  - Admin can create redeem codes with 3 usage modes: single use (auto-disabled after first redemption), multi-use with configurable limit, and user-locked mode
  - Supports auto-generation of random codes when left blank
  - Added optional validity window (Valid from → Valid to) for time-limited campaigns
  - Displays usage count as used/max_uses and status badge (Active/Disabled)
- Added Automatic Reward Distribution:
  - Adds Coin/Cash to user balance with full transaction logging via `init_plugin_suite_user_engine_log_transaction()`
  - Sends inbox notification to redeemer confirming awarded rewards
  - Integrated with existing balance and transaction systems
- Enhanced Redemption Security:
  - Enforced one-time redemption per user even for multi-use codes
  - Uses metadata on code record to store user_id, username, display name, and redemption timestamp
  - Added database-level concurrency safety using `START TRANSACTION` and `SELECT ... FOR UPDATE` to prevent race conditions
  - Users attempting to redeem same code twice receive "You have already used this code" error
- Added REST Endpoint:
  - `POST /redeem-code` for logged-in users
  - Returns structured response: `{ success, message, coin, cash }`
  - Automatically disables code when usage limit reached
- Improved Admin UI:
  - Integrated with existing user search UI (same as Inbox Tool & Top-Up Tool)
  - Supports selecting specific user for locked-code mode through live search
  - Maintains consistent layout and interaction patterns
- Database and Compatibility:
  - Metadata now stores redeemed users (JSON format)
  - ACID-protected redemption operations (atomic + thread-safe)
  - Fully backward compatible with existing Coin/Cash/VIP/Inbox systems
  - No breaking changes to existing APIs

= 1.3.6 – October 27, 2025 =
- Fixed Critical Display Name Sanitization Bug:
  - Resolved issue where `update_profile` endpoint removed all whitespace from display names (e.g., "Nguyễn Văn A" → "NguyễnVănA")
  - Adjusted sanitization to preserve natural spacing while preventing XSS and invalid characters
- Improved Display Name Fallback Logic:
  - Enhanced empty-name handling to ensure proper fallback to nickname or username
  - Uses stricter validation for better reliability across multilingual inputs
- No database or schema changes. Backward compatible and safe for immediate deployment. Affected endpoint: `init_plugin_suite_user_engine_api_update_profile()`

= 1.3.5 – October 27, 2025 =
- Refined Submit Button UI (Login + Register):
  - Modern, minimal interaction with no glow or shadows
  - Subtle hover/active feedback using transform and filter (crisp, non-flashy)
  - Preserves theme colors: `var(--iue-theme-color)` → `var(--iue-theme-active-color)` gradient
  - Improved `:focus-visible` outline for accessibility with zero layout shift
  - Affected selectors: `.iue-login-form input[type="submit"]`, `.iue-login-form .login-submit input[type="submit"]`, `.iue-register-form button.iue-submit`
- No markup changes required. CSS-only update and backward compatible

= 1.3.4 – October 23, 2025 =
- Enhanced Captcha Security System:
  - Expanded captcha question bank with 40+ new math and logic-based variations
  - Introduced 4 smart captcha modes: symbolic math (`+`, `−`, `×`), text-based math (e.g., "What is 5 plus 3?"), general knowledge numerics (e.g., "How many days in a week?"), and contextual variants (e.g., "Double 4 is?", "Give the next even number after 7")
  - Added internal hook `init_user_engine_captcha_bank` to allow external extensions to register new captcha questions
  - Localized all captcha questions and added full translator context for `%` placeholders
  - Ensured all captcha answers are numeric-only for maximum bot resistance
- Added Disable Captcha setting:
  - Allows disabling all captcha validations (including Turnstile) for testing environments
  - Includes strong "DANGER" warning and contextual description to prevent misuse
  - Automatically bypasses both frontend and backend captcha logic when enabled
- Added Disable New Registrations feature:
  - Completely blocks new user registrations across both REST API and WordPress forms
  - Integrates with registration endpoint for immediate early return
  - Prevents rendering of registration form on login/register templates when active
  - Designed for maintenance or private-access environments
- Improved Multi-Layer Bot Protection with combined honeypot, custom captcha, and Cloudflare Turnstile verification. Added global registration lockout switch and enhanced IP-based rate limiting

= 1.3.3 – October 23, 2025 =
- Enhanced Admin Notification Tool:
  - Fully synchronized recipient selection UI and backend logic with the Top-up Tool
  - Added unified recipient options: selected users (manual input with live search), active VIPs (fetched via `init_plugin_suite_user_engine_get_active_vip_users( 'ids' )`), and all members (retrieved using `get_users( [ 'fields' => 'ID' ] )`)
  - Replaced old "Send to all" checkbox with radio-based recipient selection for consistency
  - Integrated automatic user resolution for VIP group using same helper function as Top-up
  - Added bulk message delivery with chunked sending through `init_user_engine_inbox_bulk_chunk_size` filter (default: 500)
  - Retains full compatibility with existing inbox delivery logic, meta, and hooks
- Improved Admin UI Consistency:
  - Recipient selection block now mirrors the Top-up Tool layout and markup
  - "Select Users" interface unified for both tools (search, display, hidden ID handling)
  - Maintains identical sanitization, nonce verification, and capability checks
- No functional or database schema changes. Fully backward compatible with all prior notification and VIP systems

= 1.3.2 – October 18, 2025 =
- Added Avatar Upload Permission System:
  - Introduced new helper function `init_plugin_suite_user_engine_can_upload_avatar( $user_id )` for unified permission checking
  - Supports multi-layer policy: global disable (`disable_all`), VIP-only mode (`vip_only`) using `init_plugin_suite_user_engine_is_vip()`, and per-user ban via `iue_avatar_ban` user meta
  - Fully integrated into REST endpoints `upload_avatar` and `remove_avatar` for backend-level enforcement
  - Automatically blocks upload and deletion attempts from banned or non-VIP users according to policy
- Enhanced Admin User Metabox:
  - Added "Ban Avatar Upload" / "Unban Avatar Upload" button next to "Remove VIP"
  - Toggles the `iue_avatar_ban` meta instantly via secure `admin-post` action
  - Includes full nonce verification, capability checks, redirect notices, and audit hook `init_plugin_suite_user_engine_avatar_ban_toggled`
  - Displays current avatar permission state ("Allowed" / "BANNED") beside the button
- Improved Security and Consistency:
  - Backend guards prevent unauthorized file handling even if frontend modified
  - Added HTTP 403 and 423 codes for forbidden or locked states to ensure clear API responses
  - Unified `wp_die()` and `WP_Error` patterns across user-related endpoints

= 1.3.1 – October 17, 2025 =
- Enhanced Admin Top-up Tool:
  - Replaced old checkboxes with radio buttons for selecting recipients (Selected users / Active VIPs / All members)
  - Added automatic log display under the form showing up to 100 recent top-up entries
  - Logs include amount, type, recipient info (linked to user profile when applicable), and timestamp
  - Improved layout spacing and usability for cleaner admin experience
- Added Persistent Top-up Log System:
  - Introduced helper functions `init_plugin_suite_user_engine_add_topup_log()` and `init_plugin_suite_user_engine_get_topup_logs()`
  - Standardized log entry format: `quantity|type(coin|cash)|target(VIP|ALL|uid:{id}|user:{count})|time`
  - Automatically trims to the latest 100 entries
  - Stored via `update_option()` with autoload disabled
- Improved User Display in Logs:
  - User targets now show as clickable links to admin profile pages
  - Fallback added for deleted or invalid users
  - VIP, ALL, and multi-user targets show readable labels
- Backward Compatibility:
  - Compatible with legacy `iue_send_all` and `iue_send_vip` fields
  - No database or API changes
  - Existing balance, inbox, and transaction logic remain unchanged

= 1.3.0 – October 12, 2025 =
- Added Coin Exchange system:
  - Users can now convert Cash → Coin with a configurable rate
  - Added real-time conversion preview, validation, and rate display
  - Includes optional min/max limits via filters for full customization
  - Fully integrated with transaction log and dark mode UI
- Improved profile update reliability:
  - Added smart fallback for empty or invalid display names → automatically uses nickname or username
  - Prevented saving blank or whitespace-only display names after sanitization
  - Ensured consistent and safe user display names across all update scenarios
- No database or API changes. Fully backward compatible

= 1.2.9 – October 10, 2025 =
- Upgraded multi-user inbox sender to use **bulk insert** for massive scalability
  - Converts thousands of single inserts into optimized batched queries
  - Handles large user arrays efficiently with automatic chunking for stability
- Simplified inbox table creation for new installations (no index changes applied)
- Significantly improved performance when sending inbox messages to large user bases (e.g., 10,000+ users) 
- Refactored **Admin Tools**:
  - **Notification Tool** now uses the new bulk inbox sender for instant multi-user delivery
  - **Top-up Tool** updated to integrate with the new inbox system while keeping balance and log logic fully intact
- Fully backward compatible, no database schema or API changes

= 1.2.8 – October 10, 2025 =
- Added `autocomplete="off"` to all password fields in the settings page to prevent browsers from auto-saving or suggesting stored passwords
- Minor UI consistency adjustments in settings forms
- No functional or database changes

= 1.2.7 – October 8, 2025 =
- Added **Inbox Cleanup Tool** directly in the *Inbox Statistics* admin page:
  - Introduced new “Cleanup Inbox by Type” block under the **Refresh Data** section
  - Allows administrators to permanently delete all inbox messages of a selected `type`
  - Automatically lists all existing message types for quick selection
  - Includes nonce verification, capability checks, and confirmation prompt for safety
  - Displays success or error notice with deleted message count after operation
- Enhanced date range filter security:
  - Added nonce field and verification for the “Date Range” dropdown form
  - Removed WPCS `NonceVerification.Recommended` warnings on GET processing
- Improved overall PHPCS compliance for the statistics module:
  - Explicitly documented safe cases for display-only notices
  - Limited PHPCS ignores to justified database queries only
- Fully backward compatible with existing inbox data and analytics logic
- No database schema changes or new dependencies introduced

= 1.2.6 – October 5, 2025 =
- Enhanced Admin User Metabox for better visibility of user activity and communication:
  - Added Recent Transactions section under VIP Details showing up to 100 latest Coin/Cash logs with type, amount, source, and timestamp
  - Added Recent Inbox section under Inbox (User) listing up to 100 latest messages with type, status, title, and time
  - Both sections feature compact scrollable layouts with limited height for clean, admin-friendly viewing
- Fully backward compatible:
  - No database or meta structure changes
  - Automatically uses existing transaction and inbox data
  - Lightweight rendering ensures stable performance even with large user histories

= 1.2.5 – October 4, 2025 =
- Refined dashboard menu CSS for better scalability with multiple grouped items:
  - Added `.multi-menu` style to support grouped links (e.g., Sticker Store, Frame Store, Effects)
  - Adjusted padding, spacing, and hover states to keep grouped items compact but consistent with main menu
  - Improved dark mode support for multi-menu background/hover states
- Optimized avatar frame overlay CSS:
  - Prevented hover scaling on frame elements while keeping core avatar hover intact
  - Ensured frame overlay maintains alignment across various container contexts
- Minor visual polish:
  - Standardized border-radius and spacing for consistency across badges, dots, and menu links
  - Unified hover background opacity values in both light and dark modes

= 1.2.4 – October 4, 2025 =
- Added full integration with Cloudflare Turnstile for spam-proof registration:
  - Admin settings now support entering Turnstile **Site Key** and **Secret Key**
  - If both keys are set, registration form automatically shows Turnstile widget instead of legacy math captcha
  - Fallback to legacy captcha only when Turnstile keys are missing
  - If CAPTCHA is disabled entirely, registration form shows no challenge at all
- Updated registration endpoint (`/register`) to:
  - Verify Turnstile tokens server-side with Cloudflare API
  - Gracefully fallback to math captcha validation if Turnstile is not configured
  - Return structured WP_Error codes: `turnstile_required`, `turnstile_invalid`, `captcha_wrong`, etc.
  - Retain honeypot detection and IP-based rate limiting (max 5 attempts/hour)
- Enhanced `guest.js`:
  - Unified client-side flow for both Turnstile and legacy captcha
  - Implemented late initialization (Turnstile widget loads only when Register tab is shown) to reduce page weight
  - Reset Turnstile widget after each failed or successful attempt to avoid stale tokens
  - Clear, translated error messages for Turnstile failures (expired, timeout, missing token, etc.)
- Security hardened:
  - Prevented bypass when captcha/Turnstile token missing
  - Verified tokens are one-time-use per attempt
  - Ensured consistent block on invalid, expired, or reused tokens

= 1.2.3 – October 3, 2025 =
- Refactored all EXP + Coin award logic to use unified, extensible filters:
  - `init_plugin_suite_user_engine_publish_post_rewards`
  - `init_plugin_suite_user_engine_user_register_rewards`
  - `init_plugin_suite_user_engine_update_profile_rewards`
  - `init_plugin_suite_user_engine_daily_login_rewards`
  - `init_plugin_suite_user_engine_woo_order_rewards`
- Each filter now returns both `exp` and `coin` values in a single array for easier customization
- Preserved default reward amounts (e.g. 20 EXP + 5 Coin for publish, 50 EXP + 20 Coin for register, etc.)
- Added support for WooCommerce dynamic rewards calculation based on order total, with filter override
- Ensured backward compatibility with existing `init_plugin_suite_user_engine_add_exp` and `init_plugin_suite_user_engine_add_coin` actions
- Updated inbox notification content strings to use filtered values dynamically
- All new filters fully localized and translation-ready with proper `translators:` comments
- Synced Daily Tasks REST API with plugin settings:
  - `checkin_coin` setting now drives "Check in today" reward
  - `online_coin` setting now drives "Stay active today" reward
  - Prevented hardcoded reward mismatch between API output and actual check-in/claim logic

= 1.2.2 – October 3, 2025 =
- Added new Comment Reward options in plugin settings:
  - EXP per comment (default 10)
  - Coin per comment (default 2)
  - Daily comment cap (default 0 = unlimited, reset anchored to daily check-in)
- Implemented counter reset tied to `iue_checkin_last` meta to ensure daily limits reset only after user check-in
- Prevented reward farming by enforcing strict per-day cap logic
- Localized all new strings with msgid/msgstr entries for full translation support
- Preserved backward compatibility with existing EXP and Coin award actions

= 1.2.1 – September 28, 2025 =
- Refactored avatar override to hook into `pre_get_avatar_data` with very high priority, ensuring IUE avatar takes precedence over third-party filters such as Nextend Social Login  
- Retained lightweight `get_avatar_url` shim for backward compatibility with direct URL calls  
- Added safe fallbacks: defer to WordPress/Nextend when no IUE avatar is present; if Gravatar is disabled, serve bundled SVG as default  
- Improved cache behavior so avatar changes propagate more consistently with CDN/page cache purges  
- Fixed WPCS issues in `admin_post_iue_remove_vip`:  
  - Properly unslashed and sanitized all `$_GET` inputs before verification  
  - Strengthened nonce handling with a dedicated notice nonce for admin notices  
  - Escaped all dynamic output at the point of rendering to resolve `OutputNotEscaped` errors  
  - Sanitized query arguments on redirects; left targeted PHPCS ignores only for raw SQL queries with clear justification  
- No breaking changes: all actions, filters, and helper functions remain unchanged for seamless drop-in update

= 1.2.0 – September 28, 2025 =
- Added ability to revoke VIP membership directly from the Admin User Metabox
- Implemented automatic inbox notification to inform users when their VIP status is removed
- Ensured safe validation to guarantee VIP is removed from the correct user only
- Added new option in plugin settings to completely disable CAPTCHA on registration
- Updated registration form JS to auto-detect and skip CAPTCHA field when disabled
- Enhanced REST API `/register` to conditionally bypass CAPTCHA validation based on settings
- Expanded CAPTCHA trivia pool with fresh, unambiguous fact-based questions (e.g., sides of a square, letters in the English alphabet, spider legs, etc.)
- Fully localized all new CAPTCHA questions with ready-made msgid/msgstr entries
- Improved validation and UI consistency by showing clearer error messages and reset flow
- Ensured no debatable or ambiguous questions remain in the trivia pool for maximum reliability

= 1.1.9 – September 17, 2025 =
- Added extensible filter to inject custom KPIs after Cash in the Admin User metabox
- Introduced helper function to normalize extra KPI items and ensure safe output
- Provided theme example to display "Power Stone" metric with i18n and WPCS compliance

= 1.1.8 – September 17, 2025 =
- Added admin user metabox on profile/edit screens showing wallet, EXP, VIP, and inbox statistics
- Displayed Coin, Cash, Level, and EXP progress with dynamic progress bar
- Integrated VIP information including total purchases, expiry date, lifetime detection, and coin spent
- Implemented inbox quick stats with total, unread, last 7 days, and last message timestamp
- Linked inbox statistics directly to full analytics page for quick navigation
- Included helper functions with safe fallbacks for coin, cash, VIP, and inbox stats to ensure robustness
- Fixed missing version parameter in `wp_register_style()` to comply with WordPress coding standards
- Enhanced inline admin CSS with proper versioning using `INIT_PLUGIN_SUITE_IUE_VERSION`

= 1.1.7 – August 30, 2025 =
- Refactored Admin Top-up tool to support both addition (positive) and deduction (negative) amounts
- Bypassed VIP/bonus multipliers during manual top-ups to ensure exact applied value
- Implemented raw balance adjustments with automatic clamping to prevent negative wallet values
- Updated inbox notifications to reflect both top-up and deduction actions consistently
- Enhanced transaction logging with accurate applied amounts and proper change type (add/deduct)
- Added `translators:` comments for all `sprintf()` translation strings in Top-up tool to meet WPCS i18n standards
- Improved admin UI with description note: positive values add funds, negative values deduct funds

= 1.1.6 – August 23, 2025 =
- Enhanced database initialization system with admin_init hook for improved reliability
- Added administrator privilege verification to ensure secure table creation and maintenance
- Implemented comprehensive table existence checking to prevent database inconsistencies
- Improved multisite compatibility with automatic table creation for new blog instances
- Added PHPCS compliance annotations to suppress unnecessary warnings for database operations
- Strengthened plugin activation process with fail-safe table creation mechanisms

= 1.1.5 – August 22, 2025 =
- Fixed timezone consistency issues in transient cleanup cron scheduler
- Corrected inbox statistics queries to properly handle WordPress timezone settings
- Enhanced date range filtering accuracy for inbox analytics and daily activity charts
- Improved scheduled cleanup reliability by using WordPress timezone-aware functions
- Fixed statistical calculations that were affected by UTC vs local time discrepancies
- Updated cron frequency from hourly to twice-daily for optimal performance balance

= 1.1.4 – August 22, 2025 =
- Optimized captcha loading system with lazy initialization to reduce unnecessary API calls
- Implemented smart captcha management that only loads when users access the registration form
- Fixed memory leaks in JavaScript interval handlers with proper cleanup on modal close
- Enhanced registration flow by preserving captcha on successful submissions instead of unnecessary reloads
- Improved performance by eliminating background captcha generation for inactive registration forms
- Reduced server load and database transient accumulation through intelligent captcha lifecycle management

= 1.1.3 – August 22, 2025 =
- Added automated hourly cleanup system for expired transient data
- Implemented scheduled cron job to remove outdated captcha and rate limiting transients
- Enhanced database performance by preventing transient accumulation and orphaned records
- Improved system stability through regular cleanup of temporary data without manual intervention
- Added proper cleanup on plugin deactivation to maintain database integrity
- Optimized memory usage by eliminating stale transient entries that could impact site performance

= 1.1.2 – August 19, 2025 =
- Added automated weekly cleanup system for orphaned inbox messages
- Implemented silent background maintenance to remove inbox entries from deleted user accounts
- Enhanced database integrity by automatically clearing orphaned data without logging or notifications
- Improved system performance through regular cleanup of stale inbox records

= 1.1.1 – August 18, 2025 =
- Added full Inbox Statistics admin page with detailed analytics and charts
- Implemented date range filter (7d, 30d, 90d, all-time) for customizable reporting
- Built overview grid showing total, unread, daily, and recipient counts
- Introduced advanced breakdowns: message types, priority levels, and engagement analytics
- Added daily activity chart, top recipients leaderboard, and recent activity summary
- Implemented refresh button with last updated timestamp for real-time insights
- Added simplified dashboard widget showing key inbox metrics with quick action links

= 1.1.0 – August 14, 2025 =
- Reorganized and consolidated all CSS files into a single minified stylesheet for improved performance
- Combined all JavaScript files into one unified script file to reduce HTTP requests
- Optimized asset loading by eliminating multiple file dependencies
- Improved page load times through streamlined resource management
- Enhanced maintainability with centralized CSS and JS architecture
- Reduced bandwidth usage and improved caching efficiency for better user experience

= 1.0.9 – August 3, 2025 =
- Added extensible filter system to `init_plugin_suite_user_engine_format_log_message()` function
- Introduced `init_user_engine_format_log_message` filter hook for customizing transaction log messages
- Enhanced log message formatting with access to full entry data, source, type, and amount parameters
- Improved code maintainability by allowing themes and plugins to extend log message display

= 1.0.8 – July 31, 2025 =
- Completely rewrote check-in countdown logic to only count when tab is active
- Changed from timestamp-based calculation to real-time remaining seconds storage
- Fixed critical bug where countdown continued running in background when tab was hidden
- Added proper tab visibility detection to pause/resume countdown accurately
- Implemented daily reset mechanism that clears old countdown data on new day
- Fixed auto-reward claiming issue that occurred after long periods of inactivity
- Added proper state persistence when switching tabs or closing browser
- Countdown now properly resumes from exact remaining time when returning to tab
- Improved localStorage management with separate keys for remaining time and date tracking
- Enhanced countdown reliability by saving state on every second tick
- Fixed memory leaks by properly clearing intervals and event listeners

= 1.0.7 – July 31, 2025 =
- Refactored daily check-in JavaScript: clearer logic, safer countdown handling, and accurate reward claiming
- Removed redundant `localStorage` key (`REMAINING_KEY`), simplified timing logic using only `startTime`
- Fixed countdown bug when tab visibility changes by pausing updates and preventing memory leaks
- Handled auto-claim reward when returning after countdown has finished
- Prevented duplicate intervals by clearing previous `setInterval` before starting new one
- Fixed bug where disabled check-in button never re-enabled on error
- Rewrote `/daily-tasks` REST API to prevent fatal errors caused by invalid log entries
- Replaced unsafe closures in task check callbacks with static callbacks (`__return_true`)
- Added fallback and error logging in `call_user_func` for task completion checks
- Filtered and validated log entries before accessing offsets to avoid runtime crashes

= 1.0.6 – July 28, 2025 =
- Added Daily Task modal with REST API support
- Built daily tasks for check-in, online activity, and other actions based on real user logs
- Rewards now dynamically reflect actual amount and type (coin/cash) from transaction data
- Tasks only appear if completed, ensuring a clean and relevant UI
- Supported extensible task system via `init_plugin_suite_user_engine_daily_tasks` filter
- Added `translators:` comments for all `sprintf()` translation strings in CAPTCHA module
- Minor refinements to i18n strings for better clarity and consistency

= 1.0.5 – July 23, 2025 =
- Emergency fix for PHP 7.4 compatibility (replaced match expressions and array unpacking)
- Standardized all translation strings for full i18n compliance

= 1.0.4 – July 23, 2025 =
- Upgraded CAPTCHA system with better validation and answer protection
- Introduced three CAPTCHA modes: math symbols, natural language, and mixed trivia
- Added smart attempt tracking with auto-refresh after too many failures
- Improved token generation using user agent, IP, and timestamp for enhanced uniqueness
- Increased CAPTCHA expiration time to 15 minutes
- Fully localized all new CAPTCHA questions and error messages

= 1.0.3 – July 22, 2025 =
- Secured the `/register` endpoint against spam and abuse
- Added custom CAPTCHA system with randomized math questions (e.g. "6 + 3", "What is 5 times 2?")
- Implemented honeypot hidden field to block bot-based submissions
- Stored CAPTCHA answer via transient using IP + token with 10-minute expiration
- All CAPTCHA questions are fully translatable using standard i18n functions
- Minor code cleanup and improved form reliability

= 1.0.2 – July 21, 2025 =
- Added Edit Profile modal with support for display name, bio, password, social links, website, and gender
- Built REST API endpoints for fetching and updating user profile
- Created Admin Top-up tool for Coins and Cash
- Supports selecting specific users or sending to all members
- Logs transaction and sends inbox notification upon top-up

= 1.0.1 – July 8, 2025 =
- Added full registration module for guest users
- Toggle login/register forms with animated UI
- Added password visibility toggle using SVG icons
- Built REST API endpoint `/register` for user signup
- Implemented client-side validation with i18n support
- Improved i18n messages for all form interactions
- Automatically updates modal header when switching forms

= 1.0.0 – June 23, 2025 =  
- Initial release  
- Shortcode `[init_user_engine]` for frontend avatar + dashboard  
- EXP system with level-up bonus and milestone logic  
- Coin & cash wallet with transaction log  
- Daily check-in with online timer reward  
- REST API for EXP, coin, inbox, VIP, referral  
- Inbox system with pagination and actions  
- VIP purchase system via coin  
- Referral system using cookie-based signup tracking 
- Avatar module with upload/remove and REST API support 

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
