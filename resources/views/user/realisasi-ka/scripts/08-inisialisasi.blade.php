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
}

/* =====================================================
MULAI
===================================================== */

function initRealisasiKA() {
    renderTable();

    lastVisibleIds = getVisibleIds();

    updateWindowRange();

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