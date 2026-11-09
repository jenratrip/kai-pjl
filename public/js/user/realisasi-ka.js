window.realisasiKaConfig = window.realisasiKaConfig || {};

const CONFIG = window.realisasiKaConfig;
const SETTINGS = CONFIG.settings || {};
const TIME_WINDOW = window.RealisasiKaTimeWindow;
const INTERACTIONS = window.RealisasiKaInteractions;

if (!TIME_WINDOW) {
    throw new Error('Filter waktu Realisasi KA belum dimuat.');
}

if (!INTERACTIONS) {
    throw new Error('Interaksi Realisasi KA belum dimuat.');
}

if (!Array.isArray(CONFIG.data)) {
    CONFIG.data = [];
}

const ZONA_WAKTU = CONFIG.timeZone || 'Asia/Jakarta';

const BATAS_CEPAT_MENIT = 2;

const BATAS_AWAS_MENIT = Number(
    SETTINGS.jarakMinimumAntarKaMenit ?? 0
);

const BATAS_PENUTUPAN_PALANG_MENIT = Number(
    SETTINGS.batasPenutupanPalangMenit ?? 0
);

const WARNING_ANTAR_KA_AKTIF =
    SETTINGS.warningAntarKaAktif === true;

const WARNING_PENUTUPAN_PALANG_AKTIF =
    SETTINGS.warningPenutupanPalangAktif === true;

const JUMLAH_KOLOM_TABEL = 12;
const BATAS_KARAKTER_CATATAN = 5000;
let isPrinting = false;

/*
 * BATAS_PENUTUPAN_PALANG_MENIT dan
 * WARNING_PENUTUPAN_PALANG_AKTIF sudah dibaca dari database.
 *
 * Peringatan penutupan palang belum diterapkan karena data
 * waktu penutupan/pembukaan palang belum tersedia pada objek KA.
 */

/* =====================================================
   DATA KA
===================================================== */

let dataKA = CONFIG.data.map((ka, index) => ({
    no: ka.no ?? index + 1,
    nomor: ka.nomor ?? '',
    nama: ka.nama ?? '',
    hulu: ka.hulu ?? '',
    hilir: ka.hilir ?? '',
    relasi: ka.relasi ?? '',
    arah: String(ka.arah ?? '').trim().toUpperCase(),
    jadwal: ka.jadwal ?? '-',
    aktual: ka.aktual ?? '',
    status: ['JALAN', 'BATAL', 'BELUM'].includes(ka.status)
        ? ka.status
        : 'BELUM',
    semboyan21: ka.semboyan21 ?? '',
    genta: ka.genta ?? '',
    pintu: ka.pintu ?? '',
    semboyan35: ka.semboyan35 ?? '',
    keterangan: ka.keterangan ?? '',
    is_bersamaan: ka.is_bersamaan === true,
    jadwal_ka_id: ka.jadwal_ka_id ?? null,
    realisasi_ka_id: ka.realisasi_ka_id ?? null,
    is_manual: Boolean(ka.is_manual)
}));

const persistedRows = new Set(
    dataKA.flatMap((ka, index) => TIME_WINDOW.hasRecordedRealization(ka) ? [index] : [])
);

let manualCheck = {
    semboyan21: '',
    genta: '',
    pintu: '',
    semboyan35: ''
};

/* =====================================================
   UTILITAS
===================================================== */

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getWIBTime() {
    const parts = new Intl.DateTimeFormat('en-GB', {
        timeZone: ZONA_WAKTU,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hourCycle: 'h23'
    }).formatToParts(new Date());

    const result = {};

    parts.forEach(part => {
        if (['hour', 'minute', 'second'].includes(part.type)) {
            result[part.type] = Number(part.value);
        }
    });

    return {
        hour: result.hour ?? 0,
        minute: result.minute ?? 0,
        second: result.second ?? 0
    };
}

function getCurrentMinutes() {
    const now = getWIBTime();
    return now.hour * 60 + now.minute;
}

function getCurrentPreciseMinutes() {
    const now = getWIBTime();

    return now.hour * 60 + now.minute + now.second / 60;
}

function getNowHHMM() {
    const now = getWIBTime();

    return (
        String(now.hour).padStart(2, '0') + ':' +
        String(now.minute).padStart(2, '0')
    );
}

function getNowHHMMSS() {
    const now = getWIBTime();

    return (
        String(now.hour).padStart(2, '0') + ':' +
        String(now.minute).padStart(2, '0') + ':' +
        String(now.second).padStart(2, '0')
    );
}

function timeToMinutes(time) {
    if (!time || time === '-') {
        return null;
    }

    const match = String(time).trim().match(
        /^(\d{1,2}):(\d{2})(?::\d{2})?$/
    );

    if (!match) {
        return null;
    }

    const hours = Number(match[1]);
    const minutes = Number(match[2]);

    if (
        !Number.isInteger(hours) ||
        !Number.isInteger(minutes) ||
        hours < 0 ||
        hours > 23 ||
        minutes < 0 ||
        minutes > 59
    ) {
        return null;
    }

    return hours * 60 + minutes;
}

function formatMinutes(totalMinutes) {
    totalMinutes = ((totalMinutes % 1440) + 1440) % 1440;

    return (
        String(Math.floor(totalMinutes / 60)).padStart(2, '0') +
        ':' +
        String(totalMinutes % 60).padStart(2, '0')
    );
}

/* =====================================================
   JAM REALTIME DAN RENTANG JAM SAAT INI
===================================================== */

function updateWindowRange() {
    const clock = document.getElementById('jamSekarang');
    const range = document.getElementById('windowRange');

    if (clock) {
        clock.textContent = getNowHHMMSS();
    }

    const { startMinutes, endMinutes } = TIME_WINDOW.getWindow(getCurrentMinutes());

    if (range) {
        const jamMulai = formatMinutes(startMinutes).replace(':', '.');
        const jamSelesai = formatMinutes(endMinutes).replace(':', '.');

        range.textContent =
            `Rentang jadwal ${jamMulai}–${jamSelesai} WIB`;
    }
}

/* =====================================================
   FILTER JADWAL BERDASARKAN JAM SAAT INI
===================================================== */

function getVisibleKA() {
    return isPrinting
        ? TIME_WINDOW.getShiftRows(dataKA)
        : TIME_WINDOW.getWebRows(
            dataKA,
            getCurrentPreciseMinutes(),
            (_, index) => persistedRows.has(index) && !dirtyRows.has(index),
            CONFIG.shiftStart,
            CONFIG.shiftEnd
        );
}

function getVisibleIds() {
    return getVisibleKA()
        .map(item => item.index)
        .join(',');
}

/* =====================================================
   PERHITUNGAN SELISIH WAKTU
===================================================== */

function getSelisihMenit(ka) {
    const jadwal = timeToMinutes(ka.jadwal);
    const aktual = timeToMinutes(ka.aktual);

    if (jadwal === null || aktual === null) {
        return null;
    }

    let diff = aktual - jadwal;

    // Menangani pergantian hari.
    if (diff > 720) diff -= 1440;
    if (diff < -720) diff += 1440;

    return diff;
}

/* =====================================================
   WARNA STATUS OTOMATIS
===================================================== */

function getStatusColor(ka) {
    if (ka.status === 'BATAL') {
        return 'batal';
    }

    if (ka.status !== 'JALAN') {
        return 'belum';
    }

    const selisih = getSelisihMenit(ka);

    if (selisih === null) {
        return 'tepat';
    }

    /*
     * Peringatan keterlambatan tinggi diperiksa lebih dahulu
     * agar tidak tertimpa kategori keterlambatan biasa.
     */
    const delayStatus = TIME_WINDOW.getDelayStatus(selisih, SETTINGS);
    if (delayStatus !== 'tepat') {
        return delayStatus;
    }

    if (selisih < -BATAS_CEPAT_MENIT) {
        return 'cepat';
    }

    return 'tepat';
}

function getStatusNote(ka, color) {
    if (color === 'batal') {
        return 'KA batal';
    }

    const selisih = getSelisihMenit(ka);

    if (color === 'telat-tinggi') {
        return `Peringatan keterlambatan tinggi: ${selisih} menit`;
    }

    if (color === 'telat') {
        return `Terlambat ${selisih} menit`;
    }

    if (color === 'cepat') {
        return `Lebih cepat ${Math.abs(selisih)} menit`;
    }

    if (color === 'tepat') {
        if (selisih === null || selisih === 0) {
            return 'Tepat waktu';
        }

        return selisih > 0
            ? `Terlambat ${selisih} menit (dalam toleransi)`
            : `Lebih cepat ${Math.abs(selisih)} menit (dalam toleransi)`;
    }

    return '';
}

/* =====================================================
   AWAS 2 ARAH
===================================================== */

function getParitas(ka) {
    const match = String(ka.nomor ?? '').match(/\d+/);

    if (!match) {
        return '';
    }

    return Number(match[0].slice(-1)) % 2 === 0
        ? 'GENAP'
        : 'GANJIL';
}

function getLabelArah(ka) {
    if (ka.arah === 'HULU' || ka.arah === 'HILIR') {
        return `dari ${ka.arah.toLowerCase()}`;
    }

    const paritas = getParitas(ka);

    return paritas
        ? `nomor ${paritas.toLowerCase()}`
        : 'arah tidak diketahui';
}

function isBerlawananArah(a, b) {
    const arahValid = ['HULU', 'HILIR'];

    if (
        arahValid.includes(a.arah) &&
        arahValid.includes(b.arah)
    ) {
        return a.arah !== b.arah;
    }

    const paritasA = getParitas(a);
    const paritasB = getParitas(b);

    return (
        paritasA !== '' &&
        paritasB !== '' &&
        paritasA !== paritasB
    );
}

function getWaktuRujukan(ka) {
    if (ka.status === 'JALAN' && ka.aktual) {
        return timeToMinutes(ka.aktual);
    }

    return timeToMinutes(ka.jadwal);
}

function cariAwas2Arah() {
    /*
     * Peringatan tidak dihitung jika dinonaktifkan oleh Admin
     * atau jarak minimum belum dikonfigurasi.
     */
    if (
        !WARNING_ANTAR_KA_AKTIF ||
        !Number.isFinite(BATAS_AWAS_MENIT) ||
        BATAS_AWAS_MENIT <= 0
    ) {
        return {
            indexSet: new Set(),
            pairs: []
        };
    }

    const kandidat = getVisibleKA()
        .map(({ ka, index }) => ({
            ka,
            index,
            waktu: getWaktuRujukan(ka)
        }))
        .filter(item =>
            item.ka.status !== 'BATAL' &&
            item.waktu !== null
        );

    const indexSet = new Set();
    const pairs = [];

    for (let i = 0; i < kandidat.length; i++) {
        for (let j = i + 1; j < kandidat.length; j++) {
            const a = kandidat[i];
            const b = kandidat[j];

            if (!isBerlawananArah(a.ka, b.ka)) {
                continue;
            }

            /*
             * Peringatan ditujukan pada pasangan yang setidaknya
             * salah satu KA-nya belum diproses.
             */
            if (
                a.ka.status !== 'BELUM' &&
                b.ka.status !== 'BELUM'
            ) {
                continue;
            }

            let selisih = Math.abs(a.waktu - b.waktu);
            selisih = Math.min(selisih, 1440 - selisih);

            // Peringatan aktif jika jarak waktu <= batas minimum.
            if (selisih > BATAS_AWAS_MENIT) {
                continue;
            }

            indexSet.add(a.index);
            indexSet.add(b.index);

            pairs.push({ a, b, selisih });
        }
    }

    pairs.sort((a, b) => a.selisih - b.selisih);

    return { indexSet, pairs };
}

function updateAwasBanner(info) {
    const banner = document.getElementById('awasBanner');

    if (!banner) {
        return;
    }

    if (!info.pairs.length) {
        banner.hidden = true;
        banner.innerHTML = '';
        return;
    }

    const describe = item =>
        `KA ${escapeHtml(item.ka.nomor)} ` +
        `(${escapeHtml(getLabelArah(item.ka))}, ` +
        `${formatMinutes(item.waktu)})`;

    const lines = info.pairs.map(pair => `
        <li>
            ${describe(pair.a)} &amp; ${describe(pair.b)}
            <span class="awas-gap">
                selisih ${pair.selisih} menit
            </span>
        </li>
    `).join('');

    banner.innerHTML = `
        <div class="awas-title">⚠ AWAS 2 ARAH</div>
        <div class="awas-text">
            KA dari dua arah akan melintas berdekatan.
            Tingkatkan kewaspadaan.
        </div>
        <ul class="awas-list">${lines}</ul>
    `;

    banner.hidden = false;
}

/* =====================================================
   STATUS PENYIMPANAN PER BARIS
===================================================== */

let dirtyRows = new Set();
let savingRows = new Set();
let savedRows = new Set();
let rowVersions = new Map();

function getRowVersion(index) {
    return rowVersions.get(index) || 0;
}

function incrementRowVersion(index) {
    rowVersions.set(index, getRowVersion(index) + 1);
}

function stateBaris(index) {
    if (savingRows.has(index)) {
        return 'saving';
    }

    if (dirtyRows.has(index)) {
        return 'dirty';
    }

    if (
        savedRows.has(index) ||
        persistedRows.has(index)
    ) {
        return 'saved';
    }

    return 'dirty';
}

function refreshRowButtons() {
    document.querySelectorAll('[data-save-row]').forEach(button => {
        const index = Number(button.dataset.saveRow);
        const state = stateBaris(index);

        const labels = {
            dirty: 'Simpan',
            saving: 'Menyimpan…',
            saved: '✓ Tersimpan'
        };

        button.className = `row-save-btn ${state}`;
        button.textContent = labels[state] || 'Simpan';
        button.disabled = state === 'saving';
    });
}

function simpanBaris(index) {
    if (!dataKA[index]) {
        return;
    }

    dirtyRows.add(index);
    isDirty = true;

    refreshRowButtons();
    simpanRealisasi({ index });
}

/* =====================================================
   KOMPONEN TABEL
===================================================== */

function createCheckGroup(value, index, field) {
    const yesClass = value === 'yes' ? 'active-yes' : 'inactive';
    const noClass = value === 'no' ? 'active-no' : 'inactive';
    const printLabel = TIME_WINDOW.getCheckPrintLabel(value);

    return `
        <span class="check-print-label">${printLabel}</span>
        <div class="check-group">
            <button type="button"
                class="check ${yesClass}"
                onclick="setCheck(this, ${index}, '${field}', 'yes')"
                aria-label="Ya"
                title="Ya">✓</button>

            <button type="button"
                class="check ${noClass}"
                onclick="setCheck(this, ${index}, '${field}', 'no')"
                aria-label="Tidak"
                title="Tidak">×</button>
        </div>
    `;
}

function createStatus(ka, index) {
    const color = getStatusColor(ka);

    const jalanClassMap = {
        tepat: 'is-tepat',
        telat: 'is-telat',
        'telat-tinggi': 'is-telat',
        cepat: 'is-cepat'
    };

    const jalanActive = ka.status === 'JALAN'
        ? (jalanClassMap[color] || 'is-tepat')
        : '';

    const batalActive = ka.status === 'BATAL' ? 'is-batal' : '';
    const note = getStatusNote(ka, color);
    const selisih = ka.status === 'JALAN' ? getSelisihMenit(ka) : null;
    const printDelayNote = selisih !== null && selisih > 0
        ? TIME_WINDOW.getPrintDelayNote(selisih, SETTINGS)
        : note;
    const printLabel = ka.status === 'JALAN' || ka.status === 'BATAL'
        ? ka.status
        : 'BELUM';

    return `
        <span class="status-print-label">${printLabel}</span>

        <div class="status-group">
            <button type="button"
                class="status-btn btn-jalan ${jalanActive}"
                onclick="setStatus(${index}, 'JALAN')"
                aria-label="Tandai KA ${escapeHtml(ka.nomor)} berjalan"
                title="Jalan"
                aria-pressed="${ka.status === 'JALAN'}">
                ✓
            </button>

            <button type="button"
                class="status-btn btn-batal ${batalActive}"
                onclick="setStatus(${index}, 'BATAL')"
                aria-label="Tandai KA ${escapeHtml(ka.nomor)} batal"
                title="Batal"
                aria-pressed="${ka.status === 'BATAL'}">
                ✕
            </button>
        </div>

        ${note
            ? `<div class="status-note ${color}">${escapeHtml(note)}</div>`
            : ''}

        ${printDelayNote
            ? `<div class="status-note-print ${color}">${escapeHtml(printDelayNote)}</div>`
            : ''}
    `;
}

function createRowAction(index) {
    const state = stateBaris(index);

    return `
        <button type="button"
            class="row-save-btn ${state}"
            data-save-row="${index}"
            onclick="simpanBaris(${index})">
            ${state === 'saved' ? '✓ Tersimpan' : 'Simpan'}
        </button>
    `;
}

function renderTable() {
    const tbody = document.getElementById('kaTableBody');

    if (!tbody) {
        return;
    }

    const awasInfo = cariAwas2Arah();
    const bersamaanIndexes = TIME_WINDOW.getSimultaneousRowIndexes(dataKA);
    updateAwasBanner(awasInfo);

    const visibleKA = getVisibleKA();

    tbody.innerHTML = '';

    if (!visibleKA.length) {
        const message = dataKA.length === 0
            ? 'Belum ada jadwal KA untuk dinas aktif ini.'
            : 'Tidak ada KA belum dicatat dalam 60 menit ke depan.';

        tbody.innerHTML = `
            <tr>
                <td colspan="${JUMLAH_KOLOM_TABEL}"
                    style="height:85px;text-align:center;color:#8a93a1">
                    ${escapeHtml(message)}
                </td>
            </tr>
        `;

        updateSummary();
        updateWindowRange();
        refreshRowButtons();

        return;
    }

    visibleKA.forEach(({ ka, index }) => {
        const row = document.createElement('tr');
        row.dataset.index = index;

        if (awasInfo.indexSet.has(index)) {
            row.classList.add('awas-row');
        }
        if (bersamaanIndexes.has(index)) {
            row.classList.add('bersamaan-row');
        }

        const relasi = ka.relasi || [ka.hulu, ka.hilir]
            .filter(Boolean)
            .join(' – ') || '-';

        row.innerHTML = `
            <td>
                <strong>${escapeHtml(ka.nomor)}</strong>
                ${ka.is_manual
                    ? '<div style="margin-top:3px;color:#d9923e;font-size:8px">MANUAL</div>'
                    : ''}
            </td>

            <td>
                ${escapeHtml(ka.nama)}
                ${bersamaanIndexes.has(index)
                    ? '<div class="bersamaan-badge"><span class="screen-only">⚠ BERSAMAAN</span><span class="print-only">BERSAMAAN</span></div>'
                    : ''}
                ${awasInfo.indexSet.has(index)
                    ? '<div class="awas-badge">⚠ AWAS 2 ARAH</div>'
                    : ''}
            </td>

            <td>${escapeHtml(relasi)}</td>
            <td>${escapeHtml(ka.jadwal)}</td>

            <td>
                <input type="time"
                    class="actual-time"
                    value="${escapeHtml(ka.aktual)}"
                    onchange="updateActualTime(${index}, this.value)">
                <span class="actual-time-print">${escapeHtml(ka.aktual || '-')}</span>
            </td>

            <td>${createStatus(ka, index)}</td>

            <td>${createCheckGroup(ka.semboyan21, index, 'semboyan21')}</td>
            <td>${createCheckGroup(ka.genta, index, 'genta')}</td>
            <td>${createCheckGroup(ka.pintu, index, 'pintu')}</td>
            <td>${createCheckGroup(ka.semboyan35, index, 'semboyan35')}</td>

            <td>
                <input type="text"
                    class="note-input"
                    value="${escapeHtml(ka.keterangan)}"
                    placeholder="Keterangan"
                    oninput="updateKeterangan(${index}, this.value)">
                <span class="note-input-print">${escapeHtml(ka.keterangan || '-')}</span>
            </td>

            <td>${createRowAction(index)}</td>
        `;

        tbody.appendChild(row);
    });

    updateSummary();
    updateWindowRange();
    refreshRowButtons();
}

/* =====================================================
   AKSI STATUS DAN PEMERIKSAAN
===================================================== */

function setStatus(index, status) {
    const ka = dataKA[index];

    if (!ka || !['JALAN', 'BATAL'].includes(status)) {
        return;
    }

    INTERACTIONS.toggleStatus(ka, status, getNowHHMM());

    incrementRowVersion(index);
    tandaiBerubah(index);
    renderTable();
}

function updateActualTime(index, value) {
    if (!dataKA[index]) {
        return;
    }

    dataKA[index].aktual = value;
    incrementRowVersion(index);

    tandaiBerubah(index);
    renderTable();
}

function setCheck(button, index, field, value) {
    if (!dataKA[index]) {
        return;
    }

    INTERACTIONS.setCheckValue(dataKA[index], field, value);
    incrementRowVersion(index);

    tandaiBerubah(index);
    renderTable();
}

function updateKeterangan(index, value) {
    if (!dataKA[index]) {
        return;
    }

    dataKA[index].keterangan = value;
    incrementRowVersion(index);

    tandaiBerubah(index);
}

/* =====================================================
   RINGKASAN
===================================================== */

function updateSummary() {
    const { total, tepat, telat, cepat, batal, belum } =
        TIME_WINDOW.summarizeShiftRows(dataKA, getStatusColor);

    const setText = (id, value) => {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = value;
        }
    };

    const setWidth = (id, value) => {
        const element = document.getElementById(id);

        if (element) {
            element.style.width = value;
        }
    };

    setText('totalKA', total);
    setText('jalanKA', tepat);
    setText('telatKA', telat);
    setText('cepatKA', cepat);
    setText('batalKA', batal);
    setText('belumKA', belum);

    const selesai = tepat + telat + cepat + batal;
    const progress = total
        ? Math.round((selesai / total) * 100)
        : 0;

    setText('progressText', `${progress}%`);

    const persen = jumlah =>
        `${total ? (jumlah / total) * 100 : 0}%`;

    setWidth('progressGreen', persen(tepat));
    setWidth('progressYellow', persen(telat));
    setWidth('progressBlue', persen(cepat));
    setWidth('progressRed', persen(batal));
    setWidth('progressGray', persen(belum));
}

/* =====================================================
   PENYIMPANAN MANUAL
===================================================== */

let isSaving = false;
let isDirty = false;
const queuedSaveIndexes = new Set();
let allowUnloadWithUnsavedChanges = false;

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

function tandaiBerubah(index) {
    isDirty = true;
    dirtyRows.add(index);
    savedRows.delete(index);

    setSaveStatus('pending', 'Perubahan belum disimpan…');
    refreshRowButtons();
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
    const tombol = document.getElementById('simpanBtn');
    const labelTombol = tombol?.querySelector('[data-save-label]');

    if (dataKA.length === 0) {
        alert('Belum ada data KA untuk disimpan.');
        return;
    }

    const targetIndexes = INTERACTIONS.getSaveIndexes(
        [...dirtyRows],
        options.index,
        options.indices
    ).filter(index => Number.isInteger(index) && index >= 0 && dataKA[index]);

    if (options.index !== undefined && targetIndexes.length === 1) {
        dirtyRows.add(targetIndexes[0]);
        isDirty = true;
    }

    const indexesToSave = [...new Set(targetIndexes)].filter(index => dirtyRows.has(index));

    if (indexesToSave.length === 0) {
        setSaveStatus('idle', 'Tidak ada perubahan untuk disimpan.');
        return;
    }

    if (isSaving) {
        indexesToSave.forEach(index => queuedSaveIndexes.add(index));
        return;
    }

    isSaving = true;

    const versiDikirim = new Map();
    const barisDikirim = new Set(indexesToSave);
    const snapshot = indexesToSave.map(index => {
        versiDikirim.set(index, getRowVersion(index));
        return { ...dataKA[index] };
    });

    savingRows = new Set(barisDikirim);

    refreshRowButtons();
    setSaveStatus('saving', 'Menyimpan…');

    if (tombol) {
        tombol.disabled = true;
        if (labelTombol) {
            labelTombol.textContent = 'Menyimpan...';
        } else {
            tombol.textContent = 'Menyimpan...';
        }
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
                save_mode: options.index === undefined ? 'batch' : 'row',
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
            const pesanValidasi = result.errors
                ? Object.values(result.errors).flat().join('\n')
                : result.message;

            const error = new Error(
                pesanValidasi || 'Gagal menyimpan realisasi KA.'
            );

            error.permanen = response.status === 422;

            throw error;
        }

        const confirmedRows = TIME_WINDOW.getConfirmedRows(
            result.data,
            indexesToSave
        );

        const snapshotByIndex = new Map(
            indexesToSave.map((index, payloadIndex) => [index, snapshot[payloadIndex]])
        );

        confirmedRows.forEach((realizationId, index) => {
            dataKA[index].realisasi_ka_id = realizationId;
        });

        barisDikirim.forEach(index => {
            const savedSnapshot = snapshotByIndex.get(index);

            if (TIME_WINDOW.hasRecordedRealization(savedSnapshot)) {
                persistedRows.add(index);
            } else {
                persistedRows.delete(index);
            }

            if (
                getRowVersion(index) === versiDikirim.get(index)
            ) {
                savedRows.add(index);
                dirtyRows.delete(index);
            } else {
                dirtyRows.add(index);
            }
        });

        savingRows = new Set();
        isDirty = dirtyRows.size > 0;
        refreshTimeFilter();

        if (isDirty) {
            setSaveStatus(
                'pending',
                'Ada perubahan baru yang belum disimpan…'
            );
        } else {
            const jam = formatJamStatusSimpan();

            if (result.serah_terima_sinkron === false) {
                setSaveStatus(
                    'saved',
                    `Tersimpan pukul ${jam} (sinkron ke Serah Terima gagal)`
                );
            } else {
                setSaveStatus(
                    'saved',
                    `Tersimpan pukul ${jam}`
                );
            }
        }

        if (options.index === undefined) {
            alert(
                result.message ||
                'Realisasi KA berhasil disimpan ke database.'
            );
        }

    } catch (error) {
        console.error('Gagal menyimpan realisasi:', error);

        isDirty = true;

        barisDikirim.forEach(index => dirtyRows.add(index));
        savingRows = new Set();
        setSaveStatus(
            'error',
            `Gagal menyimpan: ${error.message}. Tekan Simpan untuk mencoba lagi.`
        );
        alert(
            'Realisasi belum berhasil disimpan.\n\n' +
            error.message
        );
    } finally {
        isSaving = false;

        refreshRowButtons();

        if (tombol) {
            tombol.disabled = false;
            if (labelTombol) {
                labelTombol.textContent = 'Simpan Semua';
            } else {
                tombol.textContent = 'Simpan Semua';
            }
        }

        if (queuedSaveIndexes.size > 0) {
            const queuedIndexes = [...queuedSaveIndexes];
            queuedSaveIndexes.clear();

            if (queuedIndexes.some(index => dirtyRows.has(index))) {
                simpanRealisasi({ indices: queuedIndexes });
            }
        }
    }
}

window.addEventListener('beforeunload', event => {
    if ((isDirty || isSaving) && !allowUnloadWithUnsavedChanges) {
        event.preventDefault();
        event.returnValue = '';
    }
});

window.addEventListener('offline', () => {
    setSaveStatus(
        'error',
        'Tidak ada koneksi. Perubahan tetap tersimpan sementara di halaman.'
    );
});

/* =====================================================
   TOMBOL SELESAI
===================================================== */

function cetakDokumen() {
    window.print();
}

window.addEventListener('beforeprint', () => {
    isPrinting = true;
    renderTable();
});

window.addEventListener('afterprint', () => {
    isPrinting = false;
    renderTable();
});

/* =====================================================
   TAMBAH KA MANUAL
===================================================== */

function toggleFormTambahKA(forceOpen) {
    const form = document.getElementById('formTambahKa');
    const btn = document.getElementById('toggleTambahKa');

    if (!form || !btn) {
        return;
    }

    const open = typeof forceOpen === 'boolean'
        ? forceOpen
        : form.hidden;

    form.hidden = !open;
    btn.hidden = open;
    btn.setAttribute('aria-expanded', String(open));

    if (open) {
        form.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });

        document.getElementById('manualNomor')?.focus();
    } else {
        resetFormManual();
        btn.focus();
    }
}

function setManualCheck(button) {
    const group = button.dataset.group;
    const value = button.dataset.value;

    document.querySelectorAll(`[data-group="${group}"]`).forEach(item => {
        item.classList.remove('active-yes', 'active-no');
        item.classList.add('inactive');
    });

    button.classList.remove('inactive');
    button.classList.add(
        value === 'yes' ? 'active-yes' : 'active-no'
    );

    const fieldMap = {
        manual21: 'semboyan21',
        manualGenta: 'genta',
        manualPintu: 'pintu',
        manual35: 'semboyan35'
    };

    if (fieldMap[group]) {
        manualCheck[fieldMap[group]] = value;
    }
}

function resetManualChecks() {
    manualCheck = {
        semboyan21: '',
        genta: '',
        pintu: '',
        semboyan35: ''
    };

    document.querySelectorAll('.mini-check').forEach(button => {
        button.classList.remove('active-yes', 'active-no');
        button.classList.add('inactive');
    });
}

function resetFormManual() {
    [
        'manualNomor',
        'manualNama',
        'manualRelasi',
        'manualAktual',
        'manualKeterangan'
    ].forEach(id => {
        const element = document.getElementById(id);

        if (element) {
            element.value = '';
        }
    });

    resetManualChecks();
}

function ambilNilaiInput(id) {
    const element = document.getElementById(id);
    return element ? element.value.trim() : '';
}

function tambahKAManual() {
    const nomor = ambilNilaiInput('manualNomor');
    const nama = ambilNilaiInput('manualNama');
    const relasi = ambilNilaiInput('manualRelasi');
    const keterangan = ambilNilaiInput('manualKeterangan');
    const aktual = ambilNilaiInput('manualAktual') || getNowHHMM();

    if (!nomor) {
        alert('Nomor KA wajib diisi.');
        document.getElementById('manualNomor')?.focus();
        return;
    }

    if (!nama) {
        alert('Nama KA wajib diisi.');
        document.getElementById('manualNama')?.focus();
        return;
    }

    const kaBaru = {
        no: dataKA.length + 1,
        nomor,
        nama,
        hulu: '',
        hilir: '',
        relasi,
        arah: '',
        jadwal: '-',
        aktual,
        status: 'JALAN',
        semboyan21: manualCheck.semboyan21,
        genta: manualCheck.genta,
        pintu: manualCheck.pintu,
        semboyan35: manualCheck.semboyan35,
        keterangan: keterangan || 'KA tambahan',
        jadwal_ka_id: null,
        realisasi_ka_id: null,
        is_manual: true
    };

    dataKA.push(kaBaru);

    const indexBaru = dataKA.length - 1;

    incrementRowVersion(indexBaru);
    dirtyRows.add(indexBaru);

    renderTable();
    toggleFormTambahKA(false);
    tandaiBerubah(indexBaru);
}

document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') {
        return;
    }

    const form = document.getElementById('formTambahKa');

    if (form && !form.hidden) {
        toggleFormTambahKA(false);
    }
});

/* =====================================================
   CATATAN DINAS - MODAL
===================================================== */

function openCatatanModal() {
    const modal = document.getElementById('catatanModal');

    if (!modal) {
        return;
    }

    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('cdm-lock');

    document.getElementById('cdmCatatan')?.focus();
}

function closeCatatanModal() {
    const modal = document.getElementById('catatanModal');

    if (!modal) {
        return;
    }

    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('cdm-lock');
}

function initCatatanModal() {
    const modal = document.getElementById('catatanModal');

    if (!modal) {
        return;
    }

    const form = document.getElementById('catatanForm');
    const textarea = document.getElementById('cdmCatatan');
    const counter = document.getElementById('cdmCounter');
    const submitBtn = form
        ? form.querySelector('.cdm-save')
        : null;

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
            modal.classList.contains('open')
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
        form.addEventListener('submit', event => {
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

            if (isDirty || isSaving) {
                const lanjut = confirm(
                    'Ada perubahan Realisasi KA yang belum disimpan.\n\n' +
                    'Simpan Realisasi KA melalui tombol Simpan terlebih dahulu, ' +
                    'atau lanjut tanpa menyimpan perubahan tersebut?'
                );

                if (!lanjut) {
                    sedangKirim = false;
                    setTombol(false, labelAwal);
                    return;
                }

                allowUnloadWithUnsavedChanges = true;
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

    setSaveStatus('idle', 'Perubahan disimpan hanya melalui tombol Simpan.');

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
