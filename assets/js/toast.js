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
