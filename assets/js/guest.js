(function () {
	// Aff tracking (7 ngày)
	const affParam = new URLSearchParams(window.location.search).get('aff');
	if (affParam) {
		document.cookie = `iue_ref=${affParam}; path=/; max-age=${60 * 60 * 24 * 7}`;
	}
})();

// === Late-load Cloudflare Turnstile only when needed ===
(function () {
	let loading = false, loaded = false, queue = [];
	window.iueLoadTurnstile = function (cb) {
		if (loaded && typeof cb === 'function') return cb();
		if (typeof cb === 'function') queue.push(cb);
		if (loading) return;
		loading = true;
		const s = document.createElement('script');
		s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=iueTurnstileOnload&render=explicit';
		s.async = true; s.defer = true;
		document.head.appendChild(s);
		window.iueTurnstileOnload = function () {
			loaded = true;
			const cbs = queue.slice(); queue = [];
			cbs.forEach(fn => { try { fn(); } catch (e) {} });
		};
	};
})();
// elId / widgetVarName có default để KHÔNG phá vỡ các lời gọi cũ (register vẫn dùng 'iue-turnstile' / '_iueWidgetId')
window.iueRenderTurnstile = function (elId, widgetVarName) {
	elId = elId || 'iue-turnstile';
	widgetVarName = widgetVarName || '_iueWidgetId';
	const el = document.getElementById(elId); // placeholder trong template
	if (!el || typeof turnstile === 'undefined' || el.dataset.rendered === '1') return;
	const sitekey = el.dataset.sitekey;
	const theme   = el.dataset.theme || 'auto';
	window[widgetVarName] = turnstile.render(el, { sitekey, theme });
	el.dataset.rendered = '1';
};

document.addEventListener('DOMContentLoaded', function () {
	const avatar  = document.getElementById('init-user-engine-avatar');
	const modal   = document.getElementById('init-user-engine-login-modal');
	const closeBtn= document.getElementById('init-user-engine-modal-close');

	// Modal và nút đóng luôn được render qua wp_footer nên bắt buộc phải có.
	// Avatar là tùy chọn: một số trang (vd. trang yêu cầu đăng nhập) không có avatar để bấm.
	if (!modal || !closeBtn) return;

	// SVG (giữ nguyên nếu cần dùng chỗ khác)
	const svgEye = `<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><circle fill="none" stroke="currentColor" cx="10" cy="10" r="3.45"></circle><path fill="none" stroke="currentColor" d="m19.5,10c-2.4,3.66-5.26,7-9.5,7h0,0,0c-4.24,0-7.1-3.34-9.49-7C2.89,6.34,5.75,3,9.99,3h0,0,0c4.25,0,7.11,3.34,9.5,7Z"></path></svg>`;
	const svgEyeOff = `<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path fill="none" stroke="currentColor" d="m7.56,7.56c.62-.62,1.49-1.01,2.44-1.01,1.91,0,3.45,1.54,3.45,3.45,0,.95-.39,1.82-1.01,2.44"></path><path fill="none" stroke="currentColor" d="m19.5,10c-2.4,3.66-5.26,7-9.5,7h0,0,0c-4.24,0-7.1-3.34-9.49-7C2.89,6.34,5.75,3,9.99,3h0,0,0c4.25,0,7.11,3.34,9.5,7Z"></path><line fill="none" stroke="currentColor" x1="2.5" y1="2.5" x2="17.5" y2="17.5"></line></svg>`;

	function openLoginModal() {
		modal.classList.add('open');
		document.body.classList.add('init-user-engine-modal-open');
		const user = document.getElementById('user_login');
		const pass = document.getElementById('user_pass');
		const i18n = window.InitUserEngineData?.i18n || {};
		if (user) {
			user.placeholder = i18n.placeholder_username || 'Username or Email Address';
			user.focus();
		}
		if (pass) {
			pass.placeholder = i18n.placeholder_password || 'Password';
		}

		// LAZY init Turnstile cho form Login — chỉ tải khi modal thực sự mở
		const loginTurnstileEl = document.getElementById('iue-turnstile-login');
		if (loginTurnstileEl) {
			iueLoadTurnstile(() => iueRenderTurnstile('iue-turnstile-login', '_iueWidgetIdLogin'));
		}
	}
	function closeModal() {
		modal.classList.remove('open');
		document.body.classList.remove('init-user-engine-modal-open');
	}

	// Trang "Yêu cầu đăng nhập" (Require Login Gate): không cho phép tắt modal
	// bằng bất kỳ cách nào, để bắt buộc người dùng phải đăng nhập.
	const isRequireLoginGate = document.body.classList.contains('iue-require-login');

	if (avatar) {
		avatar.addEventListener('click', function (e) { e.preventDefault(); openLoginModal(); });
	}
	if (!isRequireLoginGate) {
		closeBtn.addEventListener('click', closeModal);
	}

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			if (!isRequireLoginGate) closeModal();
			return;
		}
		if (e.altKey && e.key.toLowerCase() === 'l') {
			if (!modal.classList.contains('open')) openLoginModal();
		}
	});

	modal.addEventListener('click', function (e) {
		if (isRequireLoginGate) return;
		const content = modal.querySelector('.iue-content');
		if (content && !content.contains(e.target)) closeModal();
	});

	// Trigger qua hash
	if (window.location.hash === '#init-user-engine') openLoginModal();

	// Tự mở modal + hiển thị thông báo khi đăng nhập sai mật khẩu/tài khoản.
	// Server (includes/hooks.php) đã chuyển hướng về đúng trang này kèm 2 tham số
	// tạm thời bên dưới thay vì văng sang wp-login.php như mặc định của WordPress.
	(function handleFailedLoginRedirect() {
		const params = new URLSearchParams(window.location.search);
		if (params.get('iue_login_failed') !== '1') return;

		const i18n = window.InitUserEngineData?.i18n || {};
		const messagesByCode = {
			invalid_username:   i18n.login_error_invalid_username,
			invalid_email:      i18n.login_error_invalid_email,
			incorrect_password: i18n.login_error_incorrect_password,
			empty_username:     i18n.login_error_empty_username,
			empty_password:     i18n.login_error_empty_password,
		};

		const code = params.get('iue_login_code') || '';
		const message = (Object.prototype.hasOwnProperty.call(messagesByCode, code) && messagesByCode[code])
			? messagesByCode[code]
			: (i18n.login_error_generic || 'Incorrect username or password. Please try again.');

		openLoginModal();

		const loginForm = document.getElementById('loginform');
		if (loginForm) {
			let notice = document.getElementById('iue-login-notice');
			if (!notice) {
				notice = document.createElement('div');
				notice.id = 'iue-login-notice';
				loginForm.prepend(notice);
			}
			notice.className = 'iue-register-message error';
			notice.textContent = message;
		}

		// Dọn query string để tránh hiện lại thông báo khi tải lại trang hoặc chia sẻ URL.
		params.delete('iue_login_failed');
		params.delete('iue_login_code');
		const cleanQuery = params.toString();
		const cleanUrl = window.location.pathname + (cleanQuery ? `?${cleanQuery}` : '') + window.location.hash;
		window.history.replaceState({}, document.title, cleanUrl);
	})();

	// ============ FORM VIEWS: login / register / lostpassword ============
	// Quản lý việc chuyển qua lại giữa các form trong modal. Template login-form.php
	// có thể bị theme override (bản cũ không có form Quên mật khẩu) nên mọi phần tử
	// đều có thể vắng mặt — khi đó link giữ hành vi mặc định (chuyển trang).
	const viewI18n = window.InitUserEngineData?.i18n || {};
	const viewPanels = {
		login:        document.getElementById('iue-form-login'),
		register:     document.getElementById('iue-form-register'),
		lostpassword: document.getElementById('iue-form-lostpassword'),
	};
	const viewTitles = {
		login:        viewI18n.title_login        || 'Login',
		register:     viewI18n.title_register     || 'Register',
		lostpassword: viewI18n.title_lostpassword || 'Lost Password',
	};
	const viewHeaderTitle = modal.querySelector('.iue-header h3');
	const registerLink    = document.getElementById('iue-register-link');
	const lostPassLink    = document.getElementById('iue-lostpass-link');
	const textBackToLogin = viewI18n.back_to_login || 'Back to login';
	const textRegister    = viewI18n.register      || 'Create a new account';
	const textLostPass    = lostPassLink ? lostPassLink.textContent.trim() : '';

	// Register chỉ chuyển form trong modal khi KHÔNG dùng custom URL
	const canShowRegister = !!(registerLink && viewPanels.login && viewPanels.register && registerLink.dataset.hasCustomUrl !== '1');
	// Quên mật khẩu chỉ hiển thị trong modal khi option bật (data-iue-modal="1") và không có custom URL
	const canShowLostPass = !!(lostPassLink && viewPanels.login && viewPanels.lostpassword && lostPassLink.dataset.iueModal === '1');

	let currentView = 'login';

	function showView(view) {
		if (view === 'register' && !canShowRegister) return;
		if (view === 'lostpassword' && !canShowLostPass) return;
		if (!viewPanels[view] || view === currentView) return;

		currentView = view;

		// 1. Toggle form visibility
		Object.keys(viewPanels).forEach(name => {
			const panel = viewPanels[name];
			if (!panel) return;
			if (name === view) {
				panel.classList.remove('iue-hidden');
				panel.classList.add('iue-animating');
			} else {
				panel.classList.add('iue-hidden');
			}
		});

		// 2. Cleanup animation
		setTimeout(() => {
			Object.keys(viewPanels).forEach(name => {
				if (viewPanels[name]) viewPanels[name].classList.remove('iue-animating');
			});
		}, 400);

		// 3. Update link text
		if (canShowRegister) registerLink.textContent = view === 'register' ? textBackToLogin : textRegister;
		if (canShowLostPass) lostPassLink.textContent = view === 'lostpassword' ? textBackToLogin : textLostPass;

		// 4. Update header title
		if (viewHeaderTitle) viewHeaderTitle.textContent = viewTitles[view];

		// 5. LAZY init Register / Turnstile chỉ khi form thực sự được mở
		if (view === 'register') {
			initRegisterFormIfNeeded();
			const turnstileEl = document.getElementById('iue-turnstile');
			if (turnstileEl) iueLoadTurnstile(() => iueRenderTurnstile());
		} else if (view === 'lostpassword') {
			const lostTurnstileEl = document.getElementById('iue-turnstile-wplostpassword');
			if (lostTurnstileEl) iueLoadTurnstile(() => iueRenderTurnstile('iue-turnstile-wplostpassword', '_iueWidgetIdLostPassword'));
		}

		// 6. Focus first input
		const targetInput = viewPanels[view].querySelector('input:not([type="hidden"])');
		if (targetInput) setTimeout(() => targetInput.focus(), 50);
	}

	if (registerLink && registerLink.dataset.hasCustomUrl === '1') {
		registerLink.addEventListener('click', e => { e.preventDefault(); window.location.href = registerLink.dataset.url; });
	} else if (canShowRegister) {
		registerLink.addEventListener('click', e => {
			e.preventDefault();
			showView(currentView === 'register' ? 'login' : 'register');
		});
	}

	if (canShowLostPass) {
		lostPassLink.addEventListener('click', e => {
			e.preventDefault();
			showView(currentView === 'lostpassword' ? 'login' : 'lostpassword');
		});
	}

	// Hiển thị thông báo ở đầu 1 form (tái sử dụng style .iue-register-message)
	function showFormNotice(container, noticeId, message, type) {
		if (!container) return;
		let notice = document.getElementById(noticeId);
		if (!notice) {
			notice = document.createElement('div');
			notice.id = noticeId;
			container.prepend(notice);
		}
		notice.className = `iue-register-message ${type}`;
		notice.textContent = message;
	}

	// Xóa các tham số tạm thời khỏi URL để tránh hiện lại thông báo khi tải lại trang hoặc chia sẻ URL.
	function cleanQueryParams(keys) {
		const params = new URLSearchParams(window.location.search);
		keys.forEach(key => params.delete(key));
		const cleanQuery = params.toString();
		const cleanUrl = window.location.pathname + (cleanQuery ? `?${cleanQuery}` : '') + window.location.hash;
		window.history.replaceState({}, document.title, cleanUrl);
	}

	// Trigger qua data-iue="login"
	document.querySelectorAll('[data-iue="login"]').forEach(el => {
		el.addEventListener('click', function (e) { e.preventDefault(); openLoginModal(); });
	});

	// Trigger qua data-iue="register"
	document.querySelectorAll('[data-iue="register"]').forEach(el => {
		el.addEventListener('click', function (e) {
			e.preventDefault();

			// Mở modal trước, rồi chuyển sang tab Đăng ký (nếu đang dùng modal, không phải custom URL)
			openLoginModal();
			showView('register');
		});
	});

	// Trigger qua data-iue="lostpassword"
	document.querySelectorAll('[data-iue="lostpassword"]').forEach(el => {
		el.addEventListener('click', function (e) {
			// Không dùng modal (option tắt / custom URL) → để link hoạt động như bình thường
			if (!canShowLostPass) {
				if (lostPassLink && el.tagName !== 'A') window.location.href = lostPassLink.href;
				return;
			}
			e.preventDefault();
			openLoginModal();
			showView('lostpassword');
		});
	});

	// ============ LOST PASSWORD FORM ============
	// Form là <form> thật, POST thẳng tới wp-login.php?action=lostpassword (luồng gốc của WordPress).
	// JS chỉ điền redirect_to để quay lại đúng trang hiện tại và chặn submit sớm khi rõ ràng thiếu dữ liệu.
	(function initLostPasswordForm() {
		const form = document.getElementById('iue-lostpassword-form');
		if (!form || !canShowLostPass) return;

		const submitBtn   = form.querySelector('button[type="submit"]');
		const submitLabel = submitBtn ? submitBtn.textContent.trim() : '';

		form.addEventListener('submit', function (e) {
			const userField = form.querySelector('input[name="user_login"]');
			if (userField && !userField.value.trim()) {
				e.preventDefault();
				showFormNotice(form, 'iue-lostpass-notice', viewI18n.lostpass_error_empty || 'Please enter a username or email address.', 'error');
				userField.focus();
				return;
			}

			// Turnstile: server vẫn luôn xác thực lại — đây chỉ là UX tốt hơn, tránh rời trang khi rõ ràng thiếu captcha
			const turnstileEl = document.getElementById('iue-turnstile-wplostpassword');
			if (turnstileEl && typeof turnstile !== 'undefined' && turnstile.getResponse) {
				if (!turnstile.getResponse(window._iueWidgetIdLostPassword)) {
					e.preventDefault();
					showFormNotice(form, 'iue-lostpass-notice', viewI18n.captcha_required || 'Please complete the captcha.', 'error');
					return;
				}
			}

			// Sau khi gửi email thành công, WordPress sẽ chuyển hướng về đúng URL này
			const redirectInput = form.querySelector('input[name="redirect_to"]');
			if (redirectInput) {
				try {
					const returnUrl = new URL(window.location.href);
					['iue_lostpass', 'iue_lostpass_code', 'iue_login_failed', 'iue_login_code'].forEach(key => returnUrl.searchParams.delete(key));
					returnUrl.searchParams.set('iue_lostpass', 'sent');
					returnUrl.hash = '';
					redirectInput.value = returnUrl.toString();
				} catch (err) {
					redirectInput.value = '';
				}
			}

			// Chống bấm gửi nhiều lần (button bị disable SAU khi form đã bắt đầu submit)
			if (submitBtn) {
				setTimeout(() => {
					if (e.defaultPrevented) return;
					submitBtn.disabled = true;
					submitBtn.textContent = viewI18n.processing || 'Processing...';
				}, 0);
			}
		});

		// Khôi phục nút gửi khi người dùng bấm Back và trang được lấy lại từ bfcache
		window.addEventListener('pageshow', function (e) {
			if (!e.persisted || !submitBtn || !submitBtn.disabled) return;
			submitBtn.disabled = false;
			submitBtn.textContent = submitLabel;
		});
	})();

	// Tự mở modal + hiển thị kết quả sau khi gửi form Quên mật khẩu.
	// - iue_lostpass=sent   : WordPress đã gửi email thành công (redirect_to do JS điền ở trên)
	// - iue_lostpass=failed : server (includes/hooks.php) đưa người dùng quay lại kèm mã lỗi
	(function handleLostPasswordRedirect() {
		const params = new URLSearchParams(window.location.search);
		const state  = params.get('iue_lostpass');
		if (state !== 'sent' && state !== 'failed') return;

		openLoginModal();

		if (state === 'sent') {
			showFormNotice(
				document.getElementById('loginform') || viewPanels.login,
				'iue-login-notice',
				viewI18n.lostpass_sent || 'Check your email for the confirmation link.',
				'success'
			);
		} else {
			const messagesByCode = {
				empty_username:                  viewI18n.lostpass_error_empty,
				invalid_email:                   viewI18n.lostpass_error_invalid,
				invalidcombo:                    viewI18n.lostpass_error_invalid,
				retrieve_password_email_failure: viewI18n.lostpass_error_email,
				no_password_reset:               viewI18n.lostpass_error_not_allowed,
				iue_turnstile_failed:            viewI18n.lostpass_error_captcha,
			};
			const code = params.get('iue_lostpass_code') || '';
			const message = (Object.prototype.hasOwnProperty.call(messagesByCode, code) && messagesByCode[code])
				? messagesByCode[code]
				: (viewI18n.lostpass_error_generic || 'Could not process your request. Please try again.');

			if (canShowLostPass) {
				showView('lostpassword');
				showFormNotice(document.getElementById('iue-lostpassword-form'), 'iue-lostpass-notice', message, 'error');
			} else {
				showFormNotice(document.getElementById('loginform') || viewPanels.login, 'iue-login-notice', message, 'error');
			}
		}

		cleanQueryParams(['iue_lostpass', 'iue_lostpass_code']);
	})();

	// ============ REGISTER FORM (lazy) ============
	let registerFormInitialized = false;
	function initRegisterFormIfNeeded() {
		if (registerFormInitialized) return;
		registerFormInitialized = true;
		handleRegisterForm();
	}

	function handleRegisterForm() {
		const form = document.getElementById('iue-register-form');
		if (!form) return;

		const captchaInput   = document.getElementById('iue_register_captcha_answer');
		const hasCaptcha     = !!captchaInput;

		// Turnstile placeholder (id từ template)
		const turnstileEl    = document.getElementById('iue-turnstile');
		const hasTurnstile   = !!turnstileEl;

		let currentCaptcha = null;
		let captchaAttempts = 0;
		const maxAttempts = 3;

		// --- Turnstile helpers ---
		function getTurnstileToken() {
			if (typeof turnstile !== 'undefined' && turnstile.getResponse) {
				const t = turnstile.getResponse(window._iueWidgetId);
				if (t) return t;
			}
			const hidden = document.querySelector('input[name="cf-turnstile-response"]');
			return hidden && hidden.value ? hidden.value : null;
		}
		function resetTurnstile() {
			if (typeof turnstile !== 'undefined' && turnstile.reset) {
				try { turnstile.reset(window._iueWidgetId); } catch (e) {}
			}
		}

		// --- Captcha cũ (phép tính) ---
		async function loadCaptcha(force = false) {
			if (!hasCaptcha) return;
			try {
				const timestamp = Date.now();
				// rest_url có thể đã chứa "?" khi site dùng permalink "Plain" (?rest_route=...)
				const captchaUrl = `${InitUserEngineData.rest_url}/captcha`;
				const res = await fetch(`${captchaUrl}${captchaUrl.indexOf('?') === -1 ? '?' : '&'}_=${timestamp}`, {
					headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }
				});
				if (!res.ok) throw new Error('Failed to load captcha');

				currentCaptcha = await res.json();
				const box = document.getElementById('iue-captcha-question');
				if (box) { box.textContent = currentCaptcha.question; box.className = 'iue-captcha-question'; }
				if (captchaInput) captchaInput.value = '';

				if (force) { captchaAttempts = 0; updateCaptchaUI(); }
			} catch (error) {
				console.error('Captcha load error:', error);
				const box = document.getElementById('iue-captcha-question');
				if (box) { box.textContent = 'Failed to load captcha. Please refresh the page.'; box.className = 'iue-captcha-question error'; }
			}
		}
		function updateCaptchaUI() {
			if (!hasCaptcha) return;
			const box = document.getElementById('iue-captcha-question');
			if (captchaAttempts >= maxAttempts) {
				if (box) box.className = 'iue-captcha-question error';
				if (captchaInput) captchaInput.disabled = true;
				showRegisterMessage('Too many captcha attempts. Getting a new one...', 'error');
				setTimeout(() => loadCaptcha(true), 2000);
			} else {
				if (box) box.className = 'iue-captcha-question';
				if (captchaInput) captchaInput.disabled = false;
			}
		}

		// Chỉ load captcha cũ nếu thực sự dùng captcha cũ
		if (hasCaptcha && !hasTurnstile) loadCaptcha();

		form.addEventListener('submit', async function (e) {
			e.preventDefault();

			const username = form.username.value.trim();
			const email    = form.email.value.trim();
			const password = form.password.value;
			const captchaAnswer = hasCaptcha ? form.captcha_answer.value.trim() : null;

			const error = validateRegisterInput(username, email, password, captchaAnswer);
			if (error) { showRegisterMessage(error, 'error'); return; }

			// Turnstile token
			let turnstileToken = null;
			if (hasTurnstile) {
				turnstileToken = getTurnstileToken();
				if (!turnstileToken) { showRegisterMessage('Please complete the captcha.', 'error'); return; }
			}

			// Captcha cũ: check token + expiry
			if (!hasTurnstile && hasCaptcha) {
				if (!currentCaptcha || !currentCaptcha.token) {
					showRegisterMessage('Captcha not loaded. Please wait...', 'error');
					await loadCaptcha(); return;
				}
				if (currentCaptcha.expires && Date.now() > currentCaptcha.expires * 1000) {
					showRegisterMessage('Captcha expired. Loading new one...', 'error');
					await loadCaptcha(true); return;
				}
			}

			showRegisterMessage(window.InitUserEngineData?.i18n?.registering || 'Registering...', 'loading');

			try {
				const payload = { username, email, password, iue_hp: '' };
				if (hasTurnstile && turnstileToken) {
					payload.turnstile_token = turnstileToken; // endpoint ưu tiên field này
				} else if (hasCaptcha) {
					payload.captcha_token  = currentCaptcha?.token;
					payload.captcha_answer = parseInt(captchaAnswer);
				}

				const response = await fetch(`${InitUserEngineData.rest_url}/register`, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
					body: JSON.stringify(payload)
				});
				const result = await response.json();

				if (!response.ok) {
					if (hasTurnstile) {
						if (result.code === 'turnstile_timeout' || result.code === 'turnstile_invalid' || result.code === 'turnstile_required') {
							resetTurnstile();
						}
					} else if (hasCaptcha) {
						if (result.code === 'captcha_wrong' || result.code === 'captcha_attempts') {
							captchaAttempts++; updateCaptchaUI();
						} else if (result.code === 'captcha_expired' || result.code === 'captcha_invalid') {
							await loadCaptcha(true);
						}
					}
					if (result.code === 'rate_limit') {
						showRegisterMessage('Too many attempts. Please wait before trying again.', 'error');
						setTimeout(() => window.location.reload(), 5000);
						return;
					}
					throw new Error(result.message || 'Registration failed');
				}

				const i18nSuccess = window.InitUserEngineData?.i18n || {};
				const successMessage = result.auto_login
					? (i18nSuccess.register_success_auto_login || 'Welcome! Logging you in…')
					: (i18nSuccess.register_success || 'Welcome! You can now log in.');

				showRegisterMessage(successMessage, 'success');
				form.reset();
				captchaAttempts = 0;
				resetTurnstile();

				if (result.auto_login) {
					// Đã đăng nhập sẵn ở server (wp_set_auth_cookie) — reload để lấy đúng
					// trạng thái UI đã login (avatar, nonce...) thay vì chuyển sang form Login.
					setTimeout(() => { window.location.reload(); }, 1200);
				} else {
					setTimeout(() => {
						const registerLink = document.getElementById('iue-register-link');
						if (registerLink) registerLink.click();
					}, 2000);
				}

			} catch (err) {
				showRegisterMessage(err.message, 'error');
				if (!hasTurnstile && hasCaptcha) {
					setTimeout(async () => {
						if (!String(err.message || '').toLowerCase().includes('captcha')) await loadCaptcha(true);
					}, 1000);
				}
			}
		});

		function validateRegisterInput(username, email, password, captchaAnswer) {
			const i18n = window.InitUserEngineData?.i18n || {};
			if (!hasTurnstile && hasCaptcha && !captchaAnswer) return i18n.captcha_required || 'Please complete the captcha.';
			if (username.length < 3) return i18n.username_too_short || 'Username must be at least 3 characters.';
			if (!/^[a-zA-Z0-9_]+$/.test(username)) return i18n.username_invalid || 'Username can only contain letters, numbers and underscores.';
			if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return i18n.email_invalid || 'Please enter a valid email address.';
			if (password.length < 6) return i18n.password_too_short || 'Password must be at least 6 characters.';
			if (!/(?=.*[a-zA-Z])(?=.*\d)/.test(password)) return i18n.password_weak || 'Password must contain both letters and numbers.';
			return null;
		}

		// Auto refresh cho captcha cũ
		let refreshInterval;
		function startCaptchaRefresh() {
			if (!hasTurnstile && hasCaptcha) {
				refreshInterval = setInterval(async () => {
					const registerForm = document.getElementById('iue-form-register');
					if (document.visibilityState === 'visible' && currentCaptcha && registerForm && !registerForm.classList.contains('iue-hidden')) {
						const age = Date.now() - (currentCaptcha.expires - 15 * 60 * 1000);
						if (age > 10 * 60 * 1000) await loadCaptcha(true);
					}
				}, 60 * 1000);
			}
		}
		function stopCaptchaRefresh() {
			if (refreshInterval) { clearInterval(refreshInterval); refreshInterval = null; }
		}
		if (!hasTurnstile && hasCaptcha) {
			startCaptchaRefresh();
			const closeBtn2 = document.getElementById('init-user-engine-modal-close');
			if (closeBtn2) closeBtn2.addEventListener('click', stopCaptchaRefresh);
			const registerLink2 = document.getElementById('iue-register-link');
			if (registerLink2) {
				registerLink2.addEventListener('click', function () {
					const registerForm = document.getElementById('iue-form-register');
					if (registerForm && registerForm.classList.contains('iue-hidden')) stopCaptchaRefresh();
				});
			}
		}

		function showRegisterMessage(message, type = 'info') {
			let box = document.getElementById('iue-register-message');
			if (!box) {
				box = document.createElement('div');
				box.id = 'iue-register-message';
				box.className = 'iue-register-message';
				form.prepend(box);
			}
			box.textContent = message;
			box.className = `iue-register-message ${type}`;
			if (type === 'success') setTimeout(() => { box.style.opacity = '0.7'; }, 3000);
		}
	}

	// === LOGIN TURNSTILE: chặn submit sớm nếu widget chưa hoàn thành ===
	// (form đăng nhập là <form> thật của wp_login_form(), submit thẳng tới wp-login.php,
	// server vẫn luôn xác thực lại — đây chỉ là UX tốt hơn, tránh reload trang khi rõ ràng thiếu captcha)
	(function () {
		const loginForm = document.getElementById('loginform');
		if (!loginForm) return;

		loginForm.addEventListener('submit', function (e) {
			const el = document.getElementById('iue-turnstile-login');
			if (!el) return; // Turnstile không bật cho login, bỏ qua

			if (typeof turnstile === 'undefined' || !turnstile.getResponse) return; // chưa kịp load, để server xử lý

			const token = turnstile.getResponse(window._iueWidgetIdLogin);
			if (token) return;

			e.preventDefault();

			let notice = document.getElementById('iue-login-turnstile-notice');
			if (!notice) {
				notice = document.createElement('div');
				notice.id = 'iue-login-turnstile-notice';
				notice.className = 'iue-register-message error';
				loginForm.prepend(notice);
			}
			const i18n = window.InitUserEngineData?.i18n || {};
			notice.textContent = i18n.captcha_required || 'Please complete the captcha.';
		});
	})();

	// Theme apply cho modal
	(function applyLoginModalTheme() {
		const config = window.InitPluginSuiteUserEngineConfig || {};
		const theme  = config.theme;
		const modal  = document.getElementById('init-user-engine-login-modal');
		if (!modal) return;
		modal.classList.remove('dark');
		if (theme === 'dark') {
			modal.classList.add('dark');
		} else if (theme === 'auto') {
			const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
			if (prefersDark) modal.classList.add('dark');
		}
	})();

	// === PASSWORD TOGGLE ===
	(function () {

		function attachToggle(input) {
			if (!input || input.dataset.iuePwdEnhanced === "1") return;

			// Wrap input
			const wrap = document.createElement("div");
			wrap.className = "iue-password-wrapper";
			input.parentNode.insertBefore(wrap, input);
			wrap.appendChild(input);

			// Button eye toggle
			const btn = document.createElement("button");
			btn.type = "button";
			btn.className = "iue-password-toggle";
			btn.innerHTML = svgEye;
			btn.tabIndex = -1;
			wrap.appendChild(btn);

			// Toggle function
			const toggle = () => {
			    const show = input.type === "password";
			    const cursorPos = input.selectionStart; // nhớ vị trí caret

			    input.type = show ? "text" : "password";
			    btn.innerHTML = show ? svgEyeOff : svgEye;

			    // Focus lại và restore caret
			    input.focus();
			    input.setSelectionRange(cursorPos, cursorPos);
			};

			// Click icon → toggle (và ĐỪNG đóng modal)
			btn.addEventListener("click", (e) => {
				e.preventDefault();
				e.stopPropagation();  // không cho click lan lên modal overlay
				toggle();
			});

			// đánh dấu đã gắn
			input.dataset.iuePwdEnhanced = "1";
		}

		// Gắn ngay cho login + register.
		// Form đăng ký được render sẵn (ẩn) trong modal từ server nên đã có trong DOM ngay lúc này,
		// không cần MutationObserver theo dõi toàn bộ <body> (tốn CPU trên mọi thay đổi DOM của trang).
		attachToggle(document.getElementById("user_pass"));
		attachToggle(document.getElementById("iue_register_password"));

	})();

	window.openLoginModal = openLoginModal;
});
