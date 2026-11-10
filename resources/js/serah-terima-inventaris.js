const IMAGE_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);

export function requiresSerahTerimaReport(condition) {
    return ['perlu_perbaikan', 'rusak', 'hilang'].includes(condition);
}

export function routeSerahTerimaCondition(condition, { openReport, saveBaik }) {
    if (requiresSerahTerimaReport(condition)) {
        openReport(condition);
        return 'report';
    }

    saveBaik(condition);
    return 'save';
}

export function validateSerahTerimaReport(condition, description, file, hasExistingPhoto) {
    const errors = {};

    if (file && !['rusak', 'hilang'].includes(condition)) {
        errors.bukti_foto = 'Foto bukti hanya dapat dilampirkan untuk kondisi rusak atau hilang.';
    }

    if (['rusak', 'hilang'].includes(condition) && !String(description ?? '').trim()) {
        errors.catatan = 'Keterangan kerusakan atau kehilangan wajib diisi.';
    }

    if (condition === 'rusak' && !hasExistingPhoto && !file) {
        errors.bukti_foto = 'Foto bukti kerusakan wajib dilampirkan.';
    }

    if (file && !IMAGE_TYPES.has(file.type)) {
        errors.bukti_foto = 'Foto harus berformat JPEG, PNG, atau WebP.';
    }

    if (file && file.size > 5 * 1024 * 1024) {
        errors.bukti_foto = 'Ukuran foto maksimal 5 MB.';
    }

    return errors;
}

export function initializeSerahTerimaInventory() {
    const section = document.querySelector('[data-serah-terima-inventaris]');
    const dialog = section?.querySelector('[data-report-dialog]');

    if (!section || !dialog || section.dataset.bound === '1') {
        return;
    }

    section.dataset.bound = '1';

    const cards = Array.from(section.querySelectorAll('[data-item]'));
    const conditionInput = dialog.querySelector('[data-report-condition]');
    const descriptionInput = dialog.querySelector('[data-report-description]');
    const photoInput = dialog.querySelector('[data-report-photo]');
    const photoRequirement = dialog.querySelector('[data-report-photo-requirement]');
    const photoError = dialog.querySelector('[data-report-photo-error]');
    const descriptionError = dialog.querySelector('[data-report-description-error]');
    const preview = dialog.querySelector('[data-report-preview]');
    const previewWrap = dialog.querySelector('[data-report-preview-wrap]');
    const existingPhotoWrap = dialog.querySelector('[data-report-existing-photo-wrap]');
    const existingPhoto = dialog.querySelector('[data-report-existing-photo]');
    const confirmButton = dialog.querySelector('[data-report-confirm]');
    let activeCard = null;
    let previewUrl = null;

    function setError(element, message) {
        element.textContent = message ?? '';
        element.hidden = !message;
    }

    function clearErrors() {
        setError(photoError, '');
        setError(descriptionError, '');
    }

    function clearPreview() {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        preview.removeAttribute('src');
        previewWrap.hidden = true;
    }

    function updateConditionHelp() {
        const condition = conditionInput.value;
        const savedPhoto = Boolean(activeCard?.dataset.savedPhotoUrl);
        const photoRequired = condition === 'rusak' && !savedPhoto;

        dialog.querySelector('[data-report-title]').textContent = {
            perlu_perbaikan: 'Laporan Perlu Perbaikan',
            rusak: 'Laporan Kerusakan Barang',
            hilang: 'Laporan Kehilangan Barang',
        }[condition];
        dialog.querySelector('[data-report-help]').textContent = condition === 'rusak'
            ? 'Keterangan wajib diisi. Foto wajib dilampirkan jika belum ada bukti tersimpan.'
            : condition === 'hilang'
                ? 'Keterangan wajib diisi. Foto pendukung bersifat opsional.'
                : 'Keterangan dan foto bersifat opsional untuk kondisi ini.';
        photoRequirement.textContent = photoRequired
            ? '(wajib)'
            : condition === 'hilang'
                ? '(opsional)'
                : '(tidak diperlukan)';
        photoInput.required = photoRequired;
        photoInput.disabled = condition === 'perlu_perbaikan';
    }

    function updateSavedPhoto(card, url) {
        const wrapper = card.querySelector('[data-saved-evidence]');
        const image = card.querySelector('[data-saved-evidence-image]');

        card.dataset.savedPhotoUrl = url ?? '';
        wrapper.hidden = !url;
        if (url) {
            image.src = url;
        } else {
            image.removeAttribute('src');
        }
    }

    function setStatus(card, text, color) {
        const status = card.querySelector('[data-status]');
        status.textContent = text;
        status.classList.remove('text-green-700', 'text-red-600', 'text-amber-700', 'text-slate-500');
        status.classList.add(color);
    }

    function updateProgress() {
        const progress = section.querySelector('[data-progress]');
        if (progress) {
            progress.textContent = `(${cards.filter((card) => card.dataset.state === 'sudah').length} dari ${cards.length} sudah diperiksa)`;
        }
    }

    function restoreCard(card) {
        const condition = card.dataset.savedCondition || '';
        card.querySelector('[data-lengkap]').checked = condition === 'baik'
            && Number(card.dataset.savedQuantity) === Number(card.dataset.jumlahMaster);
        card.querySelector('[data-tidak]').checked = Boolean(condition)
            && condition !== 'baik';
        card.querySelector('[data-jumlah]').value = card.dataset.savedQuantity;
        card.querySelector('[data-catatan]').value = card.dataset.savedNote || '';
        card.querySelector('[data-condition-label]').textContent = condition
            ? `Kondisi tersimpan: ${condition.replaceAll('_', ' ')}`
            : 'Kondisi tersimpan: belum dipilih';
        card.querySelector('[data-detail]').classList.toggle('hidden', !condition || condition === 'baik');
        updateSavedPhoto(card, card.dataset.savedPhotoUrl || '');
        setStatus(
            card,
            card.dataset.state === 'sudah' ? 'Tersimpan' : 'Belum diperiksa',
            card.dataset.state === 'sudah' ? 'text-green-700' : 'text-slate-500',
        );
    }

    function openReport(card, initialCondition = null) {
        activeCard = card;
        const savedCondition = card.dataset.savedCondition;
        conditionInput.value = initialCondition
            || (requiresSerahTerimaReport(savedCondition) ? savedCondition : 'perlu_perbaikan');
        descriptionInput.value = card.querySelector('[data-catatan]').value;
        photoInput.value = '';
        clearErrors();
        clearPreview();

        const savedUrl = card.dataset.savedPhotoUrl || '';
        existingPhotoWrap.hidden = !savedUrl;
        if (savedUrl) {
            existingPhoto.src = savedUrl;
        } else {
            existingPhoto.removeAttribute('src');
        }

        updateConditionHelp();
        dialog.querySelector('[data-report-item]').textContent = card.dataset.itemName;
        if (!dialog.open) {
            dialog.showModal();
        }
    }

    function cancelReport() {
        const card = activeCard;
        dialog.close();
        clearPreview();
        photoInput.value = '';
        clearErrors();
        activeCard = null;
        if (card) {
            restoreCard(card);
        }
    }

    async function save(card, condition, { description, file } = {}) {
        const quantityInput = card.querySelector('[data-jumlah]');
        const quantity = quantityInput.value === ''
            ? Number.parseInt(card.dataset.jumlahMaster, 10) || 0
            : Number.parseInt(quantityInput.value, 10);

        if (Number.isNaN(quantity) || quantity < 0) {
            setError(descriptionError, 'Jumlah aktual harus berupa angka nol atau lebih.');
            return false;
        }

        if (requiresSerahTerimaReport(condition)) {
            const errors = validateSerahTerimaReport(
                condition,
                description,
                file,
                Boolean(card.dataset.savedPhotoUrl),
            );
            setError(descriptionError, errors.catatan);
            setError(photoError, errors.bukti_foto);
            if (Object.keys(errors).length > 0) {
                return false;
            }
        }

        const formData = new FormData();
        formData.set('inventaris_id', card.dataset.inventarisId);
        formData.set('kondisi', condition);
        formData.set('jumlah_aktual', String(quantity));
        formData.set('catatan', description ?? card.querySelector('[data-catatan]').value);
        if (file) {
            formData.set('bukti_foto', file);
        }

        confirmButton.disabled = true;
        confirmButton.textContent = 'Menyimpan...';
        setStatus(card, 'Menyimpan...', 'text-amber-700');

        try {
            const response = await fetch(section.dataset.url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': section.dataset.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });
            const result = await response.json().catch(() => null);

            if (!response.ok || !result || result.ok === false) {
                const errors = result?.errors ?? {};
                if (dialog.open) {
                    setError(descriptionError, errors.catatan?.[0] ?? result?.message);
                    setError(photoError, errors.bukti_foto?.[0]);
                } else {
                    setStatus(card, Object.values(errors)[0]?.[0] ?? result?.message ?? 'Penyimpanan gagal.', 'text-red-600');
                }
                return false;
            }

            const note = description ?? card.querySelector('[data-catatan]').value;
            card.dataset.state = 'sudah';
            card.dataset.savedCondition = condition;
            card.dataset.savedQuantity = String(quantity);
            card.dataset.savedNote = note;
            card.querySelector('[data-lengkap]').checked = condition === 'baik'
                && quantity === Number(card.dataset.jumlahMaster);
            card.querySelector('[data-tidak]').checked = condition !== 'baik'
                || quantity !== Number(card.dataset.jumlahMaster);
            card.querySelector('[data-jumlah]').value = String(quantity);
            card.querySelector('[data-catatan]').value = note;
            card.querySelector('[data-condition-label]').textContent = `Kondisi tersimpan: ${condition.replaceAll('_', ' ')}`;
            card.querySelector('[data-detail]').classList.toggle('hidden', condition === 'baik');
            updateSavedPhoto(card, result.bukti_foto_url ?? card.dataset.savedPhotoUrl ?? '');
            setStatus(
                card,
                result.ada_masalah ? 'Tersimpan. Menunggu tinjauan admin.' : 'Tersimpan',
                'text-green-700',
            );
            updateProgress();

            if (dialog.open) {
                dialog.close();
                clearPreview();
                photoInput.value = '';
                activeCard = null;
                clearErrors();
            }

            return true;
        } catch (error) {
            if (dialog.open) {
                setError(photoError, 'Koneksi bermasalah. Perubahan belum tersimpan.');
            } else {
                setStatus(card, 'Koneksi bermasalah. Perubahan belum tersimpan.', 'text-red-600');
            }
            return false;
        } finally {
            confirmButton.disabled = false;
            confirmButton.textContent = 'Simpan Laporan';
        }
    }

    cards.forEach((card) => {
        card.querySelector('[data-lengkap]').addEventListener('change', (event) => {
            if (!event.currentTarget.checked) {
                return;
            }
            card.querySelector('[data-jumlah]').value = card.dataset.jumlahMaster;
            card.querySelector('[data-detail]').classList.add('hidden');
            routeSerahTerimaCondition('baik', {
                openReport: () => openReport(card),
                saveBaik: (condition) => save(card, condition),
            });
        });

        card.querySelector('[data-tidak]').addEventListener('change', (event) => {
            if (event.currentTarget.checked) {
                routeSerahTerimaCondition('perlu_perbaikan', {
                    openReport: () => openReport(card),
                    saveBaik: (condition) => save(card, condition),
                });
            }
        });

        card.querySelector('[data-edit-report]').addEventListener('click', () => openReport(card));

        card.querySelector('[data-jumlah]').addEventListener('change', (event) => {
            const quantity = Number.parseInt(event.currentTarget.value, 10);
            if (!Number.isNaN(quantity) && quantity !== Number(card.dataset.jumlahMaster)) {
                card.querySelector('[data-tidak]').checked = true;
                openReport(card);
            } else if (card.querySelector('[data-lengkap]').checked) {
                save(card, 'baik');
            }
        });

        card.querySelector('[data-catatan]').addEventListener('input', () => {
            if (!card.querySelector('[data-lengkap]').checked) {
                return;
            }
            clearTimeout(card._noteTimer);
            card._noteTimer = setTimeout(() => save(card, 'baik'), 700);
        });
    });

    dialog.querySelector('[data-report-cancel]').addEventListener('click', cancelReport);
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        cancelReport();
    });
    dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) {
            return;
        }

        const bounds = dialog.getBoundingClientRect();
        const clickedBackdrop = event.clientX < bounds.left
            || event.clientX > bounds.right
            || event.clientY < bounds.top
            || event.clientY > bounds.bottom;

        if (clickedBackdrop) {
            cancelReport();
        }
    });
    conditionInput.addEventListener('change', () => {
        if (conditionInput.value === 'perlu_perbaikan' && photoInput.files?.length) {
            photoInput.value = '';
            clearPreview();
        }
        updateConditionHelp();
    });
    descriptionInput.addEventListener('input', () => setError(descriptionError, ''));
    photoInput.addEventListener('change', () => {
        clearPreview();
        const file = photoInput.files?.[0];
        if (!file) {
            return;
        }
        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        previewWrap.hidden = false;
        setError(photoError, '');
    });
    confirmButton.addEventListener('click', () => {
        if (activeCard) {
            save(activeCard, conditionInput.value, {
                description: descriptionInput.value,
                file: photoInput.files?.[0] ?? null,
            });
        }
    });

    updateProgress();
    window.addEventListener('pagehide', clearPreview, { once: true });
}
