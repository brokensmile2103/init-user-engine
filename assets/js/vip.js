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
