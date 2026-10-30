/* =====================================================
CATATAN DINAS - MODAL
===================================================== */

function openCatatanModal() {
    const modal = document.getElementById('catatanModal');

    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');

    document.getElementById('cdmCatatan')?.focus();
}

function closeCatatanModal() {
    const modal = document.getElementById('catatanModal');

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
}

function initCatatanModal() {
    const modal = document.getElementById('catatanModal');

    if (!modal) {
        return;
    }

    const form = document.getElementById('catatanForm');
    const textarea = document.getElementById('cdmCatatan');
    const counter = document.getElementById('cdmCounter');

    const submitBtn = form ?
        form.querySelector('.cdm-save') :
        null;

    const labelAwal = submitBtn ? submitBtn.textContent : '';

    let sedangKirim = false;

    function updateHitung() {
        if (!counter || !textarea) {
            return;
        }

        counter.textContent =
            `${textarea.value.length.toLocaleString('id-ID')} / ` +
            `${BATAS_KARAKTER_CATATAN.toLocaleString('id-ID')} karakter`;
    }

    function setTombol(disabled, text) {
        if (!submitBtn) {
            return;
        }

        submitBtn.disabled = disabled;
        submitBtn.textContent = text;
    }

    modal.addEventListener('mousedown', event => {
        if (event.target === modal) {
            closeCatatanModal();
        }
    });

    document.addEventListener('keydown', event => {
        if (
            event.key === 'Escape' &&
            !modal.classList.contains('hidden')
        ) {
            closeCatatanModal();
        }
    });

    if (textarea) {
        textarea.addEventListener('input', updateHitung);

        updateHitung();

        textarea.addEventListener('keydown', event => {
            if (
                (event.ctrlKey || event.metaKey) &&
                event.key === 'Enter'
            ) {
                event.preventDefault();

                if (form && typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                }
            }
        });
    }

    if (form) {
        form.addEventListener('submit', async event => {
            if (sedangKirim) {
                event.preventDefault();
                return;
            }

            if (!form.checkValidity()) {
                return;
            }

            if (textarea && textarea.value.trim() === '') {
                event.preventDefault();
                textarea.focus();
                return;
            }

            event.preventDefault();

            sedangKirim = true;
            setTombol(true, 'Menyimpan...');

            if (isDirty || isSaving || saveGagal) {
                const berhasil = await tungguSimpanSelesai();

                if (!berhasil) {
                    const lanjut = confirm(
                        'Sebagian data realisasi KA belum berhasil tersimpan.\n\n' +
                        'Tetap simpan catatan dinas?'
                    );

                    if (!lanjut) {
                        sedangKirim = false;
                        setTombol(false, labelAwal);
                        return;
                    }
                }
            }

            form.submit();
        });
    }

    window.addEventListener('pageshow', event => {
        if (event.persisted) {
            sedangKirim = false;
            setTombol(false, labelAwal);
        }
    });

    if (modal.dataset.openOnLoad === '1') {
        openCatatanModal();
    }
}