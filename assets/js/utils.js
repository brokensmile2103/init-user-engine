function renderPagination({ page, totalPages, onClick, maxVisible = 5 }) {
	if (totalPages <= 1) return null;

	const wrapper = document.createElement('div');
	wrapper.className = 'iue-pagination';

	// Helper để tạo nút
	function createButton(label, pageNum, isActive = false, isDisabled = false) {
		const btn = document.createElement('button');
		btn.textContent = label;
		btn.className = 'iue-page-btn';
		if (isActive) btn.classList.add('active');
		if (isDisabled) btn.disabled = true;

		if (!isDisabled && pageNum !== page) {
			btn.addEventListener('click', () => onClick(pageNum));
		}

		return btn;
	}

	// Prev
	wrapper.appendChild(createButton('«', page - 1, false, page === 1));

	// Logic hiển thị các trang
	let start = Math.max(1, page - Math.floor(maxVisible / 2));
	let end = start + maxVisible - 1;
	if (end > totalPages) {
		end = totalPages;
		start = Math.max(1, end - maxVisible + 1);
	}

	if (start > 1) {
		wrapper.appendChild(createButton('1', 1));
		if (start > 2) {
			const dot = document.createElement('span');
			dot.textContent = '…';
			dot.className = 'iue-pagination-dots';
			wrapper.appendChild(dot);
		}
	}

	for (let i = start; i <= end; i++) {
		wrapper.appendChild(createButton(i, i, i === page));
	}

	if (end < totalPages) {
		if (end < totalPages - 1) {
			const dot = document.createElement('span');
			dot.textContent = '…';
			dot.className = 'iue-pagination-dots';
			wrapper.appendChild(dot);
		}
		wrapper.appendChild(createButton(totalPages, totalPages));
	}

	// Next
	wrapper.appendChild(createButton('»', page + 1, false, page === totalPages));

	return wrapper;
}

function updateUserLevelBadge(level) {
    const badge = document.querySelector('.iue-badge-level');
    if (!badge) return;

    const levelNumEl = badge.querySelector('.iue-badge-number');
    const svg = badge.querySelector('svg');

    // Update level number
    if (levelNumEl) {
        levelNumEl.textContent = 'Lv.' + level;
    }

    // Update fill color
    if (svg) {
        let fill = '#FFD700'; // default
        if (level >= 100) fill = '#00bcd4';
        else if (level >= 50) fill = '#5bc0de';
        else if (level >= 25) fill = '#C0C0C0';
        else if (level >= 10) fill = '#CD7F32';
        svg.setAttribute('fill', fill);
    }
}

function setupConfirmableButton(button, options = {}) {
	const {
		confirmText = InitUserEngineData.i18n.confirm_action || 'Click again to confirm',
		resetAfter = 5000,
		onConfirm = () => {}
	} = options;

	let confirming = false;
	let timeout;

	function reset() {
		if (!confirming) return;
		confirming = false;
		button.classList.remove('iue-confirming');
		button.textContent = button.dataset.originalText;
		clearTimeout(timeout);
		document.removeEventListener('click', outsideClickHandler);
	}

	function outsideClickHandler(e) {
		if (!button.contains(e.target)) {
			reset();
		}
	}

	button.dataset.originalText = button.textContent;

	button.addEventListener('click', function handleClick(e) {
		e.stopPropagation();
		if (confirming) {
			reset();
			onConfirm();
		} else {
			confirming = true;
			button.classList.add('iue-confirming');
			button.textContent = confirmText;

			document.addEventListener('click', outsideClickHandler);
			timeout = setTimeout(reset, resetAfter);
		}
	});
}
