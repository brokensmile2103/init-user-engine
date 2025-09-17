=== Init User Engine – Gamified, Fast, Frontend-First ===
Contributors: brokensmile.2103
Tags: user, level, check-in, referral, vip
Requires at least: 5.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.8
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
- `init_user_engine_format_log_message` – Customize transaction log message display with access to entry data, source, type, and amount

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
