(function () {
	const affParam = new URLSearchParams(window.location.search).get('aff');
	if (affParam) {
		document.cookie = `iue_ref=${affParam}; path=/; max-age=${60 * 60 * 24 * 7}`; // lưu 7 ngày
	}
})();

document.addEventListener('DOMContentLoaded', function () {
	const avatar = document.getElementById('init-user-engine-avatar');
	const modal = document.getElementById('init-user-engine-login-modal');
	const closeBtn = document.getElementById('init-user-engine-modal-close');
	const svgEye = `<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">
	<circle fill="none" stroke="currentColor" cx="10" cy="10" r="3.45"></circle>
	<path fill="none" stroke="currentColor" d="m19.5,10c-2.4,3.66-5.26,7-9.5,7h0,0,0c-4.24,0-7.1-3.34-9.49-7C2.89,6.34,5.75,3,9.99,3h0,0,0c4.25,0,7.11,3.34,9.5,7Z"></path>
	</svg>`;

	const svgEyeOff = `<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">
	<path fill="none" stroke="currentColor" d="m7.56,7.56c.62-.62,1.49-1.01,2.44-1.01,1.91,0,3.45,1.54,3.45,3.45,0,.95-.39,1.82-1.01,2.44"></path>
	<path fill="none" stroke="currentColor" d="m19.5,10c-2.4,3.66-5.26,7-9.5,7h0,0,0c-4.24,0-7.1-3.34-9.49-7C2.89,6.34,5.75,3,9.99,3h0,0,0c4.25,0,7.11,3.34,9.5,7Z"></path>
	<line fill="none" stroke="currentColor" x1="2.5" y1="2.5" x2="17.5" y2="17.5"></line>
	</svg>`;

	if (!avatar || !modal || !closeBtn) return;

	function openLoginModal() {
		modal.classList.add('open');
		document.body.classList.add('init-user-engine-modal-open');

		const user = document.getElementById('user_login');
		const pass = document.getElementById('user_pass');
		const i18n = InitUserEngineData?.i18n || {};
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

	avatar.addEventListener('click', function (e) {
		e.preventDefault();
		openLoginModal();
	});

	closeBtn.addEventListener('click', closeModal);
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') closeModal();

		if (e.altKey && e.key.toLowerCase() === 'l') {
			if (!modal.classList.contains('open')) {
				openLoginModal();
			}
		}
	});

	modal.addEventListener('click', function (e) {
		const content = modal.querySelector('.iue-content');
		if (!content.contains(e.target)) closeModal();
	});

	// Trigger qua #init-user-engine
	if (window.location.hash === '#init-user-engine') {
		openLoginModal();
	}

	// Trigger qua data-iue="login"
	document.querySelectorAll('[data-iue="login"]').forEach(el => {
		el.addEventListener('click', function (e) {
			e.preventDefault();
			openLoginModal();
		});
	});

	// Toggle login/register form
	(function toggleLoginRegisterForm() {
		const registerLink = document.getElementById('iue-register-link');
		const loginForm    = document.getElementById('iue-form-login');
		const registerForm = document.getElementById('iue-form-register');
		const headerTitle  = document.querySelector('.iue-header h3');

		if (!registerLink || !loginForm || !registerForm || !headerTitle) return;

		const hasCustomUrl = registerLink.dataset.hasCustomUrl === '1';
		const i18n         = InitUserEngineData?.i18n || {};
		const textLogin    = i18n.back_to_login    || 'Back to login';
		const textRegister = i18n.register         || 'Create a new account';
		const titleLogin   = i18n.title_login      || 'Login';
		const titleRegister= i18n.title_register   || 'Register';

		if (hasCustomUrl) {
			registerLink.addEventListener('click', e => {
				e.preventDefault();
				window.location.href = registerLink.dataset.url;
			});
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
			const targetInput = showingRegister
				? registerForm.querySelector('input')
				: loginForm.querySelector('input');
			if (targetInput) setTimeout(() => targetInput.focus(), 50);
		});
	})();

	(function handleRegisterForm() {
		const form = document.getElementById('iue-register-form');
		if (!form) return;

		form.addEventListener('submit', async function (e) {
			e.preventDefault();

			const username = form.username.value.trim();
			const email = form.email.value.trim();
			const password = form.password.value;

			// === 1. Validate ===
			const error = validateRegisterInput(username, email, password);
			if (error) {
				showRegisterMessage(error, 'error');
				return;
			}

			// === 2. Show loading ===
			showRegisterMessage(InitUserEngineData.i18n.registering || 'Registering...', 'loading');

			try {
				// === 3. Gửi request ===
				const response = await fetch('/wp-json/inituser/v1/register', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ username, email, password })
				});

				const result = await response.json();

				if (!response.ok) {
					throw new Error(result.message || 'Unknown error');
				}

				// === 4. Thành công ===
				showRegisterMessage(InitUserEngineData.i18n.register_success || 'Welcome! You can now log in.', 'success');

				// Option: tự động chuyển về form login
				setTimeout(() => {
					document.getElementById('iue-register-link')?.click();
				}, 1500);

			} catch (err) {
				showRegisterMessage(err.message, 'error');
			}
		});

		(function enablePasswordToggleSVG() {
			document.querySelectorAll('input[type="password"]').forEach(input => {
				if (input.dataset.hasToggle === '1') return;

				const wrapper = document.createElement('div');
				wrapper.className = 'iue-password-wrapper';
				input.parentNode.insertBefore(wrapper, input);
				wrapper.appendChild(input);

				const toggle = document.createElement('button');
				toggle.type = 'button';
				toggle.className = 'iue-password-toggle';
				toggle.innerHTML = svgEye;
				wrapper.appendChild(toggle);

				toggle.addEventListener('click', (e) => {
					e.preventDefault();
					e.stopPropagation();

					const isShown = input.type === 'text';
					input.type = isShown ? 'password' : 'text';
					toggle.innerHTML = isShown ? svgEye : svgEyeOff;
				});

				input.dataset.hasToggle = '1';
			});
		})();

		function validateRegisterInput(username, email, password) {
			const i18n = InitUserEngineData?.i18n || {};
			if (username.length < 3) return i18n.username_too_short || 'Username must be at least 3 characters.';
			if (!/^[a-zA-Z0-9_]+$/.test(username)) return i18n.username_invalid || 'Username can only contain letters, numbers and underscores.';
			if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return i18n.email_invalid || 'Please enter a valid email address.';
			if (password.length < 6) return i18n.password_too_short || 'Password must be at least 6 characters.';
			return null;
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
		}
	})();

	(function applyLoginModalTheme() {
		const config = window.InitPluginSuiteUserEngineConfig || {};
		const theme = config.theme;
		const modal = document.getElementById('init-user-engine-login-modal');

		if (!modal) return;
		modal.classList.remove('dark');

		if (theme === 'dark') {
			modal.classList.add('dark');
		} else if (theme === 'auto') {
			const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
			if (prefersDark) modal.classList.add('dark');
		}
	})();

	window.openLoginModal = openLoginModal;
});
