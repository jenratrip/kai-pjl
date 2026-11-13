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

    if (savedRows.has(index)) {
        return 'saved';
    }

    // Jika belum ada realisasi_ka_id, berarti belum pernah disimpan
    if (!dataKA[index] || !dataKA[index].realisasi_ka_id) {
        // Check apakah ada perubahan dari baseline
        return isRowChanged(index) ? 'dirty' : 'clean';
    }

    // Sudah pernah disimpan (punya realisasi_ka_id)
    // Check apakah ada perubahan dari baseline
    return isRowChanged(index) ? 'dirty' : 'clean';
}

function refreshRowButtons() {
    document.querySelectorAll('[data-save-row]').forEach(button => {
        const index = Number(button.dataset.saveRow);
        const state = stateBaris(index);

        // Sembunyikan tombol jika baris bersih (tidak ada perubahan)
        if (state === 'clean') {
            button.style.display = 'none';
            return;
        }

        button.style.display = '';  // Tampilkan kembali jika ada perubahan

        const labels = {
            dirty: 'Simpan',
            saving: 'Menyimpan…',
            saved: '✓ Tersimpan'
        };

        button.className = `row-save-btn ${state}`;

        const styles = {
            dirty: 'background:#2563eb;color:#fff;border:1px solid #2563eb;',
            saving: 'background:#64748b;color:#fff;border:1px solid #64748b;',
            saved: 'background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;'
        };

        button.style.cssText = `
            ${styles[state] || styles.dirty}
            padding:7px 12px;
            border-radius:7px;
            font-size:12px;
            font-weight:600;
            white-space:nowrap;
            cursor:pointer;
            display:;
        `;

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

    simpanRealisasi();
}


/* =====================================================
   KOMPONEN TABEL
===================================================== */

/*
 * Satu tombol dengan siklus:
 * kosong -> yes -> no -> yes -> no ...
 */
function createCheckGroup(value, index, field) {
    const isYes = value === 'yes';
    const isNo = value === 'no';

    let label = '—';
    let style = `
        background:#fff;
        color:#64748b;
        border:1px solid #cbd5e1;
    `;

    let title = 'Belum diperiksa';

    if (isYes) {
        label = '✓';
        title = 'YA';
        style = `
            background:#dcfce7;
            color:#15803d;
            border:1px solid #86efac;
        `;
    } else if (isNo) {
        label = '×';
        title = 'TIDAK';
        style = `
            background:#fee2e2;
            color:#b91c1c;
            border:1px solid #fca5a5;
        `;
    }

    const buttonBase = [
        'inline-flex items-center justify-center',
        'h-8 w-8 rounded-md',
        'text-sm font-bold transition-colors',
        'focus:outline-none focus:ring-2 focus:ring-offset-1'
    ].join(' ');

    return `
        <div class="check-group flex items-center justify-center">
            <button
                type="button"
                class="check ${buttonBase}"
                style="${style}"
                onclick="setCheck(this, ${index}, '${field}')"
                aria-label="${title}"
                aria-pressed="${isYes || isNo}"
                title="${title}"
            >${label}</button>
        </div>
    `;
}


/* =====================================================
   PERGANTIAN STATUS PEMERIKSAAN
===================================================== */

function setCheck(button, index, field) {
    if (!dataKA[index]) {
        return;
    }

    const currentValue = dataKA[index][field];

    // Kosong -> YA -> TIDAK -> YA
    const nextValue = currentValue === 'yes'
        ? 'no'
        : 'yes';

    // Simpan perubahan pada data lokal.
    dataKA[index][field] = nextValue;

    incrementRowVersion(index);

    // ✓ PERBAIKAN: Deteksi apakah berubah dari baseline
    if (isRowChanged(index)) {
        dirtyRows.add(index);
        savedRows.delete(index);
    } else {
        // Nilai kembali ke baseline
        dirtyRows.delete(index);
    }

    // Perbarui tombol tanpa merender seluruh tabel.
    const group = button.closest('.check-group');

    if (group) {
        group.outerHTML = createCheckGroup(
            nextValue,
            index,
            field
        );
    }

    // Tandai perubahan untuk disimpan nanti.
    tandaiBerubah(index);
}


/* =====================================================
   TAMPILAN JAM AKTUAL
===================================================== */

function createActualTimeDisplay(ka, index) {
    // Jika belum ada jam aktual, tampilkan hanya ikon jam
    if (!ka.aktual) {
        return `
            <div class="actual-time-wrapper flex items-center justify-center gap-2">
                <button
                    type="button"
                    class="time-icon-btn inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 transition-colors"
                    onclick="openActualTimeInput(${index})"
                    title="Klik untuk mengisi jam aktual">
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current stroke-2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 6v6l4 2" />
                    </svg>
                </button>
            </div>
        `;
    }

    // Jika ada jam aktual, tampilkan waktu dengan ikon jam yang bisa diklik
    return `
        <div class="actual-time-wrapper flex items-center justify-center gap-2">
            <button
                type="button"
                class="time-value-btn inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50 transition-colors"
                onclick="openActualTimeInput(${index})"
                title="Klik untuk mengubah jam">
                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="h-3 w-3 mr-1 fill-none stroke-current stroke-2">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 6v6l4 2" />
                </svg>
                <span>${escapeHtml(ka.aktual)}</span>
            </button>
        </div>
    `;
}

function openActualTimeInput(index) {
    const ka = dataKA[index];
    
    if (!ka) {
        return;
    }

    // Cari wrapper jam aktual
    const row = document.querySelector(`tr[data-index="${index}"]`);
    
    if (!row) {
        return;
    }

    const wrapper = row.querySelector('.actual-time-wrapper');
    
    if (!wrapper) {
        return;
    }

    // Tampilkan input time
    wrapper.innerHTML = `
        <input
            type="time"
            class="actual-time-input w-full min-w-0 rounded-md border border-blue-500 px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            value="${escapeHtml(ka.aktual)}"
            onchange="updateActualTimeFromInput(${index}, this.value)"
            onblur="closeActualTimeInput(${index})"
            autofocus>
    `;

    // Focus input
    const input = wrapper.querySelector('input[type="time"]');
    if (input) {
        input.focus();
        input.select?.();
    }
}

function updateActualTimeFromInput(index, value) {
    const ka = dataKA[index];

    if (!ka) {
        return;
    }

    const jamAktual = String(value ?? '').trim();

    // Validasi format jam HH:mm
    if (
        jamAktual !== '' &&
        !/^([01]\d|2[0-3]):[0-5]\d$/.test(jamAktual)
    ) {
        return;
    }

    ka.aktual = jamAktual;

    incrementRowVersion(index);

    // ✓ PERBAIKAN: Deteksi apakah berubah dari baseline
    if (isRowChanged(index)) {
        dirtyRows.add(index);
        savedRows.delete(index);
    } else {
        // Nilai kembali ke baseline
        dirtyRows.delete(index);
    }

    // Tandai perubahan
    tandaiBerubah(index);

    // Render ulang untuk memperbarui tampilan
    renderTable();
}

function closeActualTimeInput(index) {
    const ka = dataKA[index];
    
    if (!ka) {
        return;
    }

    const row = document.querySelector(`tr[data-index="${index}"]`);
    
    if (!row) {
        return;
    }

    const wrapper = row.querySelector('.actual-time-wrapper');
    
    if (!wrapper) {
        return;
    }

    // Tampilkan kembali display jam aktual
    wrapper.innerHTML = createActualTimeDisplay(ka, index);
}


/* =====================================================
   STATUS PERJALANAN KA
===================================================== */

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
                data-print-status="jalan"
                style="${jalanStyle}font-size:18px;"
                onclick="setStatus(${index}, 'JALAN')"
                aria-label="Tandai KA jalan"
                title="Jalan"
                aria-pressed="${jalanActive}">✓</button>

            <button
                type="button"
                data-print-status="batal"
                style="${batalStyle}font-size:18px;"
                onclick="setStatus(${index}, 'BATAL')"
                aria-label="Tandai KA batal"
                title="Batal"
                aria-pressed="${batalActive}">×</button>
        </div>

        ${note
            ? `<div style="margin-top:6px;white-space:normal;overflow-wrap:anywhere;font-size:12px;color:#6b7280;">${escapeHtml(note)}</div>`
            : ''}
    `;
}


/* =====================================================
   TOMBOL SIMPAN PER BARIS
===================================================== */

function createRowAction(index) {
    const state = stateBaris(index);

    // Jika baris bersih, jangan render tombol
    if (state === 'clean') {
        return '';
    }

    const styles = {
        dirty: 'background:#2563eb;color:#fff;border:1px solid #2563eb;',
        saving: 'background:#64748b;color:#fff;border:1px solid #64748b;',
        saved: 'background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;'
    };

    const labels = {
        dirty: 'Simpan',
        saving: 'Menyimpan…',
        saved: '✓ Tersimpan'
    };

    return `
        <button
            type="button"
            class="row-save-btn ${state}"
            style="${styles[state] || styles.dirty} padding:7px 12px; border-radius:7px; font-size:12px; font-weight:600; white-space:nowrap; cursor:pointer;"
            data-save-row="${index}"
            onclick="simpanBaris(${index})"
            ${state === 'saving' ? 'disabled' : ''}
        >
            ${labels[state] || 'Simpan'}
        </button>
    `;
}


/* =====================================================
   RENDER TABEL REALISASI KA
===================================================== */

function renderTable(forPrint = false) {
    const tbody = document.getElementById('kaTableBody');

    if (!tbody) return;

    const awasInfo = cariAwas2Arah();
    updateAwasBanner(awasInfo);

    const visibleKA = forPrint
        ? dataKA.map((ka, index) => ({ ka, index }))
        : getVisibleKA();

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

    visibleKA.forEach(({
        ka,
        index
    }, visibleIndex) => {
        const row = document.createElement('tr');

        row.dataset.index = index;
        row.className = 'border-b border-gray-200 hover:bg-gray-50';

        if (awasInfo.indexSet.has(index)) {
            row.classList.add('awas-row');
        }

        row.innerHTML = `
            <td class="break-words px-1 py-2 text-center text-xs text-gray-600">
                ${visibleIndex + 1}
            </td>

            <td class="break-words px-1 py-2 text-xs font-semibold text-gray-800">
                ${escapeHtml(ka.nomor)}
                ${ka.is_manual
                    ? '<div class="mt-1 text-xs font-medium text-amber-600">MANUAL</div>'
                    : ''}
            </td>

            <td class="break-words px-1 py-2 text-xs text-gray-800">
                ${escapeHtml(ka.nama)}
                ${awasInfo.indexSet.has(index)
                    ? '<div class="awas-badge">⚠ AWAS 2 ARAH</div>'
                    : ''}
            </td>

            <td class="break-words px-1 py-2 text-xs text-gray-600">
                ${escapeHtml(ka.relasi || '-')}
            </td>

            <td class="whitespace-nowrap px-1 py-2 text-center text-xs font-medium text-gray-700">
                ${escapeHtml(ka.jadwal)}
            </td>

            <td class="px-1 py-2 text-center">
                ${createActualTimeDisplay(ka, index)}
            </td>

            <td class="px-1 py-2 text-center">
                ${createStatus(ka, index)}
            </td>

            <td class="px-1 py-2 text-center">
                ${createCheckGroup(ka.semboyan21, index, 'semboyan21')}
            </td>

            <td class="px-1 py-2 text-center">
                ${createCheckGroup(ka.genta, index, 'genta')}
            </td>

            <td class="px-1 py-2 text-center">
                ${createCheckGroup(ka.pintu, index, 'pintu')}
            </td>

            <td class="px-1 py-2 text-center">
                ${createCheckGroup(ka.semboyan35, index, 'semboyan35')}
            </td>

            <td class="px-1 py-2">
                <input
                    type="text"
                    class="note-input w-full min-w-0 rounded-md border border-gray-300 px-2 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
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
