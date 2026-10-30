/* =====================================================
   AKSI STATUS DAN PEMERIKSAAN
===================================================== */

/**
 * Daftar pemeriksaan semboyan dan perangkat.
 */
const FIELD_PEMERIKSAAN_KA = [
    'semboyan21',
    'genta',
    'pintu',
    'semboyan35'
];


/**
 * Mengubah status realisasi KA.
 *
 * JALAN:
 * - Mengisi jam aktual berdasarkan waktu WIB.
 * - Semua pemeriksaan otomatis YES.
 *
 * BATAL:
 * - Jam aktual dikosongkan.
 * - Semua pemeriksaan otomatis NO.
 *
 * Klik status yang sama:
 * - Mengembalikan status menjadi BELUM.
 * - Mengosongkan jam aktual dan pemeriksaan.
 */
function setStatus(index, status) {
    const ka = dataKA[index];

    if (
        !ka ||
        !['JALAN', 'BATAL'].includes(status)
    ) {
        return;
    }

    // Jika status yang sama diklik kembali,
    // kembalikan realisasi menjadi BELUM.
    if (ka.status === status) {
        ka.status = 'BELUM';
        ka.aktual = '';

        FIELD_PEMERIKSAAN_KA.forEach(field => {
            ka[field] = '';
        });

    } else if (status === 'JALAN') {
        ka.status = 'JALAN';
        ka.aktual = getNowHHMM();

        FIELD_PEMERIKSAAN_KA.forEach(field => {
            ka[field] = 'yes';
        });

    } else {
        ka.status = 'BATAL';
        ka.aktual = '';

        FIELD_PEMERIKSAAN_KA.forEach(field => {
            ka[field] = 'no';
        });
    }

    // Tandai perubahan data.
    incrementRowVersion(index);

    // Perbarui tampilan.
    renderTable();

    // Simpan perubahan secara otomatis.
    tandaiBerubah(JEDA_SIMPAN_STATUS, index);
}


/**
 * Memperbarui jam aktual secara manual.
 *
 * Format yang diperbolehkan:
 * HH:mm
 *
 * Contoh:
 * 08:15
 * 13:45
 * 23:59
 */
function updateActualTime(index, value) {
    const ka = dataKA[index];

    if (!ka) {
        return;
    }

    const jamAktual = String(value ?? '').trim();

    // Validasi format jam HH:mm.
    if (
        jamAktual !== '' &&
        !/^([01]\d|2[0-3]):[0-5]\d$/.test(jamAktual)
    ) {
        return;
    }

    ka.aktual = jamAktual;

    incrementRowVersion(index);

    renderTable();

    tandaiBerubah(JEDA_SIMPAN_STATUS, index);
}


/**
 * Mengubah pemeriksaan semboyan atau perangkat.
 *
 * Field yang diperbolehkan:
 * - semboyan21
 * - genta
 * - pintu
 * - semboyan35
 */
function setCheck(button, index, field, value) {
    const ka = dataKA[index];

    if (
        !ka ||
        !FIELD_PEMERIKSAAN_KA.includes(field)
    ) {
        return;
    }

    // Nilai pemeriksaan yang diperbolehkan.
    const allowedValues = [
        'yes',
        'no',
        ''
    ];

    if (!allowedValues.includes(value)) {
        return;
    }

    ka[field] = value;

    incrementRowVersion(index);

    renderTable();

    tandaiBerubah(JEDA_SIMPAN_CHECK, index);
}


/**
 * Memperbarui keterangan KA.
 *
 * Tidak melakukan render ulang setiap kali pengguna
 * mengetik agar posisi kursor tidak terganggu.
 */
function updateKeterangan(index, value) {
    const ka = dataKA[index];

    if (!ka) {
        return;
    }

    // Batasi panjang keterangan.
    ka.keterangan = String(value ?? '')
        .slice(0, BATAS_KARAKTER_CATATAN);

    incrementRowVersion(index);

    // Simpan otomatis dengan jeda pengetikan.
    tandaiBerubah(JEDA_SIMPAN_KETIK, index);
}


/* =====================================================
   RINGKASAN REALISASI
===================================================== */

/**
 * Menghitung seluruh data KA dalam dinas aktif.
 *
 * Perhitungan tidak mengikuti filter tampilan 60 menit.
 * Semua jadwal tetap dihitung dalam ringkasan.
 *
 * Kategori:
 * - Tepat waktu
 * - Terlambat
 * - Lebih cepat
 * - Batal
 * - Belum diproses
 */
function updateSummary() {
    const allData = Array.isArray(dataKA)
        ? dataKA
        : [];

    const total = allData.length;

    // Jumlah KA yang ditambahkan secara manual.
    const totalManual = allData.filter(
        ka => ka.is_manual === true ||
              ka.is_manual === 1 ||
              ka.is_manual === '1'
    ).length;

    let tepat = 0;
    let telat = 0;
    let cepat = 0;
    let batal = 0;
    let belum = 0;


    // ----------------------------------------------
    // HITUNG STATUS SELURUH JADWAL
    // ----------------------------------------------

    allData.forEach(ka => {
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
            /*
             * Masuk kategori BELUM:
             * - Status BELUM.
             * - Status JALAN tanpa jam aktual.
             * - Data realisasi belum lengkap.
             * - Status lain yang belum memenuhi klasifikasi.
             */
            belum++;
        }
    });


    // ----------------------------------------------
    // UTILITAS UPDATE ELEMEN
    // ----------------------------------------------

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


    // ----------------------------------------------
    // UPDATE ANGKA RINGKASAN
    // ----------------------------------------------

    setText('totalKA', total);
    setText('manualKA', totalManual);

    setText('jalanKA', tepat);
    setText('telatKA', telat);
    setText('cepatKA', cepat);
    setText('batalKA', batal);
    setText('belumKA', belum);


    // ----------------------------------------------
    // PROGRESS REALISASI
    // ----------------------------------------------

    const selesai =
        tepat +
        telat +
        cepat +
        batal;

    const progress = total > 0
        ? Math.round((selesai / total) * 100)
        : 0;

    setText('progressText', `${progress}%`);


    // ----------------------------------------------
    // PROGRESS BAR
    // ----------------------------------------------

    const persen = jumlah => {
        return total > 0
            ? `${(jumlah / total) * 100}%`
            : '0%';
    };

    setWidth('progressGreen', persen(tepat));
    setWidth('progressYellow', persen(telat));
    setWidth('progressBlue', persen(cepat));
    setWidth('progressRed', persen(batal));
    setWidth('progressGray', persen(belum));
}
