const PROFILE_IMAGE_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);

export function validateProfilePhoto(file) {
    if (!file) {
        return 'Pilih foto profil terlebih dahulu.';
    }
    if (!PROFILE_IMAGE_TYPES.has(file.type)) {
        return 'Foto harus berformat JPEG, PNG, atau WebP.';
    }
    if (file.size > 2 * 1024 * 1024) {
        return 'Ukuran foto maksimal 2 MB.';
    }

    return '';
}

export function initializeUserProfilePhoto() {
    const form = document.querySelector('[data-profile-photo-form]');
    const modal = document.querySelector('[data-profile-photo-modal]');
    if (!form || !modal || form.dataset.bound === '1') {
        return;
    }

    form.dataset.bound = '1';
    const openButton = document.querySelector('[data-profile-photo-open]');
    const closeButton = modal.querySelector('[data-profile-photo-close]');
    const cancelButton = modal.querySelector('[data-profile-photo-cancel]');
    const input = form.querySelector('[data-profile-photo-input]');
    const preview = form.querySelector('[data-profile-photo-preview]');
    const wrapper = form.querySelector('[data-profile-photo-preview-wrap]');
    const error = form.querySelector('[data-profile-photo-error]');
    let previewUrl = null;
    let previouslyFocused = null;

    function openModal() {
        previouslyFocused = document.activeElement;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        input.focus();
    }

    function closeModal(resetInput = false) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        if (resetInput) {
            input.value = '';
            clearPreview();
            error.textContent = '';
            error.hidden = true;
        }
        previouslyFocused?.focus?.();
    }

    function clearPreview() {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
        preview.removeAttribute('src');
        wrapper.classList.add('hidden');
    }

    input.addEventListener('change', () => {
        clearPreview();
        const file = input.files?.[0];
        const validationMessage = validateProfilePhoto(file);
        error.textContent = validationMessage;
        error.hidden = !validationMessage;
        if (validationMessage) {
            return;
        }

        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        wrapper.classList.remove('hidden');
    });

    form.addEventListener('submit', (event) => {
        const validationMessage = validateProfilePhoto(input.files?.[0]);
        error.textContent = validationMessage;
        error.hidden = !validationMessage;
        if (validationMessage) {
            event.preventDefault();
        }
    });

    openButton?.addEventListener('click', openModal);
    closeButton?.addEventListener('click', () => closeModal(true));
    cancelButton?.addEventListener('click', () => closeModal(true));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal(true);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal(true);
        }
    });

    if (modal.dataset.openOnLoad === 'true') {
        openModal();
    }

    window.addEventListener('pagehide', clearPreview, { once: true });
}
