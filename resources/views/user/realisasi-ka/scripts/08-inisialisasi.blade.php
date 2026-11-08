/* =====================================================
REFRESH FILTER WAKTU
===================================================== */

let lastVisibleIds = '';

function refreshTimeFilter() {
    updateWindowRange();

    const currentVisibleIds = getVisibleIds();

    /*
     * Render ulang hanya jika daftar KA yang tampil berubah.
     * Ini mencegah input yang sedang diketik ter-reset setiap detik.
     */
    if (currentVisibleIds !== lastVisibleIds) {
        lastVisibleIds = currentVisibleIds;
        renderTable();
    }

    // Perbarui deteksi dan tampilan AWAS 2 ARAH.
    const infoAwas = cariAwas2Arah();
    updateAwasBanner(infoAwas);
}

/* =====================================================
MULAI
===================================================== */

function initRealisasiKA() {
    renderTable();

    lastVisibleIds = getVisibleIds();

    updateWindowRange();

    // Jalankan deteksi AWAS 2 ARAH saat halaman pertama dibuka.
    const infoAwas = cariAwas2Arah();
    updateAwasBanner(infoAwas);

    setSaveStatus('idle', 'Penyimpanan otomatis aktif');

    initCatatanModal();

    setInterval(refreshTimeFilter, 1000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRealisasiKA, {
        once: true
    });
} else {
    initRealisasiKA();
}
