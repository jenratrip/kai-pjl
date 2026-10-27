window.realisasiKaConfig = window.realisasiKaConfig || {};

const CONFIG = window.realisasiKaConfig;
const SETTINGS = CONFIG.settings || {};

if (!Array.isArray(CONFIG.data)) {
    CONFIG.data = [];
}

const ZONA_WAKTU = CONFIG.timeZone || 'Asia/Jakarta';

const BATAS_CEPAT_MENIT = 2;

const BATAS_TELAT_MENIT = Number(
    SETTINGS.toleransiKeterlambatanMenit ?? 5
);

const BATAS_TELAT_TINGGI_MENIT = Number(
    SETTINGS.peringatanKeterlambatanTinggiMenit ?? 10
);

const BATAS_AWAS_MENIT = Number(
    SETTINGS.jarakMinimumAntarKaMenit ?? 0
);

const BATAS_PENUTUPAN_PALANG_MENIT = Number(
    SETTINGS.batasPenutupanPalangMenit ?? 0
);

const WARNING_KETERLAMBATAN_AKTIF =
    SETTINGS.warningKeterlambatanAktif === true;

const WARNING_KETERLAMBATAN_TINGGI_AKTIF =
    SETTINGS.warningKeterlambatanTinggiAktif === true;

const WARNING_ANTAR_KA_AKTIF =
    SETTINGS.warningAntarKaAktif === true;

const WARNING_PENUTUPAN_PALANG_AKTIF =
    SETTINGS.warningPenutupanPalangAktif === true;

const JEDA_SIMPAN_STATUS = 300;
const JEDA_SIMPAN_CHECK = 600;
const JEDA_SIMPAN_KETIK = 800;
const JEDA_COBA_ULANG = 10000;

const JUMLAH_KOLOM_TABEL = 15;
const BATAS_KARAKTER_CATATAN = 5000;

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
    jadwal_ka_id: ka.jadwal_ka_id ?? null,
    realisasi_ka_id: ka.realisasi_ka_id ?? null,
    is_manual: Boolean(ka.is_manual)
}));

// Status terakhir yang diketahui berhasil tersimpan di server.
const savedStatuses = new Map(
    dataKA.map((ka, index) => [index, ka.status])
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

function getCurrentHourWindowMinutes() {
    const startMinutes = getCurrentMinutes();
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

function isWithinCurrentHour(ka, index) {
    if (ka.is_manual || !ka.jadwal || ka.jadwal === '-') {
        return true;
    }

    const jadwalMinutes = timeToMinutes(ka.jadwal);

    if (jadwalMinutes === null) {
        return false;
    }

    const currentMinutes = getCurrentMinutes();

    // Rentang bergulir: sekarang sampai 60 menit ke depan.
    const selisihMaju =
        (jadwalMinutes - currentMinutes + 1440) % 1440;

    if (selisihMaju <= 60) {
        return true;
    }

    // Deteksi jadwal yang sudah lewat.
    // Termasuk jadwal malam sebelumnya jika dinas melintasi tengah malam.
    const jadwalSudahLewat =
        jadwalMinutes < currentMinutes ||
        (
            currentMinutes < 60 &&
            jadwalMinutes >= 23 * 60
        );

    if (!jadwalSudahLewat) {
        return false;
    }

    const statusSudahDiproses =
        ['JALAN', 'BATAL'].includes(ka.status) &&
        savedStatuses.get(index) === ka.status &&
        !dirtyRows.has(index) &&
        !savingRows.has(index);

    // Jadwal yang lewat tetap tampil sampai statusnya tersimpan.
    return !statusSudahDiproses;
}

function getVisibleKA() {
    return dataKA
        .map((ka, index) => ({ ka, index }))
        .filter(item => isWithinCurrentHour(item.ka, item.index));
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
    if (
        WARNING_KETERLAMBATAN_TINGGI_AKTIF &&
        selisih >= BATAS_TELAT_TINGGI_MENIT
    ) {
        return 'telat-tinggi';
    }

    if (
        WARNING_KETERLAMBATAN_AKTIF &&
        selisih > BATAS_TELAT_MENIT
    ) {
        return 'telat';
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
    simpanRealisasi({ index });
}

/* =====================================================
   KOMPONEN TABEL
===================================================== */


function createCheckGroup(value, index, field) {
    const yesClass = value === 'yes' ? 'active-yes' : 'inactive';
    const noClass = value === 'no' ? 'active-no' : 'inactive';

    return `
        <div class="check-group flex items-center justify-center gap-2">
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
        tepat: 'JALAN',
        telat: 'TERLAMBAT',
        'telat-tinggi': 'TELAT TINGGI',
        cepat: 'LEBIH CEPAT',
        batal: 'BATAL'
    };

    const badgeStyleMap = {
        tepat: 'background:#f0fdf4;color:#15803d;border-color:#bbf7d0;',
        telat: 'background:#fefce8;color:#a16207;border-color:#fde68a;',
        'telat-tinggi': 'background:#fefce8;color:#a16207;border-color:#fde68a;',
        cepat: 'background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;',
        batal: 'background:#fef2f2;color:#b91c1c;border-color:#fecaca;'
    };

    const jalanActive = ka.status === 'JALAN';
    const batalActive = ka.status === 'BATAL';

    const buttonBaseStyle =
        'flex:1;min-width:0;height:36px;padding:0 8px;' +
        'border:1px solid #d5dce5;border-radius:9px;' +
        'box-shadow:none;font-size:12px;font-weight:500;' +
        'line-height:1.2;white-space:nowrap;cursor:pointer;' +
        'display:flex;align-items:center;justify-content:center;';

    const jalanStyle = buttonBaseStyle + (
        jalanActive
            ? 'background:#f0fdf4;color:#15803d;border-color:#bbf7d0;'
            : 'background:#f8fafc;color:#64748b;'
    );

    const batalStyle = buttonBaseStyle + (
        batalActive
            ? 'background:#fef2f2;color:#b91c1c;border-color:#fecaca;'
            : 'background:#f8fafc;color:#64748b;'
    );

    const note = getStatusNote(ka, color);

    return `

        <div style="
            display:flex;
            width:100%;
            min-width:0;
            gap:6px;
            align-items:center;
        ">
            <button
                type="button"
                style="${jalanStyle}"
                onclick="setStatus(${index}, 'JALAN')"
                aria-pressed="${jalanActive}">
                JALAN
            </button>

            <button
                type="button"
                style="${batalStyle}"
                onclick="setStatus(${index}, 'BATAL')"
                aria-pressed="${batalActive}">
                BATAL
            </button>
        </div>

        ${note
            ? `<div style="margin-top:6px;white-space:normal;overflow-wrap:anywhere;font-size:12px;color:#6b7280;">${escapeHtml(note)}</div>`
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

    if (!tbody) return;

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
                    class="px-4 py-8 text-center text-sm text-gray-500">
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
        row.className = 'border-b border-gray-200 hover:bg-gray-50';

        if (awasInfo.indexSet.has(index)) {
            row.classList.add('awas-row');
        }

        row.innerHTML = `
            <td class="whitespace-nowrap px-3 py-3 text-center text-sm text-gray-600">
                ${visibleIndex + 1}
            </td>

            <td class="whitespace-nowrap px-3 py-3 text-sm font-semibold text-gray-800">
                ${escapeHtml(ka.nomor)}
                ${ka.is_manual
                    ? '<div class="mt-1 text-xs font-medium text-amber-600">MANUAL</div>'
                    : ''}
            </td>

            <td class="min-w-36 px-3 py-3 text-sm text-gray-800">
                ${escapeHtml(ka.nama)}
                ${awasInfo.indexSet.has(index)
                    ? '<div class="awas-badge">⚠ AWAS 2 ARAH</div>'
                    : ''}
            </td>

            <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-600">
                ${escapeHtml(ka.hulu || '-')}
            </td>

            <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-600">
                ${escapeHtml(ka.hilir || '-')}
            </td>

            <td class="min-w-28 px-3 py-3 text-sm text-gray-600">
                ${escapeHtml(ka.relasi || '-')}
            </td>

            <td class="whitespace-nowrap px-3 py-3 text-center text-sm font-medium text-gray-700">
                ${escapeHtml(ka.jadwal)}
            </td>

            <td class="min-w-32 px-3 py-3">
                <input
                    type="time"
                    class="actual-time w-full min-w-24 rounded-md border border-gray-300 px-2 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    value="${escapeHtml(ka.aktual)}"
                    onchange="updateActualTime(${index}, this.value)">
            </td>

            <td class="min-w-28 px-3 py-3 text-center">
                ${createStatus(ka, index)}
            </td>

            <td class="min-w-28 px-3 py-3 text-center">
                ${createCheckGroup(ka.semboyan21, index, 'semboyan21')}
            </td>

            <td class="min-w-24 px-3 py-3 text-center">
                ${createCheckGroup(ka.genta, index, 'genta')}
            </td>

            <td class="min-w-36 px-3 py-3 text-center">
                ${createCheckGroup(ka.pintu, index, 'pintu')}
            </td>

            <td class="min-w-28 px-3 py-3 text-center">
                ${createCheckGroup(ka.semboyan35, index, 'semboyan35')}
            </td>

            <td class="min-w-44 px-3 py-3">
                <input
                    type="text"
                    class="note-input w-full min-w-36 rounded-md border border-gray-300 px-2 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    value="${escapeHtml(ka.keterangan)}"
                    placeholder="Keterangan"
                    oninput="updateKeterangan(${index}, this.value)">
            </td>

            <td class="whitespace-nowrap px-3 py-3 text-center">
                ${createRowAction(index)}
            </td>
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
        ka.status = 'JALAN';
        ka.aktual = getNowHHMM();

        fieldSemboyan.forEach(field => {
            ka[field] = 'yes';
        });
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

        if (color === 'tepat') {
            tepat++;
        } else if (
            color === 'telat' ||
            color === 'telat-tinggi'
        ) {
            telat++;
        } else if (color === 'cepat') {
            cepat++;
        } else if (color === 'batal') {
            batal++;
        } else {
            belum++;
        }
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
            body: JSON.stringify({ data: snapshot })
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
                    otomatis
                        ? `Tersimpan otomatis pukul ${jam}`
                        : `Tersimpan pukul ${jam}`
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
