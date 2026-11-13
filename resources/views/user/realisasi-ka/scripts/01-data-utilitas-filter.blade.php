/* =====================================================
   KONFIGURASI UTAMA REALISASI KA
===================================================== */

window.realisasiKaConfig = window.realisasiKaConfig || {};

const CONFIG = window.realisasiKaConfig;
const SETTINGS = CONFIG.settings || {};

if (!Array.isArray(CONFIG.data)) {
    CONFIG.data = [];
}

const ZONA_WAKTU = CONFIG.timeZone || 'Asia/Jakarta';

// -----------------------------------------------------
// PENGATURAN KETERLAMBATAN
// -----------------------------------------------------

const BATAS_TELAT_MENIT = Number(
    SETTINGS.toleransiKeterlambatanMenit ?? 5
);

const BATAS_TELAT_TINGGI_MENIT = Number(
    SETTINGS.peringatanKeterlambatanTinggiMenit ?? 10
);

// -----------------------------------------------------
// PENGATURAN KA LEBIH CEPAT DARI JADWAL
// -----------------------------------------------------

const BATAS_CEPAT_MENIT = Number(
    SETTINGS.batasLebihCepatMenit ?? 2
);

// -----------------------------------------------------
// PENGATURAN JARAK ANTAR KA
// -----------------------------------------------------

const BATAS_JARAK_ANTAR_KA_MENIT = Number(
    SETTINGS.jarakMinimumAntarKaMenit ?? 0
);

// -----------------------------------------------------
// PENGATURAN AWAS 2 ARAH
// -----------------------------------------------------

const BATAS_AWAS_2_ARAH_MENIT = Number(
    SETTINGS.batasAwas2ArahMenit ?? 5
);

// -----------------------------------------------------
// PENGATURAN PENUTUPAN PALANG
// -----------------------------------------------------

const BATAS_PENUTUPAN_PALANG_MENIT = Number(
    SETTINGS.batasPenutupanPalangMenit ?? 0
);

// -----------------------------------------------------
// UTILITAS PEMBACAAN TOGGLE
// -----------------------------------------------------

function isWarningEnabled(value) {
    return (
        value === true ||
        value === 1 ||
        value === '1'
    );
}

// -----------------------------------------------------
// TOGGLE PERINGATAN
// -----------------------------------------------------

const WARNING_KETERLAMBATAN_AKTIF =
    isWarningEnabled(SETTINGS.warningKeterlambatanAktif);

const WARNING_KETERLAMBATAN_TINGGI_AKTIF =
    isWarningEnabled(SETTINGS.warningKeterlambatanTinggiAktif);

const WARNING_LEBIH_AWAL_AKTIF =
    isWarningEnabled(SETTINGS.warningLebihAwalAktif);

const WARNING_ANTAR_KA_AKTIF =
    isWarningEnabled(SETTINGS.warningAntarKaAktif);

const WARNING_AWAS_2_ARAH_AKTIF =
    isWarningEnabled(SETTINGS.warningAwas2ArahAktif);

const WARNING_PENUTUPAN_PALANG_AKTIF =
    isWarningEnabled(SETTINGS.warningPenutupanPalangAktif);

// -----------------------------------------------------
// JEDA PENYIMPANAN
// -----------------------------------------------------


// -----------------------------------------------------
// BATAS TAMPILAN
// -----------------------------------------------------

const BATAS_TAMPIL_JADWAL_MENIT = 60;

const JUMLAH_KOLOM_TABEL = 13;
const BATAS_KARAKTER_CATATAN = 5000;


/* =====================================================
   DATA KA
===================================================== */

let dataKA = CONFIG.data.map((ka, index) => ({
    no: ka.no ?? index + 1,
    nomor: ka.nomor ?? '',
    nama: ka.nama ?? '',
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


// Status awal dari database.
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
   BASELINE DATA UNTUK DETEKSI PERUBAHAN
===================================================== */

/**
 * Snapshot data original dari backend.
 * Digunakan untuk mendeteksi apakah ada perubahan pada setiap baris.
 * Diperbarui setelah penyimpanan backend berhasil.
 */
let originalData = JSON.parse(JSON.stringify(dataKA));

/**
 * Field yang dapat diedit dan perlu dibandingkan untuk deteksi perubahan.
 */
const EDITABLE_FIELDS = [
    'status',
    'aktual',
    'semboyan21',
    'genta',
    'pintu',
    'semboyan35',
    'keterangan'
];

/**
 * Periksa apakah baris mengalami perubahan dari baseline.
 * Membandingkan hanya field yang dapat diedit.
 * 
 * @param {number} index - Index baris
 * @returns {boolean} true jika ada perubahan, false jika sama dengan baseline
 */
function isRowChanged(index) {
    if (!originalData[index] || !dataKA[index]) {
        return false;
    }

    return EDITABLE_FIELDS.some(field => {
        const originalValue = originalData[index][field];
        const currentValue = dataKA[index][field];

        // Normalisasi nilai kosong
        const orig = (originalValue === null || originalValue === undefined) ? '' : String(originalValue);
        const curr = (currentValue === null || currentValue === undefined) ? '' : String(currentValue);

        return orig !== curr;
    });
}

/**
 * Perbarui baseline data setelah penyimpanan berhasil.
 * 
 * @param {number} index - Index baris yang disimpan
 */
function updateBaseline(index) {
    if (dataKA[index]) {
        originalData[index] = JSON.parse(JSON.stringify(dataKA[index]));
    }
}


/* =====================================================
   UTILITAS HTML
===================================================== */

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}


/* =====================================================
   WAKTU WIB
===================================================== */

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


/* =====================================================
   KONVERSI JAM
===================================================== */

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
   POSISI WAKTU BERDASARKAN SHIFT
===================================================== */

function getShiftTimeInfo() {
    const shiftStart = timeToMinutes(CONFIG.shiftStart);
    const shiftEnd = timeToMinutes(CONFIG.shiftEnd);

    if (shiftStart === null || shiftEnd === null) {
        return null;
    }

    const overnight = shiftEnd < shiftStart;

    const duration = overnight
        ? (1440 - shiftStart) + shiftEnd
        : shiftEnd - shiftStart;

    return {
        start: shiftStart,
        end: shiftEnd,
        duration,
        overnight
    };
}

function getRelativeShiftMinute(timeMinutes, shiftInfo) {
    if (!shiftInfo) {
        return timeMinutes;
    }

    if (!shiftInfo.overnight) {
        return timeMinutes - shiftInfo.start;
    }

    if (timeMinutes <= shiftInfo.end) {
        return timeMinutes + 1440 - shiftInfo.start;
    }

    return timeMinutes - shiftInfo.start;
}


/* =====================================================
   SELISIH JADWAL TERHADAP WAKTU SEKARANG
===================================================== */

function getSelisihJadwalMenit(jadwal) {
    const jadwalMinutes = timeToMinutes(jadwal);

    if (jadwalMinutes === null) {
        return null;
    }

    const sekarang = getCurrentMinutes();
    const shiftInfo = getShiftTimeInfo();

    if (!shiftInfo) {
        let selisih = jadwalMinutes - sekarang;

        if (selisih > 720) {
            selisih -= 1440;
        } else if (selisih < -720) {
            selisih += 1440;
        }

        return selisih;
    }

    const posisiSekarang = getRelativeShiftMinute(
        sekarang,
        shiftInfo
    );

    const posisiJadwal = getRelativeShiftMinute(
        jadwalMinutes,
        shiftInfo
    );

    return posisiJadwal - posisiSekarang;
}


/* =====================================================
   STATUS REALISASI TERSIMPAN
===================================================== */

function isStatusSudahDikonfirmasi(ka, index) {
    const statusTersimpan = savedStatuses.get(index);

    const sudahTersimpan =
        ka.realisasi_ka_id !== null &&
        ka.realisasi_ka_id !== undefined &&
        ka.realisasi_ka_id !== '';

    const statusFinal =
        statusTersimpan === 'JALAN' ||
        statusTersimpan === 'BATAL';

    return (
        sudahTersimpan &&
        statusFinal &&
        ka.status === statusTersimpan
    );
}


/* =====================================================
   FILTER JADWAL
===================================================== */

function isWithinCurrentHour(ka, index) {
    if (isStatusSudahDikonfirmasi(ka, index)) {
        return false;
    }

    // KA manual tetap tampil selama belum tersimpan.
    if (ka.is_manual) {
        return !ka.realisasi_ka_id;
    }

    if (!ka.jadwal || ka.jadwal === '-') {
        return false;
    }

    const jadwalMinutes = timeToMinutes(ka.jadwal);

    if (jadwalMinutes === null) {
        return false;
    }

    const selisih = getSelisihJadwalMenit(ka.jadwal);

    if (selisih === null) {
        return false;
    }

    // Jadwal mendatang maksimal 60 menit.
    if (
        selisih > 0 &&
        selisih <= BATAS_TAMPIL_JADWAL_MENIT
    ) {
        return true;
    }

    // Jadwal lewat tetap tampil jika masih dalam shift.
    if (selisih <= 0) {
        const shiftInfo = getShiftTimeInfo();

        if (!shiftInfo) {
            return true;
        }

        const posisiJadwal = getRelativeShiftMinute(
            jadwalMinutes,
            shiftInfo
        );

        const posisiSekarang = getRelativeShiftMinute(
            getCurrentMinutes(),
            shiftInfo
        );

        const jadwalDalamShift =
            posisiJadwal >= 0 &&
            posisiJadwal <= shiftInfo.duration;

        const jadwalSudahLewat =
            posisiJadwal <= posisiSekarang;

        return jadwalDalamShift && jadwalSudahLewat;
    }

    return false;
}


/* =====================================================
   DATA YANG DITAMPILKAN
===================================================== */

function getVisibleKA() {
    return dataKA
        .map((ka, index) => ({
            ka,
            index
        }))
        .filter(item => {
            return isWithinCurrentHour(
                item.ka,
                item.index
            );
        });
}

function getVisibleIds() {
    return getVisibleKA()
        .map(item => item.index)
        .join(',');
}


/* =====================================================
   RENTANG TAMPILAN
===================================================== */

function getCurrentHourWindowMinutes() {
    const startMinutes = getCurrentMinutes();

    const endMinutes =
        startMinutes + BATAS_TAMPIL_JADWAL_MENIT;

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

    const {
        startMinutes,
        endMinutes
    } = getCurrentHourWindowMinutes();

    if (range) {
        const jamMulai = formatMinutes(startMinutes)
            .replace(':', '.');

        const jamSelesai = formatMinutes(endMinutes)
            .replace(':', '.');

        range.textContent =
            `Jadwal 60 menit ke depan: ${jamMulai}–${jamSelesai} WIB`;
    }
}