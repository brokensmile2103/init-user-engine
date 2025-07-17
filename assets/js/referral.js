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
                +${rewards.ref_reward_coin || 0} ${InitUserEngineData.label_coin}, 
                +${rewards.ref_reward_exp || 0} EXP, 
                +${rewards.ref_reward_cash || 0} ${InitUserEngineData.label_cash}
            </div>
            <div class="iue-referral-benefit-row">
                <strong>${t.friend_get || 'Your friend gets:'}</strong>
                +${rewards.ref_new_coin || 0} ${InitUserEngineData.label_coin}, 
                +${rewards.ref_new_exp || 0} EXP, 
                +${rewards.ref_new_cash || 0} ${InitUserEngineData.label_cash}
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
