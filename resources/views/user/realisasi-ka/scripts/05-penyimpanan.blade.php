/* =====================================================
PENYIMPANAN OTOMATIS
===================================================== */

let isSaving = false;
let isDirty = false;
let saveQueued = false;
let queuedManual = false;
let saveGagal = false;

let autoSaveTimer = null;
let retryTimer = null;

function setSaveStatus(state, text) {
    const box = document.getElementById('saveStatus');
    const label = document.getElementById('saveStatusText');

    if (box) {
        box.className = `save-status ${state}`;
    }

    if (label) {
        label.textContent = text;
    }
}

function tandaiBerubah(delay = JEDA_SIMPAN_STATUS, index) {
    isDirty = true;

    if (index !== undefined && index !== null) {
        dirtyRows.add(index);
    }

    setSaveStatus('pending', 'Perubahan belum disimpan…');
    refreshRowButtons();

    clearTimeout(autoSaveTimer);
}

function ambilCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function formatJamStatusSimpan() {
    const now = getWIBTime();

    return (
        String(now.hour).padStart(2, '0') + ':' +
        String(now.minute).padStart(2, '0')
    );
}

async function simpanRealisasi(options = {}) {
    const otomatis = options.otomatis === true;
    const tombol = document.getElementById('simpanBtn');

    if (dataKA.length === 0) {
        if (!otomatis) {
            alert('Belum ada data KA untuk disimpan.');
        }

        return;
    }

    if (isSaving) {
        saveQueued = true;

        if (!otomatis) {
            queuedManual = true;
        }

        return;
    }

    clearTimeout(autoSaveTimer);
    clearTimeout(retryTimer);

    isSaving = true;

    const versiDikirim = new Map();
    const barisDikirim = new Set(dirtyRows);

    dataKA.forEach((_, index) => {
        versiDikirim.set(index, getRowVersion(index));
    });

    const snapshot = JSON.parse(JSON.stringify(dataKA));

    isDirty = false;
    savingRows = new Set(barisDikirim);
    dirtyRows = new Set();

    refreshRowButtons();
    setSaveStatus('saving', 'Menyimpan…');

    if (!otomatis && tombol) {
        tombol.disabled = true;
        tombol.textContent = 'Menyimpan...';
    }

    try {
        const response = await fetch(CONFIG.storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': ambilCsrfToken()
            },
            body: JSON.stringify({
                data: snapshot
            })
        });

        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            let error;

            if (response.status === 419) {
                error = new Error(
                    'Sesi berakhir. Muat ulang halaman, lalu coba lagi.'
                );
                error.permanen = true;

            } else if (
                response.status === 401 ||
                response.status === 403
            ) {
                error = new Error(
                    'Akses ditolak. Silakan login kembali.'
                );
                error.permanen = true;

            } else {
                error = new Error(
                    'Server tidak mengembalikan JSON. Periksa route dan log Laravel.'
                );
            }

            throw error;
        }

        const result = await response.json();

        if (!response.ok) {
            const pesanValidasi = result.errors ?
                Object.values(result.errors).flat().join('\n') :
                result.message;

            const error = new Error(
                pesanValidasi || 'Gagal menyimpan realisasi KA.'
            );

            error.permanen = response.status === 422;

            throw error;
        }

        if (Array.isArray(result.data)) {
            result.data.forEach(item => {
                const index = Number(item.index);

                if (
                    Number.isInteger(index) &&
                    index >= 0 &&
                    dataKA[index] &&
                    item.realisasi_ka_id !== undefined &&
                    item.realisasi_ka_id !== null
                ) {
                    dataKA[index].realisasi_ka_id =
                        item.realisasi_ka_id;
                }
            });
        }

        barisDikirim.forEach(index => {
            if (
                getRowVersion(index) === versiDikirim.get(index)
            ) {
                savedRows.add(index);
                savedStatuses.set(index, dataKA[index].status);
                dirtyRows.delete(index);

            } else {
                dirtyRows.add(index);
            }
        });

        savingRows = new Set();
        saveGagal = false;

        renderTable();

        if (dirtyRows.size > 0 || isDirty) {
            isDirty = true;

            setSaveStatus(
                'pending',
                'Ada perubahan baru yang belum disimpan…'
            );

        } else {
            isDirty = false;

            const jam = formatJamStatusSimpan();

            if (result.serah_terima_sinkron === false) {
                setSaveStatus(
                    'saved',
                    `Tersimpan pukul ${jam} (sinkron ke Serah Terima gagal)`
                );

            } else {
                setSaveStatus(
                    'saved',
                    otomatis ?
                    `Tersimpan otomatis pukul ${jam}` :
                    `Tersimpan pukul ${jam}`
                );
            }
        }

        if (!otomatis) {
            alert('Berhasil disimpan!');
        }

    } catch (error) {
        console.error('Gagal menyimpan realisasi:', error);

        saveGagal = true;
        isDirty = true;

        barisDikirim.forEach(index => dirtyRows.add(index));
        savingRows = new Set();

        if (error.permanen) {
            setSaveStatus(
                'error',
                `Gagal menyimpan: ${error.message}`
            );

        } else {
            setSaveStatus(
                'error',
                'Gagal menyimpan. Silakan klik Simpan untuk mencoba lagi.'
            );
        }

        if (!otomatis) {
            alert(
                'Realisasi belum berhasil disimpan.\n\n' +
                error.message
            );
        }

    } finally {
        isSaving = false;

        refreshRowButtons();

        if (tombol) {
            tombol.disabled = false;
            tombol.textContent = '▣  Simpan Semua';
        }

        if (saveQueued) {
            const manual = queuedManual;

            saveQueued = false;
            queuedManual = false;

            if (manual && (isDirty || dirtyRows.size > 0)) {
                simpanRealisasi();
            }
        }
    }
}

async function tungguSimpanSelesai() {
    while (isSaving) {
        await sleep(150);
    }

    return !isDirty && !saveGagal;
}

window.addEventListener('beforeunload', event => {
    if (isDirty) {
        event.preventDefault();
        event.returnValue = '';
    }
});

window.addEventListener('offline', () => {
    setSaveStatus(
        'error',
        'Tidak ada koneksi internet.'
    );
});