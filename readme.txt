=== Init User Engine – Gamified, Fast, Frontend-First ===
Contributors: brokensmile.2103
Tags: user, level, check-in, referral, vip
Requires at least: 5.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.7.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gamified user engine with EXP levels, Coin/Cash wallet, check-in, VIP, inbox, and referral – powered by REST API and Vanilla JS.

== Description ==

**Init User Engine** is a lightweight, no-bloat user system for modern WordPress sites. It's designed for maximum frontend flexibility and gamified user engagement. All dynamic interfaces are rendered via JavaScript with real-time REST API interaction.

No jQuery. Minimal settings. Smart by default.

What you get:

- Display user avatar and dashboard via shortcode
- Show level, EXP, Coin/Cash, and full user wallet
- Frontend login & registration modal with Cloudflare Turnstile (or built-in math captcha) protection
- Let users check-in daily and receive timed rewards
- Auto-track referral registrations with reward system
- Let users buy VIP status using Coin, Cash, or both
- Redeem Code / Gift Code and dedicated VIP Code system, with CSV export from wp-admin
- Built-in inbox for notifications (uses custom DB table), with pinned/expiring messages and a stats dashboard
- Custom avatar support with upload & preview modal
- Send custom notifications to selected users or all members from wp-admin
- Optional "Require Login to Access Site" mode that gates the entire frontend behind the built-in login modal
- Optional one-step "Login After Register" so new users land on the site already signed in

This plugin is the core user system behind the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) – optimized for frontend-first interaction, extensibility, and real-time gamification.

GitHub repository: [https://github.com/brokensmile2103/init-user-engine](https://github.com/brokensmile2103/init-user-engine)

== Features ==

**Profile & Avatar**

- Avatar shortcode `[init_user_engine]` – renders the guest login button or the logged-in avatar with dropdown dashboard automatically  
- Modal dashboard showing level, EXP progress, Coin/Cash wallet, and quick links  
- Custom avatar upload with live preview, crop-free flow, and one-click revert to the default/Gravatar image  
- Configurable upload policy (allow everyone, VIP only, or disable uploads entirely) with a configurable max file size  
- Optional display name change limit (e.g. once every 7 days, like TikTok), enforced in the profile modal, `wp-admin/profile.php`, the REST API and WooCommerce Account details  
- Theme override support: any template file can be overridden by copying it to `your-theme/init-user-engine/`

**Accounts, Login & Registration**

- Frontend login & registration modal – no theme editing or template overrides required  
- "Login After Register" option to automatically sign users in right after they create an account (disabled by default)  
- Failed logins reopen the login modal on the current page with an inline error message, instead of redirecting to `wp-login.php`  
- Cloudflare Turnstile captcha on registration, with a built-in math-question captcha as automatic fallback when no Turnstile keys are set  
- Optional protection of the native WordPress login, registration, and lost-password forms (`wp-login.php`) using the same Turnstile setup  
- "Forgot password?" form right inside the login modal, using the native WordPress reset flow (can be turned off; a custom Lost Password URL always takes priority)  
- Custom redirect URLs for "Register" and "Lost password" links  
- Optional "Require Login to Access Site" mode that gates the entire frontend behind the built-in login modal  
- Ability to temporarily disable new registrations without affecting other plugins or WordPress core

**Gamification**

- EXP & Level system with hookable progression logic  
- Coin & Cash dual-wallet system with transaction logs and a configurable exchange rate between the two currencies  
- Daily check-in with streak milestones and an online-time bonus timer  
- Optional **Streak Recovery**: members who miss a few days can spend Coin to keep their check-in streak alive (allowed missed days and Coin price per day are configurable, off by default)  
- Built-in reward hooks for registration, daily login, comments, published posts, and completed WooCommerce orders (when WooCommerce is active)

**VIP Membership**

- VIP membership system with Coin-based, Cash-based, or combined purchase options  
- Multiple configurable pricing tiers per duration, including a lifetime option  
- VIP bonus multipliers for Coin/EXP earning  
- Optional VIP purchase lock (globally disable new purchases) and stacking restriction

**Referral**

- Referral module with cookie-based signup tracking  
- Separate, configurable rewards for both the referrer and the newly referred user

**Redeem & VIP Codes**

- Redeem Code / Gift Code module – code in, rewards out  
- Dedicated VIP Codes module to grant VIP duration through a code  
- One-click CSV export for both code lists (full list, streamed in batches, UTF-8 BOM, and sanitized against CSV/formula injection)

**Inbox & Notifications**

- Built-in inbox system with pagination and read/claim/delete actions  
- Pinned messages that automatically unpin once read, or once their optional expiration time passes  
- Admin panel to send custom notifications to selected users or all members, with priority and expiration  
- Inbox Statistics dashboard (Users → Init User Engine → Inbox Statistics) with date-range filtering

**Admin Tools**

- Manual Top-up tool for Coin/Cash (Users → Init User Engine → Top-up Coin/Cash)  
- Per-user overview metabox on the WordPress profile/edit-user screen (level, wallet, VIP status, recent activity), with one-click Remove VIP, Ban Avatar Upload and Unlock Display Name actions

**Developer-Friendly**

- REST API for all features (read/write/modify)  
- Action/filter hooks for full customization  
- Pure Vanilla JS frontend – no jQuery, no server bloat

== Screenshots ==

1. Settings with options for theme color, currency labels, and admin bar/Gravatar control.
2. Custom Links section for setting Register and Lost Password URLs.
3. Check-in Reward configuration, including Coin, EXP, and Cash per check-in.
4. Online Reward configuration based on active time with reward values.
5. VIP Pricing options for various durations, including lifetime.
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
- `init_plugin_suite_user_engine_streak_restore_max_days` – Modify how many missed check-in days a user may cover to keep their streak (return `0` to disable it for that user)
- `init_plugin_suite_user_engine_streak_restore_cost` – Modify the Coin cost of keeping a streak (receives total cost, missed days, and user ID)
- `init_plugin_suite_user_engine_lostpassword_modal_enabled` – Enable or disable the lost password form inside the login modal (receives the current value and the plugin settings)
- `init_plugin_suite_user_engine_display_name_change_days` – Modify how many days a member has to wait between two display name changes (receives the days from the settings and the user ID; `0` = unlimited)
- `init_plugin_suite_user_engine_display_name_limit_applies` – Return `false` to let a user (or role) change their display name freely (receives the current value and the user ID)

=== Actions ===

- `init_plugin_suite_user_engine_level_up` – When user levels up  
- `init_plugin_suite_user_engine_exp_added` – After EXP is added  
- `init_plugin_suite_user_engine_transaction_logged` – After Coin/Cash is logged  
- `init_plugin_suite_user_engine_exp_logged` – After EXP log is recorded  
- `init_plugin_suite_user_engine_inbox_inserted` – After new inbox message  
- `init_plugin_suite_user_engine_referral_completed` – When referral is completed  
- `init_plugin_suite_user_engine_after_checkin` – After user check-in  
- `init_plugin_suite_user_engine_streak_restored` – After a user paid Coin to keep their check-in streak (user ID, missed days, cost, new streak)  
- `init_plugin_suite_user_engine_after_claim_reward` – After user claims reward  
- `init_plugin_suite_user_engine_vip_purchased` – After VIP is purchased  
- `init_plugin_suite_user_engine_add_exp` – Triggered when adding EXP via hook  
- `init_plugin_suite_user_engine_add_coin` – Triggered when adding Coin via hook  
- `init_plugin_suite_user_engine_coin_changed` – After user’s Coin balance is updated  
- `init_plugin_suite_user_engine_cash_changed` – After user’s Cash balance is updated  
- `init_plugin_suite_user_engine_admin_send_notice` – When admin sends notification via wp-admin.
- `init_plugin_suite_user_engine_display_name_unlocked` – After an administrator lets a member change their display name again (user ID)

=== REST API Endpoints ===

**Base:** `/wp-json/inituser/v1/`

- `POST /register` – Create a new user account  
- `POST /checkin` – Daily check-in (optional JSON body: `prompt_restore`, `restore_streak`, `restore_cost` for Streak Recovery)  
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
- `GET  /profile/me` – Get current user profile (includes `display_name_limit`: `days`, `locked`, `next_change`, `notice`)  
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

= 1.7.1 – October 17, 2026 =
- Performance (large sites): every Coin/Cash change scanned the whole `usermeta` table. The atomic `UPDATE` added in 1.6.8 picked the wallet row with a subquery on the same table (`WHERE umeta_id = ( SELECT id FROM ( SELECT MIN(umeta_id) … ) )`), which MySQL/MariaDB cannot resolve through the primary key: `EXPLAIN` showed a full index scan (`type: index`, about 800k rows). In our 800k-row benchmark each check-in, reward, purchase, exchange or top-up spent about 0.3 seconds in the database, and the cost grew with the table
  - The wallet row is now looked up through the `user_id` index (only the rows of that one user are read), then changed by an `UPDATE … WHERE umeta_id = …` on the primary key: 1 row examined instead of the whole table, under 1 ms in the same benchmark
  - Still atomic: the new value is computed by the database and the funds check stays in the `WHERE` clause of the `UPDATE`. If the row changes between the lookup and the `UPDATE`, nothing is written and the change is retried. Tested with 40 parallel requests on one wallet: no balance spent twice, no credit lost
  - A plain single-table `UPDATE` by primary key: no multi-table `UPDATE … JOIN`, deterministic for statement-based replication, and no new index on WordPress core tables
  - The admin **Top-up** tool (all members / VIP members) gets the same speed-up, since it makes one wallet change per member
  - Balance reads (`init_plugin_suite_user_engine_wallet_read()`, the `X-IUE-Coin` / `X-IUE-Cash` headers, `GET /balance`) no longer use `ORDER BY umeta_id LIMIT 1`, which can make MySQL walk the primary key instead of the `user_id` index. The oldest row is picked in PHP, so the result is the same
  - Return values, error codes and the `init_plugin_suite_user_engine_coin_changed` / `..._cash_changed` actions are unchanged
- Performance: the **Referral log** (`GET /referral-log`) now compares `iue_referred_by` as a string, the way it is stored, instead of making the database convert every value to a number (which also matched values such as `12abc`). The invited members are loaded with one query instead of one query per member
- Performance: the list of active VIP members (used by the Top-up and Send Notification tools) no longer asks the database to count rows it never uses (`SQL_CALC_FOUND_ROWS`)
- Code quality: table checks in the upgrade routine, the log migration, the Edit User box and Inbox Statistics now use the prepared, `esc_like()`-escaped helper instead of raw `SHOW TABLES LIKE '…'` queries
- Performance (large sites): **Top-up Coin/Cash** and **Send Notification** to *All members* or *Active VIPs* now run in the background through WP-Cron. Before, `get_users()` loaded every user ID inside the admin request, then made one wallet change, one log row and one Inbox message per member; with tens of thousands of members the request could hit the PHP time limit halfway, with no way to know who had been paid
  - Members are walked by user ID in batches (100 for top-ups, 500 for notifications) with `ID > cursor`, never all at once and never with `OFFSET`. Each slice runs for up to 20 seconds (or half of `max_execution_time` when lower), then the next slice is scheduled right away
  - Crash-safe top-ups: for each member, the wallet change, the transaction log row, the Inbox message and the job progress are saved in one database transaction. We killed PHP (`kill -9`) in the middle of a member: that member was rolled back and handled again, and all 12,003 test members were paid exactly once
  - Notifications: if PHP dies during a batch, the next run skips the members who already got the message, so nobody gets it twice
  - New **Background Jobs** table on both pages with progress, members changed / messages sent, failures and status, plus a **Cancel** button (members already processed keep what they received). It refreshes by itself while the page is open
  - Jobs only ever run from WP-Cron: the built-in WP-Cron, or your system cron job if the site sets `DISABLE_WP_CRON`. The admin page never runs a job by itself, so heavy work you moved to a system cron stays there. When PHP has no time limit (WP-CLI, or `php wp-cron.php` run from the command line by a system cron job), each run processes up to 60 seconds of work
  - If a job makes no progress for 15 minutes (filter `init_plugin_suite_user_engine_bulk_job_stall_minutes`; keep it longer than your system cron interval), its row shows a warning and a **Process from this page** button. Only after the admin clicks it does the open page process the job, a few seconds per request, sharing the same lock as WP-Cron
  - One job runs at a time, oldest first, and a site-wide lock keeps two processes from running the queue together. Job progress is read straight from the database, never from the object cache. A job interrupted by deactivating the plugin continues once it is activated again
  - The same members as before are targeted (members of the current site on Multisite; for VIPs, the members whose VIP was active when the job was created)
  - *Selected users* still runs right away, as before
  - In our benchmark the background top-up handled 12,003 members at about 2.7 ms per member, and the notification to 12,003 members took less than one second of database work
  - Developers: for these two targets, `init_plugin_suite_user_engine_admin_send_notice` now fires once per batch, with the members of that batch. New actions `init_plugin_suite_user_engine_bulk_job_created` and `init_plugin_suite_user_engine_bulk_job_finished`, new filters `init_plugin_suite_user_engine_bulk_job_time_budget`, `init_plugin_suite_user_engine_bulk_job_batch_size`, `init_plugin_suite_user_engine_bulk_job_query_args` and `init_plugin_suite_user_engine_bulk_job_stall_minutes`
- Performance: avatars in comment lists. Each member's avatar reads the `iue_custom_avatar` user meta, which cost one query per commenter not yet in the cache. The users and user meta of the whole list are now loaded at once with `cache_users()`: 40 comments from 40 different members went from 82 queries to 5. This covers the comment template, the Recent Comments block/widget, the wp-admin Comments screen and the REST API
- Fixed: a Coin Top-up that included VIP members wrote their amount *with* the VIP Coin bonus into the transaction log, while the balance was changed without it (Top-up is a raw adjustment). The log now shows the amount actually applied
- Translations: POT file regenerated with WP-CLI, Vietnamese translation updated

= 1.7.0 – October 11, 2026 =
- New: **Display Name Change Limit** (Init User Engine → Settings → Display Name). Members can change their display name only once every N days, like TikTok (for example 7). Set to 0 for unlimited changes; 0 is the default, so nothing changes until you set it
  - Enforced everywhere a member can change their own display name, not only in the Edit Profile modal: `wp-admin/profile.php` (WordPress lets members change it freely there), the core REST API (`/wp/v2/users/me`), WooCommerce Account details, and any other plugin that calls `wp_update_user()` for the signed-in member. The check runs in `wp_pre_insert_user_data`, so a change made too early keeps the current display name
  - The Edit Profile modal, `wp-admin/profile.php` and WooCommerce Account details show a clear message with the date and time of the next allowed change. While the member has to wait, the display name field is disabled in the modal and on `profile.php`
  - The Edit Profile modal returns an error (`display_name_locked`, HTTP 403) and saves nothing when the display name cannot be changed yet; saving other fields with the same display name still works
  - Administrators (users who can edit other users) are not limited. A change an administrator makes to a member's display name does not start the member's waiting period
  - New **Unlock Display Name** button in the user overview box on the Edit User screen (next to *Ban Avatar Upload*). It shows until when the member is locked, and one click lets them change their display name again right away; their next change starts a new waiting period. Administrators only, protected by a nonce
  - The waiting period starts from the member's last own change (user meta `iue_display_name_changed_at`). Changes are recorded even while the limit is 0, so a limit set later applies right away
  - Race-safe: the check and the record run under the per-user lock, so two requests sent at the same moment cannot both change the display name
  - New filters `init_plugin_suite_user_engine_display_name_change_days` and `init_plugin_suite_user_engine_display_name_limit_applies`, new action `init_plugin_suite_user_engine_display_name_unlocked`, and new PHP helpers `init_plugin_suite_user_engine_is_display_name_locked( $user_id )`, `init_plugin_suite_user_engine_get_display_name_next_change( $user_id )` and `init_plugin_suite_user_engine_unlock_display_name( $user_id )`
  - `GET /wp-json/inituser/v1/profile/me` now includes `display_name_limit` (`days`, `locked`, `next_change`, `notice`)
- `POST /profile/update` now returns the display name actually saved
- Improved: the **Edit Profile** modal now opens right away with a loading spinner and fills in the form when the profile data arrives, like every other modal. Before, nothing happened until the data was loaded, then the modal appeared all at once. If the modal is closed (or another modal is opened) while loading, the late response is ignored; if loading fails, the error is shown inside the modal
- Fixed: a display name containing a double quote (`"`) broke the display name field in the Edit Profile modal. The value is now set safely
- Translations: POT file regenerated with WP-CLI, Vietnamese translation updated

View full changelog (all versions): [Init User Engine – Changelog](https://en.inithtml.com/plugin/init-user-engine/)

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
