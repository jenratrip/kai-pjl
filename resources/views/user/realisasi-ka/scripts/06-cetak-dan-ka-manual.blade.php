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
    const tbody = document.getElementById('kaTableBody');

    if (!tbody) return;

    // Tampilkan seluruh KA khusus untuk proses cetak.
    renderTable(true);

    function pulihkanTampilan() {
        renderTable();
        window.removeEventListener('afterprint', pulihkanTampilan);
    }

    window.addEventListener('afterprint', pulihkanTampilan);

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

    const open = typeof forceOpen === 'boolean' ?
        forceOpen :
        form.hidden;

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

    const fieldMap = {
        manual21: 'semboyan21',
        manualGenta: 'genta',
        manualPintu: 'pintu',
        manual35: 'semboyan35'
    };

    const field = fieldMap[group];

    if (!field || !['yes', 'no'].includes(value)) return;

    const buttons = document.querySelectorAll(
        `.mini-check [data-group="${group}"]`
    );

    const baseClasses = [
        'border-slate-300',
        'bg-white',
        'text-slate-500'
    ];

    buttons.forEach(item => {
        item.classList.remove(
            'border-green-300', 'bg-green-100', 'text-green-700',
            'border-red-300', 'bg-red-100', 'text-red-700'
        );

        baseClasses.forEach(className => {
            item.classList.add(className);
        });

        item.setAttribute('aria-pressed', 'false');
    });

    button.classList.remove(...baseClasses);

    button.classList.add(
        value === 'yes' ? 'border-green-300' : 'border-red-300',
        value === 'yes' ? 'bg-green-100' : 'bg-red-100',
        value === 'yes' ? 'text-green-700' : 'text-red-700'
    );

    button.setAttribute('aria-pressed', 'true');

    manualCheck[field] = value;
}

function resetManualChecks() {
    manualCheck = {
        semboyan21: '',
        genta: '',
        pintu: '',
        semboyan35: ''
    };

    document.querySelectorAll('.mini-check [data-group]').forEach(button => {
        button.classList.remove(
            'border-green-300', 'bg-green-100', 'text-green-700',
            'border-red-300', 'bg-red-100', 'text-red-700'
        );

        button.classList.add(
            'border-slate-300',
            'bg-white',
            'text-slate-500'
        );

        button.setAttribute('aria-pressed', 'false');
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