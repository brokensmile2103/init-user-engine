const IUE_Icons = {
	eye: '<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><circle fill="none" stroke="currentColor" cx="10" cy="10" r="3.45"></circle><path fill="none" stroke="currentColor" d="m19.5,10c-2.4,3.66-5.26,7-9.5,7h0,0,0c-4.24,0-7.1-3.34-9.49-7C2.89,6.34,5.75,3,9.99,3h0,0,0c4.25,0,7.11,3.34,9.5,7Z"></path></svg>',
	eyeoff: '<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path fill="none" stroke="currentColor" d="m7.56,7.56c.62-.62,1.49-1.01,2.44-1.01,1.91,0,3.45,1.54,3.45,3.45,0,.95-.39,1.82-1.01,2.44"></path><path fill="none" stroke="currentColor" d="m19.5,10c-2.4,3.66-5.26,7-9.5,7h0,0,0c-4.24,0-7.1-3.34-9.49-7C2.89,6.34,5.75,3,9.99,3h0,0,0c4.25,0,7.11,3.34,9.5,7Z"></path><line fill="none" stroke="currentColor" x1="2.5" y1="2.5" x2="17.5" y2="17.5"></line></svg>',
    
    coin: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><ellipse fill="none" stroke="currentColor" cx="10" cy="4.64" rx="7.5" ry="3.14"></ellipse><path fill="none" stroke="currentColor" d="M17.5,8.11 C17.5,9.85 14.14,11.25 10,11.25 C5.86,11.25 2.5,9.84 2.5,8.11"></path><path fill="none" stroke="currentColor" d="M17.5,11.25 C17.5,12.99 14.14,14.39 10,14.39 C5.86,14.39 2.5,12.98 2.5,11.25"></path><path fill="none" stroke="currentColor" d="M17.49,4.64 L17.5,14.36 C17.5,16.1 14.14,17.5 10,17.5 C5.86,17.5 2.5,16.09 2.5,14.36 L2.5,4.64"></path></svg>`,
    cash: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><rect width="17" height="12" fill="none" stroke="currentColor" x="1.5" y="4.5"></rect><rect width="18" height="3" x="1" y="7"></rect></svg>`,
    
    star: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><polygon fill="none" stroke="currentColor" stroke-width="1.01" points="10 2 12.63 7.27 18.5 8.12 14.25 12.22 15.25 18 10 15.27 4.75 18 5.75 12.22 1.5 8.12 7.37 7.27"></polygon></svg>`,
    camera: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="10" cy="10.8" r="3.8"></circle><path fill="none" stroke="currentColor" d="M1,4.5 C0.7,4.5 0.5,4.7 0.5,5 L0.5,17 C0.5,17.3 0.7,17.5 1,17.5 L19,17.5 C19.3,17.5 19.5,17.3 19.5,17 L19.5,5 C19.5,4.7 19.3,4.5 19,4.5 L13.5,4.5 L13.5,2.9 C13.5,2.6 13.3,2.5 13,2.5 L7,2.5 C6.7,2.5 6.5,2.6 6.5,2.9 L6.5,4.5 L1,4.5 L1,4.5 Z"></path></svg>`,
    diamond: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M15 3h-.4l-.2.2h-.1v.1l-.1.1L1 17.4l-.6.5.5.7 23 29.7q.2.5.7.7l.4.6.5-.6q.5-.2.5-.7l23-29.7.6-.7-.6-.6L36 3.5l-.2-.2-.2-.2V3zm.8 2h7l-9 9.7zm11.5 0h6.9l2 9.7zm-2.3.5L35.7 17H14.2zm11.7 1.8L46 17h-7.2zm-23.4 0L11.2 17H4zM3.8 19h7.5L21 41.2zm9.7 0h23L25 45.5zm25.1 0h7.6L29 41.2z" transform="scale(5.12)" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode:normal"/></svg>`,
    task: `<svg width="20" height="20" viewBox="0 0 28 28" fill="none"><path d="M4 5.25C4 3.45 5.46 2 7.25 2h13.5C22.55 2 24 3.46 24 5.25v12.13q-.18.12-.34.28l-1.16 1.16V5.25c0-.97-.78-1.75-1.75-1.75H7.25c-.97 0-1.75.78-1.75 1.75v17.5c0 .97.78 1.75 1.75 1.75h8.07l1.5 1.5H7.25A3.25 3.25 0 0 1 4 22.75z" fill="#212121"/><path d="M10.5 8.75a1.25 1.25 0 1 1-2.5 0 1.25 1.25 0 0 1 2.5 0m-1.25 6.5a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5m0 5.25a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5M12.75 8a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5zM12 14c0-.41.34-.75.75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 12 14m.75 4.5a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5zm13.03 1.28-6 6a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 0 1 1.06-1.06l2.47 2.47 5.47-5.47a.75.75 0 1 1 1.06 1.06" fill="#212121"/></svg>`,
    history: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><polyline fill="currentColor" points="1 2 2 2 2 6 6 6 6 7 1 7 1 2"></polyline><path fill="none" stroke="currentColor" stroke-width="1.1" d="M2.1,6.548 C3.391,3.29 6.746,1 10.5,1 C15.5,1 19.5,5 19.5,10 C19.5,15 15.5,19 10.5,19 C5.5,19 1.5,15 1.5,10"></path><rect width="1" height="7" x="9" y="4"></rect><path fill="none" stroke="currentColor" stroke-width="1.1" d="M13.018,14.197 L9.445,10.625"></path></svg>`,
    referral: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><line fill="none" stroke="currentColor" stroke-width="1.1" x1="13.4" y1="14" x2="6.3" y2="10.7"></line><line fill="none" stroke="currentColor" stroke-width="1.1" x1="13.5" y1="5.5" x2="6.5" y2="8.8"></line><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="15.5" cy="4.6" r="2.3"></circle><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="15.5" cy="14.8" r="2.3"></circle><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="4.5" cy="9.8" r="2.3"></circle></svg>`,
    calendar: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M 2,3 2,17 18,17 18,3 2,3 Z M 17,16 3,16 3,8 17,8 17,16 Z M 17,7 3,7 3,4 17,4 17,7 Z"></path><rect width="1" height="3" x="6" y="2"></rect><rect width="1" height="3" x="13" y="2"></rect></svg>`,
    clock: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="10" cy="10" r="9"></circle><rect width="1" height="7" x="9" y="4"></rect><path fill="none" stroke="currentColor" stroke-width="1.1" d="M13.018,14.197 L9.445,10.625"></path></svg>`,
    fire: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M9.32 15.65a.8.8 0 0 1-.09-.85c.18-.34.25-.74.2-1.12a2 2 0 0 0-.26-.78 2 2 0 0 0-.54-.6A4 4 0 0 1 7.14 10c-1.7 2.24-1.05 3.51-.23 4.63a.75.75 0 0 1-.01.9 1 1 0 0 1-.4.29 1 1 0 0 1-.48.02 5.4 5.4 0 0 1-2.85-1.6 5 5 0 0 1-.9-1.56A5 5 0 0 1 2 10.9s-.13-2.46 2.84-4.87c0 0 3.51-2.98 2.3-5.18a.6.6 0 0 1 .1-.65.6.6 0 0 1 .63-.15L8 .1a7.6 7.6 0 0 1 2.96 3.5 7.2 7.2 0 0 1 .19 4.52q.49-.44.8-1.03l.03-.06c.2-.48.82-.33 1.05-.02.09.14 2.3 3.35 1.11 6.05a5.5 5.5 0 0 1-1.84 2.03 6 6 0 0 1-2.14.9 1 1 0 0 1-.47-.05 1 1 0 0 1-.38-.29M7.55 7.9a.4.4 0 0 1 .55.15q.06.09.08.2l.04.35c.02.5.02 1.04.22 1.53q.3.76.93 1.3a3 3 0 0 1 1.16 1.42c.22.57.25 1.2.08 1.77a4 4 0 0 0 1.4-.75l.1-.09a3.2 3.2 0 0 0 1.16-2.28 5.3 5.3 0 0 0-.82-2.97q-.39.54-.99.8-.37.15-.78.19a1 1 0 0 1-.43-.1 1 1 0 0 1-.32-.33.8.8 0 0 1-.04-.73c.41-.97.54-2.05.37-3.1A6 6 0 0 0 8.6 2.1c-.14 2.2-2.4 4.25-2.87 4.7l-.22.19c-2.43 1.96-2.26 3.75-2.26 3.83a3.7 3.7 0 0 0 .46 2.05 4 4 0 0 0 1.52 1.54c-.73-1.6-.73-3.52 1.95-6.27z"></path></svg>`,
    inbox: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><polyline fill="none" stroke="currentColor" points="1.4,6.5 10,11 18.6,6.5"></polyline><path d="M 1,4 1,16 19,16 19,4 1,4 Z M 18,15 2,15 2,5 18,5 18,15 Z"></path></svg>`,
    user: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="9.9" cy="6.4" r="4.4"></circle><path fill="none" stroke="currentColor" stroke-width="1.1" d="M1.5,19 C2.3,14.5 5.8,11.2 10,11.2 C14.2,11.2 17.7,14.6 18.5,19.2"></path></svg>`,
    logout: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><polygon points="13 2 3 2 3 17 13 17 13 16 4 16 4 3 13 3 13 2"></polygon><line stroke="currentColor" x1="7.96" y1="9.49" x2="16.96" y2="9.49"></line><polyline fill="none" stroke="currentColor" points="14.17 6.31 17.35 9.48 14.17 12.66"></polyline></svg>`,

    more: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><circle cx="3" cy="10" r="2"></circle><circle cx="10" cy="10" r="2"></circle><circle cx="17" cy="10" r="2"></circle></svg>`,

    // Sharing + social
    facebook: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M11,10h2.6l0.4-3H11V5.3c0-0.9,0.2-1.5,1.5-1.5H14V1.1c-0.3,0-1-0.1-2.1-0.1C9.6,1,8,2.4,8,5v2H5.5v3H8v8h3V10z"></path></svg>`,
    x: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="m15.08,2.1h2.68l-5.89,6.71,6.88,9.1h-5.4l-4.23-5.53-4.84,5.53H1.59l6.24-7.18L1.24,2.1h5.54l3.82,5.05,4.48-5.05Zm-.94,14.23h1.48L6,3.61h-1.6l9.73,12.71h0Z"></path></svg>`,
    google: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M17.86,9.09 C18.46,12.12 17.14,16.05 13.81,17.56 C9.45,19.53 4.13,17.68 2.47,12.87 C0.68,7.68 4.22,2.42 9.5,2.03 C11.57,1.88 13.42,2.37 15.05,3.65 C15.22,3.78 15.37,3.93 15.61,4.14 C14.9,4.81 14.23,5.45 13.5,6.14 C12.27,5.08 10.84,4.72 9.28,4.98 C8.12,5.17 7.16,5.76 6.37,6.63 C4.88,8.27 4.62,10.86 5.76,12.82 C6.95,14.87 9.17,15.8 11.57,15.25 C13.27,14.87 14.76,13.33 14.89,11.75 L10.51,11.75 L10.51,9.09 L17.86,9.09 L17.86,9.09 Z"></path></svg>`,
    whatsapp: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M16.7,3.3c-1.8-1.8-4.1-2.8-6.7-2.8c-5.2,0-9.4,4.2-9.4,9.4c0,1.7,0.4,3.3,1.3,4.7l-1.3,4.9l5-1.3c1.4,0.8,2.9,1.2,4.5,1.2 l0,0l0,0c5.2,0,9.4-4.2,9.4-9.4C19.5,7.4,18.5,5,16.7,3.3 M10.1,17.7L10.1,17.7c-1.4,0-2.8-0.4-4-1.1l-0.3-0.2l-3,0.8l0.8-2.9 l-0.2-0.3c-0.8-1.2-1.2-2.7-1.2-4.2c0-4.3,3.5-7.8,7.8-7.8c2.1,0,4.1,0.8,5.5,2.3c1.5,1.5,2.3,3.4,2.3,5.5 C17.9,14.2,14.4,17.7,10.1,17.7 M14.4,11.9c-0.2-0.1-1.4-0.7-1.6-0.8c-0.2-0.1-0.4-0.1-0.5,0.1c-0.2,0.2-0.6,0.8-0.8,0.9 c-0.1,0.2-0.3,0.2-0.5,0.1c-0.2-0.1-1-0.4-1.9-1.2c-0.7-0.6-1.2-1.4-1.3-1.6c-0.1-0.2,0-0.4,0.1-0.5C8,8.8,8.1,8.7,8.2,8.5 c0.1-0.1,0.2-0.2,0.2-0.4c0.1-0.2,0-0.3,0-0.4C8.4,7.6,7.9,6.5,7.7,6C7.5,5.5,7.3,5.6,7.2,5.6c-0.1,0-0.3,0-0.4,0 c-0.2,0-0.4,0.1-0.6,0.3c-0.2,0.2-0.8,0.8-0.8,2c0,1.2,0.8,2.3,1,2.4c0.1,0.2,1.7,2.5,4,3.5c0.6,0.2,1,0.4,1.3,0.5 c0.6,0.2,1.1,0.2,1.5,0.1c0.5-0.1,1.4-0.6,1.6-1.1c0.2-0.5,0.2-1,0.1-1.1C14.8,12.1,14.6,12,14.4,11.9"></path></svg>`,
    share: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2.47,13.11 C4.02,10.02 6.27,7.85 9.04,6.61 C9.48,6.41 10.27,6.13 11,5.91 L11,2 L18.89,9 L11,16 L11,12.13 C9.25,12.47 7.58,13.19 6.02,14.25 C3.03,16.28 1.63,18.54 1.63,18.54 C1.63,18.54 1.38,15.28 2.47,13.11 L2.47,13.11 Z M5.3,13.53 C6.92,12.4 9.04,11.4 12,10.92 L12,13.63 L17.36,9 L12,4.25 L12,6.8 C11.71,6.86 10.86,7.02 9.67,7.49 C6.79,8.65 4.58,10.96 3.49,13.08 C3.18,13.7 2.68,14.87 2.49,16 C3.28,15.05 4.4,14.15 5.3,13.53 L5.3,13.53 Z"></path></svg>`,

    // Common actions
    copy: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><rect width="12" height="16" fill="none" stroke="currentColor" x="3.5" y="2.5"></rect><polyline fill="none" stroke="currentColor" points="5 0.5 17.5 0.5 17.5 17"></polyline></svg>`,
    gift: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 485 485" xml:space="preserve"><path d="M0 69.9V415h485V70zm455 30v93.3h-35.5a46.3 46.3 0 0 0-74.5-50.3l-47.5 44.7-47.4-44.6-.2-.1a46.3 46.3 0 0 0-74.4 50.3H30V99.9zm-79.2 93.3-8-.2v.2h-32.4l30-28.2q4.6-4.1 10.8-4.2a16.2 16.2 0 0 1 .8 32.4zm-148.6-.2-8 .2H218a16.2 16.2 0 1 1 11.7-28.2l30 28.2h-32.4zM30 385.1v-162h222.5v67.7h30v-67.6h30v107.6h30V223.2H455v162z"></path></svg>`,
    friends: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><line fill="none" stroke="currentColor" stroke-width="1.1" x1="13.4" y1="14" x2="6.3" y2="10.7"></line><line fill="none" stroke="currentColor" stroke-width="1.1" x1="13.5" y1="5.5" x2="6.5" y2="8.8"></line><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="15.5" cy="4.6" r="2.3"></circle><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="15.5" cy="14.8" r="2.3"></circle><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="4.5" cy="9.8" r="2.3"></circle></svg>`,
    check: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><polyline fill="none" stroke="currentColor" stroke-width="1.1" points="4,10 8,15 17,4"></polyline></svg>`,
};

const IUE_BadgeSVG = `<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" class="iue-badge-icon"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>`;

function initUserEngineIcons(container = document) {
	container.querySelectorAll('[data-iue-icon]').forEach(el => {
		const name = el.getAttribute('data-iue-icon');
		if (IUE_Icons[name]) {
			el.innerHTML = IUE_Icons[name];
			el.setAttribute('aria-hidden', 'true');
			el.classList.add('iue-icon-rendered');
		}
	});
}

function initUserEngineBadges(container = document) {
	container.querySelectorAll('.iue-badge-level:not(.iue-badge-enhanced)').forEach(el => {
		el.classList.add('iue-badge-enhanced');

		// Lấy nội dung hiện có (Lv.x)
		const text = el.textContent;
		el.textContent = '';

		// Tạo nội dung mới: SVG + span
		const svgWrap = document.createElement('span');
		svgWrap.innerHTML = IUE_BadgeSVG;
		el.appendChild(svgWrap.firstElementChild);

		const textSpan = document.createElement('span');
		textSpan.className = 'iue-badge-number';
		textSpan.textContent = text;
		el.appendChild(textSpan);
	});
}

document.addEventListener('DOMContentLoaded', () => {
	initUserEngineIcons();
	initUserEngineBadges();
});
