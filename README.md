# Init User Engine – Gamified, Fast, Frontend-First

> Add a modern, gamified user system to WordPress with EXP levels, Coin/Cash wallet, check-in streaks, VIP, referral, and full JS modals – all powered by REST API.

**Pure JavaScript. Real-time REST API. Built for frontend-first WordPress.**

[![Version](https://img.shields.io/badge/stable-v1.6.5-blue.svg)](https://wordpress.org/plugins/init-user-engine/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![Made with ❤️ in HCMC](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F%20in%20HCMC-blue)

## Overview

Init User Engine is a gamified user module built from scratch for frontend-first WordPress sites. Everything runs via REST API and Vanilla JS — no jQuery, no PHP-based forms, no bloat.

You get full control over user interactions: check-in, VIP purchase, Coin/EXP rewards, inbox notifications, referral tracking — all in one slick modal dashboard.

## What's New in 1.6.5: Streak Recovery

Members who miss a few days can now **spend Coin to keep their check-in streak** instead of losing it.

- Two settings under **Init User Engine → Settings → Streak Recovery**: *Missed Days Allowed* (`0` = off) and *Coin Cost per Missed Day* (`0` = free). Off by default, so existing sites are unaffected until you enable it
- Clicking **Check In** after a gap opens the standard modal with the current streak, missed days, cost, and balance — keep the streak (pay) or reset it and check in anyway
- Missed days are covered but **not counted as check-ins**: the streak continues and today adds +1, so streak milestone rewards behave exactly as before
- Every charge is logged in the Coin transaction history with a readable reason
- The price is re-checked on the server, the balance is verified right before charging, and the Coin is refunded automatically if the log entry can't be written
- The frontend only shows your configured **Coin Label** — never a hardcoded "Coin"

Also in this release: check-ins are now serialized per user (no double rewards from overlapping requests), modals opened by logged-in users now animate properly, empty Coin/Cash labels fall back to "Coin"/"Cash", and the activation hook now runs on activation.

## Features

- Shortcode `[init_user_engine]` to display avatar + modal dashboard
- Frontend login & registration modal with Cloudflare Turnstile (or built-in math captcha) protection
- Optional "Login After Register" to sign users in automatically right after they create an account (disabled by default)
- Failed logins reopen the login modal on the current page with an inline error message, instead of redirecting to `wp-login.php`
- Optional protection of the native WordPress login/register/lost-password forms using the same Turnstile setup
- Optional "Require Login to Access Site" mode that gates the entire frontend behind the login modal
- Option to temporarily disable new registrations, plus custom Register / Lost Password URLs
- EXP & level system with streaks, milestones, and bonuses
- Coin & Cash dual-wallet system with transaction history, custom currency labels, and a configurable exchange rate between the two currencies
- Manual Coin/Cash top-up and deduction tool for selected users, active VIPs, or all members
- Daily check-in with streak milestones, an online-time reward, and optional Coin-powered streak recovery
- Daily Tasks with per-task rewards
- Automatic rewards for registration, daily login, comments (with a daily cap), first published post, and completed WooCommerce orders
- VIP membership system with Coin-based, Cash-based, or combined purchases, multiple duration tiers, a lifetime option, and VIP bonuses
- Referral system with cookie-based tracking and separate rewards for referrer and new user
- Redeem Code / Gift Code system with auto rewards (Coin/Cash), plus a dedicated VIP Codes module — both exportable to CSV from wp-admin
- Built-in inbox system (custom DB table) with pinned/expiring messages and an Inbox Statistics dashboard
- Profile editing: display name, bio, social links, gender, and date of birth
- Custom avatar upload & preview, with configurable upload policy (everyone / VIP only / disabled) and max file size; optional Gravatar disable
- Admin panel to send targeted notifications, plus a per-user overview metabox on the profile screen
- Automatic cleanup of a user's EXP log and Inbox when the account is permanently deleted (Coin/Cash transaction history is intentionally kept for reconciliation)
- Theme override support: copy any template to `your-theme/init-user-engine/`
- REST API for all user actions – no reloads, no delays
- Fully i18n-ready with JS-based validation & messages (Vietnamese translation included)
- Lightweight, modern UI – no jQuery, no dependencies

## Shortcode

### `[init_user_engine]`

Outputs the avatar button and attaches the full modal dashboard.

## REST API Endpoints

Base: `/wp-json/inituser/v1/`

- `POST /register` – Create new user account  
- `GET  /captcha` – Get a fallback math captcha (used when Turnstile is not configured)  
- `POST /checkin` – Daily check-in (see [Streak Recovery flow](#streak-recovery-flow))  
- `POST /claim-reward` – Claim online reward  
- `GET  /transactions` – View wallet logs  
- `GET  /exp-log` – View EXP history  
- `GET  /daily-tasks` – Get list of completed daily tasks and rewards  
- `GET  /inbox` – Fetch inbox messages  
- `POST /inbox/mark-read` – Mark a message as read  
- `POST /inbox/mark-all-read` – Mark all messages as read  
- `POST /inbox/delete` – Delete a message  
- `POST /inbox/delete-all` – Delete all messages  
- `POST /vip/purchase` – Buy VIP membership  
- `POST /exchange` – Convert Cash to Coin  
- `POST /exchange-reverse` – Convert Coin to Cash  
- `GET  /referral-log` – Get referral history  
- `POST /avatar` – Upload avatar  
- `POST /avatar/remove` – Revert to default avatar  
- `GET  /profile/me` – Get current user profile  
- `POST /profile/update` – Update profile information  
- `POST /redeem-code` – Redeem gift code → returns `{ success, message, Coin, Cash }`  
- `POST /redeem-vip-code` – Redeem a VIP code → grants VIP duration

### Streak Recovery flow

`POST /checkin` accepts three optional JSON fields. Clients that don't send them behave exactly as before.

| Field | Type | Purpose |
| --- | --- | --- |
| `prompt_restore` | bool | If the streak can be kept, answer with `status: "restore_available"` (streak, missed days, cost, balance) instead of checking in. Nothing is changed or charged |
| `restore_streak` | bool | Pay the Coin cost for the missed days and keep the streak |
| `restore_cost` | int | The price the user was shown; the request is rejected with `409 restore_changed` if it no longer matches |

Other responses you may see: `409 restore_unavailable` (nothing to restore), `400 not_enough_coin`, `409 busy` (another check-in is already running for this user). A successful response includes `streak_restored` (bool).

## Developer Hooks

### Filters

- `init_plugin_suite_user_engine_localized_data` – Modify frontend JS data  
- `init_plugin_suite_user_engine_exp_required` – Modify EXP required per level  
- `init_plugin_suite_user_engine_vip_prices` – Modify VIP package prices (Coin)  
- `init_plugin_suite_user_engine_vip_cash_prices` – Modify VIP package prices (Cash)  
- `init_plugin_suite_user_engine_referral_rewards` – Modify referral rewards  
- `init_plugin_suite_user_engine_calculated_coin_amount` – Modify Coin reward before apply  
- `init_plugin_suite_user_engine_calculated_cash_amount` – Modify Cash reward before apply  
- `init_plugin_suite_user_engine_calculated_exp_amount` – Modify EXP reward before apply  
- `init_plugin_suite_user_engine_user_register_rewards` – Modify the rewards given on registration  
- `init_plugin_suite_user_engine_daily_login_rewards` – Modify the daily login rewards  
- `init_plugin_suite_user_engine_publish_post_rewards` – Modify the rewards for a first published post  
- `init_plugin_suite_user_engine_update_profile_rewards` – Modify the rewards for a profile update  
- `init_plugin_suite_user_engine_checkin_milestones` – Set milestone streak days  
- `init_plugin_suite_user_engine_online_minutes` – Modify required online minutes after check-in  
- `init_plugin_suite_user_engine_streak_restore_max_days` – Modify how many missed check-in days a user may cover to keep their streak (return `0` to disable it for that user)  
- `init_plugin_suite_user_engine_streak_restore_cost` – Modify the Coin cost of keeping a streak (receives total cost, missed days, and user ID)  
- `init_plugin_suite_user_engine_exchange_min_cash` / `_max_cash` – Set per-exchange limits for Cash → Coin (`0` = unlimited for max)  
- `init_plugin_suite_user_engine_exchange_min_coin` / `_max_coin` – Set per-exchange limits for Coin → Cash (`0` = unlimited for max)  
- `init_plugin_suite_user_engine_format_inbox` – Modify formatted inbox data  
- `init_plugin_suite_user_engine_inbox_insert_data` – Modify inbox data before inserting into database  
- `init_plugin_suite_user_engine_inbox_bulk_chunk_size` – Change the batch size used for bulk inbox inserts (default 500)  
- `init_plugin_suite_user_engine_inbox_bulk_row_data` – Adjust each row before a bulk inbox insert  
- `init_plugin_suite_user_engine_render_level_badge` – Customize level badge HTML  
- `init_plugin_suite_user_engine_validate_register_fields` – Validate or modify registration fields before account creation  
- `init_plugin_suite_user_engine_daily_tasks` – Add or modify daily task list and logic  
- `init_plugin_suite_user_engine_captcha_bank` – Extend the captcha question bank with custom items  
- `init_plugin_suite_user_engine_format_log_message` – Customize transaction log message display with access to entry data, source, type, and amount  
- `init_plugin_suite_user_engine_exp_log_label` – Customize the label shown for an EXP log entry  
- `init_plugin_suite_user_engine_should_keep_original` – Override decision to keep original uploaded avatar (GIF or other formats)  
- `init_plugin_suite_user_engine_vip_expire_soon_threshold` – Modify the threshold (in seconds) used to determine when VIP is considered close to expiration  
- `init_plugin_suite_user_engine_body_vip_classes` – Add, remove, or modify VIP-related CSS classes applied to the `<body>` element  
- `init_plugin_suite_user_engine_theme_colors` – Modify theme color system (primary and active colors)  
- `init_plugin_suite_user_engine_require_login_bypass` – Exclude a specific request from the "Require Login to Access Site" gate  
- `init_plugin_suite_user_engine_admin_user_extra_stats` – Add extra stat items to the per-user overview metabox in wp-admin

### Actions

- `init_plugin_suite_user_engine_level_up` – When user levels up  
- `init_plugin_suite_user_engine_exp_added` – After EXP is added  
- `init_plugin_suite_user_engine_transaction_logged` – After Coin/Cash is logged  
- `init_plugin_suite_user_engine_exp_logged` – After EXP log is recorded  
- `init_plugin_suite_user_engine_inbox_inserted` – After new inbox message is created  
- `init_plugin_suite_user_engine_inbox_bulk_inserted` – After a batch of inbox messages is inserted  
- `init_plugin_suite_user_engine_referral_completed` – When referral is completed  
- `init_plugin_suite_user_engine_after_checkin` – After user check-in  
- `init_plugin_suite_user_engine_streak_restored` – After a user paid Coin to keep their check-in streak (user ID, missed days, cost, new streak)  
- `init_plugin_suite_user_engine_after_claim_reward` – After user claims reward  
- `init_plugin_suite_user_engine_after_exchange` – After a Cash → Coin exchange  
- `init_plugin_suite_user_engine_after_exchange_reverse` – After a Coin → Cash exchange  
- `init_plugin_suite_user_engine_vip_purchased` – After VIP is purchased  
- `init_plugin_suite_user_engine_vip_removed` – After an admin removes a user's VIP  
- `init_plugin_suite_user_engine_avatar_ban_toggled` – After an admin bans or allows a user's avatar uploads  
- `init_plugin_suite_user_engine_after_register` – After successful user registration  
- `init_plugin_suite_user_engine_after_update_profile` – After a user updates their profile  
- `init_plugin_suite_user_engine_user_data_purged` – After a deleted user's EXP log and Inbox have been cleaned up (fires with the user ID)  
- `init_plugin_suite_user_engine_add_exp` – Triggered when adding EXP via hook  
- `init_plugin_suite_user_engine_add_coin` – Triggered when adding Coin via hook  
- `init_plugin_suite_user_engine_coin_changed` – After user's Coin balance changes  
- `init_plugin_suite_user_engine_cash_changed` – After user's Cash balance changes

### JavaScript events

- `iue:checkin:success` – Fired on `document` after a successful check-in (`event.detail` holds the response, including `streak` and `streak_restored`)  
- `iue:reward:claimed` – After the online reward is claimed  
- `iue:level:up` – After a level-up

## Installation

1. Upload to `/wp-content/plugins/init-user-engine`  
2. Activate via WordPress admin  
3. Add `[init_user_engine]` anywhere to get started  
4. Done — the dashboard and all modals load automatically  
5. Optional: enable streak recovery in **Init User Engine → Settings → Streak Recovery**

## License

GPLv2 or later — open-source, extensible, built for performance.

## Part of Init Plugin Suite

Init User Engine is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a collection of fast, no-bloat plugins built for modern WordPress developers.
