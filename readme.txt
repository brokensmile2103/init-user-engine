=== Init User Engine – Gamified, Fast, Frontend-First ===
Contributors: brokensmile.2103
Tags: user, level, check-in, referral, vip
Requires at least: 5.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.4
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
