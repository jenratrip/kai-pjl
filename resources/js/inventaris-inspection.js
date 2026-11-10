const ALLOWED_EVIDENCE_TYPES = new Set([
    'image/jpeg',
    'image/png',
    'image/webp',
]);

export function requiresEvidenceModal(condition) {
    return condition === 'rusak' || condition === 'hilang';
}

export function validateEvidenceInput(condition, description, file, hasExistingPhoto) {
    const errors = {};

    if (requiresEvidenceModal(condition) && !String(description ?? '').trim()) {
        errors.catatan = 'Keterangan kerusakan atau kehilangan wajib diisi.';
    }

    if (condition === 'rusak' && !hasExistingPhoto && !file) {
        errors.bukti_foto = 'Foto bukti kerusakan wajib dilampirkan.';
    }

    if (file && !ALLOWED_EVIDENCE_TYPES.has(file.type)) {
        errors.bukti_foto = 'Foto harus berformat JPEG, PNG, atau WebP.';
    }

    if (file && file.size > 5 * 1024 * 1024) {
        errors.bukti_foto = 'Ukuran foto maksimal 5 MB.';
    }

    return errors;
}

export function routeInspectionAction(condition, { openEvidenceModal, saveImmediately }) {
    if (requiresEvidenceModal(condition)) {
        openEvidenceModal(condition);
        return 'modal';
    }

    saveImmediately(condition);
    return 'save';
}

export function bindConditionChange(conditionInput, { openEvidenceModal, markPending }) {
    conditionInput.addEventListener('change', () => {
        const condition = conditionInput.value;

        if (requiresEvidenceModal(condition)) {
            openEvidenceModal(condition);
            return;
        }

        markPending(condition);
    });
}

export function openEvidenceDialog(dialog) {
    if (typeof dialog.showModal === 'function') {
        dialog.showModal();
        return;
    }

    dialog.setAttribute('open', '');
}

export function closeEvidenceDialog(dialog) {
    if (typeof dialog.close === 'function') {
        dialog.close();
        return;
    }

    dialog.removeAttribute('open');
}

export function initializeInventarisInspection() {
    const section = document.querySelector('[data-inventory-inspection]');
    const dialog = section?.querySelector('[data-evidence-dialog]');

    if (!section || !dialog || section.dataset.bound === '1') {
        return;
    }

    section.dataset.bound = '1';

    const cards = Array.from(section.querySelectorAll('[data-item]'));
    const photoInput = dialog.querySelector('[data-evidence-photo]');
    const descriptionInput = dialog.querySelector('[data-evidence-description]');
    const preview = dialog.querySelector('[data-evidence-preview]');
    const previewWrap = dialog.querySelector('[data-evidence-preview-wrap]');
    const existingPhoto = dialog.querySelector('[data-existing-evidence]');
    const photoRequired = dialog.querySelector('[data-photo-required]');
    const photoOptional = dialog.querySelector('[data-photo-optional]');
    const photoError = dialog.querySelector('[data-error-photo]');
    const descriptionError = dialog.querySelector('[data-error-description]');
    const confirmButton = dialog.querySelector('[data-evidence-confirm]');
    let activeCard = null;
    let pendingCondition = null;
    let previewUrl = null;

    function setFieldError(element, message) {
        element.textContent = message ?? '';
        element.hidden = !message;
    }

    function clearErrors() {
        setFieldError(photoError, '');
        setFieldError(descriptionError, '');
    }

    function clearNewPreview() {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        preview.removeAttribute('src');
        previewWrap.hidden = true;
    }

    function showSavedEvidence(card, url) {
        const container = card.querySelector('[data-saved-evidence]');
        const image = card.querySelector('[data-saved-evidence-image]');

        card.dataset.savedPhotoUrl = url ?? '';
        container.hidden = !url;

        if (url) {
            image.src = url;
        } else {
            image.removeAttribute('src');
        }
    }

    function setSavedStatus(card, message, kind) {
        const element = card.querySelector('[data-status]');
        element.textContent = message;
        element.classList.remove('text-green-700', 'text-red-600', 'text-amber-700', 'text-slate-500');
        element.classList.add({
            success: 'text-green-700',
            error: 'text-red-600',
            pending: 'text-amber-700',
            neutral: 'text-slate-500',
        }[kind]);
    }

    function updateProgress() {
        const progress = section.querySelector('[data-progress]');
        if (progress) {
            const complete = cards.filter((card) => card.dataset.state === 'sudah').length;
            progress.textContent = `${complete} / ${cards.length} item diperiksa`;
        }
    }

    function restoreCard(card) {
        card.querySelector('[data-kondisi]').value = card.dataset.savedCondition || 'baik';
        card.querySelector('[data-jumlah]').value = card.dataset.savedQuantity || '';
        card.querySelector('[data-catatan]').value = card.dataset.savedNote || '';
        showSavedEvidence(card, card.dataset.savedPhotoUrl || '');
        setSavedStatus(
            card,
            card.dataset.savedInspected === '1' ? 'Pemeriksaan tersimpan.' : 'Belum diperiksa.',
            'neutral',
        );
    }

    function openModal(card, condition) {
        activeCard = card;
        pendingCondition = condition;
        const savedPhotoUrl = card.dataset.savedPhotoUrl || '';

        dialog.querySelector('[data-evidence-title]').textContent = condition === 'rusak'
            ? 'Bukti kerusakan inventaris'
            : 'Keterangan kehilangan inventaris';
        dialog.querySelector('[data-evidence-help]').textContent = condition === 'rusak'
            ? 'Foto kerusakan dan keterangan singkat wajib diisi.'
            : 'Keterangan kehilangan wajib diisi. Foto pendukung bersifat opsional.';
        descriptionInput.value = card.querySelector('[data-catatan]').value;
        photoInput.value = '';
        photoInput.required = condition === 'rusak' && !savedPhotoUrl;
        photoRequired.hidden = !photoInput.required;
        photoOptional.hidden = condition === 'rusak';
        clearNewPreview();
        clearErrors();

        existingPhoto.hidden = !savedPhotoUrl;
        if (savedPhotoUrl) {
            existingPhoto.querySelector('img').src = savedPhotoUrl;
        } else {
            existingPhoto.querySelector('img').removeAttribute('src');
        }

        if (!dialog.open) {
            openEvidenceDialog(dialog);
        }
    }

    function cancelModal() {
        const card = activeCard;
        closeEvidenceDialog(dialog);
        clearNewPreview();
        photoInput.value = '';
        clearErrors();
        activeCard = null;
        pendingCondition = null;

        if (card) {
            restoreCard(card);
        }
    }

    async function save(card, condition, { modal = false, file = null, description = null } = {}) {
        const quantityInput = card.querySelector('[data-jumlah]');
        const quantity = quantityInput.value === ''
            ? Number.parseInt(card.dataset.jumlahMaster, 10) || 0
            : Number.parseInt(quantityInput.value, 10);
        const catatan = description ?? card.querySelector('[data-catatan]').value;
        const errors = modal
            ? validateEvidenceInput(
                condition,
                catatan,
                file,
                Boolean(card.dataset.savedPhotoUrl),
            )
            : {};

        if (Number.isNaN(quantity) || quantity < 0) {
            if (modal) {
                setFieldError(descriptionError, 'Jumlah aktual harus berupa angka nol atau lebih.');
            }

            return false;
        }

        if (Object.keys(errors).length > 0) {
            setFieldError(photoError, errors.bukti_foto);
            setFieldError(descriptionError, errors.catatan);

            return false;
        }

        const form = card.querySelector('form');
        const formData = new FormData();
        formData.set('_method', 'PATCH');
        formData.set('dinas_id', form.querySelector('[name="dinas_id"]').value);
        formData.set('kondisi', condition);
        formData.set('jumlah_aktual', String(quantity));
        formData.set('catatan', catatan);

        if (file) {
            formData.set('bukti_foto', file);
        }

        confirmButton.disabled = modal;
        if (modal) {
            confirmButton.textContent = 'Menyimpan...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });
            const result = await response.json().catch(() => null);

            if (!response.ok || !result || result.ok === false) {
                const validationErrors = result?.errors ?? {};
                if (modal) {
                    setFieldError(photoError, validationErrors.bukti_foto?.[0] ?? result?.message ?? 'Foto belum dapat disimpan.');
                    setFieldError(descriptionError, validationErrors.catatan?.[0]);
                } else {
                    const firstError = Object.values(validationErrors)[0]?.[0];
                    setSavedStatus(card, firstError ?? result?.message ?? 'Penyimpanan gagal. Coba kembali.', 'error');
                }

                return false;
            }

            card.dataset.savedCondition = condition;
            card.dataset.savedQuantity = String(quantity);
            card.dataset.savedNote = catatan;
            card.dataset.savedInspected = '1';
            card.dataset.state = 'sudah';
            card.querySelector('[data-jumlah]').value = String(quantity);
            card.querySelector('[data-catatan]').value = catatan;

            const evidenceUrl = result.bukti_foto_url ?? card.dataset.savedPhotoUrl ?? '';
            showSavedEvidence(card, evidenceUrl);
            setSavedStatus(
                card,
                result.ada_masalah
                    ? 'Tersimpan. Menunggu tinjauan admin.'
                    : 'Kondisi inventaris berhasil disimpan.',
                'success',
            );
            updateProgress();

            if (modal) {
                activeCard = null;
                pendingCondition = null;
                closeEvidenceDialog(dialog);
                photoInput.value = '';
                clearNewPreview();
                clearErrors();
            }

            return true;
        } catch (error) {
            if (modal) {
                setFieldError(photoError, 'Koneksi bermasalah. Perubahan belum tersimpan.');
            }

            return false;
        } finally {
            confirmButton.disabled = false;
            confirmButton.textContent = 'Simpan';
        }
    }

    function pilihAksi(card, condition) {
        return routeInspectionAction(condition, {
            openEvidenceModal: (selectedCondition) => openModal(card, selectedCondition),
            saveImmediately: (selectedCondition) => save(card, selectedCondition),
        });
    }

    cards.forEach((card) => {
        const form = card.querySelector('form');
        const conditionInput = card.querySelector('[data-kondisi]');

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            pilihAksi(card, conditionInput.value);
        });

        bindConditionChange(conditionInput, {
            openEvidenceModal: (condition) => openModal(card, condition),
            markPending: () => {
                setSavedStatus(card, 'Perubahan belum disimpan. Tekan Simpan Pemeriksaan.', 'pending');
            },
        });
    });

    updateProgress();

    dialog.querySelector('[data-evidence-cancel]').addEventListener('click', cancelModal);
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        cancelModal();
    });
    dialog.querySelector('[data-evidence-confirm]').addEventListener('click', () => {
        if (activeCard && pendingCondition) {
            save(activeCard, pendingCondition, {
                modal: true,
                file: photoInput.files?.[0] ?? null,
                description: descriptionInput.value,
            });
        }
    });

    descriptionInput.addEventListener('input', () => {
        setFieldError(descriptionError, '');
    });

    photoInput.addEventListener('change', () => {
        clearNewPreview();
        const file = photoInput.files?.[0];
        if (!file) {
            return;
        }

        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        previewWrap.hidden = false;
        setFieldError(photoError, '');
    });

    window.addEventListener('pagehide', () => {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
    }, { once: true });
}
