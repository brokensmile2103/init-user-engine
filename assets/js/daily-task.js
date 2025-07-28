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

	const statusEl = completed
		? `<span class="iue-amount-positive">+${reward.amount} ${label}</span>`
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
