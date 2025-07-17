function iueShowAvatarModal() {
    const wrapper = document.querySelector('.iue-avatar-wrapper[data-iue-avatar-trigger]');
    const img = wrapper?.querySelector('img');
    let currentSrc = img?.src || '';

    // Nếu là gravatar → nâng size lên 100 cho rõ preview
    if (currentSrc.includes('gravatar.com') && currentSrc.includes('s=50')) {
        currentSrc = currentSrc.replace('s=50', 's=100');
    }

    // Nếu là avatar custom size 50 → đổi sang size 80 cho nét
    if (currentSrc.match(/-50\.(jpg|jpeg|png|webp)$/)) {
        currentSrc = currentSrc.replace(/-50\.(jpg|jpeg|png|webp)$/, '-80.$1');
    }

    const html = `
        <div class="iue-avatar-upload-box">
            <div class="iue-avatar-dropzone" id="iueAvatarDropzone">
                <p>${InitUserEngineData.i18n.avatar_drop_text || 'Drop image here or click to upload'}</p>
                <input type="file" accept="image/*" id="iueAvatarFileInput">
            </div>
            <div class="iue-avatar-preview-wrapper" id="iueAvatarPreviewWrapper">
                <img id="iueAvatarPreviewImg" src="${currentSrc}" alt="Preview">
            </div>
            <div class="iue-avatar-actions">
                <button class="iue-avatar-upload-btn" id="iueAvatarUploadBtn">
                    ${InitUserEngineData.i18n.avatar_save || 'Save Avatar'}
                </button>
                <button class="iue-avatar-remove-btn" id="iueAvatarRemoveBtn">
                    ${InitUserEngineData.i18n.avatar_remove || 'Remove Avatar'}
                </button>
            </div>
        </div>
    `;

    showUserEngineModal(
        InitUserEngineData.i18n.upload_avatar || 'Upload Avatar',
        html
    );

    const removeBtn = document.getElementById('iueAvatarRemoveBtn');
    if (removeBtn) {
        setupConfirmableButton(removeBtn, {
            onConfirm: () => {
                removeBtn.disabled = true;
                removeBtn.textContent = InitUserEngineData.i18n.avatar_removing || 'Removing...';

                fetch(`${InitUserEngineData.rest_url}/avatar/remove`, {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': InitUserEngineData.nonce
                    },
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.url) throw new Error('Remove failed');
                    document.querySelectorAll('img.iue-avatar-img').forEach(img => {
                        img.src = data.url;
                    });
                    document.querySelector('#iue-modal .iue-modal-close')?.click();
                })
                .catch(err => {
                    console.error('[InitUserEngine] Remove avatar failed:', err);
                    alert(InitUserEngineData.i18n.avatar_remove_fail || 'Failed to remove avatar.');
                })
                .finally(() => {
                    removeBtn.disabled = false;
                    removeBtn.textContent = InitUserEngineData.i18n.avatar_remove || 'Remove Avatar';
                });
            }
        });
    }
}

// Preview file khi chọn
document.addEventListener('change', (e) => {
    const input = e.target;
    if (input.id !== 'iueAvatarFileInput') return;

    const file = input.files[0];
    if (!file || !file.type.startsWith('image/')) return;

    if (file.size > 10 * 1024 * 1024) {
        InitUserEngineToast.show(
            InitUserEngineData.i18n.avatar_too_large || 'Image too large (max 10MB)',
            'warning'
        );
        return;
    }

    const previewImg = document.getElementById('iueAvatarPreviewImg');
    if (previewImg) {
        previewImg.src = URL.createObjectURL(file);
    }
});

// Upload avatar
document.addEventListener('click', (e) => {
    const btn = e.target.closest('#iueAvatarUploadBtn');
    if (!btn) return;

    const input = document.getElementById('iueAvatarFileInput');
    const file = input?.files[0];

    if (!file || !file.type.startsWith('image/')) {
        InitUserEngineToast.show(
            InitUserEngineData.i18n.avatar_invalid || 'Please select a valid image.',
            'error'
        );
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        InitUserEngineToast.show(
            InitUserEngineData.i18n.avatar_too_large || 'Image too large (max 10MB)',
            'warning'
        );
        return;
    }

    const formData = new FormData();
    formData.append('avatar', file);

    btn.disabled = true;
    btn.textContent = InitUserEngineData.i18n.avatar_uploading || 'Uploading...';

    fetch(`${InitUserEngineData.rest_url}/avatar`, {
        method: 'POST',
        headers: {
            'X-WP-Nonce': InitUserEngineData.nonce
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const finalUrl = data.url_80 || data.url_50;
        if (!finalUrl) throw new Error('Upload failed');

        document.querySelectorAll('img.iue-avatar-img').forEach(img => {
            img.src = finalUrl;
        });

        document.querySelector('#iue-modal .iue-modal-close')?.click();
    })
    .catch(err => {
        console.error('[InitUserEngine] Upload avatar failed:', err);
        InitUserEngineToast.show(
            InitUserEngineData.i18n.avatar_upload_fail || 'Upload failed. Please try again.',
            'error'
        );
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = InitUserEngineData.i18n.avatar_save || 'Save Avatar';
    });
});
