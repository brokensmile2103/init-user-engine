=== Init User Engine – Gamified, Fast, Frontend-First ===
Contributors: brokensmile.2103
Tags: user, level, check-in, referral, vip
Requires at least: 5.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.5.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gamified user engine with EXP levels, Coin/Cash wallet, check-in, VIP, inbox, and referral – powered by REST API and Vanilla JS.

== Description ==

**Init User Engine** is a lightweight, no-bloat user system for modern WordPress sites. It's designed for maximum frontend flexibility and gamified user engagement. All dynamic interfaces are rendered via JavaScript with real-time REST API interaction.

No jQuery. Minimal settings. Smart by default.

What you get:

- Display user avatar and dashboard via shortcode
- Show level, EXP, Coin/Cash, and full user wallet
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
- VIP membership system with Coin-based purchase & expiry  
- Referral module with cookie-based signup tracking  
- Redeem Code / Gift Code module – code in, rewards out
- REST API for all features (read/write/modify)  
- Action/filter hooks for full customization  
- Pure Vanilla JS frontend – no jQuery, no server bloat  
- Admin notification panel to send messages to selected users or all members  

== Screenshots ==

1. Settings with options for theme color, currency labels, and admin bar/Gravatar control.
2. Custom Links section for setting Register and Lost Password URLs.
3. Check-in Reward configuration, including Coin, EXP, and Cash per check-in.
4. Online Reward configuration based on active time with reward values.
5. VIP Pricing (by Coin) options for various durations, including lifetime.
6. VIP Bonus settings to configure extra Coin/EXP for VIP users.
7. Referral Reward settings for both referrer and new user.
8. Admin panel to send notifications with content, targeting, priority, and expiration.
9. Login modal interface for non-logged-in users.
10. Registration modal with username, email, and password fields.
11. Avatar button with dropdown panel showing user info, level, stats, and quick links.
12. VIP Membership modal with Coin-based purchase options and expiration note.
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
- `init_plugin_suite_user_engine_calculated_coin_amount` – Modify Coin reward before apply  
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
- `init_plugin_suite_user_engine_should_keep_original` – Override decision to keep original uploaded avatar (GIF or other formats)  
- `init_plugin_suite_user_engine_vip_expire_soon_threshold` – Modify the threshold (in seconds) used to determine when VIP is considered close to expiration  
- `init_plugin_suite_user_engine_body_vip_classes` – Add, remove, or modify VIP-related CSS classes applied to the `<body>` element
- `init_plugin_suite_user_engine_theme_colors` – Modify theme color system (primary and active colors)

=== Actions ===

- `init_plugin_suite_user_engine_level_up` – When user levels up  
- `init_plugin_suite_user_engine_exp_added` – After EXP is added  
- `init_plugin_suite_user_engine_transaction_logged` – After Coin/Cash is logged  
- `init_plugin_suite_user_engine_exp_logged` – After EXP log is recorded  
- `init_plugin_suite_user_engine_inbox_inserted` – After new inbox message  
- `init_plugin_suite_user_engine_referral_completed` – When referral is completed  
- `init_plugin_suite_user_engine_after_checkin` – After user check-in  
- `init_plugin_suite_user_engine_after_claim_reward` – After user claims reward  
- `init_plugin_suite_user_engine_vip_purchased` – After VIP is purchased  
- `init_plugin_suite_user_engine_add_exp` – Triggered when adding EXP via hook  
- `init_plugin_suite_user_engine_add_coin` – Triggered when adding Coin via hook  
- `init_plugin_suite_user_engine_coin_changed` – After user’s Coin balance is updated  
- `init_plugin_suite_user_engine_cash_changed` – After user’s Cash balance is updated  
- `init_plugin_suite_user_engine_admin_send_notice` – When admin sends notification via wp-admin.

=== REST API Endpoints ===

**Base:** `/wp-json/inituser/v1/`

- `POST /register` – Create a new user account  
- `POST /checkin` – Daily check-in  
- `POST /claim-reward` – Claim reward after online duration  
- `GET  /transactions` – Get Coin/Cash transaction log  
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
- `POST /exchange` – Convert Cash → Coin based on exchange rate
- `POST /redeem-code` – Redeem gift code (returns Coin/Cash rewards)

== Frequently Asked Questions ==

= How do I customize the UI? =  
The frontend is written in modular Vanilla JS with minimal HTML structure.  
Override styles via your theme or inject custom JS as needed.

= Where is user data stored? =  
- `user_meta`: EXP, level, Coin, Cash, VIP, referral  
- `wp_init_user_engine_inbox`: inbox messages (custom DB table)

= Can I extend or integrate it? =  
Yes. The plugin is built around WordPress hooks and REST API. You can inject logic via `add_action`, `add_filter`, or build your own UI on top of the endpoints.

= Is it compatible with WooCommerce or BuddyPress? =  
Not officially, but it’s modular and can be integrated via code or future addons.

= How do I send messages to users manually? =  
Go to **Users → Init User Engine → Send Notification** in wp-admin.  
You can search users, customize message type, link, priority, and even set expiration.

== Changelog ==

= 1.5.2 – May 17, 2026 =
- Updated custom dashicon CSS to use `currentColor` for full compatibility with WordPress Administration Color Schemes
- Removed hardcoded icon colors that conflicted with theme-aware color variables
- Ensured compatibility with WordPress 7.0's updated admin color system

= 1.5.1 – April 22, 2026 =
- Refactored migration architecture to use self-looping WP-Cron instead of admin_init execution
- Introduced background migration runner (`init_plugin_suite_iue_migration_event`) with automatic rescheduling
- Added transient-based locking mechanism to prevent concurrent migration execution
- Migration process is now fully decoupled from admin traffic and runs reliably in low-traffic environments
- Improved stability and consistency of batch migration for large datasets
- Activation hook now schedules migration automatically if not already completed
- Maintained full backward compatibility with existing migration logic and data structures

= 1.5.0 – April 21, 2026 =
- Migrated transaction log (coin/cash) and EXP log from user meta to dedicated database tables
- Introduced `init_user_engine_transaction_log` and `init_user_engine_exp_log` tables for better scalability
- Automatic data migration from old user meta (`iue_coin_cash_log`, `iue_exp_log`) with cleanup on completion
- Migration runs in batches of 200 users to prevent timeouts on large sites and resumes if interrupted
- REST API pagination for transaction and EXP history now uses true COUNT + OFFSET instead of loading all records
- Added `wp_cache` support for transaction and EXP log reads with automatic invalidation on write
- Database schema check is now version-gated to avoid redundant queries on every admin load
- Schema and migration are also triggered via `upgrader_process_complete` for reliable update handling
- Full backward compatibility maintained: all hooks, filters, and i18n strings are preserved

= 1.4.9 – April 15, 2026 =
- Added filter to override theme color system (theme_color, theme_active_color)
- Introduced centralized color hook for easier customization from themes and addons
- Ensured safe fallback when filter returns incomplete or invalid values

= 1.4.8 – March 25, 2026 =
- Fixed check-in countdown not starting on new devices after login
- Countdown now resets to full duration on unrecognized devices
- Remaining time is saved only on tab hide and page unload, not every second
- Fixed date comparison using locale-aware format to prevent UTC offset mismatch

= 1.4.7 – March 24, 2026 =
- Added wp_cache support for unread Inbox count
- Reduced database load by caching COUNT(*) queries per user
- Cache is automatically cleared on insert, read, delete, and bulk operations
- Introduced centralized cache helpers and consistent naming
- Improved performance and internal code structure for better maintainability

= 1.4.6 – February 7, 2026 =
- Fixed redeem code generation logic to respect custom codes
- Single-use codes now preserve exact input when quantity is 1
- Prefix + random suffix only applies to batch generation (qty > 1)
- Multi-use and locked codes no longer force random suffixes

= 1.4.5 – February 4, 2026 =
- Fixed Inbox pagination not respecting the active filter
- Total message count and total pages are now calculated per category
- Prevents incorrect page numbers when switching between filters
- Ensures accurate server-side pagination and consistent navigation

= 1.4.4 – February 4, 2026 =
- Added bulk generation for single-use redeem codes
  - Supports quantity-based creation with automatic prefix usage
  - Generates random 6-character suffix using `wp_generate_password()`
  - When quantity = 1, uses the exact input code (no random suffix appended)
- Added safe delete action for redeem codes (only unused codes can be removed)
- Improved redeem code creation flow with better validation, sanitization, and consistent behavior across modes
- Upgraded Inbox system with categorized filters
  - Added filters: All, Unread, System, Rewards, Activity, Other
  - Server-side filtering with correct pagination
  - Logical grouping of message types for cleaner UX
- Minor UI and internal refinements for consistency and maintainability

= 1.4.3 – January 28, 2026 =
- Added VIP state–aware body classes for frontend customization
  - Automatically adds `iue-vip` for active VIP users
  - Adds `iue-vip-expired` for users whose VIP has expired
  - Adds `iue-expire-soon` when VIP is close to expiration (default: ≤ 1 day)
- Introduced extensibility hooks for VIP presentation logic
  - New filter `init_plugin_suite_user_engine_vip_expire_soon_threshold` to customize the “expire soon” window
  - New filter `init_plugin_suite_user_engine_body_vip_classes` to allow developers to add or modify VIP-related body classes
- Improved separation between VIP core logic and UI layer
  - Enables lightweight CSS-based customization without conditional checks
  - Keeps VIP business logic isolated and stable
- Minor internal refinement for consistency and long-term maintainability

= 1.4.2 – November 19, 2025 =
- Updated **transaction logging system** for Coin & Cash
  - VIP users now automatically receive the correct **bonus %** directly inside the log entry
  - Log entries now include:
    - `original` (amount before bonus)
    - `amount` (amount after bonus)
    - `vip_bonus` flag and `bonus_percent` value
  - Ensures perfectly aligned behavior with `init_plugin_suite_user_engine_add_coin()`
- Improved accuracy of VIP-related operations
  - Bonus only applies to **Coin** and only when **adding** (no bonus for deductions)
  - Avoids mismatch between displayed history and real balance changes
- Enhanced internal data consistency
  - Log entries capped at 100 items with stable array slicing
  - Ensures clean, lightweight meta storage over long-term usage
- Minor structural refinement for better readability and maintainable code paths

= 1.4.1 – November 17, 2025 =
- Fixed VIP bonus logic when modifying Coin balance
  - Bonus percentage now applies **only when adding** positive Coin amounts
  - Negative adjustments (deducting Coin) **no longer receive bonus**
- Added support for `data-iue="register"`
  - Automatically opens the modal **and switches directly to the Register tab**
  - Ignores custom register URL mode (only toggles modal when active)
- Improved WPCS compatibility
  - Added targeted `phpcs:ignore` rules for PluginCheck false positives
  - Clean handling of dynamic table names in prepared SQL queries
- No other changes; fast patch release for immediate correctness

= 1.4.0 – November 4, 2025 =
- Improved **Admin User Overview** security model
  - Any user can view their own overview (Coin, Cash, Level, VIP info, Inbox)
  - Action buttons (Remove VIP / Toggle Avatar Upload Ban / Inbox Statistics) are now restricted to administrators only
  - UI gracefully disables restricted actions for non-admin users instead of hiding them
- Added server-side permission guards for sensitive actions
  - `iue_remove_vip` and `iue_toggle_avatar_ban` now require `manage_options`
  - Requests are validated using capability check + nonce verification
  - Prevents URL/REST crafting or manual calls to admin-post endpoints
- Improved admin notices behavior
  - Success/error messages only appear for administrators
  - Notices limited to `profile.php` and `user-edit.php` screens
- Minor code cleanup and consistency improvements to maintainable structure

View full changelog (all versions): [Init User Engine – Changelog](https://en.inithtml.com/plugin/init-user-engine/)

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
