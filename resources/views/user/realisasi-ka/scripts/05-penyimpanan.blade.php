/* =====================================================
PENYIMPANAN MANUAL
===================================================== */

let isSaving = false;
let isDirty = false;
let saveQueued = false;
let saveGagal = false;

function setSaveStatus(state, text) {
    const box = document.getElementById('saveStatus');
    const label = document.getElementById('saveStatusText');

    if (box) {
        box.classList.remove('idle', 'pending', 'saving', 'saved', 'error',
            'text-slate-600', 'text-amber-700', 'text-blue-700', 'text-green-700', 'text-rose-700');
        box.classList.add('save-status', state);
        const colorClass = {
            idle: 'text-slate-600',
            pending: 'text-amber-700',
            saving: 'text-blue-700',
            saved: 'text-green-700',
            error: 'text-rose-700'
        }[state] || 'text-slate-600';
        box.classList.add(colorClass);
    }
    if (label) label.textContent = text;
}

function tandaiBerubah(index) {
    isDirty = true;

    if (index !== undefined && index !== null) dirtyRows.add(index);

    setSaveStatus('pending', 'Ada perubahan yang belum disimpan. Tekan Simpan Semua.');
    refreshRowButtons();
}

function ambilCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function formatJamStatusSimpan() {
    const now = getWIBTime();
    return String(now.hour).padStart(2, '0') + ':' + String(now.minute).padStart(2, '0');
}

async function simpanRealisasi() {
    const tombol = document.getElementById('simpanBtn');

    if (dataKA.length === 0) {
        alert('Belum ada data KA untuk disimpan.');
        return;
    }

    if (isSaving) {
        saveQueued = true;
        return;
    }

    isSaving = true;
    const versiDikirim = new Map();
    const barisDikirim = new Set(dirtyRows);

    dataKA.forEach((_, index) => versiDikirim.set(index, getRowVersion(index)));
    const snapshot = JSON.parse(JSON.stringify(dataKA));

    isDirty = false;
    savingRows = new Set(barisDikirim);
    dirtyRows = new Set();
    refreshRowButtons();
    setSaveStatus('saving', 'Menyimpan data…');

    if (tombol) {
        tombol.disabled = true;
        tombol.querySelector('[data-save-label]')?.replaceChildren('Menyimpan…');
    }

    try {
        const response = await fetch(CONFIG.storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': ambilCsrfToken()
            },
            body: JSON.stringify({ data: snapshot })
        });

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            let message = response.status === 419
                ? 'Sesi berakhir. Muat ulang halaman, lalu coba lagi.'
                : response.status === 401 || response.status === 403
                    ? 'Akses ditolak. Silakan login kembali.'
                    : 'Server tidak mengembalikan JSON. Periksa route dan log Laravel.';
            const error = new Error(message);
            error.permanen = [401, 403, 419].includes(response.status);
            throw error;
        }

        const result = await response.json();
        if (!response.ok) {
            const validation = result.errors ? Object.values(result.errors).flat().join('\n') : result.message;
            const error = new Error(validation || 'Gagal menyimpan realisasi KA.');
            error.permanen = response.status === 422;
            throw error;
        }

        if (Array.isArray(result.data)) {
            result.data.forEach(item => {
                const index = Number(item.index);
                if (Number.isInteger(index) && index >= 0 && dataKA[index] && item.realisasi_ka_id != null) {
                    dataKA[index].realisasi_ka_id = item.realisasi_ka_id;
                }
            });
        }

        barisDikirim.forEach(index => {
            if (getRowVersion(index) === versiDikirim.get(index)) {
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
            setSaveStatus('pending', 'Ada perubahan baru yang belum disimpan. Tekan Simpan Semua.');
        } else {
            const jam = formatJamStatusSimpan();
            setSaveStatus(
                'saved',
                result.serah_terima_sinkron === false
                    ? `Tersimpan pukul ${jam} (sinkron ke Serah Terima gagal)`
                    : `Tersimpan pukul ${jam}`
            );
        }

        alert('Berhasil disimpan!');
    } catch (error) {
        console.error('Gagal menyimpan realisasi:', error);
        saveGagal = true;
        isDirty = true;
        barisDikirim.forEach(index => dirtyRows.add(index));
        savingRows = new Set();
        setSaveStatus('error', error.permanen
            ? `Gagal menyimpan: ${error.message}`
            : 'Gagal menyimpan. Periksa koneksi, lalu klik Simpan Semua untuk mencoba lagi.');
        alert('Realisasi belum berhasil disimpan.\n\n' + error.message);
    } finally {
        isSaving = false;
        refreshRowButtons();
        if (tombol) {
            tombol.disabled = false;
            const label = tombol.querySelector('[data-save-label]');
            if (label) label.textContent = 'Simpan Semua';
        }

        // Jika user menekan Simpan lagi ketika request pertama berjalan,
        // kirim satu request lanjutan hanya karena ada klik manual kedua.
        if (saveQueued) {
            saveQueued = false;
            if (isDirty || dirtyRows.size > 0) simpanRealisasi();
        }
    }
}

async function tungguSimpanSelesai() {
    while (isSaving) await sleep(150);
    return !isDirty && !saveGagal;
}

window.addEventListener('beforeunload', event => {
    if (isDirty) {
        event.preventDefault();
        event.returnValue = '';
    }
});

window.addEventListener('offline', () => {
    setSaveStatus('error', 'Tidak ada koneksi internet. Perubahan belum disimpan.');
});
