function initCheckin() {
    const checkinBox = document.querySelector('.iue-checkin-box');
    if (!checkinBox) return;

    const button       = checkinBox.querySelector('.iue-checkin-button');
    const timer        = checkinBox.querySelector('.iue-checkin-timer');
    const countdownEl  = timer.querySelector('.iue-timer-countdown');
    const streakEl     = checkinBox.querySelector('.iue-checkin-streak');
    const coinEl       = document.querySelector('.iue-value-coin');
    const cashEl       = document.querySelector('.iue-value-cash');

    const STORAGE_KEY  = 'iue_checkin_start';
    const COUNTDOWN    = InitUserEngineData.online_minutes * 60;

    if (checkinBox.dataset.checkin === '1' && checkinBox.dataset.rewarded !== '1') {
        let startTime = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
        if (!startTime || isNaN(startTime) || startTime <= 0) {
            startTime = Math.floor(Date.now() / 1000);
            localStorage.setItem(STORAGE_KEY, startTime);
        }

        const elapsed   = Math.floor(Date.now() / 1000) - startTime;
        const remaining = COUNTDOWN - elapsed;

        if (remaining > 0) {
            button.classList.add('iue-hidden');
            timer.classList.remove('iue-hidden');
            startCountdown(remaining);
        } else {
            localStorage.removeItem(STORAGE_KEY);
        }
        return;
    }

    button.addEventListener('click', function () {
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
                const now = Math.floor(Date.now() / 1000);
                localStorage.setItem(STORAGE_KEY, now);

                checkinBox.dataset.checkin = '1';
                streakEl.textContent = data.streak;

                if (typeof data.coin !== 'undefined') coinEl.textContent = data.coin;
                if (typeof data.cash !== 'undefined') cashEl.textContent = data.cash;

                if (typeof data.level !== 'undefined') {
                    updateUserLevelBadge(data.level);
                }

                // BẮN SỰ KIỆN: điểm danh thành công
                document.dispatchEvent(new CustomEvent('iue:checkin:success', { detail: data }));

                // Nếu có lên level, bắn thêm event riêng
                if (parseInt(data.level_up_count || 0, 10) > 0) {
                    document.dispatchEvent(new CustomEvent('iue:level:up', { detail: data }));
                }

                button.classList.add('iue-hidden');
                timer.classList.remove('iue-hidden');
                startCountdown(COUNTDOWN);
                InitUserEngineToast.show(InitUserEngineData.i18n.checkin_success, 'success');
            } else {
                button.textContent = InitUserEngineData.i18n.already_checked_in || 'Checked in';
                InitUserEngineToast.show(InitUserEngineData.i18n.already_checked_in, 'info');
            }
        })
        .catch(err => {
            console.error(err);
            button.textContent = InitUserEngineData.i18n.error || 'Error!';
            InitUserEngineToast.show(InitUserEngineData.i18n.error, 'error');
        });
    });

    function startCountdown(seconds) {
        let total = seconds;

        const interval = setInterval(() => {
            if (document.hidden) return;

            const min = String(Math.floor(total / 60)).padStart(2, '0');
            const sec = String(total % 60).padStart(2, '0');
            countdownEl.textContent = `${min}:${sec}`;
            total--;

            if (total < 0) {
                clearInterval(interval);
                localStorage.removeItem(STORAGE_KEY);
                timer.classList.add('iue-hidden');

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

                        if (typeof data.level !== 'undefined') {
                            updateUserLevelBadge(data.level);
                        }

                        // BẮN SỰ KIỆN: nhận thưởng thành công
                        document.dispatchEvent(new CustomEvent('iue:reward:claimed', { detail: data }));

                        // Nếu có lên level, bắn thêm event riêng
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
        }, 1000);
    }
}
