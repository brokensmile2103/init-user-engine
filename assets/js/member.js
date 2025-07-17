document.addEventListener('DOMContentLoaded', function () {
    initApplyDarkTheme();
    initDashboardToggle();
    initMenuClick();
    initCheckin();
});

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
