=== Init User Engine – Gamified, Fast, Frontend-First ===
Contributors: brokensmile.2103
Tags: user, level, check-in, referral, vip
Requires at least: 5.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.6.7
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
- Per-user overview metabox on the WordPress profile/edit-user screen (level, wallet, VIP status, recent activity)

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

= 1.6.7 – October 3, 2026 =
- Performance (large sites): rebuilt the database indexes used by the Inbox, after a production slow query log showed "Mark All as Read" (`UPDATE … WHERE user_id = … AND status = 'unread'`) taking 2.5 seconds on a 1.2M-message Inbox
  - New composite index `user_status (user_id, status)`: the unread badge count, "Mark All as Read" and the Unread tab now read the index only instead of loading every message row of the user (about 181 → 3 pages read per unread count in our 1.25M-row benchmark)
  - New composite index `user_pinned_created (user_id, pinned, created_at)`: the Inbox list (`ORDER BY pinned DESC, created_at DESC`) is read straight from the index, no more filesort
  - Dropped single-column `user_id` indexes on the Inbox, Transaction log and EXP log tables. They were fully covered by composite indexes that start with `user_id`, so they only cost extra writes and disk space (every Coin/EXP change writes to these tables)
  - Safe on big databases: the index upgrade runs in the background through WP-Cron, never inside a page load (small and new sites are upgraded instantly). It uses online DDL (`ALGORITHM=INPLACE, LOCK=NONE`), so reads and writes keep working while indexes are built; it gives up after 3 seconds if it has to wait for a table lock, instead of making other queries queue behind it, then retries an hour later (up to 24 times); and a lock option makes sure only one process ever runs it. If WP-Cron is not running, the upgrade runs on the next admin page load after the event is more than an hour late
  - You can also run it yourself during quiet hours (replace `wp_` with your table prefix); the plugin detects the new indexes and marks the upgrade as done: `ALTER TABLE wp_init_user_engine_inbox ADD KEY user_status (user_id, status), ADD KEY user_pinned_created (user_id, pinned, created_at), DROP KEY user_id, ALGORITHM=INPLACE, LOCK=NONE;` then `ALTER TABLE wp_init_user_engine_transaction_log DROP KEY user_id;` and `ALTER TABLE wp_init_user_engine_exp_log DROP KEY user_id;`
- Performance: the weekly orphaned-Inbox cleanup no longer runs a single `DELETE … LEFT JOIN` over the whole Inbox table. That statement scanned and locked every row (even when nothing needed deleting), blocking every member's Inbox actions while it ran; in our benchmark a member's "Mark All as Read" waited 7.9 seconds. It now walks the distinct user IDs through the index, checks them against the users table in batches of 500 and deletes only orphaned messages, at most 1,000 rows per statement (0.45 s and no table-wide locks when there is nothing to delete)
- Performance: **Inbox Statistics → Delete All of This Type** now deletes in batches of 1,000 by primary key instead of one `DELETE … WHERE type = …`, which locked the whole table until done (members' Inbox actions waited 7 seconds while 200k messages were deleted in our benchmark; now under 0.1 second)
- Fixed: the orphaned-Inbox cleanup used `$wpdb->prefix . 'users'` as the users table, which does not exist on Multisite sub-sites (the users table is shared network-wide). It now uses `$wpdb->users`
- The new background event is cleared on deactivation along with the plugin's other cron events

= 1.6.6 – October 1, 2026 =
- Added **Lost Password in Modal** (Init User Engine → Settings, right below *Login After Register*). The "Forgot password?" link in the login modal now opens a lost password form inside the same modal instead of sending visitors to `wp-login.php`
  - Enabled by default. Turn it off if your site cannot send emails yet — the link then goes to the default `wp-login.php?action=lostpassword` page as before
  - **Custom Lost Password URL** (Custom Links) still takes priority: when it is set, the link always goes there, whatever this option says
  - Uses the native WordPress password reset flow: the form posts to `wp-login.php?action=lostpassword`, so the reset email, `lostpassword_form` / `lostpassword_post` hooks and the Turnstile "Lost Password Form" protection all keep working
  - After submitting, visitors land back on the same page with the modal open: a success notice in the login form when the email was sent, or an inline error in the lost password form (unknown user, empty field, email sending failure, captcha failure). Any other error keeps WordPress's default `wp-login.php` behaviour so its exact message is still shown
  - Template `lostpassword-form.php` can be overridden from `your-theme/init-user-engine/`; any element can also open it with `data-iue="lostpassword"`
  - Added filter `init_plugin_suite_user_engine_lostpassword_modal_enabled`
- Performance: the guest script no longer runs a `MutationObserver` on the whole `<body>` (it fired on every DOM change of the page just to attach the password toggle to the registration form, which is already in the page)
- Performance: the `get_avatar_url` filter no longer re-runs every plugin's `pre_get_avatar_data` callbacks for each avatar; it only applies Init User Engine's own avatar logic (the result is the same)
- Performance: the check-in countdown now saves its state on `pagehide` instead of `beforeunload`, so browsers can keep the page in the back/forward cache
- Fixed: the deactivation hook was registered against `includes/core.php` instead of the main plugin file, so it never ran and the plugin's cron events stayed scheduled after deactivation. All three recurring events are now cleared on deactivation (they are scheduled again automatically on reactivation)
- Fixed: the twice-daily cleanup compared transient expiry times (stored in UTC) with the site's local time, so on sites ahead of UTC it could delete registration captchas and rate-limit counters that had not expired yet
- Fixed: on sites using "Plain" permalinks (REST URL like `?rest_route=/inituser/v1`), requests that add their own query string were malformed: the registration math captcha never loaded, and Inbox, Transaction History and Experience Log pagination failed. Query strings are now appended with `&` when the REST URL already has one
- Updated `.pot`/`.po`/`.mo` translation files with the new strings introduced above (Vietnamese translation included), regenerated with WP-CLI

= 1.6.5 – September 24, 2026 =
- Added **Streak Recovery** (Init User Engine → Settings → Streak Recovery). When a member misses a few days, they can now spend Coin to keep their check-in streak instead of losing it
  - Two new settings: **Missed Days Allowed** (how many missed days can be covered, `0` = feature off) and **Coin Cost per Missed Day** (`0` = free). Off by default, so nothing changes on existing sites until an admin turns it on
  - Clicking **Check In** after a gap opens the plugin's standard modal showing the current streak, missed days, total cost and the member's balance, with two choices: keep the streak (pay), or reset it and check in anyway. Closing the modal does nothing. Missing more days than allowed simply resets the streak as before
  - Keeping a streak covers the missed days but does not count them as check-ins: the streak continues from where it was and today adds +1, so streak milestone rewards (7/30/90…) behave exactly as before
  - Every charge is written to the Coin transaction log as a deduction with a readable reason (e.g. "Kept check-in streak (2 missed days)"), shown in the member's Transaction History and in the admin user metabox
  - The frontend only ever shows the configured **Coin Label**, never a hardcoded "Coin"; all new frontend and error strings are label-aware
  - Safety: the price is re-checked on the server against what the member was shown (rejected if it changed), the balance is verified just before charging, and if the log entry cannot be written the Coin is handed back and the streak is left untouched
  - `POST /checkin` gained three optional JSON fields: `prompt_restore` (answer with status `restore_available` instead of checking in when the streak can be kept — nothing is changed), `restore_streak` and `restore_cost`. Clients that don't send them behave exactly as before
  - Added filters `init_plugin_suite_user_engine_streak_restore_max_days` and `init_plugin_suite_user_engine_streak_restore_cost`, and action `init_plugin_suite_user_engine_streak_restored`
  - New logic lives in `includes/streak-restore.php`
- Fixed: `POST /checkin` had no protection against overlapping requests, so two requests fired at the same moment could both pass the "already checked in today" test and reward the same day twice. Check-ins are now serialized per user with the same short-lived mutex pattern used by the exchange endpoints; a request that arrives while another is running gets a `409 busy` response
- Fixed: modals opened from the logged-in dashboard never played their open animation. The `fadeIn`/`slideDown` keyframes referenced by `#iue-modal` only existed in `style-guest.css`, which is not loaded for logged-in users. Added namespaced `iue-fade-in`/`iue-slide-down` keyframes to `style-user.css` (so a theme's own `fadeIn`/`slideDown` can no longer interfere) plus a `prefers-reduced-motion` opt-out
- Fixed: leaving the **Coin Label** or **Cash Label** field empty made the frontend (dashboard data, Referral benefits, exchange and VIP screens) display a blank currency name, and the VIP purchase inbox message could show an empty label. Both now fall back to "Coin" / "Cash" through the shared `init_plugin_suite_user_engine_get_coin_label()` / `..._get_cash_label()` helpers
- Fixed: after a failed check-in request (a server error, or a network failure) the check-in button used to switch to "Checked in" or fall back to an untranslated "Check-in" (it read a translation key that was never defined); it now returns to its normal, translated label and shows the error
- Fixed: the plugin's activation hook was registered against `includes/init.php` instead of the main plugin file, so it never ran and the database tables were only created later, on the first `admin_init`. It now runs on activation as intended (the `admin_init` version check stays as the upgrade path)
- Updated `.pot`/`.po`/`.mo` translation files with the new strings introduced above (Vietnamese translation included), regenerated with WP-CLI

= 1.6.4 – September 10, 2026 =
- Fixed: permanently deleting a user (from Users → All Users, bulk delete, or the REST Users endpoint) used to leave that user's **EXP history** and **Inbox messages** behind in the database forever, since nothing ever cleaned them up after the account itself was gone
  - Now hooked into WordPress core's `deleted_user` action (fires for both single-site `wp_delete_user()` and multisite `wpmu_delete_user()`, so one hook covers both): as soon as a user is permanently deleted, their EXP log rows and all Inbox messages are deleted right along with it
  - Coin/Cash **transaction log stays untouched on purpose** — wallet history feeds into site-wide statistics and reconciliation (total coin/cash ever issued, revenue reports, etc.), so it's intentionally kept even after the user account is gone
  - `iue_*` user meta (check-in streak, login bonus flags, profile bonus flag...) needed no extra handling — WordPress core already wipes all usermeta for the user before `deleted_user` fires
  - Added action hook `init_plugin_suite_user_engine_user_data_purged` (fires with the deleted user's ID right after cleanup) so other plugins/add-ons can hook in and clean up their own related data too
- No new user-facing strings in this release, so `.pot`/`.po` files are unchanged

= 1.6.3 – September 7, 2026 =
- Added a new **"Login After Register"** option (Init User Engine → Settings → General, disabled by default). When enabled, a successful registration through the plugin's REST endpoint (`/register`) immediately signs the new user in (`wp_set_auth_cookie()` + `wp_set_current_user()`, followed by the standard `wp_login` action for compatibility with other plugins/themes) instead of leaving them on the Login form
  - The frontend register form now reloads the page after a successful auto-login (instead of switching to the Login form) so the UI, nonces, and avatar immediately reflect the signed-in state
  - Left disabled by default so registration and login remain two explicit, separate steps unless a site owner opts in
- Fixed: a failed login attempt from the plugin's login modal (via `wp_login_form()`) used to fall back to WordPress's default behavior and redirect the visitor to `wp-login.php`, which felt out of place on sites that never expose that page. Failed logins now redirect back to the exact page the visitor was on, with the login modal automatically reopened and an inline error message (wrong username, wrong password, or missing fields)
  - Implemented via the `wp_login_failed` action combined with `wp_get_referer()`; only intervenes when the login attempt came from a normal frontend page, so logins made directly on `wp-login.php` or inside `wp-admin` keep WordPress's native behavior untouched
  - The redirect uses two short-lived query arguments (`iue_login_failed`, `iue_login_code`) that `assets/js/guest.js` reads once to open the modal and show the right message, then immediately strips from the URL via `history.replaceState()` so refreshing or sharing the link never re-shows the notice
- Refreshed `readme.txt` — the **Features** section was rewritten from scratch to match the plugin's actual current feature set (Turnstile/captcha, Require Login gate, Redeem/VIP Codes with CSV export, Inbox Statistics, admin user metabox, and more), several of which had been implemented in past releases but never documented here
- Updated `.pot`/`.po` translation files with the new strings introduced above (Vietnamese translation included)

= 1.6.2 – September 4, 2026 =
- Changed: a pinned Inbox message now only stays pinned to the top while it is **unread**. As soon as it's marked as read (single message or "mark all as read"), it automatically unpins and behaves like any other message, so read messages no longer take up space at the top of the Inbox modal
  - Implemented by clearing `pinned` back to `0` in the exact same `UPDATE` query that already sets a message to `read` (`init_plugin_suite_user_engine_mark_inbox_read()` and the "mark all as read" REST endpoint) — no extra query, no change to the Inbox `SELECT`/`ORDER BY`, and no new database index needed
  - Added a one-time backfill (runs once on upgrade, same mechanism already used for schema updates) that clears `pinned` for any pre-existing message that was already pinned **and** read before this update
  - Added a client-side safety net in the Inbox renderer so the pin icon never shows on a read message, even for the brief moment before the backfill above has run
- Updated `.pot`/`.po` translation files (no new strings were needed for this change; existing ones were already sufficient)

= 1.6.1 – September 4, 2026 =
- Added **Export CSV** for both code list screens (User Engine → Redeem Codes / VIP Codes)
  - New "Export CSV" button next to the "Existing Redeem Codes" / "Existing VIP Codes" heading, exporting the **entire list** (every page, not just the current one) as a downloadable `.csv` file
  - Redeem Codes CSV columns: ID, Code, Type, Locked User ID, Locked Username, Coin Amount, Cash Amount, Max Uses, Used Count, Status, Valid From, Valid To, Created By, Created At, Updated At
  - VIP Codes CSV columns: same as above, with VIP Days in place of Coin/Cash Amount
  - Data is streamed straight to the browser in batches of 500 rows so memory usage stays low even on sites with a large number of codes
  - File is written with a UTF-8 BOM so it opens correctly in Excel, and every cell is sanitized against CSV/formula injection (values starting with `=`, `+`, `-`, `@`, or a tab are safely prefixed)
  - Gated behind the `manage_options` capability plus a dedicated nonce per screen (`iue_redeem_code_export_csv` / `iue_vip_code_export_csv`)
- Updated `.pot`/`.po` translation files with the new strings introduced above (Vietnamese translation included); `.mo` not rebuilt as part of this change

= 1.6.0 – September 2, 2026 =
- Added a new **"Require Login to Access Site"** option (Init User Engine → Settings → General). When enabled, visitors who are not logged in no longer see the site's actual content on any page
  - Instead, they see a blank page in the plugin's theme color with the built-in login modal opened automatically, so they can sign in without leaving the page
  - Implemented via `template_redirect`, so `wp_head()`/`wp_footer()` still run in full — the plugin's own login modal, and every other theme/plugin hook attached to those actions, keeps working normally
  - REST API, AJAX, cron, feed, and robots.txt requests are always excluded and are never blocked by this option
  - Added filter `init_plugin_suite_user_engine_require_login_bypass` so other plugins/themes can exclude specific requests (e.g. a payment callback URL) from the gate
  - New dedicated `assets/css/require-login.css` and `assets/js/require-login.js` files render the gate's background and auto-open the login modal, kept separate from PHP output for coding-standards compliance
- Fixed: the frontend guest script (`guest.js`) only exposed `window.openLoginModal` (and wired up Escape-to-close, Alt+L, and hash-triggered opening) when an avatar element was present on the page. Pages without the avatar shortcode/widget — including the new Require Login gate — could not open the login modal at all. The avatar element is now optional; the modal and its triggers work on any page as long as the modal itself is rendered (always the case via `wp_footer`)
- Updated `.pot`/`.po` translation files with the new strings introduced above (Vietnamese translation included); `.mo` not rebuilt as part of this change

View full changelog (all versions): [Init User Engine – Changelog](https://en.inithtml.com/plugin/init-user-engine/)

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
