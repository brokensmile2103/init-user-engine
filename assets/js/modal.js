function showUserEngineModal(title, contentHTML, size = 'medium') {
	const modalId = 'iue-modal';
	let modal = document.getElementById(modalId);

	// Nếu chưa tồn tại → tạo modal mới
	if (!modal) {
		modal = document.createElement('div');
		modal.id = modalId;
		modal.innerHTML = `
			<div class="iue-overlay"></div>
			<div class="iue-modal-content">
				<div class="iue-modal-header">
					<h3></h3>
					<button class="iue-modal-close" aria-label="Close">
						<svg width="20" height="20" viewBox="0 0 24 24">
							<path d="m21 21-9-9m0 0L3 3m9 9 9-9m-9 9-9 9"
								  stroke="currentColor"
								  stroke-width="1.1"
								  stroke-linecap="round"
								  stroke-linejoin="round" />
						</svg>
					</button>
				</div>
				<div class="iue-modal-body"></div>
			</div>
		`;
		document.body.appendChild(modal);
		// document.body.style.overflow = 'hidden';

		// Sự kiện đóng modal
		modal.querySelector('.iue-modal-close')?.addEventListener('click', closeModal);
		modal.querySelector('.iue-overlay')?.addEventListener('click', closeModal);
		document.addEventListener('keydown', handleEsc);
	}

	// Gán class dark nếu cần (vào #iue-modal)
	applyModalDarkTheme(modal);

	// Kích thước modal-content
	const modalContent = modal.querySelector('.iue-modal-content');
	modalContent.className = 'iue-modal-content'; // reset
	modalContent.classList.add(`iue-modal-${size}`);

	// Nội dung
	modal.querySelector('h3').innerText = title;
	modal.querySelector('.iue-modal-body').innerHTML = contentHTML;
}

function applyModalDarkTheme(modal) {
	const config = window.InitPluginSuiteUserEngineConfig || {};
	const theme = config.theme;

	modal.classList.remove('dark');

	if (theme === 'dark') {
		modal.classList.add('dark');
	} else if (theme === 'auto') {
		const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
		if (prefersDark) {
			modal.classList.add('dark');
		}
	}
}

function closeModal() {
	const modal = document.getElementById('iue-modal');
	if (modal) modal.remove();
	document.body.style.overflow = '';
	document.removeEventListener('keydown', handleEsc);
}

function handleEsc(e) {
	if (e.key === 'Escape') {
		closeModal();
	}
}
