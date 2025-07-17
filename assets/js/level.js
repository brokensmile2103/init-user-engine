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
	const amount  = Math.abs(parseInt(entry.amount || 0, 10));
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
			<strong>${i18n.exp_log_exp || 'EXP'}</strong>: ${sign}${amount} ${message} · ${formattedTime}
		</div>
	`;
}
