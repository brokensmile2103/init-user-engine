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
window.iueRenderTurnstile = function () {
	const el = document.getElementById('iue-turnstile'); // placeholder trong template
	if (!el || typeof turnstile === 'undefined' || el.dataset.rendered === '1') return;
	const sitekey = el.dataset.sitekey;
	const theme   = el.dataset.theme || 'auto';
	window._iueWidgetId = turnstile.render(el, { sitekey, theme });
	el.dataset.rendered = '1';
};

document.addEventListener('DOMContentLoaded', function () {
	const avatar  = document.getElementById('init-user-engine-avatar');
	const modal   = document.getElementById('init-user-engine-login-modal');
	const closeBtn= document.getElementById('init-user-engine-modal-close');

	if (!avatar || !modal || !closeBtn) return;

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
	}
	function closeModal() {
		modal.classList.remove('open');
		document.body.classList.remove('init-user-engine-modal-open');
	}

	avatar.addEventListener('click', function (e) { e.preventDefault(); openLoginModal(); });
	closeBtn.addEventListener('click', closeModal);

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') closeModal();
		if (e.altKey && e.key.toLowerCase() === 'l') {
			if (!modal.classList.contains('open')) openLoginModal();
		}
	});

	modal.addEventListener('click', function (e) {
		const content = modal.querySelector('.iue-content');
		if (content && !content.contains(e.target)) closeModal();
	});

	// Trigger qua hash
	if (window.location.hash === '#init-user-engine') openLoginModal();

	// Trigger qua data-iue="login"
	document.querySelectorAll('[data-iue="login"]').forEach(el => {
		el.addEventListener('click', function (e) { e.preventDefault(); openLoginModal(); });
	});

	// Trigger qua data-iue="register"
	document.querySelectorAll('[data-iue="register"]').forEach(el => {
		el.addEventListener('click', function (e) {
			e.preventDefault();

			// Mở modal trước
			openLoginModal();

			// Rồi chuyển sang tab Đăng ký (nếu đang dùng modal, không phải custom URL)
			const registerLink = document.getElementById('iue-register-link');
			if (registerLink) {
				const hasCustomUrl = registerLink.dataset.hasCustomUrl === '1';

				// Nếu không có custom URL → dùng toggle form trong modal
				if (!hasCustomUrl) {
					registerLink.click();
				}
			}
		});
	});

	// Toggle login/register
	(function toggleLoginRegisterForm() {
		const registerLink = document.getElementById('iue-register-link');
		const loginForm    = document.getElementById('iue-form-login');
		const registerForm = document.getElementById('iue-form-register');
		const headerTitle  = document.querySelector('.iue-header h3');
		if (!registerLink || !loginForm || !registerForm || !headerTitle) return;

		const hasCustomUrl = registerLink.dataset.hasCustomUrl === '1';
		const i18n         = window.InitUserEngineData?.i18n || {};
		const textLogin    = i18n.back_to_login    || 'Back to login';
		const textRegister = i18n.register         || 'Create a new account';
		const titleLogin   = i18n.title_login      || 'Login';
		const titleRegister= i18n.title_register   || 'Register';

		if (hasCustomUrl) {
			registerLink.addEventListener('click', e => { e.preventDefault(); window.location.href = registerLink.dataset.url; });
			return;
		}

		let showingRegister = false;

		registerLink.addEventListener('click', e => {
			e.preventDefault();
			showingRegister = !showingRegister;

			// 1. Toggle form visibility
			if (showingRegister) {
				loginForm.classList.add('iue-hidden');
				registerForm.classList.remove('iue-hidden');
				registerForm.classList.add('iue-animating');

				// LAZY init Register + Turnstile
				initRegisterFormIfNeeded();
				const turnstileEl = document.getElementById('iue-turnstile');
				if (turnstileEl) iueLoadTurnstile(() => iueRenderTurnstile());
			} else {
				registerForm.classList.add('iue-hidden');
				loginForm.classList.remove('iue-hidden');
				loginForm.classList.add('iue-animating');
			}

			// 2. Cleanup animation
			setTimeout(() => {
				loginForm.classList.remove('iue-animating');
				registerForm.classList.remove('iue-animating');
			}, 400);

			// 3. Update link text
			registerLink.textContent = showingRegister ? textLogin : textRegister;

			// 4. Update header title
			headerTitle.textContent = showingRegister ? titleRegister : titleLogin;

			// 5. Focus first input
			const targetInput = showingRegister ? registerForm.querySelector('input') : loginForm.querySelector('input');
			if (targetInput) setTimeout(() => targetInput.focus(), 50);
		});
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
				const res = await fetch(`${InitUserEngineData.rest_url}/captcha?_=${timestamp}`, {
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

				showRegisterMessage(window.InitUserEngineData?.i18n?.register_success || 'Welcome! You can now log in.', 'success');
				form.reset();
				captchaAttempts = 0;
				resetTurnstile();

				setTimeout(() => {
					const registerLink = document.getElementById('iue-register-link');
					if (registerLink) registerLink.click();
				}, 2000);

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

		// Gắn ngay cho login
		attachToggle(document.getElementById("user_pass"));

		// Theo dõi register form để gắn khi xuất hiện
		const observer = new MutationObserver(() => {
			attachToggle(document.getElementById("iue_register_password"));
		});
		observer.observe(document.body, { childList: true, subtree: true });

	})();

	window.openLoginModal = openLoginModal;
});
