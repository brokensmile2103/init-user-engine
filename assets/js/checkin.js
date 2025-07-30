function initCheckin() {
    const checkinBox = document.querySelector('.iue-checkin-box');
    if (!checkinBox) return;

    const button       = checkinBox.querySelector('.iue-checkin-button');
    const timer        = checkinBox.querySelector('.iue-checkin-timer');
    const countdownEl  = timer?.querySelector('.iue-timer-countdown');
    const streakEl     = checkinBox.querySelector('.iue-checkin-streak');
    const coinEl       = document.querySelector('.iue-value-coin');
    const cashEl       = document.querySelector('.iue-value-cash');

    if (!button || !timer || !countdownEl) return;

    const STORAGE_KEY  = 'iue_checkin_start';
    const COUNTDOWN    = (InitUserEngineData.online_minutes || 10) * 60;

    // Logic cũ nhưng fix bugs
    if (checkinBox.dataset.checkin === '1' && checkinBox.dataset.rewarded !== '1') {
        const REMAINING_KEY = 'iue_countdown_remaining';
        let remaining = parseInt(localStorage.getItem(REMAINING_KEY) || '0', 10);
        
        // Nếu có thời gian còn lại đã lưu, dùng nó
        if (remaining > 0) {
            button.classList.add('iue-hidden');
            timer.classList.remove('iue-hidden');
            startCountdown(remaining);
        } else {
            // Fallback về logic cũ
            let startTime = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
            if (!startTime || isNaN(startTime) || startTime <= 0) {
                startTime = Math.floor(Date.now() / 1000);
                localStorage.setItem(STORAGE_KEY, startTime.toString());
            }

            const elapsed = Math.floor(Date.now() / 1000) - startTime;
            remaining = COUNTDOWN - elapsed;

            if (remaining > 0) {
                button.classList.add('iue-hidden');
                timer.classList.remove('iue-hidden');
                startCountdown(remaining);
            } else {
                localStorage.removeItem(STORAGE_KEY);
                localStorage.removeItem(REMAINING_KEY);
                claimReward();
            }
        }
        return;
    }

    button.addEventListener('click', function () {
        if (button.disabled) return; // Fix double click
        
        button.disabled = true;
        const originalText = button.textContent;
        button.textContent = InitUserEngineData.i18n?.checking_in || 'Checking in...';

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
                const now = Math.floor(Date.now() / 1000);
                localStorage.setItem(STORAGE_KEY, now.toString());

                checkinBox.dataset.checkin = '1';
                checkinBox.dataset.rewarded = '0'; // Reset rewarded
                
                if (streakEl) streakEl.textContent = data.streak || '';
                if (coinEl && typeof data.coin !== 'undefined') coinEl.textContent = data.coin;
                if (cashEl && typeof data.cash !== 'undefined') cashEl.textContent = data.cash;

                if (typeof data.level !== 'undefined' && typeof updateUserLevelBadge === 'function') {
                    updateUserLevelBadge(data.level);
                }

                // Events
                document.dispatchEvent(new CustomEvent('iue:checkin:success', { detail: data }));
                if (parseInt(data.level_up_count || 0, 10) > 0) {
                    document.dispatchEvent(new CustomEvent('iue:level:up', { detail: data }));
                }

                button.classList.add('iue-hidden');
                timer.classList.remove('iue-hidden');
                startCountdown(COUNTDOWN);
                
                if (typeof InitUserEngineToast !== 'undefined') {
                    InitUserEngineToast.show(InitUserEngineData.i18n?.checkin_success || 'Check-in success!', 'success');
                }
            } else {
                button.textContent = InitUserEngineData.i18n?.already_checked_in || 'Already checked in';
                if (typeof InitUserEngineToast !== 'undefined') {
                    InitUserEngineToast.show(InitUserEngineData.i18n?.already_checked_in || 'Already checked in', 'info');
                }
            }
        })
        .catch(err => {
            console.error('Check-in failed:', err);
            button.textContent = InitUserEngineData.i18n?.error || 'Error!';
            button.disabled = false;
            
            if (typeof InitUserEngineToast !== 'undefined') {
                InitUserEngineToast.show(InitUserEngineData.i18n?.error || 'Error occurred', 'error');
            }
            
            // Reset sau 3s
            setTimeout(() => {
                button.textContent = originalText;
            }, 3000);
        });
    });

    function startCountdown(seconds) {
        let total = Math.max(0, Math.floor(seconds));
        const REMAINING_KEY = 'iue_countdown_remaining';

        // Lưu thời gian còn lại vào localStorage
        localStorage.setItem(REMAINING_KEY, total.toString());

        const interval = setInterval(() => {
            // KHÔNG ĐẾM KHI TAB HIDDEN - đúng như bản gốc
            if (document.hidden) return;

            const min = String(Math.floor(total / 60)).padStart(2, '0');
            const sec = String(total % 60).padStart(2, '0');
            countdownEl.textContent = `${min}:${sec}`;
            total--;

            // Cập nhật localStorage mỗi giây
            localStorage.setItem(REMAINING_KEY, total.toString());

            if (total < 0) {
                clearInterval(interval);
                localStorage.removeItem(STORAGE_KEY);
                localStorage.removeItem(REMAINING_KEY);
                timer.classList.add('iue-hidden');
                claimReward();
            }
        }, 1000);
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
                if (coinEl && typeof data.coin !== 'undefined') coinEl.textContent = data.coin;
                if (cashEl && typeof data.cash !== 'undefined') cashEl.textContent = data.cash;

                if (typeof data.level !== 'undefined' && typeof updateUserLevelBadge === 'function') {
                    updateUserLevelBadge(data.level);
                }

                checkinBox.dataset.rewarded = '1';

                // Events
                document.dispatchEvent(new CustomEvent('iue:reward:claimed', { detail: data }));
                if (parseInt(data.level_up_count || 0, 10) > 0) {
                    document.dispatchEvent(new CustomEvent('iue:level:up', { detail: data }));
                }

                if (typeof InitUserEngineToast !== 'undefined') {
                    InitUserEngineToast.show(InitUserEngineData.i18n?.reward_claimed || 'Reward claimed!', 'success');
                }
            }
        })
        .catch(err => {
            console.error('Claim reward failed:', err);
            if (typeof InitUserEngineToast !== 'undefined') {
                InitUserEngineToast.show(InitUserEngineData.i18n?.error || 'Error occurred', 'error');
            }
        });
    }
}

// Init when ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCheckin);
} else {
    initCheckin();
}
