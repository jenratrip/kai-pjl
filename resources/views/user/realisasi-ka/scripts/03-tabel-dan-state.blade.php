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
        (
            dataKA[index] &&
            dataKA[index].realisasi_ka_id
        )
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

        const styles = {
            dirty: 'background:#2563eb;color:#fff;border:1px solid #2563eb;',
            saving: 'background:#64748b;color:#fff;border:1px solid #64748b;',
            saved: 'background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;'
        };

        button.className = `row-save-btn ${state}`;

        button.style.cssText = `
            ${styles[state] || styles.dirty}
            padding:6px 7px;
            border-radius:7px;
            font-size:10px;
            font-weight:600;
            white-space:nowrap;
            cursor:pointer;
        `;

        button.textContent = labels[state] || 'Simpan';
        button.disabled = state === 'saving';
    });
}

function simpanBaris(index) {
    if (!dataKA[index]) {
        return;
    }

    if (savingRows.has(index)) {
        return;
    }

    dirtyRows.add(index);
    isDirty = true;

    refreshRowButtons();

    simpanRealisasi({
        index
    });
}


/* =====================================================
   KOMPONEN INDIKATOR CENTANG / SILANG

   KOSONG -> ✓ -> ✕ -> ✓
===================================================== */

function createCheckGroup(value, index, field) {
    const currentState =
        value === 'yes'
            ? 'yes'
            : value === 'no'
                ? 'no'
                : 'empty';

    const styles = {
        yes: 'background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;',
        no: 'background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;',
        empty: 'background:#f8fafc;color:#94a3b8;border:1px solid #cbd5e1;'
    };

    const icons = {
        yes: '✓',
        no: '✕',
        empty: '—'
    };

    return `
        <button
            type="button"
            class="check-toggle inline-flex items-center justify-center w-full max-w-[42px] min-w-0 rounded-md px-1 py-2 text-sm font-bold transition-colors"
            style="${styles[currentState]}"
            onclick="toggleCheck(${index}, '${field}')"
            aria-label="Ubah indikator ${field}"
            title="Klik untuk mengubah centang atau silang"
        >
            ${icons[currentState]}
        </button>
    `;
}

function getNextCheckValue(currentValue) {
    if (currentValue === '') {
        return 'yes';
    }

    if (currentValue === 'yes') {
        return 'no';
    }

    return 'yes';
}

function toggleCheck(index, field) {
    const ka = dataKA[index];

    if (!ka) {
        return;
    }

    const nextValue = getNextCheckValue(
        ka[field] ?? ''
    );

    setCheck(
        null,
        index,
        field,
        nextValue
    );
}


/* =====================================================
   JAM AKTUAL

   KOSONG -> IKON JAM
   KLIK   -> INPUT WAKTU
   TERISI -> TAMPILKAN WAKTU
===================================================== */

function createActualTime(ka, index) {
    if (ka.aktual) {
        return `
            <button
                type="button"
                class="inline-flex w-full min-w-0 items-center justify-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-1 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                onclick="showActualTimeInput(${index})"
                title="Klik untuk mengubah jam aktual"
            >
                <span>${escapeHtml(ka.aktual)}</span>
                <span class="text-[10px]">✎</span>
            </button>
        `;
    }

    return `
        <button
            type="button"
            class="inline-flex w-full items-center justify-center rounded-md border border-slate-200 bg-slate-50 px-2 py-2 text-slate-500 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600"
            onclick="showActualTimeInput(${index})"
            title="Isi jam aktual"
            aria-label="Isi jam aktual"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="17"
                height="17"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </button>
    `;
}

function showActualTimeInput(index) {
    if (!dataKA[index]) {
        return;
    }

    const row = document.querySelector(
        `tr[data-index="${index}"]`
    );

    if (!row) {
        return;
    }

    const cell = row.querySelector('[data-actual-time]');

    if (!cell || cell.querySelector('input[type="time"]')) {
        return;
    }

    const input = document.createElement('input');

    input.type = 'time';

    input.className = [
        'actual-time',
        'w-full',
        'min-w-0',
        'rounded-md',
        'border',
        'border-blue-400',
        'px-1',
        'py-2',
        'text-xs',
        'text-center',
        'focus:outline-none',
        'focus:ring-1',
        'focus:ring-blue-500'
    ].join(' ');

    input.value = dataKA[index].aktual || '';

    cell.replaceChildren(input);

    input.focus();

    input.addEventListener('change', function () {
        updateActualTime(index, this.value);
    });

    input.addEventListener('blur', function () {
        if (!dataKA[index]) {
            return;
        }

        const currentValue = dataKA[index].aktual || '';

        if (this.value === currentValue) {
            renderTable();
        }
    });
}


/* =====================================================
   STATUS KA

   JALAN: SELALU HIJAU
   BATAL: SELALU MERAH
===================================================== */

function createStatus(ka, index) {
    const color = getStatusColor(ka);

    const jalanActive = ka.status === 'JALAN';
    const batalActive = ka.status === 'BATAL';

    const buttonBaseStyle = [
        'flex:1',
        'min-width:0',
        'height:34px',
        'padding:0 5px',
        'border:1px solid',
        'border-radius:7px',
        'font-size:11px',
        'font-weight:700',
        'line-height:1.2',
        'white-space:nowrap',
        'cursor:pointer',
        'display:flex',
        'align-items:center',
        'justify-content:center',
        'transition:all .15s ease'
    ].join(';');

    const jalanStyle = buttonBaseStyle + ';' + (
        jalanActive
            ? 'background:#15803d;color:#fff;border-color:#15803d;'
            : 'background:#dcfce7;color:#166534;border-color:#86efac;'
    );

    const batalStyle = buttonBaseStyle + ';' + (
        batalActive
            ? 'background:#b91c1c;color:#fff;border-color:#b91c1c;'
            : 'background:#fee2e2;color:#991b1b;border-color:#fca5a5;'
    );

    const note = getStatusNote(ka, color);

    return `
        <div style="
            display:flex;
            width:100%;
            min-width:0;
            gap:5px;
            align-items:center;
        ">
            <button
                type="button"
                data-print-status="jalan"
                style="${jalanStyle}"
                onclick="setStatus(${index}, 'JALAN')"
                aria-pressed="${jalanActive}"
            >
                JALAN
            </button>

            <button
                type="button"
                data-print-status="batal"
                style="${batalStyle}"
                onclick="setStatus(${index}, 'BATAL')"
                aria-pressed="${batalActive}"
            >
                BATAL
            </button>
        </div>

        ${
            note
                ? `
                    <div style="
                        margin-top:4px;
                        white-space:normal;
                        overflow-wrap:anywhere;
                        font-size:9px;
                        color:#6b7280;
                    ">
                        ${escapeHtml(note)}
                    </div>
                `
                : ''
        }
    `;
}


/* =====================================================
   TOMBOL AKSI BARIS
===================================================== */

function createRowAction(index) {
    const state = stateBaris(index);

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
            style="
                ${styles[state] || styles.dirty}
                padding:6px 7px;
                border-radius:7px;
                font-size:10px;
                font-weight:600;
                white-space:nowrap;
                cursor:pointer;
            "
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

   1. Nomor KA
   2. Nama KA
   3. Relasi
   4. Jam Jadwal
   5. Jam Aktual
   6. Status
   7. Semboyan 21
   8. Genta
   9. Pintu Perlintasan
   10. Semboyan 35
   11. Keterangan
   12. Aksi
===================================================== */

function renderTable(forPrint = false) {
    const tbody = document.getElementById('kaTableBody');

    if (!tbody) {
        return;
    }

    const awasInfo = cariAwas2Arah();

    updateAwasBanner(awasInfo);

    const visibleKA = forPrint
        ? dataKA.map((ka, index) => ({
            ka,
            index
        }))
        : getVisibleKA();

    tbody.innerHTML = '';

    if (!visibleKA.length) {
        const message = dataKA.length === 0
            ? 'Belum ada jadwal KA untuk dinas aktif ini.'
            : 'Tidak ada KA dalam rentang jam ini.';

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="${JUMLAH_KOLOM_TABEL}"
                    class="px-2 py-8 text-center text-xs text-gray-500"
                >
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

        row.className =
            'border-b border-gray-200 hover:bg-gray-50';

        if (awasInfo.indexSet.has(index)) {
            row.classList.add('awas-row');
        }

        row.innerHTML = `
            <!-- NOMOR KA -->
            <td class="
                break-words
                px-1
                py-2
                text-center
                text-xs
                font-semibold
                text-gray-800
            ">
                ${escapeHtml(ka.nomor)}

                ${
                    ka.is_manual
                        ? `
                            <div class="mt-1 text-[9px] font-medium text-amber-600">
                                MANUAL
                            </div>
                        `
                        : ''
                }
            </td>

            <!-- NAMA KA -->
            <td class="
                break-words
                px-1
                py-2
                text-xs
                font-medium
                text-gray-800
            ">
                ${escapeHtml(ka.nama)}

                ${
                    awasInfo.indexSet.has(index)
                        ? `
                            <div class="awas-badge mt-1 text-[9px]">
                                ⚠ AWAS 2 ARAH
                            </div>
                        `
                        : ''
                }
            </td>

            <!-- RELASI -->
            <td class="
                break-words
                px-1
                py-2
                text-xs
                text-gray-600
            ">
                ${escapeHtml(ka.relasi || '-')}
            </td>

            <!-- JAM JADWAL -->
            <td class="
                whitespace-nowrap
                px-1
                py-2
                text-center
                text-xs
                font-medium
                text-gray-700
            ">
                ${escapeHtml(ka.jadwal || '-')}
            </td>

            <!-- JAM AKTUAL -->
            <td
                class="px-1 py-2 text-center"
                data-actual-time
            >
                ${createActualTime(ka, index)}
            </td>

            <!-- STATUS -->
            <td class="px-1 py-2 text-center">
                ${createStatus(ka, index)}
            </td>

            <!-- SEMBOYAN 21 -->
            <td class="px-1 py-2 text-center">
                ${createCheckGroup(ka.semboyan21, index, 'semboyan21')}
            </td>

            <!-- GENTA -->
            <td class="px-1 py-2 text-center">
                ${createCheckGroup(ka.genta, index, 'genta')}
            </td>

            <!-- PINTU PERLINTASAN -->
            <td class="px-1 py-2 text-center">
                ${createCheckGroup(ka.pintu, index, 'pintu')}
            </td>

            <!-- SEMBOYAN 35 -->
            <td class="px-1 py-2 text-center">
                ${createCheckGroup(ka.semboyan35, index, 'semboyan35')}
            </td>

            <!-- KETERANGAN -->
            <td class="px-1 py-1">
                <input
                    type="text"
                    class="
                        note-input
                        w-full
                        min-w-0
                        rounded
                        border
                        border-gray-300
                        px-1
                        py-1.5
                        text-[9px]
                        focus:border-blue-500
                        focus:outline-none
                        focus:ring-1
                        focus:ring-blue-500
                    "
                    value="${escapeHtml(ka.keterangan || '')}"
                    placeholder="Catatan"
                    maxlength="${BATAS_KARAKTER_CATATAN}"
                    oninput="updateKeterangan(${index}, this.value)"
                >
            </td>

            <!-- AKSI -->
            <td class="px-1 py-2 text-center">
                ${createRowAction(index)}
            </td>
        `;

        tbody.appendChild(row);
    });

    updateSummary();
    updateWindowRange();
    refreshRowButtons();
}
