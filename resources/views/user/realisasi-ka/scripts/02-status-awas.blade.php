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
    if (diff > 720) {
        diff -= 1440;
    }

    if (diff < -720) {
        diff += 1440;
    }

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
        return 'belum';
    }

    // Keterlambatan tinggi.
    if (
        BATAS_TELAT_TINGGI_MENIT > 0 &&
        selisih >= BATAS_TELAT_TINGGI_MENIT
    ) {
        return 'telat-tinggi';
    }

    // Keterlambatan biasa.
    if (selisih > BATAS_TELAT_MENIT) {
        return 'telat';
    }

    // Lebih cepat dari jadwal.
    if (
        BATAS_CEPAT_MENIT > 0 &&
        selisih < -BATAS_CEPAT_MENIT
    ) {
        return 'cepat';
    }

    return 'tepat';
}


/* =====================================================
   KETERANGAN STATUS
===================================================== */

function getStatusNote(ka, color) {
    if (color === 'batal') {
        return 'KA batal';
    }

    const selisih = getSelisihMenit(ka);

    if (color === 'belum') {
        return '';
    }

    if (selisih === null) {
        return 'Waktu aktual belum tersedia';
    }

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
        if (selisih === 0) {
            return 'Tepat waktu';
        }

        return selisih > 0
            ? `Terlambat ${selisih} menit (dalam toleransi)`
            : `Lebih cepat ${Math.abs(selisih)} menit (dalam toleransi)`;
    }

    return '';
}


/* =====================================================
   ARAH PERJALANAN KA
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

    return 'arah tidak diketahui';
}

function isBerlawananArah(a, b) {
    const arahValid = ['HULU', 'HILIR'];

    if (
        !arahValid.includes(a.arah) ||
        !arahValid.includes(b.arah)
    ) {
        return false;
    }

    return a.arah !== b.arah;
}


/* =====================================================
   WAKTU RUJUKAN KA
===================================================== */

function getWaktuRujukan(ka) {
    if (ka.status === 'JALAN') {
        if (!ka.aktual) {
            return null;
        }

        return timeToMinutes(ka.aktual);
    }

    if (ka.status === 'BELUM') {
        return timeToMinutes(ka.jadwal);
    }

    return null;
}


/* =====================================================
   POSISI WAKTU BERDASARKAN SHIFT
===================================================== */

function getPosisiWaktuAwas(waktu, shiftInfo) {
    if (waktu === null) {
        return null;
    }

    if (!shiftInfo) {
        return waktu;
    }

    if (!shiftInfo.overnight) {
        return waktu - shiftInfo.start;
    }

    if (waktu <= shiftInfo.end) {
        return waktu + 1440 - shiftInfo.start;
    }

    return waktu - shiftInfo.start;
}

function getJarakWaktuAwas(a, b, shiftInfo) {
    const posisiA = getPosisiWaktuAwas(
        a.waktu,
        shiftInfo
    );

    const posisiB = getPosisiWaktuAwas(
        b.waktu,
        shiftInfo
    );

    if (posisiA === null || posisiB === null) {
        return null;
    }

    return Math.abs(posisiA - posisiB);
}


/* =====================================================
   KANDIDAT AWAS 2 ARAH
   MEMERIKSA SELURUH KA DINAS AKTIF
===================================================== */

function getKandidatAwas() {
    return dataKA
        .map((ka, index) => ({
            ka,
            index,
            waktu: getWaktuRujukan(ka)
        }))
        .filter(item =>
            item.ka.status !== 'BATAL' &&
            item.waktu !== null
        );
}


/* =====================================================
   DETEKSI AWAS 2 ARAH
===================================================== */

function cariAwas2Arah() {
    // Periksa aktivasi dan ambang batas Admin.
    if (
        !WARNING_AWAS_2_ARAH_AKTIF ||
        !Number.isFinite(BATAS_AWAS_2_ARAH_MENIT) ||
        BATAS_AWAS_2_ARAH_MENIT <= 0
    ) {
        return {
            indexSet: new Set(),
            pairs: []
        };
    }

    // Mengambil seluruh KA dinas aktif.
    const kandidat = getKandidatAwas();

    const shiftInfo = getShiftTimeInfo();

    const indexSet = new Set();
    const pairs = [];

    for (let i = 0; i < kandidat.length; i++) {
        for (let j = i + 1; j < kandidat.length; j++) {
            const a = kandidat[i];
            const b = kandidat[j];

            // Harus berlawanan arah.
            if (!isBerlawananArah(a.ka, b.ka)) {
                continue;
            }

            // Minimal satu KA belum dikonfirmasi.
            if (
                a.ka.status !== 'BELUM' &&
                b.ka.status !== 'BELUM'
            ) {
                continue;
            }

            // Hitung selisih waktu berdasarkan shift.
            const selisih = getJarakWaktuAwas(
                a,
                b,
                shiftInfo
            );

            if (selisih === null) {
                continue;
            }

            // Di luar batas AWAS 2 ARAH.
            if (selisih > BATAS_AWAS_2_ARAH_MENIT) {
                continue;
            }

            indexSet.add(a.index);
            indexSet.add(b.index);

            pairs.push({
                a,
                b,
                selisih
            });
        }
    }

    // Pasangan dengan jarak waktu terdekat ditampilkan dahulu.
    pairs.sort((a, b) => a.selisih - b.selisih);

    return {
        indexSet,
        pairs
    };
}


/* =====================================================
   TAMPILAN BANNER AWAS 2 ARAH
===================================================== */

function updateAwasBanner(info) {
    const banner = document.getElementById('awasBanner');

    if (!banner) {
        return;
    }

    // Sembunyikan banner jika tidak ada pasangan berbahaya.
    if (!info || !info.pairs || !info.pairs.length) {
        banner.hidden = true;
        banner.innerHTML = '';
        return;
    }

    // Format informasi KA.
    const describe = item => {
        return (
            `KA ${escapeHtml(item.ka.nomor)} ` +
            `(${escapeHtml(getLabelArah(item.ka))}, ` +
            `${escapeHtml(formatMinutes(item.waktu))})`
        );
    };

    // Daftar pasangan KA berlawanan arah.
    const lines = info.pairs.map(pair => `
        <li>
            ${describe(pair.a)} &amp; ${describe(pair.b)}

            <span class="awas-gap">
                selisih ${pair.selisih} menit
            </span>
        </li>
    `).join('');

    banner.innerHTML = `
        <div class="awas-title">
            ⚠ AWAS 2 ARAH
        </div>

        <div class="awas-text">
            KA dari dua arah akan melintas berdekatan.
            Tingkatkan kewaspadaan.
        </div>

        <ul class="awas-list">
            ${lines}
        </ul>
    `;

    banner.hidden = false;
}