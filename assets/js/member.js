// Support dark mode
function initApplyDarkTheme() {
    const config = window.InitPluginSuiteUserEngineConfig || {};
    const theme = config.theme;

    const dashboard = document.querySelector('.iue-dashboard');
    if (!dashboard) return;

    dashboard.classList.remove('dark');

    if (theme === 'dark') {
        dashboard.classList.add('dark');
    } else if (theme === 'auto') {
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (prefersDark) {
            dashboard.classList.add('dark');
        }
    }
}

// Toggle mini dashboard
function initDashboardToggle() {
    const avatar = document.getElementById('init-user-engine-avatar');
    const dashboard = document.querySelector('.iue-dashboard');
    if (!avatar || !dashboard) return;

    avatar.addEventListener('click', function (e) {
        e.stopPropagation();
        dashboard.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
        if (!avatar.contains(e.target) && !dashboard.contains(e.target)) {
            dashboard.classList.remove('open');
        }
    });
}

// Handle menu clicks (mở modal sau này)
function initMenuClick() {
    document.addEventListener('click', function (e) {
        const target = e.target.closest('.iue-menu-link, .iue-level-badge-link');
        if (!target) return;

        e.preventDefault();
        const action = target.dataset.action;
        if (!action) return;

        const dashboard = document.querySelector('.iue-dashboard');
        if (dashboard && dashboard.classList.contains('open')) {
            dashboard.classList.remove('open');
        }

        if (action === 'vip') {
            loadVipModal();
            return;
        }

        if (action === 'daily-task') {
            loadDailyTasks();
            return;
        }

        if (action === 'history') {
            loadTransactionHistory();
            return;
        }

        if (action === 'inbox') {
            loadInbox();
            return;
        }

        if (action === 'referral') {
            loadReferral();
            return;
        }

        if (action === 'exp-log') {
            loadExpLog();
            return;
        }

        if (action === 'exchange') {
            loadExchangeModal();
            return;
        }

        if (action === 'edit-profile') {
            loadEditProfileModal();
            return;
        }
    });
}

// Hệ thống phím tắt Alt + Key
document.addEventListener('keydown', function (e) {
    if (!e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) return;

    const key = e.key.toLowerCase();

    if (key === 'u') {
        e.preventDefault();
        const dashboard = document.querySelector('.iue-dashboard');
        if (dashboard) {
            dashboard.classList.toggle('open');
        }
        return;
    }

    if (key === 'a') {
        e.preventDefault();
        iueShowAvatarModal();
        return;
    }

    if (key === 'e') {
        e.preventDefault();
        loadExpLog();
        return;
    }

    if (key === 'v') {
        e.preventDefault();
        loadVipModal();
        return;
    }

    if (key === 'd') {
        e.preventDefault();
        loadDailyTasks();
        return;
    }

    if (key === 'h') {
        e.preventDefault();
        loadTransactionHistory();
        return;
    }

    if (key === 'i') {
        e.preventDefault();
        loadInbox();
        return;
    }

    if (key === 'r') {
        e.preventDefault();
        loadReferral();
        return;
    }

    if (key === 'p') {
        e.preventDefault();
        loadEditProfileModal();
        return;
    }

    if (key === 'x') {
        e.preventDefault();
        loadExchangeModal();
        return;
    }

    if (/^[1-9]$/.test(key)) {
        const index = parseInt(key, 10) - 1;
        const menuLinks = document.querySelectorAll('.iue-menu-link');
        if (menuLinks[index]) {
            e.preventDefault();
            menuLinks[index].click();
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    document.body.addEventListener('click', (e) => {
        const wrapper = e.target.closest('.iue-avatar-wrapper[data-iue-avatar-trigger]');
        if (wrapper) {
            e.preventDefault();
            if (typeof iueShowAvatarModal === 'function') {
                const dashboard = document.querySelector('.iue-dashboard');
                if (dashboard && dashboard.classList.contains('open')) {
                    dashboard.classList.remove('open');
                }
                
                iueShowAvatarModal();
            } else {
                console.warn('[InitUserEngine] Avatar modal function not found: iueShowAvatarModal()');
            }
        }
    });
});

// Hook event để bắn hiệu ứng
document.addEventListener('iue:checkin:success', function (e) {
    const streak = parseInt(e.detail.streak || 0, 10);

    if (typeof runEffect !== 'function') return;

    if (streak > 0 && streak % 30 === 0) {
        runEffect('celebrationBurst');
    } else if (streak > 0 && streak % 7 === 0) {
        runEffect('cannonBlast');
    }
});

document.addEventListener('iue:reward:claimed', function () {
    if (typeof runEffect !== 'function') return;
    runEffect('firework');
});

document.addEventListener('iue:level:up', function () {
    if (typeof runEffect !== 'function') return;
    runEffect('starlightBurst');
});

// MODAL
function showUserEngineModal(title, contentHTML, size = 'medium') {
    const modalId = 'iue-modal';
    let modal = document.getElementById(modalId);

    // Nếu chưa tồn tại → tạo modal mới
    if (!modal) {
        modal = document.createElement('div');
        modal.id = modalId;
        modal.innerHTML = `
            <div class="iue-overlay"></div>
            <div class="iue-modal-content">
                <div class="iue-modal-header">
                    <h3></h3>
                    <button class="iue-modal-close" aria-label="Close">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path d="m21 21-9-9m0 0L3 3m9 9 9-9m-9 9-9 9"
                                  stroke="currentColor"
                                  stroke-width="1.1"
                                  stroke-linecap="round"
                                  stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
                <div class="iue-modal-body"></div>
            </div>
        `;
        document.body.appendChild(modal);
        // document.body.style.overflow = 'hidden';

        // Sự kiện đóng modal
        modal.querySelector('.iue-modal-close')?.addEventListener('click', closeModal);
        modal.querySelector('.iue-overlay')?.addEventListener('click', closeModal);
        document.addEventListener('keydown', handleEsc);
    }

    // Gán class dark nếu cần (vào #iue-modal)
    applyModalDarkTheme(modal);

    // Kích thước modal-content
    const modalContent = modal.querySelector('.iue-modal-content');
    modalContent.className = 'iue-modal-content'; // reset
    modalContent.classList.add(`iue-modal-${size}`);

    // Nội dung
    modal.querySelector('h3').innerText = title;
    modal.querySelector('.iue-modal-body').innerHTML = contentHTML;
}

function applyModalDarkTheme(modal) {
    const config = window.InitPluginSuiteUserEngineConfig || {};
    const theme = config.theme;

    modal.classList.remove('dark');

    if (theme === 'dark') {
        modal.classList.add('dark');
    } else if (theme === 'auto') {
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (prefersDark) {
            modal.classList.add('dark');
        }
    }
}

function closeModal() {
    const modal = document.getElementById('iue-modal');
    if (modal) modal.remove();
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleEsc);
}

function handleEsc(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
}

// ICON
const IUE_Icons = {
    eye: '<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><circle fill="none" stroke="currentColor" cx="10" cy="10" r="3.45"></circle><path fill="none" stroke="currentColor" d="m19.5,10c-2.4,3.66-5.26,7-9.5,7h0,0,0c-4.24,0-7.1-3.34-9.49-7C2.89,6.34,5.75,3,9.99,3h0,0,0c4.25,0,7.11,3.34,9.5,7Z"></path></svg>',
    eyeoff: '<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path fill="none" stroke="currentColor" d="m7.56,7.56c.62-.62,1.49-1.01,2.44-1.01,1.91,0,3.45,1.54,3.45,3.45,0,.95-.39,1.82-1.01,2.44"></path><path fill="none" stroke="currentColor" d="m19.5,10c-2.4,3.66-5.26,7-9.5,7h0,0,0c-4.24,0-7.1-3.34-9.49-7C2.89,6.34,5.75,3,9.99,3h0,0,0c4.25,0,7.11,3.34,9.5,7Z"></path><line fill="none" stroke="currentColor" x1="2.5" y1="2.5" x2="17.5" y2="17.5"></line></svg>',
    
    coin: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><ellipse fill="none" stroke="currentColor" cx="10" cy="4.64" rx="7.5" ry="3.14"></ellipse><path fill="none" stroke="currentColor" d="M17.5,8.11 C17.5,9.85 14.14,11.25 10,11.25 C5.86,11.25 2.5,9.84 2.5,8.11"></path><path fill="none" stroke="currentColor" d="M17.5,11.25 C17.5,12.99 14.14,14.39 10,14.39 C5.86,14.39 2.5,12.98 2.5,11.25"></path><path fill="none" stroke="currentColor" d="M17.49,4.64 L17.5,14.36 C17.5,16.1 14.14,17.5 10,17.5 C5.86,17.5 2.5,16.09 2.5,14.36 L2.5,4.64"></path></svg>`,
    cash: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><rect width="17" height="12" fill="none" stroke="currentColor" x="1.5" y="4.5"></rect><rect width="18" height="3" x="1" y="7"></rect></svg>`,
    exchange: `<svg width="20" height="20" viewBox="0 0 17 17" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M6 15.04V8H5v7.04L1.35 11.4l-.7.7 4.85 4.86 4.85-4.86-.7-.7zm-.51.5h.02zM15.65 5.6 12 1.96v7.1h-1v-7.1L7.35 5.6l-.7-.7L11.5.04l4.85 4.86z"/></svg>`,
    star: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><polygon fill="none" stroke="currentColor" stroke-width="1.01" points="10 2 12.63 7.27 18.5 8.12 14.25 12.22 15.25 18 10 15.27 4.75 18 5.75 12.22 1.5 8.12 7.37 7.27"></polygon></svg>`,
    camera: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="10" cy="10.8" r="3.8"></circle><path fill="none" stroke="currentColor" d="M1,4.5 C0.7,4.5 0.5,4.7 0.5,5 L0.5,17 C0.5,17.3 0.7,17.5 1,17.5 L19,17.5 C19.3,17.5 19.5,17.3 19.5,17 L19.5,5 C19.5,4.7 19.3,4.5 19,4.5 L13.5,4.5 L13.5,2.9 C13.5,2.6 13.3,2.5 13,2.5 L7,2.5 C6.7,2.5 6.5,2.6 6.5,2.9 L6.5,4.5 L1,4.5 L1,4.5 Z"></path></svg>`,
    diamond: `<svg width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M15 3h-.4l-.2.2h-.1v.1l-.1.1L1 17.4l-.6.5.5.7 23 29.7q.2.5.7.7l.4.6.5-.6q.5-.2.5-.7l23-29.7.6-.7-.6-.6L36 3.5l-.2-.2-.2-.2V3zm.8 2h7l-9 9.7zm11.5 0h6.9l2 9.7zm-2.3.5L35.7 17H14.2zm11.7 1.8L46 17h-7.2zm-23.4 0L11.2 17H4zM3.8 19h7.5L21 41.2zm9.7 0h23L25 45.5zm25.1 0h7.6L29 41.2z" transform="scale(5.12)" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode:normal"/></svg>`,
    task: `<svg width="20" height="20" viewBox="0 0 28 28" fill="none"><path d="M4 5.25C4 3.45 5.46 2 7.25 2h13.5C22.55 2 24 3.46 24 5.25v12.13q-.18.12-.34.28l-1.16 1.16V5.25c0-.97-.78-1.75-1.75-1.75H7.25c-.97 0-1.75.78-1.75 1.75v17.5c0 .97.78 1.75 1.75 1.75h8.07l1.5 1.5H7.25A3.25 3.25 0 0 1 4 22.75z" fill="currentColor"/><path d="M10.5 8.75a1.25 1.25 0 1 1-2.5 0 1.25 1.25 0 0 1 2.5 0m-1.25 6.5a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5m0 5.25a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5M12.75 8a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5zM12 14c0-.41.34-.75.75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 12 14m.75 4.5a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5zm13.03 1.28-6 6a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 0 1 1.06-1.06l2.47 2.47 5.47-5.47a.75.75 0 1 1 1.06 1.06" fill="currentColor"/></svg>`,
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

// AVATAR
function iueShowAvatarModal() {
    const wrapper = document.querySelector('.iue-avatar-wrapper[data-iue-avatar-trigger]');
    const img = wrapper?.querySelector('img');
    let currentSrc = img?.src || '';

    // Nếu là gravatar → nâng size lên 100 cho rõ preview
    if (currentSrc.includes('gravatar.com') && currentSrc.includes('s=50')) {
        currentSrc = currentSrc.replace('s=50', 's=100');
    }

    // Nếu là avatar custom size 50 → đổi sang size 80 cho nét
    if (currentSrc.match(/-50\.(jpg|jpeg|png|webp)$/)) {
        currentSrc = currentSrc.replace(/-50\.(jpg|jpeg|png|webp)$/, '-80.$1');
    }

    const html = `
        <div class="iue-avatar-upload-box">
            <div class="iue-avatar-dropzone" id="iueAvatarDropzone">
                <p>${InitUserEngineData.i18n.avatar_drop_text || 'Drop image here or click to upload'}</p>
                <input type="file" accept="image/*" id="iueAvatarFileInput">
            </div>
            <div class="iue-avatar-preview-wrapper" id="iueAvatarPreviewWrapper">
                <img id="iueAvatarPreviewImg" src="${currentSrc}" alt="Preview">
            </div>
            <div class="iue-avatar-actions">
                <button class="iue-avatar-upload-btn" id="iueAvatarUploadBtn">
                    ${InitUserEngineData.i18n.avatar_save || 'Save Avatar'}
                </button>
                <button class="iue-avatar-remove-btn" id="iueAvatarRemoveBtn">
                    ${InitUserEngineData.i18n.avatar_remove || 'Remove Avatar'}
                </button>
            </div>
        </div>
    `;

    showUserEngineModal(
        InitUserEngineData.i18n.upload_avatar || 'Upload Avatar',
        html
    );

    const removeBtn = document.getElementById('iueAvatarRemoveBtn');
    if (removeBtn) {
        setupConfirmableButton(removeBtn, {
            onConfirm: () => {
                removeBtn.disabled = true;
                removeBtn.textContent = InitUserEngineData.i18n.avatar_removing || 'Removing...';

                fetch(`${InitUserEngineData.rest_url}/avatar/remove`, {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': InitUserEngineData.nonce
                    },
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.url) throw new Error('Remove failed');
                    document.querySelectorAll('img.iue-avatar-img').forEach(img => {
                        img.src = data.url;
                    });
                    document.querySelector('#iue-modal .iue-modal-close')?.click();
                })
                .catch(err => {
                    console.error('[InitUserEngine] Remove avatar failed:', err);
                    alert(InitUserEngineData.i18n.avatar_remove_fail || 'Failed to remove avatar.');
                })
                .finally(() => {
                    removeBtn.disabled = false;
                    removeBtn.textContent = InitUserEngineData.i18n.avatar_remove || 'Remove Avatar';
                });
            }
        });
    }
}

// Preview file khi chọn
document.addEventListener('change', (e) => {
    const input = e.target;
    if (input.id !== 'iueAvatarFileInput') return;

    const file = input.files[0];
    if (!file || !file.type.startsWith('image/')) return;

    if (file.size > 10 * 1024 * 1024) {
        InitUserEngineToast.show(
            InitUserEngineData.i18n.avatar_too_large || 'Image too large (max 10MB)',
            'warning'
        );
        return;
    }

    const previewImg = document.getElementById('iueAvatarPreviewImg');
    if (previewImg) {
        previewImg.src = URL.createObjectURL(file);
    }
});

// Upload avatar
document.addEventListener('click', (e) => {
    const btn = e.target.closest('#iueAvatarUploadBtn');
    if (!btn) return;

    const input = document.getElementById('iueAvatarFileInput');
    const file = input?.files[0];

    if (!file || !file.type.startsWith('image/')) {
        InitUserEngineToast.show(
            InitUserEngineData.i18n.avatar_invalid || 'Please select a valid image.',
            'error'
        );
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        InitUserEngineToast.show(
            InitUserEngineData.i18n.avatar_too_large || 'Image too large (max 10MB)',
            'warning'
        );
        return;
    }

    const formData = new FormData();
    formData.append('avatar', file);

    btn.disabled = true;
    btn.textContent = InitUserEngineData.i18n.avatar_uploading || 'Uploading...';

    fetch(`${InitUserEngineData.rest_url}/avatar`, {
        method: 'POST',
        headers: {
            'X-WP-Nonce': InitUserEngineData.nonce
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const finalUrl = data.url_80 || data.url_50;
        if (!finalUrl) throw new Error('Upload failed');

        document.querySelectorAll('img.iue-avatar-img').forEach(img => {
            img.src = finalUrl;
        });

        document.querySelector('#iue-modal .iue-modal-close')?.click();
    })
    .catch(err => {
        console.error('[InitUserEngine] Upload avatar failed:', err);
        InitUserEngineToast.show(
            InitUserEngineData.i18n.avatar_upload_fail || 'Upload failed. Please try again.',
            'error'
        );
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = InitUserEngineData.i18n.avatar_save || 'Save Avatar';
    });
});

// CHECKIN
function initCheckin() {
    const checkinBox = document.querySelector('.iue-checkin-box');
    if (!checkinBox) return;

    const button      = checkinBox.querySelector('.iue-checkin-button');
    const timer       = checkinBox.querySelector('.iue-checkin-timer');
    const countdownEl = timer.querySelector('.iue-timer-countdown');
    const streakEl    = checkinBox.querySelector('.iue-checkin-streak');
    const coinEl      = document.querySelector('.iue-value-coin');
    const cashEl      = document.querySelector('.iue-value-cash');

    const STORAGE_KEY_REMAINING = 'iue_checkin_remaining_seconds';
    const STORAGE_KEY_DATE = 'iue_checkin_date';
    const COUNTDOWN = InitUserEngineData.online_minutes * 60;

    let countdownInterval = null;
    let remainingSeconds = 0;

    function clearCountdown() {
        if (countdownInterval) clearInterval(countdownInterval);
        countdownInterval = null;
    }

    function updateCountdownDisplay(seconds) {
        const min = String(Math.floor(seconds / 60)).padStart(2, '0');
        const sec = String(seconds % 60).padStart(2, '0');
        countdownEl.textContent = `${min}:${sec}`;
    }

    function saveRemainingTime(seconds) {
        const today = new Date().toDateString();
        localStorage.setItem(STORAGE_KEY_REMAINING, seconds);
        localStorage.setItem(STORAGE_KEY_DATE, today);
    }

    function getRemainingTime() {
        const savedDate = localStorage.getItem(STORAGE_KEY_DATE);
        const today = new Date().toDateString();
        
        // Nếu đã qua ngày mới, xóa dữ liệu cũ
        if (savedDate !== today) {
            localStorage.removeItem(STORAGE_KEY_REMAINING);
            localStorage.removeItem(STORAGE_KEY_DATE);
            return 0;
        }
        
        const remaining = parseInt(localStorage.getItem(STORAGE_KEY_REMAINING) || '0', 10);
        return remaining;
    }

    function clearStoredTime() {
        localStorage.removeItem(STORAGE_KEY_REMAINING);
        localStorage.removeItem(STORAGE_KEY_DATE);
    }

    function startCountdown(initialSeconds) {
        clearCountdown();
        timer.classList.remove('iue-hidden');
        button.classList.add('iue-hidden');

        remainingSeconds = initialSeconds;
        updateCountdownDisplay(remainingSeconds);

        countdownInterval = setInterval(() => {
            // Chỉ đếm khi tab đang active
            if (document.hidden) {
                return;
            }

            remainingSeconds--;
            
            if (remainingSeconds > 0) {
                updateCountdownDisplay(remainingSeconds);
                // Lưu thời gian còn lại sau mỗi giây
                saveRemainingTime(remainingSeconds);
            } else {
                clearCountdown();
                clearStoredTime();
                timer.classList.add('iue-hidden');
                claimReward();
            }
        }, 1000);

        // Lưu trạng thái khi tab bị ẩn
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && remainingSeconds > 0) {
                saveRemainingTime(remainingSeconds);
            }
        });

        // Lưu trạng thái khi tắt trang
        window.addEventListener('beforeunload', () => {
            if (remainingSeconds > 0) {
                saveRemainingTime(remainingSeconds);
            }
        });
    }

    function claimReward() {
        fetch(InitUserEngineData.rest_url + '/claim-reward', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': InitUserEngineData.nonce
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'reward_claimed') {
                if (typeof data.coin !== 'undefined') coinEl.textContent = iueFmt(data.coin);
                if (typeof data.cash !== 'undefined') cashEl.textContent = iueFmt(data.cash);
                if (typeof data.level !== 'undefined') updateUserLevelBadge(data.level);

                document.dispatchEvent(new CustomEvent('iue:reward:claimed', { detail: data }));
                if (parseInt(data.level_up_count || 0, 10) > 0) {
                    document.dispatchEvent(new CustomEvent('iue:level:up', { detail: data }));
                }

                InitUserEngineToast.show(InitUserEngineData.i18n.reward_claimed, 'success');
            }
        })
        .catch(err => {
            console.error('Claim reward failed', err);
            InitUserEngineToast.show(InitUserEngineData.i18n.error, 'error');
        });
    }

    function checkExistingCountdown() {
        const checkin = checkinBox.dataset.checkin === '1';
        const rewarded = checkinBox.dataset.rewarded === '1';

        if (!checkin || rewarded) return false;

        const remaining = getRemainingTime();
        
        if (remaining > 0) {
            startCountdown(remaining);
            return true;
        } else if (remaining === 0 && localStorage.getItem(STORAGE_KEY_DATE)) {
            // Nếu đã hết thời gian nhưng chưa claim reward
            clearStoredTime();
            claimReward();
            return false;
        }

        return false;
    }

    if (checkExistingCountdown()) {
        return;
    }

    button.addEventListener('click', () => {
        button.disabled = true;
        button.textContent = InitUserEngineData.i18n.checking_in || '...';

        fetch(InitUserEngineData.rest_url + '/checkin', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': InitUserEngineData.nonce
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                checkinBox.dataset.checkin = '1';
                streakEl.textContent = data.streak;

                if (typeof data.coin !== 'undefined') coinEl.textContent = iueFmt(data.coin);
                if (typeof data.cash !== 'undefined') cashEl.textContent = iueFmt(data.cash);
                if (typeof data.level !== 'undefined') updateUserLevelBadge(data.level);

                document.dispatchEvent(new CustomEvent('iue:checkin:success', { detail: data }));
                if (parseInt(data.level_up_count || 0, 10) > 0) {
                    document.dispatchEvent(new CustomEvent('iue:level:up', { detail: data }));
                }

                InitUserEngineToast.show(InitUserEngineData.i18n.checkin_success, 'success');
                startCountdown(COUNTDOWN);
            } else {
                button.disabled = false;
                button.textContent = InitUserEngineData.i18n.already_checked_in || 'Checked in';
                InitUserEngineToast.show(InitUserEngineData.i18n.already_checked_in, 'info');
            }
        })
        .catch(err => {
            console.error(err);
            button.disabled = false;
            button.textContent = InitUserEngineData.i18n.checkin || 'Check-in';
            InitUserEngineToast.show(InitUserEngineData.i18n.error, 'error');
        });
    });
}

// DAILY TASK
function loadDailyTasks() {
    const endpoint = `${InitUserEngineData.rest_url}/daily-tasks`;
    const headers = {
        'Content-Type': 'application/json',
        'X-WP-Nonce': InitUserEngineData.nonce
    };

    showUserEngineModal(InitUserEngineData.i18n.daily_task_title, `
        <div class="iue-loading">
            <svg width="30" height="30" viewBox="0 0 25 25" fill="none">
                <path d="M4.5 12.5a8 8 0 1 0 8-8" stroke="currentColor" stroke-width="1"/>
            </svg>
        </div>`);

    fetch(endpoint, {
        method: 'GET',
        credentials: 'include',
        headers
    })
    .then(res => res.json())
    .then(res => {
        const container = document.querySelector('#iue-modal .iue-modal-body');
        if (!container) return;

        if (!Array.isArray(res)) return renderError(container, 'Invalid response format.');
        if (res.length === 0) return renderError(container, 'No daily tasks found.');

        const listEl = document.createElement('div');
        listEl.className = 'iue-transaction-list'; // dùng chung class
        listEl.innerHTML = res.map(renderDailyTaskItem).join('');

        container.innerHTML = '';
        container.appendChild(listEl);
    })
    .catch(err => {
        console.error('[Init User Engine] Failed to load daily tasks:', err);
        const container = document.querySelector('#iue-modal .iue-modal-body');
        if (container) renderError(container, 'Failed to load daily tasks.');
    });
}

function renderDailyTaskItem(task) {
    const {
        title = 'Unknown task',
        completed = false,
        reward = { type: 'coin', amount: 0 }
    } = task;

    const label = reward.type === 'cash'
        ? InitUserEngineData.label_cash || 'Cash'
        : InitUserEngineData.label_coin || 'Coin';

    const amountStr = iueFmt(reward.amount || 0);
    const statusEl = completed
        ? `<span class="iue-amount-positive">+${amountStr} ${label}</span>`
        : `<span class="iue-amount-negative"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><g stroke="currentColor" stroke-width="1.2"><circle cx="12" cy="12" r="10"/><path d="m9 9 6 6m0-6-6 6" stroke-linecap="round"/></g></svg></span>`;

    return `
        <div class="iue-transaction-item">
            <div class="iue-trans-row">
                <span class="iue-trans-message">${title}</span>
                ${statusEl}
            </div>
        </div>
    `;
}

// INBOX
function loadInbox(page = 1) {
    const perPage = 20;
    const endpoint = `${InitUserEngineData.rest_url}/inbox?page=${page}&per_page=${perPage}`;
    const headers = {
        'Content-Type': 'application/json',
        'X-WP-Nonce': InitUserEngineData.nonce
    };

    showUserEngineModal(InitUserEngineData.i18n.inbox_title, `<div class="iue-loading"><svg width="30" height="30" viewBox="0 0 25 25" fill="none"><path d="M4.5 12.5a8 8 0 1 0 8-8" stroke="currentColor" stroke-width="1"/></svg></div>`);

    fetch(endpoint, { method: 'GET', credentials: 'include', headers })
        .then(res => res.json())
        .then(res => {
            const container = document.querySelector('#iue-modal .iue-modal-body');
            if (!container) return;

            const { data, total_pages, page } = res;

            if (!Array.isArray(data) || data.length === 0) {
                container.innerHTML = `<p class="iue-empty">${InitUserEngineData.i18n.no_messages}</p>`;
                return;
            }

            const listEl = document.createElement('div');
            listEl.className = 'iue-inbox-list';
            listEl.innerHTML = data.map(renderInboxItem).join('');

            const controls = `
                <div class="iue-inbox-controls">
                    <button class="iue-mark-all-read" type="button">${InitUserEngineData.i18n.mark_all_read}</button>
                    <button class="iue-delete-all" type="button">${InitUserEngineData.i18n.delete_all}</button>
                </div>
            `;

            container.innerHTML = controls;
            container.appendChild(listEl);

            const pagination = renderPagination({
                page,
                totalPages: total_pages,
                onClick: loadInbox
            });

            if (pagination) container.appendChild(pagination);

            attachInboxEvents(container);
            initUserEngineIcons(container);
        })
        .catch(err => {
            console.error('[Init User Engine] Failed to load inbox:', err);
            const container = document.querySelector('#iue-modal .iue-modal-body');
            if (container) {
                container.innerHTML = `<p class="iue-error">${InitUserEngineData.i18n.load_inbox_error}</p>`;
            }
        });
}

function renderInboxItem(entry) {
    const id = entry.id || 0;
    const title = entry.title || 'No title';
    const content = entry.content || '';
    const time = entry.time;
    const isRead = entry.status === 'read';
    const link = entry.link || null;
    const priorityClass = entry.priority === 'high' ? ' iue-high-priority' : '';
    const pinnedClass = entry.pinned ? ' iue-pinned' : '';

    const header = `
        <div class="iue-inbox-header">
            <strong>${title}</strong>
            <small>${time}</small>
        </div>
    `;

    const body = `<div class="iue-inbox-content">${content}</div>`;

    const actions = `
        <div class="iue-inbox-actions">
            <button class="iue-inbox-more" data-id="${id}" aria-haspopup="true">
                <span class="iue-icon" data-iue-icon="more"></span>
            </button>
            <div class="iue-inbox-menu" data-id="${id}" hidden>
                <button class="iue-inbox-read" data-id="${id}" ${isRead ? 'disabled' : ''}>${InitUserEngineData.i18n.mark_as_read}</button>
                <button class="iue-inbox-delete" data-id="${id}">${InitUserEngineData.i18n.delete}</button>
            </div>
        </div>
    `;

    const contentBlock = `
        <div class="iue-inbox-content-wrap">
            ${link ? `<a href="${link}" class="iue-inbox-link" target="_blank" rel="noopener noreferrer">${header + body}</a>` : header + body}
        </div>
    `;

    return `
        <div class="iue-inbox-item${isRead ? '' : ' iue-unread'}${priorityClass}${pinnedClass}" data-id="${id}">
            ${contentBlock}
            ${actions}
        </div>
    `;
}

function attachInboxEvents(container) {
    // Dropdown toggle
    container.querySelectorAll('.iue-inbox-more')?.forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            const id = btn.dataset.id;
            if (!id) return;

            // Close all others
            document.querySelectorAll('.iue-inbox-menu').forEach(menu => {
                if (menu.dataset.id !== id) menu.hidden = true;
            });

            const menu = container.querySelector(`.iue-inbox-menu[data-id="${id}"]`);
            if (menu) {
                menu.hidden = !menu.hidden;
            }
        });
    });

    // Click outside to close all menus
    document.addEventListener('click', e => {
        if (!e.target.closest('.iue-inbox-actions')) {
            document.querySelectorAll('.iue-inbox-menu').forEach(menu => menu.hidden = true);
        }
    });

    // Xoá từng tin
    container.querySelectorAll('.iue-inbox-delete')?.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            if (!id) return;
            deleteInboxItem(id, btn);
        });
    });

    // Đánh dấu đã đọc từng tin
    container.querySelectorAll('.iue-inbox-read')?.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            if (!id) return;
            markInboxRead(id, btn);
        });
    });

    // Đánh dấu là đã đọc khi click vào link (nếu chưa đọc)
    container.querySelectorAll('.iue-inbox-link')?.forEach(link => {
        link.addEventListener('click', e => {
            const item = link.closest('.iue-inbox-item');
            if (!item || item.classList.contains('iue-unread') === false) return;

            const id = item.dataset.id;
            if (!id) return;

            // Gọi API đánh dấu đã đọc (nhưng không reload UI)
            fetch(`${InitUserEngineData.rest_url}/inbox/mark-read`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': InitUserEngineData.nonce
                },
                body: JSON.stringify({ id })
            }).then(() => {
                item.classList.remove('iue-unread');
                updateInboxUnreadIndicator();
            });
        });
    });

    // Đánh dấu tất cả đã đọc
    const markAllBtn = container.querySelector('.iue-mark-all-read');
    if (markAllBtn) {
        setupConfirmableButton(markAllBtn, { onConfirm: markInboxAllRead });
    }

    // Xoá tất cả
    const deleteAllBtn = container.querySelector('.iue-delete-all');
    if (deleteAllBtn) {
        setupConfirmableButton(deleteAllBtn, { onConfirm: deleteInboxAll });
    }
}

function deleteInboxItem(id, btn) {
    btn.disabled = true;
    fetch(`${InitUserEngineData.rest_url}/inbox/delete`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': InitUserEngineData.nonce
        },
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'deleted') {
            const el = btn.closest('.iue-inbox-item');
            if (el) el.remove();
            updateInboxUnreadIndicator();
        }
    })
    .catch(err => console.error('Delete inbox item failed', err));
}

function deleteInboxAll() {
    fetch(`${InitUserEngineData.rest_url}/inbox/delete-all`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': InitUserEngineData.nonce
        }
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'all_deleted') {
            InitUserEngineToast.show(InitUserEngineData.i18n.delete_all_success, 'success');
            loadInbox();
            updateInboxUnreadIndicator();
        }
    })
    .catch(err => {
        console.error('Delete all inbox failed', err);
        InitUserEngineToast.show(InitUserEngineData.i18n.error, 'error');
    });
}

function markInboxRead(id, btn) {
    btn.disabled = true;
    fetch(`${InitUserEngineData.rest_url}/inbox/mark-read`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': InitUserEngineData.nonce
        },
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'marked') {
            const el = btn.closest('.iue-inbox-item');
            if (el) el.classList.remove('iue-unread');
            updateInboxUnreadIndicator();
        }
    })
    .catch(err => console.error('Mark inbox read failed', err));
}

function markInboxAllRead() {
    fetch(`${InitUserEngineData.rest_url}/inbox/mark-all-read`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': InitUserEngineData.nonce
        }
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'all_marked') {
            InitUserEngineToast.show(InitUserEngineData.i18n.mark_all_read_success, 'success');
            loadInbox();
            updateInboxUnreadIndicator();
        }
    })
    .catch(err => console.error('Mark all inbox read failed', err));
}

function updateInboxUnreadIndicator() {
    const unreadDot = document.querySelector('.iue-unread-dot');
    const menuInbox = document.querySelector('.iue-menu-link[data-action="inbox"]');
    const badge     = menuInbox?.querySelector('.iue-badge');
    const avatar    = document.getElementById('init-user-engine-avatar');

    const stillUnread = document.querySelectorAll('.iue-inbox-item.iue-unread').length;

    if (stillUnread <= 0) {
        unreadDot?.remove();
        badge?.remove();
        avatar?.classList.remove('iue-has-unread');
    } else {
        // Nếu chưa có badge thì tạo luôn
        if (!badge) {
            const span = document.createElement('span');
            span.className = 'iue-badge';
            span.textContent = iueFmt(stillUnread);
            menuInbox?.querySelector('span')?.appendChild(span);
        } else {
            badge.textContent = iueFmt(stillUnread);
        }

        // Nếu chưa có dot thì tạo lại
        if (!unreadDot && avatar && !avatar.querySelector('.iue-unread-dot')) {
            const dot = document.createElement('span');
            dot.className = 'iue-unread-dot';
            avatar.appendChild(dot);
        }

        avatar?.classList.add('iue-has-unread');
    }
}

// LEVEL
function loadExpLog(page = 1) {
    const badgeEl = document.querySelector('.iue-level-badge-link');
    if (!badgeEl) return;

    const i18n = InitUserEngineData?.i18n || {};
    const currentExp = parseInt(badgeEl.dataset.exp || 0, 10);
    const currentLevel = parseInt(badgeEl.dataset.level || 1, 10);
    const expRequired = parseInt(badgeEl.dataset.expMax || (1000 + (currentLevel - 1) * 500), 10);
    const percent = Math.min(100, Math.round((currentExp / expRequired) * 100));
    const perPage = 20;

    const endpoint = `${InitUserEngineData.rest_url}/exp-log?page=${page}&per_page=${perPage}`;
    const headers = {
        'Content-Type': 'application/json',
        'X-WP-Nonce': InitUserEngineData.nonce
    };

    showUserEngineModal(i18n.exp_log_title || 'Experience Log', `<div class="iue-loading"><svg width="30" height="30" viewBox="0 0 25 25" fill="none"><path d="M4.5 12.5a8 8 0 1 0 8-8" stroke="currentColor" stroke-width="1"/></svg></div>`);

    fetch(endpoint, {
        method: 'GET',
        credentials: 'include',
        headers
    })
    .then(res => res.json())
    .then(res => {
        const container = document.querySelector('#iue-modal .iue-modal-body');
        if (!container) return;

        const { data, total_pages, page } = res;

        if (!Array.isArray(data)) {
            renderError(container, i18n.exp_log_load_fail || 'Failed to load experience log.');
            return;
        }

        const expBar = `
            <div class="iue-exp-progress">
                <div class="iue-exp-info">
                    ${('Level %d – %d / %d EXP').replace('%d', currentLevel).replace('%d', currentExp).replace('%d', expRequired)}
                </div>
                <div class="iue-exp-bar">
                    <div class="iue-exp-bar-fill" style="width:${percent}%"></div>
                </div>
            </div>
        `;

        const listEl = document.createElement('div');
        listEl.className = 'iue-exp-log-list';

        if (data.length === 0) {
            listEl.innerHTML = `<p class="iue-empty-log">${i18n.no_exp_log || 'No EXP activity yet.'}</p>`;
        } else {
            listEl.innerHTML = data.map(entry => renderExpLogItem(entry, i18n)).join('');
        }

        container.innerHTML = expBar;
        container.appendChild(listEl);

        const pagination = renderPagination({
            page,
            totalPages: total_pages,
            onClick: loadExpLog
        });

        if (pagination) container.appendChild(pagination);
    })
    .catch(err => {
        console.error('[Init User Engine] Failed to load EXP log:', err);
        const container = document.querySelector('#iue-modal .iue-modal-body');
        if (container) {
            renderError(container, i18n.exp_log_load_fail || 'Failed to load experience log.');
        }
    });
}

function renderExpLogItem(entry, i18n = {}) {
    const amount  = Math.abs(iueParse(entry.amount));
    const sign    = entry.change === 'deduct' ? '–' : '+';
    const message = entry.message || (i18n.exp_log_unknown || 'Unknown');
    const rawTime = entry.time || '';

    // Format chuỗi YYYY-MM-DD HH:mm:ss → đẹp hơn chút (VD: 24/06/2025 lúc 14:33)
    let formattedTime = rawTime;
    try {
        const [datePart, timePart] = rawTime.split(' ');
        const [y, m, d] = datePart.split('-');
        formattedTime = `${d}/${m}/${y} ${timePart}`;
    } catch (e) {
        // fallback giữ nguyên rawTime
    }

    return `
        <div class="iue-exp-log-item">
            <strong>${i18n.exp_log_exp || 'EXP'}</strong>: ${sign}${iueFmt(amount)} ${message} · ${formattedTime}
        </div>
    `;
}

// PROFILE
function loadEditProfileModal() {
    const t = InitUserEngineData.i18n || {};

    // Fetch profile trước khi render modal
    fetch(`${InitUserEngineData.rest_url}/profile/me`, {
        credentials: 'include',
        headers: {
            'X-WP-Nonce': InitUserEngineData.nonce
        }
    })
    .then(res => res.json())
    .then(user => {
        renderEditProfileModal(user, t);
    })
    .catch(err => {
        console.error('[Init User Engine] Failed to fetch profile:', err);
        InitUserEngineToast.show(t.fetch_profile_failed || 'Could not load profile data.', 'error');
    });
}

function renderEditProfileModal(user, t) {
    showUserEngineModal(t.edit_profile_title || 'Edit Profile', `
       <div class="iue-edit-profile-container">
           <div class="iue-form-group">
               <label for="iue-display-name">${t.display_name || 'Display Name'}</label>
               <input type="text" id="iue-display-name" value="${user.display_name || ''}" placeholder="${t.display_name_placeholder || 'Your public display name'}" />
           </div>
           <div class="iue-form-group">
               <label for="iue-bio">${t.bio || 'Bio'}</label>
               <textarea id="iue-bio" rows="3" placeholder="${t.bio_placeholder || 'Short self introduction'}">${user.bio || ''}</textarea>
           </div>
           <div class="iue-form-group">
               <label for="iue-new-password">${t.new_password || 'New Password'}</label>
               <input type="password" id="iue-new-password" placeholder="${t.leave_blank_to_keep || 'Leave blank to keep current password'}" />
           </div>
           <div class="iue-form-group">
               <label for="iue-facebook">Facebook</label>
               <input type="url" id="iue-facebook" value="${user.facebook || ''}" placeholder="${t.facebook_placeholder || 'https://facebook.com/yourprofile'}" />
           </div>
           <div class="iue-form-group">
               <label for="iue-twitter">Twitter</label>
               <input type="url" id="iue-twitter" value="${user.twitter || ''}" placeholder="${t.twitter_placeholder || 'https://twitter.com/yourhandle'}" />
           </div>
           <div class="iue-form-group">
               <label for="iue-discord">Discord</label>
               <input type="text" id="iue-discord" value="${user.discord || ''}" placeholder="${t.discord_placeholder || 'Your Discord username or invite'}" />
           </div>
           <div class="iue-form-group">
               <label for="iue-website">Website</label>
               <input type="url" id="iue-website" value="${user.website || ''}" placeholder="${t.website_placeholder || 'https://yourwebsite.com'}" />
           </div>
           <div class="iue-form-group">
               <label for="iue-gender">${t.gender || 'Gender'}</label>
               <select id="iue-gender">
                   <option value="">${t.gender_unspecified || 'Prefer not to say'}</option>
                   <option value="male" ${user.gender === 'male' ? 'selected' : ''}>${t.gender_male || 'Male'}</option>
                   <option value="female" ${user.gender === 'female' ? 'selected' : ''}>${t.gender_female || 'Female'}</option>
                   <option value="other" ${user.gender === 'other' ? 'selected' : ''}>${t.gender_other || 'Other'}</option>
               </select>
           </div>
           <div class="iue-form-actions">
               <button id="iue-save-profile" class="iue-btn">${t.save || 'Save'}</button>
           </div>
       </div>
   `);

    const saveBtn = document.querySelector('#iue-save-profile');
    if (!saveBtn) return;

    saveBtn.addEventListener('click', () => {
        const data = {
            display_name: document.querySelector('#iue-display-name').value.trim(),
            bio: document.querySelector('#iue-bio').value.trim(),
            new_password: document.querySelector('#iue-new-password').value,
            facebook: document.querySelector('#iue-facebook').value.trim(),
            twitter: document.querySelector('#iue-twitter').value.trim(),
            discord: document.querySelector('#iue-discord').value.trim(),
            website: document.querySelector('#iue-website').value.trim(),
            gender: document.querySelector('#iue-gender').value
        };

        saveBtn.disabled = true;

        fetch(`${InitUserEngineData.rest_url}/profile/update`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': InitUserEngineData.nonce
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if (res && res.success) {
                InitUserEngineToast.show(t.update_success || 'Profile updated successfully!', 'success');
                closeModal();

                if (res.data && res.data.display_name) {
                    const nameEl = document.querySelector('#iue-dashboard-display-name');
                    if (nameEl) {
                        nameEl.textContent = res.data.display_name;
                    }
                }
            } else {
                InitUserEngineToast.show((res && res.message) || (t.update_failed || 'Could not update profile.'), 'error');
                saveBtn.disabled = false;
            }
        })
        .catch(err => {
            console.error('[Init User Engine] Profile update error:', err);
            InitUserEngineToast.show(t.error_generic || 'An error occurred while updating.', 'error');
            saveBtn.disabled = false;
        });
    });
}

// REFERRAL
// referral_modal.js
function loadReferral() {
    const t = InitUserEngineData.i18n || {};
    const affCode = InitUserEngineData.referral_code || '';
    const rewards = InitUserEngineData.referral_rewards || {};
    const siteTitle = document.title;
    const baseUrl = location.origin;
    const referralUrl = `${baseUrl}/?aff=${affCode}`;

    const rewardHTML = `
        <div class="iue-referral-benefits">
            <h4>${t.referral_benefits || 'Referral Benefits'}</h4>
            <div class="iue-referral-benefit-row">
                <strong>${t.you_get || 'You get:'}</strong>
                +${iueFmt(rewards.ref_reward_coin || 0)} ${InitUserEngineData.label_coin}, 
                +${iueFmt(rewards.ref_reward_exp || 0)} EXP, 
                +${iueFmt(rewards.ref_reward_cash || 0)} ${InitUserEngineData.label_cash}
            </div>
            <div class="iue-referral-benefit-row">
                <strong>${t.friend_get || 'Your friend gets:'}</strong>
                +${iueFmt(rewards.ref_new_coin || 0)} ${InitUserEngineData.label_coin}, 
                +${iueFmt(rewards.ref_new_exp || 0)} EXP, 
                +${iueFmt(rewards.ref_new_cash || 0)} ${InitUserEngineData.label_cash}
            </div>
        </div>
    `;

    const modalHTML = `
        <div class="iue-referral-container">
            <h3 class="iue-referral-heading">
                ${t.referral_heading || 'Invite your friends and earn rewards'}
            </h3>

            <div class="iue-referral-box">
                <div class="iue-referral-code">${affCode}</div>
                <div class="iue-referral-code-label">
                    ${t.referral_code_label || 'Your Referral Code'}
                </div>
            </div>

            <div class="iue-referral-input-wrap">
                <input id="iue-referral-link" type="text" readonly value="${referralUrl}" />
                <button class="iue-referral-copy-btn" aria-label="Copy">
                    <span data-iue-icon="copy"></span>
                </button>
            </div>

            <div class="iue-referral-share">
                <p>${t.referral_share || 'Share via'}</p>
                <div class="iue-referral-socials">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralUrl)}"
                       target="_blank" rel="noopener" data-iue-icon="facebook" aria-label="Facebook"></a>

                    <a href="https://x.com/share?text=${encodeURIComponent(siteTitle)}&url=${encodeURIComponent(referralUrl)}"
                       target="_blank" rel="noopener" data-iue-icon="x" aria-label="X"></a>

                    <a href="https://mail.google.com/mail/?view=cm&fs=1&su=${encodeURIComponent(siteTitle)}&body=${encodeURIComponent(referralUrl)}"
                       target="_blank" rel="noopener" data-iue-icon="google" aria-label="Gmail"></a>

                    <a href="https://wa.me/?text=${encodeURIComponent(referralUrl)}"
                       target="_blank" rel="noopener" data-iue-icon="whatsapp" aria-label="WhatsApp"></a>

                    <a href="#" class="iue-referral-share-btn"
                       data-title="${siteTitle}"
                       data-text="${t.referral_text || 'Join me here!'}"
                       data-link="${referralUrl}"
                       data-iue-icon="share" aria-label="System Share"></a>
                </div>
            </div>

            ${rewardHTML}

            <div class="iue-referral-history">
                <h4>${t.referral_history || 'Your Referral History'}</h4>
                <div class="iue-referral-log" id="iue-referral-log">
                    <div class="iue-loading"><svg width="30" height="30" viewBox="0 0 25 25" fill="none"><path d="M4.5 12.5a8 8 0 1 0 8-8" stroke="currentColor" stroke-width="1"/></svg></div>
                </div>
            </div>
        </div>
    `;

    showUserEngineModal(t.referral_title || 'Invite Friends', modalHTML);
    initUserEngineIcons(document.querySelector('#iue-modal'));

    const copyBtn = document.querySelector('.iue-referral-copy-btn');
    const inputEl = document.getElementById('iue-referral-link');

    copyBtn?.addEventListener('click', () => {
        inputEl.select();
        document.execCommand('copy');
        InitUserEngineToast.show(t.referral_copied || 'Link copied!', 'success');
    });

    const webShareBtn = document.querySelector('.iue-referral-share-btn');
    if (webShareBtn && navigator.share) {
        webShareBtn.addEventListener('click', (e) => {
            e.preventDefault();
            navigator.share({
                title: webShareBtn.dataset.title,
                text: webShareBtn.dataset.text,
                url: webShareBtn.dataset.link
            }).catch(() => {});
        });
    }

    // Load referral log
    fetch(`${InitUserEngineData.rest_url}/referral-log`, {
        credentials: 'include',
        headers: {
            'X-WP-Nonce': InitUserEngineData.nonce
        }
    })
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById('iue-referral-log');
        if (!data || !Array.isArray(data.data) || data.data.length === 0) {
            container.innerHTML = `<div class="iue-referral-empty">${t.no_referrals || 'No referral data yet.'}</div>`;
            return;
        }

        container.innerHTML = '<ul class="iue-referral-list">' + data.data.map(u => `
            <li>
                <strong>${u.username}</strong>
                <span class="iue-referral-date">${u.registered}</span>
            </li>
        `).join('') + '</ul>';
    })
    .catch(() => {
        document.getElementById('iue-referral-log').innerHTML = `<div class="iue-referral-error">${t.load_fail || 'Could not load referral history.'}</div>`;
    });
} 

// TOAST
window.InitUserEngineToast = (function () {
    function showToast(message = '', type = 'info', duration = 7000) {
        const containerId = 'iue-toast-container';
        let container = document.getElementById(containerId);

        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.className = 'iue-toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `iue-toast iue-toast-${type}`;
        toast.innerHTML = `
            <div class="iue-toast-icon">${getIcon(type)}</div>
            <div class="iue-toast-message">${message}</div>
            <button class="iue-toast-close" aria-label="Close">&times;</button>
        `;

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('iue-toast-show');
        });

        toast.querySelector('.iue-toast-close').addEventListener('click', () => {
            removeToast(toast);
        });

        setTimeout(() => removeToast(toast), duration);
    }

    function removeToast(toast) {
        toast.classList.remove('iue-toast-show');
        toast.addEventListener('transitionend', () => toast.remove());
    }

    function getIcon(type) {
        switch (type) {
            case 'success': return `<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9 16.17L4.83 12 3.41 13.41 9 19l12-12-1.41-1.41z"/></svg>`;
            case 'error':   return `<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 15h-1v-1h2v1h-1zm0-4h-1V7h2v6h-1z"/></svg>`;
            case 'warning': return `<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>`;
            case 'info':
            default:        return `<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M11 9h2V7h-2v2zm0 8h2v-6h-2v6zm1-16C6.48 1 2 5.48 2 11s4.48 10 10 10 10-4.48 10-10S17.52 1 12 1z"/></svg>`;
        }
    }

    return {
        show: showToast
    };
})();

// TRANSACTION
function loadTransactionHistory(page = 1) {
    const perPage = 20;
    const endpoint = `${InitUserEngineData.rest_url}/transactions?page=${page}&per_page=${perPage}`;
    const headers = {
        'Content-Type': 'application/json',
        'X-WP-Nonce': InitUserEngineData.nonce
    };

    showUserEngineModal(InitUserEngineData.i18n.transaction_title, `
        <div class="iue-loading">
            <svg width="30" height="30" viewBox="0 0 25 25" fill="none">
                <path d="M4.5 12.5a8 8 0 1 0 8-8" stroke="currentColor" stroke-width="1"/>
            </svg>
        </div>`);

    fetch(endpoint, {
        method: 'GET',
        credentials: 'include',
        headers
    })
    .then(res => res.json())
    .then(res => {
        const container = document.querySelector('#iue-modal .iue-modal-body');
        if (!container) return;

        const { data, total_pages, page } = res;

        if (!Array.isArray(data)) return renderError(container, 'Invalid response format.');
        if (data.length === 0) return renderError(container, 'No transactions found.');

        const listEl = document.createElement('div');
        listEl.className = 'iue-transaction-list';
        listEl.innerHTML = data.map(renderTransactionItem).join('');

        container.innerHTML = '';
        container.appendChild(listEl);

        const pagination = renderPagination({
            page,
            totalPages: total_pages,
            onClick: loadTransactionHistory
        });

        if (pagination) container.appendChild(pagination);
    })
    .catch(err => {
        console.error('[Init User Engine] Failed to load transactions:', err);
        const container = document.querySelector('#iue-modal .iue-modal-body');
        if (container) renderError(container, 'Failed to load transaction history.');
    });
}

function renderError(container, message) {
    container.innerHTML = `<p class="iue-error">${message}</p>`;
}

function renderTransactionItem(entry) {
    const rawType  = entry.type || 'unknown';
    const type     = rawType.toLowerCase();
    const amount   = Math.abs(iueParse(entry.amount));
    const change   = (entry.change || '').trim();
    const sign     = change === '-' ? '–' : '+';
    const message  = entry.message || 'Unknown action';

    const label = type === 'cash'
        ? InitUserEngineData.label_cash || 'Cash'
        : InitUserEngineData.label_coin || 'Coin';

    const amountClass = change === '-' ? 'iue-amount-negative' : 'iue-amount-positive';

    const timeStr = entry.time || '???';

    return `
        <div class="iue-transaction-item">
            <div class="iue-trans-row">
                <span class="iue-trans-message">${message}</span>
                <span class="${amountClass}">${sign}${iueFmt(amount)} ${label}</span>
            </div>
            <div class="iue-trans-meta">${timeStr}</div>
        </div>
    `;
}

// VIP
function loadVipModal() {
    const t = InitUserEngineData.i18n || {};
    const coinLabel = InitUserEngineData.label_coin || 'Coin';
    const userIsVip = InitUserEngineData.is_vip || false;
    const vipExpiry = InitUserEngineData.vip_expiry || 0;
    const userCoin = InitUserEngineData.user_coin || 0;

    const statusText = userIsVip
        ? (t.vip_until || 'VIP until') + ' ' + new Date(vipExpiry * 1000).toLocaleDateString()
        : (t.vip_not || 'Not a VIP');

    const prices = InitUserEngineData.vip_prices || {};

    const packageInfo = {
        1: { days: 7, label: t.vip_7d || 'VIP 7 days' },
        2: { days: 30, label: t.vip_30d || 'VIP 30 days' },
        3: { days: 90, label: t.vip_90d || 'VIP 90 days' },
        4: { days: 180, label: t.vip_180d || 'VIP 180 days' },
        5: { days: 360, label: t.vip_360d || 'VIP 360 days' },
        6: { days: 9999, label: t.vip_lifetime || 'VIP Lifetime' }
    };

    showUserEngineModal(t.vip_title || 'VIP Membership', `
        <div class="iue-vip-container">
            <div class="iue-vip-current">
                <strong>${t.vip_status_prefix || 'Current status:'}</strong>
                <span class="iue-vip-status">${statusText}</span>
            </div>
            <div class="iue-vip-grid"></div>
            <div class="iue-vip-note">
                <p><strong>${t.vip_note_title || 'Note:'}</strong> ${t.vip_note_extend || 'VIP will be extended if purchased again before expiration.'}</p>
            </div>
        </div>
    `);

    const grid = document.querySelector('#iue-modal .iue-vip-grid');
    if (!grid) return;

    for (let i = 1; i <= 6; i++) {
        const rawPrice = prices['vip_price_' + i];
        const price = parseInt(rawPrice || 0, 10);
        const isInactive = price <= 0;
        const unaffordable = price > userCoin;
        const info = packageInfo[i];

        const displayPrice = isInactive
            ? `<span class="iue-vip-disabled-text">${t.vip_unavailable || 'Unavailable'}</span>`
            : `${price.toLocaleString()} <span>${coinLabel}</span>`;

        const buttonClass = ['iue-vip-buy-btn'];
        if (unaffordable) buttonClass.push('disabled');

        const card = document.createElement('div');
        card.className = 'iue-vip-card' + (isInactive ? ' iue-vip-card--disabled' : '');
        card.dataset.package = i;
        card.innerHTML = `
            <div class="iue-vip-title">${info.label}</div>
            <div class="iue-vip-price">${displayPrice}</div>
            <button class="${buttonClass.join(' ')}" ${isInactive ? 'disabled' : ''}>
                ${isInactive ? (t.vip_unavailable || 'Unavailable') : (t.vip_buy_btn || 'Buy Now')}
            </button>
        `;
        grid.appendChild(card);
    }

    grid.querySelectorAll('.iue-vip-buy-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            const card = e.target.closest('.iue-vip-card');
            const packageId = parseInt(card.dataset.package, 10);

            if (!packageId || isNaN(packageId)) return;

            btn.disabled = true;

            fetch(`${InitUserEngineData.rest_url}/vip/purchase`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': InitUserEngineData.nonce
                },
                body: JSON.stringify({ package_id: packageId })
            })
            .then(res => res.json())
            .then(res => {
                if (res && res.success) {
                    // Cập nhật data
                    InitUserEngineData.is_vip = true;
                    InitUserEngineData.vip_expiry = res.new_expiry;
                    const packagePrice = parseInt(prices['vip_price_' + packageId] || 0, 10);
                    InitUserEngineData.user_coin = Math.max(0, InitUserEngineData.user_coin - packagePrice);

                    // Cập nhật UI: trạng thái VIP
                    const vipStatus = document.querySelector('.iue-vip-status');
                    if (vipStatus) {
                        const expiry = new Date(res.new_expiry * 1000).toLocaleDateString();
                        vipStatus.textContent = (t.vip_until || 'VIP until') + ' ' + expiry;
                    }

                    // Cập nhật lại toàn bộ nút (ẩn hoặc disable nếu không đủ tiền)
                    document.querySelectorAll('.iue-vip-card').forEach(card => {
                        const btn = card.querySelector('.iue-vip-buy-btn');
                        if (!btn) return;

                        const packageId = parseInt(card.dataset.package || 0, 10);
                        const p = prices['vip_price_' + packageId];

                        if (InitUserEngineData.user_coin < p) {
                            btn.classList.add('disabled');
                        } else {
                            btn.classList.remove('disabled');
                            btn.disabled = false; // optional: enable lại nếu cần
                        }
                    });

                    // Toast báo thành công
                    InitUserEngineToast.show(t.vip_purchase_success || 'VIP purchased successfully!', 'success');
                } else {
                    const msg = (res && res.message) || (t.vip_purchase_fail || 'Could not purchase VIP package.');
                    InitUserEngineToast.show(msg, 'error');
                    btn.disabled = false;
                    btn.textContent = t.vip_buy_btn || 'Buy Now';
                }
            })
            .catch(err => {
                console.error('[Init User Engine] VIP purchase error:', err);
                InitUserEngineToast.show(t.vip_error_generic || 'An error occurred during VIP purchase.', 'error');
                btn.disabled = false;
                btn.textContent = t.vip_buy_btn || 'Buy Now';
            });
        });
    });
}

// EXCHANGE: Cash -> Coin
function loadExchangeModal() {
    const t = InitUserEngineData.i18n || {};
    const rate = parseFloat(InitUserEngineData.rate_coin_per_cash || 0);
    const labelCoin = InitUserEngineData.label_coin || 'Coin';
    const labelCash = InitUserEngineData.label_cash || 'Cash';

    // Nếu tắt quy đổi
    if (!rate || rate <= 0) {
        showUserEngineModal(t.exchange_title || 'Exchange', `
            <div class="iue-exchange-disabled">
                <p>${t.exchange_disabled || 'Exchange is currently disabled.'}</p>
            </div>
        `, 'small');
        return;
    }

    const currentCoin = (() => {
        const el = document.querySelector('.iue-value-coin');
        return el ? iueParse(el.textContent) : 0;
    })();

    const currentCash = (() => {
        const el = document.querySelector('.iue-value-cash');
        return el ? iueParse(el.textContent) : 0;
    })();

    showUserEngineModal(t.exchange_title || 'Exchange', `
        <div class="iue-exchange-box">
            <div class="iue-exchange-rate">
                <strong>${t.exchange_rate || 'Rate'}:</strong>
                1 ${labelCash} → ${rate} ${labelCoin}
            </div>

            <div class="iue-exchange-balance">
                <div>${labelCash}: <strong class="iue-ex-balance-cash">${currentCash.toLocaleString()}</strong></div>
                <div>${labelCoin}: <strong class="iue-ex-balance-coin">${currentCoin.toLocaleString()}</strong></div>
            </div>

            <div class="iue-form-group">
                <label for="iue-exchange-cash">${t.exchange_amount || 'Amount'} (${labelCash})</label>
                <input type="number" id="iue-exchange-cash" min="1" step="1" placeholder="0" />
                <button type="button" class="iue-btn iue-btn-mini" id="iue-exchange-max">${t.exchange_max || 'Max'}</button>
            </div>

            <div class="iue-exchange-preview">
                <span>${t.exchange_receive || 'You will receive'}:</span>
                <strong><span id="iue-exchange-coin">0</span> ${labelCoin}</strong>
            </div>

            <div class="iue-form-actions">
                <button id="iue-exchange-submit" class="iue-btn">${t.exchange_submit || 'Convert'}</button>
            </div>

            <p class="iue-exchange-note">
                ${t.exchange_note || 'Conversion is irreversible. Please review before confirming.'}
            </p>
        </div>
    `, 'small');

    const input = document.getElementById('iue-exchange-cash');
    const receiveEl = document.getElementById('iue-exchange-coin');
    const btnMax = document.getElementById('iue-exchange-max');
    const btnSubmit = document.getElementById('iue-exchange-submit');

    function recompute() {
        const val = Math.max(0, parseInt(input.value || '0', 10));
        const coins = Math.floor(val * rate);
        receiveEl.textContent = coins.toLocaleString('vi-VN'); // ví dụ: 1.000.000
    }

    input?.addEventListener('input', recompute);
    btnMax?.addEventListener('click', () => {
        input.value = String(currentCash);
        recompute();
    });

    btnSubmit?.addEventListener('click', () => {
        const cashAmount = parseInt(input.value || '0', 10);
        if (!cashAmount || cashAmount <= 0) {
            InitUserEngineToast.show(t.exchange_invalid || 'Enter a valid amount.', 'warning');
            return;
        }
        if (cashAmount > currentCash) {
            InitUserEngineToast.show(t.exchange_insufficient || 'Not enough Cash.', 'error');
            return;
        }

        btnSubmit.disabled = true;
        btnSubmit.textContent = t.exchange_processing || 'Processing...';

        fetch(`${InitUserEngineData.rest_url}/exchange`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': InitUserEngineData.nonce
            },
            body: JSON.stringify({ cash: cashAmount })
        })
        .then(res => res.json())
        .then(res => {
            if (res && (res.status === 'exchanged')) {
                // cập nhật số dư trong dashboard
                const coinEl = document.querySelector('.iue-value-coin');
                const cashEl = document.querySelector('.iue-value-cash');
                if (coinEl && typeof res.balances?.coin !== 'undefined') {
                    coinEl.textContent = iueFmt(res.balances.coin);
                }
                if (cashEl && typeof res.balances?.cash !== 'undefined') {
                    cashEl.textContent = iueFmt(res.balances.cash);
                }

                InitUserEngineToast.show(t.exchange_success || 'Exchanged successfully!', 'success');
                closeModal();
            } else {
                const msg = (res && (res.message || res.code)) || (t.exchange_error || 'Exchange failed.');
                InitUserEngineToast.show(msg, 'error');
                btnSubmit.disabled = false;
                btnSubmit.textContent = t.exchange_submit || 'Convert';
            }
        })
        .catch(err => {
            console.error('[Init User Engine] Exchange error:', err);
            InitUserEngineToast.show(t.exchange_error || 'Exchange failed.', 'error');
            btnSubmit.disabled = false;
            btnSubmit.textContent = t.exchange_submit || 'Convert';
        });
    });

    // init icons in modal if any
    initUserEngineIcons(document.getElementById('iue-modal'));
}

// UTILS
function renderPagination({ page, totalPages, onClick, maxVisible = 5 }) {
    if (totalPages <= 1) return null;

    const wrapper = document.createElement('div');
    wrapper.className = 'iue-pagination';

    // Helper để tạo nút
    function createButton(label, pageNum, isActive = false, isDisabled = false) {
        const btn = document.createElement('button');
        btn.textContent = label;
        btn.className = 'iue-page-btn';
        if (isActive) btn.classList.add('active');
        if (isDisabled) btn.disabled = true;

        if (!isDisabled && pageNum !== page) {
            btn.addEventListener('click', () => onClick(pageNum));
        }

        return btn;
    }

    // Prev
    wrapper.appendChild(createButton('«', page - 1, false, page === 1));

    // Logic hiển thị các trang
    let start = Math.max(1, page - Math.floor(maxVisible / 2));
    let end = start + maxVisible - 1;
    if (end > totalPages) {
        end = totalPages;
        start = Math.max(1, end - maxVisible + 1);
    }

    if (start > 1) {
        wrapper.appendChild(createButton('1', 1));
        if (start > 2) {
            const dot = document.createElement('span');
            dot.textContent = '…';
            dot.className = 'iue-pagination-dots';
            wrapper.appendChild(dot);
        }
    }

    for (let i = start; i <= end; i++) {
        wrapper.appendChild(createButton(i, i, i === page));
    }

    if (end < totalPages) {
        if (end < totalPages - 1) {
            const dot = document.createElement('span');
            dot.textContent = '…';
            dot.className = 'iue-pagination-dots';
            wrapper.appendChild(dot);
        }
        wrapper.appendChild(createButton(totalPages, totalPages));
    }

    // Next
    wrapper.appendChild(createButton('»', page + 1, false, page === totalPages));

    return wrapper;
}

function updateUserLevelBadge(level) {
    const badge = document.querySelector('.iue-badge-level');
    if (!badge) return;

    const levelNumEl = badge.querySelector('.iue-badge-number');
    const svg = badge.querySelector('svg');

    // Update level number
    if (levelNumEl) {
        levelNumEl.textContent = 'Lv.' + level;
    }

    // Update fill color
    if (svg) {
        let fill = '#FFD700'; // default
        if (level >= 100) fill = '#00bcd4';
        else if (level >= 50) fill = '#5bc0de';
        else if (level >= 25) fill = '#C0C0C0';
        else if (level >= 10) fill = '#CD7F32';
        svg.setAttribute('fill', fill);
    }
}

function setupConfirmableButton(button, options = {}) {
    const {
        confirmText = InitUserEngineData.i18n.confirm_action || 'Click again to confirm',
        resetAfter = 5000,
        onConfirm = () => {}
    } = options;

    let confirming = false;
    let timeout;

    function reset() {
        if (!confirming) return;
        confirming = false;
        button.classList.remove('iue-confirming');
        button.textContent = button.dataset.originalText;
        clearTimeout(timeout);
        document.removeEventListener('click', outsideClickHandler);
    }

    function outsideClickHandler(e) {
        if (!button.contains(e.target)) {
            reset();
        }
    }

    button.dataset.originalText = button.textContent;

    button.addEventListener('click', function handleClick(e) {
        e.stopPropagation();
        if (confirming) {
            reset();
            onConfirm();
        } else {
            confirming = true;
            button.classList.add('iue-confirming');
            button.textContent = confirmText;

            document.addEventListener('click', outsideClickHandler);
            timeout = setTimeout(reset, resetAfter);
        }
    });
}

// Number formatting helpers
const iueFmt = (n) => {
  const v = typeof n === 'string' ? parseInt(n.replace(/[^\d-]/g, ''), 10) : Number(n);
  return Number.isFinite(v) ? v.toLocaleString('vi-VN') : '0';
};
const iueParse = (text) => parseInt(String(text || '0').replace(/[^\d-]/g, ''), 10) || 0;

// Init
document.addEventListener('DOMContentLoaded', function () {
    initApplyDarkTheme();
    initDashboardToggle();
    initMenuClick();
    initCheckin();
});