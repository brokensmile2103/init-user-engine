document.addEventListener('DOMContentLoaded', function () {
	function tryOpenLoginModal() {
		if (typeof window.openLoginModal === 'function') {
			window.openLoginModal();
			return true;
		}
		return false;
	}

	// guest.js gắn window.openLoginModal trong cùng sự kiện DOMContentLoaded và
	// được nạp trước file này (dependency), nên thường đã sẵn sàng ngay tại đây.
	if (!tryOpenLoginModal()) {
		// Phòng trường hợp thứ tự tải script bị plugin/theme khác can thiệp.
		window.setTimeout(tryOpenLoginModal, 150);
	}
});
