/* =====================================================
   REALISASI KA - KAI PJL
   File: public/js/realisasi-ka.js
===================================================== */

/* =====================================================
   KONFIGURASI
===================================================== */

const ZONA_WAKTU = 'Asia/Jakarta';

const BATAS_CEPAT_MENIT = 2;
const BATAS_TELAT_MENIT = 5;
const BATAS_AWAS_MENIT = 10;

const JEDA_SIMPAN_STATUS = 300;
const JEDA_SIMPAN_CHECK = 600;
const JEDA_SIMPAN_KETIK = 800;
const JEDA_COBA_ULANG = 10000;

const JUMLAH_KOLOM_TABEL = 15;
const BATAS_KARAKTER_CATATAN = 5000;

/* =====================================================
   VALIDASI KONFIGURASI LARAVEL
===================================================== */

window.realisasiKaConfig = window.realisasiKaConfig || {};

if (!Array.isArray(window.realisasiKaConfig.data)) {
    window.realisasiKaConfig.data = [];
}

/* =====================================================
   DATA KA
===================================================== */

let dataKA = window.realisasiKaConfig.data.map((ka, index) => ({
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
    jadwal_ka_id: ka.jadwal_ka_id ?? null,
    realisasi_ka_id: ka.realisasi_ka_id ?? null,
    is_manual: Boolean(ka.is_manual)
}));

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

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/* =====================================================
   JAM REALTIME DAN RENTANG JAM SAAT INI
===================================================== */

/*
 * Mengambil rentang satu jam berdasarkan jam WIB saat ini.
 *
 * Contoh:
 * 13:00–13:59 -> rentang 13:00–14:00
 * 14:00–14:59 -> rentang 14:00–15:00
 * 23:00–23:59 -> rentang 23:00–00:00
 *
 * Rentang menggunakan batas awal inklusif dan batas akhir
 * eksklusif. Jadi, KA pukul 14:00 masuk rentang 14:00–15:00.
 */

function getCurrentHourWindowMinutes() {
    const currentMinutes = getCurrentMinutes();
    const startMinutes = Math.floor(currentMinutes / 60) * 60;
    const endMinutes = startMinutes + 60;

    return {
        startMinutes,
        endMinutes
    };
}

function updateWindowRange() {
    const clock = document.getElementById('jamSekarang');
    const range = document.getElementById('windowRange');

    if (clock) {
        clock.textContent = getNowHHMMSS();
    }

    const { startMinutes, endMinutes } =
        getCurrentHourWindowMinutes();

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

function isWithinCurrentHour(ka) {
    // KA manual selalu ditampilkan.
    if (ka.is_manual || !ka.jadwal || ka.jadwal === '-') {
        return true;
    }

    const jadwalMinutes = timeToMinutes(ka.jadwal);

    if (jadwalMinutes === null) {
        return false;
    }

    const { startMinutes, endMinutes } =
        getCurrentHourWindowMinutes();

    /*
     * Untuk rentang normal:
     * 13:00 <= jadwal < 14:00
     *
     * Untuk rentang 23:00–00:00:
     * 23:00 <= jadwal < 24:00
     *
     * Waktu 00:00 masuk ke rentang jam 00:00–01:00,
     * bukan rentang jam 23:00–00:00.
     */

    if (endMinutes >= 1440) {
        return jadwalMinutes >= startMinutes;
    }

    return (
        jadwalMinutes >= startMinutes &&
        jadwalMinutes < endMinutes
    );
}

function getVisibleKA() {
    return dataKA
        .map((ka, index) => ({ ka, index }))
        .filter(item => isWithinCurrentHour(item.ka));
}

function getVisibleIds() {
    return getVisibleKA()
        .map(item => item.index)
        .join(',');
}

/* =====================================================
   WARNA STATUS OTOMATIS
===================================================== */

function getSelisihMenit(ka) {
    const jadwal = timeToMinutes(ka.jadwal);
    const aktual = timeToMinutes(ka.aktual);

    if (jadwal === null || aktual === null) {
        return null;
    }

    let diff = aktual - jadwal;

    if (diff > 720) diff -= 1440;
    if (diff < -720) diff += 1440;

    return diff;
}

function getStatusColor(ka) {
    if (ka.status === 'BATAL') {
        return 'batal';
    }

    if (ka.status !== 'JALAN') {
        return 'belum';
    }

    const selisih = getSelisihMenit(ka);

    if (selisih !== null) {
        if (selisih > BATAS_TELAT_MENIT) {
            return 'telat';
        }

        if (selisih < -BATAS_CEPAT_MENIT) {
            return 'cepat';
        }
    }

    return 'tepat';
}

function getStatusNote(ka, color) {
    if (color === 'batal') {
        return 'KA batal';
    }

    const selisih = getSelisihMenit(ka);

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

            if (
                a.ka.status !== 'BELUM' &&
                b.ka.status !== 'BELUM'
            ) {
                continue;
            }

            let selisih = Math.abs(a.waktu - b.waktu);
            selisih = Math.min(selisih, 1440 - selisih);

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
        (dataKA[index] && dataKA[index].realisasi_ka_id)
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
    simpanRealisasi({ otomatis: true, index });
}

/* =====================================================
   KOMPONEN TABEL
===================================================== */

function createCheckGroup(value, index, field) {
    const yesClass = value === 'yes' ? 'active-yes' : 'inactive';
    const noClass = value === 'no' ? 'active-no' : 'inactive';

    return `
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

    const labelMap = {
        belum: 'BELUM',
        tepat: 'JALAN',
        telat: 'TERLAMBAT',
        cepat: 'LEBIH CEPAT',
        batal: 'BATAL'
    };

    const jalanClassMap = {
        tepat: 'is-tepat',
        telat: 'is-telat',
        cepat: 'is-cepat'
    };

    const jalanActive = ka.status === 'JALAN'
        ? (jalanClassMap[color] || 'is-tepat')
        : '';

    const batalActive = ka.status === 'BATAL' ? 'is-batal' : '';
    const note = getStatusNote(ka, color);

    return `
        <div class="status-badge ${color}">
            ${labelMap[color]}
        </div>

        <div class="status-group">
            <button type="button"
                class="status-btn btn-jalan ${jalanActive}"
                onclick="setStatus(${index}, 'JALAN')"
                aria-pressed="${ka.status === 'JALAN'}">
                JALAN
            </button>

            <button type="button"
                class="status-btn btn-batal ${batalActive}"
                onclick="setStatus(${index}, 'BATAL')"
                aria-pressed="${ka.status === 'BATAL'}">
                BATAL
            </button>
        </div>

        ${note
            ? `<div class="status-note ${color}">${escapeHtml(note)}</div>`
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
    updateAwasBanner(awasInfo);

    const visibleKA = getVisibleKA();

    tbody.innerHTML = '';

    if (!visibleKA.length) {
        const message = dataKA.length === 0
            ? 'Belum ada jadwal KA untuk dinas aktif ini.'
            : 'Tidak ada KA dalam rentang jam ini.';

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

    visibleKA.forEach(({ ka, index }, visibleIndex) => {
        const row = document.createElement('tr');
        row.dataset.index = index;

        if (awasInfo.indexSet.has(index)) {
            row.classList.add('awas-row');
        }

        row.innerHTML = `
            <td>${visibleIndex + 1}</td>

            <td>
                <strong>${escapeHtml(ka.nomor)}</strong>
                ${ka.is_manual
                    ? '<div style="margin-top:3px;color:#d9923e;font-size:8px">MANUAL</div>'
                    : ''}
            </td>

            <td>
                ${escapeHtml(ka.nama)}
                ${awasInfo.indexSet.has(index)
                    ? '<div class="awas-badge">⚠ AWAS 2 ARAH</div>'
                    : ''}
            </td>

            <td>${escapeHtml(ka.hulu || '-')}</td>
            <td>${escapeHtml(ka.hilir || '-')}</td>
            <td>${escapeHtml(ka.relasi || '-')}</td>
            <td>${escapeHtml(ka.jadwal)}</td>

            <td>
                <input type="time"
                    class="actual-time"
                    value="${escapeHtml(ka.aktual)}"
                    onchange="updateActualTime(${index}, this.value)">
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

    const fieldSemboyan = [
        'semboyan21',
        'genta',
        'pintu',
        'semboyan35'
    ];

    if (ka.status === status) {
        ka.status = 'BELUM';
        ka.aktual = '';

        fieldSemboyan.forEach(field => {
            if (ka[field] === 'no') {
                ka[field] = '';
            }
        });
    } else if (status === 'JALAN') {
        const sebelumnya = ka.status;

        ka.status = 'JALAN';
        ka.aktual = getNowHHMM();

        if (sebelumnya === 'BATAL') {
            fieldSemboyan.forEach(field => {
                if (ka[field] === 'no') {
                    ka[field] = '';
                }
            });
        }
    } else {
        ka.status = 'BATAL';
        ka.aktual = '';

        fieldSemboyan.forEach(field => {
            ka[field] = 'no';
        });
    }

    incrementRowVersion(index);
    renderTable();
    tandaiBerubah(JEDA_SIMPAN_STATUS, index);
}

function updateActualTime(index, value) {
    if (!dataKA[index]) {
        return;
    }

    dataKA[index].aktual = value;
    incrementRowVersion(index);

    renderTable();
    tandaiBerubah(JEDA_SIMPAN_STATUS, index);
}

function setCheck(button, index, field, value) {
    if (!dataKA[index]) {
        return;
    }

    const group = button.parentElement;

    group.querySelectorAll('.check').forEach(item => {
        item.classList.remove('active-yes', 'active-no');
        item.classList.add('inactive');
    });

    button.classList.remove('inactive');
    button.classList.add(
        value === 'yes' ? 'active-yes' : 'active-no'
    );

    dataKA[index][field] = value;
    incrementRowVersion(index);

    tandaiBerubah(JEDA_SIMPAN_CHECK, index);
}

function updateKeterangan(index, value) {
    if (!dataKA[index]) {
        return;
    }

    dataKA[index].keterangan = value;
    incrementRowVersion(index);

    tandaiBerubah(JEDA_SIMPAN_KETIK, index);
}

/* =====================================================
   RINGKASAN
===================================================== */

function updateSummary() {
    const visibleData = getVisibleKA().map(item => item.ka);
    const total = visibleData.length;

    let tepat = 0;
    let telat = 0;
    let cepat = 0;
    let batal = 0;
    let belum = 0;

    visibleData.forEach(ka => {
        const color = getStatusColor(ka);

        if (color === 'tepat') tepat++;
        else if (color === 'telat') telat++;
        else if (color === 'cepat') cepat++;
        else if (color === 'batal') batal++;
        else belum++;
    });

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

    autoSaveTimer = setTimeout(() => {
        simpanRealisasi({ otomatis: true });
    }, delay);
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
        const response = await fetch(
            window.realisasiKaConfig.storeUrl,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': ambilCsrfToken()
                },
                body: JSON.stringify({ data: snapshot })
            }
        );

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
                dirtyRows.delete(index);
            } else {
                dirtyRows.add(index);
            }
        });

        savingRows = new Set();
        saveGagal = false;

        if (dirtyRows.size > 0 || isDirty) {
            isDirty = true;

            setSaveStatus(
                'pending',
                'Ada perubahan baru yang belum disimpan…'
            );

            clearTimeout(autoSaveTimer);

            autoSaveTimer = setTimeout(() => {
                simpanRealisasi({ otomatis: true });
            }, JEDA_SIMPAN_STATUS);
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
                    otomatis
                        ? `Tersimpan otomatis pukul ${jam}`
                        : `Tersimpan pukul ${jam}`
                );
            }
        }

        if (!otomatis) {
            alert(
                result.message ||
                'Realisasi KA berhasil disimpan ke database.'
            );
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
                'Gagal menyimpan. Mencoba lagi otomatis…'
            );

            clearTimeout(retryTimer);

            retryTimer = setTimeout(() => {
                if (navigator.onLine && isDirty) {
                    simpanRealisasi({ otomatis: true });
                }
            }, JEDA_COBA_ULANG);
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

            if (isDirty || manual) {
                simpanRealisasi({ otomatis: !manual });
            }
        }
    }
}

async function tungguSimpanSelesai() {
    while (isSaving) {
        await sleep(150);
    }

    if (isDirty) {
        await simpanRealisasi({ otomatis: true });
    }

    while (isSaving) {
        await sleep(150);
    }

    return !isDirty && !saveGagal;
}

function kirimSisaPerubahan() {
    if (!isDirty || isSaving || dataKA.length === 0) {
        return;
    }

    fetch(window.realisasiKaConfig.storeUrl, {
        method: 'POST',
        keepalive: true,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': ambilCsrfToken()
        },
        body: JSON.stringify({ data: dataKA })
    }).catch(error => {
        console.error('Gagal mengirim sisa perubahan:', error);
    });
}

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
        kirimSisaPerubahan();
    }
});

window.addEventListener('pagehide', kirimSisaPerubahan);

window.addEventListener('beforeunload', event => {
    if (isDirty && saveGagal) {
        event.preventDefault();
        event.returnValue = '';
    }
});

window.addEventListener('online', () => {
    if (isDirty) {
        simpanRealisasi({ otomatis: true });
    }
});

window.addEventListener('offline', () => {
    setSaveStatus(
        'error',
        'Tidak ada koneksi. Perubahan akan disimpan saat online.'
    );
});

/* =====================================================
   TOMBOL SELESAI
===================================================== */

const selesaiLink = document.getElementById('selesaiLink');

if (selesaiLink) {
    selesaiLink.addEventListener('click', async event => {
        if (!isDirty && !isSaving && !saveGagal) {
            return;
        }

        event.preventDefault();

        const berhasil = await tungguSimpanSelesai();

        if (berhasil) {
            window.location.href = selesaiLink.href;
            return;
        }

        const lanjut = confirm(
            'Sebagian data belum berhasil tersimpan.\n\n' +
            'Tetap lanjut ke Serah Terima Dinas?'
        );

        if (lanjut) {
            window.location.href = selesaiLink.href;
        }
    });
}

function cetakDokumen() {
    window.print();
}

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
        'manualHulu',
        'manualHilir',
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
    const hulu = ambilNilaiInput('manualHulu');
    const hilir = ambilNilaiInput('manualHilir');
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
        hulu,
        hilir,
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
    tandaiBerubah(JEDA_SIMPAN_STATUS, indexBaru);
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

/* =====================================================
   REFRESH FILTER WAKTU
===================================================== */

let lastVisibleIds = '';

function refreshTimeFilter() {
    updateWindowRange();

    const currentVisibleIds = getVisibleIds();

    /*
     * Tabel hanya dirender ulang jika daftar KA yang terlihat
     * berubah. Ini mencegah input yang sedang diketik ter-reset
     * setiap detik.
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