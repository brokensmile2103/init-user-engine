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
	const amount   = Math.abs(parseInt(entry.amount || 0, 10));
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
				<span class="${amountClass}">${sign}${amount} ${label}</span>
			</div>
			<div class="iue-trans-meta">${timeStr}</div>
		</div>
	`;
}
