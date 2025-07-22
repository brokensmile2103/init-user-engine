function loadEditProfileModal() {
	const t = InitUserEngineData.i18n || {};

	// Fetch profile trước khi render modal
	fetch(`${InitUserEngineData.rest_url}/profile/me`, {
		credentials: 'include',
		headers: {
			'X-WP-Nonce': InitUserEngineData.nonce
		}
	})
	.then(res => res.json())
	.then(user => {
		renderEditProfileModal(user, t);
	})
	.catch(err => {
		console.error('[Init User Engine] Failed to fetch profile:', err);
		InitUserEngineToast.show(t.fetch_profile_failed || 'Could not load profile data.', 'error');
	});
}

function renderEditProfileModal(user, t) {
	showUserEngineModal(t.edit_profile_title || 'Edit Profile', `
		<div class="iue-edit-profile-container">
			<div class="iue-form-group">
				<label>${t.display_name || 'Display Name'}</label>
				<input type="text" id="iue-display-name" value="${user.display_name || ''}" placeholder="${t.display_name_placeholder || 'Your public display name'}" />
			</div>

			<div class="iue-form-group">
				<label>${t.bio || 'Bio'}</label>
				<textarea id="iue-bio" rows="3" placeholder="${t.bio_placeholder || 'Short self introduction'}">${user.bio || ''}</textarea>
			</div>

			<div class="iue-form-group">
				<label>${t.new_password || 'New Password'}</label>
				<input type="password" id="iue-new-password" placeholder="${t.leave_blank_to_keep || 'Leave blank to keep current password'}" />
			</div>

			<div class="iue-form-group">
				<label>Facebook</label>
				<input type="url" id="iue-facebook" value="${user.facebook || ''}" placeholder="${t.facebook_placeholder || 'https://facebook.com/yourprofile'}" />
			</div>

			<div class="iue-form-group">
				<label>Twitter</label>
				<input type="url" id="iue-twitter" value="${user.twitter || ''}" placeholder="${t.twitter_placeholder || 'https://twitter.com/yourhandle'}" />
			</div>

			<div class="iue-form-group">
				<label>Discord</label>
				<input type="text" id="iue-discord" value="${user.discord || ''}" placeholder="${t.discord_placeholder || 'Your Discord username or invite'}" />
			</div>

			<div class="iue-form-group">
				<label>Website</label>
				<input type="url" id="iue-website" value="${user.website || ''}" placeholder="${t.website_placeholder || 'https://yourwebsite.com'}" />
			</div>

			<div class="iue-form-group">
				<label>${t.gender || 'Gender'}</label>
				<select id="iue-gender">
					<option value="">${t.gender_unspecified || 'Prefer not to say'}</option>
					<option value="male" ${user.gender === 'male' ? 'selected' : ''}>${t.gender_male || 'Male'}</option>
					<option value="female" ${user.gender === 'female' ? 'selected' : ''}>${t.gender_female || 'Female'}</option>
					<option value="other" ${user.gender === 'other' ? 'selected' : ''}>${t.gender_other || 'Other'}</option>
				</select>
			</div>

			<div class="iue-form-actions">
				<button id="iue-save-profile" class="iue-btn">${t.save || 'Save'}</button>
			</div>
		</div>
	`);

	const saveBtn = document.querySelector('#iue-save-profile');
	if (!saveBtn) return;

	saveBtn.addEventListener('click', () => {
		const data = {
			display_name: document.querySelector('#iue-display-name').value.trim(),
			bio: document.querySelector('#iue-bio').value.trim(),
			new_password: document.querySelector('#iue-new-password').value,
			facebook: document.querySelector('#iue-facebook').value.trim(),
			twitter: document.querySelector('#iue-twitter').value.trim(),
			discord: document.querySelector('#iue-discord').value.trim(),
			website: document.querySelector('#iue-website').value.trim(),
			gender: document.querySelector('#iue-gender').value
		};

		saveBtn.disabled = true;

		fetch(`${InitUserEngineData.rest_url}/profile/update`, {
			method: 'POST',
			credentials: 'include',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': InitUserEngineData.nonce
			},
			body: JSON.stringify(data)
		})
		.then(res => res.json())
		.then(res => {
			if (res && res.success) {
				InitUserEngineToast.show(t.update_success || 'Profile updated successfully!', 'success');
				closeModal();

				if (res.data && res.data.display_name) {
					const nameEl = document.querySelector('#iue-dashboard-display-name');
					if (nameEl) {
						nameEl.textContent = res.data.display_name;
					}
				}
			} else {
				InitUserEngineToast.show((res && res.message) || (t.update_failed || 'Could not update profile.'), 'error');
				saveBtn.disabled = false;
			}
		})
		.catch(err => {
			console.error('[Init User Engine] Profile update error:', err);
			InitUserEngineToast.show(t.error_generic || 'An error occurred while updating.', 'error');
			saveBtn.disabled = false;
		});
	});
}
