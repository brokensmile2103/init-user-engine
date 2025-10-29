jQuery(function ($) {
	const $input = $('#iue_user_search');
	const $result = $('#iue_user_results');
	const $selected = $('#iue_user_selected');
	const isRedeemCode = $('#iue_user_lock').length > 0;
	const $hidden = isRedeemCode ? $('#iue_user_lock') : $('#iue_user_ids');
	const selectedMap = new Map();
	let timer;
	let activeIndex = -1;

	$input.on('input', function () {
		const term = $(this).val().trim();
		clearTimeout(timer);
		activeIndex = -1;

		if (term.length < 2) return $result.hide().empty();

		timer = setTimeout(() => {
			$.post(ajaxurl, {
				action: 'iue_user_search',
				term: term,
				_ajax_nonce: InitPluginSuiteUserEngineAdminNoticeData.nonce
			}, function (res) {
				$result.empty().show();
				if (!Array.isArray(res)) return;

				res.forEach(user => {
					if (selectedMap.has(user.id)) return;

					const $item = $('<div class="iue-user-item">')
						.text(`${user.name} (${user.login})`)
						.attr('data-id', user.id)
						.attr('tabindex', -1)
						.on('click', function () {
							selectedMap.set(user.id, user);
							renderSelected();
							$result.hide();
							$input.val('');
						});

					$result.append($item);
				});
			});
		}, 300);
	});

	// Navigate via arrow keys + Enter
	$input.on('keydown', function (e) {
		const $items = $result.find('.iue-user-item');

		if (!$items.length) return;

		if (e.key === 'ArrowDown') {
			e.preventDefault();
			activeIndex = (activeIndex + 1) % $items.length;
			updateActive($items);
		}
		else if (e.key === 'ArrowUp') {
			e.preventDefault();
			activeIndex = (activeIndex - 1 + $items.length) % $items.length;
			updateActive($items);
		}
		else if (e.key === 'Enter') {
			if (activeIndex >= 0 && $items.eq(activeIndex).length) {
				e.preventDefault(); // prevent form submit
				$items.eq(activeIndex).trigger('click');
			}
		}
	});

	function updateActive($items) {
		$items.removeClass('iue-active');
		if (activeIndex >= 0) {
			$items.eq(activeIndex).addClass('iue-active');
		}
	}

	// Click outside để đóng dropdown
	$(document).on('click', function (e) {
		if (!$(e.target).closest('#iue_user_results, #iue_user_search').length) {
			$result.hide();
		}
	});

	function renderSelected() {
		let ids = Array.from(selectedMap.keys());

		if (isRedeemCode) {
			const first = ids[0] || null;

			// Lưu user trước khi clear
			let userObj = first ? selectedMap.get(first) : null;

			selectedMap.clear();

			if (first && userObj) {
				selectedMap.set(first, userObj);
				ids = [first];
			} else {
				ids = [];
			}
		}

		$hidden.val(ids.join(','));

		$selected.empty();
		selectedMap.forEach(user => {
			const $tag = $('<span class="iue-user-tag">')
				.text(`${user.name} (${user.login})`)
				.append(
					$('<span class="iue-user-remove">×</span>').on('click', () => {
						selectedMap.delete(user.id);
						renderSelected();
					})
				);

			$selected.append($tag);
		});
	}

	// Ẩn/hiện hàng "Select Users" theo radio
    function toggleUserRow() {
        var selected = document.querySelector('input[name="iue_target"]:checked');
        var row = document.getElementById('iue-row-select-users');
        if (!selected || !row) return;
        row.style.display = (selected.value === 'selected') ? '' : 'none';
    }
    document.addEventListener('change', function(e){
        if (e.target && e.target.name === 'iue_target') toggleUserRow();
    });
    toggleUserRow();
});

(function(){
    const typeField = document.querySelector('select[name="iue_type"]');
    const rowMulti  = document.querySelector('.redeem-multi-row');
    const rowUser   = document.querySelector('.redeem-user-row');

    typeField.addEventListener('change', function() {
        rowMulti.style.display = (this.value === 'multi') ? '' : 'none';
        rowUser.style.display  = (this.value === 'user_locked') ? '' : 'none';
    });
})();