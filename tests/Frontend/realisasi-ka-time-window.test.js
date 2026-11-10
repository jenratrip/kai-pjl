import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const helper = {};
runInNewContext(
    readFileSync(new URL('../../public/js/user/realisasi-ka-time-window.js', import.meta.url), 'utf8'),
    { window: helper },
);

const { RealisasiKaTimeWindow } = helper;
const interactions = {};
const realisasiScript = readFileSync(new URL('../../public/js/user/realisasi-ka.js', import.meta.url), 'utf8');
const blade = readFileSync(new URL('../../resources/views/user/realisasi-ka/index.blade.php', import.meta.url), 'utf8');
const realisasiCss = readFileSync(new URL('../../public/css/user/realisasi-ka.css', import.meta.url), 'utf8');
const timeWindow = RealisasiKaTimeWindow;

runInNewContext(
    readFileSync(new URL('../../public/js/user/realisasi-ka-interactions.js', import.meta.url), 'utf8'),
    { window: interactions },
);

const RealisasiKaInteractions = interactions.RealisasiKaInteractions;

test('keeps the rolling window exact at 23:44 and formats its end after midnight', () => {
    assert.deepEqual(
        JSON.parse(JSON.stringify(RealisasiKaTimeWindow.getWindow(23 * 60 + 44))),
        { startMinutes: 1424, endMinutes: 1484 },
    );
    assert.equal(RealisasiKaTimeWindow.isWithinNextHour('00:44', 1424), true);
    assert.equal(RealisasiKaTimeWindow.isWithinNextHour('00:45', 1424), false);
});

test('keeps the rolling window exact at 10:17 without rounding to the hour', () => {
    assert.deepEqual(
        JSON.parse(JSON.stringify(RealisasiKaTimeWindow.getWindow(10 * 60 + 17))),
        { startMinutes: 617, endMinutes: 677 },
    );
    assert.equal(RealisasiKaTimeWindow.isWithinNextHour('11:17', 617), true);
    assert.equal(RealisasiKaTimeWindow.isWithinNextHour('11:18', 617), false);
});

test('uses the Admin-configured lateness boundary below, at, and above the threshold', () => {
    const fiveMinuteThreshold = {
        toleransiKeterlambatanMenit: 5,
        peringatanKeterlambatanTinggiMenit: 10,
        warningKeterlambatanAktif: true,
        warningKeterlambatanTinggiAktif: true,
    };

    assert.equal(RealisasiKaTimeWindow.getDelayStatus(4, fiveMinuteThreshold), 'tepat');
    assert.equal(RealisasiKaTimeWindow.getDelayStatus(5, fiveMinuteThreshold), 'tepat');
    assert.equal(RealisasiKaTimeWindow.getDelayStatus(6, fiveMinuteThreshold), 'telat');
    assert.equal(RealisasiKaTimeWindow.getDelayStatus(10, fiveMinuteThreshold), 'telat-tinggi');
    assert.equal(RealisasiKaTimeWindow.getDelayStatus(11, fiveMinuteThreshold), 'telat-tinggi');
    assert.match(realisasiScript, /TIME_WINDOW\.getDelayStatus\(selisih, SETTINGS\)/);
});

test('changing the Admin lateness settings changes the classification without changing schedule or actual time', () => {
    const warningSettings = {
        toleransiKeterlambatanMenit: 3,
        peringatanKeterlambatanTinggiMenit: 8,
        warningKeterlambatanAktif: true,
        warningKeterlambatanTinggiAktif: true,
    };
    const higherTolerance = { ...warningSettings, toleransiKeterlambatanMenit: 7 };

    assert.equal(RealisasiKaTimeWindow.getDelayStatus(5, warningSettings), 'telat');
    assert.equal(RealisasiKaTimeWindow.getDelayStatus(5, higherTolerance), 'tepat');
});

test('PDF delay notes never describe over-tolerance delay as within tolerance', () => {
    const disabledWarnings = {
        toleransiKeterlambatanMenit: 5,
        peringatanKeterlambatanTinggiMenit: 10,
        warningKeterlambatanAktif: false,
        warningKeterlambatanTinggiAktif: false,
    };
    const activeWarnings = {
        ...disabledWarnings,
        warningKeterlambatanAktif: true,
        warningKeterlambatanTinggiAktif: true,
    };

    assert.equal(
        RealisasiKaTimeWindow.getPrintDelayNote(4, disabledWarnings),
        'Terlambat 4 menit (dalam toleransi)',
    );
    assert.equal(
        RealisasiKaTimeWindow.getPrintDelayNote(230, disabledWarnings),
        'Terlambat 230 menit (peringatan nonaktif)',
    );
    assert.equal(
        RealisasiKaTimeWindow.getPrintDelayNote(230, activeWarnings),
        'Peringatan keterlambatan tinggi: 230 menit',
    );
    assert.match(realisasiScript, /status-note-print/);
    assert.match(realisasiCss, /\.status-note-print\s*\{\s*display:\s*none/);
    assert.match(realisasiCss, /\.status-note-print\s*\{\s*display:\s*block/);
});

test('print layout compacts table rows while keeping the original readable font size', () => {
    const printStyles = realisasiCss.slice(realisasiCss.lastIndexOf('@media print'));

    assert.match(printStyles, /height:\s*auto\s*!important/);
    assert.match(printStyles, /padding:\s*1mm\s+1\.4mm/);
    assert.match(printStyles, /font-size:\s*8\.2pt/);
    assert.match(printStyles, /thead\s*\{\s*display:\s*table-header-group/);
    assert.match(printStyles, /tr\s*\{[^}]*break-inside:\s*avoid/);
});

test('simultaneous warning indexes include only scheduled rows annotated by Jadwal KA', () => {
    const indexes = RealisasiKaTimeWindow.getSimultaneousRowIndexes([
        { nomor: 'KA-1', is_bersamaan: true, is_manual: false },
        { nomor: 'KA-2', is_bersamaan: false, is_manual: false },
        { nomor: 'KA-3', is_bersamaan: true, is_manual: true },
    ]);

    assert.deepEqual(Array.from(indexes), [0]);
    assert.match(realisasiScript, /row\.classList\.add\('bersamaan-row'\)/);
    assert.match(realisasiScript, /<div class="bersamaan-badge"><span class="screen-only">⚠ BERSAMAAN<\/span><span class="print-only">BERSAMAAN<\/span><\/div>/);
    assert.doesNotMatch(realisasiScript, /updateBersamaanBanner|bersamaanBanner/);
    assert.doesNotMatch(blade, /bersamaanBanner|bersamaan-banner/);
    assert.match(realisasiCss, /tbody tr\.bersamaan-row[\s\S]*background:\s*#fee2e2/);
    assert.match(realisasiCss, /\.bersamaan-badge\s*\{[\s\S]*?padding:\s*1px 5px;[\s\S]*?font-size:\s*8px;/);
});

test('print and save all actions render valid inline SVG icons while preserving their handlers', () => {
    const actions = blade.slice(blade.indexOf('<div class="actions">'), blade.indexOf('</div>', blade.indexOf('<div class="actions">')));

    assert.match(actions, /onclick="cetakDokumen\(\)"[\s\S]*?<svg[\s\S]*?<span>Cetak Dokumen JPL<\/span>/);
    assert.match(actions, /onclick="simpanRealisasi\(\)"[\s\S]*?<svg[\s\S]*?<span[^>]*>Simpan Semua<\/span>/);
    assert.match(actions, /viewBox="0 0 24 24"/);
    assert.doesNotMatch(actions, />\?\s*&nbsp;/);
    assert.match(realisasiScript, /labelTombol\.textContent = 'Menyimpan\.\.\.'/);
    assert.match(realisasiScript, /labelTombol\.textContent = 'Simpan Semua'/);
});

test('PDF print layout uses landscape pages, a reserved repeated document header and repeated table headings', () => {
    assert.match(realisasiCss, /@page\s*\{\s*size:\s*A4 landscape;\s*margin:\s*10mm;/);
    assert.match(blade, /<thead>[\s\S]*?print-document-header-row[\s\S]*?Laporan Realisasi KA[\s\S]*?<th>NO KA/);
    assert.match(realisasiCss, /thead \.print-document-header-row\s*\{\s*display:\s*table-row;/);
    assert.match(realisasiCss, /thead\s*\{\s*display:\s*table-header-group;/);
    assert.match(realisasiCss, /tr\s*\{[^}]*break-inside:\s*avoid;\s*page-break-inside:\s*avoid;/);
    assert.match(realisasiCss, /table-layout:\s*fixed;/);
    assert.match(realisasiCss, /colgroup col:nth-child\(2\) \{ width: 16% !important; \}/);
    assert.match(realisasiCss, /colgroup col:nth-child\(12\) \{ display: none !important; width: 0 !important; \}/);
    assert.match(realisasiCss, /th:nth-child\(4\),[\s\S]*?th:nth-child\(6\)\s*\{\s*white-space:\s*normal;/);
    assert.match(blade, /KAI OPERASIONAL[\s\S]*SISTEM DIGITAL PJL[\s\S]*\$petugasCetak\?->name[\s\S]*Status:/);
    assert.equal((blade.match(/alt="Logo KAI"/g) ?? []).length, 1);
    assert.match(realisasiCss, /\.op-header,[\s\S]*?\.op-sidebar-overlay\s*\{\s*display:\s*none !important;/);
});

test('PDF inspection states use readable text and print rows retain the full shift dataset', () => {
    assert.equal(timeWindow.getCheckPrintLabel('yes'), 'YA');
    assert.equal(timeWindow.getCheckPrintLabel('no'), 'TIDAK');
    assert.equal(timeWindow.getCheckPrintLabel(''), 'BELUM');
    assert.match(realisasiScript, /TIME_WINDOW\.getCheckPrintLabel\(value\)/);
    assert.match(realisasiScript, /actual-time-print/);
    assert.match(realisasiScript, /note-input-print/);
    assert.match(realisasiScript, /ka\.aktual \|\| '-'/);
    assert.match(realisasiScript, /ka\.keterangan \|\| '-'/);
    assert.match(realisasiScript, /window\.addEventListener\('beforeprint',[\s\S]*?isPrinting = true;[\s\S]*?renderTable\(\)/);

    const rows = Array.from({ length: 60 }, (_, index) => ({
        nomor: `KA-${index + 1}`,
        nama: index === 0 ? 'Nama Kereta Sangat Panjang Melintasi Beberapa Stasiun' : `Kereta Uji ${index + 1}`,
        status: ['JALAN', 'BATAL', 'BELUM'][index % 3],
        is_bersamaan: index === 2,
        genta: ['yes', 'no', ''][index % 3],
    }));
    const printRows = timeWindow.getShiftRows(rows);

    assert.equal(printRows.length, 60);
    assert.equal(printRows[0].ka.nama, rows[0].nama);
    assert.equal(printRows[2].ka.is_bersamaan, true);
});

test('web rows show blank placeholders in the midnight window and hide saved or out-of-range rows', () => {
    const rows = RealisasiKaTimeWindow.getWebRows([
        { jadwal_ka_id: 1, jadwal: '00:29', realisasi_ka_id: 1, status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 2, jadwal: '00:39', realisasi_ka_id: 2, status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 3, jadwal: '00:40', realisasi_ka_id: 3, status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 4, jadwal: '00:59', realisasi_ka_id: 4, status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 5, jadwal: '01:03', realisasi_ka_id: 5, status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 6, jadwal: '00:30', realisasi_ka_id: 6, status: 'JALAN', is_manual: false },
        { jadwal_ka_id: 7, jadwal: '00:45', realisasi_ka_id: 7, status: 'BATAL', is_manual: false },
        { jadwal_ka_id: 8, jadwal: '01:05', realisasi_ka_id: null, status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 9, jadwal: '00:20', realisasi_ka_id: 9, status: 'BELUM', aktual: '00:21', is_manual: false },
        { jadwal_ka_id: 10, jadwal: '00:10', realisasi_ka_id: 10, status: 'BELUM', is_manual: true },
        { jadwal_ka_id: 11, jadwal: '23:55', realisasi_ka_id: null, status: 'BELUM', is_manual: false },
    ], 4 + 50 / 60);

    assert.deepEqual(rows.map(({ ka }) => ka.jadwal_ka_id), [1, 2, 3, 4, 5, 10]);
});

test('overdue unsaved schedules in an active overnight shift stay visible despite a placeholder realization ID', () => {
    const rows = RealisasiKaTimeWindow.getWebRows([
        { jadwal_ka_id: 1, jadwal: '23:50', realisasi_ka_id: 81, status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 2, jadwal: '00:01', realisasi_ka_id: 82, status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 3, jadwal: '00:30', realisasi_ka_id: null, status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 4, jadwal: '01:05', realisasi_ka_id: null, status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 5, jadwal: '23:55', realisasi_ka_id: 83, status: 'JALAN', aktual: '23:58', is_manual: false },
        { jadwal_ka_id: 6, jadwal: '18:55', realisasi_ka_id: null, status: 'BELUM', is_manual: false },
    ], 4 + 4 / 60, RealisasiKaTimeWindow.hasRecordedRealization, '19:00', '07:00');

    assert.deepEqual(rows.map(({ ka }) => ka.jadwal_ka_id), [1, 2, 3]);
    assert.equal(RealisasiKaTimeWindow.hasRecordedRealization({
        realisasi_ka_id: 99,
        status: 'BELUM',
        aktual: '',
        semboyan21: '',
        genta: '',
        pintu: '',
        semboyan35: '',
        keterangan: '',
    }), false);
});

test('rows are only treated as saved after every requested row has a valid server confirmation', () => {
    assert.throws(
        () => RealisasiKaTimeWindow.getConfirmedRows(undefined, [2]),
        /tidak mengonfirmasi/,
    );
    assert.throws(
        () => RealisasiKaTimeWindow.getConfirmedRows([{ index: 0, realisasi_ka_id: null }], [2]),
        /belum mengonfirmasi/,
    );
    assert.deepEqual(
        JSON.parse(JSON.stringify(Array.from(RealisasiKaTimeWindow.getConfirmedRows([
            { index: 0, realisasi_ka_id: 501 },
            { index: 1, realisasi_ka_id: 502 },
        ], [2, 7]).entries()))),
        [[2, 501], [7, 502]],
    );

    const savePath = realisasiScript.slice(
        realisasiScript.indexOf('async function simpanRealisasi('),
        realisasiScript.indexOf('function resetManualChecks('),
    );

    assert.ok(savePath.indexOf('TIME_WINDOW.getConfirmedRows(') < savePath.indexOf('persistedRows.add(index)'));
    assert.match(savePath, /catch \(error\)[\s\S]*barisDikirim\.forEach\(index => dirtyRows\.add\(index\)\)/);
});

test('shift print rows and rekap retain all schedules with saved status and actual time', () => {
    const rows = [
        { jadwal_ka_id: 1, jadwal: '23:55', aktual: '23:57', status: 'JALAN', is_manual: false },
        { jadwal_ka_id: 2, jadwal: '00:10', aktual: '', status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 3, jadwal: '03:10', aktual: '', status: 'BATAL', is_manual: false },
    ];
    const printRows = RealisasiKaTimeWindow.getShiftRows(rows);
    const summary = RealisasiKaTimeWindow.summarizeShiftRows(
        printRows.map(({ ka }) => ka),
        (ka) => ({ JALAN: 'telat', BATAL: 'batal', BELUM: 'belum' })[ka.status],
    );

    assert.deepEqual(printRows.map(({ ka }) => ka.jadwal_ka_id), [1, 2, 3]);
    assert.equal(printRows[0].ka.aktual, '23:57');
    assert.equal(printRows[0].ka.status, 'JALAN');
    assert.deepEqual(JSON.parse(JSON.stringify(summary)), {
        total: 3,
        tepat: 0,
        telat: 1,
        cepat: 0,
        batal: 1,
        belum: 1,
    });
});

test('Jalan activates all checks and a second click resets status and checks', () => {
    const ka = {
        status: 'BELUM',
        aktual: '',
        semboyan21: '',
        genta: '',
        pintu: '',
        semboyan35: '',
        keterangan: 'keep this note',
    };

    RealisasiKaInteractions.toggleStatus(ka, 'JALAN', '00:05');
    assert.equal(ka.status, 'JALAN');
    assert.equal(ka.aktual, '00:05');
    assert.deepEqual(
        ['semboyan21', 'genta', 'pintu', 'semboyan35'].map((field) => ka[field]),
        ['yes', 'yes', 'yes', 'yes'],
    );

    RealisasiKaInteractions.setCheckValue(ka, 'genta', 'no');
    assert.equal(ka.status, 'JALAN');
    assert.equal(ka.genta, 'no');

    RealisasiKaInteractions.toggleStatus(ka, 'JALAN', '00:06');
    assert.equal(ka.status, 'BELUM');
    assert.equal(ka.aktual, '');
    assert.deepEqual(
        ['semboyan21', 'genta', 'pintu', 'semboyan35'].map((field) => ka[field]),
        ['', '', '', ''],
    );
    assert.equal(ka.keterangan, 'keep this note');
});

test('Batal resets all checks on a second click and status changes apply the new values', () => {
    const ka = {
        status: 'BELUM',
        aktual: '',
        semboyan21: '',
        genta: '',
        pintu: '',
        semboyan35: '',
    };

    RealisasiKaInteractions.toggleStatus(ka, 'BATAL', '00:05');
    assert.equal(ka.status, 'BATAL');
    assert.deepEqual(
        ['semboyan21', 'genta', 'pintu', 'semboyan35'].map((field) => ka[field]),
        ['no', 'no', 'no', 'no'],
    );

    RealisasiKaInteractions.toggleStatus(ka, 'BATAL', '00:06');
    assert.equal(ka.status, 'BELUM');
    assert.deepEqual(
        ['semboyan21', 'genta', 'pintu', 'semboyan35'].map((field) => ka[field]),
        ['', '', '', ''],
    );

    RealisasiKaInteractions.toggleStatus(ka, 'BATAL', '00:07');
    RealisasiKaInteractions.toggleStatus(ka, 'JALAN', '00:08');
    assert.equal(ka.status, 'JALAN');
    assert.equal(ka.aktual, '00:08');
    assert.deepEqual(
        ['semboyan21', 'genta', 'pintu', 'semboyan35'].map((field) => ka[field]),
        ['yes', 'yes', 'yes', 'yes'],
    );

    RealisasiKaInteractions.toggleStatus(ka, 'BATAL', '00:09');
    assert.equal(ka.status, 'BATAL');
    assert.equal(ka.aktual, '');
    assert.deepEqual(
        ['semboyan21', 'genta', 'pintu', 'semboyan35'].map((field) => ka[field]),
        ['no', 'no', 'no', 'no'],
    );
});

test('manual inspection changes keep the selected Jalan or Batal status unchanged', () => {
    const ka = { status: 'JALAN', genta: 'yes' };

    RealisasiKaInteractions.setCheckValue(ka, 'genta', 'no');
    assert.equal(ka.genta, 'no');
    assert.equal(ka.status, 'JALAN');

    ka.status = 'BATAL';
    RealisasiKaInteractions.setCheckValue(ka, 'genta', 'yes');
    assert.equal(ka.genta, 'yes');
    assert.equal(ka.status, 'BATAL');
});

test('manual inspection changes update internal state before rebuilding the visible check buttons', () => {
    const checkHandler = realisasiScript.slice(
        realisasiScript.indexOf('function setCheck('),
        realisasiScript.indexOf('function updateKeterangan('),
    );

    assert.ok(checkHandler.indexOf('INTERACTIONS.setCheckValue(dataKA[index], field, value)')
        < checkHandler.indexOf('renderTable()'));
    assert.doesNotMatch(checkHandler, /classList|fetch\s*\(|simpanRealisasi\s*\(/);
    assert.match(realisasiScript, /createCheckGroup\(ka\.semboyan21, index, 'semboyan21'\)/);
    assert.match(realisasiScript, /createCheckGroup\(ka\.genta, index, 'genta'\)/);
    assert.match(realisasiScript, /createCheckGroup\(ka\.pintu, index, 'pintu'\)/);
    assert.match(realisasiScript, /createCheckGroup\(ka\.semboyan35, index, 'semboyan35'\)/);
});

test('input handlers only mark local changes while explicit save buttons call the save endpoint', () => {
    const savePath = realisasiScript.slice(
        realisasiScript.indexOf('async function simpanRealisasi('),
        realisasiScript.indexOf('function resetManualChecks('),
    );
    const inputHandlers = realisasiScript.slice(
        realisasiScript.indexOf('function setStatus('),
        realisasiScript.indexOf('/* =====================================================\n   RINGKASAN'),
    );
    const localChangeMarker = realisasiScript.slice(
        realisasiScript.indexOf('function tandaiBerubah('),
        realisasiScript.indexOf('function ambilCsrfToken('),
    );
    const rowSaveHandler = realisasiScript.slice(
        realisasiScript.indexOf('function simpanBaris('),
        realisasiScript.indexOf('/* =====================================================\n   KOMPONEN TABEL'),
    );

    assert.doesNotMatch(inputHandlers, /fetch\s*\(|simpanRealisasi\s*\(/);
    assert.doesNotMatch(localChangeMarker, /setTimeout\s*\(|fetch\s*\(|simpanRealisasi\s*\(/);
    assert.match(rowSaveHandler, /simpanRealisasi\(\{\s*index\s*\}\)/);
    assert.match(blade, /onclick="simpanRealisasi\(\)"/);
    assert.match(savePath, /save_mode:\s*options\.index === undefined \? 'batch' : 'row'/);
    assert.doesNotMatch(realisasiScript, /visibilitychange|pagehide|addEventListener\(['"]online['"]|tungguSimpanSelesai/);
});

test('manual KA form omits Hulu and Hilir and keeps actions in the same form row', () => {
    const formStart = blade.indexOf('id="formTambahKa"');
    const formEnd = blade.indexOf('</section>', formStart);
    const manualForm = blade.slice(formStart, formEnd);
    const gridStart = manualForm.indexOf('class="add-grid"');
    const gridEnd = manualForm.indexOf('</div>', manualForm.indexOf('class="add-actions"'));
    const grid = manualForm.slice(gridStart, gridEnd);

    assert.doesNotMatch(manualForm, /manualHulu|manualHilir|for="manualHulu"|for="manualHilir"/);
    assert.match(grid, /id="manualNomor"[\s\S]*id="manualNama"[\s\S]*id="manualRelasi"/);
    assert.match(grid, /class="add-actions"[\s\S]*onclick="tambahKAManual\(\)"[\s\S]*onclick="toggleFormTambahKA\(false\)"/);
    assert.match(realisasiScript, /hulu:\s*'',\s*hilir:\s*''/);

    const desktopActions = realisasiCss.slice(
        realisasiCss.indexOf('@media (min-width: 1101px)'),
    );

    assert.match(desktopActions, /\.add-actions\s*\{[\s\S]*?gap:\s*4px;[\s\S]*?flex-wrap:\s*nowrap/);
    assert.match(desktopActions, /\.add-grid \.add-actions \.add-button,[\s\S]*?min-height:\s*36px;[\s\S]*?padding:\s*0 8px;[\s\S]*?font-size:\s*10px;/);
});

test('row save targets one KA and Save All targets all dirty rows', () => {
    assert.deepEqual(
        Array.from(RealisasiKaInteractions.getSaveIndexes([2, 5, 7], 5)),
        [5],
    );
    assert.deepEqual(
        Array.from(RealisasiKaInteractions.getSaveIndexes([2, 5, 7])),
        [2, 5, 7],
    );
    assert.deepEqual(
        Array.from(RealisasiKaInteractions.getSaveIndexes([2, 5], undefined, [5])),
        [5],
    );
});
