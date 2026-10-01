=== Init User Engine – Gamified, Fast, Frontend-First ===
Contributors: brokensmile.2103
Tags: user, level, check-in, referral, vip
Requires at least: 5.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.6.6
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

= 1.5.9 – August 29, 2026 =
- Changed: "Send Notification" (Init User Engine → Send Notification) now uses a dedicated **admin** message type instead of reusing **system**, which was also used internally by several unrelated automated notices (VIP removed, redeem code success, VIP code success). Admin-sent notices and automated system notices are now cleanly separated
  - The "System" filter tab in the user-facing Inbox still includes admin-sent messages, same as before
  - Previously sent messages (type `system`) are not migrated and keep displaying normally in the Inbox; only newly sent notifications use the new `admin` type
- Fixed: **"Pin this message"** on the Send Notification screen had no visible effect beyond storing a flag — pinned messages now always float to the top of the Inbox list, ahead of unpinned messages, across every filter tab
- Fixed: the **"Expire At"** field on Send Notification was saved to the database but never actually read back anywhere — it had no effect at all, on any message
  - Clarified and scoped its behavior to work together with the pin fix above: **Expire At now only applies to pinned messages** — once the time passes, the message is automatically unpinned (it remains fully visible in the Inbox as a regular message, nothing is hidden or deleted). Unpinned messages ignore this field entirely and never expire
  - Added an hourly cron job (`init_plugin_suite_user_engine_unpin_expired_inbox`) that clears the `pinned` flag on messages past their `expire_at`; it only ever updates that one column, never deletes or alters message content
  - Added inline descriptions on the Send Notification screen clarifying this scope for both the "Pin this message" checkbox and the "Expire At" field
- Fixed: the **Date Range** filter (Last 7/30/90 Days, All Time) on the Inbox Statistics page (Users → Init User Engine → Inbox Statistics) only affected the "Daily Activity" chart; every other number on the page (Total/Unread/Pinned Messages, Total Recipients, Message Types, Priority Levels, Top Recipients, Active Recipients, Peak Day) silently ignored it and always showed all-time (or hardcoded 30/90-day) figures
  - All of the above now correctly scope to the selected range
  - "Active Recipients" and "Peak Day" no longer use a hardcoded 30/90-day window — they follow the selected range like everything else; label updated from "Active Recipients (30d)" to "Active Recipients" to match
  - "Sent Today", "This Week", and "This Month" remain fixed calendar anchors by design, independent of the Date Range selector
- Added a short-lived (5 min) cache for the Inbox Statistics page, shared across all its stat sections, to avoid re-running its ~10 aggregate queries on every page load/range change
- Added a database index on `created_at` for the inbox table to keep the now range-aware statistics queries fast on large tables. Applied automatically and safely via `dbDelta()` on next admin page load — existing data is untouched, no manual DB work or reinstall needed
- Updated `.pot`/`.po` translation files for the above: added `admin` / `Active Recipients` / the two new Pin & Expire At description strings, removed the now-unused `system` (as a standalone label) / `Active Recipients (30d)` strings, and corrected stale source-line references throughout

= 1.5.8 – August 22, 2026 =
- Fixed: several user-facing notifications and REST API error messages ignored the admin-configured **Coin Label** / **Cash Label** and always displayed the hardcoded English words "Coin"/"Cash" regardless of the custom label set in Settings → Currency Labels
  - Affected: level-up bonus notice, sign-up/order/review reward notices, all Coin ⇄ Cash exchange error messages (invalid amount, min/max limit, insufficient balance, zero-result, update failed), and VIP purchase error messages (wrong currency, insufficient balance)
  - All of the above now consistently use the configured label, matching the behavior already used by Redeem Codes, Top-up, and the VIP purchase success message
  - Added shared helpers `init_plugin_suite_user_engine_get_coin_label()` and `init_plugin_suite_user_engine_get_cash_label()` in `includes/utils.php`
- Fixed: frontend JS (`member.js`) referenced an `exchange_insufficient_coin` translation string for the Coin→Cash exchange screen that was never localized from PHP, silently falling back to a hardcoded, non-translatable "Not enough Coin." string; now properly localized and label-aware
- Removed a leftover duplicate/dead array key in the frontend localization data (`exchange_insufficient`) that was immediately overwritten and never actually used
- Updated `.pot`/`.po` translation files to match: merged duplicate Coin/Cash message pairs into shared, label-aware strings; Vietnamese translation now consistently uses "Coin" instead of the previous literal "xu"/"đồng xu" wording throughout

= 1.5.7 – August 16, 2026 =
- Added **Date of Birth** field to the frontend Edit Profile modal, stored per-user instead of a raw age number so it stays accurate over time without users needing to re-enter it
  - New REST field `dob` on both `GET /profile/me` and `POST /profile/update`
  - Server-side validation rejects malformed dates, future dates, and dates older than 120 years; invalid input is rejected before any other profile field is saved
- Added `init_plugin_suite_user_engine_get_age( $user_id )` helper — computes a user's current age from their stored date of birth, returns `0` if not set
- Added new i18n strings for the Date of Birth field and its validation messages

= 1.5.6 – August 16, 2026 =
- Added **VIP Codes** — a dedicated code system for granting VIP membership days, alongside the existing Coin/Cash Redeem Codes
  - New admin page (User Engine → VIP Codes) with the same workflow as Redeem Codes: single/batch, multi-use, and user-locked codes, usage history, disable/delete
  - New REST endpoint `POST /redeem-vip-code`, using the same transaction-locked, race-condition-safe redemption flow as Redeem Codes
  - New "Redeem VIP Code" option in the frontend user dashboard, with its own modal
- Added **Disable VIP Stacking** setting
  - When enabled, a user with an active VIP can't purchase or redeem another VIP package/code until the current one expires, instead of extending it
  - Enforced consistently across both Coin/Cash purchase and VIP Code redemption, with a row-level DB lock on redemption to prevent two simultaneous requests from both stacking VIP before either write completes
- Added **Disable VIP Purchase** setting
  - Completely turns off VIP purchasing/activation for all users; hides the purchase UI and the "Redeem VIP Code" menu item, and blocks both the purchase and VIP Code redeem endpoints
  - Existing active VIP members are unaffected — only new activations are blocked
- Changed: VIP Lifetime package now stores 99999 days instead of 9999 (previously only ~27 years, which confused users); all existing lifetime-detection logic remains compatible with old data
- Added new i18n strings for VIP Codes, VIP stacking, and VIP purchase-disabled notices

= 1.5.5 – August 11, 2026 =
- Added **Cloudflare Turnstile protection for WordPress's default forms**
  - Extends the same Turnstile widget to WordPress's native Login, Registration, and Lost Password forms (`wp-login.php`), not just this plugin's own registration endpoint
  - Three new toggles under Cloudflare Turnstile → Protect Default WordPress Forms: Login Form, Registration Form, Lost Password Form
  - Login protection covers both the native `wp-login.php` page and the plugin's own login modal, since both submit through the same WordPress login flow
  - Registration protection applies to WordPress's native `wp-login.php?action=register` page (only relevant when "Anyone can register" is enabled), independent from this plugin's own registration form/endpoint
  - All three require both Turnstile keys to be set and only take effect when "Disable Captcha" is off, same as the existing registration captcha
  - Turnstile script for the login modal only loads once the modal is actually opened, matching the existing lazy-load behavior of the registration widget
- Added **Test API button** for Cloudflare Turnstile
  - Verifies the Secret Key against Cloudflare directly from the Settings page, before saving
  - Site Key can only be fully confirmed once the widget actually renders in the browser (e.g. on the registration form)
- Added new i18n strings for the Turnstile form protection settings and the Test API button

= 1.5.4 – August 11, 2026 =
- Fixed: Admin User Overview metabox (Recent Transactions / Recent EXP-related data) stopped showing new activity after the meta → custom table migration (v1.5.x). Root cause: the transaction/EXP log reader queried the **oldest** 100 entries (`ORDER BY logged_at ASC LIMIT 100`) instead of the most recent ones, so entries logged after a user passed 100 total transactions never appeared. Now correctly fetches and displays the latest 100 entries
- Improved: Admin User Overview metabox now performs a single aggregate query for inbox stats (total / last 7 days / last message time) instead of 3 separate `COUNT`/`MAX` queries, reducing database round-trips on profile page loads
- Improved: Minor cleanup of redundant array processing when rendering the Recent Transactions list
- `Tested up to: 7.1`

= 1.5.3 – July 29, 2026 =
- Added **two-way currency exchange** between Cash and Coin
  - New REST endpoint `POST /exchange-reverse` to convert Coin → Cash
  - Exchange modal now supports toggling between Cash → Coin and Coin → Cash
  - Added independent exchange rate settings for both directions
  - Rate limiting, idempotency, and mutex locks applied to both endpoints
- Added **VIP bonus for Cash**
  - VIP users now receive configurable bonus Cash (%) on all Cash additions
  - Aligns with existing VIP bonus behavior for Coin and EXP
- Added **VIP purchase by Cash**
  - VIP packages can now be priced and purchased using Cash instead of Coin
  - New setting to choose payment currency: Coin only, Cash only, or Both
  - When set to Both, users can toggle between Coin and Cash in the purchase modal
  - VIP purchase log now records the currency used for each transaction
- Added new i18n strings for exchange direction, VIP currency selection, and Cash-related notifications

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
