# Init User Engine – Gamified, Fast, Frontend-First

> Add a modern, gamified user system to WordPress with EXP levels, Coin/Cash wallet, VIP, referral, and full JS modals – all powered by REST API.

**Pure JavaScript. Real-time REST API. Built for frontend-first WordPress.**

[![Version](https://img.shields.io/badge/stable-v1.3.7-blue.svg)](https://wordpress.org/plugins/init-user-engine/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![Made with ❤️ in HCMC](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F%20in%20HCMC-blue)

## Overview

Init User Engine is a gamified user module built from scratch for frontend-first WordPress sites. Everything runs via REST API and Vanilla JS — no jQuery, no PHP-based forms, no bloat.

You get full control over user interactions: check-in, VIP purchase, Coin/EXP rewards, inbox notifications, referral tracking — all in one slick modal dashboard.

## Features

- Shortcode `[init_user_engine]` to display avatar + modal dashboard
- EXP & level system with streaks, milestones, and bonuses
- Coin & Cash wallet with transaction history
- Daily check-in + online time reward
- VIP membership system with coin-based purchases
- Referral system with cookie-based tracking
- Built-in inbox system (custom DB table)
- Custom avatar upload & preview
- Admin panel to send targeted notifications
- Redeem Code / Gift Code system with auto rewards (Coin/Cash)
- REST API for all user actions – no reloads, no delays
- Fully i18n-ready with JS-based validation & messages
- Lightweight, modern UI – no jQuery, no dependencies

## Shortcode

### `[init_user_engine]`

Outputs the avatar button and attaches the full modal dashboard.

## REST API Endpoints

Base: `/wp-json/inituser/v1/`

- `POST /register` – Create new user account  
- `POST /checkin` – Daily check-in  
- `POST /claim-reward` – Claim online reward  
- `GET  /transactions` – View wallet logs  
- `GET  /exp-log` – View EXP history  
- `GET  /inbox` – Fetch inbox messages  
- `POST /inbox/mark-read` – Mark a message as read  
- `POST /inbox/delete` – Delete a message  
- `POST /vip/purchase` – Buy VIP membership  
- `POST /exchange` – Convert Cash to Coin  
- `GET  /referral-log` – Get referral history  
- `POST /avatar` – Upload avatar  
- `POST /avatar/remove` – Revert to default avatar  
- `GET  /profile/me` – Get current user profile  
- `POST /profile/update` – Update profile information  
- `GET  /daily-tasks` – Get list of completed daily tasks and rewards
- `POST /redeem-code` – Redeem gift code → returns `{ success, message, Coin, Cash }`

## Developer Hooks

### Filters

- `init_plugin_suite_user_engine_localized_data` – Modify frontend JS data  
- `init_plugin_suite_user_engine_exp_required` – Modify EXP required per level  
- `init_plugin_suite_user_engine_vip_prices` – Modify VIP package prices  
- `init_plugin_suite_user_engine_referral_rewards` – Modify referral rewards  
- `init_plugin_suite_user_engine_calculated_coin_amount` – Modify Coin reward before apply  
- `init_plugin_suite_user_engine_calculated_exp_amount` – Modify EXP reward before apply  
- `init_plugin_suite_user_engine_format_inbox` – Modify formatted inbox data  
- `init_plugin_suite_user_engine_render_level_badge` – Customize level badge HTML  
- `init_plugin_suite_user_engine_validate_register_fields` – Validate or modify registration fields before account creation  
- `init_plugin_suite_user_engine_daily_tasks` – Add or modify daily task list and logic  
- `init_plugin_suite_user_engine_captcha_bank` – Extend the captcha question bank with custom items  
- `init_plugin_suite_user_engine_format_log_message` – Customize transaction log message display with access to entry data, source, type, and amount  

### Actions

- `init_plugin_suite_user_engine_level_up` – When user levels up  
- `init_plugin_suite_user_engine_exp_added` – After EXP is added  
- `init_plugin_suite_user_engine_transaction_logged` – After Coin/Cash is logged  
- `init_plugin_suite_user_engine_inbox_inserted` – After new inbox message is created  
- `init_plugin_suite_user_engine_vip_purchased` – After VIP is purchased  
- `init_plugin_suite_user_engine_after_register` – After successful user registration  
- `init_plugin_suite_user_engine_coin_changed` – After user's Coin balance changes  
- `init_plugin_suite_user_engine_cash_changed` – After user's Cash balance changes  

## Installation

1. Upload to `/wp-content/plugins/init-user-engine`  
2. Activate via WordPress admin  
3. Add `[init_user_engine]` anywhere to get started  
4. Done — the dashboard and all modals load automatically

## License

GPLv2 or later — open-source, extensible, built for performance.

## Part of Init Plugin Suite

Init User Engine is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a collection of fast, no-bloat plugins built for modern WordPress developers.
