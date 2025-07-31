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
                if (typeof data.coin !== 'undefined') coinEl.textContent = data.coin;
                if (typeof data.cash !== 'undefined') cashEl.textContent = data.cash;
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

                if (typeof data.coin !== 'undefined') coinEl.textContent = data.coin;
                if (typeof data.cash !== 'undefined') cashEl.textContent = data.cash;
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
