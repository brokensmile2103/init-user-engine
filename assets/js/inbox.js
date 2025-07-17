function loadInbox(page = 1) {
	const perPage = 20;
	const endpoint = `${InitUserEngineData.rest_url}/inbox?page=${page}&per_page=${perPage}`;
	const headers = {
		'Content-Type': 'application/json',
		'X-WP-Nonce': InitUserEngineData.nonce
	};

	showUserEngineModal(InitUserEngineData.i18n.inbox_title, `<div class="iue-loading"><svg width="30" height="30" viewBox="0 0 25 25" fill="none"><path d="M4.5 12.5a8 8 0 1 0 8-8" stroke="currentColor" stroke-width="1"/></svg></div>`);

	fetch(endpoint, { method: 'GET', credentials: 'include', headers })
		.then(res => res.json())
		.then(res => {
			const container = document.querySelector('#iue-modal .iue-modal-body');
			if (!container) return;

			const { data, total_pages, page } = res;

			if (!Array.isArray(data) || data.length === 0) {
				container.innerHTML = `<p class="iue-empty">${InitUserEngineData.i18n.no_messages}</p>`;
				return;
			}

			const listEl = document.createElement('div');
			listEl.className = 'iue-inbox-list';
			listEl.innerHTML = data.map(renderInboxItem).join('');

			const controls = `
				<div class="iue-inbox-controls">
					<button class="iue-mark-all-read" type="button">${InitUserEngineData.i18n.mark_all_read}</button>
					<button class="iue-delete-all" type="button">${InitUserEngineData.i18n.delete_all}</button>
				</div>
			`;

			container.innerHTML = controls;
			container.appendChild(listEl);

			const pagination = renderPagination({
				page,
				totalPages: total_pages,
				onClick: loadInbox
			});

			if (pagination) container.appendChild(pagination);

			attachInboxEvents(container);
			initUserEngineIcons(container);
		})
		.catch(err => {
			console.error('[Init User Engine] Failed to load inbox:', err);
			const container = document.querySelector('#iue-modal .iue-modal-body');
			if (container) {
				container.innerHTML = `<p class="iue-error">${InitUserEngineData.i18n.load_inbox_error}</p>`;
			}
		});
}

function renderInboxItem(entry) {
	const id = entry.id || 0;
	const title = entry.title || 'No title';
	const content = entry.content || '';
	const time = entry.time;
	const isRead = entry.status === 'read';
	const link = entry.link || null;
	const priorityClass = entry.priority === 'high' ? ' iue-high-priority' : '';
	const pinnedClass = entry.pinned ? ' iue-pinned' : '';

	const header = `
		<div class="iue-inbox-header">
			<strong>${title}</strong>
			<small>${time}</small>
		</div>
	`;

	const body = `<div class="iue-inbox-content">${content}</div>`;

	const actions = `
		<div class="iue-inbox-actions">
			<button class="iue-inbox-more" data-id="${id}" aria-haspopup="true">
				<span class="iue-icon" data-iue-icon="more"></span>
			</button>
			<div class="iue-inbox-menu" data-id="${id}" hidden>
				<button class="iue-inbox-read" data-id="${id}" ${isRead ? 'disabled' : ''}>${InitUserEngineData.i18n.mark_as_read}</button>
				<button class="iue-inbox-delete" data-id="${id}">${InitUserEngineData.i18n.delete}</button>
			</div>
		</div>
	`;

	const contentBlock = `
		<div class="iue-inbox-content-wrap">
			${link ? `<a href="${link}" class="iue-inbox-link" target="_blank" rel="noopener noreferrer">${header + body}</a>` : header + body}
		</div>
	`;

	return `
		<div class="iue-inbox-item${isRead ? '' : ' iue-unread'}${priorityClass}${pinnedClass}" data-id="${id}">
			${contentBlock}
			${actions}
		</div>
	`;
}

function attachInboxEvents(container) {
	// Dropdown toggle
	container.querySelectorAll('.iue-inbox-more')?.forEach(btn => {
		btn.addEventListener('click', e => {
			e.preventDefault();
			e.stopPropagation();
			const id = btn.dataset.id;
			if (!id) return;

			// Close all others
			document.querySelectorAll('.iue-inbox-menu').forEach(menu => {
				if (menu.dataset.id !== id) menu.hidden = true;
			});

			const menu = container.querySelector(`.iue-inbox-menu[data-id="${id}"]`);
			if (menu) {
				menu.hidden = !menu.hidden;
			}
		});
	});

	// Click outside to close all menus
	document.addEventListener('click', e => {
		if (!e.target.closest('.iue-inbox-actions')) {
			document.querySelectorAll('.iue-inbox-menu').forEach(menu => menu.hidden = true);
		}
	});

	// Xoá từng tin
	container.querySelectorAll('.iue-inbox-delete')?.forEach(btn => {
		btn.addEventListener('click', () => {
			const id = btn.dataset.id;
			if (!id) return;
			deleteInboxItem(id, btn);
		});
	});

	// Đánh dấu đã đọc từng tin
	container.querySelectorAll('.iue-inbox-read')?.forEach(btn => {
		btn.addEventListener('click', () => {
			const id = btn.dataset.id;
			if (!id) return;
			markInboxRead(id, btn);
		});
	});

	// Đánh dấu là đã đọc khi click vào link (nếu chưa đọc)
	container.querySelectorAll('.iue-inbox-link')?.forEach(link => {
		link.addEventListener('click', e => {
			const item = link.closest('.iue-inbox-item');
			if (!item || item.classList.contains('iue-unread') === false) return;

			const id = item.dataset.id;
			if (!id) return;

			// Gọi API đánh dấu đã đọc (nhưng không reload UI)
			fetch(`${InitUserEngineData.rest_url}/inbox/mark-read`, {
				method: 'POST',
				credentials: 'include',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': InitUserEngineData.nonce
				},
				body: JSON.stringify({ id })
			}).then(() => {
				item.classList.remove('iue-unread');
				updateInboxUnreadIndicator();
			});
		});
	});

	// Đánh dấu tất cả đã đọc
	const markAllBtn = container.querySelector('.iue-mark-all-read');
	if (markAllBtn) {
		setupConfirmableButton(markAllBtn, { onConfirm: markInboxAllRead });
	}

	// Xoá tất cả
	const deleteAllBtn = container.querySelector('.iue-delete-all');
	if (deleteAllBtn) {
		setupConfirmableButton(deleteAllBtn, { onConfirm: deleteInboxAll });
	}
}

function deleteInboxItem(id, btn) {
	btn.disabled = true;
	fetch(`${InitUserEngineData.rest_url}/inbox/delete`, {
		method: 'POST',
		credentials: 'include',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': InitUserEngineData.nonce
		},
		body: JSON.stringify({ id })
	})
	.then(res => res.json())
	.then(res => {
		if (res.status === 'deleted') {
			const el = btn.closest('.iue-inbox-item');
			if (el) el.remove();
			updateInboxUnreadIndicator();
		}
	})
	.catch(err => console.error('Delete inbox item failed', err));
}

function deleteInboxAll() {
	fetch(`${InitUserEngineData.rest_url}/inbox/delete-all`, {
		method: 'POST',
		credentials: 'include',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': InitUserEngineData.nonce
		}
	})
	.then(res => res.json())
	.then(res => {
		if (res.status === 'all_deleted') {
		    InitUserEngineToast.show(InitUserEngineData.i18n.delete_all_success, 'success');
			loadInbox();
			updateInboxUnreadIndicator();
		}
	})
	.catch(err => {
	    console.error('Delete all inbox failed', err);
	    InitUserEngineToast.show(InitUserEngineData.i18n.error, 'error');
	});
}

function markInboxRead(id, btn) {
	btn.disabled = true;
	fetch(`${InitUserEngineData.rest_url}/inbox/mark-read`, {
		method: 'POST',
		credentials: 'include',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': InitUserEngineData.nonce
		},
		body: JSON.stringify({ id })
	})
	.then(res => res.json())
	.then(res => {
		if (res.status === 'marked') {
			const el = btn.closest('.iue-inbox-item');
			if (el) el.classList.remove('iue-unread');
			updateInboxUnreadIndicator();
		}
	})
	.catch(err => console.error('Mark inbox read failed', err));
}

function markInboxAllRead() {
	fetch(`${InitUserEngineData.rest_url}/inbox/mark-all-read`, {
		method: 'POST',
		credentials: 'include',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': InitUserEngineData.nonce
		}
	})
	.then(res => res.json())
	.then(res => {
		if (res.status === 'all_marked') {
			InitUserEngineToast.show(InitUserEngineData.i18n.mark_all_read_success, 'success');
			loadInbox();
			updateInboxUnreadIndicator();
		}
	})
	.catch(err => console.error('Mark all inbox read failed', err));
}

function updateInboxUnreadIndicator() {
	const unreadDot = document.querySelector('.iue-unread-dot');
	const menuInbox = document.querySelector('.iue-menu-link[data-action="inbox"]');
	const badge     = menuInbox?.querySelector('.iue-badge');
	const avatar    = document.getElementById('init-user-engine-avatar');

	const stillUnread = document.querySelectorAll('.iue-inbox-item.iue-unread').length;

	if (stillUnread <= 0) {
		unreadDot?.remove();
		badge?.remove();
		avatar?.classList.remove('iue-has-unread');
	} else {
		// Nếu chưa có badge thì tạo luôn
		if (!badge) {
			const span = document.createElement('span');
			span.className = 'iue-badge';
			span.textContent = stillUnread;
			menuInbox?.querySelector('span')?.appendChild(span);
		} else {
			badge.textContent = stillUnread;
		}

		// Nếu chưa có dot thì tạo lại
		if (!unreadDot && avatar && !avatar.querySelector('.iue-unread-dot')) {
			const dot = document.createElement('span');
			dot.className = 'iue-unread-dot';
			avatar.appendChild(dot);
		}

		avatar?.classList.add('iue-has-unread');
	}
}
